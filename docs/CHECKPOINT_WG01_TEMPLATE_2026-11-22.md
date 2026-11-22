# CHECKPOINT WG01-TEMPLATE — 22 NOVEMBER 2026

## Identitas
- Repository: \`zane0184/cbt_november\`
- Branch Agent 3: \`work/web-guru-foundation-20261122\`
- HEAD remote sebelum penambahan laporan/checkpoint tahap template: \`a24eb549a34d9138ee209af62df2397b77aefddb\`
- Source/template latest commit: \`47d1af0c41e4cb86c660e85d87b4380fd44e902c\`
- CI workflow update: \`e000d50cf3408ee525ffe6c6919d43c14c3a8851\`
- Laporan implementasi: \`docs/WG01_TEMPLATE_IMPLEMENTATION_2026-11-22.md\`
- Issue kontrak Agent 2: https://github.com/zane0184/cbt_november/issues/8

## AUDIT
- Remote branch diverged forward by 12 commits from prior checkpoint HEAD \`c4d61feed490244b92370b2c231a3fe521c8203f\`, 0 behind.
- Tidak ada checkout Git lokal di runtime; status working tree lokal tidak diketahui.
- Workspace rekonstruksi lokal dan ZIP baseline tidak ditemukan pada listing runtime ini; remote repository source tetap dapat diperiksa.
- TeacherPortalAccess memvalidasi exact-role GURU, tenant dan satu profil Guru.
- Bank Soal utama tetap disabled; hanya link unduh template ditambahkan.
- Import soal existing tetap JSON rows; backend XLSX/PDF import belum ada.
- Agent 2 belum memberi keputusan kontrak pada saat checkpoint dibuat.

## DESAIN
- Template \`CBT-QUESTION-1\` memiliki enam sheet: Petunjuk, Data Soal, Detail Opsi, Referensi Jenis Soal, Contoh Soal, Referensi Akademik.
- Enam tipe source dikenali ditampilkan. Opsi E tidak didukung.
- SHORT_ANSWER diberi status BLOCKED karena accepted answers storage/snapshot belum terbukti.
- Referensi akademik tidak diisi dengan data asumsi; placeholder menegaskan perlu kontrak backend.
- Kurikulum Merdeka dan KBC/Panca Cinta tetap satu konteks terintegrasi.
- Download melalui route Portal Guru yang dilindungi exact-role access.

## IMPLEMENTASI
File baru:
- \`backend-laravel/app/Exports/QuestionImport/QuestionImportTemplateExport.php\`
- \`backend-laravel/app/Http/Controllers/Teacher/QuestionImportTemplateController.php\`
- \`backend-laravel/tests/Feature/QuestionImportTemplateDownloadTest.php\`

File existing diubah:
- \`backend-laravel/routes/teacher.php\`
- \`backend-laravel/resources/views/layouts/teacher.blade.php\`
- \`.github/workflows/web-guru-foundation-ci.yml\` — filter test diperluas.

Backup:
- \`docs/backup/web-guru-wg01-template-20261122/routes_teacher.php.before\`
- \`docs/backup/web-guru-wg01-template-20261122/teacher.blade.php.before\`
- \`docs/backup/web-guru-wg01-template-20261122/web-guru-foundation-ci.yml.before\`
- Manifest: \`docs/backup/web-guru-wg01-template-20261122/MANIFEST.md\`
- Ketiga blob SHA backup diverifikasi identik dengan source sebelum perubahan.

Koordinasi:
- Issue #8 dibuat dengan 13 kelompok pertanyaan kontrak.
- Tidak ada perubahan pada source Agent 1/2, schema, migration, API backend, QuestionPolicy, importer bersama, batch model/service, Composer dependency, database atau CBT Engine.

## PENGUJIAN
- PASS: remote branch compare dan verifikasi backup.
- NOT RUN: unduh workbook nyata, PHPUnit baru, PHP lint runtime, browser, responsive, accessibility.
- NOT VERIFIED: status CI terbaru; combined status yang terbaca tidak menampilkan status check.
- BLOCKED: upload/preview/correction/confirm, metadata akademik dinamis, accepted answers, PDF/OCR.
- Tidak ada klaim fitur siap pakai.

## STATUS
- WG01-CONTRACT: PENDING Agent 2.
- WG01-TEMPLATE: source implementation written; test/browser/CI verification pending.
- WG01-XLSX-UI: NOT STARTED.
- WG01-XLSX-INTEGRATION: BLOCKED by backend contract.
- WG01-PDF: BLOCKED by parser/OCR design and environment.
- PROCTOR-UI / PROCTOR-SECURITY: NOT STARTED.

## LANGKAH BERIKUTNYA
1. Audit jawaban Agent 2 pada issue #8.
2. Verifikasi CI dan workbook template pada runtime.
3. Lanjutkan UI preview/correction setelah endpoint contract tersedia.
4. Jangan mengubah Agent 1/2 source atau production database.


---

# CHECKPOINT LANJUTAN — VERIFIKASI XLSX & DESAIN DOCX (22 NOVEMBER 2026)

## HEAD dan bukti
- HEAD Agent 3 sebelum append checkpoint: 1dd626bbd8547a1387935438aa6327733294f1bf.
- Source PHP/test yang diuji: a33867928711b456d2414a675bef1a929861dee8.
- CI run #34: 44995889398 — SUCCESS, SHA diuji sama dengan source PHP/test di atas.
- Hasil CI: 16 tests passed, 69 assertions; PHP lint dan route registration PASS; disposable MySQL 8; 80 tabel InnoDB; cleanup PASS.
- CI URL: https://github.com/zane0184/cbt_november/actions/runs/44995889398
- Tidak ada checkout Git lokal; working tree lokal NOT VERIFIED.
- Workspace lokal /mnt/data/CBT_MADRASAH_RECONSTRUCTION dan ZIP baseline tidak ditemukan pada listing runtime sesi ini.

## AUDIT
- Template, controller, route, layout, test dan workflow diperiksa ulang pada remote branch.
- Route unduh berada di middleware auth/tenant/license/EnsureTeacherPortalAccess; source TeacherPortalAccess memeriksa tepat satu role GURU dan profil tenant.
- Test menolak unauthenticated, ADMIN_MADRASAH, SISWA dan multi-role.
- XLSX aktual berhasil dihasilkan dan dibuka kembali dengan PhpSpreadsheet; enam sheet, header dan dropdown diverifikasi.
- Browser E2E dan visual/responsive/accessibility belum diuji.
- Issue #8 dan #9 masih OPEN, tanpa komentar Agent 2.
- Composer saat ini memiliki Laravel Excel/PhpSpreadsheet; PHPWord tidak ditemukan. Tidak ada dependency baru.
- Branch Agent 1/2 tetap tidak diubah dan tidak ada path source overlap pada perubahan WG01 yang diperiksa.
- Insiden dokumentasi: file desain DOCX sempat ditambahkan ke main akibat parameter branch terlewat; file disalin identik ke branch Agent 3 dan kemudian dihapus dari main. Commit add/delete dicatat di laporan; tidak ada source aplikasi yang berubah pada main.

## DESAIN
- DOCX dan XLSX menggunakan parser/adapter terpisah tetapi wajib menghasilkan satu normalized draft contract dan satu batch/storage/lifecycle backend.
- DOCX perlu document profile, source mapping, preview, correction, revalidation, dan explicit-key-only. Ambiguity menjadi NEEDS_REVIEW; tidak ada tebakan AI.
- Tabel, gambar, OMML, Arabic/harakat, mixed RTL dan external relationships ditangani sesuai status ekstraksi; konten gagal ekstrak harus tampil sebagai warning.
- DOC lama ditunda; PDF/OCR tetap deferred.

## IMPLEMENTASI
- Test template diperluas untuk response HTTP nyata, workbook parse, header/dropdown, unauthenticated, ADMIN/SISWA dan multi-role.
- Backup manifest berisi 12 snapshot; 12/12 blob SHA cocok.
- Desain DOCX tersimpan di docs/DESAIN_IMPORT_SOAL_DOCX_WG01_2026-11-22.md.
- Laporan diperbarui di docs/WG01_TEMPLATE_IMPLEMENTATION_2026-11-22.md.
- Tidak ada perubahan pada QuestionController, QuestionPolicy, schema, migration, importer bersama, batch model/service, Composer, MediaAsset, ExamSnapshot, Scoring, CBT Engine atau production DB.

## PENGUJIAN
- PASS: CI run 44995889398, 16 tests/69 assertions.
- PASS: PHP lint, route registration, disposable MySQL 8, 80 tabel InnoDB, cleanup.
- PASS: workbook generator + parser readback + dropdown.
- PASS: route headers dan filename, exact-role Guru, unauthenticated/Admin/Siswa/multi-role denial.
- NOT RUN: browser E2E, visual responsive, accessibility.
- BLOCKED: XLSX upload/preview/confirm, DOCX parser/UI, PDF/OCR.

## STATUS
- WG01-TEMPLATE: CI VERIFIED; browser E2E pending.
- WG01-CONTRACT XLSX: PENDING Agent 2.
- WG01-DOCX DESIGN: DONE, initial design only.
- WG01-XLSX UI/integration: BLOCKED by backend contract.
- WG01-DOCX parser/UI: BLOCKED by backend contract and dependency decision.
- PDF/OCR: DEFERRED.
- PROCTOR UI: NOT STARTED.

## Keamanan operasional
Portal Guru hanya layak untuk uji terbatas pada dashboard/profil dan unduh template yang diuji CI. Bank Soal dan seluruh alur import belum siap digunakan operasional atau produksi. Tidak ada merge, PR, deployment, migration produksi atau perubahan branch Agent 1/2.


---

# CHECKPOINT QA DAN DESAIN UI XLSX/DOCX — 22 NOVEMBER 2026

## Identitas dan posisi
- Branch: `work/web-guru-foundation-20261122`.
- HEAD sebelum pembaruan dokumentasi tahap ini: `de165f59a22ab9f72c94a8e086cf6883f08a784e`.
- CI source SHA: `a33867928711b456d2414a675bef1a929861dee8`.
- CI Run #34: `44995889398`, completed/success.
- Desain UI: `docs/DESAIN_UI_IMPORT_SOAL_XLSX_DOCX_WG01_2026-11-22.md`.
- Issue #8 dan #9: OPEN, masing-masing 0 komentar.

## AUDIT
- Source route, layout, controller download, dan export template diperiksa ulang.
- Link template ada di navigasi desktop dan mobile; modul Bank Soal tetap disabled.
- Lima commit setelah SHA CI hanya mengubah dokumentasi; tidak ada perubahan source aplikasi/workflow. CI tetap relevan untuk source template.
- CI: 16 tests, 69 assertions; PHP lint/route registration PASS; MySQL 8 disposable, 80 tabel InnoDB, cleanup PASS.
- Browser QA NOT RUN karena environment aplikasi/browser interaktif dan akun uji non-produksi tidak tersedia.
- Backup report/checkpoint/manifest dibuat dan diverifikasi sebelum perubahan. Backup tambahan 3/3 MATCH; manifest diperbarui dan koreksi duplikasi section dicatat.
- Tidak ada path source Agent 1/2 yang diubah. Seluruh write eksplisit ke branch Agent 3.

## DESAIN
- Satu wizard UI Guru dengan jalur file XLSX dan DOCX terpisah.
- DOCX memakai document profile atau auto-detect terbatas.
- Kedua jalur menuju normalized draft DTO dan lifecycle backend bersama.
- Preview, source mapping, warnings, koreksi, revalidation, confirm, dan report.
- Referensi akademik tenant-scoped; Kurmer + KBC/Panca Cinta terintegrasi.
- Confirm hanya menyimpan DRAFT, tidak publish.
- DOCX key hanya marker eksplisit; tidak ada tebakan AI.
- SHORT_ANSWER BLOCKED; ESSAY manual.
- Desain responsif dan accessibility dicatat dalam dokumen desain.
- Semua endpoint/DTO/status/batch/storage final masih menunggu Agent 2.

## IMPLEMENTASI
- Hanya dokumen desain UI baru serta pembaruan laporan/checkpoint/manifest.
- Tidak ada source aplikasi, API, parser, schema, migration, dependency, batch service, QuestionController/Policy, atau CBT Engine yang diubah.
- Backup:
  - Report sebelum update: `docs/backup/web-guru-wg01-template-20261122/WG01_TEMPLATE_IMPLEMENTATION.before-qa-ui-design-20261122.md`, SHA `796386c72e045574c2349a2768aa0a2290b54fc2`.
  - Checkpoint sebelum update: `docs/backup/web-guru-wg01-template-20261122/CHECKPOINT_WG01_TEMPLATE.before-qa-ui-design-20261122.md`, SHA `901f2f88092a60a8dd9213183b27368b2b3445ee`.
  - Manifest sebelum update: `docs/backup/web-guru-wg01-template-20261122/MANIFEST.md.before-qa-ui-design-20261122`, SHA `4bbfca0a9f3d893f5d44975904a1820192f44fb8`.
  - Manifest saat koreksi duplikasi juga dibackup; seluruh hash backup yang diuji cocok.
- Branch Agent 3 saja; tidak ada merge/PR/deploy.

## PENGUJIAN
- CI VERIFIED: Run 44995889398, SHA a338679..., 16 tests/69 assertions.
- CI source applicability: PASS; lima commit setelah SHA hanya dokumentasi.
- BROWSER QA: NOT RUN.
- Desktop/mobile, actual browser download, session expiry, cross-tenant, keyboard/accessibility: NOT RUN.
- Upload/preview/confirm XLSX/DOCX: BLOCKED.

## STATUS
- Template XLSX: CI VERIFIED.
- Portal Guru browser: NOT RUN.
- XLSX/DOCX UI: DESIGN ONLY.
- Agent 2 contract #8/#9: BLOCKED / waiting.
- Bank Soal operational: NOT READY FOR OPERATIONAL USE.
- PDF/OCR: DEFERRED.

## Instruksi tahap berikutnya
Tunggu jawaban Agent 2. Audit keputusan terhadap source aktual. Jangan implementasikan UI import, parser, API, schema, batch service, atau dependency sebelum kontrak disetujui dan tahap baru diizinkan.


## REKONSILIASI HEAD FINAL SETELAH PEMBARUAN DOKUMENTASI

- HEAD final branch yang diverifikasi: `443b5f8173b6b12599416951f76d24eb016fe886`.
- Compare HEAD final terhadap ref branch terkini: identical, 0 ahead / 0 behind.
- Compare CI SHA `a33867928711b456d2414a675bef1a929861dee8` terhadap HEAD final: 17 commit ahead, 0 behind.
- Seluruh 17 perubahan file pada rentang tersebut adalah dokumentasi dan backup di bawah `docs/`; tidak ada source aplikasi, workflow CI, Composer, migration, schema, atau konfigurasi runtime berubah.
- Dengan demikian CI Run #34 tetap mencakup source aplikasi yang sama. Commit dokumentasi tidak diuji ulang oleh PHPUnit karena tidak mengubah kode.
- Browser QA tetap NOT RUN.
- Backup final report sebelum rekonsiliasi: `docs/backup/web-guru-wg01-template-20261122/WG01_TEMPLATE_IMPLEMENTATION.before-final-head-reconciliation-20261122.md`, blob SHA `5d116d9e70c4bbeecebea4c149273f63e65d6558` (MATCH).
- Backup final checkpoint sebelum rekonsiliasi: `docs/backup/web-guru-wg01-template-20261122/CHECKPOINT_WG01_TEMPLATE.before-final-head-reconciliation-20261122.md`, blob SHA `60fecf2f090ff146b3b7c69f1040deebaafbdf02` (MATCH).
