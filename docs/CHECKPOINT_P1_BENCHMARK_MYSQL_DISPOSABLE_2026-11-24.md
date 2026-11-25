# Checkpoint P1 — Disposable MySQL Benchmark

Tanggal: 24 November 2026  
Branch: `work/p1-seven-targets-20261124`  
Source code commit: `996138c972720ca38041aa3af802e16ed4a4cca5`  
PR #23: OPEN / DRAFT / NOT MERGED

## Posisi

- PHPUnit pada SHA sebelumnya: 542 PASS / 0 FAIL / 2.334 assertions.
- SQLite benchmark gagal karena migration memakai ALTER TABLE ADD CONSTRAINT CHECK.
- Benchmark kini memakai MySQL temporary schema `cbt_phase7d_*`.
- Guard: APP_ENV local/testing, opt-in flag + environment variable, local host, database prefix disposable.
- Cleanup: DROP DATABASE + INFORMATION_SCHEMA absence verification.
- Safety tests ditambahkan.
- Backup byte-exact tersedia di dua manifest P1_BENCHMARK_*.

## Pengujian

- CI exact source SHA: PENDING.
- MySQL benchmark smoke: PENDING.
- Temporary schema cleanup: PENDING.
- No production DB/migration touched.

## Status

P1-ID-03A: PARTIAL.  
P1-ID-03B: BLOCKED.  
PR #23 tetap Draft.
