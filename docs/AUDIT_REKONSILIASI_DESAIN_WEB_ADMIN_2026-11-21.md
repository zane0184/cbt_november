
# ADDENDUM REKONSILIASI SOURCE AKTUAL — 21 NOVEMBER 2026

Status: audit statis read-only terhadap source branch kandidat; tidak ada pengujian runtime yang dijalankan dalam sesi ini.

## A. Provenance source dan branch

| Referensi | Commit | Hasil pemeriksaan |
|---|---|---|
| Default branch main | 91dae257b2210ef89c90534185a3cbdd8979ffe0 | Tree 24 entri; tidak memuat source aktif routes/web.php atau app Laravel. backend-laravel hanya berisi arsip backup_before_patch. Jangan gunakan main sebagai source build/deployment saat ini. |
| Base PR #5: fix/p0-96g-installer-private-db-config-20261120 | 158227362eea82fc7ec7e9c232736596015739d2 | Tree 665 entri, source Laravel aktif tersedia. |
| Head PR #5: fix/web-portal-usability-rbac-20261120 | ade5daa6cf853c164c4f51ec6e25ca68be40b4f2 | Tree 761 entri, truncated=false; source yang menjadi objek rekonsiliasi. SHA sama dengan baseline audit 21 November, sehingga belum ada commit source baru setelah audit tersebut. |
| PR #5 | Open, Draft, Unmerged | 64 file, +14.945/-120. |
| PR #6 | Open, Draft, Unmerged | 1 file spesifikasi, +1.378. |
| PR #7 | Open, Draft, Unmerged | 9 file dokumentasi, +1.796. |

PR #5 menggunakan base branch PR #4, bukan main. PR #6 dan #7 merupakan rantai dokumentasi yang belum digabungkan. Tidak ada PR yang di-merge atau deployment yang dilakukan.

## B. Status rekonsiliasi 23 modul audit

Klasifikasi: MASIH BERLAKU = temuan audit lama masih terlihat pada source branch yang sama; BERUBAH = temuan lama perlu dikoreksi/diperjelas; BELUM DAPAT DIVERIFIKASI = bukti runtime atau cakupan pencarian belum memadai.

