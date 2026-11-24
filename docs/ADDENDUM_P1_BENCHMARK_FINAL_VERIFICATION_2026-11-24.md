# Addendum P1 — Final Verification Benchmark MySQL Disposable

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
Exact source HEAD tested: `dbe99de52dd22f8a2a0852908feeca4200e02278`  
GitHub Actions: https://github.com/zane0184/cbt_november/actions/runs/45382796481  
Workflow conclusion: **SUCCESS**

## A. AUDIT

CI exact source SHA `dbe99de52dd22f8a2a0852908feeca4200e02278` completed successfully.

- PHP 8.3.37 / Laravel 13.42.0.
- MySQL 8.0.46, InnoDB.
- PHP lint: PASS.
- Focused Web login and tenant identity smoke: 57 passed / 242 assertions.
- Full PHPUnit: **549 passed / 0 failed / 2.353 assertions**.
- New rombel filter regression test: PASS.
- New failed-export cleanup test: PASS.

## B. AUTHORIZATION POLICY — VERIFIED

Success path:
- Benchmark uses the real `ReportController::exportPdf()`.
- Controller executes `authorize('viewExamResults', $exam)`.
- Actor is a real seeded Guru with exact role `GURU`.
- `RolePermissionBootstrapper` grants the official `report.view` permission.
- A valid active `TeacherSubjectAssignment` binds teacher, subject, rombel, academic year, semester, curriculum version, owner and tenant registration.
- Assessment carries the same pinned curriculum context.
- No Policy mock, bypass, permission fabrication, or access relaxation is used in the successful benchmark.

Failure path:
- A separate PHPUnit regression test intentionally omits the official grant only in its disposable test fixture.
- It calls the real ReportController and real ReportPolicy.
- Gate after-callback observed `viewExamResults = false`.
- The temporary schema was present at the moment of Policy denial and absent after command failure.
- No Policy is mocked or bypassed.

## C. PDF OUTPUT — VERIFIED

The benchmark persists the actual response into a temporary `.pdf` file and checks HTTP status, non-empty body, full write, file size, and PDF signature.

- Warm-up: HTTP 200, valid `%PDF-` signature, **19,693 bytes**.
- Measured run 1: HTTP 200, valid `%PDF-` signature, **19,693 bytes**.
- The temporary PDF files are deleted and absence checked by the harness.

## D. BENCHMARK RESULTS

Disposable workload:
- 1 tenant, 1 rombel, 100 students.
- 10 exams, 500 question snapshots.
- 1 CP, 2 ATP, 5 TP.
- 1 measured run per operation.

| Operation | Result | Peak memory |
|---|---:|---:|
| Batch achievement calculation (100 students) | 1,732.86 ms | 50.50 MB |
| Combined 100-student PDF export | 468.05 ms | 72.50 MB |

Governance thresholds:
- Batch calculation <= 2,000 ms: PASS.
- PDF export <= 3,000 ms: PASS.
- Peak memory <= 128 MB: PASS.
- Batch consistency CV <= 15%: PASS (0.00% with one run).
- PDF consistency CV <= 15%: PASS (0.00% with one run).

Overall benchmark status: **PASSED**.

Note: only one measured run was configured. CV 0.00% for a single sample is a mathematical artifact and is not evidence of repeatability under load. A multi-run performance/stability campaign remains future work.

## E. DATABASE CLEANUP — VERIFIED

Success path:
- Temporary schema `cbt_phase7d_*` was dropped in `finally`.
- `INFORMATION_SCHEMA.SCHEMATA` was queried after DROP.
- Log: `Temporary MySQL database removed; absence verified.`

Failure path:
- PHPUnit intentionally triggered real Policy denial after temporary schema creation.
- Test confirmed schema existed during denial.
- Test confirmed no `cbt_phase7d_*` schema remained after failure.

Production safety:
- `APP_ENV=testing`, local MySQL service and disposable CI database only.
- Production/remote DB guard remains fail-closed.
- No production database, VPS, or production migration was accessed.

## F. IMPLEMENTASI YANG DIVERIFIKASI

- Disposable MySQL benchmark replaces incompatible SQLite benchmark fixture.
- PDF physical file validation and cleanup.
- Temporary database cleanup with INFORMATION_SCHEMA absence verification.
- Teacher policy fixture follows official curriculum registration and assignment.
- Report rombel query closure captures `$exam` correctly.
- Report filter test creates valid Curriculum → Phase → GradeLevel → Rombel.
- Failure-path test proves real Policy denial and schema cleanup.

Backups byte-exact are recorded in:
- `backend-laravel/backup_before_patch/P1_BENCHMARK_MYSQL_DISPOSABLE_20261124/BACKUP_MANIFEST.md`
- `backend-laravel/backup_before_patch/P1_BENCHMARK_POLICY_FIXTURE_20261124/BACKUP_MANIFEST.md`
- `backend-laravel/backup_before_patch/P1_REPORT_ROMBEL_FILTER_CAPTURE_20261124/BACKUP_MANIFEST.md`
- `backend-laravel/backup_before_patch/P1_GRADE_LEVEL_AND_CLEANUP_TEST_20261124/BACKUP_MANIFEST.md`
- `backend-laravel/backup_before_patch/P1_BENCHMARK_POLICY_DENIAL_CLEANUP_20261124/BACKUP_MANIFEST.md`

## G. STATUS

- P1-ID-03A benchmark: **PASS — verified**.
- MySQL disposable success cleanup: **PASS — verified**.
- Real Policy denial failure cleanup: **PASS — verified**.
- Exact source SHA CI: **PASS — verified**.
- PR #23 remains OPEN / DRAFT / NOT MERGED.
- This does not imply production readiness or release approval.

## H. NEXT BACKLOG

Continue the P1 main backlog after this gate:
1. Audit and complete remaining Web Admin account lifecycle and identity database invariants.
2. Audit Web Siswa login/resume/admission against the master spec, including session/device binding.
3. Close Web Siswa IndexedDB recovery, multi-tab, offline synchronization and server admission gaps.
4. Continue integration and multi-device Device Lease/Fencing validation; do not mark production-ready without those tests.
5. Continue Web Guru/assessment publishing and end-to-end role/tenant regression.
