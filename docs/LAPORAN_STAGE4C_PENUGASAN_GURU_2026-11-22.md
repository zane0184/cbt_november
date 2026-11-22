# LAPORAN IMPLEMENTASI CBT MADRASAH — STAGE 4C
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4b-room-session-20261122
HEAD laporan: 3fbf3b4a976298abc96aadca0db84bdf9f94cd4a

## AUDIT

### Posisi repository dan checkpoint
- Repository private ditemukan dan branch acuan tersedia.
- HEAD branch sebelum Stage 4C: `3bf04829a07930ecc0e4e97de45501795248ad69`. Commit tersebut hanya menambahkan snapshot backup Stage 4B; source aplikasi yang dirujuk manifest masih berada pada source HEAD `32bb76634d3390f8aeefade339fbb044394c783c`.
- Branch `work/stage4b-room-session-20261122` divergen dari `main`; tidak dilakukan merge atau rebase.
- README dan `composer.json` menegaskan target Laravel 13, PHP `^8.3`, MySQL 8+ InnoDB, Blade/Livewire.
- Checkpoint Stage 4A menyebut CI run `44952383038` SUCCESS pada source yang saat itu diuji. Itu bukti historis Stage 4A, bukan bukti untuk HEAD Stage 4C.
- Checkpoint Stage 4B `docs/CHECKPOINT_STAGE4B_ROOM_SESSION_2026-11-22.md` menyebut run `44957719985` SUCCESS, 110 test/559 assertions pada suite terpilih. Namun checkpoint tersebut mencatat HEAD `81b6c74d...`, bukan source HEAD terbaru yang dirujuk manifest Stage 4B.
- Manifest backup Stage 4B mencatat penambahan dua skenario race MySQL, koreksi alias metadata InnoDB, koreksi teardown migration, dan koreksi urutan drop FK sebelum index. Tidak ditemukan bukti CI baru yang menguji source setelah koreksi rollback terakhir. Karena itu Stage 4B belum boleh ditutup berdasarkan checkpoint lama.
- Source Stage 4B saat ini sudah memiliki UI edit/simpan/status ruang dan dua test multi-connection: konflik attach ruang dan konflik konfirmasi Proktor. Lifecycle otomatis EXPIRED/COMPLETED belum terbukti lengkap. Penempatan individual lintas ruang tidak dibuka untuk Admin; desain Stage 4B menempatkan kebutuhan pemecahan rombel sebagai workflow terpisah yang dimiliki Guru bila kelak diperlukan.
- Stage 4C sebelum perubahan telah memiliki model `TeacherSubjectAssignment` dan API controller `TeacherSubjectAssignmentController`, termasuk validasi tenant, hubungan tahun ajaran/rombel/semester, audit, dan penanganan unique conflict. Tetapi `routes/web.php` dan navigasi layout belum menyediakan halaman Livewire Admin untuk pengelolaan penugasan tersebut.

### Status CI terbaru
- Workflow `wa01-alternative-isolated-runtime.yml` menggunakan PHP 8.3 dan MySQL 8.0 disposable, serta dipicu push pada branch Stage 4B untuk perubahan backend/workflow.
- Workflow kini ditambah agar menjalankan dua test Stage 4C.
- Pemeriksaan status commit melalui konektor GitHub mengembalikan daftar status kosong; daftar workflow run yang tersedia juga kosong karena endpoint konektor tersebut hanya menampilkan run yang dipicu pull request. Maka CI untuk HEAD `3fbf3b4a...` belum dapat diverifikasi dari sesi ini. Tidak ada klaim PASS.

## DESAIN

- Halaman baru hanya untuk Admin Madrasah dengan role `ADMIN_MADRASAH`, tenant aktif, dan permission `academic.manage`. Pemeriksaan dilakukan pada lifecycle Livewire, bukan hanya dengan menyembunyikan menu.
- Data Guru harus terhubung ke akun aktif dengan role GURU dan TeacherProfile tenant yang sama.
- Relasi Guru, mata pelajaran, rombel, tahun ajaran, dan semester divalidasi ulang di dalam transaksi dan harus berada pada tenant yang sama. Rombel dan semester harus berada pada tahun ajaran yang sama.
- Create, view, edit, dan deactivate disediakan. Deactivate mengubah status menjadi INACTIVE; record tidak dihapus.
- Pengulangan payload yang identik diperlakukan idempotent. Kombinasi penugasan yang sama dengan payload berbeda ditolak, bukan membuat histori duplikat.
- Tenant row dikunci dalam transaksi untuk menyerialisasi penulisan pada tenant yang sama; unique database yang sudah dirujuk API menjadi pertahanan terakhir terhadap duplikasi.
- Setiap create/update/deactivate dicatat melalui AuditService.
- Tanggal mulai/berakhir tersedia dan nullable sesuai kontrak API yang sudah ada. Bila kebijakan final mengharuskan keduanya wajib, kontrak API dan UI perlu diselaraskan dalam patch terpisah.
- Tidak dibuat aturan konflik jadwal mengajar lintas penugasan karena source yang diperiksa belum menyediakan model jadwal pelajaran yang dapat menjadi dasar konflik waktu. Konflik yang diuji pada tahap ini adalah duplikasi identitas penugasan dan konsistensi periode akademik.

