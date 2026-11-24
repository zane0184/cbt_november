# LAPORAN P0 — STUDENT IMPORT SECURITY PREFLIGHT
Tanggal: 25 November 2026
Status akhir: IMPLEMENTASI DAN FULL REGRESSION PASS PADA EXACT SHA 77e46497d4341078b9310c6454c42fdbe774542b

## 1. AUDIT
Audit exact baseline work/benchmark-cleanup-proof-20261125 @ b6148507bd77116dfc8a5f5697cf4cff226a4b87 menemukan:
- StudentImportService memanggil PhpSpreadsheet IOFactory::load sebelum ZIP/OOXML preflight.
- Batas worksheet sebelumnya diperiksa setelah workbook dibuka.
- CSV menggunakan fgetcsv tanpa batas record eksplisit dan tanpa batas kolom.
- Belum ada test hostile OOXML/ZIP bomb.

## 2. DESAIN
Hardening dilakukan sebelum parser:
- Maksimum upload 10 MiB.
- Maksimum 256 ZIP entries.
- Maksimum 24 MiB per ZIP entry dan 32 MiB total expanded size.
- Compression ratio maksimum 100.
- Maksimum XML entry 24 MiB.
- Tolak path traversal, duplicate entry names, external relationships, macro/embedded object, DTD dan ENTITY.
- Wajib ada [Content_Types].xml, xl/workbook.xml dan worksheet OOXML.
- Periksa dimension dan cell reference aktual; maksimum 5.000 baris data dan 32 kolom.
- Gunakan reader XLSX data-only dan load worksheet pertama saja.
- CSV maksimum 64 KiB per record, 32 kolom, 5.000 baris, dan upload 10 MiB.
- NISN tetap string 10 digit; tidak ada backfill atau perubahan skema database.

## 3. IMPLEMENTASI
Branch kerja: work/p0-student-import-security-20261125
Baseline parent: b6148507bd77116dfc8a5f5697cf4cff226a4b87
Head saat laporan: 77e46497d4341078b9310c6454c42fdbe774542b.

File aplikasi diubah:
- backend-laravel/app/Services/StudentImportService.php
- backend-laravel/tests/Feature/StudentImportTest.php

File CI baru:
- .github/workflows/student-import-security-ci.yml

Backup byte-exact sebelum patch:
- backend-laravel/backup_before_patch/STUDENT_IMPORT_SECURITY_PREFLIGHT_20261125/StudentImportService.php.before
- backend-laravel/backup_before_patch/STUDENT_IMPORT_SECURITY_PREFLIGHT_20261125/StudentImportTest.php.before
- backend-laravel/backup_before_patch/STUDENT_IMPORT_SECURITY_PREFLIGHT_20261125/BACKUP_MANIFEST.md

SHA-256 asli dan backup cocok:
- StudentImportService.php: b0e6ee0854d2884b3036c699719af6afc127b366415ef3ccdb6fe90e1891298f
- StudentImportTest.php: ff1247fd264c240aebb44dfbc929c091ec975c016bfbcb155940c13932d1439f

Backup Git blob SHA-1 juga cocok identik. Backup tidak ditimpa. Workflow distribusi yang diaudit mengecualikan folder backup_before_patch dari ZIP distribusi.

Tidak ada migration, database produksi, VPS, atau konfigurasi live yang diubah. Tidak ada merge atau deployment.

## 4. PENGUJIAN
GitHub Actions run: 45479381320
Exact head SHA: 77e464 pada branch work/p0-student-import-security-20261125.
Environment: PHP 8.3 dan PHP 8.4, MySQL 8.0 InnoDB disposable.

Pada saat laporan dibuat:
- Check patch whitespace: PASS.
- Composer validation/install: PASS.
- PHP lint: PASS pada kedua job.
- Student Import security acceptance test step: PASS pada kedua job.
- Focused Student Import security acceptance: 22 passed, 390 assertions pada PHP 8.3 dan PHP 8.4.
- Full PHPUnit suite: masing-masing 607 passed, 1 skipped, 0 failed, 3.163 assertions pada PHP 8.3 dan PHP 8.4.
- Kedua job memverifikasi database disposable cbt_ci_student_import_security / MySQL 8.0.46 / InnoDB.
- Git diff --check, PHP lint, Composer validate/install: PASS.
- GitHub Actions run 45479381320: SUCCESS pada exact SHA 77e46497d4341078b9310c6454c42fdbe774542b.

