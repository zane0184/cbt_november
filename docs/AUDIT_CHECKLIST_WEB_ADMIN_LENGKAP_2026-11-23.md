# AUDIT CHECKLIST MENYELURUH — WEB ADMIN MADRASAH
Tanggal pemeriksaan: 23 November 2026 (WIB)
Repository: `zane0184/cbt_november`
Dokumen ini ditulis untuk koordinasi seluruh coding agent. Checklist hanya dicentang jika ada bukti source yang sesuai; status runtime/CI dicatat terpisah.

## 0. Identitas pemeriksaan dan sumber kebenaran

### Sumber yang diperiksa
- Keputusan arsitektur terbaru: `docs/KEPUTUSAN_ARSITEKTUR_INSTALASI_MANDIRI_DAN_ROLE_2026-11-23.md`, branch `work/web-admin-implementation-20261123`, blob SHA `4033487b2de94dc8c22078918cf7a033e95e3696`.
- Master cakupan Web Admin: Issue #12 — https://github.com/zane0184/cbt_november/issues/12.
- Audit read-only awal: branch `audit/web-admin-readonly-20261123`, baseline yang diperiksa dokumen tersebut adalah PR #5 SHA `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`. Dokumen audit awal dipakai sebagai peta temuan, bukan bukti status source terbaru.
- Source implementasi Admin: branch `work/web-admin-implementation-20261123`, HEAD `e137e5b4f1ac42c899cd7cd308754fad79940e17`.
- Remediasi Admin: PR #18, branch `work/web-admin-remediation-20261123`, HEAD `42a0de2c1f86b8e76e34e1b74ab0feaca90def3a`, base SHA `e137e5b4f1ac42c899cd7cd308754fad79940e17`.
- Perubahan PR #18: 84 commits, 58 files changed. Status open, Draft, belum merge.
- Tidak ada workflow run, combined status check, atau review PR yang ditemukan untuk HEAD PR #18 saat pemeriksaan ini.

### Keputusan terbaru yang mengikat
Dokumen arsitektur 23 November adalah acuan lebih baru daripada teks cakupan lama yang bertentangan:
- Instalasi dan database berdiri sendiri per madrasah; tidak ada database akademik bersama/lintas madrasah.
- Role lokal hanya Admin Madrasah, Guru, Siswa.
- Operator dilebur ke Admin; Kamad hanya metadata; Proktor adalah Guru dengan assignment sementara, bukan role permanen.
- Admin tidak boleh membuat/menerbitkan/mengubah/mengatur ujian Guru, mengakses Bank Soal, token, Attempt, jawaban, monitoring peserta, penilaian/nilai, force submit, reset Attempt, atau menutup ujian.
- Admin hanya mengelola penugasan Guru pengawas sementara.
- Web Super Admin dan Android Super Admin ditunda sampai Juni 2027.
- Tidak boleh migration produksi, merge, deployment, atau pemindahan data tanpa rencana, backup, tes disposable, dan persetujuan eksplisit.

**Penting:** dokumen Issue #12 berisi beberapa ketentuan lebih lama yang mengizinkan Admin mengelola ruang/roster/monitoring. Bagian itu tidak boleh dipakai untuk membenarkan implementasi yang bertentangan dengan keputusan arsitektur 23 November.

## 1. Legenda checklist

- [x] **SESUAI SECARA STATIS** — source yang diperiksa menunjukkan perilaku sesuai keputusan; belum otomatis berarti CI/runtime lulus.
- [ ] **BELUM LULUS** — fitur belum lengkap, bertentangan, atau bukti tidak cukup.
- **PARSIAL** — sebagian source tersedia, tetapi acceptance criteria belum terpenuhi.
- **BLOCKED** — menunggu kontrak, persetujuan, atau pengujian dependency.
- **NOT VERIFIED** — belum ada bukti eksekusi yang memadai.

Tidak ada fitur yang diberi status "siap produksi" hanya karena komponen, route, migration, atau file test tersedia.

## 2. Ringkasan eksekutif

