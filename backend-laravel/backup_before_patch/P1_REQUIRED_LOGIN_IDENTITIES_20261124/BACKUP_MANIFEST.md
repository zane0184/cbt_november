# Backup byte-exact — P1 Required Login Identities

- Branch: `work/p1-seven-targets-20261124`
- Source baseline: P1 branch HEAD immediately before backup creation.
- Date: 24 November 2026
- Purpose: preserve the exact source files before enforcing the latest owner instruction for Guru login identities.

- Source: `backend-laravel/app/Livewire/Admin/UserManagement.php`
  - Source blob SHA: `899b011fe40492c570c9611dbde90554ebffe1a6`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/UserManagement.php.before`
  - Backup commit: `2ce7d426b17b07a40d8c4a80c0090fe753fb67e4`
- Source: `backend-laravel/app/Services/LoginIdentityResolver.php`
  - Source blob SHA: `1edf12db91b2e6d751844153de457104aa3001b9`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/LoginIdentityResolver.php.before`
  - Backup commit: `f6bdb8b5b7645ae4f93309d13505b70f5a67189e`
- Source: `backend-laravel/resources/views/auth/login.blade.php`
  - Source blob SHA: `8e02e832b42993c87dca490c0a81d42b8055cd44`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/login.blade.php.before`
  - Backup commit: `74a9830d92aec61791c8445d945bb8cf97ffb520`
- Source: `backend-laravel/resources/views/livewire/admin/user-management.blade.php`
  - Source blob SHA: `4287ec71afc292b40fbaff036363e1ec36ffd141`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/user-management.blade.php.before`
  - Backup commit: `bbb6f6bcfbaa0deac8f3fb42eca23262e8780506`
- Source: `backend-laravel/app/Http/Controllers/AuthWebController.php`
  - Source blob SHA: `d9512b27463b1b732ff49950243553ae750dc3a1`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/AuthWebController.php.before`
  - Backup commit: `7eb9906977802b20329166338d9e2dfe4a81eb96`
- Source: `backend-laravel/tests/Feature/AdminUiVerificationTest.php`
  - Source blob SHA: `1fd857c73ef6ad9336fd5cd2d308aaa1af26aeec`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/AdminUiVerificationTest.php.before`
  - Backup commit: `8eead14d143f3ba98d93a0a81e02c4bb3d6ed04a`
- Source: `backend-laravel/tests/Feature/WebAdminAuthTest.php`
  - Source blob SHA: `1762d3972f956f6387287e8f22e491bae9cfde5d`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/WebAdminAuthTest.php.before`
  - Backup commit: `79267bbd2a23c5fd60ccae9e5648f8c13f6e6a3d`

- Source: `backend-laravel/tests/Feature/StudentWebLoginTest.php`
  - Source blob SHA: `d3a24650db9dfb7466ab230300084c9277579d13`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/StudentWebLoginTest.php.before`
  - Backup commit: `26301cf2faa2851285a2adc464cf3008dff57742`

- Source: `backend-laravel/bootstrap/app.php`
  - Source blob SHA: `d1137fce9e10bc490a044f950170008d55cab914`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/bootstrap-app.php.before`
  - Backup commit: `9b7eeb7cd9a541701362c0f398a3847956e37970`

- Source: `backend-laravel/app/Models/TeacherProfile.php`
  - Source blob SHA: `a5f232895d9e0702ad81dd939344780fe5e86331`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/TeacherProfile.php.before`
  - Backup commit: `undefined`

- Source: `backend-laravel/app/Models/StudentProfile.php`
  - Source blob SHA: `4237e764cf722817cd1ce27b4ffa134ad2d776c7`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/StudentProfile.php.before`
  - Backup commit: `undefined`

- Source: `backend-laravel/resources/views/livewire/admin/student-rombel-management.blade.php`
  - Source blob SHA: `dde84fb3f11f075a8633c6822bbf0ce2c7dde35c`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/student-rombel-management.blade.php.before`
  - Backup commit: `undefined`

- Source: `backend-laravel/resources/views/livewire/teacher/portal-profile.blade.php`
  - Source blob SHA: `97001a504f11c19c686d35bd83fda824c4590203`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/portal-profile.blade.php.before`
  - Backup commit: `undefined`

- Source: `backend-laravel/database/migrations/2026_11_24_170001_enforce_tenant_login_identity_uniques.php`
  - Source blob SHA: `c7dcc69d494e42c6fde77b77b76f8f7a6467bad2`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/2026_11_24_170001_enforce_tenant_login_identity_uniques.php.before_refinement`
  - Backup commit: `undefined`

- Source: `backend-laravel/app/Console/Commands/LoginIdentityPreflightCommand.php`
  - Source blob SHA: `ce0fbe32ddd2c57faecf857ae1041946405db368`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/LoginIdentityPreflightCommand.php.before_refinement`
  - Backup commit: `undefined`

- Source: `backend-laravel/tests/Feature/LoginIdentityPreflightCommandTest.php`
  - Source blob SHA: `f6b8f1729b20394ed9b67720c343ac7c30e23b66`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/LoginIdentityPreflightCommandTest.php.before_refinement`
  - Backup commit: `undefined`

- Source: `backend-laravel/database/migrations/2026_11_24_170001_enforce_tenant_login_identity_uniques.php`
  - Source blob SHA: `9dec8add2936be9d1f54fa3f4fb6e251422cc08c`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/2026_11_24_170001_enforce_tenant_login_identity_uniques.php.before_length_compare`
  - Backup commit: `2f564e7336b29214d5626872327164fa961ab3f8`

- Source: `backend-laravel/tests/Feature/AdminUiVerificationTest.php`
  - Source blob SHA: `bf878cddd0a97a67b30bd93c8c442c49bdad78a2`
  - Backup: `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/AdminUiVerificationTest.php.before_all_identity_unique_assertions`
  - Backup commit: `967dcfe62aed80c2f6d1431f20a142a284ce4112`
