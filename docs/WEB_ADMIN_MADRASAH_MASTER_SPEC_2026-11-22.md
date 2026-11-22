# MASTER SPESIFIKASI WEB ADMIN MADRASAH CBT
**Tanggal:** 22 November 2026  
**Repository:** `zane0184/cbt_november`  
**Status dokumen:** Acuan wajib audit dan implementasi Web Admin Madrasah.  
**Prioritas:** Tinggi — jangan mengubah arah proyek tanpa keputusan pengguna.

> Dokumen ini adalah spesifikasi target dan daftar audit. Keberadaan fitur dalam dokumen ini **tidak berarti fitur tersebut sudah tersedia atau sudah lulus pengujian**. Agent wajib memeriksa source aktual, membandingkannya dengan spesifikasi, lalu melaporkan status setiap fitur: ADA & TERVERIFIKASI / ADA TAPI BELUM LENGKAP / BELUM ADA / BLOCKED.

## 0. KEPUTUSAN PROYEK YANG TIDAK BOLEH DILANGGAR

1. Fokus sekarang **WEB CBT saja**. Portal aktif: **Admin Madrasah, Guru, Siswa**.
2. Android dan Web Super Admin ditunda sampai **Juni 2027**. Jangan menjadikannya blocker rilis Web saat ini.
3. Tidak ada portal/akun/role Operator. Semua fungsi administrasi Operator digabung ke **Admin Madrasah**.
4. Tidak ada portal/akun/role Kamad dalam CBT. Nama Kepala Madrasah boleh menjadi data identitas dokumen jika diperlukan, bukan akun/role.
5. Tidak ada portal Proktor permanen. Proktor adalah **Guru yang mendapat penugasan sementara** pada Exam dan Room tertentu. Siswa tidak pernah menjadi Proktor.
6. Setiap madrasah memiliki satu instalasi aplikasi dan database akademik sendiri. Tidak ada database akademik bersama atau akses akademik antarmadrasah. `tenant_id` tetap dipakai sebagai defense-in-depth dalam instalasi.
7. Isolasi per instalasi mencakup DB credentials/config, domain, session, cache, queue, storage, log, export, temporary files, dan backup.
8. Ujian menggunakan **MODEL SERENTAK**. Jangan memperkenalkan gelombang/sesi operasional, `ExamSession`, `SessionRoom`, `session_number`, atau session lifecycle. Jadwal ujian mengacu ke `exam.start_at` dan `exam.end_at`.
9. Room hanya untuk penempatan peserta dan lingkup monitoring Proktor, bukan syarat admission untuk start/resume Attempt.
10. Admin mengelola administrasi, master data, penempatan, penugasan Proktor, monitoring, dan dukungan teknis. Guru mengelola bank soal serta membuat/mengatur/menerbitkan ujian dalam lingkup penugasannya tanpa persetujuan Admin.
11. Guru tidak boleh mengakses kelas, mata pelajaran, soal, ujian, peserta, jawaban, atau nilai milik Guru lain tanpa penugasan/otorisasi sah.
12. Admin tidak boleh memperoleh akses ke kunci jawaban/jawaban ujian hanya karena memiliki akses monitoring.
13. Jangan mengklaim Device Lease/fencing siap produksi sebelum uji integrasi dan multi-device MySQL berhasil. Jangan mengklaim browser sebagai kiosk atau mampu mencegah screenshot/perpindahan aplikasi.
14. SHORT_ANSWER tidak boleh diaktifkan sebelum accepted answers, immutable snapshot, dan scoring terbukti. ESSAY memerlukan penilaian manual.
15. Waktu server adalah acuan resmi. Token dibuat otomatis setelah Guru menerbitkan ujian; token dapat dilihat sebelum jadwal, tetapi siswa hanya dapat memulai sesuai jadwal.
16. Database VPS saat ini dinyatakan kosong dan pengguna mengizinkan instalasi ulang total. VPS sudah disiapkan: aaPanel, Laravel dan lingkungan pendukung telah dipasang; domain `https://cbt.min15jakarta.sch.id/login` telah tampil normal di browser pengguna. **Jangan menyimpulkan server down dari kegagalan fetch tool.**
17. Pengguna tidak memiliki akses SSH dan tidak menyediakan SSH untuk agent. Jalur deployment yang direncanakan: aaPanel File Manager (upload ZIP, extract), buat database via aaPanel, konfigurasi aplikasi melalui panel/UI. Jangan mensyaratkan SSH atau terminal untuk pengguna.
18. Jangan meminta password, .env, private key, token, atau rahasia melalui chat.
19. Jangan menjalankan Gradle via terminal. Android memang di luar scope saat ini.
20. Pisahkan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN. Jangan mengklaim lulus tanpa output/bukti tes. Backup sebelum mengubah file; ZIP baseline asli tidak boleh diubah.

