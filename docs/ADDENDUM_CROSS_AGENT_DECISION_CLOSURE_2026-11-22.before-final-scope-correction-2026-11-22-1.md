# ADDENDUM — CROSS-AGENT DECISION CLOSURE
Tanggal/waktu pemeriksaan: 22 November 2026, sekitar 18:00 WIB
Repository: zane0184/cbt_november
Branch koordinasi: work/stage4b-room-session-20261122
HEAD Agent 1 saat mulai refresh: 627ee00aacb96b28e1a5d1733fd42534af7f1c83
Status: BLOCKED — CROSS-AGENT DECISION PENDING

## 1. AUDIT — remote refs dan graph
| Owner/jenis | Branch | HEAD | Ahead / behind terhadap Agent 1 |
|---|---|---|---|
| Agent 1 | work/stage4b-room-session-20261122 | 627ee00aacb96b28e1a5d1733fd42534af7f1c83 | 0 / 0 |
| Agent 2 | work/stage4d-account-lifecycle-20261122 | 7d63ea3b70b5191530c2c92c5c86bf92d7324db6 | 227 / 94 |
| Agent 3 | work/web-guru-foundation-20261122 | fa658202886c05fa668fe1bdb0d080c5eae7e916 | 193 / 239 |
| Agent 4 dokumentasi | work/agent4-web-siswa-audit-20261122 | 16c7d324ef9d7103bb21f0c5528c3c888ba19568 | 27 / 1212 |
| Kandidat Agent 4 | work/import-guru-siswa-implementation-20261122 | f38ceec7b0653fcf3363eec40f3d4577c323cca8 | 80 / 362 |
| main | main | feab77228f7108f7f7a45325d7f1fb878c07310b | 15 / 1212 |

Semua branch diverged. Branch Agent 4 dokumentasi berbasis main dan berisi dokumen Web Siswa; bukan source implementasi. Kandidat Agent 4 belum dikonfirmasi owner. Tidak ada merge/rebase/cherry-pick.

Commit terbaru:
- Agent 1: 627ee00 — manifest dokumentasi.
- Agent 2: 7d63ea3 — checkpoint dokumentasi; source runtime baseline yang dinyatakan tetap 74bf7178f46673e9be90523671490c0749034acd.
- Agent 3: fa65820 — checkpoint Web-only dokumentasi; test source 88b5c1354971bfb7b03d715014b40b9026f1fbed.
- Agent 4 docs: 16c7d32 — checkpoint Web Siswa.
- Kandidat Agent 4: f38ceec — belum dikonfirmasi resmi.

Dokumen pemilik yang dibaca: enam dokumen Agent 1; checkpoint, laporan utama dan addendum Agent 2; checkpoint exact-role dan Web-only Agent 3; checkpoint Web Siswa, kontrak, threat model admission/media, permintaan kontrak room/recovery, backend, IndexedDB/offline, submit barrier, dan test matrix Agent 4.

## 2. AUDIT — Issue #10 dan legitimasi approval
Issue #10 tetap OPEN, sembilan komentar saat refresh. Semua komentar terlihat berasal dari akun GitHub zane0184. Komentar terbaru #7078152288 adalah permintaan koordinasi dari koordinator.

Komentar #7077640990 berisi catatan review UI Guru: UI hanya mengonsumsi data backend-authorized, monitoring room-scoped, tidak membuat jadwal/session terpisah, KAMAD bukan portal, Admin tidak otomatis monitoring. Teks tersebut juga secara eksplisit tidak menyetujui schema, migration, lifecycle Attempt, scheduler, lock order, atau assignment service. Karena author metadata adalah zane0184 dan bukan identitas owner Agent 3 yang dapat diverifikasi, ini dicatat sebagai catatan review yang disampaikan melalui koordinator, bukan approval owner.

Status:
- Agent 2: NEEDS DESIGN / COORDINATION; approval final belum ada.
- Agent 3: NEEDS DESIGN / PENDING untuk UI Guru; tidak ada approval lintas-agent terverifikasi.
- Agent 4: PENDING — owner/branch resmi belum dikonfirmasi.
- Agent 1: proposal teknis belum menjadi keputusan koordinator.

