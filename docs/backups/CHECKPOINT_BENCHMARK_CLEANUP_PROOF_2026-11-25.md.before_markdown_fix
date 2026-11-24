# Checkpoint CBT Madrasah — 25 November 2026

Repository: `zane0184/cbt_november`  
Branch: `work/benchmark-cleanup-proof-20261125`  
Baseline: `d7a33b73e32d42064a31f4ef29c01266121d29f0`  
Source SHA teruji: `b6148507bd77116dfc8a5f5697cf4cff226a4b87`  
HEAD branch sebelum checkpoint ini: `db78662b2e43a068448d8d55ddf71bfa95e49d06`  
Laporan lengkap: [REPORT_BENCHMARK_CLEANUP_PROOF_2026-11-25.md](REPORT_BENCHMARK_CLEANUP_PROOF_2026-11-25.md)

## Posisi terakhir yang benar-benar terbukti

### 1. Benchmark cleanup denial-path
<badge>PASS</badge>

CI run [45476011304](https://github.com/zane0184/cbt_november/actions/runs/45476011304) pada exact source SHA `b6148507bd77116dfc8a5f5697cf4cff226a4b87`:
- PHP 8.3 + MySQL 8.0.46/InnoDB: SUCCESS.
- PHP 8.4 + MySQL 8.0.46/InnoDB: SUCCESS.
- Full PHPUnit per job: 596 passed, 1 skipped, 2.825 assertions.
- Student Import acceptance per job: 11 passed, 52 assertions.
- Benchmark denial-path per job: 1 passed, 20 assertions.

Branch HEAD setelah source test hanya menerima perubahan dokumentasi/backup manifest; source aplikasi tidak berubah setelah SHA teruji.

### 2. Import Siswa
<badge>PARTIAL</badge>

Fitur XLSX/CSV, preview, NISN string, duplikasi, idempotency, transaksi, rollback, audit, dan tenant scope sudah ada. Belum approval-ready karena:
- XLSX package preflight belum berjalan sebelum `IOFactory::load()`.
- ZIP entry count, expanded size, compression ratio, XML/sharedStrings/styles limits, DTD/entity, path traversal, external relationship, macro/OLE, dan malformed archive belum dibuktikan.
- CSV belum membatasi record bytes, columns, dan cell bytes pada service.
- Belum ada concurrent import test untuk NISN/idempotency.
- Credential expiry dan first-login password change belum diterapkan/dibuktikan.

Jangan mulai DOCX sebelum Import Siswa memenuhi gate ini.

### 3. DOCX
<badge>NOT STARTED</badge>

- Owner addendum menetapkan shared stimulus first-class dan TRUE_FALSE_GROUP tiga pernyataan dengan scoring proporsional.
- Current branch belum memiliki QuestionDraftDTO, ImportBatch/ImportBatchRow, shared stimulus schema, atau TRUE_FALSE_GROUP end-to-end.
- Matching masih dapat dipilih pada QuestionBank/snapshot, padahal harus disabled sampai runtime E2E.
- XLSX question importer lama tidak boleh diaktifkan kembali.
- Binary fixture belum berada di GitHub; raw bytes belum tersedia di current runtime. Jangan membuat fixture dari asumsi/hash.

### 4. Import Guru
<badge>NOT STARTED ON ACTIVE BRANCH</badge>

Branch lama `work/import-guru-siswa-implementation-20261122` mempunyai implementasi referensi yang teruji pada SHA `f38ceec7b0653fcf3363eec40f3d4577c323cca8`, tetapi branch tersebut divergen. Gunakan hanya komponen/pola yang sudah diaudit; jangan merge/cherry-pick keseluruhan branch.

## Aturan keselamatan yang tetap berlaku

- PR #23 tetap OPEN/DRAFT/UNMERGED. Jangan merge, tutup, atau ubah base.
- Tidak ada akses atau perubahan VPS.
- Tidak ada migration produksi atau koneksi ke database produksi.
- Tidak ada source patch tanpa backup byte-exact dan manifest SHA-256.
- Jangan mengklaim Import Siswa, DOCX, Import Guru, Device Fencing, atau aplikasi siap produksi hanya dari hasil benchmark/PHPUnit.
- Android dan Web Super Admin tetap ditunda sampai Juni 2027 sesuai keputusan owner.

## Tindakan berikutnya

1. Audit ulang exact HEAD branch ini sebelum patch.
2. Backup source StudentImport, test, Livewire component, credential service/UI, dan auth flow.
3. Implementasikan XLSX/CSV preflight di service sebelum parser.
4. Tambahkan malicious-file, concurrency, rollback, audit failure, dan tenant/RBAC tests pada MySQL 8/InnoDB.
5. Implementasikan credential issuance yang aman dan memiliki expiry/aktivasi.
6. Jalankan CI PHP 8.3 dan 8.4, lalu browser UAT Admin.
7. Baru setelah Import Siswa lulus, lanjutkan desain/implementasi DOCX.

**Checkpoint: BENCHMARK COMPLETE; STUDENT IMPORT HARDENING NEXT; PROJECT NOT PRODUCTION READY.**
