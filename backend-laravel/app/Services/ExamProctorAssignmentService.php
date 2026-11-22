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
        return DB::transaction(function () use ($actor, $exam, $proctorUserId, $startsAt, $expiresAt, $reason) {
            $tenantId = (string) $actor->tenant_id;
            $lockedExam = Exam::query()->where('tenant_id', $tenantId)->whereKey($exam->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('assignProctor', $lockedExam);

            $proctor = User::query()->where('tenant_id', $tenantId)->whereKey($proctorUserId)
                ->whereNull('deleted_at')->lockForUpdate()->first();
            if (!$proctor
                || $proctor->roles()->count() !== 1
                || !$proctor->roles()->where('name', 'GURU')->exists()
                || !$proctor->teacherProfile()->where('tenant_id', $tenantId)->whereNull('deleted_at')->exists()) {
                throw ValidationException::withMessages(['selectedProctorId' => 'Penugasan Proktor hanya dapat diberikan kepada akun Guru aktif pada tenant yang sama.']);
            }

            $reason = trim($reason);
            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
                throw ValidationException::withMessages(['reason' => 'Alasan penugasan wajib berisi 10–1.000 karakter.']);
            }

            $start = \Illuminate\Support\Carbon::parse($startsAt);
            $expiry = \Illuminate\Support\Carbon::parse($expiresAt);
            $examEnd = $lockedExam->end_at;
            if (!$examEnd || $start->greaterThanOrEqualTo($expiry) || $expiry->lessThanOrEqualTo(now())
                || $start->greaterThanOrEqualTo($examEnd) || $expiry->greaterThan($examEnd)) {
                throw ValidationException::withMessages(['expiresAt' => 'Masa penugasan harus berada sebelum batas akhir ujian dan masih berlaku.']);
            }

            $assignment = ExamProctorAssignment::query()->where('tenant_id', $tenantId)
                ->where('exam_id', $lockedExam->id)->where('proctor_user_id', $proctor->id)
                ->lockForUpdate()->first();
            $isNew = !$assignment;
            $oldValues = $assignment?->only(['status', 'starts_at', 'expires_at', 'assignment_reason', 'assigned_by', 'revoked_at', 'revoked_by', 'revoke_reason']);

            $assignment ??= new ExamProctorAssignment();
            $assignment->fill([
                'tenant_id' => $tenantId, 'exam_id' => $lockedExam->id, 'proctor_user_id' => $proctor->id,
                'assigned_by' => $actor->id, 'starts_at' => $start, 'expires_at' => $expiry,
                'status' => 'ACTIVE', 'assignment_reason' => $reason,
                'revoked_at' => null, 'revoked_by' => null, 'revoke_reason' => null,
            ]);
            $assignment->save();

            $this->auditService->log(
                $isNew ? 'EXAM_PROCTOR_ASSIGNMENT_CREATE' : 'EXAM_PROCTOR_ASSIGNMENT_RENEW',
                'ExamProctorAssignment', $assignment->id, $oldValues,
                $assignment->only(['tenant_id', 'exam_id', 'proctor_user_id', 'assigned_by', 'starts_at', 'expires_at', 'status', 'assignment_reason'])
            );
            return $assignment->fresh()->load(['exam', 'proctor', 'assigner']);
        });
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
