# WG-01 — Audit dan Desain Import Soal XLSX/PDF
Tanggal: 22 November 2026
Branch Agent 3: `work/web-guru-foundation-20261122`

## Ringkasan status
Tahap audit source dan desain teknis awal selesai. Belum ada source aplikasi, migration, route, policy, dependency Composer, database, atau file milik Agent 1/2 yang diubah. Import XLSX/PDF belum siap digunakan.

## AUDIT

### 1. Posisi branch dan CI
- HEAD remote aktual: `25fe5e74a0754bcd110ceffac20141880c549ca8`.
- Parent HEAD: `ed36225496dc6f15287ce9f518cefd7ef27d8da7` (catatan source CI sebelumnya berada pada `e9c6867d40b134538e791bbbba02f5743afec7ea`; pemeriksaan commit menunjukkan HEAD terbaru hanya mengubah satu baris dokumentasi audit, bukan source aplikasi).
- CI run `44984094200`: SUCCESS, SHA `e9c6867d40b134538e791bbbba02f5743afec7ea`, job `teacher-portal-tests`, 16 step sukses termasuk PHP lint/route, PHPUnit, InnoDB dan cleanup database disposable.
- Dengan demikian source aplikasi pada HEAD saat ini sama dengan source aplikasi yang diuji pada run tersebut; commit setelahnya hanya koreksi dokumentasi.
- GitHub API hanya memberi committed tree/commit. Tidak ada local checkout/working tree yang dapat diperiksa melalui koneksi ini; tidak ada klaim status `git status` lokal.
- Branch Agent 1 yang diperiksa: `work/stage4b-room-session-20261122` HEAD `6815148248cb963b4083430fbea5e21056676a45`.
- Branch Agent 2 yang diperiksa: `work/web-admin-package1-implementation-20261122` HEAD `d7e2034ede3b044fc4ab0b900335eff3669b41ed`; branch import Guru/Siswa `work/import-guru-siswa-implementation-20261122` HEAD `f38ceec7b0653fcf3363eec40f3d4577c323cca8`.
- Tidak ada branch lain yang diubah, merge, rebase, PR, deployment, Android, Web Siswa, VPS atau database produksi.

### 2. Inventaris import yang sudah ada
**A. Import soal existing**
- `POST /api/v1/questions/import` pada `routes/api.php`.
- `QuestionController::import()` menerima JSON `rows[]`, bukan file XLSX/PDF.
- `ExcelQuestionImporter::import(string $filePath, array $options)` menerima baris yang sudah diparsing melalui `options['rows']`; parameter `$filePath` tidak digunakan untuk membuka workbook.
- `QuestionImportService` hanya memiliki satu method `import()`; belum ada kontrak preview, koreksi draft, confirm, cancel, report batch, PDF extraction atau OCR.
- `QuestionImportTest` menguji endpoint JSON dengan data array; tidak mengunggah file XLSX/PDF. Test yang ada mencakup MC, validasi required, matching, dan versi baru untuk soal published.
- Kesimpulan: sudah ada kerangka import JSON ke Question/QuestionVersion, tetapi belum ada import file Excel sebenarnya.

**B. Import Guru/Siswa existing — jangan disamakan dengan import soal**
- `ImportTemplateExport`, `ImportInstructionSheet`, `ImportDataSheet`, `ImportReferenceSheet`, `ImportTemplateController` dan `ImportWorkbookService` melayani provisioning akun Guru/Siswa dan penugasan Guru, bukan butir soal.
- Template saat ini memiliki sheet Petunjuk, Data Guru/Data Siswa, Penugasan Guru dan Referensi.
- `ImportBatch`/`ImportBatchRow` menyediakan checksum SHA-256, unique tenant/type/file, status preview/processing, row staging, encrypted payload dan ringkasan hasil.
- `ImportWorkbookService` sudah menerapkan preflight XLSX/ZIP/XML, preview, konfirmasi, transaksi per baris, retry/resume, audit, tenant guard, serta retensi staging.
- Pola dan model batch berpotensi dipakai ulang untuk import soal, tetapi service saat ini khusus ADMIN + `user.manage` + GURU/SISWA. Jangan memanggil atau memperluasnya tanpa koordinasi Agent 2.
- `ImportTemplateGeneratorTest` adalah test template Guru/Siswa; bukan test template soal.

