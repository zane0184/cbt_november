# DESAIN STAGE 4B — RUANG, SESI, PESERTA, DAN PROKTOR
Tanggal: 22 November 2026
Status: DESAIN LANJUTAN BERDASARKAN KEPUTUSAN PEMILIK PROYEK
Basis source: 88f1510a7d1992f6cd8d4b727daf93fe28a3afd3

## 1. Keputusan operasional yang wajib dipertahankan
- Admin Madrasah menentukan Guru yang ditugaskan sebagai Proktor, ruang, dan sesi berdasarkan kebutuhan/edaran ujian.
- Guru tetap membuat, mengelola konten, dan menerbitkan ujian sesuai kewenangannya.
- Peserta ditentukan berdasarkan rombel.
- Guru Proktor hanya memperoleh capability monitoring untuk ujian, ruang, dan sesi yang tercantum dalam assignment terkonfirmasi.
- Admin hanya mengakses metadata minimum yang diperlukan untuk penjadwalan dan penugasan. Admin tidak memperoleh soal, peserta operasional, jawaban, attempt, score, hasil, atau monitoring CBT.
- Tidak ada role/portal Proktor permanen.
- Kamad, Super Admin, Android, dan deployment tidak termasuk Stage 4B.

## 2. Temuan source
- Belum ditemukan model/tabel ExamRoom atau ExamSession.
- ExamParticipant hanya menyimpan exam_id, user_id, status, eligibility window, attempt limit; belum menyimpan rombel asal.
- ExamProctorAssignment pada source teruji baru memiliki tenant/exam/proctor/time/status; belum memiliki room/session scope.
- RombelStudent adalah pivot tenant-scoped; belum memiliki effective dates/status historis.
- ExamController::assignRombel menambahkan peserta secara idempotent tetapi belum menyimpan rombel_id snapshot pada ExamParticipant.
- MonitoringController summary/participants menghitung seluruh peserta/attempt ujian setelah authorize monitor; belum membatasi peserta menurut ruang/sesi assignment Proktor.

## 3. Model data yang dirancang
Gunakan relasi many-to-many yang fleksibel agar satu sesi dapat memakai beberapa ruang dan satu ruang dapat dipakai pada sesi lain, tanpa mengunci desain menjadi satu ruang per sesi.

### 3.1 exam_rooms
Master ruang fisik per tenant:
- id, tenant_id, code, name, location, capacity, status, timestamps.
- Unique tenant_id + code.
- Status ACTIVE/INACTIVE.
- Tidak mengandung data peserta atau CBT.

### 3.2 exam_sessions
Sesi terjadwal per ujian:
- id, tenant_id, exam_id, name, session_number, starts_at, ends_at, schedule_revision, status, timestamps.
- Composite FK tenant/exam.
- schedule_revision dimulai dari 1 dan bertambah setiap perubahan waktu/ruang yang memengaruhi tugas.
- Waktu sesi wajib berada dalam rentang keseluruhan ujian.

### 3.3 exam_session_rooms
Pivot ruang yang digunakan oleh sesi:
- id, tenant_id, exam_id, exam_session_id, exam_room_id, capacity_override, status, timestamps.
- Composite FK memastikan exam, session, room, dan tenant konsisten.
- Unique tenant_id + exam_session_id + exam_room_id.
- Pasangan sesi-ruang menjadi scope operasional yang dipakai assignment Proktor dan mapping peserta.

### 3.4 exam_participants.rombel_id
Simpan rombel asal sebagai snapshot pada saat peserta ditambahkan ke ujian:
- nullable untuk data lama yang belum dapat dibuktikan asal rombelnya.
- peserta baru melalui assignRombel wajib menyimpan rombel_id.
- penambahan peserta individual hanya boleh dilakukan bila ada tepat satu rombel yang valid pada tahun ajaran asesmen; selain itu ditolak dan Admin/Guru harus menyelesaikan data rombel terlebih dahulu.
- Composite FK tenant/rombel mencegah peserta ditautkan ke rombel tenant lain.
- rombel_id tidak berubah otomatis ketika keanggotaan akademik Siswa berubah setelah ujian dibuat.

