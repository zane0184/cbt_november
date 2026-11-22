# MASTER SPESIFIKASI WEB ADMIN MADRASAH CBT
Tanggal: 22 November 2026  
Status: **ARAHAN PRODUK WAJIB — ACUAN AGENT WEB/ BACKEND**  
Repository: `zane0184/cbt_november`  
Berlaku untuk: Web CBT saat ini

> Dokumen ini adalah acuan fitur dan batas kewenangan Web Admin Madrasah. Agent wajib membaca seluruh dokumen ini sebelum audit, desain, atau implementasi. Jangan menganggap fitur selesai hanya karena menu/tombol sudah tampil. Setiap fitur harus memiliki alur data, validasi backend, otorisasi, audit trail bila relevan, dan pengujian.

## 0. KEPUTUSAN SCOPE TERKINI — WAJIB DIDAHULUKAN

Keputusan eksplisit pengguna yang berlaku saat ini dan mengesampingkan rancangan lama yang bertentangan:

1. Fokus sekarang **WEB CBT ONLY**.
2. Tiga portal aktif: **Admin Madrasah, Guru, Siswa**.
3. Android dan Web Super Admin ditunda sampai **Juni 2027**. Jangan menjadikannya dependency atau blocker pekerjaan Web saat ini.
4. Tidak ada portal/akun/role Operator. Seluruh fungsi administrasi Operator digabung ke Admin Madrasah.
5. Tidak ada portal/akun/role Kamad di CBT. Nama kepala madrasah boleh menjadi data identitas dokumen, bukan akun/role CBT.
6. Tidak ada role/portal Proktor permanen. Proktor adalah Guru yang mendapat penugasan operasional sementara untuk Exam dan Room tertentu.
7. Siswa hanya peserta ujian, bukan Proktor.
8. Setiap Madrasah memiliki **instalasi aplikasi sendiri dan database akademik sendiri**. Tidak ada database akademik bersama lintas madrasah. `tenant_id` tetap dipertahankan sebagai defense-in-depth dalam instalasi.
9. Isolasi instalasi meliputi DB credentials/config, domain, session, cache, queue, storage, logs, exports, temporary files, dan backup.
10. VPS sudah disiapkan, aaPanel terpasang, Laravel dan environment pendukung sudah tersedia. Domain `https://cbt.min15jakarta.sch.id/login` sudah tampil normal menurut pemeriksaan pengguna. Database/aplikasi masih kosong. Pengguna tidak memiliki akses SSH; proses instalasi harus dapat dilakukan melalui aaPanel File Manager, upload/extract ZIP, pembuatan database via aaPanel, dan konfigurasi yang didokumentasikan. Jangan mengulang instruksi instalasi VPS dari nol seolah belum tersedia. Jangan minta password/secret di chat.
11. Ujian memakai **MODEL SERENTAK**. Jadwal resmi menggunakan `exam.start_at` dan `exam.end_at`. Tidak ada gelombang atau sesi operasional ujian.
12. **KOREKSI PENTING TERHADAP ISSUE #10 DAN DOKUMEN LAMA:** Issue #10 saat ini masih memuat kontrak satu Exam–satu ExamSession. Kontrak tersebut sudah tidak berlaku untuk scope saat ini dan tidak boleh dijadikan arahan implementasi. Jangan membuat `ExamSession`, `SessionRoom`, session_number, gelombang, atau session lifecycle sebagai model operasional ujian. Room hanya untuk penempatan peserta dan lingkup monitoring Proktor; Room bukan admission gate untuk start/resume Attempt. Jangan menghapus tabel/migrasi historis secara membabi buta; audit source dan tentukan strategi migrasi fresh-install yang aman.
13. Token otomatis dibuat setelah Guru menerbitkan ujian. Token dapat dilihat sebelum jadwal; Siswa hanya dapat mulai sesuai jadwal dan waktu server.
14. Kamera bukan syarat CBT standar.
15. Browser bukan kiosk dan tidak boleh diklaim mencegah screenshot/Home/app switching.
16. Jangan menyebut Device Lease/fencing siap produksi sebelum integrasi end-to-end dan uji multi-device/concurrency pada MySQL 8/InnoDB lulus.

