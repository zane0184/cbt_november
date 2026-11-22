# MASTER SPESIFIKASI FITUR WEB ADMIN MADRASAH CBT
Tanggal: 22 November 2026
Status: **ARAHAN PRODUK WAJIB — ACUAN AGENT WEB**
Repository: `zane0184/cbt_november`

> Dokumen ini adalah spesifikasi ruang lingkup dan perilaku Web Admin Madrasah. Agent wajib membaca sebelum audit, desain, atau implementasi. Jangan menyimpulkan fitur selesai hanya karena menu/tombol tampil. Setiap fitur harus memiliki alur backend, otorisasi, validasi, audit, dan pengujian.

## 0. KEPUTUSAN PROYEK YANG MENGIKAT

1. Fokus sekarang **WEB CBT** untuk penggunaan madrasah.
2. Portal aktif hanya: **Admin Madrasah, Guru, Siswa**.
3. Android dan Web Super Admin ditunda sampai Juni 2027. Jangan jadikan keduanya dependency atau blocker pekerjaan Web sekarang.
4. Tidak ada portal/akun/role Operator. Seluruh fungsi administrasi Operator digabung ke Admin Madrasah.
5. Tidak ada portal/akun/role Kamad dalam CBT. Nama kepala madrasah boleh menjadi data identitas pada kop/dokumen, bukan akun/role.
6. Tidak ada role/portal Proktor permanen. Proktor adalah Guru yang menerima penugasan sementara untuk Exam dan Room tertentu.
7. Siswa hanya peserta ujian, bukan Proktor.
8. Setiap madrasah mendapat satu instalasi aplikasi dan database akademik sendiri. Tidak ada database akademik bersama atau akses lintas madrasah. `tenant_id` tetap menjadi defense-in-depth.
9. VPS telah disiapkan, aaPanel dan Laravel serta lingkungan pendukung telah dipasang. Domain `https://cbt.min15jakarta.sch.id/login` telah tampil normal menurut pengguna. Database/aplikasi masih kosong. Pengguna tidak memiliki akses SSH; alur deployment harus mendukung aaPanel File Manager, upload/extract ZIP, pembuatan database melalui aaPanel, dan konfigurasi aplikasi. Jangan menganggap VPS mati karena fetch dari lingkungan agent gagal.
10. Model ujian adalah **SERENTAK**. Admission berdasarkan `exam.start_at`, `exam.end_at`, token Exam, dan waktu server. Jangan menghidupkan kembali gelombang/sesi ujian operasional atau menjadikan Room admission gate. Tidak ada `ExamSession`, `SessionRoom`, session_number, wave, atau session lifecycle sebagai model operasional.
11. Token otomatis dibuat saat Guru menerbitkan ujian; dapat dilihat sebelum jadwal, tetapi Siswa baru boleh mulai sesuai jadwal server.
12. Jangan menyebut Device Lease/fencing siap produksi sebelum integration test dan MySQL 8/InnoDB multi-device/concurrency test lulus.
13. Jangan mengklaim browser sebagai kiosk atau mampu mencegah screenshot, tombol Home, atau perpindahan aplikasi.
14. SHORT_ANSWER belum boleh diaktifkan sebelum accepted answers pada authoring, immutable snapshot, dan scoring terbukti. ESSAY memerlukan penilaian manual.
15. Kurikulum Merdeka harus terintegrasi ke akademik, pembelajaran, asesmen, bank soal, ujian, dan laporan; pertahankan versi dan histori. Sediakan desain untuk Kurikulum Berbasis Cinta/Panca Cinta, Pembelajaran Mendalam, dan kurikulum berikutnya.

**PENTING TENTANG ISSUE LAMA:** issue #10 dan dokumen historis yang menyebut “SATU EXAM = SATU EXAMSESSION OPERASIONAL” sudah tidak sesuai dengan keputusan terbaru. Untuk implementasi Web saat ini, keputusan serentak tanpa ExamSession/SessionRoom operasional di atas yang berlaku. Jangan mengikuti kontrak lama tersebut tanpa persetujuan baru pengguna.

