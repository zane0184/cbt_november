<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_question_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('exam_id')->constrained('exams')->onDelete('cascade');
            $table->foreignUuid('source_question_id')->constrained('questions')->onDelete('cascade');
            $table->foreignUuid('source_question_version_id')->constrained('question_versions')->onDelete('cascade');

            $table->integer('sequence')->default(0);
            $table->decimal('points', 8, 2)->default(1.00);

            // IMMUTABLE CONTENT SNAPSHOT
            $table->text('snapshot_stem');
            $table->text('snapshot_explanation')->nullable();
            $table->json('snapshot_options'); // Immutable copy of options and keys
            $table->json('snapshot_media')->nullable(); // Immutable references to assets

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'source_question_id'], 'exam_q_unique');
            $table->foreign(['exam_id', 'tenant_id'])->references(['id', 'tenant_id'])->on('exams');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_question_snapshots');
    }
};
