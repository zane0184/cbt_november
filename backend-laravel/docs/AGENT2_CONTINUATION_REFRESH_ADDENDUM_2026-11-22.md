# Addendum Refresh — Agent 2 Authentication, Exact-Role, Tenant, Reporting, Lifecycle, and Import
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4d-account-lifecycle-20261122
HEAD branch sebelum addendum: 32d383eb1564cdbcf084b8677fe8b750a4b45a11
Source runtime baseline: 74bf7178f46673e9be90523671490c0749034acd
Parent dokumentasi terbaru: 74bf7178f46673e9be90523671490c0749034acd
Status: AUDIT/DESIGN CONTINUATION COMPLETE — IMPLEMENTATION GATED

Dokumen ini adalah addendum terhadap backend-laravel/docs/AGENT2_CONTINUATION_AUTH_TENANT_REPORTING_LIFECYCLE_2026-11-22.md. Dokumen lama tidak ditimpa. Addendum memperbarui HEAD lintas-agent, mengoreksi beberapa pernyataan, dan menambahkan bukti source yang diperiksa setelah laporan awal.

## 1. AUDIT — refresh branch dan bukti CI

### 1.1 HEAD aktual

| Owner | Branch | HEAD aktual pada refresh | Status |
|---|---|---|---|
| Agent 2 | work/stage4d-account-lifecycle-20261122 | 32d383eb1564cdbcf084b8677fe8b750a4b45a11 | Commit dokumentasi saja; source runtime tetap 74bf7178 |
| Agent 1 | work/stage4b-room-session-20261122 | b3a7f5edac7c6ce592d800512abee6c36ec93df9 | Rekonsiliasi dokumentasi; source room/session belum berubah dari source yang diaudit |
| Agent 3 | work/web-guru-foundation-20261122 | 4c9bcc6bb403bfcf9ed90a915c86320da6c35287 | Checkpoint dokumentasi terbaru; source component tetap pada commit sebelumnya |
| Kandidat Agent 4 | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | Kandidat; owner/branch resmi belum dikonfirmasi |

Source runtime Agent 2 yang diperiksa tetap SHA 74bf7178f46673e9be90523671490c0749034acd. Commit 32d383e menambah dokumen audit, bukan patch runtime.

### 1.2 Authentication regression

- Fixture Admin CBT pada source SHA 74bf7178 masih memakai actingAs($admin, 'web'); blob source f15a6a0c5621e9c19bacbb9a2769913750aa1afe.
- Backup byte-exact tetap tersedia pada backend-laravel/docs/backup/stage4d-account-lifecycle-2026-11-22/AdminCbtBoundaryTest.php.before-explicit-web-actingAs-2026-11-22, blob 76fc79b9e22cf52efe05e8deadb0ef1a6ca91376.
- Commit backup a48cc115fa25a292d297c20d2b7f7bf99c18d506; commit fixture 74bf7178f46673e9be90523671490c0749034acd.
- Manifest root docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md belum mencatat backup explicit-web-guard di bawah backend-laravel/docs/backup. File backup ada; pencatatan manifest tetap gap. SHA-256 lokal tidak dihitung karena pemeriksaan dilakukan melalui GitHub contents API; Git blob SHA dicatat.
- Run #45000004149 menguji SHA lama a9c9e0b0dd0e876ff7eaac040e73202bc0b4438b, bukan fixture patch. PHPUnit berjalan dan gagal pada fixture guard lama; bukan bukti source patch gagal.
- Run #388 / ID 45003796892 menguji SHA patch yang tepat 74bf7178. Attempts 1, 2, dan 3 selesai failure dengan runner_id 0, tanpa runner, tanpa steps, dan tanpa log/artifact test. Status: BLOCKED — RUNNER.
- Run #389 / ID 45008591113 menguji commit dokumentasi 32d383eb, bukan source runtime baru. Job safe-runtime-tests juga runner_id 0 dan steps null. Status: BLOCKED — RUNNER.
- Tidak ada PHPUnit yang berjalan pada SHA patch. Tidak ada patch fixture kedua dan tidak ada perubahan middleware, policy, route, atau autentikasi untuk memaksa PASS.

### 1.3 Exact-role — delta audit backend

Audit file mencakup Policy, Middleware, route, Controller/API, Livewire, Service, Support, dan Provider. Exact-role belum konsisten. Selain gap yang sudah tercatat pada laporan utama, bukti berikut perlu dimasukkan ke backlog:

1. StudentExamController memakai pola hasRole(SISWA) dan pengecualian ADMIN, bukan tepat satu role SISWA. Akun GURU+SISWA tidak otomatis ditolak oleh pola ini.
2. LicenseStatusController masih mengenali role legacy OPERATOR melalui pemeriksaan keberadaan role; OPERATOR bukan portal aktif.
3. TeacherAccess merupakan helper pembatasan assignment/data Guru, tetapi tidak menjadi boundary exact-role mandiri. Pemanggil harus memastikan exact-role, dan helper kritis tidak boleh menjadi jalan bypass jika dipanggil langsung.
4. ExamMonitoringScope masih menyediakan cabang KAMAD dan pemeriksaan Guru berbasis keberadaan role pada beberapa jalur.
5. ExamParticipantController memeriksa role SISWA pada user peserta dengan hasRole; validasi peserta juga harus memastikan tepat satu role SISWA.
6. ImportTemplateController, TenantProfileManagement, sejumlah Admin Livewire component, ExamRoomSessionService, dan ImportWorkbookService masih menggunakan hasRole(ADMIN_MADRASAH) ditambah permission tanpa exact-role.
7. CurriculumAnalyticsController menerima Admin atau Guru berdasarkan keberadaan role, bukan satu role eksklusif.
8. MediaController memakai kombinasi GURU dan bukan ADMIN, bukan exact-role.
9. BasePolicy mengandalkan permission tanpa prasyarat exact-role mandiri; policy turunan master akademik harus menambahkan role boundary yang sesuai.
10. Auth web/API memang menghitung jumlah role tunggal, tetapi allow-list masih memasukkan KAMAD. Login-time exact count tidak menggantikan authorization server-side pada setiap request/service entrypoint.
11. Route group web dan API memakai auth/session/tenant/license middleware; tidak ada satu middleware exact-role global yang menggantikan policy per-resource. Ini dapat diterima hanya jika setiap controller, policy, Livewire action, dan service entrypoint melakukan pemeriksaan sesuai kemampuan.

Kontrol yang lebih kuat dan harus dipertahankan:
- QuestionPolicy mensyaratkan Guru tunggal pada authoring.
- ExcelQuestionImporter::assertQuestionAuthor() membaca role dari database, mewajibkan tepat satu GURU, lalu permission question.manage.
- ExamAnswerController memeriksa tepat satu SISWA untuk save answer.
- ExamAttemptController memiliki helper requireSingleStudent pada jalur tertentu; cabang show/branching lain tetap perlu ditutup.
- ExamPolicy::monitor memiliki exact Guru check, tetapi cabang KAMAD masih harus dihapus dari portal authorization.
- ExamProctorAssignmentService memvalidasi kelayakan Guru lebih ketat.
- Agent 3 memiliki TeacherPortalAccess::profile() dengan tepat satu GURU, user persisted, tenant context konsisten, dan tepat satu TeacherProfile tenant-lokal. Ini ada di branch Agent 3, bukan source Agent 2.

### 1.4 Test coverage exact-role

- AdminCbtBoundaryTest pada Agent 2 memiliki cakupan terbatas. Mixed GURU+SISWA diuji terutama pada QuestionPolicy dan ExcelQuestionImporter. Skenario Admin multi-role yang diuji menggabungkan lebih dari dua role; ini tidak menggantikan tes pasangan Admin+Guru dan Admin+Siswa pada seluruh surface.
- StudentWebLoginTest membuktikan login menolak beberapa kombinasi role, tetapi login resolver tidak membuktikan seluruh policy dan direct service invocation.
- MediaAuthorizationTest dan ProctorAssignmentSecurityTest memberi coverage pada area masing-masing; belum merupakan matriks seluruh route/method.
- MultiTenantAuthTest menunjukkan resolver saat ini menolak lebih dari satu Tenant per installation/database. Ini bukti perilaku source, bukan bukti bahwa shared multi-tenant DB sudah didukung.
- TeacherPortalFoundationTest di branch Agent 3 kini memiliki 21 test method pada source test commit c9d8f7e723be64467d759b921ebc3346fb4dfe46. Test tambahan meliputi revoked permission/assignment/role, soft-deleted profile/user, tenant context berubah, dan post-mount action.
- Run #45003191589 menguji source lama 834a59f11f9b548c8d7f0fcea68c15cf4bbe501f dengan 15 test method: 18 passed, 2 failed, 86 assertions. Failure pertama mengharapkan 403 tetapi menerima 200 pada tenant-context mismatch. Failure kedua memunculkan ModelNotFoundException saat Livewire action lintas tenant, sementara test mengharapkan Not Found.
- Test commit Agent 3 yang lebih baru c9d8f7e dan HEAD dokumentasi Agent 3 4c9bcc6 belum memiliki workflow/status check yang membuktikan PASS. Run lama 44995889398 pada SHA a33867928711b456d2414a675bef1a929861dee8 bukan bukti untuk test commit terbaru.
- Negative tests masih wajib pada policy, API, web route, Livewire hydration/action, service direct entrypoint, stale session/role revocation, tenant mismatch, dan seluruh kombinasi role.

