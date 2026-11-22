<?php

namespace App\Policies;

use App\Models\Assessment;
use App\Models\User;
use App\Support\Security\TeacherAccess;

class AssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        return $user->hasExactRole('GURU') || $user->hasExactRole('SISWA');
    }

    public function view(User $user, Assessment $assessment): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $assessment->tenant_id
            || $user->hasExactRole('ADMIN_MADRASAH')
            || $user->hasRole('OPERATOR')) return false;
        if ($user->hasExactRole('GURU')) {
            return TeacherAccess::canManageAssessment($user, $assessment);
        }

        if ($user->hasExactRole('SISWA')) {
            return $assessment->exams()
                ->whereHas('participants', fn ($query) => $query->where('user_id', $user->id))
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        return $user->hasExactRole('GURU')
            && $user->hasPermission('exam.manage');
    }

    public function update(User $user, Assessment $assessment): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $assessment->tenant_id) return false;
        return $user->hasExactRole('GURU')
            && $user->hasPermission('exam.manage')
            && (string) $assessment->created_by === (string) $user->id
            && TeacherAccess::canManageAssessment($user, $assessment);
    }

    public function delete(User $user, Assessment $assessment): bool
    {
        if ($user->hasExactRole('ADMIN_MADRASAH')) return false;
        if ($user->tenant_id !== $assessment->tenant_id) return false;
        if ($assessment->exams()->count() > 0) return false;

        return $user->hasExactRole('GURU')
            && $user->hasPermission('exam.manage')
            && (string) $assessment->created_by === (string) $user->id
            && TeacherAccess::canManageAssessment($user, $assessment);
    }
}
