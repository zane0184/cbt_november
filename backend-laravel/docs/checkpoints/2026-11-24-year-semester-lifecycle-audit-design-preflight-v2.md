# CBT Madrasah — Year/Semester Lifecycle
## Audit lanjutan, desain v2 untuk persetujuan, pre-flight, dan test plan

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/solo-web-cbt-completion-20261123-execution`  
HEAD saat checkpoint dibuat: `fa1bb642bd5f40ea6e08d79576e1c58d83f5972b`

> STATUS: **AUDIT LANJUTAN SELESAI SECARA SOURCE-LEVEL. DESAIN V2 DISERAHKAN UNTUK REVIEW, BELUM DISETUJUI. PRE-FLIGHT DATA BELUM DAPAT DIJALANKAN KARENA DATABASE DISPOSABLE/REPRESENTATIF TIDAK TERSEDIA. IMPLEMENTASI DITAHAN.**

## 0. Batas pekerjaan dan bukti

- Branch dan HEAD diperiksa ulang sebelum checkpoint. HEAD tetap `fa1bb642bd5f40ea6e08d79576e1c58d83f5972b`.
- Perbandingan dari baseline `0250dc257cc7292d960cacf2c20eda782ee8a25d` menunjukkan 3 commit di depan, 0 di belakang, seluruh perubahan sebelumnya berupa 3 dokumen checkpoint. Tidak ada source aplikasi berubah pada tiga commit itu.
- Audit source dilakukan dengan membaca file pada ref HEAD tersebut, bukan mengandalkan nama file atau laporan lama.
- Dokumen checkpoint sebelumnya tidak ditimpa.
- Tidak ada source aplikasi, migration, trigger, route, test, atau database yang diubah.
- Satu-satunya perubahan pada checkpoint ini adalah penambahan dokumen ini.

### Batas pre-flight database

File Project `Lanjutkan Eksekusi Sampai Selesai.txt` mencatat hasil pemeriksaan runtime sebelumnya: MySQL tidak tersedia, PDO drivers kosong, Docker/Podman tidak tersedia, SSH/SCP tidak tersedia, dan akses kedua VPS belum tersedia. Catatan tersebut juga menyatakan tidak ada migration atau koneksi database utama. Karena itu, audit ini **tidak menjalankan SQL terhadap database** dan tidak mengklaim mengetahui jumlah record existing.

## 1. AUDIT LANJUTAN

### 1.1 FK akademik dan periode

Source yang diperiksa:

- `database/migrations/2024_01_01_000002_create_academic_tables.php`
- `database/migrations/2024_01_01_000014_create_assessments_table.php`
- `database/migrations/2026_11_07_210001_create_teacher_subject_assignments_table.php`
- `database/migrations/2026_11_07_210002_harden_teacher_assignment_tenant_foreign_keys.php`
- `database/migrations/2026_11_09_230003_harden_rombel_student_tenant_integrity.php`
- `database/migrations/2026_11_09_230004_harden_rombel_teacher_tenant_integrity.php`
- `database/migrations/2026_11_23_000001_add_canonical_web_curriculum_contract.php`
- `database/migrations/2026_09_13_110007_create_tp_achievements_table.php`
- `database/migrations/2026_09_13_110008_create_tp_assessment_contributions_table.php`
- `database/migrations/2026_09_13_110009_create_atp_achievements_table.php`
- `database/migrations/2026_09_13_110010_create_atp_tp_sources_table.php`
- `database/migrations/2026_09_13_110011_create_cp_achievements_table.php`
- `database/migrations/2026_09_13_110012_create_cp_atp_sources_table.php`

**Yang sudah ada dan harus dipertahankan:**

- `semesters(id, tenant_id)` dan `semesters(id, academic_year_id, tenant_id)` candidate keys dibuat oleh migration 210002.
- Assessment memiliki FK komposit `(semester_id, academic_year_id, tenant_id)` ke Semester melalui `assessment_semester_year_tenant_fk`, dengan RESTRICT.
- Teacher Subject Assignment memiliki FK komposit ke Semester, Tahun Ajaran, Rombel, Teacher Profile, Subject, dan creator; aturan delete utamanya RESTRICT.
- Rombel–Student dan Rombel–Teacher telah menerima hardening tenant.
- Tabel TP/ATP/CP achievement sudah mengunci referensi tahun, semester, rombel, siswa, dan subject dengan RESTRICT.
- Mapping kurikulum canonical sudah menggunakan banyak FK tenant-aware dan RESTRICT.

**Temuan yang masih perlu ditangani:**

1. Migration dasar `academic_years.tenant_id` masih CASCADE.
2. Migration dasar `semesters.tenant_id` masih CASCADE.
3. Relasi Semester → Tahun Ajaran memiliki FK langsung CASCADE dan FK komposit CASCADE.
4. Rombel → Tahun Ajaran masih CASCADE, termasuk FK komposit.
5. Assessment → Tahun Ajaran masih memiliki FK langsung CASCADE, sementara FK komposit year-tenant dibuat tanpa aksi eksplisit (default RESTRICT). Dua FK pada pasangan kolom yang sama dengan aksi berbeda harus direkonsiliasi di schema aktual; migration korektif harus menyisakan satu aturan yang eksplisit dan konsisten.
6. FK langsung Assessment.semester_id SET NULL dari migration awal sudah diganti oleh FK komposit RESTRICT pada migration 210002. Jangan menambahkan FK Semester baru yang menduplikasi relasi ini.
7. FK tahun/semester pada tabel kurikulum canonical sebagian besar mengunci tenant, tetapi belum semuanya mengunci kombinasi `semester_id + academic_year_id + tenant_id`. Contohnya Tenant Learning Outcome dan question-version mappings menyimpan tahun dan semester secara terpisah. FK tenant-aware yang sudah ada harus dipertahankan; tambahan pasangan hanya setelah preflight data.
8. Tabel achievement TP/ATP/CP memakai RESTRICT untuk academic_year_id dan semester_id. Ini harus tetap menjadi pelindung histori hasil belajar.

### 1.2 FK CBT, snapshot, attempt, answer, score

Source yang diperiksa:

- `2024_01_01_000014_create_assessments_table.php`
- `2024_01_01_000015_create_exams_table.php`
- `2024_01_01_000016_create_exam_question_snapshots_table.php`
- `2024_01_01_000017_create_exam_participants_table.php`
- `2024_01_01_000018_create_exam_attempts_table.php`
- `2024_01_01_000020_create_exam_attempt_answers_table.php`
- `2024_01_01_000021_create_scoring_tables.php`
- `2026_09_13_110006_create_exam_attempt_score_current_table.php`
- `2026_11_16_230001_protect_exam_snapshot_source_history.php`
- `2026_11_20_100002_harden_device_lease_tenant_constraints.php`
- `2026_11_23_090002_create_exam_attempt_answer_requests_table.php`

**Jalur penghapusan historis yang ditemukan:**

| Parent → child | Aturan pada migration/source | Dampak yang perlu ditutup |
|---|---|---|
| Tenant → users | CASCADE | Hard-delete tenant dapat menghapus akun |
| Tenant → academic_years / semesters | CASCADE | Periode dapat ikut terhapus |
| AcademicYear → Semester | CASCADE (langsung dan komposit) | Semester historis dapat ikut terhapus |
| AcademicYear → Rombel | CASCADE | Rombel dan keanggotaan kelas berisiko hilang |
| Rombel → rombel_student | CASCADE | Keanggotaan siswa hilang |
| StudentProfile → rombel_student | CASCADE | Riwayat keanggotaan hilang |
| Tenant → assessments | CASCADE | Assessment dapat ikut terhapus |
| AcademicYear → assessments | FK langsung CASCADE, FK komposit year-tenant juga ada | Konflik aksi FK harus direkonsiliasi |
| Assessment → exams | CASCADE | Ujian dapat terhapus |
| Exam → exam_question_snapshots | CASCADE | Snapshot ujian dapat terhapus |
| Exam → exam_participants | CASCADE | Daftar peserta dapat terhapus |
| Exam → exam_attempts | CASCADE | Attempt dapat terhapus |
| ExamParticipant → exam_attempts | CASCADE | Attempt peserta dapat terhapus |
| ExamAttempt → exam_attempt_answers | CASCADE | Jawaban dapat terhapus |
| ExamAttempt → exam_attempt_scores | CASCADE | Riwayat nilai dapat terhapus |
| ExamAttempt → exam_question_scores | CASCADE | Nilai per soal dapat terhapus |
| ExamAttempt → exam_attempt_score_current | CASCADE | Pointer nilai aktif dapat terhapus |
| ExamAttemptScore → exam_attempt_score_current | CASCADE | Pointer histori dapat hilang |
| Exam → snapshots → answers/scores | CASCADE berantai | Penghapusan Exam berpotensi menghapus snapshot, jawaban, dan nilai |
| Tenant → audit_logs | SET NULL | Log tetap ada, tetapi tenant attribution hilang |
| User → exam_participants | CASCADE | Hard-delete akun siswa dapat menghapus peserta dan seluruh attempt turunannya |
| User → assessments/exams/question creator | Sebagian CASCADE, sebagian SET NULL | Identitas pembuat dapat menghapus objek atau kehilangan atribusi |
| Tenant → registered_devices/device_leases | Migration awal CASCADE, kemudian migration 2026-11-20 mengganti ke RESTRICT | Schema aktual harus diverifikasi; pertahankan hardening terbaru |

Migration `2026_11_16_230001_protect_exam_snapshot_source_history.php` sudah mengubah FK snapshot → source question dan question version menjadi RESTRICT. Perlindungan tersebut benar dan tidak boleh dibatalkan.

**Catatan penting tentang Tenant:** model `Tenant` menggunakan SoftDeletes. Pemanggilan Eloquent `delete()` normal hanya mengisi `deleted_at`, bukan menjalankan FK cascade. Namun hard-delete/force-delete atau SQL DELETE terhadap Tenant dapat memicu cascade graph. Selain itu, SoftDeletes pada AcademicYear/Semester dapat menyembunyikan periode dari query biasa walaupun data turunannya masih ada. Karena itu, perlindungan harus mencakup soft delete, hard delete, dan seluruh jalur parent cascade.

**Catatan tentang creator identity:** CASCADE dari user pembuat ke Assessment/Exam tidak boleh menghapus objek akademik. Pilihan desain: RESTRICT untuk user yang menjadi identitas peserta/riwayat, dan RESTRICT atau SET NULL dengan snapshot identitas untuk kolom creator/updater. Keputusan akhir harus mengikuti kebutuhan audit, bukan menghapus riwayat.

**SET NULL yang ditemukan dan dampaknya:**

- `audit_logs.tenant_id` dan `audit_logs.user_id`: log tetap ada, tetapi identitas tenant/actor dapat hilang saat parent hard-delete.
- `rombels.teacher_id`: penghapusan Teacher Profile dapat mengosongkan wali/teacher Rombel.
- Migration awal Assessment: `semester_id` SET NULL, tetapi FK ini telah diganti oleh composite RESTRICT di migration 210002; `curriculum_id` dan `subject_id` masih SET NULL; `updated_by` SET NULL; `created_by` CASCADE.
- Curriculum detail mappings mempunyai beberapa SET NULL ke phase/grade/element/learning outcome/objective/path; perlu dipastikan tidak menjadi satu-satunya provenance akademik.
- Registered Device `revoked_by` dan Device Lease `released_by` semula SET NULL, lalu migration 2026-11-20-100002 mengganti FK runtime device menjadi RESTRICT pada jalur up.
- SET NULL pada kolom periode/tenant/attempt/snapshot/score tidak boleh digunakan karena akan memutus histori. Untuk kolom actor, SET NULL hanya boleh dipertahankan bila audit log menyimpan actor snapshot immutable.

### 1.3 Transaksi dan urutan lock yang benar-benar ditemukan

| Jalur | Source | Urutan lock saat ini |
|---|---|---|
| Start Attempt | `ExamAttemptController::store` | Exam → Participant → Attempt aktif/expiry → User → DeviceLease |
| Resume/execution | `ExamAttemptController::execution` | Exam → Participant → Attempt → DeviceLease |
| Submit | `ExamAttemptController::submit` | Exam → Participant → Attempt → DeviceLease; update Attempt dan Participant |
| Read/restore answers | `ExamAnswerController::index` | Exam → Participant → Attempt → DeviceLease |
| Sync/write answer | `ExamAnswerController::update` | Exam → Participant → Attempt → DeviceLease, lalu request ledger dan Answer |
| Expiry recovery | `ExpireExamAttemptsCommand::expireOne` | Exam → Participant → Attempt → DeviceLease; scoring dipanggil setelah terminalisasi dalam transaksi |
| Score | `ScoringController::score` | Attempt → current score pointer → score |
| Manual grading | `ScoringController::updateManualScore` | Attempt → pointer → attempt score → question score |
| Finalize | `ScoringController::finalize` | Attempt → pointer → score; kemudian curriculum achievement |
| Recalculate | `ScoringController::recalculate` | Attempt → pointer → score |
| Reopen score | `ScoringService::reopenAttempt` | Attempt → pointer → score; kemudian scoring |
| Device lease renewal | `AttemptDeviceLeaseService::assertAndRenew` | Dipanggil ketika Attempt telah dikunci; lalu DeviceLease |
| Exam publish/edit/close | `ExamController` | Exam; pada operasi soal/rombel dapat mengunci snapshot/rombel sesudahnya |
| Participant add/remove | `ExamParticipantController` | Exam → Participant; remove memeriksa keberadaan Attempt |
| Teacher assignment | `TeacherSubjectAssignmentManagement` | Teacher Profile → Rombel → Semester → Registration |
| Exam/Assessment builder | `Livewire/Admin/ExamManagement.php` | Teacher Subject Assignment → Exam; Assessment dibuat/diubah dalam transaksi |
| Score recalculation | `ScoringService::scoreAttempt` | Mengunci score pointer terlebih dahulu; tidak mengunci Attempt sendiri pada awal method |

**Temuan lock order yang harus diperbaiki secara sempit:**

- `ScoringService::scoreAttempt` memulai dari score pointer. Sebagian besar caller sudah mengunci Attempt lebih dahulu, tetapi service tidak menegakkan kontrak itu sendiri. Perlu memastikan Attempt dikunci sebelum pointer pada seluruh entry point.
- `ExpireExamAttemptsCommand` sudah mengunci Exam → Participant → Attempt sebelum memanggil ScoringService. Perubahan service tidak boleh membalik urutan ini.
- `ExamAttemptController` dan `ExamAnswerController` sudah memiliki urutan parent-first yang konsisten. Jangan mengganti urutan tersebut untuk seluruh CBT.
- Lifecycle periode tidak boleh mengunci Exam/Participant/Attempt jika tujuannya hanya mengganti periode. Mengunci seluruh graf ujian akan memperbesar kontensi dan menimbulkan deadlock baru.
- `ExamAttemptController::store` adalah titik kritis yang harus diserialisasi dengan pergantian periode. Jalur resume, answer sync, submit, expiry recovery, dan scoring harus tetap dapat menyelesaikan Attempt lama tanpa mensyaratkan periode masih aktif.

### 1.4 Force Submit dan Recovery

Route `routes/api.php` yang diperiksa menyediakan:

- POST `/exams/{exam}/attempts` untuk start.
- GET `/exams/{exam}/execution` untuk execution/resume.
- GET/PUT `/exams/{exam}/attempts/{attempt}/answers` untuk read/write jawaban.
- POST `/exams/{exam}/attempts/{attempt}/submit` untuk submit peserta.
- POST score, recalculate, grading, finalize, reopen.
- Scheduler `cbt:expire-attempts` untuk menutup Attempt yang expired.

**Tidak ditemukan route khusus Force Submit oleh Admin/Proktor** pada route/controller yang diperiksa. Route submit saat ini adalah submit oleh siswa; expiry scheduler adalah penutupan otomatis, bukan Force Submit berotorisasi. Ini harus tetap menjadi gap terpisah dan tidak boleh disamarkan sebagai fitur yang sudah ada.

### 1.5 Penggunaan periode aktif dan pemilihan `first()`

| File | Bukti source | Penilaian |
|---|---|---|
| `Livewire/Admin/Dashboard.php` | `AcademicYear::query()->where(...is_active)->first()` | BUG: tidak memvalidasi jumlah aktif |
| `Livewire/Operator/OperatorDashboard.php` | `AcademicYear::where(...is_active)->first()` | BUG: tidak memvalidasi jumlah aktif |
| `Services/TeacherPortal/TeacherPortalDataService.php` | Mengambil semua tahun/semester aktif, memeriksa count, hanya menerima tepat satu dan memeriksa pasangan tahun | Pola fail-closed yang baik; perlu dipindahkan ke resolver bersama |
| `ScoringController::finalize` | `studentProfile?->rombels()->first()` | Bukan pemilih periode aktif; tetap berisiko memilih Rombel sembarang saat menghitung capaian |
| `TeacherSubjectAssignmentManagement.php` | `'semester_name' => $semester->name` | BUG: model Semester tidak mempunyai kolom/properti name; gunakan label dari term |
| `TeacherCurriculumContextService.php` | `$assignment->semester?->name` | BUG sejenis; Semester hanya mempunyai term |
| `StudentExamController.php` | Memilih Attempt aktif/terbaru dari daftar Attempt | Bukan pemilih AcademicYear/Semester |

Tidak ditemukan penggunaan `Semester::first()` yang secara langsung memilih Semester aktif tanpa validasi jumlah pada file periode utama yang diperiksa. Resolver bersama harus menjadi satu-satunya sumber periode aktif; dashboard dan portal tidak boleh melakukan `first()` pada boolean aktif.

### 1.6 Proses akademik yang perlu validasi periode

- Pembuatan Rombel di `AcademicManagement::createRombel`: boleh memilih Tahun Ajaran persiapan; tidak boleh menganggapnya sebagai periode operasional.
- Pembuatan Teacher Subject Assignment di Livewire/API: dapat menyiapkan penugasan untuk periode mendatang, tetapi status operasional harus dibedakan.
- Pembuatan Assessment di `ExamManagement`: wajib memakai Assignment dan Semester yang valid; untuk Assessment operasional/CBT, Semester harus merupakan periode aktif.
- Penerbitan Exam: harus memvalidasi konteks Assessment dan periode; Draft persiapan boleh diatur hanya jika aturan produk mengizinkan, tetapi tidak boleh memulai Attempt.
- Start Attempt: wajib validasi periode aktif di dalam transaksi setelah lock periode didapat.
- Resume/answer/submit/recovery/scoring untuk Attempt yang sudah dibuat: tidak boleh ditolak hanya karena periode kemudian ditutup.

## 2. DESAIN V2 — USULAN FINAL UNTUK REVIEW

Desain ini belum disetujui untuk implementasi. Perubahan dari desain awal: **generated unique index tetap dipakai, tetapi ditambah referensi aktif terikat dan satu pointer periode aktif per tenant.** Trigger belum dipilih.

### 2.1 State dan konsistensi metadata

Kolom yang diusulkan pada AcademicYear dan Semester:

- `is_active` tetap ada untuk kompatibilitas selama transisi.
- `closed_at` nullable.
- `archived_at` nullable.
- `deleted_at` tidak boleh diisi untuk periode; periode ditutup/diarsipkan, bukan soft-delete.

State yang valid:

| State | is_active | closed_at | archived_at |
|---|---:|---|---|
| PREPARING | 0 | NULL | NULL |
| ACTIVE | 1 | NULL | NULL |
| CLOSED | 0 | NOT NULL | NULL |
| ARCHIVED | 0 | NOT NULL | NOT NULL |

CHECK lokal yang diusulkan:
- ACTIVE mengharuskan `closed_at IS NULL AND archived_at IS NULL`.
- CLOSED/ARCHIVED mengharuskan `is_active=0`.
- ARCHIVED mengharuskan `closed_at IS NOT NULL`.
- `deleted_at IS NULL` untuk seluruh record AcademicYear/Semester.

CHECK hanya menegakkan konsistensi kolom dalam satu baris; CHECK MySQL tidak boleh memakai subquery/kolom tabel lain. Karena itu CHECK tidak bisa menggantikan FK/service untuk validasi Semester-parent.

### 2.2 Generated stored columns dan unique keys

**AcademicYear:**
- Generated STORED `active_tenant_key = CASE WHEN is_active=1 AND deleted_at IS NULL THEN tenant_id ELSE NULL END`.
- Generated STORED `active_ref_id = CASE WHEN is_active=1 AND deleted_at IS NULL THEN id ELSE NULL END`.
- UNIQUE `active_tenant_key`: maksimal satu tahun aktif per tenant.
- UNIQUE `(active_ref_id, tenant_id)`: candidate key yang hanya tersedia untuk tahun aktif.

**Semester:**
- Generated STORED `active_year_ref = CASE WHEN is_active=1 AND deleted_at IS NULL THEN academic_year_id ELSE NULL END`.
- Generated STORED `active_semester_ref = CASE WHEN is_active=1 AND deleted_at IS NULL THEN id ELSE NULL END`.
- UNIQUE `(tenant_id, active_year_ref)`: maksimal satu semester aktif per tahun.
- UNIQUE `(active_semester_ref, active_year_ref, tenant_id)`: candidate key untuk pointer aktif.
- FK `(active_year_ref, tenant_id)` → AcademicYear `(active_ref_id, tenant_id)`, ON DELETE RESTRICT. Karena generated refs NULL saat Semester tidak aktif, Semester persiapan boleh berada pada tahun nonaktif. Saat Semester diaktifkan, FK mengharuskan parent tahun aktif.

Ini lebih kuat daripada unique index saja: active Semester tidak dapat menunjuk ke AcademicYear nonaktif.

### 2.3 Pointer periode aktif tenant

Tambahkan tabel `tenant_active_academic_periods` sebagai pointer otoritatif:

- `tenant_id` PRIMARY KEY, FK ke Tenant RESTRICT.
- `academic_year_id` NOT NULL.
- `semester_id` NOT NULL.
- `transition_version` BIGINT untuk optimistic stale-request guard.
- `updated_by`, timestamp, dan metadata transisi yang diperlukan.

FK:
- `(academic_year_id, tenant_id)` → `academic_years(active_ref_id, tenant_id)`.
- `(semester_id, academic_year_id, tenant_id)` → `semesters(active_semester_ref, active_year_ref, tenant_id)`.
- Semua ON DELETE RESTRICT.

Dampak:
- Satu row per tenant mengikat tepat satu pasangan Tahun Ajaran + Semester aktif.
- FK memastikan Semester aktif tersebut berada pada Tahun Ajaran aktif yang sama dan tenant yang sama.
- Generated unique keys mencegah active year/semester tambahan.
- Tahun Ajaran baru dapat disiapkan tanpa mengubah pointer lama.
- Pergantian semester/tahun memperbarui pointer satu kali dalam transaksi bersama perubahan flag lama/baru.
- Tidak ada status committed yang menunjukkan tahun operasional tanpa semester aktif, selama pointer row ada.

Untuk tenant madrasah existing, migration hanya boleh membuat pointer setelah preflight menemukan tepat satu pasangan aktif yang valid. Tenant System Global dikecualikan dari kewajiban periode akademik. Tenant madrasah baru harus membuat tahun, semester, dan pointer dalam proses onboarding atomik sebelum diizinkan operasional. Jika pointer tidak ada/invalid, resolver menolak seluruh aktivitas yang membutuhkan periode aktif.

### 2.4 Mengapa trigger belum dipilih

Evaluasi opsi:

| Mekanisme | Dapat menjamin | Batas |
|---|---|---|
| UNIQUE biasa | Mencegah duplikasi pasangan persis | Tidak membatasi satu aktif per tenant/year jika kolomnya tidak tepat |
| Generated STORED + UNIQUE | Maksimal satu active year per tenant; maksimal satu active semester per year | Tidak menjamin keberadaan satu semester aktif |
| CHECK | Kombinasi state lokal | Tidak boleh membaca tabel lain |
| FK komposit | Tenant, year, semester, dan active marker saling cocok | Tidak menjamin pointer row ada untuk setiap tenant tanpa provisioning |
| Pointer table + FK komposit | Satu pasangan aktif atomik per tenant yang mempunyai pointer | Onboarding/backfill wajib membuat row |
| Service + transaction | Transisi lengkap, audit, idempotensi, validasi operasional | Semua writer aplikasi harus melewati service |
| Trigger | Bisa menghalangi jalur SQL langsung tertentu | Memperumit urutan aktivasi, migration, rollback, observabilitas, dan kompatibilitas |

**Rekomendasi v2:** gunakan generated STORED + UNIQUE + CHECK + FK komposit + pointer table + lifecycle service. Jangan menambahkan trigger pada tahap pertama. Terapkan proteksi penghapusan melalui:
1. CHECK `deleted_at IS NULL` pada tabel periode untuk menolak soft delete.
2. FK historis ON DELETE RESTRICT.
3. Hak DB runtime tidak diberi DELETE pada `academic_years`, `semesters`, `tenant_active_academic_periods`, dan tabel Tenant.
4. Migration/maintenance menggunakan akun terpisah; bukan akun runtime aplikasi.

Jika privilege separation tidak dapat diterapkan pada instalasi target, barulah BEFORE DELETE/UPDATE trigger dievaluasi sebagai kontrol tambahan dan diuji pada MySQL 8 disposable. Jangan memasang trigger sebelum bukti kebutuhan dan uji compatibility tersedia.

### 2.5 Perlindungan seluruh histori dan cascade

Migration korektif harus mengaudit dan mengubah FK yang dapat menghapus histori:

- Tenant → users, academic years, semesters, rombels, assessments, exams, participants, attempts, answers, scores, snapshots, achievements, dan tabel tenant-owned durable lainnya: RESTRICT, bukan CASCADE.
- AcademicYear → Semester, Rombel, Assessment: RESTRICT.
- Semester → Assessment/Assignment/achievement/mapping: RESTRICT.
- Assessment → Exam: RESTRICT.
- Exam → snapshot/participant/attempt: RESTRICT.
- Participant → Attempt: RESTRICT.
- Attempt → Answer/Score/ScoreCurrent/QuestionScore/DeviceLease: RESTRICT.
- User → participant identity/attempt history: RESTRICT.
- Creator/updater identity: RESTRICT atau SET NULL dengan immutable actor snapshot; jangan CASCADE ke objek historis.
- Audit log tidak boleh dihapus ketika Tenant/User diarsipkan. Tenant attribution harus tetap dapat ditelusuri melalui tombstone/tenant snapshot; SET NULL tidak boleh menjadi satu-satunya bukti kepemilikan log.

FK snapshot → source question/version yang sudah RESTRICT tetap dipertahankan. Relasi histori yang sudah RESTRICT pada achievement dan canonical curriculum juga dipertahankan.

Perubahan FK harus menghindari duplicate FK pada kolom yang sama. Preflight wajib mencatat nama constraint, kolom, parent, delete/update rule aktual dari INFORMATION_SCHEMA sebelum migration.

### 2.6 State transition

**Semester:**
- PREPARING → ACTIVE hanya jika parent Year ACTIVE dan melalui lifecycle service.
- ACTIVE → CLOSED saat Semester pengganti diaktifkan.
- CLOSED → ARCHIVED setelah proses arsip/rekonsiliasi.
- CLOSED/ARCHIVED tidak dapat diaktifkan kembali melalui operasi normal.
- Tidak ada DELETE atau SoftDelete.

**Tahun Ajaran:**
- PREPARING dapat disiapkan bersama Tahun Ajaran yang sedang berjalan.
- Pergantian Tahun hanya boleh menuju tahun PREPARING yang sudah mempunyai Semester target PREPARING.
- Satu transaksi menutup Semester lama, menutup Tahun lama, mengaktifkan Tahun baru, mengaktifkan Semester target, dan memperbarui pointer.
- Tahun lama dan Semester lama tetap dapat dibaca untuk laporan/histori.
- Tahun/semester lama tidak boleh dihapus atau disembunyikan.

### 2.7 Concurrency strategy — perubahan minimal pada CBT

**Lock mode:**
- Start Attempt memakai shared lock `FOR SHARE` pada AcademicYear dan Semester yang terkait, sebelum mengunci Exam.
- Lifecycle switch memakai exclusive lock `FOR UPDATE` pada Tenant, AcademicYear, dan Semester.
- MySQL 8 mendukung `SELECT ... FOR SHARE`; sintaks lama `LOCK IN SHARE MODE` masih kompatibel. Query builder Laravel harus diverifikasi menghasilkan SQL yang didukung versi MySQL target.

**Urutan lock:**

1. Pergantian Semester: Tenant (UPDATE) → AcademicYear (UPDATE) → Semester lama dan target (UPDATE, urut ID) → pointer.
2. Pergantian Tahun: Tenant (UPDATE) → AcademicYear lama dan target (UPDATE, urut ID) → Semester lama dan target (UPDATE, urut ID) → pointer.
3. Start Attempt: pre-read nonlocking untuk mendapatkan year/semester ID → AcademicYear (SHARE) → Semester (SHARE) → Exam (UPDATE) → Participant (UPDATE) → Attempt (UPDATE) → User/DeviceLease sesuai urutan yang ada.
4. Resume: tetap Exam → Participant → Attempt → DeviceLease.
5. Sync Answer: tetap Exam → Participant → Attempt → DeviceLease → request ledger → Answer.
6. Submit: tetap Exam → Participant → Attempt → DeviceLease. Tidak memerlukan periode aktif karena Attempt sudah dimulai.
7. Expiry recovery: tetap Exam → Participant → Attempt → DeviceLease → scoring.
8. Scoring/manual grading/finalize/recalculate/reopen: Attempt → score pointer → score → question score. ScoringService harus menegakkan Attempt lock sebelum pointer bila dipanggil sebagai entry point mandiri.

**Mengapa tidak deadlock dengan lifecycle:**
- Lifecycle tidak mengunci Exam, Participant, Attempt, Answer, atau Score.
- Start Attempt mengunci Year/Semester SHARE terlebih dahulu; lifecycle menunggu exclusive lock Year.
- Jika Start menang, Attempt baru dibuat dan transaksi selesai; lifecycle kemudian berpindah. Attempt itu menjadi Attempt existing dan boleh diselesaikan.
- Jika lifecycle menang, Start menunggu; setelah commit, Start membaca status lama sebagai nonaktif dan ditolak.
- Resume/sync/submit/recovery/scoring tidak meminta lock Year/Semester, sehingga Attempt lama dapat terus berjalan tanpa siklus lock dengan lifecycle.
- Dua lifecycle switch pada tenant sama diserialisasi oleh row Tenant. Request kedua wajib membaca ulang pointer dan transition_version setelah mendapat lock.

**Rollback/retry:**
- Semua perubahan periode, pointer, transition ledger, dan audit log dalam satu transaksi.
- Deadlock MySQL 1213: rollback seluruh transaksi, retry seluruh unit maksimal 3 kali.
- Lock wait timeout 1205: jangan lanjutkan transaksi seolah statement sukses; rollback seluruh transaksi dan kembalikan error retryable/503 atau retry penuh secara terbatas.
- Unique/FK/check violation: bukan retry; hentikan dan catat integrity conflict.
- Tidak mengubah global `innodb_lock_wait_timeout` tanpa rencana operasi.
- Transaksi harus pendek; tidak boleh melakukan upload, HTTP call, atau pekerjaan berat di dalam lock.
- Gunakan `request_key` unik per tenant + expected transition_version untuk idempotensi dan penolakan request stale.

**Attempt yang sudah berjalan:**
- Pergantian periode tidak menghapus, reset, atau memaksa close Attempt lama.
- Attempt lama boleh resume, sync jawaban, submit, expire, dan scoring berdasarkan snapshot immutable.
- Attempt baru dari periode lama ditolak setelah switch commit.
- Force Submit berotorisasi belum tersedia di route yang diperiksa; jika dibuat kelak, ia harus mengunci Exam → Participant → Attempt dan boleh menutup Attempt lama tanpa memerlukan periode aktif.

## 3. PRE-FLIGHT — QUERY READ-ONLY V2

Semua query di bawah hanya SELECT dan harus dijalankan pada database disposable atau salinan representatif. Jangan jalankan pada database produksi.

### 3.1 Versi, engine, isolation, dan FK

```sql
SELECT VERSION() AS mysql_version,
       @@transaction_isolation AS transaction_isolation,
       @@foreign_key_checks AS foreign_key_checks,
       @@innodb_lock_wait_timeout AS innodb_lock_wait_timeout;

