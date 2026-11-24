# Backup Manifest — P0 Academic Provisioning

- Source baseline SHA: `a65b4604495e8bcc153bf0e09df12a9efb11543a`
- Branch: `work/p0-academic-provisioning-20261125`
- Backup method: exact UTF-8 source content copied before patch; no source file modified in this step.

| Source file | Source Git blob SHA | SHA-256 content | Backup path |
|---|---|---|---|
| `backend-laravel/app/Services/InstallerService.php` | `a75350d7b0ca2be59d5cc8ffb74810ac4b232a00` | `e1603710e14c5094cefe6c0de15203cbaa2ad2e3144e845fbc134b25670eb0e7` | `backend-laravel/backups/p0-academic-provisioning-20261125/InstallerService.php.before` |
| `backend-laravel/app/Livewire/Admin/AcademicManagement.php` | `63652d47345962c1cd49e08420956453765e9373` | `0808efb629405aa52f71754f87f6154bc21e51002dbb1b18e3fc70fa0fcbecad` | `backend-laravel/backups/p0-academic-provisioning-20261125/AcademicManagement.php.before` |
| `backend-laravel/resources/views/livewire/admin/academic-management.blade.php` | `dd00edc31b554e4200dca1ced858b81d474a629c` | `af866459997774f41173b9081a1c0bd143a2808a32894f7f6e0f78b804f3f1a5` | `backend-laravel/backups/p0-academic-provisioning-20261125/academic-management.blade.php.before` |
| `backend-laravel/tests/Feature/InstallerEndToEndTest.php` | `84b6becad2a700c799b51a9a8f2e6e9ddf86b777` | `67dad53e5ddba31fa0acd76885fb3862cf6cc67deac412c0e5ef8c69dad2a7da` | `backend-laravel/backups/p0-academic-provisioning-20261125/InstallerEndToEndTest.php.before` |
| `.github/workflows/tenant-backend-ci.yml` | `d9678da8fd52210b970d50300f65f7e5dc437636` | `28cc7de24f8c92201dc2fe7772327ba3a19be84ec681964ec7c14a390ca7a822` | `.github/workflows/backup/p0-academic-provisioning-20261125/tenant-backend-ci.yml.before` |
| `backend-laravel/database/seeders/CurriculumReferenceSourceSeeder.php` | `e2e75d34f2fbca683841190ff36ff143aec82aa5` | `52623adc0c7377abe6e833f0f7457438381bfa95beb80fb08b5ea7e923a30291` | `backend-laravel/backups/p0-academic-provisioning-20261125/CurriculumReferenceSourceSeeder.php.before` |
| `backend-laravel/database/seeders/CurriculumRegistryBootstrapSeeder.php` | `e7cd1e94144b6d7bbad7e54f42a1f87a80af531d` | `5011fc437eebbbf707f1864dd6eecf21b5fdf43dff9061de5861010f5f98aea0` | `backend-laravel/backups/p0-academic-provisioning-20261125/CurriculumRegistryBootstrapSeeder.php.before` |
| `backend-laravel/database/seeders/AssessmentTaxonomySeeder.php` | `e61f199f85859f160db0f80d5d0b765df373f232` | `bb4d5705dacbe10f1f8164eb74aaa5d67e94dc0b6d2da211e2b694e285d34cea` | `backend-laravel/backups/p0-academic-provisioning-20261125/AssessmentTaxonomySeeder.php.before` |
| `backend-laravel/app/Services/Licensing/InstallationIdentityService.php` | `66115b340189581b34bcf027f73c1049281560e5` | `86d72d1e5af210855000ede57b8f046d39d4c0f06632e75ba04721e2fc1ab449` | `backend-laravel/backups/p0-academic-provisioning-20261125/InstallationIdentityService.php.before` |

Backups are retained in the controlled repository and must be excluded from distribution ZIPs.