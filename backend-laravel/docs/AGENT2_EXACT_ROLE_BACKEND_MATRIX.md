# Agent 2 — Matriks Exact-Role Backend

Tanggal: 22 November 2026
Branch: work/stage4d-account-lifecycle-20261122
HEAD sebelum dokumentasi: 7d63ea3b70b5191530c2c92c5c86bf92d7324db6
Source runtime: 74bf7178f46673e9be90523671490c0749034acd

## AUDIT — Kontrak yang diwajibkan

Setiap capability wajib memvalidasi:
1. User persisted dan tidak soft-deleted.
2. Tepat satu role aktif yang diizinkan untuk capability tersebut.
3. Tenant user sama dengan tenant instalasi dan resource.
4. Permission sesuai capability.
5. Assignment/ownership masih aktif dan berlaku.
6. Resource scope dan relasi tenant valid.
7. Pemeriksaan diulang pada request/action/service yang dapat dipanggil langsung; bukan hanya saat render UI.

Role portal yang aktif:
- ADMIN_MADRASAH
- GURU
- SISWA

KAMAD bukan portal. OPERATOR adalah role legacy dan tidak boleh menjadi jalur portal operasional. Proktor adalah penugasan sementara kepada Guru, bukan role permanen.

Kombinasi multi-role wajib ditolak, termasuk GURU+SISWA, ADMIN_MADRASAH+GURU, ADMIN_MADRASAH+SISWA, KAMAD+role portal, dan kombinasi legacy lain. Jangan mengganti hasRole() secara mekanis; tentukan role yang benar per capability.

## AUDIT — Matriks source

| File/class | Blob SHA | Temuan statis | Status |
|---|---|---|---|
| ExamPolicy | c05d96ef932fdc81b67336240af4e1726864c3d6 | Banyak method memakai hasRole GURU/SISWA atau pengecualian ADMIN; jalur KAMAD masih ada. startAttempt belum membuktikan tepat satu SISWA. | P0 NON-EXACT |
| AssessmentPolicy | 1aea2b90d92139c9c6935c60e94ca43e825abb29 | viewAny/view mengenali KAMAD; create/update/delete memakai hasRole GURU, bukan exact-role. | P0 NON-EXACT |
| ExamAttemptPolicy | 00a3a4119f3983de6d940fd8f352a541336c9e12 | view/viewScore mengenali KAMAD; score/finalize/reopen memakai hasRole GURU. | P0 NON-EXACT |
| ReportPolicy | 0fbd934bde79ebd7ae3fe4fe08f09cb4dc9e46ce | Mengizinkan KAMAD untuk laporan; role GURU/ADMIN menggunakan hasRole. | P0 NON-EXACT |
| StudentExamController | 981fd29d24906c158d4dda5889950fdc43f6ec3d | index/show/result memeriksa SISWA dan bukan ADMIN, bukan tepat satu SISWA. Role campuran GURU+SISWA dapat melewati pemeriksaan awal. | P0 NON-EXACT |
| LicenseStatusController | 2e6a718c54e8aac573790cf3b117c1822759f72b | Mengizinkan ADMIN_MADRASAH atau OPERATOR melalui whereIn; belum exact-role Admin. | P0 LEGACY ROLE |
| TeacherAccess | 766289c5163b6cf367d511c0bf35207ccbc97684 | Memeriksa assignment/tenant/rombel/mapel, tetapi bukan pengganti exact-role boundary. | NEEDS CALLER GUARD |
| ExamMonitoringScope | 11b455954f974d1c60ed5c49700368f8d37c09c1 | KAMAD mendapat seluruh participant query; exam author memakai hasRole GURU. | P0 KAMAD/NON-EXACT |
| MediaController | f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62 | Beberapa jalur memakai GURU dan bukan ADMIN; jalur owner USER perlu kontrak role yang terpisah. | P0 NON-EXACT / REVIEW |
| ExamParticipantController | Perlu verifikasi ulang blob pada inventory endpoint | Pemeriksaan SISWA pernah ditemukan berbasis hasRole. | P0 REVIEW |
| ExamRoomSessionService | Blob/entrypoint perlu direkonsiliasi dengan branch Agent 1 | Admin permission checks bukan exact-role proof; room/session ownership Agent 1. | CROSS-AGENT |
| ImportWorkbookService | Path/entrypoint perlu dipastikan pada tree branch terkini | Service invocation harus exact GURU + permission + assignment. | P0 REVIEW |
| CurriculumAnalyticsController | 2fe90e6a3bbf398be69260d053e09471bb886396 | Rombel/subject/TP analytics memakai kombinasi negasi hasRole Admin/Guru; role campuran dapat masuk cabang Guru. | P0 NON-EXACT |
| AuthWebController | b8e04102858652c2fe9042f8f680c511fcbccf63 | Allowed roles masih memuat KAMAD; redirect KAMAD menuju /. | P0 KAMAD PORTAL |
| API AuthController | ed0d7c9f51de4190c7f150b92c27a89e93703b40 | Allowed roles masih memuat KAMAD. | P0 KAMAD PORTAL |
| LoginIdentityResolver | c1f56029da5b8fdb5d7f80380269637b7d1c3ebe | Allowed roles masih memuat KAMAD; tenant resolver perlu domain-bound check. | P0 KAMAD / TENANT |
| IdentifyTenant | 376a4b2771c3d9a6b128ad847c150d47ac2977db | Mengabaikan X-Tenant-Override (baik), tetapi masih memiliki cabang SUPER_ADMIN bypass dan memakai tenant user tanpa verifikasi instalasi. | P0 INSTALLATION BOUNDARY |
| QuestionPolicy | Blob pada audit sebelumnya perlu dicatat ulang pada matrix final | Helper hasExactGuruPermission dan exact GURU disebut pada audit terdahulu. | STRONGER PATTERN |
| ExcelQuestionImporter | 8a8951b0427506a56a4bfc700fbf4cf7feb7a214 | assertQuestionAuthor adalah service-level guard; exact one GURU tercatat pada audit terdahulu. | STRONGER PATTERN |
| ExamAnswerController | b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c | index/update memeriksa roleNames count=1 dan SISWA. | EXACT ROLE PATTERN |
| routes/web.php | f012b3de23575e3ef91c8f07ad1c0d7ae780fb21 | Root hanya redirect tiga portal, tetapi AuthWebController masih mengenali KAMAD. | INCONSISTENT |
| routes/api.php | 2e43c37dd669a26446a44b818c1d2c8420b15aca | Endpoint memakai auth:sanctum/tenant/license; route middleware tidak menggantikan role/policy/service guard. | CALLER GUARD REQUIRED |

