# CHECKPOINT — PAKET 1 WEB ADMIN / STAGE 1 DASHBOARD

Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch implementasi: work/web-admin-package1-implementation-20261122
Commit source yang diuji: 1eb9ee8b6115e5fe40c345d0737ca459bcff29f2
Run pengujian: https://github.com/zane0184/cbt_november/actions/runs/44923715582

## AUDIT
- Posisi dilanjutkan dari WA-01 yang sudah memiliki bukti runtime pada MySQL 8 disposable.
- Dashboard Admin lama masih berada di namespace Operator dan layout masih menampilkan brand gabungan Admin/Guru.
- Root/login Admin masih diarahkan ke URL dashboard lama.
- Query dashboard lama menghitung akun umum dan belum memberi ringkasan periode akademik serta kesehatan instalasi yang aman.
- Batas CBT tetap dipertahankan: Admin tidak mendapat menu Bank Soal/Manajemen Ujian dan endpoint langsung tetap ditolak.
- Pemeriksaan source tahap ini difokuskan pada file yang diperlukan untuk implementasi dashboard; audit statis 23 modul tidak diulang.

## DESAIN
- Dashboard khusus ADMIN_MADRASAH pada /admin/dashboard.
- Ringkasan hanya data administratif dalam tenant aktif: profil siswa, profil Guru, rombel aktif, mata pelajaran aktif.
- Periode aktif ditampilkan berdasarkan record aktif yang benar-benar ada. Jika tidak ada, tampilkan Belum Ditetapkan; jika terdapat lebih dari satu atau relasi periode tidak konsisten, tampilkan Perlu Ditinjau. Dashboard tidak mengaktifkan periode otomatis.
- Distribusi siswa menggunakan rombel aktif pada tahun ajaran aktif.
- Aktivitas terbaru dibatasi pada entitas administratif; tidak menampilkan event operasional ujian.
- Kesehatan instalasi hanya menampilkan status koneksi database dan kemampuan tulis storage, tanpa stack trace/credential.
- Sidebar desktop memiliki scroll independen; navigasi mobile menggunakan menu responsif.
- Dashboard Admin tidak menampilkan menu, angka, atau data CBT operasional.

## IMPLEMENTASI
Branch: work/web-admin-package1-implementation-20261122

File source:
1. backend-laravel/routes/web.php — route /admin/dashboard, root redirect Admin, URL lama Operator menjadi redirect kompatibilitas.
2. backend-laravel/app/Http/Controllers/AuthWebController.php — login Admin Madrasah diarahkan ke dashboard baru.
3. backend-laravel/resources/views/layouts/app.blade.php — brand Panel Admin Madrasah/Portal Guru, sidebar independen, menu Admin dan Guru dipisahkan, mobile navigation.
4. backend-laravel/app/Livewire/Admin/AdminDashboard.php — komponen dashboard dengan otorisasi Admin dan query tenant-scoped.
5. backend-laravel/resources/views/livewire/admin/admin-dashboard.blade.php — UI dashboard eksekutif, periode, distribusi, aktivitas administratif, kesehatan instalasi.
6. backend-laravel/tests/Feature/AdminDashboardAccessTest.php — 3 test baru: tenant scope, larangan konten CBT, periode tidak diaktifkan otomatis, dan penolakan akses Siswa.
7. backend-laravel/tests/Feature/AdminCbtBoundaryTest.php — ekspektasi root redirect diselaraskan ke /admin/dashboard.
8. .github/workflows/wa01-alternative-isolated-runtime.yml — runner otomatis dipindahkan ke branch Paket 1 dan menambahkan test dashboard.

Backup sebelum perubahan:
- docs/backup/web-admin-package1-2026-11-22/routes-web.php.before-admin-dashboard
  - SHA-256: 1928c778eb8cc873dcf8f49eae447e2fa7bcc7871c2e59b1aefb333536ad4e57
  - Git blob: b28abc9e026c63e4fccb9438cd9d5a3c4d9c86c6
- docs/backup/web-admin-package1-2026-11-22/app-layout.blade.php.before-admin-dashboard
  - SHA-256: ffebe8ba9a5adbb799dc1e3ce279a0372b7ce21a21924e19de74bba9433dca7d
  - Git blob: fd7ba39dde9a1299338211801fbf48c71d5e8c21
- docs/backup/web-admin-package1-2026-11-22/AuthWebController.php.before-admin-dashboard
  - SHA-256: 1772e7e6ce279f75f5b6794e05d3c18f1a898d4166b0cfc8be070cde7e1a0f22
  - Git blob: 1f76179dbbf304eb11271b74fbe1d1d38c74a401
- docs/backup/web-admin-package1-2026-11-22/wa01-alternative-runtime.yml.before-package1-dashboard
  - Git blob: 12fdf1d471695fcc7c164fced80211b9e0f3530d
  - SHA-256 belum dihitung pada checkpoint ini.
