# AUDIT LANJUTAN — FINALISASI, PUBLIKASI, SCORE HISTORY, ESSAY & AUTO SCORING
Tanggal: 27 November 2026
Repository: `zane0184/cbt_november`
Branch: `fix/manual-question-optional-curriculum-20261127`
PR: https://github.com/zane0184/cbt_november/pull/44
Base: `integration/zip-vps-php83-20261126`
Status PR saat pemeriksaan: OPEN, DRAFT, belum merged.
Compare terakhir: 74 commits ahead, 0 behind (wajib diperiksa ulang sebelum pekerjaan berikutnya).

> Audit ini bersifat READ-ONLY. Tidak ada source diubah, tidak ada backup source baru karena tidak ada file source yang diedit, tidak ada migration/database/deployment. PHPUnit, CI, runtime, browser, dan integration tests tidak dijalankan sesuai instruksi pemilik.

## A. AUDIT

### A1. Finalisasi nilai satu peserta

Source:
- `backend-laravel/app/Livewire/Teacher/ResultDetail.php` — blob `6033e20d64a90756df19718b496581939d3518ae`
- `backend-laravel/app/Policies/ExamAttemptPolicy.php` — blob `6c7dfb7ab633cb3d6b73b6f27b8fe65bdf65ef30`
- `backend-laravel/app/Models/ExamAttempt.php` — blob `543023af002c771af1de31df86832a52a912cbaf`

Temuan positif:
1. Livewire `ResultDetail::finalize()` mengunci attempt lebih dahulu, lalu re-authorize `finalize` terhadap model terkunci.
2. Hanya status `SUBMITTED` yang dapat difinalisasi pada jalur ini.
3. Current score pointer harus ada. Target score harus cocok pada `attempt_id`, `tenant_id`, dan `scoring_version`.
4. Finalisasi menolak butir dengan status `MANUAL_REVIEW`.
5. Finalisasi idempotent pada status FINALIZED: tidak membuat perubahan kedua.
6. Audit event `FINALIZE_SCORE` ditulis pada transaksi yang sama.

Gap:
- Audit event finalisasi Livewire belum menyertakan before/after status, scoring_version, actor/grader, dan alasan/perubahan yang cukup untuk rekonstruksi riwayat. Event dasar ada, detailnya minimal.
- UI `saveScore()` tidak menulis event audit khusus untuk perubahan nilai per butir. Pemanggilan `ScoringService::scoreAttempt()` hanya mencatat ringkasan score attempt; tidak menggantikan histori nilai/feedback ESSAY per pertanyaan.
- Query awal `editScore()` mengikat question score ke attempt dan scoring_version pointer, tetapi tidak eksplisit menambahkan exam_id dan tenant_id pada query tersebut; model memakai TenantScoped. Query save juga mengandalkan scope model. Tambahkan kondisi eksplisit agar kontrak konsisten dengan API.
- Reopen dipanggil melalui service setelah policy di controller/Livewire; service mengunci attempt tetapi tidak melakukan otorisasi domain ulang setelah lock. Perlu satu pola re-authorize pasca-lock di seluruh entry point.

### A2. Finalisasi massal

Source:
- `backend-laravel/app/Livewire/Teacher/ResultList.php` — blob `0c15c2361e0d7941356133f7b4e041ecbcafe527`

Temuan positif:
1. `confirmFinalizeAll()` membentuk kandidat dari attempt SUBMITTED dan current score status bukan FINALIZED/MANUAL_REVIEW.
2. Di dalam transaksi, setiap attempt dikunci lebih dahulu, status diperiksa ulang, policy `finalize` diperiksa, kemudian pointer dan score target dikunci.
3. Pointer hilang/target invalid tidak dipulihkan dengan menebak MAX scoring_version; kandidat tersebut dilewati dan dilaporkan.
4. Setiap score yang berhasil difinalisasi memiliki audit event `FINALIZE_SCORE`.
5. Cache analytics exam dan rombel diinvalidasi setelah batch.

