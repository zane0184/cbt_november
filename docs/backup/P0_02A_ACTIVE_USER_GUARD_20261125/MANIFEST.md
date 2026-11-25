# Backup Manifest — P0-02A Active User Guard Fix

Tanggal: 2026-11-25

| Source | Backup path | UTF-8 bytes | Git blob SHA-1 | SHA-256 |
|---|---|---:|---|---|
| `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php` | `backend-laravel/backup_before_patch/P0_02A_ACTIVE_USER_GUARD_20261125/TeacherSubjectAssignmentManagement.php.before` | 14,806 | `9d076387c1e81a4b229b0358911a4f9133085a86` | `cb19390aedfc4685b18c9b8fe29c812ebabd4f480a7b6fec7a6245d2280d52b6` |
| `backend-laravel/tests/Feature/P002ANonCpAcademicAcceptanceTest.php` | `backend-laravel/backup_before_patch/P0_02A_ACTIVE_USER_GUARD_20261125/P002ANonCpAcademicAcceptanceTest.php.before` | 31,962 | `a0a582f3f937358e3570fb2b88c71a77c0aa3429` | `1d3107119672f930a93f715a92cff3a5e2c61298e50e24d19e24926ba6fb736d` |

Backup commits:
- Source: `88a7783295dadde03d7249529fc0e994b4430f05`
- Test: `6d905e75ce4aedff9662e0ace2b65e8a23752933`

Verifikasi fetch ulang dilakukan untuk kedua pasangan source/backup. Konten identik byte-for-byte; ukuran dan Git blob SHA-1 sama. SHA-256 dihitung dengan implementasi yang divalidasi terhadap known vector `SHA256("test") = 9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08`.
