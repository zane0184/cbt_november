<?php

namespace Tests\Feature;

use App\Livewire\Admin\TenantProfileManagement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TenantProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Lama', 'code' => 'PROFILE-A']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $role = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $permission = Permission::firstOrCreate(['name' => 'tenant.manage', 'label' => 'Kelola Data Madrasah']);
        $role->permissions()->syncWithoutDetaching([$permission->id]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Profil',
            'email' => 'admin.profile@example.test',
            'password' => 'password-admin-profile',
        ]);
        $this->admin->roles()->sync([$role->id]);
        $this->actingAs($this->admin);
    }

    public function test_admin_can_update_madrasah_profile_and_audit_changed_field_names_only(): void
    {
        Livewire::test(TenantProfileManagement::class)
            ->set('name', 'Madrasah Ibtidaiyah Negeri Contoh')
            ->set('shortName', 'MIN Contoh')
            ->set('nsm', '111122223333')
            ->set('npsn', '12345678')
            ->set('level', 'MI')
            ->set('ownershipStatus', 'NEGERI')
            ->set('address', 'Jl. Pendidikan No. 10')
            ->set('village', 'Kelurahan Contoh')
            ->set('district', 'Kecamatan Contoh')
            ->set('city', 'Kota Contoh')
            ->set('province', 'DKI Jakarta')
            ->set('postalCode', '12345')
            ->set('phone', '0215550101')
            ->set('email', 'info@madrasah.example')
            ->set('website', 'https://madrasah.example')
            ->set('principalName', 'Kepala Madrasah Contoh')
            ->set('additionalInfo', 'Informasi profil')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', [
            'id' => $this->tenant->id,
            'name' => 'Madrasah Ibtidaiyah Negeri Contoh',
            'short_name' => 'MIN Contoh',
            'nsm' => '111122223333',
            'npsn' => '12345678',
            'level' => 'MI',
            'ownership_status' => 'NEGERI',
            'postal_code' => '12345',
            'principal_name' => 'Kepala Madrasah Contoh',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'TENANT_PROFILE_UPDATED',
            'entity_id' => $this->tenant->id,
        ]);

        $audit = \App\Models\AuditLog::where('action', 'TENANT_PROFILE_UPDATED')->where('entity_id', $this->tenant->id)->firstOrFail();
        $this->assertStringNotContainsString('111122223333', json_encode($audit->new_values));
        $this->assertStringNotContainsString('Kepala Madrasah Contoh', json_encode($audit->new_values));
    }

    public function test_profile_validation_rejects_invalid_identifiers_and_ownership_status(): void
    {
        Livewire::test(TenantProfileManagement::class)
            ->set('name', 'Madrasah Contoh')
            ->set('nsm', '123')
            ->set('npsn', '1234')
            ->set('level', 'MI')
            ->set('ownershipStatus', 'CABANG')
            ->set('address', 'Alamat')
            ->call('save')
            ->assertHasErrors(['nsm' => 'digits', 'npsn' => 'digits', 'ownershipStatus' => 'in']);
    }

    public function test_non_admin_cannot_manage_madrasah_profile(): void
    {
        $guruRole = Role::firstOrCreate(['name' => 'GURU', 'label' => 'Guru']);
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Profil',
            'email' => 'guru.profile@example.test',
            'password' => 'password-guru-profile',
        ]);
        $guru->roles()->sync([$guruRole->id]);

        $this->actingAs($guru);
        Livewire::test(TenantProfileManagement::class)->assertForbidden();
    }

    public function test_profile_updates_are_bound_to_the_authenticated_installation_tenant(): void
    {
        $otherTenant = Tenant::create(['name' => 'Madrasah Lain', 'code' => 'PROFILE-B']);

        Livewire::test(TenantProfileManagement::class)
            ->set('name', 'Nama Baru Tenant A')
            ->set('level', 'MI')
            ->set('ownershipStatus', 'SWASTA')
            ->set('address', 'Alamat A')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id, 'name' => 'Nama Baru Tenant A']);
        $this->assertDatabaseHas('tenants', ['id' => $otherTenant->id, 'name' => 'Madrasah Lain']);
    }
}
