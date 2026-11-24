# Checkpoint P1 — Admin Username Preflight

Tanggal: 24 November 2026  
Branch: `work/p1-seven-targets-20261124`  
Source code commit: `48f84b57e802294e3536e087c8c077fac0d0a1f2`  
PR #23: OPEN / DRAFT / NOT MERGED

## Posisi

- Preflight sekarang memeriksa Admin username, Guru NIP/NIK/NUPTK, Siswa NISN.
- Memeriksa required schema, relasi user/profile, tenant, exact-one role, profil silang, dan akun tanpa profil.
- Test valid/invalid membandingkan snapshot hash tabel sebelum/sesudah command.
- Backup byte-exact: `P1_IDENTITY_PREFLIGHT_ADMIN_USERNAME_20261124/BACKUP_MANIFEST.md`.

## Status pengujian

CI exact commit: PENDING.  
PHP lint: PENDING.  
PHPUnit MySQL 8 disposable: PENDING.  
Benchmark SQLite smoke: PENDING.  
Production database/VPS: tidak disentuh.

P1-ID-03A: PARTIAL.  
P1-ID-03B: BLOCKED.  
Tidak ada merge, migration, atau deployment.

## Langkah berikutnya

Periksa CI exact commit, perbaiki jika perlu, lalu review koordinator sebelum conditional DB constraints.
