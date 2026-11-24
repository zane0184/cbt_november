# CHECKPOINT P0 — STUDENT IMPORT SECURITY
Tanggal: 25 November 2026

Branch kerja: work/p0-student-import-security-20261125
Baseline: b6148507bd77116dfc8a5f5697cf4cff226a4b87
Head saat checkpoint: 77e46497d4341078b9310c6454c42fdbe774542b
GitHub Actions run: 45479381320
PR #23: tetap OPEN / DRAFT / NOT MERGED

## Selesai
- Audit StudentImportService menemukan IOFactory::load sebelum OOXML preflight.
- Backup byte-exact StudentImportService.php dan StudentImportTest.php dibuat sebelum patch.
- Git blob SHA-1 dan SHA-256 source/backup cocok.
- Implementasi ZIP/OOXML preflight, CSV bounds, worksheet dimension/cell ref guard, data-only reader, first-sheet-only.
- 11 security regression tests ditambahkan (total StudentImportTest menjadi 22).
- PHP lint, composer, whitespace check dan focused security test steps pada exact SHA 77e464: PASS.

## Verifikasi final exact SHA 77e46497d4341078b9310c6454c42fdbe774542b
- GitHub Actions run 45479381320: SUCCESS.
- PHP 8.3 / MySQL 8.0.46 InnoDB disposable: 22 focused tests passed / 390 assertions; full suite 607 passed, 1 skipped, 0 failed / 3.163 assertions.
- PHP 8.4 / MySQL 8.0.46 InnoDB disposable: 22 focused tests passed / 390 assertions; full suite 607 passed, 1 skipped, 0 failed / 3.163 assertions.
- Composer validate/install, PHP lint, git diff --check: PASS.

## Jangan dilakukan
- Jangan jalankan migration pada database produksi.
- Jangan sentuh VPS atau aplikasi live.
- Jangan merge PR #23 atau ubah status/base PR #23.
- Jangan aktifkan importer XLSX untuk soal.
- Jangan mengklaim Student Import siap produksi atau ZIP siap uji operasional.

## Langkah berikutnya
1. Buat Draft PR ke work/benchmark-cleanup-proof-20261125 untuk review; tidak merge.
2. Lakukan staging acceptance terisolasi untuk Student Import; jangan sentuh database produksi.
3. Berikutnya: implementasi DOCX question parser dan TRUE_FALSE_GROUP/shared stimulus sesuai addendum owner 25 November.
4. Setelah itu: integrasi runner Web Siswa secara selektif, browser-to-real-Laravel E2E, Guru import XLSX/CSV, curriculum provenance, dan ZIP acceptance.

## Status
Student Import security hardening: IMPLEMENTED + CI VERIFIED pada exact SHA.
Student Import staging/operational acceptance: BELUM DIUJI.
ZIP SIAP UJI OPERASIONAL TERBATAS: BELUM.


## UPDATE KONTINUITAS — 25 NOVEMBER 2026

### Source of truth terbaru
Branch integrasi `work/benchmark-cleanup-proof-20261125` bergerak maju saat branch P0 terpisah dikerjakan. Head terbaru saat update: `653412daace51fa973c697f98195a9fa8a91219a`.

Branch integrasi sudah memiliki `XlsxPackagePreflight`, `StudentImportReadFilter`, dan `StudentImportXlsxPreflightTest`. Jangan merge PR #24 atau cherry-pick seluruh implementasi P0 lama. PR #24 telah ditandai **SUPERSEDED — DO NOT MERGE** dan tetap DRAFT/OPEN. PR #23 tetap tidak diubah.

### Gate terbaru
- CI SHA `385e66c`: FAIL pada test worksheet dimension karena preflight row limit off-by-one.
- Commit `653412d` mengubah `MAX_ROWS` dari 5001 menjadi 5000.
- CI run `45480853326` untuk exact SHA `653412d`: IN PROGRESS pada saat checkpoint. Tunggu hasil final sebelum lanjut.

### Temuan yang perlu ditangani setelah branch stabil
- CSV blank rows kemungkinan ditolak karena `readCsv()` memeriksa jumlah kolom sebelum melewati blank row; validasi perlu diuji.
- Belum ada test khusus untuk CSV record 256 KiB, 32 kolom, cell 4 KiB, UTF-8/NUL, total cells, dan blank rows.
- Backup source dan test pada branch integrasi sebelum koreksi apa pun.

### Langkah berikutnya
1. Verifikasi CI run `45480853326` dan source head terbaru.
2. Jika CI gagal, audit log dan perbaiki root cause tanpa menghapus assertion.
3. Jika branch stabil, buat branch korektif dari head terbaru; backup StudentImportService.php dan StudentImportTest.php byte-exact.
4. Perbaiki blank-row CSV dan tambah acceptance tests batas CSV.
5. Jalankan CI PHP 8.3/8.4 + MySQL 8 disposable.
6. Tutup PR #24 hanya setelah rekonsiliasi selesai; jangan merge.
7. Lanjutkan DOCX parser dan Web Siswa runner setelah P0 import gate lulus.
