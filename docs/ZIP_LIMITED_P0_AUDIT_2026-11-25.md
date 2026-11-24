# Audit P0 — Kelayakan ZIP Siap Uji Operasional Terbatas

- Tanggal: 2026-11-25
- Repository: `zane0184/cbt_november`
- Source Agent 1 yang diperiksa: `a65b4604495e8bcc153bf0e09df12a9efb11543a`
- Branch source Agent 1: `work/benchmark-cleanup-proof-20261125`
- Branch audit: `audit/independent-qa-admin-guru-siswa-20261125`
- Head branch audit sebelum laporan ini: `c1de3108fa09c050914068ed2f294a8ebb5a193e`
- Metode: inspeksi source dan test melalui GitHub; pemeriksaan CI Agent 1; tidak ada perubahan source aplikasi.
- Tujuan: memutuskan blocker P0 yang benar-benar menghambat ZIP Web CBT untuk uji operasional terbatas pada instalasi mandiri madrasah.

## 1. Perubahan ruang lingkup

Audit kelengkapan seluruh CBT yang tertulis pada laporan baseline sebelumnya **tidak menjadi prioritas pekerjaan sekarang**. Untuk delivery terdekat, audit dibatasi pada:

1. Setup identitas madrasah dan data akademik minimal.
2. Pembuatan akun Guru dan Siswa.
3. Bank soal, asesmen, Exam Builder, publish dan token.
4. Login Siswa, akses ujian yang ditugaskan, menjawab, menyimpan, submit, dan hasil.
5. Konsistensi waktu server, Attempt, Answer, Submit, Score, transaksi dan idempotensi.
6. Isolasi tenant, role/permission dan akses objek.
7. Import peserta yang diperlukan, installer dan paket ZIP.

Fitur nonkritis cukup dicatat sebagai backlog CBT final. Android native peserta tidak menjadi blocker ZIP Web sekarang; sesuai keputusan produk, Android ditunda sampai Juni 2027.

## 2. Ringkasan keputusan audit

| ID | Area | Status audit saat ini | Dampak ZIP uji operasional terbatas | Status backlog CBT final |
|---|---|---|---|---|
| P0-01 | Tahun pelajaran, semester, tingkat kelas, rombel | **BLOCKER — NOT IMPLEMENTED pada alur setup Admin instalasi baru** | **YA** | Wajib diselesaikan |
| P0-02 | CP resmi dan pemetaan CP → TP/ATP → soal → asesmen → ujian | **BLOCKER — BLOCKED oleh artefak CP resmi yang belum diimpor/checksum-verified** | **YA**, karena alur authoring mewajibkan mapping | Wajib diselesaikan |
| P0-03 | Buat Guru manual dan import Siswa | **PASS pada source + CI untuk create/import yang diperiksa** | Tidak ada blocker source yang terbukti; import Guru massal tidak diperlukan untuk pilot kecil | Import Guru massal tetap backlog |
| P0-04 | Login Siswa, peserta/tenant ownership, jadwal dan akses ujian | **PASS pada feature-test CI; browser E2E NOT VERIFIED** | Gate uji browser kritis sebelum klaim siap uji operasional | Wajib terus diuji |
| P0-05 | Attempt, Answer, autosave, submit, waktu server, scoring | **PASS pada feature-test CI untuk kontrak yang diuji; browser E2E NOT VERIFIED** | Gate uji browser kritis sebelum ZIP dinyatakan lolos | Wajib terus diuji |
| P0-06 | RBAC, isolasi tenant, transaksi, idempotensi | **PASS pada test suite CI untuk skenario yang tersedia; tidak sama dengan sertifikasi keamanan menyeluruh** | Tidak ada FAIL source baru yang terkonfirmasi pada snapshot; wajib pertahankan negative test | Wajib dipertahankan dan diperluas |
| P0-07 | Installer dan clean-install mandiri | **PARTIAL — guard/test tersedia, clean-install E2E opt-in belum dibuktikan berjalan pada SHA ini** | **YA sebagai release gate** | Wajib |
| P0-08 | Pembuatan dan verifikasi ZIP deployment | **NOT VERIFIED — workflow tersedia, belum ada bukti artifact dibuat dari SHA ini** | **YA sebagai release gate** | Wajib untuk setiap rilis |

