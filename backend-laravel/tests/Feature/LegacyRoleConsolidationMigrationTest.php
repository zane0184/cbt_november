<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyRoleConsolidationMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_roles_and_user_assignments_are_preserved_but_grants_and_sessions_are_revoked(): void
    {
        $tenant = Tenant::create(['name' => 'Legacy Role Test', 'code' => 'LRT01']);
        $legacyRole = Role::create(['name' => 'OPERATOR', 'label' => 'Legacy Operator']);
        $permission = Permission::create(['name' => 'legacy.operator.manage', 'label' => 'Legacy operator permission']);
        $legacyRole->permissions()->attach($permission->id);
        app(TenantContext::class)->setTenantId($tenant->id);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Operator Lama',
            'username' => 'operator.legacy.test',
            'email' => 'operator.legacy.test@example.test',
            'password' => bcrypt('password'),
        ]);
        $user->roles()->attach($legacyRole->id);
        DB::table('users')->where('id', $user->id)->update(['remember_token' => 'legacy-remember-token']);

        $sessionId = 'legacy-session-' . Str::random(12);
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->insert([
                'id' => $sessionId,
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => 'test-payload',
                'last_activity' => now()->timestamp,
            ]);
        }

        if (Schema::hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')->insert([
                'tokenable_type' => User::class,
                'tokenable_id' => $user->id,
                'name' => 'legacy-token',
                'token' => hash('sha256', Str::random(64)),
                'abilities' => '["*"]',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $migration = require database_path('migrations/2026_11_23_130001_consolidate_legacy_local_roles.php');
        $migration->up();

        $this->assertDatabaseHas('roles', ['id' => $legacyRole->id, 'name' => 'OPERATOR']);
        $this->assertDatabaseHas('role_user', ['role_id' => $legacyRole->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('permission_role', ['role_id' => $legacyRole->id, 'permission_id' => $permission->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'remember_token' => null]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'action' => 'LEGACY_ROLE_ACCESS_RETIRED',
            'entity_type' => 'User',
            'entity_id' => $user->id,
        ]);

        if (Schema::hasTable('sessions')) {
            $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
        }
        if (Schema::hasTable('personal_access_tokens')) {
            $this->assertDatabaseMissing('personal_access_tokens', [
                'tokenable_id' => $user->id,
                'name' => 'legacy-token',
            ]);
        }

        $this->assertDatabaseMissing('roles', ['name' => 'ADMIN_MADRASAH']);
    }
}
