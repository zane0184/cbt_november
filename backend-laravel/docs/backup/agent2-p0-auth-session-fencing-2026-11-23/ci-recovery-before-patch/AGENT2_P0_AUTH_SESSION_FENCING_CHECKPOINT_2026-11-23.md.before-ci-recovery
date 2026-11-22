# Agent 2 — P0 Auth Session, Attempt Binding, Device Lease/Fencing

Tanggal: 23 November 2026  
Branch: `work/agent2-p0-auth-session-fencing-20261123`  
Baseline branch: `work/agent2-p0-attempt-answer-scoring-20261122`  
Status: **IMPLEMENTASI SOURCE TERPASANG — BELUM LULUS RUNTIME GATE**

## 1. AUDIT — temuan sebelum perubahan

Pemeriksaan dilakukan pada source Agent 2 yang aktual, bukan hanya tabel/skema.

1. `AuthWebController` berhasil mengautentikasi user dan meregenerasi session tanpa memeriksa apakah Siswa memiliki Attempt `IN_PROGRESS`.
2. `Api/V1/AuthController` dapat menerbitkan Personal Access Token baru tanpa memeriksa Attempt aktif.
3. `ExamAttemptController` mengikat perangkat melalui `device_fingerprint` / `X-Device-Binding-ID`, tetapi tidak mengikat Attempt pada identitas sesi autentikasi server. Dua Sanctum token berbeda dapat mengirim fingerprint perangkat yang sama.
4. Jalur execution, answer, heartbeat, submit, status, dan media tidak mempunyai satu pemeriksaan auth-session binding yang konsisten.
5. Skema `device_leases` dan `fence_generation` sudah ada, tetapi belum ada model/service runtime yang menegakkan pemilik lease, expiry, fencing generation, rotasi generation, dan pelepasan lease pada terminal Attempt.
6. `client_request_id` pada baris jawaban hanya menyimpan request terakhir. Tidak ada ledger immutable per request ID; replay lama dapat kehilangan jejak setelah revisi berikutnya.
7. Penguncian parent `Exam -> Participant -> Attempt` sudah dipakai di beberapa jalur, tetapi suite lama tidak membuktikan contention dua koneksi MySQL untuk race answer/submit/expiry.

## 2. DESAIN

### Identitas autentikasi
- Stateful Web / Sanctum cookie: identitas berasal dari ID session Laravel server.
- Sanctum bearer token: identitas berasal dari primary key row Personal Access Token di server.
- Database hanya menyimpan HMAC-SHA256 digest, jenis identitas, dan waktu binding; tidak menyimpan session ID atau bearer token mentah.
- Tidak menggunakan fingerprint perangkat sebagai satu-satunya identitas.
- Attempt lama dengan kolom binding null tidak di-auto-adopt. Jalur eksekusi lama fail-closed.

### Login ganda
- Web dan API mengunci row User dan memeriksa Attempt Siswa non-terminal sebelum login diterbitkan.
- API mengembalikan `409 ACTIVE_EXAM_SESSION`; Web mengembalikan validation error.
- Semua Attempt `IN_PROGRESS` memblokir login baru sampai scheduler memfinalisasi expired Attempt. Ini fail-closed bila scheduler terlambat.

### Device Lease dan fencing
- Lease Web disimpan di tabel `device_leases` dengan `auth_session_hash`; `registered_device_id` dibuat nullable untuk lease berbasis sesi Web.
- Lease aktif lima menit, dibatasi oleh `Attempt.expires_at`.
- Request Attempt-scoped wajib mengirim `X-CBT-Fence-Generation`.
- Server memeriksa auth-session hash, generation, pemilik lease, status ACTIVE, dan expiry.
- Lease expired hanya dapat dipulihkan oleh auth session yang sama. Pemulihan menaikkan generation dan merevoke lease ACTIVE stale.
- Submit/expiry melepaskan seluruh lease ACTIVE dalam transaksi. Generation terminal dipertahankan agar retry submit/status dari sesi yang sama tetap idempotent.
- Tidak ada endpoint takeover lintas sesi. Sesi lain selalu ditolak; pemindahan pemegang lease memerlukan prosedur pemulihan berotorisasi terpisah dan belum tersedia pada branch ini.

### Idempotensi jawaban
- Ledger `exam_attempt_answer_requests` unik pada tenant + Attempt + client_request_id.
- Hash payload mencakup snapshot ID, answer_state, dan canonical answer_payload.
- Request ID sama + hash sama mengembalikan receipt asli dengan `idempotent: true`.
- Request ID sama + hash berbeda mengembalikan `409 IDEMPOTENCY_KEY_REUSED`.
- Receipt dan perubahan jawaban berada dalam transaksi yang sama; response hanya keluar setelah transaksi berhasil commit.

## 3. IMPLEMENTASI — file yang berubah

