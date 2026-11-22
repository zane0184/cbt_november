<?php

namespace App\Services;

use App\Models\LocalInstallationIdentity;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\TeacherProfile;
use App\Models\User;

class LoginIdentityResolver
{
    public function resolve(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '' || strlen($identifier) > 100) return null;
        $installations = LocalInstallationIdentity::query()->limit(2)->get();
        if ($installations->count() !== 1 || Tenant::withoutGlobalScopes()->count() !== 1) return null;
        $tenantId = (string) $installations->first()->tenant_id;
        $normalized = strtolower($identifier);

        $usernameUsers = User::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('is_active', true)
            ->whereRaw('LOWER(username) = ?', [$normalized])->get();
        $teacherUserIds = collect();
        if (preg_match('/^\\d{18}$/', $normalized)) {
            $teacherUserIds = TeacherProfile::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->where('employment_type', 'ASN')
                ->whereRaw('LOWER(nip) = ?', [$normalized])
                ->pluck('user_id');
        } elseif (preg_match('/^\\d{16}$/', $normalized)) {
            $teacherUserIds = TeacherProfile::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->where('employment_type', 'HONORER')
                ->whereRaw('LOWER(nik) = ?', [$normalized])
                ->pluck('user_id');
        }

        $studentUserIds = collect();
        if (preg_match('/^\\d{10}$/', $normalized)) {
            $studentUserIds = StudentProfile::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->whereRaw('LOWER(nisn) = ?', [$normalized])
                ->pluck('user_id');
        }

        $candidateIds = collect($usernameUsers->pluck('id'))
            ->merge($teacherUserIds)->merge($studentUserIds)->map(fn ($id) => (string) $id)->unique()->values();
        if ($candidateIds->count() !== 1) return null;

        $user = User::withoutGlobalScopes()->with('roles')->where('tenant_id', $tenantId)->whereNull('deleted_at')->where('is_active', true)->whereKey($candidateIds->first())->first();
        if (! $user) return null;
        $roles = $user->roles->pluck('name')->all();
        $allowedRoles = ['ADMIN_MADRASAH', 'GURU', 'SISWA'];
        if (count($roles) !== 1 || !in_array($roles[0], $allowedRoles, true)) return null;

        $matchesUsername = $usernameUsers->contains(fn ($candidate) => (string) $candidate->id === (string) $user->id);
        $matchesTeacher = $teacherUserIds->contains(fn ($id) => (string) $id === (string) $user->id);
        $matchesStudent = $studentUserIds->contains(fn ($id) => (string) $id === (string) $user->id);

        return match ($roles[0]) {
            'SISWA' => $matchesStudent ? $user : null,
            'GURU' => $matchesTeacher ? $user : null,
            'ADMIN_MADRASAH' => $matchesUsername ? $user : null,
            default => null,
        };
    }
}
