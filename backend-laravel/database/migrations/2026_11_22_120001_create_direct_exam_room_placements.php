<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['tenants', 'users', 'exams', 'exam_rooms', 'exam_participants', 'exam_proctor_assignments'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Direct Exam-Room prerequisite missing: {$table}.");
            }
        }

        if (DB::getDriverName() === 'mysql') {
            $required = ['exams', 'exam_rooms', 'exam_participants', 'exam_proctor_assignments'];
            $marks = implode(',', array_fill(0, count($required), '?'));
            $engines = DB::select(
                "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ({$marks})",
                $required
            );
            if (count($engines) !== count($required)
                || collect($engines)->contains(fn ($row) => strtolower((string) $row->ENGINE) !== 'innodb')) {
                throw new RuntimeException('Direct Exam-Room schema requires MySQL 8/InnoDB prerequisite tables.');
            }
        }

        if (Schema::hasTable('exam_room_exam')
            || Schema::hasTable('exam_participant_room_placements')
            || Schema::hasColumn('exam_proctor_assignments', 'exam_room_id')) {
            throw new RuntimeException('Direct Exam-Room migration detected partial state; no DDL was executed.');
        }

        // exam_rooms is a reusable physical room registry. This pivot allocates
        // the same physical room to different Exams without introducing sessions.
        Schema::create('exam_room_exam', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('exam_id');
            $table->uuid('exam_room_id');
            $table->unsignedInteger('capacity_override')->nullable();
            $table->string('status', 16)->default('ACTIVE');
            $table->uuid('assigned_by');
            $table->timestamps();

            $table->unique(['tenant_id', 'exam_id', 'exam_room_id'], 'ere_exam_room_unique');
            $table->unique(['exam_id', 'tenant_id', 'exam_room_id'], 'ere_exam_tenant_room_fk_unique');
            $table->unique(['id', 'tenant_id', 'exam_id'], 'ere_id_tenant_exam_unique');
            $table->index(['tenant_id', 'exam_id', 'status'], 'ere_exam_status_idx');
            $table->foreign(['exam_id', 'tenant_id'], 'ere_exam_tenant_fk')
                ->references(['id', 'tenant_id'])->on('exams')->restrictOnDelete();
            $table->foreign(['exam_room_id', 'tenant_id'], 'ere_room_tenant_fk')
                ->references(['id', 'tenant_id'])->on('exam_rooms')->restrictOnDelete();
            $table->foreign(['assigned_by', 'tenant_id'], 'ere_assigner_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });

        Schema::create('exam_participant_room_placements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('exam_id');
            $table->uuid('participant_id');
            $table->uuid('exam_room_id');
            $table->string('status', 16)->default('ACTIVE');
            $table->uuid('assigned_by');
            $table->timestamp('assigned_at');
            $table->timestamp('released_at')->nullable();
            $table->uuid('released_by')->nullable();
            $table->string('release_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'exam_id', 'exam_room_id', 'status'], 'eprp_room_status_idx');
            $table->index(['tenant_id', 'participant_id', 'status'], 'eprp_participant_status_idx');
            $table->foreign(['participant_id', 'tenant_id', 'exam_id'], 'eprp_participant_scope_fk')
                ->references(['id', 'tenant_id', 'exam_id'])->on('exam_participants')->restrictOnDelete();
            $table->foreign(['exam_id', 'tenant_id', 'exam_room_id'], 'eprp_exam_room_scope_fk')
                ->references(['exam_id', 'tenant_id', 'exam_room_id'])->on('exam_room_exam')->restrictOnDelete();
            $table->foreign(['assigned_by', 'tenant_id'], 'eprp_assigner_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['released_by', 'tenant_id'], 'eprp_releaser_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "CREATE UNIQUE INDEX eprp_one_active_participant_exam_unique ON exam_participant_room_placements ((CASE WHEN status = 'ACTIVE' THEN CONCAT(tenant_id, ':', exam_id, ':', participant_id) ELSE NULL END))"
            );
        } else {
            DB::statement(
                "CREATE UNIQUE INDEX eprp_one_active_participant_exam_unique ON exam_participant_room_placements (tenant_id, exam_id, participant_id) WHERE status = 'ACTIVE'"
            );
        }

        Schema::table('exam_proctor_assignments', function (Blueprint $table): void {
            $table->uuid('exam_room_id')->nullable()->after('exam_id');
            $table->index(
                ['tenant_id', 'exam_id', 'exam_room_id', 'proctor_user_id', 'status'],
                'epa_direct_room_scope_idx'
            );
            $table->foreign(['exam_room_id', 'tenant_id'], 'epa_direct_room_tenant_fk')
                ->references(['id', 'tenant_id'])->on('exam_rooms')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        foreach (['exam_participant_room_placements', 'exam_room_exam'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Rollback refused: {$table} contains operational history.");
            }
        }

        if (Schema::hasColumn('exam_proctor_assignments', 'exam_room_id')) {
            Schema::table('exam_proctor_assignments', function (Blueprint $table): void {
                $table->dropForeign('epa_direct_room_tenant_fk');
                $table->dropIndex('epa_direct_room_scope_idx');
                $table->dropColumn('exam_room_id');
            });
        }

        if (Schema::hasTable('exam_participant_room_placements')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('DROP INDEX eprp_one_active_participant_exam_unique ON exam_participant_room_placements');
            } else {
                DB::statement('DROP INDEX IF EXISTS eprp_one_active_participant_exam_unique');
            }
            Schema::dropIfExists('exam_participant_room_placements');
        }
        Schema::dropIfExists('exam_room_exam');
    }
};