## 1. PRINSIP UMUM IMPLEMENTASI

- Backend Laravel 13, PHP 8.3, MySQL 8+, Blade dan Livewire.
- Modular monolith.
- Server time adalah acuan resmi ujian.
- Pisahkan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN.
- Jangan mengklaim fitur lulus tanpa bukti test yang benar-benar dijalankan.
- Jangan menghapus/menimpa source asli. Backup setiap file sebelum perubahan dan catat lokasi/hash backup.
- Jangan jalankan migration eksperimental pada database produksi. Database VPS saat ini dilaporkan kosong dan fresh install diperbolehkan, tetapi verifikasi keadaan aktual dahulu.
- Jangan menjalankan Gradle via terminal; Android sedang di luar scope.
- Jangan mengubah ZIP baseline asli.
- Semua otorisasi harus diperiksa di backend: tenant/instalasi, role, permission, ownership, penugasan, kelas, mapel, Exam, Room, dan status.
- Menyembunyikan tombol di frontend bukan kontrol keamanan.
- Gunakan transaksi, idempotensi, constraint, dan lock yang konsisten untuk mencegah duplikasi/race condition.
- Histori akademik dan histori tindakan harus dipertahankan; utamakan archive/soft-delete/RESTRICT sesuai relasi, bukan hard delete sembarangan.
- Desain UI premium tetapi ringan, responsif desktop/tablet/mobile; halaman ujian Siswa tetap fokus dan minimal.
- Tema Light/Dark/System konsisten dengan semantic design tokens dan target kontras WCAG 2.2 AA.
- Dukungan kurikulum harus berversi dan histori akademik tidak boleh hilang.

## 2. STRUKTUR MENU WEB ADMIN MADRASAH — TARGET FITUR

Nama menu boleh mengikuti pola UI yang sudah ada, tetapi fungsi berikut tidak boleh dihilangkan.

### 2.1 Dashboard
- Ringkasan jumlah siswa aktif, Guru aktif, rombel, kelas, mapel.
- Ringkasan ujian draft/terbit/terjadwal/berlangsung/selesai.
- Jumlah peserta per status: belum mulai, sedang mengerjakan, sudah submit, bermasalah.
- Jadwal ujian terdekat dan ujian berjalan.
- Aktivitas terbaru: import, perubahan data, penugasan, penerbitan ujian, perubahan jadwal, tindakan operasional.
- Peringatan data master belum lengkap.
- Semua angka harus berasal dari DB aktual, difilter berdasarkan tahun pelajaran dan instalasi; bukan angka dummy.
- Dashboard monitoring tidak otomatis membuka isi jawaban/kunci.

### 2.2 Data Madrasah
#### Profil
- Nama resmi dan nama singkat madrasah.
- NSM, NPSN, jenjang, status negeri/swasta.
- Alamat, kelurahan/desa, kecamatan, kabupaten/kota, provinsi, kode pos.
- Telepon, email resmi, website.
- Nama kepala madrasah sebagai data identitas dokumen saja.
- Data tambahan bila dibutuhkan.
- Validasi format dan field.

#### Logo dan identitas visual
- Upload, preview, ganti, hapus dengan konfirmasi.
- Logo header aplikasi, logo kop dokumen, favicon, logo halaman login.
- PNG/JPG/JPEG; SVG hanya jika disanitasi dengan benar.
- Batasi MIME, ukuran file, dimensi; pertahankan rasio aspek dan jangan distorsi.
- Branding hanya untuk instalasi madrasah tersebut.

#### Kop surat/kepala dokumen
- Logo kiri/kanan jika diperlukan.
- Nama instansi/kementerian dan nama madrasah.
- Alamat, kontak, website.
- Teks tambahan, garis pembatas, posisi/ukuran logo, alignment, jarak dan margin.
- Preview.
- Dipakai pada daftar peserta, daftar hadir, berita acara, rekap nilai, laporan ujian dan dokumen cetak yang tersedia.
- Jangan hardcode identitas madrasah ke template/source.

