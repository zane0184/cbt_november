<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesCanonicalTeacherAssignment;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\Rombel;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Curriculum;
use App\Models\Phase;
use App\Models\GradeLevel;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\StudentProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamIntegrityTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCanonicalTeacherAssignment;

    private $tenantA;
    private $tenantB;
    private $userA;
    private $userB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantA = Tenant::create(['name' => 'A', 'code' => 'A']);
        $this->tenantB = Tenant::create(['name' => 'B', 'code' => 'B']);

        $context = app(\App\Contexts\TenantContext::class);

        $context->setTenantId($this->tenantA->id);
        $this->userA = User::create(['tenant_id' => $this->tenantA->id, 'name' => 'A', 'email' => 'a@a.com', 'password' => 'p']);

        $context->setTenantId($this->tenantB->id);
        $this->userB = User::create(['tenant_id' => $this->tenantB->id, 'name' => 'B', 'email' => 'b@b.com', 'password' => 'p']);

        $context->setTenantId(null);
    }

    public function test_tenant_a_cannot_assign_student_from_tenant_b()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $this->userA->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru'])
        );
        $curriculum = Curriculum::create(['name' => 'Kurikulum Merdeka', 'code' => 'KM-A']);
        $phase = Phase::create(['curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A']);
        $ayA = AcademicYear::create(['tenant_id' => $this->tenantA->id, 'name' => '2024']);
        $semester = Semester::create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $ayA->id, 'term' => 'GANJIL', 'is_active' => true]);
        $subject = Subject::create(['tenant_id' => $this->tenantA->id, 'code' => 'MAT', 'name' => 'Matematika', 'short_name' => 'MTK', 'status' => 'ACTIVE']);
        $gradeLevel = GradeLevel::create(['phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1]);
        $rombel = Rombel::create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $ayA->id, 'grade_level_id' => $gradeLevel->id, 'name' => '1A', 'status' => 'ACTIVE']);
        $teacherProfile = TeacherProfile::create(['tenant_id' => $this->tenantA->id, 'user_id' => $this->userA->id, 'full_name' => 'Guru A']);
        $assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenantA->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $rombel->id,
            'academic_year_id' => $ayA->id,
            'semester_id' => $semester->id,
            'starts_at' => now()->subDay()->toDateString(),
            'status' => 'ACTIVE',
            'created_by' => $this->userA->id,
        ]);
        $assessmentA = Assessment::create(['tenant_id' => $this->tenantA->id, 'subject_id' => $subject->id, 'semester_id' => $semester->id, 'academic_year_id' => $ayA->id, 'title' => 'A', 'assessment_type' => 'DAILY', 'created_by' => $this->userA->id]);
        $this->bindCanonicalTeacherAssignment($assignment, $assessmentA);
        $examA = Exam::create(['tenant_id' => $this->tenantA->id, 'assessment_id' => $assessmentA->id, 'title' => 'A', 'duration_seconds' => 60, 'created_by' => $this->userA->id]);
        $this->bootstrapCbtRolePermissions();

        $this->actingAs($this->userA, 'sanctum');

        $response = $this->postJson("/api/v1/exams/{$examA->id}/participants", [
            'user_id' => $this->userB->id // Student from Tenant B
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['user_id']);
    }

    public function test_attempt_lifecycle_restrictions()
    {
        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $ayA = AcademicYear::create(['tenant_id' => $this->tenantA->id, 'name' => '2024']);
        $assessmentA = Assessment::create(['tenant_id' => $this->tenantA->id, 'academic_year_id' => $ayA->id, 'title' => 'A', 'assessment_type' => 'DAILY', 'created_by' => $this->userA->id]);

        // Exam in DRAFT
        $examA = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessmentA->id,
            'title' => 'A',
            'duration_seconds' => 60,
            'created_by' => $this->userA->id,
            'status' => 'DRAFT',
            'start_at' => now()->addHour(),
            'end_at' => now()->addHours(2),
            'require_token' => true,
            'token' => 'ABC123',
        ]);

        // Student participant
        $studentUser = User::create(['tenant_id' => $this->tenantA->id, 'name' => 'S', 'email' => 's@s.com', 'password' => 'p']);
        $studentUser->roles()->attach(
            \App\Models\Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa'])
        );
        $participant = new \App\Models\ExamParticipant(['user_id' => $studentUser->id]);
        $participant->tenant_id = $this->tenantA->id;
        $participant->exam_id = $examA->id;
        $participant->save();

        $this->actingAs($studentUser, 'sanctum');

        // Attempt before locked/scheduled; token is valid, but server schedule must deny it.
        $response = $this->withHeader('X-Device-Binding-ID', 'dev-1')
                         ->postJson("/api/v1/exams/{$examA->id}/attempts", ['token' => 'ABC123']);
        $response->assertStatus(403);
    }
}
