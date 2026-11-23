# CHECKPOINT — SOLO WEB CBT COMPLETION
Tanggal: 23 November 2026 (WIB)
Repository: `zane0184/cbt_november`
Branch pelaksana tunggal: `work/solo-web-cbt-completion-20261123`
Baseline: `work/web-admin-implementation-20261123`
PR lama #18/#19/#13 tetap OPEN/DRAFT/UNMERGED dan diperlakukan sebagai sumber audit, bukan jalur eksekusi terpisah.

## AUDIT

### Keputusan pemilik yang berlaku
- Fokus implementasi: WEB CBT ONLY — Backend Laravel, Web Admin Madrasah, Web Guru, Web Siswa.
- Satu agent/pemilik implementasi menangani audit, desain, coding, integrasi, dokumentasi, dan pengujian seluruh komponen.
- Portal lokal: ADMIN_MADRASAH, GURU, SISWA. Operator/Kamad/Proktor permanen tidak digunakan.
- Instalasi dan database akademik berdiri sendiri per madrasah.
- Taksonomi asesmen mengikuti Kurikulum Merdeka + KBC. UTS/UAS/UM/SIMULASI TKA dan label serupa bukan enum/kategori baku; nomenklatur asesmen dipisah dari cakupan, nama kegiatan, instrumen, dan mode CBT.
- CP induk terlindungi; Guru mengembangkan TP/ATP. Admin tidak mengambil alih authoring Guru.
- Tidak ada merge, migration produksi, deployment, atau distribusi final tanpa gate dan persetujuan eksplisit owner.

### Temuan source aktual
1. Dokumen arsitektur pada baseline branch masih memuat kategori tetap UTS/UAS/UM/SIMULASI TKA dan pembagian tanggung jawab Agent 2/3/4. Keduanya bertentangan dengan keputusan owner terbaru.
2. `Admin/CurriculumManagement.php` memakai `LearningOutcome` legacy, memuat role ADMIN/OPERATOR, dan menyediakan pembuatan TP oleh Admin. Ini tidak sesuai keputusan bahwa CP induk dilindungi dan TP/ATP dikembangkan Guru.
3. `QuestionVersion` hanya mengikat element, LearningOutcome legacy, dan LearningObjective legacy. Tidak ada relasi eksplisit tahun ajaran/semester/materi/topik/versi kurikulum/MultiLearningOutcome pada version soal.
4. `QuestionMultiCurriculumMapping` mengikat `question_id`, bukan `question_version_id`.
5. `ExamSnapshotService` menyalin metadata soal dasar saja; provenance kurikulum/CP/TP/ATP/materi/topik belum dibawa secara immutable ke snapshot.
6. `TeacherCurriculumContextService` menampilkan Panca Cinta sebagai literal dan secara jujur menandai provenance CP, Pembelajaran Mendalam, serta delapan dimensi belum tersedia/terverifikasi.
7. Materi legacy tersedia. Audit Web Guru menyatakan entitas Topic terpisah belum ditemukan. Jangan membuat asumsi hubungan Topic sebelum desain kontrak ditetapkan.
8. Web Siswa memiliki client IndexedDB/recovery dan runner test, tetapi CI #291 pada exact HEAD hanya static/browser runner; bukan bukti browser-to-real-Laravel curriculum E2E.
9. PR #18 HEAD `42a0de2c1f86b8e76e34e1b74ab0feaca90def3a`: tidak ditemukan workflow run pada exact SHA. PR #19 HEAD `71296ca6e7c48b7295455a26d38bda11b901eb1d`: tidak ditemukan workflow run pada exact SHA. PR #13 HEAD `9ef375c407b1faf606c5a268f00b24424d93be9e`: run #291 SUCCESS tetapi hanya static/browser runner.
10. Tidak ada PR lama yang di-merge. Tidak ada migration produksi atau deployment dilakukan.

## DESAIN
- Satu kontrak backend kanonis menjadi sumber kebenaran untuk Admin/Guru/Siswa.
- Registry kurikulum dan referensi resmi menyimpan provenance, versi, tanggal berlaku, status, dan histori.
- Assignment Guru menentukan konteks akademik yang sah (tahun/semester, mapel, kelas/rombel, fase, versi kurikulum).
- CP induk bersifat read-only; TP/ATP adalah authoring Guru yang versioned dan scoped pada assignment.
- Panca Cinta, Pembelajaran Mendalam, dan 8 dimensi profil lulusan adalah registry terpisah dengan sumber resmi, bukan literal hard-coded.
- Materi/topic dan mapping soal harus version-scoped pada QuestionVersion.
- Exam snapshot menyalin label, ID, versi, dan provenance akademik secara immutable.
- Payload Siswa hanya berisi data yang diotorisasi dan telah di-redact server-side.
- DOCX menjadi format utama import soal Guru; XLSX question import tidak dihidupkan kembali. XLSX/CSV administratif tetap boleh untuk data Guru/Siswa/Mapel.

