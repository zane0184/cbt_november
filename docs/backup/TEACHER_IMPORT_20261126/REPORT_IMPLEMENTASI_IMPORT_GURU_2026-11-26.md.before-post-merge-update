# Laporan Implementasi — Import Guru, Kredensial Sekali Tampil, dan Ekspor XLSX
Tanggal: 2026-11-26

## Ringkasan
Pekerjaan implementasi berada pada branch `feature/admin-teacher-import-20261126-r1`, PR #27. Cakupan mencakup penambahan Import Guru dan penyelesaian alur kredensial otomatis untuk Import Guru serta Import Siswa. Setelah impor sukses, Admin diarahkan ke halaman Manajemen Pengguna; halaman tersebut menampilkan daftar akun hasil impor beserta identitas login dan password awal satu kali, serta menyediakan unduhan kredensial hanya dalam format XLSX.

Source target `work/guru-docx-import-e2e-20261125` telah bergerak sampai commit `2dd2baaa891e01d2595df4f4aa586c1c9254f301`. Perbandingan terhadap common base menunjukkan perubahan upstream berfokus pada parser/import DOCX, view Bank Soal/Guru, test DOCX/Exam, dan backup. Tidak ada overlap pada source utama Import Guru/Import Siswa/Manajemen Pengguna; koreksi syntax upstream pada `ExamManagementHardeningTest.php` juga diselaraskan ke branch fitur.

## AUDIT
- Source Import Siswa sebelumnya sudah membuat password acak dan menyimpannya dalam hash, tetapi tidak mengembalikan password awal kepada Admin.
- Source login resmi menggunakan NISN untuk Siswa, NIP 18 digit untuk Guru PNS/PPPK, dan NIK 16 digit untuk Guru Honorer. `LoginIdentityResolver` mengikat identitas ke tenant instalasi lokal; username bukan fallback login Guru/Siswa.
- Password hash tidak dapat dibaca kembali. Karena itu, kredensial plaintext hanya tersedia pada hasil transaksi dan sesi Admin sekali tampil, bukan disimpan permanen di tabel atau audit log.
- Manajemen Pengguna sebelumnya belum mencari berdasarkan NIP/NIK/NISN.
- Template XLSX Import Siswa sebelumnya membuat dua worksheet, sedangkan `XlsxPackagePreflight` hanya menerima tepat satu worksheet. Kondisi ini menyebabkan template yang diunduh tidak sesuai dengan pemeriksaan keamanan saat upload.

## DESAIN
- Setelah batch berhasil, service mengembalikan daftar kredensial sekali tampil: nama, identitas login, jenis identitas, dan password awal.
- Password awal dibuat acak 24 karakter; database hanya menyimpan hash password. Kredensial tidak dicatat ke audit log.
- Komponen Import Guru/Siswa melakukan redirect ke `/admin/users` dengan flash session untuk menampilkan kredensial pada halaman daftar pengguna.
- Halaman Manajemen Pengguna otomatis memfilter role sesuai hasil impor (GURU atau SISWA), menampilkan seluruh kredensial batch dalam tabel yang dapat digulir, dan menyediakan tombol **Unduh Daftar Akun (.xlsx)**.
- XLSX memuat kolom `nama`, `identitas_login`, `jenis_identitas`, `password_awal`; nilai ditulis sebagai teks agar NIP/NIK/NISN dan password tidak berubah format.
- Tombol tutup menghapus tampilan kredensial dari state Livewire. Setelah ditutup atau sesi berakhir, password awal tidak dapat dipulihkan; Admin dapat menggunakan fitur Reset Password yang sudah ada untuk menerbitkan password baru.
- Pencarian Manajemen Pengguna mencakup nama, username, email, NIP, NIK, dan NISN.
- Format unduhan daftar kredensial hanya XLSX. CSV tetap dapat diterima sebagai format file masukan bila Admin memilihnya; bukan sebagai format hasil unduhan.
- Template Import Siswa diselaraskan menjadi satu worksheet Data Siswa; petunjuk pengisian dipindahkan ke halaman impor. Baris contoh harus diganti/dihapus sebelum upload.