| ID | Modul | Status rekonsiliasi | Kesimpulan source terkini |
|---|---|---|---|
| ADM-001 | Dashboard | MASIH BERLAKU | Dashboard Operator masih menjadi komponen yang dipakai; KPI/health dan identitas Admin belum memenuhi desain final. |
| ADM-002 | Identitas Madrasah | MASIH BERLAKU | Halaman administrasi identitas madrasah/kop dokumen belum ditemukan pada tree source kandidat. |
| ADM-003 | Tahun ajaran/semester | MASIH BERLAKU | Model dan pengelolaan dasar tersedia; siklus administrasi dan histori lengkap belum terbukti. |
| ADM-004 | Kelas/fase/rombel/wali kelas | MASIH BERLAKU | Rombel dan penempatan dasar ada; workflow lengkap fase MI, wali kelas, histori, dan mutasi belum terbukti. |
| ADM-005 | Data Guru | MASIH BERLAKU | UserManagement masih berupa fondasi create; lifecycle edit/nonaktif/reset/histori belum lengkap. |
| ADM-006 | Penugasan Guru | MASIH BERLAKU | Model dan API TeacherSubjectAssignment ada; UI Admin CRUD/histori belum ditemukan. |
| ADM-007 | Data Siswa | MASIH BERLAKU | Pembuatan dasar ada; lifecycle administratif lengkap belum terbukti. |
| ADM-008 | Penempatan/promosi/tinggal kelas | MASIH BERLAKU | Penempatan rombel tersedia; promosi/tinggal kelas lintas tahun secara transaksional belum terbukti. |
| ADM-009 | Kelulusan/alumni/histori | MASIH BERLAKU | Workflow Admin kelulusan/alumni/histori belum ditemukan. |
| ADM-010 | Master mapel MI | MASIH BERLAKU | Tambah mapel dasar ada; pengelolaan master lengkap dan histori belum terbukti. |
| ADM-011 | Mulok | MASIH BERLAKU | Modul Mulok khusus Admin belum ditemukan. |
| ADM-012 | Kurmer/KBC/versioning | MASIH BERLAKU | Fondasi CP/TP ada; pengelolaan Kurmer/KBC berversi dan provenance belum lengkap. |
| ADM-013 | CP/TP/ATP | MASIH BERLAKU | CP/TP dasar ada; ATP, versi, sumber, dan histori belum terbukti lengkap. |
| ADM-014 | Panca Cinta/Pembelajaran Mendalam/DPL | MASIH BERLAKU | Modul referensi Admin yang lengkap belum ditemukan. |
| ADM-015 | Impor Excel Siswa | MASIH BERLAKU | Importer master data siswa belum ditemukan. |
| ADM-016 | Impor Excel Guru | MASIH BERLAKU | Importer master data Guru belum ditemukan. |
| ADM-017 | Impor Excel Mapel | MASIH BERLAKU | Importer master data mapel belum ditemukan. |
| ADM-018 | Laporan administratif | MASIH BERLAKU | Laporan ujian Guru bukan pengganti laporan administratif Admin; modul Admin belum terbukti. |
| ADM-019 | Backup/restore | MASIH BERLAKU | UI dan workflow backup/restore Admin belum ditemukan. |
| ADM-020 | Profil/preferensi/notifikasi Admin | MASIH BERLAKU | Modul Admin lengkap belum ditemukan. |
| ADM-021 | Audit log/kesehatan instalasi | MASIH BERLAKU | AuditService/AuditLog ada; cakupan mutasi Admin tidak konsisten dan health dashboard belum lengkap. |
| ADM-022 | Shell/sidebar/responsive | MASIH BERLAKU | Layout masih menampilkan Panel Admin & Guru, Dashboard Operator, menu exam/question lintas portal, sidebar hidden pada mobile, dan belum ada pola scroll/drawer final. |
| ADM-023 | Daftar ujian/assignment Proktor | MASIH BERLAKU dengan koreksi | Assignment dasar ada, tetapi daftar ujian masih memakai komponen bersama, pemilih Proktor membatasi role PROKTOR, belum ada kelas dan konflik waktu, serta belum ada status review saat jadwal berubah. |

## C. Rekonsiliasi keamanan dan koreksi terhadap audit lama

