<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamParticipantRoomPlacement;
use App\Models\ExamProctorAssignment;
use App\Models\User;
use App\Support\Security\TeacherAccess;
use Illuminate\Database\Eloquent\Builder;

class ExamMonitoringScope
{
    /**
     * Return only participants the current teacher is allowed to supervise.
     *
     * SELF_MANAGED: the creator may monitor only when TeacherAccess confirms
     * that every participant in the Exam is within their assigned academic
     * scope. CENTRALIZED: access is granted only through an active Admin
     * assignment to a specific Exam + physical Room and active placement.
     *
     * No KAMAD or generic Exam-author bypass exists.
     */
    public function participantQuery(User $user, Exam $exam): Builder
    {
        $participants = ExamParticipant::query()
            ->where('exam_participants.tenant_id', $exam->tenant_id)
            ->where('exam_participants.exam_id', $exam->id);

        if ((string) $user->tenant_id !== (string) $exam->tenant_id
            || !$user->hasRole('GURU')) {
            return $participants->whereRaw('1 = 0');
        }

        if ($exam->proctoring_mode === 'SELF_MANAGED') {
            if (!$this->isExamAuthor($user, $exam)) {
                return $participants->whereRaw('1 = 0');
            }

            return $participants;
        }

        if ($exam->proctoring_mode !== 'CENTRALIZED') {
            return $participants->whereRaw('1 = 0');
        }

        $assignedRoomIds = ExamProctorAssignment::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('proctor_user_id', $user->id)
            ->confirmedNow()
            ->select('exam_room_id');

        $participantIds = ExamParticipantRoomPlacement::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('status', 'ACTIVE')
            ->whereIn('exam_room_id', $assignedRoomIds)
            ->select('participant_id');

        return $participants->whereIn('exam_participants.id', $participantIds);
    }

    public function allowsParticipant(User $user, Exam $exam, string $participantId): bool
    {
        return $this->participantQuery($user, $exam)
            ->whereKey($participantId)
            ->exists();
    }

    /** IDs only: callers must build a status/progress allowlist, never load Answer/Score. */
    public function attemptIdsQuery(User $user, Exam $exam): Builder
    {
        return \App\Models\ExamAttempt::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('participant_id', $this->participantQuery($user, $exam)->select('exam_participants.id'))
            ->select('id');
    }

    public function isExamAuthor(User $user, Exam $exam): bool
    {
        return (string) $user->tenant_id === (string) $exam->tenant_id
            && $user->hasRole('GURU')
            && $user->hasPermission('exam.monitor')
            && (string) $exam->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);
    }
}
