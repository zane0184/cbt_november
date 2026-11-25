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