---

## 1. STRUKTUR MENU WEB ADMIN MADRASAH

Nama label boleh mengikuti konsistensi UI yang ada, tetapi cakupan fungsi berikut tidak boleh hilang.

1. Dashboard
2. Data Madrasah
3. Data Pengguna
   - Guru
   - Siswa
   - Akun dan akses
   - Import data
   - Export data
4. Data Akademik
   - Tahun Pelajaran/Semester
   - Kelas
   - Rombongan Belajar
   - Penempatan/Mutasi Siswa
   - Mata Pelajaran
   - Penugasan Guru
5. Kurikulum
   - Daftar kurikulum dan versi
   - Registrasi kurikulum madrasah/tahun pelajaran
   - Struktur kurikulum
   - Fase/kelas/mata pelajaran
   - CP, TP, ATP
   - Pemetaan dan histori kurikulum
6. Manajemen Ujian
   - Daftar ujian
   - Detail/status ujian
   - Ruang ujian
   - Penempatan peserta per ruang
   - Penugasan Proktor
8. Laporan
   - Rekap akademik
   - Rekap peserta/kehadiran
   - Rekap ujian
   - Hasil/nilai sesuai kewenangan
   - Export dan cetak
9. Audit dan Keamanan
   - Log aktivitas
   - Riwayat login dan akses ditolak
   - Riwayat tindakan sensitif
10. Pengaturan
   - Umum
   - Identitas dan branding
   - Logo/favicon
   - Kop dokumen/cetak
   - Tampilan/tema
   - Profil dan keamanan akun Admin
   - Notifikasi/preferensi
   - Pengaturan operasional yang memang menjadi kewenangan Admin

Dashboard bukan pengganti menu fungsional. Setiap menu harus punya list, detail, create/edit yang relevan, pencarian/filter, pagination, empty state, validasi, dan feedback error/sukses yang benar.

## 2. DASHBOARD

Tampilkan data real dari database dan filter menurut tahun pelajaran aktif:
- jumlah siswa aktif, Guru aktif, kelas, rombel, mata pelajaran;
- jumlah ujian terdaftar;
- jadwal ujian terdekat sebagai informasi administratif (tanpa status real-time ujian/peserta);
- aktivitas administrasi terbaru (import, perubahan data, penerbitan ujian, penugasan Proktor);
- peringatan data master yang belum lengkap dan kendala operasional.

Tidak boleh menampilkan angka dummy/hard-coded. Dashboard Admin tidak membuka jawaban atau kunci jawaban tanpa kewenangan terpisah.

## 3. DATA MADRASAH, LOGO, BRANDING, DAN KOP

### 3.1 Profil Madrasah
Kelola nama resmi/singkat, NSM, NPSN, jenjang, status negeri/swasta, alamat lengkap, desa/kelurahan, kecamatan, kabupaten/kota, provinsi, kode pos, telepon, email, website, nama kepala madrasah sebagai identitas dokumen, dan informasi tambahan yang diperlukan.

### 3.2 Logo dan identitas visual
Admin dapat upload, preview, mengganti, dan menghapus logo dengan konfirmasi; mengatur logo header aplikasi, logo kop dokumen, favicon, identitas halaman login, dan branding instalasi. Jaga rasio aspek; batasi ukuran/dimensi; validasi ekstensi dan MIME; sanitasi SVG atau tolak SVG yang tidak aman. Jangan menyimpan file upload pada lokasi publik tanpa kontrol yang sesuai.

### 3.3 Kop surat/kepala dokumen
Pengaturan mandiri tanpa hard-code: logo kiri/kanan, nama instansi/kementerian, nama madrasah, alamat, kontak, website, teks tambahan, garis pembatas, posisi/ukuran logo, alignment, margin, jarak, header/footer, nomor halaman bila relevan. Sediakan preview. Kop digunakan pada daftar peserta, daftar hadir, berita acara, rekap nilai, laporan hasil ujian, dan dokumen cetak lain.

