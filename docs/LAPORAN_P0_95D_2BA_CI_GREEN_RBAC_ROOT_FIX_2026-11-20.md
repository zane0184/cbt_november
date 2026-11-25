# LAPORAN P0.95D.2BA — CI Green, Akar RBAC, dan Rekonsiliasi Portal
Tanggal: 20 November 2026

## 1. AUDIT

### 1.1 Source of truth dan Git
- Repo: `zane0184/cbt_november`.
- Branch: `work/schema-dump-export-20261119`.
- Source code head sebelum dokumentasi tahap ini: `f11ef7b2ae60553fae5bbb125f5c3c2934e88228`.
- PR #3: OPEN, Draft, base `main` `95736e338e517a087503adfb3e3a899709e0340c`.
- PR tidak digabung; `main` tidak disentuh.
- Dua ZIP handover 19 November diperiksa: ZIP P0.95D.2AB berisi 12.602 file dan ZIP PRE_2AH berisi 12.598 file. Seluruh 12.598 file bersama memiliki ukuran dan CRC yang identik; ZIP terbaru hanya menambahkan empat dokumen P0.95D.2AH.
- `unzip -t` kedua arsip: PASS, tidak ada kerusakan CRC.

### 1.2 CI terbaru
Run Tenant Backend CI `44587114741`, job `135782985833`:
- PHP 8.3, disposable MySQL 8.0.46.
- MySQL identity fail-closed: PASS.
- Composer lock/platform: PASS.
- Install locked dependencies dan Laravel package discovery: PASS.
- Focused Web login + tenant identity smoke tests: PASS.
- Full PHPUnit suite: **439 passed, 1 skipped, 1.800 assertions**, job conclusion SUCCESS.
- Tidak ada test gagal atau risky pada ringkasan full suite.
- Seluruh kelompok test yang sebelumnya gagal — Admin UI, analytics cache, exam builder/management, installer, Fase1B/Fase1C, monitoring, Phase 7 scoring/reopen/history, QuestionBank, RBAC, AnswerKeyLeakage, SeederSafety, dan UserSecurity — tampil PASS pada log run ini.

Fresh Chain Migration Gate run `44566705845`: SUCCESS pada source checkpoint sebelumnya. Perbandingan commit `91b4d2e` ke source code head `f11ef7b` tidak menunjukkan file migration berubah; karena itu bukti fresh-chain masih relevan untuk kumpulan migration yang sama. Fresh Chain belum dijalankan ulang pada head dokumentasi terbaru.

### 1.3 Akar kegagalan berlapis yang dituntaskan
1. Bootstrapper mengasumsikan role OPERATOR selalu ada. Ketika role opsional tidak dibuat oleh fixture/tenant, `$roles->get('OPERATOR')` menghasilkan null dan memicu 161 kegagalan turunan. Perbaikan menggunakan lookup opsional dan skip bila role tidak ada.
2. Matriks permission Admin dan policy grading tidak konsisten. Admin memiliki kewenangan operasional tertentu, tetapi manual grading per-soal tetap milik Guru yang ditugaskan. Admin dapat melakukan finalisasi/reopen sesuai permission terpisah; tidak boleh mengambil alih koreksi jawaban per soal.
3. Test portal lama mengharapkan route Kamad dan Proktor permanen. Pengujian diselaraskan dengan desain: Kamad tidak memiliki dashboard permanen; Proktor hanya memiliki permukaan penugasan sementara; tidak ada dashboard Proktor permanen.
4. Role OPERATOR tetap legacy/opsional. Akses kurikulum yang sudah diizinkan dipertahankan; akses manajemen ujian tetap ditolak.
5. Dua kegagalan terakhir dari run sebelumnya (manual grade Admin mendapat 200 padahal harus 403; assertion akses kurikulum Operator) dikoreksi berdasarkan kontrak test dan role boundary. Run terbaru seluruh suite PASS.

## 2. DESAIN DAN BATAS KEWENANGAN
- Guru tetap menjadi pembuat/pengelola bank soal, penyusun dan penerbit ujian, serta pelaksana manual grading sesuai penugasan akademik.
- Admin Madrasah menangani administrasi dan kewenangan operasional ujian yang ditetapkan permission, termasuk finalisasi/reopen sesuai kebijakan; Admin tidak memperoleh hak mengubah nilai manual per soal sebagai pengganti Guru.
- Kamad hanya monitoring dan laporan, bukan portal pengelolaan ujian.
- Proktor bukan portal permanen. Halaman penugasan tetap fail-closed sampai model, route, policy, dan audit penugasan sementara tersedia.
- Isolasi tenant, penugasan akademik Guru, anti-enumeration, audit log, immutable score history, dan current-score pointer tidak boleh dilemahkan.

