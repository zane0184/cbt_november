# CHECKPOINT AGENT 4 — WEB SISWA FINALIZATION
Tanggal: 23 November 2026
Repo: `zane0184/cbt_november`
Branch: `work/agent4-web-siswa-emergency-20261122`
PR #13: OPEN / DRAFT / UNMERGED
PR head saat checkpoint: `5154ea14b9ab0f9cc7c6c591df2e17f4ae87b580`
PR base: `work/stage4d-account-lifecycle-20261122` @ `7642f8df7d20effe82dc1ba5b086147894332767`

## 1. AUDIT

### Web Siswa scope
- Daftar ujian: penugasan, jadwal, status, eligibility window, attempt limit.
- Instruksi/token dan mulai/lanjutkan Attempt.
- Runner: timer server-authoritative, navigasi, enam tipe soal, media, draft IndexedDB, sync queue, konflik, submit barrier, unknown-submit recovery.
- Hasil: hanya hasil final yang dipublikasikan, tanpa jawaban detail.
- Multi-tenant/role/peserta diuji pada route Web Siswa.

### Backend contract blockers (Agent 2)
Source branch Agent 2 `work/agent2-p0-auth-session-fencing-20261123` telah diperiksa:
1. Start API masih memiliki kondisi terbalik untuk `require_token=false`, sehingga ujian token opsional ditolak.
2. Receipt object dari answer API belum mengembalikan `client_request_id`; client sekarang sengaja menahan ACK dan submit jika ID tidak ada/tidak cocok.
3. GET read-only receipt lookup belum tersedia; exact retry dengan request ID + payload immutable diuji sebagai fallback.
4. Run Agent 2 #89 (`45066851753`) gagal 4 test di `ParticipantAuthorizationAndAttemptLimitTest`; concurrency test SKIPPED.
5. `StudentExamController.php` belum menunjukkan enforcement server-side untuk membedakan Web Siswa dari Android native client. Keputusan produk tetap Android wajib native, Web/PWA untuk iOS/non-Android browser yang disetujui. User-Agent JS bukan security boundary.
6. Media Range/seek pada BinaryFileResponse belum diuji terhadap Laravel sebenarnya.

## 2. DESAIN / KEPUTUSAN

- Agent 4 tetap hanya Web Siswa; tidak mengubah backend/controller/migration/DB, Web Admin/Guru, atau branch Agent 2.
- Fence generation hanya dari response sukses server, disimpan durable pada IndexedDB Attempt context.
- Heartbeat 60 detik. Online reconnect harus menunggu heartbeat berhasil dan generation baru tersimpan sebelum retry answer queue.
- Reload normal memulihkan Attempt yang sama dari IndexedDB, heartbeat bound auth-session, GET status/execution; tidak POST start kedua.
- Media Fetch same-origin memakai credentials + `X-CBT-Fence-Generation` + `X-Device-Binding-ID`, MIME allowlist, 10 MB limit, Blob/Object URL dan cleanup.
- Receipt harus cocok client_request_id, Attempt, snapshot, revision, SHA-256 payload hash, id, committed_at sebelum queue ACK. Receipt tidak cocok tetap berada di queue dan submit tertahan.
- Native Android platform restriction menunggu kontrak server-side Agent 2/koordinator.

## 3. IMPLEMENTASI

### Runner `resources/views/student/exams/show.blade.php`
- Fencing generation + lease expiry durable di IndexedDB.
- Header fencing/device pada Attempt-scoped request.
- Heartbeat dan recovery terminal status.
- Resume Attempt normal sesudah reload.
- Lock latch pada session/fence mismatch.
- Online reconnect: heartbeat selesai dahulu, kemudian retry queue.
- Media image/audio/video/PDF via Fetch/Blob; validasi MIME/ukuran; revoke URL.
- Receipt verifier canonical SHA-256; strict client_request_id binding.
- RTL `dir=auto`, timer cleanup, IndexedDB fail-closed.

### Tests / CI
- `tests/runner-browser-e2e.mjs`: Playwright Chromium.
- `tests/runner-static-audit.mjs`: static checks.
- `tests/Feature/StudentExamPortalTest.php`: 6 test.
- `tests/Feature/StudentResultVisibilityTest.php`: 7 test.
- Workflow Agent 4 kini berjalan pada pull_request + workflow_dispatch; push trigger dihapus agar tidak menggandakan run yang sama.
- Backup sebelum setiap perubahan disimpan pada `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/`.

## 4. PENGUJIAN

### Run #191 — bukti PASS sebelum tambahan reconnect in-place
Run ID `45068778145`, commit `b1f2...`:
- Static audit: PASS.
- Chromium browser E2E: **25 skenario PASS**, termasuk responsive mobile/desktop dengan CSS Blade aktual, 48px touch target, drawer resize, receipt exact retry/bad receipt, fence, heartbeat, media, RTL, offline reload, conflict, expiry, unknown submit.
- MySQL 8 disposable: 7 passed (22 assertions) + 6 passed (23 assertions).
- Total Laravel: 13 passed (45 assertions).

### Run #203 — temuan harness dan perbaikan
Run ID `45068916198`, commit `5b167c...`:
- Static audit: PASS.
- MySQL 8 tests: PASS (13 tests / 45 assertions).
- Browser tests sebelumnya lulus hingga skenario reconnect in-place; satu assertion harness gagal karena mock baru merekam `client_request_id` setelah mensimulasikan abort offline.
- Ini bukan kegagalan aplikasi; test mock diperbaiki agar merekam request body sebelum abort.
- Fix commit: `f4437914f8f9105252fd141b5a296ea0342de28c`.

### Run #209 — verifikasi ulang fix
Run ID `45069223927`, commit `2a51bed8e2a9c434f0b28a90b7a3b6d38d34f9c8`:
- Static audit + Chromium browser E2E: **SUCCESS**.
- **26 skenario browser PASS**, termasuk mobile/desktop responsive + drawer state, reconnect in-place setelah heartbeat generation advance, exact retry, receipt mismatch, fencing, heartbeat terminal, media 4 tipe/MIME/size/auth, RTL, offline reload, conflict, expiry dan unknown submit.
- MySQL 8 disposable: **7 passed (22 assertions) + 6 passed (23 assertions)**.
- Kedua job workflow: SUCCESS.
- Fix harness request ID sebelum offline abort terbukti lulus; reconnect menggunakan request ID sama dan hanya satu answer commit.

## 5. GATE BERIKUTNYA

1. Verifikasi hasil run #209; jika reconnect E2E lulus, update checkpoint final.
2. Tunggu Agent 2 memperbaiki token opsional, receipt client_request_id, empat test yang gagal, dan concurrency.
3. Tetapkan platform enforcement Android native vs Web Siswa server-side.
4. Integrasikan branch backend Agent 2 yang disetujui sebagai base/contract; jangan cherry-pick atau mengubah backend Agent 2 dari branch Agent 4.
5. Browser-to-real-Laravel integration pada MySQL disposable untuk start, heartbeat, status, execution, answer, receipt replay, media, submit, hasil.
6. Uji media Range/seek dan authorization di server Laravel.
7. Ganti Tailwind CDN dengan asset lokal setelah pipeline asset disepakati.
8. UAT madrasah.
9. PR #13 tetap Draft/unmerged.

## 6. STATUS

Web Siswa client: **26 Chromium browser E2E PASS + 9 static audit checks pada run #209**.
Web Siswa route MySQL: **13 tests PASS / 45 assertions pada run #209**.
Backend API/receipt/platform integration: **belum lengkap**.
Production readiness: **NOT READY / NOT CLAIMED**.
