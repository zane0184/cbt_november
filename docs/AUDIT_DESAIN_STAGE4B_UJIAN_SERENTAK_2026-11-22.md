# AUDIT DESAIN STAGE 4B — KETENTUAN UJIAN SERENTAK
Tanggal: 22 November 2026

## Ringkasan keputusan

**Tidak ditemukan bahwa ExamSession mengatur atau membuka akses attempt siswa secara bergelombang.** Jalur start attempt tetap menggunakan jadwal global Exam.start_at/end_at dan waktu server. Room/session placement tidak menjadi syarat start attempt.

Namun **ExamSession bukan sekadar label/pengelompokan ruang**: ia memiliki starts_at/ends_at, status lifecycle, dapat dijadwalkan ulang secara terpisah di dalam jendela Exam, menjadi dasar overlap ruang, serta membatasi masa tugas Proktor. Karena itu beberapa ExamSession dalam satu Exam dapat mempunyai jendela operasional yang berbeda. Perbedaan itu tidak membatasi waktu mulai siswa, tetapi dapat menimbulkan kesan sesi bergelombang dan ketidaksinkronan cakupan Proktor.

Kesimpulan: **struktur tabel Stage 4B dapat dipertahankan; belum ada dasar untuk redesign schema. Akan tetapi desain operasional perlu guardrail/keputusan eksplisit sebelum dipakai luas.** Rekomendasi Agent 1: satu jendela pelaksanaan per Exam; idealnya satu ExamSession operasional untuk satu Exam dengan seluruh ruang sebagai ExamSessionRoom di bawahnya, atau jika beberapa record dipertahankan untuk alasan administratif, semua starts_at/ends_at wajib sama dengan Exam.start_at/end_at. Tidak boleh mengartikan perbedaan ExamSession sebagai gelombang peserta. Tidak ada perubahan source dalam audit ini.

## A. AUDIT CI DAN KEPEMILIKAN

- Repository: `zane0184/cbt_november`
- Branch: `work/stage4b-room-session-20261122`
- SHA yang diuji: `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5`
- HEAD setelah checkpoint sebelumnya: `4e663cd84038b7c3337d776f702c499f20d8f537`
- Perbandingan Git dari SHA CI ke HEAD: tepat 2 commit; file yang berubah hanya:
  - `docs/LAPORAN_STAGE4B_STAGE4C_VERIFIKASI_2026-11-22.md`
  - `docs/CHECKPOINT_STAGE4B_STAGE4C_VERIFIED_2026-11-22.md`
