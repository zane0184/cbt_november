# WA-01 — Matriks Authorization, Livewire, Metadata Assignment, dan Test Plan Final

**Status:** DOKUMENTASI-ONLY • IMPLEMENTASI NOT STARTED • MENUNGGU PERSETUJUAN CODING  
**Source:** work/web-admin-w0-20261121  
**Source pin:** 2b4321ce032fd5e67cf022da8564a5287b8cef6f  
**Compare source:** identical • 0 ahead • 0 behind • 0 file changed  
**Tanggal:** 21 November 2026 (WIB)

> Seluruh target pada dokumen ini adalah rancangan, bukan implementasi. Semua test berstatus NOT RUN sampai tersedia artefak run yang dapat diverifikasi.

## 1. Batas dan konvensi

- ADMIN berarti hanya role runtime ADMIN_MADRASAH. ADMIN dan OPERATOR bukan alias Admin aktif.
- Guru, Siswa, dan Proktor tetap mengikuti perilaku W0 yang sudah ada. Tidak ada capability baru, schedule revision, status Guru baru, atau integrasi WA-03 dalam acceptance WA-01.
- Proktor pada matriks berarti role PROKTOR dengan assignment tenant-local yang aktif di source W0. Ini bukan persetujuan mempertahankan portal/role Proktor permanen.
- Permission disebut sesuai nama yang benar-benar diperiksa source. Bila tidak ada pemeriksaan permission pada method, dinyatakan demikian.
- TenantScoped tidak menggantikan authorization. Setiap akses perlu tenant, owner, TeacherAccess, assignment atau participant scope yang sesuai.
- Admin deny harus terjadi sebelum query relasi sensitif, cache read, service, file generation, serialization atau fallback Siswa.
- Constructor injection bukan authorization. Helper private hanya boleh dipanggil dari alur yang sudah diotorisasi.

## 2. Matriks enam policy

### 2.1 QuestionPolicy

| Method | Akses W0 saat ini | Target WA-01 | Permission | Tenant / ownership / scope | Admin / Guru / Siswa / Proktor | Shared / deny Admin |
|---|---|---|---|---|---|---|
| viewAny | Role dengan tenant dan question.view; grant bootstrap memberi Admin dan Guru | Admin deny; Guru tetap | question.view | tenant_id wajib; caller query tenant-local | Admin deny; Guru allow sesuai query; Siswa/Proktor deny bila tanpa permission | Shared; Admin deny eksplisit sebelum permission result |
| view | Admin dapat melihat MADRASAH bila question.view; PERSONAL bila owner sama; SHARED bila publication approved; Guru via subject scope | Admin deny semua owner type; Guru tetap | question.view | Tenant equality; PERSONAL owner_user_id; SUBJECT/MADRASAH via subject; SHARED publication tenant/status/scope | Admin deny; Guru scope; Siswa/Proktor tidak diberi akses baru | Shared; deny sebelum match owner_type/query publication |
| create | Guru + question.manage | Tidak berubah | question.manage | tenant tersedia; role GURU | Admin/Siswa/Proktor deny; Guru allow | Shared; pertahankan predicate Guru |
| update | Guru + question.manage, bukan ARCHIVED, tenant sama, PERSONAL owner atau SUBJECT assignment | Tidak berubah | question.manage | Tenant, owner_user_id, TeacherAccess, status | Admin/Siswa/Proktor deny; Guru allow sesuai scope | Shared; jangan perluas kepemilikan Guru |
| delete | Selalu false | Tidak berubah | Tidak ada | Physical delete dilarang | Semua deny | Shared; jangan ubah archive/soft-delete |
| publish | Delegasi ke update | Tidak berubah | question.manage melalui update | Sama dengan update | Admin/Siswa/Proktor deny; Guru sesuai update | Shared; pertahankan publish Guru |
| canAccessSubject (private) | Hanya GURU dan TeacherAccess | Tidak berubah | Tidak memeriksa permission sendiri | Subject ID + TeacherAccess | Admin/Siswa/Proktor deny; Guru sesuai assignment | Helper internal, bukan entry authorization |

**Temuan:** viewAny saat ini meloloskan Admin dengan question.view; view secara eksplisit meloloskan Admin untuk owner_type MADRASAH. Ini akses data langsung, bukan sekadar kelemahan menu.

### 2.2 AssessmentPolicy

| Method | Akses W0 saat ini | Target WA-01 | Permission | Tenant / ownership / scope | Admin / Guru / Siswa / Proktor | Shared / deny Admin |
|---|---|---|---|---|---|---|
| viewAny | ADMIN_MADRASAH, OPERATOR, KAMAD, GURU, SISWA; tidak memeriksa tenant/permission | Admin deny; Guru dan Siswa tetap | Tidak ada pemeriksaan permission | Caller wajib tenant-local | Admin deny; Guru/Siswa allow; Proktor deny | Shared; deny Admin sebelum role list |
| view | Tenant sama; Guru TeacherAccess; Siswa participant pada exam assessment; Admin/OPERATOR/KAMAD allow | Admin deny; Guru/Siswa tetap | Tidak ada permission eksplisit | Tenant equality; Guru assignment; Siswa participant ownership | Admin deny; Guru scope; Siswa own participant; Proktor deny | Shared; deny Admin sebelum role fallback |
| create | Guru + exam.manage | Tidak berubah | exam.manage | Controller validasi subject/year/semester tenant | Admin/Siswa/Proktor deny; Guru allow | Shared; pertahankan |
| update | Guru + exam.manage + creator + TeacherAccess | Tidak berubah | exam.manage | Tenant, created_by, assignment subject/year/semester | Admin/Siswa/Proktor deny; Guru creator+scope | Shared; jangan ubah ownership Guru |
| delete | Assessment tanpa exam; Guru + exam.manage + creator + TeacherAccess | Tidak berubah | exam.manage | Tenant, creator, no linked exams, TeacherAccess | Admin/Siswa/Proktor deny; Guru sesuai W0 | Shared; pertahankan exam count check |

### 2.3 ExamPolicy