#### Tampilan/branding
- Nama aplikasi, judul/deskripsi login, pesan sambutan, warna identitas, favicon, footer, website/media sosial/kontak bantuan.
- Semua dapat diatur tanpa mengubah source.

### 2.3 Data Pengguna
#### Guru
- Daftar, tambah, edit, detail, aktif/nonaktif, arsip sesuai kebijakan, reset password, username/email/telepon, riwayat perubahan, riwayat penugasan mengajar, riwayat penugasan Proktor.
- Import, export, unduh template.
- Data: nama, NIP (opsional), NUPTK (opsional), NIK hanya bila diperlukan/dilindungi, jenis kelamin, email, telepon, alamat, status kepegawaian, keterangan tugas, status akun.
- Jangan mewajibkan NIP bagi guru yang tidak memilikinya.

#### Siswa
- Daftar, tambah, edit, detail, pindah rombel, naik kelas, lulus, nonaktif, arsip, reset password, akun login, histori penempatan.
- Import/export dan template.
- Data: nama, NIS, NISN, NIK hanya bila perlu, jenis kelamin, tempat/tanggal lahir, orang tua/wali bila dibutuhkan, alamat, rombel, tahun masuk, status siswa, username/status akun.
- Jangan hilangkan histori ketika pindah rombel, naik kelas, lulus, atau nonaktif.

#### Akun dan akses
- Admin mengelola akun Guru/Siswa sesuai kewenangan.
- Buat/aktifkan/nonaktifkan akun, reset password, username, paksa ganti password saat login pertama jika kebijakan dipakai, lihat status akun/login terakhir, revoke sesi bila tersedia dan berotorisasi.
- Admin tidak boleh membuat/mengubah dirinya menjadi Super Admin.
- Jangan buat role Operator, Kamad, atau Proktor permanen.

### 2.4 Import dan Export Guru/Siswa — FITUR WAJIB DETAIL
#### Template
- Template Excel .xlsx terpisah untuk Guru dan Siswa; CSV bila didukung.
- Petunjuk pengisian, contoh baris, kolom wajib/opsional, format tanggal, nilai enum yang diterima.
#### Alur
1. Unduh template.
2. Upload.
3. Validasi ekstensi, MIME, ukuran, struktur/header, jumlah baris.
4. Pemetaan kolom; beri tahu header tak dikenal atau kolom wajib hilang.
5. Preview sebelum commit.
6. Tampilkan valid, invalid, duplicate, conflict, dan alasan per baris.
7. Konfirmasi eksplisit Admin.
8. Simpan dengan transaksi/batch aman dan hasil kegagalan yang jelas; tidak boleh meninggalkan partial import tanpa status.
9. Laporan hasil: berhasil, dilewati, gagal; unduh daftar kesalahan.
#### Validasi Siswa
- NIS unik sesuai lingkup instalasi/madrasah.
- NISN sesuai format yang berlaku.
- Nama wajib; rombel harus ada/aktif; tahun pelajaran jelas; jenis kelamin dan tanggal valid.
- Import ulang tidak membuat siswa/akun ganda.
- Data yang sudah ada dan berbeda ditampilkan sebagai calon update, jangan ditimpa diam-diam.
#### Validasi Guru
- Identitas dicocokkan dengan data yang ada.
- NIP/NUPTK unik bila diisi.
- Username unik dalam instalasi; email divalidasi.
- Status kepegawaian sesuai enum.
- Import ulang tidak membuat guru/akun ganda.
#### Kebijakan konflik
- Duplikat identitas ditolak atau ditampilkan sebagai konflik.
- Data ambigu tidak boleh ditebak.
- Import file yang sama harus idempotent.
- Jangan menimpa data existing tanpa preview dan persetujuan.
- Jangan merusak histori tahun/rombel.
#### Export
- Export data Guru, Siswa, siswa per kelas/rombel/tahun, status aktif/nonaktif.
- Tidak pernah menyertakan password.
- Otorisasi, filter, audit export dan proteksi data pribadi wajib.