- docs/backup/web-admin-package1-2026-11-22/AdminCbtBoundaryTest.php.before-admin-dashboard-redirect
  - SHA-256: 139f36aa2e8bb8ce8962e54f54a2beb51785079658bce5cfc79f6e75f1f983e3
  - Git blob: 012488458f6542fccc94442bfe7fd04346cd37b2

SHA-256 source akhir:
- routes/web.php: f8793870f9727c0fcd9386dd304c379783b3263a700c185c75796f7ae5862a99
- resources/views/layouts/app.blade.php: 252e5b1d1d7086199d4289e226005aec3943685dfb4e6179f3e4aa33eed0bbe4
- AuthWebController.php: c02f11e61a1f2656352f489d3dc57dd1ef5d533b26d667fbb326dd365f904c6c
- AdminDashboard.php: fd54a7955ae20e8249ea061acc71241254850fede229486c7808d83ce4a73890
- admin-dashboard.blade.php: 6040e662c2dfe7192fa6069d8c7cde54095ac071ecbef9f487ce23ed92e5002a
- AdminDashboardAccessTest.php: 4afd9be2fd6f73ffebb2a414980bd4f67bb2b8bed5d0a950f7c12e1bf52626ab
- AdminCbtBoundaryTest.php: 09f2dc55471c2c2b5e91a1afc1f9eac79f2570b52e85037239f45bfb8bd589cb
- wa01-alternative-isolated-runtime.yml: 8bb9cd2d67aead78e084e67e7fb9bb9ae2d08a0305e932651332234cb4493c55

## PENGUJIAN
PASS — GitHub Actions run #44923715582, commit 1eb9ee8b6115e5fe40c345d0737ca459bcff29f2.

Lingkungan:
- Ubuntu 24.04.
- PHP 8.3.37.
- Composer 2.11.2.
- MySQL 8.0.46, InnoDB.
- MySQL hostname sementara: b0ebe8e3f3c5.
- Database disposable: cbt_wa01_alt_44923715582_1.
- User aplikasi: wa01_alt_44923715582_1@%.
- Grant aplikasi hanya pada schema disposable tersebut; tidak ada global privilege/grant option.
- Tidak memakai production/VPS credentials.
- Schema kosong sebelum migration.
- 71 base tables sesudah test, semuanya InnoDB.
- Cleanup database dan user disposable berhasil.

Hasil test:
| Test | Hasil | Assertions |
|---|---:|---:|
| AdminCbtBoundaryTest (stale role/grants, semua CBT surfaces) | 1 PASS | 27 |
| AdminDashboardAccessTest | 3 PASS | 28 |
| AuthTest | 7 PASS | 38 |
| MultiTenantAuthTest | 3 PASS | 11 |
| TenantIsolationTest | 10 PASS | 16 |
| WebAdminAuthTest — login Guru NIP | 1 PASS | 5 |
| StudentWebLoginTest | 8 PASS | 27 |
| StudentResultVisibilityTest | 7 PASS | 22 |
| TOTAL | 40 PASS | 174 |

- PHP lint pada app/bootstrap/config/database/routes/tests: PASS.
- Composer validate/install/check-platform-reqs: PASS.
- MySQL identity, schema kosong, grant terbatas, dan Laravel DB config: PASS.
- Test Admin tidak menulis permission rows atau permission_role pivot; assertion jumlah sebelum/sesudah tidak berubah: PASS.
- Tidak menjalankan seeder, RolePermissionBootstrapper, atau migration/DB produksi.
- Artifact: wa01-alternative-runtime-44923715582-1, tersedia 30 hari.

## BATAS YANG MASIH OPEN
- Paket 1 belum selesai: identitas madrasah, periode akademik CRUD/aktivasi, data Guru/Siswa lengkap, penugasan Guru, kenaikan/kelulusan, impor Excel, master Kurmer/KBC versioned, laporan administratif, profil/password, backup/restore dan hardening masih harus dikerjakan.
- Migrasi role OPERATOR/ADMIN/PROKTOR belum diimplementasikan. File OperatorDashboard lama masih tersimpan sebagai source historis; route lamanya kini hanya redirect kompatibilitas. Auth legacy dan role Proktor masih perlu diselesaikan fail-closed dengan migrasi yang menjaga akun/histori.
- Halaman assignment Proktor harus direkonsiliasi ke keputusan Addendum 35; status capability CONFIRMED dan rombel/schedule revision/overlap masih belum dibuktikan.
- CI subset ini bukan seluruh acceptance Web Admin; tidak ada UAT/browser visual/performance/backup-restore/concurrency Proktor.
- Tidak ada merge, deployment VPS, atau perubahan database produksi.

## CHECKPOINT / LANGKAH BERIKUTNYA
1. Lanjutkan Paket 1 Stage 2: manajemen periode akademik dan identitas madrasah.
2. Setiap patch source wajib backup dan hash sebelum perubahan.
3. Jalankan test baru pada MySQL 8 disposable melalui runner branch ini.
4. Setelah semua acceptance Web Admin lulus, lanjut Portal Guru, lalu Portal Siswa.
5. Branch belum di-merge; PR/merge menunggu persetujuan pengguna.
