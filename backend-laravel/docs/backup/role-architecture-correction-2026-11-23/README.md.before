# CBT Madrasah — Tenant Backend

Repository sumber untuk rekonstruksi CBT Madrasah Tenant.

## Status
- Framework target: Laravel 13 / PHP 8.3.
- Database target: MySQL 8+ (InnoDB).
- Antarmuka web: Blade + Livewire.
- Repository ini adalah workspace source dan pengujian, **bukan bukti siap produksi**.
- Device Lease/Fencing, migration upgrade, pemulihan, concurrency, dan integrasi wajib lulus uji terpisah sebelum dinyatakan siap.

## Aturan keselamatan
1. Jangan pernah commit `.env`, kredensial, private key, dump DB, data siswa, atau backup.
2. Jangan menjalankan migration pada database produksi dari CI.
3. PHPUnit/CI hanya boleh memakai database disposable yang dibuat khusus untuk satu job.
4. Fresh install dan upgrade adalah alur berbeda. Jangan menebak cohort database.
5. Jangan mengedit migration historis 190008/200001; strategi forward-only harus mengikuti checkpoint desain.
6. Jangan menghapus bukti akademik atau histori ujian.
7. Semua perubahan harus disertai backup, audit, pengujian, dan checkpoint.

## Menjalankan lokal
1. Salin `.env.example` menjadi `.env` dan isi konfigurasi lokal sendiri.
2. Jalankan `composer install` sesuai prosedur proyek.
3. Gunakan database disposable khusus pengembangan/pengujian.
4. Baca laporan audit dan checkpoint sebelum menjalankan perintah yang memodifikasi database.

## Batasan status
Hasil lint atau PHPUnit tidak otomatis membuktikan keamanan multi-tenant, integritas MySQL, ketahanan sinkronisasi, Device Lease/Fencing, atau kesiapan operasional madrasah.
