<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Curriculum;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamParticipant;
use App\Models\GradeLevel;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionBootstrapper;
use App\Livewire\Operator\OperatorDashboard;
use App\Livewire\Kamad\KamadDashboard;
use App\Livewire\Proktor\ProktorDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleDashboardVerificationTest extends TestCase
{
    use RefreshDatabase;

    private $tenantA;
    private $tenantB;
    private $superAdmin;
    private $adminA;
    private $operatorA;
    private $kamadA;
    private $proctorA;
    private $guruA;
    private $siswaA;

    private $adminB;
    private $siswaB;

    private $roleSuperAdmin;
    private $roleAdmin;
    private $roleOperator;
    private $roleKamad;
    private $roleProctor;
    private $roleTeacher;
    private $roleStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'Madrasah Demo A', 'code' => 'DEMOA']);
        $this->tenantB = Tenant::create(['name' => 'Madrasah Demo B', 'code' => 'DEMOB']);

        $context = app(\App\Contexts\TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $this->roleSuperAdmin = Role::create(['name' => 'SUPER_ADMIN', 'label' => 'Super Admin']);
        $this->roleAdmin      = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $this->roleOperator   = Role::create(['name' => 'OPERATOR', 'label' => 'Operator']);
        $this->roleKamad      = Role::create(['name' => 'KAMAD', 'label' => 'Kepala Madrasah']);
        $this->roleProctor    = Role::create(['name' => 'PROKTOR', 'label' => 'Proktor']);
        $this->roleTeacher    = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $this->roleStudent    = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        app(RolePermissionBootstrapper::class)->bootstrap();

        // Tenant A Users
        $this->superAdmin = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Super Admin',
            'email' => 'super@demoa.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($this->roleSuperAdmin);

        $this->adminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin A',
            'email' => 'admin@demoa.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->adminA->roles()->attach($this->roleAdmin);

        $this->operatorA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Operator A',
            'email' => 'operator@demoa.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->operatorA->roles()->attach($this->roleOperator);

        $this->kamadA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Kamad A',
            'email' => 'kamad@demoa.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->kamadA->roles()->attach($this->roleKamad);

        $this->proctorA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Proktor A',
            'email' => 'proctor@demoa.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->proctorA->roles()->attach($this->roleProctor);

        $this->guruA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Guru A',
            'email' => 'guru@demoa.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->guruA->roles()->attach($this->roleTeacher);

        $this->siswaA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Siswa A',
            'email' => 'siswa@demoa.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->siswaA->roles()->attach($this->roleStudent);

        // Tenant B Users
        $context->setTenantId($this->tenantB->id);
        $this->adminB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Admin B',
            'email' => 'admin@demob.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->adminB->roles()->attach($this->roleAdmin);

        $this->siswaB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Siswa B',
            'email' => 'siswa@demob.sch.id',
            'password' => bcrypt('password'),
        ]);
        $this->siswaB->roles()->attach($this->roleStudent);

        $context->setTenantId(null);
    }

    /** @test */
    public function operator_dashboard_access_control()
    {
        // Unauthenticated
        $this->get('/operator/dashboard')->assertRedirect('/login');

        // Authorized roles
        $this->actingAs($this->operatorA)->get('/operator/dashboard')->assertStatus(200);
        $this->actingAs($this->adminA)->get('/operator/dashboard')->assertStatus(200);
        $this->actingAs($this->superAdmin)->get('/operator/dashboard')->assertStatus(403);

        // Unauthorized roles
        $this->actingAs($this->kamadA)->get('/operator/dashboard')->assertStatus(403);
        $this->actingAs($this->siswaA)->get('/operator/dashboard')->assertStatus(403);
    }

    /** @test */
    public function kamad_has_no_separate_permanent_dashboard_route()
    {
        // Kamad retains monitoring/report permissions but uses the approved portal structure.
        $this->get('/kamad/dashboard')->assertNotFound();
        $this->actingAs($this->kamadA)->get('/kamad/dashboard')->assertNotFound();
        $this->actingAs($this->adminA)->get('/kamad/dashboard')->assertNotFound();
        $this->actingAs($this->operatorA)->get('/kamad/dashboard')->assertNotFound();
        $this->actingAs($this->siswaA)->get('/kamad/dashboard')->assertNotFound();
    }

    /** @test */
    public function proktor_has_only_an_assignment_surface_not_a_permanent_dashboard()
    {
        $this->get('/proktor/dashboard')->assertNotFound();
        $this->actingAs($this->proctorA)->get('/proktor/dashboard')->assertNotFound();

        // The assignment surface is restricted to a Proktor identity; no tenant-wide
        // exam list is exposed until a temporary assignment record exists.
        $this->actingAs($this->proctorA)->get('/proktor/assignments')->assertStatus(200);
        $this->actingAs($this->adminA)->get('/proktor/assignments')->assertStatus(403);
        $this->actingAs($this->superAdmin)->get('/proktor/assignments')->assertStatus(403);
        $this->actingAs($this->guruA)->get('/proktor/assignments')->assertStatus(403);
        $this->actingAs($this->operatorA)->get('/proktor/assignments')->assertStatus(403);
        $this->actingAs($this->kamadA)->get('/proktor/assignments')->assertStatus(403);
        $this->actingAs($this->siswaA)->get('/proktor/assignments')->assertStatus(403);
    }

    /** @test */
    public function guru_dashboard_access_control_and_sidebar_isolation()
    {
        // Authorized routes for GURU
        $this->actingAs($this->guruA)->get('/admin/exams')->assertStatus(200);
        $this->actingAs($this->guruA)->get('/admin/questions')->assertStatus(200);

        // Sidebar rendering verification for GURU (Proktor Dashboard menu MUST NOT be present)
        $response = $this->actingAs($this->guruA)->get('/admin/exams');
        $response->assertDontSee('Dashboard Proktor');
        $response->assertSee('Manajemen Ujian');
        $response->assertSee('Bank Soal');

        // Sidebar rendering verification for OPERATOR (Proktor Dashboard menu MUST NOT be present)
        $opResponse = $this->actingAs($this->operatorA)->get('/operator/dashboard');
        $opResponse->assertDontSee('Dashboard Proktor');
        $opResponse->assertSee('Dashboard Operator');

        // Sidebar rendering verification for PROCTOR (Proktor Dashboard menu MUST be present)
        $proctorResponse = $this->actingAs($this->proctorA)->get('/proktor/assignments');
        $proctorResponse->assertSee('Penugasan Proktor');
        $proctorResponse->assertDontSee('Dashboard Proktor');
        $proctorResponse->assertDontSee('Token:');
    }

    /** @test */
    public function operator_legacy_role_cannot_access_admin_or_cbt_modules()
    {
        $this->actingAs($this->operatorA);
        $this->get('/admin/curriculum')->assertStatus(403);
        $this->get('/admin/exams')->assertStatus(403);
    }

    /** @test */
    public function operator_and_student_cannot_open_teacher_result_detail_surface()
    {
        $attempt = new ExamAttempt([
            'tenant_id' => $this->tenantA->id,
            'exam_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->operatorA)->allows('view', $attempt));
        $this->assertFalse(\Illuminate\Support\Facades\Gate::forUser($this->siswaA)->allows('view', $attempt));
    }

    /** @test */
    public function kamad_cannot_manage_exams()
    {
        $this->actingAs($this->kamadA);
        $this->get('/admin/exams')->assertStatus(403);
    }

    /** @test */
    public function monitoring_access_control_and_tenant_isolation()
    {
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantA->id);

        $curriculum = Curriculum::create(['name' => 'K-Merdeka', 'code' => 'KM', 'is_active' => true]);
        $year = AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);
        $assessment = Assessment::create([
            'academic_year_id' => $year->id,
            'title' => 'Sumatif Mid',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->adminA->id
        ]);

        $exam = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Mid Semester',
            'duration_seconds' => 3600,
            'status' => 'PUBLISHED',
            'created_by' => $this->adminA->id,
        ]);

        $participant = ExamParticipant::create([
            'exam_id' => $exam->id,
            'user_id' => $this->siswaA->id,
            'status' => 'ASSIGNED',
        ]);
        $attempt = ExamAttempt::create([
            'exam_id' => $exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'IN_PROGRESS',
            'device_fingerprint' => 'DEVICE-FINGERPRINT-SECRET',
            'server_start_reference' => 'SERVER-REFERENCE-SECRET',
            'question_order' => ['QUESTION-ORDER-SECRET'],
            'options_order' => ['OPTIONS-ORDER-SECRET'],
            'started_at' => now(),
            'expires_at' => now()->addHour(),
        ]);

        app(\App\Contexts\TenantContext::class)->setTenantId(null);

        // Tenant A authorized users can access monitoring
        $this->actingAs($this->proctorA)->get("/exams/{$exam->id}/monitoring")->assertStatus(403);
        $this->actingAs($this->kamadA)->get("/exams/{$exam->id}/monitoring")->assertStatus(200);
        $this->actingAs($this->operatorA)->get("/exams/{$exam->id}/monitoring")->assertStatus(403);
        $this->actingAs($this->adminA)->get("/exams/{$exam->id}/monitoring")->assertStatus(403);

        // The monitoring detail response must be an allowlist, not a raw attempt model.
        $attemptResponse = $this->actingAs($this->kamadA, 'sanctum')
            ->getJson("/api/v1/exams/{$exam->id}/monitoring/attempts/{$attempt->id}");
        $attemptResponse->assertStatus(200)
            ->assertJsonPath('data.attempt.participant.user.name', 'Siswa A')
            ->assertJsonMissingPath('data.attempt.tenant_id')
            ->assertJsonMissingPath('data.attempt.device_fingerprint')
            ->assertJsonMissingPath('data.attempt.server_start_reference')
            ->assertJsonMissingPath('data.attempt.question_order')
            ->assertJsonMissingPath('data.attempt.options_order')
            ->assertDontSee('DEVICE-FINGERPRINT-SECRET')
            ->assertDontSee('SERVER-REFERENCE-SECRET')
            ->assertDontSee('QUESTION-ORDER-SECRET')
            ->assertDontSee('OPTIONS-ORDER-SECRET');

        // Tenant A Siswa is blocked
        $this->actingAs($this->siswaA)->get("/exams/{$exam->id}/monitoring")->assertStatus(403);

        // Tenant B Admin is blocked by tenant isolation (404 or 403)
        $responseB = $this->actingAs($this->adminB)->get("/exams/{$exam->id}/monitoring");
        $this->assertTrue(in_array($responseB->getStatusCode(), [403, 404]));
    }

    /** @test */
    public function dashboards_render_tenant_isolated_data()
    {
        // Set up Tenant A data
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantA->id);
        $curriculumA = Curriculum::create(['name' => 'K-Merdeka A', 'code' => 'KM-A', 'is_active' => true]);
        $phaseA = Phase::create(['curriculum_id' => $curriculumA->id, 'name' => 'Fase E', 'code' => 'E']);
        $gradeLevelA = GradeLevel::create(['phase_id' => $phaseA->id, 'name' => 'Kelas X', 'code' => 'X', 'level' => 10]);

        $yearA = AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);
        $rombelA = Rombel::create(['name' => 'X-1', 'academic_year_id' => $yearA->id, 'grade_level_id' => $gradeLevelA->id]);

        $assessmentA = Assessment::create([
            'academic_year_id' => $yearA->id,
            'title' => 'Assesmen A',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->adminA->id
        ]);

        $examA = Exam::create([
            'tenant_id' => $this->tenantA->id,
            'assessment_id' => $assessmentA->id,
            'title' => 'Ujian A',
            'duration_seconds' => 1800,
            'status' => 'PUBLISHED',
            'created_by' => $this->adminA->id,
        ]);

        // Set up Tenant B data
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantB->id);
        $curriculumB = Curriculum::create(['name' => 'K-Merdeka B', 'code' => 'KM-B', 'is_active' => true]);
        $phaseB = Phase::create(['curriculum_id' => $curriculumB->id, 'name' => 'Fase F', 'code' => 'F']);
        $gradeLevelB = GradeLevel::create(['phase_id' => $phaseB->id, 'name' => 'Kelas XI', 'code' => 'XI', 'level' => 11]);

        $yearB = AcademicYear::create(['name' => '2024/2025', 'is_active' => true]);
        $rombelB1 = Rombel::create(['name' => 'XI-1', 'academic_year_id' => $yearB->id, 'grade_level_id' => $gradeLevelB->id]);
        $rombelB2 = Rombel::create(['name' => 'XI-2', 'academic_year_id' => $yearB->id, 'grade_level_id' => $gradeLevelB->id]);

        app(\App\Contexts\TenantContext::class)->setTenantId(null);

        // Test Operator Dashboard for Tenant A
        $this->actingAs($this->operatorA);
        Livewire::test(OperatorDashboard::class)
            ->assertViewHas('totalStudents', 1)
            ->assertViewHas('totalRombels', 1)
            ->assertViewHas('totalExams', 1);

        // Test Kamad Dashboard for Tenant A
        $this->actingAs($this->kamadA);
        Livewire::test(KamadDashboard::class)
            ->assertViewHas('totalExams', 1)
            ->assertViewHas('activeExams', 1);

        // Test Proktor Dashboard for Tenant A
        $this->actingAs($this->proctorA);
        Livewire::test(ProktorDashboard::class)
            ->assertViewHas('activeExams', fn ($exams) => $exams->isEmpty())
            ->assertViewHas('inProgressAttempts', 0)
            ->assertViewHas('submittedAttempts', 0);
    }
}
