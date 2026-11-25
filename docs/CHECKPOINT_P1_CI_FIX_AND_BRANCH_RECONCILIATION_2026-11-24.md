# Checkpoint P1 — CI Fix dan Branch Reconciliation

Tanggal: 24 November 2026  
Branch integrasi: `work/p1-seven-targets-20261124`  
Source code commit: `1e4832b8dec08e6b133e6a93e81aaf91dd5e4e7b`  
PR #23: OPEN / DRAFT / NOT MERGED

## Posisi

- Perbaikan audit reason: reason operasional dipertahankan, PII di dalam teks disamarkan.
- `scoring_skipped_reason` dipulihkan.
- NISN fixture dipendekkan.
- Test cross-tenant profile sekarang membuktikan composite FK menolak insert yang tidak sah.
- Backup byte-exact: `P1_AUDIT_REASON_AND_PREFLIGHT_FIX_20261124/BACKUP_MANIFEST.md`.

## Branch decision

- PR23 dipilih sebagai integration branch karena berbasis PR22/P0 hardening.
- Branch W1 `work/solo-web-cbt-completion-20261123-execution` memiliki banyak fitur Admin/lifecycle tetapi 75 commit behind PR22 dan masih memiliki username fallback Guru.
- W1 dipakai sebagai referensi porting selektif, bukan merge wholesale.

## Pengujian

- CI sebelumnya: 536 PASS / 5 FAIL; kegagalan sudah diperbaiki.
- CI exact source SHA: PENDING.
- Full PHPUnit MySQL 8: PENDING.
- Benchmark SQLite smoke: PENDING.
- Produksi/VPS tidak disentuh.

## Status

- P1-ID-03A: PARTIAL.
- P1-ID-03B: BLOCKED.
- Account lifecycle port ke PR23: NEXT.
- Tidak ada merge, migration, deployment.
