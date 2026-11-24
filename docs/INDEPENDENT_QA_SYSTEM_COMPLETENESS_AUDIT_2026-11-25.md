> **CATATAN PRIORITAS TERBARU (2026-11-25):** Dokumen ini adalah inventaris awal untuk kelengkapan CBT final dan bukan scope kerja aktif. Atas instruksi pengguna, audit Agent 2 saat ini dipersempit ke P0 ZIP SIAP UJI OPERASIONAL TERBATAS. Gunakan `docs/ZIP_LIMITED_P0_AUDIT_2026-11-25.md` sebagai laporan aktif dan checkpoint terbaru sebagai posisi kerja. Fitur nonkritis pada dokumen ini tidak boleh dianggap blocker ZIP terbatas tanpa bukti ketergantungan langsung.\n\n# Audit QA Independen — Inventaris Kelengkapan Sistem CBT Madrasah

- Tanggal audit: 2026-11-25
- Repository: `zane0184/cbt_november`
- Branch audit: `audit/independent-qa-admin-guru-siswa-20261125`
- Commit source yang diperiksa: `a65b4604495e8bcc153bf0e09df12a9efb11543a`
- Baseline branch audit dibuat dari commit tersebut; belum ada perubahan source aplikasi.
- Metode: inspeksi GitHub source, routes, Livewire, controller, services, policies, migrations, tests, pull requests, issues, dan GitHub Actions.
- Batas: tidak menjalankan test lokal/DB; tidak mengubah source aplikasi, migration, CI, database, VPS, deployment, atau PR Agent 1.

## 1. Prinsip audit dan status

Audit mencakup kelengkapan aplikasi akhir, bukan hanya kelayakan ujian terdekat. Prioritas tidak diturunkan hanya karena fitur dapat ditunda dari ZIP uji operasional terbatas.

Status:
- **PASS (source/test evidence)**: implementasi atau test spesifik ditemukan; bukan otomatis bukti E2E operasional.
- **FAIL**: perilaku yang diperiksa terbukti tidak memenuhi kontrak.
- **NOT IMPLEMENTED**: jalur/fitur belum ditemukan atau source secara eksplisit menolaknya.
- **NOT VERIFIED**: belum ada bukti cukup; jangan disimpulkan lulus.
- **BLOCKED**: pemeriksaan tidak dapat dilanjutkan karena dependency/bukti/lingkungan.
- **DEFERRED — KNOWN ISSUE**: sengaja ditunda dan risiko harus tercatat.

Dampak dicatat terpisah:
- **ZIP terbatas**: apakah menghambat ZIP tahap uji operasional terbatas.
- **CBT final**: apakah menghambat kelengkapan aplikasi final.

## 2. Ringkasan source inventory

Pada commit yang diperiksa, tree repository menunjukkan:
- 13 komponen Livewire Admin.
- 10 komponen Livewire Guru.
- 0 komponen Livewire di direktori Student; portal siswa menggunakan controller dan Blade.
- 30 controller HTTP/API.
- 41 service.
- 11 policy.
- 80 test PHP Feature/Unit.
- 71 migration.
- 12 workflow/path terkait GitHub Actions (termasuk arsip/backup workflow).

Inventory ini adalah peta source, bukan klaim bahwa seluruh 80 test dijalankan oleh auditor.

## 3. Temuan terkonfirmasi awal

### ADM-01 — Master tahun pelajaran, semester, dan tingkat kelas belum dikelola melalui Admin
- Modul: Web Admin / Data Akademik.
- Prioritas: **P0**.
- Status: **NOT IMPLEMENTED pada UI Admin yang diperiksa**.
- Commit: `a65b4604495e8bcc153bf0e09df12a9efb11543a`.
- Lokasi:
  - `backend-laravel/app/Livewire/Admin/AcademicManagement.php`
  - `backend-laravel/resources/views/livewire/admin/academic-management.blade.php`
  - `backend-laravel/app/Services/InstallerService.php`
  - `backend-laravel/database/seeders/DatabaseSeeder.php`
  - `backend-laravel/database/migrations/2024_01_01_000002_create_academic_tables.php`
