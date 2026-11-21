<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Exam;
use App\Support\Security\TeacherAccess;

class ReportPolicy
{
    /**
     * Determine if the user can view exam results.
     */
    public function viewExamResults(User $user, Exam $exam): bool
    {
        if (!$exam || $user->tenant_id !== $exam->tenant_id) return false;

        if ($user->hasRole('GURU')) {
            return $user->hasPermission('report.view') && TeacherAccess::canViewExam($user, $exam);
        }

        return $user->hasRole('KAMAD') && $user->hasPermission('report.view');
    }

    /**
     * Determine if the user can view student results.
     */
    public function viewStudentResults(User $user, User $student): bool
    {
        if ($user->tenant_id !== $student->tenant_id) return false;

        if ($user->id === $student->id) return true;

        if ($user->hasRole('GURU')) {
            // Exact subject/year/semester/rombel authorization is enforced by
            // studentResults/exportStudentPdf after required filters validate.
            return $user->hasPermission('report.view') && TeacherAccess::profileIds($user) !== [];
        }

        return ($user->hasRole('ADMIN_MADRASAH') || $user->hasRole('KAMAD')) && $user->hasPermission('report.view');
    }

    /**
     * Determine if the user can view subject results.
     */
    public function viewSubjectResults(User $user, \App\Models\Subject $subject): bool
    {
        if ($user->tenant_id !== $subject->tenant_id) return false;

        // Subject aggregates currently span all rombels. A teacher assignment is
        // rombel-specific, so do not expose tenant-wide subject aggregates until
        // the report query is constrained to the teacher's assigned rombels.
        return ($user->hasRole('ADMIN_MADRASAH') || $user->hasRole('KAMAD')) && $user->hasPermission('report.view');
    }
}
