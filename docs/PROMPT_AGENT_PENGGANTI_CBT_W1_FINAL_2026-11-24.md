# PROMPT AGENT PENGGANTI CBT MADRASAH — W1 FINAL / LANJUTKAN, JANGAN MENGULANG
Tanggal pembaruan: 24 November 2026 (WIB)
Repository: https://github.com/zane0184/cbt_november
Branch kerja yang diverifikasi: `work/solo-web-cbt-completion-20261123-execution`
Owner: pengguna — satu-satunya pemegang keputusan produk/keamanan/data
Mandat agent: koordinator sekaligus pelaksana Backend Laravel, Web Admin Madrasah, Web Guru, dan Web Siswa.

> **PERINTAH PALING PENTING: LANJUTKAN PEKERJAAN YANG BELUM SELESAI. JANGAN MENGULANG PEKERJAAN YANG SUDAH SELESAI, JANGAN MEMBUAT ULANG PATCH, JANGAN MENGULANG TRIAGE ATAU TEST TANPA PEMICU YANG SAH.**
>
> Dokumen ini adalah prompt/handover operasional terbaru. Dokumen handover/prompt versi lebih lama memuat status dan angka CI yang sudah kedaluwarsa. Jika ada perbedaan, gunakan dokumen ini, source aktual pada branch aktif, exact SHA, dan bukti Actions terbaru. Jangan menghapus riwayat dokumen lama; dokumen ini yang menjadi acuan operasional terkini.

---

## 1. KONDISI GITHUB TERAKHIR — WAJIB DIPEGANG

### 1.1 Branch, SHA, dan CI
- Branch kerja: `work/solo-web-cbt-completion-20261123-execution`.
- HEAD branch saat prompt ini disusun: `66801d3afdef925e79b0478870b1f51d6c363d1a`.
- Source revision yang menjalankan perubahan W1 Admin/Guru dan diuji penuh: `b5e97564a4875c6b9f809adbb61b65469897d79b`.
- Commit `66801d3a…` hanya menambahkan pembaruan laporan/checkpoint; tidak mengubah source aplikasi yang diuji pada `b5e97564…`.
- Source commit: https://github.com/zane0184/cbt_november/commit/b5e97564a4875c6b9f809adbb61b65469897d79b
- Commit dokumentasi/checkpoint: https://github.com/zane0184/cbt_november/commit/66801d3afdef925e79b0478870b1f51d6c363d1a

### 1.2 Bukti CI yang sudah final — jangan diulang tanpa alasan
**GitHub Actions #1662 — SUCCESS**, exact source SHA `b5e97564a4875c6b9f809adbb61b65469897d79b`:
- PHP 8.3.
- MySQL 8.0.46, InnoDB, disposable/test database; bukan database produksi.
- Login/tenant focused smoke: 27 passed / 90 assertions.
- Admin Livewire reauthorization: 5 passed / 5 assertions.
- Teacher result/analytics reauthorization: 4 passed / 8 assertions.
- Full PHPUnit: **527 passed / 2,190 assertions / 0 failed**.
- Job cleanup: SUCCESS.
- Bukti: https://github.com/zane0184/cbt_november/actions/runs/45257698090

**GitHub Actions #1663 — SUCCESS**, pada HEAD dokumentasi `66801d3afdef925e79b0478870b1f51d6c363d1a`:
- Setup, smoke login/tenant, Admin 5/5, Guru 4/4, full PHPUnit dan cleanup berhasil.
- Ini pemeriksaan ulang HEAD setelah commit dokumentasi. Jangan jalankan ulang hanya untuk memperoleh angka yang sama.
- Bukti: https://github.com/zane0184/cbt_november/actions/runs/45258556051

**Kebijakan test ke depan:** focused test dahulu. Full PHPUnit hanya bila perubahan berdampak luas, acceptance gate mensyaratkan, atau focused/integration test menunjukkan indikasi regresi lintas modul. Jangan menjalankan full suite sekadar karena agent berganti.

