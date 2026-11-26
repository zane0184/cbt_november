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


## ADDENDUM — AUDIT LANJUTAN 27 NOVEMBER 2026

### Status PR dan branch
- Head terverifikasi: `641155faf7f8989f6a5d1008ce4faf76ee68c896`.
- PR #44 tetap OPEN, DRAFT, belum merged.
- Compare terhadap `integration/zip-vps-php83-20261126`: 48 commit ahead, 0 behind.

### Verifikasi snapshot ESSAY
- `QuestionVersion` memiliki field `explanation`.
- `ExamSnapshotService::createSnapshots()` menyalin `$version->explanation` ke `snapshot_explanation`.
- `ExamQuestionSnapshot` memasukkan `snapshot_explanation` ke fillable.
- Halaman koreksi Guru membaca `$qs->snapshot->snapshot_explanation`.
- Dengan demikian sumber rubrik pada halaman koreksi adalah snapshot ujian immutable, bukan versi soal aktif yang bisa berubah.

### Temuan dan perbaikan tambahan
1. `resources/views/livewire/teacher/result-detail.blade.php`: directive penutup validasi `manualScore` salah. Diperbaiki menjadi `@enderror`.
2. `app/Livewire/Teacher/ResultDetail.php`: jalur finalisasi satu attempt dari UI Guru belum menulis audit event. Ditambahkan `FINALIZE_SCORE`.
3. `app/Livewire/Teacher/ResultList.php`: finalisasi massal dari UI Guru belum menulis audit event per score. Ditambahkan `FINALIZE_SCORE` untuk setiap skor yang benar-benar difinalisasi.

### Source dan backup tambahan
- `backend-laravel/app/Livewire/Teacher/ResultDetail.php` — blob SHA `8c23d37df6dc0a87d12948f014f201852222b739`.
- `backend-laravel/app/Livewire/Teacher/ResultList.php` — blob SHA `0c15c2361e0d7941356133f7b4e041ecbcafe527`.
- `backend-laravel/resources/views/livewire/teacher/result-detail.blade.php` — blob SHA `9908f787266899ec5b5c3c274d3f94104a26dfc3`.
- Backup sebelum perubahan tersedia pada `backend-laravel/backups/matching-essay-hardening-20261127/`, dengan nama `result-detail.blade.php.pre-fix-manualScore-error-directive`, `ResultDetail.php.pre-finalize-audit-log`, dan `ResultList.php.pre-finalize-audit-log`.

### PENGUJIAN
- PHPUnit, CI, runtime, dan integration tests tetap tidak dijalankan sesuai instruksi.
- Source dibaca ulang dari GitHub setelah perubahan dan SHA diverifikasi.
- `TenantScoped` pada Attempt dan score models menerapkan filter tenant serta fail-closed tanpa konteks tenant. Policy Attempt membatasi role, permission, tenant, dan akses exam berdasarkan penugasan.
- Belum ada bukti compile/render Blade atau runtime. Regression tests MATCHING/ESSAY belum ditulis.
- Tidak ada database, migration, atau data produksi yang disentuh.

### Berikutnya
- Tulis regression tests MATCHING dan ESSAY tanpa menjalankannya.
- Lanjutkan audit alur koreksi, finalisasi, dan publikasi hasil.
- PR tetap Draft dan tidak di-merge. SHORT_ANSWER tetap nonaktif.
