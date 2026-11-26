# Ekstraksi Awal CP PABP SD/MI — BKPDM 020/2026
Tanggal pemeriksaan: 26 November 2026

## Identitas artefak yang benar-benar diperiksa
- Nama unggahan: `BKPDM SALINAN KEPKA.pdf`
- Berkas percakapan: `/mnt/data/BKPDM SALINAN KEPKA.pdf`
- Ukuran: 14.786.103 byte
- Jumlah halaman: 79
- SHA-256 byte berkas: `802fad489a31672ae0cdafb0cc358ee85f7c57faa208455af5259b2af6d0ceb0`
- Judul pada halaman 1: Keputusan Kepala Badan Kebijakan Pendidikan Dasar dan Menengah Nomor 020 Tahun 2026 tentang Perubahan atas Keputusan Kepala BSKAP Nomor 046/H/KR/2025.
- Tanggal penetapan: 11 Juni 2026.
- Bagian yang diekstrak: Lampiran I, CP Pendidikan Agama dan Budi Pekerti reguler, halaman PDF 7–8 (nomor halaman tercetak 6–7).
- Metode: render halaman PDF 7–8 pada 300 dpi dan OCR lokal, lalu pemeriksaan visual halaman. PDF hasil pindai; ekstraksi teks otomatis pada layanan Files kosong.
- Status autentikasi: berkas ini adalah salinan yang diunggah pengguna. Hash membuktikan identitas byte unggahan saat ini, bukan tanda tangan digital penerbit atau keaslian salinan.

## AUDIT — hasil ekstraksi CP yang terbaca

### Fase A — kelas I dan II SD/MI
1. Al-Qur’an Hadis: Membaca huruf hijaiyah berharakat, huruf hijaiyah bersambung; menghafal Surah al-Fatihah, beberapa surah pendek Al-Qur’an, dan hadis tentang kebersihan.
2. Akidah: Menjelaskan dan meyakini rukun iman, iman kepada Allah Swt., beberapa asmaulhusna, dan iman kepada malaikat.
3. Akhlak: Menerapkan akhlak terhadap Allah Swt. dengan menyucikan dan memuji-Nya, dan akhlak terhadap diri sendiri.
4. Fikih: Menerapkan rukun Islam, syahadatain, tata cara bersuci, salat fardu, zikir dan berdoa setelah salat.
5. Sejarah Peradaban Islam: Menceritakan kisah keteladanan beberapa nabi dan rasul.

### Fase B — kelas III dan IV SD/MI
1. Al-Qur’an Hadis: Membaca, menulis, dan membedakan huruf hijaiah bersambung, menghafal dan menjelaskan beberapa surah pendek, hadis tentang kewajiban salat dan menjaga hubungan baik dengan sesama.
2. Akidah: Menjelaskan dan meyakini sifat-sifat Allah Swt., iman kepada kitab-kitab Allah Swt., beberapa asmaulhusna, dan iman kepada rasul-rasul Allah Swt.
3. Akhlak: Menerapkan akhlak terhadap Allah Swt. dengan berbaik sangka kepada-Nya, akhlak terhadap orang tua, keluarga, dan guru.
4. Fikih: Menerapkan azan dan ikamah, salat Jumat dan salat sunah, menjelaskan balig dan tanggung jawab yang menyertainya (taklif).
5. Sejarah Peradaban Islam: Menceritakan dan menjelaskan kisah Nabi Muhammad saw. sebelum dan sesudah menjadi rasul periode Makkah.

### Fase C — kelas V dan VI SD/MI
1. Al-Qur’an Hadis: Membaca, menulis, dan membedakan huruf hijaiah bersambung, menjelaskan beberapa surah pendek dan hadis tentang berbuat baik kepada orang tua, guru, dan teman.
2. Akidah: Menjelaskan dan meyakini beberapa asmaulhusna, iman kepada hari akhir, iman kepada gada’ dan gadar.
3. Akhlak: Menerapkan akhlak terhadap Allah Swt. dengan berdoa dan bertawakal kepada-Nya, akhlak terhadap teman, tetangga, non-muslim, hewan, dan tumbuhan.
4. Fikih: Menerapkan puasa wajib dan sunah, makanan minuman yang halal dan haram, zakat, infak, sedekah, dan wakaf.
5. Sejarah Peradaban Islam: Menceritakan dan menjelaskan kisah Nabi Muhammad saw. periode Madinah dan khulafaurasyidin.

## Locator sumber
- Fase A: halaman PDF 7, bagian D.1, butir 1.1–1.5.
- Fase B: halaman PDF 7, bagian D.2, butir 2.1–2.5.
- Fase C: halaman PDF 7–8, bagian D.3, butir 3.1–3.5.
- Pemetaan fase dan jenjang pada keputusan: halaman PDF 7–8 menyebut SD/MI; kelas I–II, III–IV, V–VI.
- Ketentuan perubahan: halaman PDF 3, Diktum KESATU menyatakan perubahan CP Pendidikan Agama dan Budi Pekerti dalam Keputusan BSKAP 046/H/KR/2025.

## DESAIN — batas pemakaian
1. Ekstraksi ini adalah CP PABP reguler pada lampiran BKPDM 020/2026, bukan keseluruhan dataset CP MI.
2. Jangan otomatis memetakan lima elemen ini sebagai lima mata pelajaran terpisah di struktur MI. Pendis 9941/2025 memuat struktur mapel madrasah yang berbeda; precedence dan pemetaan operasional harus diputuskan berdasarkan dokumen/penjelasan resmi.
3. Jangan menghapus atau menimpa CP PAI lima mapel Pendis 9941/2025.
4. Jangan menganggap ekstraksi OCR ini sebagai verifikasi final per karakter. Transkripsi harus dibandingkan kembali dengan citra halaman sumber sebelum impor.
5. CP umum non-PAI tetap memerlukan BSKAP 046/2025 dan tidak tercakup dalam ekstraksi ini.

## IMPLEMENTASI
- Dibuat dokumen ekstraksi audit baru ini saja.
- Tidak ada source aplikasi, seeder, migration, SQL, database, atau VPS yang diubah.
- Tidak ada file sumber yang ditimpa.

## PENGUJIAN
- `pdfinfo`: 79 halaman, tidak terenkripsi.
- SHA-256 dihitung dari byte unggahan aktual dengan `sha256sum`.
- Halaman PDF 7–8 dirender 300 dpi.
- OCR Bahasa Indonesia/Inggris berhasil menghasilkan teks CP Fase A/B/C.
- Belum ada pembandingan dua-orang/independen per karakter, belum ada importer, dan belum ada uji MySQL.

## STATUS
`PARTIAL EXTRACTION — NOT READY FOR IMPORT`

Langkah berikutnya:
1. Selesaikan transkripsi dan audit isi Pendis 9941/2025 untuk seluruh mapel MI dan fase yang memang ada.
2. Dapatkan artefak BSKAP 046/2025 agar CP umum SD/MI dapat diekstrak lengkap.
3. Rekonsiliasi resmi BKPDM 020/2026 vs Pendis 9941/2025 untuk struktur PAI MI.
4. Setelah seluruh sumber, teks, locator, dan checksum memenuhi gate, baru implementasikan importer idempoten pada branch kerja dan uji di MySQL 8 disposable.
