# SPESIFIKASI INDUK WEB ADMIN MADRASAH
## Audit, Redesain Total, Pengembangan Fitur, Keamanan, dan Kriteria Penerimaan
**Proyek:** CBT MADRASAH NOVEMBER 2026  
**Ruang lingkup dokumen:** Portal Web Admin Madrasah saja  
**Branch dokumentasi:** `docs/admin-web-spec-20261121`  
**Baseline source yang menjadi titik awal:** `fix/web-portal-usability-rbac-20261120`  
**Baseline commit yang pernah diaudit:** `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`

> Dokumen ini adalah spesifikasi produk dan mandat kerja agent. Ini bukan klaim bahwa seluruh fitur sudah diimplementasikan. Agent wajib memeriksa ulang source, branch, commit, dan kondisi aktual sebelum coding. Dokumen ini tidak memberi izin deployment, perubahan database produksi, atau perubahan scope remediasi P0.1/P0.2/P0.3.

---

# 1. MANDAT DAN BATAS RUANG LINGKUP

Agent bertanggung jawab menyusun ulang dan menyempurnakan keseluruhan **Web Admin Madrasah** secara menyeluruh: struktur informasi, dashboard, navigasi, halaman administrasi, formulir, tabel, impor/ekspor, laporan administratif, pengaturan, pemeliharaan, keamanan, aksesibilitas, responsivitas, dan performa.

**Fokus hanya Web Admin Madrasah.** Jangan melakukan audit, redesign, atau implementasi Portal Guru maupun Web Siswa sebagai bagian dari pekerjaan ini. Jangan mengubah fitur operasional CBT yang merupakan tanggung jawab Guru. Jika file bersama/layout bersama harus disentuh agar Portal Admin berfungsi, perubahan harus dibatasi pada kebutuhan Admin, tidak mengubah alur atau kewenangan portal lain, dan dicatat jelas.

Jangan membangun ulang aplikasi dari nol. Pertahankan komponen dan fungsi yang masih benar, perbaiki yang kurang, dan tambahkan yang belum tersedia berdasarkan audit bukti.

## 1.1 Hasil akhir yang dituju

Web Admin harus menjadi portal administrasi madrasah yang profesional, lengkap, mudah dipahami, ringan, responsif, aman, dan benar-benar dapat digunakan untuk mengelola data madrasah. Bukan dashboard template yang hanya berisi beberapa kartu angka dan tautan CRUD.

## 1.2 Definisi Admin

Admin Madrasah mengelola administrasi dan data induk madrasah, termasuk identitas, periode akademik, data Guru/Siswa, rombel, penugasan, master mata pelajaran, master Kurmer/KBC, impor data, laporan administratif, konfigurasi, audit administratif, dan pemeliharaan instalasi.

Admin **bukan** pemilik operasional CBT harian. Admin tidak membuat/mengelola bank soal, membuat/menerbitkan ujian Guru, memonitor pengerjaan sebagai operator ujian, menilai, atau menganalisis hasil ujian Guru. Statistik ujian agregat untuk kebutuhan eksekutif boleh ditampilkan tanpa mengambil alih fungsi operasional Guru. Akses dukungan teknis harus dibatasi permission khusus, alasan, scope, dan audit log.

---

# 2. ATURAN KERJA WAJIB

1. Gunakan Bahasa Indonesia pada dokumentasi dan laporan.
2. Pisahkan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN, DOKUMENTASI.
3. Jangan coding sebelum audit dan desain diserahkan untuk ditinjau.
4. Jangan mengklaim fitur tersedia hanya karena route/model/migration ada. Periksa UI, aksi backend, validasi, permission, persistensi, dan test.
5. Jangan mengklaim test lulus tanpa menjalankannya dan menyimpan bukti.
6. Jangan menghapus source, data, histori akademik, migration lama, atau file yang belum dipahami.
7. Backup file sebelum perubahan. Catat path, SHA-256 sebelum/sesudah, dan hasil verifikasi backup.
8. Jangan mengubah ZIP baseline asli `CBT REVISI 6 NOPEMBER 2026.zip`.
9. Jangan menjalankan migration eksperimental pada database produksi.
10. Tidak ada deployment VPS, perubahan .env produksi, APP_KEY, konfigurasi Nginx, atau data produksi tanpa persetujuan eksplisit.
11. Jangan menjalankan Gradle melalui terminal; Android berada di luar scope.
12. Jangan menyentuh Device Lease/Fencing; feature flag tetap OFF.
13. Jangan memulai atau mengubah pekerjaan P0.1/P0.2/P0.3 tanpa persetujuan terpisah.
14. Jangan mengubah branch kandidat lama atau melakukan force-push.
15. Jangan merge PR tanpa persetujuan.
16. Source aktual adalah dasar kebenaran. Bila laporan lama berbeda, catat perbedaan.
17. Jangan meminta file/keputusan diulang sebelum seluruh sumber yang tersedia diperiksa.
18. Jika ada ketidakpastian, tandai BELUM TERVERIFIKASI; jangan mengisi dengan asumsi.
19. Perubahan harus kompatibel dengan instalasi satu madrasah satu database.
20. Setiap tahap menghasilkan laporan dan checkpoint yang dapat dilanjutkan agent berikutnya.

---

# 3. ARSITEKTUR DAN KEPUTUSAN PRODUK YANG TIDAK BOLEH BERUBAH

## 3.1 Teknologi

- Backend: Laravel 13, PHP 8.3.
- Web: Blade dan Livewire.
- Database: MySQL 8+ dengan InnoDB.
- API: Laravel Sanctum sesuai penggunaan.
- Arsitektur: Modular Monolith.
- VPS: aaPanel, Nginx, PHP 8.3, MySQL 8+, Redis opsional.

Verifikasi versi aktual sebelum implementasi.

## 3.2 Model instalasi

**Satu madrasah = satu instalasi = satu database.**

Setiap madrasah memiliki domain, database, `.env`, APP_KEY, storage, secrets, backup, dan lifecycle deployment sendiri. Tidak ada shared operational database atau pertukaran data akademik antarmadrasah.

Mekanisme `tenant_id`, TenantContext, middleware, global scope, policy, dan validasi tenant tetap dipertahankan sebagai lapisan isolasi internal. Jangan menghapus mekanisme tenant karena model deployment satu database per madrasah.

## 3.3 Kurikulum yang didukung

**Hanya Kurikulum Merdeka (Kurmer) dan Kurikulum Berbasis Cinta (KBC).**

- Jangan menyediakan pilihan Kurikulum 2013 atau kurikulum lain.
- Jangan membangun master/menu/konfigurasi untuk kurikulum selain Kurmer/KBC.
- Jangan menghapus data historis kurikulum lain secara otomatis.
- Data historis, nilai, ujian, dan arsip akademik harus tetap utuh. Pembersihan data historis memerlukan audit dependensi dan persetujuan terpisah.

## 3.4 Struktur fase MI

- Fase A: kelas 1–2.
- Fase B: kelas 3–4.
- Fase C: kelas 5–6.

## 3.5 Batasan operasional CBT

Bank soal, media soal, manajemen ujian, penerbitan ujian, monitoring operasional, penugasan proktor, penilaian, hasil, analisis, dan laporan ujian merupakan tanggung jawab Guru. Jangan memasukkannya sebagai modul pengelolaan Admin. Dashboard Admin hanya boleh menyajikan statistik agregat/eksekutif dan status sistem yang relevan, dengan akses minimum.

---

# 4. AUDIT SOURCE SEBELUM REDESAIN

