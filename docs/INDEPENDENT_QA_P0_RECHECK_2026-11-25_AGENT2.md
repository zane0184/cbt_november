# LAPORAN AUDIT INDEPENDEN P0 — RECHECK AGENT 2

Tanggal: 25 November 2026  
Repository: zane0184/cbt_november  
Tujuan: verifikasi ulang kelayakan ZIP Web untuk uji operasional terbatas  
Metode: read-only GitHub inspection, source/diff/tree review, pembacaan CI log, dan pemeriksaan sumber dokumen publik.  
Status keputusan: **BELUM SIAP — P0 RELEASE GATES MASIH TERBUKA**

## 1. IDENTITAS AUDIT

| Item | Branch | HEAD aktual |
|---|---|---|
| Agent 2 | audit/independent-qa-admin-guru-siswa-20261125 | 5f2fa4b24e1fe0e1c7ee0a01584c60ceeccc3bc3 |
| Agent 1 — ZIP P0 | work/limited-zip-p0-20261125 | ef8350814258354e1efe03bfcccd81f580ab2008 |
| Agent 1 — provisioning akademik | work/p0-academic-provisioning-20261125 | ba136c2c2eeff377f621b240e54f3a6a2ff8dd7e |

Merge-base dua branch Agent 1: a65b4604495e8bcc153bf0e09df12a9efb11543a.  
Perbandingan branch ZIP P0 terhadap branch provisioning: **DIVERGED** — provisioning 106 commit di depan dan ZIP P0 25 commit di depan dari merge-base. Tidak ditemukan PR yang menggabungkan kedua branch tersebut.

Run #109: https://github.com/zane0184/cbt_november/actions/runs/45507930036  
Run provisioning P0: https://github.com/zane0184/cbt_november/actions/runs/45498672360

## 2. AUDIT

### 2.1 Run #109 — FAIL terkonfirmasi

Exact SHA yang diuji: c3b3b99dd02996f0412455f3bbfe8d8b1e8cfe35.

Kedua job selesai dengan conclusion failure:

| Job | PHPUnit | Assertions | Kesimpulan |
|---|---:|---:|---|
| PHP 8.3 / MySQL 8.0 InnoDB | 624 passed, 3 failed, 1 skipped | 3.113 | FAIL |
| PHP 8.4 / MySQL 8.0 InnoDB | 624 passed, 3 failed, 1 skipped | 3.113 | FAIL |

Tiga test yang gagal:
1. test_admin_creation_assigns_only_admin_madrasah_role
2. test_admin_creation_recovers_a_committed_pair_when_state_checkpoint_was_lost
3. test_legacy_tenant_created_checkpoint_reconciles_exact_committed_admin_pair

Pesan ketiganya: System Tenant referensi tidak ditemukan atau identitasnya tidak valid. Instalasi dihentikan.

Langkah yang berhasil pada kedua job:
- Checkout exact pushed commit.
- Setup PHP dan verifikasi MySQL disposable.
- Composer lock/dependency validation.
- PHP lint.
- Clean-install Installer E2E pada database disposable terpisah.
- Student Import acceptance.
- Benchmark cleanup denial-path.

Clean-install E2E PASS tidak membatalkan kegagalan full PHPUnit.

### 2.2 Perbaikan Installer setelah Run #109

Commit a68dd45079216ad9fef91622f58bf0104eda7729 menambahkan pemanggilan SystemTenantSeeder::seed() pada tiga test fixture yang gagal.

HEAD branch Agent 1 saat ini adalah ef8350814258354e1efe03bfcccd81f580ab2008. Commit terbaru tersebut hanya menambahkan backup sebelum perbaikan lanjutan:
- InstallerService.php.before_academic_registry_diagnostic_fix_20261125
- InstallerTest.php.before_academic_registry_fixture_fix_20261125
- BACKUP_MANIFEST_ACADEMIC_REGISTRY_DIAGNOSTIC_20261125.md

Manifest menyatakan Git blob SHA sumber dan backup identik untuk kedua file, beserta ukuran dan SHA-256. Bukti manifest tersedia; Agent 2 belum mengunduh ulang blob untuk menghitung ulang SHA secara lokal.

Tidak ada combined status/check run pada HEAD ef835081. Fetch commit workflow runs juga tidak menemukan workflow run untuk exact HEAD tersebut. Karena itu tiga test belum dinyatakan diperbaiki. Patch fixture ada, tetapi hasil full suite PHP 8.3 dan PHP 8.4 pada exact HEAD belum tersedia.

### 2.3 P0-01 — provisioning akademik minimum

Temuan lama P0-01 pada SHA a65b460 bahwa setup akademik minimum belum tersedia sudah SUPERSEDED pada branch ZIP P0.

Bukti source pada branch ZIP P0:
- InstallerService memanggil TenantAcademicBootstrapper dalam transaksi pembuatan Tenant/Admin.
- TenantAcademicBootstrapper memvalidasi tenant context, versi registry KMA 1503/2025 pada System Tenant, fase A/B/C, dan tingkat MI 1–6.
- Bootstrap membuat data lokal tenant: Kurikulum Merdeka, tiga fase, enam tingkat kelas, satu tahun pelajaran berjalan, semester Ganjil/Genap, serta satu semester aktif berdasarkan bulan.
- Bootstrap tidak membuat konten CP/TP fiktif.

InstallerEndToEndTest memeriksa jumlah tahun pelajaran, semester, semester aktif, fase, dan tingkat kelas. Tiga file utama berikut memiliki Git blob SHA identik antara SHA Run #109 dan HEAD ef835081:
- InstallerService.php
- TenantAcademicBootstrapper.php
- InstallerEndToEndTest.php

Run #109 melaporkan clean-install E2E PASS. Maka provisioning minimum untuk pilot **PASS** pada source yang tidak berubah sejak SHA yang diuji.

Branch provisioning terpisah menambahkan pengelolaan tahun pelajaran/semester melalui UI dan service yang lebih luas. Run 45498672360 pada SHA 61de22182c9804df4d8749c901069a11046024dc membuktikan 190 passed (935 assertions) pada PHP 8.3 / MySQL 8.0.46 InnoDB. HEAD branch tersebut ba136c2c2eeff377f621b240e54f3a6a2ff8dd7e hanya satu commit sesudah SHA CI dan menambahkan dokumen/manifest, bukan perubahan source aplikasi.

Batas bukti: 190 test adalah suite P0 akademik terpilih, bukan full PHPUnit lintas PHP 8.3/8.4. Implementasi pengelolaan kalender akademik lanjutan belum terintegrasi ke branch ZIP P0.

