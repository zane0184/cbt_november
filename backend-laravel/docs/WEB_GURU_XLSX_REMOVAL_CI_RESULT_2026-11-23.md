# Hasil CI — Penghapusan Import XLSX Soal Web Guru
Tanggal: 23 November 2026

## Source yang diuji
- Branch: `work/web-guru-remove-xlsx-20261123`
- Commit yang diuji: `4c5f97e85775a2e14aead329a1d2101470c15f9b`
- PR: [#19](https://github.com/zane0184/cbt_november/pull/19)

HEAD PR setelah hasil CI adalah `2ddadea4208c967703c51796e1ca9b4420606660`. GitHub compare menunjukkan hanya satu commit tambahan setelah SHA yang diuji, yaitu penambahan manifest backup `MANIFEST_TEST_FIXES.md`; tidak ada source aplikasi atau test berubah setelah commit yang diuji.

## Hasil
GitHub Actions run [45107615631](https://github.com/zane0184/cbt_november/actions/runs/45107615631):
- Status: **SUCCESS**
- Checkout log membuktikan source SHA yang diuji persis `4c5f97e85775a2e14aead329a1d2101470c15f9b`.
- PHPUnit: **82 passed, 378 assertions**, durasi 13.51 detik.
- PHP lint: lulus.
- Laravel route registration: lulus; route Guru aktif terdaftar dan route import/template XLSX tidak ada.
- Composer validate, install locked dependencies, dan platform requirements: lulus.
- Disposable MySQL 8: identitas database dan schema kosong diverifikasi sebelum test.
- Setelah migration: **80 base tables, seluruhnya InnoDB**.
- Disposable database dan user dibersihkan oleh workflow.

## Koreksi yang dilakukan setelah CI awal
Run awal menemukan dua test yang perlu diperbaiki:
1. Test navigasi mengharapkan route XLSX yang sengaja dihapus. Assertion diganti agar memastikan route Bank Soal tetap ada dan route import/template tidak terdaftar.
2. Fixture versioning menggunakan SHORT_ANSWER yang memang BLOCKED. Fixture diganti ESSAY yang didukung untuk menguji immutability, tanpa mengubah guard SHORT_ANSWER.

Setelah koreksi, run final di atas lulus pada source SHA yang sesuai.

## Batas hasil
CI ini memverifikasi suite Web Guru yang dipilih, bukan seluruh aplikasi. Belum dilakukan browser E2E/visual QA, concurrency/load testing, DOCX parser testing, UAT madrasah, deployment VPS, atau production readiness.

## Status
**Penghapusan jalur XLSX khusus soal: IMPLEMENTED + CI PASS pada SHA yang tercantum.**
**DOCX: NOT IMPLEMENTED; menunggu kontrak final/approval Agent 2 pada Issue #9.**
Tidak ada merge, deployment, migration produksi, atau perubahan DB produksi.
