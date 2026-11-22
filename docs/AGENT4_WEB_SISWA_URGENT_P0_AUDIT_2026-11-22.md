# AUDIT DARURAT AGENT 4 — WEB SISWA P0
Tanggal: 22 November 2026
Target yang diminta: operasional 23 November 2026
Repository: `zane0184/cbt_november`
Branch dokumentasi Agent 4: `work/agent4-web-siswa-audit-20261122`
HEAD audit docs sebelum laporan: `2d073c8b6571d6f7cd5839960d237e90343ca409`
Mode: READ-ONLY SOURCE AUDIT
Status: **BLOCKED — IMPLEMENTATION BASE/CONTRACT NOT SAFE**
Web Siswa: **NOT READY FOR OPERATIONAL EXAM**

## 1. Ringkasan keputusan

Prompt terbaru menetapkan scope Agent 4: Web Siswa, login/identitas peserta, dashboard ujian, token, start, runner, answer persistence, resume, submit dan hasil. Android dan Web Super Admin tidak dikerjakan.

Saya membaca instruksi final Issue #10 comment `7078921888`, model operasional Proktor comment `7079352852`, klarifikasi lintas kelas `7079388544`, dan addendum upgrade aplikasi terpasang `7079478897`.

Tidak ada source runtime yang diubah. Target 23 November tidak dapat dinyatakan aman dari source yang diperiksa karena ada blocker P0 pada autentikasi/role dan runner answer persistence/submit. Branch kandidat Agent 4 juga bukan basis aman untuk patch: branch itu **diverged** dari branch backend Agent 2 dan tertinggal 510 commit.

## 2. AUDIT — remote branch dan baseline

| Branch | HEAD remote |
|---|---|
| Agent 1 `work/stage4b-room-session-20261122` | `d4d9d3eb60404c09131f36f037c604055926694f` |
| Agent 2 `work/stage4d-account-lifecycle-20261122` | `7642f8df7d20effe82dc1ba5b086147894332767` |
| Agent 3 `work/web-guru-foundation-20261122` | `830914d9c41e790c4642195f9798b83cb43b1f9b` |
| Agent 4 dokumentasi `work/agent4-web-siswa-audit-20261122` | `2d073c8b6571d6f7cd5839960d237e90343ca409` |
| Kandidat Agent 4 `work/import-guru-siswa-implementation-20261122` | `f38ceec7b0653fcf3363eec40f3d4577c323cca8` |
| main | `feab77228f7108f7f7a45325d7f1fb878c07310b` |

Candidate vs Agent 2 HEAD: **diverged, 80 ahead / 510 behind**. Candidate vs Agent 1: **diverged, 80 ahead / 389 behind**. Candidate vs Agent 3: **diverged, 80 ahead / 342 behind**. Candidate carries changes to Agent 2-owned `ExamAttemptController`, `ExamAnswerController`, monitoring and Proktor files. Candidate must not be treated as current Agent 4 base or merged into Agent 2.

Official Agent 4 runtime branch/base is still not identified. The docs branch is documentation-only.

## 3. AUDIT — source Web Siswa and backend contract

Source blobs fetched from Agent 2 current branch unless stated:

