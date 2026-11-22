# Backup manifest — Stage 4D parallel work

Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4d-account-lifecycle-20261122
Base source HEAD: 0631a3d84d058c8c049dcb71dc54b2e7cbb849a6

| Source path | Backup path | Original blob SHA | UTF-8 bytes | Backup commit |
|---|---|---|---:|---|
| backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php | docs/backup/stage4c-teacher-subject-assignment-2026-11-22/TeacherSubjectAssignmentManagementTest.php.before-ci-fixture-fix | bfd4b0950fa7293cde7e59cf7a36e5c6e9fe6bd9 | 9412 | cfb7856abffb353539ec3685c79c08a4f4475a5c |
| .github/workflows/wa01-alternative-isolated-runtime.yml | docs/backup/stage4d-account-lifecycle-2026-11-22/wa01-alternative-isolated-runtime.yml.before-branch-enable | 24b9b01d1c4ff63f13bb2d64592cecf03fe68cb5 | 24874 | 97b41b89f96e996a1419abc2ad44777a18540340 |

No application migration, production database, VPS, Android source, or baseline ZIP was changed.


## Snapshot tambahan — koreksi fixture setelah CI 44981468061

| Source path | Backup path | Original blob SHA | UTF-8 bytes | Backup commit |
|---|---|---|---:|---|
| backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/TeacherSubjectAssignmentManagementTest.php.before-nonadmin-fixture-fix | b402b25a88518d426da37d13b54708492b8b7e45 | 9897 | 9c18882e4202c6a43fcf660614b1436c46167f20 |

Perubahan setelah snapshot hanya memperbaiki fixture test non-Admin agar memakai role GURU yang sudah dibuat setUp(), bukan membuat role duplikat. Source runtime tidak diubah.

## Snapshot tambahan — perbaikan concurrency test dan isolasi workflow

| Source path | Backup path | Original blob SHA | UTF-8 bytes | Backup commit |
|---|---|---|---:|---|
| backend-laravel/tests/Feature/TeacherSubjectAssignmentConcurrencyTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/TeacherSubjectAssignmentConcurrencyTest.php.before-forward-only-and-branch-isolation-fix | c87892e310582d63e4c2920ccdff52aec081e7fe | 7266 | 586539e659103278b8a1b16dfbe12fb0137ec6eb |
| .github/workflows/wa01-alternative-isolated-runtime.yml | docs/backup/stage4d-account-lifecycle-2026-11-22/wa01-alternative-isolated-runtime.yml.before-forward-only-and-branch-isolation-fix | fc707c3847195b8deeee36318296baad3911184c | 24922 | b0e7257eac640b4ab2f13bef879cf6965cf38458 |

## Snapshot dokumen sebelum addendum CI

| Source path | Backup path | Original blob SHA | Backup commit |
|---|---|---|---|
| docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md | docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md.before-ci-root-cause-addendum | 8a98f57c5d9fffff017cad9537cc85c96ca0125e | 6f1fd42b8ade49305f4d70d3309c3f3a46c3c1ab |
| docs/LAPORAN_STAGE4D_AUDIT_DESAIN_2026-11-22.md | docs/backup/stage4d-account-lifecycle-2026-11-22/LAPORAN_STAGE4D_AUDIT_DESAIN_2026-11-22.md.before-ci-root-cause-addendum | 06a4ba1281a61c06962cfa140f44c826d85c2b99 | a0c20076ed72c43c2f913cc521acca2fe996050e |
| docs/CHECKPOINT_STAGE4D_ACCOUNT_LIFECYCLE_2026-11-22.md | docs/backup/stage4d-account-lifecycle-2026-11-22/CHECKPOINT_STAGE4D_ACCOUNT_LIFECYCLE_2026-11-22.md.before-ci-root-cause-addendum | 2c783c115fba0db40492b7def4e4bc1900032621 | 4bbf31a283cc2e372a7311eb034696999608305f |

Patch 4C concurrency menghapus trait DatabaseMigrations dari test yang dijalankan pada database disposable yang sudah dimigrasikan. Workflow menjalankan migrate:fresh pada schema disposable yang identitasnya telah diverifikasi sebelum race test, sehingga teardown tidak memanggil down() pada migration keamanan forward-only. Workflow Stage4D juga melewatkan test dan required schema Stage4B; Stage4B tetap diuji hanya pada branch Agent1.


