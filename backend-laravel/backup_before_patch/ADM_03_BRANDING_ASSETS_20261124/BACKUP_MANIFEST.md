# Backup manifest — ADM-03 branding assets

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Parent SHA: 83c3786fc9b27804bfeb3374fcc9e55fc269f627

| Source path | Source blob SHA | Backup path | Backup blob SHA | Result |
|---|---|---|---|---|
| `backend-laravel/app/Models/Tenant.php` | `51b2c615888f814362db115ef556b78089018d96` | `backend-laravel/backup_before_patch/ADM_03_BRANDING_ASSETS_20261124/Tenant.php.before` | `51b2c615888f814362db115ef556b78089018d96` | MATCH |
| `backend-laravel/routes/web.php` | `a21ff76c2066877a8247c94bfe7e0ec913d49874` | `backend-laravel/backup_before_patch/ADM_03_BRANDING_ASSETS_20261124/web.php.before` | `a21ff76c2066877a8247c94bfe7e0ec913d49874` | MATCH |
| `backend-laravel/resources/views/layouts/app.blade.php` | `67730194dcf65df426d54dd4773e267cce998f4f` | `backend-laravel/backup_before_patch/ADM_03_BRANDING_ASSETS_20261124/app.blade.php.before` | `67730194dcf65df426d54dd4773e267cce998f4f` | MATCH |
| `backend-laravel/resources/views/auth/login.blade.php` | `58155a992ce192266e14e363c6f97ac3dda8a910` | `backend-laravel/backup_before_patch/ADM_03_BRANDING_ASSETS_20261124/login.blade.php.before` | `58155a992ce192266e14e363c6f97ac3dda8a910` | MATCH |

New files:
- `backend-laravel/database/migrations/2026_11_24_230001_add_tenant_branding_asset_paths.php`
- `backend-laravel/app/Services/TenantBrandingService.php`
- `backend-laravel/app/Livewire/Admin/TenantBrandingAssetsManagement.php`
- `backend-laravel/resources/views/livewire/admin/tenant-branding-assets-management.blade.php`
- `backend-laravel/tests/Feature/TenantBrandingAssetsManagementTest.php`

Logo/favicon assets are public branding files stored under storage/app/public/tenants/{tenant_id}/branding. Uploads are validated and tenant-scoped. No production data or deployment was changed.