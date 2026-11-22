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


## Keputusan Koordinator yang mengikat — Portal, KAMAD, Proktor

| Komponen | Keputusan final |
|---|---|
| Admin Madrasah | Akun dan portal aktif |
| Guru | Akun dan portal aktif |
| Siswa | Akun dan portal aktif |
| KAMAD | `KAMAD PORTAL/ACCESS — FROZEN, NOT IN CURRENT CBT SCOPE` |
| Proktor | Penugasan operasional sementara kepada akun GURU |
| Menu Proktor | Menu khusus di dalam Web Guru |
| Portal/login/akun Proktor terpisah | Tidak ada |
| Role permanen PROKTOR | Tidak dibuat |
| Akun SISWA sebagai Proktor | Dilarang |

**Koreksi wajib terhadap isi lama:** setiap rumusan yang menyebut Proktor memakai akun Siswa atau memiliki portal tersendiri dinyatakan superseded. KAMAD bukan role CBT aktif dan tidak mendapat akses/monitoring/approval khusus.

### Pembagian ownership yang diperbarui
- Agent 1: assignment sementara Guru-Proktor per SessionRoom/Room, lifecycle, expiry/revocation, history dan audit.
- Agent 2: authentication, exact-role GURU, backend authorization, tenant/installation checks, larangan akses KAMAD.
- Agent 3: menu PROKTOR dalam Web Guru; UI tidak memberi capability dan tidak membuat login/portal baru.
- Agent 4: SISWA tetap peserta; tidak mengimplementasikan Proktor.

### Enforcement dan acceptance
Backend harus menolak Guru tanpa assignment aktif, assignment expired/revoked, room/Exam/Session berbeda, tenant/installation berbeda, dan actor bukan akun GURU yang memenuhi exact-role. Menu frontend bukan kontrol keamanan. Semua endpoint, Livewire, query monitoring, export dan media harus room-scoped. Histori identitas/assignment tetap dipertahankan.

Status matriks ini tetap usulan ownership sampai pemilik teknis mengonfirmasi scope masing-masing. Keputusan produk di atas sudah final dari koordinator; bukan approval source atau izin implementasi.


## KOREKSI SCOPE FINAL — berlaku menggantikan rumusan lama
- Sampai Juni 2027: HANYA WEB CBT. Android native/Android Studio dan Web Super Admin ditunda; bukan blocker/dependency/prasyarat.
- Portal aktif: ADMIN_MADRASAH, GURU, SISWA saja.
- Fungsi Operator berada pada Admin Madrasah; tidak ada akun/role OPERATOR.
- Proktor adalah penugasan sementara pada akun GURU; menu di Web Guru; tidak ada akun/login/credential/portal/role permanen Proktor.
- SISWA hanya peserta ujian, bukan Proktor.
- KAMAD tidak termasuk ruang lingkup identitas/kewenangan CBT. Jangan mencantumkannya sebagai aktor, kebutuhan, atau acceptance criterion. Referensi legacy hanya dicatat bila berdampak pada allowlist otorisasi atau fungsi Web CBT.
- Distribusi: satu ZIP untuk semua madrasah, instalasi terpisah per madrasah, database akademik dan seluruh resource operasional terisolasi per instalasi, tenant_id sebagai defense-in-depth.

## Ownership yang berlaku untuk Web CBT
- Agent 1: Exam/ExamSession canonical, Room/SessionRoom, placement, temporary Guru-Proktor assignment, scheduler, close/recovery barrier, audit dan integritas relasi.
- Agent 2: kontrak backend Exam/Attempt/Answer/Score, auth/exact-role tiga akun, tenant/installation defense, global lock order, idempotensi dan lifecycle transaksi. Agent 1 wajib meminta keputusan lock/recovery yang beririsan sebelum patch lintas-service.
- Agent 3: Web Admin Madrasah dan Web Guru, termasuk menu Proktor di Web Guru; UI hanya consumer backend authorization dan room-scoped data.
- Agent 4: Web Siswa admission, token, jadwal, Attempt/Answer persistence, submit/resume/expiry/recovery; SISWA hanya peserta.

Ownership tetap tidak mengizinkan satu agent mengubah branch milik agent lain atau menganggap persetujuan tanpa bukti langsung.
