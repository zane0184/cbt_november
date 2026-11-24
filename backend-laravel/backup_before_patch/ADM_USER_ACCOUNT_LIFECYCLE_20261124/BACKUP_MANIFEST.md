# Backup manifest — Admin account lifecycle

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Parent SHA: 0384027fc0bba8aefbe230b9a59f5b8c18cd62d8
- Method: byte-exact copies of existing source files before changes.

| Source path | Source blob SHA | Backup path | Backup blob SHA | Result |
|---|---|---|---|---|
| `backend-laravel/app/Models/User.php` | `dec82ba26e785708945bdea1bc0e34a1ed20458d` | `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/User.php.before` | `dec82ba26e785708945bdea1bc0e34a1ed20458d` | MATCH |
| `backend-laravel/app/Services/LoginIdentityResolver.php` | `7208951e46a39f20afb897d4c39e1adb0429190f` | `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/LoginIdentityResolver.php.before` | `7208951e46a39f20afb897d4c39e1adb0429190f` | MATCH |
| `backend-laravel/app/Http/Controllers/AuthWebController.php` | `9d6109a906be5659615d8e978daf8995112beca0` | `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/AuthWebController.php.before` | `9d6109a906be5659615d8e978daf8995112beca0` | MATCH |
| `backend-laravel/app/Http/Controllers/Api/V1/AuthController.php` | `7680c93bc3f88c43ab47cb1a4c24a603947f79de` | `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/AuthController.php.before` | `7680c93bc3f88c43ab47cb1a4c24a603947f79de` | MATCH |
| `backend-laravel/bootstrap/app.php` | `207accdc3233d9d8552447019cb166fdc2e65ba5` | `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/app.php.before` | `207accdc3233d9d8552447019cb166fdc2e65ba5` | MATCH |
| `backend-laravel/routes/web.php` | `f6df667627c7b134ce7ed6f90f916a9bab2730a9` | `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/web.php.before` | `f6df667627c7b134ce7ed6f90f916a9bab2730a9` | MATCH |
| `backend-laravel/routes/api.php` | `b35d35704483217061730aeff2281d9033aeb621` | `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/api.php.before` | `b35d35704483217061730aeff2281d9033aeb621` | MATCH |
| `backend-laravel/routes/teacher.php` | `a1679492a6fded0c0c58a4ac790f8654e143b6e0` | `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/teacher.php.before` | `a1679492a6fded0c0c58a4ac790f8654e143b6e0` | MATCH |
| `backend-laravel/app/Livewire/Admin/UserManagement.php` | `e8e17149549803c20116f09dc7853dee2c1d1027` | `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/UserManagement.php.before` | `e8e17149549803c20116f09dc7853dee2c1d1027` | MATCH |
| `backend-laravel/resources/views/livewire/admin/user-management.blade.php` | `80e7c476ccc2be81e16ba668e213376cf4e0e830` | `backend-laravel/backup_before_patch/ADM_USER_ACCOUNT_LIFECYCLE_20261124/user-management.blade.php.before` | `80e7c476ccc2be81e16ba668e213376cf4e0e830` | MATCH |

New files:
- `backend-laravel/database/migrations/2026_11_24_210001_add_user_account_state.php`
- `backend-laravel/app/Http/Middleware/EnsureActiveUser.php`
- `backend-laravel/app/Services/UserAccountService.php`
- `backend-laravel/tests/Feature/UserAccountLifecycleTest.php`

No production database or deployment is changed by this source commit.