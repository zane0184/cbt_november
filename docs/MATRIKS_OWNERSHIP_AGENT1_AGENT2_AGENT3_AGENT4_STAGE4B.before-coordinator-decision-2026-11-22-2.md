# MATRIKS OWNERSHIP AGENT 1–4 — STAGE 4B / WEB SISWA
Tanggal: 22 November 2026
Status: USULAN KOORDINASI — MENUNGGU KONFIRMASI PEMILIK

| Komponen | Owner utama yang diusulkan | Batas |
|---|---|---|
| Canonical ExamSession, DB guard, Room, SessionRoom, Rombel mapping, placement peserta | Agent 1 | Agent 2 menyetujui DB/FK/tenant; Agent 3 UI konsumsi; Agent 4 tidak mengubah backend placement |
| Proctor assignment per room, scheduler, cancel/reschedule compatibility | Agent 1 | Attempt/recovery barrier bersama Agent 2/4; Agent 3 UI |
| Exam lifecycle, schedule authority, token, snapshot, serialization root | Agent 2 | Room/session state dari Agent 1; Agent 3/4 consumer |
| Attempt, Answer, Score, Force Submit, expiry, report current pointer, lease/fencing backend | Agent 2 | Agent 4 menyampaikan kebutuhan client; tidak menetapkan API backend |
| Exact-role, tenant/auth/session version, reporting | Agent 2 | Agent 3/4 mengikuti kontrak backend |
| Web Guru, UI room/session/placement/assignment, monitoring UI | Agent 3 | Hanya setelah API backend disetujui; tidak membuat bypass Admin/KAMAD |
| Web Siswa, admission rendering, IndexedDB, answer queue, offline recovery, submit barrier | Agent 4 setelah owner/branch dikonfirmasi | Backend authorization/receipt/lease dari Agent 2; room/recovery dari Agent 1 |
| QuestionVersion/media/accepted answers/RTL | Agent 2 backend; Agent 3 authoring UI; Agent 4 rendering consumer | Perlu kontrak data dan test enam tipe soal |

## Konfirmasi yang diminta
Setiap owner harus membalas langsung pada Issue #10 dengan status APPROVED / REJECTED / NEEDS DESIGN / PENDING, scope file/class, bagian matriks diterima/ditolak, transaksi/lock order, audit event, acceptance criteria, regression/integration test, dependensi dan risiko.

Matriks ini tidak memberi hak mengubah source owner lain dan bukan izin implementasi.