- Bukti source: AcademicManagement menyediakan pembuatan Rombel dan mata pelajaran, dengan pilihan tahun pelajaran dan tingkat kelas yang sudah ada. Tidak ditemukan CRUD tahun pelajaran, semester, atau tingkat kelas pada komponen Admin tersebut. Installer membuat tenant dan akun Admin; DatabaseSeeder memuat tenant sistem, role/permission, dan registry kurikulum, tetapi tidak menginisialisasi tahun pelajaran/semester/grade level per tenant.
- Reproduksi statis: instalasi baru → masuk Admin → buka Data Akademik → periksa seluruh aksi dan opsi; tidak ditemukan jalur membuat tahun pelajaran/semester/tingkat kelas sebelum membuat Rombel.
- Aktual: prasyarat Rombel harus tersedia lebih dahulu; tidak ada alur UI Admin yang ditemukan untuk menyediakannya.
- Harapan: Admin dapat mengelola tahun pelajaran, semester aktif, tingkat kelas MI 1–6, lalu Rombel.
- Dampak: instalasi baru berpotensi tidak dapat menyelesaikan setup akademik mandiri.
- Bukti test: `AdminUiVerificationTest::admin_academic_management_can_create_rombel_and_subject` membuat `academicYear`, `gradeLevel`, `semester` melalui fixture test; ini tidak membuktikan Admin dapat membuatnya melalui UI.
- Rekomendasi: implementasikan master academic year/semester/grade level beserta validasi tenant, status aktif, histori, dan audit log; tambahkan feature test alur instalasi baru dari tenant kosong.
- ZIP terbatas: **YA**, bila tidak ada prosedur setup akademik resmi lain yang dapat digunakan.
- CBT final: **YA**.

### CUR-01 — Registry CP resmi belum diimpor/terverifikasi
- Modul: Kurikulum / Guru / Bank Soal / Asesmen.
- Prioritas: **P0**.
- Status: **BLOCKED oleh sumber data resmi yang belum tersedia/terverifikasi pada source yang diperiksa**; bukan izin membuat CP fiktif.
- Commit: sama dengan baseline audit.
- Lokasi:
  - `backend-laravel/database/seeders/CurriculumRegistryBootstrapSeeder.php`
  - `backend-laravel/database/seeders/DatabaseSeeder.php`
  - `backend-laravel/app/Services/TeacherPortal/TeacherCurriculumContextService.php`
  - `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php`
  - `backend-laravel/app/Livewire/Teacher/QuestionBank.php`
- Bukti source: bootstrap registry menandai `cp_content_imported = false`; struktur fase/kelas dan dimensi konteks tersedia, tetapi bukan konten CP resmi. TeacherCurriculumContextService memerlukan sumber CP terverifikasi/checksum. TeacherLearningOutcomeService menolak pembuatan TP jika CP induk tidak terverifikasi. Bank Soal mensyaratkan pemetaan CP dan TP/ATP sebelum publikasi.
- Reproduksi statis: jalankan setup registry tenant baru tanpa paket CP resmi terverifikasi → buka konteks pembelajaran Guru → upayakan buat TP dan publikasi soal.
- Aktual: kontrak data menahan TP/publikasi ketika CP belum terverifikasi.
- Harapan: CP resmi per kurikulum/fase/kelas/mapel terverifikasi; Guru dapat menyusun TP/ATP yang terkait, dan histori versi dipertahankan.
- Dampak: rantai kurikulum → TP/ATP → bank soal → asesmen → ujian belum dapat dibuktikan end-to-end.
- Bukti test: test kontrak kurikulum yang menggunakan fixture CP sintetis membuktikan kontrak kode, bukan ketersediaan paket CP resmi produksi.
- Rekomendasi: sediakan proses import artefak resmi dengan sumber, versi, checksum, provenance, dan status verifikasi; jangan mengarang konten CP.
- ZIP terbatas: **YA**, bila uji operasional mensyaratkan Guru membuat materi/soal kurikulum dari CP.
- CBT final: **YA**.

### ADM-02 — User Management belum memiliki fungsi edit profil/akun
- Modul: Web Admin / User Management.
- Prioritas: **P1**.
- Status: **NOT IMPLEMENTED pada komponen yang diperiksa**.
- Lokasi: `backend-laravel/app/Livewire/Admin/UserManagement.php`, `backend-laravel/app/Services/UserAccountService.php`.
- Bukti source: tersedia create Guru/Siswa, aktivasi, nonaktif (mencabut token dan sesi), dan reset password dengan audit log. Tidak ditemukan state/modal/action edit identitas, username/email, atau profil Guru/Siswa pada komponen ini.
- Klarifikasi audit: aktivasi/nonaktif dan reset password **SUDAH ADA**; jangan dicatat sebagai fitur yang hilang.
- Dampak: koreksi data pengguna setelah dibuat memerlukan prosedur lain/manual yang belum diverifikasi.
- Rekomendasi: tentukan field yang dapat diedit, aturan perubahan identifier unik, validasi role/tenant, audit before/after, dan perlindungan saat siswa sedang memiliki attempt aktif.
- ZIP terbatas: **TIDAK SELALU**, bergantung apakah data awal sudah benar; tetap perlu dicatat.
- CBT final: **YA**.

