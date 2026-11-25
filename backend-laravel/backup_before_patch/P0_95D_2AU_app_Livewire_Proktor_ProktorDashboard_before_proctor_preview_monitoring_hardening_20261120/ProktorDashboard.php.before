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
        if (!$user || !$user->roles()->whereIn('name', ['ADMIN_MADRASAH', 'ADMIN', 'PROKTOR'])->exists()) {
            abort(403, 'Akses ditolak. Layanan ini khusus untuk Proktor dan Pengawas Ujian.');
        }
    }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;

        $activeExams = Exam::where('tenant_id', $tenantId)
            ->whereIn('status', ['PUBLISHED', 'ACTIVE', 'DRAFT'])
            ->with(['subject'])
            ->latest()
            ->get();

        $allExams = Exam::where('tenant_id', $tenantId)
            ->with(['subject'])
            ->latest()
            ->take(10)
            ->get();

        $inProgressAttempts = ExamAttempt::where('tenant_id', $tenantId)->where('status', 'IN_PROGRESS')->count();
        $submittedAttempts = ExamAttempt::where('tenant_id', $tenantId)->where('status', 'SUBMITTED')->count();

        return view('livewire.proktor.proktor-dashboard', [
            'activeExams' => $activeExams,
            'allExams' => $allExams,
            'inProgressAttempts' => $inProgressAttempts,
            'submittedAttempts' => $submittedAttempts,
        ])->layout('layouts.app');
    }
}
