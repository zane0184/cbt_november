<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AcademicYearManagementService
{
    public function createForTenant(string $tenantId, string $name): AcademicYear
    {
        [$start, $end] = $this->parseAcademicYearName($name);

        return $this->withinTenantLock($tenantId, function () use ($tenantId, $name, $start, $end): AcademicYear {
            $duplicate = AcademicYear::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('name', $name)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'academicYearName' => 'Tahun pelajaran tersebut sudah pernah digunakan pada madrasah ini.',
                ]);
            }

            $activeYearCount = AcademicYear::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count();
            $activeSemesters = Semester::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get();

            $activeYears = AcademicYear::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get();

            $activeSemester = $activeSemesters->first();
            $activeYear = $activeYears->first();

            if ($activeYears->count() > 1
                || $activeSemesters->count() > 1
                || (($activeYear !== null) !== ($activeSemester !== null))
                || ($activeSemester && (string) $activeSemester->academic_year_id !== (string) $activeYear->id)) {
                throw new RuntimeException('Active academic period state is inconsistent; audit it before creating another year.');
            }

            // No academic year or semester is activated by default.
            // Admin explicitly chooses the active semester after reviewing the catalog.
            $year = AcademicYear::create([
                'name' => $name,
                'is_active' => false,
            ]);

            $ganjil = Semester::create([
                'academic_year_id' => $year->id,
                'term' => 'GANJIL',
                'is_active' => false,
            ]);

            $genap = Semester::create([
                'academic_year_id' => $year->id,
                'term' => 'GENAP',
                'is_active' => false,
            ]);

            app(AuditService::class)->log(
                'ACADEMIC_YEAR_CREATED',
                'AcademicYear',
                $year->id,
                null,
                [
                    'name' => $name,
                    'start_year' => $start,
                    'end_year' => $end,
                    'is_active' => false,
                    'semester_ids' => [$ganjil->id, $genap->id],
                ]
            );

            return $year->load('semesters');
        });
    }

    public function activateSemesterForTenant(string $tenantId, string $semesterId): Semester
    {
        return $this->withinTenantLock($tenantId, function () use ($tenantId, $semesterId): Semester {
            $semester = Semester::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($semesterId)
                ->lockForUpdate()
                ->firstOrFail();

            $year = AcademicYear::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($semester->academic_year_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureSemesters($tenantId, $year);

            $previousActiveYearIds = AcademicYear::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->pluck('id')
                ->all();
            $previousActiveSemesterIds = Semester::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            AcademicYear::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            Semester::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $year->update(['is_active' => true]);
            $semester->update(['is_active' => true]);

            app(AuditService::class)->log(
                'ACADEMIC_SEMESTER_ACTIVATED',
                'Semester',
                $semester->id,
                [
                    'active_academic_year_ids' => $previousActiveYearIds,
                    'active_semester_ids' => $previousActiveSemesterIds,
                ],
                [
                    'academic_year_id' => $year->id,
                    'academic_year' => $year->name,
                    'term' => $semester->term,
                    'active_academic_year_id' => $year->id,
                    'active_semester_id' => $semester->id,
                ]
            );

            return $semester->fresh('academicYear');
        });
    }

    private function ensureSemesters(string $tenantId, AcademicYear $year)
    {
        $semesters = collect();

        foreach (['GANJIL', 'GENAP'] as $term) {
            $semester = Semester::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('academic_year_id', $year->id)
                ->where('term', $term)
                ->lockForUpdate()
                ->first();

            if ($semester && $semester->trashed()) {
                throw new RuntimeException('A required semester was previously archived; it will not be restored or overwritten automatically.');
            }

            if (!$semester) {
                $semester = Semester::create([
                    'academic_year_id' => $year->id,
                    'term' => $term,
                    'is_active' => false,
                ]);
            }

            if ((string) $semester->tenant_id !== $tenantId
                || (string) $semester->academic_year_id !== (string) $year->id
                || $semester->term !== $term) {
                throw new RuntimeException('Semester ownership or academic-year relation is inconsistent.');
            }

            $semesters->push($semester);
        }

        return $semesters;
    }

    private function parseAcademicYearName(string $name): array
    {
        if (!preg_match('/^(\d{4})\/(\d{4})$/D', $name, $matches)) {
            throw ValidationException::withMessages([
                'academicYearName' => 'Format tahun pelajaran harus YYYY/YYYY, misalnya 2026/2027.',
            ]);
        }

        $start = (int) $matches[1];
        $end = (int) $matches[2];

        if ($end !== $start + 1) {
            throw ValidationException::withMessages([
                'academicYearName' => 'Tahun akhir harus satu tahun setelah tahun awal.',
            ]);
        }

        return [$start, $end];
    }

    private function withinTenantLock(string $tenantId, callable $callback): mixed
    {
        if (trim($tenantId) === '') {
            throw new RuntimeException('Academic year operation requires an explicit tenant.');
        }

        $user = auth()->user();
        abort_unless(
            $user
            && (string) $user->tenant_id === $tenantId
            && $user->roles()->count() === 1
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('academic.manage'),
            403,
            'Hanya Admin Madrasah pada instalasi ini yang dapat mengelola tahun pelajaran dan semester.'
        );

        $context = app(TenantContext::class);
        $previousTenantId = $context->getTenantId();
        $context->setTenantId($tenantId);

        try {
            return DB::transaction(function () use ($tenantId, $callback): mixed {
                $tenant = Tenant::withoutGlobalScopes()
                    ->whereKey($tenantId)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (!$tenant) {
                    throw new RuntimeException('Academic year tenant does not exist.');
                }

                return $callback();
            }, 3);
        } finally {
            $context->setTenantId($previousTenantId);
        }
    }
}
