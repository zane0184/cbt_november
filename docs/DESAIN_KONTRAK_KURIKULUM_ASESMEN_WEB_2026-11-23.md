# DESAIN KONTRAK BACKEND KURIKULUM, ASESMEN, DAN JEJAK SOAL — WEB CBT
Tanggal: 23 November 2026
Status: DESAIN IMPLEMENTASI TERPADU — source schema belum diubah pada dokumen ini

## 1. Dasar resmi yang diverifikasi

| Kerangka | Dokumen | Fakta yang dipakai | Status sumber |
|---|---|---|---|
| Kurikulum Madrasah | KMA 1503 Tahun 2025, perubahan atas KMA 450/2024 | Menetapkan kerangka kurikulum madrasah yang mengintegrasikan Pembelajaran Mendalam dan KBC/Panca Cinta; fase A kelas I–II MI, B kelas III–IV, C kelas V–VI | Metadata dan isi dokumen berhasil diperiksa pada salinan domain Kanwil Kemenag |
| KBC | Kepdirjen Pendis 6077 Tahun 2025, Panduan Kurikulum Berbasis Cinta | Panduan berlaku bagi madrasah pemerintah dan masyarakat; lima topik Panca Cinta | Metadata dan isi dokumen berhasil diperiksa pada CDN Kemenag |
| Profil lulusan | Permendikdasmen 10 Tahun 2025 | Pasal 4 menetapkan delapan dimensi profil lulusan | JDIH/BPK; status peraturan berlaku pada halaman metadata |
| Pembelajaran Mendalam | Kepmendikdasmen 126/P/2025 | Pedoman implementasi PM; prinsip memuliakan, berkesadaran, bermakna, menggembirakan; olah pikir, hati, rasa, raga | Salinan dokumen pemerintah/BPMP berhasil diperiksa |
| Panduan kurikulum satuan pendidikan | Panduan Pengembangan Kurikulum Satuan Pendidikan edisi revisi 2025 | Referensi panduan pengembangan kurikulum satuan pendidikan | Dokumen BSKAP Kemendikdasmen berhasil diperiksa |
| CP | CP umum mengacu pada Keputusan Kepala BSKAP 046/H/KR/2025, dengan perubahan terbatas melalui Keputusan Kepala BKPDM 020 Tahun 2026 untuk Pendidikan Agama dan Budi Pekerti; CP PAI dan Bahasa Arab madrasah perlu memperhatikan keputusan Dirjen Pendis 9941/2025 yang artefaknya masih harus diverifikasi | CP induk tidak boleh dikarang atau diisi dari sumber tidak terverifikasi | Belum dilakukan import konten CP penuh |

### 1.1 Panca Cinta
Sesuai KMA 1503/2025 dan Kepdirjen Pendis 6077/2025:
1. Cinta Allah dan Rasul-Nya.
2. Cinta Ilmu.
3. Cinta Lingkungan.
4. Cinta Diri dan Sesama Manusia.
5. Cinta Tanah Air.

Panca Cinta adalah topik/nilai lintas pembelajaran, bukan mata pelajaran atau jenis ujian.

### 1.2 Delapan dimensi profil lulusan
Sesuai Pasal 4 Permendikdasmen 10/2025:
1. Keimanan dan ketakwaan terhadap Tuhan Yang Maha Esa.
2. Kewargaan.
3. Penalaran kritis.
4. Kreativitas.
5. Kolaborasi.
6. Kemandirian.
7. Kesehatan.
8. Komunikasi.

Delapan dimensi adalah profil lulusan; bukan jenis soal atau pengganti CP/TP.

### 1.3 Pembelajaran Mendalam
Kepmendikdasmen 126/P/2025 (ditetapkan 4 September 2025) dan panduan pemerintah menjelaskan:
- Prinsip: memuliakan, berkesadaran, bermakna, dan menggembirakan.
- Olah: pikir, hati, rasa, raga.
- Dokumen menjelaskan bahwa pembelajaran mendalam menuntut murid memahami, menganalisis, menerapkan konsep, dan melakukan refleksi dalam beragam konteks; ini bukan enum fase belajar yang harus dikunci.
- Kerangka pembelajaran: praktik pedagogis, kemitraan, lingkungan, pemanfaatan digital.

PM adalah pendekatan pembelajaran, bukan kurikulum baru.

