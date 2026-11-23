# PROMPT AGENT PENGGANTI CBT MADRASAH — W2 FINAL
Tanggal: 24 November 2026 (WIB)
Repository: https://github.com/zane0184/cbt_november
Branch kerja: `work/solo-web-cbt-completion-20261123-execution`
HEAD terverifikasi saat dokumen disusun: `e894d3f4727e3bfcfdb45a29ce1da5c3abd6b598`
Owner: pengguna — pemegang keputusan tunggal
Mandat: lanjutkan penyempurnaan aplikasi CBT yang SUDAH ADA dan SUDAH TERPASANG di VPS. Bukan membangun ulang.

> # PERINTAH UTAMA: LANJUTKAN, JANGAN MENGULANG.
> **Tugasmu adalah menyelesaikan backlog dan fitur yang belum lengkap. BUKAN mengulang pekerjaan yang sudah selesai, bukan membangun ulang proyek, bukan mengulang audit dari nol, dan bukan meminta owner menjelaskan ulang keputusan yang sudah tercatat.**
>
> Website yang telah berjalan: https://cbt.min15jakarta.sch.id/login
> Aplikasi sebelumnya sudah pernah dikemas menjadi ZIP dan dipasang di VPS. Pemilik menyatakan VPS, konfigurasi, dan proses instalasi telah melalui verifikasi ketat serta berjalan lancar. Fokus sekarang adalah memperbaiki dan melengkapi fitur web yang sudah ditetapkan tetapi ternyata belum lengkap/berfungsi pada aplikasi yang terpasang. Target akhirnya satu ZIP distribusi lengkap yang siap diunggah dan diekstrak di VPS setelah semua fitur dan acceptance gate lulus.

Dokumen ini adalah instruksi operasional terbaru dan mengesampingkan arahan lama yang meminta agent memulai audit infrastruktur/VPS atau membangun ulang aplikasi. Riwayat dokumen lama tetap dipertahankan.

---

## 1. MISI DAN DEFINISI SELESAI

### 1.1 Misi
1. Teruskan source yang sudah ada pada branch kerja GitHub.
2. Cocokkan setiap fitur terhadap master spesifikasi, Issue, keputusan owner, source, route, policy, API, database, dan test yang sudah ada.
3. Identifikasi gap nyata saja. Jangan mengulang patch atau membuat komponen paralel.
4. Selesaikan seluruh fitur Web Admin Madrasah, Web Guru, Web Siswa, dan backend yang termasuk scope sekarang.
5. Uji alur fungsional end-to-end, bukan hanya tampilan, unit test, atau status build.
6. Setelah seluruh acceptance gate terpenuhi, buat satu ZIP distribusi lengkap yang dapat diunggah dan diekstrak pada VPS.
7. Buktikan kelengkapan ZIP: struktur, source, dependency, konfigurasi contoh, permission, instruksi, checksum, proses ekstraksi bersih, dan jalur instal/upgrade yang aman.

### 1.2 Definisi selesai
Proyek belum selesai hanya karena:
- halaman bisa dibuka;
- tombol terlihat;
- PHPUnit hijau;
- satu modul berhasil dibuat;
- source dapat di-zip;
- aplikasi berhasil dideploy.

Selesai untuk tahap distribusi hanya bila:
- semua fitur wajib dalam spesifikasi sudah berfungsi;
- alur lintas modul terintegrasi dengan backend nyata;
- seluruh role dan permission diuji positif maupun negatif;
- isolasi data per instalasi, audit log, transaksi, idempotensi, dan histori akademik terjaga;
- skenario error, loading, empty, recovery, dan akses langsung diuji;
- fresh install/upgrade diuji pada database disposable MySQL 8/InnoDB;
- integrasi browser-to-Laravel, sinkronisasi, backup/restore, dan security gate relevan lulus;
- satu ZIP lengkap telah diekstrak pada lingkungan bersih dan struktur/isinya diverifikasi;
- README menjelaskan cara upload/extract dan konfigurasi runtime tanpa mengharuskan pemindahan file manual.

