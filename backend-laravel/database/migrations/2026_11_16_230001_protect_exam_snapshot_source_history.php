<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preserve source question/version rows while exam snapshots reference them.
 *
 * Snapshots copy the exam content, but their source identifiers remain useful
 * for provenance. Deleting a source question/version must never cascade into
 * snapshots, answers, or score history.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_question_snapshots', function (Blueprint $table): void {
            $table->dropForeign('exam_question_snapshots_source_question_id_foreign');
            $table->dropForeign('exam_question_snapshots_source_question_version_id_foreign');

            $table->foreign('source_question_id', 'exam_snapshot_source_question_restrict_fk')
                ->references('id')
                ->on('questions')
                ->restrictOnDelete();

            $table->foreign('source_question_version_id', 'exam_snapshot_source_version_restrict_fk')
                ->references('id')
                ->on('question_versions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        // Rollback restores the historical cascade behavior. Do not roll back
        // this migration on a database containing exam history.
        Schema::table('exam_question_snapshots', function (Blueprint $table): void {
            $table->dropForeign('exam_snapshot_source_question_restrict_fk');
            $table->dropForeign('exam_snapshot_source_version_restrict_fk');

            $table->foreign('source_question_id')
                ->references('id')
                ->on('questions')
                ->cascadeOnDelete();

            $table->foreign('source_question_version_id')
                ->references('id')
                ->on('question_versions')
                ->cascadeOnDelete();
        });
    }
};
