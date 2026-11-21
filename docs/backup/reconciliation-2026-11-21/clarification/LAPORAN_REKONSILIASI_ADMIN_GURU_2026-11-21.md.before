# LAPORAN REKONSILIASI DOKUMENTASI DAN AUDIT INDEPENDEN — ADMIN/GURU
Tanggal: 21 November 2026 (WIB)
Repository: https://github.com/zane0184/cbt_november
Branch dokumentasi hasil: `docs/canonical-reconciliation-admin-guru-20261121`
Mode: audit GitHub/source read-only + perubahan dokumentasi saja

## A. AUDIT

### A.1 Branch dan HEAD yang diverifikasi ulang
| Branch | HEAD |
|---|---|
| `recovery/source-preservation-20261121` | `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0` |
| `work/web-admin-w0-20261121` | `2b4321ce032fd5e67cf022da8564a5287b8cef6f` |
| `docs/curriculum-kbc-w6-requirements-20261121` | `4a7f7c3ed56dc2fb10ce477418cebc5a2394a0d4` |
| `docs/web-admin-master-spec-20261121` | `8956906c9f0750ba9c6ce845582ae279c9f68e74` |
| `docs/admin-web-spec-20261121` | `3e0b9195c9768c0d892322f926f25a6b9c4fb135` |
| `docs/web-admin-agent-handover-20261121` | `a7bef3dea2b1f57c79d34c775822bd5bde24998f` |
| `docs/web-admin-audit-reconciliation-20261121` | `7617bf07e0e7f83935d5be880652cc65c355566a` |
| `docs/source-canonical-design-closure-20261121` | `59aa76ae60de396216e9754d200cb1b6d806e9e0` |
| `docs/web-admin-w0-recheck-20261121` | `fb058f05264b8d464d687583c53f06fbb7fe54f7` |
| `docs/project-continuity-policy-20261121` | `c3efd541d1f7d321f2d2f6ba1495c346c9992785` |
| `docs/prompt-induk-audit-20261120` | `396b06aaaa0a84b0cef355441fc39b93bd170126` |

Branch tambahan yang terlihat dalam inventaris repository mencakup backup P0.95D, source import, P0.96G, P0.95D, work schema, work portal, dan hotfix Fase1C. Branch tersebut tidak otomatis menjadi kandidat source Web Admin.

### A.2 Hubungan antarbranch
- Recovery → W0: W0 ahead 18 / behind 0. Perubahan empat path saja: workflow snapshot, checkpoint, laporan audit, manifest. Tidak ada source aplikasi berubah.
- W0 → Kurmer-KBC: diverged ahead 4 / behind 4, merge base `cbb7cda6ebfbd3792435d6dbfebed8b1cbc406af`. Empat dokumen W6 berada pada branch terpisah.
- Handover → source-canonical: source-canonical ahead 6 / behind 0; enam perubahan dokumentasi.
- Source-canonical → W0-recheck: W0-recheck ahead 2 / behind 0; menambahkan laporan read-only W0 dan checkpoint.
- Master-spec → source-canonical: diverged, source-canonical ahead 45 / behind 27. Tidak fast-forward.
- Master-spec dan W0-recheck juga memiliki riwayat bercabang dari common ancestor, bukan satu urutan commit. Karena itu tidak dilakukan merge; entry point dan dokumen terkait disalin secara eksplisit ke branch konsolidasi dengan backup.

### A.3 PR
Status detail GitHub:
| PR | Status | Base | Head | Catatan |
|---|---|---|---|---|
| #1 | OPEN / Draft / Unmerged | `p0-95d-migration-gate-20261118` | `hotfix/fase1c-mysql8-fk-name-20261119` | P0/Fase1C |
| #2 | CLOSED / Unmerged | `main` | `work/schema-dump-export-20261119` | Ditutup tanpa merge |
| #3 | OPEN / Draft / Unmerged | `main` | `work/schema-dump-export-20261119` | P0.95D |
| #4 | OPEN / Draft / Unmerged | `work/schema-dump-export-20261119` | `fix/p0-96g-installer-private-db-config-20261120` | P0.96G |
| #5 | OPEN / Draft / Unmerged | `fix/p0-96g-installer-private-db-config-20261120` | `fix/web-portal-usability-rbac-20261120` | Web CBT source; head `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2` |
| #6 | OPEN / Draft / Unmerged | `fix/web-portal-usability-rbac-20261120` | `docs/admin-web-spec-20261121` | Spesifikasi awal |
| #7 | OPEN / Draft / Unmerged | `docs/admin-web-spec-20261121` | `docs/web-admin-agent-handover-20261121` | Paket handover lama |