## IMPLEMENTASI
File baru:
- `backend-laravel/app/Services/TeacherImportService.php`
- `backend-laravel/app/Livewire/Admin/TeacherImport.php`
- `backend-laravel/resources/views/livewire/admin/teacher-import.blade.php`
- `backend-laravel/database/migrations/2026_11_26_010001_create_teacher_import_batches_table.php`
- `backend-laravel/tests/Feature/TeacherImportTest.php`

File diubah:
- `backend-laravel/routes/web.php` — route Import Guru.
- `backend-laravel/resources/views/livewire/admin/user-management.blade.php` — tombol Import Guru dan panel hasil kredensial.
- `backend-laravel/app/Services/TeacherImportService.php` — password awal dan hasil kredensial.
- `backend-laravel/app/Livewire/Admin/TeacherImport.php` — redirect setelah impor.
- `backend-laravel/app/Services/StudentImportService.php` — hasil kredensial Siswa.
- `backend-laravel/app/Livewire/Admin/StudentImport.php` — redirect setelah impor dan perbaikan template XLSX satu worksheet.
- `backend-laravel/resources/views/livewire/admin/student-import.blade.php` — petunjuk hasil kredensial dan aturan satu worksheet.
- `backend-laravel/app/Livewire/Admin/UserManagement.php` — tampilan state sekali tampil, ekspor XLSX, hapus tampilan, filter role, pencarian identitas.
- `backend-laravel/tests/Feature/StudentImportTest.php` dan `TeacherImportTest.php` — tes kredensial, login Web/API, ekspor XLSX, dan template.
- `.github/workflows/tenant-backend-ci.yml` — trigger CI untuk branch fitur; benchmark PDF yang tidak terkait dilewati pada branch fitur.

Backup setiap file sebelum perubahan tersedia di `docs/backup/TEACHER_IMPORT_20261126/`. Daftar rinci seluruh backup: `docs/backup/TEACHER_IMPORT_20261126/ADDITIONAL_BACKUPS.md`; backup sumber utama tercatat di `MANIFEST.md`.

Migration `teacher_import_batches` hanya ditambahkan ke source. Tidak dijalankan pada VPS atau database produksi. Tidak ada perubahan CBT Engine atau model kurikulum.

## PENGUJIAN
<badge color="success">PASS — GitHub Actions Tenant Backend CI</badge>

- Workflow: [Tenant Backend CI — run #2305](https://github.com/zane0184/cbt_november/actions/runs/45709784302)
- Commit yang diuji: `4533367434593304a700f66c855d603af5370cc5`.
- PHP 8.3 lint seluruh aplikasi, migration, dan test: **PASS**.
- Isolasi database uji, Composer lock/dependencies, dan pemeriksaan migration/login identity pada MySQL 8.0.46 disposable: **PASS**.
- PHPUnit lengkap pada MySQL 8.0.46 disposable: **678 tests passed, 3.564 assertions, 187,57 detik**.
- Tes mencakup login Web dan API untuk Siswa (NISN), Guru ASN (NIP), dan Guru Honorer (NIK), pemeriksaan Hash password, tampilan kredensial sekali tampil, unduhan XLSX, serta preflight template Import Siswa satu worksheet.
- Tidak ada pengujian terhadap database produksi dan tidak ada migration produksi yang dijalankan.
- Tidak ada pengujian ulang VPS pada tahap ini.

## STATUS DAN LANGKAH BERIKUTNYA
Implementasi dan CI backend pada branch fitur **lulus**. PR #27 masih perlu integrasi ke source branch. Migration `teacher_import_batches` belum dijalankan di VPS/produksi; deployment/migration memerlukan prosedur backup, preflight, dan persetujuan tersendiri.
