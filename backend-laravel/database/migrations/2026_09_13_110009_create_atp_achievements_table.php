<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atp_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('users')->onDelete('restrict');
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('restrict');
            $table->foreignUuid('semester_id')->constrained('semesters')->onDelete('restrict');
            $table->foreignUuid('subject_id')->constrained('subjects')->onDelete('restrict');
            $table->foreignUuid('rombel_id')->nullable()->constrained('rombels')->onDelete('restrict');
            $table->foreignUuid('learning_path_id')->constrained('learning_paths')->onDelete('restrict');
            $table->foreignUuid('learning_path_version_id')->nullable()->constrained('learning_path_versions')->onDelete('restrict');

            $table->decimal('score', 8, 2)->nullable();
            $table->string('status', 30)->default('BELUM_DINILAI');
            $table->integer('calculation_version')->default(1);
            $table->boolean('is_current')->default(true);

            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignUuid('finalized_by')->nullable()->constrained('users')->onDelete('restrict');

            $table->timestamps();

            $table->unique(['id', 'tenant_id']);
            $table->unique(['student_id', 'learning_path_id', 'academic_year_id', 'semester_id', 'calculation_version'], 'atp_ach_version_unique');

            $table->foreign(['academic_year_id', 'tenant_id'], 'atp_ach_ay_tenant_fk')
                ->references(['id', 'tenant_id'])->on('academic_years')->onDelete('restrict');
            $table->foreign(['subject_id', 'tenant_id'], 'atp_ach_sub_tenant_fk')
                ->references(['id', 'tenant_id'])->on('subjects')->onDelete('restrict');
            $table->foreign(['learning_path_id', 'tenant_id'], 'atp_ach_lp_tenant_fk')
                ->references(['id', 'tenant_id'])->on('learning_paths')->onDelete('restrict');
        });

        DB::statement(
            "ALTER TABLE atp_achievements
             ADD CONSTRAINT atp_ach_status_check
             CHECK (status IN ('BELUM_DINILAI', 'TERCAPAI', 'BELUM_TERCAPAI'))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('atp_achievements');
    }
};
