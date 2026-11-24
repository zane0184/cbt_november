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

    private const OPTIONAL_HEADERS = [
        'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
        'nama_orang_tua', 'telepon', 'alamat',
    ];

    public function preview(string $path, string $extension, string $tenantId, string $rombelId): array
    {
        $rombel = $this->resolveRombel($tenantId, $rombelId);
        [$headers, $sourceRows] = $this->readRows($path, strtolower($extension));

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
        if (in_array($extension, ['csv', 'txt'], true)) {
            return $this->readCsv($path);
        }

        if ($extension !== 'xlsx') {
            throw ValidationException::withMessages(['file' => 'Format file harus XLSX atau CSV.']);
        }

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getSheet(0);
            $lastColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
            $lastRow = $sheet->getHighestDataRow();
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
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'file' => 'File XLSX tidak dapat dibaca. Pastikan file tidak rusak dan bukan hasil ekspor yang dilindungi.',
            ]);
        }
    }

    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'File CSV tidak dapat dibuka.']);
        }

        try {
            $headerRow = fgetcsv($handle);
            if (!is_array($headerRow)) {
                throw ValidationException::withMessages(['file' => 'File CSV kosong.']);
            }

            if (isset($headerRow[0]) && is_string($headerRow[0])) {
                $headerRow[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headerRow[0]) ?? $headerRow[0];
            }

            $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $headerRow);
            $rows = [];
            $line = 1;

            while (($values = fgetcsv($handle)) !== false) {
                $line++;
                $rows[] = ['row' => $line, 'values' => $values];
            }

            return [$headers, $rows];
        } finally {
            fclose($handle);
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
