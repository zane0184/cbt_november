<?php

namespace App\Services\Analytics;

use App\Models\Exam;
use App\Models\ExamAttemptScore;
use Illuminate\Support\Facades\DB;

class ExamAnalyticsService
{
    /**
     * Get summary statistics for an exam based on finalized results of submitted/expired attempts.
     */
    public function getSummary(Exam $exam): array
    {
        $query = ExamAttemptScore::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('grading_status', 'FINALIZED')
            ->current()
            ->current()
            ->whereHas('attempt', function ($q) {
                $q->whereIn('status', ['SUBMITTED', 'EXPIRED']);
            });

        $stats = $query->select([
                DB::raw('COUNT(*) as participant_count'),
                DB::raw('AVG(percentage) as average_percentage'),
                DB::raw('MAX(percentage) as highest_percentage'),
                DB::raw('MIN(percentage) as lowest_percentage'),
                DB::raw('STDDEV_POP(percentage) as std_dev_percentage'),
                DB::raw('AVG(raw_score) as average_raw_score'),
                DB::raw('MAX(raw_score) as highest_raw_score'),
                DB::raw('MIN(raw_score) as lowest_raw_score'),
            ])
            ->first();

        $percentages = $query->clone()
            ->orderBy('percentage')
            ->pluck('percentage')
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
     * Get score distribution in 10 deterministic buckets.
     */
    public function getDistribution(Exam $exam): array
    {
        $results = ExamAttemptScore::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('grading_status', 'FINALIZED')
            ->whereHas('attempt', function ($q) {
                $q->whereIn('status', ['SUBMITTED', 'EXPIRED']);
            })
            ->pluck('percentage')
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

        foreach ($results as $p) {
            $p = (float) $p;
            foreach ($buckets as $key => $bucket) {
                if ($key === 9) {
                    if ($p >= 90 && $p <= 100) {
                        $buckets[$key]['count']++;
                        break;
                    }
                } else {
                    if ($p >= $bucket['lower'] && $p < $bucket['upper']) {
                        $buckets[$key]['count']++;
                        break;
                    }
                }
            }
        }

        return array_map(function ($b) use ($participantCount) {
            $b['percentage_of_participants'] = $participantCount > 0
                ? round(($b['count'] / $participantCount) * 100, 2)
                : 0;
            return $b;
        }, $buckets);
    }

    protected function calculateMedian(array $values): ?float
    {
        $n = count($values);
        if ($n === 0) return null;

        // No need to sort if already sorted by SQL in getSummary,
        // but it's safer to ensure it's sorted here too.
        sort($values);
        $middle = floor(($n - 1) / 2);

        if ($n % 2) {
            return (float) $values[$middle];
        } else {
            $low = $values[$middle];
            $high = $values[$middle + 1];
            return ($low + $high) / 2;
        }
    }
}
