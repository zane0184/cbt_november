# Checkpoint P1 — Benchmark Teacher Policy Fixture

Tanggal: 24 November 2026  
Branch: `work/p1-seven-targets-20261124`  
Source code commit: `f005cfc5fc23a4d8403a809f93128912e9817496`  
PR #23: OPEN / DRAFT / NOT MERGED

## Posisi

- PHPUnit: 547 PASS / 0 FAIL / 2.344 assertions pada SHA sebelumnya.
- MySQL disposable migration dan workload berhasil; temporary schema dibersihkan dan diverifikasi.
- PDF export sebelumnya ditolak policy karena memakai Admin.
- Fixture diperbaiki: role grant report.view, official curriculum registration, teacher assignment, assessment pinned context, semua peserta tercakup, Guru sebagai actor.
- Authorization policy tetap aktif dan tidak di-bypass.
- Backup: `P1_BENCHMARK_POLICY_FIXTURE_20261124/BACKUP_MANIFEST.md`.

## Pengujian

- CI exact source SHA: PENDING.
- PDF export benchmark: PENDING.
- Cleanup temporary MySQL: PENDING.
- Production DB/VPS: tidak disentuh.

## Status

P1-ID-03A: PARTIAL.  
P1-ID-03B: BLOCKED.  
PR #23 tetap Draft.
