# Addendum Koreksi Owner — Batasan Fixture DOCX dan Format Import Soal
Tanggal: 25 November 2026
Repository: `zane0184/cbt_november`
Work branch: `work/docx-import-parser-20261125`
PR #23: tetap OPEN / DRAFT / NOT MERGED

## Keputusan Owner dan prioritas dokumen

Addendum ini mengoreksi dan melengkapi desain parser pada `ADDENDUM_EXTENSIBLE_DOCX_PARSER_QUESTION_TYPES_2026-11-25.md`. Jika ada perbedaan, addendum ini yang berlaku untuk penggunaan fixture dan perilaku import.

**Prinsip utama: dokumen contoh adalah alat pengujian parser, bukan format administrasi yang harus diikuti guru dan bukan sumber data kurikulum proyek.**

DOCX `Kisi Kisi_Soal ASAS Matematia_B. Indo_kelas 6(2).docx` hanya fixture pengujian internal. File asli tidak boleh diubah. Hash dicatat pada manifest fixture test; file tidak boleh menjadi seed, data awal, konfigurasi permanen, atau sumber data akademik/kurikulum.

Dilarang:
- mengimpor tabel kisi-kisi sebagai soal;
- menyimpan kisi-kisi contoh sebagai data akademik atau kurikulum;
- menjadikan format kisi-kisi sebagai format wajib guru;
- menyimpulkan atau membuat CP, TP, ATP, materi, kelas, atau pemetaan kurikulum dari fixture tanpa pemilihan/pemetaan guru yang berwenang;
- menjadikan nama file, mata pelajaran yang tertulis di contoh, atau konten kisi-kisi sebagai konfigurasi sistem.

## Format dokumen guru

Guru cukup mengunggah DOCX berisi:
1. soal;
2. opsi atau pernyataan sesuai jenis soal;
3. kunci jawaban.

Tidak ada persyaratan adanya kisi-kisi, tabel CP/TP/ATP, identitas penyusun, administrasi ujian, halaman pengesahan, atau template dokumen tertentu. Parser harus bekerja pada struktur Word yang wajar tanpa bergantung pada unsur administratif tersebut.

## Desain parser fleksibel

Parser harus membaca DOCX sebagai ordered content stream yang mempertahankan urutan paragraf, tabel, baris, sel, dan relasi konten. Struktur yang didukung dapat mencakup:
- soal dan opsi dalam paragraf;
- soal dan opsi dalam tabel;
- kunci jawaban di akhir dokumen atau setelah soal;
- satu atau beberapa mata pelajaran, hanya bila penanda pemisah/identitasnya jelas;
- stimulus yang dipakai beberapa soal;
- gambar, tabel, grafik, dan simbol/formula matematika sesuai dukungan OOXML dan renderer.

Parser tidak boleh bergantung pada nomor paragraf saja atau menganggap setiap tabel adalah tabel soal. Tabel kisi-kisi/administrasi yang terdeteksi harus ditandai sebagai non-importable/excluded content. Kategori dan eksklusi harus berbasis struktur/penanda yang dapat dijelaskan, bukan nama file atau hard-coded isi fixture.

### Ambiguitas dan provenance

Setiap hasil AST wajib menyimpan provenance sumber: part DOCX, urutan blok, paragraph index, table index, row index, cell index, dan relasi gambar bila relevan. Field yang tidak tersedia boleh null, tetapi jangan mengarang locator.

Jika jenis soal, batas soal, opsi, kunci jawaban, mata pelajaran, atau relasi stimulus ambigu:
- jangan menebak;
- tampilkan warning/error dan tingkat keyakinan;
- tandai item NEEDS_REVIEW atau BLOCKED;
- minta guru mengoreksi atau mengonfirmasi pada preview;
- jangan menyimpan item invalid/ambigu secara diam-diam.

Parser boleh menghasilkan preview parsial untuk item valid, tetapi hasil tidak boleh dipublikasikan atau disimpan sebagai draft sebelum pengguna menyelesaikan required review untuk item yang dipilih. Item invalid tidak boleh lolos ke penyimpanan.

## Preview, koreksi, dan pemetaan akademik

Sebelum penyimpanan, Guru yang berwenang harus dapat:
- meninjau semua soal hasil parse;
- memeriksa dan mengedit stem, opsi/pernyataan, tipe, dan kunci jawaban;
- memeriksa stimulus serta hubungan stimulus-ke-soal;
- melihat provenance, warning, dan error;
- mengonfirmasi atau memperbaiki hasil parsing yang ambigu;
- menetapkan mata pelajaran, kelas/rombel, materi, dan pemetaan kurikulum sesuai hak akses.

Mata pelajaran/kelas/materi dan pemetaan kurikulum harus berasal dari data akademik sistem atau pilihan eksplisit Guru yang berwenang. Parser tidak boleh membuat data kurikulum atau menyimpulkan CP/TP/ATP dari kisi-kisi contoh. Draft yang disimpan harus valid, tenant-scoped, dan memiliki audit trail atas koreksi/pemetaan pengguna.

## Registry tipe dan keputusan sebelumnya yang tetap berlaku

