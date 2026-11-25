<?php

namespace App\Services\Licensing;

use App\Models\LocalInstallationIdentity;
use App\Models\Tenant;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use RuntimeException;

class InstallationIdentityService
{
    public function getOrCreate(string $tenantId): LocalInstallationIdentity
    {
        $identities = LocalInstallationIdentity::query()->limit(2)->get();
        if ($identities->count() > 1) throw new RuntimeException('MULTIPLE_INSTALLATION_IDENTITIES');
        $existing = $identities->first();
        if ($existing) {
            if ((string) $existing->tenant_id !== $tenantId || $existing->product_code !== (string) config('license.product_code', 'CBT-MI')) throw new RuntimeException('INSTALLATION_TENANT_MISMATCH');
            if (Tenant::withoutGlobalScopes()->count() !== 1) throw new RuntimeException('CBT_STANDALONE_REQUIRES_ONE_TENANT');
            return $existing;
        }
        if (Tenant::withoutGlobalScopes()->count() !== 1 || ! Tenant::withoutGlobalScopes()->whereKey($tenantId)->exists()) throw new RuntimeException('CBT_STANDALONE_REQUIRES_ONE_TENANT');
        if (! function_exists('sodium_crypto_sign_keypair')) throw new RuntimeException('PHP_SODIUM_REQUIRED');
        if (! config('app.key')) throw new RuntimeException('APP_KEY_REQUIRED');

        $pair = sodium_crypto_sign_keypair();
        $secret = sodium_crypto_sign_secretkey($pair);
        $public = sodium_crypto_sign_publickey($pair);
        $installationId = (string) Str::uuid();
        $keyId = 'inst-'.Str::lower(Str::random(24));
        $appUrl = (string) config('app.url');
        $domain = strtolower((string) parse_url($appUrl, PHP_URL_HOST));
        if (parse_url($appUrl, PHP_URL_SCHEME) !== 'https' || $domain === '' || filter_var('https://'.$domain, FILTER_VALIDATE_URL) === false) throw new RuntimeException('CBT_DOMAIN_REQUIRED');

        return LocalInstallationIdentity::query()->create([
            'tenant_id' => $tenantId,
            'installation_id' => $installationId,
            'key_id' => $keyId,
            'public_key' => $this->b64($public),
            'encrypted_private_key' => Crypt::encryptString(base64_encode($secret)),
            'key_fingerprint' => hash('sha256', $public),
            'registered_domain' => $domain,
            'product_code' => (string) config('license.product_code', 'CBT-MI'),
            'product_version' => (string) config('license.product_version', '1.0.0'),
        ]);
    }

    public function secretKey(LocalInstallationIdentity $identity): string
    {
        $encoded = Crypt::decryptString($identity->encrypted_private_key);
        $secret = base64_decode($encoded, true);
        if ($secret === false || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) throw new RuntimeException('INSTALLATION_PRIVATE_KEY_INVALID');
        return $secret;
    }

    public function b64(string $value): string { return rtrim(strtr(base64_encode($value), '+/', '-_'), '='); }
    public function unb64(string $value): string
    {
        if ($value === '' || preg_match('/[^A-Za-z0-9_-]/', $value)) throw new RuntimeException('INVALID_BASE64URL');
        $decoded = base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true);
        if ($decoded === false || $this->b64($decoded) !== $value) throw new RuntimeException('INVALID_BASE64URL');
        return $decoded;
    }
}
