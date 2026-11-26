# Laporan Perbaikan Web Guru — Assessment & Ujian HTTP 500
Tanggal: 26 November 2026

## Identitas pekerjaan
- Repository: `zane0184/cbt_november`
- Branch kerja: `work/guru-assessment-500-schedule-20261126`
- Base source: `work/combined-feature-completion-20261126`
- Base SHA: `055489d8c7557bc0d01c78aecc5843704c75baf7`
- HEAD setelah patch source: `aa2146bece051260060293b0c85b9608d4ef48f3`
- Scope: hanya menu Guru `/guru/asesmen`, data modal Assessment & Ujian, dan tampilan jadwal.

## AUDIT
1. Route `/guru/asesmen` memuat Livewire `App\\Livewire\\Admin\\ExamManagement` melalui `routes/teacher.php`; komponen menggunakan layout Guru.
2. Blade `resources/views/livewire/admin/exam-management.blade.php` memakai variabel `$subjects`, `$activeSemester`, dan `$rombels` di modal Buat/Edit Ujian.
3. Method `render()` sebelumnya tidak mengirim tiga variabel tersebut ke Blade. Saat modal dibuka, Blade mengevaluasi variabel yang tidak tersedia. Ini merupakan cacat source yang dapat memicu HTTP 500 (undefined variable).
4. Komponen sudah memperoleh `$assignmentRows` melalui `TeacherPortalDataService::assignmentOptions()`. Baris tersebut berisi relasi subject, semester, dan rombel yang telah dibatasi pada assignment Guru aktif dan tenant instalasi.
5. Field jadwal pada Blade sebelumnya sudah memakai `datetime-local` dan nilai Livewire memakai format `Y-m-d\\TH:i`; ini sudah mendukung tanggal, jam, dan menit. Namun label tidak menjelaskan resolusi menit, step tidak dinyatakan eksplisit, dan ringkasan jadwal tidak menampilkan tahun.

## DESAIN
- Jangan query daftar mapel/rombel seluruh madrasah untuk memperbaiki render Guru.
- Turunkan daftar subject dan rombel dari assignment aktif yang sudah lolos scope Guru.
- Ambil semester dari assignment terpilih.
- Pertahankan `datetime-local` sebagai satu input tanggal + jam + menit; set `step=60` agar presisi input per menit eksplisit.
- Tampilkan tahun pada ringkasan jadwal.
- Tidak mengubah taxonomy asesmen, snapshot, scoring, token, participant, permission, migration, database, atau fitur portal lain.

## IMPLEMENTASI
### File source berubah
1. `backend-laravel/app/Livewire/Admin/ExamManagement.php`
   - Membentuk `$subjects` dan `$rombels` dari `$assignmentRows` yang tenant/assignment-scoped.
   - Mengambil `$activeSemester` dari semester assignment terpilih.
   - Mengirim ketiga variabel tersebut ke view.
2. `backend-laravel/resources/views/livewire/admin/exam-management.blade.php`
   - Label jadwal menjadi “Tanggal & Waktu Mulai/Selesai (Jam:Menit)”.
   - Kedua input `datetime-local` menetapkan `step="60"`.
   - Ringkasan tanggal mulai/selesai menampilkan format `d M Y, H:i`.

### Backup byte-exact sebelum patch
- `backend-laravel/app/Livewire/Admin/ExamManagement.php`
  - Backup: `backend-laravel/backups/guru-assessment-500-schedule-20261126/ExamManagement.php.before`
  - Source blob SHA: `2ec8c13b073c5f9a0a5fcf643e9b4d639432fb19`
  - Backup blob SHA terverifikasi: sama persis.
- `backend-laravel/resources/views/livewire/admin/exam-management.blade.php`
  - Backup: `backend-laravel/backups/guru-assessment-500-schedule-20261126/exam-management.blade.php.before`
  - Source blob SHA: `bdcd98c65726d9bf41170dc82f954e5bba570cee`
  - Backup blob SHA terverifikasi: sama persis.
- Manifest: `backend-laravel/backups/guru-assessment-500-schedule-20261126/BACKUP_MANIFEST.md`

### Commit source
- Komponen render: `a44395dba2402d71e13f7b1fae0920bf49b77758`
- Pengiriman data view: `aa2146bece051260060293b0c85b9608d4ef48f3`
- Perbaikan jadwal Blade: `8b70834215f92175260cd8349fe310f9c591651d`

## PENGUJIAN
Sesuai instruksi owner, CI dan PHPUnit **tidak dijalankan**.

Pemeriksaan statis yang dilakukan setelah patch:
- Memuat ulang source dari branch GitHub dan memeriksa blob SHA.
- Memastikan data view `subjects`, `activeSemester`, dan `rombels` sekarang dikirim.
- Memastikan sumber data berasal dari `assignmentRows`, bukan query sekolah luas.
- Memastikan dua input `datetime-local`, dua `step="60"`, label tanggal/jam/menit, dan ringkasan tahun tersedia.
- Memastikan backup blob SHA identik dengan source sebelum patch.

Belum dilakukan:
- Browser runtime pada VPS atau browser E2E.
- Verifikasi log HTTP 500 pada VPS.
- CI, PHPUnit, migration, atau database test.

Karena runtime tidak dijalankan, status ini adalah **source fix committed; runtime belum diverifikasi**, bukan klaim 500 telah terbukti hilang di VPS.

## Batas perubahan
Tidak ada perubahan pada Admin, Siswa, Android, Super Admin, API backend lain, migration, database, konfigurasi VPS, atau fitur di luar Assessment & Ujian Guru.
