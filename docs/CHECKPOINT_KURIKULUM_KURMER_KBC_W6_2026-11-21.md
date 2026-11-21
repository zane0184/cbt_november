# Checkpoint Dokumentasi — Master Kurmer + KBC / W6

**Tanggal:** 21 November 2026 (WIB)  
**Repository:** `zane0184/cbt_november`  
**Branch dokumentasi:** `docs/curriculum-kbc-w6-requirements-20261121`  
**Base commit:** `cbb7cda6ebfbd3792435d6dbfebed8b1cbc406af` dari `work/web-admin-w0-20261121`  
**Scope:** spesifikasi, register keputusan, matriks audit/pengujian.  
**Status:** dokumentasi ditambahkan; W0 tetap belum ditutup; W1 belum disetujui/dimulai; W6 belum diimplementasikan.

## AUDIT

- Source yang diperiksa read-only: commit `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`.
- Ditemukan fondasi `subjects`, `curricula`, `phases`, `curriculum_subjects`, `elements`, CP, CP versions, TP, ATP, ATP versions, dan mapping soal ke outcome multi-kurikulum.
- Ditemukan gap: KBC tercatat sebagai `multi_curriculums.code=KBC` sejajar dengan `MERDEKA`; provenance CP/TP resmi tidak lengkap; TP belum memiliki version table yang terlihat; UI Admin saat ini terutama menampilkan CP/TP dan tambah TP; integrasi Question ke CP/TP legacy belum terbukti; master Panca Cinta/DPL resmi dan survei karakter belum terbukti tersedia.
- Kode mapel RDM belum diverifikasi. Portal RDM publik menampilkan status pemeliharaan ketika diperiksa.
- Nomenklatur Panca Cinta dan delapan DPL diperiksa terhadap KMA 1503/2025. Panduan KBC dan identitas Kepdirjen 6077/2025 diperiksa dari dokumen panduan yang tersedia.

## DESAIN

- Kurmer adalah kerangka akademik; KBC adalah landasan nilai/karakter yang terintegrasi. Tidak ada pilihan Kurmer ATAU KBC.
- KBC tidak membentuk mapel/fase/CP/TP/ATP/kode RDM tandingan.
- Admin Madrasah menambah/mengubah master; tidak ada delete permanen.
- Guru hanya memilih referensi master melalui dropdown; tidak mengubah master.
- Panca Cinta dan DPL berlaku lintas mata pelajaran.
- Sumber resmi, versi, tahun/tanggal berlaku, status, dan histori wajib disimpan.
- Seluruh acceptance criteria W6 tertulis di matriks baru.

## IMPLEMENTASI DOKUMENTASI

Ditambahkan pada branch dokumentasi terpisah:
1. `docs/SUPPLEMENTAL_SPEC_MASTER_KURIKULUM_KURMER_KBC_W6.md`
2. `docs/REGISTER_KEPUTUSAN_WEB_ADMIN_KURIKULUM.md`
3. `docs/MATRIKS_AUDIT_DAN_PENGUJIAN_MASTER_KURIKULUM_W6.md`
4. Dokumen checkpoint ini.

Tidak ada file source aplikasi, migration, seeder, database, konfigurasi runtime, atau ZIP baseline yang diubah. Tidak ada dokumen W0 yang ditimpa. Branch W0 dan recovery tidak diubah oleh pekerjaan dokumentasi ini.

## PENGUJIAN DOKUMENTASI

- File baru dibuat melalui GitHub Contents API; commit SHA dikembalikan untuk setiap file.
- Verifikasi ulang konten, branch HEAD, dan file status harus dilakukan sebelum laporan final.
- Tidak ada automated test aplikasi, migration, database test, atau E2E yang dijalankan.
- Tidak ada klaim bahwa fitur Master Kurikulum sudah diimplementasikan.

## Gerbang berikutnya

1. Tinjau addendum, register, dan matriks.
2. Tutup W0 dan dapatkan persetujuan eksplisit untuk W1.
3. Setelah W1–W5 sesuai urutan, lakukan audit mendalam khusus W6 terhadap seluruh migration, seeder, data, model, service, policy, route, API, Livewire, view, dan test sebelum patch.
4. Verifikasi referensi kode RDM dan CP/TP resmi dari sumber yang berwenang sebelum import.
5. W6 hanya dimulai setelah desain data final dan backup/manifest target disiapkan.