### 3.4 Branding web
Nama aplikasi, logo, favicon, warna identitas, judul/deskripsi login, pesan sambutan, informasi bantuan, footer, website dan media sosial. Semua setting hanya memengaruhi instalasi madrasah tersebut.

## 4. DATA PENGGUNA

### 4.1 Guru
CRUD/detail, aktif/nonaktif, akun/username, reset password, email/telepon, riwayat perubahan, riwayat penugasan mengajar dan Proktor, import/export, template. Field sesuai kebutuhan: nama, NIP/NUPTK (opsional bila tidak dimiliki), NIK hanya bila ada dasar dan perlindungan, gender, tempat/tanggal lahir bila diperlukan, email, telepon, alamat, status kepegawaian, keterangan tugas, status akun. Jangan mewajibkan NIP untuk semua Guru.

### 4.2 Siswa
CRUD/detail, aktif/nonaktif/arsip/lulus, pindah/naik kelas, penempatan rombel, reset password, akun login, import/export/template, histori akademik dan mutasi. Field: nama, NIS, NISN, NIK hanya bila diperlukan, gender, tempat/tanggal lahir, wali bila diperlukan, alamat, tahun masuk, kelas/rombel, status siswa, username/status akun.

### 4.3 Akun dan akses
Admin mengelola akun Guru dan Siswa sesuai kewenangan: buat/aktif/nonaktif, reset password, username, paksa ganti password pertama kali bila kebijakan dipakai, login terakhir, cabut sesi jika didukung. Tidak boleh membuat role Operator, Kamad, atau Proktor permanen; Admin tidak dapat menaikkan dirinya menjadi Super Admin. Password tidak pernah ditampilkan atau diekspor.

## 5. IMPORT DAN EXPORT GURU/SISWA — WAJIB LENGKAP

Sediakan template XLSX terpisah untuk Guru dan Siswa, instruksi kolom, contoh baris, kolom wajib/opsional, format tanggal, enum gender/status, dan ketentuan rombel/tahun pelajaran.

Alur wajib:
1. Unduh template.
2. Upload file.
3. Validasi ekstensi/MIME/ukuran/struktur/header/jumlah baris.
4. Pemetaan kolom yang aman; laporkan kolom wajib hilang/tidak dikenal.
5. Preview sebelum commit.
6. Tampilkan valid, invalid, duplikat, konflik, dan alasan per baris.
7. Konfirmasi eksplisit Admin.
8. Simpan secara transaksional/batch aman; kegagalan tidak boleh meninggalkan hasil parsial yang tidak terlaporkan.
9. Laporan hasil: berhasil, dilewati, gagal; unduh error report.

Validasi Siswa: nama wajib, NIS unik dalam lingkup instalasi, NISN tervalidasi, rombel dan tahun pelajaran benar-benar ada/aktif, gender/date valid, siswa tidak terduplikasi. Validasi Guru: identitas diperiksa, NIP/NUPTK unik bila diisi, username unik, email valid, status sesuai enum.

Kebijakan konflik:
- Identitas duplikat ditolak/masuk daftar konflik.
- Data existing dengan perubahan kolom menjadi calon update dan butuh preview/konfirmasi; jangan overwrite diam-diam.
- Import file yang sama berulang harus idempotent.
- Data ambigu harus dikoreksi, jangan menebak identitas.
- Import tidak boleh merusak histori akademik.
- Jangan export password, token, secret, atau credential sensitif.

Export data Siswa/Guru dapat difilter per status, kelas, rombel, tahun pelajaran, dengan audit untuk data sensitif.

## 6. DATA AKADEMIK

