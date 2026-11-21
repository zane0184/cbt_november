# CHECKPOINT — REVIEW FINAL IMPORT GURU DAN SISWA
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/import-guru-siswa-implementation-20261122
Source code yang diuji: a3522e1c8be02224ef9b369051105f05b2704e39
GitHub Actions: 44944158355 — SUCCESS
Run: https://github.com/zane0184/cbt_november/actions/runs/44944158355

## STATUS
REVIEW FINAL KODE: PASS pada cakupan pemeriksaan dan suite CI yang dicatat di bawah.
Status produksi: BELUM SIAP PRODUKSI. Belum ada uji UI manual madrasah, uji beban multi-proses, deployment staging/VPS, atau persetujuan migration produksi.
Tidak ada PR, merge, deployment VPS, atau perubahan database produksi.

## AUDIT
- Aturan identitas dipertahankan: Guru PNS login dengan NIP; Guru honorer login dengan NIK; Siswa login dengan NISN; NUPTK hanya data pendukung.
- TeacherProfile dan StudentProfile tetap mengikuti kolom aktual database. Tidak ditambahkan atribut profil berdasarkan asumsi.
- Tidak ada unique constraint baru pada NIP, NIK, NUPTK, NIS, atau NISN. Constraint baru hanya untuk entitas import seperti checksum batch/token dan relasi baris.
- Kedelapan area review:
  1. URL aktivasi rahasia tidak lagi berada dalam properti publik Livewire. Tautan diserahkan melalui session flash setelah redirect, kemudian di-pull satu kali pada render halaman.
  2. Batch dan baris memiliki state transition terpisah. Batch masuk PROCESSING sebelum pemrosesan; tiap baris diproses atomik dengan retry transaksi tiga kali; batch dapat dilanjutkan setelah proses terputus; status akhir dihitung dari status baris.
  3. Workbook diperiksa sebelum parser: ukuran file, ZIP entry count/expanded size, path, daftar komponen, relationship eksternal, macro/embedded content, DTD/ENTITY, formula, sheet, status hidden/visible, batas baris/kolom, dan panjang sel.
  4. Data Guru diproses sebelum Penugasan Guru. Kegagalan pembuatan Guru tidak menghasilkan assignment yatim karena transaksi per baris dan validasi ulang relasi.
  5. Payload staging dienkripsi dengan Crypt; field sensitif disembunyikan dari serialization. Preview/processing yang terbengkalai kedaluwarsa setelah tujuh hari; payload dibersihkan, laporan dan audit tetap tersedia.
  6. Exception internal tidak ditampilkan mentah kepada Admin. Pesan kegagalan generik; detail teknis minimum dicatat di log.
  7. Actor, tenant context, query, relasi referensi, laporan, dan perubahan import dibatasi ke tenant yang sama.
  8. Import hanya dapat dijalankan ADMIN_MADRASAH dengan permission user.manage. Import tidak memberi role/permission CBT kepada Admin.
- Retensi file: service tidak mengarsipkan workbook asli sebagai file permanen; yang disimpan untuk pemrosesan adalah payload baris terenkripsi. Temporary upload dikelola oleh mekanisme upload Livewire. Uji retensi filesystem live belum dilakukan.

## DESAIN
- Template XLSX berasal dari referensi tenant aktif: Petunjuk, Data Guru, Penugasan Guru, Data Siswa, dan Referensi.
- Data baru diimpor; data existing dilewati; duplikat dalam file ditolak; tidak ada auto-update.
- Upload → validasi → preview → konfirmasi eksplisit → import; idempotensi memakai checksum batch.
- Tenant lock menyerialisasi operasi import dan pembuatan user manual yang memakai lock yang sama.
- Password tidak dicantumkan di workbook. Akun baru menggunakan tautan aktivasi satu kali, signed dan berumur 48 jam. Database hanya menyimpan token hash; penggunaan token mengunci token/user, mengubah password, menandai token terpakai, dan menonaktifkan token lain.
- Riwayat dan laporan hanya dapat diakses Admin Madrasah berizin dalam tenant terkait.

