# Checkpoint Web Siswa Agent 4 — acknowledgement race — 23 November 2026

## Posisi
- Branch: `work/agent4-web-siswa-emergency-20261122`.
- PR #13: Draft, open, belum merge.
- Base branch: `work/stage4d-account-lifecycle-20261122`.
- Commit patch terbaru: `d173ab7920d7f60226d70d4ad2561cfb57c1fcd2`.
- Runner blob: `c6ad92640e9358e33a9b4980c022e374dedf9c10`.

## Temuan dan perbaikan
- Race condition ditemukan pada `saveAnswer()`: setelah acknowledgement IndexedDB selesai, kode lama menghapus `state.pending` tanpa memastikan item yang sedang dihapus masih item yang sama.
- Perbaikan memeriksa ulang identitas objek setelah `await acknowledgePersistedPending()`. Edit baru siswa tidak dapat terhapus dari antrean in-memory oleh acknowledgement request lama.
- Tidak ada perubahan schema, API, backend, route, Attempt, Web Guru, atau Web Admin.

## Backup
- Path: `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before-ack-race-guard-2026-11-23`
- Blob: `fbdeafaf1ec9f5469d06675c16e7d68b632a8ede`
- Byte-exact verified: PASS.

## Pengujian
- JS inline parse/compile: PASS.
- Reproduksi perilaku lama kehilangan item baru: PASS.
- Simulasi patch mempertahankan item baru: PASS.
- Acknowledgement normal tetap membersihkan item lama: PASS.
- Static checks IndexedDB v2, legacy migration, pending restore, submit/conflict barrier: PASS.
- Belum diuji browser E2E, IndexedDB runtime lintas browser close/reopen, PHP lint/PHPUnit, API integration, MySQL concurrency, multi-device/fencing, UAT.

## Pekerjaan berikutnya
1. Verifikasi migrasi database IndexedDB v1 nyata ke v2 di browser tanpa kehilangan queue.
2. Uji browser close/reopen: draft pending, conflict, SUBMITTING/UNKNOWN, serta request PUT yang responsnya hilang.
3. Uji request out-of-order dan edit baru selama acknowledgement IndexedDB.
4. Setelah harness/runtime browser terbukti tersedia, jalankan browser E2E; jangan mengklaim static mock sebagai E2E.
5. Tetap tunggu penyelesaian Agent 2 untuk P0 dual-login/session binding dan endpoint enforcement.

## Larangan dan status
- Hanya Web Siswa.
- Jangan ubah Web Guru, Web Admin, backend Agent 2, database, migration, atau API.
- Tidak merge/deploy. PR #13 tetap Draft.
