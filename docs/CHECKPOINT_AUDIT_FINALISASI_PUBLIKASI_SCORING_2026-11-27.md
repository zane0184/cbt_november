# CHECKPOINT — AUDIT FINALISASI, PUBLIKASI, AUDIT LOG, ESSAY & AUTO SCORING
Tanggal: 27 November 2026
Repository: `zane0184/cbt_november`
Branch: `fix/manual-question-optional-curriculum-20261127`
PR: https://github.com/zane0184/cbt_november/pull/44
Base: `integration/zip-vps-php83-20261126`

## Posisi
- PR #44 OPEN, DRAFT, belum merged pada pemeriksaan terakhir.
- Compare terakhir: 74 commits ahead, 0 behind.
- Audit ini READ-ONLY. Tidak ada source code, test, migration, database, atau VPS yang diubah.
- PHPUnit, CI, runtime, browser, integration tests tidak dijalankan sesuai arahan pengguna.

## AUDIT — Hasil utama

### 1. Finalisasi single/bulk
- Livewire single finalize dan bulk finalize sudah menggunakan lock Attempt → pointer → score, validasi current pointer, dan menolak MANUAL_REVIEW.
- Livewire single finalize re-authorize setelah lock; bulk memeriksa policy finalize per attempt setelah lock.
- API ScoringController::finalize belum re-authorize setelah lock. API score/recalculate/manual grade juga perlu re-authorize setelah lock.
- Finalisasi bulk skip pointer anomaly dan hanya melaporkan jumlah berhasil; UI belum menyajikan daftar/jumlah attempt yang dilewati.
- Audit finalisasi single/bulk/API belum memiliki before/after payload yang memadai.

### 2. Temuan P0: EXPIRED tidak bisa difinalisasi
- ExamAttemptController menutup attempt sebagai EXPIRED saat deadline server lewat dan tetap menjalankan scoring.
- Student policy dan student result menerima EXPIRED sebagai terminal state.
- ResultDetail, ResultList bulk, dan ScoringController finalize hanya menerima SUBMITTED.
- Dampak: EXPIRED dapat memiliki skor tetapi tidak dapat difinalisasi lewat jalur normal; hasil siswa akan tertahan.
- Perbaikan berikutnya wajib menyatukan kontrak status terminal di UI/API/bulk/policy/report.

### 3. Publikasi dan akses Siswa
- Toggle publikasi mengunci Exam, re-authorize, audit old/new show_result, dan invalidate analytics cache.
- Policy hasil Siswa memeriksa ownership, tenant, published, terminal, pointer valid, dan FINALIZED.
- Student API mengembalikan DTO summary dan details kosong; report self-service membatasi visible/finalized dan memakai resolvedCurrentScore.
- Publish saat ini mengizinkan partial publication; gate per attempt mencegah siswa melihat nilai belum final. Perlu kejelasan UX/preflight.
- StudentExamController result belum mencatat REPORT_VIEW event.

### 4. Audit log dan score history
- resolvedCurrentScore memvalidasi pointer, target, tenant, attempt, dan scoring_version. Tidak memakai MAX version sebagai current.
- Reopen membuat version baru dan menyimpan old/new summary serta reason.
- API score dan recalculate berpotensi membuat audit ganda karena ScoringService dan controller sama-sama menulis event.
- Livewire saveScore tidak memiliki audit khusus UPDATE_MANUAL_SCORE, berbeda dengan API.
- Reopen membawa nilai GRADED ke version baru tetapi tidak membawa feedback/grader_id/graded_at.
- Perlu cek constraint unique dan foreign key pointer/version dari migration/schema source.

### 5. ESSAY dan auto scoring
- ESSAY rubrik diambil dari immutable snapshot, jawaban siswa ditampilkan Guru, dan MANUAL_REVIEW menahan finalisasi.
- Feedback/grader/timestamp dapat hilang pada reopen version baru.
- Formula source: MC/TF all-or-zero; TRUE_FALSE_GROUP proporsional tiga statement; MULTIPLE_RESPONSE partial credit hanya bila tidak ada pilihan salah; MATCHING proporsional pasangan; ESSAY manual; SHORT_ANSWER tetap blocked.
- QuestionReportService dan ExamSummaryReportService mengagregasi semua score versions tanpa filter current pointer. Setelah reopen/regrade statistik dapat menghitung histori lebih dari sekali.
- correct_count di QuestionReportService memakai awarded_score > 0, sehingga partial-credit dianggap benar; definisi metrik perlu diperjelas.

