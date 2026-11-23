# PROMPT AGENT PENGGANTI CBT MADRASAH — W3 FINAL
Tanggal: 24 November 2026 (WIB)
Repository: https://github.com/zane0184/cbt_november
Branch kerja: `work/solo-web-cbt-completion-20261123-execution`
Commit checkpoint terakhir yang dilaporkan agent: `df079f0ab51235b611b97b96c2cb10cc3d33d8eb`
HEAD aktual pada saat prompt ini disusun: `df079f0ab51235b611b97b96c2cb10cc3d33d8eb`
Owner: pengguna — pemegang keputusan tunggal
Mandat: **LANJUTKAN PENYEMPURNAAN APLIKASI CBT YANG SUDAH ADA. JANGAN MENGULANG PEKERJAAN YANG TELAH SELESAI.**

> ## PERINTAH PALING PENTING
> **Jangan mulai ulang proyek. Jangan membangun ulang sistem. Jangan mengulang patch. Jangan mengulang audit/test yang telah selesai tanpa pemicu teknis yang sah. Tugas utama adalah meneruskan backlog yang belum selesai sampai semua fitur web berfungsi dan lolos acceptance.**
>
> Website yang sudah berjalan: https://cbt.min15jakarta.sch.id/login
> Aplikasi sebelumnya sudah dikemas ZIP dan dipasang pada VPS. Owner menyatakan VPS, konfigurasi, dan instalasi telah melalui verifikasi ketat serta berjalan lancar. Jangan minta ulang spesifikasi VPS atau mengulang pemeriksaan infrastruktur. Fokus pada gap fitur aplikasi, kemudian satu ZIP final lengkap siap upload/extract setelah acceptance gate terpenuhi.

---

## 1. PEMBARUAN FAKTA TERPENTING DARI GITHUB — CI SUDAH PASS

### 1.1 Perbaikan CI sudah dilaksanakan
Agent sebelumnya telah memperbaiki:
- File: `.github/workflows/tenant-backend-ci.yml`
- Perbaikan: pemeriksaan InnoDB memakai prepared statement dan tetap fail-closed.
- Backup byte-exact: `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/tenant-backend-ci.yml.before-postcheck-quote-fix`
- Blob SHA backup/original sebelum edit: `c2d824eccf029f69d13a7b5dba3f5baa4adfe986`
- Workflow source blob sesudah patch: `e21c6be218a374bafc19813fda63621fe3884d6a`
- Patch commit: `1b25c3bbed07fba6def2dfe869d686a8559be1cc`
- Laporan: `docs/REPORT_W2_CI_INNODB_POSTCHECK_FIX_2026-11-24.md`
- Checkpoint: `docs/CHECKPOINT_W2_CI_INNODB_POSTCHECK_FIX_2026-11-24.md`

**Dilarang mengulang perbaikan workflow tersebut.** Jangan edit lagi file workflow dan jangan buat backup/patch duplikat kecuali CI baru menunjukkan regresi yang spesifik atau ditemukan bukti query saat ini tidak benar.

### 1.2 CI terbaru pada exact HEAD dokumentasi df079f0
GitHub Actions run **#1722 SUCCESS**:
- Run ID: `45276501877`
- Exact SHA: `df079f0ab51235b611b97b96c2cb10cc3d33d8eb`
- URL: https://github.com/zane0184/cbt_november/actions/runs/45276501877
- PHP 8.3.
- MySQL 8.0.46 pada disposable database.
- Focused Web login/tenant: `30 passed / 99 assertions`.
- Admin Livewire reauthorization: `5 passed / 5 assertions`.
- Teacher result Livewire reauthorization: `4 passed / 8 assertions`.
- Full PHPUnit: **536 passed / 2,231 assertions / 0 failed**.
- Post-check InnoDB: SUCCESS.
- Job cleanup: SUCCESS.
- Kesimpulan: workflow lengkap #1722 sudah PASS, termasuk pemeriksaan InnoDB yang sebelumnya gagal.

Jangan ulang full PHPUnit hanya karena agent berganti. Buka kembali hanya jika source baru mengubah lingkup test yang relevan, ada regresi, acceptance gate yang jelas mewajibkan, atau owner meminta. Mulai dengan focused test.

