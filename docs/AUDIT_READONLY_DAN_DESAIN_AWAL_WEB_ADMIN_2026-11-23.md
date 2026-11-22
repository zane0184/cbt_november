# AUDIT READ-ONLY DAN DESAIN AWAL — WEB ADMIN MADRASAH
Tanggal: 23 November 2026 (WIB)
Repository: https://github.com/zane0184/cbt_november
Branch audit: `audit/web-admin-readonly-20261123`
Baseline source yang diperiksa: PR #5 head `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`
Spesifikasi/keputusan: PR #6 dan paket handover PR #7; branch dokumen `docs/web-admin-agent-handover-20261121`
Status dokumen: AUDIT READ-ONLY + DESAIN AWAL; belum ada perubahan source aplikasi.

## 1. Ringkasan keputusan

Web Admin belum memenuhi spesifikasi final. Source yang ada adalah fondasi parsial: dashboard, manajemen pengguna, rombel, akademik, CP/TP, dan penugasan Proktor sudah memiliki komponen; beberapa operasi lain baru tersedia sebagai API; sebagian besar modul administratif belum mempunyai UI.

Temuan paling penting:
1. Hak Admin masih terlalu luas terhadap CBT Guru. Permission bootstrap memberikan `exam.monitor`, `exam.close`, `exam.token`, `exam.grade`, `exam.reopen`, dan permission CBT lain. `ExamPolicy::monitor` mengizinkan Admin melakukan monitoring; `ExamPolicy::close` juga mengizinkan Admin menutup ujian. Ini bertentangan dengan batas kewenangan Admin yang disepakati.
2. Admin masih dapat membuka Bank Soal melalui route `/admin/questions` dan permission `question.view`; test saat ini bahkan mengharapkan route tersebut dapat diakses Admin.
3. Admin dan Operator masih diperlakukan sebagai dua role/identitas berbeda di seeder, route, dashboard, permission bootstrap, dan test. Keputusan produk menyatakan Admin = Operator: satu portal, satu dashboard, satu permission set; label utama Admin Madrasah.
4. Modul penugasan Proktor masih mensyaratkan role permanen `PROKTOR`. Keputusan terbaru menyatakan semua Guru aktif dapat dipilih dan konteks Proktor berasal dari assignment/jadwal aktif, bukan role permanen.
5. Tiga impor Excel wajib (Siswa, Guru, Mata Pelajaran), promosi/tinggal kelas, kelulusan/alumni, identitas madrasah, laporan administratif, backup/restore, profil Admin, dan kesehatan sistem belum mempunyai UI Admin yang terbukti.
6. Mutasi pada beberapa komponen Admin belum menulis audit log, walaupun tabel dan service audit tersedia.

Kesimpulan: **Web Admin BELUM SELESAI, BELUM LAYAK DITUTUP, dan BELUM SIAP PRODUKSI.**

## 2. Audit sumber dan batas pemeriksaan

### 2.1 Pull request dan baseline
- PR #5: OPEN / Draft / Unmerged; branch `fix/web-portal-usability-rbac-20261120`; head `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`.
- PR #6: OPEN / Draft / Unmerged; spesifikasi induk Web Admin.
- PR #7: OPEN / Draft / Unmerged; paket handover dan register keputusan.
- Source aktual yang diperiksa untuk audit ini adalah tree dan file pada SHA PR #5 di atas. Status/isi historis dari dokumen handover tidak dianggap bukti implementasi.
- Tree pada baseline berisi 526 file. Pemeriksaan path menemukan 180 file yang terkait area backend/routes/migrations/tests yang perlu ditelusuri lebih lanjut. Komponen Livewire yang terlihat langsung terkait Admin: `OperatorDashboard`, `UserManagement`, `StudentRombelManagement`, `AcademicManagement`, `CurriculumManagement`, `ExamManagement`, `QuestionBank`, dan `ProctorAssignmentManagement`.

### 2.2 Metode
READ-ONLY: membaca tree Git, route, Livewire, Blade, model, policy, service, migration, seeder, dan test. Tidak ada file source aplikasi yang diubah. Tidak menjalankan migration, tidak menyentuh database produksi, VPS, .env, atau deployment.

