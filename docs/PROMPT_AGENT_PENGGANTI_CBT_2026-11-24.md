> **STATUS TERBARU 24 NOVEMBER 2026 — WAJIB BACA SEBELUM MEMAKAI BAGIAN LAMA DI BAWAH.**
>
> Prompt ini memiliki bagian historis yang status/angka CI-nya sudah kedaluwarsa. Acuan operasional terbaru adalah [PROMPT AGENT PENGGANTI W1 FINAL](PROMPT_AGENT_PENGGANTI_CBT_W1_FINAL_2026-11-24.md). Jangan menggunakan HEAD/angka lama dari teks di bawah sebagai status saat ini.
>
> Branch kerja: `work/solo-web-cbt-completion-20261123-execution`; HEAD dokumentasi: `66801d3afdef925e79b0478870b1f51d6c363d1a`; source W1 diuji pada `b5e97564a4875c6b9f809adbb61b65469897d79b`.
>
> CI #1662 dan #1663 SUCCESS; full PHPUnit 527 passed, 2,190 assertions, 0 failed. Focused login/tenant 27/90, Admin 5/5, Guru 4/4. Jangan mengulang triage atau full suite tanpa pemicu yang sah.
>
> Mandat tidak berubah: **LANJUTKAN, JANGAN MENGULANG.** W1 reauthorization patch sudah verified pada scope-nya; ADM-01..23 belum verified end-to-end; ADM-20 tetap blocked sampai canonical central exam eligibility marker diputuskan owner. Issue #20 masih open. Tidak ada merge/deploy/production migration tanpa approval. Android dan Web Super Admin ditunda sampai Juni 2027.
>
> Baca dokumen baru di atas sebagai sumber instruksi utama. Bagian historis setelah banner hanya konteks, bukan status terkini.

---

# PROMPT AGENT PENGGANTI — CBT MADRASAH (OWNER-DIRECTED)

