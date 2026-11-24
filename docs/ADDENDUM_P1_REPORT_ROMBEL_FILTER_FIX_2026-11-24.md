# Addendum P1 — Perbaikan Rombel Filter Laporan Hasil Ujian

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`  
Source code commit: `feee0dac40b35edced677e2ee1f1e083ba6c7e26`  
PR #23: OPEN / DRAFT / NOT MERGED

## A. AUDIT

Pada CI sebelumnya:
- PHPUnit: 547 PASS / 0 FAIL / 2.344 assertions.
- Benchmark MySQL disposable berhasil membuat schema, menjalankan migration, seeding role/curriculum, dan membangun workload.
- Policy report sekarang dilewati oleh aktor Guru yang sah dengan permission `report.view` dan TeacherSubjectAssignment aktif.
- PDF export kemudian menemukan bug aplikasi: `ExamResultReportService` menggunakan variabel `$exam` di nested closure filter rombel tanpa menangkap variabel tersebut.

Bug terdapat pada dua jalur:
- `getResults()`
- `getAllResultsForExport()`

## B. DESAIN

Filter rombel harus membatasi hasil berdasarkan:
- rombel yang diminta;
- tahun ajaran pada Assessment milik Exam;
- relasi StudentProfile peserta.

Closure nested harus menangkap `$exam` secara eksplisit. Authorization ReportPolicy dan TeacherAccess tetap tidak diubah.

## C. IMPLEMENTASI

- Kedua nested closure di `ExamResultReportService.php` sekarang menangkap `$rombelId` dan `$exam`.
- Test `Phase7DReportingTest::test_rombel_filter_is_applied_to_result_queries_and_export` ditambahkan untuk menguji kedua jalur query.
- Test menggunakan StudentProfile, TeacherProfile, Rombel, dan academic year yang sesuai.
- Backup byte-exact:
  `backend-laravel/backup_before_patch/P1_REPORT_ROMBEL_FILTER_CAPTURE_20261124/BACKUP_MANIFEST.md`

## D. PENGUJIAN

- PHPUnit pada source sebelum patch report: 547 PASS / 0 FAIL / 2.344 assertions.
- Benchmark PDF sebelumnya: gagal pada undefined variable `$exam`.
- Regression test baru: menunggu CI exact SHA `feee0dac...`.
- Benchmark MySQL PDF: menunggu CI exact SHA `feee0dac...`.
- Temporary database cleanup sebelumnya: berhasil dan absence sudah diverifikasi.
- Tidak ada database produksi atau VPS yang disentuh.

## E. STATUS

Rombel-filter report query: PATCHED, NOT VERIFIED.  
Benchmark MySQL disposable: PARTIAL.  
P1-ID-03A: PARTIAL.  
P1-ID-03B: BLOCKED.  
PR #23 tetap Draft.

## F. NEXT ACTION

1. CI exact HEAD.
2. Jika regression test lulus, jalankan benchmark PDF hingga selesai.
3. Catat metrik dan verifikasi schema temporary terhapus.
4. Lanjutkan port account lifecycle secara selektif; hapus login gate aktif-attempt yang bertentangan dengan spesifikasi Web Siswa.
