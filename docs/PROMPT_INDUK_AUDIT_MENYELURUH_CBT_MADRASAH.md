# PROMPT INDUK — AUDIT MENYELURUH, REKONSTRUKSI FITUR, DAN PENYEMPURNAAN CBT MADRASAH NOVEMBER 2026

## 0. Mandat

Anda bertindak sebagai Lead Software Architect, Senior Laravel Engineer, Senior Android Engineer, Database Architect, Application Security Auditor, QA Engineer, dan Technical Project Coordinator untuk proyek CBT MADRASAH NOVEMBER 2026.

Tujuan akhir: membangun sistem CBT Madrasah multi-tenant yang lengkap, aman, stabil, terintegrasi antara backend Web dan aplikasi Android native, mendukung kebutuhan akademik madrasah, serta siap digunakan setelah seluruh pengujian teknis dan validasi operasional selesai.

Mandat utama: jangan sampai fitur, keputusan desain, atau hasil pekerjaan agent terdahulu hilang akibat pergantian agent, branch, sesi, atau keterbatasan konteks.

## 1. Aturan wajib

1. Gunakan Bahasa Indonesia.
2. Pisahkan laporan menjadi AUDIT, DESAIN, IMPLEMENTASI, dan PENGUJIAN.
3. Jangan menyatakan fitur selesai tanpa bukti source dan pengujian.
4. Jangan mengubah ZIP baseline asli `CBT REVISI 6 NOPEMBER 2026.zip`.
5. Backup setiap file sebelum perubahan. Catat path, hash sebelum/sesudah, alasan, dan hasil pemeriksaan.
6. Jangan menghapus source, staged/unstaged changes, commit, atau backup yang belum dipahami.
7. Semua perubahan dilakukan di workspace rekonstruksi atau branch kerja aman.
8. Jangan menjalankan migration eksperimental pada database produksi. Gunakan database disposable/staging terisolasi.
9. Migration produksi memerlukan rencana, backup, preflight, pemeriksaan kompatibilitas, dan persetujuan eksplisit pengguna.
10. Build Android wajib melalui Android Studio. Jangan menjalankan Gradle melalui terminal.
11. Jangan mengklaim deployment, build, test, integrasi, atau kesiapan produksi tanpa bukti aktual.
12. Jangan meminta pengguna mengulang keputusan atau mengirim ulang file sebelum seluruh sumber yang tersedia diperiksa.
13. Jika laporan lama bertentangan dengan source aktual, utamakan source aktual dan catat perbedaannya.
14. Jangan membuat ulang source berdasarkan asumsi.
15. Jika konteks/sesi hampir habis, berhenti pada batas aman, simpan checkpoint lengkap, dan berikan instruksi lanjutan yang spesifik.

## 2. Sumber wajib diperiksa

Repository utama:
- https://github.com/zane0184/cbt_november
- Branch Web yang pernah digunakan: `fix/web-portal-usability-rbac-20261120`
- PR yang pernah diperiksa: #5
- Commit yang pernah dilaporkan: `ade5daa6cf853c164c4f51ec6c25ca68ce40b4f2`

Status branch, PR, dan commit wajib diverifikasi ulang; jangan menganggap status lama masih berlaku.

Periksa seluruh:
- branch, tag, release, commit history, Pull Request (merged, closed, draft, open);
- folder dan subfolder repository;
- source backend dan Android;
- migration, seeder, model, relasi, controller, service, request validation;
- route Web/API, Livewire, Blade, policy, middleware, role, permission;
- test, konfigurasi deployment, dokumentasi, checkpoint;
- source yang diubah atau ditambahkan agent sebelumnya.

Telusuri rantai setiap fitur:
**Database → Model → Service → Controller/Livewire → Route/API → Policy/Middleware → View/UI → Test → Dokumentasi.**

Jangan menyimpulkan fitur tersedia hanya karena menemukan satu file bernama mirip.

