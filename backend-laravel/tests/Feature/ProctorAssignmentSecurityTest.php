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
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
    private User $guru;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Proktor A', 'code' => 'PROKA']);
        $context = app(TenantContext::class);
        $context->setTenantId($this->tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $permission = Permission::create(['name' => 'exam.proctor.assign', 'label' => 'Menugaskan Proktor sementara']);
        $adminRole->permissions()->attach($permission->id);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Admin Proktor',
            'email' => 'admin-proktor@example.test', 'password' => bcrypt('password'),
        ]);
        $this->admin->roles()->attach($adminRole->id);

        $this->guru = User::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Guru A',
            'email' => 'guru-a@example.test', 'password' => bcrypt('password'),
        ]);
        $this->guru->roles()->attach($guruRole->id);
        TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->guru->id,
            'full_name' => 'Guru A',
        ]);

        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $assessment = Assessment::create([
            'academic_year_id' => $year->id, 'title' => 'Asesmen Proktor',
            'assessment_type' => 'SUMMATIVE', 'created_by' => $this->admin->id,
        ]);
        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id, 'assessment_id' => $assessment->id,
            'title' => 'Ujian Kelas 6', 'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(5), 'end_at' => now()->addHours(2),
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
            ->set('selectedProctorId', $this->guru->id)
            ->set('startsAt', now()->subMinute()->format('Y-m-d\\TH:i'))
            ->set('expiresAt', now()->addHour()->format('Y-m-d\\TH:i'))
            ->set('reason', 'Pengawasan ujian kelas enam')
            ->call('assign')
            ->assertHasNoErrors();
    }

    private function assertCandidateRejectedThroughUi(User $candidate): void
    {
        $this->actingAs($this->admin);

        try {
            Livewire::test(ProctorAssignmentManagement::class)
                ->set('selectedExamId', $this->exam->id)
                ->set('selectedProctorId', $candidate->id)
                ->set('startsAt', now()->subMinute()->format('Y-m-d\\TH:i'))
                ->set('expiresAt', now()->addHour()->format('Y-m-d\\TH:i'))
                ->set('reason', 'Pengawasan ujian kelas enam')
                ->call('assign');

            $this->fail('Kandidat yang tidak memenuhi syarat tidak boleh ditugaskan.');
        } catch (ModelNotFoundException) {
            $this->assertDatabaseCount('exam_proctor_assignments', 0);
        }
    }

    public function test_admin_can_assign_active_guru_as_temporary_proctor_and_assignment_is_audited(): void
    {
        $this->assignThroughUi();

        $this->assertDatabaseHas('exam_proctor_assignments', [
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'proctor_user_id' => $this->guru->id,
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

    public function test_user_without_guru_role_cannot_be_selected_as_temporary_proctor(): void
    {
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $student = User::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Siswa A',
            'email' => 'siswa-a@example.test', 'password' => bcrypt('password'),
        ]);
        $student->roles()->attach($studentRole->id);
        TeacherProfile::create([
            'tenant_id' => $this->tenant->id, 'user_id' => $student->id, 'full_name' => 'Siswa A',
        ]);

        $this->assertCandidateRejectedThroughUi($student);
    }

    public function test_multi_role_user_cannot_be_selected_as_temporary_proctor(): void
    {
        $guruRole = Role::where('name', 'GURU')->firstOrFail();
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $multiRoleUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Multi Role',
            'email' => 'guru-multi@example.test',
            'password' => bcrypt('password'),
        ]);
        $multiRoleUser->roles()->attach([$guruRole->id, $studentRole->id]);
        TeacherProfile::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $multiRoleUser->id,
            'full_name' => 'Guru Multi Role',
        ]);

        $this->assertCandidateRejectedThroughUi($multiRoleUser);
    }

    public function test_guru_without_active_teacher_profile_cannot_be_selected(): void
    {
        $teacherRole = Role::where('name', 'GURU')->firstOrFail();
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $inactiveGuru = User::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Guru Nonaktif',
            'email' => 'guru-inactive@example.test', 'password' => bcrypt('password'),
        ]);
        $inactiveGuru->roles()->attach($teacherRole->id);
        TeacherProfile::create([
            'tenant_id' => $this->tenant->id, 'user_id' => $inactiveGuru->id, 'full_name' => 'Guru Nonaktif',
        ])->delete();

        $this->assertCandidateRejectedThroughUi($inactiveGuru);
    }

    public function test_revoked_assignment_is_audited_and_remains_in_history(): void
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
    }

    public function test_guru_cannot_open_admin_temporary_assignment_screen(): void
    {
        $this->actingAs($this->guru);
        $this->get('/admin/proctor-assignments')->assertForbidden();
    }

    public function test_database_rejects_cross_tenant_guru_reference(): void
    {
        $tenantB = Tenant::create(['name' => 'Madrasah Proktor B', 'code' => 'PROKB']);
        $context = app(TenantContext::class);
        $context->setTenantId($tenantB->id);
        $guruRole = Role::where('name', 'GURU')->firstOrFail();
        $guruB = User::create([
            'tenant_id' => $tenantB->id, 'name' => 'Guru B',
            'email' => 'guru-b@example.test', 'password' => bcrypt('password'),
        ]);
        $guruB->roles()->attach($guruRole->id);
        TeacherProfile::create([
            'tenant_id' => $tenantB->id, 'user_id' => $guruB->id, 'full_name' => 'Guru B',
        ]);
        $context->setTenantId(null);
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
            'assignment_reason' => 'Cross tenant direct SQL attempt',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