**Status pengujian pada audit ini:** seluruh temuan di bawah adalah hasil inspeksi statis. Test browser/E2E dan runtime UI tidak dijalankan pada tahap ini. Test yang ada di repository tidak otomatis dianggap PASS hanya karena file test tersedia.

## 3. Matriks audit fitur

| ID | Modul | Status baseline | Bukti source utama | Gap/risiko utama |
|---|---|---|---|---|
| ADM-001 | Dashboard eksekutif | ADA TAPI BELUM LENGKAP | `app/Livewire/Operator/OperatorDashboard.php`; `resources/views/livewire/operator/operator-dashboard.blade.php` | Dashboard berlabel Operator; tidak menampilkan identitas/logo madrasah dan semester aktif; belum ada kesehatan sistem/aktivitas administratif; total siswa/guru dihitung dari role saja, bukan validasi profil/status aktif; rombel/mapel dihitung tanpa filter status aktif; ada shortcut ke modul operasional ujian. |
| ADM-002 | Identitas madrasah | BACKEND ONLY | `Tenant` model dan migration `0000_01_01_000000_create_tenants_table.php` | Kolom nama, NSM, NPSN, alamat, telepon, email, logo_path tersedia; belum ditemukan halaman Admin untuk edit/preview/upload, validasi aset, histori perubahan, kop dokumen, atau identitas Kamad. |
| ADM-003 | Tahun ajaran/semester | BACKEND ONLY / UI SANGAT TERBATAS | `AcademicYear`, `Semester`, `academic-management.blade.php` | Model dan tabel tersedia; UI hanya membaca tahun ajaran untuk pilihan Rombel. Belum ada CRUD periode, aktivasi semester, validasi periode aktif tunggal, histori dan alur transisi. |
| ADM-004 | Kelas/fase/rombel/wali kelas | ADA TAPI BELUM LENGKAP | `AcademicManagement.php`, `StudentRombelManagement.php` dan view terkait | Ada daftar/penambahan Rombel, mapel, tingkat/fase, serta penempatan siswa. Belum ada pengelolaan wali kelas yang lengkap, edit/status/histori Rombel, promosi, tinggal kelas, atau riwayat akademik terpadu. |
| ADM-005 | Data Guru administratif | ADA TAPI BELUM LENGKAP | `UserManagement.php` dan `user-management.blade.php` | Form tambah Guru dan profil NIP/NIK tersedia. Belum terbukti edit/detail/nonaktif/reset akses aman, histori, dan alur administrasi lengkap. Mutasi create belum memanggil AuditService. |
| ADM-006 | Penugasan Guru kelas/mapel/rombel/periode | BACKEND ONLY | `TeacherSubjectAssignmentController.php`, `TeacherSubjectAssignment.php` | API CRUD tenant-scoped, validasi referensi akademik, transaksi dan audit tersedia; belum ada UI Admin untuk list/create/edit/nonaktif, filter, konflik, dan histori. `canManage` hanya menerima role ADMIN_MADRASAH; role OPERATOR lama tidak konsisten dengan keputusan satu portal. |
| ADM-007 | Data Siswa administratif | ADA TAPI BELUM LENGKAP | `UserManagement.php`, `StudentProfile` | Form tambah Siswa dan NISN tersedia. Belum terbukti edit/detail/status, reset PIN/password dengan alur aman, data masuk/keluar, kelulusan/alumni, dan histori lengkap. Mutasi create belum memanggil AuditService. |
| ADM-008 | Penempatan siswa ke Rombel | ADA TAPI BELUM LENGKAP | `StudentRombelManagement.php` | Penambahan siswa dilakukan transaksional, cek tenant/role/status Rombel, duplikasi, dan audit log. Belum ada alur pindah/keluar/perubahan rombel dan promosi tahunan dengan pratinjau serta histori menyeluruh. |
| ADM-009 | Promosi/tinggal kelas/kelulusan/alumni | BELUM ADA UI/SERVICE YANG TERBUKTI | Recursive tree baseline | Tidak ditemukan komponen Admin/service khusus promosi, tinggal kelas, kelulusan kelas 6, alumni, atau riwayat proses. Wajib dirancang tanpa menghapus histori lama. |
| ADM-010 | Master mata pelajaran MI | ADA TAPI BELUM LENGKAP | `AcademicManagement.php`, `Subject.php`, academic migration | Tambah/list kode, nama, short_name tersedia. Schema belum memuat kelompok mapel/jenis nasional/Mulok sebagai field yang terlihat pada model/migration yang diperiksa; belum ada edit, histori, template impor, dan klasifikasi PAI/Bahasa Arab/Umum/Mulok. |
| ADM-011 | Mulok | BELUM ADA | `Subject` model/migration dan view akademik | Belum ada field/flow khusus penanda Mulok, nama lokal/daerah, kelompok, atau validasi impor Mulok yang diminta. |
| ADM-012 | Kurmer/KBC/versioning | BACKEND PARTIAL; UI BELUM LENGKAP | `CurriculumManagement.php`, `MultiCurriculum*`, `Curriculum*` models/migrations | UI yang ada berlabel Kurikulum Merdeka (CP & TP). Belum ada administrasi Kurmer + KBC secara utuh, sumber master resmi/lokal, versi/riwayat dan konfigurasi KBC yang terbukti di UI. |
| ADM-013 | CP/TP/ATP | ADA TAPI BELUM LENGKAP | `CurriculumManagement.php`, `curriculum-management.blade.php` | CP ditampilkan read-only; TP dapat dibuat. Belum ada pengelolaan CP/versi, edit/status, ATP, pemetaan dan riwayat akademik lengkap. |
| ADM-014 | Panca Cinta/DPL/Pembelajaran Mendalam | BELUM ADA UI YANG TERBUKTI | Recursive tree dan view Admin baseline | Belum ditemukan halaman Admin untuk master/konfigurasi Panca Cinta, delapan dimensi profil lulusan, atau Pembelajaran Mendalam. Nomenklatur dan sumber resmi harus diverifikasi; jangan mengarang master. |
| ADM-015 | Impor Excel Siswa | BELUM ADA UI | Recursive tree; route API | Tidak ditemukan halaman/template/import pipeline Admin Siswa dengan validasi, preview, konfirmasi, error report, dan riwayat. |
| ADM-016 | Impor Excel Guru | BELUM ADA UI | `TeacherSubjectAssignmentController.php` hanya API assignment; recursive tree | Tidak ditemukan impor Guru beserta validasi profil dan assignment. |
| ADM-017 | Impor Excel Mata Pelajaran | BELUM ADA UI | `Subject.php`, academic migration, recursive tree | Tidak ditemukan template/impor mapel dan Mulok. API `questions/import` adalah impor soal, bukan impor administratif. |
| ADM-018 | Laporan administratif | BELUM ADA UI YANG TERBUKTI | API reports/analytics yang ada terkait hasil/ujian/kurikulum | Belum ditemukan halaman laporan administratif siswa/guru/rombel/mapel/periode/kenaikan/kelulusan/aktivitas. Laporan nilai/analisis ujian Guru tidak boleh dialihkan menjadi fungsi Admin. |
| ADM-019 | Backup/restore | BELUM ADA UI/SERVICE ADMIN YANG TERBUKTI | Recursive tree baseline | Belum ditemukan alur backup database/media, verifikasi integritas, riwayat, dan restore terkontrol. Restore produksi tetap membutuhkan persetujuan dan prosedur khusus. |
| ADM-020 | Profil Admin/password/preferensi | BELUM ADA UI YANG TERBUKTI | Routes/layout baseline | Login/logout tersedia; belum ditemukan halaman profil, ubah password, preferensi, notifikasi, dan pengaturan dokumen. |
| ADM-021 | Audit log/kesehatan sistem | BACKEND PARTIAL | `AuditLog`, `AuditService`, audit migration, API `/api/v1/health` | Audit table/service ada dan beberapa mutasi tercatat; belum ada UI audit administratif dan dashboard kesehatan sistem. UserManagement, AcademicManagement, dan CurriculumManagement memiliki mutasi yang belum terlihat memanggil AuditService. Health endpoint publik yang ditemukan hanya status/version/timestamp, bukan panel diagnostik Admin. |
| ADM-022 | Sidebar/topbar/responsif | ADA TAPI BELUM LENGKAP / BERISIKO UX | `resources/views/layouts/app.blade.php` | Brand masih “Panel Admin & Guru”; sidebar `hidden md:flex` tanpa drawer mobile; nav tidak memiliki scroll independen; Tailwind CDN dan Google Fonts CDN masih dimuat runtime; notifikasi memakai `alert()`; judul/header tidak menunjukkan konteks madrasah/tahun/semester. |
| ADM-023 | Daftar Ujian & Penugasan Proktor | ADA TAPI BELUM SESUAI KEPUTUSAN FINAL | `ExamManagement.php`, `ProctorAssignmentManagement.php`, `ExamProctorAssignmentService.php`, `ExamPolicy.php` | Daftar/aksi ujian dan assignment terpisah; halaman assignment memakai akun role PROKTOR permanen; dropdown hanya ujian PUBLISHED/locked/belum berakhir; belum terlihat filter list ujian Guru terintegrasi, review assignment saat jadwal berubah, ruang/sesi, dan pemeriksaan konflik jadwal lintas assignment. |