### 1.3 Status branch dan commit
- Branch kerja tetap `work/solo-web-cbt-completion-20261123-execution`.
- Commit terakhir terverifikasi saat prompt ini dibuat: `df079f0ab51235b611b97b96c2cb10cc3d33d8eb`.
- Commit tersebut hanya mengarahkan AGENT_START_HERE ke checkpoint CI terbaru.
- Pada saat prompt ini disusun, HEAD branch yang dibaca GitHub adalah `df079f0ab51235b611b97b96c2cb10cc3d33d8eb`.
- Verifikasi ulang HEAD sebelum melakukan perubahan; jangan berasumsi HEAD tidak berubah.

---

## 2. JANGAN ULANGI PEKERJAAN YANG SUDAH SELESAI

Area berikut berstatus CLOSED/VERIFIED pada cakupan yang dinyatakan:

1. **Remediasi 42 kegagalan PHPUnit baseline** telah diselesaikan dan ditutup. Jangan ulang triage atau patch fixture/helper terkait.
2. Baseline full PHPUnit lama: 518 passed / 2,177 assertions / 0 failed pada SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`.
3. W1 Livewire reauthorization Admin/Guru telah diuji pada CI #1662/#1663. Jangan ulang patch authorization yang sama.
4. CI #1714 membuktikan source sebelum perbaikan query workflow: 536 passed / 2,231 assertions / 0 failed; workflow hanya gagal pada query post-check.
5. **Perbaikan post-check InnoDB telah diimplementasikan** di commit `1b25c3b...` dan workflow penuh terbaru #1722 sudah SUCCESS. Jangan ulangi.
6. **Profil dasar Admin Madrasah telah diimplementasikan**, dengan field nama, NSM, NPSN, alamat, telepon dan email. Route `admin.profile`, component, view, sidebar dan audit log sudah ada.
7. Tiga test profil dasar telah lulus dalam PHPUnit #1714/#1722:
   - Admin dapat melihat/memperbarui profil sendiri dengan audit log.
   - Profil membutuhkan exact Admin role dan permission `tenant.manage`.
   - Tenant ditentukan hanya dari authenticated Admin; installation code tidak dapat diubah.
   Jangan membuat ulang form/route/component atau mengulang test ini tanpa pemicu.

**Aturan pembukaan ulang:** hanya jika owner meminta, source baru mengubah komponen terkait, Actions menunjukkan regresi relevan, ada bukti security/data-integrity defect, atau acceptance spesifik belum tercakup. Sebutkan pemicu, lakukan focused audit/test, lalu ubah minimal dengan backup byte-exact.

---

## 3. SUMBER WAJIB DAN CARA MELANJUTKAN

GitHub adalah satu-satunya sumber kebenaran source dan keputusan:
- Repository: `zane0184/cbt_november`
- Branch: `work/solo-web-cbt-completion-20261123-execution`
- Titik masuk: `docs/AGENT_START_HERE.md`
- Prompt W2 sebelumnya: `docs/PROMPT_AGENT_PENGGANTI_CBT_W2_FINAL_2026-11-24.md`
- Laporan patch CI: `docs/REPORT_W2_CI_INNODB_POSTCHECK_FIX_2026-11-24.md`
- Checkpoint patch CI: `docs/CHECKPOINT_W2_CI_INNODB_POSTCHECK_FIX_2026-11-24.md`
- Laporan/checkpoint profil dasar Admin W2.
- Issue #20 (ADM-01..ADM-23 dan komentar owner): https://github.com/zane0184/cbt_november/issues/20
- Issue #10 (spesifikasi Guru dan keputusan lintas portal): https://github.com/zane0184/cbt_november/issues/10
- Master spec Web Admin: `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md`
- Master spec Web Guru: `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md`
- Master spec Web Siswa: `docs/MASTER_SPESIFIKASI_FITUR_WEB_SISWA_CBT_MADRASAH_2026-11-22.md` pada branch `work/agent4-web-siswa-audit-20261122`.

Protokol awal sesi:
1. Fetch branch/HEAD terbaru.
2. Bandingkan hanya commit setelah `df079f0...`; periksa Actions, status PR/issue comments baru, dan checkpoint terkini.
3. Baca spesifikasi untuk task berikut yang belum selesai; tidak perlu scan ulang seluruh repository.
4. Cari source, test, route, policy, migration dan backup existing sebelum menyatakan item belum ada.
5. Pilih backlog paling dekat, tidak blocked, dan bernilai tinggi.
6. **Langsung implementasikan** gap nyata setelah backup; jangan berhenti setelah audit dan jangan mengirim pertanyaan yang sudah terjawab.
7. Update report/checkpoint di GitHub.

Jangan gunakan ZIP baseline, Project Files, Library, atau arsip percakapan sebagai sumber status. Jangan minta owner mengirim ulang keputusan/file GitHub.

---

## 4. KONTEKS VPS DAN TUJUAN PRODUK

Owner sudah menegaskan:
- Website CBT live: https://cbt.min15jakarta.sch.id/login.
- ZIP aplikasi sebelumnya telah dibuat dan diinstal.
- VPS, konfigurasi, dan instalasi sudah diverifikasi ketat dan dinyatakan lancar oleh owner.
- Agent tidak perlu meminta spesifikasi VPS, domain, SSL, Nginx, PHP, MySQL, panel, OS, atau mengulang audit infrastruktur generik.
- Target utama ialah melengkapi fitur-fitur yang sudah ditetapkan tetapi belum lengkap/berfungsi.
- Target akhir adalah **satu ZIP distribusi lengkap** yang dapat diunggah dan diekstrak pada VPS.

Batas keselamatan:
- Jangan melakukan pengujian destruktif, migration, edit source, upload, atau deployment pada sistem live tanpa approval eksplisit owner.
- Gunakan test/disposable environment untuk source, database, install/upgrade dan E2E.
- Jangan menganggap source GitHub pasti identik dengan source di live VPS; jangan menyentuh live untuk mencoba menyelaraskan.
- Tidak ada deployment atau penggantian paket live tanpa instruksi eksplisit.

---

## 5. KEPUTUSAN OWNER FINAL — JANGAN DITANYAKAN ULANG

### Instalasi/tenant
- Satu ZIP generik untuk semua madrasah.
- Tiap madrasah punya instalasi dan database sendiri.
- Tidak ada shared multi-tenant academic database.
- Data siswa/guru/akademik/soal/ujian/Attempt/jawaban/nilai antarinstalasi tidak saling berbagi.
- Integrasi pusat masa depan terbatas pada lisensi/entitlement/pembayaran; Web Super Admin ditunda Juni 2027.

### Portal dan role
- Role lokal aktif hanya `ADMIN_MADRASAH`, `GURU`, `SISWA`.
- Kamad/Operator bukan portal atau role aktif; tugas Operator dilebur ke Admin Madrasah.
- Proktor hanya assignment/menu operasional sementara di portal Guru, tidak menjadi role permanen.
- Penugasan Proktor tidak otomatis memberi hak melihat atau mengelola sesi, roster, Attempt, jawaban, nilai, monitoring atau operasi CBT lainnya.
- Guru mengelola bank soal, asesmen, ujian, jadwal, token, peserta sesuai assignment, dan penerbitan tanpa persetujuan Admin.
- Admin mengelola administrasi, identitas, akun, master akademik, sinkronisasi, bantuan teknis, audit, settings, backup/restore dan laporan administratif; Admin tidak memiliki otorisasi authoring/operasi ujian Guru.

### Kurikulum
- Kurikulum Merdeka + Kurikulum Berbasis Cinta (KBC/Panca Cinta) satu kesatuan, termasuk Pembelajaran Mendalam dan delapan dimensi profil lulusan.
- CP resmi induk read-only dengan provenance/version yang bisa diverifikasi.
- TP/ATP disusun Guru sesuai assignment.
- Integrasi dari master akademik, assignment, materi/topik, bank soal, QuestionVersion, asesmen, snapshot ujian, hasil dan laporan.
- Jangan mengarang CP atau data resmi.
- Jangan hardcode enum UTS/UAS/UM/TKA; bedakan fungsi, cakupan, nama kegiatan, instrumen dan mode CBT. TKA tidak disamakan dengan asesmen internal.

### CBT
- Waktu server sumber resmi.
- Token tersedia otomatis setelah Guru menerbitkan ujian; peserta hanya bisa mulai sesuai jadwal.
- Attempt/Answer/Score/Device Lease/fencing/idempotency/offline sync/recovery harus konsisten.
- Kamera bukan syarat standar.
- Force Submit, Reset Attempt, takeover/perpanjangan waktu wajib berotorisasi dan tercatat audit.
- Device Lease/fencing tidak boleh disebut siap produksi sebelum integration/multi-device/concurrency/recovery tests lulus.

### Batas scope
- Sampai Juni 2027 fokus Backend Laravel, Web Admin Madrasah, Web Guru dan Web Siswa.
- Android native dan Web Super Admin ditunda hingga Juni 2027.
- Tidak ada build Android/Gradle terminal.

---

## 6. BACKLOG AKTIF — LANJUTKAN HANYA ITEM YANG BELUM SELESAI

### P0 — Verifikasi status CI dan lanjut fitur
- CI #1722 sudah PASS. Jangan jalankan lagi tanpa perubahan relevan.
- Periksa apakah ada commit/run lebih baru, lalu lanjutkan backlog.

### P1 — Lengkapi Profil Admin (bukan membuat ulang profil dasar)
Laporan W2 menyebut gap:
- metadata profil tambahan: jenjang, status negeri/swasta, alamat/wilayah rinci, website, media sosial, metadata Kepala Madrasah dan identitas tambahan sesuai master spec;
- logo upload/replace/delete;
- favicon;
- branding login;
- private asset delivery/authorization;
- kop surat fleksibel dan preview cetak.

Sebelum coding:
- Audit master spec terkait dan schema actual, form component/view existing, disk/storage config, auth context, file validation, delivery routes, image processing dan audit service.
- Identifikasi field existing yang bisa dipakai; jangan tambah duplikasi.
- Desain additive/backward-compatible untuk field yang sungguh belum ada. Jangan migration jika kebutuhan bisa dipenuhi via konfigurasi existing.
- Untuk migration: buat backup file source, forward migration yang aman, upgrade/fresh install test pada disposable MySQL, rencana rollback/recovery, dan minta approval owner sebelum production deployment.
- Logo/branding harus mencegah arbitrary public file access, path traversal, cross-install access, MIME spoofing, SVG/script risk dan cache leakage. Gunakan upload validation, opaque storage names, authorization dan content-type yang aman.
- Buat focused feature tests positif/negatif dan preview E2E yang relevan.
- Jangan menyatakan selesai kalau hanya upload form/kolom tersedia; verifikasi output tampilan di layout/login/report sebenarnya.

### P2 — Issue #20 Web Admin ADM-01..ADM-23
Lanjutkan seluruh item sesuai source/acceptance:
- W1 exact roles, reauthorization, session/revoke, installation context, IDOR/direct route/API/export/download, negative permission Admin. Patch W1 reauthorization yang sudah lulus jangan diulang.
- W2 shell/dashboard: menu, responsive, loading/empty/error/success, keyboard/accessibility dan data valid; audit existing, jangan membuat ulang shell/dashboard.
- W3 profile/branding/settings; profil dasar sudah ada, kerjakan gap saja.
- W4 administrasi Guru/Siswa, akun, import/export.
- W5 master akademik, tahun ajaran, semester, kelas, rombel, mapel, assignment, histori, promosi, tinggal kelas, kelulusan dan alumni.
- W6 import/export administratif; DOCX utama untuk bank soal, XLSX question import dilarang.
- W7 assignment pengawas/proktor sementara pada Guru dengan batas hak.
- W8 audit logs, health, backup/restore.
- W9 integrasi portal dan acceptance end-to-end.

Jangan menganggap menu atau CRUD dasar berarti acceptance item selesai. Cocokkan acceptance criteria per ADM dari Issue #20 dan master spec. Tandai DONE hanya dengan bukti.

### P3 — Web Guru E2E
Integrasikan:
`assignment → CP/TP/ATP → materi/topik → question bank & versioning/media → DOCX import → assessment/exam builder → snapshot immutable → peserta sesuai assignment → publish/token/jadwal → exam execution → manual Essay scoring → results/analytics/reports`.

Verifikasi:
- route/policy exact role dan assignment;
- tidak ada hak tambahan via assignment proktor;
- token, jadwal dan waktu server;
- snapshot ujian tidak berubah bila bank soal diedit setelah publish;
- data hasil/analytics benar, bukan mock;
- AuditLog untuk tindakan sensitif;
- negative access Guru di luar assignment;
- Arabic RTL/harakat/rumus bila ada dalam spec.

Jangan mengulang patch reauthorization ResultList/ResultDetail/AnalyticsDashboard/ItemAnalysis yang sudah lulus. Jangan menyimpulkan analytics statistik lulus dari test mock reauthorization.

### P4 — Web Siswa E2E
Integrasikan browser yang didukung dengan backend nyata:
- login/session exact SISWA;
- dashboard dan eligibility;
- token/jadwal/admission dengan server clock;
- immutable snapshot dan runner;
- tipe soal/media dengan MIME/Range/seek yang aman;
- autosave, revision, receipt, `client_request_id`, idempotent retry;
- IndexedDB, reload/crash, offline/reconnect, multi-tab, stale conflict;
- expiry, submit barrier, recovery dan hasil;
- server-side redaction;
- Browser E2E nyata ke Laravel.

Android wajib native ketika scope dibuka kembali. Android Browser tidak boleh memulai/melanjutkan Attempt. Untuk saat ini Android ditunda; jangan mulai implementasi Android.

### P5 — Integritas, keamanan dan operasional
- RBAC/assignment/IDOR seluruh route, action, export, media dan direct API.
- Install identity/context per instalasi.
- DB transaction, lock, race/concurrency, idempotency, retry.
- Audit log untuk perubahan penting.
- MySQL 8/InnoDB migration/schema integrity.
- Backup/restore diverifikasi, bukan hanya file backup berhasil dibuat.
- Fresh install dan upgrade pada disposable DB.
- Offline/sync/recovery/expiry.
- Browser E2E antar-role.
- Accessibility/responsive.
- UAT fungsional pada lingkungan yang diizinkan.

Blocker:
- ADM-20 ujian pusat tetap BLOCKED sampai owner menetapkan canonical eligibility marker dan aktor yang berhak menetapkannya. Jangan menyimpulkan dari `assessment_origin=EXTERNAL`.
- SHORT_ANSWER tetap fail-closed sampai accepted answer privat, snapshot immutable dan scoring E2E lulus.
- Device Lease/fencing belum production-ready sampai integration, multi-device, concurrency dan recovery lulus.
- Android dan Web Super Admin DEFERRED sampai Juni 2027.

---

## 7. BACKUP DAN IMPLEMENTASI

Untuk setiap file existing yang akan diubah:
1. Fetch source branch aktif; catat path/blob SHA.
2. Periksa caller, route, policy, schema, migration, tests, API contracts dan kompatibilitas.
3. Buat backup byte-exact di GitHub sebelum perubahan.
4. Fetch backup dan pastikan blob SHA/content cocok.
5. Patch sekecil mungkin; jangan rewrite file besar.
6. Catat file, backup path/blob SHA, commit SHA.
7. Jangan hapus source/migration/data historis tanpa rencana upgrade yang teruji dan approval.
8. Tidak ada perubahan langsung pada website/VPS live atau database produksi.

---

## 8. PENGUJIAN

- CI #1722 sudah PASS: 536 tests / 2,231 assertions / 0 failed. Jangan mengulang hanya karena agent baru.
- Focused test untuk setiap perubahan.
- Full PHPUnit hanya bila dampak lintas modul luas, acceptance gate mensyaratkan, atau ada bukti regresi.
- PHP 8.3 + disposable MySQL 8/InnoDB.
- Browser E2E harus benar-benar berkomunikasi dengan backend Laravel; mock tidak cukup.
- Uji positive/negative roles, error/empty/loading/success, direct access/IDOR, concurrency/idempotency, backup/restore dan recovery sesuai scope.
- Jangan test database live atau melakukan migration produksi.
- Catat exact SHA, Actions URL, environment, results/assertions/skips dan keterbatasan.
- Jangan melonggarkan security untuk membuat CI hijau.

---

## 9. ZIP DISTRIBUSI FINAL

ZIP dibuat **setelah seluruh fitur wajib dan acceptance gate terpenuhi**.

Wajib satu paket generik lengkap untuk setiap instalasi madrasah mandiri:
- Backend Laravel dan seluruh source web yang dibutuhkan;
- dependencies/lock dan assets yang diperlukan saat deploy;
- migration/installer/fresh-install/upgrade scripts;
- konfigurasi contoh tanpa secret;
- asset lokal, panduan permission/storage;
- README Bahasa Indonesia untuk upload/extract via aaPanel, konfigurasi dari backup pemilik, install/upgrade, cache, storage, queue/scheduler bila dipakai, health check, backup/restore dan troubleshooting;
- checksum SHA-256 dan manifest;
- tanpa .git, .env produksi, secret, log/cache/temp, backup internal atau file pribadi.

Verifikasi:
- source commit/manifest;
- extract ke folder uji bersih;
- struktur tidak nested ganda;
- dependency install reproducible;
- fresh install dan upgrade disposable MySQL 8/InnoDB;
- route/assets/storage/config/permissions/health;
- fungsi utama Web Admin/Guru/Siswa dan security;
- README memungkinkan upload/extract tanpa pemindahan manual.

Jangan merge PR/deploy/replace ZIP live tanpa persetujuan eksplisit. Status distribusi dan produksi harus dibedakan.

---

## 10. LAPORAN DAN CHECKPOINT GITHUB

Setiap tahap:
- **AUDIT**: source/route/policy/schema/API/test yang dicek; gap dan bukti.
- **DESAIN**: solusi, boundaries, schema/API, kompatibilitas.
- **IMPLEMENTASI**: file berubah, backup path/blob SHA, commit SHA.
- **PENGUJIAN**: exact SHA, workflow/run URL, PHP/MySQL, test/asserstions/pass/fail/skip, batas.
- backlog DONE / IN PROGRESS / BLOCKED / NOT STARTED / DEFERRED.
- risiko, blocker, next actions.
- handover agent berikut dengan daftar yang jangan diulang.

Simpan di GitHub dan update `AGENT_START_HERE.md` agar selalu menunjuk prompt/checkpoint paling baru. Tidak cukup hanya laporan chat.

---

## 11. PERINTAH EKSEKUSI PERTAMA

1. Verifikasi branch dan HEAD saat ini.
2. Pastikan CI #1722 masih tercatat SUCCESS pada SHA `df079f0...`.
3. Jangan edit ulang `.github/workflows/tenant-backend-ci.yml`; jangan ulangi patch query.
4. Jangan ulangi profil dasar Admin, triage 42 kegagalan PHPUnit, atau patch W1 reauthorization.
5. Baca Issue #20 dan master spec untuk item aktif berikutnya.
6. Langsung kerjakan gap profil Admin atau item ADM yang belum selesai dan tidak blocked. Prioritas teknis: audit gap → backup → patch minimal → focused test → report/checkpoint.
7. Berikutnya lanjutkan integrasi Web Guru dan Web Siswa end-to-end, hardening, acceptance.
8. Jangan berhenti setelah membuat matriks audit. Teruskan implementasi backlog selama tidak memerlukan keputusan owner yang belum ada.
9. Buat ZIP final hanya setelah seluruh acceptance gate benar-benar lulus.

**JANGAN MENGULANG. JANGAN MEMBANGUN ULANG. JANGAN MEMINTA SPESIFIKASI VPS ULANG. LANJUTKAN DARI POSISI CHECKPOINT TERAKHIR HINGGA FITUR CBT LENGKAP, TERUJI, DAN SATU ZIP SIAP DISTRIBUSI.**

Status proyek pada dokumen ini: **IN PROGRESS — CI GREEN, WEB FEATURES NOT YET FULLY ACCEPTED, ZIP NOT READY FOR DISTRIBUTION**.
