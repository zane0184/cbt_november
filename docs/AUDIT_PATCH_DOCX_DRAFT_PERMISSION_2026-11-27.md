# Audit & Patch — DOCX Draft Permission Gate (2026-11-27)

## AUDIT
Pada branch integrasi `integration/zip-vps-php83-20261126`, dua lapisan otorisasi import DOCX sama-sama mensyaratkan `curriculum.manage`:
- `QuestionDocxImport::authorizeAccess()`
- `DocxQuestionDraftImporter::import()`

Padahal penyimpanan DRAFT sudah dirancang tidak mewajibkan mapping CP dan TP/ATP. Guru yang memiliki izin `question.view` dan `question.manage`, tetapi tidak memiliki `curriculum.manage`, tetap ditolak sebelum proses simpan.

## DESAIN
Import dan penyimpanan DRAFT hanya membutuhkan:
- akun dengan role tepat `GURU`;
- tenant aktif;
- profil Guru yang valid;
- izin `question.view` dan `question.manage` pada komponen Livewire;
- izin `question.manage` pada service importer;
- penugasan mata pelajaran aktif milik Guru tetap divalidasi di service importer.

Hak pengelolaan kurikulum tidak diberikan secara implisit. Fitur pemetaan CP/TP/ATP tetap menggunakan otorisasi kurikulum pada alur pemetaan tersendiri.

## IMPLEMENTASI
Menghapus syarat `curriculum.manage` dari kedua gerbang otorisasi import DOCX. Pesan penolakan diperbarui agar sesuai dengan persyaratan baru.
Tidak mengubah validasi tenant, role, profil, penugasan, validasi isi soal, transaksi, audit log, media, atau idempotensi.

Backup sebelum perubahan berada di:
`backend-laravel/backups/docx-draft-permission-20261127/`

## PENGUJIAN
- Pemeriksaan source statis: kedua file sudah tidak memiliki referensi `curriculum.manage` pada gerbang import.
- PHPUnit: tidak dijalankan.
- CI: tidak dijalankan.
- Runtime/VPS: tidak dijalankan.
Karena itu belum ada klaim bahwa tombol Simpan sudah berhasil pada VPS.

## BATASAN
Patch ini menghapus penghalang otorisasi kurikulum pada jalur simpan DOCX DRAFT. Patch ini belum mengubah aturan penerbitan soal/ujian yang mungkin masih mensyaratkan mapping kurikulum. Aturan penerbitan dan Exam Builder harus diaudit terpisah sebelum menyatakan soal tanpa CP/TP/ATP dapat diujikan.