| File | Blob SHA | Audit finding |
|---|---|---|
| `app/Http/Controllers/StudentExamController.php` | `981fd29d24906c158d4dda5889950fdc43f6ec3d` | Student Web gates use `hasRole('SISWA')` and reject only ADMIN_MADRASAH; not exact-one. Result route repeats the same pattern. |
| `app/Http/Controllers/AuthWebController.php` | `b8e04102858652c2fe9042f8f680c511fcbccf63` | Login requires exactly one role but explicitly permits KAMAD; redirectForUser routes KAMAD to '/'. Violates frozen KAMAD decision. |
| `app/Services/LoginIdentityResolver.php` | `c1f56029da5b8fdb5d7f80380269637b7d1c3ebe` | Resolves SISWA by NISN and non-deleted StudentProfile/User, but allows KAMAD role in allowedRoles and does not verify current Rombel/academic-period membership as part of login. |
| `app/Models/StudentProfile.php` | `1be9c380343640c72244e2a03acb78688f74700e` | StudentProfile has SoftDeletes and tenant-scoped Rombel relation. |
| `app/Models/RombelStudent.php` | `6e9badd9e09b96da163a814a09749a771e026d42` | Tenant boundary on pivot creation; no active/period state visible in this model. |
| `app/Models/User.php` | `c231e60c5a25597b4528b4d0adaac7580b70f8d3` | SoftDeletes; `hasRole()` uses loaded relation and is not an exact-role helper. |
| `app/Policies/ExamPolicy.php` | `c05d96ef932fdc81b67336240af4e1726864c3d6` | `startAttempt()` accepts any user having SISWA and denies only ADMIN_MADRASAH; KAMAD still has view/monitor policy paths. |
| `app/Http/Controllers/Api/V1/ExamAttemptController.php` | `13e8981cca4653ff4e9b9448a50e4d0ec8a74bae` | Start/resume locks Exam → Participant → Attempt and returns existing active Attempt for same device before token validation. It delegates initial admission to ExamPolicy; exact-one role is therefore not proven here. |
| `app/Http/Controllers/Api/V1/ExamAnswerController.php` | `b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c` | Explicit exact-one SISWA check; validates owner/tenant/question_order/device/status/expiry; uses client_request_id/base_revision and returns 409 with current answer. Lock order shown Attempt → Answer, not the parent-first order used by Attempt lifecycle. |
| `resources/views/student/exams/index.blade.php` | `e76c70f4646677c3b64bd2c36b333330f93cdcae` | Lists ExamParticipant rows and attempts; only displays student name, not class/rombel/academic period profile. |
| `resources/views/student/exams/show.blade.php` | `7283ca553d787f4505299a306e2bd487884622d3` | Runner is implemented in one Blade/inline JS file; details below. |
| `resources/views/student/results/show.blade.php` | `acff7cd3bfd74839f18e348dc1bf939cfd06a44e` | Shows only published result and finalized current score in inspected controller flow. |
| `routes/web.php` | `f012b3de23575e3ef91c8f07ad1c0d7ae780fb21` | Student pages behind auth/tenant/license; role authorization remains controller/policy responsibility. |
| `tests/Feature/StudentWebLoginTest.php` | `01fb9e72b310e2de01143551cf597113acc21938` | 11 test methods statically present; includes login, NISN, mixed role and account deactivation cases. Not runtime proof. |
| `tests/Feature/ExamExecutionHardeningTest.php` | `87d5c547116298c0eded649971162fd29b261063` | 6 test methods statically present; includes same Attempt resume, expiry, other student/tenant and question order. |
| `tests/Feature/ExamAnswerTest.php` | `c6ac21f3db9591e37ffbdf21228f4736d7bd962a` | 21 test methods statically present; includes idempotency/revision/IDOR/device and answer payload tests. |
| `tests/Feature/ParticipantAuthorizationAndAttemptLimitTest.php` | `15ea92a22237e3b395fdac435d4064b6534283ce` | 13 test methods statically present; includes participant, token, eligibility, attempt limit and tenant cases. |

### 3.1 Login/identity — P0

- Web login exact-one check exists, but allowed role list still includes KAMAD. LoginIdentityResolver also accepts KAMAD. **KAMAD frozen is not enforced**.
- StudentExamController index/show/result do not enforce exact-one SISWA; mixed SISWA+GURU or SISWA+KAMAD may pass the controller's role predicate if authenticated session remains.
- ExamPolicy startAttempt uses `hasRole('SISWA')`, not exact-one. API Attempt start inherits this policy gap.
- Login resolver confirms non-deleted StudentProfile and NISN, but inspected source does not require a current valid Rombel membership/academic period. ExamParticipant eligibility is separately checked for exam access.
- Agent 2 owns these backend/auth/policy changes. Agent 4 must not patch them.

### 3.2 Runner — P0

