<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Bootstrap only operational roles that may be created in a new standalone installation.
 * OPERATOR, KAMAD and PROKTOR are retired identities. They receive no grants and
 * must be handled only by an explicit, audited legacy-upgrade process.
 */
class RolePermissionBootstrapper
{
    private const ROLE_GRANTS = [
        'ADMIN_MADRASAH' => [
            'curriculum.view', 'curriculum.manage', 'academic.manage',
            'user.manage', 'tenant.manage', 'exam.proctor.assign',
        ],
        'GURU' => [
            'curriculum.view', 'question.view', 'question.manage',
            'exam.manage', 'exam.assign', 'exam.monitor', 'exam.token',
            'exam.grade', 'exam.reopen', 'report.view',
        ],
        'SISWA' => [],
    ];

    private const LEGACY_ROLES = ['OPERATOR', 'KAMAD', 'PROKTOR', 'SUPER_ADMIN'];

    public function bootstrap(): void
    {
        $requiredRoleNames = array_keys(self::ROLE_GRANTS);
        $roles = Role::query()->whereIn('name', $requiredRoleNames)->get()->keyBy('name');
        $missing = array_values(array_diff($requiredRoleNames, $roles->keys()->all()));

        if ($missing !== []) {
            throw new RuntimeException('Required operational roles are missing: ' . implode(', ', $missing));
        }

        DB::transaction(function () use ($roles): void {
            $permissions = [
                'curriculum.view' => 'Lihat struktur kurikulum',
                'curriculum.manage' => 'Kelola struktur kurikulum',
                'academic.manage' => 'Kelola data akademik dan Rombel',
                'question.view' => 'Lihat Bank Soal sesuai penugasan Guru',
                'question.manage' => 'Kelola Bank Soal sesuai penugasan Guru',
                'user.manage' => 'Kelola akun Guru dan Siswa madrasah',
                'tenant.manage' => 'Kelola identitas madrasah lokal',
                'exam.manage' => 'Guru mengelola asesmen dan ujian sesuai penugasan',
                'exam.assign' => 'Guru mengelola peserta ujian sesuai penugasan',
                'exam.monitor' => 'Monitoring Guru sesuai penugasan aktif',
                'exam.close' => 'Guru menutup ujian sesuai kewenangan dan audit',
                'exam.proctor.assign' => 'Admin menugaskan dan mencabut Guru pengawas sementara',
                'exam.token' => 'Guru mengelola token ujian setelah publikasi',
                'exam.grade' => 'Guru mengoreksi dan memfinalisasi nilai',
                'exam.reopen' => 'Guru mengoreksi hasil final sesuai kewenangan dan audit',
                'report.view' => 'Lihat laporan akademik sesuai penugasan Guru',
            ];

            $permissionIds = [];
            foreach ($permissions as $name => $label) {
                $permissionIds[$name] = Permission::firstOrCreate(['name' => $name], ['label' => $label])->id;
            }

            foreach (self::ROLE_GRANTS as $roleName => $permissionNames) {
                $ids = array_map(fn (string $name) => $permissionIds[$name], $permissionNames);
                $roles->get($roleName)->permissions()->sync($ids);
            }

            // Retire permission grants first. Do not delete legacy role rows or reassign users
            // here; a separate versioned upgrade migration must handle account mapping with audit.
            Role::query()->whereIn('name', self::LEGACY_ROLES)->get()->each(
                fn (Role $legacyRole) => $legacyRole->permissions()->detach()
            );
        });
    }
}