### 1.5 Auth, KAMAD, dan tenant resolution

- AuthWebController dan API AuthController masih mengizinkan KAMAD. AuthWebController mengarahkan KAMAD ke root, sedangkan root hanya memiliki portal ADMIN_MADRASAH, GURU, dan SISWA; KAMAD tidak memiliki portal permanen dan berpotensi kembali ke login.
- LoginIdentityResolver menolak multi-role tetapi menerima KAMAD tunggal. KAMAD harus dikeluarkan dari allow-list login web/API; akun lama tidak dihapus dan tidak dialihkan ke Admin/Guru.
- Role KAMAD masih dibuat/diisi permission pada RolePermissionBootstrapper. Role legacy tidak boleh memberi portal atau permission operasional. Jabatan Kepala Madrasah harus menjadi official appointment tenant.
- LegacyRoleMigrationService memetakan role Proktor legacy ke Guru dan Operator legacy ke Admin; endpoint lain seperti LicenseStatusController yang masih menerima OPERATOR harus diselaraskan.
- Resolver mensyaratkan tepat satu LocalInstallationIdentity dan tepat satu Tenant total. MultiTenantAuthTest mengunci perilaku tersebut. Ini belum mendukung satu shared database berisi banyak Tenant.
- LocalInstallationIdentity memiliki registered_domain, tetapi resolver belum menggunakannya untuk memilih Tenant pre-auth. IdentifyTenant mengambil tenant dari user authenticated dan mengabaikan override header; perilaku ini dipertahankan.
- Keputusan topologi deployment masih wajib: shared DB multi-tenant dengan host/domain registry tepercaya, atau database/installation terpisah per tenant. Jangan mengklaim keduanya setara.
- Login view masih memakai branding generik CBT. Agent 2 harus menyediakan TenantBrandingResolver/DTO berdasarkan host registry/installation tepercaya; Agent 3 mengimplementasikan tampilan. Tidak boleh mempercayai tenant_id/header bebas dari browser.

### 1.6 Identitas Tenant, Guru, dan Kamad

Source yang diperiksa:
- Tenant: blob 08c43026e61c242d9d0bc7239769f1a0ce38e13c.
- TeacherProfile: blob 81574e9f9cc599a788a9d32bb412ffdaf0e7900f.
- TeacherSubjectAssignment: blob eae15c6c01dc929b53ad92196d36a92d9a8db796.
- Tenant memiliki name, code, NSM, NPSN, address, phone, email, logo_path, status. Kota/tempat penerbitan dan timezone belum ada.
- TeacherProfile memiliki employee_identifier, NIP, NIK, NUPTK, full_name, gender, phone. employment_status PNS/NON_PNS/UNKNOWN belum ada.
- NIP/NIK memiliki unique tenant-scoped; NISN juga tenant-scoped. NUPTK belum terbukti memiliki unique constraint. Unique policy untuk identifier nasional perlu review terhadap data existing.
- TeacherSubjectAssignment sudah menyimpan subject, rombel, academic year, semester, period, status, dan actor.
- Tidak ditemukan model/migration official appointment Tenant–Kamad, histori jabatan, atau relasi penandatangan resmi.
- Status PNS tidak boleh ditebak dari NIP kosong. NIP hanya wajib ketika status PNS telah diverifikasi. Non-PNS ditampilkan '-' hanya jika status Non-PNS eksplisit.
- Desain appointment Kamad harus memisahkan identitas pejabat dari role portal; simpan effective_from/effective_until, sumber dokumen, actor pencatat, audit, dan histori. Jika pejabat juga Guru, relasi TeacherProfile bersifat opsional dan tidak memberikan portal.
- Tidak ada migration/schema yang dibuat.