### Runtime
- `app/Http/Controllers/AuthWebController.php` — mutex User dan guard Attempt aktif pada login Web.
- `app/Http/Controllers/Api/V1/AuthController.php` — mutex User, guard Attempt aktif, dan response 409 sebelum token baru diterbitkan.
- `app/Services/AttemptAuthSessionBinding.php` — identitas Web session / Sanctum token dan HMAC digest.
- `app/Services/ActiveExamLoginGuard.php` — pemeriksaan tenant eksplisit untuk Attempt Siswa non-terminal.
- `app/Models/ExamAttempt.php` — field auth-session, cast generation/lease timestamps, relasi lease.
- `app/Models/DeviceLease.php` — model tenant-scoped untuk lease.
- `app/Services/AttemptDeviceLeaseService.php` — create, verify, renew, rotate fence, revoke stale lease, read-current, release.
- `app/Http/Controllers/Api/V1/ExamAttemptController.php` — binding dan fencing pada start/resume, execution, submit, status; response generation/expiry.
- `app/Http/Controllers/Api/V1/ExamAnswerController.php` — binding dan fencing pada read/write; immutable request ledger dan receipt.
- `app/Http/Controllers/Api/V1/MonitoringController.php` — binding/fencing dan renewal pada heartbeat.
- `app/Http/Controllers/Api/V1/MediaController.php` — media Siswa hanya jika snapshot media termasuk Attempt aktif milik sesi dan lease yang benar.
- `app/Console/Commands/ExpireExamAttemptsCommand.php` — pelepasan lease di transaksi expiry.

### Database migrations baru
- `database/migrations/2026_11_23_090001_bind_exam_attempts_to_auth_sessions.php` — field auth-session pada Attempt dan Device Lease; nullable registered device untuk Web lease.
- `database/migrations/2026_11_23_090002_create_exam_attempt_answer_requests_table.php` — immutable idempotency/receipt ledger.

### Test source baru/diubah
- `tests/Feature/AttemptAuthSessionBindingTest.php` — dua Web session berbeda dengan fingerprint perangkat sama.
- `tests/Feature/AttemptTransactionConcurrencyTest.php` — dua koneksi MySQL/InnoDB, contention Exam/Participant/Attempt, submit-vs-answer dan expiry-vs-answer.
- `tests/Feature/DeviceLeaseSchemaIntegrityTest.php` — membuktikan lease Web dapat disimpan tanpa registered_device_id.
- `tests/Feature/ExpireExamAttemptsCommandTest.php` — membuktikan scheduler expiry melepaskan lease ACTIVE dalam transaksi.
- `tests/Feature/StudentWebLoginTest.php` — login Web/API ditolak saat Attempt aktif; login diizinkan setelah Attempt terminal.
- `tests/Feature/ParticipantAuthorizationAndAttemptLimitTest.php` — API test menggunakan bearer token stabil dan fence generation.
- `tests/Feature/ExamAnswerTest.php` — real Sanctum tokens, second-session rejection untuk execution/answer/heartbeat/submit/status/media, lease recovery, stale fence, immutable receipt replay, payload mismatch, rollback receipt, dan submit retry.
- Workflow `.github/workflows/agent2-p0-auth-session-fencing.yml` — gate PHP 8.3 + MySQL 8/InnoDB disposable DB, migration penuh, PHP lint, feature suites, dua-connection concurrency test, dan cleanup DB.

## 4. BACKUP

Semua file existing yang diubah mempunyai backup byte-exact sebelum patch di:

`backend-laravel/docs/backup/agent2-p0-auth-session-fencing-2026-11-23/`

File backup:
- `backend-laravel__app__Http__Controllers__AuthWebController.php.before`
- `backend-laravel__app__Http__Controllers__Api__V1__AuthController.php.before`
- `backend-laravel__app__Http__Controllers__Api__V1__ExamAttemptController.php.before`
- `backend-laravel__app__Http__Controllers__Api__V1__ExamAnswerController.php.before`
- `backend-laravel__app__Http__Controllers__Api__V1__MonitoringController.php.before`
- `backend-laravel__app__Http__Controllers__Api__V1__MediaController.php.before`
- `backend-laravel__app__Models__ExamAttempt.php.before`
- `backend-laravel__tests__Feature__StudentWebLoginTest.php.before`
- `backend-laravel__tests__Feature__ParticipantAuthorizationAndAttemptLimitTest.php.before`
- `backend-laravel__tests__Feature__ExamAnswerTest.php.before`
- `backend-laravel__tests__Feature__AuthTest.php.before`
- `backend-laravel__tests__Feature__MediaAuthorizationTest.php.before`
- `ExpireExamAttemptsCommand.php.before`
- `DeviceLeaseSchemaIntegrityTest.php.before`
- `ExpireExamAttemptsCommandTest.php.before`

File baru (service/model/migration/test/workflow) tidak menimpa source lama. ZIP baseline tidak diubah.

## 5. PENGUJIAN — bukti aktual

