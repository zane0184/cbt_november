# Agent 4 — Permintaan Kontrak Room, Proktor dan Recovery kepada Agent 1

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch dokumentasi: `work/agent4-web-siswa-audit-20261122`  
HEAD dokumentasi sebelum dokumen ini: setelah tiga commit dokumen lanjutan pada branch yang sama.  
Owner: Agent 1 untuk ExamSession/Room/Placement/Proktor assignment; Agent 2 untuk Attempt/Lease/Fencing; Agent 4 untuk UX recovery Web Siswa.  
Status: **NEEDS DESIGN / CROSS-AGENT APPROVAL PENDING**.

## 1. Keputusan arsitektur dan scope

Satu instalasi = satu madrasah = satu database akademik sendiri. Tidak ada shared academic database atau sinkronisasi akademik lintas instalasi. Web Siswa tidak boleh menganggap satu database pusat multi-madrasah.

Sampai Juni 2027 hanya Web Admin Madrasah, Web Guru dan Web Siswa. Android native dan Super Admin ditunda.

## 2. AUDIT — sumber yang diperiksa

Remote HEAD saat refresh:
- Agent 1: `work/stage4b-room-session-20261122` @ `d2532792471966f1d0a9d9cbdd4d65e7c84547f0`.
- Agent 2: `work/stage4d-account-lifecycle-20261122` @ `7d63ea3b70b5191530c2c92c5c86bf92d7324db6`.
- Agent 3: `work/web-guru-foundation-20261122` @ `bb22fbd1ea3181fdec898109a5d9c5110f2a0890`.
- Kandidat Agent 4: `work/import-guru-siswa-implementation-20261122` @ `f38ceec7b0653fcf3363eec40f3d4577c323cca8`, owner belum terkonfirmasi.

Dokumen Agent 1 yang diperiksa:
`docs/KONTRAK_INTEGRASI_AGENT1_AGENT2_AGENT3_EXAM_SESSION_2026-11-22.md`, blob `80bdb8c678965554422fc492e4ebe767e2b73d55`.

Source Agent 1:
- `backend-laravel/app/Services/ExamSessionLifecycleService.php`: `8e34152afd817f30cc2f81b5f25fc450571463ac`.
- Kontrak Stage 4B menyatakan satu Exam = satu ExamSession operasional; banyak room di bawah satu session.
- Jadwal resmi hanya Exam.start_at/end_at; session/room/placement bukan admission gate.
- Proktor adalah Guru dengan assignment sementara per room.
- Attempt/Answer/Score tidak dipindah saat rekonsiliasi room/session.
- Scheduler tidak boleh menyelesaikan assignment ketika Attempt aktif masih memerlukan dukungan recovery.
- Attempt IN_PROGRESS yang sah dapat resume setelah Exam close; Attempt baru tetap ditolak setelah close.
- Status issue #10 tetap menunggu approval Agent 1/2/3/4 secara eksplisit.

## 3. Pertanyaan kontrak recovery

Mohon Agent 1 menetapkan bersama Agent 2 dan koordinator:

1. Apakah monitoring Proktor harus tetap tersedia selama Attempt IN_PROGRESS yang masih sah membutuhkan pemulihan?
2. Status Attempt mana yang membutuhkan monitoring langsung, dan status mana yang sudah terminal?
3. Apakah status seperti EXPIRED, SUBMITTED, FORCE_SUBMITTED, NEEDS_REVIEW atau status lain yang ada pada source diperlakukan berbeda? Jangan menambah status baru tanpa persetujuan.
4. Kondisi apa yang mengakhiri kebutuhan dukungan Proktor: submit final, expiry final, revoke lease, takeover selesai, keputusan operator, atau kombinasi yang ditentukan owner?
5. Kapan assignment Proktor dapat COMPLETED/RELEASED tanpa menghilangkan jalur dukungan?
6. Apa yang terjadi bila assignment Proktor berakhir tetapi Attempt masih IN_PROGRESS?
7. Apakah assignment lama tetap dapat melihat Attempt yang sudah tidak berada pada room aktif? Jika ya, batas audit/read-only-nya bagaimana?
8. Bagaimana escalation jika Proktor tidak tersedia: Admin Madrasah, Guru penanggung jawab, atau workflow lain? Admin tidak otomatis memperoleh monitoring/answer access.
9. Bagaimana reschedule/cancel/placement change memengaruhi assignment dan Attempt aktif?
10. Bagaimana mencegah assignment recovery menjadi akses Proktor tanpa batas?

**Jangan menentukan nilai `recovery_until` pada Agent 4.** Nama field, durasi, status assignment dan policy recovery harus ditetapkan Agent 1/2/koordinator.

## 4. Local recovery — kapan boleh

Usulan batas client (belum disetujui):
- Local recovery hanya memulihkan data jawaban dari IndexedDB untuk user, tenant instalasi, Exam, Attempt, device dan snapshot scope yang sama.
- Setelah reload/reconnect, browser tidak menganggap Attempt dapat dilanjutkan sebelum server memverifikasi status Attempt, eligibility, registered device, lease, expiry/revoke dan fencing generation.
- Jawaban lokal tanpa receipt tetap dipertahankan dan disinkronkan hanya jika backend menyatakan Attempt/device masih sah.
- Jika akun berbeda, Attempt berbeda, device mismatch, lease invalid, stale fencing, Attempt terminal atau scope tidak cocok: jangan kirim jawaban dan jangan melakukan takeover otomatis.
- Jika server menolak resume atau tidak dapat memverifikasi status: pertahankan data lokal, tampilkan instruksi menghubungi pengawas/Guru sesuai kebijakan yang disetujui.