## 3. AUDIT — source aktual dan gap
Agent 1:
- Migration Stage 4B blob 9551b1dd79397bd0432f03eefc970852b675fd25.
- ExamSessionLifecycleService blob 8e34152afd817f30cc2f81b5f25fc450571463ac.
- ExamRoomSessionService blob e514fa9f751584e54af1125d48b5673e013f2339.
- ExamProctorAssignmentService blob c968ce68c0decace1c35a0ccb3b2b4449c057656.
- ExamAttemptController blob 13e8981cca4653ff4e9b9448a50e4d0ec8a74bae.
- ExamMonitoringScope blob 11b455954f974d1c60ed5c49700368f8d37c09c1.
Migration unique tenant_id/exam_id/session_number belum menegakkan satu operational session. Scheduler session-first tidak memeriksa Attempt aktif sebelum menyelesaikan assignment. Cancel/reschedule independen dan lock order berlawanan masih ada.

Agent 2:
- Source runtime baseline 74bf7178f46673e9be90523671490c0749034acd.
- ExamAnswerController blob b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c.
- ExamController blob c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9.
- Migration Stage 4B branch Agent 2 blob 4250669036b80c54ceb2bea8f4ae9123f97b45b5; tidak memiliki is_operational/operational guard.
Start/submit mengunci Exam → Participant → Attempt. Resume Attempt IN_PROGRESS yang belum expired dan peserta eligible setelah close didukung policy. Answer update masih Attempt → Answer. Archive/publishResults/destroy belum konsisten dengan transaksi + Exam lock + re-authorize. Exact-role, tenant topology, score current-pointer/report snapshot, KAMAD login/monitoring, lease/fencing runtime masih memiliki gap.

Agent 3:
- HEAD fa658202 adalah checkpoint dokumentasi; test source 88b5c135.
- ExamAttemptController blob e915bf43118026ef8eace96338fa6cad44ee29e1; show() tidak memanggil room scope.
- ExamMonitoringScope blob 11b455954f974d1c60ed5c49700368f8d37c09c1; bypass Exam author/KAMAD masih ada.
- ExamPolicy blob 839df41c82a5b45706072d60431706ca5861cc6c; masih mengenali KAMAD dan hasRole non-exact.
- Run 45009039557 pada SHA 88b5: job failure, steps kosong, log/artifact tidak tersedia; PHPUnit UNKNOWN. Run 45003191589 pada SHA 834a59f benar-benar menjalankan PHPUnit dan gagal 18 pass/2 fail/86 assertions.
Review UI Guru membatasi ownership Agent 3 pada konsumsi backend-authorized data dan room-scoped display; tidak menyetujui backend ownership.

Agent 4:
- Branch dokumentasi work/agent4-web-siswa-audit-20261122 HEAD 16c7d32 berisi dokumen Web Siswa, bukan source runtime.
- Kandidat work/import-guru-siswa-implementation-20261122 belum dikonfirmasi resmi.
- Candidate branch tidak memiliki routes/student.php, Student Livewire ExamAttempt, atau ExamMonitoringScope pada path yang diperiksa.
- Candidate ExamAttemptController blob 808702092c94b386657fe027f60758a625d5bb93 menggunakan X-Device-Binding-ID/fingerprint; ini bukan bukti Device Lease/fencing end-to-end.
- Dokumen Agent 4 menyatakan F-01 admission/snapshot dan F-02 media masih P0 OPEN; IndexedDB, receipt/conflict, submit barrier, offline recovery, multi-tab/multi-device belum diimplementasikan/diuji. Web Siswa NOT READY FOR OPERATIONAL EXAM.
- Run 44944158355 pada source a3522e1 adalah 107 commit di belakang candidate HEAD; run 44949239676 juga bukan bukti integrasi Stage 4B lintas branch.

