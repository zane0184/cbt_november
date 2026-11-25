# MASTER HANDOVER — CBT MADRASAH WEB COMPLETION

Tanggal: 23 November 2026  
Repository: `zane0184/cbt_november`  
Branch eksekusi: `work/solo-web-cbt-completion-20261123`  
PR integrasi: [#21](https://github.com/zane0184/cbt_november/pull/21) — OPEN/DRAFT/UNMERGED pada checkpoint yang tersedia. Verifikasi ulang status/head aktual sebelum tindakan.  
Status proyek: **WORK IN PROGRESS — NOT PRODUCTION READY — NOT READY FOR OPERATIONAL EXAM**.

Dokumen ini adalah handover operasional agar agent pengganti tidak mengulang pekerjaan dan dapat melanjutkan dari source, bukti, serta checkpoint aktual. Ini bukan bukti bahwa seluruh fitur telah diaudit tuntas.

---

## 1. Urutan tindakan agent baru (wajib)

1. Buka `docs/AGENT_START_HERE.md`, lalu dokumen ini.
2. Verifikasi HEAD branch kerja dan PR #21; jangan percaya SHA yang tercatat di checkpoint lama tanpa pemeriksaan.
3. Periksa PR #13 (Agent 4 Web Siswa), PR #19 (Web Guru), PR #18 (Web Admin), PR #21 (integrasi) dan status merge/draft/CI masing-masing. Jangan cherry-pick atau menggabungkan otomatis.
4. Baca seluruh komentar/keputusan owner di Issue #20 dan Issue #8/#9 bila relevan; keputusan terbaru owner mengikat.
5. Baca dokumen spesifikasi/hasil audit terbaru pada branch pemilik masing-masing portal. Jangan anggap file pada branch integrasi sudah memuat commit terbaru dari PR lain.
6. Cocokkan laporan dengan source aktual dan CI exact SHA. Catat mismatch dan bukti baru.
7. Susun daftar file/fitur yang sudah selesai, parsial, gagal, belum ada, dan belum diverifikasi. Hindari patch duplikat.
8. Kerjakan satu akar masalah per tahap; backup byte-exact sebelum perubahan source existing.
9. Jalankan tes yang sesuai pada disposable environment, bukan DB produksi.
10. Perbarui checkpoint, laporan, dan handover di GitHub. Pertahankan PR Draft/unmerged sampai seluruh acceptance gate dan persetujuan owner.

## 2. Keputusan produk/arsitektur yang mengikat

### Portal dan deployment
- Portal yang dikerjakan sekarang: **Admin Madrasah, Guru, Siswa**.
- Android native dan Web Super Admin ditunda sampai **Juni 2027**.
- Tidak ada portal permanen Operator, Kamad, atau Proktor.
- Semua tugas administratif Operator dilaksanakan Admin Madrasah.
- Proktor adalah penugasan operasional sementara pada Guru; penugasan ini tidak memberi akses monitoring/room/session/seat/roster/Attempt atau operasi CBT.
- Setiap madrasah menggunakan instalasi dan database mandiri. Tidak ada pertukaran data akademik antar-madrasah. Integrasi pusat di masa mendatang hanya lisensi/entitlement/pembayaran.
- Jangan membangun ulang konsep multi-tenant data akademik lintas instalasi.

### Kewenangan
- Guru mengelola bank soal, membuat, mengatur, menjadwalkan, dan menerbitkan ujian sesuai penugasan tanpa persetujuan Admin.
- Admin mengelola administrasi, master data, sinkronisasi, dukungan teknis, dan fungsi administratif yang telah disepakati; bukan mengambil alih authoring/operasi ujian Guru.
- Terapkan exact role, permission, assignment, tenant/install scope, dan ownership server-side. Jangan percaya role/header/ID dari client.
- Penugasan Proktor tidak boleh membuka hak yang tidak disetujui.

### Kurikulum dan asesmen
- Integrasikan Kurikulum Merdeka dan Kurikulum Berbasis Cinta/Panca Cinta sebagai satu kesatuan, bersama Pembelajaran Mendalam dan delapan dimensi profil lulusan.
- Pertahankan provenance, versi, dan riwayat akademik sampai question version dan immutable exam snapshot.
- CP induk read-only; TP/ATP dibuat Guru sesuai assignment.
- Taxonomy asesmen fleksibel dan versioned; jangan mengunci UTS/UAS/UM/SIMULASI TKA menjadi enum tetap.
- TKA eksternal tidak otomatis menjadi ujian pusat.
- DOCX adalah prioritas import soal. Jangan menghidupkan kembali XLSX question import.
- SHORT_ANSWER tetap blocked sampai accepted answer, snapshot privat, dan scoring diuji end-to-end.

### Prinsip CBT
- Token dibuat otomatis setelah Guru menerbitkan/mengunci ujian sesuai kontrak; token dapat dilihat sebelum jadwal, tetapi ujian hanya dapat dimulai pada rentang jadwal.
- Waktu server adalah acuan resmi.
- Attempt, Answer, Score, device binding/lease, fencing, dan submit harus konsisten.
- Jawaban harus tahan refresh, response loss, offline/reconnect, dan pemulihan.
- Kamera bukan syarat CBT standar.
- Reset Attempt, force submit, takeover, dan perpanjangan waktu wajib otorisasi serta audit log.
- Browser deterrent bukan secure kiosk; jangan menjanjikan pencegahan screenshot/kamera/perangkat kedua.

## 3. Stack dan batas implementasi

- Backend: Laravel 13, PHP 8.3, MySQL 8+.
- Web: Blade + Livewire.
- Android masa mendatang: Kotlin, Jetpack Compose, Room, WorkManager, Retrofit.
- Modular monolith, instalasi/database mandiri per madrasah.
- Android build harus melalui Android Studio ketika fase Android dibuka. **Jangan jalankan Gradle via terminal.**
- Jangan mengubah source baseline asli. Semua perubahan pada branch kerja, dengan backup byte-exact tiap file existing yang diubah.
- Jangan melakukan migration eksperimental pada database produksi.

## 4. Status bukti yang sudah tercatat

### Web Admin / integrasi backend
- PR #21 adalah jalur integrasi Web CBT dan tetap harus diverifikasi status/head/CI aktual.
- Checkpoint `docs/CHECKPOINT_LANJUTAN_WEB_CBT_2026-11-23.md` mencatat full PHPUnit masih bermasalah pada beberapa kelompok: fixture Guru tanpa assignment kanonis, Exam/Scoring/Monitoring, legacy migration test, dan ExamManagement ViewException.
- Run #45140404370 pada SHA `a97301d8b5512a7104978197c3b5dcfeef55458d` pernah dilaporkan: focused login/tenant PASS, full PHPUnit 124 failed / 367 passed / 1,421 assertions. Itu bukan hasil source HEAD terkini; periksa CI terbaru sebelum mengambil kesimpulan.
- Inventaris Admin ADM-01–ADM-23 dan penutupan acceptance masih perlu dilanjutkan.
- PR #18 memiliki rancangan/implementasi ruang ujian terpusat yang berbeda dengan keputusan terbaru mengenai Proktor administratif. Jangan gabungkan fitur room/monitoring/seat/session dari PR lama tanpa memeriksa keputusan owner terbaru. PR #21/checkpoint integrasi adalah acuan lebih baru.

### Web Guru
- PR #19 mendokumentasikan penghapusan XLSX question import, DOCX sebagai jalur prioritas, regression tests Bank Soal/Exam Builder/hasil/monitoring, dan beberapa CI PASS historis.
- Bukti PR #19 mencatat monitoring route belum sepenuhnya menerapkan EnsureTeacherPortalAccess/exact-one role; integrasi kurikulum ke QuestionVersion/snapshot belum lengkap; DOCX contract final masih pending; Pembelajaran Mendalam, delapan dimensi, provenance Panca Cinta, materi/topik, Pengaturan Guru dan kelengkapan portal masih perlu ditutup.
- Jangan menganggap CI historis PR #19 sebagai bukti bahwa source terintegrasi di PR #21 sudah lulus.

### Web Siswa
- Audit anti-curang branch integrasi pada 23 Nov menyatakan anti-copy/paste belum ditemukan pada source yang diperiksa, dual-login parsial melalui session-bound device-binding UUID, belum ada lease/fencing penuh, dan mobile-first baru responsive dasar serta belum terverifikasi. Lihat `docs/REPORT_AUDIT_ANTI_CHEAT_WEB_SISWA_MOBILE_FIRST_2026-11-23.md`.
- **PENTING: ada pekerjaan Agent 4 yang lebih baru di PR #13** daripada audit integrasi tersebut. Deskripsi PR #13 melaporkan:
  - IndexedDB v2 untuk attempt context, answers, sync queue, server receipts, conflicts, local metadata.
  - Penulisan jawaban + queue atomik, request ID/payload/base revision untuk retry identik.
  - Konflik 409 dipersistenkan dan submit diblokir; draft lokal dipulihkan terpisah dari jawaban server.
  - Submit barrier, state SUBMITTING/UNKNOWN, pemulihan tanpa POST start/submit ganda.
  - Mobile-first: satu kolom pada HP, dua kolom mulai 1024px, navigasi HP ringkas/floating, target sentuh 44–48px.
  - Copy/cut/selection/context menu/drag/shortcut dibatasi pada area soal sebagai deterrent; input/media/link dikecualikan.
  - Chromium E2E dan static assertions pernah PASS; satu run gabungan #45063309059 melaporkan runner static + Chromium E2E SUCCESS serta disposable MySQL tests 7 + 6 passed.
  - Namun PR #13 sendiri menyatakan browser mock bukan integrasi Laravel/MySQL penuh; lease/fencing, token condition, answer receipt contract, API integration, concurrency, WebKit/iOS Safari dan UAT masih menjadi gate.
- Jangan mengulang patch Agent 4. Baca commit, source, checkpoint, workflow dan CI terbaru pada PR #13/branch-nya; bandingkan dengan source integrasi PR #21. PR #13 tidak otomatis terintegrasi ke PR #21.

## 5. Urutan prioritas kerja yang direkomendasikan

### P0 — keamanan dan integritas backend
1. Verifikasi exact-one role, permission, assignment, scope, dan auth/session untuk ketiga portal.
2. Pastikan isolasi instalasi/database per madrasah dan akses lintas scope ditolak.
3. Audit lifecycle Attempt/Answer/Score, transaksi, locking, idempotensi, revision, server time, audit log.
4. Tutup seluruh kegagalan full PHPUnit dengan memperbaiki fixture/kontrak yang salah; jangan menurunkan policy.
5. Pastikan snapshot, payload soal, media, dan answer key tidak bocor.

### P1 — keamanan dan ketahanan ujian Web Siswa
1. Satukan kontrak backend device identity + lease + fencing generation; expiry/revoke/heartbeat; takeover hanya dengan otorisasi.
2. Pastikan start/resume/execution/answer/media/submit memvalidasi lease/fencing yang sama; stale client ditolak.
3. Selesaikan token required/optional contract antara UI dan API.
4. Definisikan answer receipt dan read-only receipt lookup untuk response loss.
5. Uji multi-tab, multi-device, concurrent start, stale writes, duplicate retry, expiry/submit race.
6. Integrasikan IndexedDB runner Agent 4 dengan API Laravel yang nyata.
7. Browser deterrent: copy/cut/paste/context menu/shortcut/selection hanya best-effort; visibility/focus jadi audit signal, bukan hukuman otomatis.
8. Jangan auto-submit atau menghukum siswa hanya karena focus loss (panggilan, notifikasi, keyboard virtual, aksesibilitas).

### P2 — kelengkapan Admin dan Guru
1. Inventaris Admin ADM-01–ADM-23: setiap requirement dipetakan ke route, Livewire/controller, model, policy, migration, test, status.
2. Audit seluruh alur master data, import, tahun ajaran/semester, kelas/rombel, guru/siswa/mapel, penugasan, laporan administratif, backup/restore.
3. Audit seluruh alur Guru: assignment, CP/TP/ATP, bank soal/versioning, DOCX import, media, Exam Builder, publish/token/jadwal, result/grading/finalization.
4. Audit monitoring dan Proktor sesuai batas kewenangan terbaru; jangan menghidupkan portal Proktor atau hak CBT yang dilarang.

### P3 — mobile-first dan integrasi akademik
1. Uji Web Siswa Android Chrome dan iOS Safari, viewport kecil, portrait/landscape, keyboard virtual, scroll/focus, timer, touch target, low bandwidth, offline/reconnect.
2. Tutup provenance Kurikulum Merdeka/KBC/Panca Cinta/Pembelajaran Mendalam/8 dimensi sampai QuestionVersion dan immutable snapshot.
3. Uji DOCX upload, validasi, private staging, rollback, media authorization dan lifecycle.
4. Pastikan laporan hasil belajar mempertahankan versi kurikulum dan riwayat.

### P4 — acceptance, deployment, distribusi
1. Full PHPUnit exact SHA PASS.
2. Browser E2E ketiga portal.
3. Disposable MySQL 8 fresh install, legacy upgrade, repeat seeder, rollback guard.
4. Backup/restore dengan verifikasi integritas.
5. Security/concurrency/load/recovery tests.
6. UAT Admin/Guru/Siswa di madrasah.
7. Rencana deployment VPS, preflight, backup dan persetujuan eksplisit.
8. ZIP generik baru dibuat setelah seluruh gate lulus; verifikasi struktur, ekstraksi, konfigurasi dan README. Jangan klaim siap distribusi sebelum itu.

## 6. Daftar sumber dan file untuk dilanjutkan

Selalu cek versi paling baru dan ref pemiliknya sebelum edit.

### Branch integrasi / laporan
- `docs/CHECKPOINT_LANJUTAN_WEB_CBT_2026-11-23.md`
- `docs/REPORT_CI_FULL_PHPUNIT_REMEDIATION_2026-11-23.md`
- `docs/REPORT_AUDIT_ANTI_CHEAT_WEB_SISWA_MOBILE_FIRST_2026-11-23.md`
- Source siswa yang pernah diaudit:
  - `backend-laravel/resources/views/student/exams/show.blade.php`
  - `backend-laravel/app/Http/Controllers/StudentExamController.php`
  - `backend-laravel/app/Http/Controllers/Api/V1/ExamAttemptController.php`
  - `backend-laravel/app/Http/Controllers/Api/V1/ExamAnswerController.php`
  - `backend-laravel/app/Models/ExamAttempt.php`

### PR / branch independen yang wajib direkonsiliasi
- PR #13: Agent 4 Web Siswa; baca latest checkpoint, audit, runner, browser E2E test, workflow, commit dan CI di head branch PR tersebut.
- PR #19: Web Guru; baca laporan curriculum audit, DOCX design, CI result dan checkpoint pada head branch PR.
- PR #18: Web Admin legacy room/proctor work; cek ulang terhadap owner decision terbaru sebelum reuse.
- PR #21: branch integrasi tunggal; periksa source aktual dan seluruh CI.

## 7. Matriks status minimum yang wajib dibuat agent baru

Untuk setiap requirement Admin/Guru/Siswa, catat:
- ID requirement dan deskripsi dari spesifikasi owner.
- Route/UI entry point.
- Controller/Livewire/service/model.
- Policy/middleware/permission/assignment/scope.
- Schema/migration/index/FK yang relevan.
- Test unit/feature/browser/E2E yang benar-benar ada.
- Source ref/SHA dan PR pemilik.
- Status: PASS, PARTIAL, FAIL, NOT IMPLEMENTED, NOT VERIFIED.
- Risiko dan dependency.
- Acceptance criteria serta bukti CI/UAT.
- Backup path dan commit patch jika sudah diubah.

Jangan menyatakan "sudah membaca seluruh fitur" sebelum matriks seluruh requirement terisi dan setiap item punya bukti source.

## 8. Protokol patch dan pelaporan

- Audit read-only terlebih dahulu.
- Sebelum edit existing file: ambil source pada branch kerja, simpan backup byte-exact pada lokasi backup yang konsisten, bandingkan blob/hash/isi.
- Satu perubahan per akar masalah, tulis regression test.
- Jangan menjalankan migration terhadap DB produksi. Pakai disposable MySQL.
- Jangan jalankan Gradle via terminal.
- Setelah patch: PHP lint/format bila relevan, focused tests, full CI exact SHA, browser E2E bila terkait.
- Perbarui laporan/checkpoint dengan commit SHA, source SHA, file berubah, backup, test run, hasil dan batasan.
- Tidak ada klaim PASS tanpa run ID/log. Mock browser test bukan Laravel integration test.
- Tidak merge/deploy/ZIP distribution tanpa approval/gates.

## 9. Posisi terakhir yang harus diverifikasi

Checkpoint branch integrasi yang tersedia masih menyebut source SHA lama `a97301d8b5512a7104978197c3b5dcfeef55458d` untuk CI #45140404370 dan PR #21 Draft/unmerged. Audit anti-cheat integrasi memakai SHA `4d505fed8d79d81ab0af2fbc41b6ad082203dcff`. Ada aktivitas Agent 4 yang lebih baru pada PR #13 dengan commit/checkpoint terpisah. Oleh sebab itu, **jangan gunakan salah satu SHA ini sebagai HEAD saat ini tanpa verifikasi**.

Prioritas aksi pertama bukan coding: verifikasi PR #21 dan PR #13 terbaru, ambil hasil CI/log yang belum dicatat, rekonsiliasi source Agent 4 ke branch integrasi, lalu buat matriks Admin/Guru/Siswa dan mulai P0 yang benar-benar masih terbuka.

---

**Status akhir pada saat handover:** belum siap produksi, belum siap ujian operasional, belum siap distribusi. Tidak ada izin merge, migration produksi, deployment, atau perubahan database produksi yang diberikan oleh dokumen ini.


---

## Addendum rekonsiliasi aktual 23 November 2026

Laporan lintas portal terbaru: [REPORT_REKONSILIASI_AWAL_KETIGA_PORTAL_2026-11-23.md](REPORT_REKONSILIASI_AWAL_KETIGA_PORTAL_2026-11-23.md). Gunakan laporan ini bersama checkpoint utama.

### HEAD yang terakhir diverifikasi
- PR #21 integrasi: `44c2b7e3f84f5fef4ff749add13a1fd75369fa6d`.
- PR #13 Web Siswa: `9ef375c407b1faf606c5a268f00b24424d93be9e`.
- PR #19 Web Guru: `71296ca6e7c48b7295455a26d38bda11b901eb1d`.
- PR #18 Web Admin legacy room/proctor: `42a0de2c1f86b8e76e34e1b74ab2e8f0` (verifikasi ulang SHA PR via GitHub sebelum bertindak).
- Agent 2 auth/session/fencing: `b00cf4312b12e1158c609ca91af3dcecec7a2553`.

### Koreksi status Web Siswa
Audit anti-curang lama di branch integrasi memakai runner lama. PR #13 telah memiliki runner lebih baru yang memuat deterrent copy/paste, mobile-first, IndexedDB, client fencing/heartbeat, media fetch, dan receipt verifier. Jangan mengulang implementasi tersebut. Namun PR #13 HEAD terbaru belum mempunyai bukti CI exact SHA dan browser-to-real-Laravel integration masih belum lulus.

### P0 backend Agent 2
Run #45066851753 pada exact Agent 2 HEAD: migration fresh MySQL 8 dan PHP lint PASS; suite gagal 4 test (78 passed/293 assertions); concurrency test SKIPPED. Dua mismatch yang dibaca langsung dari source: kondisi token optional terbalik dan receipt response belum mengembalikan `client_request_id`. Empat test failure harus ditriase; jangan mengubah policy menjadi longgar.

### Integrasi branch
Branch Agent 2 sangat divergen dari base integrasi dan membawa banyak file/fitur historis. Dilarang merge keseluruhan branch secara otomatis. Integrasikan hanya kontrak P0 yang telah diaudit dan disetujui, dengan backup dan regression tests.
PR #18 room/session/multi-Proktor juga jangan diintegrasikan otomatis karena bertentangan dengan keputusan owner terbaru. Proktor tidak boleh menjadi portal/role permanen atau mendapatkan hak monitoring/Attempt/operasi CBT.
PR #19 mempunyai hasil CI historis 118/520, tetapi source SHA tersebut berbeda dari PR HEAD yang sekarang; verifikasi exact HEAD terlebih dahulu.

### Status handover setelah rekonsiliasi
- Laporan rekonsiliasi dibuat: commit `8b8fc6fc645779152f36cd53bf3db59ef6490d03`.
- Checkpoint ditambahkan: commit `0fe5e30b7e0cf9fdff1a4bf092ad510939725358`.
- Tidak ada source runtime, migration, DB produksi, VPS, merge, atau deployment yang diubah pada tahap audit ini.


Addendum current CI and unified curriculum

## Addendum current state — 23 November 2026

Read [REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md](REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md) before any further source changes.

- Latest documented failing integration run: [#45148868236](https://github.com/zane0184/cbt_november/actions/runs/45148868236), SHA `6fcdc757fa04ca04a1871b1685e0c89d67ccfd13`: focused login smoke 27/90 PASS; full PHPUnit 163 failed/346 passed/1,388 assertions.
- Login root cause was identified: total Tenant count included reserved SYSTEM_GLOBAL. Resolver fix is committed `2f1becace2e2d0fdb5891f586426b774f3eaea08`; regression test `AuthTest` commit `d70270d55de8b5e0bfd8429ce7b0cae3c4bca1c1`. Both files have byte-exact backups. CI exact final HEAD was queued/cancelled by concurrency at handover; verify current run before claiming.
- Curriculum owner clarification is appended to `docs/DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md`: Kurmer, Panca Cinta, and Pembelajaran Mendalam are one integrated ecosystem, not three separate curricula. Canonical mapping/snapshot code exists; achievement/report code still uses legacy LearningOutcome/LearningObjective/LearningPath. This is a material P3 gap.
- Do not repeat the existing auth/lease/receipt integration patches or rebuild the Agent 4 runner. Read the latest source and test evidence first.
- Keep PR #21 Draft/unmerged. No production migration, deployment, Android build, or distribution ZIP.


## Addendum handover agent pengganti — 23 November 2026
Baca [REPORT_HANDOVER_AGENT_PENGGANTI_2026-11-23.md](REPORT_HANDOVER_AGENT_PENGGANTI_2026-11-23.md) dan ikuti [PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md](PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md).

- Branch `work/solo-web-cbt-completion-20261123`, HEAD `dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4`.
- PR #21 OPEN/DRAFT/UNMERGED.
- Backend run #45156925960: focused login/tenant PASS; full PHPUnit FAILURE; log detail tidak tersedia pada saat handover.
- Agent 4 Web Siswa run #45156926014: static audit, Chromium, WebKit compatibility, selected Laravel Student feature jobs PASS; belum browser-to-real-Laravel E2E atau UAT Safari fisik.
- Commit koordinator `dbebbd5` membatasi API materials menjadi read-only, menambah policy read, menghapus pilihan filter role legacy UI Admin, dan menambah regression test. Empat file existing dibackup byte-exact.
- Prioritas: triase full PHPUnit dari log aktual; audit kompatibilitas MaterialController; integrasi Web Siswa ke Laravel nyata; matriks ADM-01–ADM-23; Guru end-to-end; kurikulum sampai laporan historis; acceptance, backup/restore, UAT.
- Keputusan owner Issue #20 tetap mengikat. Tidak ada merge, production migration, deployment, atau ZIP.
