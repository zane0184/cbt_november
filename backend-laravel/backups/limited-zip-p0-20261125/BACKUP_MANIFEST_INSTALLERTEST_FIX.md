# Backup manifest — InstallerTest System Tenant fixture correction

Repository: `zane0184/cbt_november`
Branch: `work/limited-zip-p0-20261125`
Pre-patch source HEAD: `c3b3b99dd02996f0412455f3bbfe8d8b1e8cfe35`
Backup commit: `0b5dae495f74c46d141b515e32d530c73142abad`

| Source file | Backup file | Original/backup Git blob SHA | SHA-256 | Bytes |
|---|---|---|---|---:|
| `backend-laravel/tests/Feature/InstallerTest.php` | `backend-laravel/backups/limited-zip-p0-20261125/InstallerTest.php.before_system_tenant_fixture_fix_20261125` | `2a1a9c5392b896b3930094430cfbf164a516cd8e` | `831be3a17c258233108cb43bc5777fe1c912ef1207dd8104d4d1af0e817ee04f` | 20724 |

Verification: source and backup Git blob SHA are identical after backup commit, proving byte-for-byte preservation. SHA-256 was computed from the fetched pre-patch source bytes. No existing backup was overwritten.

Reason: preserve the InstallerTest fixture before adding explicit System Tenant reference seeding to the three failing installation/recovery scenarios.
