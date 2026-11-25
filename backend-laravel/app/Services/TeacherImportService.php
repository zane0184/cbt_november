<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\Role;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\Import\StudentImportReadFilter;
use App\Services\Import\XlsxPackagePreflight;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TeacherImportService
{
    private const MAX_ROWS = 5000;
    private const MAX_COLUMNS = 8;
    private const MAX_CELL_BYTES = 4096;
    private const HEADERS = ['nama', 'status_kepegawaian', 'nip', 'nik', 'nuptk', 'jenis_kelamin', 'telepon', 'email'];

    public function preview(string $path, string $extension, string $tenantId): array
    {
        $this->assertAdminActor($tenantId);
        [$headers, $sourceRows] = $this->readRows($path, strtolower($extension));

        if (count(array_unique($headers)) !== count($headers)) {
            throw ValidationException::withMessages(['file' => 'Nama kolom tidak boleh duplikat.']);
        }
        if (!in_array('nama', $headers, true) || !in_array('status_kepegawaian', $headers, true)) {
            throw ValidationException::withMessages(['file' => 'Kolom wajib nama dan status_kepegawaian tidak ditemukan. Gunakan template Import Guru.']);
        }
        $unknown = array_values(array_diff($headers, self::HEADERS));
        if ($unknown !== []) {
            throw ValidationException::withMessages(['file' => 'Kolom tidak dikenal: '.implode(', ', $unknown).'. Gunakan template Import Guru.']);
        }

        $fileHash = hash_file('sha256', $path);
        $idempotencyKey = hash('sha256', 'teacher-import-v1|'.$tenantId.'|'.$fileHash);
        $alreadyImported = DB::table('teacher_import_batches')
            ->where('tenant_id', $tenantId)
            ->where('idempotency_key', $idempotencyKey)
            ->exists();

        $seen = ['nip' => [], 'nik' => [], 'nuptk' => [], 'email' => []];
        $rows = [];
        $valid = 0;

        foreach ($sourceRows as $source) {
            $values = $source['values'];
            if ($this->isBlankRow($values)) {
                continue;
            }
            $data = [];
            foreach ($headers as $index => $header) {
                $data[$header] = $values[$index] ?? null;
            }

            $name = $this->stringValue($data['nama'] ?? null);
            $status = strtoupper($this->stringValue($data['status_kepegawaian'] ?? null));
            $nip = $this->stringValue($data['nip'] ?? null);
            $nik = $this->stringValue($data['nik'] ?? null);
            $nuptk = $this->stringValue($data['nuptk'] ?? null);
            $genderRaw = strtoupper($this->stringValue($data['jenis_kelamin'] ?? null));
            $phone = $this->stringValue($data['telepon'] ?? null);
            $email = strtolower($this->stringValue($data['email'] ?? null));
            $errors = [];

            if ($name === '' || mb_strlen($name) > 255) {
                $errors[] = 'Nama wajib diisi dan maksimal 255 karakter.';
            }
            if (!in_array($status, ['PNS', 'PPPK', 'HONORER'], true)) {
                $errors[] = 'Status kepegawaian harus PNS, PPPK, atau HONORER.';
            } elseif (in_array($status, ['PNS', 'PPPK'], true)) {
                if (!preg_match('/^\d{18}$/D', $nip)) {
                    $errors[] = 'Guru PNS/PPPK wajib memiliki NIP tepat 18 digit.';
                }
                if ($nik !== '') {
                    $errors[] = 'NIK harus dikosongkan untuk Guru PNS/PPPK.';
                }
            } else {
                if (!preg_match('/^\d{16}$/D', $nik)) {
                    $errors[] = 'Guru Honorer wajib memiliki NIK tepat 16 digit.';
                }
                if ($nip !== '') {
                    $errors[] = 'NIP harus dikosongkan untuk Guru Honorer.';
                }
            }
            if ($nuptk !== '' && !preg_match('/^\d{16}$/D', $nuptk)) {
                $errors[] = 'NUPTK harus kosong atau tepat 16 digit.';
            }
            $gender = match ($genderRaw) {
                '', '—' => null,
                'L', 'LAKI-LAKI', 'LAKI LAKI', 'M' => 'M',
                'P', 'PEREMPUAN', 'F' => 'F',
                default => '__INVALID__',
            };
            if ($gender === '__INVALID__') {
                $errors[] = 'Jenis kelamin harus L atau P.';
            }
            if (mb_strlen($phone) > 40) {
                $errors[] = 'Telepon maksimal 40 karakter.';
            }
            if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 255)) {
                $errors[] = 'Email harus berupa alamat email valid dan maksimal 255 karakter.';
            }

            foreach (['nip' => $nip, 'nik' => $nik, 'nuptk' => $nuptk, 'email' => $email] as $field => $value) {
                if ($value === '') {
                    continue;
                }
                if (isset($seen[$field][$value])) {
                    $errors[] = strtoupper($field).' duplikat di dalam file (baris '.$seen[$field][$value].').';
                } else {
                    $seen[$field][$value] = $source['row'];
                }
            }

            if ($status === 'PNS' || $status === 'PPPK') {
                $this->addDuplicateError($errors, 'nip', $nip, $tenantId);
            } elseif ($status === 'HONORER') {
                $this->addDuplicateError($errors, 'nik', $nik, $tenantId);
            }
            if ($nuptk !== '') {
                $this->addDuplicateError($errors, 'nuptk', $nuptk, $tenantId);
            }
            if ($email !== '') {
                $exists = User::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereRaw('LOWER(email) = ?', [$email])->exists();
                if ($exists) {
                    $errors[] = 'Email sudah digunakan oleh akun pada instalasi/madrasah ini.';
                }
            }

            $rows[] = [
                'row' => $source['row'],
                'data' => [
                    'name' => $name,
                    'employment_status' => $status,
                    'nip' => $nip !== '' ? $nip : null,
                    'nik' => $nik !== '' ? $nik : null,
                    'nuptk' => $nuptk !== '' ? $nuptk : null,
                    'employee_identifier' => in_array($status, ['PNS', 'PPPK'], true) ? $nip : ($status === 'HONORER' ? $nik : null),
                    'gender' => $gender === '__INVALID__' ? null : $gender,
                    'phone' => $phone !== '' ? $phone : null,
                    'email' => $email !== '' ? $email : null,
                ],
                'errors' => $errors,
            ];
            if ($errors === []) {
                $valid++;
            }
        }

        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris data per proses import.']);
        }
        if ($alreadyImported) {
            foreach ($rows as &$row) {
                $row['errors'][] = 'File ini sudah pernah berhasil diimpor (idempotency protection).';
            }
            unset($row);
            $valid = 0;
        }

        return [
            'rows' => $rows,
            'total' => count($rows),
            'valid' => $valid,
            'invalid' => count($rows) - $valid,
            'file_sha256' => $fileHash,
            'idempotency_key' => $idempotencyKey,
        ];
    }

    public function import(string $path, string $extension, string $tenantId, string $actorId): array
    {
        $actor = $this->assertAdminActor($tenantId, $actorId);
        $preview = $this->preview($path, $extension, $tenantId);
        if ($preview['total'] < 1 || $preview['invalid'] > 0) {
            throw ValidationException::withMessages(['file' => 'Import dibatalkan. Perbaiki seluruh baris bermasalah lalu buat preview kembali.']);
        }

        $context = app(TenantContext::class);
        $previousTenant = $context->getTenantId();
        $context->setTenantId($tenantId);

        try {
            return DB::transaction(function () use ($preview, $tenantId, $actor): array {
                $existing = DB::table('teacher_import_batches')
                    ->where('tenant_id', $tenantId)
                    ->where('idempotency_key', $preview['idempotency_key'])
                    ->lockForUpdate()
                    ->exists();
                if ($existing) {
                    throw ValidationException::withMessages(['file' => 'File ini sudah pernah berhasil diimpor. Proses ulang ditolak.']);
                }

                $batchId = (string) Str::uuid();
                DB::table('teacher_import_batches')->insert([
                    'id' => $batchId,
                    'tenant_id' => $tenantId,
                    'uploaded_by' => $actor->id,
                    'file_sha256' => $preview['file_sha256'],
                    'idempotency_key' => $preview['idempotency_key'],
                    'total_rows' => $preview['total'],
                    'imported_rows' => 0,
                    'status' => 'PROCESSING',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $role = Role::query()->where('name', 'GURU')->firstOrFail();
                foreach ($preview['rows'] as $row) {
                    $data = $row['data'];
                    $identityColumn = $data['employment_status'] === 'HONORER' ? 'nik' : 'nip';
                    $identityValue = $data[$identityColumn];

                    $duplicate = TeacherProfile::withoutGlobalScopes()
                        ->where('tenant_id', $tenantId)
                        ->where($identityColumn, $identityValue)
                        ->lockForUpdate()
                        ->exists();
                    if ($duplicate) {
                        throw ValidationException::withMessages([
                            'file' => "Identitas Guru pada baris {$row['row']} baru saja terdaftar. Seluruh batch dibatalkan.",
                        ]);
                    }

                    if ($data['nuptk'] !== null && TeacherProfile::withoutGlobalScopes()
                        ->where('tenant_id', $tenantId)->where('nuptk', $data['nuptk'])->lockForUpdate()->exists()) {
                        throw ValidationException::withMessages([
                            'file' => "NUPTK pada baris {$row['row']} baru saja terdaftar. Seluruh batch dibatalkan.",
                        ]);
                    }

                    $user = User::query()->create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'password' => Hash::make(Str::random(48)),
                    ]);
                    $user->roles()->sync([$role->id]);

                    TeacherProfile::query()->create([
                        'user_id' => $user->id,
                        'full_name' => $data['name'],
                        'employment_status' => $data['employment_status'],
                        'nip' => $data['nip'],
                        'nik' => $data['nik'],
                        'nuptk' => $data['nuptk'],
                        'employee_identifier' => $data['employee_identifier'],
                        'gender' => $data['gender'],
                        'phone' => $data['phone'],
                    ]);
                }

                DB::table('teacher_import_batches')->where('id', $batchId)->update([
                    'imported_rows' => $preview['total'],
                    'status' => 'COMPLETED',
                    'updated_at' => now(),
                ]);

                app(AuditService::class)->log('TEACHER_IMPORT_BATCH_COMPLETED', 'TeacherImportBatch', $batchId, null, [
                    'file_sha256' => $preview['file_sha256'],
                    'total_rows' => $preview['total'],
                    'imported_rows' => $preview['total'],
                ]);

                return ['batch_id' => $batchId, 'imported' => $preview['total']];
            }, 3);
        } finally {
            $context->setTenantId($previousTenant);
        }
    }

    private function addDuplicateError(array &$errors, string $column, string $value, string $tenantId): void
    {
        if ($value === '') {
            return;
        }
        $exists = TeacherProfile::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where($column, $value)
            ->exists();
        if ($exists) {
            $errors[] = strtoupper($column).' sudah terdaftar pada instalasi/madrasah ini.';
        }
    }

    private function assertAdminActor(string $tenantId, ?string $actorId = null): User
    {
        $authenticatedId = auth()->id();
        if (!$authenticatedId || ($actorId !== null && (string) $authenticatedId !== $actorId)) {
            throw new AuthorizationException('Aktor Import Guru tidak sesuai dengan sesi autentikasi.');
        }
        $actor = User::withoutGlobalScopes()->whereKey($authenticatedId)->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')->first();
        if (!$actor || !(bool) $actor->is_active || !$actor->hasExactRole('ADMIN_MADRASAH')
            || !$actor->hasPermission('user.manage')) {
            throw new AuthorizationException('Import Guru hanya dapat dilakukan Admin Madrasah aktif dengan permission user.manage.');
        }
        $contextTenantId = app(TenantContext::class)->getTenantId();
        if ($contextTenantId !== null && $contextTenantId !== $tenantId) {
            throw new AuthorizationException('Tenant context tidak cocok dengan Admin Madrasah yang terautentikasi.');
        }
        return $actor;
    }

    private function readRows(string $path, string $extension): array
    {
        $size = filesize($path);
        if (!is_int($size) || $size < 1 || $size > XlsxPackagePreflight::MAX_UPLOAD_BYTES) {
            throw ValidationException::withMessages(['file' => 'Ukuran file harus lebih dari 0 dan maksimal 10 MiB.']);
        }

        if (in_array($extension, ['csv', 'txt'], true)) {
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                throw ValidationException::withMessages(['file' => 'File CSV tidak dapat dibuka.']);
            }
            try {
                $first = fgets($handle, 262145);
                if ($first === false || strlen($first) > 262144) {
                    throw ValidationException::withMessages(['file' => 'Header CSV kosong atau terlalu besar.']);
                }
                $delimiter = ',';
                $counts = array_map(fn ($d) => count(str_getcsv($first, $d, '"', '')), [',', ';', "\t"]);
                $delimiter = [',', ';', "\t"][array_search(max($counts), $counts, true)];
                rewind($handle);
                $headers = fgetcsv($handle, 262145, $delimiter, '"', '');
                if (!is_array($headers)) {
                    throw ValidationException::withMessages(['file' => 'Header CSV tidak valid.']);
                }
                if (isset($headers[0])) {
                    $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? $headers[0];
                }
                $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $headers);
                if (count($headers) > self::MAX_COLUMNS) {
                    throw ValidationException::withMessages(['file' => 'Template Import Guru maksimal 8 kolom.']);
                }
                $rows = [];
                $line = 1;
                while (($values = fgetcsv($handle, 262145, $delimiter, '"', '')) !== false) {
                    $line++;
                    if ($this->isBlankRow($values)) {
                        continue;
                    }
                    if (count($values) !== count($headers)) {
                        throw ValidationException::withMessages(['file' => "Jumlah kolom CSV tidak konsisten pada baris {$line}."]);
                    }
                    foreach ($values as $value) {
                        if ($value !== null && (!is_string($value) || strlen($value) > self::MAX_CELL_BYTES
                            || !mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0"))) {
                            throw ValidationException::withMessages(['file' => "Isi cell CSV tidak valid atau melewati batas pada baris {$line}."]);
                        }
                    }
                    $rows[] = ['row' => $line, 'values' => $values];
                    if (count($rows) > self::MAX_ROWS) {
                        throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris data per proses import.']);
                    }
                }
                return [$headers, $rows];
            } finally {
                fclose($handle);
            }
        }

        if ($extension !== 'xlsx') {
            throw ValidationException::withMessages(['file' => 'Format file harus XLSX atau CSV.']);
        }
        $sheetName = app(XlsxPackagePreflight::class)->inspect($path);
        $spreadsheet = null;
        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly([$sheetName]);
            $reader->setReadFilter(new StudentImportReadFilter(self::MAX_ROWS + 1, XlsxPackagePreflight::MAX_COLUMNS));
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) {
                throw ValidationException::withMessages(['file' => 'Worksheet Data Guru tidak ditemukan.']);
            }
            $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            $lastRow = $sheet->getHighestDataRow();
            if ($lastRow > self::MAX_ROWS + 1 || $lastColumn > self::MAX_COLUMNS) {
                throw ValidationException::withMessages(['file' => 'Worksheet XLSX melewati batas 5.000 baris data atau 8 kolom.']);
            }
            $headers = [];
            for ($column = 1; $column <= $lastColumn; $column++) {
                $headers[] = $this->normalizeHeader((string) $sheet->getCell(Coordinate::stringFromColumnIndex($column).'1')->getValue());
            }
            $rows = [];
            for ($row = 2; $row <= $lastRow; $row++) {
                $values = [];
                for ($column = 1; $column <= $lastColumn; $column++) {
                    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column).$row);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        throw ValidationException::withMessages(['file' => 'Formula tidak diizinkan. Gunakan nilai tetap pada template Import Guru.']);
                    }
                    $value = $cell->getValue();
                    if (is_string($value) && strlen($value) > self::MAX_CELL_BYTES) {
                        throw ValidationException::withMessages(['file' => 'Isi cell XLSX melewati batas 4 KiB.']);
                    }
                    $values[] = $value;
                }
                $rows[] = ['row' => $row, 'values' => $values];
            }
            return [$headers, $rows];
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            throw ValidationException::withMessages(['file' => 'File XLSX tidak dapat dibaca atau rusak.']);
        } finally {
            $spreadsheet?->disconnectWorksheets();
        }
    }

    private function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-'], '_', $header);
        return preg_replace('/[^a-z0-9_]/', '', $header) ?? '';
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function isBlankRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }
}