## IMPLEMENTASI
Perubahan review final berada di branch terisolasi:
- app/Livewire/Admin/ImportManagement.php — penghapusan state publik activationLinks, handover via session flash/redirect, sanitasi exception, resume batch.
- app/Services/Import/ImportWorkbookService.php — transaksi per baris, retry, resume PROCESSING, heartbeat batch, sanitasi alasan gagal, hardening ZIP/XML.
- app/Exceptions/ImportWorkbookValidationException.php — exception validasi workbook yang terkontrol.
- routes/console.php — perintah imports:expire-staging dan scheduler retensi tujuh hari.
- tests/Feature/ImportWorkbookServiceTest.php — regression workbook, activation, staging, tenant, status, partial failure.
- tests/Feature/ImportDeadlockRetryTest.php — pengujian retry MySQL error 1213.
- Tidak ada perubahan unique constraint identitas lama.
- Source baseline dan ZIP asli tidak diubah.

Backup:
- Folder: docs/backup/import-review-final-2026-11-22/
- Manifest: docs/backup/import-review-final-2026-11-22/MANIFEST.md
- Backup awal mencakup ImportManagement, ImportWorkbookService, Blade, test, dan routes/console.
- Snapshot tambahan dibuat sebelum setiap patch lanjutan; blob SHA dicatat di manifest. Snapshot test sebelum dua patch DTD disimpan dengan path berbeda agar tidak saling menimpa.
- Checkpoint lama juga dibackup sebelum diperbarui.

## PENGUJIAN
GitHub Actions run 44944158355:
- Status: SUCCESS.
- Source diuji: a3522e1c8be02224ef9b369051105f05b2704e39.
- 14 suite terpilih: 93 test, 502 assertions, seluruhnya PASS.
- Rincian:
  - AdminCbtBoundaryTest: 1 / 27 assertions.
  - AdminDashboardAccessTest: 3 / 28.
  - AcademicPeriodManagementTest: 3 / 33.
  - LegacyRoleMigrationServiceTest: 4 / 34.
  - TenantProfileManagementTest: 3 / 18.
  - AdminUserManagementScopeTest: 3 / 21.
  - ImportTemplateGeneratorTest: 5 / 40.
  - ImportWorkbookServiceTest + ImportDeadlockRetryTest: 35 / 182.
  - AuthTest: 7 / 38.
  - MultiTenantAuthTest: 3 / 11.
  - TenantIsolationTest: 10 / 16.
  - AssessmentPolicyTeacherCreateTest: 1 / 5.
  - StudentWebLoginTest: 8 / 27.
  - StudentResultVisibilityTest: 7 / 22.
- PHP lint: PASS.
- MySQL 8.0.46 disposable, PHP 8.3, Ubuntu runner.
- 75 base tables; 0 tabel non-InnoDB; required schema tersedia.
- Disposable database dan user aplikasi berhasil dihapus setelah tes.
- Artifact: wa01-alternative-runtime-44944158355-1, ID 14723455279, SHA-256: 680a4db83b9f51a004e623e747230ddbb5523cccfc6be7d1058d8e4051f540cf, retensi sampai 21 Desember 2026.
- Cakupan deadlock: test menyuntikkan QueryException dengan kode MySQL 1213 untuk memverifikasi retry transaksi. Ini belum merupakan simulasi deadlock multi-koneksi yang benar-benar terbentuk oleh dua transaksi bersamaan.
- Belum dilakukan uji stress multi-proses, pengujian UI browser manual, uji filesystem staging di VPS, dan UAT madrasah.

## PEKERJAAN BERIKUTNYA
1. Lanjutkan Stage 4A sesuai koreksi pemilik proyek: pisahkan portal Admin Madrasah, Guru, dan antarmuka Siswa; Admin tidak boleh mengakses fitur/data CBT; Guru dibatasi role, permission, penugasan, dan tenant; Proktor hanya penugasan sementara kepada Guru yang memenuhi syarat, tanpa role/portal permanen. Uji URL/API langsung dan akses lintas kewenangan/tenant. Tidak ada ruang lingkup Kamad.
2. Setelah batas Stage 4A terdefinisi dan teruji, lanjutkan lifecycle assignment Proktor sementara: PENDING → CONFIRMED, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED; scope tenant/exam/rombel/schedule revision; conflict check; pencabutan capability ketika jadwal berubah.
3. Lanjutkan TeacherSubjectAssignment Management agar kewenangan Guru benar-benar mengikuti penugasan aktif.
4. Lanjutkan UserManagement: edit, status aktif/nonaktif, reset password, dan audit lifecycle.
5. Lanjutkan StudentRombelManagement: perpindahan rombel, kenaikan kelas, tinggal kelas, dan kelulusan tanpa menghapus histori akademik.
6. Lengkapi CurriculumManagement untuk versi kurikulum, CP/TP/ATP, Kurikulum Merdeka, KBC/Panca Cinta, Pembelajaran Mendalam.
7. Lengkapi laporan administratif, konfigurasi, backup/restore, dan UAT Web Admin.
8. Tidak ada merge, PR, migration produksi, atau deployment VPS tanpa persetujuan eksplisit.

