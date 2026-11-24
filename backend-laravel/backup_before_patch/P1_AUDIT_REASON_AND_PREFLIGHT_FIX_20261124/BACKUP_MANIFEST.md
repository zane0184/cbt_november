# Backup byte-exact — audit reason and preflight test correction

- Repository: `zane0184/cbt_november`
- Branch: `work/p1-seven-targets-20261124`
- Date: 24 November 2026
- Verification: fetched-back backup content compared byte-for-byte with source.

- Source: `backend-laravel/app/Services/AuditPayloadSanitizer.php`
  - Source blob SHA: `58d866b76a022da72919c04063dc9868bed073c8`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_REASON_AND_PREFLIGHT_FIX_20261124/AuditPayloadSanitizer.php.before`
  - Backup blob SHA: `58d866b76a022da72919c04063dc9868bed073c8`
  - Backup commit: `8ed6f7a284eb1332afad4b786f679517d7cc6120`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/app/Console/Commands/ExpireExamAttemptsCommand.php`
  - Source blob SHA: `4629bae32531badf6d861d2f4d4376a1396cdff1`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_REASON_AND_PREFLIGHT_FIX_20261124/ExpireExamAttemptsCommand.php.before`
  - Backup blob SHA: `4629bae32531badf6d861d2f4d4376a1396cdff1`
  - Backup commit: `3d5a607c424d4bdc133e2fb8fb941f96a026360b`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/tests/Feature/AuditPayloadSanitizerTest.php`
  - Source blob SHA: `feecdf7cbabd938932daeb27f88a0a70fa5fa852`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_REASON_AND_PREFLIGHT_FIX_20261124/AuditPayloadSanitizerTest.php.before`
  - Backup blob SHA: `feecdf7cbabd938932daeb27f88a0a70fa5fa852`
  - Backup commit: `41e78336b0cdbe18d9fe670b9f059ec5e655fd5f`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/tests/Feature/LoginIdentityPreflightCommandTest.php`
  - Source blob SHA: `943d7ad359dad33894fa6863f9b45dfc93d3bc52`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_REASON_AND_PREFLIGHT_FIX_20261124/LoginIdentityPreflightCommandTest.php.before`
  - Backup blob SHA: `943d7ad359dad33894fa6863f9b45dfc93d3bc52`
  - Backup commit: `b8c1208d121d4932baca338577576e1b39c47e2f`
  - Byte-exact: **MATCH**