### 3.5 exam_participant_room_assignments
Pemetaan peserta ujian ke pasangan sesi-ruang:
- id, tenant_id, exam_id, participant_id, exam_session_room_id, status, assigned_by, assigned_at, released_at, timestamps.
- Satu participant hanya boleh memiliki satu assignment saat ini dalam satu exam.
- Perubahan penempatan menutup record lama dan membuat record baru; riwayat tidak dihapus.
- Pembuatan/pemindahan assignment mengunci Exam lalu ExamParticipant sebelum memeriksa current assignment.
- Peserta dalam pasangan sesi-ruang harus berasal dari rombel yang termasuk dalam penempatan sesi-ruang tersebut.
- Kapasitas room/session-room tidak boleh terlampaui.

### 3.6 exam_session_room_rombels
Pemetaan rombel yang dijadwalkan ke pasangan sesi-ruang:
- id, tenant_id, exam_id, exam_session_room_id, rombel_id, status, assigned_by, timestamps.
- Unique tenant_id + exam_id + rombel_id untuk mencegah satu rombel didistribusikan ganda secara tidak sengaja.
- Service dapat menghasilkan participant-room assignment untuk seluruh peserta ujian yang memiliki rombel_id tersebut.
- Admin Madrasah hanya memilih rombel dan pasangan sesi-ruang; Admin tidak mendapat daftar/nama peserta, data operasional peserta, atau UI penempatan peserta individual.
- Sistem membentuk participant-room assignment secara otomatis dari snapshot rombel. Pemecahan satu rombel lintas ruang tidak menjadi capability Admin pada Stage 4B.
- Jika kelak pemecahan rombel benar-benar diperlukan, implementasikan sebagai workflow terpisah yang dimiliki Guru pembuat ujian, dengan otorisasi, kapasitas, idempotensi, histori release/reassignment, dan audit tersendiri. Jangan membuka data peserta operasional kepada Admin.

### 3.7 exam_proctor_assignments
Tambahkan scope:
- exam_session_room_id wajib untuk assignment baru.
- schedule_revision menyimpan revision sesi saat assignment dibuat/dikonfirmasi.
- Unique assignment per tenant/session-room/Guru.
- Status kanonis: PENDING, CONFIRMED, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED; ACTIVE tidak valid.
- PENDING tidak memberi capability.
- CONFIRMED hanya memberi capability selama starts_at <= serverNow < expires_at, belum revoked, Guru/TeacherProfile aktif, tenant dan schedule_revision cocok.
- Legacy assignment tanpa room/session scope dipindahkan ke NEEDS_REVIEW; tidak mendapat capability otomatis.

## 4. Otorisasi monitoring
Buat satu service scope (misalnya ExamMonitoringScope) yang digunakan oleh semua controller:
- Guru pembuat ujian: full scope pada exam yang dibuatnya setelah TeacherAccess/permission/tenant valid.
- Guru Proktor: hanya participant/attempt yang terhubung ke exam_session_room_id dalam assignment CONFIRMED yang valid.
- Guru tanpa assignment dan bukan pembuat ujian: ditolak.
- Admin Madrasah: ditolak secara eksplisit pada semua permukaan CBT.
- Scope diterapkan pada summary, participants, attempt detail, events, dan seluruh query terkait. Jangan hanya menyembunyikan kolom pada response.
- Proktor tidak otomatis memperoleh akses scoring, grading, token, question authoring, atau report hasil.
- Admin assignment UI hanya memakai metadata minimum (exam title/type/date, session, room, Guru, status assignment).

