# Laporan Koordinator — Rekonsiliasi Scope dan Backlog Web CBT
Tanggal pemeriksaan: 25 November 2026 (WIB)
Repository: zane0184/cbt_november
Branch dokumentasi audit: audit/coordinator-scope-reconciliation-20261125
Branch eksekusi yang ditunjuk handover W4: work/solo-web-cbt-completion-20261123-execution
HEAD branch eksekusi: 2a82331fe7aede1d051af1c4e268af9e772123d0
Source SHA aplikasi terakhir pada branch eksekusi: 0250dc257cc7292d960cacf2c20eda782ee8a25d
CI exact HEAD: https://github.com/zane0184/cbt_november/actions/runs/45339951497

## 1. Mandat dan keputusan owner yang berlaku

Laporan ini mengunci kembali keputusan owner yang cocok dengan dokumen owner terbaru di GitHub:

- Fokus sampai Juni 2027: Backend Laravel, Web Admin Madrasah, Web Guru, Web Siswa.
- Android native dan Web Super Admin ditunda sampai Juni 2027. Jangan mengembangkan, menguji, atau menjadikan keduanya blocker pekerjaan Web saat ini.
- Produk didistribusikan sebagai satu ZIP generik. Setiap madrasah memasang sendiri dan mempunyai database akademik, konfigurasi, storage, cache, queue, log, export, dan backup sendiri.
- Tidak ada shared academic database atau akses akademik lintas madrasah. Ini bukan SaaS multi-tenant akademik. Kolom/guard tenant_id yang masih ada diperlakukan sebagai kompatibilitas instalasi lokal dan defense-in-depth; jangan menghapusnya secara massal tanpa audit schema, query, FK, migration, installer, dan histori.
- Integrasi pusat masa depan hanya untuk lisensi, aktivasi/validasi, entitlement/paket fitur, masa berlaku, dan pembayaran. Tidak mengirim data akademik, identitas siswa, soal, jawaban, Attempt, nilai, atau laporan ke pusat.
- Role lokal resmi tepat tiga: ADMIN_MADRASAH, GURU, SISWA.
- Operator tidak memiliki akun/role/portal; seluruh fungsi administrasinya berada pada Admin Madrasah.
- Kamad tidak memiliki akun/role/portal; nama/jabatan Kepala Madrasah hanya metadata dokumen bila diperlukan.
- Proktor bukan role/portal permanen. Proktor adalah capability/penugasan sementara pada akun Guru dan tampil di Web Guru. Assignment tidak otomatis memberi hak monitoring/Room/roster/Attempt/jawaban/nilai.
- Guru mengelola pembelajaran, bank soal, asesmen, ujian, jadwal, token, dan publikasi sesuai assignment tanpa persetujuan Admin.
- Admin mengelola administrasi, akun, master akademik, dukungan teknis, sinkronisasi, audit, pengaturan, dan laporan administratif. Admin tidak mengelola authoring/operasi ujian Guru, peserta, token, Attempt, jawaban, monitoring, atau nilai.
- Kurikulum Merdeka dan KBC/Panca Cinta adalah satu kesatuan, bersama Pembelajaran Mendalam dan delapan dimensi profil lulusan. CP resmi read-only dengan provenance/versi terverifikasi; Guru mengembangkan TP/ATP sesuai assignment.
- Model ujian serentak: waktu server dan Exam.start_at/end_at menjadi acuan; token otomatis saat publish; tidak ada ExamSession/wave/session sebagai admission gate.
- Kamera bukan syarat CBT standar. Device Lease/fencing belum boleh disebut siap produksi tanpa integrasi, multi-device, concurrency, dan recovery test.

Acuan owner:
- https://github.com/zane0184/cbt_november/blob/work/benchmark-cleanup-proof-20261125/docs/ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md
- https://github.com/zane0184/cbt_november/blob/work/solo-web-cbt-completion-20261123-execution/docs/AGENT_START_HERE.md

## 2. AUDIT — posisi GitHub aktual

### 2.1 Branch eksekusi

Branch kerja yang ditetapkan oleh handover W4 adalah work/solo-web-cbt-completion-20261123-execution.

