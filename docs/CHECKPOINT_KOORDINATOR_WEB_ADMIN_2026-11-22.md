# CHECKPOINT KOORDINATOR — WEB ADMIN MADRASAH
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch kerja: work/coordinator-web-admin-audit-20261122
HEAD sebelum pembaruan dokumen: 7a0fa60e7cda06b22c86eb608a6a74189f13edf2
Acuan: docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md pada main, blob 68150acf7b9abf130776bee27444c99889ce53a5.

## AUDIT
- Web Admin belum accepted. Gunakan status EXISTS & VERIFIED / EXISTS BUT BROKEN / MISSING / BLOCKED / DEFERRED; komponen/route saja bukan bukti verified.
- UserManagement awal hanya daftar/create. P1 menambahkan edit dan reset password; status aktif/nonaktif belum ada di schema users.
- users.deleted_at tersedia tetapi model User tidak menggunakan SoftDeletes; jangan memakai soft-delete sebagai status akun aktif/nonaktif.
- Import/export Guru/Siswa, room management, pengaturan branding/kop lengkap, audit UI dan laporan Admin masih memerlukan audit seluruh routes/services/jobs/views/tests sebelum ditandai missing.
- CI WA-01 masih failure sebelum steps muncul; tidak ada test runtime yang bisa diklaim.

## DESAIN
- Data Pengguna dikerjakan dahulu: create, edit, reset password; kemudian desain status akun dan detail/history. Setelah itu import/export sesuai alur preview/confirm/idempotensi §5.
- Status akun membutuhkan schema flag, enforcement LoginIdentityResolver, penolakan login, session/remember-token revocation, filtering admin, audit, migration tests dan backfill. Jangan gunakan deleted_at.
- Tidak mengubah model produk: role aktif tetap ADMIN_MADRASAH/GURU/SISWA; tidak ada role Operator/Kamad/Proktor permanen; ujian serentak tanpa ExamSession operasional.

## IMPLEMENTASI
- NIP/NIK Guru dibuat opsional; test regresi ditambahkan.
- Edit Guru/Siswa: nama, username, email, NIP/NIK atau NISN. Tenant scoped, single-role only, validasi uniqueness, transaksi dan row lock, audit USER_UPDATED.
- Reset password: minimal 14 karakter, transaksi/row lock, rotasi remember_token, audit USER_PASSWORD_RESET tanpa menyimpan password.
- Backup pra-edit/reset ada di docs/backup/coordinator-web-admin-2026-11-22/user-management-edit-reset-p1/; backup pra-NIP/NIK opsional ada di docs/backup/coordinator-web-admin-2026-11-22/guru-identitas-opsional-p0/.
- Source commits: b16d61a8bfa353377b9acabb499de3333ccee798, ab93091c040bda245bd901f6a16bf5339c3ac201, 95735b4f1c637e9ef8434776082bb5c050080d57, ee2d811bf493d897be411773a91bc5f8724ccb08, 7a0fa60e7cda06b22c86eb608a6a74189f13edf2.
- Audit commit setelah pembaruan: 0f5c92dcd55db3321326d9b59b927c7f410692ec.

## PENGUJIAN
- Source dan test dibaca ulang dari GitHub. PHPUnit NOT RUN; PHP lint NOT RUN; MySQL 8/InnoDB NOT RUN; browser/E2E NOT RUN.
- Actions run #499–#502 failure tanpa steps yang tercatat; run #502 job 137270555915 completed/failure, steps kosong. Jangan menyimpulkan test suite gagal atau lulus karena runner tidak menjalankan langkah yang dapat diamati.
- Implementasi berstatus NOT VERIFIED.

## LARANGAN / BATAS
- Tidak ada migration, database produksi, VPS, main branch, merge, atau deployment yang diubah.
- Android native dan Web Super Admin tetap deferred sampai Juni 2027.

## LANGKAH BERIKUTNYA
1. Audit & implementasikan status aktif/nonaktif akun secara end-to-end (schema + login enforcement + session revocation + audit + tests), tanpa menggunakan soft-delete.
2. Tambahkan detail akun dan riwayat perubahan/penugasan Guru dan Proktor sesuai batas akses.
3. Audit seluruh repository untuk import/export Guru/Siswa, template, service/job, routes dan tests; baru implementasikan gap.
4. Selesaikan master akademik, kurikulum, ruang/placement, monitoring, laporan, audit UI, branding/kop, dan pengaturan.
5. Pulihkan GitHub Actions runner dan jalankan seluruh tes pada exact HEAD, MySQL 8/InnoDB, E2E serta UAT.

Status akhir: WEB ADMIN BELUM ACCEPTED.