# Laporan Milestone ADM-02 — Profil Madrasah

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
Source SHA diuji: `1e4f2c044609cf11b8a29eb8be381016508cbc98`  
PR #23: OPEN / DRAFT / NOT MERGED

## A. AUDIT

Source baseline memiliki model `Tenant` dan kolom dasar `name`, `code`, `nsm`, `npsn`, `address`, `phone`, `email`, `logo_path`, dan `status`. Belum ada halaman Admin untuk mengelola profil madrasah, dan belum tersedia kolom untuk nama singkat, jenjang, negeri/swasta, bagian alamat terstruktur, website, nama Kepala Madrasah, atau informasi tambahan.

Permission `tenant.manage` sudah tersedia pada katalog role dan diberikan kepada role `ADMIN_MADRASAH`; permission tersebut dipakai sebagai gate halaman profil.

## B. DESAIN

- Profil disimpan pada row Tenant instalasi yang sedang terautentikasi.
- Tidak ada tabel akademik bersama dan tidak ada akses lintas instalasi.
- Kode instalasi dan status operasional tenant tidak dapat diedit dari halaman ini.
- Kolom baru dibuat nullable agar migration tidak menebak atau mengubah data legacy.
- NSM harus 12 digit bila diisi; NPSN harus 8 digit bila diisi; keduanya tetap unik pada database instalasi.
- Jenjang dibatasi pada MI/MTs/MA/RA; status negeri/swasta dibatasi pada NEGERI/SWASTA.
- Audit log hanya mencatat nama kolom yang berubah, bukan nilai PII.
- Rollback migration hanya boleh dilakukan bila semua kolom baru masih NULL; jika sudah berisi data, rollback berhenti.

## C. IMPLEMENTASI

### File baru

- `backend-laravel/database/migrations/2026_11_24_220001_extend_tenant_profile_fields.php`
- `backend-laravel/app/Livewire/Admin/TenantProfileManagement.php`
- `backend-laravel/resources/views/livewire/admin/tenant-profile-management.blade.php`
- `backend-laravel/tests/Feature/TenantProfileManagementTest.php`

### File diubah

- `backend-laravel/app/Models/Tenant.php`
- `backend-laravel/routes/web.php`
- `backend-laravel/resources/views/layouts/app.blade.php`

### Fitur

- Route `/admin/school-profile`.
- Link navigasi desktop dan mobile dengan gate permission `tenant.manage`.
- Nama resmi/singkat, NSM, NPSN, jenjang, status negeri/swasta.
- Alamat, desa/kelurahan, kecamatan, kabupaten/kota, provinsi, kode pos.
- Telepon, email, website, nama Kepala Madrasah, informasi tambahan.
- Transaksi row-lock dan audit event `TENANT_PROFILE_UPDATED`.
- Tidak ada pengubahan `Tenant::status`, kode instalasi, atau `logo_path` pada milestone ini.

### Backup

Backup byte-exact dan manifest SHA ada pada:
`backend-laravel/backup_before_patch/ADM_02_TENANT_PROFILE_20261124/`

Semua source yang diubah memiliki backup yang cocok.

## D. PENGUJIAN

Exact source SHA: `1e4f2c044609cf11b8a29eb8be381016508cbc98`

- Tenant Backend CI: [Run #45402844847](https://github.com/zane0184/cbt_november/actions/runs/45402844847) — SUCCESS.
- PHP lint: PASS.
- MySQL 8.0.46/InnoDB identity migration gate: PASS.
- Full PHPUnit: **565 passed, 2.456 assertions, 134,05 detik**.
- Test profil: save, validasi NSM/NPSN/status, non-Admin denied, dan tenant-bound update — PASS.
- Benchmark MySQL disposable dan PDF policy verification: PASS.
- ZIP workflow: [Run #45402844935](https://github.com/zane0184/cbt_november/actions/runs/45402844935) — SUCCESS.
- Artifact kandidat ID: `14922728949`, ukuran 19.790.346 byte.

ZIP tersebut bukan distribusi final. Fresh install, upgrade, backup/restore, staging, dan E2E tiga portal belum selesai.

## E. STATUS

**ADM-02 — PARTIAL.**

| Area | Status |
|---|---|
| Profil dasar tenant | PASS pada source/test |
| NSM/NPSN/jenjang/negeri-swasta | PASS pada source/test |
| Alamat/kontak/Kepala Madrasah | PASS pada source/test |
| Tenant boundary dan permission | PASS pada feature test |
| Audit changed-field names | PASS |
| Logo/favicon | Belum — ADM-03 |
| Branding web/login/footer/social media | Belum — ADM-04 |
| Kop dokumen dan preview cetak | Belum — ADM-05 |
| Penggunaan profil pada report PDF nyata | NOT VERIFIED |
| Staging/database instalasi | BLOCKED / NOT VERIFIED |
| Produksi | NOT TOUCHED |

`ReportHeaderService` tersedia pada source, tetapi pencarian pemakaian service belum menemukan integrasi aktif ke jalur report. Karena itu, kop dokumen belum dinyatakan bekerja.

## F. LANGKAH SELANJUTNYA

1. ADM-03: logo aplikasi/dokumen dan favicon; validasi MIME/ekstensi/dimensi/ukuran, storage tenant-isolated, preview, replace, delete confirmation.
2. ADM-04: branding aplikasi/login/footer/media sosial per instalasi dan cache invalidation.
3. ADM-05: konfigurasi kop, preview, dan integrasi nyata ke daftar peserta/daftar hadir/berita acara/rekap nilai/laporan.
4. Lanjutkan sisa ADM-06–23; status PARTIAL tidak berarti diterima.
5. Staging tetap menunggu akses operator; jangan menjalankan migration pada produksi.

Tidak ada klaim siap produksi atau siap distribusi.
