<?php

namespace App\Livewire\Teacher;

use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\TeacherSubjectAssignment;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantLearningOutcome;
use App\Services\AuditService;
use App\Services\TeacherPortal\TeacherPortalDataService;
use App\Support\Security\TeacherPortalAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TeacherMaterialManagement extends Component
{
    public string $selectedAssignmentId = '';
    public string $materialCode = '';
    public string $materialName = '';
    public string $materialDescription = '';
    public string $selectedMaterialOutcomeId = '';

    public string $selectedMaterialId = '';
    public string $selectedParentTopicId = '';
    public string $topicCode = '';
    public string $topicTitle = '';
    public string $topicDescription = '';
    public string $selectedTopicOutcomeId = '';

    private function teacherProfile()
    {
        $user = auth()->user();
        abort_unless($user && $user->hasPermission('curriculum.manage'), 403, 'Akun Guru tidak memiliki izin mengelola materi.');
        return app(TeacherPortalAccess::class)->profile($user);
    }

    public function mount(): void
    {
        $this->teacherProfile();
    }

    public function updatedSelectedAssignmentId(): void
    {
        $this->reset([
            'selectedMaterialOutcomeId',
            'selectedMaterialId',
            'selectedParentTopicId',
            'selectedTopicOutcomeId',
        ]);
    }

    private function activeAssignment(string $assignmentId): TeacherSubjectAssignment
    {
        $user = auth()->user();
        $profile = $this->teacherProfile();

        return TeacherSubjectAssignment::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('teacher_profile_id', $profile->id)
            ->whereKey($assignmentId)
            ->where('status', 'ACTIVE')
            ->whereDate('starts_at', '<=', now()->toDateString())
            ->whereDate('ends_at', '>=', now()->toDateString())
            ->with(['subject', 'rombel.gradeLevel.phase', 'academicYear', 'semester', 'curriculumRegistration'])
            ->firstOrFail();
    }

    public function createMaterial(): void
    {
        $this->teacherProfile();
        $user = auth()->user();
        $tenantId = (string) $user->tenant_id;

        $this->validate([
            'selectedAssignmentId' => ['required', 'uuid'],
            'materialCode' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'materialName' => ['required', 'string', 'max:255'],
            'materialDescription' => ['nullable', 'string', 'max:12000'],
            'selectedMaterialOutcomeId' => ['nullable', 'uuid'],
        ]);

        DB::transaction(function () use ($user, $tenantId): void {
            $assignment = $this->activeAssignment($this->selectedAssignmentId);
            $registration = TenantCurriculumRegistration::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($assignment->tenant_curriculum_registration_id)
                ->where('multi_curriculum_version_id', $assignment->multi_curriculum_version_id)
                ->where('curriculum_owner_id', $assignment->curriculum_owner_id)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->firstOrFail();

            $subjectStructureId = $assignment->curriculum_context_snapshot['subject']['official_structure_id'] ?? null;
            if (!$subjectStructureId || !$assignment->rombel?->gradeLevel?->phase) {
                throw ValidationException::withMessages([
                    'selectedAssignmentId' => 'Penugasan belum memiliki konteks fase, kelas, dan mapel resmi yang lengkap.',
                ]);
            }

            $outcome = null;
            if ($this->selectedMaterialOutcomeId !== '') {
                $outcome = TenantLearningOutcome::query()
                    ->where('tenant_id', $tenantId)
                    ->where('teacher_subject_assignment_id', $assignment->id)
                    ->where('tenant_curriculum_registration_id', $registration->id)
                    ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                    ->where('curriculum_owner_id', $registration->curriculum_owner_id)
                    ->whereKey($this->selectedMaterialOutcomeId)
                    ->where('is_current', true)
                    ->where('status', 'PUBLISHED')
                    ->firstOrFail();
            }

            $code = strtoupper(trim($this->materialCode));
            if (Material::query()->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->where('material_code', $code)
                ->where('version_code', '1')
                ->exists()) {
                throw ValidationException::withMessages([
                    'materialCode' => 'Kode materi sudah digunakan pada penugasan ini.',
                ]);
            }

            $material = Material::query()->create([
                'tenant_id' => $tenantId,
                'curriculum_id' => null,
                'phase_id' => $assignment->rombel->gradeLevel->phase_id,
                'subject_id' => $assignment->subject_id,
                'grade_level_id' => $assignment->rombel->grade_level_id,
                'element_id' => null,
                'learning_outcome_id' => null,
                'learning_objective_id' => null,
                'learning_path_id' => null,
                'name' => trim($this->materialName),
                'description' => trim($this->materialDescription),
                'status' => 'DRAFT',
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'material_code' => $code,
                'version_code' => '1',
                'teacher_subject_assignment_id' => $assignment->id,
                'tenant_curriculum_registration_id' => $registration->id,
                'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                'curriculum_owner_id' => $registration->curriculum_owner_id,
                'academic_year_id' => $assignment->academic_year_id,
                'semester_id' => $assignment->semester_id,
                'multi_curriculum_structure_id' => $subjectStructureId,
                'tenant_learning_outcome_id' => $outcome?->id,
                'effective_from' => $assignment->starts_at,
                'effective_until' => $assignment->ends_at,
                'is_current' => true,
                'supersedes_id' => null,
                'metadata' => [
                    'assignment_snapshot' => $assignment->curriculum_context_snapshot,
                    'created_by_teacher' => $user->id,
                ],
            ]);

            app(AuditService::class)->log(
                'TEACHER_MATERIAL_CREATED',
                'Material',
                $material->id,
                null,
                [
                    'material_code' => $material->material_code,
                    'teacher_subject_assignment_id' => $assignment->id,
                    'subject_id' => $assignment->subject_id,
                    'tenant_learning_outcome_id' => $outcome?->id,
                ]
            );
        }, 3);

        $this->reset(['materialCode', 'materialName', 'materialDescription', 'selectedMaterialOutcomeId']);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Materi pembelajaran berhasil disimpan sebagai draft.']);
    }

    public function createTopic(): void
    {
        $this->teacherProfile();
        $user = auth()->user();
        $tenantId = (string) $user->tenant_id;

        $this->validate([
            'selectedAssignmentId' => ['required', 'uuid'],
            'selectedMaterialId' => ['required', 'uuid'],
            'selectedParentTopicId' => ['nullable', 'uuid'],
            'topicCode' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._-]+$/'],
            'topicTitle' => ['required', 'string', 'max:255'],
            'topicDescription' => ['nullable', 'string', 'max:12000'],
            'selectedTopicOutcomeId' => ['nullable', 'uuid'],
        ]);

        DB::transaction(function () use ($user, $tenantId): void {
            $assignment = $this->activeAssignment($this->selectedAssignmentId);
            $material = Material::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->whereKey($this->selectedMaterialId)
                ->where('status', '!=', 'ARCHIVED')
                ->lockForUpdate()
                ->firstOrFail();

            $registration = TenantCurriculumRegistration::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($material->tenant_curriculum_registration_id)
                ->where('status', 'ACTIVE')
                ->firstOrFail();

            if ($this->selectedParentTopicId !== '') {
                MaterialTopic::query()
                    ->where('tenant_id', $tenantId)
                    ->where('material_id', $material->id)
                    ->whereKey($this->selectedParentTopicId)
                    ->firstOrFail();
            }

            $outcome = null;
            if ($this->selectedTopicOutcomeId !== '') {
                $outcome = TenantLearningOutcome::query()
                    ->where('tenant_id', $tenantId)
                    ->where('teacher_subject_assignment_id', $assignment->id)
                    ->where('tenant_curriculum_registration_id', $registration->id)
                    ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                    ->where('curriculum_owner_id', $registration->curriculum_owner_id)
                    ->whereKey($this->selectedTopicOutcomeId)
                    ->where('is_current', true)
                    ->where('status', 'PUBLISHED')
                    ->firstOrFail();
            }

            $code = strtoupper(trim($this->topicCode));
            if (MaterialTopic::query()->where('tenant_id', $tenantId)
                ->where('material_id', $material->id)
                ->where('code', $code)
                ->where('version_code', '1')
                ->exists()) {
                throw ValidationException::withMessages([
                    'topicCode' => 'Kode topik sudah digunakan pada materi ini.',
                ]);
            }

            $topic = MaterialTopic::query()->create([
                'tenant_id' => $tenantId,
                'material_id' => $material->id,
                'parent_id' => $this->selectedParentTopicId ?: null,
                'code' => $code,
                'title' => trim($this->topicTitle),
                'description' => trim($this->topicDescription),
                'version_code' => '1',
                'effective_from' => $material->effective_from,
                'effective_until' => $material->effective_until,
                'status' => 'DRAFT',
                'created_by' => $user->id,
                'metadata' => ['tenant_learning_outcome_id' => $outcome?->id],
                'teacher_subject_assignment_id' => $assignment->id,
                'tenant_curriculum_registration_id' => $registration->id,
                'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                'curriculum_owner_id' => $registration->curriculum_owner_id,
                'tenant_learning_outcome_id' => $outcome?->id,
            ]);

            app(AuditService::class)->log(
                'TEACHER_MATERIAL_TOPIC_CREATED',
                'MaterialTopic',
                $topic->id,
                null,
                [
                    'material_id' => $material->id,
                    'topic_code' => $topic->code,
                    'teacher_subject_assignment_id' => $assignment->id,
                    'tenant_learning_outcome_id' => $outcome?->id,
                ]
            );
        }, 3);

        $this->reset(['selectedParentTopicId', 'topicCode', 'topicTitle', 'topicDescription', 'selectedTopicOutcomeId']);
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Topik materi berhasil disimpan sebagai draft.']);
    }

    public function publishMaterial(string $materialId): void
    {
        $user = auth()->user();
        $tenantId = (string) $user->tenant_id;
        $this->teacherProfile();

        $material = Material::query()
            ->where('tenant_id', $tenantId)
            ->where('teacher_subject_assignment_id', $this->selectedAssignmentId)
            ->whereKey($materialId)
            ->with('learningOutcome')
            ->firstOrFail();

        $assignment = $this->activeAssignment($material->teacher_subject_assignment_id);
        if ($material->tenant_learning_outcome_id
            && (!$material->learningOutcome
                || $material->learningOutcome->status !== 'PUBLISHED'
                || !$material->learningOutcome->is_current)) {
            throw ValidationException::withMessages([
                'selectedMaterialOutcomeId' => 'TP/ATP yang dipetakan ke materi tidak lagi terpublikasi atau bukan versi terkini.',
            ]);
        }
        $before = $material->only(['status', 'is_current']);
        $material->update(['status' => 'PUBLISHED', 'is_current' => true]);

        app(AuditService::class)->log(
            'TEACHER_MATERIAL_PUBLISHED',
            'Material',
            $material->id,
            $before,
            ['status' => 'PUBLISHED', 'teacher_subject_assignment_id' => $assignment->id]
        );

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Materi berhasil dipublikasikan untuk penugasan ini.']);
    }

    public function publishTopic(string $topicId): void
    {
        $user = auth()->user();
        $tenantId = (string) $user->tenant_id;
        $this->teacherProfile();

        $topic = MaterialTopic::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('material', fn ($query) => $query
                ->where('teacher_subject_assignment_id', $this->selectedAssignmentId)
                ->where('status', 'PUBLISHED'))
            ->whereKey($topicId)
            ->with(['material', 'learningOutcome'])
            ->firstOrFail();

        $this->activeAssignment($topic->teacher_subject_assignment_id);
        if ($topic->tenant_learning_outcome_id
            && (!$topic->learningOutcome
                || $topic->learningOutcome->status !== 'PUBLISHED'
                || !$topic->learningOutcome->is_current)) {
            throw ValidationException::withMessages([
                'selectedTopicOutcomeId' => 'TP/ATP yang dipetakan ke topik tidak lagi terpublikasi atau bukan versi terkini.',
            ]);
        }

        $before = $topic->only(['status']);
        $topic->update(['status' => 'PUBLISHED']);

        app(AuditService::class)->log(
            'TEACHER_MATERIAL_TOPIC_PUBLISHED',
            'MaterialTopic',
            $topic->id,
            $before,
            ['status' => 'PUBLISHED', 'material_id' => $topic->material_id]
        );

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Topik berhasil dipublikasikan.']);
    }

    public function render()
    {
        $user = auth()->user();
        $this->teacherProfile();
        $assignmentRows = app(TeacherPortalDataService::class)->assignmentOptions($user);

        if ($this->selectedAssignmentId === '' && $assignmentRows !== []) {
            $this->selectedAssignmentId = (string) $assignmentRows[0]['assignment']->id;
        }

        $selectedRow = collect($assignmentRows)->first(
            fn (array $row) => (string) $row['assignment']->id === $this->selectedAssignmentId
        );
        $assignment = $selectedRow['assignment'] ?? null;
        $context = $selectedRow['curriculum_context'] ?? [];
        $tenantId = (string) $user->tenant_id;

        $outcomes = $assignment
            ? TenantLearningOutcome::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->where('status', 'PUBLISHED')
                ->where('is_current', true)
                ->with('parentCp', 'parentTp')
                ->orderBy('outcome_type')
                ->orderBy('sequence_number')
                ->get()
            : collect();

        $materials = $assignment
            ? Material::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->with(['learningOutcome', 'topics.learningOutcome'])
                ->orderByDesc('created_at')
                ->get()
            : collect();

        return view('livewire.teacher.teacher-material-management', compact(
            'assignmentRows',
            'selectedRow',
            'assignment',
            'context',
            'outcomes',
            'materials'
        ))->layout('layouts.teacher');
    }
}
