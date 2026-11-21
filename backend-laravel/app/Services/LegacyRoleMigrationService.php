<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class LegacyRoleMigrationService
{
    private const ROLE_MAP = [
        'ADMIN' => 'ADMIN_MADRASAH',
        'OPERATOR' => 'ADMIN_MADRASAH',
        'PROKTOR' => 'GURU',
    ];

    public function migrate(): array
    {
        return DB::transaction(function (): array {
            $this->assertSchemaAndForeignKeyPreflight();

            $legacyRoles = DB::table('roles')
                ->whereIn('name', array_keys(self::ROLE_MAP))
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $orphanLegacyUsers = DB::table('users')
                ->whereIn('role', array_keys(self::ROLE_MAP))
                ->get();

            $legacyRoleIds = $legacyRoles->pluck('id')->all();
            $assignmentRows = $legacyRoleIds === []
                ? collect()
                : DB::table('role_user as ru')
                    ->join('users as u', 'u.id', '=', 'ru.user_id')
                    ->join('roles as r', 'r.id', '=', 'ru.role_id')
                    ->whereIn('ru.role_id', $legacyRoleIds)
                    ->orderBy('u.id')
                    ->lockForUpdate()
                    ->get([
                        'u.id as user_id',
                        'u.tenant_id',
                        'u.role as legacy_user_role',
                        'u.deleted_at',
                        'ru.role_id',
                        'r.name as legacy_role_name',
                        'r.label as legacy_role_label',
                    ]);

            $assignmentsByUser = $assignmentRows->groupBy('user_id');

            foreach ($orphanLegacyUsers as $user) {
                $assignedLegacyNames = $assignmentRows
                    ->where('user_id', $user->id)
                    ->pluck('legacy_role_name')
                    ->all();

                if (!in_array($user->role, $assignedLegacyNames, true)) {
                    throw new RuntimeException(
                        "Legacy user role mismatch for user {$user->id}: users.role={$user->role} but no matching role_user assignment exists. Manual reconciliation required."
                    );
                }
            }

            $canonicalRoleIds = [
                'ADMIN_MADRASAH' => DB::table('roles')->where('name', 'ADMIN_MADRASAH')->value('id'),
                'GURU' => DB::table('roles')->where('name', 'GURU')->value('id'),
            ];

            foreach ($assignmentsByUser as $userId => $rows) {
                $first = $rows->first();
                $targetRole = self::ROLE_MAP[$first->legacy_role_name] ?? null;
                $canonicalRoleId = $canonicalRoleIds[$targetRole] ?? null;

                if (!$canonicalRoleId) {
                    throw new RuntimeException("Canonical role {$targetRole} is missing; no legacy role migration was applied.");
                }

                $userRoles = DB::table('role_user as ru')
                    ->join('roles as r', 'r.id', '=', 'ru.role_id')
                    ->where('ru.user_id', $userId)
                    ->pluck('r.name')
                    ->all();

                $legacyNamesForUser = $rows->pluck('legacy_role_name')->unique()->all();
                $canonicalAlreadyAssigned = in_array($targetRole, $userRoles, true);

                if ($targetRole === 'ADMIN_MADRASAH') {
                    $allowedRoles = ['ADMIN', 'OPERATOR', 'ADMIN_MADRASAH'];
                    $allowedUserColumnValues = ['STUDENT', 'ADMIN', 'OPERATOR', 'ADMIN_MADRASAH'];

                    if ($first->tenant_id === null
                        || array_diff($userRoles, $allowedRoles) !== []
                        || !in_array($first->legacy_user_role, $allowedUserColumnValues, true)) {
                        throw new RuntimeException(
                            "Admin legacy role conflict for user {$userId}. Only tenant-local ADMIN/OPERATOR aliases may be mapped to ADMIN_MADRASAH. Manual reconciliation required."
                        );
                    }
                } else {
                    $allowedRoles = ['PROKTOR', 'GURU'];
                    $allowedUserColumnValues = ['STUDENT', 'PROKTOR', 'GURU', 'TEACHER'];

                    if ($first->deleted_at !== null
                        || $first->tenant_id === null
                        || array_diff($userRoles, $allowedRoles) !== []
                        || !in_array($first->legacy_user_role, $allowedUserColumnValues, true)) {
                        throw new RuntimeException(
                            "Proktor legacy role conflict for user {$userId}. User must be active and have only PROKTOR/GURU role assignments. Manual reconciliation required."
                        );
                    }

                    $teacherProfileExists = DB::table('teacher_profiles')
                        ->where('user_id', $userId)
                        ->where('tenant_id', $first->tenant_id)
                        ->whereNull('deleted_at')
                        ->exists();

                    if (!$teacherProfileExists) {
                        throw new RuntimeException(
                            "Proktor user {$userId} has no active TeacherProfile in the same tenant. Manual reconciliation required."
                        );
                    }
                }

                $legacyRoleIdsForUser = $rows->pluck('role_id')->unique()->all();
                $newUserRoleValue = $targetRole;

                foreach ($rows as $row) {
                    $this->insertRecord([
                        'record_type' => 'USER_ROLE_MAPPING',
                        'role_id' => $row->role_id,
                        'old_role_name' => $row->legacy_role_name,
                        'old_role_label' => $row->legacy_role_label,
                        'archived_role_name' => $this->archivedName($row->legacy_role_name, $row->role_id),
                        'archived_role_label' => 'Archived legacy role: ' . $row->legacy_role_name,
                        'user_id' => $userId,
                        'tenant_id' => $row->tenant_id,
                        'canonical_role_id' => $canonicalRoleId,
                        'users_role_before' => $row->legacy_user_role,
                        'users_role_after' => $newUserRoleValue,
                        'canonical_role_was_assigned' => $canonicalAlreadyAssigned,
                    ]);
                }

                if (!$canonicalAlreadyAssigned) {
                    DB::table('role_user')->insert([
                        'role_id' => $canonicalRoleId,
                        'user_id' => $userId,
                    ]);
                }

                DB::table('role_user')
                    ->where('user_id', $userId)
                    ->whereIn('role_id', $legacyRoleIdsForUser)
                    ->delete();

                DB::table('users')
                    ->where('id', $userId)
                    ->update(['role' => $newUserRoleValue, 'updated_at' => now()]);
            }

            foreach ($legacyRoles as $role) {
                $archivedName = $this->archivedName($role->name, $role->id);
                $archivedLabel = 'Archived legacy role: ' . $role->name;

                $collision = DB::table('roles')
                    ->where('name', $archivedName)
                    ->where('id', '!=', $role->id)
                    ->exists();

                if ($collision) {
                    throw new RuntimeException("Archive role name collision for role {$role->id}; no migration should continue.");
                }

                $this->insertRecord([
                    'record_type' => 'ROLE_ARCHIVE',
                    'role_id' => $role->id,
                    'old_role_name' => $role->name,
                    'old_role_label' => $role->label,
                    'archived_role_name' => $archivedName,
                    'archived_role_label' => $archivedLabel,
                    'user_id' => null,
                    'tenant_id' => null,
                    'canonical_role_id' => null,
                    'users_role_before' => null,
                    'users_role_after' => null,
                    'canonical_role_was_assigned' => false,
                ]);

                DB::table('roles')->where('id', $role->id)->update([
                    'name' => $archivedName,
                    'label' => $archivedLabel,
                    'updated_at' => now(),
                ]);
            }

            return [
                'archived_roles' => $legacyRoles->count(),
                'migrated_users' => $assignmentsByUser->count(),
                'permission_role_mutated' => false,
            ];
        }, 3);
    }

    public function rollback(): void
    {
        DB::transaction(function (): void {
            $records = DB::table('legacy_role_migration_records')
                ->orderBy('migrated_at')
                ->lockForUpdate()
                ->get();

            $userRecords = $records
                ->where('record_type', 'USER_ROLE_MAPPING')
                ->groupBy('user_id');

            foreach ($userRecords as $userId => $rows) {
                $user = DB::table('users')->where('id', $userId)->lockForUpdate()->first();

                if (!$user || $user->role !== $rows->first()->users_role_after) {
                    throw new RuntimeException("Cannot rollback legacy role migration for user {$userId}: account role has changed since migration.");
                }

                $canonicalRoleId = $rows->first()->canonical_role_id;
                $currentRoleIds = DB::table('role_user')->where('user_id', $userId)->pluck('role_id')->all();

                if (count($currentRoleIds) !== 1 || (string) $currentRoleIds[0] !== (string) $canonicalRoleId) {
                    throw new RuntimeException("Cannot rollback legacy role migration for user {$userId}: role assignments changed since migration.");
                }

                foreach ($rows as $row) {
                    $exists = DB::table('role_user')
                        ->where('user_id', $userId)
                        ->where('role_id', $row->role_id)
                        ->exists();

                    if (!$exists) {
                        DB::table('role_user')->insert([
                            'role_id' => $row->role_id,
                            'user_id' => $userId,
                        ]);
                    }
                }

                if (!$rows->first()->canonical_role_was_assigned) {
                    DB::table('role_user')
                        ->where('user_id', $userId)
                        ->where('role_id', $canonicalRoleId)
                        ->delete();
                }

                DB::table('users')->where('id', $userId)->update([
                    'role' => $rows->first()->users_role_before,
                    'updated_at' => now(),
                ]);
            }

            $roleRecords = $records->where('record_type', 'ROLE_ARCHIVE');
            foreach ($roleRecords as $row) {
                $current = DB::table('roles')->where('id', $row->role_id)->lockForUpdate()->first();
                if (!$current || $current->name !== $row->archived_role_name) {
                    throw new RuntimeException("Cannot restore legacy role {$row->old_role_name}; archived role identity changed.");
                }

                $nameCollision = DB::table('roles')
                    ->where('name', $row->old_role_name)
                    ->where('id', '!=', $row->role_id)
                    ->exists();

                if ($nameCollision) {
                    throw new RuntimeException("Cannot restore legacy role {$row->old_role_name}; role name is already in use.");
                }

                DB::table('roles')->where('id', $row->role_id)->update([
                    'name' => $row->old_role_name,
                    'label' => $row->old_role_label,
                    'updated_at' => now(),
                ]);
            }

            DB::table('legacy_role_migration_records')->delete();
        }, 3);
    }

    private function assertSchemaAndForeignKeyPreflight(): void
    {
        foreach (['roles', 'role_user', 'permission_role', 'users', 'teacher_profiles', 'legacy_role_migration_records'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("Legacy role migration preflight: required table {$table} is missing.");
            }
        }

        if (!Schema::hasColumn('users', 'role') || !Schema::hasColumn('users', 'deleted_at')) {
            throw new RuntimeException('Legacy role migration preflight: users.role or users.deleted_at is missing.');
        }

        if (!Schema::hasColumn('teacher_profiles', 'tenant_id') || !Schema::hasColumn('teacher_profiles', 'deleted_at')) {
            throw new RuntimeException('Legacy role migration preflight: TeacherProfile tenant/deleted columns are missing.');
        }

        if (DB::getDriverName() === 'mysql') {
            $references = DB::table('information_schema.key_column_usage')
                ->where('constraint_schema', DB::getDatabaseName())
                ->where('referenced_table_name', 'roles')
                ->selectRaw('TABLE_NAME as table_name, COLUMN_NAME as column_name')
                ->get();

            $allowed = ['role_user.role_id', 'permission_role.role_id'];
            $unexpected = $references->filter(
                fn ($row) => !in_array($row->table_name . '.' . $row->column_name, $allowed, true)
            );

            if ($unexpected->isNotEmpty()) {
                throw new RuntimeException(
                    'Legacy role migration preflight found additional foreign-key references to roles: ' .
                    $unexpected->map(fn ($row) => $row->table_name . '.' . $row->column_name)->implode(', ')
                );
            }
        }

        $orphanRoleUser = DB::table('role_user as ru')
            ->leftJoin('roles as r', 'r.id', '=', 'ru.role_id')
            ->leftJoin('users as u', 'u.id', '=', 'ru.user_id')
            ->where(function ($query): void {
                $query->whereNull('r.id')->orWhereNull('u.id');
            })
            ->exists();

        $orphanPermissionRole = DB::table('permission_role as pr')
            ->leftJoin('roles as r', 'r.id', '=', 'pr.role_id')
            ->whereNull('r.id')
            ->exists();

        if ($orphanRoleUser || $orphanPermissionRole) {
            throw new RuntimeException('Legacy role migration preflight found orphan role_user or permission_role rows.');
        }
    }

    private function insertRecord(array $data): void
    {
        DB::table('legacy_role_migration_records')->insert([
            'id' => (string) Str::uuid(),
            ...$data,
            'migrated_at' => now(),
        ]);
    }

    private function archivedName(string $roleName, string $roleId): string
    {
        return 'LEGACY_ARCHIVED_' . $roleName . '_' . substr(str_replace('-', '', $roleId), 0, 8);
    }
}
