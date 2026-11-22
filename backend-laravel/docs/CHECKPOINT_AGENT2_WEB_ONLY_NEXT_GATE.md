# Checkpoint Agent 2 — Web-only Next Gate

Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4d-account-lifecycle-20261122
Source runtime yang diaudit: 74bf7178f46673e9be90523671490c0749034acd
HEAD Agent 2 sebelum dokumentasi Web-only: 7d63ea3b70b5191530c2c92c5c86bf92d7324db6
Parent HEAD sebelum checkpoint ini: e90233f79acc67b97c329bc4b33d20abbedd0317

Status: **BLOCKED — EXACT-ROLE/SECURITY DESIGN PENDING**
Status implementasi: **GATED — TIDAK ADA IZIN PATCH RUNTIME**

## 1. AUDIT — scope dan source

### Scope terbaru

Sampai Juni 2027, produk yang dikerjakan hanya:
1. Web Admin Madrasah.
2. Web Guru.
3. Web Siswa.

Web Super Admin, Android native, Android Studio, Kotlin/Compose/Room/WorkManager/Retrofit, Android-specific integration dan Android release pipeline ditunda. Tidak ada pekerjaan baru untuk dua produk tersebut.

Satu ZIP distribusi Web CBT akan dipasang secara terpisah pada server/hosting setiap madrasah. Satu instalasi = satu madrasah = satu database akademik, storage, kredensial, konfigurasi, domain dan backup sendiri. tenant_id tetap menjadi defense-in-depth di dalam instalasi; tidak ada shared academic database atau academic sync antarmadrasah.

### Branch dan HEAD aktual saat refresh

| Owner | Branch | HEAD aktual saat refresh | Catatan |
|---|---|---|---|
| Agent 1 | work/stage4b-room-session-20261122 | 51571ca54ec922466e6230b4e8137a85336bc024 | 10 commit sesudah d253279; delta hanya dokumen/manifest. Source Stage 4B baseline tetap d2532792471966f1d0a9d9cbdd4d65e7c84547f0. |
| Agent 2 | work/stage4d-account-lifecycle-20261122 | e90233f79acc67b97c329bc4b33d20abbedd0317 | Source runtime tetap 74bf7178; seluruh perubahan sejak runtime SHA berupa dokumentasi. |
| Agent 3 | work/web-guru-foundation-20261122 | aa17b814e6ca2327a4e386d38105760d9515f25b | Test source commit 88b5c135; perubahan setelah baseline mencakup test dan dokumentasi/backup, bukan perubahan policy/backend Agent 2. |
| Kandidat Agent 4 | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | Owner belum dikonfirmasi; jangan mengubah atau menganggap branch resmi. |
| Dokumentasi Agent 4 | work/agent4-web-siswa-audit-20261122 | 16c7d324ef9d7103bb21f0c5528c3c888ba19568 | Branch dokumentasi berbasis main, bukan branch implementasi. |
| main | main | feab77228f7108f7f7a45325d7f1fb878c07310b | Bukan target patch Agent 2. |

Semua branch divergen. Perbandingan terhadap Agent 1: Agent 2 227 ahead/98 behind; Agent 3 198 ahead/243 behind; kandidat Agent 4 80 ahead/366 behind; main 15 ahead/1216 behind. Tidak ada PR integrasi Agent 2 yang ditemukan pada pencarian head branch. Tidak ada merge/rebase/cherry-pick dilakukan.

### Source runtime utama

- LocalInstallationIdentity d6f36438e12be0c64b8a9ecaa3d4e03994ea2d8c.
- LoginIdentityResolver c1f56029da5b8fdb5d7f80380269637b7d1c3ebe.
- TenantContext 171261b61ee7f551e3c1b1eb4335b169a1df0d8d.
- IdentifyTenant 376a4b2771c3d9a6b128ad847c150d47ac2977db.
- AuthWebController b8e04102858652c2fe9042f8f680c511fcbccf63.
- API AuthController ed0d7c9f51de4190c7f150b92c27a89e93703b40.
- ExamPolicy c05d96ef932fdc81b67336240af4e1726864c3d6.
- AssessmentPolicy 1aea2b90d92139c9c6935c60e94ca43e825abb29.
- StudentExamController 981fd29d24906c158d4dda5889950fdc43f6ec3d.
- ExamMonitoringScope 11b455954f974d1c60ed5c49700368f8d37c09c1.
- CurriculumAnalyticsController 2fe90e6a3bbf398be69260d053e09471bb886396.
- ExamAnswerController b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c.
- ExamAttemptController 13e8981cca4653ff4e9b9448a50e4d0ec8a74bae.
- ExamSummaryReportService 45d8f97eb9b2c75164a6f10a403e052b77696552.
- ExamSnapshotService d0bfcd70b2777a3884c7f92b7c256f05492645ec.
- ScoringService 1fc74eae97538ada140b9b4c75ccd4388d1a40ad.
- ImportBatch 1c95378302b8d3368a781c9ec224e29cc8d0d8b9.
- ImportBatchRow c47c736e0a09732e7b9a1c44592057515e2a4cff.

