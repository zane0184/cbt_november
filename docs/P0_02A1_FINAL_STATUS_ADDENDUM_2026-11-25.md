# Addendum Final — P0-02A.1 dan P0-02A Non-CP Academic Acceptance
Tanggal: 2026-11-25

Dokumen ini melengkapi dan memperbarui checkpoint `docs/P0_02A1_AUDIT_HARDENING_CHECKPOINT_2026-11-25.md`. Bagian “langkah berikutnya” pada checkpoint sebelumnya diperjelas: **P0-02A Non-CP Academic Acceptance telah selesai dan PASS pada Run #182**. Tahap berikutnya bukan mengulang P0-02A, melainkan verifikasi artefak CP resmi dan penutupan gap browser-to-backend.

## 1. AUDIT — hasil akhir

### Provenance TP/ATP
- `createTp()`, `createAtp()`, dan `publish()` memakai helper provenance CP bersama.
- CP dimuat ulang dari database di dalam transaksi; status/snapshot TP lama tidak dipercaya sebagai bukti bahwa CP masih valid.
- CP wajib owner/version/struktur/relasi sesuai, `outcome_type=CP`, `status=ACTIVE`, `is_official=true`, `is_editable=false`.
- Source wajib berada dalam owner scope dan `verification_status=ARTIFACT_CHECKSUM_VERIFIED`.
- Checksum source dan CP wajib SHA-256 64 karakter heksadesimal dan cocok menggunakan `hash_equals()`.
- Permission `curriculum.manage` ditegakkan pada service. Pemanggilan service langsung diuji, bukan hanya jalur Livewire.
- Tidak ada route API aktif yang memanggil `TeacherLearningOutcomeService`; route legacy menggunakan model/domain `LearningOutcome` yang berbeda dan jalur write lama dinonaktifkan. Jika API TP/ATP ditambahkan kelak, endpoint harus memanggil service yang sama.
- CP source resmi Pendis 9941 tetap `PENDING`; seeder dan status resmi tidak diubah. Tidak ada CP sintetis yang dipakai sebagai bukti artefak resmi.

### Browser runner
- `backend-laravel/tests/runner-browser-e2e.mjs` mengintersep `page.route('**/api/v1/**')` dan menyediakan mock API.
- Runner tersebut menguji browser runner/siswa, bukan alur Admin/Guru ke Laravel/MySQL nyata.
- Secara teknis runner dapat diadaptasi dengan meniadakan mock, menyalakan Laravel pada MySQL disposable, dan menyiapkan sesi/tenant. Namun ini bukan konfigurasi yang tersedia saat ini dan belum menjadi bukti browser-to-backend. Status: **BLOCKED / belum tersedia**.

## 2. IMPLEMENTASI — rangkuman final

File source yang berubah dalam ruang lingkup:
- `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php`
  - guard permission dan provenance bersama;
  - stable rejection codes: `CURRICULUM_PERMISSION_DENIED`, `CP_PARENT_RELATION_INVALID`, `CP_NOT_ACTIVE`, `CP_SOURCE_UNVERIFIED`, `CP_CHECKSUM_MISMATCH`, `ATP_FRAMEWORK_MAPPING_INVALID`;
  - locking assignment → registration → outcome/TP → CP → source → mapping/framework node;
  - pemeriksaan ulang mapping framework sebelum diwariskan;
  - meneruskan `pmOlahIds` ke validator;
  - menyimpan `teacher_subject_assignment_id` pada mapping TP dan ATP;
  - publish memeriksa ulang CP dan mapping.
- `backend-laravel/app/Models/TeacherSubjectAssignment.php`: alias relasi `teacherProfile()`, tanpa menghapus `teacher()`.
- `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`: validasi akun Guru aktif; eager-load/view diperbaiki melalui alias relasi model.
- `backend-laravel/tests/Feature/P002ANonCpAcademicAcceptanceTest.php`: alur Admin non-CP dan negative CP PENDING.
- `backend-laravel/tests/Feature/TeacherLearningOutcomeProvenanceTest.php`: 11 regression tests provenance, mapping, transaction, permission, publish, dan TP→ATP service path.

Tidak ada migration, seeder CP, provisioning dasar, database produksi, konfigurasi VPS, atau route API yang diubah.

## 3. PENGUJIAN — bukti akhir

GitHub Actions **Run #182**, ID `45538552057`:
https://github.com/zane0184/cbt_november/actions/runs/45538552057

Commit source/test yang diuji: `185f6d515bc92beb08bff8dc8b2c2c91d96458dd`.

| Pemeriksaan | PHP 8.3.37 | PHP 8.4.28 |
|---|---:|---:|
| MySQL disposable identity | PASS | PASS |
| DB name / engine / server | `cbt_ci_docx / MySQL 8.0.46 / InnoDB` PASS | PASS |
| Composer validate dan dependency | PASS | PASS |
| PHP lint | PASS | PASS |
| Installer E2E | 1 PASS / 27 assertions | 1 PASS / 27 assertions |
| Student Import acceptance | 22 PASS / 87 assertions | 22 PASS / 87 assertions |
| Benchmark cleanup denial-path | 1 PASS / 20 assertions | 1 PASS / 20 assertions |
| Full PHPUnit | **643 PASS, 1 SKIPPED / 3.327 assertions** | **643 PASS, 1 SKIPPED / 3.327 assertions** |
| Workflow job | PASS | PASS |

