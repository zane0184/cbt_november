# LAPORAN IMPLEMENTASI STAGE 4B — RUANG, SESI, PESERTA, DAN PROKTOR

Tanggal: 22 November 2026
Repository: `zane0184/cbt_november`
Branch kerja: `work/stage4b-room-session-20261122`
Baseline branch: `work/stage4a-monitoring-rbac-20261122`
Baseline HEAD sebelum patch: `32bb76634d3390f8aeefade339fbb044394c783c`

## 1. AUDIT — posisi aktual yang diverifikasi

1. HEAD aktual Stage 4A adalah `32bb76634d3390f8aeefade339fbb044394c783c`, bukan commit laporan lama `88f1510a7d1992f6cd8d4b727daf93fe28a3afd3`.
2. Perbandingan `88f1510...` → `32bb766...` menunjukkan 12 commit tambahan hanya menyentuh dokumentasi/backup; tidak ada source aplikasi yang berubah setelah source terakhir yang diuji.
3. Branch `work/web-admin-package1-implementation-20261122` adalah leluhur Stage 4A (Stage 4A 257 commit di depan, 0 di belakang).
4. Branch `work/web-cbt-hardening-20261119` adalah leluhur Stage 4A (Stage 4A 917 commit di depan, 0 di belakang).
5. Perbandingan dua arah branch `work/import-guru-siswa-implementation-20261122` dengan Stage 4A menunjukkan perubahan source import Guru/Siswa telah tercakup; perbedaan yang tertinggal adalah backup/dokumentasi.
6. Source import aktual yang diperiksa meliputi `ImportManagement`, `ImportWorkbookService`, `ImportActivationToken`, `ImportBatch`, migration import, serta test workbook dan deadlock retry. Tidak ada file import yang ditimpa Stage 4B.
7. RBAC aktual diperiksa pada `RolePermissionBootstrapper`, `ExamPolicy`, `ExamAttemptPolicy`, dan test. Admin Madrasah hanya memiliki kewenangan administrasi termasuk `exam.proctor.assign`; tidak diberikan `exam.monitor`, bank soal, ujian, scoring, atau laporan CBT. Role PROKTOR permanen tidak ditambahkan.
8. Backup sebelum perubahan disimpan secara byte-exact melalui Git source commit `32bb766...` dan blob SHA pada `docs/backup/stage4b-room-session-2026-11-22/MANIFEST.md`. ZIP baseline dan branch Stage 4A tidak diubah.

## 2. DESAIN — keputusan yang diterapkan

- Ruang dan sesi merupakan metadata administratif per tenant.
- Satu ujian dapat memiliki beberapa sesi; sesi terhubung ke ruang.
- Peserta memiliki snapshot `rombel_id` pada saat dimasukkan ke ujian. Record legacy dengan `rombel_id = NULL` tidak ditebak atau di-backfill otomatis.
- Rombel dipetakan ke pasangan ruang-sesi; assignment peserta disimpan sebagai histori.
- Guru Proktor tetap ber-role GURU. Tidak ada role atau portal PROKTOR permanen.
- Capability monitoring hanya berasal dari assignment `CONFIRMED` yang belum dicabut/kedaluwarsa, tenant dan exam cocok, room/session aktif, schedule revision cocok, dan waktu assignment masih berlaku.
- Guru pembuat ujian tetap memiliki cakupan monitoring author. Guru Proktor dibatasi pada peserta di ruang-sesi tugasnya.
- Admin hanya mengelola metadata ruang/sesi dan assignment administratif; Admin tidak mendapat akses ke data operasional CBT.
- Perubahan jadwal atau pemetaan menaikkan schedule revision dan mengubah assignment terdampak menjadi `NEEDS_REVIEW`.

## 3. IMPLEMENTASI — perubahan pada branch Stage 4B

### Database dan model
- Migration Stage 4B membuat master `exam_rooms`, `exam_sessions`, relasi `exam_session_rooms`, pemetaan rombel, assignment peserta, dan penguatan relasi assignment Proktor.
- Composite foreign key menegakkan tenant/exam ownership sepanjang relasi ruang, sesi, rombel, peserta, dan Guru.
- Unique/partial unique index mencegah kode ruang duplikat per tenant, nomor sesi duplikat per ujian, mapping rombel aktif ganda, double placement peserta aktif, serta assignment Proktor aktif ganda pada scope yang sama.
- Legacy assignment tanpa scope ruang-sesi tidak otomatis diberi capability; statusnya direkonsiliasi menjadi `NEEDS_REVIEW`.
- Model baru: `ExamRoom`, `ExamSession`, `ExamSessionRoom`, `ExamSessionRoomRombel`, `ExamParticipantRoomAssignment`.
- Model lama yang diperluas: `Exam`, `ExamParticipant`, `ExamProctorAssignment`.

### Service dan otorisasi
- `ExamRoomSessionService` menangani pembuatan ruang/sesi, pemasangan ruang, pemetaan rombel, pelepasan pemetaan, reschedule, capacity/overlap/double-placement validation, audit, dan schedule revision.
- `ExamProctorAssignmentService` menangani assign, confirm, revoke, validasi Guru, tenant, ruang-sesi, waktu, revision, overlap jadwal Guru, dan audit.
- `ExamMonitoringScope` memusatkan cakupan peserta dan attempt yang boleh terlihat oleh Guru author atau Guru Proktor.
- `MonitoringController` membatasi summary, participants, attempt detail, dan events sesuai scope. Query peserta juga memakai allowlist sort field.
- `ExamController` menyimpan snapshot rombel pada peserta baru dan tidak menebak snapshot untuk peserta legacy.

