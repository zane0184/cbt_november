# REGISTER KEPUTUSAN KANONIS — WEB ADMIN MADRASAH

Tanggal konsolidasi: 21 November 2026  
Status: keputusan produk dari pengguna; perubahan hanya melalui klarifikasi/persetujuan pengguna.

## 1. Scope tahap aktif

1. Tahap aktif hanya Web Admin Madrasah.
2. Jangan audit/desain/implementasi Portal Guru atau Portal Siswa sekarang.
3. Portal Guru baru boleh dimulai setelah Web Admin selesai, diuji, divalidasi, dilaporkan, dan disetujui pengguna.
4. Portal Siswa baru boleh dimulai setelah Portal Guru selesai dan disetujui.
5. Android native, Device Lease/Fencing, P0 database, dan deployment VPS bukan bagian pekerjaan Web Admin ini.

## 2. Arsitektur dan model instalasi

- Backend Laravel 13, PHP 8.3, MySQL 8+, Blade/Livewire, Sanctum, modular monolith.
- Satu madrasah = satu instalasi = satu database.
- Domain, DB, .env, APP_KEY, storage, secrets, backup, dan lifecycle deployment terpisah per madrasah.
- tenant_id, TenantContext, middleware, global scope, policy, dan validasi tenant tetap dipertahankan sebagai pertahanan internal instalasi.
- Source aktual wajib diperiksa; jangan membangun ulang dari asumsi.

## 3. Role dan kewenangan

### Admin Madrasah — satu-satunya role aktif
ADMIN_MADRASAH adalah satu-satunya role aktif pengelola madrasah. Operator adalah nama/identifier lama dan bukan role kedua. Semua akun lama OPERATOR/ADMIN tenant-local dipetakan ke ADMIN_MADRASAH melalui migration aman. Tidak ada runtime branch, portal, dashboard, sidebar, atau permission profile Operator.

Akun berbeda tetap dipertahankan sebagai user record berbeda; jangan menggabungkan akun orang yang berbeda. Semua akun lama memperoleh permission array dan otorisasi route/action yang identik.

### Guru
Guru tetap menjadi pemilik bank soal, soal, ujian, mapel yang tertaut pada ujian, jadwal akademik, konfigurasi CBT, snapshot, penerbitan/token, jawaban, penilaian, analisis dan hasil.

### Proktor
Proktor adalah capability sementara untuk Guru aktif yang memiliki assignment sah. Tidak ada role atau portal Proktor permanen. Semua Guru aktif dalam madrasah yang sama dapat dipilih.

## 4. Alur ujian Guru → Admin → pengawas (KEPUTUSAN TERBARU)

1. Guru membuat soal dan ujian serta menentukan mapel dan jadwal akademik.
2. Setelah ujian disimpan, metadata ujian otomatis tampil pada list Web Admin.
3. Halaman Admin bernama **Daftar Ujian & Penugasan Proktor**.
4. Admin membaca nama/judul ujian, mapel, Guru pembuat, kelas/fase/rombel/peserta sasaran bila tersedia, tanggal/sesi/jam, status ujian, dan status assignment pengawas.
5. Admin memilih ujian dari list dan menekan **Atur/Tentukan Pengawas**.
6. Admin memilih Guru aktif dari madrasah yang sama.
7. Detail akademik ujian bersifat read-only bagi Admin.
8. Admin tidak membuat ulang ujian, tidak mengetik ulang mapel/jadwal, tidak mengubah soal, konfigurasi CBT, snapshot, token, penilaian, jawaban, analisis, atau hasil.
9. Perubahan jadwal/mapel oleh Guru harus tercermin di list Admin. Jika assignment terdampak, tandai **Perlu Ditinjau Ulang**; jangan mengubah/menghapus roster diam-diam.
10. Capability Proktor tersedia otomatis hanya untuk assignment berstatus CONFIRMED setelah seluruh validasi terpenuhi dan selama starts_at <= serverNow < expires_at; tidak ada status assignment ACTIVE.

