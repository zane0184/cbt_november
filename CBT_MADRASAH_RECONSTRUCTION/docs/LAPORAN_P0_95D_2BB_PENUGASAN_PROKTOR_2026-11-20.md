# LAPORAN P0.95D.2BB — Penugasan Proktor Sementara (Implementasi Awal)

Tanggal: 20 November 2026  
Branch: \`work/schema-dump-export-20261119\`  
PR: #3 — OPEN, Draft  
Status: IMPLEMENTASI AWAL MASUK SOURCE; CI PASCA-PERBAIKAN MENUNGGU

## 1. AUDIT

### 1.1 Source aktual sebelum implementasi
- Repository: \`zane0184/cbt_november\`.
- Head sebelum patch: \`506d9f53e8a46327a833191de500dc14eb76a10a\`.
- Base \`main\`: \`95736e338e517a087503adfb3e3a899709e0340c\`.
- PR #3 tetap OPEN dan Draft.
- Pemeriksaan source menunjukkan halaman \`/proktor/assignments\` sudah ada, tetapi \`ProktorDashboard\` mengembalikan collection kosong.
- Belum ada tabel/model/service penugasan Proktor.
- \`ExamPolicy::monitor()\` belum mengizinkan PROKTOR berdasarkan penugasan aktif.
- Permission \`exam.proctor.assign\` sudah tersedia pada bootstrapper, tetapi belum memiliki workflow operasional.
- Route monitoring sudah menggunakan policy \`monitor\`, sehingga policy dapat menjadi gerbang akses terpusat.

### 1.2 Prinsip desain yang dipertahankan
- Tidak membuat dashboard Proktor permanen.
- Admin Madrasah memberi penugasan untuk satu ujian dan satu akun Proktor dalam tenant yang sama.
- Penugasan mempunyai waktu mulai, waktu berakhir, alasan, status, aktor pemberi, serta data pencabutan.
- Akses monitoring Proktor hanya aktif selama penugasan berlaku dan belum dicabut.
- Proktor tidak memperoleh kewenangan mengelola soal, token, peserta, penilaian, atau finalisasi.
- Foreign key komposit mengikat exam, Proktor, pemberi, pencabut, dan tenant.
- Riwayat perubahan dicatat melalui AuditService.
- Rollback migration ditolak jika tabel sudah berisi riwayat penugasan.

## 2. DESAIN

### 2.1 Struktur data
Tabel baru: \`exam_proctor_assignments\`.

Kolom utama:
- \`tenant_id\`, \`exam_id\`, \`proctor_user_id\`
- \`assigned_by\`, \`starts_at\`, \`expires_at\`
- \`status\`, \`assignment_reason\`
- \`revoked_at\`, \`revoked_by\`, \`revoke_reason\`

Unique key: satu baris kondisi terkini per kombinasi tenant + ujian + Proktor. Perpanjangan/penugasan ulang memperbarui baris tersebut; perubahan sebelumnya tetap dicatat pada audit log.

### 2.2 Aturan otorisasi
- Hanya ADMIN_MADRASAH dengan permission \`exam.proctor.assign\` dapat membuat, memperbarui, atau mencabut.
- Ujian harus PUBLISHED, terkunci, memiliki \`end_at\` yang masih akan datang.
- Akun Proktor harus aktif, memiliki role PROKTOR, dan berada pada tenant yang sama.
- \`expires_at\` tidak boleh melewati \`exams.end_at\`.
- Penugasan yang kedaluwarsa atau dicabut tidak memberi akses monitoring.
- Proktor yang ditugaskan hanya dapat mengakses ujian yang sama dengan assignment aktif.

## 3. IMPLEMENTASI

### 3.1 File baru
- \`backend-laravel/database/migrations/2026_11_20_100001_create_exam_proctor_assignments_table.php\`
- \`backend-laravel/app/Models/ExamProctorAssignment.php\`
- \`backend-laravel/app/Services/ExamProctorAssignmentService.php\`
- \`backend-laravel/app/Livewire/Admin/ProctorAssignmentManagement.php\`
- \`backend-laravel/resources/views/livewire/admin/proctor-assignment-management.blade.php\`
- \`backend-laravel/tests/Feature/ProctorAssignmentSecurityTest.php\`

### 3.2 File yang diubah
- \`backend-laravel/app/Policies/ExamPolicy.php\`
- \`backend-laravel/app/Livewire/Proktor/ProktorDashboard.php\`
- \`backend-laravel/resources/views/livewire/proktor/proktor-dashboard.blade.php\`
- \`backend-laravel/routes/web.php\`
- \`backend-laravel/resources/views/layouts/app.blade.php\`

### 3.3 Backup sebelum perubahan
Backup dibuat pada branch yang sama dan diverifikasi identik dengan blob source:
- ExamPolicy: old/backup blob \`08811cbc9e41ea15306b2c794ba97cc2fe28f097\`
- ProktorDashboard: old/backup blob \`e213bc9bbb717f944858045ef6275b34a18914a6\`
- Proktor view: old/backup blob \`b33f8128b3a37dbecaf313273e13c495c69fdd8a\`
- web.php: old/backup blob \`f30411a2f0ee2aa40ce0339eafb163c8ee60544b\`
- app layout: old/backup blob \`1dadc16dbf655544949e4c247f9ab8a6700f2f86\`
- migration baru sebelum koreksi: old/backup blob \`4a0d92426e9b19dbc7ba2ee60f05fbfa1869f8d9\`

Semua pasangan backup/source di atas telah dibandingkan dan identik.

### 3.4 Commit
- Backup source awal: commit berurutan \`8016958c\`, \`888a244a\`, \`742b97f8\`, \`b613b5e8\`, \`506d9f53\`.
- Implementasi Proktor: \`0a58b4e2fa2100e113d722475108f53dc93986f9\`.
- Backup migration sebelum koreksi: \`e9e8caa00a0b22e00b322d057716a73135b86bb1\`.
- Koreksi import exception migration: \`f41ca8a3be75d1fb155a4216d8ee5aa21a95f10d\`.
- Head branch saat checkpoint: \`f41ca8a3be75d1fb155a4216d8ee5aa21a95f10d\`.

### 3.5 Koreksi yang ditemukan dari CI
Fresh Chain run \`44665838943\` gagal sebelum migration dijalankan karena deklarasi \`use RuntimeException;\` pada file migration global namespace memunculkan warning PHP yang diperlakukan sebagai exception.

Koreksi dilakukan dengan:
- menghapus import global yang tidak diperlukan;
- menggunakan \`\\RuntimeException\` secara eksplisit;
- membuat backup migration sebelum koreksi;
- tidak mengubah desain schema atau mengurangi guard rollback.

## 4. PENGUJIAN

### 4.1 Yang sudah dibuktikan
- Branch dan PR head diverifikasi setelah commit.
- Backup keenam file diverifikasi identik berdasarkan Git blob SHA.
- GitHub Actions menjalankan Fresh Chain pada commit implementasi awal dan menemukan kesalahan import di atas.
- Fresh Chain tersebut gagal pada tahap awal migrasi; database disposable dihancurkan oleh job cleanup sesuai log.
- Test baru telah ditambahkan untuk assignment, audit, monitoring aktif, pencabutan, kedaluwarsa, akses Proktor, dan penolakan FK lintas tenant.

### 4.2 Yang belum boleh diklaim
- Migration baru belum dinyatakan PASS setelah koreksi \`f41ca8a\`.
- Full PHPUnit pada source P0.95D.2BB belum dinyatakan PASS.
- Feature test baru belum dinyatakan PASS.
- Belum ada concurrency test paralel untuk assign/revoke.
- Belum ada uji UI Livewire melalui browser nyata.
- Belum ada staging VPS atau database produksi yang disentuh.

## 5. STATUS DAN LANGKAH BERIKUTNYA

**NO-GO produksi.**

Prioritas:
1. Jalankan ulang Fresh Chain pada source setelah koreksi import.
2. Jalankan full Tenant Backend CI pada commit yang sama.
3. Audit hasil test dan perbaiki setiap kegagalan tanpa menghapus test atau melemahkan tenant/RBAC/FK.
4. Uji race assign-vs-revoke dan masa berlaku pada MySQL 8/InnoDB.
5. Setelah Proktor assignment lulus, lanjutkan implementasi Device Lease/Fencing end-to-end. Source aktif masih belum memiliki service/endpoint fencing; heartbeat ujian masih menggunakan device binding lama.
6. Siapkan staging dua VPS hanya setelah gate backend dan schema terpenuhi.

Tidak ada merge ke main, deployment, migration produksi, atau perubahan database utama pada tahap ini.