- CI: [Run 44984303003](https://github.com/zane0184/cbt_november/actions/runs/44984303003)
- Checkout SHA: `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5`
- Job `safe-runtime-tests`: success; 35 step, 35 sukses, 0 failure, 0 skipped.
- Empat suite target: 24 test, 120 assertion.
- Artifact: `wa01-alternative-runtime-44984303003-1`, ID `14739069544`, 20,145 bytes, SHA-256 `62856a1a0b94b77db9e096c711f24985cca4c7c0dfbaf354362ba9ab44034904`.
- Bukti CI pada database disposable MySQL 8.0.46/InnoDB. Tidak ada database produksi yang dipakai.
- Pada penutupan audit ini tidak ada perubahan source aplikasi, migration, schema, workflow, Agent 2, atau Agent 3. Tidak ada merge/rebase/PR/deployment.

Rujukan laporan CI sebelumnya: [Laporan Stage 4B–4C](https://github.com/zane0184/cbt_november/blob/work/stage4b-room-session-20261122/docs/LAPORAN_STAGE4B_STAGE4C_VERIFIKASI_2026-11-22.md).

## B. AUDIT KESESUAIAN DESAIN

Semua bukti kode di bawah menunjuk ke source SHA `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5`, bukan ke asumsi atau ke source yang lebih baru.

| Komponen | Fungsi aktual | Sesuai/tidak sesuai ujian serentak | Bukti source | Risiko/dampak |
|---|---|---|---|---|
| `ExamSession` | Satu record punya `session_number`, `starts_at`, `ends_at`, `schedule_revision`, dan status SCHEDULED/ACTIVE/COMPLETED. Lifecycle scheduler mengaktifkan/menyelesaikan record berdasarkan waktunya sendiri. | **Tidak menjadi gelombang akses siswa.** Tetapi mengandung jadwal operasional terpisah dan dapat dibuat berbeda antar-record. | Migration [L38-L55](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php#L38-L55); model [ExamSession.php](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/app/Models/ExamSession.php); createSession [L138-L190](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/app/Services/ExamRoomSessionService.php#L138-L190); lifecycle [L23-L89](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/app/Services/ExamSessionLifecycleService.php#L23-L89). | Admin dapat membuat waktu operasional berbeda; rawan disalahartikan sebagai jadwal peserta. |
| `ExamRoom` | Master ruang fisik tenant: kode, nama, lokasi, kapasitas, status. | **Sesuai.** Tidak mengatur jadwal peserta. | Migration [L22-L36](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php#L22-L36); model `ExamRoom.php`. | Kapasitas dan status ruang divalidasi pada service. |
| `ExamSessionRoom` | Menghubungkan Exam, ExamSession, dan ExamRoom; menyimpan kapasitas override/status. Overlap fisik ruang diperiksa terhadap rentang waktu ExamSession. Satu session dapat memiliki banyak ruang. | **Sesuai sebagai pemetaan ruang untuk satu pelaksanaan bersama**, sepanjang session tidak dipakai sebagai gelombang peserta. | Migration [L57-L74](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php#L57-L74); attachRoom [L193-L303](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/app/Services/ExamRoomSessionService.php#L193-L303). | Jika session windows berbeda, pemakaian ruang dapat terjadwal seperti gelombang meskipun akses siswa tetap global. |
| `ExamSessionRoomRombels` / model `ExamSessionRoomRombel` | Memetakan rombel ke pasangan sesi-ruang; menyimpan status, siapa yang menugaskan/melepas, waktu dan alasan pelepasan. | **Sesuai untuk administrasi penempatan**, bukan penentu waktu mulai siswa. | Migration [L84-L106](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php#L84-L106); mapRombel [L305-L464](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/app/Services/ExamRoomSessionService.php#L305-L464). | Mapping tidak mengubah `eligible_from/until`; penempatan ruang bukan time gate peserta. |
| `ExamParticipantRoomAssignments` | Memetakan participant ke pasangan sesi-ruang, status ACTIVE/RELEASED, dan histori penempatan. | **Sesuai sebagai assignment operasional/monitoring**, bukan penjadwalan start attempt. | Migration [L108-L132](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php#L108-L132); mapRombel [L380-L464](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/app/Services/ExamRoomSessionService.php#L380-L464). | Siswa yang belum terpetakan tetap dapat melewati gate start Exam jika participant globalnya sah; Proktor tidak dapat memonitor peserta yang tidak masuk room assignment miliknya. |
| `ExamProctorAssignment` | Penugasan sementara Guru ke Exam dan `exam_session_room_id`, dengan masa berlaku, status, konfirmasi, pencabutan, alasan dan schedule revision. | **Sesuai.** Bukan role permanen dan scope terikat ruang. | Service assign [L20-L85](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/app/Services/ExamProctorAssignmentService.php#L20-L85); Guru-only [L339-L353](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/app/Services/ExamProctorAssignmentService.php#L339-L353); model confirmedNow [L53-L75](https://github.com/zane0184/cbt_november/blob/f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5/backend-laravel/app/Models/ExamProctorAssignment.php#L53-L75). | Masa assignment Proktor mengikuti session operational window; jika session terlambat dari Exam, monitoring room dapat belum aktif saat siswa sudah boleh mulai. |

### Jawaban tujuh pertanyaan

1. **Apakah satu ujian tetap dapat berlangsung serentak untuk seluruh peserta sesuai jadwal ujian yang sama? — YA.** Gate start attempt adalah Exam global. `ExamPolicy::startAttempt` memeriksa participant eligibility lalu `$exam->canStartAttempt()`; `Exam::canStartAttempt()` memakai `isOngoing()` yang memeriksa `Exam.start_at/end_at`. `ExamAttemptController::store` mengulang otorisasi setelah lock Exam, menggunakan `now()`, dan menghitung expiry dari durasi ujian serta `Exam.end_at`. Tidak ada pembacaan `ExamSession` pada jalur start.
2. **Apakah ExamSession hanya pengelompokan operasional/administratif? — TIDAK SEPENUHNYA.** Ia adalah grouping ruang dan scope Proktor, tetapi juga memiliki window waktu dan lifecycle independen. Waktu tersebut mempengaruhi overlap ruang, status session, serta masa Proktor; bukan eligibility siswa.
3. **Apakah peserta dapat mulai pada jadwal berbeda karena penempatan session? — TIDAK.** Policy/controller start tidak membaca `ExamParticipantRoomAssignment`, `ExamSessionRoom`, atau `ExamSession.starts_at/ends_at`. Field participant `eligible_from/until` dapat membatasi peserta secara individual bila diisi oleh proses lain, tetapi mapping Stage 4B tidak mengisinya. Jadi batas individual itu bukan akibat session/room placement.
4. **Apakah Proktor ditugaskan berdasarkan ujian dan ruang, bukan role permanen? — YA.** Assignment menyimpan `exam_id` dan `exam_session_room_id`; penerima harus Guru dengan role tunggal GURU dan profil Guru aktif. Tidak ada role PROKTOR yang dibuat.
5. **Apakah monitoring dan tindakan Proktor dibatasi assignment? — YA untuk endpoint monitoring yang diperiksa.** `ExamPolicy::monitor` mengharuskan role GURU tunggal, permission, dan assignment CONFIRMED yang sedang berlaku. `ExamMonitoringScope` menyaring participant berdasarkan room assignments aktif di session-room yang assignment Proktornya confirmedNow. Monitoring summary, participants, attempt detail, events, serta `ExamAttemptController::show` memakai scope tersebut. Guru pembuat ujian dan Kamad memiliki scope sesuai policy terpisah, bukan scope Proktor.
6. **Apakah ruang dapat ditugaskan ulang tanpa mengubah jadwal resmi Exam? — YA, dengan batas lifecycle.** Mapping lama dapat dilepas ketika session masih SCHEDULED dan tidak ada attempt IN_PROGRESS; histori release dipertahankan. Rombel/peserta kemudian dapat dipetakan ke session-room lain. Perubahan scope menaikkan `schedule_revision` dan membuat assignment Proktor terdampak menjadi NEEDS_REVIEW. `rescheduleSession` secara eksplisit hanya mengubah `ExamSession.starts_at/ends_at`, bukan `Exam.start_at/end_at`. Penempatan ulang tidak mengubah jadwal resmi Exam.
7. **Apakah ada konflik token otomatis dan waktu server? — TIDAK PADA JALUR UTAMA.** `ExamController::lock` menerbitkan Exam dan membuat token otomatis jika token belum ada. Token melekat pada Exam, bukan pada ExamSession. Start attempt memeriksa token Exam dan jadwal Exam menggunakan waktu server `now()`. Lifecycle session/assignment juga memakai waktu server. Risiko tersisa bukan token, melainkan ketidaksinkronan window operasional Proktor terhadap window peserta jika Admin mengatur session lebih sempit/berbeda.

### Risiko desain yang harus diputuskan

- `createSession` mengizinkan starts_at/ends_at berbeda asalkan berada di dalam window Exam; tidak ada validasi equality dengan Exam.
- UI Admin meminta nama, nomor, starts_at dan ends_at untuk setiap session; UI menampilkan label “Sesi N” beserta jam.
- `rescheduleSession` dapat mengubah waktu session secara independen dari waktu Exam.
- Proktor hanya mendapat capability selama assignment dan session scope aktif. Jika Exam sudah dibuka global tetapi session/assignment room tertentu baru aktif kemudian, siswa di room itu dapat mulai sebelum capability Proktor berlaku.
- Mapping ruang tidak menjadi syarat start attempt. Peserta tanpa mapping tetap dapat lolos start policy jika terdaftar dan waktunya sah, tetapi tidak termasuk participantQuery Proktor.

### Opsi guardrail untuk persetujuan desain — belum diimplementasikan

**Opsi yang direkomendasikan:** pertahankan seluruh struktur room/assignment, tetapi definisikan satu pelaksanaan serentak per Exam. Sediakan satu ExamSession operasional untuk Exam tersebut dan pasangkan seluruh ruang sebagai ExamSessionRoom di bawahnya. Jika kebutuhan administratif mengharuskan beberapa record ExamSession, wajibkan seluruh starts_at/ends_at sama dengan Exam.start_at/end_at dan larang perubahan jadwal session secara independen. Penugasan Proktor tetap dapat dibatasi per ruang dan masa penugasan sesuai kebijakan operasional yang disepakati.

Alternatif: tetap izinkan beberapa operational windows untuk kebutuhan shift petugas, tetapi UI dan dokumentasi harus menyebutnya sebagai window penugasan/coverage Proktor, bukan jadwal pelaksanaan peserta; seluruh peserta tetap hanya mengikuti Exam global. Opsi ini membutuhkan prosedur memastikan semua ruang tercakup saat Exam global dimulai.

**Tidak ada perubahan source, migration, schema, atau aturan bisnis pada audit ini.** Agent 1 menunggu keputusan desain sebelum mengusulkan patch.

## C. IMPLEMENTASI

Tidak ada implementasi baru pada tahap penutupan ini. Tidak ada file aplikasi atau migration yang diubah. Hanya dua dokumen audit/checkpoint baru yang ditambahkan ke branch Agent 1.

## D. PENGUJIAN

Tidak ada test baru yang dijalankan pada audit desain ini. Tidak perlu mengulang suite yang telah lulus tanpa bukti regresi baru.

Referensi hasil pengujian yang diterima:
- [CI 44984303003](https://github.com/zane0184/cbt_november/actions/runs/44984303003): 35/35 step sukses, 0 failure, 0 skipped.
- Stage 4C lifecycle: 9 test / 41 assertions.
- Stage 4C concurrency: 1 test / 8 assertions.
- Stage 4B placement integrity: 12 test / 49 assertions.
- Stage 4B concurrency: 2 test / 22 assertions.
- Total empat suite: 24 test / 120 assertions.
- Rollback Stage 4B dan restore, foreign-key checks aktif, FK tenant/index dasar terjaga: PASS pada MySQL 8.0.46 disposable.

## E. BATAS PEKERJAAN DAN HANDOVER

- Stage 4B: **selesai pada lingkup implementasi dan CI; audit desain menemukan guardrail window session perlu keputusan.**
- Stage 4C: **selesai pada lingkup implementasi dan CI.**
- Isu desain terbuka: definisi satu ExamSession per Exam atau aturan equality semua session windows dengan Exam; serta prosedur coverage Proktor untuk semua ruang sejak Exam dimulai.
- Agent 1: menutup dan mendokumentasikan Stage 4B–4C; tidak mengambil Stage 4D.
- Agent 2: tetap pemilik Stage 4D/Device Lease-fencing sesuai pembagian tugas; koordinasikan temuan hanya sebagai dependensi desain, tanpa mengubah source mereka.
- Agent 3: tetap pemilik Web Guru; koordinasikan bahwa jadwal peserta berasal dari Exam global, bukan ExamSession.
- Tidak ada merge, rebase, PR, deployment, atau database produksi.
- Device Lease/fencing **tidak dinyatakan siap produksi**.
- Langkah berikutnya yang memerlukan persetujuan: pilih guardrail desain operasional session dari opsi di atas. Sampai keputusan diterima, jangan mengubah source.

