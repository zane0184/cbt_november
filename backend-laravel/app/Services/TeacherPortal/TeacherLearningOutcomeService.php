<?php

namespace App\Services\TeacherPortal;

use App\Models\LearningOutcomeStructureMapping;
use App\Models\CurriculumReferenceSource;
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
        array $pmOlahIds,
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
            $pmOlahIds,
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

            $cp = $this->resolveVerifiedOfficialCp(
                $registration->curriculum_owner_id,
                $registration->multi_curriculum_version_id,
                $subjectStructureId,
                $parentCpId,
                'parentCpId',
                'TP'
            );

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
        abort_unless($user->hasPermission('curriculum.manage'), 403, 'Akun Guru tidak memiliki izin mengembangkan TP/ATP.');
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
            $subjectStructureId = $assignment->curriculum_context_snapshot['subject']['official_structure_id'] ?? null;

            if (!$subjectStructureId) {
                throw ValidationException::withMessages([
                    'assignment' => 'Snapshot penugasan tidak memiliki pemetaan mata pelajaran resmi.',
                ]);
            }

            $parentTp = TenantLearningOutcome::query()
                ->where('tenant_id', $tenantId)
                ->where('tenant_curriculum_registration_id', $registration->id)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->where('curriculum_owner_id', $registration->curriculum_owner_id)
                ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                ->where('multi_curriculum_structure_id', $subjectStructureId)
                ->where('subject_id', $assignment->subject_id)
                ->where('academic_year_id', $assignment->academic_year_id)
                ->where('semester_id', $assignment->semester_id)
                ->whereNotNull('parent_cp_id')
                ->whereKey($parentTpId)
                ->where('outcome_type', 'TP')
                ->where('is_current', true)
                ->whereIn('status', ['DRAFT', 'PUBLISHED'])
                ->with('structureMappings')
                ->lockForUpdate()
                ->firstOrFail();

            $parentCp = $this->resolveVerifiedOfficialCp(
                $registration->curriculum_owner_id,
                $registration->multi_curriculum_version_id,
                $subjectStructureId,
                $parentTp->parent_cp_id,
                'parentTpId',
                'ATP'
            );

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
                'parent_cp_id' => $parentCp->id,
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
        abort_unless($user->hasPermission('curriculum.manage'), 403, 'Akun Guru tidak memiliki izin mengembangkan TP/ATP.');
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
                ->whereIn('outcome_type', ['TP', 'ATP'])
                ->with(['assignment', 'parentTp', 'structureMappings.structure.source'])
                ->lockForUpdate()
                ->firstOrFail();

            $assignment = $outcome->assignment;
            if (!$assignment
                || (string) $assignment->tenant_id !== $tenantId
                || (string) $assignment->teacher_profile_id !== (string) $profile->id
                || (string) $outcome->tenant_curriculum_registration_id !== (string) $assignment->tenant_curriculum_registration_id
                || (string) $outcome->multi_curriculum_version_id !== (string) $assignment->multi_curriculum_version_id
                || (string) $outcome->curriculum_owner_id !== (string) $assignment->curriculum_owner_id
                || (string) $outcome->subject_id !== (string) $assignment->subject_id
                || (string) $outcome->academic_year_id !== (string) $assignment->academic_year_id
                || (string) $outcome->semester_id !== (string) $assignment->semester_id) {
                throw ValidationException::withMessages([
                    'outcome' => 'Konteks penugasan TP/ATP tidak konsisten.',
                ]);
            }

            if ($outcome->outcome_type === 'TP' && $outcome->parent_tp_id !== null) {
                throw ValidationException::withMessages([
                    'outcome' => 'TP induk memiliki relasi parent TP yang tidak sah.',
                ]);
            }

            if ($outcome->outcome_type === 'ATP') {
                $parentTp = TenantLearningOutcome::query()
                    ->where('tenant_id', $tenantId)
                    ->where('tenant_curriculum_registration_id', $assignment->tenant_curriculum_registration_id)
                    ->where('teacher_subject_assignment_id', $assignment->id)
                    ->where('parent_cp_id', $outcome->parent_cp_id)
                    ->where('outcome_type', 'TP')
                    ->where('is_current', true)
                    ->whereIn('status', ['DRAFT', 'PUBLISHED'])
                    ->whereKey($outcome->parent_tp_id)
                    ->lockForUpdate()
                    ->first();

                if (!$parentTp
                    || (string) $parentTp->curriculum_owner_id !== (string) $outcome->curriculum_owner_id
                    || (string) $parentTp->multi_curriculum_version_id !== (string) $outcome->multi_curriculum_version_id
                    || (string) $parentTp->multi_curriculum_structure_id !== (string) $outcome->multi_curriculum_structure_id
                    || (string) $parentTp->subject_id !== (string) $outcome->subject_id
                    || (string) $parentTp->academic_year_id !== (string) $outcome->academic_year_id
                    || (string) $parentTp->semester_id !== (string) $outcome->semester_id) {
                    throw ValidationException::withMessages([
                        'outcome' => 'Relasi TP induk pada ATP tidak konsisten.',
                    ]);
                }
            }

            $registration = $this->resolveRegistration($assignment, $tenantId);
            $subjectStructureId = $assignment->curriculum_context_snapshot['subject']['official_structure_id'] ?? null;
            if (!$subjectStructureId || (string) $outcome->multi_curriculum_structure_id !== (string) $subjectStructureId) {
                throw ValidationException::withMessages([
                    'outcome' => 'Struktur mata pelajaran pada TP/ATP tidak cocok dengan snapshot penugasan.',
                ]);
            }

            $verifiedCp = $this->resolveVerifiedOfficialCp(
                $registration->curriculum_owner_id,
                $registration->multi_curriculum_version_id,
                $subjectStructureId,
                $outcome->parent_cp_id,
                'outcome',
                'publikasi TP/ATP'
            );

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

    private function resolveVerifiedOfficialCp(
        string $ownerId,
        string $versionId,
        string $structureId,
        ?string $cpId,
        string $errorKey,
        string $action
    ): MultiLearningOutcome {
        $failure = fn () => ValidationException::withMessages([
            $errorKey => "CP induk belum memenuhi provenance artefak resmi terverifikasi. {$action} tidak dapat dilanjutkan.",
        ]);

        if (!$cpId) {
            throw $failure();
        }

        $cp = MultiLearningOutcome::query()
            ->where('tenant_id', $ownerId)
            ->where('multi_curriculum_version_id', $versionId)
            ->where('multi_curriculum_structure_id', $structureId)
            ->whereKey($cpId)
            ->where('outcome_type', 'CP')
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->where('is_editable', false)
            ->lockForUpdate()
            ->first();

        if (!$cp || !$cp->source_id) {
            throw $failure();
        }

        $source = CurriculumReferenceSource::query()
            ->where('tenant_id', $ownerId)
            ->whereKey($cp->source_id)
            ->lockForUpdate()
            ->first();

        $sourceChecksum = (string) ($source?->checksum_sha256 ?? '');
        $cpChecksum = (string) ($cp->source_checksum ?? '');

        if (!$source
            || $source->verification_status !== 'ARTIFACT_CHECKSUM_VERIFIED'
            || preg_match('/\\A[a-f0-9]{64}\\z/i', $sourceChecksum) !== 1
            || preg_match('/\\A[a-f0-9]{64}\\z/i', $cpChecksum) !== 1
            || !hash_equals($sourceChecksum, $cpChecksum)) {
            throw $failure();
        }

        $cp->setRelation('source', $source);

        return $cp;
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
        array $pmOlahIds,
        array $pmFrameworkIds,
        array $graduateDimensionIds
    ): array {
        $pancaCintaIds = array_values(array_unique($pancaCintaIds));
        $pmPrincipleIds = array_values(array_unique($pmPrincipleIds));
        $pmOlahIds = array_values(array_unique($pmOlahIds));
        $pmFrameworkIds = array_values(array_unique($pmFrameworkIds));
        $graduateDimensionIds = array_values(array_unique($graduateDimensionIds));

        if ($pancaCintaIds === [] || $pmPrincipleIds === [] || $pmOlahIds === [] || $graduateDimensionIds === []) {
            throw ValidationException::withMessages([
                'framework' => 'Pilih sekurang-kurangnya satu nilai Panca Cinta, satu prinsip Pembelajaran Mendalam, dan satu dimensi profil lulusan.',
            ]);
        }

        $expected = [];
        foreach ($pancaCintaIds as $id) $expected[$id] = 'PANCA_CINTA';
        foreach ($pmPrincipleIds as $id) $expected[$id] = 'PM_PRINCIPLE';
        foreach ($pmOlahIds as $id) $expected[$id] = 'PM_OLAH';
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
