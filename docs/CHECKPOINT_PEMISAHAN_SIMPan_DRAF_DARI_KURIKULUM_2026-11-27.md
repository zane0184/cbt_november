# CHECKPOINT — Pemisahan Simpan Draf dari Pemetaan Kurikulum
Tanggal: 27 November 2026
Repository: zane0184/cbt_november
Branch: fix/manual-question-optional-curriculum-20261127

## AUDIT
Pemeriksaan source menunjukkan bahwa UI menampilkan CP dan TP/ATP sebagai opsional, tetapi jalur simpan manual tetap memanggil QuestionVersionCurriculumMappingService::sync() tanpa syarat, termasuk saat kedua daftar kosong. Jalur impor DOCX juga memanggil layanan yang sama saat daftar pemetaan kosong. Dengan demikian, jalur simpan draf masih melewati service pemetaan kurikulum meskipun status akhirnya NOT_MAPPED.

## DESAIN
- Penugasan Guru dan mata pelajaran tetap wajib untuk otorisasi tenant, scope mapel, dan kepemilikan soal.
- CP dan TP/ATP tidak wajib untuk menyimpan DRAFT.
- Jika keduanya kosong, simpan langsung sebagai NOT_MAPPED tanpa memanggil layanan pemetaan kurikulum.
- Jika salah satu/dua sisi pemetaan dipilih, layanan kurikulum tetap dipanggil sehingga pasangan CP + TP/ATP dan validasi resmi tetap berlaku.
- Jika mengedit draft yang sebelumnya memiliki mapping lalu dikosongkan, hapus mapping CP, outcome, dan struktur agar tidak ada data mapping lama yang tertinggal.
- Fitur pemetaan kurikulum tidak dihapus dari sistem.

## IMPLEMENTASI
1. Backup sebelum perubahan:
   - backend-laravel/backups/question-save-no-curriculum-service-20261127/QuestionBank.php
     Blob SHA: ba86aa0b898127a43ac7e82f7af2927321f4b01d
   - backend-laravel/backups/question-save-no-curriculum-service-20261127/DocxQuestionDraftImporter.php
     Blob SHA: 8f64ca028c74f20d3d822f7f0b54ba8bbb7c7787
2. Source diperbarui:
   - backend-laravel/app/Livewire/Admin/QuestionBank.php
     Blob SHA: a44e0930d7659656747437fa0258062d7e2079ba
     Jalur tanpa mapping tidak lagi memanggil QuestionVersionCurriculumMappingService; membersihkan tiga tabel mapping berdasarkan tenant dan versi; menulis audit NOT_MAPPED; metadata versi tetap dicatat.
   - backend-laravel/app/Services/Questions/DocxQuestionDraftImporter.php
     Blob SHA: f971bb6f584c1b3024d7a0d42a9ad810eb34d460
     Import DOCX tanpa mapping tidak lagi memanggil layanan pemetaan; versi baru tetap diberi metadata NOT_MAPPED.
3. Seluruh perubahan berada pada branch kerja; belum dinyatakan merge atau terpasang di VPS.

## PENGUJIAN
- Verifikasi fetch ulang source dan backup melalui GitHub: PASS.
- Verifikasi SHA source/backup cocok dengan hasil commit: PASS.
- PHPUnit/Livewire test: BELUM DIJALANKAN.
- Browser/klik tombol Save di VPS: BELUM DIUJI.
- Integrasi impor DOCX di VPS: BELUM DIUJI.
- Tidak ada perubahan database, migration, atau konfigurasi VPS.

## BATASAN DAN LANGKAH BERIKUTNYA
Patch ini memutus pemanggilan service pemetaan saat CP dan TP/ATP kosong, tetapi belum membuktikan tombol Save berhasil pada instalasi VPS. Pastikan file pada VPS benar-benar diperbarui dari branch ini, bukan hanya patch service sebelumnya. Setelah deployment ke staging, uji simpan soal manual tanpa CP/TP/ATP dan impor DOCX tanpa pemetaan. Jika gagal, ambil pesan validasi Livewire dan potongan storage/logs/laravel.log pada waktu percobaan. Jangan jalankan migration produksi.
