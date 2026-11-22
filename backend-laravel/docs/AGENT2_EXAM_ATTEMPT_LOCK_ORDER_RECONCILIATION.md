# Agent 2 — Rekonsiliasi Lock Order Exam, Attempt, Answer, Score dan Room

Tanggal: 22 November 2026
Branch Agent 2: work/stage4d-account-lifecycle-20261122
HEAD Agent 2 sebelum dokumentasi: 7d63ea3b70b5191530c2c92c5c86bf92d7324db6
Source runtime Agent 2: 74bf7178f46673e9be90523671490c0749034acd
Branch Agent 1: work/stage4b-room-session-20261122
HEAD Agent 1 saat pemeriksaan: 51571ca54ec922466e6230b4e8137a85336bc024
Source Agent 1 yang menjadi dasar: d2532792471966f1d0a9d9cbdd4d65e7c84547f0

## AUDIT

### Kontrak Attempt yang harus dipertahankan

1. Attempt baru mengikuti status Exam, token, eligibility, attempt limit dan waktu server.
2. Close Exam menolak Attempt baru.
3. Attempt IN_PROGRESS yang belum expired dan peserta masih eligible tetap boleh resume setelah Exam ditutup.
4. Attempt aktif yang masih sah tidak boleh dihapus, dipindahkan, atau dibatalkan secara otomatis.
5. Room/session bukan admission gate.
6. Device binding header saja bukan bukti Device Lease/Fencing.

### Source Agent 2

| File | Blob SHA | Temuan |
|---|---|---|
| ExamAttemptController | 13e8981cca4653ff4e9b9448a50e4d0ec8a74bae | Start memakai Exam → Participant → Attempt dan re-authorize setelah Exam lock. Resume aktif memakai device_fingerprint. |
| ExamAnswerController | b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c | Update masih mengunci Attempt → Answer; belum konsisten dengan parent-first Exam → Participant → Attempt → Answer. |
| ExamController | c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9 | update/archive/publishResults/destroy perlu transaksi, Exam lock dan re-authorize di dalam transaksi. destroy memakai SoftDeletes, bukan hard delete. |
| ExamAttempt | 341c8d00337ad8761dca94602da7acfbdc769fb0 | score() adalah relation melalui current pointer; resolvedCurrentScore() memvalidasi attempt, tenant dan scoring_version. |

### Source Agent 1 / Stage 4B

Agent 1 current branch HEAD 51571ca berisi dokumentasi tambahan saja setelah source baseline d253279. Tidak ada perubahan runtime Stage 4B dalam delta yang diperiksa.

Source blob yang tercatat pada laporan Agent 1:
- ExamSessionLifecycleService: 8e34152afd817f30cc2f81b5f25fc450571463ac.
- ExamRoomSessionService: e514fa9f751584e54af1125d48b5673e013f2339.
- ExamProctorAssignmentService: c968ce68c0decace1c35a0ccb3b2b4449c057656.
- Migration Stage 4B: 9551b1dd79397bd0432f03eefc970852b675fd25.

Temuan lintas-agent:
- Migration belum menegakkan satu operational session per Exam.
- Scheduler/session lifecycle masih memiliki jalur session-first dan belum membuktikan pemeriksaan Attempt aktif sebelum assignment diselesaikan.
- RoomSession service memiliki jalur SessionRoom → Session, berlawanan dengan Session → SessionRoom.
- Proctor assignment pernah mengunci User sebelum Assignment.
- Agent 1 mengusulkan parent-first global lock order; usulan ini belum disetujui Agent 2/3/4.
- Issue #10 tetap menjadi tempat koordinasi; belum ada approval owner terverifikasi.

### Lock order proposal Agent 1 — BELUM DISETUJUI

Usulan:
Exam → Physical Room(s) terurut → ExamSession → ExamSessionRoom(s) → Rombel(s) → Participant(s) → Placement(s) → Proctor Assignment(s) → User(s) → Attempt → Answer → Score → Device Lease/Fencing.

Untuk workflow Attempt-only:
Exam → Participant → Attempt → Answer → Score/current pointer → Device Lease/Fencing.

Ini hanya proposal untuk review, bukan kontrak final dan bukan izin mengubah Stage 4B.

## DESAIN — Matriks per operasi

| Operasi | Kandidat lock order | Re-authorization/guard |
|---|---|---|
| Update Exam/jadwal | Exam | Owner Guru, permission, tenant, status, schedule revision |
| Publish/token/snapshot | Exam → Participant(s) bila roster berubah → snapshot rows | Guru owner, tenant, status, token, schedule |
| Close Exam | Exam | Attempt baru ditolak; Attempt aktif sah tetap resume |
| Archive/destroy | Exam → child/history yang benar-benar dimutasi | Soft delete sesuai kontrak, retention, transaction, reauthorize |
| Publish results | Exam → Attempt(s) → Score(s) → current pointer | FINALIZED current score saja |
| Start Attempt | Exam → Participant → Attempt | Reauthorize setelah lock, token, waktu server, eligibility, limit |
| Resume Attempt | Exam → Participant → Attempt | IN_PROGRESS, belum expired, peserta eligible; close tidak otomatis membatalkan |
| Save Answer | Exam → Participant → Attempt → Answer | ownership, status, expiry, snapshot membership, idempotency, fencing bila tersedia |
| Submit/Force Submit | Exam → Participant → Attempt → Answer(s) → Score/current pointer → Lease/Fencing | idempotency, terminal state, actor/reason, expiry, generation |
| Scoring/finalize | Exam → Participant → Attempt → Answer(s) → Score(s) → current pointer | version immutable, pointer target valid |
| Lease acquire/renew/revoke | Exam → Participant → Attempt → Lease | generation monotonik, lease expiry, authorization, audit |
| Scheduler room/session | Menunggu persetujuan Agent 1/2 untuk urutan parent-first | cek Attempt aktif/recovery sebelum mengakhiri assignment |

Tidak boleh mengunci child lalu mengambil parent lock. Row sejenis diurutkan deterministik. Semua otorisasi yang dapat berubah diulang setelah parent lock.

## KEPUTUSAN DAN APPROVAL

- Agent 1 mengusulkan global lock order dan ownership room/session/proctor.
- Agent 2 belum memberi approval final pada lock order global.
- Agent 3 bukan owner lifecycle/lock backend; UI hanya consumer backend-authorized data.
- Agent 4 kandidat belum memiliki owner resmi.
- Issue #10 masih memerlukan keputusan eksplisit dan tidak ada integration SHA lintas-agent.

**Status: BLOCKED — CROSS-AGENT CONTRACT PENDING.**

Tidak ada perubahan source Stage 4B atau Agent 1/3/4.

## TEST WAJIB

- Save Answer vs Submit pada dua koneksi MySQL 8/InnoDB.
- Start Attempt vs close/archive/destroy.
- Scheduler vs attach/cancel/reschedule room/session.
- Proctor confirm/revoke vs scheduler dan Attempt recovery.
- Force Submit vs save answer.
- Lease takeover vs stale answer/submit.
- Tidak ada deadlock, lost update, duplicate Attempt, score pointer rusak, atau audit event parsial.
- Verifikasi semua transisi dengan transaction, parent lock, re-authorize dan idempotency.

## PENGUJIAN

Tidak ada MySQL concurrency test, PHPUnit baru, migration, atau runtime test dijalankan dalam audit ini. CI Agent 1 yang SUCCESS adalah pada SHA lama f1d01ea..., bukan source d253279 saat ini. CI Agent 2 pada source 74bf7178 berstatus runner/job failure dengan steps kosong; PHPUnit tidak diketahui berjalan.