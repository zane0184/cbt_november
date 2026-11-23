<?php

namespace Database\Seeders;

use App\Models\CurriculumReferenceSource;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurriculumReferenceSourceSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            SystemTenantSeeder::seed();

            $sources = [
                [
                    'issuer' => 'Kementerian Agama Republik Indonesia',
                    'document_number' => 'KMA 1503 Tahun 2025',
                    'title' => 'Perubahan atas KMA 450 Tahun 2024 tentang Pedoman Implementasi Kurikulum pada Madrasah',
                    'document_version' => '2025-09-22',
                    'published_at' => '2025-09-22',
                    'effective_from' => '2025-09-22',
                    'source_uri' => 'https://sulteng.kemenag.go.id/files/show/20251020162801surat-penyampaian-kma-kurikulum-2025%2Blampiran_compressed-tzR.pdf',
                    'verification_status' => 'METADATA_VERIFIED',
                    'metadata' => [
                        'source_kind' => 'official_government_pdf',
                        'verified_scope' => ['madrasah_implementation', 'phase_mi', 'panca_cinta', 'deep_learning'],
                        'artifact_checksum_verified' => false,
                        'notes' => 'KMA 1503/2025 mengintegrasikan Pembelajaran Mendalam dan Kurikulum Berbasis Cinta. Fase A MI kelas I-II, B kelas III-IV, C kelas V-VI.',
                    ],
                ],
                [
                    'issuer' => 'Direktorat Jenderal Pendidikan Islam, Kementerian Agama Republik Indonesia',
                    'document_number' => 'Kepdirjen Pendis 6077 Tahun 2025',
                    'title' => 'Panduan Kurikulum Berbasis Cinta',
                    'document_version' => '2025-07-22',
                    'published_at' => '2025-07-22',
                    'effective_from' => '2025-07-22',
                    'source_uri' => 'https://cdn.kemenag.go.id/storage/archives/panduan-kurikulum-berbasis-cinta.pdf',
                    'verification_status' => 'METADATA_VERIFIED',
                    'metadata' => [
                        'source_kind' => 'official_kemenag_pdf',
                        'verified_scope' => ['panca_cinta'],
                        'artifact_checksum_verified' => false,
                        'notes' => 'Lima topik: Cinta Allah dan Rasul-Nya; Cinta Ilmu; Cinta Lingkungan; Cinta Diri dan Sesama Manusia; Cinta Tanah Air.',
                    ],
                ],
                [
                    'issuer' => 'Kementerian Pendidikan Dasar dan Menengah Republik Indonesia',
                    'document_number' => 'Permendikdasmen 10 Tahun 2025',
                    'title' => 'Standar Kompetensi Lulusan pada PAUD, Pendidikan Dasar, dan Pendidikan Menengah',
                    'document_version' => '2025-06-13',
                    'published_at' => '2025-06-13',
                    'effective_from' => '2025-06-13',
                    'source_uri' => 'https://peraturan.bpk.go.id/Details/321419/permendikdasmen-no-10-tahun-2025',
                    'verification_status' => 'METADATA_VERIFIED',
                    'metadata' => [
                        'source_kind' => 'official_regulation_registry',
                        'verified_scope' => ['eight_graduate_profile_dimensions'],
                        'artifact_checksum_verified' => false,
                        'notes' => 'Pasal 4 menetapkan delapan dimensi profil lulusan.',
                    ],
                ],
                [
                    'issuer' => 'Kementerian Pendidikan Dasar dan Menengah Republik Indonesia',
                    'document_number' => 'Kepmendikdasmen 126/P/2025',
                    'title' => 'Pedoman Implementasi Pembelajaran Mendalam',
                    'document_version' => '2025',
                    'published_at' => null,
                    'effective_from' => null,
                    'source_uri' => 'https://bpmpbabel.kemendikdasmen.go.id/PPID/wp-content/uploads/2025/09/Salinan-Kepmendikdasmen-Nomor-126_P_2025-CAP.pdf',
                    'verification_status' => 'METADATA_VERIFIED',
                    'metadata' => [
                        'source_kind' => 'official_government_pdf',
                        'verified_scope' => ['deep_learning_principles', 'learning_experiences', 'learning_framework'],
                        'artifact_checksum_verified' => false,
                        'notes' => 'Pendekatan Pembelajaran Mendalam; prinsip memuliakan, berkesadaran, bermakna, menggembirakan; olah pikir, hati, rasa, raga.',
                    ],
                ],
                [
                    'issuer' => 'Badan Standar, Kurikulum, dan Asesmen Pendidikan, Kemendikdasmen',
                    'document_number' => 'Keputusan Kepala BSKAP 046/H/KR/2025',
                    'title' => 'Capaian Pembelajaran PAUD, Pendidikan Dasar, dan Pendidikan Menengah',
                    'document_version' => '2025-07-16',
                    'published_at' => '2025-07-16',
                    'effective_from' => '2025-07-16',
                    'source_uri' => 'https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf',
                    'verification_status' => 'METADATA_VERIFIED',
                    'metadata' => [
                        'source_kind' => 'official_government_pdf',
                        'verified_scope' => ['general_subject_cp'],
                        'artifact_checksum_verified' => false,
                        'notes' => 'Untuk Pendidikan Agama dan Budi Pekerti, catat perubahan terbatas Keputusan Kepala BKPDM 020 Tahun 2026.',
                    ],
                ],
                [
                    'issuer' => 'Badan Kebijakan Pendidikan Dasar dan Menengah, Kemendikdasmen',
                    'document_number' => 'Keputusan Kepala BKPDM 020 Tahun 2026',
                    'title' => 'Perubahan atas Keputusan Kepala BSKAP 046/H/KR/2025 tentang Capaian Pembelajaran',
                    'document_version' => '2026-06-23',
                    'published_at' => null,
                    'effective_from' => null,
                    'source_uri' => 'https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti',
                    'verification_status' => 'METADATA_VERIFIED',
                    'metadata' => [
                        'source_kind' => 'official_press_release',
                        'verified_scope' => ['religion_and_character_education_cp_only'],
                        'artifact_checksum_verified' => false,
                        'notes' => 'Siaran pers resmi 23 Juni 2026 menyatakan perubahan hanya untuk Pendidikan Agama dan Budi Pekerti; CP mapel lain tetap mengacu pada 046/H/KR/2025. Dokumen keputusan asli perlu diarsipkan untuk checksum.',
                    ],
                ],
                [
                    'issuer' => 'Direktorat Jenderal Pendidikan Islam, Kementerian Agama Republik Indonesia',
                    'document_number' => 'Kepdirjen Pendis 9941 Tahun 2025',
                    'title' => 'Capaian Pembelajaran Pendidikan Agama Islam dan Bahasa Arab Kurikulum Merdeka pada Madrasah',
                    'document_version' => '2025',
                    'published_at' => null,
                    'effective_from' => null,
                    'source_uri' => 'https://batam.kemenag.go.id/main/perkuat-mutu-pembelajaran-pengawas-madrasah-hadiri-perdana-mgmp-pai-dan-bahasa-arab-mts-kota-batam',
                    'verification_status' => 'PENDING',
                    'metadata' => [
                        'source_kind' => 'official_kemenag_notice',
                        'verified_scope' => [],
                        'artifact_checksum_verified' => false,
                        'notes' => 'Nomor keputusan disebut oleh laman resmi Kemenag; artefak keputusan dan lampiran CP MI harus diperoleh dan diverifikasi sebelum konten CP PAI/Bahasa Arab diimpor.',
                    ],
                ],
                [
                    'issuer' => 'Badan Standar, Kurikulum, dan Asesmen Pendidikan, Kemendikdasmen',
                    'document_number' => 'Panduan Pengembangan Kurikulum Satuan Pendidikan Edisi Revisi 2025',
                    'title' => 'Panduan Pengembangan Kurikulum Satuan Pendidikan Edisi Revisi 2025',
                    'document_version' => '2025-revisi',
                    'published_at' => null,
                    'effective_from' => null,
                    'source_uri' => 'https://kurikulum.kemendikdasmen.go.id/file/1755670818_manage_file.pdf',
                    'verification_status' => 'METADATA_VERIFIED',
                    'metadata' => [
                        'source_kind' => 'official_government_guidance',
                        'verified_scope' => ['school_curriculum_development'],
                        'artifact_checksum_verified' => false,
                    ],
                ],
            ];

            foreach ($sources as $source) {
                CurriculumReferenceSource::query()->updateOrCreate(
                    [
                        'tenant_id' => Tenant::SYSTEM_TENANT_ID,
                        'issuer' => $source['issuer'],
                        'document_number' => $source['document_number'],
                        'document_version' => $source['document_version'],
                    ],
                    [
                        'title' => $source['title'],
                        'published_at' => $source['published_at'],
                        'effective_from' => $source['effective_from'],
                        'source_uri' => $source['source_uri'],
                        'verification_status' => $source['verification_status'],
                        'metadata' => $source['metadata'],
                    ]
                );
            }
        });
    }
}
