<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Models\User;
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
    private const MAX_UPLOAD_BYTES = 10 * 1024 * 1024;
    private const MAX_CSV_COLUMNS = 32;
    private const MAX_CSV_RECORD_BYTES = 65536;
    private const MAX_ZIP_ENTRIES = 256;
    private const MAX_ZIP_ENTRY_BYTES = 24 * 1024 * 1024;
    private const MAX_ZIP_TOTAL_EXPANDED_BYTES = 32 * 1024 * 1024;
    private const MAX_ZIP_COMPRESSION_RATIO = 100;
    private const MAX_XML_ENTRY_BYTES = 24 * 1024 * 1024;

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
        $this->assertUploadFileIsBounded($path);

        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsv($path);
        }

        if ($extension !== 'xlsx') {
            throw ValidationException::withMessages(['file' => 'Format file harus XLSX atau CSV.']);
        }

        // ZIP/OOXML resource and XML checks must run before PhpSpreadsheet opens the workbook.
        $this->preflightXlsx($path);

        try {
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            $sheetNames = $reader->listWorksheetNames($path);
            if (!isset($sheetNames[0]) || !is_string($sheetNames[0]) || $sheetNames[0] === '') {
                throw ValidationException::withMessages(['file' => 'Workbook XLSX tidak memiliki worksheet yang dapat dibaca.']);
            }
            $reader->setLoadSheetsOnly([$sheetNames[0]]);
            $spreadsheet = $reader->load($path);
            $sheet = $spreadsheet->getSheet(0);
            $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            $lastRow = $sheet->getHighestDataRow();

            if ($lastRow > self::MAX_ROWS + 1) {
                throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris data per proses import.']);
            }
            if ($lastColumn > self::MAX_CSV_COLUMNS) {
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
                        $values[] = null;
                    } else {
                        $values[] = $cell->getValue();
                    }
                }
                $rows[] = ['row' => $row, 'values' => $values];
            }

            $spreadsheet->disconnectWorksheets();

            return [$headers, $rows];
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'file' => 'File XLSX tidak dapat dibaca. Pastikan file tidak rusak dan bukan hasil ekspor yang dilindungi.',
            ]);
        }
    }

    private function assertUploadFileIsBounded(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw ValidationException::withMessages(['file' => 'File import tidak tersedia atau tidak dapat dibaca.']);
        }

        $size = filesize($path);
        if ($size === false || $size < 1 || $size > self::MAX_UPLOAD_BYTES) {
            throw ValidationException::withMessages(['file' => 'Ukuran file import harus lebih dari 0 dan maksimal 10 MB.']);
        }
    }

    private function preflightXlsx(string $path): void
    {
        if (!class_exists(\ZipArchive::class)) {
            throw ValidationException::withMessages(['file' => 'Pemeriksaan keamanan XLSX tidak tersedia pada server.']);
        }

        $zip = new \ZipArchive();
        $opened = $zip->open($path, \ZipArchive::CHECKCONS);
        if ($opened !== true) {
            throw ValidationException::withMessages(['file' => 'File XLSX bukan paket OOXML yang valid atau arsipnya rusak.']);
        }

        try {
            if ($zip->numFiles < 3 || $zip->numFiles > self::MAX_ZIP_ENTRIES) {
                throw ValidationException::withMessages(['file' => 'Struktur XLSX memiliki jumlah entri ZIP yang tidak didukung.']);
            }

            $seenNames = [];
            $totalExpandedBytes = 0;
            $hasContentTypes = false;
            $hasWorkbook = false;
            $hasWorksheet = false;

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $stat = $zip->statIndex($index);
                if (!is_array($stat)
                    || !isset($stat['name'], $stat['size'], $stat['comp_size'], $stat['comp_method'])) {
                    throw ValidationException::withMessages(['file' => 'Metadata entri XLSX tidak valid.']);
                }

                $name = (string) $stat['name'];
                if ($name === ''
                    || str_contains($name, "\0")
                    || str_contains($name, '\\')
                    || str_starts_with($name, '/')
                    || preg_match('/^[A-Za-z]:/', $name) === 1) {
                    throw ValidationException::withMessages(['file' => 'Path entri XLSX tidak aman.']);
                }

                $segments = explode('/', rtrim($name, '/'));
                foreach ($segments as $segment) {
                    if ($segment === '' || $segment === '.' || $segment === '..') {
                        throw ValidationException::withMessages(['file' => 'Path traversal pada entri XLSX ditolak.']);
                    }
                }

                $normalizedName = strtolower($name);
                if (isset($seenNames[$normalizedName])) {
                    throw ValidationException::withMessages(['file' => 'Nama entri XLSX duplikat tidak diizinkan.']);
                }
                $seenNames[$normalizedName] = true;

                if (preg_match('#^xl/(externalLinks|embeddings|activeX)/#i', $name) === 1
                    || preg_match('#^xl/vbaProject\.bin$#i', $name) === 1) {
                    throw ValidationException::withMessages(['file' => 'External link, macro, atau embedded object pada XLSX tidak diizinkan.']);
                }

                $size = (int) $stat['size'];
                $compressedSize = (int) $stat['comp_size'];
                $compressionMethod = (int) $stat['comp_method'];

                if ($size < 0
                    || $compressedSize < 0
                    || $size > self::MAX_ZIP_ENTRY_BYTES
                    || !in_array($compressionMethod, [\ZipArchive::CM_STORE, \ZipArchive::CM_DEFLATE], true)) {
                    throw ValidationException::withMessages(['file' => 'Ukuran atau metode kompresi entri XLSX tidak didukung.']);
                }

                $totalExpandedBytes += $size;
                if ($totalExpandedBytes > self::MAX_ZIP_TOTAL_EXPANDED_BYTES) {
                    throw ValidationException::withMessages(['file' => 'Ukuran hasil ekstraksi XLSX melebihi batas keamanan.']);
                }

                if ($size > 0
                    && ($compressedSize < 1 || ($size / max(1, $compressedSize)) > self::MAX_ZIP_COMPRESSION_RATIO)) {
                    throw ValidationException::withMessages(['file' => 'Compression ratio XLSX melebihi batas keamanan.']);
                }

                $lowerName = strtolower($name);
                if ($lowerName === '[content_types].xml') {
                    $hasContentTypes = true;
                }
                if ($lowerName === 'xl/workbook.xml') {
                    $hasWorkbook = true;
                }
                if (preg_match('#^xl/worksheets/[^/]+\.xml$#i', $name) === 1) {
                    $hasWorksheet = true;
                }

                if (preg_match('/\.(xml|rels)$/i', $name) === 1) {
                    if ($size > self::MAX_XML_ENTRY_BYTES) {
                        throw ValidationException::withMessages(['file' => 'Ukuran XML XLSX melebihi batas keamanan.']);
                    }

                    $xml = $zip->getFromIndex($index);
                    if (!is_string($xml) || strlen($xml) !== $size) {
                        throw ValidationException::withMessages(['file' => 'Entri XML XLSX tidak dapat dibaca secara utuh.']);
                    }

                    if (preg_match('/<!\s*(DOCTYPE|ENTITY)\b/i', $xml) === 1) {
                        throw ValidationException::withMessages(['file' => 'DTD atau XML entity pada XLSX tidak diizinkan.']);
                    }

                    if (str_ends_with($lowerName, '.rels')
                        && preg_match('/\bTargetMode\s*=\s*["\']External["\']/i', $xml) === 1) {
                        throw ValidationException::withMessages(['file' => 'External relationship pada XLSX tidak diizinkan.']);
                    }

                    if (preg_match('#^xl/worksheets/[^/]+\.xml$#i', $name) === 1) {
                        $this->assertWorksheetDimensions($xml);
                    }

                    unset($xml);
                }
            }

            if (!$hasContentTypes || !$hasWorkbook || !$hasWorksheet) {
                throw ValidationException::withMessages(['file' => 'Paket XLSX tidak memiliki bagian OOXML wajib.']);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'Pemeriksaan keamanan XLSX gagal.']);
        } finally {
            $zip->close();
        }
    }

    private function assertWorksheetDimensions(string $xml): void
    {
        $maxColumn = 0;
        $maxRow = 0;

        if (preg_match('/<dimension\b[^>]*\bref\s*=\s*["\']([^"\']+)["\']/i', $xml, $dimension) === 1) {
            $referenceParts = explode(':', trim($dimension[1]));
            $lastCell = trim($referenceParts[count($referenceParts) - 1]);

            if (preg_match('/^\$?([A-Z]{1,3})\$?(\d+)$/i', $lastCell, $coordinate) !== 1) {
                throw ValidationException::withMessages(['file' => 'Worksheet XLSX memiliki dimension reference tidak valid.']);
            }

            $maxColumn = Coordinate::columnIndexFromString(strtoupper($coordinate[1]));
            $maxRow = (int) $coordinate[2];
        }

        // Verify actual cell references even when the dimension attribute exists.
        // A forged small dimension must not hide cells placed outside import limits.
        $cellPattern = '/<c\b[^>]*\br\s*=\s*["\']\$?([A-Z]{1,3})\$?(\d+)["\']/i';
        $cellOffset = 0;
        $cellCount = 0;
        $xmlLength = strlen($xml);

        // Scan one reference at a time to avoid allocating a large PREG_SET_ORDER result.
        while ($cellOffset < $xmlLength
            && preg_match($cellPattern, $xml, $cell, PREG_OFFSET_CAPTURE, $cellOffset) === 1) {
            $cellCount++;
            if ($cellCount > self::MAX_ROWS * self::MAX_CSV_COLUMNS) {
                throw ValidationException::withMessages(['file' => 'Worksheet XLSX memiliki terlalu banyak cell.']);
            }

            $maxColumn = max($maxColumn, Coordinate::columnIndexFromString(strtoupper($cell[1][0])));
            $maxRow = max($maxRow, (int) $cell[2][0]);
            $cellOffset = $cell[0][1] + strlen($cell[0][0]);
        }

        if ($maxRow > self::MAX_ROWS + 1) {
            throw ValidationException::withMessages(['file' => 'Maksimal 5.000 baris data per proses import.']);
        }
        if ($maxColumn > self::MAX_CSV_COLUMNS) {
            throw ValidationException::withMessages(['file' => 'Maksimal 32 kolom per proses import.']);
        }
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'File CSV tidak dapat dibuka.']);
        }

        try {
            $firstLine = fgets($handle, self::MAX_CSV_RECORD_BYTES + 2);
            if ($firstLine === false) {
                throw ValidationException::withMessages(['file' => 'File CSV kosong.']);
            }
            if (strlen($firstLine) > self::MAX_CSV_RECORD_BYTES) {
                throw ValidationException::withMessages(['file' => 'Panjang record CSV melebihi batas keamanan.']);
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
            $headerRow = fgetcsv($handle, self::MAX_CSV_RECORD_BYTES + 2, $delimiter, '"', '');
            if (!is_array($headerRow)) {
                throw ValidationException::withMessages(['file' => 'Header CSV tidak dapat dibaca.']);
            }

            if (isset($headerRow[0]) && is_string($headerRow[0])) {
                $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headerRow[0]) ?? $headerRow[0];
            }

            $this->assertCsvRecordBounds($headerRow, null, 'header');
            if (count($headerRow) > self::MAX_CSV_COLUMNS) {
                throw ValidationException::withMessages(['file' => 'Maksimal 32 kolom per proses import.']);
            }

            $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $headerRow);
            $rows = [];
            $line = 1;

            while (($values = fgetcsv($handle, self::MAX_CSV_RECORD_BYTES + 1, $delimiter, '"', '')) !== false) {
                $line++;
                if ($this->isBlankRow($values)) {
                    continue;
                }

                $this->assertCsvRecordBounds($values, count($headers), "baris {$line}");
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

    private function assertCsvRecordBounds(array $values, ?int $expectedColumns, string $recordLabel): void
    {
        if (count($values) > self::MAX_CSV_COLUMNS
            || ($expectedColumns !== null && count($values) !== $expectedColumns)) {
            throw ValidationException::withMessages(['file' => "Jumlah kolom CSV pada {$recordLabel} tidak konsisten atau melebihi batas."]);
        }

        $recordBytes = max(0, count($values) - 1);
        foreach ($values as $value) {
            if ($value !== null && !is_scalar($value)) {
                throw ValidationException::withMessages(['file' => "Record CSV pada {$recordLabel} tidak valid."]);
            }
            $recordBytes += strlen((string) ($value ?? ''));
            if ($recordBytes > self::MAX_CSV_RECORD_BYTES) {
                throw ValidationException::withMessages(['file' => "Panjang record CSV pada {$recordLabel} melebihi batas keamanan."]);
            }
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
