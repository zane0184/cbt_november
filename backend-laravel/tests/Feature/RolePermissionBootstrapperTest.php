<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Services\RolePermissionBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class RolePermissionBootstrapperTest extends TestCase
{
    use RefreshDatabase;

    private function createRequiredRoles(): void
    {
        foreach ([
            'ADMIN_MADRASAH' => 'Admin Madrasah',
            'KAMAD' => 'Kepala Madrasah',
            'GURU' => 'Guru',
        ] as $name => $label) {
            Role::create(['name' => $name, 'label' => $label]);
        }
    }

    public function test_role_permissions_are_granted_least_privilege_and_idempotently(): void
    {
        $this->createRequiredRoles();
        $manage = Permission::create(['name' => 'curriculum.manage', 'label' => 'Kelola Struktur Kurikulum']);
        Role::where('name', 'KAMAD')->firstOrFail()->permissions()->attach($manage->id);
        Role::where('name', 'GURU')->firstOrFail()->permissions()->attach($manage->id);

        $bootstrapper = app(RolePermissionBootstrapper::class);

        $bootstrapper->bootstrap();
        $bootstrapper->bootstrap();

        $admin = Role::where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $kamad = Role::where('name', 'KAMAD')->firstOrFail();
        $guru = Role::where('name', 'GURU')->firstOrFail();

        $this->assertEqualsCanonicalizing(['curriculum.view', 'curriculum.manage', 'academic.manage', 'question.view', 'user.manage', 'exam.monitor', 'exam.token', 'exam.reopen', 'report.view'], $admin->permissions()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['curriculum.view', 'exam.monitor', 'report.view'], $kamad->permissions()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['curriculum.view', 'question.view', 'question.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.grade', 'report.view'], $guru->permissions()->pluck('name')->all());
        $this->assertFalse($kamad->permissions()->where('name', 'curriculum.manage')->exists());
        $this->assertFalse($guru->permissions()->where('name', 'curriculum.manage')->exists());
        $expectedPermissions = ['curriculum.view', 'curriculum.manage', 'academic.manage', 'question.view', 'question.manage', 'user.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.token', 'exam.grade', 'exam.reopen', 'report.view'];
        $this->assertSame(count($expectedPermissions), Permission::whereIn('name', $expectedPermissions)->count());
    }

    public function test_missing_required_role_fails_before_creating_permissions(): void
    {
        Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        Role::create(['name' => 'GURU', 'label' => 'Guru']);

        try {
            app(RolePermissionBootstrapper::class)->bootstrap();
            $this->fail('Expected missing-role exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('KAMAD', $exception->getMessage());
        }

        $this->assertSame(0, Permission::whereIn('name', ['curriculum.view', 'curriculum.manage', 'academic.manage', 'question.view', 'question.manage', 'user.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.token', 'exam.grade', 'exam.reopen', 'report.view'])->count());
    }
}
