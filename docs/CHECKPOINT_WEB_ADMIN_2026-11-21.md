# CHECKPOINT PROYEK — WEB ADMIN MADRASAH
**Tanggal checkpoint:** 21 November 2026  
**Zona waktu:** Asia/Jakarta (WIB)  
**Repository:** `zane0184/cbt_november`  
**Branch dokumen handover:** `docs/web-admin-agent-handover-20261121`

## 1. POSISI PROYEK

<status>**STATUS: MENUNGGU AUDIT SOURCE WEB ADMIN — BELUM ADA IMPLEMENTASI WEB ADMIN BERDASARKAN SPESIFIKASI BARU**</status>

Fokus aktif yang disetujui pengguna adalah menyelesaikan Web Admin Madrasah secara menyeluruh. Portal Guru tidak boleh mulai sebelum Web Admin selesai, diuji, divalidasi, dilaporkan, dan disetujui. Portal Siswa hanya setelah Portal Guru selesai dan disetujui.

## 2. DOKUMEN ACUAN KANONIS

1. Spesifikasi induk Web Admin:
   `docs/SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md`
   Branch: `docs/admin-web-spec-20261121`
   Commit terakhir yang sebelumnya diverifikasi: `3e0b9195c9768c0d892322f926f25a6b9c4fb135`
   Blob SHA terakhir: `9882e9ba7f7fff216aa8d367ef655da8f1f54e48`
   Status PR historis #6: Draft/Open/Unmerged pada pemeriksaan sebelumnya; agent baru wajib cek ulang.

2. Prompt agent baru:
   `docs/PROMPT_INDUK_AGENT_BARU_WEB_ADMIN.md`

3. Checkpoint ini:
   `docs/CHECKPOINT_WEB_ADMIN_2026-11-21.md`

4. Indeks handover:
   `docs/HANDOVER_WEB_ADMIN_AGENT_BARU.md`

5. Prompt induk audit keseluruhan proyek (acuan tambahan, bukan perluasan scope):
   `docs/PROMPT_INDUK_AUDIT_MENYELURUH_CBT_MADRASAH.md`
   Branch historis: `docs/prompt-induk-audit-20261120`
   Commit historis: `396b06aaaa0a84b0cef355441fc39b93bd170126`

## 3. AUDIT — KONDISI YANG DAPAT DIBUKTIKAN SAAT CHECKPOINT

### 3.1 GitHub
- Repository ditemukan: `zane0184/cbt_november`.
- Branch spesifikasi ditemukan: `docs/admin-web-spec-20261121`.
- File spesifikasi berhasil di-fetch. Blob SHA: `9882e9ba7f7fff216aa8d367ef655da8f1f54e48`.
- PR #6 ditemukan dengan judul “docs: spesifikasi induk redesign Web Admin Madrasah”.
- PR #5 ditemukan dengan judul “[DRAFT] Web CBT — perbaikan alur UI, filter, dan navigasi berbasis permission”.
- Data pencarian PR tidak memberikan status state/head SHA lengkap; jangan menyimpulkan status terkini hanya dari hasil pencarian. Periksa detail PR langsung.
- Belum dilakukan pemeriksaan ulang seluruh branch source, commit HEAD, workflow terbaru, dan PR checks pada checkpoint ini.

### 3.2 Isi spesifikasi
Dokumen induk mencakup mandat Web Admin saja, batas kewenangan Admin, arsitektur, audit source, UI/UX, sitemap, dashboard, identitas madrasah, akademik, Guru/Siswa sebagai data administrasi, impor Excel, laporan administratif, backup/restore, keamanan, profil Admin, master resmi Kurmer/KBC, Panca Cinta, DPL, Pembelajaran Mendalam, versioning, seed, dan acceptance criteria.

Klasifikasi fitur wajib:
- SUDAH ADA DAN BERFUNGSI
- ADA TAPI BELUM LENGKAP
- BACKEND ONLY
- UI ONLY
- BELUM ADA
- BERMASALAH/BERISIKO
- BELUM TERVERIFIKASI

