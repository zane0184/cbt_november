<?php

namespace App\Services\QuestionImport;

use App\Exports\QuestionImport\QuestionImportTemplateExport;
use App\Models\GradeLevel;
use App\Models\LearningObjective;
use App\Models\LearningOutcome;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use App\Support\Security\TeacherAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

final class QuestionImportWorkbookParser
{
    private const ALLOWED_TYPES = [
        'MULTIPLE_CHOICE',
        'MULTIPLE_RESPONSE',
        'TRUE_FALSE',
        'MATCHING',
        'ESSAY',
    ];

    private const DATA_HEADERS = [
        'External Row ID', 'Nomor Soal', 'Kode Soal', 'Mata Pelajaran Ref', 'Kelas Ref',
        'Tahun Ajaran Ref', 'Materi/Topik', 'CP Ref', 'TP Ref', 'KBC Ref',
        'Elemen KBC Ref', 'Jenis Soal', 'Pertanyaan', 'Pembahasan/Rubrik',
        'Bobot', 'Kesulitan', 'Indikator', 'Sumber/Referensi', 'Media Ref',
    ];

    private const DETAIL_HEADERS = [
        'Nomor Soal', 'Tipe Detail', 'Urutan', 'Option Key', 'Isi Opsi/Pasangan',
        'Matching Key', 'Benar (TRUE/FALSE)', 'Accepted Answer', 'Media Ref',
    ];

    private const REQUIRED_SHEETS = [
        'Petunjuk',
        'Data Soal',
        'Detail Opsi',
        'Referensi Jenis Soal',
        'Contoh Soal',
        'Referensi Akademik',
    ];

    public function parse(UploadedFile $file, User $user): array
    {
        $errors = [];
        $rows = [];

        if (!$file->isValid() || strtolower($file->getClientOriginalExtension()) !== 'xlsx') {
            return $this->failed([['row' => 0, 'field' => 'file', 'error' => 'File harus berupa workbook XLSX yang valid.']]);
        }

        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->failed([['row' => 0, 'field' => 'file', 'error' => 'Ukuran workbook maksimal 10 MB.']]);
        }

        try {
            $workbook = IOFactory::load($file->getRealPath());
        } catch (Throwable $exception) {
            return $this->failed([['row' => 0, 'field' => 'file', 'error' => 'Workbook tidak dapat dibaca atau rusak.']]);
        }

        $sheetNames = $workbook->getSheetNames();
        foreach (self::REQUIRED_SHEETS as $sheetName) {
            if (!in_array($sheetName, $sheetNames, true)) {
                $errors[] = ['row' => 0, 'field' => $sheetName, 'error' => 'Sheet wajib tidak ditemukan.'];
            }
        }
        if ($errors !== []) {
            return $this->failed($errors);
        }

        foreach ($sheetNames as $sheetName) {
            if (!in_array($sheetName, self::REQUIRED_SHEETS, true)) {
                $errors[] = ['row' => 0, 'field' => $sheetName, 'error' => 'Sheet tambahan tidak dikenal; gunakan template resmi tanpa sheet tambahan.'];
            }
        }

        $instructions = $workbook->getSheetByName('Petunjuk');
        if ((string) $instructions->getCell('B2')->getFormattedValue() !== QuestionImportTemplateExport::VERSION) {
            $errors[] = ['row' => 2, 'field' => 'TEMPLATE_VERSION', 'error' => 'Versi template tidak dikenal. Unduh template terbaru dari Portal Guru.'];
        }

        $dataSheet = $workbook->getSheetByName('Data Soal');
        $detailSheet = $workbook->getSheetByName('Detail Opsi');
        $this->validateHeaders($dataSheet, self::DATA_HEADERS, 'Data Soal', $errors);
        $this->validateHeaders($detailSheet, self::DETAIL_HEADERS, 'Detail Opsi', $errors);

        if ($errors !== []) {
            return $this->failed($errors);
        }

        $dataLastRow = $dataSheet->getHighestDataRow();
        $detailLastRow = $detailSheet->getHighestDataRow();
        if ($dataLastRow > 2001 || $detailLastRow > 8001) {
            return $this->failed([['row' => 0, 'field' => 'workbook', 'error' => 'Workbook melebihi batas 2.000 soal atau 8.000 detail opsi.']]);
        }

        $dataByNumber = [];
        $externalIds = [];
        $questionCodes = [];