### 6.1 Tahun pelajaran/semester
Tambah/edit, aktif/nonaktif/arsip, histori, penetapan tahun aktif. Maksimal satu tahun pelajaran aktif per instalasi kecuali desain yang disetujui menyatakan lain. Pergantian tahun tidak menghapus data lama.

### 6.2 Kelas dan rombel
Kelas MI 1–6; kelola nama/tingkat/status. Rombel: nama (contoh 1A/1B), tahun pelajaran, tingkat, wali kelas bila dipakai, tambah/pindah/keluarkan siswa dengan alasan, import/export, histori. Cegah satu siswa memiliki dua penempatan rombel aktif yang bertentangan pada tahun yang sama.

### 6.3 Mata pelajaran
CRUD, kode, kelompok, kelas/jenjang pengguna, status, relasi kurikulum/versi.

### 6.4 Penugasan Guru
Admin menugaskan Guru ke mata pelajaran dan kelas/rombel per tahun pelajaran, mengubah/mencabut dengan histori, dan melihat cakupan tugas. Guru hanya boleh mengelola bank soal/asesmen/ujian pada lingkup penugasannya. Tidak boleh akses kelas/ujian Guru lain.

## 7. KURIKULUM

Kurikulum Merdeka terintegrasi, bukan label Bank Soal saja. Kelola daftar dan versi kurikulum, tahun berlaku, status, struktur, fase, kelas, mapel, CP, TP, ATP, registrasi kurikulum madrasah per tahun pelajaran, pemetaan dan histori. Siapkan extensibility untuk Kurikulum Berbasis Cinta/Panca Cinta, Pembelajaran Mendalam, dan kurikulum berikutnya. Guru menggunakan struktur kurikulum saat authoring pembelajaran, bank soal, asesmen, ujian; Admin mengelola penerapan/struktur. Jangan menghapus versi/riwayat yang telah dipakai ujian.

## 8. MANAJEMEN UJIAN, RUANG, DAN PROKTOR

### 8.1 Daftar ujian
Admin melihat nama, mapel, Guru pembuat, kelas/rombel, tahun pelajaran, jadwal, durasi, status, jumlah peserta, status mulai/submit. Admin tidak otomatis boleh mengedit soal, kunci, atau konfigurasi akademik milik Guru.

### 8.2 Ruang
CRUD ruang, kapasitas, status, penempatan peserta, daftar peserta, Proktor yang ditugaskan, histori. Room hanya mengatur penempatan dan cakupan monitoring; **Room bukan admission gate untuk start/resume Attempt**.

### 8.3 Penugasan Proktor
Admin memilih Exam, Room, akun Guru, interval waktu, alasan. Catat assigned_by, revoked_by, waktu, status, reason dan audit. Proktor adalah assignment sementara, bukan role permanen. Penugasan berbatas waktu dan memakai interval [starts_at, ends_at). Adjacent 07:00–09:00 dan 09:00–11:00 tidak overlap. Cegah overlap untuk Guru yang sama pada waktu bersamaan, termasuk request concurrent melalui transaksi/locking yang benar. Proktor hanya melihat minimum identitas peserta, kehadiran, status/progres di Room yang ditugaskan. Tidak boleh melihat jawaban, kunci, bank soal, konfigurasi, nilai, atau Room lain kecuali hak terpisah yang eksplisit.

### 8.4 Mode operasional
**Mode A — Guru mandiri:** Guru mengelola ujian sendiri sesuai kelas/mapel yang ditugaskan; tidak perlu assignment Proktor Admin.
**Mode B — Ujian terpusat:** Admin menugaskan Guru sebagai Proktor untuk Exam+Room; roster lintas kelas hanya berdasarkan penempatan resmi Admin; akses Proktor terbatas pada ruangnya.

### 8.5 Model serentak
Jadwal Exam `start_at/end_at`, waktu server resmi. Token dibuat saat publish, dapat dilihat sebelum jadwal, Siswa tidak boleh mulai sebelum jadwal. Jangan implementasikan wave/session/session_number/ExamSession/SessionRoom sebagai lifecycle operasional atau syarat admission. Pertahankan kompatibilitas historis seperlunya tanpa mengaktifkan model lama.