| Method | Akses W0 saat ini | Target WA-01 | Permission | Tenant / ownership / scope | Admin / Guru / Siswa / Proktor | Shared / deny Admin |
|---|---|---|---|---|---|---|
| viewAny | Admin/OPERATOR/KAMAD/Guru/Siswa | Admin deny; Guru/Siswa tetap | Tidak ada | Caller query harus tenant-local; Siswa participant | Admin deny; Guru/Siswa allow; Proktor deny | Shared; deny sebelum role list |
| view | Tenant sama; Guru TeacherAccess; Admin/OPERATOR/KAMAD allow; Siswa participant sendiri | Admin deny; Guru/Siswa tetap | Tidak ada eksplisit | Tenant; Guru canManageExam; Siswa participant | Admin deny; Guru scope; Siswa own; Proktor deny | Shared; deny sebelum branching |
| preview | Guru, exam.manage + question.manage, assessment creator, TeacherAccess | Tidak berubah | exam.manage + question.manage | Tenant, creator, assessment/exam scope | Admin/Siswa/Proktor deny; Guru allow | Shared; pertahankan answer-key preview Guru |
| create | Guru + exam.manage | Tidak berubah | exam.manage | Tenant + valid assessment | Admin/Siswa/Proktor deny; Guru allow | Shared |
| update | Guru + exam.manage, creator, unlocked, TeacherAccess | Tidak berubah; schedule path tetap BLOCKED | exam.manage | Tenant, creator, unlocked, TeacherAccess | Admin/Siswa/Proktor deny; Guru W0 | Shared; tidak mengubah schedule |
| delete | Guru + exam.manage, creator, unlocked, TeacherAccess | Tidak berubah | exam.manage | Tenant, creator, unlocked, TeacherAccess | Admin/Siswa/Proktor deny; Guru W0 | Shared |
| manageQuestions | Guru + question.manage dan update | Tidak berubah | question.manage + exam.manage via update | Tenant, creator, TeacherAccess | Admin/Siswa/Proktor deny; Guru allow | Shared; Admin guard saja |
| manageParticipants | update + exam.assign | Tidak berubah | exam.manage + exam.assign | Tenant, creator, TeacherAccess | Admin/Siswa/Proktor deny; Guru allow | Shared |
| generateToken | Guru + exam.token; PUBLISHED, locked, creator, TeacherAccess | Tidak berubah | exam.token | Tenant, creator, assignment | Admin/Siswa/Proktor deny; Guru allow | Shared; jangan ubah token lifecycle |
| lock | Delegasi update | Tidak berubah | exam.manage | Tenant, creator, unlocked, TeacherAccess | Admin/Siswa/Proktor deny; Guru allow | Shared; jangan ubah lock/publish |
| close | Admin + exam.close bila locked dan PUBLISHED/CLOSED; atau Guru + exam.manage creator/scope | Admin deny; Guru tetap | Admin saat ini exam.close; Guru exam.manage | Tenant, locked, status; Guru creator+TeacherAccess | Admin deny; Guru allow; Siswa/Proktor deny | Shared; deny sebelum Admin permission branch |
| startAttempt | Siswa tenant sama, participant eligible, exam start/resume rule | Tidak berubah | Tidak ada; role SISWA | Tenant, participant.user_id, eligible_from/until, resumable attempt | Admin/Guru/Proktor deny; Siswa own participant allow | Shared; jangan merusak start/resume |
| assignProctor | Admin + exam.proctor.assign, tenant sama, exam PUBLISHED+locked+end_at future | Tetap satu-satunya policy action Exam yang diizinkan bagi Admin | exam.proctor.assign | Tenant, status, locked_at, end_at future | Admin allow hanya assignment; Guru/Siswa/Proktor deny | Shared; assignment payload terpisah BLOCKED |
| revokeProctor | Admin + exam.proctor.assign, tenant sama | Tetap diizinkan hanya untuk revoke assignment | exam.proctor.assign | Tenant + exam assignment | Admin allow; Guru/Siswa/Proktor deny | Shared |
| monitor | Proktor active assignment; Admin/OPERATOR/KAMAD exam.monitor; Guru exam.monitor + TeacherAccess | Admin deny; Guru/Proktor W0 tetap | exam.monitor untuk Guru/Admin; active assignment untuk Proktor | Tenant; Guru canViewExam; Proktor activeNow + exam_id + proctor_user_id | Admin deny; Guru scope; Siswa deny; Proktor active assignment allow | Shared; deny Admin sebelum permission branch, jangan ubah Guru/Proktor |
| viewExamResults | Delegasi ReportPolicy | Admin deny; Guru tetap | report.view via ReportPolicy | Tenant + TeacherAccess | Admin deny; Guru allow; Siswa/Proktor deny | Shared |
| publishResults | Guru + exam.grade, assessment creator, TeacherAccess; Admin explicit deny | Tidak berubah | exam.grade | Tenant, creator, TeacherAccess | Admin/Siswa/Proktor deny; Guru allow | Shared; pertahankan Guru |

### 2.4 ExamAttemptPolicy

| Method | Akses W0 saat ini | Target WA-01 | Permission | Tenant / ownership / scope | Admin / Guru / Siswa / Proktor | Shared / deny Admin |
|---|---|---|---|---|---|---|
| view | Guru report.view + TeacherAccess; Admin/KAMAD report.view | Admin deny; Guru tetap | report.view | Tenant; Guru canViewExam | Admin deny; Guru allow; Siswa/Proktor deny | Shared; Admin guard sebelum role branch |
| viewScore | Guru report.view+TeacherAccess; Admin/KAMAD report.view; selain itu Student own participant + published final current score | Admin deny sebelum fallback Siswa; Guru dan Siswa self DTO tetap | report.view untuk Guru/Admin | Tenant; participant.user_id; show_result; SUBMITTED/EXPIRED; current score pointer tenant/attempt/finalized | Admin deny termasuk Admin+Siswa; Guru scope; Siswa own finalized score; Proktor deny | Shared; Admin check harus sebelum Student fallback |
| score | Guru exam.grade + TeacherAccess | Tidak berubah | exam.grade | Tenant, exam+assessment, TeacherAccess | Admin/Siswa/Proktor deny; Guru allow | Shared |
| grade | Delegasi score | Tidak berubah | exam.grade | Sama dengan score | Admin/Siswa/Proktor deny; Guru allow | Shared |
| finalize | Admin exam.grade; Guru exam.grade + TeacherAccess | Admin deny; Guru tetap | exam.grade | Tenant; Guru assessment/exam scope | Admin deny; Guru allow; Siswa/Proktor deny | Shared; Admin guard sebelum grant branch |
| reopen | Admin exam.reopen tenant-local | Admin deny; tidak dialihkan ke Guru | exam.reopen saat ini | Tenant equality | Admin/Guru/Siswa/Proktor deny | Shared; tidak memberikan reopen ke Guru |

### 2.5 ReportPolicy

| Method | Akses W0 saat ini | Target WA-01 | Permission | Tenant / ownership / scope | Admin / Guru / Siswa / Proktor | Shared / deny Admin |
|---|---|---|---|---|---|---|
| viewExamResults | Guru report.view + TeacherAccess; Admin/KAMAD report.view | Admin deny; Guru tetap | report.view | Tenant + TeacherAccess Guru | Admin deny; Guru allow; Siswa/Proktor deny | Shared; Admin guard sebelum Guru/legacy |
| viewStudentResults | Tenant sama; self ID langsung allow; Guru report.view + profileIds; Admin/KAMAD report.view | Admin deny sebelum self fallback; Guru dan Siswa self tetap | report.view untuk Guru/Admin | Tenant; Guru profile+filters; Siswa self only | Admin deny termasuk Admin+Siswa; Guru scope; Siswa own; Proktor deny | Shared; Admin guard sebelum self equality |
| viewSubjectResults | Admin/KAMAD report.view; Guru deny karena aggregate tenant-wide | Admin deny; Guru tetap deny sampai scope agregat dirancang | report.view untuk Admin/KAMAD saat ini | Tenant; no safe Guru subject aggregate | Admin deny; Guru/Siswa/Proktor deny | Shared; jangan buka agregat ke Guru |

## 3. Matriks 12 API controller — semua method

### 3.1 QuestionController

| Method | Akses W0 / pemeriksaan | Target Admin | Guru / Siswa / Proktor | Tenant / boundary |
|---|---|---|---|---|
| __construct | AuditService injection | — | — | Tidak mengakses data |
| index | QuestionPolicy.viewAny; eager-load currentVersion.options, CP/TP, tags; Admin query MADRASAH; Guru personal/subject; shared publication approved | Deny sebelum query/eager-load | Guru tetap; Siswa/Proktor default deny sesuai permission | Tenant filter pada branch; shared publication tenant/status/scope |
| show | QuestionPolicy.view; JSON load currentVersion/options/CP/TP/tags | Deny sebelum load | Guru owner/subject scope; Siswa/Proktor tidak diperluas | Policy tenant + owner/TeacherAccess/publication |
| store | QuestionPolicy.create; tenant ID user; exists curriculum/subject/CP/TP tenant | Deny sebelum validation/write | Guru create; Siswa/Proktor deny | Tenant-bound references + subject/CP/TP relation |
| update | QuestionPolicy.update; lock row lalu reauthorize; update version/options | Deny sebelum validation/query/write | Guru creator/subject assignment | Tenant equality; locked row; CP/TP tenant |
| publish | QuestionPolicy.publish; reauthorize locked question; status/version + audit | Deny sebelum lock/mutation | Guru publish tetap | Tenant via policy/locked model |
| import | QuestionPolicy.create; importer | Deny sebelum parse/import | Guru sesuai create; Siswa/Proktor deny | Tenant + create policy |
| destroy | QuestionPolicy.update; archive/soft-delete | Deny sebelum lock/archive | Guru sesuai update | Tenant + ownership/subject |
| versions | QuestionPolicy.view; eager-load options/paginate | Deny sebelum query | Guru sesuai view | Tenant via policy |
| validateOptionIntegrity (private) | Validasi opsi | N/A | Internal helper | Tidak punya akses mandiri |
| saveOptions (private) | Simpan opsi dalam transaction | N/A | Internal helper | Harus berada di alur authorized |

### 3.2 QuestionPublicationController