### 2.4 System Tenant

Source yang diperiksa:
- database/seeders/DatabaseSeeder.php
- database/seeders/SystemTenantSeeder.php
- InstallerService.php

DatabaseSeeder menjalankan SystemTenantSeeder, bootstrap role ADMIN_MADRASAH/GURU/SISWA, permission, serta registry referensi kurikulum/taksonomi. Tidak ditemukan pembuatan demo madrasah atau demo user pada DatabaseSeeder.

SystemTenantSeeder menggunakan withTrashed untuk UUID dan code yang dicadangkan; soft-deleted reserved identity dan konflik UUID/code menghentikan proses. DatabaseSeeder dan SystemTenantSeeder identik pada branch ZIP P0 dan branch provisioning.

Clean-install E2E pada Run #109 PASS. Tiga test fixture yang gagal pada Run #109 memanggil seeder secara eksplisit dalam patch a68dd, tetapi belum diuji ulang pada exact HEAD.

Status: **PASS untuk desain/source bootstrap yang diperiksa; regression gate tiga test Installer tetap NOT VERIFIED.**

### 2.5 P0-02 — CP resmi dan provenance

Source aktual yang diperiksa:
- CurriculumReferenceSourceSeeder.php
- CurriculumRegistryBootstrapSeeder.php
- TeacherLearningOutcomeService.php
- QuestionBank.php
- ExamManagement.php

Status registry:
- cp_content_imported = false.
- artifact_checksum_verified = false.
- Kepdirjen Pendis 9941/2025 masih PENDING.
- BSKAP 046/H/KR/2025 berstatus METADATA_VERIFIED, tetapi artifact checksum belum verified.
- BKPDM 020/2026 berstatus METADATA_VERIFIED berdasarkan siaran pers, bukan naskah keputusan lengkap; checksum belum verified.

Sumber publik yang diperiksa:
1. BSKAP 046/H/KR/2025: PDF tersedia pada domain resmi Kemendikdasmen, URL seeder cocok dengan PDF yang diperiksa. Dokumen menetapkan CP SD/MI dan fase A kelas I–II, fase B kelas III–IV, fase C kelas V–VI. Bytes PDF tidak berhasil diunduh ke workspace auditor; SHA-256 belum dihitung.
2. BKPDM 020/2026: siaran pers resmi 23 Juni 2026 mengonfirmasi perubahan terbatas pada Agama dan Budi Pekerti. Lampiran unduhan pada halaman tersebut adalah PDF siaran pers dua halaman, bukan naskah keputusan lengkap beserta lampirannya.
3. Kepdirjen Pendis 9941/2025: seeder masih PENDING dan source URI saat ini menunjuk pemberitahuan Kemenag, bukan PDF keputusan. Salinan lengkap PDF ditemukan pada situs madrasah pihak ketiga; dokumen memuat surat pengantar yang menyatakan tanda tangan elektronik BSrE, tetapi signature certificate belum diverifikasi secara kriptografis dan bytes tidak berhasil diunduh. Salinan pihak ketiga tidak dipakai sebagai bukti final provenance.

Gate aplikasi tetap bekerja fail-closed:
- TeacherLearningOutcomeService menolak pembuatan TP bila CP induk tidak memiliki sumber ARTIFACT_CHECKSUM_VERIFIED dan checksum sumber/CP yang cocok.
- QuestionBank mewajibkan pemetaan CP dan TP/ATP sah sebelum publikasi.
- ExamManagement mewajibkan pemetaan CP/outcome untuk asesmen/ujian.

Status: **BLOCKED**. Jangan membuat CP contoh, jangan mengubah status PENDING/false menjadi verified, dan jangan melewati checksum.

### 2.6 P0-08 — Packaging ZIP

Exact branch ZIP P0 HEAD: ef8350814258354e1efe03bfcccd81f580ab2008.

Tree GitHub pada exact HEAD:
- 1.350 file.
- 49 file di backend-laravel/backups/.
- Tiga direktori backup: benchmark-cleanup-proof-20261125, limited-zip-p0-20261125, student-import-hardening-20261125.
- Tidak ditemukan .env aktif selain .env.example.
- Tidak ditemukan database dump berekstensi .sql/.dump/.sqlite/.db pada tree yang diperiksa.
- Tidak ditemukan private key berekstensi .pem/.key/.p12/.pfx pada tree yang diperiksa.

Workflow branch ZIP P0:
- rsync mengecualikan .env dan backup_before_patch/.
- rsync TIDAK mengecualikan backups/.
- Archive assertion TIDAK memeriksa direktori backups/.
- Dengan source tree berisi 49 file backend-laravel/backups/, workflow pada branch ini berpotensi memasukkan backup internal ke ZIP. Ini blocker P0 yang terkonfirmasi dari kombinasi tree dan aturan rsync/guard.

Workflow pada branch provisioning menambahkan exclude backups/ dan assertion archive untuk path backups/. Namun branch tersebut belum menjadi branch ZIP kandidat final.

Kekurangan yang masih ada bahkan pada workflow branch provisioning:
- Guard hanya memeriksa .env persis; belum menolak .env.* secara rekursif dengan allowlist eksplisit untuk .env.example.
- Belum ada guard generik untuk database dump dan private key/credential files.
- Workflow membuat SHA256SUMS dan menjalankan unzip -t, tetapi belum mengekstrak ke direktori bersih lalu memverifikasi checksum setiap file hasil ekstraksi terhadap manifest.
- Tidak ada artifact ZIP yang dapat diaudit dari exact HEAD branch ZIP P0.

Status packaging: **FAIL P0** pada branch ZIP P0 saat ini.  
Status artifact/extraction: **NOT VERIFIED**.

## 3. DESAIN / REKOMENDASI KEPADA AGENT 1

