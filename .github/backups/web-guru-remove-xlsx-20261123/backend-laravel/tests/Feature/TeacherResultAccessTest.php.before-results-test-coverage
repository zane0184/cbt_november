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
use App\Models\ExamParticipant;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesTeacherExamScope;
use Tests\TestCase;

class TeacherResultAccessTest extends TestCase
{
    use RefreshDatabase;
    use CreatesTeacherExamScope;

    private Tenant $tenant;
    private User $teacher;
    private Role $guruRole;
    private Exam $exam;
    private ExamAttempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Madrasah Result Access',
            'code' => 'MRA01',
            'status' => 'ACTIVE',
        ]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $this->guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $permissions = collect(['report.view', 'exam.grade'])
            ->map(fn (string $name) => Permission::firstOrCreate(['name' => $name], ['label' => $name]));
        $this->guruRole->permissions()->sync($permissions->pluck('id')->all());

        $this->teacher = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Guru Hasil Uji',
            'email' => 'guru.hasil@mra.test',
            'password' => bcrypt('password123'),
        ]);
        $this->teacher->roles()->attach($this->guruRole->id);

        $scope = $this->createTeacherExamScope($this->tenant, $this->teacher, [], 'RESULTS');
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $student = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Siswa Hasil Uji',
            'email' => 'siswa.hasil@mra.test',
            'password' => bcrypt('password123'),
        ]);
        $student->roles()->attach($studentRole->id);
        $this->attachStudentToRombel($this->tenant, $student, $scope['rombel']);

        $assessment = Assessment::factory()->create([
            'tenant_id' => $this->tenant->id,
            'subject_id' => $scope['subject']->id,
            'academic_year_id' => $scope['academicYear']->id,
            'semester_id' => $scope['semester']->id,
            'created_by' => $this->teacher->id,
        ]);
        $this->exam = Exam::factory()->create([
            'tenant_id' => $this->tenant->id,
            'assessment_id' => $assessment->id,
            'created_by' => $this->teacher->id,
        ]);
        $participant = ExamParticipant::factory()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'user_id' => $student->id,
        ]);
        $this->attempt = ExamAttempt::factory()->create([
            'tenant_id' => $this->tenant->id,
            'exam_id' => $this->exam->id,
            'participant_id' => $participant->id,
            'status' => 'SUBMITTED',
        ]);
    }

    public function test_result_list_rechecks_revoked_guru_role_on_livewire_hydration(): void
    {
        $component = Livewire::actingAs($this->teacher)
            ->test(ResultList::class, ['exam' => $this->exam])
            ->assertOk();

        $this->teacher->roles()->detach($this->guruRole->id);

        $component->set('search', 'Siswa')->assertForbidden();
    }

    public function test_result_detail_rechecks_revoked_guru_role_on_livewire_hydration(): void
    {
        $component = Livewire::actingAs($this->teacher)
            ->test(ResultDetail::class, ['exam' => $this->exam, 'attempt' => $this->attempt])
            ->assertOk();

        $this->teacher->roles()->detach($this->guruRole->id);

        $component->set('feedbackText', 'perubahan setelah pencabutan role')->assertForbidden();
    }

    public function test_analytics_dashboard_rechecks_revoked_guru_role_on_livewire_hydration(): void
    {
        $component = Livewire::actingAs($this->teacher)
            ->test(AnalyticsDashboard::class, ['exam' => $this->exam])
            ->assertOk();

        $this->teacher->roles()->detach($this->guruRole->id);

        $component->set('summary', [])->assertForbidden();
    }

    public function test_item_analysis_rechecks_revoked_guru_role_on_livewire_hydration(): void
    {
        $component = Livewire::actingAs($this->teacher)
            ->test(ItemAnalysis::class, ['exam' => $this->exam])
            ->assertOk();

        $this->teacher->roles()->detach($this->guruRole->id);

        $component->set('items', [])->assertForbidden();
    }

    public function test_multi_role_guru_is_rejected_on_result_component_hydration(): void
    {
        $kamadRole = Role::create(['name' => 'KAMAD', 'label' => 'Kepala Madrasah']);
        $component = Livewire::actingAs($this->teacher)
            ->test(ResultList::class, ['exam' => $this->exam])
            ->assertOk();

        $this->teacher->roles()->attach($kamadRole->id);

        $component->set('search', 'Siswa')->assertForbidden();
    }

    public function test_kamad_result_access_remains_available_without_guru_role(): void
    {
        $kamadRole = Role::create(['name' => 'KAMAD', 'label' => 'Kepala Madrasah']);
        $reportPermission = Permission::firstOrCreate(['name' => 'report.view'], ['label' => 'Lihat laporan']);
        $kamadRole->permissions()->sync([$reportPermission->id]);
        $kamad = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Kamad Uji',
            'email' => 'kamad.hasil@mra.test',
            'password' => bcrypt('password123'),
        ]);
        $kamad->roles()->attach($kamadRole->id);

        Livewire::actingAs($kamad)
            ->test(ResultList::class, ['exam' => $this->exam])
            ->assertOk();
    }
}
