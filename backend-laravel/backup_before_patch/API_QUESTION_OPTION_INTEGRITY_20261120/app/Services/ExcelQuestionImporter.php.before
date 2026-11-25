<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuestionVersion;
use App\Support\Security\TeacherAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExcelQuestionImporter implements QuestionImportService
{
    private const ALLOWED_TYPES = [
        'MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE',
        'MATCHING', 'SHORT_ANSWER', 'ESSAY'
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

        try {
            DB::transaction(function () use ($rows, &$results) {
                foreach ($rows as $index => $row) {
                    $this->processRow($row, $index + 1, $results);
                }

                if (!empty($results['errors'])) {
                    throw new \Exception('Validation errors occurred.');
                }
            });
        } catch (\Exception $e) {
            $results['success'] = false;
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

        // 2. Type-Specific Validation
        if (in_array($row['type'], ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE'])) {
            if (empty($row['options']) || !is_array($row['options'])) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Options are required for this question type.'];
                return;
            }
            $hasCorrect = collect($row['options'])->contains('is_correct', true);
            if (!$hasCorrect) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'At least one correct option is required.'];
                return;
            }
        }

        if ($row['type'] === 'MATCHING') {
            if (empty($row['options']) || !is_array($row['options'])) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Pairs are required for matching.'];
                return;
            }
            // Check if there are left and right keys
            $hasMapping = collect($row['options'])->every(fn($opt) => !empty($opt['option_key']) || !empty($opt['matching_key']));
            if (!$hasMapping) {
                $results['errors'][] = ['row' => $rowNumber, 'error' => 'Matching items must have keys for pairing.'];
                return;
            }
        }

        // 3. Question & Versioning Logic
        $questionId = $row['question_id'] ?? null;
        $question = null;

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

        if ($question) {
            $current = $question->currentVersion;

            if ($current && $current->status === 'PUBLISHED') {
                // Create New Version
                $question->versions()->update(['is_current' => false]);
                $version = $question->versions()->create([
                    'major_version' => $current->major_version,
                    'minor_version' => $current->minor_version + 1,
                    'question_type' => $row['type'],
                    'stem' => $row['stem'],
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
                'created_by' => auth()->id(),
            ]);

            $version = $question->versions()->create([
                'major_version' => 1,
                'minor_version' => 0,
                'question_type' => $row['type'],
                'stem' => $row['stem'],
                'difficulty' => $row['difficulty'] ?? 'MEDIUM',
                'default_points' => $row['points'] ?? 1,
                'status' => 'DRAFT',
                'is_current' => true,
                'created_by' => auth()->id(),
            ]);
        }

        // 4. Options/Metadata Processing
        $this->processOptions($version, $row);

        $results['imported']++;
    }

    protected function processOptions(QuestionVersion $version, array $row): void
    {
        if (in_array($row['type'], ['MULTIPLE_CHOICE', 'MULTIPLE_RESPONSE', 'TRUE_FALSE', 'MATCHING'])) {
            foreach ($row['options'] as $idx => $opt) {
                $version->options()->create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'content' => $opt['content'] ?? '',
                    'is_correct' => $opt['is_correct'] ?? false,
                    'option_key' => $opt['option_key'] ?? null,
                    'matching_key' => $opt['matching_key'] ?? null,
                    'sequence' => $idx
                ]);
            }
        }

        if ($row['type'] === 'SHORT_ANSWER') {
            // Short answer accepted answers stored in metadata per Phase 4E logic
            $version->update([
                'metadata' => array_merge($version->metadata ?? [], [
                    'accepted_answers' => $row['accepted_answers'] ?? []
                ])
            ]);
        }
    }
}
