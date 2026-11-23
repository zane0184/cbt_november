<?php

namespace App\Services\TeacherPortal;

use App\Models\MultiCurriculumStructure;
use App\Models\MultiLearningOutcome;
use App\Models\TeacherSubjectAssignment;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantCurriculumSubjectMapping;
use App\Models\TenantLearningOutcome;
use Illuminate\Support\Collection;

class TeacherCurriculumContextService
{
    private function sourceMetadataVerified($source): bool
    {
        return $source
            && in_array($source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true);
    }

    private function outcomeArtifactVerified(MultiLearningOutcome $outcome): bool
    {
        $source = $outcome->source;

        return $source
            && $source->verification_status === 'ARTIFACT_CHECKSUM_VERIFIED'
            && $source->checksum_sha256
            && $outcome->source_checksum
            && hash_equals((string) $source->checksum_sha256, (string) $outcome->source_checksum);
    }

    private function nodePayload(MultiCurriculumStructure $node): array
    {
        return [
            'id' => $node->id,
            'code' => $node->code,
            'title' => $node->title,
            'node_type' => $node->node_type,
            'source_id' => $node->source_id,
            'source_document' => $node->source?->document_number,
            'source_title' => $node->source?->title,
            'verification_status' => $node->source?->verification_status ?? $node->verification_status,
            'is_official' => (bool) $node->is_official,
            'is_editable' => (bool) $node->is_editable,
            'metadata' => $node->metadata ?? [],
        ];
    }

