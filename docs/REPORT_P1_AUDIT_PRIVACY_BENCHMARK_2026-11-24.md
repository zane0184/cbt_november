# Laporan P1 — Audit Privacy, Account Creation, dan Benchmark Fixture

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
PR: #23 — tetap OPEN / DRAFT / NOT MERGED

## A. AUDIT

### Posisi source

- Source code milestone: `7e27b4869b1d014981deed4f8ac001a71214d8b1`.
- HEAD branch setelah perubahan workflow CI: `d3ed6b18b98071c50275c39279f6bcc8c3a8f3bc`.
- PR #23 tetap Draft; tidak ada merge.
- Tidak ada akses atau perubahan database produksi/VPS.
- Tidak ada migration baru atau migration yang dijalankan.

### Temuan yang ditutup pada milestone ini

1. `AuditService` sebelumnya menyimpan old/new audit payload tanpa redaksi rekursif.
2. Dua runtime writer langsung ke `AuditLog::create()` berada pada `InstallerService` dan `ExpireExamAttemptsCommand`; keduanya melewati `AuditService`.
3. `UserManagement::createUser()` membuat akun dan profil Guru/Siswa dalam transaksi, tetapi belum menghasilkan audit event pembuatan akun/profil.
4. Benchmark `Phase7dBenchmarkCommand` membuat TeacherProfile tanpa status/identitas wajib dan NISN sintetis berformat `NISN_001`.
5. Database SQLite benchmark sebelumnya menggunakan file tetap dan cleanup tidak berada di `finally`; exception dapat meninggalkan file dan state runtime.
6. Audit callsite inventory sebelumnya mencatat 107 pemanggilan `AuditService::log()` pada 33 caller files. Sanitizer terpusat kini melindungi payload lama/baru yang melewati service tersebut.

## B. DESAIN

1. Gunakan satu `AuditPayloadSanitizer` untuk seluruh payload audit baru, termasuk nested arrays dan alias key.
2. Jangan menulis ulang histori audit lama.
3. Dua runtime bypass memakai sanitizer yang sama sebelum `AuditLog::create()`.
4. Identitas internal dan jawaban sensitif tidak disimpan mentah dalam old/new audit JSON.
5. IP disimpan sebagai prefix jaringan yang dianonimkan (IPv4 /24, IPv6 /48); User-Agent disimpan sebagai HMAC fingerprint dengan APP_KEY.
6. Benchmark memakai SQLite temporary file unik di direktori sistem sementara, memulihkan koneksi, tenant context, dan auth state dalam `finally`, lalu menghapus database dan sidecar.
7. Fixture benchmark memakai identitas sintetis dengan format final dan unik; tidak dimaksudkan sebagai identitas orang nyata.

## C. IMPLEMENTASI

### File aplikasi yang berubah

- `backend-laravel/app/Services/AuditPayloadSanitizer.php` — baru.
- `backend-laravel/app/Services/AuditService.php` — redaksi old/new, anonimisasi IP, HMAC User-Agent.
- `backend-laravel/app/Services/InstallerService.php` — runtime audit bypass memakai sanitizer.
- `backend-laravel/app/Console/Commands/ExpireExamAttemptsCommand.php` — runtime audit bypass memakai sanitizer; enum alasan disimpan sebagai `scoring_skipped_code`.
- `backend-laravel/app/Livewire/Admin/UserManagement.php` — event `USER_CREATED`, `TEACHER_PROFILE_CREATED`, dan `STUDENT_PROFILE_CREATED` dalam transaksi yang sama.
- `backend-laravel/app/Console/Commands/Phase7dBenchmarkCommand.php` — fixture Guru/Siswa valid sintetis; temporary SQLite unik; cleanup dan pemulihan state pada `finally`.

### Test dan CI

