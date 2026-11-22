<?php

namespace App\Livewire\Operator;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Throwable;

class OperatorDashboard extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        if (!$user || !$user->tenant_id || !$user->roles()->whereIn('name', ['ADMIN_MADRASAH', 'ADMIN', 'OPERATOR'])->exists()) {
            abort(403, 'Akses ditolak. Dashboard ini hanya tersedia untuk Admin Madrasah pada tenant aktif.');
        }
    }

    public function render()
    {
        $tenantId = (string) auth()->user()->tenant_id;
        $tenant = Tenant::query()->whereKey($tenantId)->firstOrFail();

        $activeAcademicYear = AcademicYear::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        $activeSemester = $activeAcademicYear
            ? Semester::query()->where('tenant_id', $tenantId)
                ->where('academic_year_id', $activeAcademicYear->id)
                ->where('is_active', true)
                ->first()
            : null;

        $totalUsers = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')->count();
        $totalStudents = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'SISWA'))
            ->whereHas('studentProfile')
            ->count();
        $totalTeachers = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereHas('roles', fn ($query) => $query->where('name', 'GURU'))
            ->whereHas('teacherProfile')
            ->count();
        $totalRombels = Rombel::query()->where('tenant_id', $tenantId)->where('status', 'ACTIVE')->count();
        $totalSubjects = Subject::query()->where('tenant_id', $tenantId)->where('status', 'ACTIVE')->count();
        $totalExams = Exam::query()->where('tenant_id', $tenantId)->count();

        $recentUsers = User::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->with('roles')->latest()->take(5)->get();
        $recentRombels = Rombel::query()->where('tenant_id', $tenantId)->where('status', 'ACTIVE')
            ->latest()->take(5)->get();
        $recentActivity = AuditLog::query()->where('tenant_id', $tenantId)
            ->with('user:id,name')->orderByDesc('created_at')->limit(8)->get();

        try {
            DB::select('SELECT 1');
            $databaseHealth = 'Terhubung';
        } catch (Throwable) {
            $databaseHealth = 'Perlu diperiksa';
        }

        $storageHealth = is_dir(storage_path('app')) && is_writable(storage_path('app'))
            ? 'Direktori dapat ditulis'
            : 'Perlu diperiksa';

        return view('livewire.operator.operator-dashboard', [
            'tenant' => $tenant,
            'totalUsers' => $totalUsers,
            'totalStudents' => $totalStudents,
            'totalTeachers' => $totalTeachers,
            'totalRombels' => $totalRombels,
            'totalSubjects' => $totalSubjects,
            'totalExams' => $totalExams,
            'activeAcademicYear' => $activeAcademicYear,
            'activeSemester' => $activeSemester,
            'recentUsers' => $recentUsers,
            'recentRombels' => $recentRombels,
            'recentActivity' => $recentActivity,
            'databaseHealth' => $databaseHealth,
            'storageHealth' => $storageHealth,
        ])->layout('layouts.app');
    }
}
