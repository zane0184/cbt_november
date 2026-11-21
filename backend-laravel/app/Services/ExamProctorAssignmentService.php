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

    public function assign(
        User $actor,
        Exam $exam,
        string $proctorUserId,
        CarbonInterface $startsAt,
        CarbonInterface $expiresAt,
        string $reason
    ): ExamProctorAssignment {
        return DB::transaction(function () use ($actor, $exam, $proctorUserId, $startsAt, $expiresAt, $reason) {
            $tenantId = (string) $actor->tenant_id;
            $lockedExam = Exam::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('assignProctor', $lockedExam);

            // Lock order: exam -> Guru mutex -> assignment row.
            $proctor = User::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($proctorUserId)
                ->lockForUpdate()
                ->first();

            if (!$this->isEligibleGuru($proctor, $tenantId)) {
                throw ValidationException::withMessages([
                    'selectedProctorId' => 'Penugasan hanya dapat diberikan kepada Guru dengan profil aktif pada tenant ini.',
                ]);
            }

            $reason = trim($reason);
            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
                throw ValidationException::withMessages(['reason' => 'Alasan penugasan wajib berisi 10–1.000 karakter.']);
            }

            [$start, $expiry] = $this->validatedWindow($lockedExam, $startsAt, $expiresAt);

            $assignment = ExamProctorAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('exam_id', $lockedExam->id)
                ->where('proctor_user_id', $proctor->id)
                ->lockForUpdate()
                ->first();

            $isNew = !$assignment;
            $oldValues = $assignment?->only([
                'status', 'starts_at', 'expires_at', 'assignment_reason', 'assigned_by',
                'revoked_at', 'revoked_by', 'revoke_reason',
            ]);

            $assignment ??= new ExamProctorAssignment();
            $assignment->fill([
                'tenant_id' => $tenantId,
                'exam_id' => $lockedExam->id,
                'proctor_user_id' => $proctor->id,
                'assigned_by' => $actor->id,
                'starts_at' => $start,
                'expires_at' => $expiry,
                // Assignment baru/perubahan jadwal selalu menunggu konfirmasi eksplisit.
                'status' => 'PENDING',
                'assignment_reason' => $reason,
                'revoked_at' => null,
                'revoked_by' => null,
                'revoke_reason' => null,
            ]);
            $assignment->save();

            $this->auditService->log(
                $isNew ? 'EXAM_PROCTOR_ASSIGNMENT_CREATE' : 'EXAM_PROCTOR_ASSIGNMENT_RENEW',
                'ExamProctorAssignment',
                $assignment->id,
                $oldValues,
                $assignment->only([
                    'tenant_id', 'exam_id', 'proctor_user_id', 'assigned_by',
                    'starts_at', 'expires_at', 'status', 'assignment_reason',
                ])
            );

            return $assignment->fresh()->load(['exam', 'proctor', 'assigner']);
        });
    }

    public function confirm(User $actor, Exam $exam, ExamProctorAssignment $assignment): ExamProctorAssignment
    {
        return DB::transaction(function () use ($actor, $exam, $assignment) {
            $tenantId = (string) $actor->tenant_id;
            $lockedExam = Exam::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('confirmProctor', $lockedExam);

            // Read the immutable target ID, then lock Guru before locking assignment.
            $assignmentSnapshot = ExamProctorAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('exam_id', $lockedExam->id)
                ->whereKey($assignment->id)
                ->firstOrFail();

            $proctor = User::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($assignmentSnapshot->proctor_user_id)
                ->lockForUpdate()
                ->first();

            if (!$this->isEligibleGuru($proctor, $tenantId)) {
                throw ValidationException::withMessages([
                    'assignment' => 'Guru yang ditugaskan tidak lagi aktif atau profil Gurunya tidak valid.',
                ]);
            }

            $lockedAssignment = ExamProctorAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('exam_id', $lockedExam->id)
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedAssignment->status, ['PENDING', 'NEEDS_REVIEW'], true)
                || $lockedAssignment->revoked_at !== null) {
                throw ValidationException::withMessages([
                    'assignment' => 'Hanya assignment PENDING atau NEEDS_REVIEW yang dapat dikonfirmasi.',
                ]);
            }

            [$start, $expiry] = $this->validatedWindow(
                $lockedExam,
                $lockedAssignment->starts_at,
                $lockedAssignment->expires_at
            );

            $hasConflict = ExamProctorAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('proctor_user_id', $proctor->id)
                ->where('status', 'CONFIRMED')
                ->whereNull('revoked_at')
                ->whereKeyNot($lockedAssignment->id)
                ->where('starts_at', '<', $expiry)
                ->where('expires_at', '>', $start)
                ->lockForUpdate()
                ->get()
                ->isNotEmpty();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'assignment' => 'Guru sudah memiliki penugasan Proktor CONFIRMED yang waktunya bertumpang tindih.',
                ]);
            }

            $oldValues = $lockedAssignment->only(['status', 'starts_at', 'expires_at', 'revoked_at']);
            $lockedAssignment->update(['status' => 'CONFIRMED']);

            $this->auditService->log(
                'EXAM_PROCTOR_ASSIGNMENT_CONFIRM',
                'ExamProctorAssignment',
                $lockedAssignment->id,
                $oldValues,
                $lockedAssignment->fresh()->only([
                    'tenant_id', 'exam_id', 'proctor_user_id', 'status',
                    'starts_at', 'expires_at', 'assigned_by',
                ])
            );

            return $lockedAssignment->fresh()->load(['exam', 'proctor', 'assigner']);
        });
    }

    public function revoke(User $actor, Exam $exam, ExamProctorAssignment $assignment, string $reason): ExamProctorAssignment
    {
        return DB::transaction(function () use ($actor, $exam, $assignment, $reason) {
            $tenantId = (string) $actor->tenant_id;
            $lockedExam = Exam::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('revokeProctor', $lockedExam);

            $assignmentSnapshot = ExamProctorAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('exam_id', $lockedExam->id)
                ->whereKey($assignment->id)
                ->firstOrFail();

            User::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($assignmentSnapshot->proctor_user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAssignment = ExamProctorAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('exam_id', $lockedExam->id)
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAssignment->status === 'REVOKED' || $lockedAssignment->revoked_at !== null) {
                return $lockedAssignment->fresh()->load(['exam', 'proctor', 'assigner', 'revoker']);
            }

            if (!in_array($lockedAssignment->status, ['PENDING', 'CONFIRMED', 'NEEDS_REVIEW'], true)) {
                throw ValidationException::withMessages([
                    'assignment' => 'Assignment yang sudah EXPIRED atau COMPLETED tidak dapat dicabut ulang.',
                ]);
            }

            $reason = trim($reason);
            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
                throw ValidationException::withMessages(['revokeReason' => 'Alasan pencabutan wajib berisi 10–1.000 karakter.']);
            }

            $oldValues = $lockedAssignment->only([
                'status', 'starts_at', 'expires_at', 'revoked_at', 'revoked_by', 'revoke_reason',
            ]);
            $lockedAssignment->update([
                'status' => 'REVOKED',
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
                'revoke_reason' => $reason,
            ]);

            $this->auditService->log(
                'EXAM_PROCTOR_ASSIGNMENT_REVOKE',
                'ExamProctorAssignment',
                $lockedAssignment->id,
                $oldValues,
                $lockedAssignment->fresh()->only([
                    'tenant_id', 'exam_id', 'proctor_user_id', 'status',
                    'revoked_at', 'revoked_by', 'revoke_reason',
                ])
            );

            return $lockedAssignment->fresh()->load(['exam', 'proctor', 'assigner', 'revoker']);
        });
    }

    private function isEligibleGuru(?User $guru, string $tenantId): bool
    {
        if (!$guru || (string) $guru->tenant_id !== $tenantId) {
            return false;
        }

        $roles = $guru->roles()->pluck('name')->all();

        return count($roles) === 1
            && $roles[0] === 'GURU'
            && $guru->teacherProfile()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->exists();
    }

    /**
     * Assignment interval is bounded by the published exam window and server time.
     *
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function validatedWindow(Exam $exam, CarbonInterface $startsAt, CarbonInterface $expiresAt): array
    {
        $start = \Illuminate\Support\Carbon::parse($startsAt);
        $expiry = \Illuminate\Support\Carbon::parse($expiresAt);
        $examEnd = $exam->end_at;

        if (!$examEnd
            || $start->greaterThanOrEqualTo($expiry)
            || $expiry->lessThanOrEqualTo(now())
            || $start->greaterThanOrEqualTo($examEnd)
            || $expiry->greaterThan($examEnd)) {
            throw ValidationException::withMessages([
                'expiresAt' => 'Masa penugasan harus berada sebelum batas akhir ujian dan masih berlaku.',
            ]);
        }

        return [$start, $expiry];
    }
}
