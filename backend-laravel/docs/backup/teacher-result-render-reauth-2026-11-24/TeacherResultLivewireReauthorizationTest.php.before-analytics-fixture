<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Teacher\AnalyticsDashboard;
use App\Livewire\Teacher\ItemAnalysis;
use App\Livewire\Teacher\ResultDetail;
use App\Livewire\Teacher\ResultList;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesCanonicalTeacherAssignment;
use Tests\Support\CreatesTeacherExamScope;
use Tests\TestCase;

class TeacherResultLivewireReauthorizationTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTeacherExamScope;
    use CreatesCanonicalTeacherAssignment;

    private Tenant $tenant;
    private User $teacher;
    private Role $adminRole;
    private Exam $exam;
    private ExamAttempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Result Auth Madrasah',
            'code' => 'RESA',
        ]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $guruRole = Role::firstOrCreate(['name' => 'GURU'], ['label' => 'Guru']);
        $this->adminRole = Role::firstOrCreate(['name' => 'ADMIN_MADRASAH'], ['label' => 'Admin Madrasah']);
        $siswaRole = Role::firstOrCreate(['name' => 'SISWA'], ['label' => 'Siswa']);

        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Result Auth',
            'email' => 'guru.result.auth@example.test',
            'password' => bcrypt('SandiUji#2026'),
        ]);
        $this->teacher->roles()->sync([$guruRole->id]);

        $student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Result Auth',
            'email' => 'siswa.result.auth@example.test',
            'password' => bcrypt('SandiUji#2026'),
        ]);
        $student->roles()->sync([$siswaRole->id]);

        $scope = $this->createTeacherExamScope($this->tenant, $this->teacher, [$student], 'RESULTAUTH');
        $assessment = Assessment::create([
            'tenant_id' => $this->tenant->id,
            'academic_year_id' => $scope['academicYear']->id,
            'subject_id' => $scope['subject']->id,
            'semester_id' => $scope['semester']->id,
            'title' => 'Asesmen Reauthorization',
            'assessment_type' => 'DAILY',
            'created_by' => $this->teacher->id,
        ]);
        $this->bindCanonicalTeacherAssignment($scope['assignment'], $assessment);

        $this->exam = Exam::create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'title' => 'Ujian Reauthorization',
            'duration_seconds' => 3600,
            'created_by' => $this->teacher->id,
            'status' => 'SCHEDULED',
            'start_at' => now()->subHour(),
            'end_at' => now()->addHour(),
            'locked_at' => now(),
            'show_result' => true,
        ]);

        $participant = $this->exam->participants()->create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $student->id,
        ]);
        $this->attempt = ExamAttempt::create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'attempt_number' => 1,
            'status' => 'SUBMITTED',
            'started_at' => now()->subMinutes(30),
            'expires_at' => now()->addMinutes(30),
            'submitted_at' => now(),
        ]);

        $this->bootstrapCbtRolePermissions();
    }

    private function revokeGuruRole(): void
    {
        $this->teacher->roles()->sync([$this->adminRole->id]);
    }

    public function test_result_list_rechecks_policy_after_livewire_mount(): void
    {
        $this->actingAs($this->teacher);
        $component = Livewire::test(ResultList::class, ['exam' => $this->exam]);

        $this->revokeGuruRole();

        $component->set('search', 'Siswa')->assertForbidden();
    }

    public function test_result_detail_rechecks_policy_after_livewire_mount(): void
    {
        $this->actingAs($this->teacher);
        $component = Livewire::test(ResultDetail::class, [
            'exam' => $this->exam,
            'attempt' => $this->attempt,
        ]);

        $component->assertSuccessful();

        $this->revokeGuruRole();

        // A normal property update forces the next Livewire request to re-run render().
        $component->set('showReopenModal', true)->assertForbidden();
    }

    public function test_analytics_dashboard_rechecks_policy_after_livewire_mount(): void
    {
        $this->actingAs($this->teacher);
        $component = Livewire::test(AnalyticsDashboard::class, ['exam' => $this->exam]);

        $this->revokeGuruRole();

        $component->set('summary', ['tampered' => true])->assertForbidden();
    }

    public function test_item_analysis_rechecks_policy_after_livewire_mount(): void
    {
        $this->actingAs($this->teacher);
        $component = Livewire::test(ItemAnalysis::class, ['exam' => $this->exam]);

        $this->revokeGuruRole();

        $component->set('items', [['tampered' => true]])->assertForbidden();
    }
}
