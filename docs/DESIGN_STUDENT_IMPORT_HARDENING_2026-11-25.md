# Desain Tahap Student Import Hardening — 25 November 2026

Status: DESAIN AWAL, belum diimplementasikan.  
Repository: `zane0184/cbt_november`  
Branch: `work/benchmark-cleanup-proof-20261125`  
Acuan source: branch HEAD sebelum patch Student Import berikutnya; benchmark exact source SHA `b6148507bd77116dfc8a5f5697cf4cff226a4b87` sudah PASS.

## 1. Tujuan

Melengkapi Student Import XLSX/CSV yang sudah ada tanpa mengganti alur preview, validasi, idempotency, transaksi, audit, dan tenant scope yang sudah lulus acceptance test. Hardening harus terjadi di service sebelum parser membaca file; validasi Livewire saja bukan batas keamanan.

Tidak ada dependency baru. Gunakan `ZipArchive`, `XMLReader`, dan PhpSpreadsheet yang sudah ada di lockfile.

## 2. Batas input yang diusulkan

Batas ini menjadi konfigurasi terpusat, bukan angka yang tersebar pada UI dan service:

| Batas | Nilai awal |
|---|---:|
| Upload file | 10 MiB |
| ZIP entry count | 200 |
| Total expanded ZIP bytes | 25 MiB |
| Expanded size per entry | 10 MiB |
| Compression ratio per entry | 100:1 |
| Worksheet XML | 10 MiB |
| Shared strings XML | 10 MiB |
| Styles XML | 2 MiB |
| Jumlah baris data | 5.000 |
| Jumlah kolom | 32 |
| Jumlah cell maksimum | 160.000 |
| Ukuran satu cell | 4 KiB |
| Satu CSV record | 256 KiB |
| Waktu parsing maksimum | 30 detik |

Batas harus diuji memakai workbook valid terbesar yang masih berada di bawah limit. Jika memory budget runtime tidak cukup, proses berhenti dengan pesan yang jelas sebelum PhpSpreadsheet dimuat. Jangan menaikkan memory/time limit tanpa batas.

## 3. XLSX package preflight — wajib sebelum IOFactory

Buat satu guard terpusat, misalnya `XlsxPackagePreflight`, dengan urutan:

1. Validasi ukuran file dan signature ZIP.
2. Buka ZIP secara read-only; jangan extract ke filesystem.
3. Batasi entry count, expanded size total/per-entry, dan compression ratio.
4. Tolak nama entry absolut, path traversal, backslash/path ambigu, duplicate normalized path, symlink, entry terenkripsi, serta package part yang tidak diizinkan.
5. Tolak macro/VBA, OLE/embedding, externalLinks, external relationship, dan konten eksternal.
6. Batasi ukuran XML, shared strings, styles, dan worksheet sebelum membaca kontennya.
7. Parse XML menggunakan XMLReader dengan `LIBXML_NONET`, DTD loading off, entity substitution off; tolak DOCTYPE/ENTITY dan malformed XML.
8. Resolve worksheet aktif melalui `xl/workbook.xml` dan relationship yang valid.
9. Scan worksheet dimension dan setiap cell reference sebelum `IOFactory::load()`; tolak row/column/cell count di atas limit.
10. Hanya setelah seluruh preflight lulus, buat Xlsx reader dengan `setReadDataOnly(true)`, load sheet yang dipilih, dan gunakan read filter pada kolom yang diperlukan.

Jangan mengandalkan `getHighestDataRow()` setelah workbook dimuat sebagai perlindungan terhadap memory exhaustion. Itu hanya validasi pasca-load.

## 4. CSV streaming guard

- Validasi ukuran file di service, bukan hanya Livewire.
- Gunakan pembacaan record dengan batas byte dan hitung byte yang benar-benar dikonsumsi, termasuk multiline quoted field.
- Batasi rows, columns, total cells, dan ukuran setiap cell.
- Validasi UTF-8 dan tolak NUL/control bytes yang tidak didukung.
- Deteksi delimiter secara bounded; jangan membaca seluruh file untuk menebak delimiter.
- Tolak record yang terpotong akibat batas byte; jangan menganggap fragmen sebagai row baru.
- Terapkan aturan header yang sama dengan XLSX.

## 5. Authorization, tenant, transaksi, dan concurrency