1. **Admin dan Operator masih dua role.** RolePermissionBootstrapper memberi ADMIN_MADRASAH ROLE_GRANTS dan OPERATOR OPTIONAL_ROLE_GRANTS yang berbeda. Ini masih berlaku.
2. **Role/portal PROKTOR permanen masih ada.** routes/web.php masih memiliki redirect dan route /proktor/assignments; layout menampilkan menu Proktor; ProktorDashboard masih ada.
3. **Pemilih Proktor masih membatasi role PROKTOR.** ProctorAssignmentManagement dan ExamProctorAssignmentService memeriksa hasRole('PROKTOR'). Semua Guru aktif belum dapat dipilih.
4. **Admin masih memiliki kewenangan ujian lebih luas dari keputusan terbaru.** RolePermissionBootstrapper memberi Admin exam.monitor, exam.close, exam.token, exam.grade, exam.reopen dan exam.manage. ExamPolicy mengizinkan Admin memonitor dan menutup ujian. Walaupun create/update/publish tertentu dibatasi policy Guru, Admin tidak boleh memiliki monitoring/close/token/grading. Batas final adalah metadata read-only dan assignment pengawas saja.
5. **Komponen ExamManagement masih gabungan Admin/Guru.** mount menerima ADMIN_MADRASAH dan GURU. Policy mencegah sebagian aksi authoring Admin, sehingga audit ini tidak menyatakan ada bypass policy yang terbukti. Masalahnya adalah permukaan UI/route dan permission profile masih bercampur; Admin memerlukan halaman daftar metadata read-only tersendiri.
6. **Penguncian baris Guru sudah ada pada service assignment.** ExamProctorAssignmentService mengunci exam dan User proktor menggunakan lockForUpdate di dalam transaksi. Audit lama yang menyatakan tidak ada mutex Guru perlu diperbaiki.
7. **Pemeriksaan konflik waktu belum ada.** Service tidak menjalankan query overlap terhadap assignment Guru lain. Penguncian baris Guru tanpa query konflik hanya menyerialisasi request; tidak menolak assignment bertabrakan. Dua Admin masih dapat membuat konflik secara berurutan.
8. **Batas interval akses sudah setengah terbuka.** ExamProctorAssignment::scopeActiveNow memakai starts_at <= waktu server dan expires_at > waktu server. Ini sesuai [start,end) untuk pemeriksaan akses. Belum ada validasi overlap.
9. **Kelas tidak terikat pada assignment.** Tabel assignment hanya memiliki exam_id dan proctor_user_id; tidak memiliki rombel_id/class_id. Session/room juga belum menjadi atribut assignment. Aturan satu Guru satu kelas belum dapat ditegakkan dengan konteks kelas eksplisit.
10. **Perubahan jadwal belum memicu review.** Belum ditemukan alur yang mengubah assignment menjadi NEEDS_REVIEW/Perlu Ditinjau Ulang saat Guru mengubah jadwal ujian.
11. **TenantScoped perlu koreksi fail-closed.** Trait membuat model tenant-scoped menolak create tanpa tenant context, tetapi global scope hanya menambahkan where tenant_id bila context tersedia. Jika context tidak ada, query tidak otomatis ditolak. IdentifyTenant juga meneruskan request ketika tidak ada user atau user tanpa tenant. Middleware auth melindungi route tertentu, tetapi fail-closed pada seluruh jalur query/API belum dibuktikan. Tambahkan guard eksplisit dan tes tenantless-context.
12. **TenantContext diturunkan dari user.** IdentifyTenant mengabaikan X-Tenant-Override dan mengambil tenant_id dari user terautentikasi; ini selaras dengan larangan tenant switching. Tetap perlu invariant satu tenant lokal per instalasi.
13. **Composite foreign keys assignment sudah menjadi fondasi baik.** Migration mengikat exam, user proktor, assigner, dan revoker bersama tenant_id. Pertahankan dan perluas untuk rombel bila skema final disetujui.
14. **CI tidak dijalankan ulang oleh audit ini.** Status pengujian pada dokumen lama tidak boleh diperlakukan sebagai pengujian baru. Tidak ada test runtime, browser, atau concurrency test yang dijalankan pada sesi rekonsiliasi.

## D. Arsitektur tenant yang dikonsolidasikan

- Satu madrasah = satu instalasi mandiri = satu database MySQL mandiri = satu domain/konfigurasi deployment sendiri.
- Tidak ada shared operational database dan tidak ada pertukaran data akademik, pengguna, soal, ujian, jawaban, nilai, atau data operasional antarmadrasah.
- Setiap instalasi memiliki .env, APP_KEY, kredensial DB, storage, secret, backup, dan lifecycle deployment sendiri.
- tenant_id tetap dipertahankan sebagai identitas internal dan defense-in-depth pada tabel, relasi, query, policy, audit, serta composite foreign key. tenant_id bukan mekanisme untuk menggabungkan database madrasah.
- TenantContext menjadi konteks satu tenant lokal yang ditetapkan dari LocalInstallationIdentity/user terautentikasi; bukan tenant switcher dan bukan parameter yang dapat dipilih client.
- X-Tenant-Override tetap diabaikan. Tidak ada akses lintas instalasi dari tenant UI.
- Installer harus menegakkan satu tenant kanonis pada database instalasi. Source sekarang membuat tenant dan LocalInstallationIdentity, tetapi belum terbukti menolak tenant kedua dalam DB yang sama.
- .env.example sudah menggunakan APP_URL dan DB_DATABASE yang dapat dikonfigurasi. Ini fondasi portabilitas, bukan bukti uji pemasangan lintas domain. registered_domain pada LocalInstallationIdentity dan alur lisensi perlu diuji agar tidak mengikat paket source pada satu domain.
- Web Super Admin/Publisher ditunda. Ketika dikembangkan kelak, scope hanya lisensi, aktivasi, dan pembayaran sesuai desain tersendiri; bukan shared database atau sinkronisasi akademik. Source Publisher yang ada dipertahankan, tetapi bukan scope implementasi Web Admin sekarang.

