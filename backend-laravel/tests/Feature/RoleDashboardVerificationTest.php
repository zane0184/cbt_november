<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Exam;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\ExamPolicy;
use App\Services\RolePermissionBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardVerificationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantA;
    private Tenant $tenantB;
    private User $adminA;
    private User $guruA;
    private User $siswaA;
    private User $adminB;
    private User $legacyOperator;
    private User $legacyKamad;
    private User $legacyProktor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::create(['name' => 'Madrasah Demo A', 'code' => 'DEMOA']);
        $this->tenantB = Tenant::create(['name' => 'Madrasah Demo B', 'code' => 'DEMOB']);

        $context = app(TenantContext::class);
        $context->setTenantId($this->tenantA->id);

        $roles = [];
        foreach ([
            'ADMIN_MADRASAH' => 'Admin Madrasah',
            'GURU' => 'Guru',
            'SISWA' => 'Siswa',
            'OPERATOR' => 'Legacy Operator',
            'KAMAD' => 'Legacy Kamad',
            'PROKTOR' => 'Legacy Proktor',
        ] as $name => $label) {
            $roles[$name] = Role::create(['name' => $name, 'label' => $label]);
        }

        app(RolePermissionBootstrapper::class)->bootstrap();

        $this->adminA = $this->makeUser('admin-a', $roles['ADMIN_MADRASAH']);
        $this->guruA = $this->makeUser('guru-a', $roles['GURU']);
        $this->siswaA = $this->makeUser('siswa-a', $roles['SISWA']);
        $this->legacyOperator = $this->makeUser('operator-a', $roles['OPERATOR']);
        $this->legacyKamad = $this->makeUser('kamad-a', $roles['KAMAD']);
        $this->legacyProktor = $this->makeUser('proktor-a', $roles['PROKTOR']);

        TeacherProfile::create([
            'user_id' => $this->guruA->id,
            'full_name' => 'Guru A',
        ]);

        $context->setTenantId($this->tenantB->id);
        $adminRole = Role::where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $this->adminB = $this->makeUser('admin-b', $adminRole);

        $context->setTenantId(null);
        $this->withoutMiddleware(\App\Http\Middleware\EnsureLicenseActive::class);
    }

    private function makeUser(string $username, Role $role): User
    {
        $user = User::create([
            'tenant_id' => app(TenantContext::class)->getTenantId(),
            'name' => $username,
            'username' => $username,
            'password' => bcrypt('SandiUji#2026'),
        ]);
        $user->roles()->attach($role);

        return $user;
    }

    public function test_only_three_local_portals_are_routed_and_legacy_portals_are_absent(): void
    {
        $this->get('/operator/dashboard')->assertNotFound();
        $this->get('/kamad/dashboard')->assertNotFound();
        $this->get('/proktor/dashboard')->assertNotFound();
        $this->get('/proktor/assignments')->assertNotFound();

        $this->actingAs($this->adminA)->get('/admin/dashboard')->assertOk();
        $this->actingAs($this->guruA)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($this->siswaA)->get('/admin/dashboard')->assertForbidden();

        $this->actingAs($this->legacyOperator)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($this->legacyKamad)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($this->legacyProktor)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_guru_portal_requires_exact_guru_role_and_profile(): void
    {
        $this->actingAs($this->guruA)->get('/guru/dashboard')->assertOk();
        $this->actingAs($this->adminA)->get('/guru/dashboard')->assertForbidden();
        $this->actingAs($this->siswaA)->get('/guru/dashboard')->assertForbidden();
        $this->actingAs($this->legacyProktor)->get('/guru/dashboard')->assertForbidden();
    }

    public function test_student_portal_is_limited_to_student_role(): void
    {
        $this->actingAs($this->siswaA)->get('/student/exams')->assertOk();
        $this->actingAs($this->adminA)->get('/student/exams')->assertForbidden();
        $this->actingAs($this->guruA)->get('/student/exams')->assertForbidden();
        $this->actingAs($this->legacyOperator)->get('/student/exams')->assertForbidden();
    }

    public function test_admin_has_read_only_exam_register_access_and_no_teacher_cbt_operations(): void
    {
        $exam = new Exam([
            'status' => 'PUBLISHED',
            'locked_at' => now(),
            'end_at' => now()->addHour(),
        ]);
        $exam->tenant_id = $this->tenantA->id;

        $policy = app(ExamPolicy::class);

        $this->assertTrue($policy->view($this->adminA, $exam), 'Admin may view the local exam register.');
        $this->assertFalse($policy->update($this->adminA, $exam));
        $this->assertFalse($policy->delete($this->adminA, $exam));
        $this->assertFalse($policy->manageQuestions($this->adminA, $exam));
        $this->assertFalse($policy->manageParticipants($this->adminA, $exam));
        $this->assertFalse($policy->generateToken($this->adminA, $exam));
        $this->assertFalse($policy->monitor($this->adminA, $exam));
        $this->assertFalse($policy->close($this->adminA, $exam));
        $this->assertFalse($policy->viewExamResults($this->adminA, $exam));
        $this->assertTrue($policy->assignProctor($this->adminA, $exam));
        $this->assertFalse($policy->view($this->adminB, $exam), 'Cross-installation exam access must be denied.');
    }
}