### Web Admin
- Halaman Livewire `/admin/exam-rooms-sessions` untuk metadata ruang, sesi, pasangan ruang-sesi, pemetaan rombel, dan reschedule.
- Halaman `/admin/proctor-assignments` diperbarui untuk assignment Guru Proktor berdasarkan pasangan ruang-sesi.
- Navigasi Admin ditambahkan untuk ruang/sesi.
- Admin tidak mendapat halaman monitoring CBT atau data jawaban/nilai.
- Tidak ada portal Proktor permanen, Kamad, Super Admin, atau Android yang dikerjakan.

### File utama
- `backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope_tables.php`
- `backend-laravel/app/Services/ExamRoomSessionService.php`
- `backend-laravel/app/Services/ExamProctorAssignmentService.php`
- `backend-laravel/app/Services/ExamMonitoringScope.php`
- `backend-laravel/app/Livewire/Admin/ExamRoomSessionManagement.php`
- `backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php`
- `backend-laravel/app/Models/ExamRoom.php`
- `backend-laravel/app/Models/ExamSession.php`
- `backend-laravel/app/Models/ExamSessionRoom.php`
- `backend-laravel/app/Models/ExamSessionRoomRombel.php`
- `backend-laravel/app/Models/ExamParticipantRoomAssignment.php`
- `backend-laravel/app/Models/ExamParticipant.php`
- `backend-laravel/app/Models/ExamProctorAssignment.php`
- `backend-laravel/app/Models/Exam.php`
- `backend-laravel/app/Http/Controllers/Api/V1/ExamController.php`
- `backend-laravel/app/Http/Controllers/Api/V1/MonitoringController.php`
- `backend-laravel/resources/views/livewire/admin/exam-room-session-management.blade.php`
- `backend-laravel/resources/views/livewire/admin/proctor-assignment-management.blade.php`
- `backend-laravel/routes/web.php`
- `backend-laravel/resources/views/layouts/app.blade.php`
- `backend-laravel/tests/Feature/ExamRoomSessionManagementTest.php`
- `backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php`
- `backend-laravel/tests/Feature/MonitoringDuplicationTest.php`
- `.github/workflows/wa01-alternative-isolated-runtime.yml`

## 4. PENGUJIAN — bukti aktual

GitHub Actions: [Run 44957719985 — SUCCESS](https://github.com/zane0184/cbt_november/actions/runs/44957719985)

Lingkungan:
- PHP 8.3, Composer 2.
- MySQL 8 disposable database dan akun aplikasi dengan grant terbatas.
- Semua tabel hasil migration diverifikasi memakai InnoDB.
- Tidak menggunakan database produksi.

Hasil:
- PHP lint aplikasi, migration, route, dan test: PASS.
- Static guard Admin CBT: PASS.
- Admin CBT boundary: 1 test / 27 assertions.
- Admin dashboard: 3 / 28.
- Academic period: 3 / 33.
- Legacy role migration: 4 / 34.
- Tenant profile: 3 / 18.
- Admin user management: 3 / 21.
- Import workbook/template/deadlock retry: 35 / 182.
- Auth, multi-tenant, tenant isolation, Web Admin auth, login siswa, result visibility: 36 / 119.
- Monitoring duplication/scope: 9 / 48.
- Temporary Guru Proktor: 8 / 34.
- Stage 4B room/session/participant placement: 5 / 15.

**Total suite terpilih: 110 tests / 559 assertions — PASS.**

Test suite terpilih tersebut bukan seluruh suite proyek. Belum ada browser E2E, UAT madrasah, multi-connection race test paralel MySQL, atau validasi operasional di server produksi.

## 5. STATUS — Stage 4B

**IMPLEMENTASI AWAL TERUJI CI, BELUM DINYATAKAN SELESAI / SIAP PRODUKSI.**

Yang sudah terbukti:
- Migration berhasil dijalankan pada MySQL 8 disposable.
- Relasi tenant dan test suite Stage 4B lulus.
- Monitoring Guru author/Proktor dibatasi sesuai scope.
- Admin ditolak pada permukaan CBT.
- Import Guru/Siswa dan regresi Guru/Siswa yang dipilih tetap lulus.

Pekerjaan Stage 4B yang masih perlu ditutup:
1. CRUD ruang (edit/deaktivasi) dengan capacity guard dan histori.
2. Lifecycle sesi (aktif, selesai, pembatalan) dan sinkronisasi status assignment `EXPIRED`/`COMPLETED`.
3. Prosedur individual placement/reassignment bila satu rombel harus dipecah lintas ruang, dengan histori dan tanpa double placement.
4. Multi-connection race test MySQL 8 untuk room overlap, capacity, double placement, assignment overlap, dan schedule revision.
5. Browser E2E dan UAT Admin/Guru.

## 6. BATASAN OPERASIONAL

- Tidak merge.
- Tidak membuat PR.
- Tidak deployment.
- Tidak mengakses VPS/aaPanel.
- Tidak menjalankan migration produksi.
- Tidak mengubah database produksi.
- Tidak menjalankan Gradle.
- Tidak mengerjakan Android, Kamad, Super Admin, atau portal Proktor permanen.

## 7. LANGKAH BERIKUTNYA

Lanjutkan menutup Stage 4B (CRUD ruang, lifecycle sesi/assignment, individual placement, concurrency MySQL), lalu Stage 4C penugasan akademik Guru, Stage 4D manajemen Guru, Stage 4E siswa/rombel, Stage 4F kurikulum/pembelajaran, dan Stage 4G dashboard/administrasi Admin. Setelah Web Admin benar-benar lolos gate, pindah ke Web Guru, kemudian Web Siswa.
