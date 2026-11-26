# Laporan Koordinator — Audit dan Root Cause CP/TP Web Admin → Guru → Siswa
Tanggal: 26 November 2026 (WIB)
Repository: `zane0184/cbt_november`

## Identitas source yang diperiksa
- Source branch terbaru sesuai checkpoint koordinator: `work/rombel-docx-completion-20261126`
- Exact source HEAD: `841a1dd90dbe0277026fade1511a39b5dc9216b6`
- Tree source: 2.053 entri, recursive.
- Branch pencatatan audit: `audit/coordinator-continuation-20261126`
- HEAD branch audit sebelum laporan ini: `78dca623fbeecbddc091c477c21b070241de80df`
- Scope: CP/TP/ATP dan integrasi Web Admin, Guru, Siswa saja.
- Pemeriksaan hanya pada GitHub. Tidak membuka ZIP baseline, ChatGPT Library, Project Files, VPS, atau database produksi.

## Keputusan arsitektur yang berlaku
- Satu instalasi dan satu database akademik mandiri per madrasah; tidak ada shared academic database atau akses akademik antar-madrasah.
- Android dan Web Super Admin ditunda sampai Juni 2027.
- Role portal lokal: ADMIN_MADRASAH, GURU, SISWA. Proktor adalah capability/menu tambahan Guru, bukan role permanen. Operator dan Kamad bukan portal/role aktif.
- Kurikulum Merdeka dan KBC adalah satu kesatuan pada konteks pembelajaran, asesmen, bank soal, snapshot dan laporan.
- Audit ini tidak mengubah keputusan tersebut dan tidak menyentuh pekerjaan Android/Super Admin.

## AUDIT — temuan aktual source

### 1. Halaman CP/TP Admin yang lama membaca domain legacy
File:
- `backend-laravel/app/Livewire/Admin/CurriculumManagement.php`
- `backend-laravel/resources/views/livewire/admin/curriculum-management.blade.php`

Temuan:
- Component menggunakan model legacy `LearningOutcome`, relasi `currentVersion` dan `objectives` (`LearningObjective`).
- View secara eksplisit menamai dirinya “Tampilan baca-saja data CP/TP legacy” dan memberi peringatan bahwa data bukan registry resmi terverifikasi.
- Method create TP lama dipertahankan sebagai compatibility guard yang selalu menolak (HTTP 403); Admin memang tidak berwenang menulis TP/ATP.
- Jika tabel legacy kosong, halaman akan menampilkan “Belum ada data referensi CP/TP legacy”. Halaman ini tidak membaca CP canonical dari `MultiLearningOutcome` atau TP/ATP Guru dari `TenantLearningOutcome`.
- Dengan demikian halaman Referensi Kurikulum lama bukan tampilan authoritative CP/TP yang harus dipakai untuk integrasi baru.

Status: **PARSIAL / LEGACY VIEW**. Ini adalah salah satu akar tampilan kosong/berantakan, tetapi bukan satu-satunya penyebab CP kosong.

### 2. Admin Registry sudah ada, tetapi belum memiliki isi CP resmi
File:
- `backend-laravel/app/Livewire/Admin/CurriculumRegistryManagement.php`
- `backend-laravel/resources/views/livewire/admin/curriculum-registry-management.blade.php`
- `backend-laravel/database/seeders/CurriculumReferenceSourceSeeder.php`
- `backend-laravel/database/seeders/CurriculumRegistryBootstrapSeeder.php`

