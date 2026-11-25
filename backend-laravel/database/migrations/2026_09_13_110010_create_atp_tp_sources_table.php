<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atp_tp_sources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('atp_achievement_id')->constrained('atp_achievements')->onDelete('restrict');
            $table->foreignUuid('tp_achievement_id')->constrained('tp_achievements')->onDelete('restrict');

            $table->decimal('tp_score_snapshot', 8, 2)->nullable();
            $table->string('tp_status_snapshot', 30)->nullable();
            $table->integer('tp_calculation_version_snapshot')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->unique(['atp_achievement_id', 'tp_achievement_id'], 'atp_tp_src_unique');
            $table->unique(['id', 'tenant_id']);

            $table->foreign(['atp_achievement_id', 'tenant_id'], 'ats_atpach_tenant_fk')
                ->references(['id', 'tenant_id'])->on('atp_achievements')->onDelete('restrict');
            $table->foreign(['tp_achievement_id', 'tenant_id'], 'ats_tpach_tenant_fk')
                ->references(['id', 'tenant_id'])->on('tp_achievements')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atp_tp_sources');
    }
};
