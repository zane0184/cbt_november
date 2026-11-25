# Backup manifest — benchmark policy-denial repair

Backup dibuat sebelum patch pada branch `work/benchmark-cleanup-proof-20261125`.

Source HEAD sebelum backup: `38ab6a03b82556e63781a83f57013408fd8a8054`

| Source file | Backup file | Source Git blob SHA | Backup Git blob SHA | SHA-256 source/backup |
|---|---|---|---|---|
| backend-laravel/app/Console/Commands/Phase7dBenchmarkCommand.php | backend-laravel/backups/benchmark-cleanup-proof-20261125/Phase7dBenchmarkCommand.php.before_38ab6a0 | 0cbb25215108350ea4b9874bbbdbe4e0d874df16 | 0cbb25215108350ea4b9874bbbdbe4e0d874df16 | 1b22e1a468bdf7f5f745eceeaf8fb9b9a2fc0d0f3f78f994e2d4d13828560375 |
| backend-laravel/tests/Feature/Phase7dBenchmarkCommandSafetyTest.php | backend-laravel/backups/benchmark-cleanup-proof-20261125/Phase7dBenchmarkCommandSafetyTest.php.before_38ab6a0 | f870b226f1e114183ca81a2d2111570bb29953b6 | f870b226f1e114183ca81a2d2111570bb29953b6 | 90078585e92b30550284404d28164e8482eb170c743bbeda5f9d8ce9e73abd2b |

## Verification
- Both backup files were re-fetched from GitHub after creation.
- Backup Git blob SHA equals the source Git blob SHA for both files.
- SHA-256 was calculated from the UTF-8 source content fetched at the exact pre-patch HEAD.
- No source file had been changed when these backups were created.
