# CHECKPOINT OPERASIONAL — HANDOVER AGENT CBT MADRASAH
Tanggal: 27 November 2026
Repo: `zane0184/cbt_november`
PR aktif: https://github.com/zane0184/cbt_november/pull/44
Branch: `fix/manual-question-optional-curriculum-20261127`
Base: `integration/zip-vps-php83-20261126`

## 1. AUDIT — STATUS GITHUB TERKINI SAAT CHECKPOINT DIBUAT
- PR #44 berjudul **Fix manual question save without CP/TP mapping**.
- PR #44 tercatat OPEN dan DRAFT, belum merged.
- Compare branch base → head pada pemeriksaan terakhir: **72 commits ahead, 0 behind**.
- Base commit terverifikasi: `ed8c147e800568957de3eacaedf9f7745b23a302`.
- Angka compare dan status PR harus diperiksa ulang saat agent mulai bekerja; jangan dianggap permanen.
- Source GitHub branch kerja aktif adalah dasar audit. Tidak menggunakan ZIP baseline atau Library.

## 2. KEPUTUSAN PEMILIK YANG HARUS DIIKUTI
1. Instalasi mandiri satu madrasah per aplikasi dan satu database terpisah per madrasah.
2. Tidak ada multi-tenant lintas madrasah atau akses akademik antar-madrasah.
3. Layanan pusat hanya untuk identitas instalasi, lisensi, aktivasi/validasi, entitlement, masa berlaku, dan pembayaran/langganan. Tidak ada sinkronisasi data akademik/CBT.
4. Portal/role resmi hanya ADMIN MADRASAH, GURU, SISWA.
5. Semua pekerjaan Operator dilakukan Admin Madrasah. Role/portal Operator tidak digunakan.
6. Tidak ada portal/role Kamad. Kamad hanya metadata/jabatan. Role legacy KAMAD tidak digunakan untuk authorization.
7. Proktor bukan role permanen; hanya menu/tugas sementara di Web Guru berbasis assignment dan audit.
8. Android dan Web Super Admin ditunda hingga Juni 2027 sesuai instruksi terbaru pemilik. Fokus saat ini Backend + Web Admin Madrasah + Web Guru + Web Siswa.
9. Kurmer dan KBC/Panca Cinta merupakan satu kesatuan, bukan dua kurikulum terpisah.
10. Website operasional yang menjadi konteks: https://cbt.min15jakarta.sch.id/login. Jangan meminta ulang spesifikasi VPS; fokus pada kelengkapan fitur dan paket upgrade aman.
11. Seluruh laporan, checkpoint, backup manifest, dan perubahan harus tersimpan di GitHub.
12. Jangan mengulang pekerjaan yang telah selesai. Jangan menjalankan PHPUnit, CI, runtime, browser, atau integration tests sampai ada instruksi baru.

## 3. DESAIN / MODEL PRODUK
- Backend Laravel 13 / PHP 8.3, MySQL 8+ InnoDB, Blade + Livewire.
- Satu kontrak backend/API untuk Web Admin, Guru, dan Siswa.
- Data kurikulum harus mempertahankan registry/version/provenance/histori dan jejak akademik sampai immutable exam snapshot serta laporan.
- Jangan menghapus `tenant_id` atau legacy role secara membabi buta; audit dampaknya dan siapkan transisi upgrade yang aman.
- ZIP akhir harus satu paket lengkap, dapat di-upload dan diekstrak di VPS, tanpa .env/secret/data/backup internal/cache/test artifact.

## 4. IMPLEMENTASI YANG SUDAH ADA — JANGAN DIULANG

### 4.1 Manual Question Save / Curriculum Mapping
- Manual save menerima kedua mapping CP dan TP/ATP kosong untuk soal umum.
- Partial mapping ditolak.
- `question.manage` tetap wajib; `curriculum.manage` hanya wajib jika mapping dipilih.
- Assignment Guru aktif, subject, registration/version kurikulum, dan validasi scope tetap wajib.
- `NOT_MAPPED` membersihkan mapping version-wide dan mencatat audit.
- Metadata menyimpan status `NOT_MAPPED`/`MAPPED` dan konteks assignment untuk edit.

### 4.2 DOCX Import
- DOCX tanpa CP dan TP menjadi `NOT_MAPPED`.
- Mapping lengkap divalidasi; jika valid menjadi `MAPPED`.
- Mapping parsial/tidak valid menjadi `PENDING_TEACHER`.
- Override mapping per soal atomik; tidak mencampur satu sisi per-soal dengan default batch.
- Test regresi ditulis, belum dijalankan.

### 4.3 Publication Gate dan Exam Selection
- Publikasi soal menolak `PENDING_TEACHER`.
- Metadata mapping harus konsisten dengan baris mapping aktual.
- ExamManagement memfilter `PENDING_TEACHER`.
- ExamSnapshotService menolak `PENDING_TEACHER` dan mencegah campuran mapped/unmapped.
- Backup source ada di `backend-laravel/backups/docx-not-mapped-import-20261127/`.

