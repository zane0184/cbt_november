# Laporan Audit dan Remediasi CI Full PHPUnit — 23 November 2026

Status: **WORK IN PROGRESS — NOT PRODUCTION READY**

Branch eksekusi: `work/solo-web-cbt-completion-20261123`
Source HEAD saat laporan: `8d5d45b9ae596534a41a72b145815c1cfc76bbf0`
PR: [#21 — OPEN / DRAFT / UNMERGED](https://github.com/zane0184/cbt_november/pull/21)
Owner decision: [Issue #20](https://github.com/zane0184/cbt_november/issues/20)
CI terbaru untuk HEAD laporan: [Tenant Backend CI #45138531873](https://github.com/zane0184/cbt_november/actions/runs/45138531873)

## AUDIT

### Sumber yang diperiksa
- Repo `zane0184/cbt_november`, branch `main`, branch eksekusi, branch backup, recursive source tree.
- Master spesifikasi Web Admin pada `main`.
- Master spesifikasi Web Guru pada `main`.
- Master spesifikasi Web Siswa pada `work/agent4-web-siswa-audit-20261122`.
- Issue #10, Issue #20 beserta seluruh 32 komentar.
- PR #13, #17, #18, #19, #21.
- GitHub Actions run #45134847069 dan job log lengkap.
- Source aktual dan backup file terkait pada branch kerja.

Tidak dibaca: ZIP baseline dan ChatGPT Library, sesuai arahan owner.

### Keputusan arsitektur yang dikonfirmasi
- Web Admin, Web Guru, Web Siswa dikerjakan sekarang.
- Android native dan Web Super Admin ditunda sampai Juni 2027.
- Satu instalasi mandiri dan satu database lokal untuk satu madrasah; tidak ada pertukaran data akademik antarinstalasi. Pusat mendatang hanya menangani lisensi/entitlement/pembayaran.
- Role portal aktif hanya ADMIN_MADRASAH, GURU, SISWA. OPERATOR/KAMAD/PROKTOR/SUPER_ADMIN tidak menjadi portal aktif.
- Proktor adalah penugasan sementara pada Guru. Penugasan administratif tidak memberikan hak room/session/seat/roster/monitoring/Attempt atau operasi CBT.
- Kurikulum Merdeka dan KBC/Panca Cinta adalah satu kesatuan akademik. Pembelajaran Mendalam dan delapan dimensi profil lulusan ikut dalam konteks yang sama.
- Taxonomy asesmen fleksibel dan versioned; UTS/UAS/UM/SIMULASI TKA bukan enum tertutup. TKA eksternal tidak otomatis menjadi ujian pusat.
- Tidak menggunakan ExamSession/wave/session_number/room sebagai lifecycle admission ujian.
- DOCX menjadi jalur import soal; XLSX import soal tidak dihidupkan kembali. SHORT_ANSWER tetap diblokir hingga accepted answer, snapshot privat, dan scoring terverifikasi.

### Source dan CI
Main HEAD saat diperiksa: `a17b77aa19eb9ba4a01cff44387dc144aca6f740`.
PR #21 source yang diuji sebelumnya: `af36f9edbef7bfa5754afd025a98656079b32616`.
CI [#45134847069](https://github.com/zane0184/cbt_november/actions/runs/45134847069):
- MySQL 8 disposable identity/InnoDB: PASS.
- Composer validate/install: PASS.
- Laravel package discovery: PASS.
- Disposable installer DB: PASS.
- Focused WebAdminAuth/StudentWebLogin: PASS (24 tests, 80 assertions).
- Full PHPUnit: FAILURE.

Job log memperlihatkan 94 failure entries pada full suite; angka tersebut bukan 94 akar masalah independen. Kelompok utama:
1. Dropdown Admin mengurutkan kolom `name` yang tidak tersedia dalam query Role; menyebabkan 500.
2. QuestionBank test mengirim form tanpa assignment/CP/outcome yang kini wajib.
3. Seeder registry mengalami duplikasi identitas node menurut unique key MySQL.
4. Sejumlah legacy Exam/Monitoring/Scoring tests menerima 403 karena policy baru mewajibkan exact role + permission + scope penugasan kanonis.
5. ExamExecution/ExamHardening tests mendapat ModelNotFoundException pada fixture lama.
6. ExamSnapshot tests masih mengandung fixture/kontrak yang belum selaras.
7. API Assessment legacy write routes sengaja dinonaktifkan; request dengan method yang tidak dilayani memberi 405, sedangkan test lama mengharapkan 404.
8. Phase7DReportingTest membuat User tambahan tanpa TenantContext.
9. ProctorAssignmentSecurityTest tidak mengimpor ValidationException pada namespace yang tepat.
10. LegacyRoleConsolidationMigrationTest melempar RuntimeException dan perlu diagnosis tersendiri.
11. ExamManagementHardeningTest menghasilkan ViewException; fixture/kontrak UI masih perlu pemeriksaan.

Policy tidak akan dilemahkan agar fixture lama menerima 200. Guru harus memiliki assignment akademik kanonis untuk assessment/exam yang dikelola.

## DESAIN

- Memperbaiki query dan fixture yang memang salah tanpa menghapus policy exact-role dan assignment.
- Seeder harus idempotent pada unique key MySQL dan fail-closed bila identitas node yang ada memiliki tenant/node type tidak sesuai.
- API Assessment legacy yang tidak mendukung metadata taxonomy/assignment kanonis tetap nonaktif.
- Penugasan Proktor tetap metadata administratif, bukan grant monitoring.
- Remediasi berikutnya dilakukan per akar masalah dengan regression test yang mewakili keputusan owner.
- Tidak mengubah data/migration produksi; verifikasi melalui disposable MySQL CI.

## IMPLEMENTASI

### Backup byte-exact sebelum patch
Branch backup: `backup/solo-web-cbt-ci-repair-20261123`

| Source file | Backup path | Backup blob SHA |
|---|---|---|
| `app/Livewire/Admin/Dashboard.php` | `backend-laravel/docs/backup/solo-web-cbt-ci-repair-2026-11-23/Dashboard.php.before-ci-repair` | `7838eddc2a296d867aa626db07f12aba9ab5493e` |
| `database/seeders/CurriculumRegistryBootstrapSeeder.php` | `backend-laravel/docs/backup/solo-web-cbt-ci-repair-2026-11-23/CurriculumRegistryBootstrapSeeder.php.before-ci-repair` | `1486653edd1d0e883ea060c1e576ac03b4a3f074` |
| `tests/Feature/AdminUiVerificationTest.php` | `backend-laravel/docs/backup/solo-web-cbt-ci-repair-2026-11-23/AdminUiVerificationTest.php.before-ci-repair` | `00d251160c298bf867cd367867f2b45dbfc955a6` |
| `tests/Feature/Phase7DReportingTest.php` | `backend-laravel/docs/backup/solo-web-cbt-ci-repair-2026-11-23/Phase7DReportingTest.php.before-ci-repair` | `f6552df5e60125752583f7af5643f05d22ef67c0` |
| `tests/Feature/ProctorAssignmentSecurityTest.php` | `backend-laravel/docs/backup/solo-web-cbt-ci-repair-2026-11-23/ProctorAssignmentSecurityTest.php.before-ci-repair` | `2c0f3d2090b8a322ca26dde0656dcf497e365282` |
| `tests/Feature/AssessmentApiWriteContractTest.php` | `backend-laravel/docs/backup/solo-web-cbt-ci-repair-2026-11-23/AssessmentApiWriteContractTest.php.before-ci-repair` | `a946be4caea1e9b01425eb250b7e9a95009de7cf` |

Lima backup (Dashboard, Seeder, Proctor test, Reporting test, Assessment API test) diverifikasi ulang dan memiliki blob SHA identik dengan source saat backup. Backup AdminUiVerificationTest juga dibuat; file tersebut belum diubah.

### Patch test pada branch eksekusi
- `tests/Feature/ProctorAssignmentSecurityTest.php`: import `Illuminate\\Validation\\ValidationException` ditambahkan agar test menangkap exception yang memang diharapkan.
  - Commit: `6bf699f5a6897f097bfd8fd0033b724d2caa3311`
- `tests/Feature/Phase7DReportingTest.php`: TenantContext tenant A disiapkan sebelum membuat User tambahan pada test penolakan Admin.
  - Commit: `c5c1f24190f8a86793a943fa66b0bac3971f5662`
- `tests/Feature/AssessmentApiWriteContractTest.php`: ekspektasi diubah menjadi HTTP 405 untuk POST/PUT/PATCH terhadap route yang sengaja tidak dilayani.
  - Commit: `8d5d45b9ae596534a41a72b145815c1cfc76bbf0`

Dashboard dan seeder tidak diubah pada rangkaian patch ini. Pembacaan ulang menunjukkan source branch sudah memiliki perbaikan tersebut: Dashboard mengurutkan recent users dengan `created_at/id`, dan seeder memakai lookup sesuai unique identity MySQL. Patch ganda tidak dibuat.

Tidak ada merge PR, migration database produksi, deploy VPS, atau perubahan database produksi.

## PENGUJIAN

### Run sebelumnya
Run #45134847069 untuk SHA `af36f9edbef7bfa5754afd025a98656079b32616`:
- Focused login/tenant: PASS, 24 tests / 80 assertions.
- Full PHPUnit: FAILURE.
- Browser E2E: belum ada bukti.
- Multi-connection concurrency: belum ada bukti.
- UAT: belum ada.

### Run terbaru
Run #45138531873 pada HEAD laporan `8d5d45b9ae596534a41a72b145815c1cfc76bbf0` telah dibuat setelah patch test terakhir. Pada saat laporan ini ditulis job `laravel-mysql` masih menyiapkan container; belum ada hasil test dan belum ada klaim PASS.

### Belum dibuktikan
- Full PHPUnit PASS pada source HEAD terkini.
- Focused regression tests untuk ketiga patch.
- Seeder MySQL repeat-run test (jalankan seed dua kali dan pastikan count stabil).
- Fresh install dan upgrade legacy pada disposable DB.
- Browser E2E Admin/Guru/Siswa.
- Race/concurrency MySQL multi-connection.
- Backup/restore test.
- UAT madrasah.
- VPS deployment.
- Android compile/perangkat nyata (ditunda Juni 2027).

## CHECKPOINT

Tahap terakhir yang benar-benar selesai:
- Repository, branch, PR, owner decision dan spesifikasi telah diperiksa.
- Full PHPUnit run #45134847069 telah dibaca dari job log.
- Backup byte-exact enam file dibuat pada branch backup; lima backup diverifikasi hash dan isi identik dengan source saat backup.
- Tiga patch test terlokalisasi telah masuk ke branch eksekusi.

Pekerjaan tertunda:
1. Ambil hasil CI #45138531873 dan log lengkap.
2. Diagnosis kelompok 403 Exam/Monitoring/Scoring: perbaiki fixture memakai assignment kanonis, bukan melemahkan policy.
3. Diagnosis legacy migration test dan ExamManagement ViewException.
4. Lengkapi inventaris Admin ADM-01–ADM-23 dengan route/Livewire/model/policy/test.
5. Lengkapi integrasi Guru/Siswa, browser E2E dan recovery tests.
6. Pastikan taxonomy asesmen dan KBC terpadu, bukan enum tertutup.
7. Uji fresh install dan backup/restore pada disposable environment.
8. Siapkan ZIP generik setelah seluruh gate kelengkapan, keamanan dan uji terpenuhi.

PR #21 tetap Draft/Unmerged. Tidak ada perubahan database produksi dan tidak ada deployment. Proyek belum siap produksi maupun distribusi.
