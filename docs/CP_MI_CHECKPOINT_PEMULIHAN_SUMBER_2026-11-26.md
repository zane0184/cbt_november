# CBT Madrasah — Checkpoint Pemulihan Sumber CP MI
Tanggal: 26 November 2026

Branch: `integration/zip-vps-php83-20261126`
HEAD sebelum checkpoint ini: `30507e082d9eb5edabe940ec3a463f8354be949a`

## AUDIT

### Koreksi inventaris sumber Project Library
Pemeriksaan ulang Library proyek menemukan dua PDF yang sebelumnya hanya disebut sebagai dokumen web:
1. `Dirjen-pendis-2025-9941 Capaian Pembelajaran PAI dan Bahasa Arab pada Madrasah.pdf`
   - File ID Library: `file_0000000011308211b521c0481040fc8e`
   - Ukuran metadata Library: 1.161.548 byte; 107 halaman.
   - Ekstraksi teks Library dapat dibaca, termasuk keputusan, surat pengantar, dan lampiran.
   - Upaya materialisasi raw bytes ditolak oleh Files: `This Project file does not have an authorized raw-byte materialization path.`
   - SHA-256 independen dari byte asli belum tersedia.
2. `Salinan Kepka BKPDM No. 020 Tahun 2026 tentang Perubahan atas Keputusan Kepala BSKAP Kemendikdasmen No. 046_H_KR_2025.pdf`
   - File ID Library: `file_0000000020708207a8ff2907aa56699c`
   - Ukuran metadata Library: 8.510.107 byte; 79 halaman.
   - Teks tidak dapat diparse oleh extractor PDF Library.
   - Upaya materialisasi raw bytes ditolak dengan pesan yang sama; checksum dan pemeriksaan tanda tangan belum dapat dilakukan.
3. BSKAP 046/H/KR/2025:
   - URL resmi tercatat: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
   - Belum ditemukan sebagai PDF terpisah di daftar PDF Library yang diperiksa.
   - PDF resmi belum tersedia sebagai raw bytes dalam workspace.

### Ekstraksi CP Pendis 9941/2025 yang berhasil dibuktikan dari teks Library
- Dokumen memuat surat pengantar B-606/Dt.I.I/PP.00/12/2025 tanggal 1 Desember 2025 dan keputusan Dirjen Pendis 9941 tanggal 28 November 2025.
- Al-Qur'an Hadis MI: bagian CP Fase A, B, C ditemukan (halaman tercetak 11 dan bagian setelahnya; nomor halaman PDF berbeda).
- Akidah Akhlak MI: Fase A dan B ditemukan; Fase C perlu ekstraksi dan verifikasi lanjutan.
- Fikih MI: heading fase dan isi CP perlu diekstrak penuh untuk A/B/C.
- SKI MI: Fase B dan C ditemukan pada halaman tercetak 88–89; Fase A tidak ditemukan pada bagian CP MI yang diperiksa. Jangan membuat CP SKI Fase A.
- Bahasa Arab MI: Fase A/B/C perlu diekstrak penuh; pertahankan teks Arab, harakat, dan urutan RTL.
- Hasil pencarian teks saja belum merupakan ekstraksi lengkap/siap impor. Belum ada hitungan CP final per mapel/fase dan belum ada validasi transkripsi.

### Rekonsiliasi normatif
- BKPDM 020/2026 mengubah CP Pendidikan Agama dan Budi Pekerti pada lampiran BSKAP 046/2025.
- Pendis 9941/2025 menetapkan lima mata pelajaran PAI/Bahasa Arab pada madrasah: Al-Qur'an Hadis, Akidah Akhlak, Fikih, SKI, Bahasa Arab.
- Bukti publik yang tersedia belum cukup untuk menetapkan hubungan operasional kedua struktur tersebut bagi MI tanpa tafsir/konfirmasi resmi Kemenag. Jangan gabungkan atau hapus salah satunya secara otomatis.
- BSKAP 046/2025 tetap menjadi sumber CP mapel umum, kecuali bagian yang secara eksplisit diubah BKPDM 020/2026.

### Source aplikasi
Temuan source sebelumnya tetap berlaku:
- Registry membentuk struktur fase A/B/C dan node mata pelajaran, tetapi bootstrap tidak mengimpor CP.
- Gate Guru menolak CP resmi jika sumber belum `ARTIFACT_CHECKSUM_VERIFIED` dan checksum outcome tidak sama dengan checksum sumber.
- Importer Bahasa Indonesia Fase C yang parsial bukan solusi lengkap dan tidak boleh dipasang.

## DESAIN
1. Pertahankan fail-closed provenance gate.
2. Jangan ubah status sumber menjadi verified hanya berdasarkan hasil ekstraksi teks atau ukuran metadata Library.
3. Ekstrak CP hanya dari bagian D “Capaian Pembelajaran” untuk tiap mapel/fase; jangan mengimpor rasional, tujuan, atau karakteristik sebagai CP.
4. Model cakupan fase per mata pelajaran; SKI tidak boleh dipaksa memiliki Fase A.
5. TP disusun Guru dari CP resmi terverifikasi; ATP disusun dari TP dengan histori versi. Jangan seed TP/ATP rekaan.
6. Jangan membuat importer final sampai artefak primer, teks lengkap, locator, checksum, dan aturan pemetaan PAI MI tervalidasi.

## IMPLEMENTASI
- Tidak ada source aplikasi, migration, seeder, SQL, database, atau VPS yang diubah.
- Tidak ada file lama ditimpa.
- Checkpoint ini dokumen baru pada branch kerja.
- Tidak ada backup source diperlukan karena tidak ada source lama yang diedit.

## PENGUJIAN
- Pemeriksaan daftar Library PDF: dilakukan.
- Pembacaan teks PDF Pendis 9941: berhasil melalui Files extraction.
- Pembacaan teks BKPDM 020: gagal diparse; raw-byte materialization ditolak.
- SHA-256 dari PDF primer: belum dilakukan.
- Ekstraksi lengkap CP semua mapel/fase: belum selesai.
- MySQL 8 disposable, idempotensi importer, tenant isolation, dropdown Guru: belum diuji.

## STATUS
`BLOCKED — SOURCE ARTIFACT / CP EXTRACTION / PAI NORMATIVE RECONCILIATION`

Tidak boleh menyatakan CP MI lengkap, tidak boleh mengaktifkan CP dropdown, dan tidak boleh membuat klaim siap produksi. Langkah berikutnya adalah memperoleh raw bytes BSKAP 046, BKPDM 020, dan Pendis 9941 melalui jalur arsip yang diizinkan; kemudian ekstrak dan rekonsiliasi semua CP MI per mapel/fase sebelum implementasi importer.