Catatan: beberapa path/entrypoint perlu rekonsiliasi tree dan blob sebelum matrix dinyatakan final. Status ini bukan klaim seluruh backend sudah diinventarisasi.

## DESAIN

- Buat satu helper/boundary exact-role yang memeriksa role dari database dan user persisted, tetapi gunakan hanya pada capability yang sesuai.
- KAMAD dihapus dari allow-list login portal, policy, monitoring, report access dan redirect. Jangan menghapus akun/histori/role legacy tanpa rencana migrasi yang disetujui.
- OPERATOR tidak menjadi portal; kompatibilitas URL lama tidak berarti hak akses OPERATOR.
- ADMIN_MADRASAH hanya mendapat capability administrasi yang ditetapkan. Tidak boleh memperoleh question authoring, token, Attempt, Answer, answer key, scoring, result detail, atau live exam monitoring hanya karena memiliki permission administratif.
- Guru harus memiliki role tunggal GURU, permission, TeacherProfile lokal yang sah, serta assignment mapel/rombel/tahun ajaran/semester yang aktif.
- Siswa harus memiliki role tunggal SISWA, tenant yang sesuai, participant ownership, eligibility, dan akses hanya ke attempt/hasil miliknya.
- Proktor memperoleh monitoring terbatas berdasarkan assignment Guru yang masih aktif dan room scope; bukan role tambahan.

## TEST WAJIB

- Single valid role untuk setiap portal.
- Setiap pasangan multi-role penting ditolak.
- KAMAD tunggal dan KAMAD+role portal ditolak sebagai login portal.
- OPERATOR tunggal ditolak.
- User soft-deleted dan TeacherProfile soft-deleted ditolak.
- Permission revoked, assignment revoked, role berubah setelah mount ditolak.
- TenantContext berubah dan tenant mismatch ditolak.
- Direct service invocation tidak dapat melewati controller/policy.
- Cross-tenant Question/Exam/Attempt/Media ID tidak membocorkan resource.
- Student tidak dapat endpoint Guru; Guru tidak dapat endpoint Student.
- Admin tidak dapat operasi CBT, answer-level, scoring, result-detail, atau live monitoring.
- API, Web route, Livewire boot/hydrate/action, file/media, export, job/scheduler diuji.

## IMPLEMENTASI DAN PENGUJIAN

Tidak ada source/test/migration yang diubah. Tidak ada PHPUnit yang dijalankan pada tahap ini. Exact-role belum lulus integrasi.

Owner: Agent 2 untuk policy/API/service/auth. Agent 3 hanya untuk boundary komponen Web Guru dan tidak mengambil alih source Agent 2.