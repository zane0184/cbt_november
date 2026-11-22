# Implementasi P0 Portal Web Guru — 23 November 2026

## Identitas
- Repository: `zane0184/cbt_november`
- Branch: `work/web-guru-phase1-audit-implementation-20261122`
- Lingkup: integritas current score, keamanan halaman hasil/analitik, regression tests, MySQL 8 CI.
- Tidak membaca ZIP baseline atau Library/Pustaka.
- Tidak mengubah Portal Super Admin, Android, database produksi, atau deployment.

## Backup sebelum perubahan
Backup source asli tersimpan di:
`.github/backups/web-guru-p0-20261123/`

Manifest mencatat original Git blob SHA. Salinan backup untuk 16 file yang akan disentuh telah dibaca ulang dan blob SHA salinan cocok dengan blob SHA sumber. Tidak ada source asli yang dihapus.

## Implementasi source
1. `ExamAttemptScore::scopeCurrent()` ditambahkan. Scope hanya memilih score yang ditunjuk `exam_attempt_score_current` dan cocok pada current_score_id, attempt_id, tenant_id, dan scoring_version.
2. Scope current diterapkan ke ExamAnalytics, StudentAnalytics, RombelAnalytics, SubjectAnalytics, dan ExamSummaryReport.
3. ItemAnalysis sekarang membentuk populasi berdasarkan current score per attempt; agregasi ExamQuestionScore dicocokkan dengan attempt_id, tenant_id, dan scoring_version aktif, bukan hanya student_id.
4. QuestionReport dibatasi ke attempt dengan current finalized score; agregasi butir mengikuti scoring_version pointer dan closure menerima konteks exam yang benar.
5. SubjectAnalytics closure menerima subject melalui capture.
6. Policy hasil dan attempt menolak akun yang membawa role GURU bersama role lain, tanpa menghapus jalur KAMAD yang diizinkan.
7. `TeacherResultComponent::booted()` memeriksa ulang akun, role persisted, tenant, dan policy setelah mount/hydration. ResultList, ResultDetail, AnalyticsDashboard, dan ItemAnalysis memakai base component tersebut.

## Regression tests
- Test baru `CurrentScoreAnalyticsTest` mencakup histori score finalized lama vs current, distribusi, ringkasan, analitik siswa, item analysis, question report, subject analytics, serta pointer hilang.
- Test baru `TeacherResultAccessTest` mencakup role revoke saat Livewire session aktif, multi-role Guru, dan akses KAMAD.
- `ItemAnalysisTest` yang sudah ada diperbarui agar fixture membuat current pointer dan question scoring_version.
- Workflow MySQL 8 menambahkan tiga test class tersebut pada filter PHPUnit.

## Pengujian
- Belum ada klaim test lulus.
- Perubahan workflow dan dokumen ini mendorong GitHub Actions untuk menjalankan PHPUnit di MySQL 8 disposable database yang dibuat dan dihapus per run.
- Hasil CI harus dibaca sampai job dan log selesai; kegagalan wajib diperbaiki sebelum P0 dinyatakan selesai.
- Tidak ada migration produksi atau akses ke database produksi.

## Batas tahap ini
Fokus implementasi ini adalah P0 integritas score dan otorisasi hasil/analitik. UI impor/media, ATP, editor RTL/persamaan, navigasi, dan kelengkapan alur asesmen masih tahap berikutnya.
