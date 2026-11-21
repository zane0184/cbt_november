<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Http\Middleware\EnsureLicenseActive;
use App\Livewire\Admin\ExamManagement;
use App\Livewire\Admin\QuestionBank;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class RoleDashboardVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_is_the_only_admin_entrypoint_and_legacy_portals_are_absent(): void
    {
        $tenant = Tenant::create(['name' => 'Admin Dashboard Madrasah', 'code' => 'ADMINDASH']);
        app(TenantContext::class)->setTenantId($tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $admin = User::create(['name' => 'Admin', 'email' => 'admin.dashboard@example.test', 'password' => bcrypt('password')]);
        $admin->roles()->attach($adminRole->id);
        $guru = User::create(['name' => 'Guru', 'email' => 'guru.dashboard@example.test', 'password' => bcrypt('password')]);
        $guru->roles()->attach($guruRole->id);
        $student = User::create(['name' => 'Siswa', 'email' => 'siswa.dashboard@example.test', 'password' => bcrypt('password')]);
        $student->roles()->attach($studentRole->id);

        $this->withoutMiddleware(EnsureLicenseActive::class);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($guru)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($admin)->get('/')->assertRedirect('/admin/dashboard');

        $this->get('/operator/dashboard')->assertNotFound();
        $this->get('/proktor/assignments')->assertNotFound();

        $this->actingAs($admin);
        Livewire::test(QuestionBank::class)->assertForbidden();
        Livewire::test(ExamManagement::class)->assertForbidden();

        $this->assertSame(0, DB::table('permissions')->count());
        $this->assertSame(0, DB::table('permission_role')->count());
    }
}
