# REPORT P1 IMPLEMENTATION UNBLOCK — 24 November 2026

## Metadata
- Repository: `zane0184/cbt_november`
- Branch at audit start: `work/p1-seven-targets-20261124`
- Exact HEAD at audit start: `cd2b6ee8ed9d57604a06860e1dee399fb4b2b050`
- PR #23: OPEN / DRAFT / NOT MERGED
- Base branch: `work/phpunit-41-root-cause-20261123`
- Base SHA: `1c33e5d422175f9bd85d206230271a9fe5a4eecd`
- Scope: workspace/toolchain unblock and continuation audit for P1-ID-03A.
- No application source, migration, production database, PR state, or deployment was changed by this report.

## AUDIT

### 1. Repository and branch
- Refreshed repository metadata, PR #23, and branch ref through GitHub.
- Branch ref resolved to exact SHA `cd2b6ee8ed9d57604a06860e1dee399fb4b2b050`; it matched the supplied historical HEAD at audit time.
- PR #23 remained OPEN, DRAFT, NOT MERGED. Base SHA remained `1c33e5d422175f9bd85d206230271a9fe5a4eecd`.
- Both requested report/checkpoint paths were absent before this report was created.
- No local checkout, Git worktree, `.git` directory, or CBT repository cache was found in the current container paths.

### 2. Workspace and toolchain diagnosis
Environment observations in the current container:
- `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/`: NOT FOUND.
- Git CLI: available at `/usr/bin/git`.
- PHP CLI: 8.4.24; project requires `php ^8.3` (Composer constraint in `backend-laravel/composer.json`). PHP 8.4 is not a substitute for the project's required PHP 8.3 validation environment.
- PHP extensions loaded: core set only. `dom`, `mbstring`, `xmlwriter`, `pdo_mysql`, `pdo_sqlite`, and `sqlite3` are not available.
- `PDO::getAvailableDrivers()`: empty.
- Composer: NOT FOUND.
- MySQL/MariaDB client/server: NOT FOUND.
- Docker/Podman: NOT FOUND.
- PHPUnit and Laravel vendor dependencies: NOT AVAILABLE locally; no `vendor/` checkout exists.
- DNS lookup for `github.com` via system resolver failed; `curl` HTTPS request failed with `Could not resolve host: github.com`. TLS verification was not disabled. No trusted proxy configuration capable of resolving the host was found in the active environment.
- GitHub connector can read repository metadata, trees, files, workflow runs and logs, but it is not a local Git checkout and does not supply a usable local PHP/MySQL workspace. Its fetch capability returns UTF-8 text; it is not a binary repository archive/export mechanism.

Conclusion: workspace/toolchain recovery is BLOCKED. Do not claim local coding/testing readiness. The next environment must provide a writable checkout or official snapshot plus Git metadata, PHP 8.3 and required extensions, Composer, locked dependencies, PHPUnit, and a disposable MySQL 8/InnoDB service. An official source snapshot must be verified against the exact commit SHA; if it lacks `.git`, report it as a snapshot, not a Git checkout.

### 3. Exact-HEAD CI evidence
GitHub Actions run:
- Run ID: `45347315210`
- URL: https://github.com/zane0184/cbt_november/actions/runs/45347315210
- Event: push
- Exact SHA: `cd2b6ee8ed9d57604a06860e1dee399fb4b2b050`
- Job: `laravel-mysql`
- Conclusion: SUCCESS
- Steps include PHP 8.3 setup, disposable MySQL identity check, Composer lock/platform validation, locked dependency installation, package discovery, PHP lint, focused Web login/tenant smoke tests, and full PHPUnit against disposable MySQL 8.
- Focused suite log: 56 passed, 225 assertions.
- Full suite log: 534 passed, 2,265 assertions.
- This is CI evidence for that exact SHA, not proof that the current local toolchain has been restored or that P1-ID-03A is complete.

### 4. P1-ID-03A source re-check
Current source blob references at the audited SHA:
- `app/Console/Commands/LoginIdentityPreflightCommand.php`: `4fa441f98cd58b8be011537a54e20f24fa64648b`
- `tests/Feature/LoginIdentityPreflightCommandTest.php`: `0ef9ec3b79e74ebc7fa50874376d0386460fc604`
- `app/Services/AuditService.php`: `e968cc401cdd8b04ab1322171d5f475332a55372`
- `app/Models/TeacherProfile.php`: `192d9804d4e926e7827d6a379542825da3fc80d0`
- `app/Models/StudentProfile.php`: `95ce4b3033b6cc4406a42e2e4a6045cff01218b8`
- `app/Livewire/Admin/UserManagement.php`: `fb18b91c5f534e774ccf5462e195875d7cb4e1b4`
- `app/Livewire/Teacher/TeacherProfilePage.php`: `e5bc6d51c740b83d9ec0dd72b0fbb3f2929ff5b1`
- `app/Console/Commands/Phase7dBenchmarkCommand.php`: `335e126e9fef534094eb737c69165e60cbac8e6a`
- `database/migrations/2026_11_24_170001_enforce_tenant_login_identity_uniques.php`: `11eb38296af04769562738f6fae491b0a36a019e`

Preflight currently checks employment status, required NIP/NIK/NISN formats, optional NUPTK format, employee_identifier equality, blank/edge-whitespace identity fields, and duplicates per tenant for each individual identifier column. It returns counts and does not print raw identifier values for the inspected categories.

Confirmed gaps in the current preflight source:
- It does not reject a PNS/PPPK profile that also has NIK populated.
- It does not reject a HONORER profile that also has NIP populated.
- It does not inspect cross-category collision between Admin username and Guru/Siswa identifiers.
- It does not inspect whether linked User and profile tenant IDs match.
- It does not verify profile-to-user cardinality/role consistency.
- Its initial structure guard checks table existence, but does not explicitly validate every required column before the SELECTs. Missing selected columns would cause query failure; add explicit fail-closed schema validation with a sanitized diagnostic.
- The command currently does not include tenant_id/user_id in selected teacher/student fields, so it cannot perform tenant-link checks.
- Duplicate groups are checked separately by table/column, not across identity categories.

Existing preflight tests cover only three scenarios: valid PNS/Honorer/Siswa data and no raw identifier output; whitespace-padded NIP detection; legacy Guru without status/identifier. Test coverage does not yet prove all anomaly categories, database immutability, cross-tenant duplicate allowance, same-tenant collisions, missing-column fail-closed behavior, or null/empty variants for every field.

### 5. Fixtures and benchmark
- `Phase7dBenchmarkCommand.php` creates a benchmark TeacherProfile without employment_status and required NIP/NIK/employee_identifier.
- The same benchmark seeds student NISN values as `NISN_001` etc., which violate the final 10-digit NISN contract.
- This benchmark fixture must be corrected with synthetic but format-valid, unique identities before database enforcement. Preserve benchmark workload purpose and ensure no identity value is claimed to be real.
- Full repository-wide fixture/benchmark sweep is NOT COMPLETE because local checkout and scripted search are unavailable. Do not claim all fixtures have been normalized.

### 6. AuditService and PII
- Current `AuditService::log()` persists `oldValues` and `newValues` arrays directly into AuditLog. No central recursive identity-field redaction is visible in the inspected implementation.
- TeacherProfile model hides `employee_identifier`, `nip`, `nik`, and `nuptk` during serialization, but those hidden attributes can still be explicitly passed to AuditService.
- StudentProfile hides `nisn` during serialization, but this does not redact arbitrary audit payloads.
- Therefore centralized PII redaction/masking/fingerprint is NOT IMPLEMENTED in the inspected AuditService source. Existing historical audit rows must not be rewritten.
- All AuditService callsites have NOT YET been exhaustively inventoried.

### 7. Mass assignment
- TeacherProfile `$fillable` includes `employee_identifier`, `employment_status`, `nip`, `nik`, and `nuptk`.
- StudentProfile `$fillable` includes `nisn`.
- Admin UserManagement create path performs role-specific validation and writes profiles inside a transaction; this is a validated creation path, not proof that every write path is safe.
- TeacherProfilePage currently updates only phone; it does not provide identity update.
- A complete audit of create/fill/forceFill/update/updateOrCreate/firstOrCreate/upsert/insert/query-builder/raw SQL/save callsites is NOT COMPLETE. Keep mass-assignment gate PARTIAL until repository-wide search and caller authorization review are performed.

### 8. Import status
- Admin Guru/Siswa XLSX import/export implementation was not proven by this re-check. Keep import Guru/Siswa as NOT IMPLEMENTED / NOT VERIFIED according to the current source evidence; do not upgrade the status based on the Admin master specification alone.

### 9. Existing migration boundary
- The existing migration `2026_11_24_170001_enforce_tenant_login_identity_uniques.php` adds tenant-scoped unique indexes and has a read-only preflight before DDL.
- It does not enforce status/category/format correctness using MySQL CHECK or SQLite trigger.
- No new migration was created or executed in this task.
- P1-ID-03B remains BLOCKED pending P1-ID-03A completion and explicit design approval.

## DESAIN

### 1. Workspace recovery path
1. Obtain a writable checkout of repository at the verified branch and commit using normal Git clone/fetch once DNS/HTTPS works.
2. If Git transport remains blocked, use only an official GitHub-generated archive/snapshot for the exact SHA; verify the archive commit metadata/tree against GitHub. Mark it SNAPSHOT ONLY if `.git` metadata is absent.
3. Do not create a synthetic Git history or claim a complete checkout.
4. Provision PHP 8.3 with extensions required by Composer/Laravel/PHPUnit and MySQL 8/InnoDB disposable.
5. Validate `composer validate --strict`, `composer check-platform-reqs`, install from `composer.lock`, PHP lint, and focused/full tests only after environment validation.

### 2. P1-ID-03A patch order
1. Expand preflight schema contract checks and anomaly categories; retain read-only behavior.
2. Add one isolated test per anomaly and paired valid-data tests; verify raw PII never appears and DB row snapshots remain unchanged.
3. Sweep fixtures/benchmarks, beginning with Phase7dBenchmarkCommand invalid Teacher/Siswa identities. Use valid synthetic fixed-length numeric strings and uniqueness.
4. Inventory all identity write paths and prove role/tenant/authorization validation at each path.
5. Add centralized recursive AuditService redaction for NIP/NIK/NISN/NUPTK/employee_identifier and nested payload keys; retain safe masked/fingerprint evidence where needed. Do not rewrite historical rows.
6. Complete mass-assignment review and restrict direct identity writes to validated domain paths without blindly removing fillable fields.
7. Byte-exact backup each file before change; record original blob SHA, backup path, and SHA-256 computed in the workspace.
8. Run focused PHPUnit, full PHPUnit, and CI on the resulting exact commit SHA.

### 3. Scope boundaries
- Do not create identity enforcement migration during P1-ID-03A.
- Do not begin P1-ID-03B until P1-ID-03A passes review and receives explicit approval.
- Do not change final login identity policy.
- Do not repeat P0 remediation.
- Do not rebuild Web Siswa runner or create a second curriculum system/ZIP workflow.
- Do not merge PR #23, alter production database/configuration, deploy, or run production migrations.

## IMPLEMENTASI

- No application source files changed.
- No migrations created, edited, or run.
- No local checkout recovered.
- No local backup generated because no source files were changed.
- Two documentation files are being added to the existing P1 branch only; this is documentation, not an application patch.
- PR #23 remains OPEN / DRAFT / NOT MERGED.

## PENGUJIAN

### Current container tests
- Git CLI availability: CHECKED.
- DNS github.com: FAIL (name resolution failure).
- HTTPS github.com: FAIL (curl could not resolve host).
- PHP CLI: 8.4.24, NOT the required PHP 8.3 runtime.
- Composer: NOT RUN / NOT INSTALLED.
- Composer platform validation: NOT RUN.
- Laravel dependency install: NOT RUN.
- PHP lint on checkout: NOT RUN.
- PHPUnit local: NOT RUN.
- MySQL 8/InnoDB disposable local: NOT AVAILABLE.
- PDO drivers: none available.
- Docker/Podman: NOT AVAILABLE.
- Production DB and deployment: NOT TOUCHED.

### Existing GitHub Actions evidence
- Exact SHA: `cd2b6ee8ed9d57604a06860e1dee399fb4b2b050`
- Run: https://github.com/zane0184/cbt_november/actions/runs/45347315210
- PHP: 8.3
- MySQL: disposable MySQL 8; workflow disposable identity check passed.
- Focused tests: 56 passed, 225 assertions.
- Full PHPUnit: 534 passed, 2,265 assertions.
- Workflow conclusion: SUCCESS.
- This is historical CI evidence on the unchanged exact SHA, not a new run triggered by this unblock report.

## STATUS
- Workspace/toolchain recovery: BLOCKED.
- P1-ID-03A: PARTIAL / NOT COMPLETE.
- P1-ID-03B: BLOCKED / NOT STARTED.
- Web Admin: PARTIAL.
- Web Guru: PARTIAL.
- Web Siswa: PARTIAL.
- Proktor Mode B: BLOCKED.
- Staging: NOT VERIFIED.
- Final ZIP: NOT ACCEPTED.
- Production: NOT TOUCHED.


---

## ADDENDUM — Refresh repository dan CI setelah checkpoint awal

Waktu refresh: 24 November 2026, 09:15 UTC (berdasarkan metadata GitHub Actions).

