# Checkpoint Perbaikan Simpan Import DOCX Web Guru — 26 November 2026

Repository: `zane0184/cbt_november`
Branch kerja: `fix/docx-draft-save-root-cause-20261126`
Commit source saat checkpoint: `6647404a3072214e0d7ced61d4a6e6901f39a13d`

## AUDIT
PR #32, #33, #39 dan source branch integrasi ditelusuri sampai jalur `QuestionDocxImport::importDrafts()`, `DocxQuestionDraftImporter::import()`, `QuestionVersionCurriculumMappingService::sync()`, penyimpanan media, batch idempotensi, serta query Bank Soal.

Akar masalah yang ditemukan:
1. Kartu hanya disimpan jika status preview browser `READY`. Parser memberi `NEEDS_REVIEW` untuk konten/struktur yang memang dapat diperbaiki Guru atau disimpan sebagai DRAFT dengan peninjauan manual (misalnya rumus OMML dan tabel bersarang). Status parser tidak dihitung ulang setelah Guru mengedit kartu.
2. Importer mewajibkan pemetaan CP dan TP/ATP sebelum membuat DRAFT. Service pemetaan juga mensyaratkan provenance CP/TP dan struktur Panca Cinta, Pembelajaran Mendalam, serta dimensi profil lulusan. Kegagalan validasi mapping me-rollback transaksi Question/QuestionVersion/opsi/media/batch secara keseluruhan.
3. Status `READY` dari Livewire/browser tidak layak menjadi otoritas final; save harus memakai parse server terbaru dan field edit Guru yang diizinkan.
4. Migration batch DOCX dan metadata tersedia dalam source: `2026_11_25_020001_add_question_version_metadata_and_docx_import_batches.php`. Penerapannya pada database VPS tidak diperiksa.

## DESAIN
- Re-parse file di server pada saat Simpan.
- Gunakan browser state hanya untuk include/exclude dan field edit Guru yang diizinkan.
- Validasi ulang struktur soal melalui importer, bukan status READY dari browser.
- DRAFT boleh disimpan dengan pemetaan kurikulum tertunda. Mapping resmi tetap ketat; jika gagal validasi, tidak ada mapping palsu/parsial yang disimpan.
- Publikasi tetap ditolak sampai mapping CP/TP/ATP resmi dan review konten terpenuhi.

## IMPLEMENTASI
File runtime yang berubah:
- `backend-laravel/app/Livewire/Teacher/QuestionDocxImport.php`
- `backend-laravel/app/Services/Questions/DocxQuestionDraftImporter.php`
- `backend-laravel/app/Livewire/Admin/QuestionBank.php`
- `backend-laravel/resources/views/livewire/teacher/question-docx-import.blade.php`

Perubahan inti:
- Status READY browser tidak lagi dipakai untuk memilih baris impor.
- Source identity, media, OOXML, blocked features, dan alasan review diambil dari parse server terbaru.
- Field yang diedit Guru digabung secara allowlist dan divalidasi ulang.
- Soal yang lolos validasi dasar dapat disimpan sebagai DRAFT walaupun pemetaan CP/TP/ATP belum lengkap atau belum valid; metadata menandai `PENDING_TEACHER`.
- Mapping yang valid tetap disimpan melalui service resmi. Kegagalan validasi mapping dicatat sebagai pending dan tidak membuat mapping parsial.
- Review parser disimpan pada metadata dan mengaktifkan `requires_manual_review`; publikasi tetap terkunci.
- Setelah Guru melengkapi mapping melalui editor Bank Soal dan validasi berhasil, status metadata diperbarui menjadi `MAPPED`.
- Pesan sukses menyebut jumlah soal yang menunggu mapping.
- PR #39 (error mapping dan loading state) tetap menjadi bagian source final.

## BACKUP
Backup source sebelum patch:
`backend-laravel/backups/docx-draft-save-root-cause-20261126/`
Manifest dan SHA blob dicatat di `BACKUP_MANIFEST.md`.

## PENGUJIAN
Sesuai instruksi:
- PHPUnit tidak dijalankan.
- CI tidak dijalankan.
- Browser/runtime/VPS tidak dijalankan.
Tidak ada klaim transaksi database runtime telah berhasil. Pemeriksaan source statis saja.

## DISTRIBUSI
Paket deployment lengkap sudah dirakit:
- File: `CBT_MADRASAH_WEB_DEPLOYMENT_7cd0823d2fc8.zip`
- Source runtime: commit `7cd0823d2fc81af450ce0acc670cc38cc9148d3d` pada branch `fix/docx-draft-save-root-cause-20261126`.
- Basis paket lengkap dan vendor: artifact deployment yang berhasil dibuat sebelumnya dari commit integrasi `ee38818f89627443704bbda2469cf9b16d89625c`.
- Empat file runtime yang berubah ditimpa dengan source dari branch perbaikan dan diverifikasi byte-for-byte menggunakan Git blob SHA.
- Jumlah file di ZIP: 8.707.
- Ukuran: 17.920.513 bytes.
- SHA-256 ZIP: `262706f9ce6b31a84b76c4994027defb1625e6a036493e5a8c733273ace242ef`.
- Pemeriksaan paket: struktur ZIP utuh, `vendor/autoload.php`, `artisan`, `composer.lock`, `.env.example`, dan migration batch DOCX tersedia; checksum mencakup seluruh file; backup/tests/.env tidak disertakan.
- Pemeriksaan ini adalah integritas arsip dan kelengkapan file, bukan pengujian PHP, PHPUnit, CI, browser, atau runtime VPS.
