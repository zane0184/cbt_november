# KONTRAK INTEGRASI AGENT 1 — AGENT 2 — AGENT 3
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch dokumen: work/stage4b-room-session-20261122
Status: KONTRAK DESAIN READ-ONLY — TIDAK MENGUBAH BRANCH AGENT LAIN

## 1. Invariant bersama
1. Satu Exam memiliki tepat satu ExamSession operasional.
2. Satu ExamSession operasional menampung seluruh ExamSessionRoom untuk semua ruang fisik.
3. Tidak ada Session 2/3 atau jadwal gelombang peserta dalam satu Exam.
4. Jadwal peserta hanya Exam.start_at/end_at dengan waktu server.
5. ExamSession.starts_at/ends_at adalah mirror jadwal Exam, bukan sumber jadwal.
6. ExamSession/session-room/placement bukan admission gate peserta.
7. Token tetap melekat pada Exam dan diterbitkan saat Guru mengunci/menerbitkan Exam.
8. Assignment Proktor tetap temporer dan scoped ke Exam + exam_session_room_id.
9. Proktor hanya memonitor peserta/attempt pada room yang ditugaskan.
10. Tenant_id, exam_id, session_id, room_id dan assignment scope harus divalidasi bersama; tidak ada akses lintas tenant.
11. Semua histori room, mapping, placement, Proktor, Attempt, Answer, Score dan audit dipertahankan.

## 2. Kontrak data dan relasi
- Exam: pemilik jadwal resmi, status lifecycle, token, durasi, attempt policy.
- ExamSession: satu wadah operasional canonical untuk Exam; identitasnya tidak diganti setelah dibuat.
- ExamSessionRoom: satu hubungan session dengan ruang fisik; banyak row room dapat berada di bawah satu session.
- ExamSessionRoomRombel: mapping rombel ke room-session.
- ExamParticipantRoomAssignment: penempatan peserta ke room-session dengan status ACTIVE/RELEASED dan histori.
- ExamProctorAssignment: assignment Guru temporer yang mengikat tenant_id + exam_id + exam_session_room_id + schedule_revision + window tugas.
- ExamAttempt/ExamAttemptAnswer/ExamAttemptScore/ExamAttemptScoreCurrent/ExamQuestionScore: tetap terikat pada Exam/Participant/Attempt; tidak dipindah saat room/session direkonsiliasi.

Penanda is_operational:
- 1 = canonical session untuk Exam.
- 0 = session lama/historis non-operasional.
- Nilai 1 tidak berubah menjadi 0 untuk membuat session pengganti.
- Semua query operasional (scheduler, assignment baru, monitoring, management) harus memfilter is_operational=1.
- Histori non-operasional tetap dapat dibaca untuk audit, tetapi tidak boleh memberi capability.

## 3. Tanggung jawab Agent 1 — Stage 4B
- Menjaga kontrak satu Exam/satu session dan banyak room.
- Mengelola room master, session operasional tunggal, mapping rombel/placement, assignment Proktor dan lifecycle room-session.
- Menegakkan invariant pada service + database, bukan UI saja.
- Menyediakan scope monitoring per room untuk Proktor.
- Tidak mengambil alih implementasi Device Lease/fencing Agent 2 atau Web Guru Agent 3.
- Tidak mengubah branch Agent 2/3 tanpa koordinasi dan persetujuan.

## 4. Kontrak untuk Agent 2 — Stage 4D / Device Lease / Fencing
Agent 2 wajib:
- Memakai Exam.start_at/end_at dan waktu server sebagai admission/timing authority.
- Tidak memakai ExamSession.starts_at/ends_at, session_number, atau room assignment sebagai waktu mulai Attempt.
- Tidak membuat session/gelombang tambahan untuk device lease, recovery, takeover, atau fencing.
- Lease/fencing tetap mengikat identitas peserta/Attempt/Exam/device sesuai desain Stage 4D; bila menyimpan session/room reference untuk audit atau konteks operasional, referensi tersebut tidak boleh menjadi gate jadwal.
- Attempt aktif yang mengalami recovery/takeover tidak boleh dipindah room/session secara otomatis. Perubahan placement harus mengikuti workflow Stage 4B dan tetap mempertahankan histori.
- Lock order lintas service harus menghindari siklus: Exam -> Participant -> Attempt -> Device Lease/Fencing, dan ketika operasi perlu room/session, ikuti Exam -> Room -> Session -> SessionRoom sebelum operasi Attempt/Lease yang relevan; detail final harus disepakati kedua agent sebelum implementasi.
- Tidak mengubah FK session-room, menghapus history, atau memperluas Proktor monitoring.
- Menambahkan integration test bahwa peserta dapat start sesuai Exam window walaupun session/room assignment berbeda masa tugas Proktor; session tidak menjadi admission gate.
- Melaporkan kebutuhan perubahan schema bersama sebelum mengubahnya.

