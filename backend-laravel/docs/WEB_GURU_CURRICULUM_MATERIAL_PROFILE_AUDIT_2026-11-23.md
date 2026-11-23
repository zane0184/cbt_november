# AUDIT LANJUTAN KURIKULUM, MATERI/TOPIK, SNAPSHOT, DAN PROFIL GURU
Tanggal: 23 November 2026
Repository: `zane0184/cbt_november`
Branch audit: `work/web-guru-remove-xlsx-20261123`
HEAD referensi saat audit: `97993040048231a302d66d40430368db85a8a897`
Scope: Web Guru; pemeriksaan source/model/migration/service/view/test. Tidak mengubah shared schema/service.

## 1. AUDIT — temuan berbasis source

### 1.1 Konteks kurikulum pada halaman Pembelajaran
File:
- `TeacherPortalDataService.php`
- `TeacherCurriculumContextService.php`
- `TeacherLearning.php`
- `portal-learning.blade.php`

Yang sudah dilakukan:
- Halaman mengambil penugasan Guru dengan tenant filter dan memvalidasi konsistensi subject, rombel, grade/phase, academic year, semester.
- Periode aktif hanya ditampilkan bila tepat satu tahun pelajaran dan satu semester aktif serta semester cocok dengan academic year. Kondisi ambigu tidak ditebak; status ditampilkan perlu ditinjau Admin.
- Referensi kurikulum yang ditampilkan berasal dari `TenantCurriculumRegistration` aktif, dengan owner dibatasi ke tenant madrasah atau System Tenant; versi dan kurikulum harus ACTIVE dan owner konsisten.
- CP/TP/ATP/KBC_CINTA dihitung dari `MultiLearningOutcome` yang terikat ke versi dan struktur aktif.
- UI secara jujur menampilkan provenance CP belum diverifikasi, Pembelajaran Mendalam belum terintegrasi, dan delapan dimensi profil lulusan belum tersedia.

Kesenjangan:
- `TeacherCurriculumContextService::forTenant()` mengumpulkan seluruh versi kurikulum terdaftar di tenant, bukan konteks kurikulum yang spesifik untuk setiap assignment/mapel/fase/rombel.
- Pemetaan rinci Panca Cinta belum dapat dibuktikan hanya dari daftar literal lima unsur; struktur baseline memiliki node `DIMENSI_CINTA` dan `PETA_KASIH`, tetapi belum ada kontrak source/provenance yang membuktikan mapping ke lima unsur.
- Belum ada referensi Pembelajaran Mendalam atau delapan dimensi pada service ini; status false dipertahankan dan UI tidak mengarang data.

### 1.2 Materi/topik dan QuestionVersion
Temuan schema/model:
- Tabel/model `materials` memang sudah ada pada migration `2024_01_01_000005_create_curriculum_details_tables.php` dan model `Material`.
- Material legacy terhubung ke tenant, curriculum, phase, subject, grade_level (nullable), element (nullable), learning_outcome (nullable), learning_objective (nullable), learning_path (nullable), name, description, status.
- Pencarian pohon source aktif untuk model/migration terkait tidak menemukan model/tabel Topic terpisah. Ini perlu dikonfirmasi oleh pemilik kontrak akademik sebelum membuat konsep Topic baru.
- `QuestionVersion` fillable saat ini hanya memiliki `element_id`, `learning_outcome_id`, dan `learning_objective_id` sebagai referensi akademik. Tidak ada academic_year_id, semester_id, material_id, topic_id, multi_curriculum_version_id, atau multi_learning_outcome_id.
- Editor `QuestionBank` Web Guru menggunakan `LearningOutcome` dan `LearningObjective` legacy untuk CP/TP; ia belum memilih versi kurikulum MultiCurriculum, materi, atau topik.
- `QuestionMultiCurriculumMapping` menghubungkan `question_id` ke `multi_learning_outcome_id` dan `multi_curriculum_version_id`, bukan ke `question_version_id`. Karena identitas mapping melekat ke Question, mapping versi dapat berubah mengikuti versi soal berikutnya kecuali dibuat snapshot/versi mapping terpisah.

