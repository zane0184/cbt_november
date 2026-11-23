# WEB ADMIN REMEDIASI TAHAP 2 — AUDIT TAKSONOMI UJIAN & RUANG
Tanggal: 23 November 2026

## Status
AUDIT lanjutan selesai pada source branch `work/web-admin-implementation-20261123` dan dibandingkan dengan branch remediasi `work/web-admin-remediation-20261123`.
Belum ada perubahan source aplikasi pada tahap ini. Tidak ada migration dijalankan, database/VPS produksi tidak disentuh, dan PR #18 tetap draft serta belum di-merge.

## AUDIT

### 1. Identitas source yang diperiksa
- Repository: `zane0184/cbt_november`
- Branch basis implementasi: `work/web-admin-implementation-20261123`
- Commit basis PR #18: `e137e5b4f1ac42c899cd7cd308754fad79940e17`
- Branch remediasi: `work/web-admin-remediation-20261123`
- HEAD PR #18 saat pemeriksaan: `86ab3917960051936a8a27fee940b8a9bf5074d4`
- PR: https://github.com/zane0184/cbt_november/pull/18
- Status PR: open, draft=true, merged=false.

### 2. Klasifikasi UTS/UAS/UM belum tersedia sebagai kontrak data
Bukti source:
- `database/migrations/2024_01_01_000014_create_assessments_table.php`, blob `a767904f1a965e4531808f693c88306162dc8d88`: `assessment_type` didefinisikan sebagai `string('assessment_type')` dengan komentar contoh `DAILY, MIDTERM, FINAL, etc.`; tidak ada enum/constraint untuk UTS/UAS/UM.
- `app/Models/Assessment.php`, blob `03d4ad7b1c110dd871ec1cd7f54cbb7af208ded5`: `assessment_type` termasuk fillable biasa, tanpa enum/normalisasi.
- `database/factories/AssessmentFactory.php`, blob `e2582e3d33d55f7490c09a792a8becd9c467053a`: nilai contoh `SUMMATIVE`.
- `tests/Feature/ProctorAssignmentSecurityTest.php`, blob `9e8928da969faf1d3d867e8ba461a35d079f33d7`: skenario assignment juga memakai `SUMMATIVE`.
- Tidak ditemukan taxonomy data/enum UTS, UAS, UM dalam model dan migration yang diperiksa.

Kesimpulan: nama ujian atau teks judul tidak boleh dipakai untuk menebak kategori. Menggunakan pencocokan judul seperti mengandung kata "UTS" bukan validasi kanonis dan dapat salah klasifikasi.

### 3. Master ruang ujian belum ditemukan
Pemeriksaan daftar lengkap file pada:
- `database/migrations`
- `app/Models`

menunjukkan tidak ada migration/model ruang ujian seperti `Room`, `ExamRoom`, atau tabel ruang sejenis pada branch basis yang diperiksa.

Bukti skema terkait:
- `2024_01_01_000015_create_exams_table.php`, blob `3273d5a55112495fb8b4bd8af1626c45093f563b`: tabel `exams` tidak memiliki `room_id`.
- `2026_11_20_100001_create_exam_proctor_assignments_table.php`, blob `e51ef878e14d13078d81585fbbc10e1d00b76e25`: tabel assignment memiliki tenant, exam, Guru, assigner, interval, status, alasan, dan revoke metadata; tidak memiliki `room_id`.
- `app/Models/ExamProctorAssignment.php`, blob `dde7511ebb5035a76287d34dc75339a6a2bffdd8`: fillable dan relasi tidak mencakup ruang.

Kesimpulan: binding ruang dan validasi tenant/ruang tidak dapat diimplementasikan aman dengan menambahkan field UI saja. Diperlukan sumber data ruang dan relasi database yang benar.

### 4. Kondisi alur assignment saat ini
- `app/Livewire/Admin/ProctorAssignmentManagement.php`, blob basis `5395e274f1dc528b97cf777f3a3803e68aca8505`: daftar ujian mengambil seluruh ujian tenant berstatus PUBLISHED, terkunci, memiliki end_at, dan belum berakhir. Tidak ada filter UTS/UAS/UM.
- Pada branch remediasi, pemeriksaan akses komponen telah dibatasi menjadi hanya `ADMIN_MADRASAH` dengan permission `exam.proctor.assign` (blob `951b279ad8922f54f6d89d06774043b91a6a0e53`).
- `app/Policies/ExamPolicy.php`, blob basis `43ae9800eda99b7fbcc4c1d2ab1317650146d6c3`: `assignProctor()` memeriksa tenant, role Admin, permission, status terbit/terkunci, end_at, dan waktu berakhir; belum memeriksa kategori UTS/UAS/UM.
- `app/Services/ExamProctorAssignmentService.php`, blob `527da0faa173d2e6642841d659c4511a5ee41d24`: sudah memakai transaksi, lock ujian/Guru, validasi Guru tenant yang memiliki TeacherProfile, interval dalam batas ujian, pencegahan benturan interval Guru lintas ujian, dan audit log. Belum memvalidasi kategori ujian atau ruang.
- View assignment menampilkan judul ujian dan Guru; belum memiliki pemilih ruang.