Temuan:
- Registry Admin mendaftarkan versi kurikulum resmi, melakukan sinkronisasi katalog mata pelajaran, dan memetakan mapel lokal ke node mapel per versi/fase.
- View registry menyatakan CP per elemen belum lengkap sampai artefak resmi, versi dan checksum diverifikasi.
- `CurriculumRegistryBootstrapSeeder` membuat versi KMA-1503-2025, fase A/B/C, kelas MI 1–6, node mata pelajaran, lima node Panca Cinta, empat prinsip PM, empat olah PM, empat framework PM, dan delapan dimensi profil lulusan.
- Metadata versi secara eksplisit menetapkan `artifact_checksum_verified=false` dan `cp_content_imported=false`.
- Seeder tersebut tidak membuat record CP bertipe `MultiLearningOutcome`; tidak ditemukan seeder CP resmi/TP/ATP pada direktori `database/seeders`.
- `CurriculumReferenceSourceSeeder` mendaftarkan sumber CP umum BSKAP 046/H/KR/2025 sebagai `METADATA_VERIFIED`, bukan artifact checksum verified; sumber perubahan BKPDM 020/2026 hanya metadata siaran pers; Kepdirjen Pendis 9941/2025 tetap `PENDING`.

Status: struktur dan metadata referensi tersedia, **isi CP resmi tidak tersedia**.

### 3. Root cause CP kosong pada Guru
File:
- `backend-laravel/app/Services/TeacherPortal/TeacherCurriculumContextService.php`
- `backend-laravel/app/Livewire/Teacher/TeacherLearning.php`
- `backend-laravel/resources/views/livewire/teacher/portal-learning.blade.php`

Temuan:
- `forAssignment()` mencari CP pada `MultiLearningOutcome` dengan filter owner, version, subject structure, `outcome_type=CP`, ACTIVE, official.
- `cp_options` hanya selectable jika sumber CP berstatus `ARTIFACT_CHECKSUM_VERIFIED`, checksum source ada, checksum CP ada, dan keduanya sama.
- Status `CP_REGISTRY_PENDING` / `CP_SOURCE_UNVERIFIED` dan notice menonaktifkan form TP/ATP saat CP sah tidak tersedia.
- Guru membuat TP dan ATP sebagai data tenant melalui `TeacherLearningOutcomeService`, berdasarkan penugasan mapel/kelas/tahun/semester, CP induk, versi kurikulum, serta mapping Panca Cinta/PM/dimensi.
- Jadi UI Guru yang kosong konsisten dengan tidak adanya record CP resmi di registry, bukan sekadar masalah dropdown atau styling.

Status: **PARSIAL / fail-closed bekerja sesuai kontrak provenance**; positive path CP resmi tidak dapat berjalan karena artefak sumber belum tersedia.

### 4. TP/ATP dan pengamanan provenance
File:
- `backend-laravel/app/Services/TeacherPortal/TeacherLearningOutcomeService.php`
- `backend-laravel/tests/Feature/TeacherLearningOutcomeProvenanceTest.php`
- `backend-laravel/tests/Feature/P002ANonCpAcademicAcceptanceTest.php`

Temuan:
- Service memeriksa permission `curriculum.manage` di server.
- CP dimuat ulang dalam transaksi dan harus cocok dengan owner, versi, struktur mapel, status ACTIVE, official immutable, sumber yang berada pada owner scope, status artifact checksum verified, dan SHA-256 source/CP yang cocok.
- TP/ATP terikat ke assignment Guru, registration, curriculum owner/version, mapel, tahun ajaran dan semester.
- Test positif menggunakan fixture CP sintetis yang secara eksplisit test-only. Fixture tersebut bukan konten CP resmi dan tidak boleh dipakai sebagai data seed produksi.
- Addendum `docs/P0_02A1_FINAL_STATUS_ADDENDUM_2026-11-25.md` menyatakan Run #182 PASS (PHP 8.3/8.4 + MySQL 8.0/InnoDB disposable) untuk hardening negatif/provenance dan non-CP acceptance pada SHA `185f6d515bc92beb08bff8dc8b2c2c91d96458dd`. Bukti tersebut tidak membuktikan positive path dengan CP resmi dan bukan CI pada source HEAD terbaru `841a1dd...`.

Status: **NEGATIVE GATE PASS pada SHA historis yang dicatat; CP resmi positive path BLOCKED**.

### 5. Integrasi ke Bank Soal dan snapshot ujian
File:
- `backend-laravel/app/Services/TeacherPortal/QuestionVersionCurriculumMappingService.php`
- `backend-laravel/app/Models/QuestionVersionCurriculumMapping.php`
- `backend-laravel/app/Services/ExamSnapshotService.php`
- `backend-laravel/app/Models/ExamQuestionSnapshot.php`

