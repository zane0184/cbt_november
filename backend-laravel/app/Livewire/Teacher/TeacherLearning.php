<?php

namespace App\Livewire\Teacher;

use App\Services\TeacherPortal\TeacherPortalDataService;

class TeacherLearning extends TeacherPortalComponent
{
    public function render()
    {
        $data = app(TeacherPortalDataService::class)->learning(auth()->user());

        return view('livewire.teacher.portal-learning', $data)
            ->layout('layouts.teacher');
    }
}
