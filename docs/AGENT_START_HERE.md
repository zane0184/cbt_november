# TITIK MASUK WAJIB — CBT MADRASAH (OWNER-DIRECTED EXECUTION)

## 0. CATATAN TERKUNCI — FULL PHPUNIT SUDAH HIJAU (WAJIB BACA SEBELUM TEST)
- **Area remediasi Full PHPUnit berstatus CLOSED / GREEN BASELINE. Jangan mengulang audit, patch, atau full suite tanpa pemicu yang sah.**
- Baseline: source SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`; CI #45243430635; 518 passed, 2,177 assertions, 0 failed (PHP 8.3 + disposable MySQL 8.0).
- Baca [LOCKED_BASELINE_FULL_PHPUNIT_GREEN_2026-11-24.md](LOCKED_BASELINE_FULL_PHPUNIT_GREEN_2026-11-24.md) dan [laporan taksonomi/remediasi](REPORT_PHPUNIT_FAILURE_TAXONOMY_AND_GREEN_2026-11-24.md).
- Jangan ulang triage 42 failure, jangan rerun full PHPUnit hanya untuk konfirmasi, jangan patch fixture/source yang sudah hijau, dan jangan membuat patch duplikat.
- Buka kembali hanya jika owner meminta, ada perubahan source baru yang menyentuh lingkup, atau CI source baru membuktikan regresi relevan. Wajib sebut pemicu dan jalankan focused test terlebih dahulu.
- Ini pembekuan ruang lingkup secara dokumenter; bukan branch protection/read-only setting GitHub.

**Berlaku mulai 24 November 2026. Dokumen ini menggantikan arahan agent/ownership lama.**

## 1. Otoritas dan cara kerja
- Owner proyek adalah satu-satunya pengambil keputusan produk. ChatGPT bertindak sebagai satu-satunya koordinator sekaligus eksekutor Backend, Web Admin, Web Guru, dan Web Siswa.
- Tidak ada agent implementasi lain yang sedang bekerja. Jangan membagi tugas ke agent lain atau menunggu hasil agent lain.
- GitHub repository zane0184/cbt_november adalah satu-satunya sumber kebenaran pekerjaan proyek. Jangan memakai ZIP baseline, Project Files, Library/Pustaka, atau rangkuman percakapan sebagai sumber source/keputusan terbaru.
- Setiap sesi wajib memverifikasi branch/HEAD, PR, Issues dan komentar owner, commit, Actions exact SHA, source aktual, serta catatan/checkpoint terbaru di GitHub.
- Lanjutkan pekerjaan yang benar-benar tersisa. Jangan mengulang patch/fitur yang sudah ada. Jika laporan berbeda dengan source, source aktual dan hasil test exact SHA yang menjadi dasar.
- Untuk perubahan source: backup byte-exact terlebih dahulu, verifikasi backup, implementasikan perubahan sekecil mungkin, jalankan test yang relevan, kemudian buat laporan dan checkpoint di GitHub.
- Pengguna tidak memerlukan laporan rutin di chat. Tetap buat laporan/checkpoint dan prompt handover agent berikutnya di GitHub.
- Tanyakan owner terlebih dahulu hanya untuk keputusan produk/keamanan/data yang belum ditetapkan atau perubahan berisiko yang membutuhkan persetujuan. Jangan menanyakan ulang keputusan yang sudah eksplisit.

## 2. Keputusan owner yang wajib dipatuhi
- Fokus sampai Juni 2027: Backend Laravel + Web Admin Madrasah + Web Guru + Web Siswa.
- Android native dan Web Super Admin ditunda sampai Juni 2027. Jangan mengimplementasikan atau menguji fitur tersebut sekarang.
- **Tidak ada multi-tenant akademik.** Satu paket ZIP generik dipakai semua madrasah, tetapi setiap madrasah memiliki instalasi dan database sendiri. Data akademik antar-madrasah tidak saling terhubung atau terbagi.
- Integrasi pusat masa depan hanya untuk lisensi, entitlement dan pembayaran; Web Super Admin ditunda sampai Juni 2027.
- Role lokal resmi hanya ADMIN_MADRASAH, GURU, SISWA. Portal/role KAMAD dan OPERATOR dibekukan/dihapus; seluruh pekerjaan Operator berada pada Admin Madrasah.
- Tidak ada portal/role PROKTOR permanen. Proktor hanya menu/penugasan operasional sementara di portal Guru. Penugasan tidak otomatis memberi akses monitoring, room/session, roster, Attempt, jawaban, atau operasi CBT.
- Guru mengelola bank soal, asesmen, ujian, jadwal, token dan penerbitan sesuai assignment tanpa persetujuan Admin.
- Admin mengelola administrasi, master data, akun, akademik, sinkronisasi, dukungan teknis, audit dan pengaturan; tidak mengelola authoring soal/ujian, token, peserta ujian, Attempt, monitoring, jawaban atau nilai.
- Kurikulum Merdeka dan Kurikulum Berbasis Cinta (KBC/Panca Cinta) adalah satu kesatuan dalam konteks pembelajaran dan asesmen, bersama Pembelajaran Mendalam dan delapan dimensi profil lulusan. Jangan membangun kurikulum/modul terpisah atau membuat data resmi sintetis.
- CP resmi induk read-only dan harus memiliki provenance/versi terverifikasi. Guru mengembangkan TP/ATP sesuai assignment.
- Nomenklatur asesmen harus selaras Kurikulum Merdeka + KBC; jangan hardcode UTS/UAS/UM/TKA sebagai enum tertutup. Pisahkan fungsi, cakupan, nama kegiatan, instrumen dan mode CBT.
- DOCX adalah format utama import soal. Jangan aktifkan kembali XLSX question import. SHORT_ANSWER tetap fail-closed sampai accepted answer privat, snapshot dan scoring teruji.
- Waktu server adalah acuan resmi. Attempt/Answer/Score/lease/fencing/idempotency/recovery harus konsisten. Kamera bukan syarat CBT standar.
- Laravel 13, PHP 8.3, MySQL 8+, Blade/Livewire. Build Android hanya via Android Studio, tetapi Android sedang ditunda.

## 3. Batas perubahan dan keselamatan
- Jangan menjalankan migration pada database produksi, deploy VPS, merge PR, atau mengubah DB produksi tanpa persetujuan eksplisit owner dan preflight yang terdokumentasi.
- Jangan melemahkan policy/validasi untuk membuat test lama lulus. Audit apakah test usang atau source salah.
- Jangan memetakan akun Operator/Kamad otomatis menjadi Admin tanpa keputusan/mekanisme mapping owner yang aman. Pertahankan data historis dan lakukan fail-closed.
- Jangan menganggap assessment_origin=EXTERNAL sebagai penanda ujian pusat. Jika eligibility penugasan proktor belum memiliki marker/otoritas kanonis, tetap fail-closed dan ajukan pertanyaan spesifik ke owner.
- Jangan menyebut Device Lease/fencing siap produksi sebelum integrasi, multi-device, concurrency dan recovery tests lulus.
- Jangan menyebut ZIP final/siap VPS sebelum semua acceptance gates yang relevan lulus.

## 4. Urutan eksekusi
1. Verifikasi backlog dan status CI exact SHA pada branch eksekusi aktif.
2. Selesaikan P0 backend/full PHPUnit berdasarkan akar masalah aktual; jangan mengubah test secara artifisial.
3. Integrasikan dan uji Web Siswa dengan backend Laravel nyata.
4. Selesaikan alur Web Guru end-to-end.
5. Selesaikan ADM-01 sampai ADM-23 pada Web Admin.
6. Tutup kontrak kurikulum Merdeka + KBC end-to-end dan redaction payload Siswa.
7. Uji fresh install, upgrade, backup/restore, keamanan, stabilitas dan browser E2E.
8. Siapkan satu ZIP generik, lalu verifikasi struktur, konfigurasi, checksum dan ekstraksi bersih.
9. Deployment staging/VPS hanya setelah owner memberi akses dan persetujuan; produksi tetap memerlukan persetujuan terpisah.

## 5. Wajib membuat jejak GitHub
Setiap tahap harus menghasilkan:
- laporan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN;
- daftar file berubah dan path backup + hash/blob;
- commit SHA dan exact CI run/result;
- blocker/risiko dan langkah berikutnya;
- checkpoint yang menunjuk pekerjaan belum selesai;
- pembaruan prompt agent pengganti agar agent berikutnya membaca dokumen ini dan tidak mengulang pekerjaan.

## 6. Entry point saat melanjutkan
Baca:
- [Prompt eksekusi dan handover agent berikutnya](PROMPT_AGENT_PENGGANTI_CBT_2026-11-24.md)
- [Checkpoint owner-directed 24 November 2026](CHECKPOINT_OWNER_DIRECTED_EXECUTION_2026-11-24.md)
- [Laporan audit GitHub terbaru](REPORT_OWNER_DIRECTED_GITHUB_AUDIT_2026-11-24.md)
- [Handover pemulihan PHPUnit](HANDOVER_AGENT_BARU_PHPUNIT_2026-11-23.md)
- [Triage PHPUnit](REPORT_P0_TRIAGE_FULL_PHPUNIT_2026-11-23.md)
- [Keputusan owner](https://github.com/zane0184/cbt_november/issues/20)

**Instruksi ringkas untuk agent berikutnya:** baca dokumen ini dan seluruh referensi di atas, verifikasi source/HEAD/CI terbaru, lalu lanjutkan hanya pekerjaan yang belum selesai. Jangan mulai ulang, jangan memakai sumber di luar GitHub, dan jangan mengubah keputusan owner.

## HANDOVER AGENT BARU — WAJIB LANJUTKAN, JANGAN MENGULANG
Dokumen handover terbaru dan lengkap: [HANDOVER_AGENT_BARU_LANJUTKAN_BUKAN_MENGULANG_2026-11-24.md](HANDOVER_AGENT_BARU_LANJUTKAN_BUKAN_MENGULANG_2026-11-24.md).
Prompt agent pengganti: [PROMPT_AGENT_PENGGANTI_CBT_2026-11-24.md](PROMPT_AGENT_PENGGANTI_CBT_2026-11-24.md).

Tugas agent baru adalah meneruskan backlog yang belum selesai. Jangan memulai ulang proyek, mengulang triage/remediasi Full PHPUnit, menjalankan full suite hanya untuk konfirmasi, atau mengulang patch yang sudah ada. Full PHPUnit CLOSED/GREEN: exact source SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`, CI #45243430635, 518 passed / 2,177 assertions / 0 failed. Buka kembali hanya dengan pemicu sah pada lock note.

