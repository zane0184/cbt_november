# PROMPT INDUK AGENT BARU — PENYELESAIAN WEB ADMIN MADRASAH

**Proyek:** CBT MADRASAH NOVEMBER 2026  
**Fokus tunggal:** Audit, desain, implementasi, pengujian, dan validasi Web Admin Madrasah.  
**Repository:** https://github.com/zane0184/cbt_november  
**Spesifikasi induk wajib dibaca:** `docs/SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md`  
**Checkpoint wajib dibaca:** `docs/CHECKPOINT_WEB_ADMIN_2026-11-21.md`

---

## 0. PERINTAH UTAMA

Anda adalah coding agent baru yang melanjutkan proyek yang sudah berjalan. Jangan memulai ulang, jangan mengarang source, jangan menganggap laporan lama sebagai bukti keadaan source saat ini.

**Tugas Anda sekarang adalah menyelesaikan Web Admin saja.** Jangan mengaudit, mendesain, mengimplementasikan, atau mengubah Portal Guru maupun Portal Siswa. Setelah Web Admin selesai, diuji, divalidasi, dilaporkan, dan mendapat persetujuan pengguna, barulah pekerjaan Portal Guru boleh dimulai. Portal Siswa baru boleh dimulai setelah Portal Guru selesai dan disetujui.

Jangan menyela tahap Web Admin dengan pekerjaan portal lain, Android, Device Lease/Fencing, deployment VPS, atau remediasi P0 database.

## 1. URUTAN WAJIB SEBELUM MENYENTUH SOURCE

### 1.1 Sinkronisasi dan pemeriksaan GitHub
1. Pastikan repository dan akses aktual.
2. Periksa seluruh branch, commit, PR terbuka/tertutup/merged, workflow, artifact, dan perubahan terbaru.
3. Baca ulang spesifikasi induk dan checkpoint ini.
4. Periksa PR #6 dan PR #5: status, head/base SHA, draft/merged, checks, review comments, dan perubahan sejak laporan terakhir.
5. Identifikasi baseline source Web yang benar-benar akan diaudit. Baseline historis yang pernah tercatat adalah branch `fix/web-portal-usability-rbac-20261120`, commit `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`; ini bukan jaminan HEAD saat ini.
6. Jangan bekerja langsung di branch dokumentasi, jangan force-push, jangan merge PR, jangan mengubah branch kandidat historis.
7. Buat branch kerja audit/implementasi baru dari baseline source yang telah dipilih dan catat alasan serta SHA-nya. Branch audit dan implementasi boleh dipisah agar hasil review tidak tercampur.

### 1.2 Pemeriksaan source menyeluruh (READ-ONLY)
Audit recursive seluruh repository yang relevan, terutama:
- `backend-laravel/routes/`
- `backend-laravel/app/Livewire/`
- `backend-laravel/app/Http/Controllers/`
- `backend-laravel/app/Models/`
- `backend-laravel/app/Policies/`
- `backend-laravel/app/Providers/`
- `backend-laravel/app/Services/`
- `backend-laravel/resources/views/`
- `backend-laravel/resources/css/`, `resources/js/`, `vite.config.*`, `package.json`
- `backend-laravel/database/migrations/`, `seeders/`, `factories/`
- `backend-laravel/tests/`
- konfigurasi permission/RBAC, tenant middleware/scope, audit log, import/export, storage, queue, scheduler, backup.
- dokumentasi, backup, manifest, dan checkpoint terdahulu yang relevan.

Jangan hanya mencari nama folder Admin. Cari juga route alias, role/permission, view, komponen bersama, service, model, query, policy, dan test yang mendukung atau membatasi Admin. Hindari perubahan fitur Guru/Siswa meskipun berada dalam file bersama.

### 1.3 Klasifikasi wajib
Setiap fitur harus diberi salah satu status:
1. SUDAH ADA DAN BERFUNGSI — UI + backend + validasi + permission + persistensi + test memadai.
2. ADA TAPI BELUM LENGKAP.
3. BACKEND ONLY.
4. UI ONLY.
5. BELUM ADA.
6. BERMASALAH/BERISIKO.
7. BELUM TERVERIFIKASI.

Setiap baris matriks memuat: fitur, route, file/path, komponen, aksi, model/tabel/query, permission/policy, tenant guard, validasi, audit log, test yang ada, hasil test aktual, gap, risiko, rekomendasi, dan dependensi.