## E. Role dan permission kanonis

### Admin Madrasah
ADMIN_MADRASAH menjadi identitas dan permission profile kanonis untuk Admin Madrasah/Operator. Satu dashboard, satu portal, satu sidebar, satu permission profile. OPERATOR dipertahankan sementara sebagai legacy identifier/alias kompatibilitas sampai inventaris akun, pivot, dan grant selesai; jangan menghapus atau mengubah akun/role secara destruktif.

Permission Admin hanya mencakup administrasi madrasah sesuai modul, pembacaan metadata ujian Guru, dan exam.proctor.assign. Admin tidak membuat/mengubah soal, ujian, mapel yang tertaut pada ujian, jadwal akademik ujian, snapshot, token, peserta, jawaban, nilai, analisis, atau monitoring operasional Guru/Proktor.

Admin dapat mengelola katalog referensi master mata pelajaran madrasah sesuai modul administrasi akademik. Namun pemilihan/pengaitan mapel pada suatu ujian tetap milik Guru dan read-only di Admin.

### Guru
Guru tetap pemilik operasional soal, bank soal, ujian, mapel yang digunakan pada ujian, jadwal akademik ujian, snapshot, penerbitan/token, jawaban, penilaian, analisis, dan hasil sesuai assignment serta permission.

### Proktor
Tidak ada role Proktor permanen dan tidak ada portal Proktor. Proktor adalah capability kontekstual sementara yang berasal dari assignment Guru aktif. Semua Guru aktif di instalasi yang sama dapat dipilih. Role PROKTOR lama tidak dihapus sebelum rekonsiliasi data, tetapi harus fail-closed dan tidak menjadi dasar akses baru.

## F. Desain teknis assignment Proktor

### F.1 Skema konseptual

Pertahankan tabel exam_proctor_assignments dan data historis. Kolom inti yang sudah ada: id, tenant_id, exam_id, proctor_user_id, assigned_by, starts_at, expires_at, status, assignment_reason, revoked_at, revoked_by, revoke_reason, timestamps.

Perluasan desain yang diusulkan, belum diimplementasikan:
- rombel_id wajib untuk mengikat assignment ke satu kelas/rombel;
- session_id dan room_id nullable hanya jika konsep sesi/ruang memang digunakan oleh source operasional;
- exam_revision atau schedule_fingerprint untuk mengikat assignment ke versi jadwal Guru;
- review_required_at dan review_reason;
- status NEEDS_REVIEW untuk perubahan jadwal yang belum dikonfirmasi Admin;
- tabel event/history append-only untuk create, renew, replace, review, confirm, revoke, complete; AuditService tetap mencatat actor, tenant, before/after, reason, dan timestamp.

Foreign key harus tenant-aware: (exam_id,tenant_id), (rombel_id,tenant_id), (proctor_user_id,tenant_id), (assigned_by,tenant_id), (revoked_by,tenant_id). Jangan menghapus composite FK yang sudah ada. Index pencarian konflik: (tenant_id,proctor_user_id,status,starts_at,expires_at). Index daftar per ujian/rombel: (tenant_id,exam_id,rombel_id,status).

### F.2 Aturan interval dan konflik

Interval berlaku [starts_at,expires_at): starts_at inklusif, expires_at eksklusif. Jadwal 07:00–09:00 dan 09:00–11:00 boleh bersebelahan. Jadwal 07:00–09:00 dan 08:00–10:00 harus ditolak.

Kondisi overlap untuk Guru yang sama pada tenant yang sama:
existing.starts_at < new.expires_at AND existing.expires_at > new.starts_at.

