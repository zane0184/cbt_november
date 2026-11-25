<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_question_scores', function (Blueprint $table) {
            $table->integer('scoring_version')->default(1)->after('scoring_rule_version');
        });

        DB::table('exam_question_scores')->whereNull('scoring_version')->update(['scoring_version' => 1]);

        Schema::table('exam_question_scores', function (Blueprint $table) {
            $table->dropUnique('unique_attempt_q_score');
            $table->unique(['attempt_id', 'snapshot_id', 'scoring_version'], 'unique_attempt_q_score_version');
        });
    }

    public function down(): void
    {
        Schema::table('exam_question_scores', function (Blueprint $table) {
            $table->dropUnique('unique_attempt_q_score_version');
            $table->unique(['attempt_id', 'snapshot_id'], 'unique_attempt_q_score');
            $table->dropColumn('scoring_version');
        });
    }
};
