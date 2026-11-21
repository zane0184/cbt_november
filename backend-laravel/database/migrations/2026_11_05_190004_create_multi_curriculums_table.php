<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multi_curriculums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained('tenants')->onDelete('cascade');
            $table->string('code', 50); // MERDEKA, KBC, K13, CUSTOM
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('ACTIVE'); // ACTIVE, ARCHIVED, DEACTIVATED
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'code'], 'uniq_m_curriculums_tenant_code');
            $table->index(['tenant_id', 'status'], 'idx_m_curriculums_tenant_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multi_curriculums');
    }
};