### Temuan P0

- Exact-role belum konsisten pada ExamPolicy, AssessmentPolicy, ExamAttemptPolicy, ReportPolicy, StudentExamController, MediaController, ExamMonitoringScope, CurriculumAnalyticsController, LicenseStatusController dan entrypoint lain.
- AuthWebController/API AuthController/LoginIdentityResolver masih menerima KAMAD. Redirect KAMAD ke root berpotensi loop. KAMAD bukan portal.
- LicenseStatusController masih mengenali OPERATOR.
- IdentifyTenant mengabaikan X-Tenant-Override (pertahankan), tetapi belum membandingkan user tenant dengan tenant instalasi dan host lokal pada setiap request.
- LoginIdentityResolver membatasi satu LocalInstallationIdentity dan satu Tenant; registered_domain belum dipakai sebagai verifikasi host.
- config/database.php membaca kredensial installer dari storage/app/private; InstallerService memeriksa MySQL 8+/InnoDB/database kosong. Redis prefix default berbasis APP_NAME. Storage/cache/queue/session config belum terverifikasi.
- Device Lease/Fencing belum memiliki bukti lifecycle runtime end-to-end; fingerprint/header bukan fencing generation.
- Web Student runner menggunakan API untuk exam/media/answer/submit; endpoint tersebut tidak boleh dihapus hanya karena Android ditunda.

### Temuan P1

- ExamSummaryReportService mengagregasi seluruh histori ExamAttemptScore, bukan hanya current pointer FINALIZED.
- StudentResultReportService sudah memakai current pointer untuk finalized_only. ExamResultReportService memakai relation score yang melalui current pointer; tetap perlu regression test.
- PDF belum dinyatakan aman untuk membedakan belum dinilai dengan nilai final 0.
- Immutable ReportSnapshot dan penerbitan PDF dari snapshot belum ditemukan.
- Import XLSX/DOCX memerlukan satu DTO dan satu lifecycle. SHORT_ANSWER tetap BLOCKED karena accepted_answers belum tersimpan end-to-end dan tidak disalin oleh ExamSnapshotService.

## 2. DESAIN — keputusan dan status

### Exact-role

Role portal: ADMIN_MADRASAH, GURU, SISWA. KAMAD bukan portal. OPERATOR bukan portal. Proktor adalah assignment Guru.

Setiap capability harus memeriksa user persisted, tepat satu role, tenant instalasi/resource, permission, assignment/ownership, dan resource scope. Kombinasi multi-role ditolak. Service direct invocation, Livewire lifecycle, API, Web, media, export dan job harus diuji.

### Instalasi

Satu instalasi memiliki satu database akademik. Domain/host harus diverifikasi lokal. User tenant harus sama dengan tenant instalasi. Tidak ada browser tenant selection atau domain registry akademik pusat. Storage/cache/queue/session/log dan backup/restore wajib terisolasi.

### Lock order

Agent 1 mengusulkan parent-first global lock order:
Exam → Room(s) → Session → SessionRoom(s) → Rombel(s) → Participant(s) → Placement(s) → Proctor Assignment(s) → User(s) → Attempt → Answer → Score → Device Lease/Fencing.

Untuk Attempt-only:
Exam → Participant → Attempt → Answer → Score/current pointer → Lease/Fencing.

Usulan ini BELUM DISETUJUI Agent 2/3/4. Jangan mengubah source Stage 4B atau menganggap lock order final. ExamAnswerController update masih Attempt → Answer. Exam update/archive/publish/destroy perlu transaksi, Exam lock dan re-authorize. Session bukan admission gate; Attempt IN_PROGRESS valid tetap resume setelah close.

### Reporting

Statistik resmi hanya current score pointer yang valid dan FINALIZED. Histori score tetap disimpan. Belum dinilai tidak boleh menjadi angka nol. Penerbitan laporan harus immutable snapshot, hash payload/artifact, idempotency, revision baru dan PDF dari snapshot. Kamad signer berdasarkan appointment yang sah; bukan role portal.

