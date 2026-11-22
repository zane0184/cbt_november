# MATRIKS LOCK ORDER — STAGE 4B / AGENT 1–2
Tanggal: 22 November 2026
Status: USULAN UNTUK REVIEW — BELUM DISETUJUI

## Prinsip
- Tidak semua workflow harus mengunci seluruh jenis row. Kunci hanya row yang dibutuhkan workflow.
- Jika workflow menyentuh parent dan child, parent harus dikunci lebih dahulu.
- Row pada kelompok yang sama diurutkan deterministik berdasarkan primary key (UUID/PK) sebelum lock.
- Tidak boleh ada jalur yang mengunci child dahulu lalu mengambil parent lock.
- Semua keputusan otorisasi yang dapat berubah harus diulang setelah row parent terkunci.
- Urutan final harus disetujui Agent 1/2/3/4 untuk bagian ownership masing-masing.

## Urutan global usulan
Exam → Physical Room(s) → ExamSession → ExamSessionRoom(s) → Rombel(s) → Participant(s) → Placement(s) → Proctor Assignment(s) → User(s) → Attempt → Answer → Score → Device Lease/Fencing.

Untuk workflow Attempt-only yang tidak mengubah room/placement/assignment:
Exam → Participant → Attempt → Answer → Score → Device Lease/Fencing.

## Matriks per workflow
| Workflow | Lock yang diperlukan (urut) | Validasi/reauthorize | Audit event minimum |
|---|---|---|---|
| Membuat Exam | Assessment (jika dimutasi/di-lock) → Exam baru | tenant, Guru owner, subject/year/assignment | EXAM_CREATE |
| Mengubah Exam/jadwal | Exam | Guru owner, permission, status, start/end valid; reauthorize setelah lock | EXAM_UPDATE / EXAM_SCHEDULE_CHANGE |
| Publish/token/snapshot | Exam → Participant(s) bila roster disentuh → snapshot rows sesuai urutan | Guru owner, tenant, draft/publish transition, token dan schedule revision | EXAM_PUBLISH / EXAM_TOKEN_GENERATE / EXAM_SNAPSHOT_CREATE |
| Close Exam | Exam | Guru owner, status saat ini; jangan menghapus Attempt aktif | EXAM_CLOSE |
| Archive | Exam → row histori/score/report terkait bila operasi menyentuhnya | status close/finalization dan history retention | EXAM_ARCHIVE |
| Publish results | Exam → Score(s) sesuai urutan → current-score pointer/report snapshot | hanya FINALIZED score, current pointer, authorization | EXAM_RESULTS_PUBLISH |
| Delete/destroy (soft delete) | Exam → Participant(s) / child history bila perlu | transaksi + reauthorize; cek Attempt dan histori; soft delete saja jika kontrak mengizinkan | EXAM_DELETE / EXAM_ARCHIVE |
| Membuat/mengubah Room | Physical Room | admin metadata only; tidak memberi monitoring | EXAM_ROOM_CREATE / UPDATE |
| Attach Room ke ExamSession | Exam → Physical Room → ExamSession → ExamSessionRoom | tenant, Exam owner/authorization, kapasitas, canonical session | EXAM_SESSION_ROOM_ATTACH |
| Mapping Rombel | Exam → ExamSession → ExamSessionRoom → Rombel | tenant, year/semester, rombel eligibility | EXAM_ROOM_ROMBEL_ASSIGN / RELEASE |
| Placement peserta | Exam → Physical Room(s) jika kapasitas berubah → Session → SessionRoom(s) → Rombel(s) → Participant(s) → Placement(s) | tenant, participant eligibility, satu active placement | EXAM_PARTICIPANT_ROOM_ASSIGN / RELEASE |
| Reassignment peserta | Exam → Room(s) terurut → Session → SessionRoom(s) → Rombel(s) → Participant → Placement lama/baru | tidak memindah/hapus Attempt; aturan Attempt aktif harus diputuskan | EXAM_PARTICIPANT_ROOM_REASSIGN |
| Assign Proktor | Exam → Physical Room → Session → SessionRoom → Assignment(s) → User(Proktor) | Guru aktif, tenant, room scope, waktu dan schedule_revision | EXAM_PROCTOR_ASSIGN |
| Confirm/revoke Proktor | Exam → Room → Session → SessionRoom → Assignment → User bila perlu | status CONFIRMED, expiry, schedule revision, no revocation during protected recovery | EXAM_PROCTOR_CONFIRM / REVOKE |
| Scheduler | Exam → Room(s) → Session → SessionRoom(s) → Assignment(s) → User(s) bila transisi account diperlukan → Attempt(s) hanya jika kontrak recovery membutuhkan pembacaan/lock | re-read status/time; active Attempt and bounded recovery; deterministic ordering | EXAM_SESSION_STATUS_AUTO / PROCTOR_ASSIGNMENT_STATUS_AUTO |
| Start Attempt baru | Exam → Participant → Attempt (new row) | reauthorize Exam after lock; token, server time, eligibility, limit; session not admission gate | EXAM_ATTEMPT_START |
| Resume Attempt | Exam → Participant → Attempt | status IN_PROGRESS, expiry, participant eligibility; resume allowed after close if valid | EXAM_ATTEMPT_RESUME |
| Save Answer | Exam → Participant → Attempt → Answer | participant ownership, attempt active/expiry, snapshot membership, lease/fencing; revalidate after locks | EXAM_ANSWER_SAVE |
| Submit/Force Submit | Exam → Participant → Attempt → Answer(s) → Score(s) → Lease/Fencing | submit idempotency, attempt status, expiry, authorization Force Submit, fencing epoch | EXAM_ATTEMPT_SUBMIT / FORCE_SUBMIT |
| Scoring/finalization | Exam → Participant → Attempt → Answer(s) → Score(s) → current-score pointer | immutable score version, current pointer only to FINALIZED version | EXAM_SCORE_FINALIZE |
| Lease acquire/renew | Exam → Participant → Attempt → Lease | device identity, expiry, epoch/fencing token, tenant and participant match | DEVICE_LEASE_ACQUIRE / RENEW |
| Lease revoke/takeover | Exam → Participant → Attempt → Lease(s) → User/device references | explicit permission, expected epoch, idempotency, old device fenced | DEVICE_LEASE_REVOKE / TAKEOVER |

