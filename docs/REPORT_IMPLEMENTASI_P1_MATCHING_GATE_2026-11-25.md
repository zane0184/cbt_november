# Laporan Implementasi P1 — Gate MATCHING dan Audit Akses Media
Tanggal: 25 November 2026 (WIB)
Repository: `zane0184/cbt_november`
Branch: `work/coordinator-web-cbt-p0-20261125`
Source/test SHA: `08b591624fef7cd9f62186453ab977fcee07ca12`
CI final: [Workflow run #45596394167](https://github.com/zane0184/cbt_november/actions/runs/45596394167)

Status: **P1 MATCHING GATE — PASS CI MATRIX. BUKAN PERSETUJUAN PRODUKSI.**

---

## 1. AUDIT

### MATCHING
- Bank Soal Guru sudah dapat membuat/mengubah soal MATCHING sebagai DRAFT.
- Jalur publikasi pada `QuestionBank::publishQuestion()` belum menolak MATCHING.
- `ExamSnapshotService::createSnapshots()` belum menolak MATCHING.
- Runtime E2E peserta untuk MATCHING belum terverifikasi. Kontrak scoring saja tidak cukup untuk mengaktifkan tipe soal.
- Test lama `AnswerKeyLeakageTest::test_student_execution_does_not_leak_matching_pair_keys` mencoba membuat snapshot MATCHING untuk ujian peserta, sehingga bertentangan dengan keputusan bahwa tipe ini belum aktif.

### Akses media setelah ujian ditutup
Pemeriksaan `routes/api.php`, controller, dan Livewire menemukan:
- Method `ExamController::close()` ada, tetapi tidak ditemukan route API yang mengeksposnya.
- Tidak ditemukan action close aktif pada komponen Exam Management yang diperiksa.
- `ExamAttemptController::store()` membatasi `expires_at` Attempt ke nilai yang tidak melampaui `exam.end_at`.
- Dengan demikian, belum ditemukan jalur operasional yang dapat menutup ujian lebih awal sementara Attempt tetap sah dan belum kedaluwarsa.

**Kesimpulan media:** dugaan bahwa filter `CLOSED` pada `MediaController` memutus media Attempt aktif setelah early-close belum terbukti pada alur produk yang tersedia. Tidak ada patch spekulatif pada `MediaController`. File tersebut dan test hardening terkait tetap dibackup untuk audit, tetapi tidak diubah.

---

## 2. DESAIN

1. MATCHING tetap boleh disusun sebagai DRAFT untuk persiapan, tetapi tidak boleh dipublikasikan.
2. Snapshot ujian harus menolak MATCHING, termasuk jika ada versi legacy berstatus PUBLISHED.
3. Gate harus berada pada authoring/publish dan service pembentuk snapshot, bukan hanya menyembunyikan tombol UI.
4. Test runtime yang mencoba menjalankan MATCHING tidak boleh dipertahankan sebagai bukti kesiapan ketika tipe soal memang belum aktif. Test tersebut ditunda sampai runtime E2E, navigasi, autosave, offline sync, dan scoring MATCHING lengkap.
5. Media authorization tidak diubah karena early-close belum memiliki jalur operasional yang dapat diuji. Jika fitur early-close kelak dibuat, media authorization wajib diuji bersama Attempt aktif dan lease.

---

## 3. IMPLEMENTASI

### File source yang berubah
1. `backend-laravel/app/Livewire/Admin/QuestionBank.php`
   - Menolak publikasi versi MATCHING dengan HTTP 422.
   - Soal tetap DRAFT; tidak dihapus.

2. `backend-laravel/app/Services/ExamSnapshotService.php`
   - Menolak penambahan MATCHING ke immutable exam snapshot dengan HTTP 422.
   - Gate juga berlaku untuk versi MATCHING legacy yang sudah berstatus PUBLISHED.

3. `backend-laravel/tests/Feature/QuestionBankTest.php`
   - Test authoring MATCHING tetap memeriksa create/update DRAFT.
   - Ditambahkan assertion bahwa publikasi ditolak dan status tetap DRAFT.

4. `backend-laravel/tests/Feature/ExamSnapshotTest.php`
   - Ditambahkan regression test untuk versi MATCHING legacy PUBLISHED.
   - Ekspektasi: HTTP 422 dan tidak ada snapshot baru.

5. `backend-laravel/tests/Feature/Security/AnswerKeyLeakageTest.php`
   - Test runtime payload MATCHING yang tidak lagi sesuai kebijakan dihapus.
   - Perlindungan saat ini dibuktikan melalui gate publish/snapshot. Test kebocoran payload MATCHING harus ditambahkan kembali ketika tipe tersebut benar-benar diaktifkan setelah E2E.

### Backup
- Backup enam file audit awal dibuat pada commit `baf6eb75b9fe00f9c6ad4331a655055f5de641be`.
- Backup `AnswerKeyLeakageTest.php` sebelum penghapusan test lama dibuat pada commit `aae0c8c2b474bea1a34ea6f88f3d00f36220aeb1`.
- Semua backup dan hash tercantum dalam manifest:
  `docs/backup_before_patch/COORDINATOR_P1_MATCHING_MEDIA_20261125/MANIFEST.md`.
- `MediaController.php` dan `ExamSessionHardeningTest.php` dibackup untuk audit, tetapi tidak diubah.

---

## 4. PENGUJIAN

Workflow run **45596394167** menguji exact source/test SHA `08b591624fef7cd9f62186453ab977fcee07ca12` pada MySQL 8.0/InnoDB.

| Environment | PHPUnit | Assertions | Durasi |
|---|---:|---:|---:|
| PHP 8.3 + MySQL 8.0 | 659 passed, 1 skipped | 3.413 | 199,91 detik |
| PHP 8.4 + MySQL 8.0 | 659 passed, 1 skipped | 3.413 | 131,98 detik |

Langkah CI:
- Composer lock validation: PASS.
- PHP lint application/migrations/tests: PASS.
- `git diff --check`: PASS.
- Clean-install Installer E2E pada database disposable: PASS.
- Student Import acceptance: 23 passed, 88 assertions.
- Benchmark cleanup denial-path: 1 passed, 20 assertions.
- Full PHPUnit pada kedua matriks: PASS.
- P0-02A acceptance test: 1 passed, 27 assertions.

Catatan:
- Satu test tetap skipped; alasan spesifik belum diidentifikasi dari ringkasan workflow.
- Ada deprecation warning metadata test doc-comment yang perlu dimigrasikan ke attributes sebelum PHPUnit 12.
- Browser E2E visual, UAT, staging/VPS, dan deployment ZIP belum dijalankan.

---

## 5. STATUS DAN LANGKAH BERIKUTNYA

**Gate MATCHING lulus CI dan sekarang fail-closed pada publish serta snapshot.** Ini tidak berarti MATCHING siap digunakan peserta dan tidak berarti seluruh CBT siap produksi.

Berikutnya:
1. Bangun platform trust model untuk memisahkan Web Siswa iOS dan klien yang tidak dipercaya; jangan memakai User-Agent/client-supplied platform sebagai bukti.
2. Audit ulang media bila early-close route benar-benar ditambahkan; sertakan test Attempt aktif sampai expiry.
3. Lanjutkan fitur Admin/Guru yang MISSING/PARTIAL: import Guru XLSX, export Guru/Siswa, mutasi siswa, tahun/semester, kop dokumen, laporan.
4. Selesaikan Browser E2E Admin/Guru/Siswa dan UAT.
5. Pertahankan MATCHING dan SHORT_ANSWER nonaktif sampai seluruh acceptance gate masing-masing lulus.

Tidak ada migration produksi, mutasi database proyek, akses VPS, atau deployment pada tahap ini.