        for ($excelRow = 2; $excelRow <= $dataLastRow; $excelRow++) {
            if ($this->rowIsBlank($dataSheet, count(self::DATA_HEADERS), $excelRow)) {
                continue;
            }
            if ($this->rowHasFormula($dataSheet, count(self::DATA_HEADERS), $excelRow)) {
                $errors[] = ['row' => $excelRow, 'field' => 'Data Soal', 'error' => 'Formula tidak diperbolehkan. Masukkan nilai sebagai teks atau angka.'];
                continue;
            }

            $values = $this->readRow($dataSheet, self::DATA_HEADERS, $excelRow);
            $number = trim((string) $values['Nomor Soal']);
            $externalId = trim((string) $values['External Row ID']);
            $questionCode = strtoupper(trim((string) $values['Kode Soal']));

            if ($number === '') {
                $errors[] = ['row' => $excelRow, 'field' => 'Nomor Soal', 'error' => 'Nomor Soal wajib diisi dan menjadi penghubung Detail Opsi.'];
                continue;
            }
            if (isset($dataByNumber[$number])) {
                $errors[] = ['row' => $excelRow, 'field' => 'Nomor Soal', 'error' => 'Nomor Soal duplikat di dalam workbook.'];
                continue;
            }
            if ($externalId !== '' && isset($externalIds[$externalId])) {
                $errors[] = ['row' => $excelRow, 'field' => 'External Row ID', 'error' => 'External Row ID duplikat di dalam workbook.'];
                continue;
            }
            if ($questionCode !== '' && isset($questionCodes[$questionCode])) {
                $errors[] = ['row' => $excelRow, 'field' => 'Kode Soal', 'error' => 'Kode Soal duplikat di dalam workbook.'];
                continue;
            }

            if (str_starts_with(strtoupper($number), 'CONTOH-') || str_starts_with(strtoupper($questionCode), 'CONTOH-')) {
                $errors[] = ['row' => $excelRow, 'field' => 'Nomor Soal', 'error' => 'Baris contoh tidak boleh diimpor sebagai soal nyata.'];
                continue;
            }

            $type = strtoupper(trim((string) $values['Jenis Soal']));
            if (!in_array($type, self::ALLOWED_TYPES, true)) {
                $errors[] = ['row' => $excelRow, 'field' => 'Jenis Soal', 'error' => 'Jenis soal tidak didukung atau masih berstatus BLOCKED.'];
                continue;
            }

            $stem = trim((string) $values['Pertanyaan']);
            if ($stem === '') {
                $errors[] = ['row' => $excelRow, 'field' => 'Pertanyaan', 'error' => 'Pertanyaan wajib diisi.'];
                continue;
            }

            $unsupported = [
                'Tahun Ajaran Ref' => 'Tahun ajaran tidak disimpan pada versi soal; kosongkan sampai kontrak akademik versi soal disetujui.',
                'Materi/Topik' => 'Materi/topik belum memiliki kolom versi yang tervalidasi; kosongkan sampai metadata soal tersedia.',
                'KBC Ref' => 'Pemetaan KBC menunggu kontrak mapping versi soal dan snapshot; kosongkan untuk sementara.',
                'Elemen KBC Ref' => 'Pemetaan elemen KBC menunggu kontrak mapping versi soal dan snapshot; kosongkan untuk sementara.',
                'Indikator' => 'Indikator belum memiliki kolom versi yang tervalidasi; kosongkan sampai metadata soal tersedia.',
                'Sumber/Referensi' => 'Sumber/referensi belum memiliki kolom versi yang tervalidasi; kosongkan sampai metadata soal tersedia.',
                'Media Ref' => 'Media dipasang melalui pengelola Media pada versi draft soal, bukan melalui referensi workbook.',
                'Accepted Answer' => 'Accepted answer untuk SHORT_ANSWER masih BLOCKED.',
            ];
            foreach ($unsupported as $field => $message) {
                if (trim((string) ($values[$field] ?? '')) !== '') {
                    $errors[] = ['row' => $excelRow, 'field' => $field, 'error' => $message];
                }
            }

            $subjectId = trim((string) $values['Mata Pelajaran Ref']);
            if (!Str::isUuid($subjectId)) {
                $errors[] = ['row' => $excelRow, 'field' => 'Mata Pelajaran Ref', 'error' => 'Ref harus berupa UUID mata pelajaran tenant aktif.'];
                continue;
            }
            $subject = Subject::query()->whereKey($subjectId)->where('tenant_id', $user->tenant_id)->first();
            if (!$subject || !TeacherAccess::canManageSubject($user, $subjectId)) {
                $errors[] = ['row' => $excelRow, 'field' => 'Mata Pelajaran Ref', 'error' => 'Mata pelajaran tidak ditemukan pada tenant aktif atau bukan penugasan Guru.'];
                continue;
            }

            $gradeLevelId = trim((string) $values['Kelas Ref']);
            $gradeLevel = null;
            if ($gradeLevelId !== '') {
                if (!Str::isUuid($gradeLevelId)) {
                    $errors[] = ['row' => $excelRow, 'field' => 'Kelas Ref', 'error' => 'Ref kelas harus berupa UUID.'];
                    continue;
                }
                $gradeLevel = GradeLevel::query()
                    ->with('phase')
                    ->whereKey($gradeLevelId)
                    ->where('tenant_id', $user->tenant_id)
                    ->where('status', 'ACTIVE')
                    ->first();
                if (!$gradeLevel || !$gradeLevel->phase || $gradeLevel->phase->status !== 'ACTIVE') {
                    $errors[] = ['row' => $excelRow, 'field' => 'Kelas Ref', 'error' => 'Kelas/fase tidak ditemukan atau tidak aktif pada tenant ini.'];
                    continue;
                }
            }

            $outcomeId = trim((string) $values['CP Ref']);
            $outcome = null;
            if ($outcomeId !== '') {
                if (!Str::isUuid($outcomeId)) {
                    $errors[] = ['row' => $excelRow, 'field' => 'CP Ref', 'error' => 'Ref CP harus berupa UUID.'];
                    continue;
                }
                $outcome = LearningOutcome::query()
                    ->with('currentVersion')
                    ->whereKey($outcomeId)
                    ->where('tenant_id', $user->tenant_id)
                    ->where('subject_id', $subjectId)
                    ->first();
                if (!$outcome || !$outcome->currentVersion || $outcome->currentVersion->status !== 'ACTIVE') {
                    $errors[] = ['row' => $excelRow, 'field' => 'CP Ref', 'error' => 'CP tidak aktif, tidak ditemukan, atau bukan CP mata pelajaran/tenant ini.'];
                    continue;
                }
            }

            $objectiveId = trim((string) $values['TP Ref']);
            $objective = null;
            if ($objectiveId !== '') {
                if ($outcomeId === '' || !Str::isUuid($objectiveId)) {
                    $errors[] = ['row' => $excelRow, 'field' => 'TP Ref', 'error' => 'TP harus berupa UUID dan wajib disertai CP Ref.'];
                    continue;
                }
                $objective = LearningObjective::query()
                    ->whereKey($objectiveId)
                    ->where('tenant_id', $user->tenant_id)
                    ->where('learning_outcome_id', $outcomeId)
                    ->where('status', 'ACTIVE')
                    ->first();
                if (!$objective) {
                    $errors[] = ['row' => $excelRow, 'field' => 'TP Ref', 'error' => 'TP tidak aktif atau tidak berada di bawah CP yang dipilih.'];
                    continue;
                }
            }

            if ($gradeLevel && $outcome && $outcome->phase_id && $gradeLevel->phase_id !== $outcome->phase_id) {
                $errors[] = ['row' => $excelRow, 'field' => 'CP Ref', 'error' => 'Fase kelas tidak cocok dengan fase CP.'];
                continue;
            }

            $difficulty = strtoupper(trim((string) $values['Kesulitan']));
            if ($difficulty === '') $difficulty = 'MEDIUM';
            if (!in_array($difficulty, ['EASY', 'MEDIUM', 'HARD'], true)) {
                $errors[] = ['row' => $excelRow, 'field' => 'Kesulitan', 'error' => 'Kesulitan harus EASY, MEDIUM, atau HARD.'];
                continue;
            }

            $pointsRaw = trim((string) $values['Bobot']);
            $points = $pointsRaw === '' ? 1.0 : (float) $pointsRaw;
            if (!is_numeric($pointsRaw === '' ? '1' : $pointsRaw) || $points < 0.5 || $points > 1000) {
                $errors[] = ['row' => $excelRow, 'field' => 'Bobot', 'error' => 'Bobot harus angka antara 0,5 dan 1000.'];
                continue;
            }

            if ($questionCode !== '') {
                $questionCodes[$questionCode] = true;
            }
            if ($externalId !== '') {
                $externalIds[$externalId] = true;
            }

            $existingQuestion = null;
            if ($questionCode !== '') {
                $existingQuestion = Question::withTrashed()
                    ->where('tenant_id', $user->tenant_id)
                    ->where('question_code', $questionCode)
                    ->first();

                if ($existingQuestion && ($existingQuestion->trashed() || $existingQuestion->status === 'ARCHIVED')) {
                    $errors[] = ['row' => $excelRow, 'field' => 'Kode Soal', 'error' => 'Kode Soal sudah digunakan oleh soal arsip; gunakan kode baru.'];
                    continue;
                }
                if ($existingQuestion && $existingQuestion->subject_id !== $subjectId) {
                    $errors[] = ['row' => $excelRow, 'field' => 'Kode Soal', 'error' => 'Kode Soal sudah digunakan pada mata pelajaran lain.'];
                    continue;
                }
            }

            $dataByNumber[$number] = [
                '_excel_row' => $excelRow,
                '_number' => $number,
                'question_id' => $existingQuestion?->id,
                'question_code' => $questionCode !== '' ? $questionCode : null,
                'subject_id' => $subjectId,
                'grade_level_id' => $gradeLevel?->id,
                'phase_id' => $gradeLevel?->phase_id ?? $outcome?->phase_id,
                'curriculum_id' => $gradeLevel?->phase?->curriculum_id ?? $outcome?->curriculum_id,
                'element_id' => $outcome?->element_id,
                'learning_outcome_id' => $outcome?->id,
                'learning_objective_id' => $objective?->id,
                'type' => $type,
                'stem' => $stem,
                'explanation' => trim((string) $values['Pembahasan/Rubrik']),
                'points' => $points,
                'difficulty' => $difficulty,
                'options' => [],
            ];
        }