## 4. Temuan authorization dan kewenangan — prioritas tinggi

### 4.1 Admin masih memiliki akses operasional CBT Guru
File:
- `backend-laravel/app/Services/RolePermissionBootstrapper.php`
- `backend-laravel/tests/Feature/RolePermissionBootstrapperTest.php`
- `backend-laravel/app/Policies/ExamPolicy.php`
- `backend-laravel/routes/web.php`

Temuan:
- `ADMIN_MADRASAH` diberi `question.view`, `exam.manage`, `exam.assign`, `exam.monitor`, `exam.close`, `exam.token`, `exam.grade`, `exam.reopen`, `report.view`, dan permission lain.
- `ExamPolicy::monitor` mengizinkan Admin Madrasah dengan `exam.monitor` melakukan monitoring.
- `ExamPolicy::close` mengizinkan Admin Madrasah dengan `exam.close` menutup ujian.
- `/exams/{exam}/monitoring` tersedia melalui route Web yang memakai policy.
- `/admin/questions` menerima role Admin dan permission `question.view`.
- `AdminUiVerificationTest::admin_can_access_all_admin_routes` mengharapkan Admin dapat membuka `/admin/questions` dan `/admin/exams`.
- `RolePermissionBootstrapperTest` mengunci grant operasional tersebut sebagai hasil yang diharapkan.

