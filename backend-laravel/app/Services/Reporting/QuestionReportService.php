<?php

namespace App\Services\Reporting;

use App\Models\Exam;
use App\Models\ExamQuestionSnapshot;
use App\Models\ExamQuestionScore;
use Illuminate\Support\Facades\DB;

class QuestionReportService
{
    /**
     * Get basic report for all questions in an exam.
     */
    public function getQuestionReport(Exam $exam): array
    {
        $snapshots = $exam->snapshots()->orderBy('sequence')->get();

        return $snapshots->map(function ($snapshot) {
            $stats = ExamQuestionScore::query()
                ->where('tenant_id', $exam->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('snapshot_id', $snapshot->id)
                ->select([
                    DB::raw('COUNT(*) as total_responses'),
                    DB::raw('SUM(CASE WHEN awarded_score > 0 THEN 1 ELSE 0 END) as correct_count'),
                    DB::raw('AVG(awarded_score) as average_score'),
                ])
                ->first();

            // Check how many actually answered (answer_state = 'ANSWERED')
            $answeredCount = \App\Models\ExamAttemptAnswer::query()
                ->where('tenant_id', $exam->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('exam_question_snapshot_id', $snapshot->id)
                ->where('answer_state', 'ANSWERED')
                ->count();

            $participantCount = $snapshot->exam->participants()->where('exam_participants.tenant_id', $exam->tenant_id)->count();

            return [
                'snapshot_id' => $snapshot->id,
                'sequence' => $snapshot->sequence + 1,
                'question_type' => $snapshot->metadata['question_type'] ?? 'MC',
                'max_points' => $snapshot->points,
                'total_responses' => $stats->total_responses ?? 0,
                'answered_count' => $answeredCount,
                'not_answered_count' => $participantCount - $answeredCount,
                'correct_count' => (int) ($stats->correct_count ?? 0),
                'average_score' => round($stats->average_score ?? 0, 2),
            ];
        })->toArray();
    }
}