### 1.7 Reporting dan immutable snapshot

Source SHA utama:
- ExamAttempt: 341c8d00337ad8761dca94602da7acfbdc769fb0.
- ExamSummaryReportService: 45d8f97eb9b2c75164a6f10a403e052b77696552.
- ExamResultReportService: d57eca45de94cc722826998f995369c56ad323d8.
- StudentResultReportService: abc5eb0964983bd555c880c9d1d8fc4bc588d87b.
- ReportHeaderService: 9cab29ad41aecce79a8729bc98f506f5c83a2452.
- PDF header: fd15e6d112953fb31d1fea8b6196853ac42851bf.
- PDF exam-results: 177736de7edfe7c94a643911fbf21c0ac83af1bf.
- PDF student-results: b697997dea5fafadc6d91314fb04b87619050322.

Temuan:
- ExamAttempt::score() menggunakan current pointer ExamAttemptScoreCurrent. resolvedCurrentScore() memvalidasi keterkaitan attempt, tenant, score, dan scoring_version.
- ExamSummaryReportService mengagregasi tabel seluruh ExamAttemptScore history, bukan current pointer. AVG/MIN/MAX/total scored dapat menghitung beberapa versi untuk satu Attempt dan memasukkan status non-final. Hanya finalized_count yang menghitung FINALIZED. Ini bug statistik yang harus diperbaiki.
- StudentResultReportService memakai current pointer dan dapat membatasi FINALIZED. Pola pointer + FINALIZED harus menjadi kontrak laporan resmi.
- ExamResultReportService menggunakan relasi score aktif, tetapi status non-final tetap harus dibedakan pada report resmi dan tidak boleh otomatis masuk statistik final.
- PDF exam-results dan student-results menampilkan 0 saat score tidak ada. Belum dinilai harus berbeda dari final zero.
- ReportHeaderService menerima logo_path dan mencoba beberapa path filesystem termasuk path langsung; strict tenant storage prefix belum tampak. Ini perlu hardening, tetapi belum terbukti sebagai exploit.
- Tidak ditemukan ReportSnapshot/issued report table/model/service pada tree Agent 2.
- Header belum memiliki kota/tempat penerbitan, timezone, identitas Kamad resmi, dan Guru penandatangan.
- Exam memakai SoftDeletes (blob model b679bf6d6a0f68074ef1a000c0345f0248f696f3). Koreksi terhadap laporan awal: ExamController::destroy() memanggil delete() pada model SoftDeletes; ini soft delete, bukan hard delete fisik. Namun operasi destroy tetap belum memakai Exam row lock + transaction + re-authorize dalam transaksi dan tetap race dengan pembuatan Attempt.

Kontrak report snapshot:
1. Draft preview boleh live; issuance harus mengambil satu konsistensi source pada transaction/lock yang sesuai.
2. Snapshot JSON kanonik menyimpan identitas tenant/logo (hash bytes), Guru dan assignment penandatangan, Kamad appointment aktif, periode akademik, Exam, peserta, current score, finalization state, tempat/waktu/timezone, actor penerbit, schema version, template version, dan report revision.
3. PDF dirender hanya dari snapshot, bukan query live. Simpan hash payload dan hash artefak.
4. Issued payload/artifact immutable. Revisi membuat revision baru; revoke/cancel merupakan event audit, bukan overwrite.
5. Idempotency key per tenant + report type + scope + source revision. Repeated issue request yang sama mengembalikan issuance yang sama.
6. Guru signer harus berasal dari assignment mata pelajaran/rombel/Exam yang relevan, bukan sekadar user login. Kamad signer berasal dari official appointment yang berlaku pada waktu penerbitan.
7. Statistik hanya current score pointer berstatus FINALIZED. Pending/non-final/cancelled tidak dihitung sebagai nilai final.
8. Retention dan kewenangan Admin untuk jenis laporan tertentu masih memerlukan kebijakan; tidak ada migration dibuat.

### 1.8 Exam lifecycle, room/session, lock order, fencing

Source Agent 2:
- ExamController: blob c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9.
- ExamAnswerController: blob b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c.
- ExamPolicy: blob c05d96ef932fdc81b67336240af4e1726864c3d6.
- ExamAttemptPolicy: blob 00a3a4119f3983de6d940fd8f352a541336c9e12.