**Prioritas:** Bagian 30 spesifikasi dan bagian 16 prompt agent adalah klarifikasi terbaru. Bagian 29 yang terdahulu harus dibaca sesuai koreksi ini: Admin hanya mengelola roster pengawas dari ujian Guru, bukan pemilik kalender akademik ujian.

## 5. Lingkup Admin

Admin mengelola:
- dashboard eksekutif dan kesehatan instalasi;
- identitas/branding madrasah;
- tahun ajaran, semester, kelas/fase, rombel, wali kelas;
- data Guru/Siswa administratif;
- penugasan Guru kelas/mapel/rombel/periode;
- penempatan siswa, promosi, tinggal kelas, kelulusan kelas 6, alumni, histori akademik;
- master mapel MI dan Mulok;
- master Kurmer/KBC, CP/TP/ATP, Pembelajaran Mendalam, Panca Cinta, DPL;
- impor Excel Guru, Siswa, Mata Pelajaran;
- laporan administratif;
- backup/restore terkontrol (restore hanya disposable test);
- audit log administratif, kesehatan sistem, konfigurasi instalasi;
- profil Admin, keamanan akun, preferensi/notifikasi;
- daftar ujian Guru dan penugasan pengawas sesuai keputusan bagian 4.

Admin bukan operator authoring CBT Guru.

## 6. Kurikulum

- Kurikulum yang didukung hanya Kurikulum Merdeka dan Kurikulum Berbasis Cinta.
- Jangan menambah kurikulum lain atau menghapus histori lama.
- Fase MI: A kelas 1–2, B kelas 3–4, C kelas 5–6.
- Integrasi kurikulum ke data akademik, mapel, CP/TP/ATP, materi, asesmen, bank soal, ujian, hasil, laporan, histori/versioning.
- Lima pilar Panca Cinta sesuai nomenklatur spesifikasi.
- 8 Dimensi Profil Lulusan harus memakai nama/definisi dan sumber resmi yang diverifikasi.
- Seed resmi idempotent; tidak menimpa custom lokal. Master resmi dan turunan lokal harus dibedakan.
- Jangan mengarang kode RDM, CP/TP/ATP resmi, struktur mapel, alokasi jam, atau ketentuan edaran.

## 7. UI/UX

- Premium, ringan, konsisten, responsif; tidak mengorbankan performa.
- Sidebar gelap dapat di-scroll independen; seluruh menu dapat dicapai; brand/header tetap terlihat bila desain mendukung.
- Mobile/tablet menggunakan drawer/off-canvas dengan scroll, fokus keyboard, dan tombol tutup yang benar.
- Brand hanya “Panel Admin Madrasah”/“Admin Madrasah”; jangan “Panel Admin & Guru”.
- Semua halaman punya loading, empty, error, success state yang jelas.
- Aksesibilitas dasar: keyboard, focus, label, contrast, error feedback.

## 8. Keamanan dan integritas

- Server-side authorization adalah otoritas; menyembunyikan menu tidak cukup.
- Validasi role, permission, tenant, assignment, scope data di setiap route/action/query/mutasi.
- Cegah IDOR, mass assignment, CSRF, kebocoran file, akses ekspor, rate abuse, kebocoran PII, query lintas tenant.
- Mutasi sensitif memakai transaction, idempotency, audit log, validasi concurrency sesuai kebutuhan.
- Jangan menghapus histori akademik.
- Backup sebelum patch; catat path dan SHA-256 source/backup/final.
- Jangan destructive migration atau migration eksperimental di DB produksi.

## 9. P0 dan Device Lease/Fencing

- P0 database adalah pekerjaan terpisah; jangan disentuh dalam Web Admin tanpa persetujuan terpisah.
- Device Lease/Fencing HOLD; feature flag enforcement OFF; jangan klaim siap produksi.
- Jangan menyentuh Attempt/Answer/Submit/monitoring sebagai “perbaikan sampingan” kecuali dependensi keamanan Admin terbukti dan pengguna menyetujui.
- Jangan memulihkan/recreate source berdasarkan laporan atau manifest lama tanpa provenance source yang cocok.

