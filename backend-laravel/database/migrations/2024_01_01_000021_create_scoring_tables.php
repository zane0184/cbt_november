<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempt_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignUuid('attempt_id')->constrained('exam_attempts')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('users')->onDelete('cascade');

            $table->decimal('raw_score', 12, 4)->default(0);
            $table->decimal('max_score', 12, 4)->default(0);
            $table->decimal('percentage', 8, 2)->default(0);
            $table->decimal('normalized_score', 12, 4)->nullable();

            $table->integer('scoring_version')->default(1);
            $table->string('grading_status')->default('NOT_SCORED'); // NOT_SCORED, AUTO_SCORED, MANUAL_REVIEW, GRADED, FINALIZED

            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignUuid('finalized_by')->nullable()->constrained('users');

            $table->timestamps();

            $table->unique(['attempt_id'], 'unique_attempt_score');
            $table->foreign(['exam_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('exams');
            $table->foreign(['attempt_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('exam_attempts');
        });

        Schema::create('exam_question_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignUuid('attempt_id')->constrained('exam_attempts')->onDelete('cascade');
            $table->foreignUuid('snapshot_id')->constrained('exam_question_snapshots')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('users')->onDelete('cascade');

            $table->string('question_type');
            $table->decimal('awarded_score', 12, 4)->default(0);
            $table->decimal('max_score', 12, 4)->default(0);

            $table->string('scoring_status')->default('NOT_SCORED'); // AUTO, MANUAL
            $table->integer('scoring_rule_version')->default(1);
            $table->json('evaluated_answer_snapshot')->nullable();

            $table->timestamp('evaluated_at')->nullable();
            $table->foreignUuid('grader_id')->nullable()->constrained('users');
            $table->timestamp('graded_at')->nullable();
            $table->text('feedback')->nullable();

            $table->timestamps();

            $table->unique(['attempt_id', 'snapshot_id'], 'unique_attempt_q_score');
            $table->foreign(['exam_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('exams');
            $table->foreign(['attempt_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('exam_attempts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_question_scores');
        Schema::dropIfExists('exam_attempt_scores');
    }
};
