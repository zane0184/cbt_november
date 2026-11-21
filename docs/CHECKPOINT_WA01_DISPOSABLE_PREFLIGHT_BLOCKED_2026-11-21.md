# Checkpoint WA-01 — Disposable MySQL Preflight Terblokir
Tanggal pemeriksaan: 2026-11-21 (UTC)

## Status
**BLOCKED — preflight belum memenuhi syarat. Tidak ada migration atau PHPUnit yang dijalankan. WA-01 belum PASS.**

## AUDIT
Branch: `work/wa01-package0-implementation-20261121`
HEAD branch saat checkpoint ini dibuat: `1215c6ba5ededc0a2ab44f7230f867cbeeab9c2e`
Parent/source commit yang dipertahankan: `11ab8e437af66fd6f35af81dfbcdec4fd391567f`

Pemeriksaan runtime container:
- PHP tersedia: 8.4.24 CLI, bukan PHP 8.3.
- Composer: tidak tersedia.
- MySQL client/server: tidak tersedia.
- Docker/Podman: tidak tersedia.
- Workspace lokal `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/`: tidak tersedia pada container ini.
- Modul PHP yang tampak tersedia tidak mencakup `pdo_mysql`, `mbstring`, atau `pdo_sqlite`.
- Tidak ada MySQL disposable yang dibuat atau dikonfigurasi. Karena itu hostname/server, nama database, user, versi MySQL, engine, dan konfigurasi koneksi belum dapat diverifikasi; tidak boleh diisi dengan asumsi.

## DESAIN / BATAS PENGUJIAN YANG DISETUJUI
Persetujuan pengguna hanya mencakup migration otomatis melalui Laravel `RefreshDatabase` pada database MySQL 8/InnoDB disposable yang dibuat khusus, diverifikasi, dan memakai user terbatas hanya pada database tersebut.

Tetap dilarang:
- Menjalankan `RolePermissionBootstrapper::bootstrap()`.
- Menjalankan seeder.
- Mengubah permission database di luar cakupan tes yang disetujui.
- Menyentuh database produksi/VPS atau melakukan deployment.
- Mengubah lifecycle Guru atau CBT Engine di luar scope WA-01.
- Mengubah source hanya untuk melewati pengujian.

Test yang memanggil `RolePermissionBootstrapper` harus dikecualikan/dipisahkan.

## IMPLEMENTASI
Tidak ada source aplikasi yang diubah. Tidak ada database yang dibuat atau disentuh. Checkpoint ini hanya dokumentasi.

## PENGUJIAN
Belum dijalankan:
- PHP lint pada runtime target PHP 8.3.
- PHPUnit WA-01 subset tanpa RolePermissionBootstrapper.
- Livewire runtime.
- API authorization.
- Tenant isolation pada MySQL 8/InnoDB.
- Regresi Guru dan Siswa.

Alasan: runner dan database disposable belum tersedia; preflight gagal. Tidak ada log test/failure karena tidak ada test yang dieksekusi.

## Gerbang untuk melanjutkan
Sediakan runner terisolasi yang memenuhi PHP 8.3, Composer v2 sesuai lockfile, ekstensi PHP yang dibutuhkan, dan MySQL 8/InnoDB; atau jalankan melalui CI yang dapat dibuktikan memakai konfigurasi tersebut. Sebelum test apa pun yang menjalankan migration, catat dan verifikasi hostname, nama database, nama user, versi MySQL, engine InnoDB, hak akses terbatas, dan bukti tidak ada rute/kredensial menuju produksi/VPS. Laporkan hasil preflight terlebih dahulu.

Setelah seluruh tes yang disetujui lulus dengan log lengkap, baru buat checkpoint WA-01 PASS dan mulai Paket 1 Dashboard Admin.