1. Pertahankan satu branch kandidat ZIP yang otoritatif. Rekonsiliasi branch ZIP P0 dan branch provisioning secara selektif; jangan merge seluruh branch tanpa audit diff karena keduanya divergen 106/25 commit dari merge-base.
2. Pertahankan provisioning minimum yang sudah terbukti pada TenantAcademicBootstrapper. Jika pengelolaan tahun pelajaran lanjutan diambil dari branch provisioning, satukan kontrak/service/UI tanpa duplikasi bootstrapper dan tanpa mengubah semantik semester aktif secara diam-diam.
3. Setelah patch fixture Installer, jalankan full PHPUnit PHP 8.3 dan PHP 8.4 pada exact HEAD final. Pastikan tiga test sebelumnya lulus dan semua test tetap dieksekusi; jangan skip atau mengurangi assertion.
4. Pertahankan SystemTenantSeeder fail-closed. Jangan melemahkan validasi registry atau identitas System Tenant.
5. Peroleh naskah asli resmi Kepdirjen Pendis 9941/2025 beserta lampiran, dan keputusan lengkap BKPDM 020/2026 bila berlaku untuk MI. Arsipkan bytes, verifikasi sumber/tanda tangan, hitung SHA-256, cocokkan cakupan mapel/fase/kelas, lalu baru buat importer CP idempotent.
6. Perkuat workflow ZIP: exclude seluruh backups/ dan backup_before_patch/ secara rekursif; allowlist .env.example; tolak .env aktif/variasi, dump DB, credential/private key, storage privat, log/cache/session/temp/test/debug.
7. Tambahkan archive guard untuk seluruh kategori terlarang. Ekstrak ZIP ke direktori kosong, jalankan sha256sum -c terhadap manifest, verifikasi jumlah/struktur file, README, konfigurasi, dan tidak ada backup.
8. Jalankan workflow packaging pada exact commit final; simpan artifact, SHA-256 ZIP, contents list, manifest, guard output, hasil unzip -t, hasil ekstraksi, dan checksum verification.
9. Browser E2E pada hasil ekstraksi tetap wajib: Admin setup → Guru/penugasan → Rombel/Siswa → CP/TP/soal/ujian/token → login Siswa → jadwal → jawaban → refresh/reconnect → submit → skor. Tambahkan negative role/tenant tests.

## 4. IMPLEMENTASI AGENT 2

**Tidak ada source implementasi yang diubah oleh Agent 2.**

Tidak ada migration, database, branch Agent 1, workflow Agent 1, PR, VPS, atau deployment yang diubah. Hanya dokumen audit dan checkpoint pada branch audit independen yang akan ditambahkan.

## 5. PENGUJIAN YANG BENAR-BENAR DIPERIKSA

Agent 2 tidak menjalankan PHPUnit, Gradle, migration, atau browser test lokal.

Bukti CI Agent 1 yang dibaca:
- Run #109, SHA c3b3b99: PHP 8.3 dan PHP 8.4; masing-masing 624 passed, 3 failed, 1 skipped, 3.113 assertions.
- Run #109: lint, Composer/dependency, MySQL disposable, clean-install E2E, Student Import acceptance, benchmark denial-path berhasil.
- Run #45498672360, SHA 61de221: PHP 8.3/MySQL 8.0.46; 190 passed, 935 assertions untuk P0 academic suite.
- Exact HEAD ef835081: tidak ada combined status/check run yang ditemukan.

## 6. STATUS ACCEPTANCE GATE

| Gate | Status | Bukti / Risiko | Tindakan |
|---|---|---|---|
| Full PHPUnit PHP 8.3/8.4 | FAIL | Run #109 tiga InstallerTest gagal; patch fixture belum diuji pada exact HEAD | Jalankan ulang full suite |
| MySQL 8/InnoDB disposable | PASS pada CI | Run #109 kedua job dan Run #454986 | Pertahankan database disposable |
| Clean-install E2E | PASS | Run #109; test mengassert tahun, semester, fase, grade MI | Ulangi pada exact final ZIP |
| P0-01 provisioning minimum | PASS | Source identik dengan SHA Run #109; E2E memeriksa 1 tahun, 2 semester, 3 fase, 6 grade | Pertahankan |
| System Tenant | PASS dengan regression gate terbuka | Seeder fail-closed dan clean-install E2E; tiga test fixture belum re-run | Verifikasi full suite |
| Student Import | PASS pada step CI | Run #109 step acceptance berhasil | Smoke test browser dari ZIP |
| CP resmi/provenance | BLOCKED | 9941 pending; 046 checksum false; 020 keputusan asli belum tersedia | Peroleh dan verifikasi artefak |
| Tenant/RBAC security | NOT VERIFIED sebagai acceptance final | Feature test tersedia, tetapi full suite gagal dan browser negative E2E belum dilakukan | Uji negatif dari ZIP |
| ZIP backup exclusion | FAIL P0 | 49 file backup di source dan rsync branch ZIP tidak mengecualikan backups/ | Patch oleh Agent 1 |
| .env/secrets/dump/private key guard | NOT VERIFIED | Guard belum generik; tidak ditemukan file aktif pada tree saat ini | Tambahkan deny rules |
| ZIP artifact exact HEAD | NOT VERIFIED | Tidak ada build/artifact exact HEAD | Build workflow |
| Extraction + full checksum | NOT VERIFIED | unzip -t saja; tidak ada full extract/hash verification | Ekstrak dan verifikasi manifest |
| Browser E2E | NOT VERIFIED | Belum ada bukti end-to-end browser | Jalankan pada hasil ekstraksi |
| Android native / Device Lease | DEFERRED | Android ditunda sampai Juni 2027; fencing tidak disertifikasi | Backlog sesuai keputusan owner |

## 7. KESIMPULAN

**BELUM SIAP untuk dinyatakan ZIP SIAP UJI OPERASIONAL TERBATAS.**

P0-01 provisioning minimum telah ditutup dengan bukti source dan clean-install E2E. System Tenant juga memiliki fondasi seeder yang fail-closed.

Blocker yang masih menghalangi:
1. Full PHPUnit masih FAIL pada Run #109; perbaikan tiga fixture belum diverifikasi pada exact HEAD.
2. CP resmi belum memiliki bytes/checksum/provenance lengkap dan konten belum diimpor.
3. Workflow branch ZIP P0 berpotensi memasukkan 49 file backup internal.
4. Workflow ZIP pada exact HEAD belum menghasilkan artifact.
5. Ekstraksi penuh/checksum dan browser E2E belum dibuktikan.

Jangan menyatakan siap produksi, siap seluruh madrasah, siap Android Studio, atau Device Lease/fencing siap produksi.

## 8. CHECKPOINT BERIKUTNYA

- Branch audit sebelum penambahan dokumen ini: audit/independent-qa-admin-guru-siswa-20261125.
- HEAD audit sebelum penambahan dokumen ini: 5f2fa4b24e1fe0e1c7ee0a01584c60ceeccc3bc3.
- HEAD Agent 1 ZIP P0: ef8350814258354e1efe03bfcccd81f580ab2008.
- HEAD Agent 1 provisioning: ba136c2c2eeff377f621b240e54f3a6a2ff8dd7e.
- File utama diperiksa: InstallerService.php, TenantAcademicBootstrapper.php, InstallerEndToEndTest.php, InstallerTest.php, SystemTenantSeeder.php, DatabaseSeeder.php, CurriculumReferenceSourceSeeder.php, CurriculumRegistryBootstrapSeeder.php, TeacherLearningOutcomeService.php, QuestionBank.php, ExamManagement.php, build-web-deployment.yml, DISTRIBUTION_EXCLUSIONS.md.
- Temuan terbuka: Installer full suite, CP artifact, branch divergence, ZIP backup/security guards, exact artifact, extraction checksum, browser E2E.
- Tugas berikutnya: tunggu commit/CI baru Agent 1; audit ulang exact SHA dan artifact. Jangan mengulang test atau patch yang sudah terbukti.


