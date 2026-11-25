<?php

namespace App\Support\Security;

/**
 * Validates the narrow 2026 pre-activation gate using persisted installer state,
 * the immutable installed.lock marker, authenticated tenant, and DB identity row.
 */
class PreActivationInstallationGuard
{
    public function allows(array $install, ?array $lock, ?string $userTenantId, ?string $databaseIdentityId): bool
    {
        if ($lock === null) return false;

        $tenantId = (string) ($install['tenant_id'] ?? '');
        $adminId = (string) ($install['admin_id'] ?? '');
        $identityId = (string) ($install['installation_identity_id'] ?? '');
        $fingerprint = (string) ($install['schema_fingerprint'] ?? '');

        if (($install['state'] ?? null) !== 'LOCKED'
            || ($install['license_mode'] ?? null) !== 'PRE_ACTIVATION_2026'
            || ($lock['license_mode'] ?? null) !== 'PRE_ACTIVATION_2026'
            || $tenantId === '' || $adminId === '' || $identityId === '' || $fingerprint === '') {
            return false;
        }

        if (!empty($install['license_id']) || !empty($install['license_revision'])
            || !empty($lock['license_id']) || !empty($lock['license_revision'])) {
            return false;
        }

        return $userTenantId !== null
            && $databaseIdentityId !== null
            && hash_equals($tenantId, (string) ($lock['tenant_id'] ?? ''))
            && hash_equals($adminId, (string) ($lock['admin_id'] ?? ''))
            && hash_equals($identityId, (string) ($lock['installation_identity_id'] ?? ''))
            && hash_equals($identityId, $databaseIdentityId)
            && hash_equals($tenantId, $userTenantId)
            && hash_equals($fingerprint, (string) ($lock['schema_fingerprint'] ?? ''));
    }
}