Temuan Agent 2:
- Start/submit Attempt mengunci Exam → Participant → Attempt dan re-authorize setelah Exam lock.
- Resume Attempt IN_PROGRESS yang belum expired dan peserta masih eligible tetap harus diizinkan setelah Exam close; Attempt baru tetap ditolak di luar schedule/status Exam.
- Answer update masih mengunci Attempt → Answer; belum parent-first Exam → Participant → Attempt → Answer/Score/Lease.
- ExamController::update belum serial melalui transaction + Exam lock + re-authorize.
- close mengunci Exam, tetapi belum menyelaraskan session/assignment Proktor secara atomik.
- archive dan publishResults belum transaction + Exam lock + re-authorize. destroy soft-delete tetapi tidak mengunci Exam; race dengan start Attempt masih ada.
- Destroy/archive tidak boleh menghapus histori Attempt/Answer/Score/snapshot; archive adalah jalur retensi normal.

Temuan Agent 1 lintas-branch:
- ExamSession migration blob 9551b1dd79397bd0432f03eefc970852b675fd25 masih unique (tenant_id, exam_id, session_number), bukan unique satu operational session per Exam; kolom is_operational/operational_guard belum ada.
- ExamSessionLifecycleService blob 8e34152afd817f30cc2f81b5f25fc450571463ac mengunci Session tanpa mengunci Exam terlebih dahulu. Scheduler menyelesaikan session dan assignment Proktor berdasarkan ends_at tanpa memeriksa Attempt IN_PROGRESS yang masih membutuhkan monitoring/recovery.
- cancelSession dapat melepas placement/revoke Proktor sementara Exam tetap PUBLISHED dan masih dapat menerima Attempt baru karena session bukan admission gate.
- rescheduleSession masih independen dari jadwal Exam.
- ExamRoomSessionService blob e514fa9f751584e54af1125d48b5673e013f2339. mapRombel/releaseRombelMapping menggunakan urutan lock Exam → Room → SessionRoom → Session; canonical Agent 1 mengusulkan Exam → Room → Session → SessionRoom.
- ExamProctorAssignmentService blob c968ce68c0decace1c35a0ccb3b2b4449c057656. lockScope juga menggunakan Exam → Room → SessionRoom → Session. Assign/revoke Proktor mengunci User sebelum Assignment, sedangkan kontrak canonical mengunci Assignment sebelum User.
- Scheduler mengunci Session tanpa Exam. Ini berpotensi deadlock/inconsistent state terhadap service yang mengunci Exam/Room lebih dahulu.
- Agent 1 meminta urutan canonical untuk room/session/proctor dan Attempt-only path disepakati lintas agent. Status approval tetap PENDING.
- Agent 1 branch HEAD terbaru b3a7f5e; dokumen kontrak final menyatakan Agent 2/3/4 belum memberi persetujuan formal.
- Agent 3 branch berbeda pada ExamAttemptController::show: source Agent 3 tidak memanggil ExamMonitoringScope::allowsParticipant() pada jalur yang diperiksa. Room scope Proktor harus tetap diberlakukan; otorisasi Exam-level saja tidak cukup.
- Branch kandidat Agent 4 belum resmi dan belum memuat keseluruhan Stage 4B room/session.

Lock order design gate:
A. Attempt-only: Exam → Participant → Attempt → Answer(s) → Score/current pointer → DeviceLease/Fencing.
B. Room/session lifecycle: Exam → Room(s) → Session → SessionRoom(s) → Rombel/Participant/Placement → ProctorAssignment(s) → User(s).
C. Operasi gabungan wajib menggunakan urutan global yang disepakati Agent 1–4; jangan mengambil child lock lalu kembali ke parent. Lock berulang harus deterministik berdasarkan primary key.
D. Scheduler harus lock Exam dahulu; jangan menyelesaikan assignment jika Attempt masih membutuhkan recovery. Revoke Proktor aktif harus ditolak atau dialihkan secara terkontrol.
E. Belum ada persetujuan final atau integration test MySQL 8 untuk keseluruhan matrix. Tidak ada migration.

