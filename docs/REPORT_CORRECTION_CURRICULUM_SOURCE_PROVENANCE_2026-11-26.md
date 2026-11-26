# Laporan Koreksi Provenance Registry Sumber Kurikulum
Tanggal: 26 November 2026
Branch: `integration/zip-vps-php83-20261126`
Commit source: `40f81807861cd0b3d0d9fde2b7dc35d8af313425`

## AUDIT
Pemeriksaan `CurriculumReferenceSourceSeeder.php` menemukan:
1. Record BKPDM 020/2026 sebelumnya memakai URL siaran pers untuk record yang bernama dokumen keputusan. Siaran pers adalah bukti metadata/pengumuman, bukan artefak keputusan CP.
2. Record Kepdirjen Pendis 9941/2025 sebelumnya menunjuk berita Kemenag Batam tentang kegiatan MGMP MTs, bukan PDF keputusan dan lampiran CP MI.
3. Keduanya berisiko mencampur metadata pemberitahuan dengan artefak sumber CP.

Sumber yang dapat dibaca:
- BSKAP 046/2025 PDF resmi: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
- Siaran pers resmi BKPDM 020/2026: https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti
- Salinan PDF keputusan BKPDM 020/2026 pada CDN pihak ketiga (belum provenance final): https://cdn.schoolpro.id/documents/1782087907907-48baee9c9a6c43c280955078b534442c.pdf
- Salinan PDF Kepdirjen Pendis 9941/2025 pada situs madrasah pihak ketiga (belum provenance final): https://min2sbb.sch.id/wp-content/uploads/2025/12/SK-Dirjen-Pendis-9941-2025-CP-PAI-dan-Bahasa-Arab-pada-Madrasah.pdf
- Contoh buku MI pada domain Kemenag yang mencantumkan acuan Kepdirjen 9941/2025, KMA 1503/2025, dan Kepdirjen 6077/2025: https://cms2025.kemenag.go.id/storage/files/Alquran%20Hadis%20Kelas%201.pdf

Batas:
- PDF salinan pihak ketiga belum diverifikasi tanda tangan elektronik/QR dan belum dihitung SHA-256 dari raw bytes.
- Buku resmi Kemenag merupakan bukti pendukung penggunaan 9941/2025 pada bahan ajar MI, tetapi bukan pengganti validasi artefak keputusan.
- Hubungan normatif BKPDM 020/2026 dan Kepdirjen Pendis 9941/2025 pada MI belum dinyatakan tuntas; CP PAI tetap diblokir dari import.

## DESAIN
- Pisahkan artefak keputusan BKPDM 020/2026 dari siaran pers resmi 520/sipers/A6/VI/2026.
- Record keputusan BKPDM menunjuk PDF salinan yang dapat dibaca tetapi berstatus PENDING, tanpa verified_scope dan tanpa checksum.
- Record siaran pers BKPDM dibuat tersendiri, berstatus METADATA_VERIFIED, dan hanya memiliki scope pemberitahuan penerbitan/ruang lingkup perubahan.
- Record Kepdirjen Pendis 9941/2025 menunjuk PDF salinan keputusan yang dapat dibaca, tetapi tetap PENDING; metadata menyebut host pihak ketiga dan status verifikasi yang belum selesai.
- Tidak ada record yang ditandai ARTIFACT_CHECKSUM_VERIFIED.
- Gate TeacherCurriculumContextService tidak diubah.

## IMPLEMENTASI
File berubah:
- `backend-laravel/database/seeders/CurriculumReferenceSourceSeeder.php`

Perubahan:
- Memperbaiki source_uri record Kepdirjen Pendis 9941/2025 ke PDF salinan keputusan dan lampiran.
- Memisahkan BKPDM 020/2026 decision copy (PENDING) dari siaran pers resmi (METADATA_VERIFIED).
- Menjaga identity version BKPDM lama `2026-06-23` agar record existing diperbarui, bukan meninggalkan duplikat identity lama.
- Tidak mengubah `DatabaseSeeder`, importer CP, migration, model, service, atau database.

Backup:
- `backend-laravel/backups/curriculum-source-registry-correction-20261126/CurriculumReferenceSourceSeeder.php.before`
- Manifest: `backend-laravel/backups/curriculum-source-registry-correction-20261126/MANIFEST.md`
- Blob source sebelum patch: `d089332a768bf9825c20c4c0571ff534d5bf550c`

## PENGUJIAN
- Static diff review: dilakukan.
- PHP Runtime Validation dan Build ZIP untuk commit patch: workflow berjalan otomatis; status final harus dibaca dari GitHub Actions sebelum klaim PASS.
- PHPUnit database seeder test: belum dijalankan.
- MySQL disposable: belum dijalankan.
- Database/VPS produksi: tidak disentuh.

## STATUS
**SOURCE METADATA CORRECTION COMMITTED; RUNTIME/SEEDER TEST PENDING.**
Tidak ada CP yang diimpor dan tidak ada database produksi yang berubah.