Periksa juga seluruh file percakapan, Project Files, Library, workspace, backup, laporan, dan checkpoint yang tersedia. Dokumen tahap yang sudah pernah diberikan antara lain:
- `CBT_Audit_Tahap_1.md`
- `CBT_Audit_Tahap_2_Fase1B.md`
- `CBT_Rancangan_Integritas_Database_Tahap_3.md`
- `CBT_Implementasi_Tahap_4A.md` sampai `CBT_Implementasi_Tahap_4D.md`
- `CBT_Audit_Tahap_5A_Device_Fencing.md`
- `CBT_Implementasi_Tahap_5B.md` sampai `CBT_Implementasi_Tahap_5L.md`
- `CBT_Implementasi_Tahap_5M_FINAL.md`
- `CBT_Implementasi_Tahap_5N.md`
- `CBT_Audit_Tahap_5O_Fencing_Recovery.md`
- `CBT_Implementasi_Tahap_5P1.md`
- `CBT_Implementasi_Tahap_5P_Backend_Android.md`
- `Lanjutkan Eksekusi Sampai Selesai.txt`
- seluruh laporan PR, audit Web, Android, backup, manifest, dan checkpoint lainnya.

Daftar ini bukan batas sumber. Cari seluruh dokumen terkait. Catat nama, lokasi, tanggal, tahap, keputusan, status, kaitan dengan source aktual, dan apakah klaim masih benar.

## 3. Arsitektur dan keputusan yang telah disepakati

### Teknologi
Backend:
- Laravel 13.x, PHP 8.3
- MySQL 8.0+ / InnoDB
- Blade, Livewire, Laravel Sanctum
- Modular Monolith, multi-tenant

Android:
- Kotlin, Jetpack Compose, Clean Architecture, MVVM
- Room, WorkManager, Retrofit
- Local-First dan sinkronisasi aman

Server:
- VPS, aaPanel, Nginx, PHP 8.3, MySQL 8+, Redis opsional

Toolchain Android yang pernah disepakati:
- Kotlin 2.1.0
- KSP 2.1.0-1.0.29
- Room 2.6.1
- AGP 8.8
- JVM Target 17

Verifikasi konfigurasi aktual sebelum menyimpulkan versi yang digunakan.

### Portal dan kewenangan
Tiga portal utama:
1. Super Admin
2. Admin Madrasah
3. Guru

Siswa menggunakan aplikasi Android native untuk ujian Android. Web/PWA peserta sementara disediakan untuk iOS.

Proktor bukan portal permanen; Proktor adalah penugasan operasional sementara dengan batas kewenangan, waktu, ujian, dan audit log.

Kepala Madrasah (Kamad) mempunyai fungsi pengawasan sesuai kewenangan dan tidak otomatis disamakan dengan Admin. Operator merupakan fungsi administrasi; periksa penempatannya sesuai keputusan sebelumnya, jangan membuat role/portal permanen baru berdasarkan asumsi.

Semua permintaan wajib divalidasi berdasarkan tenant aktif, identitas, role, permission, penugasan, lingkup akademik, kepemilikan data, status objek, dan policy. Isolasi tenant wajib ditegakkan di backend, bukan hanya dengan menyembunyikan menu.

### Kurikulum dan akademik
Dukung:
- Kurikulum Merdeka;
- Kurikulum Berbasis Cinta (Panca Cinta);
- Pembelajaran Mendalam;
- kurikulum berikutnya melalui struktur extensible.

Integrasi bukan hanya Bank Soal. Harus tercermin pada identitas akademik, tahun ajaran, semester, fase/kelas, mapel, CP, TP, ATP, perencanaan pembelajaran, materi, asesmen, bank soal, ujian, penilaian, analisis hasil, laporan, rapor, dan histori akademik. Pertahankan versi kurikulum dan seluruh histori.

### Prinsip CBT
- Android peserta wajib aplikasi native, bukan browser/PWA.
- iOS sementara via Web.
- Token otomatis dibuat setelah Guru menerbitkan ujian; dapat dilihat sebelum jadwal, tetapi ujian hanya mulai sesuai jadwal server.
- Waktu server adalah acuan resmi.
- Attempt, Answer, Score, Device Lease, dan Fencing harus konsisten.
- Dukung offline dan pemulihan sinkronisasi aman.
- Kamera bukan syarat CBT standar.
- Reset Attempt, Force Submit, takeover perangkat, dan perpanjangan waktu harus berotorisasi, memiliki alasan, dan tercatat di audit log.

## 4. Audit kelengkapan fitur