- `backend-laravel/tests/Feature/AdminUiVerificationTest.php` — regresi audit pembuatan akun Guru dan Siswa serta redaksi kontak/identitas.
- `backend-laravel/tests/Feature/AuditPayloadSanitizerTest.php` — regresi nested alias, device/answer data, free-text, IP, User-Agent, serta pencegahan false-positive pada `finished_at`/key aman.
- `.github/workflows/tenant-backend-ci.yml` — menambahkan PDO SQLite dan benchmark smoke; workflow memeriksa bahwa temporary SQLite file dibersihkan setelah command normal selesai.

### Backup

Seluruh file existing yang diubah telah memiliki backup byte-exact dan diverifikasi fetch-back:

- `backend-laravel/backup_before_patch/P1_AUDIT_REDACTION_AND_ACCOUNT_CREATE_20261124/BACKUP_MANIFEST.md`
- `backend-laravel/backup_before_patch/P1_AUDIT_SANITIZER_BENCHMARK_20261124/BACKUP_MANIFEST.md`
- `backend-laravel/backup_before_patch/P1_AUDIT_SANITIZER_EDGECASE_20261124/BACKUP_MANIFEST.md`
- `backend-laravel/backup_before_patch/P1_BENCHMARK_CI_SMOKE_20261124/BACKUP_MANIFEST.md`

## D. PENGUJIAN

### Bukti yang sudah selesai

Pada source SHA `ee0de548cdb3f5f9e885c04fffeb5c4095dc143f`, GitHub Actions #45372478269:

- PHP 8.3 setup: PASS.
- Disposable MySQL 8.0.46/InnoDB identity check: PASS.
- Composer validation/install: PASS.
- PHP lint aplikasi, migration, dan test: PASS.
- Focused Web login/tenant smoke: PASS.
- Full PHPUnit: **535 passed, 0 failed, 2.280 assertions**.

Bukti tersebut hanya mencakup source SHA tersebut, sebelum shared sanitizer dan benchmark changes berikutnya.

### Status exact source milestone terbaru

- Build Standalone Web CBT Deployment ZIP pada source SHA `7e27b4869b1d014981deed4f8ac001a71214d8b1`: workflow SUCCESS. Ini bukan acceptance ZIP final.
- CI untuk source milestone `7e27b4869b1d014981deed4f8ac001a71214d8b1` / branch setelah perubahan masih harus diverifikasi sampai selesai.
- Benchmark smoke dengan PDO SQLite baru ditambahkan pada workflow; hasil runtime belum tersedia.
- Tidak ada PHPUnit lokal pada sesi ini.
- Tidak ada test benchmark runtime pada sesi ini.
- Tidak ada MySQL migration test untuk conditional identity enforcement.

## E. STATUS

**P1 audit privacy/account-create: IMPLEMENTASI TERSEDIA — VERIFIKASI EXACT HEAD MENUNGGU CI.**

**P1-ID-03A: PARTIAL / NOT COMPLETE.** Fixture benchmark dan audit payload kini memiliki patch awal, tetapi seluruh acceptance P1-ID-03A belum lulus. Preflight kategori/relasi, DB immutability test, seluruh fixture inventory, dan mass-assignment review masih perlu ditutup.

**P1-ID-03B: BLOCKED / NOT STARTED.** Jangan membuat atau menjalankan migration conditional format/status raw SQL sebelum desain ditinjau dan disetujui koordinator serta test disposable MySQL 8/SQLite tersedia.

## F. NEXT ACTION

1. Tunggu CI exact HEAD dan benchmark smoke.
2. Jika gagal, perbaiki berdasarkan log dan ulangi pada source SHA baru.
3. Selesaikan test preflight kategori/relasi dan bukti database immutability.
4. Lengkapi mass-assignment/authorization review.
5. STOP untuk review koordinator sebelum P1-ID-03B.
6. Setelah P1-ID-03A selesai, lanjutkan matriks tujuh target Backend/Admin/Guru/Siswa/Kurikulum/Integrasi/Distribusi.

## G. SAFETY

- Tidak ada merge PR.
- Tidak ada deployment.
- Tidak ada perubahan database produksi.
- Tidak ada migration dijalankan.
- Tidak ada klaim ZIP final siap didistribusikan.