- HEAD: 2a82331fe7aede1d051af1c4e268af9e772123d0.
- Perbandingan source SHA 0250dc257cc7292d960cacf2c20eda782ee8a25d ke HEAD menunjukkan hanya dokumen checkpoint akademik/lifecycle yang bertambah; tidak ada source aplikasi berubah sesudah source SHA tersebut.
- Run #1873 pada exact HEAD berstatus SUCCESS. PHP 8.3.37, MySQL 8.0.46/InnoDB disposable; full PHPUnit 565 passed, 2.544 assertions, 0 failed. Focused login/tenant 30/99, Admin reauthorization 5/5, Teacher result reauthorization 4/8, account lifecycle/license/concurrency 25/271, Web/API auth 30/121.
- Run ini membuktikan regression suite backend pada exact HEAD, bukan browser E2E, UAT, atau kesiapan seluruh fitur.

Bukti: https://github.com/zane0184/cbt_november/actions/runs/45339951497

### 2.2 Branch Student Import yang terpisah

Ditemukan pekerjaan lebih baru pada work/benchmark-cleanup-proof-20261125, HEAD a65b4604495e8bcc153bf0e09df12a9efb11543a.

- Sudah ada Student Import XLSX/CSV, preview, NISN string, duplikasi/idempotency, transaksi/rollback/audit, tenant/install guard, XLSX ZIP/OOXML preflight, dan batas CSV.
- Run #93 / ID 45482223886 pada exact SHA a65b460... SUCCESS pada PHP 8.3 dan PHP 8.4 + MySQL 8.0.46/InnoDB.
- Full PHPUnit per matriks: 619 passed, 1 skipped, 3.110 assertions.
- Student Import acceptance dan benchmark cleanup denial-path juga dijalankan pada kedua matriks.
- File StudentImportService, Livewire StudentImport, StudentImportReadFilter, XlsxPackagePreflight, migration batch, view, dan test belum ada pada branch eksekusi work/solo-web-cbt-completion... saat pemeriksaan ini.

Dua branch tersebut divergen (perbandingan GitHub: benchmark branch 550 commit ahead dan 314 commit behind terhadap solo execution branch). Jangan merge/cherry-pick seluruh branch dan jangan mengulang implementasi Student Import. Pekerjaan ini harus direkonsiliasi dan diintegrasikan selektif ke branch eksekusi setelah audit dependency, route, schema, service, UI, backup, dan regression pada exact source target.

Bukti: https://github.com/zane0184/cbt_november/actions/runs/45482223886

### 2.3 PR dan Issue aktif

- Issue #20 Web Admin ADM-01..ADM-23 tetap OPEN; ini backlog induk Admin.
- PR #21 Solo Web CBT masih OPEN/DRAFT/UNMERGED; head PR bukan HEAD branch execution terbaru.
- PR #23 P1 Web CBT masih OPEN/DRAFT/UNMERGED dan menggunakan branch P1 yang lebih lama.
- PR #24 Student Import security preflight secara eksplisit berstatus SUPERSEDED — DO NOT MERGE. Implementasi hardening yang diteruskan adalah implementasi di branch benchmark-cleanup-proof, bukan PR #24.
- PR #13 Web Siswa, #18 Web Admin/Room/Proktor, #19 XLSX question import removal, #17 Guru contracts, dan PR lama lain masih draft. Jangan merge otomatis. PR #18 terutama tidak boleh digabung apa adanya karena desain Room/session/seat/roster/monitoring bertentangan dengan model ujian serentak dan batas kewenangan owner.
- Issue #9 DOCX, #14 mapping CP/TP/ATP/KBC snapshot, dan #15 Pembelajaran Mendalam/delapan dimensi tetap menjadi dependency kurikulum/import.

### 2.4 Insiden main

HEAD main yang diperiksa: aa5a5d39e41ccfbd777cb0766d2a6f83e41801dc. Riwayat menunjukkan manifest backup pernah masuk ke main dan dihapus melalui commit koreksi. Riwayat tersebut harus tetap dicatat transparan; tidak boleh diklaim main tidak pernah tersentuh. Audit ini tidak mengubah main.

## 3. AUDIT — matriks pekerjaan per portal

Status memakai: IMPLEMENTED & TESTED (hanya untuk scope yang punya exact CI), PARTIAL, NOT VERIFIED, BLOCKED, atau DEFERRED. Keberadaan menu/route bukan bukti fitur selesai.

### 3.1 Backend dan keamanan bersama

