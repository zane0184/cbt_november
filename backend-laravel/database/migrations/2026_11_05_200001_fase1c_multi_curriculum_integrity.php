<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. multi_curriculums parent unique index for composite FKs
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_m_curr_id_tenant ON multi_curriculums (id, tenant_id);");
        } else {
            Schema::table('multi_curriculums', function (Blueprint $table) {
                $table->unique(['id', 'tenant_id'], 'uniq_m_curr_id_tenant');
            });
        }

        // 2. multi_curriculum_versions composite FK and unique indexes with RESTRICT on delete for master integrity
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_m_ver_id_tenant ON multi_curriculum_versions (id, tenant_id);");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_m_ver_id_curr ON multi_curriculum_versions (id, multi_curriculum_id);");
        } else {
            Schema::table('multi_curriculum_versions', function (Blueprint $table) {
                $table->unique(['id', 'tenant_id'], 'uniq_m_ver_id_tenant');
                $table->unique(['id', 'multi_curriculum_id'], 'uniq_m_ver_id_curr');
                $table->foreign(['multi_curriculum_id', 'tenant_id'], 'fk_m_ver_curr_tenant')
                    ->references(['id', 'tenant_id'])
                    ->on('multi_curriculums')
                    ->onDelete('RESTRICT');
            });
        }

        // 3. multi_curriculum_structures composite FKs and unique indexes
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_m_struct_id_ver ON multi_curriculum_structures (id, multi_curriculum_version_id);");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_m_struct_id_ver_t ON multi_curriculum_structures (id, multi_curriculum_version_id, tenant_id);");
        } else {
            Schema::table('multi_curriculum_structures', function (Blueprint $table) {
                $table->unique(['id', 'multi_curriculum_version_id'], 'uniq_m_struct_id_ver');
                $table->unique(['id', 'multi_curriculum_version_id', 'tenant_id'], 'uniq_m_struct_id_ver_t');

                $table->foreign(['multi_curriculum_version_id', 'tenant_id'], 'fk_m_struct_ver_tenant')
                    ->references(['id', 'tenant_id'])
                    ->on('multi_curriculum_versions')
                    ->onDelete('RESTRICT');

                $table->foreign(['parent_id', 'multi_curriculum_version_id'], 'fk_m_struct_parent_ver')
                    ->references(['id', 'multi_curriculum_version_id'])
                    ->on('multi_curriculum_structures')
                    ->onDelete('RESTRICT');
            });
        }

        // 4. multi_learning_outcomes additions and composite FKs
        if (!Schema::hasColumn('multi_learning_outcomes', 'multi_curriculum_version_id')) {
            Schema::table('multi_learning_outcomes', function (Blueprint $table) {
                $table->uuid('multi_curriculum_version_id')->nullable()->after('multi_curriculum_structure_id');
            });
        }

        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_mlo_id_ver ON multi_learning_outcomes (id, multi_curriculum_version_id);");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_mlo_id_ver_t ON multi_learning_outcomes (id, multi_curriculum_version_id, tenant_id);");
        } else {
            Schema::table('multi_learning_outcomes', function (Blueprint $table) {
                $table->unique(['id', 'multi_curriculum_version_id'], 'uniq_mlo_id_ver');
                $table->unique(['id', 'multi_curriculum_version_id', 'tenant_id'], 'uniq_mlo_id_ver_t');

                $table->foreign(['multi_curriculum_structure_id', 'multi_curriculum_version_id', 'tenant_id'], 'fk_mlo_struct_ver_t')
                    ->references(['id', 'multi_curriculum_version_id', 'tenant_id'])
                    ->on('multi_curriculum_structures')
                    ->onDelete('RESTRICT');
            });
        }

        // Safe Reconciliation for multi_learning_outcomes (backfill version ONLY from proven structure relation)
        DB::statement("
            UPDATE multi_learning_outcomes
            SET multi_curriculum_version_id = (
                SELECT multi_curriculum_version_id
                FROM multi_curriculum_structures
                WHERE multi_curriculum_structures.id = multi_learning_outcomes.multi_curriculum_structure_id
            )
            WHERE multi_curriculum_version_id IS NULL AND multi_curriculum_structure_id IS NOT NULL
        ");

        // Trigger to auto-sync multi_curriculum_version_id on multi_learning_outcomes when omitted in raw SQL
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER IF NOT EXISTS trg_mlo_sync_ver AFTER INSERT ON multi_learning_outcomes
                FOR EACH ROW WHEN NEW.multi_curriculum_version_id IS NULL AND NEW.multi_curriculum_structure_id IS NOT NULL
                BEGIN
                    UPDATE multi_learning_outcomes
                    SET multi_curriculum_version_id = (
                        SELECT multi_curriculum_version_id FROM multi_curriculum_structures WHERE id = NEW.multi_curriculum_structure_id
                    )
                    WHERE id = NEW.id;
                END;
            ");
        }

        // 5. Create tenant_curriculum_registrations table
        if (!Schema::hasTable('tenant_curriculum_registrations')) {
            Schema::create('tenant_curriculum_registrations', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
                $table->foreignUuid('multi_curriculum_version_id')->constrained('multi_curriculum_versions', 'id', 'fk_tcr_mcv_id')->onDelete('RESTRICT');
                $table->foreignUuid('curriculum_owner_id')->constrained('tenants')->onDelete('RESTRICT');
                $table->string('status')->default('ACTIVE');
                $table->timestamp('registered_at')->useCurrent();
                $table->timestamps();

                $table->unique(['tenant_id', 'multi_curriculum_version_id'], 'uniq_tenant_curr_ver_reg');
                $table->unique(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'uniq_reg_triplet');
                $table->unique(['id', 'tenant_id'], 'uniq_tcr_id_tenant');

                $table->foreign(['multi_curriculum_version_id', 'curriculum_owner_id'], 'fk_tcr_version_owner')
                    ->references(['id', 'tenant_id'])
                    ->on('multi_curriculum_versions')
                    ->onDelete('RESTRICT');
            });
        }

        // 6. Non-destructive table alteration for question_multi_curriculum_mappings
        if (DB::getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            if (!Schema::hasColumn('question_multi_curriculum_mappings', 'curriculum_owner_id')) {
                Schema::create('qmcm_temp', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->uuid('tenant_id');
                    $table->uuid('question_id');
                    $table->uuid('multi_curriculum_version_id');
                    $table->uuid('multi_learning_outcome_id');
                    $table->uuid('curriculum_owner_id')->nullable();
                    $table->decimal('weight', 5, 2)->default(1.00);
                    $table->timestamps();

                    $table->foreign('tenant_id', 'qmcm_tenant_fk')->references('id')->on('tenants')->onDelete('cascade');
                    $table->foreign(['question_id', 'tenant_id'], 'qmcm_q_tenant_fk')->references(['id', 'tenant_id'])->on('questions')->onDelete('cascade');
                    $table->foreign('multi_curriculum_version_id', 'qmcm_ver_fk')->references('id')->on('multi_curriculum_versions')->onDelete('RESTRICT');

                    $table->foreign(['multi_learning_outcome_id', 'multi_curriculum_version_id'], 'fk_qmcm_lo_ver')
                        ->references(['id', 'multi_curriculum_version_id'])
                        ->on('multi_learning_outcomes')
                        ->onDelete('cascade');

                    $table->foreign(['multi_learning_outcome_id', 'curriculum_owner_id'], 'fk_qmcm_lo_owner')
                        ->references(['id', 'tenant_id'])
                        ->on('multi_learning_outcomes')
                        ->onDelete('cascade');

                    $table->foreign(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'fk_qmcm_reg_triplet')
                        ->references(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                        ->on('tenant_curriculum_registrations')
                        ->onDelete('cascade');
                });

                // Copy existing data with proven reconciliation ONLY (no fallback assumptions)
                DB::statement("
                    INSERT INTO qmcm_temp (id, tenant_id, question_id, multi_curriculum_version_id, multi_learning_outcome_id, curriculum_owner_id, weight, created_at, updated_at)
                    SELECT
                        q.id,
                        q.tenant_id,
                        q.question_id,
                        q.multi_curriculum_version_id,
                        q.multi_learning_outcome_id,
                        (SELECT tenant_id FROM multi_learning_outcomes WHERE id = q.multi_learning_outcome_id AND tenant_id IS NOT NULL),
                        q.weight,
                        q.created_at,
                        q.updated_at
                    FROM question_multi_curriculum_mappings q
                ");

                Schema::dropIfExists('question_multi_curriculum_mappings');
                Schema::rename('qmcm_temp', 'question_multi_curriculum_mappings');
            }

            Schema::enableForeignKeyConstraints();
        } else {
            Schema::table('question_multi_curriculum_mappings', function (Blueprint $table) {
                $table->dropForeign('qmcm_lo_tenant_fk');

                if (!Schema::hasColumn('question_multi_curriculum_mappings', 'curriculum_owner_id')) {
                    $table->uuid('curriculum_owner_id')->nullable()->after('multi_learning_outcome_id');
                }
            });

            DB::statement("
                UPDATE question_multi_curriculum_mappings
                SET curriculum_owner_id = (
                    SELECT tenant_id
                    FROM multi_learning_outcomes
                    WHERE multi_learning_outcomes.id = question_multi_curriculum_mappings.multi_learning_outcome_id
                      AND multi_learning_outcomes.tenant_id IS NOT NULL
                )
                WHERE curriculum_owner_id IS NULL AND multi_learning_outcome_id IS NOT NULL
            ");

            Schema::table('question_multi_curriculum_mappings', function (Blueprint $table) {
                $table->foreign(['multi_learning_outcome_id', 'multi_curriculum_version_id'], 'fk_qmcm_lo_ver')
                    ->references(['id', 'multi_curriculum_version_id'])
                    ->on('multi_learning_outcomes')
                    ->onDelete('cascade');

                $table->foreign(['multi_learning_outcome_id', 'curriculum_owner_id'], 'fk_qmcm_lo_owner')
                    ->references(['id', 'tenant_id'])
                    ->on('multi_learning_outcomes')
                    ->onDelete('cascade');

                $table->foreign(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'fk_qmcm_reg_triplet')
                    ->references(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                    ->on('tenant_curriculum_registrations')
                    ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // This migration performs data reconciliation and engine-specific table
        // reconstruction. A generic rollback would discard reconciled values and
        // cannot safely restore the exact pre-migration state on every supported
        // engine. Fail closed before issuing any DDL; use a reviewed forward-fix
        // or restore a verified backup instead.
        throw new \RuntimeException(
            'Fase1C migration is intentionally irreversible. No schema changes were made. '
            .'Use a reviewed forward migration or restore a verified backup.'
        );
    }
};