**Status keseluruhan Web Admin: BELUM LENGKAP — BELUM LULUS VALIDASI — TIDAK SIAP PRODUKSI.**

| Kelompok | Status | Catatan |
|---|---|---|
| RBAC dan batas Admin terhadap CBT Guru | PARSIAL; ada perbaikan source | PR #18 Tahap 1 mempersempit beberapa akses, tetapi CI HEAD belum tersedia |
| Dashboard | PARSIAL | Kartu total ujian dan tautan operasional dihapus; dashboard eksekutif lengkap belum terbukti |
| Data Guru/Siswa | PARSIAL | Pembuatan akun tersedia; CRUD administratif lengkap belum ada |
| Akademik/rombel | PARSIAL | Fondasi create/list dan penempatan siswa tersedia; lifecycle akademik belum lengkap |
| Kurikulum | PARSIAL/BLOCKED | CP/TP dasar; registry dan integrasi kurikulum penuh belum tersedia |
| Import/export administratif | BELUM ADA BUKTI UI LENGKAP | Import Guru/Siswa/Mapel belum dibuktikan |
| Identitas/branding/kop surat | BELUM ADA UI LENGKAP | Model Tenant tidak sama dengan alur pengelolaan identitas |
| Daftar ujian & penugasan Guru pengawas | KONFLIK DESAIN | Implementasi room/session PR #18 tidak sesuai batas arsitektur terbaru |
| Monitoring peserta/operasi ujian oleh Admin | DILARANG | Harus ditolak server-side |
| Laporan administratif | BELUM ADA UI LENGKAP | Export Guru ujian bukan laporan administratif Admin |
| Audit log & kesehatan sistem | PARSIAL | Service/tabel ada; UI dan coverage mutasi belum lengkap |
| Backup/restore dan pengaturan Admin | BELUM ADA UI TERBUKTI | Belum memenuhi spesifikasi |
| UI shell/responsif/aksesibilitas | BELUM SESUAI | Sidebar mobile, branding, asset lokal, dan feedback perlu perbaikan |
| Pengujian end-to-end | NOT VERIFIED | Tidak ada CI untuk HEAD PR #18; browser E2E/UAT belum ada |

## 3. Checklist fitur per menu