Status PASS di tabel hanya berlaku pada source/test/CI evidence yang disebut. Status tersebut bukan klaim browser E2E, UAT, deployment, atau siap produksi.

## 3. Temuan blocker yang terkonfirmasi

### P0-01 — Instalasi baru tidak menyediakan master akademik minimum

**Status:** BLOCKER — NOT IMPLEMENTED pada jalur setup Admin yang diperiksa.

**Bukti source pada SHA `a65b4604495e8bcc153bf0e09df12a9efb11543a`:**
- `backend-laravel/app/Services/InstallerService.php`: `createInitialDataAndAdmin()` membuat Tenant dan akun Admin; tidak membuat `AcademicYear`, `Semester`, atau `GradeLevel`.
- `backend-laravel/database/seeders/DatabaseSeeder.php`: bootstrap role/permission dan registry referensi kurikulum; tidak menginisialisasi master akademik lokal untuk Tenant baru.
- `backend-laravel/app/Livewire/Admin/AcademicManagement.php`: aksi create yang tersedia adalah Rombel dan mata pelajaran. Tab tingkat kelas hanya menampilkan data; tidak ada aksi create tahun pelajaran, semester, atau tingkat kelas.
- `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`: tidak menyediakan create master tahun/semester/kelas.
- `backend-laravel/app/Livewire/Admin/StudentRombelManagement.php`: pengelolaan siswa pada Rombel bergantung pada Rombel yang sudah ada.
- `backend-laravel/app/Livewire/Admin/StudentImport.php`: import Siswa mensyaratkan Rombel aktif.
- `backend-laravel/app/Livewire/Admin/ExamManagement.php`: pembuatan asesmen/ujian mensyaratkan assignment dan semester.

**Alur dampak:** instalasi baru → Admin membuat Rombel, tetapi form membutuhkan tahun pelajaran dan tingkat kelas yang sudah tersedia → import/penempatan siswa dan assignment Guru terhambat; semester juga dibutuhkan pada alur assignment/asesmen.

**Rekomendasi minimum untuk ZIP:** sediakan provisioning akademik lokal yang tervalidasi tenant untuk tahun pelajaran aktif, semester Ganjil/Genap, serta tingkat kelas MI 1–6 yang dipetakan ke fase A/B/C; atau UI Admin setara. Jangan mengandalkan fixture test atau data tenant lain. Pembuatan Rombel baru boleh dilakukan setelah prasyarat tersedia.

**Dampak ZIP:** YA — blocker instalasi mandiri baru.
**Backlog CBT final:** tetap wajib; perlu histori tahun ajaran/semester dan aturan perubahan periode.

### P0-02 — CP resmi belum tersedia sebagai konten terverifikasi, padahal alur soal dan ujian mewajibkannya

**Status:** BLOCKER — BLOCKED oleh artefak CP resmi yang belum diimpor dan diverifikasi.

**Bukti source:**
- `backend-laravel/database/seeders/CurriculumReferenceSourceSeeder.php`: sumber regulasi dicatat sebagai metadata referensi; statusnya metadata, bukan bukti checksum konten CP.
- `backend-laravel/database/seeders/CurriculumRegistryBootstrapSeeder.php`: metadata versi Kurikulum Merdeka secara eksplisit memuat `artifact_checksum_verified = false` dan `cp_content_imported = false`. File ini membangun struktur fase/kelas dan konteks, tetapi tidak membuat record `MultiLearningOutcome` CP.
- `backend-laravel/app/Services/TeacherPortal/TeacherCurriculumContextService.php`: status konteks menjadi `CP_REGISTRY_PENDING` bila tidak ada CP resmi dengan checksum artefak terverifikasi.
- `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php`: pembuatan TP ditolak jika CP induk belum memiliki sumber resmi berstatus `ARTIFACT_CHECKSUM_VERIFIED` dan checksum yang cocok.
- `backend-laravel/app/Livewire/Admin/QuestionBank.php`: `saveQuestion()` mewajibkan sekurangnya satu CP dan satu TP/ATP; `publishQuestion()` menolak CP yang belum terverifikasi checksum artefak resmi.
- `backend-laravel/app/Livewire/Admin/ExamManagement.php`: `saveExam()` juga mewajibkan mapping CP dan outcome.