### ADM-03 — Import Guru belum ditemukan sebagai alur Admin
- Modul: Web Admin / Data Guru.
- Prioritas: **P1**.
- Status: **NOT IMPLEMENTED pada UI/source Admin yang diinventarisasi**.
- Lokasi diperiksa: `app/Livewire/Admin/UserManagement.php`, `app/Livewire/Admin/StudentImport.php`, service import yang ada.
- Bukti source: Guru dapat dibuat satu per satu dari UserManagement dengan profil employment/NIP/NIK/NUPTK; alur import yang ditemukan adalah StudentImport. Belum ditemukan komponen/service import Guru.
- Harapan: import massal Guru dengan validasi identitas, status kepegawaian, duplikasi per tenant, preview, transaksi, idempotensi, dan audit log.
- Rekomendasi: rancang import Guru terpisah atau perluas import secara aman; jangan mencampur role/profile secara tidak tervalidasi.
- ZIP terbatas: **TIDAK**, bila input manual cukup untuk jumlah kecil; **YA** bila onboarding massal merupakan prasyarat operasional.
- CBT final: **YA**.

### ADM-04 — Monitoring Admin belum terbukti sebagai halaman monitoring operasional lengkap
- Modul: Web Admin / Monitoring.
- Prioritas: **P1**.
- Status: **NOT VERIFIED**.
- Lokasi: `app/Http/Controllers/Api/V1/MonitoringController.php`, komponen Livewire Admin yang tersedia.
- Bukti source: terdapat API MonitoringController, tetapi inventory 13 komponen Livewire Admin tidak menunjukkan halaman Admin Monitoring tersendiri. Perlu ditelusuri apakah API ini ditujukan untuk capability Guru/Proktor atau Admin dan apakah UI Admin memakainya.
- Harapan: Admin dapat memantau kesehatan instalasi, aktivitas, proses ujian, antrian/scheduler, kapasitas, dan kejadian keamanan sesuai kewenangan tenant.
- Rekomendasi: petakan setiap endpoint monitoring ke role dan UI; jangan menganggap keberadaan controller sama dengan fitur Admin.
- ZIP terbatas: **TIDAK/tergantung skenario**.
- CBT final: **YA**.

### GURU-01 — Import DOCX soal belum terbukti sebagai jalur Web Guru
- Modul: Web Guru / Bank Soal / Import.
- Prioritas: **P1**.
- Status: **NOT VERIFIED; indikasi NOT IMPLEMENTED pada jalur UI aktif**.
- Lokasi:
  - `backend-laravel/app/Services/ExcelQuestionImporter.php`
  - `backend-laravel/app/Services/QuestionImportService.php`
  - `backend-laravel/app/Livewire/Teacher/TeacherPortalComponent.php`
  - `backend-laravel/app/Livewire/Teacher/TeacherMaterialManagement.php`
- Bukti source: service bernama ExcelQuestionImporter menerima array baris yang sudah diparsing; bukan parser DOCX. Belum ditemukan jalur DOCX end-to-end (upload → parse → preview → mapping → commit) pada komponen Guru yang diperiksa.
- Reproduksi statis: telusuri route/view/Livewire Guru dan binding importer; belum ada pemanggilan DOCX parser yang dapat ditelusuri.
- Harapan: DOCX import aman, preview, validasi seluruh tipe soal, opsi/kunci, media, stimulus, pemetaan kurikulum, dan transaksi/idempotensi.
- Rekomendasi: telusuri seluruh tree termasuk package/command dan PR terkait sebelum memutuskan final NOT IMPLEMENTED; jika memang belum ada, buat backlog implementasi.
- ZIP terbatas: **TIDAK**, jika soal dapat dibuat manual.
- CBT final: **YA** bila fitur import DOCX termasuk keputusan produk.

### GURU-02 — Capability Proktor belum berfungsi sebagai penugasan operasional
- Modul: Web Guru / Proktor.
- Prioritas: **P1**.
- Status: **NOT IMPLEMENTED / DEFERRED — KNOWN ISSUE** (alasan penundaan harus diverifikasi pada catatan keputusan).
- Lokasi:
  - `backend-laravel/app/Services/ExamProctorAssignmentService.php`
  - `backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php`
  - `backend-laravel/app/Http/Controllers/Api/V1/MonitoringController.php`
  - `backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php`
