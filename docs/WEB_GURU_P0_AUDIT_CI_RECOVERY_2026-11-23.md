# Web Guru — Audit P0 dan Pemulihan Bukti CI

Tanggal: 23 November 2026  
Agent: Agent 3 — Web Guru  
Repository: `zane0184/cbt_november`  
Branch: `work/web-guru-phase1-audit-implementation-20261122`  
HEAD saat audit read-only dimulai: `d3c964be07ea8d687de3842bbf4f95e23325ebcc`  
Acuan produk: `main/docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md`  
Status: **AUDIT SOURCE SELESAI — P0 CI BLOCKED, WEB GURU PARTIAL**

## 1. AUDIT — Identitas dan integritas branch

- Ref branch aktual menunjuk ke `d3c964be07ea8d687de3842bbf4f95e23325ebcc`.
- Commit HEAD adalah `docs(web-guru): checkpoint Phase 4B published question gate`; diff commit hanya mengubah checkpoint dokumentasi.
- Compare source Phase 4B `18830228b64e6718291119662a2907e0e2f416b8` ke HEAD menunjukkan 4 commit tambahan, dengan file berubah hanya:
  - `TeacherPracticeWorkflowTest.php` (penambahan regresi Phase 4B);
  - checkpoint Phase 4B;
  - laporan Phase 4B;
  - backup checkpoint.
- Compare test SHA `616dc82f006e0c8327f5738857cd33d53ec9f071` ke HEAD menunjukkan 3 commit tambahan dan seluruh perubahan hanya dokumentasi/checkpoint. Source runtime tidak berubah setelah source SHA Phase 4B.
- Tidak ditemukan workspace checkout lokal pada sesi ini. Pemeriksaan source dilakukan melalui repository GitHub pada ref yang disebutkan, bukan mengasumsikan file lokal tersedia.

## 2. AUDIT — Matriks fitur Web Guru dari source aktual

Status berikut adalah inspeksi source statis; bukan bukti runtime/operasional.

| Modul | Status | Bukti dan gap utama |
|---|---|---|
| Login / boundary Guru | EXISTS, NOT RUNTIME VERIFIED | Route Guru memakai `auth`, `tenant`, `license`, dan `EnsureTeacherPortalAccess`. `TeacherPortalAccess` memeriksa user persisted, tepat satu role GURU, tenant context, dan tepat satu TeacherProfile tenant-local. PHPUnit/HTTP runtime belum terbukti. |
| Dashboard Guru | EXISTS, PARTIAL | `TeacherDashboard` dan `TeacherPortalDataService::dashboard()` menyediakan statistik assignment/mapel/rombel/siswa. Agenda lengkap, jumlah soal, status ujian, essay pending, dan Proktor hari ini belum terbukti tersedia. |
| Penugasan Saya | EXISTS, PARTIAL | Halaman read-only `/guru/penugasan` memakai `TeacherPortalDataService::learning()`; source route, view, menu desktop/mobile, dan regression test tersedia. Runtime belum diverifikasi. |
| Pembelajaran | EXISTS, PARTIAL | `TeacherLearning` menampilkan konteks assignment dan kurikulum. Perencanaan pembelajaran/agenda belum lengkap. |
| Kurikulum / CP-TP-ATP | PARTIAL / NEEDS DATA | Tampilan referensi kurikulum dan mapping legacy ada; provenance resmi, registrasi versi, Panca Cinta, Pembelajaran Mendalam, dan integrasi lintas alur belum terbukti lengkap. Data resmi tidak boleh direka. |
| Bank Soal | EXISTS, PARTIAL | List/search/filter mapel/status/pagination, create/edit/archive, dan lima tipe dasar ada. Filter fase/kelas, kurikulum/versi, ATP, materi, difficulty, cognitive level, owner, media authoring, preview RTL, formula editor, dan import belum lengkap. SHORT_ANSWER tetap BLOCKED; ESSAY manual review. |
| Asesmen & Exam Builder Mode A | EXISTS, PARTIAL | Route Guru, assignment/subject scope, roster, snapshot, publish/token tersedia. Phase 4B menambahkan filter Question PUBLISHED + current QuestionVersion PUBLISHED pada daftar dan validasi server-side `saveExam()`. Invariant ini belum terbukti pada `ExamSnapshotService` untuk semua caller. |
| Monitoring Mode A | BLOCKED / NOT EXPOSED | Menu masih nonaktif; route/service Guru yang aman dan runtime-tested belum dibuktikan. |
| Proktor Mode B | BLOCKED | Direct Exam–Room, placement, assignment lifecycle, overlap serialization, dan monitoring Room-scoped belum disetujui/terbukti lintas Agent 1/2. |
| Penilaian Essay | BLOCKED / NOT EXPOSED | ESSAY berstatus manual review, tetapi UI/workflow rubrik, penilaian, koreksi, histori, dan finalisasi belum dibuktikan. |
| Hasil & Laporan | BLOCKED / NOT EXPOSED | Menu Guru masih ditahan; rekap/export/print Guru belum terverifikasi. |
| Analytics | BLOCKED / NOT EXPOSED | Menu Guru masih ditahan; analisis butir/capaian dengan data final belum terbukti. |
| Profil Guru | EXISTS, PARTIAL | Profil dan perubahan nomor kontak dengan audit tersedia. Password, notifikasi, preferensi tampilan, dan riwayat aktivitas belum lengkap. |
| Pengaturan Guru | MISSING / DEFERRED | Preferensi tampilan/notifikasi belum dibuktikan sebagai alur lengkap. |