Gap:
- Semua kandidat diproses dalam satu transaksi besar. Untuk ujian besar, transaksi dapat panjang dan menahan lock lama. Perlu keputusan desain batch/chunk dengan idempotensi dan hasil parsial eksplisit sebelum mengubah.
- Pointer hilang/invalid hanya masuk `report()` dan dilewati; hasil notifikasi hanya menyatakan jumlah yang berhasil. UI belum memberi jumlah dan daftar attempt yang dilewati karena anomali integritas.
- Audit event massal sama minimalnya dengan single finalize; tidak ada before/after payload yang memadai.
- Query kandidat awal dan preflight tidak menampilkan kategori pointer invalid/score history anomaly. Preflight dapat memberi kesan semua kandidat terhitung.
- Otorisasi per attempt sudah dilakukan di dalam loop, tetapi policy finalisasi bergantung assignment/TeacherAccess. Perlu cek lagi assignment aktif setelah lock bila model assignment dapat berubah paralel.

### A3. TEMUAN KRITIS — EXPIRED TIDAK DAPAT DIFINALISASI

Source:
- `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` — blob `043f31dd1a2c748839e778aac6b6e12ab1000787`
- `ResultDetail.php`, `ResultList.php`, `ScoringController.php` pada blob di atas.
- `backend-laravel/app/Policies/ExamAttemptPolicy.php` — blob `6c7dfb7ab633cb3d6b73b6f27b8fe65bdf65ef30`

Bukti source:
1. Submit controller menentukan terminal status `EXPIRED` bila `expires_at <= serverNow`; jika tidak, `SUBMITTED`.
2. Setelah terminalisasi, service scoring tetap dipanggil untuk kedua status.
3. `ExamAttemptPolicy::viewScore()` menerima status `SUBMITTED` atau `EXPIRED`, asalkan ujian dipublikasikan dan current score FINALIZED.
4. `StudentExamController::index()` dan `result()` juga menerima `SUBMITTED`/ `EXPIRED` sebagai terminal state.
5. Namun `ResultDetail::finalize()`, `ResultList::confirmFinalizeAll()`, dan API `ScoringController::finalize()` hanya menerima `SUBMITTED`.

Dampak:
- Attempt yang otomatis berakhir sebagai EXPIRED dapat memiliki skor awal, tetapi tidak bisa difinalisasi melalui tiga jalur finalisasi normal.
- Karena akses hasil Siswa mensyaratkan FINALIZED, nilai EXPIRED berpotensi tertahan dan tidak dapat dipublikasikan.
- Ini adalah kontradiksi lifecycle yang perlu diperbaiki terlebih dahulu. Kebijakan finalisasi harus menerima status terminal yang sah (`SUBMITTED` dan `EXPIRED`) atau proses expiry harus secara eksplisit menormalisasi ke status yang bisa difinalisasi. Pilihan harus konsisten di UI, API, bulk, policy, reporting, dan tests. Jangan hanya mengganti satu kondisi.

### A4. Publikasi hasil dan batas akses Siswa

Source:
- `backend-laravel/app/Livewire/Teacher/ResultList.php` — blob `0c15c2361e0d7941356133f7b4e041ecbcafe527`
- `backend-laravel/app/Policies/ExamAttemptPolicy.php` — blob `6c7dfb7ab633cb3d6b73b6f27b8fe65bdf65ef30`
- `backend-laravel/app/Http/Controllers/StudentExamController.php` — blob `5f937d31562ffcdee7fbd77df031650f4070980a`
- `backend-laravel/app/Http/Controllers/Api/V1/ScoringController.php` — blob `e82c86139524f79588e24043d0590f7203b68223`
- `backend-laravel/app/Http/Controllers/Api/V1/ReportController.php` — blob `e08ca28242c6a3613da0b7e45e7bebacd35068be`
- `backend-laravel/app/Services/Reporting/StudentResultReportService.php` — blob `abc5eb0964983bd555c880c9d1d8fc4bc588d87b`
- `backend-laravel/resources/views/student/results/show.blade.php` — blob `99bbba7ec5ddb3604f97807d205b3d6957bdfdac`

