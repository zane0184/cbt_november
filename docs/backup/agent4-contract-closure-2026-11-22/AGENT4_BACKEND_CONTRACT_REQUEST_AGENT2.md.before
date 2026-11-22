# Agent 4 — Permintaan Kontrak Backend kepada Agent 2

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch dokumentasi: `work/agent4-web-siswa-audit-20261122`  
HEAD branch dokumentasi sebelum dokumen ini: `c4a02a93bb143da7c83df1894f92b2ffa75c8bc6`  
Owner kontrak/backend: Agent 2. Owner client Web Siswa: Agent 4.  
Status: **REQUEST FOR DESIGN / APPROVAL — NOT AN API SPECIFICATION**.

## 1. Konteks instalasi dan tenant

Keputusan distribusi: satu instalasi Web CBT untuk satu madrasah dan satu database akademik sendiri. Storage, file, credential, konfigurasi dan backup terisolasi per instalasi. Tidak ada shared academic database atau sinkronisasi akademik antar madrasah. Agent 2 diminta mengonfirmasi bahwa auth/tenant resolver, API, media path dan seluruh query tetap benar pada topologi ini. Jangan menambahkan endpoint atau parameter pemilih tenant dari browser.

Scope sampai Juni 2027 hanya Web Admin Madrasah, Web Guru, Web Siswa. Android native dan Super Admin ditunda.

## 2. AUDIT — source yang dibaca

Branch Agent 2: `work/stage4d-account-lifecycle-20261122`, remote HEAD `7d63ea3b70b5191530c2c92c5c86bf92d7324db6`.

| Source | Blob SHA | Observasi |
|---|---|---|
| `backend-laravel/app/Http/Controllers/Api/V1/ExamController.php` | `c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9` | `show()` me-load snapshots bila `canStartAttempt()` |
| `backend-laravel/app/Http/Controllers/Api/V1/MediaController.php` | `f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62` | Tenant/owner dan active-attempt media reference; lease/fence/question_order belum terbukti lengkap |
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` | `13e8981cca4653ff4e9b9448a50e4d0ec8a74bae` | `X-Device-Binding-ID`; start/resume/submit; lock Exam → Participant → Attempt |
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php` | `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c` | Request memuat `client_request_id`, `base_revision`, `answer_payload`, `answer_state`; lock Attempt → Answer |
| `backend-laravel/routes/api.php` | `2e43c37dd669a26446a44b818c1d2c8420b15aca` | Route exams/attempts/answers/media pada authenticated/tenant/license group |
| `backend-laravel/app/Policies/ExamPolicy.php` | `c05d96ef932fdc81b67336240af4e1726864c3d6` | View siswa memeriksa tenant/membership; startAttempt mengizinkan resume sah setelah close; belum exact one SISWA role contract |
| `backend-laravel/app/Services/ExamSnapshotService.php` | `d0bfcd70b2777a3884c7f92b7c256f05492645ec` | Snapshot membuat copy stem/explanation/options/media/metadata |
| `backend-laravel/app/Models/QuestionVersion.php` | `5f937abf691f96ee62696f7ef01b5adb2d6f0d9a` | Version identity major/minor, type, stem, explanation, points, options/media relation |
| `backend-laravel/app/Models/ExamQuestionSnapshot.php` | `20a9ef80ed69c5e6281d0fbe592c900178ede2d2` | Snapshot persistence model |

Branch Agent 2 HEAD tersebut adalah snapshot saat refresh; blob SHA di atas adalah bukti file yang dibaca. Tidak ada source diubah.

## 3. Permintaan A — admission F-01

Mohon Agent 2 menetapkan satu kontrak admission server yang dipakai untuk detail ujian, start/resume Attempt dan pengambilan snapshot. Kontrak wajib memverifikasi:
1. User authenticated dan aktif.
2. Tepat satu role portal SISWA; multi-role/role kosong ditolak.
3. Tenant instalasi dan semua resource sama.
4. ExamParticipant sah dan eligible.
5. Exam window memakai waktu server dan `Exam.start_at/end_at`.
6. Token Exam valid untuk admission baru.
7. Attempt milik user yang meminta.
8. Status Attempt masih memperbolehkan operasi.
9. Attempt belum expired/terminal.
10. Registered device dan Device Lease valid.
11. Lease tidak expired/revoked.
12. Fencing generation current.
13. Snapshot dan question order valid.