### Test source disiapkan
- Web login + API login active Attempt.
- Dua Sanctum token yang dibuat sebelum Attempt, menggunakan device fingerprint sama; token kedua ditolak pada execution, answer, heartbeat, submit, status, dan media.
- Dua Web server session dengan fingerprint perangkat sama; hanya session yang dibind dapat memakai Attempt.
- Request ID sama/payload sama mengembalikan receipt lama; request ID sama/payload berbeda ditolak.
- Retry request lama setelah revisi baru tidak menimpa jawaban terbaru.
- Kegagalan AuditService di tengah transaksi tidak meninggalkan answer atau receipt ledger.
- Lease expiry recovery oleh sesi yang sama menaikkan fence generation; request dengan generation lama ditolak.
- Server expiry memfinalisasi Attempt dan melepaskan lease; skema menerima Web lease tanpa registered_device_id.
- Submit retry idempotent dan lease dilepas.
- Dua koneksi MySQL/InnoDB membuktikan row-lock contention dan status terminal setelah submit/expiry.

### Runtime belum dapat dijalankan
- GitHub Actions workflow sudah terdaftar dan terpicu, tetapi job berakhir sebelum step pertama: `logs_url=null`, `steps=[]`. Run terbaru pada pemeriksaan akhir: `45053144875`, job `137302473159`; `logs_url=null`, `steps=null`. Job selesai failure sebelum step pertama. Penyebab runner tidak dapat dipastikan dari bukti yang tersedia.
- Ini diklasifikasikan **BLOCKED runner/observability**, bukan PHPUnit PASS dan bukan test failure dari source.
- Runtime container saat audit tidak mempunyai Composer, MySQL client/server, `pdo_mysql`, atau `pdo_sqlite`; database pengujian MySQL 8 tidak dapat dijalankan lokal.
- Karena itu: PHP lint, migration MySQL 8, PHPUnit, contention test, multi-device, dan end-to-end API **NOT VERIFIED**.
- Tidak ada migration produksi atau database produksi yang dijalankan.

## 6. DAMPAK KONTRAK API WEB SISWA — Issue #10

Komentar kontrak sudah dicatat pada Issue #10, comment ID `7083116647`.

Perubahan kontrak:
- API login saat Attempt aktif: `409 ACTIVE_EXAM_SESSION`.
- Attempt-scoped request setelah start wajib mengirim `X-CBT-Fence-Generation`.
- Start/resume, execution, heartbeat, answer, submit, dan status mengembalikan generation; start juga mengembalikan lease expiry.
- Fencing mismatch pada JSON Attempt endpoints: `409 ATTEMPT_FENCE_MISMATCH`; media file endpoint menolak akses dengan `403` jika sesi/lease/fence tidak sah.
- Answer response menambah receipt dan `idempotent`; bentuk `success/data` lama tetap ada.
- Existing `X-Device-Binding-ID` tetap dipakai sebagai pemeriksaan tambahan.

Read-only audit terhadap Agent 4 Web Siswa menemukan source saat ini belum mengirim `X-CBT-Fence-Generation` dan belum memverifikasi receipt. Karena itu source Agent 4 saat ini akan ditolak oleh gate fencing sampai Agent 4 mengadaptasi IndexedDB/request helper. Agent 2 tidak mengubah file atau branch Agent 4. Review Agent 4 pada Issue #10 masih ditunggu.

## 7. TEMUAN / BLOCKER YANG BELUM SELESAI

1. Runtime PHP 8.3 + MySQL 8 belum berjalan; semua test tetap NOT VERIFIED.
2. Agent 4 harus menyetujui kontrak Issue #10 dan mengirim/persist generation serta receipt sebelum integrasi Web Siswa bisa dinyatakan kompatibel.
3. Tidak ada authorized cross-session takeover endpoint. Tidak ada takeover diam-diam; pemulihan lintas sesi harus dirancang sebagai operasi berotorisasi/audit terpisah.
4. Legacy Attempt dengan auth-session null tidak di-auto-bind; harus diterminalisasi secara aman sebelum enforcement deployment.
5. Lease berbasis auth session ini membedakan sesi/token server, bukan dua perangkat yang menyalin cookie/token yang sama. Proteksi credential cloning memerlukan device-bound token/DPoP atau mekanisme proof-of-possession terpisah.
6. Multi-device dan recovery end-to-end belum terbukti sampai test MySQL 8 dan browser integration Agent 4 lulus.

## 8. NEXT

1. Pulihkan runner/observability dan jalankan workflow pada exact HEAD.
2. Perbaiki semua PHP lint, migration, PHPUnit, dan MySQL lock test sampai hasil tercatat.
3. Review migrasi nullable registered_device_id dan FK komposit pada MySQL 8 disposable DB.
4. Tunggu respons Agent 4 pada Issue #10; jangan merge kontrak sampai ada persetujuan dan bukti IndexedDB/retry/receipt.
5. Setelah test lulus, lakukan API E2E dua token/sesi dan dua browser terpisah; baru evaluasi status P0.
6. Tidak ada production deploy/migration sebelum approval, backup, preflight, dan integrasi Agent 4.

