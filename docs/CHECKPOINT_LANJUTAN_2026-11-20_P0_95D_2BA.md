# CHECKPOINT LANJUTAN P0.95D.2BA — 20 November 2026

## 1. Posisi aktual Git
- Repo: `zane0184/cbt_november`.
- Branch: `work/schema-dump-export-20261119`.
- Source code head yang diuji: `f11ef7b2ae60553fae5bbb125f5c3c2934e88228`.
- Laporan P0.95D.2BA commit: `5dda7968ee683c12431e1dd2065d5892927ecff9`.
- PR #3: OPEN, Draft, base `main` SHA `95736e338e517a087503adfb3e3a899709e0340c`.
- Jangan merge; `main` tidak diubah.
- Setelah checkpoint ini dibuat, perbarui SHA head branch dari GitHub; commit checkpoint akan menjadi head dokumentasi terbaru.

## 2. CI terbaru — PASS
Tenant Backend CI:
- Run `44587114741`, job `135782985833`.
- PHP 8.3, MySQL 8.0.46 disposable.
- Identity fail-closed, Composer lock/platform, dependency install, package discovery: PASS.
- Focused Web login + tenant identity smoke tests: PASS.
- Full PHPUnit: **439 passed, 1 skipped, 1.800 assertions**.
- Job conclusion: SUCCESS.
- Log lengkap run sudah diperiksa; semua kelompok test yang tercatat pada P0.95D.2AH tampil PASS.

Fresh Chain Migration Gate:
- Run `44566705845`: SUCCESS.
- Run ini berasal dari source sebelum patch non-migration terakhir. Perbandingan `91b4d2e` → `f11ef7b` menunjukkan tidak ada file migration yang berubah. Fresh Chain tetap menjadi bukti untuk migration yang sama; jalankan ulang jika migration berubah.

## 3. Akar kegagalan yang sudah dituntaskan
- Bootstrapper role OPERATOR opsional menyebabkan null dereference dan 161 failure turunan; sekarang role optional di-skip secara fail-safe.
- RBAC manual grading dan finalisasi dipisah: manual question grading hanya Guru yang ditugaskan; Admin finalisasi/reopen melalui permission terpisah.
- Portal legacy Kamad/Proktor permanen ditolak; Proktor hanya memiliki permukaan assignment sementara yang fail-closed.
- Operator tetap hanya pada akses kurikulum yang sudah diizinkan; tidak diberi akses pengelolaan ujian.
- Dua failure tersisa pada CI 420 passed/17 failed telah diperbaiki; CI berikutnya 439 passed/1 skipped.

## 4. File berubah dan backup
Implementasi utama:
- `backend-laravel/app/Services/RolePermissionBootstrapper.php`
- `backend-laravel/app/Policies/ExamAttemptPolicy.php`
- `backend-laravel/tests/Feature/RolePermissionBootstrapperTest.php`
- `backend-laravel/tests/Feature/RoleDashboardVerificationTest.php`
- Rangkaian hardening sebelumnya: ExamPolicy, AssessmentPolicy, ExamController, MonitoringController, ProktorDashboard, layout portal, dan test terkait.

Backup telah dibuat dan diverifikasi identik sebelum perubahan:
- `backend-laravel/backup_before_patch/P0_95D_2AU_*`
- `backend-laravel/backup_before_patch/P0_95D_2AV_*`
- `backend-laravel/backup_before_patch/P0_95D_2AW_*`
- `backend-laravel/backup_before_patch/P0_95D_2BA_*`
- `backend-laravel/backup_before_patch/P0_95D_2BB_*`
- `backend-laravel/backup_before_patch/P0_95D_2BC_*`

Laporan lengkap: `docs/LAPORAN_P0_95D_2BA_CI_GREEN_RBAC_ROOT_FIX_2026-11-20.md`.

## 5. ZIP handover yang diperiksa
- `CBT_MADRASAH_HANDOVER_AGENT_BARU_2026-11-19_P0_95D_2AB(1).zip`: 12.602 file.
- `CBT_MADRASAH_HANDOVER_AGENT_BARU_2026-11-19_PRE_2AH_BACKUP(1).zip`: 12.598 file.
- Kedua ZIP lolos `unzip -t`.
- Seluruh 12.598 entri bersama memiliki ukuran dan CRC identik; ZIP terbaru menambahkan empat dokumen P0.95D.2AH.
- Arsip asli tidak diubah.

## 6. Blocker yang masih wajib diselesaikan
1. Proktor assignment: belum ditemukan model/tabel/route/policy/service penugasan sementara. ProktorDashboard masih fail-closed dengan daftar kosong. Permission `exam.proctor.assign` saja belum berarti workflow sudah ada.
2. Device Lease/Fencing: source branch saat ini tidak memiliki `release-device`, `fence_generation`, atau `device_lease_expires_at`. Laporan Tahap 5P lama bukan bukti integrasi ke branch ini.
3. Concurrency/race MySQL, multi-device, offline/sync/recovery, backup/restore, staging VPS, dan UAT empat madrasah belum lulus/belum dibuktikan.
4. Tidak ada akses VPS Publisher/Tenant pada sesi ini.

## 7. Langkah berikutnya
1. Audit penuh desain Proktor assignment sementara dan implementasikan hanya setelah model relasi dan batas otorisasi terdefinisi.
2. Uji akses lintas tenant, masa berlaku assignment, revoke, audit actor/reason, dan akses setelah assignment kedaluwarsa.
3. Buat integrasi Device Lease/Fencing yang terpisah; tes save-vs-release race, stale generation, expired lease, token invalid, duplicate release, dan multi-device.
4. Lanjutkan CI backend dan Fresh Chain setelah perubahan migration.
5. Setelah integrasi lolos, lanjut staging dua VPS, security/concurrency, recovery, backup/restore, dan UAT empat madrasah.
6. Jangan merge PR #3, deploy, atau migration produksi tanpa persetujuan eksplisit.

## Status
**FULL BACKEND CI PASS. PRODUKSI TETAP NO-GO.** Web CBT scope; Android client dan central Super Admin mengikuti keputusan handover untuk ditunda sampai Juni 2027.