## Snapshot tambahan — sebelum checkpoint CI terbaru

| Source path | Backup path | Original blob SHA | UTF-8 bytes | Backup commit |
|---|---|---|---:|---|
| docs/CHECKPOINT_STAGE4D_ACCOUNT_LIFECYCLE_2026-11-22.md | docs/backup/stage4d-account-lifecycle-2026-11-22/CHECKPOINT_STAGE4D_ACCOUNT_LIFECYCLE_2026-11-22.md.before-final-ci-update | 900fac446d4743d67caa1757d48dd1014c47bc9d | 3132 | e3fbb27a5509c0ebe665a84a5039e137265e6807 |
| docs/LAPORAN_STAGE4D_AUDIT_DESAIN_2026-11-22.md | docs/backup/stage4d-account-lifecycle-2026-11-22/LAPORAN_STAGE4D_AUDIT_DESAIN_2026-11-22.md.before-final-ci-update | 7226563c4ba09074b0652523f34248d0c5d3a9cf | 19253 | 9dd1c3ab1a08175418e051c2021c376920305341 |
| docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md | docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md.before-final-ci-update | ab23fb49f7a0fd01c165a0c8a288853cc5b50af4 | 3733 | d265d5c96942ceb3f62ba3ca9915052522135dcb |
| backend-laravel/tests/Feature/TeacherSubjectAssignmentConcurrencyTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/TeacherSubjectAssignmentConcurrencyTest.php.before-forward-only-migration-fix | 80e282e0023e0f058f07d185f03d363c667c2f6a | 7595 | see branch history |

## Dokumen audit tambahan
- `docs/AUDIT_STAGE4E_4G_SOURCE_MATRIX_2026-11-22.md` (blob 68435d9a87beca5b9afb18fc4fd459bc7f497ed8) memuat inventory route, controller, model, migration, policy, permission, test, serta gap authorization API kurikulum dan desain histori akademik.
- Functional source commit terakhir: `af4e26ad974e0cded776ca552cafa77bcf4ae4d7`.
- HEAD branch setelah commit dokumentasi/backup: `d265d5c96942ceb3f62ba3ca9915052522135dcb` (docs/backup only setelah functional source commit).
- CI run `44982525201` pada SHA `af4e26ad974e0cded776ca552cafa77bcf4ae4d7` masih berjalan saat manifest ini disusun.

Tidak ada source runtime Stage4D, migration, DB produksi, VPS, Android, atau ZIP baseline yang diubah.


## Snapshot tambahan — sebelum update status Stage 4C PASS (22 November 2026)

| Source path | Backup path | Original blob SHA | UTF-8 bytes | Backup commit |
|---|---|---|---:|---|
| docs/LAPORAN_STAGE4D_AUDIT_DESAIN_2026-11-22.md | docs/backup/stage4d-account-lifecycle-2026-11-22/LAPORAN_STAGE4D_AUDIT_DESAIN_2026-11-22.md.before-stage4c-pass-2026-11-22 | 7226563c4ba09074b0652523f34248d0c5d3a9cf | belum dihitung ulang | a483a376e1690a5e07b01d7a70381ea374685288 |
| docs/CHECKPOINT_STAGE4D_ACCOUNT_LIFECYCLE_2026-11-22.md | docs/backup/stage4d-account-lifecycle-2026-11-22/CHECKPOINT_STAGE4D_ACCOUNT_LIFECYCLE_2026-11-22.md.before-stage4c-pass-2026-11-22 | 900fac446d4743d67caa1757d48dd1014c47bc9d | belum dihitung ulang | 0510142fa117405f15f291322ed42c5cb755187b |
| docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md | docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md.before-stage4c-pass-2026-11-22 | b5fd685b87c08794a93f2d6047c0f75ee85d82e6 | belum dihitung ulang | be2a2d38b2dc82bd622d5d5926bb6ceeb271670f |