Catatan: hasil di atas berasal dari exact source SHA 77e46497d4341078b9310c6454c42fdbe774542b, bukan dari commit pembanding yang lebih lama.

## 5. STATUS
- Implementasi preflight XLSX/OOXML dan CSV: DITULIS.
- Backup byte-exact + SHA-256: PASS.
- PHP lint dan focused security test pada exact SHA 77e464: PASS menurut step conclusion GitHub Actions.
- Full PHPUnit exact SHA 77e46497d4341078b9310c6454c42fdbe774542b: PASS.
- Staging/VPS: BELUM DIUJI.
- ZIP acceptance: BELUM DIUJI.
- Student Import siap produksi: BELUM.
- ZIP SIAP UJI OPERASIONAL TERBATAS: BELUM.

## 6. TINDAK LANJUT
1. Buat Draft PR dari work/p0-student-import-security-20261125 ke work/benchmark-cleanup-proof-20261125 untuk review; jangan merge.
2. Lakukan staging acceptance pada salinan/staging yang terisolasi setelah persetujuan operasional; jangan sentuh database produksi.
3. Setelah Student Import security diterima, lanjutkan DOCX question parser sesuai owner addendum 25 November: TRUE_FALSE_GROUP tiga stable IDs, scoring proporsional, shared stimulus first-class dan immutable snapshot.
4. Port Web Siswa runner secara selektif; jangan membawa ExamSession/SessionRoom/session lifecycle dari PR #13.


## 7. UPDATE AUDIT — DIVERGENCE SETELAH PR DIBUAT

Setelah PR #24 dibuat, branch integrasi `work/benchmark-cleanup-proof-20261125` bergerak maju secara independen. Source aktual kini memiliki implementasi terpisah:
- `backend-laravel/app/Services/Import/XlsxPackagePreflight.php`
- `backend-laravel/app/Services/Import/StudentImportReadFilter.php`
- `backend-laravel/tests/Feature/StudentImportXlsxPreflightTest.php`
- perubahan lanjutan pada `StudentImportService.php`.

Head integrasi terbaru yang terverifikasi saat update: `653412daace51fa973c697f98195a9fa8a91219a`.
Implementasi branch P0 yang dibuat sebelumnya tidak boleh digabungkan langsung karena duplikasi dan divergence.

PR #24 telah diubah judul/body menjadi **SUPERSEDED — DO NOT MERGE** dan tetap DRAFT/OPEN sebagai catatan audit. PR #23 tidak disentuh.

### Temuan tambahan pada source integrasi
1. XLSX preflight saat ini lebih komprehensif daripada patch branch P0: entry count, expanded size, compression ratio, path safety, symlink, macro/OLE/external relationship, XML DTD/entity, workbook relationship, satu worksheet visible, dimension/cell bounds, formulas, shared strings, styles, dan per-cell byte limit.
2. CI pada SHA `385e66cacda4b33b1c61ba6abc75a2ad2dd3d44a` gagal pada `StudentImportTest::test_xlsx_worksheet_dimensions_are_rejected_before_php_spreadsheet_load` karena component tidak menerima validation error.
3. Perbandingan source menunjukkan preflight menggunakan `MAX_ROWS = 5001`, sehingga batas row off-by-one. Commit `653412d` mengubahnya menjadi `5000` dan memulai CI run `45480853326`. Run tersebut masih berjalan pada saat update; hasil final belum boleh diklaim.
4. Audit source `StudentImportService::readCsv()` menemukan record kosong diproses sebagai row biasa dan jumlah kolom diperiksa sebelum `isBlankRow()` dapat digunakan oleh preview. Blank line CSV berpotensi ditolak walaupun preview memiliki mekanisme skip blank row. Ini perlu dikonfirmasi dengan test regresi dan diperbaiki bila kontrak import memang mengizinkan blank row.
5. Test saat ini belum mencakup batas CSV yang sudah diterapkan: record 256 KiB, 32 kolom, cell 4 KiB, UTF-8/NUL, total cell, serta blank rows. Tambahkan acceptance tests pada branch integrasi setelah perubahan agent saat ini stabil.

### Keputusan koordinasi
- Source `work/benchmark-cleanup-proof-20261125` adalah sumber kebenaran integrasi terbaru.
- Jangan cherry-pick atau merge branch P0 lama.
- Tunggu CI run `45480853326` pada SHA `653412d`.
- Setelah branch stabil, buat branch korektif baru dari head integrasi terkini untuk blank-row CSV dan coverage batas CSV; backup source/test terlebih dahulu.
