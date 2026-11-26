# Laporan Perbaikan Menu CP & TP Admin — 2026-11-26

## AUDIT
- Screenshot menunjukkan menu **CP & TP Kurikulum** dan **Registry Kurikulum** terpisah.
- Pada source sebelum perbaikan, route `/admin/curriculum` merupakan redirect ke `/admin/curriculum/registry`. Karena itu menu CP & TP tidak membuka halaman CP/TP.
- Komponen tujuan yang tersedia adalah `App\\Livewire\\Admin\\CurriculumManagement` dengan view `livewire.admin.curriculum-management`.
- Komponen tersebut membatasi akses pada satu role `ADMIN_MADRASAH`, tenant aktif, dan permission `curriculum.view`; CP/TP Admin bersifat baca-saja. TP/ATP dikembangkan Guru sesuai penugasan.
- Registry tetap menggunakan `CurriculumRegistryManagement` dan permission `curriculum.registry.manage`.

## DESAIN
- Pisahkan URL CP/TP `/admin/curriculum` dari Registry `/admin/curriculum/registry`.
- Pertahankan validasi role, tenant, permission, histori, dan batas baca-saja Admin.

## IMPLEMENTASI
- Backup route sebelum perubahan: `backend-laravel/backups/route-cp-tp-fix-20261126/web.php.before`.
- File diubah: `backend-laravel/routes/web.php`.
- Menambahkan import `CurriculumManagement`.
- Mengganti redirect CP/TP menjadi route Livewire `CurriculumManagement::class`.
- Route Registry tetap diarahkan ke `CurriculumRegistryManagement::class`.
- Commit implementasi: `04f65dcf72926bea855a96ad5b6fb334ec5db099`.

## PENGUJIAN
- Pemeriksaan ulang source route dilakukan setelah commit.
- Hasil pemeriksaan: {"imports":true,"cp_tp_route":true,"registry_route":true,"old_redirect":false}.
- Belum ada bukti browser/UAT pada instalasi VPS pengguna.
- Runtime CI dan paket deployment harus diverifikasi pada workflow yang terpicu oleh commit ini sebelum dinyatakan lolos.
- Tidak ada database atau VPS produksi yang disentuh.