## 4. AUDIT — CI exact SHA
| Owner | Run | SHA | Hasil |
|---|---:|---|---|
| Agent 1 | 44984303003 | f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5 | SUCCESS; PHPUnit/Stage 4B tests berjalan; bukan HEAD 627ee00 dan tidak menguji operational guard |
| Agent 2 | 45003796892 | 74bf7178f46673e9be90523671490c0749034acd | BLOCKED — RUNNER; PHPUnit tidak berjalan |
| Agent 2 | 45008591113 | 32d383eb1564cdbcf084b8677fe8b750a4b45a11 | BLOCKED — RUNNER; PHPUnit tidak berjalan |
| Agent 3 | 45003191589 | 834a59f11f9b548c8d7f0fcea68c15cf4bbe501f | FAILURE; 18 passed, 2 failed, 86 assertions |
| Agent 3 | 45009039557 | 88b5c1354971bfb7b03d715014b40b9026f1fbed | Job failure, steps kosong; PHPUnit UNKNOWN |
| Kandidat Agent 4 | 44949239676 | SHA kandidat lama | SUCCESS cakupan terpilih; bukan integration proof |
| Kandidat Agent 4 | 44944158355 | a3522e1c8be02224ef9b369051105f05b2704e39 | SUCCESS 100 tests/519 assertions menurut checkpoint; 107 commit di belakang kandidat HEAD |

Tidak ada CI pada exact integration SHA yang menggabungkan source Agent 1–4.

## 5. DESAIN — ownership usulan, belum disetujui
| Komponen | Owner utama yang diusulkan | Batas |
|---|---|---|
| Canonical ExamSession, guard, Room, SessionRoom, mapping Rombel, placement | Agent 1 | Agent 2 menyetujui DB/FK/tenant; Agent 3 UI konsumsi; Agent 4 tidak mengubah placement |
| Proctor assignment per room, scheduler, cancel/reschedule compatibility | Agent 1 | Attempt/recovery barrier bersama Agent 2/4; Agent 3 UI |
| Exam lifecycle, jadwal, token, snapshot, serialization root | Agent 2 | Room/session state dari Agent 1; Agent 3/4 consumer |
| Attempt, Answer, Score, Force Submit, expiry, current score, lease/fencing backend | Agent 2 | Agent 4 menyampaikan kebutuhan client; tidak menentukan API backend |
| Exact-role, tenant/auth/session version, reporting | Agent 2 | Agent 3/4 mengikuti kontrak |
| Web Guru, UI room/session/placement/assignment, monitoring UI | Agent 3 | Setelah API backend disetujui; tidak membuat bypass Admin/KAMAD |
| Web Siswa, admission rendering, IndexedDB, answer queue, offline recovery, submit barrier | Agent 4 setelah owner/branch resmi dikonfirmasi | Backend dari Agent 2; room/recovery Agent 1 |
| QuestionVersion/media/accepted answers/RTL | Agent 2 backend; Agent 3 authoring UI; Agent 4 rendering consumer | Kontrak data dan enam tipe soal perlu disepakati |

Ini rekomendasi koordinasi, bukan penetapan owner sepihak.

## 6. DESAIN — keputusan teknis dan status
Canonical session yang diajukan: satu Exam = satu ExamSession operasional; banyak room; placement/Proktor per room; tidak ada wave/session terpisah; jadwal dan token melekat ke Exam; session bukan admission gate. Proposal MySQL generated guard + unique tenant/exam/guard untuk at-most-one dan service/preflight untuk at-least-one belum disetujui atau diuji. SQLite partial unique hanya test tambahan. Legacy multi-session dengan Attempt/Answer/Score/placement/assignment/history wajib manual decision.

Lock order parent-first pada matriks terdahulu masih proposal: Exam → Room(s) terurut → Session → SessionRoom(s) → Rombel(s) → Participant(s) → Placement(s) → Proctor Assignment(s) → User(s) → Attempt → Answer → Score → Lease/Fencing. Agent 2 belum approve; Agent 3 bukan owner lock order; Agent 4 meminta kontrak backend.

Attempt aktif yang valid tetap dapat resume setelah close; Attempt baru ditolak. Scheduler tidak boleh menyelesaikan session/assignment sebelum kebutuhan recovery aktif dinilai. recovery_until wajib bounded dan diaudit; durasi/status belum ditetapkan. Cancel/reschedule session independen perlu guard; perubahan jadwal melalui Exam dan menaikkan schedule_revision. Room monitoring harus membatasi Proktor pada room assignment yang sah di semua endpoint/UI; KAMAD/Admin tidak otomatis memperoleh monitoring.

