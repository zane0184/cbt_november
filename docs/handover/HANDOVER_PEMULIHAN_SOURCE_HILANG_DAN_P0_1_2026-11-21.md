# HANDOVER AGENT BERIKUTNYA — PEMULIHAN SOURCE CBT MADRASAH

Tanggal: 21 November 2026  
Repositori: `zane0184/cbt_november`  
Branch dokumentasi: `docs/project-continuity-policy-20261121`  
Status dokumen: instruksi handover; bukan bukti bahwa source telah dipulihkan.

## 1. Mandat
Lanjutkan rekonstruksi CBT Madrasah dengan urutan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN. Jangan menganggap laporan lama sebagai bukti source saat ini. Source aktual, hash, dan hasil tes menjadi dasar kebenaran. Seluruh pekerjaan harus dicatat dan di-commit ke GitHub pada branch kerja terpisah; jangan merge tanpa persetujuan pengguna.

## 2. Keputusan arsitektur yang wajib dipertahankan
- Backend Laravel 13 / PHP 8.3; MySQL 8+ InnoDB.
- Web Blade + Livewire; Android Kotlin + Jetpack Compose + Room + WorkManager + Retrofit.
- Modular monolith, multi-tenant. Satu madrasah = satu instalasi Web + database operasional independen; tenant scoping tetap wajib.
- Admin Madrasah mengurus administrasi/master data/monitoring/dukungan; Guru mengelola bank soal dan membuat/mengatur/menerbitkan ujian sesuai assignment/permission tanpa approval Admin; Siswa hanya data sendiri; Proktor hanya penugasan sementara per ujian; Super Admin/Publisher control plane terpisah ditunda sampai Juni 2027.
- Kurikulum Merdeka, Kurikulum Berbasis Cinta/Panca Cinta, Pembelajaran Mendalam, dan kurikulum berikutnya terintegrasi lintas akademik, pembelajaran, asesmen, bank soal, ujian, laporan, semua role, dengan versioning dan histori.
- Android native adalah target akhir untuk peserta Android, tetapi pekerjaan/source Android ditunda sampai Juni 2027. Jangan mengubah source Android atau menjalankan Gradle/Android build sekarang. Ingatkan pengguna membawa kembali Cubot saat pengujian Android Studio/ADB dimulai.
- Token dibuat setelah Guru publish; token dapat dilihat sebelum jadwal, ujian hanya mulai sesuai jadwal; waktu server adalah acuan resmi.
- Attempt, Answer, Snapshot, Score, audit, sinkronisasi offline/recovery harus konsisten. Jangan auto-replay jawaban stale.
- Force Submit dan Reset Attempt berbeda; operasi sensitif harus berizin dan tercatat. Kamera bukan syarat CBT standar.
- Device Lease/Fencing tetap HOLD / SCHEMA OR SOURCE UNVERIFIED / NOT PRODUCTION READY sampai bukti integrasi E2E, multi-device, concurrency/race, idempotency, tenant isolation, recovery, dan parity tersedia. Jangan aktifkan feature flag.

## 3. Status audit terakhir
### A02 read-only
Candidate GitHub: `zane0184/cbt_november`, branch `fix/web-portal-usability-rbac-20261120`, HEAD `ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2`; PR #5 masih Open/Draft/Unmerged pada audit. Jangan asumsikan status saat membaca dokumen ini; verifikasi ulang HEAD dan PR.

CI historis run `44755020408` pada SHA tersebut PASS: PHP 8.3.37, MySQL 8.0.46 disposable, PHPUnit 480 tests / 1,942 assertions. Itu bukan bukti P0.1 atau kesiapan produksi.

Temuan A02:
1. Migration `2026_11_05_190008_create_question_multi_curriculum_mappings_table.php` mengandung `Schema::dropIfExists()` pada `up()` sebelum create — CRITICAL, berpotensi menghapus tabel/data jika tabel telah ada ketika migration pending.
2. Relasi CBT belum seluruhnya punya composite tenant FK.
3. Master-data API memiliki celah authorization per-action/policy yang belum dibuktikan eksploitasi HTTP.
4. Device Lease pada HEAD A02 hanya schema/parsial; runtime end-to-end tidak terbukti.
5. Transaksi/idempotensi exam, token rotation, reset/force submit/time extension dan race perlu audit.