## IMPLEMENTASI

### File baru
- `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`
- `backend-laravel/resources/views/livewire/admin/teacher-subject-assignment-management.blade.php`
- `backend-laravel/tests/Feature/TeacherSubjectAssignmentManagementTest.php`
- `backend-laravel/tests/Feature/TeacherSubjectAssignmentConcurrencyTest.php`

### File diubah
- `backend-laravel/routes/web.php`: route `/admin/teacher-subject-assignments`.
- `backend-laravel/resources/views/layouts/app.blade.php`: navigasi desktop dan mobile di bawah permission `academic.manage`.
- `.github/workflows/wa01-alternative-isolated-runtime.yml`: menambahkan eksekusi dua test Stage 4C pada MySQL 8 disposable.

### Backup
- Backup route, layout, workflow sebelum dua pembaruan CI, dan test sebelum penambahan kasus tenant/conflict disimpan di `docs/backup/stage4c-teacher-subject-assignment-2026-11-22/`.
- Manifest utama dan supplement mencatat source blob SHA, ukuran, dan commit backup.
- File baru tidak memiliki source terdahulu sehingga tidak membutuhkan snapshot pre-change.
- Tidak ada migration, database, ZIP baseline, VPS, atau deployment yang diubah.

### Commit
- Implementasi Livewire: `be9193956896f14d318d96642723a3c36e0aabef`
- View: `1af51b5830343771fc7ec7cba16cfa0d5a49e101`
- Route: `d1a6aef0dc20b4d525f76812776bdc2d6521a931` (blob)
- Layout: `69a5f972e36a7676817c5227238ba8dd879abd81` (blob)
- Feature test: `46243ef29f2b677332027410e6531d63f209ab17`
- MySQL concurrency test: `1eb34db8cbef0129f437a8b57392043e83e5b288`
- CI workflow terakhir: `3fbf3b4a976298abc96aadca0db84bdf9f94cd4a`
- HEAD branch saat laporan: `3fbf3b4a976298abc96aadca0db84bdf9f94cd4a`

## PENGUJIAN

Test baru ditulis, belum dinyatakan lulus:
- Feature test mencakup create + idempotensi, deactivate tanpa delete, konsistensi tahun ajaran, penolakan Guru lintas tenant, konflik duplicate key, validasi rentang tanggal, dan penolakan non-Admin.
- MySQL concurrency test memakai dua koneksi, mengunci row tenant pada koneksi pertama, memverifikasi writer kedua tertahan, lalu memastikan retry melihat assignment yang sudah ada.
- Workflow CI mengarahkan kedua test tersebut ke MySQL 8 disposable/InnoDB dan PHP 8.3.

Belum dijalankan/terverifikasi dalam sesi ini:
- `php -l` dan PHPUnit pada source terbaru.
- Eksekusi CI pada commit `3fbf3b4a...`.
- Hasil MySQL 8 concurrency untuk test Stage 4C dan final rollback Stage 4B.
- Browser E2E Admin, Guru, Siswa, dan UAT madrasah.

Tidak ada test yang dihapus. Tidak ada klaim PASS untuk test baru.

## STATUS DAN TINDAK LANJUT

**Stage 4C: IMPLEMENTASI AWAL / PARTIAL — menunggu CI dan verifikasi runtime.**

**Stage 4B: PARTIAL — source telah berkembang setelah checkpoint, tetapi CI final sesudah koreksi migration rollback belum terbukti.**

Gate minimum Admin belum dinyatakan lulus. Web Guru belum dimulai pada tahap ini karena hasil MySQL 8/CI untuk source terbaru dan batas Admin terhadap CBT harus dibuktikan pada commit aktual.

Langkah berikutnya:
1. Verifikasi hasil push workflow untuk commit terbaru yang sama dengan source.
2. Perbaiki setiap kegagalan lint/test tanpa menghapus test.
3. Pastikan migration Stage 4B fresh-install/rollback dan dua race test lulus pada MySQL 8 disposable.
4. Tambahkan bukti test API/Livewire penugasan Guru dan tenant isolation.
5. Setelah seluruh delapan syarat gate minimum Admin benar-benar lulus, langsung mulai Web Guru; fitur administratif minor tetap backlog.
6. Lanjutkan Stage 4D–4G tanpa mengubah histori akademik atau batas Admin terhadap CBT.