## IMPLEMENTASI
- Branch tunggal dibuat dari `work/web-admin-implementation-20261123`.
- Dokumen arsitektur diperbarui untuk menghapus kategori tetap dan ownership multi-agent, serta menetapkan Web-only single-owner workflow.
- Backup byte-exact dokumen arsitektur dibuat pada:
  `docs/backup/KEPUTUSAN_ARSITEKTUR_INSTALASI_MANDIRI_DAN_ROLE_2026-11-23.md.before-solo-web-owner-taxonomy`
- Backup diverifikasi identik dengan source sebelum patch.
- Backup commit: `2c97847779042c704f8822310cc61d41872278bd`.
- Update commit: `11425ac105c6dee761f9268e77cc8fad02436f84`.
- Source baru diverifikasi memuat Web-only, single-owner, dan taxonomy Kurmer+KBC.
- Belum ada source aplikasi, schema, migration, atau database yang diubah pada checkpoint ini.

## PENGUJIAN
- Backup arsitektur: byte-exact content equality PASS.
- Source arsitektur setelah patch: fetch ulang PASS.
- CI aplikasi pada branch tunggal: NOT RUN.
- PHPUnit / MySQL 8 / browser-to-Laravel E2E: NOT RUN.
- Production migration/deployment: NOT RUN (dilarang tanpa approval dan preflight).

## LANGKAH IMPLEMENTASI BERIKUTNYA
1. Finalisasi desain kontrak backend kurikulum/asesmen, relasi assignment, QuestionVersion, snapshot, dan redaction.
2. Tambahkan migration additive dan model/service/API dengan backup byte-exact, tanpa menghapus kolom/relasi legacy.
3. Tambahkan tests disposable MySQL 8 untuk provenance, versioning, role/scope, snapshot immutability, dan redaction.
4. Integrasikan Web Admin, Web Guru, dan Web Siswa ke kontrak yang sama.
5. Jalankan CI pada exact HEAD dan browser E2E nyata yang terhubung Laravel.
6. Lengkapi ADM-01 s.d. ADM-23, DOCX import, administrasi import/export, backup/restore, audit, dan health checks.
7. Tidak merge/deploy/migrate produksi/distribusi final tanpa persetujuan eksplisit owner.


---

## UPDATE EKSEKUSI BACKEND — 23 NOVEMBER 2026

HEAD yang diperiksa setelah perubahan: `a750cfc5e530b5d5c9daba133aebb1560a38fa0a`.

### AUDIT

- Branch kerja tunggal masih tersedia. Perbandingan terhadap `work/web-admin-implementation-20261123` menunjukkan branch kerja ahead 310 commit; rentang ini terlalu besar untuk diperlakukan sebagai perubahan kecil dan perlu ditinjau sebelum PR/merge.
- `fetch_commit_workflow_runs` pada HEAD tidak mengembalikan run PR; combined commit status juga kosong. Karena wrapper workflow hanya mengambil run pemicu pull_request, status CI keseluruhan belum dapat dipastikan dari hasil ini. CI exact HEAD dinyatakan BELUM TERVERIFIKASI.
- Migration Fase 1C yang sudah ada menyediakan registrasi owner kurikulum dan unique key komposit yang dipakai kontrak baru. CP resmi tetap berada di `multi_learning_outcomes`; authoring TP/ATP dipisah ke tabel tenant-local.
- Artefak CP MI lengkap, checksum PDF sumber, dan isi resmi lengkap untuk seluruh mapel/elemen belum diimpor. Tidak boleh mengisi CP dengan data sintetis.

### DESAIN

- CP resmi diperlakukan sebagai referensi read-only; TP/ATP Guru berada di `tenant_learning_outcomes` dan terkait assignment, tahun/semester, registrasi kurikulum, versi, owner, dan CP induk.
- Mapping CP ke QuestionVersion dan mapping TP/ATP ke QuestionVersion dipisahkan agar CP global tidak tertukar dengan authoring lokal.
- Mapping materi/topik, fase/struktur, owner, tenant, dan registrasi kurikulum menggunakan foreign key komposit yang merujuk parent key Fase 1C.
- Taksonomi asesmen memisahkan FUNCTION, SCOPE, INSTRUMENT, dan DELIVERY_MODE; nama kegiatan tetap bebas. Asesmen eksternal dipisahkan melalui `assessment_origin` dan `external_program_name`.
- Admin hanya boleh memiliki role tunggal ADMIN_MADRASAH pada komponen yang sudah disentuh; penugasan pengawas sementara hanya untuk akun Guru tunggal. Guard ini belum mewakili audit seluruh route, API, export, Livewire, dan direct request.