### 3.3 Komponen source historis (belum diverifikasi ulang)
Pernah tercatat:
- `backend-laravel/app/Livewire/Operator/OperatorDashboard.php`
- `backend-laravel/app/Livewire/Admin/UserManagement.php`
- `backend-laravel/app/Livewire/Admin/StudentRombelManagement.php`
- `backend-laravel/app/Livewire/Admin/AcademicManagement.php`
- `backend-laravel/app/Livewire/Admin/CurriculumManagement.php`
- `backend-laravel/resources/views/layouts/app.blade.php`
- `backend-laravel/resources/views/livewire/admin/`
- `backend-laravel/routes/web.php`

Route historis:
- `/operator/dashboard`
- `/admin/users`
- `/admin/rombel-students`
- `/admin/academic`
- `/admin/curriculum`

Semua path/route tersebut wajib diverifikasi terhadap commit source aktual; jangan diasumsikan masih ada atau berfungsi.

## 4. KEPUTUSAN PENGGUNA YANG SUDAH FINAL

- Web Admin saja sampai selesai dan disetujui.
- Jangan audit/desain/implementasi Portal Guru atau Siswa sekarang.
- Admin mengelola administrasi dan data induk, bukan operasional CBT Guru.
- Guru kelak memiliki operasional CBT penuh sesuai assignment/permission tanpa approval Admin.
- Proktor hanya penugasan operasional sementara, bukan portal permanen.
- Satu madrasah = satu instalasi = satu database; tenant guards tetap dipertahankan.
- Kurikulum hanya Kurmer dan KBC; jangan tambah kurikulum lain dan jangan hapus histori lama.
- Fase A kelas 1–2, B kelas 3–4, C kelas 5–6.
- Panca Cinta:
  1. Cinta Allah dan Rasul-Nya
  2. Cinta Ilmu
  3. Cinta Diri dan Sesama
  4. Cinta Lingkungan
  5. Cinta Tanah Air
- Tagging butir soal/instrumen non-tes dilakukan Guru pada tahap Guru kelak. Admin mengelola master/konfigurasi resmi saja sekarang.
- DPL delapan dimensi sesuai regulasi yang dirujuk spesifikasi.
- Jangan klaim pengukuran karakter dari tagging saja.
- Tidak ada deployment, migration produksi, perubahan VPS, merge PR, atau aktivasi Device Lease/Fencing tanpa persetujuan yang relevan.
- Build Android wajib via Android Studio; Android di luar scope sekarang.

## 5. STATUS IMPLEMENTASI DAN PENGUJIAN

| Area | Status checkpoint |
|---|---|
| Spesifikasi induk Web Admin | Tersedia di branch dokumentasi |
| Prompt agent baru Web Admin | Dibuat pada branch handover ini |
| Audit source Web Admin menyeluruh | BELUM DIMULAI pada checkpoint ini |
| Desain detail setelah audit | BELUM DIMULAI |
| Persetujuan desain | BELUM DIMINTA |
| Implementasi Web Admin sesuai spesifikasi | BELUM DIMULAI |
| Pengujian Web Admin | BELUM DIMULAI |
| Validasi operasional Admin | BELUM DIMULAI |
| Portal Guru | DITAHAN |
| Portal Siswa | DITAHAN |
| Android native | DITUNDA sampai Juni 2027 |
| Deployment VPS/produksi | TIDAK DILAKUKAN |

Tidak ada source aplikasi, migration, database, .env, konfigurasi VPS, atau deployment yang diubah sebagai bagian dari checkpoint dokumentasi ini.

## 6. TEMUAN TERPISAH YANG HARUS DIHORMATI

### 6.1 P0.1 database
Laporan historis menyatakan P0.1 BLOCKED — belum ada patch, backup, atau test. Runtime sebelumnya tidak memiliki PHP 8.3, Composer 2, MySQL, PDO driver, Docker/Podman, dan akses SSH. Agent baru harus memeriksa ulang bila pekerjaan P0 diminta, tetapi P0 bukan scope Web Admin dan tidak boleh disentuh sekarang.

