# Checkpoint — Admin, Import Siswa, Password, Mulok & CP/TP
Tanggal: 26 November 2026

- Repository: zane0184/cbt_november
- Base: work/rombel-docx-completion-20261126 @ 841a1dd90dbe0277026fade1511a39b5dc9216b6
- Branch aktif: work/admin-import-curriculum-fixes-20261126
- Code changeset HEAD sebelum dokumentasi: 0ca76d0d5c620da37006d69e52ddcdc344391db3
- Branch/PR head saat checkpoint: 6e1a394a4c80f429376387fb8bf4ad6d7a126b0a
- PR: #34 https://github.com/zane0184/cbt_november/pull/34, OPEN/DRAFT, belum merged; GitHub melaporkan mergeable=true.
- Laporan: docs/REPORT_ADMIN_USER_REQUEST_FIXES_2026-11-26.md

## AUDIT
- Semester dropdown sebelumnya sudah menggunakan term; tabel daftar assignment masih menggunakan atribut semester.name yang tidak ada.
- Mulok create/category sudah ada dari tahap sebelumnya; edit UI/method belum ada. Migration category sudah ada dan merupakan prasyarat schema VPS.
- Password installer/Admin sebelumnya minimum 14; import dan reset menghasilkan 24 karakter.
- UserManagement sudah satu component, tetapi filter dropdown belum berupa tab Guru/Siswa.
- Admin curriculum route/component ada, tetapi tidak ditautkan di nav dan membaca data legacy. Data CP official belum diimpor/verified; P0-02 GitHub mencatat artefak asli dan checksum belum tersedia.
- Import Siswa masih memerlukan Rombel yang sudah dipilih di luar template.

## DESAIN
- Role dan portal tetap: Admin Madrasah, Guru, Siswa. Tidak ada portal baru.
- Admin hanya melihat CP/TP; Guru membuat TP/ATP melalui assignment.
- CP hanya dapat tampil sebagai official setelah source checksum verified; tidak membuat konten contoh.
- Template Import Siswa satu file per Rombel, metadata nama Rombel/tahun/kelas seragam.
- Rombel otomatis dibuat dalam transaksi yang sama dengan batch import, hanya setelah seluruh preview valid.

## IMPLEMENTASI
- Assignment list menampilkan Semester Ganjil/Genap dari semester.term.
- Mulok dapat diedit oleh Admin tenant; registry/mapel wajib tidak dapat diedit; create/update diaudit.
- Password Admin minimum 8; password acak import Guru/Siswa dan reset akun menjadi 8 karakter.
- Pengguna & Akun satu halaman dengan tab Semua Akun/Guru/Siswa.
- CP & TP link tampil pada navigasi Admin; page menampilkan verified CP, TP/ATP Guru, Panca Cinta, Pembelajaran Mendalam dan dimensi profil lulusan dari registry versi terdaftar.
- Template Siswa menambahkan rombel, tahun_pelajaran, kelas; preview validasi satu Rombel per file; import membuat Rombel bila belum ada dan memasukkan seluruh siswa ke Rombel itu.
- Backup exact source tersimpan di backend-laravel/backups/user-request-fixes-20261126/, manifest: MANIFEST.md.
- Tidak ada migration baru, perubahan VPS, database produksi, atau merge.

## PENGUJIAN
- Regression tests ditambah/disesuaikan: Mulok edit, Rombel otomatis, password 8 karakter, dan semester pada daftar assignment.
- PHPUnit: tidak dijalankan.
- CI: tidak dijalankan.
- PHP lint/runtime, browser UAT, migration dan VPS: tidak dijalankan.
- Tidak ada klaim PASS runtime.

## NEXT
1. Review/merge PR #34 setelah source review.
2. Di VPS, periksa migration ledger untuk 2026_11_26_000001_add_subject_category_for_local_content.php; jalankan hanya setelah backup, preflight dan persetujuan.
3. Uji enam kebutuhan pengguna pada staging/VPS; catat bukti PASS/FAIL.
4. CP official tetap BLOCKED sampai artefak PDF asli/appendix dan checksum resmi tersedia di GitHub.
5. Buat ZIP deployment baru setelah integrasi source dan acceptance VPS; jangan gunakan ZIP lama untuk menyatakan patch ini sudah terpasang.

Status: IMPLEMENTASI SOURCE SELESAI UNTUK REVIEW — PR DRAFT — RUNTIME ACCEPTANCE BELUM DILAKUKAN.
