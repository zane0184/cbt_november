# CHECKPOINT AGENT 4 — WEB SISWA FENCE, MEDIA, RECEIPT INTEGRATION
Tanggal: 23 November 2026
Repo: `zane0184/cbt_november`
Branch: `work/agent4-web-siswa-emergency-20261122`
PR: #13 — OPEN / DRAFT / UNMERGED
PR head: `86b93044d85a486df246f4dabf5ceaa2fcbbf3b7`
PR base: `work/stage4d-account-lifecycle-20261122` @ `7642f8df7d20effe82dc1ba5b086147894332767`

## 1. AUDIT

### 1.1 Web Siswa surface
Source Agent 4 yang diaudit:
- `resources/views/student/exams/index.blade.php`: daftar ujian yang ditugaskan, jadwal, status, kelayakan peserta, jumlah percobaan, lanjutkan/mulai.
- `resources/views/student/exams/show.blade.php`: instruksi, token, Attempt, timer, navigasi, enam tipe soal, media, draft lokal, sinkronisasi, konflik, submit dan recovery.
- `resources/views/student/results/show.blade.php`: hasil final yang dipublikasikan, tanpa rincian jawaban soal.
- `StudentExamController.php`: validasi role, tenant, peserta, eligibility window, Attempt aktif, dan hasil.

### 1.2 Kontrak Agent 2 yang diperiksa
Branch: `work/agent2-p0-auth-session-fencing-20261123`.
Blob yang diperiksa:
- `ExamAttemptController.php`: `145913e6d014eed258d66ef971e5e548de81168d`
- `ExamAnswerController.php`: `b2b5a24c4544ce571c4c95b22d01f032fcedbb2a`
- `MediaController.php`: `37e5a6283c3832a8295d32dc32f35c1a4c3b6645`
- `routes/api.php`: `4a9faa1e2bb0485570f30c199ccfbea7aa3a26ff`
- `AttemptDeviceLeaseService.php`: `dd3961d5d8385e4640d1c799ed96e1d94dcbb96c`
- `MonitoringController.php`: `bb69ed1ca4d981924708a4c1cd2b6d0c61c14d50`

Fakta source:
1. Start Attempt mengembalikan `fence_generation` dan `lease_expires_at`.
2. Execution, answer read/write, status, submit, media dan heartbeat memeriksa bound auth-session / lease / fence.
3. Heartbeat route: `POST /api/v1/exams/{exam}/attempts/{attempt}/heartbeat`; response sukses mengembalikan generation dan lease expiry.
4. Lease expiry lima menit; recovery expired lease harus berasal dari auth-session yang sama.
5. Answer API menyimpan immutable ledger dengan `client_request_id` dan payload hash. Exact replay dengan ID + payload sama mengembalikan response ledger, sedangkan ID sama dengan payload berbeda ditolak.
6. Receipt response saat audit belum menyertakan `client_request_id` pada objek receipt, walaupun ledger menyimpannya.
7. GET read-only receipt lookup belum ada di route API.
8. Media controller memakai `response()->file()`, dengan otorisasi tenant, Attempt aktif, snapshot dan auth-session. Runtime Range behavior belum diuji.
9. **Token opsional blocker:** kondisi `if (!$lockedExam->require_token || !$lockedExam->token)` mengembalikan `EXAM_TOKEN_NOT_CONFIGURED` saat `require_token=false`; ini membalik semantik token opsional dan menghalangi ujian tanpa token.
10. Agent 2 Actions run `45066851753`, source `b00cf4312b12e1158c609ca91af3dcecec7a2553`: 78 passed, 4 failed (293 assertions). Keempat failure berada di `ParticipantAuthorizationAndAttemptLimitTest`; two-connection concurrency test SKIPPED karena suite belum bersih.

## 2. DESAIN

- Scope Agent 4 tetap Web Siswa saja. Tidak mengubah API/controller, migration, DB, Web Admin/Guru, atau branch Agent 2.
- Hanya generation dari response server sukses yang boleh dipakai. Generation disimpan durable di IndexedDB Attempt context sebelum request Attempt-scoped berikutnya.
- Semua request Attempt-scoped membawa `X-CBT-Fence-Generation` dan `X-Device-Binding-ID`.
- Heartbeat tiap 60 detik; 409 fence/session mismatch mengunci runner, menjaga draft dan menahan submit.
- Reload normal memulihkan Attempt ID + generation dari IndexedDB, heartbeat pada bound auth-session, GET status, lalu GET execution. Tidak membuat Attempt kedua.
- Media menggunakan authenticated Fetch + Blob/Object URL; allowlist MIME `image/jpeg`, `image/png`, `audio/mpeg`, `video/mp4`, `application/pdf`; batas 10 MB; revoke Object URL pada penggantian soal/terminal state.
- Receipt diverifikasi sebelum antrean lokal dihapus: client request ID, Attempt, snapshot, revision, payload hash SHA-256 dan committed_at. Exact retry menggunakan request ID + payload immutable untuk response-loss. Jika receipt hilang/tidak cocok, antrean dipertahankan dan submit diblokir.
- Canonical hash client mengikuti implementasi PHP Agent 2: object keys diurutkan, array order dipertahankan, JSON UTF-8, unescaped slash/Unicode, SHA-256. U+2028/U+2029 diselaraskan dengan default PHP JSON encoding.
- Tidak mengklaim physical-device identity dari auth-session/device binding.

## 3. IMPLEMENTASI

