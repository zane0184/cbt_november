# PAKET PERSIAPAN EKSEKUSI WA-01 — WEB ADMIN CBT MADRASAH

**Status: SIAP DITINJAU — BELUM DISETUJUI UNTUK CODING.** Dokumen ini bukan perintah eksekusi. Tidak ada source aplikasi, database, migration, seeder, role, permission, konfigurasi, Web Guru, Android, VPS atau PR yang diubah.

- Repository: zane0184/cbt_november
- Branch dokumentasi: docs/canonical-reconciliation-admin-guru-20261121
- Source branch: work/web-admin-w0-20261121
- Source commit/pin: 2b4321ce032fd5e67cf022da8564a5287b8cef6f
- Backup source/test: docs/backup/wa-01-source-2026-11-21/
- Manifest SHA-256: docs/backup/wa-01-source-2026-11-21/MANIFEST.md
- Tanggal: 21 November 2026 (WIB)

## 1. Tujuan dan batas WA-01

WA-01 menutup seluruh akses role ADMIN_MADRASAH terhadap isi dan operasi CBT yang menjadi kewenangan Guru. Kontrol wajib berada pada server-side authorization dan query/serialization boundary, bukan hanya menu atau tombol.

Cakupan yang harus ditolak untuk Admin:
1. Bank soal, isi soal, opsi, kunci, pembahasan, rubrik/pedoman penskoran, versi soal, publikasi, dan media soal.
2. Token, konfigurasi ujian, snapshot, instruksi/pertanyaan, dan relasi Exam/Assessment mentah.
3. Roster, peserta, attempt, jawaban, revisi jawaban, skor, nilai per soal, hasil, dan histori hasil.
4. Monitoring, heartbeat/event operasional, close, publish result, scoring, recalculation, manual grading, finalization, reopen, dan operasi CBT lain.
5. Laporan, PDF/Excel export, analytics ujian/butir/rombel/siswa/mapel yang berasal dari hasil CBT.
6. Semua akses lewat UI, Livewire mount/hydration/action/render, HTTP/API, controller, policy/gate, route, service invocation, query, cache, export, dan serialization.

Admin tetap memiliki akses administratif sah di luar CBT. Satu-satunya informasi ujian yang boleh tampil pada alur Admin adalah metadata minimum untuk penugasan Proktor.

## 2. Batas scope yang wajib dipertahankan

