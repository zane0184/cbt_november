# Checkpoint Agent 4 — Web Siswa
Tanggal: 22 November 2026

- Branch: `work/agent4-web-siswa-emergency-20261122`
- Base: `7642f8df7d20effe82dc1ba5b086147894332767`
- Runtime: `backend-laravel/resources/views/student/exams/show.blade.php`
- Runtime blob SHA: `8222aa80a588abfb476c1ff94b153f6ee6ef1ee1`
- Laporan blob SHA: `260bb9fd5f161a9f66396ab8acae81c909be23f6`
- Laporan: `docs/WEB_SISWA_EMERGENCY_AGENT4_REPORT_2026-11-22.md` (blob `a5e0c4ca8d970fb302a16fc77d96db8b977185ef`)
- PR draft: https://github.com/zane0184/cbt_november/pull/13

## Posisi implementasi
Runner memiliki IndexedDB schema v2 dengan object store:
`attempt_context`, `answers`, `sync_queue`, `server_receipts`, `conflicts`, `local_meta`.

- Jawaban lokal + sync queue ditulis atomik.
- Request ID, payload, base revision disimpan untuk retry immutable.
- Conflict 409 diarsipkan; draft lokal tidak ditimpa; conflict ditampilkan pada navigasi dan memblokir submit.
- Queue lama v1 dimigrasikan ke struktur v2.
- Orphaned local state yang tidak sesuai snapshot Attempt menghentikan runner.
- Kegagalan IndexedDB write menandai item undurable dan memblokir submit.
- Submit hanya lanjut jika queue kosong, tidak ada conflict, dan tidak ada draft yang gagal disimpan secara durable.
- Submit response loss menyimpan marker SUBMITTING/UNKNOWN secara lokal, tidak memicu POST ulang otomatis, dan memeriksa GET Attempt sebelum mengizinkan start baru untuk student+exam yang sama. Completion hanya ditampilkan untuk status server SUBMITTED/EXPIRED.
- Jika server masih melaporkan IN_PROGRESS, client meminta execution context dan memverifikasi ID Attempt yang sama, lalu resume Attempt tersebut via GET execution; tidak mengirim POST start baru.
- Completion membedakan status SUBMITTED dari EXPIRED (Attempt ditutup server).
- Receipt store disiapkan tetapi belum diisi karena kontrak receipt backend belum tersedia. UI tidak menyatakan receipt verified.

## Backup
- Baseline: `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/show.blade.php.before`, blob `7283ca553d787f4505299a306e2bd487884622d3`, byte-exact verified.
- Sebelum IndexedDB v2: `.../show.blade.php.before-indexeddb-v2`.
- Sebelum safe resume after submit check: `.../show.blade.php.before-safe-resume-after-submit-check`, blob `1b63a315a1df0e36fc5b07339acd3bc6b88e242d`.
- Sebelum terminal status label: `.../show.blade.php.before-terminal-status-label`, blob `5b8aa0efc2f180d675f401315e43ce578b722925`.
- Verifikasi akhir seluruh backup runner: 11/11 blob SHA cocok.
- Backup tambahan dibuat sebelum conflict race, conflict UI, dan ack/scope refinements. Detail blob/commit tercatat di laporan.

## Pengujian
- Source dan backup dibaca ulang melalui GitHub.
- Pemeriksaan statis struktur IndexedDB dan call sites dilakukan.
- JavaScript syntax parse/compile pada inline runner aktual setelah patch terakhir: PASS.
- 8/8 assertion statis pada runner final: PASS.
- 9/9 assertion statis pada runner sebelumnya: PASS.
- Route GET Attempt cocok dengan apiBase yang sudah memuat ID Exam.
- Commit status exact SHA belum memiliki status/check run; CI tidak berjalan.
- Browser E2E/IndexedDB runtime, PHPUnit, PHP lint, MySQL concurrency, API integration, Device Lease/Fencing, atau UAT belum dilakukan.
- Tidak mengklaim runtime PASS.

## Blocker
Backend exact-one SISWA, KAMAD frozen, F-01 admission/snapshot, F-02 media, Device Lease/Fencing, idempotency same-ID/different-payload, verified receipt, server expiry/submit, serta response-lost recovery belum ditutup.
Web Siswa belum siap operasional.

## Langkah berikutnya (tetap scope Agent 4)
1. Verifikasi source final dan seluruh backup.
2. Siapkan automated browser tests untuk IndexedDB migration, atomic draft/queue, immutable retry, conflict persistence, orphaned-state rejection, submit barrier.
3. Jalankan browser E2E setelah runner dapat dijalankan pada environment test yang tersedia.
4. Sinkronkan kontrak receipt/conflict/submit dengan Agent 2 sebelum mengubah client.
5. Update laporan berdasarkan hasil nyata; jangan merge/deploy sendiri.
