<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ExamProctorAssignmentService
{
    public function __construct(protected AuditService $auditService)
    {
    }

    public function assign(Exam $exam, User $proctor, array $data): ExamProctorAssignment
    {
        return DB::transaction(function () use ($exam, $proctor, $data) {
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::authorize('manageProctors', $lockedExam);

            $lockedProctor = User::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->whereKey($proctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedProctor->hasRole('PROKTOR')) {
                throw ValidationException::withMessages([
                    'proctor_user_id' => 'Akun yang ditugaskan harus memiliki role PROKTOR pada madrasah yang sama.',
                ]);
            }

            $timezone = $lockedExam->timezone ?: config('app.timezone', 'UTC');
            $startsAt = Carbon::parse($data['starts_at'], $timezone)->utc();
            $endsAt = Carbon::parse($data['ends_at'], $timezone)->utc();

            if ($endsAt->lessThanOrEqualTo($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'Akhir penugasan harus setelah waktu mulai dalam zona waktu ujian.',
                ]);
            }

            $existing = ExamProctorAssignment::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->where('proctor_user_id', $lockedProctor->id)
                ->lockForUpdate()
                ->first();

            $oldValues = $existing?->only([
                'status', 'starts_at', 'ends_at', 'revoked_at', 'revoked_by',
            ]);

            if ($existing) {
                $existing->update([
                    'assigned_by' => auth()->id(),
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'ACTIVE',
                    'revoked_at' => null,
                    'revoked_by' => null,
                    'notes' => $data['notes'] ?? null,
                ]);
                $assignment = $existing->fresh();
            } else {
                $assignment = ExamProctorAssignment::create([
                    'exam_id' => $lockedExam->id,
                    'proctor_user_id' => $lockedProctor->id,
                    'assigned_by' => auth()->id(),
                    'starts_at' => $startsAt,
                    'ends_at' => $endsAt,
                    'status' => 'ACTIVE',
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            $this->auditService->log(
                'ASSIGN_PROCTOR',
                ExamProctorAssignment::class,
                (string) $assignment->id,
                $oldValues,
                $assignment->only([
                    'exam_id', 'proctor_user_id', 'assigned_by',
                    'starts_at', 'ends_at', 'status', 'notes',
                ])
            );

            return $assignment;
        });
    }

    public function revoke(Exam $exam, ExamProctorAssignment $assignment): void
    {
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

            Gate::authorize('manageProctors', $lockedExam);

            $lockedAssignment = ExamProctorAssignment::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->where('exam_id', $lockedExam->id)
                ->whereKey($assignment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedAssignment->status === 'REVOKED') {
                return;
            }

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
        });
    }
}