**Kesimpulan:** ini bukan sekadar kelengkapan laporan kurikulum. Pada alur UI aktif yang diperiksa, Guru tidak dapat menempuh rantai authoring soal/asesmen/ujian tanpa mapping CP/TP yang sah. Sumber dokumen resmi yang dicatat sebagai metadata tidak dapat menggantikan data CP resmi per fase/kelas/mapel.

**Rekomendasi minimum untuk ZIP:** impor artefak CP resmi yang dapat diverifikasi (dokumen/sumber, versi, cakupan mapel/fase/kelas, checksum SHA-256, provenance), lalu verifikasi pemetaan CP ke struktur yang digunakan assignment Guru. Jangan mengarang CP atau menandai checksum terverifikasi tanpa memeriksa file sumber aslinya.

**Dampak ZIP:** YA — blocker jika alur Guru harus membuat dan menerbitkan soal/ujian melalui UI yang ada.
**Backlog CBT final:** tetap wajib; histori versi, perubahan regulasi, KBC/Panca Cinta, Pembelajaran Mendalam dan dimensi profil lulusan harus tetap konsisten.

## 4. Area yang tidak menjadi blocker source berdasarkan bukti yang tersedia

### P0-03 — Guru manual dan import Siswa

- `UserManagement.php` menyediakan pembuatan akun Guru manual dengan validasi identitas/kepegawaian dan pembuatan akun Siswa. Karena jumlah Guru pilot dapat dimasukkan manual, Import Guru massal tidak wajib untuk ZIP terbatas.
- `StudentImport.php` menyediakan preview dan import CSV/XLSX ke Rombel aktif.
- `StudentImportService.php` memvalidasi aktor Admin dan Rombel tenant, NISN teks 10 digit, duplikasi dalam file/database, batas ukuran/struktur file, hash file dan idempotency key, lalu mengimpor dalam transaksi serta mencatat batch/audit.
- CI Agent 1 pada run `45482223886` menunjukkan Student Import acceptance **20 passed (77 assertions)** pada kedua job PHP 8.3/MySQL 8.0 dan PHP 8.4/MySQL 8.0. Full suite kedua job: **619 passed, 1 skipped (3110 assertions)**.
- Ini tidak membuktikan proses import melalui browser pada server madrasah; tetap masuk skenario smoke test setelah ZIP dibuat.

**Dampak ZIP:** Import Siswa tersedia pada source/CI; prasyarat Rombel harus diselesaikan oleh P0-01.
**Backlog CBT final:** Import Guru massal tetap backlog, bukan blocker saat Guru dapat dibuat manual.

### P0-04 — Login Siswa, penugasan peserta, jadwal, dan kepemilikan objek

Test yang relevan ada dan lulus pada CI run `45482223886`, termasuk:
- `StudentWebLoginTest`
- `StudentExamPortalTest`
- `ParticipantAuthorizationAndAttemptLimitTest`
- `ExamExecutionHardeningTest`
- `TenantIsolationTest`
- `MultiTenantAuthTest`

Source `StudentExamController.php` memeriksa role SISWA, tenant, peserta ujian, jendela eligibility, attempt limit, dan hasil milik Siswa. API Attempt juga mensyaratkan role SISWA dan mengunci Exam/Participant/Attempt dalam transaksi sesuai urutan lifecycle.

**Status:** PASS pada feature-test CI yang tersedia; browser E2E tetap NOT VERIFIED.

### P0-05 — Token, waktu server, Attempt, Answer, Submit dan Score

Bukti source/test yang tersedia:
- Publish/lock ujian membuat token secara otomatis pada alur Exam Builder; token baru hanya dapat dibuat setelah ujian diterbitkan/dikunci.
- Start Attempt memeriksa role/session/device binding, status Exam, participant dan jadwal, lalu menggunakan transaksi serta row lock.
- Answer update memvalidasi role SISWA, device binding, payload berdasarkan tipe soal, dan transaksi.
- Scoring memvalidasi question order agar tidak kosong, malformed, atau duplikat; pointer nilai aktif diperiksa terhadap target score.
- Test yang relevan mencakup `ServerTimeAndClockTamperingTest`, `AttemptTransactionConcurrencyTest`, `AttemptAuthSessionBindingTest`, `ExamAnswerTest`, `SubmitLockingTest`, `ScoringHardeningTest`, `StudentResultVisibilityTest`, dan `ExamExecutionHardeningTest`.
- CI MySQL 8 menjalankan suite lengkap dan concurrency test; kedua job sukses.

