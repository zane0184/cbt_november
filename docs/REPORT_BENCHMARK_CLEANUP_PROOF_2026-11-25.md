# Laporan Tahap — Benchmark Cleanup Proof dan Audit Lanjutan

Tanggal pemeriksaan: 25 November 2026 (WIB)  
Repository: `zane0184/cbt_november`  
Branch kerja: `work/benchmark-cleanup-proof-20261125`  
Baseline branch: `d7a33b73e32d42064a31f4ef29c01266121d29f0`  
HEAD dokumentasi saat laporan dibuat: `2fcf74e883a4c9752168045620e962433b1d6397`  
Exact source SHA yang diuji CI: `b6148507bd77116dfc8a5f5697cf4cff226a4b87`

## AUDIT

### 1. Posisi repository dan branch

- Branch kerja berada 18 commit di depan baseline `d7a33b7`, tanpa commit tertinggal.
- HEAD `2fcf` hanya berbeda dari source SHA teruji `b614850` pada dua perubahan dokumentasi/backup manifest; tidak ada source aplikasi berubah setelah CI PASS.
- Branch `work/docx-import-parser-20261125`: `d7a33b73e32d42064a31f4ef29c01266121d29f0`.
- Branch `work/p1-seven-targets-20261124`: `d2127a6e6663ebcf4c9107e06dfc6b1f231c7241`.
- Branch `work/import-guru-siswa-implementation-20261122`: `f38ceec7b0653fcf3363eec40f3d4577c323cca8`.
- Branch `main`: `b29e1e0ec7f14a46515cddb29e306ddb1d5f1ebb`.
- PR #23 tetap OPEN, DRAFT, UNMERGED. Head PR masih `work/p1-seven-targets-20261124` / `d2127a6`; base tetap `work/phpunit-41-root-cause` / `1c33e5d422175f9bd85d206230271a9fe5a4eecd`. Tidak ada perubahan base, merge, atau penutupan PR.

### 2. Diagnosis benchmark

Run lama pada SHA `5d02d58` dan `38ab6a0` gagal pada assertion yang mengharapkan `ReportPolicy` menjadi policy langsung untuk model `Exam`. Source Laravel yang sebenarnya memakai `ExamPolicy` untuk model tersebut; method `viewExamResults` mendelegasikan pemeriksaan ke `ReportPolicy`.

Pada source awal branch ini, benchmark command juga menjalankan pre-authorization Gate sendiri sebelum memanggil `ReportController::exportPdf()`. Akibatnya, test denial dapat berhenti sebelum jalur authorization controller yang sebenarnya dijalankan.

Perbaikan yang dilakukan:
1. Menghilangkan pre-authorization duplikat pada command benchmark.
2. Menjadikan `ReportController::exportPdf()` sebagai batas authorization yang diuji.
3. Menyesuaikan assertion dengan policy discovery Laravel yang sebenarnya (`ExamPolicy`), tanpa mock Gate atau ReportPolicy.
4. Mengobservasi evaluasi Gate secara pasif dan membuktikan database disposable masih ada saat policy menolak.
5. Membuktikan migration dan workload sudah tersedia sebelum authorization: tabel migration ada, Exam ter-seed, 100 peserta dan 100 attempt tersedia.
6. Memastikan cleanup tetap berjalan setelah `AuthorizationException`.

Run `45475336834` pada SHA `eb88399` masih gagal karena assertion memakai `Artisan::output()` untuk membuktikan migration/workload. Assertion tersebut diganti dengan bukti query database langsung pada callback policy.

### 3. Audit Import Siswa

Source aktif pada branch ini sudah mempunyai:
- XLSX dan CSV.
- Preview, validasi kolom/data wajib, duplikasi, dan laporan kesalahan baris.
- NISN 10 digit sebagai string.
- Idempotency berbasis tenant, rombel, dan file.
- Transaksi, rollback, retry deadlock, dan audit log.
- Scoping tenant dan validasi rombel/tahun ajaran.