## AREA TERKUNCI — FULL PHPUNIT SUDAH HIJAU (BACA SEBELUM EKSEKUSI)
**Status: CLOSED / GREEN BASELINE. Jangan mengulang atau mengubah area ini tanpa pemicu yang sah.**
- Baseline exact SHA: `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`.
- Bukti CI: [#45243430635](https://github.com/zane0184/cbt_november/actions/runs/45243430635) — 518 passed, 2,177 assertions, 0 failed.
- Instruksi wajib: [LOCKED_BASELINE_FULL_PHPUNIT_GREEN_2026-11-24.md](LOCKED_BASELINE_FULL_PHPUNIT_GREEN_2026-11-24.md).
- Laporan rinci: [REPORT_PHPUNIT_FAILURE_TAXONOMY_AND_GREEN_2026-11-24.md](REPORT_PHPUNIT_FAILURE_TAXONOMY_AND_GREEN_2026-11-24.md).
- Jangan jalankan ulang full suite untuk mengecek ulang; jangan ulang triage 42 failure; jangan patch atau refactor helper, fixture, query pivot, assignment boundary, rate limiter, snapshot, atau test terkait yang telah hijau.
- Buka kembali hanya atas permintaan eksplisit owner, perubahan source baru yang menyentuh lingkup, atau bukti CI baru tentang regresi relevan. Catat pemicu; focused test dulu; full suite hanya bila perlu.
- Jangan menyatakan CBT siap produksi hanya karena suite ini hijau.
- Pembekuan ini bersifat dokumenter. Tidak ada klaim bahwa branch telah dikunci secara teknis oleh GitHub branch protection.

Gunakan prompt ini hanya ketika pekerjaan benar-benar dialihkan ke agent baru. GitHub adalah sumber kebenaran tunggal.

## Instruksi wajib
Kamu adalah satu-satunya koordinator sekaligus eksekutor Backend Laravel, Web Admin, Web Guru, dan Web Siswa untuk proyek CBT Madrasah. Owner adalah satu-satunya pengambil keputusan produk. Tidak ada agent lain yang sedang mengerjakan proyek.

Mulai dengan membaca docs/AGENT_START_HERE.md, dokumen ini, checkpoint terbaru, laporan audit terbaru, seluruh komentar owner pada Issue #20, dan PR/CI yang relevan. Periksa source aktual dan exact HEAD sebelum menyimpulkan status. Jangan memakai ZIP baseline, Library/Pustaka, Project Files, atau rangkuman percakapan.

## Keputusan owner — tidak boleh ditafsirkan ulang
- Web CBT only sampai Juni 2027: Backend Laravel, Admin Madrasah, Guru, Siswa.
- Android native dan Web Super Admin ditunda sampai Juni 2027.
- Tidak ada multi-tenant akademik. Satu ZIP generik untuk semua madrasah; setiap madrasah memiliki instalasi dan database sendiri. Data antar-madrasah terisolasi total.
- Integrasi pusat nantinya hanya lisensi/entitlement/pembayaran.
- Role lokal hanya ADMIN_MADRASAH, GURU, SISWA. KAMAD dan OPERATOR dibekukan/dihapus; pekerjaan Operator ditangani Admin Madrasah. Proktor hanya menu/penugasan sementara pada portal Guru, bukan role/portal dan bukan grant monitoring.
- Guru memiliki kewenangan penuh membuat/mengelola bank soal, asesmen, ujian, jadwal, token dan menerbitkan tanpa approval Admin sesuai assignment.
- Admin mengelola administrasi dan master akademik; tidak mengelola soal, ujian, token, peserta ujian, Attempt, monitoring, jawaban, penilaian atau nilai.
- Kurikulum Merdeka + KBC/Panca Cinta adalah satu kesatuan, terintegrasi bersama Pembelajaran Mendalam dan 8 dimensi profil lulusan. CP resmi read-only dan terverifikasi; TP/ATP dikembangkan Guru berdasarkan assignment.
- Jangan hardcode UTS/UAS/UM/TKA menjadi enum tertutup. Pisahkan fungsi asesmen, cakupan, nama kegiatan, instrumen dan mode CBT.
- DOCX import soal utama; XLSX question import tidak boleh diaktifkan kembali. SHORT_ANSWER fail-closed sampai accepted answer, snapshot dan scoring E2E lulus.
- Waktu server resmi. Attempt/Answer/Score/lease/fencing/idempotency/recovery harus konsisten. Kamera bukan syarat CBT standar.

## Cara kerja
1. Verifikasi Issues, owner comments, branch/HEAD, PR, commit, workflow Actions, laporan dan checkpoint terbaru.
2. Bandingkan source dengan klaim laporan. Source aktual dan test exact SHA mengalahkan status naratif.
3. Jangan mengulang patch/fitur yang sudah selesai. Cari bukti source, backup, commit dan test terlebih dahulu.
4. Pisahkan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN.
5. Sebelum mengubah file existing, buat backup byte-exact di GitHub, catat blob SHA asal dan verifikasi konten identik.
6. Jangan melemahkan authorization atau mengubah ekspektasi test agar hijau tanpa bukti kontrak. Bedakan bug source, fixture usang, dan kontrak produk lama.
7. Jalankan test focused, lalu suite penuh di disposable MySQL 8. Catat exact SHA, run URL, jumlah pass/fail/skip dan assertions.
8. Buat laporan dan checkpoint GitHub setiap tahap. Perbarui prompt ini bila branch, status, keputusan atau pekerjaan berikutnya berubah.
9. Jangan lapor rutin di chat kecuali ada keputusan penting yang harus disetujui owner atau hambatan yang memerlukan akses dari owner.

## Gerbang keselamatan
- Dilarang merge PR, deploy VPS, menjalankan migration produksi, atau mengubah database produksi tanpa persetujuan eksplisit owner dan preflight/backup yang disetujui.
- Jangan menganggap assignment Proktor atau assessment_origin=EXTERNAL sebagai izin ujian pusat. Jika marker eligibility/aktor penetap belum ditentukan owner, tetap fail-closed dan tanyakan owner secara spesifik.
- Jangan menyebut Device Lease/fencing siap produksi tanpa multi-device, concurrency, integration dan recovery test.
- Jangan membuat CP resmi, sumber KBC/PM atau data kurikulum sintetis dan menyatakannya resmi/terverifikasi.
- Jangan menyebut ZIP siap distribusi sebelum fresh install, upgrade, checksum, ekstraksi bersih, konfigurasi, security dan acceptance gates lulus.
- Tidak ada Android build via terminal; Android sedang ditunda.

## Titik awal pekerjaan
Mulai dari branch eksekusi aktif yang tercantum di checkpoint GitHub terbaru. Handover lama adalah riwayat, bukan status otomatis. Periksa ulang CI yang saat ini menjadi blocker. Pada audit 24 November, branch execution HEAD yang diperiksa adalah acc24ed24ea9c37baf1355067235e97af06ca325 dan CI #45228426438 menunjukkan 42 failed, 476 passed, 1,863 assertions; jangan menganggap angka ini masih terbaru tanpa memeriksa Actions.

Urutan kerja: P0 backend/full PHPUnit → Web Siswa real Laravel E2E → Web Guru E2E → Web Admin ADM-01..23 → kurikulum Merdeka+KBC end-to-end → install/upgrade/backup/restore/security/UAT → ZIP generik final → staging/VPS setelah approval.

Jika keputusan owner yang diperlukan belum ada, jangan menebak. Ajukan satu pertanyaan paling spesifik dan lanjutkan pekerjaan lain yang tidak terblokir.

## HANDOVER TERBARU — PRIORITAS MUTLAK: LANJUTKAN, JANGAN MENGULANG
Baca dokumen lengkap: [HANDOVER_AGENT_BARU_LANJUTKAN_BUKAN_MENGULANG_2026-11-24.md](HANDOVER_AGENT_BARU_LANJUTKAN_BUKAN_MENGULANG_2026-11-24.md).

Instruksi ini mengikat: pekerjaan utama agent pengganti adalah meneruskan backlog yang belum selesai, bukan mengulang audit/patch/test/keputusan yang sudah ditutup. Full PHPUnit sudah CLOSED/GREEN pada exact SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51` dengan 518 passed, 2,177 assertions, 0 failed (CI #45243430635). Jangan ulang triage 42 failure, jangan rerun full suite hanya untuk konfirmasi, dan jangan mengubah area itu tanpa pemicu sah sebagaimana dijelaskan dalam lock note.

Branch kerja saat handover: `work/solo-web-cbt-completion-20261123-execution`; HEAD saat dokumen handover dibuat: `a8ad92d9a4319f87827291b6cc02419aab964db9`. Snapshot baseline terpisah: `baseline/phpunit-green-20261124`; jangan digeser/dipakai untuk coding. Selalu verifikasi HEAD/CI terbaru sebelum bertindak karena branch dapat maju setelah handover.

Backlog utama berikutnya adalah meneruskan Issue #20 (ADM-01..ADM-23) dengan membandingkan checklist terhadap source dan bukti terbaru, lalu Web Siswa E2E, Web Guru E2E, integrasi kurikulum, hardening dan acceptance. Item yang sudah selesai harus ditandai berdasarkan bukti; jangan mengulang item yang sudah selesai. Tidak ada Android native/Web Super Admin sampai Juni 2027. Semua keputusan owner, larangan production migration/deploy/merge tanpa approval, backup byte-exact sebelum edit, dan pelaporan AUDIT/DESAIN/IMPLEMENTASI/PENGUJIAN tetap berlaku.