### 6.2 Device Lease/Fencing
Dokumen provenance A00 menyimpulkan belum ada source kanonis end-to-end yang dapat ditetapkan dari bukti yang tersedia. Beberapa laporan implementasi historis tidak cocok dengan source GitHub kandidat; raw workspace/backup tidak dapat diverifikasi dan manifest memiliki hash berbeda. Karena itu Device Lease/Fencing tetap HOLD, enforcement OFF, dan tidak boleh direkonstruksi berdasarkan laporan saja.

### 6.3 Runtime
Runtime yang pernah dilaporkan pada audit terpisah:
- PHP CLI 8.4.24; PHP 8.3 tidak tersedia.
- Composer 2 tidak tersedia.
- MySQL tidak tersedia; PDO drivers kosong.
- SSH/SCP dan Docker/Podman tidak tersedia.
- Nginx binary tersedia tetapi tidak berjalan.
Ini catatan historis, wajib dites ulang sebelum digunakan sebagai fakta runtime baru.

## 7. LANGKAH PERTAMA AGENT BERIKUTNYA

1. Baca `PROMPT_INDUK_AGENT_BARU_WEB_ADMIN.md` dan `SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md` sampai selesai.
2. Periksa HEAD semua branch relevan dan status PR #5/#6.
3. Pilih baseline source Web berdasarkan bukti commit aktual; jangan menganggap branch historis masih terkini.
4. Audit recursive source Web Admin secara READ-ONLY.
5. Periksa runtime yang tersedia.
6. Buat matriks fitur dan authorization/security.
7. Susun desain dan test plan.
8. Serahkan laporan audit + desain kepada pengguna dan tunggu persetujuan sebelum coding.

## 8. LARANGAN

- Jangan mulai coding sebelum audit dan desain disetujui.
- Jangan bekerja di branch dokumentasi sebagai branch implementasi.
- Jangan merge PR #5/#6.
- Jangan force-push.
- Jangan melakukan production migration/deployment.
- Jangan mengubah database produksi atau konfigurasi VPS.
- Jangan memulai Portal Guru/Siswa.
- Jangan mengubah source Android atau Device Lease/Fencing.
- Jangan mengklaim Web Admin selesai tanpa bukti end-to-end dan persetujuan pengguna.

## 9. CATATAN INTEGRITAS

Checkpoint ini adalah posisi dokumentasi dan keputusan. Source aktual tetap menjadi dasar kebenaran. Jika checkpoint berbeda dari source/PR aktual, agent wajib mencatat perbedaan dan memperbarui checkpoint setelah verifikasi.


## 10. TEMUAN BARU DARI REVIEW SCREENSHOT — SIDEBAR DAN LABEL PORTAL

Pengguna melaporkan dua masalah pada screenshot dashboard yang ditinjau:
- Sidebar gelap tidak dapat digulir, sehingga menu yang tidak muat di viewport tidak dapat dijangkau.
- Teks brand “Panel Admin & Guru” tidak konsisten karena halaman yang dimaksud adalah halaman Admin, bukan portal gabungan Admin/Guru.

**Keputusan/requirement baru:**
1. Sidebar desktop harus scrollable secara vertikal dan independen dari konten utama.
2. Seluruh menu harus tetap dapat diakses pada viewport pendek; jangan mengatasi dengan menyembunyikan item atau sekadar mengecilkan menu.
3. Header brand sidebar tetap terbaca ketika menu di-scroll.
4. Mobile memakai drawer/off-canvas yang tetap scrollable, dapat ditutup, dan mendukung keyboard/focus.
5. Branding portal harus “Panel Admin Madrasah” atau “Admin Madrasah”; hapus “Panel Admin & Guru”.
6. Judul halaman, badge role, breadcrumb, menu, dan profil harus sesuai role aktual. Jangan tampilkan “Dashboard Operator” atau “Role: Operator Madrasah” pada sesi Admin.
7. Navigasi Admin tidak boleh mencampur fitur operasional Guru. Role display harus dinamis dari autentikasi/permission, bukan hard-coded.
8. Perbaikan harus ditelusuri ke source layout/sidebar/menu/role; bukan hanya mengganti teks pada screenshot/mockup.

