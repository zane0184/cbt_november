<?php

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\ExamAttemptScore;

class StudentAnalyticsService
{
    /**
     * Get performance history from current finalized scores only.
     */
    public function getStudentSummary(User $student, array $filters = []): array
    {
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
            ->where('exam_attempt_scores.tenant_id', $student->tenant_id)
            ->where('exam_attempt_scores.student_id', $student->id)
            ->where('exam_attempt_scores.grading_status', 'FINALIZED')
            ->whereIn('score_attempts.status', ['SUBMITTED', 'EXPIRED'])
            ->whereHas('exam', function ($examQuery) {
                $examQuery->where('show_result', true);
            })
            ->with(['exam.assessment.subject'])
            ->select('exam_attempt_scores.*');

        if (array_key_exists('allowed_exam_ids', $filters)) {
            $query->whereIn('exam_attempt_scores.exam_id', $filters['allowed_exam_ids']);
        }

        if (!empty($filters['subject_id'])) {
            $query->whereHas('exam.assessment', function ($assessmentQuery) use ($filters) {
                $assessmentQuery->where('subject_id', $filters['subject_id']);
            });
        }

        if (!empty($filters['academic_year_id'])) {
            $query->whereHas('exam.assessment', function ($assessmentQuery) use ($filters) {
                $assessmentQuery->where('academic_year_id', $filters['academic_year_id']);
            });
        }

        if (!empty($filters['semester_id'])) {
            $query->whereHas('exam.assessment', function ($assessmentQuery) use ($filters) {
                $assessmentQuery->where('semester_id', $filters['semester_id']);
            });
        }

        $scores = $query->orderBy('exam_attempt_scores.scored_at', 'asc')->get();

        return [
            'total_exams' => $scores->count(),
            'average_percentage' => round((float) ($scores->avg('percentage') ?? 0), 2),
            'highest_percentage' => round((float) ($scores->max('percentage') ?? 0), 2),
            'lowest_percentage' => round((float) ($scores->min('percentage') ?? 0), 2),
            'history' => $scores->map(function ($score) {
                return [
                    'exam_id' => $score->exam_id,
                    'exam_title' => $score->exam->title,
                    'subject_name' => $score->exam->assessment->subject->name ?? '-',
                    'percentage' => (float) $score->percentage,
                    'raw_score' => (float) $score->raw_score,
                    'max_score' => (float) $score->max_score,
                    'date' => $score->scored_at?->toIso8601String(),
                ];
            })->values(),
        ];
    }
}
