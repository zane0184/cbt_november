# LAPORAN W1 — AUDIT, RBAC, MIGRATION, DAN STATUS WEB CBT
Tanggal: 23 November 2026

**Repository:** https://github.com/zane0184/cbt_november  
**Branch utama tunggal:** work/solo-web-cbt-completion-20261123  
**Source HEAD yang diuji:** 49625c1a7e79ee97b6e394412d189c859e93e6ef  
**CI:** [Tenant Backend CI #45133706442](https://github.com/zane0184/cbt_november/actions/runs/45133706442)  
**Keputusan owner:** [Issue #20](https://github.com/zane0184/cbt_november/issues/20)  
**Master scope Web Admin:** [Issue #12](https://github.com/zane0184/cbt_november/issues/12)  
**PR terpadu:** [PR #21](https://github.com/zane0184/cbt_november/pull/21) — tetap OPEN/DRAFT/UNMERGED.

> Status keseluruhan WORK IN PROGRESS — NOT PRODUCTION READY. Laporan ini membedakan perubahan nyata dari pengujian yang masih berjalan. Tidak ada migration produksi, deployment VPS, atau merge PR.

---

## 1. AUDIT

### 1.1 Keputusan yang mengikat

Berdasarkan Issue #20 dan komentar owner terbaru:

- Instalasi setiap madrasah mandiri: domain, database, credentials, session, cache, queue, storage, log, export, temp dan backup terpisah.
- tenant_id tetap defense-in-depth.
- Portal lokal hanya Admin Madrasah, Guru, Siswa.
- Kamad dan Operator bukan role/portal operasional. Proktor bukan portal permanen.
- Admin menangani administrasi dan penugasan Guru pengawas sementara; tidak mengelola ujian/soal/token/peserta/room/Attempt/monitoring/jawaban/nilai.
- Assignment pengawas adalah catatan administratif dan tidak membuka akses room/session/seat/roster/monitoring/Attempt atau operasi CBT.
- Guru mengelola bank soal dan ujian sesuai penugasan.
- Metadata asesmen harus fleksibel dan versioned, menyatu dengan Kurikulum Merdeka + KBC + Pembelajaran Mendalam.
- UTS/UAS/UM/SIMULASI TKA bukan enum/kategori baku.
- Tidak ada ExamSession/gelombang sebagai admission gate. Jadwal Exam satu-satunya acuan mulai ujian.
- Android siswa native; Android bukan scope implementasi aktif sampai keputusan Juni 2027.
- Device Lease/fencing belum siap produksi sebelum E2E MySQL 8 multi-device.

### 1.2 Temuan migration dari CI

| CI | SHA | Hasil | Akar masalah |
|---|---|---|---|
| [#45129553728](https://github.com/zane0184/cbt_november/actions/runs/45129553728) | c9f05ccccbda82586b8fab3e1e135df371981900 | FAILED | question_version_tenant_outcome_mappings memakai teacher_subject_assignment_id di unique/FK tanpa deklarasi kolom |
| [#45130860599](https://github.com/zane0184/cbt_november/actions/runs/45130860599) | 655ee4cd5f7d75c4e1886e36c0140730a0f69b7d | FAILED | Setelah qvtom diperbaiki, question_version_structure_mappings juga memakai teacher_subject_assignment_id tanpa deklarasi kolom |
| [#45133706442](https://github.com/zane0184/cbt_november/actions/runs/45133706442) | 49625c1a7e79ee97b6e394412d189c859e93e6ef | IN PROGRESS | Memuat dua deklarasi assignment scope dan seluruh patch RBAC W1; focused tests masih berjalan saat laporan dibuat |

Dua kegagalan di atas berasal dari migration yang gagal sebelum assertion test dijalankan. Full PHPUnit pada kedua run tersebut tidak dimulai. Tidak boleh mengklaim tes RBAC telah lulus hanya dari status step setup.

### 1.3 Audit RBAC dan security boundary

Source awal masih memiliki beberapa jalur role legacy/multi-role:

- AssessmentPolicy masih mengizinkan OPERATOR/KAMAD melihat asesmen.
- ExamAttemptPolicy masih mengizinkan KAMAD membaca Attempt/nilai.
- Beberapa policy/controller menggunakan hasRole() tanpa exact-one-role.
- Admin Dashboard menerima akun multi-role selama memiliki role Admin.
- ExamPolicy::monitor dahulu memberi monitoring hanya karena ada assignment Proktor.
- ProktorDashboard masih memiliki komponen permanen walau tidak ada route portal.
- StudentExamController dan validasi roster perlu exact Siswa.
- Assignment service perlu memeriksa exact Guru, bukan hanya role existence.

Semua ini bertentangan dengan keputusan portal dan W7 Issue #20.

### 1.4 Audit Admin UI dan operasional

Source Admin yang ditemukan:
- Dashboard
- UserManagement
- AcademicManagement
- StudentRombelManagement
- TeacherSubjectAssignmentManagement
- CurriculumManagement
- CurriculumRegistryManagement
- ProctorAssignmentManagement

Temuan yang perlu ditindaklanjuti:
- Dashboard sebelumnya menampilkan Total Ujian dan tautan dead-end /admin/exams.
- UserManagement yang diperiksa mendukung daftar/pembuatan akun, tetapi update/deactivate/reset/import/export belum terbukti.
- AcademicManagement yang diperiksa mendukung pembuatan rombel/mapel, tetapi CRUD lengkap dan audit event belum terbukti.
- StudentRombelManagement memvalidasi satu role SISWA saat submit; kandidat list juga perlu menyembunyikan akun multi-role.
- Model pivot siswa-rombel belum membuktikan aturan satu rombel aktif per tahun pelajaran dengan histori transfer yang lengkap.
- ProctorAssignmentManagement memilih Exam PUBLISHED; belum ada penanda kanonis yang membedakan ujian pusat yang ditetapkan owner dari ujian lokal.
- AssessmentController API lama masih mewajibkan enum assessment_type (DAILY/MIDTERM/FINAL/PRACTICE/TRYOUT/CUSTOM) dan belum mewajibkan seluruh metadata taxonomy canonical. Jalur tulis ini dapat menghasilkan kontrak asesmen yang tidak konsisten dengan Web ExamManagement/ExamSnapshotService.
- Import/export administratif Guru/Siswa belum terbukti tersedia. XLSX question import tidak boleh dikembangkan kembali; JSON rows[] importer existing tidak boleh dirusak.
- Profil/branding madrasah, logo/favicon, kop surat, pengaturan tampilan/footer/kontak, notifikasi, backup/restore dan health dashboard belum dibuktikan lengkap oleh audit source awal.

### 1.5 Batas keamanan assignment Proktor

Keputusan Issue #20 W7:
- Penugasan adalah catatan administratif sementara Guru + Exam + waktu + alasan + aktor + status + audit.
- Penugasan tidak memberi hak monitoring, roster, room, seat, Attempt, jawaban, nilai, token atau operasi CBT.
- Tidak ada portal Proktor permanen.

Penanda kanonis ujian pusat belum ditemukan. assessment_origin=EXTERNAL dan external_program_name tidak boleh otomatis dianggap sebagai penetapan ujian pusat. Eligibility assignment masih BLOCKED.

---

## 2. DESAIN

### 2.1 Exact-one-role authorization
- Portal aktif: ADMIN_MADRASAH, GURU, SISWA.
- Setiap boundary authorization memeriksa tepat satu role yang sesuai, membaca pivot role terbaru dari database.
- Legacy roles OPERATOR/KAMAD/PROKTOR/SUPER_ADMIN tidak menjadi izin.
- Multi-role account ditolak meskipun salah satu rolenya termasuk role portal aktif.
- Permission juga dibaca fresh dari DB agar perubahan grant tidak tertutup oleh relasi yang stale.

### 2.2 Batas Admin
- Admin boleh melihat metadata/register yang diizinkan dan melakukan administrasi.
- Admin tidak boleh membuka question bank, token, roster, Attempt, jawaban, monitoring, score/result, force submit atau reset Attempt.
- Admin hanya mencatat penugasan Guru pengawas sesuai eligibility ujian pusat yang otoritatif.
- Assignment tidak menjadi authorization grant.

### 2.3 Batas Guru/Siswa
- Guru: scope subject/rombel/academic year/semester dari TeacherSubjectAssignment.
- Siswa: hanya exam participant dan hasil milik sendiri.
- Penempatan Rombel dan roster Exam hanya menerima akun dengan tepat satu role SISWA.

### 2.4 Migration dan data
- Pertahankan unique index dan composite foreign key; jangan menghapus constraint untuk membuat CI hijau.
- Migration legacy role consolidation tidak dijalankan sebelum mapping plan, backup/recovery, upgrade test disposable dan persetujuan owner.
- Data produksi tidak disentuh.

---

## 3. IMPLEMENTASI

### 3.1 Migration

File utama:
backend-laravel/database/migrations/2026_11_23_000001_add_canonical_web_curriculum_contract.php

Perubahan yang sudah dibuat:
1. Menghapus deklarasi duplikat is_official dan is_editable dalam satu Schema closure.
2. Menambahkan nullable UUID created_by dan updated_by ke material context sebelum FK dibuat.
3. Menambahkan UUID NOT NULL teacher_subject_assignment_id ke question_version_tenant_outcome_mappings.
4. Menambahkan UUID NOT NULL teacher_subject_assignment_id ke question_version_structure_mappings.

Backup migration tersedia pada branch:
backup/solo-web-cbt-before-ci-repair-20261123

Folder:
backend-laravel/docs/backup/solo-web-cbt-role-rbac-2026-11-23/

Nama backup relevan:
- 2026_11_23_000001_add_canonical_web_curriculum_contract.php.before-qvtom-assignment-column-repair
- 2026_11_23_000001_add_canonical_web_curriculum_contract.php.before-qvsm-assignment-column-repair
- 2026_11_23_000001_add_canonical_web_curriculum_contract.php.before-material-actor-columns

### 3.2 RBAC dan portal

File source yang diperbarui:
- backend-laravel/app/Models/User.php: tambah hasExactRole() dan hasExactlyOneOfRoles(); permission query fresh.
- backend-laravel/app/Policies/BasePolicy.php: hanya Admin/Guru exact.
- backend-laravel/app/Policies/ExamPolicy.php: exact role; assignment Proktor tidak memberi monitoring.
- backend-laravel/app/Policies/AssessmentPolicy.php: hapus akses Operator/Kamad.
- backend-laravel/app/Policies/ExamAttemptPolicy.php: hapus akses Kamad ke Attempt/nilai.
- backend-laravel/app/Policies/QuestionPolicy.php: exact Guru.
- backend-laravel/app/Policies/ReportPolicy.php: exact Guru/Siswa.
- backend-laravel/app/Support/Security/TeacherAccess.php: exact Guru dan Siswa.
- backend-laravel/app/Livewire/Admin/Dashboard.php: exact Admin.
- backend-laravel/app/Http/Controllers/StudentExamController.php: exact Siswa.
- backend-laravel/app/Http/Controllers/Api/V1/ExamController.php: roster hanya exact Siswa.
- backend-laravel/app/Services/ExamProctorAssignmentService.php: target pengawas exact Guru.
- backend-laravel/app/Livewire/Proktor/ProktorDashboard.php: compatibility stub selalu 403.
- backend-laravel/app/Http/Controllers/AuthWebController.php: sesi legacy/multi-role di-logout saat membuka login form; Guru diarahkan ke /guru/dashboard.
- backend-laravel/app/Livewire/Admin/StudentRombelManagement.php: kandidat siswa dengan role kedua disembunyikan.

### 3.3 UI dan test

- Dashboard Admin mengganti kartu Total Ujian dengan jumlah assignment pengawas aktif.
- Dead link /admin/exams diganti tautan ke /admin/proctor-assignments.
- Test WebAdminAuth diselaraskan dengan tiga portal dan logout legacy session.
- RoleDashboardVerificationTest ditulis ulang untuk tiga portal, legacy role, multi-role dan batas Admin.
- ProctorAssignmentSecurityTest memastikan assignment tidak membuka monitoring/portal Proktor.
- Phase7DReportingTest tidak lagi menggunakan Kamad sebagai pengawas laporan; Admin ditolak untuk export hasil.
- AdminUiVerificationTest hanya menguji route Admin yang benar-benar terdaftar dan tidak mengharapkan akses /admin/exams atau /admin/questions.

### 3.4 Backup

Semua file source/test yang diubah memiliki backup byte-exact di branch:
backup/solo-web-cbt-before-ci-repair-20261123

Folder:
backend-laravel/docs/backup/solo-web-cbt-role-rbac-2026-11-23/

Laporan commit dan backup blob SHA tersedia pada komentar Issue #20. Tidak ada file baseline eksternal yang digunakan.

---

## 4. PENGUJIAN

### 4.1 CI terbaru

[Run #45133706442](https://github.com/zane0184/cbt_november/actions/runs/45133706442) menggunakan source SHA 49625c1a7e79ee97b6e394412d189c859e93e6ef.

Saat laporan ini dibuat:
- Setup job: PASS
- Initialize MySQL 8: PASS
- Checkout: PASS
- PHP 8.3: PASS
- Disposable MySQL identity/InnoDB: PASS
- Composer validate: PASS
- Composer install locked dependencies: PASS
- Laravel runtime dirs/package discovery: PASS
- Disposable installer DB creation: PASS
- Focused Web login/tenant identity tests: IN PROGRESS
- Full PHPUnit: NOT RUN
- Stop containers: PENDING

Belum ada klaim bahwa migration berhasil sepenuhnya, focused tests PASS, atau full PHPUnit PASS.

### 4.2 Test historis yang gagal
- #45129553728: gagal karena kolom qvtom assignment scope tidak dideklarasikan.
- #45130860599: gagal karena kolom qvsm assignment scope tidak dideklarasikan.
- Kedua akar masalah sudah dipatch pada migration; run #45133706442 adalah validasi pertama setelah patch lengkap + RBAC batch.

### 4.3 Test yang belum dilakukan
- Browser E2E Admin/Guru/Siswa.
- API direct-role negative tests penuh.
- Full PHPUnit.
- Concurrent overlap Proctor assignment pada dua koneksi MySQL 8.
- Upgrade dari data legacy pada DB disposable.
- Restore/backup recovery.
- Offline/sync/recovery Android.
- Multi-device Device Lease/fencing.
- UAT Admin/Guru/Siswa madrasah.

---

## 5. OPEN BLOCKER DAN PEKERJAAN LANJUTAN

P0:
1. Tunggu hasil CI #45133706442; ambil log bila gagal dan patch akar masalah.
2. Setelah focused tests PASS, jalankan dan perbaiki full PHPUnit sampai PASS.
3. Tutup exact-role policy gap dengan regression tests tambahan untuk semua API entry point.
4. Tentukan penanda kanonis dan aktor penetap ujian pusat sebelum membuka assignment Proktor. Jangan samakan EXTERNAL dengan ujian pusat.
5. Selesaikan AssessmentController API legacy enum/fixed category agar satu canonical assessment DTO/validation; endpoint tulis harus fail-closed sampai kontrak canonical tersedia.
6. Rencanakan legacy role migration dengan mapping akun dan recovery; jangan produksi tanpa approval.

P1:
7. Audit ADM-01–ADM-23 per route/component/policy/model/migration/view/test.
8. Lengkapi UserManagement: edit, deactivate/reactivate, reset credential, audit, import/export jika kontrak disetujui.
9. Lengkapi AcademicManagement: master data, status, histori, audit dan validasi.
10. Rancang histori penempatan siswa-rombel dan aturan satu rombel aktif per tahun pelajaran.
11. Audit TeacherSubjectAssignment lifecycle, overlap dan uniqueness.
12. Implementasi import/export Guru/Siswa menggunakan satu lifecycle transactional dan audit.
13. Profil/branding madrasah, kop surat, pengaturan umum/tampilan/footer/kontak, notifikasi, health, backup/restore.
14. Audit dashboard statistik agar label sesuai filter status dan scope.
15. Uji concurrency, idempotency, tenant isolation, restore, dan audit log.

P2:
16. Lanjutkan backend/web finalisasi, baru kemudian Android Studio dan VPS deployment sesuai urutan approval.
17. Android native tetap wajib untuk siswa Android; jangan menjalankan Gradle via terminal.
18. Device Lease/fencing tetap NOT PRODUCTION READY sebelum E2E MySQL 8 multi-device berhasil.

---

**Kesimpulan:** W1 telah menghasilkan patch migration dan hardening role/portal yang nyata, dengan backup dan laporan. CI terbaru masih berjalan pada focused test. Proyek tetap WORK IN PROGRESS — NOT PRODUCTION READY.