**Keberadaan route/model/migration bukan bukti fitur berfungsi.** Telusuri seluruh jalur UI → action → authorization → validation → service/query → transaction → persistence → audit → response → test.

## 2. TAHAP KERJA — DILARANG MELOMPATI GERBANG

### Tahap A — AUDIT
- Read-only; jangan patch source.
- Buat inventaris route dan halaman Admin.
- Buat matriks fitur lengkap sesuai spesifikasi induk.
- Buat matriks role × route × action × permission.
- Audit tenant isolation per query dan mutasi.
- Audit validasi input, mass assignment, IDOR, CSRF, upload/download, file storage, rate limit, session, reset password, ekspor data pribadi, dan audit log.
- Audit query dashboard, agregasi, pagination, N+1, dan performa.
- Audit impor Excel: template, format, parser, batas ukuran, validasi, duplicate detection, preview, confirmation, transaction, rollback, error report, dan import history.
- Audit akademik: tahun ajaran, semester, kelas/fase, rombel, wali kelas, penempatan, assignment Guru, kenaikan kelas, kelulusan, alumni, histori.
- Audit kurikulum hanya Kurmer/KBC, sumber resmi, versioning, seed idempotent, master resmi vs custom, Mulok, CP/TP/ATP, Pembelajaran Mendalam, Panca Cinta, DPL.
- Audit UI/UX pada seluruh viewport dan alur kerja. Catat bukti screenshot/route jika tersedia.
- Audit test existing dan gap test. Jangan klaim runtime pass jika tidak dijalankan.

**Output Tahap A:** laporan audit, matriks gap, risiko terurut P0/P1/P2, inventaris test, bukti source/commit, dan daftar pertanyaan yang benar-benar menghalangi desain. Jangan coding.

### Tahap B — DESAIN
Setelah audit selesai:
- Buat sitemap final dan struktur navigasi.
- Buat alur pengguna untuk setiap fitur Admin.
- Buat rancangan halaman, komponen, form, tabel, filter, modal, empty/loading/error/success state.
- Buat desain responsif desktop/laptop/tablet/mobile.
- Buat design tokens dan aturan komponen.
- Rancang permission matrix dan server-side authorization.
- Rancang transaksi, idempotensi, histori, audit trail, import preview/commit, dan pemulihan kegagalan.
- Rancang query dashboard dan batas statistik eksekutif.
- Rancang test plan dan acceptance criteria per fitur.
- Buat daftar file yang diperkirakan berubah, dependensi, risiko regresi, serta strategi backup.

**Gerbang persetujuan:** serahkan Tahap A dan B kepada pengguna. Jangan coding sebelum pengguna menyetujui desain dan urutan implementasi. Persetujuan desain tidak berarti persetujuan deployment atau migration produksi.

### Tahap C — IMPLEMENTASI
Hanya setelah persetujuan desain:
1. Buat branch implementasi baru dari baseline yang disepakati.
2. Catat commit awal dan status working tree.
3. Backup setiap file yang akan diubah sebelum patch. Backup tidak boleh disimpan dalam paket deployment.
4. Catat SHA-256 file asli, backup, dan hasil akhir.
5. Implementasikan bertahap per modul, dengan perubahan kecil dan terisolasi.
6. Jangan menghapus histori akademik, data, migration lama, atau source yang belum dipahami.
7. Jangan melakukan destructive migration. Jangan menjalankan migration eksperimental pada DB produksi.
8. Gunakan transaction, lock, idempotency, validation, policy, tenant checks, dan audit log sesuai risiko.
9. Jangan menambahkan data regulasi/kurikulum yang tidak dapat dibuktikan dari sumber resmi.
10. Jangan memperluas scope ke Guru/Siswa. Jika perubahan file bersama tidak terhindarkan, jelaskan alasan, diff, risiko, dan bukti bahwa perilaku portal lain tidak berubah.
11. Setiap patch harus disertai regression test yang relevan.
12. Jangan mengubah `.env`, secret, APP_KEY, konfigurasi VPS, Nginx, database produksi, atau melakukan deployment.

### Tahap D — PENGUJIAN
Jalankan dan laporkan tes nyata, sesuai kemampuan lingkungan:
- syntax/lint dan static checks;
- route:list dan route authorization;
- unit/feature tests;
- RBAC matrix tests;
- tenant isolation tests;
- IDOR/CSRF/upload/download security tests;
- import Excel tests termasuk malformed file, duplicate, partial failure, transaction rollback, dan idempotency;
- academic transition tests (promosi, tinggal kelas, kelulusan, alumni, histori);
- curriculum seed idempotency/versioning tests;
- dashboard query correctness dan performance;
- responsive visual checks pada viewport 1366×768, 390×844, tablet portrait/landscape;
- accessibility dasar: keyboard, focus, contrast, label, error feedback;
- regression tests untuk alur yang sudah ada.