Status akhir harus dibedakan:
- **SIAP DIDISTRIBUSIKAN KE VPS**: seluruh acceptance distribusi lulus.
- **SIAP PRODUKSI**: baru boleh dinyatakan setelah deployment terotorisasi dan UAT operasional, keamanan, stabilitas, pemulihan, dan madrasah lulus.
Jangan menyamakan kedua status tersebut.

---

## 2. KONTEKS VPS DAN WEBSITE YANG SUDAH BERJALAN — JANGAN DIULANG

Owner menegaskan:
- Website CBT sudah berjalan di https://cbt.min15jakarta.sch.id/login.
- Paket ZIP aplikasi sebelumnya sudah pernah dibuat dan diinstal di VPS.
- VPS, konfigurasi server, dan instalasi sebelumnya telah melalui verifikasi ketat; owner menyatakan semuanya lancar.
- Tujuan pekerjaan bukan menemukan VPS baru, merancang ulang server, atau mengulang pemeriksaan spesifikasi server.
- Agent **dilarang meminta owner mengulang spesifikasi VPS, panel, OS, Nginx, PHP, MySQL, domain, SSL, atau konfigurasi yang sudah diverifikasi**.
- Jangan membuat backlog infrastruktur generik yang tidak terkait bukti masalah aplikasi.
- Jangan menjadikan pemeriksaan VPS sebagai prasyarat untuk menyelesaikan fitur pada source.

Batas penting:
- Website yang live adalah sistem yang sudah berjalan dan harus diperlakukan sebagai lingkungan produksi yang sensitif.
- Jangan melakukan login uji, pemindaian agresif, perubahan data, upload, migration, deployment, atau pengujian destruktif terhadap website live tanpa instruksi dan otorisasi eksplisit owner.
- Jangan menyimpulkan bahwa karena website live, semua source di GitHub otomatis sama dengan source yang terpasang. Perbedaan versi harus ditangani melalui identifikasi versi/build secara aman jika nanti diperlukan, bukan dengan mengutak-atik live system.
- Untuk pekerjaan fitur, gunakan source GitHub dan lingkungan test/disposable. VPS live bukan tempat eksperimen.
- Jika kelak perlu tahap upload/deploy, minta hanya persetujuan tindakan spesifik dan data akses yang memang belum tersedia. Jangan bertanya ulang spesifikasi VPS yang telah diverifikasi.

---

## 3. SUMBER KEBENARAN DAN KONTINUITAS

### 3.1 Sumber kebenaran
Untuk status source, keputusan, dan pekerjaan:
- Repository GitHub `zane0184/cbt_november` adalah sumber kebenaran utama.
- Branch kerja: `work/solo-web-cbt-completion-20261123-execution`.
- Baca prompt ini, `docs/AGENT_START_HERE.md`, handover/checkpoint/laporan terbaru, Issue #20 dan komentar owner, Issue #10, serta master spesifikasi terkait.
- Source aktual, commit SHA, diff, test pada exact SHA, dan workflow Actions adalah bukti utama.
- Bila laporan lama berbeda dengan source terbaru, utamakan source terbaru dan bukti exact SHA; catat perbedaannya.
- Jangan mengandalkan ringkasan percakapan untuk mengklaim file atau fitur sudah/ belum ada.

### 3.2 Larangan sumber
Sesuai arahan owner untuk workflow saat ini, jangan mencari atau menggunakan ZIP baseline lama, file lokal/Project Files/Library, atau arsip percakapan sebagai sumber status source. Jangan meminta owner mengirim ulang file atau keputusan yang sudah tercatat di GitHub.

### 3.3 Cara melanjutkan
Pada awal sesi:
1. Verifikasi branch dan HEAD terbaru. HEAD pada saat prompt ini dibuat adalah `e894d3f4727e3bfcfdb45a29ce1da5c3abd6b598`; angka ini adalah titik acuan, bukan asumsi HEAD selamanya.
2. Periksa hanya commit/perubahan setelah titik acuan tersebut, Actions terbaru, issue comments baru, status PR yang relevan, checkpoint, dan laporan terbaru.
3. Temukan backlog berikutnya yang belum selesai dan tidak blocked.
4. Cari implementasi/test/backup yang sudah ada sebelum menyimpulkan fitur belum dikerjakan.
5. Langsung kerjakan backlog tersebut. Jangan berhenti setelah membuat audit ulang.
6. Jangan mengulang patch yang sudah teruji atau full suite tanpa pemicu sah.
7. Simpan laporan/checkpoint/prompt update di GitHub.

