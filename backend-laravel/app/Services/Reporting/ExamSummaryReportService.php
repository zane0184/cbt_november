<?php

namespace App\Services\Reporting;

use App\Models\Exam;
use App\Models\ExamAttemptScore;

class ExamSummaryReportService
{
    /**
     * Only the authoritative current score pointer contributes to reports.
     * Historical score versions remain audit history and are never aggregated.
     */
    public function getSummary(Exam $exam): array
    {
        $participantCount = $exam->participants()
            ->where('exam_participants.tenant_id', $exam->tenant_id)
            ->count();

        $attemptCount = $exam->attempts()
            ->where('exam_attempts.tenant_id', $exam->tenant_id)
            ->count();

        $stats = $this->currentScoreQuery($exam)
            ->selectRaw($this->aggregateSelect())
            ->first();

        return [
            'exam_title' => $exam->title,
            'participant_count' => $participantCount,
            'attempt_count' => $attemptCount,
            'total_scored' => (int) ($stats->total_scored ?? 0),
            'finalized_count' => (int) ($stats->finalized_count ?? 0),
            'pending_manual_count' => (int) ($stats->pending_manual_count ?? 0),
            'average_percentage' => round($stats->average_percentage ?? 0, 2),
            'highest_percentage' => round($stats->highest_percentage ?? 0, 2),
            'lowest_percentage' => round($stats->lowest_percentage ?? 0, 2),
            'average_score' => round($stats->average_score ?? 0, 2),
            'highest_score' => round($stats->highest_score ?? 0, 2),
            'lowest_score' => round($stats->lowest_score ?? 0, 2),
        ];
    }

    /**
     * Get summary report for an exam filtered by rombel.
     */
    public function getRombelSummary(Exam $exam, string $rombelId): array
    {
        $rombel = \App\Models\Rombel::query()->findOrFail($rombelId);
        abort_unless($rombel->tenant_id === $exam->tenant_id, 404);

        $participantCount = $exam->participants()
            ->where('exam_participants.tenant_id', $exam->tenant_id)
            ->whereHas('user', function ($userQuery) use ($exam, $rombel) {
                $userQuery->where('tenant_id', $exam->tenant_id)
                    ->whereHas('studentProfile', function ($profileQuery) use ($exam, $rombel) {
                        $profileQuery->where('tenant_id', $exam->tenant_id)
                            ->whereHas('rombels', function ($rombelQuery) use ($exam, $rombel) {
                                $rombelQuery->where('rombels.id', $rombel->id)
                                    ->where('rombels.tenant_id', $exam->tenant_id)
                                    ->where('rombels.academic_year_id', $exam->assessment?->academic_year_id);
                            });
                    });
            })
            ->count();

        $stats = $this->currentScoreQuery($exam)
            ->whereHas('student', function ($userQuery) use ($exam, $rombel) {
                $userQuery->where('tenant_id', $exam->tenant_id)
                    ->whereHas('studentProfile', function ($profileQuery) use ($exam, $rombel) {
                        $profileQuery->where('tenant_id', $exam->tenant_id)
                            ->whereHas('rombels', function ($rombelQuery) use ($exam, $rombel) {
                                $rombelQuery->where('rombels.id', $rombel->id)
                                    ->where('rombels.tenant_id', $exam->tenant_id)
                                    ->where('rombels.academic_year_id', $exam->assessment?->academic_year_id);
                            });
                    });
            })
            ->selectRaw($this->aggregateSelect())
            ->first();

        return [
            'exam_title' => $exam->title,
            'rombel_name' => $rombel->name,
            'participant_count' => $participantCount,
            'total_scored' => (int) ($stats->total_scored ?? 0),
            'finalized_count' => (int) ($stats->finalized_count ?? 0),
            'pending_manual_count' => (int) ($stats->pending_manual_count ?? 0),
            'average_percentage' => round($stats->average_percentage ?? 0, 2),
            'highest_percentage' => round($stats->highest_percentage ?? 0, 2),
            'lowest_percentage' => round($stats->lowest_percentage ?? 0, 2),
            'average_score' => round($stats->average_score ?? 0, 2),
            'highest_score' => round($stats->highest_score ?? 0, 2),
            'lowest_score' => round($stats->lowest_score ?? 0, 2),
        ];
    }

    /**
     * Get summary for all exams of a subject.
     */
    public function getSubjectSummary(string $subjectId, array $filters = []): \Illuminate\Support\Collection
    {
        $subject = \App\Models\Subject::query()->findOrFail($subjectId);
        $exams = Exam::query()
            ->where('tenant_id', $subject->tenant_id)
            ->whereHas('assessment', function ($query) use ($subject) {
                $query->where('tenant_id', $subject->tenant_id)
                    ->where('subject_id', $subject->id);
            })
            ->when($filters['academic_year_id'] ?? null, function ($query, $academicYearId) {
                $query->whereHas('assessment', fn ($assessmentQuery) => $assessmentQuery->where('academic_year_id', $academicYearId));
            })
            ->when($filters['semester_id'] ?? null, function ($query, $semesterId) {
                $query->whereHas('assessment', fn ($assessmentQuery) => $assessmentQuery->where('semester_id', $semesterId));
            })
            ->get();

        return $exams->map(fn (Exam $exam) => $this->getSummary($exam));
    }

    private function currentScoreQuery(Exam $exam): \Illuminate\Database\Eloquent\Builder
    {
        return ExamAttemptScore::query()
            ->join('exam_attempt_score_current as current_pointer', function ($join) {
                $join->on('current_pointer.attempt_id', '=', 'exam_attempt_scores.attempt_id')
                    ->on('current_pointer.current_score_id', '=', 'exam_attempt_scores.id')
                    ->on('current_pointer.tenant_id', '=', 'exam_attempt_scores.tenant_id')
                    ->on('current_pointer.scoring_version', '=', 'exam_attempt_scores.scoring_version');
            })
            ->where('exam_attempt_scores.tenant_id', $exam->tenant_id)
            ->where('exam_attempt_scores.exam_id', $exam->id);
    }

    private function aggregateSelect(): string
    {
        return 'COUNT(*) as total_scored,
            SUM(CASE WHEN exam_attempt_scores.grading_status = "FINALIZED" THEN 1 ELSE 0 END) as finalized_count,
            SUM(CASE WHEN exam_attempt_scores.grading_status = "MANUAL_REVIEW" THEN 1 ELSE 0 END) as pending_manual_count,
            AVG(CASE WHEN exam_attempt_scores.grading_status = "FINALIZED" THEN exam_attempt_scores.percentage ELSE NULL END) as average_percentage,
            MAX(CASE WHEN exam_attempt_scores.grading_status = "FINALIZED" THEN exam_attempt_scores.percentage ELSE NULL END) as highest_percentage,
            MIN(CASE WHEN exam_attempt_scores.grading_status = "FINALIZED" THEN exam_attempt_scores.percentage ELSE NULL END) as lowest_percentage,
            AVG(CASE WHEN exam_attempt_scores.grading_status = "FINALIZED" THEN exam_attempt_scores.raw_score ELSE NULL END) as average_score,
            MAX(CASE WHEN exam_attempt_scores.grading_status = "FINALIZED" THEN exam_attempt_scores.raw_score ELSE NULL END) as highest_score,
            MIN(CASE WHEN exam_attempt_scores.grading_status = "FINALIZED" THEN exam_attempt_scores.raw_score ELSE NULL END) as lowest_score';
    }
}
