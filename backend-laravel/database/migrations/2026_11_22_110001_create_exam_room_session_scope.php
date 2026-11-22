<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLES = [
        'exam_rooms',
        'exam_sessions',
        'exam_session_rooms',
        'exam_session_room_rombels',
        'exam_participant_room_assignments',
    ];

    public function up(): void
    {
        $this->assertPrerequisites();

        Schema::create('exam_rooms', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('location', 255)->nullable();
            $table->unsignedInteger('capacity');
            $table->string('status', 16)->default('ACTIVE');
            $table->timestamps();

            $table->unique(['id', 'tenant_id'], 'exam_rooms_id_tenant_unique');
            $table->unique(['tenant_id', 'code'], 'exam_rooms_tenant_code_unique');
            $table->foreign('tenant_id', 'exam_rooms_tenant_fk')
                ->references('id')->on('tenants')->restrictOnDelete();
        });

        Schema::create('exam_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('exam_id');
            $table->string('name', 120);
            $table->unsignedSmallInteger('session_number');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('schedule_revision')->default(1);
            $table->string('status', 16)->default('SCHEDULED');
            $table->timestamps();

            $table->unique(['id', 'tenant_id', 'exam_id'], 'exam_sessions_id_tenant_exam_unique');
            $table->unique(['tenant_id', 'exam_id', 'session_number'], 'exam_sessions_exam_number_unique');
            $table->index(['tenant_id', 'exam_id', 'starts_at', 'ends_at'], 'exam_sessions_exam_window_idx');
            $table->foreign(['exam_id', 'tenant_id'], 'exam_sessions_exam_tenant_fk')
                ->references(['id', 'tenant_id'])->on('exams')->restrictOnDelete();
        });

        Schema::create('exam_session_rooms', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('exam_id');
            $table->uuid('exam_session_id');
            $table->uuid('exam_room_id');
            $table->unsignedInteger('capacity_override')->nullable();
            $table->string('status', 16)->default('ACTIVE');
            $table->timestamps();

            $table->unique(['id', 'tenant_id', 'exam_id'], 'exam_session_rooms_id_tenant_exam_unique');
            $table->unique(['tenant_id', 'exam_session_id', 'exam_room_id'], 'exam_session_rooms_scope_unique');
            $table->index(['tenant_id', 'exam_room_id', 'status'], 'exam_session_rooms_room_status_idx');
            $table->foreign(['exam_session_id', 'tenant_id', 'exam_id'], 'exam_session_rooms_session_fk')
                ->references(['id', 'tenant_id', 'exam_id'])->on('exam_sessions')->restrictOnDelete();
            $table->foreign(['exam_room_id', 'tenant_id'], 'exam_session_rooms_room_fk')
                ->references(['id', 'tenant_id'])->on('exam_rooms')->restrictOnDelete();
        });

        Schema::table('exam_participants', function (Blueprint $table): void {
            $table->uuid('rombel_id')->nullable()->after('user_id');
            $table->unique(['id', 'tenant_id', 'exam_id'], 'exam_participants_id_tenant_exam_unique');
            $table->index(['tenant_id', 'exam_id', 'rombel_id'], 'exam_participants_rombel_scope_idx');
            $table->foreign(['rombel_id', 'tenant_id'], 'exam_participants_rombel_tenant_fk')
                ->references(['id', 'tenant_id'])->on('rombels')->restrictOnDelete();
        });

        Schema::create('exam_session_room_rombels', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('exam_id');
            $table->uuid('exam_session_room_id');
            $table->uuid('rombel_id');
            $table->string('status', 16)->default('ACTIVE');
            $table->uuid('assigned_by');
            $table->timestamp('released_at')->nullable();
            $table->uuid('released_by')->nullable();
            $table->string('release_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'exam_id', 'rombel_id', 'status'], 'esrr_rombel_status_idx');
            $table->foreign(['exam_session_room_id', 'tenant_id', 'exam_id'], 'esrr_session_room_fk')
                ->references(['id', 'tenant_id', 'exam_id'])->on('exam_session_rooms')->restrictOnDelete();
            $table->foreign(['rombel_id', 'tenant_id'], 'esrr_rombel_tenant_fk')
                ->references(['id', 'tenant_id'])->on('rombels')->restrictOnDelete();
            $table->foreign(['assigned_by', 'tenant_id'], 'esrr_assigner_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['released_by', 'tenant_id'], 'esrr_releaser_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });

        Schema::create('exam_participant_room_assignments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->uuid('exam_id');
            $table->uuid('participant_id');
            $table->uuid('exam_session_room_id');
            $table->string('status', 16)->default('ACTIVE');
            $table->uuid('assigned_by');
            $table->timestamp('assigned_at');
            $table->timestamp('released_at')->nullable();
            $table->uuid('released_by')->nullable();
            $table->string('release_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'exam_id', 'exam_session_room_id', 'status'], 'epra_room_status_idx');
            $table->index(['tenant_id', 'participant_id', 'status'], 'epra_participant_status_idx');
            $table->foreign(['participant_id', 'tenant_id', 'exam_id'], 'epra_participant_scope_fk')
                ->references(['id', 'tenant_id', 'exam_id'])->on('exam_participants')->restrictOnDelete();
            $table->foreign(['exam_session_room_id', 'tenant_id', 'exam_id'], 'epra_session_room_scope_fk')
                ->references(['id', 'tenant_id', 'exam_id'])->on('exam_session_rooms')->restrictOnDelete();
            $table->foreign(['assigned_by', 'tenant_id'], 'epra_assigner_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
            $table->foreign(['released_by', 'tenant_id'], 'epra_releaser_tenant_fk')
                ->references(['id', 'tenant_id'])->on('users')->restrictOnDelete();
        });

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement(
                "CREATE UNIQUE INDEX esrr_one_active_rombel_unique ON exam_session_room_rombels ((CASE WHEN status = 'ACTIVE' THEN CONCAT(exam_id, ':', rombel_id) ELSE NULL END))"
            );
            DB::statement(
                "CREATE UNIQUE INDEX epra_one_active_participant_unique ON exam_participant_room_assignments ((CASE WHEN status = 'ACTIVE' THEN participant_id ELSE NULL END))"
            );
        } else {
            DB::statement(
                "CREATE UNIQUE INDEX esrr_one_active_rombel_unique ON exam_session_room_rombels (exam_id, rombel_id) WHERE status = 'ACTIVE'"
            );
            DB::statement(
                "CREATE UNIQUE INDEX epra_one_active_participant_unique ON exam_participant_room_assignments (participant_id) WHERE status = 'ACTIVE'"
            );
        }

        Schema::table('exam_proctor_assignments', function (Blueprint $table): void {
            $table->uuid('exam_session_room_id')->nullable()->after('exam_id');
            $table->unsignedInteger('schedule_revision')->nullable()->after('exam_session_room_id');
            $table->dropUnique('exam_proctor_assignment_unique');
            $table->index(
                ['tenant_id', 'exam_session_room_id', 'proctor_user_id', 'status'],
                'epa_room_proctor_lookup_idx'
            );
            $table->index(
                ['tenant_id', 'exam_session_room_id', 'status', 'starts_at', 'expires_at'],
                'epa_room_capability_idx'
            );
            $table->foreign(['exam_session_room_id', 'tenant_id', 'exam_id'], 'epa_session_room_scope_fk')
                ->references(['id', 'tenant_id', 'exam_id'])->on('exam_session_rooms')->restrictOnDelete();
        });

        DB::table('exam_proctor_assignments')
            ->whereNull('exam_session_room_id')
            ->whereIn('status', ['ACTIVE', 'PENDING', 'CONFIRMED'])
            ->update(['status' => 'NEEDS_REVIEW']);

        if ($driver === 'mysql') {
            DB::statement("CREATE UNIQUE INDEX epa_one_open_assignment_scope_unique ON exam_proctor_assignments ((CASE WHEN exam_session_room_id IS NOT NULL AND revoked_at IS NULL AND status IN ('PENDING', 'CONFIRMED', 'NEEDS_REVIEW') THEN CONCAT(exam_session_room_id, ':', proctor_user_id) ELSE NULL END))");
        } else {
            DB::statement("CREATE UNIQUE INDEX epa_one_open_assignment_scope_unique ON exam_proctor_assignments (exam_session_room_id, proctor_user_id) WHERE exam_session_room_id IS NOT NULL AND revoked_at IS NULL AND status IN ('PENDING', 'CONFIRMED', 'NEEDS_REVIEW')");
        }
    }

    public function down(): void
    {
        $this->assertPrerequisites(false);

        if (DB::getDriverName() === 'mysql'
            && (int) DB::selectOne('SELECT @@FOREIGN_KEY_CHECKS AS enabled')->enabled !== 1) {
            throw new RuntimeException('Stage 4B rollback requires MySQL foreign-key checks to remain enabled.');
        }

        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException("Stage 4B rollback refused: {$table} contains room/session history. Preserve the records and use an approved forward-only recovery.");
            }
        }

        if (DB::table('exam_participants')->whereNotNull('rombel_id')->exists()
            || DB::table('exam_proctor_assignments')->whereNotNull('exam_session_room_id')->exists()
            || DB::table('exam_proctor_assignments')->whereNotNull('schedule_revision')->exists()) {
            throw new RuntimeException('Stage 4B rollback refused: participant or proctor scope history is present.');
        }

        if (DB::getDriverName() === 'mysql') {
            if (DB::table('information_schema.statistics')->where('table_schema', DB::getDatabaseName())->where('table_name', 'exam_proctor_assignments')->where('index_name', 'epa_one_open_assignment_scope_unique')->exists()) {
                DB::statement('DROP INDEX epa_one_open_assignment_scope_unique ON exam_proctor_assignments');
            }
        } else {
            DB::statement('DROP INDEX IF EXISTS epa_one_open_assignment_scope_unique');
        }

        Schema::table('exam_proctor_assignments', function (Blueprint $table): void {
            $table->dropForeign('epa_session_room_scope_fk');
            $table->dropIndex('epa_room_capability_idx');
            $table->dropIndex('epa_room_proctor_lookup_idx');
            $table->dropColumn(['exam_session_room_id', 'schedule_revision']);
            $table->unique(['tenant_id', 'exam_id', 'proctor_user_id'], 'exam_proctor_assignment_unique');
        });

        // Remove MySQL functional indexes and child tables before dropping
        // parent candidate keys. In particular, epra_participant_scope_fk
        // references exam_participants(id, tenant_id, exam_id); keeping this
        // child-first order preserves every tenant-aware FK in the base schema.
        if (DB::getDriverName() === 'mysql') {
            if (DB::table('information_schema.statistics')->where('table_schema', DB::getDatabaseName())->where('table_name', 'exam_session_room_rombels')->where('index_name', 'esrr_one_active_rombel_unique')->exists()) {
                DB::statement('DROP INDEX esrr_one_active_rombel_unique ON exam_session_room_rombels');
            }
            if (DB::table('information_schema.statistics')->where('table_schema', DB::getDatabaseName())->where('table_name', 'exam_participant_room_assignments')->where('index_name', 'epra_one_active_participant_unique')->exists()) {
                DB::statement('DROP INDEX epra_one_active_participant_unique ON exam_participant_room_assignments');
            }
        } else {
            DB::statement('DROP INDEX IF EXISTS esrr_one_active_rombel_unique');
            DB::statement('DROP INDEX IF EXISTS epra_one_active_participant_unique');
        }

        foreach ([
            'exam_participant_room_assignments',
            'exam_session_room_rombels',
            'exam_session_rooms',
            'exam_sessions',
            'exam_rooms',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        // The base migration's tenant_id FK remains intact. It receives its
        // own support index before the Stage 4B composite lookup index is removed.
        if (! Schema::hasIndex('exam_participants', ['tenant_id'])) {
            Schema::table('exam_participants', function (Blueprint $table): void {
                $table->index('tenant_id', 'exam_participants_tenant_fk_support_idx');
            });
        }

        Schema::table('exam_participants', function (Blueprint $table): void {
            // The child placement FK is already gone with its child table.
            $table->dropForeign('exam_participants_rombel_tenant_fk');
            $table->dropIndex('exam_participants_rombel_scope_idx');
            $table->dropUnique('exam_participants_id_tenant_exam_unique');
            $table->dropColumn('rombel_id');
        });
    }

    private function assertPrerequisites(bool $forUp = true): void
    {
        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('Stage 4B supports only MySQL 8+ and SQLite test runtime.');
        }

        foreach (['tenants', 'users', 'exams', 'rombels', 'exam_participants', 'exam_proctor_assignments'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Stage 4B prerequisite table missing: {$table}.");
            }
        }

        foreach ([
            ['exams', 'id'], ['exams', 'tenant_id'],
            ['rombels', 'id'], ['rombels', 'tenant_id'],
            ['exam_participants', 'id'], ['exam_participants', 'tenant_id'], ['exam_participants', 'exam_id'], ['exam_participants', 'user_id'],
            ['exam_proctor_assignments', 'tenant_id'], ['exam_proctor_assignments', 'exam_id'], ['exam_proctor_assignments', 'proctor_user_id'],
        ] as [$table, $column]) {
            if (! Schema::hasColumn($table, $column)) {
                throw new RuntimeException("Stage 4B prerequisite column missing: {$table}.{$column}.");
            }
        }

        if ($driver === 'mysql') {
            $tables = ['tenants', 'users', 'exams', 'rombels', 'exam_participants', 'exam_proctor_assignments'];
            $placeholders = implode(',', array_fill(0, count($tables), '?'));
            $rows = DB::select(
                "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ({$placeholders})",
                $tables
            );
            if (count($rows) !== count($tables)
                || collect($rows)->contains(fn ($row) => strtolower((string) $row->ENGINE) !== 'innodb')) {
                throw new RuntimeException('Stage 4B requires InnoDB on all prerequisite tables.');
            }
        }

        if ($forUp) {
            foreach (self::TABLES as $table) {
                if (Schema::hasTable($table)) {
                    throw new RuntimeException("Stage 4B partial state: {$table} already exists; no DDL executed.");
                }
            }
            if (Schema::hasColumn('exam_participants', 'rombel_id')
                || Schema::hasColumn('exam_proctor_assignments', 'exam_session_room_id')
                || Schema::hasColumn('exam_proctor_assignments', 'schedule_revision')) {
                throw new RuntimeException('Stage 4B partial state: participant/proctor scope columns already exist; no DDL executed.');
            }
            if (! Schema::hasIndex('rombels', 'rombels_id_tenant_unique')
                || ! Schema::hasIndex('exams', 'exams_id_tenant_id_unique')
                || ! Schema::hasIndex('users', 'users_id_tenant_unique')
                || ! Schema::hasIndex('exam_proctor_assignments', 'exam_proctor_assignment_unique')) {
                throw new RuntimeException('Stage 4B required composite candidate key or legacy assignment index is missing.');
            }
        }
    }
};