## 5. Schedule revision dan perubahan jadwal
- Jadwal sesi/ruang ditetapkan dan diubah Admin melalui ExamScheduleChangeService.
- Service mengunci Exam → ExamSession → Guru/assignment terkait dengan urutan konsisten.
- Perubahan starts_at, ends_at, room/session mapping, atau peserta yang mengubah lingkup tugas menaikkan schedule_revision.
- Assignment CONFIRMED terdampak berubah menjadi NEEDS_REVIEW dalam transaksi yang sama; capability lama langsung dicabut.
- Admin meninjau konflik dan mengonfirmasi ulang assignment menjadi CONFIRMED dengan revision terbaru.
- `Exam.start_at/end_at` tetap menjadi jadwal keseluruhan yang dimiliki Guru. Operasi Admin pada sesi/ruang tidak boleh mengubah, menghitung ulang, atau mengambil alih rentang ujian tersebut.
- Waktu sesi yang dibuat/diubah Admin wajib tetap berada di dalam rentang `Exam.start_at/end_at`. Jika kebutuhan operasional berada di luar rentang, Guru pemilik ujian harus mengubah jadwal keseluruhan melalui kewenangannya sendiri.
- Reschedule/cancel sesi hanya mengubah metadata sesi, revision, mapping dan status assignment terkait; seluruh perubahan tetap atomik dan diaudit.

## 6. Conflict dan concurrency
- Ruang tidak boleh digunakan pada dua sesi yang bertumpang tindih.
- Guru Proktor tidak boleh memiliki assignment CONFIRMED yang overlap pada waktu yang sama, lintas ujian/ruang.
- Peserta tidak boleh memiliki dua room-session assignment aktif pada exam yang sama.
- Interval menggunakan [starts_at, ends_at); sesi bersebelahan diperbolehkan.
- Lock order yang direncanakan: Exam → Room/Session → Guru atau Participant mutex → Assignment/Mapping row.
- Validasi konflik memakai locking/current read InnoDB; seluruh perubahan mapping, assignment, revision, dan audit satu transaksi.

## 7. Backfill dan fail-closed
- Tidak menghapus peserta atau membership lama.
- Backfill ExamParticipant.rombel_id hanya jika StudentProfile memiliki tepat satu Rombel yang valid untuk academic_year_id asesmen.
- Jika tidak ada/lebih dari satu rombel yang dapat dibuktikan, biarkan rombel_id NULL dan tandai untuk review; jangan memilih rombel secara otomatis.
- Assignment Proktor legacy tanpa room/session menjadi NEEDS_REVIEW.
- Tidak ada assignment Proktor atau participant room-session yang diberi capability hanya karena backfill.
- Migration hanya dijalankan pada MySQL 8 disposable selama pengembangan. Produksi memerlukan rencana terpisah, backup, preflight, dan persetujuan pemilik proyek.

## 8. Acceptance test Stage 4B
1. Admin dapat mengelola metadata ruang/sesi dan menugaskan Guru, tetapi tidak dapat membuka monitoring/participants/attempt/answers/scores/reports.
2. Guru author dapat monitoring seluruh ujian miliknya sesuai TeacherAccess.
3. Guru Proktor CONFIRMED hanya melihat peserta dan attempt pada room-session assignment miliknya.
4. Guru Proktor tidak dapat melihat room/session lain pada ujian yang sama atau ujian lain.
5. PENDING, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED, legacy ACTIVE, profile nonaktif, role ganda, tenant mismatch, dan schedule revision mismatch semuanya fail-closed.
6. Guru tanpa assignment dan bukan author tidak dapat monitoring.
7. Peserta hanya masuk ke satu room-session aktif pada exam; rombel snapshot tetap benar walau membership akademik berubah setelahnya.
8. Kapasitas ruang, overlap ruang, overlap Guru, dan konflik peserta ditolak.
9. Reschedule mengubah revision dan mencabut capability lama secara atomik, tanpa mengubah `Exam.start_at/end_at` milik Guru.
10. Semua endpoint URL/API langsung memakai scope yang sama; tidak ada bypass melalui report/export/attempt/events.
11. Test multi-koneksi MySQL 8 membuktikan tidak ada double assignment/race condition.
12. CI MySQL 8 disposable lulus seluruh suite terkait.

## 9. Batas pekerjaan
Stage 4B tidak membuat portal Proktor permanen, tidak mengerjakan Kamad/Super Admin/Android, tidak menjalankan migration produksi, dan tidak melakukan deployment/merge/PR.
