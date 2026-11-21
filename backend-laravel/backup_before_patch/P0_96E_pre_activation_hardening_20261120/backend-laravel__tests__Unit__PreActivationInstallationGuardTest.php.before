<?php

namespace Tests\Unit;

use App\Support\Security\PreActivationInstallationGuard;
use PHPUnit\Framework\TestCase;

class PreActivationInstallationGuardTest extends TestCase
{
    private function validInstall(): array
    {
        return [
            'state' => 'LOCKED',
            'license_mode' => 'PRE_ACTIVATION_2026',
            'tenant_id' => 'tenant-1',
            'admin_id' => 'admin-1',
            'installation_identity_id' => 'identity-1',
            'schema_fingerprint' => str_repeat('a', 64),
            'license_id' => null,
            'license_revision' => null,
        ];
    }

    private function validLock(): array
    {
        return [
            'license_mode' => 'PRE_ACTIVATION_2026',
            'tenant_id' => 'tenant-1',
            'admin_id' => 'admin-1',
            'installation_identity_id' => 'identity-1',
            'schema_fingerprint' => str_repeat('a', 64),
            'license_id' => null,
            'license_revision' => null,
        ];
    }

    public function test_allows_only_the_exact_finalized_installation_identity(): void
    {
        $guard = new PreActivationInstallationGuard();

        self::assertTrue($guard->allows($this->validInstall(), $this->validLock(), 'tenant-1', 'identity-1'));
    }

    public function test_rejects_a_different_identity_even_when_tenant_matches(): void
    {
        $guard = new PreActivationInstallationGuard();

        self::assertFalse($guard->allows($this->validInstall(), $this->validLock(), 'tenant-1', 'identity-other'));
    }

    public function test_rejects_lock_created_by_a_different_admin(): void
    {
        $guard = new PreActivationInstallationGuard();
        $lock = $this->validLock();
        $lock['admin_id'] = 'admin-other';

        self::assertFalse($guard->allows($this->validInstall(), $lock, 'tenant-1', 'identity-1'));
    }

    public function test_rejects_mismatched_schema_fingerprint(): void
    {
        $guard = new PreActivationInstallationGuard();
        $lock = $this->validLock();
        $lock['schema_fingerprint'] = str_repeat('b', 64);

        self::assertFalse($guard->allows($this->validInstall(), $lock, 'tenant-1', 'identity-1'));
    }

    public function test_rejects_any_active_publisher_license_claim_in_pilot_state(): void
    {
        $guard = new PreActivationInstallationGuard();
        $install = $this->validInstall();
        $install['license_id'] = 'publisher-license-1';

        self::assertFalse($guard->allows($install, $this->validLock(), 'tenant-1', 'identity-1'));
    }

    public function test_rejects_missing_lock_or_wrong_authenticated_tenant(): void
    {
        $guard = new PreActivationInstallationGuard();

        self::assertFalse($guard->allows($this->validInstall(), null, 'tenant-1', 'identity-1'));
        self::assertFalse($guard->allows($this->validInstall(), $this->validLock(), 'tenant-2', 'identity-1'));
    }
    public function test_rejects_wrong_install_state_or_license_mode(): void
    {
        $guard = new PreActivationInstallationGuard();
        $install = $this->validInstall();
        $install['state'] = 'INSTALL_VERIFIED';
        self::assertFalse($guard->allows($install, $this->validLock(), 'tenant-1', 'identity-1'));

        $install = $this->validInstall();
        $install['license_mode'] = 'PUBLISHER';
        self::assertFalse($guard->allows($install, $this->validLock(), 'tenant-1', 'identity-1'));

        $lock = $this->validLock();
        $lock['license_mode'] = 'PUBLISHER';
        self::assertFalse($guard->allows($this->validInstall(), $lock, 'tenant-1', 'identity-1'));
    }

    public function test_rejects_tenant_identity_and_admin_binding_mismatches_independently(): void
    {
        $guard = new PreActivationInstallationGuard();

        $lock = $this->validLock();
        $lock['tenant_id'] = 'tenant-other';
        self::assertFalse($guard->allows($this->validInstall(), $lock, 'tenant-1', 'identity-1'));

        $install = $this->validInstall();
        $install['installation_identity_id'] = 'identity-other';
        self::assertFalse($guard->allows($install, $this->validLock(), 'tenant-1', 'identity-1'));

        $lock = $this->validLock();
        $lock['installation_identity_id'] = 'identity-other';
        self::assertFalse($guard->allows($this->validInstall(), $lock, 'tenant-1', 'identity-1'));

        $lock = $this->validLock();
        $lock['admin_id'] = 'admin-other';
        self::assertFalse($guard->allows($this->validInstall(), $lock, 'tenant-1', 'identity-1'));
    }

    public function test_rejects_missing_identity_fingerprint_or_authenticated_tenant(): void
    {
        $guard = new PreActivationInstallationGuard();

        $install = $this->validInstall();
        $install['installation_identity_id'] = null;
        self::assertFalse($guard->allows($install, $this->validLock(), 'tenant-1', 'identity-1'));

        $install = $this->validInstall();
        $install['schema_fingerprint'] = '';
        self::assertFalse($guard->allows($install, $this->validLock(), 'tenant-1', 'identity-1'));

        self::assertFalse($guard->allows($this->validInstall(), $this->validLock(), null, 'identity-1'));
        self::assertFalse($guard->allows($this->validInstall(), $this->validLock(), 'tenant-1', null));
    }

    public function test_rejects_license_revision_claim_in_pilot_lock(): void
    {
        $guard = new PreActivationInstallationGuard();
        $lock = $this->validLock();
        $lock['license_revision'] = 1;

        self::assertFalse($guard->allows($this->validInstall(), $lock, 'tenant-1', 'identity-1'));
    }

}
