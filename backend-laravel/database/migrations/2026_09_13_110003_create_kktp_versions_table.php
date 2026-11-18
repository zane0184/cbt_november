<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kktp_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('learning_objective_id')->constrained('learning_objectives')->onDelete('restrict');
            $table->integer('version')->default(1);
            $table->decimal('threshold', 5, 2)->default(70.00);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->unique(['learning_objective_id', 'version'], 'kktp_ver_tp_version_unique');
            $table->unique(['id', 'tenant_id']);

            $table->foreign(['learning_objective_id', 'tenant_id'], 'kktp_ver_lo_tenant_fk')
                ->references(['id', 'tenant_id'])->on('learning_objectives')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kktp_versions');
    }
};
