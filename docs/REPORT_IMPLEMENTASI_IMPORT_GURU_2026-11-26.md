# Laporan Implementasi — Import Guru Web Admin
Tanggal: 2026-11-26

## Ringkasan
Fitur Import Guru ditambahkan pada branch terpisah `feature/admin-teacher-import-20261126`, berbasis source aktif `work/guru-docx-import-e2e-20261125` pada commit `b451989b1ca2ac39b4ea16746914f6d28d1169a8`. Source branch aktif tidak ditimpa dan belum ada merge ke branch kerja.

## AUDIT
- Pada source dasar tidak ditemukan komponen, service, route, view, migration batch, atau feature test khusus Import Guru.
- Import Siswa telah memiliki pola preview, validasi, transaksi batch, idempotensi, audit log, dan pembatasan akses Admin Madrasah.
- Pembuatan akun Guru manual pada `UserManagement` menetapkan aturan identitas: PNS/PPPK memakai NIP 18 digit; Honorer memakai NIK 16 digit; NUPTK opsional 16 digit.
- `TeacherProfile` memiliki kolom `employment_status`, `nip`, `nik`, `nuptk`, `employee_identifier`, `gender`, dan `phone`. Constraint login identity dan unique tenant-scoped sudah tersedia pada source dasar.
- Pemeriksa paket XLSX yang ada hanya menerima satu worksheet. Template Guru disesuaikan menjadi satu worksheet agar tidak menghasilkan file template yang ditolak oleh pemeriksa yang sama.

## DESAIN
- Fitur ditempatkan di Manajemen Pengguna melalui tombol Import Guru XLSX/CSV, sejajar dengan Import Siswa.
- File memuat kolom: `nama`, `status_kepegawaian`, `nip`, `nik`, `nuptk`, `jenis_kelamin`, `telepon`, `email`.
- Preview wajib dilakukan sebelum konfirmasi. Semua baris harus valid; satu kesalahan membatalkan seluruh batch.
- Setiap baris membuat satu akun dengan role tepat `GURU` dan satu `TeacherProfile`. Password awal dibuat acak dan tidak diekspor; Admin menggunakan Reset Password yang sudah ada untuk menerbitkan kredensial.
- Maksimal 5.000 baris dan 10 MiB per file. XLSX memakai pemeriksaan paket yang sudah ada; CSV/XLSX menolak kolom tak dikenal, identitas duplikat, format identitas salah, dan formula XLSX.
- Isolasi mengikuti satu instalasi/database per madrasah dan tenant context yang sudah ada. Tidak ada integrasi lintas madrasah, tidak ada perubahan CBT Engine atau kurikulum.

## IMPLEMENTASI
File source baru:
- `backend-laravel/app/Services/TeacherImportService.php`
- `backend-laravel/app/Livewire/Admin/TeacherImport.php`
- `backend-laravel/resources/views/livewire/admin/teacher-import.blade.php`
- `backend-laravel/database/migrations/2026_11_26_010001_create_teacher_import_batches_table.php`
- `backend-laravel/tests/Feature/TeacherImportTest.php`

File source yang diubah:
- `backend-laravel/routes/web.php`: route bernama `admin.teachers.import`.
- `backend-laravel/resources/views/livewire/admin/user-management.blade.php`: tombol Import Guru.

Backup sebelum perubahan:
- `docs/backup/TEACHER_IMPORT_20261126/web.php.before`
- `docs/backup/TEACHER_IMPORT_20261126/user-management.blade.php.before`
- `docs/backup/TEACHER_IMPORT_20261126/TeacherImport.php.before-xlsx-single-sheet-fix`
- `docs/backup/TEACHER_IMPORT_20261126/TeacherImportService.php.before-xlsx-column-guard`
- `docs/backup/TEACHER_IMPORT_20261126/TeacherImportTest.php.before-xlsx-roundtrip-test`
- Manifest: `docs/backup/TEACHER_IMPORT_20261126/MANIFEST.md`

Migration menambahkan tabel batch `teacher_import_batches`, dengan unique idempotency key per instalasi, foreign key uploader dan tenant. Migration hanya ditambahkan ke source; belum dijalankan pada VPS atau database produksi.

## PENGUJIAN
- Feature tests ditambahkan untuk template XLSX, preview dan import PNS/Honorer, validasi identitas, duplikasi, idempotensi, akses non-Admin, pemanggilan service langsung, rollback ketika audit gagal, serta round-trip XLSX satu worksheet.
- Pemeriksaan source melalui GitHub mengonfirmasi seluruh file dan route baru tersedia pada branch fitur.
- Perbandingan GitHub dari base `b451989b1ca2ac39b4ea16746914f6d28d1169a8` ke head `4a7239954add699b46064fad8d6e7307f6efb8a4` menunjukkan 7 file source/test terkait fitur berubah/ditambahkan, ditambah dokumentasi dan backup.
- **Belum dijalankan:** PHPUnit, PHP lint, CI GitHub Actions, pengujian browser, dan uji instalasi di VPS. Karena itu fitur belum boleh dinyatakan lulus atau siap produksi.
- Tidak ada migration yang dijalankan pada database produksi dan tidak ada perubahan pada branch source dasar.

## STATUS DAN LANGKAH BERIKUTNYA
Status: implementasi awal tersedia di branch fitur; menunggu CI dan audit hasil tes. PR dibuat sebagai draft untuk pemeriksaan, tidak di-merge otomatis. Setelah CI hijau, lakukan review keamanan dan uji manual pada database disposable. Deployment/VPS hanya setelah persetujuan terpisah dan backup produksi yang diverifikasi.