Gunakan database MySQL 8 disposable untuk test yang memerlukan MySQL. Pastikan nama DB test fail-closed dan tidak mungkin menunjuk DB produksi. Jika MySQL 8/PHP 8.3/Composer tidak tersedia, nyatakan BLOCKED; jangan mengganti dengan SQLite lalu mengklaim MySQL lulus.

Simpan command, exit code, jumlah test/assertion, versi runtime, commit SHA, log ringkas, dan artifact bukti. Bedakan PASS, FAIL, BLOCKED, NOT RUN.

### Tahap E — VALIDASI DAN PENUTUPAN WEB ADMIN
- Demonstrasikan alur Admin end-to-end dengan data uji.
- Validasi role dan batas kewenangan Admin.
- Validasi keamanan tenant dan akses data pribadi.
- Validasi seluruh impor wajib.
- Validasi administrasi akademik dan histori.
- Validasi Kurmer/KBC dan tagging Panca Cinta sesuai spesifikasi.
- Validasi backup/restore hanya pada lingkungan disposable.
- Buat daftar known limitations dan residual risks.
- Serahkan laporan final, test evidence, screenshot, commit/PR, backup manifest, dan checkpoint.
- Minta persetujuan eksplisit pengguna untuk menyatakan Web Admin selesai.
- Jangan memulai Portal Guru sebelum persetujuan itu diterima.

## 3. BATAS PRODUK DAN KEWENANGAN

### Admin Madrasah boleh mengelola
- Dashboard eksekutif dan kesehatan instalasi.
- Identitas madrasah, logo, kop dokumen, kepala madrasah.
- Tahun ajaran, semester, kelas/fase, rombel, wali kelas.
- Data Guru dan Siswa untuk administrasi.
- Penugasan Guru kelas/mapel/rombel/periode.
- Penempatan siswa, kenaikan kelas, kelulusan kelas 6, alumni, histori akademik.
- Master mapel MI, Mulok, Kurmer/KBC, CP/TP/ATP, Pembelajaran Mendalam, Panca Cinta, DPL.
- Impor Excel Siswa, Guru, Mata Pelajaran.
- Laporan administratif.
- Backup/restore, kesehatan sistem, audit log administratif, konfigurasi instalasi.
- Profil Admin, keamanan akun, preferensi, notifikasi.

### Admin tidak boleh mengambil alih operasional Guru
- Tidak membuat/mengedit bank soal atau media soal.
- Tidak membuat, mengatur, menerbitkan, atau mengunci ujian operasional.
- Tidak mengelola token, monitoring pengerjaan, penugasan Proktor, penilaian, jawaban, analisis, atau laporan hasil ujian individual Guru.
- Statistik agregat eksekutif hanya bila dibutuhkan dan tidak membuka detail jawaban/nilai yang bukan kewenangannya.
- Proktor adalah penugasan sementara per ujian, bukan portal permanen.

Role/permission backend adalah otoritas; menyembunyikan menu di UI tidak cukup.

## 4. KEPUTUSAN ARSITEKTUR YANG TIDAK BOLEH DIUBAH

- Backend Laravel 13, PHP 8.3, MySQL 8+, Blade/Livewire, Sanctum, Modular Monolith.
- Satu madrasah = satu instalasi = satu database. Domain, DB, .env, APP_KEY, storage, secrets, backup, dan lifecycle terpisah per madrasah.
- tenant_id, TenantContext, middleware, global scope, policy, dan validasi tenant tetap dipertahankan sebagai lapisan isolasi internal.
- Kurikulum yang didukung hanya Kurikulum Merdeka dan Kurikulum Berbasis Cinta. Jangan membuat opsi kurikulum lain. Jangan menghapus data historis lama.
- Fase MI: A kelas 1–2; B kelas 3–4; C kelas 5–6.
- Kurikulum harus berversi dan menjaga histori akademik.
- Seed resmi idempotent, tidak menimpa customisasi lokal.
- Master resmi read-only; Admin dapat membuat turunan/custom lokal dengan sumber, alasan, tanggal, dan riwayat.
- Jangan memberi label CP/TP/ATP “resmi” tanpa sumber yang benar-benar menetapkannya.
- Jangan mengarang kode RDM atau struktur mapel/alokasi jam.
- KBC: lima dimensi Panca Cinta dengan nomenklatur proyek:
  1. Cinta Allah dan Rasul-Nya
  2. Cinta Ilmu
  3. Cinta Diri dan Sesama
  4. Cinta Lingkungan
  5. Cinta Tanah Air
