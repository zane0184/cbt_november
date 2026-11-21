# LAPORAN AUDIT W0 — WEB ADMIN MADRASAH
**Tanggal:** 21 November 2026 (WIB)  
**Status:** AUDIT STATIS SELESAI; SHA-256 DAN RESTORE SNAPSHOT PASS; PROVENANCE ZIP BASELINE TETAP BLOCKED  
**Scope:** Web Admin Madrasah saja  
**Tidak termasuk:** Portal Guru/Siswa, Android, Publisher/Super Admin, licensing, VPS/deployment, Device Lease/Fencing

## 1. Ringkasan eksekutif

Persetujuan pengguna telah diterapkan dengan batas yang tegas:
- Working source sementara khusus Web Admin: `recovery/source-preservation-20261121`.
- Desain W0–W10 disetujui sebagai dasar kerja.
- Admin Madrasah dan Operator wajib menjadi satu identitas/role/portal/permission profile; role aktif tunggal `ADMIN_MADRASAH`.
- Guru memegang operasional CBT.
- Proktor hanya penugasan sementara kepada Guru aktif, bukan role atau portal permanen.
- Admin hanya membaca metadata ujian dan mengelola penugasan Proktor.
- Satu madrasah = satu instalasi = satu database.

W0 telah membuat branch kerja baru, memverifikasi titik awalnya, membuat manifest rekursif Git, memverifikasi manifest, dan menyelesaikan audit statis route-permission utama. Tidak ada source aplikasi yang diubah. Tidak ada migration, database, merge PR, deployment, atau konfigurasi produksi yang disentuh.

**W1 belum dimulai.** Walaupun backup Git dan manifest telah diverifikasi, pemeriksaan SHA-256 per-file serta uji pemulihan snapshot lokal belum dapat dilakukan di runtime ini. Keterkaitan byte-to-byte source Git dengan ZIP baseline 6 November juga belum terbukti. Karena itu W0 tidak dinyatakan lulus penuh pada gate integritas arsip.

## 2. AUDIT

### 2.1 Identitas source dan branch

| Item | Bukti |
|---|---|
| Repository | `zane0184/cbt_november` |
| Working source yang disetujui | `recovery/source-preservation-20261121` |
| Commit source | `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0` |
| Git tree SHA-1 | `144364e4163ccc62f74bc1fbf93b601380ed2723` |
| Branch kerja baru | `work/web-admin-w0-20261121` |
| Titik awal branch kerja | Commit persis `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0` |
| Branch kerja setelah manifest | `de2d82244150863e330c17e36345bee73c3907ac` |
| Manifest blob SHA-1 | `f74b89d00015a15c04e4e062d46571afdd3074bc` |
| Manifest | `docs/backup/W0_SOURCE_TREE_MANIFEST_2026-11-21.tsv` |

Branch kerja dibuat dari SHA yang disetujui, bukan dari `main`, PR base, atau branch recovery yang berbeda. Perbandingan awal sebelum manifest menunjukkan branch kerja identik dengan commit recovery (ahead 0, behind 0). Setelah manifest ditambahkan, perbandingan recovery → branch kerja menunjukkan ahead 1, behind 0, dengan tepat satu file tambahan: manifest. Branch recovery tetap pada SHA `7fc45bf...`.

### 2.1.1 Klarifikasi riwayat commit

Perbedaan yang ditanyakan berasal dari checkpoint awal yang mencatat commit `641aff0...` sebagai commit terakhir, padahal commit tersebut adalah commit laporan audit dan menjadi parent dari commit checkpoint `c708e978...`. Pemeriksaan API GitHub terhadap seluruh parent chain membuktikan urutan setelah source pin:
1. `de2d822...` (parent `7fc45bf...`) — manifest Git tree.
2. `641aff0...` (parent `de2d822...`) — laporan audit statis.
3. `c708e978...` (parent `641aff0...`) — checkpoint awal.
4. `9c6ce35...` (parent `c708e978...`) — workflow SHA-256/restore versi pertama.
5. `a42cdb...` (parent `9c6ce35...`) — perbaikan workflow; HEAD branch kerja yang benar saat laporan revisi ini.

Jadi `641aff0...` bukan HEAD terbaru. Branch recovery tetap pada `7fc45bf...`. Perbandingan recovery → branch kerja: ahead 5, behind 0; path tambahan hanya empat file: tiga dokumen W0 dan satu workflow verifikasi. Tidak ada source aplikasi berubah.

