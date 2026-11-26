<?php

namespace App\Livewire\Admin;

use App\Models\AcademicYear;
use App\Models\MultiCurriculumStructure;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantCurriculumSubjectMapping;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TeacherSubjectAssignmentManagement extends Component
{
    public string $selectedTeacherProfileId = '';
    public string $selectedSubjectId = '';
    public array $selectedSubjectIds = [];
    private bool $bulkCreating = false;
    public string $selectedRombelId = '';
    public string $selectedSemesterId = '';
    public string $selectedRegistrationId = '';
    public string $startsAt = '';
    public string $endsAt = '';

    private function authorizeAcademicManagement(): void
    {
        $user = auth()->user();
        $roles = $user?->roles()->pluck('name')->all() ?? [];

        abort_unless($user && $user->tenant_id && count($roles) === 1, 403, 'Akses penugasan ditolak.');

        if ($roles[0] === 'GURU') {
            app(\\App\\Support\\Security\\TeacherPortalAccess::class)->profile($user);
            return;
        }

        abort_unless(
            $roles[0] === 'ADMIN_MADRASAH' && $user->hasPermission('academic.manage'),
            403,
            'Akses ditolak. Halaman ini hanya tersedia untuk Guru dan Admin Madrasah berizin.'
        );
    }

    private function isTeacherUser(): bool
    {
        $user = auth()->user();
        return $user !== null && $user->roles()->count() === 1 && $user->roles()->where('name', 'GURU')->exists();
    }

    private function authenticatedTeacherProfile(): TeacherProfile
    {
        abort_unless($this->isTeacherUser(), 403, 'Aksi ini hanya tersedia untuk Guru.');
        return app(\\App\\Support\\Security\\TeacherPortalAccess::class)->profile(auth()->user());
    }

    private function activeCurriculumRegistration(string $tenantId): ?TenantCurriculumRegistration
    {
        $registrations = TenantCurriculumRegistration::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->with('version.source')
            ->orderByDesc('registered_at')
            ->get();

        return $registrations->count() === 1 ? $registrations->first() : null;
    }

    public function mount(): void
    {
        $this->authorizeAcademicManagement();
        $this->startsAt = now()->toDateString();
        $this->endsAt = now()->addMonths(6)->toDateString();

        if ($this->isTeacherUser()) {
            $profile = $this->authenticatedTeacherProfile();
            $this->selectedTeacherProfileId = (string) $profile->id;
            $registration = $this->activeCurriculumRegistration((string) auth()->user()->tenant_id);
            $this->selectedRegistrationId = (string) ($registration?->id ?? '');
        }
    }

    public function updatedSelectedRombelId(): void
    {
        $this->selectedSemesterId = '';
    }

    public function createAssignment(): void
    {
        $this->authorizeAcademicManagement();
        $tenantId = (string) auth()->user()->tenant_id;
        if ($this->isTeacherUser()) {
            $profile = $this->authenticatedTeacherProfile();
            $this->selectedTeacherProfileId = (string) $profile->id;
            $registration = $this->activeCurriculumRegistration($tenantId);
            if (!$registration) {
                throw ValidationException::withMessages([
                    'selectedRegistrationId' => 'Harus ada tepat satu versi kurikulum aktif yang terdaftar. Minta Admin memeriksa Registry Kurikulum.',
                ]);
            }
            $this->selectedRegistrationId = (string) $registration->id;
        }

        $this->validate([
            'selectedTeacherProfileId' => ['required', 'uuid', Rule::exists('teacher_profiles', 'id')->where('tenant_id', $tenantId)],
            'selectedSubjectId' => ['required', 'uuid', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'selectedRombelId' => ['required', 'uuid', Rule::exists('rombels', 'id')->where('tenant_id', $tenantId)],
            'selectedSemesterId' => ['required', 'uuid', Rule::exists('semesters', 'id')->where('tenant_id', $tenantId)],
            'selectedRegistrationId' => ['required', 'uuid', Rule::exists('tenant_curriculum_registrations', 'id')->where('tenant_id', $tenantId)],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after_or_equal:startsAt'],
        ]);

        DB::transaction(function () use ($tenantId): void {
            $teacher = TeacherProfile::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($this->selectedTeacherProfileId)
                ->with('user.roles')
                ->lockForUpdate()
                ->firstOrFail();

            if (!$teacher->user
                || $teacher->user->tenant_id !== $tenantId
                || $teacher->user->roles->count() !== 1
                || !$teacher->user->roles->contains('name', 'GURU')
                || !(bool) $teacher->user->is_active
                || $teacher->user->deleted_at !== null) {
                throw ValidationException::withMessages([
                    'selectedTeacherProfileId' => 'Penugasan hanya dapat diberikan kepada akun Guru aktif dengan tepat satu role GURU.',
                ]);
            }

            $subject = Subject::query()->where('tenant_id', $tenantId)->whereKey($this->selectedSubjectId)->firstOrFail();
            $rombel = Rombel::query()->where('tenant_id', $tenantId)->whereKey($this->selectedRombelId)
                ->with(['gradeLevel.phase', 'academicYear'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($rombel->status !== 'ACTIVE' || !$rombel->gradeLevel || !$rombel->gradeLevel->phase) {
                throw ValidationException::withMessages([
                    'selectedRombelId' => 'Rombel harus aktif dan memiliki tingkat kelas serta fase yang valid.',
                ]);
            }

            $semester = Semester::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($this->selectedSemesterId)
                ->firstOrFail();

            if ($semester->academic_year_id !== $rombel->academic_year_id) {
                throw ValidationException::withMessages([
                    'selectedSemesterId' => 'Semester harus berada pada tahun ajaran yang sama dengan rombel.',
                ]);
            }

            $registration = TenantCurriculumRegistration::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($this->selectedRegistrationId)
                ->where('status', 'ACTIVE')
                ->with('version.source')
                ->lockForUpdate()
                ->firstOrFail();

            $gradeNumber = (int) $rombel->gradeLevel->level;
            $phaseCode = strtoupper(str_replace('FASE_', '', (string) $rombel->gradeLevel->phase->code));
            if (!in_array($phaseCode, ['A', 'B', 'C'], true) || $gradeNumber < 1 || $gradeNumber > 6) {
                throw ValidationException::withMessages([
                    'selectedRombelId' => 'Fase/kelas rombel belum cocok dengan struktur Fase A, B, C kelas MI 1–6.',
                ]);
            }

            $phaseNode = MultiCurriculumStructure::query()
                ->where('tenant_id', $registration->curriculum_owner_id)
                ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                ->where('node_type', 'PHASE')
                ->where('code', 'FASE_' . $phaseCode)
                ->where('status', 'ACTIVE')
                ->first();

            $gradeNode = MultiCurriculumStructure::query()
                ->where('tenant_id', $registration->curriculum_owner_id)
                ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                ->where('node_type', 'GRADE_LEVEL')
                ->where('code', 'MI_' . $gradeNumber)
                ->where('parent_id', $phaseNode?->id)
                ->where('status', 'ACTIVE')
                ->first();

            if (!$phaseNode || !$gradeNode) {
                throw ValidationException::withMessages([
                    'selectedRombelId' => 'Fase atau kelas belum tersedia pada versi kurikulum yang didaftarkan.',
                ]);
            }

            if ($subject->subject_category === 'MULOK') {
                // Every tenant-defined Mulok subject maps to the official phase-level
                // Muatan Lokal node; its local title remains independent and editable.
                $localContentNode = MultiCurriculumStructure::query()
                    ->where('tenant_id', $registration->curriculum_owner_id)
                    ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                    ->where('node_type', 'SUBJECT')
                    ->where('code', 'FASE_' . $phaseCode . '_MUATAN_LOKAL')
                    ->where('parent_id', $phaseNode->id)
                    ->where('status', 'ACTIVE')
                    ->where('is_official', true)
                    ->where('is_editable', false)
                    ->with('source')
                    ->first();

                if (!$localContentNode
                    || !$localContentNode->source
                    || !in_array($localContentNode->source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true)) {
                    throw ValidationException::withMessages([
                        'selectedSubjectId' => 'Node resmi Muatan Lokal untuk fase rombel ini belum tersedia atau sumbernya belum diverifikasi.',
                    ]);
                }

                $subjectMapping = TenantCurriculumSubjectMapping::query()->firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'subject_id' => $subject->id,
                        'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                        'multi_curriculum_structure_id' => $localContentNode->id,
                    ],
                    [
                        'tenant_curriculum_registration_id' => $registration->id,
                        'curriculum_owner_id' => $registration->curriculum_owner_id,
                        'status' => 'ACTIVE',
                        'effective_from' => $localContentNode->effective_from,
                        'effective_until' => $localContentNode->effective_until,
                        'created_by' => auth()->id(),
                        'metadata' => [
                            'local_subject_code' => $subject->code,
                            'official_subject_code' => 'MUATAN_LOKAL',
                            'mapping_method' => 'TENANT_DEFINED_LOCAL_CONTENT',
                        ],
                    ]
                )->load('curriculumStructure.source');

                if ($subjectMapping->status !== 'ACTIVE') {
                    throw ValidationException::withMessages([
                        'selectedSubjectId' => 'Pemetaan Mulok untuk versi kurikulum ini pernah dinonaktifkan. Audit histori pemetaan sebelum mengaktifkannya kembali.',
                    ]);
                }
            } else {
                $subjectMapping = TenantCurriculumSubjectMapping::query()
                    ->where('tenant_id', $tenantId)
                    ->where('subject_id', $subject->id)
                    ->where('tenant_curriculum_registration_id', $registration->id)
                    ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                    ->where('curriculum_owner_id', $registration->curriculum_owner_id)
                    ->where('status', 'ACTIVE')
                    ->with('curriculumStructure.source')
                    ->get()
                    ->first(function (TenantCurriculumSubjectMapping $mapping) use ($phaseNode): bool {
                        return $mapping->curriculumStructure
                            && $mapping->curriculumStructure->node_type === 'SUBJECT'
                            && $mapping->curriculumStructure->parent_id === $phaseNode->id;
                    });
            }

            if (!$subjectMapping || !$subjectMapping->curriculumStructure) {
                throw ValidationException::withMessages([
                    'selectedSubjectId' => 'Mata pelajaran belum memiliki pemetaan kurikulum yang sah untuk fase rombel ini.',
                ]);
            }

            if (!$subjectMapping->curriculumStructure->is_official
                || $subjectMapping->curriculumStructure->is_editable
                || !$subjectMapping->curriculumStructure->source
                || !in_array($subjectMapping->curriculumStructure->source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true)) {
                throw ValidationException::withMessages([
                    'selectedSubjectId' => 'Node mata pelajaran resmi atau provenance sumbernya belum memenuhi syarat.',
                ]);
            }

            $source = $registration->version?->source;
            if (!$source || !in_array($source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true)) {
                throw ValidationException::withMessages([
                    'selectedRegistrationId' => 'Sumber versi kurikulum belum melewati verifikasi metadata.',
                ]);
            }

            $duplicate = TeacherSubjectAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_profile_id', $teacher->id)
                ->where('subject_id', $subject->id)
                ->where('rombel_id', $rombel->id)
                ->where('academic_year_id', $rombel->academic_year_id)
                ->where('semester_id', $semester->id)
                ->where('status', 'ACTIVE')
                ->whereDate('starts_at', '<=', $this->endsAt)
                ->whereDate('ends_at', '>=', $this->startsAt)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'selectedTeacherProfileId' => 'Penugasan aktif yang sama sudah tersedia untuk Guru, mapel, rombel, tahun ajaran, dan semester ini.',
                ]);
            }

            $contextSnapshot = [
                'registration_id' => $registration->id,
                'curriculum_owner_id' => $registration->curriculum_owner_id,
                'curriculum_version_id' => $registration->multi_curriculum_version_id,
                'curriculum_version_code' => $registration->version?->version_code,
                'curriculum_version_title' => $registration->version?->title,
                'curriculum_source_document' => $source->document_number,
                'curriculum_source_verification' => $source->verification_status,
                'phase' => [
                    'id' => $phaseNode->id,
                    'code' => $phaseNode->code,
                    'title' => $phaseNode->title,
                ],
                'grade' => [
                    'id' => $gradeNode->id,
                    'code' => $gradeNode->code,
                    'title' => $gradeNode->title,
                    'level' => $gradeNumber,
                ],
                'subject' => [
                    'local_id' => $subject->id,
                    'local_code' => $subject->code,
                    'local_name' => $subject->name,
                    'official_structure_id' => $subjectMapping->curriculumStructure->id,
                    'official_code' => $subjectMapping->curriculumStructure->metadata['subject_code'] ?? $subjectMapping->curriculumStructure->code,
                    'official_name' => $subjectMapping->curriculumStructure->title,
                ],
                'academic_period' => [
                    'academic_year_id' => $rombel->academic_year_id,
                    'academic_year_name' => $rombel->academicYear?->name,
                    'semester_id' => $semester->id,
                    'semester_name' => $semester->name,
                ],
                'captured_at' => now()->toISOString(),
            ];

            $assignment = TeacherSubjectAssignment::query()->create([
                'tenant_id' => $tenantId,
                'teacher_profile_id' => $teacher->id,
                'subject_id' => $subject->id,
                'rombel_id' => $rombel->id,
                'academic_year_id' => $rombel->academic_year_id,
                'semester_id' => $semester->id,
                'starts_at' => $this->startsAt,
                'ends_at' => $this->endsAt,
                'status' => 'ACTIVE',
                'tenant_curriculum_registration_id' => $registration->id,
                'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                'curriculum_owner_id' => $registration->curriculum_owner_id,
                'curriculum_context_snapshot' => $contextSnapshot,
            ]);

            app(AuditService::class)->log(
                'TEACHER_SUBJECT_ASSIGNMENT_CREATED',
                'TeacherSubjectAssignment',
                $assignment->id,
                null,
                [
                    'teacher_profile_id' => $teacher->id,
                    'subject_id' => $subject->id,
                    'rombel_id' => $rombel->id,
                    'academic_year_id' => $rombel->academic_year_id,
                    'semester_id' => $semester->id,
                    'curriculum_context_snapshot' => $contextSnapshot,
                ]
            );
        }, 3);

        $this->reset(['selectedTeacherProfileId', 'selectedSubjectId', 'selectedRombelId', 'selectedSemesterId']);
        if (!$this->bulkCreating) {
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Penugasan Guru berhasil disimpan dengan versi kurikulum yang dipatok.']);
        }
    }

    /**
     * Create several subject assignments for one teacher and rombel atomically.
     * Every subject is still validated by the canonical single-assignment path,
     * including tenant, phase, curriculum provenance, active teacher and overlap checks.
     */
    public function createBulkAssignments(): void
    {
        $this->authorizeAcademicManagement();
        $tenantId = (string) auth()->user()->tenant_id;
        if ($this->isTeacherUser()) {
            $profile = $this->authenticatedTeacherProfile();
            $this->selectedTeacherProfileId = (string) $profile->id;
            $registration = $this->activeCurriculumRegistration($tenantId);
            if (!$registration) {
                throw ValidationException::withMessages([
                    'selectedRegistrationId' => 'Harus ada tepat satu versi kurikulum aktif yang terdaftar. Minta Admin memeriksa Registry Kurikulum.',
                ]);
            }
            $this->selectedRegistrationId = (string) $registration->id;
        }

        $this->validate([
            'selectedTeacherProfileId' => ['required', 'uuid', Rule::exists('teacher_profiles', 'id')->where('tenant_id', $tenantId)],
            'selectedSubjectIds' => ['required', 'array', 'min:1'],
            'selectedSubjectIds.*' => ['required', 'uuid', 'distinct', Rule::exists('subjects', 'id')->where('tenant_id', $tenantId)],
            'selectedRombelId' => ['required', 'uuid', Rule::exists('rombels', 'id')->where('tenant_id', $tenantId)],
            'selectedSemesterId' => ['required', 'uuid', Rule::exists('semesters', 'id')->where('tenant_id', $tenantId)],
            'selectedRegistrationId' => ['required', 'uuid', Rule::exists('tenant_curriculum_registrations', 'id')->where('tenant_id', $tenantId)],
            'startsAt' => ['required', 'date'],
            'endsAt' => ['required', 'date', 'after_or_equal:startsAt'],
        ]);

        $teacherId = $this->selectedTeacherProfileId;
        $rombelId = $this->selectedRombelId;
        $semesterId = $this->selectedSemesterId;
        $registrationId = $this->selectedRegistrationId;
        $startsAt = $this->startsAt;
        $endsAt = $this->endsAt;
        $subjectIds = array_values(array_unique($this->selectedSubjectIds));
        $createdCount = count($subjectIds);

        $this->bulkCreating = true;
        try {
            DB::transaction(function () use ($teacherId, $rombelId, $semesterId, $registrationId, $startsAt, $endsAt, $subjectIds): void {
                foreach ($subjectIds as $subjectId) {
                    $this->selectedTeacherProfileId = $teacherId;
                    $this->selectedSubjectId = $subjectId;
                    $this->selectedRombelId = $rombelId;
                    $this->selectedSemesterId = $semesterId;
                    $this->selectedRegistrationId = $registrationId;
                    $this->startsAt = $startsAt;
                    $this->endsAt = $endsAt;
                    $this->createAssignment();
                }
            }, 3);
        } finally {
            $this->bulkCreating = false;
        }

        $this->reset([
            'selectedTeacherProfileId',
            'selectedSubjectId',
            'selectedSubjectIds',
            'selectedRombelId',
            'selectedSemesterId',
        ]);
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $createdCount . ' penugasan Guru berhasil disimpan sekaligus.',
        ]);
    }

    public function render()
    {
        $this->authorizeAcademicManagement();
        $tenantId = (string) auth()->user()->tenant_id;
        $isTeacher = $this->isTeacherUser();
        if ($isTeacher) {
            $profile = $this->authenticatedTeacherProfile();
            $this->selectedTeacherProfileId = (string) $profile->id;
            $registration = $this->activeCurriculumRegistration($tenantId);
            $this->selectedRegistrationId = (string) ($registration?->id ?? '');
        }

        $teachers = $isTeacher
            ? TeacherProfile::query()->where('tenant_id', $tenantId)->whereKey($this->selectedTeacherProfileId)->get()
            : TeacherProfile::query()
                ->where('tenant_id', $tenantId)
                ->with('user.roles')
                ->orderBy('full_name')
                ->get()
                ->filter(fn (TeacherProfile $profile) => $profile->user
                    && $profile->user->tenant_id === $tenantId
                    && $profile->user->roles->count() === 1
                    && $profile->user->roles->contains('name', 'GURU'))
                ->values();

        $subjects = Subject::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->where('code', '!=', 'MUATAN_LOKAL')
            ->orderBy('name')
            ->get();
        $rombels = Rombel::query()->where('tenant_id', $tenantId)->where('status', 'ACTIVE')->with(['gradeLevel.phase', 'academicYear'])->orderBy('name')->get();
        $selectedRombel = $this->selectedRombelId !== ''
            ? $rombels->firstWhere('id', $this->selectedRombelId)
            : null;
        $semesters = Semester::query()
            ->where('tenant_id', $tenantId)
            ->when($selectedRombel, fn ($query) => $query->where('academic_year_id', $selectedRombel->academic_year_id))
            ->with('academicYear:id,name')
            ->orderByDesc(AcademicYear::select('name')->whereColumn('academic_years.id', 'semesters.academic_year_id'))
            ->orderByRaw("CASE WHEN term = 'GANJIL' THEN 0 ELSE 1 END")
            ->get();
        $registrations = TenantCurriculumRegistration::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->with('version.source')
            ->orderByDesc('registered_at')
            ->get();

        $assignments = TeacherSubjectAssignment::query()
            ->where('tenant_id', $tenantId)
            ->when($isTeacher, fn ($query) => $query->where('teacher_profile_id', $this->selectedTeacherProfileId))
            ->with(['teacher.user', 'subject', 'rombel.gradeLevel.phase', 'academicYear', 'semester', 'curriculumVersion'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.admin.teacher-subject-assignment-management', compact(
            'teachers',
            'subjects',
            'rombels',
            'semesters',
            'registrations',
            'assignments',
            'isTeacher'
        ))->layout($isTeacher ? 'layouts.teacher' : 'layouts.app');
    }
}
