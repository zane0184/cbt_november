<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;
use RuntimeException;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (!in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException("Login identity enforcement does not support database driver [{$driver}].");
        }

        $this->assertRequiredColumns();
        $expressions = $driver === 'mysql'
            ? $this->mysqlExpressions()
            : $this->sqliteExpressions();

        // Read-only preflight happens before the first DDL statement. Existing
        // records are never trimmed, guessed, or rewritten by this migration.
        $this->assertExistingRowsAreValid($expressions);

        if ($driver === 'mysql') {
            $this->assertEnforcedMySqlCheckSupport();
            $this->addMysqlCheck('teacher_profiles', 'teacher_profiles_login_identity_chk', $expressions['teacher_identity']);
            $this->addMysqlCheck('teacher_profiles', 'teacher_profiles_nuptk_format_chk', $expressions['nuptk']);
            $this->addMysqlCheck('student_profiles', 'student_profiles_nisn_format_chk', $expressions['student_nisn']);
            return;
        }

        $this->installSqliteTriggers();
    }

    private function assertRequiredColumns(): void
    {
        $required = [
            'teacher_profiles' => ['employment_status', 'nip', 'nik', 'nuptk', 'employee_identifier'],
            'student_profiles' => ['nisn'],
        ];

        foreach ($required as $table => $columns) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("Login identity enforcement requires table [{$table}].");
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    throw new RuntimeException("Login identity enforcement requires column [{$table}.{$column}].");
                }
            }
        }
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
                "Login identity CHECK constraints require Oracle MySQL 8.0.16 or newer; detected [{$version}]."
            );
        }
    }

    private function mysqlExpressions(): array
    {
        return [
            'teacher_identity' => "((employment_status IN ('PNS', 'PPPK') AND nip IS NOT NULL AND nip REGEXP '^[0-9]{18}$' AND nik IS NULL AND employee_identifier = nip) OR (employment_status = 'HONORER' AND nik IS NOT NULL AND nik REGEXP '^[0-9]{16}$' AND nip IS NULL AND employee_identifier = nik))",
            'nuptk' => "(nuptk IS NULL OR nuptk REGEXP '^[0-9]{16}$')",
            'student_nisn' => "(nisn IS NOT NULL AND nisn REGEXP '^[0-9]{10}$')",
        ];
    }

    private function sqliteExpressions(): array
    {
        return [
            'teacher_identity' => "((employment_status IN ('PNS', 'PPPK') AND nip IS NOT NULL AND length(nip) = 18 AND nip NOT GLOB '*[^0-9]*' AND nik IS NULL AND employee_identifier = nip) OR (employment_status = 'HONORER' AND nik IS NOT NULL AND length(nik) = 16 AND nik NOT GLOB '*[^0-9]*' AND nip IS NULL AND employee_identifier = nik))",
            'nuptk' => "(nuptk IS NULL OR (length(nuptk) = 16 AND nuptk NOT GLOB '*[^0-9]*'))",
            'student_nisn' => "(nisn IS NOT NULL AND length(nisn) = 10 AND nisn NOT GLOB '*[^0-9]*')",
        ];
    }

    private function assertExistingRowsAreValid(array $expressions): void
    {
        $invalidTeacher = DB::table('teacher_profiles')
            ->whereRaw('NOT COALESCE((' . $expressions['teacher_identity'] . '), 0)')
            ->exists();

        if ($invalidTeacher) {
            throw new RuntimeException(
                'Login identity enforcement aborted: existing teacher profile category/identity anomalies must be reconciled first.'
            );
        }

        $invalidNuptk = DB::table('teacher_profiles')
            ->whereNotNull('nuptk')
            ->whereRaw('NOT COALESCE((' . $expressions['nuptk'] . '), 0)')
            ->exists();

        if ($invalidNuptk) {
            throw new RuntimeException(
                'Login identity enforcement aborted: existing NUPTK format anomalies must be reconciled first.'
            );
        }

        $invalidStudent = DB::table('student_profiles')
            ->whereRaw('NOT COALESCE((' . $expressions['student_nisn'] . '), 0)')
            ->exists();

        if ($invalidStudent) {
            throw new RuntimeException(
                'Login identity enforcement aborted: existing NISN format anomalies must be reconciled first.'
            );
        }
    }

    private function addMysqlCheck(string $table, string $name, string $expression): void
    {
        $existing = DB::selectOne(
            'SELECT cc.CHECK_CLAUSE, cc.ENFORCED
             FROM information_schema.TABLE_CONSTRAINTS tc
             INNER JOIN information_schema.CHECK_CONSTRAINTS cc
                ON cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
               AND cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
             WHERE tc.TABLE_SCHEMA = DATABASE()
               AND tc.TABLE_NAME = ?
               AND tc.CONSTRAINT_NAME = ?
               AND tc.CONSTRAINT_TYPE = ?',
            [$table, $name, 'CHECK']
        );

        if ($existing !== null) {
            if (
                strtoupper((string) $existing->ENFORCED) !== 'YES'
                || $this->normalizeCheck((string) $existing->CHECK_CLAUSE) !== $this->normalizeCheck($expression)
            ) {
                throw new RuntimeException("Existing CHECK constraint [{$name}] differs from the required definition.");
            }
            return;
        }

        DB::statement(
            'ALTER TABLE ' . chr(96) . $table . chr(96)
            . ' ADD CONSTRAINT ' . chr(96) . $name . chr(96)
            . ' CHECK (' . $expression . ')'
        );
    }

    private function normalizeCheck(string $expression): string
    {
        return strtolower((string) preg_replace('/[\s()\x60]/', '', $expression));
    }

    private function installSqliteTriggers(): void
    {
        $expressions = $this->sqliteExpressions();
        $teacherNew = str_replace(
            ['employment_status', 'nip', 'nik', 'employee_identifier'],
            ['NEW.employment_status', 'NEW.nip', 'NEW.nik', 'NEW.employee_identifier'],
            $expressions['teacher_identity']
        );
        $nuptkNew = str_replace('nuptk', 'NEW.nuptk', $expressions['nuptk']);
        $studentNew = str_replace('nisn', 'NEW.nisn', $expressions['student_nisn']);

        $triggers = [
            'teacher_profiles_login_identity_bi' => "CREATE TRIGGER teacher_profiles_login_identity_bi BEFORE INSERT ON teacher_profiles FOR EACH ROW WHEN NOT (({$teacherNew}) AND ({$nuptkNew})) BEGIN SELECT RAISE(ABORT, 'Invalid teacher login identity or NUPTK format'); END",
            'teacher_profiles_login_identity_bu' => "CREATE TRIGGER teacher_profiles_login_identity_bu BEFORE UPDATE ON teacher_profiles FOR EACH ROW WHEN NOT (({$teacherNew}) AND ({$nuptkNew})) BEGIN SELECT RAISE(ABORT, 'Invalid teacher login identity or NUPTK format'); END",
            'student_profiles_login_identity_bi' => "CREATE TRIGGER student_profiles_login_identity_bi BEFORE INSERT ON student_profiles FOR EACH ROW WHEN NOT ({$studentNew}) BEGIN SELECT RAISE(ABORT, 'Invalid student NISN format'); END",
            'student_profiles_login_identity_bu' => "CREATE TRIGGER student_profiles_login_identity_bu BEFORE UPDATE ON student_profiles FOR EACH ROW WHEN NOT ({$studentNew}) BEGIN SELECT RAISE(ABORT, 'Invalid student NISN format'); END",
        ];

        foreach ($triggers as $name => $sql) {
            DB::unprepared('DROP TRIGGER IF EXISTS ' . chr(96) . $name . chr(96));
            DB::unprepared($sql);
        }
    }

    public function down(): void
    {
        throw new LogicException(
            'Login identity database constraints are forward-only security invariants and cannot be rolled back automatically.'
        );
    }
};