Temuan positif:
1. Publish/unpublish UI mengunci row Exam, re-authorize setelah lock, menyimpan old/new show_result di audit, dan menginvalidasi exam analytics cache.
2. Siswa hanya dapat melihat hasil attempt miliknya sendiri.
3. Policy viewScore memeriksa tenant, participant ownership, exam show_result, terminal status, pointer valid, target score cocok, dan FINALIZED.
4. API score show mengembalikan DTO summary minimal untuk Siswa dan `details: []`; evaluated answer, feedback, grader identity, dan detail per butir tidak dikirim ke Siswa.
5. StudentExamController result memuat pointer, memeriksa kepemilikan, publikasi, terminal state, lalu policy.
6. StudentResultReportService membatasi self-service pada published exam dan current pointer FINALIZED; hasil score dipasang dari `resolvedCurrentScore()`.
7. Student report API membentuk DTO kecil dan tidak mengirim Eloquent internals/score history.
8. Halaman hasil Siswa hanya menampilkan persentase, skor, status final, percobaan, waktu, dan waktu finalisasi.

Gap:
- Publish toggle tidak mewajibkan semua attempt final. Sistem saat ini mengizinkan publikasi parsial, sementara setiap siswa tetap digate secara individual. Ini dapat diterima hanya jika keputusan produk memang mengizinkan hasil bertahap; UI preflight harus menjelaskan jumlah yang belum final dan tidak boleh memberi kesan seluruh peserta telah memperoleh nilai.
- `startPublishModal()` menghitung finalized dan pending review dari semua attempt yang memiliki current score, lalu menghitung `unfinalized_submitted = submitted - finalized`. Angka finalized tidak dibatasi ke status terminal SUBMITTED/EXPIRED sehingga preflight dapat tidak presisi pada data anomali/attempt lain.
- API `ScoringController::show()` memanggil `hasRole('SISWA')` untuk DTO branch; policy viewScore saat ini sudah menggunakan exact role sehingga akses role ganda seharusnya ditolak, tetapi gunakan exact role secara konsisten pada controller.
- StudentExamController result view belum menulis audit event result view pada method ini, sedangkan ReportController studentResults menulis REPORT_VIEW. Perlu kebijakan audit konsisten untuk akses hasil individual.
- Uji semua route export dan report secara runtime tetap belum dilakukan. Source report menunjukkan student self-service filter/DTO, sementara exam exports dilindungi viewExamResults policy untuk Guru.

### A5. Audit log, current score pointer, dan riwayat nilai

Source:
- `backend-laravel/app/Services/ScoringService.php` — blob `4f88ea73d1c6ee17e7a259c66dc0a68c7779979c`
- `backend-laravel/app/Models/ExamAttempt.php` — blob `543023af002c771af1de31df86832a52a912cbaf`
- `backend-laravel/app/Models/ExamAttemptScore.php` — blob `2fdceaa21b1b0ce2f4524630c8fe02cddc2f2f73`
- `backend-laravel/app/Models/ExamAttemptScoreCurrent.php` — blob `d9d0841d9f8e2824b8f8d9d88b223a34c754c963`
- `backend-laravel/app/Models/ExamQuestionScore.php` — blob `34f9802f81e84c51c84f63f464aceb301f8ceb5f`
- `backend-laravel/app/Services/AuditService.php` — blob `d123f5c0ebf616fa9a30c0c1b3db5dd17067f336`
- `backend-laravel/app/Services/AuditPayloadSanitizer.php` — blob `d8017eb6ec7cd108eab7c39675dfa8bd4c49a2b7`

Temuan positif:
1. `resolvedCurrentScore()` hanya menerima pointer dan target yang cocok pada ID, attempt, tenant, dan scoring_version; tidak menggunakan MAX version sebagai current.
2. `scoreAttempt()` menolak pointer target invalid dan menolak pointer hilang jika score history sudah ada.
3. Reopen mengalokasikan scoring_version baru dari riwayat tertinggi hanya sebagai allocator; active source tetap current pointer yang tervalidasi.
4. Reopen menyimpan old/new score summary dan reason dalam audit event REOPEN_SCORE.
5. AuditPayloadSanitizer meredaksi feedback, grader ID, evaluated answer snapshot, answer payload, token, data pribadi, dan identifier sensitif.

