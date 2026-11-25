<?php

namespace App\Services\Questions;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Models\TeacherSubjectAssignment;
use App\Services\AuditService;
use App\Services\TeacherPortal\QuestionVersionCurriculumMappingService;
use App\Support\Security\TeacherAccess;
use App\Support\Security\TeacherPortalAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocxQuestionDraftImporter
{
    private const IMPORTABLE_TYPES = [
        'MULTIPLE_CHOICE',
        'MULTIPLE_RESPONSE',
        'TRUE_FALSE',
        'TRUE_FALSE_GROUP',
        'ESSAY',
    ];

    public function import(
        array $questions,
        string $fileSha256,
        string $assignmentId,
        array $cpIds,
        array $outcomeIds,
        ?string $materialId = null,
        ?string $topicId = null
    ): array {
        $user = auth()->user();
        abort_unless(
            $user
            && $user->tenant_id
            && $user->hasExactRole('GURU')
            && $user->hasPermission('question.manage')
            && $user->hasPermission('curriculum.manage'),
            403,
            'Hanya Guru dengan penugasan dan kewenangan pengelolaan soal serta kurikulum yang dapat mengimpor DOCX.'
        );

        $profile = app(TeacherPortalAccess::class)->profile($user);
        $tenantId = (string) $user->tenant_id;

        return DB::transaction(function () use (
            $questions, $fileSha256, $assignmentId, $cpIds, $outcomeIds,
            $materialId, $topicId, $user, $profile, $tenantId
        ): array {
            $assignment = TeacherSubjectAssignment::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($assignmentId)
                ->where('teacher_profile_id', $profile->id)
                ->where('status', 'ACTIVE')
                ->activeForDate()
                ->with('subject')
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $assignment->subject && TeacherAccess::canManageSubject($user, (string) $assignment->subject_id),
                403,
                'Mata pelajaran tidak berada dalam penugasan aktif Guru.'
            );

            $existingBatch = DB::table('question_docx_import_batches')
                ->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->where('file_sha256', $fileSha256)
                ->lockForUpdate()
                ->first();

            if ($existingBatch) {
                throw ValidationException::withMessages([
                    'docx' => 'Dokumen DOCX dengan isi yang sama sudah pernah diimpor ke penugasan ini.',
                ]);
            }

            $ready = array_values(array_filter(
                $questions,
                fn (array $question): bool => (bool) ($question['included'] ?? true)
            ));

            if ($ready === []) {
                throw ValidationException::withMessages(['docx' => 'Pilih minimal satu soal untuk diimpor.']);
            }

            foreach ($ready as $index => $row) {
                $this->validateQuestion($row, $index + 1);
                $effectiveCpIds = array_values(array_unique(!empty($row['mapping_cp_ids']) ? $row['mapping_cp_ids'] : $cpIds));
                $effectiveOutcomeIds = array_values(array_unique(!empty($row['mapping_outcome_ids']) ? $row['mapping_outcome_ids'] : $outcomeIds));
                if ($effectiveCpIds === [] || $effectiveOutcomeIds === []) {
                    throw ValidationException::withMessages([
                        'docx' => 'Setiap soal yang disertakan wajib memiliki minimal satu CP dan satu TP/ATP. Atur pemetaan default atau pemetaan khusus pada kartu soal.',
                    ]);
                }
            }

            $mappingService = app(QuestionVersionCurriculumMappingService::class);
            $importedIds = [];

            foreach ($ready as $row) {
                $questionCpIds = array_values(array_unique(!empty($row['mapping_cp_ids']) ? $row['mapping_cp_ids'] : $cpIds));
                $questionOutcomeIds = array_values(array_unique(!empty($row['mapping_outcome_ids']) ? $row['mapping_outcome_ids'] : $outcomeIds));
                $question = Question::query()->create([
                    'tenant_id' => $tenantId,
                    'owner_type' => 'SUBJECT',
                    'owner_user_id' => null,
                    'question_code' => 'DOCX-' . strtoupper(Str::random(12)),
                    'status' => 'DRAFT',
                    'subject_id' => $assignment->subject_id,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                $metadata = [
                    'import_source' => 'DOCX',
                    'source_file_sha256' => $fileSha256,
                    'source_question_number' => (int) ($row['source_number'] ?? 0),
                    'source_locator' => $row['source_locator'] ?? [],
                ];

                if (($row['question_type'] ?? null) === 'ESSAY') {
                    $metadata['grading_mode'] = 'MANUAL_REVIEW';
                }

                if (($row['question_type'] ?? null) === 'TRUE_FALSE_GROUP') {
                    $metadata['statement_items'] = array_values($row['statements']);
                    $metadata['statement_scoring'] = 'PROPORTIONAL_PER_STATEMENT_V1';
                }

                if (!empty($row['stimulus']['content'])) {
                    $stimulusContent = trim((string) $row['stimulus']['content']);
                    $metadata['shared_stimulus'] = [
                        'group_id' => hash('sha256', $fileSha256 . '|' . ($row['stimulus']['id'] ?? 'stimulus')),
                        'source_group_key' => (string) ($row['stimulus']['id'] ?? ''),
                        'version' => 1,
                        'content' => $stimulusContent,
                        'content_sha256' => hash('sha256', $stimulusContent),
                        'source_locator' => $row['stimulus']['source'] ?? [],
                    ];
                }

                $version = QuestionVersion::query()->create([
                    'tenant_id' => $tenantId,
                    'question_id' => $question->id,
                    'major_version' => 1,
                    'minor_version' => 0,
                    'question_type' => $row['question_type'],
                    'stem' => trim((string) $row['stem']),
                    'explanation' => null,
                    'metadata' => $metadata,
                    'difficulty' => $row['difficulty'] ?? 'MEDIUM',
                    'default_points' => (float) ($row['points'] ?? 1),
                    'status' => 'DRAFT',
                    'is_current' => true,
                    'created_by' => $user->id,
                ]);

                if (in_array($row['question_type'], ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE'], true)) {
                    foreach (array_values($row['options']) as $sequence => $option) {
                        QuestionOption::query()->create([
                            'tenant_id' => $tenantId,
                            'question_version_id' => $version->id,
                            'option_key' => $option['option_key'],
                            'content' => trim((string) $option['content']),
                            'sequence' => $sequence + 1,
                            'is_correct' => (bool) ($option['is_correct'] ?? false),
                            'score_weight' => (bool) ($option['is_correct'] ?? false) ? 1 : 0,
                        ]);
                    }
                }

                $mappingService->sync(
                    $version,
                    (string) $assignment->id,
                    $questionCpIds,
                    $questionOutcomeIds,
                    $materialId,
                    $topicId
                );

                $importedIds[] = $question->id;
                app(AuditService::class)->log(
                    'QUESTION_IMPORTED_FROM_DOCX',
                    Question::class,
                    (string) $question->id,
                    null,
                    [
                        'question_version_id' => $version->id,
                        'question_type' => $version->question_type,
                        'teacher_subject_assignment_id' => $assignment->id,
                        'source_file_sha256' => $fileSha256,
                        'source_question_number' => (int) ($row['source_number'] ?? 0),
                        'cp_mapping_count' => count($questionCpIds),
                        'outcome_mapping_count' => count($questionOutcomeIds),
                    ]
                );
            }

            DB::table('question_docx_import_batches')->insert([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'uploaded_by' => $user->id,
                'teacher_subject_assignment_id' => $assignment->id,
                'file_sha256' => $fileSha256,
                'imported_count' => count($importedIds),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [
                'imported' => count($importedIds),
                'question_ids' => $importedIds,
                'batch_sha256' => $fileSha256,
            ];
        }, 3);
    }

    private function validateQuestion(array $row, int $index): void
    {
        $prefix = 'Soal ' . $index . ': ';
        $type = $row['question_type'] ?? '';

        if (!in_array($type, self::IMPORTABLE_TYPES, true)) {
            throw ValidationException::withMessages([
                'docx' => $prefix . 'jenis soal belum didukung untuk penyimpanan otomatis.',
            ]);
        }

        if (trim((string) ($row['stem'] ?? '')) === '') {
            throw ValidationException::withMessages(['docx' => $prefix . 'teks soal wajib diisi.']);
        }
        $points = (float) ($row['points'] ?? 1);
        if ($points < 0.5 || $points > 100) {
            throw ValidationException::withMessages(['docx' => $prefix . 'poin harus berada pada rentang 0,5 sampai 100.']);
        }
        if (!in_array($row['difficulty'] ?? 'MEDIUM', ['EASY', 'MEDIUM', 'HARD'], true)) {
            throw ValidationException::withMessages(['docx' => $prefix . 'tingkat kesulitan tidak valid.']);
        }

        if ($type === 'TRUE_FALSE_GROUP') {
            $statements = $row['statements'] ?? [];
            if (count($statements) !== 3) {
                throw ValidationException::withMessages(['docx' => $prefix . 'Benar/Salah kelompok wajib tepat tiga pernyataan.']);
            }
            $statementIds = array_map(fn (array $statement): string => trim((string) ($statement['id'] ?? '')), $statements);
            if (in_array('', $statementIds, true) || count(array_unique($statementIds)) !== 3) {
                throw ValidationException::withMessages(['docx' => $prefix . 'Setiap pernyataan wajib memiliki ID stabil yang unik.']);
            }
            foreach ($statements as $statement) {
                if (trim((string) ($statement['text'] ?? '')) === '' || !is_bool($statement['is_true'] ?? null)) {
                    throw ValidationException::withMessages(['docx' => $prefix . 'setiap pernyataan Benar/Salah harus memiliki teks dan kunci boolean.']);
                }
            }
            return;
        }

        if ($type === 'ESSAY') {
            if (($row['options'] ?? []) !== []) {
                throw ValidationException::withMessages(['docx' => $prefix . 'soal uraian tidak boleh memiliki opsi pilihan ganda.']);
            }
            return;
        }

        if ($type === 'TRUE_FALSE') {
            $options = $row['options'] ?? [];
            if (count($options) !== 2
                || count(array_filter($options, fn (array $option) => (bool) ($option['is_correct'] ?? false))) !== 1) {
                throw ValidationException::withMessages(['docx' => $prefix . 'Benar/Salah tunggal wajib memiliki satu kunci benar.']);
            }
            return;
        }

        $options = $row['options'] ?? [];
        if (count($options) < 2 || count($options) > 4) {
            throw ValidationException::withMessages(['docx' => $prefix . 'PG/PGK wajib memiliki 2 sampai 4 opsi.']);
        }

        $keys = [];
        $correct = 0;
        foreach ($options as $option) {
            $key = strtoupper(trim((string) ($option['option_key'] ?? '')));
            if (!in_array($key, ['A', 'B', 'C', 'D'], true)
                || isset($keys[$key])
                || trim((string) ($option['content'] ?? '')) === '') {
                throw ValidationException::withMessages(['docx' => $prefix . 'label opsi harus unik A-D dan isi opsi wajib lengkap.']);
            }
            $keys[$key] = true;
            $correct += (bool) ($option['is_correct'] ?? false) ? 1 : 0;
        }

        if (($type === 'MULTIPLE_CHOICE' && $correct !== 1)
            || ($type === 'MULTIPLE_RESPONSE' && $correct < 1)) {
            throw ValidationException::withMessages(['docx' => $prefix . 'jumlah kunci jawaban tidak sesuai dengan tipe soal.']);
        }
    }
}