Periksa assignment dengan status SCHEDULED, ACTIVE, atau NEEDS_REVIEW; assignment NEEDS_REVIEW tetap memblokir penjadwalan baru sampai dikonfirmasi/dicabut. Assignment yang telah lewat expires_at tidak memberi hak dan tidak bentrok dengan periode baru yang dimulai setelahnya.

Satu Guru tidak boleh memiliki assignment yang overlap pada kelas/rombel berbeda. Untuk implementasi aman, larang seluruh overlap assignment Guru yang sama, termasuk lintas ujian, agar tidak ada dua penugasan bersamaan yang ambigu.

### F.3 Perlindungan concurrent requests

Dalam satu transaksi MySQL/InnoDB:
1. Lock exam terkait dengan lockForUpdate.
2. Lock baris User Guru target dengan lockForUpdate sebagai mutex per Guru.
3. Validasi Guru aktif, role GURU, tenant sama, dan kelas/rombel sama tenant.
4. Jalankan query overlap sebagai locking/current read setelah mutex Guru didapat.
5. Tolak transaksi jika ada overlap; jika tidak, simpan assignment dan audit event.
6. Semua jalur assignment memakai urutan lock yang konsisten (exam lalu Guru lalu assignment) untuk mengurangi deadlock.
7. Uji dengan dua koneksi database independen. SQLite tidak cukup untuk membuktikan perilaku lock MySQL.

Dengan urutan tersebut, dua Admin yang menugaskan Guru sama pada ujian berbeda akan terserialisasi pada baris Guru; request kedua membaca assignment hasil commit pertama dan menolak konflik.

### F.4 Perubahan jadwal Guru

Perubahan jadwal hanya boleh dilakukan Guru melalui alur yang diotorisasi. Di dalam transaksi perubahan jadwal:
- simpan versi/fingerprint jadwal lama dan baru;
- tandai seluruh assignment yang terdampak NEEDS_REVIEW;
- catat actor, waktu, nilai lama/baru, alasan, dan versi jadwal;
- jangan memindahkan jam assignment atau mengganti pengawas secara diam-diam;
- Admin melihat status Perlu Ditinjau Ulang dan melakukan konfirmasi roster;
- konfirmasi menjalankan validasi overlap ulang. Jika konflik, konfirmasi ditolak sampai Admin mengganti pengawas/waktu;
- sampai dikonfirmasi, assignment NEEDS_REVIEW tidak memberi akses Proktor.

Exam memiliki revision_number; desain dapat menggunakannya bila semantik revision terbukti cocok. Jika tidak, tambahkan schedule_fingerprint/version khusus. Jangan menganggap revision_number sudah mengimplementasikan review otomatis.

### F.5 Aktivasi, kedaluwarsa, dan pencabutan

Pada setiap request/action yang memerlukan capability Proktor, server memeriksa user aktif, tenant, assignment, exam, rombel, status, waktu server, dan permission aksi. Hak aktif hanya bila starts_at <= now < expires_at, status bukan NEEDS_REVIEW/REVOKED/COMPLETED, dan konteks exam/rombel cocok. Saat now >= expires_at, akses ditolak otomatis tanpa menunggu cron. Cron boleh memperbarui label EXPIRED untuk tampilan, tetapi bukan sumber otorisasi. Pencabutan mengubah status menjadi REVOKED secara transaksional; request berikutnya langsung ditolak. Jangan cache authorization melampaui perubahan assignment.

## G. Rencana W0–W10 — rencana saja, belum dimulai