| Area | Status saat ini | Catatan |
|---|---|---|
| Laravel 13 / PHP 8.3 / MySQL 8 InnoDB CI | IMPLEMENTED & TESTED pada exact HEAD | Run #1873; bukan E2E browser |
| Exact role lokal | IMPLEMENTED pada source; perlu acceptance portal menyeluruh | RolePermissionBootstrapper hanya bootstrap tiga role; OPERATOR/KAMAD/PROKTOR/SUPER_ADMIN tidak diberi grant dan tidak otomatis dipetakan |
| Account lifecycle Guru/Siswa | IMPLEMENTED & TESTED | Aktivasi/deaktivasi, reset kredensial, force change password, auth_version, session/token revocation, Attempt IN_PROGRESS guard, audit dan concurrency; Run #1873 |
| Isolasi instalasi | PARTIAL / perlu E2E | Database terpisah per madrasah; tenant_id lokal/legacy defense-in-depth; tidak boleh ada cross-install data sharing |
| Exam/Attempt/Answer/Score locking dan idempotency | PARTIAL / backend tests ada | Belum membuktikan seluruh browser-to-real-Laravel flow dan recovery operasional |
| Device Lease/fencing | NOT PRODUCTION VERIFIED | Wajib multi-device, concurrency, stale fence, takeover/recovery test |
| Force Submit berotorisasi | NOT VERIFIED / GAP | Audit lifecycle menemukan belum ada endpoint Force Submit Admin/Proktor yang terpisah |
| Backup/restore | NOT VERIFIED | Harus ada restore test pada database disposable; tidak boleh menguji restore destruktif pada produksi |

### 3.2 Web Admin Madrasah

| Area ADM | Status |
|---|---|
| ADM-01 Dashboard data nyata, filter tahun aktif, tanpa dummy | PARTIAL / E2E NOT VERIFIED |
| ADM-02 Profil dasar madrasah | IMPLEMENTED & TESTED untuk field dasar; metadata lanjutan belum lengkap |
| ADM-03 Logo/favicon aman | PARTIAL / acceptance lengkap belum terbukti |
| ADM-04 Branding login/header/footer per instalasi | PARTIAL / E2E belum diterima |
| ADM-05 Kop dokumen fleksibel, preview, cetak | NOT VERIFIED end-to-end |
| ADM-06 CRUD/lifecycle akun Guru | Lifecycle akun PASS; detail/edit/profil/riwayat assignment masih perlu acceptance penuh |
| ADM-07 Lifecycle Siswa, arsip, mutasi, promosi, kelulusan/alumni | PARTIAL / belum acceptance penuh |
| ADM-08 Akun, reset, revoke, first-login change | IMPLEMENTED & TESTED pada account lifecycle scope |
| ADM-09 Import Guru XLSX/CSV | NOT STARTED / NOT VERIFIED |
| ADM-10 Import Siswa XLSX/CSV | Ada implementasi hardening pada branch benchmark terpisah; BELUM TERINTEGRASI ke branch eksekusi |
| ADM-11 Export Guru aman | NOT VERIFIED |
| ADM-12 Export Siswa aman | NOT VERIFIED |
| ADM-13 Tahun ajaran/semester lifecycle | BLOCKED pada desain v2 belum disetujui dan preflight DB representatif belum tersedia |
| ADM-14 Kelas MI 1–6 | PARTIAL / E2E NOT VERIFIED |
| ADM-15 Rombel, mutasi, histori, promosi | PARTIAL / E2E NOT VERIFIED |
| ADM-16 Mata pelajaran/Mulok dan histori | PARTIAL; create + audit event diuji, bukan CRUD lengkap |
| ADM-17 Penugasan Guru per mapel/rombel/periode | PARTIAL / negative scope E2E belum lengkap |
| ADM-18 Registry kurikulum dan versi | PARTIAL |
| ADM-19 Struktur CP/TP/ATP, KBC dan provenance snapshot | BLOCKED/PARTIAL; CP resmi belum verified dan mapping belum dibuktikan immutable di Exam Snapshot |
| ADM-20 Penugasan Guru Proktor ujian pusat | BLOCKED; service assign masih fail-closed karena marker eligibility pusat belum kanonis |
| ADM-21 Laporan akademik administratif dan cetak | NOT VERIFIED |
| ADM-22 Audit log | CREATE akun Guru/Siswa, Rombel, mapel IMPLEMENTED & TESTED; operasi edit/nonaktif/mutasi/import/export/config belum seluruhnya |
| ADM-23 Pengaturan, tema, health, backup/restore | NOT VERIFIED end-to-end |

### 3.3 Web Guru

