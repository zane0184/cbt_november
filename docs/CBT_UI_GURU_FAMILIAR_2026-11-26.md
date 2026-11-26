# Checkpoint UI Web Guru — Familiar Learning Experience
Tanggal: 26 November 2026
Branch: `work/guru-docx-rich-content-20261126`

## AUDIT
- Portal Guru menggunakan layout tersendiri: `backend-laravel/resources/views/layouts/teacher.blade.php`.
- Dashboard dirender oleh `TeacherDashboard` melalui `TeacherPortalDataService::dashboard()` ke view `livewire.teacher.portal-dashboard`.
- Data `activeAssignments` telah tersedia dari service, dengan filter berdasarkan profil guru, tenant, status dan masa berlaku penugasan. Data kartu tidak dibuat dari data statis.
- Layout sebelumnya menggunakan sidebar hijau, tetapi navigasi dashboard belum menonjolkan konsep Kelas Virtual; dashboard menampilkan ringkasan dan tabel penugasan aktif.
- Route yang tersedia untuk modul Guru meliputi dashboard, penugasan, pembelajaran, materi, profil, bank soal, impor DOCX, dan asesmen/ujian.
- Tidak ditemukan kebutuhan perubahan database atau backend untuk tahap visual dashboard ini.

## DESAIN
- Menjadikan dashboard sebagai pintu masuk Kelas Virtual.
- Menampilkan kartu berdasarkan penugasan aktif: rombel, mapel, fase/kelas, tahun pelajaran, semester, jumlah siswa, dan status.
- Menyediakan pintasan ke halaman Bank Soal dan Ujian CBT yang sudah ada.
- Mempertahankan ringkasan aktivitas asesmen, identitas Guru, dan ringkasan kurikulum Merdeka terintegrasi KBC.
- Menyelaraskan label navigasi agar lebih familier bagi pengguna E-Learning Madrasah.
- Mempertahankan tema hijau terang yang konsisten.

## IMPLEMENTASI
Commit:
- `5f00672d20e4398a9f3261f1458c4b017017bead` — dashboard kartu kelas.
- `9a0d308ced40bbe17feb4bd14010eb8ab3e5f26d` — navigasi/layout Guru.

File source yang diubah:
1. `backend-laravel/resources/views/livewire/teacher/portal-dashboard.blade.php`
2. `backend-laravel/resources/views/layouts/teacher.blade.php`

Backup sebelum perubahan:
1. `backend-laravel/backups/guru-ui-familiar-20261126/portal-dashboard.blade.php.before`
   - Source SHA sebelum perubahan: `c15df47a44fe007913bdd215f1b29656da5dfec5`
   - Backup SHA: sama persis; diverifikasi byte-exact.
2. `backend-laravel/backups/guru-ui-familiar-20261126/teacher.blade.php.before`
   - Source SHA sebelum perubahan: `82f8e89c98c9c8d603566b5800b54eb545910f9b`
   - Backup SHA: sama persis; diverifikasi byte-exact.

Perubahan hanya menyentuh Blade/presentasi. Tidak ada perubahan pada route, Livewire component, service, model, API, database, migration, business logic, keamanan, atau mesin CBT.

Catatan UX:
- Pintasan Bank Soal dan Ujian pada setiap kartu saat ini menuju route modul yang sudah ada. Audit saat ini belum membuktikan bahwa route tersebut menerima konteks assignment dari kartu. Karena itu, jangan menyebutnya sebagai navigasi ruang kelas yang sudah otomatis memfilter assignment; hal ini perlu ditinjau pada tahap UI berikutnya tanpa mengubah kontrak backend.
- Tampilan kelas yang dibuat hanya menampilkan penugasan aktif. Riwayat/nonaktif tetap tersedia di halaman Penugasan.

## PENGUJIAN
- Verifikasi commit GitHub: kedua commit masing-masing hanya mengubah satu file Blade yang dimaksud.
- Verifikasi file source setelah commit: SHA dan isi terbaru berhasil dibaca.
- Verifikasi backup: kedua backup identik byte-per-byte dengan source awal.
- Combined CI status untuk kedua commit belum melaporkan status check; workflow runs yang terhubung ke commit juga belum tersedia.
- Browser visual test, Livewire interaction test, dan validasi langsung oleh Guru belum dijalankan.
- Tidak ada klaim bahwa UI sudah lulus uji operasional.

## LANGKAH BERIKUTNYA
1. Lanjutkan rekonstruksi layout halaman Penugasan, Pembelajaran/Materi, Bank Soal, Edit Soal, dan Asesmen/Ujian agar pola navigasinya konsisten.
2. Audit alur pemilihan assignment yang memang sudah tersedia di setiap modul sebelum membuat navigasi kelas yang membawa konteks assignment.
3. Sesuaikan form dan modal hanya pada presentasi, termasuk progressive disclosure dan pratinjau soal, tanpa mengubah backend.
4. Jalankan CI/regresi dan browser visual test sebelum menyatakan tahap selesai.
