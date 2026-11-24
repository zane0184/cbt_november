<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoginIdentityPreflightCommand extends Command
{
    protected $signature = 'login-identity:preflight';

    protected $description = 'Audit read-only identitas login Admin, Guru, dan Siswa sebelum penegakan constraint.';

    public function handle(): int
    {
        foreach (['teacher_profiles', 'student_profiles'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Preflight dihentikan: tabel {$table} belum tersedia.");
                return self::FAILURE;
            }
        }

        $counts = [
            'Status kepegawaian Guru kosong/tidak dikenal' => 0,
            'Guru PNS/PPPK tanpa NIP valid 18 digit' => 0,
            'Guru Honorer tanpa NIK valid 16 digit' => 0,
            'NIP terisi dengan format tidak valid' => 0,
            'NIK terisi dengan format tidak valid' => 0,
            'NUPTK terisi dengan format tidak valid' => 0,
            'employee_identifier tidak sesuai identitas login' => 0,
            'Field identitas Guru berisi string kosong' => 0,
            'Field identitas Guru memiliki spasi di tepi' => 0,
            'Siswa tanpa NISN valid 10 digit' => 0,
            'NISN berisi string kosong' => 0,
            'NISN memiliki spasi di tepi' => 0,
            'Kelompok NIP duplikat per tenant' => 0,
            'Kelompok NIK duplikat per tenant' => 0,
            'Kelompok NUPTK duplikat per tenant' => 0,
            'Kelompok NISN duplikat per tenant' => 0,
        ];

        DB::table('teacher_profiles')
            ->select(['id', 'employment_status', 'employee_identifier', 'nip', 'nik', 'nuptk'])
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$counts): void {
                foreach ($rows as $row) {
                    $status = (string) ($row->employment_status ?? '');
                    $nipRaw = (string) ($row->nip ?? '');
                    $nikRaw = (string) ($row->nik ?? '');
                    $nuptkRaw = (string) ($row->nuptk ?? '');
                    $nip = trim($nipRaw);
                    $nik = trim($nikRaw);
                    $nuptk = trim($nuptkRaw);
                    $employeeIdentifier = (string) ($row->employee_identifier ?? '');

                    if (!in_array($status, ['PNS', 'PPPK', 'HONORER'], true)) {
                        $counts['Status kepegawaian Guru kosong/tidak dikenal']++;
                    }

                    if ($row->nip !== null && $nip === '') {
                        $counts['Field identitas Guru berisi string kosong']++;
                    }
                    if ($row->nik !== null && $nik === '') {
                        $counts['Field identitas Guru berisi string kosong']++;
                    }
                    if ($row->nuptk !== null && $nuptk === '') {
                        $counts['Field identitas Guru berisi string kosong']++;
                    }
                    if (($nipRaw !== '' && $nipRaw !== $nip)
                        || ($nikRaw !== '' && $nikRaw !== $nik)
                        || ($nuptkRaw !== '' && $nuptkRaw !== $nuptk)) {
                        $counts['Field identitas Guru memiliki spasi di tepi']++;
                    }

                    if ($nipRaw !== '' && preg_match('/^\d{18}$/D', $nipRaw) !== 1) {
                        $counts['NIP terisi dengan format tidak valid']++;
                    }
                    if ($nikRaw !== '' && preg_match('/^\d{16}$/D', $nikRaw) !== 1) {
                        $counts['NIK terisi dengan format tidak valid']++;
                    }
                    if ($nuptkRaw !== '' && preg_match('/^\d{16}$/D', $nuptkRaw) !== 1) {
                        $counts['NUPTK terisi dengan format tidak valid']++;
                    }

                    if (in_array($status, ['PNS', 'PPPK'], true)) {
                        if (preg_match('/^\d{18}$/D', $nipRaw) !== 1) {
                            $counts['Guru PNS/PPPK tanpa NIP valid 18 digit']++;
                        }
                        if ($employeeIdentifier !== $nipRaw) {
                            $counts['employee_identifier tidak sesuai identitas login']++;
                        }
                    } elseif ($status === 'HONORER') {
                        if (preg_match('/^\d{16}$/D', $nikRaw) !== 1) {
                            $counts['Guru Honorer tanpa NIK valid 16 digit']++;
                        }
                        if ($employeeIdentifier !== $nikRaw) {
                            $counts['employee_identifier tidak sesuai identitas login']++;
                        }
                    }
            });

        DB::table('student_profiles')
            ->select(['id', 'nisn'])
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$counts): void {
                foreach ($rows as $row) {
                    $nisnRaw = (string) ($row->nisn ?? '');
                    $nisn = trim($nisnRaw);
                    if ($row->nisn !== null && $nisn === '') {
                        $counts['NISN berisi string kosong']++;
                    }
                    if ($nisnRaw !== '' && $nisnRaw !== $nisn) {
                        $counts['NISN memiliki spasi di tepi']++;
                    }
                    if (preg_match('/^\d{10}$/D', $nisnRaw) !== 1) {
                        $counts['Siswa tanpa NISN valid 10 digit']++;
                    }
                }
                }
            });

        foreach ([
            ['teacher_profiles', 'nip', 'Kelompok NIP duplikat per tenant'],
            ['teacher_profiles', 'nik', 'Kelompok NIK duplikat per tenant'],
            ['teacher_profiles', 'nuptk', 'Kelompok NUPTK duplikat per tenant'],
            ['student_profiles', 'nisn', 'Kelompok NISN duplikat per tenant'],
        ] as [$table, $column, $label]) {
            $duplicates = DB::table($table)
                ->select(['tenant_id', $column])
                ->whereNotNull($column)
                ->whereRaw("TRIM({$column}) <> ''")
                ->groupBy('tenant_id', $column)
                ->havingRaw('COUNT(*) > 1');

            $counts[$label] = DB::query()->fromSub($duplicates, 'duplicate_identity_groups')->count();
        }

        $rows = [];
        foreach ($counts as $label => $count) {
            $rows[] = [$label, $count];
        }
        $this->table(['Pemeriksaan read-only', 'Jumlah'], $rows);

        if (array_sum($counts) > 0) {
            $this->error('Ditemukan konflik identitas. Tidak ada data yang diubah; rekonsiliasi harus dilakukan berdasarkan bukti resmi.');
            return self::FAILURE;
        }

        $this->info('Tidak ditemukan konflik pada pemeriksaan identitas yang dicakup. Tidak ada data yang diubah.');
        return self::SUCCESS;
    }
}