        $detailOrder = [];
        for ($excelRow = 2; $excelRow <= $detailLastRow; $excelRow++) {
            if ($this->rowIsBlank($detailSheet, count(self::DETAIL_HEADERS), $excelRow)) {
                continue;
            }
            if ($this->rowHasFormula($detailSheet, count(self::DETAIL_HEADERS), $excelRow)) {
                $errors[] = ['row' => $excelRow, 'field' => 'Detail Opsi', 'error' => 'Formula tidak diperbolehkan.'];
                continue;
            }

            $values = $this->readRow($detailSheet, self::DETAIL_HEADERS, $excelRow);
            $number = trim((string) $values['Nomor Soal']);
            if (!isset($dataByNumber[$number])) {
                $errors[] = ['row' => $excelRow, 'field' => 'Nomor Soal', 'error' => 'Detail opsi merujuk nomor soal yang tidak ada pada Data Soal.'];
                continue;
            }

            $detailType = strtoupper(trim((string) $values['Tipe Detail']));
            if (!in_array($detailType, ['OPTION', 'MATCH_PAIR'], true)) {
                $errors[] = ['row' => $excelRow, 'field' => 'Tipe Detail', 'error' => 'Tipe detail hanya OPTION atau MATCH_PAIR; ACCEPTED_ANSWER masih BLOCKED.'];
                continue;
            }
            if (trim((string) $values['Accepted Answer']) !== '' || trim((string) $values['Media Ref']) !== '') {
                $errors[] = ['row' => $excelRow, 'field' => 'Detail Opsi', 'error' => 'Accepted Answer dan Media Ref belum didukung oleh kontrak import ini.'];
                continue;
            }

            $content = trim((string) $values['Isi Opsi/Pasangan']);
            $optionKey = strtoupper(trim((string) $values['Option Key']));
            $matchingKey = trim((string) $values['Matching Key']);
            $correctRaw = strtoupper(trim((string) $values['Benar (TRUE/FALSE)']));
            $isCorrect = in_array($correctRaw, ['TRUE', '1', 'YA', 'YES'], true);
            if ($correctRaw !== '' && !in_array($correctRaw, ['TRUE', 'FALSE', '1', '0', 'YA', 'TIDAK', 'YES', 'NO'], true)) {
                $errors[] = ['row' => $excelRow, 'field' => 'Benar (TRUE/FALSE)', 'error' => 'Nilai benar harus TRUE/FALSE atau 1/0.'];
                continue;
            }
            if ($content === '') {
                $errors[] = ['row' => $excelRow, 'field' => 'Isi Opsi/Pasangan', 'error' => 'Isi opsi/pasangan wajib diisi.'];
                continue;
            }
            if ($detailType === 'OPTION' && !in_array($optionKey, ['A', 'B', 'C', 'D'], true)) {
                $errors[] = ['row' => $excelRow, 'field' => 'Option Key', 'error' => 'Option Key wajib A-D.'];
                continue;
            }
            if ($detailType === 'MATCH_PAIR' && $optionKey === '' && $matchingKey === '') {
                $errors[] = ['row' => $excelRow, 'field' => 'Matching Key', 'error' => 'Pasangan wajib memiliki Option Key atau Matching Key.'];
                continue;
            }

            $detailOrder[$number] = ($detailOrder[$number] ?? 0) + 1;
            $sequenceValue = trim((string) $values['Urutan']);
            if ($sequenceValue !== '' && (!ctype_digit($sequenceValue) || (int) $sequenceValue < 1)) {
                $errors[] = ['row' => $excelRow, 'field' => 'Urutan', 'error' => 'Urutan harus bilangan bulat positif.'];
                continue;
            }
            $dataByNumber[$number]['options'][] = [
                'option_key' => $optionKey !== '' ? $optionKey : null,
                'content' => $content,
                'matching_key' => $matchingKey !== '' ? $matchingKey : null,
                'is_correct' => $isCorrect,
                '_detail_type' => $detailType,
                '_sequence' => is_numeric($values['Urutan']) ? (int) $values['Urutan'] : $detailOrder[$number],
            ];
        }

