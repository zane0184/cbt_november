<?php

namespace App\Services\Analytics;

use App\Models\Rombel;
use App\Models\Exam;
use App\Models\ExamAttemptScore;
use Illuminate\Support\Facades\DB;

class RombelAnalyticsService
{
    /**
     * Get analytics for a specific Rombel in an exam.
     */
    public function getRombelExamAnalytics(Rombel $rombel, Exam $exam): array
    {
        abort_unless($rombel->tenant_id === $exam->tenant_id, 404);

        $query = ExamAttemptScore::query()
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
            ->select('exam_attempt_scores.*')
            ->whereHas('student', function ($studentQuery) use ($rombel, $exam) {
                $studentQuery->where('tenant_id', $exam->tenant_id)
                    ->whereHas('studentProfile', function ($profileQuery) use ($rombel, $exam) {
                        $profileQuery->where('tenant_id', $exam->tenant_id)
                            ->whereHas('rombels', function ($rombelQuery) use ($rombel, $exam) {
                                $rombelQuery->where('rombels.id', $rombel->id)
                                    ->where('rombels.tenant_id', $exam->tenant_id)
                                    ->where('rombels.academic_year_id', $exam->assessment?->academic_year_id);
                            });
                    });
            });

        $stats = $query->select([
            DB::raw('COUNT(*) as participant_count'),
            DB::raw('AVG(exam_attempt_scores.percentage) as average_percentage'),
            DB::raw('MAX(exam_attempt_scores.percentage) as highest_percentage'),
            DB::raw('MIN(exam_attempt_scores.percentage) as lowest_percentage'),
            DB::raw('STDDEV_POP(exam_attempt_scores.percentage) as std_dev_percentage'),
        ])->first();

        $percentages = $query->orderBy('exam_attempt_scores.percentage')->pluck('exam_attempt_scores.percentage')->toArray();
        $median = $this->calculateMedian($percentages);

        return [
            'rombel_name' => $rombel->name,
            'exam_title' => $exam->title,
            'participant_count' => (int) ($stats->participant_count ?? 0),
            'average_percentage' => $stats->average_percentage !== null ? round((float) $stats->average_percentage, 2) : null,
            'median_percentage' => $median !== null ? round((float) $median, 2) : null,
            'highest_percentage' => $stats->highest_percentage !== null ? round((float) $stats->highest_percentage, 2) : null,
            'lowest_percentage' => $stats->lowest_percentage !== null ? round((float) $stats->lowest_percentage, 2) : null,
            'standard_deviation_percentage' => $stats->std_dev_percentage !== null ? round((float) $stats->std_dev_percentage, 2) : null,
        ];
    }

    protected function calculateMedian(array $values): ?float
    {
        $n = count($values);
        if ($n === 0) return null;
        sort($values);
        $middle = floor(($n - 1) / 2);
        if ($n % 2) {
            return (float) $values[$middle];
        } else {
            return ($values[$middle] + $values[$middle + 1]) / 2;
        }
    }
}
