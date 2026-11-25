# Laporan Implementasi — Import Guru, Kredensial Sekali Tampil, dan Ekspor XLSX
Tanggal: 2026-11-26

## Ringkasan
Pekerjaan pada branch `feature/admin-teacher-import-20261126-r1`, PR #27. Cakupan diperluas dari penambahan Import Guru menjadi penyelesaian alur kredensial Import Guru dan Import Siswa. Setelah impor sukses, Admin diarahkan ke halaman Manajemen Pengguna; halaman tersebut menampilkan daftar akun yang baru dibuat beserta identitas login dan password awal, serta menyediakan unduhan kredensial hanya dalam format XLSX.

Source branch target bergerak dari common base `bc3d9a497a2775fcbeda9deed57ee05bcf53e2e8` dengan tiga commit tambahan yang hanya menyentuh parser/import DOCX, view Bank Soal/Guru, test DOCX/Exam, dan backup. Tidak ada file overlap dengan file utama Import Guru/Import Siswa/Manajemen Pengguna. Perbandingan terbaru harus diperiksa lagi sebelum merge.

## AUDIT
- Source Import Siswa sebelumnya sudah membuat password acak dan menyimpannya dalam hash, tetapi tidak mengembalikan password awal kepada Admin. Admin hanya diberi arahan melakukan Reset Password satu akun demi satu akun.
- Source login resmi menggunakan NISN untuk Siswa, NIP 18 digit untuk Guru PNS/PPPK, dan NIK 16 digit untuk Guru Honorer. `LoginIdentityResolver` mengikat identitas ke tenant instalasi lokal; username bukan fallback login Guru/Siswa.
- Password hash tidak dapat dibaca kembali. Karena itu, kredensial plaintext hanya boleh tersedia pada hasil transaksi dan sesi Admin sekali tampil, bukan disimpan permanen di tabel atau audit log.
- Manajemen Pengguna sebelumnya menampilkan data Guru/Siswa, tetapi pencarian belum mencakup NIP/NIK/NISN.

## DESAIN
- Setelah batch berhasil, setiap service mengembalikan daftar kredensial sekali tampil: nama, identitas login, jenis identitas, dan password awal.
- Password awal dibuat acak 24 karakter; database hanya menyimpan hash password. Kredensial tidak dicatat ke audit log.
- Komponen Import Guru/Siswa melakukan redirect ke `/admin/users` dengan flash session untuk menampilkan kredensial pada halaman daftar pengguna.
- Halaman Manajemen Pengguna otomatis memfilter role sesuai hasil impor (GURU atau SISWA), menampilkan semua kredensial batch dalam tabel yang dapat digulir, dan menyediakan tombol **Unduh Daftar Akun (.xlsx)**.
- XLSX memuat kolom `nama`, `identitas_login`, `jenis_identitas`, `password_awal`; nilai ditulis sebagai teks agar NIP/NIK/NISN dan password tidak berubah format.
- Tombol tutup menghapus tampilan kredensial dari state Livewire. Setelah ditutup atau sesi berakhir, password awal tidak dapat dipulihkan; Admin dapat menggunakan fitur Reset Password yang sudah ada untuk membuat password baru.
- Pencarian Manajemen Pengguna mencakup nama, username, email, NIP, NIK, dan NISN.
- Format unduhan daftar kredensial hanya XLSX. CSV tetap dapat diterima sebagai format file masukan bila Admin memilihnya; bukan sebagai format hasil unduhan kredensial.

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
- `backend-laravel/app/Livewire/Admin/StudentImport.php` — redirect setelah impor.
- `backend-laravel/resources/views/livewire/admin/student-import.blade.php` — petunjuk hasil kredensial.
- `backend-laravel/app/Livewire/Admin/UserManagement.php` — tampilan state sekali tampil, ekspor XLSX, hapus tampilan, filter role, pencarian identitas.
- `backend-laravel/tests/Feature/StudentImportTest.php` dan `TeacherImportTest.php` — tes kredensial, login, dan ekspor XLSX.
- `.github/workflows/tenant-backend-ci.yml` — trigger CI ditambahkan untuk branch fitur ini agar test suite backend MySQL 8 berjalan.

Backup sebelum perubahan tersimpan di `docs/backup/TEACHER_IMPORT_20261126/`, termasuk file service, Livewire, view, test, workflow, laporan, dan checkpoint. Manifest awal: `docs/backup/TEACHER_IMPORT_20261126/MANIFEST.md`; backup lanjutan memakai suffix `before-one-time-credentials`, `before-login-credential-tests`, `before-web-login-assertions`, `before-search-and-filter-ux`, dan `before-final-credential-docs`.

Migration `teacher_import_batches` hanya ditambahkan pada source. Tidak dijalankan pada VPS atau database produksi. Tidak ada perubahan CBT Engine atau model kurikulum.

## PENGUJIAN
- Ditambahkan tes bahwa kredensial Siswa mengarah ke user yang benar melalui `LoginIdentityResolver`, password cocok dengan hash database, dan login Web berhasil menuju portal Siswa.
- Ditambahkan tes bahwa kredensial Guru ASN (NIP) dan Honorer (NIK) mengarah ke akun yang benar, password cocok dengan hash database, dan login Web Guru berhasil.
- Ditambahkan tes unduhan `kredensial-import-siswa.xlsx` dan `kredensial-import-guru.xlsx` dari halaman Manajemen Pengguna.
- Tes yang sudah ada untuk preview, validasi, role, tenant, duplikasi, idempotensi, audit rollback, dan template XLSX tetap dipertahankan.
- GitHub Actions `Tenant Backend CI` telah dipicu untuk branch fitur. Hasil final run harus dibaca sebelum fitur dinyatakan lulus; status PASS belum boleh diklaim sebelum job selesai.
- PHPUnit dan PHP lint belum dinyatakan lulus pada saat laporan ini ditulis.
- Tidak ada pengujian terhadap database produksi dan tidak ada migration produksi yang dijalankan.

## STATUS DAN LANGKAH BERIKUTNYA
Status: implementasi kredensial sekali tampil dan XLSX sudah masuk ke branch fitur; PR #27 tetap draft dan belum merge. Lanjutkan dengan membaca hasil CI MySQL 8, memperbaiki kegagalan bila ada, memeriksa diff final terhadap source branch terkini, lalu memperbarui checkpoint. Jangan menyatakan fitur selesai sebelum CI dan alur redirect/tampilan/XLSX/login terbukti lulus.
