# Backup byte-exact — Admin username preflight

- Repository: `zane0184/cbt_november`
- Branch: `work/p1-seven-targets-20261124`
- Date: 24 November 2026
- Verification: backup content fetched back and compared byte-for-byte.

- Source: `backend-laravel/app/Console/Commands/LoginIdentityPreflightCommand.php`
  - Source blob SHA: `6aaad722cd70656438fc852c2fe45e5ae09f0185`
  - Backup: `backend-laravel/backup_before_patch/P1_IDENTITY_PREFLIGHT_ADMIN_USERNAME_20261124/LoginIdentityPreflightCommand.php.before`
  - Backup blob SHA: `6aaad722cd70656438fc852c2fe45e5ae09f0185`
  - Backup commit: `407ed0a67c2cc03a27d7556d6f08da89218ac59d`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/tests/Feature/LoginIdentityPreflightCommandTest.php`
  - Source blob SHA: `791f8277bf553df33eb919d8d8e8641e3c6c3e6c`
  - Backup: `backend-laravel/backup_before_patch/P1_IDENTITY_PREFLIGHT_ADMIN_USERNAME_20261124/LoginIdentityPreflightCommandTest.php.before`
  - Backup blob SHA: `791f8277bf553df33eb919d8d8e8641e3c6c3e6c`
  - Backup commit: `2bf7bf8d9194c79c8937bde775464d8a305be2fe`
  - Byte-exact: **MATCH**
