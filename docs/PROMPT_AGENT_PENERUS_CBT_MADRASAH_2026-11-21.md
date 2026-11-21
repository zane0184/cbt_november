# PROMPT AGENT PENERUS — IMPLEMENTASI CBT MADRASAH SETELAH REKONSILIASI DOKUMENTASI

Anda melanjutkan repository `zane0184/cbt_november`. Jangan memulai ulang dan jangan menganggap dokumen ini sebagai bukti source telah diperbaiki.

## Urutan wajib baca
1. `docs/WEB_ADMIN_MASTER_SPECIFICATION.md`.
2. `docs/SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md` — terutama Addendum 33.
3. `docs/REGISTER_KEPUTUSAN_WEB_ADMIN.md` — DEC-WA-019 s.d. DEC-WA-025.
4. `docs/MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md` — 23 modul dan addendum cross-cutting.
5. `docs/MATRIKS_PENGUJIAN_WEB_ADMIN.md` — T-001–T-168.
6. `docs/PETA_DOKUMEN_PROVENANCE_CBT.md`.
7. `docs/REKONSILIASI_DOKUMENTASI_W0_2026-11-21.md`.
8. Checkpoint terbaru dan laporan audit source.

## Keputusan yang tidak boleh diubah
- Satu-satunya role Admin aktif adalah `ADMIN_MADRASAH`. `OPERATOR` bukan role aktif dan tidak boleh menjadi alias runtime.
- Akun legacy OPERATOR dipetakan dengan migrasi aman; pertahankan user ID, password hash, tenant, profil, histori, audit. Akun orang berbeda tidak digabung. Konflik ambigu = BLOCKED/manual review.
- Admin hanya mengelola administrasi madrasah, master referensi sesuai kewenangan, data Guru/Siswa, dukungan administrasi, dan assignment Proktor.
- Admin tidak mengelola bank soal, isi/kunci soal, ujian, mapel/jadwal/peserta ujian, publish/token/snapshot, Attempt/Answer/Score, grading, hasil/analitik ujian, monitoring operasional, close/finalize/reopen, reset Attempt, Force Submit, takeover perangkat, perpanjangan waktu, atau status pelaksanaan.
- Guru adalah pemilik seluruh operasional CBT tanpa approval Admin jika permission/assignment/validasi sistem terpenuhi.
- Proktor adalah Guru aktif dengan assignment sementara dari Admin, bukan role/portal permanen.
- Perubahan jadwal ujian hanya oleh Guru melalui `ExamScheduleChangeService`; assignment terdampak menjadi `NEEDS_REVIEW`.
- Assignment memakai [start,end), overlap ditolak melalui MySQL 8+ InnoDB locking/current read; hak Proktor hanya untuk assignment CONFIRMED yang valid pada waktu server.
- Satu madrasah = satu instalasi = satu database. Tenant isolation tetap defense-in-depth dan fail-closed.
- Kurmer + KBC terintegrasi; jangan mengarang kode atau nomenklatur resmi.

## Tahap pertama — audit source read-only
Sebelum coding:
1. Verifikasi semua branch HEAD, PR, commits, CI, source tree, file dan blob SHA.
2. Pilih source candidate hanya berdasarkan bukti/provenance; jangan menganggap branch recovery otomatis branch implementasi yang disetujui.
3. Audit seluruh seeder, installer, role/permission/pivot, login resolver, middleware, policy, gate, routes, controller, Livewire, API, layout/sidebar, views, tests.
4. Audit semua direct endpoint untuk memastikan Admin ditolak pada seluruh operasi CBT.
5. Audit migration role legacy dan buat preflight/mapping plan tanpa menjalankannya.
6. Audit semua jalur assignment, interval overlap lintas ujian/rombel, schedule revision, NEEDS_REVIEW, revoke/expiry, locking InnoDB, dan audit transaction.
7. Audit tenant isolation khusus User/withoutGlobalScopes, TenantContext, SystemTenantSeeder, installation identity/domain binding.
8. Perbarui matriks audit dengan path, branch, commit, blob SHA, route/action, permission, test dan gap.
9. Jalankan test hanya pada environment disposable yang sesuai; concurrency wajib MySQL 8+ InnoDB dengan dua koneksi.
10. Serahkan laporan AUDIT + DESAIN + rencana file/backup/migration/test.

## Gerbang persetujuan
Berhenti dan tunggu persetujuan eksplisit pengguna sebelum mengubah source. Persetujuan dokumentasi ini bukan persetujuan implementasi, migration, merge, deployment, atau database produksi.

## Batas keras
- Jangan menjalankan migration/DML pada database produksi.
- Jangan mengubah .env/secrets/VPS/Nginx atau deploy.
- Jangan merge/rebase/force-push/reset atau menghapus branch.
- Jangan mulai Portal Guru/Siswa, Android, Super Admin/Publisher, licensing/payment, W1/W6, atau Device Lease/Fencing di luar scope yang disetujui.
- Jangan jalankan Gradle via terminal; Android build hanya Android Studio ketika scope Android disetujui.
- Jangan klaim PASS tanpa bukti aktual.
- Backup setiap file sebelum perubahan dan update checkpoint.
