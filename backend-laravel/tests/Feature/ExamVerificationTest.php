<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\ExamQuestionSnapshot;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\Question;
use App\Models\QuestionVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExamVerificationTest extends TestCase
{
    use RefreshDatabase;

    private $tenantA;
    private $tenantB;
    private $adminA;
    private $guruA;
    private $subjectA;
    private $semesterA;
    private $snapshotA;
    private string $studentToken;
    private string $guruToken;
    private $siswaA;
    private $examA;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantA = Tenant::create(['name' => 'Madrasah A', 'code' => 'A']);
        $this->tenantB = Tenant::create(['name' => 'Madrasah B', 'code' => 'B']);

        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $this->adminA = User::create(['tenant_id' => $this->tenantA->id, 'name' => 'Admin A', 'email' => 'admin@a.com', 'password' => 'p']);
        $this->adminA->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin'])
        );

        $this->guruA = User::create(['tenant_id' => $this->tenantA->id, 'name' => 'Guru A', 'email' => 'guru@a.com', 'password' => 'p']);
        $this->guruA->roles()->attach(\App\Models\Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']));

        $this->siswaA = User::create(['tenant_id' => $this->tenantA->id, 'name' => 'Siswa A', 'email' => 'siswa@a.com', 'password' => 'p']);
        $this->siswaA->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );
        $this->studentToken = $this->siswaA->createToken('exam-verification-student')->plainTextToken;
        $this->guruToken = $this->guruA->createToken('exam-verification-guru')->plainTextToken;

        $ay = AcademicYear::create(['tenant_id' => $this->tenantA->id, 'name' => '2024']);
        $this->semesterA = Semester::create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $ay->id, 'term' => 'GANJIL', 'is_active' => true]);
        $this->subjectA = Subject::create(['tenant_id' => $this->tenantA->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'status' => 'ACTIVE']);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenantA->id,
            'academic_year_id' => $ay->id,
            'semester_id' => $this->semesterA->id,
            'subject_id' => $this->subjectA->id,
            'title' => 'Daily',
            'assessment_type' => 'DAILY',
            'created_by' => $this->guruA->id,
        ]);

        $this->examA = Exam::create([
            'assessment_id' => $assessment->id,
            'title' => 'Test Exam',
            'duration_seconds' => 3600,
            'created_by' => $this->guruA->id,
            'status' => 'DRAFT',
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(50),
            'require_token' => true,
            'token' => 'SECRET'
        ]);

        $question = Question::create([
            'tenant_id' => $this->tenantA->id,
            'owner_type' => 'TENANT',
            'owner_user_id' => $this->guruA->id,
            'question_code' => 'VERIFY-01',
            'created_by' => $this->guruA->id,
        ]);
        $version = QuestionVersion::create([
            'tenant_id' => $this->tenantA->id,
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'ESSAY',
            'stem' => 'Soal verifikasi',
            'status' => 'PUBLISHED',
            'is_current' => true,
            'created_by' => $this->guruA->id,
        ]);
        $this->snapshotA = ExamQuestionSnapshot::create([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => $this->examA->id,
            'source_question_id' => $question->id,
            'source_question_version_id' => $version->id,
            'sequence' => 1,
            'points' => 10,
            'snapshot_stem' => 'Soal verifikasi',
            'snapshot_options' => [],
            'snapshot_media' => [],
            'metadata' => ['question_type' => 'ESSAY'],
            'created_by' => $this->guruA->id,
        ]);

        $participant = ExamParticipant::create([
            'user_id' => $this->siswaA->id,
            'exam_id' => $this->examA->id,
            'status' => 'ASSIGNED'
        ]);

        $context->setTenantId(null);
        $this->bootstrapCbtRolePermissions();
    }

    /** 1. Lifecycle Verification **/
    public function test_exam_status_transitions_correctly()
    {
        $exam = $this->examA;

        // 1. DRAFT (unlocked)
        $this->assertEquals('DRAFT', $exam->current_status);

        // 2. SCHEDULED (locked, before start)
        $exam->update([
            'locked_at' => now(),
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2)
        ]);
        $this->assertEquals('SCHEDULED', $exam->current_status);

        // 3. ONGOING (locked, within window)
        $exam->update([
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(10)
        ]);
        $this->assertEquals('ONGOING', $exam->current_status);

        // 4. CLOSED (locked, after window)
        $exam->update([
            'start_at' => now()->subMinutes(20),
            'end_at' => now()->subMinutes(10)
        ]);
        $this->assertEquals('CLOSED', $exam->current_status);

        // 5. Explicitly CLOSED
        $exam->update(['status' => 'CLOSED']);
        $this->assertEquals('CLOSED', $exam->current_status);

        // 6. ARCHIVED
        $exam->update(['status' => 'ARCHIVED']);
        $this->assertEquals('ARCHIVED', $exam->current_status);
    }

    /** 2. Token Security Verification **/
    public function test_token_validation_and_rate_limiting()
    {
        $this->examA->update(['status' => 'PUBLISHED', 'published_at' => now(), 'locked_at' => now()]); // Make it ongoing
        $this->withToken($this->studentToken);

        // Valid Token
        $response = $this->withHeaders(['X-Device-Binding-ID' => 'dev-1', 'X-CBT-Fence-Generation' => '1'])
                         ->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'SECRET']);
        $response->assertStatus(201);

        // Reset dependent device leases before deleting attempts; the production FK is RESTRICT.
        $attemptIds = $this->examA->attempts()->pluck('id');
        \\Illuminate\\Support\\Facades\\DB::table('device_leases')
            ->where('tenant_id', $this->tenantA->id)
            ->whereIn('exam_attempt_id', $attemptIds)
            ->delete();

        // Reset participant to try again (limit override)
        $this->examA->attempts()->delete();

        // Invalid Token
        $response = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'WRONG1']);
        $response->assertStatus(422);

        // Brute force (5 hits)
        for ($i = 0; $i < 4; $i++) {
            $this->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'WRONG1']);
        }
        $response = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'WRONG1']);
        $response->assertStatus(429);
    }

    /** 3. Attempt Submission Verification **/
    public function test_attempt_submission_integrity()
    {
        $this->withToken($this->studentToken);
        $this->withHeaders([
            'X-Device-Binding-ID' => 'dev-1',
            'X-CBT-Fence-Generation' => '1',
        ]);
        $startResponse = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts", ['token' => 'SECRET']);
        $startResponse->assertStatus(201);
        $attempt = ExamAttempt::query()->findOrFail($startResponse->json('data.id'));

        // Valid Submission
        $response = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$attempt->id}/submit");
        $response->assertStatus(200);
        $this->assertEquals('SUBMITTED', $attempt->fresh()->status);
        $submittedAt = $attempt->fresh()->submitted_at;
        $this->assertNotNull($submittedAt);

        // Double Submission Denied
        $response = $this->postJson("/api/v1/exams/{$this->examA->id}/attempts/{$attempt->id}/submit");
        $response->assertStatus(200)->assertJsonPath('idempotent', true);
        $this->assertEquals($submittedAt->toDateTimeString(), $attempt->fresh()->submitted_at->toDateTimeString());
    }

    /** 4. Participant Rombel Verification **/
    public function test_rombel_assignment_isolation_and_snapshot()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $tp = TeacherProfile::create([
            'tenant_id' => $this->tenantA->id,
            'user_id' => $this->guruA->id,
            'full_name' => 'Guru A'
        ]);

        $curr = \App\Models\Curriculum::create(['name' => 'K', 'code' => 'K-' . Str::random(4)]);

        $phase = \App\Models\Phase::create(['name' => 'A', 'code' => 'A-' . Str::random(4), 'curriculum_id' => $curr->id]);

        $gl = \App\Models\GradeLevel::create(['name' => '5', 'code' => '5-' . Str::random(4), 'level' => 5, 'phase_id' => $phase->id]);

        $rombel = Rombel::create([
            'academic_year_id' => $this->examA->assessment->academic_year_id,
            'grade_level_id' => $gl->id,
            'name' => '5A',
            'teacher_id' => $tp->id
        ]);

        $student = StudentProfile::create([
            'user_id' => $this->siswaA->id,
            'nis' => '123', 'full_name' => 'Siswa A'
        ]);

        \Illuminate\Support\Facades\DB::table('rombel_student')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenantA->id,
            'rombel_id' => $rombel->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Replace the setup participant with the Rombel-scoped assignment under test.
        $this->examA->participants()->delete();
        TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenantA->id,
            'teacher_profile_id' => $tp->id,
            'subject_id' => $this->subjectA->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $this->examA->assessment->academic_year_id,
            'semester_id' => $this->semesterA->id,
            'starts_at' => now()->subDay()->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->guruA->id,
        ]);

        $this->withToken($this->guruToken);

        // Assign
        $response = $this->postJson("/api/v1/exams/{$this->examA->id}/assign-rombel", ['rombel_id' => $rombel->id]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('exam_participants', ['exam_id' => $this->examA->id, 'user_id' => $this->siswaA->id]);

        // Duplicate Check (Idempotency)
        $this->postJson("/api/v1/exams/{$this->examA->id}/assign-rombel", ['rombel_id' => $rombel->id]);
        $this->assertEquals(1, ExamParticipant::where('exam_id', $this->examA->id)->where('user_id', $this->siswaA->id)->count());

        // Snapshot Check: Remove from Rombel, participant stays
        $rombel->students()->detach($student->id);
        $this->assertDatabaseHas('exam_participants', ['exam_id' => $this->examA->id, 'user_id' => $this->siswaA->id]);
    }

    /** 5. Snapshot Negative Tests **/
    public function test_cannot_modify_snapshots_after_lock()
    {
        $this->examA->update(['locked_at' => now()]);

        $this->actingAs($this->guruA, 'sanctum');

        // Add Questions
        $response = $this->postJson("/api/v1/exams/{$this->examA->id}/questions", ['question_version_ids' => []]);
        $response->assertStatus(403); // canBeEdited() check in policy

        // Remove the existing immutable snapshot.
        // Reorder
        $response = $this->postJson("/api/v1/exams/{$this->examA->id}/questions/reorder", ['orders' => []]);
        $response->assertStatus(403);

        // Remove
        $response = $this->deleteJson("/api/v1/exams/{$this->examA->id}/questions/{$this->snapshotA->id}");
        $response->assertStatus(403);
    }

    /** 6. RBAC & Tenant Isolation **/
    public function test_legacy_platform_super_admin_is_denied_even_with_tenant_override_header()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $super = User::create(['tenant_id' => $this->tenantA->id, 'name' => 'Super', 'email' => 's@s.com', 'password' => bcrypt('p')]);
        $role = \App\Models\Role::firstOrCreate(['name' => 'SUPER_ADMIN'], ['label' => 'Super']);
        $super->roles()->syncWithoutDetaching([$role->id]);
        $super->load('roles'); // Ensure roles are loaded for hasRole check

        $this->assertTrue($super->hasRole('SUPER_ADMIN'));

        $this->actingAs($super, 'sanctum');

        // Legacy platform identity must be rejected by the CBT tenant boundary,
        // regardless of whether an override header is supplied.
        $this->getJson("/api/v1/exams/{$this->examA->id}")->assertStatus(403);
        $this->withHeader('X-Tenant-Override', $this->tenantB->id)
             ->getJson("/api/v1/exams/{$this->examA->id}")->assertStatus(403);
    }
}
