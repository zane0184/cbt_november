<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
use App\Livewire\Admin\AcademicManagement;
use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Admin\StudentRombelManagement;
use App\Livewire\Admin\TenantProfileManagement;
use App\Livewire\Admin\ProctorAssignmentManagement;
use App\Livewire\Admin\QuestionBank;
use App\Livewire\Admin\ExamManagement;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserManagementScopeTest extends TestCase
{
    use RefreshDatabase;

    private function signInAdmin(Tenant $tenant, bool $staleGuruRole = false): User
    {
        $this->setTenantContext($tenant->id);
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $admin = User::create([
            'name' => 'Admin Pengguna',
            'email' => 'admin.users.' . strtolower($tenant->code) . '@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        $admin->roles()->attach($adminRole);
        $roles = collect([$adminRole]);

        if ($staleGuruRole) {
            $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
            $admin->roles()->attach($guruRole);
            $roles->push($guruRole);
        }

        $adminRole->setRelation('permissions', collect([
            new Permission(['name' => 'user.manage', 'label' => 'Kelola Pengguna']),
            new Permission(['name' => 'academic.manage', 'label' => 'Kelola Akademik']),
        ]));
        $admin->setRelation('roles', $roles);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_admin_user_list_contains_only_single_role_guru_and_siswa_from_current_tenant(): void
    {
        $permissionRowsBefore = Permission::query()->count();
        $permissionPivotsBefore = DB::table('permission_role')->count();
        $tenantA = Tenant::create(['name' => 'Madrasah User A', 'code' => 'USERA']);
        $tenantB = Tenant::create(['name' => 'Madrasah User B', 'code' => 'USERB']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $siswaRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $this->setTenantContext($tenantA->id);
        $guru = User::create(['name' => 'Guru A', 'email' => 'guru.a.users@test.local', 'password' => bcrypt('password123')]);
        $guru->roles()->attach($guruRole);
        TeacherProfile::create(['user_id' => $guru->id, 'full_name' => 'Guru A', 'nip' => '198601012020011001', 'employee_identifier' => '198601012020011001']);

        $student = User::create(['name' => 'Siswa A', 'email' => 'siswa.a.users@test.local', 'password' => bcrypt('password123')]);
        $student->roles()->attach($siswaRole);
        StudentProfile::create(['user_id' => $student->id, 'full_name' => 'Siswa A', 'nisn' => '0011223344']);

        $this->setTenantContext($tenantB->id);
        $guruB = User::create(['name' => 'Guru B', 'email' => 'guru.b.users@test.local', 'password' => bcrypt('password123')]);
        $guruB->roles()->attach($guruRole);
        TeacherProfile::create(['user_id' => $guruB->id, 'full_name' => 'Guru B', 'nip' => '198601012020011002', 'employee_identifier' => '198601012020011002']);

        $this->setTenantContext($tenantA->id);
        $this->signInAdmin($tenantA);

        Livewire::test(UserManagement::class)
            ->assertSee('Guru A')
            ->assertSee('Siswa A')
            ->assertDontSee('Guru B')
            ->assertDontSee('Admin Pengguna')
            ->assertDontSee('Operator')
            ->assertDontSee('Kepala Madrasah');

        $this->assertSame($permissionRowsBefore, Permission::query()->count());
        $this->assertSame($permissionPivotsBefore, DB::table('permission_role')->count());
    }

    public function test_admin_with_stale_guru_role_is_denied_user_management(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Admin Role Conflict', 'code' => 'ADMCON']);
        $this->signInAdmin($tenant, true);

        Livewire::test(UserManagement::class)->assertForbidden();
    }

    public function test_admin_with_stale_guru_role_is_denied_all_admin_management_surfaces(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Admin Role Conflict', 'code' => 'ADMALL']);
        $this->signInAdmin($tenant, true);

        Livewire::test(AdminDashboard::class)->assertForbidden();
        Livewire::test(UserManagement::class)->assertForbidden();
        Livewire::test(AcademicManagement::class)->assertForbidden();
        Livewire::test(StudentRombelManagement::class)->assertForbidden();
        Livewire::test(TenantProfileManagement::class)->assertForbidden();
        Livewire::test(ProctorAssignmentManagement::class)->assertForbidden();
        Livewire::test(QuestionBank::class)->assertForbidden();
        Livewire::test(ExamManagement::class)->assertForbidden();
    }

    public function test_dashboard_and_rombel_picker_exclude_multi_role_student_and_teacher_accounts(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Single Role Counts', 'code' => 'SINGLE']);
        $this->setTenantContext($tenant->id);
        $siswaRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);

        $multi = User::create(['name' => 'Akun Multi Role', 'email' => 'multi.role@madrasah.test', 'password' => bcrypt('password123')]);
        $multi->roles()->attach([$siswaRole->id, $guruRole->id]);
        $multiStudent = StudentProfile::create(['user_id' => $multi->id, 'full_name' => 'Profil Siswa Multi', 'nisn' => '0099001100']);
        TeacherProfile::create(['user_id' => $multi->id, 'full_name' => 'Profil Guru Multi', 'nip' => '198801012020011001', 'employee_identifier' => '198801012020011001']);

        $deletedStudent = User::create(['name' => 'Siswa Dihapus', 'email' => 'siswa.dihapus@madrasah.test', 'password' => bcrypt('password123')]);
        $deletedStudent->roles()->attach($siswaRole->id);
        StudentProfile::create(['user_id' => $deletedStudent->id, 'full_name' => 'Siswa Dihapus', 'nisn' => '0099001102']);
        DB::table('users')->where('id', $deletedStudent->id)->update(['deleted_at' => now()]);

        $student = User::create(['name' => 'Siswa Tunggal', 'email' => 'siswa.tunggal@madrasah.test', 'password' => bcrypt('password123')]);
        $student->roles()->attach($siswaRole->id);
        $studentProfile = StudentProfile::create(['user_id' => $student->id, 'full_name' => 'Siswa Tunggal', 'nisn' => '0099001101']);

        $teacher = User::create(['name' => 'Guru Tunggal', 'email' => 'guru.tunggal@madrasah.test', 'password' => bcrypt('password123')]);
        $teacher->roles()->attach($guruRole->id);
        TeacherProfile::create(['user_id' => $teacher->id, 'full_name' => 'Guru Tunggal', 'nip' => '198801012020011002', 'employee_identifier' => '198801012020011002']);

        $this->signInAdmin($tenant);
        Livewire::test(AdminDashboard::class)
            ->assertViewHas('totalStudents', 1)
            ->assertViewHas('totalTeachers', 1);

        Livewire::test(StudentRombelManagement::class)
            ->assertViewHas('availableStudents', fn ($students) => $students->pluck('id')->all() === [$studentProfile->id])
            ->assertDontSee('Profil Siswa Multi');

        Livewire::test(UserManagement::class)->assertDontSee('Siswa Dihapus');

        $this->assertSame(2, $multi->fresh()->roles()->count());
        $this->assertSame(1, StudentProfile::query()->whereKey($multiStudent->id)->count());
    }

    public function test_admin_can_create_guru_and_student_with_profiles_and_audit_log(): void
    {
        $permissionRowsBefore = Permission::query()->count();
        $permissionPivotsBefore = DB::table('permission_role')->count();
        $tenant = Tenant::create(['name' => 'Madrasah Buat User', 'code' => 'NEWUSR']);
        $this->signInAdmin($tenant);
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        Livewire::test(UserManagement::class)
            ->set('name', 'Guru Baru')
            ->set('selectedRole', 'GURU')
            ->set('employmentType', 'ASN')
            ->set('nip', '198701012021011001')
            ->set('password', 'StrongPassword123!')
            ->call('createUser')
            ->assertHasNoErrors();

        $guru = User::query()->where('name', 'Guru Baru')->firstOrFail();
        $this->assertTrue($guru->roles()->where('name', 'GURU')->exists());
        $this->assertSame('198701012021011001', $guru->teacherProfile->nip);

        Livewire::test(UserManagement::class)
            ->set('name', 'Siswa Baru')
            ->set('selectedRole', 'SISWA')
            ->set('nisn', '0099887766')
            ->set('password', 'StrongPassword123!')
            ->call('createUser')
            ->assertHasNoErrors();

        $student = User::query()->where('name', 'Siswa Baru')->firstOrFail();
        $this->assertTrue($student->roles()->where('name', 'SISWA')->exists());
        $this->assertSame('0099887766', $student->studentProfile->nisn);
        $this->assertSame(2, AuditLog::query()->where('tenant_id', $tenant->id)->where('action', 'USER_CREATED')->count());

        $this->assertSame($permissionRowsBefore, Permission::query()->count());
        $this->assertSame($permissionPivotsBefore, DB::table('permission_role')->count());
    }

    public function test_admin_cannot_assign_admin_or_legacy_roles_through_user_management(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Role', 'code' => 'ROLEUSR']);
        $this->signInAdmin($tenant);
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        Livewire::test(UserManagement::class)
            ->set('name', 'Akun Terlarang')
            ->set('selectedRole', 'OPERATOR')
            ->set('password', 'StrongPassword123!')
            ->call('createUser')
            ->assertHasErrors(['selectedRole']);

        $this->assertSame(0, User::query()->where('name', 'Akun Terlarang')->count());
    }

    public function test_admin_cannot_create_guru_without_employment_type_and_login_identity(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Guru Tanpa Identitas Opsional', 'code' => 'GURUNO']);
        $this->signInAdmin($tenant);
        Role::create(['name' => 'GURU', 'label' => 'Guru']);
        Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        Livewire::test(UserManagement::class)
            ->set('name', 'Guru Tanpa Identitas')
            ->set('username', 'guru.tanpa.identitas')
            ->set('selectedRole', 'GURU')
            ->set('password', 'StrongPassword123!')
            ->call('createUser')
            ->assertHasErrors(['employmentType']);

        $this->assertSame(0, User::query()->where('name', 'Guru Tanpa Identitas')->count());
    }


    public function test_admin_can_edit_guru_identity_and_reset_password_with_audit(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Edit Guru', 'code' => 'EDITGU']);
        $this->setTenantContext($tenant->id);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $this->signInAdmin($tenant);

        $guru = User::create([
            'name' => 'Guru Sebelum Edit',
            'username' => 'guru.sebelum',
            'email' => 'guru.sebelum@madrasah.test',
            'password' => bcrypt('InitialPassword123!'),
        ]);
        $guru->roles()->sync([$guruRole->id]);
        $profile = TeacherProfile::create([
            'user_id' => $guru->id,
            'full_name' => 'Guru Sebelum Edit',
            'nip' => null,
            'nik' => null,
            'employee_identifier' => null,
        ]);

        Livewire::test(UserManagement::class)
            ->call('openEditModal', $guru->id)
            ->assertSet('showEditModal', true)
            ->set('editName', 'Guru Sesudah Edit')
            ->set('editUsername', 'guru.sesudah')
            ->set('editEmail', 'guru.sesudah@madrasah.test')
            ->set('editEmploymentType', 'ASN')
            ->set('editNip', '198701012021011001')
            ->set('editNik', '')
            ->call('updateUser')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $guru->refresh();
        $profile->refresh();
        $this->assertSame('Guru Sesudah Edit', $guru->name);
        $this->assertSame('guru.sesudah', $guru->username);
        $this->assertSame('198701012021011001', $profile->nip);
        $this->assertSame('198701012021011001', $profile->employee_identifier);
        $this->assertTrue(AuditLog::query()->where('tenant_id', $tenant->id)->where('action', 'USER_UPDATED')->where('entity_id', $guru->id)->exists());

        Livewire::test(UserManagement::class)
            ->call('openResetPasswordModal', $guru->id)
            ->assertSet('showResetPasswordModal', true)
            ->set('resetPassword', 'NewStrongPassword123!')
            ->call('resetUserPassword')
            ->assertHasNoErrors()
            ->assertSet('showResetPasswordModal', false);

        $guru->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewStrongPassword123!', $guru->password));
        $this->assertTrue(AuditLog::query()->where('tenant_id', $tenant->id)->where('action', 'USER_PASSWORD_RESET')->where('entity_id', $guru->id)->exists());
        $resetLog = AuditLog::query()->where('tenant_id', $tenant->id)->where('action', 'USER_PASSWORD_RESET')->where('entity_id', $guru->id)->firstOrFail();
        $this->assertStringNotContainsString('NewStrongPassword123!', json_encode($resetLog->new_values));
    }

    public function test_admin_cannot_edit_or_reset_password_for_other_tenant_user(): void
    {
        $tenantA = Tenant::create(['name' => 'Madrasah A Edit', 'code' => 'EDTA']);
        $tenantB = Tenant::create(['name' => 'Madrasah B Edit', 'code' => 'EDTB']);
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);

        $this->setTenantContext($tenantB->id);
        $foreignGuru = User::create([
            'name' => 'Guru Tenant B',
            'username' => 'guru.tenantb',
            'email' => 'guru.tenantb@madrasah.test',
            'password' => bcrypt('InitialPassword123!'),
        ]);
        $foreignGuru->roles()->sync([$guruRole->id]);
        TeacherProfile::create(['user_id' => $foreignGuru->id, 'full_name' => 'Guru Tenant B']);

        $this->setTenantContext($tenantA->id);
        $this->signInAdmin($tenantA);

        Livewire::test(UserManagement::class)->call('openEditModal', $foreignGuru->id)->assertNotFound();
        Livewire::test(UserManagement::class)->call('openResetPasswordModal', $foreignGuru->id)->assertNotFound();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('InitialPassword123!', $foreignGuru->fresh()->password));
    }


    public function test_admin_can_deactivate_and_reactivate_guru_with_reason_audit_and_token_revocation(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Lifecycle Akun', 'code' => 'LIFECY']);
        $this->setTenantContext($tenant->id);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $this->signInAdmin($tenant);

        $guru = User::create([
            'name' => 'Guru Lifecycle',
            'username' => 'guru.lifecycle',
            'password' => bcrypt('InitialPassword123!'),
        ]);
        $guru->roles()->sync([$guruRole->id]);
        TeacherProfile::create(['user_id' => $guru->id, 'full_name' => 'Guru Lifecycle']);
        $plainTextToken = $guru->createToken('lifecycle-test')->plainTextToken;
        $tokenId = explode('|', $plainTextToken)[0];

        Livewire::test(UserManagement::class)
            ->call('openAccountStatusModal', $guru->id, false)
            ->assertSet('showAccountStatusModal', true)
            ->set('statusReason', 'Akun dinonaktifkan sementara untuk pengujian.')
            ->call('updateAccountStatus')
            ->assertHasNoErrors()
            ->assertSet('showAccountStatusModal', false);

        $guru->refresh();
        $this->assertFalse($guru->is_active);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenId]);
        $this->assertTrue(AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('action', 'USER_DEACTIVATED')
            ->where('entity_id', $guru->id)
            ->exists());

        Livewire::test(UserManagement::class)
            ->call('openAccountStatusModal', $guru->id, true)
            ->set('statusReason', 'Akun diaktifkan kembali setelah verifikasi.')
            ->call('updateAccountStatus')
            ->assertHasNoErrors();

        $this->assertTrue($guru->fresh()->is_active);
        $this->assertTrue(AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('action', 'USER_REACTIVATED')
            ->where('entity_id', $guru->id)
            ->exists());
        $this->assertSame(0, $guru->fresh()->tokens()->count());
    }

    public function test_admin_cannot_change_account_status_for_another_tenant_user(): void
    {
        $tenantA = Tenant::create(['name' => 'Madrasah Lifecycle A', 'code' => 'LIFA']);
        $tenantB = Tenant::create(['name' => 'Madrasah Lifecycle B', 'code' => 'LIFB']);
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);

        $this->setTenantContext($tenantB->id);
        $foreignGuru = User::create([
            'name' => 'Guru Foreign Lifecycle',
            'username' => 'guru.foreign.lifecycle',
            'password' => bcrypt('InitialPassword123!'),
        ]);
        $foreignGuru->roles()->sync([$guruRole->id]);
        TeacherProfile::create(['user_id' => $foreignGuru->id, 'full_name' => 'Guru Foreign Lifecycle']);

        $this->setTenantContext($tenantA->id);
        $this->signInAdmin($tenantA);

        Livewire::test(UserManagement::class)
            ->call('openAccountStatusModal', $foreignGuru->id, false)
            ->assertNotFound();

        $this->assertTrue($foreignGuru->fresh()->is_active);
    }

}