## 10. Gerbang persetujuan dan larangan

- Audit source read-only terlebih dahulu.
- Serahkan matriks audit, risiko, gap, dan desain.
- Tunggu persetujuan pengguna sebelum patch.
- Tidak ada merge PR, deployment VPS, perubahan .env/secrets, DB produksi, Nginx, atau migration produksi tanpa persetujuan eksplisit terpisah.
- Test yang tidak dijalankan wajib ditulis BLOCKED/NOT RUN, bukan PASS.
- “Siap produksi” memerlukan integrasi, keamanan, stabilitas, pemulihan, dan validasi operasional; bukan sekadar UI atau CI.



## 12. Source of truth branch dan baseline rekonsiliasi

- Default branch main: 91dae257b2210ef89c90534185a3cbdd8979ffe0; tree 24 entri tanpa source Laravel aktif. Jangan jadikan main basis build/deployment saat ini.
- Base PR #5: fix/p0-96g-installer-private-db-config-20261120, SHA 158227362eea82fc7ec7e9c232736596015739d2, tree 665 entri.
- Kandidat source Web Admin PR #5: fix/web-portal-usability-rbac-20261120, SHA ade5daa6cf853c164c4f51ec6e25ca68be40b4f2, tree 761 entri.
- PR #5/#6/#7 masih Open, Draft, Unmerged. Source candidate belum masuk main.
- Pilihan branch canonical untuk implementasi harus menjadi gerbang W0 setelah persetujuan desain.

## 13. Arsitektur instalasi mandiri

Satu madrasah = satu instalasi = satu database sendiri. Tidak ada shared operational database atau pertukaran data akademik antarmadrasah. tenant_id, TenantContext, middleware, global scope, policy, audit dan composite FK dipertahankan sebagai defense-in-depth internal.

InstallationIdentityService sudah menegakkan tepat satu Tenant dan maksimal satu LocalInstallationIdentity per DB. TenantScoped fail-closed jika context kosong. IdentifyTenant mengambil tenant dari user dan mengabaikan X-Tenant-Override. Batas tersisa: SystemTenantSeeder harus diisolasi, User model diuji eksplisit, dan registered_domain existing identity harus dicocokkan dengan APP_URL host.

## 14. Role dan kepemilikan ujian

- ADMIN_MADRASAH adalah satu-satunya role Admin aktif. OPERATOR dan ADMIN hanya identifier legacy migration-only; tidak ada role/pivot/grant aktif setelah transisi.
- Semua akun OPERATOR/ADMIN tenant-local dipetakan ke ADMIN_MADRASAH dengan menjaga user ID, password, tenant, profil, dan histori. Akun orang berbeda tidak digabung.
- Semua Guru aktif dapat ditugaskan sebagai Proktor sementara. Tidak ada role/portal Proktor permanen. Legacy PROKTOR hanya dipetakan ke GURU jika TeacherProfile valid dan tenant cocok.
- Guru memiliki soal, ujian, mapel ujian, jadwal akademik, snapshot, token/penerbitan, jawaban, penilaian, analisis, hasil.
- Admin hanya membaca metadata ujian Guru dan menetapkan/meninjau/mencabut assignment pengawas. Admin tidak mengubah atau memonitor ujian, tidak membuat token, tidak grading, tidak membuka jawaban/nilai/hasil.

## 15. Aturan assignment Proktor

- Assignment mengikat tenant, exam, rombel, Guru aktif, actor Admin, periode [starts_at, expires_at), status, schedule revision, alasan dan histori.
- Interval setengah terbuka [start,end). Overlap: existing.starts_at < new.expires_at AND effective_existing_end > new.starts_at.
- Satu Guru tidak boleh mempunyai assignment overlap lintas kelas/rombel atau ujian.
- Transaksi InnoDB memakai lock order exam → Guru row → assignment rows. Setelah mutex Guru didapat, query overlap locking/current read FOR UPDATE. Semua write memakai service yang sama.
- Perubahan jadwal Guru melalui ExamScheduleChangeService menandai assignment terdampak NEEDS_REVIEW; Admin meninjau/confirm setelah validasi konflik.
- NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED tidak memberi capability. Expiry ditolak langsung berdasarkan waktu server; revoke langsung menghentikan akses.
- Semua create/renew/reschedule/review/confirm/revoke/expire dicatat AuditService dan event append-only.

