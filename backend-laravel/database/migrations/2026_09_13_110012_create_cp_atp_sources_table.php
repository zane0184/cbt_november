<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cp_atp_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('cp_achievement_id')->constrained('cp_achievements')->onDelete('restrict');
            $table->foreignUuid('atp_achievement_id')->constrained('atp_achievements')->onDelete('restrict');

            $table->decimal('atp_score_snapshot', 8, 2)->nullable();
            $table->string('atp_status_snapshot', 30)->nullable();
            $table->integer('atp_calculation_version_snapshot')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['cp_achievement_id', 'atp_achievement_id'], 'cp_atp_src_unique');
            $table->unique(['id', 'tenant_id']);

            $table->foreign(['cp_achievement_id', 'tenant_id'], 'cas_cpach_tenant_fk')
                ->references(['id', 'tenant_id'])->on('cp_achievements')->onDelete('restrict');
            $table->foreign(['atp_achievement_id', 'tenant_id'], 'cas_atpach_tenant_fk')
                ->references(['id', 'tenant_id'])->on('atp_achievements')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cp_atp_sources');
    }
};
