<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multi_curriculum_structures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('multi_curriculum_version_id')->constrained('multi_curriculum_versions')->onDelete('cascade');
            $table->foreignUuid('parent_id')->nullable()->constrained('multi_curriculum_structures')->onDelete('set null');
            $table->string('node_type', 50); // FASE, GRADE_LEVEL, ELEMENT, DIMENSI_CINTA, PETA_KASIH
            $table->string('code', 50);
            $table->string('title');
            $table->integer('sort_order')->default(0);
            $table->string('status')->default('ACTIVE'); // ACTIVE, ARCHIVED, DEACTIVATED
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['multi_curriculum_version_id', 'code'], 'uniq_m_curr_struct_code');
            $table->index(['tenant_id', 'multi_curriculum_version_id', 'parent_id'], 'idx_m_curr_struct_t_v_p');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multi_curriculum_structures');
    }
};
