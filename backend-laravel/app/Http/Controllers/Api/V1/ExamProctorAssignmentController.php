<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExamProctorAssignmentController extends Controller
{
    public function __construct(protected AuditService $auditService)
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

        $assignment = DB::transaction(function () use ($exam, $proctor, $validated) {
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->authorize('manageProctors', $lockedExam);

            $existing = ExamProctorAssignment::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('proctor_user_id', $proctor->id)
                ->lockForUpdate()
                ->first();

            $oldValues = $existing?->only(['status', 'starts_at', 'ends_at', 'revoked_at', 'revoked_by']);

            if ($existing) {
                $existing->update([
                    'assigned_by' => auth()->id(),
                    'starts_at' => $validated['starts_at'],
                    'ends_at' => $validated['ends_at'] ?? null,
                    'status' => 'ACTIVE',
                    'revoked_at' => null,
                    'revoked_by' => null,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $assignment = $existing->fresh();
            } else {
                $assignment = ExamProctorAssignment::create([
                    'exam_id' => $lockedExam->id,
                    'proctor_user_id' => $proctor->id,
                    'assigned_by' => auth()->id(),
                    'starts_at' => $validated['starts_at'],
                    'ends_at' => $validated['ends_at'] ?? null,
                    'status' => 'ACTIVE',
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            $this->auditService->log(
                'ASSIGN_PROCTOR',
                ExamProctorAssignment::class,
                (string) $assignment->id,
                $oldValues,
                $assignment->only(['exam_id', 'proctor_user_id', 'assigned_by', 'starts_at', 'ends_at', 'status', 'notes'])
            );

            return $assignment;
        });

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

        DB::transaction(function () use ($exam, $assignment) {
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize('manageProctors', $lockedExam);

            $lockedAssignment = ExamProctorAssignment::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAssignment->status !== 'REVOKED') {
                $oldValues = $lockedAssignment->only(['status', 'revoked_at', 'revoked_by']);
                $lockedAssignment->update([
                    'status' => 'REVOKED',
                    'revoked_at' => now(),
                    'revoked_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    'REVOKE_PROCTOR_ASSIGNMENT',
                    ExamProctorAssignment::class,
                    (string) $lockedAssignment->id,
                    $oldValues,
                    $lockedAssignment->only(['status', 'revoked_at', 'revoked_by'])
                );
            }
        });

        return response()->json(['success' => true, 'message' => 'Penugasan Proktor telah dicabut.']);
    }
}
