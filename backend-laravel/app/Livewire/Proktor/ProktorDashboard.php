<?php

namespace App\Livewire\Proktor;

use App\Models\Exam;
use App\Models\ExamAttempt;
use Livewire\Component;

class ProktorDashboard extends Component
{
    public function mount()
    {
        $user = auth()->user();
        if (!$user || !$user->hasRole('PROKTOR')) {
            abort(403, 'Akses ditolak. Halaman ini hanya untuk akun Proktor yang ditugaskan.');
        }
    }

    public function render()
    {
        // Fail closed until temporary exam-proctor assignment records and policies exist.
        // Never expose a tenant-wide exam or attempt list to a Proktor account.
        return view('livewire.proktor.proktor-dashboard', [
            'activeExams' => collect(),
            'allExams' => collect(),
            'inProgressAttempts' => 0,
            'submittedAttempts' => 0,
        ])->layout('layouts.app');}
