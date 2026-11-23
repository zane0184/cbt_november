# WEB ADMIN REMEDIASI TAHAP 3 — KATEGORI UJIAN, RUANG, MULTI-PROKTOR
Tanggal: 23 November 2026

## Status ringkas
Fondasi backend dan halaman Admin untuk penjadwalan ujian terpusat telah diimplementasikan pada branch remediasi. Pengujian CI MySQL 8 masih berjalan/belum menghasilkan PASS pada saat laporan ini dibuat. Tidak ada migration yang dijalankan terhadap database produksi, tidak ada deployment VPS, dan PR #18 tetap draft serta belum di-merge.

## AUDIT — keputusan operasional yang dipakai

Ketentuan yang diberikan pengguna dari jawaban Google AI diterapkan sebagai spesifikasi kerja:
- Ujian mandiri Guru: FORMATIF, SUMATIF_LINGKUP, SURVEI_KBC, REMEDIAL, TRYOUT_MANDIRI.
- Ujian terpusat Admin: ASTS (UTS), ASAS (UAS Ganjil), ASAT (UAS Genap), UM, SIMULASI_AN.
- Satu ruang/sesi boleh memiliki beberapa Guru pengawas.
- Satu Guru tidak boleh ditugaskan ke dua ruang/sesi dengan jadwal beririsan.
- Benturan ruang diblokir kecuali kedua assignment secara eksplisit memilih Gabung Ruang dan rentang waktunya identik.
- Guru pengawas hanya memiliki akses operasional pada peserta ruang/sesi yang ditugaskan; tidak memperoleh akses bank soal/kunci jawaban untuk ujian tersebut.

Source yang tetap dipertahankan:
- Data ujian lama dimigrasikan secara kompatibel ke kategori REGULAR dan non-terpusat.
- Tabel legacy `exam_proctor_assignments` tidak dihapus atau ditimpa; halaman Admin diarahkan ke alur room assignment baru.
- Relasi `exam_participants` yang sudah ada tidak diganti; kolom room assignment dan seat number ditambahkan nullable agar histori lama tidak rusak.

## DESAIN

### Kategori kanonis
- Kategori menjadi `exams.category`.
- `exams.is_centralized` diturunkan otomatis dari kategori oleh model, sehingga nilai flag tidak dipercaya dari input klien.
- MySQL 8+ memperoleh CHECK constraint untuk domain kategori dan konsistensi flag.
- Kategori lama/legacy memakai REGULAR dan tidak otomatis berubah menjadi ujian terpusat.
- Kategori baru dapat dipilih Guru melalui Web Exam Builder dan API.

### Skema ruang dan pengawas
Migration additive membuat:
- `rooms`: master ruang per tenant, kode unik per tenant, kapasitas, status aktif.
- `exam_room_assignments`: ujian, ruang, nomor sesi, token 6 karakter, masa token, jadwal, flag gabung ruang, pembuat.
- `exam_room_proctors`: pivot multi-pengawas, label proctor/supervisor, status, metadata pencabutan dan audit.
- `exam_participants.exam_room_assignment_id` dan `seat_number`, dengan FK komposit yang memastikan assignment sesuai exam dan tenant.

FK komposit dan unique candidate key menjaga isolasi tenant pada exam, room, user, assignment, dan participant.

### Konflik dan transaksi
- Service mengunci ujian, ruang, dan baris Guru dalam transaksi.
- Multi-Guru pada satu assignment diperbolehkan.
- Benturan jadwal Guru lintas assignment ditolak.
- Benturan ruang ditolak kecuali kedua assignment mengaktifkan Gabung Ruang dan jadwalnya persis sama.
- Token sesi dibuat menggunakan sumber acak kriptografis dan unik per tenant.
- Penambahan, reaktivasi, dan pencabutan pengawas dicatat melalui AuditService.

### RBAC dan monitoring
- Admin hanya melihat ujian kategori terpusat pada halaman penugasan.
- Guru pengawas harus memiliki role GURU, TeacherProfile aktif, tenant yang sama, dan pivot assignment aktif.
- Policy memberi kemampuan monitoring/daftar peserta/rilis token/reset login/extra time/cetak berita acara pada level room assignment. Policy tidak memberi akses bank soal atau kunci jawaban.
- MonitoringController membatasi ringkasan, daftar peserta, detail attempt, dan audit events pada peserta/attempt di ruang yang sedang ditugaskan kepada Guru tersebut.
- Guru untuk ujian mandiri tetap mengikuti kewenangan TeacherAccess yang sudah ada; tidak dibuat role Proktor permanen.

## IMPLEMENTASI

### File baru
- `backend-laravel/database/migrations/2026_11_23_140001_add_central_exam_room_assignments.php`
- `backend-laravel/app/Models/Room.php`
- `backend-laravel/app/Models/ExamRoomAssignment.php`
- `backend-laravel/app/Models/ExamRoomProctor.php`
- `backend-laravel/app/Services/ExamRoomAssignmentService.php`
- `backend-laravel/app/Policies/ExamRoomAssignmentPolicy.php`
- `backend-laravel/app/Livewire/Admin/CentralExamRoomAssignmentManagement.php`
- `backend-laravel/resources/views/livewire/admin/central-exam-room-assignment-management.blade.php`
- `backend-laravel/tests/Feature/CentralExamRoomAssignmentTest.php`

