# LAPORAN PEMBARUAN PROMPT & HANDOVER AGENT BARU — CBT MADRASAH
Tanggal: 24 November 2026 (WIB)
Repository: https://github.com/zane0184/cbt_november
Branch kerja: `work/solo-web-cbt-completion-20261123-execution`
Owner: pengguna (pemegang keputusan tunggal)
Eksekutor yang ditetapkan: ChatGPT sebagai koordinator sekaligus pelaksana Backend, Web Admin, Web Guru, Web Siswa.

## 1. TUJUAN HANDOVER — LANJUTKAN, JANGAN MULAI ULANG
Dokumen ini adalah catatan operasional untuk agent pengganti. Tugas utama agent baru adalah **meneruskan pekerjaan yang belum selesai dari source dan checkpoint terakhir**, bukan mengulang audit, implementasi, test, atau keputusan yang sudah ditutup.

Dilarang:
- memulai proyek dari nol atau menganggap repository kosong;
- mengulang triage PHPUnit yang sudah selesai;
- mengulang patch yang sudah ada di source;
- menjalankan full test suite hanya untuk membuktikan ulang baseline yang sudah hijau;
- membuat ulang laporan/fitur dengan nama berbeda tanpa kebutuhan;
- mengubah keputusan owner, memperluas scope, atau mengerjakan Android/Super Admin sebelum waktunya;
- meminta owner mengirim ulang file/keputusan yang sudah ada di GitHub.

Prinsip prioritas: **source aktual + commit SHA + bukti test exact SHA > laporan naratif > ingatan/rangkuman percakapan**. Jika ada perbedaan, audit source dan bukti terbaru, catat selisihnya, jangan mengulang semua pekerjaan.

