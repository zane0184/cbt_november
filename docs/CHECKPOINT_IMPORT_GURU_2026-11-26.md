# Checkpoint — Import Guru Web Admin
Tanggal: 2026-11-26

- Source dasar terbaru: `work/guru-docx-import-e2e-20261125` @ `bc3d9a497a2775fcbeda9deed57ee05bcf53e2e8`.
- Branch implementasi aktif: `feature/admin-teacher-import-20261126-r1`.
- Snapshot awal b451 tertinggal dua commit; PR #26 ditutup dan tidak digunakan. Dua commit terbaru dipertahankan dalam base baru.
- Status: implementasi awal Import Guru tersedia pada branch fitur; belum merge, belum diuji runtime.
- Route: `/admin/teachers/import`; komponen: `TeacherImport`; service: `TeacherImportService`; batch table: `teacher_import_batches`.
- Menu diakses dari Manajemen Pengguna; Import Siswa tidak diubah.
- Aturan identitas: PNS/PPPK = NIP 18 digit; Honorer = NIK 16 digit; NUPTK opsional 16 digit.
- Template XLSX satu worksheet dan parser memeriksa kolom ekstra.
- Backup route dan halaman Manajemen Pengguna tercatat di `docs/backup/TEACHER_IMPORT_20261126/`.
- Migration baru hanya ada di source. Tidak dijalankan di VPS/produksi.
- Feature tests ditambahkan, tetapi belum dijalankan. PHPUnit/PHP lint/CI/browser/VPS masih pending.
- Tidak ada perubahan pada CBT Engine atau kurikulum.

## Tindakan berikutnya
1. Review source dan diff terhadap base terbaru.
2. Jalankan CI pada branch fitur dan perbaiki semua kegagalan.
3. Setelah CI hijau, uji template XLSX/CSV pada database disposable.
4. Persetujuan eksplisit, backup produksi, dan preflight sebelum migration/deployment VPS.
5. Setelah deploy yang disetujui, Admin Madrasah menguji import Guru nyata dan login menggunakan NIP/NIK.
