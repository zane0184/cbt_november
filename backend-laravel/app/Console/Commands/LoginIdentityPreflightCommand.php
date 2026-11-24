<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LoginIdentityPreflightCommand extends Command
{
    protected $signature = 'login-identity:preflight';

    protected $description = 'Audit read-only identitas login Admin, Guru, dan Siswa sebelum penegakan constraint.';

    private const REQUIRED_SCHEMA = [
        'teacher_profiles' => [
            'id', 'user_id', 'tenant_id', 'employment_status', 'employee_identifier',
            'nip', 'nik', 'nuptk', 'deleted_at',
        ],
        'student_profiles' => ['id', 'user_id', 'tenant_id', 'nisn', 'deleted_at'],
        'users' => ['id', 'tenant_id', 'username'],
        'role_user' => ['user_id', 'role_id'],
        'roles' => ['id', 'name'],
    ];

    public function handle(): int
    {
        if (!$this->validateRequiredSchema()) {
            return self::FAILURE;
        }

        $counts = [
            'Status kepegawaian Guru kosong/tidak dikenal' => 0,
            'Guru PNS/PPPK tanpa NIP valid 18 digit' => 0,
            'Guru Honorer tanpa NIK valid 16 digit' => 0,
            'NIP terisi dengan format tidak valid' => 0,
            'NIK terisi dengan format tidak valid' => 0,
            'NUPTK terisi dengan format tidak valid' => 0,
            'Guru PNS/PPPK mengisi NIK yang tidak diperbolehkan' => 0,
            'Guru Honorer mengisi NIP yang tidak diperbolehkan' => 0,
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
            'Profil Guru tanpa user' => 0,
            'Profil Siswa tanpa user' => 0,
            'Tenant profil Guru tidak sama dengan tenant user' => 0,
            'Tenant profil Siswa tidak sama dengan tenant user' => 0,
            'Profil Guru tidak memiliki tepat satu role GURU' => 0,
            'Profil Siswa tidak memiliki tepat satu role SISWA' => 0,
            'User memiliki profil Guru lebih dari satu' => 0,
            'User memiliki profil Siswa lebih dari satu' => 0,
            'User memiliki profil Guru dan Siswa sekaligus' => 0,
            'Akun memiliki jumlah role bukan satu' => 0,
            'Akun memiliki role di luar tiga portal aktif' => 0,
            'Akun Guru tanpa profil aktif' => 0,
            'Akun Siswa tanpa profil aktif' => 0,
            'Akun Admin memiliki profil Guru/Siswa' => 0,
            'Admin tanpa username valid' => 0,
            'Username Admin memiliki spasi di tepi' => 0,
            'Kelompok username Admin duplikat per tenant' => 0,
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
                        if ($nik !== '') {
                            $counts['Guru PNS/PPPK mengisi NIK yang tidak diperbolehkan']++;
                        }
                        if ($employeeIdentifier !== $nipRaw) {
                            $counts['employee_identifier tidak sesuai identitas login']++;
                        }
                    } elseif ($status === 'HONORER') {
                        if (preg_match('/^\d{16}$/D', $nikRaw) !== 1) {
                            $counts['Guru Honorer tanpa NIK valid 16 digit']++;
                        }
                        if ($nip !== '') {
                            $counts['Guru Honorer mengisi NIP yang tidak diperbolehkan']++;
                        }
                        if ($employeeIdentifier !== $nikRaw) {
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

        $activeTeacherUserIds = array_fill_keys(
            DB::table('teacher_profiles')->whereNull('deleted_at')->pluck('user_id')->all(),
            true
        );
        $activeStudentUserIds = array_fill_keys(
            DB::table('student_profiles')->whereNull('deleted_at')->pluck('user_id')->all(),
            true
        );

        $teacherProfiles = DB::table('teacher_profiles as tp')
            ->leftJoin('users as u', 'u.id', '=', 'tp.user_id')
            ->leftJoin('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->leftJoin('roles as r', 'r.id', '=', 'ru.role_id')
            ->whereNull('tp.deleted_at')
            ->select([
                'tp.id',
                'tp.user_id',
                'tp.tenant_id as profile_tenant_id',
                'u.id as matched_user_id',
                'u.tenant_id as user_tenant_id',
            ])
            ->selectRaw('COUNT(ru.role_id) AS role_count')
            ->selectRaw("SUM(CASE WHEN r.name = 'GURU' THEN 1 ELSE 0 END) AS guru_role_count")
            ->groupBy('tp.id', 'tp.user_id', 'tp.tenant_id', 'u.id', 'u.tenant_id')
            ->get();

        foreach ($teacherProfiles as $profile) {
            if ($profile->matched_user_id === null) {
                $counts['Profil Guru tanpa user']++;
            } elseif ((string) $profile->profile_tenant_id !== (string) $profile->user_tenant_id) {
                $counts['Tenant profil Guru tidak sama dengan tenant user']++;
            }

            if ((int) $profile->role_count !== 1 || (int) $profile->guru_role_count !== 1) {
                $counts['Profil Guru tidak memiliki tepat satu role GURU']++;
            }
        }

        $studentProfiles = DB::table('student_profiles as sp')
            ->leftJoin('users as u', 'u.id', '=', 'sp.user_id')
            ->leftJoin('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->leftJoin('roles as r', 'r.id', '=', 'ru.role_id')
            ->whereNull('sp.deleted_at')
            ->select([
                'sp.id',
                'sp.user_id',
                'sp.tenant_id as profile_tenant_id',
                'u.id as matched_user_id',
                'u.tenant_id as user_tenant_id',
            ])
            ->selectRaw('COUNT(ru.role_id) AS role_count')
            ->selectRaw("SUM(CASE WHEN r.name = 'SISWA' THEN 1 ELSE 0 END) AS siswa_role_count")
            ->groupBy('sp.id', 'sp.user_id', 'sp.tenant_id', 'u.id', 'u.tenant_id')
            ->get();

        foreach ($studentProfiles as $profile) {
            if ($profile->matched_user_id === null) {
                $counts['Profil Siswa tanpa user']++;
            } elseif ((string) $profile->profile_tenant_id !== (string) $profile->user_tenant_id) {
                $counts['Tenant profil Siswa tidak sama dengan tenant user']++;
            }

            if ((int) $profile->role_count !== 1 || (int) $profile->siswa_role_count !== 1) {
                $counts['Profil Siswa tidak memiliki tepat satu role SISWA']++;
            }
        }

        $duplicateTeacherUsers = DB::table('teacher_profiles')
            ->whereNull('deleted_at')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1');
        $counts['User memiliki profil Guru lebih dari satu'] = DB::query()
            ->fromSub($duplicateTeacherUsers, 'duplicate_teacher_users')
            ->count();

        $duplicateStudentUsers = DB::table('student_profiles')
            ->whereNull('deleted_at')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1');
        $counts['User memiliki profil Siswa lebih dari satu'] = DB::query()
            ->fromSub($duplicateStudentUsers, 'duplicate_student_users')
            ->count();

        $counts['User memiliki profil Guru dan Siswa sekaligus'] = DB::table('teacher_profiles as tp')
            ->join('student_profiles as sp', 'sp.user_id', '=', 'tp.user_id')
            ->whereNull('tp.deleted_at')
            ->whereNull('sp.deleted_at')
            ->distinct()
            ->count('tp.user_id');

        $users = DB::table('users as u')
            ->leftJoin('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->leftJoin('roles as r', 'r.id', '=', 'ru.role_id')
            ->select(['u.id', 'u.tenant_id', 'u.username'])
            ->selectRaw('COUNT(ru.role_id) AS role_count')
            ->selectRaw('MAX(r.name) AS role_name')
            ->groupBy('u.id', 'u.tenant_id')
            ->get();

        foreach ($users as $user) {
            $roleCount = (int) $user->role_count;
            $roleName = (string) ($user->role_name ?? '');

            if ($roleCount !== 1) {
                $counts['Akun memiliki jumlah role bukan satu']++;
                continue;
            }

            if (!in_array($roleName, ['ADMIN_MADRASAH', 'GURU', 'SISWA'], true)) {
                $counts['Akun memiliki role di luar tiga portal aktif']++;
            }

            if ($roleName === 'ADMIN_MADRASAH') {
                $usernameRaw = (string) ($user->username ?? '');
                if (trim($usernameRaw) === '') {
                    $counts['Admin tanpa username valid']++;
                }
                if ($usernameRaw !== trim($usernameRaw)) {
                    $counts['Username Admin memiliki spasi di tepi']++;
                }
            }

            if ($roleName === 'GURU' && !isset($activeTeacherUserIds[$user->id])) {
                $counts['Akun Guru tanpa profil aktif']++;
            } elseif ($roleName === 'SISWA' && !isset($activeStudentUserIds[$user->id])) {
                $counts['Akun Siswa tanpa profil aktif']++;
            } elseif ($roleName === 'ADMIN_MADRASAH'
                && (isset($activeTeacherUserIds[$user->id]) || isset($activeStudentUserIds[$user->id]))) {
                $counts['Akun Admin memiliki profil Guru/Siswa']++;
            }
        }

        $duplicateAdminUsernames = DB::table('users as u')
            ->join('role_user as ru', 'ru.user_id', '=', 'u.id')
            ->join('roles as r', 'r.id', '=', 'ru.role_id')
            ->where('r.name', 'ADMIN_MADRASAH')
            ->whereNotNull('u.username')
            ->whereRaw("TRIM(u.username) <> ''")
            ->select('u.tenant_id')
            ->selectRaw('LOWER(TRIM(u.username)) AS normalized_username')
            ->groupBy('u.tenant_id')
            ->groupByRaw('LOWER(TRIM(u.username))')
            ->havingRaw('COUNT(*) > 1');
        $counts['Kelompok username Admin duplikat per tenant'] = DB::query()
            ->fromSub($duplicateAdminUsernames, 'duplicate_admin_usernames')
            ->count();

        $rows = [];
        foreach ($counts as $label => $count) {
            $rows[] = [$label, $count];
        }
        $this->table(['Pemeriksaan read-only', 'Jumlah'], $rows);

        if (array_sum($counts) > 0) {
            $this->error('Ditemukan konflik identitas atau relasi akun/profil. Tidak ada data yang diubah; rekonsiliasi harus dilakukan berdasarkan bukti resmi.');
            return self::FAILURE;
        }

        $this->info('Tidak ditemukan konflik identitas/relasi pada pemeriksaan yang dicakup. Tidak ada data yang diubah.');
        return self::SUCCESS;
    }

    private function validateRequiredSchema(): bool
    {
        foreach (self::REQUIRED_SCHEMA as $table => $requiredColumns) {
            if (!Schema::hasTable($table)) {
                $this->error("Preflight dihentikan: tabel {$table} belum tersedia.");
                return false;
            }

            $availableColumns = Schema::getColumnListing($table);
            $missingColumns = array_values(array_diff($requiredColumns, $availableColumns));

            if ($missingColumns !== []) {
                $this->error("Preflight dihentikan: kolom wajib pada tabel {$table} belum lengkap (" . implode(', ', $missingColumns) . ').');
                return false;
            }
        }

        return true;
    }
}
