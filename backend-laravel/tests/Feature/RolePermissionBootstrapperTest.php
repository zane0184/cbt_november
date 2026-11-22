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
        $roles = [
            'ADMIN_MADRASAH' => 'Admin Madrasah',
            'GURU' => 'Guru',
        ];

        foreach ($roles as $name => $label) {
            Role::create(['name' => $name, 'label' => $label]);
        }
    }

    public function test_role_permissions_are_granted_least_privilege_and_idempotently(): void
    {
        $this->createRequiredRoles();
        $manage = Permission::create(['name' => 'curriculum.manage', 'label' => 'Kelola Struktur Kurikulum']);
        Role::where('name', 'GURU')->get()->each(fn (Role $role) => $role->permissions()->attach($manage->id));

        Permission::insertOrIgnore([
            ['name' => 'exam.manage', 'label' => 'Kelola ujian'],
            ['name' => 'exam.assign', 'label' => 'Kelola peserta'],
            ['name' => 'exam.token', 'label' => 'Kelola token'],
            ['name' => 'exam.grade', 'label' => 'Kelola nilai'],
            ['name' => 'question.manage', 'label' => 'Kelola bank soal'],
        ]);
        Role::where('name', 'ADMIN_MADRASAH')->firstOrFail()->permissions()->syncWithoutDetaching(
            Permission::whereIn('name', ['exam.manage', 'exam.assign', 'exam.token', 'exam.grade', 'question.manage'])->pluck('id')->all()
        );

        $bootstrapper = app(RolePermissionBootstrapper::class);

        $bootstrapper->bootstrap();
        $bootstrapper->bootstrap();

        $admin = Role::where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $guru = Role::where('name', 'GURU')->firstOrFail();

        $this->assertEqualsCanonicalizing(['curriculum.view', 'curriculum.manage', 'academic.manage', 'user.manage', 'exam.proctor.assign'], $admin->permissions()->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['curriculum.view', 'question.view', 'question.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.token', 'exam.grade', 'report.view'], $guru->permissions()->pluck('name')->all());
        $this->assertFalse($guru->permissions()->where('name', 'curriculum.manage')->exists());
        $this->assertFalse($admin->permissions()->whereIn('name', ['question.view', 'question.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.close', 'exam.token', 'exam.grade', 'exam.reopen', 'report.view'])->exists());
        $expectedPermissions = ['curriculum.view', 'curriculum.manage', 'academic.manage', 'question.view', 'question.manage', 'user.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.close', 'exam.proctor.assign', 'exam.token', 'exam.grade', 'exam.reopen', 'report.view'];
        $this->assertSame(count($expectedPermissions), Permission::whereIn('name', $expectedPermissions)->count());
    }

    public function test_bootstrap_requires_only_the_three_portal_roles_and_does_not_create_legacy_roles(): void
    {
        $this->createRequiredRoles();
        app(RolePermissionBootstrapper::class)->bootstrap();

        $admin = Role::where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $this->assertTrue($admin->permissions()->where('name', 'exam.proctor.assign')->exists());
        $this->assertFalse($admin->permissions()->where('name', 'exam.manage')->exists());
        $this->assertSame(0, Role::whereIn('name', ['KAMAD', 'OPERATOR'])->count());
        $this->assertEqualsCanonicalizing(['ADMIN_MADRASAH', 'GURU'], Role::query()->pluck('name')->all());
    }

    public function test_missing_required_role_fails_before_creating_permissions(): void
    {
        Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        Role::create(['name' => 'GURU', 'label' => 'Guru']);

        try {
            app(RolePermissionBootstrapper::class)->bootstrap();
            $this->fail('Expected missing-role exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('GURU', $exception->getMessage());
        }

        $this->assertSame(0, Permission::whereIn('name', ['curriculum.view', 'curriculum.manage', 'academic.manage', 'question.view', 'question.manage', 'user.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.close', 'exam.proctor.assign', 'exam.token', 'exam.grade', 'exam.reopen', 'report.view'])->count());
    }
}