### 3.1 Runner
File: `backend-laravel/resources/views/student/exams/show.blade.php`
- Fence generation + lease expiry tersimpan di `attempt_context`.
- API helper menambahkan header fencing pada request Attempt-scoped.
- Heartbeat interval 60 detik; terminal heartbeat direkonsiliasi melalui GET status.
- Resume normal sesudah reload menggunakan Attempt context tersimpan, bukan POST start baru.
- Lock latch mempertahankan runner inert setelah fence/session mismatch meskipun jalur error lain mencoba unlock.
- Media image/audio/video/PDF dimuat melalui Fetch dengan credentials + fence/device headers, MIME/size validation, Blob Object URL dan cleanup.
- Receipt jawaban diverifikasi terhadap client_request_id, Attempt, snapshot, revision dan payload hash sebelum ACK local queue.
- Receipt tidak valid/kurang field: pending draft tidak dihapus, submit tertahan.
- Dukungan `dir=auto` untuk Arab berharakat dan campuran Arab–Indonesia tetap ada.
- Timer interval dibersihkan pada submit/terminal state; IndexedDB unavailable gagal tertutup.

### 3.2 Tests / CI
- `tests/runner-browser-e2e.mjs`: browser tests untuk normal save/submit, receipt verification, response-loss exact retry, bad receipt, no IndexedDB, no fence, token, enam tipe soal, RTL, heartbeat, terminal heartbeat, media MIME/size/auth, offline reload, IndexedDB v1 migration, fence mismatch, conflict, expiry dan unknown submit.
- `tests/runner-static-audit.mjs`: static assertions untuk IndexedDB, submit barrier, fence, heartbeat, media lifecycle dan receipt hash verification.
- `tests/Feature/StudentExamPortalTest.php`: 6 Laravel feature tests untuk daftar ujian, akses instruksi/token, Attempt aktif, unassigned/cross-tenant, eligibility window, role.
- `StudentResultVisibilityTest.php`: 7 tests untuk hasil siswa.
- Workflow Agent 4 menggabungkan Chromium E2E + MySQL 8 disposable pada satu workflow.
- Backup byte-exact untuk setiap perubahan disimpan di `backend-laravel/docs/backup/agent4-web-siswa-emergency-2026-11-22/`.

### 3.3 Koordinasi Agent 2
Komentar Issue #10:
- `7085359080`: review fence/heartbeat/media + receipt design.
- `7085431533`: audit run Agent 2 #89, empat test gagal.
- `7085439483`: blocker token opsional.
- `7085512792`: kebutuhan client_request_id pada receipt dan exact retry immutable.

## 4. PENGUJIAN

### GitHub Actions run #170
Run ID: `45068198022`
Commit: `86b93044d85a486df246f4dabf5ceaa2fcbbf3b7`
- `runner-static-audit` job `137349272317`: SUCCESS.
- `student-results-feature` job `137349272558`: SUCCESS.
- Static audit: PASS.
- Chromium browser E2E: **24 skenario PASS**, termasuk exact retry setelah response loss tanpa commit kedua, bad receipt tidak menghapus queue, fence generation, heartbeat, media 4 jenis, MIME mismatch, media >10 MB, 403 media, enam tipe soal, RTL, offline reload, conflict, expiry, unknown submit.
- MySQL 8.0.46 disposable:
  - `StudentResultVisibilityTest.php`: **7 passed (22 assertions)**.
  - `StudentExamPortalTest.php`: **6 passed (23 assertions)**.
- Total Laravel: **13 passed (45 assertions)**.
- Kedua job run #170: SUCCESS.

### Batas bukti
- Chromium E2E menggunakan API mock yang memodelkan kontrak yang disepakati. Ini belum browser-to-real-Laravel integration.
- MySQL tests menjalankan route Web Siswa terpilih; belum mencakup semua endpoint API runner dalam satu sesi browser.
- Receipt backend aktual belum cocok dengan verifier client sampai Agent 2 menambahkan `client_request_id` pada receipt.
- Token opsional aktual belum dapat dipakai sampai Agent 2 memperbaiki kondisi start.
- Multi-device / two-connection concurrency Agent 2 belum PASS.
- Media response Range/seek belum diuji terhadap BinaryFileResponse Laravel sebenarnya.
- Tailwind masih dimuat dari CDN eksternal.

## 5. GATE / LANGKAH BERIKUTNYA

1. Agent 2 memperbaiki token opsional dan menambahkan `client_request_id` pada receipt response/replay.
2. Agent 2 menutup empat kegagalan ParticipantAuthorizationAndAttemptLimitTest dan menjalankan two-connection concurrency sampai PASS.
3. Setelah kontrak Agent 2 tersedia, integrasikan backend branch yang disetujui ke base integrasi PR #13; jangan menyalin/mengubah source backend di branch Agent 4.
4. Tambahkan browser-to-real-Laravel integration pada disposable MySQL 8 untuk start, heartbeat, status, execution, answer, receipt replay, media, submit, hasil.
5. Uji media Range/seek, auth 401/403/409, dan cleanup pada server Laravel sebenarnya.
6. Ganti Tailwind CDN dengan asset lokal setelah pipeline asset proyek disepakati.
7. Jalankan UAT Admin/Guru/Siswa di madrasah.
8. PR #13 tetap Draft dan unmerged sampai semua gate lulus.

## 6. STATUS

Web Siswa client behavior: **static audit + 24 Chromium E2E PASS**.
Student result + portal route tests: **13 Laravel/MySQL tests PASS (45 assertions)**.
Receipt verifier: **client implementation + mock E2E PASS; backend receipt contract belum lengkap**.
Fence/heartbeat/media: **client implementation + mock E2E PASS; real API/browser integration belum diverifikasi**.
Production readiness: **NOT READY / NOT CLAIMED**.
