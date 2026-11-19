<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\User;
use App\Services\ExamProctorAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExamProctorAssignmentController extends Controller
{
    public function __construct(protected ExamProctorAssignmentService $assignmentService)
    {
    }

    public function index(Exam $exam)
    {
        $this->authorize('manageProctors', $exam);

        $assignments = $exam->proctorAssignments()
            ->where('tenant_id', $exam->tenant_id)
            ->with(['proctor:id,name,email', 'assigner:id,name,email', 'revoker:id,name,email'])
            ->orderByDesc('starts_at')
            ->get();

        return response()->json(['success' => true, 'data' => $assignments]);
    }

    public function store(Request $request, Exam $exam)
    {
        $this->authorize('manageProctors', $exam);

        $validated = $request->validate([
            'proctor_user_id' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where('tenant_id', $exam->tenant_id),
            ],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $proctor = User::query()
            ->where('tenant_id', $exam->tenant_id)
            ->whereKey($validated['proctor_user_id'])
            ->firstOrFail();

        if (!$proctor->hasRole('PROKTOR')) {
            return response()->json([
                'success' => false,
                'message' => 'Akun yang ditugaskan harus memiliki role PROKTOR.',
            ], 422);
        }

        $assignment = $this->assignmentService->assign($exam, $proctor, $validated);

        return response()->json(['success' => true, 'data' => $assignment], 201);
    }

    public function destroy(Exam $exam, ExamProctorAssignment $assignment)
    {
        $this->authorize('manageProctors', $exam);

        if (
            (string) $assignment->tenant_id !== (string) $exam->tenant_id
            || (string) $assignment->exam_id !== (string) $exam->id
        ) {
            abort(404);
        }

        $this->assignmentService->revoke($exam, $assignment);

        return response()->json(['success' => true, 'message' => 'Penugasan Proktor telah dicabut.']);
    }
}