**Status:** PASS untuk kontrak feature-test yang dijalankan CI; NOT VERIFIED untuk rangkaian browser E2E dengan refresh/reconnect dan pengguna nyata. Jangan menyebut Device Lease/fencing siap produksi hanya dari bukti ini.

### P0-06 — RBAC, isolasi tenant, transaksi dan idempotensi

- Test suite CI mencakup `RBACEnforcementTest`, `TenantIsolationTest`, `MultiTenantAuthTest`, `AttemptTransactionConcurrencyTest`, `StudentImportTest`, `SubmitLockingTest`, dan test security lain.
- Dua job CI pada PHP 8.3 dan PHP 8.4 dengan MySQL 8.0/InnoDB sukses pada source SHA Agent 1.
- Tidak ada FAIL baru yang dapat dipastikan hanya dari inspeksi statis P0 ini. Namun, test yang ada tidak sama dengan audit keamanan menyeluruh atau bukti staging/UAT.

**Status:** PASS pada test CI yang tersedia; pemeriksaan browser dan uji operasional lintas role tetap menjadi gate.

## 5. Installer dan ZIP — release gates

### P0-07 — Installer

Bukti positif:
- `InstallerController` dan `InstallerService` menerapkan bootstrap token minimal 32 karakter, session authorization, pemeriksaan PHP/extensions/HTTPS/APP_KEY, inspeksi MySQL 8+/InnoDB, database harus kosong, fingerprint koneksi, state installer, exclusive lock, migration ledger, dan fail-closed ketika state rusak.
- `InstallerStateStore` menulis state secara atomik dengan permission 0600.
- `InstallerTest.php` menguji bootstrap authorization, penolakan request tanpa sesi otorisasi, dan penolakan pembuatan Admin tanpa token.
- `InstallerEndToEndTest.php` memiliki clean-install acceptance untuk database disposable dengan nama berawalan `cbt_installer_test_`; test ini hanya aktif jika `CBT_INSTALLER_E2E=1`.

Yang belum terbukti:
- Tidak ada bukti pada run CI yang diperiksa bahwa clean-install E2E opt-in dijalankan untuk SHA ini.
- Tidak ada bukti wizard installer berhasil dari database kosong sampai installer terkunci pada MySQL disposable untuk artefak ZIP yang akan dibagikan.

**Dampak ZIP:** release gate — jalankan clean-install E2E hanya pada database disposable terpisah, lalu uji ulang installer dari hasil ekstraksi ZIP.
**Backlog CBT final:** wajib; termasuk recovery install state dan upgrade path.

### P0-08 — Workflow ZIP

Bukti source:
- `.github/workflows/build-web-deployment.yml` tersedia dengan `workflow_dispatch`.
- Workflow melakukan Composer validate/install locked dependencies, platform requirements, route check, staging tree, pengecualian .env/backup/tests/demo/debug, manifest, checksum file, ZIP, `unzip -t`, daftar isi, SHA-256 dan upload artifact.
- `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md` memuat prosedur instalasi mandiri, APP_KEY, bootstrap token, DB kosong, HTTPS, permission, dan lisensi.
- Workflow push trigger tidak mencantumkan branch Agent 1 saat ini `work/benchmark-cleanup-proof-20261125`; tetapi workflow menyediakan `workflow_dispatch`.

Yang belum terbukti:
- Workflow packaging belum dibuktikan berjalan pada source SHA `a65b4604495e8bcc153bf0e09df12a9efb11543a`.
- Belum ada artifact ZIP, manifest, contents list, dan SHA-256 yang dapat diverifikasi untuk SHA tersebut.
- Belum ada bukti ekstraksi dan instalasi dari ZIP artifact hasil workflow.

**Dampak ZIP:** YA — ZIP belum boleh disebut siap dibagikan sebelum workflow dijalankan pada commit final, artifact diunduh/diverifikasi, dan clean-install smoke test lulus.
**Backlog CBT final:** setiap rilis harus menghasilkan manifest, checksum, dan verifikasi isi.