### 3. Bank Soal, editor, model dan media
- Bank Soal existing: `App\\Livewire\\Admin\\QuestionBank`, `QuestionController`, `QuestionPolicy`, `Question`, `QuestionVersion`, `QuestionOption`, `MediaAsset`, `QuestionMultiCurriculumMapping`.
- `QuestionBank` menggunakan form textarea/input biasa. Belum ditemukan rich-text/equation editor atau tombol upload media pada form authoring.
- `MediaController` dapat menerima media JPG/JPEG/PNG/MP3/MP4/PDF (batas saat ini 10 MB), menyimpan private di tenant, dan mengaitkan media ke QuestionVersion dengan role STEM/OPTION/EXPLANATION. Snapshot ujian menyalin referensi media.
- Endpoint upload PDF sebagai media tidak sama dengan pembacaan/ekstraksi isi PDF.
- Stem, explanation dan option content disimpan sebagai teks. UI menampilkan teks dengan escaping dan line-break; belum ada renderer rumus MathJax/KaTeX atau editor RTL khusus.
- UTF-8 dapat menyimpan teks Arab/harakat, tetapi arah RTL, tata letak campuran Arab-Indonesia, dan preservasi struktur PDF/XLSX belum dibuktikan melalui UI/browser.
- Gambar yang telah diunggah dapat direferensikan melalui MediaAsset; ekstraksi gambar yang tertanam di workbook/PDF belum ada.

### 4. Audit kemampuan tipe soal CBT Engine
Source backend yang diperiksa: QuestionBank, QuestionController, ExcelQuestionImporter, ExamAnswerController, ExamSnapshotService dan ScoringService.
- Source mengenali enam tipe: `MULTIPLE_CHOICE`, `MULTIPLE_RESPONSE`, `TRUE_FALSE`, `MATCHING`, `SHORT_ANSWER`, `ESSAY`.
- ScoringService memiliki jalur penilaian untuk keenam tipe; ESSAY masuk MANUAL_REVIEW.
- MC dan TRUE_FALSE menggunakan satu selected option; MULTIPLE_RESPONSE menggunakan selected option IDs; MATCHING menggunakan pasangan left_id/right_id; SHORT_ANSWER memakai text; ESSAY memakai text dan penilaian manual.
- Batas opsi yang saat ini ditegakkan QuestionController/ExcelQuestionImporter/QuestionBank adalah A-D (maksimal empat). TRUE_FALSE memakai A dan B. Opsi E tidak didukung oleh kontrak authoring/API saat ini dan tidak boleh dimasukkan template sebagai opsi aktif.
- Matching perlu lebih dari satu pasangan. Representasi storage menggunakan option_key dan matching_key, sehingga template memerlukan sheet detail baris-per-opsi/pasangan, bukan satu kolom teks bebas.
- SHORT_ANSWER memerlukan accepted answers. Ada ketidaksesuaian yang perlu diperiksa Agent 2: ExcelQuestionImporter menulis `QuestionVersion.metadata.accepted_answers`, sementara migration awal `create_question_versions` tidak mendefinisikan kolom metadata dan ExamSnapshotService saat ini hanya menyalin difficulty/cognitive_level/question_type ke metadata snapshot. Jalur ini belum terbukti dapat menyimpan dan mensnapshot accepted answers secara benar.
- Bukti source support tipe tidak sama dengan hasil runtime test enam tipe. QuestionImportTest yang ada hanya menguji MC dan MATCHING.

### 5. Kesenjangan importer soal
- Endpoint JSON tidak menerima UploadedFile dan tidak memeriksa extension/MIME/ZIP structure.
- Tidak ada parser XLSX, versi template, preview, koreksi, confirm atau report import file.
- Importer saat ini membuat/ubah soal secara langsung dalam satu DB transaction; jika ada error validasi, transaksi dibatalkan seluruhnya. Tidak ada preview draft.
- Belum ada idempotensi file checksum, row-level fingerprint, duplikasi dalam file, atau duplikasi terhadap soal existing yang teruji.
- Hasil belum memiliki laporan jumlah duplicate terpisah.
- API import melakukan authorize create, tetapi QuestionPolicy::create dan guard QuestionBank hanya mensyaratkan hasRole GURU serta menolak ADMIN_MADRASAH; belum memastikan tepat satu role GURU. Ini blocker integrasi portal Guru sampai Agent 2 mengoordinasikan exact-role policy.
- Importer memeriksa subject melalui TeacherAccess, tetapi belum membuktikan seluruh metadata akademik yang diminta pada template disimpan dengan benar.

