

## Verifikasi CI terbaru pada exact HEAD PR #22 — run #45220055382

- Workflow: Tenant Backend CI; job `laravel-mysql`, job ID `137851568268` — SUCCESS.
- Exact HEAD yang diuji: `856294037312c186f03422071368bcaa2987516a` pada branch `work/phpunit-41-root-cause-20261123`.
- PHP 8.3; disposable MySQL 8.0.46/InnoDB; isolated MySQL identity check, Composer lock/platform, dependencies, package discovery, installer E2E database dan cleanup sukses.
- Focused Web login + tenant identity smoke: **27 passed (90 assertions)**.
- Full PHPUnit: **518 passed (0 failed, 2,189 assertions)**.
- Log job: https://github.com/zane0184/cbt_november/actions/runs/45220055382
- PR #22 masih OPEN/DRAFT/UNMERGED, head SHA 8562940..., base `work/solo-web-cbt-completion-20261123-execution`. Tidak ada review. Jangan merge otomatis.
- Source/test P0 tidak perlu diulang. Handover kanonis: [HANDOVER_AGENT_BARU_2026-11-23_P0_518_PASS.md](HANDOVER_AGENT_BARU_2026-11-23_P0_518_PASS.md); prompt agent: [PROMPT_AGENT_BARU_LANJUTAN_2026-11-23.md](PROMPT_AGENT_BARU_LANJUTAN_2026-11-23.md).

**Catatan status:** 518/0 hanya menutup automated PHPUnit pada exact HEAD ini. E2E portal, Proktor Mode B, offline recovery/multi-device, acceptance VPS/ZIP, dan UAT tetap belum selesai.
# CHECKPOINT KOORDINATOR — 23 NOVEMBER 2026
## P0 PHPUnit 518/0 PASS; handover ke P1 Web Admin/Guru/Siswa

Repository: `zane0184/cbt_november`  
Branch kerja terisolasi: `work/phpunit-41-root-cause-20261123`  
Branch sumber/base: `work/solo-web-cbt-completion-20261123-execution`  
Base SHA: `d6aaeae6f2851bf15272d0cc2bce9e68989d3a4d`  
Branch HEAD sebelum checkpoint update ini: `2899baf49e3432322643d5fa72b3a08a715852ef`
SHA source/test yang diuji: `085a20eea16ada12e8c055fca6be9bb1d6d6972a`

## 1. Status aktual

**PASS** Full PHPUnit pada CI #45215342323, #45217367885, #45218279043, dan CI checkpoint PR #22 #45219173631: **518 passed, 0 failed, 2.189 assertions**.

Link CI: https://github.com/zane0184/cbt_november/actions/runs/45215342323
Verifikasi ulang branch dokumentasi: https://github.com/zane0184/cbt_november/actions/runs/45217367885
CI HEAD PR #22: https://github.com/zane0184/cbt_november/actions/runs/45218279043
CI checkpoint PR #22: https://github.com/zane0184/cbt_november/actions/runs/45219173631

Environment: PHP 8.3, disposable MySQL 8.0.46/InnoDB. MySQL identity, Composer/dependencies, installer E2E, login/tenant smoke, dan full PHPUnit lulus pada SHA 085a20ee.

**Catatan exact SHA:** Source/test code yang lulus pertama kali adalah SHA 085a20ee. Run #45217367885 lulus pada branch dokumentasi 6b5e412e, run #45218279043 lulus pada HEAD PR #22 e3dd533, dan run #45219173631 lulus pada HEAD PR #22 2899baf (semuanya 518/0). Source/test/workflow code tetap sama sejak SHA 085a20ee. HEAD sebelum checkpoint update ini adalah 2899baf. CI push yang dipicu checkpoint commit ini harus selesai sebelum PR dapat dinyatakan tervalidasi pada HEAD terbaru.

## 2. Perubahan P0 yang sudah dilakukan

- `app/Livewire/Admin/ExamManagement.php`: query role peserta memakai `users.tenant_id`, bukan kolom `role_user.tenant_id` yang tidak ada.
- Fixture `CreatesCanonicalCurriculumContext`: isi `academic_year_id` dan `semester_id` dari assignment pada AssessmentCurriculumMapping.
- Historical recalculation: tidak mengganti registration/version assignment yang masih direferensikan; fixture memakai canonical context.
- Livewire ExamManagement tests: selaras dengan `selectedAssignmentId` dan assignment-scoped builder; foreign assignment yang valid ditolak.
- Question/QuestionVersion fixtures: status PUBLISHED sesuai kontrak snapshot.
- ExamSnapshot tests: cross-tenant curriculum fixture sah; cleanup exam/assessment/mapping/local registration/System Tenant registry dilakukan child-first untuk FK MySQL.
- Rate limit tests: reset cached guard saat mengganti bearer token; isolasi User A/B tetap menguji batas produksi.
- AnswerKeyLeakage: Sanctum PAT persisten + fencing generation; SHORT_ANSWER tetap fail-closed; metadata ESSAY tidak bocor.
- TeacherPractice: jadwal test berada di masa depan sesuai validasi.
- Test count tetap 518; tidak menghapus test untuk membuat suite hijau.

## 3. Backup

Backup byte-exact berada pada:

`backend-laravel/backup_before_patch/PHPUNIT_41_ROOT_CAUSE_20261123/`

Termasuk:
- `CreatesCanonicalCurriculumContext.php.before`
- `CreatesCanonicalTeacherAssignment.php.before`
- `ExamManagement.php.before`
- `ExamManagementHardeningTest.php.before`
- `Phase7DHistoricalRecalculationAndAnalyticsTest.php.before`
- `RateLimitingAndAbuseProtectionTest.php.before`
- folder `round2/` sampai `round6/` untuk iterasi fixture/security tests.