SELECT TABLE_NAME, ENGINE
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'tenants','academic_years','semesters','rombels',
    'rombel_student','teacher_subject_assignments','assessments',
    'exams','exam_question_snapshots','exam_participants',
    'exam_attempts','exam_attempt_answers','exam_attempt_scores',
    'exam_question_scores','exam_attempt_score_current',
    'audit_logs'
  );
```

Target minimum desain CHECK: MySQL 8.0.16 atau lebih baru. CI sebelumnya memakai MySQL 8.0.46, tetapi versi instalasi target belum diverifikasi.

### 3.2 Seluruh Tenant tanpa Tahun Ajaran aktif / duplikasi aktif

```sql
SELECT t.id AS tenant_id, t.code, t.status,
       COUNT(ay.id) AS total_years,
       SUM(CASE WHEN ay.is_active = 1 AND ay.deleted_at IS NULL
                THEN 1 ELSE 0 END) AS active_years
FROM tenants t
LEFT JOIN academic_years ay ON ay.tenant_id = t.id
WHERE t.id <> '00000000-0000-0000-0000-000000000000'
GROUP BY t.id, t.code, t.status
HAVING active_years <> 1;
```

Interpretasi:
- 0 = tenant belum memiliki tahun aktif; wajib rekonsiliasi sebelum pointer dibuat.
- >1 = data duplikat; jangan memilih record dengan first()/latest() otomatis.

### 3.3 Tahun aktif tanpa Semester aktif dan semester ganda

```sql
SELECT ay.tenant_id, ay.id AS academic_year_id, ay.name,
       SUM(CASE WHEN s.is_active = 1 AND s.deleted_at IS NULL
                THEN 1 ELSE 0 END) AS active_semesters