## 9. MONITORING CBT — DIHAPUS DARI WEB ADMIN

Tidak ada menu, halaman, widget, panel dashboard, endpoint, atau tindakan Monitoring CBT pada Web Admin Madrasah. Admin tidak memantau ujian berjalan, kehadiran, progres peserta, Attempt, perangkat, sinkronisasi, maupun status teknis peserta. Admin juga tidak melakukan Reset Attempt, Force Submit, perpanjangan waktu, release/takeover perangkat dari Web Admin. Informasi administratif dasar tentang daftar ujian dan jadwal tetap boleh ditampilkan di Manajemen Ujian, tetapi tanpa status real-time ujian/peserta. Laporan hasil/rekap akademik pada Bagian 10 tetap berlaku dan bukan monitoring ujian berjalan.

## 10. LAPORAN DAN CETAK

Akademik: rekap Siswa per kelas/rombel/tahun, Guru, mapel, penugasan.
Ujian: daftar ujian, peserta, kehadiran, belum mulai, sedang mengerjakan, selesai, rekap per mapel/kelas/tahun.
Hasil: nilai per Siswa/kelas/mapel/ujian, distribusi, histori, status penilaian. Essay dinilai manual oleh Guru/pemeriksa berwenang; Admin tidak otomatis menjadi penilai.
Fitur: filter, preview, cetak PDF, export XLSX, kop madrasah, header/footer, tanggal, nomor halaman bila diperlukan. Semua export tunduk pada authorization dan audit; jangan menyertakan credential.

## 11. AUDIT DAN KEAMANAN

Catat login sukses/gagal, logout, reset password, CRUD Guru/Siswa, import/export, perubahan tahun/rombel/mapel/penugasan, branding, publish/perubahan jadwal ujian, assignment/revoke Proktor, Reset Attempt, Force Submit, perpanjangan waktu, tindakan lease/perangkat, pengaturan, akses ditolak. Log memuat actor, waktu, action, target, hasil, alasan; jangan log password/token/secret.

Setiap route, controller, Livewire action, query detail, search, import, export, download, dan mutation harus memvalidasi installation/tenant, role, permission, ownership, teaching assignment, class/subject scope, proctor assignment, exam status/schedule. Jangan mengandalkan hide menu/tombol. Cegah IDOR, mass assignment, SQL injection, XSS, CSRF, upload berbahaya, enumeration, privilege escalation, cross-tenant access, duplicate/race, dan kebocoran data melalui export/log.

## 12. PENGATURAN WEB

### 12.1 Umum
Nama aplikasi, deskripsi, zona waktu, format tanggal/waktu/angka, bahasa, tahun pelajaran aktif, preferensi.

### 12.2 Branding
Nama aplikasi, logo, favicon, warna utama/sekunder, login title/description/welcome, bantuan, footer, website/media sosial. Scope per instalasi.

### 12.3 Kop dan cetak
Identitas instansi, logo, alamat/kontak/website, garis kop, margin, header/footer, nomor halaman, format tanggal, preview.

### 12.4 Akun Admin
Profil sendiri, ubah password, preferensi, notifikasi, sesi sesuai kemampuan. Admin tidak mengelola secret infrastruktur.

### 12.5 Tema dan responsivitas
Light, Dark, System (default mengikuti preferensi perangkat), semantic design tokens, sidebar responsif, desktop/tablet/mobile, kontras WCAG 2.2 AA. Uji seluruh halaman dan komponen.

### 12.6 Batas pengaturan
Jangan expose DB credentials, .env, private key, signing key, server secret, deployment settings sensitif dalam form Admin Madrasah.

## 13. PORTAL GURU DAN SISWA — DEPENDENSI MENU ADMIN

