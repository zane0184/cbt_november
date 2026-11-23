# Laporan Remediasi Full PHPUnit — Taksonomi 42 Kegagalan dan Hasil Hijau

Tanggal: 24 November 2026 (WIB)  
Repository: `zane0184/cbt_november`  
Branch: `work/solo-web-cbt-completion-20261123-execution`  
Baseline gagal: `acc24ed24ea9c37baf1355067235e97af06ca325`  
HEAD hijau: `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`  
CI hijau: [GitHub Actions #45243430635](https://github.com/zane0184/cbt_november/actions/runs/45243430635)

## AUDIT

### Koreksi jumlah kegagalan
Permintaan menyebut 41 test, tetapi log CI baseline yang diperiksa secara langsung berisi **42 baris FAILED**. Angka audit memakai hitungan log: 42 gagal, 476 lulus, 1.863 assertions. Angka 41 tidak didukung oleh log baseline tersebut.

### Pengelompokan 42 kegagalan awal

| Kelompok test | Gagal | Pola utama | Klasifikasi akar masalah |
|---|---:|---|---|
| ExamBuilderTest | 7 | MySQL error 1364: `assessment_curriculum_mappings.academic_year_id` wajib tetapi fixture tidak mengisinya | Test/helper fixture |
| ExamSnapshotTest | 15 | Error 1364 yang sama saat helper membuat mapping; fixture assignment historis juga perlu memakai registration/version context kanonis yang sama | Test/helper fixture |
| AnswerKeyLeakageTest | 7 | Error 1364 pada mapping kurikulum; kemudian fixture snapshot juga harus memenuhi status terbit Question dan QuestionVersion | Test/helper fixture |
| Phase7DHistoricalRecalculationAndAnalyticsTest | 4 | Mapping kehilangan academic year/semester dan assessment historis membuat scope assignment/version berbeda dari mapping TP/struktur yang sudah terikat | Test/helper fixture |
| ExamManagementHardeningTest | 5 | Validasi rombel tidak eksplisit; nilai `ALL` lolos sebagai target; waktu mulai pada menit berjalan dapat menjadi masa lalu sebelum validasi; satu insert peserta gagal karena pivot role yang salah diasumsikan punya `tenant_id` | Campuran bug aplikasi + fixture waktu |
| RateLimitingAndAbuseProtectionTest | 2 | Limit heartbeat berbasis IP berbagi kuota untuk pengguna di balik NAT; presisi timestamp MySQL membuat assertion perubahan waktu tidak stabil | Campuran bug aplikasi + fixture waktu |
| TeacherPracticeWorkflowTest | 2 | Query peserta mengakses `role_user.tenant_id` yang tidak ada; jadwal mulai pada menit berjalan dapat menjadi masa lalu dan membuat Exam tidak terbentuk | Campuran bug aplikasi + fixture waktu |
| **Total** | **42** |  |  |

### Penjelasan skema/pivot
- Kolom `academic_year_id` dan `semester_id` pada `assessment_curriculum_mappings` memang merupakan bagian dari kontrak skema MySQL. Kegagalan berasal dari helper/fixture test yang tidak memasok nilai akademik wajib, bukan alasan mengubah skema produksi menjadi nullable.
- Tabel pivot `role_user` hanya memiliki `role_id` dan `user_id`; tidak memiliki `tenant_id`. Kegagalan ini adalah bug query aplikasi pada alur pemilihan peserta ujian.
- Tidak ditemukan bukti dari 42 baris FAILED tersebut tentang perubahan skema ruang ujian atau pivot Proktor. Jangan menyimpulkan ada bug room/proktor dari kumpulan kegagalan ini.
- Kegagalan 403 tidak boleh digeneralisasi sebagai RBAC rusak. Kasus yang terkonfirmasi adalah Guru dapat mengirim nilai `ALL` dan mendapat 200 padahal harus ditolak; itu bug batas assignment di aplikasi. Test lain diselaraskan ke aktor Guru ketika kontrak izin memang Guru-only.

## PEMISAHAN BUG APLIKASI VS TEST/FACTORY

### Bug aplikasi yang diperbaiki
1. **Query pivot role salah skema** — `ExamManagement.php` mengakses `role_user.tenant_id`. Query diperbaiki agar role dipetakan melalui user yang sudah dibatasi `users.tenant_id`; pivot tidak diberi kolom baru.
2. **Batas Rombel pada Guru** — `saveExam` sekarang mewajibkan UUID Rombel, menolak nilai `ALL` (403), dan memeriksa Rombel terpilih sama dengan Rombel pada assignment aktif. Ini menjaga isolasi penugasan dan mencegah ekspansi peserta lintas Rombel.
3. **Rate limit heartbeat** — limiter bernama `exam-heartbeat` dibatasi 30 permintaan/menit per identitas pengguna/credential, dengan fallback IP ketika identitas belum tersedia. Route heartbeat memakai limiter tersebut. Ini mencegah pengguna berbeda di belakang NAT berbagi kuota yang sama.

### Penyesuaian test/helper/factory — bukan perubahan kontrak produksi
1. Helper canonical curriculum mengisi `academic_year_id` dan `semester_id` untuk mapping asesmen.
2. Test histori Phase7D menggunakan registration/version assignment yang sama dengan TP/struktur mapping yang telah dibuat; tidak mengganti composite-FK scope di tengah test.
3. Fixture ExamBuilder, ExamSnapshot dan AnswerKeyLeakage menandai parent Question dan QuestionVersion yang memang menjadi sumber snapshot sebagai `PUBLISHED`. Kasus yang sengaja menguji status non-published tetap dipertahankan.
4. Fixture jadwal ExamManagement dan TeacherPractice dipindah ke waktu mendatang agar tidak gagal karena waktu berjalan melewati batas menit.
5. Test Phase7D yang mengharapkan reopen berhasil memakai aktor Guru sesuai izin; tidak menambah hak Admin.
6. Test rate-limit memajukan clock satu detik sebelum membandingkan `last_activity_at`, sesuai presisi timestamp MySQL.
7. Fixture execution mengirim Sanctum token yang terikat ke pengguna, device binding dan fencing generation pada setiap request yang memang mensyaratkannya; test pergantian pengguna mereset cached auth guard.
8. Fixture multi-curriculum menyesuaikan skenario konflik reserved SYSTEM tenant dengan seeder/identity contract yang memang aktif.
9. Helper fencing memperbaiki fully qualified model names dan normalisasi namespace separator.

### Klasifikasi tambahan pada tahap antara
Setelah patch awal, run antara menunjukkan 26 gagal. Kategori tersebut bukan 26 akar masalah baru; di antaranya masih sisa status Question/QuestionVersion, validasi waktu, aktor reopen, limiter heartbeat, dan fixture execution. Perbaikan lanjutan di atas menutupnya sampai run penuh hijau.

## IMPLEMENTASI DAN BACKUP

Perubahan aplikasi yang relevan:
- `backend-laravel/app/Livewire/Admin/ExamManagement.php`
- `backend-laravel/app/Providers/AppServiceProvider.php`
- `backend-laravel/routes/api.php`

Perubahan test/helper yang relevan:
- `backend-laravel/tests/Concerns/CreatesCanonicalCurriculumContext.php`
- `backend-laravel/tests/Feature/ExamBuilderTest.php`
- `backend-laravel/tests/Feature/ExamManagementHardeningTest.php`
- `backend-laravel/tests/Feature/ExamSnapshotTest.php`
- `backend-laravel/tests/Feature/Fase1cMultiCurriculumIntegrityTest.php`
- `backend-laravel/tests/Feature/Phase7DHistoricalRecalculationAndAnalyticsTest.php`
- `backend-laravel/tests/Feature/RateLimitingAndAbuseProtectionTest.php`
- `backend-laravel/tests/Feature/Security/AnswerKeyLeakageTest.php`
- `backend-laravel/tests/Feature/TeacherPracticeWorkflowTest.php`

Backup byte-exact sebelum perubahan tersedia di:
- `backend-laravel/backup_before_patch/P0_CANONICAL_FIXTURE_SCOPE_20261124/`
- `backend-laravel/backup_before_patch/P0_ROLE_PIVOT_SCOPE_20261124/`
- `backend-laravel/backup_before_patch/P0_PHPUNIT_26_FAILURES_20261124/`
- `backend-laravel/backup_before_patch/P0_RESERVED_TENANT_FIXTURE_20261124/`

Commit penting:
- `8f3dd62f064be8f2940eb3afc30f6a6d2d28fdd4` — mapping fixture mengisi academic scope.
- `3ada2353b9471b2986a7138665636d458fcca4f9` — historical assessment memakai scope assignment kanonis yang sama.
- `30aa1bfd36ec4a24e689ef0fc88ac9ce7a95f02f` — query role pivot diperbaiki.
- `5582af32032b6fad8129f77c6edda7706484861a` — guard Rombel assignment.
- `baa9dc084c9d5995dd38fedfe6ddce1404229d06`, `10232982731f522d8418d66085041e632c5b08c2`, `e2375d585a307406b2c897f14633ac965a52161d`, `ca60e3ccb417cce48c40e2f1bf2d593b4aaa258a` — per-user heartbeat throttling dan key isolation.
- `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51` — final helper namespace correction.

## PENGUJIAN

CI final [#45243430635](https://github.com/zane0184/cbt_november/actions/runs/45243430635):
- Exact source SHA: `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`.
- Environment: GitHub Actions, PHP 8.3, disposable MySQL 8.0/InnoDB.
- Isolated DB identity check: PASS.
- Composer validation/install: PASS.
- Laravel package discovery: PASS.
- Focused Web login/tenant smoke: **27 passed (90 assertions)**.
- Full PHPUnit: **518 passed (2,177 assertions), 0 failed**.
- Relevant tests explicitly PASS: ExamBuilderTest, ExamManagementHardeningTest, ExamSnapshotTest, Phase7DHistoricalRecalculationAndAnalyticsTest, RateLimitingAndAbuseProtectionTest, Security/AnswerKeyLeakageTest, TeacherPracticeWorkflowTest, ProctorAssignmentSecurityTest, RBACEnforcementTest, AttemptTransactionConcurrencyTest, ExamExecutionHardeningTest, ExamVerificationTest.

## KESIMPULAN TERBATAS
- Permintaan remediasi PHPUnit ini telah mencapai status **HIJAU** pada exact SHA yang disebutkan.
- Tidak ada bukti perubahan skema produksi, migration produksi, database produksi, deployment VPS, atau merge PR dalam remediasi ini.
- Hasil hijau adalah bukti suite CI MySQL disposable pada SHA tersebut; bukan klaim bahwa CBT siap produksi atau semua portal sudah UAT.
- Fokus pekerjaan ini selesai. Tidak melakukan pekerjaan di luar taksonomi kegagalan dan full PHPUnit.