- Fondasi dashboard, penugasan, pembelajaran/kurikulum, bank soal, Exam Builder, hasil/analitik, dan reauthorization sudah ada di source.
- Reauthorization hasil/analitik Guru: 4 regression tests PASS; ini hanya boundary authorization, bukan acceptance fungsional analytics.
- Bank soal dan snapshot memiliki fondasi; perlu audit field-by-field terhadap immutable QuestionVersion → Exam Snapshot, termasuk mapping kurikulum, media, key privat, explanation, dan scoring.
- Guru harus dapat membuat/menerbitkan ujian sesuai assignment tanpa persetujuan Admin; perlu browser E2E publish, token otomatis, idempotency, jadwal serentak dan server time.
- DOCX import soal belum terbukti terimplementasi end-to-end. Issue #9 meminta satu DTO/lifecycle/batch/storage; owner addendum 25 Nov mewajibkan parser fleksibel, ordered content/provenance, preview/koreksi, tanpa menebak kunci atau pemetaan akademik.
- XLSX question importer lama tidak boleh diaktifkan kembali. PhpSpreadsheet tetap dapat digunakan untuk import Guru/Siswa Admin dan export.
- SHORT_ANSWER tetap BLOCKED sampai accepted answer disimpan privat, masuk immutable snapshot, dan dinilai ScoringService. ESSAY tetap manual review.
- Shared stimulus harus first-class/versioned dan masuk immutable snapshot. TRUE_FALSE_GROUP tiga pernyataan harus memiliki ID stabil dan scoring proporsional. MATCHING tetap disabled sampai runtime E2E.
- Mode Proktor di Web Guru belum siap; assignment pusat tetap BLOCKED. Jangan memberi hak monitoring Room/roster/Attempt hanya karena assignment.

### 3.4 Web Siswa

- Runner Web Siswa pada source memiliki fondasi IndexedDB: attempt_context, answers, sync_queue, server_receipts, conflicts, local_meta; queue/receipt/revision/conflict/submit barrier dan recovery marker telah dicatat dalam handover.
- MediaController memiliki pemeriksaan ownership Attempt, auth-session binding, lease/fence dan keterikatan media snapshot menurut source audit.
- Semua di atas masih source evidence, bukan browser E2E. Belum ada bukti acceptance lengkap untuk reload/crash, offline/reconnect, multi-tab, stale conflict, receipt backend, expiry, submit, media, dan result.
- Browser deterrent tidak boleh diklaim sebagai kiosk/anti-screenshot.
- Android native tetap deferred; Web Siswa tidak boleh diklaim sebagai Android native.

### 3.5 Kurikulum Merdeka + KBC satu kesatuan

- CP resmi induk harus read-only dengan provenance dan versi yang benar-benar diverifikasi.
- TP/ATP dibuat Guru sesuai assignment.
- Panca Cinta/KBC, Pembelajaran Mendalam, dan delapan dimensi profil lulusan terhubung dalam satu konteks akademik.
- Issue #14 menemukan mapping CP/TP/ATP/KBC belum ikut immutable Exam Snapshot secara lengkap.
- Issue #15 menemukan referensi Pembelajaran Mendalam dan delapan dimensi belum memiliki dataset/provenance resmi terverifikasi; jangan membuat seed sintetis atau placeholder seolah resmi.
- Perubahan referensi kurikulum tidak boleh mengubah histori QuestionVersion, Exam Snapshot, Attempt, Score, atau laporan lama.

## 4. AUDIT — lifecycle tahun ajaran/semester

Dokumen backend-laravel/docs/checkpoints/2026-11-24-year-semester-lifecycle-audit-design-preflight-v2.md pada branch eksekusi menyatakan:

- Audit source-level selesai.
- Desain v2 (pointer periode aktif + generated stored columns/FK/CHECK) belum disetujui.
- Preflight data belum dijalankan karena database disposable/representatif tidak tersedia.
- Risiko CASCADE/SET NULL pada graph akademik/CBT dan histori Attempt/Answer/Score harus direkonsiliasi.
- Tidak ada source, migration, trigger, route, test, atau database yang diubah.

Jangan membuat migration lifecycle periode sebelum desain disetujui dan preflight read-only pada clone/disposable representatif tersedia. Ini blocker keselamatan data, bukan alasan untuk meminta spesifikasi VPS lagi.

## 5. DESAIN — urutan kelanjutan tanpa mengulang

