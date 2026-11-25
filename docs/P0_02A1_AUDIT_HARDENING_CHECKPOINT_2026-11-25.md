# P0-02A.1 — Audit & Hardening CP Provenance pada ATP
Tanggal: 2026-11-25
Status: **REGRESSION GATE PASS (PHP 8.3 dan PHP 8.4 / MySQL 8.0 InnoDB)**

## 1. Ringkasan keputusan
- Temuan P0-02A.1 terkonfirmasi sebagai celah konsistensi dan integritas pada alur TP/ATP, bukan karena CP PENDING pernah berhasil dipakai.
- Validasi CP kini dipusatkan pada `resolveVerifiedOfficialCp()`, digunakan oleh `createTp()`, `createAtp()`, dan `publish()`.
- Penolakan provenance menggunakan kode stabil. Permission `curriculum.manage` juga diperiksa di service, bukan hanya Livewire.
- ATP dan mapping-nya dibuat dalam satu transaksi. Regression test menyuntik kegagalan pada insert mapping kedua dan membuktikan seluruh data ATP, mapping, serta audit rollback.
- CP resmi PENDING tetap PENDING. Fixture CP sintetis hanya digunakan oleh PHPUnit dan bukan bukti ketersediaan artefak CP resmi.
- Tidak ada merge ke `main`, migration produksi, operasi VPS, deployment, atau build Gradle.

## 2. AUDIT

### 2.1 Provenance CP
Service memeriksa ulang CP dari database di dalam transaksi, bukan mempercayai status yang dibawa TP atau UI:
1. CP harus berada pada tenant pemilik kurikulum, versi, struktur mapel, dan relasi CP yang sesuai.
2. CP harus `outcome_type=CP`, `status=ACTIVE`, `is_official=true`, dan `is_editable=false`.
3. Source harus ada pada tenant pemilik kurikulum (owner scope), bukan hanya ditemukan berdasarkan ID.
4. Source harus `ARTIFACT_CHECKSUM_VERIFIED`.
5. Checksum source dan CP harus berupa SHA-256 64 karakter heksadesimal dan sama dengan `hash_equals()`.
6. Validasi yang sama dijalankan kembali ketika ATP dibuat dan ketika TP/ATP dipublikasikan. Perubahan status atau checksum setelah TP dibuat tidak dipercaya dari snapshot lama.

### 2.2 Permission dan jalur pemanggilan
- `createTp()`, `createAtp()`, dan `publish()` memakai pemeriksaan service `curriculum.manage` yang sama.
- Pemeriksaan permission dijalankan sebelum resolusi profil Guru. Pemanggilan langsung ke service diuji untuk ketiga method.
- Audit route API: tidak ditemukan route API yang memanggil `TeacherLearningOutcomeService`. Pemanggil service yang ditemukan adalah aksi Livewire `TeacherLearning`. Controller API lama `LearningOutcomeController` memakai model `LearningOutcome` legacy, bukan domain `TenantLearningOutcome` TP/ATP ini. Tidak ada jalur API alternatif ke service yang dapat melewati guard; jika endpoint API TP/ATP ditambahkan kelak, endpoint wajib memanggil service ini.

### 2.3 Konsistensi relasi dan mapping
- TP yang dipakai ATP dibatasi pada tenant, registration, assignment, owner, version, struktur mapel, subject, tahun akademik, semester, tipe TP, status, dan current-version yang tepat.
- Registration dikunci setelah assignment; CP dan source dikunci saat provenance diperiksa.
- Urutan lock diseragamkan: **assignment → registration → outcome/TP → CP → source → mapping/framework node**.
- Relasi `teacher()` tetap dipertahankan dan alias `teacherProfile()` ditambahkan untuk kompatibilitas dengan eager-load UI Admin.
- Audit menemukan dua masalah tambahan pada jalur positif TP/ATP:
  - `createTp()` sebelumnya tidak meneruskan argumen `pmOlahIds` ke `validateFrameworkNodes()`.
  - Mapping TP yang dibuat `persistFrameworkMappings()` belum menyimpan `teacher_subject_assignment_id`. Akibatnya mapping tidak lengkap untuk kontrak assignment-scoped dan validasi pewarisan ATP.
- Mapping ATP kini membawa tenant, assignment, registration, version, owner, structure, role, dan creator yang konsisten. Mapping TP diverifikasi ulang sebelum diwariskan; node harus aktif, resmi, tidak editable, dan source-nya metadata/artifact verified.