### 2.5 Data Akademik
#### Tahun Pelajaran
- Tambah/edit, tetapkan aktif, arsip, histori, semester bila digunakan.
- Satu tahun pelajaran aktif per instalasi kecuali desain eksplisit mengatur lain.
- Pergantian tahun tidak menghapus histori lama.
#### Kelas
- Jenjang MI kelas 1–6; tambah/edit/aktif-nonaktif/urutan.
#### Rombel
- Buat/edit, nama (contoh 1A), tahun pelajaran, tingkat kelas, wali kelas bila dipakai.
- Tambah/pindah/keluarkan siswa dengan alasan, import penempatan, export, histori.
- Cegah satu siswa memiliki dua penempatan rombel aktif pada tahun yang sama jika aturan akademik tidak mengizinkan.
#### Mata Pelajaran
- Tambah/edit/kode/kelompok/tingkat kelas/status dan relasi kurikulum/versi.
#### Penugasan Guru
- Assign Guru ke mapel + kelas/rombel + tahun pelajaran.
- Ubah/cabut dengan histori; daftar lingkup tugas.
- Guru hanya dapat mengelola bank soal/ujian sesuai penugasan. Tidak boleh akses kelas/mapel Guru lain tanpa kewenangan.

### 2.6 Kurikulum dan Pembelajaran
- Dukung Kurikulum Merdeka.
- Siapkan integrasi Kurikulum Berbasis Cinta/Panca Cinta, Pembelajaran Mendalam, dan kurikulum masa depan sesuai keputusan produk.
- Data berversi: kurikulum, versi, tahun berlaku, struktur, fase, kelas, mapel, CP, TP, ATP, elemen/lingkup materi bila perlu, pemetaan antarversi.
- Registrasi kurikulum pada madrasah/tahun pelajaran.
- Riwayat registrasi dan pemetaan tidak hilang saat kurikulum berganti.
- Exam lama tetap mengacu pada konteks kurikulum/snapshot ketika dibuat/diterbitkan.
- Admin mengelola struktur dan penerapan; Guru menggunakannya saat membuat materi, bank soal, asesmen dan ujian.

### 2.7 Manajemen Ujian
#### Daftar Ujian
- Admin melihat nama, mapel, Guru pembuat, kelas/rombel, tahun pelajaran, start_at/end_at, durasi, status, jumlah peserta dan progres.
- Admin tidak otomatis berhak mengubah soal, kunci, atau konfigurasi akademik ujian milik Guru.
#### Ruang
- CRUD/aktif-nonaktif ruang, kapasitas, penempatan peserta, daftar peserta, Guru Proktor, histori placement.
- Room hanya untuk placement dan monitoring scope. Room BUKAN admission gate start/resume Attempt.
#### Penugasan Proktor
- Admin memilih Exam, Room, Guru, starts_at/ends_at, alasan.
- Catat assigned_by, revoked_by, reason, status, interval dan audit.
- Interval `[starts_at, ends_at)`; 07:00–09:00 dan 09:00–11:00 tidak overlap.
- Cegah overlap penugasan Guru untuk Exam/Room yang konflik, termasuk concurrent requests; serialisasi/constraint yang benar.
- Tidak ada role permanen Proktor.
- Proktor hanya melihat minimum identity, attendance/status/progress peserta di Room yang ditugaskan.
- Tidak boleh melihat soal, kunci, jawaban, bank soal, skor atau konfigurasi Exam hanya karena menjadi Proktor.
- Tidak boleh Force Submit/Reset Attempt/akses Room lain kecuali ada otorisasi tambahan terpisah.
#### Mode A — Guru mandiri
- Guru membuat/menerbitkan/mengelola ujian sendiri dalam lingkup kelas/mapel yang ditugaskan.
- Tidak perlu penugasan Proktor Admin.
- Tidak boleh melihat/mengelola Exam atau peserta Guru lain.
#### Mode B — Ujian terpusat
- Admin assign Guru sebagai Proktor untuk Exam + Room.
- Cross-class hanya untuk official roster/room placement oleh Admin.
- Proktor terbatas pada Room yang ditugaskan.
#### Model serentak
- Admission memakai Exam schedule, Exam token, server time.
- Token otomatis setelah Guru publish dan terlihat sebelum jadwal.
- Siswa tidak dapat start sebelum jadwal.
- Tidak ada wave/session lifecycle/ExamSession/SessionRoom/session_number operasional.
- Room tidak mengendalikan admission atau resume.

