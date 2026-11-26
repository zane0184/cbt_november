# Rancangan Teknis — Self-Service Kelas dan Penugasan Guru
Tanggal: 26 November 2026

- Repository: `zane0184/cbt_november`
- Branch: `work/admin-import-curriculum-fixes-20261126`
- Basis source yang diaudit: `2308754139b7f8c5d3785c7571e37e60fbae0f00`
- Status: DESAIN DISETUJUI — BELUM IMPLEMENTASI

## 1. Keputusan produk

Guru membuat sendiri kelas pembelajaran dan memilih mata pelajaran serta rombel yang diampu dari akun Guru. Tidak ada persetujuan Admin untuk mengaktifkan penugasan yang valid.

Admin Madrasah mengelola data induk akademik (akun, mata pelajaran, rombel, peserta didik, tahun pelajaran, semester, registrasi kurikulum), memonitor kelas/pengampu, dan melakukan koreksi administratif beralasan serta tercatat. Admin tidak lagi menjadi operator input satu per satu untuk penugasan normal.

## 2. Temuan audit source

1. Tabel `teacher_subject_assignments` sudah menjadi relasi kanonis Guru–mapel–rombel–tahun ajaran–semester, dengan status, pembuat, tanggal berlaku, dan snapshot kurikulum yang ditambahkan oleh perubahan sebelumnya.
2. `created_by` tersedia untuk identitas pembuat penugasan.
3. Migration `2026_11_07_210002_harden_teacher_assignment_tenant_foreign_keys.php` menambahkan foreign key tenant-aware, termasuk relasi Guru, mapel, rombel, tahun ajaran, semester, dan pembuat.
4. `TeacherPortalAccess` memeriksa akun aktif, tepat satu role `GURU`, tenant, dan profil Guru yang unik. `TeacherPortalComponent::boot()` mengulang pemeriksaan pada request Livewire.
5. `TeacherAssignmentsPage` dan `portal-assignments.blade.php` saat ini hanya membaca daftar penugasan; UI secara eksplisit menyebut Admin sebagai pembuat.
6. `TeacherSubjectAssignmentManagement` milik Admin saat ini memuat proses validasi akademik yang panjang: Guru aktif, mapel tenant, rombel aktif dan fase, semester satu tahun dengan rombel, registrasi kurikulum aktif, node fase/kelas, mapping mapel resmi/provenance, snapshot, duplikasi, dan audit.
7. Tidak perlu membuat tabel kelas kedua pada tahap awal. Satu `TeacherSubjectAssignment` akan menjadi identitas ruang kelas/pengampu kanonis. Modul Guru berikutnya tetap merujuk assignment ID yang sama.

## 3. Desain UI Portal Guru

Menu Admin lama "Penugasan Guru" dialihkan menjadi "Monitoring Kelas & Pengampu" (read-only untuk operasi normal; tindakan koreksi/penonaktifan khusus tetap berizin dan diaudit).

Menu Guru "Penugasan Saya" diganti/ditingkatkan menjadi "Kelas Saya" dengan:
- Ringkasan jumlah mapel/kelas, rombel, dan peserta didik.
- Filter tahun pelajaran, semester, fase/kelas, rombel, mapel, dan status.
- Daftar kartu/daftar kelas dengan pintasan ke TP/ATP, Materi, Bank Soal, Asesmen/Ujian, dan daftar siswa.
- Tombol "Buat Kelas / Tambah Mapel".
- Riwayat kelas yang tidak aktif tetap dapat dilihat.

### Wizard pembuatan

1. Periode akademik: tahun ajaran dan semester aktif dari konfigurasi Admin dipilih otomatis. Guru tidak membuat/mengaktifkan periode akademik.
2. Rombel: pilih satu atau beberapa rombel aktif pada tahun ajaran terpilih.
3. Mapel: pilih satu atau beberapa mapel yang aktif dan sah untuk fase/kelas rombel.
4. Review: tampilkan kombinasi mapel–rombel yang tepat. Jangan membuat Cartesian product tanpa review. Guru dapat menghapus kombinasi yang tidak diampu.
5. Simpan: seluruh kombinasi dikirim sekali dan disimpan atomik.
6. Setelah berhasil, kelas muncul di "Kelas Saya" tanpa menunggu persetujuan Admin.

