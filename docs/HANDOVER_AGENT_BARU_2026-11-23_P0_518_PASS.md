# HANDOVER KANONIS — AGENT PENGGANTI CBT MADRASAH
Tanggal pembaruan: 23 November 2026
Repository: `zane0184/cbt_november`
Branch handover/P0: `work/phpunit-41-root-cause-20261123`
HEAD handover: `856294037312c186f03422071368bcaa2987516a`
Branch target integrasi: `work/solo-web-cbt-completion-20261123-execution`
Base SHA branch target: `d6aaeae6f2851bf15272d0cc2bce9e68989d3a4d`

> **BACA INI SEBELUM MELAKUKAN APA PUN.** Dokumen ini adalah status operasional terkini untuk pergantian agent. Source aktual + CI exact SHA adalah kebenaran. Dokumen lama yang menyebut 41/92/160 kegagalan atau branch lama adalah riwayat, bukan pekerjaan yang harus diulang.

## 1. Status eksekusi yang sudah terbukti

<badge> P0 BACKEND — LULUS CI </badge>

- CI terbaru: [Run #45220055382](https://github.com/zane0184/cbt_november/actions/runs/45220055382)
- Exact tested HEAD: `856294037312c186f03422071368bcaa2987516a`
- Workflow: Tenant Backend CI, job `laravel-mysql`, job ID `137851568268`.
- PHP 8.3 + disposable MySQL 8.0.46/InnoDB.
- Login/Web tenant identity smoke: **27 passed, 90 assertions**.
- Full PHPUnit: **518 passed, 0 failed, 2,189 assertions**.
- Langkah CI yang tercatat sukses: isolated MySQL identity check, Composer lock/platform validation, locked dependency install, Laravel package discovery, disposable installer E2E database, focused smoke, full PHPUnit, container cleanup.
- PR #22: [fix: close 41 PHPUnit failures without weakening CBT security](https://github.com/zane0184/cbt_november/pull/22), status OPEN + DRAFT, head branch `work/phpunit-41-root-cause-20261123`, head SHA `8562940...`, base branch `work/solo-web-cbt-completion-20261123-execution`. PR belum merged; tidak ada review. **Jangan merge otomatis.**
- Branch target masih pada base SHA `d6aaeae...`; remediasi belum masuk ke branch target karena PR #22 masih Draft.

**Kesimpulan yang boleh dipakai:** automated full PHPUnit pada exact HEAD di atas lulus 518/0. **Bukan** bukti seluruh portal, integrasi browser, Proktor, offline recovery, VPS, ZIP, atau UAT sudah selesai.

## 2. Larangan mengulang pekerjaan P0 yang sudah selesai

Jangan:
1. Mengulang triage baseline 41 kegagalan seolah masih gagal.
2. Mengulang patch source/test P0 yang tercantum di §3.
3. Menghapus test, melonggarkan policy/RBAC, menjadikan FK nullable, menurunkan validasi, atau melemahkan fencing untuk mengejar status hijau.
4. Menganggap angka 92 failed pada CI lama #45168433247 atau 41 failed pada baseline #45191014821 sebagai status sekarang.
5. Menyalin branch/commit lama dari prompt lama tanpa verifikasi.

Root cause baseline 41 failure sudah diperbaiki dan diuji. Test count tidak dikurangi untuk membuat suite hijau.

## 3. Perubahan P0 yang sudah ada pada branch handover

Branch `work/phpunit-41-root-cause-20261123`, exact HEAD `856294037312c186f03422071368bcaa2987516a`:

| File | Perubahan yang sudah dilakukan |
|---|---|
| `backend-laravel/app/Livewire/Admin/ExamManagement.php` | Query peserta/role memakai `users.tenant_id`, bukan `role_user.tenant_id` yang tidak tersedia. Ini memperbaiki bug source nyata. |
| `backend-laravel/tests/Concerns/CreatesCanonicalCurriculumContext.php` | Fixture AssessmentCurriculumMapping mengisi `academic_year_id` dan `semester_id` dari assignment kanonis. |
| `backend-laravel/tests/Feature/Phase7DHistoricalRecalculationAndAnalyticsTest.php` | Fixture historical recalculation tidak mengganti registration/version assignment yang masih direferensikan FK. |
| `backend-laravel/tests/Feature/ExamManagementHardeningTest.php` | Test mengikuti `selectedAssignmentId` dan assignment-scoped builder; foreign assignment yang valid tetap ditolak/fail-closed. |
| `backend-laravel/tests/Feature/ExamBuilderTest.php` | Question dan QuestionVersion berstatus PUBLISHED sesuai kontrak snapshot. |
| `backend-laravel/tests/Feature/ExamSnapshotTest.php` | Fixture kurikulum/published valid; cross-tenant fixture memakai identitas curriculum yang sesuai; cleanup dua koneksi mengikuti FK child-first. |
| `backend-laravel/tests/Feature/RateLimitingAndAbuseProtectionTest.php` | Cached Sanctum guard direset saat mengganti bearer token; identitas User A/B diuji terpisah. |
| `backend-laravel/tests/Feature/Security/AnswerKeyLeakageTest.php` | PAT Sanctum persisten + fencing generation; SHORT_ANSWER tetap fail-closed; metadata ESSAY tidak bocor. |
| `backend-laravel/tests/Feature/TeacherPracticeWorkflowTest.php` | Jadwal fixture berada di masa depan sesuai validasi server. |

Root cause yang sudah tertutup: required academic year/semester fixture, FK historical recalculation, kontrak Livewire lama, cached Sanctum guard, query tenant salah, status Question/QuestionVersion, cross-tenant curriculum fixture, cleanup FK dua koneksi, persisted PAT/fencing, SHORT_ANSWER fail-closed, ESSAY metadata privacy, dan jadwal TeacherPractice.

## 4. Lokasi backup — jangan ditimpa

Backup byte-exact file source/test sebelum remediasi:
`backend-laravel/backup_before_patch/PHPUNIT_41_ROOT_CAUSE_20261123/`

Termasuk backup utama dan iterasi `round2/` sampai `round6/`. Nama file antara lain:
- `CreatesCanonicalCurriculumContext.php.before`
- `CreatesCanonicalTeacherAssignment.php.before`
- `ExamManagement.php.before`
- `ExamManagementHardeningTest.php.before`
- `Phase7DHistoricalRecalculationAndAnalyticsTest.php.before`
- `RateLimitingAndAbuseProtectionTest.php.before`

Backup workflow:
`.github/workflows/backup/tenant-backend-ci.yml.before-phpunit-root-cause-branch-20261123`

Backup dokumen:
- `docs/backup/AGENT_START_HERE.md.before-coordinator-addendum-2026-11-23`
- `docs/backup/REPORT_KOORDINATOR_AUDIT_GITHUB_DAN_ROOT_CAUSE_2026-11-23.md.before-518-pass`
- `docs/backup/ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md.before-master-spec-reconciliation`

**Sebelum mengubah file existing berikutnya:** buat backup byte-exact baru di folder patch yang diberi nama/tanggal, verifikasi hash/isi, catat path dan checksum di laporan.

## 5. Dokumen yang wajib dibaca (urut)

Pada branch `work/phpunit-41-root-cause-20261123`:
1. `docs/AGENT_START_HERE.md`
2. Dokumen ini: `docs/HANDOVER_AGENT_BARU_2026-11-23_P0_518_PASS.md`
3. `docs/PROMPT_AGENT_BARU_LANJUTAN_2026-11-23.md`
4. `docs/REPORT_KOORDINATOR_AUDIT_GITHUB_DAN_ROOT_CAUSE_2026-11-23.md`
5. `docs/CHECKPOINT_KOORDINATOR_2026-11-23_P0_PHPUNIT_518_PASS.md`
6. `docs/ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md`
7. `docs/AGENT_HANDOVER_MASTER_2026-11-23.md`
8. `docs/MATRIKS_ADM_01_23_AUDIT_2026-11-23.md`
9. `docs/REPORT_P0_TRIAGE_FULL_PHPUNIT_2026-11-23.md`
10. `docs/REPORT_AUDIT_ANTI_CHEAT_WEB_SISWA_MOBILE_FIRST_2026-11-23.md`
11. `docs/DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md`
12. `docs/REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md`

Master spesifikasi produk di branch/ref terpisah:
- Web Admin: [Master Web Admin di main](https://github.com/zane0184/cbt_november/blob/main/docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md)
- Web Guru: [Master Web Guru di main](https://github.com/zane0184/cbt_november/blob/main/docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md)
- Web Siswa: [Master Web Siswa pada branch Agent 4](https://github.com/zane0184/cbt_november/blob/work/agent4-web-siswa-audit-20261122/docs/MASTER_SPESIFIKASI_FITUR_WEB_SISWA_CBT_MADRASAH_2026-11-22.md)
- Keputusan owner Proktor/instalasi/role/import: [Addendum pada branch handover](ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md)
- Issue #20: [keputusan dan komentar owner](https://github.com/zane0184/cbt_november/issues/20)
- Issue #10: [kontrak lama ExamSession — komentar owner 7081649923](https://github.com/zane0184/cbt_november/issues/10#issuecomment-7081649923) dan arahan terbaru terkait kurikulum di [komentar 7091954732](https://github.com/zane0184/cbt_november/issues/10#issuecomment-7091954732).

**Catatan:** Jangan mengedit/menyalin master spec dari branch lain ke branch kerja tanpa kebutuhan dan review. Baca spesifikasi di ref aslinya.

## 6. Keputusan owner yang mengikat

- Satu ZIP generik; **satu instalasi dan satu database akademik mandiri per madrasah**. Tidak ada shared academic database atau akses lintas madrasah. `tenant_id`/System Tenant yang masih ada harus dipahami dalam konteks lokal/legacy/defense-in-depth; jangan refactor massal sebelum audit.
- Role aktif tepat: `ADMIN_MADRASAH`, `GURU`, `SISWA`.
- Tidak ada portal/role Operator, Kamad, Proktor, atau Super Admin lokal. Operator dilebur ke Admin; Kamad metadata; histori role lama tidak dihapus sembarangan.
- Proktor hanya penugasan sementara/capability pada akun Guru di Web Guru, bukan role atau portal.
- Mode Proktor owner adalah Mode B Exam+Room untuk ujian terpusat; Admin hanya assign/revoke Guru. Admin tidak mengelola Room/roster dan tidak memonitor peserta.
- Proktor default monitoring-only, hanya peserta dalam Room assignment terkait. Tidak melihat soal/kunci/jawaban/nilai, tidak reset/force-submit/extend/takeover.
- Tidak ada `ExamSession`, `SessionRoom`, `session_number`, `wave`, `session_token`, atau session lifecycle. Room bukan admission gate.
- Guru membuat/mengatur/menerbitkan ujian sendiri sesuai assignment tanpa persetujuan Admin.
- Android native dan Web Super Admin ditunda sampai **Juni 2027**. Jangan jadikan blocker Web.
- Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + delapan dimensi profil lulusan adalah satu ekosistem dengan provenance/versioning sampai laporan historis.
- Import soal: konflik Master Guru §5.8/Issue #8/#9/#16 dengan PR #19 harus direkonsiliasi sebelum coding. DOCX prioritas sesuai arahan terbaru yang tercatat; jangan hidupkan importer XLSX soal lama. XLSX import Guru/Siswa pada Admin tetap berbeda dan tidak boleh ikut dihapus. PDF/OCR deferred. SHORT_ANSWER tetap BLOCKED sampai accepted answer privat, immutable snapshot, dan scoring terbukti.
- Jangan jalankan Gradle via terminal.
- Jangan merge, migration produksi, deploy VPS, atau ZIP final tanpa preflight, acceptance, dan persetujuan owner.

## 7. PR/Issue: status dan aturan integrasi

- **PR #22**: Draft, OPEN, head `work/phpunit-41-root-cause-20261123`, base `work/solo-web-cbt-completion-20261123-execution`. Tujuan: membawa remediasi P0 518/0 ke branch eksekusi. CI lulus pada exact HEAD 856294. Review PR tetap wajib; **jangan merge otomatis**.
- **PR #21**: Solo Web CBT lama, Draft/stale/divergen dari branch target eksekusi. Jangan jadikan source aktif tanpa compare.
- **PR #19**: Web Guru/import XLSX. Belum integrasi; rekonsiliasi dengan Master Guru §5.8 dan Issue #16.
- **PR #18**: Web Admin; jangan gabung apa adanya karena ada kontrak Room/Proktor/session yang perlu audit terhadap keputusan owner.
- **PR #17**: kontrak/audit Web Guru; cek source dan status aktual sebelum implementasi.
- **PR #13**: Web Siswa IndexedDB/submit barrier; real Laravel E2E, offline recovery, dan browser-to-backend belum dianggap selesai.
- PR lain #1/#3–#7: compare source/base/CI sebelum integrasi; jangan merge berdasarkan nomor atau laporan.
- Issue #20: audit ADM-01–23; gunakan checklist terbaru dan keputusan owner.
- Issue #14/#15: provenance/versioning kurikulum, Pembelajaran Mendalam, 8 DPL.
- Issue #8/#9/#16: kontrak import soal; rekonsiliasi DOCX/XLSX.
- Issue #10: ExamSession lama superseded; jangan implement lifecycle session.

Status PR dapat berubah; agent wajib fetch ulang sebelum bekerja.

## 8. Backlog terurut — pekerjaan yang benar-benar tersisa

### P1-A — Integrasi PR #22 ke branch eksekusi
1. Verifikasi ulang branch refs, PR #22 head/base, diff, changed files, review, CI exact SHA.
2. Lakukan review source/test/security pada PR #22.
3. Minta persetujuan owner sebelum merge. Tidak ada merge otomatis.
4. Setelah integrasi, jalankan CI pada exact HEAD branch target dan catat SHA/log.

### P1-B — Proktor Mode B (Exam + Room)
- Audit source service/model/schema/route/policy/test saat ini; jangan ulang desain.
- Tambahkan marker operasional ujian yang eksplisit sesuai keputusan owner dan taxonomy kanonis proyek. **Jangan menganggap UTS/UAS/UM sebagai taxonomy kurikulum**; itu jenis operasional ujian.
- Implementasi assignment Exam+Room+Guru, interval `[starts_at, ends_at)`, overlap/concurrency, revoke, audit actor/time/reason.
- Menu Web Guru: tugas aktif, jadwal, detail Exam/Room, roster Room, monitoring, catatan kehadiran/insiden, riwayat.
- Enforce room scope di query/API/route/event/cache/export/download/action. Tambahkan negative tests lintas Room/Exam.
- Admin hanya assign/revoke, bukan kelola roster/monitoring. Jangan bypass service fail-closed sebelum marker dan tests lengkap.

### P1-C — Web Admin ADM-01–23
- Audit setiap requirement dengan route, Livewire/controller/service, model/schema, policy, test, CI evidence.
- Gunakan status EXISTS & VERIFIED / EXISTS BUT BROKEN / MISSING / BLOCKED / DEFERRED.
- Prioritas sesuai master: identitas/logo/branding/kop; CRUD Guru/Siswa; akun/akses; import/export Guru/Siswa; tahun ajaran/semester/kelas/rombel/mapel/assignment; registry kurikulum; laporan/cetak administratif; audit log; pengaturan; backup/restore.
- Jangan memberi Admin akses bank soal, ujian, peserta, token, Attempt, jawaban, monitoring, penilaian/nilai.

### P1-D — Web Guru
- Audit assignment, CP/TP/ATP, materi/topik, bank soal/versioning, DOCX parser, media, taxonomy asesmen, snapshot, publish/token/jadwal, monitoring Guru pembuat, grading, histori, analytics.
- Jangan implement ulang fitur yang sudah ada.
- Import soal: selesaikan konflik spesifikasi dahulu; jangan implement XLSX question importer lama. DOCX belum boleh disebut lulus tanpa uploaded-file parser + security + staging/preview/commit tests.
- SHORT_ANSWER tetap BLOCKED; ESSAY manual review harus menjaga metadata/jawaban privat sesuai kontrak.

### P1-E — Web Siswa
- Reconcile PR #13 dan master spec.
- Integrasikan ke Laravel API/Sanctum/PAT/Device Lease/fencing dan Exam tanpa ExamSession.
- Uji login, start/resume, jadwal/token, heartbeat, save/retry/receipt/idempotency, media, 409/stale fence, submit barrier, expiry, lost response, offline/reconnect/recovery, multi-tab/device, submit/expiry race.
- Focus/visibility hanya sinyal audit, bukan auto-submit/punishment.
- Browser-to-real-Laravel/MySQL E2E wajib; mock/static test bukan bukti integrasi. Physical Safari/Chrome UAT saat perangkat tersedia.

### P1-F — Satu ekosistem Kurikulum
- Satu kontrak/read model yang menghubungkan registrasi+versi sumber → fase/kelas/mapel → assignment → CP → TP/ATP → materi/topik → Panca Cinta/PM/8 dimensi → QuestionVersion → Assessment → immutable Exam Snapshot → Attempt/Answer/Score → capaian/laporan.
- Provenance dan versi harus tersimpan pada snapshot; perubahan master tidak boleh mengubah laporan historis.
- Jangan buat CP ganda, hapus histori, atau mengubah snapshot lama.

### P2 — Acceptance, VPS, dan ZIP
- PHPUnit exact HEAD; browser E2E tiga portal; security/IDOR; concurrency multi-device/fencing; offline/recovery; load/stability; fresh install + upgrade pada disposable DB; repeat seeder; backup/restore; UAT Admin/Guru/Siswa.
- Rencana aaPanel/Nginx/PHP 8.3/MySQL 8/queue/scheduler/storage/permissions/SSL/backup setelah acceptance.
- ZIP distribusi lengkap, clean extraction, README, tidak menyertakan backup/temp/secret/.env produksi.
- Jangan klaim SIAP DIDISTRIBUSIKAN atau SIAP PRODUKSI sebelum semua gate dan persetujuan owner.
- Android native dan Web Super Admin tetap deferred sampai Juni 2027.

## 9. Matriks status per area pada handover

| Area | Status saat handover | Bukti / pekerjaan berikutnya |
|---|---|---|
| Backend PHPUnit | **PASS 518/0** | CI #45220055382 exact HEAD 856294 |
| Login + tenant identity smoke | PASS 27/90 | Job 137851568268 |
| P0 root cause 41 failures | DONE + tested | Source/test list §3; backup §4 |
| PR #22 integration | PENDING | Draft, not merged, owner approval |
| Proktor Mode B Exam+Room | NOT VERIFIED / incomplete | Marker, assignment, Room scope, UI, negative tests |
| Web Admin ADM-01–23 | PARTIAL / NOT VERIFIED | Audit matrix + master spec |
| Web Guru full E2E | NOT VERIFIED | Master Guru + PR #19/#17 review |
| Web Siswa real Laravel E2E | NOT VERIFIED | PR #13 and master Siswa |
| DOCX real parser | NOT VERIFIED | Upload parser E2E/security |
| XLSX question import | CONFLICT / HOLD | Reconcile PR #19, Issue #16, Master Guru |
| SHORT_ANSWER | BLOCKED | Accepted answer + immutable snapshot + scoring |
| Curriculum provenance to historical report | PARTIAL / NOT VERIFIED | Issue #14/#15, design contract |
| Offline recovery / multi-device UAT | NOT VERIFIED | integration + concurrency tests |
| Fresh VPS install / deployment | NOT RUN | disposable acceptance first |
| ZIP build / clean extraction | NOT RUN | after all acceptance |
| Madrasah UAT | NOT RUN | Admin/Guru/Siswa validation |
| Android native + Web Super Admin | DEFERRED | June 2027 |

## 10. Protokol kerja agent selanjutnya

1. Mulai dengan verifikasi HEAD/ref, PR, issue owner comments, workflow, job/log, dan file aktual; jangan percaya branch/status dari dokumen lama tanpa fetch.
2. Gunakan GitHub sebagai satu-satunya sumber proyek. Jangan membuka ZIP baseline, ChatGPT Project Files, atau Library.
3. Buat satu backlog terurut. Jangan mengerjakan banyak akar masalah sekaligus.
4. Sebelum patch existing: backup byte-exact, checksum, catat manifest.
5. Patch sekecil mungkin, regression test, focused test, full suite pada disposable MySQL.
6. Jangan mengubah source di branch target langsung jika PR/branch kerja terisolasi diperlukan.
7. Update laporan + checkpoint + prompt handover di GitHub setelah tiap tahap. Cantumkan SHA, file, backup, hasil test, CI link, status, risiko, next step.
8. Jangan menyatakan selesai tanpa bukti dan jangan melakukan merge/deploy/production migration/ZIP final tanpa izin owner.

## 11. Format laporan wajib

Setiap laporan/checkpoint harus memisahkan:
- **AUDIT:** source/ref/commit/requirement yang diperiksa dan temuan.
- **DESAIN:** keputusan kontrak dan trade-off.
- **IMPLEMENTASI:** file/commit yang berubah dan backup.
- **PENGUJIAN:** command/workflow/job, exact SHA, environment, pass/fail/assertions/skips, log.
- **RISIKO & BATAS:** apa yang belum dibuktikan.
- **CHECKPOINT BERIKUTNYA:** tugas tunggal berikut dan kriteria selesai.

---
**Instruksi pindah agent:** buka `docs/AGENT_START_HERE.md`, lalu baca dokumen ini dan prompt pasangannya. Jangan mulai dari baseline 41 failures; lanjutkan dari P1-A.
