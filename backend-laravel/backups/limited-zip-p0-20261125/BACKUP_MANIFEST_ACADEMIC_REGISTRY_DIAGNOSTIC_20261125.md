# Backup Manifest — P0-01 Academic Registry Fixture and Diagnostics

- Branch: work/limited-zip-p0-20261125
- Pre-patch HEAD: a68dd45079216ad9fef91622f58bf0104eda7729
- Method: source content fetched from exact pre-patch commit and stored as backup blobs; matching Git blob SHA verifies byte-exact content.

## 1. backend-laravel/app/Services/InstallerService.php
- Backup: backend-laravel/backups/limited-zip-p0-20261125/InstallerService.php.before_academic_registry_diagnostic_fix_20261125
- Original Git blob SHA: 0303b141c7fab02d07b04335d14ee3057b3ebb52
- Backup Git blob SHA: 0303b141c7fab02d07b04335d14ee3057b3ebb52
- Size: 53454 bytes
- SHA-256: 07f31f32855e93095d7b95fda651cebc0a87a337d3a59e4eef5ffddda7339fe2
- Byte-exact verification: PASS

## 2. backend-laravel/tests/Feature/InstallerTest.php
- Backup: backend-laravel/backups/limited-zip-p0-20261125/InstallerTest.php.before_academic_registry_fixture_fix_20261125
- Original Git blob SHA: c7efcc5451ef546d7a14e946bb6c206fb1891b8f
- Backup Git blob SHA: c7efcc5451ef546d7a14e946bb6c206fb1891b8f
- Size: 20873 bytes
- SHA-256: c8e79a7950680ea23a46910b99e3d2f08af6f40c213eea97c3a42dc4e4d834f2
- Byte-exact verification: PASS
