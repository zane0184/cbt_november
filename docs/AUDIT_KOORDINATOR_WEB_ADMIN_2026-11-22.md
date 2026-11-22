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


## ADDENDUM IMPLEMENTASI KOORDINATOR — CI BRANCH ISOLATION
Setelah audit statis, workflow isolasi WA01 di branch koordinator disiapkan agar perubahan berikutnya dapat diuji pada branch terpisah tanpa menulis ke branch Agent 3 atau main.

- Workflow: .github/workflows/wa01-alternative-isolated-runtime.yml
- Blob sebelum perubahan: c21ba66ddfd32838962a75bae584ea953a16b333
- Backup byte-exact dibuat terlebih dahulu: docs/backup/coordinator-web-admin-2026-11-22/wa01-alternative-isolated-runtime.yml.before-coordinator-branch
- Backup commit: 8ffc427c7ceac25dc98dd1f4d54aeb2e4da9ef1b
- Workflow branch filter diperluas agar mencakup work/coordinator-web-admin-audit-20261122.
- Commit perubahan workflow: c3d2c816b4fe219542ae329a2fe36370ec126491
- Blob workflow setelah perubahan: 172107b8e14a0ae1c9dfb584386facea7b789eeb
- Workflow menggunakan MySQL 8 disposable dan app DB user dengan privilege terbatas sesuai konfigurasi file; tidak diarahkan ke VPS.
- Saat pemeriksaan pertama setelah commit, endpoint Actions belum menampilkan run untuk branch ini (total_count 0). Karena itu belum ada hasil runtime PASS/FAIL; pemicu CI harus diverifikasi pada perubahan backend berikutnya atau setelah GitHub memproses event.

Perubahan ini hanya workflow CI dan backup workflow; tidak ada source aplikasi, migration, database, credential, atau deployment yang diubah. Test runtime koordinator tetap NOT RUN.


### Hasil percobaan runner setelah addendum CI
- Run #45034945940 pada exact SHA c3d2c816b4fe219542ae329a2fe36370ec126491, attempt 1: failure sebelum steps; job tidak memiliki steps.
- Satu rerun gagal dengan kondisi sama; attempt 2 juga tidak memiliki steps.
- Job IDs: 137246503960 (attempt 1), 137246664176 (attempt 2).
- Ini diklasifikasikan BLOCKED — RUNNER/EXECUTION ENVIRONMENT. Bukan hasil PHPUnit gagal dan bukan PASS. Tidak ada bukti test runtime pada source exact SHA ini.
- Jangan mengulang rerun tanpa batas. Jalur CI perlu diperiksa/diaktifkan kembali setelah ketersediaan runner GitHub pulih; sementara itu perubahan runtime tidak boleh diklaim lulus.



## ADDENDUM IMPLEMENTASI P0-A — CANONICAL ROLE GATE (22 NOV 2026)

### AUDIT
Pemeriksaan ulang source pada branch koordinator menemukan AuthWebController dan LoginIdentityResolver masih menerima KAMAD; AuthWebController mengarahkan Guru ke rute legacy /admin/exams. AdminDashboard dan UserManagement masih memakai hasRole tanpa exact-one role check. CurriculumManagement pada source branch ini ternyata sudah menggunakan exact-one role check (blob 2b613a4a...), sehingga tidak diubah.

### IMPLEMENTASI
Perubahan dibuat hanya pada branch work/coordinator-web-admin-audit-20261122. Backup source dibuat sebelum patch untuk:
- AuthWebController.php — backup blob asal 235518c4d1fa9913b3add7a02636bbfcd608d408; backup: docs/backup/coordinator-web-admin-2026-11-22/AuthWebController.php.before-canonical-role-p0
- LoginIdentityResolver.php — backup blob asal c1f56029da5b8fdb5d7f80380269637b7d1c3ebe; backup: docs/backup/coordinator-web-admin-2026-11-22/LoginIdentityResolver.php.before-canonical-role-p0
- AdminDashboard.php — backup blob asal b81ce1ea19a50db07ec439b15bec0a034f340afc; backup: docs/backup/coordinator-web-admin-2026-11-22/AdminDashboard.php.before-canonical-role-p0
- UserManagement.php — backup blob asal 9173f4b6032f43bebe9472234eb80a6176af043e; backup: docs/backup/coordinator-web-admin-2026-11-22/UserManagement.php.before-canonical-role-p0
- WebAdminAuthTest.php — backup blob asal 93a822f44f7d21d4c25913599bda2bcfe7d32e34; backup: docs/backup/coordinator-web-admin-2026-11-22/WebAdminAuthTest.php.before-canonical-role-p0
- AdminDashboardAccessTest.php — backup blob asal f63a4ea49fe98e4b1147750427e50af83e74518b; backup dibuat dari ref pra-perubahan 432ac2277b30984eff2fa504bc176b7153e206e4 di docs/backup/coordinator-web-admin-2026-11-22/AdminDashboardAccessTest.php.before-single-role-guard

