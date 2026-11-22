# Audit Lanjutan Portal Web Guru — 23 November 2026

## Identitas dan batas audit
- Repository: `zane0184/cbt_november`
- Branch: `work/web-guru-phase1-audit-implementation-20261122`
- HEAD saat audit: `5509e263f5b63d823ab728f2a191b16f901b7d9c`
- Source aplikasi terakhir: `b056e8289805cb71f492e9997288a715e460ce70`
- Perbandingan b056e828... ke HEAD branch: 2 commit di depan, 0 di belakang; hanya menambah dua dokumen audit/checkpoint sebelumnya. Tidak ada perubahan source aplikasi.
- Lingkup: Portal Web Guru saja. ZIP baseline, Library/Pustaka lama, Portal Super Admin, dan source Android tidak diperiksa.
- Tidak ada perubahan kode, migration, database, merge, atau deployment pada tahap ini.

## AUDIT

### 1. Dashboard Guru
**Status: fondasi tersedia; belum merupakan dashboard operasional lengkap.**
- Menampilkan identitas Guru/madrasah, periode akademik, jumlah mapel/rombel/siswa/penugasan, aktivitas asesmen, dan penugasan aktif.
- Agenda dan notifikasi masih berupa keadaan kosong: “Belum ada agenda terhubung”.
- Pengujian fondasi yang sudah ada mencakup dashboard dan validasi hitungan siswa; belum membuktikan seluruh aktivitas dan agenda.

### 2. Penugasan
**Status: tampilan dan pembatasan data tersedia.**
- Guru hanya melihat penugasan dan riwayat; penetapan/perubahan/pencabutan tetap kewenangan Admin Madrasah.
- Data dipagari tenant dan relasi akademik; status aktif dihitung berdasarkan tanggal server.
- Test fondasi mencakup halaman penugasan, penugasan kedaluwarsa, dan isolasi data tenant.

### 3. Pembelajaran dan Kurikulum
**Status: BELUM LENGKAP.**
- Periode akademik, penugasan, versi kurikulum terdaftar, serta referensi CP/TP/ATP/KBC yang tersimpan dapat ditampilkan.
- Source secara eksplisit menyatakan Pembelajaran Mendalam belum terintegrasi dan referensi delapan dimensi profil lulusan belum tersedia.
- Panca Cinta saat ini ditampilkan sebagai daftar kerangka; pemetaan data rinci ke lima unsur belum tervalidasi.
- Provenance/sumber resmi CP belum tersimpan/terverifikasi.
- Jadwal pembelajaran belum memiliki model sumber data dan halaman menampilkan placeholder.
- Guru belum dapat mengubah referensi kurikulum dari halaman ini. Ini bukan sekadar masalah pengujian; kemampuan tersebut memang belum ada pada komponen yang diperiksa.

### 4. Profil Guru
**Status: fungsi kontak dasar tersedia.**
- Profil menampilkan identitas kepegawaian dan kontak.
- Satu-satunya field yang dapat diubah melalui komponen ini adalah nomor telepon.
- Penyimpanan menggunakan transaksi, row lock, dan AuditService.
- Test yang diperiksa menguji pembaruan nomor kontak dan audit. Status kepegawaian aktif/nonaktif tersendiri tidak tersedia pada skema yang dipakai halaman.

### 5. Bank Soal
**Status: authoring dasar tersedia; kelengkapan fitur belum tercapai.**
- Editor mendukung MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, dan ESSAY.
- SHORT_ANSWER sengaja diblokir sampai accepted-answer authoring, persistence, snapshot, dan scoring benar-benar didukung. Jangan mengaktifkannya sebelum seluruh rantai tersebut lulus tes.
- Pemetaan pada editor yang diperiksa menyediakan CP dan TP; field/pilihan ATP tidak tampak pada alur authoring ini.
- Tampilan editor menggunakan textarea dan input teks; tidak ditemukan kontrol unggah media, editor persamaan, atau kontrol RTL khusus.
- API backend memiliki endpoint import dan media, tetapi Portal Web Guru yang diperiksa hanya menyediakan unduhan template. Komponen QuestionBank tidak memiliki method import dan view-nya tidak memiliki input file.
- Versi soal terbit dibuat immutable; edit versi terbit membuat draft versi baru. Publish memiliki otorisasi dan audit log.
- Test fondasi yang diperiksa membuktikan SHORT_ANSWER ditolak, akses dasar, dan batas tenant/role; belum membuktikan seluruh siklus create/edit/publish/delete untuk semua tipe soal.