## 9. SUMBER PUBLIK CP YANG DIPERIKSA

- PDF resmi BSKAP 046/H/KR/2025: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
- Siaran pers resmi BKPDM 020/2026: https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti
- Lampiran unduhan pada halaman tersebut (PDF siaran pers, bukan keputusan lengkap): https://www.kemendikdasmen.go.id/download/file/1933
- Salinan PDF Kepdirjen Pendis 9941/2025 pada situs pihak ketiga: https://min2sbb.sch.id/wp-content/uploads/2025/12/SK-Dirjen-Pendis-9941-2025-CP-PAI-dan-Bahasa-Arab.pdf
- Halaman rujukan Pendis yang dicatat dalam audit sebelumnya: https://pendis.kemenag.go.id/read/keputusan-direktur-jenderal-pendidikan-islam-nomor-9941-tahun-2025-tentang-capaian-pembelajaran-mata-pelajaran-pendidikan-agama-islam-dan-bahasa-arab-pada-madrasah

Catatan: PDF BSKAP 046 dapat dibaca dari domain resmi dan cakupan SD/MI serta fase A/B/C terkonfirmasi. Bytes PDF belum tersedia untuk penghitungan SHA-256 independen. PDF Kepdirjen Pendis 9941 yang terbaca berasal dari mirror pihak ketiga; pernyataan tanda tangan elektronik BSrE di dalam dokumen belum diverifikasi secara kriptografis. PDF unduhan BKPDM 020 yang diperiksa adalah siaran pers dua halaman, bukan naskah keputusan. Status CP tetap BLOCKED.


## 10. CATATAN LINGKUNGAN CI P0 AKADEMIK

Log Run 45498672360 memperlihatkan environment test menggunakan CBT_LICENSE_TEST_BYPASS=true. Karena itu, hasil 190 passed membuktikan suite provisioning/installer yang dijalankan pada lingkungan CI disposable, tetapi **bukan** bukti bahwa aktivasi lisensi Publisher/Tenant tanpa bypass telah berhasil pada runtime. Jalur lisensi tetap memerlukan test tersendiri pada environment staging yang sah.


---

# ADDENDUM — INDEPENDENT QA RECONCILIATION UPDATE
Tanggal audit: 25 November 2026 (WIB)
Status: READ-ONLY — tidak ada perubahan source Agent 1, merge, migration, database produksi, atau deployment.

## A. Identitas branch dan exact SHA yang diverifikasi

| Area | Branch / ref | Exact HEAD / commit | Hasil |
|---|---|---|---|
| Agent 1 — kandidat ZIP P0 | `work/limited-zip-p0-20261125` | `52f34ee2be7d346c1e1eaba276285badc75dac7f` | HEAD terkonfirmasi |
| Agent 1 — provisioning akademik lanjutan | `work/p0-academic-provisioning-20261125` | `ba136c2c2eeff377f621b240e54f3a6a2ff8dd7e` | HEAD terkonfirmasi |
| Agent 2 — audit independen sebelum update ini | `audit/independent-qa-admin-guru-siswa-20261125` | `46ead67f8886f87271d34a4b4191ac8810aa8bbb` | HEAD terkonfirmasi |
| P0-01 Installer/System Tenant | commit | `150b5ffec865230038aae37e0c9a1c94f151ed8d` | Ancestor branch ZIP P0; diuji Run #114 |

Perbandingan commit membuktikan branch ZIP P0 berada tepat satu commit setelah `150b5ff`; satu-satunya file pada commit tambahan itu adalah `docs/P0_02_AUDIT_AKADEMIK_CP_PROVENANCE_2026-11-25.md`. Dengan demikian tidak ada perubahan source aplikasi setelah commit P0-01 yang diuji Run #114 pada HEAD ZIP P0 saat ini.

Branch Agent 1 untuk provisioning akademik lanjutan tetap terpisah dan divergen dari branch ZIP P0. Perbandingan ref terbaru menunjukkan 106 commit hanya pada sisi provisioning dan 27 commit hanya pada sisi ZIP P0 dari merge-base `a65b4604495e8bcc153bf0e09df12a9efb11543a`. Tidak ada merge yang diasumsikan atau dilakukan oleh Agent 2.

## B. AUDIT — Run #114 dan status P0-01

GitHub Actions Run #114:
`https://github.com/zane0184/cbt_november/actions/runs/45521595664`

Dua job yang diperiksa langsung:
- PHP 8.3 / MySQL 8.0 InnoDB — SUCCESS.
- PHP 8.4 / MySQL 8.0 InnoDB — SUCCESS.

Log kedua job menunjukkan checkout exact SHA `150b5ffec865230038aae37e0c9a1c94f151ed8d`, bukan branch tip yang bergerak. Kedua job berhasil pada:
- checkout dan verifikasi exact source/disposable MySQL identity;
- Composer lock/dependency validation;
- PHP lint;
- clean-install Installer E2E pada database disposable terpisah;
- Student Import acceptance;
- benchmark cleanup denial-path;
- complete PHPUnit suite.

Ringkasan full PHPUnit pada masing-masing PHP matrix:
- 628 passed;
- 1 skipped;
- 3.143 assertions;
- tidak ada test failed.

**Keputusan P0-01: PASS / CLOSED.** Installer/System Tenant fixture correction, clean-install E2E, dan full PHPUnit PHP 8.3/8.4 telah memperoleh bukti CI. Run #109 dan tiga Installer test yang gagal di dalamnya adalah hasil historis yang **SUPERSEDED** oleh Run #114. Jangan meminta Agent 1 mengulang P0-01 dan jangan mencatat Run #109 sebagai CI terbaru atau blocker aktif.

Batas: Run #114 membuktikan test di lingkungan CI disposable. Ini bukan bukti browser E2E dengan backend nyata, deployment VPS, aktivasi lisensi Publisher tanpa bypass, atau uji operasional madrasah.

## C. AUDIT — rekonsiliasi source provisioning akademik