Gap:
- API `score()` memanggil ScoringService yang sudah menulis SCORE_ATTEMPT/RECALCULATE_SCORE, lalu controller menulis SCORE_ATTEMPT lagi. Potensi audit duplikat.
- API `recalculate()` juga mencatat RECALCULATE_SCORE dari service dan controller. Tetapkan satu pemilik pencatatan event.
- Livewire manual score tidak menulis UPDATE_MANUAL_SCORE dengan old/new values, berbeda dengan API manual grading.
- Livewire single/bulk finalize hanya menulis event dasar tanpa old/new status/version payload.
- API finalize menulis event dasar tanpa old/new values; audit event harus dapat membuktikan perubahan status, version, actor, dan waktu.
- Audit sanitizer meredaksi `feedback` dan `grader_id` (sesuai privasi), sehingga audit nilai harus tetap menyimpan angka sebelum/sesudah, score row ID, snapshot ID, scoring_version, grading status, actor, dan timestamp; jangan mencoba menyimpan feedback mentah di audit.
- Reopen membuat score version baru. `evaluateQuestion()` membawa forward nilai untuk butir GRADED, termasuk ESSAY, tetapi `persistQuestionScore()` tidak membawa `feedback`, `grader_id`, dan `graded_at`. Catatan koreksi/rubrik feedback dapat hilang pada versi baru meskipun nilai manual tetap dipertahankan.
- Perlu audit constraint database untuk unique pointer per attempt, unique score version per attempt, dan foreign key pointer → score. Source model sendiri belum membuktikan constraint database.
- Cache invalidation dilakukan di dalam beberapa transaksi scoring/finalize/reopen. Efek cache sebelum commit perlu ditinjau; pertimbangkan afterCommit atau mekanisme outbox bila ada race stale cache.

### A6. Koreksi ESSAY dan penilaian otomatis

Source:
- `backend-laravel/resources/views/livewire/teacher/result-detail.blade.php` — blob `9908f787266899ec5b5c3c274d3f94104a26dfc3`
- `backend-laravel/app/Livewire/Teacher/ResultDetail.php` — blob `6033e20d64a90756df19718b496581939d3518ae`
- `backend-laravel/app/Services/ScoringService.php` — blob `4f88ea73d1c6ee17e7a259c66dc0a68c7779979c`
- `backend-laravel/app/Services/ExamSnapshotService.php` — blob `da10e1f13a0ec4269ca729fbb83c12c3e99a8721`
- `backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php` — blob `276ccf346f73382dbbcdc2ed380336c904acce12`

ESSAY:
- UI Guru menampilkan teks soal, jawaban siswa, rubrik dari `snapshot_explanation`, feedback, nilai, status, dan aksi koreksi.
- Snapshot menyimpan rubrik immutable; tidak membaca rubrik dari versi bank soal yang dapat berubah.
- ESSAY tanpa nilai manual menjadi MANUAL_REVIEW, dan finalisasi tertahan selama ada butir MANUAL_REVIEW.
- Guru dapat mengubah nilai/feedback melalui Livewire dan API.
- Gap utama: audit Livewire manual grading hilang; feedback/grader/timestamp tidak dibawa ke versi baru saat reopen; API grading perlu batas feedback max length yang konsisten dan validasi max_score setelah row terkunci; API grading belum re-authorize setelah lock.

Auto scoring source policy:
- MC / MULTIPLE_CHOICE / TRUE_FALSE / TF: skor penuh bila pilihan benar, selain itu nol.
- TRUE_FALSE_GROUP: tepat tiga statement; nilai proporsional benar/3, dibulatkan 2 desimal.
- MULTIPLE_RESPONSE: proporsional pada pilihan benar yang dipilih, tetapi bila ada pilihan salah yang ikut dipilih, skor nol.
- MATCHING: proporsional pasangan benar terhadap total pasangan pada snapshot.
- ESSAY: manual review, bukan otomatis.
- SHORT_ANSWER: evaluator legacy masih ada di ScoringService tetapi tetap diblokir oleh snapshot/API answer sesuai keputusan pemilik; jangan aktifkan.
- Unknown type: fallback MANUAL_REVIEW pada ScoringService, tetapi snapshot allowlist dan API answer fail-closed mencegah tipe tak dikenal menjadi soal aktif.

