# AUDIT LANJUTAN — FINALISASI, PUBLIKASI, DAN SCORING
Tanggal: 27 November 2026
Repository: `zane0184/cbt_november`
Branch: `fix/manual-question-optional-curriculum-20261127`
PR: https://github.com/zane0184/cbt_november/pull/44
Base: `integration/zip-vps-php83-20261126`
HEAD saat audit: `a8551ae80285041f624493d2b9bf5c971a9de48f`

## Status pemeriksaan
AUDIT READ-ONLY berdasarkan source GitHub branch HEAD. Tidak ada source runtime, migration, database, VPS, PR state, atau workflow yang diubah. Tidak menjalankan PHPUnit, CI, browser, runtime, integration, maupun concurrency test.

## 1. AUDIT

### 1.1 Verifikasi branch dan dokumen handover
- PR #44 OPEN, DRAFT, belum merged.
- Perbandingan base `integration/zip-vps-php83-20261126` terhadap head `fix/manual-question-optional-curriculum-20261127`: 109 ahead, 0 behind. Angka ini memperbarui checkpoint lama yang menyebut 72/74 ahead.
- Lima file inti yang dicatat checkpoint memiliki blob SHA yang cocok:
  - ResultDetail.php: `6033e20d64a90756df19718b496581939d3518ae`
  - ResultList.php: `0c15c2361e0d7941356133f7b4e041ecbcafe527`
  - ScoringController.php: `e82c86139524f79588e24043d0590f7203b68223`
  - ExamSnapshotService.php: `da10e1f13a0ec4269ca729fbb83c12c3e99a8721`
  - ExamAnswerController.php: `276ccf346f73382dbbcdc2ed380336c904acce12`
- Path QuestionBank dan DOCX yang benar di HEAD adalah `app/Livewire/Admin/QuestionBank.php` dan `app/Services/Questions/DocxQuestionDraftImporter.php`, bukan path lama Teacher/TeacherPortal yang tercantum di beberapa referensi.
- Path analytics report yang disebut dalam ringkasan lama belum berhasil di-resolve pada lokasi yang dicoba; lakukan pencarian GitHub berdasarkan class/method sebelum menyatakan file tidak ada.

### 1.2 Finalisasi single attempt
- Livewire `ResultDetail::finalize()` mengunci attempt, re-authorize pada attempt terkunci, memeriksa status, current pointer, target score/version dan MANUAL_REVIEW.
- API `ScoringController::finalize()` mengotorisasi sebelum transaksi tetapi tidak terlihat re-authorization sesudah `lockForUpdate()`. Ini perlu patch setelah audit lengkap seluruh authorization/policy and route context.
- Keduanya hanya menerima `SUBMITTED`; policy `viewScore` menerima `SUBMITTED` dan `EXPIRED`. Ini mendukung temuan kontrak status terminal belum konsisten.
- Current pointer dipakai sebagai sumber nilai aktif; jangan menggantinya dengan MAX(scoring_version).

### 1.3 Finalisasi massal
- `ResultList::confirmFinalizeAll()` membatasi awal query berdasarkan exam dan `SUBMITTED`, mengunci attempt, mengotorisasi finalize, memeriksa pointer/target dan MANUAL_REVIEW, kemudian menulis audit event.
- Attempt yang pointer-nya hilang/invalid dilewati dan dilaporkan ke logger; UI hanya menampilkan jumlah berhasil, belum daftar/jumlah detail yang dilewati.
- Preflight, eligible query dan proses finalisasi masih perlu diselaraskan pada current pointer, terminal status, tenant/install scope dan partial failure. Belum ada bukti runtime.

### 1.4 Publikasi dan akses hasil
- Toggle publikasi mengunci Exam, re-authorize, menulis old/new state ke audit dan invalidate analytics cache.
- Preflight menghitung score FINALIZED / MANUAL_REVIEW melalui relasi score. Perlu diverifikasi relasi itu current-pointer-only dan konsisten dengan query siswa.
- Policy siswa mengharuskan tenant, participant owner, exam published, terminal status `SUBMITTED|EXPIRED`, current score pointer valid dan FINALIZED.
- API ScoringController::show() untuk siswa mengembalikan summary terbatas dan details kosong. Namun alur ini tetap harus diaudit bersama Web Student, export, report, cache, notifikasi dan endpoint alternatif.
- Partial publication belum memiliki keputusan UX/preflight yang tuntas sebagaimana checkpoint.