### A02.1 remediation design yang telah disetujui
P0 urutan: P0.1 migration 190008 data-preserving; P0.2 tenant CBT relational integrity; P0.3 master-data API authorization. Pengguna hanya menyetujui mulai P0.1. Jangan mulai P0.2/P0.3 tanpa persetujuan terpisah.
- Migration pending + tabel sudah ada: PREFLIGHT HALT; jangan drop, overwrite, atau auto-recreate.
- Historical migrations jangan diedit kecuali terbukti belum pernah dirilis/dijalankan; prefer safe forward migration.
- Tenant ownership ambigu/unproven: quarantine, jangan backfill paksa.
- Pertahankan seluruh histori Attempt/Answer/Snapshot/Score/Achievement.
- Tidak ada production DB/migration.

### P0.1 blocker yang pernah terverifikasi
Runtime sesi audit tidak memiliki Composer/MySQL/PDO driver dan ekstensi yang diperlukan. Karena itu P0.1 belum dipatch dan belum dites di disposable MySQL 8. Jangan mengklaim patch/test sudah ada. Verifikasi ulang runtime saat mulai.

## 4. ZIP yang sudah diperiksa langsung
File upload: `CBT_MADRASAH_HANDOVER_AGENT_BARU_2026-11-18(02).zip`  
Materialized path saat audit: `/mnt/data/cbt_user_supplied/CBT_MADRASAH_HANDOVER_AGENT_BARU_2026-11-18(02).zip`  
Ukuran 137,529,248 byte; 12,023 entri; `ZipFile.testzip()` menghasilkan `None` (integritas ZIP PASS).

Path source migration ditemukan:
`CBT_MADRASAH_HANDOVER_AGENT_BARU_2026-11-18/01_WORKSPACE/CBT_MADRASAH_RECONSTRUCTION/01_SOURCE_TENANT/backend-laravel/database/migrations/2026_11_05_190008_create_question_multi_curriculum_mappings_table.php`

Pemeriksaan nama path exact pada ZIP tersebut:
- `CurriculumIntegrityPreflightCommand.php`: NOT FOUND.
- `2026_11_06_200002_enforce_curriculum_integrity_not_null.php`: NOT FOUND.
- `RegisteredDevice.php`: NOT FOUND.
- `DeviceLease.php`: NOT FOUND.
- `DeviceLeaseService.php`: NOT FOUND.
- `DeviceLeaseController.php`: NOT FOUND.
- Migration `2026_11_05_190008...`: FOUND.

Temuan ini hanya berlaku untuk ZIP `(02)` yang diperiksa; jangan generalisasi ke semua arsip/branch sebelum pencarian penuh.

## 5. Laporan historis yang tersedia — bukan source code
- Tahap 4B melaporkan `backend-laravel/app/Console/Commands/CurriculumIntegrityPreflightCommand.php`, read-only `curriculum:integrity-preflight`, lint dan artisan list PASS; belum dijalankan terhadap DB.
- Tahap 4D melaporkan migration `2026_11_06_200002_enforce_curriculum_integrity_not_null.php`, forward-only, null preflight guard, trigger SQLite/MySQL; PHP lint dan SQLite in-memory trigger PASS, MySQL runtime belum diuji, tidak dijalankan pada DB proyek.
- Tahap 5P-1 melaporkan model `RegisteredDevice`, `DeviceLease`, perubahan `ExamAttempt`, dan `DeviceLeaseService`; baru fondasi, belum controller/Android integration dan integration tests.
- Tahap 5P backend+Android melaporkan controller start/resume/answer/submit/heartbeat/release, token hash, fencing generation, Android encrypted token storage/interceptor/repository/worker. PHP lint/route list PASS; Android compile, PHPUnit, MySQL race, perangkat fisik, tenant runtime, recovery belum diverifikasi.
- Tahap 5B melaporkan file backend lease pada fase lain. Seluruh laporan adalah bukti histori pekerjaan yang diklaim, bukan bukti file itu ada di ZIP `(02)` atau HEAD GitHub saat ini.