## 1. STRUKTUR MENU TARGET WEB ADMIN MADRASAH

Nama menu dapat mengikuti konvensi UI existing, tetapi cakupan fungsi tidak boleh hilang. Agent harus menginventarisasi route, controller, Livewire component, policy, model, migration, view, menu, dan test aktual sebelum mengubahnya.

### 1. Dashboard
- Ringkasan jumlah siswa aktif, guru aktif, rombel, kelas, mata pelajaran.
- Ringkasan ujian terjadwal, sedang berjalan, selesai.
- Ringkasan peserta per status: belum mulai, sedang mengerjakan, selesai, bermasalah (hanya status yang benar-benar tersedia).
- Jadwal ujian terdekat dan ujian aktif.
- Aktivitas terbaru: import data, perubahan data master, pembuatan/penerbitan ujian, penugasan/pencabutan Proktor, perubahan jadwal.
- Peringatan data master yang belum lengkap dan kendala operasional.
- Semua angka harus berasal dari database, difilter sesuai instalasi/tahun pelajaran dan tidak boleh berupa angka statis/dekoratif.
- Dashboard tidak menampilkan isi jawaban atau kunci jawaban.

### 2. Data Madrasah / Identitas
**Profil madrasah**
- Nama resmi dan nama singkat.
- NSM, NPSN, jenjang, status negeri/swasta.
- Alamat lengkap, kelurahan/desa, kecamatan, kabupaten/kota, provinsi, kode pos.
- Telepon, email resmi, website.
- Nama Kepala Madrasah sebagai teks identitas dokumen bila diperlukan (bukan role/login CBT).
- Keterangan tambahan yang relevan.

**Logo dan identitas visual**
- Upload, preview, ganti, hapus dengan konfirmasi.
- Logo header aplikasi, logo kop dokumen, favicon, logo/identitas halaman login.
- Format gambar yang aman (PNG/JPG/JPEG; SVG hanya bila disanitasi).
- Validasi MIME, ekstensi, ukuran, dimensi; cegah upload file executable/script.
- Pertahankan rasio aspek; jangan membuat logo gepeng.
- File hanya berlaku untuk instalasi madrasah tersebut.

**Kop surat / kepala dokumen**
- Logo kiri/kanan bila diperlukan.
- Nama instansi/kementerian, nama madrasah, alamat, kontak, website.
- Garis pembatas, teks tambahan, ukuran/posisi logo, perataan, margin dan jarak.
- Preview sebelum cetak/ekspor.
- Digunakan pada daftar peserta, daftar hadir, berita acara, rekap nilai, laporan hasil ujian, dan dokumen administrasi lain yang memang memerlukannya.
- Tidak boleh hardcode nama/alamat/logo di source atau template.

### 3. Data Pengguna
**Guru**
- Daftar, tambah, detail, edit, aktif/nonaktif, arsip bila sesuai.
- Nama, NIP (opsional), NUPTK (opsional), email, telepon, alamat dan atribut relevan.
- Status kepegawaian/jabatan bila diperlukan.
- Akun: username, email login, status, reset password, status login terakhir.
- Riwayat penugasan mengajar dan penugasan Proktor.
- Import/export dan template.

**Siswa**
- Daftar, tambah, detail, edit, aktif/nonaktif, arsip/lulus sesuai kebijakan.
- NIS, NISN, nama, jenis kelamin, tempat/tanggal lahir, alamat, data wali bila diperlukan.
- Tahun masuk, status siswa, kelas/rombel.
- Akun login, username, reset password, status akun.
- Pindah rombel, naik kelas, lulus, nonaktif dengan histori.
- Import/export dan template.
- Histori akademik dan ujian tidak boleh hilang ketika siswa pindah kelas/lulus/nonaktif.