Source changes:
1. AuthWebController: daftar login portal dibatasi menjadi ADMIN_MADRASAH, GURU, SISWA; redirect hanya menerima tepat satu role canonical. Akun login lama/role di luar tiga portal ditolak. Session authenticated noncanonical dihentikan saat mengunjungi login.
2. LoginIdentityResolver: identitas KAMAD tidak lagi di-resolve sebagai portal CBT; role harus tepat satu dan termasuk tiga role canonical.
3. AdminDashboard: akses mensyaratkan tepat satu role ADMIN_MADRASAH.
4. UserManagement: mount/mutation/render guard mensyaratkan tepat satu role ADMIN_MADRASAH dan permission user.manage.
5. WebAdminAuthTest: ekspektasi login ADMIN diperbarui ke /admin/dashboard; OPERATOR, KAMAD, PROKTOR ditolak.
6. AdminDashboardAccessTest: fixture Admin disederhanakan menjadi satu role; ditambah tes negatif Admin multi-role.

Tidak mengubah routes/web.php, migration, permission tables, database, branch main, VPS, .env, APP_KEY, atau deployment. Redirect Guru lama belum diubah pada tahap ini karena rute portal Guru final belum dibangun; perubahan portal Guru menunggu tahap Guru setelah Admin diterima.

### PENGUJIAN
- GitHub Actions run #45035838339 terpicu pada exact SHA 720dea52258cc0cfb0f92d5c191a740938bba08a.
- Job safe-runtime-tests berakhir failure, tetapi daftar steps tidak tersedia dan pengambilan job logs menghasilkan 404 BlobNotFound.
- Karena tidak ada log/step, status PHPUnit adalah NOT VERIFIED; bukan dinyatakan gagal karena source dan bukan PASS.
- Test runtime lokal tidak dijalankan pada sesi ini. Syntax/lint belum dapat diklaim lulus.

### STATUS
P0-A source patch awal sudah dibuat, tetapi BELUM ACCEPTED dan BELUM VERIFIED. Tindak lanjut wajib: pulihkan observabilitas runner, jalankan seluruh test terkait pada PHP 8.3 + disposable MySQL 8, periksa route boundary Guru/Admin dan tes negative multi-role/legacy roles. Jangan lanjut ke P0-B atau fitur User Management tambahan sebelum patch ini mendapat hasil test yang bisa diperiksa.



## ADDENDUM KEPUTUSAN ROLE — RETIRE KAMAD/OPERATOR (22 NOV 2026)

### DESAIN DIKUNCI
Portal akun hanya ADMIN_MADRASAH, GURU, SISWA.
- KAMAD bukan role, bukan akun portal, dan tidak memiliki dashboard/permission. Nama Kepala Madrasah adalah data identitas madrasah di pengaturan Admin, dipakai sebagai nama penanda tangan pada laporan penilaian yang diekspor Guru.
- OPERATOR bukan role/portal terpisah. Fungsi administrasi dan master data menjadi tanggung jawab ADMIN_MADRASAH.
- PROKTOR bukan role permanen; penugasan tetap melekat pada Guru per Exam + Room.

### AUDIT
- DatabaseSeeder sebelumnya masih membuat role KAMAD.
- RolePermissionBootstrapper sebelumnya mensyaratkan dan memberi permission kepada KAMAD.
- LegacyRoleMigrationService yang telah ada memetakan ADMIN dan OPERATOR menjadi ADMIN_MADRASAH, PROKTOR menjadi GURU, serta mengarsipkan role alias.
- Ada route kompatibilitas /operator/dashboard dan komponen/dashboard Kamad yang tidak lagi sesuai model tiga portal.
- Tenant belum memiliki kolom nama Kepala Madrasah; laporan PDF belum memiliki blok tanda tangan Guru/Kepala Madrasah.

### IMPLEMENTASI DI BRANCH KOORDINATOR
1. RolePermissionBootstrapper kini hanya mengelola permission role ADMIN_MADRASAH dan GURU; SISWA tetap menjadi role portal tanpa grants administrasi. DatabaseSeeder tidak lagi membuat KAMAD.
2. Test RolePermissionBootstrapper disesuaikan untuk tiga role portal dan tanpa KAMAD/OPERATOR.
3. Route /operator/dashboard dihapus; test dashboard lama diganti verifikasi tiga role portal serta 404 untuk /operator/dashboard dan /kamad/dashboard.
4. Komponen KamadDashboard.php dihapus. Salinan source dan view disimpan di docs/backup/... sebelum penghapusan; view lama tetap tersimpan sebagai backup karena operasi penghapusan view menemui konflik GitHub API.
5. Migration 2026_11_22_000004_retire_kamad_role.php + RetireKamadRoleService mengarsipkan role KAMAD dan mencabut assignments/permission pivots dengan catatan reversible:
   - Akun yang hanya memiliki KAMAD dipertahankan, role_user KAMAD dilepas, users.role menjadi UNASSIGNED sehingga tidak memperoleh akses portal.
   - Akun KAMAD yang juga memiliki tepat satu role portal canonical mempertahankan role portal tersebut.
   - Akun dengan role tambahan ambigu atau data users.role tidak konsisten membuat migration berhenti (fail closed), tidak dipaksa dikonversi.
   - Record migrasi menyimpan data untuk rollback; role lama diarsipkan sebagai ARCHIVED_KAMAD_<UUID>, bukan hard-delete, agar histori/FK tetap aman.
