<?php

namespace Tests\Concerns;

use App\Contexts\TenantContext;
use App\Models\Assessment;
use App\Models\AssessmentCurriculumMapping;
use App\Models\AssessmentTaxonomyOption;
use App\Models\CurriculumReferenceSource;
use App\Models\LearningOutcomeStructureMapping;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\MultiLearningOutcome;
use App\Models\QuestionVersion;
use App\Models\QuestionVersionCurriculumMapping;
use App\Models\QuestionVersionStructureMapping;
use App\Models\QuestionVersionTenantOutcomeMapping;
use App\Models\TeacherSubjectAssignment;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;
use App\Models\TenantLearningOutcome;
use App\Models\User;
use Database\Seeders\AssessmentTaxonomySeeder;
use Database\Seeders\CurriculumReferenceSourceSeeder;
use Database\Seeders\CurriculumRegistryBootstrapSeeder;
use Database\Seeders\SystemTenantSeeder;

/**
 * Test-only fixture for the official curriculum/assessment contract.
 *
 * The CP source is synthetic and marked as a checksum-verified test artifact.
 * It never writes to production data or alters the official registry seeders.
 */
trait CreatesCanonicalCurriculumContext
{
    protected function createCanonicalCurriculumContext(
        TeacherSubjectAssignment $assignment,
        User $teacher,
        ?Assessment $assessment = null
    ): array {
        $tenantContext = app(TenantContext::class);
        $previousTenantId = $tenantContext->getTenantId();
        $tenantContext->setTenantId((string) $assignment->tenant_id);

        try {
            (new SystemTenantSeeder())->run();
            (new CurriculumReferenceSourceSeeder())->run();
            (new CurriculumRegistryBootstrapSeeder())->run();
            (new AssessmentTaxonomySeeder())->run();

            $systemTenantId = Tenant::SYSTEM_TENANT_ID;
            $version = MultiCurriculumVersion::query()
                ->where('tenant_id', $systemTenantId)
                ->where('version_code', 'KMA-1503-2025')
                ->firstOrFail();

            $assignment->loadMissing(['subject', 'rombel.gradeLevel']);
            $gradeNumber = (int) ($assignment->rombel?->gradeLevel?->level ?? 1);
            $phaseCode = $gradeNumber <= 2 ? 'A' : ($gradeNumber <= 4 ? 'B' : 'C');
            $localSubjectCode = strtoupper((string) ($assignment->subject?->code ?? ''));
            $officialSubjectCode = match ($localSubjectCode) {
                'MTK', 'MAT', 'MATH', 'MATEMATIKA' => 'MATEMATIKA',
                'IPA', 'IPAS', 'BIO', 'BIOLOGI' => 'IPAS',
                'PJOK', 'PENJAS' => 'PJOK',
                'BIND', 'BINDO', 'BAHASA_INDONESIA' => 'BAHASA_INDONESIA',
                'FIKIH', 'FIQIH' => 'FIKIH',
                'ARAB', 'BAHASA_ARAB' => 'BAHASA_ARAB',
                default => 'MATEMATIKA',
            };

            $subjectStructure = MultiCurriculumStructure::query()
                ->where('tenant_id', $systemTenantId)
                ->where('multi_curriculum_version_id', $version->id)
                ->where('node_type', 'SUBJECT')
                ->where('code', 'FASE_' . $phaseCode . '_' . $officialSubjectCode)
                ->firstOrFail();

            $registration = TenantCurriculumRegistration::query()->firstOrCreate([
                'tenant_id' => (string) $assignment->tenant_id,
                'multi_curriculum_version_id' => $version->id,
                'curriculum_owner_id' => $systemTenantId,
            ], [
                'status' => 'ACTIVE',
                'registered_at' => now(),
            ]);

            $contextSnapshot = [
                'curriculum_version' => [
                    'id' => $version->id,
                    'code' => $version->version_code,
                    'title' => $version->title,
                ],
                'phase' => [
                    'code' => $phaseCode,
                    'name' => 'Fase ' . $phaseCode,
                ],
                'grade' => [
                    'number' => $gradeNumber,
                    'name' => 'Kelas ' . $gradeNumber,
                ],
                'subject' => [
                    'id' => $assignment->subject_id,
                    'code' => $assignment->subject?->code,
                    'name' => $assignment->subject?->name,
                    'official_structure_id' => $subjectStructure->id,
                ],
            ];

            $assignment->forceFill([
                'tenant_curriculum_registration_id' => $registration->id,
                'multi_curriculum_version_id' => $version->id,
                'curriculum_owner_id' => $systemTenantId,
                'curriculum_context_snapshot' => $contextSnapshot,
                'starts_at' => $assignment->starts_at ?? now()->subDay()->toDateString(),
                'ends_at' => $assignment->ends_at ?? now()->addYears(5)->toDateString(),
            ])->save();

            $suffix = substr(str_replace('-', '', (string) $assignment->id), 0, 12);
            $checksum = hash('sha256', 'cbt-phpunit-verified-cp-fixture:' . $suffix);
            $cpSource = CurriculumReferenceSource::query()->create([
                'tenant_id' => $systemTenantId,
                'issuer' => 'CBT PHPUnit Fixture',
                'document_number' => 'PHPUNIT-CP-' . $suffix,
                'title' => 'Synthetic verified CP fixture',
                'document_version' => '1',
                'effective_from' => now()->toDateString(),
                'source_uri' => 'urn:cbt-phpunit:cp:' . $suffix,
                'checksum_sha256' => $checksum,
                'verification_status' => 'ARTIFACT_CHECKSUM_VERIFIED',
                'verified_at' => now(),
                'metadata' => ['test_fixture' => true],
            ]);

            $cp = MultiLearningOutcome::query()->create([
                'tenant_id' => $systemTenantId,
                'multi_curriculum_version_id' => $version->id,
                'multi_curriculum_structure_id' => $subjectStructure->id,
                'code' => 'CP-PHPUNIT-' . $suffix,
                'outcome_type' => 'CP',
                'description' => 'Synthetic verified CP used only by PHPUnit fixtures.',
                'status' => 'ACTIVE',
                'source_id' => $cpSource->id,
                'source_locator' => 'phpunit-fixture/' . $suffix,
                'verification_status' => 'ARTIFACT_CHECKSUM_VERIFIED',
                'verified_at' => now(),
                'source_checksum' => $checksum,
                'metadata' => ['test_fixture' => true],
                'is_official' => true,
                'is_editable' => false,
            ]);

            $tp = TenantLearningOutcome::query()->create([
                'tenant_id' => (string) $assignment->tenant_id,
                'tenant_curriculum_registration_id' => $registration->id,
                'multi_curriculum_version_id' => $version->id,
                'curriculum_owner_id' => $systemTenantId,
                'subject_id' => $assignment->subject_id,
                'academic_year_id' => $assignment->academic_year_id,
                'semester_id' => $assignment->semester_id,
                'teacher_subject_assignment_id' => $assignment->id,
                'parent_cp_id' => $cp->id,
                'outcome_type' => 'TP',
                'code' => 'TP-PHPUNIT-' . $suffix,
                'title' => 'Synthetic TP fixture',
                'description' => 'Synthetic TP linked to the verified PHPUnit CP fixture.',
                'sequence_number' => 1,
                'version_code' => '1',
                'is_current' => true,
                'status' => 'PUBLISHED',
                'author_user_id' => $teacher->id,
            ]);

            $frameworkDefinitions = [
                ['role' => 'PANCA_CINTA', 'code' => 'CINTA_ILMU'],
                ['role' => 'PM_PRINCIPLE', 'code' => 'BERMAKNA'],
                ['role' => 'DIMENSI_PROFIL_LULUSAN', 'code' => 'PENALARAN_KRITIS'],
            ];
            $framework = [];
            foreach ($frameworkDefinitions as $definition) {
                $node = MultiCurriculumStructure::query()
                    ->where('tenant_id', $systemTenantId)
                    ->where('multi_curriculum_version_id', $version->id)
                    ->where('code', $definition['code'])
                    ->firstOrFail();

                LearningOutcomeStructureMapping::query()->firstOrCreate([
                    'tenant_id' => (string) $assignment->tenant_id,
                    'tenant_learning_outcome_id' => $tp->id,
                    'teacher_subject_assignment_id' => $assignment->id,
                    'tenant_curriculum_registration_id' => $registration->id,
                    'multi_curriculum_version_id' => $version->id,
                    'curriculum_owner_id' => $systemTenantId,
                    'multi_curriculum_structure_id' => $node->id,
                    'mapping_role' => $definition['role'],
                ], [
                    'weight' => 1,
                    'created_by' => $teacher->id,
                ]);
                $framework[$definition['role']] = $node;
            }

            $taxonomy = [
                'function' => AssessmentTaxonomyOption::query()
                    ->where('tenant_id', $systemTenantId)->where('dimension', 'FUNCTION')->where('code', 'SUMATIF')->firstOrFail(),
                'scope' => AssessmentTaxonomyOption::query()
                    ->where('tenant_id', $systemTenantId)->where('dimension', 'SCOPE')->where('code', 'LINGKUP_MATERI')->firstOrFail(),
                'instrument' => AssessmentTaxonomyOption::query()
                    ->where('tenant_id', $systemTenantId)->where('dimension', 'INSTRUMENT')->where('code', 'TES_TERTULIS')->firstOrFail(),
                'delivery' => AssessmentTaxonomyOption::query()
                    ->where('tenant_id', $systemTenantId)->where('dimension', 'DELIVERY_MODE')->where('code', 'CBT')->firstOrFail(),
            ];

            if ($assessment) {
                $assessment->forceFill([
                    'teacher_subject_assignment_id' => $assignment->id,
                    'tenant_curriculum_registration_id' => $registration->id,
                    'curriculum_version_id' => $version->id,
                    'curriculum_owner_id' => $systemTenantId,
                    'curriculum_context_snapshot' => $contextSnapshot,
                    'assessment_taxonomy_owner_id' => $systemTenantId,
                    'assessment_function_option_id' => $taxonomy['function']->id,
                    'assessment_scope_option_id' => $taxonomy['scope']->id,
                    'instrument_type_option_id' => $taxonomy['instrument']->id,
                    'delivery_mode_option_id' => $taxonomy['delivery']->id,
                    'assessment_origin' => 'INTERNAL',
                    'activity_name' => 'Kegiatan Asesmen PHPUnit',
                    'assessment_type' => 'SUMATIF',
                    'description' => 'Fixture asesmen dengan konteks kurikulum terverifikasi untuk PHPUnit.',
                ])->save();

                AssessmentCurriculumMapping::query()->firstOrCreate([
                    'tenant_id' => (string) $assignment->tenant_id,
                    'assessment_id' => $assessment->id,
                    'teacher_subject_assignment_id' => $assignment->id,
                    'tenant_curriculum_registration_id' => $registration->id,
                    'multi_curriculum_version_id' => $version->id,
                    'curriculum_owner_id' => $systemTenantId,
                    'multi_learning_outcome_id' => $cp->id,
                    'tenant_learning_outcome_id' => $tp->id,
                    'mapping_role' => 'PRIMARY',
                ], [
                    'weight' => 1,
                    'is_primary' => true,
                    'created_by' => $teacher->id,
                ]);
            }

            return [
                'assignment' => $assignment->fresh(),
                'registration' => $registration,
                'version' => $version,
                'subject_structure' => $subjectStructure,
                'cp' => $cp,
                'tp' => $tp,
                'taxonomy' => $taxonomy,
                'framework' => $framework,
                'teacher' => $teacher,
            ];
        } finally {
            $tenantContext->setTenantId($previousTenantId);
        }
    }