- Web Admin saja. Tidak ada pengembangan Web Guru.
- Tidak mengubah app/Livewire/Teacher/* atau view Guru.
- Tidak mengubah perilaku Guru pada API/controller/policy/service/shared component. Perubahan yang berpotensi mengubah perilaku Guru dihentikan dan memerlukan persetujuan lintas-scope.
- Shared schedule service, ExamController schedule update, shared ExamManagement schedule-save, dan capability Guru terkait WA-03 tetap CROSS-SCOPE BLOCKED.
- Role Admin aktif hanya ADMIN_MADRASAH. ADMIN dan OPERATOR bukan alias runtime; konsolidasi alias merupakan WA-02.
- Akun lama tidak dihapus, digabung, diubah identitasnya, atau dihilangkan histori/audit-nya.
- Tidak ada migration, seeder execution, database write, deployment, perubahan .env/config, atau PR pada tahap persiapan.

## 3. File source WA-01 yang direncanakan berubah

SHA di bawah berasal dari source W0, bukan branch dokumentasi. Backup byte-identical seluruh file terdapat di manifest.

| # | File dan source blob SHA W0 | Kondisi sebelum | Target sesudah | Dependency / risiko regresi |
|---:|---|---|---|---|
| 1 | app/Services/RolePermissionBootstrapper.php — fac82573de84c52b52867afbbf7e1637164835b4 | Grant Admin mencakup question.view, exam.manage/assign/monitor/close/token/grade/reopen, report.view. | Cabut grant CBT Admin; pertahankan exam.proctor.assign dan permission curriculum/academic/user. Idempotent; tidak menghapus role/user/history. | Bootstrap kelak mengubah role_permission pivot. Snapshot/export grant sebelum deploy; cek tidak ada workflow administratif sah yang bergantung pada grant yang dicabut. |
| 2 | app/Policies/QuestionPolicy.php — af94614a2043206922149b0c44a8ec1e3d87767d | Admin dapat viewAny/view soal tenant, termasuk soal MADRASAH, jika punya question.view. | Admin ditolak eksplisit pada viewAny/view walau grant lama masih melekat. Guru authoring/subject ownership tetap sama. | Shared policy; wajib Guru positive regression dan Admin stale-permission test. |
| 3 | app/Policies/AssessmentPolicy.php — 79a0367c8c83723ae3cc9e581fb8dda8a6e2d02a | Admin/Operator/Kamad dapat viewAny/view Assessment; show API memuat relasi exams. | Admin ditolak pada raw Assessment/Exam read. Guru dan Siswa tetap mengikuti aturan existing. | Shared policy dan student visibility; uji payload serta Admin multi-role Siswa. |
| 4 | app/Policies/ExamPolicy.php — 350c1ddc6bed8738d1af17cacd6c95bc367b7c29 | Admin dapat viewAny/view/monitor/close dengan permission; assignProctor/revokeProctor tersedia. | Admin ditolak pada viewAny/view/monitor/close/lifecycle CBT. Pertahankan assignProctor/revokeProctor untuk halaman assignment. Guru lifecycle tetap sesuai kewenangan existing. | Shared policy; jangan mengubah TeacherAccess/Guru branch. Close tetap Guru sesuai policy existing. |
| 5 | app/Policies/ExamAttemptPolicy.php — b19d6d39d34884d8be464f786340c1a2ab7108b6 | Admin dapat view/viewScore/finalize/reopen dengan permission. | Admin ditolak untuk attempt, answer-level detail, score, finalize, reopen. Penolakan Admin didahulukan dari fallback Student self-service untuk akun multi-role. | Guru grading/finalize dan Siswa current-score DTO tetap. Reopen tidak dialihkan ke Guru tanpa keputusan terpisah. |
| 6 | app/Policies/ReportPolicy.php — e53be3994c52d067c1e31fe37edc450c24a0f4b6 | Admin/Kamad dapat melihat hasil exam/student/subject. | Admin ditolak untuk seluruh CBT result/report methods, termasuk self-result jika akun juga memiliki role Admin. Guru scope tetap. | Shared policy dipakai Gate/Livewire; positive Guru dan Student regression wajib. |
| 7 | app/Http/Controllers/Api/V1/QuestionController.php — 7ded53423dd4d96788123819e8039fd543ce4fba | List/detail/version/import/publish dan mutasi bergantung authorization saat ini. | Setiap method memakai QuestionPolicy; tidak ada alternate method bypass; Admin 403 sebelum data/side effect. | Guru CRUD/import/publish tidak boleh berubah; audit route matrix per method. |
| 8 | app/Http/Controllers/Api/V1/QuestionPublicationController.php — b0c3f6501601af8c6254feb1ed9cbcab7eeaaff1 | Publication read/list/mutation memakai authorization existing. | Admin tidak dapat membaca/mengubah publication atau status publikasi. Guru workflow tetap. | Uji index/show/store/update/delete per route. |
| 9 | app/Http/Controllers/Api/V1/MediaController.php — 52254b81a55db8e2e45538158ef202b8ccf09b70 | Admin/Guru dapat memasuki beberapa jalur QUESTION media; USER media memiliki owner checks. | Admin tidak dapat list/show/upload/attach/detach/delete QUESTION media. USER media milik sendiri tetap sesuai owner checks. Guru question media tetap. | Storage private/no-store/path validation dan snapshot media historis tidak boleh rusak. |
| 10 | app/Http/Controllers/Api/V1/AssessmentController.php — 28e6f8fe07c8e2b3dad22ecd190e9aa108947e78 | show melakukan load exams; index menerima Admin. | Admin tidak mendapat raw Assessment atau embedded Exam; tidak ada token/config melalui relasi. Metadata assignment hanya melalui halaman khusus. | Uji JSON recursive key denylist, termasuk nested relation. Guru/Siswa response tetap. |
| 11 | app/Http/Controllers/Api/V1/ExamController.php — bf373858869b470b235e2256e5be6169fbb1f561 | Admin viewAny/view/close policy mengizinkan raw exam read/operasi tertentu. | Admin ditolak pada index/show/preview/snapshot/questions/token/lock/close/publish/lifecycle. Guru branch tetap. | Shared controller: petakan setiap method; jangan mengubah schedule update/API Guru. |
| 12 | app/Http/Controllers/Api/V1/ExamParticipantController.php — f7014ab069cc2c9548f9b238e0e7a4f1986cb421 | index memakai monitor; Guru manageParticipants; Admin bisa monitor roster. | Admin ditolak untuk roster/index/store/destroy. Guru participant management tetap. Perubahan tenant join/FK ditunda ke WA-04. | Jangan gabungkan perbaikan FK WA-04 ke WA-01. |
| 13 | app/Http/Controllers/Api/V1/ExamAttemptController.php — ace6a83989375e067b3a7747bc4f320c21222c69 | Student self-service, attempt lifecycle dan detail memiliki jalur masing-masing. | Admin ditolak pada execution/store/show/submit/attempt detail termasuk akun multi-role; Siswa flow tetap. | Jangan mengubah fencing, lease, idempotency, timer atau lifecycle Siswa. |
| 14 | app/Http/Controllers/Api/V1/ExamAnswerController.php — 333e37007c072243fe828ab038d738fc98d88e8e | Ownership attempt membatasi jawaban ke peserta; belum ada explicit deny untuk Admin multi-role. | Explicit deny Admin sebelum answer index/update; Siswa owner/device/revision/idempotency flow tetap. | Risiko tinggi karena answer payload sensitif; uji body, conflict response, dual-role, device binding. |
| 15 | app/Http/Controllers/Api/V1/MonitoringController.php — 335a32029f4d5ab4788ea31fd81088dfdb5acde2 | Admin dapat summary/participants/attempt/events; heartbeat adalah jalur operasional Siswa. | Admin ditolak pada seluruh monitoring read/write. Heartbeat Siswa tetap; jangan mengubah payload Guru/Proktor pada WA-01. | Shared operational controller; pisahkan Admin guard dari Siswa/Guru/Proktor. |
| 16 | app/Http/Controllers/Api/V1/ScoringController.php — 42b96176162a6e69e3661d468a6d3362b05f59c5 | Policy memberi Admin score read/finalize/reopen pada jalur tertentu. | Admin ditolak pada score/recalculate/show/manual grading/finalize/reopen. Guru scoring/manual grading/finalize tetap. | Jangan mengubah scoring algorithm, score pointer, lock order atau cache invalidation. |
| 17 | app/Http/Controllers/Api/V1/ReportController.php — c78442de0f5b4b40a7773bb2af0cf0ffd852d055 | ReportPolicy mengizinkan Admin hasil/summary/student/subject dan export. | Admin ditolak pada exam/student/subject/question report dan PDF/Excel export CBT. Guru scope tetap; Student self-result DTO tetap. | Uji PDF/Excel tidak mulai generate dan tidak mengembalikan file/body sensitif. |
| 18 | app/Http/Controllers/Api/V1/AnalyticsController.php — 3339103d64d06427896344c6e4f3bb45db8a680b | Admin dapat exam/item/rombel/student/subject analytics; cache tenant-prefixed. | Admin ditolak sebelum cache read/service call. Curriculum achievement analytics terpisah tidak disentuh. | Uji cache pre-warm, cache hit, dan tenant key; Guru analytics tetap. |
| 19 | app/Livewire/Admin/QuestionBank.php — a3013b528015daf4214dd1aa567eba950b03663a | mount mengizinkan Admin dan Guru; render memuat stem/options/learning outcome. | Admin ditolak pada mount, render, hydration, dan setiap action. Guru authoring branch tetap. | Komponen shared; jangan mengubah Guru query/validation/authoring. Uji Livewire direct action tanpa UI. |
| 20 | app/Livewire/Admin/ExamManagement.php — 478aaa170ee297b0c8399f049cd9b1ba56cbfb10 | Query eager-load assessment, participants, attempts, snapshots; UI menampilkan token/monitoring/results/analytics. Guru juga memakai component. | Admin ditolak pada halaman dan seluruh hydration/action. Guru branch dipertahankan. Admin memakai /admin/proctor-assignments yang sudah ada untuk metadata minimum. | Shared component sensitif; jangan mengubah Guru schedule-save/create/publish/token/lock flow. Jika isolasi tidak terbukti, hentikan patch file ini dan minta approval lintas-scope. |
| 21 | resources/views/livewire/admin/question-bank.blade.php — 72feaf1cc1be17253868c91861781dbb223b5aa9 | Menampilkan stem, opsi, jawaban benar, pembahasan/rubrik dan action. | Tidak dirender kepada Admin; Guru view tetap. Security tetap di component/policy, bukan hidden button. | Uji response HTML dan Livewire payload; jangan mengubah markup Guru. |
| 22 | resources/views/livewire/admin/exam-management.blade.php — 5705d815cc3e2539d167b22a26d3508586a77439 | Menampilkan token, monitoring, hasil, analytics dan konfigurasi. | Tidak dirender kepada Admin; Guru view tetap. Admin metadata berada di halaman assignment terpisah. | Uji HTML no token/config/roster/attempt/result fields. |
| 23 | routes/web.php — c22fed7d54871bf4971acf626514fc02c4a68ce9 | Admin route /admin/questions dan /admin/exams serta result/analytics routes tersedia; monitoring closure memakai policy. | Tambahkan route-level can guards untuk question authoring, exam authoring, results/detail/analytics; Guru tetap lolos sesuai policy. Monitoring tetap policy-gated. | Route middleware harus role-aware dan tidak mengubah akses Guru. |

### File baru yang direncanakan
- backend-laravel/tests/Feature/AdminCbtBoundaryTest.php — test matrix Admin negative, Guru positive, Student self-service, response allowlist, stale permission, multi-role, cache prewarm dan metadata-only assignment page. File belum dibuat; tidak ada SHA/backup pre-change.

### File yang sengaja TIDAK diubah pada WA-01
- backend-laravel/routes/api.php — seluruh API routes tetap; enforcement dilakukan pada controller/policy dan diuji untuk setiap route. File ini disalin sebagai artefak referensi, bukan target patch.
- app/Livewire/Teacher/* dan seluruh view Guru.
- app/Livewire/Admin/ProctorAssignmentManagement.php — blob W0 4e8bd91dd54757aa863d35983aace718def2ca2c.
- resources/views/livewire/admin/proctor-assignment-management.blade.php — blob W0 e5e91928c8779be8651fef106911f215a3818a64.
- app/Services/ExamProctorAssignmentService.php — blob W0 e16ef5db308714aa98bf283dc5a1545608da0477.
- app/Providers/AppServiceProvider.php — blob W0 9cb0fa0b9e9db7a338741e14a7c0798d635e9bc6; Gate definitions tetap memanggil hardened policies.
- app/Models/User.php — blob W0 74dd080f703d7ef3256b406176e53dad5786a937; tidak ada status Guru migration/model change pada WA-01.
- Teacher Livewire ResultList, ResultDetail, AnalyticsDashboard, ItemAnalysis tetap untouched; route/policy denial Admin diuji tanpa mengubah komponen Guru.

## 4. Metadata minimum yang diizinkan untuk Admin

Halaman /admin/proctor-assignments yang sudah ada menjadi satu-satunya surface metadata ujian Admin pada WA-01. Source W0 ProctorAssignmentManagement query daftar ujian dengan kolom id,title,start_at,end_at; riwayat assignment memakai id,title,status,start_at,end_at pada relasi Exam. Tampilan menampilkan judul ujian, Proktor, masa berlaku dan status assignment.

Tidak ada token, instructions, subject/question content, snapshot, roster, participant identity, attempt, answer, score, result, monitoring event, atau analytics pada halaman ini. Tidak dibuat endpoint/resource baru karena halaman assignment metadata sudah tersedia. WA-03 menangani status assignment/Guru dan tidak termasuk perubahan WA-01.

Acceptance membuktikan response HTML/Livewire hanya memuat allowlist tersebut, termasuk ketika data sensitif tersedia pada database. Metadata page tidak boleh menjadi jalur alternatif untuk membuka Exam model mentah.

## 5. Existing test yang perlu direvisi

Semua test berikut adalah kandidat source W0, bukan hasil eksekusi baru. Hanya assertion yang mengasumsikan akses Admin CBT yang boleh diubah; jangan melemahkan Guru/Siswa/tenant controls.

| Test file | Source blob SHA W0 | Test method | Perubahan ekspektasi |
|---|---|---|---|
| tests/Feature/AdminUiVerificationTest.php | ac81ef5841c98154558aa06765744e0f74949907 | admin_can_access_all_admin_routes | Admin tetap membuka modul administrasi; /admin/questions dan /admin/exams ditolak; /admin/proctor-assignments diizinkan. |
| tests/Feature/RoleDashboardVerificationTest.php | 359ebbaeb817bf2d76d4137673a84eeedf237681 | monitoring_access_control_and_tenant_isolation | Admin ditolak; Guru sesuai assignment/TeacherAccess; Proktor hanya assignment valid. |
| tests/Feature/RolePermissionBootstrapperTest.php | cfacac702f967667c8e1e4f6dff18000fc105f68 | test_role_permissions_are_granted_least_privilege_and_idempotently | Admin tidak menerima CBT grants; exam.proctor.assign tetap; bootstrap dua kali menghasilkan pivot sama. |
| tests/Feature/ScoringHardeningTest.php | c4d36a9663cf94caf5cc26eb1e4ac33870b63fc6 | test_reopen_score_is_allowed_only_for_admin_roles_in_same_tenant | Revisi karena bertentangan dengan keputusan baru: Admin tidak boleh reopen. Uji deny Admin; jangan memindahkan hak reopen ke Guru tanpa keputusan terpisah. |
| tests/Feature/Security/AnswerKeyLeakageTest.php | 13a3451aa67f1a124f066ce6288bb158e262ced9 | test_non_teacher_staff_cannot_preview_answer_key; test_guru_can_see_answer_key_in_preview | Tambahkan Admin deny untuk question content/API; pertahankan Guru preview positive. |
| tests/Feature/MediaAuthorizationTest.php | 75821e6554c0ceec1ff4b9e81f00913b4c9c2a9c | Tambah method Admin question-media deny | Admin tidak dapat question media; Guru media authoring dan USER media owner behavior tetap. |

## 6. Test baru — identity, method, source commit, environment dan status

Semua ID adalah test plan, bukan test yang sudah dijalankan. File AdminCbtBoundaryTest.php baru boleh dibuat setelah approval. Source baseline seluruh test adalah 2b4321ce032fd5e67cf022da8564a5287b8cef6f.

| ID | Test method yang akan dibuat | Target utama | Source commit | Environment rencana | Hasil aktual saat paket dibuat |
|---|---|---|---|---|---|
| WA01-N-001 | admin_cannot_read_question_content_versions_or_keys | QuestionController index/show/versions | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-002 | admin_cannot_import_publish_or_mutate_questions | QuestionController import/publish/store/update/delete | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-003 | admin_cannot_read_or_mutate_question_publications | QuestionPublicationController all routes | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-004 | admin_cannot_read_or_mutate_question_media | MediaController QUESTION owner paths | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB + private storage fake | NOT RUN |
| WA01-N-005 | admin_cannot_read_assessment_or_embedded_exam_relations | AssessmentController index/show | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-006 | admin_cannot_read_exam_index_detail_or_preview | ExamController index/show/preview | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-007 | admin_cannot_read_or_mutate_exam_snapshot_questions | ExamController question/snapshot methods | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-008 | admin_cannot_read_or_rotate_exam_token | ExamController token endpoint | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-009 | admin_cannot_lock_close_or_publish_exam | ExamController lock/close/publish lifecycle | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-010 | admin_cannot_read_or_mutate_exam_roster | ExamParticipantController index/store/destroy | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-011 | admin_cannot_read_attempts_execution_or_submit | ExamAttemptController execution/store/show/submit | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-012 | admin_cannot_read_or_write_exam_answers | ExamAnswerController index/update | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-013 | admin_cannot_access_monitoring_or_heartbeat_operations | MonitoringController summary/participants/attempt/events/heartbeat | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-014 | admin_cannot_score_recalculate_or_read_scores | ScoringController score/recalculate/show | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-015 | admin_cannot_grade_finalize_or_reopen_scores | Manual grading/finalize/reopen API + web action | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-016 | admin_cannot_read_exam_student_subject_question_reports | ReportController report methods | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB disposable | NOT RUN |
| WA01-N-017 | admin_cannot_export_exam_or_student_results | PDF/Excel exports and headers | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB + fake storage | NOT RUN |
| WA01-N-018 | admin_cannot_read_exam_item_rombel_student_subject_analytics | AnalyticsController + prewarmed cache | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB + cache test store | NOT RUN |
| WA01-N-019 | admin_cannot_mount_or_hydrate_question_bank_livewire | QuestionBank mount/render/actions | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB + Livewire test | NOT RUN |
| WA01-N-020 | admin_cannot_mount_or_hydrate_exam_management_livewire | ExamManagement mount/render/actions | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB + Livewire test | NOT RUN |
| WA01-N-021 | admin_cannot_open_result_monitoring_or_analytics_web_routes | web routes + route middleware + policies | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB HTTP tests | NOT RUN |
| WA01-N-022 | admin_assignment_page_exposes_only_allowlisted_exam_metadata | ProctorAssignmentManagement HTML/data fields | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB + Livewire test | NOT RUN |
| WA01-N-023 | admin_with_stale_permission_or_multirole_is_still_denied | Policies + direct grants + Admin+Siswa adversarial actor | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |
| WA01-N-024 | admin_cannot_read_cached_analytics_after_cache_preload | Cache prewarm then Admin request | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB + cache store | NOT RUN |
| WA01-N-025 | admin_role_bootstrapper_revokes_cbt_grants_idempotently | RolePermissionBootstrapper + permission pivot | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |
| WA01-P-001 | guru_question_bank_authoring_and_question_api_remain_allowed | Guru QuestionBank + QuestionController | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |
| WA01-P-002 | guru_publication_and_question_media_workflow_remains_allowed | Guru publication/media attach/detach/show | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB + private storage | NOT RUN |
| WA01-P-003 | guru_assessment_and_exam_authoring_remains_allowed | Assessment/Exam list/detail/create/update | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |
| WA01-P-004 | guru_token_lock_and_exam_lifecycle_remain_allowed | Guru token/lock/close/publish | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |
| WA01-P-005 | guru_participant_management_remains_assignment_scoped | ExamParticipantController | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |
| WA01-P-006 | guru_monitoring_remains_assignment_scoped | MonitoringController + web monitoring | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |
| WA01-P-007 | guru_scoring_manual_grading_and_finalization_remain_allowed | ScoringController + Teacher ResultDetail behavior | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |
| WA01-P-008 | guru_reports_exports_and_analytics_remain_scope_limited | ReportController/AnalyticsController | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |
| WA01-P-009 | guru_livewire_hydration_and_actions_remain_unchanged | Guru branches in shared Admin components | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB + Livewire | NOT RUN |
| WA01-S-001 | student_exam_execution_answer_sync_and_submit_remain_unchanged | Student execution/answer/submit API | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |
| WA01-S-002 | student_own_published_result_dto_remains_minimal | Student current-score DTO and visibility | W0 2b4321c | PHP 8.3 + MySQL 8/InnoDB | NOT RUN |

Setiap test saat dijalankan wajib menyimpan source commit aktual, file::method, MySQL server version dan InnoDB confirmation, PHP version, command, exit code, assertion count, stdout/stderr/log, response/serialization evidence, dan reviewer. Semua 36 test ID pada tabel saat ini NOT RUN.

## 7. Response serialization dan leakage checks

Recursive denylist untuk seluruh response API/Livewire/export:
- token, require_token, token history/rotation metadata;
- instructions, question stem, option content, correct answer/key, score_weight, explanation/rubric/marking guide;
- snapshot IDs/payload/order/media and embedded question relations;
- participant/student roster identity, attempt IDs/status/device data, answer payload/revisions;
- score/current pointer/question score/feedback/finalization data;
- report rows, result aggregates, export file content, analytics dimensions.

Uji HTML rendered, Livewire snapshot/dehydrated state, pagination metadata, validation errors, exception response, headers/download, dan cache hit. Admin deny harus terjadi sebelum service/cache/export/serialization sensitif berjalan.

Allowlist metadata assignment: exam id,title,start_at,end_at; assignment history hanya id,title,status,start_at,end_at ditambah nama Proktor/aktor yang memang diperlukan untuk administrasi assignment. Jika data yang ditampilkan melampaui allowlist ini, acceptance gagal.

## 8. Dependency, risiko dan mitigasi

1. Policy sharing: Question/Assessment/Exam/Attempt/Report policies dipakai Guru/Siswa/Proktor juga. Patch harus berupa Admin-specific branch, tanpa refactor TeacherAccess.
2. Livewire shared component: QuestionBank dan ExamManagement berada di namespace Admin tetapi menerima Guru. Guru branch harus tetap sama; jika patch memerlukan perubahan schedule-save atau authoring Guru, hentikan dan minta approval lintas-scope.
3. Role permission bootstrap: bootstrap execution mengubah role_permission pivot. Snapshot sebelum run; idempotency; tidak menghapus akun/role/history. Jangan menghapus role OPERATOR/ADMIN di WA-01.
4. Stale permission/session: policy eksplisit harus deny Admin walau permission lama tertinggal atau relation cache sudah ter-load; uji request baru dan hydrated Livewire.
5. Admin assignment metadata: ProctorAssignmentManagement sudah ada dan query-nya allowlisted; jangan membuat raw Exam API baru. WA-03 status assignment/Guru tidak disentuh.
6. ExamAnswerController: ownership check perlu explicit Admin role denial agar akun multi-role tidak melewati batas.
7. Cache/export: authorization harus sebelum cache read/service/export; prewarm cache dengan Guru lalu request Admin.
8. API routes: routes/api.php tidak diubah untuk menghindari route-group side effects. Semua route existing dipetakan dan controller/policy wajib menolak Admin.
9. Tenant FK: perubahan join/FK pada ExamParticipant/Monitoring ditunda ke WA-04, kecuali temuan P0 yang terbukti mengubah data keluar. Tidak ada migration WA-01.
10. Teacher files: tidak ada file Teacher Livewire/view yang diubah; route/policy denial Admin diuji tanpa mengubah komponen Guru.

## 9. Dampak DB dan migration WA-01

- Tidak ada schema migration.
- Tidak ada seeder execution pada tahap ini.
- Saat WA-01 kelak dijalankan, perubahan RolePermissionBootstrapper mencabut CBT permission grants dari role ADMIN_MADRASAH saat bootstrap dipanggil. Pivot role-permission adalah satu-satunya data DB yang direncanakan berubah; role row, user row, user-role pivot, password, profile, history dan audit record tidak dihapus/merge.
- Sebelum bootstrap dijalankan: export/snapshot seluruh role_permission untuk Admin, daftar permission saat ini, jumlah user per role, dan bukti restore DB. Jalankan pada clone dulu.
- Tidak boleh menjalankan bootstrapper/migration di database produksi selama paket belum disetujui.

## 10. Rencana environment dan urutan pengujian

Target acceptance environment:
- PHP 8.3.x; Laravel dependency set persis dari lockfile source W0.
- MySQL 8.0/InnoDB disposable DB, direstore dari fixture/snapshot representatif; versi server direkam. CI historis #692 menggunakan PHP 8.3.37 dan MySQL 8.0.46, tetapi itu bukan bukti run WA-01.
- Cache store test/Redis sesuai konfigurasi uji; private storage fake untuk file media/export.
- HTTP Feature tests + Livewire tests. Tidak ada test terhadap production DB.
- Runtime lokal saat audit sebelumnya tidak tersedia; sebelum eksekusi pastikan Composer/vendor, .env.testing, PDO MySQL, DB fixture dan CI environment tersedia.

Urutan:
1. Source diff terhadap W0 dan verify backup SHA-256 sebelum patch.
2. Policy tests untuk setiap role/method.
3. API negative tests per route/method dan body serialization.
4. Livewire mount/hydration/action tests.
5. Web route tests untuk monitoring/results/analytics/export.
6. Guru positive regression dan Student self-service regression.
7. Cache prewarm, media/private storage, export denial.
8. RolePermissionBootstrapper idempotency/pivot snapshot test.
9. Focused suite, lalu seluruh Feature suite pada MySQL 8/InnoDB.
10. Review manual HTML/JSON/export/log dan security diff.

## 11. Rollback dan backup saat implementasi kelak

- Sebelum patch pertama, fetch source branch dan pastikan setiap blob SHA sama dengan manifest; jika berbeda, stop dan audit ulang.
- Backup source aktual sekali lagi di workspace/source branch target sesuai aturan proyek dan simpan manifest GitHub; backup pada branch dokumentasi ini adalah preservation pin, bukan pengganti backup pre-patch pada source workspace.
- Patch satu work package/commit terukur, tanpa menggabungkan WA-02/03/04/05/06.
- Rollback code dengan revert commit terkontrol atau restore file dari backup byte-identical.
- Permission pivot rollback harus berdasarkan snapshot before-state. Jangan mengembalikan grant CBT Admin sebagai rollback karena membuka kembali temuan P0. Rollback hanya memulihkan non-CBT grant yang terbukti diperlukan; batas deny Admin tetap.
- Tidak ada schema rollback WA-01 karena tidak ada migration. Jika bootstrap sudah dijalankan, jangan menghapus akun/role/user-role/history.
- Setelah rollback, ulangi negative Admin tests, Guru positive tests, dan permission pivot diff.

## 12. Acceptance criteria terukur WA-01

WA-01 hanya ACCEPTED jika:
1. Semua route/API method pada matriks test menolak Admin untuk konten/operasi CBT; tidak ada jalur alternatif.
2. Admin dengan stale CBT permission tetap ditolak policy/controller.
3. Admin multi-role (misalnya Admin+Siswa) tidak dapat memakai student execution/answer/result path untuk membaca atau mengubah CBT.
4. Response JSON, HTML, Livewire state, export dan cache tidak memuat field/data sensitif; allowlist metadata assignment saja yang boleh tampil.
5. Admin dapat membuka /admin/proctor-assignments dan hanya menerima metadata allowlist; assignment action tetap melalui exam.proctor.assign.
6. Guru tetap dapat membuat/mengelola bank soal, assessment, exam, token, participant, monitoring, grading/finalization, report/export/analytics sesuai permission dan TeacherAccess W0.
7. Siswa tetap dapat menjalankan ujian, menyimpan jawaban, submit, dan melihat hanya DTO hasil miliknya yang sudah dipublikasikan/final.
8. Tidak ada file Teacher Livewire/view, Android, migration, .env/config, atau shared schedule path yang berubah.
9. RolePermissionBootstrapper idempotent; Admin CBT grants dicabut; exam.proctor.assign dan administrative grants tetap.
10. Semua test ID memiliki evidence lengkap; seluruh test wajib PASS, tidak ada NOT RUN/BLOCKED/FLAKY.
11. Source diff hanya pada 23 source files + test files yang disetujui; setiap source backup SHA-256 cocok.
12. Security review dan user approval acceptance terpisah selesai. Lulus test tidak otomatis berarti siap produksi.

## 13. Keputusan DESAIN status aktif Guru — TERPILIH, belum diimplementasikan

Keputusan pengguna: dua status eksplisit:
- users.account_status: ACTIVE, SUSPENDED, INACTIVE.
- teacher_profiles.employment_status: ACTIVE, ON_LEAVE, INACTIVE, ENDED.

Guru hanya eligible untuk penugasan Proktor jika kedua status ACTIVE, role GURU, tepat satu TeacherProfile valid dan tenant-local, user/profile tidak dihapus, serta permission/scope/assignment checks lolos.

### 13.1 Transisi data lama dan default aman
- Migration kelak additive; kolom baru NOT NULL dengan default INACTIVE (atau tahap nullable sementara dengan seluruh NULL diperlakukan tidak eligible sebelum backfill). Tidak ada default ACTIVE.
- Seluruh akun/profil lama pada backfill awal masuk INACTIVE. Hanya verifikasi manual berbasis data kepegawaian/madrasah yang dapat mengubah ke ACTIVE; simpan actor, timestamp, alasan, bukti dan audit.
- Akun ambigu, role ganda, profil hilang/duplikat, tenant mismatch, soft-deleted profile, atau identitas tidak cocok tetap INACTIVE dan masuk NEEDS_REVIEW. Tidak ada auto activation, auto merge, restore, atau delete.
- User dengan role legacy PROKTOR tidak otomatis dikonversi menjadi GURU. Akun dan riwayat tetap; transisi role adalah WA-02 dan assignment WA-03.
- SUSPENDED hanya jika ada keputusan penangguhan terverifikasi; ON_LEAVE hanya jika cuti terverifikasi; ENDED hanya jika hubungan kerja berakhir terverifikasi. Jangan menyimpulkan dari soft-delete.
- Status ini pertama kali dipakai sebagai syarat eligibility assignment Proktor WA-03. Perubahan login, global account lockout, session/token revocation, atau semua fitur Guru lain tidak termasuk tanpa keputusan tambahan.

### 13.2 Indeks dan constraint
- Kandidat indeks: users(tenant_id, account_status, id); teacher_profiles(tenant_id, employment_status, deleted_at, user_id).
- Kandidat integritas one-profile-per-user: unique teacher_profiles(tenant_id,user_id) dan composite tenant FK ke users(tenant_id,id), hanya setelah audit duplicate/orphan dan koordinasi WA-04. Jika duplikat ditemukan, migration berhenti; tidak ada penghapusan otomatis.
- Status domain disimpan sebagai VARCHAR terbatas dengan CHECK constraint yang tervalidasi pada MySQL 8.0.16+; versi server wajib diperiksa. Jika target MySQL lebih lama, desain constraint harus direvisi sebelum migration, bukan silently degrade.
- Indexes/constraints existing harus diinventarisasi agar tidak membuat duplikat/lock tidak perlu.

### 13.3 Preflight, backup, compatibility dan rollback
1. Baca seluruh migration/model/factory/seeder User dan TeacherProfile; introspeksi schema clone MySQL 8, tipe UUID, nullability, collation, indexes, FK, deleted_at, unique keys.
2. Profiling read-only: hitung Guru per tenant; missing/duplicate profile; user/profile tenant mismatch; role ganda; soft-deleted profiles; orphan links; legacy PROKTOR accounts; assignment rows/status/time.
3. Ekspor mapping data lama dengan alasan klasifikasi; semua record awal INACTIVE. Siapkan manual review list, tidak ada UPDATE produksi.
4. Backup DB konsisten (logical + physical sesuai operasi), checksum file, simpan backup di lokasi aman, dan lakukan restore drill pada clone.
5. Migration apply/rollback diuji pada clone MySQL 8/InnoDB; ukur metadata lock/downtime. MySQL DDL dapat implicit commit, jadi rollback bukan asumsi transaksi.
6. Deploy additive schema terlebih dahulu setelah approval; kode lama harus mengabaikan kolom baru. Kode eligibility fail-closed terhadap NULL/unknown.
7. Rollback kode terlebih dahulu dan pertahankan kolom untuk kompatibilitas. Drop column hanya melalui keputusan terpisah setelah backup/restore teruji; jangan kehilangan status yang sudah diverifikasi.
8. Status ACTIVE tidak pernah dipulihkan otomatis saat rollback. Semua perubahan status diaudit.

### 13.4 Acceptance status Guru aktif (khusus WA-03 kelak)
- ACTIVE + ACTIVE + GURU + satu profile tenant-local valid: eligible bila seluruh scope checks lolos.
- Salah satu status SUSPENDED/INACTIVE/ON_LEAVE/ENDED, NULL/unknown, missing/duplicate profile, deleted profile, role bukan GURU, atau tenant mismatch: deny.
- Dua koneksi MySQL 8/InnoDB menguji assignment/revoke/status transition bersamaan; SQLite tidak dapat menjadi bukti concurrency.

## 14. Urutan work package berikutnya (rencana, bukan approval)

1. WA-01 — Admin CBT boundary.
2. WA-04 — tenant identity, User scope, relasi dan composite FK.
3. WA-05 — authorization CRUD kurikulum.
4. WA-06 — Livewire reauthorization dan integritas AuditLog.
5. WA-02 — konsolidasi role dan transisi akun legacy.
6. WA-03 — Admin assignment core dengan dua status Guru terpilih; shared Guru integration tetap memerlukan approval tersendiri.
7. WA-07 — penyempurnaan modul Admin.

Setiap WP memerlukan backup, checkpoint, test evidence dan approval gate sesuai risikonya.

## 15. Gerbang persetujuan

Paket ini BELUM disetujui untuk coding. Persetujuan eksplisit diperlukan atas daftar source/test files, batas shared component/policy yang menjaga Guru tetap tanpa perubahan perilaku, keputusan WA-01 tanpa migration dan dampak permission pivot saat bootstrapper kelak dieksekusi, serta test plan dan acceptance criteria.

Sampai persetujuan diberikan, berhenti pada dokumentasi. Tidak ada source write, DB write, migration, seeder execution, deployment, source branch write atau PR action.
