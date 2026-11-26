# Checkpoint Perbaikan Import DOCX Web Guru — 26 November 2026

## Ruang lingkup
Hanya perbaikan komponen Import DOCX Web Guru. Tidak menjalankan pekerjaan atau menunggu agent lain. Tidak menjalankan CI, PHPUnit, maupun pengujian backend di luar DOCX.

## AUDIT
Source diperiksa pada branch `audit/web-guru-scope-reconciliation-20261126`:
- `backend-laravel/app/Livewire/Teacher/QuestionDocxImport.php`
- `backend-laravel/resources/views/livewire/teacher/question-docx-import.blade.php`
- `backend-laravel/app/Services/Questions/DocxQuestionParser.php`
- `backend-laravel/app/Services/Questions/DocxQuestionDraftImporter.php`
- `backend-laravel/app/Services/Questions/DocxQuestionTemplate.php`
- `backend-laravel/routes/teacher.php`

Temuan langsung pada komponen Livewire:
1. Regex hook `updated()` salah escape, sehingga perubahan `question_type` dari kartu preview tidak terdeteksi dengan benar.
2. Validasi nested mewajibkan field pada semua kartu, termasuk soal yang sengaja tidak dicentang untuk diimpor. Ini dapat menggagalkan import soal valid hanya karena ada kartu yang dikecualikan.
3. Preview lama tetap tersimpan ketika penugasan diganti atau parsing file baru gagal; ada risiko hasil preview lama tampil/terbawa ke konteks baru.

## IMPLEMENTASI
File yang diubah:
- `backend-laravel/app/Livewire/Teacher/QuestionDocxImport.php`

Perubahan:
1. Memperbaiki regex event Livewire agar pergantian jenis soal pada kartu preview dikenali.
2. Mengosongkan hasil preview sebelumnya ketika penugasan berubah.
3. Mengosongkan preview lama sebelum parsing upload baru, sehingga kegagalan parsing tidak meninggalkan hasil lama yang tampak masih aktif.
4. Mengikat preview ke ID penugasan saat preview dibuat; import ditolak jika konteks penugasan berubah.
5. Validasi struktur kartu yang belum dicentang dibuat opsional pada lapisan UI; seluruh kartu yang dipilih tetap divalidasi oleh `DocxQuestionDraftImporter` sebelum penyimpanan.
6. Tidak menambah dependency, migration, tabel, route, controller, maupun subsystem import baru.

## BACKUP
Branch backup dibuat sebelum perubahan:
- `backup/docx-import-before-fix-20261126`
- SHA file asli: `1f4431ca52dd9d3e19b45c7bcca0ec204fb82a76`

## VERIFIKASI
- GitHub membuktikan branch kerja hanya berbeda satu file dari branch audit asal.
- File berubah: 1.
- Diff: 39 penambahan, 10 penghapusan.
- SHA file sesudah perubahan: `144784b5fab5e19dccddc7098f09ff494d9a03a0`.
- CI, PHPUnit, dan runtime browser tidak dijalankan sesuai instruksi ruang lingkup.
- Belum ada fixture DOCX aktual yang dijalankan pada runtime dalam sesi ini. Karena itu, hasil ini berstatus **perbaikan source tercommit; perilaku runtime belum terverifikasi**, bukan klaim Import DOCX siap produksi.

## TAUTAN
- Source hasil perbaikan: https://github.com/zane0184/cbt_november/blob/work/docx-import-fix-20261126/backend-laravel/app/Livewire/Teacher/QuestionDocxImport.php
- Branch backup: https://github.com/zane0184/cbt_november/tree/backup/docx-import-before-fix-20261126
- Branch kerja: https://github.com/zane0184/cbt_november/tree/work/docx-import-fix-20261126

## LANGKAH DOCX BERIKUTNYA
Lakukan uji langsung di halaman Guru dengan DOCX fixture: PG, PGK, Benar/Salah, uraian, dokumen berisi tabel, teks Arab/harakat, serta dokumen rusak/relasi eksternal. Catat hasil per kasus. Jangan mengaktifkan penyimpanan gambar/rumus Word sebelum parser dan storage media benar-benar mendukungnya.
