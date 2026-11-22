# Backup Manifest — Exact-role Portal Guru P0
Tanggal: 22 November 2026
Branch: `work/web-guru-foundation-20261122`
HEAD sebelum rangkaian backup: `702274d5298930f5f1dd13b71b7e402c12868109`

Seluruh backup dibuat sebagai file baru sebelum perubahan source. Blob SHA backup diverifikasi sama dengan blob SHA source sebelum perubahan.

| File sumber | Blob SHA source | Path backup | Blob SHA backup | Commit backup | Hasil |
|---|---|---|---|---|---|
| backend-laravel/app/Livewire/Admin/QuestionBank.php | ce69a86c8c33f7ae00f9faffa344ac18ff1c396c | docs/backup/web-guru-exact-role-20261122/QuestionBank.php.before | ce69a86c8c33f7ae00f9faffa344ac18ff1c396c | d1de1b9325d84beae45b65eae1933dfb162270f7 | MATCH |
| backend-laravel/app/Livewire/Admin/ExamManagement.php | d5380dd931aae00aadb9769fb22bdffcac20ad51 | docs/backup/web-guru-exact-role-20261122/ExamManagement.php.before | d5380dd931aae00aadb9769fb22bdffcac20ad51 | 2d735793fed40b4dbd3a11c2007f57fba3aad94d | MATCH |
| backend-laravel/tests/Feature/TeacherPortalFoundationTest.php | 414a0a7fca80898c4e9d3aceceac31378208aa30 | docs/backup/web-guru-exact-role-20261122/TeacherPortalFoundationTest.php.before | 414a0a7fca80898c4e9d3aceceac31378208aa30 | 10a4ff0396dad89e435096a550cf8f8d2643460b | MATCH |

## Ringkasan
- Backup source: 3/3 MATCH.
- Backup tidak menimpa file lama.
- Tidak ada file Agent 2, migration, schema, Composer, CBT Engine, atau database yang diubah pada tahap backup.

## Backup tambahan — foreign-tenant record ID regression

| File sumber | Blob SHA source sebelum edit | Path backup | Blob SHA backup | Commit backup | Hasil |
|---|---|---|---|---|---|
| backend-laravel/tests/Feature/TeacherPortalFoundationTest.php | b3e4204701277aad53ef88a86b33e2d7fdd41098 | docs/backup/web-guru-exact-role-20261122/TeacherPortalFoundationTest.php.before-foreign-tenant-id-test | b3e4204701277aad53ef88a86b33e2d7fdd41098 | 6862a1914a9c960a43d6f46aa216d06d54395ad7 | MATCH |
| docs/backup/web-guru-exact-role-20261122/MANIFEST.md | 7e98a904f205acd0c8f3201a2e9aea13fcbb805b | docs/backup/web-guru-exact-role-20261122/MANIFEST.md.before-foreign-tenant-id-test | 7e98a904f205acd0c8f3201a2e9aea13fcbb805b | c594652f420b222b51027725bdccded4d6549bdb | MATCH |

Jumlah snapshot backup terdaftar: 5. Seluruh blob SHA yang tercantum: MATCH. Backup awal tiga file tetap dipertahankan; backup test tambahan melindungi revisi test berikutnya.

## Backup tambahan — laporan/checkpoint dan manifest sebelum rekonsiliasi

| File sumber | Blob SHA source sebelum edit | Path backup | Blob SHA backup | Commit backup | Hasil |
|---|---|---|---|---|---|
| docs/LAPORAN_P0_EXACT_ROLE_IDENTITAS_LAPORAN_2026-11-22.md | d12cb964e26e23fafb1cb5dfec1d666b4d8dac2a | docs/backup/web-guru-exact-role-20261122/LAPORAN_P0_EXACT_ROLE.before-foreign-tenant-id-test.md | d12cb964e26e23fafb1cb5dfec1d666b4d8dac2a | eafd1a2ae62d470e646fda1b62215a5bfb033ddd | MATCH |
| docs/CHECKPOINT_P0_EXACT_ROLE_IDENTITAS_LAPORAN_2026-11-22.md | d637440befbc542427154b5481a90d2012d1633b | docs/backup/web-guru-exact-role-20261122/CHECKPOINT_P0_EXACT_ROLE.before-foreign-tenant-id-test.md | d637440befbc542427154b5481a90d2012d1633b | 147ae89788e07bf50ed453f392edf9b8a619dcc9 | MATCH |
| docs/backup/web-guru-exact-role-20261122/MANIFEST.md | 9755942010bd6c470a969144f8ebf24f574c369e | docs/backup/web-guru-exact-role-20261122/MANIFEST.md.before-report-checkpoint-reconcile | 9755942010bd6c470a969144f8ebf24f574c369e | 05247536b8c89a64a66e989b884ea3ccdc31e38c | MATCH |

Jumlah snapshot backup terdaftar: 8. Seluruh blob SHA yang tercantum: MATCH. Tidak ada snapshot lama yang ditimpa.

## Backup tambahan — isolasi tenant-context dan assignment test

