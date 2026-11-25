# Laporan Implementasi P0 — Pengamanan Exam API Web CBT
Tanggal: 25 November 2026 (WIB)
Repository: `zane0184/cbt_november`
Branch implementasi: `work/coordinator-web-cbt-p0-20261125`
Source/test SHA: `732786ebca0d6d87217a45087de3a7fe785e1471`
Branch sumber awal: `work/guru-docx-import-e2e-20261125`
HEAD sumber awal: `7c1cdcbe62673d50d05923cef8de9b462cd8aec9`
CI final: [Workflow run #45591711361](https://github.com/zane0184/cbt_november/actions/runs/45591711361)

Status: **P0 API HARDENING — PASS CI MATRIX; SISTEM KESELURUHAN BELUM SIAP PRODUKSI**

---

## 1. AUDIT

### Temuan yang ditangani
1. Student generic exam list mengirim model Exam mentah yang memuat token.
2. Student generic exam detail mengirim question snapshots berdasarkan status/jadwal, tanpa admission penuh, Attempt, dan lease yang telah dibuktikan.
3. Student specification menetapkan bahwa Web Siswa tidak menampilkan token rahasia dan question snapshot hanya diberikan melalui execution setelah admission.
4. Admin memiliki akses baca exam register secara read-only. Hal ini dikonfirmasi oleh `RoleDashboardVerificationTest::test_admin_has_read_only_exam_register_access_and_no_teacher_cbt_operations`; akses baca Admin tidak boleh dicabut.
5. Token generic Admin tetap perlu di-redact sampai tersedia marker ujian terpusat UTS/UAS/UM dan permission khusus untuk workflow Proktor.

### Koreksi selama audit/implementasi
Percobaan awal menolak seluruh generic Exam read untuk Admin. CI menemukan test kontrak yang menyatakan Admin boleh membaca exam register read-only. Policy Admin dipulihkan persis dari backup byte-equivalent. Perubahan final mempertahankan read-only Admin, menyembunyikan token dari generic Admin list/detail, dan tetap melarang mutasi.

---

## 2. DESAIN

- **Guru:** tetap dapat melihat token ujian milik sendiri melalui alur Guru yang sudah ada.
- **Siswa:** generic list/detail hanya berisi metadata; token tidak dikirim; question snapshot tidak dikirim.
- **Execution Siswa:** tetap menjadi satu-satunya jalur untuk menerima snapshot setelah admission, Attempt yang sah, dan lease aktif divalidasi.
- **Admin:** tetap boleh membaca exam register dalam tenant sendiri; generic list/detail tidak menampilkan token atau snapshot; operasi create/update/delete, soal, peserta, token, monitoring, close, dan hasil tetap berada di luar kewenangan Admin.
- **Proktor:** assignment dan akses operasional tetap fail-closed sampai marker ujian terpusat kanonis tersedia.

---

## 3. IMPLEMENTASI

### File source yang berubah
1. `backend-laravel/app/Http/Controllers/Api/V1/ExamController.php`
   - Token di-redact pada index untuk role Siswa dan Admin.
   - Detail Siswa menjadi metadata-only; token disembunyikan dan relasi snapshots tidak dimuat/dikirim.
   - Detail Admin tetap read-only, tetapi token dan snapshots di-redact.
   - Jalur Guru tidak diubah untuk mempertahankan akses token pemilik ujian.

2. `backend-laravel/app/Policies/ExamPolicy.php`
   - Policy dipulihkan persis dari backup awal setelah test kontrak membuktikan Admin mempunyai hak baca exam register.
   - Tidak ada perubahan final pada kewenangan Admin untuk memutasi ujian.

### Test yang berubah/ditambahkan
3. `backend-laravel/tests/Feature/ExamListContractTest.php`
   - Memastikan Guru tetap melihat token ujian miliknya.
   - Memastikan Siswa tidak menerima token dari list.
   - Memastikan detail Siswa tidak menerima token atau snapshots sebelum admission.
   - Memastikan Admin dapat membaca register, tidak menerima token/snapshots, dan tidak dapat mengubah Exam.

4. `backend-laravel/tests/Feature/Security/AnswerKeyLeakageTest.php`
   - Mengubah test detail Siswa menjadi metadata-only.
   - Mempertahankan pemeriksaan bahwa execution tidak mengirim accepted answers, rubric, answer key, atau metadata scoring privat.

### CI
5. `.github/workflows/docx-import-parser-ci.yml`
   - Menambahkan branch koordinator ke push matrix agar patch ini diuji pada PHP 8.3/8.4 + MySQL 8.0/InnoDB.

### Backup
Backup byte-equivalent dibuat sebelum source diubah dan SHA-256 diverifikasi. Manifest:
`docs/backup_before_patch/COORDINATOR_P0_EXAM_API_20261125/MANIFEST.md`

File backup:
- `backend-laravel/backups/coordinator-web-cbt-p0-20261125/ExamController.php.before`
- `backend-laravel/backups/coordinator-web-cbt-p0-20261125/ExamPolicy.php.before`
- `backend-laravel/backups/coordinator-web-cbt-p0-20261125/ExamListContractTest.php.before`
- `backend-laravel/backups/coordinator-web-cbt-p0-20261125/AnswerKeyLeakageTest.php.before`
- `.github/backups/coordinator-web-cbt-p0-20261125/docx-import-parser-ci.yml.before`

---

## 4. PENGUJIAN

Workflow run **45591711361** menguji exact source/test SHA `732786ebca0d6d87217a45087de3a7fe785e1471` pada MySQL 8.0/InnoDB.

| Environment | PHPUnit | Assertions | Durasi |
|---|---:|---:|---:|
| PHP 8.3 + MySQL 8.0 | 659 passed, 1 skipped | 3.409 | 192,17 detik |
| PHP 8.4 + MySQL 8.0 | 659 passed, 1 skipped | 3.409 | 125,50 detik |

Hasil langkah CI:
- Composer lock validation: PASS.
- PHP lint application/migrations/tests: PASS.
- `git diff --check`: PASS.
- Clean-install Installer E2E pada database disposable terpisah: PASS.
- Student Import acceptance: 23 passed, 88 assertions.
- Benchmark cleanup denial-path: 1 passed, 20 assertions.
- Full PHPUnit pada kedua matriks: PASS.
- P0-02A non-CP academic acceptance dan test terkait tetap masuk full suite.

Catatan:
- Satu test tetap berstatus skipped; alasan spesifik belum diidentifikasi dari ringkasan workflow.
- PHPUnit menampilkan deprecation warning untuk metadata test dalam doc-comment yang perlu dimigrasikan ke attributes sebelum PHPUnit 12.
- Warning Node.js 20 berasal dari GitHub Actions dependency, bukan kegagalan aplikasi.
- Test ini adalah backend/feature CI. Browser E2E visual terhadap Laravel nyata belum dijalankan.

### Yang belum diuji
- Browser E2E Admin/Guru/Siswa terhadap Laravel + MySQL.
- UAT madrasah.
- Deployment staging/VPS dan ekstraksi ZIP.
- Offline/reload/crash pada browser/perangkat nyata.
- RTL/harakat/formula secara menyeluruh.
- Platform trust Android-browser vs iOS-Web.
- Seluruh fitur Admin/Guru yang masih MISSING/PARTIAL dalam laporan audit.

---

## 5. STATUS DAN LANGKAH BERIKUTNYA

**P0 Exam API hardening lulus CI pada PHP 8.3 dan PHP 8.4 + MySQL 8.0/InnoDB.** Ini bukan persetujuan produksi atau distribusi ZIP.

Prioritas berikutnya:
1. Gate fail-closed untuk MATCHING pada QuestionBank publish dan ExamSnapshotService sampai runtime E2E lengkap.
2. Perbaiki konsistensi akses media untuk Attempt IN_PROGRESS ketika Guru menutup Exam lebih awal; media harus mengikuti lease/expiry Attempt yang sama dengan execution.
3. Tetapkan trusted platform credential/capability; jangan memakai User-Agent atau client-supplied platform sebagai bukti.
4. Implement Import Guru XLSX, export Guru/Siswa, lifecycle tahun/semester, mutasi siswa, kop dokumen, audit UI, dan laporan Admin.
5. Selesaikan marker kanonis UTS/UAS/UM sebelum membuka assignment/monitoring Proktor.
6. Dapatkan artefak CP MI resmi dan checksum; jangan membuat konten CP fiktif.
7. Jalankan Browser E2E, UAT, staging, kemudian build ZIP dari exact SHA yang telah lulus acceptance.

Tidak ada migration produksi, mutasi database proyek, akses VPS, atau deployment pada tahap ini.
