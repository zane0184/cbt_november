# Supplemental Backup Manifest — Test Corrections
Tanggal: 23 November 2026
Branch: `work/web-guru-remove-xlsx-20261123`

Dua file test tambahan perlu diperbaiki setelah CI mengungkap:
1. Assertion route lama yang masih mengharapkan route import XLSX.
2. Fixture test versioning yang menggunakan SHORT_ANSWER, padahal tipe tersebut sengaja masih BLOCKED.

Keduanya di-backup sebelum koreksi dan diverifikasi byte-exact terhadap baseline source SHA.

| File | Source blob SHA | Backup path | Backup blob SHA | Hasil |
|---|---|---|---|---|
| `backend-laravel/tests/Feature/QuestionBankTest.php` | `c55f217f14bfc4877d8e0fdc6c6f423c3ea697ec` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/tests/Feature/QuestionBankTest.php.before` | `c55f217f14bfc4877d8e0fdc6c6f423c3ea697ec` | MATCH |
| `backend-laravel/tests/Feature/TeacherResultNavigationTest.php` | `1d541ed6a8b27dee7abaded4026cd9a93db1b2fa` | `.github/backups/web-guru-remove-xlsx-20261123/backend-laravel/tests/Feature/TeacherResultNavigationTest.php.before` | `1d541ed6a8b27dee7abaded4026cd9a93db1b2fa` | MATCH |

Hasil tambahan: **2/2 MATCH**. Total backup pada tahap penghapusan XLSX dan koreksi test: **14/14 MATCH**.

Perubahan test:
- `TeacherResultNavigationTest` kini memastikan route Bank Soal tetap ada dan route import/template XLSX tidak lagi terdaftar.
- `QuestionBankTest` menggunakan ESSAY untuk menguji versioning immutable. SHORT_ANSWER tetap diblokir dan tetap diuji secara terpisah pada kontrak import API.
