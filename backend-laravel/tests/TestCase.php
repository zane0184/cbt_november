<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Contexts\TenantContext;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::resetInstance();
        if (app()->bound(TenantContext::class)) {
            app(TenantContext::class)->setTenantId(null);
        }
    }

    protected function bootstrapCbtRolePermissions(): void
    {
        foreach ([
            'ADMIN_MADRASAH' => 'Admin Madrasah',
            'GURU' => 'Guru',
            'SISWA' => 'Siswa',
        ] as $name => $label) {
            \App\Models\Role::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        app(\App\Services\RolePermissionBootstrapper::class)->bootstrap();
    }

    protected function withSyntheticTeacherIdentity(array $attributes): array
    {
        static $sequence = 1;
        $status = strtoupper((string) ($attributes['employment_status'] ?? 'PNS'));

        if ($status === 'HONORER') {
            $nik = $attributes['nik'] ?? ('9' . str_pad((string) $sequence++, 15, '0', STR_PAD_LEFT));
            $attributes['employment_status'] = 'HONORER';
            $attributes['nip'] = null;
            $attributes['nik'] = $nik;
            $attributes['employee_identifier'] = $nik;
            return $attributes;
        }

        if (!in_array($status, ['PNS', 'PPPK'], true)) {
            $status = 'PNS';
        }

        $nip = $attributes['nip'] ?? ('9' . str_pad((string) $sequence++, 17, '0', STR_PAD_LEFT));
        $attributes['employment_status'] = $status;
        $attributes['nip'] = $nip;
        $attributes['nik'] = null;
        $attributes['employee_identifier'] = $nip;

        return $attributes;
    }

    protected function withSyntheticStudentIdentity(array $attributes): array
    {
        static $sequence = 1;
        $nisn = $attributes['nisn'] ?? null;

        if (!is_string($nisn) || preg_match('/^\\d{10}$/D', $nisn) !== 1) {
            $attributes['nisn'] = '9' . str_pad((string) $sequence++, 9, '0', STR_PAD_LEFT);
        }

        return $attributes;
    }

    protected function setTenantContext(?string $tenantId): void
    {
        $context = app(TenantContext::class);
        $context->setTenantId($tenantId);
        if ($instance = TenantContext::getInstance()) {
            $instance->setTenantId($tenantId);
        }
    }

    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        if (isset($user->tenant_id)) {
            $this->setTenantContext($user->tenant_id);
        }

        return parent::actingAs($user, $guard);
    }

    protected function tearDown(): void
    {
        if (app()->bound(TenantContext::class)) {
            app(TenantContext::class)->setTenantId(null);
        }
        TenantContext::resetInstance();
        parent::tearDown();
    }
}
