# CHECKPOINT WEB GURU — P0 CI RECOVERY

Tanggal: 23 November 2026  
Agent: Agent 3 — Web Guru  
Repository: `zane0184/cbt_november`  
Branch: `work/web-guru-phase1-audit-implementation-20261122`  
HEAD checkpoint: `0635aecfd8d8a94c6a73dc3fcdc7223d1f09c793`  
Status: **P0 BLOCKED — SOURCE AUDITED, RUNTIME TEST NOT VERIFIED**

## 1. Posisi source

- Source ExamManagement: `453879ef8ad42a7dbea9cd6a78c5eb7c8face219`.
- Regression test Exam Builder: `0271a7836c5fefe76b97e9bca70ded23ae35619e`.
- TeacherPortalFoundationTest: `c3ba6a2f938990d1e9cbd2cb6503c3dda51f73f5`.
- TeacherPortalAccess: `8c60fb6db17512d6bbf8845027f69841172e2f60`.
- ExamSnapshotService: `d0bfcd70b2777a3884c7f92b7c256f05492645ec`.

HEAD `0635aec...` hanya menambahkan laporan audit P0; tidak mengubah source runtime, test, policy, service, workflow, migration, atau database.

## 2. Laporan terbaru

- `docs/WEB_GURU_P0_AUDIT_CI_RECOVERY_2026-11-23.md`
- Commit laporan: `0635aecfd8d8a94c6a73dc3fcdc7223d1f09c793`.

## 3. Pekerjaan yang sudah selesai

1. Refresh branch, HEAD, commit, checkpoint, master spec, dan source terkait.
2. Verifikasi Phase 4B source/test tetap ada; backup pra-patch 2/2 blob SHA cocok.
3. Buat matriks fitur Web Guru berbasis source aktual.
4. Audit post-mount Livewire: hanya tenant reassignment yang sudah diuji melalui HTTP update nyata; revokasi permission/assignment/profile/user/role masih memakai Livewire test harness.
5. Audit dependency Agent 2: policy aktif masih memuat hasRole/KAMAD; branch Agent 2 P0 belum terintegrasi dan tidak boleh diambil sebagian.
6. Rerun job CI exact test SHA `616dc82f006e0c8327f5738857cd33d53ec9f071`; attempt 2 tetap gagal sebelum steps, log tidak tersedia (`BlobNotFound`).

## 4. Belum selesai / blocker

- PHPUnit, PHP lint, route:list, dan MySQL 8/InnoDB pada exact SHA belum terbukti berjalan.
- HTTP Livewire POST untuk permission revoke, assignment revoke, TeacherProfile soft-delete, User soft-delete, dan role revoke setelah mount belum ada.
- Agent 2 belum memberi approval teknis yang dapat diverifikasi pada Issue #10 untuk exact-role/policy, snapshot private/public, dan lock order.
- `ExamSnapshotService` belum terbukti menegakkan Question + QuestionVersion PUBLISHED untuk semua caller.
- Browser E2E, responsive/RTL QA, dan UAT belum dijalankan.
- Monitoring, Penilaian, Hasil, Analytics, Import, Media, dan Proktor tetap PARTIAL/BLOCKED sesuai dependency.

## 5. Aturan yang dipertahankan

- Fokus eksklusif Web Guru.
- Jangan mengubah `main`.
- Jangan merge/rebase/cherry-pick.
- Jangan mengubah file ownership Agent 1/2/4.
- Jangan migration atau menyentuh database produksi.
- Jangan mengaktifkan menu yang dependency backend/policy/test-nya belum siap.
- Jangan mengklaim PASS tanpa log exact SHA.

## 6. Langkah berikutnya

1. Pulihkan akses runner/Actions atau jalankan workflow manual dari GitHub Actions pada branch ini; pastikan checkout exact SHA dan simpan steps/log/artifact. Kegagalan job saat ini tidak cukup untuk menyimpulkan penyebabnya.
2. Setelah runner berjalan, jalankan PHP lint, route:list, seluruh PHPUnit filter Web Guru, dan verifikasi MySQL 8/InnoDB disposable.
3. Tambahkan HTTP Livewire post-mount revocation tests pada file test Agent 3 setelah CI runtime tersedia.
4. Koordinasikan dengan Agent 2 untuk approval exact-role, policy, snapshot PUBLISHED, dan lock order; jangan ubah file mereka.
5. Setelah P0 lulus, pilih satu gap Web Guru dengan ownership Agent 3 dan acceptance test yang jelas.

**Checkpoint ini menggantikan asumsi posisi sebelumnya. Source aktual tetap menjadi dasar kebenaran; Phase 4B tidak diulang.**
