# CHECKPOINT — WEB GURU FOUNDATION
Tanggal: 22 November 2026

Repository: zane0184/cbt_november
Branch: work/web-guru-foundation-20261122
Baseline: 81b6c74d81d80bc483530802594f624fb4e0f3db
HEAD source: 55e8557c30f427db97624b8c4837e8dc3c9d8784
Checkpoint document commit before this update: 27639f38bdf1bba0075cbbefc852068aa8603fd9
Status: PARSIAL — implementasi awal tersedia; CI terbaru belum final.

## Selesai
- Branch terisolasi dibuat dari baseline yang memiliki bukti CI sukses.
- Backup routes/web.php dan AuthWebController.php dibuat sebelum perubahan.
- Route portal Guru terisolasi pada routes/teacher.php.
- Dashboard, Profil Guru, Pembelajaran, dan konteks Kurikulum & Akademik dibuat.
- Gate role tepat GURU, tenant server-side, dan profil Guru tenant-local diterapkan.
- Perubahan nomor kontak diaudit.
- Semester tidak dipilih otomatis.
- Penugasan aktif dibatasi tenant, relasi akademik, status, dan masa berlaku.
- Tidak ada migration atau perubahan database produksi.
- Test MySQL disposable ditulis.

## Bukti pengujian
Run 44982652810:
- PHP lint dan route registration PASS.
- Test step PASS: 7 test cases, 35 assertions, 7 warnings.
- InnoDB verification PASS.
- Cleanup disposable DB gagal pada run lama karena heredoc workflow lama.
- Run 44982777697 pada SHA 20012d21: seluruh 16 langkah PASS, termasuk cleanup; PHPUnit 7 test cases / 35 assertions / 7 warnings.
- Perbaikan workflow cleanup dan --display-warnings sudah masuk ke branch.
- Test tambahan assignment kedaluwarsa sudah ditambahkan.
- Run 44982836419 pada SHA 55e8557c masih berjalan; hasil HEAD terbaru belum boleh diklaim PASS.

## Blocker / tindak lanjut
1. Periksa run terbaru pada branch dan pastikan cleanup DB disposable PASS.
2. Periksa detail 7 warnings dari PHPUnit.
3. Jalankan test terbaru yang mencakup assignment kedaluwarsa.
4. Audit halaman Guru existing untuk Bank Soal, Asesmen, Ujian, Proktor, Monitoring, Penilaian, dan Hasil sebelum menu dihubungkan.
5. Browser visual test Light/Dark dan responsive.
6. Verifikasi CP resmi, Pembelajaran Mendalam, dan delapan dimensi melalui referensi resmi.

## Batas kerja
Tidak ada merge, PR, rebase, deployment, VPS, Android, Web Siswa, atau perubahan Web Admin selain route/auth integration yang sudah dibackup dan dicatat.

Laporan lengkap: docs/WEB_GURU_FOUNDATION_AUDIT_2026-11-22.md
Backup manifest: docs/backup/web-guru-foundation-20261122/MANIFEST.md


## PEMBARUAN CHECKPOINT — 22 NOVEMBER 2026 (AUDIT LANJUTAN)

HEAD branch aktual terakhir diperiksa: `7c8be7668bd039b56680f030a2f1778021d75eb1`.
Source test commit: `9afc0a0fd489227a67310edec67223457fe42139`; commit HEAD setelah source test hanya pembaruan manifest.

### Bukti CI
- `44982777697`: SUCCESS pada SHA `20012d216cdef9b80a47dc344e505125755e24f9`.
- `44982836419`: SUCCESS pada SHA `55e8557c30f427db97624b8c4837e8dc3c9d8784`; log checkout dan `git log -1` membuktikan SHA persis.
- `44983508195`: SUCCESS pada SHA `7d6d80cacc72def49fbe63731537dae773fa055f`, termasuk PHPUnit dan cleanup disposable DB.
- `44983639700`: status pending/queued saat checkpoint; SHA `9afc0a0fd489227a67310edec67223457fe42139`.
- Warning delapan test pada run 44982836419 berasal dari file `.env` yang tidak ada; workflow diperbaiki dengan empty `.env` sementara dan trap cleanup. Deprecation PHPUnit metadata doc-comment masih terpisah dan belum diperbaiki.

