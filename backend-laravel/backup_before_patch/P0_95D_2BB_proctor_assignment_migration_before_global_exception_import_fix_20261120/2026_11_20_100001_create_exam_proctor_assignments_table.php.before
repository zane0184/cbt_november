<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exam_proctor_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->uuid('exam_id');
            $table->uuid('proctor_user_id');
            $table->uuid('assigned_by');
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->string('status', 16)->default('ACTIVE');
            $table->text('assignment_reason');
            $table->timestamp('revoked_at')->nullable();
            $table->uuid('revoked_by')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'exam_id', 'proctor_user_id'], 'exam_proctor_assignment_unique');
            $table->index(['tenant_id', 'proctor_user_id', 'status', 'starts_at', 'expires_at'], 'epa_proctor_active_window_idx');
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
            throw new RuntimeException('Rollback ditolak: riwayat penugasan Proktor sudah tersimpan. Gunakan prosedur retensi/arsip yang disetujui.');
        }
        Schema::dropIfExists('exam_proctor_assignments');
    }
};
