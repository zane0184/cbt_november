# Backup manifest — P1 identity negative test matrix

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Parent SHA: 10b5286bd2bc45b5cf02611d1a4549b20fe88a0a
- Method: byte-exact source copies created before source updates in the same commit.

| Source path | Source blob SHA | Backup path | Backup blob SHA | Result |
|---|---|---|---|---|
| `backend-laravel/tests/Feature/LoginIdentityDatabaseConstraintTest.php` | `45dd39eb141eed1858e2728847f5294fa58fb0ff` | `backend-laravel/backup_before_patch/P1_IDENTITY_NEGATIVE_CASE_MATRIX_20261124/LoginIdentityDatabaseConstraintTest.php.before` | `45dd39eb141eed1858e2728847f5294fa58fb0ff` | MATCH |
| `backend-laravel/tests/Feature/LoginIdentitySQLiteTriggerTest.php` | `1eb6606a1b021f0b39039bc05c9455ec534c9a51` | `backend-laravel/backup_before_patch/P1_IDENTITY_NEGATIVE_CASE_MATRIX_20261124/LoginIdentitySQLiteTriggerTest.php.before` | `1eb6606a1b021f0b39039bc05c9455ec534c9a51` | MATCH |

Only test cases are expanded. No runtime source, migration, or database is changed.