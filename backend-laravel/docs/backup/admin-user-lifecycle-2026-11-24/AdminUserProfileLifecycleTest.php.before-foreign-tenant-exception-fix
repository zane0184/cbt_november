<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\UserManagement;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserProfileLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Profile Test', 'code' => 'MPT01']);
        app(TenantContext::class)->setTenantId($this->tenant->id);
        TenantContext::getInstance()?->setTenantId($this->tenant->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $teacherRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        $permission = Permission::firstOrCreate(['name' => 'user.manage'], ['label' => 'Kelola akun']);
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Test',
            'username' => 'admin.test',
            'email' => 'admin@test.local',
            'password' => bcrypt('password-test'),
        ]);
        $this->admin->roles()->attach($adminRole);
    }

    private function makeTeacher(string $suffix = '01'): array
    {
        $role = Role::where('name', 'GURU')->firstOrFail();
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru ' . $suffix,
            'username' => 'guru' . $suffix,
            'email' => 'guru' . $suffix . '@test.local',
            'password' => bcrypt('password-test'),
        ]);
        $user->roles()->attach($role);
        $profile = TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'nip' => '1987000000' . $suffix,
            'nik' => '317400000000' . str_pad($suffix, 4, '0', STR_PAD_LEFT),
            'nuptk' => '1234567890' . $suffix,
            'employee_identifier' => '1987000000' . $suffix,
            'gender' => 'M',
            'phone' => '0812000000' . $suffix,
        ]);

        return [$user, $profile];
    }

    private function makeStudent(string $suffix = '01'): array
    {
        $role = Role::where('name', 'SISWA')->firstOrFail();
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa ' . $suffix,
            'username' => 'siswa' . $suffix,
            'email' => 'siswa' . $suffix . '@test.local',
            'password' => bcrypt('password-test'),
        ]);
        $user->roles()->attach($role);
        $profile = StudentProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'full_name' => $user->name,
            'nisn' => '00990000' . $suffix,
            'gender' => 'F',
            'birth_place' => 'Jakarta',
            'birth_date' => '2015-05-01',
            'parent_name' => 'Wali ' . $suffix,
            'phone' => '0813000000' . $suffix,
            'address' => 'Alamat lama',
        ]);

        return [$user, $profile];
    }

    public function test_admin_can_edit_teacher_profile_and_audit_contains_only_changed_field_names(): void
    {
        [$teacher, $profile] = $this->makeTeacher();
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->call('openEditModal', $teacher->id)
            ->assertSet('editingRole', 'GURU')
            ->set('editName', 'Guru Diperbarui')
            ->set('editUsername', 'guru.updated')
            ->set('editEmail', 'guru.updated@test.local')
            ->set('editNip', '198700009999')
            ->set('editNik', '3174000000009999')
            ->set('editNuptk', '123456789099')
            ->set('editGender', 'F')
            ->set('editPhone', '081299999999')
            ->call('updateUserProfile')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Diperbarui',
            'username' => 'guru.updated',
            'email' => 'guru.updated@test.local',
        ]);
        $this->assertDatabaseHas('teacher_profiles', [
            'id' => $profile->id,
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Guru Diperbarui',
            'nip' => '198700009999',
            'nik' => '3174000000009999',
            'nuptk' => '123456789099',
            'employee_identifier' => '198700009999',
            'gender' => 'F',
            'phone' => '081299999999',
        ]);

        $audit = AuditLog::query()->where('action', 'UPDATE_LOCAL_USER_PROFILE')
            ->where('entity_id', $teacher->id)->firstOrFail();
        $this->assertContains('nip', $audit->new_values['changed_fields']);
        $this->assertContains('name', $audit->new_values['changed_fields']);
        $this->assertArrayNotHasKey('nik', $audit->new_values);
        $this->assertArrayNotHasKey('nip', $audit->new_values);
        $this->assertArrayNotHasKey('phone', $audit->new_values);
    }

    public function test_admin_can_edit_student_profile_and_preserve_academic_identity_fields(): void
    {
        [$student, $profile] = $this->makeStudent();
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->call('openEditModal', $student->id)
            ->assertSet('editingRole', 'SISWA')
            ->set('editName', 'Siswa Diperbarui')
            ->set('editUsername', 'siswa.updated')
            ->set('editEmail', '')
            ->set('editNisn', '0099887766')
            ->set('editGender', 'M')
            ->set('editPhone', '081388887766')
            ->set('editBirthPlace', 'Depok')
            ->set('editBirthDate', '2015-06-02')
            ->set('editParentName', 'Wali Baru')
            ->set('editAddress', 'Alamat baru')
            ->call('updateUserProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('student_profiles', [
            'id' => $profile->id,
            'tenant_id' => $this->tenant->id,
            'full_name' => 'Siswa Diperbarui',
            'nisn' => '0099887766',
            'gender' => 'M',
            'birth_place' => 'Depok',
            'birth_date' => '2015-06-02',
            'parent_name' => 'Wali Baru',
            'phone' => '081388887766',
            'address' => 'Alamat baru',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'tenant_id' => $this->tenant->id,
            'email' => null,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'action' => 'UPDATE_LOCAL_USER_PROFILE',
            'entity_type' => 'User',
            'entity_id' => $student->id,
        ]);
    }

    public function test_admin_cannot_open_profile_from_another_madrasah(): void
    {
        $otherTenant = Tenant::create(['name' => 'Madrasah Other', 'code' => 'MOT01']);
        $previous = app(TenantContext::class)->getTenantId();
        app(TenantContext::class)->setTenantId($otherTenant->id);
        TenantContext::getInstance()?->setTenantId($otherTenant->id);

        $role = Role::where('name', 'GURU')->firstOrFail();
        $foreign = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Guru Asing',
            'username' => 'guru.asing',
            'email' => 'foreign@test.local',
            'password' => bcrypt('password-test'),
        ]);
        $foreign->roles()->attach($role);
        TeacherProfile::create([
            'tenant_id' => $otherTenant->id,
            'user_id' => $foreign->id,
            'full_name' => $foreign->name,
            'nip' => '198700009876',
        ]);

        app(TenantContext::class)->setTenantId($previous);
        TenantContext::getInstance()?->setTenantId($this->tenant->id);
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->call('openEditModal', $foreign->id)
            ->assertStatus(404);
    }

    public function test_user_management_rejects_non_admin_and_multi_role_admin(): void
    {
        [$teacher] = $this->makeTeacher('02');
        $this->actingAs($teacher);
        Livewire::test(UserManagement::class)->assertForbidden();

        $adminRole = Role::where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $teacherRole = Role::where('name', 'GURU')->firstOrFail();
        $this->admin->roles()->attach($teacherRole);
        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->assertForbidden();
    }

    public function test_duplicate_tenant_username_and_teacher_identifiers_are_rejected_without_audit(): void
    {
        [$target, $profile] = $this->makeTeacher('03');
        [$other] = $this->makeTeacher('04');
        $this->actingAs($this->admin);

        Livewire::test(UserManagement::class)
            ->call('openEditModal', $target->id)
            ->set('editUsername', $other->username)
            ->set('editNip', '198700000004')
            ->call('updateUserProfile')
            ->assertHasErrors(['editUsername', 'editNip']);

        $this->assertDatabaseMissing('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'action' => 'UPDATE_LOCAL_USER_PROFILE',
            'entity_id' => $target->id,
        ]);
        $this->assertSame($profile->nip, $profile->fresh()->nip);
    }
}
