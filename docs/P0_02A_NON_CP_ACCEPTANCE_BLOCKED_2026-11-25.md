# CHECKPOINT P0-02A — Non-CP Academic Acceptance (BLOCKED / NOT EXECUTED)
Tanggal: 2026-11-25

## 1. Identitas branch dan checkpoint
- Repository: `zane0184/cbt_november`
- Branch: `work/limited-zip-p0-20261125`
- HEAD sebelum checkpoint ini: `52f34ee2be7d346c1e1eaba276285badc75dac7f`
- Verifikasi: `compare_commits(base=52f34ee2be7d346c1e1eaba276285badc75dac7f, head=work/limited-zip-p0-20261125)` menghasilkan `status=identical`, `ahead_by=0`, `behind_by=0`.
- Checkpoint P0-02 dibaca ulang dari branch; blob SHA: `ae3731e26fb4704a9406d3af0a0633497428df1a`.
- Tidak ada perubahan pada `main`, tidak ada merge, VPS, deployment, migration produksi, atau database produksi yang disentuh.

## 2. AUDIT — kondisi aktual yang berhasil diverifikasi
### Source dan kontrak
- `backend-laravel/phpunit.xml` blob SHA `267993a554d8b355ca7b2ad0592eefb4d7110991` menetapkan `DB_CONNECTION=sqlite` dan `DB_DATABASE=:memory:` pada konfigurasi PHPUnit. Ini bukan bukti pengujian MySQL 8/InnoDB.
- `backend-laravel/composer.json` blob SHA `64eaad3a8bf90c527ee4d5dc269318d6aba47ba0` mensyaratkan PHP `^8.3` dan Laravel `^13.0`.
- `AdminUiVerificationTest.php` blob SHA `4a7b407ba7eefcbdf9c6590847d6aaebeb6d89e9` sudah menguji sebagian aksi Livewire Admin, identitas Guru, audit, serta penempatan siswa. Fixture-nya menyiapkan sebagian data langsung dan bukan satu alur Admin lengkap dari awal.
- `CurriculumRegistryManagement.php` blob SHA `dd814febbef59ef2d3c4ef0f047c6aa5573e9499` memperlihatkan registrasi versi metadata resmi dan pemetaan mapel dilakukan dengan transaksi serta audit. Tidak diubah.
- `TeacherSubjectAssignmentManagement.php` blob SHA `9d076387c1e81a4b229b0358911a4f9133085a86` memvalidasi Guru, mapel, Rombel, semester, registrasi, fase/kelas, mapping mapel; menggunakan transaksi, lock, snapshot konteks dan audit. Tidak diubah.
- `StudentRombelManagement.php` blob SHA `8a250fbd31fad79a8d5f685756c0fde038feaa3b` memvalidasi tenant/role/rombel, lock, insert pivot dan audit di dalam transaksi. Tidak diubah.
- Test helper `CreatesCanonicalCurriculumContext` secara eksplisit membuat CP sintetis verified khusus PHPUnit. Helper ini tidak dipakai sebagai bukti CP resmi dan tidak boleh dipakai untuk acceptance CP PENDING.
- Checkpoint P0-02 sebelumnya mencatat browser runner Playwright menggunakan mock API/local fixture; belum membuktikan browser ke backend Laravel nyata.

