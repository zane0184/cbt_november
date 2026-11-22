# CBT Madrasah — Audit Lanjutan dan Rekonsiliasi Agent

**Tanggal:** 22 November 2026  
**Repository:** `zane0184/cbt_november`  
**Mode:** audit READ-ONLY dan koordinasi. Tidak ada source, branch, database, staging, atau VPS yang diubah oleh ChatGPT.

## 1. Ringkasan keputusan

1. **Stage 4B/4C: CI teknis PASS pada source SHA `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5`; branch HEAD `4e663cd84038b7c3337d776f702c499f20d8f537`.** Run `44984303003` berhasil pada 35/35 langkah. Namun, Stage 4B **BELUM APPROVAL READY** karena desainnya menggunakan `ExamSession`/gelombang/sesi pelaksanaan, sedangkan keputusan proyek terbaru menetapkan ujian **MODEL SERENTAK tanpa gelombang atau sesi pelaksanaan**. CI pass tidak menghapus konflik desain ini.
2. **Stage 4C Penugasan Guru Mapel: PASS untuk suite yang dijalankan.** 9 feature tests/41 assertions dan 1 concurrency test/8 assertions lulus pada MySQL 8.0.46. Kode Stage 4C dapat dipertahankan sebagai pekerjaan terpisah dari redesign Stage 4B.
3. **Web Guru: CI PASS pada source SHA `e9c6867d40b134538e791bbbba02f5743afec7ea`; branch HEAD `25fe5e74a0754bcd110ceffac20141880c549ca8`.** 11 tests/44 assertions lulus. Portal masih fondasi awal, belum siap dibuka penuh; legacy module authorization, browser/E2E, visual Light/Dark, dan verifikasi sumber CP masih terbuka.
4. **Admin security gate: bukti inti tersedia, tetapi cakupan negatif langsung API/export belum lengkap.** Test boundary yang dijalankan hanya satu method (1 test/27 assertions), tidak menjalankan seluruh method dalam file dan tidak membuktikan seluruh endpoint API CBT serta export secara langsung.
5. Agent 1 dan Agent 2 saat ini menggunakan riwayat branch yang sama, `work/stage4b-room-session-20261122`. SHA `42a3ed3a` yang pernah disebut sebagai HEAD Agent 1 ternyata commit test Stage 4C, bukan HEAD branch. Perlu pemisahan branch sebelum pekerjaan berikutnya.
6. Hanya `routes/web.php` yang teridentifikasi sebagai file source yang berubah pada kedua jalur Agent 2 dan Agent 3 sejak baseline bersama. Jangan merge otomatis; tetapkan satu pemilik file.

## 2. AUDIT — Stage 4B dan Stage 4C

### 2.1 HEAD dan bukti CI

| Item | Nilai |
|---|---|
| Branch | `work/stage4b-room-session-20261122` |
| HEAD saat audit | `4e663cd84038b7c3337d776f702c499f20d8f537` |
| Source SHA yang diuji | `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5` |
| Workflow run | `44984303003` |
| Hasil | 35/35 langkah berhasil |
| MySQL | 8.0.46, database disposable, InnoDB |

Suite target yang terverifikasi:

| Suite | Hasil |
|---|---:|
| Stage 4B room/session management | 12 tests / 49 assertions PASS |
| Stage 4B MySQL concurrency | 2 tests / 22 assertions PASS |
| Stage 4C teacher-subject assignment | 9 tests / 41 assertions PASS |
| Stage 4C MySQL concurrency | 1 test / 8 assertions PASS |
| **Total empat suite target** | **24 tests / 120 assertions PASS** |

Bukti tambahan pada workflow yang sama:
- Admin CBT boundary: 1 test / 27 assertions PASS.
- Admin dashboard access dan tenant-scoped data: 35 tests / 182 assertions PASS.
- API authentication/tenant isolation, Guru/Siswa regression, monitoring, dan Proctor assignment security ikut dijalankan.
- Pemeriksaan 80 tabel menunjukkan seluruh tabel hasil migrasi menggunakan InnoDB.
- Rollback gate melaporkan `rollback_was_child_first=true`, `base_exam_participants_tenant_fk_preserved=true`, dan `single_column_exam_participants_tenant_index_preserved=true`.
- Disposable database dan user pengujian dibersihkan.

Kegagalan historis run `44980547414` pada source SHA `3e35ad7...` adalah SQLSTATE 1553 ketika rollback menghapus index `exam_participants_id_tenant_exam_unique`. Source terbaru memindahkan urutan rollback dan CI baru `44984303003` membuktikan skenario rollback berhasil. Karena itu, kegagalan lama tidak boleh dipakai sebagai status terkini; bukti terbaru adalah run `44984303003`.

### 2.2 Konflik desain yang memblokir penerimaan Stage 4B

