# Checkpoint W2 Progress — Admin Dashboard + ADM-06

Tanggal: 24 November 2026  
Branch: `work/solo-web-cbt-completion-20261123-execution`

Current source blobs:
- backend-laravel/app/Livewire/Admin/Dashboard.php: e3cfaed3d70c336362d254641f453f6a03f1aa8d
- backend-laravel/resources/views/livewire/admin/dashboard.blade.php: 017e95463161bb93628a205ef8acc298c20ce93d
- backend-laravel/tests/Feature/AdminUiVerificationTest.php: 54278709eb7153aab494d85f962c1b7026f5dd91
- backend-laravel/app/Livewire/Admin/UserManagement.php: b0868c101f5b98581263d6cfd3cec687eb9219b8
- backend-laravel/resources/views/livewire/admin/user-management.blade.php: e8e26cb482f3cb33e677f486a79092c0c807daf6

Backups:
- backend-laravel/docs/backup/admin-dashboard-year-scope-2026-11-24/Dashboard.php.before: 0198e7ad2fed9bc18669972805dba1bcd6d89c7a
- backend-laravel/docs/backup/admin-dashboard-year-scope-2026-11-24/dashboard.blade.php.before: 701b943088e4d112110e3e6fbb2661d7ddbb0bc5
- backend-laravel/docs/backup/admin-dashboard-year-scope-2026-11-24/AdminUiVerificationTest.php.before: 8dc6580e6be22f4d10907ef957db5a399ca7f644
- backend-laravel/docs/backup/admin-guru-identity-optional-2026-11-24/UserManagement.php.before: 4e6d2e39d42a8b014e801dcdbac0d7d4e995d8db
- backend-laravel/docs/backup/admin-guru-identity-optional-2026-11-24/user-management.blade.php.before: bc2c153e934091923728a78af9dff62ccbb75bcc
- backend-laravel/docs/backup/admin-guru-identity-optional-2026-11-24/AdminUiVerificationTest.php.before: df9b50407a1169e4a4ab2cda73d85e23fa25ccbc

Changes:
- Dashboard Rombel metrics scoped to active academic year + ACTIVE; Subject count ACTIVE; current-year Rombel list shows true status and academic year.
- Guru creation no longer requires NIP/NIK; username can be used; empty employee_identifier persists as NULL.
- Tests cover dashboard prior-year/inactive/no-active-year and Guru account without NIP/NIK.

Test status:
- CI #1668 is green for preceding W1 source SHA.
- Runs #1676/#1679 were cancelled as newer commits pushed during execution; they did not verify W2.
- Current W2/ADM-06 source test verdict PENDING until exact HEAD Actions run completes.

Next: wait for consolidated HEAD workflow; then continue only if green.
