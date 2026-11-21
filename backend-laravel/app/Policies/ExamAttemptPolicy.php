<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;
use App\Support\Security\TeacherAccess;

class ExamAttemptPolicy
{
    public function view(User $user, ExamAttempt $attempt): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $attempt->tenant_id) return false;

        // This policy protects the teacher/admin result-detail surface, which
        // exposes answer payloads and per-question grading data. Student self-service
        // uses the dedicated result endpoint and its restricted DTO instead.
        if ($user->hasRole('GURU')) {
            return $user->hasPermission('report.view')
                && TeacherAccess::canViewExam($user, $attempt->exam);
        }

        if ($user->hasRole('KAMAD')) {
            return $user->hasPermission('report.view');
        }

        // OPERATOR is limited to operational monitoring and must not receive
        // answer-level or score-detail access. Students use their own result route.
        return false;
    }

    public function viewScore(User $user, ExamAttempt $attempt): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $attempt->tenant_id) return false;

        if ($user->hasRole('GURU')) {
            return $user->hasPermission('report.view') && TeacherAccess::canViewExam($user, $attempt->exam);
        }

        if ($user->hasRole('ADMIN_MADRASAH') || $user->hasRole('KAMAD')) {
            return $user->hasPermission('report.view');
        }

        // Student results are visible only through the authoritative current-score pointer.
        // Historical score rows must not become visible merely because they have a higher
        // scoring_version or remain in the score history after a pointer change.
        $participant = $attempt->participant;
        $exam = $attempt->exam;
        $currentScore = $attempt->resolvedCurrentScore();

        return $participant
            && $participant->tenant_id === $attempt->tenant_id
            && $participant->user_id === $user->id
            && $exam
            && $exam->tenant_id === $attempt->tenant_id
            && $exam->show_result
            && in_array($attempt->status, ['SUBMITTED', 'EXPIRED'], true)
            && $currentScore
            && $currentScore->tenant_id === $attempt->tenant_id
            && $currentScore->attempt_id === $attempt->id
            && $currentScore->grading_status === 'FINALIZED';
    }

    public function score(User $user, ExamAttempt $attempt): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $attempt->tenant_id || !$user->hasRole('GURU')) return false;

        $exam = $attempt->exam;
        $assessment = $exam?->assessment;
        if (!$exam || !$assessment) return false;

        return $user->hasPermission('exam.grade')
            && TeacherAccess::canManageAssessment($user, $assessment)
            && TeacherAccess::canViewExam($user, $exam);
    }

    public function grade(User $user, ExamAttempt $attempt): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        // Manual question-level grading belongs to the assigned Guru only.
        // Admin Madrasah may reopen/finalize under separate audited permissions,
        // but cannot alter individual marks as a substitute for the assigned teacher.
        return $this->score($user, $attempt);
    }

    public function finalize(User $user, ExamAttempt $attempt): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $attempt->tenant_id) return false;

        if (!$user->hasRole('GURU')) return false;

        $exam = $attempt->exam;
        $assessment = $exam?->assessment;
        if (!$exam || !$assessment) return false;

        return $user->hasPermission('exam.grade')
            && TeacherAccess::canManageAssessment($user, $assessment)
            && TeacherAccess::canViewExam($user, $exam);
    }

    /**
     * Reopen applies to a FINALIZED SCORE, not to the student's exam attempt.
     * Phase 7 Decision #17 restricts this action to ADMIN_MADRASAH
     * within the authorized tenant scope.
     */
    public function reopen(User $user, ExamAttempt $attempt): bool
    {
        if ($user->hasRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $attempt->tenant_id) {
            return false;
        }

        return $user->hasRole('GURU')
            && $user->hasPermission('exam.grade')
            && $attempt->exam
            && $attempt->exam->assessment
            && TeacherAccess::canManageAssessment($user, $attempt->exam->assessment)
            && TeacherAccess::canViewExam($user, $attempt->exam);
    }
}
