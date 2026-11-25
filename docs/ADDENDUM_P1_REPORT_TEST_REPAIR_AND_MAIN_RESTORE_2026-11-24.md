# Addendum P1 — Repair Regression Test dan Pemulihan Branch Default

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch integrasi: `work/p1-seven-targets-20261124`  
Source code commit: `51d5352625f43e39d84601a67471bba16515ab68`  
PR #23: OPEN / DRAFT / NOT MERGED

## A. AUDIT

Setelah patch closure capture:
- Source service sudah benar.
- Pemeriksaan ulang menemukan test baru sempat terletak di luar class akibat file lama memakai CRLF dan insertion marker tidak cocok dengan penutup class.
- Test tidak dibiarkan dalam kondisi tersebut; file dipulihkan dari backup byte-exact pra-patch dan test dimasukkan kembali sebelum penutup class.
- Verifikasi source akhir: 28 opening braces dan 28 closing braces; method berada di dalam class.

## B. IMPLEMENTASI

- `Phase7DReportingTest.php` dibangun ulang dari backup `P1_REPORT_ROMBEL_FILTER_CAPTURE_20261124`, dinormalisasi ke LF, lalu test rombel filter ditempatkan sebelum penutup class.
- Service `ExamResultReportService.php` tetap memakai dua closure yang menangkap `$exam`.
- Backup kondisi file test sebelum repair:
  `backend-laravel/backup_before_patch/P1_REPORT_ROMBEL_FILTER_TEST_REPAIR_20261124/BACKUP_MANIFEST.md`.
- Backup awal service/test:
  `backend-laravel/backup_before_patch/P1_REPORT_ROMBEL_FILTER_CAPTURE_20261124/BACKUP_MANIFEST.md`.

## C. KOREKSI BRANCH DEFAULT

Satu manifest backup sempat dibuat tanpa parameter branch sehingga masuk ke `main`. Kesalahan langsung dikoreksi:
- File manifest dihapus dari `main`.
- Corrective commit pada main: `9e91da87b4850e56ff26eab86125c2a25b42a6b9`.
- Pemeriksaan ulang: path manifest **NOT FOUND** pada `main`, dan tersedia pada branch integrasi.
- Tidak ada source aplikasi, migration, atau konfigurasi produksi yang berubah pada `main`; net file tree untuk path tersebut kembali seperti semula.

## D. PENGUJIAN

- PHPUnit sebelum report-service patch: 547 PASS / 0 FAIL / 2.344 assertions.
- Benchmark MySQL sebelumnya mencapai ReportController dan menemukan undefined `$exam`; database temporary tetap berhasil dihapus dan absence diverifikasi.
- Regression test baru dan benchmark exact HEAD `51d53526...`: menunggu CI.
- Tidak ada migration atau database produksi.

## E. STATUS

Report rombel filter: PATCHED, NOT VERIFIED.  
Benchmark PDF: NOT VERIFIED.  
P1-ID-03A: PARTIAL.  
P1-ID-03B: BLOCKED.  
PR #23 tetap Draft.
