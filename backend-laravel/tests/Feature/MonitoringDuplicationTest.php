<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringDuplicationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCanonicalTeacherAssignment;

    private $tenant;
    private $teacher;
    private $student;
    private $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'T1', 'code' => 'T1']);
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenant->id);
        $this->teacher = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Guru', 'email' => 'guru@e.com', 'password' => 'p']);
        $guruRole = \\App\\Models\\Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $this->teacher->roles()->attach($guruRole);

        $this->student = User::create(['tenant_id' => $this->tenant->id, 'name' => 'S1', 'email' => 's@e.com', 'password' => 'p']);
        $this->student->roles()->create(['name' => 'SISWA', 'label' => 'Siswa']);

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $semester = Semester::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'term' => 'GANJIL', 'is_active' => true]);
        $subject = Subject::create(['tenant_id' => $this->tenant->id, 'name' => 'Matematika', 'code' => 'MTK', 'short_name' => 'MTK', 'status' => 'ACTIVE']);
        $curriculum = Curriculum::create(['name' => 'Kurikulum Uji', 'code' => 'KM']);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $grade = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1]);
        $rombel = Rombel::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $ay->id,
            'grade_level_id' => $grade->id,
            'name' => '1A',
            'status' => 'ACTIVE',
        ]);
        $studentProfile = StudentProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->student->id,
            'full_name' => 'Siswa 1',
        ]);
        $studentProfile->rombels()->attach($rombel->id, ['tenant_id' => $this->tenant->id]);
        $teacherProfile = TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->teacher->id,
            'full_name' => 'Guru',
        ]);
        $assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $ay->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->teacher->id,
        ]);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $ay->id,
            'semester_id' => $semester->id,
            'subject_id' => $subject->id,
            'title' => 'A1',
            'assessment_type' => 'DAILY',
            'created_by' => $this->teacher->id,
        ]);
        $this->bindCanonicalTeacherAssignment($assignment, $assessment);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'E1',
            'duration_seconds' => 60,
            'created_by' => $this->teacher->id,
        ]);
        $this->exam->participants()->create(['tenant_id' => $this->tenant->id, 'user_id' => $this->student->id]);
        $this->bootstrapCbtRolePermissions();
    }

    public function test_participants_list_does_not_duplicate_when_multiple_attempts_exist()
    {
        $participant = ExamParticipant::where('exam_id', $this->exam->id)->first();

        // 1. Create first attempt (e.g., finished)
        $attempt1 = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
        ]);
        $attempt1->created_at = now()->subHour();
        $attempt1->save();

        // 2. Create second attempt (latest)
        $attempt2 = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'student_id' => $this->student->id,
            'attempt_number' => 2,
            'status' => 'IN_PROGRESS',
        ]);
        $attempt2->created_at = now();
        $attempt2->save();

        $this->actingAs($this->teacher, 'sanctum');
        $response = $this->getJson("/api/v1/exams/{$this->exam->id}/monitoring/participants");

        $response->assertStatus(200);
        // Should only have 1 student row despite 2 attempts
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('IN_PROGRESS', $response->json('data.0.attempt.status'));
    }
}
