<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Convert Operator assignments to Admin Madrasah and retire other legacy
     * local identities. User rows and audit history are intentionally preserved.
     */
    public function up(): void
    {
        foreach (['roles', 'role_user', 'permission_role', 'users', 'audit_logs'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new \RuntimeException("Role consolidation aborted: required table [{$table}] is missing.");
            }
        }

        DB::transaction(function (): void {
            $adminRole = DB::table('roles')->where('name', 'ADMIN_MADRASAH')->lockForUpdate()->first();
            if (!$adminRole) {
                throw new \RuntimeException('Role consolidation aborted: ADMIN_MADRASAH is not present.');
            }

            $legacyNames = ['OPERATOR', 'KAMAD', 'PROKTOR', 'SUPER_ADMIN'];
            $legacyRoles = DB::table('roles')->whereIn('name', $legacyNames)->orderBy('name')->lockForUpdate()->get();

            foreach ($legacyRoles as $legacyRole) {
                $assignments = DB::table('role_user as ru')
                    ->join('users as u', 'u.id', '=', 'ru.user_id')
                    ->where('ru.role_id', $legacyRole->id)
                    ->lockForUpdate()
                    ->get(['u.id as user_id', 'u.tenant_id']);

                foreach ($assignments as $assignment) {
                    $userId = (string) $assignment->user_id;
                    $tenantId = (string) $assignment->tenant_id;

                    if ($legacyRole->name === 'OPERATOR') {
                        DB::table('role_user')->insertOrIgnore([
                            'role_id' => $adminRole->id,
                            'user_id' => $userId,
                        ]);
                    }

                    DB::table('audit_logs')->insert([
                        'id' => (string) Str::uuid(),
                        'tenant_id' => $tenantId,
                        'user_id' => null,
                        'action' => $legacyRole->name === 'OPERATOR'
                            ? 'LEGACY_OPERATOR_ROLE_MAPPED_TO_ADMIN'
                            : 'LEGACY_LOCAL_ROLE_RETIRED',
                        'entity_type' => 'User',
                        'entity_id' => $userId,
                        'old_values' => json_encode([
                            'role_name' => $legacyRole->name,
                            'role_id' => $legacyRole->id,
                        ], JSON_THROW_ON_ERROR),
                        'new_values' => json_encode(
                            $legacyRole->name === 'OPERATOR'
                                ? ['role_name' => 'ADMIN_MADRASAH', 'role_id' => $adminRole->id, 'forced_reauthentication' => true]
                                : ['legacy_role_removed' => true, 'forced_reauthentication' => true],
                            JSON_THROW_ON_ERROR
                        ),
                        'ip_address' => null,
                        'user_agent' => 'system:legacy-role-consolidation',
                        'created_at' => now(),
                    ]);
                }

                // Remove the retired role from all users and remove its permission grants.
                DB::table('role_user')->where('role_id', $legacyRole->id)->delete();
                DB::table('permission_role')->where('role_id', $legacyRole->id)->delete();

                // Do not preserve an unusable role in the operational catalog.
                DB::table('roles')->where('id', $legacyRole->id)->delete();

                // Revoke active sessions, API tokens, and remember-me credentials for affected users.
                $userIds = $assignments->pluck('user_id')->map(fn ($id) => (string) $id)->all();
                if ($userIds !== []) {
                    DB::table('users')->whereIn('id', $userIds)->update(['remember_token' => null]);

                    if (Schema::hasTable('sessions')) {
                        DB::table('sessions')->whereIn('user_id', $userIds)->delete();
                    }

                    if (Schema::hasTable('personal_access_tokens')) {
                        DB::table('personal_access_tokens')
                            ->where('tokenable_type', 'App\\Models\\User')
                            ->whereIn('tokenable_id', $userIds)
                            ->delete();
                    }
                }
            }
        });
    }

    /**
     * Role consolidation is forward-only. Recreating legacy identities could
     * restore retired privileges or reactivate accounts without owner review.
     */
    public function down(): void
    {
        throw new \RuntimeException(
            'Role consolidation cannot be rolled back automatically. Restore the per-madrasah database backup and follow the approved recovery plan.'
        );
    }
};
