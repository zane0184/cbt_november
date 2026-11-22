<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\LearningObjective;
use App\Models\LearningOutcome;
use App\Models\Phase;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Subject;
use App\Services\AuditService;
use App\Support\Security\TeacherAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExcelQuestionImporter implements QuestionImportService
{
    private const ALLOWED_TYPES = [
        'MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE',
        'MATCHING', 'ESSAY'
    ];

    public function import(string $filePath, array $options = []): array
    {
        $results = [
            'success' => true,
            'total_rows' => 0,
            'imported' => 0,
            'errors' => []
        ];

        $rows = $options['rows'] ?? [];
        $results['total_rows'] = count($rows);

        if ($rows === []) {
            return ['success' => false, 'total_rows' => 0, 'imported' => 0, 'errors' => [['row' => 0, 'error' => 'Tidak ada baris soal yang dapat diimpor.']]];
        }

        try {
            DB::transaction(function () use ($rows, &$results) {
                foreach ($rows as $index => $row) {
                    $this->processRow($row, $index + 1, $results);
                }

                if (!empty($results['errors'])) {
                    throw new \Exception('Validation errors occurred.');
                }
            });
        } catch (\Throwable $e) {
            $results['success'] = false;
            $results['imported'] = 0; // The enclosing transaction rolled back all rows.
            if ($e->getMessage() !== 'Validation errors occurred.') {
                $results['errors'][] = ['row' => 0, 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    protected function processRow(array $row, int $rowNumber, array &$results): void
    {
        // 1. Basic Validation
        if (empty($row['stem']) || empty($row['type'])) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => 'Stem and Type are required.'];
            return;
        }

        if (!in_array($row['type'], self::ALLOWED_TYPES)) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => "Invalid question type: {$row['type']}"];
            return;
        }

        // 2. Type-Specific Validation. Keep import integrity equivalent to
        // the Question API and Livewire authoring rules.
        $options = $row['options'] ?? [];
        if (!is_array($options)) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => 'Options must be an array.'];
            return;
        }

        if (in_array($row['type'], ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE'], true)) {
            $min = 2;
            $max = $row['type'] === 'TRUE_FALSE' ? 2 : 4;
            if (count($options) < $min || count($options) > $max) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Jumlah opsi tidak sesuai dengan jenis soal.'];
                return;
            }

            $keys = [];
            $correctCount = 0;
            foreach ($options as $option) {
                $key = strtoupper(trim((string) ($option['option_key'] ?? '')));
                if ($key === '') $key = chr(65 + count($keys));
                if (!in_array($key, ['A', 'B', 'C', 'D'], true)
                    || trim((string) ($option['content'] ?? '')) === ''
                    || in_array($key, $keys, true)) {
                    $results['errors'][] = ['row' => $rowNumber, 'error' => 'Setiap opsi wajib memiliki kunci A-D unik dan isi yang tidak kosong.'];
                    return;
                }
                $keys[] = $key;
                if ((bool) ($option['is_correct'] ?? false)) $correctCount++;
            }

            if (($row['type'] === 'TRUE_FALSE' && array_diff(['A', 'B'], $keys) !== [])
                || (in_array($row['type'], ['MULTIPLE_CHOICE', 'TRUE_FALSE'], true) && $correctCount !== 1)
                || ($row['type'] === 'MULTIPLE_RESPONSE' && $correctCount < 1)) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Kunci jawaban tidak sesuai dengan aturan jenis soal.'];
                return;
            }
        } elseif ($row['type'] === 'MATCHING') {
            $mappingKeys = [];
            foreach ($options as $option) {
                $content = trim((string) ($option['content'] ?? ''));
                $leftKey = trim((string) ($option['option_key'] ?? ''));
                $rightKey = trim((string) ($option['matching_key'] ?? ''));
                if ($content === '' || ($leftKey === '' && $rightKey === '')) {
                    $results['errors'][] = ['row' => $rowNumber, 'error' => 'Isi dan kunci setiap item menjodohkan wajib tersedia.'];
                    return;
                }
                if ($leftKey !== '') $mappingKeys[$leftKey] = true;
                if ($rightKey !== '') $mappingKeys[$rightKey] = true;
            }
            if (count($options) < 2 || count($mappingKeys) < 1) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Soal menjodohkan wajib memiliki minimal dua item dengan kunci pasangan.'];
                return;
            }
        } elseif ($row['type'] === 'ESSAY' && $options !== []) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => 'Soal jawaban singkat/esai tidak boleh memiliki opsi pilihan.'];
            return;
        }

        // 3. Question & Versioning Logic
        $questionId = $row['question_id'] ?? null;
        $question = null;

        if (!$questionId && !empty($row['question_code'])) {
            $questionId = Question::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->where('question_code', strtoupper(trim((string) $row['question_code'])))
                ->value('id');
        }

        if ($questionId) {
            $question = Question::query()
                ->where('tenant_id', auth()->user()->tenant_id)
                ->whereKey($questionId)
                ->lockForUpdate()
                ->first();

            if (!$question) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Question ID tidak ditemukan pada tenant aktif; import dibatalkan untuk baris ini.'];
                return;
            }

            $user = auth()->user();
            $ownsPersonal = $question->owner_type === 'PERSONAL' && $question->owner_user_id === $user->id;
            $assignedSubject = $question->owner_type === 'SUBJECT'
                && $question->subject_id
                && TeacherAccess::canManageSubject($user, $question->subject_id);
            if (!$ownsPersonal && !$assignedSubject) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Tidak berwenang memperbarui soal ini (owner/assignment tidak sesuai).'];
                return;
            }
            if ($question->status === 'ARCHIVED' || $question->trashed()) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Soal arsip tidak dapat diperbarui.'];
                return;
            }
        }

        $references = $this->validateRowReferences($row, $rowNumber, $question, $results);
        if ($references === null) {
            return;
        }
        $row = array_merge($row, $references);

        $oldValues = $question ? [
            'question_code' => $question->question_code,
            'subject_id' => $question->subject_id,
            'grade_level_id' => $question->grade_level_id,
            'phase_id' => $question->phase_id,
            'curriculum_id' => $question->curriculum_id,
            'current_version_id' => $question->currentVersion?->id,
        ] : null;

        if ($question) {
            $question->update([
                'question_code' => $row['question_code'] ?? $question->question_code,
                'subject_id' => $row['subject_id'] ?? $question->subject_id,
                'grade_level_id' => $row['grade_level_id'] ?? $question->grade_level_id,
                'phase_id' => $row['phase_id'] ?? $question->phase_id,
                'curriculum_id' => $row['curriculum_id'] ?? $question->curriculum_id,
                'updated_by' => auth()->id(),
            ]);
            $current = $question->currentVersion;

            if ($current && $current->status === 'PUBLISHED') {
                // Create New Version
                $question->versions()->update(['is_current' => false]);
                $version = $question->versions()->create([
                    'major_version' => $current->major_version,
                    'minor_version' => $current->minor_version + 1,
                    'question_type' => $row['type'],
                    'stem' => $row['stem'],
                    'explanation' => $row['explanation'] ?? $current->explanation,
                    'learning_outcome_id' => $row['learning_outcome_id'] ?? $current->learning_outcome_id,
                    'learning_objective_id' => $row['learning_objective_id'] ?? $current->learning_objective_id,
                    'element_id' => $row['element_id'] ?? $current->element_id,
                    'difficulty' => $row['difficulty'] ?? $current->difficulty,
                    'default_points' => $row['points'] ?? $current->default_points,
                    'status' => 'DRAFT',
                    'is_current' => true,
                    'created_by' => auth()->id(),
                ]);
            } elseif ($current) {
                if ($current->status !== 'DRAFT') {
                    $results['errors'][] = ['row' => $rowNumber, 'error' => 'Versi soal yang digunakan tidak dapat diubah.'];
                    return;
                }
                $current->update([
                    'stem' => $row['stem'],
                    'question_type' => $row['type'],
                    'explanation' => $row['explanation'] ?? $current->explanation,
                    'learning_outcome_id' => $row['learning_outcome_id'] ?? $current->learning_outcome_id,
                    'learning_objective_id' => $row['learning_objective_id'] ?? $current->learning_objective_id,
                    'element_id' => $row['element_id'] ?? $current->element_id,
                    'difficulty' => $row['difficulty'] ?? $current->difficulty,
                    'default_points' => $row['points'] ?? $current->default_points,
                ]);
                $current->options()->delete();
                $version = $current;
            } else {
                $version = $question->versions()->create([
                    'major_version' => 1,
                    'minor_version' => 0,
                    'question_type' => $row['type'],
                    'stem' => $row['stem'],
                    'explanation' => $row['explanation'] ?? null,
                    'learning_outcome_id' => $row['learning_outcome_id'] ?? null,
                    'learning_objective_id' => $row['learning_objective_id'] ?? null,
                    'element_id' => $row['element_id'] ?? null,
                    'difficulty' => $row['difficulty'] ?? 'MEDIUM',
                    'default_points' => $row['points'] ?? 1,
                    'status' => 'DRAFT',
                    'is_current' => true,
                    'created_by' => auth()->id(),
                ]);
            }
        } else {
            // New questions imported by Guru are subject-owned and require
            // an active assignment in that subject.
            $subjectId = $row['subject_id'] ?? null;
            if (!$subjectId || !TeacherAccess::canManageSubject(auth()->user(), $subjectId)) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Mata pelajaran wajib diisi dan harus berada dalam penugasan aktif Guru.'];
                return;
            }
            $question = Question::create([
                'tenant_id' => auth()->user()->tenant_id,
                'owner_type' => 'SUBJECT',
                'owner_user_id' => null,
                'status' => 'DRAFT',
                'subject_id' => $subjectId,
                'question_code' => $row['question_code'] ?? null,
                'grade_level_id' => $row['grade_level_id'] ?? null,
                'phase_id' => $row['phase_id'] ?? null,
                'curriculum_id' => $row['curriculum_id'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $version = $question->versions()->create([
                'major_version' => 1,
                'minor_version' => 0,
                'question_type' => $row['type'],
                'stem' => $row['stem'],
                'explanation' => $row['explanation'] ?? null,
                'learning_outcome_id' => $row['learning_outcome_id'] ?? null,
                'learning_objective_id' => $row['learning_objective_id'] ?? null,
                'element_id' => $row['element_id'] ?? null,
                'difficulty' => $row['difficulty'] ?? 'MEDIUM',
                'default_points' => $row['points'] ?? 1,
                'status' => 'DRAFT',
                'is_current' => true,
                'created_by' => auth()->id(),
            ]);
        }

        // 4. Options/Metadata Processing
        $this->processOptions($version, $row);

        app(AuditService::class)->log(
            $oldValues ? 'IMPORT_UPDATE_QUESTION' : 'IMPORT_CREATE_QUESTION',
            Question::class,
            (string) $question->id,
            $oldValues,
            [
                'question_code' => $question->question_code,
                'subject_id' => $question->subject_id,
                'version_id' => $version->id,
                'question_type' => $version->question_type,
                'learning_outcome_id' => $version->learning_outcome_id,
                'learning_objective_id' => $version->learning_objective_id,
                'option_count' => count($row['options'] ?? []),
            ]
        );

        $results['imported']++;
    }

    /**
     * Validate and normalize all tenant-scoped references before writing any row.
     */
    protected function validateRowReferences(array $row, int $rowNumber, ?Question $question, array &$results): ?array
    {
        $user = auth()->user();
        $tenantId = $user?->tenant_id;
        if (!$user || !$tenantId) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => 'Sesi Guru tidak tersedia.'];
            return null;
        }

        $subjectId = $row['subject_id'] ?? $question?->subject_id;
        if (!$question && !$subjectId) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => 'Mata pelajaran wajib diisi untuk soal baru.'];
            return null;
        }
        if ($subjectId) {
            $subject = Subject::query()->whereKey($subjectId)->where('tenant_id', $tenantId)->first();
            if (!$subject || !TeacherAccess::canManageSubject($user, $subjectId)) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Mata pelajaran tidak ditemukan pada tenant aktif atau bukan penugasan Guru.'];
                return null;
            }
            if ($question && $question->owner_type === 'SUBJECT' && $question->subject_id !== $subjectId) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Soal tidak boleh dipindahkan ke mata pelajaran lain melalui import.'];
                return null;
            }
        }

        $questionCode = isset($row['question_code']) && trim((string) $row['question_code']) !== ''
            ? strtoupper(trim((string) $row['question_code']))
            : $question?->question_code;
        if ($questionCode !== null && (strlen($questionCode) > 50 || !preg_match('/^[A-Z0-9._-]+$/', $questionCode))) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => 'Kode Soal hanya boleh berisi A-Z, 0-9, titik, garis bawah, atau tanda hubung (maksimal 50 karakter).'];
            return null;
        }
        if ($questionCode !== null) {
            $duplicate = Question::query()
                ->where('tenant_id', $tenantId)
                ->where('question_code', $questionCode)
                ->when($question, fn ($query) => $query->where('id', '<>', $question->id))
                ->exists();
            if ($duplicate) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Kode Soal sudah digunakan oleh soal lain pada tenant ini.'];
                return null;
            }
        }

        $gradeLevelId = $row['grade_level_id'] ?? $question?->grade_level_id;
        $gradeLevel = null;
        if ($gradeLevelId) {
            $gradeLevel = GradeLevel::query()
                ->with('phase')
                ->whereKey($gradeLevelId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'ACTIVE')
                ->first();
            if (!$gradeLevel || !$gradeLevel->phase || $gradeLevel->phase->status !== 'ACTIVE') {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Kelas/fase tidak ditemukan atau tidak aktif pada tenant ini.'];
                return null;
            }
        }

        $outcomeId = $row['learning_outcome_id'] ?? $question?->currentVersion?->learning_outcome_id;
        $outcome = null;
        if ($outcomeId) {
            $outcome = LearningOutcome::query()
                ->with('currentVersion')
                ->whereKey($outcomeId)
                ->where('tenant_id', $tenantId)
                ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
                ->first();
            if (!$outcome || !$outcome->currentVersion || $outcome->currentVersion->status !== 'ACTIVE') {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'CP tidak aktif, tidak ditemukan, atau bukan milik mata pelajaran/tenant ini.'];
                return null;
            }
        }

        $objectiveId = $row['learning_objective_id'] ?? $question?->currentVersion?->learning_objective_id;
        $objective = null;
        if ($objectiveId) {
            if (!$outcomeId) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'TP harus berada di bawah CP yang dipilih.'];
                return null;
            }
            $objective = LearningObjective::query()
                ->whereKey($objectiveId)
                ->where('tenant_id', $tenantId)
                ->where('learning_outcome_id', $outcomeId)
                ->where('status', 'ACTIVE')
                ->first();
            if (!$objective) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'TP tidak aktif atau tidak berada di bawah CP yang dipilih.'];
                return null;
            }
        }

        $phaseId = $row['phase_id'] ?? $gradeLevel?->phase_id ?? $outcome?->phase_id ?? $question?->phase_id;
        if ($gradeLevel && $phaseId !== $gradeLevel->phase_id) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => 'Fase kelas tidak cocok dengan fase soal.'];
            return null;
        }
        if ($outcome && $outcome->phase_id && $phaseId !== $outcome->phase_id) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => 'Fase soal tidak cocok dengan fase CP.'];
            return null;
        }
        if ($phaseId) {
            $phase = Phase::query()->whereKey($phaseId)->where('tenant_id', $tenantId)->where('status', 'ACTIVE')->first();
            if (!$phase) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Fase tidak ditemukan atau tidak aktif pada tenant ini.'];
                return null;
            }
        }

        $curriculumId = $row['curriculum_id'] ?? $gradeLevel?->phase?->curriculum_id ?? $outcome?->curriculum_id ?? $question?->curriculum_id;
        if ($gradeLevel?->phase?->curriculum_id && $curriculumId !== $gradeLevel->phase->curriculum_id) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => 'Kurikulum tidak cocok dengan kelas/fase yang dipilih.'];
            return null;
        }
        if ($outcome?->curriculum_id && $curriculumId !== $outcome->curriculum_id) {
            $results['errors'][] = ['row' => $rowNumber, 'error' => 'Kurikulum tidak cocok dengan CP yang dipilih.'];
            return null;
        }
        if ($curriculumId) {
            $curriculum = Curriculum::query()
                ->whereKey($curriculumId)
                ->where('tenant_id', $tenantId)
                ->where('status', 'ACTIVE')
                ->first();
            if (!$curriculum) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Kurikulum tidak ditemukan atau tidak aktif pada tenant ini.'];
                return null;
            }
        }

        return [
            'question_code' => $questionCode,
            'subject_id' => $subjectId,
            'grade_level_id' => $gradeLevelId,
            'phase_id' => $phaseId,
            'curriculum_id' => $curriculumId,
            'learning_outcome_id' => $outcome?->id,
            'learning_objective_id' => $objective?->id,
            'element_id' => $outcome?->element_id,
        ];
    }

    protected function processOptions(QuestionVersion $version, array $row): void
    {
        if (in_array($row['type'], ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE', 'MATCHING'])) {
            foreach ($row['options'] as $idx => $opt) {
                $optionKey = $opt['option_key'] ?? null;
                if ($optionKey === null && in_array($row['type'], ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE'], true)) {
                    $optionKey = chr(65 + $idx);
                }
                $version->options()->create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'content' => $opt['content'] ?? '',
                    'is_correct' => $opt['is_correct'] ?? false,
                    'option_key' => $optionKey,
                    'matching_key' => $opt['matching_key'] ?? null,
                    'sequence' => $idx
                ]);
            }
        }

    }
}