Walaupun dokumen ini berfokus Admin, Admin harus mendukung alur portal lain:
- Guru: dashboard, penugasan saya, bank soal/media, kurikulum CP/TP/ATP, asesmen, builder ujian, publish/token/jadwal/peserta, ruang/Proktor bila ditugaskan, penilaian essay dan laporan sesuai izin.
- Guru boleh membuat/menerbitkan ujian tanpa persetujuan Admin selama sesuai penugasan.
- Menu Proktor hanya muncul pada akun Guru ketika assignment aktif dan hanya menampilkan Exam/Room yang ditugaskan.
- Siswa: profil minimum, daftar ujian, token, aturan ujian, start sesuai jadwal server, pengerjaan, autosave/penyimpanan jawaban, resume sesuai eligibility, submit, status hasil bila diizinkan.
- Siswa bukan Proktor. Tidak ada portal Operator/Kamad/Proktor permanen.

## 14. STANDAR UX DAN KUALITAS

- UI profesional, premium, ringan, konsisten, tidak berlebihan.
- Sidebar dengan menu sesuai permission.
- Search, filter, sort, pagination.
- Form dengan validasi inline dan server-side.
- Konfirmasi untuk hapus/nonaktifkan/tindakan sensitif.
- Empty/loading/error/success states.
- Preview sebelum import, cetak, atau perubahan massal.
- Tidak boleh ada data dummy di production.
- Aksesibilitas keyboard, label, kontras, fokus, responsive.
- Hindari duplikasi menu dan istilah yang membingungkan.

## 15. ACCEPTANCE CHECKLIST AGENT

Agent wajib membuat matriks per fitur dengan status: **EXISTS & VERIFIED / EXISTS BUT BROKEN / MISSING / BLOCKED / DEFERRED**. Jangan tandai VERIFIED hanya karena route atau komponen ditemukan.

Untuk setiap menu/submenu:
1. Audit route, controller, model, policy, Livewire component, view, migration, seeder, test.
2. Buktikan flow UI sampai database dan kembali.
3. Verifikasi authorization negatif (Guru lain, Siswa lain, Proktor Room lain, tenant/install lain).
4. Verifikasi validasi input, idempotensi, transaksi, race condition, audit.
5. Verifikasi pagination/filter/search dan export.
6. Tambahkan feature/integration tests; MySQL 8/InnoDB wajib untuk constraint/locking/concurrency.
7. Jalankan test dan laporkan command, environment, hasil, jumlah test/assertion. Jika tidak dijalankan, tulis NOT RUN.
8. Jangan menyatakan siap produksi sebelum E2E, keamanan, stabilitas, recovery, dan UAT madrasah dibuktikan.

### Minimum test import
- Template valid; file corrupt; MIME palsu; ukuran terlalu besar; header hilang; baris invalid; duplikat internal; existing identity; re-import file sama; transaksi gagal di tengah; import dua request bersamaan; rombel/tahun tidak valid; export tanpa password.

### Minimum test Admin/authorization
- Admin madrasah sendiri dapat mengelola data madrasah.
- Tidak ada akses lintas instalasi/database.
- Guru tidak dapat mengubah master data Admin.
- Guru hanya mengakses assignment miliknya.
- Proktor hanya Room assignment aktif.
- Siswa tidak dapat membuka portal/fitur Proktor.
- IDOR pada detail, update, delete, export, dan download ditolak.

### Minimum test Exam
- Token dibuat saat publish.
- Sebelum start_at ditolak; pada jadwal yang sah diizinkan.
- Setelah end_at ditolak sesuai aturan.
- Admission tidak bergantung pada Room atau ExamSession.
- Room memengaruhi monitoring/placement, bukan hak start/resume.
- Guru mandiri dan ujian terpusat diuji terpisah.
- Proctor overlap/adjacent interval diuji pada MySQL concurrent.
- Reset/Force Submit/extension/release memerlukan permission, reason, transaction, audit.

