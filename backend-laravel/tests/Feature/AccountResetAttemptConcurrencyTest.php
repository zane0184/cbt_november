<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\UserManagement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AccountResetAttemptConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private User $student;
    private \App\Models\Exam $exam;
    private \App\Models\ExamParticipant $participant;
    private array $createdRoleIds = [];
    private array $createdPermissionIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Reset Race Tenant', 'code' => 'RACE01']);
        app(TenantContext::class)->setTenantId((string) $this->tenant->id);
        TenantContext::getInstance()?->setTenantId((string) $this->tenant->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        if ($adminRole->wasRecentlyCreated) {
            $this->createdRoleIds[] = (string) $adminRole->id;
        }

        $permission = Permission::firstOrCreate(['name' => 'user.manage'], ['label' => 'Kelola akun']);
        if ($permission->wasRecentlyCreated) {
            $this->createdPermissionIds[] = (string) $permission->id;
        }
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);

        $studentRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        if ($studentRole->wasRecentlyCreated) {
            $this->createdRoleIds[] = (string) $studentRole->id;
        }

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
    }

    private function spawnConcurrentMysqlProcess(string $script, array $payload): array
    {
        $scriptPath = tempnam(sys_get_temp_dir(), 'cbt-account-race-');
        file_put_contents($scriptPath, $script);

        $database = config('database.connections.mysql');
        $process = proc_open(
            [PHP_BINARY, $scriptPath, base64_encode(json_encode(array_merge([
                'host' => $database['host'],
                'port' => $database['port'],
                'database' => $database['database'],
                'username' => $database['username'],
                'password' => $database['password'],
            ], $payload), JSON_THROW_ON_ERROR))],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path()
        );

        if (!is_resource($process)) {
            @unlink($scriptPath);
            throw new \RuntimeException('Could not start independent MySQL transaction process.');
        }

        fclose($pipes[0]);

        return [$process, $pipes, $scriptPath];
    }

    private function lockExamParentsOnPrimaryConnection(): void
    {
        $connection = DB::connection('mysql');
        $connection->table('exams')->where('id', $this->exam->id)->lockForUpdate()->first();
        $connection->table('exam_participants')->where('id', $this->participant->id)->lockForUpdate()->first();
        $connection->table('exam_attempts')->where('participant_id', $this->participant->id)
            ->orderBy('id')->lockForUpdate()->get();
    }

    private function attemptStartWaitingScript(): string
    {
        return <<<'PHP'
<?php
$payload = json_decode(base64_decode($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$pdo = new PDO(
    'mysql:host='.$payload['host'].';port='.$payload['port'].';dbname='.$payload['database'].';charset=utf8mb4',
    $payload['username'],
    $payload['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
);
$pdo->exec('SET SESSION innodb_lock_wait_timeout = 12');
$pdo->beginTransaction();
echo "START_REQUEST_READY\n";
flush();

$exam = $pdo->prepare('SELECT id FROM exams WHERE id = ? AND tenant_id = ? FOR UPDATE');
$exam->execute([$payload['exam_id'], $payload['tenant_id']]);
$exam->fetch(PDO::FETCH_ASSOC);

$participant = $pdo->prepare('SELECT id FROM exam_participants WHERE id = ? AND exam_id = ? AND user_id = ? AND tenant_id = ? FOR UPDATE');
$participant->execute([$payload['participant_id'], $payload['exam_id'], $payload['student_id'], $payload['tenant_id']]);
$participant->fetch(PDO::FETCH_ASSOC);

$expiry = $pdo->prepare("SELECT id FROM exam_attempts WHERE participant_id = ? AND status = 'IN_PROGRESS' AND (expires_at IS NULL OR expires_at <= NOW()) FOR UPDATE");
$expiry->execute([$payload['participant_id']]);
$expiry->fetchAll(PDO::FETCH_ASSOC);

$active = $pdo->prepare("SELECT id FROM exam_attempts WHERE participant_id = ? AND status = 'IN_PROGRESS' AND expires_at > NOW() ORDER BY id FOR UPDATE");
$active->execute([$payload['participant_id']]);
$active->fetchAll(PDO::FETCH_ASSOC);

$user = $pdo->prepare('SELECT id, is_active, must_change_password FROM users WHERE id = ? AND tenant_id = ? FOR UPDATE');
$user->execute([$payload['student_id'], $payload['tenant_id']]);
$account = $user->fetch(PDO::FETCH_ASSOC);

if (!$account || !(bool) $account['is_active'] || (bool) $account['must_change_password']) {
    $pdo->commit();
    echo !$account || !(bool) $account['is_active']
        ? "ACCOUNT_INACTIVE_REJECTED\n"
        : "PASSWORD_CHANGE_REQUIRED_REJECTED\n";
    flush();
    exit(0);
}

$now = date('Y-m-d H:i:s');
$insert = $pdo->prepare("INSERT INTO exam_attempts (id, tenant_id, exam_id, participant_id, attempt_number, status, started_at, expires_at, created_at, updated_at) VALUES (?, ?, ?, ?, 1, 'IN_PROGRESS', ?, DATE_ADD(?, INTERVAL 1 HOUR), ?, ?)");
$insert->execute([
    $payload['attempt_id'], $payload['tenant_id'], $payload['exam_id'],
    $payload['participant_id'], $now, $now, $now, $now,
]);
$pdo->commit();
echo "ATTEMPT_STARTED\n";
flush();
PHP;
    }

    protected function tearDown(): void
    {
        $connection = DB::connection('mysql');

        // This test commits its fixture so the independent MySQL process can
        // observe and lock it. Remove only this test tenant's committed rows,
        // then leave an empty transaction for RefreshDatabase's normal teardown.
        if (isset($this->tenant) && $connection->getDriverName() === 'mysql') {
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }

            $tenantId = (string) $this->tenant->id;
            $userIds = $connection->table('users')->where('tenant_id', $tenantId)->pluck('id')->all();
            $connection->statement('SET FOREIGN_KEY_CHECKS=0');

            $tenantTables = $connection->select(
                'SELECT DISTINCT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ?',
                ['tenant_id']
            );
            foreach ($tenantTables as $row) {
                $table = str_replace('`', '``', (string) $row->TABLE_NAME);
                if ($table !== 'tenants') {
                    $connection->statement("DELETE FROM `{$table}` WHERE tenant_id = ?", [$tenantId]);
                }
            }

            if ($userIds !== []) {
                $userTables = $connection->select(
                    'SELECT DISTINCT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ?',
                    ['user_id']
                );
                foreach ($userTables as $row) {
                    $table = str_replace('`', '``', (string) $row->TABLE_NAME);
                    if (!in_array($table, ['users', 'tenants'], true)) {
                        $connection->table($table)->whereIn('user_id', $userIds)->delete();
                    }
                }

                $modelTables = $connection->select(
                    'SELECT DISTINCT TABLE_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = ?',
                    ['model_id']
                );
                foreach ($modelTables as $row) {
                    $table = str_replace('`', '``', (string) $row->TABLE_NAME);
                    $connection->table($table)->whereIn('model_id', $userIds)->delete();
                }
            }

            // Role/permission fixtures are global (no tenant_id). Delete only
            // rows this test actually created, and detach their pivot records.
            foreach ($this->createdPermissionIds as $permissionId) {
                $permission = Permission::withoutGlobalScopes()->find($permissionId);
                if ($permission) {
                    $permission->roles()->detach();
                    $permission->delete();
                }
            }

            foreach ($this->createdRoleIds as $roleId) {
                $role = Role::withoutGlobalScopes()->find($roleId);
                if ($role) {
                    $role->permissions()->detach();
                    $role->delete();
                }
            }

            $connection->table('tenants')->where('id', $tenantId)->delete();
            $connection->statement('SET FOREIGN_KEY_CHECKS=1');
            $connection->beginTransaction();
        }

        DB::disconnect('mysql_concurrent');
        DB::purge('mysql_concurrent');
        TenantContext::getInstance()?->setTenantId(null);
        app(TenantContext::class)->setTenantId(null);

        parent::tearDown();
    }

    public function test_reset_waits_for_a_concurrent_mysql_attempt_start_transaction_and_rejects_without_mutation(): void
    {
        $primary = DB::connection('mysql');
        $this->assertSame('mysql', $primary->getDriverName(), 'Concurrency proof requires MySQL 8/InnoDB.');

        // Publish the fixture to the second process and make the primary
        // connection's next transaction READ COMMITTED, matching the lifecycle
        // operation's post-lock active-Attempt recheck requirement.
        $primary->commit();
        $primary->statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $primary->statement('SET SESSION innodb_lock_wait_timeout = 8');
        $primary->beginTransaction();

        $deadlocksBefore = (int) ($primary->selectOne("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")->Value ?? 0);
        $attemptId = (string) Str::uuid();
        $now = now()->toDateTimeString();
        $database = config('database.connections.mysql');

        $script = <<<'PHP'
<?php
$payload = json_decode(base64_decode($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$pdo = new PDO(
    'mysql:host='.$payload['host'].';port='.$payload['port'].';dbname='.$payload['database'].';charset=utf8mb4',
    $payload['username'],
    $payload['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
);
$pdo->exec('SET SESSION innodb_lock_wait_timeout = 8');
$pdo->beginTransaction();

$exam = $pdo->prepare('SELECT id FROM exams WHERE id = ? AND tenant_id = ? FOR UPDATE');
$exam->execute([$payload['exam_id'], $payload['tenant_id']]);
$exam->fetch(PDO::FETCH_ASSOC);

$participant = $pdo->prepare('SELECT id FROM exam_participants WHERE id = ? AND exam_id = ? AND user_id = ? AND tenant_id = ? FOR UPDATE');
$participant->execute([$payload['participant_id'], $payload['exam_id'], $payload['student_id'], $payload['tenant_id']]);
$participant->fetch(PDO::FETCH_ASSOC);

// Same parent-first attempt lock sequence as ExamAttemptController::store.
$expiry = $pdo->prepare("SELECT id FROM exam_attempts WHERE participant_id = ? AND status = 'IN_PROGRESS' AND (expires_at IS NULL OR expires_at <= NOW()) FOR UPDATE");
$expiry->execute([$payload['participant_id']]);
$expiry->fetchAll(PDO::FETCH_ASSOC);

$active = $pdo->prepare("SELECT id FROM exam_attempts WHERE participant_id = ? AND status = 'IN_PROGRESS' AND expires_at > NOW() ORDER BY id FOR UPDATE");
$active->execute([$payload['participant_id']]);
$active->fetchAll(PDO::FETCH_ASSOC);

$user = $pdo->prepare('SELECT id FROM users WHERE id = ? AND tenant_id = ? FOR UPDATE');
$user->execute([$payload['student_id'], $payload['tenant_id']]);
$user->fetch(PDO::FETCH_ASSOC);

$insert = $pdo->prepare("INSERT INTO exam_attempts (id, tenant_id, exam_id, participant_id, attempt_number, status, started_at, expires_at, created_at, updated_at) VALUES (?, ?, ?, ?, 1, 'IN_PROGRESS', ?, DATE_ADD(?, INTERVAL 1 HOUR), ?, ?)");
$insert->execute([
    $payload['attempt_id'], $payload['tenant_id'], $payload['exam_id'],
    $payload['participant_id'], $payload['now'], $payload['now'],
    $payload['now'], $payload['now'],
]);

echo "LOCKED_AND_ATTEMPT_INSERTED\n";
flush();
usleep(1200000);
$pdo->commit();
echo "COMMITTED\n";
flush();
PHP;

        $scriptPath = tempnam(sys_get_temp_dir(), 'cbt-attempt-start-');
        file_put_contents($scriptPath, $script);
        $payload = [
            'host' => $database['host'],
            'port' => $database['port'],
            'database' => $database['database'],
            'username' => $database['username'],
            'password' => $database['password'],
            'tenant_id' => (string) $this->tenant->id,
            'exam_id' => (string) $this->exam->id,
            'participant_id' => (string) $this->participant->id,
            'student_id' => (string) $this->student->id,
            'attempt_id' => $attemptId,
            'now' => $now,
        ];

        $process = proc_open(
            [PHP_BINARY, $scriptPath, base64_encode(json_encode($payload, JSON_THROW_ON_ERROR))],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path()
        );
        $this->assertIsResource($process, 'Could not start independent MySQL attempt-start process.');
        fclose($pipes[0]);

        $ready = trim((string) fgets($pipes[1]));
        $this->assertSame('LOCKED_AND_ATTEMPT_INSERTED', $ready, 'Second MySQL connection must hold the real parent-first lock chain before reset begins.');

        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)
            ->call('resetUserPassword', $this->student->id)
            ->assertSet('showTemporaryPasswordModal', false)
            ->assertSet('temporaryPassword', null);

        $committed = trim((string) fgets($pipes[1]));
        fclose($pipes[1]);
        $childError = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);
        @unlink($scriptPath);

        $this->assertSame('COMMITTED', $committed, 'The concurrent attempt-start transaction must commit.');
        $this->assertSame(0, $exitCode, 'Concurrent MySQL process failed: '.$childError);

        $this->assertTrue((bool) $this->student->fresh()->is_active);
        $this->assertFalse((bool) $this->student->fresh()->must_change_password);
        $this->assertSame(1, (int) $this->student->fresh()->auth_version);
        $this->assertSame('IN_PROGRESS', $primary->table('exam_attempts')->where('id', $attemptId)->value('status'));
        $this->assertSame(0, $primary->table('exam_attempt_answers')->where('exam_attempt_id', $attemptId)->count());
        $this->assertSame(0, $primary->table('exam_attempt_scores')->where('attempt_id', $attemptId)->count());
        $this->assertSame(0, $primary->table('device_leases')->where('exam_attempt_id', $attemptId)->count());
        $this->assertSame(1, (int) $primary->table('exam_attempts')->where('id', $attemptId)->value('fence_generation'));

        $audit = \App\Models\AuditLog::where('action', 'RESET_LOCAL_USER_PASSWORD')
            ->where('entity_id', $this->student->id)
            ->firstOrFail();
        $this->assertSame('REJECTED', $audit->new_values['outcome']);
        $this->assertSame('ACTIVE_ATTEMPT_IN_PROGRESS', $audit->new_values['reason']);

        $deadlocksAfter = (int) ($primary->selectOne("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")->Value ?? 0);
        $this->assertSame($deadlocksBefore, $deadlocksAfter, 'The parent-first lock order must not produce an InnoDB deadlock.');
    }

    public function test_deactivation_waits_for_concurrent_mysql_attempt_start_and_rejects_without_mutation(): void
    {
        $primary = DB::connection('mysql');
        $this->assertSame('mysql', $primary->getDriverName());
        $this->student->createToken('deactivation-race-token');
        DB::table('sessions')->insert([
            'id' => 'deactivation-race-session', 'user_id' => $this->student->id,
            'payload' => 'encrypted-session', 'last_activity' => now()->timestamp,
        ]);

        $primary->commit();
        $primary->statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $primary->statement('SET SESSION innodb_lock_wait_timeout = 12');
        $primary->beginTransaction();

        $deadlocksBefore = (int) ($primary->selectOne("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")->Value ?? 0);
        $attemptId = (string) Str::uuid();
        $now = now()->toDateTimeString();
        $database = config('database.connections.mysql');
        $script = <<<'PHP'
<?php
$payload = json_decode(base64_decode($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$pdo = new PDO(
    'mysql:host='.$payload['host'].';port='.$payload['port'].';dbname='.$payload['database'].';charset=utf8mb4',
    $payload['username'],
    $payload['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
);
$pdo->exec('SET SESSION innodb_lock_wait_timeout = 12');
$pdo->beginTransaction();

$exam = $pdo->prepare('SELECT id FROM exams WHERE id = ? AND tenant_id = ? FOR UPDATE');
$exam->execute([$payload['exam_id'], $payload['tenant_id']]);
$exam->fetch(PDO::FETCH_ASSOC);
$participant = $pdo->prepare('SELECT id FROM exam_participants WHERE id = ? AND exam_id = ? AND user_id = ? AND tenant_id = ? FOR UPDATE');
$participant->execute([$payload['participant_id'], $payload['exam_id'], $payload['student_id'], $payload['tenant_id']]);
$participant->fetch(PDO::FETCH_ASSOC);
$expiry = $pdo->prepare("SELECT id FROM exam_attempts WHERE participant_id = ? AND status = 'IN_PROGRESS' AND (expires_at IS NULL OR expires_at <= NOW()) FOR UPDATE");
$expiry->execute([$payload['participant_id']]);
$expiry->fetchAll(PDO::FETCH_ASSOC);
$active = $pdo->prepare("SELECT id FROM exam_attempts WHERE participant_id = ? AND status = 'IN_PROGRESS' AND expires_at > NOW() ORDER BY id FOR UPDATE");
$active->execute([$payload['participant_id']]);
$active->fetchAll(PDO::FETCH_ASSOC);
$user = $pdo->prepare('SELECT id FROM users WHERE id = ? AND tenant_id = ? FOR UPDATE');
$user->execute([$payload['student_id'], $payload['tenant_id']]);
$user->fetch(PDO::FETCH_ASSOC);

$now = date('Y-m-d H:i:s');
$insert = $pdo->prepare("INSERT INTO exam_attempts (id, tenant_id, exam_id, participant_id, attempt_number, status, started_at, expires_at, created_at, updated_at) VALUES (?, ?, ?, ?, 1, 'IN_PROGRESS', ?, DATE_ADD(?, INTERVAL 1 HOUR), ?, ?)");
$insert->execute([
    $payload['attempt_id'], $payload['tenant_id'], $payload['exam_id'],
    $payload['participant_id'], $now, $now, $now, $now,
]);
echo "ATTEMPT_LOCKED_AND_INSERTED\n";
flush();
usleep(1200000);
$pdo->commit();
echo "ATTEMPT_COMMITTED\n";
flush();
PHP;
        [$process, $pipes, $scriptPath] = $this->spawnConcurrentMysqlProcess($script, [
            'tenant_id' => (string) $this->tenant->id,
            'exam_id' => (string) $this->exam->id,
            'participant_id' => (string) $this->participant->id,
            'student_id' => (string) $this->student->id,
            'attempt_id' => $attemptId,
        ]);

        try {
            $this->assertSame('ATTEMPT_LOCKED_AND_INSERTED', trim((string) fgets($pipes[1])));
            $this->actingAs($this->admin);
            Livewire::test(UserManagement::class)->call('deactivateUser', $this->student->id);
            $this->assertSame('ATTEMPT_COMMITTED', trim((string) fgets($pipes[1])));
            $childError = stream_get_contents($pipes[2]);
            $exitCode = proc_close($process);
        } finally {
            if (is_resource($pipes[1])) fclose($pipes[1]);
            if (is_resource($pipes[2])) fclose($pipes[2]);
            @unlink($scriptPath);
        }

        $this->assertSame(0, $exitCode, 'Concurrent Attempt-start process failed: '.$childError);
        $this->assertTrue((bool) $this->student->fresh()->is_active);
        $this->assertSame(1, (int) $this->student->fresh()->auth_version);
        $this->assertSame(1, DB::table('personal_access_tokens')->where('tokenable_id', $this->student->id)->count());
        $this->assertDatabaseHas('sessions', ['id' => 'deactivation-race-session']);
        $this->assertSame('IN_PROGRESS', $primary->table('exam_attempts')->where('id', $attemptId)->value('status'));
        $this->assertSame(0, $primary->table('exam_attempt_answers')->where('exam_attempt_id', $attemptId)->count());
        $this->assertSame(0, $primary->table('exam_attempt_scores')->where('attempt_id', $attemptId)->count());
        $this->assertSame(0, $primary->table('device_leases')->where('exam_attempt_id', $attemptId)->count());
        $this->assertSame(1, (int) $primary->table('exam_attempts')->where('id', $attemptId)->value('fence_generation'));

        $audit = \App\Models\AuditLog::where('action', 'DEACTIVATE_LOCAL_USER')
            ->where('entity_id', $this->student->id)->firstOrFail();
        $this->assertSame('REJECTED', $audit->new_values['outcome']);
        $this->assertSame('ACTIVE_ATTEMPT_IN_PROGRESS', $audit->new_values['reason']);
        $deadlocksAfter = (int) ($primary->selectOne("SHOW GLOBAL STATUS LIKE 'Innodb_deadlocks'")->Value ?? 0);
        $this->assertSame($deadlocksBefore, $deadlocksAfter);
    }

    public function test_reset_wins_exam_parent_locks_and_concurrent_attempt_start_is_rejected_for_password_change(): void
    {
        $primary = DB::connection('mysql');
        $primary->commit();
        $primary->statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $primary->statement('SET SESSION innodb_lock_wait_timeout = 12');
        $primary->beginTransaction();
        $this->lockExamParentsOnPrimaryConnection();
        $attemptId = (string) Str::uuid();

        [$process, $pipes, $scriptPath] = $this->spawnConcurrentMysqlProcess($this->attemptStartWaitingScript(), [
            'tenant_id' => (string) $this->tenant->id,
            'exam_id' => (string) $this->exam->id,
            'participant_id' => (string) $this->participant->id,
            'student_id' => (string) $this->student->id,
            'attempt_id' => $attemptId,
        ]);

        try {
            $this->assertSame('START_REQUEST_READY', trim((string) fgets($pipes[1])));
            $this->actingAs($this->admin);
            $component = Livewire::test(UserManagement::class)
                ->call('resetUserPassword', $this->student->id)
                ->assertSet('showTemporaryPasswordModal', true);
            $temporaryPassword = $component->get('temporaryPassword');
            $primary->commit();

            $childResult = trim((string) fgets($pipes[1]));
            $childError = stream_get_contents($pipes[2]);
            $exitCode = proc_close($process);
        } finally {
            if (is_resource($pipes[1])) fclose($pipes[1]);
            if (is_resource($pipes[2])) fclose($pipes[2]);
            @unlink($scriptPath);
        }

        $this->assertSame('PASSWORD_CHANGE_REQUIRED_REJECTED', $childResult);
        $this->assertSame(0, $exitCode, 'Concurrent Attempt-start process failed: '.$childError);
        $this->assertTrue(Hash::check($temporaryPassword, $this->student->fresh()->password));
        $this->assertTrue((bool) $this->student->fresh()->must_change_password);
        $this->assertSame(2, (int) $this->student->fresh()->auth_version);
        $this->assertSame(0, $primary->table('exam_attempts')->where('participant_id', $this->participant->id)->count());
    }

    public function test_deactivation_wins_exam_parent_locks_and_concurrent_attempt_start_is_rejected_for_inactive_account(): void
    {
        $primary = DB::connection('mysql');
        $primary->commit();
        $primary->statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $primary->statement('SET SESSION innodb_lock_wait_timeout = 12');
        $primary->beginTransaction();
        $this->lockExamParentsOnPrimaryConnection();

        [$process, $pipes, $scriptPath] = $this->spawnConcurrentMysqlProcess($this->attemptStartWaitingScript(), [
            'tenant_id' => (string) $this->tenant->id,
            'exam_id' => (string) $this->exam->id,
            'participant_id' => (string) $this->participant->id,
            'student_id' => (string) $this->student->id,
            'attempt_id' => (string) Str::uuid(),
        ]);

        try {
            $this->assertSame('START_REQUEST_READY', trim((string) fgets($pipes[1])));
            $this->actingAs($this->admin);
            Livewire::test(UserManagement::class)->call('deactivateUser', $this->student->id);
            $primary->commit();

            $childResult = trim((string) fgets($pipes[1]));
            $childError = stream_get_contents($pipes[2]);
            $exitCode = proc_close($process);
        } finally {
            if (is_resource($pipes[1])) fclose($pipes[1]);
            if (is_resource($pipes[2])) fclose($pipes[2]);
            @unlink($scriptPath);
        }

        $this->assertSame('ACCOUNT_INACTIVE_REJECTED', $childResult);
        $this->assertSame(0, $exitCode, 'Concurrent Attempt-start process failed: '.$childError);
        $this->assertFalse((bool) $this->student->fresh()->is_active);
        $this->assertSame(2, (int) $this->student->fresh()->auth_version);
        $this->assertSame(0, $primary->table('exam_attempts')->where('participant_id', $this->participant->id)->count());
    }

    public function test_deactivation_lock_timeout_rolls_back_every_account_and_exam_mutation(): void
    {
        $primary = DB::connection('mysql');
        $primary->commit();
        $primary->statement('SET SESSION innodb_lock_wait_timeout = 1');
        $primary->beginTransaction();
        $primary->commit();

        $this->student->createToken('lock-timeout-token');
        $beforeUser = (array) $primary->table('users')->where('id', $this->student->id)->first();
        $beforeExam = (array) $primary->table('exams')->where('id', $this->exam->id)->first();
        $beforeParticipant = (array) $primary->table('exam_participants')->where('id', $this->participant->id)->first();

        $attemptId = (string) Str::uuid();
        $script = <<<'PHP'
<?php
$payload = json_decode(base64_decode($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$pdo = new PDO(
    'mysql:host='.$payload['host'].';port='.$payload['port'].';dbname='.$payload['database'].';charset=utf8mb4',
    $payload['username'],
    $payload['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
);
$pdo->exec('SET SESSION innodb_lock_wait_timeout = 12');
$pdo->beginTransaction();

$exam = $pdo->prepare('SELECT id FROM exams WHERE id = ? AND tenant_id = ? FOR UPDATE');
$exam->execute([$payload['exam_id'], $payload['tenant_id']]);
$exam->fetch(PDO::FETCH_ASSOC);
$participant = $pdo->prepare('SELECT id FROM exam_participants WHERE id = ? AND exam_id = ? AND user_id = ? AND tenant_id = ? FOR UPDATE');
$participant->execute([$payload['participant_id'], $payload['exam_id'], $payload['student_id'], $payload['tenant_id']]);
$participant->fetch(PDO::FETCH_ASSOC);
$expiry = $pdo->prepare("SELECT id FROM exam_attempts WHERE participant_id = ? AND status = 'IN_PROGRESS' AND (expires_at IS NULL OR expires_at <= NOW()) FOR UPDATE");
$expiry->execute([$payload['participant_id']]);
$expiry->fetchAll(PDO::FETCH_ASSOC);
$active = $pdo->prepare("SELECT id FROM exam_attempts WHERE participant_id = ? AND status = 'IN_PROGRESS' AND expires_at > NOW() ORDER BY id FOR UPDATE");
$active->execute([$payload['participant_id']]);
$active->fetchAll(PDO::FETCH_ASSOC);
$user = $pdo->prepare('SELECT id FROM users WHERE id = ? AND tenant_id = ? FOR UPDATE');
$user->execute([$payload['student_id'], $payload['tenant_id']]);
$user->fetch(PDO::FETCH_ASSOC);

$now = date('Y-m-d H:i:s');
$insert = $pdo->prepare("INSERT INTO exam_attempts (id, tenant_id, exam_id, participant_id, attempt_number, status, started_at, expires_at, created_at, updated_at) VALUES (?, ?, ?, ?, 1, 'IN_PROGRESS', ?, DATE_ADD(?, INTERVAL 1 HOUR), ?, ?)");
$insert->execute([
    $payload['attempt_id'], $payload['tenant_id'], $payload['exam_id'],
    $payload['participant_id'], $now, $now, $now, $now,
]);
echo "ATTEMPT_START_LOCKED\n";
flush();
usleep(6500000);
$pdo->commit();
echo "ATTEMPT_START_COMMITTED\n";
flush();
PHP;
        [$process, $pipes, $scriptPath] = $this->spawnConcurrentMysqlProcess($script, [
            'tenant_id' => (string) $this->tenant->id,
            'exam_id' => (string) $this->exam->id,
            'participant_id' => (string) $this->participant->id,
            'student_id' => (string) $this->student->id,
            'attempt_id' => $attemptId,
        ]);

        try {
            $this->assertSame('ATTEMPT_START_LOCKED', trim((string) fgets($pipes[1])));
            $this->actingAs($this->admin);
            $caught = false;
            try {
                Livewire::test(UserManagement::class)->call('deactivateUser', $this->student->id);
            } catch (\Illuminate\Database\QueryException $exception) {
                $caught = str_contains($exception->getMessage(), '1205')
                    || str_contains($exception->getMessage(), 'Lock wait timeout');
            }
            $this->assertTrue($caught, 'A sustained InnoDB lock wait must surface as a lock-timeout QueryException.');
            $this->assertSame(0, $primary->transactionLevel(), 'Failed account transaction must roll back completely.');
            $this->assertSame('ATTEMPT_START_COMMITTED', trim((string) fgets($pipes[1])));
            $childError = stream_get_contents($pipes[2]);
            $exitCode = proc_close($process);
        } finally {
            if (is_resource($pipes[1])) fclose($pipes[1]);
            if (is_resource($pipes[2])) fclose($pipes[2]);
            @unlink($scriptPath);
        }

        $this->assertSame(0, $exitCode, 'Lock-holder process failed: '.$childError);
        $this->assertSame($beforeUser, (array) $primary->table('users')->where('id', $this->student->id)->first());
        $this->assertSame($beforeExam, (array) $primary->table('exams')->where('id', $this->exam->id)->first());
        $this->assertSame($beforeParticipant, (array) $primary->table('exam_participants')->where('id', $this->participant->id)->first());
        $this->assertSame(1, $primary->table('personal_access_tokens')->where('tokenable_id', $this->student->id)->count());
        $this->assertSame(0, \App\Models\AuditLog::where('action', 'DEACTIVATE_LOCAL_USER')->where('entity_id', $this->student->id)->count());
        $this->assertSame('IN_PROGRESS', $primary->table('exam_attempts')->where('id', $attemptId)->value('status'));
        $this->assertSame(0, $primary->table('exam_attempt_answers')->where('exam_attempt_id', $attemptId)->count());
        $this->assertSame(0, $primary->table('exam_attempt_scores')->where('attempt_id', $attemptId)->count());
        $this->assertSame(0, $primary->table('device_leases')->where('exam_attempt_id', $attemptId)->count());
        $this->assertSame(1, (int) $primary->table('exam_attempts')->where('id', $attemptId)->value('fence_generation'));
    }

    public function test_reset_transaction_rolls_back_cleanly_when_innodb_detects_reverse_lock_order_deadlock(): void
    {
        $primary = DB::connection('mysql');
        $primary->commit();
        $primary->statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        $primary->statement('SET SESSION innodb_lock_wait_timeout = 12');
        $primary->beginTransaction();
        $this->lockExamParentsOnPrimaryConnection();

        $attemptId = (string) Str::uuid();
        $script = <<<'PHP'
<?php
$payload = json_decode(base64_decode($argv[1]), true, 512, JSON_THROW_ON_ERROR);
$pdo = new PDO(
    'mysql:host='.$payload['host'].';port='.$payload['port'].';dbname='.$payload['database'].';charset=utf8mb4',
    $payload['username'],
    $payload['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
);
$pdo->exec('SET SESSION innodb_lock_wait_timeout = 12');
$pdo->beginTransaction();
$user = $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
$user->execute([$payload['student_id']]);
$user->fetch(PDO::FETCH_ASSOC);
echo "USER_LOCKED\n";
flush();
usleep(500000);

try {
    $exam = $pdo->prepare('SELECT id FROM exams WHERE id = ? AND tenant_id = ? FOR UPDATE');
    $exam->execute([$payload['exam_id'], $payload['tenant_id']]);
    $exam->fetch(PDO::FETCH_ASSOC);

    $participant = $pdo->prepare('SELECT id FROM exam_participants WHERE id = ? AND exam_id = ? AND user_id = ? AND tenant_id = ? FOR UPDATE');
    $participant->execute([$payload['participant_id'], $payload['exam_id'], $payload['student_id'], $payload['tenant_id']]);
    $participant->fetch(PDO::FETCH_ASSOC);

    $expiry = $pdo->prepare("SELECT id FROM exam_attempts WHERE participant_id = ? AND status = 'IN_PROGRESS' AND (expires_at IS NULL OR expires_at <= NOW()) FOR UPDATE");
    $expiry->execute([$payload['participant_id']]);
    $expiry->fetchAll(PDO::FETCH_ASSOC);
    $active = $pdo->prepare("SELECT id FROM exam_attempts WHERE participant_id = ? AND status = 'IN_PROGRESS' AND expires_at > NOW() ORDER BY id FOR UPDATE");
    $active->execute([$payload['participant_id']]);
    $active->fetchAll(PDO::FETCH_ASSOC);

    $now = date('Y-m-d H:i:s');
    $insert = $pdo->prepare("INSERT INTO exam_attempts (id, tenant_id, exam_id, participant_id, attempt_number, status, started_at, expires_at, created_at, updated_at) VALUES (?, ?, ?, ?, 1, 'IN_PROGRESS', ?, DATE_ADD(?, INTERVAL 1 HOUR), ?, ?)");
    $insert->execute([
        $payload['attempt_id'], $payload['tenant_id'], $payload['exam_id'],
        $payload['participant_id'], $now, $now, $now, $now,
    ]);
    $pdo->commit();
    echo "UNEXPECTED_ATTEMPT_STARTED\n";
} catch (PDOException $exception) {
    $driverCode = (int) ($exception->errorInfo[1] ?? 0);
    if ($driverCode !== 1213) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        fwrite(STDERR, $exception->getMessage()."\n");
        exit(2);
    }
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "DEADLOCK_ROLLBACK_".$driverCode."\n";
}
flush();
PHP;
        [$process, $pipes, $scriptPath] = $this->spawnConcurrentMysqlProcess($script, [
            'student_id' => (string) $this->student->id,
            'exam_id' => (string) $this->exam->id,
            'participant_id' => (string) $this->participant->id,
            'tenant_id' => (string) $this->tenant->id,
            'attempt_id' => $attemptId,
        ]);

        try {
            $this->assertSame('USER_LOCKED', trim((string) fgets($pipes[1])));
            $this->actingAs($this->admin);
            $component = Livewire::test(UserManagement::class)
                ->call('resetUserPassword', $this->student->id)
                ->assertSet('showTemporaryPasswordModal', true);
            $temporaryPassword = $component->get('temporaryPassword');
            $primary->commit();

            $childResult = trim((string) fgets($pipes[1]));
            $childError = stream_get_contents($pipes[2]);
            $exitCode = proc_close($process);
        } finally {
            if (is_resource($pipes[1])) fclose($pipes[1]);
            if (is_resource($pipes[2])) fclose($pipes[2]);
            @unlink($scriptPath);
        }

        $this->assertSame('DEADLOCK_ROLLBACK_1213', $childResult, 'The competing Attempt-start transaction must be the InnoDB deadlock victim and roll back.');
        $this->assertSame(0, $exitCode, 'Deadlock competitor failed: '.$childError);
        $this->assertTrue(Hash::check($temporaryPassword, $this->student->fresh()->password));
        $this->assertTrue((bool) $this->student->fresh()->must_change_password);
        $this->assertSame(2, (int) $this->student->fresh()->auth_version);
        $this->assertSame(0, $primary->table('exam_attempts')->where('participant_id', $this->participant->id)->count());
    }

}