## 16. Koreksi audit

- Source aktual masih membuat role OPERATOR pada DatabaseSeeder, RolePermissionBootstrapper, dan InstallerService; grant Admin/Operator berbeda.
- Login resolver, auth controller, license status, routes, policies, layout, dan dashboard masih membedakan Admin/Operator.
- ADMIN legacy masih diterima pada beberapa komponen; harus migration-only setelah preflight.
- Proktor masih role permanen pada beberapa source; harus capability Guru dari assignment.
- TenantScoped fail-closed dan one Tenant per DB sudah ada; catatan audit lama yang menyebut belum ada dikoreksi.
- Assignment service sudah lock row Guru, tetapi belum overlap query; revoke belum konsisten lock order.
- Jadwal API/Livewire belum memicu NEEDS_REVIEW; rombel_id dan event history belum ada.
- Source canonical seluruh CBT belum terkunci karena raw baseline/workspace tidak tersedia; working source Web Admin yang direkomendasikan tercatat pada Section 19.

## 17. Koreksi final Admin Madrasah = Operator
Keputusan final: satu role aktif ADMIN_MADRASAH. OPERATOR 100% sama dengan Admin Madrasah dan bukan role kedua. Satu role/portal/dashboard/sidebar/permission profile/kewenangan untuk setiap pengguna. Akun pengguna yang berbeda tetap terpisah; migrasi role tidak menggabungkan user record atau password. Tidak ada runtime branching untuk OPERATOR atau ADMIN. Legacy ADMIN tenant-local juga diverifikasi dan dipetakan ke ADMIN_MADRASAH; SUPER_ADMIN tidak dipetakan. KAMAD tetap terpisah.

DatabaseSeeder, InstallerService, RolePermissionBootstrapper, AuthWebController, LoginIdentityResolver, LicenseStatusController, routes, policies, dashboard, layout, UserManagement, dan tests harus menghapus role aktif OPERATOR. Role migration wajib preflight roles, role_user, permission_role, users.role, account/profile/tenant, audit, sessions/tokens, dan seluruh FK. Preserve account ID/password/profile/history. Konflik multi-role menghentikan migrasi. Setelah mapping, tidak ada OPERATOR pivot/grant/role row aktif. Permission Admin di-sync ke satu exact profile; bukan union grant lama.

Legacy PROKTOR bukan role aktif. Akun lama hanya dipetakan ke GURU bila TeacherProfile valid dan tenant cocok; selain itu manual review. Tidak ada portal Proktor.

## 18. Koreksi tenant fail-closed dan satu DB
InstallationIdentityService sudah menegakkan satu Tenant per DB dan satu LocalInstallationIdentity; LoginIdentityResolver menolak selain satu. TenantScoped sudah fail-closed bila TenantContext kosong. IdentifyTenant mengabaikan X-Tenant-Override dan mengambil tenant dari user. Koreksi ini menggantikan catatan lama yang menyatakan fail-closed/one-tenant belum terbukti.

Satu madrasah = satu instalasi = satu database mandiri; no shared operational DB/academic exchange. Batas yang tersisa: SystemTenantSeeder harus diisolasi dari DB madrasah; User yang tidak menggunakan TenantScoped harus diuji eksplisit; registered_domain existing identity harus dibandingkan dengan APP_URL host. Domain berbeda harus memakai DB berbeda.

## 19. Source canonical kerja Web Admin — rekomendasi bersyarat
Rekomendasi working source Web Admin: recovery/source-preservation-20261121 @ 7fc45bf71ac1ebd375eba44fd57c983bf18d66e0. Branch ini memiliki source backend aktif, 768 tree entries, 531 files, 237 dirs, 4.425.698 blob bytes; 347 active backend files di luar vendor/backup. Dibanding PR #5 head, hanya lima dokumen recovery/provenance ditambahkan, tidak ada source app berubah.

