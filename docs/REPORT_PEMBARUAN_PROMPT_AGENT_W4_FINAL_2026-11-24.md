# LAPORAN PEMBARUAN PROMPT AGENT PENGGANTI — W4 FINAL
Tanggal: 24 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/solo-web-cbt-completion-20261123-execution`

## AUDIT
- Membaca laporan/checkpoint W3 Audit Events, laporan sebelumnya, AGENT_START_HERE, dan source/test terkait.
- Memverifikasi exact source commit `f4397b8999ddf31a96d4d04b15bcfac4473b5203`.
- Verifikasi langsung Actions #1734 run ID `45278477028`, attempt 2: conclusion SUCCESS.
- Job laravel-mysql seluruh langkah SUCCESS, termasuk PHP 8.3, disposable MySQL 8.0.46, focused login/tenant, Admin reauthorization, Teacher result reauthorization, full PHPUnit, InnoDB post-check dan cleanup.
- Full PHPUnit: **536 passed / 2,250 assertions / 0 failed**.
- CI attempt 1 cancelled concurrency, tetapi attempt 2 berhasil. Status tidak boleh lagi disebut pending/cancelled.
- Test W3 Audit Events di `AdminUiVerificationTest` telah dieksekusi dan seluruh suite PASS. Audit CREATE akun Guru/Siswa, rombel, mapel dinyatakan IMPLEMENTED & TESTED.
- W1 authorization, baseline PHPUnit remediation 42 failure, CI InnoDB patch, dan profil dasar Admin juga telah selesai dan tidak boleh diulang tanpa pemicu yang sah.
- Remaining backlog dari laporan W3: lifecycle Guru/Siswa; tahun ajaran/semester/histori rombel; import/export administrasi; profil/branding/kop; Kurmer+KBC/DOCX dependency contract; Web Guru/Siswa E2E; audit operasional lainnya; backup/restore dan acceptance.
- ADM-20 tetap blocked sampai canonical eligibility marker owner ditetapkan. DOCX contract masih blocked sesuai laporan sebelumnya.
- Website live sudah berjalan dan VPS/instalasi telah dinyatakan terverifikasi oleh owner; tidak perlu tanya spesifikasi VPS.

## DESAIN
Prompt terbaru:
1. Mengoreksi status test audit event dari PENDING menjadi PASS berdasarkan #1734 attempt 2.
2. Mengunci patch Audit Events sebagai selesai dan melarang implementasi duplikat.
3. Mengunci hasil CI InnoDB #1722/#1734 dan melarang patch ulang.
4. Mengunci profil dasar Admin dan W1 reauthorization serta baseline PHPUnit yang sudah selesai.
5. Mengarahkan agent langsung ke backlog yang benar-benar tersisa, dimulai dari lifecycle akun Guru/Siswa atau lifecycle akademik.
6. Mempertahankan keputusan owner dan blocker; melarang meminta ulang spesifikasi VPS.
7. Menegaskan target satu ZIP final setelah acceptance seluruh fitur, bukan membuat ZIP sekarang.

## IMPLEMENTASI DOKUMENTASI
- Dibuat `docs/PROMPT_AGENT_PENGGANTI_CBT_W4_FINAL_2026-11-24.md`
  - Commit: `be7278c70bdd6e647687ddddee365a80e335674b`.
- Diperbarui `docs/AGENT_START_HERE.md`
  - Blob lama: `655824010fca3916362b6862db3bfb86db4786ef`.
  - Commit: `15cfbd253bdf310a7cd3c4ac680712d2f91be154`.
  - Blob baru: `37a63a4a018f50d5df8f017dc85903190ed1442d`.
- Source aplikasi, workflow CI, database, migration, website/VPS tidak diubah.
- Tidak ada merge/deploy atau ZIP distribution.

## PENGUJIAN / VERIFIKASI
- Fetch GitHub report/checkpoint W3 dan source branch.
- Fetch commit `f4397b8...` diff, membuktikan koreksi sintaks pada test.
- Fetch Actions #1734 run, jobs, steps dan job logs. Confirmed SUCCESS, 536 tests / 2,250 assertions / 0 failed, InnoDB post-check SUCCESS.
- Tidak menjalankan test baru selama tahap dokumentasi ini.
- Latest HEAD pada saat penulisan prompt W4 sebelum dokumentasi baru: `883f681fb42cfef9b6d7a8f73613c89e841cfe0f`; setelah penambahan prompt dan update Agent Start Here, fetch ulang HEAD diperlukan oleh agent berikutnya.

## ARAHAN AGENT BERIKUTNYA
Baca W4 Final dan AGENT_START_HERE; fetch HEAD terbaru; jangan mengulang W3 Audit Events, syntax fix, CI patch, profil dasar, W1 reauthorization, atau remediasi PHPUnit. Lanjutkan lifecycle Guru/Siswa dan lifecycle akademik yang belum selesai, berikutnya import/export, profil lanjutan, integrasi Web Guru/Siswa, kurikulum/asesmen, hardening dan acceptance. Update checkpoint setelah tiap tahap.

## STATUS
- W3 Audit Events: **IMPLEMENTED & TESTED**.
- CI #1734: **SUCCESS**.
- CI InnoDB post-check: **GREEN**.
- Profil dasar Admin: **IMPLEMENTED & TESTED**.
- Backlog fitur portal: **IN PROGRESS**.
- ZIP final: **NOT READY FOR DISTRIBUTION**.
- Produksi: **TIDAK DIKLAIM SIAP**.
