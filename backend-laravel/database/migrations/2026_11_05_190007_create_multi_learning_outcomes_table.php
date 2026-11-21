<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multi_learning_outcomes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('multi_curriculum_structure_id')->constrained('multi_curriculum_structures')->onDelete('cascade');
            $table->string('code', 100); // CP-01, TP-102, KBC-KASIH-01
            $table->string('outcome_type', 50); // CP, TP, ATP, KKTP, KBC_CINTA, K13_KD
            $table->text('description');
            $table->string('status')->default('ACTIVE'); // ACTIVE, ARCHIVED, DEACTIVATED
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['multi_curriculum_structure_id', 'code'], 'uniq_m_outcomes_struct_code');
            $table->unique(['id', 'tenant_id'], 'uniq_mlo_id_tenant');
            $table->index(['tenant_id', 'multi_curriculum_structure_id', 'outcome_type'], 'idx_m_outcomes_t_s_o');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multi_learning_outcomes');
    }
};
