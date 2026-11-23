<?php

namespace Tests\Concerns;

use App\Contexts\TenantContext;
use App\Models\Assessment;
use App\Models\MultiCurriculum;
use App\Models\MultiCurriculumVersion;
use App\Models\TeacherSubjectAssignment;
use App\Models\TenantCurriculumRegistration;
use App\Models\Tenant;
use Database\Seeders\SystemTenantSeeder;
use Illuminate\Support\Str;

/**
 * Test-only fixture helper for the canonical assignment → assessment contract.
 *
 * It deliberately creates real System-owned version/registration rows instead
 * of weakening TeacherAccess or creating nullable/legacy assessment fixtures.
 */
trait CreatesCanonicalTeacherAssignment
{
    /**
     * Bind an existing active assignment and assessment to one registered
     * curriculum version. CP/TP and question snapshot mappings remain explicit
     * in tests that exercise those separate contracts.
     *
     * @return array{curriculum: MultiCurriculum, version: MultiCurriculumVersion, registration: TenantCurriculumRegistration, context: array}
     */
    protected function bindCanonicalTeacherAssignment(
        TeacherSubjectAssignment $assignment,
        Assessment $assessment,
        array $contextOverrides = []
    ): array {
        $tenantId = (string) $assignment->tenant_id;
        $tenantContext = app(TenantContext::class);
        $previousTenantId = $tenantContext->getTenantId();
        $tenantContext->setTenantId($tenantId);

        try {
            $systemTenant = SystemTenantSeeder::seed();
            $suffix = strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 12));
            $curriculum = MultiCurriculum::query()->create([
                'tenant_id' => $systemTenant->id,
                'code' => 'TEST_' . $suffix,
                'name' => 'Kurikulum Uji Kanonis ' . $suffix,
                'description' => 'Fixture PHPUnit untuk relasi kurikulum resmi.',
                'status' => 'ACTIVE',
            ]);

            $version = MultiCurriculumVersion::query()->create([
                'tenant_id' => $systemTenant->id,
                'multi_curriculum_id' => $curriculum->id,
                'version_code' => 'TEST_' . $suffix,
                'title' => 'Versi Kurikulum Uji ' . $suffix,
                'effective_year' => (int) now()->year,
                'status' => 'ACTIVE',
                'is_official' => true,
                'is_editable' => false,
            ]);

            $registration = TenantCurriculumRegistration::query()->create([
                'tenant_id' => $tenantId,
                'multi_curriculum_version_id' => $version->id,
                'curriculum_owner_id' => $systemTenant->id,
                'status' => 'ACTIVE',
                'registered_at' => now(),
            ]);

            $context = array_merge([
                'curriculum_version_id' => $version->id,
                'curriculum_owner_id' => $systemTenant->id,
                'tenant_curriculum_registration_id' => $registration->id,
                'phase' => 'A',
                'grade' => 1,
                'subject' => [
                    'id' => $assignment->subject_id,
                ],
                'captured_at' => now()->toISOString(),
            ], $contextOverrides);

            $assignment->forceFill([
                'multi_curriculum_version_id' => $version->id,
                'curriculum_owner_id' => $systemTenant->id,
                'tenant_curriculum_registration_id' => $registration->id,
                'curriculum_context_snapshot' => $context,
            ])->save();

            $assessment->forceFill([
                'teacher_subject_assignment_id' => $assignment->id,
                'curriculum_version_id' => $version->id,
                'curriculum_owner_id' => $systemTenant->id,
                'tenant_curriculum_registration_id' => $registration->id,
                'curriculum_context_snapshot' => $context,
                'assessment_taxonomy_owner_id' => $systemTenant->id,
                'assessment_origin' => 'INTERNAL',
            ])->save();

            return compact('curriculum', 'version', 'registration', 'context');
        } finally {
            $tenantContext->setTenantId($previousTenantId);
        }
    }
}