**Acceptance criteria:** uji desktop 1366×768 dan viewport pendek; uji scroll sidebar vs konten utama secara independen; uji mobile drawer; cari seluruh string label yang salah; screenshot sebelum/sesudah dan bukti uji browser.

**Status:** TEMUAN DICATAT — BELUM DIAUDIT ROOT CAUSE, BELUM DIIMPLEMENTASIKAN, BELUM DIUJI. Temuan ini masuk audit UI/UX dan backlog desain Web Admin. Tetap ikuti gerbang: audit read-only, desain, persetujuan pengguna, baru implementasi.


## 11. KEPUTUSAN FINAL ROLE — ADMIN_MADRASAH = OPERATOR

ADMIN_MADRASAH adalah satu-satunya role aktif Admin Madrasah. OPERATOR adalah nama/identifier lama, bukan role kedua, portal, permission profile, atau tingkat kewenangan. Semua akun lama OPERATOR/ADMIN tenant-local dipetakan ke ADMIN_MADRASAH melalui migration aman.

Setiap orang tetap memiliki user record sendiri. Akun Admin lama dan Operator lama tidak digabung jika pemiliknya berbeda; user ID, password hash, tenant, profile, sesi/token sah, dan histori dipertahankan. Setelah migration tidak ada role/pivot/grant aktif OPERATOR atau ADMIN, dan tidak ada runtime hasRole(OPERATOR)/hasRole(ADMIN). UI hanya memakai label Admin Madrasah.

## 12. KEPUTUSAN FINAL PROKTOR

Proktor adalah capability sementara bagi Guru aktif yang ditugaskan pada ujian tertentu. Tidak ada role atau portal Proktor permanen. Semua Guru aktif pada madrasah yang sama dapat dipilih. Akun legacy PROKTOR hanya dikonversi ke GURU jika TeacherProfile valid dan tenant cocok; selain itu manual review.

Admin Madrasah mengelola assignment; Guru tetap pemilik ujian dan jadwal akademik. Hak Proktor dibatasi pada exam/rombel/waktu/status assignment yang sah dan divalidasi server pada setiap request.

## 13. PENEGASAN FINAL ASSIGNMENT PENGAWAS

Semua Guru aktif dapat menerima assignment pengawasan. Capability Proktor aktif otomatis hanya selama assignment sah dan rentang waktu berlaku. Tidak ada role atau portal Proktor permanen, dan tidak ada aktivasi manual. Perubahan, pencabutan, pembatalan, kedaluwarsa, atau NEEDS_REVIEW menghentikan capability sesuai waktu server.

## 14. CATATAN HISTORIS — KEPUTUSAN JADWAL ADMIN TELAH DIGANTIKAN

Bagian ini sebelumnya menggambarkan Admin membuat kalender/jadwal ujian institusi. Interpretasi itu sudah digantikan oleh alur final pada Bagian 15 dan spesifikasi Bagian 30. Admin Madrasah tidak membuat atau mengubah jadwal akademik ujian Guru; Admin hanya membaca metadata ujian dan menetapkan/meninjau/mencabut pengawas. Jangan menggunakan bagian lama ini sebagai requirement aktif.

## 15. ALUR FINAL — UJIAN DIBUAT GURU, PROKTOR DITETAPKAN ADMIN DARI DAFTAR UJIAN

Klarifikasi terbaru pengguna:
- Guru membuat soal dan menyiapkan ujian, termasuk mata pelajaran dan jadwal ujian.
- Setelah ujian tersimpan, data ujian beserta mapel dan jadwal otomatis muncul pada list Web Admin.
- Admin Madrasah memilih ujian dari list tersebut dan menentukan Guru yang bertugas sebagai pengawas/Proktor.

