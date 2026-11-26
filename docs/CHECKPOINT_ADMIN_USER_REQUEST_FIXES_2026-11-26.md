# Checkpoint — Admin, Import Siswa, Password, Mulok & CP/TP
Tanggal: 26 November 2026

- Repository: zane0184/cbt_november
- Base: work/rombel-docx-completion-20261126 @ 841a1dd90dbe0277026fade1511a39b5dc9216b6
- Branch aktif: work/admin-import-curriculum-fixes-20261126
- Code changeset HEAD sebelum dokumentasi: 0ca76d0d5c620da37006d69e52ddcdc344391db3
- Branch/PR head saat checkpoint: 6e1a394a4c80f429376387fb8bf4ad6d7a126b0a
- PR: #34 https://github.com/zane0184/cbt_november/pull/34, OPEN/DRAFT, belum merged; GitHub melaporkan mergeable=true.
- Laporan: docs/REPORT_ADMIN_USER_REQUEST_FIXES_2026-11-26.md

## AUDIT
- Semester dropdown sebelumnya sudah menggunakan term; tabel daftar assignment masih menggunakan atribut semester.name yang tidak ada.
- Mulok create/category sudah ada dari tahap sebelumnya; edit UI/method belum ada. Migration category sudah ada dan merupakan prasyarat schema VPS.
- Password installer/Admin sebelumnya minimum 14; import dan reset menghasilkan 24 karakter.
- UserManagement sudah satu component, tetapi filter dropdown belum berupa tab Guru/Siswa.
- Admin curriculum route/component ada, tetapi tidak ditautkan di nav dan membaca data legacy. Data CP official belum diimpor/verified; P0-02 GitHub mencatat artefak asli dan checksum belum tersedia.
- Import Siswa masih memerlukan Rombel yang sudah dipilih di luar template.

## DESAIN
- Role dan portal tetap: Admin Madrasah, Guru, Siswa. Tidak ada portal baru.
- Admin hanya melihat CP/TP; Guru membuat TP/ATP melalui assignment.
- CP hanya dapat tampil sebagai official setelah source checksum verified; tidak membuat konten contoh.
- Template Import Siswa satu file per Rombel, metadata nama Rombel/tahun/kelas seragam.
- Rombel otomatis dibuat dalam transaksi yang sama dengan batch import, hanya setelah seluruh preview valid.

## IMPLEMENTASI
- Assignment list menampilkan Semester Ganjil/Genap dari semester.term.
- Mulok dapat diedit oleh Admin tenant; registry/mapel wajib tidak dapat diedit; create/update diaudit.
- Password Admin minimum 8; password acak import Guru/Siswa dan reset akun menjadi 8 karakter.
- Pengguna & Akun satu halaman dengan tab Semua Akun/Guru/Siswa.
- CP & TP link tampil pada navigasi Admin; page menampilkan verified CP, TP/ATP Guru, Panca Cinta, Pembelajaran Mendalam dan dimensi profil lulusan dari registry versi terdaftar.
- Template Siswa menambahkan rombel, tahun_pelajaran, kelas; preview validasi satu Rombel per file; import membuat Rombel bila belum ada dan memasukkan seluruh siswa ke Rombel itu.
- Backup exact source tersimpan di backend-laravel/backups/user-request-fixes-20261126/, manifest: MANIFEST.md.
- Tidak ada migration baru, perubahan VPS, database produksi, atau merge.

