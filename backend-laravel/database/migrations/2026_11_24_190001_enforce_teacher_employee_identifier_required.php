<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CHECK_NAME = 'teacher_profiles_employee_identifier_required_chk';

    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException("Employee identifier enforcement does not support database driver [{$driver}].");
        }

        if (!Schema::hasTable('teacher_profiles') || !Schema::hasColumn('teacher_profiles', 'employee_identifier')) {
            throw new RuntimeException('Employee identifier enforcement requires teacher_profiles.employee_identifier.');
        }

        // Fail closed before DDL. Existing values are never backfilled or guessed.
        if (DB::table('teacher_profiles')->whereNull('employee_identifier')->exists()) {
            throw new RuntimeException(
                'Employee identifier enforcement aborted: existing teacher profile rows with NULL employee_identifier must be reconciled first.'
            );
        }

        if ($driver === 'mysql') {
            $this->assertEnforcedMySqlCheckSupport();
            $this->addMysqlCheck();
            return;
        }

        $this->installSqliteTriggers();
    }

    private function assertEnforcedMySqlCheckSupport(): void
    {
        $row = DB::selectOne('SELECT VERSION() AS version');
        $version = (string) ($row?->version ?? '');

        if (
            stripos($version, 'mariadb') !== false
            || preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches) !== 1
            || version_compare($matches[1], '8.0.16', '<')
        ) {
            throw new RuntimeException(
                "Employee identifier CHECK constraint requires Oracle MySQL 8.0.16 or newer; detected [{$version}]."
            );
        }
    }

    private function addMysqlCheck(): void
    {
        $existing = DB::selectOne(
            'SELECT cc.CHECK_CLAUSE, tc.ENFORCED
             FROM information_schema.TABLE_CONSTRAINTS tc
             INNER JOIN information_schema.CHECK_CONSTRAINTS cc
                ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
               AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
             WHERE tc.TABLE_SCHEMA = DATABASE()
               AND tc.TABLE_NAME = ?
               AND tc.CONSTRAINT_NAME = ?
               AND tc.CONSTRAINT_TYPE = ?',
            ['teacher_profiles', self::CHECK_NAME, 'CHECK']
        );

        if ($existing !== null) {
            $clause = strtolower((string) preg_replace('/[\s()\x60]/', '', (string) $existing->CHECK_CLAUSE));
            if (strtoupper((string) $existing->ENFORCED) !== 'YES' || $clause !== 'employee_identifierisnotnull') {
                throw new RuntimeException('Existing employee_identifier CHECK constraint differs from the required enforced definition.');
            }
            return;
        }

        DB::statement(
            'ALTER TABLE teacher_profiles ADD CONSTRAINT ' . self::CHECK_NAME . ' CHECK (employee_identifier IS NOT NULL)'
        );
    }

    private function installSqliteTriggers(): void
    {
        $triggers = [
            'teacher_profiles_employee_identifier_bi' => "CREATE TRIGGER teacher_profiles_employee_identifier_bi BEFORE INSERT ON teacher_profiles FOR EACH ROW WHEN NEW.employee_identifier IS NULL BEGIN SELECT RAISE(ABORT, 'employee_identifier is required'); END",
            'teacher_profiles_employee_identifier_bu' => "CREATE TRIGGER teacher_profiles_employee_identifier_bu BEFORE UPDATE ON teacher_profiles FOR EACH ROW WHEN NEW.employee_identifier IS NULL BEGIN SELECT RAISE(ABORT, 'employee_identifier is required'); END",
        ];

        foreach ($triggers as $name => $sql) {
            DB::unprepared('DROP TRIGGER IF EXISTS ' . $name);
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        throw new LogicException(
            'Employee identifier enforcement is a forward-only security invariant and cannot be rolled back automatically.'
        );
    }
};