    public function forTenant(string $tenantId): array
    {
        $registrations = TenantCurriculumRegistration::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->with(['version.source', 'owner'])
            ->orderByDesc('registered_at')
            ->get();

        $versionIds = $registrations->pluck('multi_curriculum_version_id')->unique()->values();
        $structures = collect();
        $officialOutcomes = collect();

        if ($versionIds->isNotEmpty()) {
            $ownersByVersion = $registrations->keyBy('multi_curriculum_version_id');

            foreach ($ownersByVersion as $versionId => $registration) {
                $structures = $structures->merge(
                    MultiCurriculumStructure::query()
                        ->where('tenant_id', $registration->curriculum_owner_id)
                        ->where('multi_curriculum_version_id', $versionId)
                        ->where('status', 'ACTIVE')
                        ->where('is_official', true)
                        ->with('source')
                        ->orderBy('sort_order')
                        ->get()
                );

                $officialOutcomes = $officialOutcomes->merge(
                    MultiLearningOutcome::query()
                        ->where('tenant_id', $registration->curriculum_owner_id)
                        ->where('multi_curriculum_version_id', $versionId)
                        ->where('outcome_type', 'CP')
                        ->where('status', 'ACTIVE')
                        ->where('is_official', true)
                        ->with(['source', 'structure'])
                        ->orderBy('code')
                        ->get()
                );
            }
        }

        $byType = $structures->groupBy('node_type');
        $pancaCinta = $byType->get('PANCA_CINTA', collect())->filter(fn ($node) => $this->sourceMetadataVerified($node->source))->values();
        $pmPrinciples = $byType->get('PM_PRINCIPLE', collect())->filter(fn ($node) => $this->sourceMetadataVerified($node->source))->values();
        $pmOlah = $byType->get('PM_OLAH', collect())->filter(fn ($node) => $this->sourceMetadataVerified($node->source))->values();
        $pmFramework = $byType->get('PM_FRAMEWORK', collect())->filter(fn ($node) => $this->sourceMetadataVerified($node->source))->values();
        $graduateDimensions = $byType->get('DIMENSI_PROFIL_LULUSAN', collect())->filter(fn ($node) => $this->sourceMetadataVerified($node->source))->values();
        $phases = $byType->get('PHASE', collect())->values();
        $grades = $byType->get('GRADE_LEVEL', collect())->values();
        $subjects = $byType->get('SUBJECT', collect())->values();
        $elements = $byType->get('ELEMENT', collect())->values();

        $verifiedCp = $officialOutcomes->filter(fn (MultiLearningOutcome $outcome) => $this->outcomeArtifactVerified($outcome))->values();

        return [
            'status' => $registrations->isEmpty()
                ? 'NOT_REGISTERED'
                : ($verifiedCp->isEmpty() ? 'CP_REGISTRY_PENDING' : 'READY'),
            'has_registered_reference' => $registrations->isNotEmpty(),
            'versions' => $registrations->map(fn ($registration) => [
                'registration_id' => $registration->id,
                'version_id' => $registration->multi_curriculum_version_id,
                'version_code' => $registration->version?->version_code,
                'title' => $registration->version?->title,
                'effective_year' => $registration->version?->effective_year,
                'curriculum_owner_id' => $registration->curriculum_owner_id,
                'source_document' => $registration->version?->source?->document_number,
                'source_title' => $registration->version?->source?->title,
                'source_verification_status' => $registration->version?->source?->verification_status,
                'verification_status' => $registration->version?->verification_status,
            ])->all(),
            'counts' => [
                'registered_versions' => $registrations->count(),
                'phase' => $phases->count(),
                'grade_level' => $grades->count(),
                'subject' => $subjects->count(),
                'element' => $elements->count(),
                'cp' => $officialOutcomes->count(),
                'cp_artifact_verified' => $verifiedCp->count(),
                'panca_cinta' => $pancaCinta->count(),
                'pm_principle' => $pmPrinciples->count(),
                'pm_olah' => $pmOlah->count(),
                'pm_framework' => $pmFramework->count(),
                'graduate_dimension' => $graduateDimensions->count(),
            ],
            'official_cp_verified' => $officialOutcomes->isNotEmpty() && $verifiedCp->count() === $officialOutcomes->count(),
            'panca_cinta_available' => $pancaCinta->count() === 5,
            'deep_learning_available' => $pmPrinciples->count() === 4 && $pmOlah->count() === 4 && $pmFramework->count() === 4,
            'graduate_dimensions_available' => $graduateDimensions->count() === 8,
            'panca_cinta' => $pancaCinta->map(fn ($node) => $this->nodePayload($node))->all(),
            'deep_learning_principles' => $pmPrinciples->map(fn ($node) => $this->nodePayload($node))->all(),
            'deep_learning_olah' => $pmOlah->map(fn ($node) => $this->nodePayload($node))->all(),
            'deep_learning_framework' => $pmFramework->map(fn ($node) => $this->nodePayload($node))->all(),
            'graduate_dimensions' => $graduateDimensions->map(fn ($node) => $this->nodePayload($node))->all(),
            'phases' => $phases->map(fn ($node) => $this->nodePayload($node))->all(),
            'grade_levels' => $grades->map(fn ($node) => $this->nodePayload($node))->all(),
            'subjects' => $subjects->map(fn ($node) => $this->nodePayload($node))->all(),
            'elements' => $elements->map(fn ($node) => $this->nodePayload($node))->all(),
            'sources' => $structures->pluck('source')->filter()->unique('id')->map(fn ($source) => [
                'id' => $source->id,
                'document_number' => $source->document_number,
                'title' => $source->title,
                'verification_status' => $source->verification_status,
                'source_uri' => $source->source_uri,
            ])->values()->all(),
            'notice' => $registrations->isEmpty()
                ? 'Admin belum mendaftarkan versi kurikulum resmi pada instalasi ini.'
                : ($verifiedCp->isEmpty()
                    ? 'Struktur kurikulum telah tersedia, tetapi CP resmi belum dapat dipilih sampai konten dan checksum sumber diverifikasi.'
                    : null),
        ];
    }