Device Lease/Fencing:
- Schema migration device lease/fencing dan DeviceLeaseSchemaIntegrityTest ada.
- Pada branch Agent 2 tidak ditemukan model/service/controller runtime yang mengimplementasikan acquire, renew, release, takeover, monotonically increasing fencing generation, atau enforcement pada answer/save/submit.
- Runtime yang diperiksa masih memakai X-Device-Binding-ID dan device_fingerprint; ini bukan fencing end-to-end.
- DeviceLeaseSchemaIntegrityTest adalah pemeriksaan schema/FK, bukan multi-device concurrency test.
- Device Lease/Fencing tetap BLOCKED dan tidak siap produksi.

### 1.9 Import soal — Issue #8 dan #9, status per nomor

Source:
- QuestionController blob 7ded53423dd4d96788123819e8039fd543ce4fba.
- ExcelQuestionImporter blob 8a8951b0427506a56a4bfc700fbf4cf7feb7a214.
- QuestionImportService interface blob 1bdbcec7305b9c4abdfc010492cc7382b42ba352.
- ExamSnapshotService blob d0bfcd70b2777a3884c7f92b7c256f05492645ec.
- ScoringService blob 1fc74eae97538ada140b9b4c75ccd4388d1a40ad.
- ImportBatch blob 1c95378302b8d3368a781c9ec224e29cc8d0d8b9.
- ImportBatchRow blob c47c736e0a09732e7b9a1c44592057515e2a4cff.

| No. | Keputusan | Kontrak / blocker |
|---|---|---|
| 1 API lifecycle | NEEDS DESIGN | Endpoint usulan: POST /api/v1/questions/import-batches; GET /{batch}; PATCH /{batch}/rows/{row}; POST /{batch}/revalidate; POST /{batch}/confirm; POST /{batch}/cancel; GET /{batch}/report. Batas ukuran dari config, belum ditetapkan. |
| 2 ImportBatch reuse | APPROVED | Reuse ImportBatch/ImportBatchRow, type QUESTION. Jangan buat tabel atau batch service kedua. Existing file SHA unique tenant+type+hash. |
| 3 Staging/retention | NEEDS DESIGN | encrypted_payload dan private tenant storage dipakai; retention TTL, cleanup scheduler, status dan audit event perlu persetujuan. |
| 4 Idempotency | NEEDS DESIGN | File SHA untuk upload; row fingerprint canonical DTO dan confirm idempotency perlu schema/index setelah review. |
| 5 Duplicate detection | NEEDS DESIGN | Exact normalized fingerprint intra-file dan terhadap Question existing; tidak auto-merge; per-row status dan retry harus concurrency-safe. |
| 6 Authorization | APPROVED | Import hanya exact-role GURU + permission question.manage, tenant scope, TeacherSubjectAssignment aktif untuk subject/rombel/periode, CP/TP/KBC scope. Preview dapat memakai question.view. Validasi server-side; bukan state Livewire. |
| 7 Metadata | NEEDS DESIGN | Satu QuestionDraftDTO versioned; metadata yang tidak memiliki kolom/relasi tidak boleh ditulis ke kolom lain secara implisit. |
| 8 Kurmer + KBC | NEEDS DESIGN | QuestionMultiCurriculumMapping tersedia, tetapi importer belum membuktikan persist mapping CP/TP/Panca Cinta dengan tenant/system-owner scope, subject, grade/phase, active version, provenance. |
| 9 Accepted answers | REJECTED untuk mengaktifkan SHORT_ANSWER sekarang | Importer menyimpan accepted_answers ke QuestionVersion.metadata, tetapi QuestionController validation tidak menerima accepted_answers dan ExamSnapshotService tidak menyalinnya ke snapshot. ScoringService membaca accepted_answers dari snapshot metadata. SHORT_ANSWER tetap BLOCKED sampai storage → snapshot → scoring dan test E2E lulus. |
| 10 MediaAsset | NEEDS DESIGN | Reuse MediaAsset/private storage; format media reference, attachment lifecycle, tenant prefix, ukuran, malware scan, embedded DOCX image phase perlu kontrak. |
| 11 PDF/OCR | REJECTED pada fase ini / DEFERRED | PDF/OCR tetap terpisah dan ditunda. Tidak ada parser/binary/OCR dependency sekarang. |
| 12 Security preflight | NEEDS DESIGN | XLSX/DOCX: extension + MIME + magic, ZIP expansion limit, XML DTD/ENTITY off, external relationship blocked, embedded OLE/active content blocked, malformed/password file reject, resource/time limit. PDF ikut fase terpisah. |
| 13 Audit/partial processing | NEEDS DESIGN | Per-row transaction, row status, partial failure/retry, actor/reason/time/result audit, no auto-publish; valid row hanya menjadi DRAFT. |

