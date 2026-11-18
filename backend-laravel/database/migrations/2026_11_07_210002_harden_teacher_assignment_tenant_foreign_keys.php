<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Candidate keys required by MySQL for tenant-aware composite references.
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['id', 'tenant_id'], 'users_id_tenant_unique');
        });

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->unique(['id', 'tenant_id'], 'teacher_profiles_id_tenant_unique');
            $table->dropForeign(['user_id']);
            $table->foreign(['user_id', 'tenant_id'], 'teacher_profiles_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->unique(['id', 'tenant_id'], 'semesters_id_tenant_unique');
            $table->unique(['id', 'academic_year_id', 'tenant_id'], 'semesters_id_year_tenant_unique');
        });

        // An Assessment's semester must belong to its selected academic year and tenant.
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropForeign(['semester_id']);
            $table->foreign(['semester_id', 'academic_year_id', 'tenant_id'], 'assessment_semester_year_tenant_fk')
                ->references(['id', 'academic_year_id', 'tenant_id'])->on('semesters')->restrictOnDelete();
        });

        Schema::table('rombels', function (Blueprint $table) {
            $table->unique(['id', 'tenant_id'], 'rombels_id_tenant_unique');
            $table->unique(['id', 'academic_year_id', 'tenant_id'], 'rombels_id_year_tenant_unique');
        });

        Schema::table('teacher_subject_assignments', function (Blueprint $table) {
            $table->dropForeign(['teacher_profile_id']);
            $table->dropForeign(['subject_id']);
            $table->dropForeign(['rombel_id']);
            $table->dropForeign(['academic_year_id']);
            $table->dropForeign(['semester_id']);
            $table->dropForeign(['created_by']);

            $table->foreign(['teacher_profile_id', 'tenant_id'], 'tsa_teacher_tenant_fk')
                ->references(['id', 'tenant_id'])->on('teacher_profiles')->restrictOnDelete();
            $table->foreign(['subject_id', 'tenant_id'], 'tsa_subject_tenant_fk')
                ->references(['id', 'tenant_id'])->on('subjects')->restrictOnDelete();
            $table->foreign(['rombel_id', 'tenant_id'], 'tsa_rombel_tenant_fk')
                ->references(['id', 'tenant_id'])->on('rombels')->restrictOnDelete();
            $table->foreign(['academic_year_id', 'tenant_id'], 'tsa_year_tenant_fk')
                ->references(['id', 'tenant_id'])->on('academic_years')->restrictOnDelete();
            $table->foreign(['semester_id', 'tenant_id'], 'tsa_semester_tenant_fk')
                ->references(['id', 'tenant_id'])->on('semesters')->restrictOnDelete();
            $table->foreign(['semester_id', 'academic_year_id', 'tenant_id'], 'tsa_semester_year_tenant_fk')
                ->references(['id', 'academic_year_id', 'tenant_id'])->on('semesters')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'tsa_creator_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['rombel_id', 'academic_year_id', 'tenant_id'], 'tsa_rombel_year_tenant_fk')
                ->references(['id', 'academic_year_id', 'tenant_id'])->on('rombels')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('teacher_subject_assignments', function (Blueprint $table) {
            $table->dropForeign('tsa_teacher_tenant_fk');
            $table->dropForeign('tsa_subject_tenant_fk');
            $table->dropForeign('tsa_rombel_tenant_fk');
            $table->dropForeign('tsa_year_tenant_fk');
            $table->dropForeign('tsa_semester_tenant_fk');
            $table->dropForeign('tsa_semester_year_tenant_fk');
            $table->dropForeign('tsa_creator_tenant_fk');
            $table->dropForeign('tsa_rombel_year_tenant_fk');

            $table->foreign('teacher_profile_id')->references('id')->on('teacher_profiles')->restrictOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->restrictOnDelete();
            $table->foreign('rombel_id')->references('id')->on('rombels')->restrictOnDelete();
            $table->foreign('academic_year_id')->references('id')->on('academic_years')->restrictOnDelete();
            $table->foreign('semester_id')->references('id')->on('semesters')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropForeign('assessment_semester_year_tenant_fk');
            $table->foreign('semester_id')->references('id')->on('semesters')->nullOnDelete();
        });

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropForeign('teacher_profiles_user_tenant_fk');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropUnique('teacher_profiles_id_tenant_unique');
        });

        Schema::table('rombels', function (Blueprint $table) {
            $table->dropUnique('rombels_id_year_tenant_unique');
            $table->dropUnique('rombels_id_tenant_unique');
        });

        Schema::table('semesters', function (Blueprint $table) {
            $table->dropUnique('semesters_id_year_tenant_unique');
            $table->dropUnique('semesters_id_tenant_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_id_tenant_unique');
        });
    }
};
