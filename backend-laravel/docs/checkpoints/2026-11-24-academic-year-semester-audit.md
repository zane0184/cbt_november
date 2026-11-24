# Audit Awal Lifecycle Tahun Ajaran & Semester — 24 November 2026

## Status audit

**Audit source awal selesai. Implementasi lifecycle Tahun Ajaran/Semester belum dimulai.** Tidak ada source akademik atau migration yang diubah pada tahap audit ini.

- Repository: `zane0184/cbt_november`
- Branch: `work/solo-web-cbt-completion-20261123-execution`
- Audit basis HEAD: `0250dc257cc7292d960cacf2c20eda782ee8a25d`
- Penutupan Lifecycle Akun: `0250dc257cc7292d960cacf2c20eda782ee8a25d`
- Checkpoint penutupan akun: `backend-laravel/docs/checkpoints/2026-11-24-account-lifecycle-final.md`

## AUDIT

### Model yang sudah ada — dipertahankan

**`AcademicYear`** — `app/Models/AcademicYear.php`
- Model sudah ada, menggunakan UUID, `TenantScoped`, dan `SoftDeletes`.
- Fillable: `name`, `is_active`.
- Relasi: `semesters()` dan `rombels()`.
- Belum memiliki lifecycle service/method untuk aktivasi, penutupan, pengarsipan, atau pergantian periode.

**`Semester`** — `app/Models/Semester.php`
- Model sudah ada, menggunakan UUID, `TenantScoped`, dan `SoftDeletes`.
- Fillable: `academic_year_id`, `term`, `is_active`.
- Relasi: `academicYear()`.
- Nilai term pada schema: `GANJIL` dan `GENAP`.
- Belum ada aturan lifecycle/aktivasi yang mengoordinasikan semester dengan tahun ajaran.

Model yang ada tidak boleh dibuat ulang.

### Schema/migration existing

Migration sumber: `database/migrations/2024_01_01_000002_create_academic_tables.php`.

- `academic_years`: UUID, `tenant_id`, `name`, `is_active` default false, timestamps, soft deletes; unique `(tenant_id, name)` dan `(id, tenant_id)`.
- `semesters`: UUID, `tenant_id`, `academic_year_id`, enum `term` GANJIL/GENAP, `is_active` default false, timestamps, soft deletes; unique `(academic_year_id, term)`; FK komposit `(academic_year_id, tenant_id)` ke `academic_years`.
- Belum ada unique constraint untuk memastikan hanya satu AcademicYear aktif per tenant atau satu Semester aktif per AcademicYear.
- Tabel `semesters` belum mempunyai unique key `(id, tenant_id)` yang dapat menjadi target FK komposit dari tabel turunan.

### Relasi data akademik yang telah menggunakan periode

- `Rombel` memiliki `academic_year_id` dan relasi ke `AcademicYear`.
- `TeacherSubjectAssignment` menyimpan `academic_year_id` dan `semester_id`; keduanya wajib pada migration assignment. Kombinasi Guru/mapel/rombel/tahun/semester menjadi bagian unique key.
- Controller penugasan Guru dan Livewire Admin sudah memvalidasi tenant dan memastikan semester serta rombel berada pada tahun ajaran yang sama.
- Penugasan Guru adalah konteks otoritatif untuk kewenangan pembelajaran/asesmen; jangan mengganti relasi penugasan tersebut dengan tebakan periode aktif.
- `Assessment` memiliki `academic_year_id` dan `semester_id`; relasi model tersedia. Schema awal membuat `semester_id` nullable, sedangkan daftar asesmen Guru menolak data yang belum memiliki tahun dan semester serta mencocokkan dengan penugasan Guru.
- `Exam` mengacu ke `Assessment`; hasil ujian harus tetap merujuk ke konteks akademik saat ujian dibuat/dilaksanakan.

### UI dan alur yang sudah ada

- `Admin/AcademicManagement.php` hanya memiliki tab Rombel, Mata Pelajaran, dan Tingkat Kelas/Fase.
- Komponen tersebut dapat membuat Rombel dengan tahun ajaran terpilih dan membuat mata pelajaran. Belum ada CRUD/lifecycle Tahun Ajaran atau Semester.
- Dashboard Admin mengambil `AcademicYear::where(is_active, true)->first()`. Bila tidak ada tahun aktif, dashboard menampilkan keadaan kosong. Bila terdapat lebih dari satu tahun aktif, `first()` tidak menentukan periode secara eksplisit.
- Dashboard belum memilih atau menampilkan semester aktif.
- `TeacherSubjectAssignmentManagement` menampilkan semester tenant dan memvalidasi kecocokan dengan tahun ajaran rombel.
- Belum ditemukan halaman/menu Admin untuk aktivasi, penutupan, atau arsip tahun ajaran dan semester.

### Temuan integritas yang harus diselesaikan pada implementasi

