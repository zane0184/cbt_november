# Backup manifest — P1 identity fixture collation and answer-key test repair

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Parent SHA: 117910d40e17e35a824978a6d8c55daf4c3b7a78
- Method: byte-exact source copies created before source updates in the same commit.

| Source path | Source blob SHA | Backup path | Backup blob SHA | Result |
|---|---|---|---|---|
| `backend-laravel/tests/Feature/LoginIdentityPreflightCommandTest.php` | `a6fcd5de8f8de80c541410bd9814055a7c3214e4` | `backend-laravel/backup_before_patch/P1_FIX_IDENTITY_COLLATION_AND_ANSWER_KEY_FIXTURES_20261124/LoginIdentityPreflightCommandTest.php.before` | `a6fcd5de8f8de80c541410bd9814055a7c3214e4` | MATCH |
| `backend-laravel/tests/Feature/Security/AnswerKeyLeakageTest.php` | `bf77f34ea94053f91ce2370a73fea2dec8169caa` | `backend-laravel/backup_before_patch/P1_FIX_IDENTITY_COLLATION_AND_ANSWER_KEY_FIXTURES_20261124/AnswerKeyLeakageTest.php.before` | `bf77f34ea94053f91ce2370a73fea2dec8169caa` | MATCH |

Only test fixtures and test-only temporary-table collations are changed. No runtime application logic, migration, or production database is changed.