### 1.4 Fase MI
KMA 1503/2025 Bab II:
- Fase A: kelas I–II MI.
- Fase B: kelas III–IV MI.
- Fase C: kelas V–VI MI.

### 1.5 Tautan sumber
- KMA 1503/2025 (salinan Kanwil Kemenag): https://sulteng.kemenag.go.id/files/show/20251020162801surat-penyampaian-kma-kurikulum-2025%2Blampiran_compressed-tzR.pdf
- Kepdirjen Pendis 6077/2025 (CDN Kemenag): https://cdn.kemenag.go.id/storage/archives/panduan-kurikulum-berbasis-cinta.pdf
- Permendikdasmen 10/2025 (JDIH/BPK): https://peraturan.bpk.go.id/Details/321419/permendikdasmen-no-10-tahun-2025
- Kepmendikdasmen 126/P/2025 (ditetapkan 4 September 2025; BPMP Kemendikdasmen): https://bpmpbabel.kemendikdasmen.go.id/PPID/wp-content/uploads/2025/09/Salinan-Kepmendikdasmen-Nomor-126_P_2025-CAP.pdf
- Keputusan Kepala BSKAP 046/H/KR/2025 (CP umum): https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
- Keputusan Kepala BKPDM 020 Tahun 2026 (siaran pers resmi menyatakan perubahan hanya untuk Pendidikan Agama dan Budi Pekerti): https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti
- Kepdirjen Pendis 9941 Tahun 2025 (CP PAI dan Bahasa Arab madrasah; artefak keputusan masih perlu diverifikasi): https://batam.kemenag.go.id/main/perkuat-mutu-pembelajaran-pengawas-madrasah-hadiri-perdana-mgmp-pai-dan-bahasa-arab-mts-kota-batam
- Panduan Pengembangan Kurikulum Satuan Pendidikan Revisi 2025: https://kurikulum.kemendikdasmen.go.id/file/1755670818_manage_file.pdf

Checksum PDF belum dihitung karena binary belum diarsipkan ke workspace branch. Source record harus membedakan metadata verified dengan artifact checksum verified.

## 2. Prinsip kontrak kanonis
1. Satu kontrak backend untuk Web Admin, Web Guru, Web Siswa.
2. Tidak ada enum kategori asesmen tetap UTS/UAS/UM/SIMULASI TKA.
3. Pisahkan fungsi asesmen, cakupan, nama kegiatan, instrumen, dan mode pelaksanaan.
4. CP induk adalah referensi resmi yang read-only dan berversi.
5. Guru mengembangkan TP dan ATP berdasarkan CP dan assignment aktif.
6. Panca Cinta, PM, dan 8 dimensi menjadi registry bertipe dan berversi, bukan literal di service/view.
7. Materi legacy dipertahankan; topik dibuat sebagai child dari materi agar tidak mengarang ulang master materi.
8. Mapping kurikulum melekat pada QuestionVersion, bukan hanya Question.
9. ExamQuestionSnapshot menyimpan salinan konteks kurikulum/provenance yang immutable.
10. Perubahan master setelah ujian diterbitkan tidak mengubah snapshot, hasil, atau laporan historis.
11. Semua operasi divalidasi tenant/install, ownership, role, permission, assignment, status, dan tanggal efektif di server.

## 3. Struktur data yang ditetapkan

### 3.1 curriculum_reference_sources
Menyimpan identitas dokumen sumber:
- id, tenant_id (SYSTEM_GLOBAL atau instalasi lokal);
- issuer, document_number, title, document_version;
- published_at, effective_from, effective_until;
- source_uri, archive_path, checksum_sha256;
- verification_status, verified_at, verified_by;
- metadata JSON, timestamps.

verification_status membedakan PENDING, METADATA_VERIFIED, ARTIFACT_CHECKSUM_VERIFIED, REJECTED. Hanya sumber yang memenuhi kebijakan status yang dapat menjadi referensi resmi selectable.

### 3.2 Provenance pada MultiCurriculum family
Tambahkan source_id, source_locator, effective_from/effective_until, verification_status, verified_at, source_checksum, metadata ke:
- multi_curriculum_versions;
- multi_curriculum_structures;
- multi_learning_outcomes.

Gunakan struktur yang sudah ada. Jangan membuat tabel CP kedua:
- MultiCurriculum/MultiCurriculumVersion = framework/version.
- MultiCurriculumStructure = fase, kelas, mapel, elemen, Panca Cinta, prinsip/kerangka PM, 8 dimensi.
- MultiLearningOutcome = CP resmi, TP, ATP, dan outcome turunan bertipe.

