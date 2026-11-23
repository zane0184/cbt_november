# MATRlKS ADM-01–ADM-23 — WEB ADMIN MADRASAH
Tanggal: 23 November 2026
Acuan normatif: `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md` (main, blob `2d3f8d53417c2387b7d8a1c53a0128880c8d8500`).
Branch audit: `work/solo-web-cbt-completion-20261123-execution`.

> ID ADM-01–ADM-23 di bawah adalah pengelompokan traceability untuk audit spesifikasi, bukan klaim bahwa source sudah lengkap. Semua status awal **NOT VERIFIED** sampai alur UI → backend → database → authorization → audit → test dibuktikan. Matriks ini akan diperbarui setelah tiap modul diaudit.

| ID | Requirement / acceptance scope | Area spec | Bukti minimum | Status awal |
|---|---|---|---|---|
| ADM-01 | Dashboard data nyata sesuai tahun aktif; tanpa angka dummy; tidak menampilkan kunci/jawaban | §2 | Query/service, scope tahun aktif, empty state, test angka DB dan batas data | NOT VERIFIED |
| ADM-02 | Profil madrasah lengkap: identitas, NSM/NPSN, alamat, kontak, nama Kamad sebagai data dokumen | §3.1 | Form + policy tenant.manage + validasi + audit + persistence | NOT VERIFIED |
| ADM-03 | Upload/preview/ganti/hapus logo dan favicon aman | §3.2 | MIME/extension/dimension/size, storage privat, authorization download, delete confirmation, tests upload spoof | NOT VERIFIED |
| ADM-04 | Branding aplikasi/login/footer/media sosial per instalasi | §3.4, §12.2 | Setting model/service, scoped UI, cache invalidation, tests per-install isolation | NOT VERIFIED |
| ADM-05 | Kop dokumen fleksibel + preview + dipakai cetak | §3.3, §12.3 | Template/settings, render preview, PDF outputs, tests layout/data escaping | NOT VERIFIED |
| ADM-06 | CRUD Guru, detail, aktif/nonaktif, profile, histori perubahan/penugasan | §4.1 | User/Profile CRUD, policy, transactions, audit, uniqueness, lifecycle tests | NOT VERIFIED |
| ADM-07 | CRUD Siswa, arsip/lulus/pindah/naik kelas, histori akademik | §4.2 | Profile CRUD, status transitions, history, no destructive deletion tests | NOT VERIFIED |
| ADM-08 | Akun/akses: username, reset password, revoke sessions, first-login policy bila dipakai | §4.3 | Admin self-escalation denied, hashed password, audit, no secret output, tests | NOT VERIFIED |
| ADM-09 | Template + import Guru | §5 | XLSX parser, MIME/size/header/preview/row errors, transaction/idempotency/concurrency | NOT VERIFIED |
| ADM-10 | Template + import Siswa termasuk NIS/NISN/rombel/tahun | §5 | Duplicate/conflict detection, roster validation, preview/confirm, rollback tests | NOT VERIFIED |
| ADM-11 | Export Guru terfilter dan diaudit; tanpa credential | §5 | Authorization, filter/pagination, safe columns, audit event, file test | NOT VERIFIED |
| ADM-12 | Export Siswa terfilter status/kelas/rombel/tahun; tanpa credential | §5 | Authorization, filter correctness, PII minimization, audit, file test | NOT VERIFIED |
| ADM-13 | Tahun pelajaran/semester, satu tahun aktif, arsip dan histori | §6.1 | CRUD/service, unique-active invariant, transaction/concurrency tests | NOT VERIFIED |
| ADM-14 | Kelas/tingkat MI 1–6 dan status | §6.2 | CRUD + constraint grade range + authorization tests | NOT VERIFIED |
| ADM-15 | Rombel, penempatan/mutasi siswa, cegah dua penempatan aktif bertentangan | §6.2 | Transactional transfer/history, uniqueness/locking, audit tests | NOT VERIFIED |
| ADM-16 | Mata pelajaran, kode, kelompok, jenjang, kurikulum/version mapping | §6.3 | CRUD + uniqueness + curriculum relation/version tests | NOT VERIFIED |
| ADM-17 | Penugasan Guru per mapel/rombel/tahun/semester, histori dan pencabutan | §6.4 | Assignment service + active interval + TeacherAccess negative/positive tests | NOT VERIFIED |
| ADM-18 | Registry kurikulum, versi, tahun berlaku, status, registrasi madrasah | §7 | Registry CRUD, owner/source/provenance, immutable historical version tests | NOT VERIFIED |
| ADM-19 | Struktur kurikulum, CP/TP/ATP, pemetaan dan histori read/write sesuai kewenangan | §7 | CP induk read-only; TP/ATP Guru assignment-scoped; no legacy bypass; snapshot provenance tests | NOT VERIFIED |
| ADM-20 | Penugasan/pencabutan Guru Proktor hanya untuk ujian terpusat UTS/UAS/UM | §8 | Canonical central-exam marker, interval overlap MySQL concurrency, audit; no monitoring/roster privilege | NOT VERIFIED |
| ADM-21 | Laporan akademik administratif dan cetak dengan kop | §10 | Report query scope, filters, PDF/XLSX, permission, no exam results/Attempt data for Admin | NOT VERIFIED |
| ADM-22 | Audit aktivitas, login, denied access, import/export, perubahan sensitif | §11 | Audit events completeness, immutable actor/time/target, secrets redacted, query access tests | NOT VERIFIED |
| ADM-23 | Pengaturan umum, branding, tema, akun Admin, backup/restore workflow | §12 | Settings policy, no infrastructure secrets, responsive/a11y, tested backup/restore integrity | NOT VERIFIED |