## PENGUJIAN
- **PHP runtime PASS** pada GitHub Actions run 45759006825: https://github.com/zane0184/cbt_november/actions/runs/45759006825
- Runner: PHP 8.3.37, Composer 2.11.3, Laravel 13.42.0.
- Composer validate dan locked dependency install: PASS.
- PHP syntax lint 310 file di app/bootstrap/config/database/routes: PASS.
- Laravel package discovery dan boot: PASS.
- Route list: PASS; enam route Admin terverifikasi (users, import siswa, import guru, penugasan guru, curriculum, academic).
- PHPUnit: tidak dijalankan.
- Full CI/test suite: tidak dijalankan.
- Migration, browser UAT, VPS dan database produksi: tidak dijalankan/tidak diakses.
- Regression tests ditambah/disesuaikan: Mulok edit, Rombel otomatis, password 8 karakter, dan semester pada daftar assignment; test belum dieksekusi.
- Runtime dilakukan pada source aplikasi PR #34 (kode sama dengan changeset 46f343ab3861af361bed785c41d22716999973a0); run merge-checkout SHA eac50667ddf5d04a8cb260f3f9f7a356a4dff53a.

## NEXT
1. Review/merge PR #34 setelah source review.
2. Di VPS, periksa migration ledger untuk 2026_11_26_000001_add_subject_category_for_local_content.php; jalankan hanya setelah backup, preflight dan persetujuan.
3. Uji enam kebutuhan pengguna pada staging/VPS; catat bukti PASS/FAIL.
4. CP official tetap BLOCKED sampai artefak PDF asli/appendix dan checksum resmi tersedia di GitHub.
5. Buat ZIP deployment baru setelah integrasi source dan acceptance VPS; jangan gunakan ZIP lama untuk menyatakan patch ini sudah terpasang.

Status: IMPLEMENTASI SOURCE SELESAI UNTUK REVIEW — PR DRAFT — RUNTIME ACCEPTANCE BELUM DILAKUKAN.


## Addendum — ADM-04 Web Admin Upload PNG & Dimensi Otomatis (26 Nov 2026)

### AUDIT
- Sebelumnya validasi Livewire mewajibkan dimensi minimum/maksimum logo dan favicon persegi.
- `TenantBrandingService::storeUpload()` mengulang penolakan dimensi tersebut, sehingga gambar valid gagal disimpan walau ukuran file dan formatnya sesuai.
- Admin diminta crop/menyesuaikan gambar sendiri; ini tidak sesuai kebutuhan.

### DESAIN
- Logo aplikasi/dokumen: PNG/JPG/JPEG/WebP, maksimum 2 MB; sistem mempertahankan rasio dan mengecilkan gambar secara otomatis agar muat dalam 1600×1200 px, tanpa crop dan tanpa memperbesar gambar kecil.
- Favicon: PNG/WebP, maksimum 512 KB; sistem membuat kanvas PNG transparan 128×128 px, lalu memasukkan gambar secara proporsional ke area 112×112 px. Seluruh gambar dipertahankan, tidak dipotong.
- Semua aset branding hasil pemrosesan disimpan sebagai PNG.
- Batas keamanan sumber 20 megapiksel mencegah gambar terkompresi ekstrem menghabiskan memori; ekstensi PHP GD wajib aktif.
- Pemeriksaan ekstensi, MIME aktual, validitas raster, batas byte, tenant/install identity, dan path storage aman tetap dipertahankan.

### IMPLEMENTASI
- `TenantBrandingAssetsManagement.php`: aturan `dimensions` dihapus; validasi format dan batas ukuran file tetap.
- `TenantBrandingService.php`: normalisasi otomatis melalui GD, PNG output, logo fit proporsional 1600×1200, favicon fit ke kanvas 128×128, tulis file sementara lalu rename atomik.
- `tenant-branding-assets-management.blade.php`: petunjuk UI menjelaskan dimensi otomatis dan tidak perlu crop manual.
- `TenantBrandingAssetsManagementTest.php`: regression coverage untuk favicon non-persegi, logo besar yang otomatis diperkecil, logo kecil tanpa minimum dimensi, dan WebP yang dinormalisasi menjadi PNG.
- Backup byte-exact dibuat sebelum perubahan pada `backend-laravel/backups/admin-branding-auto-dimensions-20261126/`; manifest mencatat SHA file sumber dan backup.