Tidak ada PR yang diubah, ditutup, diretarget, atau digabung. PR #5/#6/#7 bukan bukti bahwa dokumen terbaru sudah terintegrasi.

### A.4 Dokumen yang dibaca dan rekonsiliasi
Dokumen inti pada W0-recheck:
- Spesifikasi rinci `SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md`, blob awal `fd26a78b8c096026f9e1704fed6dfc45c77e2afe`, 1.703 baris.
- Register `REGISTER_KEPUTUSAN_WEB_ADMIN.md`, blob awal `85073cbd8ded633ebbc6d4efa85e2aa84f066299`.
- Matriks audit 23 modul, blob awal `707d26d7fd538f422ec18816b8327b5f1c8f1a3b`.
- Matriks test T-001–T-132, blob awal `80e8f308db2925754cad618613a755aa2aff1123`.
- Peta provenance, blob awal `6d346a328bc9f0eab718f67276eadf42c60dc940`.
- Prompt agent, blob awal `6755c2c5953b75b35ab0cec3528602d1836e3c26`.
- Paket handover, handover, checkpoint, laporan audit W0 dan audit rekonsiliasi dibaca untuk memahami hubungan dan status historis.

Dokumen W6 pada branch terpisah:
- Supplemental spec `ee4d02fae6d00301dfcb773a2fdfcb93fd192a76`.
- Register W6 `98159ac46794f62428012897edc7d1525b2f606a`.
- Matriks audit/test W6 `b1109886f40f4a9e9e2d0f74daf4587d36c67b9a`.
- Checkpoint W6 `289a07c088c2d4744eaaf9e159979ecf18533ee2`.

Master entrypoint 360 baris pada branch master-spec dan laporan rekonsiliasi 250 baris juga dibaca. Entry point sebelumnya sudah mengunci satu ADMIN_MADRASAH dan no runtime alias, tetapi beberapa kalimat/label masih menyebut “Admin = Operator”; diperjelas menjadi role Operator dihapus dan legacy migration-only.

### A.5 Konflik dan koreksi yang ditetapkan
1. Role Operator: teks lama “Admin Madrasah = Operator” berpotensi menyiratkan role tetap ada. Ketentuan aktif: hanya ADMIN_MADRASAH; OPERATOR bukan role, portal, dashboard, permission profile, atau runtime alias.
2. Batas Admin: spesifikasi ringkas masih memberi ruang statistik ujian agregat; keputusan terbaru melarang monitoring operasional dan analitik ujian Guru. Admin hanya metadata minimum untuk assignment.
3. Guru: pemilik seluruh operasional CBT dan tidak memerlukan approval Admin jika permission/assignment/validasi terpenuhi.
4. Proktor: role permanen dihapus; Guru aktif mendapat capability melalui assignment sementara.
5. Assignment: status minimum PENDING, CONFIRMED, NEEDS_REVIEW, REVOKED, EXPIRED, COMPLETED; PENDING dan status terminal/review tidak memberi capability; hanya CONFIRMED yang lolos validasi dan interval waktu.
6. Reschedule: hanya Guru; API dan Livewire melalui ExamScheduleChangeService; assignment terdampak NEEDS_REVIEW.
7. Panca Cinta: urutan terbaru Cinta Allah dan Rasul-Nya, Cinta Ilmu, Cinta Lingkungan, Cinta Diri dan Sesama Manusia, Cinta Tanah Air. Dokumen lama yang menempatkan Diri dan Sesama pada urutan ketiga adalah SUPERSEDED.
8. DPL: redaksi terbaru “Keimanan dan ketakwaan kepada Tuhan YME”. Nomenklatur tetap perlu diperiksa terhadap sumber primer sebelum seed/import.
9. ZIP baseline 6 November: dokumen W0-recheck yang menyebut raw ZIP sebagai BLOCKER adalah catatan historis yang digantikan oleh keputusan pengguna. ZIP tidak dibuka.
10. CI #671: status yang tercatat pada laporan lama tidak boleh dianggap sebagai hasil terkini. Tidak ada CI baru dijalankan pada audit ini.
11. Referensi laporan W0: checkpoint lama menyebut commit `8073cbd4...`; laporan yang disebut pada histori branch lebih baru terkait commit `9e6e6f0e9693fb728ca8c0040b92463e15f58f0a`. Catatan lama dipertahankan sebagai histori.