FROM academic_years ay
LEFT JOIN semesters s
  ON s.academic_year_id = ay.id
 AND s.tenant_id = ay.tenant_id
WHERE ay.is_active = 1 AND ay.deleted_at IS NULL
GROUP BY ay.tenant_id, ay.id, ay.name
HAVING active_semesters <> 1;
```

### 3.4 Semester aktif dengan parent nonaktif / lintas tenant

```sql
SELECT s.id AS semester_id, s.tenant_id, s.academic_year_id,
       s.term, ay.tenant_id AS parent_tenant_id,
       ay.is_active AS parent_is_active, ay.deleted_at AS parent_deleted_at
FROM semesters s
LEFT JOIN academic_years ay ON ay.id = s.academic_year_id
WHERE s.is_active = 1
  AND (ay.id IS NULL OR ay.tenant_id <> s.tenant_id
       OR ay.is_active <> 1 OR ay.deleted_at IS NOT NULL);
```

### 3.5 Kombinasi state dan soft-deleted period

```sql
SELECT 'academic_years' AS source_table, id, tenant_id, is_active,
       closed_at, archived_at, deleted_at
FROM academic_years
WHERE (is_active = 1 AND (closed_at IS NOT NULL OR archived_at IS NOT NULL))
   OR (archived_at IS NOT NULL AND closed_at IS NULL)
   OR deleted_at IS NOT NULL
