# LAPORAN P0 — TRIAGE FULL PHPUNIT & PERBAIKAN ISOLASI TEST
Tanggal: 23 November 2026
Branch: `work/solo-web-cbt-completion-20261123-execution`
Baseline runtime yang diperiksa: `dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4`
Commit perbaikan pertama: `116b1b2d02f27e33a0ba282e98a9be8427345aa3`
Commit workflow CI: `86aebaa0dbdb0f6f5182c6097db5e2c04ca16d4d`
CI terbaru: [Tenant Backend CI #45160016881](https://github.com/zane0184/cbt_november/actions/runs/45160016881)

> Status: IN PROGRESS. Belum ada klaim full PHPUnit lulus. Tidak ada perubahan DB produksi, migration produksi, deployment, merge, atau perubahan runtime aplikasi.

## AUDIT

### Bukti CI awal
Run [#45156925960](https://github.com/zane0184/cbt_november/actions/runs/45156925960), job `laravel-mysql`, source runtime SHA `dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4`:
- Disposable MySQL 8.0.46 / InnoDB identity check: PASS.
- Composer validation/install, package discovery, installer DB preparation: PASS.
- Focused Web login/tenant smoke: 27 passed (90 assertions).
- Full PHPUnit: **160 failed, 357 passed (1,475 assertions)**, duration 96.90s.

### Root cause terkonfirmasi — kebocoran fixture antar-test
Bukti log menampilkan:
```
AUTH_TEST_TENANTS=[{"code":"A2C"},{"code":"DEMO"}]
AUTH_TEST_INSTALLATIONS=[{"tenant_id":"...DEMO..."}]
```
`AttemptTransactionConcurrencyTest` sengaja tidak memakai RefreshDatabase agar dua koneksi MySQL independen dapat melihat fixture committed dan menguji row-lock. Test membuat tenant `A2C` dan record akademik, tetapi tidak menghapusnya setelah selesai. Akibatnya test berikutnya melihat dua tenant madrasah lokal; `LoginIdentityResolver` secara benar menolak instalasi yang mempunyai lebih dari satu tenant lokal, sehingga AuthTest login menerima 422. Ini bukan alasan untuk melonggarkan resolver.

### Kategori kegagalan yang terlihat dari log
1. **Test isolation:** tenant `A2C` dari test dua koneksi tertinggal dan membuat login lokal ambigu.
2. **Fixture assignment tidak kanonis:** `ExamSnapshotTest` membuat TeacherSubjectAssignment, tetapi Assessment tidak memuat seluruh metadata relasi assignment/registrasi/version/owner yang diwajibkan `TeacherAccess::canManageAssessment`. Setup test sendiri gagal pada assertion eksplisit “active matching teacher assignment”.
3. **Kontrak lama bertentangan dengan keputusan owner:** `ExamSessionHardeningTest` mengharapkan endpoint/lifecycle ExamSession operasional (beberapa route memberi 404), sedangkan spesifikasi Admin terbaru menetapkan ujian serentak berbasis Exam.start_at/end_at/token/server time tanpa ExamSession/SessionRoom sebagai lifecycle admission. Jangan memulihkan route lama untuk membuat test hijau.
4. **Fixture/otorisasi Guru lama:** beberapa test Exam Builder, monitoring, grading dan authoring mengharapkan akses tanpa semua relasi assignment/curriculum/ownership kanonis. Jangan menurunkan policy; perbaiki fixture atau ubah assertion hanya setelah dibandingkan kontrak produk.
5. **Role lama / kontrak UI lama:** sebagian AdminUiVerificationTest/Livewire practice workflow memakai asumsi komponen/field lama. Perlu audit source dan spec Guru/Admin sebelum memperbaiki.
6. **CP legacy write contract:** test mengharapkan 405 untuk route write CP legacy, sedangkan request mendapat 404 (route tidak terdaftar). 404 aman secara default; perbarui test hanya setelah audit route/read contract dan pastikan tidak ada jalur write lain yang terbuka.
7. **Attempt status/replay:** SubmitLockingTest memiliki beberapa ekspektasi 200/422 tetapi mendapat 409. Perlu audit status/fence/receipt dan fixture per kasus; jangan mengubah 409 tanpa bukti kontrak.

### Implementasi perbaikan pertama
File diubah:
- `backend-laravel/tests/Feature/AttemptTransactionConcurrencyTest.php`

Perubahan:
- Setelah tes dua koneksi menyelesaikan pemeriksaan lock submit dan expiry, fixture committed dibersihkan secara eksplisit dalam urutan FK: exam_attempts → exam_participants → exams → assessments → academic_years → users → tenants.
- Tidak mengubah perilaku lock, service CBT, policy, atau production schema.
- Tujuan: mencegah test concurrency meninggalkan tenant kedua yang mencemari test berikutnya.

Backup byte-exact:
- Path: `backend-laravel/docs/backup/solo-web-cbt-2026-11-23/AttemptTransactionConcurrencyTest.php.before-test-isolation-fix`
- Original blob SHA: `fd3e4da3aea52baff43704655812161b4b829ec6`
- Backup blob SHA: `fd3e4da3aea52baff43704655812161b4b829ec6`
- Verifikasi konten identik: PASS.

CI workflow:
- Menambahkan branch eksekusi ini ke trigger push workflow Tenant Backend CI.
- Backup workflow byte-exact: `.github/workflows/backup/tenant-backend-ci.yml.before-execution-branch-trigger`
- Original/backup blob SHA: `588bb4b5253d0033d793254b8db0a9a65951f90f`
- Workflow change commit: `86aebaa0dbdb0f6f5182c6097db5e2c04ca16d4d`.

## DESAIN / KEPUTUSAN PENANGANAN
- Resolver instalasi tetap menolak database dengan lebih dari satu tenant madrasah lokal. SYSTEM_GLOBAL bukan madrasah kedua.
- Tes multi-connection harus tetap memakai dua koneksi MySQL independen dan benar-benar menguji blocking/serialization; fixture dibersihkan setelahnya.
- Tes yang mengharapkan ExamSession/SessionRoom operasional tidak boleh dipakai untuk memaksa produk kembali ke desain lama. Ganti dengan tes admission berbasis Exam start/end/token/server time, Attempt, lease/fencing, idempotency dan recovery.
- Guru harus memiliki tepat satu role GURU, profile, assignment aktif yang cocok, dan relasi curriculum/assessment kanonis.
- Tidak ada perubahan policy untuk mengizinkan Guru tanpa assignment.

## PENGUJIAN
- Patch cleanup belum dinyatakan PASS sebelum CI pasca-patch selesai.
- CI pasca-patch berjalan pada run #45160016881, exact commit `86aebaa0dbdb0f6f5182c6097db5e2c04ca16d4d`. Status saat laporan dibuat: IN_PROGRESS.
- Full PHPUnit baseline masih FAIL sebagaimana angka di atas.
- MySQL concurrency baseline test: PASS pada run baseline, tetapi test tersebut menyebabkan fixture leakage; perbaikan cleanup menunggu hasil CI baru.

## CHECKPOINT / LANGKAH BERIKUTNYA
1. Ambil hasil run #45160016881 dan konfirmasi AuthTest tidak lagi tercemar tenant A2C.
2. Audit dan perbaiki fixture ExamSnapshotTest berdasarkan seluruh metadata kanonis assessment/assignment; backup test file byte-exact terlebih dahulu.
3. Ganti kontrak test ExamSession lama dengan contract ujian serentak dan Attempt lifecycle kanonis; jangan menambah kembali endpoint operasional ExamSession.
4. Kelompokkan sisa kegagalan berdasarkan root cause, bukan mengubah status code secara massal.
5. Setelah full PHPUnit PASS, lanjut audit jalur API master akademik, matriks ADM-01–ADM-23, snapshot laporan historis, integrasi tiga portal, fresh install, backup/restore dan UAT.

## BATAS
Tidak ada production migration, deployment VPS, perubahan database produksi, merge PR, atau ZIP distribusi pada checkpoint ini.


---

# ADDENDUM PEMUTAKHIRAN TRIAGE — 23 NOVEMBER 2026 (HANDOVER AGENT BARU)

**Addendum ini menggantikan angka/status CI yang tercantum pada bagian awal laporan lama.** Laporan awal tetap dipertahankan sebagai catatan historis.

## AUDIT TERBARU

- Branch aktif: `work/solo-web-cbt-completion-20261123-execution`.
- Exact HEAD yang diuji: `b31d7a2713383ddcc1282317cdb295d6992d5dce`.
- CI: [run #45168433247](https://github.com/zane0184/cbt_november/actions/runs/45168433247), job `137671393026`.
- Environment: GitHub Actions disposable MySQL **8.0.46**.
- Focused login/tenant smoke: **27 passed (90 assertions)**.
- Full PHPUnit: **92 failed, 426 passed (1,723 assertions)**.
- Baseline run #45156925960 pada SHA `dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4`: **160 failed, 357 passed (1,475 assertions)**.
- Perubahan dari baseline ke CI terakhir: kegagalan berkurang **68 test**; jumlah pass bertambah **69 test**. Suite masih FAIL; tidak boleh disebut selesai.

## RINCIAN KEGAGALAN TERAKHIR BERDASARKAN LOG

| Kelompok | Gagal | Temuan log / akar masalah yang perlu ditindaklanjuti |
|---|---:|---|
| ExamSnapshotTest | 15 | Setup menolak fixture karena tidak memiliki assignment Guru aktif yang cocok. Test ini belum menggunakan helper kanonis baru. |
| Phase7CReopenTest | 12 | Banyak request mendapat 403, satu skenario justru menerima 200 ketika diharapkan ditolak. Audit role tunggal, permission, route, policy, dan fixture; jangan melemahkan otorisasi. |
| QuestionBankTest | 8 | Test lama mengakses properti Livewire Admin yang tidak ada (questionType/selectedOutcomeId/correctOption(s)); Admin tidak boleh diberi kembali hak authoring soal. Audit apakah test harus dipindah ke Guru atau diubah menjadi negative access test. |
| Security/AnswerKeyLeakageTest | 7 | TeacherSubjectAssignment tidak ditemukan; fixture tidak sesuai assignment yang diperlukan. |
| RateLimitingAndAbuseProtectionTest | 6 | Beberapa request 401; autentikasi/token/lease fixture perlu diperbaiki berdasarkan middleware yang sebenarnya. |
| ExamManagementHardeningTest | 5 | Properti Livewire lama hilang; satu test menerima 200 saat mengharapkan 403; authoring exam form memakai contract lama. |
| ScoringHardeningTest | 5 | Perbedaan state FINALIZED/AUTO_SCORED, response 403, permission reopen Admin, dan nilai hasil recalculate. Audit state machine dan ownership; jangan sekadar mengganti expected status. |
| ExamListContractTest | 4 | **Regresi dari patch fixture terbaru: variabel `$assignment` dipakai tetapi belum didefinisikan** di setup. Backup tersedia. Perbaiki sebelum melanjutkan kelompok lain. |
| ProctorAssignmentSecurityTest | 4 | Dua test membuat model tenant-scoped tanpa TenantContext; dua test masih mengharapkan exception lama, sementara service memblokir assignment karena eligibility ujian pusat belum kanonis. Jangan membuka fitur Proktor sebelum marker kanonis dan keputusan owner. |
| ResultWorkflowTest | 4 | Invalid Livewire snapshot structure dan satu null scoring_status; audit cara membuat Livewire component test serta state scoring. |
| ExamVerificationTest | 3 | FK RESTRICT pada device_leases saat cleanup; dua response 403. Cleanup harus menghapus lease sebelum Attempt; periksa permission/fixture untuk attempt start dan exam read. |
| TeacherPracticeWorkflowTest | 3 | Properti legacy question-bank dan null model/ID; perlu sesuaikan fixture dan komponen Guru aktual. |
| AdminUiVerificationTest | 2 | Properti selectedAssignmentId/selectedCpIds/selectedOutcomeIds tidak cocok dengan component contract; satu invalid Livewire snapshot. |
| MonitoringTest | 2 | Heartbeat 401; read Attempt 409. Audit token session, lease, fence generation, dan status Attempt. |
| Phase7AScoringTest | 2 | Response 403 menggantikan 422/200; validasi role/permission/assignment dan urutan validasi request. |
| Kelompok satu kegagalan | 5 | Analytics Cache 403; ExamBuilder bulk add 422; MonitoringDuplication 403; ScoringEngine 403; TeacherPracticeWorkflow tambahan tercatat terpisah pada log. |

> Angka kelompok adalah hasil pengelompokan baris FAILED dari log run #45168433247. Jangan menganggap semua penyebab telah terbukti pada level source hanya dari pesan test; lakukan trace controller/service/policy/middleware dan fixture.

## IMPLEMENTASI YANG SUDAH DILAKUKAN PADA BRANCH INI

1. Membersihkan fixture committed pada `AttemptTransactionConcurrencyTest` agar tenant test tidak bocor ke test berikutnya. Backup byte-exact tersedia.
2. Menjadikan legacy generic resource API `curricula`, `phases`, `grade-levels`, `elements` hanya read-only (`index/show`); menambah regression tests. Backup route/test tersedia.
3. Menyelaraskan InstallerService postconditions dengan tiga role aktif `ADMIN_MADRASAH/GURU/SISWA` dan batas permission; memperbarui test. Backup tersedia.
4. Menambahkan guard CurriculumAnalytics agar data capaian/analitik hasil siswa hanya dapat diakses Guru dengan permission yang tepat; Admin ditolak. Backup tersedia.
5. Mengisolasi beberapa execution tests dari authoring snapshot dengan fixture immutable snapshot langsung; ini hanya fixture test, bukan perubahan production behavior.
6. Mengubah fixture beberapa lifecycle test agar memakai token Sanctum tersimpan dan header device/fence; beberapa test kemudian melewati auth 401, tetapi suite masih FAIL.
7. Memperbaiki tipe `sessions.user_id` agar kompatibel dengan UUID user dan menambahkan migration upgrade. Backup migration awal ada. Migration belum dijalankan di produksi dan harus diaudit terhadap instalasi upgrade.
8. Membuat helper `CreatesCanonicalTeacherAssignment` untuk test fixture assignment/assessment berbasis versi kurikulum dan registrasi resmi. Integrasi helper belum tuntas: ExamSnapshotTest masih gagal 15 test; ExamListContractTest memiliki regresi `$assignment` undefined.

## BATAS DESAIN YANG TIDAK BOLEH DILANGGAR

- Jangan mengubah LoginIdentityResolver untuk menerima lebih dari satu tenant lokal; cleanup fixture concurrency adalah solusi.
- Jangan melonggarkan TeacherAccess, policy, exact-one-role, assignment, ownership, session binding, lease/fencing hanya untuk membuat PHPUnit hijau.
- Admin tidak boleh authoring soal/ujian atau membaca nilai/Attempt/analitik hasil siswa.
- Jangan memulihkan mutating legacy curriculum routes. Jalur write harus melalui service/route kanonis yang terotorisasi.
- Jangan mengaktifkan Proktor/monitoring/room/session. Service saat ini memblokir assignment karena eligibility ujian pusat belum didefinisikan kanonis; itu blocker produk, bukan alasan mengubah test menjadi PASS.
- Jangan mengubah expected status secara massal. Periksa kontrak response dan middleware terlebih dahulu.

## CHECKPOINT BERIKUTNYA — PRIORITAS

1. Perbaiki regresi `ExamListContractTest::$assignment` (backup file sebelum edit, lalu focused test).
2. Audit `ExamSnapshotTest` setup dan gunakan helper canonical assignment dengan seluruh metadata yang disyaratkan `TeacherAccess`; pastikan test CP/TP mapping terpisah tetap memvalidasi snapshot.
3. Perbaiki FK cleanup `ExamVerificationTest` dalam urutan device_leases → attempts.
4. Rekonsiliasi seluruh Livewire tests Admin question bank/exam dengan keputusan owner: Admin tidak authoring; pindahkan test authoring ke komponen Guru yang benar atau uji akses Admin ditolak.
5. Triage Phase7CReopen/Scoring: cek source state machine, exact role/permission, finalization pointer dan tenant scope; jangan beri Admin hak reopen/score jika keputusan produk tidak mengizinkan.
6. Perbaiki Proctor tests hanya setelah audit bahwa service sengaja fail-closed; eligibility ujian pusat harus ditetapkan eksplisit oleh owner sebelum fitur dibuka.
7. Jalankan focused tests per file, lalu full PHPUnit exact SHA di MySQL 8.0.46.

## FILE / BACKUP / RIWAYAT
Semua backup source yang dibuat pada pekerjaan ini berada di `backend-laravel/docs/backup/solo-web-cbt-2026-11-23/`; backup workflow berada di `.github/workflows/backup/`. Daftar lengkap file berubah dan backup tercantum pada compare GitHub branch dan dokumen handover terbaru. Tidak ada perubahan DB produksi, deployment, merge, atau ZIP distribusi.

Status akhir: **FULL PHPUnit FAIL — 92 failed / 426 passed — WORK IN PROGRESS**.