6. Migration 2026_11_22_000005_add_principal_name_to_tenants menambahkan principal_name.
7. Pengaturan Profil & Identitas Madrasah memiliki input Nama Kepala Madrasah. Tenant model dan Livewire menyimpan/mengaudit nilainya.
8. Export PDF hasil ujian dan laporan hasil siswa oleh Guru menampilkan blok tanda tangan “Mengetahui, Kepala Madrasah” dan “Guru Mata Pelajaran” memakai principal_name tenant dan nama Guru yang sedang mengekspor. Ekspor mandiri oleh Siswa tidak menampilkan blok tanda tangan.
9. Test baru RetireKamadRoleServiceTest mencakup arsip role, akun KAMAD-only, akun Admin+KAMAD, role ambigu fail-closed, dan rollback. TenantProfileManagementTest memverifikasi penyimpanan principal_name.

### BACKUP
Sebelum patch, backup file yang diubah dibuat di docs/backup/coordinator-web-admin-2026-11-22/ dengan suffix:
- .before-remove-kamad-operator
- .before-retire-kamad-operator
- .before-principal-signature
Source yang dihapus KamadDashboard.php memiliki backup. Route dan test juga memiliki backup.

### PENGUJIAN / BATASAN
Belum ada PHPUnit, lint, atau migrasi yang dijalankan. Runner GitHub Actions sebelumnya tidak menyediakan steps/logs dan menghasilkan BlobNotFound. Seluruh perubahan masih branch koordinator; migration hanya source code dan BELUM dijalankan pada database mana pun. Tidak ada perubahan VPS/produksi, main, APP_KEY, atau data live.
Migration 000004 harus diuji pada disposable MySQL 8 dengan fixture kasus akun KAMAD-only, KAMAD+Admin/Guru/Siswa, konflik multi-role, orphan role column, permission pivots, dan rollback sebelum disetujui untuk deployment.

### STATUS
Keputusan desain diterapkan pada source branch, tetapi belum lulus pengujian dan belum diterima. Role OPERATOR lama dipetakan menjadi ADMIN_MADRASAH oleh migration 000003 yang sudah ada. KAMAD lama diarsipkan oleh migration 000004. Tidak boleh menjalankan migration pada VPS sebelum preflight inventaris, backup terverifikasi, uji clone, rehearsal rollback, dan persetujuan eksplisit pengguna.



## ADDENDUM KEPUTUSAN ROLE KAMAD/OPERATOR & PROKTOR GURU (22 NOV 2026)

### KEPUTUSAN DESAIN DIKUNCI
Role aktif portal madrasah hanya:
1. ADMIN_MADRASAH
2. GURU
3. SISWA

KAMAD bukan role dan bukan akun portal. Nama Kepala Madrasah merupakan atribut identitas madrasah pada `tenants.principal_name`, dikelola Admin di Profil & Identitas Madrasah. Laporan PDF hasil ujian dan laporan siswa sudah membaca `principal_name` untuk blok tanda tangan “Mengetahui, Kepala Madrasah”, berpasangan dengan nama Guru yang sedang membuat/mengeluarkan laporan. Data ini tidak memberi hak login atau akses hasil/ujian kepada Kamad.

OPERATOR bukan role. Migrasi legacy `LegacyRoleMigrationService` memetakan akun OPERATOR lama menjadi ADMIN_MADRASAH, dengan pemeriksaan konflik, transaksi, pencatatan rollback, dan arsip role lama. Akun tidak dihapus. KAMAD lama ditangani `RetireKamadRoleService`: assignment KAMAD dilepas dan role diarsipkan; jika akun hanya memiliki role KAMAD maka akun tetap ada tanpa role portal (users.role menjadi UNASSIGNED), sedangkan role portal canonical yang sah dipertahankan. Konflik multi-role di luar daftar canonical harus gagal tertutup dan direkonsiliasi manual.

PROKTOR bukan role permanen. Akun Guru dapat diberi penugasan Proktor sementara yang dibatasi exam, tenant, rentang waktu, dan audit log. Penugasan hanya memberi monitoring ujian yang ditugaskan, tidak memberi akses jawaban, nilai, token, atau kewenangan mengubah ujian.

### AUDIT SOURCE
- `DatabaseSeeder.php` dan `RolePermissionBootstrapper.php` sudah hanya membentuk role ADMIN_MADRASAH dan GURU pada bootstrapper; katalog portal tiga role juga diverifikasi oleh `RoleDashboardVerificationTest`.
- Migration `2026_11_22_000003_migrate_legacy_roles_to_canonical_roles.php` mencakup OPERATOR -> ADMIN_MADRASAH dan PROKTOR -> GURU; role lama diarsipkan, bukan dihapus fisik agar histori/rollback tetap tersedia.
- Migration `2026_11_22_000004_retire_kamad_role.php` mengarsipkan KAMAD dan merekam pelepasan role.
- Migration `2026_11_22_000005_add_principal_name_to_tenants.php`, model Tenant, dan UI Profil Madrasah sudah menyediakan principal_name.
- ReportController sudah mengirim `$tenant->principal_name` sebagai `principalName` ke kedua template PDF (hasil ujian dan hasil siswa).
- Ditemukan celah lama: AssessmentPolicy, ExamPolicy, ExamAttemptPolicy, ReportPolicy masih memberi jalur tertentu untuk KAMAD/OPERATOR; penugasan Proktor masih mensyaratkan role PROKTOR permanen.

