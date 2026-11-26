# CBT Madrasah — Implementasi Perbaikan Import DOCX
Tanggal: 26 November 2026

## AUDIT
Baseline yang diaudit adalah ZIP `CBT_MADRASAH_BACKEND_REVISI_CP_TP_DOCX_FINAL_2026-11-26(1).zip`, SHA-256 `9de8dab2590ac2070b69f520a13b927bcfda1573626cdd834650421a45dfc55d`.

Ditemukan bug konsistensi READY/NEEDS_REVIEW:
1. Parser menetapkan status `READY` jika tidak ada error dan `NEEDS_REVIEW` jika ada error.
2. Blade menjanjikan hanya soal READY disimpan.
3. Livewire sebelumnya memilih row hanya dari checkbox `included` dan batas section, tanpa memfilter status parser.
4. Row yang tidak layak dapat masuk ke importer. Validasi importer melempar ValidationException di dalam transaksi, sehingga satu row dapat membatalkan seluruh batch.

Ini cacat source yang terkonfirmasi, tetapi belum membuktikan penyebab tunggal insiden runtime pengguna.

## DESAIN
Hasil parse ulang server menjadi sumber otoritatif status READY. Hanya sequence READY, dicentang Guru, dan berada di section terpilih yang boleh masuk importer. Importer juga memvalidasi status READY sebagai defense-in-depth. CP/TP/ATP, tenant, role, permission, penugasan, transaksi, audit, dan idempotensi tetap dipertahankan.

## IMPLEMENTASI
File diubah pada workspace:
- `app/Livewire/Teacher/QuestionDocxImport.php`: filter sequence READY dari fresh parse; pesan saat tidak ada READY; verifikasi status sebelum importer.
- `app/Services/Questions/DocxQuestionDraftImporter.php`: penolakan defensif row bukan READY.

Backup: `/mnt/data/CBT_IMPORT_DOCX_WORK_20261126/backups/20261126-150334-before-ready-filter/`.
Patch bundle lokal: `CBT_PATCH_IMPORT_DOCX_READY_FILTER_2026-11-26.zip`.
Tidak ada migration atau perubahan database.

## PENGUJIAN
- PHP lint kedua file: PASS.
- PHPUnit: BLOCKED; tests/, phpunit.xml, vendor/bin/phpunit tidak ada di ekstraksi ZIP.
- Runtime Laravel/MySQL/browser: BLOCKED; runtime PHP 8.4.24 tidak memiliki DOM, ZIP, MBSTRING, PDO MySQL dan MySQL tidak tersedia.
- Uji transaksi, rollback, idempotensi, browser staging: NOT TESTED.

## GITHUB / ZIP
Branch kerja: `fix/import-docx-persistence-20261126`. Konektor GitHub sesi ini tidak menyediakan upload binary archive/release asset; ZIP baseline 18.5 MB belum tersinkron. Dokumen ini bukan bukti ZIP sudah terunggah.

## STATUS
Patch statis diterapkan dan lolos PHP lint. Runtime verification BLOCKED. Jangan deploy produksi sebelum pengujian MySQL disposable dan browser staging.