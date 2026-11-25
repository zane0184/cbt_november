# Checkpoint CBT Madrasah — Import Soal Guru DOCX
Tanggal: 25 November 2026

## Posisi terakhir
- Branch kerja: `work/guru-docx-import-e2e-20261125`
- Kode lulus CI lengkap: `f42bcb2eb9a3423bf6a00c3759c0077d5e4474ba`
- Workflow CI: `45580930015` — success
- Kedua matriks PHP 8.3 dan 8.4: 657 passed, 1 skipped, 3.404 assertions.
- Installer clean-install E2E dan Student Import acceptance lulus di MySQL 8.0/InnoDB disposable.
- Build deployment ZIP berhasil.

## Pekerjaan yang selesai
1. Halaman Import Soal Word Guru dan unduh template DOCX.
2. Parser DOCX dan preview/validasi.
3. Edit soal, opsi, kunci, stimulus, poin, kesulitan.
4. Pemetaan CP/TP default dan per soal.
5. Import batch atomik/idempotent/audit sebagai DRAFT.
6. Edit Bank Soal dan publikasi Guru.
7. Shared stimulus dan TRUE_FALSE_GROUP pada metadata versi/snapshot.
8. Sanitasi kunci jawaban untuk peserta, UI jawaban, API validasi, dan scoring.
9. Gate publikasi/snapshot untuk tiga pernyataan yang valid.
10. Template XLSX Import Siswa Admin yang sudah ada tetap dipertahankan.
11. Backup dan manifest.

## Batasan / risiko yang masih terbuka
- MATCHING dan SHORT_ANSWER tetap disabled sesuai gate proyek.
- Gambar/diagram/rumus Word belum diimpor; tabel hanya dibaca sebagai teks urut.
- Format DOCX riil dari madrasah belum diuji; automatic numbering Word belum didukung, gunakan label angka/huruf yang diketik langsung.
- Browser E2E dan pemasangan VPS belum dilakukan.
- Desain ulang global agar portal Admin/Guru terang, mewah, dan profesional belum selesai.

## Langkah berikutnya
1. Finalisasi manifest dan checkpoint branch, jalankan CI/build untuk commit dokumentasi terakhir.
2. Unduh artifact deployment terbaru; verifikasi SHA-256, struktur ZIP, README, dan seluruh checksum.
3. Kirim ZIP untuk staging/review, tanpa migration pada database produksi.
4. Minta pengguna menguji halaman Guru dan mengirim satu contoh DOCX nyata bila ada format soal madrasah yang belum terbaca.
5. Lanjutkan audit visual menyeluruh Admin/Guru setelah alur import soal terkonfirmasi.

## Larangan yang tetap berlaku
- Jangan mengklaim siap produksi dari hasil CI/build saja.
- Jangan menjalankan migration di database produksi.
- Jangan menimpa source asli; backup dahulu.
- Jangan mengubah Android/native CBT dalam tahap ini.
