# Laporan Koordinator — Audit GitHub dan Backlog Lanjutan
Tanggal: 26 November 2026 (WIB)
Repository: `zane0184/cbt_november`
Branch audit: `audit/coordinator-continuation-20261126`
Basis source terbaru yang diperiksa: `work/guru-docx-import-e2e-20261125`
Exact source HEAD: `b451989b1ca2ac39b4ea16746914f6d28d1169a8`

## Mandat dan batas audit
- Sumber pemeriksaan hanya GitHub. Tidak membuka ZIP baseline, ChatGPT Library, Project Files, VPS, atau database produksi.
- Tidak mengubah source aplikasi, migration, workflow, konfigurasi, database, PR, atau branch implementer.
- Tujuan: mengidentifikasi posisi source terbaru dan pekerjaan yang benar-benar belum selesai, tanpa mengulang patch yang sudah lulus.
- Keputusan owner terbaru pada sesi 26 November menjadi instruksi operasional: Android native dan Web Super Admin ditunda sampai Juni 2027; satu ZIP generik per madrasah dengan instalasi/database/storage masing-masing; tidak ada shared academic database atau akses antar-madrasah; layanan pusat hanya lisensi/pembayaran; role aktif hanya ADMIN_MADRASAH, GURU, SISWA; Operator dan Kamad tidak menjadi role/portal aktif; Proktor adalah menu/capability tambahan pada Web Guru; Kurikulum Merdeka + KBC satu kesatuan.

## AUDIT

### 1. Repository dan branch
- Repository ditemukan: `zane0184/cbt_november`.
- Default branch `main` pada snapshot audit: `aa5a5d39e41ccfbd777cb0766d2a6f83e41801dc`, tree hanya 43 entri dan tidak memuat keseluruhan source aktif yang ditemukan pada branch kerja.
- Branch source terbaru dengan implementasi Web Guru/DOCX dan workflow paket: `work/guru-docx-import-e2e-20261125` @ `b451989b1ca2ac39b4ea16746914f6d28d1169a8` (commit 25 Nov 2026 12:58 UTC).
- Branch ini mempunyai 1.873 entri tree, termasuk source Laravel, migration, test, workflow, dokumentasi, dan backup.
- Branch audit ini dibuat dari HEAD tersebut agar catatan pemeriksaan tidak mengubah branch implementer.
- Tidak ada merge otomatis. Jangan menganggap `main` atau PR lama sebagai source lengkap tanpa rekonsiliasi diff dan approval.

### 2. Bukti CI dan paket terbaru
**DOCX Import Parser CI**
- Run #322 / ID `45618940586`
- Exact SHA: `b451989b1ca2ac39b4ea16746914f6d28d1169a8`
- PHP 8.3 + MySQL 8.0/InnoDB disposable: SUCCESS.
- PHP 8.4 + MySQL 8.0/InnoDB disposable: SUCCESS.
- Pada kedua job: Composer validation/locked dependencies, database identity, PHP lint, clean-install Installer E2E, Student Import acceptance, benchmark denial-path, dan full PHPUnit step berstatus SUCCESS.
- Run URL: https://github.com/zane0184/cbt_november/actions/runs/45618940586
- Angka hitungan test dari checkpoint sebelumnya tidak disalin sebagai angka exact SHA ini; status yang dapat dipastikan pada run #322 adalah seluruh step full PHPUnit SUCCESS.

**Build Standalone Web CBT Deployment ZIP**
- Run #516 / ID `45618940537`
- Exact SHA sama: `b451989b1ca2ac39b4ea16746914f6d28d1169a8`
- Workflow: SUCCESS.
- Artifact: `cbt-madrasah-web-deployment-b451989b1ca2ac39b4ea16746914f6d28d1169a8`
- Artifact ID: `15022873696`
- Ukuran: 19.777.163 byte; belum expired saat diperiksa.
- Run URL: https://github.com/zane0184/cbt_november/actions/runs/45618940537
- Ini membuktikan workflow menghasilkan artifact. Belum membuktikan paket final diterima: audit independen isi bytes hasil download, ekstraksi bersih, browser-to-real-Laravel E2E, UAT, dan staging belum dibuktikan pada exact SHA ini.

