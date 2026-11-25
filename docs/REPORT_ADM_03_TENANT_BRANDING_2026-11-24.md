# Laporan Audit dan Implementasi ADM-03 — Tenant Branding Assets
Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
PR: #23 — OPEN / DRAFT / NOT MERGED  
Branch: `work/p1-seven-targets-20261124`  
Base: `work/phpunit-41-root-cause-20261123`  
Exact source HEAD yang diuji: `41038aa303ed728c777a32b3254d2ec672ff1860`

## AUDIT

### Sumber dan posisi kerja
- Repository GitHub dan branch di atas adalah sumber kerja aktif sesuai addendum owner.
- Checkpoint lama, laporan P1, keputusan arsitektur instalasi mandiri, addendum role/proktor, dan laporan ADM-02 diperiksa.
- Direktori lokal `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tidak tersedia pada runtime ini. Source aktif tetap dapat diperiksa dan diubah pada repository GitHub.
- ZIP baseline asli tidak berada pada mount lokal. Library menyimpan catatan arsip dan hash historis, tetapi byte ZIP mentah tidak dapat dimaterialisasi pada sesi ini. ZIP asli tidak diubah.
- Tidak ada akses atau perubahan ke database produksi, `.env` produksi, konfigurasi VPS, atau deployment.

### Arsitektur yang diterapkan
- Produk adalah satu instalasi aplikasi mandiri per madrasah, masing-masing dengan database, storage, konfigurasi, domain, dan installation identity sendiri.
- `LocalInstallationIdentity` yang valid menjadi otoritas identitas lokal; authenticated user dan `TenantContext` harus cocok dengannya.
- Dalam production, identitas lokal yang hilang, ganda, salah product code, atau tidak valid menyebabkan resolver fail-closed.
- `tenant_id`, `TenantScoped`, dan `TenantContext` dipertahankan untuk isolasi internal/kompatibilitas legacy; tidak dipakai sebagai arsitektur SaaS akademik bersama.
- Test dua tenant dalam satu database diberi label legacy regression only. Acceptance antar-madrasah menggunakan dua database dan storage disposable yang berbeda.
- Central Super Admin tetap dibatasi untuk lisensi/aktivasi/entitlement; audit ini tidak membuktikan seluruh jalur komunikasi pusat atau larangan akses akademik pusat.

### Temuan dan gap
1. Resolver authenticated sebelumnya belum mengikat akun/context ke local installation identity. Sudah diperbaiki dan diuji.
2. Favicon sebelumnya dapat menghasilkan link tanpa fallback yang tervalidasi berdasarkan konten. Sekarang PNG/WebP diverifikasi dari image type dan finfo MIME; fallback SVG tersedia.
3. PDF fallback untuk logo dokumen tidak terbaca kini diuji.
4. Migration kini memastikan indeks referensi non-unik untuk tiga path branding; shared reference tetap diperbolehkan.
5. HTTP Content-Type aktual dari Nginx/aaPanel belum diuji. Ini menahan ADM-03 dari status ACCEPTED.
6. Acceptance arsitektur yang lebih luas—isolasi seluruh data akademik dan komunikasi lisensi pusat—belum dibuktikan oleh pengujian branding ini.

## DESAIN

- Branding path tetap berada di managed root instalasi lokal dan selalu divalidasi terhadap identity, user, context, canonical storage root, dan realpath.
- Symlink internal hanya diterima bila target tetap berada dalam managed branding root. Symlink eksternal, lintas tenant, lintas instalasi, broken, branding directory symlink, dan public storage root symlink ditolak.
- Operasi delete pada symlink menghapus entri symlink saja, bukan target.
- Penghapusan file biasa hanya dilakukan jika tidak ada referensi lain.
- Path index bersifat non-unik agar satu file boleh dipakai oleh beberapa field branding.
- Migration forward-only: `down()` fail-closed agar kolom/index legacy yang mungkin sudah ada tidak dihapus tanpa review.
- Favicon PNG/WebP dipilih berdasarkan konten, bukan ekstensi saja; link HTML menggunakan MIME terverifikasi. HTTP response tetap diverifikasi pada staging.
- PDF menggunakan document logo valid; fallback ke app logo; jika seluruh logo tidak tersedia, ReportHeaderService memakai text fallback.

## IMPLEMENTASI

### File source yang berubah pada rentang ADM-03
1. `backend-laravel/app/Services/TenantBrandingService.php`
   - Resolver identity/context fail-closed.
   - Pemeriksaan managed path, realpath, tenant/install namespace, symlink, dan delete-safe.
   - `faviconAttributes()` memeriksa image type dan MIME aktual PNG/WebP.
2. `backend-laravel/app/Livewire/Admin/TenantBrandingAssetsManagement.php`
   - Render favicon hanya dari atribut favicon yang tervalidasi.
3. `backend-laravel/resources/views/livewire/admin/tenant-branding-assets-management.blade.php`
   - Preview favicon upload sementara dan preview aset tersimpan.
   - Copy menjelaskan storage terpisah per instalasi, bukan SaaS antar-tenant.
4. `backend-laravel/resources/views/layouts/app.blade.php`
   - MIME favicon tervalidasi dan fallback SVG.
5. `backend-laravel/resources/views/auth/login.blade.php`
   - MIME favicon tervalidasi dan fallback SVG; copy multi-tenant diperbaiki.
6. `backend-laravel/public/favicon.svg`
   - Favicon fallback generik.
7. `backend-laravel/database/migrations/2026_11_24_230001_add_tenant_branding_asset_paths.php`
   - Kolom path idempotent dan indeks referensi non-unik; rollback fail-closed.
8. `backend-laravel/tests/Feature/TenantBrandingAssetsManagementTest.php`
   - Identity, path/symlink, favicon, PDF, migration matrix, dan dua-installation tests.

### Backup byte-exact
Semua backup dibuat sebelum patch yang bersangkutan dan dibandingkan dengan blob source sebelum perubahan. Nilai berikut adalah Git blob SHA content-addressed.

| Backup directory / file | Git blob SHA |
|---|---|
| `ADM_03_FAVICON_PDF_UNREADABLE_FALLBACK_20261124/TenantBrandingService.php.before` | `540fcdf117a534862c81ae50a521fbe83e5d8212` |
| `ADM_03_FAVICON_PDF_UNREADABLE_FALLBACK_20261124/TenantBrandingAssetsManagementTest.php.before` | `ef618c60851234bd1b3b95b9b9455c64fd4d4c07` |
| `ADM_03_FAVICON_PDF_UNREADABLE_FALLBACK_20261124/TenantBrandingAssetsManagement.php.before` | `456496042cc26db87dd389a3b13263bfee1eac54` |
| `ADM_03_FAVICON_PDF_UNREADABLE_FALLBACK_20261124/tenant-branding-assets-management.blade.php.before` | `0b4b5f0ff21a0e634dca1b9c9846591a8f57d25a` |
| `ADM_03_FAVICON_PDF_UNREADABLE_FALLBACK_20261124/app.blade.php.before` | `db2f155a58c0dcaeb4e7fa142e53ce04b83cae0d` |
| `ADM_03_FAVICON_PDF_UNREADABLE_FALLBACK_20261124/login.blade.php.before` | `be8a70c990db5c687e3b4a624c9ad8f925fd09fb` |
| `ADM_03_WEBP_PREVIEW_TEST_FIX_20261124/TenantBrandingAssetsManagementTest.php.before` | `cedf34de2e0237f5a89f318e9740e5dc65556b0e` |
| `ADM_03_INSTALLATION_IDENTITY_BINDING_20261124/TenantBrandingService.php.before` | `6c8dfe203c94af88239e465bb173bcc16b0c4227` |
| `ADM_03_INSTALLATION_IDENTITY_BINDING_20261124/TenantBrandingAssetsManagementTest.php.before` | `d6fb7403d792d41b38448ce6682918d518a3ab81` |
| `ADM_03_IDENTITY_TEST_ESCAPE_FIX_20261124/TenantBrandingAssetsManagementTest.php.before` | `714315a8d1b9fe116fac8a14bd15e52f7db1a628` |
| `ADM_03_MIGRATION_INDEX_AND_CROSS_SYMLINK_20261124/2026_11_24_230001_add_tenant_branding_asset_paths.php.before` | `496c8215da2f812d19e8cdcd01a50f4623a9dc41` |
| `ADM_03_MIGRATION_INDEX_AND_CROSS_SYMLINK_20261124/TenantBrandingAssetsManagementTest.php.before` | `85bc0d404e9c0a441b9dcae1ac1f78657d04ea40` |
| `ADM_03_MIGRATION_NAMESPACE_ESCAPE_FIX_20261124/2026_11_24_230001_add_tenant_branding_asset_paths.php.before` | `f92c7f1aea434144cbade6fa783021c38261d7d6` |
| `ADM_03_INDEX_SNAPSHOT_ORDER_FIX_20261124/TenantBrandingAssetsManagementTest.php.before` | `8c70c589b1fc0669a7a05de16df50113b9110644` |
| `ADM_03_PRODUCTION_IDENTITY_FAIL_CLOSED_TEST_20261124/TenantBrandingAssetsManagementTest.php.before` | `a4391a16675d798768c8fda160fd2bc78b2a4396` |
| `ADM_03_PRODUCTION_ENV_FIX_20261124/TenantBrandingAssetsManagementTest.php.before` | `3a19943b2ed56e5f356de56ffb6471597219b50a` |
| `ADM_03_PDF_TEXT_FALLBACK_ASSERTION_20261124/TenantBrandingAssetsManagementTest.php.before` | `2055b5d9e0a81424b2eacd32d18b0b5c5c0805e1` |

Backup historis sebelumnya—termasuk blocker reconciliation, context guard, upload symlink, migration matrix, PDF fixture, dan guard switch—tetap dipertahankan di `backend-laravel/backup_before_patch/`; tidak ada backup yang dihapus atau ditimpa.

### Commit implementasi utama
- `41038aa303ed728c777a32b3254d2ec672ff1860` — render assertion PDF text fallback ketika seluruh logo tidak tersedia.
- `dd5617f43d2851236b5095f01e5fa0712d30271d` — favicon fallback dan PDF unreadable test.
- `996d7745dffb6f795ee2e438dee00f89f1204016` — identity binding.
- `758ad9d8078c1afa7eb2f331e266249b5997d32f` — migration indexes dan cross-install symlink.
- `8980a24e47c4c473f613a3071de25a3b78a1c6c1` — urutan snapshot index diperbaiki.
- `2bb51ab52e34c2305b84c642809293ccf72e373a` — production identity fail-closed test.
- `41038aa303ed728c777a32b3254d2ec672ff1860` — fixture environment detector diperbaiki.

## PENGUJIAN

### Gerbang CI terbaru
<https://github.com/zane0184/cbt_november/actions/runs/45438946455>

- Exact SHA: `41038aa303ed728c777a32b3254d2ec672ff1860`
- MySQL: 8.0.46, InnoDB disposable.
- PHPUnit: **586 passed, 0 failed, 2.775 assertions**.
- PHP lint aplikasi, migration, test: PASS.
- Composer validation dan platform check: PASS.
- Login identity migration pada MySQL disposable: PASS.
- Migration matrix: PASS.
- Two-installation DB/storage test: PASS.
- Benchmark MySQL disposable: PASS.
- ReportPolicy/PDF policy verification: PASS.
- Tidak ada migration production.

### Build ZIP
[Build Standalone Web CBT Deployment ZIP #45438946955](https://github.com/zane0184/cbt_november/actions/runs/45438946955) — SUCCESS pada exact SHA yang sama. Ini hanya membuktikan archive workflow berhasil; bukan acceptance ZIP generik, fresh install, upgrade, backup/restore, atau status siap distribusi.

### Kegagalan historis yang telah diperbaiki
- #45429567235: satu kegagalan fixture Livewire WebP; diganti dengan verifikasi stored WebP preview tanpa melemahkan validasi konten.
- #45433447406: snapshot indeks dipakai sebelum didefinisikan; urutan snapshot diperbaiki.
- #45435650639: callback `detectEnvironment` menerima argumen yang tidak disediakan framework; closure diperbaiki tanpa menghapus assertion.
- Run sebelumnya juga menemukan kesalahan escape namespace pada patch; diperbaiki dan diuji ulang.
- Tidak ada assertion yang dihapus atau di-skip untuk memperoleh CI hijau.

### Yang belum diuji
- HTTP response header `Content-Type` PNG/WebP/SVG dari Nginx/aaPanel pada staging.
- Fresh install/upgrade/backup/restore generik per madrasah.
- Seluruh acceptance arsitektur akademik lintas instalasi dan komunikasi lisensi pusat.

## Status dan langkah berikutnya

- ADM-03: **PARTIAL / NOT ACCEPTED** (38 PASS, 1 legacy-only regression, 2 PARTIAL).
- P1 Target 1: masih berjalan; ADM-03 code/CI gate pass, arsitektur backend penuh belum selesai.
- P1 Target 2: ADM-03 belum accepted; jangan mulai ADM-04/05.
- P1 Target 3 Web Guru: belum diaudit end-to-end pada tahap ini.
- P1 Target 4 Web Siswa CBT: belum diaudit end-to-end pada tahap ini.
- P1 Target 5 kurikulum/KBC/Pembelajaran Mendalam/provenance: belum diaudit end-to-end pada tahap ini.
- P1 Target 6 VPS/aaPanel staging: belum dijalankan; produksi tidak disentuh.
- P1 Target 7 ZIP generik: workflow archive success, acceptance fresh install/upgrade/restore belum dilakukan.
- Android native dan Web Super Admin pusat tetap ditunda sampai Juni 2027.
- Handover coding agent: belum diperlukan; pekerjaan masih pada ADM-03.
- Tahap berikutnya: staging disposable dengan Nginx/aaPanel untuk memverifikasi header HTTP PNG/WebP/SVG dan fallback, kemudian perbarui matriks 41 acceptance. Setelah seluruh acceptance terbukti, baru minta persetujuan owner sebelum melanjutkan ADM-04.
