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


## Verifikasi final terakhir — 21 November 2026

### Branch dan compare
- Branch: `docs/canonical-reconciliation-admin-guru-20261121`.
- HEAD yang dibandingkan pada verifikasi akhir: `3ec270e98fe9e6dfaed5a4a2db3d8a88aae3701d`.
- Base: `docs/web-admin-w0-recheck-20261121` @ `fb058f05264b8d464d687583c53f06fbb7fe54f7`.
- Compare: **49 path** = 39 added + 10 modified + 0 deleted.
- Seluruh 49 path berada di docs/. Tidak ada source aplikasi.
- Rincian: 16 dokumen kerja (6 baru, 10 diperbarui) dan 33 backup/manifest.
- Angka 18, 24, dan 28 adalah snapshot compare parsial sebelumnya; angka final 49 menggantikannya.

### Konsistensi keputusan
- DEC-WA-026 dan Addendum 35: ACTIVE.
- Addendum 34 dan nota OPEN/PROPOSED sebelumnya: SUPERSEDED.
- PENDING deny; CONFIRMED satu-satunya status assignment pemberi capability setelah semua validasi; ACTIVE bukan status assignment; NEEDS_REVIEW/REVOKED/EXPIRED/COMPLETED deny.
- Interval `starts_at <= serverNow < expires_at`, divalidasi setiap request.
- T-001–T-168 tetap utuh; T-169–T-173 ditambahkan; T-114/T-160 dikoreksi.
- 23 modul audit ADM-001–ADM-023 tetap utuh.
- Entry point: 21 referensi lokal, 18 path unik; 18/18 valid.
- Backup finalization: 16/16 salinan cocok blob SHA sumber sebelum perubahan; manifest tersedia.

### Status operasional dan batas
- PR #5/#6/#7 OPEN/Draft/Unmerged; tidak diubah. PR #1/#3/#4 OPEN/Draft; PR #2 CLOSED/Unmerged.
- Source audit read-only tetap menunjukkan gap role OPERATOR/ADMIN, permission Admin CBT, Proktor permanen, overlap, shared schedule service, dan User tenant isolation. Seluruh temuan tetap OPEN.
- Test T-001–T-173 belum dijalankan pada tahap ini: NOT RUN.
- CI, runtime, endpoint HTTP, browser, MySQL concurrency, import/restore, UAT: NOT RUN.
- Source, migration, seeder, database, .env, PR, merge, rebase, force-push, deployment, Android dan W6 implementation tidak disentuh.

**Checkpoint ini menggantikan semua status OPEN/PROPOSED sebelumnya terkait semantik status Proktor dan angka compare 18/24/28.**


## Provenance refresh
- Provenance final addendum commit: `9088bf03ab7b7c5606b7cfe360c4555f84f131f5`.
- Report final compare snapshot: base `fb058f05264b8d464d687583c53f06fbb7fe54f7` → compare HEAD `3ec270e98fe9e6dfaed5a4a2db3d8a88aae3701d`: 49 paths (39 added, 10 modified, 0 deleted), all docs/.
- Checkpoint ini diperbarui setelah laporan dan provenance; branch HEAD aktual setelah commit checkpoint harus diverifikasi melalui ref branch, bukan ditebak dari SHA sebelumnya.
- Backup checkpoint tepat sebelum refresh ini: `docs/backup/reconciliation-2026-11-21/finalization/CHECKPOINT_REKONSILIASI_2026-11-21.md.before-provenance-refresh`, blob SHA `e28aaa697433197e3969d69cdea9eb675a8d04a2`, cocok dengan sumber sebelum perubahan.


## Koreksi final compare dan provenance (snapshot terakhir sebelum checkpoint ini)
- Branch HEAD sebelum pembaruan checkpoint ini: `efa78bc9146ef4ec639b9924b7a4d6a1b624ea4a`.
- Compare base `fb058f05264b8d464d687583c53f06fbb7fe54f7` → HEAD tersebut: **50 path**, 40 added, 10 modified, 0 deleted; seluruhnya docs/, tidak ada source aplikasi.
- Angka 49 pada catatan sebelumnya adalah snapshot sebelum backup tambahan checkpoint dibuat; 50 adalah angka terkini.
- Laporan final compare diperbarui pada commit `30a1235584893626e236ade4ddc54ada171d5c6d`; provenance diperbarui pada commit `efa78bc9146ef4ec639b9924b7a4d6a1b624ea4a`.
- Branch tetap tidak di-merge. Ref branch sesudah commit checkpoint ini harus dibaca langsung untuk HEAD terakhir.


## Compare terkini setelah handover alignment
- HEAD sebelum pembaruan laporan/provenance/checkpoint ini: `2de13d6aac153d133acd99a11cc9350966ad60c4`.
- Compare base `fb058f05264b8d464d687583c53f06fbb7fe54f7` → HEAD tersebut: **54 path**, 42 added, 12 modified, 0 deleted; seluruhnya docs/, tidak ada source aplikasi.
- Rincian: 18 dokumen kerja (6 baru, 12 diperbarui) dan 36 backup/manifest. Dua dokumen handover lama sudah diselaraskan dengan keputusan final dan ruang lingkup seluruh temuan OPEN; dua backup identik blob SHA dibuat sebelum update.
- Angka 50 pada catatan sebelumnya adalah snapshot sebelum dua dokumen handover dan backup-nya dimasukkan; 54 menggantikannya.
- Semua PR tidak diubah; source, database, migration, seeder, .env, dan deployment tidak disentuh.
