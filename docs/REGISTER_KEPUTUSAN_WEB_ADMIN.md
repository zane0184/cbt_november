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

### Admin Madrasah = Operator Madrasah
- Satu role, satu akun/portal/dashboard/sidebar/permission set.
- Label utama: **Admin Madrasah**; brand sidebar: **Panel Admin Madrasah**.
- “Operator Madrasah” hanya alias/istilah penjelas, bukan role kedua.
- Jangan mengubah identifier role lama sebelum audit kompatibilitas data/source.

### Guru
- Portal/role terpisah.
- Pemilik alur akademik CBT sesuai assignment dan permission: bank soal/media, pembuatan ujian, mapel dalam ujian, jadwal akademik, konfigurasi, snapshot, token/penerbitan, penilaian, jawaban, analisis, hasil.

### Proktor
- Bukan role permanen dan bukan portal tersendiri.
- Proktor adalah Guru yang mendapat assignment pengawasan pada ujian tertentu.
- Semua Guru aktif pada madrasah yang sama dapat dipilih; jangan membatasi kategori Guru kelas/Wali Kelas/Guru Mapel.
- Status/hak Proktor sementara berasal otomatis dari assignment dan jadwal yang berlaku.
- Hak dibatasi ujian/sesi/ruang/waktu yang ditugaskan. Waktu server menjadi acuan.
- Penetapan/penggantian/pembatalan wajib memiliki otorisasi, validasi konflik, dan audit trail.

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
10. Status Proktor sementara aktif otomatis ketika jadwal pengawasan berlaku.

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

1. Satu madrasah = satu instalasi = satu database sendiri.
2. Tidak ada shared operational database atau pertukaran data akademik/operasional antarmadrasah.
3. tenant_id, TenantContext, middleware, global scope, policy, audit, dan composite FK dipertahankan sebagai pertahanan internal; bukan untuk menyatukan database.
4. TenantContext berasal dari identitas instalasi dan user sah; tidak ada tenant switching atau X-Tenant-Override.
5. Installer wajib memastikan satu tenant kanonis pada DB instalasi. Source sekarang membuat tenant dan LocalInstallationIdentity, tetapi invariant satu tenant per DB belum dibuktikan.
6. Setiap domain mempunyai .env, APP_KEY, DB credentials, storage, secrets, dan backup sendiri. .env.example configurable, tetapi uji install lintas domain dan interaksi registered_domain/lisensi belum dijalankan.
7. Web Super Admin/Publisher ditunda; scope mendatang hanya lisensi, aktivasi, pembayaran, bukan data akademik.

## 14. Role dan kepemilikan ujian

- ADMIN_MADRASAH adalah satu permission profile untuk Admin Madrasah/Operator. OPERATOR legacy dipertahankan sementara untuk kompatibilitas.
- Semua Guru aktif dalam instalasi yang sama dapat ditugaskan sebagai Proktor.
- Tidak ada role atau portal Proktor permanen. Role PROKTOR lama tidak dihapus sebelum migrasi aman dan tidak menjadi dasar akses baru.
- Guru memiliki soal, ujian, mapel dalam ujian, jadwal akademik ujian, snapshot, token/penerbitan, jawaban, penilaian, analisis, dan hasil.
- Admin hanya membaca metadata ujian Guru dan menetapkan pengawas. Admin tidak boleh monitoring, close, token, grading, jawaban, hasil, atau mengubah metadata ujian.
- Admin boleh mengelola master katalog mapel madrasah; mapel yang dipilih/ditautkan pada ujian tetap milik Guru.

## 15. Aturan assignment Proktor

- Assignment terikat ke tenant, exam, rombel/class, Guru, actor Admin, periode [starts_at, expires_at), status, alasan, versi/fingerprint jadwal, dan histori.
- Interval setengah terbuka: starts_at <= now < expires_at.
- Overlap: existing.starts_at < new.expires_at AND existing.expires_at > new.starts_at.
- Satu Guru tidak boleh mempunyai assignment overlap lintas kelas/rombel maupun lintas ujian.
- Transaksi lock exam, lock baris Guru sebagai mutex, query overlap locking/current read, lalu simpan. Lock Guru sudah ada di source; pemeriksaan overlap belum ada.
- Perubahan jadwal Guru memicu NEEDS_REVIEW; Admin mengonfirmasi ulang setelah validasi konflik. Tidak ada pergeseran roster diam-diam.
- Expiry ditolak otomatis pada setiap request dengan waktu server. Revoke langsung memutus akses. NEEDS_REVIEW, REVOKED, dan COMPLETED tidak memberi hak.
- Semua aksi create/change/replace/review/confirm/revoke/complete dicatat append-only.

## 16. Koreksi audit

Penguncian baris Guru pada ExamProctorAssignmentService sudah ada; temuan concurrency lama diperjelas: masalahnya bukan tidak ada mutex, tetapi tidak ada query overlap setelah mutex. TenantScoped juga belum otomatis fail-closed saat TenantContext kosong. Admin saat ini memiliki permission/policy monitoring dan close yang harus dicabut dari profile final.



## 17. Koreksi final Admin Madrasah = Operator
Keputusan final: satu role aktif ADMIN_MADRASAH. OPERATOR 100% sama dengan Admin Madrasah dan bukan role kedua. Satu akun/login/portal/dashboard/sidebar/permission profile/kewenangan. Tidak ada runtime branching untuk OPERATOR atau ADMIN. Legacy ADMIN tenant-local juga diverifikasi dan dipetakan ke ADMIN_MADRASAH; SUPER_ADMIN tidak dipetakan. KAMAD tetap terpisah.

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