## Batas kewenangan yang wajib diuji untuk semua baris
- Role Admin tepat satu: `ADMIN_MADRASAH`; Guru tepat satu: `GURU`; Siswa tepat satu: `SISWA`.
- Admin hanya mengelola administrasi madrasah, bukan authoring/operasi ujian Guru, soal, peserta, token, Attempt, jawaban, monitoring atau nilai.
- Admin boleh menetapkan/mencabut Guru sebagai Proktor hanya untuk ujian terpusat UTS/UAS/UM yang memiliki marker kanonis eksplisit. Tidak boleh menebak jenis ujian dari status `EXTERNAL` atau `PUBLISHED`.
- Assignment Proktor tidak memberi akses room/session/seat/roster/monitoring/Attempt/perangkat.
- Satu instalasi + satu database akademik per madrasah; tenant_id adalah defense-in-depth, bukan cross-madrasah sharing.
- CP induk tidak boleh dimutasi melalui endpoint legacy; TP/ATP authoring Guru harus melewati service assignment-scoped.
- Setiap upload/import/export/download/detail/mutation harus validasi scope, ownership, permission, audit, transaksi dan idempotency.

## Status implementasi
Belum ada baris ADM yang dinyatakan VERIFIED pada pembuatan matriks ini. Pemetaan route/class/model/policy/migration/test akan ditambahkan satu per satu setelah audit source; jangan menandai lengkap hanya karena menu atau komponen ditemukan.


---

## ADDENDUM STATUS AUDIT TERBARU — 23 NOVEMBER 2026

Matriks ADM-01–ADM-23 tetap menggunakan status **NOT VERIFIED** sampai ada bukti end-to-end. Penambahan atau pengetatan satu controller/API tidak cukup untuk menandai modul Admin selesai.

### Temuan source yang sudah dapat dicatat (parsial, bukan acceptance PASS)

- **API master akademik legacy:** route generic `curricula`, `phases`, `grade-levels`, dan `elements` pada branch ini dibatasi menjadi read-only `index/show`. Regression tests memastikan jalur mutasi legacy tidak terdaftar. Audit tetap harus mencari route/controller lain yang dapat menulis langsung dan memastikan registry kanonis adalah satu-satunya write path.
- **Laporan/analitik hasil:** `CurriculumAnalyticsController` diperketat agar ringkasan capaian/hasil siswa memerlukan role tunggal GURU dan permission `report.view`; Admin ditolak. Ini bukan implementasi laporan administratif Admin dan bukan integrasi snapshot historis yang lengkap.
- **Installer & role:** postcondition installer diselaraskan dengan role aktif `ADMIN_MADRASAH`, `GURU`, `SISWA`. Ini belum membuktikan seluruh portal, menu, route, API, export, dan direct model access telah sesuai.
- **User/Guru/Siswa CRUD:** audit awal source menunjukkan komponen manajemen pengguna/akademik sebagian besar masih create/list; CRUD penuh, lifecycle status, histori, import/export dan audit belum dibuktikan lengkap.
- **Branding/kop:** model tenant metadata/logo dan service header laporan tersedia, tetapi alur Admin untuk identitas, logo/favicon, branding login/footer, kop fleksibel, preview dan output cetak belum diverifikasi end-to-end.
- **Audit log:** service/model audit tersedia; halaman Admin dan cakupan event login/denied/import/export/perubahan sensitif belum dibuktikan lengkap.
- **Proktor:** service assignment masih fail-closed karena eligibility ujian pusat belum didefinisikan kanonis. Jangan menandai ADM-20 selesai dan jangan membuka hak monitoring/room/session/Attempt.
- **Snapshot kurikulum dan laporan historis:** fondasi mapping assignment/version/snapshot ada, tetapi laporan/capaian historis masih memakai beberapa model legacy dan belum terbukti membaca provenance dari snapshot kurikulum kanonis.

