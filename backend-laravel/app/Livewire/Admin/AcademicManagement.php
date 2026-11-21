<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class AcademicManagement extends Component
{
    use WithPagination;

    private const SUPPORTED_ACADEMIC_YEARS = [
        '2026/2027',
        '2027/2028',
        '2028/2029',
        '2029/2030',
        '2030/2031',
        '2031/2032',
    ];

    public $activeTab = 'rombel';
    public $search = '';

    public $showCreateRombelModal = false;
    public $rombelName = '';
    public $selectedAcademicYearId = '';
    public $selectedGradeLevelId = '';

    public $showCreateSubjectModal = false;
    public $subjectCode = '';
    public $subjectName = '';
    public $subjectShortName = '';

    public $showCreateAcademicYearModal = false;
    public $academicYearName = '';

    public $showCreateSemesterModal = false;
    public $selectedPeriodYearId = '';
    public $selectedPeriodTerm = 'GANJIL';

    public function mount(): void
    {
        $this->authorizeAcademicManagement();
    }

    private function authorizeAcademicManagement(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
            && $user->tenant_id
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('academic.manage'),
            403,
            'Akses ditolak. Hanya Admin Madrasah dengan permission academic.manage yang dapat mengelola data akademik.'
        );
    }

    public function openCreateRombelModal(): void
    {
        $this->authorizeAcademicManagement();
        $this->reset(['rombelName', 'selectedAcademicYearId', 'selectedGradeLevelId']);
        $this->resetValidation();
        $this->showCreateRombelModal = true;
    }

    public function createRombel(): void
    {
        $this->authorizeAcademicManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        $this->validate([
            'rombelName' => 'required|string|max:100',
            'selectedAcademicYearId' => ['required', Rule::exists('academic_years', 'id')->where('tenant_id', $tenantId)],
            'selectedGradeLevelId' => ['required', Rule::exists('grade_levels', 'id')->where('tenant_id', $tenantId)],
        ]);

        DB::transaction(function (): void {
            Rombel::create([
                'name' => $this->rombelName,
                'academic_year_id' => $this->selectedAcademicYearId,
                'grade_level_id' => $this->selectedGradeLevelId,
                'status' => 'ACTIVE',
            ]);
        });

        $this->showCreateRombelModal = false;
        session()->flash('message', "Rombel {$this->rombelName} berhasil dibuat.");
    }

    public function openCreateSubjectModal(): void
    {
        $this->authorizeAcademicManagement();
        $this->reset(['subjectCode', 'subjectName', 'subjectShortName']);
        $this->resetValidation();
        $this->showCreateSubjectModal = true;
    }

    public function createSubject(): void
    {
        $this->authorizeAcademicManagement();

        $this->validate([
            'subjectCode' => 'required|string|max:20',
            'subjectName' => 'required|string|max:100',
            'subjectShortName' => 'nullable|string|max:30',
        ]);

        Subject::create([
            'code' => strtoupper($this->subjectCode),
            'name' => $this->subjectName,
            'short_name' => $this->subjectShortName ?: $this->subjectName,
            'status' => 'ACTIVE',
        ]);

        $this->showCreateSubjectModal = false;
        session()->flash('message', "Mata Pelajaran {$this->subjectName} berhasil ditambahkan.");
    }

    public function openCreateAcademicYearModal(): void
    {
        $this->authorizeAcademicManagement();
        $this->academicYearName = '';
        $this->resetValidation();
        $this->showCreateAcademicYearModal = true;
    }

    public function createAcademicYear(): void
    {
        $this->authorizeAcademicManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        $this->validate([
            'academicYearName' => ['required', 'string', Rule::in(self::SUPPORTED_ACADEMIC_YEARS)],
        ]);

        DB::transaction(function () use ($tenantId): void {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            $exists = AcademicYear::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('name', $this->academicYearName)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'academicYearName' => 'Tahun ajaran tersebut sudah pernah terdaftar pada madrasah ini.',
                ]);
            }

            $year = AcademicYear::create([
                'name' => $this->academicYearName,
                'is_active' => false,
            ]);

            app(AuditService::class)->log(
                'ACADEMIC_YEAR_CREATED',
                'AcademicYear',
                $year->id,
                null,
                ['name' => $year->name, 'is_active' => false]
            );
        }, 3);

        $createdName = $this->academicYearName;
        $this->showCreateAcademicYearModal = false;
        $this->academicYearName = '';
        session()->flash('message', "Tahun ajaran {$createdName} berhasil dibuat dan belum diaktifkan.");
    }

    public function openCreateSemesterModal(?string $academicYearId = null): void
    {
        $this->authorizeAcademicManagement();
        $tenantId = (string) auth()->user()->tenant_id;
        $activeYear = AcademicYear::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();

        $this->selectedPeriodYearId = $academicYearId
            ?: ($activeYear?->id ?? '');
        $this->selectedPeriodTerm = 'GANJIL';
        $this->resetValidation();
        $this->showCreateSemesterModal = true;
    }

    public function createSemester(): void
    {
        $this->authorizeAcademicManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        $this->validate([
            'selectedPeriodYearId' => ['required', Rule::exists('academic_years', 'id')->where('tenant_id', $tenantId)],
            'selectedPeriodTerm' => ['required', Rule::in(['GANJIL', 'GENAP'])],
        ]);

        DB::transaction(function () use ($tenantId): void {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            $year = AcademicYear::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($this->selectedPeriodYearId)
                ->lockForUpdate()
                ->firstOrFail();

            $exists = Semester::withTrashed()
                ->where('tenant_id', $tenantId)
                ->where('academic_year_id', $year->id)
                ->where('term', $this->selectedPeriodTerm)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'selectedPeriodTerm' => 'Semester tersebut sudah pernah dibuat pada tahun ajaran ini.',
                ]);
            }

            $semester = Semester::create([
                'academic_year_id' => $year->id,
                'term' => $this->selectedPeriodTerm,
                'is_active' => false,
            ]);

            app(AuditService::class)->log(
                'SEMESTER_CREATED',
                'Semester',
                $semester->id,
                null,
                [
                    'academic_year_id' => $year->id,
                    'academic_year_name' => $year->name,
                    'term' => $semester->term,
                    'is_active' => false,
                ]
            );
        }, 3);

        $this->showCreateSemesterModal = false;
        session()->flash('message', 'Semester berhasil dibuat dan belum diaktifkan.');
    }

    public function activateAcademicYear(string $academicYearId): void
    {
        $this->authorizeAcademicManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        DB::transaction(function () use ($tenantId, $academicYearId): void {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            $year = AcademicYear::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($academicYearId)
                ->lockForUpdate()
                ->firstOrFail();

            $oldYears = AcademicYear::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get();

            $oldSemesters = Semester::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get();

            if ($oldYears->count() === 1 && (string) $oldYears->first()->id === (string) $year->id) {
                return;
            }

            AcademicYear::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->update(['is_active' => false, 'updated_at' => now()]);

            $year->forceFill(['is_active' => true])->save();

            if ($oldSemesters->isNotEmpty()) {
                Semester::query()
                    ->where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'updated_at' => now()]);

                app(AuditService::class)->log(
                    'SEMESTER_DEACTIVATED_BY_YEAR_CHANGE',
                    'Semester',
                    null,
                    ['active_semester_ids' => $oldSemesters->pluck('id')->all()],
                    ['active_semester_id' => null, 'reason' => 'ACADEMIC_YEAR_CHANGED']
                );
            }

            app(AuditService::class)->log(
                'ACADEMIC_YEAR_ACTIVATED',
                'AcademicYear',
                $year->id,
                ['active_year_ids' => $oldYears->pluck('id')->all()],
                ['active_year_id' => $year->id, 'name' => $year->name]
            );
        }, 3);

        session()->flash('message', 'Tahun ajaran aktif diperbarui. Pilih semester secara terpisah; semester tidak diaktifkan otomatis.');
    }

    public function activateSemester(string $semesterId): void
    {
        $this->authorizeAcademicManagement();
        $tenantId = (string) auth()->user()->tenant_id;

        DB::transaction(function () use ($tenantId, $semesterId): void {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            $activeYears = AcademicYear::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get();

            if ($activeYears->count() !== 1) {
                throw ValidationException::withMessages([
                    'selectedPeriodYearId' => 'Tetapkan tepat satu tahun ajaran aktif sebelum mengaktifkan semester.',
                ]);
            }

            $semester = Semester::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($semesterId)
                ->lockForUpdate()
                ->firstOrFail();

            $activeYear = $activeYears->first();
            if ((string) $semester->academic_year_id !== (string) $activeYear->id) {
                throw ValidationException::withMessages([
                    'selectedPeriodYearId' => 'Semester hanya dapat diaktifkan pada tahun ajaran yang sedang aktif.',
                ]);
            }

            $oldSemesters = Semester::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get();

            if ($oldSemesters->count() === 1 && (string) $oldSemesters->first()->id === (string) $semester->id) {
                return;
            }

            Semester::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->update(['is_active' => false, 'updated_at' => now()]);

            $semester->forceFill(['is_active' => true])->save();

            app(AuditService::class)->log(
                'SEMESTER_ACTIVATED',
                'Semester',
                $semester->id,
                ['active_semester_ids' => $oldSemesters->pluck('id')->all()],
                [
                    'active_semester_id' => $semester->id,
                    'academic_year_id' => $activeYear->id,
                    'term' => $semester->term,
                ]
            );
        }, 3);

        session()->flash('message', 'Semester aktif berhasil diperbarui.');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->authorizeAcademicManagement();
        abort_unless(in_array($tab, ['rombel', 'subject', 'grade_level', 'period'], true), 422);
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        $this->authorizeAcademicManagement();

        $tenantId = (string) auth()->user()->tenant_id;

        $rombels = Rombel::with(['academicYear', 'gradeLevel'])
            ->when($this->search, fn ($query) => $query->where('name', 'like', '%' . $this->search . '%'))
            ->latest()
            ->paginate(15);

        $subjects = Subject::query()
            ->when($this->search, function ($query): void {
                $query->where(function ($searchQuery): void {
                    $searchQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('code', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(15);

        $gradeLevels = GradeLevel::with('phase')->get();
        $academicYears = AcademicYear::query()->orderByDesc('name')->get();
        $semesters = Semester::query()->with('academicYear')->orderBy('term')->get();

        $activeYears = $academicYears->where('is_active', true)->values();
        $activeSemesters = $semesters->where('is_active', true)->values();
        $activeYear = $activeYears->count() === 1 ? $activeYears->first() : null;
        $activeSemester = $activeSemesters->count() === 1 ? $activeSemesters->first() : null;

        return view('livewire.admin.academic-management', [
            'rombels' => $rombels,
            'subjects' => $subjects,
            'gradeLevels' => $gradeLevels,
            'academicYears' => $academicYears,
            'semesters' => $semesters,
            'supportedAcademicYears' => self::SUPPORTED_ACADEMIC_YEARS,
            'activeYear' => $activeYear,
            'activeSemester' => $activeSemester,
            'activeYearCount' => $activeYears->count(),
            'activeSemesterCount' => $activeSemesters->count(),
        ])->layout('layouts.app');
    }
}
