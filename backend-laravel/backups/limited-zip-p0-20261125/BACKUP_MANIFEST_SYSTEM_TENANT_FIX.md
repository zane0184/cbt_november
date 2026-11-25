# Backup manifest — System Tenant / Installation Identity correction (2026-11-25)

Branch: `work/limited-zip-p0-20261125`
Pre-patch HEAD: `b86ddc524ae233dad38fbaf0517dd3bac50a4f75`

Backups were committed before source edits. Git blob SHA equality between each source at pre-patch HEAD and its backup proves byte-for-byte preservation.

| Source file | Backup file | Source/backup Git blob SHA | SHA-256 | Bytes |
|---|---|---|---|---:|
| `backend-laravel/app/Services/Licensing/InstallationIdentityService.php` | `backend-laravel/backups/limited-zip-p0-20261125/InstallationIdentityService.php.before_system_tenant_fix_20261125` | `66115b340189581b34bcf027f73c1049281560e5` | `86d72d1e5af210855000ede57b8f046d39d4c0f06632e75ba04721e2fc1ab449` | 3422 |
| `backend-laravel/app/Services/InstallerService.php` | `backend-laravel/backups/limited-zip-p0-20261125/InstallerService.php.before_system_tenant_fix_20261125` | `c1597e75f96b9f88ae6f69baed5d3d7fc7812045` | `77a0ce95c56f3d613173fd56d7e9a6f410d09543b5a1ba2f6c2191cbefab6064` | 51906 |

Verification: fetched backup blobs from GitHub after creation; both backup blob SHAs exactly match the original source blob SHAs. No existing backup was overwritten.
