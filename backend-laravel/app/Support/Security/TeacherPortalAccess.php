<?php

namespace App\Support\Security;

use App\Contexts\TenantContext;
use App\Models\TeacherProfile;
use App\Models\User;

class TeacherPortalAccess
{
    /**
     * Resolve the one permitted Guru profile from the authenticated server-side identity.
     * No tenant, role, or profile identifier is accepted from the browser.
     */
    public function profile(?User $user): TeacherProfile
    {
        abort_unless($user && $user->tenant_id, 403, 'Akses Portal Guru ditolak.');

        $tenantId = (string) $user->tenant_id;
        $context = app(TenantContext::class);
        $contextTenantId = $context->getTenantId();

        abort_if(
            $contextTenantId && (string) $contextTenantId !== $tenantId,
            403,
            'Konteks tenant tidak sesuai dengan akun yang terautentikasi.'
        );

        // Livewire subsequent requests may not rebuild every custom middleware context.
        // Re-establish it only from the authenticated user's immutable tenant relation.
        $context->setTenantId($tenantId);
        TenantContext::getInstance()?->setTenantId($tenantId);

        $persistedUserExists = User::query()
            ->whereKey($user->getKey())
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->exists();

        abort_unless($persistedUserExists, 403, 'Akun Guru tidak lagi aktif pada tenant ini.');

        $roles = $user->roles()->pluck('name')->all();
        abort_unless(
            count($roles) === 1 && $roles[0] === 'GURU',
            403,
            'Portal ini hanya dapat diakses oleh akun dengan satu role GURU.'
        );

        $profiles = TeacherProfile::query()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->getKey())
            ->limit(2)
            ->get();

        abort_unless(
            $profiles->count() === 1,
            403,
            'Profil Guru tenant ini tidak ditemukan atau tidak unik.'
        );

        return $profiles->first();
    }
}