Ini tidak sesuai keputusan produk: Admin hanya membaca daftar ujian Guru dan mengatur penugasan pengawas; Admin tidak mengelola Bank Soal, monitoring pengerjaan, token, penilaian, jawaban/nilai, atau penutupan ujian. Perlu desain ulang permission matrix dan negative tests. Jangan melakukan blanket detach atau menghapus role/permission sebelum inventaris seluruh pemakai dan kompatibilitas selesai.

### 4.2 Admin dan Operator masih dua role
Bukti:
- `DatabaseSeeder.php` membuat `ADMIN_MADRASAH` dan `OPERATOR` terpisah.
- `RolePermissionBootstrapper.php` memiliki grant dan revoke yang berbeda untuk keduanya.
- `AuthWebController`/route dashboard historis mengarahkan keduanya ke portal Operator.
- `WebAdminAuthTest.php` menguji login dan redirect terpisah.
- `TeacherSubjectAssignmentController::canManage` hanya menerima `ADMIN_MADRASAH`.

Keputusan produk mengikat: satu role/portal/dashboard/permission set, label “Admin Madrasah”. Implementasi harus menjaga kompatibilitas akun/role lama; jangan menghapus identifier role atau mengubah data tanpa mapping dan rencana transisi yang diuji.

### 4.3 Proktor permanen bertentangan dengan keputusan terbaru
Bukti:
- `ProctorAssignmentManagement.php` hanya mencari user dengan role `PROKTOR`.
- `ExamProctorAssignmentService.php` menolak user yang bukan role `PROKTOR`.
- UI menyebut “Akun Proktor”.
- `RolePermissionBootstrapper.php` masih mengelola grant role `PROKTOR`.

