# REPORT P1 — AUDIT DAN PROGRES TARGET 2–7
Tanggal: 24 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/p1-seven-targets-20261124`
PR: #23 (Draft)
Head yang dipakai workflow saat laporan: `535a3af9c93ccb3c144cf9e9ed8c160d7146d525`
Base P0: `1c33e5d422175f9bd85d206230271a9fe5a4eecd`

## GARIS BATAS P0
- Baseline P0 exact SHA `1c33e5d422175f9bd85d206230271a9fe5a4eecd` memiliki bukti CI #45218279043: 518 passed, 0 failed, 2.189 assertions (PHP 8.3 + disposable MySQL 8.0.46/InnoDB).
- P0 tidak diedit. Seluruh pekerjaan P1 berada pada branch turunan dan PR #23.
- CI P1 yang sedang berjalan adalah validasi integrasi lanjutan, bukan perubahan atau pengulangan patch P0.

## 2. AUDIT WEB ADMIN ADM-01–23

### Source yang diperiksa
- `routes/web.php`, `routes/teacher.php`, `resources/views/layouts/app.blade.php`.
- Livewire: Dashboard, UserManagement, StudentRombelManagement, AcademicManagement, TeacherSubjectAssignmentManagement, CurriculumManagement, CurriculumRegistryManagement, ProctorAssignmentManagement.
- Model/migration: User, TeacherProfile, StudentProfile, AcademicYear, Semester, GradeLevel, Rombel, Subject, TeacherSubjectAssignment, TenantCurriculumRegistration, AuditLog, ExamProctorAssignment.
- Test: `tests/Feature/AdminUiVerificationTest.php`, `ProctorAssignmentSecurityTest.php`, `WebAdminAuthTest.php`.
- Acuan: `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md` dan matriks ADM-01–23.

### Temuan per kelompok
| ID | Status audit source | Temuan / gap utama |
|---|---|---|
| ADM-01 | PARTIAL | Dashboard memakai query tenant, tetapi belum membuktikan seluruh statistik aktif dan filter tahun pelajaran aktif sesuai spesifikasi; perlu tes nilai DB dan empty state. |
| ADM-02 | NOT IMPLEMENTED/NOT VERIFIED | Route/UI CRUD profil madrasah dan kop identitas belum ditemukan pada route Admin aktif. |
| ADM-03 | NOT IMPLEMENTED/NOT VERIFIED | Alur upload/preview/ganti/hapus logo/favicon belum ditemukan. |
| ADM-04 | NOT IMPLEMENTED/NOT VERIFIED | Branding per instalasi/login/footer belum ditemukan end-to-end. |
| ADM-05 | NOT IMPLEMENTED/NOT VERIFIED | Editor kop dokumen, preview dan integrasi ke dokumen cetak belum ditemukan. |
| ADM-06 | PARTIAL | UserManagement hanya membuat/list/filter Guru/Siswa; belum membuktikan edit, detail, aktif/nonaktif, histori, reset password, revoke session. |
| ADM-07 | PARTIAL | Penempatan Siswa ke Rombel ada dan diaudit; transfer/kelulusan/arsip/naik kelas/mutasi dan histori lifecycle belum lengkap. |
| ADM-08 | PARTIAL | Pembuatan akun dan hash password ada; reset password, revoke session, login history dan first-login policy belum dibuktikan. |
| ADM-09 | NOT IMPLEMENTED/NOT VERIFIED | Template/import Guru dengan preview, row errors, idempotency dan transaksi belum ditemukan. |
| ADM-10 | NOT IMPLEMENTED/NOT VERIFIED | Template/import Siswa dengan validasi NIS/NISN/rombel/tahun dan preview belum ditemukan. |
| ADM-11 | NOT IMPLEMENTED/NOT VERIFIED | Export Guru terfilter/audited dan tanpa credential belum ditemukan. |
| ADM-12 | NOT IMPLEMENTED/NOT VERIFIED | Export Siswa terfilter/audited dan tanpa credential belum ditemukan. |
| ADM-13 | PARTIAL | Model AcademicYear/Semester ada, tetapi layar lifecycle tahun pelajaran/semester dan invariant satu tahun aktif belum dibuktikan. |
| ADM-14 | PARTIAL | GradeLevel dibaca pada layar akademik; CRUD/constraint MI 1–6 dan pengujian belum terbukti. |
| ADM-15 | PARTIAL | Pembuatan Rombel dan penambahan siswa ada; transfer, keluar, histori, dan pencegahan konflik dua penempatan aktif belum dibuktikan penuh. |
| ADM-16 | PARTIAL | Pembuatan/list Subject ada; edit/arsip dan mapping kurikulum/version lengkap belum dibuktikan. |
| ADM-17 | PARTIAL | Service penugasan Guru ada dengan konteks tahun/semester/rombel/mapel/registrasi; histori/revoke dan seluruh TeacherAccess regression perlu acceptance test lengkap. |
| ADM-18 | PARTIAL | Registry versi dan registrasi madrasah ada; sumber resmi yang belum checksum verified harus tetap diblokir. |
| ADM-19 | PARTIAL | CP read-only dan TP/ATP Guru-scoped telah memiliki fondasi; jalur legacy, histori dan integrasi seluruh portal perlu E2E. |
| ADM-20 | BLOCKED | ProctorAssignmentService::assign dan UI assign fail-closed karena marker kanonis ujian pusat belum ditetapkan owner. Jangan tafsirkan EXTERNAL/PUBLISHED sebagai eligibility. |
| ADM-21 | NOT IMPLEMENTED/NOT VERIFIED | Laporan administratif Admin dan output cetak dengan kop belum ditemukan. |
| ADM-22 | PARTIAL | AuditService/AuditLog ada dan beberapa mutasi mencatat event; halaman audit serta cakupan login/denied/import/export belum dibuktikan. |
| ADM-23 | NOT IMPLEMENTED/NOT VERIFIED | Pengaturan umum/branding/tema/akun Admin dan workflow backup/restore terintegrasi belum ditemukan. |

### Batas akses yang diperiksa
- Route `/guru/bank-soal` dan `/guru/asesmen` memakai middleware `EnsureTeacherPortalAccess`; komponen QuestionBank/ExamManagement berada pada namespace legacy Admin tetapi route aktifnya di portal Guru.
- Route Admin `/admin/questions` dan `/admin/exams` tidak terdaftar; regression test AdminUiVerificationTest menguji 404 untuk keduanya.
- Tidak ditemukan route Admin yang membuka authoring soal/ujian.
- Dashboard dan layar administrasi memakai role Admin tunggal pada guard yang diperiksa; tetap perlu pengujian seluruh endpoint/Livewire action, bukan hanya route.

### Perbaikan ADM-06 yang telah di-commit
- Spesifikasi menyatakan NIP/NUPTK opsional bila Guru tidak memilikinya; NIK hanya bila ada dasar dan perlindungan.
- UserManagement sebelumnya mewajibkan Guru memiliki NIP atau NIK. Guard tersebut dihapus.
- Regression test `admin_can_create_teacher_without_optional_nip_or_nik` ditambahkan.
- Backup byte-exact sebelum perubahan:
  - `backend-laravel/backup_before_patch/P1_ADM06_OPTIONAL_TEACHER_IDENTIFIERS_20261124/UserManagement.php.before`
  - `backend-laravel/backup_before_patch/P1_ADM06_OPTIONAL_TEACHER_IDENTIFIERS_20261124/AdminUiVerificationTest.php.before`
- Verifikasi konten backup terhadap source sebelum patch: identik (true).
- Test belum dinyatakan PASS sampai CI P1 selesai.

## 3. WEB GURU

### Source yang diperiksa
- `routes/teacher.php`, layout, TeacherDashboard, TeacherAssignmentsPage, TeacherLearning, TeacherMaterialManagement, TeacherProfilePage.
- Komponen QuestionBank dan ExamManagement yang dipasang di route Guru.
- TeacherAccess/TeacherPortalAccess, question curriculum mapping, assessment mapping, import tests.

### Temuan
- Portal Guru aktif untuk dashboard, penugasan, pembelajaran/kurikulum, materi, profil, bank soal, asesmen.
- QuestionBank dan ExamManagement memiliki guard Guru dan assignment; penggunaan namespace `App\\Livewire\\Admin` adalah technical debt, bukan portal Admin.
- Menu/route hasil, analytics, monitoring, dan capability Proktor di portal Guru belum lengkap sebagai satu alur.
- Import DOCX merupakan jalur utama sesuai keputusan owner; jangan hidupkan kembali XLSX question import. DOCX belum dibuktikan E2E dengan backend staging.
- SHORT_ANSWER tetap fail-closed. ESSAY memerlukan penilaian manual.
- Belum ada bukti E2E seluruh lifecycle Guru pada browser nyata dan DB MySQL 8.

Status target 3: PARTIAL / NOT ACCEPTED.

## 4. WEB SISWA

### Source yang diperiksa
- `StudentExamController`, `ExamAttemptController`, `AttemptAuthSessionBinding`, `AttemptDeviceLeaseService`, `ExamAttemptPolicy`, Student portal views dan Feature tests.
- PR #13/branch runner diperiksa secara historis; runner client belum terintegrasi penuh ke branch P1 ini.

### Temuan
- Server memeriksa exact role SISWA, tenant, ExamParticipant, eligibility window, schedule dan attempt limit.
- API start mengikat Attempt ke auth session dan device binding, menggunakan transaction/row locks, dan menolak sesi/perangkat/fence yang tidak cocok.
- Attempt response DTO tidak menserialisasi model Eloquent mentah.
- Snapshot soal menyimpan metadata privat termasuk is_correct dan explanation; endpoint payload soal harus diuji khusus untuk memastikan field privat tidak pernah keluar ke Siswa.
- Student browser runner durable queue/IndexedDB dan recovery dari PR #13 belum dibuktikan real Laravel/MySQL browser E2E pada branch P1.
- Belum ada bukti UAT lintas browser, offline/reconnect, tab/device concurrency, crash recovery, media authorization dan submit race.

Status target 4: PARTIAL / NOT ACCEPTED. Device lease/fencing tidak dinyatakan siap produksi.

## 5. KURIKULUM MERDEKA + KBC

### Source yang diperiksa
- `TeacherCurriculumContextService`
- `QuestionVersionCurriculumMappingService`
- `AssessmentCurriculumMappingService`
- `ExamSnapshotService`
- Registry/source/version/registration/outcome/mapping models dan tests.

### Temuan
- CP resmi harus official, non-editable dan checksum cocok; TP/ATP harus milik assignment, status PUBLISHED dan current.
- Snapshot asesmen membawa fungsi/scope/instrumen/mode, assignment, versi kurikulum, CP, TP/ATP, materi dan topik.
- Snapshot soal membawa konteks tahun/semester, registrasi/version, CP, TP/ATP, framework mapping dan materi/topik.
- Snapshot mewajibkan framework roles PANCA_CINTA, PM_PRINCIPLE dan DIMENSI_PROFIL_LULUSAN; source metadata harus diverifikasi.
- Konten CP/struktur resmi yang belum memiliki artefak/checksum tidak boleh dibuat sintetis atau ditandai verified.
- Acceptance belum lengkap: perlu tes immutable setelah publish/Attempt, perubahan versi baru tanpa mengubah histori, report provenance, payload siswa dan seluruh jalur API legacy.

Status target 5: IMPLEMENTASI FONDASI ADA / E2E NOT VERIFIED.

## 6. INSTALASI DAN DEPLOYMENT VPS

### Source yang diperiksa
- `.github/workflows/build-web-deployment.yml`
- `docs/PANDUAN_INSTALASI_WEB_CBT_MANDIRI.md`
- checkpoint/laporan P0.96A dan P0.96G.

### Temuan
- Installer mendukung database kosong MySQL 8/InnoDB, bootstrap token, wizard migration, pembuatan Admin awal dan lisensi.
- Panduan aaPanel menggunakan File Manager/upload/extract, document root `backend-laravel/public`, .env per instalasi, database khusus madrasah.
- Belum ada bukti staging VPS aktual, backup/restore teruji, browser UAT, load/security test atau persetujuan deployment.
- Tidak meminta atau menyimpan kredensial SSH/aaPanel dalam repo/chat.

Status target 6: STAGING NOT RUN / PRODUCTION NO-GO.

## 7. ZIP DISTRIBUSI

### Build P1
- Workflow: Build Standalone Web CBT Deployment ZIP.
- Run: [#45230720738](https://github.com/zane0184/cbt_november/actions/runs/45230720738)
- Exact source SHA: `535a3af9c93ccb3c144cf9e9ed8c160d7146d525`.
- Status workflow: SUCCESS.
- Artifact: `cbt-madrasah-web-deployment-535a3af9c93ccb3c144cf9e9ed8c160d7146d525`
- Artifact ID: `14847312148`
- Ukuran artifact container: 19,762,548 bytes.
- Digest artifact container: `sha256:2d1b8aac1948948a1b35404f13ec799da4316846c2db420fc3d4ccd483506f9a`
- Masa simpan artifact: sampai 23 Desember 2026.
- Job berhasil melewati Composer validate/install --no-dev, platform check, assembly, pemeriksaan forbidden paths, ZIP integrity dan upload artifact.
- Ini kandidat staging/build, bukan ZIP final yang disetujui distribusi. Belum menggantikan pemeriksaan ekstraksi dan acceptance seluruh portal.

Status target 7: BUILD CANDIDATE PASS / FINAL DISTRIBUTION NOT ACCEPTED.

## PENGUJIAN P1
- Tenant Backend CI run [#45230720660](https://github.com/zane0184/cbt_november/actions/runs/45230720660) pada exact SHA `535a3af9c93ccb3c144cf9e9ed8c160d7146d525`: status terakhir ketika laporan dibuat masih IN_PROGRESS pada Composer install. Belum ada hasil PHPUnit untuk patch P1.
- Deployment ZIP run #45230720738: SUCCESS seperti bukti di atas.
- VPS staging, browser E2E, backup/restore, security/load dan UAT: NOT RUN.

## KEPUTUSAN DAN LANGKAH SELANJUTNYA
1. Tunggu CI #45230720660 selesai; jika gagal, diagnosis hanya kegagalan baru pada exact SHA, jangan mengulang P0 patch.
2. Lanjutkan perbaikan Admin dengan urutan ADM-06/07/08/13/15/16/17 lalu ADM-02–05/09–12/21–23; semua perubahan backup byte-exact.
3. Pertahankan ADM-20 fail-closed sampai owner menyetujui marker/aktor eligibility ujian pusat. Room bukan admission gate.
4. Lengkapi Web Guru, integrasi DOCX dan E2E; selesaikan Student runner-to-Laravel integration, redaction dan recovery E2E.
5. Tambahkan provenance/report acceptance untuk Kurmer+KBC.
6. Lakukan staging VPS hanya dengan database kosong/terisolasi dan approval; tidak menyentuh database produksi.
7. Verifikasi isi ZIP, ekstraksi bersih, manifest, checksum, dependencies, struktur dan secret exclusion setelah seluruh fitur diterima.
8. PR #23 tetap Draft; tidak merge, tidak production migrate/deploy dan tidak menyatakan siap distribusi.
