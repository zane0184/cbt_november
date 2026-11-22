# Agent 2 — Continuation Audit: Auth, Tenant Identity, Reporting, Lifecycle

Tanggal audit: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/stage4d-account-lifecycle-20261122`  
Source baseline HEAD: `74bf7178f46673e9be90523671490c0749034acd`  
Status: **AUDIT/DESIGN CONTINUATION COMPLETE — IMPLEMENTATION GATED**

> Dokumen ini merupakan hasil audit read-only dan desain. Tidak ada migration/schema, database, runtime source, UI Agent 3, UI Siswa Agent 4, atau branch agent lain yang diubah.

## 1. AUDIT

### 1.1 Refresh source dan kontinuitas

- Ref branch Agent 2 dibaca ulang. HEAD saat audit tetap `74bf7178f46673e9be90523671490c0749034acd`; tidak ada commit baru sesudah source patch yang dilaporkan sebelumnya.
- Commit `74bf7178...` hanya mengubah fixture test `backend-laravel/tests/Feature/AdminCbtBoundaryTest.php` agar memakai guard eksplisit `$this->actingAs($admin, 'web');`.
- Blob source aktif: `f15a6a0c5621e9c19bacbb9a2769913750aa1afe`.
- Backup byte-exact: `backend-laravel/docs/backup/stage4d-account-lifecycle-2026-11-22/AdminCbtBoundaryTest.php.before-explicit-web-actingAs-2026-11-22`, blob `76fc79b9e22cf52efe05e8deadb0ef1a6ca91376`.
- Backup commit: `a48cc115fa25a292d297c20d2b7f7bf99c18d506`.
- Backup blob sama dengan blob source sebelum patch. SHA-256 lokal tidak dihitung karena file hanya diperiksa melalui GitHub contents API; Git blob SHA dicatat sebagai bukti identitas byte.
- Manifest `docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md`, blob `1d14aece347193de9822b80608718dc320b06f8d`, diperiksa. Manifest berisi backup fixture Admin CBT yang lebih lama, tetapi belum mencatat backup explicit-web-guard di lokasi `backend-laravel/docs/backup/...`. Ini adalah gap dokumentasi backup, bukan bukti bahwa file backup hilang.

### 1.2 Regresi autentikasi P0

Run yang relevan harus dibedakan berdasarkan SHA:

| Run | SHA | Hasil |
|---|---|---|
| #45000004149 | `a9c9e0b0dd0e876ff7eaac040e73202bc0b4438b` | Run lama, bukan source patch; mencapai PHPUnit dan gagal pada fixture tanpa guard eksplisit |
| #388 / ID `45003796892` | `74bf7178f46673e9be90523671490c0749034acd` | **BLOCKED — RUNNER** |

Run #388 ditemukan melalui pencarian Actions berdasarkan `head_sha`. Tiga attempt (1, 2, 3) selesai gagal dalam beberapa detik dengan `runner_id=0`, `runner_name=""`, dan `steps=[]`. Tidak ada langkah PHPUnit yang berjalan dan tidak ada log/artifact test yang dapat digunakan. Karena itu:
- patch fixture tidak boleh dinyatakan PASS;
- tidak ada bukti source patch gagal;
- status acceptance Admin CBT boundary tetap **BLOCKED — RUNNER**.

Tidak dilakukan patch fixture kedua. Tidak ada perubahan middleware, policy, route, atau autentikasi untuk memaksa PASS.

### 1.3 Exact-role seluruh jalur backend

Inventaris read-only mencakup 99 file PHP pada kelompok Policy, Middleware, Controller/API, Livewire, Service, Support, dan Provider. Pemeriksaan berfokus pada `hasRole`, permission checks, Gate/Policy, tenant scope, dan service entrypoints. Hasilnya tidak mendukung klaim bahwa exact-role sudah konsisten.

**Kontrol yang sudah lebih kuat:**
- `QuestionPolicy` menggunakan pemeriksaan role tunggal pada jalur authoring.
- `ExcelQuestionImporter::assertQuestionAuthor()` mengambil role dari relasi server-side dan mewajibkan tepat satu role `GURU`, lalu permission `question.manage`.
- `ExamAnswerController` memeriksa tepat satu role `SISWA` pada endpoint jawaban.
- `ExamPolicy::monitor()` memiliki pemeriksaan tepat satu role `GURU` untuk jalur monitoring Guru.
- `ExamProctorAssignmentService` memiliki pemeriksaan kelayakan Guru yang lebih ketat.

**Gap yang terbukti:**
- `AssessmentPolicy`, sebagian besar `ExamPolicy`, `ExamAttemptPolicy`, dan `ReportPolicy` masih menggunakan `hasRole()` pada sejumlah kemampuan. Contohnya `ExamPolicy::viewAny()` menerima salah satu role KAMAD/GURU/SISWA; `view()` memilih cabang Guru sebelum Siswa; `startAttempt()` hanya memeriksa keberadaan role Siswa. Akun campuran dapat masuk cabang kemampuan yang salah.
- `BasePolicy` berorientasi permission; policy turunannya seperti `CurriculumPolicy`, `LearningOutcomePolicy`, `MaterialPolicy`, dan `PhasePolicy` belum menjadikan exact-role sebagai prasyarat mandiri.
- `CurriculumAnalyticsController` menerima Admin atau Guru dengan pemeriksaan keberadaan role, bukan satu role eksklusif.
- `MediaController` dan `StudentExamController` menggunakan pola keberadaan role dan pengecualian Admin, bukan kontrak tepat satu role.
- `LicenseStatusController` masih mengenali role legacy `OPERATOR`.
- `ExamRoomSessionService` dan `ImportWorkbookService` memiliki pemeriksaan Admin berbasis `hasRole()` pada service entrypoint.
- `ExamMonitoringScope` dan `ExamPolicy` masih menyediakan jalur KAMAD untuk monitoring/view. Ini bertentangan dengan keputusan bahwa Kamad bukan portal operasional permanen.
- `TenantProfileManagement` memakai role Admin + permission; boundary-nya juga perlu exact-role.
- Pemeriksaan permission saja tidak boleh menjadi pengganti role. Role harus diperiksa dari relasi DB yang segar, bukan relasi yang mungkin tertinggal di cache model.

**Matriks kontrak yang dituju:**

| Actor | Role aktual | Kemampuan | Hasil |
|---|---|---|---|
| Admin Madrasah | tepat satu `ADMIN_MADRASAH` | administrasi tenant | Allow |
| Admin Madrasah | tepat satu `ADMIN_MADRASAH` | bank soal, authoring, grading, operasi CBT | Deny |
| Guru | tepat satu `GURU` + assignment aktif | bank soal, assessment, ujian sesuai assignment | Allow |
| Guru + Siswa | multi-role | seluruh kemampuan Guru/Siswa | Deny |
| Admin + Guru | multi-role | kemampuan Guru maupun Admin | Deny |
| Admin + Siswa | multi-role | kemampuan Siswa maupun Admin | Deny |
| Siswa | tepat satu `SISWA` | attempt, jawaban, hasil miliknya | Allow |
| Kamad | role legacy `KAMAD` | login/portal permanen | Deny |
| Guru + assignment Proktor | tepat satu `GURU` | monitoring hanya room yang ditugaskan dan masih aktif | Allow |
| Role kosong/tidak dikenal/legacy | bukan salah satu role portal | portal operasional | Deny |

Portal tenant aktif hanya `ADMIN_MADRASAH`, `GURU`, dan `SISWA`. Proktor tetap penugasan sementara pada Guru, bukan role. Super Admin tetap berada pada aplikasi Publisher terpisah.

### 1.4 Auth, guard, tenant resolution, dan branding

- `LoginIdentityResolver` menerima role `ADMIN_MADRASAH`, `KAMAD`, `GURU`, dan `SISWA`; ia menolak multi-role, tetapi mengizinkan KAMAD tunggal.
- `AuthController::login()` API juga memasukkan KAMAD dalam allow-list dan dapat menerbitkan Sanctum token untuknya.
- `AuthWebController` mengarahkan KAMAD ke `/`, sedangkan root route tidak memiliki portal KAMAD dan mengarah kembali ke login. Akun KAMAD berpotensi mengalami redirect loop.
- Resolver mensyaratkan tepat satu `LocalInstallationIdentity` dan tepat satu Tenant di seluruh database. Ini tidak cocok dengan database bersama yang berisi banyak tenant. Jika model deployment sebenarnya satu instalasi/database per tenant, batas tersebut perlu didokumentasikan eksplisit. Keputusan topologi ini belum boleh diasumsikan.
- `LocalInstallationIdentity` memiliki `registered_domain`, tetapi resolver belum menggunakannya untuk memilih tenant sebelum autentikasi.
- `IdentifyTenant` sudah mengambil tenant dari user terautentikasi dan mengabaikan `X-Tenant-Override`; ini dipertahankan.
- `login.blade.php` masih memakai logo huruf C dan teks umum CBT MADRASAH. Branding belum berasal dari identitas resmi tenant.
- `EnsureAccountSessionVersion` memvalidasi auth epoch browser. Setiap perubahan role di masa depan wajib menaikkan auth epoch atau mencabut token agar session lama tidak tetap berwenang; mekanisme perubahan role lintas jalur belum dibuktikan lengkap.

### 1.5 Identitas Tenant, Guru, dan Kamad

Source aktual:
- `Tenant` memiliki `name, code, nsm, npsn, address, phone, email, logo_path, status`; belum memiliki kota/tempat penerbitan dan timezone.
- `TeacherProfile` memiliki `employee_identifier, nip, nik, nuptk, full_name, gender, phone`; belum memiliki status kepegawaian PNS/non-PNS/unknown.
- Migration identitas menambahkan NIP/NIK dan unique tenant-scoped untuk NIP/NIK; NISN juga memiliki unique tenant-scoped. NUPTK belum terbukti memiliki unique constraint.
- `TeacherSubjectAssignment` sudah memiliki subject, rombel, academic year, semester, starts_at, ends_at, status, dan creator.
- Tidak ditemukan relasi resmi Tenant–Kepala Madrasah beserta masa jabatan/histori. Role KAMAD bukan pengganti data pejabat resmi.

Kontrak desain:
1. Tenant menyimpan identitas resmi, NSM/NPSN, alamat, kota penerbitan, IANA timezone, dan metadata logo tenant-scoped.
2. Guru tetap memiliki relasi User–TeacherProfile dan assignment akademik. Tambahkan status kepegawaian eksplisit `PNS | NON_PNS | UNKNOWN`; NIP wajib bagi PNS yang akan menandatangani dokumen resmi. NIP kosong tidak boleh otomatis berarti Non-PNS. Tanda `-` hanya untuk status Non-PNS yang telah diverifikasi.
3. Kepala Madrasah direpresentasikan sebagai histori penugasan pejabat tenant terpisah dari portal User, misalnya `TenantOfficialAppointment`, dengan tenant, nama, status PNS/non-PNS, NIP nullable, mulai/akhir masa jabatan, dokumen dasar, actor pencatat, dan audit. Snapshot laporan memilih pejabat yang aktif pada waktu penerbitan.
4. Unique constraints harus tenant-scoped untuk identitas lokal; identifier nasional yang secara resmi global harus ditetapkan setelah validasi data existing. Semua relasi dan query wajib tenant-scoped; histori memakai relasi `withTrashed()` hanya untuk pembacaan historis.
5. Tidak ada migration yang dibuat dalam tahap ini.

### 1.6 Reporting, current score pointer, dan snapshot

- `ExamAttempt::score()` mengarah ke `ExamAttemptScoreCurrent.current_score_id`; `resolvedCurrentScore()` memvalidasi attempt, tenant, target score, dan scoring_version.
- `StudentResultReportService` menggunakan current pointer dan dapat membatasi hasil ke `FINALIZED`. Ini merupakan pola yang harus dipakai.
- `ExamResultReportService` memuat relasi score aktif melalui pointer; filter status tersedia, tetapi laporan resmi/statistik tetap harus memisahkan nilai final dari status lain.
- **Bug terbukti:** `ExamSummaryReportService` mengagregasi langsung seluruh `ExamAttemptScore` history. `AVG/MAX/MIN` memakai seluruh baris score, termasuk versi lama dan status non-final; hanya `finalized_count` yang menghitung status FINALIZED. Ringkasan dapat salah dan menghitung versi score lebih dari sekali.
- Audit pola yang sama pada `ExamAnalyticsService`, `ItemAnalysisService`, `RombelAnalyticsService`, `StudentAnalyticsService`, dan `SubjectAnalyticsService`: query statistik perlu dialihkan ke current pointer dan filter FINALIZED.
- PDF `exam-results.blade.php` dan `student-results.blade.php` menampilkan 0 ketika score belum ada. Ini mencampur status belum dinilai dengan nilai final nol.
- `ReportHeaderService` mencari logo melalui beberapa path filesystem, termasuk path langsung dari `logo_path`; belum terlihat pembatasan ketat bahwa file harus berada pada storage prefix tenant yang sah. Ini perlu hardening, tetapi belum dinyatakan sebagai exploit.
- Header PDF belum menyimpan identitas tempat penerbitan, timezone, pejabat Kamad resmi, dan Guru penandatangan.
- Tidak ditemukan immutable report issuance/snapshot. PDF dibangun dari data live sehingga perubahan identitas master dapat mengubah hasil cetak historis.

Kontrak desain snapshot:
- Draft dapat berubah sampai diterbitkan; setelah `ISSUED`, payload dan artefak immutable.
- Snapshot minimal memuat tenant/logo (termasuk hash bytes logo), Guru dan assignment yang menjadi dasar penandatangan, Kamad aktif pada waktu penerbitan, periode akademik, exam, peserta, current score, status finalisasi, tempat/waktu/timezone, actor penerbit, schema version, dan report version.
- Guru penerbit harus berasal dari assignment mata pelajaran/rombel/ujian yang relevan, bukan sekadar user yang sedang login. Kamad berasal dari appointment resmi tenant pada waktu penerbitan.
- Idempotensi memakai key per tenant + report type + scope + revision; pengulangan key/payload yang sama mengembalikan issuance yang sama. Hash payload kanonik SHA-256 dan hash artefak PDF disimpan untuk verifikasi.
- Koreksi menghasilkan revision baru yang menautkan issuance sebelumnya; issuance lama tidak ditimpa atau dihapus diam-diam. Pembatalan/revokasi wajib menjadi event audit tersendiri.
- Retensi, otoritas final penerbitan, dan apakah Admin boleh menerbitkan jenis laporan tertentu masih memerlukan keputusan kebijakan. Tidak ada schema dibuat.

Representasi nilai:
- Tidak ada score/current pointer: `Belum dinilai`, bukan 0.
- Score aktif FINALIZED bernilai nol: tampilkan `0`.
- Score aktif FINALIZED bukan nol: tampilkan angka final.
- Reopened/non-final/cancelled: tampilkan status eksplisit dan jangan masukkan ke statistik final.
- Pointer hilang/tidak konsisten: fail closed dan catat integrity event; jangan memilih MAX(scoring_version) sebagai fallback.

### 1.7 Exam lifecycle, lock order, dan fencing

**Yang sudah benar pada source:**
- `ExamAttemptController::store()` mengunci `Exam → ExamParticipant → Attempt` dan re-authorize setelah Exam terkunci.
- `ExamPolicy::startAttempt()` mempertahankan resume Attempt IN_PROGRESS yang belum expired selama eligibility peserta masih berlaku, termasuk setelah jadwal/Exam ditutup. Attempt baru tetap harus memenuhi jadwal/status resmi.
- `ExamController::close()` menggunakan transaksi, Exam row lock, dan re-authorize di dalam transaksi.

**Gap:**
- `ExamAnswerController::update()` mengunci Attempt lalu Answer; belum mengikuti urutan global `Exam → Participant → Attempt → Answer/Score/Lease`.
- `ExamController::archive()` belum memakai transaksi + Exam row lock + re-authorize di dalam transaksi.
- `publishResults()` mengubah status publikasi tanpa pola lock/re-authorize dalam transaksi.
- `destroy()` memeriksa jumlah Attempt sebelum transaksi/row lock lalu hard delete Exam. Ini race terhadap pembuatan Attempt dan berisiko terhadap histori. Penghapusan harus dibatasi; archive adalah jalur normal.
- `ExamSessionLifecycleService` mengubah session menjadi COMPLETED saat ends_at tercapai dan menyelesaikan assignment Proktor terkait tanpa memeriksa Attempt IN_PROGRESS yang masih memerlukan monitoring/recovery.
- `ExamPolicy::monitor()` masih mengizinkan KAMAD; jalur itu harus diselaraskan dengan kebijakan tidak ada portal Kamad.
- Source Agent 2 masih menggunakan `X-Device-Binding-ID` dan `device_fingerprint`; ini bukan bukti fencing end-to-end. Tidak ada dasar untuk menyebut Device Lease/Fencing siap produksi.

Matriks lock-order yang diusulkan untuk review lintas-agent:
1. Exam lifecycle: `Exam → Participant → Attempt → Answer/Score/Lease`.
2. Answer save/submit/force-submit: urutan yang sama, dengan Answer setelah Attempt.
3. Session/room/proctor scheduler: `Exam → Room(s) → Session → SessionRoom(s) → placement/mapping → ProctorAssignment → User`; harus diselaraskan dengan ownership Agent 1.
4. Archive/publish/destroy: lock Exam, re-authorize actor dan tenant dalam transaksi, lalu periksa state/Attempt; jangan hard-delete histori.
5. Semua query lock harus deterministik (tenant, exam, id), dan setiap jalur yang bersinggungan wajib memakai urutan sama.

### 1.8 Import soal Agent 2/3 — Issue #8 dan #9

- `QuestionController::import()` saat ini menerima array `rows`, bukan upload file; ia memanggil `ExcelQuestionImporter` langsung.
- `ExcelQuestionImporter` memproses array draft dalam transaksi, bukan lifecycle upload → staging → preview → confirm → persist.
- `QuestionImportService` hanya interface filePath/options; implementasi Excel saat ini mengandalkan `options['rows']`.
- `ImportBatch` dan `ImportBatchRow` sudah ada: tenant-scoped, file_sha256, status/counters, unique tenant+type+hash, unique batch+sheet+row, serta encrypted_payload. Migration type adalah string 10 karakter; `QUESTION` muat tanpa tabel batch kedua.
- `ImportWorkbookService` saat ini khusus lifecycle import akun; jangan membuat batch table kedua atau menyalin lifecycle batch secara terpisah.
- Composer hanya menunjukkan Laravel, Sanctum, Livewire, Dompdf, dan Maatwebsite Excel; PHPWord tidak tercantum. Tidak ada dependency DOCX baru yang boleh ditambahkan tanpa audit lisensi/kompatibilitas/keamanan dan persetujuan.

Kontrak desain:
- XLSX dan DOCX memiliki adapter ekstraksi berbeda, tetapi menghasilkan satu `QuestionDraftDTO` netral-format.
- DTO memuat schema version, question type, stem, explanation, options, accepted answers, rubric, points, difficulty, subject/assignment, CP/TP, Kurikulum Merdeka/Panca Cinta mapping, media references, source locator, dan warnings/confidence.
- Satu lifecycle batch memakai tabel `ImportBatch/ImportBatchRow` yang ada; `type=QUESTION`, hash file untuk idempotensi, encrypted row payload untuk draft sensitif, source locator pada row metadata, dan private storage tenant-scoped untuk file/media sementara.
- Duplicate file ditolak/idempotent berdasarkan tenant+type+SHA-256. Duplicate content ditandai untuk review; jangan auto-merge tanpa konfirmasi.
- Alur: upload → MIME/size/ZIP/XML security validation → extraction → DTO staging → preview/row correction → revalidation → explicit confirm → persistence Question/QuestionVersion/Options/Media/mapping → audit.
- Tidak ada auto-publish. DOCX dan XLSX tidak membuat database/batch service kedua.
- `SHORT_ANSWER` tetap **BLOCKED**: importer menyimpan accepted_answers di `QuestionVersion.metadata`, tetapi `ExamSnapshotService` hanya menyalin difficulty/cognitive_level/question_type ke snapshot; `ScoringService::evaluateShortAnswer()` membaca accepted_answers dari snapshot metadata. Tanpa perbaikan snapshot dan test scoring, jawaban benar tidak terbukti dapat dinilai. `ESSAY` tetap manual review.

## 2. DESAIN — keputusan yang perlu disepakati

1. Satu guard/service exact-role untuk tiga portal, dengan query role segar dan permission pada role yang sama; semua Policy, Livewire, controller, middleware, importer, reporting, dan service entrypoint memakai kontrak ini.
2. KAMAD dikeluarkan dari login web/API; jabatan Kepala Madrasah dimodelkan sebagai official appointment, bukan portal.
3. Konfirmasi topologi tenant: satu database multi-tenant lintas domain atau satu instalasi per tenant. Resolusi tenant pre-auth harus menggunakan domain/installation registry tepercaya, bukan input bebas/header.
4. Tenant profile dan teacher profile mendapat kontrak data status/identitas; appointment Kamad menyimpan histori dan rentang jabatan.
5. Reporting menggunakan current pointer + FINALIZED untuk statistik; report issuance immutable.
6. Global lock order dan scheduler lifecycle disetujui Agent 1/2/3/4 sebelum implementasi.
7. Import DOCX/XLSX menggunakan satu DTO dan satu batch lifecycle; dependency DOCX tetap ditunda.
8. Device Lease/Fencing menunggu kontrak dan integration test multi-device.

## 3. IMPLEMENTASI

- Satu-satunya perubahan source pada baseline adalah fixture test sebelumnya, sudah ada sebelum audit ini dan tidak diulang.
- Dalam penugasan audit ini tidak ada file runtime, Policy, route, controller, middleware, migration, schema, database, UI, atau konfigurasi yang diubah.
- Dokumen audit baru ini adalah dokumentasi tambahan; bukan implementasi fitur.
- Tidak ada merge, rebase, cherry-pick, deployment, atau perubahan branch Agent 1/3/4.

## 4. PENGUJIAN

| Bukti | Environment | Hasil |
|---|---|---|
| Agent 2 Run #388, ID `45003796892`, SHA `74bf717...`, attempts 1–3 | GitHub Actions | **BLOCKED — RUNNER**; runner_id 0, tanpa steps, PHPUnit tidak berjalan |
| Run #45000004149, SHA `a9c9e0b...` | GitHub Actions | Source lama tanpa explicit web guard; gagal pada Admin CBT boundary; bukan bukti patch |
| Agent 3 Run #39, ID `45003191589`, SHA `834a59f...` | PHP 8.3, disposable MySQL 8.0.46 | 18 passed, 2 failed, 86 assertions; bukan PASS |
| Audit source oleh Agent 2 pada penugasan ini | GitHub source read-only | Tidak menjalankan PHPUnit atau concurrency test |

Rincian Agent 3:
- Satu kegagalan: test boundary mengharapkan 403 tetapi menerima 200 pada pemeriksaan tenant-context mismatch.
- Satu kegagalan: skenario ID Question lintas tenant memunculkan ModelNotFoundException saat Livewire action, sedangkan test mengharapkan respons Not Found. Perlu Agent 3 membedakan ekspektasi 404 dan exception handling; Agent 2 tidak mengubah UI.
- Branch Agent 3 saat refresh: `a955a439b1b3fa4c149f0153f283951d2bfb88d2`; source test terbaru yang diuji tetap SHA `834a59f...` dan perubahan setelahnya berupa dokumentasi.

**Tidak ada pengujian MySQL concurrency, multi-device lease/fencing, atau integrasi Android yang dijalankan dalam penugasan ini.**

## 5. KOORDINASI LINTAS-AGENT

- Issue #10: status approval Agent 2/3/4 tetap PENDING; komentar terakhir yang diperiksa menyebut belum ada approval agent. Tidak ada persetujuan integrasi/migration.
- Agent 1 branch saat refresh: `work/stage4b-room-session-20261122`, HEAD `84a3d3bf9da6abb0e9d925ead6530a9b63afe489`. Run lama `44984303003` pada source `f1d01ea...` pernah SUCCESS, tetapi invariant satu Exam–satu operational session belum dibuktikan dan scheduler completion vs active Attempt masih perlu desain.
- Agent 3 branch: `work/web-guru-foundation-20261122`, HEAD `a955a439b1b3fa4c149f0153f283951d2bfb88d2`. Helper `TeacherPortalAccess` dan hardening Livewire sudah ada di branch tersebut, tetapi belum menjadi source Agent 2; CI pada SHA test `834a59f...` memiliki dua kegagalan.
- Agent 4: `work/import-guru-siswa-implementation-20261122`, HEAD `f38ceec7b0653fcf3363eec40f3d4577c323cca8` masih kandidat, bukan branch resmi yang disetujui. Jangan mengintegrasikan UI Siswa atau menganggap kontrak import selesai.
- Issue #8 memiliki catatan Agent 3 tentang exact-role Livewire dan permintaan kontrak import; Issue #9 belum memiliki komentar. Tidak ada source lintas-agent yang diubah.

## 6. CHECKPOINT

**Selesai:**
- Refresh branch/source HEAD dan verifikasi patch fixture + backup.
- Identifikasi run exact SHA dan status BLOCKED — RUNNER.
- Audit jalur exact-role utama di Policy, Controller, Middleware, Livewire, Service, Support.
- Audit auth/KAMAD/tenant resolution dan branding login.
- Audit tenant/guru/kamad identity fields.
- Audit report current score pointer, nilai belum dinilai, dan kebutuhan immutable snapshot.
- Audit Exam lifecycle, lock-order gap, scheduler Proktor, dan status Device Lease/Fencing.
- Audit kontrak import DOCX/XLSX dan bukti SHORT_ANSWER belum siap.

**Belum selesai / blocker:**
- PHPUnit Admin CBT pada SHA patch belum berjalan karena runner.
- Exact-role backend belum konsisten dan belum memiliki negative test komprehensif untuk semua kombinasi role/jalur.
- Agent 3 CI belum PASS.
- Topologi tenant pre-auth belum diputuskan.
- Tenant/Guru/Kamad data contract dan immutable report snapshot belum disetujui.
- Lock-order dan session/proctor lifecycle menunggu koordinasi lintas-agent.
- SHORT_ANSWER tetap BLOCKED.
- Device Lease/Fencing belum siap produksi.
- Import DOCX/XLSX belum memiliki kontrak final dan end-to-end test.

**Langkah berikutnya:**
1. Jalankan ulang workflow Admin CBT pada SHA source yang memuat fixture explicit web guard setelah runner tersedia; jangan mengubah fixture lagi.
2. Finalisasi dan review exact-role contract; tambahkan negative tests pada Policy/API/Livewire/service direct entrypoints.
3. Minta Agent 3 menyelesaikan dua kegagalan CI branch Guru sebelum integrasi.
4. Review kontrak Tenant/Guru/Kamad, branding, reporting snapshot, dan current score pointer.
5. Minta Agent 1/3/4 memberi persetujuan tertulis atas lock order/session/proctor/import contract.
6. Baru setelah persetujuan desain, siapkan rencana implementasi dan migration forward-only untuk review terpisah.

Tidak ada klaim siap produksi, siap integrasi, atau siap distribusi Android Studio.