## 4.1 Repository

Repository: https://github.com/zane0184/cbt_november

Branch kandidat Web yang pernah diperiksa: `fix/web-portal-usability-rbac-20261120`  
Commit historis: `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`  
PR historis: #5

Informasi tersebut hanya baseline historis. Agent wajib memeriksa status terbaru branch, PR, commit, dan perubahan setelah baseline. Branch kerja dokumentasi ini bukan branch implementasi.

## 4.2 Komponen Admin yang pernah ditemukan

- `backend-laravel/app/Livewire/Operator/OperatorDashboard.php`
- `backend-laravel/app/Livewire/Admin/UserManagement.php`
- `backend-laravel/app/Livewire/Admin/StudentRombelManagement.php`
- `backend-laravel/app/Livewire/Admin/AcademicManagement.php`
- `backend-laravel/app/Livewire/Admin/CurriculumManagement.php`
- Layout: `backend-laravel/resources/views/layouts/app.blade.php`
- View Admin: `backend-laravel/resources/views/livewire/admin/`
- Route: `backend-laravel/routes/web.php`

Pernah ditemukan route:
- `/operator/dashboard`
- `/admin/users`
- `/admin/rombel-students`
- `/admin/academic`
- `/admin/curriculum`

Daftar ini bukan inventaris final. Audit ulang seluruh recursive tree dan implementasi aktual.

## 4.3 Temuan historis yang harus diverifikasi ulang

- Dashboard Operator/Admin pernah menampilkan statistik pengguna, siswa, guru, rombel, mata pelajaran, ujian, pengguna terbaru, dan rombel terbaru.
- Manajemen pengguna memiliki aksi tambah pengguna; kelengkapan edit/nonaktif/reset harus diverifikasi.
- Manajemen akademik pernah memiliki tab rombel, mata pelajaran, tingkat kelas, serta form tambah rombel/mapel.
- Manajemen rombel-siswa pernah memiliki aksi penempatan siswa.
- Manajemen kurikulum pernah memuat CP dan TP serta pembuatan TP.
- Layout pernah memakai Tailwind CDN; audit apakah masih demikian dan rencanakan asset build lokal untuk produksi.
- Screenshot Web aktual yang diberikan pengguna menunjukkan dashboard terlalu minimal, informasi sedikit, kartu statistik melebar/terpotong pada viewport, identitas madrasah kurang terlihat, dan navigasi belum menggambarkan administrasi madrasah lengkap.

Status fitur harus diklasifikasikan:
1. SUDAH ADA DAN BERFUNGSI — bukti UI + backend + test.
2. ADA TAPI BELUM LENGKAP.
3. ADA DI BACKEND, UI BELUM ADA.
4. ADA DI UI, BACKEND BELUM MEMADAI.
5. BELUM ADA.
6. BERMASALAH/BERISIKO.
7. BELUM DAPAT DIVERIFIKASI.

Setiap baris matriks wajib mencantumkan path file, route, komponen, aksi, permission, sumber data, test, gap, dan rekomendasi.

---

# 5. INFORMASI ARSITEKTUR DAN DESAIN WEB ADMIN

## 5.1 Prinsip UI/UX

Target tampilan:
- Premium dan profesional.
- Modern dan elegan.
- Konsisten.
- Ringan dan cepat.
- Responsif desktop, tablet, mobile.
- Mudah digunakan oleh staf administrasi madrasah.
- Bahasa antarmuka Indonesia yang jelas.
- Tidak terlalu padat, tetapi informatif.
- Tidak menggunakan dekorasi berlebihan.
- Memiliki empty state, loading state, error state, dan success state yang jelas.

Jangan hanya mengganti warna. Redesign struktur informasi, hirarki, layout, dashboard, navigasi, tabel, form, dan alur kerja.

## 5.2 Kerangka layout

Wajib dirancang:
- Sidebar Admin dengan grup menu yang jelas.
- Header/topbar.
- Identitas madrasah.
- Konteks tahun ajaran dan semester aktif.
- Profil akun dan logout.
- Breadcrumb untuk halaman bertingkat.
- Konten utama.
- Notifikasi sistem yang aman.
- Responsive sidebar untuk layar kecil.
- Active menu dan permission-aware navigation.

Menu tersembunyi di UI tidak menggantikan authorization backend.

## 5.3 Design system

Tetapkan design tokens:
- Warna primer, sekunder, aksen, background, surface, border, text, success, warning, danger, info.
- Tipografi dan skala ukuran.
- Spacing.
- Radius.
- Shadow.
- Ukuran ikon.
- Komponen button, input, select, checkbox, badge, alert, modal, card, table, tabs, pagination, dropdown, breadcrumb.
- Fokus keyboard dan kontras warna.
- Konsistensi tampilan empty/loading/error.

Jaga agar CSS/JS dikelola oleh asset build aplikasi. Hindari ketergantungan runtime pada CDN eksternal jika dapat dibundel lokal.

## 5.4 Responsivitas

Uji sekurangnya:
- Desktop lebar.
- Laptop 1366×768.
- Tablet portrait/landscape.
- Mobile 390×844 dan ukuran kecil.

Tidak boleh ada overflow horizontal yang tidak disengaja, kartu terpotong, tabel yang tidak dapat digunakan, atau tombol penting yang keluar viewport. Tabel besar dapat memakai scroll internal terkontrol atau tampilan kartu pada mobile.

---

# 6. STRUKTUR NAVIGASI ADMIN YANG DITUJU

Struktur ini adalah target desain awal. Setelah audit, agent boleh mengusulkan pengelompokan lebih baik, tetapi tidak boleh menghilangkan kebutuhan fitur tanpa persetujuan.

## 6.1 Dashboard
- Dashboard Eksekutif.
- Kesehatan Sistem.
- Aktivitas Administratif.

## 6.2 Identitas Madrasah
- Profil Madrasah.
- Identitas Kepala Madrasah.
- Logo dan Identitas Visual.
- Kop Dokumen dan Pengaturan Cetak.
- Tanda Tangan Dokumen.

## 6.3 Manajemen Akademik
- Tahun Ajaran.
- Semester.
- Tingkat Kelas.
- Fase.
- Rombongan Belajar.
- Wali Kelas.
- Penempatan Siswa.
- Kenaikan Kelas.
- Kelulusan.
- Riwayat Akademik.

## 6.4 Manajemen Pengguna
- Data Guru.
- Data Siswa.
- Akun dan Status.
- Penugasan Guru.
- Guru Kelas.
- Guru Mata Pelajaran.
- Riwayat Penugasan.
- Reset akses secara aman.

## 6.5 Master Kurikulum
- Mata Pelajaran.
- Impor Mata Pelajaran.
- Capaian Pembelajaran (CP).
- Tujuan Pembelajaran (TP).
- Alur Tujuan Pembelajaran (ATP).
- Panca Cinta.
- Delapan Dimensi Profil Lulusan.
- Pembelajaran Mendalam.
- Versi dan Riwayat Kurmer/KBC.

## 6.6 Impor dan Sinkronisasi Data
- Impor Siswa.
- Template Siswa.
- Impor Guru.
- Template Guru.
- Impor Mata Pelajaran.
- Template Mata Pelajaran.
- Validasi dan Pratinjau.
- Riwayat Impor.
- Integrasi EMIS (hanya jika API resmi dan akses tersedia).

## 6.7 Laporan Administratif
- Statistik Siswa.
- Statistik Guru.
- Rekap Rombel.
- Rekap Mata Pelajaran.
- Rekap Periode Akademik.
- Riwayat Kenaikan Kelas.
- Riwayat Kelulusan.
- Aktivitas Administratif.