## Hasil CI terbaru yang dicatat
- Stage4D run `44982525201`, exact SHA `af4e26ad974e0cded776ca552cafa77bcf4ae4d7`: final completed/success.
- Stage4C lifecycle tenant boundary: 7 passed / 26 assertions.
- Stage4C MySQL 8 tenant mutex concurrency: 1 passed / 8 assertions.
- InnoDB: 80 base tables, 0 non-InnoDB; required schema ready; cleanup disposable database/user success.
- Stage4B tests pada run Stage4D: skipped (tidak dihitung PASS).
- Agent1 latest observed HEAD `af36ad3b8b188e147e084045af0e02ee8b591d65`; run `44983125116` pending saat update; prior run `44982968567` in_progress pada SHA `fb868c3ae1f8f9701d09b4abeadb978027796b46`. Branch Agent1 tidak disentuh.
- Admin CBT denial matrix masih parsial; Gate minimum Admin belum lulus. Agent3 WG-01 belum disentuh.
- Perubahan pada checkpoint ini dokumentasi saja; tidak ada source runtime/migration/database yang berubah.

Dokumen status diperbarui pada commit report `8a1ea04a676bee3327c73614cce06f7db401a07a` dan checkpoint `8c1225a1437344dc5f52726e5087c3d131bfb996`.


## Snapshot tambahan — sebelum re-audit branch dan account lifecycle (22 November 2026)

| Source path | Backup path | Original blob SHA | Backup commit |
|---|---|---|---|
| docs/LAPORAN_STAGE4D_AUDIT_DESAIN_2026-11-22.md | docs/backup/stage4d-account-lifecycle-2026-11-22/LAPORAN_STAGE4D_AUDIT_DESAIN_2026-11-22.md.before-branch-reaudit-2026-11-22 | 7873c0fb77f5bf53ccac3ede4c6c2fc9b623b11a | dd32026bffcbb0ea8371ea42946d1fe78504975e |
| docs/CHECKPOINT_STAGE4D_ACCOUNT_LIFECYCLE_2026-11-22.md | docs/backup/stage4d-account-lifecycle-2026-11-22/CHECKPOINT_STAGE4D_ACCOUNT_LIFECYCLE_2026-11-22.md.before-branch-reaudit-2026-11-22 | 286101c750749a697860d8d769a1e706e6940da9 | 2e97426c415f839dcbd3a5bf7a23c599985bc7dd |
| docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md | docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md.before-branch-reaudit-2026-11-22 | 69c1f8eadf07abfeb5cc4f086eff31390bb8e414 | 1fdd7aa0281989e6e62166904773392443313bcc |

## Backup test tambahan account lifecycle

| Source path | Backup path | Original blob SHA | Backup commit |
|---|---|---|---|
| backend-laravel/tests/Feature/WebAdminAuthTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/WebAdminAuthTest.php.before-existing-session-http-test | bae52cbfbb9676f6fecba8514afef26cc6bfa90b | f1e1e5afc7a34311d39db83d656a75ff7d3b4364 |
| backend-laravel/tests/Feature/WebAdminAuthTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/WebAdminAuthTest.php.before-session-test-ci-selection-fix | ac50526aa310f170803e08f6beb7881e7474f87d | 2a6976d4f14ff1474284d5a25850bdd0e0789820 |
| backend-laravel/tests/Feature/AuthTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/AuthTest.php.before-soft-deleted-sanctum-http-test | 803f409f48b544d947039d0879d7e56c4c118336 | 4b21e7c303479af62e7af07685220702b5645699 |

