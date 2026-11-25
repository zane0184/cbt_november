<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'name']);
            $table->unique(['id', 'tenant_id']);
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->enum('term', ['GANJIL', 'GENAP']);
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_year_id', 'term']);

            $table->foreign(['academic_year_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('academic_years')
                ->onDelete('cascade');
        });

        Schema::create('curricula', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id']);
            $table->unique(['tenant_id', 'code']);
        });

        Schema::create('phases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('curriculum_id')->constrained('curricula')->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->timestamps();

            $table->unique(['id', 'tenant_id']);
            $table->unique(['curriculum_id', 'code']);
            $table->foreign(['curriculum_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('curricula')
                ->onDelete('cascade');
        });

        Schema::create('grade_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('phase_id')->constrained('phases')->onDelete('cascade');
            $table->string('name');
            $table->string('code');
            $table->integer('level');
            $table->string('status')->default('ACTIVE');
            $table->timestamps();

            $table->unique(['id', 'tenant_id']);
            $table->unique(['phase_id', 'code']);
            $table->foreign(['phase_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('phases')
                ->onDelete('cascade');
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('code');
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('status')->default('ACTIVE');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code']);
            $table->unique(['id', 'tenant_id']);
        });

        Schema::create('rombels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignUuid('grade_level_id')->constrained('grade_levels')->onDelete('cascade');
            $table->string('name');
            $table->foreignUuid('teacher_id')->nullable()->constrained('teacher_profiles')->onDelete('set null');
            $table->string('status')->default('ACTIVE');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['academic_year_id', 'name']);

            $table->foreign(['academic_year_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('academic_years')
                ->onDelete('cascade');

            $table->foreign(['grade_level_id', 'tenant_id'])
                ->references(['id', 'tenant_id'])
                ->on('grade_levels')
                ->onDelete('cascade');
        });

        Schema::create('rombel_student', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rombel_id')->constrained('rombels')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('student_profiles')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['rombel_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rombel_student');
        Schema::dropIfExists('rombels');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('phases');
        Schema::dropIfExists('curricula');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('academic_years');
    }
};
