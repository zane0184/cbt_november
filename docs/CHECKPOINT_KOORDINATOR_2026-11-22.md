# CHECKPOINT CBT MADRASAH — 22 NOVEMBER 2026

## Posisi kerja
- Repository: `zane0184/cbt_november`
- Branch kerja: `work/coordinator-web-admin-audit-20261122`
- Scope aktif: Web Admin P0 dan konsistensi role/penugasan Proktor.
- Branch `main` tidak diubah. Tidak ada merge, PR, atau deployment.

## Keputusan desain tetap
- Portal aktif hanya ADMIN_MADRASAH, GURU, SISWA.
- KAMAD adalah atribut `tenants.principal_name`, bukan role/portal.
- OPERATOR bukan role; fungsi administrasinya berada pada Admin Madrasah.
- PROKTOR adalah penugasan sementara pada akun Guru, bukan role permanen.
- Admin tidak otomatis mendapat akses bank soal, authoring, answer key, jawaban, atau nilai.
- Guru mengelola ujian sesuai penugasan dan tanpa approval Admin.
- Android siswa native; pengerjaan Android ditunda sampai jadwal proyek yang disepakati.
- Tidak ada migration produksi tanpa backup, clone test, rollback rehearsal, preflight, dan persetujuan eksplisit.

## AUDIT terakhir
- Source aktual branch diperiksa: RolePermissionBootstrapper, ProctorAssignmentManagement, ExamProctorAssignmentService, ExamPolicy, ExamAttemptPolicy, User, TeacherAccess, dan ProctorAssignmentSecurityTest.
- RolePermissionBootstrapper memberi `exam.monitor` kepada GURU; ExamPolicy.monitor tetap membatasi monitoring berdasarkan cakupan mengajar atau assignment Proktor aktif yang spesifik exam dan tenant.
- ExamAttemptPolicy tetap membatasi akses jawaban/nilai Guru melalui TeacherAccess dan permission report/grade; assignment Proktor tidak digunakan sebagai jalur otorisasi detail jawaban/nilai.
- UI kandidat Proktor sebelumnya belum memfilter exact-one role meskipun service sudah menolaknya. Patch koreksi telah dibuat.

## IMPLEMENTASI terakhir
File source:
- `backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php`
  - Query pemilihan dan daftar kandidat kini mensyaratkan tepat satu role GURU dan TeacherProfile aktif pada tenant yang sama.
- `backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php`
  - Ditambah test negatif untuk Guru multi-role agar tidak tampil sebagai kandidat.

Backup:
- `docs/backup/coordinator-web-admin-2026-11-22/ProctorAssignmentManagement.php.before-exact-guru-role-ui-filter`
- `docs/backup/coordinator-web-admin-2026-11-22/ProctorAssignmentSecurityTest.php.before-exact-guru-role-ui-filter`
- `docs/backup/coordinator-web-admin-2026-11-22/AUDIT_KOORDINATOR_WEB_ADMIN_2026-11-22.md.before-exact-guru-role-ui-filter`

Dokumentasi:
- Addendum dicatat pada `docs/AUDIT_KOORDINATOR_WEB_ADMIN_2026-11-22.md`.

## PENGUJIAN
- Fetch ulang source/test branch: dilakukan.
- Backup source: blob SHA sebelum patch sama dengan blob backup.
- PHPUnit, PHP lint, MySQL 8 disposable, concurrency, browser/E2E: belum dijalankan.
- GitHub Actions sebelumnya gagal sebelum steps dan tidak memberi log; status test tetap NOT VERIFIED.
- Tidak ada runtime test PASS yang dapat diklaim.

## Gerbang berikutnya
1. Pastikan workflow runner dapat mengeksekusi job dan menampilkan log.
2. Jalankan PHP lint dan PHPUnit terkait pada PHP 8.3 + MySQL 8 disposable.
3. Tambah/validasi tes eksplisit bahwa Proktor tidak dapat membaca detail jawaban, answer key, nilai, melakukan grading, atau mengubah ujian.
4. Selesaikan audit seluruh route/action Admin dan tenant isolation sebelum acceptance Web Admin.
5. Jangan mulai portal Guru/Siswa sebelum Web Admin diterima.
6. Jangan menjalankan migration atau tindakan pada VPS aktif.

## Status
WEB ADMIN: NOT ACCEPTED / P0 OPEN.
DEVICE LEASE/FENCING: belum boleh dianggap siap produksi; perlu audit integrasi dan tes multi-device/race.
STAGING: BLOCKED sampai ada bukti preflight kedua VPS.
DISTRIBUSI: ZIP final belum dibuat dan belum siap dinyatakan.