| Tahap | Target source/modul | Dependency | Acceptance criteria | Risiko utama | Pengujian wajib |
|---|---|---|---|---|---|
| W0 | Branch/source inventory, tree, manifest, backup, route-action matrix; tidak ada file aplikasi yang diubah | Persetujuan desain terlebih dahulu | Source branch canonical disepakati; main backup-only tidak dipakai; baseline SHA dan backup terverifikasi; inventaris tidak duplikatif | Salah memilih branch/source dan menimpa pekerjaan lama | Static tree/path/SHA comparison; tidak mengubah DB |
| W1 | routes/web.php, RolePermissionBootstrapper, DatabaseSeeder, User/Role, ExamPolicy, QuestionPolicy, IdentifyTenant, TenantScoped, layouts/app.blade.php, auth/RBAC tests | W0 | Admin Madrasah satu profile; legacy OPERATOR kompatibel; tidak ada portal Proktor; tenantless request fail-closed; Admin tidak punya aksi Guru | Migrasi role merusak akun/pivot; regresi route | Route-action matrix positif/negatif, legacy login, IDOR, tenantless context |
| W2 | AcademicManagement, AcademicYear, Semester, GradeLevel, Rombel, Subject, migrations, policies, views | W1 | Tahun/semester/kelas/fase/rombel/wali kelas konsisten dan tenant-scoped; histori tidak hilang | Duplikasi master dan perubahan histori | Unique, tenant FK, active period, class/teacher assignment |
| W3 | OperatorDashboard, dashboard view, school identity model/service/migration, routes, tests | W1-W2 | Brand Panel Admin Madrasah; KPI dengan definisi; identitas/kop dokumen berversi | KPI salah dan PII bocor | Query KPI fixtures, authorization, print output, responsive |
| W4 | UserManagement, User/TeacherProfile/StudentProfile, TeacherSubjectAssignmentController/model, Admin views, policies | W1-W2 | CRUD lifecycle Guru/Siswa dan assignment Guru memiliki validasi, histori, reset aman | Role escalation, duplikasi NISN/NIP, account lockout | CRUD positif/negatif, import preflight, tenant/assignment tests |
| W5 | StudentRombelManagement, RombelStudent, StudentProfile, lifecycle service/history models and migrations | W2-W4 | Penempatan, mutasi, promosi, tinggal kelas, kelulusan, alumni append-only dan transaksional | Kehilangan histori atau siswa ganda | Idempotency, rollback, double promotion, concurrent enrollment |
| W6 | CurriculumManagement, curriculum models, CP/TP/ATP, Mulok, Panca Cinta, Deep Learning, DPL provenance/version | W2 | Kurmer/KBC dengan source/version/effective date; data resmi tidak dikarang; histori immutable | Data resmi tidak valid atau histori tertimpa | Seed idempotency, source provenance, version regression |
| W7 | ExamManagement, ProctorAssignmentManagement, ExamProctorAssignmentService/model/migration, ExamPolicy, schedule service/events, views/tests | W1-W4 | List Admin read-only dari exam Guru; semua Guru aktif dapat dipilih; kelas terikat; konflik dan review jadwal aman | Race condition, stale schedule, akses lintas kelas | Interval boundaries, overlap, concurrent MySQL, schedule review, revoke/expiry, IDOR |
| W8 | Importer Guru/Siswa/Subject, import batches, admin reports, private storage and views | W2-W6 | Preview, duplicate detection, row errors, explicit commit, idempotent retry, laporan administratif saja | Partial import, formula injection, PII export | MIME/size/content, rollback, duplicate retry, export authorization |
| W9 | AuditService/AuditLog, health, backup/restore service, profile/preferences/notifications | W1-W8 | Mutasi Admin tercatat; backup checksum/retention; restore hanya disposable test; health tidak bocorkan secret | Restore salah, log PII/secret, akses file | Audit coverage, checksum corruption, restore disposable DB, path traversal |
| W10 | Seluruh Web Admin, CI MySQL 8, browser/responsive/accessibility, security/performance, deployment package review | W1-W9 | Seluruh acceptance PASS; defect terbuka ditandai; handover lengkap; pengguna validasi sebelum scope Guru | Mengklaim siap tanpa integrasi/operasional | Full suite MySQL 8, concurrency, tenant isolation, IDOR, browser, performance, backup restore |

Tidak ada tahap yang dimulai dalam rekonsiliasi ini. W0 tetap menunggu persetujuan desain eksplisit.

## H. Status pengujian rekonsiliasi

