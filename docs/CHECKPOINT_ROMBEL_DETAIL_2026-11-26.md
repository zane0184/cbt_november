# Checkpoint — Detail Rombel Admin — 26 November 2026

- Branch aktif: `fix/admin-rombel-detail-20261126`
- Base: `fix/madrasah-branding-dashboard-20261126`
- Status: patch awal detail rombel dibuat, belum diuji runtime, belum merge, belum deploy.
- Daftar rombel: kini menampilkan jumlah siswa dan link detail.
- Detail rombel: menampilkan tahun ajaran, tingkat/fase, status, jumlah siswa, dan daftar siswa.
- Route: `/admin/academic/rombels/{rombel}`.
- Otorisasi: role tunggal ADMIN_MADRASAH + permission `academic.manage`; query rombel dibatasi tenant; daftar siswa melalui relasi pivot tenant-aware.
- Backup: `docs/backup_before_patch/ROMBEL_DETAIL_20261126/`.
- Tidak ada perubahan DB/migration, import, CBT engine, branding service, atau struktur tenant.
- Belum ada tampilan wali kelas karena semantik FK `teacher_id` belum terverifikasi.
- Tes PHPUnit, browser, dan VPS belum dijalankan.
- Langkah berikutnya: tinjau diff lengkap, tambah tes terarah, buka PR draft, kemudian tunggu verifikasi lingkungan uji sebelum merge/deploy.
