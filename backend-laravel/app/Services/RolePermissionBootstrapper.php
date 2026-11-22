<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Additive, idempotent role bootstrap.
 * Legacy OPERATOR accounts retain a compatibility role but share the Admin Madrasah permission set.
 */
class RolePermissionBootstrapper
{
    private const ROLE_GRANTS = [
        'ADMIN_MADRASAH' => ['curriculum.view', 'curriculum.manage', 'academic.manage', 'user.manage', 'exam.proctor.assign'],
        'KAMAD' => ['curriculum.view', 'exam.monitor', 'report.view'],
        'GURU' => ['curriculum.view', 'question.view', 'question.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.token', 'exam.grade', 'report.view'],
    ];

    /** Legacy role alias: same portal and same administrative permissions as Admin Madrasah. */
    private const OPTIONAL_ROLE_GRANTS = [
        'OPERATOR' => ['curriculum.view', 'curriculum.manage', 'academic.manage', 'user.manage', 'exam.proctor.assign'],
    ];

    public function bootstrap(): void
    {
        $requiredRoleNames = array_keys(self::ROLE_GRANTS);
        $roles = Role::query()->whereIn('name', $requiredRoleNames)->get()->keyBy('name');
        $missing = array_values(array_diff($requiredRoleNames, $roles->keys()->all()));

        if ($missing !== []) {
            throw new RuntimeException('Required curriculum roles are missing: ' . implode(', ', $missing));
        }

        DB::transaction(function () use ($roles): void {
            $permissions = [
                'curriculum.view' => 'Lihat Struktur Kurikulum',
                'curriculum.manage' => 'Kelola Struktur Kurikulum',
                'academic.manage' => 'Kelola Data Akademik dan Rombel',
                'question.view' => 'Lihat Bank Soal sesuai penugasan Guru',
                'question.manage' => 'Kelola Bank Soal sesuai penugasan Guru',
                'user.manage' => 'Kelola akun Guru dan Siswa pada tenant aktif',
                'exam.manage' => 'Guru mengelola asesmen dan ujian sesuai penugasan',
                'exam.assign' => 'Guru mengelola peserta ujian sesuai penugasan',
                'exam.monitor' => 'Monitoring ujian sesuai kewenangan Guru atau penugasan Proktor',
                'exam.close' => 'Guru menutup ujian sesuai kewenangan dan audit',
                'exam.proctor.assign' => 'Admin menugaskan dan mencabut Guru pengawas sementara',
                'exam.token' => 'Guru mengelola token ujian setelah publikasi',
                'exam.grade' => 'Guru mengoreksi dan memfinalisasi nilai',
                'exam.reopen' => 'Guru membuka kembali hasil final sesuai kewenangan',
                'report.view' => 'Lihat laporan sesuai kewenangan Guru/Kamad',
            ];

            $permissionIds = [];
            foreach ($permissions as $name => $label) {
                $permissionIds[$name] = Permission::firstOrCreate(['name' => $name], ['label' => $label])->id;
            }

            foreach (self::ROLE_GRANTS as $roleName => $permissionNames) {
                $ids = array_map(fn (string $name) => $permissionIds[$name], $permissionNames);
                $roles->get($roleName)->permissions()->syncWithoutDetaching($ids);
            }

            foreach (self::OPTIONAL_ROLE_GRANTS as $roleName => $permissionNames) {
                $role = Role::query()->where('name', $roleName)->first();
                if ($role) {
                    $ids = array_map(fn (string $name) => $permissionIds[$name], $permissionNames);
                    $role->permissions()->syncWithoutDetaching($ids);
                }
            }

            // Remove stale grants that would expose Guru's CBT operations to Admin/Operator.
            $revokedGrants = [
                'ADMIN_MADRASAH' => ['question.view', 'question.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.close', 'exam.token', 'exam.grade', 'exam.reopen', 'report.view'],
                'OPERATOR' => ['question.view', 'question.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.close', 'exam.token', 'exam.grade', 'exam.reopen', 'report.view'],
                'KAMAD' => ['curriculum.manage', 'exam.manage', 'exam.assign', 'exam.token', 'exam.grade', 'exam.reopen', 'question.manage'],
                'GURU' => ['curriculum.manage'],
                'PROKTOR' => ['curriculum.view', 'curriculum.manage', 'academic.manage', 'user.manage', 'question.view', 'question.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.close', 'exam.proctor.assign', 'exam.token', 'exam.grade', 'exam.reopen', 'report.view'],
            ];

            foreach ($revokedGrants as $roleName => $permissionNames) {
                $role = $roles->get($roleName) ?? Role::query()->where('name', $roleName)->first();
                if (!$role) {
                    continue;
                }

                $ids = array_map(fn (string $name) => $permissionIds[$name], $permissionNames);
                $role->permissions()->detach($ids);
            }
        });
    }
}
