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
