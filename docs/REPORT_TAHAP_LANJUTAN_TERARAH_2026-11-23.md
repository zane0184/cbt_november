# Laporan Tahap Lanjutan Terarah — 23 November 2026

Repository: `zane0184/cbt_november`  
Branch integrasi: `work/solo-web-cbt-completion-20261123`  
PR integrasi: #21 (OPEN / DRAFT / UNMERGED)

## 1. AUDIT — source dan bukti aktual

### 1.1 HEAD dan CI integrasi
- Sebelum patch terbaru, PR #21 berada pada SHA `6fcdc757fa04ca04a1871b1685e0c89d67ccfd13`.
- Workflow [#45148868236](https://github.com/zane0184/cbt_november/actions/runs/45148868236) pada SHA tersebut FAILURE.
- Focused WebAdminAuthTest + StudentWebLoginTest: 27 passed / 90 assertions.
- Full PHPUnit: **163 failed / 346 passed / 1,388 assertions**. Ini adalah hasil aktual, bukan status akhir proyek.
- Kegagalan paling banyak terkonsentrasi pada Phase7BManualReview (17), ExamSnapshot (15), Phase7CReopen (14), ExamSessionHardening (13), QuestionBank (8), SubmitLocking (8), AnswerKeyLeakage (7), ExamExecutionHardening (6), ExamManagementHardening (6), serta beberapa suite lain. Jangan menambal massal; kelompokkan akar masalah dan perbarui fixture/kontrak hanya bila memang sudah usang, atau perbaiki source bila kontrak salah.

### 1.2 Akar masalah autentikasi yang terbukti
Source `LoginIdentityResolver::resolve()` sebelumnya menolak login jika jumlah seluruh record Tenant bukan tepat satu:
`Tenant::withoutGlobalScopes()->count() !== 1`.
Tetapi bootstrap produksi menjalankan `SystemTenantSeeder`, yang memang membuat tenant reserved `SYSTEM_GLOBAL` selain tenant madrasah lokal. Akibatnya, instalasi valid dengan satu tenant lokal + satu SYSTEM_GLOBAL ditolak sebagai identitas ambigu; AuthTest menunjukkan HTTP 422 dengan pesan identitas/password tidak valid.
Ini bukan alasan untuk menghapus validasi tenant. Resolusi yang benar adalah memakai satu-satunya `LocalInstallationIdentity.tenant_id`, lalu memastikan tenant tersebut ada, aktif/tidak terhapus, dan bukan SYSTEM_GLOBAL.

### 1.3 Kurikulum — integrasi yang ada dan gap yang ditemukan
Kontrak desain `docs/DESAIN_KONTRAK_KURIKULUM_ASESMEN_WEB_2026-11-23.md` menetapkan satu kontrak backend untuk Admin, Guru, Siswa; Panca Cinta, prinsip/olah/kerangka Pembelajaran Mendalam, dan delapan dimensi profil lulusan sebagai registry bertipe/berversi; mapping melekat ke QuestionVersion; snapshot asesmen/soal immutable.
Source terkini menunjukkan:
- `TeacherCurriculumContextService` menyusun konteks versi kurikulum, CP terverifikasi, Panca Cinta, PM, delapan dimensi, fase/kelas/mapel untuk assignment.
- `AssessmentCurriculumMappingService` menghubungkan assignment, CP, TP/ATP, materi/topik dan curriculum context.
- `QuestionVersionCurriculumMappingService` memetakan revisi soal ke CP/TP/ATP dan framework structure mappings.
- `ExamSnapshotService` menangkap assessment context dan question curriculum context ke snapshot immutable, dengan pemeriksaan provenance.
- Namun `CurriculumAchievementService` dan `CurriculumAnalyticsController` masih memakai keluarga model legacy `LearningOutcome`, `LearningObjective`, `LearningPath` untuk kalkulasi/laporan CP/TP/ATP. Belum ada bukti bahwa seluruh output laporan membaca satu versi/context snapshot kanonis yang sama dengan mapping QuestionVersion dan Exam snapshot.
Kesimpulan: kerangka integrasi sudah ada, tetapi alur ujung-ke-ujung hingga capaian/laporan historis belum terbukti satu kontrak. Ini backlog prioritas; jangan membuat tiga modul kurikulum terpisah atau mengganti model legacy tanpa rencana migrasi data dan bukti kompatibilitas.

## 2. DESAIN — keputusan owner ditegaskan kembali

Kurikulum Merdeka + Kurikulum Berbasis Cinta/Panca Cinta + Pembelajaran Mendalam adalah **satu ekosistem akademik terintegrasi**, bukan tiga kurikulum, tiga database, atau tiga menu mandiri.

Makna perannya:
- Kurikulum Merdeka: kerangka akademik dan struktur CP/TP/ATP, fase, kelas, mapel.
- Panca Cinta: nilai/topik lintas pembelajaran yang diintegrasikan ke perencanaan, materi, aktivitas, soal/asesmen bila relevan, dan refleksi.
- Pembelajaran Mendalam: pendekatan pedagogis dan kerangka proses pembelajaran; bukan kurikulum/jenis ujian atau enum fase.
- Delapan dimensi profil lulusan: lensa/hasil profil yang dipetakan bila terukur; bukan pengganti CP/TP.

Satu alur data yang wajib konsisten:
`sumber resmi + versi` → `registrasi kurikulum instalasi` → `academic year/semester/phase/grade/subject` → `TeacherSubjectAssignment + pinned curriculum context` → `CP resmi` → `TP/ATP Guru` → `materi/topik` → `Panca Cinta + PM + dimensi profil` → `QuestionVersion mappings` → `Assessment context` → `immutable Exam/Question snapshots` → `Attempt/Answer/Score` → `achievement + report historis`.

Aturan:
1. Referensi resmi bersifat provenance/versioned; CP tidak boleh direka atau diubah oleh Admin/Guru.
2. Admin mengelola registrasi/periode/master akademik sesuai kewenangan, bukan menulis TP/ATP, soal, ujian, token, Attempt, jawaban atau nilai.
3. Guru mengembangkan TP/ATP dan materi sesuai assignment serta versi kurikulum yang dipatok.
4. Pemetaan Panca Cinta/PM/dimensi profil harus tervalidasi terhadap tipe node; tidak boleh salah memakai node fase/mapel.
5. QuestionVersion dan immutable snapshot menyimpan referensi + label/version/provenance agar laporan historis tidak berubah ketika master diperbarui.
6. Siswa hanya menerima metadata yang memang dipublikasikan dan diotorisasi; tidak menerima kunci jawaban/rubrik privat.
7. Laporan harus menautkan hasil ke konteks kurikulum snapshot dan histori akademik yang sama. Legacy achievement model perlu dipetakan/migrasikan bertahap; jangan membuat dua sumber kebenaran permanen.
8. Jangan menyatakan selesai hanya karena istilah Panca Cinta/PM/8 dimensi tampil di UI.

## 3. IMPLEMENTASI — patch aktual tahap ini

File runtime yang diubah:
- `backend-laravel/app/Services/LoginIdentityResolver.php`
  - Backup byte-exact: `backend-laravel/docs/backup/solo-web-cbt-p0-integration-2026-11-23/LoginIdentityResolver.php.before-system-tenant-login-resolution`.
  - Blob source sebelum patch: `3d1a1d8294453f8d53fd15d5d7910e22478790f6`.
  - Perubahan: tidak lagi menghitung semua tenant sebagai syarat tepat satu. Resolver memeriksa tepat satu LocalInstallationIdentity, lalu memverifikasi tenant lokal yang dirujuk ada, tidak soft-deleted, dan bukan SYSTEM_GLOBAL.
  - Commit patch: `2f1becace2e2d0fdb5891f586426b774f3eaea08`.
- `backend-laravel/tests/Feature/AuthTest.php`
  - Backup byte-exact: `backend-laravel/docs/backup/solo-web-cbt-p0-integration-2026-11-23/AuthTest.php.before-system-tenant-login-resolution`.
  - Blob source sebelum patch: `803f409f48b544d947039d0879d7e56c4c118336`.
  - Regression test: login tetap sukses ketika SystemTenantSeeder membuat SYSTEM_GLOBAL selain tenant madrasah.
  - Commit test: `d70270d55de8b5e0bfd8429ce7b0cae3c4bca1c1`.

Tidak ada migration produksi, DB produksi, VPS, PR merge, deployment, atau Android build yang dilakukan.

## 4. PENGUJIAN — status

- Backup resolver dan AuthTest diverifikasi byte-exact sebelum patch.
- Workflow baru pada exact SHA `2f1becace2e2d0fdb5891f586426b774f3eaea08` terpicu: [#45149995187](https://github.com/zane0184/cbt_november/actions/runs/45149995187). Status harus diperiksa ulang sebelum klaim PASS.
- Regression test ditambahkan, tetapi belum ada hasil CI yang membuktikan test baru lulus saat laporan ini dibuat.
- Full suite pada SHA sebelumnya gagal 163/346; belum boleh disebut lulus.
- CI Agent 2 P0 terisolasi sebelumnya pernah PASS pada [#45145411333](https://github.com/zane0184/cbt_november/actions/runs/45145411333) di branch Agent 2, tetapi bukti itu tidak menggantikan CI integrasi PR #21.

## 5. BACKLOG TERARAH — urutan wajib

### P0-A — Integrasi backend auth/session/fencing
- [ ] Verifikasi CI exact patch resolver dan test SYSTEM_GLOBAL.
- [ ] Triage suite auth, role, session binding, lease, heartbeat, receipt, token, expiry.
- [ ] Jalankan two-connection concurrency pada branch integrasi; wajib PASS, bukan SKIPPED.
- [ ] Pertahankan exact-one role, local installation boundary, same Attempt/session resume, stale fence rejection.
- [ ] Pastikan source backend integrasi cocok dengan runner Web Siswa PR #13; jangan ulang fitur runner yang sudah ada.

### P0-B — Web Siswa real integration dan mobile-first
- [ ] Reconcile runner terbaru PR #13; jangan pakai runner lama.
- [ ] Browser-to-real-Laravel E2E pada disposable MySQL 8: login, start/resume, token, heartbeat, save/retry/receipt, stale fence, media, offline/reconnect, submit/expiry race.
- [ ] Android Chrome/iOS Safari fisik; virtual keyboard, scroll/focus, rotation, timer, touch target, koneksi lemah.
- [ ] Anti-copy/paste hanya deterrent; visibility/focus event audit signal, tidak auto-submit/punish.

### P1 — Guru dan asesmen
- [ ] Perbaiki regression fixture yang belum memenuhi canonical assignment; jangan melemahkan assignment guard.
- [ ] Selaraskan QuestionBank, QuestionVersion, Assessment, snapshot, scoring/reopen/manual review dengan satu kontrak.
- [ ] DOCX import tetap menunggu parser/security/staging contract dan test.

### P2 — Admin Madrasah
- [ ] Audit ADM-01–ADM-23 terhadap source aktual; pertahankan batas Admin administratif dan non-CBT.
- [ ] Jangan integrasikan PR #18 room/session/multi-Proktor yang bertentangan dengan keputusan owner.

### P3 — Satu ekosistem kurikulum dan laporan
- [ ] Audit model legacy achievement vs canonical MultiLearningOutcome/TenantLearningOutcome dan mapping QuestionVersion.
- [ ] Tetapkan canonical read model/report contract tanpa menghilangkan histori lama.
- [ ] Pastikan laporan CP/TP/ATP, nilai, Panca Cinta/PM/dimensi profil mengambil referensi dari snapshot/version yang sama.
- [ ] Test end-to-end assignment → CP/TP/ATP → material/topic → framework mappings → QuestionVersion → Assessment → Exam snapshot → Score → historical report.
- [ ] Pastikan perubahan versi sumber tidak mengubah snapshot/laporan historis.

### P4 — acceptance dan distribusi
- [ ] Full PHPUnit exact SHA PASS.
- [ ] Browser E2E ketiga portal, fresh install/upgrade disposable MySQL, repeat seeder, rollback guard.
- [ ] Security, tenant isolation per installation, concurrency, idempotency, load, recovery, backup/restore.
- [ ] UAT Admin/Guru/Siswa.
- [ ] Deployment VPS hanya setelah rencana, backup/preflight dan persetujuan.
- [ ] ZIP generik hanya setelah seluruh gate lulus. Android native/Web Super Admin tetap ditunda sampai Juni 2027.

## 6. STATUS AKHIR
Belum siap produksi, belum siap ujian operasional, belum siap distribusi. PR #21 tetap Draft/unmerged. Laporan ini adalah checkpoint kemajuan, bukan pernyataan proyek selesai.
