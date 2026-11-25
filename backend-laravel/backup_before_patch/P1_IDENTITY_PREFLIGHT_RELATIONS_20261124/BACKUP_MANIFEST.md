# Backup byte-exact — identity preflight relational checks

- Repository: `zane0184/cbt_november`
- Branch: `work/p1-seven-targets-20261124`
- Date: 24 November 2026
- Verification: backup content fetched back and compared byte-for-byte with source.

- Source: `backend-laravel/app/Console/Commands/LoginIdentityPreflightCommand.php`
  - Source blob SHA: `4fa441f98cd58b8be011537a54e20f24fa64648b`
  - Backup: `backend-laravel/backup_before_patch/P1_IDENTITY_PREFLIGHT_RELATIONS_20261124/LoginIdentityPreflightCommand.php.before`
  - Backup blob SHA: `4fa441f98cd58b8be011537a54e20f24fa64648b`
  - Backup commit: `99cd477e7b5da24084f2668b76d7f61650cd4fcf`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/tests/Feature/LoginIdentityPreflightCommandTest.php`
  - Source blob SHA: `0ef9ec3b79e74ebc7fa50874376d0386460fc604`
  - Backup: `backend-laravel/backup_before_patch/P1_IDENTITY_PREFLIGHT_RELATIONS_20261124/LoginIdentityPreflightCommandTest.php.before`
  - Backup blob SHA: `0ef9ec3b79e74ebc7fa50874376d0386460fc604`
  - Backup commit: `ca8aefa0459a989df7e6b6f225f8e0dd4034c12d`
  - Byte-exact: **MATCH**
