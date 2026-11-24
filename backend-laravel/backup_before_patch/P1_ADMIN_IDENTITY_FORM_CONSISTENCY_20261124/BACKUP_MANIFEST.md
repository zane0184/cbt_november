# Backup manifest — Admin identity form consistency

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Parent SHA: e8e97eea76d9edec6856cd235bc6825de72a4a63
- Method: byte-exact source copies created before source updates in the same commit.

| Source path | Source blob SHA | Backup path | Backup blob SHA | Result |
|---|---|---|---|---|
| `backend-laravel/app/Livewire/Admin/UserManagement.php` | `93561c18411f4829db245799642530d6e8e56066` | `backend-laravel/backup_before_patch/P1_ADMIN_IDENTITY_FORM_CONSISTENCY_20261124/UserManagement.php.before` | `93561c18411f4829db245799642530d6e8e56066` | MATCH |
| `backend-laravel/resources/views/livewire/admin/user-management.blade.php` | `0074d258c38adfe68cfbd5e3790b5cd353e06618` | `backend-laravel/backup_before_patch/P1_ADMIN_IDENTITY_FORM_CONSISTENCY_20261124/user-management.blade.php.before` | `0074d258c38adfe68cfbd5e3790b5cd353e06618` | MATCH |
| `backend-laravel/tests/Feature/AdminUiVerificationTest.php` | `2b13f656f9b77a73727d691f49df1b9e84a44bc6` | `backend-laravel/backup_before_patch/P1_ADMIN_IDENTITY_FORM_CONSISTENCY_20261124/AdminUiVerificationTest.php.before` | `2b13f656f9b77a73727d691f49df1b9e84a44bc6` | MATCH |

Changes only clarify the PNS/PPPK vs Honorer identity fields in the Admin form and clear stale opposite-category values. No production database or migration is changed.