**Akun & akses**
- Admin mengelola akun Guru/Siswa sesuai kewenangan.
- Aktivasi/nonaktivasi, reset password, username, cabut sesi bila tersedia dan diotorisasi.
- Jangan mengekspor password.
- Jangan membuat role Operator, Kamad, atau Proktor permanen.
- Admin tidak dapat mengangkat dirinya menjadi Super Admin.

### 4. Import dan Export Data Guru/Siswa — WAJIB DETAIL
**Template**
- Template XLSX terpisah untuk Guru dan Siswa; CSV dapat didukung bila format ditetapkan.
- Instruksi tiap kolom, kolom wajib/opsional, format tanggal, enum jenis kelamin/status, contoh baris.
- Template penempatan siswa ke rombel/tahun pelajaran harus jelas.

**Alur import**
1. Unduh template.
2. Upload file.
3. Validasi ekstensi, MIME, ukuran, struktur, header, jumlah baris.
4. Pemetaan kolom bila didukung; laporkan header tidak dikenal/kolom wajib hilang.
5. Preview seluruh baris: valid, invalid, duplikat, konflik, alasan kesalahan.
6. Konfirmasi eksplisit sebelum simpan.
7. Simpan dengan transaksi/batch aman; kegagalan tidak boleh menghasilkan data setengah tersimpan tanpa status yang jelas.
8. Tampilkan ringkasan sukses/dilewati/gagal dan sediakan laporan kesalahan yang bisa diunduh.

**Validasi siswa**
- Nama wajib; NIS unik sesuai lingkup madrasah; NISN format sesuai ketentuan.
- Rombel dan tahun pelajaran harus ada/aktif.
- Jenis kelamin dan tanggal valid.
- Identitas ambigu jangan ditebak.
- Siswa yang sudah ada tidak boleh dibuat ganda hanya karena variasi kapital/spasi nama.
- Penempatan rombel tidak boleh ganda pada tahun pelajaran yang sama.

**Validasi Guru**
- NIP/NUPTK unik bila diisi.
- Username unik dalam instalasi.
- Email valid.
- Status kepegawaian memakai enum yang dikenal.
- Import ulang tidak membuat akun/guru duplikat.

**Duplikasi/idempotensi**
- Identitas duplikat ditolak atau ditampilkan sebagai konflik.
- Perubahan data existing harus tampil sebagai calon update dan butuh konfirmasi; jangan overwrite otomatis.
- Import file sama berulang tidak membuat record/akun ganda.
- Data ambigu harus diperbaiki pengguna.
- Jangan mengubah histori akademik lintas tahun secara diam-diam.

**Export**
- Data Guru, Siswa, per kelas/rombel, per tahun pelajaran, status aktif/nonaktif.
- Terapkan filter dan otorisasi; jangan sertakan password, token, secret, atau data sensitif yang tidak perlu.

### 5. Data Akademik
**Tahun Pelajaran**
- Tambah/edit, tetapkan aktif, arsip, riwayat.
- Maksimal satu tahun pelajaran aktif per instalasi kecuali desain sumber membuktikan aturan berbeda.
- Pergantian tahun tidak menghapus data lama.

**Kelas**
- MI kelas 1–6, kode/nama, urutan, status, jenjang.
- Relasi tahun pelajaran bila memang menjadi bagian model data.

**Rombongan Belajar**
- Buat/edit rombel (contoh 1A, 1B), tahun pelajaran, tingkat, wali kelas jika dipakai.
- Tambah/pindah/keluarkan siswa dengan alasan dan histori.
- Import/export daftar siswa.
- Satu siswa tidak boleh mempunyai dua penempatan rombel aktif yang bertentangan pada tahun pelajaran yang sama.

**Mata Pelajaran**
- Tambah/edit, kode, kelompok, tingkat kelas, status, relasi kurikulum/versi.

**Penugasan Guru**
- Tugaskan Guru ke mata pelajaran dan kelas/rombel tertentu untuk tahun pelajaran.
- Ubah/cabut dengan histori.
- Guru hanya mendapat akses sesuai penugasan aktif.
- Validasi backend, bukan hanya menyembunyikan menu.