## KOREKSI RUANG LINGKUP STAGE 4A — PEMILIK PROYEK (22 NOVEMBER 2026)
- Kamad tidak termasuk ruang lingkup Stage 4A maupun portal aktif proyek. Jangan menambah role, portal, fitur, route, permission, atau alur Kamad tanpa keputusan baru pemilik proyek.
- Fokus Stage 4A: pemisahan portal Admin Madrasah, Guru, dan Siswa; larangan akses Admin terhadap fitur/data CBT; kewenangan Guru berbasis role/permission/penugasan; penugasan Proktor sementara kepada Guru yang memenuhi syarat; dan pengujian URL/API langsung serta isolasi lintas tenant/kewenangan.
- Proktor bukan portal atau role permanen.
- Perubahan source yang sempat memasukkan Kamad telah dipulihkan dari backup dan keenam file aktif diverifikasi identik dengan salinan backup masing-masing.


## CHECKPOINT IMPLEMENTASI STAGE 4A — KOREKSI PEMILIK PROYEK

Status: IMPLEMENTASI PARSIAL; bukan klaim Stage 4A selesai.

- Scope aktif: pemisahan Admin Madrasah, Guru, dan Siswa; Admin dilarang mengakses fitur/data CBT; Guru berdasarkan role/permission/penugasan/tenant; penugasan Proktor sementara hanya untuk Guru yang memenuhi syarat; pengujian URL/API langsung dan lintas kewenangan.
- Kamad tidak termasuk Stage 4A dan tidak ada portal/route/fitur baru Kamad.
- Kandidat Proktor sekarang wajib akun tenant-local, belum dihapus, tepat satu role GURU, serta mempunyai TeacherProfile tenant-local yang tidak soft-deleted. Filter UI dan validasi service sama-sama menerapkan syarat ini.
- Admin CBT boundary ditambah tes API langsung GET /api/v1/questions dan GET /api/v1/exams.
- Test ProctorAssignmentSecurityTest mencakup Guru aktif, Siswa, multi-role, profil Guru soft-deleted, revoke, akses halaman Admin oleh Guru, dan foreign key lintas tenant.
- Backup sumber implementasi: docs/backup/stage4a-proctor-eligibility-2026-11-22/ (manifest tersedia).
- Laporan implementasi: docs/IMPLEMENTASI_STAGE4A_PORTAL_RBAC_PROKTOR_GURU_2026-11-22.md.
- CI 44946708332 pada SHA 6fdefb24ef5bc3acbd182fbc9f9a1366d19a0037: SUCCESS, 100 test / 516 assertion, PHP lint PASS.
- CI terbaru 44946877772 pada SHA 2ff3f3b6c671e12f25da941f50370bf1cc47eeb5 masih berjalan saat checkpoint diperbarui. Verifikasi ulang hasil akhirnya sebelum menyatakan source terbaru lulus.
- Belum ada migration baru, perubahan DB produksi, deployment VPS, PR, atau merge.

Pekerjaan selanjutnya:
1. Verifikasi akhir CI 44946877772.
2. Lengkapi pengujian URL/API langsung untuk monitoring, peserta, jawaban, nilai, dan report.
3. Audit final portal Admin/Guru/Siswa dan isolasi akses.
4. Lanjutkan lifecycle/capability assignment Proktor sementara (PENDING/CONFIRMED/NEEDS_REVIEW/REVOKED/EXPIRED/COMPLETED), scope rombel dan schedule revision, konflik, serta pencabutan capability.
5. Lanjutkan tahap berikut tanpa membuka hak CBT untuk Admin Madrasah.
