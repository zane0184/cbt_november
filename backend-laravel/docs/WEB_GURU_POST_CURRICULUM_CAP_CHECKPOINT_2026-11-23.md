# CHECKPOINT WEB GURU — POST AUDIT KURIKULUM / MATERI / PROFIL
Tanggal: 23 November 2026

## 1. Posisi source
- Repository: `zane0184/cbt_november`
- Branch: `work/web-guru-remove-xlsx-20261123`
- Current HEAD: `0cbd6e3fa303c84f0700f37e57ad7b0c8fb514f1`
- PR #19: https://github.com/zane0184/cbt_november/pull/19
- PR: OPEN, DRAFT, UNMERGED.
- SHA source terakhir diuji: `91d8f6989c29c40ef836b244e0d0850bc22f4a99`.
- Compare SHA diuji → HEAD sekarang: hanya 2 dokumen ditambahkan (manifest backup dan hasil CI); tidak ada source/test/workflow berubah setelah pengujian.

## 2. AUDIT
### Kurikulum / materi / topik
- Teacher Portal Data Service menjaga tenant dan relasi assignment subject/rombel/phase/year/semester.
- Academic context fail-closed saat tahun/semester aktif ambigu atau semester tidak cocok dengan academic year.
- Curriculum context mengambil versi aktif yang diregistrasikan oleh tenant/System Tenant dan outcome aktif CP/TP/ATP/KBC.
- Materi legacy sudah ada pada `materials` dan model `Material`, dengan relasi curriculum/phase/subject/grade/element/CP/TP/ATP.
- Pencarian tree source aktif tidak menemukan model/migration Topic terpisah.
- QuestionVersion saat ini memiliki element_id, learning_outcome_id, learning_objective_id; belum ada year/semester/material/topic/MultiCurriculumVersion/MultiLearningOutcome.
- QuestionBank Web Guru masih memakai LearningOutcome/LearningObjective legacy.
- QuestionMultiCurriculumMapping mengikat question_id, bukan question_version_id.
- ExamSnapshotService hanya menyalin metadata difficulty/cognitive_level/question_type; snapshot belum terbukti membawa provenance kurikulum/materi/topik secara immutable.
- Profil Guru menampilkan nama, employee identifier, NIP, NIK, NUPTK, gender, phone; Guru hanya mengubah phone dengan transaction, row lock, audit. Status kepegawaian aktif/nonaktif tidak tersedia pada schema halaman.

### Monitoring role boundary
- Temuan sebelumnya tetap pending Agent 2: route Live Monitoring tidak memakai EnsureTeacherPortalAccess; ExamPolicy::monitor belum exactly-one role; GURU+KAMAD dapat melalui cabang KAMAD. Dicatat di Issue #8; Agent Web Guru tidak mengubah shared policy/service.

### DOCX
- DOCX UI draft telah disiapkan. Parser/UI belum dibuat; menunggu DTO/source locator/security/media/lifecycle contract final Agent 2 pada Issue #9.

## 3. DESAIN
- Jangan membuat CP/TP/ATP/Panca Cinta/PM/8 DPL tanpa registry resmi, sumber, versi, effective date dan provenance.
- Hubungkan assignment → curriculum registration/version → outcome yang tepat per subject/fase/rombel.
- Putuskan apakah Material legacy adalah master; putuskan Topic sebagai child atau entity terpisah.
- Mapping kurikulum harus version-scoped untuk QuestionVersion atau mapping immutable yang disetujui Agent 2.
- Exam snapshot harus membawa curriculum/outcome/material/topic/provenance immutable, bukan hanya source_question_version_id.
- Tidak menambah schema/service bersama sebelum Agent 2/owner menyetujui.

## 4. IMPLEMENTASI
- `TeacherCurriculumContextService`: memperbaiki batas tampilan CP/TP/ATP/KBC menjadi maksimum 100 secara global lintas semua versi terdaftar. Aggregate count tetap menghitung seluruh outcome aktif.
- `TeacherPortalFoundationTest`: menambah regression test dua versi × 60 CP; aggregate 120, detail tepat 100.
- Tidak ada migration, perubahan QuestionVersion, QuestionMultiCurriculumMapping, ExamSnapshotService, ScoringService atau DB produksi.
- Backup: dua source file awal 2/2 SHA MATCH; versi test sebelum koreksi sintaks 1/1 SHA MATCH. Total 3/3 snapshot MATCH.
- Manifest: `.github/backups/web-guru-curriculum-cap-20261123/`.

## 5. PENGUJIAN
- Run final: https://github.com/zane0184/cbt_november/actions/runs/45109872421
- Status: SUCCESS.
- Checkout SHA: `91d8f6989c29c40ef836b244e0d0850bc22f4a99`.
- PHPUnit: **118 passed, 520 assertions**, 16.32 sec.
- PHP lint, Laravel route registration, Composer validation/locked install/platform checks: PASS.
- Disposable MySQL 8 preflight: PASS.
- 80 base tables; all InnoDB.
- Disposable DB/user cleaned by workflow.
- Run awal test gagal karena namespace test double-escaped; snapshot test di-backup, sintaks diperbaiki, dan run final di atas PASS. Catatan lengkap di `WEB_GURU_CURRICULUM_DISPLAY_CAP_CI_RESULT_2026-11-23.md`.
- CI sebelumnya pada source unchanged: Monitoring suite 117/514 PASS.

## 6. KOORDINASI
- Issue #8 komentar Agent Web Guru terbaru: 7091266492 (kontrak kurikulum/material/snapshot) dan 7091321944 (hasil CI cap).
- Issue #9 komentar Agent Web Guru: 7091163328 (draft UI DOCX).
- Belum ada approval final Agent 2 untuk shared curriculum/snapshot contract atau DOCX details.

## 7. LANGKAH SELANJUTNYA
1. Tunggu/refresh keputusan Agent 2 pada Issue #8 dan #9; jangan menebak schema.
2. Setelah kontrak disetujui, implementasikan UI Guru untuk assignment-specific curriculum version, CP/TP/ATP, material/topic dan mapping versioned.
3. Minta Agent 2 mengimplementasikan/menyetujui QuestionVersion → snapshot provenance contract; Web Guru menambahkan UI/adapter dan tests.
4. Selesaikan browser E2E/visual QA desktop/mobile, revoke authorization tests, concurrency, UAT.
5. PR #19 tetap DRAFT. Tidak merge/deploy/migrate produksi tanpa owner approval.

## 8. STATUS
- XLSX question import removal: IMPLEMENTED + CI PASS.
- Selected Web Guru feature regression: CI PASS 118/520.
- Global outcome display cap: IMPLEMENTED + CI PASS.
- DOCX import: NOT IMPLEMENTED — awaiting Agent 2 final contract.
- Curriculum/material/topic/versioned snapshot integration: NOT COMPLETE — awaiting shared contract.
- Full Web Guru: **NOT COMPLETE / NOT PRODUCTION READY**.
