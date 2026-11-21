<?php

namespace App\\Livewire\\Kamad;

use App\\Contexts\\TenantContext;
use App\\Models\\Exam;
use App\\Models\\ExamAttempt;
use Illuminate\\Support\\Facades\\Gate;
use Livewire\\Component;

class KamadDashboard extends Component
{
    public function boot(): void
    {
        $this->assertCanView();
    }

    public function mount(): void
    {
        $this->assertCanView();
    }

    private function assertCanView(): void
    {
        $user = auth()->user();
        $tenantContextId = app(TenantContext::class)->getTenantId();

        abort_unless(
            $user
            && $user->tenant_id
            && $tenantContextId
            && (string) $tenantContextId === (string) $user->tenant_id,
            403,
            'Konteks madrasah tidak valid.'
        );

        $roleNames = $user->roles()->pluck('name')->all();
        abort_unless(
            count($roleNames) === 1 && $roleNames[0] === 'KAMAD',
            403,
            'Halaman ini khusus Kepala Madrasah.'
        );

        abort_unless(
            $user->hasPermission('exam.monitor') && $user->hasPermission('report.view'),
            403,
            'Akses baca laporan dan monitoring tidak tersedia.'
        );

        Gate::authorize('viewAny', Exam::class);
    }

    public function render()
    {
        $this->assertCanView();
        $tenantId = auth()->user()->tenant_id;
        $now = now();

        $totalExams = Exam::query()->where('tenant_id', $tenantId)->count();
        $completedExams = Exam::query()->where('tenant_id', $tenantId)->where('status', 'CLOSED')->count();
        $activeExams = Exam::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'PUBLISHED')
            ->whereNotNull('locked_at')
            ->whereNotNull('start_at')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>', $now)
            ->count();

        $totalAttempts = ExamAttempt::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'SUBMITTED')
            ->count();

        $exams = Exam::query()
            ->where('tenant_id', $tenantId)
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
