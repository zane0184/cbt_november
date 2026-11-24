# Addendum P1 — Perbaikan CI dan Rekonsiliasi Branch

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Integration branch: `work/p1-seven-targets-20261124`  
Source code commit: `1e4832b8dec08e6b133e6a93e81aaf91dd5e4e7b`  
PR #23: OPEN / DRAFT / NOT MERGED

## A. AUDIT — Hasil CI sebelumnya

CI pada SHA `f87c29210dafab056afa73a6a1385e3759e424e6` menghasilkan 536 PASS dan 5 FAIL (2.310 assertions). Lima kegagalan berasal dari tiga kelompok:

1. Sanitizer menghapus seluruh nilai `reason`, sehingga menghilangkan alasan operasional yang memang harus tercatat pada audit.
2. Fixture NISN melebihi panjang kolom; fixture tenant mismatch melanggar composite FK yang aktif.
3. Field audit `scoring_skipped_reason` berubah nama, mematahkan kontrak test yang sudah ada.

## B. DESAIN DAN PERBAIKAN

1. Field `reason` dipertahankan sebagai konteks audit; sanitizer hanya mengganti email, nomor telepon, dan rangkaian digit identitas pada free text.
2. Nilai enum reason seperti `ACTIVE_ATTEMPT_IN_PROGRESS` tetap dipertahankan.
3. Field `scoring_skipped_reason` dikembalikan ke nama kontrak sebelumnya.
4. Fixture NISN invalid memakai nilai pendek yang tetap berada dalam batas kolom.
5. Test tenant mismatch tidak lagi mencoba memasukkan data yang ditolak oleh composite FK. Test terpisah sekarang membuktikan MySQL menolak relasi profil Guru lintas tenant.
6. Foreign key tidak dilemahkan.

## C. REKONSILIASI BRANCH

Ditemukan branch paralel:

`work/solo-web-cbt-completion-20261123-execution`  
HEAD: `2a82331fe7aede1d051af1c4e268af9e772123d0`

Perbandingan dengan base PR #22 (`1c33e5d422175f9bd85d206230271a9fe5a4eecd)) menunjukkan branch tersebut 314 commit ahead dan 75 commit behind. Branch ini memiliki implementasi Web Admin/account lifecycle yang lebih luas, tetapi:
- tertinggal dari P0 hardening PR #22;
- LoginIdentityResolver masih memiliki jalur username fallback Guru yang dilarang oleh keputusan owner terbaru;
- belum memiliki preflight identitas P1 yang sekarang dibangun pada branch integrasi.

PR #21 juga memakai base yang lebih lama (`f58c0a...`). Karena itu, branch W1 tidak aman untuk digabung secara wholesale.

**Keputusan koordinasi:** lanjutkan PR #23 sebagai integration branch karena berbasis source PR #22 yang lebih baru. Gunakan branch W1 sebagai referensi porting fitur secara selektif, dengan mempertahankan P0 fixes, kontrak login terbaru, dan semua test yang sudah ada. Tidak ada merge atau cherry-pick massal.

## D. IMPLEMENTASI

- `AuditPayloadSanitizer`: reason text dipertahankan dengan redaksi pola PII.
- `ExpireExamAttemptsCommand`: nama `scoring_skipped_reason` dipulihkan.
- `AuditPayloadSanitizerTest`: menguji redaksi email, telepon, NISN dalam reason tanpa menghapus konteks.
- `LoginIdentityPreflightCommandTest`: fixture NISN diperbaiki, test relasi role/cardinality tetap read-only, dan test baru membuktikan FK menolak profil lintas tenant.

Backup byte-exact:

`backend-laravel/backup_before_patch/P1_AUDIT_REASON_AND_PREFLIGHT_FIX_20261124/BACKUP_MANIFEST.md`

## E. PENGUJIAN

- CI sebelumnya: 536 PASS, 5 FAIL; seluruh kegagalan sudah dianalisis.
- CI exact source commit `1e4832b8...`: MENUNGGU.
- PHP lint dan full PHPUnit MySQL 8: MENUNGGU.
- Benchmark smoke SQLite: MENUNGGU.
- Build ZIP sebelumnya bukan acceptance ZIP final.
- Tidak ada migration atau perubahan database produksi.

## F. STATUS DAN PEKERJAAN BERIKUTNYA

- Audit reason redaction: PATCHED, NOT VERIFIED.
- Login identity preflight: PATCHED, NOT VERIFIED.
- P1-ID-03A: PARTIAL.
- P1-ID-03B: BLOCKED.
- Berikutnya: CI exact HEAD → port account lifecycle W1 secara selektif ke PR23 → integrasi Web Admin/Guru/Siswa → kurikulum → E2E → staging → ZIP.
- Account lifecycle W1 memiliki test report 565 PASS/2.544 assertions pada source SHA historisnya; angka tersebut bukan bukti bahwa port pada PR23 sudah lulus.

## G. SAFETY

Tidak ada merge, deployment, migration produksi, atau perubahan database produksi. PR #23 tetap Draft.
