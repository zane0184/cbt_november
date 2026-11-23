<?php

namespace App\Services\TeacherPortal;

use App\Models\Assessment;
use App\Models\AssessmentCurriculumMapping;
use App\Models\Material;
use App\Models\MaterialTopic;
use App\Models\MultiLearningOutcome;
use App\Models\TeacherSubjectAssignment;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantLearningOutcome;
use App\Services\AuditService;
use App\Support\Security\TeacherPortalAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssessmentCurriculumMappingService
{
    public function sync(
        Assessment $assessment,
        TeacherSubjectAssignment $assignment,
        array $cpIds,
        array $outcomeIds,
        ?string $materialId = null,
        ?string $topicId = null
    ): array {
        $user = auth()->user();
        $profile = app(TeacherPortalAccess::class)->profile($user);
        abort_unless($user->hasPermission('exam.manage') && $user->hasPermission('curriculum.manage'), 403);

        $tenantId = (string) $user->tenant_id;
        $cpIds = array_values(array_unique($cpIds));
        $outcomeIds = array_values(array_unique($outcomeIds));
        if ($cpIds === [] || $outcomeIds === []) {
            throw ValidationException::withMessages([
                'assessmentCurriculum' => 'Asesmen wajib dipetakan ke minimal satu CP resmi dan satu TP/ATP terpublikasi.',
            ]);
        }

        return DB::transaction(function () use ($assessment, $assignment, $cpIds, $outcomeIds, $materialId, $topicId, $user, $profile, $tenantId): array {
            $lockedAssignment = TeacherSubjectAssignment::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_profile_id', $profile->id)
                ->whereKey($assignment->id)
                ->where('status', 'ACTIVE')
                ->whereDate('starts_at', '<=', now()->toDateString())
                ->whereDate('ends_at', '>=', now()->toDateString())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAssessment = Assessment::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($assessment->id)
                ->where('teacher_subject_assignment_id', $lockedAssignment->id)
                ->where('tenant_curriculum_registration_id', $lockedAssignment->tenant_curriculum_registration_id)
                ->where('curriculum_version_id', $lockedAssignment->multi_curriculum_version_id)
                ->where('curriculum_owner_id', $lockedAssignment->curriculum_owner_id)
                ->lockForUpdate()
                ->firstOrFail();

            $registration = TenantCurriculumRegistration::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($lockedAssignment->tenant_curriculum_registration_id)
                ->where('multi_curriculum_version_id', $lockedAssignment->multi_curriculum_version_id)
                ->where('curriculum_owner_id', $lockedAssignment->curriculum_owner_id)
                ->where('status', 'ACTIVE')
                ->firstOrFail();

            $subjectStructureId = $lockedAssignment->curriculum_context_snapshot['subject']['official_structure_id'] ?? null;
            if (!$subjectStructureId) {
                throw ValidationException::withMessages([
                    'selectedAssignmentId' => 'Penugasan belum memiliki node mapel resmi.',
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
                    'selectedCpIds' => 'CP harus berasal dari registry resmi untuk mapel dan fase penugasan ini.',
                ]);
            }

            foreach ($cps as $cp) {
                if (!$cp->source
                    || $cp->source->verification_status !== 'ARTIFACT_CHECKSUM_VERIFIED'
                    || !$cp->source->checksum_sha256
                    || !$cp->source_checksum
                    || !hash_equals((string) $cp->source->checksum_sha256, (string) $cp->source_checksum)) {
                    throw ValidationException::withMessages([
                        'selectedCpIds' => 'Sumber/checksum CP belum terverifikasi.',
                    ]);
                }
            }

            $outcomes = TenantLearningOutcome::query()
                ->where('tenant_id', $tenantId)
                ->where('teacher_subject_assignment_id', $lockedAssignment->id)
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
                    'selectedOutcomeIds' => 'TP/ATP harus terpublikasi dan berasal dari penugasan ini.',
                ]);
            }

            $selectedCpIds = $cps->pluck('id')->all();
            $outcomeParentCpIds = $outcomes->pluck('parent_cp_id')->unique()->values()->all();
            foreach ($outcomeParentCpIds as $parentCpId) {
                if (!in_array($parentCpId, $selectedCpIds, true)) {
                    throw ValidationException::withMessages([
                        'selectedOutcomeIds' => 'Semua TP/ATP harus berada di bawah CP yang dipilih untuk asesmen.',
                    ]);
                }
            }

            foreach ($selectedCpIds as $cpId) {
                if (!$outcomes->contains(fn (TenantLearningOutcome $outcome) => $outcome->parent_cp_id === $cpId)) {
                    throw ValidationException::withMessages([
                        'selectedCpIds' => 'Setiap CP yang dipilih harus memiliki minimal satu TP/ATP terpublikasi.',
                    ]);
                }
            }

            $material = null;
            $topic = null;
            if ($materialId) {
                $material = Material::query()
                    ->where('tenant_id', $tenantId)
                    ->where('teacher_subject_assignment_id', $lockedAssignment->id)
                    ->where('tenant_curriculum_registration_id', $registration->id)
                    ->where('status', 'PUBLISHED')
                    ->whereKey($materialId)
                    ->firstOrFail();
            }

            if ($topicId) {
                if (!$material) {
                    throw ValidationException::withMessages([
                        'selectedMaterialTopicId' => 'Pilih materi sebelum memilih topik.',
                    ]);
                }

                $topic = MaterialTopic::query()
                    ->where('tenant_id', $tenantId)
                    ->where('material_id', $material->id)
                    ->where('teacher_subject_assignment_id', $lockedAssignment->id)
                    ->where('tenant_curriculum_registration_id', $registration->id)
                    ->where('status', 'PUBLISHED')
                    ->whereKey($topicId)
                    ->firstOrFail();
            }

            AssessmentCurriculumMapping::query()
                ->where('tenant_id', $tenantId)
                ->where('assessment_id', $lockedAssessment->id)
                ->delete();

            foreach ($outcomes->values() as $index => $outcome) {
                AssessmentCurriculumMapping::query()->create([
                    'tenant_id' => $tenantId,
                    'assessment_id' => $lockedAssessment->id,
                    'teacher_subject_assignment_id' => $lockedAssignment->id,
                    'tenant_curriculum_registration_id' => $registration->id,
                    'multi_curriculum_version_id' => $registration->multi_curriculum_version_id,
                    'curriculum_owner_id' => $registration->curriculum_owner_id,
                    'multi_learning_outcome_id' => $outcome->parent_cp_id,
                    'tenant_learning_outcome_id' => $outcome->id,
                    'material_id' => $material?->id,
                    'material_topic_id' => $topic?->id,
                    'academic_year_id' => $lockedAssignment->academic_year_id,
                    'semester_id' => $lockedAssignment->semester_id,
                    'mapping_role' => $outcome->outcome_type,
                    'weight' => 1,
                    'is_primary' => $index === 0,
                    'created_by' => $user->id,
                    'metadata' => [
                        'cp_code' => $outcome->parentCp?->code,
                        'outcome_code' => $outcome->code,
                        'outcome_version' => $outcome->version_code,
                    ],
                ]);
            }

            $snapshot = $lockedAssignment->curriculum_context_snapshot ?? [];
            $lockedAssessment->update([
                'curriculum_context_snapshot' => [
                    ...$snapshot,
                    'assessment' => [
                        'activity_name' => $lockedAssessment->activity_name,
                        'assessment_origin' => $lockedAssessment->assessment_origin,
                        'assessment_function_option_id' => $lockedAssessment->assessment_function_option_id,
                        'assessment_scope_option_id' => $lockedAssessment->assessment_scope_option_id,
                        'instrument_type_option_id' => $lockedAssessment->instrument_type_option_id,
                        'delivery_mode_option_id' => $lockedAssessment->delivery_mode_option_id,
                        'cp_ids' => $cps->pluck('id')->all(),
                        'outcome_ids' => $outcomes->pluck('id')->all(),
                        'material_id' => $material?->id,
                        'material_topic_id' => $topic?->id,
                        'captured_at' => now()->toISOString(),
                    ],
                ],
            ]);

            app(AuditService::class)->log(
                'ASSESSMENT_CURRICULUM_MAPPED',
                Assessment::class,
                $lockedAssessment->id,
                null,
                [
                    'teacher_subject_assignment_id' => $lockedAssignment->id,
                    'cp_ids' => $cps->pluck('id')->all(),
                    'outcome_ids' => $outcomes->pluck('id')->all(),
                    'material_id' => $material?->id,
                    'material_topic_id' => $topic?->id,
                ]
            );

            return [
                'cp_count' => $cps->count(),
                'outcome_count' => $outcomes->count(),
                'material_id' => $material?->id,
                'topic_id' => $topic?->id,
            ];
        }, 3);
    }
}
