<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Http\Middleware\EnsureLicenseActive;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleTenantBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_madrasah_uses_one_portal_and_legacy_operator_proktor_routes_are_absent(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Role Boundary', 'code' => 'ROLEBOUNDARY']);
        app(TenantContext::class)->setTenantId($tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $admin = User::create([
            'name' => 'Admin Boundary',
            'email' => 'admin.role-boundary@example.test',
            'password' => bcrypt('password'),
        ]);
        $admin->roles()->attach($adminRole->id);

        $guru = User::create([
            'name' => 'Guru Boundary',
            'email' => 'guru.role-boundary@example.test',
            'password' => bcrypt('password'),
        ]);
        $guru->roles()->attach($guruRole->id);

        $student = User::create([
            'name' => 'Siswa Boundary',
            'email' => 'siswa.role-boundary@example.test',
            'password' => bcrypt('password'),
        ]);
        $student->roles()->attach($studentRole->id);

        $this->withoutMiddleware(EnsureLicenseActive::class);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($guru)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/')->assertRedirect('/admin/dashboard');

        $this->get('/operator/dashboard')->assertNotFound();
        $this->get('/proktor/assignments')->assertNotFound();

        // This portal-boundary test does not seed or mutate permission data.
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('permissions')->count());
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('permission_role')->count());
    }

    public function test_tenant_scoped_models_fail_closed_when_tenant_context_is_missing(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Tenant Scope', 'code' => 'TENANTSCOPE']);
        app(TenantContext::class)->setTenantId($tenant->id);

        AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $this->assertSame(1, AcademicYear::query()->count());

        app(TenantContext::class)->setTenantId(null);
        $this->assertSame(0, AcademicYear::query()->count());
    }

    public function test_user_creation_fails_closed_without_a_tenant_context(): void
    {
        app(TenantContext::class)->setTenantId(null);

        $this->expectException(\RuntimeException::class);
        User::create([
            'name' => 'Tenantless User',
            'email' => 'tenantless@example.test',
            'password' => bcrypt('password'),
        ]);
    }
}