### 6. Asesmen dan Exam Builder
**Status: alur dasar tertulis di source; end-to-end belum terbukti.**
- UI dan komponen menyediakan draft ujian, mapel/semester/rombel, durasi, batas attempt, jadwal, pengacakan, pemilihan versi soal, serta instruksi.
- Guru dibatasi pada penugasan mapel/semester/rombel; penugasan global seluruh siswa ditolak untuk Guru.
- Penyimpanan membuat snapshot melalui ExamSnapshotService. Service bersama tersebut tidak diubah dalam audit ini.
- Aksi Terbitkan & Kunci mengubah status ke PUBLISHED, mengisi locked_at/published_at, dan otomatis membuat token jika belum ada. UI menampilkan token setelah terbit, termasuk sebelum jadwal mulai; token dapat dirotasi melalui aksi terotorisasi.
- Belum ditemukan bukti tes end-to-end untuk save → snapshot → publish → token → jadwal → roster peserta → pembatasan mulai ujian. Test fondasi yang diperiksa baru membuktikan halaman Exam Builder dapat dibuka dan aksesnya dibatasi.

### 7. Hasil, penilaian, finalisasi, dan reopen
**Status: operasi utama tersedia secara statis; keamanan lifecycle perlu diperkuat dan diuji.**
- Route daftar hasil dan detail hasil tersedia.
- ResultDetail memeriksa hubungan attempt-exam, menggunakan policy, memvalidasi current score pointer, dan mengunci Attempt → pointer → score → question score saat penyimpanan/finalisasi.
- Finalisasi memeriksa status attempt dan pending manual review. Reopen mewajibkan alasan; ScoringService membuat scoring version baru, memperbarui current pointer, mencatat audit, dan menginvalidasi cache.
- Finalisasi massal memiliki preflight, row lock, pemeriksaan pointer, policy per attempt, dan invalidasi cache.
- Publikasi/penarikan publikasi hasil menggunakan transaksi, row lock, reauthorization, audit, dan invalidasi cache.
- **Celah navigasi:** menu Penilaian & Hasil masih berupa elemen disabled, bukan tautan ke route hasil.
- **Celah otorisasi Livewire:** ResultList, ResultDetail, AnalyticsDashboard, dan ItemAnalysis memeriksa policy saat mount, tetapi tidak memiliki boot boundary setara TeacherPortalComponent untuk setiap request Livewire. Tindakan sensitif tertentu memang mengotorisasi ulang, tetapi render/hydration halaman hasil dan analitik belum membuktikan reauthorization setelah role/permission/assignment dicabut.
- **Celah invariant role:** route hasil berada pada grup auth/tenant/license umum, bukan middleware EnsureTeacherPortalAccess. ReportPolicy dan ExamAttemptPolicy menerima akun yang memiliki role GURU ditambah role lain selama permission dan penugasannya cocok; tidak menegakkan invariant satu-satunya role GURU seperti halaman Dashboard/Bank Soal/Exam Builder. Ini ketidakkonsistenan batas akses yang harus ditutup dengan tes negatif. Belum diklaim sebagai exploit runtime terkonfirmasi.
- File test fondasi yang diperiksa tidak memuat skenario ResultList/ResultDetail untuk revoke role, permission, assignment, hydration, finalisasi massal, idempotensi, kegagalan parsial, dan reopen.

### 8. Analitik dan pelaporan — BLOCKER INTEGRITAS
**Status: belum benar untuk histori regrade/reopen; beberapa endpoint juga memiliki cacat source.**
- ExamAnalyticsService summary/distribution mengambil semua score FINALIZED tanpa current pointer.
- ItemAnalysisService juga mengambil seluruh score FINALIZED dan mengelompokkan statistik butir berdasarkan student_id, bukan pasangan attempt_id + scoring_version aktif.
- StudentAnalyticsService, RombelAnalyticsService, dan SubjectAnalyticsService juga mengambil score FINALIZED tanpa current pointer; riwayat versi lama berpotensi ikut dihitung.
- ExamSummaryReportService menghitung agregat langsung dari semua ExamAttemptScore, bukan hanya score aktif yang ditunjuk pointer.
- QuestionReportService mengagregasi ExamQuestionScore tanpa pembatasan attempt/scoring version aktif. Selain itu closure map-nya memakai variabel $exam tanpa menangkap variabel tersebut ke closure.
- SubjectAnalyticsService memiliki closure map yang memakai variabel $subject tanpa menangkap variabel tersebut ke closure.
- Dua masalah closure terakhir adalah cacat source yang secara statis dapat menyebabkan endpoint question report dan subject analytics gagal/berperilaku salah saat dieksekusi; runtime belum dijalankan pada audit ini.
- Report result list dan Excel/PDF menggunakan relasi ExamAttempt::score() yang sudah mengikuti current pointer; jalur ini berbeda dari service agregat di atas.
- Cache analitik memiliki invalidasi pada finalisasi dan reopen melalui service yang diperiksa, tetapi cache tidak memperbaiki query yang memilih score historis.
- Menu Analytics & Ekspor masih disabled. Route halaman analitik tersedia, tetapi tidak tersambung dari navigasi Guru.
- Test ItemAnalysisTest yang diperiksa hanya memiliki dua test dan fixture score FINALIZED tidak membuat current pointer. Belum ada regression test pada file tersebut untuk histori versi lama vs current version.

