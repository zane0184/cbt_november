# Perbaikan Kontras Preview Import DOCX — 2026-11-26

## AUDIT
- Screenshot pada halaman `/guru/bank-soal/import-docx` menunjukkan textarea teks soal dan input pilihan jawaban berlatar gelap dengan teks gelap, sehingga sulit dibaca.
- View `resources/views/livewire/teacher/question-docx-import.blade.php` memberi warna teks slate tetapi sebelumnya tidak menetapkan `bg-white` pada field teks soal dan pilihan jawaban. Tampilan menjadi bergantung pada style bawaan browser/tema.
- Area rich preview Word sendiri sudah memakai `bg-white`; yang tampak gelap pada screenshot adalah field editor soal/pilihan.

## DESAIN
- Tetapkan warna latar putih dan teks slate gelap secara eksplisit pada seluruh field teks yang dapat diedit di kartu preview.
- Pertahankan layout, parser DOCX, isi hasil ekstraksi, format tebal/miring/garis bawah, serta dukungan RTL.

## IMPLEMENTASI
- Backup: `backend-laravel/backups/docx-preview-contrast-fix-20261126/question-docx-import.blade.php.before`.
- File diubah: `backend-laravel/resources/views/livewire/teacher/question-docx-import.blade.php`.
- Field teks soal: tambah `bg-white`, `text-slate-900`, placeholder slate.
- Input pilihan jawaban dan pernyataan: tambah `bg-white`, `text-slate-900`, placeholder slate.
- Commit: `994e2d5a669eb11e2d5b200938ccb87a4ea0427e`.

## PENGUJIAN
- Fetch ulang source setelah commit: PASS.
- Verifikasi empat field teks (teks soal, stimulus, pilihan, pernyataan): semuanya menetapkan `bg-white`; teks soal/pilihan/pernyataan menggunakan teks slate gelap.
- Belum ada browser UAT pada domain madrasah; hasil visual setelah deploy belum dikonfirmasi.
- Parser, data DOCX, database, dan VPS produksi tidak disentuh.