Jangan menambahkan laporan nilai/analisis ujian operasional sebagai fungsi Admin. Jika Admin memerlukan ringkasan eksekutif, tampilkan agregat minimum yang diizinkan tanpa mengambil alih data kerja Guru.

## 6.8 Pemeliharaan Sistem
- Status Sistem.
- Backup Database.
- Backup Media.
- Restore.
- Riwayat Backup/Restore.
- Audit Log Administratif.
- Konfigurasi instalasi yang aman.

## 6.9 Pengaturan
- Profil Admin.
- Preferensi tampilan.
- Notifikasi administratif.
- Pengaturan dokumen.
- Hak akses administratif sesuai permission registry.
- Konfigurasi madrasah yang diizinkan.

---

# 7. DETAIL FITUR DASHBOARD EKSEKUTIF

Dashboard Admin bukan halaman kosong dengan beberapa angka.

## 7.1 Header konteks

Tampilkan:
- Nama dan logo madrasah.
- Sapaan dan identitas Admin.
- Tahun ajaran aktif.
- Semester aktif.
- Status konfigurasi profil madrasah.
- Informasi waktu pembaruan data bila relevan.

## 7.2 Kartu statistik

- Total siswa aktif.
- Siswa per kelas.
- Siswa per fase.
- Total Guru aktif.
- Guru kelas.
- Guru mata pelajaran.
- Total rombel aktif.
- Total mata pelajaran.
- Jumlah mapel Mulok.
- Kelengkapan data administrasi.

Semua angka harus berasal dari query backend yang benar dan sesuai definisi status aktif. Jangan menghitung semua user ber-role SISWA sebagai siswa aktif tanpa memeriksa profil, status, dan periode akademik.

## 7.3 Visualisasi

- Distribusi siswa per kelas.
- Distribusi siswa per fase.
- Komposisi Guru.
- Perkembangan jumlah siswa antarperiode jika histori mendukung.
- Aktivitas administrasi.
- Ringkasan agregat ujian hanya jika memang diperlukan untuk eksekutif dan aman.

Grafik wajib memiliki label, periode, empty state, dan sumber data yang jelas.

## 7.4 Kesehatan sistem

- Koneksi database.
- Status storage.
- Kapasitas penyimpanan.
- Queue/scheduler bila digunakan.
- Status backup terakhir.
- Kegagalan job yang aman ditampilkan.
- Versi aplikasi.
- Peringatan konfigurasi.

Jangan membocorkan detail stack trace, secrets, token, credential, APP_KEY, atau data internal sensitif.

## 7.5 Aktivitas terbaru

Tampilkan aktivitas administratif seperti:
- Penambahan Guru.
- Penambahan Siswa.
- Perubahan rombel.
- Impor data.
- Perubahan identitas madrasah.
- Perubahan periode akademik.
- Kenaikan kelas/kelulusan.
- Backup/restore.

Gunakan audit log yang benar, bukan data buatan.

---

# 8. IDENTITAS MADRASAH

Field yang dirancang:
- NSM.
- NPSN.
- Nama resmi madrasah.
- Jenjang.
- Alamat.
- RT/RW bila diperlukan.
- Desa/kelurahan.
- Kecamatan.
- Kabupaten/kota.
- Provinsi.
- Kode pos.
- Telepon.
- Email.
- Website.
- Logo madrasah.
- Logo/identitas Kemenag sesuai aturan penggunaan.
- Nama Kepala Madrasah.
- NIP bila ada.
- Jabatan.
- Aset tanda tangan.
- Pengaturan kop dan footer dokumen.

Validasi:
- Format NSM/NPSN.
- File gambar dan ukuran.
- Hak akses upload.
- Riwayat perubahan.
- Preview dokumen.
- Proteksi file dari akses publik tidak sah.

Tanda tangan berupa gambar bukan otomatis tanda tangan elektronik tersertifikasi. Jangan membuat klaim legal demikian.

---

# 9. MANAJEMEN TAHUN AJARAN, SEMESTER, ROMBEL, FASE

## 9.1 Periode akademik

- Membuat periode tahun ajaran.
- Menetapkan semester Ganjil/Genap.
- Menentukan periode aktif.
- Mencegah dua periode aktif yang saling bertentangan.
- Menyimpan histori periode.
- Tidak menghapus histori lama.

## 9.2 Rombel

- Kode/nama rombel.
- Tingkat kelas.
- Fase otomatis dari tingkat.
- Tahun ajaran.
- Wali kelas.
- Kapasitas jika digunakan.
- Status.
- Daftar siswa.
- Histori rombel.

## 9.3 Kenaikan kelas

Alur:
1. Pilih tahun ajaran asal.
2. Pilih tahun ajaran tujuan.
3. Ambil siswa aktif.
4. Tampilkan mapping kelas asal/tujuan.
5. Atur siswa tinggal kelas/pindah/keluar sesuai kebijakan.
6. Validasi siswa ganda dan kelas tujuan.
7. Pratinjau.
8. Konfirmasi.
9. Proses transaksional.
10. Simpan histori dan audit log.
11. Tampilkan laporan hasil.

Tidak boleh mengubah nilai lama atau histori ujian.

## 9.4 Kelulusan

- Pilih angkatan/periode.
- Validasi siswa kelas akhir.
- Pratinjau.
- Konfirmasi.
- Tandai status alumni/lulus.
- Pertahankan akun dan histori sesuai kebijakan.
- Simpan audit log.

Jangan menghapus akun atau riwayat siswa secara otomatis saat kelulusan.

---

# 10. MANAJEMEN GURU DAN SISWA

## 10.1 Data Guru

- Tambah.
- Edit.
- Detail.
- Aktif/nonaktif.
- Identitas.
- NIP/NIK jika relevan.
- Email/username.
- Status.
- Jenis Guru.
- Riwayat penugasan.
- Reset kredensial aman.

## 10.2 Penugasan Guru

Admin menetapkan:
- Guru kelas/wali kelas.
- Guru mata pelajaran.
- Kelas/rombel yang menjadi lingkup tugas.
- Mapel yang diampu.
- Periode penugasan.
- Status assignment.
- Riwayat perubahan.

Jangan menjadikan role global sebagai satu-satunya bukti hak mengakses kelas/mapel. Gunakan assignment yang aktif dan sesuai periode.

## 10.3 Data Siswa

- Tambah.
- Edit.
- Detail.
- Aktif/nonaktif.
- NISN.
- Nama.
- Jenis kelamin.
- Tanggal lahir jika diperlukan.
- Identitas akademik.
- Tahun masuk.
- Rombel aktif.
- Histori rombel.
- Status kelulusan/alumni.
- Akun dan reset PIN/password.

Data pribadi hanya dapat diakses sesuai kebutuhan dan permission.

---

# 11. TIGA IMPOR EXCEL WAJIB

**Ketiganya wajib memiliki template unduhan yang dapat digunakan.**

Alur standar:
1. Download template.
2. Upload file.
3. Validasi format dan versi template.
4. Validasi kolom.
5. Validasi tipe dan data wajib.
6. Validasi referensi database.
7. Deteksi duplikasi.
8. Pratinjau.
9. Konfirmasi.
10. Simpan aman.
11. Tampilkan ringkasan.
12. Unduh laporan error.
13. Simpan riwayat impor.

Tidak boleh ada proses impor langsung tanpa pratinjau dan konfirmasi.

## 11.1 Impor Siswa

