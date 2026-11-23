# CHECKPOINT LANJUTAN WEB CBT — DIPERBARUI 23 NOVEMBER 2026

**Branch eksekusi tunggal:** `work/solo-web-cbt-completion-20261123`
**Source HEAD yang dilaporkan:** `8d5d45b9ae596534a41a72b145815c1cfc76bbf0`
**Laporan CI terbaru:** [REPORT_CI_FULL_PHPUNIT_REMEDIATION_2026-11-23.md](REPORT_CI_FULL_PHPUNIT_REMEDIATION_2026-11-23.md)
**CI terbaru:** [Tenant Backend CI #45138531873](https://github.com/zane0184/cbt_november/actions/runs/45138531873)
**Owner decision:** [Issue #20](https://github.com/zane0184/cbt_november/issues/20)
**PR terpadu:** [PR #21](https://github.com/zane0184/cbt_november/pull/21) — OPEN / DRAFT / UNMERGED.

> WORK IN PROGRESS — NOT PRODUCTION READY. Source branch terus bergerak ketika commit perbaikan test dibuat; SHA harus diperiksa ulang sebelum patch berikutnya. PR tidak di-merge. Tidak ada database produksi atau VPS yang disentuh.

## Keputusan arsitektur aktif
- Portal saat ini hanya Admin Madrasah, Guru, Siswa.
- Android dan Web Super Admin ditunda sampai Juni 2027.
- Instalasi dan database mandiri per madrasah; tidak ada data akademik lintas instalasi. Integrasi pusat mendatang hanya lisensi/entitlement/pembayaran.
- Tidak ada portal Operator/Kamad/Proktor permanen. Proktor adalah Guru yang diberi penugasan sementara.
- Penugasan Proktor hanya metadata administratif dan tidak membuka room/session/seat/roster/monitoring/Attempt/operasi CBT.
- Kurikulum Merdeka + KBC/Panca Cinta adalah satu kesatuan, bersama Pembelajaran Mendalam dan delapan dimensi profil lulusan.
- Assessment taxonomy fleksibel/versioned; tidak mengunci UTS/UAS/UM/SIMULASI TKA menjadi enum tetap. TKA eksternal tidak otomatis menjadi ujian pusat.
- Tidak ada ExamSession/wave/session_number sebagai lifecycle admission ujian.
- DOCX prioritas import soal; XLSX question import tidak boleh dihidupkan kembali. SHORT_ANSWER tetap diblokir sampai accepted answer/snapshot/scoring teruji.

## AUDIT dan kondisi terbaru
- Main HEAD yang diperiksa: `a17b77aa19eb9ba4a01cff44387dc144aca6f740`.
- Source PR #21 pada run sebelumnya `af36f9edbef7bfa5754afd025a98656079b32616`.
- Run #45134847069: MySQL8 disposable setup, Composer, package discovery, disposable installer, serta focused WebAdminAuth/StudentWebLogin PASS (24 tests / 80 assertions). Full PHPUnit FAILURE.
- Full PHPUnit menunjukkan kelompok 403 karena fixtures lama belum membentuk assignment Guru kanonis; policy exact-role/permission/scope tidak akan dilonggarkan agar test legacy menjadi 200.
- Kelompok failure lain: unique curriculum registry node saat seed, TenantContext hilang pada reporting fixture, import ValidationException pada Proktor test, dan status code test pada legacy API write yang memang sudah nonaktif.
- Lihat laporan CI untuk daftar temuan dan batas bukti.

## IMPLEMENTASI terbaru yang tercatat
- Backup byte-exact enam file dibuat pada branch `backup/solo-web-cbt-ci-repair-20261123`, folder `backend-laravel/docs/backup/solo-web-cbt-ci-repair-2026-11-23/`.
- Patch `ProctorAssignmentSecurityTest.php`: import ValidationException yang benar — commit `6bf699f5a6897f097bfd8fd0033b724d2caa3311`.
- Patch `Phase7DReportingTest.php`: set TenantContext sebelum fixture User tambahan — commit `c5c1f24190f8a86793a943fa66b0bac3971f5662`.
- Patch `AssessmentApiWriteContractTest.php`: expected 405 untuk method yang disabled — commit `8d5d45b9ae596534a41a72b145815c1cfc76bbf0`.
- Source Dashboard dan curriculum registry seeder diperiksa ulang dan ditemukan telah memiliki perbaikan query/idempotency pada branch; patch ganda tidak dibuat.
- Laporan lengkap audit/remediasi: `docs/REPORT_CI_FULL_PHPUNIT_REMEDIATION_2026-11-23.md`.

## PENGUJIAN
CI [#45138531873](https://github.com/zane0184/cbt_november/actions/runs/45138531873) dibuat untuk source SHA `8d5d45b9ae596534a41a72b145815c1cfc76bbf0`. Pada checkpoint ini job masih menyiapkan container; belum ada hasil focused/full PHPUnit. Tidak ada klaim PASS untuk patch baru.
Browser E2E, concurrency MySQL multi-connection, fresh-install/legacy-upgrade lengkap, restore, UAT, VPS dan Android device validation belum terbukti.

## Langkah berikutnya
1. Ambil hasil dan log #45138531873.
2. Perbaiki fixture Guru dengan assignment kurikulum/akademik kanonis untuk test Exam/Scoring/Monitoring tanpa menurunkan policy.
3. Diagnosis LegacyRoleConsolidationMigrationTest dan ExamManagementHardeningTest ViewException.
4. Selesaikan ADM-01–ADM-23 berdasarkan route/Livewire/model/policy/DB/test aktual.
5. Tutup integrasi Web Guru/Siswa, curriculum+KBC, browser E2E dan recovery.
6. Uji fresh install, upgrade legacy, backup/restore dan security/concurrency pada disposable environment.
7. Rakit ZIP generik setelah seluruh verifikasi dan acceptance selesai.

Tidak ada merge PR #21, production migration, deployment, atau perubahan database produksi. Jangan menyebut proyek siap produksi/distribusi.


## Tambahan CI 23 November 2026 — pembatalan run

- Run #45138531873 pada source SHA `8d5d45b9ae596534a41a72b145815c1cfc76bbf0` berakhir **CANCELLED**, bukan FAILURE.
- Pemeriksaan workflow menunjukkan run dibatalkan saat commit dokumentasi checkpoint baru masuk ke branch eksekusi. Karena itu run tersebut tidak membuktikan patch test pass atau fail.
- Source tidak berubah setelah run itu; hanya checkpoint dokumentasi yang maju. HEAD saat ini: `614fe37c86cea2a606f71c0bf466621e040ff111`.
- Validasi ulang akan dipicu dengan perubahan dokumentasi berlabel `[skip ci]` agar source SHA yang sedang ditinjau tidak terus dibatalkan oleh pembaruan checkpoint.


## Addendum W3 — current source and test checkpoint (23 November 2026)

- Current HEAD: `a97301d8b5512a7104978197c3b5dcfeef55458d`.
- PR #21: OPEN / DRAFT / UNMERGED.
- Exact-source CI: [#45140404370](https://github.com/zane0184/cbt_november/actions/runs/45140404370).

### Changes after W1 report
- Fixed malformed RuntimeException namespace in curriculum registry seeder.
- Semester dropdown query uses existing `semesters.term`, not missing `name` column.
- Teacher dashboard curriculum stats now use actual service fields: registered versions, verified CP artifacts, Panca Cinta count, graduate profile dimension count.
- Admin Proctor assignment component now shows history only; assignment creation remains blocked until a canonical central-exam eligibility marker is owner-approved.
- Removed the non-operational assignment form; explanatory text explicitly says appointment does not grant room/session/seat/roster/monitoring/Attempt/CBT access.
- Assignment service denies direct assignment calls via LogicException and contains no unreachable assignment implementation. Revoke remains for historical records.
- Proctor tests now verify blocked creation and monitoring denial, while historical record revoke/expiry remains tested; actual overlap test remains blocked pending canonical eligibility.

### Backup
All corresponding source/test/view backups listed in `docs/REPORT_CI_FULL_PHPUNIT_REMEDIATION_2026-11-23.md` are stored under `backend-laravel/docs/backup/solo-web-cbt-ci-repair-2026-11-23/` on branch `backup/solo-web-cbt-ci-repair-20261123`. The backups were fetched back and compared byte-for-byte after creation.

### CI evidence
- Run #45140404370 exact SHA `a97301d8b5512a7104978197c3b5dcfeef55458d`: PHP 8.3, disposable MySQL identity, Composer, package discovery, installer DB, and focused login/tenant PASS.
- Full PHPUnit was still IN_PROGRESS at checkpoint time. No full-suite PASS claim.
- Older full suite run #45138746855 at SHA `614fe37`: 124 failed / 366 passed / 1,421 assertions. It predates W3 fixes and must not be reported as the current head outcome.

### Next actions
1. Read final result and logs for #45140404370.
2. Continue full-suite failure remediation without weakening policy.
3. Complete file-by-file Admin ADM-01–23 inventory.
4. Complete Guru/Student integration and browser E2E.
5. Complete fresh-install, migration upgrade, restore, concurrency, security and UAT gates before producing generic ZIP.
6. Keep Android and Super Admin deferred until June 2027.

No production migration, deployment, PR merge, or Android work.


## Addendum handover lintas-agent — 23 November 2026

**Pintu masuk wajib agent pengganti:** [docs/AGENT_START_HERE.md](AGENT_START_HERE.md)  
**Handover induk + backlog prioritas:** [docs/AGENT_HANDOVER_MASTER_2026-11-23.md](AGENT_HANDOVER_MASTER_2026-11-23.md)

Dokumen handover baru dibuat dan diverifikasi dapat dibaca dari branch eksekusi. Keduanya memuat keputusan owner, batas keselamatan, status bukti Admin/Guru/Siswa, PR independen yang wajib direkonsiliasi (#13/#18/#19/#21), prioritas P0–P4, protokol backup, acceptance gates, dan instruksi agent baru agar tidak mengulang patch.

Commit dokumen:
- `f68bf07ebd8602e9ca473be8dc0a7d9ff6a3b856` — tambah `docs/AGENT_START_HERE.md`.
- `92862ccd34ed8ed31bc5a6d1ee2cb1837a4b3d01` — tambah `docs/AGENT_HANDOVER_MASTER_2026-11-23.md`.

Verifikasi baca ulang:
- `docs/AGENT_START_HERE.md` tersedia pada branch; blob `2c1d8b95eada4de17af7aa99902e0549ac311b26`.
- `docs/AGENT_HANDOVER_MASTER_2026-11-23.md` tersedia pada branch; blob `5eca59944b74deffef8a00e701e5b051ddcf3586`.

Catatan penting: dokumen GitHub tidak otomatis mengirimkan konteks ke agent/chat baru. Saat pindah agent, pengguna cukup memberi instruksi: **"Lanjutkan proyek CBT dari GitHub. Baca docs/AGENT_START_HERE.md lalu ikuti seluruh handover dan lanjutkan dari checkpoint aktual. Jangan ulang pekerjaan."** Agent tetap wajib memverifikasi HEAD, PR, source, dan CI terbaru sebelum coding.

Tidak ada source runtime, migration, DB produksi, VPS, PR merge, atau deployment yang diubah pada langkah handover ini. PR #21 tetap tidak boleh di-merge tanpa approval dan seluruh acceptance gate.


## Addendum rekonsiliasi keseluruhan portal — 23 November 2026

Laporan terbaru: [REPORT_REKONSILIASI_AWAL_KETIGA_PORTAL_2026-11-23.md](REPORT_REKONSILIASI_AWAL_KETIGA_PORTAL_2026-11-23.md).

### HEAD PR/branch yang diverifikasi ulang
- PR #21 integrasi: `44c2b7e3f84f5fef4ff749add13a1fd75369fa6d` — OPEN/DRAFT/UNMERGED.
- PR #13 Web Siswa: `9ef375c407b1faf606c5a268f00b24424d93be9e` — OPEN/DRAFT/UNMERGED.
- PR #19 Web Guru: `71296ca6e7c48b7295455a26d38bda11b901eb1d` — OPEN/DRAFT/UNMERGED.
- PR #18 Web Admin legacy room/proctor: `42a0de2c1f86b8e76e34e1b74ab0feaca90def3a` — OPEN/DRAFT/UNMERGED.
- Agent 2 auth/session/fencing branch: `b00cf4312b12e1158c609ca91af3dcecec7a2553`.

### Koreksi status Web Siswa
Audit anti-curang lama di branch integrasi memeriksa runner lama (blob `7283ca...`). Runner Agent 4 terbaru pada PR #13 adalah blob `0c539c...` dan sudah berisi mobile-first, browser deterrent, IndexedDB, fence/heartbeat client, receipt verifier. Jangan mengulang patch tersebut. Namun bukti E2E/CI terbaru yang tercatat pada checkpoint Agent 4 masih untuk SHA lebih lama; verifikasi exact HEAD PR #13 dan browser-to-real-Laravel integration masih terbuka.

### Backend Agent 2 — P0 belum lulus
Run #45066851753 pada exact SHA `b00cf4312b12e1158c609ca91af3dcecec7a2553`: MySQL 8 fresh migration + PHP lint PASS, integration suite FAILURE (4 failed, 78 passed, 293 assertions), two-connection concurrency SKIPPED. Log: https://github.com/zane0184/cbt_november/actions/runs/45066851753.
Dua mismatch kontrak telah dibaca langsung dari source:
- `require_token=false` saat ini salah ditolak oleh kondisi `!require_token || !token`.
- Receipt response menyimpan client_request_id pada ledger, tetapi objek receipt belum mengembalikannya; Agent 4 strict verifier membutuhkan field itu.
Empat failure lama harus ditriase dari response/error detail, bukan diperbaiki dengan melonggarkan security.

### Konflik integrasi
- Jangan merge seluruh branch Agent 2 ke PR #21: branch sangat divergen dan berisi sejumlah fitur historis yang tidak sesuai keputusan owner.
- Jangan merge PR #18 room/session/multi-Proktor otomatis. Cocokkan dengan keputusan terbaru Issue #20: tidak ada ExamSession/wave/session_number lifecycle; Proktor bukan portal/role permanen dan tidak memperoleh monitoring/Attempt/operasi CBT.
- PR #19 memiliki CI historis 118/520 PASS pada SHA `91d8f698...`; PR head aktual berbeda, jadi cek ulang CI exact SHA sebelum menyatakan fitur Guru teruji pada HEAD terkini.

### Implementasi tahap ini
- Ditambahkan laporan rekonsiliasi awal lintas portal dan backlog P0–P4.
- Tidak ada source runtime, migration, database produksi, VPS, merge, atau deployment yang diubah.
- Commit laporan: `8b8fc6fc645779152f36cd53bf3db59ef6490d03`.

### NEXT
1. Perbaiki kontrak backend token optional dan receipt request ID dengan backup byte-exact + regression tests.
2. Diagnosis 4 failure Agent 2 dan jalankan concurrency test sampai PASS.
3. Reconcile runner PR #13 terbaru ke integrasi (bukan runner lama).
4. Browser-to-real-Laravel integration dan platform enforcement Android native vs Web.
5. Lanjutkan inventaris Admin ADM-01–ADM-23 dan integrasi akademik Web Guru.


## Addendum CI exact HEAD Agent 4 dan Web Guru — 23 November 2026

Laporan bukti diperbarui di [REPORT_REKONSILIASI_AWAL_KETIGA_PORTAL_2026-11-23.md](REPORT_REKONSILIASI_AWAL_KETIGA_PORTAL_2026-11-23.md).

- PR #13 Web Siswa HEAD `9ef375c407b1faf606c5a268f00b24424d93be9e`: run [#45110617970](https://github.com/zane0184/cbt_november/actions/runs/45110617970) SUCCESS. Static audit PASS, Chromium browser E2E PASS, WebKit engine compatibility E2E PASS. Laravel disposable MySQL tests: StudentResultVisibility 7/22, StudentExamPortal 10/47, StudentWebLogin 12/88; total 29 passed / 157 assertions.
- Catatan: WebKit engine compatibility bukan UAT iOS Safari perangkat fisik; browser E2E memakai mock API, belum browser-to-real-Laravel integration.
- PR #19 Web Guru HEAD `71296ca6e7c48b7295455a26d38bda11b901eb1d`: run [#45112459424](https://github.com/zane0184/cbt_november/actions/runs/45112459424) SUCCESS; 118 passed / 520 assertions, PHP lint, route registration, Composer, disposable MySQL 8 dan InnoDB checks PASS.
- Hasil ini memperbarui bukti lama yang hanya mencatat SHA sebelumnya. Selected suites lulus exact PR HEAD, tetapi tidak berarti portal lengkap/siap produksi.

### Implementasi P0 Agent 2 yang sedang diuji
Pada branch `work/agent2-p0-auth-session-fencing-20261123`, dibuat backup byte-exact 5/5 sebelum patch dan perubahan:
1. Inisialisasi fence generation eksplisit sebelum membuat lease.
2. Token hanya diwajibkan ketika `require_token=true`.
3. Receipt fresh response dan replay menyertakan `client_request_id`.
4. Regression tests untuk token opsional, generation/lease awal, dan receipt exact request ID.
- Backup manifest: `backend-laravel/docs/backup/agent2-p0-auth-session-fencing-2026-11-23/coordinator-p0-fix-2026-11-23/MANIFEST.md`.
- Patch commits: `d13e8cfd...`, `e99daf0...`, `c9e06b3...`, `3a7fdb5...`, `8aaeb76...`.
- CI terbaru exact SHA `8aaeb767293cc06350752f7a680b54c40b4e87c7`: run [#45144928769](https://github.com/zane0184/cbt_november/actions/runs/45144928769) masih berjalan pada saat checkpoint ini ditulis. Jangan klaim PASS sebelum seluruh suite dan two-connection concurrency selesai.


Addendum lanjutan terarah + kurikulum satu ekosistem — 23 November 2026

## Addendum lanjutan terarah dan kurikulum satu ekosistem — 23 November 2026

Laporan tahap terbaru: [REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md](REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md). Kontrak kurikulum diperjelas di [DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md](DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md).

### HEAD/CI terkini
- PR #21 tetap OPEN/DRAFT/UNMERGED; branch `work/solo-web-cbt-completion-20261123`.
- Source sebelum patch login: `6fcdc757fa04ca04a1871b1685e0c89d67ccfd13`.
- Workflow [#45148868236](https://github.com/zane0184/cbt_november/actions/runs/45148868236) FAILURE pada SHA tersebut: focused auth/login smoke 27 passed/90 assertions; full PHPUnit 163 failed/346 passed/1,388 assertions.
- Failure terbesar: Phase7BManualReview 17, ExamSnapshot 15, Phase7CReopen 14, ExamSessionHardening 13, QuestionBank 8, SubmitLocking 8, AnswerKeyLeakage 7, ExamExecutionHardening 6, ExamManagementHardening 6. Root cause tiap kelompok belum selesai ditriase; jangan mengubah policy untuk membuat test lama lulus.
- Login failure terdiagnosis: `LoginIdentityResolver` menghitung seluruh Tenant dan menolak jika bukan tepat satu, padahal `SystemTenantSeeder` membuat `SYSTEM_GLOBAL` selain tenant lokal. Resolver diperbaiki untuk menggunakan satu `LocalInstallationIdentity.tenant_id` dan memvalidasi tenant lokal yang dirujuk, tanpa menganggap SYSTEM_GLOBAL sebagai madrasah kedua.
- Backup byte-exact sebelum patch: resolver blob `3d1a1d8294453f8d53fd15d5d7910e22478790f6`; AuthTest blob `803f409f48b544d947039d0879d7e56c4c118336`.
- Patch resolver commit `2f1becace2e2d0fdb5891f586426b774f3eaea08`; regression test SystemTenant commit `d70270d55de8b5e0bfd8429ce7b0cae3c4bca1c1`.
- Workflow #45149995187 dan #45150035648 dibatalkan oleh workflow concurrency karena commit berikutnya datang. Workflow #45150186044 pada HEAD dokumentasi `de0f3b61f0b3dc8b840dd0f17c003315626c0335` tercatat QUEUED saat checkpoint dibuat; cek hasil CI terbaru sebelum klaim.
- Tidak ada hasil PASS untuk regression SystemTenant sampai CI exact HEAD selesai.

### Kurikulum: satu ekosistem terpadu
- Owner menegaskan Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam merupakan satu kesatuan, bukan tiga kurikulum/modul/database.
- Desain relasi dan peran sudah ditambahkan di dokumen kontrak.
- Source assignment mapping, QuestionVersion mapping, dan ExamSnapshot sudah memakai keluarga model kanonis.
- Gap nyata: `CurriculumAchievementService` dan `CurriculumAnalyticsController` masih memakai model legacy LearningOutcome/LearningObjective/LearningPath. Laporan historis belum terbukti membaca konteks versi/snapshot kanonis yang sama. Backlog P3: audit read-model, kompatibilitas histori, lalu E2E sampai laporan. Jangan buat sumber kebenaran ganda.

### Implementasi tahap terbaru
- `LoginIdentityResolver.php`: validasi local tenant berdasarkan installation identity, tidak menolak SYSTEM_GLOBAL.
- `AuthTest.php`: regression test login saat SYSTEM_GLOBAL ada.
- Backup source/test berada di `backend-laravel/docs/backup/solo-web-cbt-p0-integration-2026-11-23/`.
- Laporan tahap: commit `30ffd16f02612c36b54090d641fe162fd2a40c2b`.
- Kontrak kurikulum: commit `de0f3b61f0b3dc8b840dd0f17c003315626c0335`.

### NEXT yang terarah
1. Ambil hasil CI exact HEAD; jalankan focused AuthTest + SystemTenant regression. Jika gagal, baca body/log dan perbaiki akar masalah.
2. Triase P0 session/lease/receipt/expiry tests. Jangan mengubah acceptance keamanan untuk memenuhi fixture lama.
3. Kelompokkan failure suite Guru/Exam/Scoring berdasarkan kontrak dan fixture, mulai dari dependency canonical TeacherSubjectAssignment, QuestionVersion, immutable snapshot, dan authorization.
4. Reconcile latest Web Siswa PR #13 ke integrasi; lakukan browser-to-real-Laravel E2E.
5. Inventaris ADM-01–ADM-23 dan lanjutkan Admin.
6. Selesaikan read-model kurikulum/laporan agar satu ekosistem terhubung hingga hasil historis.
7. PR #21 tetap Draft; tidak ada merge, produksi, VPS, Android build, atau ZIP distribusi.
