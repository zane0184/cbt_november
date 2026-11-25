<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('academic_year_id')->constrained('academic_years')->onDelete('cascade');
            $table->foreignUuid('semester_id')->nullable()->constrained('semesters')->onDelete('set null');
            $table->foreignUuid('curriculum_id')->nullable()->constrained('curricula')->onDelete('set null');
            $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->onDelete('set null');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('assessment_type'); // DAILY, MIDTERM, FINAL, etc.
            $table->string('status')->default('DRAFT');
            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id']);
            $table->foreign(['academic_year_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('academic_years');
            $table->foreign(['subject_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('subjects');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