- Subtag dapat membedakan Cinta kepada Diri Sendiri dan Cinta kepada Sesama. Tagging multi-tag opsional, dengan justifikasi; tidak ada inferensi otomatis.
- Tagging butir soal/instrumen non-tes adalah fungsi authoring Guru pada tahap Guru kelak. Untuk Web Admin sekarang hanya kelola master resmi/konfigurasi yang menjadi kewenangan Admin; jangan implementasikan authoring Guru.
- Bedakan “mengandung konteks/nilai” dengan “mengukur perilaku/karakter”. Jangan mengklaim pengukuran karakter tanpa instrumen/rubrik tervalidasi.
- DPL 8 dimensi sesuai Permendikdasmen 10/2025. Pertahankan nama/definisi resmi dan sumbernya.
- Android native, Device Lease/Fencing, offline sync, dan Portal Siswa bukan scope pekerjaan ini. Feature flag `CBT_DEVICE_LEASE_ENFORCEMENT` tetap OFF.

## 5. P0 DAN DATABASE — PEMISAHAN KERJA KERAS

P0.1/P0.2/P0.3 adalah pekerjaan audit/remediasi database terpisah. Jangan mengerjakannya sebagai bagian Web Admin. Jangan mengubah migration atau schema untuk “sekalian memperbaiki” tanpa persetujuan terpisah.

Status historis P0.1: BLOCKED — laporan agent menyatakan belum ada patch/backup/test karena workspace canonical tidak ditemukan dan runtime PHP 8.3/Composer/MySQL disposable tidak tersedia. Migration berisiko yang pernah diidentifikasi:
- `2026_11_05_190008_create_question_multi_curriculum_mappings_table.php` — penggunaan `Schema::dropIfExists()` di `up()` perlu remediation terpisah.
- `2026_11_05_200001_fase1c_multi_curriculum_integrity.php` — backfill/provenance/composite FK perlu audit terpisah.
Jangan menyimpulkan kondisi sekarang dari catatan historis; verifikasi source terbaru. Jangan mengubah apa pun dalam P0 tanpa izin baru.

## 6. DEVICE LEASE/FENCING — HOLD

Ada konflik provenance yang sudah dicatat dalam `A00_DEVICE_LEASE_PROVENANCE`: beberapa laporan mengklaim implementasi backend/Android, tetapi source GitHub kandidat yang diaudit tidak memiliki source lease end-to-end; raw workspace/backup lama tidak dapat diverifikasi, dan manifest lama memiliki hash yang berbeda. Karena itu:
- Jangan memulihkan atau membuat ulang source lease berdasarkan laporan/manifest saja.
- Jangan menyebut Device Lease/Fencing siap produksi.
- Jangan mengaktifkan enforcement flag.
- Jangan menyentuh alur attempt/answer/submit/monitoring untuk scope Web Admin kecuali ada dependensi keamanan Admin yang terbukti dan disetujui.
- Uji integrasi, race condition MySQL, multi-device, offline/recovery, dan perangkat Android tetap pekerjaan terpisah.

## 7. LINGKUNGAN DAN KENDALA HISTORIS

Catatan runtime lokal terakhir dari audit terpisah (bukan jaminan kondisi saat agent baru mulai):
- PHP CLI 8.4.24.
- PHP 8.3 tidak tersedia.
- Composer 2 tidak tersedia.
- MySQL tidak tersedia.
- PDO drivers kosong.
- SSH/SCP tidak tersedia.
- Docker/Podman tidak tersedia.
- Nginx binary tersedia tetapi tidak berjalan.
- Karena itu Composer, Laravel boot, PHPUnit, migration, dan staging VPS belum dapat dibuktikan dalam sesi tersebut.

Agent baru wajib melakukan pemeriksaan runtime ulang dan melaporkan hasil aktual. Jangan menjalankan Gradle via terminal; tidak relevan untuk Web Admin.

## 8. DOKUMENTASI DAN CHECKPOINT WAJIB

