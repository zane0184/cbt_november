# CHECKPOINT KOORDINATOR — WEB ADMIN MADRASAH
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch kerja: work/coordinator-web-admin-audit-20261122
HEAD: 2f89a1da9d28302ed7e8f12d461298750fa05ee2
Acuan wajib: docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md pada main (blob 68150acf7b9abf130776bee27444c99889ce53a5).

## AUDIT
- Web Admin tetap NOT ACCEPTED. Audit statis gap awal tercatat dalam docs/AUDIT_KOORDINATOR_WEB_ADMIN_2026-11-22.md.
- Ditemukan ketidaksesuaian §4.1: UserManagement mewajibkan NIP atau NIK bagi Guru, sedangkan spesifikasi menyatakan NIP/NUPTK opsional bila tidak dimiliki dan NIK tidak boleh diwajibkan tanpa dasar.
- Dashboard, akademik, kurikulum, profil, rombel dan Proktor memiliki komponen; fitur import/export Guru/Siswa, pengaturan branding/kop lengkap, room management dan laporan Admin belum terbukti lengkap.
- Route /admin/questions dan /admin/exams memakai komponen Guru yang memiliki exact-role guard; route berada dalam grup auth/tenant/license umum, sehingga pengujian negatif tetap wajib.

## DESAIN
- Perbaikan dilakukan satu gap yang didukung spesifikasi, tanpa mengubah keputusan portal, model ujian serentak, atau batas akses Admin.
- NIP/NIK opsional; jika diisi, validasi format dan uniqueness tetap berlaku.
- Tahap selanjutnya: lengkapi User Management (edit, active/inactive, reset password, detail/history) dengan tenant scope dan audit; setelah itu import/export Guru/Siswa sesuai alur preview/konfirmasi/idempotensi spesifikasi.

## IMPLEMENTASI
- Menghapus syarat NIP/NIK wajib dari UserManagement::createUser.
- Memperbarui teks bantuan UI menjadi opsional.
- Menambah test_admin_can_create_guru_without_nip_or_nik, menggunakan username eksplisit dan memeriksa role, profile, nilai null, serta audit.
- Backup source pra-patch diverifikasi tersedia:
  - docs/backup/coordinator-web-admin-2026-11-22/guru-identitas-opsional-p0/UserManagement.php.before-guru-identity-optional (blob 78b5bc00ac7c2c57acc8eda6783164cfc9371aea)
  - docs/backup/coordinator-web-admin-2026-11-22/guru-identitas-opsional-p0/user-management.blade.php.before-guru-identity-optional (blob 100b5009b2663191e327443c0ca70a491b1a4528)
  - docs/backup/coordinator-web-admin-2026-11-22/guru-identitas-opsional-p0/AdminUserManagementScopeTest.php.before-guru-identity-optional (blob 9ddeb4c8cad41b5fd636714c91785ecc31a2239b)
- Backup laporan: docs/backup/coordinator-web-admin-2026-11-22/AUDIT_KOORDINATOR_WEB_ADMIN_2026-11-22.md.before-guru-identity-optional.
- Source commits: 05e51dd2129cf100d87d78a5db900c9ec5c177a0, 174e06f720d52cf05c10f6794172aebdff830e3b, 922657ab81caf36f0964cc7e914bddb7cedc216c, e0d0e24b2071c954fdfae892ad0c69c367061de5.
- Audit commit: 2f89a1da9d28302ed7e8f12d461298750fa05ee2.

## PENGUJIAN
- Source patch telah dibaca ulang dari GitHub dan backup diverifikasi cocok dengan blob source pra-patch.
- PHPUnit, PHP lint, MySQL 8 integration/concurrency: NOT RUN.
- GitHub Actions WA-01 runs #495–#497 gagal tanpa steps dan tanpa runner_name; retry run #494 attempt 2 juga gagal tanpa steps. Job logs endpoint mengembalikan BlobNotFound.
- Patch status: IMPLEMENTED — NOT VERIFIED. Tidak ada klaim PASS.

## LARANGAN / BATAS
- Tidak ada perubahan main, merge, rebase, migration, database, VPS, atau deployment.
- Tidak mengaktifkan portal Operator/Kamad/Proktor permanen; tidak membuat ExamSession/session/wave operasional.
- Android dan Web Super Admin tetap deferred sampai Juni 2027.

## LANGKAH BERIKUTNYA
1. Lanjutkan audit dan implementasi User Management P0: edit, active/inactive, reset password, detail/history, audit event dan negative authorization tests.
2. Audit seluruh repository untuk import/export Guru/Siswa, template, service/job, routes dan tests sebelum menyatakan missing.
3. Selesaikan academic/subject/teacher assignment, curriculum, room/placement/proctor, monitoring, reports, audit UI, settings/branding.
4. Pulihkan CI runner dan jalankan seluruh tes pada exact HEAD; gunakan MySQL 8 disposable/InnoDB untuk integration/concurrency.
5. Lakukan browser/E2E, responsive/accessibility dan UAT sebelum acceptance.

Status akhir saat checkpoint: WEB ADMIN BELUM ACCEPTED.