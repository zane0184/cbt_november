# Laporan Implementasi — Perbaikan Admin, Password, Mulok, Kurikulum & Import Siswa
Tanggal: 26 November 2026

Repository: `zane0184/cbt_november`
Source base: `work/rombel-docx-completion-20261126`
Base SHA: `841a1dd90dbe0277026fade1511a39b5dc9216b6`
Branch perubahan: `work/admin-import-curriculum-fixes-20261126`
HEAD perubahan: `0ca76d0d5c620da37006d69e52ddcdc344391db3`
PR: [#34 — Fix Admin semester, Mulok, account passwords, user tabs and curriculum visibility](https://github.com/zane0184/cbt_november/pull/34)
Status PR: OPEN / DRAFT / BELUM MERGED.

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

Status eksekusi:
- PHPUnit: **TIDAK DIJALANKAN**, mengikuti arahan pengguna agar validasi dilakukan langsung di VPS.
- CI: **TIDAK DIJALANKAN**.
- PHP lint/runtime: belum dijalankan.
- Browser UAT: belum dijalankan.
- Migration/VPS: tidak dijalankan.
- Source review dilakukan melalui pembacaan ulang file GitHub, perbandingan exact base/head, dan pemeriksaan struktur implementasi. Source review bukan bukti runtime PASS.

## STATUS / BATASAN

**SOURCE PATCH DITULIS — REGRESSION TEST DITAMBAHKAN — BELUM DIUJI RUNTIME — PR DRAFT BELUM MERGED.**

Hal yang masih memerlukan tindak lanjut:
1. Jalankan migration Mulok yang sudah ada hanya setelah backup database VPS, preflight, pemeriksaan migration ledger dan persetujuan.
2. Uji keenam permintaan pada VPS menggunakan source PR #34 setelah disetujui/diintegrasikan.
3. CP resmi tetap BLOCKED sampai artefak PDF asli dan lampiran yang sah diunggah ke GitHub, diarsipkan dan checksum-nya diverifikasi. Halaman Admin sekarang menampilkan status dan tidak mengarang konten.
4. Setelah uji VPS, catat hasil PASS/FAIL per fitur sebelum membuat ZIP distribusi baru.