### 2.4 Temuan run sebelumnya
- Run #161 gagal pada assertion lama yang hanya mencari kata `checksum`; pesan helper saat itu belum menyebut checksum secara eksplisit. Regression tersebut dipertahankan dan kini menguji kode penolakan stabil.
- Run sebelumnya juga memperlihatkan masalah relasi `teacherProfile` pada UI Admin dan kolom wajib `teacher_subject_assignment_id` pada mapping ATP. Kedua akar masalah diperbaiki, lalu jalur TP→ATP diberi regression test positif.
- Tidak ada kegagalan yang diubah menjadi SKIPPED.

## 3. DESAIN
Kode penolakan yang stabil:
- `CURRICULUM_PERMISSION_DENIED`
- `CP_PARENT_RELATION_INVALID`
- `CP_NOT_ACTIVE`
- `CP_SOURCE_UNVERIFIED`
- `CP_CHECKSUM_MISMATCH`
- `ATP_FRAMEWORK_MAPPING_INVALID`

Semua kegagalan terjadi di dalam transaksi sebelum commit. Pembuatan ATP dan seluruh mapping merupakan satu unit atomik. CP source yang PENDING, tidak punya checksum, atau checksum tidak cocok tidak dilonggarkan.

## 4. IMPLEMENTASI

### 4.1 File source dan regression
- `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php`
  - guard permission bersama;
  - helper provenance CP bersama dengan kode stabil;
  - pemeriksaan owner/source scope, status CP, status source, format dan kecocokan SHA-256;
  - locking registration, TP, CP, source, dan mapping;
  - validasi mapping TP sebelum diwariskan ke ATP;
  - assignment scope pada mapping TP dan ATP;
  - publish memeriksa ulang provenance CP dan mapping.
- `backend-laravel/app/Models/TeacherSubjectAssignment.php`
  - alias `teacherProfile()` ditambahkan tanpa menghapus `teacher()`.
- `backend-laravel/tests/Feature/TeacherLearningOutcomeProvenanceTest.php`
  - CP source PENDING;
  - source tanpa checksum;
  - checksum source tidak cocok;
  - checksum CP tidak cocok;
  - CP berubah menjadi INACTIVE dan source berubah menjadi PENDING setelah TP dibuat;
  - TP terhubung ke CP pada struktur mapel yang salah;
  - ATP positif pada fixture test-only checksum-consistent;
  - TP dibuat melalui service dan mapping assignment-scoped diteruskan ke ATP;
  - permission langsung untuk createTp/createAtp/publish;
  - injected mapping insert failure membuktikan rollback;
  - publish menolak CP yang berubah setelah TP dibuat.
- `backend-laravel/tests/Feature/P002ANonCpAcademicAcceptanceTest.php`
  - assertion CP PENDING menggunakan `CP_SOURCE_UNVERIFIED` pada createTp/createAtp/publish; assertion penolakan dan pemeriksaan tidak ada data parsial dipertahankan.
- `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`
  - validasi akun Guru aktif yang sudah diperbaiki pada sub-tahap P0-02A tetap dipertahankan.

### 4.2 Backup byte-exact
Seluruh pasangan source/backup diverifikasi fetch ulang: konten identik byte-for-byte, ukuran dan Git blob SHA-1 cocok. SHA-256 dihitung atas byte UTF-8 dan routine diuji dengan test vector standar `SHA256("test")`.

| File sebelum perubahan | Ukuran | Git blob SHA-1 | SHA-256 |
|---|---:|---|---|
| Service sebelum stable rejection codes | 24,449 bytes | `23cd9f01fc9a250d2fa84023502355d8956f0a11` | `296c66773cbaf00ca14c7468898bd84e8776cc589b68b8a93685e8d51cedbd4a` |
| Regression test sebelum stable assertions | 12,257 bytes | `31ee25a475ff20a6080fb544449f2f1ddcec5dfc` | `6b2505780e34e7365461796c070d06c46a87212b6da9a284b89fd77a6d1457d8` |
| Non-CP acceptance test sebelum stable-code assertion | 33,451 bytes | `22ef32a23686c18174646adcdfea5a51d629c463` | `38aa6fa0a6396fe07587dcd5354099eb1c3175645eb75b302448b7fc51a80354` |
| Service sebelum TP mapping chain repair | 29,572 bytes | `5eef8195052683c186a8e76dd2f2b56972dc0dab` | `c4b0b294fb9859f853229ca676208edeed92f113da2803ca951d40993bb8547b` |
| Regression test sebelum TP mapping chain test | 17,476 bytes | `fb5e66d7eb455be324bfb1feaff485b68589ed72` | `d3bc0f689d738a74115faf2cf533c83831ac22efe4c0302c5a97e38f99d507dd` |
| Model assignment sebelum alias `teacherProfile()` | 2,186 bytes | `0abe29f125998fd63b29fb8364a3276130304a85` | `9ddc71599460a4be128db49e37c16448c2efe7c098c7a06cbef523a9ef477277` |

