<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesCanonicalTeacherAssignment;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\GradeLevel;
use App\Models\Rombel;
use App\Models\TeacherProfile;
use App\Models\StudentProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\ExamQuestionSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCanonicalTeacherAssignment;

    private $tenant;
    private $teacher;
    private $student;
    private $exam;
    private $rombel;
    private $subject;
    private $academicYear;
    private $semester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah', 'code' => 'M1']);

        // Set context for model creation
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);

        $this->teacher = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Guru', 'email' => 'guru@m.com', 'password' => 'p']);
        $teacherRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $this->teacher->roles()->attach($teacherRole);

        $this->student = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Siswa', 'email' => 'siswa@m.com', 'password' => 'p']);
        $studentRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        $this->student->roles()->attach($studentRole);

        $this->academicYear = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $this->semester = Semester::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'term' => 'GANJIL',
            'is_active' => true,
        ]);
        $this->subject = Subject::create(['tenant_id' => $this->tenant->id, 'code' => 'MTK', 'name' => 'Matematika', 'status' => 'ACTIVE']);
        $curriculum = Curriculum::create(['tenant_id' => $this->tenant->id, 'name' => 'Kurikulum Merdeka', 'code' => 'KM', 'status' => 'ACTIVE', 'is_active' => true]);
        $phase = Phase::create(['tenant_id' => $this->tenant->id, 'curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A', 'status' => 'ACTIVE']);
        $grade = GradeLevel::create(['tenant_id' => $this->tenant->id, 'phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1, 'status' => 'ACTIVE']);
        $this->rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'grade_level_id' => $grade->id,
            'name' => '1A',
            'status' => 'ACTIVE',
        ]);
        $teacherProfile = TeacherProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->teacher->id, 'full_name' => 'Guru']);
        $studentProfile = StudentProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student->id, 'full_name' => 'Siswa']);
        $this->rombel->students()->attach($studentProfile->id);
        $assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $this->subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'status' => 'ACTIVE',
            'created_by' => $this->teacher->id,
        ]);

        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $this->academicYear->id,
            'semester_id' => $this->semester->id,
            'subject_id' => $this->subject->id,
            'title' => 'A1',
            'assessment_type' => 'DAILY',
            'created_by' => $this->teacher->id,
        ]);

        $this->bindCanonicalTeacherAssignment($assignment, $assessment);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'E1',
            'duration_seconds' => 3600,
            'created_by' => $this->teacher->id,
            'status' => 'DRAFT',
            'start_at' => now()->subMinutes(10),
            'end_at' => now()->addMinutes(50),
            'locked_at' => now()
        ]);

        $this->exam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student->id]);
        $this->bootstrapCbtRolePermissions();
    }

    public function test_teacher_can_access_monitoring_summary()
    {
        $this->actingAs($this->teacher);
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring");

        $response->assertStatus(200);
        $response->assertJsonPath('data.summary.total_participants', 1);
        $response->assertJsonPath('data.summary.not_started', 1);
    }

    public function test_student_cannot_access_monitoring_summary()
    {
        $this->actingAs($this->student);
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring");
        $response->assertStatus(403);
    }

    public function test_cross_tenant_monitoring_denied()
    {
        $tenantB = Tenant::create(['name' => 'Tenant B', 'code' => 'B1']);

        app(\App\Contexts\TenantContext::class)->setTenantId($tenantB->id);
        $teacherB = User::create(['tenant_id' => $tenantB->id, 'name' => 'Guru B', 'email' => 'guru@b.com', 'password' => 'p']);
        $teacherRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $teacherB->roles()->attach($teacherRole);

        $this->actingAs($teacherB);

        // Context will be set by IdentifyTenant middleware during request
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring");

        // TenantScoped will cause 404
        $response->assertStatus(404);
    }

    public function test_heartbeat_updates_last_activity_at()
    {
        [$attempt, $token] = $this->startBoundStudentAttempt('test-device');
        $before = $attempt->fresh()->last_activity_at;

        $response = $this->withToken($token)->withHeaders([
            'X-Device-Binding-ID' => 'test-device',
            'X-CBT-Fence-Generation' => (string) $attempt->fence_generation,
        ])->postJson("/api/v1/exams/{$this->exam->id}/attempts/{$attempt->id}/heartbeat");

        $response->assertStatus(200);
        $this->assertNotEquals($before, $attempt->fresh()->last_activity_at);
    }

    public function test_participants_list_with_search_and_filter()
    {
        $this->actingAs($this->teacher);

        // 1. Search by name
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/participants?search=Siswa");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // 2. Filter by status NOT_STARTED
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/participants?status=NOT_STARTED");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));

        // 3. Start attempt for Siswa
        $participant = ExamParticipant::where('exam_id', $this->exam->id)->where('user_id', $this->student->id)->first();
        ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addHour()
        ]);

        // 4. Filter by status IN_PROGRESS
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/participants?status=IN_PROGRESS");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_monitoring_is_read_only()
    {
        $this->actingAs($this->teacher);

        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring");
        $response->assertStatus(200);

        // Verification that no data mutation occurred on a GET request
        $this->assertEquals('DRAFT', $this->exam->fresh()->status);
    }

    public function test_student_does_not_receive_question_snapshots_before_exam_schedule(): void
    {
        $futureExam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $this->exam->assessment_id,
            'title' => 'Ujian Terjadwal',
            'duration_seconds' => 3600,
            'created_by' => $this->teacher->id,
            'status' => 'DRAFT',
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
            'locked_at' => now(),
        ]);
        $futureExam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student->id]);

        $question = Question::create([
            'tenant_id' => $this->tenant->id,
            'owner_type' => 'PERSONAL',
            'owner_user_id' => $this->teacher->id,
            'status' => 'PUBLISHED',
            'visibility' => 'PRIVATE',
            'subject_id' => $this->subject->id,
            'created_by' => $this->teacher->id,
        ]);
        $version = QuestionVersion::create([
            'tenant_id' => $this->tenant->id,
            'question_id' => $question->id,
            'major_version' => 1,
            'minor_version' => 0,
            'question_type' => 'MULTIPLE_CHOICE',
            'stem' => 'SECRET QUESTION STEM',
            'status' => 'PUBLISHED',
            'is_current' => true,
            'created_by' => $this->teacher->id,
        ]);
        ExamQuestionSnapshot::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $futureExam->id,
            'source_question_id' => $question->id,
            'source_question_version_id' => $version->id,
            'sequence' => 0,
            'points' => 1,
            'snapshot_stem' => 'SECRET QUESTION STEM',
            'snapshot_options' => [['id' => 'option-a', 'label' => 'A', 'is_correct' => true]],
            'metadata' => ['question_type' => 'MULTIPLE_CHOICE'],
        ]);

        $this->actingAs($this->student);
        $this->getJson("/api/v1/exams/{$futureExam->id}")
            ->assertStatus(200)
            ->assertJsonMissingPath('data.snapshots')
            ->assertDontSee('SECRET QUESTION STEM');

        $this->getJson("/api/v1/exams/{$futureExam->id}/preview")
            ->assertStatus(403);
    }

    public function test_student_cannot_list_all_exam_participants(): void
    {
        $this->actingAs($this->student);

        $this->getJson("/api/v1/exams/{$this->exam->id}/participants")
            ->assertStatus(403);
    }

    public function test_student_cannot_read_another_students_attempt_through_monitoring_endpoint(): void
    {
        $otherStudent = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Kedua',
            'email' => 'siswa2@m.com',
            'password' => 'p',
        ]);
        $otherStudent->roles()->attach(Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']));
        $otherProfile = StudentProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $otherStudent->id, 'full_name' => 'Siswa Kedua']);
        $this->rombel->students()->attach($otherProfile->id);
        $otherParticipant = $this->exam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $otherStudent->id]);
        $otherAttempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $otherParticipant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($this->student);
        $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/attempts/{$otherAttempt->id}")
            ->assertStatus(403);
    }

    public function test_student_cannot_read_another_students_attempt_through_nested_show(): void
    {
        $otherStudent = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Ketiga',
            'email' => 'siswa3@m.com',
            'password' => 'p',
        ]);
        $otherStudent->roles()->attach(Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']));
        $otherProfile = StudentProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $otherStudent->id, 'full_name' => 'Siswa Ketiga']);
        $this->rombel->students()->attach($otherProfile->id);
        $otherParticipant = $this->exam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $otherStudent->id]);
        $otherAttempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $otherParticipant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'started_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        $this->actingAs($this->student);
        $this->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$otherAttempt->id}")
            ->assertStatus(404);
    }

    public function test_student_can_read_only_own_attempt_detail(): void
    {
        [$ownAttempt, $token] = $this->startBoundStudentAttempt('own-attempt-device');

        $this->withToken($token)->withHeaders([
            'X-Device-Binding-ID' => 'own-attempt-device',
            'X-CBT-Fence-Generation' => (string) $ownAttempt->fence_generation,
        ])->getJson("/api/v1/exams/{$this->exam->id}/attempts/{$ownAttempt->id}")
            ->assertStatus(200);
    }

    /**
     * Start an attempt through the real API so the auth-session binding and
     * DeviceLease/fence are created exactly as they are for an exam participant.
     *
     * @return array{0: ExamAttempt, 1: string}
     */
    private function startBoundStudentAttempt(string $deviceId): array
    {
        $this->exam->forceFill([
            'status' => 'PUBLISHED',
            'require_token' => false,
            'start_at' => now()->subMinute(),
            'end_at' => now()->addHour(),
            'locked_at' => $this->exam->locked_at ?? now(),
        ])->save();

        $token = $this->student->createToken('monitoring-test')->plainTextToken;
        $response = $this->withToken($token)->withHeaders([
            'X-Device-Binding-ID' => $deviceId,
        ])->postJson("/api/v1/exams/{$this->exam->id}/attempts", []);

        $response->assertCreated();
        $attempt = ExamAttempt::query()->whereKey($response->json('data.id'))->firstOrFail();
        $attempt->forceFill(['last_activity_at' => now()->subMinutes(5)])->save();
        $attempt->refresh();

        return [$attempt, $token];
    }

}
