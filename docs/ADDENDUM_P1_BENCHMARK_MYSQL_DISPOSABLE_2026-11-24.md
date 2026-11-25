# Addendum P1 — Benchmark MySQL Disposable

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
Source code commit: `996138c972720ca38041aa3af802e16ed4a4cca5`  
PR #23: OPEN / DRAFT / NOT MERGED

## A. AUDIT

CI sebelumnya pada SHA `e905...`:
- PHP lint: PASS.
- Focused login/tenant smoke: PASS.
- Full PHPUnit: **542 passed, 0 failed, 2.334 assertions**.
- Benchmark SQLite smoke: FAIL karena migration `tp_achievements` memakai `ALTER TABLE ... ADD CONSTRAINT CHECK`, yang tidak didukung SQLite.

SQLite bukan mesin database target proyek. Mengubah constraint migration hanya untuk menyesuaikan benchmark SQLite berisiko menurunkan kesetaraan MySQL/SQLite dan tidak dipilih.

## B. DESAIN

Benchmark dipindahkan ke MySQL 8 disposable:
- Hanya APP_ENV local/testing.
- Wajib `--allow-mysql` dan `CBT_BENCHMARK_ALLOW_MYSQL=1`.
- Host hanya localhost/127.0.0.1/::1/mysql.
- Database dasar wajib memakai prefix disposable `cbt_ci_`, `cbt_test_`, `cbt_benchmark_`, atau `testing`.
- Command membuat schema sementara bernama `cbt_phase7d_*`, menjalankan migration dan workload di schema tersebut, lalu drop dan memverifikasi ketiadaan schema pada INFORMATION_SCHEMA.
- Production dan remote database ditolak sebelum koneksi/migration.
- Default connection, tenant context, dan auth state dipulihkan pada `finally`.

## C. IMPLEMENTASI

- `Phase7dBenchmarkCommand.php`: driver MySQL disposable, opt-in ganda, guard environment/host/database, cleanup schema, verifikasi INFORMATION_SCHEMA, database version pada laporan.
- `tenant-backend-ci.yml`: benchmark smoke menggunakan MySQL service disposable, bukan SQLite.
- `Phase7dBenchmarkCommandSafetyTest.php`: test penolakan tanpa opt-in, remote host, database prefix tidak aman, dan production environment.

Backup byte-exact:

`backend-laravel/backup_before_patch/P1_BENCHMARK_MYSQL_DISPOSABLE_20261124/BACKUP_MANIFEST.md`

Test safety backup:

`backend-laravel/backup_before_patch/P1_BENCHMARK_SAFETY_TEST_FIX_20261124/BACKUP_MANIFEST.md`

## D. PENGUJIAN

- Full PHPUnit pada SHA sebelumnya: 542 PASS / 0 FAIL / 2.334 assertions.
- Benchmark SQLite: gagal karena incompatibility migration; tidak ada perubahan migration.
- MySQL disposable benchmark smoke: menunggu CI exact source SHA `996138c9...`.
- Cleanup database sementara dan absence verification: menunggu CI.
- PHP lint untuk command/test: menunggu CI.

## E. STATUS

**Benchmark MySQL disposable: IMPLEMENTASI TERSEDIA, NOT VERIFIED.**

P1-ID-03A tetap PARTIAL sampai CI exact HEAD lulus. P1-ID-03B tetap BLOCKED. Tidak ada migration atau database produksi yang dijalankan.

## F. NEXT ACTION

1. Jalankan CI exact HEAD.
2. Jika benchmark gagal, periksa log seed/migration/cleanup; jangan melemahkan constraint.
3. Jika lulus, port account lifecycle dari branch W1 secara selektif ke PR23.
4. Pertahankan exact login identity dan P0 hardening.
