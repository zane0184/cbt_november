<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Import\StudentImportReadFilter;
use App\Services\Import\XlsxPackagePreflight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentImportService
{
    private const MAX_ROWS = 5000;

    private const MAX_COLUMNS = 32;

    private const MAX_CELLS = 160000;

    private const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;

    private const MAX_CSV_RECORD_BYTES = 256 * 1024;

    private const MAX_CELL_BYTES = 4096;

    private const OPTIONAL_HEADERS = [
        'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'nama_orang_tua', 'telepon', 'alamat',
    ];

    public function preview(string $path, string $extension, string $tenantId, string $rombelId): array
    {
        $rombel = $this->resolveRombel($tenantId, $rombelId);
        [$headers, $sourceRows] = $this->readRows($path, strtolower($extension));

        if (count(array_unique($headers)) !== count($headers)) {
            throw ValidationException::withMessages(['file' => 'Nama kolom tidak boleh duplikat.']);
        }

        foreach (['nama', 'nisn'] as $required) {
            if (!in_array($required, $headers, true)) {
                throw ValidationException::withMessages([
                    'file' => "Kolom wajib '{$required}' tidak ditemukan. Gunakan template Import Siswa.",
                ]);
            }
        }

        $fileHash = hash_file('sha256', $path);
        $idempotencyKey = $this->idempotencyKey($tenantId, $rombelId, $fileHash);
        $alreadyImported = DB::table('student_import_batches')
            ->where('tenant_id', $tenantId)
            ->where('idempotency_key', $idempotencyKey)
            ->exists();

        $seen = [];
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

            $errors = [];
            $name = $this->stringValue($data['nama'] ?? null);
            if ($name === '' || mb_strlen($name) > 255) {
                $errors[] = 'Nama wajib diisi dan maksimal 255 karakter.';
            }

            $nisnRaw = $data['nisn'] ?? null;
            if (!is_string($nisnRaw)) {
                $errors[] = 'NISN harus disimpan sebagai teks, bukan angka/number cell Excel.';
                $nisn = '';
            } else {
                $nisn = trim($nisnRaw);
                if (!preg_match('/^\d{10}$/', $nisn)) {
                    $errors[] = 'NISN wajib tepat 10 digit dan disimpan sebagai teks.';
                }
            }

            if ($nisn !== '' && preg_match('/^\d{10}$/', $nisn)) {
                if (isset($seen[$nisn])) {
                    $errors[] = 'NISN duplikat di dalam file (baris ' . $seen[$nisn] . ').';
                } else {
                    $seen[$nisn] = $source['row'];
                }

                $exists = DB::table('student_profiles')
                    ->where('tenant_id', $tenantId)
                    ->where('nisn', $nisn)
                    ->exists();
                if ($exists) {
                    $errors[] = 'NISN sudah terdaftar pada instalasi/madrasah ini.';
                }
            }

            $gender = strtoupper($this->stringValue($data['jenis_kelamin'] ?? null));
            if ($gender !== '') {
                $gender = match ($gender) {
                    'L', 'LAKI-LAKI', 'LAKI LAKI', 'M' => 'M',
                    'P', 'PEREMPUAN', 'F' => 'F',
                    default => '',
                };
                if ($gender === '') {
                    $errors[] = 'Jenis kelamin harus L atau P.';
                }
            }

            $birthDate = $this->stringValue($data['tanggal_lahir'] ?? null);
            if ($birthDate !== '' && !$this->isIsoDate($birthDate)) {
                $errors[] = 'Tanggal lahir harus berformat YYYY-MM-DD.';
            }

            $optional = [];
            foreach (self::OPTIONAL_HEADERS as $header) {
                $optional[$header] = $this->stringValue($data[$header] ?? null);
            }

            foreach (['tempat_lahir' => 100, 'nama_orang_tua' => 255, 'telepon' => 40, 'alamat' => 2000] as $field => $limit) {
                if (mb_strlen($optional[$field]) > $limit) {
                    $errors[] = "Kolom {$field} melebihi {$limit} karakter.";
                }
            }

            $rows[] = [
                'row' => $source['row'],
                'data' => [
                    'name' => $name,
                    'nisn' => $nisn,
                    'gender' => $gender !== '' ? $gender : null,
                    'birth_place' => $optional['tempat_lahir'] ?: null,
                    'birth_date' => $birthDate ?: null,
                    'parent_name' => $optional['nama_orang_tua'] ?: null,
                    'phone' => $optional['telepon'] ?: null,
                    'address' => $optional['alamat'] ?: null,
                ],
                'errors' => $errors,
            ];

            if ($errors === []) {
                $valid++;
            }
        }

        if (count($rows) > self::MAX_ROWS) {
            throw ValidationException::withMessages([
                'file' => 'Maksimal 5.000 baris data per proses import.',
            ]);
        }

        if ($alreadyImported) {
            foreach ($rows as &$row) {
                $row['errors'][] = 'File dan Rombel ini sudah pernah berhasil diimpor (idempotency protection).';
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
            'rombel_name' => $rombel->name,
        ];
    }

    public function import(array $preview, string $tenantId, string $actorId, string $rombelId): array
    {
        if (($preview['total'] ?? 0) < 1 || ($preview['invalid'] ?? 0) > 0) {
            throw ValidationException::withMessages([
                'file' => 'Import dibatalkan. Perbaiki seluruh baris yang memiliki error lalu buat preview kembali.',
            ]);
        }

        $context = app(TenantContext::class);
        $previousTenant = $context->getTenantId();
        $context->setTenantId($tenantId);

        try {
            return DB::transaction(function () use ($preview, $tenantId, $actorId, $rombelId): array {
                $rombel = $this->resolveRombel($tenantId, $rombelId, true);
                $idempotencyKey = $preview['idempotency_key'];

                $alreadyImported = DB::table('student_import_batches')
                    ->where('tenant_id', $tenantId)
                    ->where('idempotency_key', $idempotencyKey)
                    ->lockForUpdate()
                    ->exists();

                if ($alreadyImported) {
                    throw ValidationException::withMessages([
                        'file' => 'File ini sudah pernah berhasil diimpor. Proses ulang ditolak.',
                    ]);
                }

                $batchId = (string) Str::uuid();
                DB::table('student_import_batches')->insert([
                    'id' => $batchId,
                    'tenant_id' => $tenantId,
                    'rombel_id' => $rombelId,
                    'uploaded_by' => $actorId,
                    'file_sha256' => $preview['file_sha256'],
                    'idempotency_key' => $idempotencyKey,
                    'total_rows' => $preview['total'],
                    'imported_rows' => 0,
                    'status' => 'PROCESSING',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $studentRole = Role::query()->where('name', 'SISWA')->firstOrFail();
                $imported = 0;

                foreach ($preview['rows'] as $row) {
                    $data = $row['data'];
                    $duplicate = DB::table('student_profiles')
                        ->where('tenant_id', $tenantId)
                        ->where('nisn', $data['nisn'])
                        ->lockForUpdate()
                        ->exists();

                    if ($duplicate) {
                        throw ValidationException::withMessages([
                            'file' => "NISN pada baris {$row['row']} baru saja terdaftar. Tidak ada data batch yang disimpan.",
                        ]);
                    }

                    $user = User::query()->create([
                        'name' => $data['name'],
                        'password' => Hash::make(Str::random(48)),
                    ]);
                    $user->roles()->sync([$studentRole->id]);

                    $profile = StudentProfile::query()->create([
                        'user_id' => $user->id,
                        'nisn' => $data['nisn'],
                        'full_name' => $data['name'],
                        'gender' => $data['gender'],
                        'birth_place' => $data['birth_place'],
                        'birth_date' => $data['birth_date'],
                        'parent_name' => $data['parent_name'],
                        'phone' => $data['phone'],
                        'address' => $data['address'],
                    ]);

                    $rombel->students()->attach($profile->id);
                    $imported++;
                }

                DB::table('student_import_batches')
                    ->where('id', $batchId)
                    ->update([
                        'imported_rows' => $imported,
                        'status' => 'COMPLETED',
                        'updated_at' => now(),
                    ]);

                app(AuditService::class)->log(
                    'STUDENT_IMPORT_BATCH_COMPLETED',
                    'StudentImportBatch',
                    $batchId,
                    null,
                    [
                        'rombel_id' => $rombelId,
                        'file_sha256' => $preview['file_sha256'],
                        'total_rows' => $preview['total'],
                        'imported_rows' => $imported,
                    ]
                );

                return ['batch_id' => $batchId, 'imported' => $imported];
            }, 3);
        } finally {
            $context->setTenantId($previousTenant);
        }
    }

    private function resolveRombel(string $tenantId, string $rombelId, bool $lock = false): Rombel
    {
        $query = Rombel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->whereNull('deleted_at')
            ->whereKey($rombelId);

        if ($lock) {
            $query->lockForUpdate();
        }

        $rombel = $query->first();
        if (!$rombel
            || !DB::table('academic_years')->where('id', $rombel->academic_year_id)->where('tenant_id', $tenantId)->exists()
            || !DB::table('grade_levels')->where('id', $rombel->grade_level_id)->where('tenant_id', $tenantId)->exists()) {
            throw ValidationException::withMessages([
                'selectedRombelId' => 'Rombel tidak aktif atau tidak berada dalam instalasi/madrasah ini.',
            ]);
        }

        return $rombel;
    }

    private function readRows(string $path, string $extension): array
    {
        $this->assertRuntimeLimits();
        $this->assertUploadEnvelope($path, $extension);

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsv($path);
        }

        if ($extension !== 'xlsx') {
            throw ValidationException::withMessages(['file' => 'Format file harus XLSX atau CSV.']);
        }

        $spreadsheet = null;
        try {
            // Package and worksheet checks must complete before PhpSpreadsheet reads the workbook.
            $sheetName = app(XlsxPackagePreflight::class)->inspect($path);
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $reader->setLoadSheetsOnly([$sheetName]);
            $reader->setReadFilter(new StudentImportReadFilter(self::MAX_ROWS + 1, self::MAX_COLUMNS));

            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if ($sheet === null) {
                throw ValidationException::withMessages(['file' => 'Worksheet Import Siswa tidak ditemukan.']);
            }

            $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            $lastRow = $sheet->getHighestDataRow();

            // Defense in depth after the pre-load package/dimension gate.
            if ($lastRow > self::MAX_ROWS + 1) {
                throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris data per proses import.']);
            }
            if ($lastColumn > self::MAX_COLUMNS) {
                throw ValidationException::withMessages(['file' => 'Maksimal 32 kolom per proses import.']);
            }

            $headers = [];
            for ($column = 1; $column <= $lastColumn; $column++) {
                $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column) . '1');
                $headers[] = $this->normalizeHeader((string) $cell->getValue());
            }

            $rows = [];
            for ($row = 2; $row <= $lastRow; $row++) {
                $values = [];
                for ($column = 1; $column <= $lastColumn; $column++) {
                    $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($column) . $row);
                    if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                        throw ValidationException::withMessages([
                            'file' => 'Formula tidak diizinkan pada file Import Siswa; gunakan nilai tetap.',
                        ]);
                    }
                    $value = $cell->getValue();
                    if (is_string($value) && strlen($value) > self::MAX_CELL_BYTES) {
                        throw ValidationException::withMessages(['file' => 'Isi satu cell XLSX melewati batas 4 KiB.']);
                    }
                    $values[] = $value;
                }
                $rows[] = ['row' => $row, 'values' => $values];
            }

            return [$headers, $rows];
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'file' => 'File XLSX tidak dapat dibaca. Pastikan file tidak rusak dan bukan hasil ekspor yang dilindungi.',
            ]);
        } finally {
            if ($spreadsheet !== null) {
                $spreadsheet->disconnectWorksheets();
            }
        }
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'File CSV tidak dapat dibuka.']);
        }

        try {
            $firstLine = fgets($handle, self::MAX_CSV_RECORD_BYTES + 1);
            if ($firstLine === false || strlen($firstLine) > self::MAX_CSV_RECORD_BYTES || (!str_ends_with($firstLine, "\n") && !feof($handle))) {
                throw ValidationException::withMessages(['file' => 'Header CSV kosong atau melebihi batas ukuran record.']);
            }

            $delimiters = [',', ';', "\t"];
            $delimiter = ',';
            $highestCount = -1;
            foreach ($delimiters as $candidate) {
                $count = substr_count($firstLine, $candidate);
                if ($count > $highestCount) {
                    $highestCount = $count;
                    $delimiter = $candidate;
                }
            }

            rewind($handle);
            $headerRow = $this->readCsvRecord($handle, $delimiter);
            if (!is_array($headerRow)) {
                throw ValidationException::withMessages(['file' => 'Header CSV tidak dapat dibaca.']);
            }

            if (isset($headerRow[0]) && is_string($headerRow[0])) {
                $headerRow[0] = preg_replace('/^\\xEF\\xBB\\xBF/', '', $headerRow[0]) ?? $headerRow[0];
            }

            $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $headerRow);
            $rows = [];
            $recordNumber = 1;
            $cellCount = count($headerRow);

            while (($values = $this->readCsvRecord($handle, $delimiter)) !== false) {
                $recordNumber++;
                $cellCount += count($values);
                if (count($values) !== count($headers)) {
                    throw ValidationException::withMessages(['file' => 'Jumlah kolom setiap record CSV harus sama dengan header.']);
                }
                if ($recordNumber > self::MAX_ROWS + 1) {
                    throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris data per proses import.']);
                }
                if ($cellCount > self::MAX_CELLS) {
                    throw ValidationException::withMessages(['file' => 'Jumlah cell CSV melewati batas keamanan.']);
                }

                $rows[] = ['row' => $recordNumber, 'values' => $values];
            }

            return [$headers, $rows];
        } finally {
            fclose($handle);
        }
    }

    private function readCsvRecord($handle, string $delimiter): array|false
    {
        $start = ftell($handle);
        $values = fgetcsv($handle, self::MAX_CSV_RECORD_BYTES + 1, $delimiter, '"', '');
        $end = ftell($handle);

        if ($values === false) {
            if (!feof($handle)) {
                throw ValidationException::withMessages(['file' => 'Record CSV malformed atau tidak dapat dibaca.']);
            }

            return false;
        }

        if (!is_int($start) || !is_int($end) || ($end - $start) > self::MAX_CSV_RECORD_BYTES) {
            throw ValidationException::withMessages(['file' => 'Satu record CSV melewati batas 256 KiB.']);
        }
        if (count($values) > self::MAX_COLUMNS) {
            throw ValidationException::withMessages(['file' => 'CSV maksimal 32 kolom per record.']);
        }

        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }
            if (!is_string($value) || strlen($value) > self::MAX_CELL_BYTES) {
                throw ValidationException::withMessages(['file' => 'Isi satu cell CSV melewati batas 4 KiB.']);
            }
            if (!mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
                throw ValidationException::withMessages(['file' => 'CSV harus berisi teks UTF-8 valid tanpa NUL byte.']);
            }
        }

        return $values;
    }

    private function assertUploadEnvelope(string $path, string $extension): void
    {
        $size = filesize($path);
        if (!is_int($size) || $size < 1 || $size > self::MAX_UPLOAD_BYTES) {
            throw ValidationException::withMessages(['file' => 'Ukuran file harus lebih dari 0 dan maksimal 10 MiB.']);
        }

        if (in_array($extension, ['csv', 'txt'], true)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
            $allowed = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream'];
            if (!is_string($mime) || !in_array($mime, $allowed, true)) {
                throw ValidationException::withMessages(['file' => 'Konten file tidak dikenali sebagai CSV/text.']);
            }
        }
    }

    private function assertRuntimeLimits(): void
    {
        $memoryLimit = $this->memoryLimitBytes((string) ini_get('memory_limit'));
        if ($memoryLimit !== null && $memoryLimit < (256 * 1024 * 1024)) {
            throw ValidationException::withMessages([
                'file' => 'Import membutuhkan memory_limit PHP minimal 256M. Hubungi Admin teknis.',
            ]);
        }

        $currentExecutionLimit = (int) ini_get('max_execution_time');
        if ($currentExecutionLimit === 0 || $currentExecutionLimit > 30) {
            $changed = @set_time_limit(30);
            if (!$changed && (int) ini_get('max_execution_time') > 30) {
                throw ValidationException::withMessages([
                    'file' => 'Batas waktu parsing 30 detik tidak dapat diterapkan pada runtime PHP.',
                ]);
            }
        }
    }

    private function memoryLimitBytes(string $limit): ?int
    {
        $limit = trim($limit);
        if ($limit === '' || $limit === '-1') {
            return null;
        }

        if (!preg_match('/^(\\d+)\\s*([KMG]?)$/i', $limit, $matches)) {
            return null;
        }

        $value = (int) $matches[1];
        $unit = strtoupper($matches[2] ?? '');
        return match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => $value,
        };
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

    private function isIsoDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function idempotencyKey(string $tenantId, string $rombelId, string $fileHash): string
    {
        return hash('sha256', 'student-import-v1|' . $tenantId . '|' . $rombelId . '|' . $fileHash);
    }
}
