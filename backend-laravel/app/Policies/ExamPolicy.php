<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\ExamProctorAssignment;
use App\Models\User;
use App\Support\Security\TeacherAccess;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('GURU')
            || $user->hasRole('SISWA');
    }

    public function view(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id || $this->isAssignedRoomProctor($user, $exam)) {
            return false;
        }

        if ($user->hasRole('GURU')) {
            return TeacherAccess::canManageExam($user, $exam);
        }

        if ($user->hasRole('SISWA')) {
            return $exam->participants()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    public function preview(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id || !$user->hasRole('GURU') || $this->isAssignedRoomProctor($user, $exam)) {
            return false;
        }

        $assessment = $exam->assessment;
        if (!$assessment
            || !$user->hasPermission('exam.manage')
            || !$user->hasPermission('question.manage')
            || (string) $assessment->created_by !== (string) $user->id) {
            return false;
        }

        return TeacherAccess::canManageAssessment($user, $assessment)
            && TeacherAccess::canManageExam($user, $exam);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('GURU') && $user->hasPermission('exam.manage');
    }

    public function update(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id || $exam->isLocked() || $this->isAssignedRoomProctor($user, $exam)) {
            return false;
        }

        return $user->hasRole('GURU')
            && $user->hasPermission('exam.manage')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);
    }

    public function delete(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id || $exam->isLocked() || $this->isAssignedRoomProctor($user, $exam)) {
            return false;
        }

        return $user->hasRole('GURU')
            && $user->hasPermission('exam.manage')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);
    }

    public function viewQuestionBank(User $user, Exam $exam): bool
    {
        return !$this->isAssignedRoomProctor($user, $exam)
            && $user->hasRole('GURU')
            && TeacherAccess::canManageExam($user, $exam);
    }

    public function manageQuestions(User $user, Exam $exam): bool
    {
        return !$this->isAssignedRoomProctor($user, $exam)
            && $user->hasRole('GURU')
            && $user->hasPermission('question.manage')
            && $this->update($user, $exam);
    }

    public function manageParticipants(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam) && $user->hasPermission('exam.assign');
    }

    public function generateToken(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id || $exam->status !== 'PUBLISHED' || !$exam->locked_at || $this->isAssignedRoomProctor($user, $exam)) {
            return false;
        }

        return $user->hasRole('GURU')
            && $user->hasPermission('exam.token')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageAssessment($user, $exam->assessment)
            && TeacherAccess::canViewExam($user, $exam);
    }

    public function lock(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }

    /** Closing an exam is exclusively a Guru lifecycle operation. */
    public function close(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id || !$exam->locked_at
            || !in_array($exam->status, ['PUBLISHED', 'CLOSED'], true)) {
            return false;
        }

        return $user->hasRole('GURU')
            && $user->hasPermission('exam.manage')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);
    }

    public function startAttempt(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id || !$user->hasRole('SISWA')) {
            return false;
        }

        $now = now();
        $participant = $exam->participants()
            ->where('tenant_id', $exam->tenant_id)
            ->where('user_id', $user->id)
            ->where(function ($query) use ($now) {
                $query->whereNull('eligible_from')->orWhere('eligible_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('eligible_until')->orWhere('eligible_until', '>', $now);
            })
            ->first();

        if (!$participant) {
            return false;
        }

        return $exam->canStartAttempt() || $participant->attempts()
            ->where('status', 'IN_PROGRESS')
            ->where('expires_at', '>', $now)
            ->exists();
    }

    public function manageCentralRoomAssignments(User $user, Exam $exam): bool
    {
        return $user->tenant_id === $exam->tenant_id
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('exam.proctor.assign')
            && $exam->isCentralized()
            && $exam->status === 'PUBLISHED'
            && $exam->isLocked()
            && $exam->end_at
            && $exam->end_at->isFuture();
    }

    public function assignProctor(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id
            || !$user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            || !$user->hasPermission('exam.proctor.assign')
            || $exam->status !== 'PUBLISHED'
            || !$exam->locked_at
            || !$exam->end_at) {
            return false;
        }

        return $exam->end_at->isFuture();
    }

    public function revokeProctor(User $user, Exam $exam): bool
    {
        return $user->tenant_id === $exam->tenant_id
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('exam.proctor.assign');
    }

    public function monitor(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id) {
            return false;
        }

        if (!$user->hasRole('GURU')) {
            return false;
        }

        if ($exam->isCentralized()) {
            return \\App\\Models\\ExamRoomAssignment::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('scheduled_start', '<=', now())
                ->where('scheduled_end', '>', now())
                ->whereHas('activeProctors', fn ($query) => $query->where('user_id', $user->id))
                ->exists();
        }

        return $user->hasPermission('exam.monitor') && TeacherAccess::canViewExam($user, $exam);
    }

    private function isAssignedRoomProctor(User $user, Exam $exam): bool
    {
        if (!$exam->isCentralized() || $user->tenant_id !== $exam->tenant_id) {
            return false;
        }

        return \\App\\Models\\ExamRoomAssignment::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('exam_id', $exam->id)
            ->whereHas('activeProctors', fn ($query) => $query->where('user_id', $user->id))
            ->exists();
    }

    public function viewExamResults(User $user, Exam $exam): bool
    {
        return app(\App\Policies\ReportPolicy::class)->viewExamResults($user, $exam);
    }

    public function publishResults(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id
            || $user->hasRole('PROKTOR')
            || $user->hasRole('SISWA')
            || $user->hasRole('ADMIN_MADRASAH')) {
            return false;
        }

        return !$this->isAssignedRoomProctor($user, $exam)
            && $user->hasRole('GURU')
            && $user->hasPermission('exam.grade')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageAssessment($user, $exam->assessment)
            && TeacherAccess::canViewExam($user, $exam);
    }
}
