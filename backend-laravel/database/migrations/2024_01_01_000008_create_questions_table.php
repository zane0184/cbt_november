<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('owner_type')->default('PERSONAL'); // PERSONAL, MADRASAH, SHARED
            $table->foreignUuid('owner_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('question_code')->nullable();
            $table->string('status')->default('DRAFT'); // DRAFT, REVIEW, PUBLISHED, ARCHIVED
            $table->string('visibility')->default('PRIVATE'); // PRIVATE, PUBLIC (within tenant)

            // Curriculum Mapping (Optional fields, but stored in identity or latest version?
            // Better in identity if it's the stable property of the question,
            // or in version if it can change. Let's put in identity for core mapping).
            $table->foreignUuid('curriculum_id')->nullable()->constrained('curricula')->onDelete('set null');
            $table->foreignUuid('subject_id')->nullable()->constrained('subjects')->onDelete('set null');
            $table->foreignUuid('phase_id')->nullable()->constrained('phases')->onDelete('set null');
            $table->foreignUuid('grade_level_id')->nullable()->constrained('grade_levels')->onDelete('set null');

            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'question_code']);
            $table->unique(['id', 'tenant_id']);

            // Composite FK for integrity
            $table->foreign(['curriculum_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('curricula');
            $table->foreign(['subject_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('subjects');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