The current runner:
- Starts/resumes through `POST /api/v1/exams/{exam}/attempts`.
- Loads execution and server answers from API; derives displayed timer from server `server_now` and `performance.now()`.
- Uses an in-memory JS `Map` for answers, pending requests, revisions and timers. There is no IndexedDB/local durable queue in the inspected view.
- Generates `client_request_id` in memory and uses `base_revision` on PUT answer.
- On HTTP 409 with `current_revision`, it deletes the pending item and replaces local state with server answer. This can discard an unacknowledged local answer.
- At automatic expiry, if flush fails, it still sends submit and displays completion after server response. This can finalize without the pending answer reaching the server.
- Submit body is empty; no client idempotency key or recovery lookup is visible in this client.
- Manual submit blocks when flush throws, but after response loss it has no durable submit identity/receipt recovery.
- SHORT_ANSWER currently renders a textarea despite product status BLOCKED. Must not be enabled in operational runner until Agent 2/3 authoring→snapshot→scoring contract is proven.
- MATCHING renderer expects `option_key === LEFT` and `matching_key === RIGHT`; authoring/snapshot contract must confirm this exact shape before accepting it.
- Media requests use `/api/v1/media/{id}`; F-02 authorization chain is not proven by the client and remains Agent 2 backend responsibility.

### 3.3 Admission and token — P0

- Attempt start re-authorizes after locking Exam and Participant and checks participant eligibility.
- It checks token before creating a new Attempt and clamps expiry to Exam end and participant eligibility end.
- A valid active Attempt is resumed before new-token validation; this preserves same Attempt resume but exact-one role, current device/lease/fencing and all admission preconditions still need adversarial proof.
- ExamPolicy and ExamController snapshot release path remain P0 review items. Snapshot must not be sent before admission is proven.
- Room/Session is not an admission gate under the final coordinator decision; do not invent room-based student admission.

## 4. DESAIN — P0 implementation split and acceptance

| Priority | Work | Owner | Gate |
|---|---|---|---|
| P0 | Exact-one SISWA in web login/session, StudentExamController, ExamPolicy and all Student API; inactive/soft-deleted rejection | Agent 2 | Backend approval + tests |
| P0 | Remove KAMAD from login resolver, login allow-list, redirects, policies, token/session and all CBT paths; preserve administrative history | Agent 2 | Backend approval + tests |
| P0 | Confirm current student profile/membership/academic period rules and ExamParticipant source | Agent 2 + academic owner | Contract |
| P0 | F-01 admission and snapshot release only after authenticated exact-role participant eligibility, schedule, token, Attempt/device/lease state | Agent 2 | Adversarial tests |
| P0 | F-02 media authorization tied to Attempt question_order/snapshot/version/media and active device/lease/fencing | Agent 2 + Agent 3 | Adversarial tests |
| P0 | Canonical answer request/response, same request ID retry, receipt/ack, 409 conflict archive, lock order | Agent 2 | Approved contract |
| P0 | Prevent auto-submit when answer flush failed; submit idempotency and response-lost recovery | Agent 2 backend + Agent 4 client | Approved contract |
| P0 | Durable client answer queue/recovery across reload/network interruption; only if contract allows browser persistence | Agent 4 | Backend receipt contract |
| P0 | Preserve same Attempt on reload/login; no new Attempt after close; server expiry/time authoritative | Agent 2 backend + Agent 4 UX | Integration tests |
| P0 | Disable SHORT_ANSWER in student runner while blocked; verify MATCHING payload schema | Agent 3 + Agent 2 | Approved authoring/snapshot contract |
| P1 | Student profile, Rombel, academic year and result history presentation | Agent 4 consuming approved academic endpoints/data | Contract |
| P1 | Arabic RTL/harakat/math rendering and responsive visual polish | Agent 4 | Existing renderer confirmation |

## 5. AUDIT DEPLOYMENT AKTIF / UPGRADE COMPATIBILITY

User's current prompt states the website is already installed and viewable, database application is empty and will be created via aaPanel; no SSH access is available and no VPS/deployment action is permitted.

This audit did not access VPS or public login, did not inspect live schema/migration ledger, did not identify deployed release SHA, and did not inspect live storage/config. The repository therefore cannot establish source-to-deployed parity. Prior Issue #10 upgrade-safety addendum requires preserving existing application data/config if present. No live migration, fresh-install, DB reset, APP_KEY change, deployment or server action was performed.

Status: **BLOCKED — DEPLOYED BASELINE / SCHEMA NOT VERIFIED**. This is not a request for SSH credentials; the current scope explicitly forbids VPS access.

## 6. IMPLEMENTASI

**DOCUMENTATION ONLY — NO RUNTIME IMPLEMENTATION.**

No source/runtime/test/migration/config/database/branch Agent 1–3/candidate/main was modified. No deployment or VPS access occurred.