1. **Tidak ada aturan satu periode aktif.** Kolom boolean hanya menyimpan status; tidak ada service/constraint yang mencegah beberapa tahun aktif dalam satu tenant atau beberapa semester aktif dalam satu tahun.
2. **Semester lintas tenant belum dicegah penuh oleh FK database.** Migration penugasan Guru dan Assessment memakai FK tunggal ke `semesters.id`; validasi aplikasi sudah membatasi tenant, tetapi database belum mempunyai FK komposit `(semester_id, tenant_id)`. Perlu unique key `semesters(id, tenant_id)` dan FK komposit dalam migration tambahan setelah preflight.
3. **Hard delete berisiko merusak histori.** FK `academic_year_id` dari Rombel dan Assessment memakai cascade; FK `semester_id` pada Assessment memakai set-null. Model menggunakan SoftDeletes, tetapi hard delete/forceDelete tetap dapat menghapus relasi atau mengosongkan referensi semester. Lifecycle baru harus non-destruktif dan tidak menyediakan hard-delete.
4. **Semester name tidak ada pada schema.** Migration hanya memiliki `term`; namun `TeacherSubjectAssignmentManagement` membentuk snapshot `semester_name` dari `$semester->name`. Field tersebut tidak ada di model/migration saat ini, sehingga snapshot dapat berisi null. Gunakan label dari `term` atau tetapkan kontrak label secara eksplisit; jangan mengasumsikan kolom `name` tersedia.
5. **Factory tahun ajaran membuat `is_active=true` secara default.** Factory bukan aturan produksi, tetapi fixture yang membuat beberapa tahun dapat menghasilkan beberapa periode aktif jika tidak dinonaktifkan secara eksplisit.
6. **Semester belum memiliki pengujian lifecycle khusus.** Pengujian yang ditemukan menguji fixture, relasi penugasan, dan dashboard tanpa tahun aktif; belum ada acceptance untuk transisi aktif/nonaktif, tenant isolation lifecycle, histori, atau konkurensi aktivasi.

### Berkas yang diperiksa

- `app/Models/AcademicYear.php`
- `app/Models/Semester.php`
- `app/Models/Rombel.php`
- `app/Models/Assessment.php`
- `app/Models/TeacherSubjectAssignment.php`
- `app/Livewire/Admin/AcademicManagement.php`
- `app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`
- `app/Livewire/Admin/Dashboard.php`
- `app/Http/Controllers/Api/V1/AssessmentController.php`
- `app/Http/Controllers/Api/V1/TeacherSubjectAssignmentController.php`
- `database/migrations/2024_01_01_000002_create_academic_tables.php`
- `database/migrations/2026_11_07_210001_create_teacher_subject_assignments_table.php`
- `database/migrations/2024_01_01_000014_create_assessments_table.php`
- `database/factories/AcademicYearFactory.php`
- `tests/Feature/AdminUiVerificationTest.php`
- `tests/Feature/RombelStudentPivotTest.php`

## DESAIN awal yang disarankan

1. Pertahankan model dan UUID yang sudah ada; tambahkan service dan UI pada struktur existing.
2. Pengelolaan periode berada di portal Admin Madrasah dengan permission akademik; tidak menambah portal baru.
3. Tahun ajaran dan semester dinonaktifkan/ditutup/diarsipkan, bukan dihapus. Histori rombel, penugasan, asesmen, ujian, nilai, dan snapshot tetap tersimpan.
4. Aktivasi dan penutupan dilakukan transaksional dengan row lock, validasi tenant, validasi keterkaitan semester-tahun, audit log, dan proteksi dari dua aktivasi bersamaan.
5. Penugasan Guru tetap menjadi sumber kewenangan aktual; mengubah periode aktif tidak boleh memindahkan atau mengubah penugasan/asesmen lama.
6. Tambahkan FK komposit tenant untuk referensi semester setelah memeriksa constraint yang sudah ada dan menyiapkan migration upgrade yang aman.
7. Dashboard hanya membaca periode aktif yang ditetapkan secara eksplisit; kondisi data aktif ganda harus ditolak/dideteksi, bukan diselesaikan dengan `first()`.

## PENGUJIAN yang diperlukan pada tahap implementasi

- Satu tahun ajaran aktif per tenant (setelah aturan disepakati), dan tidak memengaruhi tenant lain.
- Satu semester aktif dalam lingkup tahun ajaran (setelah aturan disepakati); semester harus milik tahun ajaran dan tenant yang benar.
- Aktivasi bersamaan pada dua koneksi MySQL 8/InnoDB: hasil konsisten, tanpa dua periode aktif.
- Menutup periode lama dan mengaktifkan periode baru tidak mengubah rombel, assignment, assessment, exam, attempt, answer, score, snapshot kurikulum, atau histori.
- Menolak hard delete terhadap periode yang sudah memiliki histori/relasi.
- Isolasi tenant untuk UI dan endpoint.
- Role/permission Admin yang tepat; Guru tidak dapat mengelola periode.
- Snapshot penugasan dan assessment tetap menunjuk periode asal.
- Upgrade migration diuji terhadap schema sebelumnya pada database disposable MySQL 8, bukan database produksi.
- PHPUnit penuh dan pemeriksaan InnoDB setelah perubahan.

## Gerbang keputusan sebelum implementasi

Belum ada keputusan pengguna yang ditemukan tentang cardinality status aktif. Konfirmasi satu aturan ini sebelum menambahkan constraint/service aktivasi:

**Apakah aturan yang dikehendaki adalah tepat satu Tahun Ajaran aktif per tenant, dan tepat satu Semester aktif dalam Tahun Ajaran yang sedang aktif?**

Tidak ada migration produksi, deployment, merge, atau perubahan konfigurasi produksi yang dilakukan.
