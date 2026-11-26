# Addendum — Penghapusan Kolom Email Import Guru
Tanggal: 2026-11-26

## AUDIT
- File template Import Guru sebelumnya memiliki delapan kolom termasuk `email`.
- File XLSX yang dilaporkan pengguna memiliki external relationship `mailto:` pada kolom email; pemeriksaan `XlsxPackagePreflight` menolaknya.
- Email tidak digunakan sebagai identitas login Guru. Identitas login tetap NIP 18 digit untuk PNS/PPPK dan NIK 16 digit untuk Honorer.
- Akun Guru dapat dibuat tanpa mengisi email; user model dan alur pembuatan Admin yang diuji juga mengizinkan email kosong.

## DESAIN
- Hapus email dari template XLSX dan CSV, daftar header yang diterima, proses validasi, pemeriksaan duplikasi email, dan payload pembuatan akun Guru.
- Batasi import Guru menjadi tujuh kolom.
- Pertahankan validasi NIP/NIK/NUPTK, tenant, role, transaksi, idempotensi, dan audit log.
- Validator keamanan XLSX tidak dilonggarkan; file template baru tidak membuat hyperlink email yang menjadi pemicu.

## IMPLEMENTASI
- `backend-laravel/app/Services/TeacherImportService.php`: email dihapus dari header, parsing, validasi, duplikasi, payload akun; batas kolom menjadi 7.
- `backend-laravel/app/Livewire/Admin/TeacherImport.php`: template XLSX dan CSV menjadi 7 kolom; rentang style dan autofilter disesuaikan A:G.
- `backend-laravel/resources/views/livewire/admin/teacher-import.blade.php`: petunjuk kolom email dihapus.
- `backend-laravel/tests/Feature/TeacherImportTest.php`: fixture CSV import Guru diperbarui tanpa kolom email.
- Backup sebelum perubahan:
  - `backend-laravel/backups/remove-teacher-email-column-20261126/TeacherImportService.php.before`
  - `backend-laravel/backups/remove-teacher-email-column-20261126/TeacherImport.php.before`
  - `backend-laravel/backups/remove-teacher-email-column-20261126/teacher-import.blade.php.before`
  - `backend-laravel/backups/remove-teacher-email-column-20261126/TeacherImportTest.php.before`
- Commit implementasi kode: `3f60a399a24465fa006d32dd8c2d2f85fbd94e68`.

## PENGUJIAN
- Perubahan telah diperiksa ulang pada source GitHub: template XLSX/CSV berisi tujuh header, parser membatasi tujuh kolom, dan UI tidak lagi menyebut email.
- GitHub Actions pada commit kode `3f60a399a24465fa006d32dd8c2d2f85fbd94e68` mulai berjalan; status akhir CI belum tersedia saat addendum ini ditulis.
- Tidak ada perubahan atau migration pada VPS/database produksi.
- File XLSX lama yang memiliki hyperlink email tetap akan ditolak oleh validator lama/ketat. Setelah source ini terpasang, Admin harus mengunduh template baru lalu menyalin data tanpa kolom email.