## 6. Target pemulihan source — jangan langsung menulis ulang
Lakukan pencarian sumber lengkap sebelum rekonstruksi:
1. ZIP baseline dan semua ZIP handover/varian, seluruh folder/subfolder, nested ZIP, serta manifest.
2. Semua workspace rekonstruksi dan folder backup historis, termasuk nama path yang dilaporkan dalam checkpoint.
3. Seluruh branch/commit/PR GitHub, bukan hanya default branch. Gunakan GitHub search/fetch dan tree recursive; periksa blob SHA serta file contents.
4. Project Files, Library, dan laporan/checkpoint. Laporan dapat memberi spesifikasi tetapi tidak menggantikan source.
5. Catat exact path, archive/branch, SHA-256, ukuran, versi, dan apakah file raw atau hanya disebut di laporan.

Target source yang hilang/harus dilacak:
- `backend-laravel/app/Console/Commands/CurriculumIntegrityPreflightCommand.php`
- `backend-laravel/database/migrations/2026_11_06_200002_enforce_curriculum_integrity_not_null.php`
- `backend-laravel/app/Models/RegisteredDevice.php`
- `backend-laravel/app/Models/DeviceLease.php`
- `backend-laravel/app/Services/Exam/DeviceLeaseService.php` (konfirmasi path historis)
- `backend-laravel/app/Http/Controllers/Api/V1/DeviceLeaseController.php` (konfirmasi path historis)
- perubahan `ExamAttempt`, `ExamAttemptController`, `ExamAnswerController`, `MonitoringController`, `routes/api.php`, scheduler, migration terkait, tests, Android source bila ditemukan — Android hanya arsipkan/audit, jangan ubah sebelum Juni 2027.

Status tiap target wajib salah satu: RECOVERED EXACT (byte-identical hash proven), RECOVERED VERSIONED (raw source found, hash/version recorded), RECONSTRUCTED (new implementation from reports/design, clearly labelled), NOT FOUND. Jangan menyebut rekonstruksi sebagai pemulihan asli.

## 7. Aturan implementasi dan backup
- Bekerja hanya di workspace rekonstruksi, jangan mengubah ZIP asli.
- Sebelum perubahan: source SHA, git status, backup byte-identical di folder backup terpisah, manifest SHA-256.
- Tidak boleh overwrite source yang tidak dipahami. Periksa diff dan jalur dependensi.
- Jangan jalankan migration produksi. Gunakan disposable MySQL 8/InnoDB yang terisolasi; bila runtime tidak tersedia, berhenti dan dokumentasikan blocker.
- Tidak menjalankan Gradle via terminal. Android source tetap beku hingga Juni 2027.
- P0.1 saja sesuai persetujuan. Jangan sentuh P0.2/P0.3.

## 8. Acceptance criteria P0.1
- `up()` tidak menghapus tabel/data yang telah ada.
- Preflight mendeteksi kombinasi migration ledger/tabel/kolom yang tidak konsisten dan berhenti tanpa DDL.
- Instalasi bersih dapat menjalankan migration dengan aman.
- Simulasi migration pending + tabel existing mengembalikan HALT dan data checksum/count tetap sama.
- Simulasi migration ledger applied dan schema valid tidak merusak data.
- Uji MySQL 8/InnoDB disposable, lint, PHPUnit/feature tests yang relevan, diff check.
- Backup dan rollback/forward-fix plan terdokumentasi.
- Tidak ada perubahan DB produksi.

## 9. Pelaporan wajib setiap tahap
Pisahkan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN. Sertakan commit/branch, file berubah, backup path+hash, test command dan hasil aktual, kendala, residual risks, checkpoint, serta next action. Tidak ada klaim PASS tanpa log/bukti.

## 10. Status saat handover dibuat
Dokumen ini dibuat setelah pemeriksaan langsung ZIP `(02)`. Belum ada source yang dipulihkan atau direkonstruksi pada tahap ini. P0.1 masih menunggu sumber/runtime yang sesuai. Agent berikutnya wajib melanjutkan pemeriksaan dan pemulihan secara bertahap, tanpa mengulang pertanyaan ke pengguna tentang file yang sudah tersedia.