## DESIGN — Urutan prioritas
P0: Satukan status terminal SUBMITTED/EXPIRED untuk scoring/finalization/student publication.
P1: Re-authorize API pasca-lock; validasi ulang manual score max dan feedback setelah row terkunci.
P1: Audit manual grading UI/API, audit finalisasi dengan old/new, hilangkan duplicate score audit.
P1: Bawa metadata koreksi ESSAY pada reopen version baru.
P1: Summary/question reports hanya mengagregasi current score/current question score.
P2: Definisikan partial publication dan perbaiki preflight.
P2: Defensive evaluator validation dan definisi metrik benar penuh vs skor positif.

## IMPLEMENTASI
- Tidak ada patch source pada checkpoint ini.
- Tidak ada file source yang memerlukan backup karena tidak diubah.
- Laporan audit disimpan sebagai dokumen GitHub terpisah.
- Daftar blob SHA source yang diperiksa terdapat dalam laporan audit lengkap.

## PENGUJIAN
- PHPUnit/CI/runtime/integration tidak dijalankan.
- Static inspection saja; bukan bukti end-to-end.
- Tidak ada DB/migration/produksi/VPS yang disentuh.

## NEXT
1. Tangani EXPIRED vs SUBMITTED lebih dahulu.
2. API re-authorization setelah lock.
3. Audit per-butir manual grading dan audit duplicate.
4. Carry-forward feedback ESSAY pada reopen.
5. Current pointer filtering pada report aggregations.
6. Regression tests ditulis saja, belum dijalankan.


---

## CHECKPOINT TAMBAHAN — PERBAIKAN TOMBOL SIMPAN BANK SOAL / DOCX
Tanggal: 27 November 2026

### AUDIT
- Pengguna mengklarifikasi bahwa halaman Bank Soal dan Import DOCX dapat dibuka; hanya penyimpanan yang gagal.
- Jalur manual `QuestionBank::saveQuestion()` dan DOCX `DocxQuestionDraftImporter::import()` sama-sama memanggil `QuestionVersionCurriculumMappingService::sync()`.
- Mapping service sebelumnya mensyaratkan `starts_at` dan `ends_at` tidak NULL melalui `whereDate()`, sedangkan scope `TeacherSubjectAssignment::activeForDate()` mendukung NULL untuk masa berlaku terbuka.
- Ini merupakan penyebab statis yang masuk akal untuk kegagalan kedua save path; data runtime aktual belum diperiksa.

### DESAIN
- Samakan validasi assignment dengan scope `activeForDate()`.
- Tidak mengubah authorization, tenant scope, curriculum registration, atau aturan pemetaan.

### IMPLEMENTASI
- Source diperbaiki: `backend-laravel/app/Services/TeacherPortal/QuestionVersionCurriculumMappingService.php`.
- Backup: `backend-laravel/backups/question-save-nullable-assignment-dates-20261127/QuestionVersionCurriculumMappingService.php`.
- Blob sebelum: `2d2aa64e666d98d90c6276d16a4cf6c735be415c`.
- Blob sesudah: `bcb9a2722c013b9216b6663f7693b58f2d310e16`.
- Commit: `e1a58e7a0bec8990bb5cfa006d43747ee00000d0`.
- Tidak ada database/migration/VPS/deployment yang disentuh.
- Backup laporan dan checkpoint lama tersimpan di `docs/backups/question-save-nullable-assignment-dates-20261127/`.

### PENGUJIAN
- Fetch ulang source GitHub: PASS.
- PHPUnit/CI/runtime/browser: BELUM DIJALANKAN sesuai arahan.
- Belum mengklaim tombol Simpan berfungsi di produksi. Perlu uji runtime ketika diizinkan.

### LANJUTAN
- Periksa semua cabang validasi dan persistence pada manual save serta DOCX save.
- Tambahkan regression tests (jangan jalankan).
- PR #44 tetap Draft; tidak merge/deploy.
