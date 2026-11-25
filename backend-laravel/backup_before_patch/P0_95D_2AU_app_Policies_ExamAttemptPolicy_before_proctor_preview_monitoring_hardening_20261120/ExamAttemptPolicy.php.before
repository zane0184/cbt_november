<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;
use App\Support\Security\TeacherAccess;

class ExamAttemptPolicy
{
    public function view(User $user, ExamAttempt $attempt): bool
    {
        if ($user->tenant_id !== $attempt->tenant_id) return false;

        if ($user->hasRole('GURU')) {
            return TeacherAccess::canViewExam($user, $attempt->exam);
        }

        if ($user->hasRole('ADMIN_MADRASAH') ||
            $user->hasRole('OPERATOR') ||
            $user->hasRole('KAMAD') ||
            $user->hasRole('PROKTOR')) {
            return true;
        }

        return $attempt->participant->user_id === $user->id;
    }

    public function viewScore(User $user, ExamAttempt $attempt): bool
    {
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
        if ($user->tenant_id !== $attempt->tenant_id) return false;
        if ($user->hasRole('PROKTOR') || $user->hasRole('KAMAD') || $user->hasRole('OPERATOR')) return false;

        if ($user->hasRole('GURU')) {
            return $user->hasPermission('exam.grade') && TeacherAccess::canViewExam($user, $attempt->exam);
        }

        return $user->hasRole('ADMIN_MADRASAH') && $user->hasPermission('exam.grade');
    }

    public function grade(User $user, ExamAttempt $attempt): bool
    {
        return $this->score($user, $attempt);
    }

    public function finalize(User $user, ExamAttempt $attempt): bool
    {
        if ($user->tenant_id !== $attempt->tenant_id) return false;
        if ($user->hasRole('PROKTOR') || $user->hasRole('KAMAD') || $user->hasRole('OPERATOR')) return false;

        return ($user->hasRole('ADMIN_MADRASAH') && $user->hasPermission('exam.grade'))
            || ($user->hasRole('GURU') && $user->hasPermission('exam.grade') && TeacherAccess::canViewExam($user, $attempt->exam));
    }

    /**
     * Reopen applies to a FINALIZED SCORE, not to the student's exam attempt.
     * Phase 7 Decision #17 restricts this action to ADMIN_MADRASAH
     * within the authorized tenant scope.
     */
    public function reopen(User $user, ExamAttempt $attempt): bool
    {
        if ($user->tenant_id !== $attempt->tenant_id) {
            return false;
        }

        return $user->hasRole('ADMIN_MADRASAH') && $user->hasPermission('exam.reopen');
    }
}
