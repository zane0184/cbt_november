<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->preflight();

        Schema::table('tenant_curriculum_registrations', function (Blueprint $table): void {
            $table->uuid('academic_year_id')->after('tenant_id');
        });
        Schema::table('question_multi_curriculum_mappings', function (Blueprint $table): void {
            $table->uuid('academic_year_id')->after('tenant_id');
        });

        Schema::table('question_multi_curriculum_mappings', function (Blueprint $table): void {
            $table->dropForeign('fk_qmcm_reg_triplet');
            $table->dropUnique('uniq_q_m_outcome');
            $table->uuid('curriculum_owner_id')->nullable(false)->change();
        });

        Schema::table('tenant_curriculum_registrations', function (Blueprint $table): void {
            $table->dropUnique('uniq_tenant_curr_ver_reg');
            $table->dropUnique('uniq_reg_triplet');
            $table->index(['tenant_id', 'academic_year_id', 'status'], 'idx_tcr_tenant_year_status');
            $table->unique(['tenant_id', 'academic_year_id', 'multi_curriculum_version_id'], 'uniq_tcr_tenant_year_version');
            $table->unique(['tenant_id', 'academic_year_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'uniq_tcr_year_triplet');
            $table->foreign(['academic_year_id', 'tenant_id'], 'fk_tcr_academic_year_tenant')
                ->references(['id', 'tenant_id'])->on('academic_years')->restrictOnDelete();
        });

        Schema::table('question_multi_curriculum_mappings', function (Blueprint $table): void {
            $table->index(['tenant_id', 'academic_year_id', 'multi_curriculum_version_id'], 'idx_qmcm_tenant_year_version');
            $table->unique(['question_id', 'academic_year_id', 'multi_learning_outcome_id'], 'uniq_qmcm_question_year_outcome');
            $table->foreign(['tenant_id', 'academic_year_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'fk_qmcm_registration_year')
                ->references(['tenant_id', 'academic_year_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                ->on('tenant_curriculum_registrations')
                ->cascadeOnDelete();
        });
    }

    private function preflight(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('0010 supports only MySQL 8+ and SQLite test connections.');
        }

        foreach (['tenant_curriculum_registrations', 'question_multi_curriculum_mappings', 'academic_years'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("0010 prerequisite table missing: {$table}.");
            }
        }

        if (!Schema::hasIndex('academic_years', 'academic_years_id_tenant_unique')) {
            throw new RuntimeException('0010 requires the tenant-aware academic_years(id, tenant_id) unique index from migration 000008.');
        }
        if (Schema::hasColumn('tenant_curriculum_registrations', 'academic_year_id')
            || Schema::hasColumn('question_multi_curriculum_mappings', 'academic_year_id')) {
            throw new RuntimeException('0010 partial state: academic_year_id already exists. Inspect schema before retrying.');
        }
        if (!Schema::hasIndex('tenant_curriculum_registrations', 'uniq_tenant_curr_ver_reg')
            || !Schema::hasIndex('tenant_curriculum_registrations', 'uniq_reg_triplet')
            || !Schema::hasForeignKey('question_multi_curriculum_mappings', 'fk_qmcm_reg_triplet')
            || !Schema::hasIndex('question_multi_curriculum_mappings', 'uniq_q_m_outcome')) {
            throw new RuntimeException('0010 expected legacy registration constraints are missing. No DDL executed.');
        }

        $registrations = DB::table('tenant_curriculum_registrations')->count();
        $mappings = DB::table('question_multi_curriculum_mappings')->count();
        if ($registrations > 0 || $mappings > 0) {
            throw new RuntimeException(
                "0010 blocked before DDL: found {$registrations} curriculum registrations and {$mappings} question mappings without academic-year provenance. Do not guess the year. Prepare an approved row-by-row mapping plan first."
            );
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $tables = DB::select("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('tenant_curriculum_registrations','question_multi_curriculum_mappings','academic_years')");
            if (count($tables) !== 3 || collect($tables)->contains(fn ($row) => strtolower((string) $row->ENGINE) !== 'innodb')) {
                throw new RuntimeException('0010 requires InnoDB on all affected tables.');
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Curriculum registration year scoping is forward-only. Do not remove academic-year provenance or mapping constraints with a generic rollback.'
        );
    }
};
