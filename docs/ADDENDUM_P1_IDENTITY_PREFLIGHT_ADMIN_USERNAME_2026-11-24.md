# Addendum P1 — Admin Username dan Preflight Identitas

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
Source code commit: `48f84b57e802294e3536e087c8c077fac0d0a1f2`  
PR #23: OPEN / DRAFT / NOT MERGED

## AUDIT

Kontrak owner mengharuskan Admin Madrasah login menggunakan username. Preflight sebelumnya sudah memeriksa Guru dan Siswa, tetapi belum memvalidasi username Admin.

## DESAIN

- Username Admin wajib tersedia dan tidak boleh kosong atau memiliki whitespace tepi.
- Duplikasi username Admin diperiksa per tenant setelah normalisasi `LOWER(TRIM(username))`.
- Tidak mengubah aturan login Guru/Siswa dan tidak mengizinkan fallback identitas.
- Preflight tetap read-only dan hanya menampilkan jumlah konflik.

## IMPLEMENTASI

- `LoginIdentityPreflightCommand` kini mewajibkan kolom `users.username`.
- Menambah pemeriksaan Admin tanpa username, whitespace tepi, dan kelompok duplikat username per tenant.
- Menambah fixture Admin valid pada test preflight.
- Menambah regression test untuk username kosong dan berpagar whitespace; test membandingkan hash snapshot tabel sebelum/sesudah command.
- Backup terbaru: `backend-laravel/backup_before_patch/P1_IDENTITY_PREFLIGHT_ADMIN_USERNAME_20261124/BACKUP_MANIFEST.md`.

## PENGUJIAN

- PHP lint: menunggu CI.
- PHPUnit MySQL 8 disposable: menunggu CI.
- Benchmark smoke SQLite: menunggu CI.
- Tidak ada migration atau database produksi yang dijalankan.

## STATUS

Implementasi preflight identitas Admin/Guru/Siswa tersedia. Verifikasi exact source SHA `48f84b57...` masih menunggu CI. P1-ID-03A tetap PARTIAL sampai semua test lulus dan review koordinator selesai. P1-ID-03B tetap BLOCKED.

## NEXT ACTION

1. Periksa CI exact source commit.
2. Perbaiki kegagalan yang terbukti melalui log.
3. Selesaikan review mass-assignment/data writers.
4. Minta review koordinator sebelum P1-ID-03B.
