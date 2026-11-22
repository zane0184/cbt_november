<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Http\Middleware\EnsureLicenseActive;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_guru_and_siswa_are_portal_roles(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Portal', 'code' => 'PORTAL']);
        app(TenantContext::class)->setTenantId($tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $siswaRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        app(RolePermissionBootstrapper::class)->bootstrap();

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@portal.test', 'password' => bcrypt('password123')]);
        $admin->roles()->attach($adminRole);
        $guru = User::create(['name' => 'Guru', 'email' => 'guru@portal.test', 'password' => bcrypt('password123')]);
        $guru->roles()->attach($guruRole);
        $siswa = User::create(['name' => 'Siswa', 'email' => 'siswa@portal.test', 'password' => bcrypt('password123')]);
        $siswa->roles()->attach($siswaRole);

        $this->assertEqualsCanonicalizing(['ADMIN_MADRASAH', 'GURU', 'SISWA'], Role::query()->pluck('name')->all());
        $this->assertSame(0, Role::whereIn('name', ['KAMAD', 'OPERATOR'])->count());

        app(TenantContext::class)->setTenantId(null);
        $this->withoutMiddleware(EnsureLicenseActive::class);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($guru)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($siswa)->get('/admin/dashboard')->assertForbidden();

        $this->get('/operator/dashboard')->assertNotFound();
        $this->get('/kamad/dashboard')->assertNotFound();
    }
}
