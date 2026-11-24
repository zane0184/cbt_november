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
| `backend-laravel/database/seeders/CurriculumRegistryBootstrapSeeder.php` | `e7cd1e94144b6d7bbad7e54f42a1f87a80af531d` | `bf18ad7b3780a36874d43689fa5037085f527194fd8e1627ab9c32830fc48e1c` | `backend-laravel/backups/p0-academic-provisioning-20261125/CurriculumRegistryBootstrapSeeder.php.before` |
| `backend-laravel/database/seeders/AssessmentTaxonomySeeder.php` | `e61f199f85859f160db0f80d5d0b765df373f232` | `bb4d5705dacbe10f1f8164eb74aaa5d67e94dc0b6d2da211e2b694e285d34cea` | `backend-laravel/backups/p0-academic-provisioning-20261125/AssessmentTaxonomySeeder.php.before` |
| `backend-laravel/app/Services/Licensing/InstallationIdentityService.php` | `66115b340189581b34bcf027f73c1049281560e5` | `86d72d1e5af210855000ede57b8f046d39d4c0f06632e75ba04721e2fc1ab449` | `backend-laravel/backups/p0-academic-provisioning-20261125/InstallationIdentityService.php.before` |
| `.github/workflows/build-web-deployment.yml` | `c2ea4c907001ce7f12aa347f354f8099dffa07e3` | `9bb3b4cee00493407fa2f8537ebaff7f736ee6bf6a1922258794b7b3b710f44c` | `backend-laravel/backups/p0-academic-provisioning-20261125/build-web-deployment.yml.before` |
| `backend-laravel/DISTRIBUTION_EXCLUSIONS.md` | `8ef98c10ed61ee7927aaa25252656918f4b4cc3a` | `dd4f6295cb31d01f534584dfea640621f14bc6821d6ff62330f4b47ab659b314` | `backend-laravel/backups/p0-academic-provisioning-20261125/DISTRIBUTION_EXCLUSIONS.md.before` |
| `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php` | `9d076387c1e81a4b229b0358911a4f9133085a86` | `cb19390aedfc4685b18c9b8fe29c812ebabd4f480a7b6fec7a6245d2280d52b6` | `backend-laravel/backups/p0-academic-provisioning-20261125/TeacherSubjectAssignmentManagement.php.before` |
| `backend-laravel/resources/views/livewire/admin/teacher-subject-assignment-management.blade.php` | `91d7f3cfa021c9a77baa84963a062cdf4d319c42` | `7235a584a8ea7ae1a89c5007d16973a2210f4c3f32ccf6b368903b9f98af7e06` | `backend-laravel/backups/p0-academic-provisioning-20261125/teacher-subject-assignment-management.blade.php.before` |

Backups are retained in the controlled repository and must be excluded from distribution ZIPs.