Branch kerja terakhir saat handover: `work/solo-web-cbt-completion-20261123-execution`; HEAD saat handover: `a8ad92d9a4319f87827291b6cc02419aab964db9`. Selalu verifikasi ulang HEAD dan CI terkini. Lanjutkan backlog Issue #20 ADM-01..ADM-23 berdasarkan source aktual; kemudian Web Siswa/Guru E2E dan acceptance sesuai urutan handover. Baca laporan handover lengkap sebelum menyentuh source.


## PROMPT TERBARU — W2 FINAL / APLIKASI SUDAH TERPASANG DI VPS

**Acuan operasional agent pengganti yang paling baru:** [PROMPT_AGENT_PENGGANTI_CBT_W2_FINAL_2026-11-24.md](PROMPT_AGENT_PENGGANTI_CBT_W2_FINAL_2026-11-24.md).

Owner menegaskan aplikasi CBT sudah berjalan di https://cbt.min15jakarta.sch.id/login, sebelumnya sudah pernah dikemas ZIP dan dipasang di VPS. VPS/konfigurasi/instalasi telah diverifikasi ketat dan berjalan lancar. Jangan meminta ulang spesifikasi VPS atau mengulang audit infrastruktur. Fokus pada gap fitur aplikasi yang belum lengkap, teruskan source GitHub, uji semua fitur web end-to-end, lalu buat satu ZIP lengkap siap upload/extract setelah acceptance gate lulus.

