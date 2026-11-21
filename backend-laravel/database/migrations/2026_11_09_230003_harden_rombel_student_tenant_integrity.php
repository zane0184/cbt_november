<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL DDL is not fully transactional. Run all data checks before the
        // first schema mutation so corrupt legacy rows fail before DDL begins.
        $this->assertDriverAndTables();
        if (Schema::hasColumn('rombel_student', 'tenant_id')) throw new RuntimeException('230003 partial state: rombel_student.tenant_id already exists. No DDL executed.');
        foreach ([
            ['users', 'users_id_tenant_unique'],
            ['rombels', 'rombels_id_tenant_unique'],
        ] as [$table, $index]) if (! Schema::hasIndex($table, $index)) throw new RuntimeException("230003 prerequisite candidate key missing: {$table}.{$index}.");
        foreach ([
            ['student_profiles', 'student_profiles_user_id_foreign'],
            ['rombel_student', 'rombel_student_rombel_id_foreign'],
            ['rombel_student', 'rombel_student_student_id_foreign'],
        ] as [$table, $foreign]) if (! $this->hasForeignKey($table, $foreign)) throw new RuntimeException("230003 legacy foreign key missing or renamed: {$table}.{$foreign}.");
        foreach ([
            ['student_profiles', 'student_profiles_id_tenant_unique'],
            ['rombel_student', 'rombel_student_tenant_lookup_idx'],
        ] as [$table, $index]) if (Schema::hasIndex($table, $index)) throw new RuntimeException("230003 partial state: unexpected index {$table}.{$index} already exists.");
        foreach ([['student_profiles', 'student_profiles_user_tenant_fk'], ['rombel_student', 'rombel_student_rombel_tenant_fk'], ['rombel_student', 'rombel_student_student_tenant_fk']] as [$table, $foreign]) {
            if ($this->hasForeignKey($table, $foreign)) throw new RuntimeException("230003 partial state: unexpected composite FK {$table}.{$foreign} already exists.");
        }

        $orphanRombels = DB::table('rombel_student as rs')
            ->leftJoin('rombels as r', 'r.id', '=', 'rs.rombel_id')
            ->whereNull('r.id')
            ->count();

        $orphanProfiles = DB::table('rombel_student as rs')
            ->leftJoin('student_profiles as sp', 'sp.id', '=', 'rs.student_id')
            ->whereNull('sp.id')
            ->count();

        $crossTenantMemberships = DB::table('rombel_student as rs')
            ->join('rombels as r', 'r.id', '=', 'rs.rombel_id')
            ->join('student_profiles as sp', 'sp.id', '=', 'rs.student_id')
            ->whereColumn('r.tenant_id', '<>', 'sp.tenant_id')
            ->count();

        $crossTenantStudentUsers = DB::table('student_profiles as sp')
            ->join('users as u', 'u.id', '=', 'sp.user_id')
            ->whereColumn('sp.tenant_id', '<>', 'u.tenant_id')
            ->count();

        if ($orphanRombels > 0 || $orphanProfiles > 0 || $crossTenantMemberships > 0 || $crossTenantStudentUsers > 0) {
            throw new RuntimeException(sprintf(
                'rombel_student tenant preflight failed: orphan_rombels=%d, orphan_student_profiles=%d, cross_tenant_memberships=%d, cross_tenant_student_users=%d. Correct legacy data and rerun staging preflight; no automatic deletion is performed.',
                $orphanRombels,
                $orphanProfiles,
                $crossTenantMemberships,
                $crossTenantStudentUsers
            ));
        }

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->unique(['id', 'tenant_id'], 'student_profiles_id_tenant_unique');
            $table->dropForeign(['user_id']);
            $table->foreign(['user_id', 'tenant_id'], 'student_profiles_user_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::table('rombel_student', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
        });

        // Correlated scalar UPDATE is accepted by both MySQL 8 and SQLite test runtime.
        DB::statement(<<<'SQL'
            UPDATE rombel_student
            SET tenant_id = (
                SELECT r.tenant_id
                FROM rombels AS r
                WHERE r.id = rombel_student.rombel_id
            )
            WHERE tenant_id IS NULL
        SQL);

        $unbackfilledRows = DB::table('rombel_student')->whereNull('tenant_id')->count();
        if ($unbackfilledRows > 0) {
            throw new RuntimeException("rombel_student tenant backfill left {$unbackfilledRows} null rows; inspect partial migration state before retrying.");
        }

        Schema::table('rombel_student', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable(false)->change();
            $table->dropForeign(['rombel_id']);
            $table->dropForeign(['student_id']);
            $table->index(['tenant_id', 'rombel_id', 'student_id'], 'rombel_student_tenant_lookup_idx');

            $table->foreign(['rombel_id', 'tenant_id'], 'rombel_student_rombel_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('rombels')
                ->cascadeOnDelete();
            $table->foreign(['student_id', 'tenant_id'], 'rombel_student_student_tenant_fk')
                ->references(['id', 'tenant_id'])
                ->on('student_profiles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        $this->assertDriverAndTables();
        if (! Schema::hasColumn('rombel_student', 'tenant_id')) throw new RuntimeException('230003 rollback blocked: tenant_id is absent.');
        foreach ([
            ['rombel_student', 'rombel_student_rombel_tenant_fk'],
            ['rombel_student', 'rombel_student_student_tenant_fk'],
            ['student_profiles', 'student_profiles_user_tenant_fk'],
        ] as [$table, $foreign]) if (! $this->hasForeignKey($table, $foreign)) throw new RuntimeException("230003 rollback blocked: expected FK {$table}.{$foreign} is missing.");
        foreach ([
            ['rombel_student', 'rombel_student_rombel_id_foreign'],
            ['rombel_student', 'rombel_student_student_id_foreign'],
            ['student_profiles', 'student_profiles_user_id_foreign'],
        ] as [$table, $foreign]) if ($this->hasForeignKey($table, $foreign)) throw new RuntimeException("230003 rollback blocked: legacy FK {$table}.{$foreign} already exists.");
        if (! Schema::hasIndex('student_profiles', 'student_profiles_id_tenant_unique') || ! Schema::hasIndex('rombel_student', 'rombel_student_tenant_lookup_idx')) throw new RuntimeException('230003 rollback blocked: required composite indexes are missing.');
        $crossTenant = DB::table('rombel_student as rs')->join('rombels as r', 'r.id', '=', 'rs.rombel_id')->join('student_profiles as sp', 'sp.id', '=', 'rs.student_id')->whereColumn('rs.tenant_id', '<>', 'r.tenant_id')->orWhereColumn('rs.tenant_id', '<>', 'sp.tenant_id')->count();
        if ($crossTenant > 0) throw new RuntimeException("230003 rollback blocked: {$crossTenant} cross-tenant memberships detected.");

        Schema::table('rombel_student', function (Blueprint $table) {
            $table->dropForeign('rombel_student_rombel_tenant_fk');
            $table->dropForeign('rombel_student_student_tenant_fk');
            $table->dropIndex('rombel_student_tenant_lookup_idx');
            $table->foreign('rombel_id')->references('id')->on('rombels')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('student_profiles')->cascadeOnDelete();
            $table->dropColumn('tenant_id');
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropForeign('student_profiles_user_tenant_fk');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropUnique('student_profiles_id_tenant_unique');
        });
    }

    private function assertDriverAndTables(): void
    {
        $driver = DB::connection()->getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) throw new RuntimeException('230003 supports only MySQL 8+ and SQLite test connections.');
        foreach (['users', 'student_profiles', 'rombels', 'rombel_student'] as $table) if (! Schema::hasTable($table)) throw new RuntimeException("230003 prerequisite table missing: {$table}.");
        foreach ([['users', 'id'], ['users', 'tenant_id'], ['student_profiles', 'id'], ['student_profiles', 'user_id'], ['student_profiles', 'tenant_id'], ['rombels', 'id'], ['rombels', 'tenant_id'], ['rombel_student', 'id'], ['rombel_student', 'rombel_id'], ['rombel_student', 'student_id']] as [$table, $column]) if (! Schema::hasColumn($table, $column)) throw new RuntimeException("230003 prerequisite column missing: {$table}.{$column}.");
        if ($driver === 'mysql') {
            $engines = DB::select("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('users','student_profiles','rombels','rombel_student')");
            if (count($engines) !== 4 || collect($engines)->contains(fn ($row) => strtolower((string) $row->ENGINE) !== 'innodb')) throw new RuntimeException('230003 requires InnoDB on all related tables.');
        }
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        return collect(Schema::getForeignKeys($table))->contains(fn (array $foreign) => ($foreign['name'] ?? null) === $name);
    }

};
