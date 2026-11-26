# Addendum 2 — Pemetaan Bagian CP MI per Fase dan Mata Pelajaran
Tanggal: 26 November 2026
Branch: `integration/zip-vps-php83-20261126`
Commit dasar: `c3bb2a6e4475df925e5ace4bd00c3bdfc14bc4ed`

## AUDIT — ekstraksi struktur dokumen primer

### A. SK Dirjen Pendis Nomor 9941 Tahun 2025
PDF 106 halaman berhasil dibuka dan diekstrak teksnya melalui pembaca dokumen web:
https://min2sbb.sch.id/wp-content/uploads/2025/12/SK-Dirjen-Pendis-9941-2025-CP-PAI-dan-Bahasa-Arab.pdf

Bukti dokumen:
- Halaman 1 PDF (baris 38–42): identitas Keputusan Dirjen Pendis Nomor 9941 Tahun 2025.
- Halaman 3 PDF (baris 137–142): ditetapkan 28 November 2025 oleh Dirjen Pendidikan Islam.
- Halaman 4 dan seterusnya: Lampiran I.
- Halaman 8 dan seterusnya: CP Al-Qur'an Hadis MI.
- Halaman 37 dan seterusnya: CP Akidah Akhlak MI.
- Halaman 65 dan seterusnya: CP Fikih MI.
- Halaman 85 dan seterusnya: CP Sejarah Kebudayaan Islam MI.
- Halaman 94 dan seterusnya: CP Bahasa Arab MI.

#### Fase yang eksplisit di bagian MI

| Mapel | Fase A | Fase B | Fase C | Bukti lokasi dalam PDF |
|---|---|---|---|---|
| Al-Qur'an Hadis | Ada | Ada | Ada | Bagian II, halaman 8–18; struktur fase harus diekstrak dari subbagian D |
| Akidah Akhlak | Ada | Ada | Ada | Bagian IV, halaman 37–46; D. CP memuat fase A, B, C |
| Fikih | Ada | Ada | Ada | Bagian VI, halaman 65–71; D. CP memuat fase A, B, C |
| Sejarah Kebudayaan Islam | Tidak ditemukan Fase A pada bagian MI | Ada | Ada | Bagian VIII, halaman 85–89; subbagian D dimulai dari Fase B, lalu Fase C |
| Bahasa Arab | Ada | Ada | Ada | Bagian IX, halaman 94–99; D. CP memuat fase A, B, C |

Catatan penting:
- Jangan membuat record SKI Fase A. Bagian SKI MI pada dokumen ini dimulai dari Fase B (kelas III–IV).
- Jangan membuat Fase A SKI berdasarkan pola mata pelajaran lain.
- Pada dokumen, Al-Qur'an Hadis, Akidah Akhlak, Fikih, SKI, dan Bahasa Arab merupakan bagian terpisah. Setiap mapel memiliki struktur CP dan elemen masing-masing.
- Dokumen juga memuat bagian untuk RA, MTs, MA/MAK, dan MAPK. Import MI hanya boleh mengambil bagian MI, tidak boleh memasukkan CP jenjang lain.

#### Detail elemen yang sudah terlihat
- Al-Qur'an Hadis: elemen Pemahaman Konsep dan Keterampilan Proses (bagian II).
- Akidah Akhlak: elemen Pemahaman Konsep dan Keterampilan Proses; sub-elemen konsep termasuk Akidah, Akhlak, Adab, Kisah Keteladanan (bagian IV).
- Fikih: elemen Pemahaman Konsep dan Keterampilan Proses; sub-elemen konsep Fase A/B/C perlu diambil persis dari bagian VI.
- SKI: Pemahaman Konsep dan Keterampilan Proses; fase B dan C.
- Bahasa Arab: Komponen Bahasa dan Keterampilan Bahasa; teks Arab perlu dipertahankan utuh termasuk harakat/urutan RTL.

### B. Keputusan Kepala BKPDM Nomor 020 Tahun 2026
PDF 020/2026 berhasil dibuka:
https://cdn.schoolpro.id/documents/1782087907907-48baee9c9a6c43c280955078b534442c.pdf

Temuan:
- Ditetapkan 11 Juni 2026 (halaman 3 PDF, baris 57–64).
- Diktum KESATU menyatakan perubahan atas CP Pendidikan Agama dan Budi Pekerti serta Pendidikan Khusus Pendidikan Agama dan Budi Pekerti pada Lampiran II dan Lampiran V BSKAP 046/2025 (halaman 3 PDF, baris 52–53).
- Lampiran 020/2026 memuat CP Pendidikan Agama dan Budi Pekerti pada SD/MI dan jenjang lain.
- Siaran pers resmi 23 Juni 2026 menyatakan perubahan terbatas hanya pada Pendidikan Agama dan Budi Pekerti; mapel lain tetap mengacu BSKAP 046/2025:
https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti

