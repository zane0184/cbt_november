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
        $this->assertCurriculumPermission($user);
        $profile = app(TeacherPortalAccess::class)->profile($user);
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

            $this->assertCpMatchesAssignmentPhase($cp, $assignment, $subjectStructureId, 'parentCpId');

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
                $pmOlahIds,
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
        $this->assertCurriculumPermission($user);
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
                ->lockForUpdate()
                ->first();

            if (!$parentTp) {
                $this->reject('CP_PARENT_RELATION_INVALID', 'TP induk tidak ditemukan atau tidak terkait secara sah dengan penugasan dan CP.', 'parentTpId');
            }

            $parentCp = $this->resolveVerifiedOfficialCp(
                $registration->curriculum_owner_id,
                $registration->multi_curriculum_version_id,
                $subjectStructureId,
                $parentTp->parent_cp_id,
                'parentTpId',
                'ATP'
            );

            $this->assertCpMatchesAssignmentPhase($parentCp, $assignment, $subjectStructureId, 'parentTpId');

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

            $parentTpMappings = $this->resolveConsistentFrameworkMappings(
                $parentTp,
                $assignment,
                $registration,
                $tenantId
            );

            foreach ($parentTpMappings as $mapping) {
                LearningOutcomeStructureMapping::query()->create([
                    'tenant_id' => $tenantId,
                    'tenant_learning_outcome_id' => $atp->id,
                    'teacher_subject_assignment_id' => $assignment->id,
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
        $this->assertCurriculumPermission($user);
        $profile = app(TeacherPortalAccess::class)->profile($user);
        $tenantId = (string) $user->tenant_id;

        return DB::transaction(function () use ($user, $profile, $tenantId, $outcomeId): TenantLearningOutcome {
            // Global lock order: assignment -> registration -> outcome/TP -> CP -> source -> mappings.
            // The first read only discovers the assignment FK; the outcome is reloaded under lock.
            $target = TenantLearningOutcome::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($outcomeId)
                ->firstOrFail();

            $assignment = TeacherSubjectAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_profile_id', $profile->id)
                ->whereKey($target->teacher_subject_assignment_id)
                ->where('status', 'ACTIVE')
                ->whereDate('starts_at', '<=', now()->toDateString())
                ->whereDate('ends_at', '>=', now()->toDateString())
                ->lockForUpdate()
                ->firstOrFail();

            $registration = $this->resolveRegistration($assignment, $tenantId);

            $outcome = TenantLearningOutcome::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->whereKey($outcomeId)
                ->whereIn('outcome_type', ['TP', 'ATP'])
                ->with(['parentTp'])
                ->lockForUpdate()
                ->firstOrFail();
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
                $this->reject('CP_PARENT_RELATION_INVALID', 'TP induk memiliki relasi parent TP yang tidak sah.', 'outcome');
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
                    $this->reject('CP_PARENT_RELATION_INVALID', 'Relasi TP induk pada ATP tidak konsisten.', 'outcome');
                }
            }

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

            $this->assertCpMatchesAssignmentPhase($verifiedCp, $assignment, $subjectStructureId, 'outcome');

            $this->resolveConsistentFrameworkMappings($outcome, $assignment, $registration, $tenantId);

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

    private function assertCpMatchesAssignmentPhase(
        MultiLearningOutcome $cp,
        TeacherSubjectAssignment $assignment,
        string $subjectStructureId,
        string $errorKey
    ): void {
        $subject = MultiCurriculumStructure::query()
            ->where('tenant_id', $cp->tenant_id)
            ->where('multi_curriculum_version_id', $cp->multi_curriculum_version_id)
            ->whereKey($subjectStructureId)
            ->where('node_type', 'SUBJECT')
            ->with('parent')
            ->first();

        $phaseCode = strtoupper((string) ($subject?->parent?->code ?? ''));
        $phaseCode = str_replace('FASE_', '', $phaseCode);

        $snapshot = $assignment->curriculum_context_snapshot ?? [];
        $gradeNumber = (int) (
            data_get($snapshot, 'grade.level')
            ?? data_get($snapshot, 'grade.grade_number')
            ?? 0
        );

        if ($gradeNumber < 1) {
            $assignment->loadMissing('rombel.gradeLevel');
            $gradeNumber = (int) ($assignment->rombel?->gradeLevel?->level ?? 0);
        }

        $metadata = $cp->metadata ?? [];
        $cpPhase = strtoupper((string) ($metadata['phase'] ?? ''));
        $cpGrades = array_map('intval', (array) ($metadata['grade_levels'] ?? []));

        if (!$subject
            || !$phaseCode
            || $gradeNumber < 1
            || $cpPhase !== $phaseCode
            || !in_array($gradeNumber, $cpGrades, true)) {
            $this->reject(
                'CP_PHASE_GRADE_MISMATCH',
                'CP tidak sesuai dengan fase dan kelas pada penugasan Guru. TP/ATP tidak dapat dibuat atau dipublikasikan.',
                $errorKey
            );
        }
    }

    private function assertCurriculumPermission($user): void
    {
        abort_unless(
            $user && $user->hasPermission('curriculum.manage'),
            403,
            'CURRICULUM_PERMISSION_DENIED: akun tidak memiliki izin curriculum.manage.'
        );
    }

    private function reject(string $code, string $message, string $errorKey = 'parentCpId'): never
    {
        throw ValidationException::withMessages([
            $errorKey => "{$code}: {$message}",
        ]);
    }

    private function resolveVerifiedOfficialCp(
        string $ownerId,
        string $versionId,
        string $structureId,
        ?string $cpId,
        string $errorKey,
        string $action
    ): MultiLearningOutcome {
        if (!$cpId) {
            $this->reject('CP_PARENT_RELATION_INVALID', "Relasi CP induk tidak tersedia. {$action} ditolak.", $errorKey);
        }

        $cp = MultiLearningOutcome::query()
            ->where('tenant_id', $ownerId)
            ->where('multi_curriculum_version_id', $versionId)
            ->where('multi_curriculum_structure_id', $structureId)
            ->whereKey($cpId)
            ->where('outcome_type', 'CP')
            ->lockForUpdate()
            ->first();

        if (!$cp || !$cp->is_official || $cp->is_editable) {
            $this->reject('CP_PARENT_RELATION_INVALID', "CP induk tidak ditemukan pada owner, versi, struktur, atau relasi resmi yang ditetapkan. {$action} ditolak.", $errorKey);
        }

        if ($cp->status !== 'ACTIVE') {
            $this->reject('CP_NOT_ACTIVE', "CP induk berstatus {$cp->status}; hanya CP ACTIVE yang dapat digunakan. {$action} ditolak.", $errorKey);
        }

        if (!$cp->source_id) {
            $this->reject('CP_SOURCE_UNVERIFIED', "Sumber artefak resmi CP tidak tersedia atau tidak berada pada cakupan owner kurikulum. {$action} ditolak.", $errorKey);
        }

        $source = CurriculumReferenceSource::query()
            ->where('tenant_id', $ownerId)
            ->whereKey($cp->source_id)
            ->lockForUpdate()
            ->first();

        if (!$source) {
            $this->reject('CP_SOURCE_UNVERIFIED', "Sumber artefak resmi CP tidak ditemukan pada tenant pemilik kurikulum. {$action} ditolak.", $errorKey);
        }

        if ($source->verification_status !== 'ARTIFACT_CHECKSUM_VERIFIED') {
            $this->reject('CP_SOURCE_UNVERIFIED', "Provenance sumber artefak resmi CP belum berstatus ARTIFACT_CHECKSUM_VERIFIED. {$action} ditolak.", $errorKey);
        }

        $sourceChecksum = (string) ($source->checksum_sha256 ?? '');
        $cpChecksum = (string) ($cp->source_checksum ?? '');

        if (preg_match('/\\A[a-f0-9]{64}\\z/i', $sourceChecksum) !== 1
            || preg_match('/\\A[a-f0-9]{64}\\z/i', $cpChecksum) !== 1
            || !hash_equals($sourceChecksum, $cpChecksum)) {
            $this->reject('CP_CHECKSUM_MISMATCH', "Checksum SHA-256 sumber dan CP tidak tersedia dalam format sah atau tidak cocok. {$action} ditolak.", $errorKey);
        }

        $cp->setRelation('source', $source);

        return $cp;
    }

    private function resolveConsistentFrameworkMappings(
        TenantLearningOutcome $outcome,
        TeacherSubjectAssignment $assignment,
        TenantCurriculumRegistration $registration,
        string $tenantId
    ) {
        $mappings = LearningOutcomeStructureMapping::query()
            ->where('tenant_id', $tenantId)
            ->where('tenant_learning_outcome_id', $outcome->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($mappings->isEmpty()) {
            $this->reject('ATP_FRAMEWORK_MAPPING_INVALID', 'TP/ATP tidak memiliki mapping framework yang dapat diwariskan secara sah.', 'mapping');
        }

        foreach ($mappings as $mapping) {
            if ((string) $mapping->tenant_id !== $tenantId
                || (string) $mapping->teacher_subject_assignment_id !== (string) $assignment->id
                || (string) $mapping->tenant_curriculum_registration_id !== (string) $registration->id
                || (string) $mapping->multi_curriculum_version_id !== (string) $registration->multi_curriculum_version_id
                || (string) $mapping->curriculum_owner_id !== (string) $registration->curriculum_owner_id) {
                $this->reject('ATP_FRAMEWORK_MAPPING_INVALID', 'Konteks tenant, assignment, registration, version, atau curriculum owner pada mapping tidak konsisten.', 'mapping');
            }
        }

        $structureIds = $mappings->pluck('multi_curriculum_structure_id')->unique()->values();
        $structures = MultiCurriculumStructure::query()
            ->where('tenant_id', $registration->curriculum_owner_id)
            ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
            ->whereIn('id', $structureIds)
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->where('is_editable', false)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if ($structures->count() !== $structureIds->count()) {
            $this->reject('ATP_FRAMEWORK_MAPPING_INVALID', 'Satu atau lebih node mapping tidak aktif atau bukan bagian dari registry resmi pada owner/version penugasan.', 'mapping');
        }

        $sourceIds = $structures->pluck('source_id')->filter()->unique()->values();
        $sources = CurriculumReferenceSource::query()
            ->where('tenant_id', $registration->curriculum_owner_id)
            ->whereIn('id', $sourceIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($structures as $structure) {
            $source = $sources->get($structure->source_id);
            if (!$source || !in_array($source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true)) {
                $this->reject('ATP_FRAMEWORK_MAPPING_INVALID', 'Provenance sumber node framework pada mapping tidak valid untuk owner kurikulum.', 'mapping');
            }
        }

        foreach ($mappings as $mapping) {
            $structure = $structures->get($mapping->multi_curriculum_structure_id);
            if (!$structure || $mapping->mapping_role !== $structure->node_type) {
                $this->reject('ATP_FRAMEWORK_MAPPING_INVALID', 'Mapping role tidak cocok dengan struktur framework resmi.', 'mapping');
            }
        }

        return $mappings;
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
            ->lockForUpdate()
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
                'teacher_subject_assignment_id' => $outcome->teacher_subject_assignment_id,
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
