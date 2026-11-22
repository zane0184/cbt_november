# Web Guru — Audit dan Hardening Lanjutan
Tanggal: 23 November 2026
Branch: `work/web-guru-phase1-audit-implementation-20261122`

## AUDIT
1. CI fondasi pada commit `8bc4395d795c23989ac378e3b166757061afae91` sukses. Run `45056543021` menuntaskan PHP lint, route registration, PHPUnit, verifikasi InnoDB, dan cleanup disposable database.
2. `TeacherPortalAccess` menjadi batas utama portal: identitas tenant berasal dari user terautentikasi, role harus tepat satu GURU, dan profil guru tenant-local diverifikasi. `TeacherAccess` membatasi akses akademik melalui penugasan aktif.
3. Exam Builder pada Livewire memvalidasi Question dan QuestionVersion berstatus PUBLISHED sebelum membuat/memperbarui snapshot. Namun `ExamSnapshotService` pada branch Guru masih belum mengulang validasi status tersebut. Ini adalah invariant lintas-caller dan perlu patch bersama pemilik service Agent 2; jangan menganggap validasi UI sebagai pengganti validasi service.
4. `ResultDetail` sebelumnya mengirim `$e->getMessage()` langsung ke browser pada kegagalan simpan nilai, finalisasi, dan buka kembali. Pesan exception dapat memuat detail internal/SQL. Ini diperbaiki dengan logging server-side melalui `report($e)` dan pesan generik untuk pengguna.
5. Route `routes/teacher.php` saat ini menampilkan dashboard, penugasan, pembelajaran/kurikulum, profil, bank soal, dan asesmen. Komponen hasil, analitik, dan analisis butir ada di source, tetapi route/akses dan alur UAT lengkapnya masih perlu audit tersendiri.
6. Komponen Bank Soal dan Exam Builder masih berada di namespace `App\\Livewire\\Admin` walaupun dipakai portal Guru. Ini masalah penataan arsitektur, bukan alasan memindahkan class sebelum audit route, policy, dan kompatibilitas selesai.

## DESAIN
- Pertahankan pembatasan exact-one role GURU, permission, tenant, dan assignment pada setiap route serta aksi Livewire.
- Validasi status PUBLISHED wajib ditegakkan di `ExamSnapshotService` untuk semua caller. Pemilik service Agent 2 perlu memasukkan invariant dan regression test lintas-caller; Agent 3 tidak mengubah file bersama tanpa koordinasi.
- Error internal penilaian dicatat di server, sedangkan browser hanya menerima pesan aman dan tidak memuat detail exception.
- Lanjutkan audit route hasil/analitik, penilaian ESSAY, publikasi nilai, dan penguncian lingkup guru sebelum menyatakan Web Guru lengkap.

## IMPLEMENTASI
File aplikasi diubah:
- `backend-laravel/app/Livewire/Teacher/ResultDetail.php`
  - saveScore, finalize, reopen sekarang memanggil `report($e)` dan menampilkan pesan generik.
Backup:
- `backend-laravel/docs/backup/agent3-web-guru-urgent-20261122/ResultDetail.php.before-error-disclosure-hardening-2026-11-23`
- Backup dibuat sebelum patch; commit backup `1d6373d1de8d45db5ce5f77b0de38d88498bcebc`.
Patch commit: `52882e7cbf1172d81f8eb878b7f9ceb33c56dc3a`.

## PENGUJIAN
- CI fondasi commit `8bc4395d795c23989ac378e3b166757061afae91`: PASS, run `45056543021`.
- CI untuk backup commit `1d6373d1de8d45db5ce5f77b0de38d88498bcebc`: run `45056884241` sedang berjalan pada pemeriksaan terakhir.
- CI untuk patch `52882e7cbf1172d81f8eb878b7f9ceb33c56dc3a`: run `45056916313` masih pending pada pemeriksaan terakhir.
- Belum ada bukti pengujian browser/UAT, MySQL concurrency, integrasi penuh, atau produksi untuk perubahan error handling ini.
- Tidak ada migration/DB produksi, merge, atau deployment.

## BLOCKER / PEKERJAAN LANJUT
1. Tunggu dan periksa CI pada patch terbaru.
2. Tambahkan regression test terarah untuk memastikan exception grading tidak bocor ke browser.
3. Koordinasikan invariant PUBLISHED di ExamSnapshotService dengan Agent 2.
4. Audit dan uji route hasil, detail penilaian, analitik, analisis butir, dan penugasan Proktor sementara sesuai keputusan portal.
5. Device lease/fencing tetap belum siap produksi sampai integrasi multi-device dan MySQL 8/InnoDB terbukti.
