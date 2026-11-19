<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_proctor_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->uuid('exam_id');
            $table->uuid('proctor_user_id');
            $table->uuid('assigned_by');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamp('revoked_at')->nullable();
            $table->uuid('revoked_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'exam_id', 'proctor_user_id'], 'epa_tenant_exam_proctor_unique');
            $table->index(['tenant_id', 'proctor_user_id', 'status', 'starts_at', 'ends_at'], 'epa_proctor_active_window_idx');
            $table->index(['tenant_id', 'exam_id', 'status'], 'epa_exam_status_idx');

            $table->foreign(['exam_id', 'tenant_id'], 'epa_exam_tenant_fk')
                ->references(['id', 'tenant_id'])->on('exams')->restrictOnDelete();
            $table->foreign(['proctor_user_id', 'tenant_id'], 'epa_proctor_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['assigned_by', 'tenant_id'], 'epa_assigner_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['revoked_by', 'tenant_id'], 'epa_revoker_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_proctor_assignments') && DB::table('exam_proctor_assignments')->exists()) {
            throw new \\RuntimeException('Proctor assignment history is retained; this migration is intentionally irreversible after assignments exist.');
        }

        Schema::dropIfExists('exam_proctor_assignments');
    }
};
