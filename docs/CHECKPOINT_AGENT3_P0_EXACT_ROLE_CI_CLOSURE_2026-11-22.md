# CHECKPOINT AGENT 3 — P0 EXACT-ROLE
Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`
HEAD saat test fix: `88b5c1354971bfb7b03d715014b40b9026f1fbed`
Status: **P0 OPEN — EVIDENCE INCOMPLETE**

## AUDIT
- Branch HEAD saat test fix diverifikasi identical dengan SHA `88b5c1354971bfb7b03d715014b40b9026f1fbed`.
- Source component:
  - QuestionBank blob `219efc2db8f492452ad95da35ae22eb4849d1e66`
  - ExamManagement blob `d45293ea8cfd01bf6eaeea17ced0579a8d8bc4cc`
- Helper:
  - TeacherPortalAccess blob `8c60fb6db17512d6bbf8845027f69841172e2f60`
  - TeacherAccess blob `766289c5163b6cf367d511c0bf35207ccbc97684`
- Test file blob `747989ed491dc1ead67b8a990434837df47c8af1`, 21 test method.
- User schema hanya menyediakan softDeletes; tidak ada `status`/`is_active`. TeacherProfile juga soft-delete tanpa status aktif. Assignment memiliki status ACTIVE dan date scope.
- QuestionPolicy Agent 2 sudah exact-role. ExamPolicy Agent 2 masih memakai hasRole pada banyak action; GURU+SISWA berpotensi melewati beberapa policy/API. Agent 3 tidak mengubah source Agent 2.
- Issue #8 OPEN (6 komentar; kontrak import masih usulan), #9 OPEN (1 komentar Agent 2; DOCX contract), #10 OPEN (7 komentar; NEEDS DESIGN / COORDINATION; approvals PENDING).

## DESAIN
- Boundary Livewire: exact-role GURU, user persisted/non-soft-deleted, tenant context konsisten, satu TeacherProfile, permission, assignment aktif.
- boot/mount memastikan authorization sebelum render; setiap request hydration/action menjalankan boot kembali.
- Perlu exact-role backend ExamPolicy dari Agent 2 sebelum P0 lintas platform ditutup.

## IMPLEMENTASI
- Hanya test file diubah pada tahap lanjutan ini.
- Commit: `88b5c1354971bfb7b03d715014b40b9026f1fbed`.
- Enam test baru: permission revoke, assignment inactive, TeacherProfile soft-delete, User soft-delete, tenant context change, role GURU revoke setelah mount.
- Backup source test dan manifest dibuat; manifest mencatat 28 snapshot.
- 28/28 backup blob SHA diverifikasi MATCH.
- Tidak ada source component/policy/model/migration/DB/Agent1/2/4 yang diubah.

## PENGUJIAN
- Backup: PASS 28/28.
- Test count: 21 method (pemeriksaan statis saja).
- Run `44995889398` SUCCESS pada SHA lama `a338679...` (16 passed/69 assertions, 80 tabel InnoDB), bukan source test terbaru. Run Agent 2 `45003191589` pada SHA `834a59f...` FAILURE: 18 passed, 2 failed (86 assertions).
- Test fix commit `88b5c135...`: combined status kosong; run exact SHA belum terverifikasi. Workflow connector tidak menyediakan listing push-run atau dispatch action.
- PHP lint exact SHA dan browser QA: NOT VERIFIED/NOT RUN.
- Database produksi tidak disentuh.

## LANGKAH BERIKUTNYA
1. Verifikasi workflow run yang checkout SHA `88b5c135...`; jangan gunakan run lama `a338679...` atau run gagal SHA `834a59f...` sebagai bukti PASS.
2. Minta Agent 2 menyelaraskan exact-role seluruh ExamPolicy dan menambahkan API/policy multi-role regression.
3. Konfirmasi definisi account/profile active karena schema saat ini hanya soft-delete.
4. Ulangi audit dan CI setelah Agent 2 memberikan review/kontrak.
5. Jangan lanjut branding, PDF final, import, merge, atau deployment sebelum P0 lulus.

## GUARDRAILS
Tidak ada merge/PR/rebase/cherry-pick/deployment/migration produksi. Tidak ada perubahan Agent 1/2/4. Status tetap P0 OPEN sampai bukti exact-SHA dan backend policy tersedia.


## ADDENDUM — dua kegagalan CI dan usulan kontrak Agent 2

### Run Agent 2 review
- Run `45003191589`, job `137148981065`, source SHA `834a59f11f9b548c8d7f0fcea68c15cf4bbe501f`.
- PHP lint dan route registration PASS.
- PHPUnit: 18 passed, 2 failed, 86 assertions.
- Failure tenant context: middleware `IdentifyTenant` mereset context dari user authenticated sebelum Livewire request. Test dikoreksi untuk menguji `boot()` komponen yang telah di-mount secara langsung setelah context server-side berubah ke Tenant B; helper mismatch 403 tetap diuji.
- Failure ID lintas tenant: Livewire test harness melempar `ModelNotFoundException` sebelum `assertNotFound()`. Test dikoreksi dengan menangkap exception dan memastikan model Question/Exam tidak ditemukan.
- Tidak ada perubahan middleware, policy, helper, atau komponen runtime.

### Test fix
- Commit `88b5c1354971bfb7b03d715014b40b9026f1fbed`.
- Test blob `747989ed491dc1ead67b8a990434837df47c8af1`.
- 21 method tetap; enam post-mount regressions tetap ada.
- Backup sebelum koreksi: `TeacherPortalFoundationTest.php.before-ci-failure-fixes` dan manifest backup terkait, blob SHA cocok.

### Kontrak import Agent 2
- XLSX/DOCX adapter berbeda → satu `QuestionDraftDTO`.
- Reuse `ImportBatch/ImportBatchRow`, satu lifecycle staging, preview, correction, revalidation, confirm, persistence, audit.
- Tidak ada auto-publish; SHORT_ANSWER BLOCKED; ESSAY manual; tidak ada dependency DOCX yang disetujui.
- Ini usulan desain, bukan approval implementasi.

### Koordinasi dan CI
- Issue #8: OPEN, 6 komentar.
- Issue #9: OPEN, 1 komentar Agent 2.
- Issue #10: OPEN, 7 komentar; Agent 2 NEEDS DESIGN / COORDINATION; Agent 1 menambahkan refresh; approval Agent 2/3/4 tetap PENDING.
- Status check commit `88b5c135...` kosong; workflow run exact SHA belum dapat diverifikasi. Test commit dipush pada path yang memicu push workflow, tetapi konektor tidak menampilkan push-run dan tidak menyediakan manual dispatch.
- P0 tetap OPEN — EVIDENCE INCOMPLETE.
