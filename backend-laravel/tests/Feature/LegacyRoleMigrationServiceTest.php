<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LegacyRoleMigrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class LegacyRoleMigrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(Tenant $tenant, string $name, string $email, string $legacyRole): User
    {
        $this->setTenantContext($tenant->id);
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt('preserved-password'),
        ]);
        DB::table('users')->where('id', $user->id)->update(['role' => $legacyRole]);
        return $user->fresh();
    }

    public function test_legacy_admin_and_proktor_roles_map_without_permission_table_mutations(): void
    {
        $permissionCountBefore = Permission::query()->count();
        $permissionPivotCountBefore = DB::table('permission_role')->count();

        $tenant = Tenant::create(['name' => 'Madrasah Legacy', 'code' => 'LEGACY']);
        $adminCanonical = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruCanonical = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $operatorRole = Role::create(['name' => 'OPERATOR', 'label' => 'Operator']);
        $adminLegacyRole = Role::create(['name' => 'ADMIN', 'label' => 'Admin Legacy']);
        $proktorRole = Role::create(['name' => 'PROKTOR', 'label' => 'Proktor']);

        $operator = $this->createUser($tenant, 'Operator Lama', 'operator.legacy@test.local', 'OPERATOR');
        $operator->roles()->attach($operatorRole);
        $admin = $this->createUser($tenant, 'Admin Lama', 'admin.legacy@test.local', 'ADMIN');
        $admin->roles()->attach($adminLegacyRole);
        $proktor = $this->createUser($tenant, 'Guru Proktor Lama', 'proktor.legacy@test.local', 'PROKTOR');
        $proktor->roles()->attach($proktorRole);
        TeacherProfile::create([
            'user_id' => $proktor->id,
            'full_name' => 'Guru Proktor Lama',
            'nip' => '198601012020011099',
            'employee_identifier' => '198601012020011099',
        ]);

        $passwordBefore = $operator->password;
        $tenantBefore = $operator->tenant_id;

        $result = app(LegacyRoleMigrationService::class)->migrate();

        $this->assertSame(3, $result['archived_roles']);
        $this->assertSame(3, $result['migrated_users']);
        $this->assertFalse($result['permission_role_mutated']);

        foreach ([$operator, $admin] as $legacyAdmin) {
            $legacyAdmin->refresh();
            $this->assertSame('ADMIN_MADRASAH', $legacyAdmin->role);
            $this->assertTrue($legacyAdmin->roles()->where('name', 'ADMIN_MADRASAH')->exists());
            $this->assertSame(1, $legacyAdmin->roles()->count());
        }

        $proktor->refresh();
        $this->assertSame('GURU', $proktor->role);
        $this->assertTrue($proktor->roles()->where('name', 'GURU')->exists());
        $this->assertSame(1, $proktor->roles()->count());
        $this->assertSame($passwordBefore, $operator->fresh()->password);
        $this->assertSame($tenantBefore, $operator->fresh()->tenant_id);

        $this->assertSame(0, Role::query()->whereIn('name', ['ADMIN', 'OPERATOR', 'PROKTOR'])->count());
        $this->assertSame(3, DB::table('legacy_role_migration_records')->where('record_type', 'ROLE_ARCHIVE')->count());
        $this->assertSame(3, DB::table('legacy_role_migration_records')->where('record_type', 'USER_ROLE_MAPPING')->count());
        $this->assertSame($permissionCountBefore, Permission::query()->count());
        $this->assertSame($permissionPivotCountBefore, DB::table('permission_role')->count());
    }

    public function test_legacy_admin_with_conflicting_guru_role_fails_closed_without_partial_changes(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Konflik', 'code' => 'LEGCON']);
        $operatorRole = Role::create(['name' => 'OPERATOR', 'label' => 'Operator']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);

        $user = $this->createUser($tenant, 'Operator Konflik', 'operator.conflict@test.local', 'OPERATOR');
        $user->roles()->attach([$operatorRole->id, $guruRole->id]);

        try {
            app(LegacyRoleMigrationService::class)->migrate();
            $this->fail('Conflicting legacy role assignments must fail closed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('conflict', strtolower($exception->getMessage()));
        }

        $this->assertSame(1, Role::query()->where('name', 'OPERATOR')->count());
        $this->assertTrue($user->fresh()->roles()->where('name', 'OPERATOR')->exists());
        $this->assertTrue($user->fresh()->roles()->where('name', 'GURU')->exists());
        $this->assertSame(0, DB::table('legacy_role_migration_records')->count());
    }

    public function test_proktor_without_active_same_tenant_teacher_profile_fails_closed(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Proktor Invalid', 'code' => 'PROINV']);
        $proktorRole = Role::create(['name' => 'PROKTOR', 'label' => 'Proktor']);
        Role::create(['name' => 'GURU', 'label' => 'Guru']);

        $user = $this->createUser($tenant, 'Proktor Tanpa Profil', 'proktor.invalid@test.local', 'PROKTOR');
        $user->roles()->attach($proktorRole);

        try {
            app(LegacyRoleMigrationService::class)->migrate();
            $this->fail('Proktor without a valid TeacherProfile must fail closed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('TeacherProfile', $exception->getMessage());
        }

        $this->assertSame(1, Role::query()->where('name', 'PROKTOR')->count());
        $this->assertTrue($user->fresh()->roles()->where('name', 'PROKTOR')->exists());
        $this->assertSame(0, DB::table('legacy_role_migration_records')->count());
    }

    public function test_migration_rollback_restores_legacy_role_assignments_and_user_role_column(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Rollback', 'code' => 'LEGROLL']);
        $adminCanonical = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $operatorRole = Role::create(['name' => 'OPERATOR', 'label' => 'Operator Asli']);

        $user = $this->createUser($tenant, 'Operator Rollback', 'operator.rollback@test.local', 'OPERATOR');
        $user->roles()->attach($operatorRole);

        $service = app(LegacyRoleMigrationService::class);
        $service->migrate();

        $this->assertTrue($user->fresh()->roles()->where('name', 'ADMIN_MADRASAH')->exists());
        $service->rollback();

        $this->assertSame('OPERATOR', $user->fresh()->role);
        $this->assertTrue($user->fresh()->roles()->where('name', 'OPERATOR')->exists());
        $this->assertFalse($user->fresh()->roles()->where('name', 'ADMIN_MADRASAH')->exists());
        $this->assertSame('Operator Asli', Role::query()->where('name', 'OPERATOR')->value('label'));
        $this->assertSame(0, DB::table('legacy_role_migration_records')->count());
    }
}
