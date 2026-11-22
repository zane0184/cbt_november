# LAPORAN AUDIT DAN PERBAIKAN — STAGE 4C / AUDIT AWAL 4D

Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch kerja paralel: work/stage4d-account-lifecycle-20261122
Base HEAD saat branch dibuat: 0631a3d84d058c8c049dcb71dc54b2e7cbb849a6

## AUDIT

### HEAD dan perubahan dari agent pertama
- Branch acuan `work/stage4b-room-session-20261122` bergerak dari checkpoint lama `2746c20e...` ke `0631a3d84d058c8c049dcb71dc54b2e7cbb849a6`.
- Enam commit baru setelah checkpoint mencakup perbaikan urutan rollback migration Stage 4B, backup migration/test, dan cleanup test concurrency dengan foreign key tetap aktif.
- Perubahan agent pertama dipertahankan. Tidak ada merge/rebase dan tidak ada perubahan pada branch acuan.
- Branch paralel dibuat dari SHA yang diverifikasi untuk menghindari benturan kerja Stage 4B.

### CI Stage 4B / 4C
- Run `44980765291`, SHA `3fbf3b4a...`, berakhir FAILURE.
- PHP lint dan seluruh test sebelum Stage 4C lulus pada run tersebut.
- Test `TeacherSubjectAssignmentManagementTest`: 5 lulus, 2 gagal.
- Kegagalan pertama: setup test lintas tahun ajaran tidak mencapai guard server-side yang dimaksud; Livewire hook perubahan tahun mengosongkan referensi lama. Test diperbaiki agar sengaja memasang kembali semester/rombel lama setelah perubahan tahun.
- Kegagalan kedua: test non-Admin membuat role GURU kedua padahal role tersebut sudah dibuat di setUp, sehingga melanggar unique `roles.name`. Fixture diubah memakai `firstOrCreate`.
- Run `44981295216` pada SHA `8b44ff71...` CANCELLED.
- Run `44981313574` pada SHA `0631a3d8...` CANCELLED.
- Run `44981254776` pada SHA `8bdda41d...` masih perlu dipantau; bukan bukti pengujian untuk HEAD final.
- Setelah perbaikan test, workflow dijalankan pada branch paralel. Run `44981468061` untuk SHA `886f6edf30d1582b639379eb118efa25a0ef28be` terpantau IN_PROGRESS pada saat laporan ini dibuat. Belum ada klaim PASS.

### Audit awal Stage 4D — akun Guru/Siswa
Source aktual yang diperiksa:
- `app/Livewire/Admin/UserManagement.php`
- `resources/views/livewire/admin/user-management.blade.php`
- `app/Models/User.php`
- `app/Models/TeacherProfile.php`
- `app/Models/StudentProfile.php`
- migration `0001_01_01_000000_create_users_table.php`
- migration `2024_01_01_000001_create_profiles_tables.php`
- migration `2026_11_11_000003_add_login_identity_fields.php`
- `app/Services/AuditService.php`
- `app/Http/Controllers/AuthWebController.php`

Temuan:
1. Halaman UserManagement saat ini mendukung daftar/pencarian/filter dan pembuatan Guru/Siswa. Belum ada edit, aktivasi/nonaktif akun, reset password, atau tampilan riwayat perubahan.
2. Tabel `users` sudah memiliki `deleted_at`, tetapi model `User` saat ini belum memakai trait SoftDeletes. Karena itu kolom tersebut belum dapat dianggap sebagai mekanisme deaktivasi yang berfungsi.
3. Profil Guru dan Siswa sudah memakai SoftDeletes. Jangan menghapus profil atau mengubah ID akun untuk tindakan lifecycle.
4. `AuditService` mendukung old_values/new_values; pembuatan akun sudah diaudit. Riwayat untuk edit/status/reset belum tersedia karena aksinya belum ada.
5. Email bersifat nullable dan unique hanya per tenant. Tabel `password_reset_tokens` bawaan Laravel memiliki email sebagai primary key global. Penggunaan broker reset standar berisiko benturan email antar-tenant dan tidak cukup aman untuk akun tanpa email. Reset password belum boleh diimplementasikan memakai broker standar tanpa desain token tenant-scoped.
6. Role list saat ini dibatasi pada satu role GURU atau SISWA; pembatasan ini harus dipertahankan. Tidak boleh mengelola Admin/Kamad/Super Admin dari halaman ini.

### Audit awal Stage 4D — histori akademik Siswa
Source aktual yang diperiksa:
- `app/Livewire/Admin/StudentRombelManagement.php`
- `resources/views/livewire/admin/student-rombel-management.blade.php`
- `app/Models/RombelStudent.php`
- `app/Models/Rombel.php`
- `app/Models/StudentProfile.php`
- migration `2024_01_01_000002_create_academic_tables.php`
- migration `2026_11_09_230003_harden_rombel_student_tenant_integrity.php`

