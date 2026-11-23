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
    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id && $user->hasExactRole('ADMIN_MADRASAH'),
            403,
            'Dashboard ini hanya tersedia untuk Admin Madrasah pada instalasi lokal.'
        );
    }

    public function render()
    {
        $tenantId = (string) auth()->user()->tenant_id;

        $totalUsers = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')->count();
        $totalStudents = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'SISWA'))
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', '!=', 'SISWA'))->count();
        $totalTeachers = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'GURU'))
            ->whereDoesntHave('roles', fn ($query) => $query->where('name', '!=', 'GURU'))
            ->whereHas('teacherProfile')->count();
        $totalRombels = Rombel::query()->where('tenant_id', $tenantId)->count();
        $totalSubjects = Subject::query()->where('tenant_id', $tenantId)->count();
        $totalProctorAssignments = ExamProctorAssignment::query()->where('tenant_id', $tenantId)->activeNow()->count();
        $activeAcademicYear = AcademicYear::query()->where('tenant_id', $tenantId)->where('is_active', true)->first();

        $recentUsers = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->with('roles')->latest()->take(5)->get();
        $recentRombels = Rombel::query()->where('tenant_id', $tenantId)->latest()->take(5)->get();

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
