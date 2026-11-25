# Laporan audit awal source Tenant
## AUDIT
- ZIP sumber: `CBT_TENANT_SECURITY_TEST_20261116.zip`
- SHA256: `2a2c78b7a6518d3cf1ef7dc06e7ffd1f06e1af4316e33ac55e6751809882b52e`
- Arsip: 26.157 entri; 147.318.266 byte tidak terkompresi; `testzip()` PASS.
- Folder aktif ditemukan: `backend-laravel/`; backup patch dan `PRE_UPDATE_BACKUP/` ada di root ZIP.
- `.env` aktif tidak ditemukan; `.env.example` ada.
- `vendor/` ikut terarsip (25.657 entri). Tidak ada `node_modules/`.
- Source diekstrak ke workspace terpisah `/mnt/data/CBT_TENANT_WORK_20261116/input/backend-laravel/`; ZIP upload asli tidak diubah.

## PENGUJIAN awal
- PHP CLI lokal: 8.4.24. Ini berbeda dari target server PHP 8.3.
- `php -l` untuk app/bootstrap/config/database/routes/tests: 291 file PASS, 0 parse error.
- Lingkungan lokal tidak memiliki PDO MySQL dan beberapa ekstensi PHP penting (antara lain DOM/mbstring/XMLWriter); PHPUnit dan MySQL integration belum dapat dinyatakan lulus.

## Temuan awal yang perlu ditangani
1. `QuestionController` tidak melakukan authorization per aksi; `show()`/`index()` memuat opsi `is_correct`.
2. `QuestionPolicy` override `view()`/`update()` melewati pemeriksaan permission dan tenant eksplisit; owner_type tidak dikenal diizinkan.
3. Livewire `QuestionBank` membuat `owner_type=SUBJECT`, tetapi API/policy mengharapkan `PERSONAL`/`MADRASAH`; saat edit dapat mengubah current version langsung meski sudah dipakai snapshot.
4. Livewire `deleteQuestion()` menghapus current version dan question. FK snapshot cascade dapat menghapus histori ujian.
5. `QuestionPublicationController` menulis `APPROVED` dengan scope `MADRASAH|ALL_MADRASAH`, sedangkan query discovery mencari `PUBLISHED` dan `TENANT|GLOBAL`.
6. Route `GET /questions/{question}/versions` menunjuk method `versions` yang tidak ada pada controller.
7. `QuestionController::update()` mengakses `$current->status` tanpa null guard dan belum mengunci row untuk versioning.
8. Banyak resource controller akademik/tag tidak memiliki pemeriksaan authorization di controller; perlu memastikan policy/middleware lain benar-benar melindungi sebelum akses.
9. ZIP memuat file debug/utility di root (`check_latest.php`, `debug_auth.php`, `debug_scores.php`, `read_attempts.php`, `smoke_test.php`, `phase6f_seed.php`, `tenant_id}`) yang harus diperiksa apakah dapat diakses publik dan dikeluarkan dari distribusi bila tidak diperlukan.
10. Bootstrapper permission berada di `app/Services/RolePermissionBootstrapper.php`, bukan path `app/Support/Security/` yang tertulis pada catatan lama. Ini koreksi lokasi berdasarkan source ZIP aktual.

## Batasan
Audit ini masih berlangsung. Belum ada patch aplikasi yang dilakukan pada source kerja ini. Jangan deploy ke live webroot. MySQL migration, PHPUnit, runtime HTTP, concurrency, dan end-to-end belum dijalankan.
