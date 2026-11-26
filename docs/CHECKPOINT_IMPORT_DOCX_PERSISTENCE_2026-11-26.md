# Checkpoint Import DOCX — 26 November 2026

## Posisi
- Baseline aktif CP/TP/ATP: `CBT_MADRASAH_BACKEND_REVISI_CP_TP_DOCX_FINAL_2026-11-26(1).zip`.
- SHA-256: `9de8dab2590ac2070b69f520a13b927bcfda1573626cdd834650421a45dfc55d`.
- Repository: `zane0184/cbt_november`.
- Branch kerja: `fix/import-docx-persistence-20261126`.
- ZIP/source aktif belum disinkronkan ke branch. Tidak ada commit source aplikasi.
- Laporan audit rinci: [AUDIT_MENDALAM_IMPORT_DOCX_WEB_GURU_2026-11-26.md](AUDIT_MENDALAM_IMPORT_DOCX_WEB_GURU_2026-11-26.md).

## AUDIT
- Route Web Guru: `/guru/bank-soal/import-docx`, memakai middleware auth, active.user, tenant, license, dan EnsureTeacherPortalAccess.
- Layout Guru memuat Livewire styles/scripts. Tombol Blade memanggil `importDrafts`.
- `QuestionDocxImport::importDrafts()` memvalidasi file, preview hash, assignment, section, dan urutan sumber.
- **CACAT TERKONFIRMASI:** parser memberi status READY/NEEDS_REVIEW; UI menyatakan NEEDS_REVIEW tidak ikut disimpan, tetapi filter `questionsForImport` hanya memeriksa section/sequence dan checkbox `included`. Semua soal diberi included=true pada preview. Status READY/errors tidak diperiksa.
- Importer juga hanya memfilter included. `validateQuestion()` menolak tipe di luar MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, TRUE_FALSE_GROUP, ESSAY. Maka satu soal NEEDS_REVIEW/UNSUPPORTED yang masih dicentang dapat menggagalkan seluruh transaksi, termasuk soal READY.
- Validasi Livewire masih menerima MATCHING, SHORT_ANSWER, UNSUPPORTED, tidak selaras dengan allow-list importer.
- Parser memberi NEEDS_REVIEW untuk unsupported blocks, tabel tidak terklasifikasi, stem kosong, opsi tidak lengkap/duplikat, dan tipe tidak didukung.
- **Penilaian:** kandidat kuat penyebab gagal simpan bila DOCX berisi soal NEEDS_REVIEW yang tidak didukung. Belum terbukti sebagai penyebab insiden pengguna karena DOCX/log runtime spesifik belum tersedia dalam bahan yang diperiksa.
- Importer menjalankan satu transaksi untuk question, version, media, options, mapping, audit log, dan batch idempotensi. Exception menyebabkan rollback.
- Mapping CP/TP/ATP tetap aktif. Mapel umum melewati mapping tetapi service masih menuntut registration kurikulum aktif; mapel yang wajib mapping memvalidasi provenance CP, TP/ATP, dan struktur framework.
- Tidak ada DOCX contoh atau log request/stack trace yang cocok dengan percobaan pengguna dalam folder kerja saat audit.
- Dump DB memiliki tabel/kolom utama; laporan VPS sebelumnya menyebut migration import batch Ran, tetapi dump tidak membuktikan identitas DB aktif website.

## DESAIN
1. Hanya soal READY, errors kosong, tanpa blocked_features, dan dicentang yang boleh dikirim ke importer.
2. Ulangi validasi status di service importer (defense in depth).
3. Samakan allow-list tipe di Livewire, parser, dan importer.
4. Tangani exception tak terduga dengan correlation ID dan pesan aman bagi Guru.
5. Pertahankan tenant, role, permission, assignment, transaksi, idempotensi, audit, serta CP/TP/ATP tanpa bypass.
6. Uji MySQL disposable: campuran READY/NEEDS_REVIEW, unsupported type, duplicate batch, mapping, media/audit failure, tenant mismatch, dan rollback.

## IMPLEMENTASI
- Workspace ekstraksi: `/mnt/data/CBT_IMPORT_DOCX_WORK_20261126/backend-laravel/`.
- Backup `QuestionDocxImport.php`: `backups/20261126-before-import-diagnostic/QuestionDocxImport.php`.
- Tidak ada patch source yang dipertahankan; tidak ada migration/database change.
- ZIP asli tidak diubah.
- GitHub connector yang tersedia tidak menyediakan upload binary ZIP/release asset; karena itu ZIP 18.5 MB belum diunggah. Jangan klaim ZIP tersinkron.

## PENGUJIAN
- PHP lint 10 file terkait dari audit sebelumnya: PASS.
- Audit statis jalur UI/parser/importer/mapping: selesai.
- PHPUnit, browser/Livewire, MySQL runtime/rollback: NOT TESTED.
- PHP lokal tidak memiliki DOM, ZIP, MBSTRING, PDO MySQL.
- Fitur belum dinyatakan berhasil.

## Berikutnya
- Siapkan patch terisolasi untuk READY/NEEDS_REVIEW + defense-in-depth setelah backup file terkait.
- Tambahkan test yang sesuai dan jalankan di runtime PHP 8.3/MySQL 8 disposable.
- Jangan jalankan migration atau eksperimen di database produksi.