### Implementasi terbaru
- Hapus satu predicate `whereHas('rombel.gradeLevel.phase')` yang duplikat.
- Tambah test role revocation, soft-deleted account, dan tenant context mismatch.
- Perbarui menu: Bank Soal dan Asesmen/Ujian teridentifikasi sebagai fitur existing, tetapi link tetap nonaktif karena audit exact-role/multi-role komponen legacy belum lolos.
- Tidak ada migration, perubahan database produksi, atau perubahan pada branch Agent 1/2.

### Inventaris reuse
- Bank Soal: QuestionBank + QuestionController + QuestionPolicy.
- Asesmen: AssessmentController + AssessmentPolicy; UI web terintegrasi dalam ExamManagement.
- Ujian: ExamManagement + ExamController + ExamPolicy.
- Proktor: Admin ProctorAssignmentManagement; penugasan Guru bersifat sementara, tanpa portal Proktor permanen.
- Monitoring: MonitoringController dan ExamPolicy::monitor, akses per ujian.
- Penilaian: ResultDetail + ExamAttemptPolicy + ScoringController.
- Hasil/Laporan: ResultList + ReportController + ReportPolicy.
- Analytics: AnalyticsDashboard/ItemAnalysis + AnalyticsController/services.
- Ekspor: ReportController Excel/PDF dengan policy laporan dan scope data.
- Tidak ada modul duplikat dibuat.

### Pengujian yang belum selesai
- Menunggu run 44983639700.
- Audit strict multi-role dan stale-permission komponen legacy.
- Pengujian membership rombel multi-tahun dan edge-case hitungan siswa.
- Browser visual test desktop/tablet/mobile Light/Dark belum dijalankan.
- Adversarial Livewire hydration/property payload belum dijalankan.
- CP resmi, Pembelajaran Mendalam, dan delapan dimensi tetap belum diverifikasi.

### Langkah berikutnya
1. Periksa hasil run 44983639700 dan warning aktual.
2. Selesaikan test multi-role/stale permission pada fitur existing sebelum mengaktifkan link menu.
3. Perluas test membership siswa multi-tahun.
4. Jalankan browser test nyata jika browser test environment tersedia; catat per viewport dan tema.
5. Perbarui checkpoint dengan SHA dan bukti CI terbaru.

STATUS: PARSIAL — tidak siap produksi, tidak merge, tidak PR, tidak deploy.


## ADDENDUM CI TERBARU

- Tujuh warning run 44982777697 diidentifikasi satu per satu sebagai warning file `.env` hilang pada tujuh method test (lihat audit §9). Run 44982836419 menghasilkan delapan warning yang sama setelah test assignment kedaluwarsa ditambahkan.
- Run 44983639700 membuktikan warning `.env` tidak lagi muncul, tetapi dua test lifecycle baru gagal karena fixture: User::delete() melakukan hard delete yang ditolak FK, dan middleware tenant pada Livewire test harness memulihkan konteks sehingga test mismatch salah sasaran.
- Fixture diperbaiki: soft-delete disimulasikan dengan update kolom `deleted_at` pada DB disposable; mismatch diuji langsung pada TeacherPortalAccess.
- Run 44983824109 pada SHA d78992ba19cb8df3b4ba1e21d5bcf1d64b50b409 sedang berjalan; belum ada hasil PASS.
- Deprecation PHPUnit metadata doc-comment dari test legacy tetap terlihat dan belum diperbaiki.
- Backup blob SHA diverifikasi identik dengan sumber pra-perubahan; ukuran manifest memakai byte UTF-8.

STATUS: PARSIAL — menunggu run 44983824109 dan audit strict multi-role/stale permission. Tidak siap produksi.