| Method / route | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / catatan |
|---|---|---|---|---|
| __construct | AuditService injection | — | — | — |
| index | QuestionPolicy.viewAny; tenant query; eager question.currentVersion | Deny sebelum query | Guru tetap; Siswa/Proktor default deny | Tenant + nested question |
| store | Validasi question tenant; QuestionPolicy.update; create publication | Deny sebelum lookup/write | Guru sesuai update; Siswa/Proktor deny | Tenant question |
| destroy | Resolve publication tenant; load question; QuestionPolicy.update; delete | Deny sebelum relation/mutation | Guru sesuai update | Publication + question tenant |
| show | Route dibangkitkan apiResource, method tidak ada di controller W0 | Route gap; jangan klaim 403 terbukti | Belum ada method behavior | BLOCKED sampai routing diputuskan |
| update | Route dibangkitkan apiResource, method tidak ada di controller W0 | Route gap; jangan klaim 403 terbukti | Belum ada method behavior | BLOCKED sampai routing diputuskan |

### 3.3 MediaController

| Method | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / ownership |
|---|---|---|---|---|
| __construct | AuditService | — | — | — |
| index | Admin/Guru; tenant query; Guru uploaded_by sendiri; Admin owner_type QUESTION; storage_path hidden | Admin deny QUESTION list | Guru own uploads; Siswa/Proktor deny sesuai role | Tenant + uploaded_by/owner_type |
| store | USER owner self; QUESTION Admin/Guru + Gate update; upload to private storage | Admin deny QUESTION sebelum storage; USER self tetap bila digunakan | Guru QUESTION; Siswa USER self; Proktor USER self sesuai W0 | Tenant + owner self/question policy |
| attachToQuestionVersion | Admin/Guru; transaction Question→Version→Media lock; Gate update; DRAFT | Admin deny sebelum transaction | Guru tetap; Siswa/Proktor deny | Tenant + question/version/media same owner |
| detachFromQuestionVersion | Admin/Guru; Gate update; transaction lock/pivot delete | Admin deny sebelum transaction | Guru tetap; Siswa/Proktor deny | Tenant + draft version |
| show | USER owner+uploaded_by; QUESTION Admin/Guru via QuestionPolicy.view atau Siswa own active attempt; safe private path | Admin deny QUESTION; USER self tetap | Guru scope; Siswa own active attempt; Proktor deny | Tenant + owner/attempt/device time + safe path |
| destroy | USER owner self; QUESTION Gate update; Guru uploaded_by self; Admin currently permitted | Admin deny QUESTION before delete/reference checks | Guru own upload; Siswa/Proktor USER self only | Tenant + question owner + upload owner + snapshot refs |
| isSafeTenantMediaPath (private) | Strict private tenant path | N/A | N/A | tenant path, traversal prevention |
| isReferencedByOwnActiveAttempt (private) | Snapshot→exam→attempt→participant joins | N/A | Siswa own active attempt | Tenant joins + user_id + active window |

### 3.4 AssessmentController

| Method | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / ownership |
|---|---|---|---|---|
| __construct | AuditService | — | — | — |
| index | AssessmentPolicy.viewAny; Guru TeacherAccess SQL; Siswa participant filter; paginate | Deny before query | Guru assigned subject/year/semester; Siswa own exam assessment; Proktor deny | Assignment join tenant/subject/year/semester/status/date; Siswa participant user_id |
| store | AssessmentPolicy.create; tenant-bound exists; Guru canCreateAssessment | Deny before validation/write | Guru only | Tenant IDs and semester/year consistency |
| show | AssessmentPolicy.view; load exams | Deny before relation load | Guru scope; Siswa own participant; Proktor deny | Tenant + participant/TeacherAccess |
| update | AssessmentPolicy.update; creator and TeacherAccess | Deny before validation/write | Guru creator+scope | Tenant + creator + assignment |
| destroy | AssessmentPolicy.delete; no exams; audit/delete | Deny before count/write | Guru creator+scope; Siswa/Proktor deny | Tenant + creator + exam count |

### 3.5 ExamController

| Method | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / scope |
|---|---|---|---|---|
| __construct | Dependency injection | — | — | — |
| index | ExamPolicy.viewAny; eager assessment; Siswa participant filter; Guru TeacherAccess constrain | Deny before query | Guru scoped; Siswa own participant; Proktor no general list | Tenant/model scope + role filters |
| store | ExamPolicy.create; assessment tenant validation; Guru TeacherAccess | Deny | Guru only | Tenant assessment + creator |
| show | ExamPolicy.view; load assessment; Siswa loads snapshots and own latest attempt | Deny before relation/snapshot query | Guru W0; Siswa own execution | Tenant + participant |
| update | ExamPolicy.update; schedule/config update | Admin already denied by Guru-only policy | Guru W0 | Tenant + creator + unlocked + TeacherAccess; **CROSS-SCOPE BLOCKED**, no schedule changes in WA-01 |
| addQuestions | manageQuestions; tenant-bound question versions; snapshot service | Deny before service | Guru assigned exam/question; others deny | Tenant + question version + locked reauthorization |
| assignRombel | manageParticipants; rombel tenant; Guru assignment; participants | Deny before participant query/write | Guru assigned rombel | Tenant + assessment + rombel + student |
| generateToken | generateToken policy; locked row/token | Deny before lock/token | Guru published+locked+owner+scope | Tenant + creator + TeacherAccess |
| lock | lock policy; snapshot check; publish/lock/token | Deny before snapshot read/mutation | Guru W0 | Tenant + creator + snapshots |
| removeQuestion | manageQuestions; tenant exam/snapshot lock | Deny before lock/delete | Guru scope | Tenant + snapshot belongs to exam |
| reorderQuestions | manageQuestions; tenant exam/snapshots | Deny before query/update | Guru scope | Tenant + snapshot belongs to exam |
| bulkRemoveQuestions | manageQuestions; tenant + snapshot history checks | Deny before query/mutation | Guru scope | Tenant + exam/snapshot history |
| examHasSnapshotHistory (private) | Snapshot/history helper | N/A | Internal | Tenant + exam IDs |
| preview | ExamPolicy.preview; snapshot question content | Deny before read/serialize | Guru creator + exam.manage + question.manage + scope | Tenant + creator + assignment |
| close | ExamPolicy.close; current Admin exam.close or Guru branch | Deny before lock/status mutation | Guru W0 | Tenant + locked + status; Guru creator/scope |
| archive | Public lifecycle method; exact route reachability and policy require recheck | Deny | Guru only if existing authorized path | Tenant/ownership must be verified; not listed in API route inventory |
| publishResults | ExamPolicy.publishResults; Guru exam.grade + creator/scope; Admin explicit deny | Deny | Guru W0 | Tenant + assessment creator + TeacherAccess |
| destroy | ExamPolicy.delete | Deny | Guru policy only | Tenant + creator + unlocked + TeacherAccess |

### 3.6 ExamParticipantController

| Method | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / scope |
|---|---|---|---|---|
| __construct | AuditService | — | — | — |
| index | ExamPolicy.monitor; returns participants with user relation + paginate | Deny before roster query/eager-load | Guru monitor scope; Proktor active assignment; Siswa deny | Tenant/exam/assignment |
| store | manageParticipants; student tenant + SISWA role; Guru rombel checks | Deny before validation/lookup | Guru manage participants; Siswa/Proktor deny | Tenant + SISWA + TeacherAccess |
| destroy | manageParticipants; lock exam/participant and remove | Deny before lock/delete | Guru scope; Siswa/Proktor deny | Tenant + participant belongs to exam |

### 3.7 ExamAttemptController

| Method | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / ownership |
|---|---|---|---|---|
| __construct | AuditService | — | — | — |
| store | startAttempt policy; participant/user/tenant; creates/resumes attempt | Deny before participant lookup | Siswa own eligible participant; Guru/Proktor deny | Tenant + participant.user_id + eligibility + attempt status/expiry |
| execution | Exam view/start rules; attempt and snapshot options | Deny before attempt/snapshot query | Siswa own execution; Guru/Proktor no student execution | Tenant + user + participant + exam state |
| submit | Exam view; participant ownership; submit transition | Deny before lock/update | Siswa own attempt; Guru/Proktor deny | Tenant + exam + attempt + participant |
| show | Student self branch; otherwise view + monitor | Deny before self/monitor fallback | Siswa own attempt; Guru/Proktor only W0 authorized monitor branch | Tenant + participant ownership |
| attemptResponseData (private) | Builds DTO | N/A | Internal | Must receive authorized attempt |