## 5. Kontrak untuk Agent 3 — Web Guru
Agent 3 wajib:
- Guru membuat/mengatur jadwal Exam pada Exam.start_at/end_at selama DRAFT dan sesuai policy.
- Setelah Exam dikunci/published, jadwal menjadi immutable pada jalur update biasa.
- Tidak menyediakan input Session 1/2/3, gelombang, jadwal session, atau reschedule session kepada Guru.
- Token tetap dikelola pada Exam dan ditampilkan sesuai hak akses; token dapat dilihat sebelum jadwal, tetapi peserta hanya dapat mulai pada Exam window.
- UI Guru dapat menampilkan informasi ruang secara read-only bila dibutuhkan, tetapi Admin tetap mengelola master ruang dan penempatan operasional sesuai permission.
- Race update-vs-publish wajib ditutup pada backend, bukan hanya disabled state di UI.
- Tidak mengubah model ExamSession, room, placement, atau assignment tanpa koordinasi Agent 1.
- Menambahkan test bahwa update jadwal setelah lock ditolak, dan bahwa race publish-vs-update tidak dapat menulis jadwal setelah locked.

## 6. Kontrak Proktor dan monitoring
- Proktor bukan role/portal permanen; Proktor adalah Guru yang mendapat assignment temporer dan confirmed.
- Satu Proktor dapat diberi tugas pada room yang ditugaskan; banyak Proktor dapat bertugas pada room berbeda dalam satu Exam.
- Assignment window boleh lebih pendek daripada Exam window tetapi selalu berada di dalamnya.
- Perubahan scope room/placement menaikkan schedule_revision dan membuat assignment terdampak NEEDS_REVIEW; konfirmasi ulang diperlukan.
- Monitoring hanya memuat peserta/attempt/event dari room yang ditugaskan. Tidak boleh memperluas akses ke room lain.
- Admin Madrasah mengelola administrasi/penugasan tetapi tidak memperoleh hak monitoring CBT hanya karena menugaskan Guru.
- Guru pemilik Exam dan Kamad mengikuti policy terpisah; assignment Proktor tidak mengubah role permanen.

## 7. Kontrak lifecycle dan close
- ExamController::update harus lock Exam dan re-authorize di dalam transaksi sebelum update jadwal.
- ExamController::lock menetapkan publish/locked dan token pada Exam.
- Close Exam oleh Guru harus menyelaraskan session/assignment secara atomik tanpa merusak Attempt aktif.
- Admin tidak dapat cancel session sebagai jalan alternatif untuk mengubah jadwal/akses peserta.
- Attempt IN_PROGRESS yang sah tetap dapat resume sesuai policy; Proktor monitoring tidak dicabut prematur selama masih dibutuhkan pemulihan.
- Session status scheduler tidak mengubah Exam schedule dan tidak menjadi start gate.

## 8. Integrasi, koordinasi, dan gate
Sebelum perubahan source lintas agent:
1. Agent yang membutuhkan perubahan mengajukan daftar file/schema/API dan alasan.
2. Pemilik branch menilai dampak dan menyepakati kontrak.
3. Buat backup sebelum file yang dimiliki agent diubah.
4. Tidak mengedit branch Agent lain secara sepihak.
5. Jalankan test integrasi lintas branch pada disposable MySQL 8+; SQLite hanya sebagai test runtime tambahan yang versi dan perilakunya dibuktikan.
6. Catat SHA, file, backup, hasil test dan checkpoint.
7. Tidak ada merge/rebase/PR/deployment/DB produksi tanpa persetujuan.

## 9. Status pemeriksaan
- Agent 2 branch yang ditemukan sebelumnya: work/stage4d-account-lifecycle-20261122; source/model terkait dibaca read-only pada audit sebelumnya. Branch tidak diubah.
- Agent 3 branch yang ditemukan sebelumnya: work/web-guru-foundation-20261122; source/model terkait dibaca read-only pada audit sebelumnya. Branch tidak diubah.
- Branch HEAD masing-masing Agent 2/3 tidak menjadi bagian dari pekerjaan ini dan tidak diubah.
- Kontrak ini adalah dokumen koordinasi; penerimaan formal Agent 2/3 belum diperoleh: NOT VERIFIED.
