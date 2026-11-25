<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure existing rows have valid scoring_version
        DB::table('exam_attempt_scores')->whereNull('scoring_version')->update(['scoring_version' => 1]);

        Schema::table('exam_attempt_scores', function (Blueprint $table) {
            $table->dropUnique('unique_attempt_score');
            $table->unique(['attempt_id', 'scoring_version'], 'unique_attempt_score_version');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempt_scores', function (Blueprint $table) {
            $table->dropUnique('unique_attempt_score_version');
            $table->unique(['attempt_id'], 'unique_attempt_score');
        });
    }
};
