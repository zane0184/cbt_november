<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RetireKamadRoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class RetireKamadRoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(Tenant $tenant, string $name, string $legacyRole): User
    {
        $this->setTenantContext($tenant->id);
        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '.', $name)) . '@madrasah.test',
            'password' => bcrypt('password123'),
        ]);
        DB::table('users')->where('id', $user->id)->update(['role' => $legacyRole]);
        return $user->fresh();
    }

    public function test_kamad_role_is_archived_and_user_account_is_preserved_without_portal_access(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Kamad', 'code' => 'KAMAD1']);
        $kamadRole = Role::create(['name' => 'KAMAD', 'label' => 'Kepala Madrasah']);
        $permission = Permission::create(['name' => 'legacy.kamad', 'label' => 'Legacy Kamad']);
        $kamadRole->permissions()->attach($permission);

        $user = $this->makeUser($tenant, 'Kamad Lama', 'KAMAD');
        $user->roles()->attach($kamadRole);

        $result = app(RetireKamadRoleService::class)->retire();

        $this->assertSame(1, $result['archived_roles']);
        $this->assertSame(1, $result['retired_users']);
        $this->assertSame('UNASSIGNED', $user->fresh()->role);
        $this->assertSame(0, $user->fresh()->roles()->count());
        $this->assertSame(0, Role::where('name', 'KAMAD')->count());
        $this->assertSame(1, Role::where('name', 'ARCHIVED_KAMAD_' . $kamadRole->id)->count());
        $this->assertSame(0, DB::table('permission_role')->where('role_id', $kamadRole->id)->count());
        $this->assertDatabaseHas('kamad_role_retirement_records', ['record_type' => 'USER_ROLE_DETACH', 'user_id' => $user->id]);
    }

    public function test_kamad_role_is_removed_but_existing_admin_portal_role_is_preserved(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Admin Kamad', 'code' => 'KAMAD2']);
        $kamadRole = Role::create(['name' => 'KAMAD', 'label' => 'Kepala Madrasah']);
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $user = $this->makeUser($tenant, 'Admin Merangkap Kamad', 'KAMAD');
        $user->roles()->attach([$kamadRole->id, $adminRole->id]);

        app(RetireKamadRoleService::class)->retire();

        $this->assertSame('ADMIN_MADRASAH', $user->fresh()->role);
        $this->assertSame(['ADMIN_MADRASAH'], $user->fresh()->roles()->pluck('name')->all());
    }

    public function test_ambiguous_kamad_multi_role_account_fails_closed_without_partial_changes(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Role Konflik', 'code' => 'KAMAD3']);
        $kamadRole = Role::create(['name' => 'KAMAD', 'label' => 'Kepala Madrasah']);
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $user = $this->makeUser($tenant, 'Kamad Konflik', 'KAMAD');
        $user->roles()->attach([$kamadRole->id, $adminRole->id, $guruRole->id]);

        try {
            app(RetireKamadRoleService::class)->retire();
            $this->fail('Ambiguous role assignments must fail closed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ambiguous', strtolower($exception->getMessage()));
        }

        $this->assertSame(1, Role::where('name', 'KAMAD')->count());
        $this->assertSame(3, $user->fresh()->roles()->count());
        $this->assertSame(0, DB::table('kamad_role_retirement_records')->count());
    }

    public function test_rollback_restores_kamad_role_assignment_and_permission(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Rollback Kamad', 'code' => 'KAMAD4']);
        $kamadRole = Role::create(['name' => 'KAMAD', 'label' => 'Kepala Madrasah']);
        $permission = Permission::create(['name' => 'legacy.kamad.rollback', 'label' => 'Legacy Kamad']);
        $kamadRole->permissions()->attach($permission);
        $user = $this->makeUser($tenant, 'Kamad Rollback', 'KAMAD');
        $user->roles()->attach($kamadRole);

        $service = app(RetireKamadRoleService::class);
        $service->retire();
        $service->rollback();

        $this->assertSame('KAMAD', $user->fresh()->role);
        $this->assertTrue($user->fresh()->roles()->where('name', 'KAMAD')->exists());
        $this->assertTrue($kamadRole->permissions()->where('name', 'legacy.kamad.rollback')->exists());
    }
}
