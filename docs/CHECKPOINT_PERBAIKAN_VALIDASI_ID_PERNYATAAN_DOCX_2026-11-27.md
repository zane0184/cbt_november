# CHECKPOINT PERBAIKAN VALIDASI ID PERNYATAAN IMPORT DOCX — 2026-11-27

## AUDIT
- Bukti browser: request POST ke endpoint Livewire `/livewire/update` mendapat HTTP 200.
- Isi Response Livewire memuat error validasi `distinct` pada `questions.17.statements.2.id` dan beberapa `questions.23.statements.*.id`.
- Parser DOCX membentuk ID pernyataan lokal seperti `sa`, `sb`, dan `sc` untuk setiap kelompok TRUE_FALSE_GROUP. ID yang sama lintas kelompok adalah sah.
- Aturan lama `distinct` pada wildcard `questions.*.statements.*.id` membandingkan ID lintas kelompok, sehingga menolak dokumen yang sebenarnya valid.

## DESAIN
- Keunikan ID pernyataan dibatasi pada satu kelompok soal.
- ID yang sama antar kelompok soal berbeda diperbolehkan.
- Duplikasi ID di dalam kelompok yang sama tetap ditolak.
- Tidak mengubah parser, format DOCX, alur soal manual, aturan tenant, atau pemetaan CP/TP/ATP.

## IMPLEMENTASI
- File diubah: `backend-laravel/app/Livewire/Teacher/QuestionDocxImport.php`.
- Backup sebelum perubahan: `backend-laravel/backups/docx-statement-id-distinct-20261127/QuestionDocxImport.php`.
- Backup blob SHA: `a7758a070cb9fc0bee1f5f41a602d77afb939095`.
- Source blob SHA setelah patch: `5a05c1ee2d731599cd970a9ef0a4b48fb950f17a`.
- Commit backup: `8e84b033c74b10c6e2d592d7786202169727ffa7`.
- Commit patch: `57cbd06d7c03d50b21b2af1c00bbf4ec32007859`.
- Aturan `distinct` pada ID anak dihapus; validasi parent array sekarang memeriksa duplikasi ID hanya di dalam array statements untuk satu soal.

## PENGUJIAN
- Verifikasi GitHub setelah penulisan: source dan backup berhasil dibaca ulang; SHA sesuai.
- PHPUnit belum dijalankan.
- Uji integrasi Livewire/browser dan uji simpan DOCX pada VPS belum dijalankan.
- Belum boleh dinyatakan berhasil di VPS atau siap produksi.

## LANGKAH BERIKUTNYA
1. Tambahkan/menjalankan regression test untuk ID `sa/sb/sc` berulang pada kelompok berbeda dan duplikasi dalam kelompok yang sama.
2. Deploy hanya file komponen ini ke staging/VPS setelah backup file di server.
3. Uji DOCX yang sebelumnya gagal; pastikan draft tersimpan dan status tetap DRAFT.
4. Periksa error baru jika masih gagal; jangan mengubah alur soal manual.