HEAD saat prompt W2 dibuat: `e894d3f4727e3bfcfdb45a29ce1da5c3abd6b598`; prompt W2 dibuat pada commit `708cc5f2ff44006fce1427986945616d110fdce1`. Verifikasi ulang HEAD setiap sesi.

CI #1714: complete PHPUnit **536 passed / 2,231 assertions / 0 failed**; focused login/tenant 30/99, Admin 5/5, Guru 4/8. Workflow overall FAILURE hanya pada post-check InnoDB karena query pemeriksaan salah (`Unknown column 'tenants' in 'where clause'`). Jangan salah menyebut PHPUnit gagal dan jangan mengubah aplikasi untuk memperbaiki query workflow. Perbaiki workflow step dengan backup byte-exact dan pertahankan fail-closed.

**PERINTAH KUNCI:** LANJUTKAN, JANGAN MENGULANG. Jangan mulai ulang, jangan ulangi patch/triage/test yang sudah selesai tanpa pemicu, dan jangan berhenti setelah audit. Lanjutkan backlog yang belum selesai sampai seluruh fitur web dan acceptance gate terpenuhi, baru buat ZIP final.

 
## ADDENDUM TERBARU — W2 CI POST-CHECK (24 NOVEMBER 2026)

Laporan terbaru: [REPORT_W2_CI_INNODB_POSTCHECK_FIX_2026-11-24.md](REPORT_W2_CI_INNODB_POSTCHECK_FIX_2026-11-24.md).
Checkpoint terbaru: [CHECKPOINT_W2_CI_INNODB_POSTCHECK_FIX_2026-11-24.md](CHECKPOINT_W2_CI_INNODB_POSTCHECK_FIX_2026-11-24.md).

