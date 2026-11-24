# CHECKPOINT KOORDINATOR — 25 NOVEMBER 2026

Repository: zane0184/cbt_november
Audit branch: audit/takeover-readonly-2026-11-25
Audit baseline: work/benchmark-cleanup-proof-20261125
Source SHA audit: b6148507bd77116dfc8a5f5697cf4cff226a4b87
PR #23: OPEN / DRAFT / NOT MERGED
Main: b29e1e0ec7f14a46515cddb29e306ddb1d5f1ebb

## Posisi yang benar-benar diverifikasi
- Branch work/benchmark-cleanup-proof merupakan turunan langsung PR #23 head dan 73 commit ahead, 0 behind.
- CI exact SHA b614850: PHP 8.3 + PHP 8.4, MySQL 8.0/InnoDB disposable; masing-masing 596 passed, 1 skipped, 0 failed, 2.825 assertions.
- StudentImportTest: 11 passed / 52 assertions pada PHP 8.3.
- Benchmark cleanup denial-path PASS; temporary schema absence diverifikasi.
- PR #23 belum menunjuk SHA b614850; PR tetap Draft.
- ZIP build pada SHA d2127a6 menghasilkan artifact 14942243969 (19.802.163 bytes; digest sha256:c66ef3a48f1522ecf90c000be7fb4fb95e7b4d573991e04f8df06842bb9176ef). Build/archive saja, bukan acceptance ZIP.
- Tidak ada build ZIP terverifikasi pada exact latest source SHA b614850.

## Blocker utama
1. Student Import XLSX: IOFactory::load berjalan sebelum OOXML ZIP preflight; batas worksheet baru diperiksa setelah load. CSV belum membatasi jumlah kolom/panjang record. Security P0 belum diterima.
2. DOCX question parser belum ada. QuestionImportService masih interface; engine belum memiliki TRUE_FALSE_GROUP/shared stimulus first-class. Owner addendum 25 November menetapkan kontrak data dan scoring.
3. PR #13 Web Siswa berada pada lineage lama dan membawa ExamSession/RoomSession/session lifecycle. Jangan merge wholesale. Runner browser E2E menggunakan mock; browser-to-real-Laravel belum terbukti.
4. Kurikulum provenance Pembelajaran Mendalam/8 dimensi dan immutable QuestionVersion-to-Exam snapshot belum selesai.
5. Staging VPS dan acceptance ZIP latest SHA belum diverifikasi.

## Keputusan owner yang harus dipertahankan
- Instalasi dan database akademik terpisah per madrasah; bukan SaaS lintas madrasah.
- Portal aktif ADMIN_MADRASAH, GURU, SISWA.
- Android dan Web Super Admin pusat ditunda sampai Juni 2027.
- DOCX import soal; jangan mengaktifkan XLSX question import.
- Admin username; Guru PNS/PPPK NIP 18 digit; Guru Honorer NIK 16 digit; Siswa NISN 10 digit. Guru tidak memakai username/NUPTK fallback.
- Ujian mengikuti Exam schedule dan waktu server; tidak menggunakan ExamSession/SessionRoom/session_number/wave sebagai admission gate.
- PR #23 tetap Draft; tidak merge/deploy/migrate production tanpa approval.

## Next task
P0 Student Import security hardening:
- Buat branch kerja baru dari b614850.
- Backup byte-exact StudentImportService.php dan StudentImportTest.php sebelum patch.
- Tambahkan preflight ZIP/OOXML sebelum IOFactory::load: entry count, per-entry/total expanded size, compression ratio, path traversal, XML size/DTD/ENTITY/external relationships dan required OOXML parts.
- Batasi CSV record length, column count, row count dan ukuran input.
- Tambahkan negative tests untuk ZIP/XML bomb, oversized entries, external relationships, path traversal, malformed OOXML, CSV extreme columns/long rows.
- Jalankan focused test dan full CI pada PHP 8.3/8.4 dengan disposable MySQL 8/InnoDB.
- Jangan menjalankan migration/database produksi; jangan deploy VPS.
- Setelah patch, update report/checkpoint dan backup manifest.

## Status
Audit takeover: SELESAI.
Student Import security: BLOCKED sampai patch dan test.
DOCX parser: BELUM DIIMPLEMENTASIKAN.
ZIP SIAP UJI OPERASIONAL TERBATAS: BELUM.
