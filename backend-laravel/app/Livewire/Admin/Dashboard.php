<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\ExamProctorAssignment;
use App\Models\Rombel;
use App\Models\Subject;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    private function authorizeDashboard(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->tenant_id && $user->hasExactRole('ADMIN_MADRASAH'),
            403,
            'Dashboard ini hanya tersedia untuk Admin Madrasah pada instalasi lokal.'
        );
    }

    public function mount(): void
    {
        $this->authorizeDashboard();
    }

    public function render()
    {
        // Re-check persisted role on every Livewire request, not only initial mount.
        $this->authorizeDashboard();
        $tenantId = (string) auth()->user()->tenant_id;

        $activeAcademicYear = AcademicYear::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        $totalUsers = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')->count();
        $totalStudents = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'SISWA'))
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', '!=', 'SISWA'))->count();
        $totalTeachers = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'GURU'))
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', '!=', 'GURU'))
            ->whereHas('teacherProfile')->count();

        // Dashboard operational totals describe the active academic year only.
        $activeRombelsQuery = Rombel::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE');
        if ($activeAcademicYear) {
            $activeRombelsQuery->where('academic_year_id', $activeAcademicYear->id);
        } else {
            $activeRombelsQuery->whereRaw('1 = 0');
        }
        $totalRombels = $activeRombelsQuery->count();

        $totalSubjects = Subject::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->count();
        $totalProctorAssignments = ExamProctorAssignment::query()->where('tenant_id', $tenantId)->activeNow()->count();

        $recentUsers = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->with('roles')->orderByDesc('created_at')->orderByDesc('id')->take(5)->get();
        $recentRombelsQuery = Rombel::query()
            ->where('tenant_id', $tenantId)
            ->when($activeAcademicYear, fn ($query) => $query->where('academic_year_id', $activeAcademicYear->id))
            ->when(!$activeAcademicYear, fn ($query) => $query->whereRaw('1 = 0'))
            ->with('academicYear')
            ->latest()
            ->take(5);
        $recentRombels = $recentRombelsQuery->get();

        return view('livewire.admin.dashboard', [
            'totalUsers' => $totalUsers,
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'totalRombels' => $totalRombels,
            'totalSubjects' => $totalSubjects,
            'totalProctorAssignments' => $totalProctorAssignments,
            'activeAcademicYear' => $activeAcademicYear,
            'recentUsers' => $recentUsers,
            'recentRombels' => $recentRombels,
        ])->layout('layouts.app');
    }
}