### 6. Kurikulum dan Pembelajaran
- Daftar kurikulum, versi, masa berlaku, status, struktur dan histori.
- Kurikulum Merdeka harus didukung.
- Ruang integrasi untuk Kurikulum Berbasis Cinta/Panca Cinta, Pembelajaran Mendalam, serta kurikulum berikutnya sesuai keputusan proyek.
- Struktur: fase, kelas, mapel, CP, TP, ATP, elemen/lingkup materi bila diperlukan.
- Registrasi kurikulum madrasah per tahun pelajaran.
- Perubahan kurikulum tidak mengubah histori ujian/asesmen yang sudah dibuat.
- Kurikulum harus tercermin dalam data akademik, pembelajaran, bank soal, asesmen, ujian dan laporan; bukan hanya label pada Bank Soal.
- Admin mengelola penerapan/struktur; Guru menggunakan struktur sesuai kewenangan.

### 7. Manajemen Ujian dan Operasional Proktor
Admin mengelola:
- Daftar ujian dan detail metadata yang boleh dilihat.
- Jadwal dan status ujian.
- Room/ruang sebagai lokasi penempatan peserta dan lingkup monitoring.
- Penempatan peserta dari roster resmi.
- Penugasan Guru sebagai Proktor untuk Exam + Room tertentu.
- Pencabutan penugasan, alasan, waktu, pemberi tugas, dan audit.
- Monitoring status operasional peserta.
- Tindakan khusus hanya jika diotorisasi secara terpisah dan dicatat audit.

**Mode A — Guru mengelola ujian sendiri**
- Guru membuat, mengatur, menerbitkan dan mengelola ujian dalam lingkup kelas/mapel yang ditugaskan.
- Tidak perlu penugasan Proktor dari Admin untuk mengelola ujian sendiri.
- Guru tidak dapat melihat ujian/kelas/peserta milik Guru lain.

**Mode B — Ujian terpusat**
- Admin menugaskan akun Guru sebagai Proktor pada Exam dan Room tertentu.
- Penempatan peserta lintas kelas boleh dilakukan hanya melalui roster resmi dan penugasan Admin yang sah.
- Proktor hanya melihat identitas minimum, kehadiran, status/progres peserta pada Room yang ditugaskan.
- Tidak boleh melihat jawaban, kunci, nilai, bank soal, konfigurasi ujian; tidak boleh mengakses Room lain.
- Tidak boleh Force Submit/Reset Attempt/tindakan khusus tanpa izin terpisah.
- Penugasan memiliki interval `[starts_at, ends_at)`, status, assigned_by, revoked_by, reason, audit.
- Penugasan Guru yang tumpang tindih pada Exam/Room yang bertentangan harus dicegah dengan transaksi/serialisasi aman; interval bersebelahan mis. 07–09 dan 09–11 tidak overlap.
- Roster Room berasal dari penempatan peserta resmi, bukan kelas asal Proktor.

**Model ujian**
- Model serentak; jadwal berdasarkan `exam.start_at` dan `exam.end_at`.
- Tidak ada gelombang/session lifecycle.
- Room bukan admission gate start/resume Attempt.
- Token otomatis setelah publish; terlihat sebelum jadwal; siswa hanya start saat jadwal sah.
- Waktu server resmi.

### 8. Monitoring CBT
- Daftar ujian aktif dan selesai sesuai kewenangan.
- Monitoring peserta: identitas minimum, Room, hadir/belum hadir, belum mulai, in progress, submitted, status teknis.
- Filter ujian, Room, kelas, status.
- Refresh/pembaruan status dengan mekanisme yang aman.
- Riwayat kendala teknis dan aktivitas operasional.
- Monitoring tidak boleh membocorkan jawaban, kunci, soal, nilai atau detail privat yang tidak dibutuhkan.
- Guru hanya monitoring ujian/kelas dalam penugasannya; Proktor hanya Room yang ditugaskan.
- Admin monitoring sesuai scope administrasi instalasi, tetapi bukan otomatis pemilik hak isi jawaban/kunci.

### 9. Laporan
- Rekap data Guru/Siswa.
- Rekap siswa per kelas/rombel/tahun pelajaran.
- Daftar peserta ujian.
- Daftar hadir ujian.
- Rekap status/progres peserta.
- Hasil ujian/nilai hanya sesuai otorisasi dan alur penilaian yang ditetapkan.
- Rekap per mapel/kelas/tahun pelajaran.
- Laporan aktivitas administrasi dan audit.
- Export PDF/XLSX/CSV sesuai kebutuhan dan lisensi library.
- Kop/identitas madrasah otomatis mengikuti pengaturan.
- Filter, pagination, periode, dan timezone harus konsisten.
- Export menerapkan otorisasi sama ketatnya dengan halaman; cegah IDOR dan export lintas scope.
- File export temporer harus memiliki akses terbatas, masa berlaku, dan dibersihkan sesuai kebijakan.

