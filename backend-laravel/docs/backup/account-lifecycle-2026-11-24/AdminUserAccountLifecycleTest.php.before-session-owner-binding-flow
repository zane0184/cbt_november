<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\UserManagement;
use App\Models\AuditLog;
use App\Models\LocalInstallationIdentity;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LoginIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
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

    private function makeLocalIdentity(): LocalInstallationIdentity
    {
        return LocalInstallationIdentity::create([
            'tenant_id' => $this->tenant->id,
            'installation_id' => (string) Str::uuid(),
            'key_id' => 'lifecycle-key-'.Str::lower(Str::random(12)),
            'public_key' => 'lifecycle-public-key',
            'encrypted_private_key' => 'lifecycle-encrypted-private-key',
            'key_fingerprint' => hash('sha256', 'lifecycle-public-key'),
            'registered_domain' => 'lifecycle.example.test',
            'product_code' => 'CBT-MI',
            'product_version' => '1.0.0',
        ]);
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
        $teacherToken = $teacher->createToken('teacher-device')->plainTextToken;
        $other->createToken('other-device');
        DB::table('sessions')->insert([
            'id' => 'target-session', 'user_id' => $teacher->id, 'payload' => 'encrypted-session',
            'last_activity' => now()->timestamp,
        ]);
        DB::table('sessions')->insert([
            'id' => 'other-session', 'user_id' => $other->id, 'payload' => 'encrypted-session',
            'last_activity' => now()->timestamp,
        ]);

        $logPath = storage_path('logs/laravel.log');
        $logBefore = is_file($logPath) ? (string) file_get_contents($logPath) : '';

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

        $logAfter = is_file($logPath) ? (string) file_get_contents($logPath) : $logBefore;
        $this->assertStringNotContainsString($temporaryPassword, $logAfter);

        // Verify the pre-reset Sanctum token and pre-reset Web session cannot be reused.
        Auth::guard('web')->logout();
        $this->actingAs($teacher->fresh())->withSession(['account_auth_version' => 1])
            ->get('/guru/dashboard')->assertRedirect('/login');
        Auth::guard('web')->logout();
        $this->withToken($teacherToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
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
        [$exam, $participant, $attempt] = $this->makeActiveAttemptFixture($student);
        DB::table('exam_attempts')->where('id', $attempt->id)->update(['status' => 'SUBMITTED']);
        $beforeAttempt = (array) DB::table('exam_attempts')->where('id', $attempt->id)->first();
        $beforeAnswer = (array) DB::table('exam_attempt_answers')->where('exam_attempt_id', $attempt->id)->first();
        $beforeScore = (array) DB::table('exam_attempt_scores')->where('attempt_id', $attempt->id)->first();
        $beforeLease = (array) DB::table('device_leases')->where('exam_attempt_id', $attempt->id)->first();

        $oldToken = $student->createToken('student-device')->plainTextToken;
        $other = $this->makeLocalUser('GURU', 'other-credential-owner');
        $otherToken = $other->createToken('other-device')->plainTextToken;
        DB::table('sessions')->insert([
            'id' => 'student-session', 'user_id' => $student->id, 'payload' => 'encrypted-session',
            'last_activity' => now()->timestamp,
        ]);
        DB::table('sessions')->insert([
            'id' => 'other-owner-session', 'user_id' => $other->id, 'payload' => 'encrypted-session',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->call('deactivateUser', $student->id);

        $disabled = $student->fresh();
        $this->assertFalse((bool) $disabled->is_active);
        $this->assertSame(2, (int) $disabled->auth_version);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $student->id)->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'student-session']);
        $this->assertSame(1, DB::table('personal_access_tokens')->where('tokenable_id', $other->id)->count());
        $this->assertDatabaseHas('sessions', ['id' => 'other-owner-session', 'user_id' => $other->id]);
        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'nisn' => '0099887766']);

        // Reuse the exact pre-deactivation bearer token: Sanctum must reject it.
        Auth::forgetGuards();
        $this->withToken($oldToken)->getJson('/api/v1/auth/me')->assertUnauthorized();

        // Credentials owned by a different account remain usable.
        Auth::forgetGuards();
        $this->withToken($otherToken)->getJson('/api/v1/auth/me')->assertOk();
        $this->assertSame($beforeAttempt, (array) DB::table('exam_attempts')->where('id', $attempt->id)->first());
        $this->assertSame($beforeAnswer, (array) DB::table('exam_attempt_answers')->where('exam_attempt_id', $attempt->id)->first());
        $this->assertSame($beforeScore, (array) DB::table('exam_attempt_scores')->where('attempt_id', $attempt->id)->first());
        $this->assertSame($beforeLease, (array) DB::table('device_leases')->where('exam_attempt_id', $attempt->id)->first());

        $this->flushHeaders();
        $this->actingAs($disabled)->withSession(['account_auth_version' => 1])
            ->get('/student/exams')->assertRedirect('/login');

        // Re-authenticate the administrator before starting a new Livewire test request.
        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->call('activateUser', $student->id);
        $activated = $student->fresh();
        $this->assertTrue((bool) $activated->is_active);
        $this->assertSame(3, (int) $activated->auth_version);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $student->id)->count());
        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id, 'nisn' => '0099887766']);

        // The old Web session stays invalid even after the account is reactivated.
        $this->flushHeaders();
        $this->actingAs($activated)->withSession(['account_auth_version' => 1])
            ->get('/student/exams')->assertRedirect('/login');
    }

    public function test_forced_password_change_updates_hash_and_requires_reauthentication(): void
    {
        $teacher = $this->makeLocalUser('GURU');
        $teacher->must_change_password = true;
        $teacher->save();
        $temporaryToken = $teacher->createToken('temporary-token', ['*', 'account-version:1'])->plainTextToken;

        // The production Blade view keeps its @vite assets; this backend test
        // explicitly disables Vite manifest resolution without changing production rendering.
        $this->withoutVite();
        $this->actingAs($teacher)->withSession(['account_auth_version' => 1])
            ->get('/')->assertRedirect('/account/password/change');
        $this->actingAs($teacher)->withSession(['account_auth_version' => 1])
            ->get('/login')->assertRedirect('/account/password/change');

        $this->actingAs($teacher)->withSession(['account_auth_version' => 1])
            ->get('/admin/dashboard')->assertRedirect('/account/password/change');

        Auth::guard('web')->logout();
        $this->withToken($temporaryToken)->getJson('/api/v1/auth/me')->assertStatus(423);

        $this->actingAs($teacher)->withSession(['account_auth_version' => 1])
            ->get('/account/password/change')->assertOk();

        $this->actingAs($teacher)->withSession(['account_auth_version' => 1])
            ->post('/account/password/change', [
                'current_password' => 'old-password-strong',
                'password' => 'new-password-strong-2026',
                'password_confirmation' => 'new-password-strong-2026',
            ])->assertRedirect('/login');

        $this->assertGuest('web');
        $fresh = $teacher->fresh();
        $this->assertTrue(Hash::check('new-password-strong-2026', $fresh->password));
        $this->assertFalse((bool) $fresh->must_change_password);
        $this->assertSame(2, (int) $fresh->auth_version);
        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $teacher->id)->count());
        $audit = AuditLog::where('action', 'FORCE_CHANGE_PASSWORD')->where('entity_id', $teacher->id)->firstOrFail();
        $payload = json_encode([$audit->old_values, $audit->new_values]);
        $this->assertStringNotContainsString('new-password-strong-2026', $payload);
        $this->assertSame('SUCCESS', $audit->new_values['outcome']);

        Auth::guard('web')->logout();
        Auth::forgetGuards();
        $this->withToken($temporaryToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_deactivation_is_rejected_during_official_in_progress_attempt_without_exam_data_mutation(): void
    {
        $student = $this->makeLocalUser('SISWA');
        [$exam, $participant, $attempt] = $this->makeActiveAttemptFixture($student);
        $student->createToken('student-device');
        DB::table('sessions')->insert([
            'id' => 'active-attempt-session', 'user_id' => $student->id,
            'payload' => 'encrypted-session', 'last_activity' => now()->timestamp,
        ]);

        $beforeAttempt = (array) DB::table('exam_attempts')->where('id', $attempt->id)->first();
        $beforeAnswer = (array) DB::table('exam_attempt_answers')->where('exam_attempt_id', $attempt->id)->first();
        $beforeScore = (array) DB::table('exam_attempt_scores')->where('attempt_id', $attempt->id)->first();
        $beforeLease = (array) DB::table('device_leases')->where('exam_attempt_id', $attempt->id)->first();

        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->call('deactivateUser', $student->id);

        $this->assertTrue((bool) $student->fresh()->is_active);
        $this->assertSame(1, (int) $student->fresh()->auth_version);
        $this->assertSame(1, DB::table('personal_access_tokens')->where('tokenable_id', $student->id)->count());
        $this->assertDatabaseHas('sessions', ['id' => 'active-attempt-session']);
        $this->assertSame($beforeAttempt, (array) DB::table('exam_attempts')->where('id', $attempt->id)->first());
        $this->assertSame($beforeAnswer, (array) DB::table('exam_attempt_answers')->where('exam_attempt_id', $attempt->id)->first());
        $this->assertSame($beforeScore, (array) DB::table('exam_attempt_scores')->where('attempt_id', $attempt->id)->first());
        $this->assertSame($beforeLease, (array) DB::table('device_leases')->where('exam_attempt_id', $attempt->id)->first());
        $this->assertSame(9, (int) DB::table('exam_attempts')->where('id', $attempt->id)->value('fence_generation'));

        $audit = AuditLog::where('action', 'DEACTIVATE_LOCAL_USER')->where('entity_id', $student->id)->firstOrFail();
        $this->assertSame('REJECTED', $audit->new_values['outcome']);
        $this->assertSame('ACTIVE_ATTEMPT_IN_PROGRESS', $audit->new_values['reason']);
    }

    public function test_reset_is_limited_to_guru_or_siswa_in_the_admin_tenant(): void
    {
        $adminTarget = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Second Admin',
            'username' => 'second.admin',
            'email' => 'second.admin@test.local',
            'password' => Hash::make('old-password-strong'),
        ]);
        $adminTarget->roles()->attach(Role::where('name', 'ADMIN_MADRASAH')->firstOrFail());

        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)
            ->call('resetUserPassword', $adminTarget->id)
            ->assertForbidden();

        $otherTenant = Tenant::create(['name' => 'Other Lifecycle Tenant', 'code' => 'ALT02']);
        app(TenantContext::class)->setTenantId((string) $otherTenant->id);
        TenantContext::getInstance()?->setTenantId((string) $otherTenant->id);
        $foreignTeacher = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Teacher',
            'username' => 'foreign.teacher',
            'email' => 'foreign.teacher@test.local',
            'password' => Hash::make('old-password-strong'),
        ]);
        $foreignTeacher->roles()->attach(Role::where('name', 'GURU')->firstOrFail());
        app(TenantContext::class)->setTenantId((string) $this->tenant->id);
        TenantContext::getInstance()?->setTenantId((string) $this->tenant->id);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(UserManagement::class)->call('resetUserPassword', $foreignTeacher->id);
    }

    public function test_web_and_api_login_recheck_password_after_account_row_lock(): void
    {
        $webUser = $this->makeLocalUser('GURU', 'race-web');
        $staleWebUser = $webUser->fresh();
        $webResolver = \Mockery::mock(LoginIdentityResolver::class);
        $webResolver->shouldReceive('resolve')
            ->once()
            ->with($webUser->username)
            ->andReturnUsing(function () use ($webUser, $staleWebUser) {
                $webUser->forceFill([
                    'password' => Hash::make('new-temporary-password'),
                    'must_change_password' => true,
                    'auth_version' => 2,
                ])->save();

                return $staleWebUser;
            });
        $this->app->instance(LoginIdentityResolver::class, $webResolver);

        $this->post('/login', [
            'identifier' => $webUser->username,
            'password' => 'old-password-strong',
        ])->assertSessionHasErrors('identifier');
        $this->assertGuest();

        $apiUser = $this->makeLocalUser('GURU', 'race-api');
        $staleApiUser = $apiUser->fresh();
        $apiResolver = \Mockery::mock(LoginIdentityResolver::class);
        $apiResolver->shouldReceive('resolve')
            ->once()
            ->with($apiUser->username)
            ->andReturnUsing(function () use ($apiUser, $staleApiUser) {
                $apiUser->forceFill([
                    'password' => Hash::make('new-temporary-password'),
                    'must_change_password' => true,
                    'auth_version' => 2,
                ])->save();

                return $staleApiUser;
            });
        $this->app->instance(LoginIdentityResolver::class, $apiResolver);

        $this->postJson('/api/v1/auth/login', [
            'identifier' => $apiUser->username,
            'password' => 'old-password-strong',
        ])->assertUnprocessable();

        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $apiUser->id)->count());
    }

    public function test_disabled_account_cannot_login_through_web_or_api(): void
    {
        $teacher = $this->makeLocalUser('GURU');
        $teacher->forceFill(['is_active' => false])->save();
        $this->makeLocalIdentity();

        $this->post('/login', [
            'identifier' => $teacher->username,
            'password' => 'old-password-strong',
        ])->assertSessionHasErrors('identifier');

        $this->postJson('/api/v1/auth/login', [
            'identifier' => $teacher->username,
            'password' => 'old-password-strong',
        ])->assertUnprocessable();

        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $teacher->id)->count());
    }


    public function test_deactivation_is_limited_to_guru_or_siswa_in_the_admin_tenant_and_requires_admin_actor(): void
    {
        $adminTarget = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Protected Admin Target',
            'username' => 'protected.admin.target',
            'email' => 'protected.admin.target@test.local',
            'password' => Hash::make('old-password-strong'),
        ]);
        $adminTarget->roles()->attach(Role::where('name', 'ADMIN_MADRASAH')->firstOrFail());

        $multiRoleTarget = $this->makeLocalUser('GURU', 'multi-role-deactivation');
        $multiRoleTarget->roles()->attach(Role::where('name', 'ADMIN_MADRASAH')->firstOrFail());

        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->call('deactivateUser', $adminTarget->id)->assertForbidden();
        Livewire::test(UserManagement::class)->call('deactivateUser', $multiRoleTarget->id)->assertForbidden();

        $otherTenant = Tenant::create(['name' => 'Other Deactivation Tenant', 'code' => 'ALT03']);
        app(TenantContext::class)->setTenantId((string) $otherTenant->id);
        TenantContext::getInstance()?->setTenantId((string) $otherTenant->id);
        $foreignTeacher = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Deactivation Teacher',
            'username' => 'foreign.deactivation.teacher',
            'email' => 'foreign.deactivation.teacher@test.local',
            'password' => Hash::make('old-password-strong'),
        ]);
        $foreignTeacher->roles()->attach(Role::where('name', 'GURU')->firstOrFail());
        app(TenantContext::class)->setTenantId((string) $this->tenant->id);
        TenantContext::getInstance()?->setTenantId((string) $this->tenant->id);

        try {
            Livewire::test(UserManagement::class)->call('deactivateUser', $foreignTeacher->id);
            $this->fail('Cross-tenant deactivation must not find or mutate the foreign account.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            $foreignFresh = User::withoutGlobalScopes()->findOrFail($foreignTeacher->id);
            $this->assertTrue((bool) $foreignFresh->is_active);
            $this->assertSame(1, (int) $foreignFresh->auth_version);
        }

        $localStudent = $this->makeLocalUser('SISWA', 'non-admin-actor-target');
        $this->actingAs($this->makeLocalUser('GURU', 'unauthorized-deactivator'));
        $this->get('/admin/users')->assertForbidden();
        $this->assertTrue((bool) $localStudent->fresh()->is_active);
    }

    private function configurePersistentSessionDriver(string $driver): void
    {
        $files = storage_path('framework/testing-sessions');
        if (!is_dir($files)) {
            mkdir($files, 0777, true);
        }

        config([
            'session.driver' => $driver,
            'session.files' => $files,
            'session.table' => 'sessions',
            'session.connection' => 'mysql',
            'session.cookie' => 'cbt_lifecycle_test_session',
            'session.encrypt' => false,
            'session.expire_on_close' => false,
            'session.lifetime' => 120,
            'session.path' => '/',
            'session.domain' => null,
            'session.secure' => false,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'session.lottery' => [0, 100],
        ]);
        app('session')->forgetDrivers();
        $this->assertSame($driver, app('session')->getDefaultDriver());
    }

    private function loginAndCapturePersistentWebSession(User $user, ?string $seedSessionId = null): array
    {
        $cookieName = (string) config('session.cookie');
        $store = app('session')->driver();
        $store->flush();
        $store->setId($seedSessionId ?? Str::random(40));
        Auth::forgetGuards();
        $this->flushHeaders();

        $response = $this->post('/login', [
            'identifier' => $user->username,
            'password' => 'old-password-strong',
        ]);
        $response->assertRedirect('/guru/dashboard');

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($candidate) => $candidate->getName() === $cookieName);
        $this->assertNotNull($cookie, 'Web login must issue a session cookie.');

        if (config('session.driver') === 'database') {
            $row = DB::table('sessions')->where('user_id', $user->id)
                ->orderByDesc('last_activity')->first();
            $this->assertNotNull($row, 'Database session must be associated with the authenticated account.');
            $sessionId = (string) $row->id;
        } elseif (config('session.driver') === 'file') {
            $files = glob(rtrim((string) config('session.files'), '/').'/*') ?: [];
            $matchingFiles = array_values(array_filter($files, function ($file) use ($user) {
                $payload = file_get_contents($file);
                return is_string($payload) && str_contains($payload, (string) $user->id);
            }));
            $this->assertCount(1, $matchingFiles, 'Exactly one persisted file session must contain the authenticated user ID.');
            $sessionId = basename($matchingFiles[0]);
        } else {
            $this->fail('This test only supports the configured file and database session drivers.');
        }

        $this->assertNotSame('', $sessionId);

        return [$cookieName, $sessionId];
    }

    private function isolateAdminLifecycleSession(): void
    {
        Auth::forgetGuards();
        $store = app('session')->driver();
        $store->flush();
        $store->setId(Str::random(40));
    }

    private function assertPersistedWebSessionsAreRevokedForDriver(string $driver): void
    {
        $this->configurePersistentSessionDriver($driver);
        $this->makeLocalIdentity();

        $resetTeacher = $this->makeLocalUser('GURU', 'session-reset-'.$driver);
        $deactivatedTeacher = $this->makeLocalUser('GURU', 'session-deactivate-'.$driver);
        $unrelatedTeacher = $this->makeLocalUser('GURU', 'session-unrelated-'.$driver);

        foreach ([$resetTeacher, $deactivatedTeacher, $unrelatedTeacher] as $teacher) {
            \App\Models\TeacherProfile::create([
                'tenant_id' => $this->tenant->id,
                'user_id' => $teacher->id,
                'full_name' => $teacher->name,
            ]);
        }

        [$cookieName, $resetCookie] = $this->loginAndCapturePersistentWebSession($resetTeacher);
        Auth::forgetGuards();
        [, $deactivateCookie] = $this->loginAndCapturePersistentWebSession($deactivatedTeacher, Str::random(40));
        Auth::forgetGuards();
        [, $unrelatedCookie] = $this->loginAndCapturePersistentWebSession($unrelatedTeacher, Str::random(40));

        // A real login request must persist the session using the requested handler.
        $handlerClass = get_class(app('session')->driver()->getHandler());
        $this->assertStringContainsString(
            $driver === 'file' ? 'FileSessionHandler' : 'DatabaseSessionHandler',
            $handlerClass
        );

        $this->isolateAdminLifecycleSession();
        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->call('resetUserPassword', $resetTeacher->id);
        Auth::forgetGuards();

        $this->flushHeaders();
        $this->withCookie($cookieName, $resetCookie)->get('/')->assertRedirect('/login');

        // Resetting one account must not invalidate another account's persisted session.
        Auth::forgetGuards();
        $this->withCookie($cookieName, $unrelatedCookie)->get('/guru/dashboard')->assertOk();

        $this->isolateAdminLifecycleSession();
        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->call('deactivateUser', $deactivatedTeacher->id);
        Auth::forgetGuards();

        $this->flushHeaders();
        $this->withCookie($cookieName, $deactivateCookie)->get('/guru/dashboard')->assertRedirect('/login');

        // A different account's persisted session must survive deactivation too.
        Auth::forgetGuards();
        $this->flushHeaders();
        $this->withCookie($cookieName, $unrelatedCookie)->get('/guru/dashboard')->assertOk();

        $this->isolateAdminLifecycleSession();
        $this->actingAs($this->admin);
        Livewire::test(UserManagement::class)->call('activateUser', $deactivatedTeacher->id);
        Auth::forgetGuards();

        // Reactivation advances auth_version again; it never resurrects the old cookie.
        $this->withCookie($cookieName, $deactivateCookie)->get('/guru/dashboard')->assertRedirect('/login');
        $this->assertTrue((bool) $deactivatedTeacher->fresh()->is_active);
        $this->assertSame(3, (int) $deactivatedTeacher->fresh()->auth_version);
    }

    public function test_persisted_file_web_sessions_are_revoked_after_reset_deactivation_and_reactivation(): void
    {
        $this->assertPersistedWebSessionsAreRevokedForDriver('file');
    }

    public function test_persisted_database_web_sessions_are_revoked_after_reset_deactivation_and_reactivation(): void
    {
        $this->assertPersistedWebSessionsAreRevokedForDriver('database');
    }

}
