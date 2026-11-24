# LAPORAN P0 — STUDENT IMPORT SECURITY PREFLIGHT
Tanggal: 25 November 2026
Status saat laporan: IMPLEMENTASI TERTULIS; FULL REGRESSION MASIH BERJALAN

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
- Full PHPUnit suite: masih IN PROGRESS pada kedua job.
- Jumlah assertions dan hasil final full suite untuk exact SHA 77e464 belum tersedia; jangan klaim PASS sampai job selesai.

Pembanding historis, bukan pengganti exact latest SHA:
- Commit b033cad: PHP 8.3 dan 8.4 masing-masing 607 passed, 1 skipped, 0 failed, 3.153 assertions.
- Commit b033cad focused Student Import: 22 passed, 380 assertions pada PHP 8.3 dan 8.4.
Commit b033cad mendahului penyempurnaan cell-scan dan assertion reason pada 77e464; hasil tersebut hanya bukti pendukung.

## 5. STATUS
- Implementasi preflight XLSX/OOXML dan CSV: DITULIS.
- Backup byte-exact + SHA-256: PASS.
- PHP lint dan focused security test pada exact SHA 77e464: PASS menurut step conclusion GitHub Actions.
- Full PHPUnit exact SHA 77e464: PENDING.
- Staging/VPS: BELUM DIUJI.
- ZIP acceptance: BELUM DIUJI.
- Student Import siap produksi: BELUM.
- ZIP SIAP UJI OPERASIONAL TERBATAS: BELUM.

## 6. TINDAK LANJUT
1. Tunggu dan verifikasi full PHPUnit pada PHP 8.3 dan 8.4 untuk exact SHA 77e464.
2. Jika lulus, ambil log final, perbarui checkpoint, lalu buat Draft PR dari work/p0-student-import-security-20261125 ke work/benchmark-cleanup-proof-20261125. Jangan merge.
3. Jika gagal, analisis test/log dan perbaiki tanpa menghapus assertion atau mengubah FAIL menjadi SKIPPED.
4. Setelah Student Import security diterima, lanjutkan DOCX question parser sesuai owner addendum 25 November: TRUE_FALSE_GROUP tiga stable IDs, scoring proporsional, shared stimulus first-class dan immutable snapshot.
5. Port Web Siswa runner secara selektif; jangan membawa ExamSession/SessionRoom/session lifecycle dari PR #13.