Buat master feature matrix dengan kolom:
1. ID fitur;
2. nama dan deskripsi;
3. kebutuhan;
4. portal/role;
5. platform (Web, Android, API, Backend);
6. modul/domain;
7. sumber keputusan (dokumen/percakapan/PR);
8. lokasi source;
9. database/model;
10. route/API;
11. policy/middleware/permission;
12. UI;
13. dependensi fitur;
14. status implementasi;
15. status pengujian;
16. temuan/kekurangan;
17. risiko;
18. prioritas;
19. rekomendasi;
20. bukti.

Status yang diperbolehkan:
- `IMPLEMENTED & VERIFIED`
- `IMPLEMENTED, NOT VERIFIED`
- `PARTIAL`
- `BACKEND ONLY`
- `UI ONLY`
- `MISSING`
- `BLOCKED`
- `CONFLICT`
- `DEPRECATED`

Jangan gunakan status selesai tanpa bukti.

## 5. Audit portal Admin Madrasah

Periksa sekurang-kurangnya:

**Dashboard dan identitas:** dashboard Admin, statistik madrasah, pengguna, guru/siswa, rombel, ujian, status operasional, profil/identitas madrasah, logo dan konfigurasi.

**Data akademik:** tahun ajaran, semester, kelas, fase, rombel, mata pelajaran, guru, siswa, keanggotaan rombel, mutasi, kenaikan kelas, histori akademik, status aktif/nonaktif.

**Pengguna dan kewenangan:** pembuatan/perubahan akun, penonaktifan/aktivasi, reset kredensial, role, permission, penugasan Guru, akun Siswa, audit perubahan identitas.

**Operasional CBT:** monitoring ujian, penugasan Proktor, status perangkat/peserta, histori attempt, dukungan teknis, reset attempt, force submit, takeover, perpanjangan waktu, audit log.

**Laporan:** laporan ujian, rekap peserta/hasil/nilai/kelas/mapel, laporan akademik, ekspor, histori.

Pastikan Admin tidak mengambil alih hak akademik Guru yang sudah disepakati.

## 6. Audit portal Guru

Guru adalah pemilik proses pembelajaran, bank soal, asesmen, dan penerbitan ujian sesuai penugasan.

**Dashboard Guru:** dashboard khusus Guru, mapel dan rombel, penugasan, jadwal mengajar, asesmen mendatang, ujian aktif/selesai, hasil belum dinilai, finalisasi, notifikasi pekerjaan.

**Pembelajaran:** mapel/rombel yang ditugaskan, CP, TP, ATP, perencanaan, materi, relasi materi-kurikulum, histori pembelajaran.

**Bank Soal:** buat/edit/arsip, versi soal, kategori, mapel, fase/kelas, CP/TP, kesulitan, jenis soal; pilihan ganda, pilihan ganda kompleks, benar/salah, menjodohkan, jawaban singkat, uraian; media jika didukung; rumus matematika; Arab/RTL/harakat; rubrik; import/export; preview; validasi.

**Asesmen dan ujian:** tujuan asesmen, peserta, penugasan rombel/individu, pemilihan soal, randomisasi, snapshot, bobot, durasi, jadwal, token, publikasi, penutupan, histori perubahan, audit aktivitas. Guru boleh membuat, mengatur, dan menerbitkan ujian sesuai penugasan tanpa persetujuan Admin.

**Monitoring:** peserta, status pengerjaan/koneksi/sinkronisasi/perangkat, Proktor, tindakan operasional yang diizinkan, histori tindakan.

**Penilaian dan analisis:** penilaian otomatis dan uraian, rubrik, feedback, revisi sesuai aturan, finalisasi, publikasi, analisis butir, kesulitan, daya pembeda bila tersedia, distribusi nilai, capaian CP/TP, rekap rombel, laporan individu, PDF/Excel, histori.

Fungsi Guru harus memiliki navigasi yang jelas. Guru tidak boleh sekadar melihat panel Admin yang dipangkas.

## 7. Audit portal Siswa

Audit Web iOS dan Android native secara terpisah.

Fitur minimal: dashboard, profil, identitas akademik, jadwal, informasi pembelajaran, daftar/detail ujian, petunjuk, token, mulai/lanjut ujian, timer server, navigasi soal, penyimpanan jawaban, autosave, offline, sinkronisasi, pemulihan, submit, status submit, histori, hasil/nilai, feedback terpublikasi, capaian belajar, notifikasi.

