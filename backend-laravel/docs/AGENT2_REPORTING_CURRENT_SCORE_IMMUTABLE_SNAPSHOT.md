# Agent 2 — Reporting Current Score dan Immutable Report Snapshot

Tanggal: 22 November 2026
Branch: work/stage4d-account-lifecycle-20261122
HEAD sebelum dokumentasi: 7d63ea3b70b5191530c2c92c5c86bf92d7324db6
Source runtime: 74bf7178f46673e9be90523671490c0749034acd

## AUDIT — Current score

| File/class | Blob SHA | Temuan |
|---|---|---|
| ExamAttempt | 341c8d00337ad8761dca94602da7acfbdc769fb0 | score() melalui ExamAttemptScoreCurrent; resolvedCurrentScore() memvalidasi attempt_id, tenant_id, current_score_id dan scoring_version. |
| ExamAttemptScoreCurrent | d9d0841d9f8e2824b8f8d9d88b223a34c754c963 | Menyimpan pointer current_score_id dan scoring_version. |
| ScoringService | 1fc74eae97538ada140b9b4c75ccd4388d1a40ad | Mengunci pointer, memvalidasi target, dan membuat versi baru tanpa memakai MAX(version) sebagai sumber nilai aktif. |
| ExamSummaryReportService | 45d8f97eb9b2c75164a6f10a403e052b77696552 | Query langsung seluruh ExamAttemptScore untuk exam/tenant dan mengagregasi COUNT/AVG/MIN/MAX. Histori versi dapat ikut dihitung. |
| ExamResultReportService | d57eca45de94cc722826998f995369c56ad323d8 | Menggunakan relation score yang terhubung ke current pointer; tetap perlu uji FINALIZED dan NOT_SCORED terhadap pointer yang invalid. |
| StudentResultReportService | abc5eb0964983bd555c880c9d1d8fc4bc588d87b | Memuat scoreCurrentPointer.currentScore dan FINALIZED current score untuk finalized_only. |
| ReportHeaderService | 9cab29ad41aecce79a8729bc98f506f5c83a2452 | Resolusi logo memakai file_exists pada kandidat path; containment ke root storage perlu hardening. |

### Bug utama

ExamSummaryReportService::getSummary() dan getRombelSummary() mengagregasi semua row ExamAttemptScore, bukan hanya current pointer. Ketika scoring direvisi, histori versi dapat menggandakan total dan mengubah average/highest/lowest resmi.

Statistik resmi wajib mengambil tepat satu current score yang target pointer-nya valid dan grading_status FINALIZED. Score historis tetap disimpan untuk audit, tetapi tidak ikut statistik nilai final.

### Belum dinilai bukan nol

Template PDF exam-results.blade.php dan student-results.blade.php pada audit sebelumnya mengeluarkan angka 0 saat score null. Ini harus diubah agar:
- belum dinilai ditampilkan sebagai belum dinilai/kosong;
- nilai final 0 tetap ditampilkan sebagai 0;
- status MANUAL_REVIEW tidak disamakan dengan final;
- current pointer invalid tidak dianggap nilai 0.

### Snapshot laporan

Belum ditemukan model/lifecycle immutable ReportSnapshot atau mekanisme penerbitan laporan yang menjamin PDF berasal dari snapshot. PDF yang dihasilkan dari query live dapat berubah ketika data, assignment, branding, atau score direvisi.

## DESAIN — Kontrak laporan

1. Preview boleh membaca data live dan harus diberi label preview.
2. Penerbitan laporan membuat snapshot immutable dalam transaksi.
3. Snapshot mencakup:
   - tenant dan branding instalasi;
   - identitas Guru penandatangan berdasarkan assignment yang sah;
   - identitas Kamad berdasarkan appointment aktif pada waktu penerbitan;
   - tahun ajaran/semester;
   - exam/assessment dan daftar peserta sesuai scope;
   - current score pointer yang valid dan hanya FINALIZED;
   - status belum dinilai/manual review secara eksplisit;
   - tempat, waktu, timezone, actor penerbit;
   - template version, schema/report revision;
   - metadata sumber dan waktu snapshot.
4. Simpan hash payload snapshot dan hash artefak PDF.
5. PDF final dibangun dari snapshot, bukan query live.
6. Revisi membuat revision baru; dokumen terbit tidak ditimpa.
7. Idempotency key mencegah request penerbitan yang sama membuat dokumen ganda.
8. Snapshot dan PDF tidak boleh membuka akses baru ke Admin Madrasah terhadap Answer, answer key, Attempt, Score detail, atau live monitoring.
9. KAMAD tidak mendapat portal; identitas penandatangan berasal dari appointment yang sah, bukan role login.
10. Logo/file harus di-resolve hanya dari root storage tenant lokal yang diizinkan.

### Data appointment Kamad

Appointment perlu menyimpan identitas pejabat, tenant, tanggal mulai/akhir, sumber/nomor dokumen penetapan, actor pencatat, audit perubahan dan riwayat jabatan. Relasi ke TeacherProfile boleh ada, tetapi appointment tidak memberikan portal/permission.

## IMPLEMENTASI

Tidak ada model, migration, table, PDF, service, policy, atau template yang diubah. Tidak ada migration yang dibuat.

## PENGUJIAN

Acceptance tests:
- Histori scoring dua versi tetapi hanya current FINALIZED yang dihitung.
- Pointer missing/foreign/invalid/version mismatch ditolak.
- Current score FINALIZED bernilai 0 tetap berbeda dari belum dinilai.
- MANUAL_REVIEW tidak dihitung final.
- PDF final identik dengan snapshot walau source data berubah setelah terbit.
- Revisi laporan menghasilkan revision/hash baru; dokumen lama tetap utuh.
- Retry idempotent tidak membuat snapshot/PDF ganda.
- Appointment Kamad berubah setelah tanggal penerbitan tidak mengubah snapshot lama.
- Tenant/file/logo path tidak dapat menyeberang instalasi.

Tidak ada PHPUnit, PDF render, MySQL transaction test, atau immutable snapshot test dijalankan.

Owner backend reporting: Agent 2. Status: P1 DESIGN PENDING; migration/implementasi ditahan sampai kontrak lifecycle, retention, audit, permission dan preflight disposable disetujui.