## 6. Daftar blocker wajib sebelum ZIP

### Blocker implementasi source
1. **P0-01:** sediakan tahun pelajaran, semester, dan tingkat kelas per instalasi madrasah baru sehingga Admin dapat membuat Rombel, assignment dan menempatkan Siswa.
2. **P0-02:** sediakan data CP resmi yang benar-benar diimpor dan checksum-verified karena UI Bank Soal dan Exam Builder mewajibkan mapping CP/TP.

### Blocker bukti/release gate
3. **P0-07:** jalankan clean-install E2E opt-in pada MySQL disposable dari source final; jangan menyentuh database produksi.
4. **P0-08:** jalankan workflow packaging `workflow_dispatch` pada SHA final Agent 1; verifikasi ZIP integrity, daftar isi, manifest, SHA-256, tidak ada .env/backup/test/debug, lalu ekstrak dan jalankan smoke test instalasi.
5. **P0-04/P0-05/P0-06:** jalankan browser E2E minimal pada hasil ekstraksi ZIP: Admin setup → buat Guru → buat Rombel dan import/daftarkan Siswa → Guru membuat soal/asesmen/ujian, publish dan token → Siswa login, membuka ujian sesuai jadwal, menyimpan jawaban, refresh/reconnect, submit → verifikasi Score/hasil. Sertakan negative test akses lintas tenant/role dan duplicate submit. Sampai bukti ini ada, status tetap NOT VERIFIED.

Tidak ada alasan untuk menahan ZIP terbatas karena Edit Profil, Import Guru massal, Monitoring Admin, Proktor, Android native, atau DOCX import apabila mekanisme input yang tersedia cukup untuk skenario uji awal. Fitur tersebut tetap berada pada backlog CBT final.

## 7. Bukti CI Agent 1 yang diperiksa

- Workflow run: `45482223886` — DOCX Import Parser CI.
- PHP 8.3 / MySQL 8.0 InnoDB: SUCCESS.
- PHP 8.4 / MySQL 8.0 InnoDB: SUCCESS.
- Kedua job menjalankan PHP lint, Student Import acceptance, benchmark cleanup denial-path, dan complete PHPUnit suite.
- Hasil full suite masing-masing job: `1 skipped, 619 passed (3110 assertions)`.
- Student Import acceptance: `20 passed (77 assertions)`.
- Ini CI Agent 1, bukan test yang dijalankan Agent 2.

## 8. Backlog nonkritis untuk CBT final

| Fitur | Dampak ZIP terbatas | Status backlog CBT final |
|---|---|---|
| Edit profil/akun Guru dan Siswa | Tunda; data awal dapat dipastikan sebelum dibuat | BELUM SELESAI / backlog |
| Import Guru massal | Tunda; Guru dapat dibuat manual | BELUM SELESAI / backlog |
| Monitoring operasional Admin | Tunda jika guru/panitia dapat menjalankan skenario terbatas tanpa dashboard Admin | NOT VERIFIED / backlog |
| Penugasan Proktor sementara | Tunda bila ujian awal tidak memakai Proktor | NOT IMPLEMENTED / backlog |
| Import soal DOCX | Tunda bila authoring manual atau jalur import lain yang tersedia cukup | NOT VERIFIED / backlog |
| Android native peserta | Tidak menjadi blocker ZIP Web saat ini; ditunda sampai Juni 2027 | DEFERRED sesuai keputusan |
| Analitik, laporan lanjutan, backup automation, queue/Redis, performance skala besar, upgrade/versioning | Tidak diaudit mendalam pada tahap ini | BACKLOG / belum disertifikasi |

## 9. Deklarasi keselamatan Agent 2

- Tidak mengubah source aplikasi Agent 1.
- Tidak mengubah migration, workflow, database, VPS, deployment, atau PR.
- Tidak menjalankan migration/test pada database produksi.
- Tidak menjalankan test lokal karena auditor hanya melakukan inspeksi GitHub dan pembacaan bukti CI yang ada.
- Tidak membuat atau mengklaim ZIP.
- Semua status yang belum memiliki bukti runtime tetap NOT VERIFIED/BLOCKED, bukan PASS.
