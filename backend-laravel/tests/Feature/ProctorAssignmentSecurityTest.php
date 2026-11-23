<?php
namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\ProctorAssignmentManagement;
use App\Livewire\Proktor\ProktorDashboard;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\ExamProctorAssignmentService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class ProctorAssignmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private User $proctor;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah Proktor A', 'code' => 'PROKA']);
        $context = app(TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $proctorRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $permission = Permission::create(['name' => 'exam.proctor.assign', 'label' => 'Menugaskan Proktor sementara']);
        $adminRole->permissions()->attach($permission->id);

        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin Proktor', 'email' => 'admin-proktor@example.test', 'password' => bcrypt('password')]);
        $this->admin->roles()->attach($adminRole->id);
        $this->proctor = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Proktor A', 'email' => 'proktor-a@example.test', 'password' => bcrypt('password')]);
        $this->proctor->roles()->attach($proctorRole->id);
        TeacherProfile::create(['tenant_id' => $this->tenant->id, 'user_id' => $this->proctor->id, 'full_name' => $this->proctor->name]);

        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $assessment = Assessment::create(['academic_year_id' => $year->id, 'title' => 'Asesmen Proktor', 'assessment_type' => 'SUMMATIVE', 'created_by' => $this->admin->id]);
        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id, 'assessment_id' => $assessment->id, 'title' => 'Ujian Kelas 6',
            'duration_seconds' => 3600, 'start_at' => now()->subMinutes(5), 'end_at' => now()->addHours(2),
            'status' => 'PUBLISHED', 'created_by' => $this->admin->id, 'locked_at' => now(),
        ]);

        $context->setTenantId(null);
        TenantContext::resetInstance();
    }

    private function assignThroughUi(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(ProctorAssignmentManagement::class)
            ->set('selectedExamId', $this->exam->id)
            ->set('selectedProctorId', $this->proctor->id)
            ->set('startsAt', now()->subMinute()->format('Y-m-d\\TH:i'))
            ->set('expiresAt', now()->addHour()->format('Y-m-d\\TH:i'))
            ->set('reason', 'Pengawasan ujian kelas enam')
            ->call('assign')
            ->assertHasNoErrors();
    }

    public function test_admin_cannot_create_proctor_assignment_until_central_exam_marker_is_canonical(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(ProctorAssignmentManagement::class)
            ->set('selectedExamId', $this->exam->id)
            ->set('selectedProctorId', $this->proctor->id)
            ->set('startsAt', now()->subMinute()->format('Y-m-d\\\\TH:i'))
            ->set('expiresAt', now()->addHour()->format('Y-m-d\\\\TH:i'))
            ->set('reason', 'Pengawasan ujian kelas enam')
            ->call('assign')
            ->assertForbidden();

        $this->assertDatabaseCount('exam_proctor_assignments', 0);
    }

    public function test_no_canonical_central_marker_means_no_assignment_and_no_monitoring_access(): void
    {
        $this->actingAs($this->proctor);
        $this->get("/exams/{$this->exam->id}/monitoring")->assertForbidden();

        $this->actingAs($this->admin);
        Livewire::test(ProctorAssignmentManagement::class)
            ->set('selectedExamId', $this->exam->id)
            ->set('selectedProctorId', $this->proctor->id)
            ->set('startsAt', now()->subMinute()->format('Y-m-d\\\\TH:i'))
            ->set('expiresAt', now()->addHour()->format('Y-m-d\\\\TH:i'))
            ->set('reason', 'Pengawasan ujian kelas enam')
            ->call('assign')
            ->assertForbidden();

        $this->assertDatabaseCount('exam_proctor_assignments', 0);
        $this->actingAs($this->proctor);
        $this->get("/exams/{$this->exam->id}/monitoring")->assertForbidden();
        $this->get('/proktor/dashboard')->assertNotFound();
        Livewire::test(ProktorDashboard::class)->assertForbidden();
    }

    public function test_direct_service_call_cannot_bypass_missing_central_exam_marker(): void
    {
        $this->expectException(\LogicException::class);

        app(ExamProctorAssignmentService::class)->assign(
            $this->admin,
            $this->exam,
            $this->proctor->id,
            now()->subMinute(),
            now()->addHour(),
            'Uji penolakan tanpa marker kanonis'
        );
    }

    public function test_guru_cannot_receive_overlapping_proctor_assignments_until_central_marker_exists(): void
    {
        $this->expectException(\LogicException::class);

        app(ExamProctorAssignmentService::class)->assign(
            $this->admin,
            $this->exam,
            $this->proctor->id,
            now()->subMinute(),
            now()->addHour(),
            'Uji penolakan overlap tanpa marker kanonis'
        );
    }

        public function test_revoked_assignment_removes_monitoring_access_and_is_audited(): void
    {
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $assignment = ExamProctorAssignment::query()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'proctor_user_id' => $this->proctor->id,
            'assigned_by' => $this->admin->id,
            'starts_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
            'status' => 'ACTIVE',
            'assignment_reason' => 'Legacy assignment retained for audit',
        ]);
        Livewire::test(ProctorAssignmentManagement::class)
            ->set('revokeReason', 'Penugasan selesai lebih awal')
            ->call('revoke', $assignment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('exam_proctor_assignments', ['id' => $assignment->id, 'status' => 'REVOKED', 'revoked_by' => $this->admin->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'EXAM_PROCTOR_ASSIGNMENT_REVOKE', 'entity_type' => 'ExamProctorAssignment']);
        $this->actingAs($this->proctor)->get("/exams/{$this->exam->id}/monitoring")->assertForbidden();
    }

    public function test_expired_historic_assignment_does_not_grant_monitoring_or_dashboard_access(): void
    {
        app(TenantContext::class)->setTenantId($this->tenant->id);
        ExamProctorAssignment::query()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'proctor_user_id' => $this->proctor->id,
            'assigned_by' => $this->admin->id,
            'starts_at' => now()->subHour(),
            'expires_at' => now()->subSecond(),
            'status' => 'ACTIVE',
            'assignment_reason' => 'Legacy assignment expired',
        ]);

        $this->actingAs($this->proctor)->get("/exams/{$this->exam->id}/monitoring")->assertForbidden();
        $this->get('/proktor/dashboard')->assertNotFound();
        Livewire::test(ProktorDashboard::class)->assertForbidden();
    }

    public function test_proctor_cannot_open_admin_assignment_screen_or_exam_management(): void
    {
        $this->actingAs($this->proctor);
        $this->get('/admin/proctor-assignments')->assertForbidden();
        $this->get('/admin/exams')->assertNotFound();
    }

    public function test_multi_role_guru_assignment_remains_blocked_until_central_exam_marker_exists(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);
        $this->proctor->roles()->attach($studentRole->id);

        $this->expectException(\\LogicException::class);
        app(ExamProctorAssignmentService::class)->assign(
            $this->admin,
            $this->exam,
            $this->proctor->id,
            now()->subMinute(),
            now()->addHour(),
            'Akun Guru memiliki role legacy kedua'
        );
    }

    public function test_multi_role_admin_assignment_remains_blocked_until_central_exam_marker_exists(): void
    {
        $guruRole = Role::where('name', 'GURU')->firstOrFail();
        $this->admin->roles()->attach($guruRole->id);

        $this->expectException(\\LogicException::class);
        app(ExamProctorAssignmentService::class)->assign(
            $this->admin,
            $this->exam,
            $this->proctor->id,
            now()->subMinute(),
            now()->addHour(),
            'Akun Admin memiliki role kedua'
        );
    }

    public function test_database_rejects_cross_tenant_proctor_reference(): void
    {
        $tenantB = Tenant::create(['name' => 'Madrasah Proktor B', 'code' => 'PROKB']);
        $context = app(TenantContext::class);
        $context->setTenantId($tenantB->id);
        $proctorRole = Role::where('name', 'GURU')->firstOrFail();
        $proctorB = User::create(['tenant_id' => $tenantB->id, 'name' => 'Proktor B', 'email' => 'proktor-b@example.test', 'password' => bcrypt('password')]);
        $proctorB->roles()->attach($proctorRole->id);
        TeacherProfile::create(['tenant_id' => $tenantB->id, 'user_id' => $proctorB->id, 'full_name' => $proctorB->name]);
        $context->setTenantId(null);
        TenantContext::resetInstance();

        $this->expectException(QueryException::class);
        DB::table('exam_proctor_assignments')->insert([
            'id' => (string) Str::uuid(), 'tenant_id' => $this->tenant->id, 'exam_id' => $this->exam->id,
            'proctor_user_id' => $proctorB->id, 'assigned_by' => $this->admin->id,
            'starts_at' => now()->subMinute(), 'expires_at' => now()->addHour(), 'status' => 'ACTIVE',
            'assignment_reason' => 'Cross tenant direct SQL attempt', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