**Isu regulasi yang wajib ditangani sebelum import:** CP PAI madrasah juga diatur khusus oleh Kepdirjen Pendis 9941/2025 dalam mata pelajaran terpisah. Karena Keputusan 020/2026 menyebut Pendidikan Agama dan Budi Pekerti pada SD/MI dan dikoordinasikan dengan Kemenag, tim implementasi harus memastikan relasi hukum/penerapannya terhadap lima mapel PAI khas MI pada 9941/2025. Jangan otomatis menimpa CP Pendis 9941 dengan PAI dan Budi Pekerti BSKAP 020, dan jangan mengabaikan 020 tanpa kajian. Simpan dua sumber dan tetapkan precedence setelah verifikasi regulasi resmi.

### C. SK BSKAP 046/H/KR/2025
PDF berhasil dibuka dengan ekstraksi teks:
https://drive.paud.id/wp-content/uploads/kurikulum-merdeka/regulasi/2025_046_H_KR_BSKAP_Capaian_Pembelajaran_PAUD_Dasmen.pdf

Temuan:
- Diktum KESATU menyebut Lampiran II sebagai CP SD/MI, SMP/MTs, SMA/MA (baris 32–39).
- Diktum KEEMPAT menetapkan Fase A kelas I–II MI, Fase B kelas III–IV MI, Fase C kelas V–VI MI (baris 42–50).
- CP dirumuskan untuk setiap mata pelajaran.
- Keputusan 020/2026 mengubah area Agama dan Budi Pekerti; mata pelajaran lain tetap mengacu 046/2025.

## DESAIN — aturan impor yang direvisi

1. Pisahkan registry sumber BSKAP 046/2025, BKPDM 020/2026, dan Pendis 9941/2025.
2. Terapkan precedence CP PAI/MI hanya setelah kajian tertulis mengenai hubungan Keputusan 020/2026 dengan Kepdirjen Pendis 9941/2025.
3. CP SKI Fase A tidak boleh dibuat karena dokumen Pendis MI yang diperiksa memulai bagian CP SKI dari Fase B.
4. Untuk setiap CP, simpan elemen dan sub-elemen asli jika tersedia, fase, jenjang MI, nomor halaman, locator yang spesifik, dan teks lengkap tanpa parafrasa.
5. Untuk Bahasa Arab, pastikan karakter Arab, harakat, urutan RTL, dan pemisahan kalimat tetap benar.
6. Jangan impor teks Rasional/Tujuan/Karakteristik sebagai CP. Hanya bagian D. Capaian Pembelajaran per fase yang menjadi outcome CP, sedangkan deskripsi elemen disimpan sebagai metadata/struktur bila skema mendukung.
7. Untuk mapel umum yang terdaftar di bootstrap seeder, cek apakah ada CP per fase di Lampiran II; jangan mengisi mata pelajaran yang tidak memiliki bagian CP pada fase tersebut.
8. Jangan isi CP generik untuk Muatan Lokal; gunakan dokumen kurikulum satuan/daerah masing-masing.
9. Cakupan checksum/verifikasi wajib granular. Empat CP Bahasa Indonesia Fase C tidak boleh membuat seluruh sumber BSKAP dianggap lengkap/terverifikasi untuk seluruh mapel dan fase.

## IMPLEMENTASI
- Hanya dokumen audit yang disiapkan pada tahap ini.
- Belum mengubah source code, seeder, atau database.
- Belum ada PDF lokal dan belum dihitung SHA-256 lokal karena upaya unduh langsung dari lingkungan kerja gagal.
- Belum ada importer CP penuh.

## PENGUJIAN
Belum dilakukan:
- parsing teks lengkap seluruh subbagian D per fase;
- perbandingan teks outcome dengan dokumen halaman demi halaman;
- hitung total CP per mapel/fase;
- import MySQL 8 disposable;
- idempotensi, FK, tenant isolation, checksum gating;
- UAT dropdown Guru.

## Langkah berikutnya
1. Lakukan ekstraksi terstruktur untuk semua subbagian D. Capaian Pembelajaran pada sumber Pendis dan BSKAP.
2. Susun manifest CP: sumber, mapel, fase, elemen, deskripsi, halaman, kode stabil, dan checksum (checksum menunggu file PDF lokal).
3. Lakukan review silang untuk SKI Fase A (pastikan memang tidak ada), dan precedence BKPDM 020 vs Pendis 9941.
4. Audit skema migrasi unik/FK dan service dropdown pada source PR aktual.
5. Baru implementasikan importer lengkap dan tes di MySQL 8 disposable.
