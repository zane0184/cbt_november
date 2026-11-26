# CHECKPOINT FILTER BANK SOAL BERDASARKAN PENUGASAN — 2026-11-27

## AUDIT
- Sebelumnya filter Bank Soal hanya menggunakan `subject_id`, sehingga dropdown hanya menampilkan nama mapel.
- Penugasan Guru tersedia melalui `TeacherPortalDataService::assignmentOptions()` dengan relasi subject, rombel, dan gradeLevel.
- Saat penyimpanan soal manual, `QuestionVersion.metadata.curriculum_mapping.teacher_subject_assignment_id` sudah disimpan, termasuk pada mode `NOT_MAPPED`.
- DOCX importer juga menyimpan `teacher_subject_assignment_id` ke metadata versi soal.
- Pemetaan kurikulum memiliki foreign key penugasan pada mapping CP dan outcome.

## DESAIN
- Filter Bank Soal menampilkan label: Mata Pelajaran · Kelas · Rombel.
- Filter memakai ID penugasan Guru, bukan hanya subject_id.
- Penyaringan soal dilakukan pada current version berdasarkan metadata penugasan atau mapping CP/TP/ATP yang terhubung ke penugasan tersebut.
- Filter kosong tetap menampilkan seluruh soal yang dapat diakses Guru sesuai cakupan tenant dan subject.
- Tidak membuat duplikasi soal per rombel; satu record soal tetap menjadi satu butir bank soal.

## IMPLEMENTASI
- `backend-laravel/app/Livewire/Admin/QuestionBank.php`
  - Mengganti state `subjectFilter` menjadi `assignmentFilter`.
  - Menambah reset pagination pada perubahan filter.
  - Menyaring current version berdasarkan assignment ID pada metadata atau relasi mapping.
  - Menghapus query subject options terpisah karena pilihan filter berasal dari assignment rows milik Guru.
- `backend-laravel/resources/views/livewire/admin/question-bank.blade.php`
  - Dropdown menampilkan `subject · gradeLevel · rombel`.
- `backend-laravel/tests/Feature/QuestionBankTest.php`
  - Memperbarui test reset pagination.
  - Menambahkan regresi untuk label Mapel/Kelas/Rombel dan penyaringan berdasarkan assignment.
- Backup dibuat sebelum patch:
  - `backend-laravel/backups/question-bank-assignment-filter-20261127/QuestionBank.php`
  - `backend-laravel/backups/question-bank-assignment-filter-20261127/question-bank.blade.php`
  - `backend-laravel/backups/question-bank-assignment-filter-20261127/QuestionBankTest.php`

## SHA
- QuestionBank.php: `15d889e39a02e1e12055c603f397a2edf3959c56`
- question-bank.blade.php: `1f561a58374112e312497d0c610fdebadb4090a4`
- QuestionBankTest.php: `06673ada404c63afd1290c9b4741375653e85ce7`
- Backup QuestionBank.php: `a44e0930d7659656747437fa0258062d7e2079ba`
- Backup Blade: `be8927670eca1d1114c5bf3d6dedba967f279863`
- Backup test: `bdb47f642dcfd0a648e618aef49614eeb18463ba`

## PENGUJIAN
- Semua tiga source/tes dan tiga backup berhasil dibaca ulang dari branch GitHub setelah penulisan; SHA diverifikasi.
- PHPUnit belum dijalankan.
- Browser/UI VPS belum diuji.
- Belum boleh menyatakan fitur filter berjalan di VPS sampai file diterapkan dan diuji.

## PENERAPAN
- Backup tiga file di VPS sebelum penggantian.
- Terapkan dua file runtime: Livewire component dan Blade view.
- File test hanya untuk repository/CI, tidak perlu ditempatkan pada runtime VPS.
- Tidak ada migration atau perubahan database.
- Setelah deploy, uji dropdown menampilkan Mapel · Kelas · Rombel dan uji hasil filter untuk penugasan terpilih.
