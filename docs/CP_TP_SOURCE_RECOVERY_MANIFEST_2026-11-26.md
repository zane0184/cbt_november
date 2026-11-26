# Rekonstruksi Sumber CP/TP — 26 November 2026

## Keputusan kerja
Permintaan koordinator: bila artefak tidak ada di repository, cari dan susun kembali dari sumber publik yang dapat diverifikasi. Tidak boleh membuat keputusan/ketetapan resmi palsu, mengarang CP, atau menandai sumber sebagai terverifikasi hanya karena ada salinan teks.

## Temuan sumber publik

| Sumber | Temuan | Status penggunaan |
|---|---|---|
| Kepka BSKAP 046/H/KR/2025 | Salinan PDF dengan Lampiran II untuk SD/MI tersedia di https://drive.paud.id/wp-content/uploads/kurikulum-merdeka/regulasi/2025_046_H_KR_BSKAP_Capaian_Pembelajaran_PAUD_Dasmen.pdf | Kandidat sumber isi CP umum SD/MI; wajib simpan bytes dan hitung SHA-256 sebelum import. Host bukan domain penerbit, sehingga identitas/keutuhan salinan perlu cross-check. |
| Kepka BKPDM 020 Tahun 2026 | Siaran pers resmi Kemendikdasmen 23 Juni 2026 menyatakan perubahan terbatas pada CP Pendidikan Agama dan Budi Pekerti; mapel lain tetap mengacu pada 046/H/KR/2025. https://www.kemendikdasmen.go.id/download/file/1933 | Metadata/ruang lingkup perubahan terkonfirmasi resmi; siaran pers bukan pengganti naskah keputusan dan lampirannya. Portal SIKN menampilkan entri salinan PDF: https://sikn-live.cosmicdigital.co.id/unduhan/regulasi-kurikulum |
| Kepdirjen Pendis 9941 Tahun 2025 | Salinan isi keputusan dan rincian lampiran ditemukan pada sumber pihak ketiga; artikel Ayo Madrasah mengarah ke folder Google Drive yang memuat PDF keputusan dan DOCX lampiran. https://www.ayomadrasah.id/2025/12/cp-pai-bahasa-arab-di-madrasah-pdf-docx.html | Belum dianggap artefak resmi terverifikasi. Perlu cross-check nomor, tanggal 28 November 2025, tanda tangan elektronik, seluruh lampiran MI, halaman dan hash terhadap salinan Kemenag. |

## Implikasi data
1. CP mapel umum MI selain Agama dan Budi Pekerti menggunakan BSKAP 046/H/KR/2025, dengan status revisi 020/2026 dicatat sebagai overlay terbatas.
2. CP PAI/Budi Pekerti MI harus mengikuti naskah perubahan BKPDM 020/2026 dan diselaraskan dengan keputusan Kemenag yang relevan; jangan menggabungkan dua dokumen tanpa pemetaan perubahan yang eksplisit.
3. CP Al-Qur'an Hadis, Akidah Akhlak, Fikih, SKI dan Bahasa Arab pada MI harus bersumber pada Kepdirjen Pendis 9941/2025, bukan disalin dari mapel umum BSKAP.
4. TP dan ATP adalah turunan/authoring guru yang terhubung ke CP resmi, bukan dibuat massal sebagai CP resmi.
5. Panca Cinta/KBC dan Pembelajaran Mendalam menjadi dimensi/konteks pemetaan pembelajaran; bukan mengganti isi CP resmi atau mengubah CP menjadi teks sintetis.

## Langkah implementasi aman berikutnya
- Ambil file PDF asli dari sumber publik yang ditemukan; simpan sebagai artefak sumber dengan metadata URL, tanggal akses, ukuran dan SHA-256.
- Bandingkan salinan 046/2025 dengan overlay 020/2026; hanya bagian yang diubah yang diganti.
- Verifikasi dokumen 9941/2025 dan lampiran MI; jika hanya salinan pihak ketiga yang tersedia, simpan status `UNVERIFIED_COPY` sampai cross-check resmi selesai.
- Ekstrak CP per jenjang MI, fase A/B/C, kelas, mata pelajaran, elemen dan teks persis; simpan nomor halaman dan identitas dokumen pada setiap record.
- Buat importer idempoten dan dry-run report. Jangan jalankan migration atau seeder pada produksi dalam tahap ini.
- Perbaiki halaman Admin legacy agar membaca registry canonical; Guru tetap memilih CP verified untuk membuat TP/ATP; Siswa hanya menerima konteks dari snapshot ujian immutable.
- Jalankan acceptance test pada MySQL 8/InnoDB disposable dan exact commit; tidak mengklaim lulus sebelum bukti test tersedia.

## Batasan status
Dokumen ini adalah manifest penelusuran dan rencana rekonstruksi, bukan dokumen regulasi resmi, bukan dataset CP final, dan bukan bukti bahwa import telah dilakukan. Source aplikasi belum diubah.
