# Rekonsiliasi Audit dan Kelanjutan Portal Web Guru
Tanggal: 23 November 2026
Repository: `zane0184/cbt_november`
Lingkup: Web Guru saja.

## AUDIT

### Source dan PR aktif
- Branch implementasi Web Guru: `work/web-guru-remove-xlsx-20261123`.
- HEAD PR #19 yang diverifikasi: `bc4fc666f2a5793e078351fda478d100fba4c870`.
- PR #19: OPEN, DRAFT, UNMERGED; base `work/web-guru-phase1-audit-implementation-20261122`.
- PR #17 (rancangan lima kontrak): OPEN, DRAFT, dokumentasi-only.
- Master spesifikasi: `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md` pada `main`; ini sumber cakupan yang otoritatif.
- Checkpoint terdahulu menyebut HEAD `0cbd6e3...`; nilai itu tidak cocok dengan HEAD PR #19 saat audit. Nilai source kebenaran saat audit ini adalah `bc4fc666...`.

### Kepastian P0 yang telah masuk PR #19
Perbandingan source dan pemeriksaan file pada HEAD PR #19 membuktikan perbaikan P0 dari branch pendahulu telah terbawa:
- `ExamAttemptScore::scopeCurrent()` memeriksa current pointer, attempt_id, tenant_id, current_score_id, dan scoring_version.
- Query Exam/Student/Rombel/Subject Analytics, Item Analysis, Exam Summary dan Question Report menggunakan current score aktif.
- Item/question scores dicocokkan terhadap attempt dan scoring_version aktif.
- `TeacherResultComponent::booted()` memeriksa ulang identitas/role/tenant/policy pada request Livewire hasil dan analitik.
- `ReportPolicy` dan `ExamAttemptPolicy` menolak multi-role GURU pada jalur Guru; role KAMAD legacy masih memiliki cabang terpisah.
- Regression tests `CurrentScoreAnalyticsTest` dan `TeacherResultAccessTest` tersedia pada branch PR #19.
Catatan: ini verifikasi source/branch, bukan pernyataan bahwa seluruh suite di HEAD PR terbaru sudah dijalankan ulang. CI run 45109872421 menjalankan SHA 91d8f698...; compare SHA itu ke HEAD PR hanya menambahkan dokumen/manifest, tanpa source/test/workflow. Hasil yang dapat diklaim untuk run tersebut adalah 118 tests / 520 assertions pada SHA 91d8f698....

### Status fitur berdasarkan source PR #19
- Dashboard/Penugasan: fondasi tersedia; agenda/aktivitas/notifikasi belum lengkap.
- Pembelajaran: penugasan dan versi kurikulum tenant-registered ditampilkan; belum mengikat curriculum registration/version spesifik pada setiap assignment, mapel, fase, rombel.
- CP/TP/ATP/KBC: referensi yang tersimpan dapat ditampilkan; daftar CP/TP/ATP/KBC dibatasi 100 secara global dan agregat tetap menghitung semua. Provenance CP/Panca Cinta belum utuh; Pembelajaran Mendalam dan 8 DPL belum tersedia pada registry.
- Materi: tabel/model legacy `materials` tersedia; source tree tidak menunjukkan Topic terpisah. Belum ada keputusan apakah Topic anak Material atau entity terpisah.
- Bank Soal: authoring dasar, versioning, media DRAFT upload/attach/view/detach tersedia; pemetaan akademik masih legacy CP/TP dan belum mencakup seluruh field kurikulum.
- Import: jalur question XLSX UI/route/wizard/parser/template khusus sudah dihapus; API row-based JSON dipertahankan untuk pemanggil lain. DOCX belum diimplementasikan.
- Asesmen/Ujian: builder dan token otomatis saat publish tersedia, dengan suite CI terpilih. Jadwal serentak menjadi kontrak produk.
- Hasil/Penilaian/Analitik: halaman, grading ESSAY, finalisasi/bulk-finalize/publikasi dan analytics tersedia pada source; CI coverage terpilih lulus pada SHA yang tercantum di tiap laporan.
- Monitoring: API/halaman per-Exam dan regression suite Guru/Proktor sementara tersedia. Sidebar belum menjadi menu langsung; halaman monitoring memakai Exam policy dan route web tersendiri. Pastikan pembatasan exactly-one role lintas policy dan route konsisten.
- Proktor: assignment sementara saat ini menggunakan model `ExamSession`/`ExamSessionRoom` dalam service dan data model; ini bertentangan dengan Master Web Guru yang melarang ExamSession sebagai lifecycle operasional/admission. Jangan memperluas UI Proktor di atas kontrak ini sebelum desain Admin/Agent 2 diselaraskan.
- Profil: identitas read-only, nomor kontak dapat diperbarui lewat transaksi, row lock dan audit. Status kepegawaian aktif/nonaktif tidak ada pada schema UI yang diperiksa.
- Pengaturan: belum ada; sidebar masih placeholder.
- RTL/Arab/harakat/persamaan: belum ada bukti kontrol/editor/preview parity Web yang memadai.

