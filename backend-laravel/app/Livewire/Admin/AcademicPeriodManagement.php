<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\Semester;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AcademicPeriodManagement extends Component
{
    public string $yearName = '';
    public string $selectedYearId = '';
    public string $semesterTerm = 'GANJIL';

    public function mount(): void
    {
        $this->authorizeManagement();
        $this->selectedYearId = (string) (AcademicYear::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->value('id') ?? '');
    }

    private function authorizeManagement(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id
                && $user->roles()->whereIn('name', ['ADMIN_MADRASAH', 'OPERATOR'])->exists()
                && $user->hasPermission('academic.manage'),
            403,
            'Hanya Admin Madrasah dengan permission academic.manage yang dapat mengelola tahun ajaran dan semester.'
        );
    }

    public function createAcademicYear(AuditService $auditService): void
    {
        $this->authorizeManagement();
        $tenantId = (string) auth()->user()->tenant_id;
        $data = $this->validate([
            'yearName' => [
                'required',
                'string',
                'regex:/^\d{4}\/\d{4}$/',
                'max:9',
                Rule::unique('academic_years', 'name')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
        ]);

        $year = DB::transaction(function () use ($tenantId, $data, $auditService) {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            if (AcademicYear::query()->where('tenant_id', $tenantId)->where('name', $data['yearName'])->exists()) {
                throw ValidationException::withMessages(['yearName' => 'Tahun ajaran tersebut sudah terdaftar.']);
            }
            $year = AcademicYear::create(['name' => $data['yearName'], 'is_active' => false]);
            $auditService->log('ACADEMIC_YEAR_CREATED', 'AcademicYear', $year->id, null, [
                'tenant_id' => $tenantId,
                'name' => $year->name,
                'is_active' => false,
            ]);
            return $year;
        });

        $this->selectedYearId = (string) $year->id;
        $this->yearName = '';
        session()->flash('academicPeriodMessage', 'Tahun ajaran berhasil ditambahkan. Aktifkan periode setelah memeriksa data.');
    }

    public function activateAcademicYear(string $yearId, AuditService $auditService): void
    {
        $this->authorizeManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        DB::transaction(function () use ($tenantId, $yearId, $auditService): void {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            $year = AcademicYear::query()->where('tenant_id', $tenantId)->whereKey($yearId)->lockForUpdate()->firstOrFail();
            $oldActive = AcademicYear::query()->where('tenant_id', $tenantId)->where('is_active', true)->lockForUpdate()->get();

            AcademicYear::query()->where('tenant_id', $tenantId)->where('is_active', true)
                ->where('id', '<>', $year->id)->update(['is_active' => false]);
            $year->update(['is_active' => true]);

            // A semester from another year must never remain the active tenant period.
            Semester::query()->where('tenant_id', $tenantId)
                ->where('academic_year_id', '<>', $year->id)
                ->where('is_active', true)->update(['is_active' => false]);

            $auditService->log('ACADEMIC_YEAR_ACTIVATED', 'AcademicYear', $year->id, [
                'active_year_ids' => $oldActive->pluck('id')->all(),
            ], [
                'active_year_id' => $year->id,
                'name' => $year->name,
            ]);
        });

        $this->selectedYearId = $yearId;
        session()->flash('academicPeriodMessage', 'Tahun ajaran aktif diperbarui. Semester dari tahun ajaran lain dinonaktifkan.');
    }

    public function createSemester(AuditService $auditService): void
    {
        $this->authorizeManagement();
        $tenantId = (string) auth()->user()->tenant_id;
        $data = $this->validate([
            'selectedYearId' => ['required', 'uuid', Rule::exists('academic_years', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'semesterTerm' => [
                'required',
                Rule::in(['GANJIL', 'GENAP']),
                Rule::unique('semesters', 'term')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('academic_year_id', $this->selectedYearId)),
            ],
        ]);

        DB::transaction(function () use ($tenantId, $data, $auditService): void {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            $year = AcademicYear::query()->where('tenant_id', $tenantId)
                ->whereKey($data['selectedYearId'])->lockForUpdate()->firstOrFail();
            if (Semester::query()->where('tenant_id', $tenantId)->where('academic_year_id', $year->id)->where('term', $data['semesterTerm'])->exists()) {
                throw ValidationException::withMessages(['semesterTerm' => 'Semester tersebut sudah terdaftar pada tahun ajaran ini.']);
            }
            $semester = Semester::create([
                'academic_year_id' => $year->id,
                'term' => $data['semesterTerm'],
                'is_active' => false,
            ]);
            $auditService->log('SEMESTER_CREATED', 'Semester', $semester->id, null, [
                'tenant_id' => $tenantId,
                'academic_year_id' => $year->id,
                'term' => $semester->term,
                'is_active' => false,
            ]);
        });

        $this->semesterTerm = 'GANJIL';
        session()->flash('academicPeriodMessage', 'Semester berhasil ditambahkan dalam keadaan nonaktif.');
    }

    public function activateSemester(string $semesterId, AuditService $auditService): void
    {
        $this->authorizeManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        DB::transaction(function () use ($tenantId, $semesterId, $auditService): void {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();
            $semester = Semester::query()->where('tenant_id', $tenantId)->whereKey($semesterId)->lockForUpdate()->firstOrFail();
            $year = AcademicYear::query()->where('tenant_id', $tenantId)
                ->whereKey($semester->academic_year_id)->lockForUpdate()->firstOrFail();

            if (!$year->is_active) {
                throw ValidationException::withMessages([
                    'selectedYearId' => 'Aktifkan tahun ajaran terlebih dahulu sebelum mengaktifkan semester.',
                ]);
            }

            $oldActive = Semester::query()->where('tenant_id', $tenantId)->where('is_active', true)->lockForUpdate()->get();
            Semester::query()->where('tenant_id', $tenantId)->where('is_active', true)
                ->where('id', '<>', $semester->id)->update(['is_active' => false]);
            $semester->update(['is_active' => true]);

            $auditService->log('SEMESTER_ACTIVATED', 'Semester', $semester->id, [
                'active_semester_ids' => $oldActive->pluck('id')->all(),
            ], [
                'active_semester_id' => $semester->id,
                'academic_year_id' => $year->id,
                'term' => $semester->term,
            ]);
        });

        session()->flash('academicPeriodMessage', 'Semester aktif diperbarui. Hanya satu semester aktif untuk tenant ini.');
    }

    public function render()
    {
        $tenantId = (string) auth()->user()->tenant_id;
        $years = AcademicYear::query()->where('tenant_id', $tenantId)
            ->with(['semesters' => fn ($query) => $query->where('tenant_id', $tenantId)->orderBy('term')])
            ->orderByDesc('name')->get();

        return view('livewire.admin.academic-period-management', [
            'years' => $years,
            'activeYear' => $years->firstWhere('is_active', true),
            'activeSemester' => Semester::query()->where('tenant_id', $tenantId)->where('is_active', true)->with('academicYear')->first(),
        ])->layout('layouts.app');
    }
}
