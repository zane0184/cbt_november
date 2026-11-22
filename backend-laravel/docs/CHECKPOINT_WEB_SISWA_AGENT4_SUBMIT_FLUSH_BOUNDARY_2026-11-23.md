# Checkpoint Web Siswa Agent 4 — submit flush-to-marker race — 23 November 2026

## Posisi
- Branch: `work/agent4-web-siswa-emergency-20261122`.
- PR #13: Draft, open, unmerged.
- Runner blob terbaru pada saat checkpoint: `2d1f868bdc877188d2bcd4925c1be3dcec153bc9`.
- Commit patch terbaru: `f1513fa48388bc86fbdcbbc01eeaa7b680cac941`.

## Temuan/perbaikan
- `flushPendingSaves()` memeriksa pending size setelah flush, tetapi runner sebelumnya tetap interaktif selama `persistSubmitState('SUBMITTING')`.
- Edit baru dapat dibuat setelah barrier selesai tetapi sebelum POST.
- Runner sekarang dikunci tepat setelah flush berhasil dan sebelum asynchronous marker write.
- Jika flush/marker gagal sebelum POST, runner dibuka kembali dan submit button diaktifkan.
- Jika POST sudah dimulai dan hasilnya unknown, runner tetap terkunci sampai recovery; jika terminal sukses, runner tetap terkunci.
- Perubahan hanya pada runner Web Siswa.

## Backup
- Path: `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before-submit-flush-boundary-lock-2026-11-23`.
- Blob: `defb1f7717b53a9115ea80d67b758986e78d8dbe`.
- Byte-exact verified sebelum patch.

## Pengujian
- JavaScript parse/compile: PASS.
- Fungsi aktual `submitExam()` diuji dengan mocked dependencies:
  - successful submit: lock before marker and POST — PASS.
  - flush failure: no POST, user may retry — PASS.
  - marker persistence failure: unlock, no POST — PASS.
  - POST unknown: remains locked, status-check available — PASS.
- Browser E2E/IndexedDB runtime/API/PHPUnit/MySQL concurrency/multi-device/UAT: belum diuji.
- Chromium headless IndexedDB test timeout saat startup; bukan PASS.

## Pekerjaan berikutnya
1. Audit pending/conflict lama dari Attempt berbeda pada exam/student yang sama; pastikan tidak salah dipasang ke Attempt baru dan tidak diam-diam tertinggal.
2. Audit terminal status recovery untuk local work yang masih unresolved, termasuk kasus marker dari versi runner sebelumnya.
3. Pastikan semua data lokal yang tidak dapat dipetakan tetap dipertahankan dan ditampilkan sebagai kondisi yang memerlukan bantuan, bukan dihapus.
4. Lanjutkan pemeriksaan backend Agent 2 hanya read-only untuk idempotency/session binding; jangan mengubah branch Agent 2.
5. Uji IndexedDB/browser runtime melalui harness yang tersedia; jangan menambahkan dependency tanpa bukti kebutuhan dan persetujuan.

## Batas
- Scope Web Siswa saja.
- Tidak mengubah Web Guru/Admin, backend Agent 2, API, DB, migration.
- Tidak merge/deploy; PR #13 tetap Draft.
