# Laporan Perbaikan Detail Rombel — 26 November 2026

## AUDIT
- Source awal memisahkan daftar rombel (/admin/academic) dan penempatan siswa (/admin/rombel-students).
- Tabel rombel sebelumnya tidak memiliki tautan detail dan tidak menampilkan jumlah siswa.
- Model Rombel menyediakan relasi students() ke StudentProfile melalui pivot rombel_student. Relasi memfilter tenant_id pada pivot.
- StudentProfile menyediakan relasi user() dan rombels().
- Data wali kelas belum dipastikan: Rombel memiliki teacher_id, tetapi makna foreign key dan relasi yang aman belum terverifikasi dari migration/schema. Karena itu tahap ini tidak menampilkan nama wali kelas agar tidak menebak relasi.
- Penempatan siswa yang ada menambahkan keanggotaan dan mencatat audit log. Tidak diubah dalam patch ini.

## DESAIN
- Klik nama rombel atau tombol "Lihat Siswa" pada daftar rombel.
- Detail rombel menampilkan tingkat/fase, tahun ajaran, status, jumlah siswa, dan daftar siswa.
- Halaman detail menyediakan tautan ke halaman penempatan siswa yang sudah ada.
- Akses dibatasi ke satu role ADMIN_MADRASAH dengan permission academic.manage dan tenant yang sama.

## IMPLEMENTASI
Branch: fix/admin-rombel-detail-20261126
Base: fix/madrasah-branding-dashboard-20261126

Perubahan:
- Tambah app/Livewire/Admin/RombelDetail.php.
- Tambah resources/views/livewire/admin/rombel-detail.blade.php.
- Tambah route /admin/academic/rombels/{rombel}.
- Daftar rombel menampilkan jumlah siswa dan tautan detail.
- Query daftar rombel memakai withCount('students').
- Query detail membatasi tenant_id, memuat relasi academicYear dan gradeLevel.phase, serta mengambil siswa melalui relasi tenant-aware Rombel::students().
- Backup source sebelum perubahan tersimpan di docs/backup_before_patch/ROMBEL_DETAIL_20261126/.

Tidak diubah:
- Database, schema, migration.
- Import siswa/guru.
- Mesin CBT, sinkronisasi, lisensi, tenant architecture, branding service.
- Logika penempatan/pemindahan siswa.
- Tidak membuat portal atau role baru.

## PENGUJIAN
- Pemeriksaan statis source dan route telah dilakukan setelah perubahan.
- PHPUnit belum dijalankan; lingkungan repository ini tidak menyediakan checkout lengkap, Composer/vendor, atau runtime database untuk menjalankan tes.
- Belum ada browser test atau VPS test.
- Tidak ada klaim bahwa fitur sudah lulus runtime.

## Batasan dan tindak lanjut
- Wali kelas belum ditampilkan sampai foreign key teacher_id dan relasi profil guru dipastikan dari migration/schema aktual.
- Perlu tes terarah: admin tenant yang benar dapat membuka detail; user non-admin/multi-role ditolak; rombel tenant lain menghasilkan 404; daftar siswa hanya memuat anggota rombel tersebut; jumlah siswa sesuai pivot.
- Jangan merge/deploy sebelum tes terarah dan inspeksi hasil di VPS.
