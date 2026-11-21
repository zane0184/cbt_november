# LAPORAN IMPLEMENTASI — STAGE 4A PORTAL/RBAC DAN PROKTOR GURU

Tanggal: 22 November 2026  
Repository: zane0184/cbt_november  
Branch: work/import-guru-siswa-implementation-20261122  
Source saat laporan: 2ff3f3b6c671e12f25da941f50370bf1cc47eeb5

## STATUS
IMPLEMENTASI PARSIAL — batas Admin terhadap CBT diperkuat dengan uji API langsung, dan pemilihan Proktor sementara kini diarahkan ke Guru aktif yang memenuhi syarat. Stage 4A belum dinyatakan selesai; CI untuk source terbaru berstatus SUCCESS.

## AUDIT
1. Portal Admin Madrasah, Guru, dan antarmuka peserta Siswa sudah memiliki jalur akses/komponen terpisah pada source aktif. AdminDashboard adalah ringkasan administrasi, bukan dashboard operasional CBT.
2. Admin Madrasah ditolak oleh policy untuk Bank Soal, Assessment, Exam, Attempt, Monitoring, Scoring, dan Report. Pengujian sebelumnya mencakup stale multi-role dan stale permission.
3. Akses API memakai auth:sanctum, tenant middleware, license middleware, dan policy/controller authorization. Audit source menunjukkan endpoint CBT tidak boleh bergantung hanya pada penyembunyian menu.
4. Modul penugasan Proktor sebelumnya mencari role permanen PROKTOR, padahal LegacyRoleMigrationService mengonsolidasikan akun legacy tersebut menjadi GURU.
5. TeacherProfile memakai SoftDeletes dan TenantScoped. Tabel users mempunyai deleted_at; model User memeriksa deleted_at secara eksplisit pada kandidat.
6. Status assignment source lama masih ACTIVE dan belum menyimpan rombel_id maupun schedule_revision. ExamPolicy::monitor belum memakai record assignment sebagai capability. Ini tetap menjadi pekerjaan lifecycle/capability berikutnya, bukan diklaim selesai oleh patch ini.

## DESAIN
- Admin Madrasah tidak mengakses bank soal, ujian, token, peserta, jawaban, nilai, monitoring CBT, maupun laporan CBT. Kewenangan Admin tetap administrasi tenant dan pengelolaan penugasan Proktor sementara.
- Guru tetap pemilik operasional CBT; otorisasi harus berdasarkan role tunggal, permission, penugasan akademik, tenant, dan kepemilikan scope.
- Kandidat Proktor sementara harus: tenant sama; akun tidak dihapus; tepat satu role GURU; TeacherProfile tenant-local tersedia dan tidak soft-deleted.
- Tidak ada role/portal Proktor permanen.
- Tidak ada penambahan role, route, portal, permission, fitur, atau alur Kamad. Enam file yang sempat diubah untuk percobaan Kamad telah dipulihkan dan blob SHA aktif cocok dengan backup masing-masing. Referensi Kamad yang sudah ada sebelum patch tidak diperluas dan tidak diberi route portal baru.

## IMPLEMENTASI
1. `backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php`
   - Kandidat UI disaring ke akun GURU dengan role tunggal GURU.
   - Wajib memiliki TeacherProfile tenant-local yang masih aktif (tidak soft-deleted).
   - Validasi server tetap melakukan lookup tenant-scoped; manipulasi selectedProctorId tidak cukup untuk melewati pemeriksaan.

2. `backend-laravel/app/Services/ExamProctorAssignmentService.php`
   - Pemeriksaan ulang dilakukan dalam transaksi setelah user dikunci.
   - Service menolak akun yang role-nya bukan tepat satu GURU atau tidak mempunyai TeacherProfile aktif tenant-local.
   - Audit assignment dan revoke tetap digunakan.

