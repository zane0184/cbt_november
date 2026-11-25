# Laporan Implementasi — Import Guru Web Admin
Tanggal: 2026-11-26

## Ringkasan
Implementasi Import Guru dibuat pada branch `feature/admin-teacher-import-20261126-r1`, dibuat dari source `work/guru-docx-import-e2e-20261125` @ `bc3d9a497a2775fcbeda9deed57ee05bcf53e2e8`; target PR telah diperbarui ke head branch terbaru @ `c6ce755f1ae9f65ed838817d06316ea4232c3b10` setelah satu commit upstream non-overlap masuk. Branch fitur sebelumnya #26 dibuat dari snapshot yang tertinggal dua commit; branch tersebut tidak digunakan sebagai dasar integrasi dan PR #26 ditutup sebagai superseded. Perubahan fitur dipindahkan secara selektif ke branch baru sehingga dua commit terbaru tetap dipertahankan.

## AUDIT
- Pada source base terbaru tidak ditemukan komponen, service, route, view, migration batch, atau feature test khusus Import Guru.
- Import Siswa memiliki pola preview, validasi, transaksi batch, idempotensi, audit log, dan pembatasan akses Admin Madrasah.
- Pembuatan akun Guru manual pada `UserManagement` menetapkan aturan identitas: PNS/PPPK memakai NIP 18 digit; Honorer memakai NIK 16 digit; NUPTK opsional 16 digit.
- `TeacherProfile` memiliki kolom `employment_status`, `nip`, `nik`, `nuptk`, `employee_identifier`, `gender`, dan `phone`. Constraint login identity dan unique tenant-scoped sudah tersedia pada source base.
- Pemeriksa paket XLSX yang ada hanya menerima satu worksheet. Template Guru dibuat satu worksheet dan parser XLSX membaca kolom sampai batas pemeriksa agar kolom ekstra dapat ditolak, bukan diabaikan.

## DESAIN
- Fitur ditempatkan di Manajemen Pengguna melalui tombol Import Guru XLSX/CSV, sejajar dengan Import Siswa.
- Kolom: `nama`, `status_kepegawaian`, `nip`, `nik`, `nuptk`, `jenis_kelamin`, `telepon`, `email`.
- Preview wajib sebelum konfirmasi. Semua baris harus valid; satu kesalahan membatalkan seluruh batch.
- Setiap baris membuat akun role tepat `GURU` dan satu `TeacherProfile`. Password awal dibuat acak dan tidak diekspor; Admin menerbitkan kredensial melalui Reset Password.
- Maksimal 5.000 baris dan 10 MiB per file. XLSX memakai preflight paket yang ada; CSV/XLSX menolak kolom tak dikenal, identitas duplikat, format identitas salah, dan formula XLSX.
- Isolasi mengikuti model satu instalasi/database per madrasah dan tenant context. Tidak ada koneksi akademik lintas madrasah, perubahan CBT Engine, atau perubahan kurikulum.

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

Backup file asli:
- `docs/backup/TEACHER_IMPORT_20261126/web.php.before`
- `docs/backup/TEACHER_IMPORT_20261126/user-management.blade.php.before`
- Manifest: `docs/backup/TEACHER_IMPORT_20261126/MANIFEST.md`

Migration menambahkan tabel `teacher_import_batches`, dengan unique idempotency key per instalasi, foreign key uploader dan tenant. Migration hanya ditambahkan ke source; belum dijalankan pada VPS atau database produksi.

## PENGUJIAN
- Feature tests ditambahkan untuk template XLSX, preview dan import PNS/Honorer, validasi identitas, duplikasi, idempotensi, akses non-Admin, pemanggilan service langsung, rollback ketika audit gagal, dan round-trip XLSX satu worksheet.
- Pemeriksaan GitHub mengonfirmasi file route, tombol, component, service, migration, view, dan test berada pada branch fitur.
- Perbandingan source dilakukan terhadap base PR terbaru `c6ce755f1ae9f65ed838817d06316ea4232c3b10` (branch fitur tertinggal satu commit non-overlap; PR terdeteksi mergeable), bukan snapshot lama.
- **Belum dijalankan:** PHPUnit, PHP lint, GitHub Actions, browser test, dan uji instalasi VPS. Fitur belum boleh dinyatakan lulus atau siap produksi.
- Tidak ada migration yang dijalankan pada database produksi.

## STATUS DAN LANGKAH BERIKUTNYA
Status: implementasi awal tersedia di branch fitur baru; PR draft menunggu review dan CI. Setelah CI hijau, lakukan review keamanan dan uji manual pada database disposable. Deployment/VPS hanya setelah persetujuan terpisah, backup produksi, dan preflight.
