# Indeks Sumber CP MI — SK Dirjen Pendis 9941/2025

**Status:** sumber diterima; identitas dan isi awal diperiksa; validasi tanda tangan digital belum tuntas.  
**File sumber pengguna:** `Dirjen-pendis-2025-9941 Capaian Pembelajaran PAI dan Bahasa Arab pada Madrasah.pdf`  
**Ukuran:** 1,161,548 byte · **Halaman:** 107 · **SHA-256:** `63ecce28af6c02b9fefde85f9594e7432b2575a69a263a46882ace6e87ffb757`

## Struktur dan indeks halaman

| Mata pelajaran | Halaman PDF | Halaman tercetak awal/akhir | Keterangan |
|---|---:|---:|---|
| Al-Qur'an Hadis MI, MTs, MA/MAK | 10–19 | 8–17 | Memuat CP fase A, B, C MI |
| Akidah Akhlak MI, MTs, MA/MAK | 39–54 | 37–52 | Memuat CP MI dan bagian MAPK |
| Fikih MI, MTs, MA/MAK | 68–85 | 66–83 | Memuat CP MI dan jenjang berikutnya |
| Sejarah Kebudayaan Islam MI, MTs, MA/MAK | 88–95 | 86–93 | Memuat CP MI; perlu verifikasi awal fase yang berlaku |
| Bahasa Arab MI, MTs, MA/MAK | 96–103 | 94–101 | Memuat CP MI dan jenjang berikutnya |

## Identitas keputusan yang terbaca
- Surat pengantar Ditjen Pendis: Nomor B-606/Dt.I.I/PP.00/12/2025, tanggal 1 Desember 2025.
- Keputusan: Direktur Jenderal Pendidikan Islam Nomor 9941 Tahun 2025.
- Tanggal penetapan: 28 November 2025.
- Ruang lingkup: CP PAI dan Bahasa Arab pada RA, MI, MTs, MA, MAK.
- Diktum KESATU dan KEDUA menetapkan CP sebagai acuan proses pembelajaran pada madrasah.
- Lampiran I dimulai pada halaman PDF 6.

## Aturan ekstraksi untuk importer
1. Jangan menganggap setiap paragraf rasional/tujuan sebagai record CP. Ambil record dari bagian **D. Capaian Pembelajaran** tiap mapel.
2. Pertahankan jenjang, fase, rentang kelas, kode/subkode, elemen, deskripsi dan teks CP persis sesuai sumber.
3. Simpan provenance pada setiap record: nomor keputusan, tahun, nama file sumber, SHA-256, halaman PDF, halaman tercetak, mapel, fase, elemen, kode.
4. Simpan keterampilan proses sebagai elemen/subelemen tersendiri jika memang dicantumkan sebagai CP pada mapel tersebut; jangan dilebur ke deskripsi konsep.
5. Jangan membuat TP atau ATP otomatis dari teks CP. TP/ATP tetap dibuat guru sebagai turunan dan harus menyimpan relasi ke CP induk.
6. Transkripsi PDF harus diperiksa visual terhadap halaman scan, khususnya istilah Arab, harakat, tanda baca, tabel dan nomor kode. Hasil pdftotext saja belum cukup untuk menyatakan dataset final.
7. Status tanda tangan: pemeriksaan `pdfsig` tidak menemukan signature object tertanam. Surat pengantar memuat pernyataan TTE BSrE, tetapi berkas yang diterima belum lolos validasi kriptografis. Jangan menyebut tanda tangan digital terverifikasi.

## Tahap berikutnya
- Transkripsi dan pemeriksaan visual per mapel/fase, mulai dari MI saja.
- Buat dataset staging yang immutable/read-only dan importer dry-run idempoten.
- Jalankan uji pada MySQL 8/InnoDB disposable, bukan database produksi.
- Baru setelah hasil audit data dan tes lolos, sambungkan ke registry Admin dan konteks Guru. Siswa menerima konteks melalui ujian/snapshot yang sudah dipublikasikan, bukan mengedit CP.