### A.6 Source aplikasi diperiksa read-only pada recovery HEAD
| Path | Blob SHA | Temuan |
|---|---|---|
| `database/seeders/DatabaseSeeder.php` | `bb9a55dd16ac7b7e56b79dc8f5ccaca689416868` | Seeder membuat ADMIN_MADRASAH dan OPERATOR. |
| `app/Services/RolePermissionBootstrapper.php` | `fac82573de84c52b52867afbbf7e1637164835b4` | Admin menerima exam.manage/assign/monitor/close/token/grade/reopen; OPERATOR grants terpisah. |
| `app/Services/LoginIdentityResolver.php` | `ec1da249e643b48094459bc3eb51ce1b5e58ab39` | Staff roles menerima ADMIN_MADRASAH, ADMIN, OPERATOR, KAMAD, PROKTOR. |
| `app/Livewire/Admin/QuestionBank.php` | `a3013b528015daf4214dd1aa567eba950b03663a` | Mount menerima Admin/Guru untuk question.view; akses UI Admin masih terbuka. |
| `app/Policies/QuestionPolicy.php` | `af94614a2043206922149b0c44a8ec1e3d87767d` | Authoring policy memiliki pembatasan Guru, tetapi read access Admin perlu ditutup. |
| `app/Policies/ExamPolicy.php` | `350c1ddc6bed8738d1af17cacd6c95bc367b7c29` | Admin/Operator masih memiliki jalur close/monitor; Proktor role permanen masih diperiksa. |
| `app/Policies/ExamAttemptPolicy.php` | `b19d6d39d34884d8be464f786340c1a2ab7108b6` | Admin masih dapat masuk ke sebagian akses attempt/score/finalize/reopen sesuai permission. |
| `app/Policies/ReportPolicy.php` | `e53be3994c52d067c1e31fe37edc450c24a0f4b6` | Admin/Kamad menerima report.view; perlu klasifikasi route report administratif vs hasil ujian Guru. |
| `app/Livewire/Admin/ProctorAssignmentManagement.php` | `4e8bd91d54757aa863d35983aace718def2ca2c` | Picker Guru masih memfilter role PROKTOR. |
| `app/Services/ExamProctorAssignmentService.php` | `e16ef5db308714aa98bf283dc5a1545608da0477` | Service memvalidasi PROKTOR; belum ada overlap check lintas assignment Guru. |
| `app/Http/Controllers/Api/V1/ExamController.php` | `bf373858869b470b235e2256e5be6169fbb1f561` | Update start_at/end_at langsung; belum melalui shared schedule service. |
| `app/Livewire/Admin/ExamManagement.php` | `478aaa170ee297b0c8399f049cd9b1ba56cbfb10` | Komponen gabungan Admin/Guru dan update jadwal langsung. |
| `app/Models/User.php` | `74dd080f703d7ef3256b406176e53dad5786a937` | Tidak memakai TenantScoped; perlu audit query/withoutGlobalScopes. |
| `app/Traits/TenantScoped.php` | `cf192a8ccb6ca530122cb105ed3086adb09a3c5a` | Fail-closed pada context kosong untuk model yang memakai trait. |
| `app/Http/Middleware/IdentifyTenant.php` | `376a4b2771c3d9a6b128ad847c150d47ac2977db` | Mengisi TenantContext dari identitas user; catatan lama menyatakan X-Tenant-Override diabaikan. |

Semua temuan di atas tetap OPEN. Pemeriksaan ini statis, tidak membuktikan perilaku runtime, dan tidak mengubah source.

### A.7 Keterbatasan audit
- Tidak ada local worktree yang diperiksa.
- Tidak ada PHP/Laravel runtime, PHPUnit, MySQL, browser, concurrency, import/restore, atau UAT yang dijalankan dalam sesi ini.
- Tidak ada source ZIP dibuka atau dibandingkan.
- Status Device Lease/Fencing tetap HOLD dan tidak disentuh.

## B. DESAIN