Keamanan:
- tidak melihat soal sebelum diizinkan;
- tidak melihat kunci jawaban;
- tidak melihat nilai yang belum dipublikasikan;
- tidak melihat data siswa lain;
- tidak membuka attempt peserta lain;
- tidak melewati jadwal server;
- tidak mengganti tenant;
- tidak mengirim jawaban dengan fencing generation lama;
- tidak melakukan replay jawaban tidak sah.

Pastikan peserta Android tidak dapat mengerjakan ujian melalui browser.

## 8. Audit backend dan database

Audit seluruh modul: IAM, Tenant, Academic, Curriculum, Question Bank, Assessment, Exam, Execution, Attempt, Answer, Scoring, Reporting, Monitoring, Proctor Assignment, Audit Log, Licensing/Productization, System Update, API, dan integrasi Android.

Untuk setiap modul periksa model, relasi, fillable/guarded, cast, scope, service, controller, request validation, policy, middleware, route, transaction, idempotency, event/listener/job, queue, cache, logging, dan test.

Database: periksa migration, schema, index, constraint, FK, unique key, nullable/default, enum, JSON, soft delete, dan relasi.

Perhatian khusus:
- tenant isolation dan composite FK;
- unique constraint dan race condition;
- data orphan dan referential integrity;
- histori akademik dan versioning;
- penghapusan/cascade;
- idempotency dan transaction boundary;
- lock ordering;
- kompatibilitas MySQL 8/InnoDB;
- perbedaan SQLite dan MySQL.

Jangan menganggap test SQLite membuktikan integritas MySQL.

## 9. Audit keamanan

Audit broken access control, IDOR, tenant escape, privilege escalation, role/permission conflict, mass assignment, CSRF, XSS, SQL injection, session, token, API authentication, rate limiting, file upload, storage isolation, kebocoran data, audit log, race condition, replay attack, takeover perangkat, stale request, duplicate submission, unauthorized export/grading/result access.

Semua tindakan sensitif wajib divalidasi di backend dan mempunyai audit trail.

## 10. Audit Android, offline, dan pemulihan

Periksa seluruh struktur Android: app, data, domain, presentation, local, remote, database, DAO, entity, repository, worker, scheduler, interceptor, ViewModel, Compose screen, navigation, DI/factory, manifest, resource, test, androidTest.

Cocokkan setiap fungsi Android dengan API backend.

Audit Local-first, Room, pending answer, autosave, outbox, WorkManager, retry, idempotency, conflict detection, conflict archive, export arsip, identitas server/tenant/siswa, attempt/snapshot/revision, fence generation, lease token/expiry, resume, force-stop, reboot, pergantian jaringan/akun/server, clear data/uninstall.

Jawaban konflik tidak boleh dihapus atau dikirim ulang otomatis tanpa protokol pemulihan yang sah.

### Device Lease/Fencing
Periksa claim, heartbeat, validate, save answer, release, takeover Proktor, generation increment, token hash, expiry, device binding, stale request, conflict archive, recovery, transaksi, dan lock order.

Rancang pengujian race:
1. save vs release;
2. heartbeat perangkat lama setelah takeover;
3. save perangkat lama setelah takeover;
4. claim dua perangkat bersamaan;
5. duplicate release;
6. expired lease;
7. token salah;
8. generation lama;
9. tenant mismatch;
10. user mismatch;
11. attempt mismatch;
12. retry duplikat;
13. perangkat offline lalu online.

Jangan menyatakan fencing siap produksi hanya dari pemeriksaan statis. RESTORE/IMPORT jawaban tetap dilarang sampai blocker, otorisasi, identitas, generation, dan pengujian pemulihan selesai.

## 11. Audit UI/UX

Target Web: premium, modern, profesional, mewah tetapi ringan, konsisten, responsif, mudah digunakan. Jangan hanya mengganti warna.

Buat design system: design tokens, warna, tipografi, spacing, grid, border, shadow, ikon, button, form, table, modal, dropdown, tabs, toast, empty/loading/error state, responsive behavior, accessibility.