Template minimal:
- NISN.
- Nama lengkap.
- Jenis kelamin.
- Tanggal lahir bila diperlukan.
- Tahun masuk bila diperlukan.
- Kelas.
- Rombel.
- Identitas akun/username sesuai desain.
- PIN/password awal hanya jika prosedur aman telah ditetapkan.

Catatan:
- Jangan menyimpan password plaintext.
- Jangan menyimpan PIN plaintext.
- Jangan menganggap NISN selalu tersedia; definisikan penanganan data kosong secara eksplisit.
- Duplikasi harus ditangani tanpa merusak data yang sudah ada.
- Template dapat dipetakan ke struktur EMIS bila format resmi telah diverifikasi.

## 11.2 Impor Guru

Template minimal:
- Nama lengkap.
- NIP.
- NIK bila diperlukan.
- Email.
- Username sesuai desain.
- Jenis Guru.
- Guru Kelas/Guru Mapel.
- Kode mapel.
- Kode kelas/rombel.
- Tahun ajaran/periode penugasan.
- Status.

Penugasan harus divalidasi terhadap master mapel, rombel, dan periode yang ada.

## 11.3 Impor Mata Pelajaran

Template minimal:
- Kode mapel.
- Nama mapel.
- Kelompok mapel.
- Jenis mapel.
- Status aktif.
- Keterangan.
- Kode referensi eksternal (opsional).
- Penanda Mulok.
- Nama lokal/daerah atau keterangan Mulok bila relevan.

Kelompok yang wajib:
- Umum.
- PAI.
- Bahasa Arab.
- Mulok.

Contoh ilustratif:

| Kode | Nama | Kelompok | Jenis |
|---|---|---|---|
| MTK | Matematika | Umum | Nasional |
| BIND | Bahasa Indonesia | Umum | Nasional |
| AQH | Al-Qur'an Hadis | PAI | Nasional |
| AA | Akidah Akhlak | PAI | Nasional |
| FIK | Fikih | PAI | Nasional |
| SKI | Sejarah Kebudayaan Islam | PAI | Nasional |
| BAR | Bahasa Arab | Bahasa Arab | Nasional |
| BTQ | Baca Tulis Al-Qur'an | Mulok | Lokal |
| BING | Bahasa Inggris | Mulok | Lokal |

Contoh di atas bukan daftar/kode resmi. Kode mapel harus diverifikasi terhadap referensi yang digunakan madrasah/RDM. Jangan mengunci kode eksternal sebagai primary key internal. Gunakan internal ID dan mapping kode eksternal.

Mulok harus menjadi kategori eksplisit, dapat lebih dari satu mapel, dan dapat berbeda antar madrasah.

## 11.4 Keamanan impor

- Validasi MIME dan ekstensi.
- Batasi ukuran dan jumlah baris.
- Proteksi formula injection pada file ekspor.
- Validasi di server.
- Hindari mass assignment yang tidak terkontrol.
- Batasi akses file upload.
- Gunakan transaksi/batch yang dapat dipulihkan.
- Catat row number, kolom, dan alasan gagal.
- Jangan menimpa data eksisting tanpa aturan eksplisit.
- Jangan menghapus data yang tidak ada pada file impor.
- Audit log menyimpan actor, waktu, jumlah, dan status.
- File sementara dibersihkan aman.
- Template mempunyai nomor versi.

Definisikan strategi atomicity: jika batch parsial diperbolehkan, tampilkan hasil parsial secara jelas; jika mode atomic, kegagalan harus rollback seluruh batch. Jangan mencampur dua perilaku tanpa dokumentasi.

---

# 12. MASTER KURIKULUM KURMER/KBC

## 12.1 Mata Pelajaran MI

Master mapel mendukung:
- Mapel umum.
- Rumpun PAI: Al-Qur'an Hadis, Akidah Akhlak, Fikih, SKI.
- Bahasa Arab.
- Mulok.

Harus ada kode internal stabil dan mapping kode eksternal/RDM. Jangan hardcode kode eksternal sebagai satu-satunya identitas.

## 12.2 Fase

- A: kelas 1–2.
- B: kelas 3–4.
- C: kelas 5–6.

## 12.3 CP

- Master CP berdasarkan mapel/fase/versi/tahun berlaku.
- Status aktif.
- Sumber referensi.
- Histori revisi.
- Hubungan dengan TP.

## 12.4 TP

- Terhubung dengan CP.
- Mapel dan fase sesuai.
- Dapat dipilih Guru sesuai assignment.
- Memiliki urutan/status/versi jika diperlukan.
- Histori dipertahankan.

## 12.5 ATP

- Susunan TP.
- Urutan pembelajaran.
- Periode/tahun berlaku.
- Hubungan CP.
- Riwayat revisi.
- Validasi konsistensi.

## 12.6 KBC/Panca Cinta

Master 5 Pilar Panca Cinta harus mengikuti referensi resmi yang telah ditetapkan proyek. Jangan mengarang definisi atau isi. Setiap referensi dapat memiliki deskripsi, status, sumber, dan versi.

## 12.7 Pembelajaran Mendalam dan 8 Dimensi Profil Lulusan

Sediakan referensi dan struktur yang dapat dipakai dalam integrasi pembelajaran dan asesmen. Nama dan definisi harus bersumber dari dokumen resmi yang diverifikasi, bukan asumsi agent.

## 12.8 Versioning dan histori

- Kurmer/KBC mempunyai versi/tahun berlaku.
- Perubahan master tidak mengubah snapshot ujian historis.
- Jangan menghapus CP/TP yang telah direferensikan tanpa mekanisme arsip.
- Data akademik historis tidak boleh hilang.

---

# 13. LAPORAN ADMINISTRATIF DAN DOKUMEN

Fitur laporan Admin:
- Rekap siswa aktif per kelas/fase.
- Rekap Guru.
- Rekap rombel.
- Rekap mata pelajaran dan Mulok.
- Rekap penugasan Guru.
- Rekap tahun ajaran/semester.
- Rekap kenaikan kelas.
- Rekap kelulusan.
- Rekap kelengkapan identitas.
- Riwayat impor.

Dokumen cetak:
- Kop madrasah.
- Identitas Kepala Madrasah.
- Logo.
- Tanggal cetak.
- Identitas pengguna pembuat dokumen.
- Nomor dokumen bila sistem mengelola.
- Tanda tangan/gambar sesuai ketentuan.

Format:
- PDF.
- Excel/CSV sesuai kebutuhan.
- Print-friendly.

Jangan membuat laporan hasil/analisis ujian sebagai tugas Admin. Operasional nilai/hasil ujian tetap milik Guru.

---

# 14. BACKUP DAN RESTORE

## 14.1 Backup

Per instalasi madrasah:
- Database MySQL.
- Media gambar.
- Audio.
- Video jika digunakan.
- Dokumen pendukung.
- Metadata pemulihan.

Fitur:
- Backup manual oleh permission khusus.
- Backup terjadwal bila scheduler telah diverifikasi.
- Status/progress.
- Ukuran.
- Waktu.
- Actor.
- Checksum.
- Lokasi penyimpanan.
- Retention policy.
- Enkripsi jika memungkinkan.
- Riwayat.

Jangan menyimpan backup di folder publik.

## 14.2 Restore

Restore adalah operasi berisiko tinggi. Wajib:
- Permission khusus.
- Re-authentication.
- Konfirmasi eksplisit.
- Pemeriksaan checksum.
- Pemeriksaan versi aplikasi/schema.
- Pemeriksaan kecocokan instalasi.
- Backup kondisi saat ini.
- Rencana rollback.
- Audit log.
- Restore hanya pada target yang benar.
- Validasi integritas pascapemulihan.

