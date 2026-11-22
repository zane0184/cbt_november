# PREFLIGHT DATABASE DISPOSABLE — STAGE 4B
Tanggal: 22 November 2026
Status: RENCANA SAJA — BELUM DIJALANKAN

## Tujuan
Menyiapkan pemeriksaan migration/legacy Stage 4B pada database disposable yang benar-benar terisolasi. Dokumen ini bukan izin restore, migration, atau koneksi ke database produksi.

## Sumber dump yang diketahui
Nama: cbt_madrasah_test_backup_20261102_083757.sql
Ukuran historis: 122.380 byte
SHA-256 historis: f716921d657baf7096df577fa844602e5cc015364d07d30a0834c4b43471901c
Sumber: Project Library.
Database sumber historis: cbt_madrasah_test
Versi historis: MySQL 8.0.46
Schema historis: 57 tabel, 57/57 InnoDB, 39 migration.
Batas bukti: dump dilaporkan hanya berisi INSERT cache/migrations/sessions; tidak memiliki data bisnis tenant/Guru/Siswa/rombel yang cukup untuk membuktikan legacy Stage 4B. Raw bytes belum berhasil dimaterialisasi pada sesi ini; ukuran/hash belum dihitung ulang.

## Gate 0 — persetujuan dan isolasi
Wajib sebelum pelaksanaan:
- Persetujuan koordinator tertulis atas rencana preflight dan dump yang akan digunakan.
- Host MySQL disposable terpisah dari VPS/produksi; bukan hanya schema baru pada host produksi.
- Kredensial baru khusus test dengan hak minimum hanya pada satu database disposable.
- Firewall/secret memastikan koneksi tidak dapat diarahkan ke hostname/IP produksi.
- Tidak memakai .env produksi, DB_URL produksi, socket produksi, atau kredensial produksi.
- Catat operator, waktu, host, database, versi server, dan tujuan test.

## Gate 1 — identitas server dan database
Sebelum restore, verifikasi dari koneksi yang sama:
- @@hostname, @@port, DATABASE(), VERSION(), CURRENT_USER().
- Nama DB harus unik, misalnya cbt_stage4b_disposable_<run-id>.
- Server harus MySQL 8+ dan tabel InnoDB.
- Pastikan schema target kosong.
- Pastikan kredensial tidak memiliki hak ke schema lain.
- Abort jika identitas tidak cocok, DB bukan disposable, atau ada tabel/data sebelumnya.

## Gate 2 — verifikasi artefak dump
- Ambil file dari Library menggunakan jalur file yang disetujui.
- Hitung ukuran byte aktual dan SHA-256 aktual; harus sama dengan nilai historis sebelum restore.
- Simpan salinan read-only dan manifest hash.
- Periksa header/statement: DROP TABLE, CREATE, INSERT, DEFINER, trigger, routine, event, user/grant, dan perintah berbahaya.
- Dump mengandung DROP TABLE IF EXISTS menurut audit terdahulu; hanya boleh restore ke DB disposable kosong.
- Abort jika ukuran/hash berbeda atau sumber tidak dapat diverifikasi.

## Gate 3 — restore
- Restore hanya ke database disposable baru yang kosong.
- Catat command, versi client, exit code, log, waktu dan ukuran.
- Setelah restore, verifikasi jumlah tabel, engine, migration ledger, FK/index, dan jumlah row per tabel.
- Pastikan tidak ada koneksi atau query ke database produksi.
- Tidak menjalankan Stage 4B migration pada database dump ini tanpa review compatibility dan approval terpisah.

## Gate 4 — preflight schema dan data
- Bandingkan schema prerequisite dengan migration Stage 4B yang tepat pada integration SHA.
- Periksa FK composite tenant-aware dan supporting indexes.
- Periksa engine InnoDB untuk semua tabel terkait.
- Periksa data orphan: tenant, exam, participant, rombel, user, proctor assignment.
- Periksa duplicate active placement/rombel/assignment.
- Jika tabel Stage 4B belum ada, catat sebagai fresh-schema baseline; jangan menganggap data legacy room/session ada.
- Untuk pengujian legacy, siapkan fixture sintetis di database disposable terpisah dengan skenario 0/1/multiple session dan relasi Attempt/Answer/Score/placement/assignment/history. Jangan membuat data tersebut pada produksi.
- Skenario multiple session yang memiliki histori wajib menghasilkan NEEDS_MANUAL_DECISION; tidak boleh auto-select.

## Gate 5 — simulasi migration/guard
Hanya setelah migration design disetujui:
- Buat snapshot disposable sebelum setiap DDL.
- Uji MySQL 8/InnoDB fresh migration.
- Uji race dua koneksi membuat canonical session.
- Uji unique guard dan service idempotency.
- Uji failure/partial DDL behavior dan retry.
- Uji rollback pada DB kosong.
- Uji rollback fail-closed ketika data/histori ada; pastikan tidak ada DDL sebelum guard menolak.
- Uji restore snapshot dan cleanup.
- SQLite hanya sebagai tambahan untuk syntax/behavior test; bukan pengganti concurrency MySQL.

## Rollback dan cleanup
- Tidak melakukan rollback destruktif pada database yang memiliki data/history.
- Simpan log dan manifest hasil.
- Setelah bukti diekspor, drop hanya database disposable dan user test yang dibuat untuk run tersebut.
- Verifikasi database disposable sudah tidak ada; tidak menyentuh schema lain.
- Jika cleanup gagal, hentikan dan laporkan; jangan mencoba cleanup luas.

## Status aktual
- Dump ditemukan pada inventaris Library.
- Restore belum dilakukan.
- Hash/size belum diverifikasi ulang karena raw bytes belum dapat diakses pada sesi ini.
- Migration belum dijalankan.
- Tidak ada database produksi yang disentuh.

Status: PREFLIGHT PLAN READY — EXECUTION REQUIRES COORDINATOR APPROVAL.