Simpan semua laporan dalam Bahasa Indonesia pada `docs/`:
- `AUDIT_WEB_ADMIN_*.md`
- `DESAIN_WEB_ADMIN_*.md`
- `IMPLEMENTASI_WEB_ADMIN_*.md`
- `PENGUJIAN_WEB_ADMIN_*.md`
- `CHECKPOINT_WEB_ADMIN_*.md`
- `HANDOVER_AGENT_WEB_ADMIN_*.md`
- `MANIFEST_BACKUP_WEB_ADMIN_*.csv` atau `.md`

Tiap checkpoint wajib mencatat:
1. Tanggal dan zona waktu.
2. Repository, branch, commit SHA, PR terkait.
3. Scope dan tahap.
4. Source yang diperiksa/diubah.
5. Hash source/backup bila ada.
6. Temuan audit dan gap.
7. Desain yang disetujui.
8. Implementasi nyata.
9. Test command, runtime, exit code, hasil dan bukti.
10. Hal yang belum diuji/blocked.
11. Keputusan pengguna yang berlaku.
12. Risiko dan pekerjaan tertunda.
13. Langkah pertama agent berikutnya.
14. Pernyataan eksplisit bahwa tidak ada merge/deployment/production migration kecuali ada bukti persetujuan.

Setiap perpindahan agent wajib membuat handover yang bisa dieksekusi, bukan sekadar ringkasan.

## 9. FORMAT LAPORAN WAJIB

Setiap laporan dipisahkan menjadi:
- **AUDIT:** apa yang benar-benar diperiksa dan temuan.
- **DESAIN:** keputusan rancangan dan alasan.
- **IMPLEMENTASI:** file yang benar-benar berubah, backup, diff, commit.
- **PENGUJIAN:** command yang benar-benar dijalankan dan hasil.
- **STATUS:** DONE / IN PROGRESS / BLOCKED / NOT STARTED.
- **RISIKO/PEKERJAAN BERIKUTNYA.**

Jangan mencampur rencana dengan hasil. Jangan memakai kata “selesai”, “aman”, “siap produksi”, atau “lulus” tanpa bukti yang sesuai.

## 10. LANGKAH PERTAMA AGENT BARU

Lakukan dalam urutan ini:
1. Baca spesifikasi induk Web Admin dan checkpoint ini sampai selesai.
2. Verifikasi HEAD/PR/branch GitHub aktual dan bandingkan dengan catatan historis.
3. Baca seluruh dokumen handover yang ditautkan dari checkpoint, terutama laporan P0 dan provenance Device Lease untuk menghindari konflik.
4. Audit source Web Admin secara read-only pada commit yang benar-benar dipilih.
5. Periksa runtime yang tersedia.
6. Buat matriks fitur Admin dan matriks authorization/security.
7. Serahkan laporan AUDIT + DESAIN kepada pengguna.
8. Berhenti dan tunggu persetujuan sebelum patch source.

**Jangan bertanya ulang keputusan yang sudah ditetapkan di dokumen ini.** Ajukan satu pertanyaan hanya jika ada keputusan baru yang benar-benar belum tersedia dan menghalangi pekerjaan.

**Target akhir:** Web Admin lengkap, aman, ringan, responsif, multi-tenant sesuai model instalasi, teruji, tervalidasi, terdokumentasi, dan disetujui pengguna — tanpa mengganggu portal lain.


## 11. TEMUAN UI WAJIB DITINDAKLANJUTI — SIDEBAR DAN IDENTITAS WEB ADMIN

Pengguna meninjau screenshot dashboard dan menemukan dua masalah nyata pada tampilan yang diberikan:

1. Sidebar kiri berwarna gelap tidak dapat digulir. Akibatnya, menu yang berada di bawah/di luar tinggi viewport tidak dapat dijangkau.
2. Identitas sidebar menampilkan tulisan **“Panel Admin & Guru”**, padahal halaman yang sedang dibahas adalah Web Admin. Ini tidak konsisten dengan pemisahan portal dan kewenangan yang telah ditetapkan.

Temuan ini harus masuk audit UI/UX Web Admin. Screenshot yang dilampirkan pengguna menjadi bukti visual awal; jangan menganggap akar masalah teknis telah diketahui sebelum memeriksa DOM/CSS/layout source aktual.

