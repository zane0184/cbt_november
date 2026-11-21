<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tp_assessment_contributions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('tp_achievement_id')->constrained('tp_achievements')->onDelete('restrict');
            $table->foreignUuid('assessment_id')->constrained('assessments')->onDelete('restrict');
            $table->foreignUuid('attempt_score_id')->nullable()->constrained('exam_attempt_scores')->onDelete('restrict');

            $table->decimal('assessment_score_snapshot', 12, 4)->nullable();
            $table->decimal('weight_snapshot', 8, 2)->default(1.00);
            $table->decimal('weighted_contribution', 12, 4)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['id', 'tenant_id']);
            $table->foreign(['tp_achievement_id', 'tenant_id'], 'tp_ac_tpach_tenant_fk')
                ->references(['id', 'tenant_id'])->on('tp_achievements')->onDelete('restrict');
            $table->foreign(['assessment_id', 'tenant_id'], 'tp_ac_ass_tenant_fk')
                ->references(['id', 'tenant_id'])->on('assessments')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tp_assessment_contributions');
    }
};