Semua perubahan resmi harus menambah versi/record baru; jangan menimpa data historis.

### 3.3 assessment_taxonomy_options
Registry opsi dinamis per dimensi:
- dimension: FUNCTION, SCOPE, INSTRUMENT, DELIVERY_MODE;
- code, label, description, version_code;
- source_id, effective_from/effective_until, status, is_selectable;
- sort_order, metadata, timestamps.

Nilai opsi tidak dibatasi enum. Contoh label awal hanya data referensi yang harus disahkan sumbernya. Nama kegiatan ujian tetap activity_name yang dapat ditentukan madrasah.

Tambahan pada assessments:
- assessment_function_option_id;
- assessment_scope_option_id;
- instrument_type_option_id;
- delivery_mode_option_id;
- activity_name;
- curriculum_version_id;
- curriculum_context_snapshot JSON.
Kolom assessment_type legacy tetap dipertahankan untuk backward compatibility sampai transisi selesai.

### 3.4 material_topics
Entitas child dari materials:
- id, tenant_id, material_id, parent_id nullable;
- code, title, description, version_code;
- effective_from/effective_until, status, metadata;
- created_by, timestamps, softDeletes.

Materi tetap memakai model/tabel legacy yang sudah ada. Topic tidak boleh bebas lintas materi, subject, phase, atau tenant.

### 3.5 question_version_curriculum_mappings
Mapping CP/TP/ATP ke revisi soal:
- id, tenant_id, question_version_id;
- multi_curriculum_version_id, multi_learning_outcome_id;
- material_id, material_topic_id nullable;
- academic_year_id, semester_id nullable;
- mapping_role, weight, is_primary, metadata, created_by, timestamps.

### 3.6 question_version_structure_mappings
Mapping KBC/Panca Cinta, PM, dan 8 dimensi ke revisi soal:
- id, tenant_id, question_version_id;
- multi_curriculum_version_id, multi_curriculum_structure_id;
- academic_year_id, semester_id nullable;
- mapping_role, weight, metadata, created_by, timestamps.

Service harus memeriksa node_type yang diperbolehkan sesuai mapping_role; node fase/mapel tidak boleh dipakai sebagai DPL/KBC.

### 3.7 Immutable snapshots
- ExamQuestionSnapshot mendapat curriculum_context_snapshot JSON.
- Exam mendapat assessment_context_snapshot JSON.
- Snapshot menyimpan label, code, source id, source document number/title, version, effective date, owner, phase/grade/subject, CP/TP/ATP, materi/topik, Panca Cinta, PM, 8 dimensi dan mapping weight.
- Snapshot tidak menyimpan jawaban benar/accepted answer dalam payload Siswa. Kunci penilaian tetap private di server-side snapshot/scoring store.
- Snapshot dibuat di transaksi yang sama dengan penguncian Exam dan QuestionVersion; perubahan mapping setelah Exam lock ditolak.

## 4. Peran portal

### Admin Madrasah
- Mengelola metadata registrasi kurikulum untuk instalasi lokal, periode akademik, master mapel/kelas/rombel, assignment, import/export administratif.
- Mengelola/meninjau sumber dan registry lokal sesuai haknya; tidak dapat mengubah isi CP resmi induk.
- Tidak membuat TP/ATP Guru, soal, asesmen, ujian, token, attempt, jawaban, atau nilai.

### Guru
- Memilih assignment aktif dan versi kurikulum yang valid untuk assignment.
- Memilih CP resmi read-only.
- Mengembangkan TP dan ATP pada konteks assignment.
- Mengelola materi/topik dan pemetaan KBC/PM/8 dimensi.
- Memetakan QuestionVersion ke CP/TP/ATP, materi/topik, dan nilai/profil.
- Menggunakan semua metadata tersebut pada asesmen/ujian dan laporan.

### Siswa
- Hanya menerima soal dan metadata yang diotorisasi.
- Tidak menerima CP source internals, answer key, is_correct, explanation privat, accepted answer, rubrik internal, storage path, atau data siswa lain.
- Hasil/feedback CP/TP hanya ditampilkan bila Guru mempublikasikan dan backend mengizinkan.

