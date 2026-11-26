# Rekonstruksi Sumber CP/TP — 26 November 2026

## Standar keaslian
Tidak ada CP yang boleh ditulis ulang, dihasilkan AI, atau dimasukkan ke registry sebagai CP resmi tanpa dokumen sumber yang dapat ditelusuri. Bedakan:
- **Primer resmi langsung**: berkas pada domain penerbit/portal resmi.
- **Salinan dokumen resmi yang dapat dikorroborasi**: berkas host pihak ketiga tetapi identitas, nomor, tanggal, isi dan tanda pengesahan cocok dengan pengumuman resmi; tetap perlu validasi berkas digital.
- **Belum terverifikasi**: teks/scan dari sumber sekunder yang belum bisa dicocokkan dengan dokumen resmi.
- **Transkripsi referensi**: teks hasil ekstraksi dari dokumen resmi; bukan pengganti PDF sumber dan harus menyimpan halaman.

## A. BSKAP 046/H/KR/2025 — sumber primer ditemukan
- **URL PDF resmi Kemendikdasmen**: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
- **Hasil pemeriksaan**: dokumen PDF berhasil dibuka dan diekstrak oleh penelusuran web. Bagian awal memuat identitas penerbit, nomor 046/H/KR/2025, tanggal penetapan 16 Juli 2025, dan tanda tangan Kepala BSKAP Toni Toharudin. Diktum KESATU menyatakan Lampiran II berisi CP SD/MI, SMP/MTs, SMA/MA. Diktum KEEMPAT mengatur fase A kelas I-II, fase B kelas III-IV, fase C kelas V-VI.
- **Rujukan ekstraksi**: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf (web extraction ref: turn505748view0; Lampiran II: turn178832view0).
- **Status**: PRIMER_RESMI_TERBACA. Namun pengunduhan byte PDF ke workspace/GitHub belum berhasil; jangan mengisi checksum bytes atau mengklaim arsip PDF lokal tersedia.
- **Penting**: untuk MI, CP mapel umum menggunakan Lampiran II; PAI dan Budi Pekerti harus menerapkan perubahan berikutnya 020/2026.

## B. BKPDM 020 Tahun 2026 — naskah ditemukan, host berkas bukan domain penerbit
- **Pengumuman resmi Kemendikdasmen**: https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti (23 Juni 2026, Siaran Pers 520/sipers/A6/VI/2026).
- **Naskah PDF salinan yang terbaca**: https://cdn.schoolpro.id/documents/1782087907907-48baee9c9a6c43c280955078b534442c.pdf
- **Korroborasi**: PDF menyatakan Kepka BKPDM Nomor 020 Tahun 2026, ditetapkan 11 Juni 2026 oleh Toni Toharudin, memuat perubahan CP Pendidikan Agama dan Budi Pekerti serta Pendidikan Khusus pada Lampiran II dan V. Pengumuman resmi Kemendikdasmen menyatakan perubahan hanya pada Agama dan Budi Pekerti; mata pelajaran lainnya tetap pada 046/2025.
- **Portal rujukan kebijakan**: https://sikn-live.cosmicdigital.co.id/unduhan/semua-peraturan menampilkan entri “Salinan Kepka BKPDM No. 020 Tahun 2026”.
- **Status**: SALINAN_DOKUMEN_DIKORROBORASI; belum ada verifikasi tanda tangan elektronik/bytes PDF dari host penerbit. Gunakan perubahan hanya setelah salinan resmi/portal arsip dapat diunduh dan hash diverifikasi.
- **Rujukan ekstraksi**: turn892466view0 dan turn650944search0.