### PENGUJIAN
- Regression tests ditambahkan/diubah tetapi **belum dieksekusi**.
- PHPUnit/full CI tidak dijalankan.
- PHP runtime PASS sebelumnya pada PR #34 hanya membuktikan source sebelum patch ADM-04; bukan bukti runtime patch ini.
- Browser upload UAT, header HTTP Nginx/aaPanel, dan VPS belum diuji.
- Tidak ada deployment, migration, atau perubahan database/VPS.

Status ADM-04: **SOURCE PATCH COMMITTED — TEST COVERAGE ADDED — EXECUTION/UAT PENDING**.


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


 
## Addendum — DESAIN SELF-SERVICE KELAS/PENUGASAN GURU (26 Nov 2026)

### AUDIT
- Source branch HEAD `2308754139b7f8c5d3785c7571e37e60fbae0f00` diperiksa.
- `teacher_subject_assignments` sudah menjadi relasi Guru-mapel-rombel-tahun ajaran-semester dan memiliki `created_by`, status, tanggal, serta snapshot kurikulum.
- Migration tenant FK assignment sudah memaksa relasi tenant-aware, termasuk creator.
- `TeacherPortalAccess` memvalidasi role GURU tunggal, tenant, akun aktif, dan profil Guru unik.
- `TeacherAssignmentsPage` hanya membaca data; Admin Livewire component masih memiliki proses create dan validasi akademik.

### DESAIN
- Guru membuat sendiri kelas/penugasan mapel-rombel tanpa persetujuan Admin.
- Admin mengelola data induk dan memonitor kelas/pengampu; tindakan koreksi/penonaktifan administratif harus beralasan dan diaudit.
- Gunakan `TeacherSubjectAssignment` sebagai identitas ruang kelas kanonis; tidak membuat tabel kelas paralel.
- Wizard batch: periode aktif otomatis, pilih rombel, pilih mapel yang sesuai fase, review pasangan mapel-rombel, simpan atomik.
- Kurikulum dipilih dari registrasi aktif server-side; guru tidak memilih versi sembarangan.
- Validasi tenant, role, profil, mapel, rombel, fase, semester/tahun ajaran, mapping subject/provenance, duplikasi dan snapshot diulang server-side.
- Co-teaching lintas Guru diberi peringatan; duplikasi identik Guru yang sama ditolak.
- Assignment inti tidak diedit setelah dipakai modul pembelajaran; penonaktifan beralasan dan histori dipertahankan.

### IMPLEMENTASI
- Belum ada source aplikasi yang diubah.
- Dokumen rancangan: `docs/DESIGN_GURU_SELF_SERVICE_CLASS_ASSIGNMENT_2026-11-26.md`.
- Backup checkpoint sebelum addendum: `docs/backups/GURU_SELF_SERVICE_ASSIGNMENT_DESIGN_20261126/CHECKPOINT_ADMIN_USER_REQUEST_FIXES_2026-11-26.md.before`; manifest: `docs/backups/GURU_SELF_SERVICE_ASSIGNMENT_DESIGN_20261126/MANIFEST.md`.

### PENGUJIAN
- Tidak ada test yang dijalankan karena tahap ini adalah desain.
- Tidak ada migration, database produksi, VPS, atau deployment yang disentuh.

Status: **DESAIN DISETUJUI — SOURCE IMPLEMENTATION BELUM DIMULAI.**

 
## Addendum — Implementasi Self-Service Kelas Guru (26 Nov 2026)

### AUDIT
- Penugasan kanonis, validasi tenant/akademik/kurikulum, snapshot, batch multi-mapel, dan AuditService sudah tersedia pada `TeacherSubjectAssignmentManagement`.
- Halaman Guru sebelumnya hanya membaca data melalui `TeacherPortalDataService`; tidak ada UI create.
- Komponen Admin sebelumnya menampilkan formulir create yang memaksa Admin memilih profil Guru.
- Tabel `teacher_subject_assignments` sudah memiliki `created_by`; proses create sebelumnya belum mengisi kolom ini.

