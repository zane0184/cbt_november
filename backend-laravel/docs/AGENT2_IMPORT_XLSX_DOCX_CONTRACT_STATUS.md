# Agent 2 — Status Kontrak Import XLSX dan DOCX

Tanggal: 22 November 2026
Branch: work/stage4d-account-lifecycle-20261122
HEAD sebelum dokumentasi: 7d63ea3b70b5191530c2c92c5c86bf92d7324db6
Source runtime: 74bf7178f46673e9be90523671490c0749034acd
Issues: #8 XLSX/PDF; #9 Microsoft Word DOCX

## AUDIT

### Source yang tersedia

| File/class | Blob SHA | Temuan |
|---|---|---|
| ImportBatch | 1c95378302b8d3368a781c9ec224e29cc8d0d8b9 | Model tenant-scoped, menyimpan creator, type, template_version, file_sha256, status dan counters. |
| ImportBatchRow | c47c736e0a09732e7b9a1c44592057515e2a4cff | Model tenant-scoped, row_summary dan encrypted_payload; encrypted_payload disembunyikan. |
| ExcelQuestionImporter | 8a8951b0427506a56a4bfc700fbf4cf7feb7a214 | Adapter XLSX yang sudah ada; memiliki service-level author guard dan enam tipe soal. |
| QuestionController | 7ded53423dd4d96788123819e8039fd543ce4fba | Validasi question_type dan fields soal; accepted_answers tidak diterima pada kontrak store yang diperiksa. |
| ExamSnapshotService | d0bfcd70b2777a3884c7f92b7c256f05492645ec | Snapshot menyalin stem, explanation, options, media dan metadata difficulty/cognitive_level/question_type; accepted_answers tidak disalin. |
| ScoringService | 1fc74eae97538ada140b9b4c75ccd4388d1a40ad | Scoring membaca accepted_answers dari snapshot metadata untuk SHORT_ANSWER. |

QuestionDraftDTO bersama belum ditemukan pada source yang diperiksa. DOCX parser/adapter belum menjadi bagian implementasi yang disetujui. Tidak ada dependency DOCX baru yang disetujui.

## DESAIN — Status per kontrak

| Area | Keputusan | Catatan |
|---|---|---|
| Satu QuestionDraftDTO netral format | NEEDS DESIGN | XLSX/DOCX harus menghasilkan DTO yang sama; field/type/nullable/enum belum disepakati. |
| XLSX dan DOCX sebagai adapter ekstraksi | APPROVED IN PRINCIPLE | Adapter berbeda, bukan dua subsystem import. |
| Reuse ImportBatch/ImportBatchRow | APPROVED IN PRINCIPLE | Tidak membuat tabel/batch service/storage kedua. Perlu verifikasi uniqueness dan lifecycle pada migration. |
| Endpoint upload/preview/correction/revalidate/confirm/cancel/report | NEEDS DESIGN | Request/response/error per-row dan status batch belum final. |
| Staging dan retention | NEEDS DESIGN | Private temp storage, expiry, cleanup dan crash recovery belum ditetapkan. |
| Idempotency, duplicate, retry, partial commit | NEEDS DESIGN | File SHA, tenant/type uniqueness, row retry dan request idempotency harus dibuktikan pada schema/service. |
| Authorization | APPROVED AS REQUIREMENT | Tepat satu role GURU, permission question.manage, tenant, TeacherProfile dan TeacherSubjectAssignment aktif. Implementasi harus diuji direct service. |
| Metadata source locator | NEEDS DESIGN | XLSX sheet/row dan DOCX paragraph/table/image relationship harus dapat ditelusuri. |
| Curriculum/Kurmer/KBC mapping | NEEDS DESIGN | Simpan curriculum/version, CP/TP/ATP dan mapping yang valid; jangan menebak dari teks. |
| accepted_answers untuk SHORT_ANSWER | REJECTED FOR ACTIVATION NOW | Controller tidak menyimpan field, snapshot tidak menyalin, scoring membaca metadata. SHORT_ANSWER tetap BLOCKED sampai end-to-end. |
| MediaAsset lifecycle | NEEDS DESIGN | Private storage, ownership, attach/detach, retention, malware/content validation dan audit. |
| PDF/OCR | DEFERRED | Tidak dikerjakan dalam fase ini. |
| Parser security | NEEDS DESIGN | ZIP/XML limits, DTD/ENTITY off, external relationships blocked, OLE/active content blocked, CPU/memory/time bounds. |
| Audit dan partial processing | NEEDS DESIGN | Audit upload/preview/correction/confirm/fail/cleanup dan commit row harus atomic/traceable. |
| ESSAY | APPROVED AS MANUAL REVIEW | Tidak ada auto-grading/AI guess. |

### Lifecycle tunggal yang diusulkan

Upload → security preflight → extract → stage → preview → correction → revalidate → explicit confirm → persist Question/Version/Options/Media/Curriculum mapping as DRAFT → audit report.

Tidak ada auto-publish. Kunci jawaban hanya menjadi candidate bila marker eksplisit dan mapping pasti; jika ambigu, NEEDS_REVIEW. Dilarang menebak jawaban dengan AI atau heuristik.

### DOCX threat model minimum

- Validasi extension, MIME dan magic bytes.
- Batasi ZIP entry count, compressed/uncompressed size dan rasio dekompresi.
- XML parser menonaktifkan DTD, external entity dan external relationship.
- Tolak embedded OLE/object, macro/active content, file terenkripsi/password-protected, malformed archive dan dokumen rusak.
- Private temporary storage, random file key, non-public path.
- Batas CPU, memory, wall-clock dan ukuran.
- Hapus staging sesuai retention; cleanup tercatat.
- Parser tidak boleh mengeksekusi macro, external link, formula atau embedded object.

Tidak ada Composer dependency parser yang ditambahkan.

## KOORDINASI

- Agent 2 adalah owner backend import.
- Agent 3 hanya menghubungkan UI Guru setelah kontrak DTO, endpoint, preview/error, status dan lifecycle disetujui.
- Agent 4 bukan owner resmi; Web Siswa tidak mengelola import soal.
- Issue #8/#9 meminta keputusan eksplisit Agent 2 dan approval kontrak UI oleh Agent 3. Catatan koordinator bukan approval owner Agent 3.
- Belum ada approval lintas-agent terverifikasi.

## IMPLEMENTASI DAN PENGUJIAN

Tidak ada DTO, parser DOCX, Composer dependency, migration, endpoint, table, batch service atau UI yang dibuat/diubah. Tidak ada parser security test atau PHPUnit yang dijalankan.

Next gate:
1. Finalisasi DTO schema.
2. Finalisasi API/preview/error contract.
3. Verifikasi migration ImportBatch uniqueness dan staging lifecycle.
4. Agent 3 menyetujui kontrak UI.
5. Threat model dan acceptance tests disetujui.
6. Izin implementasi terpisah.
