<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\ProctorAssignmentManagement;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\RolePermissionBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class ExamProctorAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private User $proctor;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Madrasah Proktor', 'code' => 'PROKTOR-A']);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $proctorRole = Role::firstOrCreate(['name' => 'PROKTOR'], ['label' => 'Proktor']);
        Role::firstOrCreate(['name' => 'KAMAD'], ['label' => 'Kepala Madrasah']);
        Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);

        $this->admin = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->admin->roles()->attach($adminRole);
        $this->proctor = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->proctor->roles()->attach($proctorRole);

        app(RolePermissionBootstrapper::class)->bootstrap();

        $year = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $assessment = Assessment::create([
            'academic_year_id' => $year->id,
            'title' => 'Asesmen Proktor',
            'assessment_type' => 'SUMMATIVE',
            'created_by' => $this->admin->id,
        ]);

        $this->exam = Exam::create([
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Proktor',
            'duration_seconds' => 3600,
            'start_at' => now()->subMinutes(5),
            'end_at' => now()->addHour(),
            'status' => 'PUBLISHED',
            'created_by' => $this->admin->id,
            'locked_at' => now(),
            'published_at' => now(),
        ]);
    }

    public function test_admin_can_assign_same_tenant_proctor_and_assignment_grants_only_exam_monitoring(): void
    {
        $this->assertFalse(app(\App\Policies\ExamPolicy::class)->monitor($this->proctor, $this->exam));

        $this->actingAs($this->admin)
            ->postJson("/api/v1/exams/{$this->exam->id}/proctor-assignments", [
                'proctor_user_id' => $this->proctor->id,
                'starts_at' => now()->subMinute()->toISOString(),
                'notes' => 'Penugasan tanpa batas waktu harus ditolak',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ends_at']);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/exams/{$this->exam->id}/proctor-assignments", [
                'proctor_user_id' => $this->proctor->id,
                'starts_at' => now()->subMinute()->toISOString(),
                'ends_at' => now()->addHour()->toISOString(),
                'notes' => 'Penugasan ruang ujian 1',
            ])
            ->assertCreated();

        $assignment = ExamProctorAssignment::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('exam_id', $this->exam->id)
            ->where('proctor_user_id', $this->proctor->id)
            ->firstOrFail();

        $this->assertSame('ACTIVE', $assignment->status);
        $this->assertTrue(app(\App\Policies\ExamPolicy::class)->monitor($this->proctor, $this->exam));

        $this->actingAs($this->proctor)
            ->get("/exams/{$this->exam->id}/monitoring")
            ->assertOk();
    }

    public function test_unassigned_expired_and_revoked_proctors_cannot_monitor_exam(): void
    {
        $policy = app(\App\Policies\ExamPolicy::class);
        $this->assertFalse($policy->monitor($this->proctor, $this->exam));

        $assignment = ExamProctorAssignment::create([
            'exam_id' => $this->exam->id,
            'proctor_user_id' => $this->proctor->id,
            'assigned_by' => $this->admin->id,
            'starts_at' => now()->subHours(2),
            'ends_at' => now()->subHour(),
            'status' => 'ACTIVE',
        ]);
        $this->assertFalse($policy->monitor($this->proctor, $this->exam));

        $assignment->update(['ends_at' => now()->addHour()]);
        $this->assertTrue($policy->monitor($this->proctor, $this->exam));

        $this->actingAs($this->admin)
            ->deleteJson("/api/v1/exams/{$this->exam->id}/proctor-assignments/{$assignment->id}")
            ->assertOk();

        $this->assertFalse($policy->monitor($this->proctor, $this->exam));
        $this->assertSame('REVOKED', $assignment->fresh()->status);
    }

    public function test_admin_cannot_assign_cross_tenant_or_non_proctor_user(): void
    {
        $otherTenant = Tenant::create(['name' => 'Tenant B', 'code' => 'PROKTOR-B']);
        app(TenantContext::class)->setTenantId($otherTenant->id);
        $foreignProctorRole = Role::firstOrCreate(['name' => 'PROKTOR'], ['label' => 'Proktor']);
        $foreignUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        $foreignUser->roles()->attach($foreignProctorRole);

        // A same-tenant account without the PROKTOR role must also be rejected.
        app(TenantContext::class)->setTenantId($this->tenant->id);
        $nonProctor = User::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->admin)
            ->postJson("/api/v1/exams/{$this->exam->id}/proctor-assignments", [
                'proctor_user_id' => $foreignUser->id,
                'starts_at' => now()->toISOString(),
                'ends_at' => now()->addHour()->toISOString(),
            ])
            ->assertUnprocessable();

        $this->actingAs($this->admin)
            ->postJson("/api/v1/exams/{$this->exam->id}/proctor-assignments", [
                'proctor_user_id' => $nonProctor->id,
                'starts_at' => now()->toISOString(),
                'ends_at' => now()->addHour()->toISOString(),
            ])
            ->assertUnprocessable();
    }


    public function test_admin_can_manage_proctor_assignments_from_admin_ui_and_revoke_without_deleting_history(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/proctor-assignments')
            ->assertOk();

        Livewire::actingAs($this->admin)
            ->test(ProctorAssignmentManagement::class)
            ->set('examId', $this->exam->id)
            ->set('proctorUserId', $this->proctor->id)
            ->set('startsAt', now()->subMinute()->format('Y-m-d\\TH:i'))
            ->set('endsAt', now()->addHour()->format('Y-m-d\\TH:i'))
            ->set('notes', 'Proktor ruang ujian 1')
            ->call('saveAssignment')
            ->assertHasNoErrors()
            ->assertSee('Penugasan Proktor berhasil dibuat atau diperbarui.');

        $assignment = ExamProctorAssignment::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('exam_id', $this->exam->id)
            ->where('proctor_user_id', $this->proctor->id)
            ->firstOrFail();

        $this->assertSame('ACTIVE', $assignment->status);

        Livewire::actingAs($this->admin)
            ->test(ProctorAssignmentManagement::class)
            ->call('revokeAssignment', $assignment->id)
            ->assertHasNoErrors()
            ->assertSee('Penugasan Proktor berhasil dicabut dan dicatat di audit log.');

        $this->assertSame('REVOKED', $assignment->fresh()->status);
        $this->assertDatabaseHas('exam_proctor_assignments', [
            'id' => $assignment->id,
            'status' => 'REVOKED',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_id' => $assignment->id,
            'action' => 'ASSIGN_PROCTOR',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'entity_id' => $assignment->id,
            'action' => 'REVOKE_PROCTOR_ASSIGNMENT',
        ]);
    }

    public function test_non_admin_cannot_open_proctor_assignment_management_ui(): void
    {
        $this->actingAs($this->proctor)
            ->get('/admin/proctor-assignments')
            ->assertForbidden();
    }

    public function test_only_admin_madrasah_can_assign_proctors(): void
    {
        $this->actingAs($this->proctor)
            ->postJson("/api/v1/exams/{$this->exam->id}/proctor-assignments", [
                'proctor_user_id' => $this->proctor->id,
                'starts_at' => now()->toISOString(),
            ])
            ->assertForbidden();
    }
}
