<?php

namespace Tests\Feature;

use App\Livewire\Admin\ExamManagement;
use App\Livewire\Admin\QuestionBank;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Permission;
use App\Models\Question;
use App\Models\Role;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCbtBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_stale_guru_role_and_permissions_is_denied_all_cbt_surfaces(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Boundary', 'code' => 'BOUNDARY']);
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Multi Role',
            'email' => 'admin.multi@boundary.test',
            'password' => bcrypt('password'),
        ]);
        $admin->roles()->attach([$adminRole->id, $guruRole->id, $studentRole->id]);

        // Simulate legacy/stale grants only in Eloquent's in-memory relations.
        // This deliberately performs no INSERT/UPDATE/DELETE on permissions or permission_role.
        $permissionRowsBefore = Permission::query()->count();
        $permissionPivotRowsBefore = DB::table('permission_role')->count();

        $stalePermissions = [
            'question.view', 'question.manage',
            'exam.manage', 'exam.assign', 'exam.monitor',
            'exam.close', 'exam.token', 'exam.grade', 'exam.reopen',
            'report.view',
        ];
        $stalePermissionModels = collect($stalePermissions)->map(
            fn (string $name): Permission => new Permission(['name' => $name, 'label' => $name])
        );
        $adminRole->setRelation('permissions', $stalePermissionModels);
        $guruRole->setRelation('permissions', $stalePermissionModels);
        $admin->setRelation('roles', collect([$adminRole, $guruRole, $studentRole]));

        $question = new Question(['tenant_id' => $tenant->id, 'owner_type' => 'MADRASAH']);
        $assessment = new Assessment(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);
        $exam = new Exam(['tenant_id' => $tenant->id, 'assessment_id' => '00000000-0000-4000-8000-000000000001']);
        $attempt = new ExamAttempt(['tenant_id' => $tenant->id, 'exam_id' => $exam->id]);
        $subject = new Subject(['tenant_id' => $tenant->id]);

        $gate = Gate::forUser($admin);

        $this->assertFalse($gate->allows('viewAny', Question::class));
        $this->assertFalse($gate->allows('view', $question));
        $this->assertFalse($gate->allows('create', Question::class));

        $this->assertFalse($gate->allows('viewAny', Assessment::class));
        $this->assertFalse($gate->allows('view', $assessment));
        $this->assertFalse($gate->allows('create', Assessment::class));

        $this->assertFalse($gate->allows('viewAny', Exam::class));
        $this->assertFalse($gate->allows('view', $exam));
        $this->assertFalse($gate->allows('create', Exam::class));
        $this->assertFalse($gate->allows('monitor', $exam));
        $this->assertFalse($gate->allows('manageParticipants', $exam));
        $this->assertFalse($gate->allows('generateToken', $exam));

        $this->assertFalse($gate->allows('view', $attempt));
        $this->assertFalse($gate->allows('viewScore', $attempt));
        $this->assertFalse($gate->allows('score', $attempt));
        $this->assertFalse($gate->allows('finalize', $attempt));
        $this->assertFalse($gate->allows('reopen', $attempt));

        $this->assertFalse($gate->allows('viewExamResults', $exam));
        $this->assertFalse($gate->allows('viewStudentResults', $admin));
        $this->assertFalse($gate->allows('viewSubjectResults', $subject));

        app(\App\Contexts\TenantContext::class)->setTenantId($tenant->id);
        $this->actingAs($admin);
        $this->get('/')->assertRedirect('/operator/dashboard');
        Livewire::test(QuestionBank::class)->assertForbidden();
        Livewire::test(ExamManagement::class)->assertForbidden();
        $this->get('/student/exams')->assertForbidden();

        $this->assertSame($permissionRowsBefore, Permission::query()->count());
        $this->assertSame($permissionPivotRowsBefore, DB::table('permission_role')->count());
    }

    public function test_admin_bootstrap_grants_only_administrative_and_temporary_proctor_assignment_permissions(): void
    {
        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        Role::create(['name' => 'KAMAD', 'label' => 'Kepala Madrasah']);

        app(\App\Services\RolePermissionBootstrapper::class)->bootstrap();

        $grants = $adminRole->fresh()->permissions()->pluck('name')->all();

        $this->assertEqualsCanonicalizing([
            'curriculum.view',
            'curriculum.manage',
            'academic.manage',
            'user.manage',
            'exam.proctor.assign',
        ], $grants);

        $this->assertFalse($adminRole->fresh()->permissions()->whereIn('name', [
            'question.view', 'question.manage',
            'exam.manage', 'exam.assign', 'exam.monitor',
            'exam.close', 'exam.token', 'exam.grade', 'exam.reopen',
            'report.view',
        ])->exists());

        $this->assertTrue($guruRole->fresh()->permissions()->where('name', 'exam.manage')->exists());
    }
}