    public function forAssignment(TeacherSubjectAssignment $assignment): array
    {
        $assignment->loadMissing([
            'subject',
            'rombel.gradeLevel.phase',
            'academicYear',
            'semester',
            'curriculumVersion.source',
            'curriculumRegistration',
        ]);

        $tenantId = $assignment->tenant_id;
        $versionId = $assignment->multi_curriculum_version_id;
        $ownerId = $assignment->curriculum_owner_id;
        $registrationId = $assignment->tenant_curriculum_registration_id;

        if (!$versionId || !$ownerId || !$registrationId) {
            return [
                'status' => 'CURRICULUM_NOT_PINNED',
                'notice' => 'Penugasan belum dipatok ke registrasi dan versi kurikulum. Admin perlu memperbarui penugasan sebelum Guru menyusun TP/ATP.',
                'cp_options' => [],
                'teacher_outcomes' => [],
            ];
        }

        $registration = TenantCurriculumRegistration::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($registrationId)
            ->where('multi_curriculum_version_id', $versionId)
            ->where('curriculum_owner_id', $ownerId)
            ->where('status', 'ACTIVE')
            ->with('version.source')
            ->first();

        if (!$registration) {
            return [
                'status' => 'REGISTRATION_INACTIVE',
                'notice' => 'Registrasi kurikulum yang dipatok pada penugasan tidak aktif atau tidak cocok dengan tenant.',
                'cp_options' => [],
                'teacher_outcomes' => [],
            ];
        }

        $snapshot = $assignment->curriculum_context_snapshot ?? [];
        $phaseCode = strtoupper((string) ($snapshot['phase']['code'] ?? $assignment->rombel?->gradeLevel?->phase?->code ?? ''));
        $phaseCode = str_replace('FASE_', '', $phaseCode);
        $gradeNumber = (int) ($snapshot['grade']['level'] ?? $assignment->rombel?->gradeLevel?->level ?? 0);

        $phaseNode = MultiCurriculumStructure::query()
            ->where('tenant_id', $ownerId)
            ->where('multi_curriculum_version_id', $versionId)
            ->where('node_type', 'PHASE')
            ->where('code', 'FASE_' . $phaseCode)
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->with('source')
            ->first();

        $gradeNode = MultiCurriculumStructure::query()
            ->where('tenant_id', $ownerId)
            ->where('multi_curriculum_version_id', $versionId)
            ->where('node_type', 'GRADE_LEVEL')
            ->where('code', 'MI_' . $gradeNumber)
            ->where('parent_id', $phaseNode?->id)
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->with('source')
            ->first();

        $subjectMapping = TenantCurriculumSubjectMapping::query()
            ->where('tenant_id', $tenantId)
            ->where('subject_id', $assignment->subject_id)
            ->where('tenant_curriculum_registration_id', $registrationId)
            ->where('multi_curriculum_version_id', $versionId)
            ->where('curriculum_owner_id', $ownerId)
            ->where('status', 'ACTIVE')
            ->with('curriculumStructure.source')
            ->get()
            ->first(fn (TenantCurriculumSubjectMapping $mapping) => $mapping->curriculumStructure
                && $mapping->curriculumStructure->parent_id === $phaseNode?->id);

        if (!$phaseNode || !$gradeNode || !$subjectMapping?->curriculumStructure) {
            return [
                'status' => 'SUBJECT_MAPPING_MISSING',
                'notice' => 'Fase, kelas, atau pemetaan mata pelajaran resmi belum tersedia untuk penugasan ini.',
                'phase' => $phaseNode ? $this->nodePayload($phaseNode) : null,
                'grade' => $gradeNode ? $this->nodePayload($gradeNode) : null,
                'cp_options' => [],
                'teacher_outcomes' => [],
            ];
        }

        $subjectNode = $subjectMapping->curriculumStructure;
        $cpOutcomes = MultiLearningOutcome::query()
            ->where('tenant_id', $ownerId)
            ->where('multi_curriculum_version_id', $versionId)
            ->where('multi_curriculum_structure_id', $subjectNode->id)
            ->where('outcome_type', 'CP')
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->with('source')
            ->orderBy('code')
            ->get();

        $cpOptions = $cpOutcomes->map(fn (MultiLearningOutcome $outcome) => [
            'id' => $outcome->id,
            'code' => $outcome->code,
            'title' => $outcome->metadata['title'] ?? $outcome->code,
            'description' => $outcome->description,
            'source_document' => $outcome->source?->document_number,
            'source_verification_status' => $outcome->source?->verification_status,
            'selectable' => $this->outcomeArtifactVerified($outcome),
        ])->all();

        $tenantOutcomes = TenantLearningOutcome::query()
            ->where('tenant_id', $tenantId)
            ->where('tenant_curriculum_registration_id', $registrationId)
            ->where('multi_curriculum_version_id', $versionId)
            ->where('curriculum_owner_id', $ownerId)
            ->where('teacher_subject_assignment_id', $assignment->id)
            ->where('status', '!=', 'ARCHIVED')
            ->with([
                'parentCp.source',
                'parentTp',
                'structureMappings.structure.source',
            ])
            ->orderBy('outcome_type')
            ->orderBy('sequence_number')
            ->get();

        $structures = MultiCurriculumStructure::query()
            ->where('tenant_id', $ownerId)
            ->where('multi_curriculum_version_id', $versionId)
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->with('source')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('node_type');

        $pancaCinta = $structures->get('PANCA_CINTA', collect())->filter(fn ($node) => $this->sourceMetadataVerified($node->source))->values();
        $pmPrinciples = $structures->get('PM_PRINCIPLE', collect())->filter(fn ($node) => $this->sourceMetadataVerified($node->source))->values();
        $pmOlah = $structures->get('PM_OLAH', collect())->filter(fn ($node) => $this->sourceMetadataVerified($node->source))->values();
        $pmFramework = $structures->get('PM_FRAMEWORK', collect())->filter(fn ($node) => $this->sourceMetadataVerified($node->source))->values();
        $graduateDimensions = $structures->get('DIMENSI_PROFIL_LULUSAN', collect())->filter(fn ($node) => $this->sourceMetadataVerified($node->source))->values();

        $verifiedCp = $cpOutcomes->filter(fn (MultiLearningOutcome $outcome) => $this->outcomeArtifactVerified($outcome))->values();

        return [
            'status' => $cpOutcomes->isEmpty()
                ? 'CP_REGISTRY_PENDING'
                : ($verifiedCp->isEmpty() ? 'CP_SOURCE_UNVERIFIED' : 'READY'),
            'assignment' => [
                'id' => $assignment->id,
                'subject' => $assignment->subject?->name,
                'rombel' => $assignment->rombel?->name,
                'academic_year' => $assignment->academicYear?->name,
                'semester' => $assignment->semester?->name,
                'version_code' => $registration->version?->version_code,
                'version_title' => $registration->version?->title,
                'phase' => $phaseNode->title,
                'grade' => $gradeNode->title,
                'official_subject' => $subjectNode->title,
            ],
            'registration_id' => $registrationId,
            'curriculum_version_id' => $versionId,
            'curriculum_owner_id' => $ownerId,
            'phase' => $this->nodePayload($phaseNode),
            'grade' => $this->nodePayload($gradeNode),
            'subject_node' => $this->nodePayload($subjectNode),
            'cp_options' => $cpOptions,
            'official_cp_verified' => $cpOutcomes->isNotEmpty() && $verifiedCp->count() === $cpOutcomes->count(),
            'teacher_outcomes' => $tenantOutcomes->map(fn (TenantLearningOutcome $outcome) => [
                'id' => $outcome->id,
                'outcome_type' => $outcome->outcome_type,
                'code' => $outcome->code,
                'title' => $outcome->title,
                'description' => $outcome->description,
                'version_code' => $outcome->version_code,
                'sequence_number' => $outcome->sequence_number,
                'status' => $outcome->status,
                'parent_cp' => $outcome->parentCp?->code,
                'parent_cp_id' => $outcome->parent_cp_id,
                'parent_tp' => $outcome->parentTp?->code,
                'parent_tp_id' => $outcome->parent_tp_id,
                'is_current' => (bool) $outcome->is_current,
                'mappings' => $outcome->structureMappings->map(fn ($mapping) => [
                    'role' => $mapping->mapping_role,
                    'code' => $mapping->structure?->code,
                    'title' => $mapping->structure?->title,
                ])->all(),
            ])->all(),
            'panca_cinta' => $pancaCinta->map(fn ($node) => $this->nodePayload($node))->all(),
            'deep_learning_principles' => $pmPrinciples->map(fn ($node) => $this->nodePayload($node))->all(),
            'deep_learning_olah' => $pmOlah->map(fn ($node) => $this->nodePayload($node))->all(),
            'deep_learning_framework' => $pmFramework->map(fn ($node) => $this->nodePayload($node))->all(),
            'graduate_dimensions' => $graduateDimensions->map(fn ($node) => $this->nodePayload($node))->all(),
            'panca_cinta_available' => $pancaCinta->count() === 5,
            'deep_learning_available' => $pmPrinciples->count() === 4 && $pmOlah->count() === 4 && $pmFramework->count() === 4,
            'graduate_dimensions_available' => $graduateDimensions->count() === 8,
            'notice' => $cpOutcomes->isEmpty()
                ? 'CP resmi untuk mata pelajaran dan fase ini belum diimpor atau diverifikasi. Penyusunan TP/ATP dinonaktifkan sampai CP induk tersedia.'
                : ($verifiedCp->isEmpty()
                    ? 'CP ditemukan, tetapi sumber/artefaknya belum checksum verified. CP belum dapat dipilih.'
                    : null),
        ];
    }
}