Perbaikan query post-check InnoDB pada `.github/workflows/tenant-backend-ci.yml` telah dibuat pada commit `1b25c3bbed07fba6def2dfe869d686a8559be1cc`. Query menggunakan prepared statement dan tetap fail-closed untuk engine selain InnoDB. Backup byte-exact berada di `backend-laravel/docs/backup/admin-madrasah-profile-2026-11-24/tenant-backend-ci.yml.before-postcheck-quote-fix`, blob SHA `c2d824eccf029f69d13a7b5dba3f5baa4adfe986`.

**PENGUJIAN:** source dan backup sudah difetch ulang; blob SHA backup sama dengan source sebelum patch. GitHub Actions pada commit patch belum terverifikasi saat addendum ini dibuat. Jangan menyatakan CI lulus sampai hasil run tersedia.

**LANJUTKAN:** verifikasi Actions pada patch CI, lalu lanjutkan gap W2 profil Admin (metadata, logo/private asset, branding login, kop surat/preview) dan backlog portal sesuai Issue #20. Jangan ulang profil dasar atau W1 reauthorization yang sudah lulus. ZIP final belum dibuat dan belum siap distribusi.


## PROMPT AGENT TERBARU — W3 FINAL (CI GREEN)

Acuan handover terbaru: [PROMPT_AGENT_PENGGANTI_CBT_W3_FINAL_2026-11-24.md](PROMPT_AGENT_PENGGANTI_CBT_W3_FINAL_2026-11-24.md).

**Status CI diperbarui:** post-check InnoDB sudah diperbaiki pada commit `1b25c3bbed07fba6def2dfe869d686a8559be1cc`; GitHub Actions #1722 SUCCESS pada exact SHA `df079f0ab51235b611b97b96c2cb10cc3d33d8eb`: 536 passed / 2,231 assertions / 0 failed; focused login/tenant 30/99, Admin 5/5, Guru 4/8; InnoDB post-check PASS. Link: https://github.com/zane0184/cbt_november/actions/runs/45276501877.

Jangan ulang patch CI, jangan mengulang profil dasar Admin, triage 42 failure PHPUnit, atau W1 reauthorization yang telah diverifikasi. **LANJUTKAN GAP FITUR YANG BELUM SELESAI.** Fokus berikutnya: profil Admin lanjutan dan backlog ADM-01..23, Web Guru/Siswa E2E, integritas dan security, kemudian satu ZIP final setelah acceptance lengkap. Jangan meminta spesifikasi VPS; owner menyatakan VPS/instalasi sudah terverifikasi.