Ini bukan perubahan lifecycle backend dan tidak memberikan capability baru pada browser.

## 5. Kapan siswa harus menghubungi pengawas

UX harus meminta siswa menghubungi pengawas/Guru ketika:
- perangkat berubah atau fingerprint/device binding tidak cocok;
- lease expired/revoked atau generation stale;
- Attempt tidak ditemukan, terminal, atau eligibility dicabut;
- ada konflik jawaban yang belum memiliki resolusi;
- IndexedDB rusak/tidak tersedia atau storage quota habis sehingga jawaban tidak dapat disimpan;
- browser tidak dapat memverifikasi status Attempt setelah reconnect;
- assignment Proktor berakhir dan belum ada jalur dukungan yang ditetapkan.

UI tidak menyuruh siswa membuat Attempt baru untuk mengatasi recovery yang gagal.

## 6. Acceptance matrix Agent 1/2

| ID | Skenario | Acceptance |
|---|---|---|
| RCV-01 | Exam closed, Attempt IN_PROGRESS valid | Resume Attempt sama; tidak membuat Attempt baru |
| RCV-02 | Exam closed, tidak ada Attempt aktif | Attempt baru ditolak |
| RCV-03 | Reload pada device sama, lease valid | Restore local data, server revalidasi sebelum resume |
| RCV-04 | Reload dengan lease expired | Tidak auto-resume/takeover; data lokal dipertahankan |
| RCV-05 | Takeover device baru | Generation lama fenced; local data lama tidak dikirim |
| RCV-06 | Assignment Proktor berakhir, Attempt aktif | Dukungan tidak hilang; workflow eskalasi eksplisit |
| RCV-07 | Attempt terminal | Tidak dapat lanjut; histori lokal tidak disamarkan sebagai active |
| RCV-08 | Placement berubah saat Attempt aktif | Attempt tidak dipindah otomatis; histori dan scope tetap benar |
| RCV-09 | Scheduler berjalan bersamaan dengan submit | Tidak mengakhiri assignment sebelum state Attempt aman |
| RCV-10 | Proktor room A meminta peserta room B | Ditolak; monitoring tetap room-scoped |

## 7. Lock order, audit dan regression

Agent 1/2 harus mengonfirmasi:
- Global lock order untuk Exam → Room(s) → Session → SessionRoom(s) → mapping/placement → Proctor assignment → User dan urutan Attempt-only Exam → Participant → Attempt → Answer/Score/Lease.
- Scheduler mengunci parent sesuai urutan dan idempotent.
- Assignment lifecycle tidak mencabut monitoring yang dibutuhkan recovery.
- Event audit untuk assignment create/confirm/revoke/complete, placement change, recovery escalation dan Force Submit.
- Actor, room scope, schedule revision, before/after, reason dan correlation ID.
- Test concurrency scheduler vs Attempt submit/resume/takeover.
- Test Proktor room scope dan tidak ada akses Admin hanya karena assigner.

Agent 4 tidak menetapkan nama event, field, status HTTP, route atau `recovery_until`.

## 8. Approval dan blocker

- Agent 1: **PENDING**.
- Agent 2 untuk Attempt/lease/fencing: **PENDING / NEEDS DESIGN**.
- Koordinator: **PENDING**.
- Agent 4 official branch ownership: **PENDING**.
- Tidak ada source Agent 1/2 yang diubah.
- Tidak ada test runtime yang dijalankan oleh dokumen ini.

Mohon Agent 1 menjawab setiap keputusan dengan `APPROVED`, `REJECTED`, `NEEDS DESIGN` atau `PENDING`, menyebut file/class owner, state transition, lock order, audit event, acceptance criteria dan regression evidence pada exact SHA.


## Addendum source refresh — Agent 1 Room/Proktor (22 November 2026, 18:20 WIB)

Current Agent 1 HEAD: `51571ca54ec922466e6230b4e8137a85336bc024`. Current Agent 1 source blobs:
- ExamSessionLifecycleService `8e34152afd817f30cc2f81b5f25fc450571463ac`.
- ExamRoomSessionService `e514fa9f751584e54af1125d48b5673e013f2339`.
- ExamProctorAssignmentService `c968ce68c0decace1c35a0ccb3b2b4449c057656`.
- ExamMonitoringScope `11b455954f974d1c60ed5c49700368f8d37c09c1`.
- Session migration `9551b1dd79397bd0432f03eefc970852b675fd25`.

The migration still uses unique `(tenant_id, exam_id, session_number)`, not a unique operational-session guard. `ExamRoomSessionService::createSession()` accepts session_number/starts_at/ends_at and separate cancel/reschedule methods remain present. `ExamSessionLifecycleService::synchronize()` completes session at ends_at and transitions assignments based on session state/time without querying whether an Attempt IN_PROGRESS still requires recovery. No `recovery_until` field/decision is established. `ExamMonitoringScope` includes an author/KAMAD wide-scope branch; KAMAD portal/capability is not approved and requires owner/coordination review.

The same migration path differs across Agent 1/2/3 blobs: Agent 1 `9551b1dd...`, Agent 2 `42506690...`, Agent 3 `83f6cacc...`. The relevant session table excerpt on each still has session_number and no is_operational/operational_guard. Do not reconcile or migrate these branches without owner agreement.

Issue #10 has no direct, independently verifiable Agent 1 owner decision on recovery/scheduler/one-session invariant. Status: **PENDING — ROOM/PROCTOR RECOVERY CONTRACT**.
