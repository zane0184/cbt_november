<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Semester;
use App\Services\AuditService;
use App\Support\Security\TeacherAccess;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssessmentController extends Controller
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Assessment::class);
        $query = Assessment::query();

        if ($request->user()->hasRole('GURU')) {
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

        if ($request->user()->hasRole('SISWA')) {
            // Siswa sees assessments related to their exams
            $query->whereHas('exams.participants', function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });
        }

        return response()->json(['success' => true, 'data' => $query->paginate()]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Assessment::class);
        $tenantId = $request->user()->tenant_id;
        $validated = $request->validate([
            'academic_year_id' => ['required', 'uuid', Rule::exists('academic_years', 'id')->where('tenant_id', $tenantId)],
            'semester_id' => ['required', 'uuid', Rule::exists('semesters', 'id')->where('tenant_id', $tenantId)],
            'subject_id' => ['required', 'uuid', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'title' => 'required|string|max:255',
            'assessment_type' => 'required|string|in:DAILY,MIDTERM,FINAL,PRACTICE,TRYOUT,CUSTOM',
        ]);

        $semester = Semester::query()->whereKey($validated['semester_id'])->firstOrFail();
        if ($semester->academic_year_id !== $validated['academic_year_id']) {
            return response()->json([
                'success' => false,
                'message' => 'Semester harus berada pada tahun ajaran yang dipilih.',
            ], 422);
        }

        if ($request->user()->hasRole('GURU') && !TeacherAccess::canCreateAssessment(
            $request->user(), $validated['subject_id'], $validated['academic_year_id'], $validated['semester_id']
        )) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki penugasan aktif untuk mata pelajaran, tahun ajaran, dan semester ini.'], 403);
        }

        $assessment = Assessment::create(array_merge($validated, [
            'tenant_id' => $tenantId,
            'created_by' => $request->user()->id,
            'status' => 'DRAFT'
        ]));

        $this->auditService->log('CREATE', 'Assessment', $assessment->id, null, $assessment->toArray());

        return response()->json(['success' => true, 'data' => $assessment], 201);
    }

    public function show(Assessment $assessment)
    {
        $this->authorize('view', $assessment);
        return response()->json(['success' => true, 'data' => $assessment->load('exams')]);
    }

    public function update(Request $request, Assessment $assessment)
    {
        $this->authorize('update', $assessment);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'assessment_type' => 'sometimes|required|string|in:DAILY,MIDTERM,FINAL,PRACTICE,TRYOUT,CUSTOM',
            'status' => 'sometimes|string'
        ]);

        $old = $assessment->toArray();
        $assessment->update($validated);

        $this->auditService->log('UPDATE', 'Assessment', $assessment->id, $old, $assessment->toArray());

        return response()->json(['success' => true, 'data' => $assessment]);
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