Gap penilaian otomatis:
- `QuestionReportService` mengagregasi semua `ExamQuestionScore` berdasarkan exam/snapshot tanpa join current pointer; setelah reopen versi lama dan baru dapat sama-sama masuk total_responses/average_score/correct_count.
- `ExamSummaryReportService` mengagregasi semua `ExamAttemptScore` pada exam tanpa membatasi ke current pointer; setelah reopen/regrade, total_scored, finalized_count, pending_manual_count, dan statistik rata-rata/min/max dapat menghitung histori versi berulang.
- `ExamResultReportService` memakai relasi current score pointer untuk tabel hasil, sehingga daftar hasil Guru lebih tepat daripada summary/question report. Pastikan export memakai relasi current pointer yang sama.
- `correct_count` pada QuestionReportService dihitung dari awarded_score > 0. Untuk MULTIPLE_RESPONSE dan MATCHING yang memberi partial credit, label "correct_count" berarti "mendapat nilai positif", bukan jawaban sepenuhnya benar. Perlu definisi metrik/label yang lebih akurat.
- Perlu audit aturan pembulatan konsisten antar tipe; TRUE_FALSE_GROUP membulatkan 2 desimal, MATCHING dan MULTIPLE_RESPONSE mengembalikan pecahan langsung, sedangkan total score disimpan decimal:4 dan percentage dibulatkan 2.
- `evaluateMultipleResponse()` mengasumsikan payload selected_option_ids array dan setiap ID valid. Validasi API answer adalah boundary, tetapi evaluator juga sebaiknya fail-closed untuk payload korup dari data lama.
- `evaluateMatching()` mengasumsikan matches berupa array terstruktur. API answer memvalidasi payload; evaluator harus fail-closed pada bentuk snapshot/payload korup dan uji kasus duplikasi/unknown IDs.
- Regression tests yang telah ditulis sebelumnya untuk tipe soal/snapshot belum dijalankan. Audit ini tidak menyimpulkan algoritma terbukti benar secara runtime.

## B. DESAIN / PRIORITAS PERBAIKAN

P0 — Lifecycle terminal:
1. Tentukan satu kontrak status terminal: SUBMITTED dan EXPIRED sama-sama dapat dinilai/finalisasi jika scoring valid; atau terminalisasi expiry harus menetapkan status yang konsisten.
2. Terapkan konsisten pada ResultDetail, bulk, API finalize, preflight, policy, student result, dan reporting.
3. Tambahkan regression tests (ditulis saja sampai izin pengujian berubah).

P1 — Otorisasi dan locking:
1. API score, recalculate, manual grading, finalize, reopen: re-fetch attempt scoped exam/installation, lock attempt, lalu re-authorize policy pada row terkunci.
2. Samakan urutan lock: Attempt → current pointer → attempt score → question score.
3. Validasi ulang questionScore ID, attempt, exam, installation, scoring_version, max_score setelah lock.
4. Hindari stale model binding dan query tanpa explicit exam/scope.

P1 — Audit:
1. Pilih satu tempat pencatatan event SCORE_ATTEMPT/RECALCULATE_SCORE agar tidak ganda.
2. Tambahkan event UPDATE_MANUAL_SCORE di Livewire dengan old/new score metadata yang disanitasi.
3. Lengkapi event FINALIZE_SCORE single/bulk/API dengan before/after status, score ID, scoring_version, actor dan timestamp.
4. Reopen version baru harus membawa forward metadata koreksi ESSAY yang aman (feedback/grader/graded_at) atau menyimpan histori relasi koreksi terpisah. Jangan menimpa histori lama.

P1 — Reporting:
1. Summary dan question report harus mengukur current score/current question score saja, dengan pointer tervalidasi.
2. Statistik historis harus dipisahkan sebagai laporan histori versi, bukan dicampur ke ringkasan nilai aktif.
3. Definisikan metrik benar penuh vs mendapat skor positif untuk tipe partial-credit.

P2 — Publication:
1. Pertahankan per-attempt gate: exam published + terminal + valid pointer + FINALIZED + ownership.
2. Perjelas apakah partial publication diizinkan; bila ya, tampilkan status jumlah siswa yang sudah/belum final. Bila tidak, publish harus memblokir saat masih ada terminal attempt belum final.
3. Audit event view hasil Siswa dibuat konsisten tanpa merekam isi nilai sensitif.

P2 — Scoring:
1. Tambahkan validasi defensive di evaluator walau API sudah memvalidasi.
2. Pertahankan policy SHORT_ANSWER disabled.
3. Uji formula MC, TF, TRUE_FALSE_GROUP, MULTIPLE_RESPONSE, MATCHING, ESSAY secara terisolasi saat test execution diizinkan.

