# Laporan Implementasi — Perbaikan Admin, Password, Mulok, Kurikulum & Import Siswa
Tanggal: 26 November 2026

Repository: `zane0184/cbt_november`
Source base: `work/rombel-docx-completion-20261126`
Base SHA: `841a1dd90dbe0277026fade1511a39b5dc9216b6`
Branch perubahan: `work/admin-import-curriculum-fixes-20261126`
Code changeset HEAD: `0ca76d0d5c620da37006d69e52ddcdc344391db3` (sebelum commit dokumentasi). Branch/PR head setelah laporan dan checkpoint: `6e1a394a4c80f429376387fb8bf4ad6d7a126b0a`.
PR: [#34 — Fix Admin semester, Mulok, account passwords, user tabs and curriculum visibility](https://github.com/zane0184/cbt_november/pull/34)
Status PR: OPEN / DRAFT / BELUM MERGED; GitHub melaporkan mergeable=true pada pemeriksaan terakhir.

## AUDIT

### 1. Semester pada Penugasan Guru
- Perbaikan dropdown semester sebelumnya sudah ada di source: label dropdown menggunakan `semester.term` dan `academicYear.name`, serta dropdown difilter mengikuti tahun ajaran Rombel.
- Gap yang tersisa berada pada tabel daftar penugasan: Blade membaca `$assignment->semester->name`, padahal model Semester tidak memiliki atribut `name`; model memakai `term`.
- Ini yang diperbaiki pada patch sekarang. Tidak mengulang patch dropdown sebelumnya.

### 2. Password
- Validasi password Admin pada proses installer sebelumnya masih mensyaratkan 14 karakter.
- Validasi akun pada halaman Pengguna & Akun juga mensyaratkan 14 karakter.
- Password sementara hasil import Guru/Siswa dan reset akun dibuat 24 karakter.
- Template import Guru/Siswa tidak menerima password dari kolom file; sistem membuat password awal secara acak dan menampilkannya satu kali. Instruksi UI sebelumnya tidak menjelaskan panjang password tersebut.

### 3. Mulok
- Kategori Mulok dan alur tambah sudah ada pada source sebelumnya.
- Belum ada tombol/form/method edit Mulok tenant; mata pelajaran registry/wajib tidak boleh diedit oleh Admin.
- Migration `2026_11_26_000001_add_subject_category_for_local_content.php` sudah ada dari pekerjaan sebelumnya. Migration ini menambah kolom `subjects.subject_category`; belum dibuktikan telah diterapkan pada database VPS.

### 4. Pengguna & Akun
- Route dan Livewire component `/admin/users` sudah merupakan satu halaman gabungan.
- UI filter sebelumnya berupa dropdown peran; diperjelas menjadi tab Semua Akun, Guru, dan Siswa pada halaman yang sama.
- Import Guru dan Import Siswa tetap memakai service masing-masing; daftar akun tidak dipecah menjadi halaman daftar Guru dan halaman daftar Siswa.

### 5. CP/TP dan kurikulum terpadu
- Route `/admin/curriculum` dan komponen lama sudah ada, tetapi link navigasi Admin belum ditampilkan.
- Tampilan lama hanya membaca `LearningOutcome` legacy, bukan menampilkan CP resmi versi terdaftar dan TP/ATP Guru dari model canonical.
- Catatan P0-02 di GitHub menyatakan isi CP resmi belum tersedia sebagai artefak asli yang dapat diarsipkan dan diverifikasi checksum. Kepdirjen Pendis 9941/2025 masih PENDING; PDF asli BSKAP 046/H/KR/2025 dan naskah/lampiran BKPDM 020/2026 juga belum diarsipkan serta diverifikasi pada source.
- Karena itu, konten CP resmi tidak boleh dibuat dari asumsi atau data sintetis. TP/ATP juga tidak dapat diproduksi secara sah sebelum CP induk terverifikasi.

### 6. Import Siswa dan Rombel
- Import sebelumnya mewajibkan Admin memilih satu Rombel yang sudah ada di luar template.
- Template hanya berisi identitas siswa dan data opsional; belum membawa metadata Rombel.
- Rombel, tahun pelajaran, dan tingkat kelas harus tetap tervalidasi terhadap tenant madrasah. Pembuatan Rombel harus satu transaksi dengan batch import agar kegagalan tidak meninggalkan Rombel/akun parsial.

## DESAIN

1. Satu halaman Pengguna & Akun dengan tab Semua Akun/Guru/Siswa; bukan dua halaman daftar terpisah.
2. Password Admin minimum 8 karakter. Password awal Import Guru/Siswa dan reset akun dibuat acak 8 karakter, ditampilkan satu kali, dan database tetap menyimpan hash.
3. Tambah Mulok tetap memakai kategori `MULOK`; Admin dapat mengubah hanya Mulok milik tenant. Registry resmi dan mata pelajaran wajib tetap terlindungi. Setiap create/update dicatat audit.
4. Halaman Admin CP & TP adalah baca-saja:
   - CP hanya ditampilkan jika official, ACTIVE, non-editable, berada pada versi yang didaftarkan, dan checksum CP cocok dengan checksum artefak sumber yang berstatus `ARTIFACT_CHECKSUM_VERIFIED`.
   - TP/ATP lokal Guru ditampilkan dengan relasi CP induk, mapel, penugasan, Rombel, tahun pelajaran dan semester.
   - KBC/Panca Cinta, Pembelajaran Mendalam, dan dimensi profil lulusan ditampilkan dalam satu kerangka bersama Kurikulum Merdeka.
   - Tidak membuat CP sintetis atau mengubah gate CP PENDING.
5. Template Import Siswa baru memuat `rombel`, `tahun_pelajaran`, `kelas`. Satu file hanya untuk satu Rombel. Preview memastikan metadata konsisten dan valid. Saat konfirmasi:
   - gunakan Rombel yang sudah cocok jika ada;
   - buat Rombel baru jika belum ada;
   - buat batch, Rombel, akun, profil dan pivot keanggotaan dalam satu transaksi.
   - Tahun pelajaran dan master tingkat kelas harus sudah ada pada instalasi madrasah.

## IMPLEMENTASI

### File aplikasi yang diubah
- `app/Livewire/Admin/TeacherSubjectAssignmentManagement.php` tidak diubah; label daftar assignment diperbaiki di Blade.
- `resources/views/livewire/admin/teacher-subject-assignment-management.blade.php`: tabel assignment memakai `semester.term` dan menampilkan Ganjil/Genap.
- `app/Livewire/Admin/AcademicManagement.php`: method edit Mulok tenant, validasi unique per tenant, lock transaksi, audit `SUBJECT_CREATED` dan `SUBJECT_UPDATED`.
- `resources/views/livewire/admin/academic-management.blade.php`: aksi Ubah Mulok dan modal create/edit.
- `app/Livewire/Admin/UserManagement.php` dan `resources/views/livewire/admin/user-management.blade.php`: tab Semua Akun/Guru/Siswa dan minimum password 8.
- `app/Http/Controllers/InstallerController.php`, `app/Services/InstallerService.php`, `resources/views/installer/admin.blade.php`: validasi dan UI password Admin minimum 8.
- `app/Services/StudentImportService.php`: metadata Rombel template, validasi satu Rombel/file, idempotency berbasis metadata+file hash, resolusi/pembuatan Rombel tenant-scoped di transaksi, fail-closed bila target tidak dapat dipastikan, password acak 8 karakter.
- `app/Livewire/Admin/StudentImport.php` dan `resources/views/livewire/admin/student-import.blade.php`: template XLSX/CSV memuat rombel/tahun_pelajaran/kelas; dropdown Rombel lama menjadi fallback opsional; status preview menjelaskan apakah Rombel akan dibuat atau dipakai.
- `app/Services/TeacherImportService.php` dan `resources/views/livewire/admin/teacher-import.blade.php`: password awal acak 8 karakter dan penjelasan UI.
- `app/Services/UserAccountService.php`: password reset acak 8 karakter.
- `app/Livewire/Admin/CurriculumManagement.php` dan `resources/views/livewire/admin/curriculum-management.blade.php`: halaman Admin canonical CP/TP/ATP, status provenance, KBC/Panca Cinta, Pembelajaran Mendalam dan dimensi profil lulusan.
- `resources/views/layouts/app.blade.php`: link navigasi Admin ke CP & TP Kurikulum.
- Regression tests ditambah/disesuaikan pada StudentImportTest, TeacherImportTest, UserAccountLifecycleTest, AcademicYearManagementTest, dan TeacherSubjectAssignmentManagementTest.

### Backup
Backup sebelum perubahan disimpan di:
`backend-laravel/backups/user-request-fixes-20261126/`

Manifest lengkap:
`backend-laravel/backups/user-request-fixes-20261126/MANIFEST.md`

Seluruh file yang diedit memiliki salinan `.before` dengan blob SHA sumber. ZIP baseline, source branch dasar, dan database produksi tidak diubah.

### Database dan VPS
- Tidak ada migration baru.
- Migration kategori Mulok yang sudah ada dari tahap sebelumnya tidak dijalankan.
- Tidak ada koneksi, perubahan source, atau perubahan database pada VPS.

## PENGUJIAN

Regression coverage yang ditambahkan/disesuaikan:
- Import Siswa membuat Rombel otomatis dari template dan menautkan Siswa.
- Edit Mulok tenant dan audit log.
- Password awal Import Guru dan Siswa tepat 8 karakter.
- Password reset tepat 8 karakter.
- Label Semester Ganjil tampil pada daftar penugasan Guru.

Status eksekusi PHP runtime:
- **PASS — GitHub Actions run 45759006825**: [bukti run](https://github.com/zane0184/cbt_november/actions/runs/45759006825).
- Runner: PHP **8.3.37**, Composer **2.11.3**, Laravel **13.42.0**.
- `composer validate --strict --no-check-publish`: PASS (`composer.json is valid`).
- `composer install` dari lock file: PASS; seluruh dependency berhasil dipasang.
- PHP syntax lint: **310 file** di direktori `app`, `bootstrap`, `config`, `database`, dan `routes`: PASS, tidak ada syntax error.
- `php artisan package:discover --ansi`: PASS.
- `php artisan --version`: PASS, terdeteksi Laravel Framework 13.42.0.
- `php artisan route:list --json`: PASS. Route Admin yang diverifikasi: `admin/users`, `admin/students/import`, `admin/teachers/import`, `admin/teacher-assignments`, `admin/curriculum`, dan `admin/academic`.
- Runtime dijalankan pada runner terisolasi dengan environment testing dan SQLite memory; tidak melakukan migration atau menulis database produksi.
- PHPUnit: **TIDAK DIJALANKAN**.
- Full CI/test suite: **TIDAK DIJALANKAN**.
- Browser UAT: belum dijalankan.
- Migration/VPS/DB produksi: tidak dijalankan/tidak diakses.

Runtime run menggunakan source aplikasi PR #34 pada saat itu (kode aplikasi sama dengan changeset `46f343ab3861af361bed785c41d22716999973a0`); commit harness saat run: `712822c1a1768922a908586a4daf5ca40eb60062`, merge-checkout runner: `eac50667ddf5d04a8cb260f3f9f7a356a4dff53a`. Setelah run, workflow runtime disimpan pada branch PR agar dapat digunakan untuk integrasi berikutnya.

## STATUS / BATASAN

**SOURCE PATCH DITULIS — PHP RUNTIME BOOT PASS — REGRESSION TEST DITAMBAHKAN TETAPI TIDAK DIEKSEKUSI — PR DRAFT BELUM MERGED.**

Hal yang masih memerlukan tindak lanjut:
1. Jalankan migration Mulok yang sudah ada hanya setelah backup database VPS, preflight, pemeriksaan migration ledger dan persetujuan.
2. Uji keenam permintaan pada VPS menggunakan source PR #34 setelah disetujui/diintegrasikan.
3. CP resmi tetap BLOCKED sampai artefak PDF asli dan lampiran yang sah diunggah ke GitHub, diarsipkan dan checksum-nya diverifikasi. Halaman Admin sekarang menampilkan status dan tidak mengarang konten.
4. Setelah uji VPS, catat hasil PASS/FAIL per fitur sebelum membuat ZIP distribusi baru.


## Addendum ADM-04 — Perbaikan Upload Branding Web Admin (26 November 2026)

### AUDIT
Masalah ditemukan pada dua lapisan. Livewire menolak logo jika dimensinya di luar rentang 64–2000 px dan menolak favicon yang belum persegi 32–256 px. Layanan penyimpanan mengulangi aturan dimensi yang sama. Karena itu, file PNG yang valid bisa gagal hanya karena ukuran pixel, padahal Admin seharusnya cukup mengunggah file sesuai format dan batas byte.

### DESAIN
- Logo aplikasi dan logo kop dokumen menerima PNG/JPG/JPEG/WebP maksimal 2 MB. Server mempertahankan rasio dan menyesuaikan agar masuk dalam batas 1600×1200 px. Tidak ada crop dan tidak ada upscaling untuk gambar kecil.
- Favicon menerima PNG/WebP maksimal 512 KB. Server membuat kanvas PNG transparan 128×128 px dan memasukkan gambar secara proporsional ke area 112×112 px. Tidak ada crop; gambar non-persegi diberi ruang transparan.
- Semua output branding disimpan dalam PNG agar format output konsisten.
- Validasi byte, MIME aktual, signature/raster, tenant/install identity, dan managed storage path tetap berjalan.
- Sumber gambar dibatasi maksimal 20 megapiksel sebagai perlindungan memori; GD wajib aktif.

### IMPLEMENTASI
File diubah:
1. `backend-laravel/app/Livewire/Admin/TenantBrandingAssetsManagement.php` — menghapus validasi dimensi dari tiga field upload.
2. `backend-laravel/app/Services/TenantBrandingService.php` — menambahkan normalisasi GD, output PNG, resize proporsional logo, kanvas favicon, file sementara dan rename atomik.
3. `backend-laravel/resources/views/livewire/admin/tenant-branding-assets-management.blade.php` — memperjelas bahwa dimensi ditangani otomatis dan pengguna tidak perlu crop.
4. `backend-laravel/tests/Feature/TenantBrandingAssetsManagementTest.php` — mengganti test penolakan favicon non-persegi dengan test normalisasi; menambah test logo besar dan logo kecil; memperbarui ekspektasi WebP input menjadi PNG output.

Backup sebelum perubahan:
`backend-laravel/backups/admin-branding-auto-dimensions-20261126/`
Manifest: `MANIFEST.md`. Salinan backup byte-exact memiliki blob SHA yang sama dengan sumber sebelum patch.

### PENGUJIAN
- Test coverage baru ditulis, tetapi PHPUnit belum dijalankan.
- PHP runtime check sebelumnya pada PR #34 tidak mencakup patch ADM-04.
- Belum ada bukti upload langsung melalui browser atau respons Nginx/aaPanel.
- Tidak ada akses/perubahan ke VPS, database, atau deployment. Patch berada di branch PR #34 yang masih draft dan belum merged.

### STATUS
**IMPLEMENTASI SOURCE SELESAI UNTUK REVIEW — PENGUJIAN EKSEKUSI DAN UAT BELUM DILAKUKAN.**

## Hasil PHP Runtime untuk ADM-04

### PHP Runtime ADM-04 — 26 Nov 2026
- **PASS** — GitHub Actions run [45759840186](https://github.com/zane0184/cbt_november/actions/runs/45759840186), source commit `ec9e79fedddacaa56662c5c30b7ffa7301a819a3`.
- PHP 8.3.37, Composer 2.11.3, Laravel 13.42.0.
- Composer validate/install: PASS.
- PHP syntax lint: **310 file PASS** (termasuk service, Livewire component dan test yang diubah).
- Laravel package discovery dan boot: PASS.
- Route registration check: PASS.
- PHP GD module tersedia pada runner.
- PHPUnit dan test suite tidak dijalankan. Runtime check tidak membuktikan hasil normalisasi gambar secara fungsional.
- Browser upload, Nginx/aaPanel Content-Type, PHP GD pada VPS, dan production UAT belum diuji.

Status tetap: syntax/boot PASS; pengujian upload fungsional dan browser/VPS UAT masih pending.
