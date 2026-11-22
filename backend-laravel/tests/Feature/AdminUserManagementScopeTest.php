<?php

namespace Tests\Feature;

use App\Livewire\Admin\UserManagement;
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
        $this->signInAdmin($tenantA, true);

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


    public function test_admin_can_edit_guru_profile_and_audit_old_and_new_values_without_password(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Edit Guru', 'code' => 'EDITGURU']);
        $this->signInAdmin($tenant);
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $guru = User::create([
            'name' => 'Guru Lama',
            'username' => 'guru.lama',
            'email' => 'guru.lama.edit@example.test',
            'password' => bcrypt('StrongPassword123!'),
        ]);
        $guru->roles()->attach($guruRole);
        $profile = TeacherProfile::create([
            'user_id' => $guru->id,
            'full_name' => 'Guru Lama',
            'nip' => '198701012021011101',
            'nik' => '3171000000000001',
            'employee_identifier' => '198701012021011101',
        ]);

        Livewire::test(UserManagement::class)
            ->call('openEditModal', $guru->id)
            ->set('editName', 'Guru Diperbarui')
            ->set('editUsername', 'guru.baru')
            ->set('editEmail', 'guru.baru@example.test')
            ->set('editNip', '198701012021011102')
            ->set('editNik', '3171000000000002')
            ->call('updateUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $guru->id,
            'name' => 'Guru Diperbarui',
            'username' => 'guru.baru',
            'email' => 'guru.baru@example.test',
        ]);
        $this->assertDatabaseHas('teacher_profiles', [
            'id' => $profile->id,
            'full_name' => 'Guru Diperbarui',
            'nip' => '198701012021011102',
            'nik' => '3171000000000002',
        ]);

        $log = AuditLog::query()->where('tenant_id', $tenant->id)->where('action', 'USER_UPDATED')->firstOrFail();
        $this->assertSame('Guru Lama', $log->old_values['name']);
        $this->assertSame('Guru Diperbarui', $log->new_values['name']);
        $this->assertArrayNotHasKey('password', $log->old_values);
        $this->assertArrayNotHasKey('password', $log->new_values);
    }

    public function test_admin_can_edit_student_nisn_without_changing_role_or_profile_identity(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Edit Siswa', 'code' => 'EDITSISWA']);
        $this->signInAdmin($tenant);
        $siswaRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        $student = User::create([
            'name' => 'Siswa Lama',
            'username' => 'siswa.lama',
            'email' => null,
            'password' => bcrypt('StrongPassword123!'),
        ]);
        $student->roles()->attach($siswaRole);
        $profile = StudentProfile::create([
            'user_id' => $student->id,
            'full_name' => 'Siswa Lama',
            'nisn' => '0099887766',
        ]);

        Livewire::test(UserManagement::class)
            ->call('openEditModal', $student->id)
            ->set('editName', 'Siswa Diperbarui')
            ->set('editUsername', 'siswa.baru')
            ->set('editEmail', '')
            ->set('editNisn', '0099887767')
            ->call('updateUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $student->id, 'name' => 'Siswa Diperbarui', 'username' => 'siswa.baru']);
        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'full_name' => 'Siswa Diperbarui', 'nisn' => '0099887767']);
        $this->assertTrue($student->fresh()->hasRole('SISWA'));
    }

    public function test_deactivation_revokes_tokens_and_preserves_profile_and_audit_history_then_restore_reuses_same_ids(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Lifecycle', 'code' => 'LIFECYCLE']);
        $admin = $this->signInAdmin($tenant);
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $guru = User::create([
            'name' => 'Guru Lifecycle',
            'username' => 'guru.lifecycle',
            'email' => 'guru.lifecycle@example.test',
            'password' => bcrypt('StrongPassword123!'),
        ]);
        $guru->roles()->attach($guruRole);
        $profile = TeacherProfile::create([
            'user_id' => $guru->id,
            'full_name' => 'Guru Lifecycle',
            'nip' => '198701012021011103',
            'employee_identifier' => '198701012021011103',
        ]);
        $token = $guru->createToken('lifecycle-test')->plainTextToken;

        Livewire::test(UserManagement::class)
            ->call('deactivateUser', $guru->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('users', ['id' => $guru->id]);
        $this->assertSame(2, (int) User::withTrashed()->findOrFail($guru->id)->auth_version);
        $this->assertNull(User::query()->find($guru->id));
        $this->assertNotNull(User::withTrashed()->find($guru->id));
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $guru->id]);
        $this->assertDatabaseHas('teacher_profiles', ['id' => $profile->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'entity_id' => $guru->id, 'action' => 'USER_DEACTIVATED']);

        $log = AuditLog::query()->where('tenant_id', $tenant->id)->where('entity_id', $guru->id)->where('action', 'USER_DEACTIVATED')->firstOrFail();
        $this->assertSame($admin->id, $log->user?->id);
        $this->assertNotSame('', $token);

        Livewire::test(UserManagement::class)
            ->set('statusFilter', 'INACTIVE')
            ->call('restoreUser', $guru->id)
            ->assertHasNoErrors();

        $this->assertNotNull(User::query()->find($guru->id));
        $this->assertSame(3, (int) User::query()->findOrFail($guru->id)->auth_version);
        $this->assertDatabaseHas('teacher_profiles', ['id' => $profile->id, 'user_id' => $guru->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'entity_id' => $guru->id, 'action' => 'USER_REACTIVATED']);
        $this->assertSame($profile->id, $guru->teacherProfile()->firstOrFail()->id);
    }

    public function test_deactivated_profile_identity_remains_reserved_and_cannot_be_claimed_by_new_account(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Identity Reserve', 'code' => 'IDRESERVE']);
        $this->signInAdmin($tenant);
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $guru = User::create([
            'name' => 'Guru Lama Reserve',
            'username' => 'guru.reserve',
            'password' => bcrypt('StrongPassword123!'),
        ]);
        $guru->roles()->attach($guruRole);
        TeacherProfile::create([
            'user_id' => $guru->id,
            'full_name' => 'Guru Lama Reserve',
            'nip' => '198701012021011104',
            'employee_identifier' => '198701012021011104',
        ]);

        Livewire::test(UserManagement::class)->call('deactivateUser', $guru->id)->assertHasNoErrors();

        Livewire::test(UserManagement::class)
            ->set('name', 'Guru Pengganti')
            ->set('selectedRole', 'GURU')
            ->set('nip', '198701012021011104')
            ->set('password', 'StrongPassword123!')
            ->call('createUser')
            ->assertHasErrors(['nip']);

        $this->assertDatabaseCount('teacher_profiles', 1);
    }

    public function test_soft_deleted_user_is_hidden_from_default_queries_but_audit_relation_keeps_actor(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Soft Delete', 'code' => 'SOFTDEL']);
        $this->signInAdmin($tenant);
        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $guru = User::create([
            'name' => 'Guru Soft Deleted',
            'username' => 'guru.soft.deleted',
            'password' => bcrypt('StrongPassword123!'),
        ]);
        $guru->roles()->attach($guruRole);

        $log = AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $guru->id,
            'action' => 'HISTORICAL_ACTOR_TEST',
            'entity_type' => 'User',
            'entity_id' => $guru->id,
            'old_values' => null,
            'new_values' => ['recorded' => true],
        ]);
        $guru->delete();

        $this->assertNull(User::query()->find($guru->id));
        $this->assertNotNull(User::withTrashed()->find($guru->id));
        $this->assertSame($guru->id, $log->fresh()->user?->id);
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
}
