<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyRoleConsolidationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_admin_aliases_map_to_admin_and_only_valid_proktors_map_to_guru(): void
    {
        $tenant = Tenant::create(['name' => 'Legacy Role Madrasah', 'code' => 'LEGACYROLE']);
        app(TenantContext::class)->setTenantId($tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $operatorRole = Role::create(['name' => 'OPERATOR', 'label' => 'Operator legacy']);
        $legacyAdminRole = Role::create(['name' => 'ADMIN', 'label' => 'Admin legacy']);
        $proktorRole = Role::create(['name' => 'PROKTOR', 'label' => 'Proktor legacy']);

        $operator = User::create(['name' => 'Operator Legacy', 'email' => 'operator.legacy@example.test', 'password' => bcrypt('password')]);
        $operator->roles()->attach($operatorRole->id);

        $legacyAdmin = User::create(['name' => 'Admin Legacy', 'email' => 'admin.legacy@example.test', 'password' => bcrypt('password')]);
        $legacyAdmin->roles()->attach($legacyAdminRole->id);

        $validProktor = User::create(['name' => 'Valid Legacy Proktor', 'email' => 'valid.proktor@example.test', 'password' => bcrypt('password')]);
        $validProktor->roles()->attach($proktorRole->id);
        TeacherProfile::create([
            'user_id' => $validProktor->id,
            'tenant_id' => $tenant->id,
            'full_name' => 'Valid Legacy Proktor',
        ]);

        $invalidProktor = User::create(['name' => 'Review Legacy Proktor', 'email' => 'review.proktor@example.test', 'password' => bcrypt('password')]);
        $invalidProktor->roles()->attach($proktorRole->id);

        $migration = require database_path('migrations/2026_11_21_230001_consolidate_legacy_tenant_roles.php');
        $migration->up();

        $this->assertDatabaseMissing('roles', ['name' => 'ADMIN']);
        $this->assertDatabaseMissing('roles', ['name' => 'OPERATOR']);
        $this->assertDatabaseMissing('roles', ['name' => 'PROKTOR']);
        $this->assertDatabaseHas('roles', ['name' => 'LEGACY_PROKTOR_REVIEW']);

        $this->assertTrue($operator->fresh()->roles()->where('name', 'ADMIN_MADRASAH')->exists());
        $this->assertTrue($legacyAdmin->fresh()->roles()->where('name', 'ADMIN_MADRASAH')->exists());
        $this->assertTrue($validProktor->fresh()->roles()->where('name', 'GURU')->exists());
        $this->assertFalse($validProktor->fresh()->roles()->where('name', 'PROKTOR')->exists());
        $this->assertTrue($invalidProktor->fresh()->roles()->where('name', 'LEGACY_PROKTOR_REVIEW')->exists());

        $this->assertDatabaseHas('legacy_role_migration_reviews', [
            'user_id' => $invalidProktor->id,
            'legacy_role' => 'PROKTOR',
            'status' => 'PENDING',
        ]);

        // Migration fixture is deliberately limited to role and role-user rows.
        $this->assertSame(0, DB::table('permissions')->count());
        $this->assertSame(0, DB::table('permission_role')->count());
    }
}
