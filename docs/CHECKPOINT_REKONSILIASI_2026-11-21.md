# CHECKPOINT REKONSILIASI DOKUMENTASI — CBT MADRASAH
Tanggal: 21 November 2026 (WIB)
Repository: `zane0184/cbt_november`
Branch: `docs/canonical-reconciliation-admin-guru-20261121`
Base: `docs/web-admin-w0-recheck-20261121` @ `fb058f05264b8d464d687583c53f06fbb7fe54f7`

## Status
<badge>DOCUMENTATION RECONCILIATION COMPLETE — DOCUMENTS ONLY</badge>

## AUDIT
- Branch dan HEAD yang tercantum pada laporan rekonsiliasi diverifikasi ulang dari GitHub.
- PR #5/#6/#7 masih OPEN, Draft, Unmerged; tidak diubah.
- Source kandidat dibaca statis pada 15 file kunci; temuan source dicatat sebagai OPEN.
- Source recovery dibaca statis pada 15 file kunci.
- Temuan utama: seeder/bootstrap/login/policy masih memiliki OPERATOR/PROKTOR; Admin masih memiliki permission/policy CBT yang dilarang; question bank Admin masih dapat diakses; Proktor picker/service masih mensyaratkan role PROKTOR; overlap dan schedule review belum lengkap.
- ZIP baseline 6 November diperlakukan sebagai arsip historis non-blocker dan tidak dibuka.
- Panca Cinta/DPL dan catatan status W0 lama memiliki konflik yang diberi precedence terbaru.

## DESAIN
- Role aktif Admin hanya ADMIN_MADRASAH.
- Admin: administrasi/master referensi sesuai kewenangan dan assignment Proktor.
- Guru: seluruh operasional CBT.
- Proktor: assignment sementara Guru aktif, bukan role/portal.
- Schedule change oleh Guru melalui ExamScheduleChangeService; assignment terdampak NEEDS_REVIEW.
- Interval [start,end), overlap ditolak dengan MySQL 8/InnoDB locking/current read.
- Migrasi role Operator dirancang fail-closed dan tidak dijalankan.

## IMPLEMENTASI DOKUMENTASI
- Branch dokumentasi baru dibuat.
- Backup enam dokumen dibuat di `docs/backup/reconciliation-2026-11-21/` dan dicatat pada MANIFEST.tsv.
- Entry point master, rekonsiliasi W0, spesifikasi rinci, register, matriks audit, matriks test, provenance, dan prompt agent diperbarui.
- T-001–T-132 dipertahankan; T-133–T-168 ditambahkan.
- Source aplikasi, database, migration, seeder, .env, VPS, Android dan Device Lease/Fencing tidak diubah.

## PENGUJIAN
- Metadata GitHub, branch compare, pembacaan source, dan pembuatan backup telah dilakukan.
- Link entry point: jumlah akhir diperiksa ulang setelah pembaruan entry point; hasil dicatat pada final verification.
- 23 modul audit tetap ada; T-001–T-132 dipertahankan; T-133–T-168 ditambahkan.
- Backup 6/6 cocok dengan blob sumber; SHA-256 dicatat pada manifest.
- Compare lengkap pada HEAD awal menunjukkan 28 path docs/ berubah/ditambahkan; tidak ada source aplikasi.
- CI, runtime, browser, MySQL concurrency, import/restore, UAT: NOT RUN.

## Gerbang berikutnya
1. Selesaikan pemeriksaan akhir link/diff/source-only.
2. Tetapkan commit final dan update provenance/checkpoint.
3. Serahkan prompt penerus.
4. Berhenti menunggu persetujuan pengguna sebelum implementasi source.
5. W1/W6, Portal Guru/Siswa, Android, deployment, database dan Device Lease/Fencing tetap HOLD sesuai scope.


## Klarifikasi desain terdahulu — SUPERSEDED
Catatan OPEN/PROPOSED tentang semantik PENDING/CONFIRMED/ACTIVE telah digantikan oleh keputusan final pengguna, Addendum 35, dan DEC-WA-026. Jangan gunakan catatan OPEN lama sebagai status keputusan saat ini.


## Verifikasi final pasca-klarifikasi
Compare lengkap dari base W0-recheck ke HEAD awal branch konsolidasi 55cfe325aef13abd4dae97c7d46e127a0ff47bbb: 28 path berubah/ditambahkan, semuanya docs/; tidak ada source aplikasi. Rincian: 12 dokumen kerja (6 added, 6 modified), 16 backup/manifest, 0 deleted, 0 source aplikasi. Angka 18 dan 24 adalah compare parsial sebelum seluruh backup finalization selesai. Backup final laporan/checkpoint tersedia di `docs/backup/reconciliation-2026-11-21/final/`.


## Keputusan final Proktor — ACTIVE
- PENDING: tidak memberikan capability.
- CONFIRMED: satu-satunya status yang dapat memberi capability setelah validasi Guru aktif, TeacherProfile, tenant, exam, rombel, schedule revision, permission/scope, revoke/conflict, dan waktu server terpenuhi.
- ACTIVE bukan status assignment.
- NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED: tidak memberikan capability.
- Interval: `starts_at <= serverNow < expires_at`, divalidasi pada setiap request.
- Dokumen normatif: Addendum 35 spesifikasi, DEC-WA-026 register, nota keputusan final. Addendum 34 dan nota OPEN sebelumnya SUPERSEDED.

## Status finalisasi sesuai Master Prompt
- Branch hasil tetap `docs/canonical-reconciliation-admin-guru-20261121`; tidak dibuat branch baru.
- Branch HEAD awal yang dibandingkan: `55cfe325aef13abd4dae97c7d46e127a0ff47bbb`.
- PR #5/#6/#7 OPEN/Draft/Unmerged; tidak disentuh. PR #1/#3/#4 OPEN/Draft; PR #2 CLOSED/Unmerged.
- 28 path docs/ berubah/ditambahkan, 0 dihapus; 12 dokumen kerja dan 16 backup/manifest; 0 source aplikasi.
- Backup finalisasi disimpan di `docs/backup/reconciliation-2026-11-21/finalization/`, blob salinan identik dengan blob file sumber sebelum update.
- Matriks audit 23 modul dipertahankan. Matriks tes T-001–T-168 dipertahankan dan T-169–T-173 ditambahkan. T-114/T-160 diperbaiki. Seluruh tes belum dijalankan tetap NOT RUN.
- Semua temuan source tetap OPEN. CI/runtime/browser/MySQL concurrency/import/restore/UAT: NOT RUN.
- Source, migration, seeder, database, .env, PR, merge, rebase, force-push, dan deployment tidak diubah/dijalankan.
