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
