# Checkpoint Audit Import DOCX Web Guru — 2026-11-26

Status: **BLOCKED — belum ada patch source DOCX yang aman untuk diterapkan.**
Scope: hanya Import DOCX pada Web Guru. Android dan Web Super Admin tidak termasuk.

## AUDIT

Sumber yang diperiksa langsung dari GitHub:
- Repository: `zane0184/cbt_november`, default branch `main`.
- Branch UI Web Guru: `work/web-guru-remove-xlsx-20261123`.
- Branch audit scope terbaru yang ditemukan: `audit/web-guru-scope-reconciliation-20261126`.
- Issue kontrak DOCX: [#9](https://github.com/zane0184/cbt_november/issues/9).
- Kontrak backend import: [#8](https://github.com/zane0184/cbt_november/issues/8).
- PR UI Web Guru: [#19](https://github.com/zane0184/cbt_november/pull/19).

Temuan terverifikasi:
1. Dokumen `WEB_GURU_DOCX_IMPORT_UI_DESIGN_DRAFT_2026-11-23.md` masih berstatus DRAFT; kontrak DTO, source locator, parser profile, warning/error, batch lifecycle, idempotency, media staging, batas keamanan dan retention masih PENDING.
2. Catatan Agent 2 pada Issue #9 menyatakan DOCX harus menjadi adapter ekstraksi menuju satu `QuestionDraftDTO` dan satu lifecycle `ImportBatch/ImportBatchRow`, bukan subsystem kedua. Catatan itu belum berisi spesifikasi field/status final dan bukan approval penuh.
3. Komentar audit 25 November pada Issue #9 menyatakan branch kerja yang diperiksa belum memiliki `QuestionDraftDTO`, `ImportBatch`, atau `ImportBatchRow`; komponen lama berada pada branch divergen `work/import-guru-siswa-implementation-20261122` (HEAD `f38ceec7`). Jangan cherry-pick seluruh branch lama atau membuat duplikasi service.
4. Audit yang sama menyatakan prasyarat Import Siswa harus lulus lebih dahulu, fixture DOCX mentah belum tersedia pada pemeriksaan tersebut, serta shared stimulus dan dukungan tipe soal tertentu masih memiliki blocker. Ini harus diverifikasi ulang pada source/CI terbaru sebelum parser DOCX dimulai.
5. Source `QuestionController.php` pada branch `audit/web-guru-scope-reconciliation-20261126` diperiksa; potongan endpoint `store()` yang tersedia adalah penyimpanan soal per-request, bukan bukti kontrak upload/preview/confirm DOCX.
6. Search code GitHub tidak menghasilkan hasil untuk istilah DOCX/PhpWord pada sesi ini; repository melaporkan code-search index belum tersedia. Karena itu hasil search kosong tidak dipakai sebagai bukti bahwa tidak ada file di seluruh repository.
7. Tidak ada bukti bahwa parser DOCX, API upload/preview, fixture keamanan, atau UI DOCX sudah lulus CI. Tidak ada dependency parser yang disetujui pada catatan yang diperiksa.

## DESAIN / GATE SEBELUM IMPLEMENTASI

Urutan aman:
1. Agent backend (Agent 2) menetapkan dan meng-commit kontrak final: DTO netral-format, API lifecycle, state batch/row, source locator, parser profile, warning/error schema, private staging/media, retention, idempotency, duplicate, revalidation, audit, serta batas resource/security.
2. Verifikasi hasil Import Siswa dan shared import lifecycle pada branch/commit aktif. Reuse hanya komponen yang sudah terbukti kompatibel; jangan menyalin branch divergen sebagai satu paket.
3. Owner/backend menyetujui parser library beserta lisensi, kompatibilitas PHP 8.3/Laravel 13, dan hasil security review. Tidak menambah Composer dependency sebelum keputusan itu.
4. Setelah gate tersebut, Agent Web Guru mengimplementasikan hanya adapter UI: upload DOCX, pemilihan parser profile, preview/source locator, koreksi per soal, warning, confirm, dan report; semua memakai kontrak backend yang sama.
5. DOCX disimpan sebagai DRAFT saja. Tidak ada auto-publish. Kunci jawaban hanya candidate bila marker eksplisit dan mapping pasti; selebihnya NEEDS_REVIEW. SHORT_ANSWER tetap BLOCKED; ESSAY manual review; PDF/OCR dan .doc tetap di luar scope.
6. Matriks uji Issue #9 wajib mencakup DOCX sederhana/bernomor, tabel, gambar, rumus, Arab/harakat/RTL, answer-key ambiguity, file rusak/ZIP bomb, external relationship/OLE, oversize/resource limit, serta negative authorization.

## IMPLEMENTASI

- Tidak ada source code yang diubah.
- Tidak ada backup source dibuat karena belum ada file source yang aman dan disepakati untuk dipatch.
- Tidak ada Composer dependency, migration, schema, batch service, controller, policy, route, atau database yang diubah.
- Tidak ada cherry-pick, merge, rebase, deployment, atau perubahan VPS.
- File desain UI yang sudah ada tidak ditimpa atau diulang.

## PENGUJIAN

- Dilakukan pembacaan file desain UI DOCX, checkpoint Web Guru sebelumnya, komentar Issue #8/#9, serta source `QuestionBank.php` dan `QuestionController.php` dari branch yang disebut.
- Belum ada test DOCX yang dapat dijalankan karena belum ada parser/API/UI DOCX yang terhubung pada source yang diperiksa.
- Status test DOCX: **NOT RUN / BLOCKED**, bukan PASS.
- Tidak ada klaim fitur import DOCX telah diperbaiki atau berfungsi.

## Langkah berikutnya

Pemilik backend perlu mempublikasikan keputusan final kontrak pada Issue #9 dan membuktikan prasyarat Import Siswa pada commit/CI terkini. Setelah itu, lanjutkan implementasi UI DOCX pada branch Web Guru tanpa mengulang penghapusan XLSX yang sudah selesai di PR #19.

