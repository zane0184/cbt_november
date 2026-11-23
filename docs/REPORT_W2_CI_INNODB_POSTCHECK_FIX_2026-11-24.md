# Laporan Eksekusi — Perbaikan CI InnoDB Post-check (W2 continuation)

Tanggal: 24 November 2026 (WIB)
Repository: `zane0184/cbt_november`
Branch: `work/solo-web-cbt-completion-20261123-execution`

## AUDIT
- Membaca `docs/PROMPT_AGENT_PENGGANTI_CBT_W2_FINAL_2026-11-24.md`, `docs/REPORT_PEMBARUAN_PROMPT_AGENT_W2_FINAL_2026-11-24.md`, `docs/AGENT_START_HERE.md`, master spesifikasi Web Admin, Web Guru, Web Siswa (branch referensi Agent 4), Issue #20 dan Issue #10 beserta komentar owner.
- Source CI pada branch kerja masih memiliki query post-check:
  `... TABLE_NAME = 'tenants'` di dalam blok shell `php -r '...'`. Apostrof pada literal SQL memutus quoting shell sehingga post-check gagal sebelum dapat membuktikan engine tabel.
- CI #1714 pada source SHA `e894d3f4727e3bfcfdb45a29ce1da5c3abd6b598`: full PHPUnit 536 passed / 2,231 assertions / 0 failed; workflow tetap FAILURE hanya pada post-check InnoDB. Hasil tersebut bukan kegagalan PHPUnit dan tidak perlu remediasi ulang.
- Profil dasar Admin sudah ada; metadata tambahan, logo/private asset, branding login, kop surat/preview masih backlog. Schema Tenant yang diperiksa memuat `name, code, nsm, npsn, address, phone, email, logo_path, status`; belum ada bukti kolom metadata profil lanjutan.
- Keputusan owner pada Issue #20 comment 7091952804 menegaskan Kurikulum Merdeka + KBC end-to-end dengan satu kontrak backend, provenance/version/history, Panca Cinta, Pembelajaran Mendalam, delapan dimensi profil lulusan, materi/topik, mapping QuestionVersion dan snapshot immutable. Siswa hanya menerima data yang diizinkan; CP/answer key/is_correct/explanation privat/rubrik internal tidak boleh bocor.
- Taksonomi asesmen tidak boleh mengunci UTS/UAS/UM/SIMULASI TKA sebagai enum. Pisahkan fungsi, cakupan, nama kegiatan, instrumen dan mode CBT; TKA dipisahkan dari asesmen internal.

## DESAIN
- Perbaikan hanya pada query pemeriksaan workflow, bukan source aplikasi.
- Gunakan prepared statement dengan placeholder `TABLE_NAME = ?`, bind `tenants`, lalu tetap mewajibkan engine aktual `INNODB`. Kondisi hilang/non-InnoDB tetap exit 1 (fail-closed).
- Tidak ada perubahan migration, schema, role, portal, tenant behavior, source Web, database atau VPS.

## IMPLEMENTASI
File existing diubah:
- `.github/workflows/tenant-backend-ci.yml`

Perubahan:
- Query literal SQL yang memerlukan apostrof diganti prepared statement:
  `$stmt = $pdo->prepare("... TABLE_NAME = ?"); $stmt->execute(["tenants"]); $row = $stmt->fetch(...);`

Backup byte-exact:
- Path: `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/tenant-backend-ci.yml.before-postcheck-quote-fix`
- Source workflow blob sebelum patch: `c2d824eccf029f69d13a7b5dba3f5baa4adfe986`
- Blob backup hasil create: `c2d824eccf029f69d13a7b5dba3f5baa4adfe986` (SHA blob sama; isi 6,286 karakter).
- Commit backup: `e6f0d958a3beee6eac67a667ba9b9532552bb212`
- Blob workflow sesudah patch: `e21c6be218a374bafc19813fda63621fe3884d6a`
- Commit patch: `1b25c3bbed07fba6def2dfe869d686a8559be1cc`

## PENGUJIAN
- Fetch ulang workflow aktif dan backup melalui GitHub.
- Blob SHA backup sama dengan blob SHA file sumber sebelum patch; perubahan query aktif terverifikasi memakai prepared statement.
- Pemeriksaan statis memastikan pola apostrof shell yang bermasalah tidak lagi ada.
- Belum ada hasil GitHub Actions yang dapat dibaca pada saat laporan ini dibuat untuk commit patch; karena itu workflow baru dan post-check MySQL belum dinyatakan PASS.
- Tidak menjalankan PHPUnit lokal, migration, database produksi, browser E2E, deployment, atau akses VPS.

## BACKLOG / RISIKO
- Menunggu bukti workflow pada source commit setelah patch; jika masih gagal, perbaiki hanya penyebab aktual.
- Lanjutkan gap profil Admin W2 setelah audit schema dan storage: metadata tambahan membutuhkan desain schema backward-compatible; logo/private asset harus memakai jalur storage/delivery yang aman; branding dan kop surat/preview belum dibuat.
- Lanjutkan backlog Issue #20 ADM-01..ADM-23 berdasarkan acceptance evidence, bukan sekadar keberadaan menu.
- Web Guru dan Web Siswa masih memerlukan integrasi end-to-end terhadap kontrak backend; jangan menyalin perubahan dari branch agent yang diverged tanpa audit.
- ADM-20 tetap blocked bila canonical central-exam eligibility marker belum ditetapkan.
- Android dan Web Super Admin tetap deferred sampai Juni 2027.
- ZIP final belum dibuat dan belum siap distribusi.

## STATUS
Perbaikan query workflow: **IMPLEMENTED — STATICALLY VERIFIED; ACTIONS PENDING**.
Aplikasi/portal dan paket distribusi: **IN PROGRESS — NOT READY FOR DISTRIBUTION / NOT READY FOR PRODUCTION**.