- Bukti source: service assignment secara eksplisit melempar LogicException untuk operasi penugasan; UI Admin menyatakan kontrol penugasan belum diaktifkan. Policy/monitoring belum membuktikan akses Proktor berbasis assignment sementara.
- Harapan: Proktor tetap capability pada Guru, assignment terbatas ujian dan masa berlaku, dapat monitoring sesuai penugasan, seluruh aksi tercatat; bukan role/portal baru.
- Rekomendasi: implementasikan assignment + authorization pada setiap endpoint monitoring/aksi proktor; uji assignment expired, lintas tenant, revoked, dan akses tanpa assignment.
- ZIP terbatas: **TIDAK**, jika uji tahap pertama tidak memakai Proktor.
- CBT final: **YA**.

### CBT-01 — End-to-end Web CBT belum memiliki bukti runtime independen
- Modul: Siswa / ujian / autosave / submit / hasil.
- Prioritas: **P0 untuk klaim kesiapan operasional**.
- Status: **NOT VERIFIED**.
- Lokasi: `app/Http/Controllers/StudentExamController.php`, `resources/views/student/exams/show.blade.php`, API ExamAttempt/ExamAnswer/Exam/Scoring.
- Bukti source: halaman siswa mengimplementasikan IndexedDB draft, server clock monotonic, autosave, heartbeat, fencing, submit barrier, dan recovery status. Ini bukti implementasi source, bukan bukti integrasi runtime multi-browser/perangkat/jaringan.
- Harapan: pengujian E2E pada DB disposable dan browser/device nyata membuktikan start, answer, reconnect, duplicate submit, race, expiry, lease/fencing, scoring, serta result visibility.
- Rekomendasi: siapkan suite E2E terisolasi; jangan klaim offline/recovery/device fencing siap produksi sebelum uji integrasi multi-device dan concurrency.
- ZIP terbatas: **YA**, sampai skenario minimum ujian dan recovery kritis lulus.
- CBT final: **YA**.

## 4. Koreksi terhadap catatan lisan awal

1. Catatan awal yang menyebut User Management tidak memiliki aktivasi/nonaktif/reset password dikoreksi: source `UserManagement.php` dan `UserAccountService.php` menunjukkan ketiganya tersedia dengan audit/revocation. Kekurangan yang masih terkonfirmasi adalah edit profil/akun.
2. Import Guru belum ditemukan; pembuatan Guru manual sudah tersedia.
3. Monitoring Admin masih NOT VERIFIED, bukan FAIL, sampai seluruh route/API/view/policy dan tujuan controller dipetakan.
4. Portal Siswa menggunakan controller + Blade, bukan Livewire Student component; ketiadaan folder Livewire Student bukan bukti fitur siswa tidak ada.
5. Implementasi IndexedDB/fencing di Blade adalah bukti source; tidak sama dengan PASS integrasi runtime.

## 5. Matriks cakupan keseluruhan — status audit

