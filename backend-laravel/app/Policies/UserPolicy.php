<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewStudentResults(User $user, User $student): bool
    {
        return app(ReportPolicy::class)->viewStudentResults($user, $student);
    }
}