### 6. PDF dan OCR
- Composer/lock memuat `barryvdh/laravel-dompdf` (PDF generation) dan `maatwebsite/excel`/PhpSpreadsheet (XLSX). Tidak ditemukan package PDF text parser atau OCR/Tesseract wrapper.
- Tidak ditemukan kode parser PDF atau OCR dalam source aplikasi non-backup.
- PDF yang dapat diseleksi, PDF scan, tabel, gambar, rumus dan teks Arab belum diekstrak.
- Tidak ada bukti binary Poppler/pdftotext atau Tesseract beserta language data terpasang di VPS; environment VPS belum diuji.
- Status: PDF import/OCR belum tersedia.

### 7. Kurikulum Merdeka terintegrasi KBC
- CP/TP legacy tersedia melalui `LearningOutcome`/`LearningObjective`, dan QuestionVersion memiliki foreign key learning_outcome_id/learning_objective_id.
- Question memiliki subject/curriculum/phase/grade; QuestionVersion memiliki element/CP/TP, difficulty, cognitive_level, estimated_time, points dan explanation.
- KBC tersedia pada model multi-curriculum melalui `MultiLearningOutcome.outcome_type = KBC_CINTA`, versi/registrasi tenant, serta struktur `DIMENSI_CINTA`/`PETA_KASIH`. `QuestionMultiCurriculumMapping` dan migration mapping sudah ada.
- Form QuestionBank dan ExcelQuestionImporter belum mengintegrasikan mapping QuestionMultiCurriculumMapping.
- Tidak ditemukan provenance sumber resmi CP pada struktur LearningOutcome saat ini; jangan melabeli CP sebagai resmi/terverifikasi.
- Data Materi/Topik, Indikator, Sumber, dan tahun ajaran tidak memiliki field langsung yang teridentifikasi pada Question/QuestionVersion. QuestionVersion migration awal juga tidak mendefinisikan metadata JSON; perlu keputusan schema Agent 2 sebelum data tersebut dapat dijanjikan tersimpan.
- Kurikulum Merdeka dan KBC harus diperlakukan satu konteks terintegrasi; tidak ada dropdown untuk memilih salah satu menggantikan yang lain. Referensi KBC hanya boleh diambil dari versi/registrasi aktif yang sah; tidak membuat mapping Panca Cinta yang belum tersedia.

### 8. Konteks addendum Operasional Proktor (file lampiran kedua)
- Mekanisme assignment sudah ada: `ExamProctorAssignment`, `ExamProctorAssignmentService`, `ProctorAssignmentManagement` (Admin), `ExamPolicy::monitor`, `ExamMonitoringScope`, `MonitoringController`.
- Assignment memiliki tenant, exam, exam_session_room, user Guru, jadwal, schedule_revision, status, revoked_at dan audit log. Service assignment menolak kandidat yang bukan tepat satu role GURU dan memerlukan profil aktif; status PENDING perlu dikonfirmasi Admin; confirmedNow memeriksa masa berlaku dan revisi jadwal.
- ExamMonitoringScope membatasi peserta melalui ExamParticipantRoomAssignment untuk session-room yang ditugaskan.
- Web Guru belum memiliki menu/halaman Penugasan Proktor, Ruang Ujian yang Ditugaskan, Insiden atau Riwayat Tindakan. Route Admin assignment ada; monitoring tersedia melalui route/API per ujian.
- Monitoring menyediakan status attempt, last_activity_at, hitungan jawaban dan sync_health heuristik. Ini bukan bukti heartbeat/device online-offline; jangan menampilkan status koneksi pasti.
- ExamPolicy::monitor belum menerapkan exact-one-role secara eksplisit pada jalur GURU dan memberi jalur KAMAD; route monitoring tidak menggunakan TeacherPortalAccess. Ini perlu koordinasi Agent 2 karena policy/API merupakan source bersama.
- Belum ditemukan model/route/UI insiden ujian dan riwayat tindakan proktor khusus. Tidak ada portal Proktor permanen.
- Tidak ada perubahan pada modul Proktor dalam audit ini.

## DESAIN

### 1. Prinsip alur
`Upload → pemeriksaan file → parsing → normalisasi → validasi tenant/assignment/kurikulum/tipe → deteksi duplikat → draft preview → koreksi Guru → konfirmasi eksplisit → simpan ke Question/QuestionVersion/QuestionOption/media/KBC mapping yang sama → laporan batch/audit`.
- Tidak ada soal yang langsung dipublikasikan. Hasil awal selalu DRAFT.
- Jawaban/kunci hasil ekstraksi PDF adalah kandidat, bukan kebenaran. Jika tidak ada bukti jelas, status kunci `NEEDS_TEACHER_REVIEW`.
- Semua validasi diulang di server saat confirm; state Livewire bukan sumber kebenaran.

