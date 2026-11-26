<?php

namespace Database\Seeders;

use App\Models\CurriculumReferenceSource;
use App\Models\MultiCurriculumStructure;
use App\Models\MultiCurriculumVersion;
use App\Models\MultiLearningOutcome;
use App\Models\MultiCurriculum;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportBahasaIndonesiaFaseCCpSeeder extends Seeder
{
    /**
     * Download this exact official BSKAP 046/H/KR/2025 PDF and place it at:
     * storage/app/curriculum-references/BSKAP_046_H_KR_2025.pdf
     *
     * Official source URL:
     * https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
     *
     * This seeder is intentionally NOT called by DatabaseSeeder. Run it
     * explicitly after the source PDF has been archived and backed up.
     */
    private const SOURCE_DOCUMENT_NUMBER = 'Keputusan Kepala BSKAP 046/H/KR/2025';
    private const SOURCE_DOCUMENT_VERSION = '2025-07-16';
    private const SOURCE_FILE = 'app/curriculum-references/BSKAP_046_H_KR_2025.pdf';
    private const SOURCE_URI = 'https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf';

    public function run(): void
    {
        $absolutePath = storage_path(self::SOURCE_FILE);

        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            throw new RuntimeException(
                'File CP resmi belum tersedia. Simpan PDF BSKAP 046/H/KR/2025 di ' . $absolutePath
            );
        }

        $size = filesize($absolutePath);
        $header = file_get_contents($absolutePath, false, null, 0, 8);
        if ($size === false || $size < 100_000 || !is_string($header) || !str_starts_with($header, '%PDF-')) {
            throw new RuntimeException(
                'File sumber tidak tampak sebagai PDF yang valid atau ukurannya tidak wajar. Tidak ada data yang diubah.'
            );
        }

        $checksum = hash_file('sha256', $absolutePath);
        if (!is_string($checksum) || preg_match('/\A[a-f0-9]{64}\z/i', $checksum) !== 1) {
            throw new RuntimeException('Checksum SHA-256 PDF gagal dihitung. Tidak ada data yang diubah.');
        }

        DB::transaction(function () use ($absolutePath, $size, $checksum): void {
            $systemTenantId = Tenant::SYSTEM_TENANT_ID;

            $source = CurriculumReferenceSource::query()
                ->where('tenant_id', $systemTenantId)
                ->where('document_number', self::SOURCE_DOCUMENT_NUMBER)
                ->where('document_version', self::SOURCE_DOCUMENT_VERSION)
                ->lockForUpdate()
                ->first();

            if (!$source) {
                throw new RuntimeException(
                    'Referensi sumber BSKAP 046/H/KR/2025 tidak ditemukan. Jalankan CurriculumReferenceSourceSeeder terlebih dahulu.'
                );
            }

            if ($source->checksum_sha256 && !hash_equals((string) $source->checksum_sha256, $checksum)) {
                throw new RuntimeException(
                    'Checksum PDF berbeda dari checksum yang sudah tercatat. File tidak ditimpa; periksa versi dan sumber dokumen terlebih dahulu.'
                );
            }

            $curriculum = MultiCurriculum::query()
                ->where('tenant_id', $systemTenantId)
                ->where('code', 'KURIKULUM_MERDEKA')
                ->firstOrFail();

            $version = MultiCurriculumVersion::query()
                ->where('tenant_id', $systemTenantId)
                ->where('multi_curriculum_id', $curriculum->id)
                ->where('version_code', 'KMA-1503-2025')
                ->where('status', 'ACTIVE')
                ->where('is_official', true)
                ->where('is_editable', false)
                ->lockForUpdate()
                ->firstOrFail();

            $subject = MultiCurriculumStructure::query()
                ->where('tenant_id', $systemTenantId)
                ->where('multi_curriculum_version_id', $version->id)
                ->where('node_type', 'SUBJECT')
                ->where('code', 'FASE_C_BAHASA_INDONESIA')
                ->where('status', 'ACTIVE')
                ->where('is_official', true)
                ->where('is_editable', false)
                ->lockForUpdate()
                ->firstOrFail();

            $archivePath = self::SOURCE_FILE;
            $source->fill([
                'source_uri' => self::SOURCE_URI,
                'archive_path' => $archivePath,
                'checksum_sha256' => $checksum,
                'verification_status' => 'ARTIFACT_CHECKSUM_VERIFIED',
                'verified_at' => now(),
                'metadata' => array_replace_recursive($source->metadata ?? [], [
                    'source_kind' => 'official_government_pdf',
                    'artifact_checksum_verified' => true,
                    'checksum_algorithm' => 'SHA-256',
                    'checksum_sha256' => $checksum,
                    'archive_path' => $archivePath,
                    'artifact_filename' => basename($absolutePath),
                    'artifact_size_bytes' => $size,
                    'verification_method' => 'SHA-256 computed from the PDF archived from the official Kemendikdasmen source URL; operator must ensure the downloaded file came from that URL.',
                    'verified_scope' => array_values(array_unique(array_merge(
                        $source->metadata['verified_scope'] ?? [],
                        ['general_subject_cp', 'bahasa_indonesia_fase_c_mi']
                    ))),
                ]),
            ]);
            $source->save();

            $outcomes = [
                [
                    'code' => 'CP-BI-C-MENYIMAK',
                    'title' => 'Fase C — Menyimak',
                    'element' => 'Menyimak',
                    'description' => 'Menganalisis informasi dari teks nonsastra berbentuk teks aural (teks yang dibacakan dan/atau didengarkan); dan menganalisis isi teks sastra berbentuk teks aural.',
                    'source_locator' => 'Lampiran II, III.1 Capaian Pembelajaran Bahasa Indonesia, D. Capaian Pembelajaran, Fase C, elemen Menyimak.',
                    'sort_order' => 1,
                ],
                [
                    'code' => 'CP-BI-C-MEMBACA-MEMIRSA',
                    'title' => 'Fase C — Membaca dan Memirsa',
                    'element' => 'Membaca dan Memirsa',
                    'description' => 'Membaca kata-kata dengan berbagai pola kombinasi huruf dengan fasih dari bacaan dan/atau tayangan yang dipirsa; dan menganalisis informasi serta nilai-nilai dalam teks sastra dan nonsastra berwujud teks visual dan/atau audiovisual.',
                    'source_locator' => 'Lampiran II, III.1 Capaian Pembelajaran Bahasa Indonesia, D. Capaian Pembelajaran, Fase C, elemen Membaca dan Memirsa.',
                    'sort_order' => 2,
                ],
                [
                    'code' => 'CP-BI-C-BERBICARA-PRESENTASI',
                    'title' => 'Fase C — Berbicara dan Mempresentasikan',
                    'element' => 'Berbicara dan Mempresentasikan',
                    'description' => 'Mempresentasikan gagasan dari berbagai tipe teks dengan efektif dan santun; dan menyampaikan perasaan berdasarkan fakta, imajinasi (dari diri sendiri dan orang lain) secara indah dan menarik dalam bentuk teks sastra dengan penggunaan kosakata secara kreatif.',
                    'source_locator' => 'Lampiran II, III.1 Capaian Pembelajaran Bahasa Indonesia, D. Capaian Pembelajaran, Fase C, elemen Berbicara dan Mempresentasikan.',
                    'sort_order' => 3,
                ],
                [
                    'code' => 'CP-BI-C-MENULIS',
                    'title' => 'Fase C — Menulis',
                    'element' => 'Menulis',
                    'description' => 'Menulis berbagai tipe teks sederhana berdasarkan gagasan, hasil pengamatan, pengalaman, dan/atau imajinasi dengan rangkaian kalimat kompleks secara kreatif, menarik, dan/atau indah; dan menggunakan kaidah kebahasaan dan kosakata baru yang memiliki makna denotatif dan konotatif.',
                    'source_locator' => 'Lampiran II, III.1 Capaian Pembelajaran Bahasa Indonesia, D. Capaian Pembelajaran, Fase C, elemen Menulis.',
                    'sort_order' => 4,
                ],
            ];

            foreach ($outcomes as $outcomeData) {
                $identity = [
                    'multi_curriculum_structure_id' => $subject->id,
                    'code' => $outcomeData['code'],
                ];

                $outcome = MultiLearningOutcome::query()
                    ->where($identity)
                    ->lockForUpdate()
                    ->first();

                if ($outcome && (
                    (string) $outcome->tenant_id !== (string) $systemTenantId
                    || (string) $outcome->multi_curriculum_version_id !== (string) $version->id
                    || $outcome->outcome_type !== 'CP'
                )) {
                    throw new RuntimeException(
                        'Konflik identitas CP ' . $outcomeData['code'] . '. Tidak ada data yang diubah.'
                    );
                }

                $outcome ??= new MultiLearningOutcome();
                $outcome->fill([
                    'tenant_id' => $systemTenantId,
                    'multi_curriculum_structure_id' => $subject->id,
                    'multi_curriculum_version_id' => $version->id,
                    'code' => $outcomeData['code'],
                    'outcome_type' => 'CP',
                    'description' => $outcomeData['description'],
                    'status' => 'ACTIVE',
                    'source_id' => $source->id,
                    'source_locator' => $outcomeData['source_locator'],
                    'effective_from' => '2025-07-16',
                    'verification_status' => 'ARTIFACT_CHECKSUM_VERIFIED',
                    'verified_at' => now(),
                    'source_checksum' => $checksum,
                    'is_official' => true,
                    'is_editable' => false,
                    'metadata' => [
                        'title' => $outcomeData['title'],
                        'element' => $outcomeData['element'],
                        'phase' => 'C',
                        'grade_levels' => [5, 6],
                        'subject_code' => 'BAHASA_INDONESIA',
                        'source_document' => self::SOURCE_DOCUMENT_NUMBER,
                        'source_document_version' => self::SOURCE_DOCUMENT_VERSION,
                        'source_checksum_sha256' => $checksum,
                        'sort_order' => $outcomeData['sort_order'],
                    ],
                ]);
                $outcome->save();
            }
        }, 3);

        $this->command?->info('Berhasil mendaftarkan 4 CP resmi Bahasa Indonesia Fase C. Tidak ada TP/ATP buatan; Guru dapat menyusunnya dari CP ini.');
        $this->command?->info('SHA-256 sumber: ' . $checksum);
    }
}
