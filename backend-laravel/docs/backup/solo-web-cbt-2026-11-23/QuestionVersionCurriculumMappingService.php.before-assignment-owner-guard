<?php

namespace App\Services\TeacherPortal;

use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\MultiLearningOutcome;
use App\Models\QuestionVersion;
use App\Models\QuestionVersionCurriculumMapping;
use App\Models\QuestionVersionStructureMapping;
use App\Models\QuestionVersionTenantOutcomeMapping;
use App\Models\TeacherSubjectAssignment;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantLearningOutcome;
use App\Services\AuditService;
use App\Support\Security\TeacherPortalAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuestionVersionCurriculumMappingService
{
    public function sync(
        QuestionVersion $version,
        string $assignmentId,
        array $cpIds,
        array $outcomeIds,
        ?string $materialId = null,
        ?string $topicId = null
    ): array {
        $user = auth()->user();
        app(TeacherPortalAccess::class)->profile($user);
        abort_unless($user->hasPermission('question.manage') && $user->hasPermission('curriculum.manage'), 403);

        $tenantId = (string) $user->tenant_id;
        $cpIds = array_values(array_unique($cpIds));
        $outcomeIds = array_values(array_unique($outcomeIds));

        if ($cpIds === [] || $outcomeIds === []) {
            throw ValidationException::withMessages([
                'curriculumMapping' => 'Setiap versi soal wajib dipetakan ke minimal satu CP resmi dan satu TP/ATP terpublikasi.',
            ]);
        }

        return DB::transaction(function () use ($version, $assignmentId, $cpIds, $outcomeIds, $materialId, $topicId, $user, $tenantId): array {
            $version = QuestionVersion::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($version->id)
                ->with('question')
                ->lockForUpdate()
                ->firstOrFail();

            if ($version->status !== 'DRAFT') {
                throw ValidationException::withMessages([
                    'curriculumMapping' => 'Pemetaan hanya dapat diubah pada versi soal DRAFT. Versi PUBLISHED bersifat immutable.',
                ]);
            }

            $assignment = TeacherSubjectAssignment::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($assignmentId)
                ->where('status', 'ACTIVE')
                ->whereDate('starts_at', '<=', now()->toDateString())
                ->whereDate('ends_at', '>=', now()->toDateString())
                ->with('curriculumRegistration')
                ->lockForUpdate()
                ->firstOrFail();

            if (!$version->question || $version->question->subject_id !== $assignment->subject_id) {
                throw ValidationException::withMessages([
                    'selectedAssignmentId' => 'Penugasan Guru tidak sesuai dengan mata pelajaran soal.',
                ]);
            }

            $registration = TenantCurriculumRegistration::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($assignment->tenant_curriculum_registration_id)
                ->where('multi_curriculum_version_id', $assignment->multi_curriculum_version_id)
                ->where('curriculum_owner_id', $assignment->curriculum_owner_id)
                ->where('status', 'ACTIVE')
                ->firstOrFail();

            $subjectStructureId = $assignment->curriculum_context_snapshot['subject']['official_structure_id'] ?? null;
            if (!$subjectStructureId) {
                throw ValidationException::withMessages([
                    'selectedAssignmentId' => 'Snapshot penugasan tidak memiliki node mapel resmi.',
                ]);
            }

            $cps = MultiLearningOutcome::query()
                ->where('tenant_id', $registration->curriculum_owner_id)
                ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                ->where('multi_curriculum_structure_id', $subjectStructureId)
                ->whereIn('id', $cpIds)
                ->where('outcome_type', 'CP')
                ->where('status', 'ACTIVE')
                ->where('is_official', true)
                ->where('is_editable', false)
                ->with('source')
                ->get();

            if ($cps->count() !== count($cpIds)) {
                throw ValidationException::withMessages([
                    'selectedCpIds' => 'CP harus berasal dari registry resmi untuk mapel dan fase pada penugasan ini.',
                ]);
            }

            foreach ($cps as $cp) {
                if (!$cp->source
                    || $cp->source->verification_status !== 'ARTIFACT_CHECKSUM_VERIFIED'
                    || !$cp->source->checksum_sha256
                    || !$cp->source_checksum
                    || !hash_equals((string) $cp->source->checksum_sha256, (string) $cp->source_checksum)) {
                    throw ValidationException::withMessages([
                        'selectedCpIds' => 'Sumber atau checksum CP belum terverifikasi. CP ini tidak dapat dipetakan ke soal.',
                    ]);
                }
            }

            $outcomes = TenantLearningOutcome::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->where('tenant_curriculum_registration_id', $registration->id)
                ->where('multi_curriculum_version_id', $registration->multi_curriculum_version_id)
                ->where('curriculum_owner_id', $registration->curriculum_owner_id)
                ->whereIn('id', $outcomeIds)
                ->whereIn('outcome_type', ['TP', 'ATP'])
                ->where('status', 'PUBLISHED')
                ->where('is_current', true)
                ->with(['parentCp.source', 'structureMappings.structure.source'])
                ->get();

            if ($outcomes->count() !== count($outcomeIds)) {
                throw ValidationException::withMessages([
                    'selectedOutcomeIds' => 'TP/ATP harus sudah dipublikasikan dan berasal dari penugasan aktif ini.',
                ]);
            }

            $selectedCpIds = $cps->pluck('id')->all();
            foreach ($outcomes as $outcome) {
                if (!in_array($outcome->parent_cp_id, $selectedCpIds, true)) {
                    throw ValidationException::withMessages([
                        'selectedOutcomeIds' => 'Setiap TP/ATP yang dipilih harus terhubung ke salah satu CP yang dipetakan pada soal.',
                    ]);
                }

                if ($outcome->structureMappings->isEmpty()) {
                    throw ValidationException::withMessages([
                        'selectedOutcomeIds' => 'TP/ATP terpilih belum memiliki pemetaan Panca Cinta, Pembelajaran Mendalam, dan dimensi profil lulusan.',
                    ]);
                }
            }

            $material = null;
            $topic = null;
            if ($materialId) {
                $material = Material::query()
                    ->where('tenant_id', $tenantId)
                    ->where('teacher_subject_assignment_id', $assignment->id)
                    ->where('tenant_curriculum_registration_id', $registration->id)
                    ->where('status', 'PUBLISHED')
                    ->whereKey($materialId)
                    ->firstOrFail();
            }

            if ($topicId) {
                if (!$material) {
                    throw ValidationException::withMessages([
                        'selectedMaterialTopicId' => 'Pilih materi induk sebelum memilih topik.',
                    ]);
                }

                $topic = MaterialTopic::query()
                    ->where('tenant_id', $tenantId)
                    ->where('material_id', $material->id)
                    ->where('teacher_subject_assignment_id', $assignment->id)
                    ->where('tenant_curriculum_registration_id', $registration->id)
                    ->where('status', 'PUBLISHED')
                    ->whereKey($topicId)
                    ->firstOrFail();
            }

            QuestionVersionCurriculumMapping::query()
                ->where('tenant_id', $tenantId)
                ->where('question_version_id', $version->id)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->delete();

            QuestionVersionTenantOutcomeMapping::query()
                ->where('tenant_id', $tenantId)
                ->where('question_version_id', $version->id)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->delete();

            QuestionVersionStructureMapping::query()
                ->where('tenant_id', $tenantId)
                ->where('question_version_id', $version->id)
                ->where('teacher_subject_assignment_id', $assignment->id)
                ->delete();

            foreach ($cps->values() as $index => $cp) {
                QuestionVersionCurriculumMapping::query()->create([
                    'tenant_id' => $tenantId,
                    'question_version_id' => $version->id,
                    'teacher_subject_assignment_id' => $assignment->id,
                    'tenant_curriculum_registration_id' => $registration->id,
                    'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                    'curriculum_owner_id' => $registration->curriculum_owner_id,
                    'multi_learning_outcome_id' => $cp->id,
                    'material_id' => $material?->id,
                    'material_topic_id' => $topic?->id,
                    'academic_year_id' => $assignment->academic_year_id,
                    'semester_id' => $assignment->semester_id,
                    'mapping_role' => 'CP',
                    'weight' => 1,
                    'is_primary' => $index === 0,
                    'created_by' => $user->id,
                    'metadata' => ['cp_code' => $cp->code, 'source_document' => $cp->source?->document_number],
                ]);
            }

            foreach ($outcomes->values() as $index => $outcome) {
                QuestionVersionTenantOutcomeMapping::query()->create([
                    'tenant_id' => $tenantId,
                    'question_version_id' => $version->id,
                    'teacher_subject_assignment_id' => $assignment->id,
                    'tenant_curriculum_registration_id' => $registration->id,
                    'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                    'curriculum_owner_id' => $registration->curriculum_owner_id,
                    'tenant_learning_outcome_id' => $outcome->id,
                    'material_id' => $material?->id,
                    'material_topic_id' => $topic?->id,
                    'academic_year_id' => $assignment->academic_year_id,
                    'semester_id' => $assignment->semester_id,
                    'mapping_role' => $outcome->outcome_type,
                    'weight' => 1,
                    'is_primary' => $index === 0,
                    'created_by' => $user->id,
                    'metadata' => [
                        'outcome_code' => $outcome->code,
                        'parent_cp_id' => $outcome->parent_cp_id,
                        'parent_tp_id' => $outcome->parent_tp_id,
                    ],
                ]);
            }

            $frameworkNodes = $outcomes
                ->flatMap(fn (TenantLearningOutcome $outcome) => $outcome->structureMappings)
                ->unique('multi_curriculum_structure_id')
                ->values();

            foreach ($frameworkNodes as $mapping) {
                QuestionVersionStructureMapping::query()->create([
                    'tenant_id' => $tenantId,
                    'question_version_id' => $version->id,
                    'teacher_subject_assignment_id' => $assignment->id,
                    'tenant_curriculum_registration_id' => $registration->id,
                    'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                    'curriculum_owner_id' => $registration->curriculum_owner_id,
                    'multi_curriculum_structure_id' => $mapping->multi_curriculum_structure_id,
                    'academic_year_id' => $assignment->academic_year_id,
                    'semester_id' => $assignment->semester_id,
                    'mapping_role' => $mapping->mapping_role,
                    'weight' => $mapping->weight,
                    'created_by' => $user->id,
                    'metadata' => ['inherited_from_outcome_id' => $mapping->tenant_learning_outcome_id],
                ]);
            }

            app(AuditService::class)->log(
                'QUESTION_VERSION_CURRICULUM_MAPPED',
                QuestionVersion::class,
                $version->id,
                null,
                [
                    'teacher_subject_assignment_id' => $assignment->id,
                    'cp_ids' => $cps->pluck('id')->all(),
                    'outcome_ids' => $outcomes->pluck('id')->all(),
                    'material_id' => $material?->id,
                    'material_topic_id' => $topic?->id,
                    'structure_mapping_count' => $frameworkNodes->count(),
                ]
            );

            return [
                'cp_count' => $cps->count(),
                'outcome_count' => $outcomes->count(),
                'structure_count' => $frameworkNodes->count(),
                'material_id' => $material?->id,
                'topic_id' => $topic?->id,
            ];
        }, 3);
    }
}