### 11.1 Perilaku scrolling wajib
- Sidebar desktop harus memiliki tinggi yang mengikuti viewport (misalnya `100dvh` dengan fallback yang sesuai) dan area menu dapat di-scroll vertikal secara independen dari konten utama.
- Jika daftar menu lebih tinggi dari viewport, pengguna tetap dapat mencapai seluruh item sampai menu paling bawah.
- Header/brand sidebar boleh tetap terlihat saat daftar menu bergulir; implementasikan dengan struktur flex yang benar, bukan membuat seluruh sidebar tidak bisa digulir.
- Area konten utama memiliki perilaku scroll yang benar dan tidak mematikan scroll sidebar. Hindari nested scroll yang saling mengunci.
- Pastikan tidak ada elemen transparan/overlay, `overflow: hidden`, tinggi tetap yang keliru, atau parent flex tanpa `min-height: 0` yang menghalangi scroll.
- Scrollbar boleh bergaya minimal, tetapi fungsi scroll harus tetap jelas dan dapat digunakan dengan mouse wheel, touchpad, keyboard, dan layar sentuh.
- Pada layar kecil, sidebar dapat berubah menjadi drawer/off-canvas sesuai desain responsif; menu harus tetap dapat di-scroll dan semua item tetap dapat diakses. Pastikan fokus keyboard dan tombol tutup bekerja.
- Jangan menyelesaikan masalah hanya dengan mengecilkan font/menu atau menghilangkan item navigasi.

### 11.2 Konsistensi identitas dan portal
- Branding sidebar untuk portal ini harus secara konsisten menyebut **“Panel Admin Madrasah”** atau **“Admin Madrasah”**.
- Hapus label gabungan **“Panel Admin & Guru”**, “Admin & Guru”, atau istilah serupa dari shell/layout Web Admin.
- Judul dashboard, badge role, breadcrumb, heading, navigasi, profil, dan teks bantuan harus mencerminkan role/portal yang benar-benar sedang aktif.
- Jangan menampilkan “Dashboard Operator” atau “Role: Operator Madrasah” sebagai identitas Admin jika autentikasi/role halaman adalah Admin Madrasah. Label role harus berasal dari role/permission aktual, bukan hard-coded.
- Jangan menampilkan menu operasional Guru (Bank Soal, Manajemen Ujian, penerbitan ujian, penilaian, dan sebagainya) pada navigasi Admin, kecuali ada elemen informasi agregat eksekutif yang secara eksplisit diizinkan spesifikasi. Jangan memberi kesan Admin dan Guru berada dalam satu portal gabungan.
- Jangan sekadar mengganti teks di screenshot atau membuat mockup. Telusuri dan perbaiki sumber label pada layout, komponen sidebar, konfigurasi menu, route, dan role display.
- Jangan mengubah hak akses backend hanya untuk menyesuaikan tampilan. UI harus mengikuti otorisasi server-side yang benar.

### 11.3 Acceptance criteria khusus
1. Pada viewport desktop 1366×768 dan resolusi lebih pendek, seluruh menu Admin dapat dijangkau dengan scroll sidebar.
2. Scroll konten utama tidak mengunci scroll sidebar dan sebaliknya.
3. Header/brand sidebar tetap terbaca ketika menu digulir.
4. Pada viewport mobile, drawer/sidebar dapat dibuka, digulir, ditutup, dan semua menu dapat dipilih.
5. Tidak ada label “Panel Admin & Guru” pada seluruh shell Web Admin.
6. Tidak ada label “Dashboard Operator” atau role Operator yang salah pada sesi Admin; nama role ditampilkan secara dinamis sesuai role aktual.
7. Navigasi Admin tidak mencampur menu operasional Guru.
8. Uji visual dan interaksi dilakukan pada browser nyata/emulator browser; sertakan screenshot sebelum/sesudah dan catat viewport serta langkah uji.
9. Perbaikan diuji regresi agar tidak merusak navigasi, responsive layout, aksesibilitas keyboard, maupun halaman Admin lainnya.

Temuan ini **belum dinyatakan sudah diperbaiki**. Agent harus mereproduksi dan mengaudit source dahulu, kemudian memasukkannya ke desain dan backlog implementasi. Jangan coding sebelum gerbang persetujuan desain umum yang sudah ditetapkan.


## 12. KLARIFIKASI FINAL ROLE ADMIN = OPERATOR (SATU ROLE, BUKAN DUA)

**Keputusan pengguna yang paling baru dan mengikat:** dalam produk ini, **Admin Madrasah adalah Operator Madrasah**. Keduanya menunjuk satu peran/akun dan satu portal yang sama. Jangan membuat dua role, dua dashboard, dua portal, dua menu, atau dua lapisan permission terpisah bernama Admin dan Operator.

