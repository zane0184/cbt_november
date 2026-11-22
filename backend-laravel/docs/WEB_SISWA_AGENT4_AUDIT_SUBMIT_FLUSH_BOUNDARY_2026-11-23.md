# Audit Web Siswa Agent 4 — submit flush-to-marker race — 23 November 2026

## AUDIT
- File diperiksa: `backend-laravel/resources/views/student/exams/show.blade.php`.
- `submitExam()` sebelumnya menjalankan `await flushPendingSaves()`, lalu langsung `await persistSubmitState('SUBMITTING')`, sementara runner tetap interaktif.
- `flushPendingSaves()` memang memeriksa ulang `state.pending.size` setelah mengirim seluruh item yang ditangkap. Edit baru selama flush akan membuat barrier gagal dan mencegah submit.
- Namun setelah flush berhasil dan sebelum transaksi IndexedDB `SUBMITTING` selesai, masih ada titik `await` ketika siswa dapat mengedit jawaban. Queue baru dapat terbentuk setelah barrier final, tetapi sebelum POST submit; kode lama tetap meneruskan POST tanpa flush ulang.
- Jika POST berhasil, draft baru tersebut berpotensi tertinggal secara lokal dan tidak menjadi bagian jawaban terminal yang dikirim.
- Temuan ini adalah race client-side pada batas final save barrier, bukan masalah server revision.

## DESAIN
- Jangan mengunci runner sebelum `flushPendingSaves()` selesai, karena siswa masih perlu memperbaiki jawaban ketika flush gagal.
- Segera setelah flush berhasil, kunci runner secara sinkron sebelum `persistSubmitState('SUBMITTING')` yang asynchronous.
- Jika gagal sebelum POST dimulai (flush atau persist marker), buka kembali runner dan izinkan siswa memperbaiki/mencoba lagi.
- Jika POST sudah dimulai dan outcome tidak pasti, pertahankan lock hingga status server berhasil dipulihkan.
- Jika POST sukses terminal, runner tetap terkunci dan completion ditampilkan.

## IMPLEMENTASI
- Backup pra-patch:
  `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before-submit-flush-boundary-lock-2026-11-23`
- Blob backup/source pra-patch: `defb1f7717b53a9115ea80d67b758986e78d8dbe`; byte-exact verified.
- File diubah: `backend-laravel/resources/views/student/exams/show.blade.php`.
- Patch menambahkan `setRunnerLocked(true)` setelah `flushPendingSaves()` berhasil dan sebelum `persistSubmitState('SUBMITTING')`.
- Jalur error sebelum POST menambahkan `setRunnerLocked(false)` sebelum tombol submit diaktifkan kembali.
- Commit: `f1513fa48388bc86fbdcbbc01eeaa7b680cac941`.
- Blob runner: `2d1f868bdc877188d2bcd4925c1be3dcec153bc9`.
- Tidak ada perubahan API, backend, database, schema, migration, Web Guru/Admin, atau Agent 2.

## PENGUJIAN
- JavaScript inline runner dikompilasi dengan `new Function()`: PASS.
- Simulasi menggunakan fungsi `submitExam()` aktual dengan dependency mock:
  - sukses: runner terkunci sebelum marker write dan sebelum POST; terminal completion — PASS.
  - flush gagal: POST tidak dilakukan; runner tetap/buka; tombol aktif untuk perbaikan — PASS.
  - penyimpanan marker gagal: runner dibuka kembali; POST tidak dilakukan — PASS.
  - POST gagal setelah request dimulai: runner tetap terkunci; outcome unknown dan tombol pemeriksaan status tersedia — PASS.
- Browser E2E, real IndexedDB, API integration, PHP lint/PHPUnit, MySQL concurrency, multi-device/fencing, UAT: BELUM DIUJI.
- Percobaan Chromium headless untuk IndexedDB runtime pada sesi ini timeout saat startup; tidak dihitung sebagai test pass.

## STATUS
- Titik balapan antara final pending barrier dan submit marker sudah ditutup pada source Web Siswa.
- PR #13 tetap Draft/unmerged.
- Tidak ada merge/deployment/migration/database mutation.
- Belum siap operasional/produksi.
