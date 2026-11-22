<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Exam;
use App\Support\Security\TeacherAccess;

class ReportPolicy
{
    public function viewExamResults(User $user, Exam $exam): bool
    {
        if (!$exam || $user->tenant_id !== $exam->tenant_id) {
            return false;
        }

        if ($user->hasRole('GURU')) {
            return $user->hasPermission('report.view') && TeacherAccess::canViewExam($user, $exam);
        }

        // Admin reporting is a separate administrative reporting module.
        // Exam scores and student assessment results remain under Guru authority.
        return $user->hasRole('KAMAD') && $user->hasPermission('report.view');
    }

    public function viewStudentResults(User $user, User $student): bool
    {
        if ($user->tenant_id !== $student->tenant_id) {
            return false;
        }

        if ($user->id === $student->id) {
            return true;
        }

        if ($user->hasRole('GURU')) {
            return $user->hasPermission('report.view') && TeacherAccess::profileIds($user) !== [];
        }

        return $user->hasRole('KAMAD') && $user->hasPermission('report.view');
    }

    public function viewSubjectResults(User $user, \App\Models\Subject $subject): bool
    {
        if ($user->tenant_id !== $subject->tenant_id) {
            return false;
        }

        return $user->hasRole('KAMAD') && $user->hasPermission('report.view');
    }
}
