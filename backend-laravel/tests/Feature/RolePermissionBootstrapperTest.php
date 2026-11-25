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

    private function createOperationalRoles(): void
    {
        foreach ([
            'ADMIN_MADRASAH' => 'Admin Madrasah',
            'GURU' => 'Guru',
            'SISWA' => 'Siswa',
        ] as $name => $label) {
            Role::create(['name' => $name, 'label' => $label]);
        }
    }

    public function test_only_three_operational_roles_receive_their_exact_least_privilege_grants_idempotently(): void
    {
        $this->createOperationalRoles();

        // Legacy role rows can exist on an installation awaiting an approved upgrade.
        // Bootstrap must detach grants, never reactivate or silently remap those identities.
        foreach (['OPERATOR', 'KAMAD', 'PROKTOR', 'SUPER_ADMIN'] as $legacyName) {
            $legacyRole = Role::create(['name' => $legacyName, 'label' => $legacyName]);
            $legacyPermission = Permission::create([
                'name' => 'legacy.' . strtolower($legacyName),
                'label' => 'Legacy permission',
            ]);
            $legacyRole->permissions()->attach($legacyPermission->id);
        }

        $bootstrapper = app(RolePermissionBootstrapper::class);
        $bootstrapper->bootstrap();
        $bootstrapper->bootstrap();

        $admin = Role::where('name', 'ADMIN_MADRASAH')->firstOrFail();
        $guru = Role::where('name', 'GURU')->firstOrFail();
        $siswa = Role::where('name', 'SISWA')->firstOrFail();

        $this->assertEqualsCanonicalizing([
            'curriculum.view',
            'curriculum.registry.manage',
            'curriculum.source.manage',
            'assessment.taxonomy.manage',
            'academic.manage',
            'user.manage',
            'tenant.manage',
            'exam.proctor.assign',
        ], $admin->permissions()->pluck('name')->all());

        $this->assertEqualsCanonicalizing([
            'curriculum.view',
            'curriculum.manage',
            'question.view',
            'question.manage',
            'exam.manage',
            'exam.assign',
            'exam.monitor',
            'exam.token',
            'exam.grade',
            'exam.reopen',
            'report.view',
        ], $guru->permissions()->pluck('name')->all());

        $this->assertSame([], $siswa->permissions()->pluck('name')->all());

        foreach (['OPERATOR', 'KAMAD', 'PROKTOR', 'SUPER_ADMIN'] as $legacyName) {
            $legacyRole = Role::where('name', $legacyName)->firstOrFail();
            $this->assertSame([], $legacyRole->permissions()->pluck('name')->all());
        }

        foreach ([
            'question.view', 'question.manage', 'exam.manage', 'exam.assign',
            'exam.monitor', 'exam.close', 'exam.token', 'exam.grade', 'exam.reopen',
            'report.view',
        ] as $forbiddenForAdmin) {
            $this->assertFalse(
                $admin->permissions()->where('name', $forbiddenForAdmin)->exists(),
                $forbiddenForAdmin . ' must not be granted to Admin Madrasah.'
            );
        }

        $this->assertSame(
            19,
            Permission::whereIn('name', [
                'curriculum.view', 'curriculum.manage', 'curriculum.registry.manage',
                'curriculum.source.manage', 'assessment.taxonomy.manage', 'academic.manage',
                'question.view', 'question.manage', 'user.manage', 'tenant.manage',
                'exam.manage', 'exam.assign', 'exam.monitor', 'exam.close',
                'exam.proctor.assign', 'exam.token', 'exam.grade', 'exam.reopen',
                'report.view',
            ])->count()
        );
    }

    public function test_legacy_role_rows_are_not_required_for_operational_bootstrap(): void
    {
        $this->createOperationalRoles();

        app(RolePermissionBootstrapper::class)->bootstrap();

        $this->assertTrue(Role::where('name', 'ADMIN_MADRASAH')->exists());
        $this->assertTrue(Role::where('name', 'GURU')->exists());
        $this->assertTrue(Role::where('name', 'SISWA')->exists());
        $this->assertFalse(Role::whereIn('name', ['OPERATOR', 'KAMAD', 'PROKTOR', 'SUPER_ADMIN'])->exists());
    }

    public function test_missing_operational_role_fails_before_creating_permissions(): void
    {
        Role::create(['name' => 'ADMIN_MADRASAH', 'label' => 'Admin Madrasah']);
        Role::create(['name' => 'SISWA', 'label' => 'Siswa']);

        try {
            app(RolePermissionBootstrapper::class)->bootstrap();
            $this->fail('Expected missing-role exception was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('GURU', $exception->getMessage());
        }

        $this->assertSame(0, Permission::count());
    }
}
