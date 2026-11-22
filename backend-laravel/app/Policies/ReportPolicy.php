<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Exam;
use App\Support\Security\TeacherAccess;

class ReportPolicy
{
    /**
     * Exam answer-level and score reports are Guru capabilities only.
     * Admin Madrasah and legacy KAMAD have no CBT result portal.
     */
    public function viewExamResults(User $user, Exam $exam): bool
    {
        return $exam
            && $user->tenant_id === $exam->tenant_id
            && $user->hasExactRole('GURU')
            && $user->hasPermission('report.view')
            && TeacherAccess::canViewExam($user, $exam);
    }

    /**
     * Students may see only their own results through the published current
     * score DTO. Guru access is further constrained by the report query.
     */
    public function viewStudentResults(User $user, User $student): bool
    {
        if ($user->tenant_id !== $student->tenant_id) {
            return false;
        }

        if ($user->hasExactRole('SISWA')) {
            return (string) $user->id === (string) $student->id;
        }

        return $user->hasExactRole('GURU')
            && $user->hasPermission('report.view')
            && TeacherAccess::profileIds($user) !== [];
    }

    /**
     * Tenant-wide subject aggregates are not safe for a rombel-scoped Guru
     * assignment. Keep this surface closed until the query is assignment-scoped.
     */
    public function viewSubjectResults(User $user, \App\Models\Subject $subject): bool
    {
        return false;
    }
}