### 2.8 Monitoring CBT
- Daftar Exam berlangsung, Guru, mapel, kelas/rombel, Room, jumlah peserta.
- Status belum hadir/belum mulai/mengerjakan/submit/bermasalah.
- Detail minimum peserta: nama/identitas minimum, kelas, Room, attendance, Attempt status, started_at, sisa waktu dari server, submit status, device/lease status jika terbukti terintegrasi.
- Monitoring Admin dan Proktor harus berbeda scope.
- Monitoring tidak otomatis memberi akses isi jawaban/kunci.
- Tindakan sensitif: Reset Attempt, Force Submit, perpanjangan waktu, release/takeover perangkat hanya dengan permission, reason wajib, transaksi atomik dan audit event.
- Device Lease/fencing tidak boleh disebut production-ready sebelum E2E + multi-device + race tests MySQL 8/InnoDB lulus.

### 2.9 Laporan
- Akademik: rekap siswa/kelas/rombel, Guru, mapel, penugasan, tahun pelajaran.
- Ujian: daftar Exam, peserta, daftar hadir, belum mulai, sedang mengerjakan, selesai, per mapel/kelas/tahun.
- Hasil: nilai per siswa/kelas/mapel/ujian, distribusi, histori, status penilaian, essay belum dinilai bila sesuai kewenangan.
- Essay dinilai Guru/pemeriksa yang berwenang; Admin tidak otomatis menjadi penilai.
- Preview, print dengan kop, PDF, Excel, filter tahun/kelas/rombel/mapel/Exam/status.
- Export mengikuti authorization dan tercatat dalam audit.

### 2.10 Audit dan Keamanan
- Catat login sukses/gagal, logout, reset password, CRUD Guru/Siswa, import/export, perubahan tahun/rombel/penugasan, identitas madrasah, publish Exam, perubahan jadwal, Proktor assignment/revoke, Reset/Force Submit/time extension/device actions dan pengaturan.
- Log: actor, timestamp, action, object, outcome, reason untuk tindakan sensitif.
- Jangan log password/token/secret.
- Tampilkan akses ditolak, percobaan login gagal, status akun/sesi dan riwayat perubahan.
- Semua API, Livewire action, export/import, search/detail/update harus validasi instalasi/tenant, role, permission, ownership, teaching assignment, class/subject, Exam, Room, Proktor assignment, schedule/status.
- Defense-in-depth `tenant_id` tetap ada walau DB per instalasi.

### 2.11 Pengaturan Web
#### Umum
- Nama aplikasi, nama madrasah, deskripsi, zona waktu, format tanggal/waktu/angka, bahasa, tahun pelajaran aktif, preferensi.
- Timezone UI harus konsisten; server time tetap acuan resmi Exam.
#### Branding
- Logo aplikasi, logo kop, favicon, warna primer/sekunder, login title/description/welcome, footer, website/social/contact.
#### Kop/cetak
- Instansi, logo, alamat, kontak, website, garis kop, margin/header/footer, nomor halaman, format tanggal dan preview.
#### Akun
- Profil Admin, ubah password sendiri, preferensi, notifikasi, sesi sesuai kemampuan.
#### Tampilan
- Light/Dark/System, responsive desktop/tablet/mobile, sidebar collapse, typography, contrast WCAG 2.2 AA.
#### Operasional
- Hanya pengaturan yang menjadi kewenangan Admin. DB credentials, private key, deployment secrets, server config bukan form bebas Admin.

## 3. HAK AKSES — RINGKASAN

### Admin Madrasah
- Administrasi madrasah, identitas, branding, data Guru/Siswa, akun, tahun pelajaran, kelas, rombel, mapel, penugasan Guru, kurikulum, ruang, roster/placement, Proktor assignment, monitoring dan laporan sesuai policy.
- Tidak menjadi Super Admin, tidak memiliki role Kamad/Operator/Proktor permanen.
- Tidak otomatis memiliki hak mengedit soal/kunci/isi jawaban atau menilai essay Guru.

