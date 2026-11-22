<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class TeacherSubjectAssignmentManagement extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'ALL';
    public bool $showForm = false;
    public string $editingAssignmentId = '';

    public string $teacherProfileId = '';
    public string $subjectId = '';
    public string $rombelId = '';
    public string $academicYearId = '';
    public string $semesterId = '';
    public string $startsAt = '';
    public string $endsAt = '';
    public string $assignmentStatus = 'ACTIVE';
    public string $changeReason = '';
    public bool $showDeactivateModal = false;
    public string $deactivatingAssignmentId = '';
    public string $deactivationReason = '';

    public function boot(): void
    {
        $this->assertCanManage();
    }

    public function mount(): void
    {
        $this->assertCanManage();

        $activeYear = AcademicYear::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->first();

        $this->academicYearId = $activeYear?->id ?? '';
        $this->semesterId = Semester::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->where('academic_year_id', $this->academicYearId)
            ->where('is_active', true)
            ->value('id') ?? '';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        abort_unless(in_array($this->statusFilter, ['ALL', 'ACTIVE', 'INACTIVE'], true), 422);
        $this->resetPage();
    }

    public function updatedAcademicYearId(): void
    {
        $tenantId = auth()->user()->tenant_id;

        if ($this->semesterId !== '' && ! Semester::query()
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', $this->academicYearId)
            ->whereKey($this->semesterId)
            ->exists()) {
            $this->semesterId = '';
        }

        if ($this->rombelId !== '' && ! Rombel::query()
            ->where('tenant_id', $tenantId)
            ->where('academic_year_id', $this->academicYearId)
            ->whereKey($this->rombelId)
            ->exists()) {
            $this->rombelId = '';
        }
    }

    public function openCreate(): void
    {
        $this->assertCanManage();
        $this->resetValidation();
        $this->reset([
            'editingAssignmentId',
            'teacherProfileId',
            'subjectId',
            'rombelId',
            'semesterId',
            'startsAt',
            'endsAt',
            'changeReason',
        ]);
        $this->assignmentStatus = 'ACTIVE';
        $this->showForm = true;
    }

    public function edit(string $assignmentId): void
    {
        $this->assertCanManage();
        $assignment = TeacherSubjectAssignment::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereKey($assignmentId)
            ->firstOrFail();

        $this->resetValidation();
        $this->editingAssignmentId = $assignment->id;
        $this->teacherProfileId = $assignment->teacher_profile_id;
        $this->subjectId = $assignment->subject_id;
        $this->rombelId = $assignment->rombel_id;
        $this->academicYearId = $assignment->academic_year_id;
        $this->semesterId = $assignment->semester_id;
        $this->startsAt = $assignment->starts_at?->format('Y-m-d') ?? '';
        $this->endsAt = $assignment->ends_at?->format('Y-m-d') ?? '';
        $this->assignmentStatus = $assignment->status;
        $this->changeReason = '';
        $this->showForm = true;
    }

    public function cancelForm(): void
    {
        $this->showForm = false;
        $this->resetValidation();
        $this->reset([
            'editingAssignmentId',
            'teacherProfileId',
            'subjectId',
            'rombelId',
            'startsAt',
            'endsAt',
        ]);
        $this->assignmentStatus = 'ACTIVE';
    }

    public function save(): void
    {
        $this->assertCanManage();
        $tenantId = (string) auth()->user()->tenant_id;
        $actor = auth()->user();

        $data = $this->validate([
            'teacherProfileId' => ['required', 'uuid', Rule::exists('teacher_profiles', 'id')->where('tenant_id', $tenantId)],
            'subjectId' => ['required', 'uuid', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'rombelId' => ['required', 'uuid', Rule::exists('rombels', 'id')->where('tenant_id', $tenantId)],
            'academicYearId' => ['required', 'uuid', Rule::exists('academic_years', 'id')->where('tenant_id', $tenantId)],
            'semesterId' => ['required', 'uuid', Rule::exists('semesters', 'id')->where('tenant_id', $tenantId)],
            'startsAt' => ['nullable', 'date_format:Y-m-d'],
            'endsAt' => ['nullable', 'date_format:Y-m-d'],
            'assignmentStatus' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'changeReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if ($data['startsAt'] && $data['endsAt'] && $data['endsAt'] < $data['startsAt']) {
            throw ValidationException::withMessages([
                'endsAt' => 'Tanggal akhir penugasan tidak boleh sebelum tanggal mulai.',
            ]);
        }

        $reason = trim($data['changeReason']);
        $attributes = [
            'teacher_profile_id' => $data['teacherProfileId'],
            'subject_id' => $data['subjectId'],
            'rombel_id' => $data['rombelId'],
            'academic_year_id' => $data['academicYearId'],
            'semester_id' => $data['semesterId'],
            'starts_at' => $data['startsAt'] ?: null,
            'ends_at' => $data['endsAt'] ?: null,
            'status' => $data['assignmentStatus'],
        ];

        try {
            $result = DB::transaction(function () use ($tenantId, $actor, $attributes, $reason) {
                Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

                $assignment = null;
                if ($this->editingAssignmentId !== '') {
                    $assignment = TeacherSubjectAssignment::query()
                        ->where('tenant_id', $tenantId)
                        ->whereKey($this->editingAssignmentId)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $teacher = TeacherProfile::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($attributes['teacher_profile_id'])
                    ->where(function ($profile) {
                        $profile->where(function ($asn) {
                            $asn->where('employment_type', 'ASN')->whereNotNull('nip')->whereRaw('LENGTH(nip) = 18');
                        })->orWhere(function ($honorer) {
                            $honorer->where('employment_type', 'HONORER')->whereNotNull('nik')->whereRaw('LENGTH(nik) = 16');
                        });
                    })
                    ->whereHas('user', function ($query) use ($tenantId) {
                        $query->where('tenant_id', $tenantId)
                            ->whereNull('deleted_at')
                            ->where('is_active', true)
                            ->has('roles', '=', 1)
                            ->whereHas('roles', fn ($roles) => $roles->where('name', 'GURU'));
                    })
                    ->lockForUpdate()
                    ->first();

                $subject = Subject::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($attributes['subject_id'])
                    ->lockForUpdate()
                    ->first();

                $rombel = Rombel::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($attributes['rombel_id'])
                    ->lockForUpdate()
                    ->first();

                $year = AcademicYear::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($attributes['academic_year_id'])
                    ->lockForUpdate()
                    ->first();

                $semester = Semester::query()
                    ->where('tenant_id', $tenantId)
                    ->whereKey($attributes['semester_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $teacher || ! $subject || ! $rombel || ! $year || ! $semester) {
                    throw ValidationException::withMessages([
                        'teacherProfileId' => 'Referensi Guru, mata pelajaran, rombel, tahun ajaran, atau semester tidak valid pada madrasah ini.',
                    ]);
                }

                if ((string) $rombel->academic_year_id !== (string) $year->id
                    || (string) $semester->academic_year_id !== (string) $year->id) {
                    throw ValidationException::withMessages([
                        'academicYearId' => 'Rombel dan semester harus berada pada tahun ajaran yang sama.',
                    ]);
                }

                $duplicate = TeacherSubjectAssignment::query()
                    ->where('tenant_id', $tenantId)
                    ->where('teacher_profile_id', $attributes['teacher_profile_id'])
                    ->where('subject_id', $attributes['subject_id'])
                    ->where('rombel_id', $attributes['rombel_id'])
                    ->where('academic_year_id', $attributes['academic_year_id'])
                    ->where('semester_id', $attributes['semester_id'])
                    ->when($assignment, fn ($query) => $query->where('id', '<>', $assignment->id))
                    ->lockForUpdate()
                    ->first();

                if ($duplicate) {
                    $samePayload = (string) $duplicate->status === $attributes['status']
                        && ($duplicate->starts_at?->format('Y-m-d') ?? null) === $attributes['starts_at']
                        && ($duplicate->ends_at?->format('Y-m-d') ?? null) === $attributes['ends_at'];

                    if (! $assignment && $samePayload) {
                        return ['assignment' => $duplicate, 'unchanged' => true];
                    }

                    throw ValidationException::withMessages([
                        'teacherProfileId' => 'Penugasan dengan kombinasi Guru, mata pelajaran, rombel, tahun ajaran, dan semester tersebut sudah tercatat. Edit riwayat yang ada, jangan membuat duplikat.',
                    ]);
                }

                if ($assignment) {
                    $before = $assignment->only([
                        'teacher_profile_id', 'subject_id', 'rombel_id',
                        'academic_year_id', 'semester_id', 'starts_at', 'ends_at', 'status',
                    ]);
                    $assignment->fill($attributes);
                    $assignment->save();

                    $after = $assignment->fresh()->only([
                        'teacher_profile_id', 'subject_id', 'rombel_id',
                        'academic_year_id', 'semester_id', 'starts_at', 'ends_at', 'status',
                    ]);
                    $after['reason'] = $reason;
                    app(AuditService::class)->log(
                        'TEACHER_ASSIGNMENT_UPDATE',
                        'TeacherSubjectAssignment',
                        $assignment->id,
                        $before,
                        $after
                    );

                    return ['assignment' => $assignment->fresh(), 'unchanged' => false];
                }

                $created = TeacherSubjectAssignment::create([
                    ...$attributes,
                    'created_by' => $actor->id,
                ]);

                $createdValues = $created->only([
                    'tenant_id', 'teacher_profile_id', 'subject_id', 'rombel_id',
                    'academic_year_id', 'semester_id', 'starts_at', 'ends_at', 'status',
                ]);
                $createdValues['reason'] = $reason;
                app(AuditService::class)->log(
                    'TEACHER_ASSIGNMENT_CREATE',
                    'TeacherSubjectAssignment',
                    $created->id,
                    null,
                    $createdValues
                );

                return ['assignment' => $created, 'unchanged' => false];
            }, 3);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateAssignment($exception)) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'teacherProfileId' => 'Penugasan ganda ditolak oleh constraint database. Muat ulang daftar sebelum mencoba kembali.',
            ]);
        }

        $this->showForm = false;
        $this->changeReason = '';
        $this->resetPage();
        session()->flash(
            'teacherAssignmentMessage',
            $result['unchanged']
                ? 'Permintaan duplikat dikenali sebagai pengulangan; data penugasan yang sama tetap satu.'
                : ($this->editingAssignmentId !== ''
                    ? 'Penugasan Guru berhasil diperbarui. Histori perubahan tercatat.'
                    : 'Penugasan Guru berhasil dibuat.')
        );
        $this->editingAssignmentId = '';
    }

    public function openDeactivateModal(string $assignmentId): void
    {
        $this->assertCanManage();
        TeacherSubjectAssignment::query()
            ->where('tenant_id', auth()->user()->tenant_id)
            ->whereKey($assignmentId)
            ->firstOrFail();

        $this->deactivatingAssignmentId = $assignmentId;
        $this->deactivationReason = '';
        $this->resetValidation();
        $this->showDeactivateModal = true;
    }

    public function deactivate(): void
    {
        $this->assertCanManage();
        $data = $this->validate([
            'deactivatingAssignmentId' => ['required', 'uuid'],
            'deactivationReason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $tenantId = (string) auth()->user()->tenant_id;
        $reason = trim($data['deactivationReason']);
        $assignmentId = (string) $data['deactivatingAssignmentId'];

        DB::transaction(function () use ($tenantId, $assignmentId, $reason) {
            Tenant::query()->whereKey($tenantId)->lockForUpdate()->firstOrFail();

            $assignment = TeacherSubjectAssignment::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($assignmentId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($assignment->status === 'INACTIVE') {
                return;
            }

            $before = $assignment->only([
                'teacher_profile_id', 'subject_id', 'rombel_id',
                'academic_year_id', 'semester_id', 'starts_at', 'ends_at', 'status',
            ]);

            $assignment->update(['status' => 'INACTIVE']);
            $after = $assignment->fresh()->only([
                'teacher_profile_id', 'subject_id', 'rombel_id',
                'academic_year_id', 'semester_id', 'starts_at', 'ends_at', 'status',
            ]);
            $after['reason'] = $reason;

            app(AuditService::class)->log(
                'TEACHER_ASSIGNMENT_DEACTIVATE',
                'TeacherSubjectAssignment',
                $assignment->id,
                $before,
                $after
            );
        }, 3);

        $this->showDeactivateModal = false;
        $this->deactivatingAssignmentId = '';
        $this->deactivationReason = '';
        session()->flash('teacherAssignmentMessage', 'Penugasan dinonaktifkan. Record dan histori akademik tetap dipertahankan.');
    }

    private function isDuplicateAssignment(QueryException $exception): bool
    {
        $driverCode = $exception->errorInfo[1] ?? null;
        $sqlState = $exception->errorInfo[0] ?? null;
        $message = strtolower((string) ($exception->errorInfo[2] ?? $exception->getMessage()));

        return (int) $driverCode === 1062
            || $sqlState === '23000' && str_contains($message, 'teacher_subject_assignments')
            || str_contains($message, 'teacher_subject_assignment_unique');
    }

    private function assertCanManage(): void
    {
        $user = auth()->user();

        abort_unless(
            $user && $user->tenant_id && $user->is_active
                && $user->roles()->count() === 1
                && $user->roles()->where('name', 'ADMIN_MADRASAH')->exists()
                && $user->hasPermission('academic.manage'),
            403,
            'Hanya Admin Madrasah aktif dengan satu role dan permission academic.manage yang dapat mengelola penugasan Guru.'
        );
    }

    public function render()
    {
        $tenantId = (string) auth()->user()->tenant_id;

        $assignments = TeacherSubjectAssignment::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'teacher:id,full_name',
                'subject:id,name,code',
                'rombel:id,name',
                'academicYear:id,name',
                'semester:id,term',
            ])
            ->when($this->statusFilter !== 'ALL', fn ($query) => $query->where('status', $this->statusFilter))
            ->when(trim($this->search) !== '', function ($query) {
                $term = trim($this->search);
                $query->where(function ($nested) use ($term) {
                    $nested->whereHas('teacher', fn ($q) => $q->where('full_name', 'like', "%{$term}%"))
                        ->orWhereHas('subject', fn ($q) => $q->where('name', 'like', "%{$term}%")
                            ->orWhere('code', 'like', "%{$term}%"))
                        ->orWhereHas('rombel', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('academicYear', fn ($q) => $q->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        $teachers = TeacherProfile::query()
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->where(function ($profile) {
                $profile->where(fn ($asn) => $asn->where('employment_type', 'ASN')->whereNotNull('nip')->whereRaw('LENGTH(nip) = 18'))
                    ->orWhere(fn ($honorer) => $honorer->where('employment_type', 'HONORER')->whereNotNull('nik')->whereRaw('LENGTH(nik) = 16'));
            })
            ->whereHas('user', fn ($query) => $query->where('tenant_id', $tenantId)
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->has('roles', '=', 1)
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'GURU')))
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $subjects = Subject::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $academicYears = AcademicYear::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('name')
            ->get(['id', 'name']);

        $semesters = Semester::query()
            ->where('tenant_id', $tenantId)
            ->when($this->academicYearId !== '', fn ($query) => $query->where('academic_year_id', $this->academicYearId))
            ->orderBy('term')
            ->get(['id', 'academic_year_id', 'term']);

        $rombels = Rombel::query()
            ->where('tenant_id', $tenantId)
            ->when($this->academicYearId !== '', fn ($query) => $query->where('academic_year_id', $this->academicYearId))
            ->orderBy('name')
            ->get(['id', 'academic_year_id', 'name']);

        return view('livewire.admin.teacher-subject-assignment-management', compact(
            'assignments', 'teachers', 'subjects', 'academicYears', 'semesters', 'rombels'
        ))->layout('layouts.app');
    }
}
