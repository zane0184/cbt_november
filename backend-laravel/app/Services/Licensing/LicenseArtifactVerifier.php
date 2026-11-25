<?php

namespace App\Services\Licensing;

use App\Models\LocalInstallationIdentity;
use App\Models\LocalLicenseState;
use RuntimeException;

class LicenseArtifactVerifier
{
    public function __construct(protected TrustedKeyStore $trustedKeyStore) {}

    public function verifyAndStore(LocalInstallationIdentity $identity, string $keyId, string $publicKeyEncoded, array $response): LocalLicenseState
    {
        if (! function_exists('sodium_crypto_sign_verify_detached')) throw new RuntimeException('PHP_SODIUM_REQUIRED');
        $publicKey = $this->unb64($publicKeyEncoded);
        try {
            $existingAnchor = $this->trustedKeyStore->read();
            if (($existingAnchor['key_id'] ?? null) !== $keyId || ($existingAnchor['public_key'] ?? null) !== $publicKeyEncoded) throw new RuntimeException('TRUST_ANCHOR_CHANGE_REQUIRES_ROTATION');
        } catch (RuntimeException $exception) {
            if ($exception->getMessage() !== 'TRUST_ANCHOR_MISSING') throw $exception;
        }
        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) throw new RuntimeException('TRUSTED_PUBLIC_KEY_INVALID');
        $license = $this->verifyJws((string) ($response['signed_license'] ?? ''), $publicKey, 'cbt-license+jws', $keyId);
        $lease = $this->verifyJws((string) ($response['signed_lease'] ?? ''), $publicKey, 'cbt-lease+jws', $keyId);
        $now = now();
        $licenseClaims = $license['claims']; $leaseClaims = $lease['claims'];
        $expectedAudience = (string) config('license.audience', 'cbt-madrasah-standalone');
        foreach ([$licenseClaims, $leaseClaims] as $claims) {
            if (($claims['aud'] ?? null) !== $expectedAudience || ($claims['installation_id'] ?? null) !== $identity->installation_id || ($claims['status'] ?? 'ACTIVE') !== 'ACTIVE') throw new RuntimeException('LICENSE_BINDING_MISMATCH');
        }
        if (($licenseClaims['iss'] ?? null) !== rtrim((string) config('license.publisher_url'), '/')
            || ($leaseClaims['iss'] ?? null) !== rtrim((string) config('license.publisher_url'), '/')
            || ($licenseClaims['product_id'] ?? null) !== ($leaseClaims['product_id'] ?? null)
            || ($licenseClaims['customer_id'] ?? null) !== ($leaseClaims['customer_id'] ?? null)
            || ($licenseClaims['policy_version'] ?? null) !== ($leaseClaims['policy_version'] ?? null)
            || ($licenseClaims['license_id'] ?? null) !== ($leaseClaims['license_id'] ?? null)
            || ($licenseClaims['license_id'] ?? null) !== ($response['license_id'] ?? null)
            || (int) ($licenseClaims['revision'] ?? 0) !== (int) ($leaseClaims['license_revision'] ?? -1)) throw new RuntimeException('LICENSE_REVISION_MISMATCH');
        $notBefore = $this->date($licenseClaims['not_before'] ?? null);
        $licenseExpiry = $this->date($licenseClaims['expires_at'] ?? null);
        $leaseExpiry = $this->date($leaseClaims['lease_expires_at'] ?? null);
        $leaseLicenseExpiry = $this->date($leaseClaims['license_expires_at'] ?? null);
        $leaseIssuedAt = $this->date($leaseClaims['issued_at'] ?? null);
        if ($notBefore->isFuture() || $licenseExpiry->isPast() || $leaseExpiry->isPast() || $leaseIssuedAt->gt($now->copy()->addSeconds((int) config('license.clock_skew_seconds', 300))) || $leaseExpiry->gt($licenseExpiry) || ! $leaseLicenseExpiry->equalTo($licenseExpiry)) throw new RuntimeException('LICENSE_TIME_WINDOW_INVALID');
        $revision = (int) $licenseClaims['revision'];
        $leaseRevision = (int) $leaseClaims['lease_revision'];
        $existing = LocalLicenseState::query()->where('installation_identity_id', $identity->id)->first();
        if ($existing && ($revision < $existing->license_revision || ($revision === $existing->license_revision && $leaseRevision < $existing->lease_revision))) throw new RuntimeException('LICENSE_ROLLBACK_REJECTED');

