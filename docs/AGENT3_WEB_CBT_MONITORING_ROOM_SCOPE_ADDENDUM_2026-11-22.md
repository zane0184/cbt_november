# Addendum Audit Agent 3 — Monitoring Room Scope & UI Authorization

Tanggal: 22 November 2026
Branch sumber Agent 3: `work/web-guru-foundation-20261122`
HEAD saat audit: `387084e06e0e4df957013a2563b7da14a2401f46`
Instruksi koordinator: Issue #10 comment `7078921888`

## Temuan tambahan

### Monitoring UI

- Route `/exams/{exam}/monitoring` pada `backend-laravel/routes/web.php` blob `0cf0c73d28a1f8ab948e7594b6a588c1ca864d6a` hanya menerima parameter Exam dan melakukan Gate `monitor`.
- View `backend-laravel/resources/views/monitoring/dashboard.blade.php` blob `6e2521c88b811c508868911b08071c99515486c7` membangun API base `/api/v1/exams/{examId}/monitoring` dan memuat summary serta daftar seluruh peserta pada endpoint Exam-level. Tidak ada SessionRoom/Room ID pada route atau API base yang digunakan view ini.
- Ini **belum membuktikan data backend bocor**, karena controller/query backend belum seluruhnya ditelusuri. Namun UI consumer saat ini tidak menunjukkan batas room; untuk Proktor, Exam-level Gate saja tidak cukup. P0: perlu endpoint/contract room-scoped dari Agent 1/2 dan UI yang mengikat ExamSession kanonis + SessionRoom/Room + assignment aktif.
- UI lama menampilkan nama siswa, status Attempt, sisa waktu dan aktivitas. Jangan menghubungkan menu Proktor ke view ini sebelum scope response API terbukti membatasi peserta ke room assignment.
- Route/view juga memiliki tautan Detail Hasil; capability Proktor tidak otomatis mencakup akses hasil/nilai. Link dan endpoint harus dibatasi berdasarkan capability yang disetujui.

### Assignment Proktor

- Agent 1 service `ExamProctorAssignmentService` blob `c968ce68c0decace1c35a0ccb3b2b4449c057656` memiliki `isEligibleGuru()` yang membaca role dari database dan mensyaratkan tepat satu role GURU, user aktif pada tenant, serta TeacherProfile tenant-local non-deleted. Ini bukti helper assignment service, bukan bukti semua entrypoint/UI sudah exact-role.
- UI Admin `ProctorAssignmentManagement` blob `9886feb0475281fc40797caf9b12926edf3b5862` memakai `whereHas(role=GURU)` untuk pilihan calon; query UI belum exact-one. Service harus tetap menjadi enforcement final dan UI sebaiknya diselaraskan agar tidak menampilkan kandidat invalid.
- UI Guru belum memiliki menu/route Proktor; layout Guru masih menampilkan placeholder monitoring dan menyatakan assignment dikelola Admin.

### Admin boundary

- Pemeriksaan statis tambahan: AdminDashboard, UserManagement, TenantProfileManagement, ProctorAssignmentManagement dan ExamRoomSessionManagement memakai `hasRole('ADMIN_MADRASAH')`, belum exact-one.
- AcademicManagement menggunakan role-exists, bukan exact-one.
- CurriculumManagement masih mengizinkan role literal legacy `ADMIN` untuk manage dan pada guard view awal hanya memeriksa permission. Ini jalur legacy yang berpengaruh langsung terhadap authorization dan harus diselaraskan dengan allowlist tiga portal.
- Ini temuan source Agent 3; belum diuji runtime. Perubahan menunggu kontrak exact-role bersama Agent 2.

## Status

Tidak ada source runtime/test yang diubah. Temuan ini menambah acceptance gate Agent 3:
1. Room-scoped monitoring contract/API dibuktikan sebelum UI Proktor aktif.
2. Admin/Guru boundary exact-one diterapkan pada mount, setiap action, dan route/API terkait setelah helper kontrak disetujui.
3. UI Admin assignment menyaring kandidat tepat satu role GURU, tetap mengandalkan service backend.
4. Proktor tidak memperoleh akses hasil/nilai/jawaban/Force Submit/room lain tanpa capability eksplisit.

Semua pengujian runtime untuk temuan ini: **NOT RUN**. Tidak ada database produksi disentuh.