        foreach ($dataByNumber as $number => $row) {
            $types = array_values(array_unique(array_column($row['options'], '_detail_type')));
            $type = $row['type'];
            if (in_array($type, ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE'], true)) {
                if ($row['options'] === [] || $types !== ['OPTION']) {
                    $errors[] = ['row' => $row['_excel_row'], 'field' => 'Detail Opsi', 'error' => 'Jenis soal pilihan wajib memiliki detail OPTION dan tidak boleh MATCH_PAIR.'];
                    continue;
                }
            } elseif ($type === 'MATCHING') {
                if (count($row['options']) < 2 || $types !== ['MATCH_PAIR']) {
                    $errors[] = ['row' => $row['_excel_row'], 'field' => 'Detail Opsi', 'error' => 'MATCHING wajib memiliki minimal dua detail MATCH_PAIR.'];
                    continue;
                }
            } elseif ($type === 'ESSAY' && $row['options'] !== []) {
                $errors[] = ['row' => $row['_excel_row'], 'field' => 'Detail Opsi', 'error' => 'ESSAY tidak boleh memiliki detail opsi.'];
                continue;
            }

            usort($row['options'], fn (array $left, array $right) => $left['_sequence'] <=> $right['_sequence']);
            $row['options'] = array_map(function (array $option): array {
                unset($option['_detail_type'], $option['_sequence']);
                return $option;
            }, $row['options']);
            $rows[] = $row;
        }

        $workbook->disconnectWorksheets();

        return [
            'success' => $errors === [] && $rows !== [],
            'total_rows' => count($dataByNumber),
            'valid_rows' => count($rows),
            'rows' => $rows,
            'errors' => $errors,
        ];
    }