Penting membedakan dua lapisan berikut.

### C1. Provisioning minimum P0-01 — SUDAH ADA di branch ZIP P0

Pada branch ZIP P0, `InstallerService` memanggil `TenantAcademicBootstrapper`. Source tersebut menyediakan katalog lokal minimum:
- Kurikulum Merdeka;
- Fase A/B/C;
- tingkat MI 1–6 dengan relasi fase;
- tahun pelajaran berjalan;
- semester Ganjil/Genap;
- satu semester aktif berdasarkan bulan.

Bootstrapper memvalidasi tenant context dan registry fase/kelas pada System Tenant serta tidak membuat konten CP/TP fiktif. File `TenantAcademicBootstrapper.php` ada pada branch ZIP P0 (blob `de00f7c9beeb4fbbd8c31b105c8bf844b78e59d5`). Ini adalah provisioning minimum yang sudah tercakup dalam PASS P0-01.

### C2. Provisioning akademik lanjutan — MASIH DI BRANCH TERPISAH

Branch `work/p0-academic-provisioning-20261125` memiliki implementasi tambahan yang tidak ditemukan pada branch ZIP P0:
- `TenantAcademicBootstrapService.php` untuk katalog enam tahun pelajaran 2026/2027–2031/2032 dan semester Ganjil/Genap yang tidak diaktifkan otomatis;
- `AcademicYearManagementService.php` untuk pembuatan tahun pelajaran dan aktivasi semester oleh Admin;
- perubahan UI `AcademicManagement.php` dan Blade;
- perubahan installer, installation identity, teacher-subject assignment, serta test terkait.

File `TenantAcademicBootstrapService.php` tidak ditemukan pada branch ZIP P0, sedangkan `TenantAcademicBootstrapper.php` yang berbeda tetap ada di branch ZIP P0. Jadi, provisioning minimum P0-01 sudah terintegrasi; **fitur kalender akademik lanjutan dan pengelolaan aktivasi semester dari branch provisioning belum terintegrasi ke kandidat ZIP P0**. Jangan menyebut seluruh implementasi provisioning dari branch terpisah sudah ada di ZIP P0. Rekonsiliasi tetap menjadi keputusan/pekerjaan Agent 1, bukan tindakan Agent 2.

Bukti CI branch provisioning sebelumnya tetap terbatas pada Run `45498672360`, commit `61de22182c9804df4d8749c901069a11046024dc`, PHP 8.3, 190 passed (935 assertions). Itu bukan pengganti Run #114 dan bukan full matrix PHP 8.3/8.4 untuk branch ZIP P0.

## D. AUDIT — P0-02 dan P0-02A

Laporan Agent 1 `docs/P0_02_AUDIT_AKADEMIK_CP_PROVENANCE_2026-11-25.md` pada branch ZIP P0 menyatakan audit source akademik P0-02 selesai. Status ini berarti pemeriksaan source dan identifikasi gap selesai, bukan bahwa penerimaan operasional akademik telah lulus.

**P0-02A Non-CP Academic Acceptance: NOT RUN / NOT VERIFIED.** Tidak ditemukan bukti run acceptance P0-02A. Jangan menganggap source audit, unit/feature tests terpisah, atau Run #114 sebagai pengganti acceptance ini.

### Kriteria audit independen untuk P0-02A

P0-02A dapat dinilai PASS hanya jika seluruh bukti berikut tersedia pada exact SHA kandidat ZIP P0, memakai MySQL 8/InnoDB disposable dan data uji non-produksi:

1. **Setup dan tenant boundary**
   - Installer membuat tepat satu tenant madrasah lokal beserta satu Admin; reserved System Tenant tetap terpisah sebagai registry internal.
   - Katalog lokal yang diperlukan tersedia; tidak ada demo tenant/user atau data lintas tenant.
   - Semua aksi diuji dengan tenant context yang eksplisit dan tenant kedua sebagai negative control.

2. **Alur Admin — backend nyata**
   - Admin membuat Guru dan Siswa; role, permission, profil, identifier, status aktif/nonaktif, dan tenant_id sesuai.
   - Admin membuat Rombel, mapel, serta penempatan Siswa; foreign key/pivot mengarah ke tenant dan Rombel yang benar.
   - Admin mendaftarkan metadata versi kurikulum dan mapping mapel yang memang non-CP, lalu membuat penugasan Guru pada mapel/Rombel/semester yang sah.
   - Tidak ada CP/TP buatan atau perubahan status provenance untuk meloloskan alur.

3. **Alur Guru**
   - Guru hanya melihat/mengelola assignment yang ditugaskan kepadanya dan data tenant sendiri.
   - Pembuatan/akses bank soal non-CP mengikuti policy/permission yang berlaku; jika publikasi mensyaratkan CP/TP resmi, sistem harus menolak secara fail-closed dan menjelaskan prasyaratnya.
   - Tidak ada bypass langsung melalui pemanggilan service/Livewire action.

4. **Alur Siswa**
   - Siswa yang dibuat dapat ditemukan pada Rombel yang benar, autentikasi memakai identifier yang benar, dan tidak dapat melihat akun/rombel/ujian tenant lain.
   - Bila skenario ujian non-CP memang diizinkan oleh desain aplikasi, jalankan sampai Attempt/Answer/Submit/Score; bila policy saat ini tetap mewajibkan CP/TP, catat sebagai blocked-by-policy dan jangan menyiasatinya.

5. **Keamanan, integritas, dan transaksi**
   - Negative test: permission denied, role salah, tenant mismatch, assignment lintas Guru/rombel/semester, akun nonaktif/soft-deleted, fase/kelas tidak cocok, Rombel nonaktif, referensi akademik tidak ada/diarsipkan.
   - Uji duplikasi/idempotensi, dua request bersamaan pada MySQL, unique/composite FK, rollback ketika salah satu tahap gagal, dan tidak ada row parsial.
   - Audit log mencatat actor, tenant, aksi, target, hasil; tidak memuat password, token rahasia, atau payload sensitif.
   - Pemeriksaan langsung database disposable memastikan seluruh row terkait konsisten dan tidak ada orphan/cross-tenant row.

6. **CP fail-closed tetap terjaga**
   - Kepdirjen Pendis 9941/2025 tetap `PENDING`; tidak ada CP resmi selectable/imported dan tidak ada checksum yang dipalsukan.
   - Pembuatan TP/ATP, publikasi TP/ATP, dan publikasi soal yang membutuhkan CP resmi ditolak tanpa membuat row parsial.
   - Fixture CP sintetis yang dipakai oleh test suite tidak boleh dihitung sebagai bukti CP resmi atau sebagai kelulusan P0-02A.

