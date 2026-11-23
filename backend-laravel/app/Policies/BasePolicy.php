<?php

namespace App\Policies;

use App\Models\User;

class BasePolicy
{
    protected string $permissionPrefix;

    private function hasAdministrativeOrTeacherRole(User $user): bool
    {
        return $user->hasExactlyOneOfRoles(['ADMIN_MADRASAH', 'GURU']);
    }

    public function viewAny(User $user): bool
    {
        return $this->hasAdministrativeOrTeacherRole($user)
            && $user->hasPermission($this->permissionPrefix . '.view');
    }

    public function view(User $user, $model): bool
    {
        return $this->hasAdministrativeOrTeacherRole($user)
            && $user->tenant_id === $model->tenant_id
            && $user->hasPermission($this->permissionPrefix . '.view');
    }

    public function create(User $user): bool
    {
        return $this->hasAdministrativeOrTeacherRole($user)
            && $user->hasPermission($this->permissionPrefix . '.manage');
    }

    public function update(User $user, $model): bool
    {
        return $this->hasAdministrativeOrTeacherRole($user)
            && $user->tenant_id === $model->tenant_id
            && $user->hasPermission($this->permissionPrefix . '.manage');
    }

    public function delete(User $user, $model): bool
    {
        return $this->hasAdministrativeOrTeacherRole($user)
            && $user->tenant_id === $model->tenant_id
            && $user->hasPermission($this->permissionPrefix . '.manage');
    }
}