Keputusan proyek terbaru: **ujian hanya MODEL SERENTAK; tidak ada gelombang atau sesi pelaksanaan.**

Source Stage 4B saat ini justru membuat dan mengoperasikan:
- `exam_sessions` dengan `session_no`, `starts_at`, `ends_at`, dan lifecycle status;
- `exam_session_rooms`;
- mapping Rombel ke session-room;
- `exam_proctor_assignments` yang terikat ke `exam_session_room_id`;
- scheduler `exams:sync-room-session-lifecycle` untuk mengubah lifecycle sesi/penugasan.

Ini adalah konflik desain langsung. Maka status yang benar:

**Stage 4B = CI teknis PASS, tetapi DESIGN NON-COMPLIANT / NOT APPROVAL READY.**

Jangan merge atau deploy desain sesi ini. Agent 1 perlu membuat rancangan pengganti tanpa sesi: satu jendela ujian serentak mengikuti `exam.start_at`/`exam.end_at` dan waktu server; pengelolaan ruang per ujian; penugasan Proktor sementara pada lingkup ujian+ruang; monitoring hanya untuk peserta yang berada pada ruang yang ditugaskan. Struktur final, relasi, dan migration plan harus diaudit dahulu sebelum implementasi. Ini rancangan usulan, bukan perubahan source yang sudah diterapkan.

### 2.3 Cakupan Admin security gate yang masih perlu ditambah

`AdminCbtBoundaryTest` memiliki dua method, tetapi workflow terakhir hanya menjalankan filter:
`test_admin_with_stale_guru_role_and_permissions_is_denied_all_cbt_surfaces`.

Method tersebut menguji Gate/policy, Livewire `QuestionBank` dan `ExamManagement`, serta route `/student/exams`. Ia tidak secara langsung mengirim request ke seluruh API CBT dan export. Method kedua tentang bootstrap permission tidak dijalankan pada step tersebut.

Tambahkan negative integration tests dengan akun Admin yang memiliki stale/mixed GURU permissions untuk endpoint yang memang tersedia: bank soal, assessment/exam authoring, token, participant, attempt execution, answer read/write, submit, result/score, monitoring, dan export. Verifikasi HTTP denial dan tidak ada mutasi. Jalankan juga test bootstrap permission secara eksplisit. Jangan menganggap satu test Gate sebagai bukti seluruh route/controller terproteksi.

## 3. AUDIT — Web Guru

| Item | Nilai |
|---|---|
| Branch | `work/web-guru-foundation-20261122` |
| HEAD saat audit | `25fe5e74a0754bcd110ceffac20141880c549ca8` |
| Source SHA yang diuji | `e9c6867d40b134538e791bbbba02f5743afec7ea` |
| Workflow run | `44984094200` |
| Hasil | 11 tests / 44 assertions PASS |

HEAD `25fe5e...` adalah perubahan dokumentasi setelah source SHA `e9c686...`; pemeriksaan compare tidak menemukan perubahan source setelah source yang diuji. Jadi build/test evidence berlaku untuk source branch saat ini.

Temuan:
- `TeacherPortalAccess` memeriksa tenant, user persisted/non-deleted, satu role GURU, dan satu profil Guru tenant-lokal.
- Dashboard, learning/curriculum context, dan profile page merupakan fondasi awal; belum seluruh fitur Guru (bank soal, assessment, exam builder, monitoring assignment) selesai.
- `TeacherCurriculumContextService` dengan benar belum mengklaim CP resmi terverifikasi, Pembelajaran Mendalam, atau dimensi lulusan sudah tersedia penuh.
- Query jumlah siswa berbasis membership Rombel perlu memastikan definisi hitungannya: profil siswa aktif atau akun siswa aktif; query yang diperiksa belum menggabungkan status akun User.
- Belum ada bukti browser E2E, visual responsive, audit seluruh legacy Guru components/routes, dan validasi Light/Dark seluruh komponen.
- CI memunculkan **49 peringatan PHPUnit** tentang metadata test di doc-comment yang akan tidak didukung PHPUnit 12. Ini bukan test failure, tetapi debt kompatibilitas. Ada juga warning lingkungan Node 20/MySQL/zoneinfo.

Checkpoint Web Guru masih menyebut HEAD `10277d...` dan run 449840 belum selesai, padahal branch sekarang `25fe5...` dan run sudah PASS. Checkpoint harus diperbarui. Jangan mengganti Agent 3; lanjutkan pada branch-nya.

## 4. AUDIT — branch dan konflik integrasi

