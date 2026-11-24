# Backup byte-exact — P1 audit sanitizer and benchmark fixtures

- Repository: `zane0184/cbt_november`
- Branch: `work/p1-seven-targets-20261124`
- Date: 24 November 2026
- Purpose: preserve source files before shared audit sanitization, runtime audit-writer, and benchmark fixture changes.
- Verification: each backup was fetched back and compared byte-for-byte with its source.

- Source: `backend-laravel/app/Services/AuditService.php`
  - Source blob SHA: `fc27fea287f1acf4845f9a1c2db92747170694d2`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_SANITIZER_BENCHMARK_20261124/AuditService.php.before`
  - Backup blob SHA: `fc27fea287f1acf4845f9a1c2db92747170694d2`
  - Backup commit: `3c97f1ce4a52641e293e2a847e37b00aff0dcbab`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/app/Services/InstallerService.php`
  - Source blob SHA: `a58634223eaefda51afceea677a48b978dcb22f3`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_SANITIZER_BENCHMARK_20261124/InstallerService.php.before`
  - Backup blob SHA: `a58634223eaefda51afceea677a48b978dcb22f3`
  - Backup commit: `ce22b038592c56c1bb4c20193248e47ed28205f2`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/app/Console/Commands/ExpireExamAttemptsCommand.php`
  - Source blob SHA: `ccc3e0b80ff73e01a2fbe32dff600a7b4a89da48`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_SANITIZER_BENCHMARK_20261124/ExpireExamAttemptsCommand.php.before`
  - Backup blob SHA: `ccc3e0b80ff73e01a2fbe32dff600a7b4a89da48`
  - Backup commit: `4f85670669a625397e251a9dd3efcd6b2a3ce674`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/app/Console/Commands/Phase7dBenchmarkCommand.php`
  - Source blob SHA: `335e126e9fef534094eb737c69165e60cbac8e6a`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_SANITIZER_BENCHMARK_20261124/Phase7dBenchmarkCommand.php.before`
  - Backup blob SHA: `335e126e9fef534094eb737c69165e60cbac8e6a`
  - Backup commit: `b1e466878f2573a8e5a501f9f99d8d102694d4c6`
  - Byte-exact: **MATCH**

- Source: `backend-laravel/tests/Feature/AdminUiVerificationTest.php`
  - Source blob SHA: `214a417aeebc86c69d6367e118f40af57fe2b099`
  - Backup: `backend-laravel/backup_before_patch/P1_AUDIT_SANITIZER_BENCHMARK_20261124/AdminUiVerificationTest.php.before`
  - Backup blob SHA: `214a417aeebc86c69d6367e118f40af57fe2b099`
  - Backup commit: `f8ada5ba8dc9745d7fb9f720e2f80eedf04c6029`
  - Byte-exact: **MATCH**
