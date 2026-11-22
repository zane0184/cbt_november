<?php

namespace App\Services\TeacherPortal;

use App\Models\MultiCurriculumStructure;
use App\Models\MultiLearningOutcome;
use App\Models\Tenant;
use App\Models\TenantCurriculumRegistration;

class TeacherCurriculumContextService
{
    private const OUTCOME_TYPES = ['CP', 'TP', 'ATP', 'KBC_CINTA'];

    public function forTenant(string $tenantId): array
    {
        $allowedOwners = [$tenantId, Tenant::SYSTEM_TENANT_ID];

        $registrations = TenantCurriculumRegistration::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->whereIn('curriculum_owner_id', $allowedOwners)
            ->with(['version.curriculum'])
            ->orderBy('registered_at')
            ->get()
            ->filter(function (TenantCurriculumRegistration $registration): bool {
                $version = $registration->version;
                $curriculum = $version?->curriculum;
                $ownerId = (string) $registration->curriculum_owner_id;

                return $version
                    && $curriculum
                    && (string) $version->tenant_id === $ownerId
                    && (string) $curriculum->tenant_id === $ownerId
                    && $version->status === 'ACTIVE'
                    && $curriculum->status === 'ACTIVE';
            })
            ->values();

        $versions = [];
        $outcomeRows = [];
        $counts = [
            'CP' => 0,
            'TP' => 0,
            'ATP' => 0,
            'KBC_CINTA' => 0,
            'DIMENSI_CINTA' => 0,
            'PETA_KASIH' => 0,
        ];

        foreach ($registrations as $registration) {
            $version = $registration->version;
            $curriculum = $version->curriculum;
            $ownerId = (string) $registration->curriculum_owner_id;

            $versions[] = [
                'curriculum_code' => $curriculum->code,
                'curriculum_name' => $curriculum->name,
                'version_code' => $version->version_code,
                'version_title' => $version->title,
                'effective_year' => $version->effective_year,
                'owner_type' => $ownerId === Tenant::SYSTEM_TENANT_ID ? 'GLOBAL' : 'TENANT',
            ];

            $outcomesQuery = MultiLearningOutcome::query()
                ->where('tenant_id', $ownerId)
                ->where('multi_curriculum_version_id', $version->id)
                ->where('status', 'ACTIVE')
                ->whereIn('outcome_type', self::OUTCOME_TYPES)
                ->whereHas('structure', function ($query) use ($ownerId, $version): void {
                    $query->where('tenant_id', $ownerId)
                        ->where('multi_curriculum_version_id', $version->id)
                        ->where('status', 'ACTIVE');
                });

            $typeCounts = (clone $outcomesQuery)
                ->selectRaw('outcome_type, COUNT(*) as aggregate')
                ->groupBy('outcome_type')
                ->pluck('aggregate', 'outcome_type');

            foreach (self::OUTCOME_TYPES as $type) {
                $counts[$type] += (int) ($typeCounts[$type] ?? 0);
            }

            $outcomes = (clone $outcomesQuery)
                ->orderBy('outcome_type')
                ->orderBy('code')
                ->limit(100)
                ->get(['id', 'code', 'outcome_type', 'description']);

            foreach ($outcomes as $outcome) {
                $outcomeRows[] = [
                    'type' => $outcome->outcome_type,
                    'code' => $outcome->code,
                    'description' => $outcome->description,
                    'version_title' => $version->title,
                ];
            }

            $structureCounts = MultiCurriculumStructure::query()
                ->where('tenant_id', $ownerId)
                ->where('multi_curriculum_version_id', $version->id)
                ->where('status', 'ACTIVE')
                ->whereIn('node_type', ['DIMENSI_CINTA', 'PETA_KASIH'])
                ->selectRaw('node_type, COUNT(*) as aggregate')
                ->groupBy('node_type')
                ->pluck('aggregate', 'node_type');

            $counts['DIMENSI_CINTA'] += (int) ($structureCounts['DIMENSI_CINTA'] ?? 0);
            $counts['PETA_KASIH'] += (int) ($structureCounts['PETA_KASIH'] ?? 0);
        }

        return [
            'versions' => $versions,
            'version_count' => count($versions),
            'counts' => $counts,
            'outcomes' => $outcomeRows,
            'panca_cinta' => [
                'Cinta Allah dan Rasul-Nya',
                'Cinta Ilmu',
                'Cinta Lingkungan',
                'Cinta Diri dan Sesama Manusia',
                'Cinta Tanah Air',
            ],
            'has_registered_reference' => $registrations->isNotEmpty(),
            'official_cp_verified' => false,
            'official_cp_note' => 'Skema baseline belum menyimpan metadata provenance sumber resmi CP. Data tersimpan tidak otomatis dinyatakan sebagai CP resmi terverifikasi.',
            'deep_learning_available' => false,
            'graduate_dimensions_available' => false,
            'integration_note' => 'Pemetaan Panca Cinta, Pembelajaran Mendalam, dan delapan dimensi profil lulusan hanya ditampilkan sebagai status integrasi. Data referensi tidak dibuat atau ditebak oleh Portal Guru.',
        ];
    }
}
