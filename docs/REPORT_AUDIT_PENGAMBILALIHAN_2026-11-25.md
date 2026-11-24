# LAPORAN AUDIT PENGAMBILALIHAN CBT MADRASAH
Tanggal: 25 November 2026
Repository: zane0184/cbt_november
Branch audit: audit/takeover-readonly-2026-11-25
Baseline audit source: work/benchmark-cleanup-proof-20261125
Exact source SHA: b6148507bd77116dfc8a5f5697cf4cff226a4b87
PR integrasi #23: OPEN / DRAFT / NOT MERGED

## Ringkasan eksekutif

Audit read-only menemukan bahwa pekerjaan terbaru berada pada branch work/benchmark-cleanup-proof-20261125, bukan pada head PR #23. Branch tersebut merupakan turunan langsung dari head PR #23 dan memiliki 73 commit tambahan, tanpa tertinggal dari head PR #23. CI pada SHA b6148507bd77116dfc8a5f5697cf4cff226a4b87 lulus pada PHP 8.3 dan 8.4 dengan MySQL 8.0/InnoDB disposable: masing-masing 596 passed, 1 skipped, 0 failed, 2.825 assertions.

Namun proyek belum siap uji operasional terbatas. Ditemukan blocker P0 pada keamanan import XLSX Siswa: PhpSpreadsheet IOFactory::load dipanggil sebelum pemeriksaan struktur/ukuran ZIP OOXML; batas dimensi worksheet diperiksa setelah workbook dimuat. Pembacaan CSV juga belum membatasi jumlah kolom atau panjang record secara eksplisit. Import Siswa memiliki acceptance test, tetapi belum memiliki bukti perlindungan terhadap ZIP/XML bomb dan input berbahaya.

DOCX question parser belum diimplementasikan. Source QuestionImportService hanya interface; ExcelQuestionImporter masih merupakan row importer, bukan parser DOCX. Kontrak owner terbaru tanggal 25 November telah menetapkan format DOCX fleksibel, TRUE_FALSE_GROUP tiga pernyataan, scoring proporsional, dan shared stimulus first-class; implementasi serta test engine/parser belum ada.

## 1. AUDIT — Repository, branch, PR, dan Actions

### Repository dan branch
- Repository publik: https://github.com/zane0184/cbt_november
- Default branch: main, HEAD b29e1e0ec7f14a46515cddb29e306ddb1d5f1ebb.
- Ditemukan 87 branch.
- Head PR #23: work/p1-seven-targets-20261124 @ d2127a6e6663ebcf4c9107e06dfc6b1f231c7241.
- Branch kerja terbaru yang diperiksa: work/benchmark-cleanup-proof-20261125 @ b6148507bd77116dfc8a5f5697cf4cff226a4b87.
- Compare menunjukkan branch benchmark-cleanup-proof 73 commit ahead dan 0 behind dari head PR #23. Perubahan lanjutan mencakup Student Import, workflow CI, benchmark cleanup, backup, dan dokumentasi DOCX.
- Branch work/docx-import-parser-20261125 pada SHA d7a33b73e32d42064a31f4ef29c01266121d29f0 mengalami CI failure pada test benchmark cleanup. Perbaikan lanjutan pada branch benchmark-cleanup-proof menghasilkan CI PASS.

### Pull request
- PR #23 tetap OPEN / DRAFT / UNMERGED; head d2127a6, base work/phpunit-41-root-cause-20261123. Tidak diubah.
- PR #22 tetap OPEN / DRAFT; P0 base exact 1c33e5d422175f9bd85d206230271a9fe5a4eecd memiliki bukti 518 passed / 0 failed / 2.189 assertions pada PHP 8.3 + disposable MySQL 8.0.46/InnoDB.
- PR #13 Web Siswa tetap OPEN / DRAFT dan berada pada lineage/base lama work/stage4d-account-lifecycle-20261122. Tidak aman digabung wholesale: compare memperlihatkan source tambahan ExamSession, ExamRoomSession, ExamSessionLifecycleService, ExamRoomSessionService, dan migration session-room. Ini bertentangan dengan keputusan owner bahwa ExamSession/SessionRoom/session lifecycle bukan admission gate. Runner client hanya boleh dipertimbangkan untuk port selektif setelah audit per file.
- PR #19 Web Guru remove XLSX question import masih OPEN / DRAFT. XLSX tidak boleh dihidupkan kembali sebagai importer soal; XLSX/CSV tetap dapat digunakan untuk import/export data administrasi.
- PR #17 dokumentasi kontrak Guru masih OPEN / DRAFT. Issue #14 dan #15 tetap menjadi dependency provenance/mapping kurikulum.

