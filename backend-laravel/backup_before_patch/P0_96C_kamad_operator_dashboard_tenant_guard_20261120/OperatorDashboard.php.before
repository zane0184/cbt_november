<?php

namespace App\Livewire\Operator;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\Rombel;
use App\Models\Subject;
use App\Models\User;
use Livewire\Component;

class OperatorDashboard extends Component
{
    public function mount()
    {
        $user = auth()->user();
        if (!$user || !$user->roles()->whereIn('name', ['ADMIN_MADRASAH', 'ADMIN', 'OPERATOR'])->exists()) {
            abort(403, 'Akses ditolak. Layanan ini khusus untuk Operator Madrasah.');
        }
    }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;

        $totalUsers = User::where('tenant_id', $tenantId)->count();
        $totalStudents = User::where('tenant_id', $tenantId)->whereHas('roles', fn($q) => $q->where('name', 'SISWA'))->count();
        $totalTeachers = User::where('tenant_id', $tenantId)->whereHas('roles', fn($q) => $q->where('name', 'GURU'))->count();
        $totalRombels = Rombel::where('tenant_id', $tenantId)->count();
        $totalSubjects = Subject::where('tenant_id', $tenantId)->count();
        $totalExams = Exam::where('tenant_id', $tenantId)->count();
        $activeAcademicYear = AcademicYear::where('tenant_id', $tenantId)->where('is_active', true)->first();

        $recentUsers = User::where('tenant_id', $tenantId)->with('roles')->latest()->take(5)->get();
        $recentRombels = Rombel::where('tenant_id', $tenantId)->latest()->take(5)->get();

        return view('livewire.operator.operator-dashboard', [
            'totalUsers' => $totalUsers,
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'totalRombels' => $totalRombels,
            'totalSubjects' => $totalSubjects,
            'totalExams' => $totalExams,
            'activeAcademicYear' => $activeAcademicYear,
            'recentUsers' => $recentUsers,
            'recentRombels' => $recentRombels,
        ])->layout('layouts.app');
    }
}