UNION ALL
SELECT 'semesters', id, tenant_id, is_active,
       closed_at, archived_at, deleted_at
FROM semesters
WHERE (is_active = 1 AND (closed_at IS NOT NULL OR archived_at IS NOT NULL))
   OR (archived_at IS NOT NULL AND closed_at IS NULL)
   OR deleted_at IS NOT NULL;
```

Jika kolom closed_at/archived_at belum ada, query ini dijalankan setelah versi query disesuaikan dengan schema saat ini; jangan menganggap kolom tersebut sudah ada.

### 3.6 Orphan/mismatch akademik

```sql
-- Semester tanpa parent / parent tenant mismatch
SELECT s.id, s.tenant_id, s.academic_year_id
FROM semesters s
LEFT JOIN academic_years ay ON ay.id = s.academic_year_id
WHERE ay.id IS NULL OR ay.tenant_id <> s.tenant_id;

-- Rombel tanpa parent year / tenant mismatch
SELECT r.id, r.tenant_id, r.academic_year_id
FROM rombels r
LEFT JOIN academic_years ay ON ay.id = r.academic_year_id
WHERE ay.id IS NULL OR ay.tenant_id <> r.tenant_id;

-- Assignment mismatch year-semester-rombel
SELECT tsa.id, tsa.tenant_id, tsa.academic_year_id, tsa.semester_id,
       s.academic_year_id AS semester_year_id,
       r.academic_year_id AS rombel_year_id
