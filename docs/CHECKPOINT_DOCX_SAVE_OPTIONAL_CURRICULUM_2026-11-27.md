# Checkpoint — Perbaikan Simpan DOCX DRAFT sampai Ujian Umum (2026-11-27)

Repository: `zane0184/cbt_november`
Branch integrasi: `integration/zip-vps-php83-20261126`
HEAD source terverifikasi: `1030c2a537cb2283df7fb3acabe9bce80d5e0ed7`

## AUDIT

Akar masalah awal impor DOCX:
- Status parser preview `NEEDS_REVIEW` dipakai sebagai hard gate save walaupun Guru telah mengedit struktur soal.
- Importer mewajibkan mapping CP/TP sebelum membuat DRAFT; kegagalan mapping me-rollback seluruh transaksi.
- Otorisasi import DOCX sebelumnya juga mensyaratkan `curriculum.manage`, padahal Guru cukup memiliki hak Bank Soal untuk menyimpan DRAFT.

Setelah jalur DRAFT diperbaiki, audit menemukan pengunci lanjutan:
- Publikasi soal mewajibkan CP/TP dan mapping Panca Cinta/PM/Profil Lulusan.
- Asesmen CBT mewajibkan CP/TP.
- Pemilih soal dan ExamSnapshot hanya menerima soal mapped.
- Akibatnya soal DOCX tanpa mapping tetap tidak dapat dipakai dalam CBT.

## DESAIN

Dua mode terpisah:
1. `NOT_MAPPED`: soal/asesmen umum tanpa mapping CP/TP/ATP/framework. Hanya boleh dipasangkan dengan soal lain yang juga tidak memiliki mapping.
2. `MAPPED`: mapping CP/TP/ATP dan framework harus lengkap dan tervalidasi resmi seperti sebelumnya.

Mapping parsial dan pencampuran dua mode ditolak. Konteks tenant, Guru, assignment aktif, mapel, Rombel, tahun ajaran, semester, registrasi kurikulum aktif, dan versi kurikulum assignment tetap wajib. Jadi bukan seluruh konteks kurikulum dihapus; hanya mapping per soal/asesmen yang menjadi opsional.

## IMPLEMENTASI

### PR #41 — perbaikan jalur simpan DRAFT
Merged ke integration pada commit source `110ff43cc5f93eca1beda299984d19574cbf9ace`.
- Re-parse DOCX di server ketika Simpan.
- State browser hanya dipakai untuk pilihan include/exclude dan field edit yang diizinkan.
- Status READY browser tidak lagi menjadi filter save.
- Mapping CP/TP boleh tertunda pada DRAFT; validasi soal dan review konten tetap berlaku.
- Backup source dan audit tersedia di repository.

### PR #42 — izin Guru
Merged pada commit `90b839209c151e3365a97ddd2439beca79764cb6`.
- Menghapus `curriculum.manage` sebagai syarat import DOCX.
- Role Guru, tenant, profil, `question.view`, `question.manage`, dan penugasan aktif tetap dijaga.

### PR #43 — soal umum dan ujian tanpa mapping
Merged pada commit integrasi akhir `1030c2a537cb2283df7fb3acabe9bce80d5e0ed7`.
- Publikasi soal umum tanpa mapping diizinkan.
- Assessment mapping service menerima dua array CP/TP kosong sebagai `NOT_MAPPED`; mapping parsial ditolak.
- ExamManagement memilih soal unmapped ketika asesmen unmapped.
- ExamSnapshot menerima mode unmapped hanya jika semua soal juga unmapped.
- Mode mapped mempertahankan seluruh provenance, checksum, TP/ATP published/current, Panca Cinta, PM, Profil Lulusan, assignment, dan materi/topik.
- Metadata publikasi dan snapshot menandai status `NOT_REQUIRED` / `NOT_MAPPED` / `MAPPED`.

### Backup

- PR #41: `backend-laravel/backups/docx-draft-save-root-cause-20261126/`
- PR #42: `backend-laravel/backups/docx-draft-permission-20261127/`
- PR #43: `backend-laravel/backups/optional-curriculum-question-exam-20261127/`

Manifest backup tersedia pada masing-masing direktori.

## SOURCE SHA DI BRANCH INTEGRASI

| File | Git blob SHA |
|---|---|
| `QuestionDocxImport.php` | `a7758a070cb9fc0bee1f5f41a602d77afb939095` |
| `DocxQuestionDraftImporter.php` | `c397f4990daad661b17136caf796484ccc06b2ab` |
| `QuestionBank.php` | `e752ec6e58693a791163b9363a3533ab44212a07` |
| `ExamSnapshotService.php` | `fb0128fcebf613e23d2f5d7fdd6f33a433d5a4c6` |
| `ExamManagement.php` | `2704a1c03b3d65e34f772100f70e783b4754ef5b` |
| `AssessmentCurriculumMappingService.php` | `c5a746da59482f4a48cbf29bc5e3f9c78b3f8104` |
| `question-bank.blade.php` | `3c8780e5a2654ca8d22dd16bd945e78026e8fde0` |
| `exam-management.blade.php` | `ea34d793bd321ed37a9ccad4e05f44886937e7b0` |

## PENGUJIAN

Sesuai instruksi pengguna:
- PHPUnit: NOT RUN.
- CI: NOT RUN.
- Runtime/VPS/browser: NOT RUN.
- Migration/database: NOT RUN.
- Pemeriksaan source: dilakukan dengan fetch ulang file branch integrasi dan pencocokan blob SHA.
- Pemeriksaan Blade: jumlah pasangan direktif if/endif, foreach/endforeach, forelse/endforelse seimbang.

Tidak ada bukti bahwa tombol Simpan, publikasi soal, atau penerbitan ujian sudah berjalan pada VPS. Tidak ada klaim siap produksi.

## DISTRIBUSI

ZIP terdahulu `CBT_MADRASAH_WEB_DEPLOYMENT_110ff43cc5f9.zip` hanya memuat source sampai PR #41 (commit 110ff). ZIP itu **tidak memuat** PR #42 dan PR #43 sehingga tidak boleh dipakai sebagai paket final dari source terbaru.

Paket distribusi baru belum dibangun. Harus dibuat ulang dari paket lengkap sebelumnya dengan overlay delapan file runtime yang tercantum di tabel SHA, lalu memperbarui PACKAGE_MANIFEST dan SHA256SUMS. Jangan mengubah vendor atau konfigurasi .env dari baseline tanpa kebutuhan.

## NEXT

1. Bangun ulang ZIP deployment lengkap yang memuat source commit `1030c2a537cb2283df7fb3acabe9bce80d5e0ed7`.
2. Verifikasi struktur ZIP, seluruh SHA256SUMS, file wajib, vendor, migration, dan pengecualian secrets/backup/tests.
3. Jangan jalankan PHPUnit, CI, atau runtime kecuali pengguna mengubah instruksi.
4. Setelah paket selesai, berikan handover instalasi ulang pada staging/VPS dengan backup .env, DB, dan storage terlebih dahulu.