DTO netral yang diusulkan: QuestionDraftDTO v1 berisi source_format, parser_version, source_locator, question_type, stem segments, explanation, options, accepted_answers (hanya bila kelak diaktifkan), rubric, points, difficulty, subject/assignment, academic year/semester, grade/phase, CP/TP, Kurikulum Merdeka/Panca Cinta mapping, media references, warnings/confidence, dan provenance.

Satu lifecycle batch:
UPLOAD → SECURITY_PREFLIGHT → EXTRACT → STAGE → PREVIEW/ROW_CORRECTION → REVALIDATE → EXPLICIT_CONFIRM → PERSIST_DRAFT → AUDIT.
DOCX dan XLSX adalah adapter parser berbeda menuju DTO dan lifecycle yang sama. ImportBatch type 10 karakter dapat memuat QUESTION; ImportBatchRow sudah memiliki encrypted_payload, row_summary, sheet_name, row_number. source_locator dan row_fingerprint terindeks belum tersedia; jangan mengandalkan row_summary sebagai unique constraint. Penambahan kolom/index menunggu review schema.
File disimpan private dan tenant-scoped; cleanup TTL belum diputuskan. Confirm berulang harus idempotent. Per-row commit memungkinkan retry/partial status, tetapi semua valid row tetap DRAFT. Tidak ada auto-publish.
DOCX parser dependency belum dipilih; PHPWord tidak tercantum di Composer yang diperiksa. Tidak ada dependency ditambahkan.
Issue #9 sekarang memiliki komentar Agent 2: DOCX adalah adapter kedua ke QuestionDraftDTO dan ImportBatch/ImportBatchRow yang sama; tidak ada subsystem kedua.
Issue #8 memiliki komentar Agent 2 berisi kontrak ringkas, tetapi endpoint, retention, idempotency row, duplicate concurrency, media, metadata dan preflight masih NEEDS DESIGN; tidak boleh disebut final approval lintas-agent.

## 2. DESAIN — ownership dan approval

| Area | Owner utama | Status |
|---|---|---|
| Exact-role backend guard, policies, API, service direct entrypoint | Agent 2 | NEEDS DESIGN sebelum patch |
| TeacherPortalAccess + QuestionBank/ExamManagement UI | Agent 3 | Source lebih kuat di branch Agent 3; CI latest test belum PASS |
| Tenant resolver, branding DTO/service | Agent 2; tampilan Agent 3 | NEEDS DESIGN |
| Official Kamad appointment identity | Agent 2 | NEEDS DESIGN |
| Report snapshot/current score | Agent 2; PDF view Agent 3 | NEEDS DESIGN |
| Exam/session/room/proctor canonical model | Agent 1 | NEEDS DESIGN / approval Agent 2/3/4 pending |
| Attempt/Answer/Score/Lease/Fencing | Agent 2 + owner Agent 4 belum terkonfirmasi | BLOCKED |
| DOCX/XLSX import DTO, batch, API, persistence | Agent 2 | Kontrak parsial; beberapa poin NEEDS DESIGN |
| Upload/preview/correction/confirm UI | Agent 3 | Menunggu kontrak backend |
| Student UI / Attempt recovery | Agent 4 belum terkonfirmasi | PENDING |

Issue #10 tetap OPEN. Tidak ada approval formal Agent 2/3/4 untuk canonical session, room, scheduler, lock order, recovery, atau integration. Branch Agent 4 tetap kandidat.
Issue #8 dan #9 tetap kontrak import; tidak ada izin menambah dependency, schema, atau batch service kedua.

## 3. IMPLEMENTASI

Dalam refresh ini:
- Tidak ada runtime source, Policy, route, controller, middleware, model, migration, schema, database, Composer, UI, konfigurasi, atau workflow yang diubah.
- Tidak ada branch Agent 1/3/4 yang diubah.
- Tidak ada merge, rebase, cherry-pick, PR, deployment, atau database migration.
- Commit 32d383e adalah dokumentasi yang sudah ada sebelum addendum ini.
- Addendum ini dan checkpoint terkait adalah dokumen baru; tidak menimpa laporan/checkpoint lama.
- Tidak ada SHA-256 lokal dihitung; Git blob SHA dipakai untuk identitas file yang diperiksa.

