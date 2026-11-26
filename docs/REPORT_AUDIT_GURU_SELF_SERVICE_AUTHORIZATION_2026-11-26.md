# Audit Statis Self-Service Kelas Guru — Lanjutan
Tanggal: 26 November 2026
Repository: `zane0184/cbt_november`
Branch: `integration/zip-vps-php83-20261126`

## AUDIT

File source yang diperiksa pada HEAD integrasi:
- `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`
  - Blob: `5a6546c9176188e113c7e93ebb7f531595c42490`
- `backend-laravel/app/Livewire/Teacher/TeacherAssignmentsPage.php`
  - Blob: `9e3bb0766023448fc27c1fc57789fa89be0dae6f`
- `backend-laravel/resources/views/livewire/admin/teacher-subject-assignment-management.blade.php`
  - Blob: `f66994fae74dda8fff265c681e28f54cad6d8a95`
- `backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php`
  - Blob: `191ca62e59f2137391cafcf705ef49434d3db640`

Temuan:
1. `authorizeAcademicManagement()` menolak akun nonaktif, tanpa tenant, atau lebih dari satu role. Guru harus lolos `TeacherPortalAccess::profile()`; Admin harus role tunggal `ADMIN_MADRASAH` dan permission `academic.manage`.
2. `createAssignment()` dan `createBulkAssignments()` memanggil `authenticatedTeacherProfile()`, yang secara eksplisit hanya mengizinkan role tunggal `GURU`. Jadi Admin yang memanggil Livewire action langsung tetap ditolak, bukan hanya disembunyikan oleh UI.
3. Identitas Guru ditimpa dari profil akun login pada server sebelum validasi; `teacher_profile_id` dari browser tidak menjadi sumber otoritatif.
4. Registrasi kurikulum ACTIVE harus tepat satu; jika nol atau lebih dari satu, pembuatan ditolak.
5. Validasi assignment memeriksa tenant untuk Guru, subject, rombel, semester, registrasi; fase A/B/C dan kelas 1–6; mapping subject pada phase node; status/provenance node; semester harus pada tahun ajaran rombel; serta duplikasi assignment aktif yang tumpang tindih.
6. Penugasan disimpan dengan `created_by`, versi/owner kurikulum, dan snapshot konteks; audit event `TEACHER_SUBJECT_ASSIGNMENT_CREATED` dicatat.
7. Bulk create satu rombel dan banyak mapel berada dalam transaksi, memanggil ulang jalur validasi kanonis per mapel.
8. Blade menampilkan form pembuatan hanya pada `@if($isTeacher)`; Admin melihat monitoring read-only. Tampilan Admin tidak memiliki form create.
9. Route Guru memakai `TeacherAssignmentsPage`, yang mewarisi komponen kanonis; tidak ada duplikasi service/tabel.
10. Test feature menegaskan Admin tidak dapat memanggil `createBulkAssignments`, Guru membuat assignment, pemalsuan profile ID tidak mengganti pemilik, dan `created_by` berasal dari akun Guru.

## DESAIN
- Pertahankan komponen bersama agar validasi tidak terduplikasi, tetapi pertahankan authorization server-side pada setiap action.
- Admin tetap monitoring; Guru menjadi pemilik tindakan create.
- Tambahkan test langsung untuk percobaan Admin memanggil `createAssignment` selain bulk pada tahap regression berikutnya bila PHPUnit dijalankan.
- Jangan mengubah workflow ini hanya untuk merapikan UI; bukti source saat ini konsisten dengan keputusan self-service.

## IMPLEMENTASI
- Tidak ada source aplikasi yang diubah dalam audit ini.
- Hanya dokumentasi audit ditambahkan.

## PENGUJIAN
- Source review statis: PASS untuk guard Admin/Guru, pemaksaan identitas dari sesi, tenant scoping, snapshot, dan audit log.
- PHPUnit test yang ada belum dieksekusi pada sesi ini.
- Browser UAT dan VPS UAT belum dilakukan.
- Karena itu belum ada klaim fungsional runtime PASS.

## STATUS
**STATIC SOURCE REVIEW PASS — PHPUnit/BROWSER/VPS NOT VERIFIED.**
