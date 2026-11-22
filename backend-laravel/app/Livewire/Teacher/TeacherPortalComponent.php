<?php

namespace App\Livewire\Teacher;

use App\Livewire\Teacher\TeacherPortalAccess as LegacyTeacherPortalAccess;
use App\Support\Security\TeacherPortalAccess;
use Livewire\Component;

abstract class TeacherPortalComponent extends Component
{
    public function boot(): void
    {
        app(TeacherPortalAccess::class)->profile(auth()->user());
    }
}
