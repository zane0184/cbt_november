<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureLicenseActive;
use App\Contexts\TenantContext;
use App\Livewire\Admin\ExamManagement;
use App\Livewire\Admin\QuestionBank;
use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamAttempt;
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

/**
 * Test double representing a legacy account whose stale permission check would
 * return true for every CBT capability, without persisting permission fixtures.
 */
class StalePermissionSimulationUser extends User
{
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, [
            'question.view', 'question.manage',
            'exam.manage', 'exam.assign', 'exam.monitor',
            'exam.close', 'exam.token', 'exam.grade', 'exam.reopen',
            'report.view',
        ], true);
    }
}

/**
 * WA-01 safe boundary coverage.
 *
 * Deliberately does not create Permission rows or permission_role pivots.
 * It verifies that ADMIN_MADRASAH remains denied even when the account also
 * carries legacy GURU/SISWA role memberships.
 */
class AdminCbtBoundaryWithoutPermissionFixturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_with_legacy_guru_and_student_roles_is_denied_without_permission_fixtures(): void
    {
        $tenant = Tenant::create(['name' => 'Madrasah Safe Boundary', 'code' => 'SAFEBOUNDARY']);
        app(TenantContext::class)->setTenantId($tenant->id);

        $adminRole = Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        $guruRole = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $studentRole = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        $persistedAdmin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Multi Role',
            'email' => 'admin.safe-boundary@example.test',
            'password' => bcrypt('password'),
        ]);
        $persistedAdmin->roles()->attach([$adminRole->id, $guruRole->id, $studentRole->id]);

        // Simulate all stale CBT grants in memory; do not create permission
        // records or write permission_role pivots to the disposable database.
        $admin = new StalePermissionSimulationUser();
        $admin->setRawAttributes($persistedAdmin->getAttributes(), true);
        $admin->setRelation('roles', $persistedAdmin->roles()->get());

        foreach ([
            'question.view', 'question.manage',
            'exam.manage', 'exam.assign', 'exam.monitor',
            'exam.close', 'exam.token', 'exam.grade', 'exam.reopen',
            'report.view',
        ] as $stalePermission) {
            $this->assertTrue($admin->hasPermission($stalePermission));
        }

        // Prove this test has not seeded or mutated permission data.
        $this->assertSame(0, DB::table('permissions')->count());
        $this->assertSame(0, DB::table('permission_role')->count());

        $question = new Question(['tenant_id' => $tenant->id, 'owner_type' => 'MADRASAH']);
        $assessment = new Assessment(['tenant_id' => $tenant->id, 'created_by' => $admin->id]);
        $exam = new Exam([
            'tenant_id' => $tenant->id,
            'assessment_id' => '00000000-0000-4000-8000-000000000001',
        ]);
        $attempt = new ExamAttempt([
            'tenant_id' => $tenant->id,
            'exam_id' => $exam->id,
        ]);
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
        $this->assertFalse($gate->allows('close', $exam));

        $this->assertFalse($gate->allows('view', $attempt));
        $this->assertFalse($gate->allows('viewScore', $attempt));
        $this->assertFalse($gate->allows('score', $attempt));
        $this->assertFalse($gate->allows('finalize', $attempt));
        $this->assertFalse($gate->allows('reopen', $attempt));

        $this->assertFalse($gate->allows('viewExamResults', $exam));
        $this->assertFalse($gate->allows('viewStudentResults', $admin));
        $this->assertFalse($gate->allows('viewSubjectResults', $subject));

        $this->actingAs($admin);
        $this->withoutMiddleware(EnsureLicenseActive::class);
        $this->get('/')->assertRedirect('/operator/dashboard');
        Livewire::test(QuestionBank::class)->assertForbidden();
        Livewire::test(ExamManagement::class)->assertForbidden();
        $this->get('/student/exams')->assertForbidden();

        // Keep the no-permission-mutation invariant explicit through the end.
        $this->assertSame(0, DB::table('permissions')->count());
        $this->assertSame(0, DB::table('permission_role')->count());
    }
}