### 1.5 Manual grading dan reopen/regrade
- Livewire saveScore re-authorize setelah lock dan memeriksa max_score dari question score yang telah dikunci.
- API updateManualScore mengotorisasi sebelum transaksi; validasi awal menggunakan max_score dari model route-bound; setelah lock mengambil ulang question score dengan scope attempt/tenant/exam/version, tetapi belum terlihat re-validasi awarded_score <= locked questionScore.max_score. Kandidat P1.
- API recalculate mengotorisasi sebelum lock dan perlu diperiksa ulang pasca-lock.
- ScoringService::reopenAttempt mengunci attempt → pointer → current score, memeriksa FINALIZED, membuat scoring version baru dan audit old/new summary + reason.
- Pada carry-forward, ScoringService::evaluateQuestion mengambil previous GRADED question score, tetapi perlu verifikasi apakah feedback, grader_id dan graded_at disalin ke record versi baru. Source yang diperiksa menunjukkan tidak ada field tersebut di ScoringService saat carry-forward; kemungkinan metadata ESSAY hilang setelah reopen perlu dibuktikan dengan membaca seluruh blok pembuatan record dan test.
- Audit ganda score/recalculate harus ditelusuri antara ScoringService dan controller sebelum menghapus event apa pun.

### 1.6 Perbedaan laporan vs source
- Laporan tahap 5B–5P yang dilampirkan dalam percakapan bukan bukti source pada HEAD branch ini. Pada branch saat ini, ExamAnswerController memakai AttemptAuthSessionBinding/AttemptDeviceLeaseService, sedangkan laporan lama menggunakan nama class/kontrak DeviceLeaseService berbeda. Audit ulang fencing harus memakai source HEAD; jangan mengasumsikan implementasi lama adalah status branch.
- Laporan terakhir mengenai trim opsi dan regression test extractor DOCX cocok dengan path/source aktual yang ditemukan.
- Source/test ada di branch; tes belum dijalankan sesuai larangan yang berlaku.

## 2. DESAIN / URUTAN KERJA YANG DIREKOMENDASIKAN

P0 — status terminal:
1. Telusuri semua transisi attempt (submit, expiry scheduler/command, force submit) dan scoring invocation.
2. Tetapkan satu definisi status terminal yang bisa dinilai/finalisasi.
3. Terapkan konsisten pada Livewire single/bulk, API finalize/recalculate, policy hasil, preflight publikasi, report/export.
4. Jangan membuka hasil EXPIRED sebelum FINALIZED dan publikasi aktif.

P1 — otorisasi dan data grading:
1. Re-authorize attempt terkunci pada API finalize, recalculate, manual grading, reopen bila applicable.
2. Revalidate nilai manual terhadap max_score dari row yang di-lock.
3. Pastikan pointer target tenant/attempt/version dan lock order seragam.
4. Pastikan audit event tunggal, bermakna, berisi before/after untuk perubahan manual/finalisasi/reopen.
5. Pastikan reopen menyalin metadata grading ESSAY yang memang harus carry-forward, tanpa mengubah histori versi lama.

P1 — finalisasi massal:
1. Tangani pointer anomaly secara terukur: hasil per peserta, skipped reason, retry aman.
2. Gunakan pointer current, bukan relasi historis ambigu.
3. Pastikan transaksi per attempt atau batch failure policy jelas.

P1/P2 — publikasi dan kebocoran:
1. Definisikan apakah partial publication memang fitur produk.
2. Preflight dan publish gate harus menampilkan incomplete/unfinalized/pending review dengan definisi current-score.
3. Audit Web Student, API, export, report, cache, notifikasi, route alternatif.
4. Hanya expose ringkasan milik siswa sendiri dan jangan bocorkan detail soal/kunci/rubrik/feedback tanpa keputusan produk eksplisit.

NEXT setelah scoring:
- Audit reopen/regrade, all scoring endpoints dan report aggregation current-pointer.
- Matriks feature coverage Admin/Guru/Siswa/Backend.
- Matriks Kurmer + KBC end-to-end.
- Audit paket ZIP / workflow build, README upgrade, backup/rollback.
- Tidak merge/deploy tanpa persetujuan.

## 3. IMPLEMENTASI
Tidak ada patch dibuat dalam audit ini. Tidak ada backup source baru karena source runtime tidak diubah.

## 4. PENGUJIAN
Tidak dijalankan: PHPUnit, CI, runtime, browser/Livewire, integration, MySQL concurrency, Android build/device. Tidak ada klaim PASS fungsional.

## 5. STATUS
AUDIT READ-ONLY LANJUTAN. PR #44 tetap OPEN/DRAFT/unmerged. Tidak siap produksi atau distribusi.
