<?php

namespace App\Services\Reporting;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ExamResultReportService
{
    /**
     * Get exam results with filtering and pagination.
     */
    public function getResults(Exam $exam, array $filters = []): LengthAwarePaginator
    {
        $query = ExamAttempt::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereHas('participant', function (Builder $participantQuery) use ($exam) {
                $participantQuery->where('tenant_id', $exam->tenant_id)
                    ->whereHas('user', function ($userQuery) use ($exam) {
                        $userQuery->where('tenant_id', $exam->tenant_id)
                            ->whereHas('studentProfile', fn ($profileQuery) => $profileQuery->where('tenant_id', $exam->tenant_id));
                    });
            })
            ->with(['participant.userForHistory.studentProfile.rombels', 'score', 'exam.assessment.subject', 'exam.assessment.academicYear', 'exam.assessment.semester'])
            ->when($filters['search'] ?? null, function (Builder $q, $search) {
                $q->whereHas('participant.userForHistory', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function (Builder $q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['grading_status'] ?? null, function (Builder $q, $gradingStatus) {
                if ($gradingStatus === 'NOT_SCORED') {
                    $q->whereDoesntHave('score');
                    return;
                }

                $q->whereHas('score', function ($sq) use ($gradingStatus) {
                    $sq->where('grading_status', $gradingStatus);
                });
            })
            ->when($filters['rombel_id'] ?? null, function (Builder $q, $rombelId) use ($exam) {
                $q->whereHas('participant.userForHistory.studentProfile.rombels', function ($sq) use ($rombelId) {
                    $sq->where('rombels.id', $rombelId)
                        ->where('rombels.tenant_id', $exam->tenant_id)
                        ->where('rombels.academic_year_id', $exam->assessment?->academic_year_id);
                });
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate($filters['per_page'] ?? 25);
    }

    /**
     * Get all results for export without pagination.
     */
    public function getAllResultsForExport(Exam $exam, array $filters = []): \Illuminate\Support\Collection
    {
        return ExamAttempt::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereHas('participant', function (Builder $participantQuery) use ($exam) {
                $participantQuery->where('tenant_id', $exam->tenant_id)
                    ->whereHas('user', function ($userQuery) use ($exam) {
                        $userQuery->where('tenant_id', $exam->tenant_id)
                            ->whereHas('studentProfile', fn ($profileQuery) => $profileQuery->where('tenant_id', $exam->tenant_id));
                    });
            })
            ->with(['participant.userForHistory.studentProfile.rombels', 'score', 'exam.assessment.subject', 'exam.assessment.academicYear', 'exam.assessment.semester'])
            ->when($filters['status'] ?? null, function (Builder $q, $status) {
                $q->where('status', $status);
            })
            ->when($filters['grading_status'] ?? null, function (Builder $q, $gradingStatus) {
                if ($gradingStatus === 'NOT_SCORED') {
                    $q->whereDoesntHave('score');
                    return;
                }

                $q->whereHas('score', function ($sq) use ($gradingStatus) {
                    $sq->where('grading_status', $gradingStatus);
                });
            })
            ->when($filters['rombel_id'] ?? null, function (Builder $q, $rombelId) use ($exam) {
                $q->whereHas('participant.userForHistory.studentProfile.rombels', function ($sq) use ($rombelId) {
                    $sq->where('rombels.id', $rombelId)
                    ->where('rombels.tenant_id', $exam->tenant_id)
                    ->where('rombels.academic_year_id', $exam->assessment?->academic_year_id);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
