<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\StudentAcademicEvent;
use App\Models\StudentProfile;
use App\Services\StudentLifecycleService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class StudentRombelManagement extends Component
{
    use WithPagination;

    public string $selectedRombelId = '';
    public string $selectedStudentId = '';
    public string $enrollmentReason = '';
    public string $enrollmentEffectiveDate = '';

    public string $search = '';
    public string $statusFilter = 'ACTIVE';

    public bool $showLifecycleModal = false;
    public string $actionType = '';
    public string $actionStudentId = '';
    public string $actionFromRombelId = '';
    public string $actionToRombelId = '';
    public string $targetStatus = '';
    public string $actionReason = '';
    public string $actionEffectiveDate = '';

    public function mount(): void
    {
        $this->authorizeAdmin();
        $this->enrollmentEffectiveDate = now()->toDateString();
        $this->actionEffectiveDate = now()->toDateString();
    }

    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        abort_unless(
            $user && $user->tenant_id && $user->is_active
                && $user->roles()->count() === 1
                && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
                && $user->hasPermission('academic.manage'),
            403,
            'Hanya Admin Madrasah aktif dengan satu role dan permission academic.manage yang dapat mengelola data siswa.'
        );
    }

    public function updatedSearch(): void
    {
        $this->resetPage('studentsPage');
        $this->resetPage('membershipsPage');
        $this->resetPage('historyPage');
    }

    public function updatedStatusFilter(): void
    {
        abort_unless(in_array($this->statusFilter, ['ALL', 'ACTIVE', 'INACTIVE', 'ARCHIVED', 'GRADUATED', 'ALUMNI'], true), 422);
        $this->resetPage('studentsPage');
    }

    public function addStudentToRombel(): void
    {
        $this->authorizeAdmin();
        $data = $this->validate([
            'selectedRombelId' => ['required', 'uuid'],
            'selectedStudentId' => ['required', 'uuid'],
            'enrollmentEffectiveDate' => ['required', 'date'],
            'enrollmentReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        app(StudentLifecycleService::class)->enroll(
            (string) auth()->user()->tenant_id,
            (string) auth()->id(),
            $data['selectedStudentId'],
            $data['selectedRombelId'],
            $data['enrollmentEffectiveDate'],
            $data['enrollmentReason']
        );

        $this->selectedStudentId = '';
        $this->selectedRombelId = '';
        $this->enrollmentReason = '';
        session()->flash('rombelMessage', 'Siswa berhasil ditempatkan. Histori akademik dan audit log telah dicatat.');
        $this->resetPage('membershipsPage');
        $this->resetPage('historyPage');
    }

    public function openActionModal(string $actionType, string $studentId, ?string $fromRombelId = null, ?string $targetStatus = null): void
    {
        $this->authorizeAdmin();
        abort_unless(in_array($actionType, ['TRANSFER', 'REMOVE', 'PROMOTE', 'RETAIN', 'GRADUATE', 'STATUS'], true), 422);

        StudentProfile::query()->where('tenant_id', auth()->user()->tenant_id)
            ->whereNull('deleted_at')->whereKey($studentId)->firstOrFail();

        if ($fromRombelId) {
            abort_unless(DB::table('rombel_student')->where('tenant_id', auth()->user()->tenant_id)
                ->where('student_id', $studentId)->where('rombel_id', $fromRombelId)->exists(), 404);
        }

        if ($actionType === 'STATUS') {
            abort_unless(in_array($targetStatus, ['ACTIVE', 'INACTIVE', 'ARCHIVED', 'ALUMNI'], true), 422);
        }

        $this->actionType = $actionType;
        $this->actionStudentId = $studentId;
        $this->actionFromRombelId = $fromRombelId ?? '';
        $this->actionToRombelId = '';
        $this->targetStatus = $targetStatus ?? '';
        $this->actionReason = '';
        $this->actionEffectiveDate = now()->toDateString();
        $this->resetValidation();
        $this->showLifecycleModal = true;
    }

    public function closeLifecycleModal(): void
    {
        $this->showLifecycleModal = false;
        $this->actionType = '';
        $this->actionStudentId = '';
        $this->actionFromRombelId = '';
        $this->actionToRombelId = '';
        $this->targetStatus = '';
        $this->actionReason = '';
        $this->actionEffectiveDate = now()->toDateString();
        $this->resetValidation();
    }

    public function processLifecycleAction(): void
    {
        $this->authorizeAdmin();
        $needsTarget = in_array($this->actionType, ['TRANSFER', 'PROMOTE', 'RETAIN'], true);
        $needsFrom = in_array($this->actionType, ['TRANSFER', 'PROMOTE', 'RETAIN', 'REMOVE', 'GRADUATE'], true);

        $rules = [
            'actionType' => ['required', Rule::in(['TRANSFER', 'REMOVE', 'PROMOTE', 'RETAIN', 'GRADUATE', 'STATUS'])],
            'actionStudentId' => ['required', 'uuid'],
            'actionEffectiveDate' => ['required', 'date'],
            'actionReason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
        if ($needsFrom) $rules['actionFromRombelId'] = ['required', 'uuid'];
        if ($needsTarget) $rules['actionToRombelId'] = ['required', 'uuid'];
        if ($this->actionType === 'STATUS') $rules['targetStatus'] = ['required', Rule::in(['ACTIVE', 'INACTIVE', 'ARCHIVED', 'ALUMNI'])];
        $data = $this->validate($rules);

        $tenantId = (string) auth()->user()->tenant_id;
        $actorId = (string) auth()->id();
        $service = app(StudentLifecycleService::class);

        match ($data['actionType']) {
            'TRANSFER' => $service->transfer($tenantId, $actorId, $data['actionStudentId'], $data['actionFromRombelId'], $data['actionToRombelId'], $data['actionEffectiveDate'], $data['actionReason']),
            'REMOVE' => $service->remove($tenantId, $actorId, $data['actionStudentId'], $data['actionFromRombelId'], $data['actionEffectiveDate'], $data['actionReason']),
            'PROMOTE' => $service->promote($tenantId, $actorId, $data['actionStudentId'], $data['actionFromRombelId'], $data['actionToRombelId'], $data['actionEffectiveDate'], $data['actionReason']),
            'RETAIN' => $service->retain($tenantId, $actorId, $data['actionStudentId'], $data['actionFromRombelId'], $data['actionToRombelId'], $data['actionEffectiveDate'], $data['actionReason']),
            'GRADUATE' => $service->graduate($tenantId, $actorId, $data['actionStudentId'], $data['actionFromRombelId'], $data['actionEffectiveDate'], $data['actionReason']),
            'STATUS' => $service->changeStatus($tenantId, $actorId, $data['actionStudentId'], $data['targetStatus'], $data['actionEffectiveDate'], $data['actionReason']),
        };

        $this->closeLifecycleModal();
        session()->flash('rombelMessage', 'Perubahan status/penempatan siswa berhasil diproses dan dicatat dalam histori akademik.');
        $this->resetPage('studentsPage');
        $this->resetPage('membershipsPage');
        $this->resetPage('historyPage');
    }

    public function render()
    {
        $this->authorizeAdmin();
        $tenantId = (string) auth()->user()->tenant_id;
        $activeYear = AcademicYear::query()->where('tenant_id', $tenantId)->where('is_active', true)->first();

        $rombels = $activeYear
            ? Rombel::query()->with('gradeLevel')->where('tenant_id', $tenantId)
                ->where('academic_year_id', $activeYear->id)->where('status', 'ACTIVE')
                ->orderBy('grade_level_id')->orderBy('name')->get()
            : collect();

        $placedStudentIds = $activeYear
            ? DB::table('rombel_student')->where('tenant_id', $tenantId)
                ->where('academic_year_id', $activeYear->id)->pluck('student_id')
            : collect();

        $availableStudents = $activeYear
            ? StudentProfile::query()->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ->where('academic_status', 'ACTIVE')
                ->whereRaw('LENGTH(nisn) = 10')
                ->whereNotIn('id', $placedStudentIds)
                ->whereHas('user', fn ($query) => $query->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')->where('is_active', true)
                    ->has('roles', '=', 1)
                    ->whereHas('roles', fn ($roles) => $roles->where('name', 'SISWA')))
                ->orderBy('full_name')->get()
            : collect();

        $students = StudentProfile::query()->with(['user', 'entryAcademicYear'])
            ->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereHas('user', fn ($query) => $query->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')->has('roles', '=', 1)
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'SISWA')))
            ->when($this->statusFilter !== 'ALL', fn ($query) => $query->where('academic_status', $this->statusFilter))
            ->when(trim($this->search) !== '', fn ($query) => $query->where(function ($nested) {
                $term = '%'.trim($this->search).'%';
                $nested->where('full_name', 'like', $term)->orWhere('nisn', 'like', $term)->orWhere('nis', 'like', $term);
            }))
            ->orderBy('full_name')->paginate(15, ['*'], 'studentsPage');

        $studentsWithMemberships = StudentProfile::query()
            ->with(['user', 'rombels' => fn ($query) => $query->withTrashed()->with(['gradeLevel', 'academicYear'])])
            ->where('tenant_id', $tenantId)->whereNull('deleted_at')
            ->whereHas('rombels', fn ($query) => $query->withTrashed())
            ->whereHas('user', fn ($query) => $query->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')->has('roles', '=', 1)
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'SISWA')))
            ->when(trim($this->search) !== '', fn ($query) => $query->where(function ($nested) {
                $term = '%'.trim($this->search).'%';
                $nested->where('full_name', 'like', $term)->orWhere('nisn', 'like', $term);
            }))
            ->orderBy('full_name')->paginate(15, ['*'], 'membershipsPage');

        $history = StudentAcademicEvent::query()->with(['student', 'fromRombel', 'toRombel', 'academicYear', 'actor'])
            ->where('tenant_id', $tenantId)->orderByDesc('created_at')
            ->paginate(20, ['*'], 'historyPage');

        return view('livewire.admin.student-rombel-management', compact(
            'activeYear', 'rombels', 'availableStudents', 'students', 'studentsWithMemberships', 'history'
        ))->layout('layouts.app');
    }
}
