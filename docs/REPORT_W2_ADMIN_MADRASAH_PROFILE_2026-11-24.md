# Laporan W2 — Profil Dasar Madrasah Admin

Tanggal: 24 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/solo-web-cbt-completion-20261123-execution`

## AUDIT
- Skema existing `tenants` sudah memiliki `name`, `code`, `nsm`, `npsn`, `address`, `phone`, `email`, `logo_path`, dan `status`.
- Model Tenant mendukung SoftDeletes/UUID, dan installer membuat tenant lokal dengan `code` yang terikat pada state instalasi.
- Permission `tenant.manage` sudah tersedia pada katalog permission dan diberikan kepada role `ADMIN_MADRASAH`.
- Sebelumnya tidak ada route, komponen, atau view profil madrasah pada Web Admin. Layout menampilkan identitas statis.
- Tidak ada perubahan skema atau field tambahan untuk jenjang, status negeri/swasta, wilayah terperinci, website, nama Kepala Madrasah, kop surat, atau branding lanjutan pada tahap ini.

## DESAIN
- Satu profil Admin hanya boleh mengelola tenant yang berasal dari `auth()->user()->tenant_id`.
- Wajib exact role tunggal `ADMIN_MADRASAH`, permission `tenant.manage`, dan bukan System Tenant.
- Semua query update dikunci dan di-scope pada tenant pengguna; field `code` dan `status` tidak dapat diedit.
- Perubahan dasar dicatat pada `audit_logs` melalui AuditService dalam transaksi yang sama.
- Gunakan kolom yang sudah tersedia; tidak membuat migration.
- Upload logo/login header/file assets ditahan dari patch ini sampai audit disk/storage dan route penayangan privat selesai.

## IMPLEMENTASI
File baru:
- `backend-laravel/app/Livewire/Admin/MadrasahProfile.php`
- `backend-laravel/resources/views/livewire/admin/madrasah-profile.blade.php`

File existing diubah:
- `backend-laravel/routes/web.php`: route `GET /admin/profile`.
- `backend-laravel/resources/views/layouts/app.blade.php`: link sidebar menggunakan permission `tenant.manage`.
- `backend-laravel/tests/Feature/AdminUiVerificationTest.php`: test positif, negative role/permission, audit values, dan batas tenant.

Byte-exact backups verified:
- `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/web.php.before` — `f6df667627c7b134ce7ed6f90f916a9bab2730a9`.
- `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/app.blade.php.before` — `4830d03985f00fdf6fea5297c8950d211191cc63`.
- `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/AdminUiVerificationTest.php.before-profile` — `a9e3a071320eb0c05273add0888b58edd5ea705b`.
- Komponen baru sebelum koreksi namespace: `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/MadrasahProfile.php.before-namespace-fix` — `08378afcad67816c16419e7a05f7676e9776b229`.

Catatan:
- File baru `MadrasahProfile.php` dan view tidak memiliki file pra-patch untuk dibackup.
- Namespace awal terduplikasi akibat serialisasi tool; telah diperbaiki dan backup sebelum koreksi dibuat.
- Tidak ada database, migration, VPS, ZIP, Android, atau Web Super Admin disentuh.

## PENGUJIAN
- Test baru:
  - `admin_can_view_and_update_own_madrasah_profile_with_audit_log`
  - `admin_madrasah_profile_requires_exact_admin_role_and_tenant_manage_permission`
  - `madrasah_profile_uses_only_authenticated_admin_tenant_and_never_updates_installation_code`
- CI test fitur profil masih PENDING. Runs #1702, #1705, #1707, #1708, dan #1709 dibatalkan sebelum checkout oleh workflow concurrency; run #1703/#1706 gagal pada probe MySQL yang diperbaiki terpisah. Belum ada focused test atau PHPUnit yang dijalankan untuk fitur profil. Run final harus dijalankan pada satu HEAD stabil sebelum status berubah menjadi VERIFIED.
- Tidak ada runtime browser/manual UAT yang dijalankan dalam tahap ini.

## Tambahan — temuan CI MySQL identity probe

Run #1703 dan #1706 gagal sebelum test aplikasi karena probe CI menanyakan engine tabel `tenants` sebelum Composer/migration membuat schema disposable. Run #1702, #1705, #1707, dan #1708 dibatalkan oleh concurrency sebelum checkout. Tidak satu pun run tersebut menjalankan focused Admin profile tests atau PHPUnit untuk patch profil; status test tetap PENDING.

Probe workflow diperbaiki dan dipisah sesuai lifecycle database:
- sebelum Composer: memverifikasi nama database dan versi MySQL 8 (tanpa mengasumsikan schema tabel sudah dibuat);
- setelah PHPUnit menjalankan migration/schema: membaca engine aktual tabel `tenants` dari `information_schema.TABLES` pada database disposable;
- tetap fail-closed kecuali tabel target nyata ber-ENGINE InnoDB.

Backup workflow byte-exact:
- `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/tenant-backend-ci.yml.before-mysql-engine-probe-fix`
- blob SHA `caf4202cbeb5b7f750222d4f5f0414d456fee180`.
- source workflow sebelum patch: blob SHA `caf4202cbeb5b7f750222d4f5f0414d456fee180`.
- fix workflow pertama (probe engine tabel terlalu awal): `37a954e0f9006a90d109c5165436b9ef3e7c22f3`.
- backup workflow sebelum pemisahan lifecycle: `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/tenant-backend-ci.yml.before-probe-after-migrations` — blob SHA `f655b44188ff788fcdea518d73ee024adc287dd2`.
- koreksi final urutan probe: commit `46c5f0ba56d42e9103c12179a80911d1844f21fc`.

## STATUS BACKLOG
- Profil dasar (nama, NSM, NPSN, alamat, telepon, email): IMPLEMENTED / TEST PENDING.
- Identitas instalasi/code/status protection: IMPLEMENTED / TEST PENDING.
- Logo/favicon/login branding: NOT STARTED; perlu audit private storage/delivery.
- Metadata lokasi rinci, jenjang, negeri/swasta, kepala madrasah, website/media sosial: BLOCKED BY SCHEMA/DESIGN; belum ada kolom pada schema aktif.
- Kop surat fleksibel dan preview laporan: NOT STARTED.
- ADM-20 tetap BLOCKED menunggu marker kelayakan ujian pusat kanonis.
- Android dan Web Super Admin tetap DEFERRED sampai Juni 2027.

## LANGKAH BERIKUTNYA
1. Tunggu CI pada exact source SHA dan perbaiki hanya kegagalan yang terbukti.
2. Setelah PASS, lanjutkan desain additive untuk metadata profil tambahan dan audit logo private storage/delivery.
3. Jangan membuat migration untuk field tambahan sebelum desain schema/backward compatibility ditinjau.