### IMPLEMENTASI

Perubahan source yang telah ditulis pada branch ini:
- Migration additive `2026_11_23_000001_add_canonical_web_curriculum_contract.php` untuk provenance sumber, registry taksonomi, topik materi, mapping subjek-kurikulum, TP/ATP tenant-owned, mapping QuestionVersion, konteks asesmen, dan snapshot.
- Model baru: `TenantCurriculumSubjectMapping`, `TenantLearningOutcome`, `LearningOutcomeStructureMapping`, `QuestionVersionTenantOutcomeMapping`.
- Perluasan model Assessment, TeacherSubjectAssignment, QuestionVersion, MultiCurriculumVersion, MultiCurriculumStructure, MultiLearningOutcome, QuestionVersionCurriculumMapping, QuestionVersionStructureMapping, AssessmentTaxonomyOption.
- Seeder registry kurikulum dan taksonomi asesmen didaftarkan pada DatabaseSeeder dan memakai SystemTenantSeeder.
- Role guard diperketat pada AcademicManagement, StudentRombelManagement, UserManagement, ProctorAssignmentManagement.
- Backup file sebelum perubahan disimpan di `backend-laravel/docs/backup/solo-web-cbt-2026-11-23/`; backup checkpoint ini disimpan di `docs/backup/CHECKPOINT_SOLO_WEB_CBT_2026-11-23.md.before-backend-foundation-update`.
- Commit backup checkpoint: `9b40c057dcf133b917cdbf4ed198be31a6ac712a`.
- Catatan: beberapa file backup dibuat per iterasi karena file yang sama mengalami perubahan bertahap. Source asli di branch tidak dihapus.

### PENGUJIAN

- Fetch ulang source/backup melalui GitHub API: dilakukan untuk file yang disentuh.
- PHP lint, PHPUnit, fresh install, migration, seeder, MySQL 8 disposable, browser E2E, security/IDOR, multi-tenant, concurrency, dan redaction: BELUM DIJALANKAN pada HEAD ini.
- Tidak ada migration produksi, deployment, merge, atau distribusi ZIP.
- Status keseluruhan: FONDASI SOURCE DITULIS; BELUM TERUJI; BELUM SIAP PRODUKSI.

### BLOCKER DAN LANGKAH BERIKUTNYA

1. Jalankan CI/PHPUnit pada exact HEAD dan perbaiki seluruh kegagalan sebelum melanjutkan integrasi UI.
2. Audit migration secara menyeluruh terhadap seluruh parent schema, constraint MySQL 8, urutan migrasi fresh install/upgrade, rollback/recovery, dan data lama. Jangan menjalankan pada DB produksi.
3. Uji seeder pada fresh install dan pastikan sumber dengan status PENDING tidak ditampilkan sebagai konten resmi terverifikasi.
4. Implementasikan layanan/API validasi untuk TP/ATP, mapping materi/topik, KBC/Panca Cinta, Pembelajaran Mendalam, delapan dimensi, QuestionVersion, snapshot, serta redaction Siswa.
5. Integrasikan UI Web Admin, Guru, dan Siswa ke kontrak yang sama; lanjutkan ADM-01 s.d. ADM-23.
6. Audit seluruh route, Livewire, API, export/download, policy dan akses langsung. Guard parsial yang sudah ditambahkan tidak cukup untuk menutup W1.
7. Buat PR hanya setelah rentang branch dan changed files ditinjau; PR tetap Draft sampai semua gate lulus.


## FOLLOW-UP IMPLEMENTASI DAN AUDIT — 23 NOVEMBER 2026

HEAD saat checkpoint follow-up: `121addf5a4cb1814b71e5dd1abc372a699f35eb9`.

### AUDIT
- Review service TP/ATP menemukan field assignment ikut dikirim ke model mapping, padahal tidak ada di schema mapping. Payload mapping telah diselaraskan dengan kolom migration yang sebenarnya.
- UI Guru sudah menampilkan empat unsur olah Pembelajaran Mendalam, tetapi form authoring TP sebelumnya belum memetakannya. Ini telah diperbaiki agar empat unsur olah dapat dipilih dan divalidasi server-side.
- Seeder belum mengimpor konten CP. Halaman Guru tetap mengunci pembuatan TP/ATP sampai CP per mapel/fase memiliki artefak resmi dan checksum terverifikasi.
- Registry metadata fase, kelas, mapel, Panca Cinta, prinsip PM, empat unsur olah, kerangka PM, dan delapan dimensi tersedia sebagai metadata awal. Ini tidak membuktikan CP per elemen atau rincian seluruh mata pelajaran sudah lengkap.

