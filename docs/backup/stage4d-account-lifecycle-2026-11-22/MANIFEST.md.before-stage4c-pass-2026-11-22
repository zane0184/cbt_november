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