### AUDIT TERBARU
- PR #23 masih OPEN / DRAFT / NOT MERGED.
- Branch PR: `work/p1-seven-targets-20261124`.
- Branch HEAD pada refresh historis 09:15 UTC: `afd9efd4e03680e063e066f7456f9f7e32c7e614` (bukan HEAD terkini; lihat addendum koreksi 09:20 UTC).
- Commit `afd9efd4` hanya mengubah `docs/CHECKPOINT_P1_IMPLEMENTATION_UNBLOCK_2026-11-24.md`; parent-nya `3e851357bc673643674398cb4406b971c46fd337`.
- Commit `3e851357` hanya menambahkan laporan unblock dan parent-nya adalah source HEAD `cd2b6ee8ed9d57604a06860e1dee399fb4b2b050`.
- Dengan demikian, source aplikasi yang terakhir diperiksa tetap `cd2b6ee8...`; dua commit setelahnya merupakan dokumentasi saja. Tidak ada source aplikasi yang berubah dalam dua commit tersebut.
- Kedua dokumen unblock yang diminta ternyata sudah tersedia pada branch; laporan ini merupakan refresh/addendum atas dokumen yang sudah ada, bukan klaim bahwa file baru dibuat pada sesi refresh.
- GitHub tree memuat 1.273 entry dan tidak terpotong. GitHub connector hanya memberi akses baca teks/metadata untuk kebutuhan ini; tidak menyediakan binary source archive yang dapat dipulihkan menjadi checkout lokal.
- Pemeriksaan lokal diulang: `/mnt/data/CBT_MADRASAH_RECONSTRUCTION` tidak ada; Git 2.47.3 tersedia; PHP 8.4.24; Composer, MySQL client/server, Docker/Podman tidak ada; modul PDO tidak memiliki driver; DNS/HTTPS GitHub dari terminal gagal dengan `Could not resolve host: github.com`. TLS verification tetap aktif.
- Tidak ada perubahan lokal yang dapat hilang karena tidak ada checkout/worktree ditemukan.

### CI EXACT CURRENT BRANCH HEAD
- Run Tenant Backend CI: `45363315036`, exact SHA `afd9efd4...`, berakhir `CANCELLED` pada 09:16:45 UTC. Log menyatakan operasi dibatalkan; focused smoke sebelumnya selesai PASS, full PHPUnit dimulai tetapi terhenti. Ini bukan hasil full-suite PASS dan tidak ada bukti assertion gagal dari run ini.
- Step yang sudah PASS: checkout, PHP 8.3 setup, disposable MySQL identity check, Composer lock/platform validation, locked dependency install, Laravel package discovery, PHP lint, disposable installer database setup, dan focused Web login/tenant identity smoke test.
- Full PHPUnit terhadap disposable MySQL 8 tidak selesai karena job dibatalkan; hasil penuh run ini NOT VERIFIED.
- Run ZIP: `45363315217`, exact SHA `afd9efd4...`, conclusion SUCCESS. Artifact ID `14906315115`, nama `cbt-madrasah-web-deployment-afd9efd4e03680e063e066f7456f9f7e32c7e614`, ukuran 19.768.203 bytes.
- ZIP build success bukan acceptance ZIP final. Fresh install, upgrade, restore, aaPanel install, security review, dan portal E2E tetap belum dibuktikan.
- CI lama pada source SHA `cd2b6ee8...` tetap PASS 534 tests / 2.265 assertions; hasil itu tidak diganti atau dianggap hasil run baru pada `afd9efd4...`.

### IMPLEMENTASI
- Tidak ada pemulihan checkout lokal.
- Tidak ada source aplikasi, migration, workflow, dependency lock, atau konfigurasi yang diubah.
- Tidak ada patch aplikasi melalui GitHub Contents API.
- Refresh ini hanya dokumentasi audit/checkpoint pada branch P1.
- Tidak ada backup source baru karena tidak ada file source yang diubah.

### PENGUJIAN
- Environment diagnosis lokal: diperiksa ulang; hasil seperti di atas.
- PHP lint lokal, Composer validation/install, PHPUnit lokal, MySQL lokal, migration, dan race test: NOT RUN.
- CI `45363315036`: RUNNING; hasil penuh NOT YET AVAILABLE.
- ZIP workflow `45363315217`: SUCCESS untuk build/archive workflow saja; ZIP belum diterima sebagai distribusi.
- Produksi/staging: tidak disentuh.

### KEPUTUSAN STATUS
- Workspace/toolchain recovery: BLOCKED.
- P1-ID-03A: PARTIAL, NOT COMPLETE.
- P1-ID-03B: BLOCKED, NOT STARTED.
- Import Guru/Siswa: NOT IMPLEMENTED / NOT VERIFIED.
- Web Admin: PARTIAL.
- Web Guru: PARTIAL.
- Web Siswa: PARTIAL.
- Proktor Mode B: BLOCKED.
- Staging: NOT VERIFIED.
- ZIP final: NOT ACCEPTED.
- Production: NOT TOUCHED.

### NEXT ACTION
1. Sediakan runner/VM/container kerja dengan PHP 8.3 dan extension yang sesuai `composer.json`, Composer, dependency install dari `composer.lock`, PHPUnit, dan MySQL 8/InnoDB disposable.
2. Pulihkan Git checkout normal melalui HTTPS setelah DNS tersedia. Bila hanya snapshot GitHub yang dapat diunduh, verifikasi commit/tree SHA dan tandai SNAPSHOT ONLY; jangan mengarang metadata Git.
3. Tunggu hasil akhir CI `45363315036` dan catat hasil exact SHA.
4. Lanjutkan P1-ID-03A saja dengan backup byte-exact sebelum setiap source edit.
5. STOP sebelum P1-ID-03B sampai P1-ID-03A lulus dan mendapat persetujuan eksplisit.


---

## ADDENDUM KOREKSI — P1 IMPLEMENTATION RECOVERY (24 November 2026)

### AUDIT — repository, dokumentasi, dan toolchain

