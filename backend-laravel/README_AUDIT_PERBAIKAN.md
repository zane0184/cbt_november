# CBT Madrasah Tenant — Paket Perbaikan Audit 16 November 2026

## Status paket

Paket ini adalah **hasil perbaikan source untuk Tenant TEST COPY**, bukan pernyataan siap produksi.
Source ini belum menjalani PHPUnit, migrasi MySQL, uji integrasi, concurrency, UAT, atau validasi operasional madrasah.

## Isi dan batasan

- Laravel 13.42.0, PHP target 8.3, Blade/Livewire, MySQL 8+.
- `vendor/` disertakan dari Tenant TEST COPY yang sudah diperbarui sebelumnya.
- `.env` tidak disertakan. Jangan menyalin `.env.example` mentah sebagai `.env` tanpa mengganti seluruh nilai lingkungan.
- `.env.example` memakai nilai template produksi yang lebih aman (`APP_DEBUG=false`, bukan root DB user), tetapi tetap wajib disesuaikan.
- Folder backup internal, log, session, cache runtime, debug script, demo seeder, dan hasil benchmark lokal tidak disertakan.
- File asli dan backup pra-patch disimpan terpisah oleh proses audit; jangan menghapus ZIP sumber yang diunggah pengguna.

## Penempatan aman di aaPanel

1. **Jangan unggah paket ini ke live webroot** `/www/wwwroot/cbt.min15jakarta.sch.id`.
2. Gunakan hanya folder Tenant TEST COPY:
   `/www/backup/CBT_TENANT_SECURITY_TEST_20261116/backend-laravel`
3. Sebelum mengganti isi test copy, buat backup folder saat ini melalui aaPanel dan simpan di lokasi baru yang tidak menimpa `PRE_UPDATE_BACKUP`.
4. Ekstrak paket sehingga `artisan`, `app/`, `bootstrap/`, `config/`, `database/`, `routes/`, `vendor/`, dan `tests/` berada langsung di dalam `backend-laravel/` (hindari folder bertingkat `backend-laravel/backend-laravel`).
5. Pertahankan `.env` lokal bila nanti dibuat khusus untuk test copy; jangan masukkan ke ZIP dan jangan pernah kirim nilainya ke chat.
6. Jangan jalankan migration/seeder/test sebelum preflight membuktikan koneksi Laravel tepat ke database disposable `cbt_test`.

## Migrasi baru

Ditambahkan:
`database/migrations/2026_11_16_230001_protect_exam_snapshot_source_history.php`

Migrasi ini mengganti FK source question/version pada `exam_question_snapshots` dari cascade delete menjadi restrict delete agar penghapusan sumber tidak menghapus snapshot historis. **Migrasi belum dijalankan dan belum diverifikasi di MySQL.** Uji terlebih dahulu pada database disposable MySQL 8.0.45. Jangan jalankan di production sebelum backup, preflight, rencana rollback, dan persetujuan eksplisit.

## Perubahan utama source

- Otorisasi Question API memakai QuestionPolicy; scope dan pagination API divalidasi.
- QuestionPolicy menegakkan tenant, permission, owner, dan assignment Guru; tipe owner tidak dikenal ditolak.
- Role bootstrap memberi `question.view` kepada Admin Madrasah dan Guru, serta `question.manage` hanya kepada Guru.
- Livewire Bank Soal memeriksa permission/policy dan tidak lagi menghapus versi/options secara fisik; aksi hapus menjadi archive + soft delete.
- Importer menolak question ID yang tidak ditemukan atau tidak dapat diakses, memeriksa assignment, dan menangani soal tanpa current version.
- Publikasi lintas madrasah berstatus PENDING; publikasi lokal menggunakan status/scope sesuai skema yang ada.
- Snapshot append mencegah duplikasi berdasarkan `source_question_id` dan menjaga sequence berurutan.
- `.env.example` tidak lagi menggunakan APP_DEBUG=true atau DB_USERNAME=root.

## Pengujian yang telah dilakukan

- PHP syntax lint: 293 file first-party lulus menggunakan PHP 8.4.24 CLI.
- `php artisan --version`: Laravel 13.42.0.
- `php artisan route:list --path=api/v1/questions`: lulus; endpoint `destroy` dan `versions` sekarang memiliki implementasi.
- PHPUnit **tidak dijalankan**: runtime lokal tidak memiliki ekstensi `dom`, `mbstring`, dan `xmlwriter`; PDO MySQL/SQLite juga tidak tersedia.
- Migration MySQL **NOT RUN**.
- Integrasi/HTTP runtime, tenant boundary, multi-device, concurrency, media-file retention, dan UAT **NOT RUN**.

## Langkah berikutnya

1. Pasang hanya pada Tenant TEST COPY.
2. Audit ulang file hasil ekstraksi dan hash.
3. Jalankan preflight koneksi ke `cbt_test`.
4. Uji migration baru dan suite yang relevan pada MySQL disposable.
5. Perbaiki temuan lanjutan sebelum paket dipertimbangkan untuk staging.