Aturan implementasi:
1. Gunakan satu role kanonis di backend dan satu himpunan permission untuk peran pengelola madrasah tersebut. Audit source untuk mengetahui nama role/enum/permission yang sudah ada; jangan mengganti identifier database atau membuat migration hanya demi menyamakan label.
2. Label UI utama yang disarankan adalah **“Admin Madrasah”** agar konsisten dengan spesifikasi Web Admin. “Operator Madrasah” boleh dipakai sebagai istilah penjelas/alias yang sudah dikenal pengguna, tetapi bukan role kedua.
3. Brand sidebar gunakan **“Panel Admin Madrasah”**. Jangan gunakan “Panel Admin & Guru”.
4. Jangan menampilkan dua menu/dashboard terpisah seperti “Dashboard Admin” dan “Dashboard Operator”. Keduanya satu dashboard yang sama.
5. Bila source lama memiliki role `operator`, `admin`, atau nama permission yang tumpang tindih, jangan langsung membuat role baru atau menghapus role lama. Audit mapping aktual, data pengguna, seeder, policy, middleware, dan route. Usulkan satu pemetaan kanonis yang kompatibel, lalu minta persetujuan bila memerlukan perubahan data/schema/authorization.
6. Badge role boleh menampilkan “Admin Madrasah” atau “Operator Madrasah” sesuai label UI yang diputuskan, tetapi jangan pernah menyiratkan bahwa keduanya dua jenis akun berbeda.
7. Pemisahan yang tetap berlaku adalah **Admin/Operator Madrasah vs Guru**. Portal Guru tetap terpisah secara kewenangan dan navigasi. Penggabungan Admin dengan Operator tidak berarti Admin dan Guru digabung.
8. Revisi requirement sebelumnya yang melarang kata “Operator” pada sesi Admin: larangan itu hanya berlaku untuk label yang menampilkan Operator seolah role/portal kedua atau salah konteks. Karena Admin = Operator, istilah Operator boleh muncul sebagai alias/penjelas, tanpa membuat identitas ganda.

Acceptance criteria:
- Hanya ada satu role/portal pengelola madrasah: Admin Madrasah (Operator).
- Satu akun tidak perlu memilih antara Admin dan Operator.
- Tidak ada duplikasi dashboard, sidebar, menu, atau permission Admin vs Operator.
- Semua route dan action pengelola madrasah memakai authorization kanonis yang sama.
- Guru tetap memiliki portal/role terpisah.
- Migrasi atau konsolidasi role lama hanya dilakukan setelah audit data dan persetujuan eksplisit; tidak boleh merusak akun maupun histori.

## 13. KLARIFIKASI PROKTOR — PENUGASAN GURU OLEH ADMIN MADRASAH

Keputusan pengguna: **Proktor adalah Guru yang ditugaskan oleh Admin/Operator Madrasah untuk menjalankan tugas pengawasan pada ujian tertentu.** Orang yang ditugaskan dapat berasal dari Guru kelas, Wali Kelas, atau Guru Mata Pelajaran.

Ketentuan:
1. Proktor bukan role pegawai permanen yang berdiri sendiri dan bukan portal permanen.
2. Admin/Operator Madrasah memiliki kewenangan mengelola penugasan Proktor untuk ujian sesuai jadwal dan kebutuhan.
3. Calon Proktor harus merupakan akun Guru yang terdaftar/aktif pada madrasah yang sama. Sumber kategori/penugasan Guru dapat berupa Guru kelas, Wali Kelas, maupun Guru Mata Pelajaran; jangan membatasi hanya salah satu kategori.
4. Penugasan harus terikat pada ujian tertentu, sesi/ruang bila relevan, waktu mulai/selesai, dan status penugasan. Simpan siapa Admin yang menugaskan, kapan, serta perubahan/pencabutan penugasan.
5. Hak Proktor bersifat sementara, terbatas pada ujian yang ditugaskan dan tindakan operasional yang memang diizinkan. Penugasan tidak otomatis memberi hak mengelola bank soal, mengubah konfigurasi ujian, menilai, melihat jawaban/nilai di luar kewenangan, atau mengakses ujian lain.
6. Guru yang menjadi Proktor tetap memiliki identitas akun/role Guru; status Proktor adalah assignment/context tambahan, bukan role permanen baru. Setelah penugasan berakhir/dicabut, hak sementara tersebut tidak berlaku lagi.
7. Validasi server-side wajib memeriksa tenant/madrasah, status Guru, ruang lingkup ujian, waktu, status assignment, dan permission setiap aksi. UI menyembunyikan menu saja tidak cukup.
8. Audit log wajib merekam pembuatan, perubahan, pencabutan, mulai/selesai tugas, dan aksi penting Proktor.
9. Jangan membuat portal Proktor permanen atau menambahkan role Proktor global hanya untuk mendukung assignment ini. Bila source lama memiliki role/permission Proktor, audit kompatibilitas dan batasi agar tidak menimbulkan portal/akses permanen.
10. Pada scope Web Admin sekarang, audit dan implementasikan hanya administrasi/penugasan Proktor oleh Admin. Jangan membangun portal atau workflow Guru/Proktor sekarang karena tahapan Guru masih ditahan.

