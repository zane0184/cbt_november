<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\LocalInstallationIdentity;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamAttempt;
use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentWebLoginTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $student;
    private string $nisn = '0001234567';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Uji', 'code' => 'MADUJI']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $studentRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        $this->student = User::create([
            'name' => 'Siswa Uji',
            'username' => 'siswa.uji',
            // Email sengaja tidak diisi: NISN adalah identifier login Siswa.
            'password' => bcrypt('SandiUji#2026'),
        ]);
        $this->student->roles()->attach($studentRole);

        StudentProfile::create([
            'user_id' => $this->student->id,
            'nisn' => $this->nisn,
            'full_name' => 'Siswa Uji',
        ]);

        LocalInstallationIdentity::create([
            'tenant_id' => $this->tenant->id,
            'installation_id' => (string) \Illuminate\Support\Str::uuid(),
            'key_id' => 'test-installation-key',
            'public_key' => 'test-public-key',
            'encrypted_private_key' => 'test-encrypted-private-key',
            'key_fingerprint' => str_repeat('a', 64),
            'registered_domain' => 'cbt.test',
            'product_code' => 'CBT_MADRASAH',
            'product_version' => 'test',
        ]);

        app(TenantContext::class)->setTenantId(null);
    }

    private function createActiveAttempt(): ExamAttempt
    {
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $year = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2026/2027']);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Ujian Aktif',
            'assessment_type' => 'DAILY',
            'created_by' => $this->student->id,
        ]);
        $exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Aktif',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'status' => 'PUBLISHED',
            'created_by' => $this->student->id,
            'locked_at' => now(),
        ]);
        $participant = ExamParticipant::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'user_id' => $this->student->id,
            'status' => 'STARTED',
        ]);

        $attempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
            'fence_generation' => 1,
        ]);
        app(TenantContext::class)->setTenantId(null);

        return $attempt;
    }

    public function test_student_can_login_on_web_using_nisn_and_password_without_email(): void
    {
        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertRedirect('/student/exams');
        $this->assertAuthenticatedAs($this->student);
    }

    public function test_student_login_page_explains_nisn_identifier(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Username / NIP / NIK / NISN');
        $response->assertSee('Siswa: NISN');
        $response->assertSee('name="identifier"', false);
    }

    public function test_student_cannot_login_using_username_instead_of_nisn(): void
    {
        $response = $this->post('/login', [
            'identifier' => 'siswa.uji',
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_invalid_student_password_returns_generic_authentication_error(): void
    {
        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'password-salah',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_duplicate_nisn_across_two_student_accounts_is_rejected(): void
    {
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $studentRole = Role::where('name', 'SISWA')->firstOrFail();
        $second = User::create([
            'name' => 'Siswa Duplikat',
            'username' => 'siswa.duplikat',
            'password' => bcrypt('SandiUji#2026'),
        ]);
        $second->roles()->attach($studentRole);
        StudentProfile::create([
            'user_id' => $second->id,
            'nisn' => $this->nisn,
            'full_name' => 'Siswa Duplikat',
        ]);
        app(TenantContext::class)->setTenantId(null);

        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_student_role_combined_with_another_role_is_rejected(): void
    {
        $teacherRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $this->student->roles()->attach($teacherRole);

        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_student_login_fails_closed_without_local_installation_identity(): void
    {
        LocalInstallationIdentity::query()->delete();

        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_student_login_fails_closed_if_installation_contains_multiple_tenants(): void
    {
        Tenant::create(['name' => 'Madrasah Lain', 'code' => 'MADLAIN']);

        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_second_web_login_is_rejected_while_student_attempt_is_active(): void
    {
        $this->createActiveAttempt();

        $response = $this->post('/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_second_api_login_returns_active_exam_session_conflict_without_issuing_token(): void
    {
        $this->createActiveAttempt();

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
            'device_name' => 'browser-second-session',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'ACTIVE_EXAM_SESSION');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_terminal_attempt_does_not_block_next_login(): void
    {
        $attempt = $this->createActiveAttempt();
        $attempt->update(['status' => 'SUBMITTED', 'submitted_at' => now()]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $this->nisn,
            'password' => 'SandiUji#2026',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }


}
