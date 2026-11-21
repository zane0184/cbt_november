# PROMPT HANDOVER AGENT BARU — CBT MADRASAH
Tanggal: 21 November 2026

## Mandat
Lanjutkan proyek CBT Madrasah dari source aktual dan checkpoint, bukan dari asumsi. Bahasa Indonesia. Pisahkan setiap tahap menjadi AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN. Jangan klaim PASS/selesai/siap produksi tanpa bukti. Jangan minta ulang keputusan atau file yang sudah tersedia. Setiap pekerjaan substansial wajib dilaporkan dan dicatat di GitHub, termasuk blocker dan handover.

## Repository dan posisi terakhir
- Repository: zane0184/cbt_november
- Branch sumber yang diaudit: fix/web-portal-usability-rbac-20261120
- HEAD sumber terakhir terverifikasi: ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2
- Branch dokumentasi kontinuitas: docs/project-continuity-policy-20261121
- Checkpoint kontinuitas: docs/handover/CHECKPOINT_KONTINUITAS_DAN_ATURAN_HANDOVER_2026-11-21.md
- PR #5 terakhir tercatat Open/Draft/Unmerged. Fetch ulang status sebelum bertindak.
- Status aktif: P0.1 DISETUJUI, IMPLEMENTASI BELUM DIMULAI, BLOCKED oleh kesiapan workspace/runtime pada pemeriksaan sebelumnya.

## Arsitektur/keputusan yang wajib dipertahankan
- Backend Laravel 13, PHP 8.3, MySQL 8+ InnoDB; Web Blade + Livewire.
- Android Kotlin, Jetpack Compose, Room, WorkManager, Retrofit; Modular Monolith multi-tenant.
- Satu madrasah = satu instalasi CBT Web dan satu database operasional independen. Domain, secrets, storage, backup, lifecycle independen; tenant_id tetap untuk validasi/isolasi internal.
- Portal operasional Admin Madrasah dan Guru. Super Admin/Publisher control plane terpisah, ditunda sampai Juni 2027.
- Proktor bukan portal permanen; hanya assignment sementara per ujian.
- Guru mengelola bank soal, membuat/mengatur/menerbitkan ujian tanpa approval Admin sesuai assignment, permission, scope. Admin mengelola administrasi, master data, sinkronisasi, monitoring, dukungan teknis. Siswa hanya data/ujian sendiri.
- Semua backend wajib validasi tenant, role, permission, assignment, scope.
- Kurikulum Merdeka, Kurikulum Berbasis Cinta/Panca Cinta, Pembelajaran Mendalam, dan kurikulum berikutnya terintegrasi di akademik, pembelajaran, asesmen, bank soal, ujian, laporan, seluruh role; histori dan versi kurikulum dipertahankan.
- Android native peserta Android adalah target akhir, tetapi implementasi/build/test Android ditunda sampai Juni 2027. Interim peserta Web; iOS sementara Web. Jangan ubah source Android selama penundaan.
- Token otomatis dibuat setelah Guru publish; token dapat dilihat sebelum jadwal, ujian hanya mulai sesuai jadwal. Waktu server acuan.
- Attempt, Answer, Score, Snapshot, Audit harus konsisten. Offline/sync aman dari replay stale, duplikasi, kehilangan data.
- Reset Attempt dan Force Submit adalah operasi/permission terpisah. Kamera bukan syarat CBT standar.
- Device Lease/Fencing tetap HOLD/SCHEMA ONLY/RUNTIME NOT VERIFIED. Jangan aktifkan atau rekonstruksi dari asumsi; perlu E2E, multi-device, concurrency, idempotency, tenant mismatch, recovery, Android parity.
- Jangan migration eksperimental di DB produksi. Jangan jalankan Gradle via terminal; Android build hanya Android Studio.

## Bukti audit A02
HEAD yang diaudit: ade5daa6cf853c164c4f51ec6e25ca68ce40b4f2.
CI historis pada SHA tersebut: Tenant Backend CI run 44755020408 SUCCESS dan Web deployment ZIP workflow 44755020451 SUCCESS. Environment PHP 8.3.37, MySQL 8.0.46 disposable DB; PHPUnit 480 tests/1.942 assertions. Fresh install installer locked PASS; test dua koneksi publish/snapshot serialization PASS. Ini bukti historis pada SHA tersebut, bukan bukti P0.1, semua concurrency, legacy upgrade, Device Lease runtime, atau production readiness.

Temuan P0:
1. Migration backend-laravel/database/migrations/2026_11_05_190008_create_question_multi_curriculum_mappings_table.php memiliki Schema::dropIfExists('question_multi_curriculum_mappings') di up(); migration pending + tabel/data sudah ada berisiko kehilangan data. Git blob SHA-1 pada audit: d0da49dd973959d05325da1068d18a69a5bcbdc2 (bukan SHA-256).
2. Migration 2026_11_05_200001_fase1c_multi_curriculum_integrity.php terkait. Migration 2026_11_06_200002_enforce_curriculum_integrity_not_null.php dan CurriculumIntegrityPreflightCommand.php tidak ditemukan pada tree HEAD yang diaudit. Laporan Library Tahap 4B/4C pernah menyebut implementasi tersebut, tetapi laporan bukan source dan bukan bukti keberadaan pada HEAD.
3. P0.2: sejumlah relasi CBT/achievement belum sepenuhnya tenant-bound composite FK; orphan/ownership ambigu harus dikarantina, bukan ditebak/backfill paksa; semua Attempt/Answer/Snapshot/Score/Achievement history dipertahankan.
4. P0.3: authorization per-action master-data API belum konsisten; policy mungkin ada tetapi belum dipanggil; finalisasi matriks Admin/Guru/Siswa/Proktor wajib sebelum policy/seeder/permission DB diubah.
5. P1: ExamController atomicity/idempotency, CurriculumAchievement concurrency, CleanupOrphanedMedia race, AuditService tenant actor pada background command.

