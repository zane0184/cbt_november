# CHECKPOINT KOORDINATOR P1 — 24 NOVEMBER 2026

Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
HEAD exact saat checkpoint: `686a21acbedc72561d5c39cd4739bb97ff8ad480`  
PR: [#23 — tetap OPEN / DRAFT / NOT MERGED](https://github.com/zane0184/cbt_november/pull/23)  
P0 base: `1c33e5d422175f9bd85d206230271a9fe5a4eecd`

## 0. KEPUTUSAN OWNER YANG BERLAKU

- Web-only sampai Juni 2027: Backend Laravel, Web Admin Madrasah, Web Guru, Web Siswa.
- Android dan Web Super Admin deferred sampai Juni 2027.
- Satu ZIP generik, satu instalasi dan satu database terpisah per madrasah. Tidak ada shared academic database/akses data antar madrasah. Integrasi pusat mendatang hanya license/activation/entitlement/payment.
- `tenant_id` dan `SYSTEM_GLOBAL` lokal tetap dipertahankan sebagai defense-in-depth/legacy. Dilarang mass-removal sebelum audit installer, migrations, FK, query, upgrade, backup/restore.
- Portal aktif hanya ADMIN_MADRASAH, GURU, SISWA. Role/portal Operator dan Kamad tidak digunakan untuk authorization/login baru. Histori legacy tidak dihapus atau dipetakan diam-diam.
- Proktor bukan role/portal; hanya capability sementara pada akun Guru dengan assignment yang disetujui. Marker eligibility ujian pusat UTS/UAS/UM dan kontrak Exam+Room harus eksplisit. Room bukan admission gate. PR #18 yang memakai session/wave tidak boleh digabung apa adanya.
- Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + delapan dimensi profil lulusan merupakan satu ekosistem akademik yang berversi dan punya provenance.
- DOCX adalah format utama import soal. Jangan hidupkan XLSX question import; XLSX/CSV admin untuk import/export Guru/Siswa tetap boleh.
- SHORT_ANSWER tetap BLOCKED; ESSAY manual review.
- Ujian serentak berdasarkan Exam.start_at/end_at dan waktu server. Token otomatis saat publish. Tidak boleh ada ExamSession/SessionRoom/wave sebagai admission.
- Tidak ada production migration/deploy/merge/final ZIP tanpa approval dan gate yang benar.

## 1. AUDIT — POSISI GITHUB AKTUAL

### Branch/PR
- P1 branch: `work/p1-seven-targets-20261124`; HEAD `686a21acbedc72561d5c39cd4739bb97ff8ad480`.
- PR #23 tetap Draft, belum merge.
- P0 base `1c33e5d422175f9bd85d206230271a9fe5a4eecd` tidak diedit.
- P0 exact base memiliki histori CI 518 passed / 0 failed / 2,189 assertions di disposable MySQL 8.0.46/InnoDB. Ini bukan bukti P1.

### Spesifikasi
- Master Admin: `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md` (main, bukan branch P1).
- Master Guru: `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md` (main, bukan branch P1).
- Master Siswa: `docs/MASTER_SPESIFIKASI_FITUR_WEB_SISWA_CBT_MADRASAH_2026-11-22.md` pada branch Agent 4 yang diberikan owner, bukan branch P1.
- Keputusan owner dan addendum instalasi/role telah dibaca pada branch P1.
- Dokumen utama P1 yang sudah ada: checkpoint P1, report P1, matrix ADM-01–23, ZIP artifact verification, addendum owner, decision architecture, handover master. Jangan mengulang laporan awal sebagai hasil terkini; source HEAD dan workflow exact SHA tetap dasar kebenaran.

### Koreksi ADM-06 identitas Guru
- Konflik laporan identitas lama dengan Master Admin telah direkonsiliasi melalui `docs/ADDENDUM_REKONSILIASI_IDENTITAS_GURU_2026-11-24.md`.
- NIP/NUPTK opsional bila Guru tidak memilikinya; NIK hanya bila ada dasar dan perlindungan.
- UserManagement, LoginIdentityResolver, kedua halaman Blade login/user, dan regression tests sudah diubah. Guru menggunakan username sebagai kredensial utama; Siswa NISN; Admin username.
- Backup byte-exact untuk seluruh file existing yang berubah berada di:
  `backend-laravel/backup_before_patch/P1_ADM06_OPTIONAL_ID_LOGIN_USERNAMES_20261124/`.
- Audit risiko tersisa: username masih ditampilkan opsional pada form, meskipun merupakan login utama; database unique constraint untuk identitas opsional belum dibuktikan; masking NIK/NIP/NUPTK, cache/rate-limit identifier redaction, audit log redaction dan import/API writers masih perlu audit.
- Jangan mengubah NIP/NIK menjadi wajib lagi hanya mengikuti laporan historis yang sudah disupersede.

## 2. MATRiks TARGET — STATUS SAAT INI

| Target | Status | Sisa pekerjaan utama |
|---|---|---|
| Backend/security/integrity | PARTIAL / NOT ACCEPTED | Full PHPUnit exact HEAD cancelled; verify retry, transaction/locking and install isolation |
| Web Admin ADM-01–23 | PARTIAL / NOT ACCEPTED | CRUD/detail/reset/session, madrasah profile/branding/kop, import/export Guru/Siswa, academic lifecycle, reports, settings, audit UI, backup/restore |
| Web Guru | PARTIAL / NOT ACCEPTED | DOCX import E2E, immutable snapshot/provenance, media, publish/token, monitoring and grading/result workflows |
| Web Siswa | PARTIAL / NOT ACCEPTED | Integrate latest Agent 4 runner once audited; real Laravel/MySQL E2E, server payload redaction, durable queue/receipt, recovery/offline, browser/mobile UAT |
| Kurmer + KBC integrated ecosystem | FOUNDATION EXISTS / E2E NOT VERIFIED | Source provenance/checksum, CP/TP/ATP/Panca Cinta/PM/DPL mappings through QuestionVersion, Assessment, snapshot, score, historical report |
| Proktor Mode B | BLOCKED | Canonical central-exam marker, Exam+Room placement contract, Room-scoped permission/API/negative tests; do not infer from EXTERNAL |
| aaPanel/VPS acceptance | NOT VERIFIED | Staging fresh install/upgrade/backup-restore, browser/security/load/UAT |
| Distribution ZIP | BUILD CANDIDATE ONLY | Latest build artifact exists; acceptance of app/staging missing; no final ZIP claim |

ADM-01–23 status detail remains in `docs/MATRIKS_ADM_01_23_AUDIT_2026-11-23.md` and `docs/REPORT_P1_AUDIT_DAN_PROGRESS_2026-11-24.md`. Re-audit affected source before patch; do not treat initial partial status as final runtime proof.

## 3. TESTING — EXACT EVIDENCE

### Backend CI exact HEAD
- Run [#45342397865](https://github.com/zane0184/cbt_november/actions/runs/45342397865)
- Source SHA: `686a21acbedc72561d5c39cd4739bb97ff8ad480`
- Job `laravel-mysql`: **CANCELLED**.
- Completed successfully before the cancellation:
  - Initialize MySQL 8 container.
  - Checkout.
  - PHP 8.3.
  - isolated local-install DB identity fail-closed.
  - Composer lock/platform validation and locked dependency install.
  - Laravel runtime setup and explicit package discovery.
  - fresh disposable installer E2E DB.
  - focused Web login and tenant identity smoke.
- Full PHPUnit step: marked cancelled. No final passed/failed/assertion count is available.
- This is NOT a PHPUnit failure result, and NOT a full-suite PASS.

### Older CI outcomes
- Run #45341915156 (older SHA `6fbd9e0...`) failed two stale login-label assertions; do not treat as current source result.
- Run #45342320691 (SHA `d2e6c088...`) cancelled during MySQL service initialization, before checkout/test.
- Run #45342213250 (SHA `b78dd65...`) cancelled after a later push.
- Build workflow latest exact HEAD:
  [#45342397839](https://github.com/zane0184/cbt_november/actions/runs/45342397839) — SUCCESS, artifact #14897500076, size 19,763,837 bytes, digest `sha256:374b67d7db5a3b1b4634dd47f9fbf2c7ec6c00b202e5f9ea7866e3f1c7ad43ce`.
- ZIP build is package assembly only. It is not app acceptance, staging deployment, or final distribution approval.
- Previous artifact #14847312148 exact SHA `535a3af...` has an earlier verified archive report; not latest source, not a final package.

## 4. NEXT WORK — SEQUENCED, NO REPEAT PATCHES

1. **Backend test gate:** inspect CI #45342397865 cancellation reason via workflow/job summary; rerun backend CI on current branch only if appropriate. Do not change source for a runner cancellation. Obtain full PHPUnit exact HEAD count.
2. **ADM-06 completion:** audit user account CRUD and username invariant; preserve current optional IDs. Add explicit username generation/display or require a username in UI with test. Audit tenant-local uniqueness DB constraints and import/API paths before adding any migration proposal. Profile masking and sensitive identifier redaction required.
3. **ADM-07/08:** student archive/transfer/graduation/rombel history; password reset and session revoke; audit-sensitive action flow. Test with database disposable.
4. **ADM-13/15/16/17:** academic Year/Semester lifecycle, class/rombel/student placement, subjects, teacher assignments; do not repeat existing FK constraints; inspect exact source and schema before patch.
5. **ADM-02–05 / 09–12 / 21–23:** madrasah profile, logo, branding, letterhead, XLSX/CSV Guru/Siswa import/export, reports/cetak, Audit UI, general settings and backup/restore. Build end-to-end with Admin permissions and audit; don't invent screens only.
6. **Guru:** audit current question bank, DOCX import, media, mapping, exam builder, publish/token, scoring and result/analytics. Do not rebuild existing features. Link to backend contract and Siswa runner.
7. **Siswa:** refresh PR #13/Agent 4 source and CI, compare per file with P1 branch; cherry-pick only exact reviewed gaps. Do not recreate IndexedDB runner/recovery already present.
8. **Curriculum:** provenance/checksum verification and immutable chain through reporting; no synthetic official CP or dimension content.
9. **Proktor:** blocked until existing owner-approved marker and Exam+Room contract are present; remain fail-closed.
10. **Staging/ZIP:** only after feature acceptance: aaPanel upload/extract flow, separate per-school DB, backup/restore, browser/security/load/UAT. Verify latest full ZIP and extraction only when all application checks pass.

## 5. SAFETY / CHANGE CONTROL
- P0 base and PR #22 untouched.
- PR #23 stays Draft; no merge.
- No production migration, SQL mutation, deployment, or final ZIP release.
- Every existing file patch requires byte-exact GitHub backup and backup manifest before edit.
- Each completed phase adds its own GitHub report/checkpoint; never overwrite historical reports.
- Do not claim VERIFIED/READY without source + focused/full tests + evidence.

**Current decision: P1 is active; ADM-06 identity subtask is patched but full-suite NOT VERIFIED. All targets remain in progress; ZIP is only a build candidate, not ready for distribution.**