Namun, implementasi belum memenuhi seluruh hardening yang diwajibkan:
- Batas ukuran 10 MB berada pada validasi Livewire; belum ada batas yang setara di service sebelum parser membaca file.
- XLSX memanggil `IOFactory::load()` sebelum pemeriksaan worksheet dimensions. Pemeriksaan row/column dilakukan setelah workbook dimuat.
- Belum ditemukan preflight ZIP entry count, total expanded size, per-entry expanded size, compression ratio, XML size, shared strings/styles, path traversal, DTD/entity, external relationship, macro/OLE, dan malformed ZIP sebelum `IOFactory::load()`.
- CSV memakai `fgetcsv()` tanpa batas panjang record; belum ada batas jumlah kolom dan ukuran sel pada jalur service.
- Belum ada test konkurensi dua import dengan NISN sama dan idempotency key sama.
- Password awal di-hash dan dibuat acak, serta Admin memiliki reset password. Namun, masa berlaku kredensial dan kewajiban mengganti password pada login pertama belum terbukti diterapkan.
- Test bernama `test_xlsx_worksheet_dimensions_are_rejected_before_import_preview_allocation` belum membuktikan pemeriksaan sebelum alokasi workbook karena pemanggilan `IOFactory::load()` terjadi lebih dahulu.

Student Import acceptance suite berisi 11 test dan 52 assertions pada exact source SHA yang diuji. Hasil PASS tidak menutup gap keamanan di atas.

### 4. Audit cabang importer lama

Branch `work/import-guru-siswa-implementation-20261122` pada SHA `f38ceec` mempunyai implementasi referensi:
- `ImportWorkbookService`.
- `ImportBatch`, `ImportBatchRow`, dan `ImportActivationToken`.
- Encrypted staging payload.
- Preview, validasi, audit, idempotency berbasis file, row-level processing, dan activation token 48 jam.
- XLSX ZIP preflight sebelum PhpSpreadsheet membaca workbook.

CI run `44949239676` pada exact SHA `f38ceec` berstatus SUCCESS. Branch tersebut terpisah/divergen dari branch kerja saat ini; tidak aman untuk cherry-pick atau merge seluruh branch tanpa audit dependency. Implementasinya hanya menjadi referensi selektif.

Hardening XLSX pada branch lama juga belum sepenuhnya memenuhi persyaratan terbaru: compression ratio belum dibatasi secara eksplisit dan worksheet bounds masih diperiksa setelah workbook dimuat. CSV adapter belum ditemukan.

### 5. Audit prasyarat DOCX dan tipe soal

Pemeriksaan tree lengkap pada branch kerja menemukan:
- Belum ada binary DOCX fixture di GitHub.
- Belum ada `QuestionDraftDTO`, `ImportBatch`, atau `ImportBatchRow` pada branch kerja ini.
- `ImportBatch`/`ImportBatchRow` hanya ditemukan pada branch importer lama.
- Belum ada model/schema first-class shared stimulus atau relasi stimulus ke question snapshot.
- Belum ada implementasi `TRUE_FALSE_GROUP` end-to-end pada persistence, answer validation, snapshot, runner, dan scoring.
- `MATCHING` masih tercantum pada QuestionBank dan jalur snapshot. Ini bertentangan dengan keputusan bahwa Matching tetap dinonaktifkan sampai runtime E2E terbukti.
- File `ExcelQuestionImporter` masih ada, tetapi tidak ditemukan route atau service binding aktif. Jangan mengaktifkan kembali XLSX question importer.
- `MULTIPLE_RESPONSE` dan `TRUE_FALSE` sudah mempunyai jalur backend; Essay memerlukan penilaian manual; `SHORT_ANSWER` sudah diblokir pada jalur snapshot/answer yang diperiksa.

Owner addendum DOCX menyetujui:
- `TRUE_FALSE_GROUP` tepat tiga pernyataan dengan ID stabil dan scoring proporsional.
- Shared stimulus harus menjadi entitas first-class, mempunyai versi/hash, relasi eksplisit, dan ikut immutable exam snapshot.

