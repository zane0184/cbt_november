# Backup byte-exact — P1 sanitizer edge case

- Repository: `zane0184/cbt_november`
- Branch: `work/p1-seven-targets-20261124`
- Date: 24 November 2026
- Purpose: preserve the sanitizer and benchmark cleanup/test sources before a follow-up correctness fix.
- Verification: fetched-back backup content compared byte-for-byte with source.

- Source: `backend-laravel/app/Services/AuditPayloadSanitizer.php`
  - Source blob SHA: `43c286b1e40a4507bd13d3d67fd7c25c7eeaf8dd`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_SANITIZER_EDGECASE_20261124/AuditPayloadSanitizer.php.before`
  - Backup blob SHA: `43c286b1e40a4507bd13d3d67fd7c25c7eeaf8dd`
  - Backup commit: `4912b879d926976da495c9f1a493dc700f84477d`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/app/Console/Commands/Phase7dBenchmarkCommand.php`
  - Source blob SHA: `cc5c61d0d4690e7bc03b28bfd33a67867eb7d974`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_SANITIZER_EDGECASE_20261124/Phase7dBenchmarkCommand.php.before`
  - Backup blob SHA: `cc5c61d0d4690e7bc03b28bfd33a67867eb7d974`
  - Backup commit: `d8987e1a8dfbead6405f33a6c97c9431b73462ee`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/tests/Feature/AuditPayloadSanitizerTest.php`
  - Source blob SHA: `396e1209d860632540c636062f38922325b910f2`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_SANITIZER_EDGECASE_20261124/AuditPayloadSanitizerTest.php.before`
  - Backup blob SHA: `396e1209d860632540c636062f38922325b910f2`
  - Backup commit: `363c2195d1d8d8d336173dc9f2481ef49e254d13`
  - Byte-exact: **MATCH**
