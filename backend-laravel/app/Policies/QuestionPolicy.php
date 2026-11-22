<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;
use App\Support\Security\TeacherAccess;

class QuestionPolicy extends BasePolicy
{
    protected string $permissionPrefix = 'question';

    public function viewAny(User $user): bool
    {
        return $this->hasExactGuruPermission($user, 'question.view');
    }

    public function view(User $user, $model): bool
    {
        if (!$model instanceof Question
            || !$user->tenant_id
            || $user->tenant_id !== $model->tenant_id
            || !$this->hasExactGuruPermission($user, 'question.view')) {
            return false;
        }

        return match ($model->owner_type) {
            'PERSONAL' => $model->owner_user_id === $user->id
                && (!$model->subject_id || TeacherAccess::canManageSubject($user, $model->subject_id)),
            'SUBJECT' => $this->canAccessSubject($user, $model),
            'MADRASAH' => $this->canAccessSubject($user, $model),
            'SHARED' => $model->publications()
                ->where('tenant_id', $user->tenant_id)
                ->where('status', 'APPROVED')
                ->where('share_scope', 'MADRASAH')
                ->exists(),
            default => false,
        };
    }

    public function create(User $user): bool
    {
        return $this->hasExactGuruPermission($user, 'question.manage');
    }

    public function update(User $user, $model): bool
    {
        if (!$model instanceof Question
            || $model->status === 'ARCHIVED'
            || $user->tenant_id === null
            || $user->tenant_id !== $model->tenant_id
            || !$this->hasExactGuruPermission($user, 'question.manage')) {
            return false;
        }

        return match ($model->owner_type) {
            'PERSONAL' => $model->owner_user_id === $user->id
                && (!$model->subject_id || TeacherAccess::canManageSubject($user, $model->subject_id)),
            'SUBJECT' => $this->canAccessSubject($user, $model),
            // Madrasah-owned and shared content is not editable by an individual Guru.
            'MADRASAH', 'SHARED' => false,
            default => false,
        };
    }

    /**
     * Physical deletion is intentionally prohibited. Use archive/soft-delete
     * so snapshots, attempts, answers and score history remain referentially safe.
     */
    public function delete(User $user, $model): bool
    {
        return false;
    }

    public function publish(User $user, Question $question): bool
    {
        return $this->update($user, $question);
    }

    private function canAccessSubject(User $user, Question $question): bool
    {
        return $question->subject_id !== null
            && TeacherAccess::canManageSubject($user, $question->subject_id);
    }

    /**
     * Read current role and permission rows from the database rather than
     * trusting potentially stale in-memory role relations from an old session.
     */
    private function hasExactGuruPermission(User $user, string $permission): bool
    {
        if (!$user->tenant_id) {
            return false;
        }

        $roles = $user->roles()->with('permissions')->get();
        if ($roles->count() !== 1 || $roles->first()->name !== 'GURU') {
            return false;
        }

        return $roles->first()->permissions->contains(
            fn ($rolePermission): bool => $rolePermission->name === $permission
        );
    }
}