Ini bukan bukti byte parity dengan ZIP asli 6 November atau worktree 533 file. Source canonical seluruh proyek tetap NOT VERIFIED/NOT LOCKED. main bukan source aktif (24 entries, 10 files, 14 dirs, 0 backend active). Tidak merge PR. Branch implementasi baru hanya setelah persetujuan.

## 20. Desain Proktor — penguncian dan review jadwal
- Semua Guru aktif dapat dipilih; tidak ada role/portal Proktor.
- Perlu exam_rombels, rombel_id pada assignment, schedule_revision, NEEDS_REVIEW metadata, dan append-only assignment events.
- Interval [start,end); overlap formula memakai effective end (expires_at atau revoked_at).
- Semua assignment create/renew/revoke/confirm/reschedule memakai urutan lock exam → Guru → assignment; overlap query locking/current read FOR UPDATE.
- API ExamController::update dan Livewire ExamManagement::saveExam harus menggunakan satu ExamScheduleChangeService. Direct update di luar service ditolak. Reschedule setelah attempt dimulai ditolak.
- NEEDS_REVIEW/REVOKED/EXPIRED/COMPLETED tidak memberi capability. Expiry ditolak langsung dari waktu server; cron hanya label/event.
- Semua transisi dicatat dalam AuditService dan event append-only dalam transaksi yang sama.


## 19. Keputusan terbaru — role dan kewenangan Admin/Guru (ACTIVE)

| ID | Tanggal | Keputusan | Alasan | Dampak | Status | Menggantikan |
|---|---|---|---|---|---|---|
| DEC-WA-019 | 21-11-2026 | Role aktif Admin hanya `ADMIN_MADRASAH`; `OPERATOR` dihapus dari role aktif dan tidak boleh menjadi alias runtime. | Satu identitas role/portal/permission yang tegas; mencegah duplikasi kewenangan. | Seeder, installer, bootstrapper, role/pivot, resolver, middleware, policy, route, UI, test, migrasi data. | ACTIVE | Kalimat lama “Admin Madrasah = Operator” bila dibaca sebagai role/alias aktif. |
| DEC-WA-020 | 21-11-2026 | Akun legacy OPERATOR dipetakan ke ADMIN_MADRASAH secara aman; user ID, password hash, tenant, profil, histori dan audit dipertahankan; akun orang berbeda tidak digabung. | Menjaga kontinuitas identitas dan histori. | Preflight roles/pivot/FK/sesi/token; transaksi; backup dan restore test; manual review untuk konflik. | ACTIVE — migration belum dijalankan | Tidak ada penghapusan user atau alias runtime. |
| DEC-WA-021 | 21-11-2026 | Admin hanya mengelola administrasi madrasah, master referensi sesuai kewenangan, serta assignment Proktor. | Memisahkan administrasi dari operasional CBT Guru. | Permission registry, policy, route/action, dashboard, UI, API, audit, test negatif. | ACTIVE | Permission Admin yang mengizinkan operasi CBT. |
| DEC-WA-022 | 21-11-2026 | Guru memiliki seluruh operasional CBT: bank soal, soal, ujian, mapel/jadwal/peserta, publikasi/token, pelaksanaan, penilaian, hasil, analitik. Tidak perlu approval Admin bila permission dan assignment sah. | Kepemilikan akademik dan operasional berada pada Guru. | Portal Guru kelak; Web Admin hanya metadata minimum untuk assignment Proktor. | ACTIVE | Interpretasi Admin sebagai pengelola/approver ujian. |
| DEC-WA-023 | 21-11-2026 | Proktor bukan role/portal permanen; Guru aktif menjadi Proktor melalui assignment sementara dari Admin. | Hak bersifat kontekstual dan terbatas. | Role migration, assignment model/service/policy, request authorization, audit, test. | ACTIVE | Role/portal PROKTOR permanen. |
| DEC-WA-024 | 21-11-2026 | Jadwal akademik ujian diubah oleh Guru; seluruh jalur API/Livewire memakai ExamScheduleChangeService dan assignment terdampak menjadi NEEDS_REVIEW. | Mencegah assignment pengawasan stale atau bergeser diam-diam. | Shared service, transaction, revision, audit event, admin review/confirm, concurrency tests. | ACTIVE | Jalur jadwal terpisah atau perubahan jadwal oleh Admin. |
| DEC-WA-025 | 21-11-2026 | Assignment Proktor memakai interval [start,end), overlap lintas kelas/ujian ditolak, locking/current read InnoDB, serta audit atomik. | Mencegah double-booking dan race condition. | MySQL 8 concurrency, lock order Exam→Guru→assignment, idempotency, revoke/reschedule/confirm. | ACTIVE — runtime proof NOT RUN | Pemeriksaan overlap yang hanya memakai SQLite/unit test. |