### IMPLEMENTASI
- `TeacherLearningOutcomeService`, komponen Livewire Guru, dan view Guru diperbarui agar pemetaan TP mencakup Panca Cinta, prinsip PM, unsur olah pikir/hati/rasa/raga, kerangka PM, dan dimensi profil lulusan.
- Field yang tidak ada pada tabel mapping dihapus dari payload create mapping.
- `CurriculumReferenceContractTest` ditambah pemeriksaan tabel TP/ATP tenant-owned, mapping QuestionVersion, kolom assignment, dan jumlah node registry (5 Panca Cinta, 4 prinsip PM, 4 unsur olah, 4 kerangka PM, 8 dimensi).
- Backup tambahan tersedia pada `backend-laravel/docs/backup/solo-web-cbt-2026-11-23/`; backup checkpoint follow-up pada `docs/backup/CHECKPOINT_SOLO_WEB_CBT_2026-11-23.md.before-pm-olah-followup`.
- Backup checkpoint commit: `2383445032837363154c7ac47698f299ae045c54`.

### PENGUJIAN
- Fetch ulang file service, Livewire, Blade, migration, model, seeder, dan test melalui GitHub API: dilakukan.
- Exact HEAD `121addf5a4cb1814b71e5dd1abc372a699f35eb9`: wrapper workflow PR tidak mengembalikan run dan combined status kosong. CI tetap BELUM TERVERIFIKASI; tidak boleh ditafsirkan sebagai PASS.
- PHP lint, PHPUnit, MySQL 8 migration/seeder integration, browser E2E dan UAT: BELUM DIJALANKAN.

### STATUS
Fondasi metadata dan authoring TP/ATP diperluas; belum diuji. Konten CP penuh, mapping materi/topik pada UI Guru, integrasi snapshot/report/Siswa, dan audit menyeluruh Admin masih terbuka.


---

# ADDENDUM CHECKPOINT TERBARU — 23 NOVEMBER 2026 (PENGGANTI CHECKPOINT LAMA)

Gunakan dokumen terbaru [HANDOVER_AGENT_BARU_PHPUNIT_2026-11-23.md](HANDOVER_AGENT_BARU_PHPUNIT_2026-11-23.md) sebagai entry point agent pengganti. Bagian lama di atas tetap menjadi riwayat desain dan implementasi; informasi branch/angka CI yang lama tidak lagi menjadi status terkini.

- Repository: zane0184/cbt_november.
- Branch aktif: work/solo-web-cbt-completion-20261123-execution.
- Source SHA full PHPUnit terakhir: b31d7a2713383ddcc1282317cdb295d6992d5dce.
- HEAD dokumen setelah update laporan: lihat ref branch aktual; commit dokumentasi/backup setelah source SHA di atas tidak mengubah source code yang diuji.
- CI terakhir: [#45168433247](https://github.com/zane0184/cbt_november/actions/runs/45168433247), MySQL 8.0.46, focused login smoke 27 passed/90 assertions, full PHPUnit **92 failed / 426 passed / 1,723 assertions**.
- Baseline full PHPUnit [#45156925960](https://github.com/zane0184/cbt_november/actions/runs/45156925960): 160 failed / 357 passed / 1,475 assertions. Perbaikan mengurangi 68 kegagalan; belum PASS.
- Laporan triage terbaru: REPORT_P0_TRIAGE_FULL_PHPUNIT_2026-11-23.md.
- Matriks ADM terbaru: MATRIKS_ADM_01_23_AUDIT_2026-11-23.md. Semua ADM-01–ADM-23 masih NOT VERIFIED.
- Prioritas langsung: regresi ExamListContractTest ($assignment undefined), 15 ExamSnapshotTest karena assignment kanonis, cleanup FK ExamVerificationTest, otorisasi/fixture Phase7CReopen dan scoring, stale Livewire tests Admin/Guru, token/lease/fencing, Proctor fail-closed.
- Source/backup sudah ada di branch ini. Jangan ulang patch. Verifikasi source dan CI exact SHA sebelum mengubah apa pun.
- Tidak ada merge, production migration, deployment, backup/restore, UAT, atau ZIP distribusi.

Status: **FULL PHPUNIT FAIL — WORK IN PROGRESS — NOT PRODUCTION READY**.
