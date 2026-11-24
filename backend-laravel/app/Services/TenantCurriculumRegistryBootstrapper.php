<?php

namespace App\Services;

use App\Contexts\TenantContext;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\Tenant;
use Database\Seeders\AssessmentTaxonomySeeder;
use Database\Seeders\CurriculumReferenceSourceSeeder;
use Database\Seeders\CurriculumRegistryBootstrapSeeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Seeds the existing official reference registry into one standalone madrasah DB.
 * Unlike DatabaseSeeder, this path never creates a second System Tenant.
 */
class TenantCurriculumRegistryBootstrapper
{
    public function seedForTenant(string $tenantId): array
    {
        $tenantId = trim($tenantId);
        if ($tenantId === '') {
            throw new RuntimeException('Curriculum registry bootstrap requires an explicit tenant ID.');
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
                    throw new RuntimeException('Curriculum registry tenant does not exist.');
                }

                app(CurriculumReferenceSourceSeeder::class)->seedForTenant($tenantId);
                app(CurriculumRegistryBootstrapSeeder::class)->seedForTenant($tenantId);
                app(AssessmentTaxonomySeeder::class)->seedForTenant($tenantId);

                $version = MultiCurriculumVersion::query()
                    ->where('tenant_id', $tenantId)
                    ->where('version_code', 'KMA-1503-2025')
                    ->where('status', 'ACTIVE')
                    ->where('is_official', true)
                    ->where('is_editable', false)
                    ->first();

                if (!$version) {
                    throw new RuntimeException('Tenant curriculum registry version was not created.');
                }

                $expectedCounts = [
                    'PHASE' => 3,
                    'GRADE_LEVEL' => 6,
                    'PANCA_CINTA' => 5,
                    'PM_PRINCIPLE' => 4,
                    'PM_OLAH' => 4,
                    'PM_FRAMEWORK' => 4,
                    'DIMENSI_PROFIL_LULUSAN' => 8,
                ];

                foreach ($expectedCounts as $nodeType => $expectedCount) {
                    $actualCount = MultiCurriculumStructure::query()
                        ->where('tenant_id', $tenantId)
                        ->where('multi_curriculum_version_id', $version->id)
                        ->where('node_type', $nodeType)
                        ->where('status', 'ACTIVE')
                        ->where('is_official', true)
                        ->where('is_editable', false)
                        ->count();

                    if ($actualCount !== $expectedCount) {
                        throw new RuntimeException('Tenant curriculum registry node count mismatch: ' . $nodeType . '.');
                    }
                }

                $metadata = $version->metadata;
                if (!is_array($metadata)
                    || ($metadata['cp_content_imported'] ?? null) !== false
                    || ($metadata['artifact_checksum_verified'] ?? null) !== false) {
                    throw new RuntimeException('CP content must remain unimported until official artifacts are verified.');
                }

                return [
                    'tenant_id' => $tenantId,
                    'version_id' => $version->id,
                    'source_count' => DB::table('curriculum_reference_sources')->where('tenant_id', $tenantId)->count(),
                    'assessment_taxonomy_count' => DB::table('assessment_taxonomy_options')->where('tenant_id', $tenantId)->count(),
                    'node_counts' => $expectedCounts,
                    'cp_content_imported' => false,
                    'artifact_checksum_verified' => false,
                ];
            }, 3);
        } finally {
            $context->setTenantId($previousTenantId);
        }
    }
}
