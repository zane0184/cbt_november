# Checkpoint Import DOCX — 26 November 2026

Baseline aktif: CBT_MADRASAH_BACKEND_REVISI_CP_TP_DOCX_FINAL_2026-11-26(1).zip
SHA-256: 9de8dab2590ac2070b69f520a13b927bcfda1573626cdd834650421a45dfc55d
Repository: zane0184/cbt_november
Branch: fix/import-docx-persistence-20261126

## AUDIT
Parser menandai soal READY atau NEEDS_REVIEW. Blade menyatakan NEEDS_REVIEW tidak ikut disimpan, tetapi filter Livewire sebelumnya hanya memeriksa checkbox dan section. Importer menolak struktur/tipenya di dalam transaksi, sehingga satu row invalid dapat membatalkan batch. Cacat source ini terkonfirmasi; penyebab tunggal insiden runtime belum terbukti.

## IMPLEMENTASI
Patch workspace: app/Livewire/Teacher/QuestionDocxImport.php dan app/Services/Questions/DocxQuestionDraftImporter.php.
Livewire sekarang hanya memilih sequence READY berdasarkan fresh parse server; importer menolak status selain READY sebagai defense-in-depth.
Backup: /mnt/data/CBT_IMPORT_DOCX_WORK_20261126/backups/20261126-150334-before-ready-filter/
Patch bundle: CBT_PATCH_IMPORT_DOCX_READY_FILTER_2026-11-26.zip
CP/TP/ATP tidak dinonaktifkan. Tidak ada migration atau perubahan database.

## PENGUJIAN
PHP lint dua file: PASS.
PHPUnit: BLOCKED; tests/, phpunit.xml, vendor/bin/phpunit tidak tersedia.
Runtime Laravel/MySQL/browser: BLOCKED; PHP lokal tidak memiliki DOM, ZIP, MBSTRING, PDO MySQL; MySQL tidak tersedia.
Transaksi, rollback, idempotensi, dan E2E: NOT TESTED.

## GITHUB / ZIP
Laporan patch telah dikomit pada branch. Konektor GitHub saat ini tidak menyediakan upload binary ZIP atau release asset; ZIP baseline 18.5 MB belum tersinkron.

## NEXT
Sinkronkan ZIP melalui mekanisme binary yang didukung, siapkan PHP 8.3 + ekstensi dan MySQL disposable, tambahkan PHPUnit tests, lalu uji browser staging dan verifikasi row database. Jangan deploy produksi sebelum gerbang pengujian lulus.