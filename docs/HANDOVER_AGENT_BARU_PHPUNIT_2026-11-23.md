# HANDOVER AGENT BARU — CBT MADRASAH / PEMULIHAN FULL PHPUNIT

Tanggal: 23 November 2026 (WIB)  
Repository: zane0184/cbt_november  
**Branch kerja aktif:** work/solo-web-cbt-completion-20261123-execution  
**Source SHA terakhir yang benar-benar diuji full PHPUnit:** b31d7a2713383ddcc1282317cdb295d6992d5dce  
**HEAD branch saat dokumen ini dibuat:** 5500b668f180a2d2913b5f830eb3363f8ac5e90a (commit dokumentasi/backup setelah source SHA yang diuji; source code tidak berubah sesudah run tersebut).

> **STATUS: WORK IN PROGRESS — FULL PHPUNIT MASIH FAIL — NOT PRODUCTION READY — NOT READY FOR OPERATIONAL EXAM — NOT READY FOR DISTRIBUTION.**

## 1. Perintah pertama untuk agent baru

1. Baca dokumen ini sampai selesai.
2. Baca docs/REPORT_P0_TRIAGE_FULL_PHPUNIT_2026-11-23.md dan docs/MATRIKS_ADM_01_23_AUDIT_2026-11-23.md (keduanya sudah diberi addendum terbaru).
3. Verifikasi HEAD branch ini dan CI exact SHA terbaru sebelum coding. Source SHA b31d7a2 adalah yang diuji terakhir; commit dokumentasi setelahnya tidak membuktikan kode berubah/lulus.
4. Baca keputusan owner di Issue #20 dan aturan proyek. Jangan ubah keputusan portal/role/instalasi/kurikulum.
5. Jangan gunakan dokumen lama yang menyebut branch work/solo-web-cbt-completion-20261123 sebagai branch aktif. Branch aktif adalah nama yang tercantum di bagian atas dokumen ini.
6. Lanjutkan dari P0 yang tercantum pada §7, jangan mulai ulang audit atau mengulang patch yang sudah ada.

## 2. Keputusan owner yang tetap mengikat

- Fokus sekarang Web CBT: Backend Laravel, Web Admin Madrasah, Web Guru, Web Siswa.
- Portal aktif hanya Admin Madrasah, Guru, Siswa. Role resmi tepat: ADMIN_MADRASAH, GURU, SISWA.
- Tidak ada portal/role Operator, Kamad, Proktor permanen, atau Super Admin lokal. Proktor hanya penugasan sementara pada Guru; assignment tidak memberikan akses room/session/seat/roster/monitoring/Attempt/operasi CBT.
- Setiap madrasah memakai instalasi dan database akademik sendiri. Tidak ada sharing akademik lintas madrasah. Integrasi pusat masa depan hanya lisensi/entitlement/pembayaran.
- Web Super Admin dan Android native ditunda sampai Juni 2027.
- Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + delapan dimensi profil lulusan adalah satu ekosistem, bukan kurikulum/modul/database terpisah. Pertahankan provenance, versi, snapshot dan histori.
- CP induk read-only; Guru membuat TP/ATP sesuai assignment. Guru membuat/menjadwalkan/menerbitkan ujian sendiri tanpa approval Admin.
- Admin mengelola administrasi/master data/sinkronisasi/dukungan teknis/audit; bukan authoring soal/ujian, peserta, token, Attempt, jawaban, monitoring, penilaian atau nilai.
- Waktu server resmi; Attempt/Answer/Score/Device Lease/fencing/idempotency/recovery konsisten. Kamera bukan syarat. Reset/force submit/takeover/perpanjangan waktu wajib otorisasi dan audit.
- DOCX prioritas import soal; XLSX question import tidak diaktifkan. SHORT_ANSWER tetap blocked sampai accepted answer privat + snapshot + scoring E2E terbukti.
- Laravel 13/PHP 8.3/MySQL 8+, Blade/Livewire. Tidak ada migration produksi, deployment, merge atau DB produksi tanpa persetujuan eksplisit.
- Jangan jalankan Gradle via terminal.

