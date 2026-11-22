<?php

namespace App\Services\Analytics;

use App\Models\Exam;
use App\Models\ExamAttemptScore;
use App\Models\ExamQuestionScore;
use App\Models\ExamQuestionSnapshot;
use App\Models\ExamAttemptAnswer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class ItemAnalysisService
{
    /**
     * Analyze items from one authoritative finalized score per submitted attempt.
     * Question scores are matched by attempt_id and the exact active scoring_version.
     */
    public function getAnalysis(Exam $exam): array
    {
        $snapshots = $exam->snapshots()->orderBy('sequence')->get();
        if ($snapshots->isEmpty()) {
            return [];
        }

        $participants = ExamAttemptScore::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('grading_status', 'FINALIZED')
            ->current()
            ->whereHas('attempt', function ($query): void {
                $query->whereIn('status', ['SUBMITTED', 'EXPIRED']);
            })
            ->orderByDesc('percentage')
            ->orderBy('attempt_id')
            ->get(['id', 'attempt_id', 'student_id', 'scoring_version', 'percentage']);

        $participantCount = $participants->count();
        if ($participantCount === 0) {
            return $snapshots->map(
                fn (ExamQuestionSnapshot $snapshot): array => $this->emptyAnalysis($snapshot, 'no_participants')
            )->toArray();
        }

        $canCalculateDiscrimination = $participantCount >= 20;
        $groupSize = $canCalculateDiscrimination ? (int) round($participantCount * 0.27) : 0;
        $allAttemptIds = $participants->pluck('attempt_id')->all();
        $topAttemptIds = $canCalculateDiscrimination
            ? $participants->take($groupSize)->pluck('attempt_id')->all()
            : [];
        $bottomAttemptIds = $canCalculateDiscrimination
            ? $participants->take(-$groupSize)->pluck('attempt_id')->all()
            : [];

        $difficultyStats = $this->currentQuestionScores($exam, $allAttemptIds)
            ->groupBy('snapshot_id')
            ->select('snapshot_id', DB::raw('AVG(awarded_score) as avg_score'))
            ->get()
            ->keyBy('snapshot_id');

        $topStats = collect();
        $bottomStats = collect();
        if ($canCalculateDiscrimination) {
            $topStats = $this->currentQuestionScores($exam, $topAttemptIds)
                ->groupBy('snapshot_id')
                ->select('snapshot_id', DB::raw('AVG(awarded_score) as avg_score'))
                ->get()
                ->keyBy('snapshot_id');

            $bottomStats = $this->currentQuestionScores($exam, $bottomAttemptIds)
                ->groupBy('snapshot_id')
                ->select('snapshot_id', DB::raw('AVG(awarded_score) as avg_score'))
                ->get()
                ->keyBy('snapshot_id');
        }

        $responseCounts = ExamAttemptAnswer::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('exam_attempt_id', $allAttemptIds)
            ->where('answer_state', 'ANSWERED')
            ->groupBy('exam_question_snapshot_id')
            ->select('exam_question_snapshot_id', DB::raw('COUNT(*) as count'))
            ->get()
            ->keyBy('exam_question_snapshot_id');

        return $snapshots->map(function (ExamQuestionSnapshot $snapshot) use (
            $participantCount,
            $canCalculateDiscrimination,
            $difficultyStats,
            $topStats,
            $bottomStats,
            $responseCounts
        ): array {
            $maxPoints = (float) $snapshot->points;
            $difficultyStat = $difficultyStats->get($snapshot->id);
            $difficulty = ($difficultyStat && $maxPoints > 0)
                ? round((float) $difficultyStat->avg_score / $maxPoints, 3)
                : null;

            $discrimination = null;
            $discriminationStatus = $canCalculateDiscrimination ? 'success' : 'insufficient_sample';

            if ($canCalculateDiscrimination && $maxPoints > 0) {
                $topAverage = (float) ($topStats->get($snapshot->id)->avg_score ?? 0);
                $bottomAverage = (float) ($bottomStats->get($snapshot->id)->avg_score ?? 0);
                $discrimination = round(($topAverage / $maxPoints) - ($bottomAverage / $maxPoints), 3);
            }

            return [
                'question_snapshot_id' => $snapshot->id,
                'sequence' => $snapshot->sequence + 1,
                'question_type' => $snapshot->metadata['question_type'] ?? 'MC',
                'max_points' => $maxPoints,
                'response_count' => (int) ($responseCounts->get($snapshot->id)->count ?? 0),
                'difficulty_index' => $difficulty,
                'difficulty_status' => $this->interpretDifficulty($difficulty),
                'discrimination_index' => $discrimination,
                'discrimination_status' => $discriminationStatus,
            ];
        })->toArray();
    }

    /**
     * Question-level scores must match the attempt's active pointer version.
     * The attempt IDs are already drawn from valid, finalized current scores.
     */
    private function currentQuestionScores(Exam $exam, array $attemptIds): Builder
    {
        return ExamQuestionScore::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('attempt_id', $attemptIds)
            ->whereExists(function (QueryBuilder $pointerQuery): void {
                $pointerQuery->selectRaw('1')
                    ->from('exam_attempt_score_current as current_score_pointer')
                    ->whereColumn('current_score_pointer.attempt_id', 'exam_question_scores.attempt_id')
                    ->whereColumn('current_score_pointer.tenant_id', 'exam_question_scores.tenant_id')
                    ->whereColumn('current_score_pointer.scoring_version', 'exam_question_scores.scoring_version');
            });
    }

    protected function emptyAnalysis(ExamQuestionSnapshot $snapshot, string $status): array
    {
        return [
            'question_snapshot_id' => $snapshot->id,
            'sequence' => $snapshot->sequence + 1,
            'question_type' => $snapshot->metadata['question_type'] ?? 'MC',
            'max_points' => (float) $snapshot->points,
            'response_count' => 0,
            'difficulty_index' => null,
            'difficulty_status' => null,
            'discrimination_index' => null,
            'discrimination_status' => $status,
        ];
    }

    protected function interpretDifficulty(?float $difficulty): ?string
    {
        if ($difficulty === null) {
            return null;
        }
        if ($difficulty < 0.30) {
            return 'Sulit';
        }
        if ($difficulty <= 0.70) {
            return 'Sedang';
        }

        return 'Mudah';
    }
}
