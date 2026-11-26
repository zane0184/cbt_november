# Audit & Implementasi — Pemetaan Kurikulum Opsional untuk Soal Umum dan CBT (2026-11-27)

Repository: `zane0184/cbt_november`
Base: `integration/zip-vps-php83-20261126`
Branch kerja: `fix/optional-curriculum-question-exam-20261127`

## AUDIT

Ditemukan pengunci kurikulum pada tiga lapisan setelah DRAFT DOCX:
1. `QuestionBank::publishQuestion()` menolak soal tanpa mapping CP, TP/ATP, dan struktur Panca Cinta/Pembelajaran Mendalam/Profil Lulusan.
2. `AssessmentCurriculumMappingService::sync()` menolak asesmen tanpa CP dan TP/ATP serta mewajibkan permission `curriculum.manage` meskipun Guru ingin membuat asesmen umum.
3. `ExamManagement::saveExam()`, daftar soal yang tersedia, dan `ExamSnapshotService::createSnapshots()` hanya menerima soal yang sudah dipetakan dan memaksa asesmen mempunyai mapping CP/TP/ATP.

Akibatnya, DRAFT DOCX yang telah disimpan tanpa mapping tetap tidak dapat diterbitkan sebagai soal umum dan tidak dapat dipakai dalam ujian CBT umum.

## DESAIN

Mendukung dua mode yang tegas dan tidak boleh dicampur:

### Mode A — Soal/asesmen umum tanpa mapping
- CP, TP/ATP, Panca Cinta, Pembelajaran Mendalam, dan dimensi Profil Lulusan tidak wajib diisi untuk menerbitkan soal umum.
- Asesmen tanpa mapping hanya dapat memakai soal terbit yang juga tidak memiliki mapping kurikulum.
- Snapshot mencatat status `NOT_MAPPED`.
- Konteks tenant, Guru, mapel, Rombel, tahun ajaran, semester, versi kurikulum pada penugasan, dan registrasi kurikulum aktif tetap wajib. Jadi yang opsional adalah mapping CP/TP/ATP pada soal dan asesmen, bukan identitas/konteks akademik penugasan.

### Mode B — Soal/asesmen dengan mapping kurikulum
- Jika mapping mulai diisi, CP dan TP/ATP wajib diisi bersama.
- Seluruh validasi lama tetap berlaku: CP resmi dan checksum terverifikasi, TP/ATP published/current, assignment Guru aktif, konsistensi CP-parent, dan mapping Panca Cinta/Pembelajaran Mendalam/Profil Lulusan resmi.
- Asesmen yang dipetakan hanya dapat memuat soal dengan mapping yang sesuai.

### Fail-closed
- Mapping parsial ditolak.
- Asesmen tanpa mapping tidak boleh mencampur soal yang sudah dipetakan.
- Asesmen dengan mapping tidak boleh memuat soal yang belum dipetakan atau mapping yang tidak cocok.
- Tidak ada CP/TP palsu, mapping sintetis, atau pelonggaran provenance sumber resmi.

## IMPLEMENTASI

Enam file runtime diubah:

1. `backend-laravel/app/Livewire/Admin/QuestionBank.php`
   - Publikasi soal tanpa mapping diizinkan sebagai soal umum.
   - Jika ada mapping apa pun, seluruh kontrak mapping resmi tetap diverifikasi.
   - Metadata versi soal mencatat `curriculum_mapping.status = NOT_REQUIRED` atau `MAPPED`; status dicatat pada audit log.

2. `backend-laravel/app/Services/TeacherPortal/AssessmentCurriculumMappingService.php`
   - Dua array kosong berarti asesmen tanpa mapping; mapping lama dibersihkan dalam transaksi dan metadata snapshot ditandai `NOT_MAPPED`.
   - CP/TP yang hanya diisi sebagian tetap ditolak.
   - Permission `exam.manage` tetap wajib. Permission `curriculum.manage` tetap wajib ketika Guru memilih mapping resmi.

3. `backend-laravel/app/Livewire/Admin/ExamManagement.php`
   - Validasi CP/TP menjadi opsional sebagai pasangan.
   - Mode tanpa mapping hanya menampilkan/menerima soal terbit yang tidak mempunyai mapping CP, outcome, maupun struktur.
   - Mode mapped tetap memeriksa kesesuaian CP/TP per penugasan.

4. `backend-laravel/app/Services/ExamSnapshotService.php`
   - Snapshot asesmen tanpa mapping diperbolehkan.
   - Mode unmapped hanya menerima soal yang juga benar-benar tidak memiliki mapping.
   - Mode mapped mempertahankan seluruh pemeriksaan provenance CP/TP, framework, materi/topik, tenant, assignment, dan snapshot immutable.
   - Snapshot konteks asesmen dan soal menandai `mapping_status = NOT_MAPPED` atau `MAPPED`.

5. `backend-laravel/resources/views/livewire/admin/question-bank.blade.php`
   - Copy UI menjelaskan mapping opsional untuk soal umum.
   - Kontrol mapping/materi/topik hanya tampil untuk akun dengan permission kurikulum.
   - Ketiadaan CP/TP resmi tidak lagi menyatakan soal tidak bisa diterbitkan; soal dapat dipakai sebagai soal umum.

6. `backend-laravel/resources/views/livewire/admin/exam-management.blade.php`
   - UI menjelaskan aturan konsistensi antara mode asesmen dan soal.

### Backup
Semua enam file dibuat backup sebelum patch di:
`backend-laravel/backups/optional-curriculum-question-exam-20261127/`

Manifest:
`backend-laravel/backups/optional-curriculum-question-exam-20261127/BACKUP_MANIFEST.md`

## PENGUJIAN

Sesuai instruksi pengguna:
- PHPUnit: tidak dijalankan.
- CI: tidak dijalankan.
- Runtime/VPS: tidak dijalankan.
- Migration/database: tidak dijalankan atau diubah.

Pemeriksaan yang dilakukan:
- Fetch ulang source dari branch setelah setiap update.
- Pemeriksaan statis jalur publikasi, sync mapping asesmen, filter soal, dan pembuatan snapshot.
- Pemeriksaan jumlah pasangan direktif Blade `@if/@endif`, `@foreach/@endforeach`, dan `@forelse/@endforelse` pada kedua view: seimbang.
- Tidak ada bukti runtime bahwa transaksi dan penyimpanan berhasil pada VPS; belum boleh disebut teruji produksi.

## BATASAN DAN CATATAN

- Jalur umum tetap membutuhkan assignment Guru aktif, registrasi kurikulum tenant aktif, konteks mapel/rombel, dan versi kurikulum pada assignment. Yang dihilangkan hanya kewajiban mapping CP/TP/ATP per soal/asesmen.
- Soal bertipe `SHORT_ANSWER` tetap ditolak oleh snapshot karena accepted answers belum tercakup dengan aman dalam immutable snapshot/scoring. Ini bukan bagian dari patch.
- Validasi bobot nilai, kunci jawaban, tipe soal, media, tenant isolation, role, permission, jadwal, participant, snapshot immutable, attempt/answer/score tidak dilonggarkan.
- Patch ini belum dinyatakan siap produksi sampai ada uji alur pada environment disposable/operasional yang diizinkan pengguna.