## C. IMPLEMENTASI
- Tidak ada source code diubah pada tahap audit ini.
- Tidak ada patch dibuat.
- Tidak ada file backup source baru karena source tidak diubah.
- Laporan audit ini menjadi dasar patch tahap berikutnya.

## D. PENGUJIAN
- PHPUnit: tidak dijalankan (instruksi pemilik).
- CI: tidak dijalankan.
- Runtime/Browser/Livewire: tidak dijalankan.
- Integration/concurrency: tidak dijalankan.
- Yang dilakukan: fetch source branch kerja, pemeriksaan statis method/route/policy/service/model/view, perbandingan SHA blob.
- Tidak ada database, migration, VPS, atau data produksi disentuh.
- Semua temuan adalah hasil inspeksi source, bukan bukti runtime.

## E. FILE DAN BLOB SHA YANG DIPERIKSA
| File | Blob SHA |
|---|---|
| `app/Livewire/Teacher/ResultDetail.php` | `6033e20d64a90756df19718b496581939d3518ae` |
| `app/Livewire/Teacher/ResultList.php` | `0c15c2361e0d7941356133f7b4e041ecbcafe527` |
| `app/Http/Controllers/Api/V1/ScoringController.php` | `e82c86139524f79588e24043d0590f7203b68223` |
| `app/Services/ScoringService.php` | `4f88ea73d1c6ee17e7a259c66dc0a68c7779979c` |
| `app/Models/ExamAttempt.php` | `543023af002c771af1de31df86832a52a912cbaf` |
| `app/Models/ExamAttemptScore.php` | `2fdceaa21b1b0ce2f4524630c8fe02cddc2f2f73` |
| `app/Models/ExamAttemptScoreCurrent.php` | `d9d0841d9f8e2824b8f8d9d88b223a34c754c963` |
| `app/Models/ExamQuestionScore.php` | `34f9802f81e84c51c84f63f464aceb301f8ceb5f` |
| `app/Policies/ExamAttemptPolicy.php` | `6c7dfb7ab633cb3d6b73b6f27b8fe65bdf65ef30` |
| `app/Http/Controllers/StudentExamController.php` | `5f937d31562ffcdee7fbd77df031650f4070980a` |
| `app/Http/Controllers/Api/V1/ExamAttemptController.php` | `043f31dd1a2c748839e778aac6b6e12ab1000787` |
| `app/Http/Controllers/Api/V1/ReportController.php` | `e08ca28242c6a3613da0b7e45e7bebacd35068be` |
| `app/Services/Reporting/StudentResultReportService.php` | `abc5eb0964983bd555c880c9d1d8fc4bc588d87b` |
| `app/Services/Reporting/ExamResultReportService.php` | `19aedf1e501fe126a286f26f4e66ede0d030d4de` |
| `app/Services/Reporting/ExamSummaryReportService.php` | `715c2df7fff94eb1f91bd601e5e82cb8432307e6` |
| `app/Services/Reporting/QuestionReportService.php` | `2d9656c6eedc58a5298461507ab8f83982898033` |
| `app/Services/AuditService.php` | `d123f5c0ebf616fa9a30c0c1b3db5dd17067f336` |
| `app/Services/AuditPayloadSanitizer.php` | `d8017eb6ec7cd108eab7c39675dfa8bd4c49a2b7` |
| `resources/views/livewire/teacher/result-detail.blade.php` | `9908f787266899ec5b5c3c274d3f94104a26dfc3` |
| `resources/views/student/results/show.blade.php` | `99bbba7ec5ddb3604f97807d205b3d6957bdfdac` |
| `app/Policies/ExamPolicy.php` | `b2be589419b935a2f5e15dfe3985aaa212e55d71` |
| `app/Policies/ReportPolicy.php` | `6482e21e80ad68e7e6144f08e1b9d18fe6e6f326` |

## F. NEXT ACTION
1. Perbaiki P0 status EXPIRED vs SUBMITTED di semua jalur finalisasi secara konsisten.
2. Perbaiki re-authorization API pasca-lock dan validasi ulang row manual score.
3. Samakan audit manual grading UI/API dan hindari duplicate scoring audit.
4. Pertahankan feedback/grader metadata saat reopen versi baru.
5. Perbaiki report summary/question agar current pointer saja.
6. Tambahkan regression tests saja; jangan jalankan sebelum pengguna mengizinkan.
7. Buat checkpoint baru setelah patch dan static review.

