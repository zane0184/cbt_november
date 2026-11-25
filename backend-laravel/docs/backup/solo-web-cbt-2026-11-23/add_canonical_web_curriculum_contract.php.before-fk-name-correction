<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('curriculum_reference_sources')) {
            Schema::create('curriculum_reference_sources', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('issuer', 190);
                $table->string('document_number', 120);
                $table->string('title', 255);
                $table->string('document_version', 80)->default('1');
                $table->date('published_at')->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->text('source_uri');
                $table->string('archive_path', 500)->nullable();
                $table->char('checksum_sha256', 64)->nullable();
                $table->string('verification_status', 40)->default('PENDING');
                $table->timestamp('verified_at')->nullable();
                $table->uuid('verified_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(
                    ['tenant_id', 'issuer', 'document_number', 'document_version'],
                    'curr_ref_source_identity_unique'
                );
                $table->index(['tenant_id', 'verification_status'], 'curr_ref_source_status_idx');
            });
        }

        if (!Schema::hasTable('assessment_taxonomy_options')) {
            Schema::create('assessment_taxonomy_options', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('dimension', 40);
                $table->string('code', 100);
                $table->string('label', 190);
                $table->text('description')->nullable();
                $table->string('version_code', 80)->default('1');
                $table->foreignUuid('source_id')->nullable()
                    ->constrained('curriculum_reference_sources')->restrictOnDelete();
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->string('status', 30)->default('DRAFT');
                $table->boolean('is_selectable')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->uuid('supersedes_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(
                    ['tenant_id', 'dimension', 'code', 'version_code'],
                    'assessment_taxonomy_identity_unique'
                );
                $table->index(['tenant_id', 'dimension', 'status', 'is_selectable'], 'assessment_taxonomy_select_idx');
                $table->foreign('supersedes_id', 'assessment_taxonomy_supersedes_fk')
                    ->references('id')->on('assessment_taxonomy_options')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('material_topics')) {
            Schema::create('material_topics', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('material_id')->constrained('materials')->restrictOnDelete();
                $table->foreignUuid('parent_id')->nullable()
                    ->constrained('material_topics')->nullOnDelete();
                $table->string('code', 100)->nullable();
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('version_code', 80)->default('1');
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->string('status', 30)->default('DRAFT');
                $table->uuid('created_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(
                    ['tenant_id', 'material_id', 'code', 'version_code'],
                    'material_topic_identity_unique'
                );
                $table->index(['tenant_id', 'material_id', 'status'], 'material_topic_scope_idx');
            });
        }

        if (!Schema::hasTable('question_version_curriculum_mappings')) {
            Schema::create('question_version_curriculum_mappings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('question_version_id')->constrained('question_versions')->cascadeOnDelete();
                $table->foreignUuid('multi_curriculum_version_id')
                    ->constrained('multi_curriculum_versions')->restrictOnDelete();
                $table->foreignUuid('multi_learning_outcome_id')
                    ->constrained('multi_learning_outcomes')->restrictOnDelete();
                $table->foreignUuid('material_id')->nullable()
                    ->constrained('materials')->nullOnDelete();
                $table->foreignUuid('material_topic_id')->nullable()
                    ->constrained('material_topics')->nullOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()
                    ->constrained('academic_years')->nullOnDelete();
                $table->foreignUuid('semester_id')->nullable()
                    ->constrained('semesters')->nullOnDelete();
                $table->string('mapping_role', 40)->default('LEARNING_OUTCOME');
                $table->decimal('weight', 6, 3)->default(1);
                $table->boolean('is_primary')->default(false);
                $table->uuid('created_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(
                    [
                        'tenant_id',
                        'question_version_id',
                        'multi_curriculum_version_id',
                        'multi_learning_outcome_id',
                        'academic_year_id',
                        'semester_id',
                    ],
                    'qv_curriculum_mapping_identity_unique'
                );
                $table->index(['tenant_id', 'question_version_id'], 'qv_curriculum_mapping_question_idx');
                $table->index(['tenant_id', 'academic_year_id', 'semester_id'], 'qv_curriculum_mapping_period_idx');
            });
        }

        if (!Schema::hasTable('question_version_structure_mappings')) {
            Schema::create('question_version_structure_mappings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignUuid('question_version_id')->constrained('question_versions')->cascadeOnDelete();
                $table->foreignUuid('multi_curriculum_version_id')
                    ->constrained('multi_curriculum_versions')->restrictOnDelete();
                $table->foreignUuid('multi_curriculum_structure_id')
                    ->constrained('multi_curriculum_structures')->restrictOnDelete();
                $table->foreignUuid('academic_year_id')->nullable()
                    ->constrained('academic_years')->nullOnDelete();
                $table->foreignUuid('semester_id')->nullable()
                    ->constrained('semesters')->nullOnDelete();
                $table->string('mapping_role', 40);
                $table->decimal('weight', 6, 3)->default(1);
                $table->uuid('created_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(
                    [
                        'tenant_id',
                        'question_version_id',
                        'multi_curriculum_version_id',
                        'multi_curriculum_structure_id',
                        'mapping_role',
                        'academic_year_id',
                        'semester_id',
                    ],
                    'qv_structure_mapping_identity_unique'
                );
                $table->index(['tenant_id', 'question_version_id'], 'qv_structure_mapping_question_idx');
            });
        }

        $this->addProvenanceColumns('multi_curriculum_versions');
        $this->addProvenanceColumns('multi_curriculum_structures');
        $this->addProvenanceColumns('multi_learning_outcomes');

        if (Schema::hasTable('assessments')) {
            Schema::table('assessments', function (Blueprint $table): void {
                if (!Schema::hasColumn('assessments', 'assessment_function_option_id')) {
                    $table->foreignUuid('assessment_function_option_id')->nullable()
                        ->constrained('assessment_taxonomy_options')->nullOnDelete();
                }
                if (!Schema::hasColumn('assessments', 'assessment_scope_option_id')) {
                    $table->foreignUuid('assessment_scope_option_id')->nullable()
                        ->constrained('assessment_taxonomy_options')->nullOnDelete();
                }
                if (!Schema::hasColumn('assessments', 'instrument_type_option_id')) {
                    $table->foreignUuid('instrument_type_option_id')->nullable()
                        ->constrained('assessment_taxonomy_options')->nullOnDelete();
                }
                if (!Schema::hasColumn('assessments', 'delivery_mode_option_id')) {
                    $table->foreignUuid('delivery_mode_option_id')->nullable()
                        ->constrained('assessment_taxonomy_options')->nullOnDelete();
                }
                if (!Schema::hasColumn('assessments', 'activity_name')) {
                    $table->string('activity_name', 190)->nullable();
                }
                if (!Schema::hasColumn('assessments', 'curriculum_version_id')) {
                    $table->foreignUuid('curriculum_version_id')->nullable()
                        ->constrained('multi_curriculum_versions')->nullOnDelete();
                }
                if (!Schema::hasColumn('assessments', 'curriculum_context_snapshot')) {
                    $table->json('curriculum_context_snapshot')->nullable();
                }
            });
        }

        if (Schema::hasTable('exams') && !Schema::hasColumn('exams', 'assessment_context_snapshot')) {
            Schema::table('exams', function (Blueprint $table): void {
                $table->json('assessment_context_snapshot')->nullable();
            });
        }

        if (Schema::hasTable('exam_question_snapshots') && !Schema::hasColumn('exam_question_snapshots', 'curriculum_context_snapshot')) {
            Schema::table('exam_question_snapshots', function (Blueprint $table): void {
                $table->json('curriculum_context_snapshot')->nullable();
            });
        }
    }

    private function addProvenanceColumns(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (!Schema::hasColumn($tableName, 'source_id')) {
                $table->foreignUuid('source_id')->nullable()
                    ->constrained('curriculum_reference_sources')->restrictOnDelete();
            }
            if (!Schema::hasColumn($tableName, 'source_locator')) {
                $table->string('source_locator', 500)->nullable();
            }
            if (!Schema::hasColumn($tableName, 'effective_from')) {
                $table->date('effective_from')->nullable();
            }
            if (!Schema::hasColumn($tableName, 'effective_until')) {
                $table->date('effective_until')->nullable();
            }
            if (!Schema::hasColumn($tableName, 'verification_status')) {
                $table->string('verification_status', 40)->default('PENDING');
            }
            if (!Schema::hasColumn($tableName, 'verified_at')) {
                $table->timestamp('verified_at')->nullable();
            }
            if (!Schema::hasColumn($tableName, 'source_checksum')) {
                $table->char('source_checksum', 64)->nullable();
            }
            if (!Schema::hasColumn($tableName, 'metadata')) {
                $table->json('metadata')->nullable();
            }
        });
    }

    public function down(): void
    {
        foreach (['assessments', 'exams', 'exam_question_snapshots'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if ($tableName === 'assessments') {
                    foreach ([
                        'assessment_function_option_id',
                        'assessment_scope_option_id',
                        'instrument_type_option_id',
                        'delivery_mode_option_id',
                        'curriculum_version_id',
                    ] as $column) {
                        if (Schema::hasColumn($tableName, $column)) {
                            $table->dropConstrainedForeignId($column);
                        }
                    }
                    foreach (['activity_name', 'curriculum_context_snapshot'] as $column) {
                        if (Schema::hasColumn($tableName, $column)) {
                            $table->dropColumn($column);
                        }
                    }
                } elseif ($tableName === 'exams' && Schema::hasColumn($tableName, 'assessment_context_snapshot')) {
                    $table->dropColumn('assessment_context_snapshot');
                } elseif ($tableName === 'exam_question_snapshots' && Schema::hasColumn($tableName, 'curriculum_context_snapshot')) {
                    $table->dropColumn('curriculum_context_snapshot');
                }
            });
        }

        foreach (['multi_curriculum_versions', 'multi_curriculum_structures', 'multi_learning_outcomes'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
                if (Schema::hasColumn($tableName, 'source_id')) {
                    $table->dropConstrainedForeignId('source_id');
                }
                foreach ([
                    'source_locator',
                    'effective_from',
                    'effective_until',
                    'verification_status',
                    'verified_at',
                    'source_checksum',
                    'metadata',
                ] as $column) {
                    if (Schema::hasColumn($tableName, $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        Schema::dropIfExists('question_version_structure_mappings');
        Schema::dropIfExists('question_version_curriculum_mappings');
        Schema::dropIfExists('material_topics');
        Schema::dropIfExists('assessment_taxonomy_options');
        Schema::dropIfExists('curriculum_reference_sources');
    }
};
