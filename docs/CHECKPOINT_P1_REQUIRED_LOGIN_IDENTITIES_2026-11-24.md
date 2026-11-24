# Checkpoint P1 — Identitas Login Wajib

Tanggal: 24 November 2026  
Branch: `work/p1-seven-targets-20261124`  
Source HEAD yang diaudit sebelum laporan/checkpoint: `7ecbd882e9b5b8b5171c1d64ecf2c5b38747216f`  
Commit laporan: `29962b07a6d338a7086dcdff5791c440713495f1`  
PR: #23 — OPEN / DRAFT / NOT MERGED  
Base P1: `1c33e5d422175f9bd85d206230271a9fe5a4eecd`

## Posisi tahap

**Subtahap identitas login wajib: IMPLEMENTASI SOURCE DITULIS — PENGUJIAN BELUM VERIFIED.**

### AUDIT
- Source P1 sebelumnya mengizinkan Guru tanpa NIP/NIK dan username Guru sebagai login.
- Master spec dan addendum lama bertentangan dengan keputusan owner terbaru 24 November.
- Keputusan owner terbaru dicatat pada `docs/ADDENDUM_OWNER_REQUIRED_LOGIN_IDENTITIES_2026-11-24.md`.
- Beberapa halaman sebelumnya menampilkan NIP/NIK/NISN lengkap.
- Status CI pada exact source HEAD yang diaudit tidak memberikan hasil status/run melalui pemeriksaan yang tersedia.

### DESAIN
- Admin = username.
- Guru PNS/PPPK = NIP wajib 18 digit.
- Guru Honorer = NIK wajib 16 digit.
- Siswa = NISN wajib 10 digit.
- Resolver tunggal digunakan Web/API; username dan NUPTK bukan fallback Guru.
- Unique index tenant-scoped dengan preflight fail-closed.
- Tidak ada auto-backfill atau perubahan data legacy.

### IMPLEMENTASI
Source berubah dan backup byte-exact tersedia untuk 13 file sebelum patch. Manifest:
`backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/BACKUP_MANIFEST.md`.

Source baru:
- `app/Console/Commands/LoginIdentityPreflightCommand.php`
- `database/migrations/2026_11_24_170001_enforce_tenant_login_identity_uniques.php`
- `tests/Feature/LoginIdentityPreflightCommandTest.php`

Dokumen:
- `docs/ADDENDUM_OWNER_REQUIRED_LOGIN_IDENTITIES_2026-11-24.md`
- `docs/REPORT_P1_REQUIRED_LOGIN_IDENTITIES_2026-11-24.md`

### PENGUJIAN
- Backup: 13/13 SHA cocok.
- PHPUnit, PHP lint, MySQL 8 migration, race test, browser E2E: NOT RUN/NOT VERIFIED.
- Migration tidak dijalankan.
- Command preflight belum dijalankan terhadap DB proyek.
- Produksi/staging tidak disentuh.

## Pekerjaan berikutnya — urutan wajib

1. Verifikasi CI exact HEAD untuk PHP lint + focused/full PHPUnit + disposable MySQL 8/InnoDB.
2. Perbaiki hanya kegagalan yang terbukti; backup file sebelum patch berikutnya.
3. Jalankan identity preflight pada disposable clone dan catat jumlah konflik tanpa menampilkan PII.
4. Audit jalur update/import/recovery identitas; jika belum ada, catat NOT IMPLEMENTED.
5. Audit semua API, session, cache, log, audit, dan export yang berpotensi memuat identifier.
6. Kembali ke integrasi Web Admin ADM-01..23, Web Guru, Web Siswa, dan backend sesuai dependency.
7. Lanjutkan ZIP hanya setelah acceptance seluruh portal dan staging terbukti.

## Status gerbang

| Gate | Status |
|---|---|
| Identitas login sesuai keputusan owner pada source | IMPLEMENTASI DITULIS |
| Backup source sebelum patch | PASS 13/13 SHA |
| Focused PHPUnit exact HEAD | NOT RUN |
| Full PHPUnit exact HEAD | NOT RUN |
| MySQL 8/InnoDB migration | NOT RUN |
| Preflight data legacy | NOT RUN |
| Browser-to-real-Laravel E2E | NOT RUN |
| ZIP final | NOT READY |
| Produksi/deployment | BLOCKED |

Jangan merge PR #23, menjalankan migration produksi, mengubah database produksi, deployment, atau menyatakan siap distribusi tanpa persetujuan owner dan bukti acceptance.


---

## Addendum checkpoint — refinement 24 November 2026

Source HEAD terakhir yang diaudit sebelum dokumen addendum: `84745e2f34df3e6248e3b1dd9fdc662e5d1d9798`.

Tambahan setelah laporan awal:
- Migration kini menolak whitespace-padded identifier sebelum DDL dan memvalidasi struktur index unique yang sudah ada.
- Read-only preflight mendeteksi whitespace pada NIP/NIK/NUPTK/NISN tanpa menampilkan nilai.
- Test DB unique kini mencakup NIP, NIK, NUPTK, NISN.
- Test API mencakup Honorer NIK dan penolakan username Guru; test Web mencakup PPPK NIP.
- Backup manifest berisi 21 snapshot; 21/21 SHA blob cocok.

Dokumen tambahan: `docs/ADDENDUM_P1_REQUIRED_LOGIN_IDENTITIES_REFINEMENT_2026-11-24.md`.

Status tetap:
- Source dan test tertulis.
- Exact-HEAD PHP lint/PHPUnit/MySQL 8/InnoDB belum terverifikasi.
- Migration dan command preflight belum dijalankan terhadap database proyek.
- Tidak ada perubahan database produksi/staging.
- PR #23 tetap OPEN/DRAFT, tidak merge.
- ZIP belum siap distribusi.

Pekerjaan berikutnya: dapatkan bukti exact-HEAD CI, jalankan pengujian MySQL disposable, lalu audit jalur update/import dan integrasi portal lainnya.