## C. Dirjen Pendis 9941 Tahun 2025 — bukti identitas kuat, berkas resmi asli belum diperoleh
- **Identitas dokumen yang konsisten pada beberapa salinan**: Keputusan Dirjen Pendis Nomor 9941 Tahun 2025, tanggal 28 November 2025, tentang CP mata pelajaran PAI dan Bahasa Arab pada RA, MI, MTs, MA dan MAK.
- **Rujukan Kemenag subdomain**: https://sites.google.com/madrasah.kemenag.go.id/portofoliodutagemaaidwiaryani/beranda-home/pusat-regulasi-kurikulum-curriculum-center (halaman Pusat Regulasi & Kurikulum menampilkan “CP Bahasa Arab dan PAI 2025” serta materi KMA 1503/2025; halaman terindeks tetapi gagal dibuka penuh oleh crawler).
- **Salinan unduh yang diumumkan Ayo Madrasah**: https://www.ayomadrasah.id/2025/12/cp-pai-bahasa-arab-di-madrasah-pdf-docx.html — halaman menyebut PDF SK beserta lampiran dan DOCX lampiran, tautan Drive: https://drive.google.com/drive/folders/1WjmiN2v3xFdV7G6NqQbawe0DiHf-epve?usp=sharing.
- **Salinan teks tambahan**: https://id.scribd.com/document/973775973/9941-Tanggal-28-November-2025-Surat-Turunan-SK-CP-PAI-Dan-Bahasa-Arab-Pada-Madrasah (menampilkan keputusan, tanggal, nama penandatangan Suyitno, pernyataan TTE BSrE, dan bagian lampiran; bukan sumber penerbit).
- **Status**: UNVERIFIED_COPY_PENDING_OFFICIAL_BYTES. Tidak boleh diperlakukan sebagai bukti final keaslian PDF/validasi sertifikat. Identitas dan isi MI dapat dipakai untuk daftar pemeriksaan, bukan seed CP terverifikasi sampai bytes PDF asli dan lampiran diperoleh serta diperiksa.

## D. Aturan pemetaan kurikulum CBT MI
1. Mapel umum MI: BSKAP 046/2025 Lampiran II, kecuali bagian yang secara eksplisit diubah oleh BKPDM 020/2026.
2. PAI dan Budi Pekerti pada struktur sekolah umum tidak boleh disamakan begitu saja dengan struktur mapel PAI madrasah. Untuk madrasah, gunakan CP khusus Kemenag 9941/2025 dan lakukan analisis perubahan 020/2026/koordinasi Kemenag sebelum memutuskan penerapannya.
3. Mapel PAI MI sesuai 9941: Al-Qur'an Hadis, Akidah Akhlak, Fikih, Sejarah Kebudayaan Islam; plus Bahasa Arab.
4. KBC/Panca Cinta dan Pembelajaran Mendalam adalah konteks/pemetaan pembelajaran sesuai KMA 1503/2025, bukan pengganti atau pengarang ulang CP resmi.
5. TP/ATP adalah hasil perencanaan guru yang diturunkan dari CP, bukan CP resmi baru.

## E. Pekerjaan yang belum boleh diklaim selesai
- Belum ada PDF bytes yang berhasil disimpan ke repository/workspace; belum ada SHA-256 file asli yang dicatat.
- Belum ada pemeriksaan tanda tangan elektronik/sertifikat BSrE pada PDF 9941 atau BKPDM 020.
- Belum ada transkripsi CP MI lengkap yang diperiksa silang per halaman.
- Belum ada import dataset, source aplikasi diubah, migration, DB, atau test baru.
- Tidak ada synthetic CP dibuat.

## F. Langkah wajib berikutnya
1. Dapatkan file PDF asli dari URL primer/Drive yang disebut di atas ke workspace; simpan read-only dengan metadata sumber.
2. Hitung SHA-256, ukuran, jumlah halaman, dan simpan hasil ekstraksi teks dengan nomor halaman.
3. Validasi tanda tangan elektronik dan sertifikat bila tersedia; cocokkan nomor/tanggal/pejabat/lampiran.
4. Untuk 9941, prioritaskan file PDF pada folder Drive yang ditautkan dan cocokkan dengan salinan yang didistribusikan Kemenag; jika bytes/sertifikat tidak dapat diverifikasi, tetap status pending dan jangan impor sebagai resmi.
5. Susun dataset CP MI secara literal dengan provenance tiap record (document_id, version, page, phase, subject, element, source_hash).
6. Buat importer idempoten + dry-run, lalu acceptance test MySQL 8/InnoDB disposable. Dilarang menulis ke database produksi pada tahap ini.

**Status keseluruhan: sumber primer BSKAP 046 sudah terbaca; BKPDM 020 sudah dikorroborasi melalui siaran resmi dan salinan dokumen; SK Dirjen Pendis 9941 belum memiliki bytes PDF asli terverifikasi. Belum ada data CP resmi diimpor.**