Layout terpisah sesuai peran:
- Super Admin;
- Admin Madrasah;
- Guru;
- Siswa Web/iOS.

Dashboard Guru harus menjadi ruang kerja Guru. Dashboard Admin fokus administrasi dan monitoring. Dashboard Siswa fokus jadwal, tugas, ujian, hasil, dan informasi akademik. Tampilan ujian minimalis, fokus, cepat, dan tidak mengganggu konsentrasi.

## 12. Audit VPS dan Livewire

Domain yang pernah digunakan: `https://cbt.min15jakarta.sch.id`

Lokasi Laravel: `/www/wwwroot/cbt.min15jakarta.sch.id/backend-laravel`
Document root: `/www/wwwroot/cbt.min15jakarta.sch.id/backend-laravel/public`
Nginx vhost: `/www/server/panel/vhost/nginx/cbt.min15jakarta.sch.id.conf`
Rewrite: `/www/server/panel/vhost/rewrite/cbt.min15jakarta.sch.id.conf`

PHP 8.3 dan Laravel 13 pernah terdeteksi.

Pernah ada masalah `/livewire/livewire.min.js` berstatus HTTP 404 meskipun body berisi JavaScript. Route Laravel `livewire/livewire.min.js` terdaftar. Dugaan kuat: regex Nginx JS/CSS mengambil request sebelum Laravel.

Backup konfigurasi yang pernah dibuat:
`/www/server/panel/vhost/nginx/cbt.min15jakarta.sch.id.conf.bak_before_livewire_20261120_231038`

Hash saat backup:
`76d5aeef22ebfc4188e88444a830f2d7783c7a1b4fb47faea2ef1ee26be3668a`

Aturan yang ditemukan:
```nginx
location ~* \\.(?:js|css)$
{
    expires 12h;
    error_log /dev/null;
    access_log /dev/null;
}
```

Perkembangan terakhir yang dilaporkan pengguna:
- pengecualian exact location untuk `/livewire/livewire.min.js` telah ditambahkan;
- `nginx -t` berhasil dengan warning `conflicting server name "_" on 0.0.0.0:80, ignored`;
- origin melalui `curl --resolve ...:127.0.0.1` memberikan HTTP 200 dan `application/javascript`;
- request publik sempat HTTP 404 dari Cloudflare cache HIT, lalu HTTP 200 dengan `cf-cache-status: MISS`;
- pengguna mengirim screenshot portal dan menyatakan tampilan dapat digunakan kembali.

Catatan: bukti ini menunjukkan endpoint Livewire telah berhasil mengembalikan HTTP 200 pada origin dan publik dalam pemeriksaan yang dilaporkan. Jangan menganggap seluruh masalah UI/portal selesai hanya karena endpoint JS pulih. Jangan mengubah Nginx lagi tanpa audit kondisi terkini dan backup.

## 13. Tahapan kerja audit

Jangan melakukan audit besar dalam satu batch. Pecah menjadi fase dengan output dan checkpoint.

### FASE A — Inventarisasi sumber
Periksa file, repository, branch, dokumen, backup, source Web/Android; buat manifest.
Output:
- `docs/AUDIT_A00_SOURCE_INVENTORY.md`
- `docs/AUDIT_A00_FILE_MANIFEST.csv`
- `docs/CHECKPOINT_A00.md`

### FASE B — Rekonstruksi keputusan
Baca laporan dan keputusan, cocokkan dengan source, catat konflik.
Output:
- `docs/AUDIT_A01_DECISION_RECONSTRUCTION.md`
- `docs/AUDIT_A01_FEATURE_REQUIREMENTS.md`
- `docs/CHECKPOINT_A01.md`

### FASE C — Backend dan database
Audit IAM, tenant, akademik, kurikulum, bank soal, asesmen, ujian, attempt, answer, scoring, laporan, monitoring, audit log, migration, test.
Output:
- `docs/AUDIT_A02_BACKEND_DATABASE.md`
- `docs/AUDIT_A02_FEATURE_MATRIX_BACKEND.csv`
- `docs/CHECKPOINT_A02.md`

