# P1 Audit — Database Enforcement Identitas Login

Tanggal: 24 November 2026  
Repository: `zane0184/cbt_november`  
Branch: `work/p1-seven-targets-20261124`

## A. AUDIT

### 1. Enforcement yang tersedia sekarang

- `2026_11_24_170001_enforce_tenant_login_identity_uniques.php` memeriksa schema, blank/whitespace, duplicate per tenant, dan index definition sebelum menambah unique index NIP, NIK, NUPTK, NISN.
- Migration tersebut **belum** memaksa kategori/format identity pada raw SQL INSERT/UPDATE.
- `LoginIdentityPreflightCommand` mendeteksi status kosong/tidak dikenal, format NIP/NIK/NUPTK/NISN, kategori yang salah, employee_identifier mismatch, blank/padded value, duplicate, role/profile cardinality, tenant mismatch, dan username Admin. Command bersifat read-only; temuan tidak mencegah write berikutnya.
- `UserManagement::rules()` dan `createUser()` memvalidasi identity pada jalur UI Admin. Ini tidak melindungi raw SQL atau jalur penulisan lain.
- `TeacherProfile` dan `StudentProfile` tidak memiliki DB-level category/format enforcement.
- `UserManagement` saat ini memiliki jalur create akun Guru/Siswa dan audit event. `TeacherProfilePage::savePhone()` hanya memperbarui nomor telepon; tidak ditemukan jalur identity update atau bulk import profile pada inventory app path yang diperiksa. Ini harus diaudit kembali saat endpoint baru ditambahkan.
- `StudentRombelManagement` mengelola penempatan rombel, bukan membuat profile baru.
- `AuditPayloadSanitizer` sudah menyamarkan email, phone, NIP/NIK/NISN/NUPTK, employee_identifier, dan key sensitif lain; create account event menyimpan flag keberadaan identifier, bukan nilainya. Catatan PR lama tentang audit creation yang hilang sudah superseded oleh source aktual.

### 2. Dampak pada test fixture

Pemeriksaan test Feature menemukan fixture profile lama yang belum mengikuti kontrak login wajib, termasuk pada:
- `TeacherPracticeWorkflowTest`
- `ExamManagementHardeningTest`
- `ExamSnapshotTest`
- `ExamIntegrityTest`
- `ExamAnswerTest`
- `QuestionBankTest`
- `ProctorAssignmentSecurityTest`
- `MonitoringTest`
- `MonitoringDuplicationTest`
- `RoleDashboardVerificationTest`
- `ExamListContractTest`
- `ExamVerificationTest`
- `Phase7DHistoricalRecalculationAndAnalyticsTest`
- `Phase7BManualReviewTest`
- `MediaAuthorizationTest`
- `ExamBuilderTest`
- `Analytics/CacheTest`
- `Phase7CReopenTest`
- `AdminUiVerificationTest`

Daftar ini adalah inventory temuan saat audit, bukan klaim bahwa hanya file-file tersebut yang perlu diperbarui. Sebagian fixture memang sengaja malformed untuk menguji preflight/privasi/kategori; pengujiannya perlu dipisahkan dari jalur penulisan normal.

### 3. Kontrak identitas owner yang berlaku

- Admin Madrasah: username.
- Guru PNS/PPPK: NIP tepat 18 digit, NIK tidak boleh terisi.
- Guru Honorer: NIK tepat 16 digit, NIP tidak boleh terisi.
- NUPTK opsional, jika terisi tepat 16 digit.
- Siswa: NISN tepat 10 digit.
- employee_identifier Guru harus sama dengan NIP atau NIK yang menjadi login identifier.
- Uniqueness tetap tenant-scoped.

## B. DESAIN

Target enforcement:
1. MySQL 8: CHECK constraints untuk kategori/format identitas, setelah read-only preflight memastikan data existing bersih. DDL harus fail-closed jika versi/driver tidak mendukung enforced CHECK.
2. SQLite: BEFORE INSERT/UPDATE triggers dengan kondisi setara, karena ALTER TABLE ADD CONSTRAINT tidak kompatibel.
3. Tidak ada auto-correction, trimming, penebakan status, atau pembuatan identitas dummy untuk data produksi.
4. Migration memeriksa seluruh kondisi existing sebelum DDL pertama; jika ada anomali, migration berhenti dan tidak mengubah data.
5. Test normal menggunakan identity sintetis yang valid. Test legacy/preflight yang sengaja malformed harus memakai fixture terisolasi dan mekanisme test-only yang tidak tersedia di production.
6. Tambahkan raw SQL INSERT dan UPDATE tests yang membuktikan invalid category/format ditolak database, sementara data valid diterima.
7. Jalur create/update/import/recovery aplikasi harus memakai validasi domain yang sama; UI validation saja tidak dianggap cukup.

## C. IMPLEMENTASI

Belum ada source atau migration yang diubah pada workstream audit ini. Tidak ada database yang disentuh. Tahap berikutnya adalah implementasi migration dan perbaikan fixture secara terukur dengan backup byte-exact sebelum setiap file lama diubah.

## D. PENGUJIAN

- Pemeriksaan source dan migration melalui GitHub: selesai.
- Test baru untuk DB CHECK/trigger: belum dibuat.
- MySQL 8 disposable migration: belum dijalankan.
- SQLite trigger compatibility: belum dijalankan.
- Production/VPS: tidak disentuh.

## E. RISIKO DAN PENGAMAN

- CHECK constraints harus diuji pada MySQL 8.0.46/InnoDB dan harus menolak invalid raw SQL.
- SQLite trigger harus diuji terpisah; jangan menganggap kompatibel hanya dari sintaks.
- Preflight yang menemukan data lama harus memblokir migration; tidak boleh ada auto-fix.
- Test legacy yang menonaktifkan constraint hanya boleh berlangsung di database test disposable dan wajib memulihkan setting setelah test.
- Tidak ada migration produksi tanpa backup, preflight, rencana rollback/forward recovery, dan persetujuan owner.

## F. STATUS

P1 identity uniqueness: sudah ada migration, belum mengunci category/format.  
P1 identity DB enforcement: **AUDIT COMPLETE / IMPLEMENTATION PENDING**.  
P1-ID-03B: tetap BLOCKED sampai desain, disposable test, dan persetujuan owner lengkap.
