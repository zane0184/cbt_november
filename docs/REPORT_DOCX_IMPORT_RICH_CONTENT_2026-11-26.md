# Laporan Pekerjaan DOCX Import — 26 November 2026

## Lingkup
Hanya parser dan alur import DOCX Guru. Tidak mengubah alur kunci jawaban, bobot nilai, publikasi, snapshot ujian, maupun modul lain.

## AUDIT
- Source awal: `backend-laravel/app/Services/Questions/DocxQuestionParser.php`, blob SHA `ec271cc9efcfc009798f4d2e2ece9c3b6279c706`.
- Ditemukan dua regresi yang sesuai dengan kegagalan CI sebelumnya:
  1. Saat header kunci jawaban ditemukan, parser mengosongkan stimulus bersama sehingga soal setelahnya kehilangan referensi stimulus.
  2. Blok gambar/formula kosong setelah bagian kunci jawaban tidak lagi memiliki soal aktif dan sebelumnya terbuang.
- Audit lanjutan atas pipeline media, penyimpanan media, rendering rich content, OOXML OMML, RTL, serta tabel bersarang belum selesai.
- Kegagalan tes pelestarian teks Arab/harakat yang pernah tercatat belum terisolasi akar masalahnya.
- Workflow DOCX CI yang ada tidak memicu push pada branch pekerjaan ini; konfigurasi trigger saat ini hanya mencantumkan branch lama dan workflow_dispatch. Tidak ada workflow run untuk branch ini.

## DESAIN
- Stimulus bersama harus bertahan melintasi bagian kunci jawaban; penutupan bagian kunci tidak sama dengan penutupan stimulus.
- Media/formula pada blok yang berurutan setelah soal selesai harus tetap terasosiasi ke soal sumber dan tetap fail-closed sampai ekstraksi, penyimpanan, serta preview teruji.
- Tidak boleh menyimpan gambar/formula sebagai teks palsu atau menebak posisi/relasi OOXML.
- Teks Unicode Arab dan harakat harus dipertahankan apa adanya; RTL harus disimpan sebagai atribut/struktur, bukan mengubah urutan karakter.

## IMPLEMENTASI
- Branch: `work/guru-docx-rich-content-20261126`.
- Backup dibuat sebelum patch:
  `backend-laravel/backups/docx-rich-content-20261126/DocxQuestionParser.php`.
- Backup diverifikasi identik dengan source awal; backup blob SHA sama dengan source blob SHA: `ec271cc9efcfc009798f4d2e2ece9c3b6279c706`.
- Patch commit: `63426a85148ebf384be862eac22c2962a1f414c0`.
- Perubahan:
  - Menghapus reset stimulus saat parser masuk bagian kunci jawaban.
  - Mengaitkan blok media/formula kosong yang berurutan setelah bagian kunci jawaban ke soal terakhir yang telah selesai.
- Belum ada perubahan pada pipeline ekstraksi media, OMML, RTL, atau struktur tabel.

## PENGUJIAN
- Backup equality: PASS (isi file backup identik byte-for-byte dengan source sebelum patch).
- PHPUnit/CI setelah patch: NOT RUN. Workflow saat ini tidak terpicu oleh push pada branch ini dan belum ada workflow run yang dapat dijadikan bukti.
- Tes Arabic/harakat: belum dinyatakan lulus.
- Tes import end-to-end sesudah patch: belum dijalankan.

## Status
PARTIAL — dua perbaikan regresi sudah dikomit, tetapi pekerjaan DOCX import belum tuntas dan belum layak dinyatakan siap. Lanjutan wajib mencakup isolasi tes Arab, konfigurasi CI khusus branch/dispatch, lalu implementasi ekstraksi dan persistensi gambar, OMML, RTL, tabel serta preview/revalidasi sebelum impor.
