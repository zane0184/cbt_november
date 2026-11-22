<?php

namespace App\Livewire\Teacher;

use App\Services\TeacherPortal\TeacherPortalDataService;

class TeacherAssignmentsPage extends TeacherPortalComponent
{
    public function render()
    {
        $data = app(TeacherPortalDataService::class)->learning(auth()->user());

        return view('livewire.teacher.portal-assignments', $data)
            ->layout('layouts.teacher');
    }
}
