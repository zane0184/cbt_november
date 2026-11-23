<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LearningObjective;
use App\Models\Rombel;
use App\Models\Subject;
use App\Models\User;
use App\Models\TpAchievement;
use App\Models\AtpAchievement;
use App\Models\CpAchievement;
use App\Policies\ReportPolicy;
use App\Support\Security\TeacherAccess;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CurriculumAnalyticsController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Curriculum achievements summary for a student.
     */
    public function studentAnalytics(Request $request, User $student)
    {
        if ($request->user()->tenant_id !== $student->tenant_id) {
            abort(403, 'Unauthorized tenant access.');
        }

        $policy = new ReportPolicy();
        if (!$policy->viewStudentResults($request->user(), $student)) {
            abort(403, 'Unauthorized role access.');
        }

        $subjectId = $request->query('subject_id');
        $academicYearId = $request->query('academic_year_id');
        $semesterId = $request->query('semester_id');

        if ($request->user()->hasRole('GURU')) {
            $filters = $request->validate([
                'subject_id' => ['required', 'uuid'],
                'academic_year_id' => ['required', 'uuid'],
                'semester_id' => ['required', 'uuid'],
            ]);
            $subjectId = $filters['subject_id'];
            $academicYearId = $filters['academic_year_id'];
            $semesterId = $filters['semester_id'];
            abort_unless(TeacherAccess::canViewStudentInAssessmentScope(
                $request->user(), $student, $subjectId, $academicYearId, $semesterId
            ), 403, 'Capaian kurikulum siswa hanya tersedia pada penugasan aktif Guru.');
            $assignedRombelIds = TeacherAccess::assignedRombelIdsForScope(
                $request->user(), $subjectId, $academicYearId, $semesterId
            );
            abort_unless($assignedRombelIds !== [], 403);
        }

        $tenantId = $student->tenant_id;

        $tpQuery = TpAchievement::where('student_id', $student->id)
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->with(['learningObjective', 'contributions']);

        $atpQuery = AtpAchievement::where('student_id', $student->id)
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->with('learningPath');

        $cpQuery = CpAchievement::where('student_id', $student->id)
            ->where('tenant_id', $tenantId)
            ->where('is_current', true)
            ->with('learningOutcome');

        if ($request->user()->hasRole('GURU')) {
            $tpQuery->whereIn('rombel_id', $assignedRombelIds);
            $atpQuery->whereIn('rombel_id', $assignedRombelIds);
            $cpQuery->whereIn('rombel_id', $assignedRombelIds);
        }

        if ($subjectId) {
            $tpQuery->where('subject_id', $subjectId);
            $atpQuery->where('subject_id', $subjectId);
            $cpQuery->where('subject_id', $subjectId);
        }
        if ($academicYearId) {
            $tpQuery->where('academic_year_id', $academicYearId);
            $atpQuery->where('academic_year_id', $academicYearId);
            $cpQuery->where('academic_year_id', $academicYearId);
        }
        if ($semesterId) {
            $tpQuery->where('semester_id', $semesterId);
            $atpQuery->where('semester_id', $semesterId);
            $cpQuery->where('semester_id', $semesterId);
        }

        $tpAchievements = $tpQuery->get();
        $atpAchievements = $atpQuery->get();
        $cpAchievements = $cpQuery->get();

        $this->auditService->log('ANALYTICS_CURRICULUM_STUDENT_VIEW', 'User', $student->id);

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                ],
                'tp_achievements' => $tpAchievements,
                'atp_achievements' => $atpAchievements,
                'cp_achievements' => $cpAchievements,
            ]
        ]);
    }

    /**
     * Aggregated curriculum analytics for a Rombel.
     */
    public function rombelAnalytics(Request $request, Rombel $rombel)
    {
        if ($request->user()->tenant_id !== $rombel->tenant_id) {
            abort(403, 'Unauthorized tenant access.');
        }

        if (!$request->user()->hasRole('ADMIN_MADRASAH') && !$request->user()->hasRole('GURU')) {
            abort(403, 'Unauthorized role access.');
        }

        $subjectId = $request->query('subject_id');
        $academicYearId = $request->query('academic_year_id');
        $semesterId = $request->query('semester_id');

        if ($request->user()->hasRole('GURU')) {
            $filters = $request->validate([
                'subject_id' => ['required', 'uuid'],
                'academic_year_id' => ['required', 'uuid'],
                'semester_id' => ['required', 'uuid'],
            ]);
            $subjectId = $filters['subject_id'];
            $academicYearId = $filters['academic_year_id'];
            $semesterId = $filters['semester_id'];
            abort_unless(in_array($rombel->id, TeacherAccess::assignedRombelIdsForScope(
                $request->user(), $subjectId, $academicYearId, $semesterId
            ), true) && $rombel->academic_year_id === $academicYearId, 403);
        }

        $tenantId = $rombel->tenant_id;

        $tpQuery = TpAchievement::where('rombel_id', $rombel->id)
            ->where('tenant_id', $tenantId)
            ->where('is_current', true);

        $atpQuery = AtpAchievement::where('rombel_id', $rombel->id)
            ->where('tenant_id', $tenantId)
            ->where('is_current', true);

        $cpQuery = CpAchievement::where('rombel_id', $rombel->id)
            ->where('tenant_id', $tenantId)
            ->where('is_current', true);

        if ($subjectId) {
            $tpQuery->where('subject_id', $subjectId);
            $atpQuery->where('subject_id', $subjectId);
            $cpQuery->where('subject_id', $subjectId);
        }
        if ($academicYearId) {
            $tpQuery->where('academic_year_id', $academicYearId);
            $atpQuery->where('academic_year_id', $academicYearId);
            $cpQuery->where('academic_year_id', $academicYearId);
        }
        if ($semesterId) {
            $tpQuery->where('semester_id', $semesterId);
            $atpQuery->where('semester_id', $semesterId);
            $cpQuery->where('semester_id', $semesterId);
        }

        $tpList = $tpQuery->get();
        $atpList = $atpQuery->get();
        $cpList = $cpQuery->get();

        $this->auditService->log('ANALYTICS_CURRICULUM_ROMBEL_VIEW', 'Rombel', $rombel->id);

        return response()->json([
            'success' => true,
            'data' => [
                'rombel' => [
                    'id' => $rombel->id,
                    'name' => $rombel->name,
                ],
                'summary' => [
                    'total_tp_evaluations' => $tpList->count(),
                    'tp_tercapai_count' => $tpList->where('status', 'TERCAPAI')->count(),
                    'tp_belum_tercapai_count' => $tpList->where('status', 'BELUM_TERCAPAI')->count(),
                    'tp_belum_dinilai_count' => $tpList->where('status', 'BELUM_DINILAI')->count(),
                    'average_tp_score' => $tpList->whereNotNull('score')->avg('score') ? round($tpList->whereNotNull('score')->avg('score'), 2) : null,
                    'average_atp_score' => $atpList->whereNotNull('score')->avg('score') ? round($atpList->whereNotNull('score')->avg('score'), 2) : null,
                    'average_cp_score' => $cpList->whereNotNull('score')->avg('score') ? round($cpList->whereNotNull('score')->avg('score'), 2) : null,
                ],
                'tp_achievements' => $tpList,
            ]
        ]);
    }

    /**
     * Subject-wide curriculum analytics.
     */
    public function subjectAnalytics(Request $request, Subject $subject)
    {
        if ($request->user()->tenant_id !== $subject->tenant_id) {
            abort(403, 'Unauthorized tenant access.');
        }

        if (!$request->user()->hasRole('ADMIN_MADRASAH') && !$request->user()->hasRole('GURU')) {
            abort(403, 'Unauthorized role access.');
        }

        $tenantId = $subject->tenant_id;
        $tpQuery = TpAchievement::where('subject_id', $subject->id)
            ->where('tenant_id', $tenantId)
            ->where('is_current', true);

        if ($request->user()->hasRole('GURU')) {
            $filters = $request->validate([
                'academic_year_id' => ['required', 'uuid'],
                'semester_id' => ['required', 'uuid'],
            ]);
            $assignedRombelIds = TeacherAccess::assignedRombelIdsForScope(
                $request->user(), $subject->id, $filters['academic_year_id'], $filters['semester_id']
            );
            abort_unless($assignedRombelIds !== [], 403);
            $tpQuery->where('academic_year_id', $filters['academic_year_id'])
                ->where('semester_id', $filters['semester_id'])
                ->whereIn('rombel_id', $assignedRombelIds);
        }

        $tpList = $tpQuery->get();

        $this->auditService->log('ANALYTICS_CURRICULUM_SUBJECT_VIEW', 'Subject', $subject->id);

        return response()->json([
            'success' => true,
            'data' => [
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                ],
                'summary' => [
                    'total_tp_evaluations' => $tpList->count(),
                    'tercapai_count' => $tpList->where('status', 'TERCAPAI')->count(),
                    'belum_tercapai_count' => $tpList->where('status', 'BELUM_TERCAPAI')->count(),
                    'belum_dinilai_count' => $tpList->where('status', 'BELUM_DINILAI')->count(),
                    'average_score' => $tpList->whereNotNull('score')->avg('score') ? round($tpList->whereNotNull('score')->avg('score'), 2) : null,
                ]
            ]
        ]);
    }

    /**
     * Detail analytics for a single Learning Objective (TP).
     */
    public function tpAnalytics(Request $request, LearningObjective $tp)
    {
        if ($request->user()->tenant_id !== $tp->tenant_id) {
            abort(403, 'Unauthorized tenant access.');
        }

        if (!$request->user()->hasRole('ADMIN_MADRASAH') && !$request->user()->hasRole('GURU')) {
            abort(403, 'Unauthorized role access.');
        }

        $tenantId = $tp->tenant_id;

        // LearningObjective intentionally has no subject_id. Resolve the
        // subject through its CP (LearningOutcome), and fail closed if the
        // curriculum relationship is missing or crosses tenant boundaries.
        $outcome = $tp->outcome;
        abort_unless(
            $outcome
                && $outcome->tenant_id === $tenantId
                && $outcome->subject_id
                && $outcome->curriculum_id
                && $outcome->phase_id,
            409,
            'TP tidak memiliki relasi CP/subject yang valid untuk analitik.'
        );

        $subjectId = $outcome->subject_id;
        $achievementQuery = TpAchievement::where('learning_objective_id', $tp->id)
            ->where('tenant_id', $tenantId)
            ->where('subject_id', $subjectId)
            ->where('is_current', true)
            ->with(['student', 'contributions']);

        if ($request->user()->hasRole('GURU')) {
            $filters = $request->validate([
                'academic_year_id' => ['required', 'uuid'],
                'semester_id' => ['required', 'uuid'],
            ]);
            $assignedRombelIds = TeacherAccess::assignedRombelIdsForScope(
                $request->user(), $subjectId, $filters['academic_year_id'], $filters['semester_id']
            );
            abort_unless($assignedRombelIds !== [], 403);
            $achievementQuery->where('academic_year_id', $filters['academic_year_id'])
                ->where('semester_id', $filters['semester_id'])
                ->whereIn('rombel_id', $assignedRombelIds);
        }

        $achievements = $achievementQuery->get();

        $this->auditService->log('ANALYTICS_CURRICULUM_TP_VIEW', 'LearningObjective', $tp->id);

        return response()->json([
            'success' => true,
            'data' => [
                'tp' => [
                    'id' => $tp->id,
                    'code' => $tp->code,
                    'title' => $tp->title,
                ],
                'summary' => [
                    'total_students' => $achievements->count(),
                    'tercapai_count' => $achievements->where('status', 'TERCAPAI')->count(),
                    'belum_tercapai_count' => $achievements->where('status', 'BELUM_TERCAPAI')->count(),
                    'belum_dinilai_count' => $achievements->where('status', 'BELUM_DINILAI')->count(),
                    'average_score' => $achievements->whereNotNull('score')->avg('score') ? round($achievements->whereNotNull('score')->avg('score'), 2) : null,
                ],
                'achievements' => $achievements
            ]
        ]);
    }
}
