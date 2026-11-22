<?php

namespace App\Livewire\Teacher;

use App\Support\Security\TeacherPortalAccess;
use Livewire\Component;

abstract class TeacherPortalComponent extends Component
{
    // Re-check the authenticated role, tenant, and profile on initial render and every Livewire request.
    public function boot(): void
    {
        app(TeacherPortalAccess::class)->profile(auth()->user());
    }
}
