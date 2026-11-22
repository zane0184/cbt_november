# Checkpoint Agent 3 — Web CBT Scope & P0 Security Gate

Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-foundation-20261122`
HEAD aktual saat refresh: `4c695181bafa43ad0f02006b8a63e551b8829a03`
Target test source: `6334dc9abd07a1441c8c02f3f7a6cc5c452ead36`
Test blob: `4f1bb48b390b03b2602df6348fd3b9788581e6c9`
Instruksi koordinator otoritatif: Issue #10 comment `7078921888`
Laporan audit lengkap: `docs/AGENT3_WEB_CBT_SCOPE_P0_SECURITY_AUDIT_2026-11-22.md`
Laporan blob akan dicatat setelah commit.

## STATUS

**P0 OPEN — WEB CBT BELUM MEMILIKI BUKTI ACCEPTANCE LENGKAP.**

Checkpoint ini adalah checkpoint Agent 3 aktif dan mengoreksi rumusan scope yang bertentangan pada checkpoint Agent 3 terdahulu. Referensi lama adalah histori, bukan instruksi aktif.

## SCOPE FINAL

- Fokus sampai Juni 2027 hanya Web CBT.
- Android native/Android Studio/API Android dan Web Super Admin ditunda; bukan blocker/dependency.
- Portal aktif hanya ADMIN MADRASAH, GURU, SISWA.
- Tidak ada akun/portal/role OPERATOR.
- Tidak ada akun/login/portal/role permanen PROKTOR. Proktor = assignment sementara akun GURU; menu berada dalam Web Guru.
- SISWA hanya peserta ujian.
- Tidak ada aktor/fitur/akses CBT untuk identitas di luar tiga portal. Legacy role string hanya diperiksa jika memengaruhi authorization/fungsi.
- Satu instalasi per madrasah dengan database akademik terpisah; Tenant ID tetap defense-in-depth; semua komponen operasional diisolasi per instalasi.

## AUDIT

- HEAD Agent 3 `4c695181...`; test blob `4f1bb48...`, 22 test methods statis.
- Agent 1 HEAD `69f16da9...`; Agent 2 HEAD `103e543a...`; Agent 4 docs HEAD `05447d93...`.
- Agent 2 source runtime baseline masih `74bf7178...`; Stage 4E adalah dokumentasi read-only, bukan runtime patch.
- Issue #8/#9/#10 masih OPEN, komentar 7/2/16; owner approval teknis terpisah belum terverifikasi.
- Web Guru route group saat ini hanya dashboard, pembelajaran, kurikulum, profil, template import. Belum ada route/menu Proktor.
- Layout Guru masih menonaktifkan Bank Soal, Asesmen/Ujian, Monitoring, Penilaian dan Analytics; copy menyebut assignment Proktor dikelola Admin.
- QuestionBank/ExamManagement memakai komponen namespace Admin dan URL `/admin/*`, tetapi component boundary menuntut Guru; perlu rekonsiliasi routing/UI.
- ProctorAssignmentManagement dan ExamRoomSessionManagement masih Admin UI.
- Beberapa Admin component memakai `hasRole('ADMIN_MADRASAH')` atau role-exists, belum exact-one. CurriculumManagement masih memiliki role literal legacy `ADMIN` pada authorization; ini berdampak langsung pada tiga-portal allowlist.
- Agent 2 ExamPolicy masih banyak `hasRole()`; `startAttempt` tidak memverifikasi exact-one SISWA. Agent 2 QuestionPolicy memiliki helper exact-GURU privat; Agent 3 QuestionPolicy masih `hasRole('GURU')`. Rekonsiliasi menunggu review Agent 2.
- HTTP POST Livewire test terbaru hanya membuktikan pemulihan TenantContext dan stale tenant reassignment. Revocation lain masih memakai Livewire test harness/direct boot.
- CSRF, replay/tampering snapshot, session lintas instalasi, side-effect coverage lengkap, MySQL 8, CI exact-SHA, Browser QA belum terbukti.

## DESAIN

- Exact-role contract harus shared, persisted, fail-closed terhadap mixed/empty/unknown/revoked roles dan stale permissions.
- Agent 2 owner helper/authorization backend; Agent 3 mengikuti kontrak pada QuestionPolicy/UI/Admin components milik Agent 3 setelah approval.
- Livewire POST diuji sebagai HTTP request baru dengan session/auth identity yang sama, dan semua revocation harus menolak.
- Menu Proktor berada di Web Guru; capability server-side dibatasi assignment aktif, expiry/revocation, ExamSession kanonis, Room/SessionRoom, peserta, tenant dan installation.
- Admin tetap mengelola administrasi/master Room/session/placement/assignment sesuai permission. Guru membuat dan menerbitkan Exam sesuai assignment tanpa approval Admin.
- Monitoring UI Proktor wajib room-scoped di seluruh data surfaces. Exam-level Gate saja bukan bukti room scope.

## IMPLEMENTASI

- Tidak ada source runtime/test/policy/route/middleware/controller/service/migration/schema/config/dependency/workflow/database yang diubah pada audit koreksi ini.
- Laporan dan checkpoint ini adalah file baru; tidak ada file lama ditimpa.
- Backup pre-patch test sebelumnya tetap byte-exact, blob `0b273618...`.
- Tidak ada merge/rebase/cherry-pick/deployment/production DB access.

## PENGUJIAN

| Tes | Status |
|---|---|
| Refresh branch/source/issues | PASS — read-only |
| Source inspection | PASS — statis |
| Backup pre-patch | PASS — byte-exact |
| PHP lint PHP 8.3 | NOT RUN |
| Route registration | NOT RUN |
| PHPUnit | NOT RUN |
| HTTP Livewire full revocation | NOT RUN / PARTIAL |
| Exact-role all portals | NOT RUN |
| CSRF | NOT RUN |
| Snapshot replay/tampering | NOT RUN |
| Tenant/session two-installation | NOT RUN |
| Database integrity full effects | PARTIAL / NOT RUN |
| MySQL 8/InnoDB | NOT RUN |
| CI exact-SHA | UNKNOWN |
| Browser QA | BLOCKED |
| Production database | NOT TOUCHED |

## NEXT GATE

1. Agent 2: approval/review exact-role contract dan QuestionPolicy/ExamPolicy/Admin authorization.
2. Agent 1: approval kontrak ExamSession/Room/SessionRoom/placement/Proktor lifecycle/monitoring scope.
3. Agent 4: kontrak Web Siswa participant/Attempt/Answer/submit/resume.
4. Agent 3: setelah kontrak diterima, rapikan route/menu Guru dan implementasikan menu Proktor dalam Web Guru; exact-role Admin component guards; HTTP Livewire revocation matrix.
5. Siapkan PHP 8.3 + Composer lockfile + MySQL 8/InnoDB disposable, lalu jalankan semua acceptance tests exact-SHA.
6. Browser QA non-production setelah gate backend lulus.

Tidak ada Android atau Web Super Admin. Tidak ada migration/deployment produksi.

**P0 tetap OPEN.**


## ADDENDUM AUDIT — Monitoring Room Scope & Admin/Guru Exact-Role (22 Nov 2026)

Audit tambahan dan temuan rinci: `docs/AGENT3_WEB_CBT_MONITORING_ROOM_SCOPE_ADDENDUM_2026-11-22.md`.

- Monitoring view saat ini menggunakan endpoint Exam-level tanpa Room/SessionRoom parameter pada client; belum terbukti backend data bocor, tetapi tidak cukup sebagai consumer Proktor room-scoped.
- Agent 1 assignment service memvalidasi tepat satu role GURU; UI Admin calon Proktor belum menyaring exact-one.
- Beberapa Admin Livewire components belum exact-one; CurriculumManagement masih menerima role literal legacy ADMIN. Ini perlu rekonsiliasi exact-role Agent 2 sebelum patch.
- Tidak ada runtime source/test berubah; seluruh temuan statis dan tes runtime NOT RUN.

Checkpoint ini dibackup byte-exact sebelum diperbarui. Backup: `docs/backup/agent3-web-cbt-p0-2026-11-22/CHECKPOINT_AGENT3_WEB_CBT_P0_SCOPE_2026-11-22.md.before-monitoring-refresh`.


---

## ADDENDUM WAJIB — Eksekusi P0 Web Guru dan keputusan tanpa ExamSession (22 Nov 2026)

Addendum ini menggantikan seluruh bagian checkpoint lama yang menyebut satu ExamSession kanonis, session number, wave, atau lifecycle sesi. Acuan terbaru adalah Issue #10 comment `7079750527`.

### Identitas terbaru
- Source HEAD saat refresh: `91d484b34b7e01fa6652138761ccbcab80370349`.
- Laporan eksekusi lengkap: `docs/AGENT3_WEB_GURU_P0_EXECUTION_2026-11-22.md`.
- Agent 1 direct Exam–Room partial HEAD: `0f6921ed7301810689f1d342f5e071ce0851a695`.
- Agent 2 HEAD: `7642f8df7d20effe82dc1ba5b086147894332767`.
- Agent 4 Web Siswa audit HEAD: `a0425e63c06fe2ac0ddbd942f81dd7db6ca111fd`.

### Keputusan teknis final
- Satu Exam = satu pelaksanaan serentak memakai `exam.start_at`–`exam.end_at` dan waktu server.
- Tidak ada ExamSession/session number/wave/lifecycle sesi baru.
- Room, roster/placement, dan assignment Proktor terhubung langsung ke Exam + Room.
- Mode A: Guru pembuat asesmen mandiri hanya dalam lingkup penugasan kelas/pesertanya.
- Mode B: ujian terpusat; Admin Madrasah menugaskan Guru sebagai Proktor untuk Room tertentu. Lintas kelas hanya melalui assignment Admin.
- Tabel/relasi sesi lama dipertahankan sebagai histori; tidak boleh mass delete/drop/auto-map sesi ambigu. Transition harus forward-only dan provenance-aware.

### Implementasi Agent 3 yang telah ditambahkan
- AuthWebController membatasi login ke exact-one ADMIN_MADRASAH/GURU/SISWA dan menolak KAMAD/OPERATOR/multi-role; stale invalid session dilogout.
- Route Guru: `/guru/bank-soal` dan `/guru/asesmen`, dilindungi auth/tenant/license/EnsureTeacherPortalAccess.
- Layout Guru menampilkan menu Bank Soal dan Asesmen & Ujian; Monitoring/Penilaian/Analytics masih ditahan.
- QuestionBank memblokir SHORT_ANSWER pada validasi server sampai accepted answers terbukti masuk snapshot/scoring.
- ExamManagement digunakan sebagai asesmen mandiri Mode A, memakai Exam.start_at/end_at, assignment rombel, snapshot dan token otomatis saat publish. Tidak ada ExamSession UI.
- Dashboard student count membatasi pada user/profile aktif dan tepat satu role SISWA.
- Test tambahan untuk exact portal login, route Guru, unsupported SHORT_ANSWER, dashboard count, publish/token. Workflow filter memasukkan TeacherPracticeWorkflowTest dan PortalLoginRoleBoundaryTest.

### Audit dan blocker
- Branch Agent 3 memiliki test tambahan tetapi belum ada CI exact-SHA dan Browser/E2E evidence pada source HEAD terbaru.
- CI #44984094200 PASS pada SHA lama `e9c6867d40b134538e791bbbba02f5743afec7ea` (11 tests/44 assertions), bukan HEAD saat ini. CI #45003191589 pernah melaporkan dua test expectation failures; perbaikan test sudah dibuat tetapi belum ada bukti run exact-SHA terbaru.
- Agent 1 sudah menambahkan migration/model awal direct Exam–Room, namun Proctor create/confirm/revoke service, placement callers/UI, monitoring consumers, concurrency dan MySQL 8 tests belum selesai.
- Agent 2 belum menetapkan global lock order/serialization contract; exact-role policy/API dan LoginIdentityResolver masih perlu review.
- Agent 4 belum membuktikan admission, Attempt/Answer, submit/resume, recovery/sync dan UAT.
- Menu Proktor tetap tidak diaktifkan sebelum direct Exam–Room backend dan room-scoped authorization lulus.
- Upload media, preview, RTL eksplisit, formula editor dan source/versioning kurikulum resmi belum terbukti lengkap.
- URL deployment telah diinformasikan pemilik, tetapi Agent 3 tidak mengakses VPS/SSH dan tidak memverifikasi deployed commit/schema. Tidak ada production DB/migration/deployment.

### File backup
Checkpoint sebelum addendum ini disimpan byte-identical di:
`docs/backup/agent3-web-guru-urgent-20261122/CHECKPOINT_AGENT3_WEB_CBT_P0_SCOPE_2026-11-22.md.before-urgent-web-guru-execution`.

**Status: PARTIAL — P0 OPEN. Portal Guru belum boleh dinyatakan siap operasional end-to-end atau siap produksi.**


---

## ADDENDUM — CI exact-SHA dan status upgrade (22 Nov 2026)

- Source terakhir yang mengubah runtime/test: `91d484b34b7e01fa6652138761ccbcab80370349`; branch HEAD saat addendum: `2b85995403df4fbc47adb5692370bdbbe1d6c180` (dokumentasi/backup setelah source SHA).
- Run [GitHub Actions #45028604234](https://github.com/zane0184/cbt_november/actions/runs/45028604234) pada exact source SHA berakhir failure pada attempt 1. Job tidak memiliki steps dan runner belum teralokasi. Rerun attempt 2 juga failure, tanpa steps/log (log endpoint `BlobNotFound`).
- Run #45028590509 pada SHA source sebelumnya juga gagal sebelum step. Ini **bukan** bukti PHPUnit gagal; PHPUnit/PHP lint/route:list belum dieksekusi. Status: BLOCKED / NOT EXECUTED.
- Workflow CI saat ini sudah memasukkan TeacherPracticeWorkflowTest bersama tiga test suite lain, tetapi belum ada test result.
- Statistik siswa memakai akun tidak terhapus dan tepat satu role SISWA. Pivot `rombel_student` tidak memiliki status aktif; keberadaan row tenant-local pada rombel aktif dipakai sebagai definisi membership aktif yang tersedia saat ini.
- Ada perbedaan konteks: prompt terbaru menyebut database aplikasi kosong, sedangkan addendum pemilik Issue #10 menyebut deployment existing yang harus mempertahankan data. Karena Agent 3 tidak punya akses SSH/read-only deployment metadata, schema dan migration ledger **belum diverifikasi**. Tidak ada migration, reset, atau deployment produksi.
- P0 tetap OPEN. Tidak ada Browser/E2E, visual QA, Mode B Proktor, Essay grading, media/RTL/formula atau UAT yang dinyatakan selesai.