Pengujian restore hanya pada database disposable/staging terisolasi. Jangan pernah menguji restore destruktif di produksi.

---

# 15. KEAMANAN, RBAC, TENANT, DAN AUDIT

Semua route, Livewire action, service, query, export, file download, dan proses impor harus memeriksa:
- Authenticated user.
- Role.
- Permission.
- TenantContext.
- Scope data.
- Assignment jika diperlukan.
- Status record.
- Ownership.

Jangan mengandalkan hide menu sebagai kontrol keamanan.

Wajib:
- Tenant isolation.
- Mass assignment protection.
- CSRF.
- Validasi server.
- Rate limiting untuk aksi sensitif.
- Secure upload/download.
- Audit log.
- Session lifecycle.
- Error handling aman.
- Proteksi IDOR.
- Proteksi export lintas tenant.
- Proteksi file media.
- Least privilege.

Aksi Admin yang sensitif:
- Reset akun.
- Perubahan identitas madrasah.
- Kenaikan kelas.
- Kelulusan.
- Impor massal.
- Backup.
- Restore.
- Perubahan permission.
- Perubahan periode akademik.

Aksi tersebut harus memiliki audit log dan otorisasi sesuai tingkat risiko.

Admin tidak boleh mendapatkan hak lintas tenant hanya karena role Admin. Model instalasi tetap satu madrasah satu database.

---

# 16. PERFORMA DAN KUALITAS

Audit:
- Query N+1.
- Query agregat dashboard.
- Index database.
- Pagination.
- Eager loading.
- Cache invalidation.
- Livewire payload.
- File upload.
- Import batch.
- Export besar.
- Asset bundling.
- Lazy loading.
- Memory usage.
- Timeout.

Dashboard tidak boleh menghitung semua data dengan query berulang yang mahal. Gunakan query agregat dan cache yang aman bila diperlukan. Cache harus mempertimbangkan tenant dan periode akademik.

Jangan mengorbankan akurasi data demi cache.

---

# 17. STRATEGI IMPLEMENTASI BERTAHAP

## Fase 0 — Source and baseline verification
- Verifikasi repository, branch, commit, PR.
- Periksa perubahan sejak baseline.
- Inventaris source Admin.
- Inventaris backup dan laporan.
- Catat baseline hash.
- Buat branch kerja implementasi terpisah dari branch dokumentasi.

## Fase 1 — Audit dan desain
- Matriks fitur.
- Matriks menu.
- Matriks permission.
- Wireframe.
- Data requirements.
- Gap analysis.
- Test plan.
- Ajukan untuk review.

## Fase 2 — Fondasi UI Admin
- Layout Admin.
- Sidebar.
- Header.
- Design system.
- Dashboard.
- Responsive fixes.
- Empty/loading/error states.

## Fase 3 — Identitas dan periode
- Profil madrasah.
- Kepala Madrasah.
- Logo/kop.
- Tahun ajaran.
- Semester.

## Fase 4 — Data akademik
- Kelas/fase.
- Rombel.
- Penempatan siswa.
- Wali kelas.
- Kenaikan kelas.
- Kelulusan.
- Histori.

## Fase 5 — Guru dan siswa
- CRUD lengkap.
- Status.
- Detail.
- Penugasan.
- Audit log.

## Fase 6 — Impor wajib
- Impor siswa + template.
- Impor Guru + template.
- Impor mapel + template Mulok.
- Pratinjau.
- Validasi.
- Error report.
- Riwayat.

## Fase 7 — Master Kurmer/KBC
- Mata pelajaran.
- CP.
- TP.
- ATP.
- Panca Cinta.
- Pembelajaran Mendalam.
- 8 Dimensi Profil Lulusan.
- Versioning.

## Fase 8 — Laporan administratif
- Rekap siswa/Guru/rombel/mapel.
- Periode.
- Kenaikan/kelulusan.
- Impor.
- Dokumen.

## Fase 9 — Backup/restore
- Backup database/media.
- Retention.
- Integrity.
- Restore disposable.
- Recovery evidence.

## Fase 10 — Hardening dan acceptance
- Security tests.
- Permission tests.
- Tenant tests.
- UI responsive tests.
- Performance tests.
- Regression tests.
- Dokumentasi.
- Checkpoint.

Urutan dapat disesuaikan berdasarkan dependency, tetapi perubahan urutan harus dicatat dan disetujui.

---

# 18. PENGUJIAN DAN KRITERIA PENERIMAAN

## 18.1 UI/UX
- Semua menu Admin sesuai spesifikasi.
- Tidak ada menu operasional CBT Guru di Admin.
- Dashboard menampilkan data valid.
- Layout responsif.
- Tidak ada card overflow.
- Form tervalidasi.
- Tabel dapat digunakan.
- Empty/loading/error state tersedia.
- Navigasi konsisten.

## 18.2 Data dan akademik
- Periode aktif konsisten.
- Fase sesuai kelas.
- Siswa tidak tergandakan.
- Penempatan rombel benar.
- Kenaikan kelas menjaga histori.
- Kelulusan menjaga histori.
- CP/TP/ATP versioned.
- KBC terhubung sesuai referensi.

## 18.3 Impor
- Template siswa valid.
- Template Guru valid.
- Template mapel valid.
- Mulok dapat diimpor sebagai kategori sendiri.
- Duplicate detection benar.
- Invalid rows dilaporkan.
- Import history tersimpan.
- Tidak ada data hilang.
- Tidak ada password plaintext.
- Tidak ada akses tenant silang.

## 18.4 Keamanan
- Admin tidak dapat mengakses tenant lain.
- Endpoint langsung tetap memeriksa permission.
- Export sesuai scope.
- File download sesuai scope.
- Aksi sensitif diaudit.
- Upload aman.
- Restore terlindungi.

## 18.5 Backup/restore
- Backup dapat dibuat.
- Checksum dapat diverifikasi.
- Media lengkap.
- Restore disposable berhasil.
- Kegagalan restore dapat ditangani.
- Histori tetap utuh.

## 18.6 Bukti pengujian
Untuk setiap test tuliskan:
- Nama test.
- Lingkungan.
- Versi PHP/Laravel/MySQL.
- Command/metode.
- Output.
- PASS/FAIL/BLOCKED.
- Log/artifact.
- Commit SHA yang diuji.

Jangan menyatakan siap produksi hanya karena halaman tampil atau test unit sebagian lulus.

---

# 19. GIT, BACKUP, DAN PULL REQUEST

- Branch dokumentasi ini tidak boleh dipakai untuk implementasi source.
- Buat branch implementasi Web Admin tersendiri setelah audit/desain disetujui.
- Jangan mengubah branch kandidat historis.
- Jangan force push.
- Jangan merge tanpa persetujuan.
- Commit kecil dan terarah.
- Setiap file yang diubah harus tercatat.
- Backup sebelum patch.
- Simpan laporan audit, desain, implementasi, test, dan checkpoint.
- PR harus menjelaskan scope, risiko, migration (jika ada), test, rollback, dan bukti.

Jika perubahan membutuhkan migration, buat desain migration terpisah, periksa kompatibilitas fresh install dan upgrade, gunakan disposable MySQL 8, dan minta persetujuan sebelum migration produksi.

---

# 20. KETERKAITAN DENGAN REMEDIASI P0

