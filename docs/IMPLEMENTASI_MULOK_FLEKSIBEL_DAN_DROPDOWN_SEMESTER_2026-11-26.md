# Implementasi Fleksibilitas Mulok dan Dropdown Semester Penugasan Guru
Tanggal: 2026-11-26
Branch: work/rombel-docx-completion-20261126

## AUDIT
- Katalog `subjects` sebelumnya tidak membedakan mata pelajaran wajib dan muatan lokal.
- Sinkronisasi katalog resmi menghasilkan satu entri teknis `MUATAN_LOKAL` yang tampil seolah-olah satu mata pelajaran yang harus dipilih.
- Penugasan Guru mewajibkan setiap mata pelajaran memiliki pemetaan ke node resmi per fase. Mapel lokal buatan madrasah belum memiliki jalur pemetaan otomatis.
- Registry resmi KMA menyediakan node SUBJECT `FASE_A_MUATAN_LOKAL`, `FASE_B_MUATAN_LOKAL`, dan `FASE_C_MUATAN_LOKAL`. Node ini menjadi referensi umum, sedangkan nama mapel Mulok ditetapkan madrasah.
- Dropdown Semester sebelumnya menampilkan seluruh semester tenant tanpa bergantung pada rombel. Perubahan rombel tidak mengosongkan pilihan semester lama dan berpotensi memilih periode tahun ajaran yang salah. Label semester juga harus memakai `term` dan relasi `academicYear.name`.

## DESAIN
- Tambahkan `subjects.subject_category` dengan nilai `WAJIB` atau `MULOK`, default `WAJIB`.
- Data lama dengan kode teknis `MUATAN_LOKAL` ditandai `MULOK`, tetapi entri teknis tersebut disembunyikan dari katalog pilihan Admin dan dropdown penugasan.
- Admin dapat membuat beberapa mapel Mulok sendiri melalui Data Akademik & Rombel > Mata Pelajaran > Tambah Mata Pelajaran, mengisi kode, nama, singkatan, dan kategori.
- Mata pelajaran Mulok tenant dipetakan saat penugasan ke node resmi Muatan Lokal sesuai fase rombel. Registry resmi tidak diubah. Mapping yang pernah dinonaktifkan tidak diaktifkan kembali otomatis.
- Dropdown Semester mengikuti tahun ajaran Rombel yang dipilih. Ketika Rombel berubah, semester yang dipilih direset. Jika tidak ada semester untuk tahun ajaran tersebut, UI menampilkan instruksi yang jelas.
- Validasi tenant, role, status Guru, fase/kelas MI, sumber resmi, semester harus satu tahun ajaran dengan rombel, snapshot kurikulum, transaksi, dan audit assignment tetap dipertahankan.

## IMPLEMENTASI
- Migration baru: `backend-laravel/database/migrations/2026_11_26_000001_add_subject_category_for_local_content.php`.
- Model: `backend-laravel/app/Models/Subject.php` menambahkan `subject_category` ke fillable.
- UI katalog: `backend-laravel/app/Livewire/Admin/AcademicManagement.php` dan `resources/views/livewire/admin/academic-management.blade.php` menambahkan kategori, validasi kode unik per tenant, dan menyembunyikan entri teknis `MUATAN_LOKAL`.
- Sinkronisasi: `backend-laravel/app/Livewire/Admin/CurriculumRegistryManagement.php` menandai entri katalog resmi Muatan Lokal sebagai kategori `MULOK`.
- Penugasan: `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php` membuat/menemukan mapping ke node Muatan Lokal sesuai fase ketika mapel kategori Mulok ditugaskan; pilihan semester difilter sesuai tahun ajaran rombel.
- UI penugasan: `resources/views/livewire/admin/teacher-subject-assignment-management.blade.php` memberi label kategori pada pilihan mapel, memperbarui dropdown semester saat rombel berubah, dan memberi pesan saat semester tidak tersedia.
- Tes: `tests/Feature/AcademicYearManagementTest.php` mencakup pembuatan mapel Mulok; `tests/Feature/TeacherSubjectAssignmentManagementTest.php` mencakup pemetaan dan penugasan mapel Mulok serta refresh dropdown semester.
- Backup sebelum perubahan tersedia di `backend-laravel/backups/mulok-and-semester-dropdown-20261126/` untuk seluruh file yang diedit.

## PENGUJIAN
- Pemeriksaan source GitHub mengonfirmasi perubahan kategori, pemetaan fase Mulok, dan dropdown semester dependen pada source.
- Feature test ditambahkan, tetapi belum dinyatakan lulus sampai CI pada HEAD akhir selesai.
- Migration belum dijalankan pada VPS atau database produksi. Deployment memerlukan backup database, preflight migration, persetujuan eksplisit, dan verifikasi pascamigrasi.
- Tidak ada perubahan data produksi atau deployment pada tahap ini.
