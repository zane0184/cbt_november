# Addendum Audit Web Guru — Integritas Analitik Nilai
Tanggal: 23 November 2026
Branch diperiksa: `work/web-guru-phase1-audit-implementation-20261122`

## AUDIT
Ditemukan ketidaksesuaian penting antara model data nilai dan query analitik:

1. `ExamAttempt::score()` serta `resolvedCurrentScore()` menetapkan bahwa skor aktif hanya ditentukan oleh `exam_attempt_score_current.current_score_id`, dengan kecocokan attempt, tenant, dan scoring_version. Versi skor lama adalah histori dan tidak boleh dianggap aktif hanya karena masih tersimpan.
2. `ExamAnalyticsService::getSummary()` dan `getDistribution()` saat ini membaca semua baris `exam_attempt_scores` berstatus FINALIZED untuk exam/tenant, tanpa join ke `exam_attempt_score_current`. Setelah reopen dan refinalisasi, versi lama yang masih FINALIZED dapat ikut dihitung bersama skor aktif.
3. `ItemAnalysisService::getAnalysis()` juga membaca seluruh baris skor FINALIZED tanpa current pointer. Agregat butir kemudian dicari berdasarkan `student_id`, bukan attempt ID dan versi scoring aktif. Jika siswa memiliki beberapa attempt atau histori skor, data butir dapat tercampur/terhitung ganda.
4. Test `tests/Feature/Analytics/ItemAnalysisTest.php` membuat score FINALIZED tanpa membuat current pointer. Test tersebut menguji batas jumlah sampel dan indeks diskriminasi, tetapi belum mengunci invariant histori-versus-current. Jika query diperketat, fixture harus diperbarui agar membangun pointer otoritatif.

## RISIKO
- Statistik rerata, median, distribusi, jumlah peserta, tingkat kesukaran, dan daya pembeda dapat memakai skor histori yang bukan skor aktif.
- Nilai hasil revisi/reopen dapat terhitung ganda atau tercampur dengan nilai sebelumnya.
- Data analitik tidak konsisten dengan aturan sumber kebenaran current score pointer.

## DESAIN PERBAIKAN YANG DIREKOMENDASIKAN
- Batasi populasi attempt ke status SUBMITTED/EXPIRED dan skor yang ditunjuk tepat oleh current pointer.
- Join skor aktif dengan pointer menggunakan seluruh invariant: `attempt_id`, `tenant_id`, `current_score_id = exam_attempt_scores.id`, dan `scoring_version`.
- Untuk analisis butir, ambil attempt ID dari populasi skor aktif, lalu agregasikan `exam_question_scores` hanya untuk attempt tersebut dan scoring_version yang sama dengan pointer masing-masing attempt. Hindari join hanya berdasarkan student_id.
- Tambahkan regression test: satu attempt dengan skor versi 1 FINALIZED dan versi 2 FINALIZED tetapi pointer menunjuk versi 2; summary/distribution hanya memakai versi 2. Untuk item analysis, skor butir versi 1 tidak boleh tercampur ke statistik versi 2. Tambahkan juga skenario dua attempt oleh student yang sama jika attempt limit mengizinkan.
- Perbarui fixture ItemAnalysisTest agar membuat ExamAttemptScoreCurrent untuk setiap attempt resmi.

## IMPLEMENTASI
Belum ada source aplikasi atau test analytics yang diubah. Perubahan ditahan sampai desain query dan test mencakup histori, pointer aktif, dan multiple attempts dengan benar.

## PENGUJIAN
- Source statis diperiksa pada `ExamAttempt.php`, `ExamAttemptScoreCurrent.php`, `ExamAnalyticsService.php`, `ItemAnalysisService.php`, dan test `ItemAnalysisTest.php`.
- Belum ada regression test baru dijalankan. Temuan ini adalah audit statis, bukan bukti runtime.
- CI patch error disclosure pada `ResultDetail.php` run `45056968233` saat pemeriksaan terakhir sudah melewati setup PHP/dependency, disposable MySQL dan lint/route; PHPUnit masih berjalan.

## STATUS
Temuan prioritas tinggi untuk integritas laporan hasil. Tidak ada perubahan database, migration, merge, atau deployment. Perlu diperbaiki sebelum menyatakan analitik hasil ujian Web Guru benar.