### Guru
- Bank soal, asesmen, membuat/mengatur/menerbitkan ujian tanpa persetujuan Admin selama dalam lingkup penugasan.
- Mengelola peserta sesuai kelas/mapel yang ditugaskan.
- Mode A mengelola ujian sendiri; Mode B dapat menjadi Proktor bila ditugaskan Admin.
- Menu Proktor adalah menu operasional kondisional pada akun Guru, bukan portal/role permanen.
- Tidak dapat mengakses Exam/kelas/mapel Guru lain tanpa penugasan.

### Siswa
- Hanya peserta ujian.
- Melihat ujian yang tersedia, memasukkan token, mulai sesuai jadwal server, mengerjakan, menyimpan, resume sesuai eligibility, submit, melihat hasil hanya bila diizinkan.
- Tidak punya akses administrasi atau Proktor.

## 4. HAL YANG TIDAK BOLEH DIBUAT / DIHIDUPKAN KEMBALI

- Portal/role Operator.
- Portal/role Kamad.
- Portal/role Proktor permanen.
- Proktor dari akun Siswa.
- Web Super Admin sebagai dependency pekerjaan sekarang.
- Android sebagai dependency pekerjaan sekarang.
- ExamSession, SessionRoom, session_number, wave/session lifecycle operasional.
- Room sebagai syarat start/resume Attempt.
- Kamera wajib untuk CBT standar.
- Klaim browser sebagai kiosk.
- Klaim Device Lease/fencing production-ready tanpa bukti integrasi dan multi-device test.
- Auto-overwrite data import.
- Export password atau secret.
- Akses lintas Guru/kelas/Room hanya karena tombol disembunyikan di UI.

## 5. ACCEPTANCE GATE — MENU BELUM BERARTI FITUR SELESAI

Setiap fitur/menu wajib memenuhi:
1. UI dapat digunakan dan responsif.
2. Route/controller/Livewire action benar dan terhubung ke service/model.
3. Server-side validation lengkap.
4. Authorization/policy diuji untuk allow dan deny.
5. Isolasi instalasi/tenant diuji.
6. Transaksi, idempotensi, unique constraint dan concurrency diuji bila relevan.
7. Audit event tersedia untuk perubahan/tindakan sensitif.
8. Error handling dan empty/loading states jelas.
9. Import/export diuji dengan file valid, invalid, duplikat, besar, dan import ulang.
10. Test dijalankan dan hasilnya dilaporkan dengan command, jumlah test/assertion, DB/version, dan commit SHA.
11. Browser E2E/UAT dilakukan untuk alur kritikal.
12. Tidak boleh menandai selesai hanya berdasarkan pemeriksaan statis atau tampilan UI.

## 6. PROTOKOL KERJA AGENT

Sebelum coding:
1. Baca dokumen ini seluruhnya.
2. Periksa branch, HEAD, status working tree, CI, issue, dan source aktual.
3. Baca checkpoint/laporan terbaru; bandingkan klaim lama dengan source aktual.
4. Petakan menu yang sudah ada, menu yang belum ada, fitur parsial, dead route, dan permission gaps.
5. Buat matriks: Menu → Submenu → Route/Livewire → Model/Service → Policy → Test → Status.
6. Tulis AUDIT dan DESAIN sebelum implementasi.
7. Tetapkan ownership file antar-agent; hindari konflik pada routes/layout/shared components.
8. Jangan mengubah source di luar scope tanpa persetujuan.

Saat implementasi:
1. Backup setiap file sebelum edit.
2. Implementasikan backend + UI + policy + audit + tests, bukan UI saja.
3. Jangan menimpa perubahan agent lain.
4. Jangan migration pada DB produksi.
5. Jika schema berubah, jelaskan migration fresh-install dan upgrade path; uji pada disposable MySQL 8/InnoDB.
6. Jangan merge/rebase/cherry-pick atau deployment tanpa persetujuan pengguna yang eksplisit.

Laporan setiap tahap wajib:
- **AUDIT:** temuan source aktual.
- **DESAIN:** rancangan dan keputusan.
- **IMPLEMENTASI:** file berubah, backup, commit/branch.
- **PENGUJIAN:** tes yang benar-benar dijalankan, hasil dan batasan.
- **CHECKPOINT:** selesai, tertunda, blocker, next step.

