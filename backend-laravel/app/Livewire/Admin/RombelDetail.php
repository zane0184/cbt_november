<?php

namespace App\Livewire\Admin;

use App\Models\Rombel;
use Livewire\Component;
use Livewire\WithPagination;

class RombelDetail extends Component
{
    use WithPagination;

    public Rombel $rombel;

    public function mount(string $rombel): void
    {
        $this->authorizeAcademicManagement();

        $this->rombel = Rombel::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->with(['academicYear', 'gradeLevel.phase'])
            ->withCount('students')
            ->whereKey($rombel)
            ->firstOrFail();
    }

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
            'Akses ditolak. Diperlukan Admin Madrasah dan permission academic.manage.'
        );
    }

    public function render()
    {
        $this->authorizeAcademicManagement();

        $students = $this->rombel->students()
            ->with('user')
            ->orderBy('full_name')
            ->paginate(20);

        return view('livewire.admin.rombel-detail', [
            'students' => $students,
        ])->layout('layouts.app');
    }
}
