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