### 1.3 Exam snapshot
- `ExamQuestionSnapshot` menyimpan source_question_id, source_question_version_id, snapshot stem/explanation/options/media, points dan metadata JSON.
- `ExamSnapshotService` menyalin metadata hanya `difficulty`, `cognitive_level`, dan `question_type`.
- Snapshot belum terbukti menyalin identitas versi kurikulum, outcome/CP/TP/ATP, materi/topik, grade/fase atau provenance sumber resmi.
- Karena `source_question_version_id` tetap tersimpan, audit trail sumber ada, tetapi snapshot mandiri belum membawa seluruh label/konteks akademik yang harus tetap konsisten setelah referensi kurikulum diperbarui.
- `ExamSnapshotService` dan schema snapshot adalah ownership Agent 2; Agent Web Guru tidak mengubahnya.

### 1.4 Profil Guru
File:
- `TeacherProfile.php`
- `TeacherProfilePage.php`
- `portal-profile.blade.php`

Yang sudah tersedia:
- Identitas nama, employee identifier, NIP, NIK, NUPTK, gender dan nomor telepon.
- UI menampilkan identitas dan tenant; nomor telepon saja yang dapat diubah Guru.
- Simpan nomor telepon memakai transaksi, row lock, validasi tenant/user/profile, dan AuditService.
- Email dan identitas kepegawaian read-only; sesuai batas bahwa data inti dikelola Admin sampai tersedia proses verifikasi perubahan.

Batas:
- Skema yang dipakai halaman belum memiliki status kepegawaian aktif/nonaktif tersendiri. Label “akun terautentikasi” tidak boleh ditafsirkan sebagai status kepegawaian.
- Tidak ada perubahan profil yang dilakukan pada tahap ini.

### 1.5 Temuan UI limit
- View `portal-learning.blade.php` menyatakan maksimal 100 entri outcome.
- Service sebelumnya menerapkan `limit(100)` di dalam loop setiap versi terdaftar. Akibatnya, dengan dua versi masing-masing dapat menampilkan 100 baris (hingga 200 total), bertentangan dengan keterangan UI.
- Koreksi kecil Web Guru dilakukan: sisa slot dihitung dari total baris yang telah dikumpulkan sehingga maksimal 100 secara global; agregat counts tetap menghitung seluruh outcome aktif.

## 2. DESAIN — kontrak yang diperlukan sebelum integrasi penuh

### 2.1 Konteks assignment
Untuk setiap assignment aktif, kontrak read-only Guru sebaiknya menyediakan:
- tenant madrasah dan teacher assignment id;
- subject, rombel, grade level, phase;
- academic year dan semester yang berasal dari assignment, bukan hanya satu global active period;
- curriculum registration/version yang sah untuk konteks assignment;
- status/provenance referensi dan tanggal efektif.
Jika lebih dari satu versi berlaku, tampilkan status konflik/perlu keputusan Admin; jangan memilih diam-diam.

### 2.2 Relasi materi/topik
Agent 2/owner akademik perlu menetapkan apakah:
- `materials` legacy akan dipertahankan sebagai master materi;
- konsep Topic merupakan child dari Material atau entitas terpisah;
- relasi harus mengikat subject/phase/grade/curriculum version dan tenant;
- perubahan materi/topik memerlukan versioning/history;
- QuestionVersion menyimpan relasi langsung ke versi material/topic atau mapping immutable yang lain.
Jangan menambah tabel Topic atau migration dari Agent Web Guru sebelum kontrak disepakati.

