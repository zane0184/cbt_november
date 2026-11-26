# Checkpoint Import DOCX — 26 November 2026

## Posisi
- Baseline aktif CP/TP/ATP: `CBT_MADRASAH_BACKEND_REVISI_CP_TP_DOCX_FINAL_2026-11-26(1).zip`.
- SHA-256: `9de8dab2590ac2070b69f520a13b927bcfda1573626cdd834650421a45dfc55d`.
- Repository: `zane0184/cbt_november`.
- Branch kerja: `fix/import-docx-persistence-20261126`.
- ZIP/source aktif belum disinkronkan ke branch; tidak ada commit aplikasi yang boleh dianggap sudah dibuat.

## AUDIT
- `QuestionDocxImport::importDrafts()` memvalidasi upload, preview hash, assignment, section, dan soal, lalu memanggil `DocxQuestionDraftImporter::import()`.
- Importer menjalankan satu transaksi DB untuk membuat Question, QuestionVersion, opsi, media, pemetaan kurikulum, audit log, dan batch idempotensi.
- Throwable menyebabkan rollback; file media yang sempat ditulis dihapus.
- Model Question, QuestionVersion, QuestionOption, dan MediaAsset memakai `TenantScoped`; trait mengisi tenant_id pada create dan query fail-closed bila tenant context tidak tersedia.
- AuditLog tidak memakai TenantScoped, tetapi tenant_id diisi eksplisit oleh AuditService.
- Dump DB yang diberikan memuat kolom utama tabel question/version/options/media/batch yang dipakai. Dump tidak membuktikan koneksi DB aktif website sama dengan dump.
- CP/TP/ATP tetap aktif. Tidak ada diagnostic bypass yang dipakai.
- Belum ditemukan exception runtime yang menunjukkan titik kegagalan.

## DESAIN
1. Jangan ubah alur CP/TP/ATP dan jangan sentuh DB produksi.
2. Dapatkan exception aktual dari request Simpan di staging atau runtime yang identik.
3. Instrumentasi hanya boleh ditambahkan setelah backup file, dengan log terarah tanpa isi soal, dokumen, token, atau kredensial.
4. Uji sukses, rollback, idempotensi, tenant/assignment mismatch, media, dan mapping CP/TP pada MySQL disposable.

## IMPLEMENTASI
- Workspace ekstraksi: `/mnt/data/CBT_IMPORT_DOCX_WORK_20261126/backend-laravel/`.
- Backup sebelum eksperimen: `backups/20261126-before-import-diagnostic/QuestionDocxImport.php`.
- File Livewire diverifikasi byte-identik dengan backup setelah eksperimen logging sementara dibatalkan.
- Tidak ada patch aplikasi yang dipertahankan; tidak ada migration atau perubahan DB.
- Checkpoint ini diperbarui pada branch GitHub kerja. Source aplikasi ZIP belum diunggah.

## PENGUJIAN
- PHP lint 10 file terkait: PASS.
- PHPUnit, CI, browser/Livewire, runtime MySQL: NOT RUN karena ZIP tidak menyertakan tests/phpunit.xml/PHPUnit binary dan PHP lokal tidak memiliki ekstensi DOM, ZIP, MBSTRING, PDO MySQL.
- Akar masalah belum teridentifikasi; fitur belum boleh dinyatakan berhasil.

## Langkah berikutnya
- Ambil exception lengkap dan timestamp dari log Laravel/PHP-FPM saat satu percobaan impor staging dilakukan.
- Bandingkan row count sebelum/sesudah pada questions, question_versions, question_options, media_assets, question_docx_import_batches, audit_logs, dan tabel mapping.
- Pastikan identitas DB aktif aplikasi dibandingkan dengan dump yang tersedia, tanpa membagikan secret.