### 9. CI dan bukti pengujian
- GitHub Actions run `45057135828`, job `teacher-portal-tests`: SUCCESS pada exact source commit `b056e828...`; bukti sebelumnya mencatat 45 test dan 239 assertion lulus, PHP 8.3, MySQL 8.0.46, serta 80/80 tabel dasar InnoDB.
- Run `45056968233` juga memiliki job berstatus completed/success pada metadata yang diperiksa.
- HEAD branch sekarang `5509e263...` hanya menambah dua dokumen; combined status dan workflow runs untuk HEAD dokumentasi tidak menunjukkan CI baru.
- CI fondasi bukan bukti seluruh fitur Web Guru, seluruh alur grading, analytics current pointer, keamanan Livewire hasil, import/media UI, atau integrasi kurikulum telah lulus.
- Tidak ada PHPUnit lokal atau runtime web yang dijalankan pada audit lanjutan ini.

## DESAIN — urutan penyelesaian terarah

### P0 — keamanan hasil dan analitik
1. Tambahkan reauthorization pada setiap request Livewire untuk halaman hasil/analitik tanpa menghilangkan akses KAMAD yang memang diizinkan policy.
2. Tegakkan invariant role yang konsisten untuk Guru pada result/detail/analytics routes; uji akun GURU tunggal, multi-role, role/permission revoke, assignment revoke, direct URL, hydration, dan action.
3. Buat satu definisi populasi score aktif berbasis current pointer dengan kecocokan attempt_id, tenant_id, current_score_id, dan scoring_version.
4. Terapkan definisi tersebut pada ExamAnalyticsService, ItemAnalysisService, StudentAnalyticsService, RombelAnalyticsService, SubjectAnalyticsService, ExamSummaryReportService, dan QuestionReportService sesuai semantik masing-masing.
5. Perbaiki closure scope pada QuestionReportService dan SubjectAnalyticsService.
6. Tambahkan regression tests: versi lama dan baru sama-sama FINALIZED tetapi hanya versi pointer yang dihitung; beberapa attempt siswa yang sama; skor butir harus cocok dengan attempt dan scoring_version; pointer hilang/tidak valid harus fail closed.

### P1 — alur Guru dan kelengkapan UI
1. Sambungkan menu Penilaian & Hasil dan Analytics & Ekspor ke route yang terotorisasi setelah P0 lulus.
2. Lengkapi alur impor soal di Web Guru, atau tandai jelas bahwa import hanya tersedia lewat API; jangan menyamakan unduhan template dengan fitur import.
3. Rancang integrasi media pada authoring Web Guru dengan validasi tenant dan versi soal.
4. Putuskan dan implementasikan ATP mapping, editor persamaan/RTL, dan jadwal pembelajaran sesuai kontrak akademik; jangan membuat data contoh.
5. Lengkapi acceptance tests untuk create/edit/publish question, snapshot, peserta, token, jadwal, grading, finalisasi, reopen, export, dan navigasi.

## IMPLEMENTASI
- Tidak ada file source aplikasi, test, konfigurasi, migration, atau database yang diubah.
- Tidak ada file Agent 2/Agent 4 atau komponen bersama yang diubah.
- Tidak ada merge, deployment, atau perubahan branch source.
- Laporan ini dibuat sebagai dokumen baru setelah path target diperiksa tidak ada; tidak ada file lama yang ditimpa dan tidak diperlukan backup file target.

## PENGUJIAN
- Branch target ditemukan dan HEAD diverifikasi melalui perbandingan Git.
- Perbandingan source commit b056e828... ke HEAD 5509e263... menunjukkan tepat dua file dokumentasi baru; source aplikasi tidak berubah.
- Pemeriksaan source dilakukan statis pada route, policy, komponen Livewire, service, model, view, dan test yang disebut dalam laporan.
- CI yang sudah ada tetap SUCCESS pada commit source b056e828...; tidak ada CI baru untuk source karena tidak ada perubahan source.
- Regression test current pointer, closure runtime, revoke Livewire, grading/reopen, media/import UI, dan jadwal belum dijalankan.

## STATUS
**Portal Web Guru BELUM LENGKAP dan BELUM BOLEH dinyatakan siap produksi.** Prioritas pertama adalah menutup celah otorisasi halaman hasil/analitik dan integritas current-score pada seluruh jalur analytics/reporting. Setelah itu baru menyambungkan navigasi dan menyelesaikan UI/acceptance tests.
