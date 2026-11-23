<?php

namespace App\Services;

use App\Models\LocalInstallationIdentity;
use App\Models\StudentProfile;
use App\Models\Tenant;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Support\Collection;

class LoginIdentityResolver
{
    public function resolve(string $identifier): ?User
    {
        $identifier = trim($identifier);
        if ($identifier === '' || strlen($identifier) > 100) return null;
        $installations = LocalInstallationIdentity::withoutGlobalScopes()->limit(2)->get();
        if ($installations->count() !== 1) return null;

        $tenantId = (string) $installations->first()->tenant_id;
        // The System Global Tenant is intentionally present in seeded
        // installations for shared, read-only curriculum references. It is
        // not a second school tenant and must not make local login ambiguous.
        $localTenantExists = Tenant::withoutGlobalScopes()
            ->whereKey($tenantId)
            ->where('id', '<>', Tenant::SYSTEM_TENANT_ID)
            ->whereNull('deleted_at')
            ->exists();
        if (!$localTenantExists) return null;
        $normalized = strtolower($identifier);

        $usernameUsers = User::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereRaw('LOWER(username) = ?', [$normalized])->get();
        $teacherUserIds = TeacherProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->where(function ($query) use ($normalized): void {
                $query->whereRaw('LOWER(nip) = ?', [$normalized])->orWhereRaw('LOWER(nik) = ?', [$normalized]);
            })->pluck('user_id');
        $studentUserIds = StudentProfile::withoutGlobalScopes()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->where('nisn', $identifier)->pluck('user_id');

        $candidateIds = collect($usernameUsers->pluck('id'))
            ->merge($teacherUserIds)->merge($studentUserIds)->map(fn ($id) => (string) $id)->unique()->values();
        if ($candidateIds->count() !== 1) return null;

        $user = User::withoutGlobalScopes()->with('roles')->where('tenant_id', $tenantId)->whereNull('deleted_at')->whereKey($candidateIds->first())->first();
        if (! $user) return null;
        $roles = $user->roles->pluck('name')->all();
        $allowedRoles = ['ADMIN_MADRASAH', 'GURU', 'SISWA'];
        if (count($roles) !== 1 || !in_array($roles[0], $allowedRoles, true)) return null;

        $matchesUsername = $usernameUsers->contains(fn ($candidate) => (string) $candidate->id === (string) $user->id);
        $matchesTeacher = $teacherUserIds->contains(fn ($id) => (string) $id === (string) $user->id);
        $matchesStudent = $studentUserIds->contains(fn ($id) => (string) $id === (string) $user->id);

        if (in_array('SISWA', $roles, true)) return $matchesStudent ? $user : null;
        if (in_array('GURU', $roles, true)) return ($matchesTeacher || $matchesUsername) ? $user : null;
        if ($roles[0] === 'ADMIN_MADRASAH') return $matchesUsername ? $user : null;
        return null;
    }
}