## 4. Aturan domain dan validasi server

Semua validasi wajib diulang di server; validasi UI hanya untuk kenyamanan.

- Identitas Guru dan tenant diturunkan dari session/auth + `TeacherPortalAccess`, bukan dari ID yang dikirim browser.
- Akun harus aktif, role tepat satu `GURU`, profil Guru unik, dan tenant konsisten.
- Mapel harus aktif, tidak soft-deleted, dan milik tenant sendiri.
- Rombel harus aktif, tidak soft-deleted, milik tenant sendiri, memiliki GradeLevel dan Phase yang sah, dan berada di tahun ajaran yang dipilih.
- Semester harus milik tenant yang sama dan tahun ajarannya harus sama dengan rombel.
- Registrasi kurikulum harus aktif untuk tenant dan versi yang sah. Guru tidak memilih versi kurikulum secara bebas; sistem mengambil registrasi aktif yang berlaku.
- Mapping mapel harus sesuai fase rombel dan node/sumber kurikulum memenuhi status verifikasi yang disyaratkan. Mulok memakai mapping resmi Muatan Lokal sesuai fase, sedangkan nama mapel lokal tetap milik tenant.
- Simpan snapshot konteks kurikulum agar perubahan registry berikutnya tidak mengubah histori kelas lama.
- Periksa duplikasi sebelum insert dan tetap tangani unique-constraint violation untuk race condition.
- Semua kombinasi batch diproses dalam satu transaksi. Jika satu kombinasi tidak valid/gagal, seluruh batch rollback dan UI menampilkan baris yang bermasalah.
- Tidak ada input `tenant_id`, `teacher_profile_id`, `created_by`, atau status aktif yang dipercaya dari browser.

## 5. Duplikasi dan pengampu bersama

- Guru yang sama tidak boleh membuat dua penugasan aktif identik untuk mapel, rombel, tahun ajaran, dan semester yang sama.
- Bila mapel–rombel yang sama sudah diampu Guru lain, tampilkan peringatan "sudah ada pengampu". Jangan menganggapnya otomatis duplikasi lintas Guru karena madrasah dapat memiliki pengampu bersama/team teaching. Catat pengampu lain pada ringkasan sebelum konfirmasi.
- Constraint unik yang ada pada database tetap menjadi pengaman duplikasi identik per Guru. Konflik lintas Guru memerlukan aturan bisnis tersendiri bila kelak madrasah ingin melarang co-teaching.

## 6. Data, lifecycle, dan histori

- Gunakan tabel `teacher_subject_assignments` yang ada; jangan membuat tabel `classrooms` paralel.
- `created_by` diisi dengan ID akun Guru yang sedang login. Relasi creator tenant-aware tetap berlaku.
- Assignment menjadi immutable pada identitas inti setelah dipakai membuat TP/ATP, materi, soal, asesmen, ujian, atau snapshot. Perubahan mapel/rombel bukan edit identitas lama; tutup/nonaktifkan dengan alasan lalu buat assignment baru.
- Penonaktifan oleh Guru sendiri hanya boleh untuk assignment miliknya dan harus menyertakan alasan. Penonaktifan Admin adalah tindakan koreksi administratif khusus dengan permission, alasan, dan audit.
- Jangan hard-delete assignment dan jangan memindahkan assignment lintas Guru/tenant.
- Histori kelas lama tetap terbaca; assignment baru untuk periode akademik baru memiliki konteks/snapshot baru.

## 7. Audit log

Gunakan `AuditService` dalam transaksi yang sama. Action yang disarankan:
- `TEACHER_CLASS_ASSIGNMENT_CREATED` — satu per assignment yang tercipta; actor Guru.
- `TEACHER_CLASS_ASSIGNMENT_BATCH_CREATED` — ringkasan batch, jumlah berhasil, periode dan correlation/batch ID.
- `TEACHER_CLASS_ASSIGNMENT_DEACTIVATED` — assignment, alasan, actor.
- `ADMIN_TEACHER_ASSIGNMENT_CORRECTED` — tindakan koreksi Admin, old/new values dan alasan.

Audit payload hanya memuat identitas dan konteks yang diperlukan; jangan menyimpan password, token, atau data rahasia.