### Kontrak Agent 2 yang belum disetujui final
- Issue #8: assignment → curriculum registration/version → CP/TP/ATP/KBC; materi/topik; versioned question mapping; immutable snapshot provenance; registry PM/8 DPL/Panca Cinta.
- Issue #9: Agent 2 mengusulkan DOCX adapter ke satu QuestionDraftDTO dan ImportBatch/ImportBatchRow. Proposal arah arsitektur ini belum menjadi approval field/enum/endpoint/error/warning/source locator/security/resource limits/retention/idempotency/media/failure semantics.
- Issue #14: versioned CP/TP/ATP/KBC mapping harus dipastikan sampai snapshot immutable dan sejarah.
Tidak boleh menambah shared migration/model/service, mengubah ExamSnapshotService atau ScoringService berdasarkan asumsi Web Guru.

### Ketidaksesuaian lintas-agent
Issue #10 berisi desain lama satu Exam–ExamSession operasional. Itu bertentangan dengan spesifikasi Master Web Guru dan keputusan Owner 23 November mengenai ujian serentak tanpa ExamSession/SessionRoom sebagai admission/lifecycle operasional. Jangan mengikuti Issue #10 bagian yang konflik.
PR #18 Admin sedang membangun room assignment dengan konsep session/room; kontrak room harus diselaraskan dahulu terhadap Master Web Guru sebelum mengintegrasikan menu Proktor Guru.

## DESAIN / URUTAN KERJA

### P0 — jaga integritas dan otorisasi
1. Pertahankan current-score pointer dan regression suite P0 yang telah terbawa ke PR #19. Jangan merombak ulang tanpa test pembanding.
2. Audit exact role untuk monitoring page/API dan hilangkan cabang KAMAD dari authorization aktif sesuai kebijakan role lokal terbaru (KAMAD bukan role akun). Ini ownership Agent 2 untuk policy/scope bersama.
3. Tambahkan regression pada monitoring: Guru penulis, Guru proktor assignment aktif, assignment revoke/expired, role berubah, direct URL, semua endpoint, tenant instalasi.
4. Rekonsiliasi assignment Room Proktor dengan model ujian serentak tanpa ExamSession/SessionRoom lifecycle operasional. Agent 2/Admin harus menetapkan kontrak Exam+Room, lock ordering dan data placement terlebih dahulu.

### P1 — kurikulum dan authoring
1. Tunggu keputusan tertulis Agent 2 pada Issue #8/#14 untuk registry sumber resmi, assignment curriculum version, Material/Topic, question-version mapping dan snapshot provenance.
2. Setelah kontrak final, implementasikan UI Pembelajaran per assignment dan form Bank Soal yang menampilkan/memvalidasi hanya referensi sesuai assignment, subject, phase/grade dan tahun akademik.
3. Snapshot harus menyimpan provenance immutable yang disepakati; uji perubahan kurikulum/materi setelah ujian terbit tidak mengubah histori.
4. Jangan menamai Panca Cinta/PM/8 DPL sebagai "resmi terverifikasi" sebelum registry dan bukti sumber disetujui.

