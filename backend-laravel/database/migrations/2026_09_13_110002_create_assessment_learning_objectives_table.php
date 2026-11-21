<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_learning_objectives', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('assessment_id')->constrained('assessments')->onDelete('cascade');
            $table->foreignUuid('learning_objective_id')->constrained('learning_objectives')->onDelete('restrict');
            $table->timestamps();

            $table->unique(['assessment_id', 'learning_objective_id'], 'alo_assessment_tp_unique');
            $table->unique(['id', 'tenant_id']);

            $table->foreign(['assessment_id', 'tenant_id'], 'alo_assessment_tenant_fk')
                ->references(['id', 'tenant_id'])->on('assessments')->onDelete('cascade');
            $table->foreign(['learning_objective_id', 'tenant_id'], 'alo_tp_tenant_fk')
                ->references(['id', 'tenant_id'])->on('learning_objectives')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_learning_objectives');
    }
};
