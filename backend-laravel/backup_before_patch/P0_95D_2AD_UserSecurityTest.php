<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Exam;
use App\Models\Assessment;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Contexts\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSecurityTest extends TestCase
{
    use RefreshDatabase;

    private $tenantA;
    private $tenantB;
    private $adminA;
    private $adminB;
    private $userA;
    private $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'Tenant A', 'code' => 'TENANTA']);
        $this->tenantB = Tenant::create(['name' => 'Tenant B', 'code' => 'TENANTB']);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin']);
        $studentRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);

        // Explicitly set context for model creation
        $context = app(TenantContext::class);

        $context->setTenantId($this->tenantA->id);
        $this->adminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin A',
            'email' => 'admin@a.com',
            'password' => bcrypt('password')
        ]);
        $this->adminA->roles()->attach($adminRole);

        $this->userA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'User A',
            'email' => 'student@a.com',
            'password' => bcrypt('password')
        ]);
        $this->userA->roles()->attach($studentRole);

        $context->setTenantId($this->tenantB->id);
        $this->adminB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Admin B',
            'email' => 'admin@b.com',
            'password' => bcrypt('password')
        ]);
        $this->adminB->roles()->attach($adminRole);

        $this->userB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'User B',
            'email' => 'student@b.com',
            'password' => bcrypt('password')
        ]);
        $this->userB->roles()->attach($studentRole);

        $context->setTenantId(null);
    }

    /**
     * Test that Sanctum can identify the user globally by UUID
     * even without tenant context.
     */
    public function test_sanctum_identifies_user_globally()
    {
        $this->actingAs($this->userA);

        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $this->userA->id)
            ->assertJsonPath('data.tenant_id', $this->tenantA->id);
    }

    /**
     * Test that IdentifyTenant middleware sets the correct context from authenticated user.
     */
    public function test_identify_tenant_middleware_sets_context()
    {
        $context = app(TenantContext::class);
        $middleware = new \App\Http\Middleware\IdentifyTenant($context);

        $request = new \Illuminate\Http\Request();
        $request->setUserResolver(fn() => $this->userA);

        $middleware->handle($request, function() {
            return new \Illuminate\Http\Response();
        });

        $this->assertEquals($this->tenantA->id, $context->getTenantId());
    }

    /**
     * Test that Admin A cannot add User B (from Tenant B) to an Exam in Tenant A.
     */
    public function test_cannot_add_cross_tenant_user_to_exam()
    {
        app(TenantContext::class)->setTenantId($this->tenantA->id);
        $ay = AcademicYear::create(['tenant_id' => $this->tenantA->id, 'name' => '2024']);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $ay->id,
            'title' => 'Test Assessment',
            'assessment_type' => 'DAILY',
            'created_by' => $this->adminA->id
        ]);

        $exam = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessment->id,
            'title' => 'Exam A',
            'duration_seconds' => 3600,
            'created_by' => $this->adminA->id,
            'status' => 'DRAFT',
            'start_at' => now(),
            'end_at' => now()->addHour()
        ]);

        $this->actingAs($this->adminA);

        // Attempt to add User B (from Tenant B) to Exam A (Tenant A)
        $response = $this->postJson("/api/v1/exams/{$exam->id}/participants", [
            'user_id' => $this->userB->id
        ]);

        // Validation should fail because user_id must exist in users table WHERE tenant_id = exam->tenant_id
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    /**
     * Test that two users with same email on different tenants are distinct.
     */
    public function test_same_email_different_tenants_distinct_login()
    {
        $email = 'common@example.com';

        app(TenantContext::class)->setTenantId($this->tenantA->id);
        $uA = User::create(['tenant_id' => $this->tenantA->id, 'name' => 'UA', 'email' => $email, 'password' => bcrypt('passA')]);

        app(TenantContext::class)->setTenantId($this->tenantB->id);
        $uB = User::create(['tenant_id' => $this->tenantB->id, 'name' => 'UB', 'email' => $email, 'password' => bcrypt('passB')]);

        // Login to Tenant A with Tenant A's password
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'TENANTA',
            'email' => $email,
            'password' => 'passA'
        ]);
        $response->assertStatus(200)->assertJsonPath('data.user.id', $uA->id);

        // Login to Tenant A with Tenant B's password should fail
        $response = $this->postJson('/api/v1/auth/login', [
            'tenant_code' => 'TENANTA',
            'email' => $email,
            'password' => 'passB'
        ]);
        $response->assertStatus(422);
    }
}