### IMPLEMENTASI BRANCH KOORDINATOR
- Menghapus otorisasi KAMAD/OPERATOR dari AssessmentPolicy, ExamPolicy, ExamAttemptPolicy, ReportPolicy.
- Penugasan Proktor di UI dan service sekarang memilih akun GURU dengan tepat satu role GURU dan TeacherProfile aktif pada tenant yang sama.
- ExamPolicy.monitor memberi Guru akses monitoring melalui cakupan mengajar atau penugasan Proktor aktif yang spesifik ujian; akses detail jawaban/nilai tetap hanya melalui TeacherAccess, sehingga penugasan Proktor tidak menjadi akses nilai.
- Guard Profil Madrasah dan pengelolaan penugasan Proktor mewajibkan tepat satu role ADMIN_MADRASAH.
- UI menampilkan “Guru yang ditugaskan sebagai Proktor” agar jelas bahwa Proktor adalah tugas sementara, bukan role akun.
- Test ProctorAssignmentSecurityTest dipindah dari role PROKTOR ke akun GURU + TeacherProfile. Test profil madrasah memverifikasi principal_name tersimpan.
- Backup dibuat sebelum patch untuk ProctorAssignmentManagement, ExamProctorAssignmentService, ProctorAssignmentSecurityTest, proctor-assignment-management view, TenantProfileManagement, TenantProfileManagementTest, dan empat Policy. Lokasi backup berada di docs/backup/coordinator-web-admin-2026-11-22/ dengan suffix yang menjelaskan kondisi sebelum patch.

### PENGUJIAN DAN BATAS
Belum ada hasil PHPUnit/lint yang dapat diklaim. Runner GitHub Actions sebelumnya tidak memberikan steps/log yang dapat dibaca. Perubahan ini hanya branch kerja, belum merge, belum deploy, dan belum menjalankan migration pada VPS/database mana pun. Role legacy tidak boleh diarsipkan pada database aktif sampai inventory akun, backup terverifikasi, clone DB test, hasil migrasi, dan rollback diuji serta ada persetujuan eksplisit.



### TINDAK LANJUT KONTROL MUTU (22 NOV 2026)
- Pemeriksaan ulang menemukan satu assertion sisa yang tidak konsisten pada `TenantProfileManagementTest`: nilai yang diset adalah "H. Asim, M.A.", tetapi ada assertion lama yang masih mengharapkan nilai lain. Assertion yang salah dihapus setelah backup baru dibuat; assertion terhadap nilai yang benar tetap dipertahankan.
- Template dashboard Kamad yang tidak lagi memiliki komponen/route aktif dihapus setelah backup sebelumnya diverifikasi. Tidak menghapus template tanda tangan PDF; bagian tanda tangan tetap menggunakan data identitas `principal_name`.
- Workflow isolasi diperluas agar menjalankan `RoleDashboardVerificationTest`, `RetireKamadRoleServiceTest`, dan `ProctorAssignmentSecurityTest` ketika runner tersedia. Ini menutup gap bahwa perubahan role dan assignment sementara harus masuk regresi CI, bukan hanya test profil.
- Pemeriksaan GitHub Actions terbaru untuk SHA `8196edd91a70f9d176a70d8bcc94f32951210a51`: job gagal dalam sekitar 3 detik setelah mulai; check run tidak memiliki title/summary/text, `steps=null`, `logs_url=null`, tidak ada artifacts, dan unduhan log menghasilkan BlobNotFound. Bukti ini menunjukkan job tidak menyediakan eksekusi test yang bisa diverifikasi; bukan bukti kegagalan PHPUnit dan bukan PASS.
- Koreksi assertion test profil, penghapusan template Kamad yatim, dan perluasan workflow berada di branch kerja saja. Tidak ada perubahan ke main, VPS, atau database.



### HARDENING ROLE TUNGGAL PADA PERMUKAAN ADMIN/GURU
Audit lanjutan menemukan beberapa komponen Admin yang sebelumnya hanya memeriksa keberadaan role ADMIN_MADRASAH, bukan memastikan tepat satu role. Komponen Guru Bank Soal/Manajemen Ujian juga hanya memeriksa role Guru dan mengecualikan Admin, sehingga akun multi-role non-Admin tertentu masih berpotensi masuk.
- `AcademicManagement` dan `StudentRombelManagement` kini mensyaratkan tepat satu role ADMIN_MADRASAH.
- `QuestionBank` dan `ExamManagement` kini mensyaratkan tepat satu role GURU.
- Sebelum setiap perubahan dibuat backup source di folder backup branch.
- `AdminUserManagementScopeTest` diperbaiki: test daftar pengguna sekarang memakai Admin satu-role (sesuai kebijakan), dan ditambah regresi yang memastikan akun Admin dengan role Guru stale ditolak oleh Dashboard, User Management, Academic Management, Student-Rombel, Profil, Penugasan Proktor, Bank Soal, dan Manajemen Ujian.
- Assertion yang bertentangan pada `TenantProfileManagementTest` sudah diperbaiki; nilai principal yang diinput "H. Asim, M.A." kini menjadi nilai yang diuji.

### STATUS WORKFLOW SETELAH PENAMBAHAN REGRESI
Workflow `wa01-alternative-isolated-runtime.yml` sekarang mencantumkan test role canonical, retirement Kamad + rollback, dan penugasan Proktor sementara pada Guru. Namun setiap Actions run terbaru masih berakhir sebelum step berjalan; check run tidak memberikan summary, steps, logs, maupun artifact. Oleh sebab itu belum ada hasil PHP lint/PHPUnit dari perubahan terakhir. Jangan tandai hardening sebagai lulus sampai ada runner yang benar-benar mengeksekusi langkah tersebut.