### 10. Audit dan Keamanan
- Catat aktor, aksi, target, waktu server, hasil, alasan, metadata relevan.
- Aktivitas penting: create/update/archive, import/export, reset password, ubah penugasan, publish/ubah jadwal, penugasan/pencabutan Proktor, tindakan Attempt khusus.
- Audit log tidak boleh bisa diedit/dihapus oleh Admin biasa.
- Pencarian/filter log dengan pagination.
- Percobaan login gagal dan status keamanan jika sistem mendukung.
- Validasi tenant/instalasi, role, permission, assignment, scope data pada backend.
- Proteksi CSRF, XSS, SQL injection, mass assignment, IDOR, session fixation, brute force, file upload berbahaya.
- Validasi server-side pada setiap Livewire action/controller/API/export; jangan mengandalkan UI hide.
- Transaksi, idempotensi, locking, dan constraint DB untuk operasi sensitif.
- Hindari race condition pada import, assignment, penempatan peserta, publish dan tindakan attempt.
- Jangan log password, token, secret, atau data jawaban lengkap tanpa alasan sah.

### 11. Pengaturan Aplikasi
**Identitas**
- Nama aplikasi, nama madrasah, logo, favicon, warna branding.
- Login title/welcome text, bantuan, kontak, footer, website/media sosial.
- Pengaturan per instalasi.

**Kop dokumen**
- Field kop, logo, posisi, garis, margin, preview.
- Template cetak memakai konfigurasi tersimpan, bukan hardcode.

**Preferensi UI**
- Tema Light/Dark/System bila tersedia; default mengikuti System.
- Kontras dan aksesibilitas setara WCAG 2.2 AA sebagai target.
- Responsive untuk desktop/tablet/mobile.
- UI Admin boleh kaya fitur namun ringan; layar ujian siswa harus fokus/minimal.
- Bahasa dan format tanggal/waktu jika disediakan; zona waktu harus konsisten.

**Operasional**
- Pengaturan tahun pelajaran aktif.
- Batas/aturan import yang aman.
- Pengaturan notifikasi yang memang tersedia.
- Informasi versi aplikasi/health status bila aman ditampilkan.
- Admin tidak boleh mengubah konfigurasi server, secret, private key, atau hak Super Admin dari portal ini.

### 12. Navigasi dan pengalaman pengguna
- Sidebar dengan kelompok menu yang jelas, aktif state dan ikon konsisten.
- Breadcrumb untuk halaman bertingkat.
- Search/filter, pagination, sorting.
- Empty state, loading state, error state, success feedback.
- Form memiliki validasi dan pesan kesalahan yang jelas.
- Konfirmasi untuk hapus/arsip/nonaktif/aksi berdampak.
- Hindari tombol dekoratif yang tidak melakukan aksi.
- Tabel mendukung pencarian, filter, sort dan export sesuai hak.
- Responsive tanpa mengorbankan fungsi penting.
- Aksesibilitas keyboard, label input, focus state, kontras.
- Jangan membuat dashboard berat atau query N+1.

## 2. BATAS KEWENANGAN ADMIN VS GURU VS PROKTOR VS SISWA

| Area | Admin Madrasah | Guru | Guru yang ditugaskan Proktor | Siswa |
|---|---|---|---|---|
| Identitas madrasah & branding | Kelola | Tidak | Tidak | Tidak |
| Data Guru/Siswa | Kelola sesuai kewenangan | Tidak | Tidak | Data sendiri terbatas |
| Tahun pelajaran/kelas/rombel | Kelola | Baca scope tugas | Baca minimum roster Room | Tidak |
| Penugasan Guru | Kelola | Lihat milik sendiri | Lihat penugasan sendiri | Tidak |
| Bank soal | Tidak otomatis mengedit isi Guru | Kelola scope tugas | Tidak karena peran Proktor | Tidak |
| Buat/publish ujian | Tidak mengambil alih hak Guru | Kelola scope tugas | Hanya jika juga pemilik/ditugaskan sebagai Guru pada konteks tersebut | Tidak |
| Monitoring | Administratif, minim data | Ujian/scope sendiri | Room yang ditugaskan | Status sendiri |
| Jawaban/kunci | Tidak otomatis | Sesuai hak penilaian/ujian sendiri | Tidak | Jawaban sendiri saat ujian |
| Reset/Force Submit/takeover | Hanya jika permission eksplisit dan audit | Hanya jika permission eksplisit | Tidak otomatis; izin terpisah | Tidak |
| Audit log | Baca sesuai scope | Aktivitas sendiri bila disediakan | Aktivitas penugasan sendiri bila disediakan | Tidak |