## 5. Urutan implementasi dan acceptance gates
1. Migration additive + model/relation untuk provenance, taxonomy, topic, mapping.
2. Service validator tenant/assignment/version/effective date dan API contract.
3. Seed registry sumber resmi yang metadata-nya diverifikasi; CP content import terpisah setelah dokumen CP MI per versi diarsipkan dan checksum dihitung.
4. Admin UI untuk registry, periode, master dan read-only CP.
5. Guru UI untuk assignment context, CP selection, TP/ATP authoring, material/topic, KBC/PM/8 dimensions, QuestionVersion mapping.
6. ExamSnapshotService menyimpan immutable academic/curriculum context.
7. Student projection/redaction dan browser-to-Laravel E2E.
8. PHPUnit + disposable MySQL 8 + concurrency/authorization/version-history + browser E2E.
9. UAT Admin/Guru/Siswa.

Tidak ada migration produksi, merge, deployment, atau distribusi final tanpa owner approval.


## 10. Penegasan owner — satu ekosistem, bukan tiga kurikulum (23 November 2026)

Pemilik proyek menegaskan bahwa Kurikulum Merdeka, Kurikulum Berbasis Cinta/Panca Cinta, dan Pembelajaran Mendalam harus dibangun sebagai **satu kesatuan sistem akademik dan pembelajaran**. Ketiganya bukan kurikulum alternatif, bukan tiga database, dan bukan tiga modul yang berdiri sendiri.

Peran dalam satu ekosistem:
- Kurikulum Merdeka menjadi kerangka akademik: fase, kelas, mata pelajaran, CP, TP, ATP dan struktur asesmen.
- Panca Cinta merupakan nilai/topik lintas pembelajaran yang diintegrasikan ke perencanaan, materi, aktivitas, asesmen/soal jika relevan, serta refleksi.
- Pembelajaran Mendalam merupakan pendekatan pedagogis dan kerangka proses pembelajaran; bukan kurikulum baru, jenis ujian, atau enum fase.
- Delapan dimensi profil lulusan merupakan dimensi profil/capaian yang dipetakan saat relevan dan terukur; bukan pengganti CP/TP.

Satu jalur data kanonis wajib dipakai lintas portal dan laporan:
`source/version` → `tenant curriculum registration` → `academic year/semester/phase/grade/subject` → `TeacherSubjectAssignment pinned context` → `official CP` → `teacher TP/ATP` → `material/topic` → `Panca Cinta + PM + graduate dimensions` → `QuestionVersion mappings` → `Assessment context` → `immutable Exam/Question snapshots` → `Attempt/Answer/Score` → `achievement and historical reports`.

Kriteria penerimaan tambahan:
1. Satu referensi versi kurikulum dan satu konteks assignment menjadi sumber kebenaran; tidak boleh ada duplikasi tiga modul.
2. Semua mapping divalidasi berdasarkan tenant/instalasi, owner sumber, assignment, subject, phase, effective date, verification status, node type, dan version.
3. Snapshot ujian menyimpan konteks akademik/framework beserta provenance; perubahan master sesudah publish tidak mengubah histori.
4. Laporan capaian harus mengonsumsi konteks versi/snapshot yang sama. Implementasi legacy `LearningOutcome/LearningObjective/LearningPath` wajib direkonsiliasi dengan `MultiLearningOutcome/TenantLearningOutcome` melalui rencana read-model/migrasi kompatibel; jangan menciptakan dua sumber kebenaran permanen.
5. UI boleh memiliki halaman kerja berbeda menurut role, tetapi data dan service contract tetap satu dan terhubung.
6. Admin mengelola registry/periode/master sesuai kewenangan; Guru mengembangkan TP/ATP dan pemetaan sesuai assignment; Siswa hanya menerima metadata/feedback yang dipublikasikan dan diotorisasi.
7. Status integrasi baru PASS jika test end-to-end membuktikan assignment sampai laporan historis, termasuk perubahan versi dan preservasi snapshot.

Audit source 23 November menemukan bahwa mapping assignment, QuestionVersion, dan immutable ExamSnapshot sudah menggunakan keluarga model kanonis. Namun `CurriculumAchievementService` dan `CurriculumAnalyticsController` masih memakai model achievement legacy. Karena itu integrasi laporan akhir **belum terverifikasi selesai** dan harus menjadi backlog P3 pada `REPORT_TAHAP_LANJUTAN_TERARAH_2026-11-23.md`.
