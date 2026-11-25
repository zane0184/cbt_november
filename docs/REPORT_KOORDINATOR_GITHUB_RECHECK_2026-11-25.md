# Laporan Koordinator — Recheck GitHub dan Urutan Eksekusi Web CBT
Tanggal: 25 November 2026 (WIB)
Repository: `zane0184/cbt_november`
Branch dokumentasi: `audit/coordinator-scope-reconciliation-20261125`

## Ruang lingkup audit

Pemeriksaan hanya menggunakan GitHub. Tidak membuka ZIP baseline, ChatGPT Project/Library, workspace lokal, atau database produksi. Tidak ada source aplikasi yang diubah dalam tahap ini.

Sumber yang dibaca:
- Master Web Admin: `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md` (main).
- Master Web Guru: `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md` (main).
- Master Web Siswa: `docs/MASTER_SPESIFIKASI_FITUR_WEB_SISWA_CBT_MADRASAH_2026-11-22.md` (branch `work/agent4-web-siswa-audit-20261122`).
- W4 agent handover, `AGENT_START_HERE.md`, owner addendum instalasi/role/Proktor, DOCX addenda/gate, Student Import report/checkpoint, coordinator reconciliation/checkpoint, independent QA route inventory and limited-P0 audit, relevant Issues #9/#12/#14/#15/#20, PR #21/#23/#24, and exact Actions runs referenced below.

## KEPUTUSAN OWNER — baseline kerja aktif

- Sampai Juni 2027 scope implementasi adalah Laravel backend + Web Admin Madrasah + Web Guru + Web Siswa.
- Android native dan Web Super Admin ditunda sampai Juni 2027. Tidak menjadi blocker Web sekarang.
- Satu ZIP generik, satu instalasi dan satu database akademik mandiri per madrasah. Tidak ada shared academic database atau akses data akademik lintas madrasah. `tenant_id` yang masih ada hanya diperlakukan sebagai guard lokal/kompatibilitas/defense-in-depth; jangan dihapus massal tanpa audit migration, FK, dan seluruh query.
- Pusat masa depan hanya untuk lisensi, aktivasi, entitlement/paket, masa berlaku, dan pembayaran; bukan untuk data akademik.
- Role resmi lokal tepat `ADMIN_MADRASAH`, `GURU`, `SISWA`. Operator dan Kamad tidak memiliki akun/role/portal. Proktor adalah penugasan/capability sementara pada Guru.
- Fungsi administrasi Operator berada pada Admin Madrasah. Admin tidak boleh mengambil alih operasional ujian Guru.
- Guru mengelola bank soal, asesmen, ujian, jadwal, publish dan token sesuai penugasan tanpa approval Admin.
- Model ujian serentak: admission berdasarkan Exam, token, status/eligibility, schedule dan waktu server. Room hanya untuk placement/monitoring scope; bukan admission gate. Tidak menambah ExamSession/wave/session_number sebagai lifecycle operasional.
- Kurikulum Merdeka + KBC/Panca Cinta satu kesatuan bersama Pembelajaran Mendalam dan delapan dimensi, dengan provenance resmi, versi, assignment context dan immutable snapshot/histori.
- DOCX jalur utama import soal; jangan hidupkan XLSX importer soal lama. XLSX/CSV tetap jalur administratif import Guru/Siswa dan export.
- Login: Admin username; Guru PNS/PPPK NIP wajib 18 digit; Guru honorer NIK wajib 16 digit; Siswa NISN wajib 10 digit. Semua identifier string; no Guru username/NUPTK fallback.
- SHORT_ANSWER tetap BLOCKED sampai accepted answer persisten, masuk immutable snapshot dan scoring E2E. ESSAY manual review. MATCHING tetap disabled sampai runtime E2E. Shared stimulus wajib first-class/versioned dan snapshot immutable; TRUE_FALSE_GROUP tepat tiga statement ber-ID stabil dengan scoring proporsional.

## AUDIT — branch, commit, CI, PR

### Jalur eksekusi yang ditunjuk handover W4
- Branch: `work/solo-web-cbt-completion-20261123-execution`
- HEAD: `2a82331fe7aede1d051af1c4e268af9e772123d0`
- Workflow run #1873 / ID `45339951497`: completed SUCCESS pada exact HEAD, PHP 8.3.37 + MySQL 8.0.46/InnoDB disposable, 565 passed / 2,544 assertions / 0 failed (sesuai laporan W4/checkpoint). Ini bukti backend PHPUnit pada SHA tersebut, bukan Browser E2E/UAT.
- Branch ini masih menjadi jalur kandidat eksekusi berdasarkan handover. Jangan menganggap PR #21 sama dengan HEAD ini: PR #21 masih OPEN/DRAFT/UNMERGED dan memakai head `work/solo-web-cbt-completion-20261123` yang lebih lama.

