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
     * Get item analysis for all questions in an exam.
     */
    public function getAnalysis(Exam $exam): array
    {
        // 1. Fetch snapshots in bulk
        $snapshots = $exam->snapshots()->orderBy('sequence')->get();
        if ($snapshots->isEmpty()) return [];

        // 2. Identify official population (finalized attempts of SUBMITTED/EXPIRED status)
        // Hardened population check
        $participants = ExamAttemptScore::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('grading_status', 'FINALIZED')
            ->whereHas('attempt', function ($q) {
                $q->whereIn('status', ['SUBMITTED', 'EXPIRED']);
            })
            ->orderByDesc('percentage')
            ->orderBy('student_id') // Deterministic tie-breaker
            ->get();

        $n = $participants->count();
        if ($n === 0) {
            return $snapshots->map(fn($s) => $this->emptyAnalysis($s, 'no_participants'))->toArray();
        }

        $canCalculateDiscrimination = $n >= 20;
        $groupSize = $canCalculateDiscrimination ? (int) round($n * 0.27) : 0;

        $topStudentIds = $canCalculateDiscrimination ? $participants->take($groupSize)->pluck('student_id')->toArray() : [];
        $bottomStudentIds = $canCalculateDiscrimination ? $participants->take(-$groupSize)->pluck('student_id')->toArray() : [];
        $allParticipantStudentIds = $participants->pluck('student_id')->toArray();

        // 3. Fetch item score aggregates in bulk to avoid N+1
        // Difficulty: average awarded score across all finalized participants
        $difficultyStats = ExamQuestionScore::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('student_id', $allParticipantStudentIds)
            ->groupBy('snapshot_id')
            ->select('snapshot_id', DB::raw('AVG(awarded_score) as avg_score'))
            ->get()
            ->keyBy('snapshot_id');

        // Discrimination: aggregates for top and bottom groups
        $topStats = collect();
        $bottomStats = collect();
        if ($canCalculateDiscrimination) {
            $topStats = ExamQuestionScore::query()
                ->where('tenant_id', $exam->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('student_id', $topStudentIds)
                ->groupBy('snapshot_id')
                ->select('snapshot_id', DB::raw('AVG(awarded_score) as avg_score'))
                ->get()
                ->keyBy('snapshot_id');

            $bottomStats = ExamQuestionScore::query()
                ->where('tenant_id', $exam->tenant_id)
                ->where('exam_id', $exam->id)
                ->whereIn('student_id', $bottomStudentIds)
                ->groupBy('snapshot_id')
                ->select('snapshot_id', DB::raw('AVG(awarded_score) as avg_score'))
                ->get()
                ->keyBy('snapshot_id');
        }

        // Response counts in bulk
        $responseCounts = ExamAttemptAnswer::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('answer_state', 'ANSWERED')
            ->groupBy('exam_question_snapshot_id')
            ->select('exam_question_snapshot_id', DB::raw('COUNT(*) as count'))
            ->get()
            ->keyBy('exam_question_snapshot_id');

        // 4. Map snapshots to metrics using in-memory collections
        return $snapshots->map(function ($snapshot) use (
            $n,
            $canCalculateDiscrimination,
            $difficultyStats,
            $topStats,
            $bottomStats,
            $responseCounts
        ) {
            $maxPoints = (float) $snapshot->points;
            $diffStat = $difficultyStats->get($snapshot->id);
            $difficulty = ($diffStat && $maxPoints > 0) ? round((float) $diffStat->avg_score / $maxPoints, 3) : null;

            $discrimination = null;
            $discriminationStatus = $canCalculateDiscrimination ? 'success' : 'insufficient_sample';

            if ($canCalculateDiscrimination && $maxPoints > 0) {
                $pTop = ($topStats->get($snapshot->id)->avg_score ?? 0) / $maxPoints;
                $pBottom = ($bottomStats->get($snapshot->id)->avg_score ?? 0) / $maxPoints;
                $discrimination = round($pTop - $pBottom, 3);
            }

            return [
                'question_snapshot_id' => $snapshot->id,
                'sequence' => $snapshot->sequence + 1,
                'question_type' => $snapshot->metadata['question_type'] ?? 'MC',
                'max_points' => $maxPoints,
                'response_count' => $responseCounts->get($snapshot->id)->count ?? 0,
                'difficulty_index' => $difficulty,
                'difficulty_status' => $this->interpretDifficulty($difficulty),
                'discrimination_index' => $discrimination,
                'discrimination_status' => $discriminationStatus,
            ];
        })->toArray();
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

    protected function interpretDifficulty(?float $p): ?string
    {
        if ($p === null) return null;
        if ($p < 0.30) return 'Sulit';
        if ($p <= 0.70) return 'Sedang';
        return 'Mudah';
    }
}