### 2. Desain template XLSX resmi
Versi usulan: `CBT-QUESTION-1` (belum dipublikasikan/diimplementasikan).
Sheet:
1. `Petunjuk` — versi template, instruksi, format teks/Unicode, contoh kunci, larangan formula/macro, kewajiban hapus contoh, alur preview-confirm.
2. `Data Soal` — satu baris per soal: External Row ID/No Soal, Kode Soal, Mata Pelajaran, Kelas, Tahun Ajaran, Materi/Topik, CP Ref, TP Ref, KBC Ref, Elemen KBC Ref, Jenis Soal, Pertanyaan, Pembahasan/Rubrik, Bobot, Kesulitan, Indikator, Referensi/Sumber, Media Ref.
3. `Detail Opsi` — satu baris per opsi/pasangan/jawaban diterima: No Soal, tipe detail, urutan, option_key A-D, isi, matching_key, benar/tidak, media ref. Sheet ini dibutuhkan agar MATCHING dapat memuat pasangan lebih dari dua dan SHORT_ANSWER dapat memiliki accepted answers tanpa format delimiter ambigu.
4. `Referensi Jenis Soal` — enam tipe yang didukung source beserta format jawaban dan validasi.
5. `Contoh Soal` — contoh terpisah yang tidak ikut diimpor.
6. `Referensi Akademik` (hidden/read-only) — subject/kelas/tahun ajaran/CP/TP/KBC refs yang benar-benar tersedia dan berada dalam tenant/assignment Guru. CP ditandai data lokal belum terverifikasi sumber resmi. Tidak ada pilihan Kurmer vs KBC.
- Opsi E tidak dimasukkan. Jika dukungan E diinginkan, harus menjadi perubahan engine/API/editor Android terkoordinasi sebelum template diubah.
- CP/TP/KBC dropdown memakai ID/ref stabil dan label; nilai yang tidak tersedia dibiarkan kosong dan ditandai perlu dilengkapi Guru/Admin. Tidak membuat CP atau mapping KBC otomatis.
- Media Ref mengacu pada MediaAsset tenant yang sudah diunggah; embedded image extraction merupakan pekerjaan terpisah.
- Materi/Topik, Indikator, Sumber, Tahun Ajaran dan metadata tambahan belum bisa dijamin tersimpan pada schema saat ini. Perlu keputusan Agent 2 tentang metadata versioned QuestionVersion/Question sebelum import commit.

### 3. XLSX import
- Gunakan PhpSpreadsheet melalui Laravel Excel yang sudah ada.
- Gunakan temporary upload private, batas ukuran/row/column/sheet, MIME + extension + magic/ZIP checks, ZIP entry/expanded-size limits, path traversal, external relationships, macro/embedded content, formula, XML DTD/ENTITY dan invalid workbook checks.
- Adaptasi pola keamanan `ImportWorkbookService` boleh dipelajari, tetapi service tersebut domain Guru/Siswa dan dimiliki Agent 2; jangan memanggil/mengubahnya tanpa koordinasi.
- Batch checksum SHA-256 untuk idempotensi file. Model `ImportBatch`/`ImportBatchRow` dapat dipertimbangkan ulang (type `QUESTION`, encrypted payload, row status, tenant, unique checksum), tetapi perlu persetujuan Agent 2 dan retensi scheduler harus dipastikan kompatibel.
- Preview menghitung total/valid/invalid/duplicate. Duplikat dalam file diperiksa dengan normalized fingerprint; duplikat terhadap bank soal existing perlu strategi transaksi/constraint yang disetujui Agent 2 agar race condition tidak lolos.
- Konfirmasi memproses baris valid secara atomik dan dapat dilanjutkan secara idempotent; setiap row menyimpan status. Kesalahan satu baris tidak boleh menghapus keberhasilan baris lain tanpa kebijakan yang jelas.
- Report memisahkan total, valid, failed, duplicate, imported, status batch, dan detail baris.

