# Laporan Implementasi Import Soal Guru melalui Word (.docx)
Tanggal: 25 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/guru-docx-import-e2e-20261125`
Commit kode yang lulus CI lengkap: `f42bcb2eb9a3423bf6a00c3759c0077d5e4474ba`
Workflow CI: `45580930015` — success

## AUDIT

1. Portal Guru belum menyediakan alur import soal Word dari Bank Soal.
2. Menambahkan tombol unggah saja tidak cukup: hasil import harus dapat divalidasi, diedit, dipetakan ke kurikulum, disimpan sebagai draf, diterbitkan, masuk ke immutable exam snapshot, ditampilkan ke peserta, dan dinilai.
3. Mesin ujian belum boleh menerima jenis soal yang kontrak jawaban/snapshot/scoring-nya belum lengkap. `MATCHING` dan `SHORT_ANSWER` tetap diblokir sesuai gate proyek.
4. Template XLSX Import Siswa Admin yang sudah ada dipertahankan; file-file terkait tidak ditimpa.

## DESAIN

- Format utama: Microsoft Word Open XML `.docx`, bukan format biner `.doc`.
- Guru dapat mengunduh template Word dari halaman import.
- Preview memisahkan soal siap impor, perlu koreksi, dan konten yang tidak dikenali.
- Guru dapat mengedit teks soal, opsi, kunci, stimulus, poin, tingkat kesulitan, serta pemetaan CP dan TP/ATP sebelum menyimpan.
- Pemetaan kurikulum dapat ditetapkan sebagai default untuk seluruh soal atau diubah per soal.
- Hasil import selalu dibuat sebagai DRAFT; penerbitan tetap tindakan eksplisit Guru.
- Setiap batch memakai SHA-256/idempotency, transaksi database, validasi tenant dan penugasan, serta audit log.
- Shared stimulus disimpan sebagai metadata versi dengan group ID, versi, dan hash konten, lalu disalin ke snapshot ujian.
- `TRUE_FALSE_GROUP` berisi tepat tiga pernyataan ber-ID stabil dan memakai skor proporsional per pernyataan.
- `ESSAY` tetap memerlukan penilaian manual.

## IMPLEMENTASI

### Portal Guru dan parser Word

- Route halaman: `/guru/bank-soal/import-docx`.
- Tombol Import Soal Word ditambahkan dari Bank Soal Guru.
- Tombol Unduh Template Word menghasilkan file `.docx`.
- Parser membaca paragraf dan teks tabel DOCX secara aman tanpa mengekstrak file ke filesystem.
- Parser mendukung:
  - PG / pilihan ganda.
  - PGK / pilihan ganda kompleks.
  - Benar/Salah tunggal.
  - Benar/Salah kelompok tepat tiga pernyataan.
  - ESSAY.
  - Kunci jawaban yang ditulis setelah soal atau dalam bagian KUNCI JAWABAN di akhir dokumen.
  - Shared stimulus dengan identitas kelompok stabil.
- Kontrol preview mendukung koreksi jenis soal, teks, opsi, kunci, pernyataan, stimulus, poin, kesulitan, dan pemetaan kurikulum.
- Importer membuat Question dan QuestionVersion berstatus DRAFT, opsi, mapping CP/TP, batch idempotency, dan audit log.
- Bank Soal mendukung edit pernyataan Benar/Salah kelompok serta validasi sebelum Guru menerbitkan.
- Snapshot ujian menyalin metadata kelompok dan stimulus secara immutable.
- API peserta menyembunyikan kunci jawaban pernyataan.
- Android/Web exam runner menampilkan stimulus dan tiga pernyataan serta mengirim jawaban per pernyataan.
- Answer API memvalidasi ID pernyataan dan tipe boolean.
- Scoring menghitung nilai proporsional untuk pernyataan yang benar.
- Gate publikasi dan snapshot menolak metadata kelompok yang tidak memiliki tepat tiga pernyataan valid.
- Diagnostik installer mencatat tail output Artisan yang dibatasi untuk membantu investigasi kegagalan migrasi.

### Integritas dan keamanan

- Tenant, role Guru, permission, penugasan mata pelajaran, dan status assignment diperiksa.
- File dibatasi ukuran dan ekstensi; parser memeriksa struktur OOXML/ZIP, ukuran entri, dan XML tanpa akses jaringan.
- Import berjalan dalam transaksi; kegagalan satu soal membatalkan batch.
- File dengan hash dan assignment yang sama tidak dapat diproses ulang.
- Tidak ada migration yang dijalankan pada database produksi.
- Backup source sebelum perubahan dicatat dalam `backend-laravel/backups/guru-docx-import-e2e-20261125/`.

## PENGUJIAN

Workflow `45580930015` berhasil pada kedua matriks:

| Lingkungan | Hasil full PHPUnit |
|---|---:|
| PHP 8.3 + MySQL 8.0/InnoDB | 657 passed, 1 skipped, 3.404 assertions |
| PHP 8.4 + MySQL 8.0/InnoDB | 657 passed, 1 skipped, 3.404 assertions |

Langkah CI yang berhasil:
- Composer validation dan instalasi dependency terkunci.
- Identitas database disposable MySQL 8.0/InnoDB.
- PHP lint seluruh app, migration, dan test.
- `git diff --check`.
- Clean-install Installer E2E pada database disposable terpisah.
- Student Import acceptance test.
- Benchmark cleanup denial-path test.
- Full PHPUnit suite.
- Build Standalone Web CBT Deployment ZIP.

Pengujian tambahan:
- ZIP deployment dapat diuji dan checksum manifest diverifikasi pada proses packaging.
- Browser E2E visual dan pemasangan di VPS belum dijalankan.

## BATASAN YANG DISENGAJA

- `MATCHING` belum dapat diimpor/diterbitkan sampai runtime E2E lengkap.
- `SHORT_ANSWER` tetap diblokir sampai accepted answer tersimpan dalam immutable snapshot dan scoring.
- Gambar, diagram, dan rumus Word belum diekstrak sebagai media/MathML; parser menandai konten yang tidak didukung agar tidak hilang diam-diam.
- Teks tabel dibaca berdasarkan urutan sel; struktur tabel tidak dipertahankan dan preview memberi peringatan.
- Gunakan nomor soal dan label opsi yang diketik langsung (contoh `1.`, `A.`), bukan automatic numbering Word. Format dokumen nyata dari madrasah masih perlu divalidasi setelah pengguna mengirim contoh DOCX.
- ESSAY masuk antrean penilaian manual, bukan auto-score.
- Tampilan konten halaman Import Soal baru menggunakan gaya terang. Desain ulang menyeluruh sidebar/layout Admin dan Guru agar lebih mewah dan profesional masih menjadi tahap terpisah.

## STATUS

Fitur import DOCX telah melewati CI penuh pada PHP 8.3 dan 8.4. Ini bukan pernyataan siap produksi: belum ada browser E2E pada VPS, uji multi-user live, UAT Admin/Guru/Siswa, atau verifikasi operasi madrasah. Paket deployment berikutnya hanya untuk staging dan review UI.