## ADDENDUM KONTROL UI PENUGASAN PROKTOR — EXACT SINGLE GURU (22 NOV 2026)

### AUDIT
Pemeriksaan ulang source pada branch koordinator menemukan:
- `ExamProctorAssignmentService::assign()` sudah menolak akun yang tidak memiliki tepat satu role GURU dan tidak memiliki TeacherProfile aktif pada tenant yang sama.
- `ProctorAssignmentManagement::assign()` dan `render()` sebelumnya hanya memfilter akun yang memiliki role GURU, sehingga akun multi-role masih dapat muncul sebagai kandidat di UI walaupun service akan menolaknya.
- Ini merupakan inkonsistensi pilihan UI terhadap validasi server-side; bukan bukti bahwa assignment multi-role berhasil disimpan.

### DESAIN
Daftar kandidat dan lookup saat submit harus sama-sama mensyaratkan tepat satu role, yaitu GURU, serta TeacherProfile aktif pada tenant yang sama. Validasi service tetap menjadi otoritas terakhir dan tidak dihapus.

### IMPLEMENTASI
Sebelum perubahan, backup dibuat dan diverifikasi melalui kesamaan Git blob SHA:
- `ProctorAssignmentManagement.php`: source sebelum patch blob `c084d7b240b118f99c5f259d1658f3bd4b9cf8d6`; backup `docs/backup/coordinator-web-admin-2026-11-22/ProctorAssignmentManagement.php.before-exact-guru-role-ui-filter`.
- `ProctorAssignmentSecurityTest.php`: source sebelum patch blob `10b279826a4d56a740dadc557609bf81496ea3fb`; backup `docs/backup/coordinator-web-admin-2026-11-22/ProctorAssignmentSecurityTest.php.before-exact-guru-role-ui-filter`.
- Dokumen audit sebelum addendum juga dibackup pada `docs/backup/coordinator-web-admin-2026-11-22/AUDIT_KOORDINATOR_WEB_ADMIN_2026-11-22.md.before-exact-guru-role-ui-filter`; blob asal `c37decb4f266a0089868172e896de815a52e6b28`.

Perubahan:
1. Query kandidat pada `assign()` ditambah `has('roles', '=', 1)`.
2. Query kandidat pada `render()` ditambah syarat yang sama.
3. `ProctorAssignmentSecurityTest` ditambah test negatif yang membuat akun dengan role GURU + ADMIN_MADRASAH dan memastikan nama akun tersebut tidak tampil pada daftar kandidat.
4. Service tetap mempertahankan exact-role validation; tidak ada perubahan pada policy, migration, skema, atau data.

### PENGUJIAN
- Pemeriksaan isi source dan test setelah commit: dilakukan melalui fetch ulang branch.
- Backup diverifikasi dari blob SHA asal yang sama dengan source pra-patch.
- PHPUnit, PHP lint, dan test runtime: BELUM DIJALANKAN. Tidak ada klaim test lulus.
- Belum ada perubahan VPS, database, migration, branch main, merge, atau deployment.

### STATUS
Patch UI exact-role dibuat pada branch koordinator dan menunggu verifikasi runtime. P0 Admin tetap NOT ACCEPTED sampai rangkaian tes PHP 8.3 + MySQL 8 disposable dan regression security dapat dijalankan serta diperiksa.


### ADMIN DATA SCOPE — IDENTITAS TUNGGAL DAN AKUN NONAKTIF
- AdminDashboard kini menghitung Guru/Siswa hanya bila akun memiliki tepat satu role yang sesuai.
- StudentRombelManagement hanya menampilkan akun Siswa aktif dengan tepat satu role SISWA; aksi penempatan memeriksa tenant, status akun, dan role tunggal kembali di server.
- UserManagement mengecualikan akun dengan deleted_at terisi dari daftar aktif.
- Regresi AdminUserManagementScopeTest ditambah untuk memastikan akun multi-role Guru+Siswa tidak dihitung sebagai Guru/Siswa dan akun Siswa soft-deleted tidak muncul pada dashboard, daftar pengguna, maupun pemilih rombel.
- Audit skema menemukan unique constraint lama pada pasangan (rombel_id, student_id) masih ada setelah hardening tenant 230003 menambahkan index tenant lookup dan composite foreign keys. Karena itu pencegahan duplikasi membership sudah memiliki lapisan DB selain pemeriksaan transaksi pada Livewire; tidak dibuat migration unik baru.
- Test/runtime belum dieksekusi karena GitHub Actions gagal sebelum steps; semua hasil tetap NOT VERIFIED sampai runner tersedia.


# Audit Gap Web Admin — 22 November 2026
Repository: zane0184/cbt_november
Branch: work/coordinator-web-admin-audit-20261122
Tree SHA: 76e387504a1451ed25883da6380b97098ec1c9a0
Acuan: docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md (main, blob 68150acf7b9abf130776bee27444c99889ce53a5)

