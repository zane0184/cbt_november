<?php

namespace App\\Services;

use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Str;
use RuntimeException;

class RetireKamadRoleService
{
    private const PORTAL_ROLES = ['ADMIN_MADRASAH', 'GURU', 'SISWA'];

    public function retire(): array
    {
        return DB::transaction(function (): array {
            $role = DB::table('roles')->where('name', 'KAMAD')->lockForUpdate()->first();
            $orphanUsers = DB::table('users')->where('role', 'KAMAD')->get();
            $assignments = $role
                ? DB::table('role_user')->where('role_id', $role->id)->orderBy('user_id')->lockForUpdate()->get()
                : collect();

            foreach ($orphanUsers as $user) {
                if (!$assignments->contains('user_id', $user->id)) {
                    throw new RuntimeException("KAMAD role mismatch for user {$user->id}; role column has no matching role_user assignment.");
                }
            }

            if (!$role) {
                return ['archived_roles' => 0, 'retired_users' => 0];
            }

            $roleIdsByUser = [];
            foreach ($assignments as $assignment) {
                $user = DB::table('users')->where('id', $assignment->user_id)->lockForUpdate()->first();
                if (!$user) {
                    throw new RuntimeException("KAMAD assignment references missing user {$assignment->user_id}.");
                }

                $remainingRoles = DB::table('role_user as ru')
                    ->join('roles as r', 'r.id', '=', 'ru.role_id')
                    ->where('ru.user_id', $user->id)
                    ->where('ru.role_id', '!=', $role->id)
                    ->pluck('r.name')
                    ->all();

                if (count($remainingRoles) > 1 || array_diff($remainingRoles, self::PORTAL_ROLES) !== []) {
                    throw new RuntimeException("KAMAD user {$user->id} has ambiguous or non-portal additional roles; manual reconciliation required.");
                }

                $newLegacyRole = $remainingRoles[0] ?? 'UNASSIGNED';
                $this->record([
                    'record_type' => 'USER_ROLE_DETACH',
                    'role_id' => $role->id,
                    'old_role_name' => $role->name,
                    'old_role_label' => $role->label,
                    'archived_role_name' => null,
                    'archived_role_label' => null,
                    'user_id' => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'users_role_before' => $user->role,
                    'users_role_after' => $newLegacyRole,
                    'permission_id' => null,
                ]);

                DB::table('role_user')->where('user_id', $user->id)->where('role_id', $role->id)->delete();
                DB::table('users')->where('id', $user->id)->update(['role' => $newLegacyRole, 'updated_at' => now()]);
                $roleIdsByUser[$user->id] = true;
            }

            $permissions = DB::table('permission_role')->where('role_id', $role->id)->get();
            foreach ($permissions as $permission) {
                $this->record([
                    'record_type' => 'PERMISSION_DETACH',
                    'role_id' => $role->id,
                    'old_role_name' => $role->name,
                    'old_role_label' => $role->label,
                    'archived_role_name' => null,
                    'archived_role_label' => null,
                    'user_id' => null,
                    'tenant_id' => null,
                    'users_role_before' => null,
                    'users_role_after' => null,
                    'permission_id' => $permission->permission_id,
                ]);
            }
            DB::table('permission_role')->where('role_id', $role->id)->delete();

            $archivedName = 'ARCHIVED_KAMAD_' . $role->id;
            if (DB::table('roles')->where('name', $archivedName)->where('id', '!=', $role->id)->exists()) {
                throw new RuntimeException('KAMAD archive name collision; retirement aborted.');
            }

            $this->record([
                'record_type' => 'ROLE_ARCHIVE',
                'role_id' => $role->id,
                'old_role_name' => $role->name,
                'old_role_label' => $role->label,
                'archived_role_name' => $archivedName,
                'archived_role_label' => 'Archived legacy role: KAMAD',
                'user_id' => null,
                'tenant_id' => null,
                'users_role_before' => null,
                'users_role_after' => null,
                'permission_id' => null,
            ]);

            DB::table('roles')->where('id', $role->id)->update([
                'name' => $archivedName,
                'label' => 'Archived legacy role: KAMAD',
                'updated_at' => now(),
            ]);

            return ['archived_roles' => 1, 'retired_users' => count($roleIdsByUser)];
        }, 3);
    }

    public function rollback(): void
    {
        DB::transaction(function (): void {
            $records = DB::table('kamad_role_retirement_records')->orderBy('migrated_at')->lockForUpdate()->get();
            foreach ($records->where('record_type', 'USER_ROLE_DETACH') as $record) {
                $user = DB::table('users')->where('id', $record->user_id)->lockForUpdate()->first();
                if (!$user || $user->role !== $record->users_role_after) {
                    throw new RuntimeException("Cannot restore KAMAD role for user {$record->user_id}: account role changed after retirement.");
                }
                if (DB::table('role_user')->where('user_id', $record->user_id)->where('role_id', $record->role_id)->exists()) {
                    throw new RuntimeException("Cannot restore KAMAD role for user {$record->user_id}: assignment already exists.");
                }
                DB::table('role_user')->insert(['role_id' => $record->role_id, 'user_id' => $record->user_id]);
                DB::table('users')->where('id', $record->user_id)->update(['role' => $record->users_role_before, 'updated_at' => now()]);
            }

            foreach ($records->where('record_type', 'ROLE_ARCHIVE') as $record) {
                $role = DB::table('roles')->where('id', $record->role_id)->lockForUpdate()->first();
                if (!$role || $role->name !== $record->archived_role_name) {
                    throw new RuntimeException('Cannot restore KAMAD role: archived role identity changed.');
                }
                if (DB::table('roles')->where('name', $record->old_role_name)->where('id', '!=', $record->role_id)->exists()) {
                    throw new RuntimeException('Cannot restore KAMAD role: original role name is already in use.');
                }
                DB::table('roles')->where('id', $record->role_id)->update([
                    'name' => $record->old_role_name,
                    'label' => $record->old_role_label,
                    'updated_at' => now(),
                ]);
            }

            foreach ($records->where('record_type', 'PERMISSION_DETACH') as $record) {
                DB::table('permission_role')->insertOrIgnore([
                    'role_id' => $record->role_id,
                    'permission_id' => $record->permission_id,
                ]);
            }
        }, 3);
    }

    private function record(array $values): void
    {
        DB::table('kamad_role_retirement_records')->insert([
            'id' => (string) Str::uuid(),
            ...$values,
            'migrated_at' => now(),
        ]);
    }
}