### Konflik arsitektur yang perlu keputusan koordinator
Dokumen Agent 4 menyatakan satu instalasi = satu madrasah = satu database akademik sendiri, sedangkan spesifikasi proyek koordinasi menetapkan modular monolith multi-tenant. Jangan mengasumsikan keduanya selaras. Koordinator perlu memutuskan apakah multi-tenant berarti isolasi logis dalam satu instalasi atau satu tenant per instalasi/database sebelum tenant resolver/schema diubah.

Dokumen Agent 4 juga menunda Android native dan Super Admin sampai Juni 2027. Ini berbeda dari keputusan arsitektur proyek yang menetapkan Android native sebagai client wajib peserta Android. Perlu keputusan koordinator; belum dianggap perubahan scope yang disetujui.

## 7. IMPLEMENTASI
Tidak ada source aplikasi, migration, schema, test, workflow, database, konfigurasi, merge/rebase/cherry-pick, atau deployment yang diubah. Issue #10 mendapat komentar koordinasi #7078152288. Addendum ini dokumen baru; tidak menimpa dokumen lama.

## 8. PENGUJIAN
Tidak ada PHPUnit, browser E2E, IndexedDB, restore MySQL, migration, concurrency, multi-device, lease/fencing, atau UAT yang dijalankan dalam refresh ini. CI lama tercatat di atas; tidak ada integration SHA lintas-agent. ST4B-01 s.d. ST4B-26 tetap test plan. Production DB tidak disentuh.

## 9. KEPUTUSAN KOORDINATOR
1. Pilih model tenant deployment: shared logical multi-tenant atau satu instalasi/database per madrasah.
2. Konfirmasi scope Android native/Super Admin.
3. Setujui canonical session dan DB guard.
4. Setujui serialization root dan lock order setelah Agent 2 review.
5. Tetapkan lifecycle Attempt/close/resume, scheduler, Proktor recovery dan durasi bounded.
6. Tetapkan cancel/reschedule/schedule_revision.
7. Tetapkan room-scoped monitoring dan batas Guru owner vs Proktor.
8. Konfirmasi owner/branch resmi Agent 4.
9. Setujui preflight disposable setelah raw dump diverifikasi.
10. Berikan izin implementasi eksplisit secara terpisah.

## 10. CHECKPOINT
BLOCKED — CROSS-AGENT DECISION PENDING.
Agent 2: NEEDS DESIGN / COORDINATION.
Agent 3: NEEDS DESIGN / PENDING untuk UI Guru; tidak ada approval owner terverifikasi.
Agent 4: PENDING — owner/branch resmi belum dikonfirmasi.
Web Siswa: NOT READY FOR OPERATIONAL EXAM.
Implementation: GATED.


## 2026-11-22 — Keputusan Koordinator: Portal, KAMAD, dan Proktor (mengikat)

Keputusan ini berasal dari instruksi koordinator terbaru dan mengesampingkan setiap rumusan lama yang bertentangan pada dokumen ini.

### DESAIN — batas produk final
- Portal/jenis akun aktif pada fase CBT ini hanya ADMIN_MADRASAH, GURU, dan SISWA.
- KAMAD: `KAMAD PORTAL/ACCESS — FROZEN, NOT IN CURRENT CBT SCOPE`. Tidak ada portal, login, dashboard, route, permission, policy access, menu, monitoring, bypass, atau approval ujian KAMAD. Identitas pejabat/histori administrasi resmi yang masih dibutuhkan tidak boleh dihapus.
- Proktor adalah penugasan operasional sementara kepada pengguna dengan akun GURU yang sah. Tidak ada akun/login/credential/portal Proktor tersendiri dan tidak dibuat role permanen PROKTOR.
- Menu `PROKTOR` berada di dalam Web Guru. Menu hanya antarmuka; otorisasi wajib ditegakkan backend berdasarkan assignment yang aktif, masa berlaku, status, tenant/installation, Exam, ExamSession, SessionRoom/Room, dan peserta yang ditempatkan.
- Akun SISWA hanya untuk peserta ujian; bukan identitas Proktor.
- Guru tanpa assignment Proktor aktif tidak memperoleh capability Proktor. Assignment berakhir/dicabut, akses operasional ikut berakhir; akun tetap GURU.

