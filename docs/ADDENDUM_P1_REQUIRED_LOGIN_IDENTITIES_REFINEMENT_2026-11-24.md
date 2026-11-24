# Addendum — Penyempurnaan Preflight dan Cakupan Uji Identitas

Tanggal: 24 November 2026  
Branch: `work/p1-seven-targets-20261124`  
Exact HEAD source saat addendum disusun: `84745e2f34df3e6248e3b1dd9fdc662e5d1d9798`  
PR #23: OPEN / DRAFT / NOT MERGED

## AUDIT

Setelah laporan `REPORT_P1_REQUIRED_LOGIN_IDENTITIES_2026-11-24.md`, pemeriksaan lanjutan menemukan bahwa pemeriksaan whitespace harus memperlakukan nilai identitas dengan spasi di tepi sebagai data non-kanonis. Perbandingan string biasa pada MySQL dapat mengabaikan trailing spaces untuk kolom nonbinary; pemeriksaan panjang setelah TRIM dipakai agar lintas MySQL/SQLite mendeteksi kondisi tersebut.

Audit jalur UserManagement menemukan komponen saat ini memiliki operasi create, tetapi tidak ditemukan metode update pada komponen tersebut. Jalur import Guru/Siswa dan recovery identitas belum dibuktikan dalam audit ini; statusnya NOT VERIFIED/NOT IMPLEMENTED pada lingkup yang diperiksa. Jangan mengklaim validasi identitas sudah menyeluruh untuk jalur yang belum ditemukan.

## DESAIN

- Migration harus menghentikan proses sebelum DDL jika nilai kosong, whitespace-padded, atau duplikat ditemukan.
- Index bernama sama harus benar-benar unique dan kolomnya harus persis `tenant_id + identifier`; index yang salah tidak boleh dianggap sudah benar.
- Read-only preflight harus menguji format terhadap nilai mentah, bukan hasil trim saja.
- Regression test harus mencakup semua unique identity: NIP, NIK, NUPTK, NISN.
- Login API perlu menguji kategori ASN, PPPK, dan Honorer, termasuk penolakan username Guru.

## IMPLEMENTASI

Perubahan lanjutan:
1. Migration `2026_11_24_170001_enforce_tenant_login_identity_uniques.php`:
   - menolak whitespace-padded identity sebelum DDL memakai perbandingan LENGTH terhadap LENGTH(TRIM(...));
   - memeriksa bahwa index yang sudah ada memiliki flag unique dan urutan kolom yang tepat.
2. `LoginIdentityPreflightCommand`:
   - mendeteksi whitespace di tepi untuk NIP/NIK/NUPTK/NISN;
   - memvalidasi format pada nilai mentah;
   - tidak menampilkan nilai identitas.
3. Regression tests:
   - unique index NIP, NIK, NUPTK, dan NISN;
   - preflight whitespace-padded identity;
   - API login Honorer dengan NIK dan penolakan username;
   - login PPPK melalui NIP.
4. Backup manifest diperluas menjadi 21 snapshot source/intermediate. Seluruh 21 backup blob diverifikasi byte-exact terhadap SHA yang dicatat di manifest.

## PENGUJIAN

- Backup: 21/21 SHA blob cocok.
- Test baru telah ditambahkan, tetapi belum dieksekusi.
- PHP lint, focused PHPUnit, full PHPUnit, disposable MySQL 8/InnoDB, migration, race/concurrency, dan browser-to-real-Laravel E2E tetap NOT RUN/NOT VERIFIED.
- Migration tidak dijalankan pada database apa pun.
- Tidak ada database produksi/staging yang diubah.

## CHECKPOINT

Source identity patch terakhir yang diperiksa: `84745e2f34df3e6248e3b1dd9fdc662e5d1d9798`.

PR #23 masih DRAFT. Tidak ada merge, deployment, atau ZIP final. Langkah berikutnya tetap exact-HEAD CI dan pengujian disposable MySQL 8/InnoDB, kemudian audit jalur update/import dan seluruh output API/log/audit.


## Batasan integritas database yang wajib dicatat

Migration saat ini hanya menegakkan unique index tenant-scoped dan fail-closed preflight duplicate/blank/whitespace/index-shape. Migration ini **belum** menambahkan DB CHECK constraint atau trigger yang menolak raw SQL INSERT/UPDATE dengan:
- status kepegawaian kosong/tidak dikenal;
- PNS/PPPK tanpa NIP tepat 18 digit;
- Honorer tanpa NIK tepat 16 digit;
- Siswa tanpa NISN tepat 10 digit.

Read-only preflight hanya mendeteksi dan melaporkan; ia tidak menghalangi penulisan berikutnya. Karena itu, validasi kategori/format saat ini baru diterapkan pada jalur create UserManagement dan login resolver; **database-level conditional enforcement masih BLOCKED**. Jangan menyebut seluruh jalur penulisan aman sampai DB constraint/trigger lintas MySQL 8 dan SQLite dirancang, diuji, dan dipasang melalui migration yang fail-closed.

Jalur update, import, recovery, serta seluruh endpoint lain yang dapat menulis TeacherProfile/StudentProfile juga belum dinyatakan tervalidasi.
