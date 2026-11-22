# LAPORAN VERIFIKASI STAGE 4B DAN STAGE 4C
Tanggal: 22 November 2026

## Status
**PASS — seluruh gerbang Stage 4B dan Stage 4C yang ditentukan pada workflow ini lulus pada source SHA yang sama.**

- Repository: `zane0184/cbt_november`
- Branch: `work/stage4b-room-session-20261122`
- Checkpoint pembanding: `3fbf3b4a976298abc96aadca0db84bdf9f94cd4a`
- Source SHA yang diuji: `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5`
- CI run: [44984303003](https://github.com/zane0184/cbt_november/actions/runs/44984303003)
- Event pemicu: `push`
- Job: `safe-runtime-tests`
- Kesimpulan job: `success`; 35/35 entri step selesai sukses, tidak ada step gagal atau skipped.
- Runtime database: MySQL 8.0.46, InnoDB, database disposable khusus run.
- Preflight membuktikan kredensial produksi/VPS tidak digunakan, file `.env` checkout tidak digunakan, dan user aplikasi hanya mendapat grant pada schema disposable.

## AUDIT

### Source dan riwayat
HEAD branch saat CI dijalankan sama persis dengan SHA checkout: `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5`. Tidak ada perubahan source setelah CI tersebut; laporan dan checkpoint ini adalah dokumentasi tambahan.

Ada 63 commit sejak checkpoint pembanding `3fbf3b4a976298abc96aadca0db84bdf9f94cd4a`. Seluruh SHA dan pesan commit dicatat di bagian Riwayat Commit.

### Stage 4B — migration, tenant integrity, dan race
- Rollback migration `2026_11_22_110001_create_exam_room_session_scope` dijalankan sebagai satu langkah rollback terakhir, bukan rollback seluruh batch.
- Migration rollback mensyaratkan MySQL `FOREIGN_KEY_CHECKS = 1`.
- Penghapusan tabel dilakukan child-first.
- FK dasar `exam_participants.tenant_id` dan indeks tunggal pendukung tenant tetap ada setelah rollback.
- Kedua skenario race memakai dua sesi MySQL independen; test membandingkan `CONNECTION_ID()` primer dan sekunder.
- Cleanup fixture berjalan tanpa menonaktifkan foreign-key checks.
- Pemeriksaan InnoDB untuk seluruh tabel yang dimigrasikan lulus.

### Stage 4C — penugasan Guru
- Validasi tenant untuk Guru, Mapel, Rombel, Tahun Ajaran, dan Semester diperiksa pada source komponen.
- Tahun ajaran kedua pada fixture lintas tahun menggunakan semester dan rombel yang sama-sama benar-benar berasal dari tahun tersebut, tetapi semester dibuat nonaktif agar tidak melanggar indeks unik satu semester aktif per tenant.
- Fixture tidak mengirim ulang `academicYearId` hasil mount; dengan demikian hook UI tidak menghapus referensi mismatch sebelum guard server diuji.
- Validasi bisnis tidak diubah untuk menyesuaikan fixture.
- Role Guru menggunakan satu role kanonis; fixture concurrency memakai `firstOrCreate` agar tidak membuat duplikat global.
- Tes akses mencakup Guru, Siswa, serta Admin tanpa permission `academic.manage`.

## IMPLEMENTASI

1. `backend-laravel/database/migrations/2026_11_22_110001_create_exam_room_session_scope.php`
   - rollback child-first;
   - mempertahankan FK tenant dasar dan indeks pendukung;
   - guard eksplisit agar rollback MySQL hanya berjalan ketika foreign-key checks aktif.
2. `backend-laravel/tests/Feature/ExamRoomSessionConcurrencyTest.php`
   - tidak lagi memakai `DatabaseMigrations` yang mencoba me-rollback seluruh batch;
   - schema disposable dibangun dengan `migrate:fresh`;
   - dua race membuktikan koneksi berbeda melalui `CONNECTION_ID()`;
   - fixture Guru kanonis dibuat/reused satu kali.
3. `backend-laravel/tests/Feature/TeacherSubjectAssignmentConcurrencyTest.php`
   - strategi schema disposable sama;
   - koneksi primer dan sekunder dibuktikan berbeda;
   - role Guru kanonis dipastikan tersedia melalui `firstOrCreate`.
4. `backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php`
   - fixture lintas tahun diperbaiki tanpa mengubah validasi bisnis;
   - coverage edit dan audit before/after ditambahkan;
   - audit create/deactivate dan idempotensi diperiksa;
   - akses Guru/Siswa dan Admin tanpa `academic.manage` diuji.
5. `.github/workflows/wa01-alternative-isolated-runtime.yml`
   - ditambahkan langkah rollback khusus Stage 4B, pemeriksaan FK/index/tabel, lalu restore migration.

Tidak ada perubahan pada source Device Lease/Stage 4D atau Web Guru. Tidak ada perubahan branch Agent 2 atau Agent 3 yang dilakukan dalam pekerjaan ini.

## PENGUJIAN

### Empat suite target

| Suite | Hasil | Assertions |
|---|---:|---:|
| Stage 4C lifecycle dan tenant boundary | 9 passed | 41 |
| Stage 4C MySQL 8 tenant mutex concurrency | 1 passed | 8 |
| Stage 4B room/session/participant integrity | 12 passed | 49 |
| Stage 4B MySQL 8 concurrent room conflict | 2 passed | 22 |
| **Total empat suite target** | **24 passed** | **120** |

### Rollback Stage 4B — bukti CI
Pada MySQL 8.0.46/InnoDB:
- `migrate:rollback --step=1` mengeksekusi migration Stage 4B dan berakhir `DONE`.
- `foreign_key_checks = 1`.
- Tidak ada tabel Stage 4B tersisa setelah rollback.
- FK dasar `exam_participants.tenant_id` tetap ada.
- Indeks tunggal `exam_participants.tenant_id` tetap ada.
- Gate `rollback_was_child_first_and_fk_checks_remained_enabled = true`.
- Migration Stage 4B dijalankan kembali dan berakhir `DONE`.
- Tidak ada tabel Stage 4B yang hilang setelah restore.

### CI keseluruhan
Semua langkah workflow lulus, termasuk PHP lint, preflight isolasi database, suite regresi lain, pemeriksaan InnoDB, rollback/restore Stage 4B, dan cleanup database disposable serta user aplikasinya.

Artifact bukti:
- Nama: `wa01-alternative-runtime-44984303003-1`
- Artifact ID: `14739069544`
- Ukuran: 20,145 bytes
- SHA-256: `62856a1a0b94b77db9e096c711f24985cca4c7c0dfbaf354362ba9ab44034904`
- Kedaluwarsa: 22 Desember 2026

## BACKUP DAN MANIFEST

Manifest:
- `docs/backup/stage4b-room-session-2026-11-22/MANIFEST.md`
- `docs/backup/stage4c-teacher-subject-assignment-2026-11-22/MANIFEST_SUPPLEMENT.md`

Sebanyak 17 snapshot sebelum perubahan tercatat di manifest. Pemeriksaan Git blob SHA pada snapshot yang diverifikasi cocok dengan source tepat sebelum perubahan. ZIP baseline tidak disentuh.

## BATASAN DAN KEPUTUSAN

- Tidak ada merge, rebase, PR, deployment, atau perubahan database produksi.
- Tidak ada perubahan pada branch Agent 2/Agent 3.
- Keberhasilan Stage 4B/4C tidak menyatakan Device Lease/fencing siap produksi.
- Hasil ini hanya membuktikan suite dan rollback pada database disposable MySQL 8.0.46 di CI; bukan bukti validasi operasional madrasah atau kesiapan produksi.

## RIWAYAT COMMIT SEJAK CHECKPOINT PEMBANDING

- `d11e74ff8478335a547ff14f88c1f7383b47c163` — docs(stage4c): add supplemental backup manifest
- `25b6780fdac759967970037a9596d7cc53768740` — docs(stage4c): record implementation audit and test status
- `2746c20ea1d636405b9d4131fa9f5b92e205e5c5` — docs(stage4c): add implementation checkpoint
- `4f927d0dd8238176f2dab909ac41ac9673a4900b` — backup(stage4b): snapshot migration before child-first rollback correction
- `aa1e2e695392adcfb67e40d600caac69614d5701` — docs(stage4b): record child-first rollback backup
- `8bdda41ddb735822572feb1e0a993fe9cee7b3c0` — fix(stage4b): drop room assignment children before parent scope key
- `d528f247b8949fc68c9cf8a3cd913d164e5aa619` — backup(stage4b): snapshot concurrency test before FK-safe teardown
- `03777dfdb5d09fd1722ef251e0823d494c550bf1` — docs(stage4b): record concurrency teardown backup
- `8b44ff7192b2ac3b16a8cb0417516455ea6c5b4a` — test(stage4b): keep foreign keys enforced during concurrency cleanup
- `0631a3d84d058c8c049dcb71dc54b2e7cbb849a6` — test(stage4b): clear nullable FK references before parent cleanup
- `0b7fef381c6c36a55f6d57bd4ca2cb6f1ae711be` — test(stage4b): clean disposable room rows in FK dependency order
- `6be72a1cde7d8b128e6b6a523e35caa6408c14d6` — docs(stage4b): clarify FK-enforced test teardown
- `73ce475076fc9dcb64e18030ff5a9355be4358d7` — docs(stage4b): record source snapshot metadata for FK cleanup test
- `813c01fc9939d66876100b489bec762aee4e51ee` — backup(stage4c): snapshot assignment management test before MySQL corrections
- `24e9674fae1865392f6f630b4b3a6548b41b183b` — docs(stage4c): register backup before MySQL test correction
- `682ea9505ff217ed7a7e511da3ae3a813d2bcdd2` — test(stage4c): submit valid cross-year semester for consistency guard
- `42a3ed3a2d5d908cbcfc30340ef06de5b62b5887` — test(stage4c): reuse canonical Guru role in access denial fixture
- `a6f6bec4af8746c540f4e318b6a54e6203ec3bcf` — test(stage4c): provide valid cross-year rombel and semester fixtures
- `7333a4f62819951f022bdf08e23c7982c7048bd3` — docs(stage4c): clarify test fixture backup history
- `9ec2e76629d40c5eb698355735e47f8169e6ff08` — backup(stage4c): snapshot MySQL concurrency test before role fixture correction
- `de78831f4c93573fd7dac168e544a084337e7762` — docs(stage4c): register concurrency fixture backup
- `5d45b8404f225c105016a25976939afe3774abef` — test(stage4c): reuse canonical Guru role in MySQL concurrency fixture
- `ee8450113f82ee2f6e6037f088eff9ea960ecc7d` — backup: snapshot backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php before MySQL fixture and connection assertions
- `6857f18eaeb6f3c88dc840f92b8f29556b5d5cff` — backup: snapshot backend-laravel/tests/Feature/TeacherSubjectAssignmentConcurrencyTest.php before MySQL fixture and connection assertions
- `e3d19c60e64cc636e8193f9639bbb529daff06b2` — backup: snapshot backend-laravel/tests/Feature/ExamRoomSessionConcurrencyTest.php before MySQL fixture and connection assertions
- `9da5c6ecccdb706ed80021d88ebc3272d1ba5748` — docs: register pre-change MySQL test snapshots
- `a2d96cf0712732dd4de7cf50e985a1782a6cf665` — docs: register pre-change MySQL test snapshots
- `fb868c3ae1f8f9701d09b4abeadb978027796b46` — test(stage4c): keep cross-year mismatch fixture within semester uniqueness constraint
- `21177831f475791275424ca2646aec055ce9ff57` — test(stage4c): assert independent MySQL sessions in tenant mutex race
- `fe04d384e224796e43c4aeaba891f1e204bf51cc` — test(stage4b): verify independent MySQL sessions and canonical Guru role
- `2060362cad028f86b99a12f9955b6088e71a8301` — backup: snapshot Stage 4B concurrency test before second race connection assertion
- `08f8f5c7455a37cca42b1615302caea85c0b7502` — docs: register Stage 4B second race pre-change snapshot
- `ea5180ac99267e1ce44b20a45db75239bb1160b4` — test(stage4b): assert independent MySQL sessions for proctor race
- `b5e3d5ebe8e4a566a0dd6f59e43ba6fb9215588f` — backup: snapshot Stage 4B migration before explicit FK checks rollback guard
- `4a04247afa449ac4348344f8a381b9f9b80d6948` — docs: register pre-change migration rollback guard snapshot
- `af36ad3b8b188e147e084045af0e02ee8b591d65` — fix(stage4b): require foreign key checks during rollback
- `13872d00f31a5600ff094f3bf312045ba06abdce` — backup: snapshot Stage 4C lifecycle test before preserving server-side year mismatch
- `4966168116a42ba28750bb7e5d933e6e45155607` — docs: register Stage 4C server-side mismatch test snapshot
- `3f76b1559001e6591b3456de59fb06cffedee7fb` — test(stage4c): exercise server-side cross-year guard without UI reset hook
- `0952bca9e9a04874c0fafcd104e9434d6a0b889b` — backup: snapshot Stage 4C lifecycle tests before audit edit and access coverage
- `22e8adaf583cc1f3a7daafcd12b318fe689a7eef` — docs: register Stage 4C audit and access test snapshot
- `22fa090221bcb3f287805c0e37daa60983509587` — test(stage4c): cover edit audit and role permission boundaries
- `a6bb82a01bd642bb7613fa77946aa4be27ae9380` — backup: snapshot Stage 4C lifecycle test before Carbon import syntax correction
- `99eaa16a20fbbabf092b79f5a9c7bb9a02402b60` — docs: register Carbon assertion pre-change snapshot
- `041738d4295916c1c786abd7296eb83e7bc814a3` — test(stage4c): correct Carbon audit assertion import
- `a76b215b5cf60946e37e9a47d12016381b7ea6a8` — backup: snapshot Stage 4C access tests before canonical SISWA fixture
- `3cdf69542978d3339f6b13de8e6b141bf25c015a` — docs: register SISWA fixture pre-change snapshot
- `d250f933fd1895d44e04d10efb70c4d253dff873` — test(stage4c): use canonical SISWA role in access denial fixture
- `ada0becdeef8f59576713f81cd9b45419449a2b2` — backup: snapshot backend-laravel/tests/Feature/TeacherSubjectAssignmentConcurrencyTest.php before canonical Guru fixture correction
- `cce7f5e0d41adaa6c0b464bf04b369fb486332d1` — backup: snapshot backend-laravel/tests/Feature/ExamRoomSessionConcurrencyTest.php before canonical Guru fixture correction
- `01a072c3f9d7b1e729a8494f86b340ee6ad45f1b` — docs: register canonical Guru fixture pre-change snapshot
- `3d1274cdcb6e3421af5f0a1721f0cff11db0db1c` — docs: register canonical Guru fixture pre-change snapshot
- `b032666b54999bb7ae0024feb778294812696395` — test(stage4c): ensure one canonical Guru role in MySQL concurrency fixture
- `a49cd6105165d370a0643d70108e9c0e6d95a801` — test(stage4b): ensure one canonical Guru role in proctor race fixture
- `6bd47f199085fe5ba3f43a64d13ecacdc6a0f946` — backup: snapshot backend-laravel/tests/Feature/TeacherSubjectAssignmentConcurrencyTest.php before isolated migration lifecycle test strategy
- `13c609abf56f3ccd344bab1c5b0f5cd536eb7621` — backup: snapshot backend-laravel/tests/Feature/ExamRoomSessionConcurrencyTest.php before isolated migration lifecycle test strategy
- `f1ff17a410b59e806a85dbb77861143f013703c2` — backup: snapshot .github/workflows/wa01-alternative-isolated-runtime.yml before isolated migration lifecycle test strategy
- `ff9cdd388477ce8fc51ccf69f39cebd63346614b` — docs: register concurrency and rollback workflow snapshots
- `e478cb7dcafc3f1dae3ec03d2eb14ce6a66e6f15` — docs: register concurrency and rollback workflow snapshots
- `a4bc3c0215133ffea931759c608a6bfe94912652` — docs: register concurrency and rollback workflow snapshots
- `ad9a44071b17719359306b900c649a0ca31557d0` — test: isolate MySQL concurrency schema without rolling back forward-only migrations
- `981f82c236c2860948ab5813b85cc49e27ec305c` — test: isolate MySQL concurrency schema without rolling back forward-only migrations
- `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5` — ci(stage4b): verify child-first migration rollback with MySQL foreign keys enabled