### 3. Fitur yang sudah ada pada source terbaru (tidak boleh dibuat ulang)
- Installer dan konfigurasi instalasi mandiri; `TenantAcademicBootstrapper` tersedia untuk prasyarat akademik instalasi baru.
- Admin: dashboard, profil madrasah, branding assets, manajemen akun manual, akademik/rombel, registrasi kurikulum, assignment mapel Guru, Import Siswa.
- Import Siswa: `StudentImport`, `StudentImportService`, template XLSX dan CSV, preview, validasi NISN teks 10 digit, batch/idempotency, audit, transaksi/rollback, XLSX preflight `XlsxPackagePreflight`, read filter, dan test preflight tersedia.
- Guru: dashboard, penugasan, pembelajaran/kurikulum, materi, profil, Bank Soal, asesmen/Exam Builder, hasil/analytics, media soal.
- Import soal Guru DOCX: route `/guru/bank-soal/import-docx`, halaman/template DOCX, parser, preview/koreksi, penyimpanan draft, mapping kurikulum, batch/idempotency/audit, dan test tersedia pada branch terbaru.
- Import DOCX report menyatakan PG, PGK, B/S, TRUE_FALSE_GROUP (tepat tiga pernyataan), ESSAY manual review; MATCHING dan SHORT_ANSWER tetap disabled/blocked.
- Role bootstrap hanya membuat tiga role operasional ADMIN_MADRASAH/GURU/SISWA. Permission role legacy OPERATOR/KAMAD/PROKTOR/SUPER_ADMIN dilepas. Route aktif yang diperiksa hanya portal Admin, Guru, dan Siswa.
- Source lama `app/Livewire/Kamad` dan `app/Livewire/Operator` masih ada sebagai file legacy, tetapi tidak ditemukan route portal aktif untuk keduanya pada `routes/web.php` dan `routes/teacher.php`. Migration legacy-role mempertahankan row role/assignment historis, mencatat audit, melepas grant, dan mencabut sesi/token; tidak melakukan hard-delete histori atau pemetaan akun otomatis.
- Jangan mengulang pembuatan Student Import, DOCX importer, Installer Academic Bootstrap, profil dasar, branding yang sudah ada, atau hardening Student XLSX yang sudah diintegrasikan ke source terbaru.

### 4. Gap terverifikasi pada source terbaru
1. **Import Guru massal belum ada.** Inventory source terbaru menemukan `StudentImport.php` dan route `/admin/students/import`, tetapi tidak menemukan komponen/service/view/route `TeacherImport` atau halaman `/admin/teachers/import`. `UserManagement` menyediakan administrasi akun Guru, tetapi bukan bukti import massal/template Guru. Ini menjawab pemeriksaan menu import: template/import Siswa ada; menu/template import Guru belum ditemukan pada branch source terbaru.
2. **Proktor di Web Guru belum tersedia sebagai alur lengkap.** Route yang ditemukan adalah `/admin/proctor-assignments`; `routes/teacher.php` belum mempunyai route/menu Proktor. Service/assignment sebelumnya dilaporkan fail-closed karena marker eligibility ujian pusat/aktor penetap belum kanonis. Jangan bypass guard atau menambahkan menu yang memberi akses tanpa assignment dan room-scope.
3. **CP resmi tetap BLOCKED.** CP source Pendis 9941 masih PENDING; belum ada artefak resmi byte-level yang provenance dan SHA-256-nya terverifikasi. Tidak boleh membuat CP sintetis atau mengubah status menjadi verified.
4. **Pembelajaran Mendalam dan delapan dimensi profil lulusan belum mempunyai reference dataset resmi terverifikasi.** Issue #15 tetap OPEN.
5. **QuestionVersion → curriculum mapping → immutable Exam Snapshot belum diterima end-to-end.** Issue #14 tetap OPEN; perlu pastikan mapping versi soal yang dipilih benar-benar disalin ke snapshot immutable dan dipakai laporan/histori.
6. **Web Siswa browser-to-real-Laravel E2E belum terbukti.** Runner browser yang disebut pada audit sebelumnya menggunakan mock API; test backend terpisah bukan pengganti browser E2E terhadap Laravel/MySQL disposable. Branch PR #13/runner lama tidak boleh digabung wholesale karena membawa ExamSession/RoomSession/session lifecycle yang bertentangan dengan model ujian serentak.
7. **Web Admin belum lengkap seluruh ADM-01..23.** Source yang ada tidak membuktikan seluruh lifecycle Guru/Siswa, import Guru, export administratif, histori akademik/pindah/promosi/kelulusan, laporan/kop cetak, halaman audit lengkap, backup/restore dengan restore test, dan acceptance UI/responsif. Issue #20 tetap OPEN.
8. **Validasi DOCX riil madrasah belum ada.** Parser belum membuktikan gambar/diagram/rumus Word; tabel dibaca sebagai teks urut; automatic numbering Word tidak didukung. Perlu fixture/dokumen contoh yang diotorisasi untuk acceptance, bukan membuat ulang parser.
9. **Device Lease/fencing belum production-ready** sampai integrasi multi-device, concurrency, retry/recovery dan end-to-end MySQL tests lulus.
10. **ZIP final belum siap distribusi.** Artifact workflow tersedia pada exact SHA, tetapi belum ada bukti independent extraction + complete acceptance + browser E2E + staging/UAT pada paket yang sama.

