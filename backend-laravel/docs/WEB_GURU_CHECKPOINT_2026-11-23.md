# Checkpoint Web Guru — 23 November 2026

## Identitas source
- Repository: `zane0184/cbt_november`
- Branch: `work/web-guru-phase1-audit-implementation-20261122`
- HEAD checkpoint: `ee6a379af9a74541cd9c94291d4bb15b031cd5b6`
- HEAD source aplikasi sebelum dokumentasi posisi awal: `b056e8289805cb71f492e9997288a715e460ce70`
- Commit checkpoint ini hanya menambahkan dokumentasi; tidak ada source aplikasi yang berubah.

## Status tahap
**AUDIT POSISI AWAL SELESAI — IMPLEMENTASI SOURCE BELUM DIMULAI.**

### Keputusan lingkup
- Agent ini hanya menangani Portal Web Guru.
- Portal Super Admin ditunda sampai Juni 2027.
- Semua pekerjaan Android ditunda sampai Juni 2027.
- Tidak ada perubahan pada source Agent 2, Agent 4, atau komponen bersama tanpa koordinasi.
- Tidak ada merge, deployment, atau migration produksi.

### Bukti branch dan CI
- Branch target ditemukan dan pada pemeriksaan awal identik dengan commit handover `b056e828...`.
- CI run `45057135828` untuk exact commit `b056e828...`: SUCCESS.
- Log CI: 45 tests passed, 239 assertions.
- Runtime CI: PHP 8.3, MySQL 8.0.46, 80/80 base tables InnoDB.
- CI tersebut menguji fondasi keamanan/data scope Web Guru; bukan seluruh acceptance Web Guru dan bukan uji current-score analytics.

### Temuan blocker yang masih terbuka
1. `ExamAnalyticsService` menghitung seluruh score FINALIZED tanpa memastikan current pointer.
2. `ItemAnalysisService` melakukan hal yang sama dan mengaitkan agregat butir dengan student_id, bukan attempt_id + scoring_version aktif.
3. Fixture `ItemAnalysisTest` belum membentuk `ExamAttemptScoreCurrent`.
4. Route hasil/detail/analitik ada, tetapi navigasi Guru masih menampilkan hasil dan analytics sebagai menu nonaktif.
5. Policy halaman hasil perlu diuji terhadap invariant tepat satu role GURU. `TeacherPortalAccess` menegakkan role tunggal, sementara policy hasil yang diperiksa menggunakan `hasRole('GURU')` dan TeacherAccess tanpa menerapkan pemeriksaan role tunggal secara langsung. Ini temuan review, belum exploit terkonfirmasi.

### Prioritas berikutnya
**P0 — Audit dan regression test otorisasi halaman hasil/detail/analitik.**
- Uji single-role GURU.
- Uji multi-role dan perubahan role pada sesi yang masih aktif.
- Uji akses URL langsung dan Livewire hydration/action.
- Uji tenant mismatch dan penugasan yang dicabut.
- Tentukan guard server-side konsisten sebelum mengubah source.

**P1 — Current-score analytics integrity.**
- Join score ke current pointer dengan kecocokan attempt_id, tenant_id, current_score_id, dan scoring_version.
- Item analysis harus menggunakan pasangan attempt_id + scoring_version aktif.
- Tambahkan tes score versi lama dan versi baru yang sama-sama FINALIZED tetapi hanya versi baru ditunjuk pointer.
- Pastikan histori tetap tersimpan dan tidak dihitung sebagai current.
- Uji beberapa attempt siswa yang sama bila kebijakan attempt mengizinkan.

**P2 — Result workflow dan navigasi.**
- Audit ResultList dan ResultDetail.
- Uji finalisasi massal, locking, idempotensi, kegagalan parsial, reopen, histori nilai, dan cache invalidation.
- Sambungkan menu/tautan hasil, detail, analitik, dan analisis butir setelah otorisasi lulus.

## Prosedur sebelum perubahan source berikutnya
1. Bandingkan branch HEAD terbaru; jangan memakai SHA checkpoint sebagai asumsi jika branch telah bergerak.
2. Periksa diff terbaru dari agent lain.
3. Baca semua dependency dan test terkait.
4. Buat backup byte-exact file yang akan diubah dan catat checksum.
5. Implementasi patch kecil pada branch Web Guru saja.
6. Jalankan tes terisolasi; catat PASS/FAIL/NOT RUN.
7. Update laporan dan checkpoint.
8. Jangan merge/deploy/migrasi produksi tanpa persetujuan.

## File dokumentasi
- `backend-laravel/docs/WEB_GURU_INITIAL_POSITION_AUDIT_2026-11-23.md`
- `backend-laravel/docs/WEB_GURU_CHECKPOINT_2026-11-23.md`

## Status produksi
**NOT READY.** CI fondasi lulus tidak membuktikan current-score analytics, seluruh alur penilaian, otorisasi end-to-end, stabilitas, dan validasi operasional sudah selesai.
