# LAPORAN AUDIT READ-ONLY W0 — WEB ADMIN MADRASAH
Tanggal: 21 November 2026
Mode: READ-ONLY; dokumentasi saja
Status: AUDIT STATIS SELESAI SEBAGIAN — BASELINE RAW DAN RUNTIME BLOCKED

## 1. Ringkasan
Pemeriksaan dilanjutkan dari paket handover Web Admin. Lingkup sesi ini hanya Web Admin Madrasah. Portal Guru/Siswa, Android, Device Lease/Fencing, P0 database, VPS, migration produksi, dan deployment tidak disentuh.

Kesimpulan: source GitHub kandidat dapat diidentifikasi dan diaudit secara statis. Source branch kerja yang direkomendasikan sementara adalah recovery/source-preservation-20261121 @ 7fc45bf71ac1ebd375eba44fd57c983bf18d66e0. Branch ini 7 commit di depan fix/web-portal-usability-rbac-20261120 @ ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2, 0 commit di belakang; perbandingan menunjukkan tambahan lima file dokumentasi recovery, bukan perubahan source aplikasi. Rekomendasi ini hanya untuk working source Web Admin, bukan canonical seluruh CBT dan belum diverifikasi byte-identik dengan ZIP baseline.

## 2. AUDIT — Source, branch, PR, dan arsip
- Repository: zane0184/cbt_november.
- Default branch main @ 91dae257b2210ef89c90534185a3cbdd8979ffe0. Dokumen rekonsiliasi source mencatat tree 24 entri tanpa source Laravel aktif. Jangan gunakan main sebagai source build/deployment.
- PR #5: OPEN, DRAFT, UNMERGED; base fix/p0-96g-installer-private-db-config-20261120 @ 158227362eea82fc7ec7e9c232736596015739d2; head fix/web-portal-usability-rbac-20261120 @ ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2; 64 file, +14.945/-120.
- PR #6: OPEN, DRAFT, UNMERGED; spesifikasi Web Admin, base PR #5.
- PR #7: OPEN, DRAFT, UNMERGED; paket handover, base PR #6.
- Branch docs/source-canonical-design-closure-20261121 @ 59aa76ae60de396216e9754d200cb1b6d806e9e0 adalah enam commit di depan branch handover PR #7 dan memperbarui enam dokumen (audit rekonsiliasi, checkpoint, matriks audit, matriks tes, register keputusan, spesifikasi). Branch ini belum menjadi PR dan belum di-merge.
- PR #7 masih menunjuk branch handover sebelumnya; dokumen terbaru ada pada branch docs/source-canonical-design-closure-20261121.
- PR #5 head saat ini belum memiliki bukti CI PASS. Run #671 menggunakan commit lama d264c8dfc896f5d78e70b818e5ee4cfd41d7e36a, bukan head ade5daa6. Run tersebut: smoke test 23 passed/83 assertions; full suite 18 failed, 462 passed/1.866 assertions. Error yang tercatat termasuk ParseError pada resources/views/livewire/admin/question-bank.blade.php. Run #668 pada commit lebih lama gagal sebelum full suite karena syntax error tests/Feature/ExamBuilderTest.php:202. Tidak ada klaim bahwa hasil tersebut mewakili head terbaru.
- Library memuat CBT REVISI 6 NOPEMBER 2026_REASSEMBLED_READONLY.zip (metadata 105.365.379 byte) dan 01_SOURCE_WORKSPACE_RECOVERED(1).zip (65.569.951 byte). Materialisasi raw-file kedua arsip ditolak layanan. CRC, central directory, SHA-256 aktual, dan isi arsip tidak diverifikasi.
- Riwayat audit menyatakan 01_SOURCE_WORKSPACE_RECOVERED adalah V50.10 Web Focus, bukan worktree current 14 November; 26 hash mismatch dan 12 not found pada 38 target. Jangan jadikan canonical.
- CBT_MADRASAH_PAKET_FINAL.zip tercatat sebagai objek Library dalam laporan historis, tetapi raw bytes belum tersedia; SHA/CRC/isi belum dapat diverifikasi.
- Filesystem sesi: /mnt/data/CBT_MADRASAH_RECONSTRUCTION/ dan CBT_IMPLEMENTATION_WORKTREE_2026-11-14/ tidak ada. Tidak ada backend-laravel/ lokal. /mnt/data berisi dokumen audit/laporan, bukan source aktif.

