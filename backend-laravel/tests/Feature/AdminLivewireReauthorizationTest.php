<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Livewire\Admin\AcademicManagement;
use App\Livewire\Admin\CurriculumManagement;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ProctorAssignmentManagement;
use App\Livewire\Admin\UserManagement;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AdminLivewireReauthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $admin;
    private Role $adminRole;
    private Role $guruRole;
    private array $permissionIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Madrasah Reauthorization',
            'code' => 'MADUREAUTH',
        ]);
        app(TenantContext::class)->setTenantId($this->tenant->id);

        $this->adminRole = Role::firstOrCreate([
            'name' => 'ADMIN_MADRASAH',
            'label' => 'Admin Madrasah',
        ]);
        $this->guruRole = Role::firstOrCreate([
            'name' => 'GURU',
            'label' => 'Guru',
        ]);

        foreach (['user.manage', 'academic.manage', 'curriculum.view', 'exam.proctor.assign'] as $name) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                ['label' => $name]
            );
            $this->permissionIds[$name] = $permission->id;
        }

        $this->adminRole->permissions()->sync(array_values($this->permissionIds));

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Reauthorization',
            'email' => 'admin.reauth@example.test',
            'password' => bcrypt('SandiUji#2026'),
        ]);
        $this->admin->roles()->attach($this->adminRole);
    }

    public function test_dashboard_rechecks_exact_admin_role_on_render(): void
    {
        $this->actingAs($this->admin);
        $dashboard = new Dashboard();

        $dashboard->mount();
        $this->admin->roles()->sync([$this->guruRole->id]);

        try {
            $dashboard->render();
            $this->fail('Dashboard render harus menolak role yang sudah dicabut.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_user_management_rechecks_role_after_livewire_mount(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(UserManagement::class);

        $this->admin->roles()->sync([$this->guruRole->id]);

        $component->set('search', 'uji')->assertForbidden();
    }

    public function test_academic_management_rechecks_permission_after_livewire_mount(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(AcademicManagement::class);

        $this->adminRole->permissions()->detach($this->permissionIds['academic.manage']);

        $component->set('search', 'matematika')->assertForbidden();
    }

    public function test_curriculum_management_rechecks_permission_after_livewire_mount(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(CurriculumManagement::class);

        $this->adminRole->permissions()->detach($this->permissionIds['curriculum.view']);

        $component->set('search', 'capaian')->assertForbidden();
    }

    public function test_proctor_exam_selector_rechecks_admin_role_after_livewire_mount(): void
    {
        $this->actingAs($this->admin);
        $component = Livewire::test(ProctorAssignmentManagement::class);

        $this->admin->roles()->sync([$this->guruRole->id]);

        $component->set('selectedExamId', (string) Str::uuid())->assertForbidden();
    }
}
