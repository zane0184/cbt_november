# LAPORAN P0.95D.2BB — Penugasan Proktor Sementara

Tanggal: 20 November 2026  
Repo: \`zane0184/cbt_november\`  
Branch: \`work/schema-dump-export-20261119\`  
PR #3: OPEN, Draft  
Source implementation yang diuji: \`9e2b9645cb4af67e9a257539460a888a74f40277\`  
Status: IMPLEMENTASI AWAL + FULL BACKEND CI + FRESH CHAIN PASS

## 1. AUDIT

### 1.1 Source aktual
- Sebelum implementasi, halaman Proktor tersedia tetapi tidak menampilkan assignment.
- Belum ada model, service, tabel, atau workflow penugasan Proktor.
- \`ExamPolicy::monitor()\` belum memeriksa penugasan Proktor.
- Permission \`exam.proctor.assign\` sudah ada pada RolePermissionBootstrapper.
- Monitoring API dan halaman monitoring sudah menggunakan policy \`monitor\`.
- Device Lease/Fencing belum diimplementasikan pada jalur aplikasi. Skema tabel/kolom migration tersedia, tetapi controller masih mengandalkan \`X-Device-Binding-ID\` dan \`device_fingerprint\`.

### 1.2 Prinsip desain
- Tidak membuat dashboard Proktor permanen.
- Admin Madrasah menugaskan satu akun Proktor untuk satu ujian, dalam tenant yang sama.
- Penugasan memiliki waktu mulai/berakhir, alasan, pemberi, status, serta data pencabutan.
- Akses monitoring hanya berlaku saat assignment aktif, belum kedaluwarsa, dan belum dicabut.
- Proktor tidak memperoleh akses mengelola soal, token, peserta, penilaian, atau finalisasi.
- FK komposit mengikat exam, Proktor, pemberi, pencabut, dan tenant.
- AuditService mencatat pemberian, pembaruan/perpanjangan, dan pencabutan.
- Rollback migration ditolak jika tabel telah memiliki riwayat assignment.

## 2. DESAIN

Tabel \`exam_proctor_assignments\`:
- tenant_id, exam_id, proctor_user_id
- assigned_by, starts_at, expires_at
- status, assignment_reason
- revoked_at, revoked_by, revoke_reason

Satu baris kondisi terkini dipertahankan per kombinasi tenant + ujian + Proktor. Perpanjangan atau penugasan ulang memperbarui baris tersebut; setiap perubahan tetap dicatat pada audit log.

Otorisasi:
- ADMIN_MADRASAH + permission \`exam.proctor.assign\`.
- Exam harus PUBLISHED, locked, dan end_at masih akan datang.
- Akun Proktor harus aktif, memiliki role PROKTOR, dan berada pada tenant yang sama.
- expires_at tidak boleh melewati exams.end_at.
- Proktor hanya dapat memantau exam dengan assignment aktif.
- Revoke segera menutup akses monitoring.

## 3. IMPLEMENTASI

### 3.1 File baru
- \`backend-laravel/database/migrations/2026_11_20_100001_create_exam_proctor_assignments_table.php\`
- \`backend-laravel/app/Models/ExamProctorAssignment.php\`
- \`backend-laravel/app/Services/ExamProctorAssignmentService.php\`
- \`backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php\`
- \`backend-laravel/resources/views/livewire/admin/proctor-assignment-management.blade.php\`
- \`backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php\`

### 3.2 File diubah
- \`backend-laravel/app/Policies/ExamPolicy.php\`
- \`backend-laravel/app/Livewire/Proktor/ProktorDashboard.php\`
- \`backend-laravel/resources/views/livewire/proktor/proktor-dashboard.blade.php\`
- \`backend-laravel/routes/web.php\`
- \`backend-laravel/resources/views/layouts/app.blade.php\`
- \`.github/workflows/p0-95d-fresh-chain.yml\`

### 3.3 Backup
Backup dibuat sebelum patch dan blob backup diverifikasi identik dengan source lama:
- ExamPolicy: \`08811cbc9e41ea15306b2c794ba97cc2fe28f097\`
- ProktorDashboard: \`e213bc9bbb717f944858045ef6275b34a18914a6\`
- Proktor dashboard Blade: \`b33f8128b3a37dbecaf313273e13c495c69fdd8a\`
- routes/web.php: \`f30411a2f0ee2aa40ce0339eafb163c8ee60544b\`
- layouts/app.blade.php: \`1dadc16dbf655544949e4c247f9ab8a6700f2f86\`
- migration Proktor sebelum koreksi: \`4a0d92426e9b19dbc7ba2ee60f05fbfa1869f8d9\`
- Fresh Chain workflow sebelum pembaruan ekspektasi schema: \`98cacef695a1bc85d6359d7f6593b3c46408801e\`

Semua pasangan backup/source di atas telah diverifikasi identik.

### 3.4 Commit penting
- Implementasi Proktor: \`0a58b4e2fa2100e113d722475108f53dc93986f9\`
- Koreksi PHP global RuntimeException warning: \`f41ca8a3be75d1fb155a4216d8ee5aa21a95f10d\`
- Dokumentasi checkpoint awal: \`ad65dc4045aed7742adeb485970ba09f3f2febdf\`
- Pembaruan Fresh Chain untuk tabel/migration baru: \`9e2b9645cb4af67e9a257539460a888a74f40277\`

## 4. PENGUJIAN

### 4.1 Full Tenant Backend CI
<badge color="success">PASS</badge>

- Run \`44666470173\`
- Job \`136056862050\`
- PHP 8.3
- MySQL 8.0 disposable
- 445 passed
- 1 skipped
- 1.818 assertions
- \`ProctorAssignmentSecurityTest\`: 6 test passed

Run duplikat \`44666477582\` juga selesai SUCCESS dengan source commit yang sama.

### 4.2 Fresh Chain Migration Gate
<badge color="success">PASS</badge>

- Run \`44666470165\`
- Job \`136056861967\`
- MySQL 8.0
- Fresh migration exit code 0.
- Schema dump berhasil dibuat dan dimuat kembali.
- Schema dump replay: 71 tabel, 57 migration ledger entries.
- Tabel \`exam_proctor_assignments\` ditemukan.
- Migration \`2026_11_20_100001_create_exam_proctor_assignments_table\` tercatat.
- FK kurikulum khusus ditemukan.
- Non-InnoDB table: 0.
- Fase1C rollback guard: PASS; schema dan migration ledger tidak berubah.
- Schema audit gate: PASS.
- Cleanup kedua database disposable: PASS; sisa schema count 0.

### 4.3 Koreksi CI yang dilakukan
- Run awal \`44665838943\` gagal sebelum migration karena import global \`RuntimeException\` memunculkan PHP warning.
- Import dihapus dan exception dipanggil dengan namespace global eksplisit.
- Setelah migration baru menambah satu tabel dan satu migration, Fresh Chain awal gagal karena masih mengharapkan 70 tabel/56 migration.
- Workflow diperbarui menjadi 71 tabel/57 migration dan memeriksa keberadaan tabel serta migration Proktor secara eksplisit.
- Tidak ada test yang dihapus atau di-skip untuk memperoleh hasil PASS.

### 4.4 Batasan yang belum diuji
- Race condition assign-vs-revoke belum diuji paralel.
- Browser UAT untuk halaman Livewire belum dilakukan.
- Device Lease/Fencing belum diterapkan.
- Belum ada uji VPS staging, restore database, atau UAT empat madrasah.

## 5. STATUS DAN LANGKAH BERIKUTNYA

**P0.95D.2BB — PASS untuk fondasi penugasan Proktor dan fresh migration.**

**NO-GO produksi tetap berlaku.**

Tahap berikutnya P0.95D.2BC:
1. Audit ulang skema registered_devices/device_leases dan seluruh endpoint attempt-scoped.
2. Rancang forward-only migration untuk composite FK tenant dan retensi riwayat lease; jangan mengubah migration historis.
3. Implementasikan RegisteredDevice, DeviceLease, DeviceLeaseService, register/claim/heartbeat/release.
4. Integrasikan lease/fence ke execution, read/save answer, heartbeat, dan submit dengan lock order konsisten.
5. Sediakan UI Proktor untuk release perangkat dengan alasan wajib.
6. Uji stale generation, expired lease, multi-device, race save-vs-release, idempotency, tenant mismatch pada MySQL 8.
7. Pertahankan kompatibilitas hanya melalui mode staging yang jelas; jangan mengklaim enforcement aktif sebelum seluruh jalur teruji.

Tidak ada merge ke main, deployment VPS, migration produksi, atau perubahan database utama pada tahap ini. Android tetap ditunda sampai Juni 2027; backend enforcement belum boleh diaktifkan pada produksi yang masih memakai klien lama.