Metadata fixture pemilik mencatat nama `Kisi Kisi_Soal ASAS Matematia_B. Indo_kelas 6(2).docx`, ukuran 45.462 byte, dan SHA-256 yang tercantum pada addendum. Binary tidak ditemukan di GitHub tree dan raw bytes tidak tersedia sebagai file yang dapat dimaterialisasi pada sesi ini. Tidak ada fixture yang direkonstruksi dari hash atau ditebak.

## DESAIN

### Benchmark

- Authorization report harus tetap berada pada controller/policy aplikasi yang sebenarnya.
- Command benchmark tidak boleh membuat denial palsu melalui mock Gate atau ReportPolicy.
- Disposable database harus tetap dibuktikan dari CREATE, migration/workload, policy denial, DROP, dan pemeriksaan INFORMATION_SCHEMA.

### Import Siswa — urutan perbaikan berikutnya

1. Tambahkan service-level upload envelope validation sebelum parser membaca file.
2. Tambahkan ZIP package preflight untuk XLSX: entry count, expanded size, compression ratio, XML limits, shared strings/styles, path safety, DTD/entity, external relationships, macro/OLE, dan malformed archive.
3. Periksa worksheet dimension dan cell reference secara streaming/sebelum `IOFactory::load()`; jangan hanya mengandalkan worksheet dimension setelah workbook dimuat.
4. Batasi CSV: file bytes, record bytes, rows, columns, cell bytes, encoding, dan malformed record.
5. Tambahkan test malicious-file dan concurrency MySQL 8/InnoDB.
6. Selesaikan credential issuance dengan mekanisme satu kali/bermasa berlaku atau mekanisme setara, tanpa menyimpan password/token plaintext, serta audit dan perlindungan login pertama.
7. Jalankan browser UAT Admin dan regression.

Pola activation token 48 jam pada branch importer lama dapat dijadikan referensi, tetapi harus disesuaikan dengan exact-role, tenant context fail-closed, dan login identity contract terbaru.

### DOCX — sesudah Import Siswa lulus

- Port/adapt batch lifecycle yang disepakati setelah memeriksa kompatibilitas; jangan merge branch importer lama secara keseluruhan.
- Gunakan satu `QuestionDraftDTO` dan satu lifecycle batch untuk question import.
- Parser DOCX harus mempertahankan order, source locator, provenance, warning/error, media, table, Arabic/RTL/harakat, dan math yang didukung.
- Ambiguitas menjadi `NEEDS_REVIEW` atau `BLOCKED`; tidak boleh menebak kunci atau membuang konten.
- Shared stimulus dan `TRUE_FALSE_GROUP` baru boleh dipublikasikan setelah jalur persistence, snapshot, runner, validation, dan scoring benar-benar teruji.
- Matching tetap disabled; XLSX question importer tidak diaktifkan kembali.

## IMPLEMENTASI

### Source yang diubah

1. `backend-laravel/app/Console/Commands/Phase7dBenchmarkCommand.php`
   - Menghapus pre-authorization Gate duplikat.
   - Authorization report tetap dijalankan oleh controller sebenarnya.

2. `backend-laravel/tests/Feature/Phase7dBenchmarkCommandSafetyTest.php`
   - Memperbaiki policy expectation.
   - Menambahkan observasi schema ketika authorization dievaluasi.
   - Membuktikan migration dan workload melalui query MySQL.
   - Memastikan cleanup dan ketiadaan schema setelah denial.

### Backup dan manifest

Backup baru dibuat sebelum setiap perubahan source dan diverifikasi ulang melalui Git blob SHA.

| File backup | Git blob SHA | SHA-256 |
|---|---|---|
| `Phase7dBenchmarkCommand.php.before_38ab6a0` | `0cbb25215108350ea4b9874bbbdbe4e0d874df16` | `1b22e1a468bdf7f5f745eceeaf8fb9b9a2fc0d0f3f78f994e2d4d13828560375` |
| `Phase7dBenchmarkCommandSafetyTest.php.before_38ab6a0` | `f870b226f1e114183ca81a2d2111570bb29953b6` | `90078585e92b30550284404d28164e8482eb170c743bbeda5f9d8ce9e73abd2b` |
| `Phase7dBenchmarkCommandSafetyTest.php.before_evidence_assertions_eb883994` | `a71c5bfb2e282303a289a9fa2588f02b531494ee` | `926cc3986fafc91b7aa065d8119c44dfd4baa1225af8e23ec356017204610329` |