FROM teacher_subject_assignments tsa
LEFT JOIN semesters s ON s.id = tsa.semester_id
LEFT JOIN rombels r ON r.id = tsa.rombel_id
WHERE s.id IS NULL OR r.id IS NULL
   OR s.tenant_id <> tsa.tenant_id OR r.tenant_id <> tsa.tenant_id
   OR s.academic_year_id <> tsa.academic_year_id
   OR r.academic_year_id <> tsa.academic_year_id;

-- Assessment mismatch year-semester-tenant
SELECT a.id, a.tenant_id, a.academic_year_id, a.semester_id,
       s.academic_year_id AS semester_year_id,
       s.tenant_id AS semester_tenant_id
FROM assessments a
LEFT JOIN semesters s ON s.id = a.semester_id
WHERE a.semester_id IS NULL OR s.id IS NULL
   OR s.tenant_id <> a.tenant_id
   OR s.academic_year_id <> a.academic_year_id;
```

Assessment semester NULL harus diklasifikasikan manual; jangan backfill dengan semester aktif saat ini karena itu dapat memalsukan histori.

### 3.7 Orphan/mismatch CBT chain

```sql
-- Exam tanpa Assessment
SELECT e.id, e.tenant_id, e.assessment_id
FROM exams e LEFT JOIN assessments a ON a.id = e.assessment_id
WHERE a.id IS NULL OR a.tenant_id <> e.tenant_id;

