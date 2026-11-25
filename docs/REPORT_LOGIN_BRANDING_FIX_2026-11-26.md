# Laporan Perbaikan Branding Halaman Login — 26 November 2026

Repository: `zane0184/cbt_november`
Branch kerja: `audit/profile-login-branding-20261126`
Basis source: `work/guru-docx-rich-content-20261126`

## AUDIT
- Profil madrasah disimpan pada model `Tenant`; field nama resmi dan nama singkat tersedia.
- Logo aplikasi tersimpan pada `tenants.logo_path`; favicon pada `tenants.favicon_path`.
- Halaman login sudah memanggil `TenantBrandingService::currentTenant()`, membaca logo aplikasi, favicon, dan nama tenant. Jadi jalur pembacaan profil tidak perlu dibuat ulang.
- Temuan UI yang terkonfirmasi: heading utama login masih literal `CBT MADRASAH`, sehingga nama madrasah tidak menggantikan judul utama meskipun subjudul dan title browser sudah dinamis.
- Service branding memakai `LocalInstallationIdentity` dan pemeriksaan product code sebagai batas instalasi. Mekanisme fail-closed ini dipertahankan; tidak dilemahkan untuk memaksa logo tampil.

## DESAIN
- Heading utama login memakai `short_name` bila terisi, lalu `name`, lalu fallback `CBT MADRASAH`.
- Subjudul menjadi keterangan fungsi portal, bukan mengulang nama madrasah.
- Logo aplikasi dan favicon tetap memakai service yang sama, dengan validasi identitas instalasi.
- Nama dan aset tetap lokal untuk satu instalasi madrasah.

## IMPLEMENTASI
- File diubah: `backend-laravel/resources/views/auth/login.blade.php`.
- Heading login sekarang dinamis mengikuti nama singkat/nama resmi.
- File test diubah: `backend-laravel/tests/Feature/WebAdminAuthTest.php`.
- Ditambahkan regression test untuk nama resmi dan nama singkat madrasah.
- Backup byte-for-byte sebelum perubahan:
  - `docs/backup_before_patch/LOGIN_BRANDING_20261126/login.blade.php.before`
  - `docs/backup_before_patch/LOGIN_BRANDING_20261126/WebAdminAuthTest.php.before`
- Tidak ada migration, perubahan database, perubahan konfigurasi produksi, atau deployment VPS.

## PENGUJIAN
- Source hasil perubahan dibaca ulang dari branch GitHub dan perubahan heading serta test terkonfirmasi.
- PHPUnit/CI belum dijalankan atau belum ada hasil yang dapat diklaim pada saat laporan ini dibuat.
- Belum ada klaim fitur logo/fav icon lulus runtime. Source login memang sudah memanggil service untuk keduanya; pemeriksaan lanjutan perlu memastikan regression test mencakup asset logo dan favicon yang valid serta fallback identitas.
- VPS tidak diuji ulang, sesuai instruksi owner.

## STATUS
- Perbaikan heading nama madrasah: diimplementasikan pada branch audit.
- Pembacaan logo aplikasi dan favicon: sudah ada pada source sebelumnya; tidak dibuat ulang.
- Sinkronisasi logo/fav icon perlu test source lebih lanjut, bukan pengujian VPS.
- Belum digabung ke branch sumber dan belum masuk ZIP distribusi.
