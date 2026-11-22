# WG01-TEMPLATE — Implementasi Template XLSX Import Soal
Tanggal: 22 November 2026
Repository: \`zane0184/cbt_november\`
Branch Agent 3: \`work/web-guru-foundation-20261122\`

## Status
**IMPLEMENTASI AWAL TEMPLATE SUDAH DITULIS DI REMOTE SOURCE; BELUM TERVERIFIKASI MELALUI CI/BROWSER.**
Kontrak backend masih menunggu Agent 2. Fitur upload, parsing, preview, koreksi, confirm, laporan batch, PDF/OCR, dan penyimpanan soal belum dibuat.

## AUDIT

### Posisi branch
- HEAD awal sebelum pekerjaan: \`c4d61feed490244b92370b2c231a3fe521c8203f\`.
- Perbandingan remote menunjukkan branch maju 12 commit, tanpa tertinggal dari HEAD awal.
- HEAD remote terakhir sebelum dokumen laporan/checkpoint ini: \`a24eb549a34d9138ee209af62df2397b77aefddb\`.
- Source terbaru yang memuat implementasi template: commit \`47d1af0c41e4cb86c660e85d87b4380fd44e902c\`; perubahan workflow CI: \`e000d50cf3408ee525ffe6c6919d43c14c3a8851\`.
- GitHub connector hanya menyediakan committed remote tree. Checkout lokal tidak tersedia, sehingga status working tree lokal tidak dapat diketahui dan tidak dinyatakan bersih.
- Workspace lokal \`/mnt/data/CBT_MADRASAH_RECONSTRUCTION/\` tidak ditemukan pada runtime ini. ZIP baseline tidak ditemukan pada listing runtime ini. Pemeriksaan ini tidak menggantikan source remote branch yang digunakan untuk perubahan.
- Branch Agent 1 dan Agent 2 tidak diubah.

### Source existing yang diperiksa
- \`backend-laravel/routes/teacher.php\`: route Portal Guru menggunakan middleware \`auth\`, \`tenant\`, \`license\`, dan \`EnsureTeacherPortalAccess\`.
- \`TeacherPortalAccess\`: server memeriksa tepat satu role GURU, tenant akun, akun aktif, dan tepat satu TeacherProfile tenant-local.
- \`layouts/teacher.blade.php\`: Bank Soal masih disabled karena gate akses modul lama belum ditautkan. Menu template ditambahkan terpisah; Bank Soal tidak dinyatakan aktif.
- \`QuestionController\`, \`QuestionPolicy\`, \`ImportBatch\`, \`ImportBatchRow\`, \`ImportWorkbookService\`, dan pola Export Guru/Siswa ditinjau untuk batas integrasi.
- \`ImportWorkbookService\` existing tetap domain Guru/Siswa/Admin; tidak digunakan untuk import soal.
- Source import soal belum menerima file XLSX; endpoint existing menerima JSON rows.

### Koordinasi Agent 2
- Issue koordinasi dibuat: [#8 — Kontrak Backend Agent 2: Import Soal XLSX/PDF](https://github.com/zane0184/cbt_november/issues/8).
- Issue memuat 13 kelompok keputusan: API lifecycle, batch reuse, staging/retention, idempotensi, duplicate detection, exact-role/permission/assignment, metadata, Kurmer-KBC, accepted answers dan snapshot, MediaAsset, PDF/OCR, file security, audit dan partial processing.
- Saat checkpoint ini dibuat, jawaban/persetujuan Agent 2 belum diterima.
- Implementasi dibatasi pada template statis dan route download yang tidak membaca/menulis database akademik atau soal.

## DESAIN

### Struktur template
Versi template: \`CBT-QUESTION-1\`.
Enam sheet:
1. \`Petunjuk\`
2. \`Data Soal\`
3. \`Detail Opsi\`
4. \`Referensi Jenis Soal\`
5. \`Contoh Soal\`
6. \`Referensi Akademik\`

### Batas isi
- Enam kode source: MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, SHORT_ANSWER, ESSAY.
- Opsi hanya A-D; opsi E tidak tersedia.
- TRUE_FALSE dijelaskan memakai A/B.
- MATCHING memiliki satu baris detail per pasangan.
- SHORT_ANSWER diberi label **BLOCKED** karena accepted answers belum terbukti tersimpan dan masuk ExamSnapshot.
- ESSAY tetap manual review.
- Sheet Contoh Soal terpisah dari Data Soal dan diberi peringatan untuk tidak diimpor sebagai data nyata.
- Referensi Akademik tidak memuat CP/TP/KBC/subject/kelas/tahun ajaran rekaan. Sheet menyatakan referensi tenant-scoped belum dimuat sampai kontrak backend disetujui.
- Kurikulum Merdeka dan KBC tetap satu konteks terintegrasi; tidak ada pilihan yang saling menggantikan.
- Dropdown lokal pada jenis soal, kesulitan, tipe detail, option key A-D dan status benar/salah hanya membantu pengisian. Validasi server tetap wajib saat backend import tersedia.
- Tidak ada macro, formula buatan, external relationship, media tertanam, atau referensi akademik dinamis pada template statis.

### Alur download
- Route: \`GET /guru/bank-soal/template\`.
- Nama route: \`teacher.questions.template\`.
- Route berada di \`routes/teacher.php\` di dalam middleware Portal Guru yang memvalidasi exact-role GURU dan profil tenant.
- Menu desktop dan mobile menampilkan tautan **Unduh Template Import Soal** di bawah modul Bank Soal yang masih berstatus audit akses.
- Tautan unduh template tidak mengaktifkan halaman Bank Soal dan tidak memberi hak mengelola soal.

## IMPLEMENTASI

### File baru
- \`backend-laravel/app/Exports/QuestionImport/QuestionImportTemplateExport.php\`
  - Export enam sheet menggunakan Laravel Excel/PhpSpreadsheet yang sudah ada.
  - Validasi dropdown dasar dan instruksi format.
- \`backend-laravel/app/Http/Controllers/Teacher/QuestionImportTemplateController.php\`
  - Download file dengan header private/no-store.
- \`backend-laravel/tests/Feature/QuestionImportTemplateDownloadTest.php\`
  - Test akses exact-role GURU, penolakan multi-role, nama enam sheet, enam tipe, status BLOCKED SHORT_ANSWER, dan batas opsi E.

### File existing yang diubah
- \`backend-laravel/routes/teacher.php\`: tambah import controller dan satu route download.
- \`backend-laravel/resources/views/layouts/teacher.blade.php\`: tambah link unduh template pada navigasi desktop dan mobile; modul Bank Soal utama tetap disabled.

### Backup
Backup dibuat sebelum file existing diedit:
- \`docs/backup/web-guru-wg01-template-20261122/routes_teacher.php.before\`
  - Blob SHA backup sama dengan source sebelum edit: \`45a128a37d0dda86a9e102bf5232b9b922bdba9c\`.
- \`docs/backup/web-guru-wg01-template-20261122/teacher.blade.php.before\`
  - Blob SHA backup sama dengan source sebelum edit: \`2f57f3cb4b6e85892a087894cf9119b7f0657ae8\`.
- \`docs/backup/web-guru-wg01-template-20261122/web-guru-foundation-ci.yml.before\`
  - Blob SHA backup sama dengan source sebelum edit: \`fd66cdcfa759dde263790ccbbba39b2e0c49c3d6\`.
- Manifest: \`docs/backup/web-guru-wg01-template-20261122/MANIFEST.md\`.
- Ketiga backup diverifikasi ulang melalui fetch; blob SHA backup identik dengan blob SHA source sebelum perubahan.

### Workflow CI
- Workflow \`.github/workflows/web-guru-foundation-ci.yml\` diperbarui agar filter PHPUnit memasukkan \`QuestionImportTemplateDownloadTest\` bersama \`TeacherPortalFoundationTest\`.
- Backup workflow dibuat sebelum perubahan.
- Tidak ada Composer dependency, migration, schema, API controller, QuestionPolicy, importer backend, model batch, database, CBT Engine, Android, atau branch Agent 1/2 yang diubah.

## PENGUJIAN

| Pemeriksaan | Status | Bukti/catatan |
|---|---|---|
| Remote branch compare | PASS | 12 commit ahead dari HEAD awal, 0 behind |
| Backup route/layout/workflow | PASS | Blob SHA backup identik dengan source sebelum edit |
| Source route middleware | PASS (audit source) | EnsureTeacherPortalAccess memvalidasi exact role GURU dan profil tenant |
| Template XLSX nyata berhasil diunduh | NOT RUN | Belum ada eksekusi aplikasi |
| PHPUnit test baru | NOT RUN | Test sudah ditulis dan dimasukkan ke filter CI, belum ada hasil run terverifikasi |
| PHP lint pada source terbaru | NOT RUN | Tidak ada checkout lokal; CI belum terverifikasi |
| CI run untuk source terbaru | NOT VERIFIED | Combined status pada commit yang diperiksa tidak menampilkan status check; tidak mengklaim PASS |
| Browser desktop/tablet/mobile | NOT RUN | Belum diuji |
| Light/Dark, keyboard, kontras | NOT RUN | Belum diuji |
| Validasi file upload/preview/correction/confirm | BLOCKED | Menunggu kontrak Agent 2 dan implementasi parser/backend |
| Referensi akademik dinamis | BLOCKED | Menunggu kontrak tenant-scoped Agent 2 |
| PDF/OCR dan media extraction | BLOCKED | Dependency/environment belum disetujui atau diuji |

## BATASAN / RISIKO TERSISA
- File XLSX belum dibuktikan dapat dibuka/diunduh dari runtime aplikasi.
- Dropdown Excel bukan pengganti validasi backend.
- Referensi akademik masih berupa penjelasan placeholder; tidak ada data CP/TP/KBC yang dimuat.
- Template belum terhubung dengan importer, belum menyimpan soal, dan tidak boleh dipakai sebagai klaim fitur import siap.
- SHORT_ANSWER tetap BLOCKED sampai accepted answers tersimpan dan tersnapshot.
- Semua fitur upload XLSX/PDF, preview, koreksi, confirm, idempotensi, duplicate detection, audit, media dan PDF/OCR belum diimplementasikan.

## LANGKAH BERIKUTNYA
1. Tunggu dan audit jawaban Agent 2 pada issue #8.
2. Jalankan CI/test terbaru dan perbaiki kegagalan yang terbukti.
3. Verifikasi workbook XLSX nyata, struktur enam sheet, dropdown, dan unduhan browser.
4. Selesaikan kontrak referensi akademik tenant-scoped sebelum mengisi sheet Referensi Akademik secara dinamis.
5. Lanjutkan WG01-XLSX-UI hanya setelah kontrak API preview/correction/confirm disetujui.
6. Jangan mulai PDF/OCR sebelum dependency dan batas resource disetujui.
7. Operasional Proktor tetap work package terpisah; belum disentuh pada checkpoint ini.

## STATUS AKHIR
**WG01-TEMPLATE: IMPLEMENTASI AWAL DITULIS, PENGUJIAN BELUM TERBUKTI.**
**WG01-XLSX-UI / XLSX-INTEGRATION / PDF / PROKTOR-UI: BELUM DIMULAI.**
Tidak ada merge, PR, rebase, deployment, migration produksi, atau perubahan branch Agent 1/2.


---

# VERIFIKASI ULANG & TAMBAHAN DOCX — 22 NOVEMBER 2026

## AUDIT TERBARU

### Branch dan sumber
- HEAD Agent 3 saat pencatatan bagian ini: 1dd626bbd8547a1387935438aa6327733294f1bf.
- Source PHP/test yang diuji CI: a33867928711b456d2414a675bef1a929861dee8.
- Perubahan setelah SHA yang diuji hanya dokumentasi desain DOCX; tidak ada perubahan PHP, route, workflow, Composer atau schema setelah SHA uji.
- Tidak ada checkout Git lokal pada runtime ini; working tree lokal NOT VERIFIED.
- Pemeriksaan filesystem: /mnt/data/CBT_MADRASAH_RECONSTRUCTION tidak ditemukan dan tidak ada ZIP baseline CBT REVISI 6 NOPEMBER 2026.zip pada listing /mnt/data sesi ini. File laporan/checkpoint yang dilampirkan tersedia terpisah; pemeriksaan ini tidak mengklaim seluruh arsip baseline telah tersedia pada runtime.
- Branch Agent 1: work/stage4b-room-session-20261122, HEAD 338fd45d02a019bbe44cc70aa846b5e76f611323.
- Branch Agent 2 Web Admin: work/web-admin-package1-implementation-20261122, HEAD d7e2034ede3b044fc4ab0b900335eff3669b41ed.
- Branch Agent 2 import Guru/Siswa: work/import-guru-siswa-implementation-20261122, HEAD f38ceec7b0653fcf3363eec40f3d4577c323cca8.
- Perbandingan daftar file menunjukkan tidak ada path source yang sama antara file Agent 3 pada WG01 dan daftar perubahan branch Agent 1/2 yang diperiksa. Tidak ada branch Agent 1/2 yang diubah.

### Source template dan akses
- Template export: backend-laravel/app/Exports/QuestionImport/QuestionImportTemplateExport.php.
- Controller: backend-laravel/app/Http/Controllers/Teacher/QuestionImportTemplateController.php.
- Route: GET /guru/bank-soal/template, name teacher.questions.template.
- Route berada dalam middleware auth, tenant, license, EnsureTeacherPortalAccess.
- TeacherPortalAccess source memeriksa akun tenant, persisted account aktif, tepat satu role GURU, dan tepat satu TeacherProfile tenant-local.
- Controller menolak jika user/tenant tidak tersedia dan mengatur Cache-Control private/no-store/max-age=0 serta Pragma no-cache.
- Menu desktop/mobile memiliki link unduh template. Menu Bank Soal utama tetap disabled; template download tidak mengaktifkan editor atau modul Bank Soal lama.

### Dependency XLSX dan DOCX
- composer.json/lock memuat maatwebsite/excel dan phpoffice/phpspreadsheet; versi PhpSpreadsheet yang tercatat adalah 1.30.8.
- PHPWord tidak ditemukan pada composer.json/composer.lock yang diperiksa.
- Tidak ada dependency baru yang ditambahkan.
- Tidak ada parser DOCX yang tersedia atau disetujui pada source yang diperiksa.

### Issue Agent 2
- Issue #8 Import XLSX/PDF: OPEN, 0 komentar.
- Issue #9 Import DOCX: OPEN, 0 komentar.
- Issue #8 body sudah ditambah ketentuan DOCX agar XLSX dan DOCX menggunakan satu normalized draft DTO, satu batch/idempotency/audit lifecycle, dan satu storage.
- Belum ada jawaban/approval Agent 2. Tidak ada backend import tandingan yang dibuat.

## DESAIN TERBARU

### DOCX
Dokumen desain baru:
docs/DESAIN_IMPORT_SOAL_DOCX_WG01_2026-11-22.md

Rancangan:
- DOCX dan XLSX mempunyai adapter upload/parser berbeda.
- Keduanya wajib menghasilkan normalized draft yang sama dan memakai batch, staging, idempotency, duplicate detection, audit, preview, correction, revalidation, confirm, report dan storage yang sama.
- Guru memilih document profile atau menerima saran auto-detect terbatas; profil harus versioned.
- Parsing mempertahankan source mapping paragraph, automatic numbering, tables, image relationships, OMML equation blocks dan metadata bidi/RTL sejauh terbukti.
- Gambar/tabel/persamaan yang tidak terbaca wajib diberi warning di preview; tidak boleh hilang diam-diam.
- Kunci jawaban hanya candidate jika marker eksplisit, nomor cocok dan opsi lengkap. Selain itu NEEDS_REVIEW. Tidak ada tebakan AI/heuristik.
- SHORT_ANSWER tetap BLOCKED sampai accepted answers tersimpan, masuk ExamSnapshot dan digunakan Scoring.
- ESSAY tetap manual review.
- DOC lama ditunda; PDF/OCR tetap jalur terpisah dan tidak dikerjakan sekarang.
- Tidak ada parser library atau Composer dependency dipilih.

### Peristiwa penempatan dokumen DOCX
- Commit f1bb5c9e4edaa4d18529d428ed2c4978ae00ec1d sempat menambahkan dokumen desain DOCX ke branch default main karena parameter branch terlewat pada satu operasi dokumentasi.
- Dokumen yang sama kemudian disalin ke branch Agent 3 pada commit 1dd626bbd8547a1387935438aa6327733294f1bf dan blob SHA diverifikasi identik.
- File yang tidak sengaja ditambahkan ke main dihapus kembali pada commit feab77228f7108f7f7a45325d7f1fb878c07310b; pemeriksaan setelahnya menunjukkan path tersebut tidak ada lagi pada main.
- Ini hanya menyangkut file dokumentasi baru; tidak ada source aplikasi, workflow, dependency, migration, database atau konfigurasi produksi yang berubah pada main.
- Riwayat commit add/delete tetap ada di histori main; tree saat ini untuk path tersebut telah dikembalikan. Peristiwa ini dicatat transparan, bukan dihilangkan dari histori.

## IMPLEMENTASI DAN BACKUP

### Test template diperluas
QuestionImportTemplateDownloadTest kini menguji:
- GURU exact-role dapat mengunduh route template.
- Response Content-Type XLSX, Content-Disposition nama file, Pragma no-cache, dan Cache-Control private/no-store/max-age=0.
- Workbook XLSX benar-benar dihasilkan sebagai ZIP/XLSX dan dibuka kembali menggunakan PhpSpreadsheet IOFactory.
- Enam sheet, header utama, dropdown jenis soal, kesulitan, detail opsi, option key A-D.
- SHORT_ANSWER tetap BLOCKED, opsi E tidak didukung, referensi akademik masih NEEDS_BACKEND_CONTRACT.
- Pengguna belum login ditolak.
- Role tunggal ADMIN_MADRASAH dan SISWA ditolak.
- Akun multi-role GURU + ADMIN_MADRASAH ditolak.

### Backup
Manifest terbaru:
docs/backup/web-guru-wg01-template-20261122/MANIFEST.md

- 12 snapshot backup terdaftar.
- 12 hash cocok; 0 hash tidak cocok.
- Backup mencakup route, layout, workflow, test sebelum setiap rangkaian revisi, serta report/checkpoint sebelum verifikasi.
- Seluruh backup tetap dipertahankan.
- Kalimat manifest yang sebelumnya salah menyebut "Kedua backup" telah diperbaiki.

## PENGUJIAN TERBARU

### CI yang lulus
<https://github.com/zane0184/cbt_november/actions/runs/44995889398>

- Run: 44995889398, run number 34.
- SHA diuji: a33867928711b456d2414a675bef1a929861dee8.
- Status: completed / success.
- PHP 8.3 + Composer v2.
- PHP lint dan route registration: PASS.
- PHPUnit command: php artisan test --filter='TeacherPortalFoundationTest|QuestionImportTemplateDownloadTest' --display-warnings.
- Hasil: 16 tests passed, 69 assertions.
- QuestionImportTemplateDownloadTest: PASS untuk unduh route, workbook parse, unauthenticated, ADMIN/SISWA, dan multi-role.
- Database MySQL 8 disposable: PASS; 80 base tables terverifikasi menggunakan InnoDB.
- Identitas database disposable diverifikasi sebelum PHPUnit.
- Cleanup database dan user disposable: PASS.
- Tidak menggunakan database produksi.

### Run gagal sebelumnya dan koreksi
- Run 44995766591 pada SHA 5e8fdbe6095575329d3995867dbf5a8daa78f87b gagal karena test membandingkan urutan string Cache-Control secara persis; framework menormalisasi urutan menjadi max-age=0, no-store, private.
- Run 44995790498 pada SHA cdf3e4283d6e5c8629372f801002eb8d70bd8122 juga gagal pada assertion urutan yang sama.
- Assertion diperbaiki pada test menjadi perbandingan direktif tanpa bergantung pada urutan.
- Run 44995889398 pada SHA a338679... lulus penuh setelah koreksi.
- Kegagalan tersebut berasal dari assertion test, bukan bukti kerusakan route/controller.

### Batas verifikasi
- PASS: workbook XLSX aktual dihasilkan oleh Laravel Excel dan dibuka ulang dengan PhpSpreadsheet pada CI.
- PASS: route unduh HTTP dan header respons diuji pada CI.
- PASS: exact-role dan akses negatif diuji melalui PHPUnit.
- NOT RUN: browser E2E visual, desktop/tablet/mobile layout, keyboard navigation, accessibility, dan uji Excel desktop/LibreOffice.
- NOT RUN: upload XLSX, preview, koreksi, confirm, batch report; backend import belum tersedia.
- BLOCKED: DOCX parser, upload, preview dan koreksi sampai kontrak Agent 2 disetujui.
- BLOCKED: PDF/OCR.
- Tidak ada klaim siap produksi.

## CHECKPOINT DAN LANGKAH BERIKUTNYA

Status:
- WG01-TEMPLATE: implementasi dan verifikasi CI lulus untuk source SHA a338679...; belum ada browser E2E.
- WG01-CONTRACT XLSX: PENDING Agent 2.
- WG01-DOCX DESIGN: selesai sebagai desain awal; parser/API/UI belum dimulai.
- WG01-XLSX-UI/INTEGRATION: BLOCKED BY AGENT 2 CONTRACT.
- WG01-DOCX PARSER/UI: BLOCKED BY AGENT 2 CONTRACT dan dependency decision gate.
- PDF/OCR: DEFERRED.
- PROCTOR-UI: belum dimulai.

Langkah berikutnya:
1. Tunggu jawaban Agent 2 pada issue #8 dan #9.
2. Cocokkan approval dengan source backend yang benar-benar diimplementasikan.
3. Lakukan browser E2E download template jika runtime tersedia.
4. Setelah kontrak backend disetujui, implementasikan satu halaman Guru dengan dua pilihan jalur: DOCX dan XLSX, memakai satu lifecycle draft bersama.
5. Jangan mengaktifkan tombol upload sebelum API tersedia.
6. Jangan menambah Composer dependency DOCX sebelum lisensi, kompatibilitas, keamanan dan pengujian disetujui.
7. Jangan mengubah schema, CBT Engine, production DB atau branch Agent lain.

## STATUS OPERASIONAL
Portal Guru hanya layak untuk uji terbatas pada dashboard/profil dan unduh template yang telah diuji CI. Bank Soal/import soal belum siap digunakan operasional karena parser, preview, correction, confirm dan integrasi penyimpanan belum tersedia. Bukan siap produksi.


---

# QA LANJUTAN, AUDIT KONTRAK, DAN DESAIN UI XLSX/DOCX — 22 NOVEMBER 2026

## 1. AUDIT

### Branch dan HEAD
- Branch kerja yang diverifikasi: `work/web-guru-foundation-20261122`.
- HEAD sebelum pembaruan dokumentasi tahap ini: `de165f59a22ab9f72c94a8e086cf6883f08a784e`.
- Branch Agent 3 tetap menjadi satu-satunya target write; semua operasi file menggunakan parameter branch eksplisit.
- Commit dokumentasi tahap ini: desain UI `316975597da9f4585275f32be2a18885c491f5c7`; pembaruan backup/report/checkpoint dilakukan terpisah pada branch yang sama.
- Tidak ada perubahan source aplikasi, workflow, Composer, migration, schema, API, atau database pada tahap ini.

### Source yang diperiksa ulang
- `backend-laravel/routes/teacher.php`
- `backend-laravel/resources/views/layouts/teacher.blade.php`
- `backend-laravel/app/Http/Controllers/Teacher/QuestionImportTemplateController.php`
- `backend-laravel/app/Exports/QuestionImport/QuestionImportTemplateExport.php`
- Laporan WG01, checkpoint, desain DOCX, dan manifest backup.
- Route unduh template tetap `GET /guru/bank-soal/template`, nama `teacher.questions.template`.
- Navigasi desktop dan mobile memiliki tautan unduh template; Bank Soal tetap disabled dan tidak diaktifkan.
- Controller mengandalkan middleware Portal Guru pada route dan memeriksa user/tenant, lalu mengatur header private/no-store. Ini audit source, bukan browser proof.

### Issue dan koordinasi Agent 2
- Issue #8: OPEN, 0 komentar; belum ada jawaban Agent 2.
- Issue #9: OPEN, 0 komentar; belum ada jawaban Agent 2.
- Tidak ditemukan keputusan Agent 2 melalui komentar kedua issue yang diperiksa.
- Tidak ada bukti persetujuan kontrak atau implementasi parser/API pada source yang diperiksa.
- Semua kelompok kontrak API lifecycle, shared draft DTO, batch/staging, authorization, academic references, idempotency, duplicate detection, audit/retention, MediaAsset, accepted answers/snapshot/scoring, dan security tetap BLOCKED / NEEDS DESIGN.
- Issue links: https://github.com/zane0184/cbt_november/issues/8 dan https://github.com/zane0184/cbt_november/issues/9.

### Verifikasi CI setelah SHA pengujian
- CI Run #34: 44995889398, status completed/success.
- SHA yang diuji: `a33867928711b456d2414a675bef1a929861dee8`.
- Perbandingan SHA pengujian terhadap branch terkini menunjukkan 5 commit ahead, 0 behind.
- Kelima file yang berubah setelah SHA pengujian hanya dokumentasi: checkpoint, desain DOCX, laporan, manifest, dan backup manifest. Tidak ada source aplikasi atau workflow yang berubah setelah SHA pengujian.
- Job `teacher-portal-tests`: completed/success; langkah PHP 8.3/Composer, disposable MySQL, PHP lint/route registration, PHPUnit, InnoDB verification dan cleanup selesai.
- Hasil yang tercatat: 16 tests passed, 69 assertions; database disposable MySQL 8; 80 tabel InnoDB; cleanup selesai.
- Karena tidak ada perubahan source aplikasi setelah SHA CI, hasil CI tetap relevan untuk source template yang sama. Ini tidak mencakup perubahan dokumentasi baru dan tidak membuktikan browser QA.

### Browser QA
- Status: NOT RUN.
- Tidak tersedia aplikasi aktif dengan environment QA yang dapat diakses dan browser interaktif untuk login, navigasi, download, atau screenshot.
- Tidak tersedia akun uji non-produksi yang dapat digunakan untuk role GURU, ADMIN_MADRASAH, SISWA, multi-role, serta tenant lain.
- Karena prasyarat tersebut tidak tersedia, login, session expiry, cross-tenant, desktop/mobile viewport, keyboard/accessibility, dan download melalui browser tidak dijalankan.
- PHPUnit CI menguji kontrol akses HTTP dan workbook generation/readback, tetapi tidak menggantikan browser QA.
- Tidak ada screenshot atau klaim BROWSER VERIFIED.

### Backup dan integritas branch
- Sebelum perubahan dokumentasi, dibuat backup report, checkpoint, dan manifest sebagai file baru.
- Backup report blob SHA: `796386c72e045574c2349a2768aa0a2290b54fc2`, cocok dengan source sebelum edit.
- Backup checkpoint blob SHA: `901f2f88092a60a8dd9213183b27368b2b3445ee`, cocok dengan source sebelum edit.
- Backup manifest blob SHA: `4bbfca0a9f3d893f5d44975904a1820192f44fb8`, cocok dengan source sebelum edit.
- Tiga backup tambahan diverifikasi 3/3 MATCH dan didaftarkan pada manifest.
- Saat koreksi ditemukan section backup tambahan sempat terduplikasi dua kali pada manifest; sebelum koreksi, versi manifest tersebut dibackup, hash diverifikasi, lalu section duplikat dihapus sehingga tinggal satu. Snapshot koreksi dicatat pada manifest.
- Tidak ada file source Agent 1/2 yang diubah. Perbandingan path sebelumnya menunjukkan tidak ada path source overlap pada WG01 yang diaudit.
- Insiden penempatan dokumen DOCX ke main sebelumnya tetap tercatat dalam laporan; tidak diulang. Semua write pada tahap ini menyebut branch Agent 3 secara eksplisit.

## 2. DESAIN

Dokumen desain UI baru:
`docs/DESAIN_UI_IMPORT_SOAL_XLSX_DOCX_WG01_2026-11-22.md`

Desain mencakup:
- Satu halaman/wizard Guru dengan dua jalur file terpisah: XLSX dan DOCX.
- DOCX memiliki document profile atau auto-detect terbatas.
- Konteks akademik tenant-scoped untuk subject, kelas/fase, tahun ajaran, CP/TP dan KBC/Panca Cinta yang terintegrasi.
- Satu normalized draft DTO, satu batch/staging/idempotency/audit/storage lifecycle.
- Preview per soal, source mapping, warning, status review, koreksi Guru, revalidation, confirm, dan report.
- Responsif desktop/mobile serta target keyboard/accessibility.
- Kunci DOCX hanya candidate jika marker eksplisit dan mapping pasti; tidak ada tebakan AI/heuristik.
- Semua soal disimpan DRAFT; tidak ada auto-publish.
- SHORT_ANSWER BLOCKED sampai accepted answers tersimpan, masuk ExamSnapshot dan digunakan Scoring; ESSAY manual.
- Tabel kontrak yang masih menunggu Agent 2, security, academic integrity, dan matriks uji setelah backend tersedia.

Semua endpoint, DTO field final, enum status, batch reuse, parser profile, retention, report, dan persistence mapping tetap usulan/NEEDS DESIGN sampai disetujui dan dibuktikan pada source Agent 2.

## 3. IMPLEMENTASI

- Tidak ada source aplikasi yang diubah.
- Tidak ada parser XLSX/DOCX, upload API, preview/correction/confirm endpoint, batch service, schema, migration, Composer dependency, QuestionController, QuestionPolicy, ImportBatch/ImportBatchRow, atau CBT Engine yang disentuh.
- File dokumentasi baru: `docs/DESAIN_UI_IMPORT_SOAL_XLSX_DOCX_WG01_2026-11-22.md`.
- File dokumentasi diperbarui: laporan WG01, checkpoint WG01, dan manifest backup.
- Backup report/checkpoint/manifest dibuat sebelum perubahan dan hash-nya diverifikasi.
- Semua commit dokumentasi dilakukan pada branch Agent 3 dengan branch eksplisit.
- Tidak ada merge, PR, rebase, cherry-pick, deployment, atau migration produksi.

## 4. PENGUJIAN

| Pemeriksaan | Status | Bukti |
|---|---|---|
| CI template XLSX | CI VERIFIED | Run 44995889398, SHA a338679..., 16 tests / 69 assertions |
| Source setelah SHA CI | PASS | 5 commit sesudah SHA hanya dokumentasi |
| PHP lint / route registration | CI VERIFIED | Langkah CI completed/success |
| Disposable MySQL 8 / InnoDB | CI VERIFIED | 80 tabel InnoDB, cleanup selesai |
| Workbook generation/readback | CI VERIFIED | PHPUnit pada run #34 |
| Browser login role matrix | NOT RUN | Tidak ada app QA/browser/account uji |
| Dashboard/profil Guru | NOT RUN | Tidak ada browser runtime |
| Browser download XLSX | NOT RUN | Tidak ada browser runtime |
| Desktop/mobile visual | NOT RUN | Tidak ada browser runtime |
| Session expiry / cross-tenant browser | NOT RUN | Tidak ada browser runtime/account tenant uji |
| Keyboard/accessibility | NOT RUN | Tidak ada browser runtime |
| XLSX/DOCX upload-preview-confirm | BLOCKED | Kontrak/backend Agent 2 belum tersedia |
| PDF/OCR | DEFERRED | Di luar tahap ini |

## 5. CHECKPOINT

- Template XLSX: CI VERIFIED; jangan ubah tanpa masalah konkret yang dapat direproduksi.
- Browser QA Portal Guru: NOT RUN; menunggu environment aplikasi QA dan akun uji non-produksi.
- Kontrak Agent 2 XLSX/PDF: BLOCKED; Issue #8 OPEN, 0 komentar.
- Kontrak Agent 2 DOCX: BLOCKED; Issue #9 OPEN, 0 komentar.
- Desain DOCX: DESIGN ONLY.
- Desain UI bersama XLSX/DOCX: DESIGN ONLY, tersimpan di dokumen baru.
- Implementasi UI upload/import: NOT STARTED.
- Parser XLSX/DOCX, shared batch, API, schema, dependency: BLOCKED.
- PDF/OCR dan DOC lama: DEFERRED.
- Bank Soal: NOT READY FOR OPERATIONAL USE.

### Langkah berikutnya
1. Tunggu jawaban Agent 2 pada Issue #8/#9 dan audit setiap keputusan terhadap source aktual.
2. Jalankan browser QA hanya setelah environment aplikasi dan akun uji non-produksi tersedia.
3. Setelah kontrak backend disetujui, review desain UI dengan Agent 2 dan susun kontrak final.
4. Jangan mulai implementasi UI import, parser, API, atau dependency sebelum persetujuan tahap berikutnya.
5. Pertahankan template XLSX dan seluruh backup.
