# AUDIT METHOD-LEVEL STAGE 4B — WEB CBT
Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch audit: `work/stage4b-room-session-20261122`
Status: READ-ONLY AUDIT / DESIGN OPEN / RUNTIME TEST NOT RUN

## 1. Scope final yang digunakan
- Sampai Juni 2027 hanya Web CBT aktif. Android native/Android Studio dan Web Super Admin ditunda, bukan blocker/dependency/prasyarat.
- Portal aktif hanya ADMIN_MADRASAH, GURU, SISWA. Operator ditangani Admin Madrasah. Proktor adalah assignment sementara akun Guru dengan menu di Web Guru; tidak ada akun/portal/role permanen Proktor. Siswa hanya peserta.
- KAMAD bukan bagian ruang lingkup identitas/kewenangan CBT. Referensi source lama hanya relevan jika memengaruhi allowlist otorisasi atau fungsi CBT.
- Satu ZIP distribusi; instalasi, database akademik dan resource operasional terpisah per madrasah; tenant_id defense-in-depth; tidak ada shared academic DB.

## 2. Source yang diperiksa
| Path | Blob SHA |
|---|---|
| `backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php` | `9551b1dd79397bd0432f03eefc970852b675fd25` |
| `backend-laravel/app/Services/ExamSessionLifecycleService.php` | `8e34152afd817f30cc2f81b5f25fc450571463ac` |
| `backend-laravel/app/Services/ExamRoomSessionService.php` | `e514fa9f751584e54af1125d48b5673e013f2339` |
| `backend-laravel/app/Services/ExamProctorAssignmentService.php` | `c968ce68c0decace1c35a0ccb3b2b4449c057656` |
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` | `13e8981cca4653ff4e9b9448a50e4d0ec8a74bae` |
| `backend-laravel/app/Services/ExamMonitoringScope.php` | `11b455954f974d1c60ed5c49700368f8d37c09c1` |
| `backend-laravel/app/Models/ExamSession.php` | `d1d41ad8c7063edbd6b17e094a3b39bdec71664a` |
| `backend-laravel/app/Models/ExamSessionRoom.php` | `d55c16e8a84432a1283d90418b29aa9833d28d59` |

## 3. AUDIT — database invariant
Migration creates `exam_sessions` with unique `(tenant_id, exam_id, session_number)`. It does not define an `is_operational`/canonical marker or equivalent unique guard for one session per Exam. Distinct session_number values remain valid at DB level. Service `createSession()` also accepts caller-supplied session number and inserts a new row after checking only duplicate number. Therefore neither DB nor service currently enforces one canonical operational session per Exam.

Room/session relationships use composite tenant/exam foreign keys. Room mapping and participant placement tables have active-only uniqueness indexes (MySQL functional index / SQLite partial index branches), including one active placement per participant. Those constraints do not solve canonical session invariant.

## 4. AUDIT — scheduler and Attempt recovery
`ExamSessionLifecycleService::synchronize()`:
1. Selects SCHEDULED/ACTIVE sessions based on starts_at/ends_at.
2. For each candidate, opens its own transaction and locks the ExamSession row.
3. Marks session COMPLETED when ends_at <= scheduler time.
4. Then queries assignments for the SessionRoom IDs and marks them COMPLETED if session completed, or EXPIRED by expires_at.
5. Source comment explicitly says scheduler does not read or change Attempt/Answer data.

There is no visible recovery barrier in this service before Session completion or assignment completion. It does not lock Exam/Room/SessionRoom/Participant/Attempt in a shared lifecycle order. Risk: scheduler may end the Session/assignment support while a valid IN_PROGRESS Attempt still requires resume/recovery. Runtime race has not been tested.

## 5. AUDIT — ExamRoomSessionService methods

### createSession
- Locks Exam and validates published/locked Exam schedule.
- Accepts arbitrary positive session_number and inserts another Session.
- No canonical one-session guard. This is a direct mismatch with one Exam = one operational Session.

### attachRoom
- Locks Exam → physical Room → Session.
- Validates active Room, SCHEDULED Session, capacity override and room time overlap.
- Creates/reactivates SessionRoom and increments schedule_revision.
- This is structurally compatible with many Rooms inside one Session, but currently permits attachment to any existing session row.

### mapRombel
- Reads SessionRoom scope, then locks Exam → Room → SessionRoom → Session → Rombel → ordered Participants.
- Checks active Rombel mapping, conflicting participant placement, and room capacity.
- Creates mapping and active participant placements; bumps schedule_revision and logs event.
- It checks conflicting placement before insert and has active-only DB uniqueness for participant placement. MySQL concurrency and capacity races are not yet proven.

### releaseRombelMapping
- Locks Exam → Room → SessionRoom → Session → Mapping → ordered Participants → ordered placement rows.
- Rejects release if any linked Attempt has status IN_PROGRESS; it does not visibly include an Attempt expiry/eligibility predicate in that check.
- Releases placement/mapping with actor/time/reason and bumps revision.
- Needs contract review for recovery eligibility and consistent lock ordering with start/resume/submit.

### cancelSession
- Locks Exam, collects Room IDs, locks sorted physical Rooms, then locks Session.
- Allows cancellation only for SCHEDULED session.
- Checks IN_PROGRESS Attempt through active placements, then revokes open Proktor assignments, releases participant placements and Rombel mappings, deactivates SessionRooms, marks Session CANCELLED and increments revision.
- This is a destructive operational transition with audit trail, but still independently cancels a Session even though target design says one canonical Session per Exam; schedule/cancel authority must be reconciled with Exam schedule.

### rescheduleSession
- Locks Exam → sorted Rooms → Session.
- Allows only SCHEDULED session and checks room overlaps and active IN_PROGRESS Attempts.
- Changes Session starts_at/ends_at independently, then bumps revision and marks pending/confirmed Proktor assignments NEEDS_REVIEW.
- Source comment says Exam.start_at/end_at remains owned by Guru and Admin only reschedules operational Session. This conflicts with a single official Exam schedule unless Agent2/Coordinator approve a defined mirror/revision contract. Do not patch independently.

### updateRoom
- Starts by locking physical Room and then reads associated Session data. This differs from other operations that lock Exam before Room and may create lock inversion when overlapping requests target the same entities.

## 6. AUDIT — Proktor assignment service
- `assign()` uses lockScope then locks target User, then current assignment row; validates a single role GURU plus active teacher profile and tenant; validates assignment window inside Session; records audit event.
- `confirm()` uses lockScope, locks Guru User, then Assignment; checks schedule_revision and overlapping confirmed assignment.
- `revoke()` first reads assignment snapshot, then locks Exam, uses lockScope (Exam → Room → SessionRoom → Session), then User → Assignment; writes revoke actor/time/reason and audit event.
- lockScope order is Exam → Room → SessionRoom → Session. The returned tuple order is [Exam, SessionRoom, Session, Room], but actual row lock order is Room before SessionRoom before Session.
- Potential lock-order differences with RoomSessionService and scheduler must be resolved globally; no deadlock is asserted without an executed concurrency test.

## 7. AUDIT — Attempt controller and monitoring scope
- Attempt start/resume locks Exam → Participant → Attempt. Existing IN_PROGRESS and unexpired Attempt is resumed using the same Attempt; device_fingerprint mismatch returns 409. This is not Device Lease/Fencing proof.
- The controller's execution path also locks Exam → Participant → Attempt. Placement/SessionRoom is not part of that lock sequence in the inspected code.
- Monitoring scope filters assigned participants by confirmed Proktor SessionRoom assignment and active placement, but an Exam-author branch returns all Exam participants before Room assignment filtering. Authoring permission and Proktor capability need distinct paths; creating/publishing an Exam must not automatically grant room-scoped Proktor monitoring.

## 8. DESIGN questions / dependencies
1. Agent2 must approve one global parent-first lock order across Exam, sorted Room(s), Session, sorted SessionRoom(s), Rombel(s), Participant(s), placement(s), Proktor Assignment(s), User(s), Attempt, Answer/Score/Lease. The proposed sequence is not approved by this audit.
2. Agent2 must specify recovery barrier: what qualifies as resumable Attempt, when Session/assignment may complete, how bounded recovery support works, and what happens to Proktor assignment while Attempt recovery remains open.
3. Agent2 must align Answer update lock order with Attempt start/resume/submit and scoring/expiry paths.
4. Canonical ExamSession database invariant and safe legacy-data handling require a migration design. No automatic selection if multiple sessions contain history.
5. Exam schedule must remain official authority; Session must not become independent wave/session. Need schedule_revision and reschedule/cancel contract.
6. Agent3 must confirm Web Admin/Guru UI uses one Session with many Rooms and Proktor menu consumes backend assignment scope only.
7. Agent4 must confirm student resume uses same Attempt after close when eligible, and scheduler/assignment expiry cannot block recovery.

## 9. PENGUJIAN
- Source fetch + blob SHA: PASS for listed files.
- PHPUnit: NOT RUN.
- Disposable MySQL migration/restore: NOT RUN.
- Multi-connection concurrency/deadlock/race: NOT RUN.
- Scheduler vs Attempt submit/resume: NOT RUN.
- Browser/Livewire/API integration: NOT RUN.
- UAT: NOT RUN.

## 10. Status
- AUDIT: method-level initial audit completed for listed methods.
- DESIGN: canonical session, recovery barrier, global lock order and schedule authority remain open.
- IMPLEMENTATION: no runtime source changed.
- TEST: no runtime test executed.
- Overall: IN PROGRESS; not production-ready.