## AUDIT
Inventory source aktual menunjukkan komponen Admin:
- backend-laravel/app/Livewire/Admin/AcademicManagement.php
- backend-laravel/app/Livewire/Admin/AdminDashboard.php
- backend-laravel/app/Livewire/Admin/CurriculumManagement.php
- backend-laravel/app/Livewire/Admin/ExamManagement.php
- backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php
- backend-laravel/app/Livewire/Admin/QuestionBank.php
- backend-laravel/app/Livewire/Admin/StudentRombelManagement.php
- backend-laravel/app/Livewire/Admin/TenantProfileManagement.php
- backend-laravel/app/Livewire/Admin/UserManagement.php

View Admin:
- backend-laravel/resources/views/livewire/admin/academic-management.blade.php
- backend-laravel/resources/views/livewire/admin/admin-dashboard.blade.php
- backend-laravel/resources/views/livewire/admin/curriculum-management.blade.php
- backend-laravel/resources/views/livewire/admin/exam-management.blade.php
- backend-laravel/resources/views/livewire/admin/proctor-assignment-management.blade.php
- backend-laravel/resources/views/livewire/admin/question-bank.blade.php
- backend-laravel/resources/views/livewire/admin/student-rombel-management.blade.php
- backend-laravel/resources/views/livewire/admin/tenant-profile-management.blade.php
- backend-laravel/resources/views/livewire/admin/user-management.blade.php

Route utama: backend-laravel/routes/web.php

Test feature kandidat:
- backend-laravel/tests/Feature/AcademicPeriodManagementTest.php
- backend-laravel/tests/Feature/AdminCbtBoundaryTest.php
- backend-laravel/tests/Feature/AdminDashboardAccessTest.php
- backend-laravel/tests/Feature/AdminUiVerificationTest.php
- backend-laravel/tests/Feature/AdminUserManagementScopeTest.php
- backend-laravel/tests/Feature/CurriculumIntegrityTest.php
- backend-laravel/tests/Feature/CurriculumVersioningTest.php
- backend-laravel/tests/Feature/Fase1cMultiCurriculumIntegrityTest.php
- backend-laravel/tests/Feature/LegacySuperAdminInventoryCommandTest.php
- backend-laravel/tests/Feature/MultiTenantAuthTest.php
- backend-laravel/tests/Feature/Phase7DCurriculumAchievementTest.php
- backend-laravel/tests/Feature/Phase7DReportingTest.php
- backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php
- backend-laravel/tests/Feature/QuestionImportTest.php
- backend-laravel/tests/Feature/TenantIsolationTest.php
- backend-laravel/tests/Feature/TenantProfileManagementTest.php
- backend-laravel/tests/Feature/UserSecurityTest.php
- backend-laravel/tests/Feature/WebAdminAuthTest.php

### Status matriks awal (statis; belum runtime-verified)
| Kelompok spesifikasi | Status | Bukti awal / gap |
|---|---|---|
| Dashboard | EXISTS BUT BLOCKED | AdminDashboard, view dan AdminDashboardAccessTest ada; dashboard menggunakan data riil untuk sebagian kartu. Belum terbukti seluruh ujian/status peserta/aktivitas sesuai master dan hasil runtime belum tersedia. |
| Profil/identitas madrasah | EXISTS BUT BLOCKED | TenantProfileManagement + test + principal_name; kelengkapan seluruh field, logo, favicon, branding, kop dan preview belum dibuktikan. |
| Data Guru/Siswa | PARTIAL | UserManagement membuat dan mendaftar akun dengan filter/pagination. Dari source yang diperiksa belum tampak edit, aktif/nonaktif, reset password, detail, riwayat, import/export, template. |
| Akun dan akses | PARTIAL | Create account dan exact-one role guard; belum terlihat workflow disable/reset/force password/session revoke. |
| Import/export Guru/Siswa | MISSING / BLOCKED | Tidak ditemukan komponen Admin import/export khusus Guru/Siswa dalam inventory awal. QuestionImportTest adalah import soal, bukan bukti import data Guru/Siswa. Perlu telusur service/job/template terpisah. |
| Data akademik | PARTIAL | AcademicManagement, StudentRombelManagement, models/migrations and tests exist. Full create/edit/archive/history, mutasi beralasan, mapel dan teacher assignment need action-by-action verification. |
| Kurikulum | PARTIAL | CurriculumManagement dan versioning/integrity tests exist; registration, structure, CP/TP/ATP mapping/history completeness not yet flow-verified. |
| Manajemen ujian | PARTIAL / BOUNDARY REVIEW | Route /admin/questions and /admin/exams use Teacher-owned components. Admin navigation hides them but routes themselves are in common auth/tenant/license group; exact backend denial to Admin must be proven. |
| Ruang ujian | MISSING / BLOCKED | No Admin room-management component found in active Livewire/Admin tree. Check existing non-Livewire services or other namespace before final status. |
| Penempatan peserta per ruang | MISSING / BLOCKED | No active Admin placement component found. No ExamSession lifecycle to be introduced; design against concurrent Exam + room placement only. |
| Penugasan Proktor | EXISTS BUT BLOCKED | ProctorAssignmentManagement and ProctorAssignmentSecurityTest exist; scope/time/overlap/room isolation must be fully integration-tested. |
| Monitoring CBT | PARTIAL / BLOCKED | Monitoring API/controller and ExamPolicy exist; Admin-specific monitoring menu/action scope, privacy and operational-action authorization not established by current Admin menu inventory. |
| Laporan | PARTIAL | ReportController and Teacher result components exist; Admin academic/attendance/exam aggregate reports, PDF/XLSX filter/export and role-specific policies not proven. |
| Audit & keamanan | PARTIAL | AuditLog model/service, recent activity card, and security tests exist. No dedicated Admin audit/security UI identified. Full login/denied/sensitive-action coverage not proven. |
| Pengaturan web | MISSING / PARTIAL | Tenant profile exists; General/branding/logo/favicon/printhead/theme/notification/preferences pages not identified. |
| UX/search/pagination/accessibility | PARTIAL | User listing search/filter/pagination; other components need per-page inspection and browser/responsive/accessibility tests. |

