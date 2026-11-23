<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('category', 32)->default('REGULAR')->after('title');
            $table->boolean('is_centralized')->default(false)->after('category');
            $table->index(['tenant_id', 'is_centralized', 'category'], 'exams_central_category_idx');
        });

        // Enforce the category domain and category/flag consistency on production MySQL 8+.
        // SQLite test runs still receive equivalent validation from the Exam model.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(\"ALTER TABLE exams ADD CONSTRAINT exams_category_centralized_chk CHECK (\n                (category IN ('ASTS', 'ASAS', 'ASAT', 'UM', 'SIMULASI_AN') AND is_centralized = 1)\n                OR (category IN ('REGULAR', 'FORMATIF', 'SUMATIF_LINGKUP', 'SURVEI_KBC', 'REMEDIAL', 'TRYOUT_MANDIRI') AND is_centralized = 0)\n            )\");
        }

        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->string('code', 32);
            $table->string('name', 120);
            $table->unsignedInteger('capacity');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code'], 'rooms_tenant_code_unique');
            $table->unique(['id', 'tenant_id'], 'rooms_id_tenant_unique');
        });

        Schema::create('exam_room_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->uuid('exam_id');
            $table->uuid('room_id');
            $table->unsignedSmallInteger('session_number')->default(1);
            $table->char('session_token', 6);
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('scheduled_start');
            $table->timestamp('scheduled_end');
            $table->boolean('merge_room')->default(false);
            $table->uuid('created_by');
            $table->timestamps();

            $table->unique(['exam_id', 'room_id', 'session_number'], 'era_exam_room_session_unique');
            $table->unique(['id', 'exam_id', 'tenant_id'], 'era_id_exam_tenant_unique');
            $table->unique(['tenant_id', 'session_token'], 'era_tenant_token_unique');
            $table->index(['tenant_id', 'room_id', 'scheduled_start', 'scheduled_end'], 'era_room_schedule_idx');
            $table->index(['tenant_id', 'exam_id', 'scheduled_start', 'scheduled_end'], 'era_exam_schedule_idx');

            $table->foreign(['exam_id', 'tenant_id'], 'era_exam_tenant_fk')
                ->references(['id', 'tenant_id'])->on('exams')->restrictOnDelete();
            $table->foreign(['room_id', 'tenant_id'], 'era_room_tenant_fk')
                ->references(['id', 'tenant_id'])->on('rooms')->restrictOnDelete();
            $table->foreign(['created_by', 'tenant_id'], 'era_creator_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });

        Schema::create('exam_room_proctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->restrictOnDelete();
            $table->uuid('exam_room_assignment_id');
            $table->uuid('user_id');
            $table->string('role_label', 16)->default('proctor');
            $table->string('status', 16)->default('ACTIVE');
            $table->uuid('assigned_by');
            $table->timestamp('revoked_at')->nullable();
            $table->uuid('revoked_by')->nullable();
            $table->string('revoke_reason', 1000)->nullable();
            $table->timestamps();

            $table->unique(['exam_room_assignment_id', 'user_id'], 'erp_assignment_user_unique');
            $table->index(['tenant_id', 'user_id', 'status'], 'erp_user_status_idx');

            $table->foreign(['exam_room_assignment_id', 'tenant_id'], 'erp_assignment_tenant_fk')
                ->references(['id', 'tenant_id'])->on('exam_room_assignments')->cascadeOnDelete();
            $table->foreign(['user_id', 'tenant_id'], 'erp_user_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['assigned_by', 'tenant_id'], 'erp_assigner_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['revoked_by', 'tenant_id'], 'erp_revoker_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });

        Schema::table('exam_participants', function (Blueprint $table) {
            $table->uuid('exam_room_assignment_id')->nullable()->after('exam_id');
            $table->string('seat_number', 32)->nullable()->after('user_id');
            $table->index(['tenant_id', 'exam_room_assignment_id'], 'ep_room_assignment_idx');
            $table->unique(['exam_room_assignment_id', 'seat_number'], 'ep_room_seat_unique');
            $table->foreign(['exam_room_assignment_id', 'exam_id', 'tenant_id'], 'ep_room_exam_tenant_fk')
                ->references(['id', 'exam_id', 'tenant_id'])->on('exam_room_assignments')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_room_proctors') && DB::table('exam_room_proctors')->exists()) {
            throw new RuntimeException('Rollback ditolak: riwayat penugasan pengawas sudah tersimpan.');
        }
        if (Schema::hasTable('exam_room_assignments') && DB::table('exam_room_assignments')->exists()) {
            throw new RuntimeException('Rollback ditolak: jadwal ruang ujian sudah tersimpan.');
        }
        if (Schema::hasTable('rooms') && DB::table('rooms')->exists()) {
            throw new RuntimeException('Rollback ditolak: master ruang ujian sudah tersimpan.');
        }

        Schema::table('exam_participants', function (Blueprint $table) {
            $table->dropForeign('ep_room_exam_tenant_fk');
            $table->dropUnique('ep_room_seat_unique');
            $table->dropIndex('ep_room_assignment_idx');
            $table->dropColumn(['exam_room_assignment_id', 'seat_number']);
        });

        Schema::dropIfExists('exam_room_proctors');
        Schema::dropIfExists('exam_room_assignments');
        Schema::dropIfExists('rooms');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE exams DROP CHECK exams_category_centralized_chk');
        }

        Schema::table('exams', function (Blueprint $table) {
            $table->dropIndex('exams_central_category_idx');
            $table->dropColumn(['category', 'is_centralized']);
        });
    }
};
