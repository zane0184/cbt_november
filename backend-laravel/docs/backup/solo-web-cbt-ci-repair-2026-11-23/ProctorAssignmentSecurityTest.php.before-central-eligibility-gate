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
use Illuminate\Validation\ValidationException;
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

    public function test_admin_can_assign_proctor_and_assignment_is_audited(): void
    {
        $this->assignThroughUi();
        $this->assertDatabaseHas('exam_proctor_assignments', [
            'tenant_id' => $this->tenant->id, 'exam_id' => $this->exam->id,
            'proctor_user_id' => $this->proctor->id, 'assigned_by' => $this->admin->id, 'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id, 'user_id' => $this->admin->id,
            'action' => 'EXAM_PROCTOR_ASSIGNMENT_CREATE', 'entity_type' => 'ExamProctorAssignment',
        ]);
    }

    public function test_proctor_assignment_does_not_grant_monitoring_or_permanent_portal_access(): void
    {
        $this->actingAs($this->proctor);
        $this->get("/exams/{$this->exam->id}/monitoring")->assertForbidden();

        $this->assignThroughUi();

        $this->actingAs($this->proctor);
        $this->get("/exams/{$this->exam->id}/monitoring")->assertForbidden();
        $this->get('/proktor/dashboard')->assertNotFound();
        Livewire::test(ProktorDashboard::class)->assertForbidden();
    }

    public function test_guru_cannot_receive_overlapping_proctor_assignments(): void
    {
        $this->assignThroughUi();

        $year = AcademicYear::create(['name' => '2027/2028', 'is_active' => false]);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Asesmen Kedua',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->admin->id,
        ]);
        $secondExam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Kedua',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(5),
            'end_at' => now()->addHours(2),
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
            'locked_at' => now(),
        ]);

        try {
            app(ExamProctorAssignmentService::class)->assign(
                $this->admin,
                $secondExam,
                $this->proctor->id,
                now()->subMinute(),
                now()->addHour(),
                'Benturan jadwal pengawasan kedua'
            );
            $this->fail('Overlapping Guru proctor assignment must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('selectedProctorId', $exception->errors());
        }

        $this->assertDatabaseCount('exam_proctor_assignments', 1);
    }

    public function test_revoked_assignment_removes_monitoring_access_and_is_audited(): void
    {
        $this->assignThroughUi();
        $assignment = ExamProctorAssignment::query()->firstOrFail();
        Livewire::test(ProctorAssignmentManagement::class)
            ->set('revokeReason', 'Penugasan selesai lebih awal')
            ->call('revoke', $assignment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('exam_proctor_assignments', ['id' => $assignment->id, 'status' => 'REVOKED', 'revoked_by' => $this->admin->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'EXAM_PROCTOR_ASSIGNMENT_REVOKE', 'entity_type' => 'ExamProctorAssignment']);
        $this->actingAs($this->proctor)->get("/exams/{$this->exam->id}/monitoring")->assertForbidden();
    }

    public function test_expired_assignment_still_does_not_grant_monitoring_or_dashboard_access(): void
    {
        $this->assignThroughUi();
        DB::table('exam_proctor_assignments')->where('exam_id', $this->exam->id)->update(['expires_at' => now()->subSecond()]);
        $this->actingAs($this->proctor)->get("/exams/{$this->exam->id}/monitoring")->assertForbidden();
        Livewire::test(ProktorDashboard::class)->assertForbidden();
    }

    public function test_proctor_cannot_open_admin_assignment_screen_or_exam_management(): void
    {
        $this->actingAs($this->proctor);
        $this->get('/admin/proctor-assignments')->assertForbidden();
        $this->get('/admin/exams')->assertNotFound();
    }

    public function test_multi_role_guru_cannot_be_assigned_as_temporary_proctor(): void
    {
        $legacyRole = Role::create(['name' => 'KAMAD', 'label' => 'Legacy Kamad']);
        $this->proctor->roles()->attach($legacyRole->id);

        $this->expectException(ValidationException::class);
        app(ExamProctorAssignmentService::class)->assign(
            $this->admin,
            $this->exam,
            $this->proctor->id,
            now()->subMinute(),
            now()->addHour(),
            'Akun Guru memiliki role legacy kedua'
        );
    }

    public function test_multi_role_admin_cannot_assign_temporary_proctor(): void
    {
        $guruRole = Role::where('name', 'GURU')->firstOrFail();
        $this->admin->roles()->attach($guruRole->id);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
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
