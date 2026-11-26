# CHECKPOINT CBT MADRASAH — QUESTION TYPES & SCORING HARDENING — 27 NOVEMBER 2026

## AUDIT
- Repo: `zane0184/cbt_november`
- PR #44: https://github.com/zane0184/cbt_november/pull/44
- Branch: `fix/manual-question-optional-curriculum-20261127`
- Base: `integration/zip-vps-php83-20261126`
- Head sebelum commit checkpoint: `ae6ef17a3cefd92821310598e33d46c5c4261d13`
- PR OPEN, DRAFT, belum merged.
- Compare sebelum commit checkpoint: 71 commit ahead, 0 behind.
- Matriks tipe soal:
  - Editor Guru: MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, TRUE_FALSE_GROUP, MATCHING, ESSAY.
  - DOCX importer: MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, TRUE_FALSE_GROUP, ESSAY. MATCHING belum didukung DOCX.
  - API jawaban: MC/MULTIPLE_CHOICE, TRUE_FALSE/TF, TRUE_FALSE_GROUP, MULTIPLE_RESPONSE, MATCHING, ESSAY. SHORT_ANSWER ditolak.
  - Scoring: MC/MULTIPLE_CHOICE/TRUE_FALSE/TF, TRUE_FALSE_GROUP, MULTIPLE_RESPONSE, MATCHING, ESSAY; evaluator SHORT_ANSWER masih ada sebagai kode lama tetapi jalur snapshot dan API tetap menolaknya.
- Temuan kontrak: snapshot sebelumnya tidak memiliki allowlist lengkap; answer switch tidak memiliki default rejection; alias legacy TF tidak konsisten di semua lapisan.
- Audit hasil/penilaian: score relation menggunakan current pointer dan memvalidasi attempt/tenant/version; akses hasil Siswa mensyaratkan exam show_result, attempt terminal, current score pointer valid, dan status FINALIZED. Detail per soal tidak dikirim kepada Siswa.
- Temuan hardening tambahan: API manual grading melakukan lock ulang QuestionScore berdasarkan ID saja; UI Guru belum memvalidasi nilai manual sebagai numeric dan feedback memiliki batas panjang yang tidak eksplisit.

## DESAIN
- Snapshot hanya boleh membawa tipe yang kontrak jawaban dan scoring-nya didukung.
- Tipe tak dikenal ditolak di snapshot dan API answer (fail-closed).
- Alias TF diperlakukan sama dengan TRUE_FALSE.
- SHORT_ANSWER tetap tidak diaktifkan.
- API manual grading mengunci ulang QuestionScore berdasarkan attempt, tenant, exam, dan scoring_version aktif.
- UI manual grading memvalidasi nilai numeric >=0, feedback string maksimal 5000 karakter, dan nilai tidak boleh melebihi max_score snapshot.
- Hasil siswa hanya terlihat bila hasil dipublikasikan dan current score sudah FINALIZED.

## IMPLEMENTASI
1. `backend-laravel/app/Services/ExamSnapshotService.php`
   - Allowlist tipe soal, fail-closed unknown type, alias MC/TF ikut pemeriksaan integritas opsi.
   - Blob SHA: `da10e1f13a0ec4269ca729fbb83c12c3e99a8721`
2. `backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php`
   - Alias TF diterima seperti TRUE_FALSE; tipe tak dikenal ditolak.
   - Blob SHA: `276ccf346f73382dbbcdc2ed380336c904acce12`
3. `backend-laravel/tests/Feature/ExamSnapshotTest.php`
   - Test unknown question type rejected; valid/invalid MATCHING snapshot; ESSAY rubric snapshot.
   - Blob SHA: `9f9ab032a0f01409878be79fede1d4c6c0aee483`
4. `backend-laravel/tests/Feature/ExamAnswerTest.php`
   - Payload alias TF ditambahkan.
   - Blob SHA: `511d9a9a7e8c1f37ae6d7a52362c933513c8d734`
5. `backend-laravel/app/Http/Controllers/Api/V1/ScoringController.php`
   - Query lock QuestionScore sekarang dibatasi ke attempt, tenant, exam, dan scoring_version pointer aktif.
   - Blob SHA: `e82c86139524f79588e24043d0590f7203b68223`
6. `backend-laravel/app/Livewire/Teacher/ResultDetail.php`
   - Validasi numeric manualScore, batas minimal 0, feedback nullable string max 5000, dan nilai tidak melebihi max_score.
   - Blob SHA: `6033e20d64a90756df19718b496581939d3518ae`

## BACKUP
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamSnapshotService.php.pre-question-type-allowlist`
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamAnswerController.php.pre-question-type-fail-closed`
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamSnapshotTest.php.pre-unknown-question-type-test`
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamAnswerTest.php.pre-tf-alias-test`
- `backend-laravel/backups/matching-essay-hardening-20261127/ExamSnapshotService.php.pre-legacy-type-alias-integrity`
- `backend-laravel/backups/matching-essay-hardening-20261127/ScoringController.php.pre-score-row-scope-hardening`
- `backend-laravel/backups/matching-essay-hardening-20261127/ResultDetail.php.pre-manual-score-input-validation`

## PENGUJIAN
- PHPUnit, CI, runtime, integration tests tidak dijalankan sesuai instruksi pengguna.
- Verifikasi statis: file diambil ulang dari GitHub; SHA cocok; allowlist, alias, fail-closed, scope QuestionScore, dan validasi UI ditemukan.
- Regression tests sudah ditulis, belum dijalankan. Tidak ada bukti test pass, Blade compile, atau runtime.
- Tidak ada database, migration, atau data produksi yang disentuh.

## STATUS DAN LANJUTAN
- PR #44 OPEN, DRAFT, belum merged.
- 71 commit ahead, 0 behind sebelum commit checkpoint.
- Tidak menyentuh Android; Android tetap ditunda.
- Berikutnya: audit jalur finalisasi dan publikasi hasil, audit keseluruhan endpoint scoring, lalu siapkan daftar gap untuk pengujian integrasi ketika pengguna mengizinkan.