## 3. AUDIT — P0 security dan ownership lintas-agent

### 3.1 Source branch Web Guru

Blob yang diperiksa pada branch aktif:
- `TeacherPortalAccess.php`: `8c60fb6db17512d6bbf8845027f69841172e2f60`.
- `TeacherPortalComponent.php`: `d1bba0a7d731f4637e3f096b661730c46ce6aae9`.
- `EnsureTeacherPortalAccess.php`: `50effb3b17a6978bbb7bc25819e932bbb67f5928`.
- `QuestionBank.php`: `ad7e6590592ee25c126b2886b739331eed3cc34e`.
- `ExamManagement.php`: `453879ef8ad42a7dbea9cd6a78c5eb7c8face219`.
- `TeacherPortalFoundationTest.php`: `c3ba6a2f938990d1e9cbd2cb6503c3dda51f73f5`.
- `TeacherPracticeWorkflowTest.php`: `0271a7836c5fefe76b97e9bca70ded23ae35619e`.
- `ExamSnapshotService.php`: `d0bfcd70b2777a3884c7f92b7c256f05492645ec`.

### 3.2 Celah bukti HTTP Livewire

- `TeacherPortalComponent::boot()` memanggil `TeacherPortalAccess::profile(auth()->user())` pada setiap request komponen.
- `QuestionBank` dan `ExamManagement` juga menjalankan pemeriksaan akses pada `boot()`/ `mount()`.
- Regression test post-mount untuk permission revoke, assignment inactive, TeacherProfile soft-delete, User soft-delete, dan role GURU revoke saat ini memakai `Livewire::test()` harness.
- Satu test POST HTTP Livewire nyata yang ada membuktikan tenant context dibangun ulang dan tenant reassignment ditolak. Skenario revokasi lain belum dibuktikan melalui POST update endpoint.
- Ini adalah gap bukti test lifecycle; belum disimpulkan sebagai bypass runtime. Pekerjaan lanjutan yang aman adalah menambah HTTP regression test pada file test milik Agent 3 setelah jalur CI dapat menjalankannya.

### 3.3 Policy dan service milik Agent 2

- Pada branch Web Guru aktif, `QuestionPolicy.php` blob `81e3cb6c82f1a2f5e15deacf8b8ea50f8a494566` masih memakai `hasRole('GURU')`.
- `ExamPolicy.php` blob `839df41c82a5b45706072d60431706ca5861cc6c` masih memuat jalur KAMAD dan pemeriksaan `hasRole()`; ini bertentangan dengan master spec yang membekukan KAMAD dari portal CBT.
- Kedua policy tersebut adalah dependency/ownership Agent 2 dan tidak diubah.
- Branch Agent 2 P0 `work/agent2-p0-attempt-answer-scoring-20261122` saat refresh menunjuk SHA `7428617018815e964091b4261ea73dd5c4abb79f`. Di branch tersebut, ExamPolicy/QuestionPolicy menggunakan exact-role helper dan KAMAD tidak tampak pada daftar role yang diizinkan. Namun branch ini tidak terintegrasi ke branch Web Guru; compare dengan branch Agent 2 lifecycle menunjukkan sejarah divergen (149 ahead, 242 behind). Tidak boleh cherry-pick/merge atau menyalin file secara parsial tanpa persetujuan owner.
- `ExamSnapshotService` pada branch Agent 2 P0 blob `48e4fe658583a006770ca769aeb392711d386364` dan branch Web Guru blob `d0bfcd70b2777a3884c7f92b7c256f05492645ec` belum menunjukkan validasi status Question/QuestionVersion PUBLISHED. Phase 4B hanya melindungi jalur Livewire Guru; service otoritatif tetap perlu ditutup Agent 2.
- Issue #10 masih OPEN. Komentar koordinasi yang diperiksa berasal dari akun pemilik repository; tidak ada approval teknis lintas-owner terpisah yang dapat diverifikasi.

