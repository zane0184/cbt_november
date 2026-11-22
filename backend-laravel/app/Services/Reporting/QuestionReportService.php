<?php

namespace App\Services\Reporting;

use App\Models\Exam;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamAttemptScore;
use App\Models\ExamQuestionScore;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder as QueryBuilder;

class QuestionReportService
{
    /**
     * Report question statistics only from each attempt's authoritative finalized score version.
     */
    public function getQuestionReport(Exam $exam): array
    {
        $snapshots = $exam->snapshots()->orderBy('sequence')->get();

        $activeAttemptIds = ExamAttemptScore::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('grading_status', 'FINALIZED')
            ->current()
            ->whereHas('attempt', function ($query): void {
                $query->whereIn('status', ['SUBMITTED', 'EXPIRED']);
            })
            ->pluck('attempt_id')
            ->all();

        return $snapshots->map(function ($snapshot) use ($exam, $activeAttemptIds): array {
            $stats = ExamQuestionScore::query()
                ->where('tenant_id', $exam->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('snapshot_id', $snapshot->id)
                ->whereIn('attempt_id', $activeAttemptIds)
                ->whereExists(function (QueryBuilder $pointerQuery): void {
                    $pointerQuery->selectRaw('1')
                        ->from('exam_attempt_score_current as current_score_pointer')
                        ->whereColumn('current_score_pointer.attempt_id', 'exam_question_scores.attempt_id')
                        ->whereColumn('current_score_pointer.tenant_id', 'exam_question_scores.tenant_id')
                        ->whereColumn('current_score_pointer.scoring_version', 'exam_question_scores.scoring_version');
                })
                ->select([
                    DB::raw('COUNT(*) as total_responses'),
                    DB::raw('SUM(CASE WHEN awarded_score > 0 THEN 1 ELSE 0 END) as correct_count'),
                    DB::raw('AVG(awarded_score) as average_score'),
                ])
                ->first();

            $answeredCount = ExamAttemptAnswer::query()
                ->where('tenant_id', $exam->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('exam_attempt_id', $activeAttemptIds)
                ->where('exam_question_snapshot_id', $snapshot->id)
                ->where('answer_state', 'ANSWERED')
                ->count();

            $participantCount = count($activeAttemptIds);

            return [
                'snapshot_id' => $snapshot->id,
                'sequence' => $snapshot->sequence + 1,
                'question_type' => $snapshot->metadata['question_type'] ?? 'MC',
                'max_points' => $snapshot->points,
                'total_responses' => (int) ($stats->total_responses ?? 0),
                'answered_count' => $answeredCount,
                'not_answered_count' => max(0, $participantCount - $answeredCount),
                'correct_count' => (int) ($stats->correct_count ?? 0),
                'average_score' => round((float) ($stats->average_score ?? 0), 2),
            ];
        })->toArray();
    }
}
