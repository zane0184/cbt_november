<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('question_multi_curriculum_mappings');

        Schema::create('question_multi_curriculum_mappings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('question_id');
            $table->uuid('multi_curriculum_version_id');
            $table->uuid('multi_learning_outcome_id');
            $table->decimal('weight', 5, 2)->default(1.00);
            $table->timestamps();

            $table->foreign('tenant_id', 'qmcm_tenant_fk')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign(['question_id', 'tenant_id'], 'qmcm_q_tenant_fk')->references(['id', 'tenant_id'])->on('questions')->onDelete('cascade');
            $table->foreign('multi_curriculum_version_id', 'qmcm_ver_fk')->references('id')->on('multi_curriculum_versions')->onDelete('cascade');
            $table->foreign(['multi_learning_outcome_id', 'tenant_id'], 'qmcm_lo_tenant_fk')->references(['id', 'tenant_id'])->on('multi_learning_outcomes')->onDelete('cascade');

            $table->unique(['question_id', 'multi_learning_outcome_id'], 'uniq_q_m_outcome');
            $table->index(['tenant_id', 'multi_curriculum_version_id', 'multi_learning_outcome_id'], 'idx_t_mcv_mlo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_multi_curriculum_mappings');
    }
};