**Penting:** Proktor adalah penugasan sementara pada akun Guru, bukan role permanen. Hak Proktor tidak menambah hak Guru untuk mengakses jawaban/kunci/konfigurasi.

## 3. KUALITAS DATA, HISTORI, DAN INTEGRITAS
- Soft delete/archive untuk data yang perlu mempertahankan histori; jangan hard delete data yang direferensikan.
- FK/unique/index harus mendukung integritas dan isolasi.
- Import harus idempotent dan aman terhadap duplikasi.
- Semua perpindahan rombel, pergantian tahun, penugasan, perubahan kurikulum harus memiliki histori yang cukup.
- Snapshot ujian yang sudah diterbitkan bersifat immutable.
- Jangan melakukan backfill yang menebak asal/tenant data.
- Jangan menghapus tabel/migration historis tanpa audit dampak dan strategi fresh install/upgrade.
- Pengujian DB utama tidak boleh memakai dump produksi; gunakan database disposable/terisolasi.
- Pengguna menyatakan database VPS saat ini kosong dan mengizinkan fresh install total, tetapi agent tetap harus memverifikasi kondisi deployment aktual dan tidak boleh menganggap database kosong tanpa bukti terbaru.

## 4. STANDAR PENGUJIAN WAJIB

### A. Unit/Feature
- CRUD setiap modul dengan validasi.
- Permission dan policy per role.
- Scope penugasan Guru.
- Scope Room Proktor.
- Import valid, invalid, duplikat, file terlalu besar, header salah, file berbahaya.
- Import file yang sama dua kali (idempotensi).
- Export sesuai scope.
- Branding/logo/kop tampil pada halaman dan dokumen.
- Histori tidak hilang saat pindah rombel/tahun/arsip.
- Validasi model ujian serentak dan tidak ada session/wave logic.

### B. Security negative tests
- Admin instalasi A tidak dapat mengakses instalasi B.
- Guru A tidak dapat mengakses data Guru B.
- Proktor Room A tidak dapat melihat Room B.
- Proktor tidak dapat melihat jawaban/kunci/nilai.
- Siswa tidak dapat melihat data siswa lain.
- IDOR melalui URL, request Livewire, API, export, dan file storage ditolak.
- Manipulasi tenant_id/foreign IDs ditolak.
- Upload file executable/script ditolak.
- Export tidak membocorkan credential atau data berlebih.

### C. Database/concurrency
- Uji MySQL 8/InnoDB nyata pada database disposable.
- Import paralel dan duplikasi.
- Dua Admin mengubah penempatan yang sama.
- Penugasan Proktor overlap bersamaan.
- Publish dan perubahan jadwal bersamaan.
- Operasi Attempt sensitif dengan lock order konsisten.
- Constraint dan rollback transaction terbukti.

### D. Browser/E2E/UAT
- Login/logout Admin, Guru, Siswa.
- Navigasi semua menu dan sub-menu.
- Form CRUD, import preview-confirm-result, export.
- Upload logo, kop preview, hasil print/PDF.
- Buat ujian, publish, token, jadwal, peserta, Room, penugasan Proktor.
- Simulasi ujian serentak oleh beberapa akun.
- Monitoring dan laporan.
- Uji desktop, tablet, mobile browser.
- UAT Admin, Guru, Siswa di madrasah.

**Jangan menyatakan siap operasional hanya karena halaman tampil atau unit test lulus.** Harus ada bukti integrasi, keamanan, stabilitas, import, ujian serentak, dan UAT.

## 5. INSTRUKSI WAJIB UNTUK AGENT PENGGANTI