### 5. Issue aktif yang relevan
- Issue #9 DOCX import: OPEN. Implementasi DOCX telah berkembang pada branch terbaru; issue perlu direkonsiliasi/ditutup hanya setelah acceptance sesuai addendum terbaru, bukan dengan mengulang parser.
- Issue #12 master scope Web Admin: OPEN.
- Issue #14 curriculum mappings dan immutable snapshot: OPEN.
- Issue #15 provenance Pembelajaran Mendalam/delapan dimensi: OPEN.
- Issue #20 eksekusi ADM-01..23: OPEN.
- PR lama #21/#23/#24 dan branch audit/implementer tidak boleh merge otomatis; cek base/head/diff/CI sebelum integrasi. PR #24 pernah ditandai SUPERSEDED — DO NOT MERGE.

## DESAIN — urutan lanjutan yang disarankan
1. **P0 — Import Guru Admin.** Audit terlebih dahulu kontrak identitas login dan akun Guru pada source terbaru, lalu rancang importer administrasi terpisah dari importer soal DOCX. XLSX/CSV diperbolehkan untuk administrasi. Harus ada template, preview, row-level errors, exact-role/permission, validasi NIP/NIK sesuai aturan identitas aktif, duplikasi, transaksi/batch, idempotency, credential onboarding aman, audit, dan test disposable MySQL. Tidak boleh menyalin importer Siswa lalu mengganti label tanpa menyesuaikan kontrak Guru.
2. **P0 — Web Siswa browser-to-real-Laravel E2E.** Integrasikan runner secara selektif tanpa ExamSession/SessionRoom; buktikan jadwal/token/server-time, payload redaction, autosave/receipt/idempotency, refresh/reconnect, submit barrier, recovery, dan akses media.
3. **P0 — Tutup kurikulum terverifikasi.** Dapatkan artefak resmi CP/PM/delapan dimensi melalui GitHub atau sumber resmi yang dapat direferensikan dan simpan provenance/checksum; tanpa artefak, tetap BLOCKED dan jangan buat konten fiktif. Selesaikan kontrak mapping QuestionVersion → snapshot immutable.
4. **P1 — Proktor Web Guru.** Setelah keputusan marker eligibility ujian pusat dan kewenangan penetapan tersedia, buat menu Guru berbasis assignment aktif, room scope, monitoring-only default, audit dan negative authorization. Admin tetap pengelola penetapan/revoke bila sesuai keputusan owner.
5. **P1 — Selesaikan ADM-01..23 dan portal Guru.** Kerjakan gap aktual per item; jangan mengulang profil, branding, create account, atau audit event yang sudah lulus. Prioritaskan histori akademik, export/import administratif, reports/print, backup/restore, visual/responsive/accessibility, dan acceptance UI.
6. **P0 Release — ZIP acceptance.** Ambil artifact exact SHA, verifikasi file list/manifest/SHA-256/secret exclusion, ekstrak ke folder kosong, jalankan smoke dan browser E2E pada environment disposable/staging, lalu UAT. Baru setelah semua gate lulus, siapkan satu ZIP final untuk upload/extract VPS.

## IMPLEMENTASI
- Tidak ada source aplikasi, migration, workflow, config, database, VPS, atau PR yang diubah.
- Branch audit dibuat: `audit/coordinator-continuation-20261126`, berbasis source HEAD `b451989b1ca2ac39b4ea16746914f6d28d1169a8`.
- Laporan ini adalah catatan audit; tidak membuat ulang patch fitur.

## PENGUJIAN
- Pemeriksaan GitHub repository, tree recursive, branch HEAD, file route/service/migration/test, issue, Actions run, jobs, dan artifact metadata.
- DOCX CI #322 pada exact SHA b451: kedua matriks PHP 8.3 dan 8.4 SUCCESS, termasuk clean-install installer E2E, Student Import acceptance, denial-path dan full PHPUnit step.
- Packaging CI #516 pada exact SHA b451: SUCCESS; artifact ID 15022873696, 19.777.163 byte.
- Tidak menjalankan PHPUnit lokal, browser E2E, download/extract artifact, staging, VPS, migration, atau UAT dalam audit ini.

## CHECKPOINT
- Source terbaru yang diperiksa: `work/guru-docx-import-e2e-20261125` @ `b451989b1ca2ac39b4ea16746914f6d28d1169a8`.
- Student Import security, Installer Academic Bootstrap, dan DOCX import tidak boleh diulang.
- Import Guru belum ditemukan dan menjadi kandidat pekerjaan berikutnya.
- CP resmi/PM/8 dimensi, mapping snapshot, Web Siswa real-Laravel E2E, Proktor Guru, acceptance Web Admin, serta ZIP final masih terbuka.
- Status: **AUDIT GITHUB SELESAI — SOURCE WEB MASIH IN PROGRESS — ARTIFACT ZIP ADA, TETAPI BELUM FINAL/READY — NOT PRODUCTION READY**.