### 4.4 MATCHING dan ESSAY
- MATCHING manual save memvalidasi array, minimal dua pasangan, isi tidak kosong.
- Publication dan snapshot memvalidasi jumlah seimbang, key unik, pasangan satu-satu.
- ESSAY wajib memiliki explanation/rubrik saat save, publish, dan snapshot.
- Snapshot menyimpan rubrik pada `snapshot_explanation`; halaman koreksi Guru mengambil rubrik dari snapshot immutable.
- Regression tests ditulis, belum dijalankan.

### 4.5 Question Type Contract
Tipe aktif yang diterima editor Guru:
- MULTIPLE_CHOICE
- MULTIPLE_RESPONSE
- TRUE_FALSE
- TRUE_FALSE_GROUP
- MATCHING
- ESSAY

DOCX importer:
- MULTIPLE_CHOICE
- MULTIPLE_RESPONSE
- TRUE_FALSE
- TRUE_FALSE_GROUP
- ESSAY
- MATCHING belum didukung DOCX.

API answer dan snapshot:
- MC/MULTIPLE_CHOICE
- TRUE_FALSE/TF
- TRUE_FALSE_GROUP
- MULTIPLE_RESPONSE
- MATCHING
- ESSAY
- Unknown type ditolak (fail-closed).
- `SHORT_ANSWER` tetap BLOCKED. Jangan diaktifkan.

### 4.6 Scoring / Manual Grading
- Scoring service menangani MC, TF, TRUE_FALSE_GROUP, MULTIPLE_RESPONSE, MATCHING, ESSAY; evaluator legacy SHORT_ANSWER bukan fitur aktif.
- ESSAY tanpa koreksi menghasilkan status MANUAL_REVIEW.
- API manual grading mengunci ulang QuestionScore berdasarkan attempt, tenant, exam, dan scoring version current pointer.
- UI manual score memvalidasi numeric >= 0, feedback nullable string max 5000, dan nilai tidak lebih dari max_score snapshot.
- Recalculation menggunakan ScoringService dan versioned score.

### 4.7 Finalisasi dan Publikasi — bagian source sudah ada, audit lanjutan masih diperlukan
- ResultDetail::finalize() memvalidasi attempt SUBMITTED, current pointer dan target score version; menolak pending MANUAL_REVIEW; mengubah grading_status FINALIZED; menulis audit `FINALIZE_SCORE`.
- ResultList finalisasi massal menulis audit untuk setiap skor yang difinalisasi.
- ResultList preflight menampilkan total, submitted, finalized, pending_review, unfinalized_submitted.
- Toggle publikasi mengunci Exam, re-authorize, mengubah `show_result`, menulis audit PUBLISH/UNPUBLISH, invalidasi analytics cache.
- ScoringController::show() memvalidasi current score pointer dan target; untuk SISWA mensyaratkan score FINALIZED dan mengembalikan summary tanpa details.
- Ini baru temuan source-level. Audit menyeluruh seluruh jalur API, Livewire, export, cache, reopen/regrade, dan race condition **belum selesai**.

## 5. FILE SOURCE TERAKHIR YANG DIVERIFIKASI
| File | Blob SHA |
|---|---|
| `backend-laravel/app/Livewire/Teacher/ResultDetail.php` | `6033e20d64a90756df19718b496581939d3518ae` |
| `backend-laravel/app/Livewire/Teacher/ResultList.php` | `0c15c2361e0d7941356133f7b4e041ecbcafe527` |
| `backend-laravel/app/Http/Controllers/Api/V1/ScoringController.php` | `e82c86139524f79588e24043d0590f7203b68223` |
| `backend-laravel/app/Services/ExamSnapshotService.php` | `da10e1f13a0ec4269ca729fbb83c12c3e99a8721` |
| `backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php` | `276ccf346f73382dbbcdc2ed380336c904acce12` |

Catatan: blob SHA adalah bukti identitas file saat diperiksa, bukan bukti runtime. Fetch ulang sebelum patch.

## 6. BACKUP YANG SUDAH ADA
Semua path berikut berada di repo:
- `backend-laravel/backups/manual-question-optional-curriculum-20261127/`
- `backend-laravel/backups/docx-not-mapped-import-20261127/`
- `backend-laravel/backups/matching-essay-hardening-20261127/`

Backup relevan untuk tahap scoring:
- `ResultDetail.php.pre-finalize-audit-log`
- `ResultList.php.pre-finalize-audit-log`
- `ScoringController.php.pre-score-row-scope-hardening`
- `ResultDetail.php.pre-manual-score-input-validation`
- `ExamSnapshotService.php.pre-question-type-allowlist`
- `ExamAnswerController.php.pre-question-type-fail-closed`
- Backup test dan Blade juga tersedia dalam folder yang sama.

Jangan mengubah backup. Untuk setiap patch baru, buat backup baru dengan nama tahap/tanggal yang jelas dan verifikasi byte-exact/blob SHA sebelum edit.

