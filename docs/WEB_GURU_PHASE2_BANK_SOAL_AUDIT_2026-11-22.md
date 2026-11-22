# PHASE 2 — AUDIT BANK SOAL WEB GURU
Tanggal: 22 November 2026
Branch audit/kerja: `work/web-guru-phase1-audit-implementation-20261122`
Source Agent 3 yang diperiksa: branch kerja, HEAD audit sebelum dokumen ini `dc3fa265a44356efc59d45c16f0a896ad523cd86`
Acuan: `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md`

Status dokumen ini: **AUDIT READ-ONLY**. Tidak ada source Bank Soal yang diubah dalam Phase 2.

## 1. AUDIT SOURCE

### 1.1 Komponen dan boundary
- Route Guru: `/guru/bank-soal`, menggunakan `App\\Livewire\\Admin\\QuestionBank` dan layout Guru.
- File komponen: `backend-laravel/app/Livewire/Admin/QuestionBank.php`, blob `ad7e6590592ee25c126b2886b739331eed3cc34e`.
- View: `backend-laravel/resources/views/livewire/admin/question-bank.blade.php`, blob `360312c228478f4b581481d2a9478b18ae30471d`.
- Component boot/mount memanggil `TeacherPortalAccess::profile()`, mewajibkan exact-one role GURU, user persisted, tenant context, dan satu TeacherProfile tenant-local. Permission `question.view` serta assignment mapel aktif juga diwajibkan.
- Action create/edit/publish/archive menggunakan Gate dan pengecekan assignment mapel. Policy Question pada branch Agent 2 terkini menggunakan helper exact-one role GURU; blob Agent 2 `af2b3be5fdee31b857c56fdf09ef54ffd02ed94e`.
- Nama namespace/view legacy Admin adalah technical debt. Route/layout tetap berada pada Portal Guru; jangan mengembalikan akses portal Admin.

### 1.2 Daftar dan pencarian
**EXISTS, PARTIAL**
- Menampilkan kode, stem, mapel, CP/TP, tipe, status, versi dan opsi.
- Filter search, mapel, status, pagination.
- Query Question mendapat tenant scoping melalui model `Question` yang memakai `TenantScoped`.
- Filter belum mencakup fase/kelas, versi kurikulum, ATP, topik/materi, difficulty, cognitive level, owner, dan rentang waktu sebagaimana target master spec.
- Belum ada detail/preview khusus yang terpisah dari kartu/list. Tampilan list memuat opsi dan indikator kunci untuk Guru.

### 1.3 Authoring dan validasi tipe
**EXISTS, PARTIAL**
- Form memuat kode soal, mapel, CP, TP, tipe, stem, explanation/rubrik, poin.
- MULTIPLE_CHOICE: A dan B wajib; C/D opsional; kunci wajib menunjuk opsi terisi.
- MULTIPLE_RESPONSE: A dan B wajib; kunci array minimal satu; distinct dan hanya A-D; setiap kunci harus menunjuk opsi terisi.
- TRUE_FALSE: opsi ditetapkan BENAR/SALAH; kunci A/B.
- MATCHING: minimal dua pasangan dan kedua sisi wajib diisi.
- ESSAY: menyimpan explanation sebagai pedoman/rubrik; scoring tetap manual review.
- SHORT_ANSWER tidak tersedia pada authoring baru. Tetap BLOCKED sampai accepted answers tersimpan, tersnapshot dan dievaluasi ScoringService.
- Field difficulty, cognitive_level, estimated_time, materi/topik, grade/fase, dan kurikulum/versi belum tersedia sebagai state/form di komponen ini walaupun sebagian kolom ada pada model QuestionVersion.
- Test existing belum membuktikan seluruh tipe, kombinasi opsi kosong/duplikat, batas poin, atau kasus manipulasi Livewire.

### 1.4 Versioning dan histori
**EXISTS, PARTIAL**
- Saat versi aktif PUBLISHED diedit, komponen membuat QuestionVersion DRAFT baru, memindahkan pointer current dan menandai Question DRAFT.
- Versi lama tidak diedit; opsi versi DRAFT dapat diganti.
- Archive menggunakan status ARCHIVED + soft delete pada Question; versions/options dipertahankan agar histori snapshot/attempt tetap dapat dirujuk.
- Belum ada bukti browser/E2E atau exact-SHA CI untuk lifecycle ini pada HEAD fase ini.

### 1.5 Integrasi kurikulum
**PARTIAL / NEEDS DESIGN**
- Komponen QuestionBank menggunakan model lama `LearningOutcome` dan `LearningObjective` untuk CP/TP.
- Halaman Pembelajaran menggunakan `MultiCurriculumVersion`, `MultiLearningOutcome`, `MultiCurriculumStructure`, dan `TenantCurriculumRegistration` melalui `TeacherCurriculumContextService`.
- Kedua jalur data belum terbukti terhubung melalui satu mapping/version/provenance yang konsisten.
- UI tidak menampilkan versi kurikulum, fase, kelas, ATP, Panca Cinta rinci, Pembelajaran Mendalam, atau dimensi lulusan pada form authoring.
- Jangan mengarang referensi CP/TP resmi atau menambahkan mapping baru tanpa kontrak data/owner Agent 2 dan master data Admin.

