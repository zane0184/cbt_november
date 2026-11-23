# LAPORAN PEMBARUAN PROMPT AGENT PENGGANTI — W3 FINAL
Tanggal: 24 November 2026 (WIB)
Repository: `zane0184/cbt_november`
Branch: `work/solo-web-cbt-completion-20261123-execution`

## AUDIT
- Owner menegaskan website CBT yang telah berjalan adalah https://cbt.min15jakarta.sch.id/login dan sudah pernah dipaketkan ZIP serta dipasang di VPS.
- VPS, konfigurasi, dan instalasi sebelumnya telah diverifikasi ketat serta dinyatakan lancar. Tidak perlu menanyakan ulang spesifikasi VPS atau mengulang audit infrastruktur.
- Agent sebelumnya melaporkan perbaikan query post-check InnoDB pada `.github/workflows/tenant-backend-ci.yml` commit `1b25c3bbed07fba6def2dfe869d686a8559be1cc`, backup byte-exact tersedia dan static check telah dilakukan.
- Verifikasi mandiri GitHub pada saat laporan dibuat: HEAD branch `work/solo-web-cbt-completion-20261123-execution` = `df079f0ab51235b611b97b96c2cb10cc3d33d8eb`.
- Actions #1722 pada exact SHA `df079f0...` berstatus SUCCESS. Job `laravel-mysql` semua langkah SUCCESS, termasuk post-check InnoDB.
- Log #1722: focused login/tenant `30 passed / 99 assertions`; Admin reauthorization `5 passed / 5 assertions`; Teacher result reauthorization `4 passed / 8 assertions`; full PHPUnit **536 passed / 2,231 assertions / 0 failed**. InnoDB post-check dan cleanup SUCCESS.
- Dengan itu, masalah CI #1714 sudah ditutup. Jangan mengulang patch CI.
- Profil dasar Admin Madrasah tetap sudah diimplementasikan dan test profilnya telah lulus. Jangan membuat ulang.
- Sisa backlog fitur tetap luas: metadata profil lanjutan, logo/private asset, branding login, kop surat/preview, ADM-01..ADM-23, integrasi Web Guru/Siswa E2E, hardening dan acceptance distribusi.
- Branch HEAD terakhir pada saat dokumen ini dibuat adalah `df079f0...`; perubahan dokumentasi W3 dan pointer Agent Start Here akan menghasilkan commit baru, jadi agent berikutnya wajib fetch HEAD aktual.

## DESAIN
Prompt terbaru dirancang untuk:
1. Mengunci hasil CI #1722 sebagai GREEN dan mencegah pengulangan patch CI/triage PHPUnit.
2. Mengunci pekerjaan yang telah selesai: 42 failure PHPUnit remediation, W1 Livewire reauthorization subset, CI InnoDB post-check correction, profil dasar Admin.
3. Menetapkan backlog aktif dan urutan kerja berikutnya hanya pada gap nyata yang belum selesai.
4. Menegaskan proyek menyempurnakan aplikasi existing yang telah berjalan; bukan membangun ulang.
5. Menyatakan VPS sudah diverifikasi owner; tidak perlu minta spesifikasi VPS.
6. Menargetkan satu ZIP distribusi lengkap setelah seluruh fitur web dan acceptance gates lulus.
7. Mempertahankan seluruh keputusan produk: instalasi/database mandiri per madrasah; role lokal ADMIN_MADRASAH/GURU/SISWA; Proktor assignment sementara Guru; Kamad/Operator bukan portal; Kurmer+KBC satu sistem; DOCX utama; XLSX question import off; SHORT_ANSWER fail-closed; Android dan Web Super Admin ditunda Juni 2027.

## IMPLEMENTASI DOKUMENTASI
- Dibuat `docs/PROMPT_AGENT_PENGGANTI_CBT_W3_FINAL_2026-11-24.md`
  - Commit: `c07158253242dd1d00ae3a6fd43e0f58209c6448`.
- Diperbarui `docs/AGENT_START_HERE.md`
  - Blob lama: `7b6df5170b1320af16085c3e9003d444bec7ca0d`.
  - Commit: `5369bb0f2761b9513f5bd23f7860cf5a87b5cc07`.
  - Blob baru: `2e513897c9875517500dfb3264fbbd684d58885b`.
- Source aplikasi dan workflow tidak diubah dalam tahap pembaruan prompt.
- Tidak ada migration, DB production, deployment, website live, atau ZIP distribution disentuh.

## PENGUJIAN / VERIFIKASI
- Fetch ulang laporan CI patch dan checkpoint patch.
- Fetch commit `df079f0...` dan branch.
- Fetch Actions #1722, jobs, steps dan logs.
- Memastikan conclusion SUCCESS, seluruh job steps SUCCESS, full PHPUnit 536/2,231, dan post-check InnoDB SUCCESS.
- Verifikasi ini menggunakan bukti GitHub; tidak menjalankan test baru dalam pekerjaan dokumentasi.
- Link bukti Actions: https://github.com/zane0184/cbt_november/actions/runs/45276501877

## INSTRUKSI AGENT BERIKUTNYA
- Baca W3 Final dan `AGENT_START_HERE.md`.
- Fetch HEAD terbaru, karena dokumentasi handover telah membuat commit setelah df079f0.
- Jangan ulang patch CI #1722, profil dasar Admin, W1 reauthorization, atau remediasi 42 failure PHPUnit.
- Lanjutkan langsung gap profil Admin lanjutan/ADM backlog yang tidak blocked; setelah itu integrasi Guru/Siswa, hardening, acceptance, dan ZIP final.
- Jangan meminta spesifikasi VPS ulang; jangan melakukan eksperimen pada website live.

## STATUS
- Pembaruan prompt dan titik masuk: **SELESAI**.
- CI #1722: **SUCCESS / GREEN**.
- Remediasi baseline PHPUnit: **CLOSED**.
- Profil dasar Admin: **IMPLEMENTED / TESTED**.
- Fitur Web Admin/Guru/Siswa end-to-end: **BELUM SELURUHNYA ACCEPTED**.
- ZIP final: **BELUM DIBUAT / NOT READY FOR DISTRIBUTION**.
- Produksi: **TIDAK DIKLAIM SIAP**.
