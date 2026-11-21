# CHECKPOINT WA-01 GitHub Actions Runner Prepared — 2026-11-21

## Status
RUNNER PREPARED — NOT EXECUTED — WA-01 NOT PASS.

No migration, seeder, RolePermissionBootstrapper, application permission mutation, production database access, VPS action, PR, or deployment has been performed by this checkpoint.

## AUDIT

Repository: `zane0184/cbt_november`

Implementation branch: `work/wa01-package0-implementation-20261121`

Implementation branch HEAD after runner preparation: `e65dd6ffca632117b3d1c7efb3d9fb0bb18be852`

Previously verified WA-01 application-source commit: `11ab8e437af66fd6f35af81dfbcdec4fd391567f`

Pinned W0 baseline remains: `2b4321ce032fd5e67cf022da8564a5287b8cef6f`

Existing workflow inspection:
- `.github/workflows/tenant-backend-ci.yml`: PHP 8.3, Composer v2, MySQL 8.0, but root DB user, installer E2E database creation, and complete PHPUnit suite; unsuitable for this restricted WA-01 run.
- `.github/workflows/p0-95d-fresh-chain.yml`: executes fresh migration chain, schema dump/replay and rollback guard; explicitly excluded.
- `.github/workflows/build-web-deployment.yml`: build/package workflow, not a WA-01 isolated test runner.
- `.github/workflows/w0-source-snapshot-verify.yml`: source snapshot verification, not a PHP/MySQL test runner.

Dependency source:
- `backend-laravel/composer.json` requires PHP `^8.3`, Laravel `^13.0`, Livewire `^3.5`.
- The runner uses `composer.lock` via `composer install` and `composer check-platform-reqs`; it does not update the lock.

Test source inspection:
- `AdminCbtBoundaryTest::test_admin_with_stale_guru_role_and_permissions_is_denied_all_cbt_surfaces` does not call RolePermissionBootstrapper and covers Gate, route and Livewire denial.
- The second test in `AdminCbtBoundaryTest` explicitly calls RolePermissionBootstrapper and is excluded.
- `AdminUiVerificationTest` and `TeacherPracticeWorkflowTest` call `bootstrapCbtRolePermissions()` in setUp; they are excluded because the helper invokes RolePermissionBootstrapper.
- `AssessmentPolicyTeacherCreateTest` is a standalone policy regression without database setup.
- Teacher login regression is selected by a single method from `WebAdminAuthTest`.
- Siswa regressions selected: `StudentWebLoginTest` and `StudentResultVisibilityTest`.
- API tests selected: `AuthTest`, `MultiTenantAuthTest`.
- Tenant isolation selected: `TenantIsolationTest`.

## DESAIN

A separate workflow is used:
- File: `.github/workflows/wa01-disposable-mysql-test.yml`
- Trigger: manual `workflow_dispatch`.
- Job guard: runs only when `github.ref == refs/heads/work/wa01-package0-implementation-20261121`.
- Runner: `ubuntu-24.04`.
- PHP: 8.3.
- Composer: v2.
- MySQL service: `mysql:8.0`, default engine verified as InnoDB.
- Application database: unique `cbt_wa01_<run_id>_<attempt>` per job.
- Application user: unique per job; granted privileges only on that database. Root is used only inside the ephemeral service to create/drop the schema and restricted user.
- No GitHub production/VPS secrets are referenced.
- Preflight phase creates and verifies the empty disposable database and exits without PHPUnit or migration.
- Execute phase repeats the preflight and then runs the approved test subset. The only migration trigger is Laravel RefreshDatabase during the selected PHPUnit tests.
- Test artifacts retained 30 days.
- Cleanup drops the disposable schema and application user; the MySQL service is destroyed with the job.

## IMPLEMENTASI

New workflow created on implementation branch:
- `.github/workflows/wa01-disposable-mysql-test.yml`
- Current blob SHA: `eaa889065234bab93ffa5e98eb35b1019373a0be`

The same workflow file was registered on default branch `main` solely so GitHub exposes the manual workflow-dispatch UI. Its job-level branch guard prevents execution on `main` or any other branch. Existing workflows and their triggers were not modified.

Workflow backups:
1. `docs/backup/wa01-runner-workflow-prepatch-2026-11-21/wa01-disposable-mysql-test.yml`
   - Commit: `ef17e6de241e845829d19fb76f98446ce02f151c`
2. `docs/backup/wa01-runner-workflow-prepostflight-2026-11-21/wa01-disposable-mysql-test.yml`
   - Commit: `59c38ee5aa54839010caa8187e70c4c1aa622e89`

No application source or existing test file was modified. No PR or deployment was created.

## PENGUJIAN

Not run yet.

Reason: the available GitHub connector exposes workflow-run/log/artifact read actions but does not expose a workflow-dispatch action. The workflow is therefore prepared and registered, but cannot be started by this execution interface.

Required manual sequence:
1. Open GitHub Actions for this repository.
2. Select `WA-01 Disposable MySQL 8 Test Gate`.
3. Select branch `work/wa01-package0-implementation-20261121` and phase `preflight`.
4. Review the uploaded `database-preflight.json` artifact. It must report MySQL 8.0, InnoDB, expected run-specific database/user, zero tables, schema-scoped grants only, and no production/VPS credentials.
5. Only after preflight is reviewed, run the same workflow again on the same branch with phase `execute`.
6. Retrieve the `wa01-test-evidence-<run_id>-<attempt>` artifact and workflow logs for audit.

Limitations:
- Guru policy and login regressions are included, but the full Guru question-bank/exam lifecycle test is excluded because its class setUp calls the prohibited RolePermissionBootstrapper helper. Do not describe full Guru lifecycle regression as passed.
- WA-01 PASS requires all included tests to pass, lint and platform checks to pass, post-migration table-engine verification to show all tables InnoDB, and complete artifacts/logs to be available.
- Package 1 Dashboard Admin must remain blocked until that evidence exists.
