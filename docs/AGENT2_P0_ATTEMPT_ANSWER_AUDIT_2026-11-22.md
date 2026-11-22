# Agent 2 — Audit P0 Attempt, Answer, Scoring, dan Admin CBT

Tanggal: 22 November 2026  
Repository: zane0184/cbt_november  
Branch kerja: work/agent2-p0-attempt-answer-scoring-20261122  
Base: 4e663cd84038b7c3337d776f702c499f20d8f537  
Status: AUDIT READ-ONLY SELESAI — PATCH P0 DAPAT DIMULAI DENGAN GUARDRAIL

## 1. AUDIT — Baseline dan lintas-agent

- Branch kerja dibuat dari SHA tepat 4e663cd84038b7c3337d776f702c499f20d8f537.
- Perbandingan branch baru terhadap SHA tersebut menghasilkan identical (ahead 0, behind 0).
- Agent 1: work/stage4b-room-session-20261122; checkpoint Agent 2 menyebut source CI f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5, CI run 44984303003 selesai SUCCESS dengan 35/35 langkah. HEAD branch 4e663cd... adalah dua dokumen setelah source CI tersebut.
- Stage4C pada branch Agent 1 memiliki 9 feature tests / 41 assertions dan 1 MySQL concurrency test / 8 assertions menurut checkpoint. Bukti run 449843 adalah source CI sebelum dua commit dokumentasi; implementasi test Stage4C dipertahankan dengan mengambil branch Agent 1 sebagai baseline.
- Branch Agent 2 lama work/stage4d-account-lifecycle-20261122 berisi pekerjaan lifecycle akun, auth epoch/session fencing, dan Admin boundary. Namun branch tersebut bukan pengganti baseline: Stage4C pada branch itu hanya 7 feature tests / 26 assertions dan Stage4B steps dilewati pada CI Agent 2. Tidak dilakukan merge/cherry-pick otomatis.
- Agent 3 dan kandidat Agent 4 diperiksa melalui checkpoint 22 November. File/komponen Guru Agent 3 dan UI Siswa Agent 4 tidak diubah. Kontrak endpoint harus diselaraskan sebelum integrasi.
- Branch Agent 1 masih membawa desain ExamSession/room-session yang checkpoint-nya sendiri nyatakan belum disetujui dan bertentangan dengan keputusan ujian serentak. Agent 2 tidak membuat, mengubah, atau mengintegrasikan domain ExamSession.

## 2. AUDIT — Temuan source P0

### Attempt
- ExamAttemptController::store() sudah memakai lock Exam → Participant → Attempt, memverifikasi token, attempt limit, eligibility, dan membuat attempt dengan waktu server.
- Resume attempt aktif yang masih valid dapat dilakukan setelah Exam ditutup melalui ExamPolicy::startAttempt(), selama participant masih eligible.
- submit() memakai lock Exam → Participant → Attempt dan menjalankan scoring dalam transaksi yang sama.
- Submit ulang untuk status SUBMITTED saat ini mengembalikan HTTP 422, bukan hasil sukses idempotent yang sama. Status EXPIRED sudah memiliki jalur sukses berulang.
- ExamController::show() dapat memuat snapshot untuk Siswa berdasarkan canStartAttempt(), sebelum token/attempt admission diverifikasi. Snapshot harus hanya diberikan melalui execution setelah admission server-side berhasil.

### Answer
- ExamAnswerController::update() mengunci Attempt lalu Answer, tetapi tidak mengunci Exam dan Participant lebih dahulu. Ini tidak konsisten dengan lock order start/submit/scheduler.
- index() belum mengunci parent rows dan belum menolak pembacaan jawaban untuk attempt terminal/expired.
- Idempotency saat ini memeriksa client_request_id pada row jawaban yang sama, tetapi tidak membandingkan payload/state yang dikirim ulang dan belum menolak pemakaian request ID yang sama pada snapshot lain dalam attempt yang sama.
- Revision conflict sudah mengembalikan jawaban server saat base revision tidak cocok; mekanisme ini dipertahankan.

### Scoring
- Scoring menggunakan snapshot ujian dan current-score pointer; pointer invalid/missing pada score history sudah fail-closed.
- ScoringService::evaluateShortAnswer() membaca metadata.accepted_answers lalu fallback ke snapshot_options. Namun ExamSnapshotService hanya menyalin difficulty, cognitive_level, dan question_type ke metadata; tidak menyalin accepted answers. QuestionVersion juga belum memiliki field accepted_answers. Karena itu SHORT_ANSWER belum konsisten dan harus diblokir, bukan diberi nilai tebakan.
- ESSAY normalnya menjadi MANUAL_REVIEW, tetapi jalur jawaban kosong diproses lebih awal sebagai AUTO_SCORED nol. ESSAY harus tetap melalui review Guru.
- ScoringController memiliki beberapa entrypoint yang mengunci Attempt tanpa mengunci Exam dan Participant terlebih dahulu. Lock order scoring harus diseragamkan.
- ExamSummaryReportService mengagregasi seluruh histori ExamAttemptScore, bukan hanya current pointer + FINALIZED. Versi historis/non-final dapat terhitung ganda.