        $serverTime = $this->date($leaseClaims['issued_at'] ?? null);
        $this->trustedKeyStore->store($keyId, $publicKeyEncoded);
        $state = LocalLicenseState::query()->updateOrCreate(['installation_identity_id' => $identity->id], [
            'license_id' => $licenseClaims['license_id'], 'status' => 'ACTIVE', 'license_revision' => $revision,
            'lease_revision' => $leaseRevision, 'policy_version' => (int) ($licenseClaims['policy_version'] ?? 1),
            'trusted_key_id' => $keyId, 'trusted_public_key' => $publicKeyEncoded,
            'signed_license_artifact' => $response['signed_license'], 'signed_lease_artifact' => $response['signed_lease'],
            'license_expires_at' => $licenseExpiry, 'lease_expires_at' => $leaseExpiry,
            'last_trusted_server_time' => $serverTime, 'last_observed_at' => $now, 'last_publisher_check_at' => $now,
            'last_error_code' => null,
        ]);
        return $state;
    }

    public function assertOperational(LocalLicenseState $state, bool $allowExistingAttempt = false): void
    {
        if ($state->status !== 'ACTIVE' || ! $state->trusted_public_key || ! $state->signed_license_artifact || ! $state->signed_lease_artifact) throw new RuntimeException('LICENSE_NOT_ACTIVE');
        $anchor = $this->trustedKeyStore->read();
        if (($anchor['key_id'] ?? null) !== $state->trusted_key_id || ($anchor['public_key'] ?? null) !== $state->trusted_public_key) throw new RuntimeException('TRUST_ANCHOR_MISMATCH');
        $publicKey = $this->unb64($anchor['public_key']);
        $license = $this->verifyJws($state->signed_license_artifact, $publicKey, 'cbt-license+jws', $state->trusted_key_id)['claims'];
        $lease = $this->verifyJws($state->signed_lease_artifact, $publicKey, 'cbt-lease+jws', $state->trusted_key_id)['claims'];
        $now = now();
        $licenseExpiry = $this->date($license['expires_at'] ?? null);
        $leaseExpiry = $this->date($lease['lease_expires_at'] ?? null);
        if ($this->date($license['not_before'] ?? null)->isFuture()) throw new RuntimeException('LICENSE_NOT_YET_VALID');
        if ($licenseExpiry->isPast() || $leaseExpiry->isPast()) throw new RuntimeException('LICENSE_OR_LEASE_EXPIRED');
        if (($license['installation_id'] ?? null) !== $state->installation->installation_id || ($lease['installation_id'] ?? null) !== $state->installation->installation_id
            || ($license['iss'] ?? null) !== rtrim((string) config('license.publisher_url'), '/') || ($lease['iss'] ?? null) !== rtrim((string) config('license.publisher_url'), '/')
            || ($license['aud'] ?? null) !== config('license.audience') || ($lease['aud'] ?? null) !== config('license.audience')
            || ($license['status'] ?? null) !== 'ACTIVE') throw new RuntimeException('LICENSE_INSTALLATION_MISMATCH');
        if (($license['license_id'] ?? null) !== $state->license_id || ($lease['license_id'] ?? null) !== $state->license_id
            || ($lease['license_revision'] ?? null) !== $state->license_revision || ($lease['installation_id'] ?? null) !== $state->installation->installation_id
            || (int) ($license['revision'] ?? 0) !== $state->license_revision || (int) ($lease['lease_revision'] ?? 0) !== $state->lease_revision
            || $this->date($lease['lease_expires_at'] ?? null)->gt($licenseExpiry)) throw new RuntimeException('LICENSE_LOCAL_REVISION_MISMATCH');
        $trustedFloor = $this->trustedTimeFloor($state);
        if ($now->lt($trustedFloor->copy()->subSeconds((int) config('license.clock_skew_seconds', 300)))) throw new RuntimeException('CLOCK_UNTRUSTED');
        if ($now->gt($leaseExpiry)) throw new RuntimeException('OFFLINE_LEASE_EXPIRED');
        if (! $state->last_observed_at || $now->gt($state->last_observed_at)) $state->forceFill(['last_observed_at' => $now])->save();
    }

    private function verifyJws(string $token, string $publicKey, string $type, string $keyId): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) throw new RuntimeException('JWS_MALFORMED');
        $header = StrictJson::decodeObject($this->unb64($parts[0]));
        if (count($header) !== 3 || ($header['alg'] ?? null) !== 'EdDSA' || ($header['typ'] ?? null) !== $type || ($header['kid'] ?? null) !== $keyId) throw new RuntimeException('JWS_HEADER_REJECTED');
        $signature = $this->unb64($parts[2]);
        if (strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES || ! sodium_crypto_sign_verify_detached($signature, $parts[0].'.'.$parts[1], $publicKey)) throw new RuntimeException('JWS_SIGNATURE_INVALID');
        $claims = StrictJson::decodeObject($this->unb64($parts[1]));
        if (! is_array($claims) || array_is_list($claims)) throw new RuntimeException('JWS_CLAIMS_INVALID');
        return ['header' => $header, 'claims' => $claims];
    }

    private function trustedTimeFloor(LocalLicenseState $state): \Illuminate\Support\Carbon
    {
        $values = array_filter([$state->last_observed_at, $state->last_trusted_server_time]);
        if ($values === []) return now()->utc();
        return collect($values)->sortByDesc(fn ($value) => $value->getTimestamp())->first()->copy();
    }

    private function date(mixed $value): \Illuminate\Support\Carbon
    {
        if (! is_string($value) || $value === '') throw new RuntimeException('LICENSE_DATE_CLAIM_MISSING');
        return \Illuminate\Support\Carbon::parse($value)->utc();
    }

    private function unb64(string $value): string
    {
        if ($value === '' || preg_match('/[^A-Za-z0-9_-]/', $value)) throw new RuntimeException('INVALID_BASE64URL');
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false || rtrim(strtr(base64_encode($decoded), '+/', '-_'), '=') !== $value) throw new RuntimeException('INVALID_BASE64URL');
        return $decoded;
    }
}