- PR #23 diverifikasi ulang: OPEN / DRAFT / NOT MERGED.
- Branch PR: `work/p1-seven-targets-20261124`.
- HEAD branch saat audit ini: `2f13517217bcdb8ffb591f4df7751bcc4b7354ab`.
- Base ref: `work/phpunit-41-root-cause-20261123`; base SHA: `1c33e5d422175f9bd85d206230271a9fe5a4eecd`.
- Source aplikasi terakhir yang diaudit: `cd2b6ee8ed9d57604a06860e1dee399fb4b2b050`.
- Perbandingan `cd2b6ee8...` ke `2f135172...`: 4 commit di depan, 0 di belakang; hanya dua file dokumentasi berubah: report dan checkpoint unblock. Tidak ada source aplikasi/migration/workflow berubah pada rentang ini.
- Pernyataan HEAD `afd9efd4...` di addendum sebelumnya adalah catatan historis pada 09:15 UTC, bukan posisi branch terkini. Run #45363315036 yang dahulu dicatat IN_PROGRESS kemudian berakhir CANCELLED; koreksi rinci ada di bawah.
- Workspace lokal `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tidak ditemukan. Tidak ada `.git`, composer.json/lock, vendor, atau checkout CBT di /mnt/data.
- Git 2.47.3 tersedia. PHP 8.4.24 tersedia, tetapi bukan runtime PHP 8.3 yang diminta. Modul yang terdeteksi hanya core terbatas (ctype, fileinfo, json, libxml, openssl, PDO, tokenizer); PDO driver kosong. Composer, MySQL client/server, Docker/Podman tidak tersedia.
- DNS `github.com` gagal di-resolve dan HTTPS curl berakhir `Could not resolve host: github.com`. TLS verification tidak dimatikan. Workspace/toolchain lokal tetap BLOCKED.
- GitHub Actions remote berhasil menyediakan PHP 8.3 dan disposable MySQL untuk CI, tetapi itu tidak memulihkan checkout lokal atau menyediakan workspace writable untuk patch source.

### AUDIT — P1-ID-03A pada exact source SHA

Source aplikasi yang diperiksa tetap `cd2b6ee8...`; blob SHA sumber utama:
- `LoginIdentityPreflightCommand.php`: `4fa441f98cd58b8be011537a54e20f24fa64648b`.
- `LoginIdentityPreflightCommandTest.php`: `0ef9ec3b79e74ebc7fa50874376d0386460fc604`.
- `AuditService.php`: `e968cc401cdd8b04ab1322171d5f475332a55372`.
- `Phase7dBenchmarkCommand.php`: `335e126e9fef534094eb737c69165e60cbac8e6a`.
- `UserManagement.php`: `fb18b91c5f534e774ccf5462e195875d7cb4e1b4`.
- `TeacherProfile.php`: `192d9804d4e926e7827d6a379542825da3fc80d0`.
- `StudentProfile.php`: `95ce4b3033b6cc4406a42e2e4a6045cff01218b8`.

Temuan preflight:
- Sudah read-only dan menghitung status Guru, format mentah NIP/NIK/NUPTK/NISN, employee_identifier equality, blank/edge whitespace, dan duplikasi per tenant per kolom.
- Belum memvalidasi relasi `tenant_id` antara User dan profile; query saat ini tidak memilih `tenant_id`/`user_id` untuk pemeriksaan tersebut.
- Belum menguji cardinality/kecocokan role User dengan profile, konflik silang kategori (misalnya kategori PNS dengan NIK terisi atau Honorer dengan NIP terisi), dan collision lintas tabel antara username Admin dengan identifier Guru/Siswa.
- Guard struktur baru memeriksa keberadaan tabel; belum memeriksa seluruh kolom yang diperlukan sebelum SELECT. Kegagalan kolom hilang dapat menghasilkan exception DB, bukan diagnostic fail-closed tersanitasi yang dirancang.
- Preflight menghitung duplikasi per tabel/kolom, bukan seluruh konflik kategori lintas identitas.
- Test preflight saat ini hanya tiga metode: data valid, whitespace-padded Guru, dan legacy Guru tanpa status/identifier. Belum ada test terpisah untuk setiap anomali, missing table/column, tenant/profile mismatch, role/cardinality, konflik silang kategori, duplicate tenant yang berbeda, setiap blank/null/format, dan bukti snapshot DB tidak berubah.
- Karena itu preflight dan test-nya tetap IMPLEMENTASI PARSIAL.

Temuan fixture/benchmark:
- `Phase7dBenchmarkCommand` membuat TeacherProfile tanpa kontrak status/identifier login final yang lengkap.
- Benchmark membuat NISN sintetis dengan pola `NISN_%03d`, bukan 10 digit numerik.
- Fixture benchmark belum dinormalisasi; jangan jalankan benchmark ini sebagai bukti kompatibilitas identity enforcement sebelum diperbaiki dan diuji.

Temuan jalur penulisan identitas dan mass assignment:
- Jalur UI create yang teridentifikasi adalah `UserManagement::createUser()`, dalam transaksi, dengan validasi role-specific; jalur ini membuat TeacherProfile atau StudentProfile.
- `TeacherProfilePage::savePhone()` hanya mengubah phone; tidak menyediakan update NIP/NIK/NUPTK.
- Benchmark melakukan direct profile create untuk fixture.
- Import Guru/Siswa dan recovery/update identitas belum terbukti tersedia: tetap NOT IMPLEMENTED / NOT VERIFIED.
- Model `TeacherProfile::$fillable` mencakup employee_identifier, employment_status, nip, nik, nuptk; `StudentProfile::$fillable` mencakup nisn. `$hidden` hanya mengatur serialisasi, bukan otorisasi mass assignment.
- Audit seluruh callsite fill/create/update/forceFill/upsert/query builder/raw SQL untuk kedua profile dan pemeriksaan otorisasi setiap jalur belum lengkap. Mass-assignment/authorization gate tetap PARTIAL.

Temuan AuditService/PII:
- `AuditService::log()` langsung meneruskan `oldValues` dan `newValues` ke `AuditLog::create()`; sanitizer/redactor terpusat belum terlihat pada implementasi.
- Inventaris callsite yang ditemukan pada pemeriksaan source mencakup Livewire StudentRombelManagement, TeacherSubjectAssignmentManagement, QuestionBank, ExamManagement, TeacherProfilePage, TeacherMaterialManagement, CurriculumRegistryManagement, ResultList; API AssessmentController, ExamAttemptController, ExamController, ExamParticipantController, QuestionController, QuestionPublicationController, ScoringController, ReportController, AnalyticsController, CurriculumController, ElementController, LearningObjectiveController, LearningOutcomeController, LearningPathController, PhaseController, TeacherSubjectAssignmentController, dan MediaController.
- InstallerService juga memanggil `AuditLog::create()` langsung sehingga melewati AuditService.
- Daftar di atas adalah hasil inventory source terarah, belum diklaim sebagai pencarian repository-wide yang lengkap. Semua payload old/new, nested keys, request payload, toArray(), dan bypass AuditLog langsung masih harus ditelusuri.
- Tidak ada redaksi terpusat yang boleh mengubah histori audit lama; desain implementasi harus menyaring data saat penulisan baru dan mempertahankan histori existing.

Temuan migration:
- Migration `2026_11_24_170001_enforce_tenant_login_identity_uniques.php` sudah ada dari pekerjaan P1 terdahulu untuk unique index tenant-scoped dan preflight sebelum DDL.
- Migration itu belum dijalankan. Tidak dibuat migration baru pada recovery ini. Conditional enforcement format/status untuk raw SQL masih belum dibuktikan; P1-ID-03B tetap tidak dimulai.

### DESAIN — langkah P1-ID-03A berikutnya

1. Lengkapi kontrak schema preflight: validasi kolom yang diperlukan, tenant/profile relation, role/profile cardinality, kategori yang saling eksklusif, format mentah, dan duplicate scope; tetap read-only dan tidak menampilkan PII.
2. Tambahkan test per kategori anomali serta pasangan data valid, cross-tenant allowance, DB immutability, dan missing schema fail-closed.
3. Normalisasi fixture/benchmark dengan identitas sintetis valid sesuai format final, unik per tenant, tanpa klaim bahwa identitas tersebut nyata.
4. Selesaikan inventory seluruh writer profile dan seluruh AuditService/AuditLog callsite; audit policy, permission, tenant scope, validated payload, dan bypass mass assignment.
5. Rancang sanitizer recursive terpusat untuk NIP/NIK/NISN/NUPTK/employee_identifier dan alias/nested payload yang sensitif. Hanya data audit baru yang disanitasi; jangan rewrite histori.
6. Backup byte-exact setiap source file sebelum edit; catat path backup, source blob SHA, backup blob SHA, dan SHA-256 hasil workspace.
7. Jalankan focused, full PHPUnit, dan CI pada exact source commit hasil patch. Jangan mulai P1-ID-03B sebelum review dan persetujuan koordinator.

### IMPLEMENTASI

- Pada recovery ini tidak ada source aplikasi, migration, dependency, workflow, atau konfigurasi yang diubah.
- Tidak ada patch source melalui GitHub Contents API.
- Hanya dokumen laporan/checkpoint yang dikoreksi secara dokumentasi.
- Tidak ada backup source baru karena tidak ada source yang diubah.
- Backup terdahulu tetap tersedia di `backend-laravel/backup_before_patch/P1_REQUIRED_LOGIN_IDENTITIES_20261124/`; manifest menyimpan blob SHA source dan path backup. Laporan identitas menyebut 13/13 pasangan awal dan addendum refinement menyebut 21/21 snapshot cocok pada saat verifikasi historis. Nilai SHA tersebut adalah baseline sebelum patch terkait, bukan hash source saat ini.
- Tidak ada merge, migration, deployment, atau perubahan DB produksi/staging.

### PENGUJIAN — exact SHA dan hasil

Run #45363315036:
- Exact SHA `afd9efd4...`; final conclusion CANCELLED (updated 09:16:45 UTC).
- PHP 8.3, MySQL disposable, Composer validation/install, package discovery, PHP lint, installer DB, dan focused smoke selesai PASS.
- Log menunjukkan focused suite 56 passed / 225 assertions.
- Full PHPUnit mulai berjalan, lalu log menyatakan `The operation was canceled`; tidak ada hasil akhir full suite untuk run ini. Ini bukan bukti test failure dan bukan PASS.

Run #45363315217:
- Exact SHA `afd9efd4...`; conclusion SUCCESS untuk build/archive workflow.
- Artifact ID `14906315115`, ukuran 19,768,203 bytes.
- Build success bukan ZIP acceptance/final distribution.

Run terbaru pada exact branch HEAD saat audit ini, `2f13517217bcdb8ffb591f4df7751bcc4b7354ab`:
- Tenant Backend CI #45363631214: SUCCESS.
- PHP 8.3, disposable MySQL identity check, Composer lock/platform validation, locked dependency install, Laravel package discovery, PHP lint, installer disposable DB, focused login/tenant smoke PASS, dan full PHPUnit PASS.
- Focused: 56 passed / 225 assertions.
- Full: 534 passed / 2,265 assertions.
- ZIP workflow #45363631190: SUCCESS untuk checkout exact SHA, PHP 8.3 setup, locked Composer validation, clean archive assembly, dan upload.
- Artifact terbaru ID `14906520236`, nama `cbt-madrasah-web-deployment-2f13517217bcdb8ffb591f4df7751bcc4b7354ab`, ukuran 19,768,215 bytes.
- CI ini berjalan pada exact HEAD dokumentasi `2f135172...`; compare membuktikan source aplikasi sama dengan `cd2b6ee8...`. Hasil 534/2,265 adalah bukti backend suite untuk tree source yang sama pada commit exact tersebut, tetapi tidak membuktikan P1-ID-03A selesai.
- ZIP workflow SUCCESS hanya membuktikan proses build/upload. Fresh install aaPanel, upgrade, backup/restore, security/load, browser E2E dan UAT belum membuktikan acceptance ZIP final.

### STATUS DAN KEPUTUSAN

- Workspace/toolchain lokal: BLOCKED.
- Remote CI PHP 8.3 + disposable MySQL: VERIFIED tersedia untuk workflow.
- Backend suite pada exact HEAD `2f135172...`: PASS 534 / 2,265.
- P1-ID-03A: PARTIAL / NOT COMPLETE.
- P1-ID-03B: BLOCKED / NOT STARTED.
- Admin Guru/Siswa import: NOT IMPLEMENTED / NOT VERIFIED.
- Web Admin: PARTIAL; Web Guru: PARTIAL; Web Siswa: PARTIAL.
- Proktor Mode B: BLOCKED.
- Staging: NOT VERIFIED.
- ZIP: BUILD SUCCESS / FINAL ZIP NOT ACCEPTED.
- Produksi: NOT TOUCHED.

### NEXT ACTION

1. Pulihkan runner/workspace writable dengan Git checkout asli dan PHP 8.3/Composer/MySQL disposable; CI remote yang PASS tidak menggantikan kemampuan edit source lokal.
2. Selesaikan audit writer, AuditService, mass assignment, dan seluruh test preflight sebelum patch.
3. Lanjutkan P1-ID-03A saja setelah environment tersedia; backup byte-exact per file sebelum patch.
4. Jalankan focused/full test setelah patch pada exact commit.
5. STOP untuk review koordinator sebelum P1-ID-03B.


---

## ADDENDUM — Audit source jarak jauh P1-ID-03A (24 November 2026)

### AUDIT — posisi branch dan sumber yang diperiksa

- Branch work/p1-seven-targets-20261124 diverifikasi melalui compare ref: HEAD saat refresh c6861410df8483d6cfb0c6e5aae8636244cb013b; compare dengan nama branch menghasilkan 0 ahead / 0 behind.
- PR #23 ditemukan pada pencarian PR open-only dengan judul [DRAFT]; tidak ada merge yang dilakukan. Base branch/SHA tetap work/phpunit-41-root-cause-20261123 / 1c33e5d422175f9bd85d206230271a9fe5a4eecd.
- Source aplikasi terakhir yang diaudit cd2b6ee8ed9d57604a06860e1dee399fb4b2b050. Compare cd2b6ee8 ke source commit paket 2f13517217bcdb8ffb591f4df7751bcc4b7354ab menunjukkan 4 commit ahead, 0 behind, hanya dua dokumen report/checkpoint berubah. Compare cd2b6ee8 ke HEAD branch c6861410 menunjukkan 7 commit ahead, 0 behind, juga hanya dua dokumen tersebut. Source aplikasi tidak berubah.
- Artifact workflow #45363631190 (artifact ID 14906520236) diunduh untuk audit source. SHA-256 artifact luar: be569407edfac045ac5194b85f29a3f776e6c1a350a45cf5a31bdb402465afcd. SHA-256 ZIP deployment di dalamnya: 3d5211059a5543cf2b05bc73c5fd4f12151eb1f28fc9b44fd7e526dfe1658194. Paket berisi 10.026 entry; manifest menunjuk source commit 2f135172..., PHP 8.3.37, Composer 2.11.2, Laravel 13.42.0. Manifest menyebut paket sebagai staging/deployment candidate, bukan persetujuan produksi.
- Snapshot audit diekstrak di /mnt/data/CBT_AUDIT_SNAPSHOT_2f135172/. Ini BUKAN checkout Git, tidak memiliki metadata .git, dan tidak dipindahkan menjadi workspace proyek. Snapshot dipakai read-only untuk pencarian source app/, migrations, seeders, dan factories. Folder tests tidak termasuk paket distribusi. Test preflight diambil terpisah dari GitHub pada source SHA yang diaudit.
- Pencarian Project/Library untuk nama baseline CBT REVISI 6 NOPEMBER 2026.zip tidak menemukan ZIP baseline; yang tersedia pada konteks file adalah dokumen laporan/checkpoint. ZIP baseline tidak diubah.
- Workspace /mnt/data/CBT_MADRASAH_RECONSTRUCTION/ tetap tidak ada. Git 2.47.3 tersedia; PHP 8.4.24 (bukan PHP 8.3); PDO driver kosong; Composer, MySQL client/server, Docker/Podman tidak tersedia; DNS/HTTPS terminal ke GitHub gagal karena Could not resolve host: github.com. Tidak ada checkout asli yang dapat dipulihkan melalui terminal.
- Fetch workflow runs dan combined status untuk HEAD dokumentasi c6861410 tidak mengembalikan run/status. Tidak ada klaim CI exact-HEAD c6861410. Bukti CI historis pada source tree yang sama tetap run #45363631214 (534 passed / 2.265 assertions) pada 2f135172; hasil itu tidak membuktikan P1-ID-03A selesai.

### AUDIT — writer profil, fixture, tenant, dan mass assignment

Pencarian source app/, migrations, seeders, dan factories dalam paket menghasilkan writer profil berikut:

1. app/Livewire/Admin/UserManagement.php::createUser() — writer aplikasi untuk akun baru. Dalam transaksi, membuat User lalu satu role GURU/SISWA dan membuat TeacherProfile atau StudentProfile. authorizeUserManagement() dipanggil pada mount dan setiap mutasi; mensyaratkan user terautentikasi, tenant, tepat satu role ADMIN_MADRASAH, dan permission user.manage. Validasi membatasi role ke GURU/SISWA; status Guru wajib PNS/PPPK/HONORER; PNS/PPPK wajib NIP 18 digit dan melarang NIK; Honorer wajib NIK 16 digit dan melarang NIP; Siswa wajib NISN 10 digit. Tenant diambil dari konteks/user server-side dan TenantScoped memaksa tenant dari konteks, bukan dari input browser.
   - Batas: unique validation dilakukan per kolom/tabel; collision lintas kategori belum diperiksa dan belum ada test preflight yang membuktikan role/profile untuk seluruh data lama.
2. app/Console/Commands/Phase7dBenchmarkCommand.php::seedWorkload() — writer fixture benchmark. Membuat TeacherProfile tanpa employment_status, NIP/NIK, dan employee_identifier; membuat StudentProfile dengan NISN pola NISN_%03d, bukan 10 digit numerik. Fixture belum sesuai kontrak identitas.
   - Command memakai SQLite benchmark terpisah dan migrate:fresh pada koneksi benchmark. Command tidak dijalankan pada audit ini; jangan gunakan sebagai bukti kompatibilitas identitas sebelum fixture dan cleanup saat exception diperbaiki/diuji.
3. app/Livewire/Teacher/TeacherProfilePage.php::savePhone() — memakai forceFill(['phone' => ...]) setelah validasi nomor telepon, otorisasi melalui TeacherPortalComponent/TeacherPortalAccess, tenant+user+profile query, dan row lock. Ini bukan writer identitas; hanya phone yang diubah.
4. Tidak ditemukan writer profil lain dalam source aplikasi yang dipaketkan. Pemakaian TeacherProfile/StudentProfile di TeacherSubjectAssignmentManagement, StudentRombelManagement, TeacherAccess, TeacherPortalAccess, TeacherPortalDataService, TeacherSubjectAssignmentController, dan LoginIdentityResolver yang diperiksa adalah pembacaan/validasi/penugasan, bukan update NIP/NIK/NUPTK/NISN.
5. Tidak ditemukan fill, forceFill, updateOrCreate, firstOrCreate, upsert, insert, query-builder update, atau raw SQL runtime yang menulis kolom identitas/tabel profil selain dua create di atas. forceFill pada TeacherProfilePage hanya menyentuh phone. Query preflight adalah SELECT/chunk/group count.
6. Database factories berisi 13 factory; tidak ada TeacherProfileFactory/StudentProfileFactory dan pencarian identitas pada seluruh factory tidak menemukan NIP/NIK/NUPTK/NISN. Lima seeder tidak membuat data identitas Guru/Siswa. Tests tidak ada dalam deployment package; hasil ini bukan klaim bahwa seluruh test fixtures repository-wide telah diaudit.
7. TeacherProfile fillable mencakup user_id, employee_identifier, employment_status, nip, nik, nuptk; StudentProfile fillable mencakup user_id dan nisn. Hidden hanya mengatur serialisasi, bukan otorisasi mass assignment.
8. Migration tenant integrity mendefinisikan composite FK profile-user tenant untuk TeacherProfile dan StudentProfile. Namun migration profil tidak menambahkan unique constraint pada user_id; relasi Eloquent hasOne saja tidak mencegah lebih dari satu profil untuk user yang sama. Preflight perlu memeriksa cardinality, kecocokan role, dan relasi user/profile. Keadaan DB aktual belum diperiksa.

### AUDIT — LoginIdentityPreflightCommand dan test

- LoginIdentityPreflightCommand::handle() memeriksa status Guru, format NIP/NIK/NUPTK/NISN, kesesuaian employee_identifier, string kosong, spasi tepi, dan duplikasi tenant per kolom. Secara statis jalur perintah hanya SELECT/chunk/group count dan tidak memanggil operasi tulis; belum ada test snapshot sebelum/sesudah yang membuktikan DB tidak berubah.
- Guard awal hanya memeriksa keberadaan tabel. Kolom yang dipakai SELECT tidak diperiksa satu per satu sebelum query. Kolom hilang dapat menghasilkan exception DB yang belum ditangani sebagai diagnostic fail-closed tersanitasi.
- Query profil tidak mengambil tenant_id dan user_id untuk memeriksa relasi dengan users. Belum ada pemeriksaan satu User memiliki tepat satu profil sesuai role, tidak ada profil silang, kategori PNS/PPPK tidak memiliki NIK login, Honorer tidak memiliki NIP login, dan collision lintas tabel/kategori.
- Duplikasi diperiksa terpisah untuk NIP, NIK, NUPTK, dan NISN. Tidak ada collision audit lintas jenis identitas dan username Admin.
- LoginIdentityPreflightCommandTest.php hanya memiliki tiga test: data valid, whitespace-padded Guru, dan Guru legacy tanpa status/identifier. Belum ada test per kategori anomali, schema missing table/column, role/profile cardinality, tenant mismatch, cross-category collision, cross-tenant duplicate allowance, null/blank/format lengkap, maupun DB immutability.
- Status preflight/test: PARTIAL / NOT COMPLETE.

### AUDIT — AuditService, AuditLog, dan PII

- Ditemukan 107 pemanggilan AuditService::log() di 33 file caller dalam source aplikasi yang dipaketkan. Fungsi yang memanggil:
  - API AnalyticsController: examSummary, examDistribution, examItems, rombelExamAnalytics, studentAnalytics, subjectAnalytics.
  - API AssessmentController: destroy.
  - API CurriculumAnalyticsController: studentAnalytics, rombelAnalytics, subjectAnalytics, tpAnalytics.
  - API CurriculumController: store, update, destroy; ElementController: store, update, destroy; ExamAnswerController: update.
  - API ExamAttemptController: store (dua event), submit (dua event); ExamController: store, update, addQuestions, assignRombel, generateToken, lock, removeQuestion, reorderQuestions, bulkRemoveQuestions, close, archive, publishResults, destroy.
  - API ExamParticipantController: store, destroy; GradeLevelController: store, update, destroy; LearningObjectiveController: store, update, destroy; LearningOutcomeController: store, update, destroy; LearningPathController: store, update, destroy.
  - API MediaController: store, attachToQuestionVersion, detachFromQuestionVersion, destroy; PhaseController: store, update, destroy; QuestionController: store, update (dua event), publish, import, destroy; QuestionPublicationController: store, destroy.
  - API ReportController: examResults, examSummary, studentResults, exportExcel, exportPdf, exportStudentPdf, exportSummaryPdf, rombelReport, subjectReport, questionReport; ScoringController: score, updateManualScore, finalize, recalculate; TeacherSubjectAssignmentController: store, update, destroy.
  - Livewire CurriculumRegistryManagement: registerVersion (dua event), mapSubject, retireMapping; ExamManagement: saveExam, generateToken, lockExam; QuestionBank: saveQuestion, publishQuestion, deleteQuestion; StudentRombelManagement: addStudentToRombel; TeacherSubjectAssignmentManagement: createAssignment; ResultList: toggleResultPublication; TeacherMaterialManagement: createMaterial, createTopic, publishMaterial, publishTopic; TeacherProfilePage: savePhone.
  - Service ExamProctorAssignmentService: revoke; ScoringService: scoreAttempt, reopenAttempt; TeacherPortal AssessmentCurriculumMappingService: sync; QuestionVersionCurriculumMappingService: sync; TeacherLearningOutcomeService: createTp, createAtp, publish.
- AuditService::log() meneruskan oldValues/newValues langsung ke AuditLog::create(); redactor recursive terpusat belum ada. Sebagian caller memasok toArray() model, sehingga redactor tidak boleh bergantung hanya pada atribut hidden model.
- Dua bypass runtime langsung ke AuditLog::create(): InstallerService (finalisasi PRE_ACTIVATION) dan ExpireExamAttemptsCommand (expire attempt). Migration 2026_11_23_130001_consolidate_legacy_local_roles melakukan insert audit historis melalui DB table; migration historis tidak boleh ditulis ulang sembarangan.
- Redaksi PII perlu terpusat untuk data audit baru, recursive atas nested arrays dan alias identitas, serta diterapkan juga pada dua bypass runtime. Histori audit lama tidak diubah.

### DESAIN — urutan penutupan P1-ID-03A

1. Perkuat schema guard preflight untuk semua tabel/kolom sebelum SELECT; diagnostic fail-closed tanpa nilai PII.
2. Tambahkan pemeriksaan tenant-user/profile relation, tepat satu profil sesuai role, tidak ada profil silang, kategori identitas saling eksklusif, format mentah, dan collision per tenant lintas username/identitas. Pertahankan semua identifier sebagai string agar leading zero tidak hilang.
3. Buat test terpisah untuk setiap anomali, pasangan valid, cross-tenant duplicate allowance, schema missing, no-PII output, dan snapshot DB sebelum/sesudah untuk bukti read-only.
4. Normalisasi fixture benchmark: status Guru eksplisit dan NIP/NIK sintetis sesuai kategori; NISN 10 digit numerik, unik per tenant. Pastikan cleanup benchmark aman saat exception.
5. Terapkan redactor recursive terpusat pada AuditService dan arahkan dua direct runtime AuditLog writer melalui sanitizer yang sama. Jangan mengubah data audit historis.
6. Tutup audit otorisasi/mass assignment pada writer, termasuk role, permission, tenant scope, validated payload, dan cardinality. Jangan menghapus fillable secara membabi buta.
7. Backup byte-exact per file sebelum source edit; catat source blob SHA, path backup, backup SHA-256, dan verifikasi cocok.
8. Jalankan focused dan full PHPUnit serta CI pada exact SHA hasil patch. STOP sebelum P1-ID-03B untuk review koordinator.

### IMPLEMENTASI

- Tidak ada source aplikasi, migration, workflow, dependency, konfigurasi, atau database yang diubah.
- Tidak ada patch source melalui GitHub Contents API.
- Tidak ada checkout Git asli yang pulih. Deployment artifact hanya menjadi snapshot audit terpisah.
- Tidak ada source backup baru karena tidak ada source yang diedit.
- Report ini diperbarui append-only sebagai dokumentasi audit; P1-ID-03A belum diimplementasikan.

### PENGUJIAN

- Audit statis dilakukan pada source snapshot paket yang manifest-nya menunjuk commit 2f135172; compare membuktikan perubahan dari source cd2b6ee8 ke snapshot hanya dua file dokumentasi.
- Inventory: dua writer profil identitas, satu phone-only forceFill, 13 factory tanpa identitas, lima seeder tanpa data identitas, 107 AuditService::log call di 33 caller files, dua direct runtime AuditLog::create bypass, satu migration historical insert.
- Tidak menjalankan PHP lint, Composer, PHPUnit, preflight, benchmark, migration, atau DB. Tidak ada bukti DB immutability runtime.
- CI exact source tree historis #45363631214: SUCCESS, focused 56 passed / 225 assertions, full 534 passed / 2.265 assertions. Tidak ada workflow/status baru pada HEAD dokumentasi c6861410.
- Produksi/staging tidak disentuh; tidak ada merge/deployment/migration.

### STATUS

- Git checkout/workspace writable: BLOCKED.
- Source audit dari deployment snapshot: PARTIAL-TO-EXTENDED; tests repository-wide belum tersedia dalam artifact.
- Identity writers app source: inventoried for packaged app.
- AuditService caller inventory: 107 calls / 33 files in packaged app.
- P1-ID-03A: PARTIAL / NOT COMPLETE.
- P1-ID-03B: BLOCKED / NOT STARTED.
- PR #23: OPEN / DRAFT / NOT MERGED.
- Production database: NOT TOUCHED.


---

## ADDENDUM — Audit source lanjutan P1-ID-03A dan refresh exact HEAD (24 November 2026)

### AUDIT

#### Repository, PR, dan HEAD
- Repository: `zane0184/cbt_november`.
- PR #23: OPEN / DRAFT / NOT MERGED.
- Branch: `work/p1-seven-targets-20261124`.
- HEAD saat refresh: `3106172e28aacccbc18019eed71374595c97e816`.
- Base branch: `work/phpunit-41-root-cause-20261123`; base SHA: `1c33e5d422175f9bd85d206230271a9fe5a4eecd`.
- Perbandingan `c6861410df8483d6cfb0c6e5aae8636244cb013b` ke HEAD `3106172e...`: 2 commit ahead, 0 behind; hanya report dan checkpoint P1 yang bertambah. Tidak ada perubahan source aplikasi pada rentang tersebut.
- Source aplikasi terakhir yang menjadi basis audit: `cd2b6ee8ed9d57604a06860e1dee399fb4b2b050`. Deployment snapshot dibuat dari commit `2f13517217bcdb8ffb591f4df7751bcc4b7354ab`; perbandingan source ke snapshot sebelumnya menunjukkan perubahan hanya dokumentasi. Snapshot bukan pengganti checkout Git.

#### Pemulihan workspace dan validasi snapshot
- Workspace kanonis `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tetap belum tersedia.
- Snapshot yang tersedia: `/mnt/data/CBT_AUDIT_SNAPSHOT_2f135172/CBT_MADRASAH_WEB_DEPLOYMENT/`.
- Snapshot memiliki `backend-laravel`, `composer.json`, `composer.lock`, dan `vendor/`; tidak memiliki direktori `.git`. Paket distribusi mengecualikan `tests/`, beberapa backup, dan file workspace non-distribusi. Karena itu snapshot hanya digunakan untuk audit statis, bukan sebagai checkout asli atau basis patch.
- Seluruh checksum dalam `SHA256SUMS.txt` lulus. Git blob SHA dari tujuh file inti yang dibandingkan cocok dengan tree GitHub: LoginIdentityPreflightCommand, AuditService, TeacherProfile, StudentProfile, UserManagement, TeacherProfilePage, dan Phase7dBenchmarkCommand.
- PHP lint lokal pada snapshot: 282 file PHP di app/database/routes/config/bootstrap, PHP 8.4.24, exit 0. Ini hanya lint sintaks pada snapshot; bukan PHPUnit dan bukan validasi PHP 8.3 lokal.
- Toolchain lokal tetap belum siap: PHP lokal 8.4.24 (bukan PHP 8.3), PDO drivers kosong, Composer tidak tersedia, MySQL client/server tidak tersedia, Docker/Podman tidak tersedia, DNS/HTTPS GitHub dari terminal gagal. Vendor di snapshot tidak menggantikan kebutuhan PHP 8.3 dan driver database.
- Tidak ada checkout Git asli yang berhasil dipulihkan. Tidak ada source edit lokal atau remote.

