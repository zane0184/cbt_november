<?php

namespace App\Livewire\Kamad;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\User;
use Livewire\Component;

class KamadDashboard extends Component
{
    public function mount()
    {
        $user = auth()->user();
        if (!$user || !$user->roles()->whereIn('name', ['ADMIN_MADRASAH', 'ADMIN', 'KAMAD'])->exists()) {
            abort(403, 'Akses ditolak. Halaman ini khusus untuk Kepala Madrasah (Executive View).');
        }
    }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;

        $totalExams = Exam::where('tenant_id', $tenantId)->count();
        $completedExams = Exam::where('tenant_id', $tenantId)->where('status', 'CLOSED')->count();
        $activeExams = Exam::where('tenant_id', $tenantId)->where('status', 'PUBLISHED')->count();

        $totalAttempts = ExamAttempt::where('tenant_id', $tenantId)->where('status', 'SUBMITTED')->count();

        $exams = Exam::where('tenant_id', $tenantId)
            ->with(['subject'])
            ->latest()
            ->take(10)
            ->get();

        return view('livewire.kamad.kamad-dashboard', [
            'totalExams' => $totalExams,
            'completedExams' => $completedExams,
            'activeExams' => $activeExams,
            'totalAttempts' => $totalAttempts,
            'exams' => $exams,
        ])->layout('layouts.app');
    }
}