## 7. PENGUJIAN — BATAS YANG BERLAKU
- PHPUnit: TIDAK DIJALANKAN sesuai instruksi pengguna.
- CI: TIDAK DIJALANKAN sesuai instruksi pengguna.
- Runtime/browser/Livewire: TIDAK DIJALANKAN.
- Integration/concurrency/multi-device: TIDAK DIJALANKAN.
- Regression tests untuk DOCX, MATCHING, ESSAY, type allowlist, TF alias telah ditulis, tetapi BELUM DIJALANKAN.
- Source-level static inspection dan GitHub blob SHA check dilakukan. Itu bukan bukti fungsi end-to-end.
- Tidak ada database produksi, migration produksi, atau deployment VPS yang disentuh dalam pekerjaan checkpoint ini.

## 8. PEKERJAAN BERIKUTNYA — URUTAN WAJIB

### NEXT-01: Audit finalisasi single attempt
Periksa ulang source aktual:
- authorization sebelum dan sesudah lock;
- attempt ownership/exam/local installation scope;
- lock order attempt → current pointer → score → question scores;
- current_score_id dan scoring_version harus menunjuk target valid;
- SUBMITTED/terminal state;
- pending MANUAL_REVIEW;
- idempotensi finalisasi;
- audit log dan cache invalidation;
- reopen/regrade tidak boleh mengubah histori tanpa versi baru.

### NEXT-02: Audit finalisasi massal
- Query harus dibatasi exam/instalasi dan peserta yang sah.
- Setiap attempt diproses atomik atau mekanisme batch yang aman.
- Tidak boleh melewati MANUAL_REVIEW.
- Re-authorize dan recheck pointer di dalam lock/transaction.
- Audit event harus konsisten per attempt yang benar-benar berubah.
- Periksa partial failure, retry, dan cache invalidation.

### NEXT-03: Audit publikasi hasil
- Preflight menghitung status secara konsisten.
- Publish/unpublish terserialisasi.
- Publikasi tidak boleh membuka skor yang belum FINALIZED.
- Siswa hanya melihat hasil miliknya setelah `show_result=true`, attempt terminal, pointer valid, score FINALIZED.
- Periksa halaman Web Siswa, API, export, analytics, notifikasi, dan route alternatif untuk kebocoran.
- Detail per soal/rubrik/kunci/feedback privat tidak dikirim ke Siswa kecuali ada keputusan produk yang secara eksplisit mengizinkan.

### NEXT-04: Audit reopen/regrade dan seluruh scoring endpoints
- Audit reason/permission, audit log, score version, pointer, feedback, grader, cache, dan publish state.
- Periksa endpoint `score`, `show`, `updateManualScore`, `finalize`, reopen, serta semua Livewire action.
- Cari jalur alternatif yang tidak melalui policy/assignment.

### NEXT-05: Regression test (hanya tulis)
- Tambahkan test bila gap belum tercakup.
- Backup test file sebelum edit.
- Jangan menjalankan PHPUnit/CI/runtime sampai instruksi baru.

### NEXT-06: Audit fitur seluruh portal dan integrasi
- Susun matriks fitur Admin/Guru/Siswa/Backend.
- Tandai VERIFIED IN SOURCE / PARTIAL / MISSING / CONFLICT / NOT VERIFIED AT RUNTIME.
- Audit Kurmer+KBC menyatu di seluruh alur.

### NEXT-07: Paket ZIP
- Audit workflow build ZIP dan dokumentasi deployment di GitHub.
- Tentukan struktur paket final dan daftar file terlarang.
- Siapkan README upload/extract/backup/preflight/upgrade/rollback.
- Verifikasi artifact dan checksum setelah tahap pengujian yang disetujui.
- Jangan deploy atau merge tanpa persetujuan eksplisit.

## 9. LARANGAN KERAS AGENT PENERUS
- Jangan mengulang patch yang sudah ada.
- Jangan memulai ulang proyek.
- Jangan membaca ZIP baseline atau ChatGPT Library.
- Jangan meminta spesifikasi VPS lagi.
- Jangan mengerjakan Android atau Web Super Admin sebelum Juni 2027.
- Jangan membuat portal/role Operator atau Kamad.
- Jangan membuat role Proktor permanen.
- Jangan menghidupkan SHORT_ANSWER.
- Jangan menjalankan PHPUnit/CI/runtime/integration tests tanpa instruksi baru.
- Jangan merge, migration produksi, atau deployment tanpa persetujuan.
- Jangan menyatakan siap produksi/siap distribusi tanpa seluruh gerbang terpenuhi.

## 10. LANGKAH PERTAMA AGENT BARU
1. Verifikasi ulang PR #44, branch HEAD, base, compare, dan status merge.
2. Baca `README.md`, `docs/KEPUTUSAN_ARSITEKTUR_INSTALASI_MANDIRI_DAN_ROLE_2026-11-23.md`, dokumen prompt ini, dan checkpoint terkait.
3. Fetch ulang empat file source di Bagian 5.
4. Audit NEXT-01 sampai NEXT-04 secara READ-ONLY terlebih dahulu.
5. Buat laporan audit baru di GitHub dan lanjutkan patch minimum setelah desain aman.
6. Jangan menjalankan test yang dilarang.

**Checkpoint ini adalah peta posisi, bukan klaim siap produksi. Source HEAD terbaru tetap menjadi dasar kebenaran.**
