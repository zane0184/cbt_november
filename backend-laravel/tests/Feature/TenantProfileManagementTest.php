<?php

namespace Tests\Feature;

use App\Livewire\Admin\TenantProfileManagement;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TenantProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    private function signInAdmin(Tenant $tenant): User
    {
        $this->setTenantContext($tenant->id);
        $role = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $user = User::create([
            'name' => 'Admin Profil',
            'email' => 'admin.profile.' . strtolower($tenant->code) . '@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $user->roles()->attach($role);
        $role->setRelation('permissions', collect([
            new Permission(['name' => 'academic.manage', 'label' => 'Kelola Akademik']),
        ]));
        $user->setRelation('roles', collect([$role]));
        $this->actingAs($user);
        return $user;
    }

    public function test_admin_updates_only_own_tenant_profile_and_audits_change(): void
    {
        Storage::fake('public');
        $permissionRowsBefore = Permission::query()->count();
        $permissionPivotsBefore = DB::table('permission_role')->count();
        $tenantA = Tenant::create(['name' => 'Madrasah Lama A', 'code' => 'PROFA']);
        $tenantB = Tenant::create(['name' => 'Madrasah Tetap B', 'code' => 'PROFB']);
        $this->signInAdmin($tenantA);

        Livewire::test(TenantProfileManagement::class)
            ->set('name', 'MIN 15 Jakarta Selatan')
            ->set('nsm', '121231740015')
            ->set('npsn', '20104876')
            ->set('address', 'Jl. Contoh Madrasah No. 15, Jakarta Selatan')
            ->set('phone', '02170000000')
            ->set('email', 'info@madrasah.test')
            ->set('logo', UploadedFile::fake()->image('logo.png', 400, 400))
            ->call('saveProfile')
            ->assertHasNoErrors()
            ->assertSee('Profil madrasah berhasil diperbarui.');

        $tenantA->refresh();
        $tenantB->refresh();
        $this->assertSame('MIN 15 Jakarta Selatan', $tenantA->name);
        $this->assertSame('121231740015', $tenantA->nsm);
        $this->assertSame('20104876', $tenantA->npsn);
        $this->assertSame('Madrasah Tetap B', $tenantB->name);
        $this->assertNotNull($tenantA->logo_path);
        Storage::disk('public')->assertExists($tenantA->logo_path);
        $this->assertTrue(AuditLog::query()->where('tenant_id', $tenantA->id)->where('entity_type', 'Tenant')->where('action', 'TENANT_PROFILE_UPDATED')->exists());
        $this->assertSame($permissionRowsBefore, Permission::query()->count());
        $this->assertSame($permissionPivotsBefore, DB::table('permission_role')->count());
    }

    public function test_profile_rejects_invalid_nsm_and_npsn(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Validasi', 'code' => 'VALPROF']);
        $this->signInAdmin($tenant);

        Livewire::test(TenantProfileManagement::class)
            ->set('name', 'Madrasah Validasi')
            ->set('nsm', '123')
            ->set('npsn', '1234')
            ->call('saveProfile')
            ->assertHasErrors(['nsm', 'npsn']);

        $this->assertSame('Madrasah Validasi', $tenant->fresh()->name);
    }

    public function test_non_admin_cannot_access_tenant_profile_editor(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Siswa', 'code' => 'PROFSIS']);
        $this->setTenantContext($tenant->id);
        $role = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $student = User::create([
            'name' => 'Siswa',
            'email' => 'siswa.profile@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $student->roles()->attach($role);
        $this->actingAs($student)->get('/admin/profile')->assertForbidden();
    }
}
