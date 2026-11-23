<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Services\RolePermissionBootstrapper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Production-safe base catalog seeder. Never creates demo tenants or users. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $roles = [
                'ADMIN_MADRASAH' => 'Admin Madrasah',
                'GURU' => 'Guru / Pengajar',
                'SISWA' => 'Siswa / Peserta Ujian',
            ];

            foreach ($roles as $name => $label) {
                Role::firstOrCreate(['name' => $name], ['label' => $label]);
            }

            $permissions = [
                ['name' => 'tenant.view', 'label' => 'Lihat Madrasah'],
                ['name' => 'tenant.manage', 'label' => 'Kelola Data Madrasah'],
                ['name' => 'user.view', 'label' => 'Lihat Akun Pengguna'],
                ['name' => 'user.manage', 'label' => 'Kelola Akun Pengguna'],
                ['name' => 'academic.manage', 'label' => 'Kelola Data Akademik dan Rombel'],
                ['name' => 'question.view', 'label' => 'Lihat Bank Soal'],
                ['name' => 'question.manage', 'label' => 'Kelola Bank Soal sesuai penugasan'], 
                ['name' => 'exam.manage', 'label' => 'Kelola Ujian'],
                ['name' => 'exam.assign', 'label' => 'Penugasan Peserta Ujian'],
                ['name' => 'exam.monitor', 'label' => 'Monitoring Ujian'],
                ['name' => 'exam.token', 'label' => 'Kelola Token Ujian'],
                ['name' => 'exam.grade', 'label' => 'Koreksi dan Finalisasi Nilai'],
                ['name' => 'exam.reopen', 'label' => 'Buka Kembali Hasil Ujian'],
                ['name' => 'report.view', 'label' => 'Lihat Laporan dan Analisis'],
                ['name' => 'executive.dashboard', 'label' => 'Dashboard Eksekutif'],
            ];

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission['name']], ['label' => $permission['label']]);
            }

            app(RolePermissionBootstrapper::class)->bootstrap();
        });

        $this->call(CurriculumReferenceSourceSeeder::class);
    }
}