### AUDIT — bukti source yang telah diperiksa (read-only)
| Branch/source | Path | Blob SHA | Temuan |
|---|---|---|---|
| Agent 1 | `backend-laravel/app/Services/ExamProctorAssignmentService.php` | `c968ce68c0decace1c35a0ccb3b2b4449c057656` | Service menerima `proctorUserId` dan memeriksa kelayakan melalui `isEligibleGuru`; penugasan terkait Exam dan SessionRoom. |
| Agent 1 | `backend-laravel/app/Models/ExamProctorAssignment.php` | `88a1c2319200342eb2dc2a5993e95a8b6a32dbfc` | Assignment menyimpan `proctor_user_id`, Exam, SessionRoom, assigner, starts/expires, status, revoke metadata. |
| Agent 1/2/3 | `backend-laravel/app/Services/ExamMonitoringScope.php` | `11b455954f974d1c60ed5c49700368f8d37c09c1` | Masih memberi bypass untuk Exam author dan `hasRole('KAMAD')`; tidak sesuai keputusan KAMAD frozen dan perlu diperbaiki oleh owner backend sebelum runtime approval. |
| Agent 2 | `backend-laravel/app/Policies/ExamPolicy.php` | `c05d96ef932fdc81b67336240af4e1726864c3d6` | `viewAny/view` masih mengenali KAMAD; policy belum sesuai keputusan final. |
| Agent 3 | `backend-laravel/app/Policies/ExamPolicy.php` | `839df41c82a5b45706072d60431706ca5861cc6c` | Policy masih mengenali KAMAD; harus dibekukan/dikeluarkan dari jalur akses CBT oleh owner backend. |
| Agent 3 | `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php` | `e915bf43118026ef8eace96338fa6cad44ee29e1` | `store` menggunakan device binding ID; bukti ini bukan bukti lease/fencing dan tidak menyelesaikan room-scope `show`. |

Inventaris di atas adalah seluruh referensi yang berhasil diverifikasi pada file/class yang dibuka dalam audit ini, bukan klaim exhaustive atas semua route, menu, role seeder, middleware, policy, Livewire, atau konfigurasi repository. Audit tree-wide KAMAD perlu dilanjutkan sebelum menyatakan seluruh referensi telah diinventarisasi.

### KOORDINASI / ownership
- Agent 1: desain dan lifecycle assignment sementara, relasi SessionRoom/Room, expiry/revocation, histori dan audit event. Tidak membuat role Proktor.
- Agent 2: exact-role GURU, autentikasi, authorization dan enforcement server-side; hapus/disable semua jalur akses KAMAD dari scope CBT tanpa menghapus histori identitas resmi.
- Agent 3: menu Proktor di Web Guru; UI hanya menampilkan assignment yang dikembalikan backend dan tidak membuat portal/login baru.
- Agent 4: peserta tetap akun SISWA; tidak ada fungsi Proktor pada Web Siswa.

### Acceptance criteria minimum
1. Login/portal yang aktif hanya Admin Madrasah, Guru, Siswa.
2. KAMAD tidak dapat login atau mengakses endpoint, route, Livewire, API, monitoring, export, atau bypass CBT; histori administratif tidak terhapus.
3. Guru dengan assignment aktif hanya melihat Exam/Session/Room/peserta yang ditugaskan.
4. Guru tanpa assignment, assignment kedaluwarsa/dicabut, tenant berbeda, room berbeda, dan actor non-GURU ditolak server-side.
5. Tidak ada akun, credential, portal, atau role permanen Proktor; menu Proktor berada di Web Guru.
6. Audit event merekam pemberi tugas, Guru, Exam, Session, Room, masa berlaku, perubahan status, pencabutan, alasan, dan actor.
7. Regression/integration test mencakup exact-role, cross-tenant, room A vs room B, expiry/revocation, history retention, serta endpoint/API/Livewire/export.

### Status
Keputusan produk untuk portal/KAMAD/identitas Proktor: FINAL dari koordinator. Implementasi backend/UI tetap GATED karena approval owner Agent 2/3/4 dan izin implementasi runtime belum lengkap. Status Stage 4B keseluruhan tetap `BLOCKED — CROSS-AGENT DECISION PENDING`.
