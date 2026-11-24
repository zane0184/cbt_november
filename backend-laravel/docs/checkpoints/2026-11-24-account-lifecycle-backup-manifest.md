# Manifest Backup Lifecycle Akun — 24 November 2026

Repository: `zane0184/cbt_november`  
Branch: `work/solo-web-cbt-completion-20261123-execution`  
HEAD penutupan: `0250dc257cc7292d960cacf2c20eda782ee8a25d`

## Verifikasi byte-exact terhadap baseline sebelum lifecycle

SHA Git blob bersifat content-addressed. Kesamaan SHA pada file backup dan file sumber di baseline menunjukkan isi byte yang identik.

| File sumber baseline | File backup | SHA sumber baseline | SHA backup | Hasil |
|---|---|---|---|---|
| `backend-laravel/app/Models/User.php` | `User.php.before-account-lifecycle` | `dec82ba26e785708945bdea1bc0e34a1ed20458d` | `dec82ba26e785708945bdea1bc0e34a1ed20458d` | MATCH |
| `backend-laravel/app/Http/Middleware/IdentifyTenant.php` | `IdentifyTenant.php.before-account-lifecycle` | `376a4b2771c3d9a6b128ad847c150d47ac2977db` | `376a4b2771c3d9a6b128ad847c150d47ac2977db` | MATCH |
| `backend-laravel/app/Http/Controllers/AuthWebController.php` | `AuthWebController.php.before-account-lifecycle` | `d9512b27463b1b732ff49950243553ae750dc3a1` | `d9512b27463b1b732ff49950243553ae750dc3a1` | MATCH |
| `backend-laravel/app/Http/Controllers/Api/V1/AuthController.php` | `AuthController.php.before-account-lifecycle` | `7680c93bc3f88c43ab47cb1a4c24a603947f79de` | `7680c93bc3f88c43ab47cb1a4c24a603947f79de` | MATCH |
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` | `ExamAttemptController.php.before-account-lifecycle` | `4668ff26bd0e897f9ab9d4976491ed9665e1ae37` | `4668ff26bd0e897f9ab9d4976491ed9665e1ae37` | MATCH |
| `backend-laravel/app/Livewire/Admin/UserManagement.php` | `UserManagement.php.before-account-lifecycle` | `b3da526b4c3845f724e3cf2ba9d625f1b9ef5a2b` | `b3da526b4c3845f724e3cf2ba9d625f1b9ef5a2b` | MATCH |
| `backend-laravel/resources/views/livewire/admin/user-management.blade.php` | `user-management.blade.php.before-account-lifecycle` | `0f01fad6b6f933004a350fbeb6fb1be4f0a2365d` | `0f01fad6b6f933004a350fbeb6fb1be4f0a2365d` | MATCH |
| `backend-laravel/routes/web.php` | `web.php.before-account-lifecycle` | `a2fb97e8324f28e6f1e52dc8fa4737faf87dd675` | `a2fb97e8324f28e6f1e52dc8fa4737faf87dd675` | MATCH |
| `backend-laravel/routes/api.php` | `api.php.before-account-lifecycle` | `cee10d9dd15996294ba700cdf0565ac3889d239e` | `cee10d9dd15996294ba700cdf0565ac3889d239e` | MATCH |
| `.github/workflows/tenant-backend-ci.yml` | `tenant-backend-ci.yml.before-focused-lifecycle-steps` | `e21c6be218a374bafc19813fda63621fe3884d6a` | `e21c6be218a374bafc19813fda63621fe3884d6a` | MATCH |

## Seluruh snapshot backup yang tersimpan

Setiap file berikut adalah snapshot tersimpan di Git. SHA adalah blob SHA yang diperiksa pada tree HEAD penutupan.

| Lokasi backup | Blob SHA |
|---|---|
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AccountLifecycleLicenseRouteTest.php.before-context-teardown` | `19af1a42658f26892630b50a345cc35a122344c5` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AccountLifecycleLicenseRouteTest.php.before-tenant-context-fixture-fix` | `d4b7a578e09550299ee7b53e5f11da633e3e50b7` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AccountResetAttemptConcurrencyTest.php.before-ci1808-concurrency-rework` | `34aa9fc0b48a829f76629483ad89282ad556e087` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AccountResetAttemptConcurrencyTest.php.before-deactivation-races` | `4b28697279a730d64878dd8d0f15a12791a7f786` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AccountResetAttemptConcurrencyTest.php.before-deadlock-and-assertion-fixes` | `89b1d3907aa52a793f8403096033ebebce52c4f8` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AccountResetAttemptConcurrencyTest.php.before-global-role-cleanup` | `87636f2486b1a1fa058f9d68add1fdf1c384a79e` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AccountResetAttemptConcurrencyTest.php.before-lock-timeout-and-pipe-cleanup` | `80c72da2cc4e711a144e112c080d1d09e4d6815a` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AccountResetAttemptConcurrencyTest.php.before-strict-deadlock-1213` | `1055424bbdbb4ab93dd771ebeab48108e004ab23` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-ci1808-test-fixes` | `bbfedc15acea550b0d95f6e5bff989d1ba847b45` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-cookie-prefix-decrypt-correction` | `c0b340e3e509d8fd86af8d42587c3c261bdf5a5d` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-cookie-prefix-fqcn-fix` | `9e397202c88df998bac94805528c28d6c36e220b` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-cookie-prefix-validate-return-fix` | `1dc6358b100a904cb520a41c74aed9c216b4dbf1` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-cookie-session-driver-fixes` | `57bc55a1ff471324e5686591c0180ef7536e4e41` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-distinct-session-cookie-fix` | `68e0983aa1f2e28ce16cf103eac9b482c1ea4103` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-expanded-security-tests` | `17eb8a916581d123a965da7df2a11f7cea429c8f` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-final-cookie-replay-fix` | `8c2aaa545c50fb5cf2700163a34ce067b043655a` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-livewire-vite-test-fix` | `e245ad1844b1e7e874c52c0545552b58c4e0a249` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-login-reset-race-test` | `0f253e934fd24631d396c635625e2d1e6313f778` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-next-acceptance-tests` | `61e3207abfac6f82cef56cd18a1f680bcc0863bc` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-raw-session-cookie-replay` | `4c1bb5eb80a8e181a0da37b5dcc9f82f62a43a91` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-root-login-route-guards` | `e4b97d5fa46cf5e9a167c5f3b916a87df465a4b5` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-session-owner-binding-flow` | `a64a0b92855fe3b01edea45825a094f92c520666` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-session-owner-lookup-fix` | `9ccd9a14c74ee4f34f9e7619753d657b12355f28` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-session-profile-and-tenant-assertion-fix` | `2179b4e670af272dda79547d9a142a8aa7a9a86f` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-session-store-id-fix` | `47170e7607518239440f28369e00cef1c3c9a052` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-session-test-isolation-split` | `63de4f2cec2d8e18a23b7c34e69560e9e030ddbd` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-session-token-assertion-fix` | `458c2b444de8c200102b527fe0622eba238e9520` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-teacher-profile-fqcn-fix` | `3f97adcfa139623fa400f424e9bef77db7e6ca1d` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-teacher-profile-helper-fqcn-fix` | `1ebaa5afba06522d71578fea44e919d8649e3d9c` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AdminUserAccountLifecycleTest.php.before-web-guard-logout-fix` | `b4f9292ab5494a414753768b2da56d747b515979` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/ApiAuthController.php.before-password-race-recheck` | `2bd70d0a61359a1741317b2b074ca4d858207095` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AuthController.php.before-account-lifecycle` | `7680c93bc3f88c43ab47cb1a4c24a603947f79de` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AuthWebController.php.before-account-lifecycle` | `d9512b27463b1b732ff49950243553ae750dc3a1` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AuthWebController.php.before-force-change-redirect-fix` | `b33a428959197817767978939c289fb86b7ab0f8` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/AuthWebController.php.before-password-race-recheck` | `fde1dd5a55de6f1c43d4100e575df34a6f4bb5b0` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/ExamAttemptController.php.before-account-lifecycle` | `4668ff26bd0e897f9ab9d4976491ed9665e1ae37` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/IdentifyTenant.php.before-account-lifecycle` | `376a4b2771c3d9a6b128ad847c150d47ac2977db` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/IdentifyTenant.php.before-database-session-owner-binding` | `13dcb23e0dab902f3bd2d45e1e662ecc03454036` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/IdentifyTenant.php.before-fqcn-escape-fix` | `9c19211f2543cf648d2ed078fec80b3b532b1ded` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/User.php.before-account-lifecycle` | `dec82ba26e785708945bdea1bc0e34a1ed20458d` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/User.php.before-model-defaults-fix` | `65946dbe2968afe3b2ba16f7d226171da024becb` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/UserManagement.php.before-account-lifecycle` | `b3da526b4c3845f724e3cf2ba9d625f1b9ef5a2b` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/api.php.before-account-lifecycle` | `cee10d9dd15996294ba700cdf0565ac3889d239e` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/tenant-backend-ci.yml.before-focused-lifecycle-steps` | `e21c6be218a374bafc19813fda63621fe3884d6a` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/user-management.blade.php.before-account-lifecycle` | `0f01fad6b6f933004a350fbeb6fb1be4f0a2365d` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/web.php.before-account-lifecycle` | `a2fb97e8324f28e6f1e52dc8fa4737faf87dd675` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/web.php.before-license-tenant-middleware` | `5994bb0d76c8dc2daaf0ade28d7124eba6dcd451` |
| `backend-laravel/docs/backup/account-lifecycle-2026-11-24/web.php.before-root-and-login-tenant-guard` | `1d2f6f29e8f70a4f3e455b42e3fd81527ed4192b` |

## File baru

File yang ditambahkan pada implementasi lifecycle (migration, controller password, view password, dan test baru) tidak memiliki source pendahulu yang dapat disalin sebagai backup. Test yang kemudian direvisi memiliki snapshot bertahap di direktori backup di atas.

## Catatan integritas

- ZIP/baseline asli tidak diubah.
- Backup disimpan dalam repository pada direktori `backend-laravel/docs/backup/account-lifecycle-2026-11-24/`.
- Tidak ada backup yang digunakan untuk menimpa source pada penutupan ini.
- Untuk pasangan sumber lama, seluruh sepuluh pasangan utama berstatus MATCH.
