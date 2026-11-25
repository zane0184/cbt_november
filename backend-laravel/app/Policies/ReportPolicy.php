<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Exam;
use App\Support\Security\TeacherAccess;

class ReportPolicy
{
    public function viewExamResults(User $user, Exam $exam): bool
    {
        if (!$exam || $user->tenant_id !== $exam->tenant_id) return false;

        return $user->hasExactRole('GURU')
            && $user->hasPermission('report.view')
            && TeacherAccess::canViewExam($user, $exam);
    }

    public function viewStudentResults(User $user, User $student): bool
    {
        if ($user->tenant_id !== $student->tenant_id) return false;
        if ($user->id === $student->id) return $user->hasExactRole('SISWA');

        return $user->hasExactRole('GURU')
            && $user->hasPermission('report.view')
            && TeacherAccess::profileIds($user) !== [];
    }

    public function viewSubjectResults(User $user, \App\Models\Subject $subject): bool
    {
        if ($user->tenant_id !== $subject->tenant_id) return false;

        return $user->hasExactRole('GURU')
            && $user->hasPermission('report.view')
            && TeacherAccess::profileIds($user) !== [];
    }
}