Kesimpulan: perbaikan akses Admin pada Tahap 1 belum membuat fitur assignment sesuai batas UTS/UAS/UM + ruang.

## DESAIN — rancangan yang direkomendasikan (belum diimplementasikan)

### A. Kategori ujian eksplisit
Tambahkan atribut terstruktur pada entitas ujian, bukan inferensi dari judul. Nilai yang disarankan:
- `REGULAR` — ujian reguler yang dikelola Guru, tidak masuk daftar assignment Admin.
- `CENTRAL_UTS`
- `CENTRAL_UAS`
- `CENTRAL_UM`

Nilai default untuk data lama harus `REGULAR` agar ujian lama tidak otomatis berubah menjadi ujian terpusat. Guru yang berwenang menetapkan kategori pada saat membuat/mengelola ujian. Admin hanya boleh melihat metadata minimum dari tiga kategori CENTRAL tersebut untuk assignment Proktor.

Catatan desain: lokasi atribut final (Exam atau Assessment) perlu dipastikan terhadap alur pembuatan ujian yang akan diintegrasikan. Saat ini branch ini belum memuat Livewire Teacher builder yang menjadi titik input kategori, sehingga jangan membuat kolom tanpa menuntaskan kontrak input dan validasinya.

### B. Master ruang dan pengikatan assignment
Rancangan minimal:
- Master `exam_rooms` per tenant dengan ID UUID, tenant_id, kode/nama ruang, kapasitas, status aktif, timestamps, dan unique key tenant+kode.
- Tambahkan `room_id` ke `exam_proctor_assignments` dengan composite FK yang menjaga pasangan `room_id + tenant_id`.
- Assignment baru wajib memiliki ruang aktif pada tenant yang sama.
- Riwayat assignment lama harus dipertahankan; migration perlu strategi kompatibilitas untuk baris historis sebelum room_id diwajibkan bagi assignment baru.
- Service wajib mengunci/validasi ruang dan mencegah benturan penugasan Guru maupun penggunaan ruang yang tumpang tindih.
- Audit log harus mencatat ruang lama/baru ketika assignment diperbarui atau dicabut.

Belum ditetapkan apakah satu ruang boleh memiliki lebih dari satu Guru pengawas pada interval yang sama. Jangan menambahkan unique constraint satu-proktor-per-ruang sebelum kebijakan operasional ini dikonfirmasi dari spesifikasi madrasah.

### C. Batas data Admin
- Admin tidak memperoleh halaman daftar seluruh ujian.
- Pemilih ujian assignment hanya mengembalikan metadata minimum untuk ujian CENTRAL_UTS/UAS/UM.
- Admin hanya melihat riwayat assignment yang menjadi kebutuhan administrasi.
- Tidak ada akses Admin ke roster peserta, Attempt, progres, monitoring, jawaban, nilai, atau hasil ujian.
- Validasi kategori dan ruang harus berada di Policy/Service, bukan hanya filter UI.

## IMPLEMENTASI
Tidak ada source aplikasi yang diubah pada Tahap 2. Tidak ada backup source baru yang diperlukan karena tidak ada file source yang ditimpa. Dokumen audit ini merupakan file baru.

## PENGUJIAN
- Pemeriksaan statis struktur migration/model/component/service/view dilakukan melalui source GitHub.
- Tidak menjalankan PHPUnit, MySQL migration, browser E2E, VPS runtime, atau UAT pada tahap ini.
- Tidak ada status PASS yang diklaim.
- PR #18 tetap draft dan belum di-merge.

## Pekerjaan berikutnya
1. Tetapkan kontrak kategori ujian dan titik input pada alur Guru.
2. Tetapkan kebijakan jumlah Guru per ruang/ujian dan aturan konflik ruang.
3. Setelah desain tersebut konsisten, buat migration additive yang kompatibel dengan data lama; backup file sebelum setiap perubahan.
4. Implementasikan validasi kategori/ruang pada Policy, Service, Livewire, dan test.
5. Jalankan MySQL disposable/fresh-install CI serta seluruh tes terkait; jangan gunakan database produksi.
