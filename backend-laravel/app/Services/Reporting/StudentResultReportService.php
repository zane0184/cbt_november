<?php

namespace App\Services\Reporting;

use App\Models\User;
use App\Models\ExamAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class StudentResultReportService
{
    /**
     * Get student results.
     */
    public function getStudentResults(User $student, array $filters = []): LengthAwarePaginator
    {
        $query = ExamAttempt::query()
            ->where('tenant_id', $student->tenant_id)
            ->whereHas('participant', function ($q) use ($student) {
                $q->where('tenant_id', $student->tenant_id)
                    ->where('user_id', $student->id);
            })
            ->with(['exam.assessment.subject', 'exam.assessment.academicYear', 'exam.assessment.semester', 'scoreCurrentPointer.currentScore'])
            ->when(array_key_exists('allowed_exam_ids', $filters), function (Builder $q) use ($filters) {
                $q->whereIn('exam_id', $filters['allowed_exam_ids']);
            })
            ->when($filters['finalized_only'] ?? false, function (Builder $q) {
                // The current-score pointer is authoritative. A historical finalized
                // score must not make a superseded/unfinalized result visible.
                $q->whereHas('scoreCurrentPointer.currentScore', function ($sq) {
                    $sq->whereColumn('exam_attempt_scores.attempt_id', 'exam_attempts.id')
                        ->whereColumn('exam_attempt_scores.tenant_id', 'exam_attempts.tenant_id')
                        ->whereColumn('exam_attempt_scores.scoring_version', 'exam_attempt_score_current.scoring_version')
                        ->where('grading_status', 'FINALIZED');
                });
            })
            ->when($filters['visible_only'] ?? false, function (Builder $q) {
                $q->whereHas('exam', function ($sq) {
                    $sq->where('show_result', true);
                });
            })
            ->when($filters['subject_id'] ?? null, function (Builder $q, $subjectId) {
                $q->whereHas('exam.assessment', function ($sq) use ($subjectId) {
                    $sq->where('subject_id', $subjectId);
                });
            })
            ->when($filters['academic_year_id'] ?? null, function (Builder $q, $academicYearId) {
                $q->whereHas('exam.assessment', function ($sq) use ($academicYearId) {
                    $sq->where('academic_year_id', $academicYearId);
                });
            })
            ->when($filters['semester_id'] ?? null, function (Builder $q, $semesterId) {
                $q->whereHas('exam.assessment', function ($sq) use ($semesterId) {
                    $sq->where('semester_id', $semesterId);
                });
            })
            ->orderBy('created_at', 'desc');

        $results = $query->paginate($filters['per_page'] ?? 25);

        // Preserve the established `score` response key while sourcing it only
        // from the authoritative pointer, never from score history's max version.
        $results->getCollection()->each(function (ExamAttempt $attempt) {
            $attempt->setRelation('score', $attempt->resolvedCurrentScore());
        });

        return $results;
    }
}
