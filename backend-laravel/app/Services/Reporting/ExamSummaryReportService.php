<?php

namespace App\Services\Reporting;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptScore;
use Illuminate\Support\Facades\DB;

class ExamSummaryReportService
{
    /**
     * Get summary report for an exam.
     */
    public function getSummary(Exam $exam): array
    {
        $participantCount = $exam->participants()->where('exam_participants.tenant_id', $exam->tenant_id)->count();
        $attemptCount = $exam->attempts()->where('exam_attempts.tenant_id', $exam->tenant_id)->count();

        $stats = ExamAttemptScore::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->select([
                DB::raw('COUNT(*) as total_scored'),
                DB::raw('SUM(CASE WHEN grading_status = "FINALIZED" THEN 1 ELSE 0 END) as finalized_count'),
                DB::raw('SUM(CASE WHEN grading_status = "MANUAL_REVIEW" THEN 1 ELSE 0 END) as pending_manual_count'),
                DB::raw('AVG(percentage) as average_percentage'),
                DB::raw('MAX(percentage) as highest_percentage'),
                DB::raw('MIN(percentage) as lowest_percentage'),
                DB::raw('AVG(raw_score) as average_score'),
                DB::raw('MAX(raw_score) as highest_score'),
                DB::raw('MIN(raw_score) as lowest_score'),
            ])
            ->first();

        return [
            'exam_title' => $exam->title,
            'participant_count' => $participantCount,
            'attempt_count' => $attemptCount,
            'total_scored' => $stats->total_scored ?? 0,
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
            })->count();

        $stats = ExamAttemptScore::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
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
            ->select([
                DB::raw('COUNT(*) as total_scored'),
                DB::raw('SUM(CASE WHEN grading_status = "FINALIZED" THEN 1 ELSE 0 END) as finalized_count'),
                DB::raw('SUM(CASE WHEN grading_status = "MANUAL_REVIEW" THEN 1 ELSE 0 END) as pending_manual_count'),
                DB::raw('AVG(percentage) as average_percentage'),
                DB::raw('MAX(percentage) as highest_percentage'),
                DB::raw('MIN(percentage) as lowest_percentage'),
                DB::raw('AVG(raw_score) as average_score'),
                DB::raw('MAX(raw_score) as highest_score'),
                DB::raw('MIN(raw_score) as lowest_score'),
            ])
            ->first();

        return [
            'exam_title' => $exam->title,
            'rombel_name' => $rombel->name,
            'participant_count' => $participantCount,
            'total_scored' => $stats->total_scored ?? 0,
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
            ->whereHas('assessment', function ($q) use ($subject) {
                $q->where('tenant_id', $subject->tenant_id)
                    ->where('subject_id', $subject->id);
            })
            ->when($filters['academic_year_id'] ?? null, function ($q, $ayId) {
                $q->whereHas('assessment', function ($sq) use ($ayId) {
                    $sq->where('academic_year_id', $ayId);
                });
            })
            ->when($filters['semester_id'] ?? null, function ($q, $semesterId) {
                $q->whereHas('assessment', function ($sq) use ($semesterId) {
                    $sq->where('semester_id', $semesterId);
                });
            })
            ->get();

        return $exams->map(function ($exam) {
            return $this->getSummary($exam);
        });
    }
}
