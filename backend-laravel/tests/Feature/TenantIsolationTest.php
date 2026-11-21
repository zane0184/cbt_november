<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Exam;
use App\Models\Assessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private $tenantA;
    private $tenantB;
    private $userA;
    private $userB;
    private $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'Madrasah A', 'code' => 'A']);
        $this->tenantB = Tenant::create(['name' => 'Madrasah B', 'code' => 'B']);

        // Explicitly set context for model instantiation inside setUp to satisfy the strict fail-closed check
        $context = app(\App\Contexts\TenantContext::class);

        $context->setTenantId($this->tenantA->id);
        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin A',
            'email' => 'a@example.com',
            'password' => bcrypt('password')
        ]);

        $context->setTenantId($this->tenantB->id);
        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Admin B',
            'email' => 'b@example.com',
            'password' => bcrypt('password')
        ]);

        $context->setTenantId($this->tenantA->id);
        $this->superAdmin = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Super',
            'email' => 'super@example.com',
            'password' => bcrypt('password')
        ]);
        // Manually role check bypass for simplicity in tests or use real roles
        $this->superAdmin->roles()->create(['name' => 'SUPER_ADMIN', 'label' => 'Super']);

        // Reset context back to clean slate after preparation
        \App\Contexts\TenantContext::resetInstance();
        $context->setTenantId(null);
    }

    public function test_tenant_a_cannot_read_tenant_b_data()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantB->id);
        $yearB = AcademicYear::create(['name' => '2025/2026']);
        $subjectB = Subject::create(['code' => 'B-MATH', 'name' => 'Math B']);

        $this->actingAs($this->userA, 'sanctum');

        $this->assertCount(0, AcademicYear::all());
        $this->assertCount(0, Subject::all());
        $this->assertNull(AcademicYear::find($yearB->id));
        $this->assertNull(Subject::find($subjectB->id));
    }

    public function test_tenant_id_hijacking_is_prevented()
    {
        $this->actingAs($this->userA, 'sanctum');

        $subject = Subject::create([
            'tenant_id' => $this->tenantB->id,
            'code' => 'HIJACK',
            'name' => 'Hijacked Subject'
        ]);

        $this->assertEquals($this->tenantA->id, $subject->tenant_id);
    }

    public function test_cross_tenant_api_access_is_blocked_by_tenant_scope()
    {
        // Create curriculum for Tenant B
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantB->id);
        $curriculumB = Curriculum::create([
            'name' => 'Kurikulum Tenant B',
            'code' => 'K-TENANT-B',
            'is_active' => true
        ]);

        // Access as User A (Tenant A)
        $this->actingAs($this->userA, 'sanctum');

        $response = $this->getJson('/api/v1/curricula/' . $curriculumB->id);

        // Should be 404 because the global scope isolates Tenant B's data from Tenant A
        $response->assertStatus(404);
    }

    public function test_legacy_platform_super_admin_is_rejected_by_tenant_middleware()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $middleware = new \App\Http\Middleware\IdentifyTenant($context);

        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(fn() => $this->superAdmin);
        $request->headers->set('X-Tenant-Override', $this->tenantB->id);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $middleware->handle($request, function() { return new \Illuminate\Http\Response(); });
    }

    public function test_regular_user_cannot_override_tenant_context()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $middleware = new \App\Http\Middleware\IdentifyTenant($context);

        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(fn() => $this->userA);
        $request->headers->set('X-Tenant-Override', $this->tenantB->id);

        $middleware->handle($request, function() { return new \Illuminate\Http\Response(); });

        $this->assertEquals($this->tenantA->id, $context->getTenantId());
    }

    public function test_missing_tenant_context_forces_fail_closed_on_queries()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantA->id);
        $yearA = AcademicYear::create(['name' => '2024/2025']);

        // Explicitly clear any bound context or static instances to simulate missing request context
        \App\Contexts\TenantContext::resetInstance();
        $context->setTenantId(null);

        // Log out out to ensure zero fallback identities
        auth()->logout();

        // Querying should return 0 elements due to 1=0 raw condition injection
        $this->assertCount(0, AcademicYear::all());
    }

    public function test_missing_tenant_context_prevents_creation_unlocked()
    {
        \App\Contexts\TenantContext::resetInstance();
        if (app()->bound(\App\Contexts\TenantContext::class)) {
            app(\App\Contexts\TenantContext::class)->setTenantId(null);
        }
        auth()->logout();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Attempted to create a tenant-scoped model");

        AcademicYear::create(['name' => 'FAIL CREATION']);
    }

    public function test_missing_tenant_context_with_explicit_tenant_id_is_strictly_rejected()
    {
        \App\Contexts\TenantContext::resetInstance();
        if (app()->bound(\App\Contexts\TenantContext::class)) {
            app(\App\Contexts\TenantContext::class)->setTenantId(null);
        }
        auth()->logout();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Attempted to create a tenant-scoped model");

        // Even with explicit tenant_id assigned, it must be rejected since context is null
        $ay = new AcademicYear();
        $ay->tenant_id = $this->tenantA->id;
        $ay->name = 'STRICT REJECT';
        $ay->save();
    }

    public function test_context_a_with_explicit_tenant_b_forces_authoritative_context_a()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $ay = new AcademicYear();
        $ay->tenant_id = $this->tenantB->id; // Explicit Tenant B
        $ay->name = 'AUTHORITATIVE TEST';
        $ay->save();

        // Must be saved into Tenant A because context A is authoritative
        $this->assertEquals($this->tenantA->id, $ay->fresh()->tenant_id);
    }

    public function test_invalid_tenant_override_header_is_ignored_for_tenant_user()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $middleware = new \App\Http\Middleware\IdentifyTenant($context);

        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(fn() => $this->userA);
        $request->headers->set('X-Tenant-Override', 'non-existent-tenant-uuid');

        $middleware->handle($request, function() { return new \Illuminate\Http\Response(); });

        $this->assertEquals($this->tenantA->id, $context->getTenantId());
        $this->assertCount(0, AcademicYear::all());
    }
}