## 4. PENGUJIAN

| Test/run | SHA | Environment | Hasil |
|---|---|---|---|
| Agent 2 Run #388, ID 45003796892, attempts 1–3 | 74bf7178 | GitHub Actions | BLOCKED — RUNNER; runner_id 0, steps kosong |
| Agent 2 Run #389, ID 45008591113 | 32d383eb | GitHub Actions | BLOCKED — RUNNER; source runtime tidak berubah |
| Run #45000004149 | a9c9e0b0 | GitHub Actions/PHPUnit | FAIL pada fixture lama; bukan SHA patch |
| Agent 3 Run #45003191589 | 834a59f | PHP 8.3, disposable MySQL 8.0.46 | 18 passed, 2 failed, 86 assertions |
| Agent 3 test commit terbaru | c9d8f7e | GitHub Actions | No workflow/status/artifact; NOT VERIFIED |
| Source audit addendum | 74bf7178 | GitHub read-only | Tidak menjalankan PHPUnit/concurrency |

Belum ada bukti MySQL 8 concurrency global lock order, immutable report issuance, tenant pre-auth, import E2E, SHORT_ANSWER E2E, atau Device Lease/Fencing multi-device.

## 5. CHECKPOINT

Selesai:
- Refresh HEAD Agent 2/1/3/4 dan bedakan source runtime SHA dari commit dokumentasi.
- Revalidasi Run #388 attempt 3 dan Run #389; keduanya BLOCKED — RUNNER.
- Audit exact-role tambahan pada StudentExamController, LicenseStatusController, TeacherAccess, ExamMonitoringScope, service entrypoints, policy dan routes.
- Koreksi semantics Exam destroy: model memakai SoftDeletes; race lock tetap terbuka.
- Audit lock order aktual Agent 1 vs canonical; temukan inversion SessionRoom/Session dan User/Assignment.
- Perbarui koordinasi Issue #8/#9/#10 dan status Agent 3 test.
- Tegaskan SHORT_ANSWER BLOCKED berdasarkan QuestionController validation + ExamSnapshotService + ScoringService.

Belum selesai / blocker:
1. PHPUnit Admin CBT pada source SHA 74bf7178 belum berjalan karena runner.
2. Exact-role backend belum konsisten; belum ada negative test komprehensif seluruh role-pair dan direct entrypoint.
3. KAMAD masih diterima login web/API dan role legacy OPERATOR masih dikenali endpoint tertentu.
4. Topologi tenant pre-auth shared DB vs per-tenant installation belum diputuskan.
5. Tenant/Guru/Kamad official identity, branding, dan immutable report snapshot belum disetujui/diimplementasikan.
6. ExamSummaryReportService harus beralih ke current score pointer + FINALIZED.
7. Lock order aktual Agent 1 perlu direkonsiliasi dan disetujui lintas-agent; scheduler/proctor recovery belum aman.
8. Device Lease/Fencing belum ada bukti runtime/multi-device.
9. Import API/DTO/batch lifecycle belum final untuk seluruh 13 poin; SHORT_ANSWER BLOCKED.
10. Agent 3 test commit c9d8f7e belum CI PASS; Agent 4 owner belum terkonfirmasi.

Langkah berikutnya:
1. Tunggu runner tersedia, jalankan workflow Admin CBT pada SHA 74bf7178 tanpa mengubah fixture.
2. Finalisasi exact-role helper/policy/service contract dan negative tests sebelum patch.
3. Minta Agent 3 menyelesaikan dua failure pada run 45003191589 dan menjalankan test 21-method pada exact SHA c9d8f7e atau HEAD terbaru.
4. Putuskan topologi tenant dan pre-auth domain resolution.
5. Review kontrak Tenant/Guru/Kamad, report snapshot, current pointer dan PDF.
6. Minta approval tertulis Agent 1/3/4 atas canonical session, lock order, room monitoring, recovery dan ownership Attempt/Lease.
7. Finalisasi 13 poin Issue #8/#9; jangan tambah Composer dependency atau schema sebelum review.
8. Setelah approval dan preflight disposable MySQL 8, ajukan rencana migration forward-only terpisah. Jangan menyentuh database produksi.

Status akhir: AUDIT/DESIGN CONTINUATION COMPLETE — IMPLEMENTATION GATED.
Tidak ada klaim siap produksi, siap integrasi, atau siap distribusi Android Studio.
