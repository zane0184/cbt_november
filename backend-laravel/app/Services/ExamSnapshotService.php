<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\QuestionVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamSnapshotService
{
    public function createSnapshots(Exam $exam, array $questionVersionIds, bool $append = false): void
    {
        DB::transaction(function () use ($exam, $questionVersionIds, $append) {
            // Serialize snapshot writes with publish and all other snapshot mutations.
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedExam->canBeEdited()) {
                abort(409, 'Exam is locked and cannot be modified.');
            }

            // Fail closed if attempts or legacy answer/score history already exist.
            $existingSnapshotIds = $lockedExam->snapshots()->pluck('id')->all();
            $hasSnapshotHistory = $lockedExam->attempts()->exists();
            if (!$hasSnapshotHistory && $existingSnapshotIds !== []) {
                $hasSnapshotHistory = DB::table('exam_attempt_answers')
                    ->where('tenant_id', $lockedExam->tenant_id)
                    ->where('exam_id', $lockedExam->id)
                    ->whereIn('exam_question_snapshot_id', $existingSnapshotIds)
                    ->exists();
            }
            if (!$hasSnapshotHistory && $existingSnapshotIds !== []) {
                $hasSnapshotHistory = DB::table('exam_question_scores')
                    ->where('tenant_id', $lockedExam->tenant_id)
                    ->where('exam_id', $lockedExam->id)
                    ->whereIn('snapshot_id', $existingSnapshotIds)
                    ->exists();
            }
            if ($hasSnapshotHistory) {
                abort(409, 'Exam snapshots cannot be changed after attempt, answer, or score history exists.');
            }

            if (!$append) {
                $lockedExam->snapshots()->delete();
                $nextSequence = 0;
            } else {
                $nextSequence = ((int) $lockedExam->snapshots()->max('sequence')) + 1;
            }

            foreach ($questionVersionIds as $versionId) {
                $version = QuestionVersion::query()
                    ->where('tenant_id', $lockedExam->tenant_id)
                    ->whereKey($versionId)
                    ->lockForUpdate()
                    ->firstOrFail();
                $options = $version->options()->orderBy('sequence')->get();
                $media = $version->media()
                    ->where('media_assets.tenant_id', $lockedExam->tenant_id)
                    ->orderBy('media_assets.id')
                    ->lockForUpdate()
                    ->get();

                if ($lockedExam->snapshots()->where('source_question_id', $version->question_id)->exists()) {
                    continue;
                }

                $snapshot = new \App\Models\ExamQuestionSnapshot([
                    'id' => (string) Str::uuid(),
                    'source_question_id' => $version->question_id,
                    'source_question_version_id' => $version->id,
                    'sequence' => $nextSequence,
                    'points' => $version->default_points,
                    'snapshot_stem' => $version->stem,
                    'snapshot_explanation' => $version->explanation,
                    'snapshot_options' => $options->map(fn($opt) => [
                        'id' => $opt->id,
                        'content' => $opt->content,
                        'is_correct' => $opt->is_correct,
                        'score_weight' => $opt->score_weight,
                        'option_key' => $opt->option_key,
                        'matching_key' => $opt->matching_key,
                    ])->toArray(),
                    'snapshot_media' => $media->map(fn($m) => [
                        'id' => $m->id,
                        'storage_path' => $m->storage_path,
                        'role' => $m->pivot->role,
                        'mime_type' => $m->mime_type,
                    ])->toArray(),
                    'metadata' => [
                        'difficulty' => $version->difficulty,
                        'cognitive_level' => $version->cognitive_level,
                        'question_type' => $version->question_type,
                    ],
                ]);
                $snapshot->exam_id = $lockedExam->id;
                $snapshot->tenant_id = $lockedExam->tenant_id;
                $snapshot->save();
                $nextSequence++;
            }
        });
    }
}
