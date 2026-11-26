# Ekstraksi Lanjutan CP MI — Kepdirjen Pendis 9941/2025
Tanggal: 26 November 2026
Branch: `integration/zip-vps-php83-20261126`

## AUDIT

### Sumber
- Nama Library: `Dirjen-pendis-2025-9941 Capaian Pembelajaran PAI dan Bahasa Arab pada Madrasah.pdf`
- File ID: `file_0000000011308211b521c0481040fc8e`
- Metadata Library: 107 halaman, 1.161.548 byte.
- Teks halaman yang tersedia diekstrak melalui Files search. Raw-byte materialization sebelumnya ditolak oleh sistem Library; belum ada SHA-256 independen dari PDF ini.
- Salinan berasal dari situs madrasah pihak ketiga. Jangan menyebut tanda tangan BSrE telah diverifikasi secara kriptografis.

### Matriks bagian D CP MI yang ditemukan
| Mapel | Fase A | Fase B | Fase C | Locator tercetak |
|---|---|---|---|---|
| Al-Qur'an Hadis | ditemukan | ditemukan | ditemukan | 11–13 |
| Akidah Akhlak | ditemukan | ditemukan | ditemukan | 42–46 |
| Fikih | ditemukan | ditemukan | ditemukan | 68–70 (bagian C berlanjut) |
| SKI | tidak ditemukan pada MI | ditemukan | ditemukan | 88–89 |
| Bahasa Arab | ditemukan | ditemukan | ditemukan | 97–99 |

Catatan: kata “ditemukan” berarti teks bagian D dan substansi CP berhasil diambil dari hasil ekstraksi pencarian, bukan bahwa seluruh halaman sudah ditranskripsi dan diverifikasi kata demi kata. SKI Fase A tidak ditemukan; jangan dibuat.

### Substansi CP yang berhasil ditemukan

#### Al-Qur'an Hadis
- Fase A, hlm. 11: tajwid huruf hijaiyah terpisah/bersambung dan tanda baca, Gunnah, Alif Lam Qamariyah/Syamsiyah; hafalan dan penjelasan surah pendek pilihan; hadis kebersihan, keutamaan belajar, dan berbakti kepada orang tua.
- Fase B, hlm. 12: hukum bacaan qalqalah, mad tabi'i, izhar halqi, ikhfa' hakiki, idgam bigunnah, idgam bilagunnah, iqlab; hafalan/penulisan surah pilihan; hadis salat berjemaah, persaudaraan, takwa, niat, silaturahmi.
- Fase C, hlm. 13: mim sukun, waqaf-wasal, ra' tafkhim/tarqiq, jawazul wajhain; ayat/surah dan hadis mengenai ciri orang munafik, menyayangi anak yatim, keutamaan memberi, amal saleh.

#### Akidah Akhlak
- Fase A, hlm. 42–43: dua kalimah syahadat, enam rukun iman, sifat wajib Allah, iman kepada malaikat, asmaulhusna; kalimah thayyibah, pola hidup sehat/bersih, jujur, terima kasih, rendah hati, adab, dan keteladanan Nabi Muhammad saw. serta Nabi Nuh a.s.
- Fase B, hlm. 44–45: asmaulhusna, iman kepada kitab Allah, nabi/rasul; kalimah thayyibah, syukur, pantang menyerah, pemberani, tolong-menolong, amanah, menghindari serakah/kikir/kufur nikmat; adab kepada orang tua, guru, teman; kisah Nabi Ibrahim a.s. dan Nabi Ismail a.s.
- Fase C, hlm. 45–46: asmaulhusna, hari akhir, qada dan qadar; kalimah thayyibah, akhlak terpuji dan menghindari akhlak tercela; adab bertamu, tetangga, lingkungan; keteladanan Nabi Musa a.s. dan Nabi Isa a.s.

#### Fikih
- Fase A, hlm. 68: rukun Islam, syahadatain, praktik taharah, salat fardu, salat berjemaah, azan/ikamah, zikir setelah salat.
- Fase B, hlm. 69–70: puasa Ramadan, salat Jumat dan beberapa salat sunah, rukhsah salat, khitan, tanda balig, bersuci dari hadas besar.
- Fase C, hlm. 70: zakat fitrah, infak/sedekah, hewan kurban, haji/umrah, halal-haram; muamalah jual beli, pinjam-meminjam, menghindari gasab, barang temuan/luqathah.
- Bagian keterampilan proses Fase C berlanjut pada halaman tercetak berikutnya dan perlu diekstrak penuh.