1. Pertahankan work/solo-web-cbt-completion-20261123-execution sebagai jalur eksekusi yang ditunjuk handover W4. Jangan pindah ke main atau mengganti basis tanpa rekonsiliasi.
2. Integrasikan Student Import dari branch benchmark secara selektif. Audit semua file, migration, route, service, UI, backup, dan tests terhadap source aktif; port hanya gap yang belum ada. Jangan merge PR #24 dan jangan merge seluruh branch.
3. Lanjutkan Admin: import Guru, export Guru/Siswa, lifecycle profil Siswa/arsip/mutasi/promosi/kelulusan, kelas/mapel/assignment, branding/kop, laporan dan audit operasi selain CREATE.
4. Lifecycle Tahun Ajaran/Semester hanya setelah desain v2 disetujui dan preflight database representatif selesai.
5. Lanjutkan Web Guru: DOCX parser dan shared DTO/batch, snapshot kurikulum, media/Arabic/RTL/formula, publish/token, grading/analytics, Proktor hanya setelah marker kanonis.
6. Lanjutkan Web Siswa: browser E2E terhadap Laravel/MySQL nyata, offline/reconnect/recovery/submit/media.
7. Tutup provenance resmi Kurmer+KBC/PM/8 dimensi dan historical snapshot.
8. Fresh install, upgrade, security, concurrency, backup/restore, browser E2E dan UAT sebelum ZIP.
9. Bangun ZIP baru hanya dari exact integrated source SHA setelah acceptance. ZIP Run #436 dari branch packaging terdahulu bukan paket final dari HEAD eksekusi sekarang.

## 6. IMPLEMENTASI PADA TAHAP AUDIT INI

- Tidak ada source aplikasi, migration, database, workflow, branch implementer, PR, atau main yang diubah.
- Dibuat branch dokumentasi audit terpisah audit/coordinator-scope-reconciliation-20261125 dari HEAD eksekusi 2a82331...
- Laporan dan checkpoint ini adalah perubahan dokumentasi saja.
- Tidak ada merge, cherry-pick, deployment VPS, production migration, atau ZIP baru.

## 7. PENGUJIAN / VERIFIKASI

- Diperiksa GitHub refs/HEAD untuk branch execution, benchmark Student Import, docx-import-parser, dan main.
- Diperiksa commit comparison: source SHA 0250dc... ke HEAD 2a82331... hanya menambah dokumen checkpoint; source aplikasi tidak berubah.
- Diperiksa Run #1873, seluruh job steps SUCCESS; full PHPUnit 565 passed / 2.544 assertions / 0 failed.
- Diperiksa Run #93 pada branch Student Import, kedua matriks PHP 8.3/8.4 SUCCESS; full PHPUnit 619 passed / 1 skipped / 3.110 assertions per matriks.
- Diperiksa Issues #9/#14/#15/#20, PR #13/#18/#19/#21/#23/#24/#3, owner addendum, W4 handover, Admin/Guru master specs, dan lifecycle checkpoint.
- Tidak menjalankan PHPUnit, browser E2E, migration, SQL preflight, atau deployment dari tahap audit ini.

## 8. BACKLOG DAN KEPUTUSAN

- Account lifecycle Guru/Siswa: DONE — ACCEPTANCE CI PASS.
- Admin/Guru Livewire reauthorization W1: DONE — TESTED.
- Admin audit CREATE akun/Rombel/mapel: DONE — TESTED.
- Student Import XLSX/CSV hardening: DONE ON SEPARATE BRANCH; NOT INTEGRATED.
- DOCX question parser: NOT IMPLEMENTED / CONTRACT DESIGN IN PROGRESS.
- Admin ADM-01..23: PARTIAL; NOT VERIFIED END-TO-END.
- Web Guru: PARTIAL; NOT VERIFIED END-TO-END.
- Web Siswa: SOURCE FOUNDATION EXISTS; BROWSER E2E NOT VERIFIED.
- Curriculum provenance Kurmer+KBC/PM/8 dimensions: BLOCKED / PARTIAL.
- Academic period lifecycle: DESIGN NOT APPROVED; PREFLIGHT BLOCKED.
- Proktor central eligibility: BLOCKED / FAIL-CLOSED.
- Final ZIP for current integrated source: NOT READY.
- Production readiness: NOT CLAIMED.

## 9. NEXT HANDOVER

Langkah teknis pertama setelah laporan ini: audit kompatibilitas dan integrasi selektif Student Import dari branch work/benchmark-cleanup-proof-20261125 ke branch eksekusi, tanpa mengulang hardening atau test yang sudah lulus pada branch asal. Sebelum patch, backup byte-exact file target yang sudah ada pada branch eksekusi; port hanya file yang benar-benar belum tersedia/berbeda. Jalankan focused tests lalu CI exact SHA. Setelah itu lanjut backlog lain sesuai dependency dan approval.

Prinsip: lanjutkan source yang sudah ada, integrasikan pekerjaan yang telah selesai di branch terpisah, dan jangan membangun ulang atau mengklaim siap sebelum bukti E2E dan acceptance lengkap.
