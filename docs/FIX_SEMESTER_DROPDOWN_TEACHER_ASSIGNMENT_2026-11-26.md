# Perbaikan Dropdown Semester Penugasan Guru
Tanggal: 2026-11-26
Branch: work/rombel-docx-completion-20261126

## AUDIT
- Screenshot halaman `/admin/teacher-assignments` memperlihatkan dropdown Semester tidak menampilkan teks pilihan.
- Model `App\\Models\\Semester` menyimpan atribut `term` dan `academic_year_id`; tidak memiliki atribut `name`.
- Komponen sebelumnya memuat semester tenant, tetapi Blade menampilkan `$semester->name`, sehingga label pilihan kosong.
- Aturan validasi penugasan sudah memeriksa semester berada pada tahun ajaran yang sama dengan rombel; aturan ini dipertahankan.

## DESAIN
- Label opsi ditampilkan sebagai `Semester Ganjil — 2026/2027` atau `Semester Genap — 2026/2027`.
- Eager-load relasi tahun ajaran untuk mencegah query berulang.
- Tidak mengubah skema, data semester, tahun ajaran, maupun aturan aktivasi semester.

## IMPLEMENTASI
- `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`: query semester eager-load relasi `academicYear`.
- `backend-laravel/resources/views/livewire/admin/teacher-subject-assignment-management.blade.php`: label dropdown menggunakan `term` dan nama tahun ajaran.
- `backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php`: menambahkan assertion bahwa label semester dan tahun ajaran tampil.
- Backup sebelum patch:
  - `backend-laravel/backups/fix-semester-dropdown-label-20261126/TeacherSubjectAssignmentManagement.php.before`
  - `backend-laravel/backups/fix-semester-dropdown-label-20261126/teacher-subject-assignment-management.blade.php.before`
  - `backend-laravel/backups/fix-semester-dropdown-label-20261126/TeacherSubjectAssignmentManagementTest.php.before`

## PENGUJIAN
- Source patch telah ditulis ke branch GitHub.
- Feature test ditambahkan, tetapi belum dinyatakan lulus sampai workflow CI pada commit terbaru selesai.
- Tidak ada migration, perubahan data, atau tindakan pada VPS/database produksi.