Manifest:
- `backend-laravel/backups/benchmark-cleanup-proof-20261125/BACKUP_MANIFEST_POLICY_DENIAL_REPAIR_38AB6A0.md`
- `backend-laravel/backups/benchmark-cleanup-proof-20261125/BACKUP_MANIFEST_RUNTIME_EVIDENCE_EB883994.md`

Manifest runtime evidence mempunyai koreksi checksum; versi sebelumnya dipertahankan byte-exact sebagai `BACKUP_MANIFEST_RUNTIME_EVIDENCE_EB883994.md.before_sha256_correction`.

### Perubahan yang tidak dilakukan

- `ReportPolicy`, `TeacherAccess`, dan `validateMysqlBenchmarkSafety()` tidak diubah.
- Tidak ada migration yang diubah atau dijalankan.
- Tidak ada database produksi yang diakses.
- Tidak ada akses/perubahan VPS, deployment, merge PR, atau ZIP final.

## PENGUJIAN

CI: [Run #45476011304](https://github.com/zane0184/cbt_november/actions/runs/45476011304)  
Exact tested SHA: `b6148507bd77116dfc8a5f5697cf4cff226a4b87`

| Job | PHP | MySQL | Hasil |
|---|---:|---|---|
| `138737210301` | 8.3 | 8.0.46 / InnoDB disposable | SUCCESS |
| `138737210817` | 8.4 | 8.0.46 / InnoDB disposable | SUCCESS |

Kedua job:
- Composer lock validation: PASS.
- Laravel runtime preparation: PASS.
- PHP lint aplikasi, migration, dan test: PASS.
- Student Import acceptance: 11 passed (52 assertions).
- Benchmark denial-path: 1 passed (20 assertions).
- Full PHPUnit: 596 passed, 1 skipped (2.825 assertions).

Satu test yang berstatus skipped tidak dihitung sebagai PASS. Tidak ditemukan kegagalan pada kedua job. Peringatan timezone/MySQL container tidak menggagalkan test.

## CHECKPOINT

### Selesai
- Diagnosis kegagalan benchmark pada branch kerja.
- Perbaikan test agar melewati controller/policy sebenarnya.
- Pembuktian disposable schema CREATE → migration/workload → authorization denial → DROP → INFORMATION_SCHEMA.
- Exact source SHA `b614850` lulus CI PHP 8.3 dan PHP 8.4 dengan MySQL 8.0/InnoDB.
- Backup source dan manifest tersedia; checksum manifest yang keliru sudah dikoreksi dan versi lama dipertahankan.

### Belum selesai
- Import Siswa belum memenuhi XLSX/CSV security hardening dan credential issuance.
- Import Guru belum diselesaikan pada branch kerja ini.
- DOCX importer belum diimplementasikan.
- Shared stimulus dan TRUE_FALSE_GROUP belum tersedia end-to-end.
- Matching masih perlu diblokir sesuai keputusan owner.
- Web Admin/Guru/Siswa, audit integrasi, staging VPS, dan ZIP distribusi belum melewati seluruh acceptance gates.

### Langkah berikutnya
Mulai Tahap Student Import Hardening. Sebelum patch, catat HEAD terbaru, buat backup byte-exact untuk `StudentImportService.php`, `StudentImportTest.php`, Livewire StudentImport, dan file credential/auth terkait. Setelah itu implementasikan preflight XLSX/CSV, credential issuance, concurrency tests MySQL, dan UAT Admin.

**Status benchmark: PASS. Status Import Siswa: PARTIAL / NOT APPROVAL READY. Status proyek keseluruhan: IN PROGRESS, NOT PRODUCTION READY.**
