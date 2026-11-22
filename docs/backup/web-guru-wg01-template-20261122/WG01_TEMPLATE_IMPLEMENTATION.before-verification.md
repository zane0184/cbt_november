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