### GitHub Actions — exact SHA
- PR #23 head d2127a6: Tenant Backend CI PASS, 586 passed / 0 failed / 2.775 assertions pada MySQL 8.0.46/InnoDB; workflow build deployment ZIP SUCCESS.
- Branch b614850: run 45476011304, kedua job PHP 8.3 dan PHP 8.4 SUCCESS.
- Full PHPUnit pada kedua job: 596 passed, 1 skipped, 0 failed, 2.825 assertions.
- StudentImportTest pada PHP 8.3: 11 passed, 52 assertions.
- Benchmark cleanup denial-path test PASS; temporary schema diperiksa tidak tersisa melalui INFORMATION_SCHEMA.
- Workflow bernama DOCX Import Parser CI belum menguji parser DOCX. Job yang ada menjalankan StudentImportTest, benchmark cleanup test, dan full PHPUnit.
- CI failure lama run 45471267239 pada SHA d7a33b7: 595 passed, 1 failed, 1 skipped, 2.808 assertions; kegagalan berasal dari assertion harness output benchmark. Perbaikan pada b614850 telah lulus.

## 2. AUDIT — Source dan fitur

### Student Import XLSX/CSV — BLOCKER P0
Source aktual pada branch b614850:
- StudentImport Livewire membatasi upload 10 MB, exact role ADMIN_MADRASAH, permission user.manage, dan Rombel aktif dalam tenant.
- Service memvalidasi NISN sebagai string tepat 10 digit, leading zero, duplikasi file/database, transaksi, idempotency key, audit log, dan rollback.
- Test terarah pada exact branch CI: 11 passed / 52 assertions.

Temuan keamanan yang belum ditutup:
1. readRows() memanggil IOFactory::load($path) sebelum ZIP/OOXML preflight.
2. Batas worksheet 5.000 baris dan 32 kolom baru diperiksa setelah workbook dibuka dan dimuat.
3. Tidak ada pemeriksaan eksplisit jumlah ZIP entry, total/per-entry expanded bytes, compression ratio, path traversal, XML size, DTD/ENTITY, atau external relationships sebelum parser.
4. CSV memakai fgetcsv dengan panjang record 0 dan belum memiliki batas jumlah kolom yang eksplisit.
5. Belum ada test hostile OOXML/ZIP bomb dan CSV extreme-column/long-record.

Kesimpulan: fitur import siswa telah diimplementasikan dan acceptance fungsional lulus, tetapi keamanan file upload belum diterima. Jangan menyebut Student Import siap produksi sebelum preflight dan test keamanan lulus.

### DOCX Question Import — BELUM DIIMPLEMENTASIKAN
- QuestionImportService.php hanya interface.
- ExcelQuestionImporter adalah importer row-array; bukan parser DOCX dan tidak boleh dihidupkan sebagai jalur XLSX soal.
- Belum ditemukan parser DOCX, upload/preview/correction/confirm workflow, atau fixture binary DOCX pada CI.
- QuestionVersion belum memiliki relasi shared stimulus first-class.
- Exam snapshot belum memiliki kontrak stimulus-group immutable.
- Engine yang diaudit belum memiliki TRUE_FALSE_GROUP dengan tiga stable statement IDs.
- Owner addendum 25 November menetapkan TRUE_FALSE_GROUP tepat tiga pernyataan, jawaban boolean per ID, scoring question_points × correct_count / 3, serta shared stimulus dengan ID/versi/hash/urutan dalam snapshot immutable.
- Addendum owner menyatakan fixture DOCX adalah alat uji internal, bukan template wajib Guru atau sumber kurikulum. Kisi-kisi tidak boleh otomatis diimpor sebagai soal atau dijadikan sumber CP/TP/ATP.

### Web Admin
- ADM-02 profil madrasah: implementasi dan CI ada; masih PARTIAL karena integrasi kop ke laporan/PDF nyata belum dibuktikan.
- ADM-03 branding: implementasi ada; matriks 41 acceptance melaporkan 38 PASS, 1 legacy-only, 2 PARTIAL. HTTP Content-Type aktual PNG/WebP dari Nginx/aaPanel belum diuji staging.
- Account lifecycle Guru/Siswa: implementasi dan CI historis lulus; aktivasi/deaktivasi, reset password, pencabutan sesi/token, last_login_at dan audit event tersedia. Ini tidak berarti seluruh ADM-06 diterima.
- Student Import ada, tetapi security preflight masih BLOCKER.
- ADM-04/05 branding dan kop cetak, import Guru, export Guru/Siswa, lifecycle akademik penuh, laporan Admin, audit UI, backup/restore, dan beberapa CRUD masih PARTIAL/NOT VERIFIED.
- ADM-20/Proktor Mode B tetap BLOCKED sampai marker ujian pusat dan kontrak kewenangan yang disetujui tersedia.

### Web Guru
- Bank Soal, assignment, Exam Builder, publish/token/schedule, scoring dan result workflow memiliki source/test.
- DOCX importer belum ada.
- SHORT_ANSWER tetap BLOCKED.
- ESSAY tetap manual review.
- MATCHING import tetap nonaktif sampai runtime E2E sesuai keputusan owner.
- Immutable curriculum provenance sampai QuestionVersion dan Exam snapshot belum dibuktikan end-to-end.

