<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Curriculum;
use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\GradeLevel;
use App\Models\Phase;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Tenant;
use App\Models\User;
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
    public function operator_dashboard_access_control()\n    {\n        // Unauthenticated
        $this->get('/admin/dashboard')->assertRedirect('/login');
        
        // Admin portal roles
        $this->actingAs($this->operatorA)->get('/admin/dashboard')->assertStatus(200);
        $this->actingAs($this->adminA)->get('/admin/dashboard')->assertStatus(200);
        $this->actingAs($this->superAdmin)->get('/admin/dashboard')->assertStatus(403);
        
        // Roles outside the Admin Madrasah portal
        $this->actingAs($this->kamadA)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($this->siswaA)->get('/admin/dashboard')->assertStatus(403);
        
        // Legacy URL is only a redirect, not a separate Operator portal.
        $this->actingAs($this->adminA)->get('/operator/dashboard')->assertRedirect('/admin/dashboard');
    }

    /** @test */
    public function kamad_dashboard_access_control()\n    {\n        // Unauthenticated
        $this->get('/admin/overview')->assertRedirect('/login');
        
        // Read-only executive view inside the Admin Madrasah portal
        $this->actingAs($this->kamadA)->get('/admin/overview')->assertStatus(200);
        $this->actingAs($this->adminA)->get('/admin/overview')->assertStatus(200);
        $this->actingAs($this->superAdmin)->get('/admin/overview')->assertStatus(403);
        
        // No separate Kamad portal and no access for unrelated roles
        $this->actingAs($this->operatorA)->get('/admin/overview')->assertStatus(403);
        $this->actingAs($this->siswaA)->get('/admin/overview')->assertStatus(403);
    }

    /** @test */
    public function proktor_dashboard_access_control()\n    {\n        // Unauthenticated
        $this->get('/proktor/assignments')->assertRedirect('/login');
        
        // Temporary workbench is available only to the assigned Proktor role.
        $this->actingAs($this->proctorA)->get('/proktor/assignments')->assertStatus(200);
        $this->actingAs($this->adminA)->get('/proktor/assignments')->assertStatus(403);
        $this->actingAs($this->superAdmin)->get('/proktor/assignments')->assertStatus(403);
        
        // Unauthorized roles (GURU, OPERATOR, KAMAD, SISWA MUST be forbidden)
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
        $opResponse = $this->actingAs($this->operatorA)->get('/admin/dashboard');
        $opResponse->assertDontSee('Dashboard Proktor');
        $opResponse->assertSee('Dashboard Madrasah');

        // Sidebar rendering verification for PROCTOR (Proktor Dashboard menu MUST be present)
        $proctorResponse = $this->actingAs($this->proctorA)->get('/proktor/assignments');
        $proctorResponse->assertSee('Penugasan Operasional');
    }

    /** @test */
    public function operator_can_access_curriculum_and_exams()\n    {\n        $this->actingAs($this->operatorA);
        // OPERATOR remains a legacy identity, not a fourth portal or an exam-authoring role.
        $this->get('/admin/dashboard')->assertStatus(200);
        $this->get('/admin/curriculum')->assertStatus(403);
        $this->get('/admin/exams')->assertStatus(403);
    }

    /** @test */
    public function kamad_can_access_exams_module()\n    {\n        $this->actingAs($this->kamadA);
        // Kamad has read-only oversight and cannot enter the exam authoring module.
        $this->get('/admin/overview')->assertStatus(200);
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
        app(\App\Contexts\TenantContext::class)->setTenantId(null);

        // Tenant A authorized users can access monitoring
        $this->actingAs($this->proctorA)->get("/exams/{$exam->id}/monitoring")->assertStatus(403);
        $this->actingAs($this->kamadA)->get("/exams/{$exam->id}/monitoring")->assertStatus(200);
        $this->actingAs($this->operatorA)->get("/exams/{$exam->id}/monitoring")->assertStatus(403);
        $this->actingAs($this->adminA)->get("/exams/{$exam->id}/monitoring")->assertStatus(200);

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

        // Test Proktor workbench: only an active, bounded assignment grants exam visibility.
        app(\App\Contexts\TenantContext::class)->setTenantId($this->tenantA->id);
        ExamProctorAssignment::create([
            'exam_id' => $examA->id,
            'proctor_user_id' => $this->proctorA->id,
            'assigned_by' => $this->adminA->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
            'status' => 'ACTIVE',
        ]);
        app(\App\Contexts\TenantContext::class)->setTenantId(null);

        $this->actingAs($this->proctorA);
        Livewire::test(ProktorDashboard::class)
            ->assertViewHas('activeExams', function ($exams) use ($examA) {
                return $exams->count() === 1 && $exams->first()->id === $examA->id;
            });
    }
}
