# W2 Progress — Dashboard Scope + ADM-06 Optional Teacher Identity

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/solo-web-cbt-completion-20261123-execution`

## AUDIT
Dari source yang dibaca:
- Dashboard sebelumnya menghitung seluruh Rombel tenant lintas tahun dan status, seluruh Subject tanpa status aktif, dan menampilkan badge Rombel "Aktif" secara hard-coded serta property code yang tidak ada pada model/migration Rombel.
- Admin UserManagement sebelumnya menolak akun Guru bila NIP dan NIK kosong, walaupun keduanya bukan syarat universal identitas/login. Spesifikasi Admin menghendaki NIP/NUPTK opsional; identitas kedinasan tidak boleh menjadi syarat pembuatan akun Guru ketika username tersedia.
- Dashboard/sidebar existing sudah ada; tidak diremake.
- ADM-20 tetap diblokir oleh belum adanya marker canonical central exam eligibility yang disetujui owner. Tidak ada perubahan terkait Proktor.

## DESAIN
1. Dashboard:
   - ambil satu academic year aktif sebelum menghitung metrik;
   - hitung rombel hanya status ACTIVE pada tahun ajaran aktif; tanpa tahun aktif, count 0;
   - hitung subject status ACTIVE;
   - daftar recent Rombel dibatasi tahun aktif, menampilkan tahun pelajaran dan status sebenarnya.
2. Guru identity:
   - NIP, NIK, dan NUPTK tidak wajib untuk membuat Guru;
   - username tenant-scoped menjadi fallback identitas login;
   - bila NIP dan NIK kosong, employee_identifier disimpan NULL, bukan string kosong;
   - validasi format dan keunikan NIP/NIK tetap dijaga bila nilainya diisi.

## IMPLEMENTASI
Files updated:
- `backend-laravel/app/Livewire/Admin/Dashboard.php`
- `backend-laravel/resources/views/livewire/admin/dashboard.blade.php`
- `backend-laravel/app/Livewire/Admin/UserManagement.php`
- `backend-laravel/resources/views/livewire/admin/user-management.blade.php`
- `backend-laravel/tests/Feature/AdminUiVerificationTest.php`

W2 dashboard regression tests:
- `admin_dashboard_counts_only_active_rombel_in_current_year_and_active_subjects`
- `admin_dashboard_shows_empty_rombel_state_when_no_academic_year_is_active`

ADM-06 regression:
- `admin_can_create_teacher_without_optional_nip_or_nik_using_username_login`

Byte-exact backups (each fetched back and compared with its source blob before edits):
- `backend-laravel/docs/backup/admin-dashboard-year-scope-2026-11-24/Dashboard.php.before` — blob `0198e7ad2fed9bc18669972805dba1bcd6d89c7a` (2758 bytes).
- `backend-laravel/docs/backup/admin-dashboard-year-scope-2026-11-24/dashboard.blade.php.before` — blob `701b943088e4d112110e3e6fbb2661d7ddbb0bc5` (8644 bytes).
- `backend-laravel/docs/backup/admin-dashboard-year-scope-2026-11-24/AdminUiVerificationTest.php.before` — blob `8dc6580e6be22f4d10907ef957db5a399ca7f644` (16961 bytes).
- `backend-laravel/docs/backup/admin-guru-identity-optional-2026-11-24/UserManagement.php.before` — blob `4e6d2e39d42a8b014e801dcdbac0d7d4e995d8db` (6197 bytes).
- `backend-laravel/docs/backup/admin-guru-identity-optional-2026-11-24/user-management.blade.php.before` — blob `bc2c153e934091923728a78af9dff62ccbb75bcc` (6404 bytes).
- `backend-laravel/docs/backup/admin-guru-identity-optional-2026-11-24/AdminUiVerificationTest.php.before` — blob `df9b50407a1169e4a4ab2cda73d85e23fa25ccbc` (19795 bytes).

Current source blobs:
- `backend-laravel/app/Livewire/Admin/Dashboard.php` — blob `e3cfaed3d70c336362d254641f453f6a03f1aa8d` (3572 bytes).
- `backend-laravel/resources/views/livewire/admin/dashboard.blade.php` — blob `017e95463161bb93628a205ef8acc298c20ce93d` (9337 bytes).
- `backend-laravel/tests/Feature/AdminUiVerificationTest.php` — blob `54278709eb7153aab494d85f962c1b7026f5dd91` (20873 bytes).
- `backend-laravel/app/Livewire/Admin/UserManagement.php` — blob `b0868c101f5b98581263d6cfd3cec687eb9219b8` (6055 bytes).
- `backend-laravel/resources/views/livewire/admin/user-management.blade.php` — blob `e8e26cb482f3cb33e677f486a79092c0c807daf6` (6495 bytes).

No application DB schema, migration, production DB, VPS or ZIP was changed.

## PENGUJIAN
- Last verified stable CI remains #1668 on exact SHA `b726dcb55d5bcba60adb6bc34529df14ffb6dad9`: SUCCESS, 530 passed / 2,199 assertions / 0 failed, PHP 8.3 + MySQL 8/InnoDB disposable.
- W2-only run #1679 on the documentation HEAD was cancelled before finishing focused steps when subsequent implementation commits were pushed. Its cancellation is not evidence of test failure and not a PASS.
- Dashboard + ADM-06 tests on the current source have not yet received a completed Actions verdict. A consolidated run is required before calling W2/ADM-06 verified.

## STATUS
- W1 Web Login installation-context negative tests: DONE / VERIFIED (#1668).
- W2 Dashboard active-year/status scope: IMPLEMENTED / TEST PENDING.
- ADM-06 optional Teacher NIP/NIK: IMPLEMENTED / TEST PENDING.
- ADM-01 remains partial pending broader acceptance, responsiveness/accessibility/browser checks.
- ADM-20 remains BLOCKED as owner has not approved a canonical central-exam marker.

## NEXT
Run the current HEAD CI. After that, continue W2 admin details (school identity/settings and import/export) based on source/spec gaps, not by repeating existing components.
