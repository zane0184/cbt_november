# Agent 2 — P1 Security Checkpoint

Tanggal: 22 November 2026
Branch kerja: `work/stage4d-account-lifecycle-20261122`

## A. AUDIT

### P0 result
- CI run #374, ID `44996834042`, code SHA `4dada4077e9dcd7c8ae767da3569cc58f02e6cc8`: SUCCESS.
- PHP lint, Guru/Siswa regression, QuestionImportTest, WebAdminAuthTest, StudentWebLoginTest, Admin CBT policy/Livewire boundary, tenant isolation, temporary Proktor assignment, dan MySQL 8 Stage 4C concurrency lulus.
- Stage 4B tests dilewati sesuai ownership Agent 1; bukan kegagalan.
- P0 fixture correction dan backup tercatat di `docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md`.

### P1 account lifecycle
- Web session epoch middleware sudah ada dan deactivation/reactivation mencabut Sanctum tokens, merotasi remember token, dan menaikkan auth_version.
- Audit menemukan Sanctum stateful cookie API belum menerapkan epoch middleware.
- Patch route/middleware/test dibuat pada commit `cf0cfc215af7ef37e3f75f74db336f4e866f8510`.
- Run #375 (`44997533553`) menemukan bug: middleware membaca session pada request stateless tanpa session store.
- Patch fail-safe dibuat pada commit `d759cfce51c97c999697fb069dcfa5467ebf1bd5`: skip jika `hasSession() === false`, dan test menetapkan `cbt.test` sebagai stateful domain.
- Run #378 (`44997980279`) membuktikan QuestionImportTest 4/4 PASS (12 assertions), WebAdminAuthTest 1/1 PASS (25 assertions), tetapi StudentWebLoginTest 10 PASS/1 FAIL pada login Admin terpisah.
- Audit test harness menemukan helper browser mereset guard instances tetapi tidak mengembalikan default guard.
- Patch helper dibuat pada commit `e91d5b20155a3d660a8a24668a9cf8c535327cc4`, dengan backup terverifikasi.
- Run #379 (`44998131077`) masih berjalan pada source sebelum patch helper; belum menjadi bukti untuk commit `e91d5b`.

### P1 security gaps lain
1. `ExamPolicy`, `AssessmentPolicy`, `ExamAttemptPolicy`, dan `ReportPolicy` masih menggunakan `hasRole()` pada beberapa jalur, sehingga identitas multi-role perlu ditolak secara eksplisit.
2. `MediaController` memakai pemeriksaan role manual yang belum konsisten dengan exact-role boundary.
3. Livewire `QuestionBank` dan `ExamManagement` dimiliki Agent 3; mount/boot memeriksa Guru dan bukan Admin, tetapi belum mensyaratkan tepat satu role. Jangan mengubah file Agent 3 tanpa koordinasi.
4. Direct API regression untuk Admin pada endpoint questions/assessments/exams/media belum lengkap; test saat ini terutama membuktikan policy dan Livewire mount.
5. Controller master akademik (GradeLevel, Element, LearningObjective, LearningPath, LearningOutcome, Material) perlu audit authorization; beberapa jalur belum memanggil policy. `StoreLearningOutcomeRequest` memakai permission `learning_outcome.manage` yang tidak didefinisikan bootstrapper, sementara permission yang tersedia adalah `curriculum.manage` / `curriculum.view`.
6. Tidak ditemukan route/controller/service/migration password reset tenant-scoped pada branch. Default Laravel broker tidak boleh diaktifkan tanpa desain tenant boundary.
7. Role KAMAD masih terdapat pada policy/permission matrix; jangan menghapus atau mengubah tanpa keputusan matriks role final.

### Source continuity — Device Lease
- Branch Agent 2 hanya memiliki schema/migration lease; belum ada `DeviceLeaseService`, controller lease, atau enforcement pada save/heartbeat.
- Repository branch tidak memiliki project Android.
- Workspace `/mnt/data/cbt_stage5p_work` tidak ditemukan.
- Pencarian seluruh branch GitHub dan Project Library hanya menemukan laporan/audit, bukan raw source Tahap 5P.
- Jangan merekonstruksi patch Stage 5P dari laporan saja. Fencing end-to-end tetap BLOCKED.