## 8. Refactor backend

1. Ekstrak logika validasi/pembuatan dari `App\\Livewire\\Admin\\TeacherSubjectAssignmentManagement::createAssignment()` ke service domain khusus, misalnya `App\\Services\\TeacherAssignmentService`.
2. Sediakan method batch yang menerima daftar pasangan subject_id–rombel_id serta konteks periode; tenant dan teacher profile diberikan dari server-side authenticated context.
3. Gunakan transaksi DB, lock pada entitas referensi penting, validasi kurikulum dan tenant, duplicate check, snapshot, audit, serta unique constraint.
4. Tambahkan action Livewire Guru khusus "Kelas Saya" untuk preview dan submit batch. Komponen Guru memanggil service dengan profil hasil `TeacherPortalAccess`.
5. Ubah Admin component menjadi halaman monitoring. Hapus fungsi create assignment normal dari alur Admin; sediakan tindakan koreksi terbatas jika benar-benar dibutuhkan.
6. Hubungkan Bank Soal, TP/ATP, Materi, Asesmen/Ujian hanya ke assignment yang dimiliki Guru tersebut, bukan hanya ke subject ID.

## 9. Rute dan akses

- Tambah route di grup `/guru` yang sudah memakai middleware `auth`, `active.user`, `tenant`, `license`, dan `EnsureTeacherPortalAccess`.
- Semua method Livewire wajib memeriksa ulang akses dan ownership pada setiap request.
- Route/Admin monitoring tetap dilindungi role Admin Madrasah dan permission akademik.
- Tidak menerima teacher profile, tenant, atau owner ID dari browser sebagai sumber otorisasi.

## 10. Regression dan acceptance test

Wajib ditambahkan sebelum implementasi dianggap selesai:
1. Guru dapat membuat satu assignment miliknya tanpa persetujuan Admin.
2. Guru dapat membuat batch beberapa mapel–rombel; semua tersimpan dan muncul di Kelas Saya.
3. Batch rollback seluruhnya jika satu kombinasi tidak valid.
4. Guru tidak dapat membuat assignment untuk akun Guru lain.
5. Guru tidak dapat mengirim tenant ID/profil Guru lain untuk menyusup lintas tenant.
6. Guru tidak dapat memilih mapel nonaktif/soft-deleted atau mapel yang tidak cocok dengan fase.
7. Guru tidak dapat memilih rombel nonaktif/lintas tenant/tahun ajaran lain.
8. Semester harus sesuai dengan tahun ajaran rombel.
9. Registrasi kurikulum dan subject mapping harus sah; Mulok terpetakan ke node resmi fase.
10. Duplikasi Guru yang sama ditolak termasuk concurrent request.
11. Pengampu bersama lintas Guru memunculkan warning dan tercatat dengan benar.
12. Audit log merekam actor, assignment, batch, periode, snapshot dan alasan penonaktifan.
13. Assignment lama dan histori asesmen/ujian tidak berubah ketika assignment baru dibuat atau assignment lama dinonaktifkan.
14. Admin tidak dapat membuat penugasan normal dari halaman monitoring.
15. Seluruh fitur existing Guru (TP/ATP, bank soal, materi, asesmen) tetap bekerja dalam assignment scope yang benar.

## 11. Migrasi dan deployment

- Desain awal tidak memerlukan tabel baru dan tidak memerlukan migration database jika kolom/constraint yang diperiksa tetap sesuai.
- Sebelum implementasi, verifikasi semua migration terkait assignment pada urutan dan schema target.
- Tidak menjalankan migration di database produksi untuk rancangan ini.
- Tidak melakukan deployment VPS sampai source direview, backup dan preflight selesai, serta pengguna menyetujui.
- PHPUnit/full CI tidak dijalankan kecuali pengguna meminta; lakukan PHP runtime dan pengujian terarah sesuai protokol proyek.

## 12. Batas tahap

Tahap ini hanya menetapkan desain teknis. Belum ada source aplikasi yang diubah, migration yang dibuat/dijalankan, database/VPS yang disentuh, atau klaim pengujian.

Status: **DESAIN DISETUJUI — MENUNGGU TAHAP IMPLEMENTASI.**