| Domain | Area | Status saat snapshot | ZIP terbatas | CBT final |
|---|---|---|---|---|
| Admin | Login/logout, dashboard, profil madrasah | NOT VERIFIED penuh; sebagian source tersedia | Perlu uji | YA |
| Admin | Tahun pelajaran, semester, tingkat kelas | NOT IMPLEMENTED pada UI Admin | YA | YA |
| Admin | Rombel, mapel, penugasan Guru | Source/test tersedia; E2E belum diverifikasi | Perlu uji | YA |
| Admin | User create, activate, deactivate, reset password | PASS pada level source; E2E/authorization lanjutan perlu uji | Perlu uji | YA |
| Admin | Edit profil/akun | NOT IMPLEMENTED pada komponen diperiksa | Kondisional | YA |
| Admin | Import Siswa | Source + CI acceptance tersedia pada commit Agent 1; audit E2E independen belum dijalankan | Perlu uji | YA |
| Admin | Import Guru | NOT IMPLEMENTED pada UI yang ditemukan | Kondisional | YA |
| Admin | Monitoring operasional | NOT VERIFIED | Kondisional | YA |
| Admin | Audit log dan pengaturan | NOT VERIFIED cakupan penuh | Perlu uji | YA |
| Guru | Profil dan assignment | Source tersedia; E2E belum diverifikasi | Perlu uji | YA |
| Guru | CP resmi → TP/ATP | BLOCKED data CP resmi terverifikasi | YA | YA |
| Guru | Materi/pembelajaran, Panca Cinta, PM, dimensi lulusan | Kontrak/source tersedia; validasi semantik dan histori belum tuntas | Perlu uji | YA |
| Guru | Bank soal dan tipe soal | Source/test tersedia; seluruh tipe dan media perlu matriks test | Perlu uji | YA |
| Guru | DOCX import | NOT VERIFIED; indikasi jalur aktif belum ditemukan | Tidak selalu | YA bila termasuk spesifikasi |
| Guru | Asesmen/Exam Builder/publish/token | Source/test tersedia; E2E lintas role belum tuntas | YA | YA |
| Guru | Hasil, penilaian, laporan, analitik | Source/test tersedia; semua kontrak role/tenant perlu diuji | Perlu uji | YA |
| Guru | Proktor capability | NOT IMPLEMENTED/DEFERRED | Tidak selalu | YA |
| Siswa | Login/profil/rombel/daftar ujian | Source/test tersedia; E2E belum tuntas | YA | YA |
| Siswa | Token/jadwal/start | Source/test tersedia; boundary cases perlu diuji runtime | YA | YA |
| Siswa | Semua tipe soal/navigasi/autosave | Source tersedia; matriks tipe dan browser runtime belum tuntas | YA | YA |
| Siswa | IndexedDB recovery/submit/idempotency | Source tersedia; integrasi NOT VERIFIED | YA | YA |
| Backend | RBAC/policy/tenant isolation/IDOR | Banyak policy/test tersedia; audit endpoint-by-endpoint masih berlangsung | YA | YA |
| Backend | Installer dan instalasi mandiri | Source dan test installer tersedia; clean-install MySQL E2E perlu verifikasi | YA | YA |
| Backend | DB relasi/migration | 71 migration terinventarisasi; audit constraint lengkap masih berlangsung | YA | YA |
| Backend | Storage/media authorization/cleanup | Source/test tersedia; batas tenant dan recovery perlu audit lanjutan | Perlu uji | YA |
| Backend | API/versioning/license/compatibility | Source tersedia; kontrak dan upgrade path belum diverifikasi menyeluruh | Perlu uji | YA |
| Backend | Queue/scheduler/backup/recovery | NOT VERIFIED | Perlu uji | YA |
| Backend | Performance/load/concurrency | Test concurrency ada; hasil runtime independen belum ada | YA untuk beban dasar | YA |
| Delivery | Deployment readiness | NOT VERIFIED | Perlu uji | YA |
| Delivery | ZIP distribution readiness | Belum diaudit kelengkapan packaging | YA | YA |

## 6. Test dan CI evidence yang benar-benar tersedia

GitHub Actions pada commit Agent 1 `a65b4604495e8bcc153bf0e09df12a9efb11543a`:
- Workflow: DOCX Import Parser CI, Run ID `45482223886`.
- PHP 8.3 / MySQL 8.0 InnoDB: SUCCESS.
- PHP 8.4 / MySQL 8.0 InnoDB: SUCCESS.
- Pada job log: acceptance Student Import 20 passed (77 assertions), benchmark cleanup denial-path 1 passed (20 assertions), complete suite 619 passed, 1 skipped (3110 assertions).
- CI ini tidak membuktikan browser E2E Admin/Guru/Siswa, multi-device, deployment, atau seluruh spesifikasi produk.

Auditor belum menjalankan PHPUnit/Playwright/browser atau database lokal. Tidak ada test lokal yang diklaim PASS.

## 7. Batas dan langkah audit berikutnya

Audit ini adalah baseline inventaris dan temuan awal, bukan sertifikasi kelengkapan final. Tahap berikutnya wajib menyelesaikan:
1. Pemetaan seluruh route web/API ke middleware, policy, permission, tenant, dan aktor.
2. Audit komponen Admin dan seluruh aksi Livewire, termasuk profil, branding, academic masters, user lifecycle, import, monitoring, audit log.
3. Audit Guru: profil, assignment, seluruh jenis soal, media/stimulus, DOCX, assessment, publish, result, analytics.
4. Audit Siswa: setiap jenis soal, token/schedule, autosave, refresh/reconnect, submit barrier, result visibility.
5. Audit 71 migration, model relationship/foreign key/unique constraints, installer clean-install, tenant DB/storage isolation.
6. Audit jobs/scheduler/commands, backup/restore, versioning/license, error handling, performance.
7. Susun test plan disposable DB dan E2E. Jangan mengubah source Agent 1.
8. Perbarui setiap temuan dengan bukti reproduksi/test aktual dan dua dampak delivery.

## 8. Deklarasi independensi

- Tidak ada source aplikasi yang diubah.
- Tidak ada migration atau workflow CI yang diubah.
- Tidak ada database produksi/VPS yang diakses.
- Tidak ada PR di-merge atau diubah.
- Branch audit terpisah dari branch Agent 1.
