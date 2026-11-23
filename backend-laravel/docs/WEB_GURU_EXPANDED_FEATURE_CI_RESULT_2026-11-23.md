# Hasil CI — Portal Guru: Bank Soal, Exam Builder, Hasil dan Penilaian
Tanggal: 23 November 2026

## Source yang diuji
- Branch: `work/web-guru-remove-xlsx-20261123`
- Checkout SHA: `c7c54f29920ce0191e9e7fb4f45a061e169d5e87`
- Current branch HEAD: `0cd51aa73cd6f50498ffd220ff0ed38b00941618`
- GitHub compare menunjukkan satu commit setelah SHA yang diuji hanya menambahkan manifest backup `MANIFEST_EXAM_BUILDER_TESTS.md`; tidak ada perubahan source, test, atau workflow setelah pengujian.

## Hasil final
GitHub Actions run [45108402325](https://github.com/zane0184/cbt_november/actions/runs/45108402325):
- Status: **SUCCESS**
- PHPUnit: **97 passed, 443 assertions**, durasi 24.62 detik.
- PHP lint: lulus.
- Laravel route registration: lulus.
- Composer validation, locked dependency install, dan platform requirements: lulus.
- Disposable MySQL 8 diverifikasi sebelum PHPUnit.
- Hasil migration test: **80 base tables, seluruhnya InnoDB**.
- Disposable database dan user dibersihkan workflow.

## Test suites yang masuk filter
- `TeacherPortalFoundationTest`
- `TeacherPracticeWorkflowTest`
- `QuestionBankTest`
- `ExamBuilderTest`
- `QuestionImportTest`
- `ResultWorkflowTest`
- `PortalLoginRoleBoundaryTest`
- `ItemAnalysisTest`
- `CurrentScoreAnalyticsTest`
- `TeacherResultAccessTest`
- `TeacherResultNavigationTest`
- `TeacherQuestionMediaTest`

Coverage tambahan yang kini teruji:
- Question Bank: create/edit/delete, import row-based, versioning, dan media DRAFT.
- Exam Builder API: bulk add questions, lock/immutability, preview, participant assignment, tenant boundary, question picker scope.
- ResultDetail Livewire: nilai ESSAY manual, feedback, current score pointer, finalisasi.
- ResultList Livewire: preflight dan bulk finalize, publikasi/tarik publikasi hasil.
- Analytics: hanya current scoring pointer/version yang dihitung.
- Portal security: role revocation, multi-role, tenant mismatch, dan hydration access.

## Backup
Workflow CI yang ditambah `ExamBuilderTest` disalin sebelum perubahan dan SHA backup diverifikasi MATCH. Lihat `MANIFEST_EXAM_BUILDER_TESTS.md`. Total backup tahap ini menjadi **17/17 file** terverifikasi byte-exact.

## Batas
Ini tetap bukan pengujian seluruh fitur aplikasi atau bukti siap produksi. Belum ada browser E2E/visual QA, concurrency/load test, multi-device integration, DOCX extraction tests, UAT madrasah, atau deployment VPS. Fitur kurikulum resmi/versioned, materi/topik, DOCX, RTL/harakat/math rendering, Pengaturan Guru, dan Monitoring Guru tetap memiliki gap/pekerjaan tersendiri.

## Status
**CI Web Guru yang diperluas: PASS 97/443.**
DOCX masih menunggu kontrak final Agent 2. Tidak ada merge, deployment, migration produksi, atau perubahan database produksi.