### File yang diubah
- `app/Models/Exam.php`: kategori kanonis, derivasi flag, relasi room assignments.
- `app/Models/ExamParticipant.php`: assignment ruang dan nomor kursi.
- `app/Policies/ExamPolicy.php`: policy penugasan terpusat, monitor, dan pembatasan hak Guru yang sedang bertugas sebagai Proktor.
- `app/Http/Controllers/Api/V1/MonitoringController.php`: scope data monitoring per ruang/sesi.
- `app/Livewire/Admin/ExamManagement.php` dan view: input kategori ujian pada alur Guru.
- `app/Http/Controllers/Api/V1/ExamController.php`: validasi kategori untuk API create/update.
- `routes/web.php`: route penugasan Proktor diarahkan ke halaman room assignment baru.
- `.github/workflows/tenant-backend-ci.yml`: branch remediasi ditambahkan ke pemicu CI.

### Backup
Backup sebelum perubahan tersedia di:
`backend-laravel/docs/backup/web-admin-room-assignment-2026-11-23/`

Termasuk backup Exam, ExamParticipant, ExamPolicy, migration (termasuk revisi candidate key FK), ExamRoomAssignmentService, MonitoringController, routes/web.php, ExamManagement, view, ExamController, workflow CI, serta salinan versi test sebelum koreksi. Tidak ada file source asli di branch basis yang ditimpa.

## PENGUJIAN

### Tes yang ditambahkan
`CentralExamRoomAssignmentTest` mencakup:
- beberapa Guru pada satu ruang/sesi;
- Guru yang sama ditolak ketika jadwal assignment berbeda beririsan;
- benturan ruang ditolak tanpa Gabung Ruang;
- ujian FORMATIF ditolak dari jalur assignment Admin;
- akses operasional Guru hanya aktif pada jadwal;
- larangan akses question bank/answer key melalui policy exam/assignment;
- API monitoring hanya mengembalikan siswa di ruang yang ditugaskan.

### Hasil CI yang teramati
- Run `45107417936` gagal pada pemuatan PHPUnit karena kesalahan sintaks namespace facade di test baru (baris 186). Kesalahan tersebut sudah diperbaiki pada commit `0d7baae0f1b64a449e7e9ada67ebd2962b0e487f`.
- Run berikutnya dibatalkan karena ada commit lanjutan saat remediasi masih berlangsung.
- Run terbaru saat laporan ini dibuat: `45107883143`, head `3c6c4af5f2a3d639cf52b4c5d745899d8b75ba12`, status in_progress. Belum ada hasil PHPUnit PASS yang dapat diklaim.
- CI menggunakan disposable MySQL 8 pada GitHub Actions; bukan database produksi.

## Belum selesai / batas klaim
1. Master room CRUD pada Web Admin belum dibuat; halaman assignment hanya memilih ruang yang sudah ada.
2. Service penempatan peserta ke ruang/sesi dan validasi kapasitas gabungan saat Gabung Ruang belum selesai.
3. Endpoint operasional untuk rilis token sesi, reset login, extra time, dan cetak berita acara belum diimplementasikan; policy capability sudah disiapkan.
4. Perlu uji penuh semua endpoint monitoring, termasuk audit payload, sebelum menyatakan isolasi Proktor final.
5. Perlu audit ulang route/export/report lain untuk memastikan tidak ada jalur Admin atau Proktor yang melewati policy.
6. PHPUnit penuh, fresh-install migration, E2E browser, concurrency test multi-Guru, dan UAT madrasah belum lulus/terverifikasi.
7. Legacy class/model/table assignment lama masih dipertahankan; perlu tahap deprecation dan migrasi histori yang terencana, bukan penghapusan langsung.

Tidak ada klaim siap produksi, tidak ada merge, tidak ada deployment, dan tidak ada migration produksi.


## Addendum — hardening batas waktu ujian (23 November 2026)

### AUDIT
Pemeriksaan lanjutan pada `ExamRoomAssignmentService::create()` menemukan pemeriksaan batas jadwal ujian sebelumnya bersifat kondisional: jika `start_at` atau `end_at` kosong, pemeriksaan batas yang bersangkutan dilewati. Padahal penugasan ruang/sesi harus selalu berada di dalam rentang waktu ujian yang sudah ditetapkan.

### IMPLEMENTASI
- Service sekarang menolak penugasan bila `start_at` atau `end_at` ujian kosong.
- Service memastikan waktu mulai dan selesai ruang/sesi seluruhnya berada di dalam rentang waktu ujian.
- Menambahkan regression test untuk ujian tanpa batas waktu mulai.
- Backup byte-identical sebelum perubahan:
  - `ExamRoomAssignmentService.php.before-schedule-boundary-hardening`
  - `CentralExamRoomAssignmentTest.php.before-schedule-boundary-hardening`
  - `WEB_ADMIN_REMEDIASI_TAHAP_3.before-schedule-boundary-update.md`

### PENGUJIAN
- Regression test ditambahkan, tetapi belum dijalankan oleh PHPUnit pada lingkungan ini.
- `fetch_commit_workflow_runs` tidak mengembalikan run untuk commit branch ini karena endpoint connector hanya mengambil workflow yang dipicu pull request; hasil CI untuk commit baru belum dapat diverifikasi dari respons tersebut.
- Tidak ada klaim PASS. Perlu verifikasi status GitHub Actions langsung pada PR/branch setelah push.
- Tidak ada migration produksi, deployment, atau merge.
