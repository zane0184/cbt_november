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
            // Serialize snapshot edits with publish/lock operations. Both paths
            // must lock the same exam row before inspecting or changing snapshots.
            $lockedExam = Exam::query()
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedExam->canBeEdited()) {
                throw new \Exception('Exam is locked and cannot be modified.');
            }

            if (!$append) {
                // Sync is allowed only while the exam remains editable; retain
                // a deterministic sequence for the replacement snapshot set.
                $lockedExam->snapshots()->delete();
                $nextSequence = 0;
            } else {
                $nextSequence = ((int) $lockedExam->snapshots()->max('sequence')) + 1;
            }

            foreach ($questionVersionIds as $versionId) {
                // Ensure the question version belongs to the same tenant
                // Serialize snapshot creation with media attach/detach operations.
                // Media writers lock QuestionVersion before MediaAsset; use the same
                // order here so the snapshot captures one committed version state.
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

                // Prevent duplicate version in same exam if appending
                // The database unique key is (exam_id, source_question_id),
                // therefore deduplicate on question identity, not version ID.
                if ($lockedExam->snapshots()->where('source_question_id', $version->question_id)->exists()) {
                    continue;
                }

                $snapshot = new \App\Models\ExamQuestionSnapshot([
                    'id' => (string) Str::uuid(),
                    'source_question_id' => $version->question_id,
                    'source_question_version_id' => $version->id,
                    'sequence' => $nextSequence,
                    'points' => $version->default_points,
                    // DEEP COPY CONTENT
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
                        'mime_type' => $m->mime_type
                    ])->toArray(),
                    'metadata' => [
                        'difficulty' => $version->difficulty,
                        'cognitive_level' => $version->cognitive_level,
                        'question_type' => $version->question_type,
                    ]
                ]);
                $snapshot->exam_id = $lockedExam->id;
                $snapshot->tenant_id = $lockedExam->tenant_id;
                $snapshot->save();
                $nextSequence++;
            }
        });
    }
}
