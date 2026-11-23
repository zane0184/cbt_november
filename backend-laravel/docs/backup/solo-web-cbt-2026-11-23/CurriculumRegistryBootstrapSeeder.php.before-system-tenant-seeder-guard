<?php

namespace Database\Seeders;

use App\Models\CurriculumReferenceSource;
use App\Models\MultiCurriculum;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurriculumRegistryBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $tenantId = Tenant::SYSTEM_TENANT_ID;

            $curriculum = MultiCurriculum::query()->updateOrCreate(
                ['tenant_id' => $tenantId, 'code' => 'KURIKULUM_MERDEKA'],
                [
                    'name' => 'Kurikulum Merdeka',
                    'description' => 'Kerangka kurikulum nasional yang diterapkan pada madrasah sesuai regulasi yang berlaku.',
                    'status' => 'ACTIVE',
                ]
            );

            $kma = CurriculumReferenceSource::query()
                ->where('tenant_id', $tenantId)
                ->where('document_number', 'KMA 1503 Tahun 2025')
                ->firstOrFail();
            $kbc = CurriculumReferenceSource::query()
                ->where('tenant_id', $tenantId)
                ->where('document_number', 'Kepdirjen Pendis 6077 Tahun 2025')
                ->firstOrFail();
            $pm = CurriculumReferenceSource::query()
                ->where('tenant_id', $tenantId)
                ->where('document_number', 'Kepmendikdasmen 126/P/2025')
                ->firstOrFail();
            $dpl = CurriculumReferenceSource::query()
                ->where('tenant_id', $tenantId)
                ->where('document_number', 'Permendikdasmen 10 Tahun 2025')
                ->firstOrFail();

            $version = MultiCurriculumVersion::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'multi_curriculum_id' => $curriculum->id,
                    'version_code' => 'KMA-1503-2025',
                ],
                [
                    'title' => 'Kurikulum Merdeka Madrasah — KMA 1503 Tahun 2025',
                    'effective_year' => 2025,
                    'status' => 'ACTIVE',
                    'source_id' => $kma->id,
                    'source_locator' => 'KMA 1503/2025, Bab II dan Bab III',
                    'effective_from' => '2025-09-22',
                    'verification_status' => 'METADATA_VERIFIED',
                    'verified_at' => now(),
                    'metadata' => [
                        'scope' => 'MADRASAH',
                        'phase_structure' => ['A' => [1, 2], 'B' => [3, 4], 'C' => [5, 6]],
                        'artifact_checksum_verified' => false,
                        'cp_content_imported' => false,
                    ],
                ]
            );

            $upsertNode = function (
                string $nodeType,
                string $code,
                string $title,
                ?string $parentId,
                CurriculumReferenceSource $source,
                string $sourceLocator,
                array $metadata = []
            ) use ($tenantId, $version): MultiCurriculumStructure {
                return MultiCurriculumStructure::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'multi_curriculum_version_id' => $version->id,
                        'node_type' => $nodeType,
                        'code' => $code,
                    ],
                    [
                        'parent_id' => $parentId,
                        'title' => $title,
                        'sort_order' => $metadata['sort_order'] ?? 0,
                        'status' => 'ACTIVE',
                        'source_id' => $source->id,
                        'source_locator' => $sourceLocator,
                        'effective_from' => $source->effective_from,
                        'effective_until' => $source->effective_until,
                        'verification_status' => $source->verification_status,
                        'verified_at' => $source->verified_at ?? now(),
                        'source_checksum' => $source->checksum_sha256,
                        'metadata' => $metadata,
                    ]
                );
            };

            $phases = [
                'A' => ['title' => 'Fase A', 'grades' => [1, 2], 'locator' => 'KMA 1503/2025, Struktur Kurikulum MI'],
                'B' => ['title' => 'Fase B', 'grades' => [3, 4], 'locator' => 'KMA 1503/2025, Struktur Kurikulum MI'],
                'C' => ['title' => 'Fase C', 'grades' => [5, 6], 'locator' => 'KMA 1503/2025, Struktur Kurikulum MI'],
            ];

            $phaseNodes = [];
            foreach ($phases as $phaseCode => $phase) {
                $phaseNode = $upsertNode(
                    'PHASE',
                    'FASE_' . $phaseCode,
                    $phase['title'],
                    null,
                    $kma,
                    $phase['locator'],
                    ['sort_order' => ord($phaseCode) - 64, 'grades' => $phase['grades']]
                );
                $phaseNodes[$phaseCode] = $phaseNode;

                foreach ($phase['grades'] as $grade) {
                    $upsertNode(
                        'GRADE_LEVEL',
                        'MI_' . $grade,
                        'Kelas ' . $grade . ' MI',
                        $phaseNode->id,
                        $kma,
                        'KMA 1503/2025, Fase ' . $phaseCode . ' MI',
                        ['sort_order' => $grade, 'grade_number' => $grade, 'phase_code' => $phaseCode]
                    );
                }
            }

            $pancaCinta = [
                ['CINTA_ALLAH_RASUL', 'Cinta Allah dan Rasul-Nya'],
                ['CINTA_ILMU', 'Cinta Ilmu'],
                ['CINTA_LINGKUNGAN', 'Cinta Lingkungan'],
                ['CINTA_DIRI_SESAMA', 'Cinta Diri dan Sesama Manusia'],
                ['CINTA_TANAH_AIR', 'Cinta Tanah Air'],
            ];
            foreach ($pancaCinta as $index => [$code, $title]) {
                $upsertNode(
                    'PANCA_CINTA',
                    $code,
                    $title,
                    null,
                    $kbc,
                    'Kepdirjen Pendis 6077/2025, Panduan Kurikulum Berbasis Cinta',
                    ['sort_order' => $index + 1, 'registry_key' => 'PANCA_CINTA']
                );
            }

            $pmPrinciples = [
                ['MEMULIAKAN', 'Memuliakan'],
                ['BERKESADARAN', 'Berkesadaran'],
                ['BERMAKNA', 'Bermakna'],
                ['MENGGEMBIRAKAN', 'Menggembirakan'],
            ];
            foreach ($pmPrinciples as $index => [$code, $title]) {
                $upsertNode(
                    'PM_PRINCIPLE',
                    $code,
                    $title,
                    null,
                    $pm,
                    'Kepmendikdasmen 126/P/2025, prinsip Pembelajaran Mendalam',
                    ['sort_order' => $index + 1]
                );
            }

            foreach ([
                ['OLAH_PIKIR', 'Olah Pikir'],
                ['OLAH_HATI', 'Olah Hati'],
                ['OLAH_RASA', 'Olah Rasa'],
                ['OLAH_RAGA', 'Olah Raga'],
            ] as $index => [$code, $title]) {
                $upsertNode(
                    'PM_OLAH',
                    $code,
                    $title,
                    null,
                    $pm,
                    'Kepmendikdasmen 126/P/2025, olah pikir/hati/rasa/raga',
                    ['sort_order' => $index + 1]
                );
            }

            foreach ([
                ['PRAKTIK_PEDAGOGIS', 'Praktik Pedagogis'],
                ['LINGKUNGAN_PEMBELAJARAN', 'Lingkungan Pembelajaran'],
                ['KEMITRAAN_PEMBELAJARAN', 'Kemitraan Pembelajaran'],
                ['PEMANFAATAN_DIGITAL', 'Pemanfaatan Digital'],
            ] as $index => [$code, $title]) {
                $upsertNode(
                    'PM_FRAMEWORK',
                    $code,
                    $title,
                    null,
                    $pm,
                    'Kepmendikdasmen 126/P/2025, kerangka desain pembelajaran',
                    ['sort_order' => $index + 1]
                );
            }

            $graduateDimensions = [
                ['KEIMANAN_KETAKWAAN', 'Keimanan dan ketakwaan terhadap Tuhan Yang Maha Esa'],
                ['KEWARGAAN', 'Kewargaan'],
                ['PENALARAN_KRITIS', 'Penalaran kritis'],
                ['KREATIVITAS', 'Kreativitas'],
                ['KOLABORASI', 'Kolaborasi'],
                ['KEMANDIRIAN', 'Kemandirian'],
                ['KESEHATAN', 'Kesehatan'],
                ['KOMUNIKASI', 'Komunikasi'],
            ];
            foreach ($graduateDimensions as $index => [$code, $title]) {
                $upsertNode(
                    'DIMENSI_PROFIL_LULUSAN',
                    $code,
                    $title,
                    null,
                    $dpl,
                    'Permendikdasmen 10/2025, Pasal 4',
                    ['sort_order' => $index + 1]
                );
            }

            $subjectSets = [
                'A' => [
                    ['ALQURAN_HADIS', 'Al-Qur’an Hadis', 'WAJIB', [1, 2]],
                    ['AKIDAH_AKHLAK', 'Akidah Akhlak', 'WAJIB', [1, 2]],
                    ['FIKIH', 'Fikih', 'WAJIB', [1, 2]],
                    ['BAHASA_ARAB', 'Bahasa Arab', 'WAJIB', [1, 2]],
                    ['PENDIDIKAN_PANCASILA', 'Pendidikan Pancasila', 'WAJIB', [1, 2]],
                    ['BAHASA_INDONESIA', 'Bahasa Indonesia', 'WAJIB', [1, 2]],
                    ['MATEMATIKA', 'Matematika', 'WAJIB', [1, 2]],
                    ['PJOK', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'WAJIB', [1, 2]],
                    ['SENI_BUDAYA', 'Seni dan Budaya', 'WAJIB', [1, 2]],
                    ['MUATAN_LOKAL', 'Muatan Lokal', 'LOKAL', [1, 2]],
                ],
                'B' => [
                    ['ALQURAN_HADIS', 'Al-Qur’an Hadis', 'WAJIB', [3, 4]],
                    ['AKIDAH_AKHLAK', 'Akidah Akhlak', 'WAJIB', [3, 4]],
                    ['FIKIH', 'Fikih', 'WAJIB', [3, 4]],
                    ['SEJARAH_KEBUDAYAAN_ISLAM', 'Sejarah Kebudayaan Islam', 'WAJIB', [3, 4]],
                    ['BAHASA_ARAB', 'Bahasa Arab', 'WAJIB', [3, 4]],
                    ['PENDIDIKAN_PANCASILA', 'Pendidikan Pancasila', 'WAJIB', [3, 4]],
                    ['BAHASA_INDONESIA', 'Bahasa Indonesia', 'WAJIB', [3, 4]],
                    ['MATEMATIKA', 'Matematika', 'WAJIB', [3, 4]],
                    ['IPAS', 'Ilmu Pengetahuan Alam dan Sosial', 'WAJIB', [3, 4]],
                    ['PJOK', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'WAJIB', [3, 4]],
                    ['SENI_BUDAYA', 'Seni dan Budaya', 'WAJIB', [3, 4]],
                    ['BAHASA_INGGRIS', 'Bahasa Inggris', 'WAJIB_TRANSISI', [3, 4]],
                    ['MUATAN_LOKAL', 'Muatan Lokal', 'LOKAL', [3, 4]],
                ],
                'C' => [
                    ['ALQURAN_HADIS', 'Al-Qur’an Hadis', 'WAJIB', [5, 6]],
                    ['AKIDAH_AKHLAK', 'Akidah Akhlak', 'WAJIB', [5, 6]],
                    ['FIKIH', 'Fikih', 'WAJIB', [5, 6]],
                    ['SEJARAH_KEBUDAYAAN_ISLAM', 'Sejarah Kebudayaan Islam', 'WAJIB', [5, 6]],
                    ['BAHASA_ARAB', 'Bahasa Arab', 'WAJIB', [5, 6]],
                    ['PENDIDIKAN_PANCASILA', 'Pendidikan Pancasila', 'WAJIB', [5, 6]],
                    ['BAHASA_INDONESIA', 'Bahasa Indonesia', 'WAJIB', [5, 6]],
                    ['MATEMATIKA', 'Matematika', 'WAJIB', [5, 6]],
                    ['IPAS', 'Ilmu Pengetahuan Alam dan Sosial', 'WAJIB', [5, 6]],
                    ['PJOK', 'Pendidikan Jasmani Olahraga dan Kesehatan', 'WAJIB', [5, 6]],
                    ['SENI_BUDAYA', 'Seni dan Budaya', 'WAJIB', [5, 6]],
                    ['BAHASA_INGGRIS', 'Bahasa Inggris', 'WAJIB_TRANSISI', [5, 6]],
                    ['KODING_AI', 'Koding dan Kecerdasan Artifisial', 'PILIHAN', [5, 6]],
                    ['MUATAN_LOKAL', 'Muatan Lokal', 'LOKAL', [5, 6]],
                ],
            ];

            foreach ($subjectSets as $phaseCode => $subjects) {
                foreach ($subjects as $index => [$code, $title, $classification, $grades]) {
                    $subjectNode = $upsertNode(
                        'SUBJECT',
                        'FASE_' . $phaseCode . '_' . $code,
                        $title,
                        $phaseNodes[$phaseCode]->id,
                        $kma,
                        'KMA 1503/2025, Struktur Kurikulum MI Fase ' . $phaseCode,
                        [
                            'sort_order' => $index + 1,
                            'classification' => $classification,
                            'applicable_grades' => $grades,
                            'subject_code' => $code,
                            'language_transition' => $code === 'BAHASA_INGGRIS'
                                ? 'Optional based on madrasah readiness through 2026/2027; mandatory from 2027/2028'
                                : null,
                            'notes' => $code === 'KODING_AI'
                                ? 'Mata pelajaran pilihan sesuai kesiapan madrasah.'
                                : null,
                        ]
                    );

                    if ($code === 'SENI_BUDAYA') {
                        foreach ([
                            ['SENI_MUSIK', 'Seni Musik'],
                            ['SENI_RUPA', 'Seni Rupa'],
                            ['SENI_TEATER', 'Seni Teater'],
                            ['SENI_TARI', 'Seni Tari'],
                        ] as $optionIndex => [$optionCode, $optionTitle]) {
                            $upsertNode(
                                'SUBJECT_OPTION',
                                'FASE_' . $phaseCode . '_' . $optionCode,
                                $optionTitle,
                                $subjectNode->id,
                                $kma,
                                'KMA 1503/2025, keterangan Seni dan Budaya MI',
                                ['sort_order' => $optionIndex + 1, 'minimum_selection' => 1]
                            );
                        }
                    }

                    if ($code === 'MUATAN_LOKAL') {
                        foreach ([
                            ['KEAGAMAAN', 'Keagamaan'],
                            ['SENI_BUDAYA', 'Seni Budaya'],
                            ['PRAKARYA', 'Prakarya'],
                            ['PJOK', 'Pendidikan Jasmani, Olahraga, dan Kesehatan'],
                            ['BAHASA', 'Bahasa'],
                        ] as $categoryIndex => [$categoryCode, $categoryTitle]) {
                            $upsertNode(
                                'LOCAL_CONTENT_CATEGORY',
                                'FASE_' . $phaseCode . '_' . $categoryCode,
                                $categoryTitle,
                                $subjectNode->id,
                                $kma,
                                'KMA 1503/2025, muatan lokal MI',
                                ['sort_order' => $categoryIndex + 1, 'configurable_by_madrasah' => true]
                            );
                        }
                    }
                }
            }
        });
    }
}