### 3.8 ExamAnswerController

| Method | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / ownership |
|---|---|---|---|---|
| __construct | AuditService | — | — | — |
| index | Query answer rows scoped by tenant/exam/attempt | Explicit Admin deny before owner/query | Siswa own attempt; Guru/Proktor deny unless W0 route explicitly allows | Tenant + exam + attempt + participant.user_id + device lease |
| update | Validates payload; locks attempt/snapshot/answer; revision/idempotency | Explicit Admin deny before validation/lock/write | Siswa own attempt; Guru/Proktor deny | Tenant + snapshot membership + owner/device/revision/idempotency |
| validatePayload (protected) | Answer schema validation | N/A | Internal | Not authorization boundary |

### 3.9 MonitoringController

| Method | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / ownership |
|---|---|---|---|---|
| summary | monitor policy; attempt aggregates | Deny before count queries | Guru scope; Proktor active assignment; Siswa deny | Tenant/exam |
| participants | monitor; eager user/attempt/answer stats; paginate | Deny before eager-load/aggregation | Guru scope; Proktor active assignment | Tenant/exam/participant |
| attempt | monitor; attempt/answer stats | Deny before attempt query | Guru scope; Proktor active assignment | Tenant + attempt belongs exam |
| events | monitor; AuditLog and attempt events | Deny before AuditLog query | Guru scope; Proktor active assignment | Tenant + entity/exam/attempt |
| heartbeat | Student operational heartbeat path; preserve lease/fencing/ownership | Admin deny before student/proctor fallback | Siswa own attempt/device; do not expand Guru/Proktor | Tenant + exam + attempt + participant + device lease |

### 3.10 ScoringController

| Method | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / scope |
|---|---|---|---|---|
| __construct | Scoring/grade services | — | — | — |
| score | ExamAttemptPolicy.score; scoring service | Deny before service/transaction | Guru exam.grade + TeacherAccess | Tenant + assessment/exam |
| show | viewScore; current pointer and question scores; Student branch | Deny before Student fallback and query | Guru scope; Siswa own finalized current score DTO only; Proktor deny | Tenant + attempt + participant + pointer |
| updateManualScore | grade policy; locks and writes marks | Deny before validation/lock/write | Guru grading scope | Tenant + question score belongs attempt |
| finalize | finalize policy; current Admin exam.grade or Guru scope | Deny | Guru finalize W0 | Tenant + assessment/exam |
| recalculate | score policy; recalculation/current pointer | Deny before service | Guru exam.grade scope | Tenant + attempt + score pointer |
| reopen | reopen policy; Admin exam.reopen | Deny before status mutation | Guru/Siswa/Proktor deny | Tenant equality |

### 3.11 ReportController

| Method | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / scope |
|---|---|---|---|---|
| __construct | Report/export services | — | — | — |
| examResults | viewExamResults; Guru optional rombel assignment | Deny before result query | Guru scope | Tenant + TeacherAccess rombel |
| examSummary | viewExamResults | Deny before aggregate | Guru scope | Tenant + TeacherAccess |
| studentResults | viewStudentResults; Guru student scope; Student self DTO branch | Deny before self branch/query | Guru scope; Siswa own only; Proktor deny | Tenant + allowed exam/rombel |
| exportExcel | viewExamResults then Excel download | Deny before Export construction | Guru scope | Tenant + report scope |
| exportPdf | viewExamResults then Pdf generation | Deny before Pdf::loadView | Guru scope | Tenant + report scope |
| exportStudentPdf | viewStudentResults; Guru scope; Student self | Deny before self branch/PDF | Guru scope; Siswa own only | Tenant + student + filters |
| exportSummaryPdf | viewExamResults then PDF | Deny before generation | Guru scope | Tenant + report scope |
| rombelReport | viewExamResults; Guru rombel assignment | Deny before query | Guru assigned rombel | Tenant + exam + rombel |
| subjectReport | viewSubjectResults; current Admin/KAMAD allowed, Guru blocked | Deny before query | Guru/Siswa/Proktor remain deny | Tenant + subject |
| questionReport | viewExamResults | Deny before query | Guru scope | Tenant + exam |

### 3.12 AnalyticsController

| Method | W0 | Target Admin | Guru / Siswa / Proktor | Tenant / scope |
|---|---|---|---|---|
| __construct | Analytics services/cache | — | — | — |
| examSummary | viewExamResults then Cache::remember/service | Deny before cache read | Guru scope | Tenant/exam |
| examDistribution | viewExamResults then cache/service | Deny before cache | Guru scope | Tenant/exam |
| examItems | viewExamResults then cache/service | Deny before cache | Guru scope | Tenant/exam |
| rombelExamAnalytics | viewExamResults; Guru rombel assignment; cache/service | Deny before cache | Guru assigned rombel | Tenant + exam + rombel |
| studentAnalytics | viewStudentResults; Guru student scope; cache/service | Deny before self fallback/cache | Guru scope; Student self only if W0 allows | Tenant + user + assignment |
| subjectAnalytics | viewSubjectResults; current Admin/KAMAD only | Deny before cache/service | Guru/Siswa/Proktor deny as W0 | Tenant + subject |

## 4. Livewire shared component — lifecycle dan action

### 4.1 QuestionBank.php

**Fakta source W0:** mount menerima ADMIN_MADRASAH atau GURU dengan question.view. render eager-load subject, currentVersion.options, learningOutcome/currentVersion, learningObjective; Admin branch mengambil soal MADRASAH. Public state mencakup stem, explanation/rubric, points, option text, correctOption/correctOptions, matching pairs, IDs edit/delete dan filters. State ini sensitif dan wajib diperlakukan sebagai payload Livewire, bukan sekadar field UI.

| Surface / method | W0 dan risiko | Target Admin | Guru preservation | Syarat |
|---|---|---|---|---|
| mount | Admin/Guru + question.view | Admin deny | Guru tetap | Role GURU + permission + tenant |
| boot | Tidak dideklarasikan | Harus menolak Admin tiap request sebelum hydration/action | Guru tetap | Lifecycle order Livewire harus dibuktikan runtime |
| hydrate/dehydrate | Tidak ada hook custom; public properties didehydrate | Snapshot Admin lama harus ditolak | Guru state contract tetap | Uji stale snapshot dan serialized state |
| render | Tidak ada auth; Admin query MADRASAH; eager-load opsi/CP/TP | Deny sebelum query/view | Query, relations, pagination, view Guru tetap W0 | Tenant + assigned subject |
| pagination | WithPagination, paginate 12; filters reset page | Admin deny sebelum render/query | Guru unchanged | Direct page/snapshot test |
| openCreateModal | Gate create (Guru policy) | Admin deny | Guru unchanged | QuestionPolicy.create |
| openEditModal | Load question/currentVersion/options sebelum Gate update | Admin deny sebelum sensitive load | Guru unchanged | Tenant + QuestionPolicy.update + owner/scope |
| addMatchingPair | State mutation tanpa auth | Admin deny | Guru unchanged | Per-request guard/action guard |
| removeMatchingPair | State mutation tanpa auth | Admin deny | Guru unchanged | Per-request guard/action guard |
| updatedSelectedSubjectId | Clear CP/TP tanpa auth | Admin deny | Guru unchanged | Per-request guard |
| updatedSelectedOutcomeId | Clear TP tanpa auth | Admin deny | Guru unchanged | Per-request guard |
| updatedQuestionType | Mutasi option/key state tanpa auth | Admin deny | Guru unchanged | Per-request guard |
| createQuestion | Delegasi saveQuestion | Admin deny | Guru unchanged | Tidak boleh bypass saveQuestion auth |
| saveQuestion | Validasi dulu, lalu Gate create/update + authorizeTeacherQuestionAccess; transaction | Admin deny sebelum validation/query | Guru authoring/validation/versioning/audit unchanged | Guru + question.manage + subject assignment + tenant + CP/TP |
| publishQuestion | Load question, Gate publish, lock, update status/version | Admin deny sebelum query/lock | Guru unchanged | QuestionPolicy.publish + TeacherAccess |
| confirmDeleteQuestion | Load question, Gate update, set delete state | Admin deny sebelum query | Guru unchanged | QuestionPolicy.update + TeacherAccess |
| deleteQuestion | Load/Gate/lock/archive | Admin deny sebelum query/lock | Guru archive unchanged | QuestionPolicy.update + TeacherAccess |
| updatingSearch | resetPage only | Admin deny | Guru unchanged | Per-request guard |
| updatingSubjectFilter | resetPage only | Admin deny | Guru unchanged | Per-request guard |
| updatingStatusFilter | resetPage only | Admin deny | Guru unchanged | Per-request guard |
| authorizeTeacherQuestionAccess (private) | Requires GURU + question.manage + TeacherAccess | Admin deny | Guru unchanged | Helper bukan lifecycle guard |
| event listeners | Tidak ada deklarasi di class | Tidak ada Admin listener entry | Guru no contract change | Uji inherited/global listener |
| computed properties | Tidak ada deklarasi | Tidak diekspos | Tidak berubah | Konfirmasi framework behavior |
| query/eager relations | render + openEdit eager-load question/options/CP/TP | Tidak boleh dijalankan untuk Admin | Guru relation graph unchanged | Deny sebelum query |