Workflow backup:
`.github/workflows/backup/tenant-backend-ci.yml.before-phpunit-root-cause-branch-20261123`

Dokumen backup:
- `docs/backup/AGENT_START_HERE.md.before-coordinator-addendum-2026-11-23`
- `docs/backup/REPORT_KOORDINATOR_AUDIT_GITHUB_DAN_ROOT_CAUSE_2026-11-23.md.before-518-pass`
- `docs/backup/ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md.before-master-spec-reconciliation`

## 4. Dokumen koordinasi terbaru

- [Audit, root cause, CI dan backlog](REPORT_KOORDINATOR_AUDIT_GITHUB_DAN_ROOT_CAUSE_2026-11-23.md)
- [Arahan owner instalasi, role, Proktor dan import](ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md)
- [Master spesifikasi Web Admin](MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md)
- [Master spesifikasi Web Guru](MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md)
- [Master spesifikasi Web Siswa](https://github.com/zane0184/cbt_november/blob/work/agent4-web-siswa-audit-20261122/docs/MASTER_SPESIFIKASI_FITUR_WEB_SISWA_CBT_MADRASAH_2026-11-22.md)

## 5. Keputusan produk yang berlaku

- Satu madrasah per instalasi ZIP dan database akademik sendiri; tidak ada shared multi-tenant antar-madrasah.
- Role aktif tepat ADMIN_MADRASAH, GURU, SISWA. Operator dilebur ke Admin; Kamad dibekukan; Proktor capability sementara pada Guru.
- Admin hanya menetapkan/mencabut Proktor untuk ujian terpusat UTS/UAS/UM. Admin tidak mengelola Room/peserta/roster dan tidak melihat monitoring peserta.
- Web Guru Proktor memiliki tugas aktif, jadwal, detail Exam/Room, roster Room, monitoring, catatan kehadiran/insiden, riwayat assignment. Room scope backend wajib.
- Tidak ada ExamSession/SessionRoom/wave/session_number/session lifecycle. Room bukan admission gate.
- Android native dan Web Super Admin ditunda sampai Juni 2027. Super Admin masa depan hanya lisensi/aktivasi/entitlement/pembayaran.
- Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + delapan dimensi profil lulusan satu ekosistem.

## 6. Status backlog GitHub

Open PR:
- [#22 — Remediasi 41 PHPUnit failures](https://github.com/zane0184/cbt_november/pull/22): Draft, base branch eksekusi d6, HEAD 2899baf, mergeable clean; CI #452182 dan #452191 lulus 518/0. Jangan merge otomatis.
- #21 Solo Web CBT: Draft, stale/divergen, base `work/solo-web-cbt-ci-base-20261123`; bukan source aktif d6.
- #19 Web Guru remove XLSX: Draft; rekonsiliasi keputusan penghapusan XLSX soal dengan Master Guru §5.8/Issue #16 sebelum integrasi.
- #18 Web Admin: Draft; ada Room/Proktor/session fields yang tidak boleh digabung apa adanya.
- #17 Web Guru five contracts: Draft desain/audit.
- #13 Web Siswa IndexedDB/submit barrier: Draft; real Laravel E2E dan recovery belum verified.
- PR #1/#3–#7 juga masih open dan bercabang; audit compare sebelum integrasi.

Open issue:
- #20 Admin ADM-01–23: checklist 20 PARTIAL, 2 NOT VERIFIED, 1 BLOCKED.
- #12 Master Admin scope.
- #14 versioned CP/TP/ATP/KBC mapping + immutable snapshot.
- #15 provenance Pembelajaran Mendalam + 8 DPL.
- #9 DOCX parser contract.
- #8 XLSX/PDF parser contract; PDF/OCR deferred.
- #16 XLSX question import wizard, konflik dengan PR #19 dan perlu rekonsiliasi.
- #10 ExamSession contract lama: superseded; jangan implementasikan session lifecycle.

## 7. Langkah berikutnya

1. PR #22 sudah dibuat Draft dari `work/phpunit-41-root-cause-20261123` ke `work/solo-web-cbt-completion-20261123-execution`. Tunggu CI push setelah update dokumentasi/checkpoint selesai; lakukan review dan jangan merge otomatis.
2. Lanjutkan P1 Proktor Mode B: explicit marker UTS/UAS/UM, Exam+Room assignment, overlap/revoke/audit, menu Guru, strict Room scope, negative tests. Jangan bypass guard saat ini.
3. Audit ADM-01–23 satu per satu dan lengkapi gap Admin tanpa menghidupkan portal Operator/Kamad.
4. Lanjutkan Web Guru: import contract (XLSX/DOCX conflict harus direkonsiliasi), grading/history/analytics, curriculum provenance.
5. Integrasikan Web Siswa ke Laravel/Sanctum/PAT/fencing/Roomless Exam admission dan uji offline recovery.
6. Siapkan ZIP VPS hanya setelah fresh install, aaPanel, permissions, queue/scheduler, backup/restore, E2E, security dan clean extraction lulus.
7. Android dan Web Super Admin tetap deferred sampai Juni 2027.

## 8. Status kesiapan

- PHPUnit: **PASS 518/0**.
- Portal Admin/Guru/Siswa E2E: NOT VERIFIED.
- Proktor Exam+Room: NOT VERIFIED.
- DOCX/XLSX real parser: NOT VERIFIED.
- Offline recovery/multi-device UAT: NOT VERIFIED.
- VPS fresh install/deployment: NOT RUN.
- ZIP distribution/clean extraction: NOT RUN.
- UAT madrasah: NOT RUN.

**Jangan menyebut aplikasi siap produksi, siap ujian operasional, atau siap upload ZIP hanya berdasarkan PHPUnit PASS.**