### Prinsip klasifikasi
- Status "EXISTS & VERIFIED" tidak digunakan tanpa alur UI→database dan tes runtime yang dapat diperiksa.
- "MISSING / BLOCKED" bermakna belum ada bukti dari inventory source saat ini; sebelum menutup sebagai missing, periksa seluruh app/services/jobs/imports/routes/tests.
- Tidak ada bukti dari tree statis yang menyatakan kesiapan produksi.

## DESAIN
Urutan audit/perbaikan Web Admin tetap P0 dan tidak mengaktifkan portal Guru/Siswa lebih jauh:
1. Login/session dan exact role/tenant context.
2. Batas route/policy Admin vs Guru (terutama QuestionBank, ExamManagement, results/export/analytics).
3. Selesaikan Data Madrasah + pengaturan branding/kop berdasarkan kebutuhan master.
4. Lengkapi Guru/Siswa + akun dan akses.
5. Lengkapi import/export Guru/Siswa.
6. Lengkapi akademik, penugasan Guru, kurikulum.
7. Ruang/placement dan assignment Proktor sesuai Exam serentak (tanpa ExamSession/SessionRoom operational model).
8. Monitoring/reporting dan audit-security UI.
9. UX, browser/responsive, UAT.

## IMPLEMENTASI
Belum ada source aplikasi diubah pada checkpoint audit gap awal ini. Tidak ada migration/database/VPS/deployment/main branch change. Langkah selanjutnya hanya setelah pemeriksaan isi file satu per satu dan backup tepat sebelum tiap patch.

## PENGUJIAN
- GitHub recursive tree statis: berhasil; Tree SHA 76e387504a1451ed25883da6380b97098ec1c9a0.
- Route/view/component/test files terinventaris.
- PHPUnit, PHP lint, MySQL 8 integration/concurrency, browser/E2E, UAT: NOT RUN / NOT VERIFIED.
- Tidak ada klaim siap produksi.

## Batas penting
- Gunakan hanya spesifikasi master dan source GitHub aktif.
- Ujian serentak; jangan menambah ExamSession/SessionRoom/session number atau lifecycle sesi.
- Tidak ada role/portal Operator/Kamad/Proktor permanen.
- Tidak ada perubahan main, merge, deployment, atau migration produksi.
- Web Admin belum diterima.

# Implementasi P0 — Identitas Guru opsional — 22 November 2026

## AUDIT
- Master spec §4.1 menyatakan NIP/NUPTK opsional bila tidak dimiliki dan tidak mewajibkan NIP untuk semua Guru.
- Source sebelum patch menolak pembuatan akun Guru apabila NIP dan NIK kosong. Ini membuat NIK seolah menjadi syarat pengganti NIP, padahal NIK hanya boleh diproses bila ada dasar dan perlindungan.
- View juga menyatakan Guru wajib mengisi minimal NIP atau NIK. Perilaku dan teks UI tidak sesuai spesifikasi.
- Model User mengalokasikan username tenant-unique bila username kosong; regresi mengisi username eksplisit agar identitas login dapat dipastikan.

## DESAIN
- Hapus syarat NIP/NIK pada create Guru.
- Pertahankan validasi format dan uniqueness untuk NIP/NIK apabila nilai memang diisi.
- Pertahankan role tunggal, tenant scope, password, transaksi, pembuatan TeacherProfile, dan audit USER_CREATED.
- Tandai NIP/NIK opsional pada UI.
- Tambahkan regresi yang membuat Guru dengan username dan password tetapi tanpa NIP/NIK, lalu memverifikasi role, profile, nilai null dan audit.

## IMPLEMENTASI
- UserManagement.php: menghapus validasi bisnis yang mewajibkan NIP atau NIK.
- user-management.blade.php: label bantuan diperbarui menjadi NIP/NIK opsional.
- AdminUserManagementScopeTest.php: menambah test_admin_can_create_guru_without_nip_or_nik.
- Backup source dibuat sebelum perubahan:
  - docs/backup/coordinator-web-admin-2026-11-22/guru-identitas-opsional-p0/UserManagement.php.before-guru-identity-optional — source blob SHA 964527ad7b955f4a48f705a303269775c9918530
  - docs/backup/coordinator-web-admin-2026-11-22/guru-identitas-opsional-p0/user-management.blade.php.before-guru-identity-optional — source blob SHA 22571e98a9196c6c780d2c15d2d9e9d075234103
  - docs/backup/coordinator-web-admin-2026-11-22/guru-identitas-opsional-p0/AdminUserManagementScopeTest.php.before-guru-identity-optional — source blob SHA 08d99bd17e7920eed0638f193906137598339502
