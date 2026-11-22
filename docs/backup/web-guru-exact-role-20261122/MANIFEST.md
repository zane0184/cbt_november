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