## 7. PENGUJIAN

| Test area | Status | Evidence |
|---|---|---|
| PHP lint | NOT RUN | No PHP runtime invoked |
| PHPUnit | NOT RUN | Static source review only |
| MySQL 8/InnoDB | NOT RUN | No disposable DB supplied/started |
| CI exact-SHA | UNKNOWN/BLOCKED | Prior Agent 2 run #45003796892 was BLOCKED — RUNNER on SHA 74bf717; no current PASS established |
| Student login | NOT RUN | 11 static methods in StudentWebLoginTest; not execution |
| Exact-one role on all Student endpoints | BLOCKED | Controller/policy gaps above |
| KAMAD login/token/session | BLOCKED | Login resolver/allow-list and policy still contain KAMAD |
| Token/schedule/start | NOT RUN | Source exists; no integrated runtime test |
| Attempt concurrency/resume/expiry | NOT RUN | 6 static execution tests; no runtime proof |
| Answer persistence/idempotency/409 | NOT RUN | 21 static answer tests; client 409 path unsafe |
| Autosave + submit race | BLOCKED | Current auto-submit can continue after flush failure |
| Reload/network recovery | BLOCKED | Current pending state is in-memory only |
| Submit idempotency/response lost | BLOCKED | No durable client submit key/receipt recovery in inspected runner |
| F-01 admission | NOT RUN / P0 OPEN | No adversarial integrated evidence |
| F-02 media | NOT RUN / P0 OPEN | No adversarial integrated evidence |
| Browser desktop/tablet/mobile E2E | NOT RUN | No browser session executed |

Static method counts are inventory only and are not PASS.

## 8. RISIKO / BLOCKER

1. **P0 role bypass:** mixed-role SISWA may reach Student Web/policy path; Agent 2 must close.
2. **P0 KAMAD access:** login resolver and login allow-list permit KAMAD; ExamPolicy still contains KAMAD view/monitoring paths.
3. **P0 answer loss:** 409 path discards pending local version; automatic expiry submits even after failed flush.
4. **P0 recovery gap:** no durable local queue; pending answer can be lost on reload/tab crash/network outage.
5. **P0 submit uncertainty:** no visible durable idempotency/receipt lookup for lost response.
6. **P0 F-01/F-02 and lease/fencing not verified.**
7. **Branch safety:** candidate Agent4 is divergent/stale; docs branch is not a runtime branch; no approved base/branch.
8. **Deployment parity:** installed site source/schema not inspectable under no-VPS-access constraint.
9. **Deadline:** no basis to claim operational readiness by 23 November. Do not use a production exam to discover these failure modes.

## 9. HANDOVER — next exact actions

### Agent 2 (backend owner)
- Close exact-one SISWA and KAMAD frozen paths across auth/session/policy/StudentExamController/Student API.
- Confirm inactive account and active academic membership semantics.
- Approve canonical Attempt start/resume/execution, Answer receipt/409, submit idempotency/response-lost and F-01/F-02 contracts.
- Reconcile lock order and expiry behavior; provide exact SHA and disposable MySQL 8 tests.

### Agent 3 (authoring/UI Guru owner)
- Confirm immutable snapshot payload and six question type schemas, especially MATCHING.
- Confirm SHORT_ANSWER stays blocked and answer key never enters student payload.
- Confirm media references are immutable and F-02 compatible.

### Agent 4 (Web Siswa)
- Once official base is approved, patch only student view/client and its owned tests.
- Remove/disable SHORT_ANSWER rendering while blocked.
- Implement durable queue only against approved receipt contract.
- Fix 409 conflict behavior: retain local payload and archive both versions; never silently replace.
- Submit barrier must block if any pending answer/conflict/outcome-unknown remains; automatic expiry may not claim successful submit when answer flush fails.
- Add desktop/tablet/mobile browser E2E and reload/network/response-lost cases.

### Koordinator
- Designate exact official Agent4 implementation branch and base commit, or authorize a new Agent4 branch from an explicitly selected base. Candidate `f38ceec7...` must not be used by assumption.
- Obtain direct Agent 2/3 technical responses; coordinator-authored notes are not owner approvals.
- Keep operational gate closed until all P0 security and answer-integrity tests pass.

**Final status: WEB SISWA NOT READY FOR OPERATIONAL EXAM.**
