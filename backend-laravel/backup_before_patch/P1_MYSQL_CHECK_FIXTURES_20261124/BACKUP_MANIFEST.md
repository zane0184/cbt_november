# Backup manifest — P1 MySQL identity fixture repair

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Parent SHA: 32dded63334c16bde62bc9f0a9ffd1982e857e54
- Method: byte-exact source copies created before source updates in the same commit.

| Source path | Source blob SHA | Backup path | Backup blob SHA | Result |
|---|---|---|---|---|
| `backend-laravel/tests/Support/CreatesTeacherExamScope.php` | `4ae94e628c078ea3f4758c2491353ef23a764efb` | `backend-laravel/backup_before_patch/P1_MYSQL_CHECK_FIXTURES_20261124/CreatesTeacherExamScope.php.before` | `4ae94e628c078ea3f4758c2491353ef23a764efb` | MATCH |
| `backend-laravel/tests/Feature/LoginIdentityPreflightCommandTest.php` | `cda1fd6b05509bb5a544eb74f6dc9e1543434afd` | `backend-laravel/backup_before_patch/P1_MYSQL_CHECK_FIXTURES_20261124/LoginIdentityPreflightCommandTest.php.before` | `cda1fd6b05509bb5a544eb74f6dc9e1543434afd` | MATCH |
| `backend-laravel/tests/Feature/LoginIdentityDatabaseConstraintTest.php` | `5ae247ceef6109ae485b4a05374393aca146966d` | `backend-laravel/backup_before_patch/P1_MYSQL_CHECK_FIXTURES_20261124/LoginIdentityDatabaseConstraintTest.php.before` | `5ae247ceef6109ae485b4a05374393aca146966d` | MATCH |

All backup blobs are expected to be byte-exact copies of their source blobs. No application runtime code or production database is changed by this test-fixture repair.