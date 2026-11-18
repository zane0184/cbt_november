<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multi_curriculum_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('multi_curriculum_id')->constrained('multi_curriculums')->onDelete('cascade');
            $table->string('version_code', 50); // 2024_REVISI, KBC_2026_V1
            $table->string('title');
            $table->integer('effective_year')->nullable();
            $table->string('status')->default('ACTIVE'); // ACTIVE, ARCHIVED, DEACTIVATED
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['multi_curriculum_id', 'version_code'], 'uniq_m_curr_ver_code');
            $table->index(['tenant_id', 'multi_curriculum_id', 'status'], 'idx_m_curr_ver_t_c_s');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multi_curriculum_versions');
    }
};
