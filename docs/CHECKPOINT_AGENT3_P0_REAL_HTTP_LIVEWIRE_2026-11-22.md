# Checkpoint Agent 3 — P0 Real HTTP Livewire / Next Gate

Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`
HEAD sebelum checkpoint ini: `eb0bede28fbf6367c81d5491314784737f421158`
Test source commit: `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`
Test blob: `4f1bb48b390b03b2602df6348fd3b9788581e6c9`
Laporan: `docs/AGENT3_P0_REAL_HTTP_LIVEWIRE_HARDENING_2026-11-22.md`
Laporan commit: `eb0bede28fbf6367c81d5491314784737f421158`

Status: **P0 OPEN — TENANT/SECURITY TEST INCOMPLETE**

## AUDIT
- Branch Agent 3 telah direfresh; source test terbaru berada pada commit `6334dc9...`.
- Livewire `v3.8.11` test harness menonaktifkan middleware untuk initial/subsequent request; test sebelumnya tidak cukup membuktikan HTTP update pipeline.
- Source test sekarang menggunakan GET route asli, mengekstrak snapshot, lalu POST ke Livewire update endpoint.
- Test boundary membangun tenant context dari authenticated user ketika context kosong dan menolak persisted tenant reassignment.
- Backup lama 34/34 cocok; backup test baru byte-exact.
- Agent 2 ExamPolicy masih memakai role check non-exact pada banyak capability dan masih memiliki jalur KAMAD. Keputusan koordinator Issue #10 membekukan KAMAD dari CBT.
- QuestionPolicy Agent 2 exact-role berbeda dari QuestionPolicy lama pada branch Agent 3; rekonsiliasi lintas-owner masih diperlukan.
- Issue #8 OPEN (7 komentar), #9 OPEN (2 komentar), #10 OPEN (15 komentar); approval owner lintas-agent belum terverifikasi.
- Run #41 pada SHA `88b5c135...` gagal di tingkat workflow/job dengan steps/log unavailable; PHPUnit execution UNKNOWN. Combined status SHA `6334dc9...` kosong.

## DESAIN
- Real HTTP Livewire update menggunakan snapshot dari response halaman asli.
- Tidak mengubah middleware produksi atau mendaftarkan middleware persistent secara spekulatif.
- Exact-role backend Agent 2 harus selesai sebelum Browser QA.
- Import XLSX/DOCX tetap desain sampai approval Agent 2.
- Browser QA harus memakai non-production environment, browser/version, viewport, akun/role, dan bukti tiap skenario.

## IMPLEMENTASI
- File test: `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`.
- Commit: `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`.
- Backup: `docs/backup/web-guru-exact-role-20261122/TeacherPortalFoundationTest.php.before-real-http-livewire-20261122`.
- Backup blob: `0b273618677c791d82645fa373be57aa2d961643` (identik dengan source sebelum patch).
- Backup addendum: `docs/backup/web-guru-exact-role-20261122/REAL_HTTP_LIVEWIRE_BACKUP_ADDENDUM.md`.
- Tidak ada source Agent 1/2/4, runtime production, middleware, policy, migration/schema, Composer, workflow, atau database yang diubah.

## PENGUJIAN
- Audit GitHub/blob/branch: PASS sebagai pemeriksaan read-only.
- Backup manifest lama: 34/34 PASS.
- Backup test terbaru: blob SHA identik.
- Test methods: 22 terverifikasi statis.
- PHP lint: NOT RUN.
- PHPUnit: NOT RUN.
- MySQL 8/InnoDB: NOT RUN.
- CI exact-SHA terbaru: UNKNOWN / belum ada bukti.
- Browser QA: BLOCKED.
- Database produksi: NOT TOUCHED.

## NEXT ACTION
1. Tunggu izin eksplisit koordinator/repository owner untuk workflow exact SHA `6334dc9...`, atau minta pemilik memeriksa Actions metadata/log pada UI/API GitHub.
2. Dapatkan PHP lint, route registration, PHPUnit PASS, disposable MySQL/InnoDB dan cleanup pada exact SHA.
3. Agent 2 menyelesaikan ExamPolicy/API/service exact-role dan KAMAD freeze.
4. Rekonsiliasi QuestionPolicy saat integrasi resmi.
5. Setelah seluruh gate backend/CI lulus, lakukan Browser QA Web Guru.
6. Jangan menutup P0, mengaktifkan import, merge/rebase/cherry-pick, deployment, migration produksi, atau mengklaim siap produksi sebelum bukti tersedia.

**P0 tetap OPEN.** Tidak ada pekerjaan Android atau Super Admin pada tahap ini.
