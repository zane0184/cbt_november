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
            'Siswa tanpa NISN valid 10 digit' => 0,
            'NISN berisi string kosong' => 0,
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
                    $nip = trim((string) ($row->nip ?? ''));
                    $nik = trim((string) ($row->nik ?? ''));
                    $nuptk = trim((string) ($row->nuptk ?? ''));
                    $employeeIdentifier = trim((string) ($row->employee_identifier ?? ''));

                    if (!in_array($status, ['PNS', 'PPPK', 'HONORER'], true)) {
                        $counts['Status kepegawaian Guru kosong/tidak dikenal']++;
                    }

                    foreach ([$nip, $nik, $nuptk] as $value) {
                        if ($value === '') {
                            // NULL is allowed for non-primary optional identifiers; empty strings are not.
                            continue;
                        }
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

                    if ($nip !== '' && preg_match('/^\d{18}$/D', $nip) !== 1) {
                        $counts['NIP terisi dengan format tidak valid']++;
                    }
                    if ($nik !== '' && preg_match('/^\d{16}$/D', $nik) !== 1) {
                        $counts['NIK terisi dengan format tidak valid']++;
                    }
                    if ($nuptk !== '' && preg_match('/^\d{16}$/D', $nuptk) !== 1) {
                        $counts['NUPTK terisi dengan format tidak valid']++;
                    }

                    if (in_array($status, ['PNS', 'PPPK'], true)) {
                        if (preg_match('/^\d{18}$/D', $nip) !== 1) {
                            $counts['Guru PNS/PPPK tanpa NIP valid 18 digit']++;
                        }
                        if ($employeeIdentifier !== $nip) {
                            $counts['employee_identifier tidak sesuai identitas login']++;
                        }
                    } elseif ($status === 'HONORER') {
                        if (preg_match('/^\d{16}$/D', $nik) !== 1) {
                            $counts['Guru Honorer tanpa NIK valid 16 digit']++;
                        }
                        if ($employeeIdentifier !== $nik) {
                            $counts['employee_identifier tidak sesuai identitas login']++;
                        }
                    }
                }
            });

        DB::table('student_profiles')
            ->select(['id', 'nisn'])
            ->orderBy('id')
            ->chunk(500, function ($rows) use (&$counts): void {
                foreach ($rows as $row) {
                    $nisn = trim((string) ($row->nisn ?? ''));
                    if ($row->nisn !== null && $nisn === '') {
                        $counts['NISN berisi string kosong']++;
                    }
                    if (preg_match('/^\d{10}$/D', $nisn) !== 1) {
                        $counts['Siswa tanpa NISN valid 10 digit']++;
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
