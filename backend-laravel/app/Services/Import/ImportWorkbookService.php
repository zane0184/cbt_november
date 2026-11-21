<?php

namespace App\Services\Import;

use App\Exports\Import\ImportTemplateVersion;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ImportActivationToken;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\Rombel;
use App\Models\Role;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class ImportWorkbookService
{
    private const GURU_HEADERS = ['Kategori Kepegawaian*', 'NIP* / kosongkan jika honorer', 'NIK* / kosongkan jika PNS', 'NUPTK (opsional)', 'Nama Lengkap*', 'Jenis Kelamin (L/P)', 'Email (opsional)', 'No. HP (opsional)'];
    private const SISWA_HEADERS = ['NIS (opsional)', 'NISN*', 'Nama Lengkap*', 'Jenis Kelamin (L/P)', 'Tempat Lahir', 'Tanggal Lahir (YYYY-MM-DD)', 'Nama Orang Tua/Wali', 'No. HP Orang Tua/Wali', 'Alamat', 'Rombel*', 'Tahun Pelajaran*'];
    private const ASSIGNMENT_HEADERS = ['Identitas Guru (NIP/NIK)*', 'Mata Pelajaran*', 'Rombel*', 'Tahun Pelajaran*', 'Semester*', 'Tanggal Mulai (YYYY-MM-DD)', 'Tanggal Selesai (YYYY-MM-DD)', 'Status (ACTIVE/INACTIVE)'];

    public function preview(UploadedFile $file, string $type, string $tenantId, string $userId): ImportBatch
    {
        $this->assertAdminActor($tenantId, $userId);
        $type = strtoupper($type);
        if (!in_array($type, ['GURU', 'SISWA'], true)) throw new RuntimeException('Jenis import tidak dikenal.');
        $sha = hash_file('sha256', $file->getRealPath());
        $existing = ImportBatch::query()->where('tenant_id', $tenantId)->where('type', $type)->where('file_sha256', $sha)->first();
        if ($existing) return $existing;

        $analysis = $this->analyze($file->getRealPath(), $type, $tenantId);
        if (!$analysis) throw new RuntimeException('Workbook tidak memiliki baris data untuk diimpor.');
        return DB::transaction(function () use ($analysis, $type, $tenantId, $userId, $sha, $file): ImportBatch {
            Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $duplicate = ImportBatch::query()->where('tenant_id', $tenantId)->where('type', $type)->where('file_sha256', $sha)->lockForUpdate()->first();
            if ($duplicate) return $duplicate;

            $batch = ImportBatch::create([
                'created_by' => $userId,
                'type' => $type,
                'template_version' => ImportTemplateVersion::VERSION,
                'file_sha256' => $sha,
                'original_filename' => substr($file->getClientOriginalName(), 0, 255),
                'status' => 'PREVIEW',
                'total_rows' => count($analysis),
                'valid_rows' => collect($analysis)->where('status', 'VALID')->count(),
                'skipped_rows' => collect($analysis)->where('status', 'SKIPPED')->count(),
                'failed_rows' => collect($analysis)->where('status', 'INVALID')->count(),
            ]);
            foreach ($analysis as $row) {
                ImportBatchRow::create([
                    'batch_id' => $batch->id,
                    'sheet_name' => $row['sheet'],
                    'row_number' => $row['row'],
                    'status' => $row['status'],
                    'reason' => $row['reason'],
                    'row_summary' => $row['summary'],
                    'encrypted_payload' => Crypt::encryptString(json_encode($row['payload'], JSON_THROW_ON_ERROR)),
                ]);
            }
            app(AuditService::class)->log('IMPORT_PREVIEW_CREATED', 'ImportBatch', $batch->id, null, [
                'type' => $type, 'total_rows' => $batch->total_rows, 'valid_rows' => $batch->valid_rows,
                'skipped_rows' => $batch->skipped_rows, 'failed_rows' => $batch->failed_rows,
                'file_sha256' => $sha,
            ]);
            return $batch->fresh('rows');
        }, 3);
    }

    public function confirm(string $batchId, string $tenantId, string $userId): array
    {
        $this->assertAdminActor($tenantId, $userId);
        return DB::transaction(function () use ($batchId, $tenantId, $userId): array {
            // Serialize all import confirmations for one tenant. This prevents two batches
            // from concurrently creating the same legacy identity without adding constraints
            // to the existing identity columns.
            Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $batch = ImportBatch::query()->where('tenant_id', $tenantId)->whereKey($batchId)->lockForUpdate()->firstOrFail();
            if ($batch->status !== 'PREVIEW') throw new RuntimeException('Batch ini tidak lagi dapat dikonfirmasi.');
            if ((string) $batch->created_by !== $userId) throw new RuntimeException('Hanya Admin pembuat preview yang dapat mengonfirmasi batch ini.');

            $batch->update(['status' => 'PROCESSING', 'confirmed_at' => now()]);
            $activationLinks = [];
            $rows = ImportBatchRow::query()->where('tenant_id', $tenantId)->where('batch_id', $batch->id)
                ->where('status', 'VALID')->orderByRaw("CASE sheet_name WHEN 'Data Guru' THEN 1 WHEN 'Data Siswa' THEN 1 ELSE 2 END")
                ->orderBy('row_number')->get();

            foreach ($rows as $row) {
                try {
                    $payload = json_decode(Crypt::decryptString($row->encrypted_payload), true, 512, JSON_THROW_ON_ERROR);
                    $result = DB::transaction(function () use ($batch, $row, $payload, $tenantId, $userId): array {
                        if ($row->sheet_name === 'Data Guru') return $this->importTeacher($payload, $tenantId, $userId);
                        if ($row->sheet_name === 'Data Siswa') return $this->importStudent($payload, $tenantId, $userId);
                        return $this->importAssignment($payload, $tenantId, $userId);
                    }, 3);

                    $summary = $row->row_summary ?? [];
                    if (!empty($result['user_id'])) $summary['created_user_id'] = $result['user_id'];
                    $row->update(['status' => $result['status'], 'reason' => $result['reason'], 'encrypted_payload' => null, 'row_summary' => $summary]);
                    if (!empty($result['activation_url'])) {
                        $activationLinks[] = [
                            'name' => $result['name'],
                            'identity' => $result['identity'],
                            'url' => $result['activation_url'],
                        ];
                    }
                } catch (Throwable $exception) {
                    Log::warning('Import row failed.', [
                        'batch_id' => $batch->id,
                        'row_id' => $row->id,
                        'exception_class' => get_class($exception),
                    ]);
                    $safeReason = $exception instanceof QueryException
                        ? 'Gagal menyimpan baris karena konflik integritas data atau penyimpanan.'
                        : mb_substr('Gagal menyimpan baris: '.$exception->getMessage(), 0, 500);
                    $row->update([
                        'status' => 'FAILED',
                        'reason' => $safeReason,
                        'encrypted_payload' => null,
                    ]);
                }
            }

            // Remove encrypted staging payloads for every row after execution. The report keeps only masked identity hints and outcomes.
            ImportBatchRow::query()->where('tenant_id', $tenantId)->where('batch_id', $batch->id)->update(['encrypted_payload' => null]);
            $batch->refresh();
            $counts = ImportBatchRow::query()->where('tenant_id', $tenantId)->where('batch_id', $batch->id)
                ->selectRaw("SUM(status IN ('IMPORTED','SUCCESS')) as imported_count")
                ->selectRaw("SUM(status = 'SKIPPED') as skipped_count")
                ->selectRaw("SUM(status IN ('INVALID','FAILED')) as failed_count")
                ->first();
            $batch->update([
                'status' => ((int) $counts->failed_count > 0 ? 'PARTIAL' : 'COMPLETED'),
                'valid_rows' => (int) $counts->imported_count,
                'skipped_rows' => (int) $counts->skipped_count,
                'failed_rows' => (int) $counts->failed_count,
                'completed_at' => now(),
            ]);

            app(AuditService::class)->log('IMPORT_BATCH_CONFIRMED', 'ImportBatch', $batch->id, null, [
                'type' => $batch->type,
                'imported_rows' => (int) $counts->imported_count,
                'skipped_rows' => (int) $counts->skipped_count,
                'failed_rows' => (int) $counts->failed_count,
                'activation_links_issued' => count($activationLinks),
            ]);

            return ['batch' => $batch->fresh('rows'), 'activation_links' => $activationLinks];
        }, 3);
    }

    public function regenerateActivation(string $batchId, string $rowId, string $tenantId, string $userId): array
    {
        $this->assertAdminActor($tenantId, $userId);
        return DB::transaction(function () use ($batchId, $rowId, $tenantId, $userId): array {
            Tenant::withoutGlobalScopes()->whereKey($tenantId)->whereNull('deleted_at')->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $batch = ImportBatch::query()->where('tenant_id', $tenantId)->whereKey($batchId)->lockForUpdate()->firstOrFail();
            if ((string) $batch->created_by !== $userId) throw new RuntimeException('Hanya Admin pembuat batch yang dapat menerbitkan ulang tautan aktivasi.');
            $row = ImportBatchRow::query()->where('tenant_id', $tenantId)->where('batch_id', $batchId)->whereKey($rowId)->lockForUpdate()->firstOrFail();
            if ($row->status !== 'IMPORTED' || empty($row->row_summary['created_user_id'])) {
                throw new RuntimeException('Baris ini tidak memiliki akun hasil import yang dapat diaktivasi ulang.');
            }
            $user = User::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereKey($row->row_summary['created_user_id'])->firstOrFail();
            $used = ImportActivationToken::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('user_id', $user->id)->whereNotNull('used_at')->exists();
            if ($used) throw new RuntimeException('Akun sudah diaktivasi. Tautan baru tidak dapat diterbitkan.');
            $teacher = TeacherProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('user_id', $user->id)->whereNull('deleted_at')->first();
            $student = StudentProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('user_id', $user->id)->whereNull('deleted_at')->first();
            if (!$teacher && !$student) throw new RuntimeException('Profil akun tidak ditemukan pada tenant ini.');
            $identity = $teacher ? ($teacher->nip ?: $teacher->nik) : $student->nisn;
            $name = $teacher?->full_name ?? $student?->full_name ?? $user->name;
            $link = $this->createActivation($user, $name, $identity, $tenantId);
            app(AuditService::class)->log('IMPORT_ACTIVATION_LINK_REISSUED', 'ImportBatchRow', $row->id, null, [
                'batch_id' => $batch->id,
                'user_id' => $user->id,
                'expires_in_hours' => 48,
            ]);
            return ['name' => $name, 'identity' => $identity, 'url' => $link['activation_url']];
        }, 3);
    }

    public function discard(string $batchId, string $tenantId, string $userId): void
    {
        $this->assertAdminActor($tenantId, $userId);
        DB::transaction(function () use ($batchId, $tenantId, $userId): void {
            $batch = ImportBatch::query()->where('tenant_id', $tenantId)->whereKey($batchId)->lockForUpdate()->firstOrFail();
            if ($batch->status !== 'PREVIEW' || (string) $batch->created_by !== $userId) {
                throw new RuntimeException('Preview ini tidak dapat dibatalkan.');
            }
            app(AuditService::class)->log('IMPORT_PREVIEW_DISCARDED', 'ImportBatch', $batch->id);
            $batch->delete();
        });
    }

    private function loginUsernameExists(string $tenantId, string $identifier): bool
    {
        return User::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereRaw('LOWER(username) = ?', [mb_strtolower($identifier)])->exists();
    }

    private function assertAdminActor(string $tenantId, string $userId): void
    {
        $actor = User::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereKey($userId)->firstOrFail();
        if (!$actor->hasRole('ADMIN_MADRASAH') || !$actor->hasPermission('user.manage')) {
            throw new RuntimeException('Hanya Admin Madrasah dengan izin Kelola Pengguna pada tenant yang sama yang dapat menjalankan import.');
        }
    }

    private function analyze(string $path, string $type, string $tenantId): array
    {
        $this->assertXlsxArchive($path);
        $book = IOFactory::load($path);
        $instruction = $book->getSheetByName('Petunjuk');
        $data = $book->getSheetByName($type === 'GURU' ? 'Data Guru' : 'Data Siswa');
        if (!$instruction || !$data) throw new RuntimeException('Struktur workbook tidak sesuai template.');
        if ((string) $instruction->getCell('B2')->getValue() !== ImportTemplateVersion::VERSION
            || (string) $instruction->getCell('B3')->getValue() !== $type) {
            throw new RuntimeException('Versi atau jenis template tidak sesuai. Unduh template terbaru.');
        }

        $expected = $type === 'GURU' ? self::GURU_HEADERS : self::SISWA_HEADERS;
        $this->assertSheetBounds($data, count($expected));
        $this->assertHeaders($data, $expected);
        $rows = [];
        $identityMap = [];
        $last = $data->getHighestDataRow();
        for ($number = 2; $number <= $last; $number++) {
            $values = $this->readRow($data, count($expected), $number, $type === 'GURU' ? [2,3,4,8] : [1,2,6,8]);
            if ($this->isBlank($values)) continue;
            $payload = $type === 'GURU' ? $this->teacherPayload($values) : $this->studentPayload($values);
            $key = $type === 'GURU' ? ($payload['nip'] ?: $payload['nik']) : $payload['nisn'];
            $rowIndex = count($rows);
            $identityValues = array_unique(array_filter(array_map(fn ($field) => $payload[$field] ?? '', $type === 'GURU' ? ['nip', 'nik', 'nuptk'] : ['nis', 'nisn']), fn ($value) => $value !== ''));
            foreach ($identityValues as $identityValue) $identityMap[$identityValue][] = $rowIndex;
            if ($type === 'GURU' && ($payload['email'] ?? '') !== '') {
                $identityMap['EMAIL:'.mb_strtolower($payload['email'])][] = $rowIndex;
            }
            $rows[] = $this->validateDataRow($type, $payload, $tenantId, $number);
        }
        foreach ($identityMap as $key => $indices) {
            if ($key !== '' && count($indices) > 1) {
                foreach ($indices as $index) {
                    $rows[$index]['status'] = 'INVALID';
                    $rows[$index]['reason'] = 'Duplikat identitas atau email dalam workbook.';
                }
            }
        }

        if ($type === 'GURU') {
            $assignments = $book->getSheetByName('Penugasan Guru');
            if (!$assignments) throw new RuntimeException('Sheet Penugasan Guru wajib tersedia pada template Guru.');
            $this->assertSheetBounds($assignments, count(self::ASSIGNMENT_HEADERS));
            $this->assertHeaders($assignments, self::ASSIGNMENT_HEADERS);
            $newTeacherKeys = collect($rows)->filter(fn ($r) => $r['sheet'] === 'Data Guru' && $r['status'] === 'VALID')
                ->map(fn ($r) => $r['payload']['nip'] ?: $r['payload']['nik'])->all();
            $assignmentKeys = [];
            for ($number = 2; $number <= $assignments->getHighestDataRow(); $number++) {
                $values = $this->readRow($assignments, count(self::ASSIGNMENT_HEADERS), $number, [1,6,7]);
                if ($this->isBlank($values)) continue;
                $payload = $this->assignmentPayload($values);
                $rowIndex = count($rows);
                $rows[] = $this->validateAssignmentRow($payload, $tenantId, $number, $newTeacherKeys);
                $assignmentKey = implode('|', [$payload['identity'], $payload['subject'], $payload['rombel'], $payload['academic_year'], $payload['semester']]);
                $assignmentKeys[$assignmentKey][] = $rowIndex;
            }
            foreach ($assignmentKeys as $indices) {
                if (count($indices) > 1) foreach ($indices as $index) {
                    $rows[$index]['status'] = 'INVALID';
                    $rows[$index]['reason'] = 'Duplikat penugasan dalam workbook.';
                }
            }
        }
        return $rows;
    }


    private function assertXlsxArchive(string $path): void
    {
        if (!is_file($path) || filesize($path) > 10 * 1024 * 1024) {
            throw new RuntimeException('File tidak ditemukan atau melebihi batas 10 MB.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('File bukan arsip XLSX yang valid atau file rusak.');
        try {
            if ($zip->numFiles > 100) throw new RuntimeException('Workbook berisi terlalu banyak komponen.');
            $expandedBytes = 0;
            $required = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                if (!$entry) continue;
                $expandedBytes += (int) ($entry['size'] ?? 0);
                if ($expandedBytes > 50 * 1024 * 1024) throw new RuntimeException('Ukuran isi workbook setelah ekstraksi melebihi 50 MB.');
                $required[$entry['name']] = true;
            }
            if (empty($required['[Content_Types].xml']) || empty($required['xl/workbook.xml'])) {
                throw new RuntimeException('Struktur arsip XLSX tidak lengkap.');
            }
        } finally {
            $zip->close();
        }
    }

    private function assertSheetBounds($sheet, int $expectedColumns): void
    {
        $rows = $sheet->getHighestDataRow();
        $columns = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        if ($rows > 5001 || $columns > $expectedColumns) {
            throw new RuntimeException('Workbook melebihi batas 5.000 baris data atau memiliki kolom tambahan yang tidak didukung pada sheet '.$sheet->getTitle().'.');
        }
    }

    private function assertHeaders($sheet, array $expected): void
    {
        foreach ($expected as $index => $header) {
            $column = Coordinate::stringFromColumnIndex($index + 1);
            if (trim((string) $sheet->getCell($column.'1')->getValue()) !== $header) {
                throw new RuntimeException('Header tidak sesuai pada sheet '.$sheet->getTitle().', kolom '.$column.'.');
            }
        }
    }

    private function readRow($sheet, int $count, int $row, array $textColumns): array
    {
        $values = [];
        for ($column = 1; $column <= $count; $column++) {
            $letter = Coordinate::stringFromColumnIndex($column);
            $cell = $sheet->getCell($letter.$row);
            if ($cell->getDataType() === DataType::TYPE_FORMULA) throw new RuntimeException('Formula tidak diizinkan pada sheet '.$sheet->getTitle().' baris '.$row.'.');
            if (mb_strlen((string) ($cell->getValue() ?? '')) > 5000) throw new RuntimeException('Isi sel terlalu panjang pada sheet '.$sheet->getTitle().' baris '.$row.'.');
            if (in_array($column, $textColumns, true) && $cell->getValue() !== null && $cell->getDataType() === DataType::TYPE_NUMERIC) {
                $values[] = '__NUMERIC_IDENTIFIER__';
            } else {
                $values[] = trim((string) ($cell->getValue() ?? ''));
            }
        }
        return $values;
    }

    private function isBlank(array $values): bool
    {
        return count(array_filter($values, fn ($value) => $value !== '')) === 0;
    }

    private function teacherPayload(array $v): array
    {
        return ['category'=>strtoupper($v[0]), 'nip'=>$v[1], 'nik'=>$v[2], 'nuptk'=>$v[3], 'name'=>$v[4], 'gender'=>strtoupper($v[5]), 'email'=>strtolower($v[6]), 'phone'=>$v[7]];
    }

    private function studentPayload(array $v): array
    {
        return ['nis'=>$v[0], 'nisn'=>$v[1], 'name'=>$v[2], 'gender'=>strtoupper($v[3]), 'birth_place'=>$v[4], 'birth_date'=>$v[5], 'parent_name'=>$v[6], 'phone'=>$v[7], 'address'=>$v[8], 'rombel'=>$v[9], 'academic_year'=>$v[10]];
    }

    private function assignmentPayload(array $v): array
    {
        return ['identity'=>$v[0], 'subject'=>$v[1], 'rombel'=>$v[2], 'academic_year'=>$v[3], 'semester'=>$v[4], 'starts_at'=>$v[5], 'ends_at'=>$v[6], 'status'=>strtoupper($v[7] ?: 'ACTIVE')];
    }

    private function validateDataRow(string $type, array $payload, string $tenantId, int $number): array
    {
        $errors = [];
        if (str_contains($payload['name'], 'CONTOH')) $errors[] = 'Baris contoh harus dihapus sebelum upload.';
        if ($payload['name'] === '') $errors[] = 'Nama lengkap wajib diisi.';
        if (mb_strlen($payload['name']) > 255) $errors[] = 'Nama lengkap maksimal 255 karakter.';
        if (!in_array($payload['gender'], ['L','P',''], true)) $errors[] = 'Jenis kelamin harus L atau P.';
        if ($type === 'GURU') {
            if (!in_array($payload['category'], ['PNS','HONORER'], true)) $errors[] = 'Kategori kepegawaian harus PNS atau HONORER.';
            if ($payload['category'] === 'PNS' && (!preg_match('/^\d{18}$/', $payload['nip']) || $payload['nik'] !== '')) $errors[] = 'Guru PNS wajib mengisi NIP 18 digit dan mengosongkan NIK.';
            if ($payload['category'] === 'HONORER' && (!preg_match('/^\d{16}$/', $payload['nik']) || $payload['nip'] !== '')) $errors[] = 'Guru honorer wajib mengisi NIK 16 digit dan mengosongkan NIP.';
            if ($payload['nuptk'] !== '' && !preg_match('/^\d{16}$/', $payload['nuptk'])) $errors[] = 'NUPTK harus 16 digit teks.';
            if ($payload['nip'] === '__NUMERIC_IDENTIFIER__' || $payload['nik'] === '__NUMERIC_IDENTIFIER__' || $payload['nuptk'] === '__NUMERIC_IDENTIFIER__') $errors[] = 'Identitas harus disimpan sebagai teks agar nol di depan tidak hilang.';
            if ($payload['email'] !== '' && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
            if (mb_strlen($payload['email']) > 255 || mb_strlen($payload['phone']) > 255) $errors[] = 'Email atau nomor HP terlalu panjang.';
            if ($payload['phone'] === '__NUMERIC_IDENTIFIER__') $errors[] = 'Nomor HP harus disimpan sebagai teks.';
            $identityValues = array_values(array_filter([$payload['nip'], $payload['nik'], $payload['nuptk']], fn ($v) => $v !== ''));
            $existing = $identityValues && TeacherProfile::withoutGlobalScopes()->where('tenant_id',$tenantId)->where(function($q) use($identityValues) {
                foreach ($identityValues as $value) $q->orWhere('nip',$value)->orWhere('nik',$value)->orWhere('nuptk',$value)->orWhere('employee_identifier',$value);
            })->exists();
            if ($existing) return $this->result('Data Guru',$number,'SKIPPED','Identitas Guru sudah terdaftar; data existing tidak diubah.',$payload);
            $loginIdentifier = $payload['nip'] ?: $payload['nik'];
            if ($loginIdentifier !== '' && $this->loginUsernameExists($tenantId, $loginIdentifier)) $errors[] = 'Identitas login sudah digunakan sebagai username akun lain pada tenant ini.';
            if ($payload['email'] !== '' && User::withoutGlobalScopes()->where('tenant_id',$tenantId)->where('email',$payload['email'])->exists()) $errors[] = 'Email sudah digunakan pada tenant ini.';
        } else {
            if ($payload['nisn'] === '__NUMERIC_IDENTIFIER__' || $payload['nis'] === '__NUMERIC_IDENTIFIER__') $errors[] = 'NIS/NISN harus disimpan sebagai teks agar nol di depan tidak hilang.';
            if (!preg_match('/^\d{10}$/', $payload['nisn'])) $errors[] = 'NISN wajib terdiri dari 10 digit teks.';
            if ($payload['nis'] !== '' && mb_strlen($payload['nis']) > 255) $errors[] = 'NIS maksimal 255 karakter.';
            if ($payload['birth_date'] !== '' && !$this->validDate($payload['birth_date'])) $errors[] = 'Tanggal lahir harus berformat YYYY-MM-DD.';
            if (mb_strlen($payload['birth_place']) > 255 || mb_strlen($payload['parent_name']) > 255 || mb_strlen($payload['phone']) > 255) $errors[] = 'Tempat lahir, nama wali, atau nomor HP terlalu panjang.';
            if ($payload['phone'] === '__NUMERIC_IDENTIFIER__') $errors[] = 'Nomor HP orang tua/wali harus disimpan sebagai teks.';
            $existing = StudentProfile::withoutGlobalScopes()->where('tenant_id',$tenantId)->where(function($q) use($payload) {
                if ($payload['nis'] !== '') $q->orWhere('nis',$payload['nis']);
                $q->orWhere('nisn',$payload['nisn']);
            })->exists();
            if ($existing) return $this->result('Data Siswa',$number,'SKIPPED','Identitas Siswa sudah terdaftar; data existing tidak diubah.',$payload);
            if ($payload['nisn'] !== '' && $this->loginUsernameExists($tenantId, $payload['nisn'])) $errors[] = 'NISN sudah digunakan sebagai username akun lain pada tenant ini.';
            $rombel = $this->resolveRombel($payload['rombel'], $payload['academic_year'], $tenantId);
            if (!$rombel) $errors[] = 'Rombel atau tahun pelajaran tidak dikenal/tidak cocok pada tenant ini.';
        }
        return $this->result($type === 'GURU' ? 'Data Guru' : 'Data Siswa', $number, $errors ? 'INVALID' : 'VALID', $errors ? implode(' ', $errors) : null, $payload);
    }

    private function validateAssignmentRow(array $p, string $tenantId, int $number, array $newTeacherKeys): array
    {
        $errors = [];
        if ($p['identity'] === '' || $p['identity'] === '__NUMERIC_IDENTIFIER__') $errors[] = 'Identitas Guru wajib berupa teks.';
        $teacher = TeacherProfile::withoutGlobalScopes()->where('tenant_id',$tenantId)->whereNull('deleted_at')
            ->where(fn($q) => $q->where('nip',$p['identity'])->orWhere('nik',$p['identity'])->orWhere('employee_identifier',$p['identity']))->first();
        if (!$teacher && !in_array($p['identity'], $newTeacherKeys, true)) $errors[] = 'Guru tidak ditemukan pada tenant ini atau belum ada di sheet Data Guru yang valid.';
        $subject = $this->resolveSubject($p['subject'], $tenantId);
        if (!$subject) $errors[] = 'Mata pelajaran tidak dikenal pada tenant ini.';
        $rombel = $this->resolveRombel($p['rombel'], $p['academic_year'], $tenantId);
        if (!$rombel) $errors[] = 'Rombel/tahun pelajaran tidak dikenal atau tidak cocok pada tenant ini.';
        $semester = $this->resolveSemester($p['semester'], $p['academic_year'], $tenantId);
        if (!$semester) $errors[] = 'Semester tidak sesuai dengan tahun pelajaran pada tenant ini.';
        if (!in_array($p['status'], ['ACTIVE','INACTIVE'], true)) $errors[] = 'Status penugasan harus ACTIVE atau INACTIVE.';
        foreach (['starts_at','ends_at'] as $date) if ($p[$date] !== '' && !$this->validDate($p[$date])) $errors[] = 'Tanggal penugasan harus berformat YYYY-MM-DD.';
        if ($p['starts_at'] && $p['ends_at'] && $p['starts_at'] > $p['ends_at']) $errors[] = 'Tanggal mulai tidak boleh setelah tanggal selesai.';
        if (!$errors && $teacher && TeacherSubjectAssignment::withoutGlobalScopes()->where('tenant_id',$tenantId)
            ->where('teacher_profile_id',$teacher->id)->where('subject_id',$subject->id)->where('rombel_id',$rombel->id)
            ->where('academic_year_id',$rombel->academic_year_id)->where('semester_id',$semester->id)->exists()) {
            return $this->result('Penugasan Guru',$number,'SKIPPED','Penugasan yang sama sudah ada.',$p);
        }
        return $this->result('Penugasan Guru',$number,$errors ? 'INVALID' : 'VALID',$errors ? implode(' ',$errors) : null,$p);
    }

    private function result(string $sheet, int $row, string $status, ?string $reason, array $payload): array
    {
        $name = $payload['name'] ?? null;
        $identity = ($payload['nip'] ?? '') ?: (($payload['nik'] ?? '') ?: (($payload['nisn'] ?? '') ?: ($payload['identity'] ?? '')));
        return ['sheet'=>$sheet,'row'=>$row,'status'=>$status,'reason'=>$reason,
            'summary'=>['name'=>$name,'identity_suffix'=>$identity !== '' ? substr($identity,-4) : null],
            'payload'=>$payload];
    }

    private function resolveSubject(string $label, string $tenantId): ?Subject
    {
        $parts = explode(' — ', $label, 2);
        if (count($parts) !== 2) return null;
        return Subject::withoutGlobalScopes()->where('tenant_id',$tenantId)->whereNull('deleted_at')->where('code',$parts[0])->where('name',$parts[1])->where('status','ACTIVE')->first();
    }

    private function resolveRombel(string $label, string $yearName, string $tenantId): ?Rombel
    {
        if ($label === '' || $yearName === '') return null;
        $parts = explode(' — ', $label, 2);
        if (count($parts) !== 2 || $parts[1] !== $yearName) return null;
        $year = AcademicYear::withoutGlobalScopes()->where('tenant_id',$tenantId)->whereNull('deleted_at')->where('name',$yearName)->first();
        if (!$year) return null;
        return Rombel::withoutGlobalScopes()->where('tenant_id',$tenantId)->where('academic_year_id',$year->id)
            ->where('name',$parts[0])->where('status','ACTIVE')->whereNull('deleted_at')->first();
    }

    private function resolveSemester(string $label, string $yearName, string $tenantId): ?Semester
    {
        $parts = explode(' — ', $label, 2);
        if (count($parts) !== 2 || $parts[1] !== $yearName) return null;
        $year = AcademicYear::withoutGlobalScopes()->where('tenant_id',$tenantId)->whereNull('deleted_at')->where('name',$yearName)->first();
        if (!$year) return null;
        return Semester::withoutGlobalScopes()->where('tenant_id',$tenantId)->whereNull('deleted_at')->where('academic_year_id',$year->id)->where('term',$parts[0])->first();
    }

    private function validDate(string $date): bool
    {
        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    private function importTeacher(array $p, string $tenantId, string $userId): array
    {
        $identityValues = array_values(array_filter([$p['nip'], $p['nik'], $p['nuptk']], fn ($v) => $v !== ''));
        $exists = $identityValues && TeacherProfile::withoutGlobalScopes()->where('tenant_id',$tenantId)
            ->where(fn($q) => collect($identityValues)->each(fn ($value) => $q->orWhere('nip',$value)->orWhere('nik',$value)->orWhere('nuptk',$value)))->exists();
        if ($exists) return ['status'=>'SKIPPED','reason'=>'Identitas sudah terdaftar sebelum konfirmasi.'];
        $loginIdentifier = $p['nip'] ?: $p['nik'];
        if ($this->loginUsernameExists($tenantId, $loginIdentifier)) throw new RuntimeException('Identitas login sudah digunakan sebagai username akun lain pada tenant ini.');
        $role = Role::query()->where('name','GURU')->firstOrFail();
        $temporaryPassword = Str::random(64);
        $user = User::create(['name'=>$p['name'],'username'=>$loginIdentifier,'email'=>$p['email'] ?: null,'password'=>Hash::make($temporaryPassword)]);
        $user->roles()->sync([$role->id]);
        $profile = TeacherProfile::create([
            'user_id'=>$user->id,'nip'=>$p['nip'] ?: null,'nik'=>$p['nik'] ?: null,'nuptk'=>$p['nuptk'] ?: null,
            'employee_identifier'=>$p['nip'] ?: $p['nik'],'full_name'=>$p['name'],
            'gender'=>$p['gender'] === 'L' ? 'M' : ($p['gender'] === 'P' ? 'F' : null),'phone'=>$p['phone'] ?: null,
        ]);
        app(AuditService::class)->log('USER_CREATED','User',$user->id,null,['role'=>'GURU','profile_id'=>$profile->id,'login_identifier_suffix'=>substr($p['nip'] ?: $p['nik'], -4)]);
        return $this->createActivation($user,$p['name'],$p['nip'] ?: $p['nik'],$tenantId);
    }

    private function importStudent(array $p, string $tenantId, string $userId): array
    {
        $exists = StudentProfile::withoutGlobalScopes()->where('tenant_id',$tenantId)
            ->where(fn($q) => $q->where('nisn',$p['nisn'])->orWhere('nis',$p['nis']))->exists();
        if ($exists) return ['status'=>'SKIPPED','reason'=>'Identitas sudah terdaftar sebelum konfirmasi.'];
        if ($this->loginUsernameExists($tenantId, $p['nisn'])) throw new RuntimeException('NISN sudah digunakan sebagai username akun lain pada tenant ini.');
        $rombel = $this->resolveRombel($p['rombel'],$p['academic_year'],$tenantId);
        if (!$rombel) throw new RuntimeException('Rombel tidak lagi valid saat konfirmasi.');
        $role = Role::query()->where('name','SISWA')->firstOrFail();
        $user = User::create(['name'=>$p['name'],'username'=>$p['nisn'],'email'=>null,'password'=>Hash::make(Str::random(64))]);
        $user->roles()->sync([$role->id]);
        $profile = StudentProfile::create([
            'user_id'=>$user->id,'nis'=>$p['nis'] ?: null,'nisn'=>$p['nisn'],'full_name'=>$p['name'],
            'gender'=>$p['gender'] === 'L' ? 'M' : ($p['gender'] === 'P' ? 'F' : null),
            'birth_place'=>$p['birth_place'] ?: null,'birth_date'=>$p['birth_date'] ?: null,
            'parent_name'=>$p['parent_name'] ?: null,'phone'=>$p['phone'] ?: null,'address'=>$p['address'] ?: null,
        ]);
        DB::table('rombel_student')->insert([
            'id'=>(string) Str::uuid(),'tenant_id'=>$tenantId,'rombel_id'=>$rombel->id,'student_id'=>$profile->id,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        app(AuditService::class)->log('USER_CREATED','User',$user->id,null,['role'=>'SISWA','profile_id'=>$profile->id,'login_identifier_suffix'=>substr($p['nisn'], -4),'rombel_id'=>$rombel->id]);
        return $this->createActivation($user,$p['name'],$p['nisn'],$tenantId);
    }

    private function importAssignment(array $p, string $tenantId, string $userId): array
    {
        $teacher = TeacherProfile::withoutGlobalScopes()->where('tenant_id',$tenantId)->whereNull('deleted_at')
            ->where(fn($q) => $q->where('nip',$p['identity'])->orWhere('nik',$p['identity'])->orWhere('employee_identifier',$p['identity']))->firstOrFail();
        $subject = $this->resolveSubject($p['subject'],$tenantId) ?? throw new RuntimeException('Mata pelajaran tidak lagi valid.');
        $rombel = $this->resolveRombel($p['rombel'],$p['academic_year'],$tenantId) ?? throw new RuntimeException('Rombel tidak lagi valid.');
        $semester = $this->resolveSemester($p['semester'],$p['academic_year'],$tenantId) ?? throw new RuntimeException('Semester tidak lagi valid.');
        $existing = TeacherSubjectAssignment::withoutGlobalScopes()->where('tenant_id',$tenantId)
            ->where('teacher_profile_id',$teacher->id)->where('subject_id',$subject->id)->where('rombel_id',$rombel->id)
            ->where('academic_year_id',$rombel->academic_year_id)->where('semester_id',$semester->id)->exists();
        if ($existing) return ['status'=>'SKIPPED','reason'=>'Penugasan yang sama sudah ada.'];
        $assignment = TeacherSubjectAssignment::create([
            'teacher_profile_id'=>$teacher->id,'subject_id'=>$subject->id,'rombel_id'=>$rombel->id,
            'academic_year_id'=>$rombel->academic_year_id,'semester_id'=>$semester->id,
            'starts_at'=>$p['starts_at'] ?: null,'ends_at'=>$p['ends_at'] ?: null,'status'=>$p['status'],'created_by'=>$userId,
        ]);
        app(AuditService::class)->log('TEACHER_ASSIGNMENT_CREATED','TeacherSubjectAssignment',$assignment->id,null,[
            'teacher_profile_id'=>$teacher->id,'subject_id'=>$subject->id,'rombel_id'=>$rombel->id,
            'academic_year_id'=>$rombel->academic_year_id,'semester_id'=>$semester->id,
        ]);
        return ['status'=>'SUCCESS','reason'=>null];
    }

    private function createActivation(User $user, string $name, string $identity, string $tenantId): array
    {
        ImportActivationToken::withoutGlobalScopes()->where('tenant_id', $tenantId)->where('user_id', $user->id)
            ->whereNull('used_at')->update(['used_at' => now()]);
        $plain = Str::random(64);
        ImportActivationToken::create([
            'user_id'=>$user->id,'token_hash'=>hash('sha256',$plain),
            'expires_at'=>now()->addHours(48),'created_at'=>now(),
        ]);
        $url = URL::temporarySignedRoute('import-account.activate.show', now()->addHours(48), ['token'=>$plain]);
        return ['status'=>'IMPORTED','reason'=>null,'activation_url'=>$url,'name'=>$name,'identity'=>$identity,'user_id'=>$user->id];
    }
}