Dengan demikian, Admin **tidak membuat ulang jadwal ujian** dan tidak mengetik ulang mapel. List Admin mengambil data dari ujian yang dibuat Guru. Admin bertanggung jawab pada penugasan pengawas.

Batas kewenangan:
- Guru memiliki soal, mapel ujian, jadwal akademik ujian, konfigurasi CBT, token, penilaian, dan hasil sesuai permission.
- Admin melihat metadata/detail ujian secara read-only dan mengatur roster pengawas.
- Perubahan jadwal/mapel oleh Guru harus tercermin di list Admin. Jika memengaruhi roster, tandai perlu ditinjau Admin; jangan mengubah atau menghapus assignment diam-diam.
- Semua Guru aktif pada madrasah yang sama dapat dipilih sebagai pengawas sesuai edaran resmi terverifikasi.
- Saat jadwal berlaku, Guru yang ditugaskan otomatis memiliki konteks/hak Proktor sementara untuk ujian tersebut.

UI Web Admin: **Daftar Ujian & Penugasan Proktor**, menampilkan nama ujian, mapel, Guru pembuat, kelas/rombel/peserta bila tersedia, tanggal, sesi/jam, status ujian, status penugasan pengawas, serta aksi “Atur/Tentukan Pengawas”. Informasi akademik ujian bersifat read-only untuk Admin.

Koreksi terhadap catatan sebelumnya: Admin tidak menyusun kalender akademik ujian dari nol. Admin menetapkan pengawas berdasarkan jadwal ujian yang sudah dibuat Guru. Modul tidak boleh membuat dua sumber jadwal yang bertentangan.


---

## 16. CHECKPOINT KONSOLIDASI PAKET HANDOVER — 21 NOVEMBER 2026

Paket handover diperluas untuk pergantian agent. Dokumen tambahan:
- `docs/00_PAKET_HANDOVER_WEB_ADMIN_AGENT_BARU.md` — indeks/pintu masuk tunggal.
- `docs/REGISTER_KEPUTUSAN_WEB_ADMIN.md` — register keputusan kanonis.
- `docs/MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md` — template audit berbasis bukti.
- `docs/MATRIKS_PENGUJIAN_WEB_ADMIN.md` — matriks tes dan acceptance.
- `docs/PETA_DOKUMEN_PROVENANCE_CBT.md` — peta pencarian dokumen dan aturan provenance.

### Hasil nyata pada GitHub
Kelima file di atas berhasil dibuat pada branch dokumentasi `docs/web-admin-agent-handover-20261121`. Commit masing-masing:
- Paket indeks: `a6b5caf37de0253fd150c00ff831c01dbfa5211d`
- Register keputusan: `3faca4a3eb8370a6a6be42cde13b4d3c00fc7e19`
- Matriks audit: `bc21ca0f5c0e5997ac7c846b6f052eaf7b55f7c4`
- Matriks pengujian: `7c32c8d2662b577ecdb7066f631a44198c314116`
- Peta provenance: `54104fdbb495b2505219aab55d7511f9f22f4479`

### Koreksi prioritas alur jadwal ujian
Bagian 14 checkpoint lama dan bagian 29 spesifikasi yang menggambarkan Admin membuat kalender/jadwal ujian dari awal adalah interpretasi terdahulu dan **tidak boleh dipakai sebagai alur final**. Gunakan:
- Bagian 15 checkpoint;
- Bagian 30 spesifikasi;
- Bagian 16 prompt agent;
- `docs/REGISTER_KEPUTUSAN_WEB_ADMIN.md`.

Alur final: Guru membuat soal/ujian, mapel dan jadwal akademik; data ujian otomatis muncul di list Admin; Admin hanya menentukan Guru pengawas. Detail akademik read-only untuk Admin. Perubahan jadwal Guru memicu peninjauan assignment, bukan mutasi roster diam-diam.

### Status pekerjaan
Konsolidasi ini hanya dokumentasi. Tidak ada source aplikasi, migration, database, .env, secret, konfigurasi VPS, atau deployment yang diubah. Audit source Web Admin menyeluruh tetap BELUM DIBUKTIKAN selesai; desain belum disetujui; implementasi perbaikan total belum dimulai berdasarkan spesifikasi final.

