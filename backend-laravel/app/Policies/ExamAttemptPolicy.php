<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;
use App\Support\Security\TeacherAccess;

class ExamAttemptPolicy
{
    public function view(User $user, ExamAttempt $attempt): bool
    {
        return $user->tenant_id === $attempt->tenant_id
            && $user->hasExactRole('GURU')
            && $user->hasPermission('report.view')
            && TeacherAccess::canViewExam($user, $attempt->exam);
    }

    public function viewScore(User $user, ExamAttempt $attempt): bool
    {
        if ($user->tenant_id !== $attempt->tenant_id) {
            return false;
        }

        if ($user->hasExactRole('GURU')) {
            return $user->hasPermission('report.view')
                && TeacherAccess::canViewExam($user, $attempt->exam);
        }

        if (!$user->hasExactRole('SISWA')) {
            return false;
        }

        // Student results are visible only through the authoritative current-score
        // pointer. Historical versions are never selected by MAX(version).
        $participant = $attempt->participant;
        $exam = $attempt->exam;
        $currentScore = $attempt->resolvedCurrentScore();

        return $participant
            && $participant->tenant_id === $attempt->tenant_id
            && (string) $participant->user_id === (string) $user->id
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
        if ($user->tenant_id !== $attempt->tenant_id || !$user->hasExactRole('GURU')) {
            return false;
        }

        $exam = $attempt->exam;
        $assessment = $exam?->assessment;
        if (!$exam || !$assessment) {
            return false;
        }

        return $user->hasPermission('exam.grade')
            && TeacherAccess::canManageAssessment($user, $assessment)
            && TeacherAccess::canViewExam($user, $exam);
    }

    public function grade(User $user, ExamAttempt $attempt): bool
    {
        // Essay/manual grading belongs to the assigned Guru, never to Admin
        // or a temporary Proktor assignment.
        return $this->score($user, $attempt);
    }

    public function finalize(User $user, ExamAttempt $attempt): bool
    {
        return $this->score($user, $attempt);
    }

    public function reopen(User $user, ExamAttempt $attempt): bool
    {
        return $this->score($user, $attempt);
    }
}