### Student Import pada branch terpisah
- Branch: `work/benchmark-cleanup-proof-20261125`
- HEAD saat audit: `a65b4604495e8bcc153bf0e09df12a9efb11543a`
- Workflow run #93 / ID `45482223886`: completed SUCCESS pada exact SHA, PHP 8.3 dan PHP 8.4 masing-masing dengan MySQL 8.0/InnoDB disposable, 619 passed / 1 skipped / 3,110 assertions per matriks. Catatan laporan awal menyebut acceptance 11 passed/52 assertions pada SHA benchmark lebih awal; run #93 yang lebih baru menyebut student import acceptance 20 passed/77 assertions. Jangan menyatukan hasil dari dua SHA/run.
- Implementasi sudah ada di branch ini, termasuk:
  - `app/Services/StudentImportService.php`
  - `app/Livewire/Admin/StudentImport.php`
  - `app/Services/Import/XlsxPackagePreflight.php`
  - `app/Services/Import/StudentImportReadFilter.php`
  - `resources/views/livewire/admin/student-import.blade.php`
  - `tests/Feature/StudentImportTest.php`
  - `tests/Feature/StudentImportXlsxPreflightTest.php`
- Pemeriksaan file pada execution branch menunjukkan ketujuh file di atas NOT FOUND. Jadi Student Import belum terintegrasi ke branch eksekusi, namun patch dan hardening sudah tersedia; tidak boleh dibuat ulang.
- Compare GitHub execution vs benchmark: diverged, 550 commits ahead / 314 behind pada snapshot compare. Dilarang merge/cherry-pick seluruh branch atau port secara massal. Integrasikan selektif hanya setelah audit exact migrations/dependencies/routes/policies/workflow/backup dan focused regression pada target.
- Temuan yang masih perlu diperiksa pada implementasi Student Import: issuance credential satu kali/masa berlaku dan first-login password change, concurrency idempotency/NISN di MySQL, UAT browser, dan kesesuaian migration tabel batch. CI PASS pada scope-nya bukan penerimaan operasional browser.

### DOCX branch/design
- Branch: `work/docx-import-parser-20261125`; HEAD `d7a33b73e32d42064a31f4ef29c01266121d29f0`.
- Desain addenda dan implementation gate sudah ada, namun parser belum diimplementasikan.
- Tidak ditemukan binary DOCX fixture di GitHub tree pada pemeriksaan issue #9/comment 25 Nov. Hash metadata pada dokumen bukan pengganti bytes dan tidak diperlakukan sebagai fixture yang tersedia.
- Tidak ada `QuestionDraftDTO`, `ImportBatch`, `ImportBatchRow` pada branch benchmark; versi lama ada di branch `work/import-guru-siswa-implementation-20261122` yang divergen dan hanya boleh dipakai sebagai referensi selektif.
- Shared stimulus first-class, TRUE_FALSE_GROUP persistence/runner/scoring, dan Matching fail-closed belum lengkap. DOCX tetap urutan sesudah Student Import dan dependency question contract.

### QA independen P0 ZIP terbatas
- Branch QA: `audit/independent-qa-admin-guru-siswa-20261125`, checkpoint `docs/CHECKPOINT_INDEPENDENT_QA_2026-11-25.md`, commit audit yang disebut checkpoint: `fabe1147b3d2e52626529d3e5c9eb37af4fc80de`.
- QA mengidentifikasi dua blocker source P0 untuk ZIP uji operasional terbatas:
  1. Provisioning Tahun Pelajaran, Semester dan Grade Level MI 1–6 untuk instalasi baru. Installer membuat Tenant+Admin; tidak membuat data akademik lokal awal. UI AcademicManagement hanya create Rombel/Mapel; Tahun Pelajaran/Semester/Grade Level dibutuhkan sebagai prasyarat tetapi belum dikelola melalui UI/flow setup.
  2. CP resmi belum diimpor/checksum-verified. Registry seeder membangun metadata/struktur fase/kelas namun menetapkan `cp_content_imported=false`; Teacher TP dan QuestionBank/Exam Builder memblokir ketika CP resmi tidak tervalidasi. Jangan membuat CP fiktif atau menandai checksum verified tanpa artefak sumber yang benar.
- QA juga mencatat release gates P0: opt-in clean-install E2E pada MySQL disposable; workflow ZIP pada exact final SHA dengan artifact/manifest/checksum; ekstrak ZIP dan browser E2E minimum Admin→Guru→Siswa→Attempt/Answer/Submit/Score termasuk refresh/reconnect, duplicate submit, dan negative access tests.
- Fitur yang boleh tetap sebagai backlog CBT final dan bukan blocker ZIP uji terbatas jika skenario operasional awal cukup: import Guru massal (Guru manual tersedia), edit profil, monitoring Admin, Proktor assignment jika belum diperlukan, DOCX jika soal dimasukkan manual, Android native (deferred).
- QA ini hanya audit dokumentasi/source/CI pada branch audit; tidak menjalankan tes runtime independen dan tidak mengubah source Agent 1.

