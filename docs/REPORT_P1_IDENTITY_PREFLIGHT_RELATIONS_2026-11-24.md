# Laporan P1 — Penguatan Read-only Login Identity Preflight

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
PR #23: OPEN / DRAFT / NOT MERGED

## A. AUDIT

Preflight sebelumnya memeriksa format NIP/NIK/NUPTK/NISN dan duplikasi per tenant, tetapi belum memeriksa kelengkapan kolom, relasi user-profile, konsistensi tenant, exact-one role, akun tanpa profil, serta profil lintas role.

Test sebelumnya juga belum membuktikan bahwa command tidak mengubah data.

## B. DESAIN

- Schema wajib diperiksa sebelum query data; tabel/kolom hilang harus membuat command berhenti.
- Semua pemeriksaan memakai Query Builder/Schema read-only.
- Relasi aktif memakai profil yang belum soft-deleted.
- Keunikan identitas tetap memeriksa seluruh baris, termasuk soft-deleted, karena unique index tenant/identifier juga mencakup baris tersebut.
- Role aktif harus tepat satu dan hanya `ADMIN_MADRASAH`, `GURU`, atau `SISWA`.
- Profil Guru harus terkait ke satu akun tenant yang sama dengan tepat satu role GURU.
- Profil Siswa harus terkait ke satu akun tenant yang sama dengan tepat satu role SISWA.
- Akun Guru/Siswa harus mempunyai profil aktif yang sesuai.
- Output hanya jumlah konflik; tidak boleh menampilkan NIP/NIK/NISN atau username.
- Test membandingkan hash snapshot tabel sebelum/sesudah command untuk membuktikan tidak ada mutasi data.

## C. IMPLEMENTASI

File berubah:

- `backend-laravel/app/Console/Commands/LoginIdentityPreflightCommand.php`
  - Required schema check untuk teacher_profiles, student_profiles, users, role_user, roles.
  - Pemeriksaan NIK pada PNS/PPPK dan NIP pada Honorer sebagai kategori yang tidak diperbolehkan.
  - Pemeriksaan tenant profile vs user.
  - Pemeriksaan exact-one role.
  - Pemeriksaan akun Guru/Siswa tanpa profil aktif.
  - Pemeriksaan role nonaktif/legacy, profil silang Guru/Siswa, dan cardinality user-profile.
  - Semua query tetap read-only.

- `backend-laravel/tests/Feature/LoginIdentityPreflightCommandTest.php`
  - Fixture valid kini memiliki role yang benar.
  - Test kategori identitas yang salah, NUPTK invalid, NISN invalid.
  - Test mismatch tenant, profil silang, multi-role, dan akun tanpa profil.
  - Test schema kolom hilang.
  - Snapshot hash tabel sebelum/sesudah pada skenario valid dan invalid.

Backup byte-exact:

`backend-laravel/backup_before_patch/P1_IDENTITY_PREFLIGHT_RELATIONS_20261124/BACKUP_MANIFEST.md`

## D. PENGUJIAN

- PHPUnit untuk perubahan preflight: menunggu GitHub Actions exact HEAD.
- PHP lint command/test: menunggu GitHub Actions exact HEAD.
- Pengujian MySQL 8/InnoDB: menunggu CI exact HEAD.
- Belum ada database produksi yang dihubungkan atau diubah.
- Belum ada migration yang dijalankan.

## E. STATUS

**Implementasi preflight relasional tersedia; verifikasi runtime belum selesai.**

P1-ID-03A masih PARTIAL sampai CI exact HEAD lulus dan review koordinator menyetujui cakupan pemeriksaan. Hasil preflight bersih pun hanya membuktikan kondisi relasi/format yang dicakup, bukan bukti provenance identitas atau persetujuan backfill.

P1-ID-03B tetap BLOCKED. Belum ada migration constraint format/status raw SQL.

## F. PEKERJAAN BERIKUTNYA

1. Jalankan PHP lint dan PHPUnit pada MySQL 8 disposable.
2. Perbaiki setiap kegagalan berdasarkan log.
3. Tambahkan test duplicate group preflight pada skema legacy tanpa unique index jika dibutuhkan untuk membuktikan jalur upgrade.
4. Selesaikan review mass-assignment dan data writer.
5. Minta review koordinator sebelum P1-ID-03B.
6. Jangan jalankan preflight atau migration pada database produksi tanpa persetujuan dan target yang telah diverifikasi.

## G. SAFETY

- Tidak ada merge.
- Tidak ada deployment.
- Tidak ada migration.
- Tidak ada perubahan database produksi.
- PR #23 tetap Draft.