3. `backend-laravel/resources/views/livewire/admin/proctor-assignment-management.blade.php`
   - Label UI menjelaskan penugasan Guru sebagai Proktor sementara.
   - Tidak menjanjikan bahwa pencatatan assignment otomatis memberikan akses monitoring; capability tetap harus dipastikan oleh policy.

4. `backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php`
   - 7 test: Guru aktif dapat ditugaskan dan diaudit; Siswa ditolak; multi-role ditolak; TeacherProfile soft-deleted ditolak; revoke diaudit; Guru tidak dapat membuka halaman assignment Admin; foreign key menolak referensi Guru lintas tenant.
   - Test memeriksa kandidat pada UI dan mengeksekusi service secara langsung untuk memastikan validasi tidak hanya berada di lapisan tampilan.

5. `backend-laravel/tests/Feature/AdminCbtBoundaryTest.php`
   - Ditambah akses API langsung `GET /api/v1/questions` dan `GET /api/v1/exams` untuk Admin yang disuntik stale role/permission.
   - Web route, Livewire, Gate, dan API diuji pada test boundary yang sama.

6. `.github/workflows/wa01-alternative-isolated-runtime.yml`
   - Test `ProctorAssignmentSecurityTest` ditambahkan ke static guard, PHP lint, dan PHPUnit MySQL 8 disposable.

Backup:
- `docs/backup/stage4a-proctor-eligibility-2026-11-22/`
- Manifest: `docs/backup/stage4a-proctor-eligibility-2026-11-22/MANIFEST.md`
- Checkpoint sebelum laporan ini dibackup pada `docs/backup/stage4a-scope-correction-2026-11-22/CHECKPOINT_IMPORT_GURU_SISWA_2026-11-22.md.before-stage4a-implementation-progress`.
- Backup source Kamad yang dipulihkan ada pada `docs/backup/web-admin-stage4a-kamad-2026-11-22/`.

## PENGUJIAN
- CI run 44946877772 — SUCCESS untuk source 2ff3f3b6c671e12f25da941f50370bf1cc47eeb5. Run sebelumnya 44946708332 juga SUCCESS.
- Runtime: PHP 8.3, MySQL 8 disposable, GitHub Actions Ubuntu runner.
- PHP lint: PASS.
- 100 test / 519 assertion pada suite terpilih.
- AdminCbtBoundaryTest: 1 test / 29 assertion PASS, termasuk akses langsung API.
- ProctorAssignmentSecurityTest: 7 test / 15 assertion PASS.
- AdminDashboardAccessTest: 3 / 28 PASS.
- Suite lainnya mencakup academic period, legacy role migration, tenant profile, user scope, import workbook, auth, multi-tenant, tenant isolation, Guru dan Siswa.
- Source 2ff3f3b6c671e12f25da941f50370bf1cc47eeb5 merapikan helper pengujian kandidat invalid agar menguji daftar UI dan service secara langsung. CI run 44946877772 pada SHA tersebut telah selesai dengan status SUCCESS.
- Tidak ada migration baru, perubahan DB produksi, deployment VPS, PR, atau merge.

## PEKERJAAN BERIKUTNYA
1. Tunggu dan verifikasi CI run 44946877772 pada SHA 2ff3f3b6c671e12f25da941f50370bf1cc47eeb5.
2. Selesaikan matriks pengujian URL/API negatif untuk seluruh permukaan CBT Admin, termasuk monitoring, participant, answer, score, dan report.
3. Audit ulang pemisahan UI Admin/Guru/Siswa beserta redirect login, menu, route langsung, dan API.
4. Lanjutkan lifecycle assignment sementara: PENDING → CONFIRMED, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED; tenant/exam/rombel/schedule revision; conflict locking; capability hanya pada CONFIRMED.
5. Hubungkan capability monitoring terbatas kepada assignment yang sah tanpa memberi Guru akses ke token, soal, jawaban, penilaian, atau data di luar rombel/exam yang ditugaskan.
6. Tidak ada migration produksi, merge, PR, atau deployment tanpa prosedur dan persetujuan eksplisit.