    protected function mapQuestionVersionToCanonicalCurriculum(QuestionVersion $version, array $context): void
    {
        $assignment = $context['assignment'];
        $tenantContext = app(TenantContext::class);
        $previousTenantId = $tenantContext->getTenantId();
        $tenantContext->setTenantId((string) $assignment->tenant_id);

        try {
            QuestionVersionCurriculumMapping::query()->firstOrCreate([
                'tenant_id' => (string) $assignment->tenant_id,
                'question_version_id' => $version->id,
                'teacher_subject_assignment_id' => $assignment->id,
                'tenant_curriculum_registration_id' => $context['registration']->id,
                'multi_curriculum_version_id' => $context['version']->id,
                'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
                'multi_learning_outcome_id' => $context['cp']->id,
                'mapping_role' => 'CP',
            ], [
                'weight' => 1,
                'is_primary' => true,
                'created_by' => $context['teacher']->id,
            ]);

            QuestionVersionTenantOutcomeMapping::query()->firstOrCreate([
                'tenant_id' => (string) $assignment->tenant_id,
                'question_version_id' => $version->id,
                'teacher_subject_assignment_id' => $assignment->id,
                'tenant_curriculum_registration_id' => $context['registration']->id,
                'multi_curriculum_version_id' => $context['version']->id,
                'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
                'tenant_learning_outcome_id' => $context['tp']->id,
                'mapping_role' => 'TP',
            ], [
                'weight' => 1,
                'is_primary' => true,
                'created_by' => $context['teacher']->id,
            ]);

            foreach ($context['framework'] as $role => $node) {
                QuestionVersionStructureMapping::query()->firstOrCreate([
                    'tenant_id' => (string) $assignment->tenant_id,
                    'question_version_id' => $version->id,
                    'teacher_subject_assignment_id' => $assignment->id,
                    'tenant_curriculum_registration_id' => $context['registration']->id,
                    'multi_curriculum_version_id' => $context['version']->id,
                    'curriculum_owner_id' => Tenant::SYSTEM_TENANT_ID,
                    'multi_curriculum_structure_id' => $node->id,
                    'mapping_role' => $role,
                ], [
                    'weight' => 1,
                    'created_by' => $context['teacher']->id,
                ]);
            }
        } finally {
            $tenantContext->setTenantId($previousTenantId);
        }
    }
}