#### AUDIT — seluruh jalur writer profil dan identitas
1. `backend-laravel/app/Livewire/Admin/UserManagement.php` — `createUser()`
   - Satu writer aplikasi utama untuk pembuatan akun Guru dan Siswa: `TeacherProfile::query()->create()` dan `StudentProfile::query()->create()`.
   - Otorisasi diperiksa ulang pada mount dan setiap mutation: akun harus memiliki tenant, tepat satu role `ADMIN_MADRASAH`, serta permission `user.manage`.
   - Role yang dapat dibuat dibatasi ke `GURU` dan `SISWA`; akun privileged tidak dibuat lewat komponen ini.
   - Validasi Guru: PNS/PPPK wajib NIP 18 digit dan NIK dilarang; Honorer wajib NIK 16 digit dan NIP dilarang; NUPTK opsional 16 digit. Validasi Siswa: NISN wajib 10 digit.
   - Rule unique NIP/NIK/NUPTK/NISN dibatasi tenant. User dan profil dibuat dalam transaksi. `TenantScoped::bootTenantScoped()` mengisi tenant dari TenantContext/auth dan menimpa tenant_id yang dikirim; pembuatan gagal jika konteks tenant tidak ada.
   - Identitas disimpan sebagai string; validasi digit dan trim tidak mengubah leading zero.
2. `backend-laravel/app/Console/Commands/Phase7dBenchmarkCommand.php` — `seedWorkload()`
   - Writer fixture benchmark: `TeacherProfile::create()` membuat profil tanpa employment_status, NIP/NIK, dan employee_identifier.
   - `StudentProfile::create()` membuat NISN sintetis `NISN_%03d`, bukan 10 digit numerik. Field `nis` sintetis `NIS_%03d` adalah nomor internal, bukan login identifier.
   - `handle()` mengalihkan koneksi ke SQLite `database/benchmark.sqlite`, menjalankan migrate:fresh pada koneksi SQLite tersebut, lalu membangun workload dan menghapus file setelah operasi normal. Pembersihan belum berada di finally; exception dapat meninggalkan file benchmark. Ini bukan writer produksi, tetapi fixture harus dinormalisasi.
3. `backend-laravel/app/Livewire/Teacher/TeacherProfilePage.php` — `savePhone()`
   - Memakai `forceFill(['phone' => $phone])` saja; bukan perubahan NIP/NIK/NUPTK/employee_identifier.
   - `TeacherPortalAccess::profile()` memeriksa tenant akun, konsistensi TenantContext, user masih aktif pada tenant, tepat satu role GURU, dan tepat satu TeacherProfile untuk user/tenant.
   - Nomor telepon divalidasi, profil dikunci dalam transaksi, dan audit hanya mengirim field phone.
4. Jalur baca yang bukan writer:
   - `LoginIdentityResolver` membaca NIP untuk PNS/PPPK, NIK untuk Honorer, dan NISN untuk Siswa.
   - `TeacherAccess`, `TeacherPortalAccess`, `TeacherSubjectAssignmentManagement`, `StudentRombelManagement`, `ExamManagement`, `TeacherSubjectAssignmentController`, dan `TeacherPortalDataService` membaca profil/relasi untuk scope tenant dan penugasan.
5. Mass assignment:
   - `TeacherProfile::$fillable` mencakup user_id, employee_identifier, employment_status, nip, nik, nuptk, full_name, gender, phone; `$hidden` mencakup employee_identifier, nip, nik, nuptk.
   - `StudentProfile::$fillable` mencakup user_id, nis, nisn, full_name, gender dan data profil lain; `$hidden` mencakup nisn.
   - Pada app source yang diperiksa, tidak ditemukan pemanggilan `fill()`, `forceFill()`, `updateOrCreate()`, `firstOrCreate()`, `upsert()`, `insert()`, query-builder update, atau raw SQL yang menulis field identitas profil. Satu-satunya `forceFill()` pada profil adalah phone-only di atas. `DB::table('teacher_profiles')` dan `DB::table('student_profiles')` pada preflight hanya SELECT/chunk; migrasi yang ditemukan menyentuh skema/index/FK, bukan memperbarui nilai identitas.
   - Ini adalah audit writer pada app/database source dan fixture yang tersedia; tidak mengubah status P1-ID-03A menjadi selesai.

#### AUDIT — factories, seeders, dan test fixtures
- Tree repository memiliki 13 factory aktif; tidak ditemukan `TeacherProfileFactory` atau `StudentProfileFactory`, dan tidak ada factory yang mengisi NIP/NIK/NISN/NUPTK.
- Lima seeder aktif yang ditemukan: AssessmentTaxonomySeeder, CurriculumReferenceSourceSeeder, CurriculumRegistryBootstrapSeeder, DatabaseSeeder, SystemTenantSeeder. Tidak ditemukan pembuatan TeacherProfile/StudentProfile atau field identitas di lima seeder tersebut.
- `DemoDataSeeder.php` dan `RuntimeTestSeeder.php` disebut dalam catatan pengecualian paket distribusi, tetapi tidak ditemukan sebagai file pada tree branch yang diperiksa; isi keduanya tidak dinyatakan telah diaudit.
- Tree repository berisi 77 file PHP di bawah tests. Pemeriksaan referensi profil/identitas menemukan fixture valid/kontrak pada:
  - `LoginIdentityPreflightCommandTest`: tiga metode untuk identitas valid, NIP ber-spasi, dan Guru legacy tanpa status/identitas.
  - `AdminUiVerificationTest`: validasi PNS NIP, Honorer NIK, siswa NISN, kategori yang salah, tenant-scoped uniqueness, dan serialization hiding. Test hiding membuat satu Guru PNS dengan NIP sekaligus NIK; fixture campuran ini harus dipisah/diubah agar tetap sesuai kategori identitas.
  - `WebAdminAuthTest::setUp()`, `StudentWebLoginTest::setUp()`, `StudentExamPortalTest::setUp()`, `ParticipantAuthorizationAndAttemptLimitTest`, dan `RombelStudentPivotTest` memakai NISN numerik 10 digit atau identitas Guru sesuai skenario.