| File sumber | Blob SHA source sebelum edit | Path backup | Blob SHA backup | Commit backup | Hasil |
|---|---|---|---|---|---|
| backend-laravel/tests/Feature/TeacherPortalFoundationTest.php | 9e5211a92294e96ed48a984171085a274ffec201 | docs/backup/web-guru-exact-role-20261122/TeacherPortalFoundationTest.php.before-tenant-context-test-order-fix | 9e5211a92294e96ed48a984171085a274ffec201 | 20d3814ef71949db3348693166552e756d1b792e | MATCH |
| docs/backup/web-guru-exact-role-20261122/MANIFEST.md | c990e3de769b632843f186a833a94fe2385eb6e0 | docs/backup/web-guru-exact-role-20261122/MANIFEST.md.before-tenant-context-test-order-fix | c990e3de769b632843f186a833a94fe2385eb6e0 | b6d163a722a1033a5312798d9550f79ac44a01e3 | MATCH |

Jumlah snapshot backup terdaftar: 10. Seluruh blob SHA yang tercantum: MATCH.

## Backup tambahan — final test/checkpoint reconciliation

| File sumber | Blob SHA source sebelum edit | Path backup | Blob SHA backup | Commit backup | Hasil |
|---|---|---|---|---|---|
| docs/LAPORAN_P0_EXACT_ROLE_IDENTITAS_LAPORAN_2026-11-22.md | 4be3201247639a9ff29db3b5ccb894470293fc77 | docs/backup/web-guru-exact-role-20261122/LAPORAN_P0_EXACT_ROLE.before-tenant-context-test-reorder.md | 4be3201247639a9ff29db3b5ccb894470293fc77 | f3123ecddc0790dfa63d3164c5ee7723eca08bdf | MATCH |
| docs/CHECKPOINT_P0_EXACT_ROLE_IDENTITAS_LAPORAN_2026-11-22.md | 804a046d1225d406db9360917f6197384608b165 | docs/backup/web-guru-exact-role-20261122/CHECKPOINT_P0_EXACT_ROLE.before-tenant-context-test-reorder.md | 804a046d1225d406db9360917f6197384608b165 | 549f738428dda01dd4d94471bdf0e4b8d70c5be5 | MATCH |
| docs/backup/web-guru-exact-role-20261122/MANIFEST.md | 2340c8f5a2dc7770b6be7b1068359e7c86d44719 | docs/backup/web-guru-exact-role-20261122/MANIFEST.md.before-final-test-reorder-reconcile | 2340c8f5a2dc7770b6be7b1068359e7c86d44719 | 8352a0be107aeca46ac9393b28379b06fc4ea269 | MATCH |

Jumlah snapshot backup terdaftar: 13. Seluruh blob SHA yang tercantum: MATCH.

## Backup tambahan — final branch/CI report reconciliation

| File sumber | Blob SHA source sebelum edit | Path backup | Blob SHA backup | Commit backup | Hasil |
|---|---|---|---|---|---|
| docs/LAPORAN_P0_EXACT_ROLE_IDENTITAS_LAPORAN_2026-11-22.md | ccafc38868d4b379148391506446657c826d3212 | docs/backup/web-guru-exact-role-20261122/LAPORAN_P0_EXACT_ROLE.before-final-head-and-CI-reconcile.md | ccafc38868d4b379148391506446657c826d3212 | a014d9974ccc7f32ca199f6012612c953aa7f560 | MATCH |
| docs/CHECKPOINT_P0_EXACT_ROLE_IDENTITAS_LAPORAN_2026-11-22.md | 140102fdf729f2cb82520c57da892d1d5f417bbd | docs/backup/web-guru-exact-role-20261122/CHECKPOINT_P0_EXACT_ROLE.before-final-head-and-CI-reconcile.md | 140102fdf729f2cb82520c57da892d1d5f417bbd | 14106f505ede0e5dff046e18e66649a18120e8a1 | MATCH |
| docs/backup/web-guru-exact-role-20261122/MANIFEST.md | 37c329641d86baec17c1313366effca23a24e088 | docs/backup/web-guru-exact-role-20261122/MANIFEST.md.before-final-head-and-CI-reconcile | 37c329641d86baec17c1313366effca23a24e088 | 7d76860e446d1b7315371a56902c34db7a3a1d78 | MATCH |

Jumlah snapshot backup terdaftar: 16. Seluruh blob SHA yang tercantum: MATCH.

## Backup tambahan — regresi post-mount permission/assignment/profile/user/tenant

| File sumber | Blob SHA source sebelum edit | Path backup | Blob SHA backup | Commit backup | Hasil |
|---|---|---|---|---|---|
| backend-laravel/tests/Feature/TeacherPortalFoundationTest.php | a0e9f03412c64e08e54922c6d5b8b49911915586 | docs/backup/web-guru-exact-role-20261122/TeacherPortalFoundationTest.php.before-post-mount-regressions | a0e9f03412c64e08e54922c6d5b8b49911915586 | fc78a504de9a2d0ee8959bd07ea9e2c91faf8d4e | MATCH |
| docs/backup/web-guru-exact-role-20261122/MANIFEST.md | a8260bb6ab8c4a6834fc2a15938183605f35c69b | docs/backup/web-guru-exact-role-20261122/MANIFEST.md.before-post-mount-regressions | a8260bb6ab8c4a6834fc2a15938183605f35c69b | 59fe4f85c790dfaf52185be45df56e916ff2485e | MATCH |

Jumlah snapshot backup terdaftar: 18. Seluruh blob SHA yang tercantum harus diverifikasi sebelum P0 dinyatakan selesai.
