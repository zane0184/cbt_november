<?php

namespace App\Services\Analytics;

use App\Models\Subject;
use App\Models\Exam;
use App\Models\ExamAttemptScore;
use Illuminate\Support\Facades\DB;

class SubjectAnalyticsService
{
    /**
     * Get analytics for a subject across all exams.
     */
    public function getSubjectAnalytics(Subject $subject, array $filters = []): array
    {
        $exams = Exam::query()
            ->whereHas('assessment', function ($q) use ($subject, $filters) {
                $q->where('tenant_id', $subject->tenant_id)->where('subject_id', $subject->id);
                if (!empty($filters['academic_year_id'])) {
                    $q->where('academic_year_id', $filters['academic_year_id']);
                }
                if (!empty($filters['semester_id'])) {
                    $q->where('semester_id', $filters['semester_id']);
                }
            })
            ->get();

        $examData = $exams->map(function ($exam) use ($subject) {
            $stats = ExamAttemptScore::query()
                ->where('tenant_id', $subject->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('grading_status', 'FINALIZED')
                ->current()
                ->select([
                    DB::raw('AVG(percentage) as average_percentage'),
                    DB::raw('COUNT(*) as participant_count')
                ])
                ->first();

            return [
                'exam_id' => $exam->id,
                'title' => $exam->title,
                'average_percentage' => $stats->average_percentage !== null ? round((float) $stats->average_percentage, 2) : null,
                'participant_count' => (int) ($stats->participant_count ?? 0),
                'date' => $exam->published_at?->toIso8601String()
            ];
        })->filter(fn($e) => $e['participant_count'] > 0)->values();

        return [
            'subject_name' => $subject->name,
            'exam_count' => $examData->count(),
            'average_percentage' => round($examData->avg('average_percentage'), 2),
            'exams' => $examData
        ];
    }
}