### 4. PDF dan OCR
- PDF digital: ekstraksi teks dengan parser PDF terpelihara atau Poppler `pdftotext -layout`; parser tidak mengeksekusi JavaScript/embedded file dan berjalan dalam batas resource.
- PDF scan: rasterisasi halaman terkontrol, lalu Tesseract OCR sebagai fallback jika binary dan language data `ind` + `ara` tersedia dan lulus uji. Harakat Arab tidak dijamin terdeteksi; hasil OCR selalu confidence-marked.
- Layout parser mengusulkan soal, pilihan, nomor halaman, tabel dan kandidat media; tidak menebak jawaban benar.
- Tabel, kolom, rumus, gambar, teks campuran Arab-Indonesia ditandai perlu review bila urutan/struktur tidak pasti.
- File sumber dan hasil antara disimpan private sementara dengan retention dan cleanup; PDF tidak menjadi soal sampai Guru memeriksa draft.

### 5. Integrasi Kurmer-KBC
- Satu alur akademik. Data Soal memiliki CP Ref, TP Ref dan KBC Ref pada satu record draft, bukan pilihan kurikulum saling menggantikan.
- CP/TP diambil dari data tenant dan subject yang sesuai; tandai provenance belum diverifikasi.
- KBC Ref diambil dari outcome KBC_CINTA pada versi kurikulum yang aktif dan terdaftar; elemen struktur DIMENSI_CINTA/PETA_KASIH hanya ditawarkan jika data aktif tersedia. Tidak mengarang pemetaan.
- Simpan mapping melalui QuestionMultiCurriculumMapping setelah Agent 2 mengonfirmasi kontrak model/schema dan tenant/system owner rules.

### 6. Matriks kepemilikan
- Agent 3: halaman import Guru, template export khusus soal, preview/correction UI, tampilan report, integrasi menu setelah gate akses.
- Agent 2 perlu koordinasi/persetujuan untuk: QuestionController/API, ExcelQuestionImporter/QuestionImportService, QuestionPolicy/BasePolicy, schema QuestionVersion metadata, QuestionMultiCurriculumMapping integration, batch service/retention, file upload/security service, PDF parser/OCR Composer dependency.
- Agent 1: tidak disentuh. Ruang/sesi/penempatan peserta bukan bagian import soal.
- File bersama `routes/web.php`, migrations, QuestionPolicy dan controller API tidak diubah pada tahap ini.

## IMPLEMENTASI
- Tidak ada source aplikasi, route, controller, Livewire, policy, model, migration, Composer dependency, workflow, atau database yang diubah.
- Hanya audit source dan rancangan teknis yang dicatat dalam dokumen WG-01 baru.
- Commit dokumentasi dibuat di branch Agent 3 saja.

## PENGUJIAN

### Hasil yang benar-benar diketahui
- PASS — CI Web Guru foundation run `44984094200`, SHA `e9c6867d40b134538e791bbb02f5743afec7ea`, 16/16 step.
- PASS (source audit, bukan runtime) — enam tipe soal tercantum pada validator/controller/scoring source.
- NOT RUN — test upload XLSX nyata ke endpoint import soal.
- NOT RUN — template XLSX soal (belum dibuat).
- NOT RUN — validasi kolom wajib, tipe tidak didukung, key invalid, duplicate, upload berulang, confirm idempotency, rollback/resume.
- NOT RUN — PDF digital, scan OCR, tabel, gambar, rumus, Arab/harakat, mixed RTL.
- NOT RUN — browser responsive, Light/Dark, preview/correction UI.
- NOT RUN — Proktor Guru portal UI, insiden dan riwayat tindakan.
- FAIL (capability gap) — opsi E belum didukung validator/editor saat ini.
- FAIL (capability gap) — PDF extraction/OCR belum tersedia.
- FAIL (capability gap) — QuestionImportService belum mengimpor file XLSX; endpoint menerima JSON rows saja.
- NOT RUN — runtime integration multi-tenant/permission for question-file import; no feature implemented.

### Keterbatasan
CI 44984094200 adalah CI foundation Web Guru, bukan bukti import soal. Tidak ada browser E2E atau test import PDF/XLSX pada tahap ini.

## BACKUP
- Tidak ada source existing yang diubah, sehingga tidak ada backup source yang perlu dibuat.
- Dua dokumen WG-01 di bawah merupakan file baru, bukan penggantian dokumen lama.
- Baseline Agent 3 dan backup sebelumnya tetap tidak diubah.

## STATUS
PARSIAL — audit dan desain teknis awal selesai. Import XLSX/PDF belum diimplementasikan dan belum siap digunakan. Tidak ada merge/PR/deploy/production migration.
