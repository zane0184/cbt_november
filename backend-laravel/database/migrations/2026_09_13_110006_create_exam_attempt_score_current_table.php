<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempt_score_current', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('attempt_id')->constrained('exam_attempts')->onDelete('cascade');
            $table->foreignUuid('current_score_id')->constrained('exam_attempt_scores')->onDelete('cascade');
            $table->integer('scoring_version')->default(1);
            $table->timestamps();

            $table->unique(['attempt_id'], 'easc_attempt_unique');
            $table->unique(['id', 'tenant_id']);

            $table->foreign(['attempt_id', 'tenant_id'], 'easc_attempt_tenant_fk')
                ->references(['id', 'tenant_id'])->on('exam_attempts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_score_current');
    }
};