## 2. PEMERIKSAAN KONDISI GITHUB SAAT HANDOVER
- Branch eksekusi: `work/solo-web-cbt-completion-20261123-execution`
- HEAD branch saat laporan dibuat: `a8ad92d9a4319f87827291b6cc02419aab964db9`
- Baseline source PHPUnit hijau: `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`
- Snapshot branch referensi: `baseline/phpunit-green-20261124` (jangan dipakai untuk pekerjaan baru dan jangan digeser).
- CI pembuktian source hijau: [Actions #45243430635](https://github.com/zane0184/cbt_november/actions/runs/45243430635) — 518 passed, 2,177 assertions, 0 failed, PHP 8.3 + disposable MySQL 8.0/InnoDB.
- Workflow dokumentasi #45248980063 pada SHA `9b65624eb0e694237e41b860592f8f4dad0d99ac` berstatus cancelled karena commit berikutnya menggantikan run; ini bukan bukti kegagalan source. Jangan mengartikan cancellation dokumentasi sebagai regresi PHPUnit.
- HEAD branch eksekusi sudah lebih maju daripada baseline source hijau karena memuat dokumentasi lock/handover. Verifikasi ulang branch/HEAD/Actions sebelum perubahan berikutnya.

## 3. AREA YANG SUDAH SELESAI DAN DITUTUP — JANGAN DIULANG
### Full PHPUnit remediation
Area remediasi 42 failure baseline telah selesai. Hasil yang disahkan:
- 518 tests passed
- 2,177 assertions
- 0 failed
- CI pada exact source SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`.

Perubahan yang sudah dikerjakan dan diuji mencakup:
1. Fixture mapping kurikulum/assessment melengkapi `academic_year_id` dan `semester_id` sesuai schema MySQL strict.
2. Fixture historical recalculation menggunakan registration/version assignment yang konsisten, tidak mengganti scope composite-FK yang sedang direferensikan.
3. Fixture Question dan QuestionVersion memenuhi status PUBLISHED sesuai kontrak immutable snapshot.
4. ExamManagement memperbaiki validasi rombel: UUID harus valid, harus cocok dengan assignment Guru aktif, dan nilai `ALL` ditolak.
5. Teacher practice dan test terkait memakai aktor/assignment yang sesuai dengan batas kewenangan Guru.
6. Heartbeat rate limit memakai identitas pengguna/credential (tenant+user) dan fallback IP untuk request anonim, sehingga pengguna sah di balik NAT tidak saling menghabiskan kuota.
7. Perbaikan presisi timestamp MySQL dan boundary waktu pada fixture.
8. Penyesuaian fixture Sanctum/device/fencing token dan reset guard yang diperlukan test.
9. Penyesuaian namespace/helper fencing serta fixture tenant sistem yang sudah ada.

Lihat laporan rinci: [REPORT_PHPUNIT_FAILURE_TAXONOMY_AND_GREEN_2026-11-24.md](REPORT_PHPUNIT_FAILURE_TAXONOMY_AND_GREEN_2026-11-24.md).

### Aturan membuka kembali area hijau
Jangan menyentuh atau menjalankan ulang full suite hanya karena agent baru ingin “memastikan”. Buka kembali hanya jika:
- owner meminta secara eksplisit;
- source baru mengubah komponen yang relevan;
- CI pada source baru menunjukkan regresi yang relevan; atau
- ditemukan bukti konkret kerentanan/integritas data.
Jika dibuka: tulis pemicu, identifikasi file/commit terdampak, jalankan focused test terlebih dahulu, lalu full suite hanya bila dampaknya luas. Jangan menghapus snapshot/bukti baseline lama.

**Catatan:** lock saat ini adalah lock dokumenter dan snapshot branch, bukan GitHub branch protection/read-only enforcement.

## 4. KEPUTUSAN OWNER YANG FINAL — TIDAK BOLEH DITAFSIRKAN ULANG
### Scope dan kepemilikan
- Satu owner adalah pengguna; keputusan produk/keamanan/data berada pada owner.
- ChatGPT menjadi satu-satunya koordinator dan eksekutor Backend, Web Admin, Web Guru, Web Siswa. Tidak ada pembagian ke agent implementasi lain.
- Fokus sampai Juni 2027: Backend Laravel + Web Admin Madrasah + Web Guru + Web Siswa.
- Android native dan Web Super Admin ditunda sampai Juni 2027.

### Bentuk instalasi dan tenant
- Tidak ada multi-tenant akademik di satu database.
- Satu paket ZIP generik digunakan semua madrasah, tetapi setiap madrasah punya instalasi dan database sendiri.
- Data akademik, pengguna, ujian, dan transaksi CBT antarinstalasi tidak boleh saling terhubung/terbagi.
- Integrasi pusat masa depan hanya untuk lisensi, entitlement, dan pembayaran; Web Super Admin pusat ditunda sampai Juni 2027.

### Portal dan kewenangan
- Role lokal resmi hanya `ADMIN_MADRASAH`, `GURU`, `SISWA`.
- KAMAD dan OPERATOR bukan role/portal aktif; tugas Operator menjadi tanggung jawab Admin. Kepala Madrasah hanya metadata/jabatan/penandatangan bila diperlukan.
- Tidak ada role/portal Proktor permanen. Pengawas/proktor adalah penugasan/menu operasional sementara pada portal Guru.
- Penugasan sementara tidak otomatis memberi hak melihat/mengelola room/session, roster, Attempt, jawaban, nilai, monitoring peserta, force submit, reset Attempt, atau operasi CBT lain.
- Guru berwenang mengelola bank soal, asesmen, ujian, jadwal, token, peserta sesuai assignment, dan menerbitkan ujian tanpa persetujuan Admin.
- Admin mengelola identitas/administrasi madrasah, akun, master akademik, sinkronisasi, dukungan teknis, audit, pengaturan, backup/restore dan laporan administratif.
- Admin tidak boleh mengelola authoring soal/ujian, token, peserta ujian, Attempt, monitoring peserta, jawaban, penilaian/nilai atau operasi ujian Guru.

### Kurikulum dan asesmen
- Kurikulum Merdeka dan Kurikulum Berbasis Cinta (KBC/Panca Cinta) menjadi satu konteks terpadu dengan Pembelajaran Mendalam dan delapan dimensi profil lulusan.
- Jangan membuat modul kurikulum yang terpisah/terputus atau mengarang data resmi.
- CP resmi induk bersifat read-only dan wajib punya provenance serta versi terverifikasi.
- TP/ATP dikembangkan Guru sesuai assignment.
- Jangan membuat enum tertutup UTS/UAS/UM/TKA. Pisahkan fungsi asesmen, cakupan, nama kegiatan, instrumen dan mode CBT.
- DOCX adalah format utama import soal. XLSX question import tidak boleh diaktifkan kembali.
- SHORT_ANSWER tetap fail-closed sampai accepted answer privat, snapshot immutable dan scoring diuji menyeluruh. ESSAY dinilai manual.

### Integritas CBT dan keamanan
- Waktu server adalah acuan resmi.
- Attempt, Answer, Score, Device Lease/fencing, idempotency, sinkronisasi dan recovery harus konsisten.
- Kamera bukan syarat CBT standar.
- Jangan menyatakan Device Lease/fencing siap produksi sebelum integration, multi-device, concurrency dan recovery tests lulus.
- Isolasi data per instalasi, RBAC, audit log, transaksi, idempotensi, histori akademik dan keamanan harus dipertahankan.

## 5. LARANGAN OPERASIONAL
Tanpa persetujuan eksplisit owner dan preflight terdokumentasi, jangan:
- merge PR;
- deploy ke VPS/produksi;
- menjalankan migration pada database produksi;
- mengubah/menghapus data produksi;
- melakukan tindakan destructive pada branch atau baseline;
- menyatakan ZIP siap distribusi/siap produksi.

Jangan menurunkan authorization/policy hanya untuk meluluskan test. Bedakan bug aplikasi, fixture usang, dan kontrak produk yang berubah. Tidak ada Gradle via terminal; Android saat ini ditunda.

## 6. BACKLOG DAN PRIORITAS KERJA — LANJUTKAN DARI SINI
Sumber pekerjaan utama Web Admin: [Issue #20 — ADM-01 s.d. ADM-23](https://github.com/zane0184/cbt_november/issues/20). Issue tersebut masih open dan menjadi backlog terstruktur, bukan alasan untuk mengulang audit awal seluruh repository.

Urutan eksekusi setelah membaca status terbaru:
1. **Verifikasi state terkini**: branch, HEAD, PR, Issues dan komentar owner, Actions pada exact SHA, checkpoint, laporan, source terkait pekerjaan berikutnya. Jangan melakukan full PHPUnit hanya untuk konfirmasi area hijau.
2. **Web Admin — lanjutkan Issue #20**: audit per item ADM-01..ADM-23 berdasarkan source aktual; identifikasi item yang sudah benar-benar selesai dan item yang masih terbuka. Jangan centang tanpa acceptance evidence. Fokus keamanan RBAC/route/Livewire/API lebih dahulu, lalu shell/dashboard, profil/branding/settings, master Guru/Siswa, akademik/kurikulum, import/export, assignment pengawas sementara, audit log/health, backup/restore, E2E acceptance.
3. **Web Siswa**: integrasi nyata ke backend Laravel dan E2E alur siswa; pastikan redaction data, eligibility, token/jadwal server, attempt/answer, autosave/sync dan error recovery.
4. **Web Guru**: alur end-to-end bank soal → asesmen → ujian → jadwal/token → penerbitan → pelaksanaan → hasil, seluruhnya dibatasi assignment dan policy.
5. **Kurikulum Merdeka + KBC**: verifikasi keterpaduan dari master akademik, assignment, bank soal, asesmen, ujian sampai laporan; provenance CP resmi, versioning dan histori.
6. **Hardening lintas sistem**: fresh install dan upgrade pada disposable MySQL 8/InnoDB, backup/restore, security/IDOR, concurrency/idempotency, audit log, browser E2E, accessibility/responsive, import/export.
7. **Paket distribusi generik**: baru dikerjakan setelah acceptance gate selesai; verifikasi source lengkap, struktur direktori, konfigurasi, README, checksum, ekstraksi bersih, tanpa backup internal/temp.
8. **Staging/VPS dan UAT madrasah**: hanya setelah akses dan approval owner. Produksi memerlukan persetujuan terpisah.

Sebelum memilih prioritas rinci, baca Issue #20 dan komentar owner terbaru serta periksa PR terkait. PR #18 sebelumnya mengandung migration/fitur room-session-seat-monitoring yang bertentangan dengan keputusan owner; jangan merge atau menjalankan migration-nya. Periksa status PR saat ini sebelum tindakan apa pun.

## 7. PROTOKOL KERJA AGENT BARU
Untuk setiap sesi:
1. Baca `docs/AGENT_START_HERE.md`, prompt ini, checkpoint terbaru, laporan audit terbaru, dan keputusan owner pada Issue #20 serta issue relevan.
2. Verifikasi exact branch/HEAD dan source. Cari file/fitur/test/backup yang sudah ada sebelum menyimpulkan belum dikerjakan.
3. Tandai tiap item backlog: DONE (dengan bukti), IN PROGRESS, BLOCKED, NOT STARTED. Jangan menyimpulkan DONE dari klaim laporan saja.
4. Buat rencana perubahan minimal yang langsung mengarah pada backlog tersisa.
5. Sebelum mengubah file existing: buat backup byte-exact di repository, catat path, blob SHA asal, commit backup dan verifikasi konten identik.
6. Implementasikan perubahan kecil dan terarah. Jangan menyentuh area terkunci kecuali pemicu sah.
7. Jalankan focused tests yang berkaitan. Jalankan suite luas hanya bila perubahan berdampak luas atau acceptance gate memang memerlukannya.
8. Catat hasil test exact SHA, nama workflow/run URL, PHP/MySQL, pass/fail/skip/assertions, serta batasan environment.
9. Buat laporan tahap dan checkpoint di GitHub. Update prompt agent ini dan `AGENT_START_HERE.md` bila status/urutan/keputusan berubah.
10. Jangan mengirim laporan rutin di chat. Hubungi owner hanya untuk keputusan material yang belum ditentukan, akses/approval yang dibutuhkan, atau blocker yang tidak bisa diselesaikan tanpa owner.

## 8. FORMAT LAPORAN WAJIB
Pisahkan dengan tegas:
- **AUDIT** — file, source, route, policy, migration, test, risiko dan bukti yang ditemukan.
- **DESAIN** — kontrak/solusi yang dipakai dan kesesuaiannya dengan keputusan owner.
- **IMPLEMENTASI** — file yang berubah, backup path/SHA, commit SHA.
- **PENGUJIAN** — perintah/workflow yang benar-benar dijalankan, exact SHA, hasil dan keterbatasan.
Tambahkan backlog selesai/tersisa, blocker, risiko, dan langkah berikutnya.

## 9. SUMBER WAJIB DIBACA
- [Titik masuk agent](AGENT_START_HERE.md)
- [Checkpoint owner-directed 24 November](CHECKPOINT_OWNER_DIRECTED_EXECUTION_2026-11-24.md)
- [Lock baseline PHPUnit](LOCKED_BASELINE_FULL_PHPUNIT_GREEN_2026-11-24.md)
- [Laporan taksonomi PHPUnit hijau](REPORT_PHPUNIT_FAILURE_TAXONOMY_AND_GREEN_2026-11-24.md)
- [Issue #20 Web Admin ADM-01..23](https://github.com/zane0184/cbt_november/issues/20)
- [Issue #12 spesifikasi Web Admin](https://github.com/zane0184/cbt_november/issues/12)
- [Branch snapshot baseline PHPUnit](https://github.com/zane0184/cbt_november/tree/baseline/phpunit-green-20261124)

## 10. PERINTAH PEMBUKA UNTUK AGENT BARU
Mulai dengan membaca seluruh dokumen wajib dan memverifikasi GitHub. Kemudian laporkan secara internal/di checkpoint:
- HEAD dan branch yang benar-benar aktif;
- daftar pekerjaan yang sudah selesai dan bukti exact;
- daftar pekerjaan tersisa yang paling dekat untuk dikerjakan;
- blocker/keputusan owner yang benar-benar diperlukan.

Setelah itu **langsung kerjakan item backlog berikutnya yang tidak terblokir**. Jangan berhenti setelah membuat audit ulang. Jangan meminta owner mengulangi keputusan. Jangan mengulang pekerjaan hijau. Misi kamu adalah meneruskan proyek sampai acceptance gates terpenuhi.


---

# ADDENDUM TERBARU — W1 FINAL / STATUS 24 NOVEMBER 2026

**Dokumen ini sebelumnya merekam status pada SHA dan CI yang lebih lama. Addendum ini menggantikan angka dan status lama untuk pekerjaan selanjutnya.** Prompt operasional lengkap terbaru: [PROMPT_AGENT_PENGGANTI_CBT_W1_FINAL_2026-11-24.md](PROMPT_AGENT_PENGGANTI_CBT_W1_FINAL_2026-11-24.md).

- Branch: `work/solo-web-cbt-completion-20261123-execution`.
- HEAD terbaru: `66801d3afdef925e79b0478870b1f51d6c363d1a`.
- Exact source SHA W1 yang diuji: `b5e97564a4875c6b9f809adbb61b65469897d79b`.
- Actions #1662 SUCCESS: 27/90 login-tenant smoke; Admin 5/5; Guru 4/4; Full PHPUnit 527 passed, 2,190 assertions, 0 failed; PHP 8.3 + MySQL 8.0.46/InnoDB disposable. https://github.com/zane0184/cbt_november/actions/runs/45257698090
- Actions #1663 SUCCESS pada HEAD dokumentasi terbaru; focused login/tenant, Admin, Guru, full suite dan cleanup sukses. https://github.com/zane0184/cbt_november/actions/runs/45258556051
- Commit source/test terakhir yang menjadi dasar W1: https://github.com/zane0184/cbt_november/commit/b5e97564a4875c6b9f809adbb61b65469897d79b
- Commit laporan/checkpoint: https://github.com/zane0184/cbt_november/commit/66801d3afdef925e79b0478870b1f51d6c363d1a

W1 reauthorization Admin dan Guru sudah VERIFIED pada cakupan patch. Komponen Admin: Dashboard, UserManagement, AcademicManagement, CurriculumManagement, ProctorAssignmentManagement selector callback. Komponen Guru: ResultList, ResultDetail, AnalyticsDashboard, ItemAnalysis. Fix Blade ResultDetail dan penghapusan duplicate policy check pada ResultList/AnalyticsDashboard juga masuk source teruji. Test Analytics memakai mock ExamAnalyticsService untuk isolasi authorization; bukan acceptance analytics statistik.

Backup byte-exact tersedia pada:
- `backend-laravel/docs/backup/livewire-admin-render-reauth-2026-11-24/`
- `backend-laravel/docs/backup/teacher-result-render-reauth-2026-11-24/`

**Jangan ulang patch, triage 42 baseline failures, atau full PHPUnit hanya karena pergantian agent.** Lakukan focused tests untuk perubahan baru; full suite hanya bila dampaknya luas atau acceptance memerlukan.

Keputusan owner tetap: instalasi/database mandiri per madrasah; tidak ada shared multi-tenant akademik; role lokal hanya ADMIN_MADRASAH/GURU/SISWA; Kamad/Operator bukan role/portal; Proktor hanya assignment/menu sementara pada Guru; Admin tidak mengelola ujian; Kurmer+KBC satu kesatuan; DOCX utama, XLSX question import disabled; SHORT_ANSWER fail-closed; Android dan Web Super Admin ditunda sampai Juni 2027.

Issue #20 tetap OPEN. W1 reauthorization subset selesai, tetapi acceptance W1 lainnya dapat masih terbuka. Lanjutkan gap W1 yang benar-benar belum terpenuhi, lalu W2–W9. ADM-01..ADM-23 belum verified end-to-end. ADM-20 tetap BLOCKED sampai canonical central exam eligibility marker disetujui owner. PR #18, #19, #13 terakhir diperiksa OPEN/DRAFT; jangan merge tanpa approval.

**Status proyek: IN PROGRESS — NOT READY FOR DISTRIBUTION / NOT READY FOR PRODUCTION.**
