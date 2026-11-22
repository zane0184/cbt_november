<?php

namespace App\Livewire\Teacher;

use App\Support\Security\TeacherPortalAccess;
use Livewire\Component;

abstract class TeacherPortalComponent extends Component
{
    // Livewire invokes boot on each request, so authorization also covers hydration and actions.
    public function boot(): void
    {
        app(TeacherPortalAccess::class)->profile(auth()->user());
    }
}
