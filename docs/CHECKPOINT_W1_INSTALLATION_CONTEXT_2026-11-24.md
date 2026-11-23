# Checkpoint W1 — Web Login Installation Context

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/solo-web-cbt-completion-20261123-execution`

## Titik source dan test
- Latest tested source: `b726dcb55d5bcba60adb6bc34529df14ffb6dad9`.
- CI: [Tenant Backend CI #1668](https://github.com/zane0184/cbt_november/actions/runs/45264938898) — SUCCESS.
- Environment: PHP 8.3 + MySQL 8/InnoDB disposable databases.
- Focused Web Login/tenant smoke: 30 passed / 99 assertions.
- Admin Livewire reauthorization: 5 passed / 5 assertions.
- Teacher result reauthorization: 4 passed / 8 assertions.
- Full PHPUnit: 530 passed / 2,199 assertions / 0 failed.
- Backup paths and blob SHA: see `REPORT_W1_INSTALLATION_CONTEXT_NEGATIVE_TESTS_2026-11-24.md`.

## Source changes
Only `backend-laravel/tests/Feature/WebAdminAuthTest.php` changed for the regression tests. Existing file was backed up byte-exact twice before the respective edits. No application source, schema, migration, production DB, or deployment target changed.

## W1 status
The Web Login installation-context test gap is closed for the three negative conditions in the report. Other W1 acceptance criteria remain open until their specific evidence exists. Do not mark whole W1 / ADM backlog complete from this checkpoint.

## Next task
W2: fix Admin Dashboard metrics to show current active-year scope, active rombel/subject counts, and correct dynamic status/year display without duplicating existing sidebar/dashboard components.