- Fixture test yang membuat profil tanpa identitas wajib dan perlu dinormalisasi/ditandai sebagai legacy secara eksplisit:
  - `ExamAnswerTest::setUp()`.
  - `ExamBuilderTest::setUp()`, `test_assign_rombel_api_does_not_reset_existing_participant_status()`, `test_add_individual_participant_api_does_not_reset_existing_status()`.
  - `ExamIntegrityTest::test_tenant_a_cannot_assign_student_from_tenant_b()`.
  - `ExamManagementHardeningTest::setUp()`, `teacher_cannot_use_an_assignment_outside_their_exact_assignment_scope()`.
  - `TeacherPracticeWorkflowTest::setUp()`; `Phase7BManualReviewTest::setUp()`; `Phase7CReopenTest::setUp()`.
  - `ExamSnapshotTest::setUp()`, `test_snapshot_from_another_tenant_cannot_be_mutated()`; `ExamVerificationTest::test_rombel_assignment_isolation_and_snapshot()`.
  - `MonitoringDuplicationTest::setUp()`; `MonitoringTest::setUp()`, `test_student_cannot_read_another_students_attempt_through_monitoring_endpoint()`, `test_student_cannot_read_another_students_attempt_through_nested_show()`.
  - `Analytics/CacheTest::finalization_invalidates_exam_and_rombel_analytics_cache()`; `ExamListContractTest::setUp()`; `Security/AnswerKeyLeakageTest::setUp()`; `RoleDashboardVerificationTest`.
  - `ProctorAssignmentSecurityTest::setUp()`, `test_database_rejects_cross_tenant_proctor_reference()`; helper `CreatesTeacherExamScope::createTeacherExamScope()` dan `attachStudentToRombel()`.
- Fixture identitas yang formatnya tidak sesuai:
  - `Phase7DHistoricalRecalculationAndAnalyticsTest::setUp()` memakai employee_identifier `GURU-A-001`, bukan NIP/NIK kategori yang valid.
  - `MediaAuthorizationTest::setUp()` memakai employee_identifier `GURU-MEDIA-1` dan `GURU-MEDIA-2`, bukan identitas login resmi.
  - `AdminUiVerificationTest::teacher_and_student_login_identifiers_are_hidden_from_automatic_model_serialization()` menggabungkan NIP dan NIK pada Guru PNS demi pengujian hidden serialization; fixture perlu dipecah menjadi data kategori valid.
- Fixture yang tidak memakai login identifier dapat tetap diperlukan untuk pengujian domain lain, tetapi harus diberi strategi eksplisit: factory/helper identitas valid, atau fixture legacy terisolasi yang tidak dicampur ke preflight acceptance dataset.

#### AUDIT — AuditService dan AuditLog
- Terinventarisasi 107 pemanggilan `AuditService::log()` pada 33 caller files:
  - API: AnalyticsController (examSummary, examDistribution, examItems, rombelExamAnalytics, studentAnalytics, subjectAnalytics); AssessmentController (destroy); CurriculumAnalyticsController (studentAnalytics, rombelAnalytics, subjectAnalytics, tpAnalytics); CurriculumController (store, update, destroy); ElementController (store, update, destroy); ExamAnswerController (update); ExamAttemptController (store, submit); ExamController (store, update, addQuestions, assignRombel, generateToken, lock, removeQuestion, reorderQuestions, bulkRemoveQuestions, close, archive, publishResults, destroy); ExamParticipantController (store, destroy); GradeLevelController (store, update, destroy); LearningObjectiveController (store, update, destroy); LearningOutcomeController (store, update, destroy); LearningPathController (store, update, destroy); MediaController (store, attachToQuestionVersion, detachFromQuestionVersion, destroy); PhaseController (store, update, destroy); QuestionController (store, update, publish, import, destroy); QuestionPublicationController (store, destroy); ReportController (examResults, examSummary, studentResults, exportExcel, exportPdf, exportStudentPdf, exportSummaryPdf, rombelReport, subjectReport, questionReport); ScoringController (score, updateManualScore, finalize, recalculate); TeacherSubjectAssignmentController (store, update, destroy).
  - Livewire: CurriculumRegistryManagement (registerVersion, mapSubject, retireMapping); ExamManagement (saveExam, generateToken, lockExam); QuestionBank (saveQuestion, publishQuestion, deleteQuestion); StudentRombelManagement (addStudentToRombel); TeacherSubjectAssignmentManagement (createAssignment); ResultList (toggleResultPublication); TeacherMaterialManagement (createMaterial, createTopic, publishMaterial, publishTopic); TeacherProfilePage (savePhone).
  - Services: ExamProctorAssignmentService (revoke); ScoringService (scoreAttempt, reopenAttempt); AssessmentCurriculumMappingService (sync); QuestionVersionCurriculumMappingService (sync); TeacherLearningOutcomeService (createTp, createAtp, publish).
- `AuditService::log()` saat ini meneruskan `oldValues` dan `newValues` langsung ke `AuditLog::create()`; redaksi PII recursive terpusat belum ada. Caller yang mengirim `toArray()` tidak boleh hanya mengandalkan `$hidden` model.
- Dua bypass runtime `AuditLog::create()`:
  - `ExpireExamAttemptsCommand::expireOne()`: audit EXPIRE_ATTEMPT; payload saat ini berisi status, expiry, score_id, dan skip reason.
  - `InstallerService::lockInstaller()`: audit PRE_ACTIVATION_FINALIZED; payload berisi status, license mode, schema fingerprint, masa berlaku pre-activation, migration count.
- Satu insert audit pada migration historis `2026_11_23_130001_consolidate_legacy_local_roles::up()` menggunakan `DB::table('audit_logs')->insert()`; payload role-retirement statis. Jangan mengubah histori/migration lama sebagai bagian dari redaksi audit baru.
- Redactor perlu recursive, case-insensitive, mengenali alias identitas (NIP/NIK/NISN/NUPTK/employee_identifier), bekerja pada nested arrays/JSON, dan semua runtime writer harus melewati sanitizer yang sama. Data audit historis tidak diubah.

#### AUDIT — preflight dan cakupan test saat ini
- `LoginIdentityPreflightCommand::handle()` bersifat read-only pada implementasi saat ini: SELECT/chunk dan query agregasi duplikasi; tidak ada statement mutasi.
- Pemeriksaan yang sudah ada: status Guru; NIP 18 digit PNS/PPPK; NIK 16 digit Honorer; format NIP/NIK/NUPTK; kesesuaian employee_identifier; nilai kosong/spasi tepi; NISN 10 digit; duplicate NIP/NIK/NUPTK/NISN per tenant.
- Gap: schema guard hanya memeriksa keberadaan tabel, bukan seluruh kolom sebelum SELECT; query tidak mengambil tenant_id/user_id sehingga tidak memverifikasi relasi User/Profile; belum memeriksa role/profile cardinality, profil silang, kategori identitas eksklusif, collision lintas kategori/username, serta anomali tenant_id null. Duplicate group memakai nilai mentah exact per kolom dan tidak memeriksa collision lintas kolom/tabel.
- `LoginIdentityPreflightCommandTest` hanya memiliki tiga metode: valid identities/no echo, whitespace padded identity, dan legacy Guru tanpa status/identity. Belum ada test per kategori anomali, missing table/column, duplicate group, role/cardinality, tenant relation, cross-category conflict, atau bukti snapshot DB sebelum/sesudah command untuk membuktikan immutability.
- `AdminUiVerificationTest` menguji validasi form dan unique constraint, tetapi bukan pengganti test kategori preflight dan bukti read-only.

### DESAIN — penyelesaian P1-ID-03A
1. Perkuat preflight schema guard untuk tabel dan seluruh kolom wajib sebelum SELECT; keluaran hanya kategori dan hitungan.
2. Tambahkan audit tenant-user-profile, tepat satu profil per role, tidak ada profil silang, kategori NIP/NIK eksklusif, format raw dan trimmed, duplicate per tenant, serta collision lintas kategori/username sesuai kontrak.
3. Tambahkan test per anomali, valid cases, cross-tenant allowance, missing schema, no-PII output, dan database snapshot before/after.
4. Normalisasi benchmark dan test fixtures; buat helper/factory khusus identitas valid dan fixture legacy terisolasi. Pastikan cleanup benchmark memakai finally.
5. Terapkan redactor recursive terpusat pada audit baru; alihkan dua bypass runtime ke sanitizer yang sama. Jangan mengubah histori audit.
6. Tutup review seluruh mass assignment/authorization, termasuk tenant context, role, permission, validated payload, dan cardinality.
7. Backup byte-exact per file sebelum setiap source edit. Catat source blob SHA, path backup, backup SHA-256, dan hasil verifikasi.
8. Jalankan focused dan full PHPUnit; verifikasi CI pada exact SHA. STOP sebelum P1-ID-03B untuk review koordinator.

### IMPLEMENTASI
- Tidak ada source aplikasi, migration, workflow, dependency, konfigurasi, atau database yang diubah.
- Tidak ada patch source melalui GitHub Contents API.
- Tidak ada checkout Git asli yang pulih; snapshot distribusi dipakai hanya untuk audit statis.
- Tidak ada source backup baru karena tidak ada source yang diedit.
- Laporan ini diperbarui append-only sebagai dokumentasi audit. P1-ID-03A belum diimplementasikan.
- Tidak ada migration baru, deployment, merge, atau akses database produksi.

### PENGUJIAN
- Local PHP lint pada snapshot: 282 file, PHP 8.4.24, PASS (exit 0). Ini bukan PHPUnit dan bukan PHP 8.3.
- GitHub Actions exact HEAD `3106172e28aacccbc18019eed71374595c97e816`:
  - Tenant Backend CI run #45367821198: SUCCESS; PHP 8.3, disposable MySQL 8, Composer lock/platform validation, PHP lint app/migrations/tests, focused login/tenant smoke 56 passed / 225 assertions, full PHPUnit 534 passed / 2,265 assertions.
  - ZIP workflow run #45367821057: SUCCESS untuk checkout, Composer validation, assembly dan upload archive saja. Artifact #14906229821, 19,768,211 bytes, SHA-256 `344ef163ab435c25915ce88fc93722f503dde586bee4ebb9bff9cd6ce1304169`. ZIP final tetap NOT ACCEPTED.
- CI PASS memvalidasi source tree yang diuji; tidak membuktikan P1-ID-03A selesai karena tidak ada patch P1-ID-03A pada source.
- Preflight lokal, PHPUnit lokal, Composer lokal, benchmark, migration, staging, UAT, dan production DB: NOT RUN / NOT TOUCHED.

### STATUS
- Git checkout asli + workspace kanonis: BLOCKED.
- Snapshot paket distribusi: checksum verified; source audit snapshot only.
- P1-ID-03A: PARTIAL / NOT COMPLETE.
- P1-ID-03B: BLOCKED / NOT STARTED.
- PR #23: OPEN / DRAFT / NOT MERGED.
- ZIP final: NOT ACCEPTED.
- Staging: NOT VERIFIED.
- Production: NOT TOUCHED.

### NEXT ACTION
Pulihkan checkout Git asli dan workspace writable lengkap beserta tests/backups, PHP 8.3, Composer locked dependencies, PDO MySQL, dan MySQL 8/InnoDB disposable. Setelah itu backup byte-exact setiap file, implementasikan P1-ID-03A saja, jalankan focused/full PHPUnit dan CI exact SHA. STOP sebelum P1-ID-03B untuk review koordinator.


---

## KOREKSI DAN ADDENDUM FINAL REFRESH P1-ID-03A — 24 November 2026

