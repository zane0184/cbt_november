<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Services\AuditService;
use App\Support\Security\TeacherAccess;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Assessment::class);
        $query = Assessment::query();

        if ($request->user()->hasExactRole('GURU')) {
            $profileIds = TeacherAccess::profileIds($request->user());
            $query->whereHas('subject')
                ->whereNotNull('academic_year_id')
                ->whereNotNull('semester_id')
                ->whereExists(function ($assignment) use ($request, $profileIds) {
                    $assignment->selectRaw('1')
                        ->from('teacher_subject_assignments as tsa')
                        ->whereColumn('tsa.tenant_id', 'assessments.tenant_id')
                        ->whereColumn('tsa.subject_id', 'assessments.subject_id')
                        ->whereColumn('tsa.academic_year_id', 'assessments.academic_year_id')
                        ->whereColumn('tsa.semester_id', 'assessments.semester_id')
                        ->whereIn('tsa.teacher_profile_id', $profileIds)
                        ->where('tsa.status', 'ACTIVE')
                        ->where(function ($dates) {
                            $dates->whereNull('tsa.starts_at')->orWhereDate('tsa.starts_at', '<=', now()->toDateString());
                        })
                        ->where(function ($dates) {
                            $dates->whereNull('tsa.ends_at')->orWhereDate('tsa.ends_at', '>=', now()->toDateString());
                        });
                });
        }

        if ($request->user()->hasExactRole('SISWA')) {
            // Siswa sees assessments related to their exams
            $query->whereHas('exams.participants', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        return response()->json(['success' => true, 'data' => $query->paginate()]);
    }

    public function show(Assessment $assessment)
    {
        $this->authorize('view', $assessment);
        return response()->json(['success' => true, 'data' => $assessment->load('exams')]);
    }

    public function destroy(Assessment $assessment)
    {
        $this->authorize('delete', $assessment);
        if ($assessment->exams()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Cannot delete assessment with existing exams.'], 422);
        }

        $this->auditService->log('DELETE', 'Assessment', $assessment->id, $assessment->toArray(), null);
        $assessment->delete();

        return response()->json(['success' => true]);
    }
}