### 2.2 Inventaris dan manifest source

Recursive Git tree berhasil diambil dengan `truncated=false`:
- 768 tree entries.
- 531 blob/file records.
- 237 directory entries.
- 503 blob berada di `backend-laravel/`.
- 347 file backend aktif di luar `backup_before_patch/` dan `vendor/`.
- 160 file berada di direktori `backup_before_patch/`.

Manifest berisi path, tipe, Git blob SHA-1, dan ukuran byte untuk seluruh 531 blob. Manifest berhasil diambil ulang dari branch kerja dan SHA blob yang dikembalikan GitHub adalah `f74b89d00015a15c04e4e062d46571afdd3074bc`; ukuran konten yang diverifikasi 71.665 byte. Ini membuktikan konsistensi manifest Git terhadap objek Git yang tersimpan.

**Batas verifikasi:** Git blob/tree menggunakan identitas objek Git SHA-1. Manifest ini bukan SHA-256 per-file dan bukan checksum ZIP. Tidak ada klaim SHA-256 arsip atau byte identity ZIP yang dibuat.

### 2.3 Provenance dan perbedaan sumber

- PR #5 head historis `fix/web-portal-usability-rbac-20261120` berada pada `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`.
- Branch recovery berada 7 commit di atas head PR #5. Perbandingan yang tersedia menunjukkan perubahan tambahan berupa 5 file dokumentasi recovery/handover; tidak ada file aplikasi yang berubah pada rangkaian tambahan tersebut.
- Karena itu, source aplikasi pada recovery branch sama dengan source aplikasi pada head PR #5, sementara branch recovery menambahkan dokumentasi provenance.
- Branch `main` bukan sumber build Web Admin yang tepat pada pemeriksaan sebelumnya: tree main tidak memuat source Laravel aktif. Jangan memindahkan basis kerja ke main.
- ZIP baseline 6 November dan arsip workspace historis belum dapat dibaca sebagai raw bytes dari Library/Project pada runtime ini. Metadata dan checksum historis tidak dihitung ulang. Kesamaan source Git dengan ZIP baseline **belum terbukti**.
- Branch recovery bukan canonical seluruh CBT dan tidak memuat bukti byte identity untuk Android/Publisher. Pengguna menyetujui branch ini hanya sebagai working source sementara Web Admin.

### 2.4 Matriks route-permission — hasil pemeriksaan statis

Semua route web utama berada di balik middleware `auth`, `tenant`, dan `license`, kecuali route yang memiliki kombinasi middleware khusus. Namun middleware grup bukan pengganti authorization per action; komponen Livewire dan controller masih harus memeriksa role/permission/policy.

