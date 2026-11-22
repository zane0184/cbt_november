<?php

namespace App\Services\Analytics;

use App\Models\Exam;
use App\Models\ExamAttemptScore;
use App\Models\ExamQuestionScore;
use App\Models\ExamQuestionSnapshot;
use App\Models\ExamAttemptAnswer;
use Illuminate\Support\Facades\DB;

class ItemAnalysisService
{
    /**
     * Calculate item metrics from the current finalized score version of each
     * terminal attempt only. Historical score/question-score versions are excluded.
     */
    public function getAnalysis(Exam $exam): array
    {
        $snapshots = $exam->snapshots()->orderBy('sequence')->get();
        if ($snapshots->isEmpty()) {
            return [];
        }

        $participants = ExamAttemptScore::query()
            ->join('exam_attempt_score_current as current_pointer', function ($join) {
                $join->on('current_pointer.attempt_id', '=', 'exam_attempt_scores.attempt_id')
                    ->on('current_pointer.current_score_id', '=', 'exam_attempt_scores.id')
                    ->on('current_pointer.tenant_id', '=', 'exam_attempt_scores.tenant_id')
                    ->on('current_pointer.scoring_version', '=', 'exam_attempt_scores.scoring_version');
            })
            ->join('exam_attempts as score_attempts', function ($join) {
                $join->on('score_attempts.id', '=', 'exam_attempt_scores.attempt_id')
                    ->on('score_attempts.tenant_id', '=', 'exam_attempt_scores.tenant_id');
            })
            ->where('exam_attempt_scores.tenant_id', $exam->tenant_id)
            ->where('exam_attempt_scores.exam_id', $exam->id)
            ->where('exam_attempt_scores.grading_status', 'FINALIZED')
            ->whereIn('score_attempts.status', ['SUBMITTED', 'EXPIRED'])
            ->orderByDesc('exam_attempt_scores.percentage')
            ->orderBy('exam_attempt_scores.student_id')
            ->orderBy('exam_attempt_scores.attempt_id')
            ->select('exam_attempt_scores.*')
            ->get();

        $attemptIds = $participants->pluck('attempt_id')->all();
        $attemptCount = count($attemptIds);
        if ($attemptCount === 0) {
            return $snapshots->map(fn ($snapshot) => $this->emptyAnalysis($snapshot, 'no_participants'))->toArray();
        }

        $canCalculateDiscrimination = $attemptCount >= 20;
        $groupSize = $canCalculateDiscrimination ? (int) round($attemptCount * 0.27) : 0;

        $topAttemptIds = $canCalculateDiscrimination
            ? $participants->take($groupSize)->pluck('attempt_id')->all()
            : [];
        $bottomAttemptIds = $canCalculateDiscrimination
            ? $participants->take(-$groupSize)->pluck('attempt_id')->all()
            : [];

        $difficultyStats = $this->currentQuestionScores($exam, $attemptIds)
            ->groupBy('exam_question_scores.snapshot_id')
            ->select('exam_question_scores.snapshot_id', DB::raw('AVG(exam_question_scores.awarded_score) as avg_score'))
            ->get()
            ->keyBy('snapshot_id');

        $topStats = collect();
        $bottomStats = collect();
        if ($canCalculateDiscrimination) {
            $topStats = $this->currentQuestionScores($exam, $topAttemptIds)
                ->groupBy('exam_question_scores.snapshot_id')
                ->select('exam_question_scores.snapshot_id', DB::raw('AVG(exam_question_scores.awarded_score) as avg_score'))
                ->get()
                ->keyBy('snapshot_id');

            $bottomStats = $this->currentQuestionScores($exam, $bottomAttemptIds)
                ->groupBy('exam_question_scores.snapshot_id')
                ->select('exam_question_scores.snapshot_id', DB::raw('AVG(exam_question_scores.awarded_score) as avg_score'))
                ->get()
                ->keyBy('snapshot_id');
        }

        $responseCounts = ExamAttemptAnswer::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('exam_attempt_id', $attemptIds)
            ->where('answer_state', 'ANSWERED')
            ->groupBy('exam_question_snapshot_id')
            ->select('exam_question_snapshot_id', DB::raw('COUNT(*) as count'))
            ->get()
            ->keyBy('exam_question_snapshot_id');

        return $snapshots->map(function ($snapshot) use (
            $attemptCount,
            $canCalculateDiscrimination,
            $difficultyStats,
            $topStats,
            $bottomStats,
            $responseCounts
        ) {
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

    private function currentQuestionScores(Exam $exam, array $attemptIds)
    {
        return ExamQuestionScore::query()
            ->join('exam_attempt_score_current as current_pointer', function ($join) {
                $join->on('current_pointer.attempt_id', '=', 'exam_question_scores.attempt_id')
                    ->on('current_pointer.tenant_id', '=', 'exam_question_scores.tenant_id')
                    ->on('current_pointer.scoring_version', '=', 'exam_question_scores.scoring_version');
            })
            ->where('exam_question_scores.tenant_id', $exam->tenant_id)
            ->where('exam_question_scores.exam_id', $exam->id)
            ->whereIn('exam_question_scores.attempt_id', $attemptIds);
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

    protected function interpretDifficulty(?float $percentage): ?string
    {
        if ($percentage === null) {
            return null;
        }

        if ($percentage < 0.30) {
            return 'Sulit';
        }

        return $percentage <= 0.70 ? 'Sedang' : 'Mudah';
    }
}
