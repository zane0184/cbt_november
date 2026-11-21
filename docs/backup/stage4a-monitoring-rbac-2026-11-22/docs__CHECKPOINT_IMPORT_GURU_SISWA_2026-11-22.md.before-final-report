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
1. Lanjutkan Web Admin Stage 4: audit dan rekonstruksi assignment Proktor sementara berbasis Guru aktif, termasuk PENDING → CONFIRMED, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED; scope tenant/exam/rombel/schedule revision; conflict check; dan pencabutan capability ketika jadwal berubah.
2. Lanjutkan UserManagement: edit, status aktif/nonaktif, reset password, dan audit lifecycle.
3. Lanjutkan StudentRombelManagement: perpindahan/kenaikan/tinggal kelas/kelulusan tanpa menghapus histori akademik.
4. Lengkapi CurriculumManagement untuk versi kurikulum, CP/TP/ATP, Kurikulum Merdeka, KBC/Panca Cinta, Pembelajaran Mendalam.
5. Lengkapi laporan administratif, konfigurasi, backup/restore, dan UAT Web Admin.
6. Tidak ada merge, PR, migration produksi, atau deployment VPS tanpa persetujuan eksplisit.
