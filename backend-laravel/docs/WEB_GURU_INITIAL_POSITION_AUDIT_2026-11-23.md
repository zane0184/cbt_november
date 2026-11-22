# Laporan Posisi Awal Agent Pengganti — Web Guru
Tanggal pemeriksaan: 23 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/web-guru-phase1-audit-implementation-20261122`
Status: AUDIT AWAL SELESAI — BELUM ADA PERUBAHAN SOURCE APLIKASI

## Ringkasan keputusan kerja
- Lingkup agent ini hanya Portal Web Guru.
- Portal Super Admin dan seluruh pengembangan Android ditunda sampai Juni 2027.
- Source branch GitHub aktif menjadi acuan; ZIP baseline, Library, dan source lama tidak menggantikan branch ini.
- Tidak ada coding, migration, database mutation, merge, atau deployment pada pemeriksaan posisi awal ini.

## AUDIT

### 1. Branch, HEAD, dan CI
- Branch pekerjaan ditemukan.
- Perbandingan ref branch terhadap HEAD yang tercatat pada handover menghasilkan `identical`, `ahead_by=0`, `behind_by=0`.
- HEAD terverifikasi: `b056e8289805cb71f492e9997288a715e460ce70`.
- CI run `45057135828` memeriksa checkout exact commit tersebut.
- Job `teacher-portal-tests`: SUCCESS.
- Log PHPUnit: 45 tests passed, 239 assertions.
- Lingkungan CI menggunakan PHP 8.3 dan MySQL 8.0.46; 80 tabel dasar diverifikasi seluruhnya menggunakan InnoDB.
- CI mencakup lint/route, fondasi keamanan dan cakupan data Web Guru, serta pemeriksaan InnoDB. Hasil ini bukan bukti semua alur Web Guru atau analitik telah lulus.
- Run `45056968233` juga menunjukkan job sukses pada pemeriksaan metadata/log yang tersedia.

### 2. Dokumen audit dan checkpoint
Dokumen branch yang ditemukan:
- `backend-laravel/docs/WEB_GURU_AUDIT_ANALYTICS_CURRENT_SCORE_2026-11-23.md`

Dokumen tersebut mengidentifikasi query analitik yang belum mengikuti current score pointer. Lokasi file checkpoint Web Guru terpisah belum berhasil diverifikasi melalui nama/path yang tersedia dalam handover; jangan menyimpulkan file tersebut tidak ada. Handover pemilik proyek dan addendum audit di atas menjadi dasar posisi awal yang dapat diverifikasi pada sesi ini.

### 3. Source Web Guru yang diperiksa
- `routes/teacher.php`
- `routes/web.php`
- `app/Livewire/Teacher/TeacherDashboard.php`
- `app/Livewire/Teacher/TeacherAssignmentsPage.php`
- `app/Livewire/Teacher/TeacherLearning.php`
- `app/Livewire/Teacher/TeacherProfilePage.php`
- `app/Livewire/Teacher/ResultList.php`
- `app/Livewire/Teacher/ResultDetail.php`
- `app/Livewire/Teacher/AnalyticsDashboard.php`
- `app/Livewire/Teacher/ItemAnalysis.php`
- `app/Livewire/Admin/QuestionBank.php`
- `app/Livewire/Admin/ExamManagement.php`
- `app/Support/Security/TeacherPortalAccess.php`
- `app/Support/Security/TeacherAccess.php`
- `app/Services/TeacherPortal/TeacherPortalDataService.php`
- `app/Services/Analytics/ExamAnalyticsService.php`
- `app/Services/Analytics/ItemAnalysisService.php`
- `app/Policies/ReportPolicy.php`
- `app/Policies/ExamAttemptPolicy.php`
- `app/Policies/ExamPolicy.php`
- `resources/views/layouts/teacher.blade.php`
- `tests/Feature/Analytics/ItemAnalysisTest.php`
- `tests/Feature/TeacherPortalFoundationTest.php`

### 4. Temuan audit
#### A. Integritas analitik — BLOCKER
- Model `ExamAttempt::score()` dan `resolvedCurrentScore()` menetapkan current pointer sebagai sumber kebenaran nilai aktif.
- `ExamAnalyticsService::getSummary()` dan `getDistribution()` masih mengambil seluruh `ExamAttemptScore` berstatus `FINALIZED` berdasarkan exam dan tenant, tanpa join ke `exam_attempt_score_current`.
- `ItemAnalysisService::getAnalysis()` melakukan hal serupa.
- Analisis butir mengelompokkan populasi dan skor berdasarkan `student_id`, bukan pasangan attempt dan scoring version yang aktif.
- Fixture `ItemAnalysisTest.php` belum membentuk `ExamAttemptScoreCurrent`.
- Risiko: histori score setelah reopen/regrade dapat ikut dihitung sebagai nilai aktif, menyebabkan statistik ganda/tidak akurat.

#### B. Otorisasi halaman hasil — REVIEW WAJIB
- Route hasil, detail, analitik, dan analisis butir terdaftar di `routes/web.php` dalam grup `auth`, `tenant`, dan `license`.
- Komponen hasil/analitik melakukan pemeriksaan policy pada mount dan/atau tindakan sensitif.
- `TeacherPortalAccess::profile()` mensyaratkan tepat satu role GURU, tenant terikat pada user terautentikasi, user aktif, dan satu profil Guru tenant-lokal.
- Beberapa policy hasil memakai `hasRole('GURU')` dan `TeacherAccess`, tetapi tidak secara langsung menerapkan invariant tepat satu role GURU.
- Komponen hasil/analitik tidak mewarisi `TeacherPortalComponent` yang memanggil `TeacherPortalAccess` setiap request Livewire.
- Ini merupakan ketidakkonsistenan batas otorisasi yang harus diuji untuk multi-role, role berubah ketika session masih aktif, direct URL, dan Livewire hydration. Belum dinyatakan sebagai exploit terkonfirmasi sebelum tes negatif dijalankan.

#### C. Navigasi hasil
- Route daftar hasil, detail, analytics, dan item analysis tersedia.
- Layout Guru masih menampilkan Penilaian & Hasil dan Analytics & Ekspor sebagai elemen nonaktif.
- Jalur navigasi dari Exam Builder ke hasil/analitik belum lengkap di menu Portal Guru.

#### D. Fondasi komponen lain
- Dashboard dan halaman penugasan memakai `TeacherPortalComponent` serta `TeacherPortalDataService`.
- `TeacherPortalDataService` membatasi penugasan dan relasi akademik berdasarkan tenant; statistik siswa memeriksa role SISWA yang tepat.
- Profil Guru hanya membuka perubahan nomor telepon, menggunakan transaksi dan row lock, serta mencatat AuditService.
- Bank Soal masih berada di namespace Admin; source memanggil TeacherPortalAccess dan TeacherAccess. Jangan pindahkan namespace sebelum audit seluruh pemanggil.
- Exam Builder masih berada di namespace Admin; route Guru menggunakannya dan source memiliki pemeriksaan Gate/TeacherAccess. Jangan pindahkan namespace sebelum audit menyeluruh.
- `ResultDetail` memiliki urutan locking Attempt → current pointer → score → question score pada save/finalize, serta reauthorization sesudah lock. Pertahankan pola ini sampai audit membuktikan pengganti lebih aman.

## DESAIN — Prioritas berikutnya
1. Audit dan tutup inkonsistensi otorisasi halaman hasil/detail/analitik: role tunggal GURU, tenant, penugasan, direct URL, Livewire hydration, dan role revocation. Tambahkan regression test negatif sebelum perubahan.
2. Perbaiki query summary/distribution agar hanya memakai score yang ditunjuk current pointer dengan kecocokan attempt_id, tenant_id, current_score_id, dan scoring_version.
3. Perbaiki item analysis agar populasi butir diikat ke attempt ID dan scoring version aktif, bukan hanya student_id.
4. Perbarui fixture dan tambahkan tes versi 1 dan versi 2 sama-sama FINALIZED tetapi hanya versi 2 menjadi current; histori versi 1 harus tetap tersimpan namun tidak dihitung.
5. Tambahkan skenario beberapa attempt untuk siswa yang sama jika aturan attempt mengizinkan.
6. Setelah integritas dan otorisasi lulus, sambungkan navigasi hasil/detail/analitik dan lanjutkan audit ResultList/ResultDetail.
7. Lanjutkan audit Dashboard, Penugasan, Pembelajaran/Kurikulum, Profil, Bank Soal, dan Exam Builder sesuai dependensi.

## IMPLEMENTASI
- Tidak ada file source aplikasi yang diubah.
- Tidak ada file Android atau Portal Super Admin yang diubah.
- Tidak ada migration atau database yang dijalankan.
- Tidak ada merge, deployment, atau perubahan branch source.
- Laporan posisi awal ini merupakan file dokumentasi baru. Path target sebelumnya diperiksa dan tidak ditemukan, sehingga tidak ada file lama yang ditimpa dan tidak diperlukan backup file target.

## PENGUJIAN
- Perbandingan branch terhadap HEAD handover: PASS (identical, 0 ahead, 0 behind).
- Branch target ditemukan: PASS.
- CI run `45057135828`: SUCCESS; 45 tests, 239 assertions; MySQL 8.0.46; 80/80 base tables InnoDB.
- Pemeriksaan source analytics: audit statis mengonfirmasi ketidaksesuaian current pointer; belum ada regression test current-pointer yang dijalankan.
- Pemeriksaan route/navigasi: route tersedia, menu hasil/analitik masih nonaktif.
- Pengujian multi-role pada halaman hasil, analitik current-pointer, regrade/reopen, dan multiple attempts: NOT RUN pada audit posisi awal.
- Tidak ada PHPUnit atau tes lokal yang dijalankan oleh agent pada tahap posisi awal.

## STATUS DAN GATE
**Posisi awal terverifikasi. Belum siap menyatakan Web Guru selesai atau siap produksi.**

Pekerjaan berikutnya dimulai dari audit dan regression test otorisasi hasil, lalu perbaikan current-score analytics. Device Lease/fencing, integrasi Android, stabilitas sinkronisasi, dan validasi operasional tetap tunduk pada gate proyek terpisah; penundaan Android sampai Juni 2027 tetap berlaku.
