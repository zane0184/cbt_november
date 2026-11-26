# Checkpoint — Penutupan Detail Rombel & Import DOCX
Tanggal: 26 November 2026
Repository: `zane0184/cbt_november`
Branch kerja: `work/rombel-docx-completion-20261126`
Base: `work/combined-feature-completion-20261126` (`055489d8c7557bc0d01c78aecc5843704c75baf7`)
HEAD pemeriksaan: `d40db1faf87034cfdc159d53698edfbf355f1a17`

## AUDIT

### Detail Rombel
- Route detail sudah tersedia: `admin.academic.rombels.show`.
- Komponen detail memeriksa role tunggal `ADMIN_MADRASAH`, permission `academic.manage`, dan tenant aktif.
- Query rombel memakai `tenant_id` eksplisit; relasi siswa memfilter pivot tenant dan fail-closed jika konteks tenant tidak cocok/kosong.
- Daftar siswa dipaginasi 20 baris; NISN dimasking kecuali empat digit terakhir.
- Daftar Rombel sekarang menampilkan jumlah siswa dan tautan menuju halaman detail.
- Link menuju halaman penempatan siswa diverifikasi terhadap route `/admin/rombel-students`.

### Import Soal DOCX
- Parser memvalidasi struktur paket DOCX, XML dan relasi internal; tidak mengekstrak arsip mentah ke filesystem.
- Gambar hanya menerima JPEG/PNG yang relasinya tervalidasi; batas sumber: 10 MB DOCX, 2 MB per gambar, 25 juta piksel per gambar, maksimum 20 lampiran dan total 8 MB setelah ekspansi referensi per soal.
- Media disimpan di private local storage dengan path tenant-scoped, checksum, metadata sumber, dan audit log.
- Gambar snapshot ujian mempertahankan urutan pivot; endpoint media memeriksa tenant dan attempt siswa yang aktif.
- OOXML paragraf, format bold/italic/underline, arah RTL, OMML, serta struktur tabel bersarang dipertahankan sebagai metadata versi.
- Konversi OMML menghasilkan MathML untuk konstruksi umum. Node yang belum didukung tidak diklaim berhasil dirender; fallback teks ditampilkan dan Guru wajib meninjau.
- Tabel bersarang disimpan sebagai struktur bertingkat dan ditampilkan sebagai preview ringkas; layout Word yang persis tidak dijamin.
- Payload siswa menghapus sumber OOXML mentah dan metadata peninjauan Guru; hanya data render yang diperlukan yang diteruskan.
- Peninjauan konten kompleks dicatat melalui audit log dan publikasi diblokir sampai Guru mengakui pemeriksaan.
- Import tetap membuat soal DRAFT; bobot/kunci/pemetaan akademik tetap harus dilengkapi dan divalidasi Guru.
- Idempotensi batch didukung unique key tenant + penugasan + SHA-256 pada migration yang sudah ada. Tidak ada migration baru pada tahap ini.

### Backup
- Sepuluh file backup `.before` dan manifest tersedia di `backend-laravel/backups/rombel-docx-completion-20261126/`.
- Blob SHA seluruh sepuluh backup dibandingkan dengan SHA source baseline pada manifest; seluruhnya cocok.
- ZIP baseline dan backup asli tidak diubah.

## DESAIN
- Detail Rombel tetap berada di portal Admin Madrasah, tanpa menambah role baru.
- Import DOCX tidak pernah menerbitkan soal otomatis.
- Konten media/rumus/tabel harus tenant-scoped, memiliki sumber yang dapat ditelusuri, dan tidak membocorkan XML internal ke peserta.
- Peninjauan manual diwajibkan untuk formula dan tabel bersarang yang kompleks.

## IMPLEMENTASI
- Perbaikan daftar Rombel: jumlah siswa, tautan nama/aksi ke detail, dan syarat tenant pada otorisasi.
- Import DOCX: ekstraksi media privat, deduplikasi validasi sumber gambar dengan batas berdasarkan jumlah lampiran aktual, penyimpanan metadata OOXML/MathML, dukungan tabel bersarang, RTL, dan preview format teks.
- Integrasi Bank Soal: media tampil, status review konten kompleks, pencatatan review, dan penguncian publikasi.
- Integrasi snapshot/ujian: urutan media dipertahankan; MathML dan struktur tabel dirender secara aman pada runner siswa; sumber OOXML mentah disanitasi.
- Transaksi importer menggunakan satu percobaan transaksi agar rollback tidak meninggalkan file media dari percobaan ulang otomatis; file yang tersimpan dibersihkan saat transaksi gagal.
- Tidak ada perubahan database produksi, VPS, atau migration baru.

File runtime utama:
- `app/Http/Controllers/Api/V1/ExamAttemptController.php`
- `app/Livewire/Admin/AcademicManagement.php`
- `app/Livewire/Admin/QuestionBank.php`
- `app/Livewire/Teacher/QuestionDocxImport.php`
- `app/Models/QuestionVersion.php`
- `app/Services/ExamSnapshotService.php`
- `app/Services/Questions/DocxQuestionDraftImporter.php`
- `app/Services/Questions/DocxQuestionMediaExtractor.php` (baru)
- `app/Services/Questions/DocxQuestionParser.php`
- `app/Services/Questions/OmmlToMathMlConverter.php` (baru)
- View Rombel, Bank Soal, Import DOCX, runner siswa, dan komponen preview tabel bersarang (baru).

## PENGUJIAN
- PHPUnit: **TIDAK DIJALANKAN**, sesuai instruksi pengguna.
- CI: **TIDAK DIJALANKAN**, sesuai instruksi pengguna.
- Build Android/Gradle: tidak relevan pada tahap web ini dan tidak dijalankan.
- Pemeriksaan yang dilakukan hanya peninjauan source, pembandingan branch, pemeriksaan route dan relasi tenant, pemeriksaan tree GitHub, serta verifikasi blob SHA backup.
- Belum ada bukti runtime di VPS untuk alur import DOCX bergambar/rumus/tabel atau tampilan detail Rombel. Pengujian operasional tetap menunggu uji langsung di VPS.

## STATUS DAN BATASAN
- Implementasi source untuk dua fitur sudah ditutup pada branch ini secara statis.
- **Belum dinyatakan siap produksi.**
- ZIP lama commit `055489d8c7557bc0d01c78aecc5843704c75baf7` tidak memuat perubahan branch ini dan tidak boleh dianggap paket final.
- Tidak ada ZIP baru pada checkpoint ini; paket distribusi harus dibuat dari HEAD branch ini tanpa menjalankan PHPUnit/CI, kemudian diuji oleh pengguna pada VPS staging.
