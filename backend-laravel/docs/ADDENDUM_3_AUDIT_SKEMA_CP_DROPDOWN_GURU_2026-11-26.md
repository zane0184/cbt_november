# Addendum 3 — Audit Skema CP, Provenance, dan Service Dropdown Guru
Tanggal: 26 November 2026
Repository: `zane0184/cbt_november`
Branch: `integration/zip-vps-php83-20261126`
Head yang diperiksa: `5319aaa9225bea27b717a86d9d6a9eca4007eb53`

## AUDIT

### 1. Skema `multi_learning_outcomes`
Migration yang diperiksa:
- `database/migrations/2026_11_05_190007_create_multi_learning_outcomes_table.php`
- `database/migrations/2026_11_05_200001_fase1c_multi_curriculum_integrity.php`
- `database/migrations/2026_11_23_000001_add_canonical_web_curriculum_contract.php`

Temuan:
- Primary key `id` UUID.
- Kolom inti: `tenant_id`, `multi_curriculum_structure_id`, `code`, `outcome_type`, `description`, `status`, timestamps, soft deletes.
- Migration Fase 1C menambahkan `multi_curriculum_version_id` dan composite integrity FK untuk structure + version + tenant.
- Migration canonical menambahkan provenance: `source_id`, `source_locator`, `effective_from`, `effective_until`, `verification_status`, `verified_at`, `source_checksum`, `metadata`, `is_official`, `is_editable`.
- Unique index `uniq_m_outcomes_struct_code` adalah `(multi_curriculum_structure_id, code)`; importer harus idempotent terhadap kombinasi struktur+kode.
- FK provenance pada MySQL mengikat `(source_id, tenant_id)` ke `curriculum_reference_sources(id, tenant_id)`. Karena itu source dan outcome wajib memakai tenant pemilik kurikulum yang sama.
- `source_checksum` harus sama dengan `curriculum_reference_sources.checksum_sha256` agar CP dapat dipilih.

### 2. Skema `curriculum_reference_sources`
Migration canonical membuat:
- UUID `id`, `tenant_id`, issuer, document_number, title, document_version, tanggal, source_uri, archive_path, checksum SHA-256, verification_status, verified_at, verified_by, metadata.
- Unique identity: `(tenant_id, issuer, document_number, document_version)`.
- Status verifikasi sumber dan checksum nullable; importer tidak boleh menaikkan status menjadi `ARTIFACT_CHECKSUM_VERIFIED` hanya karena PDF dapat dibaca di browser.

### 3. Penyebab dropdown CP kosong
Service aktual `app/Services/TeacherPortal/TeacherCurriculumContextService.php`:
- Memvalidasi assignment dipatok pada tenant registration, curriculum version, dan curriculum owner.
- Mengambil fase dari snapshot/rombel, kelas, dan subject mapping yang harus cocok dengan phase node.
- CP diambil dari node subject yang dipetakan pada fase tersebut, bukan sekadar berdasarkan nama mapel.
- CP hanya ditandai `selectable=true` jika sumber `ARTIFACT_CHECKSUM_VERIFIED`, checksum sumber ada, checksum CP ada, dan keduanya cocok.
- Jika CP belum ada, status `CP_REGISTRY_PENDING`; jika ada tetapi belum lolos checksum, status `CP_SOURCE_UNVERIFIED`.

Jadi penyelesaian harus mengisi CP pada subject node yang tepat untuk setiap fase dan menyelesaikan provenance/checksum yang valid. Mengisi CP pada subject node umum atau hanya membuat source metadata tidak akan memperbaiki dropdown assignment.

### 4. Temuan kritis pada source seeder
File `database/seeders/CurriculumReferenceSourceSeeder.php`:
- BSKAP 046/2025 berstatus `METADATA_VERIFIED`, checksum belum tersedia.
- BKPDM 020/2026 direpresentasikan sebagai sumber press release, bukan arsip keputusan PDF.
- Kepdirjen Pendis 9941/2025 masih `PENDING` dan `source_uri` saat ini menunjuk berita Kemenag Batam, bukan artefak keputusan 9941/2025.
- Source Pendis perlu diperbaiki ke URI PDF keputusan yang telah diaudit sebelum ada proses verifikasi checksum.