- Stage4B/4C branch HEAD: `4e663cd...`.
- Web Guru branch HEAD: `25fe5e7...`.
- SHA `42a3ed3a` yang disebut sebagai HEAD Agent 1 adalah commit test Stage 4C dalam riwayat, bukan branch HEAD.
- Tidak ditemukan branch Agent 1 terpisah yang dapat dipastikan dari referensi tersebut. Pekerjaan Stage4B dan Stage4C berada pada riwayat branch Stage4B/4C yang sama.
- Perbandingan perubahan dari baseline bersama `81b6c74...` menemukan satu file source yang disentuh kedua branch: `backend-laravel/routes/web.php`.
- `AuthWebController.php` dan `routes/teacher.php` merupakan pekerjaan Web Guru; jangan diedit Agent 2.

Aturan ownership berikutnya:
- Agent 2 menjadi pemilik integrasi `routes/web.php` untuk route Admin dan integrasi route Guru.
- Agent 3 mengelola `routes/teacher.php`, `AuthWebController.php`, layout dan komponen Teacher; Agent 3 tidak mengubah `routes/web.php` lagi. Perubahan route yang dibutuhkan diserahkan sebagai patch/snippet ke Agent 2.
- Agent 1 mengelola domain/migration/test pengganti Stage4B tanpa sesi; perubahan route diserahkan ke Agent 2.
- Jangan merge branch sebelum audit integrasi dan persetujuan eksplisit.

## 5. AUDIT — baseline, workspace, dan Library

- Direktori lokal `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tidak ditemukan pada runtime sesi ini.
- Library memuat `CBT REVISI 6 NOPEMBER 2026_REASSEMBLED_READONLY.zip` (105,365,379 bytes), serta arsip `01_SOURCE_WORKSPACE_RECOVERED...` dan `CBT_HANDOVER_INDEX_20261115.zip`.
- Percobaan materialisasi raw ZIP gagal karena file Project/Library tidak menyediakan jalur raw-byte yang diotorisasi pada sesi ini. Tidak ada ZIP yang diubah.
- Checkpoint audit Library sebelumnya mencatat baseline asli telah diekstrak dan diperiksa: 16.080 entries, CRC PASS, 15.926 unique SHA-256, ukuran 105.365.379 bytes. Bukti historis tersebut dipakai sebagai audit terdahulu; hash tidak dihitung ulang pada sesi ini karena raw bytes tidak dapat diakses.
- GitHub adalah source kerja yang dapat diperiksa langsung untuk pekerjaan Web Admin/Guru saat ini. Tidak ada permintaan upload ulang dibuat.

## 6. DESAIN — keputusan tindak lanjut

1. Tegakkan keputusan MODEL SERENTAK tanpa sesi. Desain Stage4B harus diganti sebelum integrasi; jangan mempertahankan `ExamSession` hanya karena CI lulus.
2. Pertahankan Stage4C penugasan Guru Mapel sebagai unit terpisah, dengan tenant, academic year/semester, idempotency, dan concurrency guard.
3. Pertahankan Web Guru pada branch terpisah. Jangan mengaktifkan menu legacy sebelum seluruh route/component/action memiliki authorization dan tenant scope.
4. Semua perubahan schema berikutnya harus berupa rencana forward-only, preflight, backup, dan test pada DB disposable. Tidak ada migration produksi.

## 7. IMPLEMENTASI

Tidak ada source yang diubah oleh ChatGPT. Tidak ada branch, commit, PR, migration, database, VPS, atau deployment yang dibuat/dijalankan. Hanya audit read-only GitHub, pemeriksaan Files/Library, dan pemeriksaan filesystem.

## 8. PENGUJIAN

Pengujian yang benar-benar dijalankan adalah CI GitHub:
- Run `44984303003`: 35/35 steps sukses pada source `f1d01ea...`.
- Run `44984094200`: 11 tests/44 assertions sukses pada source `e9c686...`; 49 deprecation warnings doc-comment masih ada.

Tidak ada build lokal, PHPUnit lokal, migration lokal, MySQL lokal, browser E2E, perangkat Android, VPS, atau produksi yang diuji pada sesi ini.

## 9. Tindak lanjut

1. Agent 1: rancangan Stage4B tanpa sesi + negative API/export/Livewire security matrix; jangan patch sebelum desain diaudit.
2. Agent 2: lanjut Stage4D–4G pada branch baru terpisah dari Agent 1; jangan mengubah Stage4B session code atau file Teacher.
3. Agent 3: lanjut Web Guru pada branch sendiri; perbarui checkpoint; audit legacy Guru modules, E2E, visual, curriculum evidence, dan hitungan siswa.
4. ChatGPT: audit setiap hasil, rekonsiliasi route, checkpoint, dan handover; tidak melakukan merge/deploy tanpa persetujuan.

---

**Status keseluruhan:** Stage4C dan fondasi Web Guru memiliki bukti CI PASS; Stage4B memiliki CI PASS tetapi desain tidak sesuai keputusan terbaru; Web Admin security gate inti lulus namun direct API/export coverage masih perlu dilengkapi; belum siap produksi atau distribusi.