Status historis proyek:
- A00/A01 final.
- A02/A02.1 disetujui sebagai dasar remediasi.
- P0.1 blocked sebelum patch karena workspace canonical dan runtime PHP 8.3/MySQL 8 disposable belum tersedia pada sesi agent sebelumnya.
- P0.2 belum dimulai.
- P0.3 belum dimulai.
- Device Lease/Fencing HOLD.

Dokumen Web Admin ini **tidak memberikan persetujuan** untuk mengubah P0.1/P0.2/P0.3. Jangan mencampur patch migration/DB integrity dengan redesign UI. Jika file atau route bersinggungan, laporkan dependency dan tunggu persetujuan.

---

# 21. FORMAT LAPORAN WAJIB

## AUDIT
- Repository/branch/commit.
- Source yang diperiksa.
- Fitur dan status.
- File path.
- Route/komponen.
- Permission.
- Test.
- Temuan.
- Risiko.
- Keterbatasan.

## DESAIN
- Sitemap.
- Menu.
- Wireframe.
- Design tokens.
- Matriks role/permission Admin.
- Alur impor.
- Model data yang dibutuhkan.
- Rencana keamanan.
- Rencana test.

## IMPLEMENTASI
- Branch/commit.
- File baru/diubah.
- Perubahan fungsi.
- Backup path.
- SHA-256 sebelum/sesudah.
- Migration jika ada.
- Rollback plan.

## PENGUJIAN
- Test aktual.
- Environment.
- Hasil.
- Evidence.
- Known gaps.
- Status PASS/FAIL/BLOCKED/NOT RUN.

## CHECKPOINT
- Tahap selesai.
- Tahap belum selesai.
- Risiko terbuka.
- Keputusan yang belum disetujui.
- Instruksi agent berikutnya.

---

# 22. INSTRUKSI PERTAMA AGENT

Jangan langsung coding dan jangan melakukan deployment.

1. Baca dokumen ini seluruhnya.
2. Periksa repository dan status branch/PR terbaru.
3. Periksa baseline Web Admin dan seluruh file terkait Admin.
4. Fokus hanya Portal Admin Madrasah.
5. Jangan audit atau redesign Portal Guru maupun Web Siswa.
6. Buat inventaris seluruh menu/halaman Admin.
7. Buat matriks fitur SUDAH ADA / BELUM LENGKAP / BELUM ADA / BERMASALAH / BELUM TERVERIFIKASI.
8. Audit UI, backend, permission, tenant scope, data source, dan test.
9. Buat rancangan Admin lengkap berdasarkan dokumen ini.
10. Sajikan laporan dan rencana bertahap kepada koordinator.
11. Tunggu persetujuan sebelum implementasi.
12. Simpan checkpoint agar pekerjaan dapat dilanjutkan agent baru tanpa mengulang audit.

**Prinsip akhir:** Web Admin harus menjadi pusat administrasi madrasah yang lengkap, bukan pusat operasional ujian. Semua operasional CBT tetap di luar ruang lingkup Admin. Kurikulum hanya Kurmer/KBC. Impor siswa, Guru, dan mata pelajaran dengan template Excel—termasuk Mulok—wajib tersedia.


---

# 23. PROFIL ADMIN DAN PENGELOLAAN KATA SANDI

Web Admin wajib menyediakan halaman profil akun Admin yang dapat diakses dari menu profil pada header/topbar. Fitur ini merupakan bagian inti administrasi akun, bukan fitur opsional.

## 23.1 Menu dan informasi profil

Sediakan menu **Profil Saya** yang memuat:
- Nama lengkap Admin.
- Username.
- Email atau kontak yang terdaftar, jika tersedia.
- Role dan ringkasan permission yang relevan (read-only).
- Status akun.
- Foto/avatar jika fitur dan penyimpanan tersedia.
- Informasi waktu terakhir login bila tersedia dan akurat.
- Tanggal pembuatan akun bila tersedia.

Admin hanya boleh mengubah field profil yang memang diizinkan oleh kebijakan akun. Role, permission, status privileged, tenant scope, dan identitas internal tidak boleh dapat dinaikkan atau diubah sendiri melalui form profil. Perubahan identitas penting harus divalidasi dan diaudit.

## 23.2 Pergantian kata sandi

Sediakan halaman atau tab **Keamanan Akun / Ganti Kata Sandi** dengan:
- Kata sandi saat ini.
- Kata sandi baru.
- Konfirmasi kata sandi baru.
- Tombol tampil/sembunyikan input password.
- Indikator dan validasi kebijakan password.
- Pesan sukses/gagal yang jelas dan tidak membocorkan detail sensitif.

Persyaratan keamanan:
1. Wajib memverifikasi kata sandi saat ini sebelum perubahan.
2. Kata sandi baru harus memenuhi kebijakan panjang dan kompleksitas yang ditetapkan aplikasi.
3. Konfirmasi harus cocok dengan kata sandi baru.
4. Kata sandi disimpan hanya dalam bentuk hash menggunakan mekanisme hashing Laravel yang sesuai; tidak boleh disimpan atau dicatat sebagai plaintext.
5. Jangan menampilkan, mengirim, atau mencatat password ke log, audit detail, analytics, atau pesan error.
6. Terapkan rate limit pada percobaan sensitif dan validasi di server.
7. Setelah perubahan berhasil, invalidasi sesi/token lain sesuai kebijakan keamanan; sesi saat ini boleh dipertahankan hanya jika mekanisme autentikasi dan rotasi session aman.
8. Berikan notifikasi perubahan kata sandi tanpa menyertakan kata sandi.
9. Catat peristiwa perubahan pada audit log dengan actor, waktu, dan hasil, tanpa menyimpan nilai password atau hash.
10. Jangan menyediakan fitur melihat kata sandi lama atau memulihkannya dalam bentuk plaintext.

## 23.3 Lupa kata sandi dan reset oleh Admin

Alur lupa kata sandi harus menggunakan mekanisme reset yang aman, token sekali pakai, masa berlaku terbatas, dan respons yang tidak membocorkan apakah suatu akun terdaftar. Reset kata sandi milik pengguna lain oleh Admin harus menjadi aksi terpisah dengan permission khusus, alasan, audit log, dan prosedur pemberitahuan; jangan menyamakan reset oleh Admin dengan pergantian kata sandi mandiri.

## 23.4 Pengujian penerimaan

- Admin dapat membuka Profil Saya dan hanya melihat data sesuai haknya.
- Admin dapat memperbarui field profil yang diizinkan.
- Admin tidak dapat mengubah role/permission sendiri melalui profil.
- Pergantian password gagal jika password saat ini salah.
- Pergantian password gagal jika konfirmasi tidak cocok atau kebijakan tidak terpenuhi.
- Pergantian password berhasil dengan password baru yang valid.
- Password baru dapat digunakan untuk login dan password lama tidak lagi berlaku sesuai kebijakan sesi.
- Password maupun hash tidak muncul pada log, audit, response, atau tampilan.
- Rate limit dan proteksi CSRF/authorization berjalan.
- Reset password pengguna lain memerlukan permission khusus dan tercatat.



---

# 24. MASTER KURIKULUM DEFAULT RESMI, DAPAT DIKONFIGURASI ADMIN

## 24.1 Prinsip seed/default dan kewenangan Admin

Master Kurikulum tidak boleh dimulai dari database kosong. Setiap instalasi madrasah harus menerima **paket data awal (seed/default) Kurikulum MI** yang telah diverifikasi terhadap regulasi dan dokumen resmi yang berlaku pada versi paket tersebut.