Database test utama adalah service container MySQL `mysql:8.0`, database `cbt_ci_docx`, dihancurkan setelah job selesai. Installer E2E memakai database terpisah `cbt_installer_test_45538552057_1`. Ini disposable CI, bukan DB produksi/VPS.

### Status test terpisah
- **PASS:** non-CP Admin → Guru/Siswa → Rombel → placement siswa → mapel → registrasi versi metadata → mapping mapel → assignment Guru.
- **PASS:** tenant isolation, permission, duplicate NIP/NISN, duplicate placement/assignment, idempotensi registrasi/mapping, audit log, rollback saat audit gagal.
- **PASS:** CP source PENDING ditolak pada create TP, create ATP, publish TP/ATP, create question, publish question; tidak ada data parsial.
- **PASS:** source tanpa checksum, source checksum mismatch, CP checksum mismatch, CP menjadi INACTIVE, source berubah setelah TP dibuat, relasi TP–CP salah struktur, mapping insert failure rollback, dan publish recheck.
- **PASS terbatas:** jalur positif ATP dan TP→ATP service memakai fixture checksum-consistent **test-only**. Ini menguji implementasi, bukan artefak CP resmi.
- **FAIL:** tidak ada pada Run #182.
- **SKIPPED:** satu test per matriks pada full suite. Ringkasan workflow tidak menampilkan nama/alasan; tidak dihitung sebagai PASS dan bukan berasal dari dua kelas regression P0.
- **BLOCKED:** ATP positif menggunakan CP resmi yang checksum artefaknya benar-benar diverifikasi; belum ada artefak CP resmi yang lolos pemeriksaan byte-level.
- **BLOCKED:** browser-to-real-Laravel E2E.
- **NOT RUN:** deployment VPS, DB produksi, validasi operasional madrasah, perangkat Android, offline/sync, dan multi-device produksi.

### Peringatan non-blocking
- PHPUnit mengeluarkan warning bahwa metadata `/** @test */` pada test methods deprecated dan tidak akan didukung di PHPUnit 12. Test saat ini tetap ditemukan dan lulus; rekomendasi P1 adalah migrasi ke atribut `#[Test]`.
- MySQL CI mengeluarkan warning InnoDB bahwa redo log capacity sempat penuh karena `log_checkpointer` tertinggal. Tidak menyebabkan test gagal. Tuning `innodb_redo_log_capacity` pada service container CI dapat dipertimbangkan; tidak ada perubahan konfigurasi produksi.

## 4. BACKUP DAN CHECKPOINT

Backup utama dan manifest:
- `docs/backup/P0_02A1_ATP_PROVENANCE_20261125/MANIFEST.md`
- `docs/backup/P0_02A1_PROVENANCE_CONTRACT_ALIGNMENT_20261125/MANIFEST.md`
- `docs/backup/P0_02A1_STABLE_PROVENANCE_CODES_20261125/MANIFEST.md`
- `docs/backup/P0_02A1_ATP_MAPPING_AND_RELATION_FIX_20261125/MANIFEST.md`
- `docs/backup/P0_02A1_TP_SERVICE_MAPPING_CHAIN_20261125/MANIFEST.md`
- `docs/backup/P0_02A1_ERROR_MESSAGE_CHECKSUM_20261125/MANIFEST.md`
- `docs/backup/P0_02A1_PUBLISH_ERROR_ASSERTION_20261125/MANIFEST.md`
- `docs/backup/P0_02A_ACTIVE_USER_GUARD_20261125/MANIFEST.md`

Seluruh backup di atas memiliki salinan `.before`; pasangan source/backup diperiksa ulang byte-for-byte, ukuran dan Git blob SHA-1 dicocokkan, SHA-256 dicatat dalam manifest. Koreksi SHA-256 untuk backup lama tersedia pada dokumen `SHA256_CORRECTION.md` yang bersangkutan.

- Branch: `work/limited-zip-p0-20261125`
- Source/test commit yang lulus Run #182: `185f6d515bc92beb08bff8dc8b2c2c91d96458dd`
- Checkpoint audit sebelumnya: `208fac356ee7d97b898720c4e04ebaf15c36763d`
- Main saat diverifikasi: `f1665d0feffe04e6581150d1fd7ff8433a80b83a`
- Tidak ada merge ke main, PR merge, deployment, atau akses DB produksi.

## 5. KEPUTUSAN DAN LANGKAH BERIKUTNYA

**P0-02A.1 — PASS pada regression gate PHP 8.3/8.4 + MySQL 8.0/InnoDB.**
**P0-02A Non-CP Academic Acceptance — PASS pada feature/installer acceptance database disposable.**

Tahap berikutnya:
1. P0-02B: dapatkan artefak keputusan/annex CP MI resmi, verifikasi sumber, tanda tangan bila tersedia, ukuran dan SHA-256; jangan mengubah status PENDING sebelum bukti artefak valid.
2. Buat browser-to-backend Playwright E2E terpisah yang memakai Laravel dan MySQL disposable sungguhan.
3. Migrasikan anotasi test ke `#[Test]` sebelum upgrade PHPUnit 12.
4. Tinjau kapasitas redo log hanya pada CI disposable.
5. Lanjutkan pengujian integrasi/operasional madrasah sebelum klaim siap produksi.

**CBT belum dinyatakan siap produksi.**
