# Agent 2 — Scope Web-only dan Batas Instalasi per Madrasah

Tanggal pemeriksaan: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4d-account-lifecycle-20261122
HEAD Agent 2 sebelum dokumentasi ini: 7d63ea3b70b5191530c2c92c5c86bf92d7324db6
Source runtime yang diperiksa: 74bf7178f46673e9be90523671490c0749034acd

## AUDIT

### Scope produk yang mengikat

Sampai Juni 2027, implementasi dibatasi pada:
1. Web Admin Madrasah.
2. Web Guru.
3. Web Siswa.

Ditunda sampai Juni 2027:
- Web Super Admin dan dashboard/control plane.
- Android native, Android Studio, Kotlin/Compose/Room/WorkManager/Retrofit.
- Android-specific client, device integration, APK/AAB dan Android release pipeline.

Tidak ada pekerjaan baru untuk dua produk yang ditunda. Backend API yang memang digunakan Web Guru/Web Siswa tetap dipertahankan dan dikerjakan sesuai kontrak Web CBT.

### Bukti source Web

- routes/web.php blob f012b3de23575e3ef91c8f07ad1c0d7ae780fb21 memuat login Web, portal Admin, route Guru, dan route Siswa.
- Route Siswa memakai StudentExamController untuk daftar ujian, detail ujian, dan hasil.
- resources/views/student/exams/show.blade.php blob 7283ca553d787f4505299a306e2bd487884622d3 memanggil API ujian, media, simpan jawaban, dan submit. Browser Web Siswa menggunakan API CBT; API tersebut bukan boleh dihapus hanya karena Android ditunda.
- routes/api.php blob 2e43c37dd669a26446a44b818c1d2c8420b15aca memuat endpoint curriculum, bank soal, exam builder, monitoring, attempt, answer, scoring, reporting, dan analytics.
- Web Student runner membuat device binding pada session browser dan mengirim X-Device-Binding-ID. Ini adalah alur Web yang harus tetap diaudit; bukan bukti integrasi Android.

API login Sanctum di /api/v1/auth/login belum boleh diasumsikan khusus Android atau dihapus. Lakukan inventory seluruh pemanggil/call-site lebih dahulu. Jangan menambah API Android baru.

### Topologi instalasi

Keputusan arsitektur:
**Satu ZIP distribusi source Web CBT dipasang secara terpisah pada instalasi setiap madrasah. Satu instalasi = satu madrasah = satu database akademik.**

Setiap instalasi memiliki sendiri:
- source/runtime dan konfigurasi domain;
- database akademik dan kredensial database;
- storage/file;
- cache/queue namespace jika digunakan;
- APP_KEY, session, log dan secret;
- backup database dan file;
- identitas, branding, Guru, Siswa, Admin, bank soal, ujian, jawaban, nilai, laporan dan arsip.

Dilarang shared academic database, cross-database academic query, sinkronisasi bank soal antarmadrasah, central academic warehouse, atau pengiriman data akademik ke publisher.

tenant_id tetap menjadi pertahanan berlapis dalam instalasi. tenant_id bukan alasan untuk menggabungkan database madrasah.

### Control plane masa depan

Setelah Juni 2027 dan hanya setelah persetujuan scope tersendiri, Super Admin/publisher boleh mengelola metadata lisensi, masa berlaku, edition/fitur, batas lisensi, invoice, pembayaran, status layanan dan pembaruan yang diotorisasi.

Control plane tidak boleh membaca atau menerima bank soal, identitas Guru/Siswa, peserta, token, Attempt, Answer, Score, hasil belajar, file, backup akademik, jadwal CBT, monitoring ujian, laporan atau analytics akademik. Tidak boleh ada telemetry akademik.

Verifikasi lisensi harus dapat dilakukan lokal. Private signing key penerbit tidak boleh berada pada instalasi madrasah. Detail implementasi control plane tetap ditunda.

## DESAIN

1. Satu instalasi produksi hanya mempunyai satu tenant aktif.
2. Tenant lokal ditetapkan dari identitas instalasi tepercaya, bukan input browser.
3. Host/domain diverifikasi terhadap konfigurasi lokal.
4. User terautentikasi harus memiliki tenant_id yang sama dengan instalasi.
5. Browser tidak boleh memilih tenant melalui header, query, atau body.
6. Seluruh model, policy, service, file access, job dan export tetap tenant-scoped.
7. Tidak ada portal Proktor permanen; Proktor adalah penugasan operasional sementara kepada Guru.
8. KAMAD adalah identitas/jabatan resmi, bukan portal login.
9. Admin Madrasah menjalankan administrasi dan master data; tidak otomatis memperoleh hak operasi CBT, jawaban, kunci, Attempt, Score, atau monitoring ujian.

## IMPLEMENTASI

Dokumen baru. Tidak ada source runtime, route, policy, migration, schema, database, konfigurasi, workflow, branch Agent 1/3/4, atau deployment yang diubah. Tidak ada file lama yang ditimpa.

## PENGUJIAN

Inspeksi statis dilakukan pada route Web/API dan runner Web Siswa. Tidak ada PHPUnit, browser E2E, MySQL disposable, multi-installation test, deployment, atau UAT dijalankan pada tahap ini.

## OWNER DAN NEXT ACTION

Owner backend boundary: Agent 2.
Owner Web Guru: Agent 3.
Owner Web Siswa: belum dikonfirmasi secara resmi; branch kandidat Agent 4 belum boleh diambil alih.
Owner room/session/proctor: Agent 1.

Lanjutkan inventory API call-site, host/domain trust, tenant identity, storage/cache/queue isolation, serta negative tests sebelum implementasi.