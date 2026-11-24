# Backup manifest — Installer E2E namespace syntax correction

Pre-patch HEAD: `a4f2b360b93a1a34745ae16c7b001b05947659c3`

| Source | Backup | Git blob SHA (equal) | SHA-256 | UTF-8 bytes |
|---|---|---|---|---:|
| `backend-laravel/tests/Feature/InstallerEndToEndTest.php` | `backend-laravel/backups/limited-zip-p0-20261125/InstallerEndToEndTest.php.before_namespace_fix_a4f2b360` | `3701449c536fa4a869c9828cbe251622602f88ee` | `4320bff21e58b75795c0f0a79847a51a9b192e235581e59a355ba80db1efdb6d` | 10131 |

The backup is byte-exact and committed before the namespace syntax correction.
