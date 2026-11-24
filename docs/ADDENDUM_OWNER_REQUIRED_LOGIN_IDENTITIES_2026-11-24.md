# Addendum Owner — Identitas Login Wajib Guru dan Siswa
Tanggal: 24 November 2026
Repository: `zane0184/cbt_november`
Branch: `work/p1-seven-targets-20261124`
Status: keputusan owner terbaru; mengikat implementasi setelah tanggal dokumen ini.

## AUDIT — konflik keputusan
Master spesifikasi Web Admin 22 November dan addendum rekonsiliasi identitas 24 November sebelumnya menyatakan NIP/NUPTK Guru opsional dan login Guru menggunakan username. Keputusan owner terbaru yang diberikan pada 24 November menetapkan kontrak login berbeda. Addendum ini menjadi prioritas lebih tinggi daripada master spec dan addendum lama untuk aturan identitas/login. Dokumen lama dipertahankan sebagai histori, tidak dihapus.

## DESAIN — kontrak final yang berlaku
| Pengguna | Identifier login wajib | Validasi |
|---|---|---|
| Admin Madrasah | Username | Tetap seperti sebelumnya |
| Guru ASN PNS/PPPK | NIP | Tepat 18 digit |
| Guru Honorer | NIK | Tepat 16 digit |
| Siswa | NISN | Tepat 10 digit |

Ketentuan tambahan:
- Status kepegawaian Guru wajib dipilih dan harus sesuai dengan identitas login.
- Guru PNS/PPPK tidak boleh login dengan username, NIK, atau NUPTK sebagai fallback; login memakai NIP.
- Guru Honorer tidak boleh login dengan username, NIP, atau NUPTK sebagai fallback; login memakai NIK.
- NUPTK dapat disimpan sebagai data tambahan bila dibutuhkan, tetapi bukan identifier login dan tidak menggantikan NIP/NIK.
- Siswa login hanya dengan NISN; Admin tetap username.
- Web dan API menggunakan resolver yang sama.
- Identitas disimpan sebagai string; leading zero wajib dipertahankan.
- Identitas unik dalam lingkup tenant/database instalasi. Identitas sama pada instalasi madrasah berbeda diperbolehkan.
- Tidak boleh membocorkan NIP/NIK/NISN melalui tampilan umum, session old input, cache key, log, error, audit payload, atau response yang tidak berwenang.

## IMPLEMENTASI
Patch P1 menerapkan validasi create Guru menurut status kepegawaian, login resolver role-specific, UI login, masking identifier pada daftar pengguna, hashed login throttle key, serta migration forward-only untuk unique index tenant-scoped dengan preflight duplicate/blank yang fail-closed. Data lama tidak di-backfill dan tidak diklasifikasikan otomatis.

## PENGUJIAN
Regression tests ditambahkan untuk pembuatan Guru tanpa status/NIP/NIK, login Guru menggunakan NIP/NIK, penolakan username Guru, login API, dan unique constraint database. Hasil test harus dicatat pada exact commit CI; test yang hanya ditulis belum dianggap lulus.

## BATASAN MIGRASI
Migration unique index tidak boleh dijalankan pada database produksi tanpa preflight read-only, backup, pengujian disposable MySQL 8/InnoDB, rencana konflik data legacy, dan persetujuan owner. Jika duplikat atau blank ditemukan, migration berhenti sebelum DDL. Tidak ada auto-backfill atau tebakan status Guru lama.

## SISA PEKERJAAN
- Audit jalur update/import/recovery lain. Bila belum ada, tandai NOT IMPLEMENTED; jangan klaim validasi menyeluruh.
- Tambahkan read-only preflight command untuk menampilkan jumlah konflik identitas tanpa menampilkan nilai mentah.
- Verifikasi masking halaman profil Guru dan seluruh output audit/log.
- Jalankan focused + full PHPUnit pada exact P1 HEAD dengan MySQL 8/InnoDB disposable.
