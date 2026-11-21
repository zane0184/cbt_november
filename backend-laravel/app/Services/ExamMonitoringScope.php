<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamParticipant;
use App\Models\ExamParticipantRoomAssignment;
use App\Models\ExamProctorAssignment;
use App\Models\User;
use App\Support\Security\TeacherAccess;
use Illuminate\Database\Eloquent\Builder;

class ExamMonitoringScope
{
    public function participantQuery(User $user, Exam $exam): Builder
    {
        $participants = ExamParticipant::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id);

        if ($this->isExamAuthor($user, $exam) || $user->hasRole('KAMAD')) {
            return $participants;
        }

        $confirmedSessionRooms = ExamProctorAssignment::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('proctor_user_id', $user->id)
            ->confirmedNow()
            ->select('exam_session_room_id');

        $participantIds = ExamParticipantRoomAssignment::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('status', 'ACTIVE')
            ->whereIn('exam_session_room_id', $confirmedSessionRooms)
            ->select('participant_id');

        return $participants->whereIn('id', $participantIds);
    }

    public function allowsParticipant(User $user, Exam $exam, string $participantId): bool
    {
        return $this->participantQuery($user, $exam)
            ->whereKey($participantId)
            ->exists();
    }

    public function attemptIdsQuery(User $user, Exam $exam)
    {
        return \App\Models\ExamAttempt::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereIn('participant_id', $this->participantQuery($user, $exam)->select('id'))
            ->select('id');
    }

    public function isExamAuthor(User $user, Exam $exam): bool
    {
        return $user->tenant_id === $exam->tenant_id
            && $user->hasRole('GURU')
            && $user->hasPermission('exam.monitor')
            && (string) $exam->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);
    }
}