**BLOCKED.** Component shared; stale snapshot/hydration ordering belum diuji; public state menyimpan answer key; render W0 memberi content kepada Admin. Opsi desain:
1. Guard ketat component bersama: hanya GURU; hook tiap request + render/action checks; buktikan boot/authorization berjalan sebelum hydration dan action. Guru tetap memakai component/view/route sama.
2. Pisahkan component/route Guru khusus; Admin tidak memakai QuestionBank. Lebih kuat tetapi menambah file/route dan perlu approval lintas-scope.
3. Bila opsi 1 tidak terbukti runtime, pilih opsi 2 atau keluarkan dari patch dan tetap BLOCKED.

### 4.2 ExamManagement.php

**Fakta source W0:** mount menerima Admin/Guru. Public state menyimpan title, instructions, subject/semester/rombel, confirmAllStudents, duration, attemptLimit, requireToken, shuffle flags, startAt/endAt dan selectedQuestionVersionIds. render eager-load assessment.subject, participants, attempts, snapshots; query Guru dipersempit TeacherAccess, Admin branch tidak memiliki filter role khusus. View menampilkan token, participant/attempt counts, monitoring/results/analytics links dan konfigurasi.

| Surface / method | W0 dan risiko | Target Admin | Guru preservation | Syarat |
|---|---|---|---|---|
| mount | Admin/Guru accepted | Admin deny | Guru unchanged | GURU + tenant |
| boot | Tidak dideklarasikan | Deny Admin tiap request sebelum hydration/action | Guru accepted | Lifecycle order harus dibuktikan |
| hydrate/dehydrate | Public state berisi exam config dan question version IDs | Stale Admin snapshot ditolak | Guru unchanged | Test old/altered/replayed snapshot |
| render | Tidak ada auth; eager-load assessment/participants/attempts/snapshots | Deny sebelum query | Guru TeacherAccess query dan view tetap | Tenant + TeacherAccess |
| pagination | WithPagination; page update memanggil render | Admin deny sebelum render | Guru unchanged | Direct page request |
| openCreateModal | Gate create Exam (Guru-only) | Admin deny | Guru unchanged | ExamPolicy.create |
| openEditModal | Query assessment/snapshots sebelum Gate update | Admin deny sebelum query | Guru unchanged | Tenant + ExamPolicy.update |
| toggleQuestionSelection | Mutasi state tanpa Gate | Admin deny | Guru unchanged | Per-request/action guard |
| createExam | Delegasi saveExam | Admin deny | Guru unchanged | Same as saveExam |
| saveExam | Transaction create/update assessment/exam, schedule fields, snapshots, participants, audit; Guru scope validation | Admin deny sebelum validation/query | Guru save behavior unchanged | **CROSS-SCOPE BLOCKED**; schedule-save Guru tidak boleh diubah |
| generateToken | Gate generateToken; token mutation/flash | Admin deny | Guru unchanged | ExamPolicy.generateToken |
| lockExam | Gate lock; snapshot validation; publish/lock/token | Admin deny | Guru unchanged | ExamPolicy.lock |
| updatingSearch | resetPage | Admin deny | Guru unchanged | Per-request guard |
| updatingStatusFilter | resetPage | Admin deny | Guru unchanged | Per-request guard |
| event listeners | Tidak ada deklarasi | No Admin listener | Guru unchanged | Uji inherited listener |
| computed properties | Tidak ada deklarasi | None | None | Confirm framework |
| query/eager relations | Exam assessment.subject, participants, attempts, snapshots; subjects; rombels.gradeLevel; Question.currentVersion | Tidak boleh dijalankan untuk Admin | Guru query/relations unchanged | Deny sebelum query |

**BLOCKED.** saveExam merupakan jalur bersama yang memuat schedule, snapshot dan participant lifecycle Guru. Guard tambahan mungkin dapat diisolasi, tetapi stale snapshot, boot-before-hydrate, direct action dan render belum dibuktikan. Opsi: guard ketat dengan bukti runtime tanpa mengubah saveExam, atau pisahkan component/route dengan approval lintas-scope. Jangan mengubah saveExam, schedule, publish, token, lock atau lifecycle Guru.

### 4.3 Aturan Livewire umum

