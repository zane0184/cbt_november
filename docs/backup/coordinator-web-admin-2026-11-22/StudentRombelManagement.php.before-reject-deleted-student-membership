<?php

namespace App\Livewire\Admin;

use App\Models\Rombel;
use App\Models\StudentProfile;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class StudentRombelManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $selectedStudentProfileId = '';
    public string $selectedRombelId = '';

    public function mount(): void
    {
        $this->authorizeAcademicManagement();
    }

    private function authorizeAcademicManagement(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id
            && $user->roles()->count() === 1
            && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
            && $user->hasPermission('academic.manage'),
            403,
            'Akses ditolak. Diperlukan Admin Madrasah dan permission academic.manage.'
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function addStudentToRombel(): void
    {
        $this->authorizeAcademicManagement();
        $tenantId = auth()->user()->tenant_id;

        $this->validate([
            'selectedStudentProfileId' => [
                'required', 'uuid',
                Rule::exists('student_profiles', 'id')->where('tenant_id', $tenantId),
            ],
            'selectedRombelId' => [
                'required', 'uuid',
                Rule::exists('rombels', 'id')->where('tenant_id', $tenantId),
            ],
        ]);

        DB::transaction(function () use ($tenantId): void {
            $student = StudentProfile::query()
                ->whereKey($this->selectedStudentProfileId)
                ->lockForUpdate()
                ->firstOrFail();
            $rombel = Rombel::query()
                ->whereKey($this->selectedRombelId)
                ->lockForUpdate()
                ->firstOrFail();
            $studentUser = $student->user;

            if (!$studentUser || $studentUser->tenant_id !== $tenantId
                || $studentUser->roles()->count() !== 1
                || !$studentUser->roles()->where('name', 'SISWA')->exists()) {
                throw ValidationException::withMessages([
                    'selectedStudentProfileId' => 'Profil harus terhubung ke akun Siswa aktif pada tenant ini.',
                ]);
            }

            if ($student->tenant_id !== $tenantId || $rombel->tenant_id !== $tenantId || $rombel->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'selectedRombelId' => 'Rombel harus aktif dan berada pada tenant yang sama.',
                ]);
            }

            $existing = DB::table('rombel_student')
                ->where('tenant_id', $tenantId)
                ->where('rombel_id', $rombel->id)
                ->where('student_id', $student->id)
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'selectedStudentProfileId' => 'Siswa sudah terdaftar pada rombel ini.',
                ]);
            }

            DB::table('rombel_student')->insert([
                'id' => (string) Str::uuid(),
                'tenant_id' => $tenantId,
                'rombel_id' => $rombel->id,
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            app(AuditService::class)->log(
                'STUDENT_ADDED_TO_ROMBEL',
                'RombelStudent',
                $student->id,
                null,
                [
                    'tenant_id' => $tenantId,
                    'student_profile_id' => $student->id,
                    'student_user_id' => $studentUser->id,
                    'rombel_id' => $rombel->id,
                    'academic_year_id' => $rombel->academic_year_id,
                ]
            );
        }, 3);

        $this->reset(['selectedStudentProfileId', 'selectedRombelId']);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Siswa berhasil ditambahkan ke rombel.']);
        session()->flash('message', 'Siswa berhasil ditambahkan ke rombel. Riwayat rombel sebelumnya tidak dihapus.');
    }

    public function render()
    {
        $this->authorizeAcademicManagement();
        $tenantId = auth()->user()->tenant_id;

        $students = StudentProfile::query()
            ->with(['user', 'rombels.academicYear'])
            ->whereHas('user.roles', fn ($query) => $query->where('name', 'SISWA'))
            ->whereHas('user', fn ($query) => $query->whereNull('deleted_at')->has('roles', '=', 1))
            ->when($this->search, function ($query): void {
                $query->where(function ($scope): void {
                    $scope->where('nisn', 'like', '%' . $this->search . '%')
                        ->orWhere('full_name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%' . $this->search . '%'));
                });
            })
            ->latest()
            ->paginate(15);

        $availableStudents = StudentProfile::query()
            ->with('user')
            ->whereHas('user.roles', fn ($query) => $query->where('name', 'SISWA'))
            ->whereHas('user', fn ($query) => $query->whereNull('deleted_at')->has('roles', '=', 1))
            ->orderBy('full_name')
            ->get();

        $rombels = Rombel::query()
            ->with('academicYear')
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.student-rombel-management', compact('students', 'availableStudents', 'rombels', 'tenantId'))
            ->layout('layouts.app');
    }
}
