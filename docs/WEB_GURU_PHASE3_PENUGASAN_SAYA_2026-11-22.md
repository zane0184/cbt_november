# PHASE 3 — IMPLEMENTASI HALAMAN PENUGASAN SAYA
Tanggal: 22 November 2026
Branch: `work/web-guru-phase1-audit-implementation-20261122`
Source HEAD untuk fitur ini: `8a0d30caa67e431badc342d8a0cf9cabcca6abf0`
Acuan: Master Spesifikasi Web Guru, bagian Penugasan Saya dan UX.

## 1. AUDIT
- Sebelum fase ini, data assignment sudah ditampilkan di Dashboard dan digabung pada halaman Pembelajaran & Kurikulum.
- Tidak tersedia route/menu khusus `/guru/penugasan`.
- `TeacherPortalDataService::learning()` sudah mengembalikan assignment tenant-local, subject, rombel, kelas/fase, tahun ajaran, semester, periode, status dan jumlah siswa.
- Service tersebut juga menghitung `active_assignment_count` berdasarkan status dan tanggal berlaku.
- Data assignment dikelola Admin; Guru hanya boleh melihat. Fase ini tidak menambah aksi edit/aktif/nonaktif assignment.

## 2. DESAIN
- Tambahkan halaman read-only khusus `/guru/penugasan`.
- Gunakan kembali `TeacherPortalDataService::learning()`; tidak membuat query atau tabel assignment kedua.
- Tampilkan periode akademik, jumlah assignment, jumlah aktif, mapel, fase/rombel/kelas, tahun, semester, periode, jumlah siswa, dan status.
- Tampilkan riwayat assignment yang berstatus nonaktif/belum berlaku/kedaluwarsa sesuai data service.
- Tambahkan tautan di sidebar desktop, menu mobile, dan CTA Dashboard.
- Boundary tetap `auth + tenant + license + EnsureTeacherPortalAccess` dan `TeacherPortalComponent::boot()`; Guru tidak mendapat hak mengubah master assignment.

## 3. IMPLEMENTASI
File baru:
- `backend-laravel/app/Livewire/Teacher/TeacherAssignmentsPage.php`
- `backend-laravel/resources/views/livewire/teacher/portal-assignments.blade.php`

File diubah:
- `backend-laravel/routes/teacher.php`: route GET `/guru/penugasan`, name `teacher.assignments`.
- `backend-laravel/resources/views/layouts/teacher.blade.php`: link sidebar desktop dan menu mobile.
- `backend-laravel/resources/views/livewire/teacher/portal-dashboard.blade.php`: CTA “Lihat Penugasan” diarahkan ke halaman baru.
- `backend-laravel/tests/Feature/TeacherPortalFoundationTest.php`: regression test HTTP untuk route dan data assignment.

Backup file sebelum edit, diverifikasi byte-identical dengan source baseline Agent 3:
- `backend-laravel/backup_before_patch/agent3-phase1-20261122/teacher.php.before-assignments-page` — blob `63eac1edee6f6b374fd1f4786a0cfc129099dbde`.
- `backend-laravel/backup_before_patch/agent3-phase1-20261122/teacher.blade.php.before-assignments-page` — blob `20fe7eb4ed0e31c89ff8a8ab5812043cc8967559`.
- `backend-laravel/backup_before_patch/agent3-phase1-20261122/portal-dashboard.blade.php.before-assignments-page` — blob `ba07cdfb30145205ff48c683b7de2512bbcc03bc`.
- `backend-laravel/backup_before_patch/agent3-phase1-20261122/TeacherPortalFoundationTest.php.before-assignments-page` — blob `dc82958d8ac9557859b471e551ca456b8a83e774`.

Commit source:
- Component `df3e1c58567c992ad2f8b36391f7ee23f90da816`.
- View `c412659709d5ba40102855c7b7fca0f06bb8574d`.
- Route `e3c1aceee36798a2b96d930674fd4d21b2ee47cc`.
- Layout `62d446c0fb7b44a27873b0f75289cf88301e0698`.
- Dashboard CTA `0bf75c0f8e6b2fc96fedad81154d87f3374352cf`.
- Regression test `8a0d30caa67e431badc342d8a0cf9cabcca6abf0`.

## 4. PENGUJIAN
Pemeriksaan statis setelah commit:
- Route mengarah ke component yang benar: PASS statis.
- Component memakai namespace `App\\Livewire\\Teacher` dan service existing: PASS statis.
- View file tersedia dan merender data assignment: PASS statis.
- Link desktop dan mobile ditemukan: PASS statis.
- CTA dashboard mengarah ke `teacher.assignments`: PASS statis.
- Regression test route ditemukan: PASS statis.
- Backup 4 file yang diubah: byte-identical / SHA MATCH.

GitHub Actions:
- Run #106 / ID `45044717360`, exact source/test SHA `8a0d30caa67e431badc342d8a0cf9cabcca6abf0`: conclusion failure, tetapi job `teacher-portal-tests` memiliki `steps=null`, `logs_url=null`; tidak ada runner/step/log.
- Run #97–#106 juga gagal sebelum step; pola sama.
- PHPUnit, PHP lint, route:list, MySQL 8/InnoDB dan browser E2E belum terbukti berjalan. Status: **CI BLOCKED / NOT EXECUTED**, bukan test PASS dan bukan bukti kode gagal.

## 5. STATUS
Halaman Penugasan Saya **sudah dibuat pada branch kerja**, tetapi belum runtime verified.
Tidak ada perubahan policy, migration, database, API, Agent 1/2/4, main, merge atau deployment.
