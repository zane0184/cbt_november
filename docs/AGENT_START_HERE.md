# STATUS TERBARU PROYEK CBT MADRASAH — 23 NOVEMBER 2026

**Titik kerja aktif dan bukti terbaru:**

- Repository: `zane0184/cbt_november`.
- Branch sumber eksekusi: `work/solo-web-cbt-completion-20261123-execution`.
- HEAD source eksekusi: `d6aaeae6f2851bf15272d0cc2bce9e68989d3a4d`.
- Branch remediasi P0: `work/phpunit-41-root-cause-20261123`.
- SHA source/test yang lulus full CI pertama kali: `085a20eea16ada12e8c055fca6be9bb1d6d6972a`.
- SHA branch dokumentasi yang lulus verifikasi ulang: `6b5e412e18e5c9230c0b17f18a9d569bdd4d7982`.
- SHA HEAD PR #22 yang lulus verifikasi ulang: `2899baf49e3432322643d5fa72b3a08a715852ef`.
- CI [#45215342323](https://github.com/zane0184/cbt_november/actions/runs/45215342323), [#45217367885](https://github.com/zane0184/cbt_november/actions/runs/45217367885), [#45218279043](https://github.com/zane0184/cbt_november/actions/runs/45218279043), dan [#45219173631](https://github.com/zane0184/cbt_november/actions/runs/45219173631): **518 passed, 0 failed, 2.189 assertions**, PHP 8.3, disposable MySQL 8.0.46/InnoDB.
- PR remediasi: [#22 — Draft, base branch eksekusi, mergeable clean](https://github.com/zane0184/cbt_november/pull/22). Jangan merge otomatis.
- Current checkpoint: [CHECKPOINT_KOORDINATOR_2026-11-23_P0_PHPUNIT_518_PASS.md](CHECKPOINT_KOORDINATOR_2026-11-23_P0_PHPUNIT_518_PASS.md).
- Audit dan backlog: [REPORT_KOORDINATOR_AUDIT_GITHUB_DAN_ROOT_CAUSE_2026-11-23.md](REPORT_KOORDINATOR_AUDIT_GITHUB_DAN_ROOT_CAUSE_2026-11-23.md).
- Arahan owner terbaru: [ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md](ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md).

**Perhatian exact SHA:** Source/test code setelah SHA 085a20ee tidak berubah. CI #45219173631 lulus pada HEAD PR #22 2899baf. Perubahan dokumentasi/checkpoint sesudahnya tetap memicu CI push baru; jangan merge sebelum checks terbaru selesai.

## Arahan kerja yang mengikat

1. Baca checkpoint, report, addendum, lalu master handover dan seluruh spesifikasi fitur Admin/Guru/Siswa.
2. Sumber utama hanya GitHub. Jangan membuka ZIP baseline, Project Files, atau Library untuk pekerjaan proyek ini.
3. Jangan mengulang patch/fitur agent sebelumnya. Periksa HEAD, PR, source, backup, issue, dan CI exact SHA sebelum integrasi.
4. Role aktif hanya ADMIN_MADRASAH, GURU, SISWA. Operator dilebur ke Admin; Kamad dibekukan; Proktor adalah capability sementara dalam Web Guru.
5. Satu madrasah = satu instalasi dan database akademik sendiri. Tidak ada shared multi-tenant antar-madrasah.
6. Android native dan Web Super Admin ditunda sampai Juni 2027.
7. Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + delapan dimensi profil lulusan adalah satu kesatuan.
8. Backup byte-exact sebelum setiap perubahan file existing. Jangan melemahkan RBAC, assignment scope, snapshot, FK, transaction, idempotency, atau fencing.
9. Jangan migration produksi, deploy VPS, merge PR, atau membuat ZIP final tanpa preflight, acceptance, dan persetujuan owner.
10. Laporan setiap tahap harus memisahkan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN. Klaim PASS harus menunjuk exact SHA dan CI.

---

# WAJIB DIBACA — TITIK MASUK AGENT CBT MADRASAH

**Tanggal handover:** 23 November 2026  
**Repository:** https://github.com/zane0184/cbt_november  
**Branch kerja yang ditetapkan:** `work/solo-web-cbt-completion-20261123`  
**PR terpadu:** [#21 — Solo Web CBT](https://github.com/zane0184/cbt_november/pull/21) — jangan merge tanpa persetujuan owner dan seluruh gate lulus.

## Instruksi pertama untuk agent baru

Jangan mulai coding, jangan membuat ulang fitur, dan jangan mengasumsikan status dari dokumen ini saja.

1. Baca dokumen ini sampai selesai.
2. Baca [AGENT_HANDOVER_MASTER_2026-11-23.md](AGENT_HANDOVER_MASTER_2026-11-23.md).
3. Verifikasi HEAD aktual branch kerja, PR #21, status CI terkini, dan perubahan setelah checkpoint.
4. Baca laporan audit/implementasi terbaru untuk Admin, Guru, Siswa, backend, serta backup yang terkait. Gunakan source aktual sebagai kebenaran; jika berbeda dari laporan, catat perbedaannya.
5. Buat ringkasan AUDIT → DESAIN → IMPLEMENTASI → PENGUJIAN dan lanjutkan dari pekerjaan yang benar-benar belum selesai. Jangan mengulang patch yang sudah ada.
6. Sebelum menyentuh file existing, buat backup byte-exact, verifikasi hash/isi, lalu patch hanya di branch kerja.
7. Perbarui checkpoint dan laporan GitHub setelah setiap tahap.

## Dokumen kanonis dan bukti

- [Master handover dan backlog](AGENT_HANDOVER_MASTER_2026-11-23.md)
- [Checkpoint Web CBT](CHECKPOINT_LANJUTAN_WEB_CBT_2026-11-23.md)
- [Audit Anti-Curang dan Mobile-First Web Siswa](REPORT_AUDIT_ANTI_CHEAT_WEB_SISWA_MOBILE_FIRST_2026-11-23.md)
- [Laporan CI/remediasi backend](REPORT_CI_FULL_PHPUNIT_REMEDIATION_2026-11-23.md)
- [PR #21](https://github.com/zane0184/cbt_november/pull/21)
- [PR #13 Web Siswa Agent 4](https://github.com/zane0184/cbt_november/pull/13)
- [PR #19 Web Guru](https://github.com/zane0184/cbt_november/pull/19)
- [PR #18 Web Admin](https://github.com/zane0184/cbt_november/pull/18)
- [Issue #20 keputusan owner](https://github.com/zane0184/cbt_november/issues/20)

## Batas keselamatan yang tidak boleh dilanggar

- Tidak merge PR, deploy VPS, menjalankan migration produksi, atau mengubah DB produksi tanpa persetujuan eksplisit owner.
- Tidak menyentuh ZIP baseline, Project Files, Library, atau sumber di luar GitHub untuk pekerjaan proyek ini.
- Tidak menjalankan Gradle via terminal. Android native dan Web Super Admin ditunda sampai Juni 2027.
- Jangan menghidupkan kembali role/portal Operator, Kamad, Proktor, atau Super Admin lokal. Portal aktif: Admin Madrasah, Guru, Siswa.
- Tidak ada database akademik multi-tenant lintas madrasah. Setiap madrasah punya instalasi dan database terpisah.
- Jangan melemahkan authorization/policy hanya untuk meluluskan test lama.
- Jangan menyatakan siap produksi, siap ujian operasional, atau siap distribusi tanpa bukti acceptance yang relevan.

**Penting:** GitHub menyimpan handover ini, tetapi agent baru tidak otomatis menerima notifikasi atau membaca repo hanya karena dokumen dibuat. Saat pindah chat/agent, berikan instruksi singkat:  
`Lanjutkan proyek CBT dari GitHub. Baca docs/AGENT_START_HERE.md lalu ikuti seluruh handover dan lanjutkan dari checkpoint aktual. Jangan ulang pekerjaan.`


## Laporan historis sebelumnya
[REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md](REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md) dan [DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md](DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md) tetap menjadi referensi historis. Status aktual dan backlog terbaru ditentukan oleh checkpoint serta laporan koordinator di bagian atas.


## HANDOVER AGENT PENGGANTI — 23 NOVEMBER 2026
- [Laporan handover lengkap, kondisi source/CI, perubahan terbaru, backlog dan kriteria selesai](REPORT_HANDOVER_AGENT_PENGGANTI_2026-11-23.md)
- [Prompt operasional agent pengganti — siap salin](PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md)

HEAD saat handover dibuat: `dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4` pada `work/solo-web-cbt-completion-20261123`. **Wajib verifikasi ulang** karena branch/CI dapat berubah.
Backend CI #45156925960: focused login/tenant PASS, full PHPUnit FAILURE. Web Siswa #45156926014: job Agent 4 yang disebut di laporan PASS, tetapi belum real-Laravel E2E.
Jangan ulang patch `dbebbd5`; audit kompatibilitas MaterialController read-only terlebih dahulu. PR #21 tetap OPEN/DRAFT/UNMERGED.


## ADDENDUM KOORDINATOR — 23 NOVEMBER 2026

Baca juga dokumen terbaru berikut sebelum melakukan perubahan:
- `docs/ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md` — arahan owner terbaru tentang instalasi per madrasah, role, menu Proktor di Web Guru, penundaan Android/Web Super Admin, dan integrasi Kurmer+KBC. Dokumen ini mengesampingkan catatan GitHub lama yang bertentangan.
- `docs/REPORT_KOORDINATOR_AUDIT_GITHUB_DAN_ROOT_CAUSE_2026-11-23.md` — audit exact source SHA d6aaeae6, CI #45191014821, kelompok 41 kegagalan, backup, patch terisolasi, dan status CI perbaikan.

Branch perbaikan terisolasi saat ini: `work/phpunit-41-root-cause-20261123`. Jangan menganggap patch lulus sebelum CI exact SHA pada branch tersebut selesai dan log dibaca. Jangan mengulang perubahan yang tercatat dalam laporan.
