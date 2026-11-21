<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\ProctorAssignmentManagement;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
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
    private User $examAuthor;
    private User $proctorGuru;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::create(['name' => 'Madrasah Penugasan A', 'code' => 'PROKA']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $assignPermission = Permission::create(['name' => 'exam.proctor.assign', 'label' => 'Menugaskan Proktor sementara']);
        $monitorPermission = Permission::create(['name' => 'exam.monitor', 'label' => 'Monitoring ujian']);
        $adminRole->permissions()->attach($assignPermission->id);
        $guruRole->permissions()->attach($monitorPermission->id);

        $this->admin = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Admin Madrasah', 'email' => 'admin-proctor@example.test', 'password' => bcrypt('password')]);
        $this->admin->roles()->attach($adminRole->id);

        $this->examAuthor = $this->createGuru($guruRole, 'author');
        $this->proctorGuru = $this->createGuru($guruRole, 'proctor');

        $year = AcademicYear::create(['tenant_id' => $this->tenant->id, 'name' => '2026/2027', 'is_active' => true]);
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $year->id,
            'title' => 'Asesmen Penugasan Proktor',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->examAuthor->id,
        ]);
        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Kelas 6',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(5),
            'end_at' => now()->addHours(2),
            'status' => 'PUBLISHED',
            'created_by' => $this->examAuthor->id,
            'locked_at' => now(),
        ]);

        app(TenantContext::class)->setTenantId(null);
        TenantContext::resetInstance();
    }

    private function createGuru(Role $guruRole, string $suffix): User
    {
        $guru = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru '.$suffix,
            'email' => 'guru-'.$suffix.'-proctor@example.test',
            'password' => bcrypt('password'),
        ]);
        $guru->roles()->attach($guruRole->id);
        TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $guru->id,
            'full_name' => 'Guru '.$suffix,
        ]);

        return $guru->fresh();
    }

    private function assignThroughUi(): void
    {
        $this->actingAs($this->admin);
        Livewire::test(ProctorAssignmentManagement::class)
            ->set('selectedExamId', $this->exam->id)
            ->set('selectedProctorId', $this->proctorGuru->id)
            ->set('startsAt', now()->subMinute()->format('Y-m-d\\TH:i'))
            ->set('expiresAt', now()->addHour()->format('Y-m-d\\TH:i'))
            ->set('reason', 'Pengawasan ujian kelas enam')
            ->call('assign')
            ->assertHasNoErrors();
    }

    public function test_admin_can_assign_eligible_guru_and_assignment_is_audited(): void
    {
        $this->assignThroughUi();

        $this->assertDatabaseHas('exam_proctor_assignments', [
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'proctor_user_id' => $this->proctorGuru->id,
            'assigned_by' => $this->admin->id,
            'status' => 'ACTIVE',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'action' => 'EXAM_PROCTOR_ASSIGNMENT_CREATE',
            'entity_type' => 'ExamProctorAssignment',
        ]);
    }

    public function test_assigned_guru_can_monitor_only_while_assignment_is_active_and_for_that_exam(): void
    {
        $this->actingAs($this->proctorGuru)->get("/exams/{$this->exam->id}/monitoring")->assertForbidden();

        $this->assignThroughUi();

        $this->actingAs($this->proctorGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertOk();
        $this->actingAs($this->proctorGuru)
            ->get("/exams/{$this->exam->id}/monitoring")
            ->assertOk();

        $otherExam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $this->exam->assessment_id,
            'title' => 'Ujian Lain',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(5),
            'end_at' => now()->addHours(2),
            'status' => 'PUBLISHED',
            'created_by' => $this->examAuthor->id,
            'locked_at' => now(),
        ]);
        $this->getJson("/api/v1/exams/{$otherExam->id}/monitoring")->assertForbidden();
    }

    public function test_admin_does_not_gain_monitoring_access_by_assigning_a_guru(): void
    {
        $this->assignThroughUi();

        $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
        $this->actingAs($this->admin)
            ->get("/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
    }

    public function test_revoked_assignment_removes_monitoring_access_and_is_audited(): void
    {
        $this->assignThroughUi();
        $assignment = ExamProctorAssignment::query()->firstOrFail();

        Livewire::test(ProctorAssignmentManagement::class)
            ->set('revokeReason', 'Penugasan selesai lebih awal')
            ->call('revoke', $assignment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('exam_proctor_assignments', [
            'id' => $assignment->id,
            'status' => 'REVOKED',
            'revoked_by' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'EXAM_PROCTOR_ASSIGNMENT_REVOKE',
            'entity_type' => 'ExamProctorAssignment',
        ]);
        $this->actingAs($this->proctorGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
    }

    public function test_expired_assignment_does_not_authorize_monitoring(): void
    {
        $this->assignThroughUi();
        ExamProctorAssignment::query()->where('exam_id', $this->exam->id)
            ->update(['expires_at' => now()->subSecond()]);

        $this->actingAs($this->proctorGuru, 'sanctum')
            ->getJson("/api/v1/exams/{$this->exam->id}/monitoring")
            ->assertForbidden();
    }

    public function test_database_rejects_cross_tenant_guru_assignment_reference(): void
    {
        $tenantB = Tenant::create(['name' => 'Madrasah Penugasan B', 'code' => 'PROKB']);
        app(TenantContext::class)->setTenantId($tenantB->id);
        $guruRole = Role::where('name', 'GURU')->firstOrFail();
        $guruB = User::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Guru Tenant B',
            'email' => 'guru-tenant-b@example.test',
            'password' => bcrypt('password'),
        ]);
        $guruB->roles()->attach($guruRole->id);
        TeacherProfile::create(['tenant_id' => $tenantB->id, 'user_id' => $guruB->id, 'full_name' => 'Guru Tenant B']);
        app(TenantContext::class)->setTenantId(null);
        TenantContext::resetInstance();

        $this->expectException(QueryException::class);
        DB::table('exam_proctor_assignments')->insert([
            'id' => (string) Str::uuid(),
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'proctor_user_id' => $guruB->id,
            'assigned_by' => $this->admin->id,
            'starts_at' => now()->subMinute(),
            'expires_at' => now()->addHour(),
            'status' => 'ACTIVE',
            'assignment_reason' => 'Percobaan assignment lintas tenant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_user_without_active_teacher_profile_cannot_be_assigned(): void
    {
        $this->proctorGuru->teacherProfile()->delete();

        $this->actingAs($this->admin);
        Livewire::test(ProctorAssignmentManagement::class)
            ->set('selectedExamId', $this->exam->id)
            ->set('selectedProctorId', $this->proctorGuru->id)
            ->set('startsAt', now()->subMinute()->format('Y-m-d\\TH:i'))
            ->set('expiresAt', now()->addHour()->format('Y-m-d\\TH:i'))
            ->set('reason', 'Pengawasan ujian kelas enam')
            ->call('assign')
            ->assertHasErrors(['selectedProctorId']);
    }
}
