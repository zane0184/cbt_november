# Hasil CI — Alur Penilaian dan Hasil Portal Guru
Tanggal: 23 November 2026

## Source yang diuji
- Branch: `work/web-guru-remove-xlsx-20261123`
- Checkout SHA: `2c6a95d06bd720abde9b299c52abebdd79df29f2`
- Current branch HEAD: `911956939b9d2121321af195012c0fe5d380da54`
- GitHub compare menunjukkan satu-satunya commit setelah SHA diuji adalah penambahan `MANIFEST_RESULTS_TESTS.md`; tidak ada source aplikasi, test, atau workflow berubah setelah pengujian.

## Hasil
GitHub Actions run [45108038746](https://github.com/zane0184/cbt_november/actions/runs/45108038746):
- Status: **SUCCESS**
- PHPUnit: **90 passed, 426 assertions**, durasi 14.59 detik.
- PHP lint: lulus.
- Route registration: lulus.
- Composer validate, install locked dependencies, dan platform requirements: lulus.
- Disposable MySQL 8 diverifikasi identitas dan schema kosong sebelum test.
- Setelah migration: **80 base tables, seluruhnya InnoDB**.
- Database dan user disposable dibersihkan oleh workflow.

## Coverage tambahan yang masuk
1. `TeacherResultAccessTest::test_guru_can_grade_and_finalize_essay_through_result_detail_livewire`
   - Guru membuka detail attempt yang telah dikirim.
   - Mengambil butir nilai dari current scoring pointer.
   - Mengisi nilai manual dan feedback melalui Livewire ResultDetail.
   - Menyimpan nilai, memastikan status GRADED.
   - Finalisasi dan memverifikasi current score FINALIZED, finalized_at, dan finalized_by.
2. `ResultWorkflowTest` ditambahkan ke filter CI:
   - Filter hasil belum dinilai dan konsistensi laporan/ekspor.
   - Current score pointer dan penolakan pointer lintas attempt/version.
   - Alur essay grading/finalisasi.
   - Preflight dan bulk finalize melalui Livewire ResultList.
   - Publikasi/tarik publikasi hasil dan visibilitas nilai siswa.

## Backup
Sebelum perubahan coverage, file `TeacherResultAccessTest.php` dan workflow CI disalin ke `.github/backups/web-guru-remove-xlsx-20261123/`; 2/2 SHA MATCH. Lihat `MANIFEST_RESULTS_TESTS.md`.

## Batas
CI ini menguji fitur dan transaksi terpilih. Belum ada browser E2E/visual QA, concurrency/load test, uji multi-device, UAT madrasah, deployment VPS, atau bukti production readiness. Reopen terkontrol tetap merupakan kewenangan Admin dan layanan shared Agent 2; pengujian reopen backend yang ada tidak mengubah kewenangan tersebut.

## Status
**Alur grading/finalisasi ResultDetail dan bulk finalize/publikasi ResultList: CI PASS pada source SHA di atas.**
Tidak ada perubahan shared scoring service, schema, migration, merge, deployment, atau DB produksi.
