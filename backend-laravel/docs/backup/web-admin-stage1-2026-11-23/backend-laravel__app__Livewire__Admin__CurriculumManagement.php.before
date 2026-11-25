<?php

namespace App\Livewire\Admin;

use App\Models\LearningOutcome;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class CurriculumManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $subjectFilter = '';

    public function mount()
    {
        $user = auth()->user();
        abort_unless($user && $user->hasPermission('curriculum.view'), 403, 'Akses ditolak. Permission curriculum.view diperlukan.');
    }

    // Modal Form State for TP (LearningObjective)
    public $showCreateTpModal = false;
    public $selectedOutcomeId = '';
    public $tpCode = '';
    public $tpTitle = '';
    public $tpDescription = '';

    private function authorizeCurriculumManagement(): void
    {
        $user = auth()->user();
        abort_unless(
            $user
            && $user->roles()->whereIn('name', ['ADMIN_MADRASAH', 'ADMIN'])->exists()
            && $user->hasPermission('curriculum.manage'),
            403,
            'Akses ditolak. Hanya Admin Madrasah dengan permission curriculum.manage yang dapat mengubah struktur kurikulum.'
        );
    }

    public function openCreateTpModal()
    {
        $this->authorizeCurriculumManagement();
        $this->reset(['selectedOutcomeId', 'tpCode', 'tpTitle', 'tpDescription']);
        $this->resetValidation();
        $this->showCreateTpModal = true;
    }

    public function createTp()
    {
        $this->authorizeCurriculumManagement();
        $tenantId = auth()->user()->tenant_id;
        $this->validate([
            'selectedOutcomeId' => ['required', Rule::exists('learning_outcomes', 'id')->where('tenant_id', $tenantId)],
            'tpCode' => 'required|string|max:50',
            'tpTitle' => 'required|string|max:255',
            'tpDescription' => 'nullable|string',
        ]);

        DB::transaction(function (): void {
            $outcome = LearningOutcome::query()->lockForUpdate()->findOrFail($this->selectedOutcomeId);
            abort_unless($outcome->tenant_id === auth()->user()->tenant_id, 403);

            $outcome->objectives()->create([
                'code' => $this->tpCode,
                'title' => $this->tpTitle,
                'description' => $this->tpDescription,
                'status' => 'ACTIVE',
                'created_by' => auth()->id(),
            ]);
        });

        $this->showCreateTpModal = false;
        session()->flash('message', "Tujuan Pembelajaran (TP) {$this->tpCode} berhasil ditambahkan.");
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSubjectFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $outcomes = LearningOutcome::with(['subject', 'phase', 'currentVersion', 'objectives'])
            ->when($this->subjectFilter, fn($q) => $q->where('subject_id', $this->subjectFilter))
            ->when($this->search, function ($q) {
                // Keep all search alternatives grouped so they cannot bypass
                // the selected subject filter or other outer query constraints.
                $q->where(function ($searchQuery) {
                    $searchQuery->where('code', 'like', '%' . $this->search . '%')
                        ->orWhereHas('objectives', function ($objectiveQuery) {
                            $objectiveQuery->where(function ($termQuery) {
                                $termQuery->where('title', 'like', '%' . $this->search . '%')
                                    ->orWhere('code', 'like', '%' . $this->search . '%');
                            });
                        });
                });
            })
            ->latest()
            ->paginate(10);

        $subjects = Subject::query()->orderBy('name')->get();
        $canManageCurriculum = auth()->user()?->hasPermission('curriculum.manage') ?? false;

        return view('livewire.admin.curriculum-management', [
            'outcomes' => $outcomes,
            'subjects' => $subjects,
            'canManageCurriculum' => $canManageCurriculum,
        ])->layout('layouts.app');
    }
}
