<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\Exam;
use App\Models\ExamQuestionSnapshot;
use App\Models\MultiLearningOutcome;
use App\Models\QuestionVersion;
use App\Models\TeacherSubjectAssignment;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantLearningOutcome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamSnapshotService
{
    private function cpSourceVerified(?MultiLearningOutcome $cp): bool
    {
        return $cp
            && $cp->is_official
            && !$cp->is_editable
            && $cp->source
            && $cp->source->verification_status === 'ARTIFACT_CHECKSUM_VERIFIED'
            && $cp->source->checksum_sha256
            && $cp->source_checksum
            && hash_equals((string) $cp->source->checksum_sha256, (string) $cp->source_checksum);
    }

    private function frameworkNodeVerified($node): bool
    {
        return $node
            && $node->is_official
            && !$node->is_editable
            && $node->source
            && in_array($node->source->verification_status, ['METADATA_VERIFIED', 'ARTIFACT_CHECKSUM_VERIFIED'], true);
    }

    private function buildAssessmentSnapshot(Assessment $assessment, TeacherSubjectAssignment $assignment, $mappings): array
    {
        $firstMapping = $mappings->first();
        $cpRows = $mappings->map(fn ($mapping) => $mapping->curriculumOutcome)->filter()->unique('id')->values();
        $outcomeRows = $mappings->map(fn ($mapping) => $mapping->tenantOutcome)->filter()->unique('id')->values();

        return [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'activity_name' => $assessment->activity_name,
                'purpose' => $assessment->description,
                'assessment_function' => [
                    'id' => $assessment->assessmentFunctionOption?->id,
                    'code' => $assessment->assessmentFunctionOption?->code,
                    'title' => $assessment->assessmentFunctionOption?->name,
                ],
                'assessment_scope' => [
                    'id' => $assessment->assessmentScopeOption?->id,
                    'code' => $assessment->assessmentScopeOption?->code,
                    'title' => $assessment->assessmentScopeOption?->name,
                ],
                'instrument_type' => [
                    'id' => $assessment->instrumentTypeOption?->id,
                    'code' => $assessment->instrumentTypeOption?->code,
                    'title' => $assessment->instrumentTypeOption?->name,
                ],
                'delivery_mode' => [
                    'id' => $assessment->deliveryModeOption?->id,
                    'code' => $assessment->deliveryModeOption?->code,
                    'title' => $assessment->deliveryModeOption?->name,
                ],
                'origin' => $assessment->assessment_origin,
                'external_program_name' => $assessment->external_program_name,
                'legacy_assessment_type' => $assessment->assessment_type,
            ],
            'assignment' => [
                'id' => $assignment->id,
                'subject_id' => $assignment->subject_id,
                'subject_code' => $assignment->subject?->code,
                'subject_name' => $assignment->subject?->name,
                'rombel_id' => $assignment->rombel_id,
                'rombel_name' => $assignment->rombel?->name,
                'phase' => $assignment->curriculum_context_snapshot['phase'] ?? null,
                'grade' => $assignment->curriculum_context_snapshot['grade'] ?? null,
                'official_subject' => $assignment->curriculum_context_snapshot['subject'] ?? null,
                'academic_year_id' => $assignment->academic_year_id,
                'academic_year_name' => $assignment->academicYear?->name,
                'semester_id' => $assignment->semester_id,
                'semester_name' => $assignment->semester?->name,
            ],
            'curriculum' => [
                'registration_id' => $assignment->tenant_curriculum_registration_id,
                'owner_id' => $assignment->curriculum_owner_id,
                'version_id' => $assignment->multi_curriculum_version_id,
                'version_code' => $assignment->curriculumVersion?->version_code,
                'version_title' => $assignment->curriculumVersion?->title,
                'effective_year' => $assignment->curriculumVersion?->effective_year,
                'source_document' => $assignment->curriculumVersion?->source?->document_number,
                'source_verification_status' => $assignment->curriculumVersion?->source?->verification_status,
            ],
            'curriculum_mappings' => [
                'cp' => $cpRows->map(fn ($cp) => [
                    'id' => $cp->id,
                    'code' => $cp->code,
                    'title' => $cp->metadata['title'] ?? $cp->code,
                    'description' => $cp->description,
                    'source_document' => $cp->source?->document_number,
                    'source_checksum' => $cp->source_checksum,
                ])->all(),
                'tp_atp' => $outcomeRows->map(fn ($outcome) => [
                    'id' => $outcome->id,
                    'type' => $outcome->outcome_type,
                    'code' => $outcome->code,
                    'title' => $outcome->title,
                    'description' => $outcome->description,
                    'version_code' => $outcome->version_code,
                    'parent_cp_id' => $outcome->parent_cp_id,
                    'parent_cp_code' => $outcome->parentCp?->code,
                ])->all(),
                'material_id' => $firstMapping?->material_id,
                'material_code' => $firstMapping?->material?->material_code,
                'material_name' => $firstMapping?->material?->name,
                'material_topic_id' => $firstMapping?->material_topic_id,
                'material_topic_code' => $firstMapping?->materialTopic?->code,
                'material_topic_title' => $firstMapping?->materialTopic?->title,
            ],
            'captured_at' => now()->toISOString(),
        ];
    }

    private function buildQuestionCurriculumSnapshot(
        QuestionVersion $version,
        TeacherSubjectAssignment $assignment,
        $cpMappings,
        $outcomeMappings,
        $structureMappings
    ): array {
        $firstMapping = $cpMappings->first() ?? $outcomeMappings->first();

        return [
            'assignment_id' => $assignment->id,
            'academic_year_id' => $assignment->academic_year_id,
            'academic_year_name' => $assignment->academicYear?->name,
            'semester_id' => $assignment->semester_id,
            'semester_name' => $assignment->semester?->name,
            'curriculum_registration_id' => $assignment->tenant_curriculum_registration_id,
            'curriculum_owner_id' => $assignment->curriculum_owner_id,
            'curriculum_version_id' => $assignment->multi_curriculum_version_id,
            'curriculum_version_code' => $assignment->curriculumVersion?->version_code,
            'curriculum_version_title' => $assignment->curriculumVersion?->title,
            'phase' => $assignment->curriculum_context_snapshot['phase'] ?? null,
            'grade' => $assignment->curriculum_context_snapshot['grade'] ?? null,
            'subject' => $assignment->curriculum_context_snapshot['subject'] ?? null,
            'cp' => $cpMappings->map(fn ($mapping) => [
                'id' => $mapping->learningOutcome?->id,
                'code' => $mapping->learningOutcome?->code,
                'title' => $mapping->learningOutcome?->metadata['title'] ?? $mapping->learningOutcome?->code,
                'description' => $mapping->learningOutcome?->description,
                'source_document' => $mapping->learningOutcome?->source?->document_number,
                'source_checksum' => $mapping->learningOutcome?->source_checksum,
            ])->values()->all(),
            'tp_atp' => $outcomeMappings->map(fn ($mapping) => [
                'id' => $mapping->learningOutcome?->id,
                'type' => $mapping->mapping_role,
                'code' => $mapping->learningOutcome?->code,
                'title' => $mapping->learningOutcome?->title,
                'description' => $mapping->learningOutcome?->description,
                'version_code' => $mapping->learningOutcome?->version_code,
                'parent_cp_id' => $mapping->learningOutcome?->parent_cp_id,
                'parent_cp_code' => $mapping->learningOutcome?->parentCp?->code,
            ])->values()->all(),
            'framework' => $structureMappings->map(fn ($mapping) => [
                'id' => $mapping->structure?->id,
                'node_type' => $mapping->structure?->node_type,
                'code' => $mapping->structure?->code,
                'title' => $mapping->structure?->title,
                'source_document' => $mapping->structure?->source?->document_number,
                'mapping_role' => $mapping->mapping_role,
            ])->values()->all(),
            'material_id' => $firstMapping?->material_id,
            'material_code' => $firstMapping?->material?->material_code,
            'material_name' => $firstMapping?->material?->name,
            'material_topic_id' => $firstMapping?->material_topic_id,
            'material_topic_code' => $firstMapping?->materialTopic?->code,
            'material_topic_title' => $firstMapping?->materialTopic?->title,
            'captured_at' => now()->toISOString(),
        ];
    }

    public function createSnapshots(Exam $exam, array $questionVersionIds, bool $append = false): void
    {
        DB::transaction(function () use ($exam, $questionVersionIds, $append): void {
            $lockedExam = Exam::query()
                ->where('tenant_id', $exam->tenant_id)
                ->whereKey($exam->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$lockedExam->canBeEdited()) {
                abort(409, 'Ujian sudah diterbitkan dan snapshot tidak dapat diubah.');
            }

            $assessment = Assessment::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->whereKey($lockedExam->assessment_id)
                ->with([
                    'subject',
                    'academicYear',
                    'semester',
                    'assessmentFunctionOption',
                    'assessmentScopeOption',
                    'instrumentTypeOption',
                    'deliveryModeOption',
                    'curriculumRegistration.version.source',
                    'teacherSubjectAssignment.subject',
                    'teacherSubjectAssignment.rombel.gradeLevel.phase',
                    'teacherSubjectAssignment.academicYear',
                    'teacherSubjectAssignment.semester',
                    'teacherSubjectAssignment.curriculumVersion.source',
                    'curriculumMappings.curriculumOutcome.source',
                    'curriculumMappings.tenantOutcome.parentCp.source',
                    'curriculumMappings.material',
                    'curriculumMappings.materialTopic',
                ])
                ->firstOrFail();

            $assignment = TeacherSubjectAssignment::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->whereKey($assessment->teacher_subject_assignment_id)
                ->where('subject_id', $assessment->subject_id)
                ->where('academic_year_id', $assessment->academic_year_id)
                ->where('semester_id', $assessment->semester_id)
                ->where('tenant_curriculum_registration_id', $assessment->tenant_curriculum_registration_id)
                ->where('multi_curriculum_version_id', $assessment->curriculum_version_id)
                ->where('curriculum_owner_id', $assessment->curriculum_owner_id)
                ->with([
                    'subject',
                    'rombel.gradeLevel.phase',
                    'academicYear',
                    'semester',
                    'curriculumVersion.source',
                ])
                ->lockForUpdate()
                ->firstOrFail();

            $registration = TenantCurriculumRegistration::query()
                ->where('tenant_id', $lockedExam->tenant_id)
                ->whereKey($assignment->tenant_curriculum_registration_id)
                ->where('multi_curriculum_version_id', $assignment->multi_curriculum_version_id)
                ->where('curriculum_owner_id', $assignment->curriculum_owner_id)
                ->where('status', 'ACTIVE')
                ->with('version.source')
                ->firstOrFail();

            if (!$assessment->assessmentFunctionOption
                || !$assessment->assessmentScopeOption
                || !$assessment->instrumentTypeOption
                || !$assessment->deliveryModeOption
                || !$assessment->activity_name
                || !$assessment->teacher_subject_assignment_id
                || !$assessment->curriculum_context_snapshot) {
                abort(422, 'Konteks taksonomi, penugasan, dan kurikulum asesmen belum lengkap.');
            }

            $assessmentMappings = $assessment->curriculumMappings;
            if ($assessmentMappings->isEmpty()) {
                abort(422, 'Asesmen belum dipetakan ke CP dan TP/ATP resmi.');
            }

            foreach ($assessmentMappings as $mapping) {
                if (!$this->cpSourceVerified($mapping->curriculumOutcome)
                    || !$mapping->tenantOutcome
                    || $mapping->tenantOutcome->status !== 'PUBLISHED'
                    || !$mapping->tenantOutcome->is_current
                    || $mapping->tenantOutcome->parent_cp_id !== $mapping->multi_learning_outcome_id) {
                    abort(422, 'Pemetaan CP/TP/ATP asesmen tidak lagi memenuhi verifikasi dan status publikasi.');
                }
            }

            $assessmentCpIds = $assessmentMappings->pluck('multi_learning_outcome_id')->unique()->values();
            $assessmentOutcomeIds = $assessmentMappings->pluck('tenant_learning_outcome_id')->unique()->values();
            $firstAssessmentMapping = $assessmentMappings->first();

            $examStart = $lockedExam->start_at;
            $examEnd = $lockedExam->end_at;
            if ($assignment->starts_at && $examStart && $examStart->toDateString() < $assignment->starts_at->toDateString()) {
                abort(422, 'Jadwal ujian berada sebelum masa penugasan Guru.');
            }
            if ($assignment->ends_at && $examEnd && $examEnd->toDateString() > $assignment->ends_at->toDateString()) {
                abort(422, 'Jadwal ujian melewati masa penugasan Guru.');
            }

            $existingSnapshotIds = $lockedExam->snapshots()->pluck('id')->all();
            $hasSnapshotHistory = $lockedExam->attempts()->exists();
            if (!$hasSnapshotHistory && $existingSnapshotIds !== []) {
                $hasSnapshotHistory = DB::table('exam_attempt_answers')
                    ->where('tenant_id', $lockedExam->tenant_id)
                    ->where('exam_id', $lockedExam->id)
                    ->whereIn('exam_question_snapshot_id', $existingSnapshotIds)
                    ->exists();
            }
            if (!$hasSnapshotHistory && $existingSnapshotIds !== []) {
                $hasSnapshotHistory = DB::table('exam_question_scores')
                    ->where('tenant_id', $lockedExam->tenant_id)
                    ->where('exam_id', $lockedExam->id)
                    ->whereIn('snapshot_id', $existingSnapshotIds)
                    ->exists();
            }
            if ($hasSnapshotHistory) {
                abort(409, 'Snapshot tidak dapat diubah setelah riwayat attempt, jawaban, atau skor tersedia.');
            }

            if (!$append) {
                $lockedExam->snapshots()->delete();
                $nextSequence = 0;
            } else {
                $nextSequence = ((int) $lockedExam->snapshots()->max('sequence')) + 1;
            }

            $assessmentContext = $this->buildAssessmentSnapshot($assessment, $assignment, $assessmentMappings);
            $lockedExam->update(['assessment_context_snapshot' => $assessmentContext]);

            foreach ($questionVersionIds as $versionId) {
                $version = QuestionVersion::query()
                    ->where('tenant_id', $lockedExam->tenant_id)
                    ->whereKey($versionId)
                    ->where('status', 'PUBLISHED')
                    ->whereHas('question', function ($query) use ($lockedExam, $assignment): void {
                        $query->where('tenant_id', $lockedExam->tenant_id)
                            ->where('subject_id', $assignment->subject_id)
                            ->where('status', 'PUBLISHED');
                    })
                    ->with([
                        'question',
                        'options' => fn ($query) => $query->orderBy('sequence'),
                        'media',
                        'curriculumMappings.learningOutcome.source',
                        'curriculumMappings.material',
                        'curriculumMappings.materialTopic',
                        'tenantOutcomeMappings.learningOutcome.parentCp.source',
                        'tenantOutcomeMappings.learningOutcome.structureMappings.structure.source',
                        'tenantOutcomeMappings.material',
                        'tenantOutcomeMappings.materialTopic',
                        'structureMappings.structure.source',
                    ])
                    ->lockForUpdate()
                    ->firstOrFail();

                $cpMappings = $version->curriculumMappings
                    ->where('teacher_subject_assignment_id', $assignment->id)
                    ->values();
                $outcomeMappings = $version->tenantOutcomeMappings
                    ->where('teacher_subject_assignment_id', $assignment->id)
                    ->values();
                $structureMappings = $version->structureMappings
                    ->where('teacher_subject_assignment_id', $assignment->id)
                    ->values();

                if ($cpMappings->isEmpty() || $outcomeMappings->isEmpty()) {
                    abort(422, 'Setiap soal wajib memiliki pemetaan CP dan TP/ATP untuk penugasan ujian ini.');
                }

                foreach ($cpMappings as $mapping) {
                    if (!$this->cpSourceVerified($mapping->learningOutcome)
                        || !$assessmentCpIds->contains($mapping->multi_learning_outcome_id)) {
                        abort(422, 'Pemetaan CP soal tidak cocok dengan asesmen atau sumber CP tidak terverifikasi.');
                    }
                }

                foreach ($outcomeMappings as $mapping) {
                    $outcome = $mapping->learningOutcome;
                    if (!$outcome
                        || $outcome->status !== 'PUBLISHED'
                        || !$outcome->is_current
                        || !$assessmentOutcomeIds->contains($outcome->id)
                        || !$cpMappings->contains(fn ($cpMapping) => $cpMapping->multi_learning_outcome_id === $outcome->parent_cp_id)) {
                        abort(422, 'Pemetaan TP/ATP soal tidak cocok dengan asesmen atau tidak lagi terpublikasi.');
                    }
                }

                $frameworkRoles = $structureMappings->pluck('mapping_role')->unique()->all();
                foreach (['PANCA_CINTA', 'PM_PRINCIPLE', 'DIMENSI_PROFIL_LULUSAN'] as $requiredRole) {
                    if (!in_array($requiredRole, $frameworkRoles, true)) {
                        abort(422, 'Snapshot soal wajib membawa pemetaan Panca Cinta, Pembelajaran Mendalam, dan dimensi profil lulusan.');
                    }
                }

                foreach ($structureMappings as $mapping) {
                    if (!$this->frameworkNodeVerified($mapping->structure)) {
                        abort(422, 'Node Panca Cinta/PM/profil lulusan pada snapshot tidak memiliki provenance resmi yang valid.');
                    }
                }

                $materialId = $firstAssessmentMapping->material_id;
                $topicId = $firstAssessmentMapping->material_topic_id;
                if ($materialId) {
                    $questionMaterialIds = $cpMappings->pluck('material_id')
                        ->merge($outcomeMappings->pluck('material_id'))->unique()->filter()->values();
                    if (!$questionMaterialIds->contains($materialId)) {
                        abort(422, 'Materi pada soal tidak sesuai dengan materi asesmen.');
                    }
                }
                if ($topicId) {
                    $questionTopicIds = $cpMappings->pluck('material_topic_id')
                        ->merge($outcomeMappings->pluck('material_topic_id'))->unique()->filter()->values();
                    if (!$questionTopicIds->contains($topicId)) {
                        abort(422, 'Topik pada soal tidak sesuai dengan topik asesmen.');
                    }
                }

                if ($lockedExam->snapshots()->where('source_question_id', $version->question_id)->exists()) {
                    continue;
                }

                $media = $version->media()
                    ->where('media_assets.tenant_id', $lockedExam->tenant_id)
                    ->orderBy('media_assets.id')
                    ->lockForUpdate()
                    ->get();

                $questionContext = $this->buildQuestionCurriculumSnapshot(
                    $version,
                    $assignment,
                    $cpMappings,
                    $outcomeMappings,
                    $structureMappings
                );

                $snapshot = new ExamQuestionSnapshot([
                    'id' => (string) Str::uuid(),
                    'source_question_id' => $version->question_id,
                    'source_question_version_id' => $version->id,
                    'sequence' => $nextSequence,
                    'points' => $version->default_points,
                    'snapshot_stem' => $version->stem,
                    'snapshot_explanation' => $version->explanation,
                    'snapshot_options' => $version->options->map(fn ($option) => [
                        'id' => $option->id,
                        'content' => $option->content,
                        'is_correct' => $option->is_correct,
                        'score_weight' => $option->score_weight,
                        'option_key' => $option->option_key,
                        'matching_key' => $option->matching_key,
                    ])->all(),
                    'snapshot_media' => $media->map(fn ($asset) => [
                        'id' => $asset->id,
                        'storage_path' => $asset->storage_path,
                        'role' => $asset->pivot->role,
                        'mime_type' => $asset->mime_type,
                    ])->all(),
                    'metadata' => [
                        'difficulty' => $version->difficulty,
                        'cognitive_level' => $version->cognitive_level,
                        'question_type' => $version->question_type,
                    ],
                    'curriculum_context_snapshot' => $questionContext,
                ]);

                $snapshot->exam_id = $lockedExam->id;
                $snapshot->tenant_id = $lockedExam->tenant_id;
                $snapshot->save();
                $nextSequence++;
            }

            if ($lockedExam->snapshots()->count() === 0) {
                abort(422, 'Ujian tidak dapat disimpan tanpa minimal satu snapshot soal yang valid.');
            }
        });
    }
}
