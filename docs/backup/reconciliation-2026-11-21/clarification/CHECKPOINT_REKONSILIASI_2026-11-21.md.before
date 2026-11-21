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
- Link entry point 17/17 PASS.
- 23 modul audit tetap ada; T-001–T-132 dipertahankan; T-133–T-168 ditambahkan.
- Backup 6/6 cocok dengan blob sumber; SHA-256 dicatat pada manifest.
- Compare menunjukkan hanya 18 path docs/ yang berubah/ditambahkan; tidak ada source aplikasi.
- CI, runtime, browser, MySQL concurrency, import/restore, UAT: NOT RUN.

## Gerbang berikutnya
1. Selesaikan pemeriksaan akhir link/diff/source-only.
2. Tetapkan commit final dan update provenance/checkpoint.
3. Serahkan prompt penerus.
4. Berhenti menunggu persetujuan pengguna sebelum implementasi source.
5. W1/W6, Portal Guru/Siswa, Android, deployment, database dan Device Lease/Fencing tetap HOLD sesuai scope.
