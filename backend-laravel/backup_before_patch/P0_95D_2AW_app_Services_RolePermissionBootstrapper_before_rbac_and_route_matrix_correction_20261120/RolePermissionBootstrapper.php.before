<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Additive, idempotent least-privilege bootstrap for Admin, curriculum, question-bank, exam, grading, and reporting access. */
class RolePermissionBootstrapper
{
    private const ROLE_GRANTS = [
        'ADMIN_MADRASAH' => ['curriculum.view', 'curriculum.manage', 'academic.manage', 'question.view', 'user.manage', 'exam.monitor', 'exam.close', 'exam.proctor.assign', 'exam.reopen', 'report.view'],
        'KAMAD' => ['curriculum.view', 'exam.monitor', 'report.view'],
        'GURU' => ['curriculum.view', 'question.view', 'question.manage', 'exam.manage', 'exam.assign', 'exam.monitor', 'exam.token', 'exam.grade', 'report.view'],
    ];

    /** Optional legacy operational role sharing the Admin Madrasah portal, without exam authoring rights. */
    private const OPTIONAL_ROLE_GRANTS = [
        'OPERATOR' => ['curriculum.view', 'academic.manage', 'exam.monitor', 'report.view'],
    ];

    public function bootstrap(): void
    {
        $requiredRoleNames = array_keys(self::ROLE_GRANTS);
        $roles = Role::query()->whereIn('name', $requiredRoleNames)->get()->keyBy('name');
        $missing = array_values(array_diff($requiredRoleNames, $roles->keys()->all()));

        // Validate every required role before creating permissions or writing pivots.
        if ($missing !== []) {
            throw new RuntimeException('Required curriculum roles are missing: ' . implode(', ', $missing));
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

            foreach (self::OPTIONAL_ROLE_GRANTS as $roleName => $permissionNames) {
                $role = Role::query()->where('name', $roleName)->first();
                if ($role) {
                    $ids = array_map(fn (string $name) => $permissionIds[$name], $permissionNames);
                    $role->permissions()->syncWithoutDetaching($ids);
                }
            }

            // Revoke stale grants that contradict the current tenant role matrix.
            $revokedGrants = [
                'ADMIN_MADRASAH' => ['exam.manage', 'exam.assign', 'exam.token', 'exam.grade', 'question.manage'],
                'KAMAD' => ['curriculum.manage', 'exam.manage', 'exam.assign', 'exam.token', 'exam.grade', 'exam.reopen', 'question.manage'],
                'GURU' => ['curriculum.manage'],
                'OPERATOR' => ['curriculum.manage', 'exam.manage', 'exam.assign', 'exam.token', 'exam.grade', 'exam.reopen', 'question.manage'],
                'PROKTOR' => ['exam.manage', 'exam.assign', 'exam.token', 'exam.grade', 'exam.reopen', 'exam.monitor', 'question.manage'],
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