## 16. ATURAN KERJA AGENT DAN PELAPORAN

Pisahkan **AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN**.
- Sebelum mengubah file, buat backup dan catat path + SHA-256.
- Jangan menimpa ZIP baseline asli.
- Perubahan hanya pada workspace/branch kerja.
- Jangan menjalankan migration pada database produksi saat pengembangan.
- Database VPS saat ini dinyatakan kosong oleh pengguna; verifikasi keadaan aktual melalui bukti aman sebelum install. Fresh install boleh direncanakan, tetapi jangan melakukan destructive action tanpa persetujuan eksplisit.
- Jangan menggunakan SSH sebagai syarat kerja; pengguna tidak memilikinya. Deployment harus melalui aaPanel UI/File Manager.
- Jangan mengubah Android/Super Admin sekarang.
- Jangan menganggap fitur selesai dari laporan agent sebelumnya; cocokkan source aktual dan commit.
- Jangan melakukan merge/rebase/cherry-pick/deploy atau migration produksi tanpa instruksi/persetujuan yang sesuai.
- Update checkpoint dan laporan setiap tahap.
- Setiap laporan mencatat branch, HEAD, file berubah, backup, test yang benar-benar dijalankan, blocker, dan langkah selanjutnya.

## 17. URUTAN PRIORITAS

P0 — Agar Web dapat digunakan:
1. Login dan role/permission Admin, Guru, Siswa.
2. Identitas madrasah, logo, kop, branding, pengaturan dasar.
3. Master Guru/Siswa dan import Excel aman.
4. Tahun pelajaran, kelas, rombel, mapel, penugasan Guru.
5. Bank soal dan builder/publish ujian sesuai penugasan Guru.
6. Token, jadwal server, peserta, ruang/placement.
7. Portal Siswa: login, daftar ujian, start, save, resume, submit.
8. Laporan minimum.
9. Audit log dan hardening akses.
10. Instalasi fresh melalui aaPanel dan UAT.

P1 — Penyempurnaan administrasi:
- laporan lanjutan, kop fleksibel, export lanjutan, analytics, notifikasi, UI polish, audit lanjutan.

DEFERRED sampai Juni 2027:
- Android native.
- Web Super Admin.
- Fitur yang hanya diperlukan oleh dua komponen deferred tersebut.



### Keputusan pengguna 23 November 2026 — pengecualian Monitoring CBT

Seluruh fungsi Monitoring CBT di Web Admin dikeluarkan dari ruang lingkup. Jangan membuat menu/halaman/panel/widget monitoring, status kehadiran/progres/Attempt/perangkat/sinkronisasi secara real-time, maupun tindakan Reset Attempt, Force Submit, perpanjangan waktu, release/takeover perangkat dari Web Admin. Daftar ujian/jadwal dan laporan hasil/rekap akademik tetap boleh ada sesuai bagian masing-masing, tanpa monitoring ujian berjalan.

## 18. INSTRUKSI PERTAMA UNTUK AGENT PENGGANTI

Jangan langsung coding. Lakukan:
1. Baca dokumen ini dan keputusan terbaru pengguna.
2. Refresh repository, branch, HEAD, status working tree, issue/PR, CI dan commit aktual.
3. Audit seluruh menu Web yang sudah ada terhadap checklist ini.
4. Buat matriks fitur: sudah ada/terverifikasi, ada tetapi rusak, belum ada, blocker, deferred.
5. Temukan konflik source antar-agent dan tetapkan file ownership.
6. Berikan laporan gap dan rencana P0 dengan file/class/route/database/test yang spesifik.
7. Minta persetujuan pengguna untuk patch yang berisiko; jangan mengulang keputusan produk yang sudah ditetapkan.

**Prinsip akhir:** menu yang terlihat bukan bukti fitur bekerja. Hanya source, alur runtime, authorization, transaksi, pengujian, dan bukti operasional yang dapat menjadi dasar status selesai.
