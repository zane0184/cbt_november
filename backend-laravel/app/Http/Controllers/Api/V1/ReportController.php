<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\User;
use App\Models\Rombel;
use App\Models\Subject;
use App\Models\Tenant;
use App\Support\Security\TeacherAccess;
use App\Policies\ReportPolicy;
use App\Services\Reporting\ExamResultReportService;
use App\Services\Reporting\StudentResultReportService;
use App\Services\Reporting\ExamSummaryReportService;
use App\Services\Reporting\QuestionReportService;
use App\Services\Reporting\ReportHeaderService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExamResultsExport;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function __construct(
        protected ExamResultReportService $examResultService,
        protected StudentResultReportService $studentResultService,
        protected ExamSummaryReportService $examSummaryService,
        protected QuestionReportService $questionReportService,
        protected AuditService $auditService,
        protected ReportHeaderService $headerService
    ) {}

    /**
     * Get results for a specific exam.
     */
    public function examResults(Request $request, Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);

        $filters = $request->only(['search', 'status', 'grading_status', 'rombel_id', 'per_page']);
        if ($request->user()->hasRole('GURU') && !empty($filters['rombel_id'])) {
            $rombel = Rombel::query()->whereKey($filters['rombel_id'])->firstOrFail();
            abort_unless($exam->assessment && TeacherAccess::canAssignRombelForAssessment($request->user(), $rombel, $exam->assessment), 403);
        }
        $results = $this->examResultService->getResults($exam, $filters);

        $this->auditService->log('REPORT_VIEW', 'Exam', $exam->id, null, ['type' => 'results']);

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ]
        ]);
    }

    /**
     * Get summary for a specific exam.
     */
    public function examSummary(Request $request, Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);

        $summary = $this->examSummaryService->getSummary($exam);

        $this->auditService->log('REPORT_VIEW', 'Exam', $exam->id, null, ['type' => 'summary']);

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get results for a specific student.
     */
    public function studentResults(Request $request, User $student)
    {
        $this->authorize('viewStudentResults', $student);

        $filters = $request->only(['subject_id', 'academic_year_id', 'semester_id', 'per_page']);
        $assignedRombelIds = null;

        if ($request->user()->hasRole('GURU')) {
            $filters = $request->validate([
                'subject_id' => ['required', 'uuid'],
                'academic_year_id' => ['required', 'uuid'],
                'semester_id' => ['required', 'uuid'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);
            abort_unless(TeacherAccess::canViewStudentInAssessmentScope(
                $request->user(), $student, $filters['subject_id'], $filters['academic_year_id'], $filters['semester_id']
            ), 403, 'Laporan siswa hanya tersedia pada penugasan aktif Guru.');
            $filters['allowed_exam_ids'] = TeacherAccess::allowedExamIdsForStudentScope(
                $request->user(), $filters['subject_id'], $filters['academic_year_id'], $filters['semester_id']
            );
            $assignedRombelIds = TeacherAccess::assignedRombelIdsForScope(
                $request->user(), $filters['subject_id'], $filters['academic_year_id'], $filters['semester_id']
            );
        }

        // If student is viewing their own results, apply strict visibility
        if ($request->user()->id === $student->id && $request->user()->hasRole('SISWA')) {
            $filters['finalized_only'] = true;
            $filters['visible_only'] = true;
        }

        $results = $this->studentResultService->getStudentResults($student, $filters);
        $data = $results->items();

        // Student self-service receives a deliberately small DTO. Do not serialize
        // Eloquent internals such as tenant IDs, participant IDs, score-pointer IDs,
        // or score-history relations into the browser response.
        if ($request->user()->id === $student->id && $request->user()->hasRole('SISWA')) {
            $data = collect($data)->map(function ($attempt) {
                $exam = $attempt->exam;
                $assessment = $exam?->assessment;
                $score = $attempt->score;

                return [
                    'id' => $attempt->id,
                    'status' => $attempt->status,
                    'attempt_number' => $attempt->attempt_number,
                    'started_at' => $attempt->started_at?->toIso8601String(),
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                    'exam' => $exam ? [
                        'id' => $exam->id,
                        'title' => $exam->title,
                        'assessment' => $assessment ? [
                            'title' => $assessment->title,
                            'subject' => $assessment->subject ? ['name' => $assessment->subject->name] : null,
                            'academic_year' => $assessment->academicYear ? ['name' => $assessment->academicYear->name] : null,
                            'semester' => $assessment->semester ? ['name' => $assessment->semester->name] : null,
                        ] : null,
                    ] : null,
                    'score' => $score ? [
                        'raw_score' => (float) $score->raw_score,
                        'max_score' => (float) $score->max_score,
                        'percentage' => (float) $score->percentage,
                        'grading_status' => $score->grading_status,
                        'finalized_at' => $score->finalized_at?->toIso8601String(),
                    ] : null,
                ];
            })->values()->all();
        }

        $this->auditService->log('REPORT_VIEW', 'User', $student->id, null, ['type' => 'student_results']);

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ]
        ]);
    }

    /**
     * Export exam results to Excel.
     */
    public function exportExcel(Request $request, Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);

        $filters = $request->only(['status', 'grading_status', 'rombel_id']);
        if ($request->user()->hasRole('GURU') && !empty($filters['rombel_id'])) {
            $rombel = Rombel::query()->whereKey($filters['rombel_id'])->firstOrFail();
            abort_unless($exam->assessment && TeacherAccess::canAssignRombelForAssessment($request->user(), $rombel, $exam->assessment), 403);
        }
        $results = $this->examResultService->getAllResultsForExport($exam, $filters);

        $this->auditService->log('REPORT_EXPORT_EXCEL', 'Exam', $exam->id, null, ['filters' => $filters]);

        $filename = 'exam_results_' . $exam->id . '_' . now()->format('Ymd_His') . '.xlsx';

        return Excel::download(new ExamResultsExport($exam, $results), $filename);
    }

    /**
     * Export exam results to PDF.
     */
    public function exportPdf(Request $request, Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);

        $filters = $request->only(['status', 'grading_status', 'rombel_id']);
        if ($request->user()->hasRole('GURU') && !empty($filters['rombel_id'])) {
            $rombel = Rombel::query()->whereKey($filters['rombel_id'])->firstOrFail();
            abort_unless($exam->assessment && TeacherAccess::canAssignRombelForAssessment($request->user(), $rombel, $exam->assessment), 403);
        }
        $results = $this->examResultService->getAllResultsForExport($exam, $filters);
        $tenant = $exam->tenant ?? Tenant::find($exam->tenant_id) ?? $request->user()->tenant;
        $logoDataUri = $this->headerService->getLogoDataUri($tenant);

        $this->auditService->log('REPORT_EXPORT_PDF', 'Exam', $exam->id, null, ['filters' => $filters]);

        $pdf = Pdf::loadView('reports.pdf.exam-results', [
            'exam' => $exam,
            'results' => $results,
            'tenant' => $tenant,
            'logoDataUri' => $logoDataUri,
        ]);

        return $pdf->download('exam_results_' . $exam->id . '.pdf');
    }

    /**
     * Export student results to PDF.
     */
    public function exportStudentPdf(Request $request, User $student)
    {
        $this->authorize('viewStudentResults', $student);

        $filters = ['per_page' => 100];
        $assignedRombelIds = null;
        if ($request->user()->hasRole('GURU')) {
            $filters = $request->validate([
                'subject_id' => ['required', 'uuid'],
                'academic_year_id' => ['required', 'uuid'],
                'semester_id' => ['required', 'uuid'],
            ]);
            abort_unless(TeacherAccess::canViewStudentInAssessmentScope(
                $request->user(), $student, $filters['subject_id'], $filters['academic_year_id'], $filters['semester_id']
            ), 403, 'Laporan siswa hanya tersedia pada penugasan aktif Guru.');
            $filters['allowed_exam_ids'] = TeacherAccess::allowedExamIdsForStudentScope(
                $request->user(), $filters['subject_id'], $filters['academic_year_id'], $filters['semester_id']
            );
            $assignedRombelIds = TeacherAccess::assignedRombelIdsForScope(
                $request->user(), $filters['subject_id'], $filters['academic_year_id'], $filters['semester_id']
            );
            $filters['per_page'] = 100;
        }
        if ($request->user()->id === $student->id && $request->user()->hasRole('SISWA')) {
            $filters['finalized_only'] = true;
            $filters['visible_only'] = true;
        }

        $results = $this->studentResultService->getStudentResults($student, $filters);
        $tenant = $student->tenant ?? Tenant::find($student->tenant_id) ?? $request->user()->tenant;
        $logoDataUri = $this->headerService->getLogoDataUri($tenant);

        $this->auditService->log('REPORT_EXPORT_PDF', 'User', $student->id, null, ['type' => 'student_results']);

        $studentForPdf = User::query()->whereKey($student->id)->where('tenant_id', $student->tenant_id)
            ->with(['studentProfile.rombels' => function ($query) use ($assignedRombelIds) {
                if (is_array($assignedRombelIds)) {
                    $query->whereIn('rombels.id', $assignedRombelIds);
                }
            }])
            ->firstOrFail();

        $pdf = Pdf::loadView('reports.pdf.student-results', [
            'student' => $studentForPdf,
            'results' => $results,
            'tenant' => $tenant,
            'logoDataUri' => $logoDataUri,
        ]);

        return $pdf->download('student_results_' . $student->id . '.pdf');
    }

    /**
     * Export exam summary to PDF.
     */
    public function exportSummaryPdf(Request $request, Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);

        $summary = $this->examSummaryService->getSummary($exam);
        $tenant = $exam->tenant ?? Tenant::find($exam->tenant_id) ?? $request->user()->tenant;
        $logoDataUri = $this->headerService->getLogoDataUri($tenant);

        $this->auditService->log('REPORT_EXPORT_PDF', 'Exam', $exam->id, null, ['type' => 'summary']);

        $pdf = Pdf::loadView('reports.pdf.exam-summary', [
            'summary' => $summary,
            'tenant' => $tenant,
            'logoDataUri' => $logoDataUri,
        ]);

        return $pdf->download('exam_summary_' . $exam->id . '.pdf');
    }

    /**
     * Get report for a specific rombel in an exam.
     */
    public function rombelReport(Request $request, Exam $exam, Rombel $rombel)
    {
        $this->authorize('viewExamResults', $exam);
        if ($rombel->tenant_id !== $exam->tenant_id) {
            abort(404);
        }
        if ($request->user()->hasRole('GURU')) {
            abort_unless($exam->assessment && TeacherAccess::canAssignRombelForAssessment($request->user(), $rombel, $exam->assessment), 403);
        }

        $summary = $this->examSummaryService->getRombelSummary($exam, $rombel->id);
        $results = $this->examResultService->getResults($exam, ['rombel_id' => $rombel->id, 'per_page' => 100]);

        $this->auditService->log('REPORT_VIEW', 'Exam', $exam->id, null, ['type' => 'rombel_report', 'rombel_id' => $rombel->id]);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'results' => $results->items()
            ]
        ]);
    }

    /**
     * Get report for a specific subject across exams.
     */
    public function subjectReport(Request $request, Subject $subject)
    {
        $this->authorize('viewSubjectResults', $subject);

        $filters = $request->validate([
            'academic_year_id' => ['sometimes', 'required', 'uuid'],
            'semester_id' => ['sometimes', 'required', 'uuid'],
        ]);

        if ($request->user()->hasRole('GURU')) {
            $filters = $request->validate([
                'academic_year_id' => ['required', 'uuid'],
                'semester_id' => ['required', 'uuid'],
            ]);

            abort_unless(
                TeacherAccess::canCreateAssessment(
                    $request->user(),
                    $subject->id,
                    $filters['academic_year_id'],
                    $filters['semester_id']
                ),
                403,
                'Laporan hanya tersedia untuk penugasan mapel, tahun ajaran, dan semester aktif.'
            );
        }

        $summary = $this->examSummaryService->getSubjectSummary($subject->id, $filters);

        $this->auditService->log('REPORT_VIEW', 'Subject', $subject->id, null, ['type' => 'subject_report']);

        return response()->json([
            'success' => true,
            'data' => [
                'subject_name' => $subject->name,
                'exams' => $summary
            ]
        ]);
    }

    /**
     * Get basic question result report for an exam.
     */
    public function questionReport(Request $request, Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);

        $report = $this->questionReportService->getQuestionReport($exam);

        $this->auditService->log('REPORT_VIEW', 'Exam', $exam->id, null, ['type' => 'question_report']);

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }
}