## 3. Bukti CI dan progres PHPUnit

Latest full CI: [run #45168433247](https://github.com/zane0184/cbt_november/actions/runs/45168433247), job 137671393026, exact source SHA b31d7a2713383ddcc1282317cdb295d6992d5dce.

- Disposable MySQL 8.0.46.
- Focused Web login/tenant smoke: **27 passed (90 assertions)**.
- Full PHPUnit: **92 failed, 426 passed (1,723 assertions)** — FAIL.
- Baseline [#45156925960](https://github.com/zane0184/cbt_november/actions/runs/45156925960), SHA dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4: **160 failed, 357 passed (1,475 assertions)**.
- Penurunan dari baseline: **68 kegagalan lebih sedikit**. Jumlah test pass bertambah 69. Ini progres nyata, bukan tanda suite selesai.
- CI sebelumnya #45167215168 pada SHA 40d57ad20b8a228d1010eb1a1fc66829bf82c356 juga gagal; jangan gunakan sebagai latest result.
- Tidak ada CI PASS untuk full PHPUnit pada branch ini.

### Kategori terbesar pada log terbaru

| Test class | Fail | Catatan |
|---|---:|---|
| ExamSnapshotTest | 15 | Fixture tidak memiliki assignment Guru aktif yang cocok; canonical helper belum diterapkan pada class ini. |
| Phase7CReopenTest | 12 | 403 tidak sesuai beberapa test; ada satu skenario unauthorized/unauthenticated justru menerima 200. Audit policy/route/fixture. |
| QuestionBankTest | 8 | Test Livewire Admin lama mengharapkan properti authoring soal; Admin tidak boleh diberi kembali kewenangan authoring. |
| Security/AnswerKeyLeakageTest | 7 | TeacherSubjectAssignment tidak ditemukan. |
| RateLimitingAndAbuseProtectionTest | 6 | Banyak request 401; audit token/session/fencing fixture. |
| ExamManagementHardeningTest | 5 | Properti Livewire lama dan satu akses yang semestinya ditolak. |
| ScoringHardeningTest | 5 | State FINALIZED/AUTO_SCORED, permission reopen, recalculate. |
| ExamListContractTest | 4 | Regresi fixture: variabel $assignment dipakai tapi belum didefinisikan di setup. Backup tersedia. |
| ProctorAssignmentSecurityTest | 4 | TenantContext hilang; test mengharapkan exception lama sementara service fail-closed karena eligibility ujian pusat belum kanonis. |
| ResultWorkflowTest | 4 | Livewire snapshot invalid dan scoring_status null. |
| ExamVerificationTest | 3 | Cleanup FK device_leases harus sebelum attempts; dua response 403. |
| TeacherPracticeWorkflowTest | 3 | Properti lama dan model/ID null. |
| AdminUiVerificationTest | 2 | Livewire component contract lama. |
| MonitoringTest | 2 | Heartbeat 401 dan Attempt read 409. |
| Phase7AScoringTest | 2 | 403 vs expected 422/200; audit permission dan validation order. |
| Analytics Cache, ExamBuilder, MonitoringDuplication, ScoringEngine | 1 masing-masing | 403/422; audit role, assignment dan contract. |

Rincian per baris test/error ada di log run #45168433247 dan addendum triage.

## 4. Source changes yang telah dibuat

Semua perubahan berada pada branch aktif. Daftar file lengkap dapat dilihat melalui GitHub compare dari base commit 13bf44047008bdaa1a0a942231b4855d3a858f13 ke source SHA yang diuji. Ringkasan:

### Production code / route / migration

- backend-laravel/routes/api.php: generic legacy resources curricula/phases/grade-levels/elements dibatasi read-only index/show. Jalur write legacy tidak dibuka kembali.
- backend-laravel/app/Services/InstallerService.php: postcondition diselaraskan dengan role aktif tiga portal dan permission boundaries.
- backend-laravel/app/Http/Controllers/Api/V1/CurriculumAnalyticsController.php: capaian/analitik hasil siswa memerlukan role tunggal GURU + report.view; Admin ditolak.
- backend-laravel/database/migrations/0001_01_01_000000_create_users_table.php: sessions.user_id menggunakan string UUID-compatible.
- backend-laravel/database/migrations/2026_11_23_140001_convert_session_user_id_to_uuid.php: migration upgrade untuk instalasi lama. **Belum diuji di produksi; perlu audit upgrade/rollback dan MySQL 8 sebelum rencana deployment.**

### Test fixture / regression

- Cleanup fixture committed pada AttemptTransactionConcurrencyTest untuk mencegah tenant test bocor.
- InstallerTest, LegacyRoleConsolidationMigrationTest, CurriculumIntegrityTest, CurriculumVersioningTest, RBACEnforcementTest diselaraskan dengan role/route kontrak baru.
- Execution tests mulai memakai token Sanctum tersimpan dan header device/fence; beberapa setup Attempt diarahkan ke endpoint resmi.
- Snapshot fixture pada beberapa test dipisahkan dari service authoring snapshot.
- Helper baru backend-laravel/tests/Concerns/CreatesCanonicalTeacherAssignment.php dibuat untuk assignment/assessment berbasis versi kurikulum dan registrasi kanonis.
- CreatesTeacherExamScope.php diperluas agar mengembalikan assignment.
- Helper tersebut belum menyelesaikan ExamSnapshotTest; 15 test masih gagal.
- ExamListContractTest.php memiliki regresi $assignment undefined (4 test gagal). Jangan lewatkan ini.

## 5. Backup

Backup file existing dibuat di:
- backend-laravel/docs/backup/solo-web-cbt-2026-11-23/
- docs/backup/solo-web-cbt-2026-11-23/
- .github/workflows/backup/

Backup dokumen lama sebelum handover terbaru telah diverifikasi byte-exact. Contoh:
- docs/backup/solo-web-cbt-2026-11-23/REPORT_P0_TRIAGE_FULL_PHPUNIT_2026-11-23.md.before-latest-handover
- docs/backup/solo-web-cbt-2026-11-23/MATRIKS_ADM_01_23_AUDIT_2026-11-23.md.before-latest-handover
- docs/backup/solo-web-cbt-2026-11-23/CHECKPOINT_SOLO_WEB_CBT_2026-11-23.md.before-phpunit-agent-handover
- docs/backup/solo-web-cbt-2026-11-23/REPORT_HANDOVER_AGENT_PENGGANTI_2026-11-23.md.before-phpunit-agent-handover
- docs/backup/solo-web-cbt-2026-11-23/PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md.before-phpunit-agent-handover
- docs/backup/solo-web-cbt-2026-11-23/AGENT_HANDOVER_MASTER_2026-11-23.md.before-phpunit-agent-handover

Setiap backup dokumen dibuat dari versi tepat sebelum update dan diverifikasi kontennya identik. Backup source per file juga berada di folder backend backup tersebut. Baca nama backup dan commit history untuk melihat versi asal masing-masing.

## 6. Laporan/checkpoint yang sudah diperbarui

- docs/REPORT_P0_TRIAGE_FULL_PHPUNIT_2026-11-23.md — baseline, latest 92 failures, kelompok kegagalan, patch dan prioritas.
- docs/MATRIKS_ADM_01_23_AUDIT_2026-11-23.md — 23 requirement tetap NOT VERIFIED; menambah temuan parsial dan prioritas.
- docs/CHECKPOINT_SOLO_WEB_CBT_2026-11-23.md — memuat checkpoint lama lintas tahap; gunakan addendum terbaru di bawah.
- docs/REPORT_HANDOVER_AGENT_PENGGANTI_2026-11-23.md — memuat handover lama; gunakan dokumen ini sebagai status terbaru yang mengesampingkan angka/branch lama.
- docs/PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md — dibaca bersama dokumen ini; branch lama di bagian isi adalah konteks historis.
- Dokumen ini adalah handover ringkas terkini dan menjadi entry point agent baru.

## 7. Next action berurutan — jangan lompat

1. **P0.1 — perbaiki regresi ExamListContractTest.** Backup current file byte-exact, definisikan assignment hasil TeacherSubjectAssignment::create, focused tests empat case.
2. **P0.2 — ExamSnapshotTest.** Audit TeacherAccess::canManageAssessment, schema, tenant context, assignment owner/version/registration/context. Terapkan helper kanonis pada setup dan semua assessment lain yang relevan. Jangan menurunkan policy. Tambahkan regression assertions untuk snapshot provenance/immutability.
3. **P0.3 — cleanup ExamVerificationTest.** Hapus device_leases sebelum exam_attempts sesuai FK RESTRICT; audit start/read 403.
4. **P0.4 — role/permission dan scoring/reopen.** Audit Phase7CReopen, Phase7A scoring, ScoringHardening, Analytics Cache; tentukan test mana yang sudah obsolete terhadap keputusan owner dan mana yang mengungkap source bug. Admin tidak boleh diberi hak hasil siswa/reopen jika tidak ada keputusan owner yang mengizinkan.
5. **P0.5 — Livewire stale contract.** Admin QuestionBank tests tidak boleh memaksa fitur authoring soal pada Admin. Pindahkan authoring test ke komponen Guru aktual atau jadikan test negative access Admin. Pastikan route/component Guru benar-benar diuji.
6. **P0.6 — token/lease/fencing.** Rate limiting, heartbeat, monitoring, submit harus memakai persisted Sanctum identity dan DeviceLease/fence yang nyata. Jangan menerima 401/409 secara membabi buta.
7. **P0.7 — Proctor fail-closed.** Pertahankan blokir sampai eligibility ujian pusat diberi marker kanonis oleh owner. Jangan mengubah LogicException menjadi akses diizinkan.
8. Jalankan focused tests per class, lalu full PHPUnit pada disposable MySQL 8.0.46 exact SHA. Catat jumlah pass/fail/skip dan assertions.
9. Setelah suite PASS baru lanjut integrasi Admin/Guru/Siswa, snapshot laporan historis, CRUD/import/export/branding/audit, fresh install, backup/restore, UAT. Jangan mengklaim produksi.

## 8. Status ADM-01–ADM-23

Semua 23 baris tetap NOT VERIFIED. Temuan parsial:
- Legacy generic academic master write paths sudah dibatasi read-only.
- CurriculumAnalytics dibatasi untuk Guru; Admin tidak boleh melihat hasil siswa.
- Installer role postcondition sudah disejajarkan dengan tiga role.
- CRUD Guru/Siswa, import/export, branding, kop, laporan administratif, audit UI belum dibuktikan lengkap.
- Proctor assignment masih BLOCKED/fail-closed karena eligibility ujian pusat belum kanonis.
- Snapshot laporan historis belum terbukti memakai satu provenance kurikulum kanonis.

## 9. Pengujian / batas yang belum dilakukan

- Full PHPUnit: FAIL.
- Fresh installation/upgrade: belum dibuktikan secara menyeluruh selain disposable installer DB preparation pada CI.
- Browser E2E nyata Admin–Guru–Siswa ke Laravel/MySQL: belum dibuktikan.
- Security/IDOR full matrix: belum selesai.
- MySQL concurrency multi-device/fencing/recovery full suite: belum selesai.
- Backup/restore integrity: belum dilakukan.
- UAT Admin/Guru/Siswa: belum dilakukan.
- VPS deployment, production migration, merge dan ZIP distribusi: tidak dilakukan.

**Kesimpulan:** Agent baru harus melanjutkan dari source SHA dan CI di atas. Fokus pertama adalah membuat 92 failure menjadi nol dengan perbaikan akar masalah, bukan mengubah test agar hijau secara artifisial.
