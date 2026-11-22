<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Throwable;

class AdminDashboard extends Component
{
    public function boot(): void
    {
        $this->authorizeDashboard();
    }

    public function mount(): void
    {
        $this->authorizeDashboard();
    }

    private function authorizeDashboard(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
            && $user->tenant_id
            && $user->roles()->count() === 1
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists(),
            403,
            'Dashboard hanya tersedia bagi Admin Madrasah pada instalasi yang sah.'
        );
    }

    public function render()
    {
        $tenantId = (string) auth()->user()->tenant_id;
        $tenant = Tenant::query()->whereKey($tenantId)->firstOrFail();

        $activeYears = AcademicYear::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(2)
            ->get();

        $activeYear = $activeYears->count() === 1 ? $activeYears->first() : null;
        $activeYearLabel = match ($activeYears->count()) {
            0 => 'Belum Ditetapkan',
            1 => (string) $activeYear->name,
            default => 'Perlu Ditinjau — lebih dari satu tahun ajaran aktif',
        };

        $activeSemesters = Semester::query()
            ->with('academicYear')
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->limit(2)
            ->get();

        $activeSemester = null;
        if (
            $activeSemesters->count() === 1
            && $activeYear
            && (string) $activeSemesters->first()->academic_year_id === (string) $activeYear->id
        ) {
            $activeSemester = $activeSemesters->first();
        }

        $activeSemesterLabel = match (true) {
            $activeSemesters->isEmpty() => 'Belum Ditetapkan',
            $activeSemesters->count() > 1 => 'Perlu Ditinjau — lebih dari satu semester aktif',
            !$activeYear => 'Perlu Ditinjau — tahun ajaran aktif belum valid',
            !$activeSemester => 'Perlu Ditinjau — semester tidak sesuai tahun ajaran aktif',
            $activeSemester->term === 'GANJIL' => 'Ganjil',
            $activeSemester->term === 'GENAP' => 'Genap',
            default => 'Perlu Ditinjau',
        };

        $totalStudents = StudentProfile::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('user', function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')
                    ->whereHas('roles', fn ($roles) => $roles->where('name', 'SISWA'));
            })
            ->count();

        $totalTeachers = TeacherProfile::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('user', function ($query) use ($tenantId): void {
                $query->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')
                    ->whereHas('roles', fn ($roles) => $roles->where('name', 'GURU'));
            })
            ->count();

        $totalRombels = Rombel::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->count();

        $totalSubjects = Subject::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->count();

        $studentDistribution = collect();
        if ($activeYear) {
            $studentDistribution = Rombel::query()
                ->with('gradeLevel')
                ->withCount('students')
                ->where('tenant_id', $tenantId)
                ->where('academic_year_id', $activeYear->id)
                ->where('status', 'ACTIVE')
                ->get()
                ->groupBy(fn (Rombel $rombel) => (int) ($rombel->gradeLevel?->level ?? 0))
                ->map(fn ($rombels, $level) => [
                    'label' => $level > 0 ? 'Kelas ' . $level : 'Kelas belum dipetakan',
                    'students' => (int) $rombels->sum('students_count'),
                ])
                ->sortKeys()
                ->values();
        }

        $maxDistributionCount = max(1, (int) $studentDistribution->max('students'));

        $recentActivities = AuditLog::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('entity_type', [
                'Tenant',
                'User',
                'TeacherProfile',
                'StudentProfile',
                'AcademicYear',
                'Semester',
                'Rombel',
                'Subject',
            ])
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $databaseHealthy = false;
        try {
            DB::selectOne('SELECT 1');
            $databaseHealthy = true;
        } catch (Throwable) {
            $databaseHealthy = false;
        }

        $storageWritable = is_dir(storage_path('app')) && is_writable(storage_path('app'));

        return view('livewire.admin.admin-dashboard', compact(
            'tenant',
            'activeYearLabel',
            'activeSemesterLabel',
            'totalStudents',
            'totalTeachers',
            'totalRombels',
            'totalSubjects',
            'studentDistribution',
            'maxDistributionCount',
            'recentActivities',
            'databaseHealthy',
            'storageWritable'
        ))->layout('layouts.app');
    }
}