---

## 4. STATUS SOURCE DAN CI TERBARU YANG SUDAH DIVERIFIKASI

### 4.1 Branch dan HEAD
- Branch: `work/solo-web-cbt-completion-20261123-execution`
- HEAD pada saat prompt ini disusun: `e894d3f4727e3bfcfdb45a29ce1da5c3abd6b598`
- Commit terakhir yang terverifikasi: [e894d3f](https://github.com/zane0184/cbt_november/commit/e894d3f4727e3bfcfdb45a29ce1da5c3abd6b598) — koreksi FQCN AuditLog di test profil Madrasah.
- Selalu fetch HEAD terkini sebelum perubahan; jangan menganggap angka di atas tetap HEAD jika branch sudah maju.

### 4.2 CI #1714 — full PHPUnit lulus, workflow keseluruhan gagal pada post-check InnoDB
Actions run: https://github.com/zane0184/cbt_november/actions/runs/45273428395
Exact SHA: `e894d3f4727e3bfcfdb45a29ce1da5c3abd6b598`
Lingkungan: PHP 8.3 dan disposable MySQL 8.0.46.

Hasil langkah:
- MySQL identity preflight: SUCCESS.
- Composer lock/platform validation dan install: SUCCESS.
- Laravel package discovery: SUCCESS.
- Disposable installer E2E database setup: SUCCESS.
- Focused Web login/tenant smoke: **30 passed / 99 assertions**.
- Focused Admin Livewire reauthorization: **5 passed / 5 assertions**.
- Focused Teacher result Livewire reauthorization: **4 passed / 8 assertions**.
- Complete PHPUnit suite: **536 passed / 2,231 assertions / 0 failed**.
- Test `AdminUiVerificationTest` juga meluluskan tiga test profil Madrasah: update profil + audit log, exact role/permission, serta tenant scope dan immutability installation code.
- Post-check tabel InnoDB: **FAIL karena bug query pemeriksaan workflow**, bukan karena PHPUnit gagal. Log menunjukkan PDO error `Unknown column 'tenants' in 'where clause'`; SQL pemeriksaan `information_schema.TABLES` salah mengutip/memperlakukan nilai nama tabel. Job akhirnya berstatus failure karena post-check tersebut.
- Cleanup container: SUCCESS.

Interpretasi wajib:
- Jangan melaporkan run #1714 sebagai full PHPUnit gagal. Full suite terbukti PASS.
- Jangan melaporkan workflow keseluruhan PASS. Workflow tetap FAILURE karena post-check InnoDB gagal.
- Jangan mengubah aplikasi untuk memperbaiki kegagalan query pemeriksaan workflow.
- Perbaiki hanya query/step post-check CI dengan backup byte-exact workflow sebelum edit. Pastikan query benar-benar memeriksa `TABLE_SCHEMA = DATABASE()`, `TABLE_NAME = 'tenants'`, dan `ENGINE = 'InnoDB'` secara benar. Jangan melemahkan pemeriksaan fail-closed.
- Setelah workflow fix, jalankan Actions baru pada satu HEAD stabil untuk membuktikan workflow utuh; tidak perlu mengulang triage PHPUnit. Run tersebut boleh menjalankan full suite karena workflow CI memang mengharuskan verifikasi source pada SHA baru setelah perubahan workflow, tetapi jangan melakukan remediasi ulang test yang sudah lulus.
- Jika full suite kembali berjalan dan lulus, catat angka terbaru. Jika ada kegagalan baru, telusuri kegagalan yang benar-benar muncul saja.

### 4.3 Baseline yang lebih lama — jangan diulang
Full PHPUnit baseline sebelumnya sudah pernah hijau pada exact SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`: 518 passed / 2,177 assertions / 0 failed. Laporan remediasi 42 failure baseline telah ditutup.
Jangan mengulang triage 42 failure atau patch fixture/helper yang sudah diselesaikan.

W1 reauthorization Admin/Guru sebelumnya telah diverifikasi pada run #1662/#1663. Jangan mengulang patch authorization yang sama. Run #1714 juga kembali meluluskan focused Admin 5/5 dan Teacher 4/4; itu hanya regression check, bukan acceptance fungsional semua portal.

---

## 5. PEKERJAAN TERAKHIR DI SOURCE — JANGAN DIULANG, LANJUTKAN GAP

### 5.1 Profil dasar Admin Madrasah
Sudah ditambahkan:
- `backend-laravel/app/Livewire/Admin/MadrasahProfile.php`
- `backend-laravel/resources/views/livewire/admin/madrasah-profile.blade.php`
- route `GET /admin/profile` bernama `admin.profile` di `backend-laravel/routes/web.php`
- link sidebar di `backend-laravel/resources/views/layouts/app.blade.php`
- test di `backend-laravel/tests/Feature/AdminUiVerificationTest.php`

Kontrak implementasi:
- exact role tunggal `ADMIN_MADRASAH` + permission `tenant.manage`;
- tenant berasal hanya dari `auth()->user()->tenant_id`;
- tidak mengizinkan System Tenant;
- update tenant scoped dan row lock;
- `code` dan `status` instalasi tidak bisa diedit dari form;
- audit old/new values melalui AuditService dalam transaksi;
- tidak ada migration.

Field dasar yang sudah dicakup: nama, NSM, NPSN, alamat, telepon, email.
Tiga test fitur profil lulus pada full PHPUnit #1714.

Backup byte-exact yang sudah ada:
- `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/web.php.before`
- `.../app.blade.php.before`
- `.../AdminUiVerificationTest.php.before-profile`
- `.../MadrasahProfile.php.before-namespace-fix`
- `.../tenant-backend-ci.yml.before-mysql-engine-probe-fix`
- `.../tenant-backend-ci.yml.before-probe-after-migrations`
- `.../AdminUiVerificationTest.php.before-audit-fqcn-fix`
Detail blob SHA tercatat di `docs/REPORT_W2_ADMIN_MADRASAH_PROFILE_2026-11-24.md`.

Gap profil yang belum selesai:
- metadata tambahan (jenjang, status negeri/swasta, wilayah rinci, website, metadata Kepala Madrasah, dan data identitas tambahan sesuai master spec);
- logo upload/replace/delete, favicon, branding login, dan penyajian asset privat yang aman;
- kop surat fleksibel dan preview.
Jangan menambahkan field/migration sebelum audit schema, master spec, kontrak kompatibilitas dan rencana upgrade. Jangan menganggap semua profil selesai hanya karena form dasar sudah lulus test.

### 5.2 Perbaikan workflow CI yang tersisa
- File: `.github/workflows/tenant-backend-ci.yml`
- Post-check InnoDB masih gagal karena query salah.
- Perbaiki hanya step ini, backup byte-exact terlebih dahulu, lalu validasi log query dan workflow.
- Jangan mengubah source aplikasi untuk menutupi masalah workflow.

---

## 6. KEPUTUSAN OWNER — FINAL, JANGAN DITANYAKAN ULANG

### 6.1 Bentuk produk dan instalasi
- Satu ZIP generik final digunakan untuk semua madrasah.
- Setiap madrasah memiliki instalasi aplikasi dan database sendiri. Tidak ada shared academic multi-tenant database antar-madrasah.
- Data siswa, guru, akademik, soal, ujian, Attempt, jawaban, nilai, laporan dan transaksi CBT tidak dibagi antar-instalasi.
- Integrasi pusat masa depan hanya untuk lisensi, entitlement/paket, masa berlaku dan pembayaran. Web Super Admin pusat ditunda sampai Juni 2027.
- Jangan membuat arsitektur multi-tenant akademik lintas madrasah.
- Jika ada struktur tenant legacy pada source, jangan menghapus schema/migration/data historis secara gegabah. Pertahankan kompatibilitas instalasi dan rancang transisi secara eksplisit.

### 6.2 Portal dan role
Role lokal resmi hanya:
- `ADMIN_MADRASAH`
- `GURU`
- `SISWA`

Keputusan:
- Portal/role Kamad dan Operator tidak aktif. Semua tugas Operator dilebur ke Admin Madrasah.
- Kepala Madrasah hanya metadata/jabatan/penandatangan jika dibutuhkan.
- Proktor bukan role/portal permanen. Proktor adalah penugasan/menu operasional sementara di portal Guru.
- Penugasan Proktor tidak otomatis memberikan hak monitoring, mengelola room/session/roster/Attempt/jawaban/nilai, force submit, reset Attempt, atau operasi ujian lain.
- Guru mengelola bank soal, asesmen, ujian, jadwal, token, peserta sesuai assignment, dan menerbitkan ujian tanpa persetujuan Admin.
- Admin mengelola administrasi madrasah, akun, master akademik, sinkronisasi, dukungan teknis, audit, pengaturan, backup/restore, dan laporan administratif.
- Admin tidak boleh mengelola authoring soal/ujian, token, peserta ujian, Attempt, jawaban, monitoring, penilaian/nilai, atau operasi ujian Guru.

### 6.3 Kurikulum dan akademik
- Kurikulum Merdeka dan Kurikulum Berbasis Cinta (KBC/Panca Cinta) menjadi satu sistem terpadu, bersama Pembelajaran Mendalam dan delapan dimensi profil lulusan.
- Integrasi harus konsisten dari master akademik, assignment, pembelajaran, CP/TP/ATP, bank soal, asesmen, ujian, hasil hingga laporan.
- CP resmi induk read-only, provenance dan versinya harus terverifikasi. Jangan mengarang data resmi.
- TP/ATP dikembangkan Guru sesuai assignment.
- Pertahankan versi kurikulum dan seluruh histori akademik.
- Jangan hardcode UTS/UAS/UM/TKA sebagai enum tertutup. Pisahkan fungsi asesmen, cakupan, nama kegiatan, instrumen, dan mode CBT.
- DOCX adalah format utama import soal. XLSX question import tetap nonaktif.
- SHORT_ANSWER tetap fail-closed sampai accepted answer privat, immutable snapshot dan scoring teruji. ESSAY dinilai manual.

### 6.4 Prinsip CBT dan keamanan
- Waktu server adalah acuan resmi.
- Token otomatis tersedia setelah Guru menerbitkan ujian; ujian hanya dimulai sesuai jadwal.
- Attempt, Answer, Score, Device Lease/fencing, idempotency, offline sync, conflict handling, dan recovery harus konsisten.
- Kamera bukan syarat CBT standar.
- Reset Attempt, Force Submit, takeover perangkat, dan perpanjangan waktu wajib berotorisasi dan tercatat audit.
- Device Lease/fencing belum boleh disebut siap produksi sebelum integration, multi-device, concurrency, dan recovery test lulus.
- Isolasi data per instalasi, exact role, assignment, policy, audit log, transaksi, idempotensi, dan histori akademik wajib terjaga.

### 6.5 Scope waktu
- Sampai Juni 2027: Backend Laravel, Web Admin Madrasah, Web Guru, Web Siswa.
- Android native dan Web Super Admin: DEFERRED sampai Juni 2027. Jangan dialihkan menjadi backlog sekarang.
- Android wajib native bila scope dibuka kembali; build Android kelak hanya melalui Android Studio, bukan Gradle terminal.

---

## 7. SPESIFIKASI DAN BACKLOG YANG HARUS DITERUSKAN

Baca sumber ini dari GitHub sebelum mengerjakan bagian terkait:
1. `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md`
2. `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md`
3. `docs/MASTER_SPESIFIKASI_FITUR_WEB_SISWA_CBT_MADRASAH_2026-11-22.md` pada branch referensi `work/agent4-web-siswa-audit-20261122`
4. Issue #20 — ADM-01 sampai ADM-23 dan komentar owner: https://github.com/zane0184/cbt_november/issues/20
5. Issue #10 — spesifikasi Guru dan keputusan lintas portal: https://github.com/zane0184/cbt_november/issues/10
6. `docs/HANDOVER_AGENT_BARU_LANJUTKAN_BUKAN_MENGULANG_2026-11-24.md`
7. `docs/CHECKPOINT_W2_ADMIN_MADRASAH_PROFILE_2026-11-24.md`
8. `docs/REPORT_W2_ADMIN_MADRASAH_PROFILE_2026-11-24.md`
9. `docs/PROMPT_AGENT_PENGGANTI_CBT_W1_FINAL_2026-11-24.md` (acuan riwayat W1; prompt ini menggantikannya untuk arahan terbaru).

Urutan kerja terarah:
1. Perbaiki query post-check InnoDB workflow CI secara minimal dan buktikan.
2. Lanjutkan W2 profil Admin dari gap yang tercatat: audit master spec dan schema aktual, rancang field tambahan secara backward-compatible, lanjutkan logo/private asset dan branding, lalu kop surat/preview sesuai acceptance. Jangan menambah migration tanpa desain/backup/upgrade test.
3. Selesaikan sisa W1/W2–W9 Admin sesuai Issue #20: shell/dashboard, profil/branding/settings, master Guru/Siswa, akademik/kurikulum, import/export administratif, assignment pengawas sementara, audit/health/backup/restore, dan integrasi acceptance.
4. Web Guru: tutup alur end-to-end assignment → CP/TP/ATP → materi/topik → bank soal/versioning/media → asesmen/ujian → jadwal/token/publish → pelaksanaan → penilaian Essay/hasil/laporan/analytics. Jangan mengulang patch reauthorization yang sudah hijau.
5. Web Siswa: tutup login/session exact role, dashboard/eligibility, token/jadwal server, immutable snapshot, runner, tipe soal/media, autosave/revision/receipt/client_request_id/idempotent retry, IndexedDB, reload/crash, offline/reconnect, multi-tab/stale conflict, expiry/submit barrier, hasil, server-side redaction, dan browser-to-Laravel E2E.
6. Integrasi backend lintas portal: jangan membuat API/DTO/enum paralel. Gunakan satu kontrak sumber.
7. Hardening: exact role, assignment, IDOR/direct route/API/export/download, transaction/locking/concurrency, idempotency, audit log, MySQL 8/InnoDB, backup/restore, fresh install/upgrade, browser E2E, dan UAT.
8. Kemas ZIP hanya setelah acceptance gate selesai.

Blocker yang sudah tercatat:
- ADM-20 / penugasan Proktor ujian pusat BLOCKED sampai owner menentukan canonical central exam eligibility marker dan aktor penetapnya. Jangan menganggap `assessment_origin=EXTERNAL` sebagai izin ujian pusat. Kerjakan backlog lain.
- Android dan Web Super Admin DEFERRED sampai Juni 2027.
- SHORT_ANSWER fail-closed sampai accepted-answer privacy/snapshot/scoring lulus.
- Device Lease/fencing belum siap produksi sampai integration/multi-device/concurrency/recovery lulus.

Status PR #13/#18/#19 dan status issue dapat berubah. Fetch status terbaru sebelum tindakan. Tidak ada merge PR tanpa approval owner.

---

## 8. PROTOKOL IMPLEMENTASI — BACKUP SEBELUM SETIAP EDIT

Untuk setiap file existing:
1. Fetch dari branch kerja aktif dan catat path, blob SHA, isi/ukuran relevan.
2. Pahami caller, route, policy, schema, migration, test, API contract, dan kompatibilitasnya.
3. Buat backup byte-exact di GitHub sebelum mengubah source.
4. Fetch kembali backup dan bandingkan seluruh konten; jangan lanjut jika tidak identik.
5. Ubah sekecil mungkin. Jangan menulis ulang file besar jika patch lokal cukup.
6. Catat diff, backup path, blob SHA, commit SHA.
7. Jangan menghapus file/migration/role/history yang digunakan instalasi lama tanpa rencana upgrade teruji dan approval owner.
8. Tidak ada perubahan langsung di website/VPS live.
9. Jangan membuat migration produksi atau menjalankan test terhadap database produksi.
10. Jika fitur baru membutuhkan migration, rancang forward-only dan backward-compatible, uji pada disposable MySQL 8, sediakan rollback/recovery plan dan minta approval sebelum deployment.

---

## 9. PROTOKOL PENGUJIAN — BUKTI, BUKAN KLAIM

- Fokus test terkait file/fitur yang berubah terlebih dahulu.
- Jangan full PHPUnit hanya karena agent berganti.
- Full suite dilakukan bila perubahan berdampak luas, acceptance gate mensyaratkan, atau ada regresi lintas modul yang terbukti.
- PHP 8.3 + disposable MySQL 8/InnoDB untuk pengujian backend yang relevan.
- SQLite bukan pengganti MySQL untuk constraint/FK/collation/timestamp/locking/transaction.
- Test positive dan negative authorization untuk tiap role.
- Browser E2E harus benar-benar berbicara dengan Laravel/backend; mock-only bukan bukti integrasi.
- Uji alur pengguna, validasi, error, loading, empty, success, retry, concurrency, idempotency, rollback, recovery, offline/sync sesuai area.
- Jangan memakai data produksi atau mengubah data live untuk test.
- Catat exact SHA, Actions run URL, environment, pass/fail/skip/assertions, log dan keterbatasan.
- CI CANCELLED bukan PASS dan bukan FAIL.
- Jangan menurunkan policy, menghapus validasi, atau mengubah expected test semata-mata untuk membuat pipeline hijau.
- Jangan klaim fitur lengkap berdasarkan test unit saja; harus ada feature/integration/E2E yang sesuai.

---

## 10. TARGET ZIP DISTRIBUSI FINAL

ZIP final adalah deliverable utama setelah seluruh fitur web selesai. Harus satu paket lengkap, bukan hanya patch.

Isi dan verifikasi:
- seluruh source backend/web yang dibutuhkan;
- file dependency/lock dan aset build yang memang diperlukan untuk deployment;
- seluruh migration dan seed yang aman untuk fresh install (tanpa menjalankan pada production);
- konfigurasi contoh seperti `.env.example`, tanpa secret atau `.env` produksi;
- asset lokal, storage link instructions, permission/ownership guidance;
- README Bahasa Indonesia untuk upload, ekstraksi, konfigurasi dari backup pemilik, install/upgrade, permission, cache, queue/scheduler jika memang dipakai, health check, backup/restore dan troubleshooting;
- struktur direktori utuh dan konsisten;
- tanpa .git, cache, test output, log, temporary file, secret, backup internal, file pribadi, atau file yang tidak diperlukan;
- checksum SHA-256 dan manifest file.

Uji ZIP:
1. Buat dari source commit yang sudah ditetapkan.
2. Ekstrak ke direktori bersih/lingkungan uji; jangan overwrite live.
3. Pastikan tidak ada folder bertingkat ganda atau file penting tertinggal.
4. Verifikasi dependency install dari lock file.
5. Jalankan fresh install dan upgrade test pada disposable MySQL 8/InnoDB.
6. Verifikasi route, asset, storage, konfigurasi, permission, health check dan fungsi utama.
7. Pastikan paket bisa diunggah dan diekstrak pada VPS tanpa pemindahan file manual.
8. Catat commit source, manifest, checksum, hasil ekstraksi dan hasil test.

Jangan memasukkan file backup internal ke ZIP distribusi. Backup tetap disimpan di GitHub untuk jejak kerja.

**Jangan membuat atau menyatakan ZIP final siap sebelum seluruh acceptance fitur selesai.** Jangan upload/replace paket live atau deploy tanpa approval eksplisit owner.

---

## 11. PELAPORAN DAN CHECKPOINT WAJIB DI GITHUB

Setiap tahap harus mencatat:
1. **AUDIT** — file/route/component/policy/schema/API/test yang ditemukan, bukti dan gap.
2. **DESAIN** — solusi/kontrak, batas kewenangan, kompatibilitas dan alasan.
3. **IMPLEMENTASI** — file baru/diubah, ringkasan diff, backup byte-exact path/blob SHA, commit SHA.
4. **PENGUJIAN** — test yang benar-benar dijalankan, exact SHA, run URL, environment, pass/fail/skip/assertions dan keterbatasan.
5. **STATUS BACKLOG** — DONE / IN PROGRESS / BLOCKED / NOT STARTED / DEFERRED per item.
6. **RISIKO/BLOCKER** — hanya yang benar-benar memerlukan keputusan/akses owner.
7. **LANGKAH BERIKUTNYA** — tugas konkret yang langsung dapat dieksekusi.
8. **HANDOVER** — titik lanjut agent berikutnya dan daftar pekerjaan yang dilarang diulang.

Buat/ubah laporan dan checkpoint pada branch kerja GitHub. Perbarui `docs/AGENT_START_HERE.md` agar selalu menunjuk prompt terbaru. Jangan hanya menyimpan laporan di chat.

---

## 12. LARANGAN KERAS

Dilarang:
- mulai ulang proyek atau membuat ulang aplikasi dari asumsi;
- mengulang patch, audit, triage, test, atau pekerjaan yang sudah selesai tanpa pemicu yang sah;
- meminta spesifikasi VPS/konfigurasi server yang owner sudah nyatakan terverifikasi;
- melakukan eksperimen pada website/VPS/database live;
- meminta owner mengulang keputusan atau mengirim file yang sudah ada di GitHub;
- merge PR, deploy, menjalankan production migration, mengubah data produksi tanpa approval;
- menghapus source/migration/history tanpa backup dan rencana upgrade;
- membuat shared multi-tenant academic database antar-madrasah;
- membuat portal/role Kamad, Operator, atau Proktor permanen;
- memberi Admin hak authoring/operasi ujian Guru;
- memberi assignment Proktor privilege tambahan yang tidak disetujui;
- mengaktifkan XLSX question import atau SHORT_ANSWER sebelum gate lulus;
- membuat data CP/kurikulum resmi palsu;
- menyebut Device Lease/fencing siap produksi sebelum test integrasi/multi-device/concurrency/recovery;
- membuat ZIP final sebelum semua fitur dan acceptance gate terbukti;
- mengklaim SIAP DIDISTRIBUSIKAN atau SIAP PRODUKSI tanpa bukti.

---

## 13. PERINTAH EKSEKUSI PERTAMA UNTUK AGENT BARU

Jangan menulis ulang laporan audit seluruh repository. Lakukan verifikasi ringan:
1. Fetch HEAD branch aktif dan bandingkan dengan `e894d3f4727e3bfcfdb45a29ce1da5c3abd6b598`.
2. Baca dokumen handover, checkpoint W2, laporan W2, Issue #20/komentar owner, dan master spesifikasi untuk item yang akan dikerjakan.
3. Periksa status Actions #1714 dan catat secara tepat: full PHPUnit 536/2,231 PASS; workflow FAILURE hanya pada post-check InnoDB karena SQL query salah.
4. Buat backup byte-exact workflow `.github/workflows/tenant-backend-ci.yml`, perbaiki query post-check InnoDB secara minimal dan fail-closed.
5. Jalankan workflow pada HEAD stabil untuk memverifikasi post-check; jangan ulang remediasi PHPUnit yang sudah selesai.
6. Setelah itu lanjutkan gap profil Admin yang benar-benar belum selesai dan backlog web berikutnya yang tidak blocked.
7. Setiap perubahan harus diikuti focused test, laporan dan checkpoint.
8. Teruskan sampai seluruh fitur web dalam spesifikasi berfungsi dan seluruh acceptance gate terpenuhi; baru siapkan satu ZIP distribusi lengkap.

**JANGAN BERHENTI SETELAH AUDIT. JANGAN MENGULANG. JANGAN MEMBANGUN ULANG. LANJUTKAN DARI SOURCE TERAKHIR DAN SELESAIKAN GAP YANG NYATA.**

Status saat prompt ini dibuat: **IN PROGRESS — NOT READY FOR DISTRIBUTION**. Full PHPUnit pada #1714 lulus, tetapi workflow post-check InnoDB masih perlu diperbaiki; profil dasar Admin sudah lulus test namun fitur profil lanjutan belum lengkap; backlog Web Admin/Guru/Siswa belum seluruhnya diterima end-to-end.
