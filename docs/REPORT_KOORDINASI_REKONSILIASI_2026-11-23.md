# Laporan Koordinasi dan Rekonsiliasi CBT Madrasah — 23 November 2026

Repository: `zane0184/cbt_november`  
Branch eksekusi: `work/solo-web-cbt-completion-20261123`  
PR integrasi: [#21](https://github.com/zane0184/cbt_november/pull/21)  

## AUDIT

### HEAD dan CI yang terverifikasi saat laporan dibuat
- PR #21 masih OPEN / DRAFT / UNMERGED; base `work/solo-web-cbt-ci-base-20261123`, head `work/solo-web-cbt-completion-20261123`.
- HEAD PR #21 saat verifikasi: `b34904ce0f087cda2c4cbcd8d7c75ea824840177`.
- Run workflow terdekat pada source SHA tersebut:
  - [Tenant Backend CI #45154028522](https://github.com/zane0184/cbt_november/actions/runs/45154028522) — sedang berjalan pada pemeriksaan terakhir, job MySQL masih tahap inisialisasi container.
  - [Agent 4 audit #45154028554](https://github.com/zane0184/cbt_november/actions/runs/45154028554) — pending pada pemeriksaan terakhir.
- Run sebelumnya pada SHA `f5dbc66b67982c28afc36f65cd4bb4b5e9609611` dibatalkan. Run yang lebih lama juga mengalami pembatalan karena commit lanjutan. Tidak ada PASS yang boleh diklaim untuk HEAD terbaru hingga run exact SHA selesai.

### Source diperiksa langsung pada branch integrasi
- `LoginIdentityResolver.php` blob `755d46bc780142919304ea3a9069e7e83266a912`: menggunakan satu `LocalInstallationIdentity`, memvalidasi satu tenant lokal aktif yang sama, mengecualikan SYSTEM_GLOBAL, dan hanya menerima exact-one role ADMIN_MADRASAH/GURU/SISWA.
- `ExamAttemptController.php` blob `4668ff26bd0e897f9ab9d4976491ed9665e1ae37`: sudah memakai `AttemptDeviceLeaseService`, auth-session binding, dan logika token wajib hanya bila `require_token=true`.
- `ExamAnswerController.php` blob `603476d7d871bd1a45ccc0b19d34e152a29a410d`: memeriksa auth-session dan fence, row-lock Attempt sebelum Answer, idempotency receipt dan `client_request_id` pada respons baru/replay.
- `CurriculumAnalyticsController.php` blob `2fe90e6a3bbf398be69260d053e09471bb886396`: masih membaca `TpAchievement`, `AtpAchievement`, `CpAchievement` dengan relasi model legacy `LearningObjective`, `LearningPath`, `LearningOutcome`. Belum ditemukan bukti bahwa data output laporan historis berasal dari konteks snapshot kurikulum kanonis yang sama.
- `ExamQuestionSnapshot.php` blob `fc6da0c554cdd39cd7c615ef7dc08b4541798dd7`: sudah menyediakan kolom `curriculum_context_snapshot` sebagai array. Ini belum membuktikan seluruh alur score/report mengonsumsi snapshot tersebut.
- Dashboard Admin saat ini memuat metrik pengguna/Guru/Siswa/rombel/mapel dan hitungan penugasan proktor historis/aktif; ini bukan audit lengkap ADM-01–ADM-23.

### Spesifikasi utama dibaca dari GitHub
- Admin: `docs/MASTER_SPESIFIKASI_FITUR_WEB_ADMIN_MADRASAH_2026-11-22.md` di main, blob `2d3f8d53417c2387b7d8a1c53a0128880c8d8500` (343 baris).
- Guru: `docs/MASTER_SPESIFIKASI_FITUR_WEB_GURU_2026-11-22.md` di main, blob `4d5e0b6f422b0f93005d6bebdabf4925c95f8df1` (868 baris).
- Siswa: `docs/MASTER_SPESIFIKASI_FITUR_WEB_SISWA_CBT_MADRASAH_2026-11-22.md` di branch Agent 4, blob `551abdf606265b12419b8030c6d4ebcfa1e063f9` (755 baris).
- Issue #10 tetap dibaca sebagai thread historis; koreksi terbaru Issue #20 dan keputusan pada branch integrasi mengikat ketika berkonflik dengan desain lama ExamSession.

## DESAIN — batas keputusan yang ditegakkan
- Portal aktif: Admin Madrasah, Guru, Siswa.
- Role Operator dan Kamad tidak aktif; Proktor bukan role permanen, hanya capability/penugasan tambahan di portal Guru sesuai keputusan terbaru. Jangan menghidupkan kembali model ruang/sesi operasional atau memberi capability monitoring bila keputusan owner terbaru melarangnya.
- Setiap madrasah memakai satu instalasi dan satu database akademik sendiri. Tidak ada pertukaran akademik lintas madrasah/shared academic multi-tenant. Jangan melakukan penghapusan massal `tenant_id`/scope sebelum dependency, foreign key, historis dan System Global curriculum reference diaudit.
- Android native dan Web Super Admin ditunda sampai Juni 2027. Jangan memasukkan implementasi keduanya dalam backlog release Web saat ini.
- Kurikulum Merdeka, KBC/Panca Cinta, dan Pembelajaran Mendalam adalah satu ekosistem pembelajaran/asesmen; delapan dimensi profil lulusan menjadi kerangka pendukung terhubung, bukan modul/kurikulum terpisah. Jejak harus utuh hingga question version, assessment, immutable snapshot, score, dan laporan historis.
- Taksonomi asesmen fleksibel dan dapat berkembang; jangan mengunci nama UTS/UAS/UM/TKA menjadi enum tetap. DOCX menjadi prioritas import soal; XLSX question import tidak boleh dihidupkan lagi. SHORT_ANSWER tetap blocked sampai accepted-answer, snapshot privat, dan scoring dibuktikan end-to-end.

## IMPLEMENTASI
- Tidak ada perubahan source runtime atau migration pada tahap rekonsiliasi ini.
- Tidak ada file existing yang diubah; karena itu tidak ada backup source baru yang diperlukan.
- Tidak ada branch merge, PR merge, database produksi, VPS, atau ZIP distribusi yang disentuh.
- Laporan ini adalah checkpoint rekonsiliasi, bukan klaim bahwa audit semua menu atau implementasi telah selesai.

## PENGUJIAN
- Tidak menjalankan test dari sesi ini. Status uji berasal dari run GitHub Actions di atas dan masih pending/in-progress; tunggu hasil exact SHA.
- Source statis dibaca pada blob SHA yang dicatat; pembacaan source bukan pengganti PHPUnit, Browser E2E, MySQL concurrency, UAT atau runtime.

## Backlog berikutnya — satu jalur terarah
1. Ambil hasil/log workflow #45154028522 dan workflow Agent 4 #45154028554 pada HEAD `b34904ce0f087cda2c4cbcd8d7c75ea824840177`; prioritaskan failure pertama pada full PHPUnit dan akar penyebab, bukan patch massal.
2. Selesaikan matriks ADM-01–ADM-23: route, Livewire, controller/service, model, policy, schema, test, exact blob, status dan bukti. Jangan mengklaim spesifikasi Admin selesai dari dashboard saja.
3. Rekonsiliasi runner Agent 4 terbaru ke PR #21 hanya setelah membandingkan exact blob/patch dan backup; jangan ulang IndexedDB/mobile-first/receipt verifier yang sudah ada di PR #13.
4. Tuntaskan laporan kurikulum: bandingkan read-model legacy achievement dengan mapping QuestionVersion dan `curriculum_context_snapshot`; desain adapter/read model kompatibilitas historis sebelum perubahan schema. Tambahkan end-to-end test assignment → CP/TP/ATP → materi/topik → Panca Cinta/PM/8 dimensi → QuestionVersion → Assessment → Exam snapshot → Score → laporan historis.
5. Tutup integration browser-to-real-Laravel, media, receipt, token optional, fencing multi-device/concurrency dan recovery dengan disposable MySQL 8.
6. Lengkapi administrasi Admin yang masih missing setelah matriks memberi bukti spesifik; tidak menambahkan hak admin atas ujian, peserta, Attempt, jawaban atau nilai.
7. Fresh-install, upgrade disposable, backup/restore, security/stability, UAT tiga portal. Baru setelah semua gate dan approval: paket ZIP satu instalasi/database per madrasah.

## Status
**NOT READY FOR OPERATIONAL EXAM / NOT READY FOR PRODUCTION / NOT READY FOR DISTRIBUTION.** PR #21 tetap Draft/unmerged. Tidak ada izin merge, migration produksi, deployment, atau perubahan database produksi yang diberikan.