### 1.6 Media, RTL dan formula
**MISSING / BLOCKED**
- `QuestionVersion` memiliki relasi media ke `MediaAsset`; `ExamSnapshotService` menyalin metadata media ke snapshot.
- UI Bank Soal yang diperiksa belum memiliki upload, preview, alt text, pengurutan, penggantian atau penghapusan media.
- Textarea menerima Unicode/Arab/harakat, tetapi belum ada kontrol arah RTL/LTR, preview bidi, formula editor atau renderer yang diverifikasi.
- Media delivery dan public/private payload berada pada kontrak backend Agent 2/Agent 4; jangan membuat endpoint asumsi.

### 1.7 Import XLSX/DOCX
**BLOCKED**
- Route unduh template tersedia, tetapi komponen Bank Soal belum memiliki alur upload → security validation → staging → preview → correction → revalidation → confirm → report.
- Issue #8/#9 mengusulkan adapter XLSX/DOCX terpisah menuju satu `QuestionDraftDTO` dan satu lifecycle `ImportBatch/ImportBatchRow`.
- Proposal belum menjadi approval final untuk endpoint/DTO/error schema/retention/limits/authorization.
- Agent 3 tidak menambahkan parser, Composer dependency, batch service/tabel, migration, atau storage kedua sebelum kontrak Agent 2 disetujui.

### 1.8 Snapshot dan privasi
**CROSS-AGENT REVIEW REQUIRED**
- `ExamSnapshotService`, blob `d0bfcd70b2777a3884c7f92b7c256f05492645ec`, menyalin stem, explanation, options termasuk `is_correct`, score_weight, media storage_path dan metadata tipe soal ke snapshot.
- Snapshot creation mengunci Exam, memeriksa subject Assessment, mengunci QuestionVersion, dan mencegah perubahan setelah attempt/answer/score history.
- Karena snapshot memuat field privat, Student-facing payload harus secara eksplisit menghapus kunci, is_correct, accepted_answers dan explanation yang belum boleh tampil. Ini harus diuji pada controller/API dan media endpoint oleh Agent 2/4. Agent 3 tidak mengubah service snapshot atau API.

## 2. MATRKS STATUS

| Fitur | Status |
|---|---|
| List, search, filter, pagination | EXISTS, PARTIAL |
| Create/edit/archive | EXISTS, PARTIAL |
| MULTIPLE_CHOICE | EXISTS, PARTIAL |
| MULTIPLE_RESPONSE | EXISTS, PARTIAL |
| TRUE_FALSE | EXISTS, PARTIAL |
| MATCHING | EXISTS, PARTIAL |
| ESSAY | EXISTS, PARTIAL — manual review |
| SHORT_ANSWER | BLOCKED |
| Published version immutability | EXISTS, NOT RUNTIME VERIFIED |
| CP/TP legacy mapping | EXISTS, PARTIAL |
| Multi-curriculum version/provenance | BLOCKED / NEEDS DESIGN |
| Media authoring/preview | MISSING |
| Arabic RTL/harakat preview | MISSING / NOT VERIFIED |
| Formula editor/renderer | MISSING |
| XLSX upload/preview/confirm | BLOCKED |
| DOCX upload/preview/confirm | BLOCKED |
| Exam snapshot immutability | EXISTS, NOT RUNTIME VERIFIED |
| Student key/explanation privacy | CROSS-AGENT NOT VERIFIED |

## 3. DESAIN YANG DIBUTUHKAN SEBELUM PATCH

1. Tetapkan satu kontrak pemetaan CP/TP/ATP dari versi kurikulum terdaftar ke QuestionVersion; pertahankan referensi legacy/histori dan jangan mengubah ujian lama.
2. Lengkapi field authoring yang schema-nya sudah tersedia hanya setelah pemetaan field/validasi dan acceptance test disepakati.
3. Media UI menunggu lifecycle MediaAsset/private storage/delivery authorization dan cleanup Agent 2.
4. Import UI menunggu keputusan final Issue #8/#9.
5. Publish harus memastikan versi dan opsi memenuhi invariant tiap tipe; verifikasi apakah backend API/service sudah menjadi sumber validasi kanonis agar UI tidak membuat validasi kedua yang bertentangan.
6. Uji field privat snapshot pada semua Student API/media path dengan negative test.
7. Tetapkan satu owner untuk setiap file bersama; Agent 3 hanya mengubah komponen/view/test Guru.

## 4. PENGUJIAN

- Source/model/service/view/route dibaca statis.
- Tidak menjalankan PHP lint, PHPUnit, route:list, MySQL 8/InnoDB, browser E2E atau visual QA pada Phase 2.
- CI Phase 1 exact-SHA masih gagal sebelum runner/steps; belum ada bukti test runtime.
- Semua status fitur di dokumen ini bukan klaim lulus operasional.

## 5. LANGKAH BERIKUTNYA

1. Pulihkan CI runner dan dapatkan hasil PHP lint/PHPUnit exact-SHA.
2. Dapatkan kontrak final Agent 2 untuk kurikulum mapping, media, snapshot privacy dan import.
3. Audit semua QuestionBank actions dan regression matrix; baru setelah itu ajukan patch kecil per gap dengan backup.
4. Jangan membuka menu Monitoring/Penilaian/Analytics/Proktor sebelum dependency backend selesai.
