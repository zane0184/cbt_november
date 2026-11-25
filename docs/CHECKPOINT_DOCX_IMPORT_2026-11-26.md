# Checkpoint DOCX Import — 26 November 2026

- Branch aktif: `work/guru-docx-rich-content-20261126`
- Base source: `ac2b7539bc184f200199aa502ef56d567dc47b36`
- Patch terbaru: `63426a85148ebf384be862eac22c2962a1f414c0`
- Fokus eksklusif: DOCX import.
- Backup parser tersedia dan sudah diverifikasi identik: `backend-laravel/backups/docx-rich-content-20261126/DocxQuestionParser.php`.
- Sudah diperbaiki: stimulus bersama tetap aktif melewati header kunci jawaban; blok media/formula trailing diasosiasikan ke soal terakhir.
- Belum selesai: tes Arab/harakat, CI pascapatch, ekstraksi & penyimpanan media, OMML, RTL, tabel/nested table, rich preview/revalidation.
- Jangan menganggap fitur gambar/formula siap: importer masih fail-closed dan pipeline media belum dibangun.
- Jangan menyatakan pekerjaan selesai sebelum PHPUnit MySQL 8 lulus dan acceptance DOCX untuk teks biasa, tabel, gambar, formula, Arab/RTL serta preview-import diuji.
- Jangan menyentuh kunci jawaban/bobot nilai atau modul di luar DOCX import.
