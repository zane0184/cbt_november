# Supplemental Backup Manifest — Koreksi Sintaks Regression Test
Tanggal: 23 November 2026
Branch: `work/web-guru-remove-xlsx-20261123`

CI pertama untuk test baru gagal di tahap PHP lint karena namespace service pada test tertulis dengan double backslash. Sebelum koreksi sintaks, versi test yang gagal di-backup.

| File | Blob SHA sebelum koreksi | Backup path | Backup blob SHA | Hasil |
|---|---|---|---|---|
| `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php` | `b8eadcc30645f9c5f3c9c0c524707cac03f2f47c` | `.github/backups/web-guru-curriculum-cap-20261123/TeacherPortalFoundationTest.php.before-php-namespace-fix` | `b8eadcc30645f9c5f3c9c0c524707cac03f2f47c` | MATCH |

Hasil: **1/1 backup byte-exact MATCH**. Total backup untuk koreksi batas tampilan kurikulum: **3/3 file snapshot**, dengan dua source baseline awal 2/2 MATCH dan versi test gagal yang dikoreksi 1/1 MATCH.
