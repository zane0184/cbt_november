# Backup byte-exact — P1 audit redaction and account creation

- Repository: `zane0184/cbt_november`
- Branch: `work/p1-seven-targets-20261124`
- Date: 24 November 2026
- Purpose: preserve each existing source/test file before the next patch.
- Verification: each backup was fetched back from GitHub and its content compared byte-for-byte with the source fetched immediately before backup.

- Source: `backend-laravel/app/Services/AuditService.php`
  - Source blob SHA: `e968cc401cdd8b04ab1322171d5f475332a55372`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_REDACTION_AND_ACCOUNT_CREATE_20261124/AuditService.php.before`
  - Backup blob SHA: `e968cc401cdd8b04ab1322171d5f475332a55372`
  - Backup commit: `de2e5cdef28ce7118ef810309d354caf2422febf`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/app/Livewire/Admin/UserManagement.php`
  - Source blob SHA: `fb18b91c5f534e774ccf5462e195875d7cb4e1b4`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_REDACTION_AND_ACCOUNT_CREATE_20261124/UserManagement.php.before`
  - Backup blob SHA: `fb18b91c5f534e774ccf5462e195875d7cb4e1b4`
  - Backup commit: `aa041e859dab636bd3fa952245b68d2ccb42ee20`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/tests/Feature/AdminUiVerificationTest.php`
  - Source blob SHA: `cacf9ca3998707cd012ab6a8e31dd278255d4eed`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_REDACTION_AND_ACCOUNT_CREATE_20261124/AdminUiVerificationTest.php.before`
  - Backup blob SHA: `cacf9ca3998707cd012ab6a8e31dd278255d4eed`
  - Backup commit: `ec0709580df99313a66cb70eac06b01df0448b1e`
  - Byte-exact: **MATCH**