7. **Kualitas bukti**
   - Sertakan exact SHA, workflow/run URL, PHP/MySQL matrix, test command/step, total passed/failed/skipped/assertions, log yang relevan, dan hasil DB postcondition.
   - Untuk klaim browser E2E, gunakan browser nyata yang berinteraksi dengan backend Laravel dan database disposable; runner dengan mock API tidak cukup.
   - Pisahkan hasil test otomatis dari uji browser dan uji operasional madrasah.

Satu sub-gate gagal atau tidak memiliki bukti berarti P0-02A tetap NOT VERIFIED/FAIL sesuai jenis temuan. Acceptance ini tidak boleh mengubah CP gate menjadi PASS.

## E. AUDIT — CP resmi tetap BLOCKED

Status CP tidak berubah:
- BSKAP 046/H/KR/2025: metadata/URL resmi diketahui, tetapi bytes artefak asli belum diarsipkan dan SHA-256 independen belum diverifikasi.
- Kepdirjen Pendis 9941/2025: salinan mirror pihak ketiga bukan bukti provenance final; byte asli dan validasi tanda tangan/provenance/checksum belum ada.
- BKPDM 020/2026: siaran pers yang diperiksa bukan naskah keputusan lengkap beserta lampiran yang diperlukan.
- `cp_content_imported=false` dan `artifact_checksum_verified=false` tetap harus dipertahankan sampai gate sah dipenuhi.

CP hanya dapat dibuka setelah file bytes asli dari penerbit/host resmi, identitas dokumen dan lampiran lengkap, provenance/signature yang dapat diverifikasi, SHA-256 hasil hitung atas bytes yang sama, serta cakupan mapel/fase/kelas diperiksa. Jangan mengimpor CP atau menandai sumber verified sebelum itu.

## F. AUDIT — workflow packaging ZIP dan archive guard

Workflow `.github/workflows/build-web-deployment.yml` pada branch ZIP P0 saat ini diperiksa langsung (blob `c2ea4c907001ce7f12aa347f354f8099dffa07e3`).

Temuan:
- `rsync` mengecualikan `.env` persis, `backup_before_patch/`, tests, dan beberapa direktori Laravel runtime.
- **Tidak mengecualikan `backups/`** dan tidak ada assertion archive untuk `backend-laravel/backups/`. Dengan adanya backup internal pada source tree yang sudah dicatat dalam audit sebelumnya, risiko backup masuk ke ZIP tetap P0.
- Guard hanya menolak nama `.env` persis. Belum ada pemeriksaan rekursif `.env.*` dengan allowlist eksplisit `.env.example`.
- Tidak ada deny rule generik untuk database dump (`.sql`, `.dump`, `.sqlite`, `.db` dan variasi terkompresi), private key/certificate/credential (`.pem`, `.key`, `.p12`, `.pfx`, dan pola terkait), atau file secret lain.
- Log Laravel dan direktori storage logs/cache/session/views/private tertentu dikecualikan; tetapi tidak ada guard generik untuk log di lokasi lain, cache umum, temp/swap/editor files, crash dump, dan artefak sementara.
- Archive guard memeriksa beberapa path spesifik dan menjalankan `unzip -t`; belum ada ekstraksi ke direktori kosong lalu `sha256sum -c SHA256SUMS.txt` atas seluruh file hasil ekstraksi.
- ZIP, hash arsip, dan contents list baru akan menjadi bukti setelah workflow dijalankan pada exact commit kandidat. Belum ada artifact ZIP yang diverifikasi pada exact HEAD branch ZIP P0.
- Trigger push workflow yang diperiksa tidak mencantumkan branch `work/limited-zip-p0-20261125`; `workflow_dispatch` tersedia, tetapi tidak ada bukti workflow packaging telah dijalankan untuk exact HEAD ini.

Kategori wajib untuk hardening guard sebelum acceptance:
1. backup internal seluruh kedalaman: `backups/`, `backup_before_patch/`, `*.before`, backup manifests yang tidak ditujukan untuk distribusi;
2. environment/secrets: semua `.env*` kecuali `.env.example`, credential/config secret, private key/certificate;
3. database: SQL/dump/SQLite/DB files, compressed dumps;
4. runtime/debug: log, cache, session, compiled views, temp, swap, crash dump, test/report/debug artifacts;
5. source control/build: `.git/`, test suite/fixture sensitif, PHPUnit cache, generated build files yang tidak dibutuhkan;
6. arsip: periksa daftar isi ZIP terhadap denylist, ekstrak ke direktori baru, verifikasi seluruh SHA-256 manifest, jumlah/struktur file, README, konfigurasi, dan ketiadaan seluruh kategori terlarang.

Status packaging tetap **FAIL P0 / NOT VERIFIED**, bukan sekadar warning.

## G. Status reconciliation dan keputusan release

| Gate | Status setelah update | Catatan |
|---|---|---|
| P0-01 Installer/System Tenant + full PHPUnit | **PASS / CLOSED** | Run #114, exact SHA 150b5, PHP 8.3 & 8.4 |
| Run #109 | **SUPERSEDED** | Riwayat kegagalan lama, bukan blocker aktif |
| P0-02 audit akademik source | **AUDIT COMPLETE** | Source review selesai; tidak berarti acceptance lulus |
| P0-02A Non-CP Academic Acceptance | **NOT RUN / NOT VERIFIED** | Kriteria independen ditetapkan di atas |
| Provisioning minimum di ZIP P0 | **PASS** | TenantAcademicBootstrapper tersedia dan P0-01 CI lulus |
| Provisioning akademik lanjutan | **SEPARATE BRANCH** | TenantAcademicBootstrapService/UI masih pada branch provisioning |
| CP resmi/provenance/checksum | **BLOCKED** | Byte asli/provenance/hash/cakupan belum lengkap |
| ZIP backup exclusion/archive guard | **FAIL P0** | backups tidak dikecualikan/ditolak |
| .env.* / DB dump / private key / generic log-cache-temp guard | **NOT VERIFIED** | Belum ada denylist menyeluruh |
| ZIP exact HEAD artifact | **NOT VERIFIED** | Belum ada artifact exact SHA |
| Clean extraction + full checksum | **NOT VERIFIED** | unzip -t saja tidak cukup |
| Browser E2E backend nyata | **NOT VERIFIED** | Belum ada bukti |
| Uji operasional madrasah | **NOT VERIFIED** | Belum ada bukti |
| Android native / Device Lease | **DEFERRED** | Sesuai keputusan owner sampai Juni 2027 |