1. Baca dokumen ini sampai selesai sebelum coding.
2. Periksa issue koordinasi utama #10 dan seluruh dokumen/checkpoint terbaru di repository.
3. Periksa branch, HEAD, status CI, source aktual, routes, migrations, models, controllers, Livewire components, Blade views, policies, tests, serta laporan agent sebelumnya.
4. Jangan membuat ulang source dari asumsi dan jangan mengulang patch yang sudah ada.
5. Buat **matriks audit** seluruh menu/submenu di atas dengan kolom: fitur, route, UI/component, backend, policy, tabel/model, test, status, gap, prioritas.
6. Bedakan fitur yang sudah ada, belum lengkap, belum ada, dan tidak sesuai keputusan proyek.
7. Prioritaskan fitur yang diperlukan operasional Web CBT; Android/Super Admin tidak boleh menghambat.
8. Sebelum mengubah file, buat backup dan catat path serta hash.
9. Hindari konflik antar-agent: tetapkan file ownership. Perubahan `routes/web.php` harus memiliki satu pemilik integrasi; agent lain menyampaikan kontrak/snippet, jangan menimpa file route bersama.
10. Setiap patch harus disertai laporan file berubah, backup, alasan, migration, test yang benar-benar dijalankan, output dan keterbatasan.
11. Jangan menjalankan migration pada DB produksi tanpa rencana, backup, preflight, dan persetujuan eksplisit.
12. Deployment harus mengikuti kondisi aktual: aaPanel sudah ada, tidak ada SSH, instalasi melalui File Manager dan UI aaPanel.
13. Buat README instalasi untuk satu ZIP distribusi seluruh madrasah; tiap madrasah install sendiri dan punya database sendiri.
14. Jangan menyatakan fitur siap hanya karena menu/tombol sudah terlihat. Buktikan alur end-to-end.

## 6. FORMAT LAPORAN AGENT

Setiap tahap wajib melaporkan:

### AUDIT
- Source/branch/commit yang diperiksa.
- Temuan dan bukti file/route/model/test.
- Status tiap fitur dan gap.

### DESAIN
- Alur pengguna.
- Role/permission/scope.
- Struktur data dan integritas.
- Dampak terhadap modul lain.
- Keputusan yang masih membutuhkan persetujuan.

### IMPLEMENTASI
- File dibuat/diubah.
- Backup path dan hash.
- Migration baru dan dampak.
- Perubahan menu, route, controller, component, policy, model.
- Jangan mengubah file di luar ownership tanpa koordinasi.

### PENGUJIAN
- Perintah/test yang benar-benar dijalankan.
- Lingkungan dan versi DB.
- Jumlah test/assertion dan hasil.
- Screenshot/log/output yang relevan dan disanitasi.
- Test yang belum dijalankan dan alasannya.
- Blocker dan langkah berikutnya.

## 7. DEFINITION OF DONE WEB ADMIN

Web Admin baru dapat dinyatakan selesai bila:
- Semua menu dan sub-menu yang disetujui memiliki fungsi nyata dan otorisasi benar.
- Profil madrasah, logo, favicon, kop, preview dan branding bekerja.
- Import/export Guru dan Siswa aman, idempotent, punya preview dan laporan kesalahan.
- Tahun pelajaran, kelas, rombel, mapel, penugasan Guru dan histori konsisten.
- Kurikulum terintegrasi lintas akademik, pembelajaran, asesmen, ujian dan laporan.
- Mode Guru mandiri dan Mode Proktor terpusat bekerja sesuai scope.
- Ujian serentak tanpa session/wave model.
- Monitoring tidak membocorkan jawaban/kunci.
- Laporan dan cetak memakai kop madrasah.
- Audit log, tenant/install isolation, permission, IDOR dan keamanan file teruji.
- E2E dan UAT nyata berhasil dengan bukti.
- Dokumentasi, checkpoint, README, dan ZIP distribusi diperbarui.
- Tidak ada klaim siap produksi tanpa bukti integrasi, keamanan, stabilitas dan validasi madrasah.

---
**PERINGATAN TERAKHIR:** Jika source saat ini berbeda dari dokumen, jangan langsung menimpa. Laporkan perbedaannya, jelaskan dampak, pertahankan keputusan proyek, dan ajukan solusi yang kompatibel. Dokumen ini adalah acuan fitur dan arah kerja, sedangkan source aktual serta hasil pengujian tetap menjadi bukti kondisi implementasi.
