<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\Phase;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Provisions the legacy tenant-owned academic FK catalog required by Rombel.
 *
 * The canonical phase/grade curriculum structure remains in the immutable
 * system-owned MultiCurriculum registry. These tenant-owned Curriculum,
 * Phase, and GradeLevel rows are only the local relational bridge used by the
 * existing academic tables; they do not contain or invent CP content.
 */
class TenantAcademicBootstrapService
{
    private const PHASES = [
        'A' => [1, 2],
        'B' => [3, 4],
        'C' => [5, 6],
    ];

    public function provisionForTenant(string $tenantId): array
    {
        $tenantId = trim($tenantId);
        if ($tenantId === '') {
            throw new RuntimeException('Academic bootstrap requires an explicit tenant ID.');
        }

        $context = app(TenantContext::class);
        $previousTenantId = $context->getTenantId();
        $context->setTenantId($tenantId);

        try {
            return DB::transaction(function () use ($tenantId): array {
                $tenant = Tenant::withoutGlobalScopes()
                    ->whereKey($tenantId)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (!$tenant) {
                    throw new RuntimeException('Academic bootstrap tenant does not exist or is inactive.');
                }

                $version = $this->assertCanonicalRegistryReady();
                $curriculum = $this->resolveLocalCurriculum($tenantId);

                $phaseIds = [];
                $gradeLevelIds = [];

                foreach (self::PHASES as $phaseCode => $grades) {
                    $canonicalPhase = MultiCurriculumStructure::query()
                        ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
                        ->where('multi_curriculum_version_id', $version->id)
                        ->where('node_type', 'PHASE')
                        ->where('code', 'FASE_' . $phaseCode)
                        ->where('status', 'ACTIVE')
                        ->where('is_official', true)
                        ->where('is_editable', false)
                        ->first();

                    if (!$canonicalPhase) {
                        throw new RuntimeException('Canonical phase registry is incomplete for Fase ' . $phaseCode . '.');
                    }

                    $phase = Phase::query()
                        ->where('tenant_id', $tenantId)
                        ->where('code', 'FASE_' . $phaseCode)
                        ->lockForUpdate()
                        ->first();

                    if (!$phase) {
                        $phase = Phase::create([
                            'curriculum_id' => $curriculum->id,
                            'name' => 'Fase ' . $phaseCode,
                            'code' => 'FASE_' . $phaseCode,
                            'description' => 'Pemetaan fase MI sesuai KMA 1503 Tahun 2025.',
                            'status' => 'ACTIVE',
                        ]);
                    }

                    if ((string) $phase->tenant_id !== $tenantId
                        || (string) $phase->curriculum_id !== (string) $curriculum->id
                        || $phase->code !== 'FASE_' . $phaseCode
                        || $phase->name !== 'Fase ' . $phaseCode
                        || $phase->status !== 'ACTIVE') {
                        throw new RuntimeException('Existing tenant phase conflicts with the canonical MI phase mapping.');
                    }

                    $phaseIds[$phaseCode] = $phase->id;

                    foreach ($grades as $gradeNumber) {
                        $gradeCode = 'MI_' . $gradeNumber;
                        $canonicalGrade = MultiCurriculumStructure::query()
                            ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
                            ->where('multi_curriculum_version_id', $version->id)
                            ->where('node_type', 'GRADE_LEVEL')
                            ->where('code', $gradeCode)
                            ->where('parent_id', $canonicalPhase->id)
                            ->where('status', 'ACTIVE')
                            ->where('is_official', true)
                            ->where('is_editable', false)
                            ->first();

                        if (!$canonicalGrade) {
                            throw new RuntimeException('Canonical grade registry is incomplete for ' . $gradeCode . '.');
                        }

                        $gradeLevel = GradeLevel::query()
                            ->where('tenant_id', $tenantId)
                            ->where('code', $gradeCode)
                            ->lockForUpdate()
                            ->first();

                        if (!$gradeLevel) {
                            $gradeLevel = GradeLevel::create([
                                'phase_id' => $phase->id,
                                'name' => 'Kelas ' . $gradeNumber . ' MI',
                                'code' => $gradeCode,
                                'level' => $gradeNumber,
                                'status' => 'ACTIVE',
                            ]);
                        }

                        if ((string) $gradeLevel->tenant_id !== $tenantId
                            || (string) $gradeLevel->phase_id !== (string) $phase->id
                            || $gradeLevel->code !== $gradeCode
                            || (int) $gradeLevel->level !== $gradeNumber
                            || $gradeLevel->name !== 'Kelas ' . $gradeNumber . ' MI'
                            || $gradeLevel->status !== 'ACTIVE') {
                            throw new RuntimeException('Existing tenant grade level conflicts with the canonical MI grade/phase mapping.');
                        }

                        $gradeLevelIds[$gradeNumber] = $gradeLevel->id;
                    }
                }

                return [
                    'tenant_id' => $tenantId,
                    'curriculum_id' => $curriculum->id,
                    'phase_ids' => $phaseIds,
                    'grade_level_ids' => $gradeLevelIds,
                    'phase_count' => count($phaseIds),
                    'grade_level_count' => count($gradeLevelIds),
                    'registry_version_id' => $version->id,
                ];
            }, 3);
        } finally {
            $context->setTenantId($previousTenantId);
        }
    }

    private function resolveLocalCurriculum(string $tenantId): Curriculum
    {
        $curriculum = Curriculum::withTrashed()
            ->where('tenant_id', $tenantId)
            ->where('code', 'KURIKULUM_MERDEKA')
            ->lockForUpdate()
            ->first();

        if (!$curriculum) {
            return Curriculum::create([
                'name' => 'Kurikulum Merdeka',
                'code' => 'KURIKULUM_MERDEKA',
                'description' => 'Katalog akademik lokal untuk menghubungkan kelas MI dengan registry kurikulum resmi sistem.',
                'status' => 'ACTIVE',
                'is_active' => true,
            ]);
        }

        if ($curriculum->trashed()
            || (string) $curriculum->tenant_id !== $tenantId
            || $curriculum->name !== 'Kurikulum Merdeka'
            || $curriculum->status !== 'ACTIVE') {
            throw new RuntimeException('Existing tenant curriculum catalog conflicts with the required MI academic catalog.');
        }

        return $curriculum;
    }

    private function assertCanonicalRegistryReady(): MultiCurriculumVersion
    {
        $version = MultiCurriculumVersion::query()
            ->where('tenant_id', Tenant::SYSTEM_TENANT_ID)
            ->where('version_code', 'KMA-1503-2025')
            ->where('status', 'ACTIVE')
            ->where('is_official', true)
            ->where('is_editable', false)
            ->first();

        if (!$version) {
            throw new RuntimeException('System-owned KMA 1503/2025 curriculum registry has not been seeded.');
        }

        return $version;
    }
}