Sebelum admission, hanya metadata aman boleh dikirim. Tidak boleh ada snapshot, answer key, accepted answers, rubric, scoring guide, explanation privat, data internal atau peserta lain.

Mohon tetapkan:
- Jalur/operasi backend yang menjadi admission gate (tanpa Agent 4 mengusulkan endpoint baru).
- Apakah token diwajibkan pada resume aktif, dan bagaimana membedakan token admission baru vs resume.
- Exact-role rule.
- Status Attempt yang boleh resume.
- Semantik token yang dirotasi/lama.
- Response aman untuk deny dan data yang tidak pernah diserialisasi.
- Audit event, actor, reason, correlation ID dan redaction.
- Test names/evidence pada exact source SHA.

## 4. Permintaan B — F-02 media authorization

Mohon Agent 2 menetapkan bahwa setiap media request mengikat sekaligus:
- installation/tenant;
- media ownership;
- QuestionVersion/source identity;
- ExamSnapshot identity;
- Attempt milik user;
- snapshot termasuk `question_order`;
- Attempt status;
- registered device;
- lease token/reference, expiry dan revoke;
- fencing generation current.

Mohon tetapkan perilaku request paralel yang dimulai sebelum takeover tetapi diproses sesudah generation berubah. Media tenant lain, QuestionVersion lain, snapshot palsu/di luar order dan Attempt user lain harus fail closed. Jelaskan cache header/private response semantics tanpa membuka file antar tenant.

Agent 4 tidak menetapkan route, status HTTP, header, query parameter, DTO atau error code.

## 5. Permintaan C — Device Lease/Fencing

Mohon tetapkan kontrak penuh:
- acquire;
- heartbeat/renew;
- release;
- revoke;
- expiry;
- takeover;
- registered device lifecycle;
- lease identity dan credential;
- fencing generation monotonicity;
- stale request rejection;
- race dua device;
- race lease dengan answer, media dan submit;
- idempotency operasi lease;
- audit event untuk takeover/revoke/force action.

Tentukan apa yang disimpan browser, apakah credential boleh dipersist, rotasi, expiry, revocation, recovery setelah reload dan tindakan saat lease hilang. Jangan menyimpan secret di IndexedDB sebelum desain keamanan disetujui.

## 6. Permintaan D — Answer revision, idempotency, receipt, conflict

Source saat ini terlihat memakai `client_request_id`, `base_revision`, `answer_payload`, `answer_state`; ini observasi, bukan persetujuan kontrak final.

Mohon Agent 2 menetapkan secara eksplisit:

| Topik | Keputusan yang diminta |
|---|---|
| Request shape | Field wajib/opsional, tipe, batas ukuran, validasi per enam tipe soal |
| Idempotency scope | Unique scope request ID: per tenant/user/attempt/device/snapshot atau scope lain |
| Same ID + same payload | Hasil retry setelah server commit |
| Same ID + different payload | Penolakan tanpa mengubah hasil pertama |
| `base_revision` | Semantik revision awal, stale, future dan null |
| Current revision | Sumber kebenaran dan perilaku ketika answer belum ada |
| Receipt | Bukti server accepted, isi minimal, authenticity/integrity verification |
| Duplicate response | Cara client mengenali hasil identik dan tidak menggandakan efek |
| Stale revision | Conflict representation; kedua versi tetap dapat dipulihkan |
| Conflict resolution | Actor, pilihan, audit, revision baru, dan kapan submit dapat dibuka |
| After submit | Penolakan request answer setelah terminal |
| After expiry | Perilaku answer request saat expired |
| Old device | Penolakan request dari generation lama setelah takeover |
| Retention | Retensi request ID, receipt dan conflict history |
| Audit | Event, actor, before/after, revision, device reference, reason |

