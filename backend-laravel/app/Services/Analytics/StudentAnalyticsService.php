<?php

namespace App\Services\Analytics;

use App\Models\User;
use App\Models\ExamAttemptScore;
use Illuminate\Support\Facades\DB;

class StudentAnalyticsService
{
    /**
     * Get performance history and trends for a student.
     */
    public function getStudentSummary(User $student, array $filters = []): array
    {
        $query = ExamAttemptScore::query()
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $student->id)
            ->where('grading_status', 'FINALIZED')
            ->whereHas('exam', function ($q) {
                $q->where('show_result', true);
            })
            ->with(['exam.assessment.subject']);

        if (array_key_exists('allowed_exam_ids', $filters)) {
            $query->whereIn('exam_id', $filters['allowed_exam_ids']);
        }

        if (!empty($filters['subject_id'])) {
            $query->whereHas('exam.assessment', function ($q) use ($filters) {
                $q->where('subject_id', $filters['subject_id']);
            });
        }

        if (!empty($filters['academic_year_id'])) {
            $query->whereHas('exam.assessment', function ($q) use ($filters) {
                $q->where('academic_year_id', $filters['academic_year_id']);
            });
        }

        if (!empty($filters['semester_id'])) {
            $query->whereHas('exam.assessment', function ($q) use ($filters) {
                $q->where('semester_id', $filters['semester_id']);
            });
        }

        $scores = $query->orderBy('calculated_at', 'asc')->get();

        $stats = [
            'total_exams' => $scores->count(),
            'average_percentage' => round($scores->avg('percentage'), 2),
            'highest_percentage' => round($scores->max('percentage'), 2),
            'lowest_percentage' => round($scores->min('percentage'), 2),
            'history' => $scores->map(function ($s) {
                return [
                    'exam_id' => $s->exam_id,
                    'exam_title' => $s->exam->title,
                    'subject_name' => $s->exam->assessment->subject->name ?? '-',
                    'percentage' => (float) $s->percentage,
                    'raw_score' => (float) $s->raw_score,
                    'max_score' => (float) $s->max_score,
                    'date' => $s->calculated_at?->toIso8601String(),
                ];
            })
        ];

        return $stats;
    }
}
