# Backup manifest — forward-only employee_identifier NOT NULL enforcement

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Parent SHA: 644ce25faa2fe7e5736567e25efb658caf31260f
- Method: byte-exact source copies created before updates in the same commit.

| Source path | Source blob SHA | Backup path | Backup blob SHA | Result |
|---|---|---|---|---|
| `backend-laravel/tests/Feature/WebAdminAuthTest.php` | `f4ea661e5dc03105e7e969502021a36855a4aad2` | `backend-laravel/backup_before_patch/P1_EMPLOYEE_IDENTIFIER_NOT_NULL_20261124/WebAdminAuthTest.php.before` | `f4ea661e5dc03105e7e969502021a36855a4aad2` | MATCH |
| `backend-laravel/tests/Feature/LoginIdentityDatabaseConstraintTest.php` | `3cb9f9cbd8a89b03afd1b2e8846c91d9b0ecfc1c` | `backend-laravel/backup_before_patch/P1_EMPLOYEE_IDENTIFIER_NOT_NULL_20261124/LoginIdentityDatabaseConstraintTest.php.before` | `3cb9f9cbd8a89b03afd1b2e8846c91d9b0ecfc1c` | MATCH |
| `backend-laravel/tests/Feature/LoginIdentitySQLiteTriggerTest.php` | `d956dbac87f0145a8f9e933a5e8a249c9c7e16d3` | `backend-laravel/backup_before_patch/P1_EMPLOYEE_IDENTIFIER_NOT_NULL_20261124/LoginIdentitySQLiteTriggerTest.php.before` | `d956dbac87f0145a8f9e933a5e8a249c9c7e16d3` | MATCH |
| `backend-laravel/tests/Feature/LoginIdentityPreflightCommandTest.php` | `e0def141a27be22ace8a068306d28b553b19ef20` | `backend-laravel/backup_before_patch/P1_EMPLOYEE_IDENTIFIER_NOT_NULL_20261124/LoginIdentityPreflightCommandTest.php.before` | `e0def141a27be22ace8a068306d28b553b19ef20` | MATCH |

New migration (new file): `backend-laravel/database/migrations/2026_11_24_190001_enforce_teacher_employee_identifier_required.php`

Historical migration 2026_11_24_180001 is not modified. The new migration performs read-only NULL preflight before DDL. No production database is touched.