- Tombol tersembunyi bukan authorization.
- Snapshot Admin lama harus ditolak; jangan menganggap mount dipanggil ulang pada setiap request.
- Uji direct Livewire request untuk setiap public method, state-only action, updater, pagination dan event.
- Capture request payload, response JSON, snapshot, effects, HTML, validation errors, exception, pagination metadata dan event payload.
- Denial harus terjadi sebelum query sensitif, cache, service, storage, export atau side effect.
- Tidak boleh mengubah Teacher/* atau view Guru.

## 5. Route web relevan — seluruh route yang ditemukan

| Route | Middleware W0 | Handler | Akses W0 | Target Admin | Guru / Siswa / Proktor | Catatan |
|---|---|---|---|---|---|---|
| GET /exams/{exam}/monitoring | Dideklarasikan sebelum auth/tenant/license group; snippet memanggil cannot(monitor) | Closure monitoring dashboard | Policy monitor mengizinkan Admin exam.monitor, Guru scope, Proktor active assignment | Admin deny | Guru scope; Proktor assignment; Siswa deny | **BLOCKED:** verifikasi global middleware dan unauthenticated; deklarasi route tidak menyebut auth/tenant/license |
| GET /admin/questions | auth, tenant, license | QuestionBank | mount Admin/Guru + question.view | Admin deny | Guru unchanged; Siswa/Proktor deny | Shared component BLOCKED |
| GET /admin/exams | auth, tenant, license | ExamManagement | mount Admin/Guru | Admin deny | Guru unchanged; Siswa/Proktor deny | Shared component BLOCKED; schedule-save cross-scope |
| GET /admin/proctor-assignments | auth, tenant, license | ProctorAssignmentManagement | mount Admin + exam.proctor.assign | Admin only assignment metadata | Guru/Siswa/Proktor deny | Payload allowlist BLOCKED |
| GET /exams/{exam}/results | auth, tenant, license group | Teacher ResultList | mount viewExamResults; actions authorize | Admin deny via ReportPolicy/ExamPolicy | Guru scope | Teacher component read-only audit surface; no edits |
| GET /exams/{exam}/results/{attempt} | auth, tenant, license group | Teacher ResultDetail | mount ExamAttemptPolicy.view; actions grade/finalize/reopen | Admin deny via policy | Guru W0; Siswa/Proktor deny | Reopen Admin current policy grant must be removed; no Guru reopen grant |
| GET /exams/{exam}/export/excel | auth, tenant, license group | ReportController.exportExcel | viewExamResults before Excel | Admin deny before Export creation | Guru scoped | Authorization-before-export |
| GET /exams/{exam}/export/pdf | auth, tenant, license group | ReportController.exportPdf | viewExamResults before PDF | Admin deny before PDF | Guru scoped | Authorization-before-file |
| GET /students/{student}/export/pdf | auth, tenant, license group | ReportController.exportStudentPdf | viewStudentResults; Student self branch | Admin deny before self fallback/PDF | Guru scoped; Siswa own | Multi-role test |
| GET /exams/{exam}/analytics | auth, tenant, license group | Teacher AnalyticsDashboard | mount viewExamResults | Admin deny | Guru scope | No Teacher edits |
| GET /exams/{exam}/analytics/items | auth, tenant, license group | Teacher ItemAnalysis | mount viewExamResults | Admin deny | Guru scope | No Teacher edits |
| GET /student/exams | auth, tenant, license group | StudentExamController.index | Student portal | Admin deny | Siswa | Preserve |
| GET /student/exams/{exam} | auth, tenant, license group | StudentExamController.show | Student portal | Admin deny | Siswa own eligible exam | Preserve |
| GET /student/results/{attempt} | auth, tenant, license group | StudentExamController.result | Student result DTO | Admin deny including multi-role | Siswa own published result | Preserve DTO |

Route grouping: /admin/questions, /admin/exams, /admin/proctor-assignments, results, exports dan analytics berada dalam auth/tenant/license group. Tidak ada role middleware eksplisit pada route declaration; authorization component/policy sangat penting. Monitoring closure berada di luar group tersebut dalam file dan memerlukan verifikasi middleware stack.

## 6. Metadata assignment — audit dan allowlist verdict

### 6.1 Source W0 dan file dilindungi

- app/Livewire/Admin/ProctorAssignmentManagement.php — blob 4e8bd91dd54757aa863d35983aace718def2ca2c
- resources/views/livewire/admin/proctor-assignment-management.blade.php — blob e5e91928c8779be8651fef106911f215a3818a64
- app/Services/ExamProctorAssignmentService.php — blob e16ef5db308714aa98bf283dc5a1545608da0477
- app/Models/ExamProctorAssignment.php — blob dde7511ebb5035a76287d34dc75339a6a2bffdd8
- app/Models/Exam.php — blob d3094eada3b2f54758af89e82987f09b4672f8d9

Semua file tersebut tetap protected dan tidak boleh berubah pada WA-01.

### 6.2 Bukti query, model, relasi, Blade

| Surface | Source W0 | Hasil audit | Verdict |
|---|---|---|---|
| Exam dropdown | Explicit select id,title,start_at,end_at; tenant + PUBLISHED + locked + end_at future | Sesuai minimum | PASS statis untuk query ini saja |
| Proctor dropdown | Explicit select id,name,email; tenant, not deleted, role PROKTOR | Email diambil dan ditampilkan padahal allowlist hanya perlu nama | FAIL |
| Assignment base query | Tenant query + eager relations, tanpa select base columns | Semua kolom assignment dapat diambil: id, tenant_id, exam_id, proctor_user_id, assigned_by, starts_at, expires_at, status, assignment_reason, revoked_at, revoked_by, revoke_reason, timestamps | FAIL query minimization |
| Exam eager relation | Select id,title,status,start_at,end_at | Exam status tambahan di luar allowlist minimum | FAIL ketat |
| Proctor eager relation | Select id,name,email | Email tambahan | FAIL |
| Assigner/revoker | Select id,name | Nama aktor mungkin diperlukan untuk audit; perlu allowlist eksplisit | CONDITIONAL |
| Blade exam | Menampilkan title | Tidak menampilkan token/soal/snapshot/participant | PASS tampilan yang diperiksa |
| Blade Proktor | Menampilkan name dan email | Email terlihat pada HTML | FAIL |
| Blade history | Menampilkan exam title, proctor name, starts/expires, assignment status, ID untuk revoke | Tidak tampak token/soal/snapshot/participant/attempt/answer/score/result/analytics | CONDITIONAL |
| Assignment model | Fillable mencakup assignment_reason/revoke_reason; relasi exam/proctor/assigner/revoker | Relasi dapat mengambil semua kolom bila load tanpa select | FAIL sebagai bukti allowlist end-to-end |
| Assignment service | fresh()->load exam/proctor/assigner dan revoke load revoker | Mengembalikan model dengan relation graph penuh; component tidak mengembalikan object ini dari action, tetapi tidak ada response capture | BLOCKED runtime |
| Pagination | Tidak menggunakan paginator; history limit 100 | Tidak ada paginator statis; response tetap harus direkam | PASS statis hanya untuk tidak adanya paginator |
| Validation/request | Public state selectedExamId, selectedProctorId, startsAt, expiresAt, reason, revokeReason | Request membawa IDs/reason; perlu audit response errors/effects | BLOCKED runtime |
| Snapshot/response | Collections adalah local render vars, bukan public props; HTML menampilkan subset | Tidak ada capture snapshot/HTML/JSON aktual | BLOCKED runtime |
| Render authorization | mount dan assign/revoke assertCanManage; updatedSelectedExamId dan render tidak memanggilnya | Stale snapshot/direct render belum terbukti fail-closed | FAIL desain saat ini |
| Hidden relation/payload | Blade hanya memakai subset, query mengambil lebih banyak | Tidak dapat menyimpulkan payload aman hanya dari DOM terlihat | BLOCKED |

### 6.3 Allowlist target

- Exam: id, title, start_at, end_at.
- Assignment history: id, status, starts_at, expires_at, nama Proktor/aktor yang secara eksplisit disetujui.
- Proctor dropdown: id, name saja; email tidak diperlukan.
- Dilarang: token, require_token, instructions, subject/question content, snapshot, roster/participant identity, attempt/device/lease, answer/revision, score/current pointer, result/report/export, monitoring event, analytics, assignment_reason/revoke_reason kecuali disetujui eksplisit sebagai metadata yang tampil.
- Query dan relasi harus select hanya field allowlist. Jangan mengandalkan makeHidden atau Blade omission sebagai satu-satunya kontrol.

### 6.4 Keputusan

**BLOCKED:** allowlist browser belum dapat dibuktikan; email terlihat dan query mengambil kolom berlebih. Karena file assignment di atas dilindungi dan tidak boleh berubah pada WA-01, jangan menyatakan metadata assignment accepted.

Pilihan:
1. Buat component metadata baru + view baru + route dialihkan ke component baru, dengan DTO/select allowlist dan response capture; file assignment protected tetap untouched. Perlu approval perubahan target source/route.
2. Buat work package assignment metadata terpisah yang mengizinkan perubahan file protected; memerlukan approval scope terpisah.
3. Tanpa pilihan, WA-01 tidak dapat accepted karena metadata assignment belum terbukti allowlist.

## 7. Permission bootstrapper — kode dan DB dipisahkan

### 7.1 Source W0

Role grants ADMIN_MADRASAH saat ini berisi administrative: curriculum.view, curriculum.manage, academic.manage, user.manage; CBT/report: question.view, exam.manage, exam.assign, exam.monitor, exam.close, exam.token, exam.grade, exam.reopen, report.view; serta assignment exam.proctor.assign.

Revocation map W0 untuk ADMIN_MADRASAH hanya mencabut question.manage. Jadi bootstrapper W0 masih memberi grant CBT Admin dan harus direvisi sebagai perubahan kode setelah approval.

### 7.2 Daftar perubahan kode rencana (belum diterapkan)

| Tindakan | Permission | Alasan |
|---|---|---|
| Cabut | question.view, question.manage | Tidak boleh baca/authoring bank soal |
| Cabut | exam.manage, exam.assign, exam.monitor, exam.close, exam.token, exam.grade, exam.reopen | Tidak boleh authoring/participant/monitor/lifecycle/token/scoring |
| Cabut | report.view | Tidak boleh hasil/laporan/analytics/export |
| Pertahankan | exam.proctor.assign | Satu-satunya capability CBT Admin untuk assignment sementara |
| Pertahankan | curriculum.view, curriculum.manage, academic.manage, user.manage | Administrasi madrasah sah |

Jangan hapus role/user/user-role/history/audit. Revocation pivot tidak menggantikan explicit policy deny untuk stale permission.

### 7.3 Prosedur DB terpisah — TIDAK DIJALANKAN

1. Snapshot role_permission pivot lengkap sebelum tindakan, termasuk role ID/name, permission ID/name dan pivot metadata yang tersedia.
2. Snapshot role/permission catalog dan jumlah pengguna per role; simpan checksum dan lokasi backup aman.
3. Export daftar grant ADMIN_MADRASAH; identifikasi seluruh CBT grant yang dicabut dan administrative grant yang dipertahankan.
4. Uji bootstrapper pada clone disposable MySQL 8/InnoDB yang direstore dari snapshot; jangan gunakan produksi.
5. Jalankan bootstrapper dua kali pada clone; hasil kedua harus identik dengan hasil pertama.
6. Verifikasi CBT grants tidak ada; exam.proctor.assign dan administrative grants tetap; role/user/user-role/history tidak berubah.
7. Backup+restore drill dari snapshot; catat server version, InnoDB, row count, checksum dan reviewer.
8. Produksi memerlukan persetujuan DB terpisah, maintenance window, backup/restore proof, preflight dan postflight.
9. Jangan rollback dengan memulihkan CBT grants Admin. Pulihkan hanya administrative grant yang terbukti perlu; Admin CBT deny tetap.
10. Tidak ada bootstrapper run, seeder, pivot write atau DB write pada tahap persiapan.

## 8. Batas WA-01 dan WA-03

Acceptance WA-01 hanya menguji perilaku Guru yang telah tersedia pada W0: question authoring/publication/media; assessment/exam authoring; token/lock/publish; participants; monitoring; grading/finalize; report/export/analytics; serta Student execution/answer/submit/self-result sesuai W0.

Tidak termasuk:
- Schedule revision/update semantics atau state machine baru.
- Status Guru baru (account_status, employment_status) dan eligibility status.
- Capability Guru baru, assignment confirmation atau integrasi WA-03.
- Shared schedule service, ExamScheduleChangeService, ExamController schedule update, shared ExamManagement.saveExam, shared ExamPolicy capability.
- Teacher Livewire/view.
- Device lease/fencing/CBT Engine lifecycle.

ExamController.update dan ExamManagement.saveExam adalah CROSS-SCOPE BLOCKED untuk perubahan schedule. File tetap berada di daftar awal 23 target sebagai kandidat hunk Admin boundary saja; bila hunk tidak dapat dipisah tanpa memengaruhi Guru, file/component dikeluarkan dari patch dan memerlukan approval lintas-scope. Tidak ada perubahan daftar 23 source target pada revisi dokumentasi ini.

## 9. Test matrix final — semua NOT RUN

Setiap test wajib mencatat source commit aktual, file::method, PHP/MySQL version, InnoDB confirmation, command, exit code, assertion count, stdout/stderr, response/snapshot/export evidence dan reviewer. Tidak ada test di bawah yang sudah dijalankan.

### 9.1 Authorization/API negative

| ID | Test identity | Cakupan | Status |
|---|---|---|---|
| WA01-N-001 | admin_cannot_read_question_content_versions_or_keys | Question index/show/versions; nested options/keys/CP/TP | NOT RUN |
| WA01-N-002 | admin_cannot_import_publish_or_mutate_questions | store/update/import/publish/destroy | NOT RUN |
| WA01-N-003 | admin_cannot_read_or_mutate_question_publications | publication index/store/destroy; show/update route gap | NOT RUN |
| WA01-N-004 | admin_cannot_read_or_mutate_question_media | media QUESTION paths; USER self unchanged | NOT RUN |
| WA01-N-005 | admin_cannot_read_assessment_or_embedded_exam_relations | assessment index/show/store/update/destroy | NOT RUN |
| WA01-N-006 | admin_cannot_read_exam_index_detail_or_preview | exam index/show/preview and nested relations | NOT RUN |
| WA01-N-007 | admin_cannot_read_or_mutate_exam_snapshot_questions | add/remove/reorder/bulk remove | NOT RUN |
| WA01-N-008 | admin_cannot_read_or_rotate_exam_token | token endpoint and response/flash | NOT RUN |
| WA01-N-009 | admin_cannot_lock_close_archive_or_publish_exam | lock/close/archive/publishResults/destroy | NOT RUN |
| WA01-N-010 | admin_cannot_read_or_mutate_exam_roster | participant index/store/destroy and user relation | NOT RUN |
| WA01-N-011 | admin_cannot_read_attempts_execution_or_submit | attempt store/execution/show/submit | NOT RUN |
| WA01-N-012 | admin_cannot_read_or_write_exam_answers | answer index/update, ownership/device/revision/idempotency | NOT RUN |
| WA01-N-013 | admin_cannot_access_monitoring_or_heartbeat_operations | monitoring summary/participants/attempt/events/heartbeat | NOT RUN |
| WA01-N-014 | admin_cannot_score_recalculate_or_read_scores | score/show/recalculate/current pointer/question scores | NOT RUN |
| WA01-N-015 | admin_cannot_grade_finalize_or_reopen_scores | manual grading/finalize/reopen | NOT RUN |
| WA01-N-016 | admin_cannot_read_exam_student_subject_or_question_reports | all report reads | NOT RUN |
| WA01-N-017 | admin_cannot_generate_or_download_exam_student_exports | Excel/PDF; generator not called | NOT RUN |
| WA01-N-018 | admin_cannot_read_exam_item_rombel_student_subject_analytics | all analytics; prewarmed cache | NOT RUN |
| WA01-N-019 | admin_stale_permission_is_denied_on_every_policy_and_api_method | stale grants + cached role relation | NOT RUN |
| WA01-N-020 | admin_multirole_student_cannot_use_student_fallback | Admin+Siswa attempt/answer/score/self-result | NOT RUN |
| WA01-N-021 | admin_permission_revoked_after_session_is_denied | existing session/Sanctum token | NOT RUN |
| WA01-N-022 | admin_denial_precedes_sensitive_query_cache_service_and_serialization | query/service/cache spies and body | NOT RUN |
| WA01-N-023 | admin_export_denial_precedes_file_generation | fake Excel/PDF/storage | NOT RUN |
| WA01-N-024 | admin_route_access_denied_for_all_relevant_web_surfaces | admin/question/exam/results/monitor/export/analytics | NOT RUN |
| WA01-N-025 | question_publication_missing_api_resource_methods_are_not_silently_accepted | show/update absent method response | NOT RUN |

### 9.2 Livewire adversarial and regression

| ID | Test identity | Cakupan | Status |
|---|---|---|---|
| WA01-L-001 | admin_cannot_mount_question_bank | Admin initial GET | NOT RUN |
| WA01-L-002 | admin_old_question_bank_snapshot_is_rejected | stale snapshot with stem/options/key | NOT RUN |
| WA01-L-003 | admin_direct_question_bank_actions_are_denied | all public actions, state-only and save/publish/delete | NOT RUN |
| WA01-L-004 | admin_question_bank_pagination_and_filter_requests_are_denied | direct page/search/subject/status | NOT RUN |
| WA01-L-005 | admin_cannot_mount_exam_management | Admin initial GET | NOT RUN |
| WA01-L-006 | admin_old_exam_management_snapshot_is_rejected | config/token-adjacent state/question IDs | NOT RUN |
| WA01-L-007 | admin_direct_exam_management_actions_are_denied | open/edit/toggle/save/token/lock/filter | NOT RUN |
| WA01-L-008 | admin_livewire_event_listener_cannot_reveal_data | direct event + inherited listener discovery | NOT RUN |
| WA01-L-009 | admin_livewire_computed_property_and_relation_payload_is_empty | computed, eager relations, dehydrated state, paginator | NOT RUN |
| WA01-L-010 | guru_question_bank_mount_render_pagination_and_authoring_unchanged | positive Guru W0 | NOT RUN |
| WA01-L-011 | guru_exam_management_create_edit_snapshot_token_lock_unchanged | positive Guru W0, no schedule logic change | NOT RUN |
| WA01-L-012 | guru_direct_action_and_stale_snapshot_regression | Guru old snapshot/direct actions | NOT RUN |
| WA01-L-013 | admin_render_denial_occurs_before_sensitive_query | query spy for both components | NOT RUN |
| WA01-L-014 | livewire_validation_error_and_exception_payload_has_no_sensitive_data | errors/exceptions/effects/HTML | NOT RUN |

### 9.3 Assignment metadata allowlist

| ID | Test identity | Cakupan | Status |
|---|---|---|---|
| WA01-M-001 | assignment_exam_dropdown_contains_only_exam_allowlist | id/title/start/end in HTML/snapshot/query | NOT RUN |
| WA01-M-002 | assignment_proctor_dropdown_excludes_email_and_extra_fields | id/name only | NOT RUN |
| WA01-M-003 | assignment_history_query_selects_only_allowlisted_columns | no full base columns/reason fields | NOT RUN |
| WA01-M-004 | assignment_relations_select_only_allowlisted_fields | exam/proctor/assigner/revoker | NOT RUN |
| WA01-M-005 | assignment_response_snapshot_and_html_have_no_sensitive_nested_payload | denylist token/question/snapshot/roster/attempt/answer/score/result/analytics | NOT RUN |
| WA01-M-006 | assignment_validation_errors_and_request_payload_are_allowlisted | request/errors/effects/events | NOT RUN |
| WA01-M-007 | assignment_direct_render_and_stale_snapshot_are_denied_or_allowlisted | mount/render/updated/action replay | NOT RUN |
| WA01-M-008 | assignment_service_return_relations_are_not_serialized_to_browser | service object vs component response | NOT RUN |
| WA01-M-009 | assignment_history_limit_and_pagination_metadata_are_safe | limit 100 and response capture | NOT RUN |
| WA01-M-010 | assignment_page_admin_role_and_permission_are_rechecked_each_request | stale role/permission/direct Livewire | NOT RUN |

### 9.4 Role preservation, cache, tenant, permission

| ID | Test identity | Cakupan | Status |
|---|---|---|---|
| WA01-P-001 | guru_question_bank_authoring_and_question_api_remain_allowed | QuestionPolicy + QuestionController | NOT RUN |
| WA01-P-002 | guru_publication_and_question_media_workflow_remains_allowed | publication/media | NOT RUN |
| WA01-P-003 | guru_assessment_and_exam_authoring_remains_allowed | assessment/exam | NOT RUN |
| WA01-P-004 | guru_token_lock_and_exam_lifecycle_remain_allowed | token/lock/close/publish W0 | NOT RUN |
| WA01-P-005 | guru_participant_management_remains_assignment_scoped | participants/rombel | NOT RUN |
| WA01-P-006 | guru_monitoring_remains_assignment_scoped | monitoring API/web | NOT RUN |
| WA01-P-007 | guru_scoring_manual_grading_and_finalization_remain_allowed | scoring + Teacher ResultDetail | NOT RUN |
| WA01-P-008 | guru_reports_exports_and_analytics_remain_scope_limited | report/export/analytics | NOT RUN |
| WA01-P-009 | guru_livewire_question_bank_and_exam_management_w0_regression | all shared component actions/query scope | NOT RUN |
| WA01-S-001 | student_exam_execution_answer_sync_and_submit_remain_unchanged | attempt/answer/heartbeat/submit | NOT RUN |
| WA01-S-002 | student_own_published_result_dto_remains_minimal | current-score pointer/self-result | NOT RUN |
| WA01-R-001 | proctor_monitoring_requires_current_assignment_scope | active assignment only | NOT RUN |
| WA01-R-002 | cache_prewarmed_by_guru_does_not_leak_to_admin | prewarm then Admin request | NOT RUN |
| WA01-R-003 | cache_hit_does_not_bypass_admin_authorization | auth before cache | NOT RUN |
| WA01-R-004 | tenant_mismatch_and_cross_tenant_route_model_are_denied | all policy/controller methods | NOT RUN |
| WA01-R-005 | permission_bootstrapper_clone_revokes_exact_admin_cbt_grants | pivot before/after | NOT RUN |
| WA01-R-006 | permission_bootstrapper_clone_preserves_admin_assignment_and_admin_grants | exam.proctor.assign + admin permissions | NOT RUN |
| WA01-R-007 | permission_bootstrapper_clone_is_idempotent | two runs, pivot equality | NOT RUN |
| WA01-R-008 | permission_pivot_backup_restore_drill_preserves_non_cbt_grants | clone restore | NOT RUN |
| WA01-R-009 | rollback_does_not_reopen_admin_cbt_access | rollback plan and deny proof | NOT RUN |

**Jumlah test final: 69 ID** (25 authorization/API + 14 Livewire + 10 metadata + 20 role/cache/tenant/permission). Semua NOT RUN. Test plan lama 36 ID tetap baseline; matriks ini memecah acceptance agar setiap boundary dapat dibuktikan. Tidak ada hasil yang dikarang.

## 10. Blocker dan opsi desain

| Blocker | Dampak | Opsi |
|---|---|---|
| WA01-B01 QuestionBank shared Livewire | Admin melihat MADRASAH content; state berisi answer key; stale snapshot belum dibuktikan | Guard lifecycle teruji atau pisahkan component/route; bila gagal, tetap BLOCKED |
| WA01-B02 ExamManagement shared Livewire | Render eager-load exam/participant/attempt/snapshot; shared saveExam mengandung schedule Guru | Guard tanpa menyentuh schedule Guru atau pisahkan component; bila gagal, BLOCKED |
| WA01-B03 Assignment allowlist | Email tampil; query full columns; render tidak reauthorize; response belum direkam | Component metadata baru + route, atau WP assignment terpisah |
| WA01-B04 Web monitoring middleware | Route declaration di luar auth/tenant/license group | Verifikasi global middleware atau persetujuan route hardening |
| WA01-B05 QuestionPublication apiResource gap | show/update route generated but method absent | Keputusan routing terpisah; routes/api.php tidak berubah di paket ini |
| WA01-B06 Permission pivot DB | Bootstrapper W0 memberi CBT grants Admin | Approval DB terpisah setelah clone/backup/restore/pivot diff |
| WA01-B07 Schedule/Guru cross-scope | ExamController.update dan ExamManagement.saveExam shared | Tetap CROSS-SCOPE BLOCKED |
| WA01-B08 Runtime Livewire evidence | Tidak ada lifecycle/snapshot/HTML capture | Jalankan test pada PHP 8.3 + MySQL 8/InnoDB disposable |

## 11. Source target list — tidak berubah

Daftar 23 file existing tetap sama dengan paket utama. Tidak ada target ditambah/dihapus karena revisi ini dokumentasi-only. Ketentuan:
- QuestionBank.php, ExamManagement.php dan dua view terkait conditional/BLOCKED sampai isolasi Admin/Guru terbukti.
- ExamController.update dan ExamManagement.saveExam CROSS-SCOPE BLOCKED dan tidak boleh masuk diff WA-01.
- ProctorAssignmentManagement.php, view assignment, model dan service tetap excluded/protected.
- routes/api.php tetap excluded; gap QuestionPublication show/update dicatat sebagai blocker.
- Tidak ada file Teacher Livewire/view yang boleh berubah.

## 12. Status pengujian aktual

- Static source review: method inventory dan route mapping diperiksa pada W0.
- Source compare: identical, 0 ahead, 0 behind.
- Runtime HTTP/API: NOT RUN.
- Livewire lifecycle/hydration/snapshot/browser capture: NOT RUN.
- Query/eager-load capture: NOT RUN.
- HTML/response/export/cache inspection: NOT RUN.
- MySQL 8/InnoDB test: NOT RUN.
- Permission bootstrapper clone run: NOT RUN.
- Backup/restore drill: NOT RUN.
- Tidak ada PASS yang diklaim.

## 13. Gate

Paket ini bukan izin coding. Tidak ada source, DB, migration, seeder, permission pivot, config, source branch, PR, deployment atau Web Guru yang diubah. Persetujuan coding WA-01 dan persetujuan DB bootstrapper harus terpisah. Berhenti setelah publikasi dokumentasi dan tunggu keputusan pengguna.
