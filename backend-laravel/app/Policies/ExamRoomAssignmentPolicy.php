<?php

namespace App\Policies;

use App\Models\ExamRoomAssignment;
use App\Models\User;

class ExamRoomAssignmentPolicy
{
    public function view(User $user, ExamRoomAssignment $assignment): bool
    {
        if ($user->tenant_id !== $assignment->tenant_id) return false;

        return $this->isAdminManager($user, $assignment)
            || $this->isAssignedProctorNow($user, $assignment);
    }

    public function manageProctors(User $user, ExamRoomAssignment $assignment): bool
    {
        return $this->isAdminManager($user, $assignment);
    }

    public function monitor(User $user, ExamRoomAssignment $assignment): bool
    {
        return $this->isAssignedProctorNow($user, $assignment);
    }

    public function viewParticipants(User $user, ExamRoomAssignment $assignment): bool
    {
        return $this->isAssignedProctorNow($user, $assignment);
    }

    public function releaseToken(User $user, ExamRoomAssignment $assignment): bool
    {
        return $this->isAssignedProctorNow($user, $assignment);
    }

    public function resetLogin(User $user, ExamRoomAssignment $assignment): bool
    {
        return $this->isAssignedProctorNow($user, $assignment);
    }

    public function grantExtraTime(User $user, ExamRoomAssignment $assignment): bool
    {
        return $this->isAssignedProctorNow($user, $assignment);
    }

    public function printAttendance(User $user, ExamRoomAssignment $assignment): bool
    {
        return $this->isAssignedProctorNow($user, $assignment);
    }

    /**
     * Explicitly deny question/answer-key access through the room-proctor context.
     * Existing Guru authoring rights remain governed by the separate Exam/Question policies.
     */
    public function viewQuestionBank(User $user, ExamRoomAssignment $assignment): bool
    {
        return false;
    }

    public function viewAnswerKey(User $user, ExamRoomAssignment $assignment): bool
    {
        return false;
    }

    private function isAdminManager(User $user, ExamRoomAssignment $assignment): bool
    {
        return $user->tenant_id === $assignment->tenant_id
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('exam.proctor.assign')
            && $assignment->exam?->isCentralized() === true;
    }

    private function isAssignedProctorNow(User $user, ExamRoomAssignment $assignment): bool
    {
        if ($user->tenant_id !== $assignment->tenant_id
            || !$user->roles()->where('name', 'GURU')->exists()
            || !$assignment->exam?->isCentralized()
            || now()->lt($assignment->scheduled_start)
            || !now()->lt($assignment->scheduled_end)) {
            return false;
        }

        return $assignment->activeProctors()
            ->where('user_id', $user->id)
            ->exists();
    }
}
