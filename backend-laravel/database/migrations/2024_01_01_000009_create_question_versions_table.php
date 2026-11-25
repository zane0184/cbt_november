<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('question_id')->constrained('questions')->onDelete('cascade');
            $table->integer('major_version')->default(1);
            $table->integer('minor_version')->default(0);
            $table->string('question_type'); // MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, SHORT_ANSWER, ESSAY

            $table->text('stem'); // The question text
            $table->text('explanation')->nullable();
            $table->string('difficulty')->default('MEDIUM'); // EASY, MEDIUM, HARD
            $table->string('cognitive_level')->nullable(); // C1-C6 or Bloom's
            $table->integer('estimated_time')->nullable(); // In seconds
            $table->decimal('default_points', 8, 2)->default(1.00);

            // Detailed curriculum mapping (can be refined in version)
            $table->foreignUuid('element_id')->nullable()->constrained('elements')->onDelete('set null');
            $table->foreignUuid('learning_outcome_id')->nullable()->constrained('learning_outcomes')->onDelete('set null');
            $table->foreignUuid('learning_objective_id')->nullable()->constrained('learning_objectives')->onDelete('set null');

            $table->string('status')->default('DRAFT');
            $table->boolean('is_current')->default(true);
            $table->foreignUuid('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['question_id', 'major_version', 'minor_version']);
            $table->unique(['id', 'tenant_id']);
            $table->foreign(['question_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('questions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_versions');
    }
};