PR #44 tetap Draft dan tidak di-merge. Tidak ada klaim siap produksi/distribusi dari audit ini.


---

## G. AUDIT & IMPLEMENTASI TAMBAHAN — TOMBOL SIMPAN BANK SOAL / IMPORT DOCX

Tanggal: 27 November 2026

### AUDIT
- Klarifikasi pengguna: halaman Bank Soal dan halaman Import DOCX dapat dibuka; kegagalan hanya terjadi saat penyimpanan. Audit diarahkan ke jalur save, bukan route/middleware/render.
- Kedua jalur penyimpanan memanggil `QuestionVersionCurriculumMappingService::sync()`:
  - `QuestionBank::saveQuestion()` untuk simpan manual.
  - `DocxQuestionDraftImporter::import()` untuk simpan hasil DOCX, termasuk saat pemetaan CP dan TP/ATP kosong (NOT_MAPPED).
- Di dalam `sync()`, query penugasan sebelumnya memakai `whereDate('starts_at', '<=', today)` dan `whereDate('ends_at', '>=', today)`.
- `TeacherSubjectAssignment::scopeActiveForDate()` secara eksplisit mendukung tanggal mulai/akhir NULL (open-ended), tetapi query di mapping service tidak. Akibatnya, penugasan ACTIVE yang tanggalnya NULL lolos dari pemilihan assignment di halaman/importer tetapi gagal ditemukan saat proses save.
- Ini adalah akar masalah statis yang dapat menjelaskan kedua tombol Simpan gagal. Belum ada bukti runtime yang mengonfirmasi data penugasan aktual menggunakan tanggal NULL; karena itu status temuan: **bug source teridentifikasi, runtime belum diverifikasi**.

### DESAIN
- Gunakan satu scope kanonis `activeForDate()` untuk validasi assignment pada mapping service, agar syarat aktif dan dukungan tanggal NULL konsisten dengan pemanggil lainnya.
- Pertahankan validasi tenant, teacher_profile_id, status aktif, curriculum registration, subject, serta validasi CP/TP lengkap atau kosong. Jangan melonggarkan otorisasi atau integritas mapping.

### IMPLEMENTASI
- File diubah: `backend-laravel/app/Services/TeacherPortal/QuestionVersionCurriculumMappingService.php`
- Perubahan: mengganti dua predicate tanggal non-null dengan `->activeForDate()`.
- Backup source byte-for-byte: `backend-laravel/backups/question-save-nullable-assignment-dates-20261127/QuestionVersionCurriculumMappingService.php`
- Source blob sebelum: `2d2aa64e666d98d90c6276d16a4cf6c735be415c`
- Source blob sesudah: `bcb9a2722c013b9216b6663f7693b58f2d310e16`
- Commit patch: `e1a58e7a0bec8990bb5cfa006d43747ee00000d0`
- Tidak ada perubahan route, tampilan, database, migration, VPS, atau data produksi.

### PENGUJIAN
- Fetch ulang source setelah update: PASS (perubahan terkonfirmasi di GitHub).
- PHPUnit/CI/browser/runtime: TIDAK DIJALANKAN sesuai instruksi pengguna.
- Belum dapat menyatakan tombol Simpan sudah berhasil di lingkungan madrasah sampai uji runtime diizinkan.
- Regression test ditambahkan pada `tests/Feature/QuestionBankTest.php` dan `tests/Feature/QuestionDocxImportFeatureTest.php` untuk assignment ACTIVE dengan starts_at dan ends_at NULL pada save manual dan DOCX. Test belum dijalankan.

### NEXT
1. Audit seluruh jalur save manual dan DOCX untuk prasyarat lain yang masih berbeda antara form dan service.
2. Tambahkan regression tests untuk tanggal NULL dan tanggal di luar masa aktif (ditulis saja, tidak dijalankan).
3. Uji kedua alur save pada lingkungan terisolasi saat izin pengujian berubah.
4. PR #44 tetap DRAFT, jangan merge/deploy tanpa instruksi eksplisit.