#### SKI
- Fase A: tidak ditemukan di bagian CP MI. Tidak boleh diisi dengan asumsi.
- Fase B, hlm. 88–89: masyarakat Arab sebelum Islam; kehidupan/kepribadian Rasulullah saw.; kerasulan; dakwah dan ketabahan Rasulullah serta sahabat; hijrah; Isra Mikraj; keterampilan proses sejarah.
- Fase C, hlm. 89 dan seterusnya: teks awal CP yang ditemukan membahas dakwah Rasulullah saw. kepada kelompok nonmuslim di Madinah dan peristiwa menjelang akhir hayat Rasulullah saw. Isi lengkap dan keterampilan proses perlu diekstrak lagi.

#### Bahasa Arab
- Fase A, hlm. 97–98: mengenali bunyi/kosakata perkenalan, keluarga, rumah, madrasah, hobi, buah, warna, transportasi, pemandangan alam; pola kalimat Arab wajib dipertahankan persis dan RTL.
- Fase B, hlm. 98: kalimat sapaan/pertanyaan terkait materi pelajaran, binatang, penyakit, olahraga, teman, taman; contoh pola kalimat Arab ada di sumber dan wajib ditranskripsi dari citra halaman.
- Fase C, hlm. 99: informasi/bacaan sederhana tentang anggota tubuh, kebun binatang, ruang tamu/ruang belajar, perpustakaan, kantin; pola kalimat Arab wajib dipertahankan persis dan RTL.
- Keterampilan Bahasa (menyimak, berbicara, membaca, memirsa, menulis, mempresentasikan) tercantum pada hlm. 98–99.

## DESAIN
1. CP harus disimpan sesuai mapel, fase, elemen/subelemen, dan teks aslinya; jangan gabungkan isi pemahaman konsep dan keterampilan proses menjadi satu ringkasan.
2. Bahasa Arab disimpan Unicode tanpa transliterasi pengganti, dengan dukungan RTL dan harakat.
3. Struktur fase SKI mengikuti sumber: B dan C saja.
4. Pemetaan Pendis 9941 ke node mata pelajaran pada registry aplikasi harus diaudit tersendiri. Tidak boleh menyimpulkan lima mata pelajaran PAI aplikasi otomatis identik dengan lima elemen PABP BKPDM 020.
5. Tidak boleh impor sebelum PDF asli/artefak dapat diverifikasi, transkripsi lengkap, locator halaman, dan hubungan regulasi diselesaikan.

## IMPLEMENTASI
- Dibuat dokumen audit/ekstraksi baru ini saja.
- Tidak ada file source lama diubah.
- Tidak ada migration, seeder, SQL, database, atau VPS yang diubah.

## PENGUJIAN
- Pencarian teks bagian D per mapel/fase: dilakukan.
- Locator halaman tercetak dicatat.
- Pemeriksaan visual setiap halaman, OCR independen, perbandingan transkripsi dua tahap: belum tuntas.
- SHA-256 PDF Pendis dari byte asli: belum tersedia.
- Importer dan uji MySQL 8 disposable: belum dilakukan.

## STATUS
`PARTIAL EXTRACTION — NOT READY FOR IMPORT`

### Pekerjaan yang masih harus dituntaskan
1. Ekstraksi penuh keterampilan proses dan seluruh teks CP pada Al-Qur'an Hadis, Akidah Akhlak, Fikih, SKI, Bahasa Arab.
2. Transkripsi visual tabel/teks Arab halaman 97–99.
3. Peroleh BSKAP 046/H/KR/2025 sebagai artefak lengkap untuk seluruh mapel umum SD/MI.
4. Rekonsiliasi resmi BKPDM 020/2026 dengan Pendis 9941/2025 untuk PAI MI.
5. Baru setelah semua gap tertutup, buat importer idempoten, transactional, fail-closed, dan uji di MySQL 8 disposable.
