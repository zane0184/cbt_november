# AUDIT LANJUTAN KOORDINATOR — WEB ADMIN MADRASAH
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Basis audit: work/web-admin-package1-implementation-20261122
Basis commit: d7e2034ede3b044fc4ab0b900335eff3669b41ed
Branch kerja koordinator: work/coordinator-web-admin-audit-20261122

## Status
AUDIT STATIS LANJUTAN — WEB ADMIN BELUM ACCEPTED.
Dokumen ini merekam pemeriksaan source GitHub. Tidak ada source runtime, migration, database, VPS, deployment, atau branch utama yang diubah.

## AUDIT

### Provenance dan branch
- main HEAD saat pemeriksaan: feab77228f7108f7f7a45325d7f1fb878c07310b.
- Basis Paket 1 HEAD saat pemeriksaan: d7e2034ede3b044fc4ab0b900335eff3669b41ed.
- Paket 1 merupakan kelanjutan linear WA01 Paket 0: 36 commits ahead, 0 behind (base b86ba805388b0913f20858cf3e13aace3a6f393f).
- Paket 1 diverged dari main: 668 ahead, 15 behind; merge/rebase/cherry-pick tidak boleh dilakukan secara otomatis.
- Branch koordinator dibuat dari exact SHA Paket 1 d7e2034ede3b044fc4ab0b900335eff3669b41ed.
- Source Paket 1 adalah kandidat revisi, bukan bukti source yang sedang berjalan di VPS.

### Bukti tahap terdahulu
Checkpoint Stage 1/2/3 mencatat hasil CI pada source SHA masing-masing:
- Stage 1 Dashboard: 40 tests / 174 assertions PASS, run 44923715582.
- Stage 2 Academic Periods: 43 tests / 207 assertions PASS, run 44924891185.
- Stage 3 Identity & Users: 49 tests / 246 assertions PASS, run 44926079149.
Semua menggunakan PHP 8.3 dan MySQL 8 disposable/InnoDB. Bukti tersebut tidak otomatis membuktikan source HEAD Paket 1 terkini lulus, dan bukan uji VPS.

### Temuan source statis (blob SHA pada basis Paket 1)
1. UserManagement.php — 9173f4b6032f43bebe9472234eb80a6176af043e
   - Authorization memakai hasRole('ADMIN_MADRASAH'), bukan exact-one role.
   - Fitur yang terlihat baru daftar dan pembuatan akun Guru/Siswa. Edit, aktivasi/nonaktif, reset password, import, dan pengelolaan status belum tersedia pada komponen ini.
   - Perlu pemeriksaan semua action publik dan relasi profil untuk tenant scoping.
2. AdminDashboard.php — b81ce1ea19a50db07ec439b15bec0a034f340afc
   - Guard memakai hasRole('ADMIN_MADRASAH') tanpa exact-one check dan tidak tampak permission gate pada guard dashboard.
3. AcademicManagement.php — 3edf4ce374328d957f4320e32a92882ffdcd302c
   - Guard mengecek keberadaan role ADMIN_MADRASAH, bukan tepat satu role.
   - Daftar tahun ajaran didukung dibatasi hard-coded 2026/2027 sampai 2031/2032; perlu desain konfigurasi/versioning tanpa menghilangkan histori.
   - Perlu audit transaksi, audit log, duplicate prevention dan tenant validation untuk seluruh action, termasuk mutasi mapel/rombel.
4. StudentRombelManagement.php — 0da3f15787fbaf1fe6ea6f0eaec1439f413231f1
   - Guard memakai hasRole dan query mengandalkan TenantScoped; perlu fail-closed test untuk tenant context kosong/stale/berubah.
   - Penambahan membership memeriksa duplikasi, tetapi perlu evaluasi unique DB constraint dan concurrency.
5. CurriculumManagement.php — e3422650ea71709cba5ae07cd2b887cf812166a8
   - Audit sebelumnya menemukan jalur legacy ADMIN; harus ditutup dan exact-one role ditegakkan.
   - Data Kurikulum Merdeka/KBC harus versioned dan histori tidak boleh dihapus; jangan mengarang katalog CP/TP/ATP/Panca Cinta tanpa dataset tervalidasi.
6. TenantProfileManagement.php — da65d6b9d8536c31c85f3dd2574df65a6be93fb6
   - Guard memakai hasRole; perlu exact-one role dan permission khusus profil, bukan mengandalkan permission akademik secara implisit.
7. AuthWebController.php — 235518c4d1fa9913b3add7a02636bbfcd608d408
   - Login masih menerima KAMAD; redirect Guru ke /admin/exams. Keduanya bertentangan dengan tiga portal final (Admin Madrasah, Guru, Siswa).
