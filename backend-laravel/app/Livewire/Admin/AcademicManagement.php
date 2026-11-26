<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Rombel;
use App\Models\Subject;
use App\Models\Semester;
use App\Services\AcademicYearManagementService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AcademicManagement extends Component
{
    use WithPagination;

    public $activeTab = 'rombel'; // rombel, subject, grade_level, academic_year
    public $search = '';

    public function mount()
    {
        $this->authorizeAcademicManagement();
    }

    // Create Rombel Modal Form State
    public $showCreateRombelModal = false;
    public $rombelName = '';
    public $selectedAcademicYearId = '';
    public $selectedGradeLevelId = '';

    // Create Subject Modal Form State
    public $showCreateSubjectModal = false;
    public $subjectCode = '';
    public $subjectName = '';
    public $subjectShortName = '';
    public $subjectCategory = 'WAJIB';
    public ?string $editingSubjectId = null;

    // Create academic year modal state
    public string $academicYearName = '';
    public bool $showCreateAcademicYearModal = false;

    private function authorizeAcademicManagement(): void
    {
        $user = auth()->user();
        abort_unless(
            $user
            && $user->tenant_id
            && $user->roles()->count() === 1
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('academic.manage'),
            403,
            'Akses ditolak. Hanya Admin Madrasah dengan permission academic.manage yang dapat mengelola data akademik.'
        );
    }

    public function openCreateRombelModal()
    {
        $this->authorizeAcademicManagement();
        $this->reset(['rombelName', 'selectedAcademicYearId', 'selectedGradeLevelId']);
        $this->resetValidation();
        $this->showCreateRombelModal = true;
    }

    public function createRombel()
    {
        $this->authorizeAcademicManagement();
        $tenantId = auth()->user()->tenant_id;

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

    public function openCreateAcademicYearModal(): void
    {
        $this->authorizeAcademicManagement();
        $this->reset(['academicYearName']);
        $this->resetValidation();
        $this->showCreateAcademicYearModal = true;
    }

    public function createAcademicYear(): void
    {
        $this->authorizeAcademicManagement();
        $this->validate([
            'academicYearName' => ['required', 'string', 'max:9', 'regex:/^\\d{4}\\/\\d{4}$/'],
        ]);

        app(AcademicYearManagementService::class)
            ->createForTenant((string) auth()->user()->tenant_id, trim($this->academicYearName));

        $createdName = trim($this->academicYearName);
        $this->showCreateAcademicYearModal = false;
        $this->reset('academicYearName');
        session()->flash('message', "Tahun pelajaran {$createdName} berhasil dibuat beserta Semester Ganjil dan Genap.");
    }

    public function activateSemester(string $semesterId): void
    {
        $this->authorizeAcademicManagement();

        $semester = app(AcademicYearManagementService::class)
            ->activateSemesterForTenant((string) auth()->user()->tenant_id, $semesterId);

        session()->flash('message', "Semester {$semester->term} tahun pelajaran {$semester->academicYear->name} berhasil diaktifkan.");
    }

    public function openCreateSubjectModal()
    {
        $this->authorizeAcademicManagement();
        $this->reset(['subjectCode', 'subjectName', 'subjectShortName', 'subjectCategory', 'editingSubjectId']);
        $this->resetValidation();
        $this->showCreateSubjectModal = true;
    }

    public function createSubject()
    {
        $this->authorizeAcademicManagement();
        $this->subjectCode = strtoupper(trim($this->subjectCode));
        $this->subjectName = trim($this->subjectName);
        $this->validate([
            'subjectCode' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('subjects', 'code')->where('tenant_id', auth()->user()->tenant_id)],
            'subjectName' => 'required|string|max:100',
            'subjectShortName' => 'nullable|string|max:30',
            'subjectCategory' => ['required', Rule::in(['WAJIB', 'MULOK'])],
        ]);

        DB::transaction(function (): void {
            $subject = Subject::create([
                'code' => strtoupper($this->subjectCode),
                'name' => $this->subjectName,
                'short_name' => $this->subjectShortName ?: $this->subjectName,
                'subject_category' => $this->subjectCategory,
                'status' => 'ACTIVE',
            ]);

            app(AuditService::class)->log('SUBJECT_CREATED', 'Subject', $subject->id, null, [
                'code' => $subject->code,
                'name' => $subject->name,
                'subject_category' => $subject->subject_category,
            ]);
        });

        $createdName = $this->subjectName;
        $this->showCreateSubjectModal = false;
        session()->flash('message', "Mata Pelajaran {$createdName} berhasil ditambahkan.");
    }


    public function openEditSubjectModal(string $subjectId): void
    {
        $this->authorizeAcademicManagement();
        $subject = Subject::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereKey($subjectId)
            ->firstOrFail();

        abort_unless($subject->subject_category === 'MULOK', 403, 'Hanya mata pelajaran Mulok milik madrasah yang dapat diubah.');

        $this->editingSubjectId = $subject->id;
        $this->subjectCode = $subject->code;
        $this->subjectName = $subject->name;
        $this->subjectShortName = $subject->short_name ?? '';
        $this->subjectCategory = 'MULOK';
        $this->resetValidation();
        $this->showCreateSubjectModal = true;
    }

    public function updateSubject(): void
    {
        $this->authorizeAcademicManagement();
        $tenantId = (string) auth()->user()->tenant_id;
        $subjectId = $this->editingSubjectId;
        abort_unless($subjectId, 404);

        $subject = Subject::query()->where('tenant_id', $tenantId)->whereKey($subjectId)->firstOrFail();
        abort_unless($subject->subject_category === 'MULOK', 403, 'Mata pelajaran wajib/registry tidak dapat diubah.');

        $this->subjectCode = strtoupper(trim($this->subjectCode));
        $this->subjectName = trim($this->subjectName);
        $this->validate([
            'subjectCode' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('subjects', 'code')->where('tenant_id', $tenantId)->ignore($subjectId)],
            'subjectName' => 'required|string|max:100',
            'subjectShortName' => 'nullable|string|max:30',
        ]);

        DB::transaction(function () use ($tenantId, $subjectId): void {
            $locked = Subject::query()->where('tenant_id', $tenantId)->whereKey($subjectId)->lockForUpdate()->firstOrFail();
            abort_unless($locked->subject_category === 'MULOK', 403, 'Hanya Mulok yang dapat diubah.');
            $before = $locked->only(['code', 'name', 'short_name', 'subject_category', 'status']);
            $locked->fill([
                'code' => $this->subjectCode,
                'name' => $this->subjectName,
                'short_name' => $this->subjectShortName ?: $this->subjectName,
            ]);
            $locked->save();
            app(AuditService::class)->log('SUBJECT_UPDATED', 'Subject', $locked->id, $before, $locked->only(['code', 'name', 'short_name', 'subject_category', 'status']));
        });

        $updatedName = $this->subjectName;
        $this->showCreateSubjectModal = false;
        $this->reset(['subjectCode', 'subjectName', 'subjectShortName', 'subjectCategory', 'editingSubjectId']);
        session()->flash('message', "Mata pelajaran Mulok {$updatedName} berhasil diperbarui.");
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->authorizeAcademicManagement();
        abort_unless(in_array($tab, ['rombel', 'subject', 'grade_level', 'academic_year'], true), 422);
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        $rombels = Rombel::with(['academicYear', 'gradeLevel'])->withCount('students')
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->latest()
            ->paginate(15);

        $subjects = Subject::query()
            ->where('code', '!=', 'MUATAN_LOKAL')
            ->when($this->search, function ($query): void {
                // Group alternatives to preserve tenant/soft-delete constraints.
                $query->where(function ($searchQuery): void {
                    $searchQuery->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('code', 'like', '%' . $this->search . '%');
                });
            })
            ->latest()
            ->paginate(15);

        $gradeLevels = GradeLevel::with('phase')->get();
        $academicYears = AcademicYear::with('semesters')->orderByDesc('name')->get();
        $activeSemester = Semester::with('academicYear')->where('is_active', true)->first();

        return view('livewire.admin.academic-management', [
            'rombels' => $rombels,
            'subjects' => $subjects,
            'gradeLevels' => $gradeLevels,
            'academicYears' => $academicYears,
            'activeSemester' => $activeSemester,
        ])->layout('layouts.app');
    }
}