## Hasil re-audit branch dan CI
- Stage4D run `44984685015`: SUCCESS pada SHA `8bc75302636f78fe2155fee49299d2a375566e7b`; PHP 8.3.37/MySQL 8.0.46; lifecycle 8/54; Admin boundary 1/27; monitoring 15/151; proctor 8/34; Stage4C lifecycle 7/26; concurrency 1/8; InnoDB 80/0. Stage4B test steps skipped.
- Stage4D run `44988322932`: SUCCESS pada SHA `465f7c7b10b87d76ff3b03c85cc437608457a4e0`; tidak membuktikan test HTTP session tambahan karena method terpisah belum dipilih oleh filter CI.
- Commit `6113ea50322a56378df3839311677f8124ffad0c`: session HTTP test dipindahkan ke method yang dipilih CI; run `44988674922` berjalan saat manifest ini diperbarui.
- Commit `3e052d97ce692b9c2301761aad978dfee727423e`: test HTTP old Sanctum token added to AuthTest; run `44988721095` pending saat manifest ini diperbarui.
- Agent1 run `44984303003` SUCCESS pada SHA `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5`; 12/49 room session; 2/22 concurrency; 80/0 InnoDB; child-first rollback with FK checks enabled PASS for empty schema.
- Agent1 branch HEAD `4e663cd84038b7c3337d776f702c499f20d8f537` hanya dua dokumen setelah tested SHA. Agent3 branch HEAD `25fe5e74a0754bcd110ceffac20141880c549ca8` hanya docs setelah tested SHA; tidak disentuh.
- Gate Admin belum lulus; reset token belum diimplementasikan; auth_version/session fencing setelah reaktivasi belum diimplementasikan; histori akademik tidak dimigrasi.
- Dokumen re-audit diperbarui pada report commit `26c24fed12819b8ff1bcc9bec208daacac55aedf` dan checkpoint commit `2ca0e3a67a1d393f8a8cfabbbcb3f2a1cccf091c`.


## Backup auth version/session fencing

| Source path | Backup path | Original blob SHA | Backup commit |
|---|---|---|---|
| docs/LAPORAN_STAGE4D_AUDIT_DESAIN_2026-11-22.md | docs/backup/stage4d-account-lifecycle-2026-11-22/LAPORAN_STAGE4D_AUDIT_DESAIN_2026-11-22.md.before-auth-version-checkpoint | 40332f4ec1714de6d72fbea57478e8f114412ffb | 151c550d0a114dc77bd484012494e79906a6c1f6 |
| docs/CHECKPOINT_STAGE4D_ACCOUNT_LIFECYCLE_2026-11-22.md | docs/backup/stage4d-account-lifecycle-2026-11-22/CHECKPOINT_STAGE4D_ACCOUNT_LIFECYCLE_2026-11-22.md.before-auth-version-checkpoint | b809ab95b7e922176e06616836b7b424465bc5cf | 9a659a74078753d7f0f062846d602984b6bda196 |
| docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md | docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md.before-auth-version-checkpoint | 525a3bbade5398b7bc40495f34bec2d710e91775 | 095b4bc7ebe392a320c6f8e0223ab7c5ebbddcb1 |
| backend-laravel/tests/Feature/WebAdminAuthTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/WebAdminAuthTest.php.before-active-after-reactivation-assertion-fix | 35facc82b879550bde8b39764fc734037a647933 | 41f8ef7f15b713f02ac46b24ce20f75a3d24365c |

## Session fencing implementation and CI
- Auth epoch migration: `2026_11_22_130002_add_auth_version_to_users.php`.
- Middleware: `EnsureAccountSessionVersion.php`; web session key `cbt.auth_version.{user_id}`; mismatches logout, invalidate session, regenerate CSRF token, redirect login.
- Web login binds current epoch; deactivate and reactivate increment it; API tokens revoked on deactivate.
- Source commits: `ab40b6d55ad12a384dbe664e9cef1ccf09f071a4`, `f96d2a3f85d8be85950ab115ce73f15f928be9a9`, `bf5eba7b32050d93aadf3c5dd9d68609297ad1c8`, `d79382ca9b2be1dc5a0f65cd3c95f4dc02c1f443`, `518f324d124793d7843f33b223e1ef57638d9ff4`, `b2adaf95302f082a2c744fc7f3f5964ac716ad6e`.
- Test commits: `2ef93713fedc40187cea1613f712a81abe3e2b94`, `9bbf64b21a67345f1720ec27277c8d3693de39f4`, `ac80026beced315acddb4c8adc8524d5779474c8`, assertion fix `0ac6dc938291e07fa871499f25983931282de5cf`.
- Run `44989053716` (SHA `ac80026...`) IN_PROGRESS and run `44989260109` (SHA `0ac6dc9...`) PENDING when recorded; do not claim auth epoch tests PASS until exact latest run completes.
- Reset password tenant-scoped not implemented; Admin CBT Gate not passed.