### B.1 Hierarki dokumentasi kanonis
1. Keputusan terbaru pengguna.
2. `docs/WEB_ADMIN_MASTER_SPECIFICATION.md` — entry point/navigasi.
3. `docs/SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md` — spesifikasi rinci; Addendum 33 precedence terbaru.
4. `docs/REGISTER_KEPUTUSAN_WEB_ADMIN.md`.
5. `docs/MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md` — 23 modul dipertahankan.
6. `docs/MATRIKS_PENGUJIAN_WEB_ADMIN.md` — T-001–T-132 dipertahankan, T-133–T-168 ditambahkan.
7. `docs/PETA_DOKUMEN_PROVENANCE_CBT.md`.
8. Laporan, checkpoint, prompt, handover, dan dokumen W6 yang commit-pinned.
9. Dokumen lama tetap ada dengan label HISTORICAL/SUPERSEDED/PENDING VERIFICATION.

### B.2 Matriks kewenangan
- Admin: administrasi madrasah, data akademik/master referensi yang diizinkan, data Guru/Siswa, dukungan administrasi, penugasan Proktor.
- Guru: seluruh operasional CBT.
- Proktor: capability sementara dari assignment Guru aktif, bukan role.
- Admin hanya metadata ujian minimum untuk assignment; tidak ada monitoring/analitik hasil CBT.

### B.3 Rencana migrasi Operator (belum dijalankan)
1. Backup database dan hash; restore ke disposable DB.
2. Inventaris roles, permissions, role-user, permission-role, legacy users.role, deleted/active users, tenant, profil, sessions/tokens, audit, FK.
3. Mapping per akun tenant-local ke ADMIN_MADRASAH; jangan merge user record.
4. Konflik multi-role, orphan pivot, tenant mismatch, FK, atau identitas ambigu = BLOCKED/manual review.
5. Transaksi idempoten; preserve ID/password hash/tenant/profile/history/audit.
6. Exact permission profile Admin; jangan union grant lama.
7. Setelah verifikasi semua referensi, hapus role/pivot/grant OPERATOR aktif melalui migration terencana yang disetujui.
8. Uji login, permission parity, direct route/API, audit trail, rollback/recovery pada disposable DB.
9. Tidak menjalankan migration atau DML dalam tahap ini.

### B.4 Rencana pembatasan permission Admin
- Cabut akses Admin ke question bank, question payload/key, exam CRUD/config/schedule/roster/publish/token/snapshot, attempt/answer/score, grading, result/analytics, monitoring/close/finalize/reopen, reset/force-submit/takeover/time extension.
- Sediakan list metadata ujian read-only yang terpisah dari UI operasional Guru.
- Admin tetap dapat membuat/review/confirm/revoke assignment Proktor.
- Authorization backend wajib berlaku pada endpoint langsung; menu disembunyikan bukan bukti keamanan.

### B.5 Rencana assignment Proktor
- Semua Guru aktif tenant-local dapat dipilih.
- Assignment mengikat exam, rombel, Guru, interval, status, schedule revision, actor, reason, audit event.
- Interval [start,end); overlap formula dan lock order InnoDB sebagaimana spesifikasi.
- Hanya assignment CONFIRMED yang valid dan berada pada waktu server memberi capability.
- Reschedule oleh Guru memakai ExamScheduleChangeService dan mengubah assignment terdampak ke NEEDS_REVIEW.

## C. IMPLEMENTASI DOKUMENTASI

### C.1 Branch baru
Dibuat branch dokumentasi `docs/canonical-reconciliation-admin-guru-20261121` dari `docs/web-admin-w0-recheck-20261121` pada HEAD `fb058f05264b8d464d687583c53f06fbb7fe54f7`. Tidak ada merge atau perubahan branch lama.

### C.2 Backup
Enam dokumen sebelum perubahan disalin ke:
`docs/backup/reconciliation-2026-11-21/`

Manifest:
`docs/backup/reconciliation-2026-11-21/MANIFEST.tsv`

Manifest mencatat path asli, Git blob SHA, SHA-256 konten UTF-8, path backup, dan commit backup. Backup adalah salinan pada branch GitHub baru, bukan backup lokal. ZIP baseline tidak disentuh.