## Temuan konkret yang memicu matriks
1. Agent 1 scheduler mengunci ExamSession tanpa parent Exam; berpotensi deadlock dengan workflow Exam-first.
2. Agent 1 RoomSession service memiliki jalur SessionRoom→Session, sedangkan workflow lain Session→SessionRoom.
3. Agent 1 ProctorAssignmentService mengunci User sebelum Assignment; usulan global menempatkan Assignment sebelum User.
4. Agent 2 ExamAttemptController start/submit sudah memakai Exam→Participant→Attempt.
5. Agent 2 ExamAnswerController update masih Attempt→Answer; belum parent-first.
6. Archive/publishResults/destroy Agent 2 perlu transaksi, Exam lock, dan reauthorize dalam transaksi.

## Keputusan yang wajib diminta
- Apakah seluruh Exam lifecycle memakai Exam sebagai serialization root?
- Bagaimana scheduler membaca active Attempt tanpa mengunci child lebih dahulu?
- Apakah scoring/lease memerlukan lock Score dan Lease setelah Attempt?
- Apakah Force Submit/revoke/takeover harus menunggu atau memutus recovery aktif?
- Bagaimana menghindari lock User jika data profil/role dapat dibaca tanpa row lock?
- Apa audit event dan idempotency key untuk tiap transisi?

## Pengujian minimum
- Save Answer vs Submit: dua koneksi MySQL 8/InnoDB, tidak deadlock, tidak ada lost update.
- Scheduler vs room attach/cancel: parent-first, tidak deadlock.
- Assignment confirm/revoke vs scheduler: status final deterministik.
- Start Attempt vs close/archive/destroy: tidak ada Attempt baru setelah close; tidak ada race delete.
- Force Submit vs save answer dan lease takeover: fencing epoch monotonik dan hanya satu pemenang.
- Semua workflow memverifikasi tenant dan otorisasi ulang setelah parent lock.

Tidak ada perubahan source dalam dokumen ini. Urutan di atas adalah proposal review, bukan keputusan implementasi.