### DESAIN
- Guru membuat kelas dari menu **Kelas Saya**. Identitas Guru diambil dari akun yang sedang login, tidak dari input browser.
- Dalam satu pengiriman Guru memilih satu rombel dan beberapa mata pelajaran. Untuk rombel berbeda, Guru mengulangi batch berikutnya agar sistem tidak membuat kombinasi silang mapel–rombel yang tidak benar.
- Semester dipilih dari semester pada tahun ajaran rombel.
- Versi kurikulum tidak dipilih bebas. Sistem otomatis mengambil tepat satu registrasi kurikulum aktif. Jika tidak ada atau lebih dari satu, submit dihentikan dan Admin diminta membetulkan Registry.
- Admin menggunakan menu **Monitoring Kelas**; formulir pembuatan tidak ditampilkan pada portal Admin.
- Data penugasan lama dipertahankan dan ditampilkan sebagai riwayat.

### IMPLEMENTASI
- `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`: komponen kanonis dipakai bersama oleh route Admin dan Guru; akses Guru diperiksa dengan `TeacherPortalAccess`; akun harus aktif dan tepat satu role; teacher profile dipaksa dari session server-side; registrasi kurikulum aktif dipilih otomatis; daftar assignment dibatasi ke profil Guru yang login; `created_by` diisi dari `auth()->id()`.
- `backend-laravel/app/Livewire/Teacher/TeacherAssignmentsPage.php`: entry point Portal Guru menggunakan kembali komponen/validasi yang sudah ada, tidak membuat service atau tabel baru.
- `backend-laravel/resources/views/livewire/admin/teacher-subject-assignment-management.blade.php`: UI Guru menampilkan form self-service batch; UI Admin menjadi monitoring read-only.
- `backend-laravel/resources/views/layouts/app.blade.php`: label Admin menjadi **Monitoring Kelas**.
- `backend-laravel/resources/views/layouts/teacher.blade.php`: label Guru menjadi **Kelas Saya**.
- `backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php`: ditambah skenario Guru membuat kelas melalui Portal Guru, mencoba memalsukan teacher_profile_id, dan verifikasi owner serta created_by tetap akun Guru login.
- Workflow PHP runtime diperluas untuk branch PR #34, syntax lint termasuk `tests`, route `guru/penugasan`, serta kompilasi Blade.
- Backup source sebelum patch: `backend-laravel/backups/guru-self-service-assignment-20261126/`; manifest tersedia pada folder tersebut. Backup laporan/checkpoint: `docs/backups/GURU_SELF_SERVICE_IMPLEMENTATION_20261126/`.

### PENGUJIAN
- Test regression baru ditulis, tetapi PHPUnit **belum dijalankan**.
- Workflow PHP runtime diperluas; status eksekusi workflow untuk commit implementasi belum terkonfirmasi.
- Belum ada bukti browser UAT atau pengujian pada VPS.
- Tidak ada migration, perubahan database, atau deployment.
- PR #34 masih Draft dan belum merged.

### BATAS IMPLEMENTASI
- Batch UI saat ini: satu rombel dengan banyak mapel per submit. Tidak ada auto-cross-product untuk banyak rombel.
- Bila tenant mempunyai nol atau lebih dari satu registrasi kurikulum ACTIVE, Guru tidak dapat submit sampai registry diperbaiki Admin.
- Komponen Admin masih menyimpan method lama untuk kompatibilitas/tes internal, tetapi form create tidak ditampilkan pada halaman Admin; operasional normal dipindahkan ke Guru.

Status: **SOURCE PATCH COMMITTED — REGRESSION TEST DITAMBAHKAN — EKSEKUSI TEST DAN BROWSER/VPS UAT MENUNGGU.**
