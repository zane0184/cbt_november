<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Idempotent least-privilege role bootstrap; removes stale grants that violate the current Admin/Guru responsibility boundary. */
class RolePermissionBootstrapper
{
    private const ROLE_GRANTS = [
        'ADMIN_MADRASAH' => ['curriculum.view', 'curriculum.manage', 'academic.manage', 'user.manage', 'exam.proctor.assign'],
        'GURU' => ['curriculum.view', 'question.view', 'question.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.token', 'exam.grade', 'report.view'],
    ];

    public function bootstrap(): void
    {
        $requiredRoleNames = array_keys(self::ROLE_GRANTS);
        $roles = Role::query()->whereIn('name', $requiredRoleNames)->get()->keyBy('name');
        $missing = array_values(array_diff($requiredRoleNames, $roles->keys()->all()));

        // Validate every required role before creating permissions or writing pivots.
        if ($missing !== []) {
            throw new RuntimeException('Required portal roles are missing: ' . implode(', ', $missing));
        }

        DB::transaction(function () use ($roles): void {
            $permissions = [
                'curriculum.view' => 'Lihat Struktur Kurikulum',
                'curriculum.manage' => 'Kelola Struktur Kurikulum',
                'academic.manage' => 'Kelola Data Akademik dan Rombel',
                'question.view' => 'Lihat Bank Soal sesuai kewenangan',
                'question.manage' => 'Kelola Bank Soal sesuai penugasan',
                'user.manage' => 'Kelola akun Guru dan Siswa pada tenant aktif',
                'exam.manage' => 'Kelola ujian sesuai kewenangan',
                'exam.assign' => 'Kelola peserta ujian sesuai kewenangan',
                'exam.monitor' => 'Monitoring ujian sesuai kewenangan',
                'exam.close' => 'Menutup ujian untuk kebutuhan operasional dengan audit',
                'exam.proctor.assign' => 'Menugaskan dan mencabut penugasan Proktor sementara',
                'exam.token' => 'Kelola token ujian setelah publikasi',
                'exam.grade' => 'Koreksi dan finalisasi nilai sesuai kewenangan',
                'exam.reopen' => 'Buka kembali hasil final sesuai kewenangan',
                'report.view' => 'Lihat laporan dan analitik sesuai kewenangan',
            ];

            $permissionIds = [];
            foreach ($permissions as $name => $label) {
                $permissionIds[$name] = Permission::firstOrCreate(
                    ['name' => $name],
                    ['label' => $label]
                )->id;
            }

            foreach (self::ROLE_GRANTS as $roleName => $permissionNames) {
                $ids = array_map(fn (string $name) => $permissionIds[$name], $permissionNames);
                $roles->get($roleName)->permissions()->syncWithoutDetaching($ids);
            }

            // Revoke stale grants that contradict the current tenant role matrix.
            $revokedGrants = [
                'ADMIN_MADRASAH' => ['question.view', 'question.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.close', 'exam.token', 'exam.grade', 'exam.reopen', 'report.view'],
                'GURU' => ['curriculum.manage'],
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
