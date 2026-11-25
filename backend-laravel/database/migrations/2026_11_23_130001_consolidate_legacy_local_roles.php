<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['roles', 'role_user', 'permission_role', 'users', 'audit_logs'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new \RuntimeException("Legacy role retirement aborted: required table [{$table}] is missing.");
            }
        }

        DB::transaction(function (): void {
            $legacyRoles = DB::table('roles')
                ->whereIn('name', ['OPERATOR', 'KAMAD', 'PROKTOR', 'SUPER_ADMIN'])
                ->orderBy('name')
                ->lockForUpdate()
                ->get();

            foreach ($legacyRoles as $legacyRole) {
                $assignments = DB::table('role_user as ru')
                    ->join('users as u', 'u.id', '=', 'ru.user_id')
                    ->where('ru.role_id', $legacyRole->id)
                    ->orderBy('u.id')
                    ->lockForUpdate()
                    ->get(['u.id as user_id', 'u.tenant_id']);

                foreach ($assignments as $assignment) {
                    DB::table('audit_logs')->insert([
                        'id' => (string) Str::uuid(),
                        'tenant_id' => $assignment->tenant_id ? (string) $assignment->tenant_id : null,
                        'user_id' => null,
                        'action' => 'LEGACY_ROLE_ACCESS_RETIRED',
                        'entity_type' => 'User',
                        'entity_id' => (string) $assignment->user_id,
                        'old_values' => json_encode([
                            'role_name' => $legacyRole->name,
                            'role_id' => (string) $legacyRole->id,
                        ], JSON_THROW_ON_ERROR),
                        'new_values' => json_encode([
                            'role_assignment_preserved' => true,
                            'role_row_preserved' => true,
                            'permission_grants_detached' => true,
                            'automatic_role_mapping' => false,
                            'forced_reauthentication' => true,
                        ], JSON_THROW_ON_ERROR),
                        'ip_address' => null,
                        'user_agent' => 'system:legacy-role-access-retirement',
                        'created_at' => now(),
                    ]);
                }

                DB::table('permission_role')->where('role_id', $legacyRole->id)->delete();

                $userIds = $assignments->pluck('user_id')->map(fn ($id) => (string) $id)->unique()->values()->all();
                if ($userIds === []) {
                    continue;
                }

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
        });
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Legacy role access retirement cannot be rolled back automatically. Restore the per-madrasah database backup and follow the approved recovery plan.'
        );
    }
};
