<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;
use App\Support\Security\TeacherAccess;

class ExamPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        return $user->hasRole('GURU') || $user->hasRole('SISWA');
    }

    public function view(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) {
            return false;
        }

        if ($user->hasRole('GURU')) {
            return TeacherAccess::canManageExam($user, $exam);
        }

        if ($user->hasRole('ADMIN_MADRASAH')) {
            return false;
        }

        if ($user->hasRole('SISWA')) {
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
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id || !$user->hasRole('GURU')) {
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
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        return $user->hasRole('GURU') && $user->hasPermission('exam.manage');
    }

    public function update(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if ($exam->isLocked()) return false;

        return $user->hasRole('GURU')
            && $user->hasPermission('exam.manage')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);
    }

    public function delete(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if ($exam->isLocked()) return false;

        return $user->hasRole('GURU')
            && $user->hasPermission('exam.manage')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageExam($user, $exam);
    }

    public function manageQuestions(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        return $user->hasRole('GURU')
            && $user->hasPermission('question.manage')
            && $this->update($user, $exam);
    }

    public function manageParticipants(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        return $this->update($user, $exam) && $user->hasPermission('exam.assign');
    }

    public function generateToken(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if ($exam->status !== 'PUBLISHED' || !$exam->locked_at) return false;

        return $user->hasRole('GURU')
            && $user->hasPermission('exam.token')
            && $exam->assessment
            && (string) $exam->assessment->created_by === (string) $user->id
            && TeacherAccess::canManageAssessment($user, $exam->assessment)
            && TeacherAccess::canViewExam($user, $exam);
    }

    public function lock(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        return $this->update($user, $exam);
    }

    /**
     * Closing is a distinct lifecycle action from locking/publishing.
     * It is only valid for an already-published, locked exam and is
     * idempotently allowed for an exam that is already CLOSED.
     */
    public function close(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id || !$exam->locked_at) {
            return false;
        }

        if (!in_array($exam->status, ['PUBLISHED', 'CLOSED'], true)) {
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
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if (!$user->hasRole('SISWA')) return false;

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

        // Resume attempt yang sah tetap diizinkan setelah jadwal mulai berakhir,
        // selama attempt masih IN_PROGRESS, belum kedaluwarsa, dan eligibility masih aktif.
        return $exam->canStartAttempt() || $participant->attempts()
            ->where('status', 'IN_PROGRESS')
            ->where('expires_at', '>', $now)
            ->exists();
    }

    public function assignProctor(User $user, Exam $exam): bool
    {
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if (!$user->hasRole('ADMIN_MADRASAH') || !$user->hasPermission('exam.proctor.assign')) return false;
        if ($exam->status !== 'PUBLISHED' || !$exam->locked_at || !$exam->end_at) return false;

        return $exam->end_at->isFuture();
    }

    public function revokeProctor(User $user, Exam $exam): bool
    {
        return $user->tenant_id === $exam->tenant_id
            && $user->hasRole('ADMIN_MADRASAH')
            && $user->hasPermission('exam.proctor.assign');
    }

    public function monitor(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;

        if ($user->hasRole('ADMIN_MADRASAH')) {
            return false;
        }

        if (!$user->hasRole('GURU') || !$user->hasPermission('exam.monitor')) {
            return false;
        }

        // A Guru may monitor through their normal teaching scope or a temporary,
        // exam-specific Proktor assignment. The assignment grants monitoring only,
        // never answer-key, grading, or result-detail access.
        $temporaryProctorAssignment = \\App\\Models\\ExamProctorAssignment::query()
            ->where('tenant_id', $exam->tenant_id)
            ->where('exam_id', $exam->id)
            ->where('proctor_user_id', $user->id)
            ->where('status', 'ACTIVE')
            ->whereNull('revoked_at')
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())
            ->exists();

        return $temporaryProctorAssignment || TeacherAccess::canViewExam($user, $exam);
    }

    public function viewExamResults(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        return app(\App\Policies\ReportPolicy::class)->viewExamResults($user, $exam);
    }

    public function publishResults(User $user, Exam $exam): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $exam->tenant_id) return false;
        if ($user->hasRole('SISWA')) return false;

        return $user->hasRole('GURU')
            && $user->hasPermission('exam.grade')
            && ($exam->assessment
                && $user->hasPermission('exam.grade')
                && $exam->assessment
                && (string) $exam->assessment->created_by === (string) $user->id
                && TeacherAccess::canManageAssessment($user, $exam->assessment)
                && TeacherAccess::canViewExam($user, $exam));
    }
}
