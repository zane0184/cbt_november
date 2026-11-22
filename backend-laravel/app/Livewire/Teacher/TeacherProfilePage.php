<?php

namespace App\Livewire\Teacher;

use App\Models\TeacherProfile;
use App\Models\Tenant;
use App\Services\AuditService;
use App\Support\Security\TeacherPortalAccess;
use Illuminate\Support\Facades\DB;

class TeacherProfilePage extends TeacherPortalComponent
{
    public string $phone = '';

    public function mount(): void
    {
        $profile = app(TeacherPortalAccess::class)->profile(auth()->user());
        $this->phone = (string) ($profile->phone ?? '');
    }

    public function savePhone(): void
    {
        $user = auth()->user();
        $profile = app(TeacherPortalAccess::class)->profile($user);

        $validated = $this->validate([
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $phone = trim((string) ($validated['phone'] ?? ''));
        $phone = $phone === '' ? null : $phone;

        DB::transaction(function () use ($user, $profile, $phone): void {
            $lockedProfile = TeacherProfile::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('user_id', $user->id)
                ->whereKey($profile->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldPhone = $lockedProfile->phone;
            if ($oldPhone === $phone) {
                return;
            }

            $lockedProfile->forceFill(['phone' => $phone])->save();

            app(AuditService::class)->log(
                'TEACHER_PROFILE_PHONE_UPDATED',
                'TeacherProfile',
                $lockedProfile->id,
                ['phone' => $oldPhone],
                ['phone' => $phone]
            );
        }, 3);

        $this->phone = $phone ?? '';
        session()->flash('message', 'Nomor kontak berhasil diperbarui.');
    }

    public function render()
    {
        $user = auth()->user();
        $profile = app(TeacherPortalAccess::class)->profile($user);
        $tenant = Tenant::query()->whereKey($user->tenant_id)->first();

        return view('livewire.teacher.portal-profile', [
            'user' => $user,
            'profile' => $profile,
            'tenant' => $tenant,
        ])->layout('layouts.teacher');
    }
}