### 5. Seeder Fase C Bahasa Indonesia — tidak layak dipakai sebagai solusi final
File `database/seeders/ImportBahasaIndonesiaFaseCCpSeeder.php` masih hanya memuat 4 outcome Bahasa Indonesia Fase C.
- Tidak mencakup Fase A/B.
- Tidak mencakup mapel umum lain.
- Tidak mencakup PAI khas MI/Bahasa Arab.
- Menggabungkan `general_subject_cp` ke `verified_scope`, padahal hanya empat outcome yang dimasukkan. Ini dapat memberi kesan cakupan sumber lebih luas daripada konten yang telah diimpor.
- Jangan jalankan seeder ini sebagai penyelesaian dropdown semua mapel/fase.
- Jangan mengemas atau menginstruksikan pengguna memasang patch Fase C tersebut.

## DESAIN — kontrak importer penuh

1. Importer harus memakai subject structure node per fase yang sudah ada pada versi `KMA-1503-2025`, dan memverifikasi kode node terhadap bootstrap seeder sebelum menulis data.
2. Setiap record CP wajib membawa:
   - jenjang MI;
   - phase A/B/C;
   - subject structure ID yang benar;
   - code stabil dan unik dalam structure;
   - outcome_type `CP`;
   - deskripsi persis sesuai dokumen;
   - source_id dan source_locator (lampiran, bagian, fase, elemen, halaman);
   - effective_from;
   - metadata elemen/subelemen, kelas, nomor urut, dokumen, dan versi.
3. Jangan memasukkan CP SKI Fase A jika dokumen Pendis MI tidak menyediakan fase tersebut.
4. Source registry harus satu per dokumen/versi; record CP menggunakan source pemilik yang sama dengan tenant kurikulum.
5. Verifikasi checksum hanya setelah bytes PDF resmi tersimpan, ukuran dan header PDF dicek, SHA-256 dihitung lokal, dan arsip read-only tersimpan. Tidak boleh memakai checksum contoh atau hash dari PDF lain/mirror tanpa verifikasi identitas artefak.
6. Perbaiki source URI Pendis ke artefak PDF yang telah diperiksa, tetapi tetap `PENDING` sampai bytes dan checksum diverifikasi.
7. Jangan ubah service dropdown untuk melonggarkan pemeriksaan checksum. Gate ini adalah perlindungan integritas sumber, bukan hambatan yang boleh dilewati.
8. Hindari update status/sumber yang luas. Cakupan `verified_scope` harus spesifik per mapel dan fase; jangan menandai seluruh `general_subject_cp` hanya karena sebagian kecil outcome masuk.
9. Importer harus transaction-safe, idempotent, lock source/version/subject nodes, fail-closed jika struktur tidak cocok, dan tidak menghapus/mengarsipkan record CP lama secara otomatis.

## IMPLEMENTASI
- Tidak ada source code, migration, seeder, atau database yang diubah pada audit ini.
- Hanya dokumentasi audit yang ditambahkan.
- PR #38 tetap Draft, open, belum merge.
- Tidak ada VPS atau database produksi yang diakses.

## PENGUJIAN
Belum dijalankan:
- migration terhadap MySQL 8 disposable;
- validasi unique/FK dengan record contoh;
- importer dry-run dan idempotensi;
- uji tenant isolation;
- uji checksum match/mismatch;
- UAT dropdown pada assignment Bahasa Indonesia 5C maupun mapel/fase lain.

## Keputusan tahap
**Belum boleh membuat atau menjalankan importer CP penuh** sampai:
1. Teks semua outcome CP diekstrak persis dari PDF primer untuk seluruh mapel dan fase yang berlaku.
2. Hubungan regulasi BKPDM 020/2026 dengan Pendis 9941/2025 ditetapkan dalam catatan regulasi yang dapat ditelusuri.
3. Kode struktur subject per fase diverifikasi dari bootstrap seeder aktual.
4. Arsip PDF primer dan checksum SHA-256 lokal tersedia.

Tahap berikutnya adalah membangun manifest data CP berbasis teks primer lengkap dan review regulasi, bukan menurunkan syarat verifikasi pada service Guru.