## B. DESAIN

1. Stateful Sanctum API: jalankan `EnsureAccountSessionVersion` hanya bila request memiliki session; bearer/stateless API tidak boleh memerlukan session store.
2. Role boundary: gunakan helper exact-role yang membaca role dari database; operasi Guru mensyaratkan tepat satu role GURU, operasi Admin tepat satu ADMIN_MADRASAH. Pertahankan penolakan eksplisit jika identitas memiliki role Admin pada CBT.
3. Admin CBT: tambah direct API regression untuk questions, assessments, exams, dan media; koordinasikan mount/boot exact-role dengan Agent 3.
4. Curriculum API: gunakan permission yang sudah disepakati (`curriculum.view`, `curriculum.manage`) untuk master data; jangan menciptakan permission baru tanpa kontrak.
5. Password reset: rancangan yang direkomendasikan adalah token sekali pakai tenant-scoped, hash-only, TTL, single-use, transaksi/row lock, revoke session/token, rotate remember token, audit tanpa secret. Channel pengiriman belum diputuskan.
6. Agent 1: jangan ubah ExamSession/room/proctor files; sinkronkan kontrak satu Exam–satu ExamSession dan pertahankan histori actor soft-deleted.
7. Agent 3: backend import contract Excel/DOCX harus memakai draft/preview/correction/confirm; SHORT_ANSWER tetap BLOCKED sampai version–snapshot–scoring terbukti.

## C. IMPLEMENTASI

File yang sudah diubah pada fase ini:
- P0 fixture/test correction (commit `4dada4077e9d`).
- `EnsureAccountSessionVersion.php`, `routes/api.php`, `StudentWebLoginTest.php` (commit `cf0cfc215af7`).
- Guard `hasSession()` dan stateful-domain fixture (commit `d759cfce51c9`).
- `IndependentBrowserClient.php` default guard reset (commit `e91d5b20155a`).

Backup sebelum setiap perubahan tercatat dalam:
`docs/backup/stage4d-account-lifecycle-2026-11-22/MANIFEST.md`.

Tidak ada migration yang dijalankan, tidak ada database produksi, tidak ada deployment, tidak ada perubahan ZIP baseline, dan tidak ada build Gradle/Android Studio.

## D. PENGUJIAN

| Run | SHA yang diuji | Hasil | Catatan |
|---|---|---|---|
| #374 / `44996834042` | `4dada4077e9d` | PASS | P0 regression dan MySQL 8 concurrency |
| #375 / `44997533553` | `cf0cfc215af7` | FAIL | Stateless API request tanpa session store |
| #376 / `44997565595` | `cb3b85397ba1` | FAIL | Mengulang source sebelum no-session guard |
| #378 / `44997980279` | `c18acf591255` | FAIL | QuestionImport PASS; StudentWebLogin gagal pada browser Admin setelah stateful API |
| #379 / `44998131077` | `a1eb70f193e8` | IN PROGRESS | Source sebelum default-guard reset helper |
| #380 / `44998658805` | `e91d5b20155a` | CANCELLED | Tidak memulai job karena concurrency workflow |

## E. STATUS DAN NEXT STEP

- DONE: P0 CI gate.
- IN PROGRESS: P1 stateful Sanctum epoch; menunggu workflow yang menguji commit `e91d5b`.
- NEXT: exact-role policies + MediaController + direct API boundary tests; kemudian curriculum API authorization.
- BLOCKED: tenant-scoped password reset menunggu keputusan channel (email terverifikasi, admin-assisted code, atau keduanya).
- BLOCKED: integrasi Device Lease/Fencing end-to-end menunggu raw source Tahap 5P dan Android project.
- BLOCKED: produksi/staging VPS belum diverifikasi; tidak ada klaim siap produksi.

Checkpoint ini menggambarkan source remote dan bukti CI yang diperiksa, bukan klaim penyelesaian seluruh P1.
