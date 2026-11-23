<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\UserManagement;
use App\Models\AuditLog;
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

class AdminUserAccountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Account Lifecycle Test', 'code' => 'ALT01']);
        app(TenantContext::class)->setTenantId($this->tenant->id);
        TenantContext::getInstance()?->setTenantId($this->tenant->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $permission = Permission::firstOrCreate(['name' => 'user.manage'], ['label' => 'Kelola akun']);
        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
        Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Lifecycle',
            'username' => 'admin.lifecycle',
            'email' => 'admin.lifecycle@test.local',
            'password' => Hash::make('admin-password-strong'),
        ]);
        $this->admin->roles()->attach($adminRole);
    }

    private function makeLocalUser(string $roleName, string $suffix = '01'): User
    {
        $user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => $roleName.' '.$suffix,
            'username' => strtolower($roleName).'.'.$suffix,
            'email' => strtolower($roleName).'.'.$suffix.'@test.local',
            'password' => Hash::make('old-password-strong'),
        ]);
        $user->roles()->attach(Role::where('name', $roleName)->firstOrFail());

        return $user;
    }

    private function makeActiveAttemptFixture(User $student): array
    {
        $year = \App\Models\AcademicYear::create([
            'tenant_id' => $this->tenant->id,
            'name' => '2026/2027',
        ]);
        $assessment = \App\Models\Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Lifecycle Assessment',
            'assessment_type' => 'DAILY',
            'created_by' => $this->admin->id,
        ]);
        $exam = \App\Models\Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Lifecycle Exam',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
            'locked_at' => now(),
        ]);
        $participant = \App\Models\ExamParticipant::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'user_id' => $student->id,
            'status' => 'STARTED',
        ]);
        $attempt = \App\Models\ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
        ]);
        DB::table('exam_attempts')->where('id', $attempt->id)->update(['fence_generation' => 9]);

        $questionId = (string) Str::uuid();
        $versionId = (string) Str::uuid();
        $snapshotId = (string) Str::uuid();
        $now = now();
        DB::table('questions')->insert([
            'id' => $questionId, 'tenant_id' => $this->tenant->id,
            'owner_type' => 'PERSONAL', 'owner_user_id' => $this->admin->id,
            'status' => 'PUBLISHED', 'visibility' => 'PRIVATE',
            'created_by' => $this->admin->id, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('question_versions')->insert([
            'id' => $versionId, 'tenant_id' => $this->tenant->id, 'question_id' => $questionId,
            'major_version' => 1, 'minor_version' => 0, 'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'Pertanyaan uji', 'status' => 'PUBLISHED', 'is_current' => true,
            'created_by' => $this->admin->id, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('exam_question_snapshots')->insert([
            'id' => $snapshotId, 'tenant_id' => $this->tenant->id, 'exam_id' => $exam->id,
            'source_question_id' => $questionId, 'source_question_version_id' => $versionId,
            'sequence' => 1, 'points' => 1, 'snapshot_stem' => 'Pertanyaan uji',
            'snapshot_options' => json_encode([]), 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('exam_attempt_answers')->insert([
            'id' => (string) Str::uuid(), 'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id, 'exam_attempt_id' => $attempt->id,
            'exam_question_snapshot_id' => $snapshotId, 'student_id' => $student->id,
            'answer_payload' => json_encode(['answer' => 'A']), 'answer_state' => 'ANSWERED',
            'revision_number' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('exam_attempt_scores')->insert([
            'id' => (string) Str::uuid(), 'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id, 'attempt_id' => $attempt->id, 'student_id' => $student->id,
            'raw_score' => 1, 'max_score' => 1, 'percentage' => 100,
            'scoring_version' => 1, 'grading_status' => 'AUTO_SCORED',
            'calculated_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $deviceId = (string) Str::uuid();
        DB::table('registered_devices')->insert([
            'id' => $deviceId, 'tenant_id' => $this->tenant->id, 'user_id' => $student->id,
            'installation_uuid' => (string) Str::uuid(), 'device_token' => 'token-'.$deviceId,
            'device_token_hash' => hash('sha256', 'token-'.$deviceId), 'platform' => 'ANDROID',
            'status' => 'ACTIVE', 'registered_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('device_leases')->insert([
            'id' => (string) Str::uuid(), 'tenant_id' => $this->tenant->id,
            'exam_attempt_id' => $attempt->id, 'registered_device_id' => $deviceId,
            'fence_generation' => 9, 'status' => 'ACTIVE',
            'leased_at' => $now, 'expires_at' => now()->addHour(),
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return [$exam, $participant, $attempt];
    }

    public function test_reset_generates_one_time_password_hashes_it_and_revokes_only_target_credentials(): void
    {
        $teacher = $this->makeLocalUser('GURU');
        $other = $this->makeLocalUser('GURU', '02');
        $teacher->createToken('teacher-device');
        $other->createToken('other-device');
        DB::table('sessions')->insert([
            'id' => 'target-session', 'user_id' => $teacher->id, 'payload' => 'encrypted-session',
            'last_activity' => now()->timestamp,
        ]);
        DB::table('sessions')->insert([
            'id' => 'other-session', 'user_id' => $other->id, 'payload' => 'encrypted-session',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($this->admin);
        $component = Livewire::test(UserManagement::class)
            ->call('resetUserPassword', $teacher->id)
            ->assertSet('showTemporaryPasswordModal', true)
            ->assertSet('temporaryPassword', fn ($value) => is_string($value) && strlen($value) === 48);

        $temporaryPassword = $component->get('temporaryPassword');
        $fresh = $teacher->fresh();

        $this->assertTrue(Hash::check($temporaryPassword, $fresh->password));
        $this->assertTrue((bool) $fresh->must_change_password);
        $this->assertSame(2, (int) $fresh->auth_version);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $teacher->id)->count());
        $this->assertSame(1, DB::table('personal_access_tokens')->where('tokenable_id', $other->id)->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);

        $audit = AuditLog::where('action', 'RESET_LOCAL_USER_PASSWORD')->where('entity_id', $teacher->id)->firstOrFail();
        $this->assertSame('SUCCESS', $audit->new_values['outcome']);
        $auditPayload = json_encode([$audit->old_values, $audit->new_values]);
        $this->assertStringNotContainsString($temporaryPassword, $auditPayload);
        $this->assertStringNotContainsString($fresh->password, $auditPayload);

        $component->call('dismissTemporaryPassword')->assertSet('temporaryPassword', null)->assertSet('showTemporaryPasswordModal', false);
    }

    public function test_reset_rejects_official_in_progress_attempt_without_mutating_attempt_answer_score_lease_or_fence(): void
    {
        $student = $this->makeLocalUser('SISWA');
        [$exam, $participant, $attempt] = $this->makeActiveAttemptFixture($student);
        $beforeAttempt = (array) DB::table('exam_attempts')->where('id', $attempt->id)->first();
        $beforeAnswer = (array) DB::table('exam_attempt_answers')->where('exam_attempt_id', $attempt->id)->first();
        $beforeScore = (array) DB::table('exam_attempt_scores')->where('attempt_id', $attempt->id)->first();
        $beforeLease = (array) DB::table('device_leases')->where('exam_attempt_id', $attempt->id)->first();

        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)
            ->call('resetUserPassword', $student->id)
            ->assertSet('showTemporaryPasswordModal', false)
            ->assertSet('temporaryPassword', null);

        $this->assertSame($beforeAttempt, (array) DB::table('exam_attempts')->where('id', $attempt->id)->first());
        $this->assertSame($beforeAnswer, (array) DB::table('exam_attempt_answers')->where('exam_attempt_id', $attempt->id)->first());
        $this->assertSame($beforeScore, (array) DB::table('exam_attempt_scores')->where('attempt_id', $attempt->id)->first());
        $this->assertSame($beforeLease, (array) DB::table('device_leases')->where('exam_attempt_id', $attempt->id)->first());
        $this->assertSame('IN_PROGRESS', $attempt->fresh()->status);
        $this->assertSame(9, (int) DB::table('exam_attempts')->where('id', $attempt->id)->value('fence_generation'));
        $this->assertFalse((bool) $student->fresh()->must_change_password);
        $this->assertSame(1, (int) $student->fresh()->auth_version);

        $audit = AuditLog::where('action', 'RESET_LOCAL_USER_PASSWORD')->where('entity_id', $student->id)->firstOrFail();
        $this->assertSame('REJECTED', $audit->new_values['outcome']);
        $this->assertSame('ACTIVE_ATTEMPT_IN_PROGRESS', $audit->new_values['reason']);
    }

    public function test_deactivation_revokes_credentials_preserves_academic_profile_and_activation_does_not_restore_them(): void
    {
        $student = $this->makeLocalUser('SISWA');
        $profile = \App\Models\StudentProfile::create([
            'tenant_id' => $this->tenant->id, 'user_id' => $student->id,
            'full_name' => $student->name, 'nisn' => '0099887766',
        ]);
        $student->createToken('student-device');
        DB::table('sessions')->insert([
            'id' => 'student-session', 'user_id' => $student->id, 'payload' => 'encrypted-session',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->call('deactivateUser', $student->id);

        $disabled = $student->fresh();
        $this->assertFalse((bool) $disabled->is_active);
        $this->assertSame(2, (int) $disabled->auth_version);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $student->id)->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'student-session']);
        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'nisn' => '0099887766']);

        $this->actingAs($disabled)->withSession(['account_auth_version' => 1])
            ->get('/student/exams')->assertRedirect('/login');

        Livewire::test(UserManagement::class)->call('activateUser', $student->id);
        $activated = $student->fresh();
        $this->assertTrue((bool) $activated->is_active);
        $this->assertSame(3, (int) $activated->auth_version);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $student->id)->count());
        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'nisn' => '0099887766']);
    }

    public function test_forced_password_change_updates_hash_and_requires_reauthentication(): void
    {
        $teacher = $this->makeLocalUser('GURU');
        $teacher->must_change_password = true;
        $teacher->save();
        $teacher->createToken('temporary-token');

        $this->actingAs($teacher)->withSession(['account_auth_version' => 1])
            ->get('/account/password/change')->assertOk();

        $this->actingAs($teacher)->withSession(['account_auth_version' => 1])
            ->post('/account/password/change', [
                'current_password' => 'old-password-strong',
                'password' => 'new-password-strong-2026',
                'password_confirmation' => 'new-password-strong-2026',
            ])->assertRedirect('/login');

        $fresh = $teacher->fresh();
        $this->assertTrue(Hash::check('new-password-strong-2026', $fresh->password));
        $this->assertFalse((bool) $fresh->must_change_password);
        $this->assertSame(2, (int) $fresh->auth_version);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $teacher->id)->count());
        $audit = AuditLog::where('action', 'FORCE_CHANGE_PASSWORD')->where('entity_id', $teacher->id)->firstOrFail();
        $payload = json_encode([$audit->old_values, $audit->new_values]);
        $this->assertStringNotContainsString('new-password-strong-2026', $payload);
        $this->assertSame('SUCCESS', $audit->new_values['outcome']);
    }
}
