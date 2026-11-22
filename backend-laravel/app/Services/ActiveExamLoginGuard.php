<?php

namespace App\Services;

use App\Models\ExamAttempt;
use App\Models\User;

class ActiveExamLoginGuard
{
    public function hasActiveAttempt(User $user): bool
    {
        if (!$user->hasExactRole('SISWA') || !$user->tenant_id) {
            return false;
        }

        return ExamAttempt::withoutGlobalScope('tenant')
            ->where('tenant_id', $user->tenant_id)
            ->where('status', 'IN_PROGRESS')
            ->whereHas('participant', function ($query) use ($user) {
                $query->withoutGlobalScope('tenant')
                    ->where('tenant_id', $user->tenant_id)
                    ->where('user_id', $user->id);
            })
            ->exists();
    }
}