-- Participant tanpa Exam/User tenant yang sama
SELECT ep.id, ep.tenant_id, ep.exam_id, ep.user_id
FROM exam_participants ep
LEFT JOIN exams e ON e.id = ep.exam_id
LEFT JOIN users u ON u.id = ep.user_id
WHERE e.id IS NULL OR u.id IS NULL
   OR e.tenant_id <> ep.tenant_id OR u.tenant_id <> ep.tenant_id;

-- Attempt tanpa Exam/Participant konsisten
SELECT ea.id, ea.tenant_id, ea.exam_id, ea.participant_id
FROM exam_attempts ea
LEFT JOIN exams e ON e.id = ea.exam_id
LEFT JOIN exam_participants ep ON ep.id = ea.participant_id
WHERE e.id IS NULL OR ep.id IS NULL
   OR e.tenant_id <> ea.tenant_id OR ep.tenant_id <> ea.tenant_id
   OR ep.exam_id <> ea.exam_id;

-- Answer tanpa Attempt/Snapshot/Exam/tenant yang cocok
SELECT ans.id, ans.tenant_id, ans.exam_id, ans.exam_attempt_id,
       ans.exam_question_snapshot_id
FROM exam_attempt_answers ans
LEFT JOIN exam_attempts a ON a.id = ans.exam_attempt_id
LEFT JOIN exam_question_snapshots s ON s.id = ans.exam_question_snapshot_id
WHERE a.id IS NULL OR s.id IS NULL
   OR a.tenant_id <> ans.tenant_id OR s.tenant_id <> ans.tenant_id
   OR a.exam_id <> ans.exam_id OR s.exam_id <> ans.exam_id;

-- Score tanpa Attempt/Exam tenant yang cocok
SELECT sc.id, sc.tenant_id, sc.exam_id, sc.attempt_id
FROM exam_attempt_scores sc
LEFT JOIN exam_attempts a ON a.id = sc.attempt_id
LEFT JOIN exams e ON e.id = sc.exam_id
WHERE a.id IS NULL OR e.id IS NULL
   OR a.tenant_id <> sc.tenant_id OR e.tenant_id <> sc.tenant_id
   OR a.exam_id <> sc.exam_id;
```

Tambahkan query serupa untuk `exam_question_scores`, `exam_attempt_score_current`, `device_leases`, `exam_attempt_answer_requests`, dan snapshot.

### 3.8 Dampak CASCADE/SET NULL dan jalur dari Tenant

```sql
SELECT
  kcu.TABLE_NAME AS child_table,
  kcu.COLUMN_NAME AS child_column,
  kcu.CONSTRAINT_NAME,
  kcu.REFERENCED_TABLE_NAME AS parent_table,
  kcu.REFERENCED_COLUMN_NAME AS parent_column,
  rc.DELETE_RULE,
  rc.UPDATE_RULE