### Langkah agent berikutnya
Ikuti bagian 1–2 pada prompt induk. Verifikasi status PR/branch/commit dan source terbaru; cari seluruh laporan terkait; lakukan audit READ-ONLY; serahkan AUDIT + DESAIN; berhenti menunggu persetujuan.



# ADDENDUM CHECKPOINT — REKONSILIASI AUDIT 21 NOVEMBER 2026

## Posisi source yang diverifikasi
- main HEAD: 91dae257b2210ef89c90534185a3cbdd8979ffe0; tree 24 entri dan tidak memuat source Laravel aktif. Main bukan basis build/deployment yang aman saat ini.
- Base PR #5: 158227362eea82fc7ec7e9c232736596015739d2; tree 665 entri.
- Kandidat source Web Admin: fix/web-portal-usability-rbac-20261120, SHA ade5daa6cf853c164c4f51ec6e25ca68be40b4f2; tree 761 entri.
- SHA kandidat source sama dengan baseline audit 21 November. Tidak ada source commit baru pada branch tersebut setelah audit.
- PR #5/#6/#7 masih Open, Draft, Unmerged.
- Source candidate tersedia di branch PR #5, tetapi belum masuk main.

## Hasil rekonsiliasi
- Mayoritas 23 temuan ADM masih berlaku.
- Koreksi penting: service Proktor sudah mengunci row Guru, tetapi belum menjalankan query overlap; assignment konflik masih dapat tersimpan.
- scopeActiveNow sudah menerapkan starts_at inklusif dan expires_at eksklusif.
- Assignment belum memiliki rombel_id, status NEEDS_REVIEW, atau event perubahan jadwal.
- Admin masih memiliki grant/policy monitoring dan close yang tidak sesuai batas final.
- TenantScoped belum otomatis fail-closed bila TenantContext kosong; perlu guard dan tes.
- Detail lengkap ada pada AUDIT_REKONSILIASI_DESAIN_WEB_ADMIN_2026-11-21.md.

## Arsitektur/keputusan yang dipertahankan
- Satu madrasah = satu instalasi = satu database sendiri; tidak ada shared operational DB dan tidak ada pertukaran data akademik antarmadrasah.
- tenant_id/TenantContext/middleware/global scope/policy/composite FK dipertahankan sebagai pertahanan internal.
- Admin Madrasah dan Operator satu identitas/profile; OPERATOR legacy dipertahankan sementara.
- Semua Guru aktif dapat ditugaskan; tidak ada role/portal Proktor permanen.
- Admin hanya membaca metadata ujian Guru dan menetapkan pengawas.
- Guru memiliki soal, ujian, mapel yang dipakai pada ujian, dan jadwal akademik.
- Super Admin/Publisher ditunda; kelak hanya lisensi dan pembayaran.

## Rencana
W0–W10 disusun dalam laporan rekonsiliasi. Tidak ada tahap yang dimulai. W0 menunggu persetujuan desain.

## Perubahan aktual sesi ini
Hanya dokumentasi pada branch docs/web-admin-audit-reconciliation-20261121:
- docs/SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md
- docs/REGISTER_KEPUTUSAN_WEB_ADMIN.md
- docs/MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md
- docs/MATRIKS_PENGUJIAN_WEB_ADMIN.md
- docs/CHECKPOINT_WEB_ADMIN_2026-11-21.md
- docs/AUDIT_REKONSILIASI_DESAIN_WEB_ADMIN_2026-11-21.md

Tidak ada source aplikasi, migration, database, .env, secret, PR existing, merge, deployment, atau test runtime yang diubah/dijalankan.
Parent docs SHA sebelum rekonsiliasi: a7bef3dea2b1f57c79d34c775822bd5bde24998f. Final docs-only commit SHA dicantumkan pada laporan handover ChatGPT setelah branch diperbarui.