**Kesimpulan: BELUM SIAP dinyatakan ZIP SIAP UJI OPERASIONAL TERBATAS.** P0-01 sudah ditutup; blocker aktif sekarang adalah P0-02A, CP resmi, rekonsiliasi source akademik yang memang diperlukan untuk scope ZIP, hardening packaging, artifact exact commit, ekstraksi/checksum, dan browser E2E.

## H. Implementasi Agent 2

- Hanya menambahkan addendum laporan dan checkpoint pada branch audit independen.
- Tidak mengubah source Agent 1, workflow Agent 1, migration, database, PR, VPS, atau deployment.
- Tidak menjalankan PHPUnit, Gradle, migration, browser test, atau operasi database. Bukti pengujian yang dicatat berasal dari GitHub Actions Agent 1.


---

# ADDENDUM — ATP PROVENANCE & ZIP ARCHIVE FOLLOW-UP
Tanggal pemeriksaan: 25 November 2026
Mode: read-only; pemeriksaan GitHub exact commit, source, test, workflow, run status, dan artifact listing.

## 1. Exact source refs dan CI

- Branch Agent 1 ZIP P0: `work/limited-zip-p0-20261125`.
- Exact HEAD pada pemeriksaan: `c0d7b4f4805cafa4db35253b5291216cdef6a94c`.
- Commit source/test yang sedang diuji Run #142: `1a9fffeb85f7efbdcc10446a064786749218a63e`.
- Perbandingan `1a9fff` → `c0d7b4`: satu commit tambahan dan satu file dokumentasi backup manifest saja; tidak ada source/test berubah setelah SHA yang sedang diuji.
- Service blob: `3e1a484aec0a212234e866c04bfde3d2789d2aa6`.
- Regression test blob: `58343c391e239f8724af382c2669c66768f03890`.
- Acceptance test blob: `9d51e8c99e998afe8941f49ee0ac9c4874dd1b21`.
- Workflow build ZIP blob: `c2ea4c907001ce7f12aa347f354f8099dffa07e`.

Run #142: https://github.com/zane0184/cbt_november/actions/runs/45534390278
- Tested exact SHA: `1a9fffeb85f7efbdcc10446a064786749218a63e`.
- Job PHP 8.3 / MySQL 8.0 InnoDB: IN_PROGRESS pada pemeriksaan.
- Job PHP 8.4 / MySQL 8.0 InnoDB: IN_PROGRESS pada pemeriksaan.
- Conclusion: belum tersedia; tidak ada angka pass/fail yang dapat disahkan.
- Artifact list: kosong.

Run #143: https://github.com/zane0184/cbt_november/actions/runs/45534521144
- Exact SHA: `c0d7b4f4805cafa4db35253b5291216cdef6a94c`.
- Trigger commit hanya menambahkan `docs/backup/P0_02A_ACTIVE_STATUS_TEST_20261125/MANIFEST.md`.
- Status pending pada pemeriksaan; belum ada hasil.
- Karena perubahan setelah Run #142 hanya dokumentasi, source/test yang diperiksa pada Run #142 identik dengan source/test pada HEAD saat ini. Namun CI exact HEAD c0d7 tetap belum selesai.

## 2. AUDIT — TeacherLearningOutcomeService::createAtp()

### Source flow yang diperiksa

Pada `TeacherLearningOutcomeService::createAtp()`:
1. Memerlukan user terautentikasi, profil Guru, dan permission `curriculum.manage`.
2. Mengunci assignment aktif milik Guru pada tenant yang sama dan tanggal penugasan yang berlaku.
3. Memvalidasi registrasi kurikulum serta official subject structure dari assignment snapshot.
4. Mengambil TP induk berdasarkan tenant, registrasi, assignment, owner, versi, struktur mapel, mapel, tahun pelajaran, semester, parent CP tidak null, status TP DRAFT/PUBLISHED, dan `is_current=true`; row dikunci.
5. Memanggil `resolveVerifiedOfficialCp()` menggunakan owner, versi, subject structure, dan `parentTp->parent_cp_id`.
6. Baru setelah validasi provenance lulus, membuat ATP, menyalin mapping TP, dan menulis audit log; seluruh proses berada dalam transaksi DB.

Helper `resolveVerifiedOfficialCp()`:
- mensyaratkan CP ada, tenant/owner, versi, struktur mapel, `outcome_type=CP`, `status=ACTIVE`, `verification_status=ARTIFACT_CHECKSUM_VERIFIED`, `is_official=true`, dan `is_editable=false`;
- mengunci row CP;
- mensyaratkan source_id dan mengambil row sumber dalam tenant/owner yang sama dengan `lockForUpdate()`;
- mensyaratkan status sumber `ARTIFACT_CHECKSUM_VERIFIED`;
- mensyaratkan source checksum dan CP source_checksum masing-masing berbentuk 64 karakter hex;
- membandingkan checksum dengan `hash_equals()`;
- menolak dengan ValidationException sebelum row ATP, mapping, atau audit baru dibuat bila gate gagal.

### Jalur pemanggilan yang ditemukan

- `TeacherLearning::createTp()` → `TeacherLearningOutcomeService::createTp()`.
- `TeacherLearning::createAtp()` → `TeacherLearningOutcomeService::createAtp()`.
- `TeacherLearning::publish()` → `TeacherLearningOutcomeService::publish()`.
- Di dalam service, helper CP yang sama dipanggil oleh `createTp()`, `createAtp()`, dan `publish()`; jadi validasi CP tidak hanya dilakukan ketika ATP dibuat, tetapi diulang ketika TP/ATP dipublikasikan.
- Question Bank dan service pemetaan kurikulum asesmen memiliki pemeriksaan provenance CP tersendiri. Pemeriksaan itu bukan pemanggilan `createAtp()` dan tetap harus diuji melalui acceptance question/assessment terpisah.

### Regression test yang ditemukan pada exact source

`TeacherLearningOutcomeProvenanceTest` mencakup:
- CP berstatus PENDING → create ATP ditolak.
- Source checksum kosong → ditolak.
- Source checksum tidak cocok → ditolak.
- CP source_checksum tidak cocok → ditolak.
- CP yang sebelumnya verified diubah menjadi INACTIVE → ditolak.
- Setelah CP diaktifkan kembali, source yang sebelumnya verified diubah menjadi PENDING → ditolak.
- TP tanpa parent CP yang valid → ditolak.
- Positive control dengan provenance fixture sintetis yang checksum-nya konsisten → ATP dibuat.
- Pemanggilan langsung service tanpa permission `curriculum.manage` → 403.

