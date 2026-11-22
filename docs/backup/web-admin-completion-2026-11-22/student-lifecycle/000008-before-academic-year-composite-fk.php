<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->assertPrerequisites();

        if (Schema::hasColumn('student_profiles', 'academic_status')
            || Schema::hasColumn('student_profiles', 'entry_academic_year_id')
            || Schema::hasColumn('rombel_student', 'academic_year_id')) {
            throw new RuntimeException('000008 partial state: one or more lifecycle columns already exist. No DDL executed.');
        }
        foreach ([
            ['student_profiles', 'student_profiles_entry_year_tenant_fk'],
            ['rombel_student', 'rombel_student_academic_year_tenant_fk'],
        ] as [$table, $name]) {
            if ($this->hasForeignKey($table, $name)) {
                throw new RuntimeException("000008 partial state: unexpected FK {$table}.{$name} already exists.");
            }
        }
        foreach ([
            ['student_profiles', 'student_profiles_tenant_status_idx'],
            ['student_profiles', 'student_profiles_entry_year_idx'],
            ['rombel_student', 'rombel_student_tenant_student_year_unique'],
            ['rombel_student', 'rombel_student_academic_year_idx'],
        ] as [$table, $name]) {
            if (Schema::hasIndex($table, $name)) {
                throw new RuntimeException("000008 partial state: unexpected index {$table}.{$name} already exists.");
            }
        }

        $duplicatePlacements = DB::table('rombel_student as rs')
            ->join('rombels as r', 'r.id', '=', 'rs.rombel_id')
            ->select('rs.tenant_id', 'rs.student_id', 'r.academic_year_id')
            ->groupBy('rs.tenant_id', 'rs.student_id', 'r.academic_year_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplicatePlacements > 0) {
            throw new RuntimeException("000008 preflight failed: {$duplicatePlacements} student-year combinations already have multiple rombel placements. Resolve them explicitly before migration; no automatic membership deletion is performed.");
        }

        $orphanRombelYear = DB::table('rombel_student as rs')
            ->leftJoin('rombels as r', 'r.id', '=', 'rs.rombel_id')
            ->whereNull('r.academic_year_id')
            ->count();
        if ($orphanRombelYear > 0) {
            throw new RuntimeException("000008 preflight failed: {$orphanRombelYear} memberships have no academic year.");
        }

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->string('academic_status', 20)->default('ACTIVE');
            $table->uuid('entry_academic_year_id')->nullable();
            $table->index(['tenant_id', 'academic_status'], 'student_profiles_tenant_status_idx');
            $table->index(['entry_academic_year_id', 'tenant_id'], 'student_profiles_entry_year_idx');
        });

        Schema::table('rombel_student', function (Blueprint $table): void {
            $table->uuid('academic_year_id')->nullable();
        });

        DB::statement(<<<'SQL'
            UPDATE rombel_student
            SET academic_year_id = (
                SELECT r.academic_year_id
                FROM rombels AS r
                WHERE r.id = rombel_student.rombel_id
            )
            WHERE academic_year_id IS NULL
        SQL);

        $nullYearRows = DB::table('rombel_student')->whereNull('academic_year_id')->count();
        if ($nullYearRows > 0) {
            throw new RuntimeException("000008 backfill failed: {$nullYearRows} rombel memberships have no academic_year_id. Inspect migration state before retrying.");
        }

        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->foreign(['entry_academic_year_id', 'tenant_id'], 'student_profiles_entry_year_tenant_fk')
                ->references(['id', 'tenant_id'])->on('academic_years')->restrictOnDelete();
        });

        Schema::table('rombel_student', function (Blueprint $table): void {
            $table->uuid('academic_year_id')->nullable(false)->change();
            $table->index(['academic_year_id', 'tenant_id'], 'rombel_student_academic_year_idx');
            $table->unique(['tenant_id', 'student_id', 'academic_year_id'], 'rombel_student_tenant_student_year_unique');
            $table->foreign(['academic_year_id', 'tenant_id'], 'rombel_student_academic_year_tenant_fk')
                ->references(['id', 'tenant_id'])->on('academic_years')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $this->assertPrerequisites();
        if (!Schema::hasColumn('student_profiles', 'academic_status')
            || !Schema::hasColumn('student_profiles', 'entry_academic_year_id')
            || !Schema::hasColumn('rombel_student', 'academic_year_id')) {
            throw new RuntimeException('000008 rollback blocked: lifecycle columns are missing.');
        }
        if (!$this->hasForeignKey('student_profiles', 'student_profiles_entry_year_tenant_fk')
            || !$this->hasForeignKey('rombel_student', 'rombel_student_academic_year_tenant_fk')) {
            throw new RuntimeException('000008 rollback blocked: expected composite foreign keys are missing.');
        }
        if (!Schema::hasIndex('student_profiles', 'student_profiles_tenant_status_idx')
            || !Schema::hasIndex('student_profiles', 'student_profiles_entry_year_idx')
            || !Schema::hasIndex('rombel_student', 'rombel_student_tenant_student_year_unique')
            || !Schema::hasIndex('rombel_student', 'rombel_student_academic_year_idx')) {
            throw new RuntimeException('000008 rollback blocked: expected lifecycle indexes are missing.');
        }

        Schema::table('rombel_student', function (Blueprint $table): void {
            $table->dropForeign('rombel_student_academic_year_tenant_fk');
            $table->dropUnique('rombel_student_tenant_student_year_unique');
            $table->dropIndex('rombel_student_academic_year_idx');
            $table->dropColumn('academic_year_id');
        });
        Schema::table('student_profiles', function (Blueprint $table): void {
            $table->dropForeign('student_profiles_entry_year_tenant_fk');
            $table->dropIndex('student_profiles_tenant_status_idx');
            $table->dropIndex('student_profiles_entry_year_idx');
            $table->dropColumn(['academic_status', 'entry_academic_year_id']);
        });
    }

    private function assertPrerequisites(): void
    {
        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('000008 supports only MySQL 8+ and SQLite test connections.');
        }
        foreach (['student_profiles', 'rombel_student', 'rombels', 'academic_years'] as $table) {
            if (!Schema::hasTable($table)) throw new RuntimeException("000008 prerequisite table missing: {$table}.");
        }
        foreach ([['student_profiles','tenant_id'], ['rombel_student','tenant_id'], ['rombel_student','student_id'], ['rombel_student','rombel_id'], ['rombels','academic_year_id'], ['academic_years','id'], ['academic_years','tenant_id']] as [$table,$column]) {
            if (!Schema::hasColumn($table, $column)) throw new RuntimeException("000008 prerequisite column missing: {$table}.{$column}.");
        }
        if ($driver === 'mysql') {
            $engines = DB::select("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('student_profiles','rombel_student','rombels','academic_years')");
            if (count($engines) !== 4 || collect($engines)->contains(fn ($row) => strtolower((string) $row->ENGINE) !== 'innodb')) {
                throw new RuntimeException('000008 requires InnoDB on all related tables.');
            }
        }
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return collect(Schema::getForeignKeys($table))->contains(fn (array $foreign) => ($foreign['name'] ?? null) === $name);
    }
};
