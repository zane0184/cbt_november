<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->string('status')->default('DRAFT'); // DRAFT, SCHEDULED, ONGOING, CLOSED

            $table->integer('duration_seconds');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');

            $table->integer('attempt_limit')->default(1);
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->boolean('show_result')->default(false);
            $table->boolean('show_correct_answer')->default(false);
            $table->boolean('allow_resume')->default(true);
            $table->boolean('auto_submit')->default(true);

            $table->boolean('require_token')->default(true);
            $table->string('token')->nullable();
            $table->string('token_mode')->default('SINGLE_TOKEN');

            $table->integer('revision_number')->default(1);
            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['id', 'tenant_id']);
            $table->foreign(['assessment_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('assessments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