# ADDENDUM CHECKPOINT — SOURCE CANONICAL & DESIGN CLOSURE (21 NOVEMBER 2026)

## Sumber dan branch
- Baseline 6 November: historical report mencatat 105.365.379 byte, 16.080 entri, CRC PASS, SHA b7017b7d0ccc2a93cc6b522e8a34ccacffe28d1a7e081c68dfded83238785ad0; raw ZIP tidak ada pada runtime sekarang.
- V50.7.4 B0 snapshot: SHA 697fc62ef41423b079cbedb72d297322044dd9fca13aa5ca40115cc0de14dc1a, 957 entri, 954/954 manifest B0; snapshot referensi bukan worktree aktif.
- Worktree 14-11 manifest 533 records; 38 target pada audit historis 0 match, 26 mismatch, 12 tidak ditemukan di V50.10 mirror.
- Current /mnt/data/CBT_MADRASAH_RECONSTRUCTION/ dan worktree 14-11 tidak ada di filesystem sesi ini. CBT_MADRASAH_PAKET_FINAL.zip raw tidak dapat diakses; tujuh part tidak ditemukan.
- GitHub branch main SHA 91dae257...: 24 entries = 10 files +14 dirs, 0 backend active.
- Working source Web Admin yang direkomendasikan (belum disetujui): recovery/source-preservation-20261121 SHA 7fc45bf71ac1ebd375eba44fd57c983bf18d66e0; 768 entries, 531 files, 237 dirs, 4.425.698 blob bytes, 347 backend active files, 160 backup files. Source code sama dengan PR #5; hanya lima dokumen recovery tambahan.
- Source canonical seluruh CBT belum locked karena byte baseline/worktree belum tersedia untuk comparison baru. Recovery branch hanya rekomendasi Web Admin backend.

## Koreksi role final
- ADMIN_MADRASAH adalah satu-satunya role Admin aktif. OPERATOR = Admin Madrasah 100% sama; bukan role kedua/portal/permission profile.
- Semua akun OPERATOR dan ADMIN tenant-local dipetakan melalui migration aman ke ADMIN_MADRASAH. Runtime tidak memeriksa OPERATOR atau ADMIN.
- PROKTOR bukan role; semua Guru aktif dapat ditugaskan. Legacy PROKTOR hanya dikonversi ke GURU bila TeacherProfile valid; selain itu manual review.
- KAMAD dan SUPER_ADMIN tidak dipetakan.
- Source aktual masih melanggar keputusan ini pada seeder/bootstrapper/installer/login resolver/routes/policies/dashboard/layout/tests.

## Koreksi tenant
- InstallationIdentityService sudah memaksa satu Tenant per DB dan maksimal satu LocalInstallationIdentity.
- TenantScoped sudah fail-closed ketika context kosong.
- IdentifyTenant mengabaikan X-Tenant-Override dan memakai tenant user.
- Batas tersisa: SystemTenantSeeder harus diisolasi, User model diuji fail-closed, registered_domain existing identity harus dicocokkan dengan APP_URL host.

## Proktor
- Service sekarang lock exam dan Guru saat assign tetapi belum query overlap; revoke belum memakai lock Guru yang sama.
- Assignment belum rombel_id; tidak ada NEEDS_REVIEW atau schedule revision.
- API ExamController::update dan Livewire ExamManagement::saveExam adalah jalur perubahan jadwal yang perlu disatukan.
- Desain final menggunakan [start,end), locking/current read, lock order exam→Guru→assignment, ExamScheduleChangeService, NEEDS_REVIEW, per-request capability check, expiry/revoke deny, dan AuditService + append-only events.

## W0–W10
Rencana lengkap, file sasaran, acceptance criteria, risiko dan test cases ada pada addendum Source Canonical & Design Closure di laporan audit. Tidak ada tahap yang dimulai.

## Perubahan sesi ini
Hanya enam file dokumentasi di branch docs/source-canonical-design-closure-20261121. Tidak ada source aplikasi, migration, DB, .env, secret, PR, merge, deployment, atau runtime test yang diubah/dijalankan.
