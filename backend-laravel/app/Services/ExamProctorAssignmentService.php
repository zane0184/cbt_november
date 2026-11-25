<?php
namespace App\Services;

use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExamProctorAssignmentService
{
    public function __construct(private readonly AuditService $auditService) {}

    public function assign(User $actor, Exam $exam, string $proctorUserId, CarbonInterface $startsAt, CarbonInterface $expiresAt, string $reason): ExamProctorAssignment
    {
        throw new \LogicException('Central exam eligibility is not yet canonically defined; proctor assignment is blocked.');
    }

    public function revoke(User $actor, Exam $exam, ExamProctorAssignment $assignment, string $reason): ExamProctorAssignment
    {
        return DB::transaction(function () use ($actor, $exam, $assignment, $reason) {
            $tenantId = (string) $actor->tenant_id;
            $lockedExam = Exam::query()->where('tenant_id', $tenantId)->whereKey($exam->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('revokeProctor', $lockedExam);

            $lockedAssignment = ExamProctorAssignment::query()->where('tenant_id', $tenantId)
                ->where('exam_id', $lockedExam->id)->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            if ($lockedAssignment->status === 'REVOKED' || $lockedAssignment->revoked_at !== null) {
                return $lockedAssignment->fresh()->load(['exam', 'proctor', 'assigner', 'revoker']);
            }

            $reason = trim($reason);
            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
                throw ValidationException::withMessages(['revokeReason' => 'Alasan pencabutan wajib berisi 10–1.000 karakter.']);
            }

            $oldValues = $lockedAssignment->only(['status', 'starts_at', 'expires_at', 'revoked_at', 'revoked_by', 'revoke_reason']);
            $lockedAssignment->update(['status' => 'REVOKED', 'revoked_at' => now(), 'revoked_by' => $actor->id, 'revoke_reason' => $reason]);
            $this->auditService->log('EXAM_PROCTOR_ASSIGNMENT_REVOKE', 'ExamProctorAssignment', $lockedAssignment->id,
                $oldValues, $lockedAssignment->fresh()->only(['tenant_id', 'exam_id', 'proctor_user_id', 'status', 'revoked_at', 'revoked_by', 'revoke_reason']));
            return $lockedAssignment->fresh()->load(['exam', 'proctor', 'assigner', 'revoker']);
        });
    }
}
