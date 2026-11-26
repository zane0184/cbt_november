# Checkpoint — Combined Feature Completion
Tanggal: 26 November 2026
Branch: `work/combined-feature-completion-20261126`
Base source: `work/guru-docx-import-e2e-20261125`
Base commit: `cbcf7db3db8dacb7ae6d6cd8b9ab1c8c075cd195`

## AUDIT
- Paket deployment sebelumnya dibangun dari source commit `cbcf7db3db8dacb7ae6d6cd8b9ab1c8c075cd195`.
- Profil Madrasah dan Branding telah tersedia pada base source dan dipertahankan tanpa ditulis ulang.
- Data Akademik telah memiliki Rombel, Mapel, dan Tingkat Kelas. UI Tahun Pelajaran/Semester belum tersedia pada base source.
- Installer terbaru menggunakan `TenantAcademicBootstrapper` yang menyiapkan satu periode akademik aktif berdasarkan tanggal instalasi. Implementasi P0 yang lebih lama memakai bootstrap berbeda (enam tahun tanpa semester aktif); bootstrap terbaru dipertahankan.
- Registry resmi sudah memiliki node SUBJECT per fase A/B/C, dengan metadata `subject_code`, classification, applicable_grades, dan provenance sumber.

## DESAIN
- Satu database tetap milik satu instalasi madrasah. Registry resmi memakai reserved System Tenant dalam database instalasi tersebut; salinan Mapel yang digunakan operasional dibuat pada tenant madrasah lokal.
- Sinkronisasi Mapel hanya menerima versi resmi read-only dan sumber berstatus `METADATA_VERIFIED` atau `ARTIFACT_CHECKSUM_VERIFIED`.
- Sinkronisasi membuat satu Mapel lokal per kode resmi unik dan pemetaan ke setiap node fase yang sesuai.
- Nama Mapel lokal yang telah diedit tidak ditimpa. Pemetaan nonaktif dan histori arsip tidak diaktifkan kembali otomatis.
- Kode resmi `SEJARAH_KEBUDAYAAN_ISLAM` memiliki 24 karakter, melebihi batas kode lokal 20 karakter; dipetakan dengan alias lokal `SKI`.
- Periode akademik baru dibuat nonaktif; Admin memilih semester aktif secara eksplisit. Riwayat tahun dan semester lama dipertahankan.

## IMPLEMENTASI
- Ditambahkan `AcademicYearManagementService.php` untuk membuat tahun pelajaran dan aktivasi semester dengan tenant lock, transaksi, otorisasi Admin, dan audit.
- Diperbarui `AcademicManagement.php` dan Blade untuk tab Tahun Pelajaran/Semester; jumlah siswa pada Rombel tetap ditampilkan.
- Diperbaiki relasi penugasan Guru dari `teacherProfile` ke relasi model yang benar, `teacher`, termasuk tampilan.
- Ditambahkan aksi `syncOfficialSubjects` pada registry kurikulum, tombol UI, audit event `CURRICULUM_SUBJECT_CATALOG_SYNCED`, alias kode SKI, dan pengamanan histori.
- Ditambahkan tes fitur untuk manajemen tahun/semester, penugasan Guru, dan sinkronisasi katalog Mapel.
- Workflow backend CI dan build ZIP deployment didaftarkan untuk branch ini.
- Profil Madrasah, Branding, import Guru/Siswa, DOCX import, dashboard, dan Rombel Detail tetap berasal dari base source.

### Backup sebelum perubahan
- `backend-laravel/backups/combined-feature-completion-20261126/AcademicManagement.php.before`
- `backend-laravel/backups/combined-feature-completion-20261126/academic-management.blade.php.before`
- `backend-laravel/backups/combined-feature-completion-20261126/TeacherSubjectAssignmentManagement.php.before`
- `backend-laravel/backups/combined-feature-completion-20261126/teacher-subject-assignment-management.blade.php.before`
- `backend-laravel/backups/combined-feature-completion-20261126/CurriculumRegistryManagement.php.before`
- `backend-laravel/backups/combined-feature-completion-20261126/curriculum-registry-management.blade.php.before`
- `docs/backup_before_patch/COMBINED_FEATURE_COMPLETION_20261126/tenant-backend-ci.yml.before`
- `docs/backup_before_patch/COMBINED_FEATURE_COMPLETION_20261126/build-web-deployment.yml.before`

## PENGUJIAN
- Pemeriksaan source dilakukan setelah penulisan: file komponen, service, view, dan tes ditemukan pada branch integrasi.
- Pemeriksaan daftar kode resmi menemukan 14 kode unik; hanya `SEJARAH_KEBUDAYAAN_ISLAM` melampaui batas 20 karakter dan telah diberi alias `SKI`.
- CI PHPUnit/MySQL 8 dan build ZIP deployment dipicu melalui push branch; hasil CI dan ZIP belum boleh dinyatakan lulus sebelum workflow selesai dan artefaknya diverifikasi.
- Tidak ada migration yang dijalankan pada VPS atau database produksi. Tidak ada akses atau perubahan VPS pada tahap ini.

## LANGKAH BERIKUTNYA
1. Periksa hasil workflow Tenant Backend CI (PHP 8.3, MySQL 8, PHPUnit).
2. Perbaiki kegagalan bila ada, dengan backup sebelum setiap revisi.
3. Periksa hasil workflow Build Standalone Web CBT Deployment ZIP.
4. Unduh artefak, validasi SHA-256, integritas ZIP, manifest, dan keberadaan Profil, Branding, Mapel sync, Tahun Pelajaran/Semester, import Guru/Siswa, DOCX, dan seluruh source runtime.
5. Berikan ZIP pembaruan dan laporan final; status hanya "siap staging" bila seluruh pemeriksaan paket lulus. Bukan persetujuan produksi.
