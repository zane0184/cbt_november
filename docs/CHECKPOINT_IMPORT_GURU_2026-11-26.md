# Checkpoint — Import Guru Web Admin
Tanggal: 2026-11-26

- Source dasar: `work/guru-docx-import-e2e-20261125` @ `b451989b1ca2ac39b4ea16746914f6d28d1169a8`.
- Branch implementasi: `feature/admin-teacher-import-20261126`.
- Head sebelum dokumentasi checkpoint: `4a7239954add699b46064fad8d6e7307f6efb8a4`.
- Status: implementasi awal Import Guru tersedia pada branch fitur, belum merge, belum diuji runtime.
- Komponen: `TeacherImport`; service: `TeacherImportService`; route: `/admin/teachers/import`; batch table: `teacher_import_batches`.
- Menu diakses dari Manajemen Pengguna; Import Siswa tidak diubah.
- Aturan identitas: PNS/PPPK = NIP 18 digit; Honorer = NIK 16 digit; NUPTK opsional 16 digit.
- Template XLSX satu worksheet agar kompatibel dengan preflight XLSX yang sudah ada.
- Backup file asli dan file yang dikoreksi tercatat di `docs/backup/TEACHER_IMPORT_20261126/`.
- Migration baru hanya ada di source. Tidak dijalankan di VPS/produksi.
- Tes yang ditambahkan belum dijalankan. PHPUnit/PHP lint/CI/browser/VPS masih pending.
- Tidak ada perubahan pada source branch dasar atau CBT Engine.

## Tindakan berikutnya
1. Review source dan diff, terutama parser CSV/XLSX, uniqueness, transaction rollback, dan tenant scoping.
2. Jalankan CI pada branch fitur; selesaikan semua kegagalan sebelum merge.
3. Setelah CI hijau, lakukan uji manual pada database disposable dengan template XLSX/CSV nyata.
4. Minta persetujuan eksplisit sebelum migration/deployment VPS; backup produksi dan preflight wajib.
5. Setelah merge/deploy yang disetujui, Admin Madrasah menguji import data Guru nyata dan verifikasi login NIP/NIK.