**SERVER_ACCEPTED** hanya boleh ditetapkan client setelah receipt yang sesuai berhasil diverifikasi. HTTP success tanpa receipt yang dapat diverifikasi belum cukup untuk menghapus pending local record.

HTTP 409 tidak boleh membuat client menghapus jawaban lokal atau menimpa diam-diam dengan versi server. Agent 2 diminta menjelaskan response existing; jangan mengubah response secara sepihak.

## 7. Permintaan E — Submit, Force Submit dan expiry

Mohon Agent 2 menentukan:
- submit idempotency key dan uniqueness scope;
- request/reply contract existing;
- status definitif submit dan cara verifikasi;
- cara lookup hasil bila response hilang;
- answer update vs submit race;
- dua submit bersamaan;
- submit setelah expiry;
- Force Submit actor/permission/reason/audit/before-after;
- auto-expiry scheduler dan interaksi dengan answer in-flight;
- status terminal dan idempotent replay;
- recovery setelah response submit hilang;
- apakah konflik/pending server dapat diselesaikan oleh operasi admin yang terotorisasi.

Client tidak akan membuat nama key, field, status, route atau response sendiri.

## 8. Permintaan F — transaksi dan global lock order

Source yang diperiksa:
- Attempt start/resume/submit mengunci Exam → Participant → Attempt.
- Answer update mengunci Attempt → Answer.
- Agent 1 merancang lock order lintas room/session/placement/proctor; scheduler dan transaksi gabungan harus diselaraskan.

Mohon Agent 2 dan Agent 1 menyetujui urutan parent-first yang tidak deadlock untuk:
- Exam close/start/resume;
- Answer save;
- Submit/Force Submit/expiry;
- score creation/finalization;
- lease acquire/takeover/revoke;
- media access yang perlu state lock;
- scheduler dan Proktor assignment.

Tentukan apakah semua jalur Attempt-only harus lock Exam → Participant → Attempt → Answer/Score/Lease. Jelaskan strategi retry deadlock dan test concurrency. Agent 4 tidak mengubah source Agent 2/1.

## 9. Permintaan G — event audit dan observability

Untuk setiap penolakan admission/media, conflict, duplicate request, lease stale, takeover, submit, Force Submit, expiry dan recovery, mohon tetapkan:
- event name;
- actor/subject;
- tenant/install, exam, attempt;
- device reference yang aman;
- correlation/request ID;
- before/after yang relevan;
- reason code;
- redaction dan retention.

Jangan mencatat token, password, bearer credential atau lease secret mentah.

## 10. Acceptance dan regression tests yang diminta

1. ADM-01–ADM-17 dan MED-01–MED-12 dari threat model Agent 4.
2. Same request ID/payload same; same ID/different payload.
3. Stale/future/null base revision.
4. Lost response after server commit then exact retry.
5. 409 conflict preserves both local/server versions.
6. Answer-vs-submit and answer-vs-Force Submit races.
7. Expiry-vs-answer and takeover-vs-answer/media races.
8. Duplicate submit and lost submit response.
9. Tenant/install isolation.
10. Exact-role and disabled-user rejection.
11. Deadlock/concurrency test on disposable MySQL 8+.
12. Full lifecycle integration test with registered device and lease/fencing.

## 11. Status, source ownership dan gate

- Agent 2 owns all backend contracts and implementation.
- Agent 4 owns browser client and E2E only after contract approval.
- Current approval: **PENDING — NEEDS DESIGN / COORDINATION**.
- F-01/F-02: P0 OPEN.
- Device Lease/Fencing: NOT PROVEN END-TO-END.
- Answer receipt/conflict: NOT APPROVED.
- Submit barrier: NOT APPROVED.
- No new endpoint/header/DTO/status is proposed here.
- No source, migration, schema or database changed.

Mohon Agent 2 menjawab per subbagian dengan `APPROVED`, `REJECTED`, `NEEDS DESIGN`, atau `PENDING`, menyebut file/class owner, transaksi/lock order, audit event, acceptance criteria dan exact-SHA regression evidence. Persetujuan parsial hanya berlaku pada butir yang disebut eksplisit.
