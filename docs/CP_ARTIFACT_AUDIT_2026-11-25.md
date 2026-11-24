# Audit Artefak CP Resmi — P0

- Status: **BLOCKED — REQUIRES OFFICIAL CP ARTIFACT**
- Source baseline: `a65b4604495e8bcc153bf0e09df12a9efb11543a`
- Tidak ada CP fiktif/placeholder dibuat; `cp_content_imported` dan `artifact_checksum_verified` tidak diubah.

## Pemeriksaan source

Diperiksa:
- `database/seeders/CurriculumReferenceSourceSeeder.php`, `CurriculumRegistryBootstrapSeeder.php`
- Model `MultiLearningOutcome`, `CurriculumReferenceSource`, `MultiCurriculumStructure`, `MultiCurriculumVersion`
- `TeacherCurriculumContextService`, `TeacherLearningOutcomeService`
- Livewire `QuestionBank.php`, `ExamManagement.php`, seluruh daftar seeder.

Struktur fase A/B/C, MI 1–6, mapel, Panca Cinta, Pembelajaran Mendalam, dan delapan dimensi profil lulusan sudah ada dan dipertahankan. Tidak ditemukan file CP PDF/DOCX/XLSX/CSV/ZIP atau seeder konten CP di tree GitHub yang diperiksa. Bootstrap menetapkan `cp_content_imported=false` dan `artifact_checksum_verified=false`. Tidak ada alur import yang menghasilkan CP `MultiLearningOutcome` terverifikasi.

Tanpa CP resmi/checksum, `TeacherCurriculumContextService` menandai CP pending, `TeacherLearningOutcomeService` menolak TP, dan Bank Soal/Exam Builder tetap mewajibkan relasi CP/TP sah. Validasi tidak dilonggarkan.

## Project Library dan workspace

- `CBT REVISI 6 NOPEMBER 2026_REASSEMBLED_READONLY.zip` ditemukan di Project Library.
- `02_INVENTAR_SEMUA_FILE_HASIL_EKSTRAKSI_18248.csv` ditemukan.
- Keduanya tercatat tetapi bytes/isi mentah tidak berhasil dibuka pada workspace. Karena itu tidak boleh diklaim ZIP tidak mengandung CP; isi arsip masih **NOT VERIFIED**.
- File percakapan dan `/mnt/data` yang tersedia berisi laporan Markdown/TXT, bukan PDF CP.
- Tidak meminta unggah ulang ZIP baseline.

## Referensi resmi teridentifikasi

| Regulasi | Cakupan | Referensi | Artefak/checksum |
|---|---|---|---|
| Kepdirjen Pendis 9941/2025 | CP PAI dan Bahasa Arab Madrasah, termasuk MI | https://pendis.kemenag.go.id/read/keputusan-direktur-jenderal-pendidikan-islam-nomor-9941-tahun-2025-tentang-capaian-pembelajaran-mata-pelajaran-pendidikan-agama-islam-dan-bahasa-arab-pada-madrasah | Seeder `PENDING`; PDF resmi asli tidak tersedia; SHA **NOT COMPUTED** |
| BSKAP 046/H/KR/2025 | CP mata pelajaran umum, mencakup SD/MI Fase A/B/C | Seeder URI: https://kurikulum.kemendikdasmen.go.id/file/1759329314_manage_file.pdf ; PDF resmi lain yang ditemukan: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf | Seeder `METADATA_VERIFIED`; URI belum dibandingkan byte-per-byte; SHA **NOT COMPUTED** |
| BSKAP 020 Tahun 2026 | Siaran resmi menyatakan perubahan terbatas pada Pendidikan Agama dan Budi Pekerti; penerapan pada CP khusus Madrasah perlu direkonsiliasi dengan 9941/2025 | https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti | Metadata `METADATA_VERIFIED`; siaran pers bukan naskah keputusan lengkap; SHA **NOT COMPUTED** |
| KMA 1503/2025 | Kerangka kurikulum Madrasah, bukan konten CP | `CurriculumReferenceSourceSeeder.php` | Metadata saja; bukan artefak CP |
| Kepdirjen Pendis 6077/2025 | Pedoman Kurikulum Berbasis Cinta, bukan konten CP | `CurriculumReferenceSourceSeeder.php` | Metadata saja; bukan artefak CP |

PDF 9941/2025 juga ditemukan pada mirror situs madrasah pihak ketiga, tetapi tidak diterima sebagai bukti provenance/berkas resmi asli untuk impor dan hashing. PDF 046 ditemukan pada domain resmi; URI yang dicatat seeder berbeda dan wajib direkonsiliasi. Siaran pers 020/2026 bukan pengganti naskah keputusan beserta lampiran.

## Keputusan dan kebutuhan

Tidak ada CP contoh, importer berbasis tebakan, perubahan status verifikasi, atau record `MultiLearningOutcome` tanpa artefak resmi.

Untuk membuka blocker, sediakan:
1. PDF resmi lengkap Kepdirjen Pendis 9941 Tahun 2025 beserta lampiran CP PAI dan Bahasa Arab Madrasah.
2. PDF resmi lengkap BSKAP 046/H/KR/2025 beserta lampiran CP SD/MI.
3. PDF keputusan dan lampiran resmi BSKAP 020 Tahun 2026 jika berlaku pada MI/Madrasah, untuk rekonsiliasi CP Pendidikan Agama/Budi Pekerti.

Berkas dapat diekstrak dari ZIP baseline yang sudah ada atau diberikan satu per satu; **tidak perlu unggah ulang seluruh ZIP**. Setelah bytes tersedia: verifikasi provenance, versi, cakupan mapel/fase/kelas, SHA-256, lalu implementasikan importer idempotent dan audit trail.
