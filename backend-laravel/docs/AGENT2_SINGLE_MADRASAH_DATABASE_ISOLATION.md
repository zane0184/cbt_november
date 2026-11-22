# Agent 2 — Isolasi Database Satu Madrasah per Instalasi

Tanggal: 22 November 2026
Branch: work/stage4d-account-lifecycle-20261122
HEAD sebelum dokumentasi: 7d63ea3b70b5191530c2c92c5c86bf92d7324db6
Source runtime: 74bf7178f46673e9be90523671490c0749034acd

## DESAIN YANG MENGIKAT

**Satu instalasi produksi = satu madrasah/tenant = satu database akademik sendiri.**

Satu ZIP distribusi Web CBT dapat dipasang berulang kali, tetapi setiap pemasangan berdiri sendiri. Database akademik, kredensial, storage, file, backup, domain, branding, APP_KEY, session, log, cache/queue namespace, dan secret tidak dibagi antarmadrasah.

tenant_id tetap dipakai sebagai defense-in-depth dalam instalasi. Tidak boleh ada shared academic database, query lintas database, cross-installation tenant access, sinkronisasi akademik otomatis, atau central academic warehouse.

## AUDIT — Source dan bukti

| Komponen | Blob SHA | Temuan |
|---|---|---|
| LocalInstallationIdentity | d6f36438e12be0c64b8a9ecaa3d4e03994ea2d8c | Menyimpan tenant_id, installation_id, key metadata, registered_domain dan encrypted_private_key. Tujuan encrypted_private_key harus diklasifikasi; jangan menganggap itu publisher license signing key. |
| LoginIdentityResolver | c1f56029da5b8fdb5d7f80380269637b7d1c3ebe | Mensyaratkan tepat satu LocalInstallationIdentity dan tepat satu Tenant dalam database. Ini mendukung pola satu tenant per instalasi. |
| IdentifyTenant | 376a4b2771c3d9a6b128ad847c150d47ac2977db | Mengabaikan X-Tenant-Override. TenantContext diisi dari tenant user, tetapi middleware belum membandingkan user.tenant_id dengan LocalInstallationIdentity dan host instalasi. |
| TenantContext | 171261b61ee7f551e3c1b1eb4335b169a1df0d8d | Context menyimpan satu tenant_id dalam instance/static singleton. Reset per request harus dipertahankan untuk worker berumur panjang. |
| config/database.php | 1ab78eb1de678eee3fa89c8de9d1c22483c971bd | Membaca kredensial runtime dari storage/app/private/installer-database.php; default dapat jatuh ke SQLite bila installer override belum ada. Redis prefix default berbasis APP_NAME. |
| InstallerService | b0ef702244edcdf8f227e7f4751334ee01ea3c4c | Memeriksa MySQL 8.0+, InnoDB, database kosong, dan menulis konfigurasi DB ke storage/app/private melalui proses atomik. |
| MediaController | f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62 | Media disimpan ke private/tenants/{tenantId}/media pada local disk. |
| ReportHeaderService | 9cab29ad41aecce79a8729bc98f506f5c83a2452 | Mencari logo melalui beberapa path filesystem; containment pada root storage yang diizinkan perlu diperketat. |

### Gap yang terbukti atau belum terverifikasi

1. registered_domain ada pada LocalInstallationIdentity, tetapi LoginIdentityResolver tidak memvalidasi request host terhadap domain tersebut.
2. IdentifyTenant tidak menerima X-Tenant-Override (dipertahankan), tetapi tenant instalasi belum menjadi pembanding wajib pada setiap request terautentikasi.
3. User yang tenant_id-nya berbeda dari instalasi harus ditolak server-side, termasuk pada token/session lama.
4. config/filesystems.php, config/cache.php, config/queue.php, dan config/session.php tidak berhasil diambil dari path branch yang diperiksa (GitHub API 404). Konfigurasi storage/cache/queue/session belum dapat dinyatakan aman atau tidak ada; verifikasi tree dan lokasi aktual masih wajib.
5. Redis prefix default memakai APP_NAME. Jika beberapa instalasi memakai Redis bersama dan APP_NAME sama, namespace berpotensi bertabrakan. Setiap instalasi yang memakai Redis harus memiliki prefix unik atau Redis database/instance terpisah.
6. MediaController menambahkan prefix tenant pada path, tetapi storage root fisik per instalasi harus diverifikasi. Backup/restore storage per madrasah belum diuji.
7. ReportHeaderService menerima beberapa kandidat path langsung dari logo_path; lakukan canonical path containment dan hanya baca file dalam root tenant lokal yang diizinkan.
8. Tidak ada bukti dari tahap ini bahwa backup database dan file dapat dipulihkan secara konsisten pada dua instalasi terpisah.

## DESAIN

### Database

- Installer menerima satu database MySQL 8.0+ yang kosong dan menggunakan InnoDB.
- Satu instalasi hanya mempunyai satu Tenant dan satu LocalInstallationIdentity yang saling cocok.
- Database tidak boleh dipilih dari header, query, body, atau input user setelah instalasi.
- Installer harus fail-closed jika konfigurasi database tidak terverifikasi; jangan diam-diam menjalankan produksi dengan SQLite.
- Jangan jalankan migration pada database produksi selama audit/preflight ini.

### Host dan identitas instalasi

- Host/domain request dinormalisasi dan dibandingkan dengan domain lokal yang telah disetujui.
- Tenant instalasi berasal dari konfigurasi/identity lokal yang tepercaya.
- User tenant harus sama dengan tenant instalasi pada login dan setiap request.
- Header X-Tenant-Override, tenant_id query/body, atau manipulasi session tidak boleh mengubah tenant.
- Tidak ada domain registry akademik terpusat.

### Storage, cache, queue, session, log

- Storage lokal per instalasi; file privat tidak disajikan melalui public symlink tanpa otorisasi.
- Cache/queue/session/log memiliki namespace dan secret per instalasi.
- Backup database dan file diambil, dienkripsi, disimpan, diuji restore, dan dihapus sesuai kebijakan per madrasah.
- Publisher/control plane tidak menerima file/backup akademik.

### Lisensi masa depan

Hanya metadata instalasi/lisensi dan transaksi yang diperlukan. Tidak ada data akademik. Private signing key penerbit hanya di publisher; public key/verifikasi lokal di instalasi.

## ACCEPTANCE TESTS

1. Dua instalasi staging A dan B menggunakan database berbeda; tidak ada shared academic database.
2. Kredensial instalasi A tidak dapat membuka database B.
3. Domain A pada instalasi B ditolak; spoof Host/X-Forwarded-Host diuji sesuai trusted proxy config.
4. X-Tenant-Override, tenant_id query/body, dan tenant session tampering tidak mengubah context.
5. User tenant B yang tokennya dipakai pada instalasi A ditolak.
6. LocalInstallationIdentity 0, 2+, tenant mismatch, atau domain mismatch menyebabkan login fail-closed.
7. Setiap file media/report hanya berada pada storage root instalasi sendiri.
8. Redis/cache/queue/session key namespace A dan B tidak bertabrakan.
9. Backup/restore DB dan storage diuji pada disposable environment.
10. Tidak ada request akademik keluar menuju publisher/control plane.

## IMPLEMENTASI DAN PENGUJIAN

Tidak ada perubahan database.php, installer, middleware, identity, storage, config, migration atau database. Tidak ada preflight MySQL baru, restore, atau multi-installation test yang dijalankan.

Owner: Agent 2. Status: tenant topology disepakati, tetapi host binding, runtime tenant comparison, storage/cache/queue isolation dan restore masih PENDING.