Acceptance criteria:
- Admin dapat memilih Guru kelas, Wali Kelas, atau Guru Mata Pelajaran yang memenuhi syarat sebagai Proktor ujian tertentu.
- Penugasan hanya berlaku untuk ujian dan rentang waktu yang ditentukan.
- Guru yang ditugaskan tidak berubah menjadi role Proktor permanen.
- Guru yang tidak ditugaskan tidak memperoleh akses Proktor untuk ujian tersebut.
- Penugasan lintas madrasah/tenant ditolak.
- Pencabutan/kedaluwarsa penugasan mengakhiri hak sementara.
- Semua perubahan dan tindakan tercatat pada audit log.

## 14. PENEGASAN FINAL — JADWAL PENGAWASAN OTOMATIS MEMBERI STATUS PROKTOR

Klarifikasi terbaru pengguna mengikat dan menyempurnakan bagian 13: **semua Guru dapat menjadi Proktor secara otomatis ketika namanya tercantum pada jadwal pengawasan ujian yang berlaku.** Tidak perlu proses pengangkatan role Proktor, pendaftaran khusus, atau pemilihan kategori Guru secara terpisah.

Aturan yang benar:
1. Semua akun Guru aktif di madrasah merupakan calon pengawas yang dapat dimasukkan ke jadwal. Tidak dibatasi hanya Guru kelas, Wali Kelas, atau Guru Mata Pelajaran; seluruhnya adalah Guru dan dapat mendapat jadwal pengawasan.
2. Admin/Operator Madrasah menyusun/mengelola jadwal pengawasan ujian dan menempatkan Guru pada sesi/ruang/waktu yang diperlukan.
3. Ketika jadwal pengawasan yang sah berlaku dan Guru tercantum sebagai pengawas, sistem otomatis memberikan konteks/hak Proktor sementara untuk tugas tersebut. Guru tidak perlu mengaktifkan role Proktor atau meminta aktivasi manual.
4. Di luar jadwal atau di luar sesi/ruang/ujian yang ditugaskan, Guru tetap hanya memiliki hak dasar Guru dan tidak memperoleh hak Proktor.
5. Hak sementara mengikuti waktu dan status jadwal. Perubahan, penggantian pengawas, pembatalan, atau berakhirnya jadwal harus segera memperbarui hak akses.
6. Assignment harus memeriksa benturan jadwal Guru, status akun, tenant/madrasah, sesi, ruang, dan periode. Cegah jadwal tumpang tindih yang tidak diizinkan.
7. Role dasar tetap Guru. Status Proktor diturunkan dari jadwal pengawasan aktif (scheduled duty), bukan role permanen atau role global baru.
8. Jangan buat workflow “promosikan Guru menjadi Proktor”, form aktivasi Proktor manual, daftar role Proktor permanen, atau portal Proktor terpisah.
9. Pada Web Admin saat ini, implementasikan/audit pengelolaan jadwal pengawasan oleh Admin/Operator. Workflow tampilan Guru/Proktor tetap menunggu tahap Guru.
10. Server-side authorization harus memeriksa jadwal pengawasan yang berlaku pada setiap aksi Proktor. Jangan percaya status dari client atau label UI saja.

Acceptance criteria:
- Semua Guru aktif dapat dipilih dalam jadwal pengawasan tanpa klasifikasi jabatan khusus.
- Guru yang tercantum pada jadwal aktif otomatis memperoleh hak Proktor terbatas sesuai tugasnya.
- Guru yang tidak tercantum, jadwal belum berlaku, sudah berakhir/dibatalkan, atau ditugaskan pada sesi lain tidak memperoleh hak tersebut.
- Perubahan/penggantian jadwal langsung mengubah hak sementara secara konsisten.
- Tidak ada role Proktor permanen atau proses aktivasi manual.
- Konflik jadwal, tenant isolation, waktu server, dan audit log diuji.