Admin Madrasah boleh:
- Menambahkan mata pelajaran Muatan Lokal (Mulok) sesuai karakteristik madrasah.
- Menambahkan atau menyusun CP/TP/ATP lokal jika memang disusun/ditetapkan madrasah, dengan penanda jelas bahwa data tersebut adalah data lokal, bukan kutipan CP resmi.
- Mengatur mata pelajaran yang digunakan, pilihan seni, alokasi/jadwal sesuai kewenangan dan ketentuan struktur kurikulum.
- Menambahkan tag/indikator lokal untuk kebutuhan pemetaan pembelajaran dan asesmen.
- Mengarsipkan/nonaktifkan konfigurasi yang tidak lagi digunakan, sepanjang tidak menghapus histori atau merusak referensi.

Admin **tidak boleh mengubah diam-diam isi master resmi** sehingga tampak seolah-olah merupakan rumusan pemerintah. Master resmi harus read-only pada record sumbernya. Jika madrasah perlu penyesuaian, sistem membuat salinan/override lokal (customization layer) dengan label "Disesuaikan Madrasah", pencatat, tanggal, alasan, dan sumber. Record resmi tetap utuh.

## 24.2 Registry regulasi dan versi default

Seed awal wajib mempunyai registry sumber:
- Jenis dokumen dan nomor.
- Judul resmi.
- Instansi penerbit.
- Tanggal ditetapkan/berlaku jika tercantum.
- URL sumber resmi.
- Tanggal verifikasi.
- Versi dataset seed.
- Jenjang/fase/mapel yang dicakup.
- Checksum file sumber yang diarsipkan secara sah.
- Status berlaku/digantikan/perlu verifikasi.

Baseline regulasi yang telah diverifikasi untuk rancangan ini:
1. **KMA Nomor 1503 Tahun 2025**, perubahan atas KMA Nomor 450 Tahun 2024 tentang Pedoman Implementasi Kurikulum pada RA, MI, MTs, MA, dan MAK. KMA ini ditetapkan 22 September 2025 dan menjadi acuan implementasi kurikulum madrasah yang menekankan Pembelajaran Mendalam dan Kurikulum Berbasis Cinta.
2. **Permendikdasmen Nomor 10 Tahun 2025** tentang Standar Kompetensi Lulusan pada PAUD, Pendidikan Dasar, dan Pendidikan Menengah, khususnya Pasal 4 mengenai 8 dimensi profil lulusan.
3. **Keputusan Direktur Jenderal Pendidikan Islam Nomor 3302 Tahun 2024** atau dokumen CP PAI/Bahasa Arab yang berlaku, hanya boleh dipakai jika status keberlakuan dan keterkaitannya dengan KMA 1503/2025 telah diverifikasi. Jangan seed CP dari dokumen lama tanpa pemeriksaan perubahan/penggantinya.
4. **Panduan Kurikulum Berbasis Cinta** dan/atau keputusan/panduan resmi Ditjen Pendidikan Islam yang berlaku, termasuk Keputusan Dirjen Pendis Nomor 6077 Tahun 2025 jika telah diverifikasi dokumen lengkapnya.

KMA 1503/2025 adalah perubahan atas KMA 450/2024. Sistem harus menyimpan relasi perubahan tersebut, bukan menganggap dokumen lama otomatis hilang seluruhnya. Sebelum paket seed dirilis, lakukan pemeriksaan JDIH Kemenag/JDIH kementerian terkait untuk memastikan tidak ada perubahan regulasi yang lebih baru.

## 24.3 Default mata pelajaran MI

Seed awal menggunakan struktur mapel MI yang tercantum pada Lampiran KMA 1503 Tahun 2025, dengan konfigurasi per kelas/kelompok kelas dan periode berlaku. Jangan menyamakan seluruh daftar mapel untuk kelas I–VI karena tabel struktur dan alokasi dapat berbeda menurut kelompok kelas.

Master awal sekurangnya memuat:
- Al-Qur'an Hadis.
- Akidah Akhlak.
- Fikih.
- Sejarah Kebudayaan Islam (SKI) sesuai kelas/struktur yang berlaku.
- Bahasa Arab.
- Pendidikan Pancasila.
- Bahasa Indonesia.
- Matematika.
- Ilmu Pengetahuan Alam dan Sosial (IPAS) pada kelas yang ditentukan struktur.
- Pendidikan Jasmani, Olahraga, dan Kesehatan (PJOK).
- Seni dan Budaya, dengan pilihan jenis seni yang diakomodasi sesuai ketentuan.
- Bahasa Inggris sesuai kelas/struktur yang berlaku.
- Mata pelajaran pilihan Koding dan Kecerdasan Artifisial sesuai ketentuan dan kesiapan madrasah.
- Muatan Lokal.

Nama, kelas sasaran, kategori wajib/pilihan, dan alokasi waktu harus diambil dari tabel resmi per kelas dalam KMA yang berlaku. Jangan menyalin alokasi satu kelas ke kelas lain. Konfigurasi struktur dan alokasi harus memiliki periode efektif dan dapat diperbarui ketika regulasi berubah.

**Catatan kode mapel/RDM:** KMA yang menjadi dasar struktur kurikulum tidak dengan sendirinya membuktikan bahwa suatu kode singkat (misalnya MTK/BIND/AQH) adalah kode resmi RDM. Karena itu:
- Sistem memiliki internal stable ID yang tidak berubah.
- Simpan kode tampilan/internal terpisah dari kode referensi eksternal.
- Sediakan tabel alias/mapping RDM dengan sumber, versi format, dan tanggal verifikasi.
- Jangan mengarang atau mengunci kode RDM berdasarkan singkatan umum.
- Import/export RDM baru boleh dinyatakan kompatibel setelah diuji menggunakan format resmi RDM yang aktual.
- Admin boleh mengatur alias lokal jika format integrasi mengizinkan, tetapi tidak boleh menimpa identitas internal atau histori.

## 24.4 Default Muatan Lokal

Mulok harus tersedia sebagai kategori yang dapat ditambahkan Admin. Seed kategori/contoh dapat meliputi ranah yang disebut dalam KMA 1503/2025, antara lain:
- Keagamaan.
- Seni budaya.
- Prakarya.
- Pendidikan jasmani, olahraga, dan kesehatan.
- Bahasa.
- Riset.
- Teknologi.

Contoh muatan keagamaan dalam lampiran KMA mencakup antara lain Al-Qur'an, Hadis, Qiro'ah Al-Qur'an, Tahfidz Al-Qur'an, Ilmu Tajwid, Imla, Nahwu, Shorof, Khat, dan Baca Tulis Al-Qur'an. Contoh ini adalah pilihan konteks, bukan daftar wajib untuk semua MI.

KMA memperbolehkan Mulok diintegrasikan ke mata pelajaran lain, tema kokurikuler, dan/atau menjadi mata pelajaran tersendiri. Karena itu model data harus mendukung ketiga bentuk tersebut; jangan memaksa semua Mulok menjadi mata pelajaran berdiri sendiri.

## 24.5 Default CP, TP, dan ATP

