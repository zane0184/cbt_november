# LAPORAN KOORDINATOR — AUDIT GITHUB DAN PEMULIHAN ROOT CAUSE PHPUNIT

Tanggal: 23 November 2026
Repository: zane0184/cbt_november
Branch sumber yang diaudit: `work/solo-web-cbt-completion-20261123-execution`
Source baseline audit: `d6aaeae6f2851bf15272d0cc2bce9e68989d3a4d`
Branch perbaikan terisolasi: `work/phpunit-41-root-cause-20261123`

> STATUS: WORK IN PROGRESS. Belum production-ready, belum siap operasional ujian, belum siap distribusi ZIP.

## A. AUDIT

### A1. Sumber GitHub yang dibaca

- `docs/AGENT_START_HERE.md`
- `docs/HANDOVER_AGENT_BARU_PHPUNIT_2026-11-23.md`
- `docs/REPORT_HANDOVER_AGENT_PENGGANTI_2026-11-23.md`
- `docs/PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md`
- `docs/CHECKPOINT_SOLO_WEB_CBT_2026-11-23.md`
- `docs/CHECKPOINT_LANJUTAN_WEB_CBT_2026-11-23.md`
- `docs/REPORT_P0_TRIAGE_FULL_PHPUNIT_2026-11-23.md`
- `docs/MATRIKS_ADM_01_23_AUDIT_2026-11-23.md`
- `docs/DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md`
- Master spesifikasi Web Admin, Web Guru, Web Siswa yang dirujuk owner.
- Issue #10 komentar `7081649923`, Issue #20 dan komentar keputusan/implementasi yang dirujuk handover.
- Branch, commit, PR #13/#18/#19/#21, compare GitHub, workflow dan log CI exact SHA.

Tidak digunakan ZIP baseline maupun ChatGPT Library.

### A2. Source dan CI terbaru