### 1.3 Dokumen status W1
- Laporan: [REPORT_W1_ADMIN_TEACHER_LIVEWIRE_REAUTH_2026-11-24.md](REPORT_W1_ADMIN_TEACHER_LIVEWIRE_REAUTH_2026-11-24.md)
- Checkpoint: [CHECKPOINT_OWNER_DIRECTED_EXECUTION_2026-11-24.md](CHECKPOINT_OWNER_DIRECTED_EXECUTION_2026-11-24.md)
- Matriks Admin: [MATRIKS_ADM_01_23_AUDIT_2026-11-23.md](MATRIKS_ADM_01_23_AUDIT_2026-11-23.md)
- Backlog utama Admin: [Issue #20](https://github.com/zane0184/cbt_november/issues/20), masih OPEN.
- Keputusan lintas-agent dan spesifikasi Guru: [Issue #10](https://github.com/zane0184/cbt_november/issues/10).

---

## 2. AREA YANG SUDAH DIKERJAKAN — JANGAN DIBUAT ULANG

### 2.1 W1 Admin Livewire reauthorization — scope patch sudah VERIFIED
Pemeriksaan authorization per request/render telah diterapkan pada komponen:
- Admin: Dashboard, UserManagement, AcademicManagement, CurriculumManagement, ProctorAssignmentManagement selector callback.
- Guru: ResultList, ResultDetail, AnalyticsDashboard, ItemAnalysis.

Perbaikan terkait:
- Blade directive penutup yang salah pada view ResultDetail telah diperbaiki menjadi `@enderror`.
- Duplikasi policy check identik pada `ResultList::render()` dan `AnalyticsDashboard::render()` telah dibuang; masing-masing tetap melakukan satu policy check per render.
- Regression test memastikan pencabutan role Guru ditolak pada request Livewire berikutnya.
- AnalyticsDashboard regression menggunakan mock `ExamAnalyticsService` agar test fokus pada authorization boundary, bukan query statistik. Ini **bukan** bukti bahwa perhitungan/isi Analytics telah lulus acceptance fungsional.

### 2.2 Backup source
Backup pra-patch disimpan di GitHub dan diverifikasi byte-exact. Lokasi yang sudah tercatat:
- `backend-laravel/docs/backup/livewire-admin-render-reauth-2026-11-24/`
- `backend-laravel/docs/backup/teacher-result-render-reauth-2026-11-24/`
- Contoh backup yang tercatat: `Dashboard.php.before`, `ResultList.php.before`, `result-detail.blade.php.before`.
- Backup tambahan untuk AnalyticsDashboard dan regression test tersedia di folder teacher backup tersebut.

**Larangan:** jangan ulang patch authorization yang sama atau mengganti test yang sudah lulus. Buka ulang hanya bila source baru menyentuh komponen/policy tersebut, ada regresi CI relevan, ada bukti kerentanan, atau owner meminta. Jika dibuka, jelaskan pemicunya, lakukan focused test, dan backup byte-exact sebelum perubahan.

### 2.3 Batas status
Yang VERIFIED hanya patch reauthorization di atas dan test pada SHA yang disebut. Ini **bukan** berarti:
- ADM-01..ADM-23 selesai end-to-end;
- seluruh Web Guru selesai;
- seluruh Web Siswa selesai;
- Analytics statistik/hasil telah UAT;
- Proktor siap operasional;
- CBT siap produksi atau siap distribusi.

---

## 3. KEPUTUSAN OWNER — FINAL, JANGAN DITANYAKAN ULANG

### 3.1 Produk dan instalasi
- Sampai Juni 2027 fokus hanya Backend Laravel + Web Admin Madrasah + Web Guru + Web Siswa.
- Android native dan Web Super Admin ditunda sampai Juni 2027.
- Satu ZIP generik yang sama nantinya digunakan semua madrasah.
- **Setiap madrasah memiliki instalasi dan database sendiri. Tidak ada shared multi-tenant akademik/database antar-madrasah.**
- Data identitas, pengguna, akademik, soal, ujian, Attempt, jawaban, nilai dan laporan pendidikan tidak boleh dibagi atau disinkronkan antarinstalasi.
- Integrasi pusat masa depan dibatasi pada lisensi, entitlement/paket, masa berlaku dan pembayaran/langganan. Web Super Admin pusat ditunda sampai Juni 2027.
- Jika source masih memiliki konsep tenant lama, jangan langsung menghapus schema/migration. Audit kompatibilitas instalasi dan histori; jangan menjadikan shared multi-tenant sebagai desain produk. Jangan melakukan migration destructive tanpa rencana upgrade, backup, test disposable dan approval owner.

### 3.2 Role dan portal
Role lokal resmi hanya:
- `ADMIN_MADRASAH`
- `GURU`
- `SISWA`

Ketentuan:
- Tidak ada portal/role Operator. Seluruh tugas administrasi Operator dilebur ke Admin Madrasah.
- Tidak ada portal/role Kamad. Kepala Madrasah hanya jabatan/metadata/penandatangan bila dibutuhkan.
- Tidak ada role/portal Proktor permanen. Proktor adalah menu dan assignment operasional sementara pada portal Guru.
- Role legacy tidak digunakan untuk authorization baru. Jangan menghapus histori atau melakukan penghapusan role legacy tanpa migration upgrade yang ter-audit.
- Penugasan Proktor tidak otomatis memberi hak melihat/mengelola Room, Session, Seat, Roster, Attempt, jawaban, nilai, monitoring peserta, Force Submit, Reset Attempt atau operasi CBT lain.

### 3.3 Batas kewenangan Admin dan Guru
**Admin Madrasah** mengelola administrasi, identitas/branding madrasah, akun Guru/Siswa, master akademik, tahun ajaran/semester, kelas/rombel/mapel/assignment, import/export administratif, promosi/kelulusan, dukungan teknis, audit, health, backup/restore dan laporan administratif.

Admin **tidak**:
- membuat/mengubah/menerbitkan ujian Guru;
- mengelola Bank Soal, token, peserta ujian, Attempt, jawaban, monitoring peserta, penilaian/nilai;
- melakukan Force Submit, Reset Attempt, penutupan ujian atau tindakan CBT Guru.

**Guru** mengelola pembelajaran, CP/TP/ATP sesuai assignment, bank soal, asesmen, ujian, jadwal, token, peserta sesuai assignment, publikasi dan hasil tanpa approval Admin selama sesuai penugasan dan policy.

### 3.4 Kurikulum satu kesatuan
Kurikulum Merdeka dan Kurikulum Berbasis Cinta (KBC/Panca Cinta) adalah **satu konteks terpadu**, bukan modul/kurikulum terpisah. Integrasi wajib meliputi:
- registry/versi kurikulum dan sumber resmi/provenance/tanggal berlaku;
- fase A/B/C, kelas MI 1–6, mapel, elemen dan CP;
- CP resmi sebagai referensi induk read-only dan terverifikasi;
- TP/ATP yang dikembangkan Guru sesuai assignment;
- lima nilai Panca Cinta;
- Pembelajaran Mendalam;
- delapan dimensi profil lulusan;
- materi/topik dan relasi akademik;
- mapping QuestionVersion;
- immutable Exam Snapshot;
- hasil, umpan balik dan laporan dengan redaction yang benar.
Jangan mengarang CP, referensi resmi, enum, materi atau dataset. Semua versi dan histori akademik harus dipertahankan.

### 3.5 Asesmen, import, ujian
- Jangan membuat enum tertutup UTS/UAS/UM/TKA. Pisahkan fungsi asesmen, cakupan, nama kegiatan, instrumen dan mode CBT.
- DOCX adalah jalur utama import soal.
- XLSX question import tidak boleh diaktifkan kembali.
- SHORT_ANSWER tetap fail-closed/nonaktif sampai accepted answer tersimpan privat, ikut immutable snapshot dan scoring teruji end-to-end.
- ESSAY dinilai manual.
- Token otomatis tersedia setelah Guru menerbitkan ujian.
- Ujian mengikuti jadwal server dan berlangsung serentak; waktu server adalah acuan resmi.
- Jangan menciptakan lifecycle wave/session/session_number/session_token sebagai gerbang admission ujian.
- Kamera bukan syarat CBT standar.

### 3.6 Integritas CBT
Attempt, Answer, Score, receipt, client_request_id, idempotency, Device Lease/fencing, offline sync, conflict handling, expiry dan recovery harus konsisten. Jangan menyebut Device Lease/fencing siap produksi sebelum uji integration, multi-device, concurrency dan recovery lulus.

---

## 4. SUMBER GITHUB WAJIB DIBACA

Semua sumber operasional adalah GitHub. Jangan membaca atau memakai ZIP baseline, ChatGPT Library/Pustaka, Project Files, atau rangkuman percakapan sebagai dasar status.

1. Dokumen ini: `docs/PROMPT_AGENT_PENGGANTI_CBT_W1_FINAL_2026-11-24.md`.
2. Prompt lama (sudah diberi penunjuk ke dokumen terbaru): `docs/PROMPT_AGENT_PENGGANTI_CBT_2026-11-24.md`.
3. Handover lama dan addendum: `docs/HANDOVER_AGENT_BARU_LANJUTKAN_BUKAN_MENGULANG_2026-11-24.md`.
4. Checkpoint W1 terbaru: `docs/CHECKPOINT_OWNER_DIRECTED_EXECUTION_2026-11-24.md`.
5. Laporan W1: `docs/REPORT_W1_ADMIN_TEACHER_LIVEWIRE_REAUTH_2026-11-24.md`.
6. Matriks Admin: `docs/MATRIKS_ADM_01_23_AUDIT_2026-11-23.md`.
7. Master Web Admin: https://github.com/zane0184/cbt_november/blob/main/docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md
8. Master Web Guru: https://github.com/zane0184/cbt_november/blob/main/docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md
9. Master Web Siswa: https://github.com/zane0184/cbt_november/blob/work/agent4-web-siswa-audit-20261122/docs/MASTER_SPESIFIKASI_FITUR_WEB_SISWA_CBT_MADRASAH_2026-11-22.md
10. Issue #20 (backlog ADM-01..ADM-23 dan komentar owner): https://github.com/zane0184/cbt_november/issues/20
11. Issue #10 (spesifikasi Guru, keputusan instalasi/role, koordinasi backend-siswa): https://github.com/zane0184/cbt_november/issues/10
12. Actions #1662: https://github.com/zane0184/cbt_november/actions/runs/45257698090
13. Actions #1663: https://github.com/zane0184/cbt_november/actions/runs/45258556051

Komentar owner penting:
- Guru: https://github.com/zane0184/cbt_november/issues/10#issuecomment-7081649923
- Instalasi mandiri/role: https://github.com/zane0184/cbt_november/issues/10#issuecomment-7086214783
- Integrasi Kurmer + KBC lintas portal: https://github.com/zane0184/cbt_november/issues/10#issuecomment-7091954732
- Acceptance Kurmer + KBC end-to-end: https://github.com/zane0184/cbt_november/issues/20#issuecomment-7091952804

Jangan meminta owner mengulang keputusan yang sudah tertulis di sumber-sumber ini.

---

## 5. STATUS BACKLOG — SELESAI, PARSIAL, BLOCKED, DEFERRED

### 5.1 Web Admin / Issue #20
Issue #20 masih OPEN. W1 memiliki patch reauthorization Admin yang telah diuji, tetapi checklist W1 secara keseluruhan tidak otomatis selesai. Bagian W1 yang sudah terbukti adalah reauthorization pada komponen yang disebut di bagian 2. Masih perlu menutup hanya acceptance W1 yang belum terbukti, antara lain bila memang belum ada di source:
- exact-role/multi-role dan role legacy boundary;
- revoke role saat sesi aktif di semua jalur relevan;
- installation/context kosong atau mismatch;
- IDOR/direct request, route/API/export/download;
- migration legacy role secara statis dan upgrade pada disposable MySQL 8;
- negative authorization Admin terhadap seluruh operasi ujian Guru.
Jangan mengulang test reauthorization yang sudah hijau kecuali source berubah atau ada bukti regresi.

Tahapan Issue #20:
- W0 baseline/branch/safety: prosedur wajib, bukan alasan untuk audit ulang seluruh repo.
- W1 RBAC/bootstrap/legacy role/route/API security: reauthorization patch di atas selesai pada scope-nya; acceptance W1 lain masih perlu verifikasi terarah.
- W2 shell UI dan dashboard Admin.
- W3 profil/identitas/branding/kop/pengaturan.
- W4 master Guru/Siswa dan administrasi akun.
- W5 akademik dan kurikulum.
- W6 import/export administratif.
- W7 penugasan Guru pengawas sementara sesuai aturan owner.
- W8 audit log, health, backup/restore.
- W9 integrasi dan acceptance.
Rincian acceptance ada di Issue #20 dan master spesifikasi Admin. Jangan menganggap judul tahap berarti seluruh item sudah selesai.

**ADM-20 / penugasan Proktor ujian pusat tetap BLOCKED** sampai canonical central exam eligibility marker dan pihak/aktor penetapnya diputuskan owner. Jangan menafsirkan `assessment_origin=EXTERNAL` sebagai izin ujian pusat. Tetap fail-closed dan lanjutkan backlog lain yang tidak terblokir.

### 5.2 Web Guru
Master Web Guru mencakup Dashboard, Penugasan Saya, Pembelajaran/Kurikulum, CP/TP/ATP, Bank Soal, versioning, media, Arabic/RTL/harakat/rumus, DOCX import, asesmen/ujian Mode A, token/publish/jadwal serentak, Proktor Mode B, monitoring, penilaian Essay, hasil/laporan/analytics, profil/pengaturan, exact-role, Livewire security dan acceptance.

Reauthorization ResultList/ResultDetail/AnalyticsDashboard/ItemAnalysis sudah lulus 4/4 pada CI. Itu tidak menutup alur fungsional Guru secara end-to-end. Audit source terbaru sebelum mengerjakan fitur; jangan mengulang yang sudah ada.

Mode A: Guru mengelola ujian sendiri dalam assignment. Mode B: Guru dapat menerima assignment Proktor sementara bila canonical scope sudah ditetapkan; jangan memberi privilege tambahan. Tidak ada role PROKTOR permanen.

### 5.3 Web Siswa
Master Web Siswa adalah acuan fitur dan acceptance. Status source harus diperiksa per gate; jangan menyatakan lengkap hanya karena IndexedDB/receipt/submit barrier/media code ada.

Acceptance penting:
- login/session dan exact role SISWA;
- dashboard dan eligibility;
- token/jadwal server, admission dan immutable snapshot;
- runner dan tipe soal/media;
- autosave, revision, receipt, client_request_id, idempotent retry;
- IndexedDB, reload/crash, offline/reconnect, multi-tab dan stale conflict;
- expiry/submit barrier dan hasil;
- redaction server-side;
- browser E2E nyata ke Laravel, bukan mock saja.

Peserta Android wajib memakai native Android; browser Android tidak boleh memulai/melanjutkan Attempt. Web/PWA disediakan untuk iOS dan browser non-Android yang disetujui. Enforcement harus berbasis credential/session/token/ability yang diterbitkan server, bukan User-Agent atau parameter body yang mudah dipalsukan.

PR #13 saat pemeriksaan terakhir masih OPEN/DRAFT. Jangan merge sebelum receipt, token optional, Attempt auth/limit/concurrency, native-platform enforcement, media MIME/Range/seek, serta browser-to-Laravel E2E yang relevan benar-benar teruji pada disposable DB.

### 5.4 Pull request saat pemeriksaan terakhir
- PR #18 Web Admin: OPEN / DRAFT. **HOLD**; jangan merge. Riwayat PR memuat migration/fitur room-session-seat-monitoring yang bertentangan dengan batas kewenangan Admin dan model ujian serentak. Audit diff terbaru sebelum tindakan.
- PR #19 Web Guru: OPEN / DRAFT. Perihal jalur XLSX question import. Jangan menganggap merge diperlukan; XLSX question import dilarang aktif. Audit isi PR terhadap keputusan DOCX-only sebelum tindakan.
- PR #13 Web Siswa: OPEN / DRAFT. Jangan merge sampai gate integrasi dan security Web Siswa lulus.
- Status PR dapat berubah; fetch status terbaru sebelum setiap tindakan.
- Tidak ada merge tanpa persetujuan eksplisit owner.

---

## 6. PERINTAH KERJA BERIKUTNYA — JANGAN BERHENTI DI AUDIT

Agent pengganti wajib langsung melanjutkan, dengan urutan ini:

### Langkah 1 — verifikasi ringan, bukan audit ulang
- Fetch branch dan HEAD terbaru.
- Periksa hanya perubahan sejak SHA handover ini, issue comments baru, PR status dan Actions.
- Baca prompt ini, checkpoint W1, laporan W1, Issue #20 dan bagian spesifikasi yang terkait task berikut.
- Jangan scan ulang seluruh repository atau mengulang baseline PHPUnit.
- Catat selisih jika HEAD sudah maju.

### Langkah 2 — tutup acceptance W1 yang masih terbuka secara terarah
- Bandingkan checklist W1 Issue #20 dengan source, route, policy, test dan bukti exact SHA.
- Tandai hanya subitem yang sudah terbukti DONE.
- Untuk subitem yang belum terbukti, cari file/test existing terlebih dahulu. Implementasikan hanya gap nyata.
- Fokus exact-role, revoke/session, installation context, IDOR/direct route/API, negative authorization Admin dan migration legacy static/upgrade test pada disposable DB.
- Jangan mengubah patch reauthorization yang sudah hijau.

### Langkah 3 — lanjut W2 Web Admin
- Setelah gap W1 yang memblokir ditutup atau dicatat jelas, lanjut W2 shell UI/dashboard.
- Periksa komponen/layout/view/CSS/route dan test yang sudah ada; jangan membuat dashboard/sidebar baru jika implementasi telah tersedia.
- Selesaikan loading/empty/error/success, responsive, keyboard/focus, sidebar desktop/drawer mobile, asset lokal dan metrik yang bersumber dari data valid.
- Jangan menampilkan metrik fiktif.

### Langkah 4 — W3/W4 dan W5 dengan kontrak akademik
- Profil/branding/kop/pengaturan dan master Guru/Siswa.
- Akademik/kurikulum harus memakai satu kontrak backend dan provenance resmi. Jangan membuat schema portal sendiri.
- Jaga histori tahun ajaran/semester, promosi, tinggal kelas, kelulusan kelas 6 dan alumni.

### Langkah 5 — Web Guru dan Web Siswa
- Audit gap nyata terhadap master spec dan lanjutkan acceptance yang belum selesai.
- Integrasikan alur Guru: assignment → CP/TP/ATP → materi/topik → QuestionVersion → asesmen/ujian → publish/token/jadwal → pelaksanaan → hasil/laporan.
- Integrasikan alur Siswa ke backend nyata dan tutup receipt/offline/recovery/media/redaction/browser E2E.
- Koordinasikan kontrak backend bersama; jangan membuat API/DTO/enum paralel.

### Langkah 6 — W6–W9 dan hardening
- Import/export administratif, audit, health, backup/restore, fresh install/upgrade, security/IDOR, concurrency/idempotency, browser E2E dan UAT.
- DOCX question import sebagai jalur utama; XLSX question import tetap disabled.
- SHORT_ANSWER tetap blocked sampai accepted-answer persistence + immutable snapshot + scoring E2E lulus.
- Device Lease/fencing tetap belum production-ready sampai multi-device, concurrency, integration dan recovery lulus.

### Langkah 7 — distribusi dan deployment
- ZIP generik final baru boleh dibuat setelah semua acceptance gate.
- Tiap madrasah akan menginstal ZIP tersebut pada server dan database sendiri.
- README harus menjelaskan ekstraksi, penempatan .env dari backup pemilik, konfigurasi dan finalisasi.
- Fresh install, upgrade, checksum, struktur, ekstraksi bersih dan keamanan wajib diverifikasi.
- VPS/aaPanel staging setelah preflight disanitasi dan approval owner. Migration/deployment produksi memerlukan persetujuan terpisah.
- Jangan menyatakan siap distribusi atau produksi hanya karena PHPUnit hijau.

---

## 7. PROTOKOL PERUBAHAN SOURCE DAN BACKUP

Untuk setiap file existing yang akan diubah:
1. Fetch file dari branch kerja yang tepat.
2. Catat path, blob SHA, ukuran/isi relevan dan alasan perubahan.
3. Buat backup byte-exact di repository sebelum patch.
4. Fetch kembali backup dan bandingkan seluruh konten; bila tidak identik, hentikan patch.
5. Lakukan perubahan sekecil mungkin.
6. Catat commit SHA, diff, backup path/SHA dan file yang berubah.
7. Jangan menyentuh ZIP baseline atau backup lama.
8. Jangan menghapus migration, source, role history atau data historis tanpa rencana forward migration dan approval.
9. Tidak ada production migration atau database production test.

Tidak boleh membuat patch dari asumsi atau menulis ulang file besar jika perubahan lokal yang kecil cukup.

---

## 8. PROTOKOL PENGUJIAN

- Gunakan PHP 8.3 dan MySQL 8/InnoDB disposable.
- Jangan menganggap SQLite sebagai pengganti validasi MySQL untuk constraint, FK, collation, timestamp, locking dan transaction.
- Fokus test pada file/fitur yang berubah terlebih dahulu.
- Full PHPUnit hanya saat relevan, bukan ritual setiap sesi.
- Browser E2E harus benar-benar berkomunikasi dengan Laravel/backend; mock-only bukan integrasi.
- Uji authorization negatif dan positive path.
- Uji concurrency/idempotency, rollback/recovery, offline/sync sesuai area yang berubah.
- Catat exact SHA, Actions run URL, PHP/MySQL, pass/fail/skip/assertions dan log relevan.
- Jika CI dibatalkan karena commit baru, statusnya CANCELLED, bukan FAIL dan bukan PASS. Jangan menyebut pengujian selesai.
- Jangan menurunkan policy atau mengubah expected test hanya untuk membuat status hijau.

---

## 9. FORMAT LAPORAN DAN CHECKPOINT WAJIB

Setiap tahap harus menyimpan laporan di GitHub, dengan bagian:
1. **AUDIT** — source, route, policy, schema, test, risiko, bukti dan gap.
2. **DESAIN** — kontrak/solusi, ownership, boundary, kompatibilitas dan alasan.
3. **IMPLEMENTASI** — file berubah, diff ringkas, backup path/blob SHA, commit SHA.
4. **PENGUJIAN** — exact SHA, workflow/test command, environment, pass/fail/skip/assertions, keterbatasan.
5. **STATUS BACKLOG** — DONE / IN PROGRESS / BLOCKED / NOT STARTED / DEFERRED per item.
6. **RISIKO DAN BLOCKER** — keputusan owner yang benar-benar belum ada.
7. **LANGKAH BERIKUTNYA** — pekerjaan konkret yang langsung dapat dieksekusi.

Update checkpoint dan prompt agent setiap tahap yang mengubah status. Jangan hanya menulis laporan di chat. Jangan mengirim laporan rutin di chat; chat dipakai untuk keputusan penting, approval atau blocker yang memang membutuhkan owner.

---

## 10. LARANGAN KERAS

Tanpa persetujuan eksplisit owner:
- jangan merge PR;
- jangan deploy VPS/produksi;
- jangan menjalankan migration produksi;
- jangan mengubah/menghapus data produksi;
- jangan melakukan operasi destructive pada branch atau baseline;
- jangan membuat/menyatakan ZIP final siap distribusi;
- jangan membuka kembali Android atau Web Super Admin sebelum Juni 2027;
- jangan membuat role Kamad/Operator/Proktor permanen;
- jangan membuat shared multi-tenant database antar-madrasah;
- jangan memberi Admin akses ujian Guru;
- jangan memberi assignment Proktor hak monitoring/operasional yang tidak disetujui;
- jangan mengaktifkan XLSX question import atau SHORT_ANSWER sebelum gate lulus;
- jangan membuat sumber kurikulum resmi palsu;
- jangan menyatakan Device Lease/fencing siap produksi tanpa integration/multi-device/concurrency/recovery;
- jangan meminta owner mengulang keputusan atau mengirim file yang sudah ada di GitHub.

---

## 11. PERINTAH PEMBUKA UNTUK AGENT BARU

Mulai dari dokumen ini. Verifikasi branch dan exact HEAD; periksa hanya perubahan setelah `66801d3a…`. Pastikan run #1662/#1663 tetap tercatat sebagai bukti, jangan mengulang full PHPUnit tanpa pemicu. Baca Issue #20 dan komentar owner terbaru, lalu:

1. tandai status item W1 yang sudah terbukti dan gap W1 yang benar-benar tersisa;
2. pilih subitem W1 yang belum selesai dan tidak blocked;
3. kerjakan langsung dengan backup byte-exact;
4. lanjutkan W2 dan tahapan berikut tanpa mengulang patch W1 yang hijau;
5. simpan laporan/checkpoint di GitHub.

**Jangan berhenti setelah audit. Jangan mengulang. Jangan meminta owner menjelaskan progres. Tugasmu adalah melanjutkan pekerjaan sampai seluruh acceptance gate terpenuhi.**

Status proyek saat handover: **IN PROGRESS — NOT READY FOR DISTRIBUTION / NOT READY FOR PRODUCTION**.