## 3. AUDIT — Temuan source Web Admin
Audit rekonsiliasi 21 November mencakup pemindaian 338 file PHP/Blade aktif dan 23 kelompok modul ADM. Semua 23 kelompok masih memiliki gap atau pekerjaan yang belum lengkap. Temuan langsung yang diverifikasi ulang pada source:
1. DatabaseSeeder membuat ADMIN_MADRASAH dan OPERATOR sebagai dua role.
2. RolePermissionBootstrapper memberi Admin dan Operator permission profile berbeda. Admin mendapat exam.manage, exam.assign, exam.monitor, exam.close, exam.proctor.assign, exam.token, exam.grade, exam.reopen, report.view.
3. AuthWebController masih mengizinkan OPERATOR dan PROKTOR; Admin/Operator diarahkan ke /operator/dashboard, PROKTOR ke /proktor/assignments.
4. layouts/app.blade.php masih berlabel Panel Admin & Guru; menampilkan Dashboard Operator, menu Proktor, Manajemen Ujian dan Bank Soal bersama; sidebar desktop disembunyikan pada mobile dan belum memiliki drawer final.
5. ExamPolicy mengizinkan Admin close dan monitor. ExamAttemptPolicy mengizinkan Admin view/viewScore/finalize/reopen sesuai permission. Ini melampaui keputusan final: Admin hanya metadata ujian read-only dan penugasan pengawas.
6. ExamManagement masih komponen gabungan Admin/Guru. Policy membatasi beberapa aksi authoring, tetapi permukaan UI dan permission tetap bercampur.
7. ProctorAssignmentManagement dan ExamProctorAssignmentService mensyaratkan hasRole(PROKTOR), bukan Guru aktif. Role/portal Proktor permanen masih ada.
8. ExamProctorAssignmentService sudah mengunci Exam dan User target dalam transaksi, tetapi belum menjalankan query overlap seluruh assignment Guru. Revoke belum menggunakan mutex Guru dengan urutan lock yang sama.
9. Assignment belum memiliki rombel_id/class_id, schedule revision/fingerprint, NEEDS_REVIEW, dan event history khusus. Migration unique hanya tenant_id+exam_id+proctor_user_id.
10. Perubahan jadwal memiliki jalur API ExamController::update dan Livewire ExamManagement::saveExam. Belum ditemukan satu ExamScheduleChangeService yang memaksa kedua jalur menandai assignment NEEDS_REVIEW.
11. TenantScoped sudah fail-closed: context kosong menghasilkan whereRaw 1=0 untuk query dan exception untuk create. IdentifyTenant mengabaikan X-Tenant-Override dan mengambil tenant dari user terautentikasi. InstallationIdentityService menegakkan satu Tenant dan maksimal satu installation identity.
12. Gap tersisa pada User model yang tidak memakai TenantScoped, domain binding existing LocalInstallationIdentity yang belum dibandingkan dengan APP_URL host, serta isolasi SystemTenantSeeder dari DB madrasah.
13. Matriks audit mencatat 23 modul: dashboard, identitas madrasah, tahun/semester, kelas/fase/rombel/wali, data Guru, penugasan Guru, data Siswa, penempatan/promosi/tinggal kelas, kelulusan/alumni, master mapel, Mulok, Kurmer/KBC, CP/TP/ATP, Panca Cinta/Deep Learning/DPL, impor Excel Siswa/Guru/mapel, laporan administratif, backup/restore, profil/preferensi/notifikasi, audit/health, shell/sidebar/responsive, daftar ujian/assignment Proktor.