### ADM-01 — Dashboard Admin Madrasah
- [x] Source remediasi menghapus kartu "Total Ujian" dan tautan ke daftar ujian generik dari Dashboard Admin (PR #18 Tahap 1).
- [ ] Statistik aktual siswa aktif, Guru aktif, kelas, rombel, mapel dan tahun/semester aktif diverifikasi benar terhadap database.
- [ ] Aktivitas administratif terakhir dari audit log.
- [ ] Peringatan kelengkapan data/master dan kendala operasional.
- [ ] Kesehatan instalasi (database/storage/queue/scheduler/backup/version) dengan informasi aman tanpa secret.
- [ ] Identitas/logo madrasah dan konteks tahun ajaran/semester tampil benar.
- [ ] Tidak ada angka dummy, hard-coded, atau agregat lintas instalasi.
**Status: PARSIAL.** Bukti perubahan Dashboard: `app/Livewire/Admin/Dashboard.php` dan `resources/views/livewire/admin/dashboard.blade.php`; CI HEAD belum terverifikasi.

### ADM-02 — Identitas dan profil madrasah
- [ ] UI baca/edit profil: nama resmi/singkat, NSM, NPSN, jenjang, negeri/swasta, alamat lengkap, kontak, website, metadata Kepala Madrasah.
- [ ] Validasi dan histori perubahan identitas.
- [ ] Upload/preview/ganti/hapus logo dengan validasi MIME, ukuran, dimensi, rasio, dan penyimpanan privat per instalasi.
- [ ] Branding login, header, favicon, dan footer menggunakan data konfigurasi, bukan hard-coded.
- [ ] Tidak ada SVG berbahaya atau path traversal.
**Status: BELUM ADA UI LENGKAP YANG TERBUKTI.** Kolom Tenant yang tersedia bukan bukti halaman CRUD/asset workflow.

### ADM-03 — Kop surat dan dokumen cetak
- [ ] Editor kop surat: logo, nama instansi/madrasah, alamat/kontak, teks tambahan, garis, margin, posisi dan ukuran logo.
- [ ] Preview sebelum simpan.
- [ ] Dipakai konsisten pada dokumen administratif yang memang menjadi kewenangan Admin.
- [ ] Pengujian PDF/print font, margin, pagination, logo dan data dinamis.
**Status: BELUM ADA UI TERBUKTI.**

### ADM-04 — Manajemen Guru
- [x] Source form pembuatan akun Guru tersedia pada `UserManagement`.
- [ ] Daftar/detail/edit profil Guru lengkap.
- [ ] Aktivasi/nonaktif akun dan status kepegawaian.
- [ ] Reset password/revoke sesi melalui alur aman dan audit.
- [ ] NIP/NUPTK opsional; validasi unik bila diisi.
- [ ] Histori perubahan profil dan penugasan.
- [ ] Filter/pagination/status dan export administratif.
**Status: PARSIAL.** Pembuatan akun tidak sama dengan CRUD Guru lengkap.

### ADM-05 — Manajemen Siswa
- [x] Source form pembuatan akun/profil Siswa tersedia pada `UserManagement`.
- [x] Source penempatan Siswa ke Rombel tersedia pada `StudentRombelManagement`; laporan audit menyebut transaksi, validasi tenant/status/duplikasi dan audit event.
- [ ] Daftar/detail/edit profil lengkap.
- [ ] Aktivasi/nonaktif/arsip/lulus dan reset kredensial aman.
- [ ] Pindah rombel/keluar madrasah/mutasi dengan alasan dan histori.
- [ ] Histori penempatan per tahun ajaran.
- [ ] Filter/pagination dan export administratif tanpa password/secret.
**Status: PARSIAL.** Alur penempatan dasar tersedia, lifecycle administrasi siswa belum lengkap.

### ADM-06 — Akun, role dan migrasi legacy
- [x] Form pembuatan akun Admin dibatasi agar tidak membuat role Admin tambahan/role legacy melalui UI (ada regression test pada `AdminUiVerificationTest`).
- [x] Source remediasi memperbaiki bootstrap role ADMIN_MADRASAH untuk fresh install.
- [ ] Satu role aktif Admin Madrasah dan tidak ada portal/akun Operator aktif pada fresh install.
- [ ] Tidak ada role Kamad/Proktor permanen yang digunakan untuk authorization baru.
- [ ] Exact-one-role enforcement konsisten pada login, middleware, Livewire mount/hydration/action, API dan policy.
- [ ] Mapping akun legacy diverifikasi satu per satu dengan audit trail dan tes upgrade.
- [ ] Migration role legacy memperoleh persetujuan owner sebelum dijalankan.
**Status: BLOCKED.** Migration `2026_11_23_130001_consolidate_legacy_local_roles.php` adalah forward-only, menghapus role/mapping legacy, mencabut sesi/token, dan `down()` menolak rollback otomatis. Tidak ada bukti migration dijalankan pada disposable MySQL di PR #18 HEAD. Jangan jalankan pada produksi.

### ADM-07 — Tahun ajaran dan semester
- [ ] UI CRUD tahun ajaran dan semester.
- [ ] Validasi hanya satu periode aktif sesuai aturan instalasi.
- [ ] Aktivasi/nonaktif dan pergantian periode transaksional.
- [ ] Histori periode lama tidak hilang/berubah.
- [ ] Assignment Guru, rombel dan peserta terikat ke periode yang benar.
**Status: PARSIAL/BELUM TERVERIFIKASI.** Model tersedia dan komponen akademik membaca pilihan tahun ajaran, tetapi UI lifecycle periode lengkap belum terbukti.

### ADM-08 — Kelas, fase, rombel dan wali kelas
- [x] Source dasar create/list Rombel tersedia pada `AcademicManagement`.
- [x] Source penempatan siswa ke Rombel tersedia pada `StudentRombelManagement`.
- [ ] Kelas MI 1–6 dan fase A/B/C divalidasi konsisten.
- [ ] Edit/status/archive Rombel dan wali kelas.
- [ ] Pindah/keluar siswa dengan alasan, transaksi dan histori.
- [ ] Pencegahan satu siswa berada pada dua Rombel aktif pada tahun yang sama.
- [ ] Import/export daftar rombel.
**Status: PARSIAL.**

### ADM-09 — Mata pelajaran dan Muatan Lokal
- [x] Source create/list dasar Mata Pelajaran tersedia pada `AcademicManagement`.
- [ ] Edit, nonaktif, histori dan pengelompokan mapel MI.
- [ ] Kategori PAI/Bahasa Arab/umum/Mulok sesuai keputusan akademik yang terverifikasi.
- [ ] Data Mulok: nama lokal/daerah, kelompok dan validasi.
- [ ] Assignment mapel ke kelas/rombel/tahun.
- [ ] Import/export mapel dengan preview dan error report.
**Status: PARSIAL.** CRUD dasar tidak membuktikan pengelolaan mapel/Mulok lengkap.

### ADM-10 — Penugasan Guru
- [ ] UI daftar/tambah/edit/nonaktif assignment Guru-mapel-rombel-tahun-semester.
- [ ] Filter konflik dan histori assignment.
- [ ] Tenant, TeacherProfile, subject, rombel dan periode divalidasi server-side.
- [ ] Guru hanya mengakses scope penugasan yang aktif.
**Status: BACKEND-ONLY/PARSIAL.** Audit awal menemukan API `TeacherSubjectAssignmentController` dengan transaksi/validasi/audit; UI Admin lengkap belum ditemukan.

### ADM-11 — Kurikulum dan pembelajaran
- [x] Komponen dasar `CurriculumManagement` dan view CP/TP tersedia.
- [ ] Registry Kurikulum Merdeka dan Kurikulum Berbasis Cinta dengan versi, owner, sumber, tanggal berlaku dan histori.
- [ ] Struktur fase/kelas/mapel/elemen tervalidasi.
- [ ] Pengelolaan CP, TP, ATP lengkap dengan status dan versi.
- [ ] Panca Cinta lima nilai dengan provenance resmi.
- [ ] Pembelajaran Mendalam sebagai pendekatan pembelajaran.
- [ ] Delapan dimensi profil lulusan sebagai registry terpisah.
- [ ] Materi/topik terstruktur dan terkait kurikulum/assignment.
- [ ] Integrasi sampai QuestionVersion, immutable Exam Snapshot, hasil dan laporan.
**Status: PARSIAL/BLOCKED.** Kontrak Agent 2 pada Issue #8 masih menjadi dependency; jangan menambah schema dari UI Admin berdasarkan asumsi.

### ADM-12 — Import data Guru, Siswa dan Mata Pelajaran
- [ ] Template XLSX Guru tersedia dan terversi.
- [ ] Template XLSX Siswa tersedia dan terversi.
- [ ] Template XLSX Mata Pelajaran/Mulok tersedia dan terversi.
- [ ] Upload memvalidasi ekstensi, MIME/magic, ukuran, header dan jumlah baris.
- [ ] Mapping kolom dan preview valid/invalid/duplikat/konflik/calon update.
- [ ] Konfirmasi eksplisit sebelum commit.
- [ ] Batch transaksional, idempotensi file/row, retry aman dan tidak ada data setengah tersimpan.
- [ ] Error report per baris dan riwayat import.
- [ ] Audit aktor, waktu, hasil dan metadata file tanpa menyimpan secret.
**Status: BELUM ADA UI/LIFECYCLE LENGKAP YANG TERBUKTI.** Jangan salah menganggap import soal Guru sebagai import administratif Admin.

### ADM-13 — Export administratif
- [ ] Export Guru/Siswa per kelas/rombel/tahun/status.
- [ ] Export mapel, rombel, assignment dan laporan administratif.
- [ ] Permission dan scope dicek server-side.
- [ ] Tidak ada password, token, secret atau data lintas instalasi.
- [ ] Audit export dan uji file.
**Status: BELUM ADA ALUR ADMIN LENGKAP YANG TERBUKTI.**

### ADM-14 — Promosi, tinggal kelas, kelulusan dan alumni
- [ ] Preview calon naik kelas dan hasil validasi.
- [ ] Promosi/tinggal kelas transaksional dan dapat diaudit.
- [ ] Kelulusan kelas 6/alumni mempertahankan akun serta seluruh histori.
- [ ] Perpindahan periode tidak mengubah hasil ujian/rapor lama.
- [ ] Laporan perubahan akademik.
**Status: BELUM DITEMUKAN UI/SERVICE KHUSUS PADA AUDIT SOURCE YANG TERSEDIA.**

### ADM-15 — Daftar ujian dan penugasan Guru pengawas sementara
- [x] PR #18 Tahap 1 menghapus akses Admin ke manajemen ujian generik melalui pembatasan komponen/policy dan menghapus tautan/kartu total ujian dari Dashboard.
- [ ] Daftar Admin read-only hanya untuk ujian yang memang boleh ditugaskan sesuai keputusan terbaru (UTS/UAS/UM bila kategori kanonis telah disepakati).
- [ ] Admin dapat memilih Guru aktif dari instalasi yang sama untuk assignment sementara.
- [ ] Create/revoke assignment dengan alasan, aktor, waktu dan audit trail.
- [ ] Konflik waktu Guru ditolak secara atomik.
- [ ] Status assignment aktif/berakhir berdasarkan waktu server.
- [ ] Perubahan jadwal ujian Guru memunculkan status perlu ditinjau ulang, bukan mengubah roster diam-diam.
- [ ] Tidak ada akses Admin untuk mengubah ujian/soal/token/peserta/Attempt/jawaban/nilai.
**Status: KONFLIK DESAIN/PARSIAL.** Source lama `ProctorAssignmentManagement` mensyaratkan role permanen PROKTOR. PR #18 menggantinya dengan sistem room/session yang melampaui kewenangan Admin terbaru. Perlu desain ulang sederhana sesuai keputusan 23 November.

### ADM-16 — Ruang, sesi dan penempatan peserta
- [ ] Tidak ada lifecycle wave/session/session_number/session token sebagai admission CBT.
- [ ] Admin tidak mengelola roster/seat/penempatan peserta atau monitoring peserta.
**Status: PR #18 TIDAK SESUAI KEPUTUSAN TERBARU.** Migration `2026_11_23_140001_add_central_exam_room_assignments.php` menambahkan `exam_room_assignments.session_number`, `session_token`, jadwal sesi dan seat/room fields pada peserta. Ini bertentangan dengan keputusan ujian serentak tanpa session lifecycle dan batas kewenangan Admin. Jangan merge atau jalankan migration ini.

### ADM-17 — Monitoring peserta dan tindakan operasional
- [ ] Admin tidak dapat membuka monitoring peserta/Attempt.
- [ ] Admin tidak dapat melihat jawaban, kunci, nilai atau event operasional.
- [ ] Admin tidak dapat force submit, reset Attempt, memperpanjang waktu, takeover atau menutup ujian.
- [ ] Guru author/assignment pengawas memperoleh hak minimum sesuai policy terpisah.
**Status: DILARANG UNTUK ADMIN.** PR #18 menambah room-scoped monitoring Proktor dan endpoint token/reset-login/extra-time/attendance dalam ringkasan desain. Harus diperiksa terhadap keputusan arsitektur; assignment tidak boleh memberi hak ke soal/token/peserta/jawaban/nilai yang dilarang. Monitoring peserta oleh Admin tidak boleh diaktifkan.

### ADM-18 — Laporan administratif
- [ ] Rekap siswa per kelas/rombel/periode.
- [ ] Rekap Guru, mapel, penugasan dan data administrasi.
- [ ] Filter, pagination, preview dan export PDF/XLSX.
- [ ] Kop madrasah dan permission.
- [ ] Histori akademik/kelulusan.
**Status: BELUM ADA UI LENGKAP YANG TERBUKTI.** ReportController/export ujian milik alur Guru bukan pengganti laporan administratif Admin.

### ADM-19 — Audit log
- [x] Model/tabel/service AuditLog/AuditService tersedia pada source.
- [x] Penempatan siswa ke Rombel memiliki assertion audit pada test yang tersedia.
- [x] Assignment Proktor legacy memiliki assertion audit pada test yang tersedia.
- [ ] Semua CRUD Guru/Siswa, akademik, kurikulum, import/export, branding, pengaturan dan tindakan sensitif tercatat.
- [ ] UI pencarian/filter audit log Admin.
- [ ] Before/after, actor, timestamp, object, result dan reason tercatat tanpa password/token/secret.
- [ ] Akses audit log dibatasi dan log tidak dapat dimanipulasi oleh Admin biasa.
**Status: PARSIAL.** Audit awal menemukan beberapa mutasi `UserManagement`, `AcademicManagement`, dan `CurriculumManagement` belum memanggil AuditService.

### ADM-20 — Kesehatan sistem
- [ ] Panel Admin untuk database, storage, queue, scheduler, backup, versi aplikasi dan status lisensi.
- [ ] Status aman tanpa menampilkan APP_KEY, credential DB, private key, API secret atau stack trace.
- [ ] Riwayat insiden/peringatan operasional.
**Status: BELUM ADA PANEL ADMIN TERBUKTI.** Endpoint health dasar tidak sama dengan dashboard kesehatan sistem.

### ADM-21 — Backup dan restore
- [ ] Backup database dan media per instalasi.
- [ ] Retensi, enkripsi/akses terbatas, checksum dan verifikasi integritas.
- [ ] Daftar riwayat backup dan hasil.
- [ ] Restore hanya melalui prosedur terkontrol; uji restore di disposable DB.
- [ ] Tidak ada restore produksi tanpa persetujuan eksplisit.
**Status: BELUM ADA UI/SERVICE ADMIN LENGKAP YANG TERBUKTI.**

### ADM-22 — Profil Admin dan pengaturan
- [ ] Profil Admin, ubah password sendiri, revoke sesi dan preferensi.
- [ ] Pengaturan umum: timezone, format tanggal/waktu/angka, bahasa dan preferensi.
- [ ] Notifikasi.
- [ ] Light/Dark/System.
- [ ] Pengaturan branding, footer, kontak dan kop surat.
- [ ] Tidak pernah mengekspos secret deployment atau kredensial.
**Status: BELUM ADA UI LENGKAP YANG TERBUKTI.**

### ADM-23 — Shell UI, navigasi, responsif dan aksesibilitas
- [ ] Brand konsisten "Panel Admin Madrasah".
- [ ] Sidebar desktop scroll independen.
- [ ] Drawer mobile/tablet dengan tombol buka/tutup, scroll dan keyboard/focus support.
- [ ] Menu hanya menampilkan fitur sesuai permission; backend tetap memvalidasi setiap action.
- [ ] Loading/empty/error/success state konsisten, bukan alert browser.
- [ ] Tidak memuat Tailwind/Google Fonts dari CDN eksternal untuk instalasi yang harus tahan jaringan terbatas.
- [ ] Keyboard, focus, label, contrast dan target sentuh diuji.
**Status: BELUM SESUAI.** Layout yang diperiksa masih bertuliskan "Portal Admin Madrasah & Guru", sidebar `hidden md:flex` tanpa drawer mobile, Tailwind CDN, Google Fonts CDN dan notifikasi `alert()`.

## 4. Konflik dan blocker P0/P1 yang wajib ditangani

### P0 — PR #18 Stage 3 tidak boleh di-merge dalam bentuk sekarang
Bukti source:
- Migration `2026_11_23_140001_add_central_exam_room_assignments.php` membuat `session_number`, `session_token`, `scheduled_start/end`, `exam_room_assignments`, dan room/seat mapping.
- Kategori terpusat mencakup ASTS, ASAS, ASAT, UM, SIMULASI_AN.
- PR #18 menambah room-scoped monitoring dan endpoint operasional Proktor.
- Ini bertentangan dengan keputusan terbaru: ujian serentak tanpa session lifecycle; Admin tidak mengelola room/roster/monitoring peserta; assignment Guru pengawas sementara tidak memberikan hak operasional CBT yang dilarang.
**Tindakan:** tahan PR #18; desain ulang Tahap 3 sebelum merge. Jangan jalankan migration.

### P0 — Migration konsolidasi role masih memerlukan gate
Migration `2026_11_23_130001_consolidate_legacy_local_roles.php`:
- membuat ADMIN_MADRASAH jika belum ada;
- memetakan OPERATOR ke ADMIN_MADRASAH;
- mencatat audit untuk role legacy;
- menghapus role/mapping legacy KAMAD/PROKTOR/SUPER_ADMIN dan revoke session/token;
- bersifat forward-only; `down()` melempar exception.
**Tindakan:** wajib audit data upgrade, backup database per instalasi, manifest mapping akun, dry-run pada disposable MySQL 8, validasi histori/audit, rencana recovery, dan persetujuan eksplisit. Jangan jalankan di produksi.

### P1 — Exact role/permission dan route map
- Route `/admin/questions` dan `/admin/exams` masih terdaftar; komponen/policy diharapkan menolak Admin. Pertahankan negative test langsung pada route, Livewire mount, hydration dan setiap action.
- Periksa seluruh endpoint export, monitoring, results, analytics, search, download, dan API agar tidak menjadi jalur samping.
- Tidak cukup menyembunyikan link pada sidebar.
- Pastikan tidak ada authorization berdasarkan role legacy Operator/Kamad/Proktor.
- Uji multi-role, role dicabut saat sesi aktif, tenant context kosong/mismatch, IDOR dan direct request.

### P1 — Audit trail belum menyeluruh
Semua mutasi Admin wajib before/after dan actor/time/action/result; alasan untuk tindakan sensitif. Import/export, perubahan status, perpindahan siswa, pergantian periode, dan penugasan Guru pengawas harus masuk audit.

## 5. Status pengujian yang benar-benar terbukti

| Bukti | Hasil | Makna |
|---|---|---|
| Test source `AdminUiVerificationTest` | Ada | Mengandung assertion Admin dapat membuka route administratif dan ditolak dari /admin/exams dan /admin/questions; file test bukan bukti test lulus |
| Test source `CentralExamRoomAssignmentTest` | Ada | Menguji kategori, overlap Guru/ruang, scope monitoring; belum membuktikan sesuai keputusan arsitektur terbaru |
| Test source `ProctorAssignmentSecurityTest` | Ada | Menguji assignment legacy, audit, overlap Guru, revoke dan tenant FK; sebagian memakai asumsi role Proktor lama |
| GitHub Actions PR #18 HEAD `42a0de2...` | Tidak ditemukan run/status | **NOT VERIFIED** |
| Review PR #18 | Tidak ada review | Belum direview |
| Browser E2E Web Admin | Tidak ada bukti | **NOT RUN/NOT VERIFIED** |
| Responsive/visual/accessibility | Tidak ada bukti | **NOT RUN/NOT VERIFIED** |
| MySQL 8 fresh install untuk HEAD PR #18 | Tidak ada bukti | **NOT VERIFIED** |
| Import/export edge cases | Tidak ada bukti | **NOT RUN** |
| Backup/restore disposable | Tidak ada bukti | **NOT RUN** |
| UAT Admin madrasah | Tidak ada bukti | **NOT RUN** |

Jangan mengambil hasil CI PR/branch lain sebagai bukti bahwa HEAD PR #18 sudah lulus. Jangan menyamakan keberadaan test dengan test PASS.

## 6. Checklist acceptance akhir Web Admin

Web Admin baru dapat ditutup setelah seluruh kotak berikut tercentang dengan bukti:

- [ ] Semua ADM-01 s.d. ADM-23 yang memang masuk scope telah memenuhi keputusan arsitektur terbaru.
- [ ] Tidak ada akses Admin ke Bank Soal, token, Attempt, jawaban, monitoring peserta, grading, force submit, reset Attempt, atau close exam.
- [ ] Portal/role Admin tunggal; tidak ada authorization legacy Operator/Kamad/Proktor.
- [ ] Identitas, branding, kop surat dan pengaturan lengkap.
- [ ] Guru/Siswa CRUD, status, histori dan penugasan lengkap.
- [ ] Tahun ajaran/semester, kelas/fase/rombel, mapel/Mulok dan histori lengkap.
- [ ] Kurikulum dan provenance terintegrasi sesuai kontrak Agent 2.
- [ ] Import Guru/Siswa/Mapel: valid, invalid, duplicate, ambiguous, retry/idempotency, rollback, error report dan audit.
- [ ] Export administratif permission-scoped dan bebas secret.
- [ ] Promosi/tinggal kelas/kelulusan/alumni mempertahankan histori.
- [ ] Penugasan Guru pengawas sementara sesuai batas kewenangan, tanpa session/wave lifecycle.
- [ ] Audit log lengkap dan panel kesehatan sistem tersedia.
- [ ] Backup dan restore teruji pada database disposable.
- [ ] RBAC/IDOR/tenant/role-change/negative authorization test lulus.
- [ ] MySQL 8/InnoDB fresh install + upgrade path lulus pada disposable DB.
- [ ] Browser E2E Admin dan visual/responsive/accessibility test lulus.
- [ ] Print/PDF/Excel test lulus.
- [ ] Tidak ada P0/P1 terbuka.
- [ ] UAT Admin/Guru/Siswa sesuai skenario yang relevan selesai.
- [ ] Pengguna menyetujui penutupan Web Admin.

## 7. Arahan kerja berikutnya untuk Agent Web Admin

1. **Jangan merge PR #18.** Tahan migration room/session dan seluruh implementasi yang bertentangan dengan arsitektur 23 November.
2. Buat desain ulang scope Admin: hanya data administratif dan penugasan Guru pengawas sementara; tidak ada Admin room/roster/monitoring/operasi CBT.
3. Pertahankan perubahan Tahap 1 yang sesuai, tetapi jalankan CI pada HEAD aktual dan audit semua route/policy/action.
4. Audit ulang fitur yang belum ada berdasarkan ADM-01 s.d. ADM-23; implementasikan bertahap setelah desain dan ownership file disepakati.
5. Sebelum setiap patch, backup byte-exact file yang diubah dan catat path/SHA.
6. Migration role legacy tidak boleh dieksekusi sebelum upgrade plan, disposable test, recovery plan, dan persetujuan owner.
7. Untuk setiap tahap, update checklist ini dengan bukti file/route/test/commit dan ubah kotak menjadi [x] hanya setelah acceptance test yang relevan lulus.

## 8. Batas pekerjaan audit ini

- AUDIT: inspeksi GitHub source/dokumen/PR/route/Livewire/policy/migration/test yang disebut di atas.
- DESAIN: rekomendasi dan konflik dicatat; belum dianggap keputusan implementasi baru kecuali merujuk keputusan arsitektur 23 November yang sudah disetujui.
- IMPLEMENTASI: **tidak ada source aplikasi yang diubah oleh audit ini**. Hanya dokumen audit yang dibuat pada branch dokumentasi.
- PENGUJIAN: tidak menjalankan PHPUnit, migration, browser, MySQL, VPS, atau UAT. Status PR #18 HEAD tidak memiliki CI/status yang ditemukan.

**Kesimpulan akhir: Web Admin masih dalam tahap remediasi dan rekonstruksi. Checklist ini adalah baseline audit bersama untuk Agent Web Admin dan koordinator; bukan sertifikat kelulusan produksi.**
