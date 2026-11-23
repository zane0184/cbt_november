<?php

namespace Tests\Feature;

use Tests\Concerns\CreatesCanonicalTeacherAssignment;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Assessment;
use App\Models\Exam;
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
use App\Models\Role;
use App\Contexts\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamListContractTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCanonicalTeacherAssignment;

    private $tenant;
    private $teacher;
    private $siswa;
    private $rombel;
    private $studentProfile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah', 'code' => 'M1']);

        $context = app(TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $this->teacher = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Guru', 'email' => 'guru@m.com', 'password' => 'p']);
        $this->teacher->roles()->attach(Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']));

        $this->siswa = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Siswa', 'email' => 'siswa@m.com', 'password' => 'p']);
        $this->siswa->roles()->attach(Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']));

        $ay = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2024']);
        $semester = Semester::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'term' => 'GANJIL', 'is_active' => true]);
        $subject = Subject::create(['tenant_id' => $this->tenant->id, 'code' => 'MTK', 'name' => 'Matematika', 'status' => 'ACTIVE']);
        $curriculum = Curriculum::create(['tenant_id' => $this->tenant->id, 'name' => 'Kurikulum Merdeka', 'code' => 'KM', 'status' => 'ACTIVE', 'is_active' => true]);
        $phase = Phase::create(['tenant_id' => $this->tenant->id, 'curriculum_id' => $curriculum->id, 'name' => 'Fase A', 'code' => 'A', 'status' => 'ACTIVE']);
        $grade = GradeLevel::create(['tenant_id' => $this->tenant->id, 'phase_id' => $phase->id, 'name' => 'Kelas 1', 'code' => '1', 'level' => 1, 'status' => 'ACTIVE']);
        $this->rombel = Rombel::create(['tenant_id' => $this->tenant->id, 'academic_year_id' => $ay->id, 'grade_level_id' => $grade->id, 'name' => '1A', 'status' => 'ACTIVE']);
        $teacherProfile = TeacherProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->teacher->id, 'full_name' => 'Guru']);
        $this->studentProfile = StudentProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->siswa->id, 'full_name' => 'Siswa']);
        $this->rombel->students()->attach($this->studentProfile->id);
        $assignment = TeacherSubjectAssignment::create([
            'tenant_id' => $this->tenant->id,
            'teacher_profile_id' => $teacherProfile->id,
            'subject_id' => $subject->id,
            'rombel_id' => $this->rombel->id,
            'academic_year_id' => $ay->id,
            'semester_id' => $semester->id,
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

        Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Exam 1',
            'duration_seconds' => 3600,
            'created_by' => $this->teacher->id,
            'status' => 'DRAFT'
        ]);

        $context->setTenantId(null);
    }

    public function test_exam_index_returns_flattened_pagination_contract()
    {
        $this->actingAs($this->teacher, 'sanctum');

        $response = $this->getJson('/api/v1/exams');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'status', 'assessment']
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                    'from',
                    'to'
                ]
            ]);

        // Verify data is an array
        $this->assertIsArray($response->json('data'));
        $this->assertCount(1, $response->json('data'));
    }

    public function test_unauthenticated_request_is_rejected()
    {
        $response = $this->getJson('/api/v1/exams');
        $response->assertStatus(401);
    }

    public function test_siswa_only_sees_assigned_exams()
    {
        $context = app(TenantContext::class);
        $context->setTenantId($this->tenant->id);

        // This exam is NOT assigned to siswa
        $ay = AcademicYear::where('name', '2024')->first();
        $assessment = Assessment::where('title', 'A1')->first();
        Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Unassigned Exam',
            'duration_seconds' => 3600,
            'created_by' => $this->teacher->id,
            'status' => 'DRAFT'
        ]);

        $context->setTenantId(null);

        $this->actingAs($this->siswa, 'sanctum');
        $response = $this->getJson('/api/v1/exams');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));

        // Assign one exam
        $exam = Exam::where('title', 'Exam 1')->first();
        $context->setTenantId($this->tenant->id);
        $exam->participants()->create(['user_id' => $this->siswa->id, 'tenant_id' => $this->tenant->id]);
        $context->setTenantId(null);

        $response = $this->getJson('/api/v1/exams');
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Exam 1', $response->json('data.0.title'));
    }

    public function test_tenant_isolation_remains_active()
    {
        $tenantB = Tenant::create(['name' => 'Madrasah B', 'code' => 'B']);

        $context = app(TenantContext::class);
        $context->setTenantId($tenantB->id);

        $teacherB = User::create(['tenant_id' => $tenantB->id, 'name' => 'Guru B', 'email' => 'b@b.com', 'password' => 'p']);
        $teacherB->roles()->attach(Role::where('name', 'GURU')->first());

        $ayB = AcademicYear::create(['tenant_id' => $tenantB->id, 'name' => '2025']);
        $assessmentB = Assessment::create(['tenant_id' => $tenantB->id, 'academic_year_id' => $ayB->id, 'title' => 'B1', 'assessment_type' => 'DAILY', 'created_by' => $teacherB->id]);

        Exam::create([
            'tenant_id' => $tenantB->id,
            'assessment_id' => $assessmentB->id,
            'title' => 'Exam B',
            'duration_seconds' => 3600,
            'created_by' => $teacherB->id,
            'status' => 'DRAFT'
        ]);

        $context->setTenantId(null);

        $this->actingAs($this->teacher, 'sanctum'); // User from Tenant A
        $response = $this->getJson('/api/v1/exams');

        $response->assertStatus(200);
        // Should only see Exam 1 (Tenant A), not Exam B (Tenant B)
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Exam 1', $response->json('data.0.title'));
    }
}
