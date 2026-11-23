# Backup Manifest — Koreksi Batas Tampilan Kurikulum
Tanggal: 23 November 2026
Branch: `work/web-guru-remove-xlsx-20261123`

Sebelum mengubah batas tampilan outcome kurikulum dan menambah regression test, kedua file berikut di-backup.

| File | Source blob SHA sebelum perubahan | Backup path | Backup blob SHA | Hasil |
|---|---|---|---|---|
| `backend-laravel/app/Services/TeacherPortal/TeacherCurriculumContextService.php` | `75c23983065f3fc871e5f4211a7cbf36e7db944f` | `.github/backups/web-guru-curriculum-cap-20261123/backend-laravel/app/Services/TeacherPortal/TeacherCurriculumContextService.php.before` | `75c23983065f3fc871e5f4211a7cbf36e7db944f` | MATCH |
| `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php` | `68a1ef4b896c7747bbd4a756a4f89be151257873` | `.github/backups/web-guru-curriculum-cap-20261123/backend-laravel/tests/Feature/TeacherPortalFoundationTest.php.before` | `68a1ef4b896c7747bbd4a756a4f89be151257873` | MATCH |

Hasil: **2/2 backup byte-exact MATCH**. Backup hanya menyalin source sebelum perubahan; tidak ada file baseline ZIP atau DB produksi yang disentuh.
