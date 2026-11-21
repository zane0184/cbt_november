<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\ExamRoom;
use App\Models\ExamSession;
use App\Models\ExamSessionRoom;
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
        string $sessionRoomId,
        string $proctorUserId,
        CarbonInterface $startsAt,
        CarbonInterface $expiresAt,
        string $reason
    ): ExamProctorAssignment {
        return DB::transaction(function () use ($actor, $exam, $sessionRoomId, $proctorUserId, $startsAt, $expiresAt, $reason) {
            [$lockedExam, $sessionRoom, $session] = $this->lockScope($actor, $exam, $sessionRoomId, 'assignProctor');

            $proctor = User::query()
                ->where('tenant_id', $actor->tenant_id)
                ->whereKey($proctorUserId)
                ->lockForUpdate()
                ->first();

            if (!$this->isEligibleGuru($proctor, (string) $actor->tenant_id)) {
                throw ValidationException::withMessages([
                    'selectedProctorId' => 'Penugasan hanya dapat diberikan kepada Guru dengan profil aktif pada tenant ini.',
                ]);
            }

            $reason = trim($reason);
            if (mb_strlen($reason) < 10 || mb_strlen($reason) > 1000) {
                throw ValidationException::withMessages(['reason' => 'Alasan penugasan wajib berisi 10–1.000 karakter.']);
            }

            [$start, $expiry] = $this->validatedWindow($session, $startsAt, $expiresAt);

            // Only one open assignment is allowed per Guru and session-room.
            // Terminal assignments remain immutable history rows.
            $assignment = ExamProctorAssignment::query()
                ->where('tenant_id', $actor->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('exam_session_room_id', $sessionRoom->id)
                ->where('proctor_user_id', $proctor->id)
                ->whereNull('revoked_at')
                ->whereIn('status', ['PENDING', 'CONFIRMED', 'NEEDS_REVIEW'])
                ->lockForUpdate()
                ->first();

            $isNew = !$assignment;
            $oldValues = $assignment?->only([
                'status', 'starts_at', 'expires_at', 'schedule_revision',
                'assignment_reason', 'assigned_by',
            ]);

            $assignment ??= new ExamProctorAssignment();
            $assignment->fill([
                'tenant_id' => $actor->tenant_id,
                'exam_id' => $lockedExam->id,
                'exam_session_room_id' => $sessionRoom->id,
                'schedule_revision' => $session->schedule_revision,
                'proctor_user_id' => $proctor->id,
                'assigned_by' => $actor->id,
                'starts_at' => $start,
                'expires_at' => $expiry,
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
                    'tenant_id', 'exam_id', 'exam_session_room_id', 'schedule_revision',
                    'proctor_user_id', 'assigned_by', 'starts_at', 'expires_at',
                    'status', 'assignment_reason',
                ])
            );

            return $assignment->fresh()->load(['exam', 'sessionRoom.session', 'sessionRoom.room', 'proctor', 'assigner']);
        });
    }

    public function confirm(User $actor, Exam $exam, ExamProctorAssignment $assignment): ExamProctorAssignment
    {
        return DB::transaction(function () use ($actor, $exam, $assignment) {
            $tenantId = (string) $actor->tenant_id;
            $snapshot = ExamProctorAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('exam_id', $exam->id)
                ->whereKey($assignment->id)
                ->firstOrFail();

            [$lockedExam, $sessionRoom, $session] = $this->lockScope(
                $actor,
                $exam,
                (string) $snapshot->exam_session_room_id,
                'confirmProctor'
            );

            $proctor = User::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($snapshot->proctor_user_id)
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
                ->where('exam_session_room_id', $sessionRoom->id)
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedAssignment->status, ['PENDING', 'NEEDS_REVIEW'], true)
                || $lockedAssignment->revoked_at !== null) {
                throw ValidationException::withMessages([
                    'assignment' => 'Hanya assignment PENDING atau NEEDS_REVIEW yang dapat dikonfirmasi.',
                ]);
            }

            if ((int) $lockedAssignment->schedule_revision !== (int) $session->schedule_revision) {
                throw ValidationException::withMessages([
                    'assignment' => 'Revisi jadwal berubah. Perbarui penugasan dan tinjau ulang sebelum konfirmasi.',
                ]);
            }

            [$start, $expiry] = $this->validatedWindow(
                $session,
                $lockedAssignment->starts_at,
                $lockedAssignment->expires_at
            );

            $hasConflict = ExamProctorAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('proctor_user_id', $proctor->id)
                ->where('status', 'CONFIRMED')
                ->whereNull('revoked_at')
                ->where('id', '<>', $lockedAssignment->id)
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

            $oldValues = $lockedAssignment->only(['status', 'starts_at', 'expires_at', 'schedule_revision']);
            $lockedAssignment->update(['status' => 'CONFIRMED']);

            $this->auditService->log(
                'EXAM_PROCTOR_ASSIGNMENT_CONFIRM',
                'ExamProctorAssignment',
                $lockedAssignment->id,
                $oldValues,
                $lockedAssignment->fresh()->only([
                    'tenant_id', 'exam_id', 'exam_session_room_id', 'proctor_user_id',
                    'status', 'schedule_revision', 'starts_at', 'expires_at', 'assigned_by',
                ])
            );

            return $lockedAssignment->fresh()->load(['exam', 'sessionRoom.session', 'sessionRoom.room', 'proctor', 'assigner']);
        });
    }

    public function revoke(User $actor, Exam $exam, ExamProctorAssignment $assignment, string $reason): ExamProctorAssignment
    {
        return DB::transaction(function () use ($actor, $exam, $assignment, $reason) {
            $tenantId = (string) $actor->tenant_id;
            $snapshot = ExamProctorAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('exam_id', $exam->id)
                ->whereKey($assignment->id)
                ->firstOrFail();

            [$lockedExam, $sessionRoom] = $this->lockScope(
                $actor,
                $exam,
                (string) $snapshot->exam_session_room_id,
                'revokeProctor',
                true
            );

            User::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($snapshot->proctor_user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAssignment = ExamProctorAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('exam_id', $lockedExam->id)
                ->where('exam_session_room_id', $sessionRoom->id)
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAssignment->status === 'REVOKED' || $lockedAssignment->revoked_at !== null) {
                return $lockedAssignment->fresh()->load(['exam', 'sessionRoom.session', 'sessionRoom.room', 'proctor', 'assigner', 'revoker']);
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
                    'tenant_id', 'exam_id', 'exam_session_room_id', 'proctor_user_id',
                    'status', 'revoked_at', 'revoked_by', 'revoke_reason',
                ])
            );

            return $lockedAssignment->fresh()->load(['exam', 'sessionRoom.session', 'sessionRoom.room', 'proctor', 'assigner', 'revoker']);
        });
    }

    private function lockScope(
        User $actor,
        Exam $exam,
        string $sessionRoomId,
        string $ability,
        bool $allowInactiveSession = false
    ): array {
        $tenantId = (string) $actor->tenant_id;
        $lockedExam = Exam::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($exam->id)
            ->lockForUpdate()
            ->firstOrFail();

        Gate::forUser($actor)->authorize($ability, $lockedExam);

        $roomId = ExamSessionRoom::query()
            ->where('tenant_id', $tenantId)
            ->where('exam_id', $lockedExam->id)
            ->whereKey($sessionRoomId)
            ->value('exam_room_id');

        $room = ExamRoom::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($roomId)
            ->lockForUpdate()
            ->firstOrFail();

        $sessionRoom = ExamSessionRoom::query()
            ->where('tenant_id', $tenantId)
            ->where('exam_id', $lockedExam->id)
            ->whereKey($sessionRoomId)
            ->lockForUpdate()
            ->firstOrFail();

        $session = ExamSession::query()
            ->where('tenant_id', $tenantId)
            ->where('exam_id', $lockedExam->id)
            ->whereKey($sessionRoom->exam_session_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($sessionRoom->status !== 'ACTIVE'
            || (!$allowInactiveSession && !in_array($session->status, ['SCHEDULED', 'ACTIVE'], true))) {
            throw ValidationException::withMessages([
                'assignment' => 'Ruang atau sesi tidak lagi aktif untuk penugasan Proktor.',
            ]);
        }

        if (!$allowInactiveSession && ($lockedExam->status !== 'PUBLISHED' || !$lockedExam->locked_at)) {
            throw ValidationException::withMessages([
                'exam' => 'Penugasan Proktor hanya tersedia untuk ujian yang sudah diterbitkan dan dikunci.',
            ]);
        }

        return [$lockedExam, $sessionRoom, $session, $room];
    }

    private function isEligibleGuru(?User $guru, string $tenantId): bool
    {
        if (!$guru || (string) $guru->tenant_id !== $tenantId || $guru->deleted_at !== null) {
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
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function validatedWindow(ExamSession $session, CarbonInterface $startsAt, CarbonInterface $expiresAt): array
    {
        $start = \Illuminate\Support\Carbon::parse($startsAt);
        $expiry = \Illuminate\Support\Carbon::parse($expiresAt);

        if ($start->greaterThanOrEqualTo($expiry)
            || $expiry->lessThanOrEqualTo(now())
            || $start->lessThan($session->starts_at)
            || $expiry->greaterThan($session->ends_at)) {
            throw ValidationException::withMessages([
                'expiresAt' => 'Masa penugasan harus berada di dalam jadwal sesi dan masih berlaku.',
            ]);
        }

        return [$start, $expiry];
    }
}
