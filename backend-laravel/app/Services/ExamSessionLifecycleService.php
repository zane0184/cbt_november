<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ExamProctorAssignment;
use App\Models\ExamSession;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamSessionLifecycleService
{
    /**
     * Synchronize session and temporary-proctor assignment lifecycle.
     *
     * Every session is locked in its own transaction. No attempt/answer data
     * is read or changed by this scheduler.
     *
     * @return array{sessions_activated:int,sessions_completed:int,assignments_expired:int,assignments_completed:int}
     */
    public function synchronize(?CarbonInterface $at = null): array
    {
        $at = $at ? CarbonImmutable::instance($at) : CarbonImmutable::now();

        $counts = [
            'sessions_activated' => 0,
            'sessions_completed' => 0,
            'assignments_expired' => 0,
            'assignments_completed' => 0,
        ];

        $candidates = ExamSession::withoutGlobalScopes()
            ->whereIn('status', ['SCHEDULED', 'ACTIVE'])
            ->where(function ($query) use ($at) {
                $query->where('starts_at', '<=', $at)
                    ->orWhere('ends_at', '<=', $at);
            })
            ->orderBy('tenant_id')
            ->orderBy('id')
            ->get(['id', 'tenant_id', 'exam_id']);

        foreach ($candidates as $candidate) {
            DB::transaction(function () use ($candidate, $at, &$counts): void {
                $session = ExamSession::withoutGlobalScopes()
                    ->where('tenant_id', $candidate->tenant_id)
                    ->whereKey($candidate->id)
                    ->lockForUpdate()
                    ->first();

                if (!$session || !in_array($session->status, ['SCHEDULED', 'ACTIVE'], true)) {
                    return;
                }

                $oldSessionStatus = $session->status;
                $newSessionStatus = null;

                if ($session->ends_at && $session->ends_at->lessThanOrEqualTo($at)) {
                    $newSessionStatus = 'COMPLETED';
                } elseif ($session->status === 'SCHEDULED'
                    && $session->starts_at
                    && $session->starts_at->lessThanOrEqualTo($at)) {
                    $newSessionStatus = 'ACTIVE';
                }

                if ($newSessionStatus !== null && $newSessionStatus !== $oldSessionStatus) {
                    $session->update(['status' => $newSessionStatus]);

                    AuditLog::create([
                        'id' => (string) Str::uuid(),
                        'tenant_id' => $session->tenant_id,
                        'user_id' => null,
                        'action' => 'EXAM_SESSION_STATUS_AUTO',
                        'entity_type' => 'ExamSession',
                        'entity_id' => $session->id,
                        'old_values' => ['status' => $oldSessionStatus],
                        'new_values' => ['status' => $newSessionStatus, 'source' => 'scheduler'],
                        'ip_address' => null,
                        'user_agent' => 'scheduler',
                        'created_at' => $at,
                    ]);

                    if ($newSessionStatus === 'ACTIVE') {
                        $counts['sessions_activated']++;
                    } elseif ($newSessionStatus === 'COMPLETED') {
                        $counts['sessions_completed']++;
                    }
                }

                $sessionRoomIds = DB::table('exam_session_rooms')
                    ->where('tenant_id', $session->tenant_id)
                    ->where('exam_id', $session->exam_id)
                    ->where('exam_session_id', $session->id)
                    ->pluck('id');

                if ($sessionRoomIds->isEmpty()) {
                    return;
                }

                $assignments = ExamProctorAssignment::withoutGlobalScopes()
                    ->where('tenant_id', $session->tenant_id)
                    ->where('exam_id', $session->exam_id)
                    ->whereIn('exam_session_room_id', $sessionRoomIds)
                    ->whereNull('revoked_at')
                    ->whereIn('status', ['PENDING', 'CONFIRMED', 'NEEDS_REVIEW'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                foreach ($assignments as $assignment) {
                    if ($session->status === 'COMPLETED') {
                        $newStatus = 'COMPLETED';
                    } elseif ($assignment->expires_at && $assignment->expires_at->lessThanOrEqualTo($at)) {
                        $newStatus = 'EXPIRED';
                    } else {
                        continue;
                    }

                    $old = $assignment->only(['status', 'expires_at', 'schedule_revision']);
                    $assignment->update(['status' => $newStatus]);

                    AuditLog::create([
                        'id' => (string) Str::uuid(),
                        'tenant_id' => $session->tenant_id,
                        'user_id' => null,
                        'action' => 'EXAM_PROCTOR_ASSIGNMENT_STATUS_AUTO',
                        'entity_type' => 'ExamProctorAssignment',
                        'entity_id' => $assignment->id,
                        'old_values' => $old,
                        'new_values' => ['status' => $newStatus, 'source' => 'scheduler'],
                        'ip_address' => null,
                        'user_agent' => 'scheduler',
                        'created_at' => $at,
                    ]);

                    if ($newStatus === 'EXPIRED') {
                        $counts['assignments_expired']++;
                    } else {
                        $counts['assignments_completed']++;
                    }
                }
            }, 3);
        }

        return $counts;
    }
}