### PR/Issue
- Issue #9 DOCX, #12 Master Admin, #14 Kurmer+KBC snapshot, #15 Pembelajaran Mendalam/delapan dimensi, #20 ADM-01..23 masih OPEN sesuai snapshot yang diperiksa.
- PR #21 dan #23 tetap OPEN/DRAFT/UNMERGED; tidak ada izin merge. PR #24 berstatus SUPERSEDED — DO NOT MERGE.
- Issue #10 yang memuat desain ExamSession lama tidak boleh dipakai untuk membatalkan keputusan owner terbaru tentang ujian serentak tanpa ExamSession operasional.
- Branch audit independen punya scope QA, bukan branch implementer. Dokumen auditnya mempersempit urutan release P0 terbatas, bukan menyatakan proyek akhir lengkap.

## DESAIN — langkah berikutnya yang terarah

Prioritas jalur operasional menuju ZIP dibedakan dari backlog kelengkapan CBT final:

1. **P0-A — Provisioning akademik instalasi baru.** Audit relasi/migration/constraints dan lifecycle Tahun Pelajaran/Semester/Grade Level yang sudah ada. Rancang provisioning lokal minimal, idempotent, transactional, fail-closed, histori aman; jangan membuat duplikasi schema atau migrasi destructive. Jangan jalankan migration produksi.
2. **P0-B — CP resmi.** Identifikasi artefak CP sumber resmi yang dapat diperoleh secara sah di GitHub/attachment project? Saat task owner hanya mengizinkan GitHub, jangan buka library. Jika artefak belum ada pada GitHub, tandai blocker evidence dan minta hanya sumber CP resmi/URL/berkas yang dibutuhkan pada tahap itu; tidak boleh membuat data sintetis.
3. **P0-C — Integrasi Student Import.** Setelah integrasi provisioning didesain/selesai atau secara dependency jelas, port secara selektif tujuh file Student Import dari benchmark ke execution. Audit migration dan service dependencies terlebih dulu; backup byte-exact setiap file yang sudah ada dan catat blob SHA. Jangan duplikasi test atau mengulang hardening.
4. **P0-D — Clean install, package dan minimal browser E2E.** Jalankan installer opt-in hanya ke disposable MySQL yang disetujui; build ZIP pada exact SHA final, verifikasi isi/hash/extract, dan browser E2E alur minimal.
5. Setelah ZIP uji operasional terbatas lulus, lanjutkan semua backlog CBT final menurut master specs: Admin ADM-01..23, Guru, Siswa, DOCX, Kurmer+KBC provenance, Proktor jika eligibility canonical diputuskan, backup/restore, concurrency, accessibility/performance, UAT, dan release ZIP final.

Jangan mengerjakan Proktor Mode B dengan menganggap EXTERNAL = ujian pusat; canonical eligibility marker dan aktor pemutus belum ada. Jangan mengaktifkan menu/route yang tak dapat memenuhi exact policy.

## IMPLEMENTASI pada sesi audit ini
- Tidak ada source aplikasi, migration, workflow, issue, PR, database, VPS, atau branch implementer yang diubah.
- Tidak ada backup source yang dibuat karena tidak ada source patch.
- Laporan/checkpoint yang dibuat hanya catatan audit koordinasi pada branch dokumentasi.

## PENGUJIAN / bukti
- Diperiksa langsung GitHub commit/tree/contents/issue/PR/compare/workflow run.
- Run #1873 verified completed SUCCESS pada exact execution HEAD.
- Run #93 verified completed SUCCESS pada exact benchmark HEAD.
- Tidak menjalankan PHPUnit/browser/MySQL/local tests pada sesi audit ini.
- Tidak menjalankan installer E2E, migration, deployment atau build ZIP.
- Tidak mengklaim fitur selesai hanya dari keberadaan file/menu.

## Kesimpulan
Source backend memiliki fondasi kuat dan regression CI yang sehat pada commit tertentu, tetapi aplikasi Web belum terbukti melalui browser E2E dan ZIP distribusi final belum dihasilkan/diverifikasi. Dua blocker source untuk ZIP uji terbatas adalah master akademik awal instalasi dan CP resmi terverifikasi. Student Import sudah selesai pada branch terpisah, sehingga tugasnya adalah integrasi selektif, bukan implementasi ulang.

Status saat checkpoint ini: **AUDIT GITHUB RECHECK SELESAI — IMPLEMENTASI P0 BELUM DIMULAI — ZIP FINAL NOT READY — NOT PRODUCTION READY**.
