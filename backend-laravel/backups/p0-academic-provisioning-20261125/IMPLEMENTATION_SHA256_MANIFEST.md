# Implementation SHA-256 Manifest

- Repository: `zane0184/cbt_november`
- Branch: `work/p0-academic-provisioning-20261125`
- Tested source commit: `61de22182c9804df4d8749c901069a11046024dc`
- Hash basis: SHA-256 over UTF-8 bytes of exact GitHub blob text.
- Backup manifest: `backend-laravel/backups/p0-academic-provisioning-20261125/BACKUP_MANIFEST.md`
- Backup manifest SHA-256: `eeaff57e75b4ffc5450c556f2cf7fce8b717c511a45c49ba8b2c4ed75cc6a717`
- Byte-exact pre-patch backups independently rechecked: **13/13 MATCH**.
- No database migration files were added or changed.

| Implementation file | SHA-256 |
|---|---|
| `.github/workflows/tenant-backend-ci.yml` | `446b6cb66c27c91c22fc7c3dac334289a06188323596a78d916755338eef752f` |
| `.github/workflows/build-web-deployment.yml` | `7c1a6bae25d74105a659e013277c2756cfd4c9fd0ea2455220373661e4adfcb4` |
| `backend-laravel/DISTRIBUTION_EXCLUSIONS.md` | `aa66daf2902087868f19493563e65408b195ebf1357655938ddb12cca53ca638` |
| `backend-laravel/app/Livewire/Admin/AcademicManagement.php` | `ff840c7c93ce1deafab5d0f1cd8ea856960841514b57a6374354b0ef61fbf974` |
| `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php` | `52c75c7b034902194fcaf1b376b19010c447f78d95053950bb33b351d2f30fab` |
| `backend-laravel/app/Services/AcademicYearManagementService.php` | `ac1b4c35ce88327f71f153d0d1a71d82bed66626dfc0c8f4663230bba3b4df2d` |
| `backend-laravel/app/Services/InstallerService.php` | `73a9b1c20d809893203b3f17e403fd2671bedb7348c76c091da64bb5e7ddb789` |
| `backend-laravel/app/Services/Licensing/InstallationIdentityService.php` | `5a3cdf53fccfd611cc144b4e68e24f34e591032b28af9fbc5dff3765edc3df2a` |
| `backend-laravel/app/Services/TenantAcademicBootstrapService.php` | `39b43b1b59f5bcad5554d484642f6ca33ad37f2bb3598fd97a646146e2343ee5` |
| `backend-laravel/resources/views/livewire/admin/academic-management.blade.php` | `3c0f05c5cdda300fd086c22355ced72634030defefe0bea783a779cc7e8a5c54` |
| `backend-laravel/resources/views/livewire/admin/teacher-subject-assignment-management.blade.php` | `5b858664c2277b69f39cd717b9c774dc417212e5d2acfccac0f07e876d40e4ac` |
| `backend-laravel/tests/Feature/AcademicYearManagementTest.php` | `130d028fb44314261dacbecfb4768d7e1e1416c68e422442721194f1f311a6e6` |
| `backend-laravel/tests/Feature/InstallerEndToEndTest.php` | `dfe2051b1efb0586fadf7c9fd48bd2e7a70cc74deee7cbee0faa8db98367b8c0` |
| `backend-laravel/tests/Feature/TenantAcademicBootstrapServiceTest.php` | `d07a291c0c3c07b1c63dfa3139b6fb183a4efa7461508ca2bc2f1b9da62354d2` |
| `backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php` | `e4776eaf825ef4ebfa9060e35813aa9e7f001397ec3d893b111f57e99f67cd3d` |

Pre-patch source blob IDs, SHA-256 values, and backup paths are listed in `BACKUP_MANIFEST.md`.
