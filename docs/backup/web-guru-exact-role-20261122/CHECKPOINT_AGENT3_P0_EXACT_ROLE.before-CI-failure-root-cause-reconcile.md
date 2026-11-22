# CHECKPOINT AGENT 3 — P0 EXACT-ROLE
Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`
HEAD saat checkpoint: `c9d8f7e723be64467d759b921ebc3346fb4dfe46`
Status: **P0 OPEN — EVIDENCE INCOMPLETE**

## AUDIT
- Branch HEAD diverifikasi identical dengan SHA `c9d8f7e723be64467d759b921ebc3346fb4dfe46`.
- Source component:
  - QuestionBank blob `219efc2db8f492452ad95da35ae22eb4849d1e66`
  - ExamManagement blob `d45293ea8cfd01bf6eaeea17ced0579a8d8bc4cc`
- Helper:
  - TeacherPortalAccess blob `8c60fb6db17512d6bbf8845027f69841172e2f60`
  - TeacherAccess blob `766289c5163b6cf367d511c0bf35207ccbc97684`
- Test file blob `0a48cede38639093e5bd53e5b99ae0526babd8c9`, 21 test method.
- User schema hanya menyediakan softDeletes; tidak ada `status`/`is_active`. TeacherProfile juga soft-delete tanpa status aktif. Assignment memiliki status ACTIVE dan date scope.
- QuestionPolicy Agent 2 sudah exact-role. ExamPolicy Agent 2 masih memakai hasRole pada banyak action; GURU+SISWA berpotensi melewati beberapa policy/API. Agent 3 tidak mengubah source Agent 2.
- Issue #8 OPEN (3 komentar, belum ada approval import), #9 OPEN (0 komentar), #10 OPEN (4 komentar, NEEDS DESIGN / COORDINATION).

## DESAIN
- Boundary Livewire: exact-role GURU, user persisted/non-soft-deleted, tenant context konsisten, satu TeacherProfile, permission, assignment aktif.
- boot/mount memastikan authorization sebelum render; setiap request hydration/action menjalankan boot kembali.
- Perlu exact-role backend ExamPolicy dari Agent 2 sebelum P0 lintas platform ditutup.

## IMPLEMENTASI
- Hanya test file diubah pada tahap lanjutan ini.
- Commit: `c9d8f7e723be64467d759b921ebc3346fb4dfe46`.
- Enam test baru: permission revoke, assignment inactive, TeacherProfile soft-delete, User soft-delete, tenant context change, role GURU revoke setelah mount.
- Backup source test dan manifest dibuat; manifest mencatat 18 snapshot.
- 18/18 backup blob SHA diverifikasi MATCH.
- Tidak ada source component/policy/model/migration/DB/Agent1/2/4 yang diubah.

## PENGUJIAN
- Backup: PASS 18/18.
- Test count: 21 method (pemeriksaan statis saja).
- CI lama run `44995889398` SUCCESS, tetapi checkout SHA `a33867928711b456d2414a675bef1a929861dee8`, bukan HEAD. Hasil: PHP lint/route PASS, PHPUnit 16 passed/69 assertions, MySQL 8 disposable, 80 tabel InnoDB, cleanup PASS.
- HEAD `c9d8f7e...`: combined status kosong; run exact SHA belum terverifikasi. Workflow connector tidak menyediakan listing push-run atau dispatch action.
- PHP lint exact SHA dan browser QA: NOT VERIFIED/NOT RUN.
- Database produksi tidak disentuh.

## LANGKAH BERIKUTNYA
1. Verifikasi workflow run yang checkout SHA `c9d8f7e...`; jangan gunakan run lama `a338679...` sebagai bukti.
2. Minta Agent 2 menyelaraskan exact-role seluruh ExamPolicy dan menambahkan API/policy multi-role regression.
3. Konfirmasi definisi account/profile active karena schema saat ini hanya soft-delete.
4. Ulangi audit dan CI setelah Agent 2 memberikan review/kontrak.
5. Jangan lanjut branding, PDF final, import, merge, atau deployment sebelum P0 lulus.

## GUARDRAILS
Tidak ada merge/PR/rebase/cherry-pick/deployment/migration produksi. Tidak ada perubahan Agent 1/2/4. Status tetap P0 OPEN sampai bukti exact-SHA dan backend policy tersedia.
