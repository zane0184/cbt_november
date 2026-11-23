<?php

namespace Tests\Feature;

use App\Contexts\TenantContext;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\AssessmentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentPolicyTeacherCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_role_can_reach_assignment_validation_when_creating_assessment(): void
    {
        $tenant = Tenant::create(['name' => 'Assessment Policy Test', 'code' => 'APCT']);
        app(TenantContext::class)->setTenantId($tenant->id);

        $role = Role::create(['name' => 'GURU', 'label' => 'Guru']);
        $permission = Permission::create(['name' => 'exam.manage', 'label' => 'Manage assessment']);
        $role->permissions()->attach($permission->id);

        $teacher = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Guru Uji',
            'username' => 'guru.assessment.policy',
            'password' => bcrypt('password'),
        ]);
        $teacher->roles()->attach($role);

        $this->assertTrue(app(AssessmentPolicy::class)->create($teacher));
    }

    public function test_student_role_cannot_create_assessment(): void
    {
        $tenant = Tenant::create(['name' => 'Assessment Student Policy Test', 'code' => 'APST']);
        app(TenantContext::class)->setTenantId($tenant->id);

        $role = Role::create(['name' => 'SISWA', 'label' => 'Siswa']);
        $student = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Siswa Uji',
            'username' => 'siswa.assessment.policy',
            'password' => bcrypt('password'),
        ]);
        $student->roles()->attach($role);

        $this->assertFalse(app(AssessmentPolicy::class)->create($student));
    }
}
