# Backup manifest — ADM-03 branding hardening

- Repository: zane0184/cbt_november
- Branch: work/p1-seven-targets-20261124
- Baseline source ref: PR #23 HEAD `199ed15df91a8c1ea8bfa5603830109375226117` (before backup commits)

| Source path | Source blob SHA | Backup path |
|---|---|---|
| `backend-laravel/app/Services/TenantBrandingService.php` | `bb1621905f9cb11a77843dea780ba858b6aa676c` | `backend-laravel/backup_before_patch/ADM_03_BRANDING_HARDENING_20261124/TenantBrandingService.php.before` |
| `backend-laravel/app/Services/Reporting/ReportHeaderService.php` | `9cab29ad41aecce79a8729bc98f506f5c83a2452` | `backend-laravel/backup_before_patch/ADM_03_BRANDING_HARDENING_20261124/ReportHeaderService.php.before` |
| `backend-laravel/database/migrations/2026_11_24_230001_add_tenant_branding_asset_paths.php` | `601d85d1163bfd00162d3bcc0a7d9ff8d54eff15` | `backend-laravel/backup_before_patch/ADM_03_BRANDING_HARDENING_20261124/2026_11_24_230001_add_tenant_branding_asset_paths.php.before` |
| `backend-laravel/tests/Feature/TenantBrandingAssetsManagementTest.php` | `f45ec05b6bd73e094801b38e40510d3f36e283e7` | `backend-laravel/backup_before_patch/ADM_03_BRANDING_HARDENING_20261124/TenantBrandingAssetsManagementTest.php.before` |
| `backend-laravel/resources/views/layouts/app.blade.php` | `e1604c6f21c9fa0e57ce7b0f2dd1b59cffe278d3` | `backend-laravel/backup_before_patch/ADM_03_BRANDING_HARDENING_20261124/app.blade.php.before` |
| `backend-laravel/resources/views/auth/login.blade.php` | `b0113f8c3c9efdc27943d65ef3c490746661a122` | `backend-laravel/backup_before_patch/ADM_03_BRANDING_HARDENING_20261124/login.blade.php.before` |

Backups are byte-exact copies created before source edits. No production database or deployment was accessed.