### C.3 File yang diperbarui/ditambahkan
- Entry point `docs/WEB_ADMIN_MASTER_SPECIFICATION.md` disalin dari branch master-spec dan diperjelas; commit awal `2428d4ec10f12c4847ef3ff89b0468c821eba1dc`.
- `docs/REKONSILIASI_DOKUMENTASI_W0_2026-11-21.md` disalin dan diberi addendum status branch/PR/precedence; commit `cc31e1775e97c9f28fdf16716d36e0ed03b8a21e`.
- `docs/SPECIFIKASI_INDUK_WEB_ADMIN_MADRASAH.md` ditambah Addendum 33; blob baru `9ae6333f8c2fe09dc219da476be0438cef362a73`, commit `0517c19064240f0d652b80ee9193bf18ef2cf8a6`.
- `docs/REGISTER_KEPUTUSAN_WEB_ADMIN.md` ditambah DEC-WA-019 s.d. DEC-WA-025; blob baru `2d062810466414c8cd369255e5a6c9f37d299f50`, commit `168e10a33ed277420d58af7ffb568ae8b68f8b91`.
- `docs/MATRIKS_AUDIT_WEB_ADMIN_TEMPLATE.md` ditambah cross-cutting control; blob baru `851576b8615da11e2da5d300f8befea8382be988`, commit `eeb5edcaf03b7386fa140efe624d4c5aacd501c2`.
- `docs/MATRIKS_PENGUJIAN_WEB_ADMIN.md` ditambah T-133 s.d. T-168; blob baru `a58c3ccbae6b2aa19fb9e5dfa26e9665d8b689e5`, commit `f58845929d1161c12b31abdabfe34e14311d00f7`.
- `docs/PETA_DOKUMEN_PROVENANCE_CBT.md` ditambah branch/status provenance; blob baru `e4c1b0260c902e62da2e92e849cd3c7f8359d0b9`, commit `4c2250dc86c9398506f81f1e1fd8078e36683071`.
- `docs/PROMPT_INDUK_AGENT_BARU_WEB_ADMIN.md` ditambah addendum keputusan terbaru; blob baru `d793878e3d566a8e0a664e8cf6f2e6d67b6d9057`, commit `d288296e388a7d271c50550e056abf8aa7ffa093`.

Checkpoint, laporan rekonsiliasi, dan prompt penerus dibuat sebagai deliverable; pemeriksaan link/diff final dilakukan setelah seluruh dokumen tersedia.

Tidak ada file source aplikasi, migration, seeder, database, .env, secret, konfigurasi VPS, Android, atau Device Lease/Fencing yang diubah.

## D. PENGUJIAN

Status saat laporan ini disusun:
- Branch/HEAD/PR inventory: PASS — data metadata GitHub berhasil dibaca.
- Compare antarbranch: PASS — hasil compare tercatat di laporan.
- Source read-only: PASS — 15 file source kunci berhasil dibaca; temuan dicatat.
- Backup dokumen: PASS — salinan GitHub branch dan manifest dibuat; bukan backup lokal.
- SHA-256: PASS — dihitung atas konten UTF-8 yang diambil dari GitHub sebelum perubahan.
- Link lintas dokumen: PASS — 17/17 tautan lokal pada entry point berhasil di-fetch dari branch konsolidasi.
- Konsistensi dokumen: PASS — matriks audit mempertahankan ADM-001–ADM-023; test matrix mempertahankan T-001–T-132 dan menambah T-133–T-168; register memuat DEC-WA-019–025; spesifikasi memuat Addendum 33 dan urutan Panca Cinta terbaru.
- Backup: PASS — keenam salinan memiliki Git blob SHA identik dengan dokumen sumber; MANIFEST.tsv memuat SHA-256 konten UTF-8.
- Diff hanya dokumentasi: PASS — compare dari base W0-recheck menunjukkan 18 path berubah/ditambahkan dan seluruhnya berada di docs/; tidak ada path source aplikasi.
- CI/runtime/browser/MySQL concurrency/import/restore/UAT: NOT RUN.
- Source aplikasi berubah: NO CHANGE — perubahan yang dilakukan melalui contents API hanya path docs.

Tidak ada klaim W0 selesai, source aman, atau siap produksi.


## E. Verifikasi penutupan dokumentasi
- Entry point lokal: 17/17 tautan valid.
- Matriks audit: 23 modul ADM-001–ADM-023 terdeteksi.
- Matriks test: 168 ID unik; seluruh T-001–T-132 tetap ada dan T-133–T-168 tersedia.
- Backup: 6/6 blob identik dengan file sumber sebelum perubahan; SHA-256 dicatat di MANIFEST.tsv.
- Compare base `fb058f05264b8d464d687583c53f06fbb7fe54f7` ke branch hasil: 18 path dokumentasi; tidak ada file source aplikasi.
- Satu perubahan baris yang terdeteksi pada diff test matrix hanya menambahkan newline terminator ke baris T-132 yang sebelumnya tidak memiliki newline; isi test T-132 tidak diubah.
- Tidak ada CI, PHPUnit, runtime, browser, MySQL concurrency, import/restore, atau UAT dijalankan.