### Import

XLSX/DOCX adalah adapter berbeda menuju satu QuestionDraftDTO dan satu ImportBatch/ImportBatchRow lifecycle. Tidak ada auto-publish. PDF/OCR ditunda. ESSAY manual review. SHORT_ANSWER tetap BLOCKED. Tidak ada Composer dependency, migration atau parser baru sebelum approval.

## 3. IMPLEMENTASI — dokumentasi saja

Enam dokumen baru dibuat pada branch Agent 2:
1. AGENT2_SCOPE_WEB_ONLY_AND_INSTALLATION_BOUNDARY.md
2. AGENT2_EXACT_ROLE_BACKEND_MATRIX.md
3. AGENT2_SINGLE_MADRASAH_DATABASE_ISOLATION.md
4. AGENT2_EXAM_ATTEMPT_LOCK_ORDER_RECONCILIATION.md
5. AGENT2_REPORTING_CURRENT_SCORE_IMMUTABLE_SNAPSHOT.md
6. AGENT2_IMPORT_XLSX_DOCX_CONTRACT_STATUS.md

Dokumen ini adalah checkpoint ketujuh. Seluruh path keluaran diperiksa tidak ada sebelum dibuat; semuanya file baru. Tidak ada file existing yang ditimpa, sehingga tidak ada backup overwrite. Tidak ada source runtime, test, workflow, migration, schema, dependency, config, database, branch Agent 1/3/4 atau deployment yang diubah.

## 4. PENGUJIAN — bukti aktual

| Run | SHA | Hasil |
|---|---|---|
| Agent 1 #44984303003 | f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5 | SUCCESS; PHPUnit dan MySQL disposable steps selesai. Bukan source d253279/current Agent 1 HEAD. |
| Agent 2 #45003796892 | 74bf7178f46673e9be90523671490c0749034acd | Workflow/job failure, steps kosong; PHPUnit UNKNOWN. |
| Agent 2 #45008591113 | 32d383eb1564cdbcf084b8677fe8b750a4b45a11 | Workflow/job failure, steps kosong; PHPUnit UNKNOWN. |
| Agent 3 #45003191589 | 834a59f11f9b548c8d7f0fcea68c15cf4bbe501f | 18 passed, 2 failed, 86 assertions. |
| Agent 3 #45009039557 | 88b5c1354971bfb7b03d715014b40b9026f1fbed | Job failure, steps kosong, log/artifact tidak tersedia; PHPUnit UNKNOWN. |

Tidak ada PHPUnit baru, browser E2E, MySQL disposable preflight, concurrency, report snapshot, import parser, multi-installation, backup restore atau UAT yang dijalankan. Database produksi tidak disentuh.

## 5. GATE IMPLEMENTASI

Implementasi backend belum dibuka. Sebelum patch runtime:
1. Selesaikan exact-role matrix dan negative tests seluruh backend.
2. Tutup KAMAD bukan portal dan legacy OPERATOR.
3. Finalisasi host/domain trust dan tenant installation boundary.
4. Buktikan storage/cache/queue/session/backup isolation.
5. Dapatkan approval lintas-agent atas lock order dan recovery.
6. Finalisasi current score dan immutable report lifecycle.
7. Finalisasi DTO/import contract dan approval Agent 3.
8. Dapatkan CI PHPUnit PASS pada exact source SHA.
9. Selesaikan preflight MySQL 8 disposable.
10. Koordinator memberi izin eksplisit patch runtime.

Persetujuan desain bukan izin migration. Izin patch bukan izin deployment. Tidak ada deployment atau database produksi.

## 6. NEXT ACTION

Owner Agent 2:
- lanjutkan audit exact-role dari entrypoint yang belum masuk matrix;
- audit dependency TenantContext/IdentifyTenant dan domain binding;
- inventaris API call-site Web sebelum memutuskan endpoint legacy;
- finalisasi lock order bersama Agent 1;
- finalisasi report snapshot dan import DTO.

Koordinator:
- konfirmasi owner resmi Agent 4;
- minta approval Agent 3 pada kontrak import;
- pulihkan/diagnosis CI runner berdasarkan log/metadata exact SHA;
- berikan izin patch runtime secara terpisah setelah seluruh gate terpenuhi.

**Status akhir: BLOCKED — EXACT-ROLE/SECURITY DESIGN PENDING.**
Web Siswa tetap NOT READY FOR OPERATIONAL EXAM. Device Lease/Fencing belum siap produksi. Super Admin dan Android tetap ditunda sampai Juni 2027.