### FASE D — Web per role
Audit Super Admin, Admin, Guru, Kamad, Proktor sementara, Siswa Web/iOS, route, layout, Livewire, Blade, permission, UI, alur.
Output:
- `docs/AUDIT_A03_WEB_ROLE_COMPLETENESS.md`
- `docs/AUDIT_A03_FEATURE_MATRIX_WEB.csv`
- `docs/CHECKPOINT_A03.md`

### FASE E — Android
Audit seluruh aplikasi, local DB, API, sinkronisasi, offline, konflik, lease, fencing, recovery.
Output:
- `docs/AUDIT_A04_ANDROID_COMPLETENESS.md`
- `docs/AUDIT_A04_FEATURE_MATRIX_ANDROID.csv`
- `docs/CHECKPOINT_A04.md`

### FASE F — Keamanan dan integrasi
Audit tenant isolation, RBAC, route authorization, transaksi, idempotensi, race, perangkat, sinkronisasi, API, Android–Laravel.
Output:
- `docs/AUDIT_A05_SECURITY_INTEGRATION.md`
- `docs/AUDIT_A05_RISK_REGISTER.csv`
- `docs/CHECKPOINT_A05.md`

### FASE G — Gap analysis
Bandingkan seluruh kebutuhan dengan implementasi. Klasifikasikan lengkap, parsial, backend only, UI only, missing, belum diuji, konflik, blocker.
Output:
- `docs/AUDIT_A06_FEATURE_GAP_ANALYSIS.md`
- `docs/AUDIT_A06_MASTER_FEATURE_MATRIX.csv`
- `docs/CHECKPOINT_A06.md`

### FASE H — Target design
Rancang portal, navigasi, dashboard, modul, workflow, role, permission, integrasi kurikulum, API, database, UI, Android, laporan.
Output:
- `docs/DESIGN_A07_TARGET_ARCHITECTURE.md`
- `docs/DESIGN_A07_PORTAL_AND_NAVIGATION.md`
- `docs/DESIGN_A07_PERMISSION_MATRIX.csv`
- `docs/CHECKPOINT_A07.md`

### FASE I — Roadmap implementasi
Susun pekerjaan menurut dependensi dan risiko. Jangan coding sebelum gap analysis dan desain selesai.
Output:
- `docs/IMPLEMENTATION_A08_ROADMAP.md`
- `docs/IMPLEMENTATION_A08_TASK_MATRIX.csv`
- `docs/CHECKPOINT_A08.md`

### FASE J — Implementasi dan pengujian
Implementasikan per modul, dengan backup, diff, test, risiko, rollback, dan checkpoint.

### FASE K — Integrasi dan validasi akhir
Uji integrasi Web/API/Android, role, tenant, offline, multi-device, recovery, stabilitas, dan validasi operasional madrasah.

## 14. Checkpoint dan pemulihan konteks

Setiap akhir fase atau sebelum sesi berpotensi terputus, checkpoint harus memuat:
1. proyek, tanggal/waktu, fase aktif;
2. fase selesai/belum selesai;
3. branch dan commit;
4. source yang diperiksa;
5. file berubah;
6. backup dan hash;
7. hasil test;
8. temuan dan blocker;
9. risiko;
10. keputusan;
11. langkah selanjutnya;
12. perintah/prosedur melanjutkan.

Simpan checkpoint terbaru:
- `docs/CHECKPOINT_MASTER_LATEST.md`
- `docs/MASTER_AUDIT_STATUS.md`

Jangan menghilangkan checkpoint lama. Catat file yang sudah/belum diperiksa. Buat instruksi lanjutan yang bisa langsung dieksekusi agent berikutnya.

Jika pengguna mengatakan "Lanjutkan", baca checkpoint terbaru, verifikasi source aktual, lalu lanjutkan dari posisi terakhir yang terbukti selesai. Jangan mengulang patch tanpa alasan.

## 15. Prioritas

**P0 — Keamanan dan integritas:** isolasi tenant, RBAC, authorization, integritas DB, Attempt/Answer/Score, Device Lease/Fencing, sinkronisasi, recovery, audit log.

**P1 — Fungsi inti:** Admin, Guru, Siswa, kurikulum, bank soal, asesmen, ujian, penilaian, laporan.

**P2 — Integrasi dan UX:** dashboard per role, navigasi, UI premium, responsive, aksesibilitas, notifikasi, analitik.