CP/TP/ATP tidak boleh diisi dengan rumusan buatan agent. Prosedur seed:
1. Temukan dokumen CP resmi yang berlaku untuk MI, mapel, dan fase terkait.
2. Verifikasi penerbit, nomor/tahun, status berlaku, dan apakah ada revisi/pengganti.
3. Ekstrak teks dengan pemeriksaan terhadap dokumen aslinya; simpan judul, elemen, fase, mapel, kode (jika resmi), rumusan, halaman, sumber, versi, dan tanggal berlaku.
4. Seed CP resmi sebagai record sumber yang tidak dapat diedit langsung oleh Admin.
5. Seed TP/ATP hanya jika ada dokumen resmi yang memang menetapkan daftar tersebut. Jika TP/ATP merupakan turunan/rancangan satuan pendidikan atau pendidik, jangan menyebutnya sebagai daftar TP/ATP nasional resmi.
6. Jika belum tersedia dataset resmi yang terverifikasi, sistem harus menampilkan status "Belum tersedia—perlu konfigurasi madrasah", bukan mengisi konten palsu.
7. Admin dapat menambahkan TP/ATP rancangan madrasah dengan label "Disusun Madrasah", nama penyusun, tanggal, versi, dan relasi ke CP sumber.
8. Guru memilih CP/TP/ATP yang relevan melalui dropdown sesuai mapel, fase/kelas, tahun ajaran, versi aktif, dan assignment Guru. Guru tetap dapat mengusulkan tujuan pembelajaran baru sesuai kewenangan, tanpa mengubah record CP resmi.
9. Snapshot asesmen/ujian menyimpan versi referensi yang digunakan sehingga revisi master tidak mengubah histori.

## 24.6 Default 5 Pilar Panca Cinta (KBC)

Seed default 5 Pilar Panca Cinta persis sesuai rumusan pada KMA 1503 Tahun 2025:
1. Cinta Allah dan Rasul-Nya.
2. Cinta Ilmu.
3. Cinta Lingkungan.
4. Cinta Diri dan Sesama Manusia.
5. Cinta Tanah Air.

Kelima pilar merupakan nilai yang menjiwai kurikulum dan pembelajaran, bukan lima mata pelajaran baru. Setiap pilar dapat memiliki uraian, indikator pembelajaran, contoh penerapan, sumber, dan versi hanya jika uraian tersebut diambil dari panduan resmi yang telah diverifikasi.

Admin boleh menambahkan indikator/kegiatan/konteks lokal pada lapisan madrasah, tetapi nama dan urutan lima pilar sumber resmi tidak boleh ditimpa. Pemetaan soal ke Panca Cinta sebaiknya opsional dan kontekstual; jangan memaksa setiap soal memiliki label karakter jika tidak relevan.

## 24.7 Default 8 Dimensi Profil Lulusan (DPL)

Seed default 8 dimensi sesuai Pasal 4 Permendikdasmen Nomor 10 Tahun 2025:
1. Keimanan dan ketakwaan terhadap Tuhan Yang Maha Esa.
2. Kewargaan.
3. Penalaran kritis.
4. Kreativitas.
5. Kolaborasi.
6. Kemandirian.
7. Kesehatan.
8. Komunikasi.

Deskripsi dimensi harus diambil dari pasal terkait dalam regulasi, bukan dibuat bebas. DPL adalah dimensi profil lulusan dan bukan label yang harus dipaksakan pada setiap butir soal. Pemetaan asesmen/survei karakter harus sesuai tujuan, usia, konteks, dan instrumen yang tervalidasi; CBT tidak boleh mengklaim mengukur karakter secara sahih hanya karena ada pilihan tag DPL.

Admin boleh menambahkan indikator operasional lokal sebagai turunan dengan label lokal, tetapi tidak boleh mengganti nama atau definisi resmi DPL. Jika regulasi berubah, buat versi baru dan pertahankan versi lama untuk histori.

## 24.8 Struktur data dan perilaku konfigurasi

Setiap entitas master minimal menyimpan:
- UUID/internal ID stabil.
- Jenis: official_seed, madrasah_custom, atau imported_reference.
- Nama/kode dan deskripsi.
- Jenjang, fase, kelas, mapel, dan periode efektif jika relevan.
- Source document, pasal/lampiran/halaman, URL, nomor/tahun, versi.
- Status aktif/arsip.
- Created/updated actor dan timestamp.
- Parent/source record untuk override atau turunan lokal.
- Version number dan changelog.

Ketentuan:
- Seed bersifat idempotent: instalasi ulang/update seed tidak menggandakan data dan tidak menimpa perubahan lokal.
- Pembaruan paket resmi membuat versi baru, bukan overwrite record yang telah dipakai.
- Admin dapat menambah dan mengarsipkan data lokal.
- Data resmi tidak bisa dihapus dari UI Admin.
- Data yang sudah direferensikan oleh CP/TP/ATP, soal, ujian, hasil, atau laporan tidak boleh dihapus secara fisik.
- Perubahan konfigurasi yang memengaruhi pembelajaran/asesmen harus tercatat dalam audit log.
- Dropdown Guru hanya menampilkan item yang sesuai mapel, fase/kelas, versi, periode, status, dan assignment.
- Tidak ada kewajiban memberi tag Panca Cinta/DPL pada setiap soal; gunakan sesuai relevansi pedagogis.

## 24.9 Acceptance criteria paket default

- Fresh install memperoleh master awal yang benar dan berversi.
- Seed dapat dijalankan berulang tanpa duplikasi atau menimpa data lokal.
- Seluruh item official_seed memiliki sumber resmi yang dapat ditelusuri.
- Tidak ada CP/TP/ATP yang dikarang atau diberi label resmi tanpa bukti.
- Struktur mapel dan alokasi per kelas cocok dengan lampiran regulasi yang dipakai.
- Lima Panca Cinta dan delapan DPL cocok persis dengan sumber regulasi.
- Admin dapat menambah Mulok dan data turunan lokal.
- Admin tidak dapat mengubah/menghapus record resmi sumber secara langsung.
- Perubahan versi tidak merusak referensi atau snapshot historis.
- Dropdown dan pemetaan hanya menampilkan referensi sesuai konteks.
- Semua perubahan memiliki audit trail.
- Uji dilakukan pada database disposable MySQL 8; tidak ada seed/migration eksperimental ke produksi tanpa persetujuan.

## 24.10 Sumber regulasi dan verifikasi

Sumber yang diperiksa untuk rancangan:
- KMA Nomor 1503 Tahun 2025, salinan lampiran dan keputusan yang disampaikan Ditjen Pendidikan Islam: https://sulteng.kemenag.go.id/files/show/20251020162801surat-penyampaian-kma-kurikulum-2025%2Blampiran_compressed-tzR.pdf
- Permendikdasmen Nomor 10 Tahun 2025, JDIH Kemendikdasmen: https://jdih.kemendikdasmen.go.id/sjdih/siperpu/dokumen/salinan/Permendikdasmen%20Nomor%2010%20Tahun%202025%20tentang%20Standar%20Kompetensi%20Lulusan%20pada%20Pendidikan%20Anak%20Usia%20Dini%2C%20Jenjang%20Pendidikan%20Dasar%2C%20dan%20Jenjang%20Pendidikan%20Menengah.pdf
- Halaman Panduan Kurikulum Berbasis Cinta Kanwil Kemenag Sulawesi Tengah: https://sulteng.kemenag.go.id/informasi-penting/y2jy/28-07-2025-panduan-kurikulum-berbasis-cinta

Sumber di atas menjadi baseline dokumentasi, bukan jaminan bahwa tidak ada perubahan setelah tanggal pemeriksaan. Sebelum merilis seed produksi, agent wajib memverifikasi status terkini melalui JDIH/penerbit resmi dan melampirkan dokumen CP MI per mapel/fase yang benar-benar dipakai. Bila suatu kode RDM atau rumusan CP/TP tidak dapat dibuktikan, jangan mengisinya dengan tebakan.
