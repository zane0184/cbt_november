# HANDOVER LENGKAP AGENT PENGGANTI — CBT MADRASAH
Tanggal pemeriksaan: 23 November 2026
Repository: `zane0184/cbt_november`
Branch kerja: `work/solo-web-cbt-completion-20261123`
PR integrasi: [#21 — Solo Web CBT](https://github.com/zane0184/cbt_november/pull/21)
HEAD yang diverifikasi saat laporan dibuat: `dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4`

> **STATUS: WORK IN PROGRESS — NOT PRODUCTION READY — NOT READY FOR OPERATIONAL EXAM — NOT READY FOR DISTRIBUTION.**
> Dokumen ini adalah handover berdasarkan GitHub. Source aktual dan CI exact SHA tetap menjadi kebenaran. Agent baru wajib memverifikasi ulang HEAD/CI sebelum tindakan.

## 1. Instruksi wajib untuk agent pengganti

Baca berurutan:
1. [AGENT_START_HERE.md](AGENT_START_HERE.md)
2. Dokumen ini.
3. [PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md](PROMPT_AGENT_PENGGANTI_CBT_2026-11-23.md) — prompt operasional siap salin.
4. [AGENT_HANDOVER_MASTER_2026-11-23.md](AGENT_HANDOVER_MASTER_2026-11-23.md)
5. [CHECKPOINT_LANJUTAN_WEB_CBT_2026-11-23.md](CHECKPOINT_LANJUTAN_WEB_CBT_2026-11-23.md)
6. [REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md](REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md)
7. [DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md](DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md)
8. [REPORT_CI_FULL_PHPUNIT_REMEDIATION_2026-11-23.md](REPORT_CI_FULL_PHPUNIT_REMEDIATION_2026-11-23.md)
9. [REPORT_AUDIT_ANTI_CHEAT_WEB_SISWA_MOBILE_FIRST_2026-11-23.md](REPORT_AUDIT_ANTI_CHEAT_WEB_SISWA_MOBILE_FIRST_2026-11-23.md)
10. Keputusan owner terbaru di [Issue #20](https://github.com/zane0184/cbt_november/issues/20), serta arahan koordinasi [Issue #10](https://github.com/zane0184/cbt_november/issues/10).

Setelah itu refresh HEAD branch kerja, PR #21, PR #13/#18/#19, commits, reviews, workflows exact SHA, issue comments, spesifikasi, routes, Livewire, controllers, services, models, policies, migrations, tests, backups, dan checkpoint. Jangan menganggap dokumentasi lama sebagai fakta bila source/CI berbeda.

## 2. Keputusan owner yang tidak boleh berubah

### Portal, role, instalasi
- Portal aktif sekarang hanya **Admin Madrasah, Guru, Siswa**.
- Android native dan Web Super Admin ditunda sampai **Juni 2027**.
- Tidak ada portal Operator, Kamad, atau Proktor permanen.
- Semua pekerjaan administratif Operator dilaksanakan Admin Madrasah.
- Role resmi: `ADMIN_MADRASAH`, `GURU`, `SISWA`. Role Kamad/Operator tidak boleh dihidupkan kembali; histori lama jangan dihapus sembarangan.
- Proktor adalah penugasan sementara pada Guru, bukan role/portal. Berdasarkan keputusan owner Issue #20 W7, assignment pengawas **tidak memberi hak room/session/seat/roster/monitoring/Attempt/operasi CBT**.
- Setiap madrasah memakai satu instalasi dan database mandiri. Tidak ada multi-tenant akademik lintas madrasah atau pertukaran data akademik antarmadrasah.
- Integrasi pusat mendatang hanya lisensi/entitlement/pembayaran; Web Super Admin ditunda sampai Juni 2027.
- Jangan membangun ulang SaaS multi-tenant akademik. Tetap validasi scope instalasi lokal, ownership, role, permission dan assignment di server.

### Kurikulum satu kesatuan
Kurikulum Merdeka + Kurikulum Berbasis Cinta/Panca Cinta + Pembelajaran Mendalam + delapan dimensi profil lulusan adalah **satu ekosistem akademik**, bukan tiga kurikulum/modul/database terpisah.

Alur target:
`sumber resmi/version` → `registrasi kurikulum` → `tahun ajaran/semester/fase/kelas/mapel` → `TeacherSubjectAssignment` → `CP resmi` → `TP/ATP Guru` → `materi/topik` → `Panca Cinta + Pembelajaran Mendalam + 8 dimensi` → `QuestionVersion` → `Assessment` → `immutable Exam/Question snapshot` → `Attempt/Answer/Score` → `capaian dan laporan historis`.

- Kurikulum Merdeka adalah kerangka akademik/CP/TP/ATP.
- Panca Cinta adalah nilai/topik lintas pembelajaran.
- Pembelajaran Mendalam adalah pendekatan pedagogis, bukan kurikulum atau jenis ujian.
- Delapan dimensi profil lulusan adalah lensa/hasil profil, bukan pengganti CP/TP.
- CP resmi harus bersumber, berversi, memiliki provenance, dan read-only.
- Guru mengembangkan TP/ATP sesuai assignment dan versi kurikulum yang dipatok.
- QuestionVersion dan snapshot ujian menyimpan referensi/version/provenance agar histori tidak berubah.
- Laporan memakai konteks versi/snapshot yang sama. Jangan membangun sumber kebenaran kedua.

### Kewenangan dan CBT
- Guru membuat, mengatur, menjadwalkan, dan menerbitkan ujian sendiri sesuai assignment tanpa persetujuan Admin.
- Admin mengelola administrasi, master data, sinkronisasi, dukungan teknis, audit dan fungsi administratif; bukan authoring/operasi ujian Guru.
- Admin tidak mengambil alih soal, ujian, peserta CBT, token, Attempt, jawaban, monitoring, penilaian atau nilai.
- Exact-one-role, permission, assignment, ownership, status, scope instalasi dan waktu efektif harus divalidasi di server.
- Token otomatis dibuat saat publish sesuai kontrak; ujian hanya mulai pada jadwal berdasarkan waktu server.
- Attempt, Answer, Score, device lease, fencing, idempotency, submit dan recovery harus konsisten.
- Kamera bukan syarat CBT standar.
- Reset Attempt, force submit, takeover, perpanjangan waktu wajib otorisasi dan audit.
- Browser deterrent bukan secure kiosk; jangan klaim mencegah screenshot/kamera/perangkat kedua.
- DOCX prioritas import soal; jangan hidupkan XLSX question import.
- SHORT_ANSWER tetap BLOCKED sampai accepted answer privat, snapshot dan scoring end-to-end terbukti.

## 3. Posisi GitHub yang diverifikasi

| PR | HEAD | Base | Status |
|---|---|---|---|
| #21 Integrasi | `dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4` | `work/solo-web-cbt-ci-base-20261123` | OPEN / DRAFT / UNMERGED |
| #13 Web Siswa | `9ef375c407b1faf606c5a268f00b24424d93be9e` | `work/stage4d-account-lifecycle-20261122` | OPEN / DRAFT / UNMERGED |
| #19 Web Guru | `71296ca6e7c48b7295455a26d38bda11b901eb1d` | `work/web-guru-phase1-audit-implementation-20261122` | OPEN / DRAFT / UNMERGED |
| #18 Web Admin lama | `42a0de2c1f86b8e76e34e1b74ab0feaca90def3a` | `work/web-admin-implementation-20261123` | OPEN / DRAFT / UNMERGED |

Tautan: [PR #21](https://github.com/zane0184/cbt_november/pull/21) · [PR #13](https://github.com/zane0184/cbt_november/pull/13) · [PR #19](https://github.com/zane0184/cbt_november/pull/19) · [PR #18](https://github.com/zane0184/cbt_november/pull/18) · [Issue #20](https://github.com/zane0184/cbt_november/issues/20) · [Issue #10](https://github.com/zane0184/cbt_november/issues/10).

PR #13/#18/#19 memiliki base sendiri; tidak otomatis masuk ke #21. Jangan cherry-pick/merge seluruh branch tanpa audit diff dan keputusan owner. PR #18 memuat desain room/session/multi-Proktor lama yang perlu disaring ketat.

## 4. CI exact SHA terbaru yang diketahui

| Workflow | Run | SHA | Hasil |
|---|---|---|---|
| Tenant Backend CI | [#45156925960](https://github.com/zane0184/cbt_november/actions/runs/45156925960) | `dbebbd5...` | FAILURE |
| Agent 4 Web Siswa | [#45156926014](https://github.com/zane0184/cbt_november/actions/runs/45156926014) | `dbebbd5...` tercatat sebagai head pemicu; job checkout source PR Agent 4 sesuai workflow | SUCCESS |

Backend #45156925960:
- MySQL disposable, PHP 8.3, identity check, Composer, package discovery, disposable installer DB: PASS.
- Focused Web login/tenant smoke tests: PASS.
- Full PHPUnit: FAILURE.
- Jumlah failure dan root cause run ini belum dapat diverifikasi karena arsip log job mengembalikan 404. Jangan menyalin angka dari run lama dan menganggapnya hasil run ini.
- Cek ulang log/artifact dan metadata workflow ketika agent baru mulai.

Web Siswa #45156926014:
- Runner static audit, Chromium E2E, WebKit compatibility: PASS.
- Selected Laravel Student result visibility, exam portal, login/logout jobs: PASS.
- Batas bukti: WebKit compatibility bukan UAT iOS Safari fisik; browser E2E Agent 4 memakai mock API, belum browser-to-real-Laravel integration.

Run backend sebelumnya [#45156010156](https://github.com/zane0184/cbt_november/actions/runs/45156010156) pada `d886b038...` juga focused login/tenant PASS dan full PHPUnit FAILURE. Bukan status source terbaru.

## 5. Perubahan terbaru koordinator pada PR #21

Commit terakhir saat laporan dibuat: `dbebbd5b50ecb7a0a872272c3aa5bdd0642b27f4`.

### AUDIT
- Dropdown filter Admin masih menampilkan OPERATOR/KAMAD meski role resmi tinggal Admin/Guru/Siswa.
- API `materials` legacy dapat menulis materi di luar jalur TeacherMaterialManagement yang mewajibkan assignment Guru.
- Jalur Guru aktif yang diperiksa menggunakan service assignment-scoped.
- Model Tenant menyimpan metadata/logo dan ReportHeaderService dapat menyusun header dokumen, tetapi UI Admin identitas/branding/kop belum terbukti lengkap.
- AuditService/AuditLog ada, tetapi halaman audit log Admin belum terbukti.
- UserManagement, AcademicManagement, StudentRombelManagement, TeacherSubjectAssignmentManagement, curriculum registry dan proctor assignment history masih parsial; ADM-01–ADM-23 belum terbukti lengkap.

### BACKUP
Empat file existing dicadangkan dan dibaca ulang byte-exact:
- `backend-laravel/routes/api.php`
- `backend-laravel/app/Http/Controllers/Api/V1/MaterialController.php`
- `backend-laravel/tests/Feature/AdminUiVerificationTest.php`
- `backend-laravel/resources/views/livewire/admin/user-management.blade.php`

Backup: `backend-laravel/docs/backup/solo-web-cbt-2026-11-23/`, suffix `.before-legacy-material-rbac-and-role-filter`.

### IMPLEMENTASI
Pada commit `dbebbd5`:
1. `routes/api.php`: route API materials dibatasi index/show.
2. `MaterialController.php`: disederhanakan ke index/show dengan authorize viewAny/view. Compare menunjukkan 8 additions/47 deletions; metode lama dihapus. **Wajib audit kompatibilitas, query scope, response shape, policy dan pemanggil sebelum menyimpulkan aman.**
3. `AdminUiVerificationTest.php`: regression assertion untuk filter role legacy dan route material read-only.
4. `user-management.blade.php`: opsi filter role legacy dihapus.
Tidak ada migration produksi, deployment, merge PR atau DB produksi.

Sebelum HEAD ini, branch sudah memuat patch login resolver SYSTEM_GLOBAL, exact-role/policy, token optional, receipt/client_request_id, fencing, dan patch test/API legacy yang terdokumentasi. **Verifikasi source/route:list pada exact HEAD; jangan ulang patch tersebut.**

## 6. Backlog terurut — pekerjaan yang belum selesai

### P0 — Backend dan full PHPUnit
1. Ambil log/artifact #45156925960. Jika tetap tidak tersedia, jalankan ulang workflow pada exact source SHA setelah rencana tes dibuat.
2. Triase failure per root cause: fixture, kontrak API, authorization, migration/seeder, ViewException, atau source bug.
3. Jangan ubah policy agar fixture lama tanpa assignment menerima 200.
4. Full PHPUnit wajib PASS pada exact HEAD.
5. Two-connection MySQL concurrency harus benar-benar dijalankan dan PASS; SKIPPED bukan PASS.
6. Validasi local installation, exact-one role, session binding, lease/fencing, token optional, receipt/replay, stale request, expiry, submit dan recovery.
7. Audit dampak MaterialController read-only: route consumers, policy, scope, pagination/filter, response compatibility.
8. Audit endpoint master/kurikulum/assessment legacy satu per satu; jangan hapus massal.

### P1 — Web Siswa
1. Baca PR #13 source/checkpoint; jangan membuat ulang IndexedDB runner/mobile-first/deterrent yang sudah ada.
2. Integrasikan runner ke Laravel nyata dan MySQL disposable.
3. Uji login, start/resume, jadwal/token, heartbeat, lease/fencing, answer save/retry/receipt, media, conflict 409, submit barrier, expiry, response loss, offline/reconnect, recovery.
4. Uji multi-tab/device, concurrent start, stale fence, duplicate request, submit/expiry race.
5. Focus/visibility hanya audit signal; jangan auto-submit/punish karena blur/focus.
6. Uji Android Chrome dan iOS Safari fisik saat tersedia; Playwright WebKit bukan Safari fisik.

### P2 — Web Guru
1. Baca spesifikasi Guru di main dan Issue #10 comment `7081649923`.
2. Refresh PR #19 exact source/CI.
3. Audit assignment → CP/TP/ATP → materi/topik → bank soal/QuestionVersion → Assessment → Exam Builder → publish/token/jadwal → monitoring Guru pembuat → grading → hasil/analytics.
4. DOCX tetap prioritas; parser/private staging/validation/rollback/media authorization harus diuji.
5. Proktor tidak menjadi portal permanen atau grant monitoring.
6. SHORT_ANSWER tetap blocked hingga accepted answer privat, immutable snapshot dan scoring terbukti.

### P3 — Web Admin
Gunakan [master spesifikasi Admin](https://github.com/zane0184/cbt_november/blob/main/docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md).

Buat matriks ADM-01–ADM-23:
`requirement → route → Livewire/controller → service → model → policy → schema/migration → test → status → bukti`.

Prioritas: profil/identitas/logo/branding/kop; CRUD Guru/Siswa; akun/akses; import/export Guru/Siswa; tahun ajaran/semester/kelas/rombel/mapel/assignment; registry kurikulum; laporan/cetak administratif; audit log; pengaturan; backup/restore. Cari implementasi tersembunyi sebelum membuat ulang. Admin tidak boleh mendapat akses CBT/soal/ujian/nilai. Penugasan Proktor hanya jika penanda ujian pusat kanonis disetujui owner; jangan tebak dari EXTERNAL/PUBLISHED.

### P4 — Kurikulum sampai laporan historis
Fondasi tersedia: TeacherCurriculumContextService, AssessmentCurriculumMappingService, QuestionVersionCurriculumMappingService, ExamSnapshotService. Gap terdokumentasi: CurriculumAchievementService dan CurriculumAnalyticsController masih memakai LearningOutcome/LearningObjective/LearningPath legacy; kesinambungan dengan curriculum_context_snapshot dan provenance/version belum terbukti. Buat canonical read-model/adapter yang menjaga histori; jangan menghapus tabel/record lama atau membuat CP kedua. Test end-to-end assignment sampai laporan historis; perubahan master tidak boleh mengubah snapshot lama.

### P5 — Acceptance, deployment, distribusi
Fresh install/upgrade disposable MySQL 8, seeder idempotent, rollback guard, backup/restore, security/IDOR, isolasi instalasi, concurrency, load, recovery, browser E2E tiga portal, UAT madrasah. Baru setelah seluruh gate dan persetujuan owner, rencanakan VPS/aaPanel. ZIP generik satu instalasi/database per madrasah dibuat setelah seluruh gate lulus dan harus diuji ekstraksi/README. Android/Web Super Admin tetap deferred Juni 2027.

## 7. Matriks status agar tidak salah klaim

| Area | Status | Batas klaim |
|---|---|---|
| PR #21 | OPEN/DRAFT/UNMERGED | Belum siap merge |
| Backend focused login/tenant | PASS pada dbebbd5 | Hanya focused smoke |
| Full PHPUnit | FAILURE pada dbebbd5 | Root cause/jumlah gagal belum diketahui dari log |
| Web Siswa static/Chromium/WebKit | PASS pada workflow Agent 4 | Bukan real Laravel E2E |
| Student Laravel selected suites | PASS | Hanya suite yang dijalankan |
| Web Guru | Selected historical CI pada SHA terdahulu | Refresh exact HEAD |
| Web Admin | Fondasi parsial | ADM-01–ADM-23 belum terbukti selesai |
| Kurikulum → laporan | Parsial | Legacy read-model belum tuntas |
| Lease/fencing | Source dan CI terpisah pernah dikerjakan | Belum production-ready tanpa integrasi multi-device/concurrency/recovery |
| Fresh install/upgrade | Sebagian disposable CI | Belum seluruh skenario |
| Backup/restore | Belum ada acceptance end-to-end | Belum lulus |
| UAT | Belum dilakukan | Belum lulus |
| VPS/ZIP | Belum dilakukan/dibuat | Tidak siap distribusi |
| Android/Super Admin | Deferred Juni 2027 | Jangan dikerjakan sekarang |

## 8. Protokol anti-pengulangan dan anti-liar
1. Satu backlog terurut; satu tahap aktif.
2. Sebelum patch: root cause, bukti pemanggil, file terdampak, risiko, regression test dan acceptance harus jelas.
3. Periksa branch/PR lain untuk mencegah duplikasi.
4. Backup byte-exact setiap file existing yang diubah; baca ulang dan bandingkan.
5. Patch kecil; satu commit source terarah. Hindari commit beruntun yang membatalkan CI concurrency.
6. Dokumentasi dapat memakai [skip ci] jika workflow mendukung; verifikasi hasilnya.
7. CI harus pada exact SHA. Catat PASS/FAIL/SKIPPED/NOT RUN secara tepat.
8. Bila log 404, tulis log tidak tersedia; jangan mengarang.
9. Update laporan/checkpoint/handover GitHub setiap tahap.
10. Jangan merge, production migrate, deploy, atau ubah DB produksi tanpa persetujuan eksplisit.
11. Jangan buat ZIP final sebelum seluruh acceptance gate lulus.

## 9. Kriteria selesai
Proyek hanya selesai bila fitur Admin/Guru/Siswa terbukti melalui source/policy/test/UAT; full PHPUnit dan E2E exact HEAD lulus; isolasi instalasi/database terbukti; Attempt/Answer/Score/token/lease/fencing/idempotency/recovery terbukti; kurikulum terintegrasi sampai laporan historis; backup/restore, fresh-install/upgrade, security/stability/load dan UAT lulus; ZIP diekstrak dan diverifikasi; owner menyetujui merge/deployment.

**Status saat handover: BELUM MEMENUHI KRITERIA SELESAI.**
