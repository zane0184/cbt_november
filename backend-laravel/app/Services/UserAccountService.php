<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserAccountService
{
    public function activate(string $userId): void
    {
        $this->assertActorCanManageUsers();
        DB::transaction(function () use ($userId): void {
            $user = $this->lockManageableUser($userId);
            if ((bool) $user->is_active) {
                return;
            }
            $user->is_active = true;
            $user->save();
            app(AuditService::class)->log('USER_ACTIVATED', 'User', $user->id, ['is_active' => false], ['is_active' => true]);
        }, 3);
    }

    public function deactivate(string $userId): void
    {
        $this->assertActorCanManageUsers();
        DB::transaction(function () use ($userId): void {
            $user = $this->lockManageableUser($userId);
            $this->assertNoActiveStudentAttempt($user);
            if (!(bool) $user->is_active) {
                return;
            }
            $user->is_active = false;
            $user->remember_token = null;
            $user->save();
            $this->revokeCredentials($user);
            app(AuditService::class)->log('USER_DEACTIVATED', 'User', $user->id, ['is_active' => true], ['is_active' => false, 'sessions_revoked' => true]);
        }, 3);
    }

    public function resetPassword(string $userId): string
    {
        $this->assertActorCanManageUsers();
        $temporaryPassword = Str::random(8);
        DB::transaction(function () use ($userId, $temporaryPassword): void {
            $user = $this->lockManageableUser($userId);
            $this->assertNoActiveStudentAttempt($user);
            $user->password = Hash::make($temporaryPassword);
            $user->remember_token = null;
            $user->save();
            $this->revokeCredentials($user);
            app(AuditService::class)->log('USER_PASSWORD_RESET', 'User', $user->id, null, ['password_reset' => true, 'sessions_revoked' => true]);
        }, 3);
        return $temporaryPassword;
    }

    private function assertActorCanManageUsers(): void
    {
        $actor = auth()->user();
        abort_unless($actor && $actor->hasExactRole('ADMIN_MADRASAH') && $actor->hasPermission('user.manage'), 403);
    }

    private function lockManageableUser(string $userId): User
    {
        $actor = auth()->user();
        $user = User::withoutGlobalScopes()
            ->where('tenant_id', $actor->tenant_id)
            ->whereNull('deleted_at')
            ->whereKey($userId)
            ->lockForUpdate()
            ->firstOrFail();

        $roles = $user->roles()->pluck('name');
        abort_unless($roles->count() === 1 && in_array($roles->first(), ['GURU', 'SISWA'], true), 403);
        return $user;
    }

    private function assertNoActiveStudentAttempt(User $user): void
    {
        if ($user->hasExactRole('SISWA')) {
            abort_if(
                app(ActiveExamLoginGuard::class)->hasActiveAttempt($user),
                409,
                'Akun Siswa tidak dapat dinonaktifkan atau direset saat ujian aktif masih berlangsung.'
            );
        }
    }

    private function revokeCredentials(User $user): void
    {
        $user->tokens()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