- Backup laporan audit: docs/backup/coordinator-web-admin-2026-11-22/AUDIT_KOORDINATOR_WEB_ADMIN_2026-11-22.md.before-guru-identity-optional — source blob SHA bf51d9e5355aa7a2f3a755bbd34d18b4fb2312b7
- Commit source: 05e51dd2129cf100d87d78a5db900c9ec5c177a0, 174e06f720d52cf05c10f6794172aebdff830e3b, 922657ab81caf36f0964cc7e914bddb7cedc216c, e0d0e24b2071c954fdfae892ad0c69c367061de5.

## PENGUJIAN
- PHPUnit test baru: NOT RUN.
- PHP lint: NOT RUN.
- GitHub Actions run #495–#497 untuk tiga commit pertama berstatus failure; job tidak menampilkan steps dan runner kosong. Run #494 dicoba ulang (attempt 2), tetap failure tanpa steps dan runner kosong.
- Tidak ada bukti runtime bahwa patch lulus. Status patch: IMPLEMENTED — NOT VERIFIED.

## STATUS DAN BATAS
- Tidak ada migration, database, VPS, main branch, merge, atau deployment yang diubah.
- Web Admin tetap belum memenuhi acceptance.
- Langkah berikutnya: pulihkan bukti CI/runtime, lalu lanjutkan gap P0 sesuai urutan master spec. Jangan menambah fitur di luar Web Admin.
# Implementasi P1 — Edit akun dan reset password Guru/Siswa — 22 November 2026

## AUDIT
- Spesifikasi master §4.1–4.3 mewajibkan CRUD/detail, pengelolaan akun/username, reset password, dan riwayat perubahan Guru/Siswa.
- UserManagement sebelumnya hanya menyediakan daftar dan create. Tidak ada alur edit atau reset password.
- users.email sudah nullable melalui migration 2026_11_11_000003_add_login_identity_fields.php; username tenant-unique tersedia. TeacherProfile dan StudentProfile memiliki soft delete; users juga memiliki deleted_at tetapi model User tidak menggunakan SoftDeletes.
- Tidak ditemukan kolom status aktif/nonaktif pada schema users. Soft-delete tidak boleh dipakai untuk mewakili status nonaktif karena semantiknya berbeda dan berisiko merusak histori/relasi. Status akun ditahan sebagai desain/schema gap tersendiri.

## DESAIN
- Tambahkan edit identitas akun Guru/Siswa dengan query target tenant-scoped dan exact single-role; edit profil identitas yang sesuai role.
- Tambahkan reset password dengan validasi minimal 14 karakter, tidak pernah menampilkan atau mencatat password, dan rotasi remember_token untuk memutus mekanisme remember-me lama.
- Setiap mutasi memakai transaksi, row lock, re-validasi target, dan audit event tanpa credential.
- Belum mengimplementasikan aktif/nonaktif sampai desain kolom, login enforcement, session revocation, query akun, dan histori disepakati melalui pemeriksaan source serta test lintas alur.

## IMPLEMENTASI
- backend-laravel/app/Livewire/Admin/UserManagement.php: menambah manageableUserQuery tenant/role scoped, openEditModal/updateUser, openResetPasswordModal/resetUserPassword, transaksi + lockForUpdate, audit USER_UPDATED dan USER_PASSWORD_RESET.
- backend-laravel/resources/views/livewire/admin/user-management.blade.php: menambah tombol Edit/Reset Password dan modal form terkait.
- backend-laravel/tests/Feature/AdminUserManagementScopeTest.php: menambah regresi edit+reset password dan penolakan target tenant lain.
- Backup pra-patch tersedia di docs/backup/coordinator-web-admin-2026-11-22/user-management-edit-reset-p1/ untuk ketiga file. Blob source sebelum patch: UserManagement.php 964527ad7b955f4a48f705a303269775c9918530; view 22571e98a9196c6c780d2c15d2d9e9d075234103; test 08d99bd17e7920eed0638f193906137598339502.
- Commit implementasi: b16d61a8bfa353377b9acabb499de3333ccee798; UI: ab93091c040bda245bd901f6a16bf5339c3ac201; test: 95735b4f1c637e9ef8434776082bb5c050080d57; hardening template/role context: ee2d811bf493d897be411773a91bc5f8724ccb08 dan 7a0fa60e7cda06b22c86eb608a6a74189f13edf2.
- Backup laporan/checkpoint pra-update tersedia pada docs/backup/coordinator-web-admin-2026-11-22/ dengan akhiran .before-user-edit-reset-p1.

## PENGUJIAN
- Source dan test baru dibaca ulang dari GitHub. Backup source pra-patch telah diverifikasi tersedia.
- PHPUnit: NOT RUN. PHP lint: NOT RUN. MySQL 8/InnoDB integration: NOT RUN.
- GitHub Actions WA-01 runs #499–#502 pada commit implementasi terkait semuanya failure. Run #502 job 137270555915 berstatus completed/failure dengan steps kosong; runner_name kosong pada payload job sebelumnya. Ini bukan bukti bahwa test code gagal; runner belum menjalankan langkah yang dapat diamati.
- Status: IMPLEMENTED — NOT VERIFIED.

## BATAS
- Tidak ada migration, database produksi, VPS, main branch, merge, atau deployment yang diubah.
- Web Admin tetap NOT ACCEPTED. Belum ada bukti E2E, keamanan penuh, UAT, atau acceptance.
