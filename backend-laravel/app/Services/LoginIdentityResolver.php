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
        if ($identifier === '' || strlen($identifier) > 100) {
            return null;
        }

        $installations = LocalInstallationIdentity::withoutGlobalScopes()->limit(2)->get();
        if ($installations->count() !== 1) {
            return null;
        }

        $tenantId = (string) $installations->first()->tenant_id;
        // SYSTEM_GLOBAL is reserved for shared read-only references, not a second school.
        $localTenantIds = Tenant::withoutGlobalScopes()
            ->where('id', '<>', Tenant::SYSTEM_TENANT_ID)
            ->whereNull('deleted_at')
            ->limit(2)
            ->pluck('id')
            ->map(fn ($id) => (string) $id);

        if ($localTenantIds->count() !== 1 || $localTenantIds->first() !== $tenantId) {
            return null;
        }

        $normalized = strtolower($identifier);
        $adminUsernameIds = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->whereRaw('LOWER(username) = ?', [$normalized])
            ->pluck('id');

        $teacherUserIds = collect();
        if (preg_match('/^\d{18}$/D', $identifier) === 1) {
            // ASN teachers authenticate only with their 18-digit NIP.
            $teacherUserIds = TeacherProfile::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->whereIn('employment_status', ['PNS', 'PPPK'])
                ->where('nip', $identifier)
                ->pluck('user_id');
        } elseif (preg_match('/^\d{16}$/D', $identifier) === 1) {
            // Honorer teachers authenticate only with their 16-digit NIK.
            $teacherUserIds = TeacherProfile::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->where('employment_status', 'HONORER')
                ->where('nik', $identifier)
                ->pluck('user_id');
        }

        $studentUserIds = collect();
        if (preg_match('/^\d{10}$/D', $identifier) === 1) {
            $studentUserIds = StudentProfile::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->where('nisn', $identifier)
                ->pluck('user_id');
        }

        // Keep ambiguity fail-closed, including collisions between Admin usernames
        // and another role's official login identifier.
        $candidateIds = collect($adminUsernameIds)
            ->merge($teacherUserIds)
            ->merge($studentUserIds)
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();

        if ($candidateIds->count() !== 1) {
            return null;
        }

        $user = User::withoutGlobalScopes()
            ->with('roles')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->whereKey($candidateIds->first())
            ->first();

        if (!$user) {
            return null;
        }

        $roles = $user->roles->pluck('name')->all();
        $allowedRoles = ['ADMIN_MADRASAH', 'GURU', 'SISWA'];
        if (count($roles) !== 1 || !in_array($roles[0], $allowedRoles, true)) {
            return null;
        }

        $matchesAdminUsername = $adminUsernameIds->contains(
            fn ($id) => (string) $id === (string) $user->id
        );
        $matchesTeacherIdentity = $teacherUserIds->contains(
            fn ($id) => (string) $id === (string) $user->id
        );
        $matchesStudentNisn = $studentUserIds->contains(
            fn ($id) => (string) $id === (string) $user->id
        );

        if ($roles[0] === 'ADMIN_MADRASAH') {
            return $matchesAdminUsername ? $user : null;
        }
        if ($roles[0] === 'GURU') {
            // Username and NUPTK are deliberately not authentication fallbacks.
            return $matchesTeacherIdentity ? $user : null;
        }
        if ($roles[0] === 'SISWA') {
            return $matchesStudentNisn ? $user : null;
        }

        return null;
    }
}