- PG: gunakan kontrak runtime yang sudah ada setelah regression test.
- PGK: gunakan `MULTIPLE_RESPONSE` setelah regression test.
- B/S sederhana: kompatibel dengan `TRUE_FALSE` lama.
- B/S tiga pernyataan: `TRUE_FALSE_GROUP`, tepat tiga pernyataan dengan ID stabil dan boolean answer per ID. Scoring proporsional per pernyataan: poin soal × jumlah benar / 3, presisi desimal konsisten, maksimum tidak melebihi poin soal.
- Shared stimulus: first-class ID dan versi stabil, relasi soal eksplisit, konten/urutan/versi/hash ikut immutable exam snapshot; jawaban tiap soal independen.
- MATCHING: importer tetap nonaktif sampai runtime E2E lulus.
- SHORT_ANSWER: tetap BLOCKED.
- ESSAY: manual review.
- Melengkapi kalimat, mengurutkan, gambar/tabel/grafik: hanya aktif jika parser, persistence, runner, answer validation, scoring, dan snapshot benar-benar lengkap.
- Parser recognition tidak mengaktifkan runtime capability; publish gate tetap fail-closed.

## Fixture dan acceptance tambahan

Fixture utama tetap test-only dan tidak boleh dipakai sebagai data aplikasi. Hash file dicatat dalam test fixture manifest. Hash saat pemeriksaan terakhir yang dilaporkan adalah:
`27571b1b4e80d0d2c1638345440f26aeab1f3209d7e880023ac7459906d45050`
Ukuran yang dilaporkan: 45.462 byte. Hash wajib dihitung ulang dari file yang benar-benar dikomit sebagai fixture sebelum acceptance; jangan mengubah file asli.

Tambahkan fixture sintetis terpisah, setidaknya:
1. DOCX tanpa kisi-kisi, satu mapel, soal dan kunci jawaban;
2. DOCX satu mapel dengan opsi paragraf;
3. DOCX soal/opsi dalam tabel;
4. DOCX kunci jawaban di akhir dokumen;
5. DOCX kunci jawaban setelah masing-masing soal;
6. DOCX multi-mapel dengan separator/heading eksplisit;
7. DOCX stimulus bersama dengan dua kelompok dan relasi soal eksplisit;
8. DOCX simbol matematika/formula;
9. DOCX gambar, tabel, dan grafik sesuai capability yang benar-benar didukung;
10. DOCX dengan struktur ambigu/invalid untuk negative tests.

Acceptance:
- DOCX tanpa kisi-kisi dapat diproses.
- DOCX satu mapel dapat diproses.
- DOCX multi-mapel hanya dipisah bila penanda jelas; selain itu NEEDS_REVIEW.
- Soal dan answer key terpisah dengan benar, termasuk kedua pola lokasi kunci.
- Kisi-kisi/administrasi, jika ada, dikecualikan dan tidak disimpan.
- Tidak ada isi kisi-kisi fixture yang masuk ke database/seed.
- Ambiguitas menghasilkan warning/error dan review, bukan tebakan.
- Preview menampilkan seluruh konten yang dikenali dan provenance.
- Hanya item valid yang disimpan sebagai draft setelah konfirmasi.
- Pemetaan akademik berasal dari sistem/pilihan Guru, bukan isi fixture.
- File fixture asli tidak berubah; hash cocok.

## Prioritas import lintas fitur

Prioritas tetap:
1. Import Siswa XLSX/CSV.
2. Import soal DOCX.
3. Import Guru XLSX/CSV.

Ketiganya wajib memiliki template jelas, preview, validasi identitas/relasi, deteksi duplikasi, error per baris yang dapat diperbaiki, transaksi/rollback, perlindungan import berulang, audit log, dan isolasi tenant/instalasi. Identifier disimpan sebagai string: Admin username; Guru PNS/PPPK NIP 18 digit; Guru Honorer NIK 16 digit; Siswa NISN 10 digit. Tidak ada fallback username/NUPTK untuk login Guru. Jangan mengaktifkan kembali XLSX question importer lama.

## AUDIT / IMPLEMENTASI / PENGUJIAN — status saat addendum

- Addendum desain ini adalah dokumentasi keputusan; bukan implementasi parser.
- Tidak ada source aplikasi, migration, database, VPS, atau PR #23 yang diubah oleh keputusan ini.
- Backup byte-exact tetap wajib untuk setiap file source yang akan dipatch.
- Implementasi memerlukan checkout branch lengkap dan environment PHP 8.3, Composer, disposable MySQL 8/InnoDB, serta browser-to-real-Laravel E2E. Status audit sebelumnya menyebut workspace lokal belum tersedia; jangan mengklaim pengujian berjalan sebelum environment tersedia.
- PHPUnit, PHP lint, MySQL, parser fixture acceptance, runner, scoring, snapshot, publish gate, dan real Laravel E2E belum dijalankan untuk implementasi ini.

## Urutan kerja

1. Periksa branch/source/backup aktual dan seluruh migration/relasi; jangan mengulang audit yang sudah terbukti tanpa alasan.
2. Pastikan fixture test-only dan manifest hash; tambahkan synthetic fixtures.
3. Backup byte-exact semua source yang akan diubah.
4. Implementasikan ordered DOCX AST, parser adapters, provenance, exclusions, warnings, and type registry.
5. Implementasikan preview dan koreksi sebelum draft save; mapel/kelas/materi/kurikulum dipilih dari data sistem.
6. Implementasikan `TRUE_FALSE_GROUP` dan shared stimulus persistence/snapshot/runner bertahap.
7. Integrasikan PG/PGK hanya setelah regression.
8. Lanjutkan import Siswa XLSX/CSV lalu Guru XLSX/CSV.
9. Jalankan acceptance, negative, PHPUnit, PHP lint, disposable MySQL 8/InnoDB, dan browser-to-real-Laravel E2E.
10. Update checkpoint dengan exact commit SHA, daftar file, backup SHA, migration kandidat, dan hasil tes.

Tidak ada migration produksi, deployment VPS, merge, ZIP distribusi final, atau perubahan status PR #23 tanpa persetujuan Owner.
