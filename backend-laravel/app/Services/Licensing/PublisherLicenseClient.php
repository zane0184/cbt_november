<?php

namespace App\Services\Licensing;

use App\Models\LocalInstallationIdentity;
use App\Models\LocalLicenseEvent;
use App\Models\LocalActivationAttempt;
use App\Models\LocalLicenseState;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class PublisherLicenseClient
{
    public function __construct(
        protected InstallationIdentityService $identityService,
        protected LicenseArtifactVerifier $artifactVerifier,
    ) {}

    public function activate(string $tenantId, string $activationToken, string $trustedKeyId, string $trustedPublicKey): LocalLicenseState
    {
        $base = (string) config('license.publisher_url');
        $parts = parse_url($base);
        if (($parts['scheme'] ?? null) !== 'https' || empty($parts['host'])) throw new RuntimeException('PUBLISHER_HTTPS_REQUIRED');
        if (strlen($trustedKeyId) > 80 || $trustedKeyId === '') throw new RuntimeException('TRUSTED_KEY_ID_INVALID');
        $publicKey = $this->identityService->unb64($trustedPublicKey);
        if (strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) throw new RuntimeException('TRUSTED_PUBLIC_KEY_INVALID');
        $identity = $this->identityService->getOrCreate($tenantId);
        $client = Http::acceptJson()->asJson()->timeout(12)->withOptions(['allow_redirects' => false, 'verify' => true]);

        $pending = LocalActivationAttempt::query()->where('installation_identity_id', $identity->id)->where('status', 'PENDING')->latest('id')->first();
        if ($pending) {
            $challengeId = $pending->challenge_id;
            $proof = $pending->getProof();
        } else {
            $challengeResponse = $client->post($base.'/api/licensing/v1/activations/challenge', [
                'activation_token' => $activationToken,
                'installation_id' => $identity->installation_id,
                'key_id' => $identity->key_id,
                'installation_public_key' => $identity->public_key,
                'product_code' => $identity->product_code,
                'registered_domain' => $identity->registered_domain,
                'product_version' => $identity->product_version,
            ]);
            if (! $challengeResponse->successful()) throw new RuntimeException('PUBLISHER_CHALLENGE_FAILED_'.$challengeResponse->status());
            $challenge = $challengeResponse->json();
            if (! is_array($challenge) || empty($challenge['challenge_id']) || empty($challenge['challenge'])) throw new RuntimeException('PUBLISHER_CHALLENGE_INVALID');
            $audience = $base.'/api/licensing/v1/activations';
            $proofClaims = [
                'iss' => $identity->installation_id, 'aud' => $audience, 'htm' => 'POST', 'htu' => $audience,
                'challenge_id' => $challenge['challenge_id'], 'challenge' => $challenge['challenge'],
                'iat' => now()->timestamp, 'jti' => $this->identityService->b64(random_bytes(24)),
            ];
            $proof = $this->signProof($identity, $proofClaims);
            $pending = LocalActivationAttempt::query()->create([
                'installation_identity_id' => $identity->id, 'challenge_id' => $challenge['challenge_id'],
                'encrypted_challenge' => Crypt::encryptString($challenge['challenge']),
                'encrypted_proof_jws' => Crypt::encryptString($proof), 'status' => 'PENDING',
                'challenge_expires_at' => $challenge['expires_at'] ?? null,
            ]);
            $challengeId = $challenge['challenge_id'];
        }
        $audience = $base.'/api/licensing/v1/activations';
        $activationResponse = $client->post($audience, ['challenge_id' => $challengeId, 'proof_jws' => $proof]);
        if (! $activationResponse->successful()) {
            if ($activationResponse->status() >= 400 && $activationResponse->status() < 500) $pending?->forceFill(['status' => 'FAILED'])->save();
            throw new RuntimeException('PUBLISHER_ACTIVATION_FAILED_'.$activationResponse->status());
        }
        $payload = $activationResponse->json();
        if (! is_array($payload) || empty($payload['signed_license']) || empty($payload['signed_lease']) || empty($payload['license_id'])) throw new RuntimeException('PUBLISHER_ACTIVATION_RESPONSE_INVALID');
        $state = DB::transaction(function () use ($identity, $trustedKeyId, $trustedPublicKey, $payload, $pending): LocalLicenseState {
            $verifiedState = $this->artifactVerifier->verifyAndStore($identity, $trustedKeyId, $trustedPublicKey, $payload);
            $pending?->forceFill(['status' => 'COMPLETED', 'completed_at' => now()])->save();
            return $verifiedState;
        });
        LocalLicenseEvent::query()->create(['installation_identity_id' => $identity->id, 'event_type' => 'LICENSE_ACTIVATED', 'reason_code' => 'PUBLISHER_TOKEN_REDEEMED', 'metadata' => ['license_id' => $state->license_id, 'revision' => $state->license_revision], 'created_at' => now()]);
        return $state;
    }

    public function refresh(): LocalLicenseState
    {
        $identity = LocalInstallationIdentity::query()->firstOrFail();
        $state = LocalLicenseState::query()->where('installation_identity_id', $identity->id)->firstOrFail();
        if (! $state->trusted_key_id || ! $state->trusted_public_key) throw new RuntimeException('LICENSE_TRUST_ANCHOR_MISSING');
        $base = (string) config('license.publisher_url');
        $url = $base.'/api/licensing/v1/leases/refresh';
        $body = json_encode(['refresh' => true], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $idempotency = 'lease-refresh-'.$state->license_revision.'-'.$state->lease_revision.'-'.now()->utc()->format('Ymd');
        $claims = [
            'iss' => $identity->installation_id, 'aud' => $url, 'htm' => 'POST', 'htu' => $url,
            'iat' => now()->timestamp, 'jti' => $this->identityService->b64(random_bytes(24)),
            'license_revision' => $state->license_revision, 'lease_revision' => $state->lease_revision,
            'bhash' => $this->identityService->b64(hash('sha256', $body, true)), 'idem' => $idempotency,
        ];
        $proof = $this->signProof($identity, $claims, 'cbt-pop+jwt');
        $response = Http::acceptJson()->withBody($body, 'application/json')->timeout(12)->withOptions(['allow_redirects' => false, 'verify' => true])->withHeaders([
            'X-Installation-Id' => $identity->installation_id,
            'Idempotency-Key' => $idempotency,
            'X-CBT-Proof' => $proof,
        ])->post($url);
        if (! $response->successful()) {
            $errorCode = (string) ($response->json('error') ?: 'PUBLISHER_LEASE_REFRESH_FAILED_'.$response->status());
            if (in_array($errorCode, ['LICENSE_RESTORE_RECONCILIATION_REQUIRED', 'LICENSE_STATE_AHEAD_OF_PUBLISHER'], true)) {
                $state->forceFill(['status' => 'RESTORE_PENDING', 'last_error_code' => $errorCode, 'last_publisher_check_at' => now()])->save();
                LocalLicenseEvent::query()->create(['installation_identity_id' => $identity->id, 'event_type' => 'LICENSE_RESTORE_BLOCKED', 'reason_code' => $errorCode, 'metadata' => ['local_license_revision' => $state->license_revision, 'local_lease_revision' => $state->lease_revision], 'created_at' => now()]);
            }
            throw new RuntimeException($errorCode);
        }
        $payload = $response->json();
        if (! is_array($payload) || empty($payload['signed_license']) || empty($payload['signed_lease']) || empty($payload['license_status'])) throw new RuntimeException('PUBLISHER_LEASE_RESPONSE_INVALID');
        $payload['license_id'] = $state->license_id;
        $verifiedState = $this->artifactVerifier->verifyAndStore($identity, $state->trusted_key_id, $state->trusted_public_key, $payload);
        LocalLicenseEvent::query()->create(['installation_identity_id' => $identity->id, 'event_type' => 'LICENSE_LEASE_REFRESHED', 'reason_code' => 'PUBLISHER_SYNC', 'metadata' => ['license_revision' => $verifiedState->license_revision, 'lease_revision' => $verifiedState->lease_revision], 'created_at' => now()]);
        return $verifiedState;
    }

    private function signProof(LocalInstallationIdentity $identity, array $claims, string $type = 'cbt-activation+jwt'): string
    {
        $secret = $this->identityService->secretKey($identity);
        $header = $this->identityService->b64(json_encode(['alg' => 'EdDSA', 'kid' => $identity->key_id, 'typ' => $type], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $payload = $this->identityService->b64(json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $input = $header.'.'.$payload;
        return $input.'.'.$this->identityService->b64(sodium_crypto_sign_detached($input, $secret));
    }
}
