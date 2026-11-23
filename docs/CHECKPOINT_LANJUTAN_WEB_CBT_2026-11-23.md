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