## P0.1: izin dan blocker
Pemilik proyek secara eksplisit menyetujui P0.1 saja: remediation migration 190008 yang data-preserving. P0.2 dan P0.3 belum disetujui untuk implementasi. Setelah P0.1, serahkan diff, backup, hasil tes, laporan, checkpoint; tunggu review sebelum P0.2.
Pemeriksaan sebelumnya:
- /mnt/data/CBT_MADRASAH_RECONSTRUCTION/ tidak ada di runtime.
- ZIP baseline exact name belum ditemukan pada pemeriksaan lokal tersebut; periksa ulang seluruh sumber, jangan menganggap status lama berlaku.
- GitHub source kandidat dapat diakses.
- Runtime saat itu PHP 8.4.24; Composer, MySQL, mysqld, Docker, Podman tidak tersedia; PDO drivers/extension dibutuhkan tidak lengkap.
- Karena blocker, tidak ada patch, backup source, migration, test, atau perubahan DB yang dilakukan.
- Laporan blocker: /mnt/data/CBT_P0_1_PREIMPLEMENTATION_CHECK_2026-11-21.zip
- SHA-256 ZIP: 82c62f536c1a06f77b0ffea58a30631fc2ef70e013fe28f924a8cc68cebbeb01

## Langkah wajib sebelum coding
1. Baca checkpoint GitHub dan laporan blocker.
2. Periksa seluruh workspace, ZIP baseline termasuk subfolder, backup, docs, checkpoint, Project Files, dan Library; cari berdasarkan nama, ekstensi, isi, fungsi. Bedakan laporan dari source.
3. Fetch ulang repo, branch, HEAD, PR status; jangan pakai SHA lama tanpa verifikasi.
4. Ambil file aktual dari HEAD dan hitung SHA-256 raw bytes, ukuran, path.
5. Periksa seluruh jalur migration Laravel 13: Artisan, installer, deployment workflow, event lifecycle, dan cara eksekusi lainnya.
6. Pastikan guard/preflight berjalan sebelum operasi destruktif dan sebelum up() menyentuh schema/data. Evaluasi MigrationStarted atau mekanisme lain hanya setelah timing Laravel 13 dan semua jalur dibuktikan.
7. Pastikan PHP 8.3, Composer, extension, MySQL 8/InnoDB disposable tersedia. Bila tidak, hentikan sebelum patch dan buat blocker report.
8. Verifikasi backup/restore clone disposable. Tidak ada akses DB produksi.

## Desain/acceptance P0.1
- Jangan dropIfExists pada tabel berpotensi berisi data.
- Jika migration pending tetapi tabel sudah ada, HALT sebelum perubahan.
- Periksa migration ledger, schema, index, FK, dan data; bedakan fresh install vs upgrade.
- Orphan, tenant mismatch, ownership/provenance ambigu: quarantine dan laporkan; jangan auto-delete, auto-overwrite, infer, atau force backfill.
- Pertahankan seluruh histori akademik/CBT.
- Migration historis yang mungkin pernah dijalankan dianggap immutable; tanpa bukti belum pernah dirilis/dijalankan, utamakan guard/preflight dan forward-only migration.
- MySQL DDL dapat implicit commit; jangan klaim rollback transaksional.
- Tes wajib: fresh install; pending + table absent; pending + table present with data => HALT before mutation; row count/checksum unchanged on HALT; inconsistent ledger/schema => diagnostic HALT; orphan/mismatch quarantine; safe legacy upgrade fixture; backup/restore; unit/feature/integration MySQL 8 InnoDB; semua jalur installer/deployment/Artisan.
- Kriteria yang belum dijalankan harus NOT VERIFIED, bukan PASS.

## Batas scope
- Hanya P0.1 yang diizinkan.
- Jangan mulai P0.2 sebelum P0.1 diserahkan dan ditinjau.
- Jangan mulai P0.3 sebelum matriks permission disetujui.
- Jangan gabungkan P0.1/P0.2/P0.3.
- Jangan sentuh Android, Device Lease/Fencing, production DB, deploy, merge PR tanpa otorisasi.
- Jangan membuat ulang source dari laporan.
- Jangan menyimpan .env, secrets, private keys, tokens, kredensial, atau data siswa nyata ke GitHub.

## GitHub, backup, dan laporan
- Backup setiap file sebelum perubahan; catat lokasi, ukuran, SHA-256.
- Catat file dibuat/diubah/dihapus dan diff.
- Setiap tahap update GitHub: audit, desain, implementasi, test, checkpoint, blocker, handover.
- Branch docs/project-continuity-policy-20261121 adalah branch dokumentasi, bukan branch sumber; jangan menganggap sudah merge.
- Jangan commit source belum diuji, jangan merge/deploy tanpa persetujuan.
- Laporan harus memiliki AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN, CHECKPOINT/NEXT ACTION. Cantumkan repo/branch/commit, SHA-256, backup, environment, perintah dan hasil tes, keterbatasan.
- Pada handover berikutnya buat prompt agent lengkap berisi posisi, keputusan, source, commit, file, backup, tes, blocker, dan langkah berikutnya.

## Instruksi penutup
Mulai dari pemeriksaan bukti aktual dan checkpoint terbaru. Jangan mengulang pekerjaan yang terbukti selesai, jangan mengarang source yang tidak ditemukan, jangan meminta keputusan yang sudah tercatat, dan jangan klaim berhasil tanpa bukti. Setelah P0.1 selesai atau blocked, update GitHub dan tunggu review pemilik proyek sebelum tahap berikutnya.