FROM information_schema.KEY_COLUMN_USAGE kcu
JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
  ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
 AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
 AND rc.TABLE_NAME = kcu.TABLE_NAME
WHERE kcu.CONSTRAINT_SCHEMA = DATABASE()
  AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
  AND (
    kcu.REFERENCED_TABLE_NAME IN (
      'tenants','academic_years','semesters','rombels',
      'assessments','exams','exam_question_snapshots',
      'exam_participants','exam_attempts','exam_attempt_answers',
      'exam_attempt_scores','exam_question_scores',
      'exam_attempt_score_current','users'
    )
    OR kcu.TABLE_NAME IN (
      'academic_years','semesters','rombels','assessments',
      'exams','exam_question_snapshots','exam_participants',
      'exam_attempts','exam_attempt_answers','exam_attempt_scores',
      'exam_question_scores','exam_attempt_score_current'
    )
  )
ORDER BY parent_table, child_table, kcu.CONSTRAINT_NAME, kcu.ORDINAL_POSITION;
```

Untuk cascade closure, hasil query ini harus diekspor dan dibangun menjadi graph parent→child. Setiap edge CASCADE dan SET NULL pada jalur historis harus diberi keputusan RESTRICT/retensi/immutable snapshot. Jangan menganggap satu query FK langsung cukup untuk menghitung seluruh cascade graph.

### 3.9 Inventaris jumlah histori per tahun

Hitung jumlah Rombel, membership, assignment, Assessment, Exam, Snapshot, Participant, Attempt per status, Answer, Score, ScoreCurrent, TP/ATP/CP achievement dan mapping per tenant/tahun. Simpan hasil sebagai baseline sebelum dan sesudah migration disposable.

## 4. TEST PLAN FINAL

### 4.1 Constraint dan state

1. Tenant tanpa Tahun Ajaran aktif terdeteksi dan fail-closed.
2. Tenant dengan dua Tahun Ajaran aktif ditolak unique constraint.
3. Tahun operasional tanpa Semester aktif tidak dapat memiliki pointer valid.
4. Dua Semester aktif pada Tahun Ajaran yang sama ditolak unique constraint.
5. Semester aktif pada Tahun Ajaran nonaktif ditolak FK generated stored.
6. Semester persiapan nonaktif pada Tahun Ajaran persiapan diterima.
7. Kombinasi ACTIVE+CLOSED/ARCHIVED ditolak CHECK.
8. ARCHIVED tanpa closed_at ditolak CHECK.
9. Soft delete AcademicYear/Semester ditolak.
10. Hard delete period ditolak oleh FK atau privilege runtime.
11. Tenant aktif tanpa pointer ditolak oleh onboarding/preflight/resolver; System Tenant dikecualikan.
12. Pointer tidak dapat menunjuk ke Semester tenant/tahun lain.

### 4.2 Tenant isolation dan integritas FK

13. Tenant A tidak dapat mengubah/membaca pointer Tenant B.
14. Cross-tenant year/semester/rombel/assignment/assessment ditolak DB.
15. FK komposit existing Assessment dan Teacher Subject Assignment tetap berfungsi.
16. FK baru tidak menduplikasi constraint lama.
17. DELETE Tenant, Year, Semester, Assessment, Exam, Participant, Attempt, Snapshot, User yang memiliki histori ditolak.
18. SET NULL tidak boleh menghilangkan atribusi periode atau provenance snapshot.
19. Penghapusan creator tidak menghapus Assessment/Exam/Question.
20. Soft delete akun tidak menghapus Participant/Attempt.

### 4.3 Transisi dan transaksi

21. Pergantian semester menutup semester lama, mengaktifkan target, mengubah pointer, membuat audit log dalam satu commit.
22. Pergantian tahun menutup semester+tahun lama, mengaktifkan tahun+semester baru, mengubah pointer dalam satu commit.
23. Tahun baru PREPARING tidak mengganggu pointer periode aktif lama.
24. Target semester dari tahun yang salah ditolak.
25. Target CLOSED/ARCHIVED ditolak.
26. Failure injection setelah penutupan semester lama menyebabkan seluruh transaksi rollback.
27. Failure injection setelah penutupan tahun lama menyebabkan seluruh transaksi rollback.
28. Audit log gagal ditulis → seluruh perubahan periode rollback.
29. Request key yang sama diulang → hasil idempotent, tanpa audit/transisi duplikat.
30. Request dengan transition_version stale ditolak.

### 4.4 Concurrency dua koneksi MySQL 8/InnoDB

31. Dua koneksi mengaktifkan Tahun Ajaran berbeda pada tenant yang sama; hasil akhir tepat satu pasangan aktif.
32. Dua koneksi mengaktifkan Semester berbeda pada Tahun Ajaran yang sama; hasil akhir tepat satu Semester aktif.
33. Start Attempt dan semester switch bersamaan:
    - jika Start memperoleh shared lock dahulu, Attempt dibuat sebelum switch commit dan menjadi Attempt existing;
    - jika switch memperoleh exclusive lock dahulu, Start menunggu lalu ditolak terhadap periode lama.
34. Start Attempt dan year switch bersamaan dengan dua hasil serialisasi yang sama.
35. Attempt IN_PROGRESS sebelum switch tetap dapat resume, sync Answer, submit, expire, dan scoring setelah switch.
36. Attempt baru pada periode lama ditolak setelah switch commit.
37. Recovery expiry bersamaan dengan submit tetap menghasilkan satu terminal status.
38. Scoring bersamaan dengan submit tidak menghasilkan score dari Attempt IN_PROGRESS.
39. Dua transisi bersamaan diserialisasi oleh row Tenant dan expected version.
40. Deadlock 1213 retry seluruh transaksi; tidak ada partial commit.
41. Lock timeout 1205 rollback seluruh transaksi; tidak ada kelanjutan setelah statement timeout.
42. Deadlock/retry tidak menggandakan audit atau transition ledger.

### 4.5 Histori snapshot dan hasil ujian

43. Tutup/arsip Tahun Ajaran tidak mengubah jumlah snapshot, answer, attempt, score.
44. Tutup/arsip Semester tidak mengubah Assessment/Exam/Attempt.
45. Reopen report untuk tahun lama tetap menampilkan data historis.
46. Snapshot tetap dapat dibaca walau sumber soal sudah tidak dipakai; FK source history tetap RESTRICT.
47. Score version dan score-current pointer tetap konsisten setelah transisi periode.
48. Penghapusan melalui Tenant/Assessment/Exam/Participant/Attempt ditolak pada semua jalur yang dapat menghapus histori.

### 4.6 Kompatibilitas

49. Fresh install MySQL 8.0.16+.
50. Upgrade pada clone database existing dengan semua migration historis sudah tercatat.
51. Upgrade pada clone dengan migration parsial/constraint rename; harus fail-closed sebelum DDL.
52. Test engine InnoDB, charset/collation UUID, FK composite ordering, generated STORED column, CHECK enforcement.
53. Test SQL query builder Laravel `sharedLock()` dan `lockForUpdate()` menghasilkan syntax yang diterima MySQL target.
54. Test tidak hanya SQLite; concurrency harus memakai dua koneksi MySQL terpisah.

## 5. RISIKO YANG BELUM TERSELESAIKAN

1. **Database representatif belum tersedia.** Belum diketahui jumlah tenant tanpa periode aktif, duplikasi, orphan, mismatch, soft-deleted periods, maupun jumlah histori per tahun.
2. **Schema aktual belum diverifikasi.** Migration repository tidak membuktikan migration sudah diterapkan pada setiap instalasi. FK aktual harus dibaca dari INFORMATION_SCHEMA clone.
3. **Versi MySQL target belum diketahui.** CHECK membutuhkan MySQL 8.0.16+. Generated STORED/FK harus dibuktikan pada versi target dan engine InnoDB.
4. **Tenant System Global** perlu dikecualikan dari kewajiban periode akademik; tenant madrasah lain harus memiliki pointer aktif.
5. **Cascade graph luas.** Tenant/User/Assessment/Exam/Participant/Attempt masih memiliki jalur cascade berisiko; perubahan harus forward-only dan diuji terhadap histori existing.
6. **Soft delete model** AcademicYear/Semester masih aktif melalui trait SoftDeletes. CHECK/privilege atau mekanisme proteksi lain harus selaras dengan perilaku Eloquent.
7. **ScoringService lock contract** perlu dinormalisasi tanpa mengubah scoring semantics.
8. **Force Submit belum ditemukan** sebagai endpoint terpisah. Ini gap fitur/otorisasi yang tidak boleh diselesaikan diam-diam dalam lifecycle patch.
9. **Rombel selection saat finalize** memakai `rombels()->first()`; perlu aturan pemilihan rombel yang tepat untuk histori hasil belajar.
10. **Periode operasional dan periode persiapan** belum sepenuhnya dibedakan pada semua alur assignment/assessment/exam. Matriks izin harus ditetapkan pada service.
11. **Tenant hard-delete protection** perlu privilege runtime yang terpisah dari akun migration. Jika tidak tersedia, evaluasi trigger menjadi tahap desain terpisah.
12. **Tidak ada test lifecycle khusus** saat ini; concurrency test yang ada menguji Exam→Participant→Attempt, bukan race start-vs-period-switch.

## 6. FILE SOURCE YANG AKAN TERDAMPAK JIKA DISETUJUI

### Database/migration (baru, forward-only)
- `database/migrations/<timestamp>_add_academic_period_lifecycle_metadata.php`
- `database/migrations/<timestamp>_add_active_academic_period_constraints.php`
- `database/migrations/<timestamp>_create_tenant_active_academic_periods_table.php`
- `database/migrations/<timestamp>_protect_academic_period_and_cbt_history_fks.php`
- Mungkin migration terpisah untuk FK creator/participant/user dan tenant cascade setelah preflight graph selesai.

### Models/services
- `app/Models/AcademicYear.php`
- `app/Models/Semester.php`
- `app/Models/Tenant.php` (relasi pointer, bukan perubahan identitas tenant)
- Model baru `TenantActiveAcademicPeriod.php`
- Service baru `AcademicPeriodLifecycleService.php`
- Service baru `ActiveAcademicPeriodResolver.php`
- Service/jurnal baru untuk transition idempotency bila diperlukan.

### Livewire/controller
- `app/Livewire/Admin/AcademicManagement.php` (UI lifecycle Admin)
- `app/Livewire/Admin/Dashboard.php`
- `app/Livewire/Operator/OperatorDashboard.php`
- `app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`
- `app/Livewire/Admin/ExamManagement.php`
- `app/Http/Controllers/Api/V1/TeacherSubjectAssignmentController.php`
- `app/Http/Controllers/Api/V1/ExamAttemptController.php` (Start Attempt saja; lock periode sebelum Exam)
- `app/Services/TeacherPortal/TeacherPortalDataService.php`
- `app/Services/TeacherPortal/TeacherCurriculumContextService.php`
- `app/Http/Controllers/Api/V1/ScoringController.php` / `app/Services/ScoringService.php` hanya untuk konsistensi lock dan pemilihan Rombel jika disetujui setelah audit dampak.

### Test
- Test baru `tests/Feature/AcademicPeriodLifecycleTest.php`
- Test baru `tests/Feature/AcademicPeriodDatabaseConstraintTest.php`
- Test baru `tests/Feature/AcademicPeriodAttemptConcurrencyTest.php`
- Test baru `tests/Feature/AcademicPeriodHistoryRetentionTest.php`
- Penyesuaian fixture/factory untuk Year/Semester aktif dan tenant pointer.
- Existing `AttemptTransactionConcurrencyTest.php`, `SubmitLockingTest.php`, `ExamSnapshotTest.php`, dan `ScoringHardeningTest.php` hanya diubah bila fixture/kontrak memang terdampak.

Tidak ada file di daftar ini yang diubah pada checkpoint ini. Daftar final file harus diperbarui setelah preflight clone membuktikan FK dan writer yang benar-benar ada.

## 7. KEPUTUSAN DAN GATE BERIKUTNYA

Belum ada izin implementasi. Gate yang masih terbuka:

1. Review desain pointer + generated STORED FK + CHECK + privilege; konfirmasi apakah desain ini diterima sebagai arah final.
2. Sediakan database disposable/clone representatif (bukan produksi) atau hasil ekspor schema+data yang telah disanitasi.
3. Jalankan seluruh query preflight read-only dan simpan output.
4. Rekonsiliasi semua duplicate/orphan/mismatch secara eksplisit.
5. Verifikasi cascade graph dan privilege runtime/migration.
6. Baru susun migration forward-only final dan minta persetujuan implementasi terpisah.

**Tidak ada source berubah, migration dibuat, trigger dibuat, SQL mutasi dijalankan, deployment dilakukan, atau PR di-merge.**

## 8. Referensi MySQL 8

- CHECK constraint mulai enforced pada MySQL 8.0.16: https://dev.mysql.com/doc/refman/8.0/en/create-table-check-constraints.html
- InnoDB FK tidak dapat mereferensikan generated column VIRTUAL; STORED dapat diindeks dan digunakan untuk FK dengan pembatasan referential action: https://dev.mysql.com/doc/refman/8.0/en/create-table-generated-columns.html
- InnoDB locking reads `FOR SHARE` dan `FOR UPDATE`: https://dev.mysql.com/doc/refman/8.0/en/innodb-locking-reads.html
- Deadlock harus ditangani dengan retry seluruh transaksi; urutan lock konsisten: https://dev.mysql.com/doc/refman/8.0/en/innodb-deadlocks.html
- Lock wait timeout default membatalkan statement, bukan otomatis seluruh transaksi: https://dev.mysql.com/doc/refman/8.0/en/innodb-parameters.html
