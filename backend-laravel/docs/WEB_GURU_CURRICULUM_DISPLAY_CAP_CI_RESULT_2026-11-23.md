# Hasil CI — Koreksi Batas Global Outcome Kurikulum Web Guru
Tanggal: 23 November 2026

## Source yang diuji
- Branch: `work/web-guru-remove-xlsx-20261123`
- Checkout SHA: `91d8f6989c29c40ef836b244e0d0850bc22f4a99`
- Current HEAD setelah laporan/manifest: `c72229a8496c19b1230db05f7c1da940c607f0d6`
- Compare current HEAD dengan SHA uji menunjukkan hanya satu file manifest backup ditambahkan setelah CI; tidak ada source/test/workflow berubah.

## Perubahan yang diuji
1. `TeacherCurriculumContextService` menghitung slot sisa dari total outcome yang sudah ditampilkan sehingga maksimum 100 entri berlaku secara global lintas seluruh versi terdaftar.
2. Agregat jumlah CP/TP/ATP/KBC tetap menghitung semua outcome aktif; yang dibatasi hanya daftar detail yang dirender.
3. Regression test membuat dua versi kurikulum dengan 60 CP per versi:
   - Total CP harus tetap 120.
   - Detail outcome yang dikirim ke UI harus tepat 100.
   - Versi pertama seluruh 60 entri ditampilkan.
   - Versi kedua ditampilkan sampai entri ke-40; entri ke-41 tidak ditampilkan.

## Hasil final
GitHub Actions run [45109872421](https://github.com/zane0184/cbt_november/actions/runs/45109872421):
- Status: **SUCCESS**
- Checkout log membuktikan SHA yang diuji persis `91d8f6989c29c40ef836b244e0d0850bc22f4a99`.
- PHPUnit: **118 passed, 520 assertions**, durasi 16.32 detik.
- PHP lint: PASS.
- Laravel route registration: PASS.
- Composer validate, locked dependency installation, platform requirements: PASS.
- Disposable MySQL 8 identity/schema preflight: PASS.
- Migration test: **80 base tables, seluruhnya InnoDB**.
- Disposable database/user dibersihkan oleh workflow.

## Koreksi setelah CI gagal
- Run awal [45109597139](https://github.com/zane0184/cbt_november/actions/runs/45109597139) gagal di PHP lint karena namespace service pada test baru tertulis dengan double backslash.
- Source test yang gagal di-backup sebelum koreksi; backup SHA MATCH.
- Namespace diperbaiki; run final 45109872421 lulus.
- Run service sebelum test baru [45109578855](https://github.com/zane0184/cbt_november/actions/runs/45109578855) juga SUCCESS dengan 117 passed / 514 assertions pada service code yang sama.

## Backup
- Dua file sebelum perubahan: 2/2 blob SHA MATCH.
- Versi test yang gagal sebelum koreksi sintaks: 1/1 blob SHA MATCH.
- Total snapshot: **3/3 MATCH**.
- Manifest:
  - `.github/backups/web-guru-curriculum-cap-20261123/MANIFEST.md`
  - `.github/backups/web-guru-curriculum-cap-20261123/MANIFEST_SYNTAX_FIX.md`

## Batas
CI membuktikan koreksi batas tampilan dan suite Web Guru terpilih, bukan kelengkapan integrasi kurikulum. Belum ada perubahan/approval kontrak materi-topik, mapping QuestionVersion, snapshot provenance, Pembelajaran Mendalam, 8 dimensi profil lulusan, DOCX, browser E2E, UAT atau production readiness.

## Status
**Koreksi global cap 100 outcome: IMPLEMENTED + CI PASS 118/520 pada SHA yang tercantum.**
Tidak ada migration, perubahan shared schema/service, merge, deployment, atau DB produksi.
