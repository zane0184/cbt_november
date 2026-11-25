# Backup byte-exact — disposable MySQL benchmark conversion

- Repository: `zane0184/cbt_november`
- Branch: `work/p1-seven-targets-20261124`
- Date: 24 November 2026
- Verification: fetched-back backup content compared byte-for-byte.

- Source: `backend-laravel/app/Console/Commands/Phase7dBenchmarkCommand.php`
  - Source blob SHA: `aa3149257e40c27ba6f041e1ad86e1c965be7e1e`
  - Backup: `backend-laravel/backup_before_patch/P1_BENCHMARK_MYSQL_DISPOSABLE_20261124/Phase7dBenchmarkCommand.php.before`
  - Backup blob SHA: `aa3149257e40c27ba6f041e1ad86e1c965be7e1e`
  - Backup commit: `92714552b07302ab09fcbc2427941b7b824d5279`
  - Byte-exact: **MATCH**

- Source: `.github/workflows/tenant-backend-ci.yml`
  - Source blob SHA: `566f91baf8e5217331d32552b76f67bc86a0f300`
  - Backup: `backend-laravel/backup_before_patch/P1_BENCHMARK_MYSQL_DISPOSABLE_20261124/tenant-backend-ci.yml.before`
  - Backup blob SHA: `566f91baf8e5217331d32552b76f67bc86a0f300`
  - Backup commit: `e4f1f10db4f4536cd8d2548965f9d2b1974cbbff`
  - Byte-exact: **MATCH**
