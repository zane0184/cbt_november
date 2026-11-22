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
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        return $user->hasExactRole('GURU') || $user->hasExactRole('SISWA');
    }

    public function view(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) {
            return false;
        }

        if ($user->hasExactRole('GURU')) {
            return TeacherAccess::canManageExam($user, $exam);
        }

        if ($user->hasExactRole('ADMIN_MADRASAH')) {
            return false;
        }

        if ($user->hasExactRole('SISWA')) {
            return $exam->participants()->where('user_id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Answer-key preview is an authoring capability, not a general exam-view
     * capability. Only the assigned Guru who owns the assessment may use it.
     */
    public function preview(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id || !$user->hasExactRole('GURU')) {
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
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        return $user->hasExactRole('GURU') && $user->hasPermission('exam.manage');
    }

    public function update(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if ($exam->isLocked()) return false;

        return $user->hasExactRole('GURU')
            && $user->hasPermission('exam.manage')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);
    }

    public function delete(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if ($exam->isLocked()) return false;

        return $user->hasExactRole('GURU')
            && $user->hasPermission('exam.manage')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);
    }

    public function manageQuestions(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        return $user->hasExactRole('GURU')
            && $user->hasPermission('question.manage')
            && $this->update($user, $exam);
    }

    public function manageParticipants(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        return $this->update($user, $exam) && $user->hasPermission('exam.assign');
    }

    public function generateToken(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if ($exam->status !== 'PUBLISHED' || !$exam->locked_at) return false;

        return $user->hasExactRole('GURU')
            && $user->hasPermission('exam.token')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageAssessment($user, $exam->assessment)
            && TeacherAccess::canViewExam($user, $exam);
    }

    public function lock(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        return $this->update($user, $exam);
    }

    /**
     * Closing is a distinct lifecycle action from locking/publishing.
     * It is only valid for an already-published, locked exam and is
     * idempotently allowed for an exam that is already CLOSED.
     */
    public function close(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id || !$exam->locked_at) {
            return false;
        }

        if (!in_array($exam->status, ['PUBLISHED', 'CLOSED'], true)) {
            return false;
        }

        return $user->hasExactRole('GURU')
            && $user->hasPermission('exam.manage')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);
    }

    public function startAttempt(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id || !$user->hasExactRole('SISWA')) {
            return false;
        }

        $now = now();
        $participant = $exam->participants()
            ->where('tenant_id', $exam->tenant_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$participant) {
            return false;
        }

        // An already valid IN_PROGRESS attempt remains recoverable after the
        // admission window closes. Eligibility windows gate new attempts only.
        $hasRecoverableAttempt = $participant->attempts()
            ->where('status', 'IN_PROGRESS')
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', $now)
            ->exists();

        if ($hasRecoverableAttempt) {
            return true;
        }

        $currentlyEligible = (!$participant->eligible_from || $participant->eligible_from->lte($now))
            && (!$participant->eligible_until || $participant->eligible_until->gt($now));

        return $currentlyEligible && $exam->canStartAttempt();
    }

    public function assignProctor(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if (!$user->hasExactRole('ADMIN_MADRASAH') || !$user->hasPermission('exam.proctor.assign')) return false;
        if ($exam->status !== 'PUBLISHED' || !$exam->locked_at || !$exam->end_at) return false;

        return $exam->end_at->isFuture();
    }

    public function confirmProctor(User $user, Exam $exam): bool
    {
        return $user->tenant_id === $exam->tenant_id
            && $user->hasExactRole('ADMIN_MADRASAH')
            && $user->hasPermission('exam.proctor.assign')
            && $exam->status === 'PUBLISHED'
            && $exam->locked_at !== null
            && $exam->end_at !== null
            && $exam->end_at->isFuture();
    }

    public function revokeProctor(User $user, Exam $exam): bool
    {
        return $user->tenant_id === $exam->tenant_id
            && $user->hasExactRole('ADMIN_MADRASAH')
            && $user->hasPermission('exam.proctor.assign');
    }

    public function monitor(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;

        // A Guru monitoring capability must not survive a stale or mixed
        // role assignment (for example GURU + SISWA). The active portal role
        // is exclusive; temporary Proktor status is an assignment, not a role.
        $roleNames = $user->roles()->pluck('name');
        if ($roleNames->count() !== 1 || $roleNames->first() !== 'GURU') {
            return false;
        }

        if (!$user->hasPermission('exam.monitor')) {
            return false;
        }

        $isExamAuthor = (string) $exam->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);

        $hasActiveTemporaryProctorAssignment = $user->teacherProfile()
            ->where('tenant_id', $user->tenant_id)
            ->whereNull('deleted_at')
            ->exists()
            && ExamProctorAssignment::query()
                ->where('tenant_id', $exam->tenant_id)
                ->where('exam_id', $exam->id)
                ->where('proctor_user_id', $user->id)
                ->confirmedNow()
                ->exists();

        return $isExamAuthor || $hasActiveTemporaryProctorAssignment;
    }

    public function viewExamResults(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        return app(\App\Policies\ReportPolicy::class)->viewExamResults($user, $exam);
    }

    public function publishResults(User $user, Exam $exam): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if ($user->hasExactRole('SISWA') || $user->hasExactRole('ADMIN_MADRASAH')) return false;

        return $user->hasExactRole('GURU')
            && $user->hasPermission('exam.grade')
            && ($exam->assessment
                && $user->hasPermission('exam.grade')
                && $exam->assessment
                && (string) $exam->assessment->created_by === (string) $user->id
                && TeacherAccess::canManageAssessment($user, $exam->assessment)
                && TeacherAccess::canViewExam($user, $exam));
    }
}