Keputusan terbaru: Guru tetap role dasar GURU; semua Guru aktif dapat dijadwalkan; hak Proktor adalah konteks sementara yang diturunkan dari assignment/jadwal aktif. Tidak boleh ada role Proktor permanen/aktivasi manual/portal tersendiri. Perlu migrasi desain service/policy, bukan sekadar mengganti label UI.

### 4.4 Audit log mutasi belum konsisten
- `StudentRombelManagement::addStudentToRombel` menggunakan transaction dan AuditService.
- `ExamProctorAssignmentService` menggunakan transaction dan AuditService.
- `UserManagement::createUser`, `AcademicManagement::createRombel/createSubject`, dan `CurriculumManagement::createTp` belum terlihat memanggil AuditService.
- Audit table/service tersedia; perlu before/after, actor, tenant, timestamp, reason untuk aksi sensitif.

## 5. Catatan keamanan/data dan kualitas implementasi

1. `TenantScoped` menerapkan fail-closed query ketika TenantContext tidak tersedia dan memaksa tenant_id dari context saat create. Ini pertahanan penting yang harus dipertahankan.
2. `StudentRombelManagement` lebih kuat daripada modul lain: transaksi, lock row, cek tenant, cek status Rombel, cek duplikasi, dan audit log. Namun perpindahan/keluar/riwayat akademik belum tersedia.
3. `UserManagement` membatasi pembuatan role ke GURU/SISWA, memakai Hash dan password minimal 14 karakter, serta tenant-scoped uniqueness NISN/NIP/NIK. Namun create belum audit; tidak ada edit/nonaktif/reset; daftar belum membedakan status aktif sesuai kebutuhan dashboard.
4. `AcademicManagement` memiliki tenant-scoped exists validation untuk tahun ajaran dan grade level; Subject create belum memiliki validasi unik yang terlihat di komponen (database memiliki unique tenant+code). Mutasi akademik belum audit log.
5. `TeacherSubjectAssignmentController` memiliki transaction, validasi relasi tenant/tahun ajaran, duplicate response, audit log, dan deactivation yang mempertahankan histori. Tetapi UI Admin belum ada.
6. `ProctorAssignmentService` mengunci ujian dan assignment, serta mencatat create/renew/revoke. Namun tidak memeriksa konflik jadwal Guru lintas ujian/ruang/sesi; unique key hanya tenant+exam+proctor. Assignment tidak memiliki state “perlu ditinjau ulang”.
7. Test yang ada mencakup sebagian auth, tenant, assignment, dan RBAC. Belum membuktikan seluruh route/action, semua role × permission, import edge cases, browser E2E, responsivitas, backup/restore, dan semua acceptance criteria.

## 6. Desain awal yang direkomendasikan

### 6.1 Portal, role, dan navigasi
- Satu portal Admin Madrasah untuk legacy Admin/Operator.
- Brand: “Panel Admin Madrasah”; label role tunggal “Admin Madrasah”.
- Role lama dipertahankan sementara sebagai compatibility alias sampai pemetaan akun, permission, seeder, policy, dan test selesai.
- Sidebar dikelompokkan: Dashboard; Identitas Madrasah; Akademik; Pengguna; Kurikulum; Impor Data; Daftar Ujian & Penugasan Proktor; Laporan Administratif; Audit & Kesehatan Sistem; Backup; Pengaturan.
- Desktop: sidebar scrollable independen; header brand tetap terlihat; konten utama scroll terpisah.
- Mobile/tablet: drawer/off-canvas dengan tombol buka/tutup, keyboard/focus support, dan scroll.
- Navigasi mengikuti permission; backend tetap memeriksa authorization pada setiap route/action.

