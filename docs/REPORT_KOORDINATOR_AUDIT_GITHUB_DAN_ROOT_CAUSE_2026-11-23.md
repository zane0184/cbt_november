

## PEMUTAKHIRAN FINAL HANDOVER — 23 NOVEMBER 2026

### AUDIT — CI exact HEAD terbaru

CI [#45220055382](https://github.com/zane0184/cbt_november/actions/runs/45220055382) selesai SUCCESS pada exact HEAD `856294037312c186f03422071368bcaa2987516a`, branch `work/phpunit-41-root-cause-20261123`, job `laravel-mysql` ID `137851568268`. Environment PHP 8.3 + disposable MySQL 8.0.46/InnoDB. Focused login/tenant smoke **27 passed/90 assertions**; full PHPUnit **518 passed/0 failed/2,189 assertions**. Setup, isolated DB checks, installer DB prep, Composer/dependencies, package discovery, test, dan cleanup steps sukses.

### IMPLEMENTASI — dokumen handover

Ditambahkan pada branch remediasi:
- `docs/HANDOVER_AGENT_BARU_2026-11-23_P0_518_PASS.md` — status kanonis, exact SHA/CI, daftar perubahan P0, backup, keputusan owner, PR/issue, backlog, matriks kesiapan dan protokol kerja.
- `docs/PROMPT_AGENT_BARU_LANJUTAN_2026-11-23.md` — prompt operasional untuk agent berikutnya, dengan larangan mengulang P0.
- `docs/AGENT_START_HERE.md` diberi banner entry point menuju dua dokumen tersebut.
- Checkpoint ini ditambahkan bukti CI #45220055382.
- Prompt lama diberi tanda superseded agar branch/status historis tidak dijadikan instruksi aktif.

### PENGUJIAN

Bukti CI #45220055382 menjalankan smoke dan seluruh PHPUnit pada exact HEAD 8562940. Perubahan dokumentasi handover setelahnya tidak mengubah source/test aplikasi; tetap jalankan CI pada HEAD terbaru setelah perubahan dokumentasi selesai bila workflow memicu run baru.

### CHECKPOINT

PR #22 tetap Draft dan belum digabung. Agent selanjutnya mulai dari P1-A: review PR #22 dan minta persetujuan owner sebelum merge ke branch eksekusi. Setelah itu lanjutkan Proktor Mode B, ADM-01–23, Web Guru, Web Siswa, provenance kurikulum, dan acceptance VPS/ZIP. Jangan mengulang 41 failure.
# LAPORAN KOORDINATOR — AUDIT GITHUB, PEMULIHAN ROOT CAUSE PHPUNIT, DAN BACKLOG WEB CBT

Tanggal pembaruan: 23 November 2026  
Repository: `zane0184/cbt_november`  
Branch sumber eksekusi: `work/solo-web-cbt-completion-20261123-execution`  
Source baseline: `d6aaeae6f2851bf15272d0cc2bce9e68989d3a4d`  
Branch perbaikan terisolasi: `work/phpunit-41-root-cause-20261123`  
SHA yang diuji CI sukses: `085a20eea16ada12e8c055fca6be9bb1d6d6972a`

**STATUS: P0 FULL PHPUNIT PASS**

**P0 PHPUnit lengkap telah lulus 518/518.** Ini bukan bukti seluruh aplikasi siap produksi, siap ujian madrasah, atau siap distribusi ZIP. Portal Admin/Guru/Siswa, Proktor, import, provenance kurikulum, E2E, recovery, VPS, dan paket ZIP masih mempunyai backlog.

## A. AUDIT

### A1. Sumber GitHub yang diperiksa

Dokumen dan sumber yang dibaca langsung dari GitHub:

- `docs/AGENT_START_HERE.md`
- `docs/HANDOVER_AGENT_BARU_PHPUNIT_2026-11-23.md`
- `docs/REPORT_HANDOVER_AGENT_PENGGANTI_2026-11-23.md`
- `docs/PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md`
- `docs/CHECKPOINT_SOLO_WEB_CBT_2026-11-23.md`
- `docs/CHECKPOINT_LANJUTAN_WEB_CBT_2026-11-23.md`
- `docs/REPORT_P0_TRIAGE_FULL_PHPUNIT_2026-11-23.md`
- `docs/MATRIKS_ADM_01_23_AUDIT_2026-11-23.md`
- `docs/DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md`
- [Checkpoint koordinator](CHECKPOINT_KOORDINATOR_2026-11-23_P0_PHPUNIT_518_PASS.md)
- [Entry point agent](AGENT_START_HERE.md)
- Master spesifikasi Web Admin, Web Guru, dan Web Siswa yang ditautkan owner.
- Issue #8, #9, #10, #12, #14, #15, #16, #20 dan keputusan/comment yang relevan.
- Semua PR terbuka yang relevan, branch, compare, exact commit, workflow, job, dan log CI.

Tidak digunakan ZIP baseline atau ChatGPT Library. Tidak ada perubahan pada branch sumber eksekusi, `main`, database produksi, atau VPS.

### A2. HEAD dan CI

| Item | Kondisi terverifikasi |
|---|---|
| `main` HEAD | `a17b77aa19eb9ba4a01cff44387dc144aca6f740` |
| Branch eksekusi | `work/solo-web-cbt-completion-20261123-execution` |
| HEAD branch eksekusi | `d6aaeae6f2851bf15272d0cc2bce9e68989d3a4d` |
| Branch perbaikan | `work/phpunit-41-root-cause-20261123` |
| SHA source/test yang diuji pertama kali | `085a20eea16ada12e8c055fca6be9bb1d6d6972a` |
| SHA branch HEAD dokumentasi yang diuji ulang | `6b5e412e18e5c9230c0b17f18a9d569bdd4d7982` |
| SHA HEAD PR #22 yang diuji ulang | `2899baf49e3432322643d5fa72b3a08a715852ef` |
| CI baseline #45191014821 | FAILURE — 41 failed, 477 passed, 1.867 assertions |
| CI perbaikan #45215342323 | **SUCCESS — 518 passed, 0 failed, 2.189 assertions** |
| CI dokumentasi/branch HEAD #45217367885 | **SUCCESS — 518 passed, 0 failed, 2.189 assertions** |
| CI HEAD PR #22 #45218279043 | **SUCCESS — 518 passed, 0 failed, 2.189 assertions** |
| CI checkpoint PR #22 #45219173631 | **SUCCESS — 518 passed, 0 failed, 2.189 assertions** |

Bukti: [CI #45191014821](https://github.com/zane0184/cbt_november/actions/runs/45191014821), [CI #45215342323](https://github.com/zane0184/cbt_november/actions/runs/45215342323), dan [CI #45217367885](https://github.com/zane0184/cbt_november/actions/runs/45217367885), serta [CI HEAD PR #22 #45218279043](https://github.com/zane0184/cbt_november/actions/runs/45218279043), serta [CI checkpoint PR #22 #45219173631](https://github.com/zane0184/cbt_november/actions/runs/45219173631).

Environment CI:
- PHP 8.3.
- Disposable MySQL 8.0.46/InnoDB.
- MySQL identity check, Composer lock/dependency install, installer E2E database, login/tenant smoke test, dan full PHPUnit lulus pada run #45215342323.

Handover yang menyebut 92 failed pada run #45168433247 dan 41 failed pada #45191014821 adalah riwayat. **Status PHPUnit terbaru adalah run #45215342323, 518 passed/0 failed.**

### A3. Pengelompokan awal tepat 41 kegagalan

| Akar masalah pada baseline | Test suite | Jumlah | Bukti |
|---|---|---:|---|
| Fixture mapping kurikulum tidak mengisi `academic_year_id` dan `semester_id` wajib | ExamBuilderTest (7), ExamManagementHardeningTest (1), ExamSnapshotTest (15), AnswerKeyLeakageTest (7) | 30 | MySQL 1364: academic_year_id tidak memiliki default |
| Fixture historical recalculation mengganti registration/version assignment yang masih direferensikan | Phase7DHistoricalRecalculationAndAnalyticsTest | 4 | MySQL 1451 pada `tlosm_assignment_scope_fk` |
| Test Livewire masih memakai kontrak lama `selectedRombelId`/ALL dan asumsi lama | ExamManagementHardeningTest | 4 | Validation/authorization/participant/snapshot assertions |
| Test rate limit tidak mengganti cached Sanctum guard saat berpindah bearer token | RateLimitingAndAbuseProtectionTest | 1 | User B menerima 429 karena identitas request tidak terisolasi |
| Query role peserta memakai `role_user.tenant_id` yang tidak ada; test kedua gagal lanjut karena Exam tidak terbentuk | TeacherPracticeWorkflowTest | 2 | MySQL 1054, lalu Attempt to read property id on null |

Total 30 + 4 + 4 + 1 + 2 = 41.

### A4. Temuan lanjutan selama remediasi

Selain kelompok awal, CI berikutnya membantu mengungkap fixture dan kontrak tambahan yang harus diperbaiki:

- Cross-tenant fixture memakai curriculum/phase global yang sama padahal unique key mengharuskan identitas curriculum berbeda.
- Test dua koneksi melakukan commit fixture; cleanup harus menghapus exam/assessment, mapping, local tenant registration, dan registry System Tenant disposable dalam urutan FK child-first.
- Attempt API membutuhkan identitas Sanctum personal access token yang persisten, bukan transient test guard, serta fencing generation pada request execution.
- Test akses assignment Guru lain harus menangkap `ModelNotFoundException` sebagai penolakan fail-closed, bukan menganggap Livewire akan selalu mengembalikan HTTP 403.
- SHORT_ANSWER ditolak oleh kontrak snapshot saat ini; test diubah untuk membuktikan penolakan tersebut dan tetap menguji kerahasiaan metadata ESSAY. SHORT_ANSWER tidak diaktifkan.

### A5. Batas klasifikasi

- Tidak mengubah kolom wajib menjadi nullable/default untuk meluluskan fixture.
- Tidak menghapus FK historical mapping.
- Tidak menghidupkan kembali akses global Guru atau akses peserta lintas assignment.
- Tidak menaikkan/menonaktifkan rate limiter.
- Tidak melemahkan exact-role, tenant context, Device Lease, fencing, snapshot, atau policy.
- Perubahan query `role_user.tenant_id` adalah bug source nyata: tenant berada pada tabel `users`, bukan pivot `role_user`.

## B. DESAIN DAN ARAHAN PRODUK YANG BERLAKU

Arahan lengkap ada pada [ADDENDUM ARAHAN OWNER](ADDENDUM_ARAHAN_OWNER_INSTALASI_ROLE_PROKTOR_2026-11-23.md). Ringkasan:

1. Satu madrasah = satu instalasi ZIP dan satu database akademik terpisah. Tidak ada shared multi-tenant antar-madrasah. `tenant_id`/System Tenant yang masih ada adalah konteks lokal/defense-in-depth, bukan SaaS lintas madrasah.
2. Role aktif tepat ADMIN_MADRASAH, GURU, SISWA. Operator dilebur ke Admin; Kamad dibekukan; histori legacy tidak dihapus diam-diam.
3. Proktor bukan role/portal. Proktor adalah Mode B/menu tambahan pada Web Guru melalui assignment sementara Exam+Room.
4. Admin hanya menetapkan/mencabut Guru Proktor untuk ujian terpusat UTS/UAS/UM. Admin tidak mengelola Room/peserta/roster, tidak melihat monitoring peserta, dan tidak melakukan tindakan Attempt/perangkat.
5. Web Guru Proktor menu: Tugas aktif, Jadwal tugas, Detail ujian/ruang, Roster peserta ruang, Monitoring peserta, Catatan kehadiran/insiden, Riwayat penugasan.
6. Proktor room-scoped dan monitoring-only; tidak melihat soal/kunci/jawaban/nilai/bank/configuration, tidak melakukan Reset/Force Submit/extension/takeover.
7. Tidak ada ExamSession, SessionRoom, session_number, wave, session_token, atau session lifecycle. Room bukan admission gate; waktu server dan Exam schedule adalah acuan.
8. Android dan Web Super Admin ditunda sampai Juni 2027. Web Super Admin masa depan hanya lisensi/aktivasi/entitlement/pembayaran.
9. Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + delapan dimensi profil lulusan merupakan satu ekosistem, bukan modul terpisah.

## C. IMPLEMENTASI P0 DI BRANCH TERISOLASI

Branch dibuat dari baseline exact d6aaeae6. Perubahan source/test:

| File | Perubahan |
|---|---|
| `app/Livewire/Admin/ExamManagement.php` | Query peserta memakai `users.tenant_id` alih-alih kolom pivot yang tidak ada. Bug source diperbaiki. |
| `tests/Concerns/CreatesCanonicalCurriculumContext.php` | Fixture AssessmentCurriculumMapping mengisi academic_year_id dan semester_id dari assignment kanonis. |
| `tests/Feature/Phase7DHistoricalRecalculationAndAnalyticsTest.php` | Fixture memakai canonical curriculum context stabil; tidak mengganti registration/version yang sudah direferensikan. |
| `tests/Feature/ExamManagementHardeningTest.php` | Test mengikuti assignment-scoped builder, menolak assignment Guru asing yang valid, dan menguji exception fail-closed. |
| `tests/Feature/ExamBuilderTest.php` | Question dan QuestionVersion dibuat dengan status PUBLISHED sesuai kontrak snapshot. |
| `tests/Feature/ExamSnapshotTest.php` | Fixture published/curriculum lengkap; cross-tenant curriculum fixture valid; cleanup dua koneksi dan registry disposable mengikuti FK child-first. |
| `tests/Feature/RateLimitingAndAbuseProtectionTest.php` | Reset cached auth guard saat mengganti bearer token dan isolasi identitas User A/User B. |
| `tests/Feature/Security/AnswerKeyLeakageTest.php` | PAT Sanctum persisten, fencing generation, mapping question yang valid; SHORT_ANSWER fail-closed dan metadata ESSAY tidak bocor. |
| `tests/Feature/TeacherPracticeWorkflowTest.php` | Jadwal test dibuat di masa depan sesuai validasi server. |

Backup byte-exact disimpan di:
`backend-laravel/backup_before_patch/PHPUNIT_41_ROOT_CAUSE_20261123/`

Terdapat baseline backup utama dan backup per iterasi round2–round6. Workflow CI juga memiliki salinan sebelum branch trigger di `.github/workflows/backup/`. Backup `AGENT_START_HERE.md` dan dokumen laporan/addendum berada di `docs/backup/`.

### Hasil implementasi dan test

- CI #45215342323: **518/518 passed, 0 failed, 2.189 assertions**.
- Exact SHA diuji: `085a20eea16ada12e8c055fca6be9bb1d6d6972a`.
- Tidak ada migration produksi, deployment VPS, merge, atau ZIP final.
- [PR #22](https://github.com/zane0184/cbt_november/pull/22) telah dibuat sebagai Draft ke branch eksekusi; mergeable state clean. CI #452191 lulus pada HEAD 2899baf. Tidak ada merge otomatis.

## D. INVENTARIS PR DAN ISSUE GITHUB TERBUKA

### D1. PR terbuka

| PR | Fokus | Status dan arahan integrasi |
|---|---|---|
| [#22](https://github.com/zane0184/cbt_november/pull/22) | Remediasi 41 PHPUnit failures | Draft, base branch eksekusi d6, HEAD 2899baf, mergeable clean; CI #452182 dan #452191 lulus 518/0. Jangan merge otomatis. |
| [#21](https://github.com/zane0184/cbt_november/pull/21) | Solo Web CBT curriculum/portal integration | Draft, HEAD `13bf440`, base `work/solo-web-cbt-ci-base-20261123`; bukan branch eksekusi d6. Stale/divergen; jangan dianggap source aktif atau merge tanpa compare. |
| [#19](https://github.com/zane0184/cbt_november/pull/19) | Web Guru remove XLSX question import path | Draft; targeted tests dilaporkan PASS pada SHA lama. Reconcile dengan Master Guru §5.8 dan Issues #8/#9/#16 sebelum integrasi. |
| [#18](https://github.com/zane0184/cbt_november/pull/18) | Web Admin remediation | Draft; sebagian konsep Room/Proktor. Diff membawa field/session lifecycle yang bertentangan dengan aturan serentak/no ExamSession; jangan merge apa adanya. |
| [#17](https://github.com/zane0184/cbt_november/pull/17) | Audit/design lima kontrak Web Guru | Draft dokumentasi; gunakan sebagai audit, bukan bukti implementasi. |
| [#13](https://github.com/zane0184/cbt_november/pull/13) | Web Siswa IndexedDB answer draft/submit barrier | Draft; UI/queue/test branch tersedia, tetapi real-Laravel browser E2E, offline recovery, dan sync integration belum terbukti. |
| #7/#6/#5/#4/#3/#1 | Handover Admin, master spec, UI, installer, backend CI, Fase1C | Draft berantai/bercabang. Audit compare exact source sebelum mengambil file; jangan merge PR lama berurutan tanpa memastikan perubahan sudah masuk d6. |

### D2. Issue terbuka

| Issue | Scope | Status kerja |
|---|---|---|
| [#20](https://github.com/zane0184/cbt_november/issues/20) | Eksekusi Web Admin ADM-01 s.d. ADM-23 | Checklist audit memiliki 20 PARTIAL, 2 NOT VERIFIED, 1 BLOCKED. Pekerjaan Admin belum selesai. |
| [#16](https://github.com/zane0184/cbt_november/issues/16) | XLSX question import wizard | Konflik dokumentasi dengan PR #19 dan Master Guru §5.8; perlu rekonsiliasi sebelum coding. |
| [#15](https://github.com/zane0184/cbt_november/issues/15) | Verified reference data/provenance Pembelajaran Mendalam dan 8 DPL | Source registry tersedia sebagian; artifact/checksum verification belum boleh diklaim selesai. |
| [#14](https://github.com/zane0184/cbt_november/issues/14) | Versioned CP/TP/ATP/KBC mapping dan immutable snapshot | Sebagian mapping/snapshot ada; provenance sampai QuestionVersion/Exam Snapshot/report masih perlu audit dan test. |
| [#12](https://github.com/zane0184/cbt_november/issues/12) | Master spesifikasi Web Admin | Kontrak fitur Admin wajib diaudit satu per satu. |
| [#10](https://github.com/zane0184/cbt_november/issues/10) | Kontrak lama Exam–ExamSession | Superseded oleh Master Admin §8.5 dan arahan owner: no ExamSession/session lifecycle. Jangan implementasikan model lama. |
| [#9](https://github.com/zane0184/cbt_november/issues/9) | DOCX question import | Kontrak parser/security/DTO; parser belum terbukti selesai. |
| [#8](https://github.com/zane0184/cbt_november/issues/8) | Kontrak import XLSX/PDF | Satu DTO/batch/lifecycle untuk parser berbeda; PDF/OCR ditunda; SHORT_ANSWER blocked. |

### D3. Konflik format import soal

Master Guru §5.8 dan Issues #8/#9/#16 menjelaskan XLSX dan DOCX sebagai parser berbeda dengan satu DTO/batch/lifecycle. PR #19 menyebut keputusan owner 23 November untuk menghapus jalur XLSX soal lama. Untuk sementara:
- jangan menghidupkan kembali importer XLSX lama yang hanya menerima JSON `rows[]`;
- jangan menghapus XLSX Admin untuk Guru/Siswa;
- jangan menambah batch/storage kedua;
- rekonsiliasi keputusan PR #19 dengan master spec sebelum parser soal berikutnya;
- DOCX belum boleh dianggap implemented hanya karena kontraknya ada;
- PDF/OCR ditunda; SHORT_ANSWER tetap BLOCKED.

## E. BACKLOG TERARAH SETELAH P0

### P1 — Backend dan keamanan lintas portal
1. Integrasi Mode B Proktor: marker UTS/UAS/UM eksplisit, Exam+Room assignment, interval/overlap concurrency, revoke, audit, route/menu Guru, roster/monitoring room-scoped, negative tests.
2. Pastikan tidak ada route/portal Kamad/Operator/Super Admin lokal yang dapat diakses lewat URL langsung; legacy roles fail-closed.
3. Periksa single-install invariant: satu madrasah per database, installer, local System Tenant, license installation identity.
4. Selesaikan provenance kurikulum/versi/sumber dan snapshot akademik.

### P1 — Web Admin
1. Jalankan audit semua ADM-01–23 dengan source/UI/database/permission/test bukti.
2. Prioritaskan profil madrasah, logo/branding, kop dokumen, data Guru/Siswa, import/export, tahun/semester, kelas/rombel/mapel, assignment Guru, kurikulum, laporan/cetak, audit dan pengaturan.
3. Admin hanya mengelola assignment Proktor UTS/UAS/UM; bukan monitoring peserta/room/Attempt.

### P1 — Web Guru
1. Selesaikan lima kontrak: question authoring, question lifecycle, assessment/exam lifecycle, scoring/grading/history, Proktor Mode B.
2. DOCX parser dan keputusan XLSX harus mengikuti kontrak bersama; preview/correction/revalidation, no auto-publish, idempotency, security, audit.
3. Scoring harus menggunakan immutable Exam Snapshot. ESSAY manual review; SHORT_ANSWER tetap blocked.
4. Selesaikan hasil, histori nilai, analytics, export, profil dan pengaturan sesuai scope.

### P1 — Web Siswa
1. Integrasikan UI/IndexedDB queue ke endpoint Laravel/Sanctum dan Device Lease/fencing.
2. Buktikan start/resume/answer autosave/submit/retry/offline queue/duplicate replay/conflict/recovery.
3. Uji browser E2E dengan data MySQL 8, multi-device dan network interruption. UI test saja tidak cukup.

### P2 — Paket dan deployment VPS
1. Audit installer fresh install dan upgrade path pada DB disposable.
2. Siapkan satu ZIP source lengkap dan dependensi yang diperlukan; tanpa backup internal, temporary files, test fixtures, secret, atau .env produksi.
3. README untuk aaPanel File Manager: upload/extract, buat DB/user via aaPanel, tempatkan .env oleh owner, permission storage/cache, PHP 8.3, MySQL 8, Nginx, queue/scheduler, SSL, backup/restore.
4. Verifikasi struktur ZIP, clean extraction, fresh install, health checks, login Admin/Guru/Siswa, API, storage, scheduler dan backup.
5. Jangan nyatakan SIAP DIUPLOAD sebelum acceptance ini selesai.

### DEFERRED — Juni 2027
- Android native client.
- Web Super Admin pusat untuk lisensi/aktivasi/entitlement/pembayaran.
- Penundaan ini tidak boleh menjadi blocker Web CBT.

## F. PENGUJIAN DAN STATUS KESIAPAN

| Pengujian | Status |
|---|---|
| PHP 8.3 + disposable MySQL 8.0.46/InnoDB | PASS pada CI #45215342323 |
| Composer/dependencies/package discovery | PASS pada CI #45215342323 |
| Installer E2E database | PASS pada CI #45215342323 |
| Login dan identitas tenant smoke | PASS pada CI #45215342323 |
| Full PHPUnit | **PASS — 518 tests, 0 failed, 2.189 assertions** |
| Browser E2E real Laravel Admin/Guru/Siswa | NOT VERIFIED |
| Proktor Exam+Room full integration | NOT VERIFIED |
| DOCX/XLSX real upload parser E2E | NOT VERIFIED |
| Offline recovery/network interruption | NOT VERIFIED |
| Multi-device/lease/fencing UAT | NOT VERIFIED |
| Load/stability/security penetration | NOT RUN |
| Fresh install/upgrade VPS aaPanel | NOT RUN |
| Backup/restore production-like | NOT RUN |
| ZIP build/clean extraction | NOT RUN |
| UAT Admin/Guru/Siswa madrasah | NOT RUN |

## G. CHECKPOINT DAN LANGKAH SELANJUTNYA

1. `AGENT_START_HERE.md`, report, dan checkpoint telah menunjuk run #452173 dan SHA yang diuji; buat PR draft ke branch eksekusi.
2. Jalankan CI lagi setelah commit dokumentasi, sehingga exact branch HEAD terbaru juga mempunyai bukti 518/0.
3. PR #22 sudah dibuat Draft ke branch eksekusi; lakukan review source/test/docs dan jangan merge otomatis.
4. Setelah P0 PR tersedia, lanjutkan P1 Proktor dan audit portal tanpa mengulang source yang sudah ada di PR lain.
5. Rekonsiliasi XLSX/DOCX sesuai sumber terbaru sebelum implementasi import.
6. Perbarui laporan/checkpoint setiap tahap. Deployment, migration produksi, dan ZIP final tetap menunggu approval dan seluruh acceptance.

**Kesimpulan:** P0 PHPUnit selesai dan terbukti PASS. Proyek Web CBT belum production-ready, belum siap operasional ujian madrasah, dan belum siap distribusi ZIP VPS. Tidak ada perubahan pada database produksi atau deployment.
