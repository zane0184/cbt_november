<?php

namespace App\Http\Middleware;

use App\Models\ExamAttempt;
use App\Models\LocalInstallationIdentity;
use App\Models\LocalLicenseState;
use App\Services\InstallerStateStore;
use App\Support\Security\PreActivationInstallationGuard;
use App\Services\Licensing\LicenseArtifactVerifier;
use App\Services\Licensing\TrustedKeyStore;
use App\Services\Licensing\StrictJson;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureLicenseActive
{
    public function __construct(protected LicenseArtifactVerifier $verifier, protected TrustedKeyStore $trustedKeyStore, protected InstallerStateStore $installerStateStore) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Explicit test-only bypass. It is disabled by default and cannot bypass
        // licensing in local, staging, or production environments.
        if (app()->environment('testing') && config('license.testing_bypass') === true) return $next($request);

        if ($request->is('up') || $request->is('install/*') || $request->is('license/status') || $request->is('api/v1/health')) return $next($request);

        // Temporary pilot exception: only the exact finalized installation may run
        // without Publisher artifacts, and only within its bounded pilot window.
        if (config('license.pre_activation_2026')) {
            try {
                $install = $this->installerStateStore->read();
                $lockPath = storage_path('installed.lock');
                $lock = is_file($lockPath) ? json_decode((string) file_get_contents($lockPath), true, 32, JSON_THROW_ON_ERROR) : null;
                $tenantId = (string) ($install['tenant_id'] ?? '');
                $identityId = (string) ($install['installation_identity_id'] ?? '');
                $databaseIdentityId = $identityId !== ''
                    ? LocalInstallationIdentity::query()->whereKey($identityId)->where('tenant_id', $tenantId)->value('id')
                    : null;
                $userTenantId = $request->user()?->tenant_id === null ? null : (string) $request->user()->tenant_id;
                $guard = app(PreActivationInstallationGuard::class);

                if ($guard->allows($install, is_array($lock) ? $lock : null, $userTenantId, $databaseIdentityId)) {
                    return $next($request);
                }

                if ($guard->isExpired($install, is_array($lock) ? $lock : null, $userTenantId, $databaseIdentityId)) {
                    if ($this->allowExpiredPilotAttemptRecovery($request, $install)) return $next($request);
                    return $this->blocked($request, 'PRE_ACTIVATION_EXPIRED');
                }

                if (($install['license_mode'] ?? null) === 'PRE_ACTIVATION_2026'
                    || (is_array($lock) && ($lock['license_mode'] ?? null) === 'PRE_ACTIVATION_2026')) {
                    return $this->blocked($request, 'PRE_ACTIVATION_INVALID');
                }
            } catch (Throwable) {
                // Fail closed and continue to Publisher verification path.
            }
        }

        $state = LocalLicenseState::query()->with('installation')->first();
        if (! $state) return $this->blocked($request, 'LICENSE_NOT_ACTIVATED');
        try {
            $this->verifier->assertOperational($state);
            return $next($request);
        } catch (Throwable $exception) {
            if (in_array($this->safeCode($exception), ['LICENSE_OR_LEASE_EXPIRED', 'OFFLINE_LEASE_EXPIRED'], true) && $this->isAttemptRecoveryRequest($request) && $this->allowExistingAttempt($request, $state)) return $next($request);
            return $this->blocked($request, $this->safeCode($exception));
        }
    }

    private function isAttemptRecoveryRequest(Request $request): bool
    {
        if (! str_starts_with($request->path(), 'api/v1/exams/')) return false;
        if (! in_array(strtoupper($request->method()), ['GET','PUT','POST'], true)) return false;
        return preg_match('#^api/v1/exams/[^/]+/attempts/[^/]+/(?:answers(?:/[^/]+)?|submit|heartbeat)$#', $request->path()) === 1;
    }

    private function allowExistingAttempt(Request $request, LocalLicenseState $state): bool
    {
        try {
            if ($state->status !== 'ACTIVE' || ! $state->installation) return false;
            $match = [];
            if (! preg_match('#^api/v1/exams/([^/]+)/attempts/([^/]+)/(?:answers(?:/[^/]+)?|submit|heartbeat)$#', $request->path(), $match)) return false;
            $attempt = ExamAttempt::withoutGlobalScopes()->with(['participant'])->whereKey($match[2])->first();
            if (! $attempt || $attempt->status !== 'IN_PROGRESS' || ! $attempt->started_at || ! $attempt->expires_at || $attempt->expires_at->isPast()) return false;
            if ((string) $attempt->tenant_id !== (string) $state->installation->tenant_id || (string) $attempt->exam_id !== (string) $match[1]) return false;
            if (! $request->user() || (string) $attempt->participant?->user_id !== (string) $request->user()->id) return false;
            $timeFloor = collect([$state->last_observed_at, $state->last_trusted_server_time])->filter()->sortByDesc(fn ($time) => $time->getTimestamp())->first();
            if ($timeFloor && now()->lt($timeFloor->copy()->subSeconds((int) config('license.clock_skew_seconds', 300)))) return false;
            $anchor = $this->trustedKeyStore->read();
            if (($anchor['key_id'] ?? null) !== $state->trusted_key_id || ($anchor['public_key'] ?? null) !== $state->trusted_public_key) return false;
            $publicKey = $this->decode($anchor['public_key']);
            $licenseParts = explode('.', (string) $state->signed_license_artifact);
            $leaseParts = explode('.', (string) $state->signed_lease_artifact);
            if (count($licenseParts) !== 3 || count($leaseParts) !== 3) return false;
            $licenseClaims = StrictJson::decodeObject($this->decode($licenseParts[1]));
            $leaseClaims = StrictJson::decodeObject($this->decode($leaseParts[1]));
            $licenseExpiry = \Illuminate\Support\Carbon::parse($licenseClaims['expires_at'] ?? null)->utc();
            $leaseExpiry = \Illuminate\Support\Carbon::parse($leaseClaims['lease_expires_at'] ?? null)->utc();
            $authorizedUntil = $licenseExpiry->lt($leaseExpiry) ? $licenseExpiry : $leaseExpiry;
            if ($attempt->started_at->gt($authorizedUntil)) return false;
            if (($licenseClaims['status'] ?? null) !== 'ACTIVE' || ($licenseClaims['installation_id'] ?? null) !== $state->installation->installation_id
                || ($licenseClaims['aud'] ?? null) !== config('license.audience') || ($leaseClaims['aud'] ?? null) !== config('license.audience')
                || ($licenseClaims['iss'] ?? null) !== rtrim((string) config('license.publisher_url'), '/') || ($leaseClaims['iss'] ?? null) !== rtrim((string) config('license.publisher_url'), '/')
                || ($licenseClaims['not_before'] ?? null) === null || now()->lt(\Illuminate\Support\Carbon::parse($licenseClaims['not_before']))
                || ($leaseClaims['installation_id'] ?? null) !== $state->installation->installation_id
                || ($licenseClaims['license_id'] ?? null) !== $state->license_id || ($leaseClaims['license_id'] ?? null) !== $state->license_id
                || (int) ($licenseClaims['revision'] ?? 0) !== $state->license_revision || (int) ($leaseClaims['license_revision'] ?? 0) !== $state->license_revision
                || (int) ($leaseClaims['lease_revision'] ?? 0) !== $state->lease_revision
                || ($licenseClaims['product_id'] ?? null) !== ($leaseClaims['product_id'] ?? null)
                || ($licenseClaims['customer_id'] ?? null) !== ($leaseClaims['customer_id'] ?? null)
                || ($licenseClaims['policy_version'] ?? null) !== ($leaseClaims['policy_version'] ?? null)
                || ! \Illuminate\Support\Carbon::parse($leaseClaims['license_expires_at'] ?? null)->equalTo($licenseExpiry)
                || $leaseExpiry->gt($licenseExpiry)) return false;
            if (! function_exists('sodium_crypto_sign_verify_detached')) return false;
            foreach ([[$licenseParts, 'cbt-license+jws'], [$leaseParts, 'cbt-lease+jws']] as [$parts, $type]) {
                $header = json_decode($this->decode($parts[0]), true, 16, JSON_THROW_ON_ERROR);
                if (count($header) !== 3 || ($header['alg'] ?? null) !== 'EdDSA' || ($header['kid'] ?? null) !== $state->trusted_key_id || ($header['typ'] ?? null) !== $type) return false;
                if (! sodium_crypto_sign_verify_detached($this->decode($parts[2]), $parts[0].'.'.$parts[1], $publicKey)) return false;
            }
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /** Allow only a previously-started attempt to finish after the pilot window closes. */
    private function allowExpiredPilotAttemptRecovery(Request $request, array $install): bool
    {
        try {
            if (!$this->isAttemptRecoveryRequest($request) || !$request->user()) return false;
            $match = [];
            if (!preg_match('#^api/v1/exams/([^/]+)/attempts/([^/]+)/(?:answers(?:/[^/]+)?|submit|heartbeat)$#', $request->path(), $match)) return false;

            $attempt = ExamAttempt::withoutGlobalScopes()->with('participant')->whereKey($match[2])->first();
            if (!$attempt || $attempt->status !== 'IN_PROGRESS' || !$attempt->started_at || !$attempt->expires_at) return false;

            $pilotExpiresAt = \\Illuminate\\Support\\Carbon::parse((string) ($install['pre_activation_expires_at'] ?? ''))->utc();
            return (string) $attempt->tenant_id === (string) ($install['tenant_id'] ?? '')
                && (string) $attempt->exam_id === (string) $match[1]
                && (string) $attempt->participant?->user_id === (string) $request->user()->id
                && (string) $request->user()->tenant_id === (string) ($install['tenant_id'] ?? '')
                && $attempt->started_at->lessThanOrEqualTo($pilotExpiresAt)
                && $attempt->expires_at->isFuture();
        } catch (Throwable) {
            return false;
        }
    }

    private function blocked(Request $request, string $code): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) return response()->json(['success' => false, 'code' => $code, 'message' => 'Lisensi CBT tidak aktif atau perlu sinkronisasi dengan Publisher.'], 423);
        return redirect('/license/status')->with('license_error', $code);
    }

    private function safeCode(Throwable $exception): string
    {
        $message = $exception->getMessage();
        return preg_match('/^[A-Z0-9_]+$/', $message) ? $message : 'LICENSE_VALIDATION_FAILED';
    }

    private function decode(?string $value): string
    {
        $value = (string) $value;
        if ($value === '' || preg_match('/[^A-Za-z0-9_-]/', $value)) throw new \RuntimeException('INVALID_BASE64URL');
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false || rtrim(strtr(base64_encode($decoded), '+/', '-_'), '=') !== $value) throw new \RuntimeException('INVALID_BASE64URL');
        return $decoded;
    }
}