### 19.1 Batas kewenangan Admin (larangan eksplisit)
Admin tidak boleh mengelola bank soal/kunci jawaban, membuat/mengubah/menerbitkan ujian, mapel/jadwal/peserta ujian, snapshot/token, Attempt/Answer/Score, penilaian/koreksi, hasil/analitik ujian Guru, monitoring operasional, close/finalize/reopen, reset Attempt, Force Submit, takeover perangkat, perpanjangan waktu, atau status pelaksanaan. Admin hanya membaca metadata minimum untuk assignment Proktor.

### 19.2 Status migrasi role
Tidak ada migration atau DML yang dijalankan dalam rekonsiliasi ini. Preflight dan mapping harus fail-closed. Konflik identitas/tenant/pivot/FK menghentikan proses untuk manual review. `OPERATOR` hanya identifier legacy migration/test, bukan role runtime.

### 19.3 Status assignment
Status minimum: PENDING, CONFIRMED, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED. PENDING/NEEDS_REVIEW/REVOKED/EXPIRED/COMPLETED tidak memberikan capability. Capability hanya untuk assignment CONFIRMED yang valid dan berada pada interval server [starts_at, expires_at).


## 19.4 DEC-WA-026 — Status assignment dan capability Proktor (KEPUTUSAN FINAL, ACTIVE)

Tanggal: 21-11-2026
Status: ACTIVE — ditegaskan eksplisit oleh pengguna.

1. Status assignment yang sah hanya `PENDING`, `CONFIRMED`, `NEEDS_REVIEW`, `REVOKED`, `EXPIRED`, dan `COMPLETED`.
2. `PENDING` tidak memberikan capability.
3. Hanya `CONFIRMED` yang dapat memberikan capability, setelah seluruh validasi Guru aktif, TeacherProfile, tenant, exam, rombel, schedule revision, permission/assignment scope, revoke/conflict, dan waktu server lulus.
4. `ACTIVE` bukan status assignment dan tidak boleh ditambahkan sebagai enum atau status pemberi capability. Istilah ACTIVE tetap sah untuk status akun Guru dalam konteks terpisah.
5. `NEEDS_REVIEW`, `REVOKED`, `EXPIRED`, dan `COMPLETED` tidak memberikan capability.
6. Capability hanya berlaku selama interval setengah terbuka `starts_at <= serverNow < expires_at`; pada `serverNow >= expires_at` akses langsung ditolak tanpa menunggu cron.
7. Setiap request memvalidasi ulang seluruh syarat. Reschedule oleh Guru melalui `ExamScheduleChangeService` mengubah assignment terdampak ke NEEDS_REVIEW secara atomik; setelah Admin melakukan review/konfirmasi sah, status kembali CONFIRMED, bukan ACTIVE.
8. Source implementation dan seluruh runtime test tetap OPEN/NOT RUN sampai ada persetujuan implementasi terpisah dan bukti uji.

**Menggantikan:** Nota Klarifikasi Status Assignment Proktor versi OPEN/PROPOSED dan Addendum 34 spesifikasi yang kini SUPERSEDED. Detail normatif berada pada Addendum 35 spesifikasi rinci.