8. routes/web.php — b258f98a5cf16d45a53c6da5e45ac8afc6f7cdd3
   - Route Admin masih mendaftarkan QuestionBank dan ExamManagement; perlu pemisahan portal dan authorization backend, bukan hanya hide menu.
   - Route ProctorAssignmentManagement ada tetapi tidak boleh diaktifkan sampai kontrak direct Exam–Room dan service/authorization/concurrency Agent 1 terbukti.
   - Route hasil/export Guru berada di kelompok auth/tenant/license umum; harus punya exact-role dan policy scope.
9. LegacyRoleMigrationService.php — d82e0789fe4ef74ae81e2576052e69f597ac45fd
   - Migration mengubah role_user, users.role, dan mengarsipkan role lama. Ini migrasi data material.
   - Harus diuji pada clone/disposable DB dengan skenario multi-role, role column mismatch, user deleted, tenant null, TeacherProfile hilang/nonaktif, role canonical hilang, rollback dan permission_role invariant.
   - DILARANG menjalankan migration ini pada VPS/DB aktif tanpa inventory, backup terverifikasi, clone-upgrade, rollback rehearsal, preflight dan persetujuan eksplisit.
10. Migration 2026_11_22_000003_migrate_legacy_roles_to_canonical_roles.php — a68326f9eb6e9df516efe44d3f70b0074e2517da
   - up() memanggil service migrasi secara otomatis; bukan sekadar membuat tabel catatan. Risiko produksi tinggi bila dijalankan tanpa gate.

### Kontrak produk yang harus mengikat Admin
- Hanya tiga portal: ADMIN_MADRASAH, GURU, SISWA; tidak ada role/portal permanen OPERATOR, KAMAD, PROKTOR.
- Admin mengelola administrasi, master data, akun Guru/Siswa, rombel, mata pelajaran, penugasan Guru dan administrasi ujian terpusat.
- Admin tidak otomatis memperoleh akses Bank Soal, exam authoring, answer key, jawaban, nilai.
- Proktor adalah penugasan sementara pada akun Guru, hanya untuk ujian terpusat dan Room yang ditugaskan.
- Model ujian terbaru: tanpa ExamSession/session number/wave; Room, roster/placement dan ProctorAssignment langsung terkait Exam+Room.
- Benturan assignment Guru harus dicegah server-side dengan interval [starts_at, ends_at), lintas Exam/Room dan aman terhadap concurrent requests.
- Proyek adalah revisi atas instalasi VPS yang telah ada, bukan greenfield. Data, APP_KEY, konfigurasi privat, histori dan storage wajib dipertahankan.
- Scope Web CBT; Android dan Web Super Admin ditunda hingga Juni 2027.

## DESAIN / URUTAN PERBAIKAN
P0-A: Tetapkan baseline dan exact-role authorization untuk login, route, dashboard, Livewire actions dan policies.
P0-B: Review dan amankan role migration dengan fail-closed inventory; jangan jalankan di produksi.
P0-C: Lengkapi User Management (edit, active/inactive, reset password, audit, duplicate identity) dengan tests.
P0-D: Lengkapi penugasan Guru ke kelas/mapel/rombel, tenant-aware, berlaku waktu dan audit.
P0-E: Audit master akademik/kurikulum versioned dan seluruh action.
P0-F: Batasi menu/route Admin dari CBT authoring, bank soal, answer key, answers dan scores.
P0-G: Regression tests untuk tenant isolation, exact-one role, CSRF/session, stale TenantContext, concurrency, upload, audit, responsive/browser dan error handling.
P0-H: Upgrade compatibility dari VPS baseline hanya setelah ada bukti read-only source/schema/migration ledger. Tidak boleh menebak.

## IMPLEMENTASI
- Membuat branch kerja koordinator dari Paket 1 exact SHA:
  work/coordinator-web-admin-audit-20261122
- Menambahkan dokumen audit ini saja.
- Tidak mengubah file aplikasi, migration, database, secrets, VPS atau branch main.
- Backup pra-perubahan source runtime tidak diperlukan karena tidak ada file runtime yang diedit. Basis commit Paket 1 tetap utuh.

## PENGUJIAN
- Pemeriksaan GitHub refs, compare commits, commit metadata dan source blobs: selesai.
- Test runtime oleh koordinator pada sesi ini: TIDAK DIJALANKAN.
- PHPUnit/MySQL 8 concurrency/browser/UAT: belum dijalankan pada exact HEAD basis audit ini.
- CI terdahulu di atas hanya berlaku pada exact SHA yang tertulis pada checkpoint masing-masing.
- VPS source/schema/migration ledger: belum dapat diverifikasi tanpa akses read-only ke metadata deployment.

## KEPUTUSAN STATUS
Web Admin: NOT ACCEPTED / P0 OPEN.
Tidak ada izin merge, production migration, atau deployment.
Tahap berikutnya adalah patch terukur per ownership, backup per file, test exact-SHA di disposable runtime, lalu review integrasi. Web Guru dan Web Siswa tetap ditahan sampai Web Admin lulus acceptance.