## Snapshot tambahan — sebelum perbaikan fixture Admin CBT boundary P0 (22 November 2026)

| Source path | Backup path | Original blob SHA | UTF-8 bytes | Backup commit |
|---|---|---|---:|---|
| backend-laravel/tests/Feature/AdminCbtBoundaryTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/AdminCbtBoundaryTest.php.before-p0-tenant-context-fixture-2026-11-22 | 027683a33dfbc019ae70a7af5901ca986b539fed | 6967 | 8aa19814ed465e7bf470bb5b00403b1a3dc02446 |

Manifest sebelum penambahan ini disalin ke `docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md.before-p0-admin-boundary-fixture-2026-11-22` (blob SHA `252444b8c71240510d5bf722a4b6b2d387c8322f`, backup commit `b27aa24d4fbceb040708fa1f08177aedf06ac59b`). Backup source diverifikasi identik berdasarkan Git blob SHA sebelum file sumber diubah.


## Snapshot tambahan — sebelum penegakan auth epoch pada Sanctum stateful API (22 November 2026)

| Source path | Backup path | Original blob SHA | UTF-8 bytes | Backup commit |
|---|---|---|---:|---|
| backend-laravel/app/Http/Middleware/EnsureAccountSessionVersion.php | docs/backup/stage4d-account-lifecycle-2026-11-22/EnsureAccountSessionVersion.php.before-stateful-api-session-epoch-2026-11-22 | 76301079c626d943cabd2098938cb5674280b817 | 1594 | 44f05919e91c329ef22eb53fd3ababdbe6c644e3 |
| backend-laravel/routes/api.php | docs/backup/stage4d-account-lifecycle-2026-11-22/api.php.before-stateful-api-session-epoch-2026-11-22 | 4a9faa1e2bb0485570f30c199ccfbea7aa3a26ff | 10143 | 789c66f9af847615c304a27c38a374e9f4b1a1c3 |
| backend-laravel/tests/Feature/StudentWebLoginTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/StudentWebLoginTest.php.before-stateful-api-session-epoch-2026-11-22 | f2d5022755ce3fcd84d0c3a35f3386471e382f03 | 13778 | 5e807f53df2b23757f0b73ef2e3a3c3b98616822 |

Manifest sebelum penambahan ini disalin ke `docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md.before-stateful-api-session-epoch-2026-11-22` (blob SHA `82c7d108874cb45dba6fc5b72ded98305ffd45f8`, backup commit `4f95ea18fcfd27a0c17641bfbc3a644d78a20c93`). Seluruh backup source diverifikasi identik dengan source aktif berdasarkan Git blob SHA sebelum modifikasi.


## Snapshot tambahan — sebelum perbaikan no-session guard dan fixture Sanctum stateful (22 November 2026)

| Source path | Backup path | Original blob SHA | UTF-8 bytes | Backup commit |
|---|---|---|---:|---|
| backend-laravel/app/Http/Middleware/EnsureAccountSessionVersion.php | docs/backup/stage4d-account-lifecycle-2026-11-22/EnsureAccountSessionVersion.php.before-session-no-store-guard-2026-11-22 | e08e25526617ae83a70e5952cca988504705acbf | 1758 | e251e64616face990e1a79f65247225d334c3e12 |
| backend-laravel/tests/Feature/StudentWebLoginTest.php | docs/backup/stage4d-account-lifecycle-2026-11-22/StudentWebLoginTest.php.before-stateful-domain-fixture-2026-11-22 | 7d6d31d42fed9df1c54e1de8732aca6055fb5dd9 | 15446 | d8097ad40190c214b586cfbdfbef9a07f5e29e07 |

Manifest sebelum penambahan disalin ke `docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md.before-stateful-api-session-no-store-guard-2026-11-22` (blob SHA `2a85da4d1c3bd78a1b08215604aa87b4b3205ae6`, backup commit `64aec86c7d41a4b2b922eda99d17c775b22a748c`). Backup source diverifikasi identik dengan source aktif berdasarkan Git blob SHA.