Setiap negative helper assertion membandingkan jumlah ATP, mapping, dan audit log sebelum/sesudah, sehingga menguji tidak adanya row parsial. Positive fixture secara eksplisit ditandai test-only; ini bukan bukti CP resmi.

`P002ANonCpAcademicAcceptanceTest` juga menguji CP sumber Pendis 9941 yang tetap PENDING: create TP, create ATP, publish TP, publish ATP, dan alur Question Bank ditolak; test memeriksa row outcome tetap tidak bertambah atau tetap DRAFT. Fixture CP adalah placeholder test-only yang tertaut ke source row PENDING, bukan konten CP resmi.

### Kekurangan coverage yang masih terlihat

- Belum terlihat kasus khusus source row hilang/soft-deleted, source_id menunjuk tenant lain, CP owner/version/subject structure mismatch, atau checksum format salah selain null/mismatch.
- Status sumber yang berubah diuji ke PENDING; belum ada variasi status invalid lain secara terpisah.
- Positive path menggunakan synthetic verified fixture; tidak boleh ditafsirkan sebagai validasi dokumen CP nyata.
- Source review dan keberadaan test bukan bukti test tersebut telah lulus dijalankan.

**Status independen createAtp saat ini: SOURCE REVIEW PASS WITH COVERAGE GAPS; REGRESSION TEST RESULT NOT VERIFIED.** Jangan menaikkannya menjadi PASS sampai Run #142/143 selesai dan log membuktikan test yang relevan lulus pada MySQL 8/InnoDB PHP 8.3 dan 8.4.

## 3. AUDIT — packaging workflow dan ZIP exact HEAD

Workflow `.github/workflows/build-web-deployment.yml` pada HEAD c0d7 masih blob `c2ea4c9...`, identik dengan workflow yang sebelumnya diperiksa. Tidak ada archive guard fix pada commit baru.

Temuan aktual:
- rsync mengecualikan `.env` persis dan `backup_before_patch/`, tetapi tidak mengecualikan `backups/`.
- Guard hanya menolak `.env` persis; belum ada denylist rekursif `.env.*` dengan allowlist `.env.example`.
- Tidak ada guard generik untuk database dumps/SQLite files, private key/certificate/credential, log di semua lokasi, cache umum, temp/swap/crash files, atau backup dengan pola nama lain.
- Guard archive melakukan `unzip -t`, membuat contents list, lalu grep pola terbatas (backup_before_patch, tests, .env persis, beberapa PHPUnit/demo files). Ia tidak memeriksa semua kategori file terlarang.
- Workflow membuat `SHA256SUMS.txt` di stage tree dan checksum ZIP, tetapi tidak mengekstrak ZIP ke direktori kosong lalu menjalankan `sha256sum -c` atas manifest hasil ekstraksi.
- Workflow memeriksa keberadaan README hasil salinan dan panduan aktivasi; pemeriksaan ini hanya saat build stage, bukan inspeksi isi README pada artifact yang sudah diekstrak.
- Branch `work/limited-zip-p0-20261125` tidak ada dalam daftar trigger `push.branches` workflow packaging. `workflow_dispatch` tersedia, tetapi tidak ada run packaging yang dapat diverifikasi pada exact HEAD.
- Tidak ada ZIP artifact pada Run #142; workflow tersebut adalah DOCX Import Parser CI, bukan package build. Tidak ada artifact ZIP exact HEAD yang tersedia pada listing yang diperiksa.

**Status packaging: FAIL P0 / NOT VERIFIED.** Workflow belum diperbaiki dan belum dijalankan untuk menghasilkan ZIP exact HEAD. Karena tidak ada ZIP artifact, auditor tidak dapat mengklaim telah memeriksa daftar file arsip, ekstraksi bersih, checksum seluruh file, struktur paket, atau README hasil ekstraksi.

### Kriteria wajib ketika ZIP exact HEAD tersedia

1. Cocokkan metadata ZIP dengan exact source SHA, branch, waktu build, dan artifact run.
2. Periksa daftar isi arsip secara rekursif untuk menolak:
   - `backups/`, `backup_before_patch/`, `*.before`, backup manifest internal;
   - semua `.env*` selain `.env.example`;
   - `.sql`, `.dump`, `.sqlite`, `.db` serta dump terkompresi;
   - `.pem`, `.key`, `.p12`, `.pfx`, private certificates, credential/token files;
   - log, cache, session, compiled views, temp/swap/crash files, PHPUnit cache, tests/fixtures/debug output, dan artefak build yang tidak dibutuhkan.
3. Ekstrak ke direktori kosong baru (bukan menimpa folder lama).
4. Jalankan verifikasi checksum seluruh file hasil ekstraksi terhadap `SHA256SUMS.txt`; pastikan manifest tidak meng-hash dirinya sendiri dan tidak ada file yang tertinggal/tidak tercatat.
5. Verifikasi struktur Laravel lengkap: artisan, composer.json/lock, vendor/autoload, .env.example, migrations, routes, config, resources, storage writable placeholders, serta tidak ada source test/backup/secrets.
6. Baca README hasil ekstraksi dan pastikan langkah Windows Explorer, penempatan .env milik pengguna dari backup pribadi, ekstraksi, kebutuhan PHP/MySQL, pembukaan/pemasangan, dan peringatan bahwa paket bukan persetujuan produksi dinyatakan jelas.
7. Simpan bukti artifact ID, ukuran, SHA-256 ZIP, contents list, hasil scan denylist, hasil extract, hasil `sha256sum -c`, jumlah file, dan inspeksi README.
8. Lakukan browser E2E ke backend Laravel nyata dari hasil ekstraksi; jangan mengganti dengan mock API.

## 4. DESAIN / acceptance tambahan

Dua fokus wajib audit berikutnya:
- ATP provenance: uji source code, jalur Livewire → service, helper CP, status CP/source, checksum format/mismatch, owner/version/subject mismatch, source missing/tenant mismatch, permission bypass langsung, dan rollback/no-partial-state. Gunakan MySQL 8/InnoDB disposable.
- ZIP: audit exact workflow dan artifact hasil build setelah guard fix; jangan menganggap lulus dari YAML, manifest build-stage, atau `unzip -t` saja.

## 5. IMPLEMENTASI Agent 2

- Hanya addendum laporan/checkpoint pada branch audit independen.
- Tidak ada source/workflow Agent 1, test Agent 1, branch Agent 1, migration, database, merge, PR, VPS, atau deployment yang diubah.
- Tidak menjalankan test atau build ZIP dari sisi Agent 2.