## 7. PRIORITAS PELAKSANAAN

P0 — Fondasi operasional:
- Identitas madrasah, logo, kop, branding dan pengaturan.
- Data Guru/Siswa CRUD dan import/export aman.
- Tahun pelajaran, kelas, rombel, mapel, penugasan Guru.
- Auth/RBAC/tenant isolation.
- Exam serentak tanpa Session/Wave.
- Roster/Room dan Proktor assignment yang benar.
- Monitoring minimum dan audit log.

P1 — Alur pembelajaran/ujian lengkap:
- Kurikulum versioning dan integrasi.
- Bank soal, media, Arabic RTL/harakat, campuran Arab-Indonesia, equation support.
- Assessment/Exam builder, immutable snapshot, token, jadwal, participant mapping.
- Attempt, Answer, Score, submit/resume/recovery yang konsisten.
- Essay manual grading dan laporan.

P2 — Penyempurnaan:
- Analytics lanjutan.
- UX, aksesibilitas, print/export lebih lengkap.
- Pengaturan operasional tambahan yang aman.
- UAT lintas peran dan validasi madrasah.

Urutan prioritas boleh disesuaikan berdasarkan audit source dan kebutuhan operasional mendesak, tetapi tidak boleh menghapus fitur atau melanggar keputusan scope.

## 8. KONDISI VPS DAN DISTRIBUSI

- VPS aaPanel sudah tersedia menurut pengguna; domain `https://cbt.min15jakarta.sch.id/login` tampil normal.
- Database/aplikasi saat ini dilaporkan kosong; pengguna mengizinkan fresh install/reinstall.
- Pengguna tidak memiliki SSH. Siapkan panduan aaPanel File Manager: backup salinan deployment saat ini, upload ZIP, extract, create database/user via aaPanel, konfigurasi .env tanpa membagikan secret di chat, set document root/public, permissions, storage link/cron/queue sesuai fitur, lalu smoke test.
- Jangan mengklaim status VPS atau deployment yang belum dibuktikan.
- Distribusi final satu ZIP lengkap untuk satu instalasi madrasah, mempertahankan struktur proyek, tanpa backup internal/temp files; README harus menjelaskan extract, penempatan .env dari pemilik, setup aaPanel, DB, dan smoke test.
- Jangan menyebut siap didistribusikan sebelum struktur, konfigurasi, kelengkapan, ekstraksi, dan test diverifikasi.

## 9. CATATAN KHUSUS UNTUK ISSUE #10

Issue #10 saat ini memiliki judul dan body lama tentang satu Exam–satu ExamSession. Arah tersebut bertentangan dengan keputusan pengguna terbaru tentang ujian serentak tanpa Session/Wave. Dokumen ini mengoreksi dan menggantikan arahan lama tersebut untuk scope saat ini. Agent harus memperbarui pemahaman issue #10; jangan menggunakan body lama sebagai spesifikasi implementasi. Jangan menghapus tabel/migrasi historis tanpa audit dan rencana fresh-install/upgrade yang aman.

## 10. INSTRUKSI PERTAMA BAGI AGENT PENGGANTI

Balas dengan konfirmasi bahwa dokumen telah dibaca, lalu:
1. Periksa repo, branch, HEAD, CI, issue dan seluruh file terkait Web Admin/Guru/Siswa.
2. Buat matriks fitur/menu berdasarkan dokumen ini dengan status: ADA & TERUJI / ADA BELUM TERUJI / PARSIAL / BELUM ADA / BERTENTANGAN.
3. Identifikasi konflik antara source saat ini dan keputusan scope, terutama Session/Wave, role Operator/Kamad/Proktor, dan akses Admin.
4. Jangan langsung melakukan rewrite besar. Sampaikan audit dan rencana file ownership terlebih dahulu.
5. Koordinasikan empat agent agar tidak saling menimpa route, layout, policy, model, migration, dan service.
6. Jangan meminta pengguna mengulang keputusan yang tercantum di sini.
