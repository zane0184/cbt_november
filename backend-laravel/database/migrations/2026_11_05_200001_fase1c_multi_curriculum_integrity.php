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
            if (!$this->hasIndex('multi_curriculums', 'uniq_m_curr_id_tenant', ['id', 'tenant_id'])) {
                Schema::table('multi_curriculums', function (Blueprint $table) {
                    $table->unique(['id', 'tenant_id'], 'uniq_m_curr_id_tenant');
                });
            }
        }

        // 2. multi_curriculum_versions composite FK and unique indexes with RESTRICT on delete for master integrity
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_m_ver_id_tenant ON multi_curriculum_versions (id, tenant_id);");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_m_ver_id_curr ON multi_curriculum_versions (id, multi_curriculum_id);");
        } else {
            if (!$this->hasIndex('multi_curriculum_versions', 'uniq_m_ver_id_tenant', ['id', 'tenant_id'])) {
                Schema::table('multi_curriculum_versions', function (Blueprint $table) {
                    $table->unique(['id', 'tenant_id'], 'uniq_m_ver_id_tenant');
                });
            }
            if (!$this->hasIndex('multi_curriculum_versions', 'uniq_m_ver_id_curr', ['id', 'multi_curriculum_id'])) {
                Schema::table('multi_curriculum_versions', function (Blueprint $table) {
                    $table->unique(['id', 'multi_curriculum_id'], 'uniq_m_ver_id_curr');
                });
            }
            if (!$this->hasForeignKey('multi_curriculum_versions', 'fk_m_ver_curr_tenant', ['multi_curriculum_id', 'tenant_id'], 'multi_curriculums', ['id', 'tenant_id'], 'RESTRICT')) {
                Schema::table('multi_curriculum_versions', function (Blueprint $table) {
                    $table->foreign(['multi_curriculum_id', 'tenant_id'], 'fk_m_ver_curr_tenant')
                        ->references(['id', 'tenant_id'])
                        ->on('multi_curriculums')
                        ->onDelete('RESTRICT');
                });
            }
        }

        // 3. multi_curriculum_structures composite FKs and unique indexes
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_m_struct_id_ver ON multi_curriculum_structures (id, multi_curriculum_version_id);");
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS uniq_m_struct_id_ver_t ON multi_curriculum_structures (id, multi_curriculum_version_id, tenant_id);");
        } else {
            if (!$this->hasIndex('multi_curriculum_structures', 'uniq_m_struct_id_ver', ['id', 'multi_curriculum_version_id'])) {
                Schema::table('multi_curriculum_structures', function (Blueprint $table) {
                    $table->unique(['id', 'multi_curriculum_version_id'], 'uniq_m_struct_id_ver');
                });
            }
            if (!$this->hasIndex('multi_curriculum_structures', 'uniq_m_struct_id_ver_t', ['id', 'multi_curriculum_version_id', 'tenant_id'])) {
                Schema::table('multi_curriculum_structures', function (Blueprint $table) {
                    $table->unique(['id', 'multi_curriculum_version_id', 'tenant_id'], 'uniq_m_struct_id_ver_t');
                });
            }
            if (!$this->hasForeignKey('multi_curriculum_structures', 'fk_m_struct_ver_tenant', ['multi_curriculum_version_id', 'tenant_id'], 'multi_curriculum_versions', ['id', 'tenant_id'], 'RESTRICT')) {
                Schema::table('multi_curriculum_structures', function (Blueprint $table) {
                    $table->foreign(['multi_curriculum_version_id', 'tenant_id'], 'fk_m_struct_ver_tenant')
                        ->references(['id', 'tenant_id'])
                        ->on('multi_curriculum_versions')
                        ->onDelete('RESTRICT');
                });
            }
            if (!$this->hasForeignKey('multi_curriculum_structures', 'fk_m_struct_parent_ver', ['parent_id', 'multi_curriculum_version_id'], 'multi_curriculum_structures', ['id', 'multi_curriculum_version_id'], 'RESTRICT')) {
                Schema::table('multi_curriculum_structures', function (Blueprint $table) {
                    $table->foreign(['parent_id', 'multi_curriculum_version_id'], 'fk_m_struct_parent_ver')
                        ->references(['id', 'multi_curriculum_version_id'])
                        ->on('multi_curriculum_structures')
                        ->onDelete('RESTRICT');
                });
            }
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
            if (!$this->hasIndex('multi_learning_outcomes', 'uniq_mlo_id_ver', ['id', 'multi_curriculum_version_id'])) {
                Schema::table('multi_learning_outcomes', function (Blueprint $table) {
                    $table->unique(['id', 'multi_curriculum_version_id'], 'uniq_mlo_id_ver');
                });
            }
            if (!$this->hasIndex('multi_learning_outcomes', 'uniq_mlo_id_ver_t', ['id', 'multi_curriculum_version_id', 'tenant_id'])) {
                Schema::table('multi_learning_outcomes', function (Blueprint $table) {
                    $table->unique(['id', 'multi_curriculum_version_id', 'tenant_id'], 'uniq_mlo_id_ver_t');
                });
            }
            if (!$this->hasForeignKey('multi_learning_outcomes', 'fk_mlo_struct_ver_t', ['multi_curriculum_structure_id', 'multi_curriculum_version_id', 'tenant_id'], 'multi_curriculum_structures', ['id', 'multi_curriculum_version_id', 'tenant_id'], 'RESTRICT')) {
                Schema::table('multi_learning_outcomes', function (Blueprint $table) {
                    $table->foreign(['multi_curriculum_structure_id', 'multi_curriculum_version_id', 'tenant_id'], 'fk_mlo_struct_ver_t')
                        ->references(['id', 'multi_curriculum_version_id', 'tenant_id'])
                        ->on('multi_curriculum_structures')
                        ->onDelete('RESTRICT');
                });
            }
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
            if ($this->hasForeignKey('question_multi_curriculum_mappings', 'qmcm_lo_tenant_fk')) {
                Schema::table('question_multi_curriculum_mappings', function (Blueprint $table) {
                    $table->dropForeign('qmcm_lo_tenant_fk');
                });
            }

            if (!Schema::hasColumn('question_multi_curriculum_mappings', 'curriculum_owner_id')) {
                Schema::table('question_multi_curriculum_mappings', function (Blueprint $table) {
                    $table->uuid('curriculum_owner_id')->nullable()->after('multi_learning_outcome_id');
                });
            }

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

            if (!$this->hasForeignKey('question_multi_curriculum_mappings', 'fk_qmcm_lo_ver', ['multi_learning_outcome_id', 'multi_curriculum_version_id'], 'multi_learning_outcomes', ['id', 'multi_curriculum_version_id'], 'CASCADE')) {
                Schema::table('question_multi_curriculum_mappings', function (Blueprint $table) {
                    $table->foreign(['multi_learning_outcome_id', 'multi_curriculum_version_id'], 'fk_qmcm_lo_ver')
                        ->references(['id', 'multi_curriculum_version_id'])
                        ->on('multi_learning_outcomes')
                        ->onDelete('cascade');
                });
            }
            if (!$this->hasForeignKey('question_multi_curriculum_mappings', 'fk_qmcm_lo_owner', ['multi_learning_outcome_id', 'curriculum_owner_id'], 'multi_learning_outcomes', ['id', 'tenant_id'], 'CASCADE')) {
                Schema::table('question_multi_curriculum_mappings', function (Blueprint $table) {
                    $table->foreign(['multi_learning_outcome_id', 'curriculum_owner_id'], 'fk_qmcm_lo_owner')
                        ->references(['id', 'tenant_id'])
                        ->on('multi_learning_outcomes')
                        ->onDelete('cascade');
                });
            }
            if (!$this->hasForeignKey('question_multi_curriculum_mappings', 'fk_qmcm_reg_triplet', ['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'tenant_curriculum_registrations', ['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'CASCADE')) {
                Schema::table('question_multi_curriculum_mappings', function (Blueprint $table) {
                    $table->foreign(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'fk_qmcm_reg_triplet')
                        ->references(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                        ->on('tenant_curriculum_registrations')
                        ->onDelete('cascade');
                });
            }
        }
    }

    private function hasIndex(string $table, string $name, array $expectedColumns): bool
    {
        $rows = DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $name)
            ->orderBy('seq_in_index')
            ->selectRaw('COLUMN_NAME AS column_name, NON_UNIQUE AS non_unique')
            ->get();

        if ($rows->isEmpty()) {
            return false;
        }

        $actualColumns = $rows->pluck('column_name')->all();
        $isUnique = $rows->every(fn ($row) => (int) $row->non_unique === 0);
        if ($actualColumns !== $expectedColumns || !$isUnique) {
            throw new \RuntimeException("Fase1C found an incompatible existing index {$table}.{$name}; no DDL was applied for this index.");
        }

        return true;
    }

    private function hasForeignKey(
        string $table,
        string $name,
        ?array $expectedColumns = null,
        ?string $referencedTable = null,
        ?array $referencedColumns = null,
        ?string $deleteRule = null
    ): bool {
        $rows = DB::table('information_schema.key_column_usage')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $name)
            ->orderBy('ordinal_position')
            ->selectRaw('COLUMN_NAME AS column_name, REFERENCED_TABLE_NAME AS referenced_table_name, REFERENCED_COLUMN_NAME AS referenced_column_name')
            ->get();

        if ($rows->isEmpty()) {
            return false;
        }

        if ($expectedColumns !== null) {
            $actualColumns = $rows->pluck('column_name')->all();
            $actualReferencedTables = $rows->pluck('referenced_table_name')->unique()->values()->all();
            $actualReferencedColumns = $rows->pluck('referenced_column_name')->all();
            $actualDeleteRule = DB::table('information_schema.referential_constraints')
                ->where('constraint_schema', DB::getDatabaseName())
                ->where('table_name', $table)
                ->where('constraint_name', $name)
                ->value('delete_rule');

            if (
                $actualColumns !== $expectedColumns
                || $actualReferencedTables !== [$referencedTable]
                || $actualReferencedColumns !== $referencedColumns
                || strtoupper((string) $actualDeleteRule) !== strtoupper((string) $deleteRule)
            ) {
                throw new \RuntimeException("Fase1C found an incompatible existing foreign key {$table}.{$name}; no DDL was applied for this foreign key.");
            }
        }

        return true;
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