## 3. IMPLEMENTASI

Source yang diubah pada rangkaian P0.95D.2AU–2BA:
- `app/Services/RolePermissionBootstrapper.php`: role OPERATOR opsional; grant Admin dikembalikan sesuai hak operasional yang disetujui; stale grant `question.manage` Admin dicabut; hak kurikulum yang tidak sesuai role dicabut.
- `app/Policies/ExamAttemptPolicy.php`: manual grading per-soal tetap Guru dengan assignment; Admin finalisasi dan reopen menggunakan permission terpisah dan pemeriksaan tenant.
- `tests/Feature/RolePermissionBootstrapperTest.php`: regresi role opsional dan matriks Admin/Guru/Kamad/Operator.
- `tests/Feature/RoleDashboardVerificationTest.php`: route Kamad/Proktor permanen ditolak; Proktor hanya permukaan assignment; Operator tetap pada cakupan kurikulum yang diizinkan.
- Rangkaian hardening sebelumnya juga mengubah `ExamPolicy`, `AssessmentPolicy`, `ExamController`, `MonitoringController`, `ProktorDashboard`, layout portal, dan test keamanan preview/monitoring.

Backup dibuat sebelum patch dan diverifikasi identik:
- `backup_before_patch/P0_95D_2AU_*`
- `backup_before_patch/P0_95D_2AV_*`
- `backup_before_patch/P0_95D_2AW_*`
- `backup_before_patch/P0_95D_2BA_*`
- `backup_before_patch/P0_95D_2BB_*`
- `backup_before_patch/P0_95D_2BC_*)

Tidak ada backup yang ditimpa. Tidak ada migration atau database produksi yang dijalankan.

## 4. PENGUJIAN
- CI Tenant Backend run `44587114741`: SUCCESS.
- Full PHPUnit: 439 passed, 1 skipped, 1.800 assertions.
- Focused login/tenant identity smoke tests: PASS.
- Fresh Chain run `44566705845`: SUCCESS pada source sebelum perubahan non-migration; migration tidak berubah setelah SHA tersebut.
- ZIP handover terbaru dan PRE_2AH: CRC PASS; 12.598 common entries identik.
- Tidak ada klaim build Android, runtime Android, VPS staging, concurrency E2E, atau UAT madrasah pada tahap ini.

## 5. BLOCKER DAN STATUS PRODUKSI
**NO-GO PRODUKSI tetap berlaku.** CI hijau adalah bukti suite otomatis backend pada disposable MySQL, bukan bukti kesiapan produksi.

Temuan yang masih terbuka:
1. Model/tabel/route/policy untuk penugasan Proktor sementara belum ditemukan pada source branch. UI Proktor sengaja mengembalikan daftar kosong (fail-closed). Permission `exam.proctor.assign` sudah ada, tetapi alur assignment end-to-end belum ada.
2. Search source branch tidak menemukan `release-device`, `fence_generation`, atau `device_lease_expires_at`. Laporan Tahap 5P lama tidak membuktikan implementasi itu sudah terintegrasi ke branch ini. Device lease/fencing harus diperlakukan belum terintegrasi dan belum siap produksi.
3. Uji race/concurrency MySQL, multi-device, sinkronisasi/offline, backup/restore, staging VPS, dan UAT empat madrasah belum dibuktikan.
4. Akses VPS Publisher/Tenant tidak tersedia pada sesi ini; tidak ada deployment atau migration produksi.

## 6. LANGKAH BERIKUTNYA
1. Audit dan desain model penugasan Proktor sementara: tenant, exam, proktor, masa berlaku, status, pemberi tugas, pencabutan, alasan, dan audit actor; kemudian route/policy/service/UI dengan fail-closed.
2. Tetapkan kontrak bahwa Proktor hanya melihat ujian yang sedang ditugaskan dan hanya dapat melakukan operasi yang diizinkan selama penugasan aktif.
3. Rancang dan uji integrasi device lease/fencing secara terpisah sebelum menyambungkannya ke Proktor; jangan menganggap laporan Tahap 5P sebagai bukti source branch saat ini.
4. Jalankan Fresh Chain Migration Gate ulang setelah ada perubahan migration berikutnya; tidak perlu mengubah migration historis.
5. Lanjutkan pengujian concurrency, tenant isolation, recovery, staging, dan UAT.
6. Jangan merge PR #3, deploy, atau menyentuh database produksi tanpa persetujuan eksplisit.

## Status
**P0.95D.2BA — FULL BACKEND CI PASS; FUNCTIONAL/INTEGRATION PRODUCTION GATES STILL OPEN.**