### Web Siswa
- Branch PR #13 mempunyai runner IndexedDB, durable queue, conflict archive, submit barrier, unknown-submit recovery, Chromium dan WebKit-proxy browser tests.
- CI pada SHA 9ef375c melaporkan static/browser tests serta PHPUnit MySQL feature tests lulus.
- Browser E2E tersebut memakai mock API; PHPUnit MySQL berjalan sebagai job terpisah. Ini bukan browser-to-real-Laravel E2E.
- PR #13 tidak boleh digabung wholesale karena membawa session-room backend model yang bertentangan dengan keputusan owner.
- Runner pada branch PR #13 belum terintegrasi ke branch b614850. Porting harus selektif dan hanya untuk perubahan client yang lolos audit.

### Kurikulum Merdeka + KBC
- Fondasi registry, CP/TP/ATP, Panca Cinta, assignment dan mapping tersedia.
- Issue #14 mencatat mapping QuestionVersion dan immutable snapshot belum lengkap.
- Issue #15 mencatat belum ada artefak resmi/provenance terverifikasi untuk Pembelajaran Mendalam dan delapan dimensi profil lulusan.
- Jangan membuat data kurikulum sintetis atau menandai verified tanpa sumber resmi, versi, tanggal berlaku, dan checksum.

### Identitas login
Kontrak owner terbaru pada PR #23 body/addendum 24 November mengikat:
- Admin Madrasah: username.
- Guru PNS/PPPK: NIP wajib tepat 18 digit.
- Guru Honorer: NIK wajib tepat 16 digit.
- Siswa: NISN wajib tepat 10 digit.
- Guru tidak boleh fallback ke username atau NUPTK.
Dokumen checkpoint lama yang menyatakan NIP/NIK opsional adalah historis dan disupersede oleh addendum owner terbaru. PR #23 body juga mencatat database belum menegakkan kategori/format terhadap raw SQL write; ini tetap blocker integritas.

## 3. DESAIN — Prioritas dan keputusan

1. Pertahankan satu instalasi dan satu database akademik per madrasah. tenant_id/TenantContext lokal dipertahankan sebagai defense-in-depth/legacy, bukan SaaS antar-madrasah.
2. Portal aktif hanya ADMIN_MADRASAH, GURU, SISWA. Android dan Web Super Admin pusat deferred sampai Juni 2027.
3. Jangan merge PR #23 atau PR lain, jangan mengubah main, dan jangan mengubah deployment/VPS/production database tanpa persetujuan.
4. Pertahankan P0 base 1c33e5d dan jangan mengulang patch yang telah lulus.
5. Prioritas implementasi pertama setelah audit: Student Import OOXML/CSV security preflight karena merupakan jalur upload P0 dengan celah resource exhaustion.
6. Setelah Student Import security lulus, lanjutkan DOCX parser dan engine contract berdasarkan addendum owner 25 November.
7. Port Web Siswa runner secara selektif; jangan membawa ExamSession/SessionRoom/session lifecycle.
8. Lanjutkan kurikulum provenance, ADM backlog, browser-to-real-Laravel E2E, staging, dan ZIP acceptance.

## 4. IMPLEMENTASI
- Audit ini READ-ONLY terhadap source aplikasi.
- Tidak ada source aplikasi, migration, database, staging, VPS, atau konfigurasi produksi yang diubah.
- Tidak ada backup source baru karena tidak ada source yang dipatch pada tahap audit.
- Branch audit dokumentasi dibuat terpisah dari main dan PR #23.
- Tidak ada merge, cherry-pick, deployment, atau perubahan status PR.

## 5. PENGUJIAN
- GitHub Actions pada exact SHA b614850: PHP 8.3 dan 8.4, MySQL 8.0/InnoDB disposable; masing-masing 596 passed, 1 skipped, 0 failed, 2.825 assertions.
- StudentImportTest pada PHP 8.3: 11 passed, 52 assertions.
- Benchmark cleanup denial path: PASS; schema absence verified.
- Tidak ada staging/VPS test, browser-to-real-Laravel E2E, atau ZIP extraction acceptance pada exact SHA b614850.
- Tidak ada migration production atau database produksi yang disentuh.

## 6. STATUS
- Repository takeover audit: SELESAI.
- P0 backend regression suite pada branch b614850: PASS (bukti exact SHA).
- Student Import functional acceptance: PASS; security hardening: BLOCKED.
- DOCX parser: BELUM DIIMPLEMENTASIKAN.
- Web Admin/Guru/Siswa end-to-end: PARTIAL / NOT ACCEPTED.
- Curriculum provenance: PARTIAL / NOT VERIFIED.
- VPS staging: NOT VERIFIED dalam audit ini.
- ZIP: BUILD CANDIDATE pada SHA d2127a6; latest SHA b614850 belum memiliki build ZIP terverifikasi.
- ZIP SIAP UJI OPERASIONAL TERBATAS: BELUM.

## 7. PEKERJAAN SELANJUTNYA
Mulai dari Student Import XLSX/CSV security preflight pada branch kerja baru yang dibuat dari b614850. Sebelum patch: backup byte-exact StudentImportService.php dan StudentImportTest.php, catat SHA, lalu implementasikan batas ZIP/OOXML dan CSV sebelum parser membaca file. Jalankan focused test serta full CI PHP 8.3/8.4 + disposable MySQL 8. Jangan menyentuh database produksi.