### Role dan Admin
- ExamPolicy, ExamAttemptPolicy, AssessmentPolicy, dan ReportPolicy masih menggunakan hasRole() pada beberapa jalur dan masih memiliki cabang KAMAD. Ini tidak cukup untuk exact-one role dan tidak sesuai portal aktif Admin Madrasah/Guru/Siswa.
- MediaController memakai pola Guru dan bukan Admin, bukan exact-one role.
- ExamParticipantController memvalidasi peserta dengan hasRole('SISWA').
- QuestionController dan AssessmentController mengandalkan policy pada jalur utama; policy harus exact-role dan permission harus dibaca fresh.
- Test AdminCbtBoundaryTest saat ini membuktikan Gate/Livewire mount boundary, tetapi belum membuktikan seluruh endpoint HTTP direct dan tidak adanya mutasi DB untuk seluruh surface yang diminta.

### Lifecycle
- Pada baseline 4e, model User belum menggunakan SoftDeletes meskipun kolom deleted_at tersedia. Branch Agent 2 lama sudah memiliki implementasi edit/deactivate/reactivate, SoftDeletes, token revoke dan auth_version; pekerjaan itu akan ditinjau dan dipindahkan selektif setelah patch P0, bukan diulang dari nol.
- Pivot rombel_student belum merupakan ledger histori append-only. Tidak ada backfill status membership yang aman berdasarkan pivot lama; histori akademik memerlukan desain/reconciliation dan migration terpisah.
- Belum ditemukan route runtime untuk Reset Attempt, Force Submit, perpanjangan waktu, atau takeover pada baseline. Tidak ada hak tersebut yang ditambahkan secara implisit ke Admin atau Proktor.

## 3. DESAIN — Lock order sementara

Untuk jalur attempt-only yang tidak melibatkan room placement:

Exam → Participant → Attempt → Answer → ScoreCurrent → AttemptScore → QuestionScore

- Semua request yang menulis Answer, submit, scoring, finalisasi, reopen, dan expiry harus memakai urutan parent-first yang sama.
- Start Attempt baru ditolak sebelum jadwal, setelah Exam ditutup, token invalid, participant tidak eligible, atau attempt limit habis.
- Attempt IN_PROGRESS yang belum expired dan masih sah boleh resume setelah Exam ditutup untuk peserta baru.
- Room placement dan assignment lock order masih menunggu persetujuan lintas-agent; Agent 2 tidak mengubah source room/session Agent 1.
- Device Lease/fencing runtime belum menjadi dependensi yang terbukti pada branch ini. device_fingerprint bukan bukti fencing; tidak ada klaim single-device fencing siap produksi.

## 4. IMPLEMENTASI

Pada saat laporan audit dibuat, belum ada source runtime yang diubah. Branch kerja dan baseline telah diverifikasi. Backup akan dibuat sebelum patch P0 pertama.

## 5. PENGUJIAN

- CI run 44984303003: job safe-runtime-tests selesai SUCCESS; seluruh 35 langkah pada log job berstatus success atau skipped sesuai ownership (Stage4B room/session steps pada run Agent 2 lama bukan bukti).
- Stage4C evidence: 9 feature tests / 41 assertions dan 1 MySQL concurrency test / 8 assertions sesuai checkpoint Agent 1.
- CI run Agent 2 lama 44996834042 SUCCESS pada SHA 4dada4077e9dcd7c8ae767da3569cc58f02e6cc8, tetapi hanya menjalankan Stage4C branch Agent 2 sebanyak 7/26 assertions; ini bukan pengganti bukti Stage4C 9/41 di branch Agent 1.
- Belum ada test P0 baru yang dijalankan pada branch Agent 2 yang baru.
- Belum ada perubahan database, migration, VPS, atau deployment.

## 6. RISIKO DAN BLOCKER

1. Lock order bersama Agent 1 belum disetujui; room placement tidak boleh disentuh sebelum kontrak tersedia.
2. Device Lease/fencing tidak terimplementasi end-to-end pada baseline.
3. SHORT_ANSWER harus tetap nonaktif sampai accepted answers, authoring, snapshot immutable, dan scoring memiliki kontrak lengkap.
4. Admin direct API denial matrix belum exhaustive.
5. PHPUnit/MySQL 8 untuk patch baru wajib lulus sebelum status PASS.
6. Tidak ada akses SSH dan tidak ada akses database produksi; seluruh migration baru hanya boleh diuji pada disposable MySQL 8.

## 7. NEXT

1. Backup exact source files dan catat manifest.
2. Patch lock order Answer, idempotency key, submit idempotency, exact-role/permission, snapshot admission, SHORT_ANSWER fail-closed, ESSAY manual review, dan current-score reporting.
3. Tambah negative integration tests HTTP dengan assertion tidak ada mutasi.
4. Jalankan complete CI pada exact commit branch baru; verifikasi MySQL 8/InnoDB.
5. Port secara selektif lifecycle akun dari branch Agent 2 lama setelah audit dependensi dan backup; histori membership tetap desain/reconciliation only.