- Audit ini adalah pembacaan source statis dan metadata GitHub.
- Tidak ada PHPUnit, migration, MySQL runtime, browser, visual, atau concurrency test yang dijalankan.
- Tes yang sudah ada pada ProctorAssignmentSecurityTest mencakup assignment dan audit, monitor dengan assignment aktif, revoke, expiry, larangan portal admin untuk akun PROKTOR, dan composite FK lintas tenant.
- Tes tersebut belum membuktikan semua Guru aktif dapat ditugaskan, overlap lintas ujian/kelas ditolak, concurrent requests aman, jadwal berubah menjadi NEEDS_REVIEW, Admin tidak dapat monitoring/close, atau tenantless query fail-closed.

## I. Bukti source kunci

Semua blob berikut diambil dari branch fix/web-portal-usability-rbac-20261120 pada commit ade5daa6cf853c164c4f51ec6e25ca68be40b4f2.

| File | Blob SHA |
|---|---|
| routes/web.php | c22fed7d54871bf4971acf626514fc02c4a68ce9 |
| app/Livewire/Admin/ProctorAssignmentManagement.php | 4e8bd91dd54757aa863d35983aace718def2ca2c |
| app/Services/ExamProctorAssignmentService.php | e16ef5db308714aa98bf283dc5a1545608da0477 |
| app/Models/ExamProctorAssignment.php | dde7511ebb5035a76287d34dc75339a6a2bffdd8 |
| database/migrations/2026_11_20_100001_create_exam_proctor_assignments_table.php | e51ef878e14d13078d81585fbbc10e1d00b76e25 |
| app/Livewire/Admin/ExamManagement.php | 478aaa170ee297b0c8399f049cd9b1ba56cbfb10 |
| app/Models/Exam.php | d3094eada3b2f54758af89e82987f09b4672f8d9 |
| app/Models/User.php | 74dd080f703d7ef3256b406176e53dad5786a937 |
| app/Services/RolePermissionBootstrapper.php | fac82573de84c52b52867afbbf7e1637164835b4 |
| app/Contexts/TenantContext.php | 171261b61ee7f551e3c1b1eb4335b169a1df0d8d |
| app/Traits/TenantScoped.php | cf192a8ccb6ca530122cb105ed3086adb09a3c5a |
| app/Http/Middleware/IdentifyTenant.php | 376a4b2771c3d9a6b128ad847c150d47ac2977db |
| app/Policies/ExamPolicy.php | 350c1ddc6bed8738d1af17cacd6c95bc367b7c29 |
| resources/views/layouts/app.blade.php | 0163813cafc0226b9eae8af789223da0fb793f64 |
| tests/Feature/ProctorAssignmentSecurityTest.php | a5eca4ea2ce05d62d4fa76e6b9cd5a21a17dd668 |
| app/Services/InstallerService.php | 62dcecd5db10574067cef41529ce531a08e02999 |
| .env.example | aa193f0d68bb96b911b71e36ea2d49e8a51f387a |

## J. Perubahan dokumentasi dan batas perubahan

Dokumentasi yang diperbarui pada branch docs/web-admin-audit-reconciliation-20261121:
- docs/SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md
- docs/REGISTER_KEPUTUSAN_WEB_ADMIN.md
- docs/MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md
- docs/MATRIKS_PENGUJIAN_WEB_ADMIN.md
- docs/CHECKPOINT_WEB_ADMIN_2026-11-21.md
- docs/AUDIT_REKONSILIASI_DESAIN_WEB_ADMIN_2026-11-21.md

Tidak ada file aplikasi, migration, database, .env, secret, PR existing, branch source, atau deployment yang diubah. Tidak ada merge. Final commit SHA dokumentasi akan dicantumkan pada handover hasil rekonsiliasi.

## K. Gerbang berikutnya

Paket rekonsiliasi dan rancangan diserahkan untuk audit bersama pengguna. Desain belum dinyatakan disetujui. W0 belum dimulai. Implementasi hanya dapat dimulai setelah pengguna menyetujui desain dan memilih branch source canonical yang benar.