### 6.2 Dashboard eksekutif
- Header nama/logo madrasah, identitas Admin, tahun ajaran dan semester aktif.
- Statistik administrasi: siswa aktif per kelas/fase, Guru aktif, rombel aktif, mapel termasuk Mulok, kelengkapan administrasi.
- Grafik dengan periode dan sumber data yang jelas.
- Aktivitas administratif dari audit log.
- Kesehatan instalasi: DB/storage/queue/scheduler/backup/version; hanya status aman, tanpa secrets/stack trace.
- Tidak ada tombol operasional Bank Soal, monitoring ujian, token, penilaian, atau close exam. Ringkasan ujian agregat hanya bila disetujui dan tidak membuka detail operasional.

### 6.3 Identitas dan akademik
- Profil madrasah memakai data Tenant yang sudah ada; audit field/asset sebelum menambah schema.
- Kelola tahun ajaran dan semester dengan validasi active period, histori, dan larangan menghapus histori.
- Kelas MI 1–6 dengan Fase A (1–2), B (3–4), C (5–6).
- Rombel, wali kelas, daftar siswa, kapasitas/status, dan histori.
- Promosi/tinggal kelas: asal/tujuan, preview, validasi ganda, transaksi, audit, laporan; nilai/ujian lama immutable.
- Kelulusan kelas 6/alumni mempertahankan akun dan seluruh histori.

### 6.4 Pengguna dan penugasan Guru
- Data Guru/Siswa: list, detail, edit, status, validasi identitas, reset kredensial melalui alur aman, histori.
- Penugasan Guru: kelas/mapel/rombel/tahun ajaran/semester/periode; UI mengonsumsi API TeacherSubjectAssignment yang sudah ada setelah audit endpoint dan authorization.
- Tidak memberi Admin hak authoring CBT Guru.
- Impor pengguna wajib memakai preview dan konfirmasi; jangan simpan password/PIN plaintext.

### 6.5 Kurmer/KBC
- Hanya Kurikulum Merdeka dan Kurikulum Berbasis Cinta.
- Pertahankan data historis kurikulum lama tanpa mengaktifkan pilihan baru untuk kurikulum lain.
- Master CP/TP/ATP, Panca Cinta, DPL, Pembelajaran Mendalam, fase, mapel, versi, dan histori.
- Bedakan master resmi dari turunan/lokal; seed harus idempotent dan tidak menimpa custom.
- Nama/kode/struktur resmi harus memiliki sumber terverifikasi; jangan mengarang.

### 6.6 Impor Excel
- Tiga alur terpisah: Siswa, Guru, Mata Pelajaran (termasuk Mulok).
- Download template versi; upload; validasi file/kolom/referensi; deteksi duplikasi; preview; konfirmasi; transaksi; ringkasan; error report; riwayat.
- Retry/idempotency dan partial failure harus diuji pada MySQL 8 disposable.
- API import soal yang ada tidak boleh disalahartikan sebagai impor administratif.

### 6.7 Daftar Ujian & Penugasan Proktor
- Sumber daftar adalah record ujian yang dibuat Guru; Admin tidak membuat salinan jadwal/mapel.
- Detail akademik ujian read-only.
- Admin memilih Guru aktif tenant yang sama dari daftar ujian lalu menetapkan pengawasan.
- Assignment mengacu pada ujian dan rentang jadwal sumber; jika jadwal Guru berubah, assignment ditandai “Perlu Ditinjau Ulang” dan tidak diubah otomatis.
- Validasi benturan jadwal Guru dan ruang/sesi jika tersedia; aturan edaran hanya diterapkan setelah sumber resmi diverifikasi.
- Hak Proktor berasal dari jadwal/assignment aktif dan waktu server, bukan role permanen.
- Admin tidak mengakses monitoring, token, penilaian, jawaban/nilai, atau aksi CBT Guru.
- Jangan membangun portal Guru/Proktor pada tahap Web Admin.

### 6.8 Laporan, audit, backup, dan pengaturan
- Laporan hanya administratif: siswa, Guru, rombel, mapel, periode, promosi, kelulusan, aktivitas.
- Audit log UI dengan filter actor/aksi/waktu/entitas dan akses read-only.
- Backup database/media dengan manifest/hash, retention, dan audit; restore diuji hanya di database disposable. Tidak ada restore produksi tanpa persetujuan.
- Profil Admin, password, preferensi, notifikasi, dan pengaturan dokumen.
- File upload/private storage harus diuji MIME/content/size/path traversal/authorization.

