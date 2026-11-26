<?php

namespace App\\Console\\Commands;

use App\\Models\\CurriculumReferenceSource;
use App\\Models\\MultiCurriculumStructure;
use App\\Models\\MultiCurriculumVersion;
use App\\Models\\MultiLearningOutcome;
use App\\Models\\Tenant;
use Illuminate\\Console\\Command;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\File;

class ImportKemenagMiCp extends Command
{
    protected $signature = 'cbt:curriculum:import-kemenag-mi-cp {--dry-run : Validasi tanpa menulis database}';

    protected $description = 'Impor CP PAI dan Bahasa Arab MI Fase A/B/C dari Kepdirjen Pendis 9941/2025 dengan verifikasi SHA-256.';

    public function handle(): int
    {
        $path = database_path('data/curriculum/kepdirjen-pendis-9941-2025-mi.json');
        if (!File::exists($path)) {
            $this->error('Data CP tidak ditemukan: ' . $path);
            return self::FAILURE;
        }

        $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $document = $payload['document'] ?? [];
        $expectedHash = strtolower((string) ($document['sha256'] ?? ''));
        $archivePath = (string) ($document['archive_path'] ?? '');
        $pdfPath = storage_path('app/' . ltrim($archivePath, '/'));

        if (!preg_match('/\\A[a-f0-9]{64}\\z/', $expectedHash) || !$archivePath || !File::exists($pdfPath)) {
            $this->error('PDF sumber belum ditempatkan di storage/app/' . $archivePath);
            return self::FAILURE;
        }

        $actualHash = strtolower(hash_file('sha256', $pdfPath));
        if (!hash_equals($expectedHash, $actualHash)) {
            $this->error('SHA-256 PDF tidak cocok. Tidak ada perubahan database.');
            return self::FAILURE;
        }

        $ownerId = Tenant::SYSTEM_TENANT_ID;
        $version = MultiCurriculumVersion::query()
            ->where('tenant_id', $ownerId)->where('version_code', 'KMA-1503-2025')
            ->where('status', 'ACTIVE')->first();
        $source = CurriculumReferenceSource::query()
            ->where('tenant_id', $ownerId)
            ->where('document_number', 'Kepdirjen Pendis 9941 Tahun 2025')
            ->where('document_version', '2025')->first();

        if (!$version || !$source) {
            $this->error('Registry versi KMA-1503-2025 atau sumber Kepdirjen 9941/2025 belum tersedia.');
            return self::FAILURE;
        }

        $rows = $payload['outcomes'] ?? [];
        if (count($rows) !== 14) {
            $this->error('Paket harus memuat tepat 14 CP MI untuk mapel/fase yang ditetapkan.');
            return self::FAILURE;
        }

        $prepared = [];
        foreach ($rows as $row) {
            $phase = strtoupper((string) ($row['phase'] ?? ''));
            $grades = array_map('intval', (array) ($row['metadata']['grade_levels'] ?? []));
            $expectedGrades = ['A' => [1, 2], 'B' => [3, 4], 'C' => [5, 6]][$phase] ?? [];
            if ($grades !== $expectedGrades || !str_starts_with((string) ($row['structure_code'] ?? ''), 'FASE_' . $phase . '_')) {
                $this->error('Metadata fase/kelas tidak konsisten pada CP: ' . ($row['code'] ?? '(tanpa kode)'));
                return self::FAILURE;
            }

            $structure = MultiCurriculumStructure::query()
                ->where('tenant_id', $ownerId)->where('multi_curriculum_version_id', $version->id)
                ->where('node_type', 'SUBJECT')->where('code', $row['structure_code'])
                ->where('status', 'ACTIVE')->where('is_official', true)->first();
            if (!$structure) {
                $this->error('Struktur mapel resmi tidak ditemukan: ' . $row['structure_code']);
                return self::FAILURE;
            }

            $existing = MultiLearningOutcome::query()
                ->where('tenant_id', $ownerId)->where('multi_curriculum_version_id', $version->id)
                ->where('multi_curriculum_structure_id', $structure->id)
                ->where('outcome_type', 'CP')->where('code', $row['code'])->first();
            if ($existing) {
                $same = $existing->description === $row['description']
                    && (string) $existing->source_id === (string) $source->id
                    && hash_equals((string) $existing->source_checksum, $expectedHash)
                    && $existing->is_official && !$existing->is_editable;
                if (!$same) {
                    $this->error('Konflik CP ditemukan; data existing tidak ditimpa: ' . $row['code']);
                    return self::FAILURE;
                }
            }
            $prepared[] = [$row, $structure];
        }

        if ($this->option('dry-run')) {
            $this->info('DRY RUN PASS: 14 CP cocok dengan struktur mapel, fase, kelas, dan checksum sumber.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($ownerId, $source, $archivePath, $expectedHash, $document, $prepared): void {
            $source->update([
                'title' => $document['title'],
                'published_at' => $document['published_at'],
                'effective_from' => $document['effective_from'],
                'archive_path' => $archivePath,
                'checksum_sha256' => $expectedHash,
                'verification_status' => 'ARTIFACT_CHECKSUM_VERIFIED',
                'verified_at' => now(),
                'metadata' => array_merge($source->metadata ?? [], [
                    'source_kind' => 'official_kemenag_decision_pdf',
                    'verified_scope' => ['MI_PAI', 'MI_BAHASA_ARAB', 'PHASE_A', 'PHASE_B', 'PHASE_C'],
                    'artifact_checksum_verified' => true,
                    'artifact_sha256' => $expectedHash,
                    'cp_record_count' => count($prepared),
                ]),
            ]);

            foreach ($prepared as [$row, $structure]) {
                MultiLearningOutcome::query()->firstOrCreate(
                    [
                        'tenant_id' => $ownerId,
                        'multi_curriculum_version_id' => $structure->multi_curriculum_version_id,
                        'multi_curriculum_structure_id' => $structure->id,
                        'outcome_type' => 'CP',
                        'code' => $row['code'],
                    ],
                    [
                        'description' => $row['description'],
                        'status' => 'ACTIVE',
                        'source_id' => $source->id,
                        'source_locator' => $row['source_locator'],
                        'effective_from' => $source->effective_from,
                        'verification_status' => 'ARTIFACT_CHECKSUM_VERIFIED',
                        'verified_at' => now(),
                        'source_checksum' => $expectedHash,
                        'metadata' => array_merge($row['metadata'], [
                            'title' => $row['title'],
                            'source_document_number' => $source->document_number,
                        ]),
                        'is_official' => true,
                        'is_editable' => false,
                    ]
                );
            }
        }, 3);

        $this->info('IMPORT PASS: 14 CP resmi MI diproses. TP dan ATP tetap disusun Guru.');
        return self::SUCCESS;
    }
}
