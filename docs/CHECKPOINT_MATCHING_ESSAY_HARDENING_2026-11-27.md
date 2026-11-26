# CHECKPOINT IMPLEMENTASI MATCHING & ESSAY — 27 NOVEMBER 2026

## Status
- Repo: zane0184/cbt_november
- PR: #44 — https://github.com/zane0184/cbt_november/pull/44
- Branch: fix/manual-question-optional-curriculum-20261127
- Base: integration/zip-vps-php83-20261126
- Head: 604fb34978fa982c3e40b6044673f785ef09bf34
- Compare: 41 commits ahead, 0 behind.
- PR masih OPEN, DRAFT, belum merged.
- SHORT_ANSWER tetap dinonaktifkan. Tidak ada perubahan untuk mengaktifkannya.

## AUDIT
1. MATCHING
   - Save manual membuat pasangan kiri/kanan dengan pair_N.
   - API jawaban memvalidasi ID sisi kiri/kanan, melarang ID ganda dan ID di luar snapshot.
   - Scoring memberi nilai proporsional berdasarkan pasangan benar.
   - Sebelumnya, publikasi dan snapshot belum menolak struktur pasangan yang tidak lengkap/tidak seimbang secara eksplisit.
2. ESSAY
   - UI Guru sudah menyediakan kolom Pedoman Penskoran/Rubrik.
   - Snapshot menyimpan explanation sebagai snapshot_explanation.
   - Scoring memberi status MANUAL_REVIEW; ResultDetail sudah memiliki aksi koreksi manual dan ResultList menahan finalisasi saat masih ada review.
   - Sebelumnya rubrik tidak diwajibkan pada save/publish/snapshot dan tidak ditampilkan pada halaman koreksi Guru.

## DESAIN
- MATCHING: setiap soal wajib memiliki minimal dua pasangan; jumlah sisi kiri dan kanan harus sama; setiap key harus unik dan berpasangan satu-satu; konten tidak boleh kosong. Pemeriksaan dilakukan saat simpan, publikasi, dan snapshot.
- ESSAY: explanation digunakan sebagai rubrik/pedoman penskoran. Wajib diisi saat simpan dan diverifikasi kembali saat publikasi serta snapshot. Rubrik immutable dari snapshot ditampilkan pada detail koreksi Guru.
- Tidak mengubah kebijakan penilaian jawaban singkat.

## IMPLEMENTASI
Source diubah:
1. backend-laravel/app/Livewire/Admin/QuestionBank.php
   - Validasi simpan MATCHING diperketat untuk struktur data dan teks pasangan.
   - ESSAY wajib memiliki pedoman penskoran/rubrik saat simpan.
   - Gerbang publikasi memeriksa pasangan MATCHING satu-satu dan menolak ESSAY tanpa rubrik.
   - Blob SHA: 7897fb8bcd9bcceefc776c0b9674b243cc527ff9
2. backend-laravel/app/Services/ExamSnapshotService.php
   - Snapshot menolak MATCHING dengan pasangan yang tidak lengkap/tidak konsisten.
   - Snapshot menolak ESSAY tanpa rubrik.
   - Blob SHA: ec39113be70b303011ecada47de164e45d80675f
3. backend-laravel/resources/views/livewire/admin/question-bank.blade.php
   - Menampilkan error validasi rubrik ESSAY.
   - Blob SHA: be8927670eca1d1114c5bf3d6dedba967f279863
4. backend-laravel/resources/views/livewire/teacher/result-detail.blade.php
   - Menampilkan rubrik ESSAY dari snapshot pada halaman koreksi Guru.
   - Blob SHA: 2b297970e90a54bd7b09613d62a417320a688093

## BACKUP
Semua backup dibuat sebelum perubahan source:
- backend-laravel/backups/matching-essay-hardening-20261127/QuestionBank.php.pre-matching-essay-hardening
- backend-laravel/backups/matching-essay-hardening-20261127/ExamSnapshotService.php.pre-matching-essay-hardening
- backend-laravel/backups/matching-essay-hardening-20261127/question-bank.blade.php.pre-rubric-error
- backend-laravel/backups/matching-essay-hardening-20261127/result-detail.blade.php.pre-show-essay-rubric

## PENGUJIAN
- Tidak menjalankan PHPUnit, CI, runtime, atau integration tests sesuai instruksi pengguna.
- Belum ada bukti runtime bahwa perubahan berfungsi end-to-end.
- Verifikasi yang dilakukan: source terbaru berhasil dibaca ulang dari GitHub, SHA cocok, compare branch berhasil, backup terbuat.
- Tidak ada database, migration, atau data produksi yang disentuh.

## Pekerjaan berikutnya
- Tambahkan regression tests untuk struktur MATCHING dan rubrik ESSAY (hanya tulis, jangan jalankan sampai instruksi berubah).
- Audit konsistensi UI koreksi, finalisasi nilai, dan publikasi hasil lintas tenant/role.
- Lanjut audit tipe soal lainnya serta alur DOCX.
- PR #44 jangan di-merge sebelum audit menyeluruh dan izin pengguna.
