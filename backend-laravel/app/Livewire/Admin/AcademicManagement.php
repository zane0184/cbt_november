<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Rombel;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AcademicManagement extends Component
{
    use WithPagination;

    public $activeTab = 'rombel'; // rombel, subject, grade_level
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

    private function authorizeAcademicManagement(): void
    {
        $user = auth()->user();
        abort_unless(
            $user
            && $user->roles()->whereIn('name', ['ADMIN_MADRASAH', 'ADMIN', 'OPERATOR'])->exists()
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

    public function openCreateSubjectModal()
    {
        $this->authorizeAcademicManagement();
        $this->reset(['subjectCode', 'subjectName', 'subjectShortName']);
        $this->resetValidation();
        $this->showCreateSubjectModal = true;
    }

    public function createSubject()
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->authorizeAcademicManagement();
        abort_unless(in_array($tab, ['rombel', 'subject', 'grade_level'], true), 422);
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        $rombels = Rombel::with(['academicYear', 'gradeLevel'])
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->latest()
            ->paginate(15);

        $subjects = Subject::query()
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
        $academicYears = AcademicYear::all();

        return view('livewire.admin.academic-management', [
            'rombels' => $rombels,
            'subjects' => $subjects,
            'gradeLevels' => $gradeLevels,
            'academicYears' => $academicYears,
        ])->layout('layouts.app');
    }
}