Temuan:
- Source terbaru sudah memiliki mapping pada level `QuestionVersion`, bukan hanya `Question`.
- Service mapping memvalidasi CP official + checksum dan TP/ATP published/current yang berasal dari assignment Guru.
- `ExamSnapshotService` membaca mapping versi soal, memvalidasi konsistensi CP/TP/ATP dan framework, lalu menyimpan salinan konteks kurikulum pada `exam_question_snapshots.curriculum_context_snapshot`.
- Snapshot mencakup CP, TP/ATP, Panca Cinta/PM/dimensi, versi kurikulum, assignment dan konteks mapel.
- Ini lebih baru daripada deskripsi awal Issue #14. Issue #14 masih OPEN dan perlu direkonsiliasi dengan source terbaru serta test exact SHA; jangan mengulang implementasi mapping/snapshot tanpa audit diff/test terlebih dahulu.
- Karena CP resmi tidak ada, jalur publish question/Exam yang mewajibkan CP resmi tetap tertahan meskipun mekanisme mapping/snapshot telah tersedia.

Status: **IMPLEMENTASI SOURCE TERSEDIA; acceptance end-to-end pada source HEAD terbaru belum dibuktikan oleh audit ini**.

### 6. Siswa
- Source snapshot ujian menyimpan konteks CP/TP/ATP sebagai snapshot immutable untuk soal yang diterbitkan.
- Ini adalah jalur integrasi Siswa melalui paket soal ujian, bukan alasan untuk menampilkan/mengubah CP induk di portal siswa.
- Tanpa CP resmi dan mapping valid, soal/ujian yang mewajibkan mapping CP/TP tidak dapat melewati gate publikasi/snapshot.
- Audit ini tidak mengubah portal Siswa.

Status: **DEPENDENCY BLOCKED oleh data CP resmi dan acceptance E2E**.

### 7. Ketersediaan artefak resmi pada GitHub
- Recursive tree source terbaru berisi 2.053 entri.
- Pencarian seluruh tree untuk artefak PDF/DOC/DOCX/XLS/XLSX/CSV/JSON/ZIP terkait kurikulum/CP tidak menemukan file biner/artefak sumber CP asli yang dapat dihitung checksum-nya.
- Catatan audit sebelumnya `docs/P0_02_AUDIT_AKADEMIK_CP_PROVENANCE_2026-11-25.md` juga mencatat bahwa PDF asli BSKAP 046 belum diarsipkan; byte asli dan verifikasi Kepdirjen Pendis 9941 beserta lampiran CP MI belum tersedia; naskah keputusan BKPDM 020/2026 beserta lampiran lengkap belum tersedia.
- Tidak ada konten CP resmi yang boleh diisi dari tebakan, contoh test, nama mapel, atau ringkasan metadata.

Status: **BLOCKED — missing source artifacts**.

## DESAIN — rencana penyelesaian tanpa mengulang patch