### 2.3 QuestionVersion → snapshot
Kontrak perlu menentukan cara menyimpan:
- curriculum id + version code/title + effective year + owner/provenance;
- outcome code/type/description dan versi CP/TP/ATP/KBC;
- material/topic identity + version/label;
- grade/phase/subject/academic year/semester yang relevan.
Snapshot harus immutable dan membawa data yang dibutuhkan untuk laporan historis; tidak cukup hanya mengandalkan relasi mutable ke source QuestionVersion.

### 2.4 Referensi resmi
Registry resmi harus mencatat sumber dokumen, nomor/tanggal, penerbit, URL/archive, tanggal berlaku, versi, checksum/identitas dokumen, dan status verifikasi. Referensi Panca Cinta, Pembelajaran Mendalam, 8 dimensi profil lulusan, CP/TP/ATP tidak boleh disemai dari literal tanpa provenance dan persetujuan Admin/pemilik kurikulum.

## 3. IMPLEMENTASI WEB GURU
- Mengubah `TeacherCurriculumContextService.php`: batas 100 outcome kini global lintas semua versi terdaftar, bukan 100 per versi.
- Menambah regression test pada `TeacherPortalFoundationTest.php`: dua versi dengan 60 outcome masing-masing; seluruh hitungan CP tetap 120, tetapi UI context hanya memuat 100 baris, dengan batas deterministik sampai V2-040 dan tidak menampilkan V2-041.
- Tidak ada perubahan schema/migration, QuestionVersion, QuestionMultiCurriculumMapping, ExamSnapshotService, ScoringService, atau service Agent 2.
- Backup dua file sebelum perubahan berada di `.github/backups/web-guru-curriculum-cap-20261123/`; 2/2 blob SHA MATCH.

## 4. PENGUJIAN
- CI otomatis dipicu setelah perubahan.
- Run service: [45109578855](https://github.com/zane0184/cbt_november/actions/runs/45109578855), source SHA `487ea073baa39a3670e5deda8d56cbb1c36cf7e5`, status terakhir saat checkpoint dibuat: IN_PROGRESS.
- Run test: [45109597139](https://github.com/zane0184/cbt_november/actions/runs/45109597139), source SHA `b733216bda5c391e41e2ea95afa1800d5d0ae522`, status terakhir saat checkpoint dibuat: PENDING.
- Belum boleh mengklaim koreksi lulus sebelum kedua run selesai dan hasil log PHPUnit diperiksa.
- Tidak ada test lokal, Gradle, migration produksi, deployment, atau akses DB produksi.

## 5. KOORDINASI AGENT 2
Diminta keputusan eksplisit pada Issue #8 untuk:
1. Apakah `materials` legacy menjadi master materi yang dipakai Web Guru, dan apakah entitas Topic sudah ada di luar file path yang diaudit.
2. Bentuk relasi assignment → curriculum registration/version → CP/TP/ATP/KBC yang sah.
3. Apakah mapping kurikulum harus version-scoped pada QuestionVersion, bukan hanya question_id.
4. Field akademik yang wajib disalin ke ExamQuestionSnapshot dan metadata provenance immutable.
5. Ownership migration/service dan strategi transisi data agar histori lama tidak berubah.
6. Status resmi Pembelajaran Mendalam dan 8 dimensi profil lulusan dalam registry kurikulum.

Agent Web Guru tidak akan mengubah shared schema/service sebelum approval.

## 6. STATUS
- Halaman Pembelajaran: fondasi ada, integrasi kurikulum end-to-end belum lengkap.
- Materi tersedia sebagai model/tabel legacy; Topic terpisah belum ditemukan pada tree source aktif.
- QuestionVersion dan snapshot belum membawa seluruh provenance kurikulum/materi/topik.
- Profil Guru: identitas read-only + edit nomor telepon, audit trail; status kepegawaian belum ada.
- Perbaikan batas tampilan 100 outcome: IMPLEMENTED, menunggu CI.
- Portal Web Guru secara keseluruhan: **BELUM LENGKAP / BELUM SIAP PRODUKSI**.
