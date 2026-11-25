<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cp_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('users')->onDelete('restrict');
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('restrict');
            $table->foreignUuid('semester_id')->constrained('semesters')->onDelete('restrict');
            $table->foreignUuid('subject_id')->constrained('subjects')->onDelete('restrict');
            $table->foreignUuid('rombel_id')->nullable()->constrained('rombels')->onDelete('restrict');
            $table->foreignUuid('learning_outcome_id')->constrained('learning_outcomes')->onDelete('restrict');
            $table->foreignUuid('learning_outcome_version_id')->nullable()->constrained('learning_outcome_versions')->onDelete('restrict');

            $table->decimal('score', 8, 2)->nullable();
            $table->string('status', 30)->default('BELUM_DINILAI');
            $table->integer('calculation_version')->default(1);
            $table->boolean('is_current')->default(true);

            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignUuid('finalized_by')->nullable()->constrained('users')->onDelete('restrict');

            $table->timestamps();

            $table->unique(['id', 'tenant_id']);
            $table->unique(['student_id', 'learning_outcome_id', 'academic_year_id', 'semester_id', 'calculation_version'], 'cp_ach_version_unique');

            $table->foreign(['academic_year_id', 'tenant_id'], 'cp_ach_ay_tenant_fk')
                ->references(['id', 'tenant_id'])->on('academic_years')->onDelete('restrict');
            $table->foreign(['subject_id', 'tenant_id'], 'cp_ach_sub_tenant_fk')
                ->references(['id', 'tenant_id'])->on('subjects')->onDelete('restrict');
            $table->foreign(['learning_outcome_id', 'tenant_id'], 'cp_ach_lo_tenant_fk')
                ->references(['id', 'tenant_id'])->on('learning_outcomes')->onDelete('restrict');
        });

        DB::statement(
            "ALTER TABLE cp_achievements
             ADD CONSTRAINT cp_ach_status_check
             CHECK (status IN ('BELUM_DINILAI', 'TERCAPAI', 'BELUM_TERCAPAI'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('cp_achievements');
    }
};
