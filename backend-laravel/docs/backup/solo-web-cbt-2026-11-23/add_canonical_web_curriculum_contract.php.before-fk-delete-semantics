<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureParentIndexes();

        if (!Schema::hasTable('curriculum_reference_sources')) {
            Schema::create('curriculum_reference_sources', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
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

                $table->unique(['id', 'tenant_id'], 'crs_id_tenant_uq');
                $table->unique(
                    ['tenant_id', 'issuer', 'document_number', 'document_version'],
                    'crs_identity_uq'
                );
                $table->index(['tenant_id', 'verification_status'], 'crs_status_idx');
                $table->foreign('tenant_id', 'crs_tenant_fk')
                    ->references('id')->on('tenants')->onDelete('RESTRICT');
                $table->foreign('verified_by', 'crs_verifier_fk')
                    ->references('id')->on('users')->onDelete('SET NULL');
            });
        }

        if (!Schema::hasTable('assessment_taxonomy_options')) {
            Schema::create('assessment_taxonomy_options', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->string('dimension', 40);
                $table->string('code', 100);
                $table->string('label', 190);
                $table->text('description')->nullable();
                $table->string('version_code', 80)->default('1');
                $table->uuid('source_id')->nullable();
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->string('status', 30)->default('DRAFT');
                $table->boolean('is_selectable')->default(false);
                $table->boolean('is_official')->default(false);
                $table->boolean('is_editable')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->uuid('supersedes_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['id', 'tenant_id'], 'ato_id_tenant_uq');
                $table->unique(['tenant_id', 'dimension', 'code', 'version_code'], 'ato_identity_uq');
                $table->index(['tenant_id', 'dimension', 'status', 'is_selectable'], 'ato_select_idx');
                $table->foreign('tenant_id', 'ato_tenant_fk')
                    ->references('id')->on('tenants')->onDelete('RESTRICT');
                $table->foreign(['source_id', 'tenant_id'], 'ato_source_owner_fk')
                    ->references(['id', 'tenant_id'])->on('curriculum_reference_sources')->onDelete('RESTRICT');
                $table->foreign(['supersedes_id', 'tenant_id'], 'ato_supersedes_owner_fk')
                    ->references(['id', 'tenant_id'])->on('assessment_taxonomy_options')->onDelete('RESTRICT');
            });
        }

        if (!Schema::hasTable('material_topics')) {
            Schema::create('material_topics', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('material_id');
                $table->uuid('parent_id')->nullable();
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

                $table->unique(['id', 'material_id', 'tenant_id'], 'mt_id_material_tenant_uq');
                $table->unique(['tenant_id', 'material_id', 'code', 'version_code'], 'mt_identity_uq');
                $table->index(['tenant_id', 'material_id', 'status'], 'mt_scope_idx');
                $table->foreign('tenant_id', 'mt_tenant_fk')
                    ->references('id')->on('tenants')->onDelete('RESTRICT');
                $table->foreign(['material_id', 'tenant_id'], 'mt_material_owner_fk')
                    ->references(['id', 'tenant_id'])->on('materials')->onDelete('RESTRICT');
                $table->foreign(['parent_id', 'material_id', 'tenant_id'], 'mt_parent_material_owner_fk')
                    ->references(['id', 'material_id', 'tenant_id'])->on('material_topics')->onDelete('RESTRICT');
                $table->foreign(['created_by', 'tenant_id'], 'mt_creator_owner_fk')
                    ->references(['id', 'tenant_id'])->on('users')->onDelete('SET NULL');
            });
        }

        $this->addCurriculumProvenance('multi_curriculum_versions');
        $this->addCurriculumProvenance('multi_curriculum_structures');
        $this->addCurriculumProvenance('multi_learning_outcomes');
        $this->addMultiOutcomeProtectionColumns();

        $this->addTeacherAssignmentCurriculumColumns();

        if (!Schema::hasTable('tenant_curriculum_subject_mappings')) {
            Schema::create('tenant_curriculum_subject_mappings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('subject_id');
                $table->uuid('tenant_curriculum_registration_id');
                $table->uuid('multi_curriculum_version_id');
                $table->uuid('curriculum_owner_id');
                $table->uuid('multi_curriculum_structure_id');
                $table->string('status', 30)->default('ACTIVE');
                $table->date('effective_from')->nullable();
                $table->date('effective_until')->nullable();
                $table->uuid('created_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['id', 'tenant_id'], 'tcsm_id_tenant_uq');
                $table->unique(
                    ['tenant_id', 'subject_id', 'multi_curriculum_version_id'],
                    'tcsm_identity_uq'
                );
                $table->index(['tenant_id', 'multi_curriculum_version_id', 'status'], 'tcsm_scope_idx');
                $table->foreign('tenant_id', 'tcsm_tenant_fk')
                    ->references('id')->on('tenants')->onDelete('RESTRICT');
                $table->foreign(['subject_id', 'tenant_id'], 'tcsm_subject_owner_fk')
                    ->references(['id', 'tenant_id'])->on('subjects')->onDelete('RESTRICT');
                $table->foreign(['tenant_curriculum_registration_id', 'tenant_id'], 'tcsm_reg_id_fk')
                    ->references(['id', 'tenant_id'])->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'tcsm_reg_triplet_fk')
                    ->references(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                    ->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(
                    ['multi_curriculum_structure_id', 'multi_curriculum_version_id', 'curriculum_owner_id'],
                    'tcsm_structure_owner_fk'
                )->references(['id', 'multi_curriculum_version_id', 'tenant_id'])
                    ->on('multi_curriculum_structures')->onDelete('RESTRICT');
                $table->foreign(['created_by', 'tenant_id'], 'tcsm_creator_owner_fk')
                    ->references(['id', 'tenant_id'])->on('users')->onDelete('SET NULL');
            });
        }

        if (!Schema::hasTable('tenant_learning_outcomes')) {
            Schema::create('tenant_learning_outcomes', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('tenant_curriculum_registration_id');
                $table->uuid('multi_curriculum_version_id');
                $table->uuid('curriculum_owner_id');
                $table->uuid('subject_id');
                $table->uuid('multi_curriculum_structure_id');
                $table->uuid('teacher_subject_assignment_id');
                $table->uuid('academic_year_id');
                $table->uuid('semester_id')->nullable();
                $table->uuid('parent_cp_id');
                $table->uuid('parent_tp_id')->nullable();
                $table->string('outcome_type', 20);
                $table->string('code', 100);
                $table->string('title', 255);
                $table->longText('description');
                $table->unsignedInteger('sequence_number')->nullable();
                $table->string('version_code', 80)->default('1');
                $table->uuid('supersedes_id')->nullable();
                $table->boolean('is_current')->default(true);
                $table->string('status', 30)->default('DRAFT');
                $table->uuid('author_user_id');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['id', 'tenant_id'], 'tlo_id_tenant_uq');
                $table->unique(
                    ['id', 'tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'],
                    'tlo_id_scope_uq'
                );
                $table->unique(
                    ['tenant_id', 'teacher_subject_assignment_id', 'parent_cp_id', 'outcome_type', 'code', 'version_code'],
                    'tlo_authoring_identity_uq'
                );
                $table->index(['tenant_id', 'teacher_subject_assignment_id', 'outcome_type', 'status'], 'tlo_assignment_idx');
                $table->index(['tenant_id', 'parent_cp_id', 'parent_tp_id'], 'tlo_parent_idx');

                $table->foreign('tenant_id', 'tlo_tenant_fk')
                    ->references('id')->on('tenants')->onDelete('RESTRICT');
                $table->foreign(['tenant_curriculum_registration_id', 'tenant_id'], 'tlo_reg_id_fk')
                    ->references(['id', 'tenant_id'])->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'tlo_reg_triplet_fk')
                    ->references(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                    ->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['subject_id', 'tenant_id'], 'tlo_subject_owner_fk')
                    ->references(['id', 'tenant_id'])->on('subjects')->onDelete('RESTRICT');
                $table->foreign(['multi_curriculum_structure_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'tlo_structure_owner_fk')
                    ->references(['id', 'multi_curriculum_version_id', 'tenant_id'])
                    ->on('multi_curriculum_structures')->onDelete('RESTRICT');
                $table->foreign(['teacher_subject_assignment_id', 'tenant_id'], 'tlo_assignment_owner_fk')
                    ->references(['id', 'tenant_id'])->on('teacher_subject_assignments')->onDelete('RESTRICT');
                $table->foreign(['academic_year_id', 'tenant_id'], 'tlo_year_owner_fk')
                    ->references(['id', 'tenant_id'])->on('academic_years')->onDelete('RESTRICT');
                $table->foreign(['semester_id', 'tenant_id'], 'tlo_semester_owner_fk')
                    ->references(['id', 'tenant_id'])->on('semesters')->onDelete('RESTRICT');
                $table->foreign(['parent_cp_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'tlo_cp_owner_fk')
                    ->references(['id', 'multi_curriculum_version_id', 'tenant_id'])
                    ->on('multi_learning_outcomes')->onDelete('RESTRICT');
                $table->foreign(['parent_tp_id', 'tenant_id'], 'tlo_parent_tp_fk')
                    ->references(['id', 'tenant_id'])->on('tenant_learning_outcomes')->onDelete('RESTRICT');
                $table->foreign(['supersedes_id', 'tenant_id'], 'tlo_supersedes_fk')
                    ->references(['id', 'tenant_id'])->on('tenant_learning_outcomes')->onDelete('RESTRICT');
                $table->foreign(['author_user_id', 'tenant_id'], 'tlo_author_owner_fk')
                    ->references(['id', 'tenant_id'])->on('users')->onDelete('RESTRICT');
            });
        }

        if (!Schema::hasTable('tenant_learning_outcome_structure_mappings')) {
            Schema::create('tenant_learning_outcome_structure_mappings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('tenant_learning_outcome_id');
                $table->uuid('tenant_curriculum_registration_id');
                $table->uuid('multi_curriculum_version_id');
                $table->uuid('curriculum_owner_id');
                $table->uuid('multi_curriculum_structure_id');
                $table->string('mapping_role', 40);
                $table->decimal('weight', 6, 3)->default(1);
                $table->uuid('created_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['id', 'tenant_id'], 'tlosm_id_tenant_uq');
                $table->unique(
                    ['tenant_id', 'tenant_learning_outcome_id', 'multi_curriculum_structure_id', 'mapping_role'],
                    'tlosm_identity_uq'
                );
                $table->foreign('tenant_id', 'tlosm_tenant_fk')
                    ->references('id')->on('tenants')->onDelete('RESTRICT');
                $table->foreign(['tenant_learning_outcome_id', 'tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'tlosm_outcome_scope_fk')
                    ->references(['id', 'tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                    ->on('tenant_learning_outcomes')->onDelete('CASCADE');
                $table->foreign(['tenant_curriculum_registration_id', 'tenant_id'], 'tlosm_reg_id_fk')
                    ->references(['id', 'tenant_id'])->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'tlosm_reg_triplet_fk')
                    ->references(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                    ->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['multi_curriculum_structure_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'tlosm_structure_owner_fk')
                    ->references(['id', 'multi_curriculum_version_id', 'tenant_id'])
                    ->on('multi_curriculum_structures')->onDelete('RESTRICT');
                $table->foreign(['created_by', 'tenant_id'], 'tlosm_creator_owner_fk')
                    ->references(['id', 'tenant_id'])->on('users')->onDelete('SET NULL');
            });
        }

        if (!Schema::hasTable('question_version_curriculum_mappings')) {
            Schema::create('question_version_curriculum_mappings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('question_version_id');
                $table->uuid('tenant_curriculum_registration_id');
                $table->uuid('multi_curriculum_version_id');
                $table->uuid('curriculum_owner_id');
                $table->uuid('multi_learning_outcome_id');
                $table->uuid('material_id')->nullable();
                $table->uuid('material_topic_id')->nullable();
                $table->uuid('academic_year_id')->nullable();
                $table->uuid('semester_id')->nullable();
                $table->string('mapping_role', 40)->default('CP');
                $table->decimal('weight', 6, 3)->default(1);
                $table->boolean('is_primary')->default(false);
                $table->uuid('created_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['id', 'tenant_id'], 'qvcm_id_tenant_uq');
                $table->unique(
                    ['tenant_id', 'question_version_id', 'multi_learning_outcome_id', 'academic_year_id', 'semester_id'],
                    'qvcm_identity_uq'
                );
                $table->index(['tenant_id', 'question_version_id'], 'qvcm_question_idx');
                $table->foreign('tenant_id', 'qvcm_tenant_fk')
                    ->references('id')->on('tenants')->onDelete('RESTRICT');
                $table->foreign(['question_version_id', 'tenant_id'], 'qvcm_question_owner_fk')
                    ->references(['id', 'tenant_id'])->on('question_versions')->onDelete('CASCADE');
                $table->foreign(['tenant_curriculum_registration_id', 'tenant_id'], 'qvcm_reg_id_fk')
                    ->references(['id', 'tenant_id'])->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'qvcm_reg_triplet_fk')
                    ->references(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                    ->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['multi_learning_outcome_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'qvcm_cp_owner_fk')
                    ->references(['id', 'multi_curriculum_version_id', 'tenant_id'])
                    ->on('multi_learning_outcomes')->onDelete('RESTRICT');
                $table->foreign(['material_id', 'tenant_id'], 'qvcm_material_owner_fk')
                    ->references(['id', 'tenant_id'])->on('materials')->onDelete('RESTRICT');
                $table->foreign(['material_topic_id', 'material_id', 'tenant_id'], 'qvcm_topic_material_owner_fk')
                    ->references(['id', 'material_id', 'tenant_id'])->on('material_topics')->onDelete('RESTRICT');
                $table->foreign(['academic_year_id', 'tenant_id'], 'qvcm_year_owner_fk')
                    ->references(['id', 'tenant_id'])->on('academic_years')->onDelete('RESTRICT');
                $table->foreign(['semester_id', 'tenant_id'], 'qvcm_semester_owner_fk')
                    ->references(['id', 'tenant_id'])->on('semesters')->onDelete('RESTRICT');
                $table->foreign(['created_by', 'tenant_id'], 'qvcm_creator_owner_fk')
                    ->references(['id', 'tenant_id'])->on('users')->onDelete('SET NULL');
            });
        }

        if (!Schema::hasTable('question_version_tenant_outcome_mappings')) {
            Schema::create('question_version_tenant_outcome_mappings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('question_version_id');
                $table->uuid('tenant_curriculum_registration_id');
                $table->uuid('multi_curriculum_version_id');
                $table->uuid('curriculum_owner_id');
                $table->uuid('tenant_learning_outcome_id');
                $table->uuid('material_id')->nullable();
                $table->uuid('material_topic_id')->nullable();
                $table->uuid('academic_year_id')->nullable();
                $table->uuid('semester_id')->nullable();
                $table->string('mapping_role', 40)->default('TP_ATP');
                $table->decimal('weight', 6, 3)->default(1);
                $table->boolean('is_primary')->default(false);
                $table->uuid('created_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['id', 'tenant_id'], 'qvtom_id_tenant_uq');
                $table->unique(
                    ['tenant_id', 'question_version_id', 'tenant_learning_outcome_id', 'academic_year_id', 'semester_id'],
                    'qvtom_identity_uq'
                );
                $table->index(['tenant_id', 'question_version_id'], 'qvtom_question_idx');
                $table->foreign('tenant_id', 'qvtom_tenant_fk')
                    ->references('id')->on('tenants')->onDelete('RESTRICT');
                $table->foreign(['question_version_id', 'tenant_id'], 'qvtom_question_owner_fk')
                    ->references(['id', 'tenant_id'])->on('question_versions')->onDelete('CASCADE');
                $table->foreign(['tenant_curriculum_registration_id', 'tenant_id'], 'qvtom_reg_id_fk')
                    ->references(['id', 'tenant_id'])->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'qvtom_reg_triplet_fk')
                    ->references(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                    ->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['tenant_learning_outcome_id', 'tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'qvtom_outcome_scope_fk')
                    ->references(['id', 'tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                    ->on('tenant_learning_outcomes')->onDelete('RESTRICT');
                $table->foreign(['material_id', 'tenant_id'], 'qvtom_material_owner_fk')
                    ->references(['id', 'tenant_id'])->on('materials')->onDelete('RESTRICT');
                $table->foreign(['material_topic_id', 'material_id', 'tenant_id'], 'qvtom_topic_material_owner_fk')
                    ->references(['id', 'material_id', 'tenant_id'])->on('material_topics')->onDelete('RESTRICT');
                $table->foreign(['academic_year_id', 'tenant_id'], 'qvtom_year_owner_fk')
                    ->references(['id', 'tenant_id'])->on('academic_years')->onDelete('RESTRICT');
                $table->foreign(['semester_id', 'tenant_id'], 'qvtom_semester_owner_fk')
                    ->references(['id', 'tenant_id'])->on('semesters')->onDelete('RESTRICT');
                $table->foreign(['created_by', 'tenant_id'], 'qvtom_creator_owner_fk')
                    ->references(['id', 'tenant_id'])->on('users')->onDelete('SET NULL');
            });
        }

        if (!Schema::hasTable('question_version_structure_mappings')) {
            Schema::create('question_version_structure_mappings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id');
                $table->uuid('question_version_id');
                $table->uuid('tenant_curriculum_registration_id');
                $table->uuid('multi_curriculum_version_id');
                $table->uuid('curriculum_owner_id');
                $table->uuid('multi_curriculum_structure_id');
                $table->uuid('academic_year_id')->nullable();
                $table->uuid('semester_id')->nullable();
                $table->string('mapping_role', 40);
                $table->decimal('weight', 6, 3)->default(1);
                $table->uuid('created_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['id', 'tenant_id'], 'qvsm_id_tenant_uq');
                $table->unique(
                    ['tenant_id', 'question_version_id', 'multi_curriculum_structure_id', 'mapping_role', 'academic_year_id', 'semester_id'],
                    'qvsm_identity_uq'
                );
                $table->foreign('tenant_id', 'qvsm_tenant_fk')
                    ->references('id')->on('tenants')->onDelete('RESTRICT');
                $table->foreign(['question_version_id', 'tenant_id'], 'qvsm_question_owner_fk')
                    ->references(['id', 'tenant_id'])->on('question_versions')->onDelete('CASCADE');
                $table->foreign(['tenant_curriculum_registration_id', 'tenant_id'], 'qvsm_reg_id_fk')
                    ->references(['id', 'tenant_id'])->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'qvsm_reg_triplet_fk')
                    ->references(['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'])
                    ->on('tenant_curriculum_registrations')->onDelete('RESTRICT');
                $table->foreign(['multi_curriculum_structure_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'qvsm_structure_owner_fk')
                    ->references(['id', 'multi_curriculum_version_id', 'tenant_id'])
                    ->on('multi_curriculum_structures')->onDelete('RESTRICT');
                $table->foreign(['academic_year_id', 'tenant_id'], 'qvsm_year_owner_fk')
                    ->references(['id', 'tenant_id'])->on('academic_years')->onDelete('RESTRICT');
                $table->foreign(['semester_id', 'tenant_id'], 'qvsm_semester_owner_fk')
                    ->references(['id', 'tenant_id'])->on('semesters')->onDelete('RESTRICT');
                $table->foreign(['created_by', 'tenant_id'], 'qvsm_creator_owner_fk')
                    ->references(['id', 'tenant_id'])->on('users')->onDelete('SET NULL');
            });
        }

        $this->addAssessmentContextColumns();
        $this->addExamSnapshotColumns();
    }

    private function ensureParentIndexes(): void
    {
        $indexes = [
            ['question_versions', ['id', 'tenant_id'], 'qv_parent_id_tenant_uq'],
            ['materials', ['id', 'tenant_id'], 'material_id_tenant_uq'],
            ['subjects', ['id', 'tenant_id'], 'subject_id_tenant_uq'],
            ['academic_years', ['id', 'tenant_id'], 'academic_year_id_tenant_uq'],
            ['semesters', ['id', 'tenant_id'], 'semester_id_tenant_uq'],
            ['teacher_subject_assignments', ['id', 'tenant_id'], 'tsa_id_tenant_uq'],
            ['users', ['id', 'tenant_id'], 'user_id_tenant_uq'],
        ];

        foreach ($indexes as [$table, $columns, $name]) {
            if (Schema::hasTable($table)) {
                $this->addUniqueIndexIfMissing($table, $columns, $name);
            }
        }
    }

    private function addCurriculumProvenance(string $tableName): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName): void {
            if (!Schema::hasColumn($tableName, 'source_id')) {
                $table->uuid('source_id')->nullable();
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
            if (!Schema::hasColumn($tableName, 'is_official')) {
                $table->boolean('is_official')->default(false);
            }
            if (!Schema::hasColumn($tableName, 'is_editable')) {
                $table->boolean('is_editable')->default(true);
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            $this->addForeignKeyIfMissing(
                $tableName,
                ['source_id', 'tenant_id'],
                substr($tableName, 0, 12) . '_source_owner_fk',
                'curriculum_reference_sources',
                ['id', 'tenant_id'],
                'RESTRICT'
            );
        }
    }

    private function addMultiOutcomeProtectionColumns(): void
    {
        if (!Schema::hasTable('multi_learning_outcomes')) {
            return;
        }

        Schema::table('multi_learning_outcomes', function (Blueprint $table): void {
            if (!Schema::hasColumn('multi_learning_outcomes', 'is_official')) {
                $table->boolean('is_official')->default(false);
            }
            if (!Schema::hasColumn('multi_learning_outcomes', 'is_editable')) {
                $table->boolean('is_editable')->default(true);
            }
        });
    }

    private function addTeacherAssignmentCurriculumColumns(): void
    {
        if (!Schema::hasTable('teacher_subject_assignments')) {
            return;
        }

        Schema::table('teacher_subject_assignments', function (Blueprint $table): void {
            if (!Schema::hasColumn('teacher_subject_assignments', 'multi_curriculum_version_id')) {
                $table->uuid('multi_curriculum_version_id')->nullable();
            }
            if (!Schema::hasColumn('teacher_subject_assignments', 'curriculum_owner_id')) {
                $table->uuid('curriculum_owner_id')->nullable();
            }
            if (!Schema::hasColumn('teacher_subject_assignments', 'tenant_curriculum_registration_id')) {
                $table->uuid('tenant_curriculum_registration_id')->nullable();
            }
            if (!Schema::hasColumn('teacher_subject_assignments', 'curriculum_context_snapshot')) {
                $table->json('curriculum_context_snapshot')->nullable();
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            $this->addForeignKeyIfMissing(
                'teacher_subject_assignments',
                ['tenant_curriculum_registration_id', 'tenant_id'],
                'tsa_curr_reg_id_fk',
                'tenant_curriculum_registrations',
                ['id', 'tenant_id'],
                'RESTRICT'
            );
            $this->addForeignKeyIfMissing(
                'teacher_subject_assignments',
                ['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'],
                'tsa_curr_reg_triplet_fk',
                'tenant_curriculum_registrations',
                ['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'],
                'RESTRICT'
            );
        }
    }

    private function addAssessmentContextColumns(): void
    {
        if (!Schema::hasTable('assessments')) {
            return;
        }

        Schema::table('assessments', function (Blueprint $table): void {
            foreach ([
                'assessment_function_option_id',
                'assessment_scope_option_id',
                'instrument_type_option_id',
                'delivery_mode_option_id',
                'assessment_taxonomy_owner_id',
                'curriculum_version_id',
                'curriculum_owner_id',
                'tenant_curriculum_registration_id',
            ] as $column) {
                if (!Schema::hasColumn('assessments', $column)) {
                    $table->uuid($column)->nullable();
                }
            }
            if (!Schema::hasColumn('assessments', 'activity_name')) {
                $table->string('activity_name', 190)->nullable();
            }
            if (!Schema::hasColumn('assessments', 'assessment_origin')) {
                $table->string('assessment_origin', 30)->default('INTERNAL');
            }
            if (!Schema::hasColumn('assessments', 'external_program_name')) {
                $table->string('external_program_name', 190)->nullable();
            }
            if (!Schema::hasColumn('assessments', 'curriculum_context_snapshot')) {
                $table->json('curriculum_context_snapshot')->nullable();
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            $this->addForeignKeyIfMissing('assessments', ['assessment_function_option_id', 'assessment_taxonomy_owner_id'], 'assess_func_owner_fk', 'assessment_taxonomy_options', ['id', 'tenant_id'], 'RESTRICT');
            $this->addForeignKeyIfMissing('assessments', ['assessment_scope_option_id', 'assessment_taxonomy_owner_id'], 'assess_scope_owner_fk', 'assessment_taxonomy_options', ['id', 'tenant_id'], 'RESTRICT');
            $this->addForeignKeyIfMissing('assessments', ['instrument_type_option_id', 'assessment_taxonomy_owner_id'], 'assess_instr_owner_fk', 'assessment_taxonomy_options', ['id', 'tenant_id'], 'RESTRICT');
            $this->addForeignKeyIfMissing('assessments', ['delivery_mode_option_id', 'assessment_taxonomy_owner_id'], 'assess_mode_owner_fk', 'assessment_taxonomy_options', ['id', 'tenant_id'], 'RESTRICT');
            $this->addForeignKeyIfMissing('assessments', ['assessment_taxonomy_owner_id'], 'assess_tax_owner_fk', 'tenants', ['id'], 'RESTRICT');
            $this->addForeignKeyIfMissing('assessments', ['tenant_curriculum_registration_id', 'tenant_id'], 'assess_curr_reg_id_fk', 'tenant_curriculum_registrations', ['id', 'tenant_id'], 'RESTRICT');
            $this->addForeignKeyIfMissing('assessments', ['tenant_id', 'curriculum_version_id', 'curriculum_owner_id'], 'assess_curr_reg_triplet_fk', 'tenant_curriculum_registrations', ['tenant_id', 'multi_curriculum_version_id', 'curriculum_owner_id'], 'RESTRICT');
        }
    }

    private function addExamSnapshotColumns(): void
    {
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

    private function addUniqueIndexIfMissing(string $tableName, array $columns, string $indexName): void
    {
        if (Schema::hasIndex($tableName, $indexName, 'unique') || Schema::hasIndex($tableName, $columns, 'unique')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
            $table->unique($columns, $indexName);
        });
    }

    private function addForeignKeyIfMissing(
        string $tableName,
        array $columns,
        string $foreignKeyName,
        string $referencedTable,
        array $referencedColumns,
        string $onDelete
    ): void {
        if (Schema::hasForeignKey($tableName, $foreignKeyName) || Schema::hasForeignKey($tableName, $columns)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $foreignKeyName, $referencedTable, $referencedColumns, $onDelete): void {
            $table->foreign($columns, $foreignKeyName)
                ->references($referencedColumns)
                ->on($referencedTable)
                ->onDelete($onDelete);
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Web curriculum contract migration is intentionally forward-only. Do not drop tenant academic mappings or snapshots; use an approved forward corrective migration or restore a verified backup.'
        );
    }
};