### P1 — DOCX import
1. Minta Agent 2 menandai APPROVED/REJECTED/NEEDS DESIGN untuk DTO final, endpoint lifecycle, source locator, error/warning schema, parser profile, ZIP/XML hardening, limits, embedded media staging, cleanup, idempotency, duplicate/retry dan audit.
2. Setelah kontrak disetujui, implementasikan UI upload → preview/source locator → koreksi → revalidate → confirm → ringkasan. Semua hasil harus DRAFT; tidak auto-publish.
3. Jangan menambah dependency DOCX sebelum lisensi, PHP 8.3/Laravel 13 compatibility dan keamanan ditinjau.
4. SHORT_ANSWER tetap BLOCKED; ESSAY tetap manual review.

### P2 — kelengkapan portal
- Monitoring sebagai Guru pembuat dan Proktor, berdasarkan capability server-side.
- Pengaturan Guru: password (melalui mekanisme auth yang aman), preferensi, notifikasi dan panduan sesuai scope.
- Dashboard agenda/aktivitas/peringatan berbasis data aktual.
- Penjadwalan pembelajaran hanya setelah model sumber resmi tersedia.
- RTL Arab/harakat, mixed bidi dan formula preview untuk Web.
- Browser E2E, visual QA desktop/mobile, MySQL concurrency/load dan UAT madrasah.

## IMPLEMENTASI
Dalam rekonsiliasi audit ini:
- Tidak ada source aplikasi, test, workflow, migration atau database yang diubah.
- Tidak ada merge, deployment atau perubahan PR.
- Tidak ada backup baru karena tidak ada file source yang dimodifikasi.
- Dokumen ini merupakan dokumentasi koordinasi baru.

## PENGUJIAN / BUKTI
- PR #19 masih OPEN/DRAFT/UNMERGED.
- Run CI #45109872421 SUCCESS pada checkout SHA `91d8f6989c29c40ef836b244e0d0850bc22f4a99`: 118 passed, 520 assertions; PHP lint, route registration, Composer, disposable MySQL 8 dan 80 tabel InnoDB lulus. Compare ke HEAD PR #19 menunjukkan tiga commit dokumentasi/manifest saja setelah SHA uji.
- Run sebelumnya: #45108770776 SUCCESS 117/514; #45108402325 SUCCESS 97/443; #45108038746 SUCCESS 90/426; setiap angka terkait SHA tertentu dalam dokumen hasilnya.
- Belum ada bukti browser E2E/visual QA menyeluruh, load/concurrency end-to-end, UAT madrasah atau deployment produksi.
- Status Web Guru: **BELUM LENGKAP / BELUM SIAP PRODUKSI**.

## LINK KOORDINASI
- Master spesifikasi: https://github.com/zane0184/cbt_november/blob/main/docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md
- Audit kurikulum/materi/snapshot/profil: https://github.com/zane0184/cbt_november/blob/work/web-guru-remove-xlsx-20261123/backend-laravel/docs/WEB_GURU_CURRICULUM_MATERIAL_PROFILE_AUDIT_2026-11-23.md
- Rancangan lima kontrak: https://github.com/zane0184/cbt_november/blob/work/web-guru-five-contracts-20261123/backend-laravel/docs/WEB_GURU_FIVE_CONTRACTS_AUDIT_DESIGN_2026-11-23.md
- Issue #8: https://github.com/zane0184/cbt_november/issues/8
- Issue #9: https://github.com/zane0184/cbt_november/issues/9
- Issue #14: https://github.com/zane0184/cbt_november/issues/14
- PR #17: https://github.com/zane0184/cbt_november/pull/17
- PR #19: https://github.com/zane0184/cbt_november/pull/19
