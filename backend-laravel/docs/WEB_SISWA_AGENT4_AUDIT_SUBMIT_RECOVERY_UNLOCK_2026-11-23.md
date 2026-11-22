# Audit Web Siswa Agent 4 — pemulihan submit mengaktifkan kembali kontrol kirim — 23 November 2026

## AUDIT
- Source aktif diperiksa: `backend-laravel/resources/views/student/exams/show.blade.php`.
- Pada `recoverSubmitStatus()`, jalur unknown submit mengunci tombol `submit-button` sebelum GET status Attempt.
- Jika server mengembalikan `IN_PROGRESS`, runner mengambil `/execution`, memverifikasi ID Attempt yang sama, menjalankan `loadExecution()`, menyimpan marker `RESUMED`, lalu menghapus `submitOutcomeUnknown`.
- Temuan: jalur tersebut sebelumnya tidak mengaktifkan kembali `submit-button`. Siswa dapat kembali ke runner dengan jawaban pulih tetapi tombol Kirim Ujian tetap disabled. Ini terjadi baik pada reload dengan marker SUBMITTING/UNKNOWN maupun pemulihan di sesi yang sama setelah respons POST submit tidak pasti.
- Jalur terminal SUBMITTED/EXPIRED harus tetap menampilkan completion dan tidak membuka kembali submit.
- Jika GET execution/pemulihan gagal, kontrol submit harus tetap terkunci dan status tetap unknown (fail-closed).
- Source runner memiliki IndexedDB v2, draft/conflict restore, pending save queue, undurable gate dan submit barrier. Patch ini tidak mengubah kontrak tersebut.
- `composer.json` mendeklarasikan PHPUnit; `phpunit.xml` memakai SQLite in-memory. Tidak ada bukti runtime PHPUnit atau MySQL pada tahap ini.

## DESAIN
- Hanya setelah server mengonfirmasi status `IN_PROGRESS`, GET execution mengembalikan Attempt ID yang sama, dan `loadExecution()` selesai, aktifkan kembali tombol submit.
- Pertahankan marker status recovery dan sembunyikan tombol pemeriksaan status setelah resume berhasil.
- Jika recovery tidak berhasil, jangan mengaktifkan tombol submit.
- `flushPendingSaves()`, konflik, undurable state, dan submit barrier tetap menjadi gerbang sebelum POST submit berikutnya.

## IMPLEMENTASI
- File: `backend-laravel/resources/views/student/exams/show.blade.php`.
- Backup pra-patch:
  `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before-submit-resume-button-unlock-2026-11-23`
- Blob backup: `9ef6558a84aec1dc8469dc37337fb3ebca38e988`.
- Backup diverifikasi byte-exact terhadap source sebelum patch saat dibuat. Backup sekarang berbeda dari source aktif karena memang menyimpan versi pra-patch.
- Patch menambahkan `el('submit-button').disabled = false` hanya pada jalur resume sukses setelah status server IN_PROGRESS dan execution Attempt ID cocok.
- Commit patch: `b418dfeffd1b85e053c59fd6ac7eea6af5273cc8`.
- Runner blob setelah patch: `db04dea4d9d804b0ab2df8f3bc5619f029b697a2`.

## PENGUJIAN
- JavaScript inline parse/compile: PASS.
- Simulasi langsung fungsi `recoverSubmitStatus()` dengan mock API/DOM:
  - Status IN_PROGRESS + execution Attempt ID sama + loadExecution sukses: tombol submit aktif kembali, marker unknown dibersihkan, tombol cek status disembunyikan — PASS.
  - Load execution gagal: tombol submit tetap terkunci, marker unknown dipertahankan, tombol cek status kembali tersedia — PASS.
  - Status SUBMITTED: completion ditampilkan dan tombol submit tidak dibuka kembali — PASS.
- Duplicate HTML IDs: tidak ditemukan.
- CSS ID selector terhadap elemen DOM: semua cocok (warna hex dikecualikan).
- Selector mobile nav tetap cocok.
- Belum dijalankan: browser E2E nyata, IndexedDB browser runtime/reload, perangkat fisik, keyboard virtual, API integration, PHP lint/PHPUnit, MySQL concurrency, multi-device/fencing, UAT.

## BATAS / GATE
- Hanya runner Web Siswa dan dokumentasi Agent 4.
- Tidak mengubah backend Agent 2, Web Guru, Web Admin, API, database, atau migration.
- Dual-login/auth-session binding dan endpoint enforcement Agent 2 tetap P0 blocker.
- PR #13 tetap Draft/unmerged. Tidak ada merge, deployment, atau perubahan database produksi.
