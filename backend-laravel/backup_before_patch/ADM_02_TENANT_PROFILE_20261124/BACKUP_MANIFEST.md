# Backup manifest — ADM-02 Tenant Profile

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Parent SHA: c0bd1a1e98c995834a509f051e17ddd0daf3ae9e

| Source path | Source blob SHA | Backup path | Backup blob SHA | Result |
|---|---|---|---|---|
| `backend-laravel/app/Models/Tenant.php` | `08c43026e61c242d9d0bc7239769f1a0ce38e13c` | `backend-laravel/backup_before_patch/ADM_02_TENANT_PROFILE_20261124/Tenant.php.before` | `08c43026e61c242d9d0bc7239769f1a0ce38e13c` | MATCH |
| `backend-laravel/routes/web.php` | `fa3e3a2f26edb145317531ef75f3712f4d79b396` | `backend-laravel/backup_before_patch/ADM_02_TENANT_PROFILE_20261124/web.php.before` | `fa3e3a2f26edb145317531ef75f3712f4d79b396` | MATCH |
| `backend-laravel/resources/views/layouts/app.blade.php` | `4830d03985f00fdf6fea5297c8950d211191cc63` | `backend-laravel/backup_before_patch/ADM_02_TENANT_PROFILE_20261124/app.blade.php.before` | `4830d03985f00fdf6fea5297c8950d211191cc63` | MATCH |

New files:
- `backend-laravel/database/migrations/2026_11_24_220001_extend_tenant_profile_fields.php`
- `backend-laravel/app/Livewire/Admin/TenantProfileManagement.php`
- `backend-laravel/resources/views/livewire/admin/tenant-profile-management.blade.php`
- `backend-laravel/tests/Feature/TenantProfileManagementTest.php`

No production database or deployment was changed.