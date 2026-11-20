<?php

namespace App\Support\Security;

use Illuminate\Support\Carbon;

/**
 * Validates the narrow 2026 pilot gate using persisted installer state,
 * the installed.lock marker, authenticated tenant, DB identity row, and a
 * bounded issuance/expiry window. This is a temporary pilot gate, not a
 * cryptographically signed Publisher license.
 */
class PreActivationInstallationGuard
{
    public function matchesInstallation(array $install, ?array $lock, ?string $userTenantId, ?string $databaseIdentityId): bool
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

        if ($userTenantId === null || $databaseIdentityId === null
            || !hash_equals($tenantId, (string) ($lock['tenant_id'] ?? ''))
            || !hash_equals($adminId, (string) ($lock['admin_id'] ?? ''))
            || !hash_equals($identityId, (string) ($lock['installation_identity_id'] ?? ''))
            || !hash_equals($identityId, $databaseIdentityId)
            || !hash_equals($tenantId, $userTenantId)
            || !hash_equals($fingerprint, (string) ($lock['schema_fingerprint'] ?? ''))) {
            return false;
        }

        try {
            $issuedAt = Carbon::parse((string) ($install['pre_activation_issued_at'] ?? ''))->utc();
            $expiresAt = Carbon::parse((string) ($install['pre_activation_expires_at'] ?? ''))->utc();
            $lockIssuedAt = Carbon::parse((string) ($lock['pre_activation_issued_at'] ?? ''))->utc();
            $lockExpiresAt = Carbon::parse((string) ($lock['pre_activation_expires_at'] ?? ''))->utc();
            $issueDeadline = Carbon::parse((string) config('license.pre_activation_issue_deadline'))->utc();
        } catch (\Throwable) {
            return false;
        }

        $maxDays = max(1, (int) config('license.pre_activation_duration_days', 180));
        $now = Carbon::now('UTC');

        if ($issuedAt->greaterThan($issueDeadline)
            || $issuedAt->greaterThan($now->copy()->addMinutes(5))
            || $expiresAt->lessThanOrEqualTo($issuedAt)
            || $expiresAt->greaterThan($issuedAt->copy()->addDays($maxDays)->addMinutes(5))
            || !$issuedAt->equalTo($lockIssuedAt)
            || !$expiresAt->equalTo($lockExpiresAt)) {
            return false;
        }

        return true;
    }

    public function allows(array $install, ?array $lock, ?string $userTenantId, ?string $databaseIdentityId): bool
    {
        if (!$this->matchesInstallation($install, $lock, $userTenantId, $databaseIdentityId)) {
            return false;
        }

        try {
            return Carbon::now('UTC')->lessThan(Carbon::parse((string) $install['pre_activation_expires_at'])->utc());
        } catch (\Throwable) {
            return false;
        }
    }

    public function isExpired(array $install, ?array $lock, ?string $userTenantId, ?string $databaseIdentityId): bool
    {
        if (!$this->matchesInstallation($install, $lock, $userTenantId, $databaseIdentityId)) {
            return false;
        }

        try {
            return Carbon::now('UTC')->greaterThanOrEqualTo(Carbon::parse((string) $install['pre_activation_expires_at'])->utc());
        } catch (\Throwable) {
            return false;
        }
    }
}
