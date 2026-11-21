<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempt_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignUuid('exam_attempt_id')->constrained('exam_attempts')->onDelete('cascade');
            $table->foreignUuid('exam_question_snapshot_id')->constrained('exam_question_snapshots')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('users')->onDelete('cascade');

            $table->json('answer_payload')->nullable();
            $table->string('answer_state')->default('UNANSWERED'); // UNANSWERED, ANSWERED, CLEARED
            $table->integer('revision_number')->default(1);
            $table->string('client_request_id')->nullable();

            $table->timestamp('answered_at')->nullable();
            $table->timestamp('saved_at')->nullable();

            $table->timestamps();

            // Unique constraint to prevent duplicate answers for the same question in an attempt
            $table->unique(['exam_attempt_id', 'exam_question_snapshot_id'], 'unique_attempt_question');

            // Composite FK for tenant safety
            $table->foreign(['exam_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('exams');
            $table->foreign(['exam_attempt_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('exam_attempts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_answers');
    }
};
