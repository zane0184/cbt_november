<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\User;
use App\Models\Rombel;
use App\Models\Subject;
use App\Services\Analytics\ExamAnalyticsService;
use App\Services\Analytics\ItemAnalysisService;
use App\Services\Analytics\StudentAnalyticsService;
use App\Services\Analytics\RombelAnalyticsService;
use App\Services\Analytics\SubjectAnalyticsService;
use App\Services\AuditService;
use App\Policies\ReportPolicy;
use App\Support\Security\TeacherAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    public function __construct(
        protected ExamAnalyticsService $examService,
        protected ItemAnalysisService $itemService,
        protected StudentAnalyticsService $studentService,
        protected RombelAnalyticsService $rombelService,
        protected SubjectAnalyticsService $subjectService,
        protected AuditService $auditService
    ) {}

    public function examSummary(Request $request, Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);

        $cacheKey = "tenant_{$request->user()->tenant_id}:analytics:exam:{$exam->id}:summary";
        $data = Cache::remember($cacheKey, 3600, fn() => $this->examService->getSummary($exam));

        $this->auditService->log('ANALYTICS_VIEW', 'Exam', $exam->id, null, ['type' => 'summary']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function examDistribution(Request $request, Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);

        $cacheKey = "tenant_{$request->user()->tenant_id}:analytics:exam:{$exam->id}:distribution";
        $data = Cache::remember($cacheKey, 3600, fn() => $this->examService->getDistribution($exam));

        $this->auditService->log('ANALYTICS_VIEW', 'Exam', $exam->id, null, ['type' => 'distribution']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function examItems(Request $request, Exam $exam)
    {
        $this->authorize('viewExamResults', $exam);

        $cacheKey = "tenant_{$request->user()->tenant_id}:analytics:exam:{$exam->id}:items";
        $data = Cache::remember($cacheKey, 3600, fn() => $this->itemService->getAnalysis($exam));

        $this->auditService->log('ITEM_ANALYSIS_VIEW', 'Exam', $exam->id, null);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function rombelExamAnalytics(Request $request, Rombel $rombel, Exam $exam)
    {
        // Simple ownership check
        if ($rombel->tenant_id !== $request->user()->tenant_id || $exam->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }
        $this->authorize('viewExamResults', $exam);
        if ($request->user()->hasExactRole('GURU')) {
            abort_unless($exam->assessment && TeacherAccess::canAssignRombelForAssessment($request->user(), $rombel, $exam->assessment), 403);
        }

        $cacheKey = "tenant_{$request->user()->tenant_id}:analytics:rombel:{$rombel->id}:exam:{$exam->id}";
        $data = Cache::remember($cacheKey, 3600, fn() => $this->rombelService->getRombelExamAnalytics($rombel, $exam));

        $this->auditService->log('ANALYTICS_VIEW', 'Rombel', $rombel->id, null, ['exam_id' => $exam->id]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function studentAnalytics(Request $request, User $student)
    {
        $this->authorize('viewStudentResults', $student);

        $filters = $request->only(['subject_id', 'academic_year_id', 'semester_id']);
        if ($request->user()->hasExactRole('GURU')) {
            $filters = $request->validate([
                'subject_id' => ['required', 'uuid'],
                'academic_year_id' => ['required', 'uuid'],
                'semester_id' => ['required', 'uuid'],
            ]);
            abort_unless(TeacherAccess::canViewStudentInAssessmentScope(
                $request->user(), $student, $filters['subject_id'], $filters['academic_year_id'], $filters['semester_id']
            ), 403, 'Analitik siswa hanya tersedia pada penugasan aktif Guru.');
            $filters['allowed_exam_ids'] = TeacherAccess::allowedExamIdsForStudentScope(
                $request->user(), $filters['subject_id'], $filters['academic_year_id'], $filters['semester_id']
            );
        }

        // No cache for student trend as it depends on filters and visibility rules
        $data = $this->studentService->getStudentSummary($student, $filters);

        $this->auditService->log('ANALYTICS_VIEW', 'User', $student->id, null, ['type' => 'student_trend']);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function subjectAnalytics(Request $request, Subject $subject)
    {
        $this->authorize('viewSubjectResults', $subject);

        $filters = $request->only(['academic_year_id']);
        $data = $this->subjectService->getSubjectAnalytics($subject, $filters);

        $this->auditService->log('ANALYTICS_VIEW', 'Subject', $subject->id, null);

        return response()->json(['success' => true, 'data' => $data]);
    }
}
