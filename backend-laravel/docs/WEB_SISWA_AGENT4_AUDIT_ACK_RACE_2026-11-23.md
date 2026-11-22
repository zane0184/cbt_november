# Audit dan perbaikan Web Siswa Agent 4 — acknowledgement race — 23 November 2026

## AUDIT
- Scope pemeriksaan: runner Web Siswa `backend-laravel/resources/views/student/exams/show.blade.php`.
- Jalur `saveAnswer()` menunggu respons PUT jawaban, lalu menjalankan `acknowledgePersistedPending()` yang menulis status acknowledgement dan menghapus record `sync_queue` dalam transaksi IndexedDB.
- Sebelum patch, identitas `state.pending.get(question.id) === pendingItem` hanya diperiksa sebelum `await acknowledgePersistedPending(...)`. Setelah transaksi selesai, item dihapus tanpa memeriksa ulang identitas.
- Jika siswa mengedit jawaban ketika transaksi acknowledgement lama sedang berjalan, `state.pending` dapat sudah menunjuk ke item baru. Penghapusan tanpa pemeriksaan ulang menghapus item terbaru dari memori. Record baru dapat tetap berada di IndexedDB, tetapi tidak lagi direpresentasikan di `state.pending`; submit barrier dan flush aktif berisiko tidak melihatnya.
- Simulasi urutan async mereproduksi perilaku lama: item `new` hilang setelah acknowledgement item `old` selesai.
- Audit IndexedDB v1→v2 pada source menunjukkan upgrade membuka versi 2, membuat stores v2, menyalin record legacy `pending-answers` ke `answers` dan `sync_queue`, serta menulis schema_version. Namun migrasi belum diuji di browser dengan database v1 nyata; kompatibilitas seluruh variasi record legacy tetap belum terverifikasi.
- Pemulihan pending queue dilakukan setelah GET execution dan GET jawaban server, kemudian draft lokal pending dipasang kembali dan `flushPendingSaves()` dijalankan. Konflik tetap terpisah dan memblokir submit.

## DESAIN
- Pertahankan acknowledgement server dan transaksi IndexedDB yang sudah ada.
- Setelah `await acknowledgePersistedPending(...)` selesai, periksa kembali apakah `state.pending` masih menunjuk ke objek `pendingItem` yang sama.
- Hapus item dari memori hanya jika identitasnya masih sama. Jika siswa telah membuat edit baru, item baru tetap berada di antrean memori dan antrean durable.
- Tidak mengubah kontrak API, revision semantics, schema IndexedDB, backend, route, Attempt, Web Guru, atau Web Admin.

## IMPLEMENTASI
- Backup dibuat sebelum patch:
  - `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before-ack-race-guard-2026-11-23`
  - Blob backup/source pra-patch: `fbdeafaf1ec9f5469d06675c16e7d68b632a8ede`
  - Verifikasi isi backup identik byte-per-byte: PASS.
- File diubah: `backend-laravel/resources/views/student/exams/show.blade.php`.
- Patch menambahkan pemeriksaan identitas ulang setelah transaksi acknowledgement selesai, sebelum `state.pending.delete(question.id)`.
- Commit: `d173ab7920d7f60226d70d4ad2561cfb57c1fcd2`.
- Blob runner baru: `c6ad92640e9358e33a9b4980c022e374dedf9c10`.

## PENGUJIAN
- JavaScript inline runner diekstrak dari Blade dan dikompilasi menggunakan `new Function()`: PASS.
- Simulasi race pada perilaku lama: item baru hilang — reproduksi PASS.
- Simulasi race setelah patch: item baru tetap berada di `state.pending` — PASS.
- Simulasi acknowledgement normal: item yang memang telah di-ack tetap dihapus — PASS.
- Static assertion: pemeriksaan identitas dilakukan kembali setelah `await` — PASS.
- Static assertion: IndexedDB v2, migrasi legacy queue, restore pending, submit barrier, dan conflict barrier tetap ada — PASS.
- Belum dijalankan: browser E2E, IndexedDB browser runtime/migrasi database v1 nyata, PHP lint, PHPUnit, API integration, MySQL concurrency, multi-device/fencing, dan UAT.

## STATUS DAN BATAS
- Temuan race pada in-memory pending queue telah diperbaiki pada branch Agent 4.
- Migrasi IndexedDB v1→v2 dan pemulihan lintas browser-close masih memerlukan uji browser runtime sebelum dinyatakan lulus.
- Agent 2 dual-login/auth-session binding dan endpoint enforcement tetap dependency P0 yang belum terbukti.
- PR #13 tetap Draft/unmerged. Tidak ada merge, deployment, migration, atau perubahan database.
- Scope tetap Web Siswa saja.