| Route/permukaan | Guard saat ini | Temuan aktual | Keputusan target |
|---|---|---|---|
| `GET /` | Redirect berbasis role | Admin/Operator diarahkan ke `/operator/dashboard`; Proktor ke `/proktor/assignments` | Admin menuju satu dashboard Admin; tidak ada portal Operator/Proktor |
| `POST /login` | LoginIdentityResolver + allowlist role | Allowlist masih menerima `OPERATOR` dan `PROKTOR`; resolver juga menerima alias `ADMIN` | Satu role aktif Admin; Guru/Siswa tetap sesuai scope produk; tidak ada role Proktor |
| `/operator/dashboard` | OperatorDashboard::mount | Menerima `ADMIN_MADRASAH`, `ADMIN`, dan `OPERATOR`; dashboard masih bernama Operator | Gabungkan ke satu dashboard Admin dan hapus cabang role/portal Operator setelah migrasi legacy aman |
| `/admin/users` | UserManagement::mount dan mutasi | Memerlukan `ADMIN_MADRASAH` + `user.manage`; create hanya mengizinkan GURU/SISWA; guard diulang pada mutasi | Secara dasar sesuai; lifecycle CRUD/status/histori masih perlu audit fase berikutnya |
| `/admin/academic` | AcademicManagement | Menerima `ADMIN_MADRASAH` atau alias `ADMIN` + `academic.manage`; tidak menerima Operator | Hapus alias `ADMIN`; satu profile Admin |
| `/admin/rombel-students` | StudentRombelManagement | Memerlukan `ADMIN_MADRASAH` + `academic.manage`; aksi memeriksa tenant dan role siswa | Dasar guard sesuai; histori/promosi/lifecycle belum lengkap |
| `/admin/curriculum` | CurriculumManagement | Mount memerlukan `curriculum.view`; mutasi memerlukan role Admin/alias `ADMIN` + `curriculum.manage` | Pertahankan master administratif Kurmer/KBC; hapus alias role `ADMIN` |
| `/admin/questions` | QuestionBank::mount | Admin dan Guru sama-sama dapat membuka bank soal; tampilan merender stem, rubrik/pembahasan, opsi, dan indikator kunci. Policy menolak create/update oleh Admin, tetapi tidak menutup pembacaan konten soal | **Tidak sesuai:** Admin tidak boleh mengelola/membaca bank soal Guru; keluarkan Admin dari route, view, permission dan API bank soal |
| `/admin/exams` | ExamManagement::mount + Gate | Admin dan Guru dapat membuka komponen yang sama. Gate menolak create/update/token/lock untuk Admin, tetapi Admin tetap memakai permukaan UI campuran. Policy mengizinkan Admin close | **Tidak sesuai:** Admin perlu daftar metadata read-only terpisah; semua aksi operasional tetap Guru |
| `/admin/proctor-assignments` | Admin + `exam.proctor.assign` | Komponen Admin tersedia, tetapi picker dan service hanya menerima role `PROKTOR` | **Tidak sesuai:** pilih Guru aktif tenant yang sama; tidak ada role/portal Proktor |
| `/proktor/assignments` | ProktorDashboard + role PROKTOR | Portal permanen masih ada | Hapus portal permanen; capability hanya dari assignment Guru yang aktif |
| `/exams/{exam}/monitoring` dan API monitoring | ExamPolicy / permission | Admin dengan `exam.monitor` dapat melakukan monitoring | **Tidak sesuai:** monitoring operasional bukan kewenangan Admin |
| API daftar/detail ujian | `viewAny` / `view` ExamPolicy | Admin dapat melihat seluruh metadata ujian pada tenant instalasinya | Pertahankan hanya field metadata minimum; jangan menyertakan snapshot/answer/result |
| API close ujian | `ExamPolicy::close` | Admin dapat menutup ujian dengan `exam.close` | **Tidak sesuai:** hanya Guru pemilik sesuai assignment/permission |
| API reports/results | ReportPolicy | Admin dengan `report.view` dapat membaca hasil ujian/siswa/mapel | **Tidak sesuai:** hasil dan analisis operasional adalah kewenangan Guru; Admin hanya agregat eksekutif minimum bila disetujui desain |
| Detail attempt/score | ExamAttemptPolicy | Admin dengan `report.view` dapat membuka detail attempt yang memuat payload jawaban dan data penilaian per soal | **P0 boundary defect:** cabut akses answer-level/score-detail Admin |
| Finalize/reopen | ExamAttemptPolicy | Admin dengan `exam.grade` dapat finalize; Admin dengan `exam.reopen` dapat reopen | **Tidak sesuai:** Admin tidak boleh menilai/finalize/reopen hasil Guru |
| API bank soal | QuestionPolicy + controller | Create/update dibatasi Guru, tetapi Admin memiliki `question.view` dan akses baca | **Tidak sesuai:** batasi bank soal kepada Guru |

### 2.5 Role dan permission yang ditemukan

`DatabaseSeeder.php` membuat role terpisah `ADMIN_MADRASAH` dan `OPERATOR`. `RolePermissionBootstrapper.php` memiliki dua permission profile berbeda:
- Admin mendapat `curriculum.view`, `curriculum.manage`, `academic.manage`, `question.view`, `user.manage`, `exam.manage`, `exam.assign`, `exam.monitor`, `exam.close`, `exam.proctor.assign`, `exam.token`, `exam.grade`, `exam.reopen`, dan `report.view`.
- Operator memiliki optional grants tersendiri: `curriculum.view`, `academic.manage`, `exam.monitor`, dan `report.view`.

Artinya Admin dan Operator belum identik dalam role, permission, dan akses. Selain itu, alias `ADMIN` masih muncul di LoginIdentityResolver, AcademicManagement, CurriculumManagement, OperatorDashboard, dan KamadDashboard.

Permission Admin yang terlalu luas bukan hanya masalah label UI: beberapa policy benar-benar mengizinkan close, monitoring, result/answer detail, finalize, dan reopen. Hak `exam.manage`, `exam.assign`, dan `exam.token` memang tidak otomatis melewati policy Guru pada semua action, tetapi grant yang tidak dibutuhkan tetap harus dicabut dari profile Admin.

