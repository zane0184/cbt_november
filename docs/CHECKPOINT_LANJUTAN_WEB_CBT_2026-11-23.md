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