### Workspace dan runtime
Pemeriksaan runtime pada sesi ini:
- `pwd` menghasilkan `/`.
- `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tidak ada.
- Tidak ada ZIP baseline pada `/mnt/data` yang tersedia di runtime ini.
- `git` tersedia; `php` tersedia; `node` dan `npm` tersedia.
- `composer`, `mysql`, `mysqld`, `docker`, dan `podman` tidak tersedia.
- Tidak ada koneksi ke VPS atau database produksi yang dilakukan.

## 3. DESAIN — batas eksekusi aman
- Tidak menyiapkan atau memakai database selain MySQL 8/InnoDB disposable yang dapat dibuktikan terisolasi.
- Tidak mengubah `phpunit.xml` untuk mengalihkan tes ke SQLite lalu menyebutnya MySQL.
- Tidak membuat CP sintetis atau mengubah gate CP untuk mengejar PASS.
- Tidak mengubah provisioning dasar, migration, seeder resmi, atau source aplikasi pada tahap ini.
- Jalur acceptance yang harus dijalankan ketika lingkungan tersedia: Admin membuat Guru dan Siswa → Rombel → placement → mapel → registrasi metadata → subject mapping → assignment; lanjut negative tests untuk CP PENDING dan seluruh rollback/tenant/permission/idempotency/duplicate/audit.
- Jalur browser harus mengarah ke Laravel yang benar-benar berjalan dan database disposable. Mock API runner hanya boleh dilaporkan sebagai browser-client test.

## 4. IMPLEMENTASI
- Source aplikasi: tidak ada file diubah.
- Migration/seeder/database: tidak ada perubahan.
- Backup source: tidak diperlukan karena tidak ada file source yang diubah.
- Checkpoint ini adalah file dokumentasi baru; tidak menimpa file lama atau baseline.
- Tidak ada perbaikan root cause karena belum ada test yang dapat dijalankan dan belum ada failure teramati.

## 5. PENGUJIAN — status per matriks
| Skenario | Status | Bukti/catatan |
|---|---|---|
| Branch dan exact HEAD | PASS | Compare branch dengan commit 52f34ee menghasilkan identical |
| Pembacaan ulang checkpoint P0-02 | PASS | File ditemukan dan kontennya dibaca |
| Audit konfigurasi PHPUnit | PASS | Konfigurasi menyatakan SQLite in-memory; ini hanya audit konfigurasi |
| Admin membuat Guru dan Siswa pada MySQL 8 | NOT RUN | Tidak ada MySQL 8 disposable |
| Admin membuat Rombel dan penempatan Siswa pada MySQL 8 | NOT RUN | Tidak ada MySQL 8 disposable |
| Admin membuat mapel pada MySQL 8 | NOT RUN | Tidak ada MySQL 8 disposable |
| Registrasi versi metadata dan mapping mapel pada MySQL 8 | NOT RUN | Tidak ada MySQL 8 disposable |
| Pembuatan assignment pada MySQL 8 | NOT RUN | Tidak ada MySQL 8 disposable |
| Tenant isolation lintas tenant | NOT RUN | Tidak ada runtime database |
| Permission dan validasi identitas | NOT RUN pada P0-02A | Ada test lama terpisah; tidak dijalankan ulang di tahap ini |
| Audit log, transaksi dan rollback parsial | NOT RUN | Tidak ada runtime database |
| Idempotensi dan duplikasi | NOT RUN | Tidak ada runtime database |
| CP PENDING menolak create/publish TP | NOT RUN | Gate source tidak diubah; tidak diuji |
| CP PENDING menolak ATP dan soal | NOT RUN | Gate source tidak diubah; tidak diuji |
| Browser → Laravel → MySQL 8 nyata | BLOCKED | Runner terdahulu memakai mock API; backend+DB disposable belum tersedia |
| Regression suite PHP 8.3/8.4 + MySQL 8 | BLOCKED | Lingkungan lokal tidak tersedia; workflow CI yang benar-benar menjalankan MySQL belum dapat diverifikasi dari runtime ini |

Tidak ada hasil FAIL yang disamarkan sebagai SKIPPED. Tidak ada pengujian aplikasi/database pada P0-02A yang dijalankan; karena itu tidak ada klaim PASS untuk alur akademik.

## 6. BLOCKER
1. Workspace source tidak tersedia pada runtime, sehingga tidak dapat membuat backup byte-exact lokal, menghitung SHA-256 source lokal, menjalankan Composer, atau menguji aplikasi.
2. Tidak tersedia MySQL 8/InnoDB disposable maupun Docker/Podman untuk menyiapkannya.
3. PHPUnit default menggunakan SQLite in-memory; hasil suite dengan konfigurasi itu tidak memenuhi kriteria MySQL 8.
4. Browser runner yang diketahui memakai mock API; browser-to-real-Laravel acceptance belum terbukti.
5. Tidak ada test CP PENDING yang dijalankan pada tahap ini. Status CP dan validasi source tetap tidak berubah.

## 7. CHECKPOINT / NEXT
- Status P0-02A: **BLOCKED — AUDIT AWAL SELESAI, ACCEPTANCE TEST BELUM DIEKSEKUSI.**
- Exact HEAD sebelum dokumen ini: `52f34ee2be7d346c1e1eaba276285badc75dac7f`.
- File yang ditambahkan: `docs/P0_02A_NON_CP_ACCEPTANCE_BLOCKED_2026-11-25.md`.
- File source/migration/seeder yang diubah: none.
- Database/VPS: none.
- Test aplikasi yang dijalankan: none.
- P0-01 tidak diulang dan tidak diubah.
- Langkah berikutnya: pulihkan workspace dari exact branch/commit yang terverifikasi; siapkan MySQL 8/InnoDB disposable terisolasi (mis. CI job yang eksplisit menjalankan MySQL 8 service); verifikasi tidak ada endpoint/credential produksi; buat test acceptance baru tanpa mengubah test lama; jalankan test terfokus lalu regression suite; baru setelah itu sambungkan browser runner ke backend Laravel nyata.
- P0-02A tidak boleh dinyatakan selesai sebelum kriteria pengguna terbukti melalui test MySQL 8/InnoDB.
