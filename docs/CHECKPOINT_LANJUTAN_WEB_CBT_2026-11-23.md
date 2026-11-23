# CHECKPOINT LANJUTAN WEB CBT — 23 NOVEMBER 2026

**Branch utama tunggal:** work/solo-web-cbt-completion-20261123  
**Commit source terakhir yang sedang diuji:** 49625c1a7e79ee97b6e394412d189c859e93e6ef  
**Commit dokumentasi checkpoint:** da43a9d12910222aefcc0f1512bc0ec9c9ac2048  
**CI:** [Tenant Backend CI #45133706442](https://github.com/zane0184/cbt_november/actions/runs/45133706442)  
**Keputusan owner:** [Issue #20](https://github.com/zane0184/cbt_november/issues/20)  
**Master scope Web Admin:** [Issue #12](https://github.com/zane0184/cbt_november/issues/12)  
**Laporan lengkap W1:** [REPORT_AUDIT_IMPLEMENTASI_W1_2026-11-23.md](REPORT_AUDIT_IMPLEMENTASI_W1_2026-11-23.md)  
**PR terpadu:** [PR #21](https://github.com/zane0184/cbt_november/pull/21) — OPEN/DRAFT/UNMERGED.

> Status: WORK IN PROGRESS — NOT PRODUCTION READY. Source SHA 49625... sedang diuji. Commit dokumentasi ini tidak mengubah source dan menggunakan [skip ci]; CI tetap menguji source SHA tersebut. Tidak ada merge, deployment, atau perubahan database produksi.

## Posisi aktual

### AUDIT
- Owner menetapkan satu branch eksekusi: work/solo-web-cbt-completion-20261123. Branch kerja sebelumnya telah fast-forward ke branch ini tanpa force push.
- Portal aktif: Admin Madrasah, Guru, Siswa. Kamad/Operator/Proktor bukan portal permanen.
- Audit W1 menemukan legacy-role grants pada AssessmentPolicy dan ExamAttemptPolicy, multi-role bypass di policy/controller, assignment Proktor yang dahulu memberi monitoring, serta roster yang belum konsisten exact role.
- Migration CI sebelumnya gagal karena kolom teacher_subject_assignment_id hilang pada qvtom dan qvsm mapping table. Keduanya telah ditambahkan tanpa menghapus unique/FK.
- Admin dashboard sebelumnya memiliki dead link /admin/exams; sekarang diarahkan ke /admin/proctor-assignments dan kartu Total Ujian diganti hitungan assignment pengawas aktif.
- Eligibility ujian pusat belum memiliki penanda kanonis. EXTERNAL tidak boleh otomatis dianggap ujian pusat.
- AssessmentController API lama masih menggunakan assessment_type enum tetap dan belum memenuhi canonical taxonomy/assignment context.
- Migration konsolidasi akun legacy tetap BLOCKED untuk produksi sampai mapping, backup/recovery, disposable upgrade test dan approval owner.

### DESAIN
- Semua authorization boundary harus memastikan tepat satu role yang sesuai.
- Assignment pengawas adalah catatan administratif saja, bukan akses monitoring, roster, room, seat, Attempt, jawaban, nilai, token atau operasi CBT.
- Guru hanya mengakses scope penugasan; Siswa hanya data miliknya.
- Migration dan data harus mempertahankan unique/FK, tenant isolation, audit, histori dan idempotency.
- Tidak menambah desain Room/session yang bertentangan dengan Issue #20.

### IMPLEMENTASI
Patch W1 pada source mencakup:
- User exact-role helpers dan fresh permission lookup.
- BasePolicy, ExamPolicy, AssessmentPolicy, ExamAttemptPolicy, QuestionPolicy, ReportPolicy.
- TeacherAccess, StudentExamController, ExamController roster, StudentRombelManagement.
- Admin Dashboard role gate dan metric assignment pengawas.
- ExamProctorAssignmentService exact Guru.
- ProktorDashboard compatibility stub selalu 403.
- AuthWebController logout legacy/multi-role session.
- Regression tests WebAdminAuth, RoleDashboardVerification, ProctorAssignmentSecurity, Phase7DReporting dan AdminUiVerification.
- Migration qvtom/qvsm assignment scope columns.

Backup byte-exact source/test disimpan pada branch backup/solo-web-cbt-before-ci-repair-20261123, folder backend-laravel/docs/backup/solo-web-cbt-role-rbac-2026-11-23/. Backup migration lama juga tersedia pada folder backup stage sebelumnya. Lihat laporan W1 untuk detail.

### PENGUJIAN
CI run #45133706442 pada source SHA 49625c1a7e79ee97b6e394412d189c859e93e6ef:
- MySQL 8 disposable identity/InnoDB: PASS.
- Composer validate/install: PASS.
- Laravel package discovery: PASS.
- Disposable installer DB creation: PASS.
- Focused Web login/tenant tests: IN PROGRESS pada pemeriksaan terakhir.
- Full PHPUnit: NOT RUN sampai focused test selesai.
- Browser E2E, concurrency multi-connection, upgrade legacy DB, restore, Android multi-device dan UAT madrasah: NOT RUN.

## Langkah berikutnya

1. Tunggu hasil CI #45133706442; jika gagal, ambil log dan patch akar masalah dengan backup.
2. Setelah focused tests PASS, jalankan full PHPUnit pada disposable MySQL 8.
3. Tutup semua exact-role negative tests untuk API/policy dan perbaiki test lama yang masih stale.
4. Selesaikan kontrak penanda ujian pusat sebelum mengaktifkan assignment Proktor.
5. Selesaikan canonical AssessmentController API; fail-closed untuk jalur tulis lama sampai satu DTO/validation tersedia.
6. Lanjutkan inventaris lengkap ADM-01–ADM-23, lalu implementasikan gap yang terbukti.
7. Lengkapi Admin CRUD, audit log, import/export Guru/Siswa, akademik dan histori rombel, profil/branding, kop surat, settings, notifikasi, health dan backup/restore.
8. Uji concurrency, idempotency, tenant isolation, recovery dan audit.
9. Tidak ada merge PR, migration produksi, atau deployment VPS sebelum semua gate dan approval terpenuhi.

**Kriteria belum tercapai:** full PHPUnit PASS, E2E keamanan/integrasi, concurrency MySQL 8, backup/restore, UAT madrasah, dan validasi Android/VPS. Jangan menyatakan siap produksi.
