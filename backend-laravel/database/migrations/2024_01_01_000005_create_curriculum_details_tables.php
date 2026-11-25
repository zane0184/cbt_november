<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('curriculum_id')->constrained('curricula')->onDelete('cascade');
            $table->foreignUuid('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignUuid('phase_id')->nullable()->constrained('phases')->onDelete('set null');
            $table->foreignUuid('grade_level_id')->nullable()->constrained('grade_levels')->onDelete('set null');
            $table->string('status')->default('ACTIVE');
            $table->timestamps();

            $table->unique(['curriculum_id', 'subject_id', 'grade_level_id'], 'curr_sub_grade_unique');
            $table->foreign(['curriculum_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('curricula');
            $table->foreign(['subject_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('subjects');
        });

        Schema::create('elements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('curriculum_id')->constrained('curricula')->onDelete('cascade');
            $table->foreignUuid('phase_id')->constrained('phases')->onDelete('cascade');
            $table->foreignUuid('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0);
            $table->string('status')->default('ACTIVE');
            $table->timestamps();

            $table->unique(['id', 'tenant_id']);
            $table->foreign(['curriculum_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('curricula');
            $table->foreign(['phase_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('phases');
            $table->foreign(['subject_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('subjects');
        });

        // Learning Outcome (CP) Parent
        Schema::create('learning_outcomes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('curriculum_id')->constrained('curricula')->onDelete('cascade');
            $table->foreignUuid('phase_id')->constrained('phases')->onDelete('cascade');
            $table->foreignUuid('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignUuid('element_id')->nullable()->constrained('elements')->onDelete('set null');
            $table->string('code')->nullable(); // Unique identity within tenant
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['id', 'tenant_id']);
            $table->foreign(['curriculum_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('curricula');
            $table->foreign(['phase_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('phases');
            $table->foreign(['subject_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('subjects');
            $table->foreign(['element_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('elements');
        });

        // Learning Outcome Versions
        Schema::create('learning_outcome_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('learning_outcome_id')->constrained('learning_outcomes')->onDelete('cascade');
            $table->integer('major_version')->default(1);
            $table->integer('minor_version')->default(0);
            $table->string('title')->nullable();
            $table->text('description');
            $table->string('status')->default('ACTIVE');
            $table->boolean('is_current')->default(true);
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->unique(['learning_outcome_id', 'major_version', 'minor_version'], 'lo_version_unique');
            $table->foreign(['learning_outcome_id', 'tenant_id'], 'lov_lo_tenant_fk')->references(['id', 'tenant_id'])->on('learning_outcomes');
        });

        Schema::create('learning_objectives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('learning_outcome_id')->constrained('learning_outcomes')->onDelete('cascade');
            // Objectives can also be versioned if attached to a specific version,
            // but for now we link to the outcome and handle logical versioning.
            $table->string('code')->nullable();
            $table->string('title')->nullable();
            $table->text('description');
            $table->integer('order_index')->default(0);
            $table->string('status')->default('ACTIVE');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id']);
            $table->foreign(['learning_outcome_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('learning_outcomes');
        });

        // Learning Path (ATP) Parent
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('curriculum_id')->constrained('curricula')->onDelete('cascade');
            $table->foreignUuid('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignUuid('phase_id')->constrained('phases')->onDelete('cascade');
            $table->string('code')->nullable();
            $table->string('name');
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['id', 'tenant_id']);
            $table->foreign(['curriculum_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('curricula');
            $table->foreign(['subject_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('subjects');
            $table->foreign(['phase_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('phases');
        });

        // Learning Path Versions
        Schema::create('learning_path_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('learning_path_id')->constrained('learning_paths')->onDelete('cascade');
            $table->integer('major_version')->default(1);
            $table->integer('minor_version')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->unique(['learning_path_id', 'major_version', 'minor_version'], 'lp_version_unique');
            $table->unique(['id', 'tenant_id']);
            $table->foreign(['learning_path_id', 'tenant_id'], 'lpver_lp_tenant_fk')->references(['id', 'tenant_id'])->on('learning_paths');
        });

        Schema::create('learning_path_objectives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('learning_path_version_id')->constrained('learning_path_versions')->onDelete('cascade');
            $table->foreignUuid('learning_objective_id')->constrained('learning_objectives')->onDelete('cascade');
            $table->integer('order_index')->default(0);
            $table->timestamps();

            $table->foreign(['learning_path_version_id', 'tenant_id'], 'lpo_lpver_tenant_fk')->references(['id', 'tenant_id'])->on('learning_path_versions');
            $table->foreign(['learning_objective_id', 'tenant_id'], 'lpo_lo_tenant_fk')->references(['id', 'tenant_id'])->on('learning_objectives');
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('curriculum_id')->constrained('curricula')->onDelete('cascade');
            $table->foreignUuid('phase_id')->constrained('phases')->onDelete('cascade');
            $table->foreignUuid('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->foreignUuid('grade_level_id')->nullable()->constrained('grade_levels')->onDelete('set null');
            $table->foreignUuid('element_id')->nullable()->constrained('elements')->onDelete('set null');
            $table->foreignUuid('learning_outcome_id')->nullable()->constrained('learning_outcomes')->onDelete('set null');
            $table->foreignUuid('learning_objective_id')->nullable()->constrained('learning_objectives')->onDelete('set null');
            $table->foreignUuid('learning_path_id')->nullable()->constrained('learning_paths')->onDelete('set null');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->timestamps();

            $table->foreign(['curriculum_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('curricula');
            $table->foreign(['learning_outcome_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('learning_outcomes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
        Schema::dropIfExists('learning_path_objectives');
        Schema::dropIfExists('learning_path_versions');
        Schema::dropIfExists('learning_paths');
        Schema::dropIfExists('learning_objectives');
        Schema::dropIfExists('learning_outcome_versions');
        Schema::dropIfExists('learning_outcomes');
        Schema::dropIfExists('elements');
        Schema::dropIfExists('curriculum_subjects');
    }
};