**P3 — Productization:** lisensi, Update Center, versioning, kompatibilitas, release notes, backup sebelum update, health checks, distribusi.

Jangan mendahulukan kosmetik UI daripada keamanan dan integritas data.

## 16. Kriteria audit selesai

Audit hanya selesai jika:
1. seluruh sumber yang tersedia diinventarisasi;
2. keputusan terdahulu direkonstruksi;
3. repository dan branch relevan diperiksa;
4. feature matrix mencakup seluruh kebutuhan;
5. setiap fitur memiliki status dan bukti;
6. konflik keputusan dicatat;
7. kekurangan dan blocker dicatat;
8. backend, Web, Android, DB, dan keamanan diaudit;
9. gap analysis tersedia;
10. desain target dan roadmap tersedia;
11. checkpoint dapat digunakan untuk melanjutkan.

## 17. Kriteria siap produksi

Tidak boleh menyatakan siap produksi hanya karena source lengkap, dashboard tampil, PHPUnit lulus, APK berhasil dibangun, atau backend terdeploy.

Harus tersedia bukti integrasi Web/API/Android, isolasi tenant, RBAC, transaksi, idempotensi, concurrency, Device Lease/Fencing, multi-device, offline, sinkronisasi, recovery, keamanan, stabilitas, backup, deployment, serta validasi Admin, Guru, dan Siswa di madrasah.

## 18. Distribusi Android Studio

Setelah rekonstruksi dan validasi selesai, buat satu ZIP distribusi Android Studio yang:
- memuat seluruh source dan resource;
- mempertahankan struktur direktori;
- memuat konfigurasi yang diperlukan;
- tidak memuat backup internal/file sementara;
- bisa diekstrak melalui Windows Explorer;
- memiliki README untuk ekstraksi, penempatan `.env` dari backup pengguna bila diperlukan, pembukaan proyek, dan finalisasi Android Studio.

Verifikasi kelengkapan, struktur, konfigurasi, dan hasil ekstraksi sebelum menyatakan siap didistribusikan.

## 19. Perintah eksekusi pertama

**Mulai dari AUDIT, bukan coding.**

1. Periksa seluruh sumber yang tersedia.
2. Baca checkpoint terbaru.
3. Periksa source aktual GitHub.
4. Periksa branch dan PR.
5. Baca seluruh dokumen tahap sebelumnya.
6. Periksa workspace dan backup.
7. Buat inventaris sumber.
8. Rekonstruksi keputusan dan fitur.
9. Buat master feature matrix.
10. Lanjutkan audit per fase.

Jangan meminta pengguna mengulang keputusan atau mengirim ulang file yang sudah tersedia. Jangan membuat source dari asumsi. Jangan mulai perubahan UI/backend sebelum audit dan gap analysis selesai.

Laporan pertama wajib memisahkan:
- **AUDIT:** sumber ditemukan, repository/branch, dokumen dibaca, source diperiksa, sumber belum dapat diakses, status pekerjaan sebelumnya, temuan dan konflik.
- **DESAIN:** metode audit, feature matrix, fase, checkpoint.
- **IMPLEMENTASI:** nyatakan file berubah atau tidak; pada inventarisasi awal seharusnya tidak ada patch.
- **PENGUJIAN:** pemeriksaan yang dijalankan, hasil, hal yang belum diuji, blocker.
- **LANGKAH BERIKUTNYA:** fase audit berikutnya berdasarkan bukti.

## PERINTAH PENUTUP

**MULAI AUDIT MENYELURUH CBT MADRASAH NOVEMBER 2026.**

Jangan membatasi audit pada PR terakhir, halaman Guru, atau beberapa file. Rekonstruksi seluruh kebutuhan dan pastikan setiap fitur dapat dilacak sampai source, UI, backend, database, permission, dan pengujiannya.

Jika pekerjaan terlalu besar untuk satu sesi, pecah menjadi fase, buat checkpoint, dan lanjutkan lintas sesi. Jangan mengorbankan kelengkapan, keamanan, integritas data, dan kesinambungan pekerjaan demi terlihat cepat selesai.

Target akhir: **CBT MADRASAH YANG LENGKAP, AMAN, STABIL, MULTI-TENANT, TERINTEGRASI, TERUJI, DAN SIAP DIGUNAKAN DI MADRASAH.**
