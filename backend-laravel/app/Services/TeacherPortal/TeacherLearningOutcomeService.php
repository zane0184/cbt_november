<?php

namespace App\Services\TeacherPortal;

use App\Models\LearningOutcomeStructureMapping;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiLearningOutcome;
use App\Models\TeacherSubjectAssignment;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantLearningOutcome;
use App\Services\AuditService;
use App\Support\Security\TeacherPortalAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherLearningOutcomeService
{
    public function createTp(
        string $assignmentId,
        string $parentCpId,
        string $code,
        string $title,
        string $description,
        array $pancaCintaIds,
        array $pmPrincipleIds,
        array $pmFrameworkIds,
        array $graduateDimensionIds
    ): TenantLearningOutcome {
        $user = auth()->user();
        $profile = app(TeacherPortalAccess::class)->profile($user);
        abort_unless($user->hasPermission('curriculum.manage'), 403, 'Akun Guru tidak memiliki izin mengembangkan TP/ATP.');
        $tenantId = (string) $user->tenant_id;

        return DB::transaction(function () use (
            $user,
            $profile,
            $tenantId,
            $assignmentId,
            $parentCpId,
            $code,
            $title,
            $description,
            $pancaCintaIds,
            $pmPrincipleIds,
            $pmFrameworkIds,
            $graduateDimensionIds
        ): TenantLearningOutcome {
            $assignment = TeacherSubjectAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_profile_id', $profile->id)
                ->whereKey($assignmentId)
                ->where('status', 'ACTIVE')
                ->whereDate('starts_at', '<=', now()->toDateString())
                ->whereDate('ends_at', '>=', now()->toDateString())
                ->lockForUpdate()
                ->firstOrFail();

            $registration = $this->resolveRegistration($assignment, $tenantId);
            $subjectStructureId = $assignment->curriculum_context_snapshot['subject']['official_structure_id'] ?? null;

            if (!$subjectStructureId) {
                throw ValidationException::withMessages([
                    'assignment' => 'Snapshot penugasan tidak memiliki pemetaan mata pelajaran resmi.',
                ]);
            }

            $cp = MultiLearningOutcome::query()
                ->where('tenant_id', $registration->curriculum_owner_id)
                ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                ->where('multi_curriculum_structure_id', $subjectStructureId)
                ->whereKey($parentCpId)
                ->where('outcome_type', 'CP')
                ->where('status', 'ACTIVE')
                ->where('is_official', true)
                ->where('is_editable', false)
                ->with('source')
                ->firstOrFail();

            if (!$cp->source
                || $cp->source->verification_status !== 'ARTIFACT_CHECKSUM_VERIFIED'
                || !$cp->source->checksum_sha256
                || !$cp->source_checksum
                || !hash_equals((string) $cp->source->checksum_sha256, (string) $cp->source_checksum)) {
                throw ValidationException::withMessages([
                    'parentCpId' => 'CP induk belum terverifikasi melalui checksum artefak resmi. TP belum dapat dibuat.',
                ]);
            }

            $code = strtoupper(trim($code));
            if (TenantLearningOutcome::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->where('parent_cp_id', $cp->id)
                ->where('outcome_type', 'TP')
                ->where('code', $code)
                ->where('version_code', '1')
                ->exists()) {
                throw ValidationException::withMessages([
                    'code' => 'Kode TP sudah digunakan pada penugasan dan CP ini.',
                ]);
            }

            $mappingNodes = $this->validateFrameworkNodes(
                $registration,
                $pancaCintaIds,
                $pmPrincipleIds,
                $pmFrameworkIds,
                $graduateDimensionIds
            );

            $outcome = TenantLearningOutcome::query()->create([
                'tenant_id' => $tenantId,
                'tenant_curriculum_registration_id' => $registration->id,
                'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                'curriculum_owner_id' => $registration->curriculum_owner_id,
                'subject_id' => $assignment->subject_id,
                'multi_curriculum_structure_id' => $subjectStructureId,
                'teacher_subject_assignment_id' => $assignment->id,
                'academic_year_id' => $assignment->academic_year_id,
                'semester_id' => $assignment->semester_id,
                'parent_cp_id' => $cp->id,
                'parent_tp_id' => null,
                'outcome_type' => 'TP',
                'code' => $code,
                'title' => trim($title),
                'description' => trim($description),
                'sequence_number' => null,
                'version_code' => '1',
                'supersedes_id' => null,
                'is_current' => true,
                'status' => 'DRAFT',
                'author_user_id' => $user->id,
                'metadata' => [
                    'curriculum_version_code' => $registration->version?->version_code,
                    'cp_code' => $cp->code,
                    'created_from_assignment_snapshot' => true,
                ],
            ]);

            $this->persistFrameworkMappings($outcome, $registration, $mappingNodes, $user->id);

            app(AuditService::class)->log(
                'TEACHER_LEARNING_OUTCOME_CREATED',
                'TenantLearningOutcome',
                $outcome->id,
                null,
                [
                    'outcome_type' => 'TP',
                    'code' => $outcome->code,
                    'parent_cp_id' => $cp->id,
                    'teacher_subject_assignment_id' => $assignment->id,
                    'mapping_count' => count($mappingNodes),
                ]
            );

            return $outcome->load(['parentCp', 'structureMappings.structure']);
        }, 3);
    }

    public function createAtp(
        string $assignmentId,
        string $parentTpId,
        string $code,
        string $title,
        string $description,
        int $sequenceNumber
    ): TenantLearningOutcome {
        $user = auth()->user();
        $profile = app(TeacherPortalAccess::class)->profile($user);
        $tenantId = (string) $user->tenant_id;

        return DB::transaction(function () use (
            $user,
            $profile,
            $tenantId,
            $assignmentId,
            $parentTpId,
            $code,
            $title,
            $description,
            $sequenceNumber
        ): TenantLearningOutcome {
            $assignment = TeacherSubjectAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_profile_id', $profile->id)
                ->whereKey($assignmentId)
                ->where('status', 'ACTIVE')
                ->whereDate('starts_at', '<=', now()->toDateString())
                ->whereDate('ends_at', '>=', now()->toDateString())
                ->lockForUpdate()
                ->firstOrFail();

            $registration = $this->resolveRegistration($assignment, $tenantId);
            $parentTp = TenantLearningOutcome::query()
                ->where('tenant_id', $tenantId)
                ->where('tenant_curriculum_registration_id', $registration->id)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->whereKey($parentTpId)
                ->where('outcome_type', 'TP')
                ->where('is_current', true)
                ->whereIn('status', ['DRAFT', 'PUBLISHED'])
                ->with('structureMappings')
                ->lockForUpdate()
                ->firstOrFail();

            if ($parentTp->curriculum_owner_id !== $registration->curriculum_owner_id
                || $parentTp->multi_curriculum_version_id !== $registration->multi_curriculum_version_id) {
                throw ValidationException::withMessages([
                    'parentTpId' => 'TP induk tidak berasal dari versi kurikulum yang dipatok pada penugasan ini.',
                ]);
            }

            $code = strtoupper(trim($code));
            if (TenantLearningOutcome::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->where('parent_cp_id', $parentTp->parent_cp_id)
                ->where('parent_tp_id', $parentTp->id)
                ->where('outcome_type', 'ATP')
                ->where('code', $code)
                ->where('version_code', '1')
                ->exists()) {
                throw ValidationException::withMessages([
                    'code' => 'Kode ATP sudah digunakan pada TP ini.',
                ]);
            }

            $atp = TenantLearningOutcome::query()->create([
                'tenant_id' => $tenantId,
                'tenant_curriculum_registration_id' => $registration->id,
                'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                'curriculum_owner_id' => $registration->curriculum_owner_id,
                'subject_id' => $assignment->subject_id,
                'multi_curriculum_structure_id' => $parentTp->multi_curriculum_structure_id,
                'teacher_subject_assignment_id' => $assignment->id,
                'academic_year_id' => $assignment->academic_year_id,
                'semester_id' => $assignment->semester_id,
                'parent_cp_id' => $parentTp->parent_cp_id,
                'parent_tp_id' => $parentTp->id,
                'outcome_type' => 'ATP',
                'code' => $code,
                'title' => trim($title),
                'description' => trim($description),
                'sequence_number' => $sequenceNumber,
                'version_code' => '1',
                'supersedes_id' => null,
                'is_current' => true,
                'status' => 'DRAFT',
                'author_user_id' => $user->id,
                'metadata' => [
                    'parent_tp_code' => $parentTp->code,
                    'parent_cp_id' => $parentTp->parent_cp_id,
                    'inherited_framework_mappings' => true,
                ],
            ]);

            foreach ($parentTp->structureMappings as $mapping) {
                LearningOutcomeStructureMapping::query()->create([
                    'tenant_id' => $tenantId,
                    'tenant_learning_outcome_id' => $atp->id,
                    'tenant_curriculum_registration_id' => $registration->id,
                    'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                    'curriculum_owner_id' => $registration->curriculum_owner_id,
                    'multi_curriculum_structure_id' => $mapping->multi_curriculum_structure_id,
                    'mapping_role' => $mapping->mapping_role,
                    'weight' => $mapping->weight,
                    'created_by' => $user->id,
                    'metadata' => ['inherited_from_tp_id' => $parentTp->id],
                ]);
            }

            app(AuditService::class)->log(
                'TEACHER_LEARNING_OUTCOME_CREATED',
                'TenantLearningOutcome',
                $atp->id,
                null,
                [
                    'outcome_type' => 'ATP',
                    'code' => $atp->code,
                    'parent_tp_id' => $parentTp->id,
                    'parent_cp_id' => $parentTp->parent_cp_id,
                    'teacher_subject_assignment_id' => $assignment->id,
                ]
            );

            return $atp->load(['parentCp', 'parentTp', 'structureMappings.structure']);
        }, 3);
    }

    public function publish(string $outcomeId): TenantLearningOutcome
    {
        $user = auth()->user();
        $profile = app(TeacherPortalAccess::class)->profile($user);
        $tenantId = (string) $user->tenant_id;

        return DB::transaction(function () use ($user, $profile, $tenantId, $outcomeId): TenantLearningOutcome {
            $outcome = TenantLearningOutcome::query()
                ->where('tenant_id', $tenantId)
                ->whereHas('assignment', fn ($query) => $query
                    ->where('teacher_profile_id', $profile->id)
                    ->where('status', 'ACTIVE')
                    ->whereDate('starts_at', '<=', now()->toDateString())
                    ->whereDate('ends_at', '>=', now()->toDateString()))
                ->whereKey($outcomeId)
                ->with('parentCp.source', 'structureMappings.structure.source')
                ->lockForUpdate()
                ->firstOrFail();

            if (!$outcome->parentCp
                || !$outcome->parentCp->source
                || $outcome->parentCp->source->verification_status !== 'ARTIFACT_CHECKSUM_VERIFIED'
                || !$outcome->parentCp->source_checksum
                || !hash_equals((string) $outcome->parentCp->source->checksum_sha256, (string) $outcome->parentCp->source_checksum)) {
                throw ValidationException::withMessages([
                    'outcome' => 'CP induk belum terverifikasi; TP/ATP tidak dapat dipublikasikan.',
                ]);
            }

            if ($outcome->structureMappings->isEmpty()) {
                throw ValidationException::withMessages([
                    'outcome' => 'TP/ATP harus memiliki pemetaan nilai Panca Cinta, prinsip Pembelajaran Mendalam, dan dimensi profil lulusan.',
                ]);
            }

            $before = $outcome->only(['status', 'is_current']);
            $outcome->update(['status' => 'PUBLISHED', 'is_current' => true]);

            app(AuditService::class)->log(
                'TEACHER_LEARNING_OUTCOME_PUBLISHED',
                'TenantLearningOutcome',
                $outcome->id,
                $before,
                ['status' => 'PUBLISHED', 'is_current' => true]
            );

            return $outcome->fresh(['parentCp', 'structureMappings.structure']);
        }, 3);
    }

    private function resolveRegistration(TeacherSubjectAssignment $assignment, string $tenantId): TenantCurriculumRegistration
    {
        $registration = TenantCurriculumRegistration::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($assignment->tenant_curriculum_registration_id)
            ->where('multi_curriculum_version_id', $assignment->multi_curriculum_version_id)
            ->where('curriculum_owner_id', $assignment->curriculum_owner_id)
            ->where('status', 'ACTIVE')
            ->with('version')
            ->first();

        if (!$registration) {
            throw ValidationException::withMessages([
                'assignment' => 'Registrasi kurikulum pada penugasan tidak aktif atau tidak cocok.',
            ]);
        }

        return $registration;
    }

    private function validateFrameworkNodes(
        TenantCurriculumRegistration $registration,
        array $pancaCintaIds,
        array $pmPrincipleIds,
        array $pmFrameworkIds,
        array $graduateDimensionIds
    ): array {
        $pancaCintaIds = array_values(array_unique($pancaCintaIds));
        $pmPrincipleIds = array_values(array_unique($pmPrincipleIds));
        $pmFrameworkIds = array_values(array_unique($pmFrameworkIds));
        $graduateDimensionIds = array_values(array_unique($graduateDimensionIds));

        if ($pancaCintaIds === [] || $pmPrincipleIds === [] || $graduateDimensionIds === []) {
            throw ValidationException::withMessages([
                'framework' => 'Pilih sekurang-kurangnya satu nilai Panca Cinta, satu prinsip Pembelajaran Mendalam, dan satu dimensi profil lulusan.',
            ]);
        }

        $expected = [];
        foreach ($pancaCintaIds as $id) $expected[$id] = 'PANCA_CINTA';
        foreach ($pmPrincipleIds as $id) $expected[$id] = 'PM_PRINCIPLE';
        foreach ($pmFrameworkIds as $id) $expected[$id] = 'PM_FRAMEWORK';
        foreach ($graduateDimensionIds as $id) $expected[$id] = 'DIMENSI_PROFIL_LULUSAN';

        $nodes = MultiCurriculumStructure::query()
            ->where('tenant_id', $registration->curriculum_owner_id)
            ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
            ->whereIn('id', array_keys($expected))
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->where('is_editable', false)
            ->with('source')
            ->get();

        if ($nodes->count() !== count($expected)) {
            throw ValidationException::withMessages([
                'framework' => 'Satu atau lebih pilihan Panca Cinta/PM/dimensi tidak berasal dari registry resmi pada versi penugasan.',
            ]);
        }

        foreach ($nodes as $node) {
            if (($expected[$node->id] ?? null) !== $node->node_type
                || !$node->source
                || !in_array($node->source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true)) {
                throw ValidationException::withMessages([
                    'framework' => 'Pemetaan harus menggunakan node resmi dengan provenance sumber terverifikasi.',
                ]);
            }
        }

        return $nodes->map(fn ($node) => [
            'id' => $node->id,
            'mapping_role' => $node->node_type,
        ])->all();
    }

    private function persistFrameworkMappings(
        TenantLearningOutcome $outcome,
        TenantCurriculumRegistration $registration,
        array $mappingNodes,
        string $userId
    ): void {
        foreach ($mappingNodes as $mapping) {
            LearningOutcomeStructureMapping::query()->create([
                'tenant_id' => $outcome->tenant_id,
                'tenant_learning_outcome_id' => $outcome->id,
                'tenant_curriculum_registration_id' => $registration->id,
                'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                'curriculum_owner_id' => $registration->curriculum_owner_id,
                'multi_curriculum_structure_id' => $mapping['id'],
                'mapping_role' => $mapping['mapping_role'],
                'weight' => 1,
                'created_by' => $userId,
                'metadata' => [],
            ]);
        }
    }
}
