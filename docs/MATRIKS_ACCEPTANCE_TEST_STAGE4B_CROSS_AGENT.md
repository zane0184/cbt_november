# MATRIKS ACCEPTANCE TEST — STAGE 4B CROSS-AGENT
Tanggal: 22 November 2026
Status: TEST PLAN — BELUM DIJALANKAN / BELUM SELURUHNYA DISETUJUI

## Konvensi
- Semua test database yang menguji lock/concurrency wajib berjalan pada MySQL 8+ / InnoDB disposable.
- SQLite hanya test tambahan untuk perilaku aplikasi; bukan bukti concurrency MySQL.
- Setiap test harus mencatat exact source SHA, migration set, DB identity, versi MySQL, engine, jumlah assertion, dan hasil.
- Test negative access harus memeriksa response serta tidak ada data bocor pada payload/log/export.
- Tidak ada test pada database produksi.

## Matriks
| ID | Area | Skenario | Acceptance criteria | Owner utama | Status bukti |
|---|---|---|---|---|---|
| ST4B-01 | Canonical session | Buat dua session paralel untuk satu Exam | Tepat satu operational session; request kedua idempotent atau ditolak dengan error domain; tidak ada dua canonical | Agent 1 | PENDING |
| ST4B-02 | DB guard | Dua koneksi bersamaan membuat canonical session | Unique guard menolak race; satu row operasional maksimum | Agent 1 | PENDING |
| ST4B-03 | Legacy 0 session | Exam tanpa session | Canonical dibuat dari jadwal Exam dalam transaksi dan diaudit | Agent 1/2 | PENDING |
| ST4B-04 | Legacy multi-session | Dua/lebih session dengan Attempt/Answer/Score/placement/history | Tidak auto-select; preflight menghasilkan NEEDS_MANUAL_DECISION; seluruh histori tetap ada | Agent 1/2 | PENDING |
| ST4B-05 | Schedule authority | Session mirror berbeda dari Exam schedule | Admission dan timer tetap menggunakan Exam start/end; mismatch ditandai dan tidak mengubah Exam otomatis | Agent 1/3 | PENDING |
| ST4B-06 | Token | Token dibuat/persist setelah publish | Token satu Exam; tidak ada token per room/session | Agent 2 | PENDING |
| ST4B-07 | Close vs start | Close Exam berbarengan dengan start Attempt baru | Operasi terserialisasi pada Exam; Attempt baru ditolak setelah close | Agent 2 | PENDING |
| ST4B-08 | Close vs resume | Exam ditutup saat Attempt IN_PROGRESS yang valid | Resume tetap bisa selama status, expiry, eligibility, dan server time sah | Agent 2/4 | PENDING |
| ST4B-09 | Save vs submit | Save Answer paralel dengan Submit/Force Submit | Lock order konsisten; tidak deadlock; jawaban terakhir yang sah masuk hasil; submit idempoten | Agent 2/4 | PENDING |
| ST4B-10 | Archive race | Create Attempt vs archive/destroy | Exam row lock serializes; tidak ada create setelah status terminal; histori tidak terhapus | Agent 2 | PENDING |
| ST4B-11 | Scheduler | ends_at tercapai sementara Attempt IN_PROGRESS | Session/assignment tidak diselesaikan/cabut monitoring sebelum aturan recovery terpenuhi | Agent 1/2/4 | PENDING |
| ST4B-12 | Bounded recovery | Assignment masuk NEEDS_REVIEW | recovery_until wajib bounded; sesudah batas, capability dicabut dan audit tercatat | Agent 1/2/4 | BLOCKED — duration decision |
| ST4B-13 | Cancel | Cancel session dengan Attempt aktif | Ditolak/guarded; tidak ada placement/assignment yang dilepas prematur; Exam admission tidak inkonsisten | Agent 1/3 | PENDING |
| ST4B-14 | Reschedule | Reschedule session tanpa update Exam | Ditolak compatibility guard; perubahan jadwal resmi melalui Exam dan schedule_revision naik | Agent 1/3 | PENDING |
| ST4B-15 | Room scope | Proktor A meminta Attempt peserta room B | 403/404 konsisten; tidak ada metadata/jawaban/media/event bocor | Agent 3 | PENDING |
| ST4B-16 | Scope matrix | Attempt, answer, media, activity, event, participant list, stats, export, Livewire | Semua jalur memakai server-side scope yang sama per request | Agent 3 | PENDING |
| ST4B-17 | Assignment | PENDING/REVOKED/EXPIRED/wrong revision | Tidak memiliki capability monitoring; hanya CONFIRMED dan valid window dapat memantau | Agent 1/3 | PENDING |
| ST4B-18 | Role boundary | Admin Madrasah dan KAMAD memanggil endpoint monitoring | Ditolak; tidak ada portal monitoring permanen KAMAD | Agent 2/3 | PENDING |
| ST4B-19 | Guru owner | Guru pemilik Exam memantau ujian sendiri | Hanya owner dengan permission/assignment sesuai policy; bukan jalur Proktor lintas room | Agent 3 | PENDING |
| ST4B-20 | Tenant isolation | Tenant A mengirim Exam/room/participant UUID Tenant B | Tidak ada cross-tenant disclosure; response anti-enumeration konsisten | Agent 1/2/3 | PENDING |
| ST4B-21 | Soft delete history | Proktor/assigner/revoker/student di-soft-delete | Histori tetap menampilkan actor; akun nonaktif tidak memperoleh capability | Agent 2 | PENDING |
| ST4B-22 | Score history | Score lama dan score FINALIZED current pointer | Report hanya memakai current pointer FINALIZED; versi lama tetap immutable | Agent 2 | PENDING |
| ST4B-23 | Device fencing | Dua device acquire/takeover | Epoch monotonic, satu device aktif, stale device ditolak server-side | Agent 2/4 | PENDING |
| ST4B-24 | Offline recovery | Offline answer sync setelah close/lease change | Idempotent, conflict explicit, no overwrite stale epoch, tidak hilang jawaban sah | Agent 4/2 | PENDING |
| ST4B-25 | Audit completeness | Cancel/reassign/revoke/force submit/takeover/recovery | Actor, tenant, entity, before/after, reason, request/idempotency key tercatat | Agent 1/2/3/4 | PENDING |
| ST4B-26 | Rollback guard | Rollback ketika room/session history ada | Fail-closed tanpa DDL/data loss; forward recovery only | Agent 1 | Existing test only; revalidate exact SHA |

## CI baseline yang diketahui
- Agent 1 run 44984303003 SUCCESS pada source f1d01ea...: Stage 4B management/concurrency/monitoring/proctor tests berjalan; tidak menguji operational guard satu-session.
- Agent 2 run 45003796892 dan 45008591113 BLOCKED — RUNNER, PHPUnit tidak berjalan.
- Agent 3 run 45003191589 FAILURE, 18 passed/2 failed/86 assertions pada source 834a59f...; exact-SHA test-fix belum diverifikasi.
- Candidate Agent 4 run 44949239676 SUCCESS bukan integrasi Stage 4B lintas branch.

## Exit criteria
1. Semua P0 tenant/role/room scope PASS pada exact integration SHA.
2. MySQL 8/InnoDB concurrency PASS untuk ST4B-02, 07, 09, 10, 11, 23.
3. Legacy preflight dan recovery policy disetujui.
4. Tidak ada approval implisit dari nama branch, dokumentasi, atau CI run lama.
5. Koordinator memberi izin eksplisit implementasi setelah seluruh owner menyetujui.
