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
