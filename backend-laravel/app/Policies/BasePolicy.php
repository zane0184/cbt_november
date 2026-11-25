<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class BasePolicy
{
    protected string $permissionPrefix;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission($this->permissionPrefix . '.view');
    }

    public function view(User $user, $model): bool
    {
        return $user->tenant_id === $model->tenant_id && $user->hasPermission($this->permissionPrefix . '.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission($this->permissionPrefix . '.manage');
    }

    public function update(User $user, $model): bool
    {
        return $user->tenant_id === $model->tenant_id && $user->hasPermission($this->permissionPrefix . '.manage');
    }

    public function delete(User $user, $model): bool
    {
        return $user->tenant_id === $model->tenant_id && $user->hasPermission($this->permissionPrefix . '.manage');
    }
}