- Service preview/import harus memvalidasi actor aktif, exact role `ADMIN_MADRASAH`, permission import/user management yang ditetapkan, dan TenantContext yang harus cocok dengan tenant actor.
- Missing tenant context harus fail-closed.
- Pertahankan lock rombel/batch, unique tenant-scoped NISN, unique idempotency key, dan transaksi all-or-nothing.
- Test dua koneksi/proses MySQL untuk NISN sama dan idempotency key sama; hasil akhirnya tidak boleh membuat user/profile/batch ganda.
- Audit failure dan DB failure harus rollback seluruh import.
- NISN selalu string 10 digit; jangan menerima konversi angka yang menghilangkan leading zero.

## 6. Credential issuance

Reset password yang ada menghasilkan temporary password tetapi belum menunjukkan expiry/first-login enforcement. Desain yang lebih aman adalah one-time activation/reset token:

- Token random hanya ditampilkan satu kali; DB menyimpan hash/verifier saja.
- Token terikat pada user + tenant + purpose + actor yang menerbitkan.
- Expiry awal 48 jam, single-use, dapat dicabut, dan tidak dapat dipakai lintas tenant.
- Akun baru tidak dapat login sampai activation selesai; pengguna menetapkan password sendiri sebelum login pertama.
- Reset oleh Admin hanya melalui authorization exact-role dan tercatat di audit log.
- Tidak ada password massal plaintext dalam CSV/XLSX laporan, log, atau audit payload.
- Gunakan pola `ImportActivationToken` pada branch referensi lama setelah port selektif dan audit kompatibilitas; jangan merge branch lama secara keseluruhan.

## 7. Matriks test wajib

### XLSX
- Valid normal dan workbook terbesar yang diizinkan.
- Upload terlalu besar.
- ZIP rusak/malformed.
- Entry count berlebih.
- Expanded size total/per-entry berlebih.
- Compression ratio berlebih.
- Path traversal, duplicate entry, symlink, encrypted entry.
- DTD/ENTITY/XML bomb dan malformed XML.
- External relationship, macro/VBA, OLE/embedding.
- Shared strings/styles/worksheet XML melebihi batas.
- Worksheet dimension palsu atau ekstrem yang ditolak sebelum IOFactory.
- Cell reference aktual melampaui row/column/cell limit.

### CSV
- Valid UTF-8, BOM, CRLF/LF, quoted delimiter, multiline quoted field.
- File/record/cell/row/column terlalu besar.
- Record terpotong, malformed quoting, invalid UTF-8, NUL byte.
- Direct service invocation tanpa Livewire validation.

### Database/security
- Duplicate NISN dan duplicate row dalam file.
- Dua import bersamaan dengan NISN sama.
- Dua import bersamaan dengan idempotency key sama.
- DB failure di tengah transaksi.
- Audit log failure.
- Unauthorized role, inactive actor, missing TenantContext, tenant mismatch.
- Credential token expired/reused/revoked/wrong tenant.
- Password/token plaintext tidak disimpan atau dilog.

Semua integration/concurrency test menggunakan MySQL 8/InnoDB disposable. SQLite hanya pelengkap.

## 8. File target dan aturan patch

Target awal:
- `backend-laravel/app/Services/StudentImportService.php`
- `backend-laravel/tests/Feature/StudentImportTest.php`
- `backend-laravel/app/Livewire/Admin/StudentImport.php`
- Credential/auth service, controller, middleware, route, model, migration, dan UI yang benar-benar diperlukan setelah audit dependency.

Sebelum perubahan:
1. Catat exact branch HEAD.
2. Backup byte-exact setiap file yang akan berubah.
3. Re-fetch backup dan cocokkan Git blob SHA.
4. Hitung SHA-256 dan tulis manifest.
5. Patch minimal, review diff, jalankan test terarah, regression, lalu CI PHP 8.3/8.4 + MySQL 8.

Tidak ada migration produksi. Tidak ada akses VPS/deployment pada tahap ini.

## 9. Gate kelulusan

Student Import baru boleh dinyatakan selesai jika seluruh XLSX/CSV malicious-file tests, concurrency, idempotency, rollback, audit, authorization/tenant, credential lifecycle, MySQL 8/InnoDB, dan browser UAT Admin lulus pada exact SHA. Setelah itu baru mulai DOCX.