### Status pengujian terkait matriks

- Full PHPUnit terakhir pada exact SHA `b31d7a2713383ddcc1282317cdb295d6992d5dce`: FAIL, 92 failed / 426 passed (1,723 assertions), run [#45168433247](https://github.com/zane0184/cbt_november/actions/runs/45168433247).
- Karena suite belum PASS dan belum ada E2E ketiga portal, tidak ada item ADM yang boleh diberi label VERIFIED.
- Fresh install/upgrade, backup/restore, UAT, dan distribusi ZIP belum dilakukan.

### Pemetaan prioritas lanjutan

| Prioritas | ADM | Tindakan |
|---|---|---|
| P0 | ADM-06–ADM-10, ADM-13–ADM-17 | Lengkapi CRUD Guru/Siswa, akun, import, tahun/semester, rombel, mapel, assignment; uji scope, transaksi, uniqueness, audit dan histori. |
| P0 | ADM-18–ADM-19 | Tutup seluruh jalur write legacy; verifikasi registry, registrasi, CP read-only, TP/ATP assignment-scoped dan provenance. |
| P1 | ADM-02–ADM-05, ADM-23 | Lengkapi identitas, logo/favicon, branding, kop, settings, backup/restore dengan validasi file/storage/policy. |
| P1 | ADM-11–ADM-12, ADM-21–ADM-22 | Export aman tanpa credential, laporan administratif terfilter, audit log lengkap; Admin tidak mengakses nilai/Attempt/analitik hasil siswa. |
| BLOCKED | ADM-20 | Tetap fail-closed sampai eligibility ujian pusat ditetapkan kanonis oleh owner; assignment tidak memberi izin operasional CBT. |

Semua status tetap **NOT VERIFIED / PARTIAL** sampai tiap baris mempunyai bukti route/UI → policy → service → persistence → audit → test dan exact CI PASS.


---

## ADDENDUM VERIFIKASI OWNER-DIRECTED — 24 NOVEMBER 2026

### Scope dan source
- Branch eksekusi: `work/solo-web-cbt-completion-20261123-execution`
- Exact source revision terkini ketika addendum disusun: `cbce0df331784ecfbef50c4da29c3e781a03d7d7`.
- Full PHP baseline sebelumnya telah ditutup pada SHA `9e5a2697ad29e2aaa15d2b7522b7b44a59c5eb51`; perubahan reauthorization Admin selanjutnya divalidasi pada CI #45251932629, SHA `b4592f8a93fd4d306cdab5105c244ba9c6a37eb0`, **523 passed / 2,182 assertions / 0 failed**.
- Perubahan reauthorization Guru + fix Blade berada pada SHA `cbce0df331784ecfbef50c4da29c3e781a03d7d7`; CI #45254396061 masih menjadi bukti yang sedang diperiksa. Hasil focused Guru pada SHA sebelumnya `1c0b72260eb0c19d36fb48fddd092bc58a649e53` gagal karena syntax Blade existing di `result-detail.blade.php`, bukan karena assertion policy; fix telah diterapkan dan harus diuji ulang.
- Tidak ada VPS, production database, migration, deployment, PR merge, Android, atau Web Super Admin yang disentuh.

### Temuan audit yang diperbarui (bukan status modul selesai)
1. Lima komponen Admin yang sebelumnya hanya mengotorisasi mount/render tidak konsisten telah diperkeras: Dashboard, UserManagement, AcademicManagement, CurriculumManagement, dan callback pemilihan Exam pada ProctorAssignmentManagement.
2. Empat halaman hasil/analitik Guru yang sebelumnya mengandalkan otorisasi mount sekarang mengulang policy saat render: ResultList, ResultDetail, AnalyticsDashboard, dan ItemAnalysis.
3. View ResultDetail memiliki directive Blade penutup `@error('manualScore')` yang salah; sudah diperbaiki menjadi `@enderror`. Backup byte-exact view tersedia.
4. Source Siswa terkini menunjukkan runner berbasis IndexedDB dengan attempt_context, answers, sync_queue, server_receipts, conflicts, local_meta, verifikasi receipt request-id/payload-hash/revision dan submit barrier. Ini bukti source, belum bukti browser E2E lintas browser atau UAT.
5. MediaController saat ini memeriksa tenant, Attempt aktif pemilik Siswa, auth-session binding, current lease/fencing, dan snapshot media yang termasuk question_order. Temuan media lama di audit Siswa harus direfresh, bukan disalin sebagai status saat ini.
6. Komponen Web Guru Proktor capability-based belum tersedia secara utuh. Assignment Proktor terpusat tetap fail-closed karena penanda canonical central exam belum diputuskan/tersedia; jangan menafsirkan `assessment_origin=EXTERNAL` sebagai central.
7. Kurmer + KBC tetap satu kerangka. Registry CP/TP/ATP, Panca Cinta, Pembelajaran Mendalam dan 8 dimensi harus memakai sumber resmi/provenance/versi yang terbukti; data referensi yang tidak tersedia tetap NEEDS DATA.

### Matriks status
- ADM-01 sampai ADM-23 tetap **NOT VERIFIED** untuk acceptance end-to-end. Patch otorisasi di atas hanya memperbaiki boundary request Livewire dan tidak menyelesaikan CRUD lifecycle, identitas madrasah, import/export, akademik, kurikulum, branding, audit UI, backup/restore atau UAT.
- ADM-20 tetap **BLOCKED**: belum ada canonical central exam eligibility. Assignment Proktor tidak boleh memberi akses Room/roster/monitoring/Attempt atau operasi ujian.
- W1 memiliki bukti focused reauthorization Admin dan suite penuh pada SHA b4592f8; itu tidak mengubah status fitur ADM menjadi VERIFIED.
- Browser E2E Admin/Guru/Siswa, fresh install + upgrade, recovery/backup-restore, load/concurrency operasional dan UAT madrasah masih **NOT RUN / NOT VERIFIED**.

### File dan backup untuk perubahan terkait
- Admin:
  - Backup `backend-laravel/docs/backup/livewire-admin-render-reauth-2026-11-24/Dashboard.php.before` blob `43624f70f78675e899f2b122318ed8e6382ffa74`.
  - Backup `.../UserManagement.php.before` blob `29f31e68f2272b1ea870415f3635b0ad8bc88b8e`.
  - Backup `.../AcademicManagement.php.before` blob `63652d47345962c1cd49e08420956453765e9373`.
  - Backup `.../CurriculumManagement.php.before` blob `992bb7c96981b781bf89240ca68d0440f06b32bc`.
  - Backup `.../ProctorAssignmentManagement.php.before` blob `107e8bac028a161f8c4f85862b5670b7ec32da22`.
  - Backup CI `.../tenant-backend-ci.yml.before` blob `4bc80531b4ee4a4bda4c47a053deefa1e8ae1a27`.
- Guru:
  - Backup `backend-laravel/docs/backup/teacher-result-render-reauth-2026-11-24/ResultList.php.before` blob `fd60539cb33bd33d0c183b980a84f567681a4d68`.
  - `.../ResultDetail.php.before` blob `6f959b61eb04ca9d0ee8005009a5e82b3b98a016`.
  - `.../AnalyticsDashboard.php.before` blob `90cabf8c0b61775ac79ede8a6d2d6e5417dfa336`.
  - `.../ItemAnalysis.php.before` blob `b82649219c0c32310b1ce4ea84a4a21d1fb071b9`.
  - Backup view `.../result-detail.blade.php.before` blob `c72c6b95703a105225a084ffab8344a83f9ec6dc`.
- Seluruh backup di atas diverifikasi identik dengan isi source sebelum patch.

### Next
1. Selesaikan CI pada exact SHA cbce0df3… termasuk empat focused Teacher reauthorization tests dan full PHPUnit.
2. Lanjutkan gap review ADM-01..23 berbasis source aktual, tanpa menandai modul complete dari keberadaan menu.
3. Implementasikan DOCX import hanya setelah kontrak parser/draft/batch API terverifikasi; XLSX question import tetap disabled.
4. Selesaikan integrasi KM+KBC/provenance dan immutable assessment/question/exam snapshots tanpa data resmi rekaan.
5. Tutup batas portal Guru/Siswa/Admin dan acceptance API/browser E2E.
6. Fresh install/upgrade/backup/restore dan UAT tetap gate sebelum ZIP final/staging VPS.