### 2.6 Penugasan Proktor dan perubahan jadwal

Bukti source:
- `ProctorAssignmentManagement.php` memfilter pengguna dengan role `PROKTOR`.
- `ExamProctorAssignmentService.php` menolak target jika `hasRole('PROKTOR')` tidak terpenuhi. Service mengunci Exam dan user target dalam transaksi saat assign.
- Query konflik interval lintas assignment Guru tidak ditemukan. Lock pada user tanpa query overlap belum mencegah double booking.
- Revoke mengunci Exam dan assignment, tetapi tidak memakai lock Guru dengan urutan yang sama.
- `exam_proctor_assignments` berisi exam_id dan proctor_user_id, belum memiliki rombel_id/class_id.
- `ExamProctorAssignment::scopeActiveNow` memakai batas interval setengah terbuka: starts_at <= waktu server dan expires_at > waktu server.
- Status aktif/revoked tersedia; alur `NEEDS_REVIEW` saat jadwal ujian berubah belum ditemukan.
- Perubahan jadwal memiliki dua jalur: API `ExamController::update` dan Livewire `ExamManagement::saveExam`. Belum ditemukan satu `ExamScheduleChangeService` yang memaksa kedua jalur melakukan audit perubahan jadwal dan menandai assignment terdampak untuk review.

### 2.7 Tenant dan model instalasi

- `IdentifyTenant` menurunkan TenantContext dari tenant milik user terautentikasi dan mengabaikan `X-Tenant-Override`.
- Trait `TenantScoped` pada model yang memakainya fail-closed ketika TenantContext kosong.
- Model `User` belum menggunakan `TenantScoped`; seluruh jalur query User, termasuk `withoutGlobalScopes`, tetap harus diuji eksplisit.
- Pemeriksaan satu Tenant dan satu LocalInstallationIdentity sudah terdapat pada LoginIdentityResolver. Ini tidak menghapus kebutuhan audit domain binding dan seluruh jalur akses.
- Tidak ada database produksi yang diakses atau diubah pada W0.

## 3. DESAIN — baseline W0–W10 yang disetujui

| Tahap | Sasaran | Gate penerimaan ringkas |
|---|---|---|
| W0 | Source pin, backup, manifest, route/action-permission matrix | Source commit dan branch kerja terverifikasi; manifest dapat diverifikasi; perbedaan provenance ditutup atau dinyatakan blocker |
| W1 | Role consolidation dan tenant guards | Satu `ADMIN_MADRASAH`; akun legacy aman; tidak ada role OPERATOR/PROKTOR aktif; tenant fail-closed |
| W2 | Admin dashboard, shell, sidebar, routes | Satu portal/dashboard/sidebar; tidak ada portal Operator/Proktor |
| W3 | Akademik, periode, kelas, fase, rombel, mapel | Data induk dan histori lengkap |
| W4 | Data Guru/Siswa, akun, status, assignment Guru | Lifecycle aman dan tidak ada duplikasi identitas |
| W5 | Penempatan, promosi, tinggal kelas, kelulusan, alumni | Transaksi atomik; histori akademik tetap utuh |
| W6 | Master Kurmer/KBC, CP/TP/ATP, Mulok, Panca Cinta, DPL | Sumber resmi/versioning jelas; tidak mengarang referensi |
| W7 | Metadata ujian Admin + assignment Guru Proktor sementara | Admin metadata-only; Guru pemilik operasional; konflik, reschedule review, expiry/revoke aman |
| W8 | Importer dan laporan administratif | Preview, validasi, idempotensi, rollback/retry; tidak membocorkan hasil ujian |
| W9 | Audit log, health, backup/restore, profil/preferensi | Audit lengkap; checksum; restore hanya disposable |
| W10 | Integrasi, security, browser, performance, UAT | Acceptance tests PASS dan persetujuan pengguna |

Desain ini tidak memberi izin merge, migration produksi, atau deployment. Implementasi setiap tahap tetap menunggu gate yang berlaku.

## 4. IMPLEMENTASI

