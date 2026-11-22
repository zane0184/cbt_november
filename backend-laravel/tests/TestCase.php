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
            'KAMAD' => 'Kepala Madrasah',
            'GURU' => 'Guru',
        ] as $name => $label) {
            \App\Models\Role::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        app(\App\Services\RolePermissionBootstrapper::class)->bootstrap();
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

        $result = parent::actingAs($user, $guard);

        if ($guard === null || $guard === 'web') {
            $this->app['session']->put(
                'cbt.auth_version.' . $user->getAuthIdentifier(),
                (int) ($user->auth_version ?? 1)
            );
        }

        return $result;
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
