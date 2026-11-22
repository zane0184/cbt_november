# CHECKPOINT — CBT MADRASAH STAGE 4C
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4b-room-session-20261122
HEAD: 3fbf3b4a976298abc96aadca0db84bdf9f94cd4a

## Posisi
- Stage 4A: checkpoint historis menyebut CI SUCCESS; bukan bukti untuk source terkini.
- Stage 4B: implementasi ruang/sesi, mapping rombel, assignment Proktor, scope monitoring, dan test multi-connection tersedia. Checkpoint Stage 4B lama tidak mencakup koreksi migration terakhir. Belum ada bukti CI pada source terbaru setelah koreksi rollback.
- Stage 4C: halaman Livewire Admin penugasan Guru Mapel telah ditambahkan pada branch yang sama. Model/API existing dipertahankan; tidak ada migration.
- Stage 4D–4G: belum dimulai pada checkpoint ini.
- Web Guru dan Web Siswa: belum dimulai. Jangan mulai Web Guru sebelum gate minimum Admin tervalidasi.

## Source Stage 4C
- Component: `backend-laravel/app/Livewire/Admin/TeacherSubjectAssignmentManagement.php`
- View: `backend-laravel/resources/views/livewire/admin/teacher-subject-assignment-management.blade.php`
- Route: `/admin/teacher-subject-assignments`
- Menu: desktop/mobile, hanya ditampilkan untuk permission `academic.manage`.
- Feature test: `TeacherSubjectAssignmentManagementTest.php` (7 skenario).
- MySQL race test: `TeacherSubjectAssignmentConcurrencyTest.php` (dua koneksi, tenant row mutex).
- CI: workflow `wa01-alternative-isolated-runtime.yml` telah ditambah dua langkah test Stage 4C; hasil eksekusi commit HEAD belum tersedia.

## Backup
Folder: `docs/backup/stage4c-teacher-subject-assignment-2026-11-22/`
- `MANIFEST.md`
- `MANIFEST_SUPPLEMENT.md`
- Backup route, layout, workflow, dan test sebelum perubahan lanjutan.
- Source blob SHA dan ukuran dicatat pada manifest.

## Database dan deployment
- Tidak ada migration dijalankan.
- Tidak ada koneksi/mutasi database produksi.
- Tidak ada deployment/VPS.
- ZIP baseline tidak diubah.
- Tidak ada PR, merge, atau rebase.

## Pengujian
- Test source ditulis dan dimasukkan ke workflow MySQL 8 disposable.
- PHPUnit, PHP lint, CI latest SHA, browser E2E, dan UAT belum terverifikasi dari sesi ini.
- Jangan mengklaim Stage 4B/4C selesai atau siap produksi.

## Berikutnya
1. Ambil bukti workflow push untuk HEAD `3fbf3b4a...` dan pastikan SHA checkout sama.
2. Selesaikan kegagalan Stage 4B/4C jika ada.
3. Pastikan Admin ditolak pada seluruh endpoint/data operasional CBT.
4. Validasi MySQL 8 disposable dan race-condition.
5. Setelah gate minimum Admin lulus, mulai Web Guru tanpa menunggu fitur minor Admin.
