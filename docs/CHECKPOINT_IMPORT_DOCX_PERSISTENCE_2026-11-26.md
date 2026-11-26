# CHECKPOINT — IMPORT SOAL DOCX / GITHUB
Tanggal: 26 November 2026

- Baseline aktif CP/TP/ATP: CBT_MADRASAH_BACKEND_REVISI_CP_TP_DOCX_FINAL_2026-11-26(1).zip
- SHA-256: 9de8dab2590ac2070b69f520a13b927bcfda1573626cdd834650421a45dfc55d
- Repo: zane0184/cbt_november
- Branch kerja dibuat dari main: fix/import-docx-persistence-20261126
- Tree main yang diperiksa berisi 43 entries; source aplikasi Import DOCX dari ZIP belum ada di branch sumber yang diperiksa.
- Belum ada source ZIP yang diunggah dan belum ada commit aplikasi. Koneksi GitHub pada sesi ini menyediakan operasi teks/blob/tree, tetapi tidak menyediakan upload binary ZIP atau direktori lokal utuh.
- ZIP asli tidak diubah. Audit lokal diisolasi pada workspace.
- PHP lint 10 file terkait: PASS.
- PHPUnit/CI/runtime: NOT RUN. ZIP tidak menyertakan tests/phpunit.xml/PHPUnit binary; PHP runtime lokal tidak memiliki ekstensi DOM, ZIP, mbstring, pdo_mysql.
- Tidak ada patch aplikasi yang dipertahankan; eksperimen logging sementara dikembalikan ke baseline.
- Akar masalah belum terkonfirmasi; tidak ada klaim perbaikan.

## Langkah berikutnya
1. Sinkronkan source ZIP aktif ke branch kerja dengan mekanisme upload folder/repository, tanpa .env, dump DB, kredensial, atau vendor.
2. Tambahkan PHPUnit feature tests Import DOCX dan workflow MySQL disposable yang berjalan pada branch ini.
3. Reproduksi import, identifikasi exception aktual, dan perbaiki akar masalah saja.
4. Verifikasi persistensi questions, question_versions, question_options, batch, rollback, dan idempotensi.
