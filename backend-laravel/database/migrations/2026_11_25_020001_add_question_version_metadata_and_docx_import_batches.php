<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_versions', function (Blueprint $table): void {
            $table->json('metadata')->nullable()->after('explanation');
        });

        Schema::create('question_docx_import_batches', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('teacher_subject_assignment_id')->constrained('teacher_subject_assignments')->cascadeOnDelete();
            $table->char('file_sha256', 64);
            $table->unsignedInteger('imported_count');
            $table->timestamps();

            $table->unique(
                ['tenant_id', 'teacher_subject_assignment_id', 'file_sha256'],
                'question_docx_import_idempotency_uq'
            );
            $table->index(['tenant_id', 'uploaded_by', 'created_at'], 'question_docx_import_actor_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_docx_import_batches');
        Schema::table('question_versions', function (Blueprint $table): void {
            $table->dropColumn('metadata');
        });
    }
};
