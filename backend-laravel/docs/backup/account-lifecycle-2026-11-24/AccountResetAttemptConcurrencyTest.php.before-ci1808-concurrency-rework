<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\UserManagement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AccountResetAttemptConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    private Tenant $tenant;
    private User $admin;
    private User $student;
    private \App\Models\Exam $exam;
    private \App\Models\ExamParticipant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Reset Race Tenant', 'code' => 'RACE01']);
        app(TenantContext::class)->setTenantId((string) $this->tenant->id);
        TenantContext::getInstance()?->setTenantId((string) $this->tenant->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $permission = Permission::firstOrCreate(['name' => 'user.manage'], ['label' => 'Kelola akun']);
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
        $studentRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Race Admin',
            'username' => 'race.admin',
            'email' => 'race.admin@test.local',
            'password' => Hash::make('admin-password-strong'),
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Race Student',
            'username' => 'race.student',
            'email' => 'race.student@test.local',
            'password' => Hash::make('old-password-strong'),
        ]);
        $this->student->roles()->attach($studentRole);

        $year = \App\Models\AcademicYear::create([
            'tenant_id' => $this->tenant->id,
            'name' => '2026/2027',
        ]);
        $assessment = \App\Models\Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Reset Race Assessment',
            'assessment_type' => 'DAILY',
            'created_by' => $this->admin->id,
        ]);
        $this->exam = \App\Models\Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Reset Race Exam',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
            'locked_at' => now(),
        ]);
        $this->participant = \App\Models\ExamParticipant::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'user_id' => $this->student->id,
            'status' => 'STARTED',
        ]);

        config(['database.connections.mysql_concurrent' => config('database.connections.mysql')]);
        DB::purge('mysql_concurrent');
    }

    protected function tearDown(): void
    {
        DB::disconnect('mysql_concurrent');
        DB::purge('mysql_concurrent');
        TenantContext::getInstance()?->setTenantId(null);
        app(TenantContext::class)->setTenantId(null);

        parent::tearDown();
    }

    public function test_reset_serializes_behind_attempt_start_locks_on_two_mysql_connections_without_deadlock(): void
    {
        $primary = DB::connection('mysql');
        $attemptStart = DB::connection('mysql_concurrent');

        $driver = $primary->getDriverName();
        $this->assertSame('mysql', $driver, 'This concurrency proof must run against MySQL/InnoDB.');

        $deadlocksBeforeRow = $primary->selectOne("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'");
        $deadlocksBefore = (int) ($deadlocksBeforeRow->Value ?? 0);

        $primary->statement('SET SESSION innodb_lock_wait_timeout = 2');
        $attemptStart->statement('SET SESSION innodb_lock_wait_timeout = 5');

        // Simulate the actual Attempt-start mutex order from ExamAttemptController:
        // Exam -> Participant -> Attempt query space -> User.
        $attemptStart->beginTransaction();
        $attemptStart->table('exams')->where('id', $this->exam->id)->lockForUpdate()->first();
        $attemptStart->table('exam_participants')->where('id', $this->participant->id)->lockForUpdate()->first();
        $attemptStart->table('users')->where('id', $this->student->id)->lockForUpdate()->first();

        Auth::login($this->admin);
        $resetWaitedOnStart = false;

        try {
            Livewire::test(UserManagement::class)->call('resetUserPassword', $this->student->id);
        } catch (QueryException $exception) {
            $resetWaitedOnStart = str_contains(strtolower($exception->getMessage()), 'lock wait timeout');
        }

        $this->assertTrue(
            $resetWaitedOnStart,
            'Reset must wait at the Exam lock while Attempt start holds the parent-first lock chain.'
        );
        $this->assertSame(0, $primary->transactionLevel(), 'The timed-out reset transaction must roll back cleanly.');

        // Attempt start wins the race and commits its official IN_PROGRESS row.
        $attemptId = (string) Str::uuid();
        $now = now();
        $attemptStart->table('exam_attempts')->insert([
            'id' => $attemptId,
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $this->participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => $now,
            'expires_at' => now()->addHour(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $attemptStart->commit();

        // Once Attempt start commits, reset retries against fresh READ COMMITTED
        // state and must reject without changing account or CBT lifecycle rows.
        $component = Livewire::test(UserManagement::class)
            ->call('resetUserPassword', $this->student->id)
            ->assertSet('showTemporaryPasswordModal', false)
            ->assertSet('temporaryPassword', null);

        $this->assertFalse((bool) $this->student->fresh()->must_change_password);
        $this->assertTrue((bool) $this->student->fresh()->is_active);
        $this->assertSame(1, (int) $this->student->fresh()->auth_version);
        $this->assertSame('IN_PROGRESS', $primary->table('exam_attempts')->where('id', $attemptId)->value('status'));

        $audit = \App\Models\AuditLog::where('action', 'RESET_LOCAL_USER_PASSWORD')
            ->where('entity_id', $this->student->id)
            ->firstOrFail();
        $this->assertSame('REJECTED', $audit->new_values['outcome']);
        $this->assertSame('ACTIVE_ATTEMPT_IN_PROGRESS', $audit->new_values['reason']);

        $deadlocksAfterRow = $primary->selectOne("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'");
        $deadlocksAfter = (int) ($deadlocksAfterRow->Value ?? 0);
        $this->assertSame($deadlocksBefore, $deadlocksAfter, 'The shared parent-first lock order must not introduce an InnoDB deadlock.');
    }
}
