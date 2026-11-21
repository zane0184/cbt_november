<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_FOREIGN_KEYS = [
        'registered_devices_tenant_id_foreign',
        'registered_devices_user_id_foreign',
        'registered_devices_revoked_by_foreign',
        'device_leases_tenant_id_foreign',
        'device_leases_exam_attempt_id_foreign',
        'device_leases_registered_device_id_foreign',
        'device_leases_released_by_foreign',
    ];

    private const NEW_FOREIGN_KEYS = [
        'registered_devices_tenant_fk',
        'registered_devices_user_tenant_fk',
        'registered_devices_revoker_tenant_fk',
        'device_leases_tenant_fk',
        'device_leases_attempt_tenant_fk',
        'device_leases_device_tenant_fk',
        'device_leases_releaser_tenant_fk',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new \RuntimeException(
                'Device lease tenant hardening requires MySQL 8/InnoDB. SQLite is not a substitute for this production constraint migration.'
            );
        }

        foreach ([
            'registered_devices' => ['tenant_id', 'user_id', 'revoked_by', 'id'],
            'device_leases' => ['tenant_id', 'exam_attempt_id', 'registered_device_id', 'released_by', 'fence_generation', 'status', 'expires_at'],
            'exam_attempts' => ['id', 'tenant_id', 'participant_id', 'fence_generation', 'current_device_token'],
            'users' => ['id', 'tenant_id'],
        ] as $table => $columns) {
            if (!Schema::hasTable($table)) {
                throw new \RuntimeException("Device lease hardening preflight: required table {$table} is missing.");
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    throw new \RuntimeException("Device lease hardening preflight: required column {$table}.{$column} is missing.");
                }
            }
        }

        $foreignKeys = $this->foreignKeyNames();
        $oldPresent = array_values(array_intersect(self::OLD_FOREIGN_KEYS, $foreignKeys));
        $newPresent = array_values(array_intersect(self::NEW_FOREIGN_KEYS, $foreignKeys));
        $hasDeviceCandidateKey = $this->hasUniqueIndex('registered_devices', ['id', 'tenant_id']);

        // A completed DDL sequence may be retried if the process stopped before
        // Laravel recorded the migration. A mixed state is never guessed or repaired.
        if (count($newPresent) === count(self::NEW_FOREIGN_KEYS)
            && count($oldPresent) === 0
            && $hasDeviceCandidateKey) {
            return;
        }

        if (count($oldPresent) !== count(self::OLD_FOREIGN_KEYS) || count($newPresent) !== 0) {
            throw new \RuntimeException(
                'Device lease hardening found an unknown/partial FK state. No DDL was attempted; inspect information_schema before recovery.'
            );
        }

        if (!$this->hasUniqueIndex('users', ['id', 'tenant_id'])
            || !$this->hasUniqueIndex('exam_attempts', ['id', 'tenant_id'])) {
            throw new \RuntimeException(
                'Device lease hardening requires candidate keys users(id,tenant_id) and exam_attempts(id,tenant_id). No DDL was attempted.'
            );
        }

        $preflight = [
            'registered_device_user_tenant_mismatch' => DB::table('registered_devices as rd')
                ->join('users as u', 'u.id', '=', 'rd.user_id')
                ->whereColumn('u.tenant_id', '<>', 'rd.tenant_id')->count(),
            'registered_device_revoker_tenant_mismatch' => DB::table('registered_devices as rd')
                ->join('users as u', 'u.id', '=', 'rd.revoked_by')
                ->whereNotNull('rd.revoked_by')
                ->whereColumn('u.tenant_id', '<>', 'rd.tenant_id')->count(),
            'device_lease_attempt_tenant_mismatch' => DB::table('device_leases as dl')
                ->join('exam_attempts as ea', 'ea.id', '=', 'dl.exam_attempt_id')
                ->whereColumn('ea.tenant_id', '<>', 'dl.tenant_id')->count(),
            'device_lease_device_tenant_mismatch' => DB::table('device_leases as dl')
                ->join('registered_devices as rd', 'rd.id', '=', 'dl.registered_device_id')
                ->whereColumn('rd.tenant_id', '<>', 'dl.tenant_id')->count(),
            'device_lease_releaser_tenant_mismatch' => DB::table('device_leases as dl')
                ->join('users as u', 'u.id', '=', 'dl.released_by')
                ->whereNotNull('dl.released_by')
                ->whereColumn('u.tenant_id', '<>', 'dl.tenant_id')->count(),
            'active_lease_without_expiry' => DB::table('device_leases')
                ->where('status', 'ACTIVE')->whereNull('expires_at')->count(),
            'active_lease_generation_mismatch' => DB::table('device_leases as dl')
                ->join('exam_attempts as ea', 'ea.id', '=', 'dl.exam_attempt_id')
                ->where('dl.status', 'ACTIVE')
                ->whereColumn('dl.fence_generation', '<>', 'ea.fence_generation')->count(),
            'active_lease_device_token_mismatch' => DB::table('device_leases as dl')
                ->join('exam_attempts as ea', 'ea.id', '=', 'dl.exam_attempt_id')
                ->join('registered_devices as rd', 'rd.id', '=', 'dl.registered_device_id')
                ->where('dl.status', 'ACTIVE')
                ->where(function ($query) {
                    $query->whereNull('ea.current_device_token')
                        ->orWhereColumn('ea.current_device_token', '<>', 'rd.device_token');
                })->count(),
            'active_lease_participant_user_mismatch' => DB::table('device_leases as dl')
                ->join('exam_attempts as ea', 'ea.id', '=', 'dl.exam_attempt_id')
                ->join('exam_participants as ep', 'ep.id', '=', 'ea.participant_id')
                ->join('registered_devices as rd', 'rd.id', '=', 'dl.registered_device_id')
                ->where('dl.status', 'ACTIVE')
                ->where(function ($query) {
                    $query->whereColumn('rd.user_id', '<>', 'ep.user_id')
                        ->orWhereColumn('rd.tenant_id', '<>', 'ep.tenant_id');
                })->count(),
        ];

        if (array_sum($preflight) > 0) {
            throw new \RuntimeException(
                'Device lease hardening stopped before DDL because legacy rows require explicit reconciliation: '
                . json_encode($preflight, JSON_UNESCAPED_SLASHES)
            );
        }

        if (!$hasDeviceCandidateKey) {
            DB::statement('ALTER TABLE registered_devices ADD UNIQUE KEY registered_devices_id_tenant_unique (id, tenant_id)');
        }

        // MySQL DDL may implicitly commit. All data and FK preflight checks are
        // completed above before the first ALTER statement.
        DB::statement("
            ALTER TABLE registered_devices
                DROP FOREIGN KEY registered_devices_tenant_id_foreign,
                DROP FOREIGN KEY registered_devices_user_id_foreign,
                DROP FOREIGN KEY registered_devices_revoked_by_foreign,
                ADD CONSTRAINT registered_devices_tenant_fk
                    FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE RESTRICT,
                ADD CONSTRAINT registered_devices_user_tenant_fk
                    FOREIGN KEY (user_id, tenant_id) REFERENCES users (id, tenant_id) ON DELETE RESTRICT,
                ADD CONSTRAINT registered_devices_revoker_tenant_fk
                    FOREIGN KEY (revoked_by, tenant_id) REFERENCES users (id, tenant_id) ON DELETE RESTRICT
        ");

        DB::statement("
            ALTER TABLE device_leases
                DROP FOREIGN KEY device_leases_tenant_id_foreign,
                DROP FOREIGN KEY device_leases_exam_attempt_id_foreign,
                DROP FOREIGN KEY device_leases_registered_device_id_foreign,
                DROP FOREIGN KEY device_leases_released_by_foreign,
                ADD CONSTRAINT device_leases_tenant_fk
                    FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE RESTRICT,
                ADD CONSTRAINT device_leases_attempt_tenant_fk
                    FOREIGN KEY (exam_attempt_id, tenant_id) REFERENCES exam_attempts (id, tenant_id) ON DELETE RESTRICT,
                ADD CONSTRAINT device_leases_device_tenant_fk
                    FOREIGN KEY (registered_device_id, tenant_id) REFERENCES registered_devices (id, tenant_id) ON DELETE RESTRICT,
                ADD CONSTRAINT device_leases_releaser_tenant_fk
                    FOREIGN KEY (released_by, tenant_id) REFERENCES users (id, tenant_id) ON DELETE RESTRICT
        ");

        $after = $this->foreignKeyNames();
        if (count(array_intersect(self::NEW_FOREIGN_KEYS, $after)) !== count(self::NEW_FOREIGN_KEYS)
            || !$this->hasUniqueIndex('registered_devices', ['id', 'tenant_id'])) {
            throw new \RuntimeException(
                'Device lease hardening DDL completed without the complete expected constraint set. Stop and inspect information_schema; do not retry blindly.'
            );
        }
    }

    public function down(): void
    {
        throw new \RuntimeException(
            'Device lease tenant hardening is forward-only. Do not remove tenant constraints or device/lease audit history automatically.'
        );
    }

    private function foreignKeyNames(): array
    {
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME IN (?, ?)',
            ['registered_devices', 'device_leases']
        );

        return array_map(fn ($row) => $row->CONSTRAINT_NAME, $rows);
    }

    private function hasUniqueIndex(string $table, array $columns): bool
    {
        $rows = DB::select(
            'SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR \',\') AS index_columns
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND NON_UNIQUE = 0
             GROUP BY INDEX_NAME',
            [$table]
        );

        $expected = implode(',', $columns);
        foreach ($rows as $row) {
            if ($row->index_columns === $expected) {
                return true;
            }
        }

        return false;
    }
};
