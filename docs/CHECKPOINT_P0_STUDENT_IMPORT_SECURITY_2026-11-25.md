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

## Menunggu
- Full PHPUnit suite PHP 8.3 + MySQL 8.0 InnoDB disposable.
- Full PHPUnit suite PHP 8.4 + MySQL 8.0 InnoDB disposable.
- Jangan klaim full suite PASS sampai kedua job selesai dan log final diperiksa.

## Jangan dilakukan
- Jangan jalankan migration pada database produksi.
- Jangan sentuh VPS atau aplikasi live.
- Jangan merge PR #23 atau ubah status/base PR #23.
- Jangan aktifkan importer XLSX untuk soal.
- Jangan mengklaim Student Import siap produksi atau ZIP siap uji operasional.

## Langkah berikutnya
1. Verifikasi hasil final run 45479381320 pada exact SHA 77e464.
2. Jika dua full suite PASS, buat report/checkpoint final dan Draft PR ke work/benchmark-cleanup-proof-20261125; tidak merge.
3. Jika gagal, perbaiki akar masalah dan ulangi test.
4. Berikutnya: implementasi DOCX question parser dan TRUE_FALSE_GROUP/shared stimulus sesuai addendum owner 25 November.
5. Setelah itu: integrasi runner Web Siswa secara selektif, browser-to-real-Laravel E2E, Guru import XLSX/CSV, curriculum provenance, dan ZIP acceptance.

## Status
Student Import hardening: IMPLEMENTED; FOCUSED TEST PASS; FULL SUITE PENDING.
ZIP SIAP UJI OPERASIONAL TERBATAS: BELUM.
