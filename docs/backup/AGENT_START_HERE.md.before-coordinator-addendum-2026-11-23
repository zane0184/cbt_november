# PEMUTAKHIRAN WAJIB AGENT PENGGANTI — 23 NOVEMBER 2026

**BACA TERLEBIH DAHULU:** [HANDOVER_AGENT_BARU_PHPUNIT_2026-11-23.md](HANDOVER_AGENT_BARU_PHPUNIT_2026-11-23.md). Dokumen tersebut adalah handover operasional terbaru dan mengesampingkan branch/SHA/angka PHPUnit lama yang masih tercantum di bawah.

- Repository: zane0184/cbt_november.
- Branch aktif: work/solo-web-cbt-completion-20261123-execution.
- Source SHA full PHPUnit terakhir: b31d7a2713383ddcc1282317cdb295d6992d5dce.
- CI terakhir #45168433247: **92 failed, 426 passed, 1,723 assertions** pada disposable MySQL 8.0.46; focused login smoke 27 passed/90 assertions.
- Baseline #45156925960: 160 failed, 357 passed, 1,475 assertions. Kegagalan sudah turun 68, tetapi full suite belum PASS.
- Baca triage dan matriks ADM terbaru; jangan mulai coding sebelum verifikasi HEAD/CI exact SHA.

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


Add current continuation report link

## Laporan lanjutan terbaru
Baca juga [REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md](REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md) dan [DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md](DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md) sebelum coding. Laporan terbaru mencatat exact CI failure, patch resolver SYSTEM_GLOBAL dan backup, serta gap integrasi kurikulum sampai laporan historis. Jangan ulang patch yang telah tercatat.


## HANDOVER AGENT PENGGANTI — 23 NOVEMBER 2026
- [Laporan handover lengkap, kondisi source/CI, perubahan terbaru, backlog dan kriteria selesai](REPORT_HANDOVER_AGENT_PENGGANTI_2026-11-23.md)
- [Prompt operasional agent pengganti — siap salin](PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md)

HEAD saat handover dibuat: `dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4` pada `work/solo-web-cbt-completion-20261123`. **Wajib verifikasi ulang** karena branch/CI dapat berubah.
Backend CI #45156925960: focused login/tenant PASS, full PHPUnit FAILURE. Web Siswa #45156926014: job Agent 4 yang disebut di laporan PASS, tetapi belum real-Laravel E2E.
Jangan ulang patch `dbebbd5`; audit kompatibilitas MaterialController read-only terlebih dahulu. PR #21 tetap OPEN/DRAFT/UNMERGED.