    private function validateHeaders(Worksheet $sheet, array $expected, string $sheetName, array &$errors): void
    {
        $actual = [];
        foreach (range(1, count($expected)) as $index) {
            $actual[] = trim((string) $sheet->getCellByColumnAndRow($index, 1)->getFormattedValue());
        }
        if ($actual !== $expected) {
            $errors[] = ['row' => 1, 'field' => $sheetName, 'error' => 'Header tidak sesuai template resmi. Unduh template terbaru dan jangan mengubah nama/urutan kolom.'];
        }
    }

    private function rowIsBlank(Worksheet $sheet, int $columns, int $row): bool
    {
        for ($column = 1; $column <= $columns; $column++) {
            if (trim((string) $sheet->getCellByColumnAndRow($column, $row)->getFormattedValue()) !== '') {
                return false;
            }
        }
        return true;
    }

    private function rowHasFormula(Worksheet $sheet, int $columns, int $row): bool
    {
        for ($column = 1; $column <= $columns; $column++) {
            if ($sheet->getCellByColumnAndRow($column, $row)->getDataType() === DataType::TYPE_FORMULA) {
                return true;
            }
        }
        return false;
    }

    private function readRow(Worksheet $sheet, array $headers, int $row): array
    {
        $values = [];
        foreach ($headers as $index => $header) {
            $values[$header] = trim((string) $sheet->getCellByColumnAndRow($index + 1, $row)->getFormattedValue());
        }
        return $values;
    }

    private function failed(array $errors): array
    {
        return [
            'success' => false,
            'total_rows' => 0,
            'valid_rows' => 0,
            'rows' => [],
            'errors' => $errors,
        ];
    }
}