### AUDIT — koreksi jumlah test dan pendalaman writer
- Koreksi hitungan: Git tree exact source SHA `cd2b6ee8ed9d57604a06860e1dee399fb4b2b050` berisi **75 file PHP di tests/**, bukan 77. Angka 77 pada checkpoint sebelumnya adalah salah hitung. Seluruh 75 file tersebut telah diambil dan diperiksa secara statis; 27 file/concern memiliki referensi atau fixture TeacherProfile/StudentProfile.
- Contoh fixture yang belum mengikuti kontrak identitas: `Analytics/CacheTest` (StudentProfile hanya NIS; TeacherProfile hanya nama), `ExamAnswerTest` (StudentProfile tanpa NISN), `ExamBuilderTest`, `ExamIntegrityTest`, `ExamListContractTest`, `ExamManagementHardeningTest`, `ExamSnapshotTest`, `ExamVerificationTest`, `MonitoringTest`, `MonitoringDuplicationTest`, `Phase7BManualReviewTest`, `Phase7CReopenTest`, `ProctorAssignmentSecurityTest`, `QuestionBankTest`, `RoleDashboardVerificationTest`, `Security/AnswerKeyLeakageTest`, `TeacherPracticeWorkflowTest`, dan helper `CreatesTeacherExamScope`. `MediaAuthorizationTest` memakai employee_identifier `GURU-MEDIA-1/2`; `Phase7DHistoricalRecalculationAndAnalyticsTest` memakai `GURU-A-001`. Test negatif yang sengaja mengandung anomali harus tetap dipertahankan sebagai negative fixtures terisolasi.
- Pendalaman otorisasi: `UserManagement::createUser()` memeriksa tepat satu role ADMIN_MADRASAH + permission `user.manage`, membatasi role yang dibuat ke GURU/SISWA, dan membuat User/Profile dalam transaksi. Route group memasang auth + IdentifyTenant + license; IdentifyTenant mengambil tenant hanya dari akun terautentikasi. Trait TenantScoped menimpa tenant_id dari konteks server-side. `TeacherProfilePage::savePhone()` memakai TeacherPortalAccess yang memeriksa akun aktif, tenant cocok, tepat satu role GURU, dan tepat satu TeacherProfile; query update juga mengunci row berdasarkan tenant/user/profile.
- Temuan audit log tambahan: `TeacherProfilePage::savePhone()` mengirim old/new `phone` ke AuditService tanpa redaksi; PII redaction tidak boleh hanya menarget NIP/NIK/NISN/NUPTK. `UserManagement::createUser()` tidak memanggil AuditService/AuditLog, sehingga pembuatan akun/profil identitas belum terlihat memiliki event audit pada jalur tersebut.
- `Phase7dBenchmarkCommand::handle()` mengalihkan koneksi default ke file SQLite `database/benchmark.sqlite` dan menjalankan `migrate:fresh` pada koneksi sqlite, bukan database MySQL produksi. Namun cleanup file hanya terjadi pada jalur normal; belum berada dalam `finally`, sehingga exception dapat meninggalkan file benchmark. Command tidak dijalankan.
- Source snapshot audit: 187 app PHP, 13 factory, 5 seeder; tidak ada factory/seeder identitas. 107 AuditService::log() call di 33 caller files; dua direct runtime AuditLog::create() bypass (ExpireExamAttemptsCommand::expireOne dan InstallerService::lockInstaller); satu historical migration insert. Tidak ditemukan writer runtime lain ke kolom identitas profil selain UserManagement dan benchmark.
- Workspace asli tetap tidak ada. Snapshot `/mnt/data/CBT_AUDIT_SNAPSHOT_2f135172/` tersedia sebagai snapshot distribusi tanpa .git; bukan checkout dan tidak dijadikan workspace patch.
- Local lint yang benar-benar dijalankan pada 282 file project-owned (app, database, routes, bootstrap, config) lulus pada PHP 8.4.24. Ini bukan lint PHP 8.3, bukan PHPUnit, dan tidak mencakup tests karena tests tidak ada dalam paket snapshot. Scan semua 7.846 file PHP termasuk vendor tidak digunakan sebagai hasil lint: berhenti pada file vendor Symfony polyfill yang tidak kompatibel dengan interpreter lokal dan timeout.
- CI pada exact HEAD sebelum addendum ini `2598af967b22a8fa032cc910bb32230804a15390`: Tenant Backend CI #45369049022 SUCCESS; Composer validation/install, PHP lint app/migrations/tests, disposable MySQL 8, focused login/tenant smoke, dan full PHPUnit steps semuanya SUCCESS. ZIP workflow #45369048969 SUCCESS untuk build/archive; artifact #14907742542, 19,768,218 bytes. Build archive bukan ZIP acceptance.

### DESAIN
- Normalisasi fixture generik pada 27 file/concern dengan helper identitas valid berbasis string: NIP 18 digit untuk PNS/PPPK, NIK 16 digit untuk Honorer, NISN 10 digit untuk Siswa. Leading zero dipertahankan. Fixture anomali hanya untuk test negatif yang eksplisit.
- Tambahkan audit event untuk create akun/profil identitas melalui jalur aman dan redacted; pastikan phone/email dan PII lain juga disanitasi.
- Redactor recursive harus berjalan pada semua runtime writer, termasuk dua bypass AuditLog langsung; jangan mengubah histori.
- Tambahkan cleanup benchmark dalam finally dan guard agar hanya memakai file SQLite benchmark khusus.
- Koreksi preflight dan test coverage seperti daftar gap di addendum sebelumnya.

### IMPLEMENTASI
- Tidak ada source aplikasi, test, migration, workflow, dependency, konfigurasi, atau database yang diubah.
- Tidak ada source patch melalui GitHub Contents API.
- Tidak ada source backup baru karena tidak ada source yang diedit.
- Hanya laporan/checkpoint dokumentasi yang diperbarui.

### PENGUJIAN
- Local PHP lint: 282/282 file project-owned PASS pada PHP 8.4.24.
- CI exact SHA `2598af967b22a8fa032cc910bb32230804a15390`: run #45369049022 SUCCESS; seluruh step termasuk focused dan full PHPUnit SUCCESS. Angka assertion tidak diambil dari metadata job ini, sehingga tidak ditambahkan sebagai angka baru.
- ZIP #45369048969 SUCCESS untuk build/upload saja; final ZIP NOT ACCEPTED.
- Preflight read-only DB snapshot, fixture-normalization tests, redactor tests, dan PHPUnit lokal: NOT RUN / NOT IMPLEMENTED.

### STATUS / STOP
- P1-ID-03A: PARTIAL / NOT COMPLETE.
- P1-ID-03B: BLOCKED / NOT STARTED.
- PR #23: OPEN / DRAFT / NOT MERGED.
- Checkout Git asli/workspace kanonis: BLOCKED.
- Staging/ZIP acceptance: NOT VERIFIED / NOT ACCEPTED.
- Production database: NOT TOUCHED.
- STOP sebelum P1-ID-03B untuk review koordinator.


---

## ADDENDUM — Audit cardinality, AuditLog PII, dan fixture repository-wide (24 November 2026)

### Metadata dan exact SHA

- Repository: zane0184/cbt_november.
- Branch: work/p1-seven-targets-20261124.
- HEAD aktual sebelum addendum ini: 8ef06d1f800e9f3f9a197afe3be41c2f361d352c.
- HEAD yang diberikan pada instruksi: 3106172e28aacccbc18019eed71374595c97e816.
- Compare 3106172e... ke 8ef06d1f...: 5 commit ahead, 0 behind. File yang berubah hanya dokumentasi: ADDENDUM_RECOVERY_COORDINATION_REFRESH_2026-11-24.md, CHECKPOINT_P1_IMPLEMENTATION_UNBLOCK_2026-11-24.md, RECOVERY_COORDINATION_CORRECTION_2026-11-24.md, dan REPORT_P1_IMPLEMENTATION_UNBLOCK_2026-11-24.md. Tidak ada source aplikasi atau migration berubah pada rentang tersebut.
- Source aplikasi yang diaudit: cd2b6ee8ed9d57604a06860e1dee399fb4b2b050. Compare source tersebut ke HEAD 8ef06d1f... hanya menunjukkan empat file dokumentasi di atas; source aplikasi tidak berubah.
- PR #23: OPEN / DRAFT / NOT MERGED. Base branch work/phpunit-41-root-cause-20261123, base SHA 1c33e5d422175f9bd85d206230271a9fe5a4eecd.
- Semua test/factory/seeder/benchmark di bawah diambil dari Git tree/blob pada exact source SHA cd2b6ee8..., bukan dari deployment ZIP. Deployment snapshot hanya digunakan untuk pemeriksaan silang source aplikasi yang sama; tidak digunakan untuk mengambil test fixture.

### AUDIT 1 — seluruh migration users, profiles, tenant, dan foreign key

Git tree exact source SHA memuat 65 migration PHP; seluruh 65 file diperiksa untuk referensi users, teacher_profiles, student_profiles, tenant_id, softDeletes/deleted_at, foreign key, unique/index, dan operasi drop/rename. Migration yang membentuk atau mengubah lifecycle identitas:

| Migration | Blob SHA | Temuan |
|---|---|---|
| 0000_01_01_000000_create_tenants_table.php | fe8ec739a98e4e0447a2abb57b37c1d188cd59b3 | Tenant memiliki SoftDeletes dan kode unik. |
| 0001_01_01_000000_create_users_table.php | c63a5c360243211eb84d5c27c974ac4f0be5ec5b | users.id UUID; tenant_id FK ke tenants ON DELETE CASCADE; deleted_at tersedia; unique awal (tenant_id,email). |
| 2024_01_01_000001_create_profiles_tables.php | 6731605480aeb104d3e12db6cfef5903bff54262 | teacher_profiles dan student_profiles masing-masing memiliki user_id FK users ON DELETE CASCADE, tenant_id FK tenants ON DELETE CASCADE, dan softDeletes. Tidak ada UNIQUE(user_id). |
| 2026_11_07_210002_harden_teacher_assignment_tenant_foreign_keys.php | 53d8a59807ff72a53cbbc65948f6f402b26a5887 | Menambahkan candidate key users(id,tenant_id) dan teacher_profiles(id,tenant_id); mengganti FK teacher_profiles(user_id) menjadi composite (user_id,tenant_id) -> users(id,tenant_id), ON DELETE RESTRICT. Tidak menambah UNIQUE(user_id). Down mengembalikan FK user_id sederhana ON DELETE CASCADE. |
| 2026_11_09_230003_harden_rombel_student_tenant_integrity.php | 0e93869da9834fc6a15d547ffc4242ec4277410e | Menambahkan candidate key student_profiles(id,tenant_id), mengganti FK profil ke User menjadi composite (user_id,tenant_id), ON DELETE RESTRICT, dan mengikat pivot rombel_student ke tenant. Tidak menambah UNIQUE(user_id). Down mengembalikan FK sederhana ON DELETE CASCADE. |
| 2026_11_09_230004_harden_rombel_teacher_tenant_integrity.php | 9d32b137565bf124bf89d6e8a40e4ba7899d7a18 | Mengikat rombels.teacher_id + tenant_id ke teacher_profiles(id,tenant_id), ON DELETE RESTRICT. Ini menjaga tenant assignment, bukan cardinality profil per user. |
| 2026_11_11_000003_add_login_identity_fields.php | 03c8a08046357fd2138b2c0135fe8812315bcaf6 | Menambah username pada users, backfill username, unique (tenant_id,username), email menjadi nullable, dan index tenant/NIK. Tidak mengubah user_id profile cardinality. |
| 2026_11_24_160001_add_teacher_employment_status.php | 3284e6b050f2678fa455d91bcb40e43ea2947736 | Menambah employment_status nullable dan index tenant/status pada teacher_profiles. Tidak mengubah user_id cardinality. |
| 2026_11_24_170001_enforce_tenant_login_identity_uniques.php | 11eb38296af04769562738f6fae491b0a36a019e | Menambah unique tenant-scoped pada teacher NIP/NIK/NUPTK dan student NISN. Tidak menambah UNIQUE(user_id), dan index identitas tidak mengecualikan deleted_at. |

Blob model pada exact source SHA:
- app/Models/User.php: dec82ba26e785708945bdea1bc0e34a1ed20458d.
- app/Models/TeacherProfile.php: 192d9804d4e926e7827d6a379542825da3fc80d0.
- app/Models/StudentProfile.php: 95ce4b3033b6cc4406a42e2e4a6045cff01218b8.

Temuan model dan lifecycle:
1. User::teacherProfile() dan User::studentProfile() didefinisikan hasOne(). TeacherProfile dan StudentProfile menggunakan SoftDeletes. Namun tidak ada migration yang memberi UNIQUE(user_id) pada salah satu tabel profil. Relasi hasOne adalah kontrak ORM, bukan constraint cardinality database.
2. Tidak ada database constraint yang mencegah satu user memiliki beberapa teacher_profiles, beberapa student_profiles, atau sekaligus satu profil Guru dan satu profil Siswa. Composite FK hanya menjamin user_id dan tenant_id menunjuk ke User dalam tenant yang sama.
3. Migration users memiliki deleted_at, tetapi model User tidak menggunakan trait SoftDeletes dan tidak ditemukan global scope deleted_at. Sebaliknya, kedua model profil menggunakan SoftDeletes. Secara statis ini berarti deleted_at pada users tidak otomatis memberi perilaku soft delete Eloquent; pemanggilan delete() pada User berpotensi menjadi hard delete. Karena FK profil yang aktif saat ini ON DELETE RESTRICT, hard delete User yang masih memiliki baris profil dapat ditolak database. Ini adalah implikasi schema/model yang belum diuji runtime.
4. Soft-deleted profile tidak dihapus dari tabel dan tetap menjadi target FK. Query Eloquent biasa pada profil mengecualikan row deleted_at, sehingga satu User dapat tampak tidak memiliki profil lalu dibuatkan profil baru; tanpa UNIQUE(user_id), beberapa row profil dapat tersimpan.
5. Unique index tenant-scoped pada NIP/NIK/NUPTK/NISN tidak memasukkan deleted_at. Dengan demikian identitas non-null pada profil soft-deleted tetap dicadangkan oleh unique index. Ini bisa merupakan kebijakan anti-reuse yang disengaja, tetapi belum ada keputusan tertulis apakah identitas boleh dipakai ulang setelah profil dihapus.
6. Candidate key (id,tenant_id) pada User/Profile tidak sama dengan unique user_id. Tidak ditemukan tabel history profile terpisah.
7. Tidak ada query terhadap database legacy aktual dalam audit ini. Karena itu jumlah user yang memiliki duplikat profile, cross-role profile, row orphan, atau row soft-deleted tidak diketahui dan tidak boleh diasumsikan nol.

Kesimpulan cardinality:
- Desain saat ini mengisyaratkan satu profil melalui hasOne, tetapi database belum menegakkannya.
- Menambah UNIQUE(user_id) langsung belum aman sebelum keputusan apakah profil soft-deleted adalah riwayat yang harus dipertahankan atau row yang dapat dipulihkan.
- Belum dibuat migration apa pun.

### DESAIN 1 — keputusan cardinality dan rancangan verifikasi sebelum migration

Keputusan yang perlu disahkan sebelum implementasi:
1. Aturan bisnis: apakah satu akun hanya boleh memiliki satu row profil sepanjang masa, atau satu profil aktif dengan beberapa versi historis?
2. Apakah restore profil soft-deleted harus mengembalikan row lama? Apakah profil baru boleh dibuat setelah profil lama di-soft-delete?
3. Apakah NIP/NIK/NISN yang pernah dipakai profil soft-deleted tetap dicadangkan selamanya? Unique index sekarang membuatnya tetap tercadangkan.
4. Bagaimana lifecycle penghapusan User: soft delete melalui trait SoftDeletes, atau hard delete hanya setelah arsip/histori dipindahkan?
5. Apakah role Guru/Siswa dan tipe profile harus saling eksklusif untuk seluruh masa akun, atau dapat berubah dengan histori?

Rancangan preflight read-only sebelum migration:
- Hitung duplikat teacher_profiles.user_id dan student_profiles.user_id, dipisah antara deleted_at IS NULL dan semua row.
- Hitung User yang memiliki lebih dari satu profil aktif, lebih dari satu profil historis, atau kedua tipe profil sekaligus.
- Periksa kesesuaian role User dengan jenis profil, termasuk user tanpa role, multi-role, dan role yang tidak sesuai.
- Periksa tenant_id User vs profil, orphan user_id, tenant/profile mismatch, serta status deleted_at pada User dan profil.
- Periksa restore collision: profil deleted yang jika dipulihkan akan bentrok dengan profil aktif atau unique identity index.
- Periksa FK dan unique index aktual melalui information_schema untuk MySQL dan PRAGMA index_list/foreign_key_list untuk SQLite.
- Output hanya hitungan dan kategori; jangan mencetak NIP/NIK/NISN mentah.

Alternatif desain yang perlu dibandingkan, bukan langsung dipilih:
- Jika tidak ada histori profil: UNIQUE(user_id) sederhana dapat cocok, tetapi restore/recreate harus memakai row lama dan kebijakan penghapusan harus jelas.
- Jika histori profil dipertahankan: tegakkan satu profil aktif per user dengan strategi yang kompatibel per DB. MySQL 8 dapat menggunakan generated nullable active_user_id yang bernilai user_id hanya ketika deleted_at IS NULL, lalu unique index; SQLite dapat menggunakan partial unique index WHERE deleted_at IS NULL. Jangan menggunakan UNIQUE(user_id,deleted_at) sebagai asumsi satu row aktif karena NULL pada unique index dapat mengizinkan beberapa row aktif.
- Untuk eksklusivitas Guru/Siswa lintas dua tabel, unique index per tabel tidak cukup. Pertimbangkan satu registry/profile discriminator per User atau validasi domain transaction + preflight; pilihan harus sesuai kebutuhan histori dan role.
- Selaraskan User model dan users.deleted_at: aktifkan SoftDeletes hanya jika itu memang lifecycle yang disetujui, kemudian uji dampaknya pada Sanctum/session, relasi, audit, dan FK. Jangan menambah trait hanya untuk menutupi mismatch tanpa test.

Test wajib sebelum persetujuan migration:
- MySQL 8/InnoDB dan SQLite dengan foreign key enforcement aktif.
- satu profil aktif per user; duplicate active profile ditolak.
- profile historis soft-deleted + profile aktif baru sesuai aturan yang dipilih.
- restore profil lama dengan/ tanpa collision.
- user memiliki kedua jenis profile; role/profile mismatch.
- tenant mismatch dan orphan FK ditolak.
- hard delete User dengan profil aktif maupun soft-deleted.
- unique login identity pada row aktif dan soft-deleted.
- migration fresh, upgrade dari fixture legacy, rollback, serta recovery dari kegagalan DDL.
- Rencana rollback harus mengembalikan index/column/constraint tanpa menghapus histori; sebelum migration diperlukan full backup database dan snapshot metadata constraint. Perhatikan DDL MySQL dapat implicit commit sehingga rollback tidak boleh diasumsikan atomik.

### AUDIT 2 — AuditService 107 callsites dan payload

AuditService source blob e968cc401cdd8b04ab1322171d5f475332a55372 meneruskan oldValues/newValues langsung ke AuditLog::create(), tanpa sanitizer atau redactor. Inventory static exact source tree menemukan 107 pemanggilan di 33 caller files. Payload shape yang ditemukan meliputi model toArray(), snapshot variabel, literal metadata, validated subset, report filters, dan exception message. Tidak ditemukan penggunaan request->all() sebagai payload audit pada 107 callsite; ada validated subset dan filter yang dipilih secara eksplisit.

Matriks caller, fungsi/event, payload, dan risiko:

| Caller file | Jumlah | Fungsi / event | Bentuk payload old/new | Risiko PII / catatan |
|---|---:|---|---|---|
| Http/Controllers/Api/V1/AnalyticsController.php | 6 | examSummary/ANALYTICS_VIEW; examDistribution/ANALYTICS_VIEW; examItems/ITEM_ANALYSIS_VIEW; rombelExamAnalytics/ANALYTICS_VIEW; studentAnalytics/ANALYTICS_VIEW; subjectAnalytics/ANALYTICS_VIEW | metadata ID atau tidak ada old/new | Rendah langsung; studentAnalytics memakai entity User ID yang dapat ditautkan. |
| Http/Controllers/Api/V1/AssessmentController.php | 1 | destroy/DELETE | Assessment.toArray() pada old_values | Snapshot model luas; created_by dan context snapshot perlu allowlist. |
| Http/Controllers/Api/V1/CurriculumAnalyticsController.php | 4 | studentAnalytics/ANALYTICS_CURRICULUM_STUDENT_VIEW; rombelAnalytics/ANALYTICS_CURRICULUM_ROMBEL_VIEW; subjectAnalytics/ANALYTICS_CURRICULUM_SUBJECT_VIEW; tpAnalytics/ANALYTICS_CURRICULUM_TP_VIEW | Tidak mengirim old/new | Rendah langsung; event akses analytics dan entity IDs tetap linkable. |
| Http/Controllers/Api/V1/CurriculumController.php | 3 | store/CREATE; update/UPDATE; destroy/DELETE | Curriculum.toArray() dan snapshot old | Snapshot model luas; perlu allowlist. |
| Http/Controllers/Api/V1/ElementController.php | 3 | store/CREATE; update/UPDATE; destroy/DELETE | Element.toArray() dan snapshot old | Snapshot model luas; perlu allowlist. |
| Http/Controllers/Api/V1/ExamAnswerController.php | 1 | update/SAVE_ANSWER | Metadata attempt_id, snapshot_id, state, revision_number | Payload yang dicatat hanya state/revision, bukan answer_payload atau isi jawaban. |
| Http/Controllers/Api/V1/ExamAttemptController.php | 4 | store/START_ATTEMPT (2 event); submit/EXPIRE_ATTEMPT atau SUBMIT_ATTEMPT; submit/SCORING_BLOCKED | Metadata attempt; device_fingerprint; exception message | Tinggi: fingerprint perangkat mentah; exception message berpotensi membawa detail sensitif/SQL. |
| Http/Controllers/Api/V1/ExamController.php | 13 | store/CREATE; update/UPDATE; addQuestions/ADD_QUESTIONS; assignRombel/ASSIGN_ROMBEL; generateToken/ROTATE_EXAM_TOKEN; lock/PUBLISH_EXAM; removeQuestion/REMOVE_QUESTION; reorderQuestions/REORDER_QUESTIONS; bulkRemoveQuestions/BULK_REMOVE_QUESTIONS; close/CLOSE_EXAM; archive/ARCHIVE; publishResults/PUBLISH_EXAM_RESULTS atau UNPUBLISH_EXAM_RESULTS; destroy/DELETE | Tiga event CRUD mengirim Exam.toArray(); event lain metadata/token_present | Tinggi: Exam.toArray() memuat field token karena model Exam tidak mendefinisikan hidden untuk token. Event ROTATE_EXAM_TOKEN sendiri hanya menyimpan boolean token_present, bukan token mentah. |
| Http/Controllers/Api/V1/ExamParticipantController.php | 2 | store/ADD_PARTICIPANT; destroy/REMOVE_PARTICIPANT | user_id pada metadata | Sedang: internal User ID dapat ditautkan ke siswa. |
| Http/Controllers/Api/V1/GradeLevelController.php | 3 | store/CREATE; update/UPDATE; destroy/DELETE | GradeLevel.toArray() dan snapshot old | Snapshot model luas; umumnya data akademik dan ID. |
| Http/Controllers/Api/V1/LearningObjectiveController.php | 3 | store/CREATE; update/UPDATE; destroy/DELETE | LearningObjective.toArray() dan snapshot old | Snapshot model luas; perlu allowlist. |
| Http/Controllers/Api/V1/LearningOutcomeController.php | 3 | store/CREATE; update/UPDATE; destroy/DELETE | LearningOutcome.toArray(), termasuk currentVersion pada update | Snapshot konten akademik dan metadata; periksa nested version. |
| Http/Controllers/Api/V1/LearningPathController.php | 3 | store/CREATE; update/UPDATE; destroy/DELETE | LearningPath.toArray(), termasuk currentVersion pada update | Snapshot konten akademik dan metadata; periksa nested version. |
| Http/Controllers/Api/V1/MediaController.php | 4 | store/UPLOAD_MEDIA; attachToQuestionVersion/ATTACH_QUESTION_VERSION_MEDIA; detachFromQuestionVersion/DETACH_QUESTION_VERSION_MEDIA; destroy/DELETE_MEDIA | Metadata media/owner dan original_name | Sedang: nama file bebas dapat memuat nama siswa, nomor, atau data pribadi. |
| Http/Controllers/Api/V1/PhaseController.php | 3 | store/CREATE; update/UPDATE; destroy/DELETE | Phase.toArray() dan snapshot old | Snapshot model luas; perlu allowlist. |
| Http/Controllers/Api/V1/QuestionController.php | 6 | store/CREATE_QUESTION; update/CREATE_VERSION; update/UPDATE_DRAFT; publish/PUBLISH_QUESTION; import/IMPORT_QUESTIONS; destroy/ARCHIVE_QUESTION | Question.toArray() pada create; sisanya metadata | Question.toArray() memiliki owner_user_id/created_by/updated_by yang linkable; payload import hanya counts, bukan row error. |
| Http/Controllers/Api/V1/QuestionPublicationController.php | 2 | store/SHARE; destroy/UNSHARE | Validated share_scope dan publication metadata | Rendah langsung; ID publikasi/tenant tetap linkable. |
| Http/Controllers/Api/V1/ReportController.php | 10 | examResults/REPORT_VIEW; examSummary/REPORT_VIEW; studentResults/REPORT_VIEW; exportExcel/REPORT_EXPORT_EXCEL; exportPdf/REPORT_EXPORT_PDF; exportStudentPdf/REPORT_EXPORT_PDF; exportSummaryPdf/REPORT_EXPORT_PDF; rombelReport/REPORT_VIEW; subjectReport/REPORT_VIEW; questionReport/REPORT_VIEW | Metadata dan filters status, grading_status, rombel_id | Rendah langsung berdasarkan field yang dipilih; rombel_id adalah ID internal, bukan NISN/NIP/NIK. |
| Http/Controllers/Api/V1/ScoringController.php | 4 | score/SCORE_ATTEMPT; updateManualScore/UPDATE_MANUAL_SCORE; finalize/FINALIZE_SCORE; recalculate/RECALCULATE_SCORE | updateManualScore mengirim ExamQuestionScore.toArray() sebelum/sesudah | Tinggi: model berisi student_id, grader_id, evaluated_answer_snapshot, feedback, dan skor. Ini data asesmen siswa yang sensitif. |
| Http/Controllers/Api/V1/TeacherSubjectAssignmentController.php | 3 | store/TEACHER_ASSIGNMENT_CREATE; update/TEACHER_ASSIGNMENT_UPDATE; destroy/TEACHER_ASSIGNMENT_DEACTIVATE | TeacherSubjectAssignment.toArray() dan snapshot old | Sedang: teacher_profile_id, created_by, dan curriculum context snapshot adalah ID/konteks yang dapat ditautkan. |
| Livewire/Admin/CurriculumRegistryManagement.php | 4 | registerVersion/CURRICULUM_VERSION_REREGISTERED; registerVersion/CURRICULUM_VERSION_REGISTERED; mapSubject/CURRICULUM_SUBJECT_MAPPED; retireMapping/CURRICULUM_SUBJECT_MAPPING_RETIRED | Snapshot status/tanggal dan metadata ID | Rendah langsung; batasi snapshot pada field yang diperlukan. |
| Livewire/Admin/ExamManagement.php | 3 | saveExam/EXAM_CREATED atau EXAM_UPDATED; generateToken/ROTATE_EXAM_TOKEN; lockExam/PUBLISH_EXAM | Metadata assessment/assignment/question IDs, token_present boolean, status | Rendah untuk payload saat ini; token mentah tidak dimasukkan pada event rotate. |
| Livewire/Admin/QuestionBank.php | 3 | saveQuestion/CREATE_QUESTION atau UPDATE_QUESTION; publishQuestion/PUBLISH_QUESTION; deleteQuestion/ARCHIVE_QUESTION | Snapshot kode soal, subject/status/version IDs | Rendah langsung; pastikan tidak memperluas ke isi soal/answer key. |
| Livewire/Admin/StudentRombelManagement.php | 1 | addStudentToRombel/STUDENT_ADDED_TO_ROMBEL | student_profile_id, student_user_id, rombel_id, academic_year_id | Sedang: User ID siswa linkable. |
| Livewire/Admin/TeacherSubjectAssignmentManagement.php | 1 | createAssignment/TEACHER_SUBJECT_ASSIGNMENT_CREATED | teacher_profile_id dan academic context IDs | Rendah–sedang: ID profil guru linkable. |
| Livewire/Teacher/ResultList.php | 1 | toggleResultPublication/PUBLISH_EXAM_RESULTS atau UNPUBLISH_EXAM_RESULTS | show_result boolean | Rendah. |
| Livewire/Teacher/TeacherMaterialManagement.php | 4 | createMaterial/TEACHER_MATERIAL_CREATED; createTopic/TEACHER_MATERIAL_TOPIC_CREATED; publishMaterial/TEACHER_MATERIAL_PUBLISHED; publishTopic/TEACHER_MATERIAL_TOPIC_PUBLISHED | Material/topic IDs, code, status snapshots | Rendah langsung; metadata akademik. |
| Livewire/Teacher/TeacherProfilePage.php | 1 | savePhone/TEACHER_PROFILE_PHONE_UPDATED | old phone dan new phone | Tinggi: nomor telepon guru disimpan mentah di old_values/new_values. |
| Services/ExamProctorAssignmentService.php | 1 | revoke/EXAM_PROCTOR_ASSIGNMENT_REVOKE | Snapshot termasuk proctor_user_id dan revoke_reason | Sedang–tinggi: alasan bebas 10–1.000 karakter dapat berisi PII. |
| Services/ScoringService.php | 2 | scoreAttempt/SCORE_ATTEMPT atau RECALCULATE_SCORE; reopenAttempt/REOPEN_SCORE | Metadata skor, snapshot old/new, reason | Sedang: nilai akademik dan alasan bebas dapat mengandung data pribadi. |
| Services/TeacherPortal/AssessmentCurriculumMappingService.php | 1 | sync/ASSESSMENT_CURRICULUM_MAPPED | teacher assignment, CP/outcome/material IDs | Rendah langsung; nested arrays berisi ID. |
| Services/TeacherPortal/QuestionVersionCurriculumMappingService.php | 1 | sync/QUESTION_VERSION_CURRICULUM_MAPPED | assignment, CP/outcome/material IDs dan mapping count | Rendah langsung; nested arrays berisi ID. |
| Services/TeacherPortal/TeacherLearningOutcomeService.php | 3 | createTp/TEACHER_LEARNING_OUTCOME_CREATED; createAtp/TEACHER_LEARNING_OUTCOME_CREATED; publish/TEACHER_LEARNING_OUTCOME_PUBLISHED | outcome code/type, parent IDs, assignment IDs, status snapshot | Rendah langsung; batasi snapshot pada metadata. |

Dua bypass runtime AuditLog::create() yang tidak melewati AuditService:
- app/Services/InstallerService.php, fungsi finalisasi PRE_ACTIVATION: payload berisi tenant/admin/installation/license IDs, mode, tanggal pre-activation, migration_count, dan schema fingerprint. Tidak tampak secret private key, tetapi tetap perlu satu sanitizer/contract audit yang sama.
- app/Console/Commands/ExpireExamAttemptsCommand.php, fungsi expire attempt: payload status, expires_at, processed_at, score_id, dan scoring_skipped_reason. Tidak tampak PII langsung.

Migration historis dipisahkan dari writer runtime:
- database/migrations/2026_11_23_130001_consolidate_legacy_local_roles.php melakukan DB::table('audit_logs')->insert() untuk event LEGACY_ROLE_ACCESS_RETIRED. Payload mencatat role name/id dan flag migrasi; entity_id adalah User ID. Ini insert historis satu kali, bukan writer runtime; jangan mengubah histori/migration lama tanpa rencana khusus.

Temuan lintas payload:
- Exam model memiliki token pada fillable dan tidak mendefinisikan hidden untuk token. ExamController CRUD yang mengirim Exam.toArray() berpotensi menyimpan token ujian mentah.
- ExamQuestionScore.toArray() dapat menyimpan evaluated_answer_snapshot, feedback, student_id, dan grader_id.
- TeacherProfilePage menyimpan nomor telepon lama/baru.
- ExamAttemptController menyimpan device_fingerprint mentah dan exception message.
- MediaController menyimpan original_name; ExamProctorAssignmentService menyimpan revoke_reason; ScoringService menyimpan reason. Semua string bebas perlu dianggap berpotensi memuat PII.
- AuditService juga selalu menyimpan Request::ip() dan Request::userAgent(); IP dan user-agent adalah metadata perangkat/jaringan yang dapat ditautkan.
- UserManagement::createUser() membuat User, role, dan profile dalam transaksi serta memiliki authorization gate, tetapi tidak memanggil AuditService atau AuditLog. Event pembuatan akun/profil belum tercatat dalam audit.
- Tidak ada redaksi terpusat. Hidden pada model tidak cukup karena AuditService menerima array eksplisit dan toArray() dari model lain.

### DESAIN 2 — rekomendasi perlindungan AuditLog

1. Terapkan sanitizer recursive pada satu boundary audit terpusat untuk seluruh audit baru, termasuk nested arrays, model toArray(), alias field, dan string metadata.
2. Gunakan allowlist payload per event sebagai lapisan utama. Jangan menyimpan seluruh toArray() model secara default.
3. Redact/mask kunci sensitif case-insensitive dan canonicalized: nip, nik, nisn, nuptk, employee_identifier, nis, phone, email, address, parent_name, password, token, secret, authorization, cookie, device_fingerprint, answer_payload, evaluated_answer_snapshot, dan field alias identifier.
4. Jangan simpan token ujian mentah; simpan status boolean seperti token_present atau fingerprint token dengan keyed HMAC bila benar-benar diperlukan untuk korelasi.
5. Jangan simpan device_fingerprint mentah; gunakan keyed HMAC/pseudonym yang tidak dapat dibalik dari log. Jangan mencatat exception getMessage() mentah; gunakan exception class + stable error code.
6. Masking nomor telepon/email dan sanitasi filename/revoke_reason/reason harus dilakukan sebelum persist. Batasi panjang string dan cegah payload nested yang tak terduga.
7. Tinjau retensi IP/user-agent, user_id/entity_id, dan audit data asesmen siswa; jangan menganggap ID pseudonim otomatis anonim.
8. Arahkan dua bypass runtime ke sanitizer/contract yang sama. Jangan rewrite historical audit rows atau migration historis.
9. Tambahkan test untuk nested arrays, key alias, mixed case, toArray, string bebas, token, phone/email, device fingerprint, dan bukti payload yang disimpan sudah tersanitasi.

### AUDIT 3 — seluruh test, factory, seeder, fixture, benchmark

Git tree tests pada exact source SHA cd2b6ee8... memiliki 84 entry: 75 file PHP, 2 file MJS, dan 7 direktori. Semua 75 blob PHP serta dua file MJS diambil dan dipindai; tidak ada file fixture data non-code tambahan pada tests tree. Hasil:
- 31 pemanggilan langsung TeacherProfile::create() pada 20 file PHP test.
- 37 pemanggilan langsung StudentProfile::create() pada 26 file PHP test.
- 27 file/concern test berbeda mempunyai referensi atau fixture TeacherProfile/StudentProfile.
- Pemanggilan tersebut mayoritas adalah relational/workflow fixtures, bukan test login identity. Banyak profile sengaja hanya memiliki user_id/full_name agar test ujian, assignment, proktor operasional, monitoring, dan kurikulum dapat membangun relasi.
- Tidak ditemukan test yang membuktikan unique cardinality user_id untuk teacher_profiles/student_profiles, soft-delete/recreate/restore lifecycle, atau user hard-delete terhadap FK profile.

Nilai identitas literal dan kategori yang ditemukan pada test:
- NIP: 198001012006041001; 198001012006041002; 198001012006041003; 198765432100000001; 199001012021041001. Negative fixture: ' 19800101200604100' (spasi depan dan panjang tidak valid).
- NIK: 3171000000000001; 3171000000000002; 3171000000000003; 3171000000000004; 3171000000000005. Negative fixture: 317100000000000 (15 digit).
- NISN: 0087654321; 0000000123; 0001234567; 0001234568; 0098765432; 1000000001; 1000000002; 2000000001; 1234567890; 9000000001; 9000000002.
- NUPTK: 1234567890123456; 1234567890123457.
- Kategori Guru eksplisit yang dipakai: PNS, PPPK, HONORER. Banyak relational fixtures tidak mengisi employment_status/NIP/NIK.
- Negative/intentional fixture: AdminUiVerificationTest menggunakan profil berstatus PNS dengan NIP dan NIK sekaligus untuk menguji serialization/hidden. Itu bukan contoh identitas valid; jangan normalisasi sebelum tujuan test dipisahkan.
- LoginIdentityPreflightCommandTest sengaja membuat Guru legacy dengan employment_status/NIP/NIK null dan Guru dengan NIP whitespace untuk menguji preflight.
- StudentWebLoginTest dan WebAdminAuthTest memakai NISN 0001234567; AdminUiVerificationTest juga memakai beberapa NISN dengan leading zero.
- Legacy field NIS, bukan NISN, masih dipakai oleh test lain: ANS- + random 8 chars, NIS-A1, NIS-A2, NIS-B1, '123', dan '12345'. Field NIS tidak boleh diperlakukan sebagai fallback login NISN.
- employee_identifier noncanonical pada relational fixtures: GURU-MEDIA-1, GURU-MEDIA-2, dan GURU-A-001. Ini bukan NIP/NIK dan belum boleh diganti tanpa memahami fungsi test.
- ProctorAssignmentSecurityTest membuat TeacherProfile untuk akun dengan role PROKTOR tanpa employment_status/NIP/NIK. Ini fixture penugasan operasional; jangan menganggapnya sebagai keputusan portal Proktor permanen atau mengubahnya tanpa memahami test.
- AdminUiVerificationTest juga memiliki negative cases untuk Guru tanpa NIP/NIK, kategori silang, dan panjang format invalid. Negative fixtures harus tetap ada sebagai negative tests, bukan dinormalisasi menjadi data valid.

Factories dan seeders pada exact source SHA:
- 13 database factories dan 5 seeders diambil dari GitHub. Tidak ditemukan TeacherProfileFactory/StudentProfileFactory atau nilai NIP/NIK/NISN/NUPTK pada factory/seeder. UserFactory juga tidak mengisi identitas login.
- Phase7dBenchmarkCommand.php blob 335e126e9fef534094eb737c69165e60cbac8e6a adalah satu-satunya benchmark profile writer yang ditemukan.
- Benchmark memakai SQLite terisolasi, tetapi cleanup file database berada setelah operasi benchmark dan bukan dalam finally. Jika migrate/seed/benchmark melempar exception sebelum cleanup, file database dapat tertinggal.
- Benchmark TeacherProfile dibuat hanya dengan tenant_id, user_id, full_name; tidak ada employment_status, NIP/NIK, atau employee_identifier.
- Benchmark StudentProfile mengisi NIS sprintf('NIS_%03d', $s) dan NISN sprintf('NISN_%03d', $s), bukan 10 digit numerik. Nilai NISN benchmark tidak sesuai kontrak login final.
- Benchmark tidak dijalankan pada audit ini. Tidak ada fixture yang dinormalisasi.

### DESAIN 3 — klasifikasi fixture sebelum normalisasi

- Pisahkan canonical identity fixtures untuk test login/validasi dari relational fixtures untuk test domain lain.
- Negative fixtures (wrong category, length invalid, whitespace, null legacy) harus dipertahankan dan diberi nama/tujuan test eksplisit.
- Relational fixtures yang tidak membutuhkan login tidak boleh otomatis diberi NIP/NIK/NISN palsu sebelum memastikan apakah preflight dijalankan pada database test tersebut.
- Legacy NIS dan employee_identifier placeholder bukan login identifier; tentukan apakah field itu tetap dibutuhkan untuk kompatibilitas data lama.
- Benchmark harus menggunakan identitas sintetis valid, unik, string fixed-length, dan kategori employment_status eksplisit; cleanup database SQLite harus berada di finally. Ini rekomendasi, belum implementasi.

### AUDIT 4 — pemulihan workspace

- /mnt/data/CBT_MADRASAH_RECONSTRUCTION/ tidak ditemukan.
- Tidak ditemukan direktori .git atau checkout Git CBT pada /mnt/data.
- ZIP baseline CBT REVISI 6 NOPEMBER 2026.zip tidak ditemukan di /mnt/data; pencarian Project/Library sebelumnya juga tidak menemukan file baseline tersebut.
- Deployment snapshot /mnt/data/CBT_AUDIT_SNAPSHOT_2f135172/ tetap bukan checkout Git; tidak memiliki .git dan tidak digunakan untuk patch.
- Git CLI tersedia. PHP lokal 8.4.24, bukan PHP 8.3. Tidak tersedia binary php8.3, Composer, MySQL client/server, Docker/Podman; PDO driver kosong.
- DNS/HTTPS terminal ke github.com gagal dengan Could not resolve host: github.com.
- GitHub connector dapat membaca tree/blob/workflow tetapi tidak memberi writable checkout atau binary source checkout dengan metadata Git.
- Tidak dibuat repository sintetis, tidak dibuat working tree dari snapshot, dan tidak ada source diubah melalui GitHub Contents API.

### IMPLEMENTASI

- Tidak ada source aplikasi, migration, dependency, workflow, konfigurasi, atau database yang diubah.
- Tidak ada migration cardinality dibuat.
- Tidak ada source backup baru karena tidak ada source edit.
- Hanya dokumen report/checkpoint diperbarui secara append-only.
- P1-ID-03A belum selesai; audit ini bukan bukti implementasi.

### PENGUJIAN

- Audit statis migration: 65 file pada exact source SHA; tidak ada test/runtime DB dijalankan.
- Audit statis tests: 75 PHP + 2 MJS pada exact source SHA; semua blob diambil dan dipindai. Test tidak dijalankan ulang pada audit ini.
- Audit statis factories/seeders: 13 factories + 5 seeders.
- Audit statis AuditService: 107 callsites / 33 caller files; 2 runtime bypass; 1 historical migration insert.
- Tidak menjalankan PHP lint, Composer, PHPUnit, preflight, benchmark, MySQL 8, SQLite migration test, atau database cardinality query.
- Workflow runs dan combined status untuk HEAD 8ef06d1f... tidak mengembalikan run/status. Jangan klaim CI PASS pada exact HEAD tersebut.
- Bukti CI terdahulu yang tercatat dalam recovery: run #45369049022 SUCCESS pada exact SHA 2598af967b22a8fa032cc910bb32230804a15390; report sebelumnya tidak menyediakan angka assertion final. Itu bukan exact HEAD saat ini dan tidak membuktikan P1-ID-03A selesai.
- ZIP workflow #45369048969 pada SHA 2598 SUCCESS hanya untuk build/archive; ZIP final tetap NOT ACCEPTED.
- Workspace lokal PHPUnit/Composer/MySQL tidak tersedia. Produksi, staging, migration, deployment, dan merge tidak disentuh.

### STATUS

- Current branch HEAD sebelum addendum ini: 8ef06d1f800e9f3f9a197afe3be41c2f361d352c.
- Source aplikasi terakhir yang diaudit: cd2b6ee8ed9d57604a06860e1dee399fb4b2b050.
- P1-ID-03A: PARTIAL / NOT COMPLETE.
- Cardinality design: AUDIT COMPLETE / BUSINESS RULE NOT DECIDED / NO MIGRATION.
- Audit PII: INVENTORY COMPLETE FOR 107 CALLSITES / CENTRAL REDACTION NOT IMPLEMENTED.
- Test fixture audit: 75 PHP + 2 MJS scanned / NO NORMALIZATION.
- Workspace checkout: BLOCKED.
- P1-ID-03B: BLOCKED / NOT STARTED.
- PR #23: OPEN / DRAFT / NOT MERGED.
- Production database: NOT TOUCHED.
- STOP sebelum migration atau P1-ID-03B untuk review koordinator.