Perubahan yang benar-benar dilakukan pada sesi W0:
1. Membuat branch `work/web-admin-w0-20261121` dari commit recovery yang disetujui.
2. Menambahkan manifest Git tree `docs/backup/W0_SOURCE_TREE_MANIFEST_2026-11-21.tsv`.
3. Menambahkan laporan audit dan checkpoint W0.
4. Menambahkan workflow audit-only `.github/workflows/w0-source-snapshot-verify.yml` untuk menghitung SHA-256, membuat arsip dari commit pin, restore disposable, dan membandingkan seluruh file. Workflow tidak mengubah source aplikasi.
5. Mengunduh artifact untuk verifikasi independen dan menyalin manifest/snapshot hasil verifikasi ke ruang kerja laporan.

Tidak ada file source aplikasi, migration, route, policy, seeder, model, view, atau konfigurasi aplikasi yang diubah. Branch recovery asli tidak diubah. Tidak ada PR baru, merge, database write, migration, .env/secret, VPS, atau deployment.

## 5. PENGUJIAN DAN BUKTI CI

### 5.1 CI terbaru untuk source aplikasi

Run **Tenant Backend CI #692**:
- Run ID: `44755020408`
- Commit yang diuji: `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`
- Status: SUCCESS
- Runtime: PHP 8.3.37; MySQL 8.0.46; InnoDB; Composer 2.11.2; PHPUnit 11.5.58.
- Database uji disposable: `cbt_ci_p089`; installer E2E memakai database disposable terpisah.
- Focused Web login + tenant identity smoke: 23 passed, 83 assertions.
- Full PHPUnit: 480 passed, 1.942 assertions.
- [CI Tenant Backend #692](https://github.com/zane0184/cbt_november/actions/runs/44755020408)

Run **Build Web CBT deployment archive #64**:
- Run ID: `44755020451`
- Commit: SHA yang sama `ade5daa...`
- Status: SUCCESS; clean standalone deployment tree/archive dibangun dan diunggah oleh workflow.
- Ini hanya bukti workflow packaging pada commit tersebut, bukan bukti deployment atau kesiapan produksi.
- [CI Web archive #64](https://github.com/zane0184/cbt_november/actions/runs/44755020451)

Source aplikasi pada recovery branch sama dengan source aplikasi pada `ade5daa...`; tambahan recovery dan branch kerja W0 adalah dokumen saja. CI di atas karena itu relevan untuk source aplikasi, tetapi bukan run baru atas commit dokumentasi W0 `de2d822...`.

### 5.2 Penelusuran kegagalan CI sebelumnya

1. **Run #668**, commit `1a52c36ee76c6a677e719b8a9d26689beaed8922`, gagal pada tahap focused smoke sebelum full suite. Log menunjukkan syntax error PHP `unexpected token "\\"` saat PHPUnit memuat test; full suite dilewati. Ini adalah kegagalan parsing/discovery, bukan hasil test assertion.
2. **Run #671**, commit `d264c8dfc896f5d78e70b818e5ee4cfd41d7e36a`, berhasil melewati focused smoke (23/83) tetapi full suite gagal: 18 failed, 462 passed, 1.866 assertions. Log menunjukkan ParseError pada compiled Blade `resources/views/livewire/admin/question-bank.blade.php:8`, dipicu class reference dengan escaping backslash ganda.
3. Perbandingan `d264...` → `ade5...` menunjukkan perubahan satu baris pada `question-bank.blade.php` serta perubahan test terkait. Pada `ade5...`, class reference Blade menggunakan namespace PHP dengan satu leading backslash yang benar.
4. **Run #692** pada `ade5...` kemudian menjalankan full suite dan lulus 480/1.942. Jadi kegagalan lama telah ditangani pada head source yang diuji, bukan sekadar diasumsikan selesai.

### 5.3 SHA-256 snapshot dan uji restore disposable

Workflow `W0 Source Snapshot SHA256 and Restore Verification #2` dijalankan pada commit workflow `a42cdbebec90dc9279160e5e062a4fbf98d85df1` dengan source checkout yang dipatok ke `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`.

Bukti run #2:
- Run ID `44870810068`; conclusion SUCCESS; runner Ubuntu 24.04.
- Source tree: 531 tracked file blobs, 237 directory entries, tidak ada symlink/submodule.
- SHA-256 dihitung atas byte aktual setiap tracked file; manifest memuat PATH, SIZE_BYTES, SHA256.
- Restore ke direktori disposable terpisah: expected 531, restored 531, missing 0, extra 0, changed 0; `RESTORE_VERIFICATION=PASS`.
- Artifact ID `14693289226`, nama `w0-source-snapshot-7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`, ukuran 808.845 byte, retention 90 hari (sampai 19 Februari 2027).
- SHA-256 manifest: `af72d7556d4c96c5ea9e6ec0db226870cdb19df076f4a6828dd7d07a22f45a02`.
- SHA-256 snapshot tar.gz: `6fca2435b62f17dbbf339935083725540148fffeab943acd14d2a7db44b3bc46`.
- SHA-256 artifact ZIP: `efe94178561b17ccb956f8bcd373d01e28c28a90c42718bfdc2eb4c673cd0bd8`.
- Artifact diunduh dan diuji lagi secara independen di container: 531 manifest rows, 531 restored files, missing=[], extra=[], changed=[]; PASS.
- Tidak ada database yang digunakan.

Percobaan workflow #1 (run `44870798226`) gagal karena expression checkout masih memiliki karakter escape yang keliru. Workflow diperbaiki pada commit `a42cdb...`; run #2 di atas adalah hasil final yang lulus.

### 5.4 Batas pengujian

- CI PHP 8.3/MySQL 8 disposable benar-benar dijalankan oleh GitHub Actions.
- Tidak ada PHPUnit lokal pada runtime sesi ini; PHP lokal yang tersedia bukan PHP 8.3 dan Composer/MySQL/Docker tidak tersedia.
- Matriks acceptance Web Admin T-001 s.d. T-132 tidak otomatis dianggap seluruhnya lulus hanya karena suite 480 test lulus. Kesesuaian test per acceptance ID harus dipetakan dan bukti masing-masing dilampirkan.
- Browser/mobile visual, concurrency assignment MySQL lintas transaksi, restore backup disposable, UAT Admin/Guru/Madrasah, dan deployment tidak dijalankan pada W0.
- Tidak ada pengujian terhadap database produksi.

## 6. STATUS GATE W0

| Kriteria | Status | Catatan |
|---|---|---|
| Working source disetujui dan SHA dipin | PASS | Recovery commit `7fc45bf...` |
| Branch kerja terpisah dibuat | PASS | `work/web-admin-w0-20261121` |
| Branch recovery tidak diubah | PASS | HEAD tetap `7fc45bf...` |
| Recursive source manifest dibuat | PASS | 531 blob records; 768 tree entries |
| Manifest diverifikasi ulang | PASS | Git blob SHA `f74b89...`; 71.665 byte |
| Route/action-permission static review | PASS | Temuan dan gap tercatat di laporan ini |
| Source aplikasi sama dengan PR #5 head | PASS | Recovery additions hanya dokumentasi; app source tidak berubah |
| SHA-256 per-file | BLOCKED | Manifest memakai Git blob SHA-1; SHA-256 tidak dihitung dalam sesi ini |
| Restore snapshot pada disposable workspace | NOT RUN | Runtime lokal tidak menyediakan Git checkout/container test; backup branch tetap tersimpan di Git |
| ZIP baseline raw-byte/CRC/SHA recheck | BLOCKED | Raw archive tidak dapat dimaterialisasi; checksum historis tidak dihitung ulang |
| Browser/responsive/UAT | NOT RUN | Di luar audit statis W0 |
| W1 | NOT STARTED | Menunggu pemeriksaan dan persetujuan laporan/checkpoint W0 |

## 7. Keputusan keamanan dan langkah berikutnya

W0 menghasilkan dasar yang cukup untuk menyusun patch plan, tetapi **tidak membuka W1 otomatis**. Sebelum W1:
1. Pengguna meninjau laporan dan checkpoint ini.
2. Tentukan apakah Git tree/blob manifest dan backup branch diterima sebagai bukti backup W0 sementara, atau SHA-256 per-file/restore test wajib diselesaikan terlebih dahulu.
3. Provenance ZIP baseline tetap ditelusuri terpisah; jangan mengklaim identik sebelum raw archive dapat diverifikasi.
4. Setelah gate W0 diterima, W1 hanya dimulai pada branch kerja terpisah dan setiap file yang akan diubah harus memiliki backup sebelum patch.

**Kesimpulan:** AUDIT STATIS W0 selesai dan terdokumentasi; gate integritas W0 masih PARTIAL/BLOCKED pada SHA-256 per-file, restore test, dan raw ZIP provenance. W1 belum dimulai.