Manifest dan lokasi backup:
- `docs/backup/P0_02A1_STABLE_PROVENANCE_CODES_20261125/MANIFEST.md`
- `docs/backup/P0_02A1_ACCEPTANCE_ERROR_CODE_ASSERTION_20261125/MANIFEST.md`
- `docs/backup/P0_02A1_TP_SERVICE_MAPPING_CHAIN_20261125/MANIFEST.md`
- `docs/backup/P0_02A1_ATP_MAPPING_AND_RELATION_FIX_20261125/MANIFEST.md`

Backup bytes berada di direktori `backend-laravel/backup_before_patch/` dengan nama file `.before` sebagaimana dicatat pada setiap manifest.

### 4.3 Commit dan branch
- Branch kerja: `work/limited-zip-p0-20261125`
- Exact tested source commit / HEAD: `185f6d515bc92beb08bff8dc8b2c2c91d96458dd`
- Service blob: `df33b1ebfcaf11a7992e91289aa08c82e8eae736`
- Provenance test blob: `49423663da08106c0adadd9e5355e7725b49c611`
- Non-CP acceptance test blob: `e7c41388122acacc1cb70699305899952b045ffb`
- Assignment model blob: `8d50160feb4d5950728617cb05be21851b3d984f`
- Main branch saat verifikasi tetap pada `f1665d0feffe04e6581150d1fd7ff8433a80b83a`; tidak ada merge atau PR merge.

## 5. PENGUJIAN

### Run final
GitHub Actions Run **#182**, ID `45538552057`
Commit diuji: `185f6d515bc92beb08bff8dc8b2c2c91d96458dd`
URL: https://github.com/zane0184/cbt_november/actions/runs/45538552057

| Pemeriksaan | PHP 8.3 / MySQL 8.0 InnoDB | PHP 8.4 / MySQL 8.0 InnoDB |
|---|---|---|
| Identitas source dan DB disposable | PASS | PASS |
| Composer lock dan dependency | PASS | PASS |
| PHP lint | PASS | PASS |
| Clean-install Installer E2E pada DB disposable terpisah | PASS | PASS |
| Student Import acceptance | PASS | PASS |
| Benchmark cleanup denial-path | PASS | PASS |
| Full PHPUnit | **643 PASS, 1 SKIPPED (3.327 assertions)** | **643 PASS, 1 SKIPPED (3.327 assertions)** |
| Job matrix | PASS | PASS |

Kelas regression:
- `Tests\\Feature\\TeacherLearningOutcomeProvenanceTest` — PASS pada kedua matriks.
- `Tests\\Feature\\P002ANonCpAcademicAcceptanceTest` — PASS pada kedua matriks.

Status terpisah:
- **PASS:** seluruh gate CI pada kedua matriks, kedua kelas regression di atas, serta full PHPUnit dengan 643 test lulus.
- **FAIL:** tidak ada pada Run #182.
- **SKIPPED:** satu test pada full suite per matriks. Nama/alasan skip tidak ditampilkan dalam ringkasan log workflow; tidak berasal dari dua kelas regression P0 yang dinyatakan PASS.
- **BLOCKED:** verifikasi positif menggunakan artefak CP resmi belum dapat dilakukan karena source CP resmi yang terdaftar masih PENDING/tidak memiliki artefak checksum terverifikasi. Test positif ATP memakai fixture sintetis test-only dan tidak mengubah registry resmi.
- **NOT RUN:** uji operasional madrasah, integrasi perangkat Android, deployment VPS, serta validasi multi-device produksi. Tidak termasuk ruang lingkup tahap ini.

Database yang digunakan workflow adalah MySQL 8.0/InnoDB disposable. Tidak ada migration produksi, Gradle, VPS, atau deployment yang dijalankan.

## 6. CHECKPOINT DAN LANGKAH BERIKUTNYA
**P0-02A.1 — Hardening CP Provenance pada ATP: PASS untuk regression gate.**

Langkah berikutnya: lanjutkan P0-02A Non-CP Academic Acceptance berdasarkan test acceptance yang sekarang PASS; kemudian lanjutkan akuisisi dan verifikasi byte-level artefak CP resmi secara terpisah. Jangan membuka blokir CP resmi hanya berdasarkan fixture PHPUnit.

Status produksi CBT tetap belum dinyatakan siap produksi.
