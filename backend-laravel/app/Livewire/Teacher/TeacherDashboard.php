<?php

namespace App\Livewire\Teacher;

use App\Services\TeacherPortal\TeacherPortalDataService;

class TeacherDashboard extends TeacherPortalComponent
{
    public function render()
    {
        $data = app(TeacherPortalDataService::class)->dashboard(auth()->user());

        return view('livewire.teacher.portal-dashboard', $data)
            ->layout('layouts.teacher');
    }
}