Temuan:
1. UI saat ini menambah keanggotaan siswa ke rombel dan menampilkan relasi rombel/tahun ajaran.
2. Pivot `rombel_student` memiliki identitas tenant dan constraint integritas tenant, tetapi skema awalnya hanya memiliki rombel_id, student_id, timestamps; tidak ada status keanggotaan, tanggal efektif mulai/akhir, atau event type.
3. Handler saat ini menolak duplikasi pasangan siswa-rombel, tetapi tidak menutup keanggotaan rombel lama ketika menambahkan ke rombel baru.
4. Akibatnya, data pivot yang ada tidak cukup untuk membedakan keanggotaan aktif dengan riwayat lama. Backfill status aktif/nonaktif tidak boleh ditebak.
5. Belum ditemukan model/tabel StudentAcademicHistory khusus pada source tree yang diperiksa.
6. Kenaikan kelas, tinggal kelas, pindah rombel, kelulusan, dan perubahan status akademik belum memiliki workflow yang dapat diverifikasi.

## DESAIN

### Perbaikan Stage 4C
- Pertahankan validasi tenant dan aturan domain pada component.
- Perbaiki test agar menguji guard server-side secara eksplisit, bukan mengandalkan state yang telah dibersihkan oleh Livewire hook.
- Perbaiki fixture role agar tidak membuat record duplikat.
- Tidak mengubah aturan bisnis, API, atau migration Stage 4C.

### Stage 4D — batas implementasi aman
- Edit akun hanya untuk field yang memang ada dan diizinkan pada schema/model.
- Aktivasi/nonaktif harus mempertahankan akun dan profil; jangan hard delete.
- Audit old/new values untuk setiap perubahan.
- Reset password harus memakai mekanisme tenant-scoped, one-time, expiry, dan tidak menampilkan/menyimpan password plaintext dalam audit. Email nullable berarti akun tanpa kanal pemulihan perlu prosedur terotorisasi terpisah.
- Histori akademik harus menjadi event append-only dengan tenant, siswa, tahun ajaran, rombel asal/tujuan, jenis perubahan, tanggal efektif, aktor, dan alasan. Namun sebelum migration, perlu strategi transisi yang tidak menebak status pivot lama.
- Tidak boleh menganggap menambahkan pivot baru sebagai transfer jika membership lama tidak dapat ditutup dan ditandai secara eksplisit.

## IMPLEMENTASI

- Branch paralel dibuat dari `0631a3d8...`: `work/stage4d-account-lifecycle-20261122`.
- Backup test Stage 4C dibuat sebelum koreksi:
  `docs/backup/stage4c-teacher-subject-assignment-2026-11-22/TeacherSubjectAssignmentManagementTest.php.before-ci-fixture-fix`
- Test Stage 4C diperbaiki pada commit `5b981d726cbeaecbcd1a374bbc5e9ae66f70a9fa`.
- Workflow isolated runtime dibackup di:
  `docs/backup/stage4d-account-lifecycle-2026-11-22/wa01-alternative-isolated-runtime.yml.before-branch-enable`
- Workflow diberi trigger branch paralel pada commit `886f6edf30d1582b639379eb118efa25a0ef28be`.
- Tidak ada perubahan migration, database produksi, VPS, Android, atau ZIP baseline.

## PENGUJIAN

- Run lama SHA `3fbf3b4a...`: lint PASS; test sebelum Stage 4C PASS; Stage 4C feature test FAIL (2 dari 7).
- Run untuk perbaikan Stage 4C SHA `886f6edf...`: IN_PROGRESS saat laporan disusun; belum boleh disebut lulus.
- Belum ada browser E2E/UAT.
- Stage 4D belum diimplementasikan dan belum diuji.

## STATUS

- Stage 4B: PARTIAL; menunggu hasil CI MySQL 8 yang mencakup migration rollback dan concurrency.
- Stage 4C: IMPLEMENTASI ADA, tetapi belum lulus gate karena test feature gagal pada run sebelumnya; test setup telah diperbaiki dan menunggu CI ulang.
- Stage 4D: AUDIT AWAL selesai; implementasi belum dimulai.
- Stage 4E–4G: belum diaudit/diimplementasikan pada pekerjaan ini.
- Gate minimum Admin: BELUM LULUS.
- Web Guru: BELUM DIMULAI.

## BACKLOG / LANGKAH BERIKUTNYA
1. Tunggu dan periksa CI SHA `886f6edf...`; cocokkan SHA checkout dengan SHA laporan.
2. Jika Stage 4C test lulus, lanjutkan Stage 4D akun Guru/Siswa dengan SoftDeletes yang benar-benar terintegrasi pada autentikasi dan audit.
3. Rancang skema histori akademik sebelum memigrasikan pivot lama; jangan menebak keanggotaan aktif.
4. Audit Stage 4E–4G dari source aktual dan definisi resmi yang ada.
5. Gerbang minimum Admin tetap memerlukan pembuktian batas CBT, tenant isolation, akun Guru/Siswa, periode/rombel, penugasan mapel, ruang/Proktor, serta MySQL 8 disposable.
6. Setelah gate lulus, mulai Web Guru segera; pekerjaan administratif minor tetap backlog.