### 3.4 Dokumen P0 lama

Beberapa dokumen P0 yang ada pada repository mengacu pada branch/SHA `work/web-guru-foundation-20261122` atau SHA lain, bukan source HEAD aktif `d3c964be...`. Dokumen tersebut berguna sebagai histori, tetapi hasil test/backup/HEAD dari branch lain tidak dipindahkan statusnya menjadi bukti branch Web Guru aktif.

## 4. DESAIN — Prioritas berikutnya

1. Pulihkan bukti CI exact-SHA sebelum menambah patch runtime.
2. Setelah CI berjalan, lengkapi POST HTTP Livewire regression untuk revokasi permission, assignment, profile, user, dan role setelah component mount. Pertahankan assertion bahwa tidak ada side effect pada tabel Question/Exam.
3. Minta Agent 2 menyelesaikan/menyetujui exact-role policy dan invariant PUBLISHED pada `ExamSnapshotService`; jangan mengubah file owner Agent 2.
4. Setelah P0 dan kontrak backend terbukti, pilih satu gap Bank Soal/Exam Builder yang memiliki ownership Agent 3.
5. Monitoring, Penilaian, Hasil, Analytics, Import, Media, dan Proktor tetap ditahan sesuai dependency masing-masing.

## 5. IMPLEMENTASI

- Tidak ada source runtime, policy, service, migration, database, atau workflow yang diubah pada audit ini.
- Tidak ada backup source yang dibuat karena tidak ada file existing yang diubah.
- Satu tindakan operasional CI dilakukan: rerun job pada exact test SHA `616dc82f006e0c8327f5738857cd33d53ec9f071`, run ID `45047116468`.
- Tidak ada merge, rebase, cherry-pick, deployment, perubahan `main`, atau perubahan database.

## 6. PENGUJIAN DAN BUKTI CI

### 6.1 Exact SHA

| Item | Bukti | Hasil |
|---|---|---|
| Branch HEAD aktif | `d3c964be07ea8d687de3842bbf4f95e23325ebcc` | Terverifikasi |
| Source Phase 4B | `18830228b64e6718291119662a2907e0e2f416b8` | Terverifikasi |
| Test Phase 4B | `616dc82f006e0c8327f5738857cd33d53ec9f071` | Terverifikasi |
| Run #109 source SHA | `45047046983`, job `137283821316` | Failure, steps/log tidak tersedia |
| Run #110 test SHA attempt 1 | `45047116468`, job `137284031253` | Failure, steps/log tidak tersedia |
| Run #110 test SHA attempt 2 (rerun) | `45047116468`, job `137289470391` | Failure, steps kosong, log `BlobNotFound` |
| PHPUnit | Tidak ada bukti step berjalan | UNKNOWN / NOT VERIFIED |
| PHP lint / route:list | Tidak ada bukti step berjalan | NOT VERIFIED |
| MySQL 8/InnoDB disposable | Tidak ada bukti step berjalan | NOT RUN / NOT VERIFIED |
| Browser E2E / UAT | Tidak dijalankan | NOT RUN |

Rerun attempt 2 dimulai dan selesai pada 22 November 2026 17:20 UTC. Metadata run menunjukkan checkout SHA tetap `616dc82f006e0c8327f5738857cd33d53ec9f071`, tetapi job gagal sebelum step tersedia. Unduhan log mengembalikan `BlobNotFound`. Ini bukan bukti PHPUnit gagal dan bukan bukti PHPUnit lulus.

### 6.2 Lingkungan lokal

- Workspace checkout `/mnt/data/CBT_MADRASAH_RECONSTRUCTION` tidak tersedia pada sesi ini.
- Source tetap dapat diaudit dari GitHub; tidak dibuat ulang berdasarkan asumsi.
- PHP 8.3/Composer/PHPUnit/MySQL lokal tidak tersedia berdasarkan pemeriksaan P0 sebelumnya; tidak ada test lokal yang dijalankan dalam audit ini.

## 7. STATUS

**Web Guru: PARTIAL — NOT READY FOR OPERATIONAL EXAM.**  
**P0 CI: BLOCKED — workflow/job gagal sebelum steps, PHPUnit execution UNKNOWN.**  
**Phase 4B: implementasi source tersedia, tetapi runtime belum terverifikasi.**

Tidak ada klaim bahwa Web Guru siap operasional atau siap produksi.