## 7. Urutan implementasi setelah persetujuan

1. **Fondasi RBAC dan portal Admin**: satu portal Admin/Operator, permission matrix least privilege, route/action tests; cabut akses monitoring/close/authoring Admin melalui desain yang teruji.
2. **Shell UI**: branding, sidebar scroll, mobile drawer, topbar, breadcrumb, design tokens, loading/empty/error/success.
3. **Dashboard + identitas madrasah + periode akademik**.
4. **Pengguna, penugasan Guru, Rombel, penempatan dan histori siswa**.
5. **Promosi/tinggal kelas/kelulusan/alumni**.
6. **Master Kurmer/KBC, CP/TP/ATP, Panca Cinta, DPL, Pembelajaran Mendalam**.
7. **Impor Excel tiga domain**.
8. **Daftar ujian Guru + assignment Guru pengawas**.
9. **Laporan administratif, audit log, kesehatan sistem, backup/restore, pengaturan**.
10. **Full regression, security, browser E2E, responsive, performance, validasi Admin madrasah**.

Setiap tahap: backup exact file → verifikasi backup byte-exact → patch minimal → focused tests → MySQL 8 disposable full CI → update checkpoint. Tidak ada merge/deployment/migration produksi tanpa persetujuan terpisah.

## 8. Rencana pengujian dan status

Matriks `docs/MATRIKS_PENGUJIAN_WEB_ADMIN.md` memiliki 38 skenario inti. Pada audit ini semua skenario browser/operasional tetap **NOT RUN** kecuali bukti CI historis yang memang dapat diverifikasi untuk commit terkait; hasil CI cabang lain tidak boleh dipakai untuk mengklaim Web Admin saat ini lulus.

Wajib dijalankan setelah implementasi:
- MySQL 8/InnoDB disposable, fresh migration chain, focused + full PHPUnit.
- Negative tests role × route × action × permission.
- Tenant IDOR list/detail/count/import/export/download.
- Concurrent assignment/promosi/import and idempotency.
- Browser E2E per role, semua tombol/form/filter/pagination/modal.
- Responsive desktop 1366×768, mobile 390×844, tablet portrait/landscape.
- Keyboard/focus/contrast/validation/error feedback.
- Backup integrity and restore rehearsal pada disposable DB.
- UAT Admin madrasah dengan data representatif.

## 9. Status dependency terpisah — Agent2 P0

CI run #89 ID `45066851753`, commit `b00cf4312b12e1158c609ca91af3dcecec7a2553`:
- MySQL 8.0.46 isolated database: PASS.
- Fresh migrations: PASS.
- PHP syntax: PASS.
- PHPUnit: 78 passed, 4 failed.
- Two-connection InnoDB race test: SKIPPED karena PHPUnit gagal.
- Disposable DB cleanup: PASS.
- Artifact: `14771164623`.

Empat kegagalan berada di `ParticipantAuthorizationAndAttemptLimitTest` (assertions terkait submit/replay/foreign execution). P0 belum lulus dan tidak boleh ditutup. Temuan ini bukan bagian dari Web Admin dan harus ditangani pada branch Agent2 tersendiri. Jangan mencampur patch P0 ke branch Web Admin.

## 10. Batas dan keputusan

- Tidak ada source aplikasi berubah pada commit audit ini.
- PR #5/#6/#7 tetap Draft/Open/Unmerged.
- Tidak ada migration, database produksi, .env, VPS, deployment, atau Android yang disentuh.
- Portal Guru/Siswa tetap di luar scope.
- P0 database dan Device Lease/Fencing tetap jalur terpisah.
- Audit ini adalah hasil READ-ONLY pada baseline SHA yang disebutkan. Jika baseline PR #5 berubah, ulangi diff dan validasi temuan terhadap head terbaru sebelum implementasi.
- Implementasi Web Admin menunggu persetujuan eksplisit pengguna atas desain dan urutan di bagian 6–7.