1. **Sumber CP resmi:** masukkan ke GitHub artefak asli (bukan ringkasan/hasil salin) BSKAP 046/H/KR/2025 beserta lampiran CP SD/MI, Kepdirjen Pendis 9941/2025 beserta seluruh lampiran CP MI PAI/Bahasa Arab, dan naskah/annex resmi BKPDM 020/2026 untuk perubahan Agama/Budi Pekerti bila berlaku.
2. Verifikasi asal penerbit, nomor/tanggal/versi, halaman/lampiran, ukuran, SHA-256, dan relasi versi efektif. Status sumber tidak boleh diubah menjadi `ARTIFACT_CHECKSUM_VERIFIED` sebelum file asli tersedia dan pemeriksaan lulus.
3. Bangun dataset CP per fase, kelas, mapel, elemen/sub-elemen sesuai struktur dokumen; setiap row menyimpan source_id, locator halaman/bagian, checksum, versi dan provenance. Tidak membuat CP dari AI atau synthetic fixture.
4. Sinkronkan halaman Admin lama agar tidak lagi menjadikan tabel `LearningOutcome/LearningObjective` legacy sebagai registry utama. Admin melihat CP resmi dan status verifikasinya secara read-only; Admin tetap tidak membuat TP/ATP.
5. Guru melihat CP sesuai assignment aktif, lalu membuat TP dan ATP di bawah CP resmi; KBC/Panca Cinta dan Pembelajaran Mendalam melekat sebagai mapping dalam konteks yang sama.
6. Bank Soal hanya menerima CP dan TP/ATP yang lolos owner/version/assignment/provenance. Snapshot immutable mempertahankan mapping versi soal. Siswa menerima snapshot, bukan query ke data CP yang berubah.
7. Tambahkan/selaraskan acceptance test pada exact source SHA: Admin registry → Guru CP dropdown → create/publish TP → create/publish ATP → question mapping → Exam snapshot → Student exam payload. Sertakan negative cases no-data, wrong subject/phase/version, source pending, checksum mismatch, revoked assignment, dan perubahan data setelah snapshot.
8. Rekonsiliasi Issue #14/#15 terhadap source terbaru. Jangan menutup issue hanya karena file/class tersedia; perlu bukti test exact SHA.

## IMPLEMENTASI
- Tidak ada source aplikasi, migration, seeder, test, route, atau database yang diubah pada audit ini.
- Tidak ada backup source dibuat karena tidak ada file source yang diedit.
- Tidak ada branch implementer yang diubah.
- Laporan ini dicatat pada branch audit, terpisah dari source branch paket.

## PENGUJIAN
- Pemeriksaan GitHub recursive tree, source file, view, service, model, seeder, migration, tests, checkpoint, issue #14 dan #15.
- Exact source HEAD yang diaudit: `841a1dd90dbe0277026fade1511a39b5dc9216b6`.
- Source hashes utama:
  - Admin `CurriculumManagement.php`: `992bb7c96981b781bf89240ca68d0440f06b32bc`
  - Admin legacy view: `7a36db460e6cd5c124f143c78f13f73d1cbd3f4f`
  - Admin Registry component: `de4b1411452afb84534dc652e15426e0d7ebf4a8`
  - Teacher context service: `9069e1ca720b4c34c1fbdccd0ce62060b8fb8600`
  - Teacher learning component: `574893e81324ba82b88f1c647bce530d759188e0`
  - Teacher learning view: `5cde335500fafe7d9028b4ca23be0981bda9c362`
  - Teacher outcome service: `df33b1ebfcaf11a7992e91289aa08c82e8eae736`
  - CP reference seeder: `e2e75d34f2fbca683841190ff36ff143aec82aa5`
  - Registry bootstrap seeder: `e7cd1e94144b6d7bbad7e54f42a1f87a80af531d`
  - Question mapping service: `42853592d3340be2a8cff69139e5252b0353de0e`
  - Exam snapshot service: `861ae514937a65f08af3ef21386af109251cb513`
- Automated tests were NOT RUN in this audit turn. The Run #182 results above are historical evidence from the cited checkpoint and exact older SHA, not a new test run.
- No browser E2E, deployment, migration, or UAT was run.

## CHECKPOINT / KEPUTUSAN
- P0 root cause tampilan: Admin CP/TP legacy page masih membaca domain legacy dan bukan canonical registry.
- P0 root cause isi CP kosong: tidak ada row CP resmi dan tidak ada artefak CP asli terverifikasi di source GitHub.
- TP/ATP authoring guard dan QuestionVersion→Exam snapshot code already exist; do not rebuild them.
- Next safe action: obtain official CP source artifacts in GitHub, verify SHA/provenance, then implement only the missing CP import/registry data and replace Admin legacy read-only display with canonical view.
- Status: **AUDIT CP/TP SELESAI — IMPLEMENTASI DATA CP BLOCKED OLEH ARTEFAK RESMI — SOURCE BELUM SIAP DIKLAIM CP/TP END-TO-END**.
