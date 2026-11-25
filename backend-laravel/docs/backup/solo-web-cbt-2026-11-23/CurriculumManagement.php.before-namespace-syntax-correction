<?php

namespace App\\Livewire\\Admin;

use App\\Models\\LearningOutcome;
use App\\Models\\Subject;
use Livewire\\Component;
use Livewire\\WithPagination;

class CurriculumManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $subjectFilter = '';

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
            && $user->roles()->count() === 1
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('curriculum.view'),
            403,
            'Akses ditolak. Halaman referensi kurikulum hanya tersedia bagi Admin Madrasah dengan permission curriculum.view.'
        );
    }

    /**
     * CP induk adalah referensi terlindungi. TP/ATP dikembangkan Guru
     * melalui konteks penugasan, bukan melalui portal administrasi.
     *
     * Method lama dipertahankan sebagai compatibility guard agar request
     * Livewire langsung dari client lama selalu ditolak secara eksplisit.
     */
    public function openCreateTpModal(): void
    {
        abort(403, 'Admin Madrasah tidak berwenang membuat TP. Gunakan Portal Guru sesuai penugasan.');
    }

    public function createTp(): void
    {
        abort(403, 'Admin Madrasah tidak berwenang membuat TP. Gunakan Portal Guru sesuai penugasan.');
    }

    public function updatingSearch(): void
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
            ->when($this->subjectFilter, fn ($query) => $query->where('subject_id', $this->subjectFilter))
            ->when($this->search, function ($query): void {
                $query->where(function ($searchQuery): void {
                    $searchQuery->where('code', 'like', '%' . $this->search . '%')
                        ->orWhereHas('objectives', function ($objectiveQuery): void {
                            $objectiveQuery->where(function ($termQuery): void {
                                $termQuery->where('title', 'like', '%' . $this->search . '%')
                                    ->orWhere('code', 'like', '%' . $this->search . '%');
                            });
                        });
                });
            })
            ->latest()
            ->paginate(10);

        $subjects = Subject::query()->orderBy('name')->get();

        return view('livewire.admin.curriculum-management', [
            'outcomes' => $outcomes,
            'subjects' => $subjects,
            'legacyReferenceOnly' => true,
        ])->layout('layouts.app');
    }
}