- Branch eksekusi sumber: `work/solo-web-cbt-completion-20261123-execution`.
- HEAD yang diuji: `d6aaeae6f2851bf15272d0cc2bce9e68989d3a4d`.
- Commit message: `fix: generate UUID primary keys for assessment curriculum mappings`.
- CI [#45191014821](https://github.com/zane0184/cbt_november/actions/runs/45191014821), job `laravel-mysql` / ID `137749077094`: FAILURE.
- Environment: PHP 8.3, disposable MySQL 8.0.46/InnoDB.
- MySQL identity, Composer, Laravel package discovery, disposable installer DB, focused login/tenant smoke: PASS.
- Full PHPUnit: **41 failed, 477 passed, 1,867 assertions**.

Handover yang menyebut 92 failed pada run #45168433247 sudah lebih lama. Jangan gunakan angka itu sebagai CI terbaru.

### A3. Pengelompokan tepat 41 kegagalan

| Akar masalah | Test suite | Jumlah | Bukti |
|---|---|---:|---|
| Fixture mapping kurikulum tidak mengisi `academic_year_id` dan `semester_id` wajib | ExamBuilderTest (7), ExamManagementHardeningTest (1), ExamSnapshotTest (15), AnswerKeyLeakageTest (7) | 30 | MySQL 1364: Field academic_year_id doesn't have a default value |
| Fixture historical recalculation mengganti registration/version assignment yang masih direferensikan | Phase7DHistoricalRecalculationAndAnalyticsTest | 4 | MySQL 1451 pada FK `tlosm_assignment_scope_fk` |
| Kontrak test Livewire ExamManagement masih memakai `selectedRombelId`/ALL dan asumsi lama, bukan assignment-scoped builder | ExamManagementHardeningTest | 4 | Validation assertion, expected 403 got 200, participant/snapshot assertions |
| Test rate limit tidak mengganti cached Sanctum guard saat berpindah bearer token | RateLimitingAndAbuseProtectionTest | 1 | Expected 200, received 429 untuk User B |
| Query role peserta memakai kolom `role_user.tenant_id` yang tidak ada; test kedua gagal lanjut karena Exam tidak terbentuk | TeacherPracticeWorkflowTest | 2 | MySQL 1054 unknown column; kemudian Attempt to read property id on null |

Total: 30 + 4 + 4 + 1 + 2 = 41.

### A4. Batas klasifikasi

- 30 kegagalan fixture bukan alasan mengubah nullable/default/constraint database. Konteks tahun akademik dan semester wajib diisi dari assignment yang sah.
- 4 kegagalan Livewire harus mengikuti kontrak komponen terbaru. Jangan memulihkan field rombel/subject lama atau memberi Guru akses global.
- FK historical mapping harus dipertahankan. Assignment yang memiliki mapping historis tidak boleh dipindah registration/version secara destruktif.
- Rate limiter produksi tidak boleh dinaikkan/dimatikan untuk membuat test lulus.
- Query tenant peserta adalah bug source nyata: tenant berada pada tabel `users`, bukan pivot `role_user`.

### A5. Status repo/PR

PR #13, #18, #19, dan #21 belum menjadi satu source terintegrasi yang siap merge. Beberapa branch divergen; compare harus diaudit file-per-file sebelum integrasi. PR #21 tetap Draft/unmerged pada saat audit. Tidak ada merge atau deployment.

## B. DESAIN

1. Satu instalasi dan satu database akademik per madrasah; tidak ada shared multi-tenant antar-madrasah.
2. Role lokal tepat ADMIN_MADRASAH, GURU, SISWA. Operator/Kamad tidak menjadi role/portal; histori legacy tidak dihapus diam-diam.
3. Proktor adalah menu/capability sementara pada portal Guru, bukan role/portal terpisah. Detail terbaru tercatat pada `ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md`.
4. Android dan Web Super Admin deferred sampai Juni 2027.
5. Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + delapan dimensi profil lulusan adalah satu ekosistem.
6. Perbaiki 41 test berdasarkan akar masalah dan pertahankan keamanan/constraint.

## C. IMPLEMENTASI DI BRANCH TERISOLASI

Branch `work/phpunit-41-root-cause-20261123` dibuat dari baseline exact d6aaeae6. Perubahan berikut sudah dicatat di GitHub, tetapi belum dinyatakan lulus pengujian:

| File | Perubahan | Backup |
|---|---|---|
| `tests/Concerns/CreatesCanonicalCurriculumContext.php` | Fixture AssessmentCurriculumMapping mengisi academic_year_id dan semester_id dari assignment | `backend-laravel/backup_before_patch/PHPUNIT_41_ROOT_CAUSE_20261123/CreatesCanonicalCurriculumContext.php.before` |
| `tests/Feature/Phase7DHistoricalRecalculationAndAnalyticsTest.php` | Menggunakan canonical curriculum context yang memakai registration/version kanonis stabil, tidak mengganti FK assignment | `.../Phase7DHistoricalRecalculationAndAnalyticsTest.php.before` |
| `app/Livewire/Admin/ExamManagement.php` | Query role SISWA memakai `users.tenant_id`, bukan kolom pivot yang tidak ada | `.../ExamManagement.php.before` |
| `tests/Feature/ExamManagementHardeningTest.php` | Test diselaraskan dengan selectedAssignmentId dan assignment-scoped contract; pengujian akses assignment asing tetap negatif | `.../ExamManagementHardeningTest.php.before` |
| `tests/Feature/RateLimitingAndAbuseProtectionTest.php` | Guard test direset ketika berpindah bearer token agar identitas User B dievaluasi secara benar | `.../RateLimitingAndAbuseProtectionTest.php.before` |
| `.github/workflows/tenant-backend-ci.yml` | Branch perbaikan terisolasi dimasukkan ke push trigger CI | `.github/workflows/backup/tenant-backend-ci.yml.before-phpunit-root-cause-branch-20261123` |

Backup tambahan:
- `CreatesCanonicalTeacherAssignment.php.before`
- `ExamManagementHardeningTest.php.before`
- `RateLimitingAndAbuseProtectionTest.php.before`

Semua backup dibuat sebelum perubahan pada file terkait. Commit backup dan patch berada pada riwayat branch tersebut.

### Status branch perbaikan

- HEAD setelah workflow trigger: `68ce79145c3d9ca7feff06b9709983b550a872e5`.
- CI [#45209452730](https://github.com/zane0184/cbt_november/actions/runs/45209452730) terdeteksi QUEUED pada saat laporan dibuat.
- PHPUnit setelah patch: NOT RUN / menunggu CI.
- Tidak ada merge ke branch eksekusi, migration produksi, deployment, atau ZIP final.

## D. PENGUJIAN

| Pengujian | Status |
|---|---|
| Audit log CI #45191014821 | PASS — log berhasil dibaca |
| PHP 8.3 + disposable MySQL 8.0.46/InnoDB pada source baseline | PASS |
| Focused login/tenant smoke pada source baseline | PASS |
| Full PHPUnit source baseline d6aaeae6 | FAIL — 41/477 |
| Patch root cause pada branch perbaikan | NOT VERIFIED — menunggu run #45209452730 |
| Local PHPUnit pada lingkungan agent ini | NOT RUN |
| Fresh install/upgrade matrix penuh | NOT RUN |
| Two-connection concurrency di luar test suite CI | NOT RUN |
| Browser-to-real-Laravel E2E tiga portal | NOT RUN |
| Offline/recovery/multi-device UAT | NOT RUN |
| Deployment VPS/aaPanel | NOT RUN |
| ZIP distribusi dan ekstraksi | NOT RUN |

## E. CHECKPOINT DAN LANGKAH BERIKUTNYA

1. Tunggu CI #45209452730 sampai selesai; baca log exact SHA.
2. Jika focused migration/login gagal, perbaiki akar masalah berikutnya; jika focused PASS, periksa full PHPUnit.
3. Jangan menambah patch source bertubi-tubi selama CI berjalan.
4. Setelah suite berkurang, triase sisa kegagalan baru dari log terbaru; jangan menganggap 41 patch otomatis menyelesaikan semua.
5. Setelah full PHPUnit PASS, lanjut audit matriks Web Admin, Web Guru, Web Siswa dan integrasi nyata sesuai spesifikasi; jangan mengulang implementasi yang sudah ada.
6. Perbarui checkpoint dan handover GitHub pada setiap tahap.
7. Merge, production migration, deployment, dan ZIP final tetap menunggu acceptance dan persetujuan owner.

**Kesimpulan:** Source baseline sudah memiliki kemajuan nyata, tetapi 41 test masih gagal. Branch perbaikan sudah berisi patch root cause dan backup, namun belum ada bukti CI lulus. Proyek belum siap produksi maupun distribusi.
