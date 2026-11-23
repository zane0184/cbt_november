# CHECKPOINT LANJUTAN WEB CBT — 23 NOVEMBER 2026

**Branch:** `work/solo-web-cbt-next-20261123`  
**Commit sumber yang diaudit:** `8d4a912c4306bdc0565eac0d168118d65939892f`  
**Workflow:** [Tenant Backend CI #45128384969](https://github.com/zane0184/cbt_november/actions/runs/45128384969)  
**Issue keputusan:** [#20](https://github.com/zane0184/cbt_november/issues/20)  
**Master cakupan Web Admin:** [#12](https://github.com/zane0184/cbt_november/issues/12)

> Status checkpoint ini bukan pernyataan siap produksi. Hasil hanya menyebut bukti yang telah tersedia pada commit yang dicantumkan. Tidak ada merge, deployment, atau perubahan database produksi.

## 1. AUDIT

### 1.1 Kondisi branch dan CI
- Branch kerja terverifikasi pada commit `8d4a912c4306bdc0565eac0d168118d65939892f`.
- CI #45128384969 menggunakan commit yang sama.
- Pada pemeriksaan terakhir: MySQL 8 disposable, validasi identitas DB, Composer validate/install, runtime directories, package discovery, dan pembuatan DB installer disposable berstatus sukses.
- Step focused `WebAdminAuthTest|StudentWebLoginTest` masih `in_progress`; full PHPUnit belum dimulai. Log job belum tersedia saat diminta (BlobNotFound ketika job masih berjalan). Tidak boleh menyatakan CI PASS.
- PR #21 tetap OPEN/DRAFT/UNMERGED, head `work/solo-web-cbt-completion-20261123` pada `e658e25b5bd07dad7ab46d172f517bb3f91702e3`, base `work/solo-web-cbt-ci-base-20261123`. Branch CI repair bukan head PR #21. Tidak ada force-push/merge/retarget.

### 1.2 RBAC dan role
- `RolePermissionBootstrapper.php` (blob `3be920483ca2961aad385cb96eb53709b212a50c`) telah mendefinisikan grants hanya untuk `ADMIN_MADRASAH`, `GURU`, dan `SISWA`. Admin diberi permission administrasi, identitas, taxonomy dan assignment Guru pengawas; tidak diberi permission question/exam operasional Guru.
- Seeder utama hanya membuat tiga role operasional lokal.
- Namun `RolePermissionBootstrapperTest.php` masih membuat dan menguji Operator/Kamad/Proktor legacy, mengharapkan Operator memperoleh grants Admin, serta mengharapkan KAMAD sebagai required role. Test ini stale dan tidak sesuai kontrak role terkini. Belum diubah pada checkpoint ini.
- `RoleDashboardVerificationTest.php` masih menguji Operator/Kamad/Proktor permanen dan monitoring Kamad. Test perlu ditulis ulang terhadap tiga portal, legacy role ditolak, dan Admin tidak bisa mengakses operasi CBT Guru.
- Migration `2026_11_23_130001_consolidate_legacy_local_roles.php` memetakan user Operator ke Admin Madrasah, menghapus role legacy lain, menulis audit log, serta revoke sesi/token. Ini adalah perubahan akun yang berdampak besar dan forward-only. Migration tetap BLOCKED untuk produksi sampai mapping plan, backup/recovery, uji upgrade disposable, dan persetujuan owner eksplisit tersedia. Jangan jalankan di produksi.

### 1.3 Route dan dashboard
- `routes/web.php` memiliki portal siswa via `StudentExamController`, portal Admin via komponen Livewire, dan route Guru dari `routes/teacher.php`.
- Tidak ditemukan folder Livewire Student pada tree yang diperiksa; portal siswa menggunakan controller, bukan Livewire.
- Dashboard Admin menghitung total users/siswa/Guru/rombel/mapel/Exam dengan tenant scope. Kartu status ujian operasional lebih detail belum terbukti lengkap.
- View dashboard memiliki tautan `/admin/exams`, sedangkan route Admin yang terdaftar di file route saat ini adalah `/admin/proctor-assignments` dan tidak mendaftarkan `/admin/exams`. Ini dead link yang perlu diperbaiki.
- Route Admin tersedia untuk users, rombel-students, academic, teacher assignments, curriculum, curriculum registry dan proctor assignments.
- Route exports tersedia untuk Excel/PDF laporan ujian dan PDF siswa. Setiap endpoint tetap perlu negative authorization test untuk memastikan Admin tidak memperoleh hasil/Attempt milik Guru.

### 1.4 Inventaris source Admin dan fitur pendukung
Tree source memuat komponen Admin:
- `Dashboard`
- `UserManagement`
- `AcademicManagement`
- `StudentRombelManagement`
- `TeacherSubjectAssignmentManagement`
- `CurriculumManagement`
- `CurriculumRegistryManagement`
- `ProctorAssignmentManagement`
- `ExamManagement`
- `QuestionBank`

Catatan:
- Keberadaan file tidak otomatis membuktikan seluruh CRUD, state, validasi, audit, export, dan acceptance test lengkap.
- Import/export administratif Guru/Siswa belum ditemukan pada daftar service/controller/export yang diperiksa; source yang terlihat mencakup `ExcelQuestionImporter` untuk bank soal dan `ExamResultsExport` untuk hasil ujian. Perlu audit lanjutan sebelum status final.
- Dalam tree aplikasi yang diperiksa belum ditemukan modul khusus profil/branding madrasah, kop surat, preferensi admin, notifikasi, backup/restore, atau health dashboard. Ini dicatat sebagai kandidat gap source, bukan bukti final ketiadaan sebelum pemeriksaan seluruh route/controller/view/asset.
- Tidak ditemukan source Android sebagai prioritas implementasi aktif karena keputusan scope saat ini WEB CBT ONLY sampai Juni 2027.

### 1.5 Kurikulum dan ujian
- `TeacherSubjectAssignmentManagement` menerapkan tenant scope dan memvalidasi Guru, subject, rombel, semester, registrasi kurikulum serta mapping struktur.
- `ExamSnapshotService` fail-closed untuk `SHORT_ANSWER` sampai accepted answers masuk immutable snapshot dan scoring terbukti.
- `ExcelQuestionImporter` dan `QuestionController` mengizinkan tipe yang tidak mencakup `SHORT_ANSWER`; test juga menolak tipe tersebut. Status SHORT_ANSWER tetap BLOCKED, jangan membuka hanya dengan menambah enum.
- XLSX import soal berada pada kontrak khusus yang telah dihapus dari scope; jangan mengembangkan kembali subsystem import XLSX soal tanpa keputusan baru.

## 2. DESAIN

1. Portal lokal tetap hanya Admin Madrasah, Guru, Siswa.
2. Admin memiliki hak administratif dan penugasan Guru pengawas sementara saja; assignment tidak membuka akses soal, token, peserta, Attempt, jawaban, monitoring CBT, penilaian, reset atau force submit.
3. Guru mengelola ujian dan soal hanya sesuai penugasan kelas/mapel.
4. Siswa menggunakan alur controller Web saat ini untuk scope Web yang disetujui; Android ditunda.
5. Legacy role migration dipisah dari bootstrap role instalasi baru. Jangan auto-map akun tanpa rencana dan persetujuan.
6. Dashboard/Admin UI hanya menampilkan data yang tersedia dan sesuai izin. Dead link dashboard harus ditangani pada patch UI terpisah.
7. Import/export administratif Guru/Siswa harus memakai satu lifecycle preview -> validate -> explicit confirm -> transactional batch -> error report -> audit, idempotent dan tidak menimpa histori.
8. Backup/restore harus diuji pada database disposable, dengan checksum dan prosedur recovery; tidak ada restore produksi tanpa persetujuan.
9. Setiap patch source harus punya backup byte-exact terlebih dahulu dan diuji di CI pada commit exact.

## 3. IMPLEMENTASI

### Perubahan nyata pada rangkaian commit sebelumnya
- Perbaikan deklarasi duplikat `is_official` dan `is_editable` pada migration curriculum.
- Penambahan trigger branch CI untuk branch repair.
- Penambahan kolom nullable UUID `created_by` dan `updated_by` ke migration material context sebelum foreign key dipasang.
- Backup file migration sebelum patch material:
  - Path: `backend-laravel/docs/backup/solo-web-cbt-short-answer-gate-2026-11-23/2026_11_23_000001_add_canonical_web_curriculum_contract.php.before-material-actor-columns`
  - Backup commit: `8529e5bf702145c607d0383ce406ec6d963f9b69`
  - Blob sebelum patch: `e183aba8b985776cfe157bfbbf2367bbab33aa37`
  - Patch commit: `8d4a912c4306bdc0565eac0d168118d65939892f`
- Backup test RBAC sebelum perubahan mendatang:
  - `backend-laravel/docs/backup/solo-web-cbt-short-answer-gate-2026-11-23/RolePermissionBootstrapperTest.php.before-role-contract-update`
  - Backup commit: `6158e1201b1a62ce7a133b9c351b649018107dcc`
  - Blob sebelum backup: `05c652e80c64f7988d2bc443add69a5a360d2cf6`
- Backup test dashboard sebelum perubahan mendatang:
  - `backend-laravel/docs/backup/solo-web-cbt-short-answer-gate-2026-11-23/RoleDashboardVerificationTest.php.before-role-contract-update`
  - Backup commit: `2e7088785ca31dded631f44a6979e4ee5e5fbd2c`
  - Blob sebelum backup: `0a71b0b9c2350cc494813206bc817ab8907506cc`
- Backup view dashboard sebelum perbaikan tautan:
  - `backend-laravel/docs/backup/solo-web-cbt-short-answer-gate-2026-11-23/dashboard.blade.php.before-admin-dead-link-fix`
  - Backup commit: `d863616aea97b17b4fa8a6e456b80f10dc18302d`
  - Blob sebelum backup: `38deb37b06824054af2c6d4f5c7c7735b3950ac6`

Tidak ada source tambahan diubah dalam checkpoint audit ini. Tidak ada migration dieksekusi terhadap database produksi.

## 4. PENGUJIAN

| Pemeriksaan | Hasil terakhir | Catatan |
|---|---|---|
| MySQL 8 disposable identity/InnoDB | PASS | CI step |
| Composer validate | PASS | CI step |
| Composer install locked deps | PASS | CI step |
| Laravel package discovery | PASS | CI step |
| Disposable installer database creation | PASS | CI step |
| Focused Web login/tenant tests | IN PROGRESS | Belum ada hasil final |
| Full PHPUnit | NOT RUN | Menunggu focused tests |
| Role bootstrapper tests terhadap kontrak baru | NOT RUN | Test lama masih stale |
| Role dashboard regression | NOT RUN | Test lama masih stale |
| Browser/E2E Admin | NOT RUN | Belum ada bukti browser |
| Import/export administratif | NOT RUN | Belum ada test acceptance |
| MySQL concurrency Proctor assignment | NOT RUN | Sequential test tidak membuktikan race safety |
| Fresh install dan upgrade legacy migration | NOT RUN | Harus disposable MySQL 8 |
| Production DB/deployment | NOT RUN | Dilarang tanpa approval |

## 5. LANGKAH BERIKUTNYA

1. Dapatkan hasil final CI #45128384969; jika gagal, ambil log dan perbaiki akar masalah saja.
2. Buat patch terpisah untuk menyelaraskan test RBAC dan dashboard terhadap tiga portal aktif; backup sudah tersedia.
3. Perbaiki dead link dashboard dan uji akses langsung Admin.
4. Lanjutkan audit lengkap ADM-01–ADM-23 per route, component, policy, model, migration, view, dan test.
5. Implementasikan hanya gap yang terbukti dan sudah sesuai keputusan owner.
6. Jalankan focused test dan full PHPUnit pada disposable MySQL 8.
7. Simpan laporan tiap tahap di `docs/` dan update Issue #20.
8. PR tetap Draft; tidak ada merge, deployment, atau migration produksi sebelum approval.

**Status keseluruhan:** WORK IN PROGRESS — NOT PRODUCTION READY.