## 4. PENGUJIAN — Runtime sesi ini
Pemeriksaan aktual:
- PHP CLI 8.4.24; PHP 8.3 tidak tersedia.
- Ekstensi PDO tersedia, tetapi PDO drivers kosong; pdo_mysql tidak tersedia.
- Composer tidak tersedia.
- MySQL client/server tidak tersedia.
- Docker tidak tersedia.
- Node.js 22.16.0, npm 10.9.2, Git 2.47.3 tersedia.
- Tidak ada source lokal untuk menjalankan PHPUnit, Laravel, route:list, browser, atau lint pada source kandidat.
- Tidak ada PHPUnit/MySQL 8/InnoDB/concurrency/browser/UAT test baru dijalankan pada sesi ini.
- Matriks tes Web Admin memuat T-001 sampai T-132; semua test baru tetap NOT RUN sampai dieksekusi. SQLite tidak boleh dipakai untuk mengklaim MySQL row-locking pass.

## 5. DESAIN — Rencana W0–W10
- W0: kunci source candidate, SHA/tree/manifest, backup register, route-action matrix; no app patch.
- W1: konsolidasi role Admin, legacy migration alias, hilangkan portal Proktor, perketat tenant guards dan permission Admin.
- W2: dashboard, shell, sidebar scroll independen, mobile drawer, route/navigation Admin.
- W3: identitas madrasah, tahun ajaran, semester, kelas/fase/rombel/wali kelas dan histori.
- W4: lifecycle Guru/Siswa, akun, status aktif, penugasan Guru dan mapel.
- W5: penempatan/mutasi/promosi/tinggal kelas/kelulusan/alumni dengan histori transaksional.
- W6: Kurmer/KBC, CP/TP/ATP, Mulok, Panca Cinta, Pembelajaran Mendalam, DPL; versi, sumber, tanggal efektif, histori immutable.
- W7: daftar ujian Guru read-only di Admin dan assignment Guru pengawas; rombel, overlap, locking MySQL, reschedule NEEDS_REVIEW, expiry/revoke.
- W8: import Excel Siswa/Guru/mapel (termasuk Mulok), preview, validasi, duplicate handling, commit idempoten, laporan administratif.
- W9: audit log, health, backup/restore pada disposable DB, profil/preferensi/notifikasi.
- W10: integrasi, keamanan, MySQL 8, concurrency, browser/responsive, performance, restore, UAT Admin.

## 6. IMPLEMENTASI
Tidak ada source aplikasi, migration, database, .env, secret, PR existing, merge, deployment, atau VPS yang diubah. Tidak ada source Android atau Device Lease/Fencing yang diubah. Audit sesi ini bersifat read-only. Laporan ini dan checkpoint pendamping adalah dokumentasi baru saja.

## 7. Keputusan yang diperlukan sebelum W1
Persetujuan eksplisit diperlukan untuk:
1. Menetapkan recovery/source-preservation-20261121 @ 7fc45bf71ac1ebd375eba44fd57c983bf18d66e0 sebagai working source Web Admin, dengan catatan belum byte-verified terhadap ZIP baseline dan bukan canonical seluruh CBT.
2. Menyetujui desain role Admin tunggal, Proktor sebagai capability sementara Guru, Admin metadata-only + assignment, assignment [start,end) dengan overlap locking/current read dan NEEDS_REVIEW, serta rencana W0–W10.
3. Mengizinkan pembuatan branch implementasi terpisah dan mulai W1 setelah persetujuan. Tidak ada merge PR #5/#6/#7 sebagai bagian dari persetujuan ini.

## 8. Status akhir
AUDIT STATIS READ-ONLY: selesai sebagian dan memiliki bukti source/branch. BASELINE RAW: BLOCKED. RUNTIME: BLOCKED. DESAIN: diajukan untuk persetujuan. IMPLEMENTASI: belum dimulai. PENGUJIAN: belum dijalankan pada head source terkini.
