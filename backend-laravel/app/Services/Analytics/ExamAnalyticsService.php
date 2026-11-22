<?php

namespace App\Services\Analytics;

use App\Models\Exam;
use App\Models\ExamAttemptScore;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ExamAnalyticsService
{
    /**
     * Aggregate only the authoritative current score for each terminal attempt.
     * Historical scoring versions are audit history, not additional participants.
     */
    public function getSummary(Exam $exam): array
    {
        $query = $this->finalizedCurrentScores($exam);

        $stats = (clone $query)->select([
            DB::raw('COUNT(*) as participant_count'),
            DB::raw('AVG(exam_attempt_scores.percentage) as average_percentage'),
            DB::raw('MAX(exam_attempt_scores.percentage) as highest_percentage'),
            DB::raw('MIN(exam_attempt_scores.percentage) as lowest_percentage'),
            DB::raw('STDDEV_POP(exam_attempt_scores.percentage) as std_dev_percentage'),
            DB::raw('AVG(exam_attempt_scores.raw_score) as average_raw_score'),
            DB::raw('MAX(exam_attempt_scores.raw_score) as highest_raw_score'),
            DB::raw('MIN(exam_attempt_scores.raw_score) as lowest_raw_score'),
        ])->first();

        $percentages = (clone $query)
            ->orderBy('exam_attempt_scores.percentage')
            ->pluck('exam_attempt_scores.percentage')
            ->toArray();

        $median = $this->calculateMedian($percentages);

        return [
            'participant_count' => (int) ($stats->participant_count ?? 0),
            'average_percentage' => $stats->average_percentage !== null ? round((float) $stats->average_percentage, 2) : null,
            'median_percentage' => $median !== null ? round((float) $median, 2) : null,
            'highest_percentage' => $stats->highest_percentage !== null ? round((float) $stats->highest_percentage, 2) : null,
            'lowest_percentage' => $stats->lowest_percentage !== null ? round((float) $stats->lowest_percentage, 2) : null,
            'standard_deviation_percentage' => $stats->std_dev_percentage !== null ? round((float) $stats->std_dev_percentage, 2) : null,
            'average_raw_score' => $stats->average_raw_score !== null ? round((float) $stats->average_raw_score, 2) : null,
            'highest_raw_score' => $stats->highest_raw_score !== null ? round((float) $stats->highest_raw_score, 2) : null,
            'lowest_raw_score' => $stats->lowest_raw_score !== null ? round((float) $stats->lowest_raw_score, 2) : null,
        ];
    }

    /**
     * Get score distribution using one current finalized score per attempt.
     */
    public function getDistribution(Exam $exam): array
    {
        $results = $this->finalizedCurrentScores($exam)
            ->pluck('exam_attempt_scores.percentage')
            ->toArray();

        $participantCount = count($results);
        $buckets = [
            ['label' => '0-9.99', 'lower' => 0, 'upper' => 10, 'count' => 0],
            ['label' => '10-19.99', 'lower' => 10, 'upper' => 20, 'count' => 0],
            ['label' => '20-29.99', 'lower' => 20, 'upper' => 30, 'count' => 0],
            ['label' => '30-39.99', 'lower' => 30, 'upper' => 40, 'count' => 0],
            ['label' => '40-49.99', 'lower' => 40, 'upper' => 50, 'count' => 0],
            ['label' => '50-59.99', 'lower' => 50, 'upper' => 60, 'count' => 0],
            ['label' => '60-69.99', 'lower' => 60, 'upper' => 70, 'count' => 0],
            ['label' => '70-79.99', 'lower' => 70, 'upper' => 80, 'count' => 0],
            ['label' => '80-89.99', 'lower' => 80, 'upper' => 90, 'count' => 0],
            ['label' => '90-100', 'lower' => 90, 'upper' => 100, 'count' => 0],
        ];

        foreach ($results as $percentage) {
            $percentage = (float) $percentage;
            foreach ($buckets as $key => $bucket) {
                if ($key === 9) {
                    if ($percentage >= 90 && $percentage <= 100) {
                        $buckets[$key]['count']++;
                        break;
                    }
                } elseif ($percentage >= $bucket['lower'] && $percentage < $bucket['upper']) {
                    $buckets[$key]['count']++;
                    break;
                }
            }
        }

        return array_map(function ($bucket) use ($participantCount) {
            $bucket['percentage_of_participants'] = $participantCount > 0
                ? round(($bucket['count'] / $participantCount) * 100, 2)
                : 0;
            return $bucket;
        }, $buckets);
    }

    private function finalizedCurrentScores(Exam $exam): Builder
    {
        return ExamAttemptScore::query()
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
            ->select('exam_attempt_scores.*');
    }

    protected function calculateMedian(array $values): ?float
    {
        $count = count($values);
        if ($count === 0) {
            return null;
        }

        sort($values);
        $middle = floor(($count - 1) / 2);

        if ($count % 2) {
            return (float) $values[$middle];
        }

        return ((float) $values[$middle] + (float) $values[$middle + 1]) / 2;
    }
}
