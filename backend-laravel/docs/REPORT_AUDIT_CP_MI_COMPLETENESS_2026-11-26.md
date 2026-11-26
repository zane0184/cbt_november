# Laporan Audit Kelengkapan CP MI — 26 November 2026

## Ruang lingkup
Audit dilakukan pada source branch `integration/zip-vps-php83-20261126`, head commit `5a658aece0e3c9184ef0ad78cdcea78126b58a92`, bukan pada database produksi. PR #38 masih OPEN/DRAFT dan belum merged. PR #34 juga OPEN/DRAFT dan belum merged.

## AUDIT

### Source yang diperiksa
- `backend-laravel/database/seeders/CurriculumReferenceSourceSeeder.php`
- `backend-laravel/database/seeders/CurriculumRegistryBootstrapSeeder.php`
- `backend-laravel/database/seeders/ImportBahasaIndonesiaFaseCCpSeeder.php`
- `backend-laravel/app/Models/MultiLearningOutcome.php`
- `backend-laravel/app/Models/CurriculumReferenceSource.php`

### Temuan terverifikasi
1. `CurriculumRegistryBootstrapSeeder` membentuk struktur fase A/B/C dan node mata pelajaran MI. Fase A memetakan kelas 1–2, Fase B kelas 3–4, dan Fase C kelas 5–6. Kategori mapel juga dibedakan (WAJIB, WAJIB_TRANSISI, PILIHAN, LOKAL); Seni dan Budaya mempunyai pilihan turunannya; Muatan Lokal mempunyai kategori.
2. Bootstrap seeder tidak mengisi baris CP ke `multi_learning_outcomes`.
3. `ImportBahasaIndonesiaFaseCCpSeeder` hanya mengimpor empat elemen CP Bahasa Indonesia Fase C (Menyimak, Membaca dan Memirsa, Berbicara dan Mempresentasikan, Menulis). Importer ini bukan paket CP MI lengkap.
4. Importer hanya memeriksa keberadaan file PDF, signature `%PDF-`, ukuran minimal, dan menghitung SHA-256. Ia mengandalkan operator menempatkan artefak yang benar di `storage/app/curriculum-references/BSKAP_046_H_KR_2025.pdf`.
5. Setelah hanya empat CP Bahasa Indonesia Fase C disimpan, importer menambahkan `general_subject_cp` ke `verified_scope` pada sumber BSKAP. Scope ini terlalu luas untuk bukti konten yang baru diimpor sebagian; harus dipersempit atau dikelola per cakupan sebelum importer diperluas.
6. Pada `CurriculumReferenceSourceSeeder`, referensi Kepdirjen Pendis 9941/2025 masih berstatus `PENDING`, dan `source_uri` saat ini menunjuk berita Kemenag Batam, bukan artefak keputusan beserta lampiran CP MI. Dengan demikian belum ada sumber resmi Pendis yang siap dipakai importer CP PAI/Bahasa Arab.
7. Sumber BSKAP 046/H/KR/2025 terdaftar sebagai `METADATA_VERIFIED`, belum `ARTIFACT_CHECKSUM_VERIFIED`. Ini tepat untuk mencegah CP dianggap terverifikasi sebelum artefak sumber diarsipkan dan checksum dihitung.
8. Model `MultiLearningOutcome` menyimpan relasi struktur, versi kurikulum, sumber, checksum sumber, status verifikasi, serta flag official/editable. Implementasi importer harus mengisi semuanya secara konsisten dan menjaga identitas tenant/versi.
9. Struktur CP harus mempertahankan perbedaan sumber: CP mata pelajaran umum merujuk BSKAP 046/2025, sedangkan CP PAI dan Bahasa Arab madrasah memerlukan sumber Pendis yang sesuai. Siaran pers resmi Kemendikdasmen 23 Juni 2026 menyatakan perubahan BKPDM 020/2026 hanya untuk mata pelajaran Agama dan Budi Pekerti; mapel lain tetap mengacu BSKAP 046/2025. Ruang lingkup penerapan perubahan tersebut pada konteks madrasah harus ditetapkan dari dokumen keputusan, bukan diasumsikan.

### Status sumber publik
- Dokumen resmi BSKAP 046/H/KR/2025 ditemukan pada domain resmi Kurikulum Kemendikdasmen: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
- Siaran pers resmi tentang BKPDM 020/2026 ditemukan: https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti
- Dokumen artefak resmi Kepdirjen Pendis 9941/2025 yang memuat lampiran CP MI belum berhasil diarsipkan dan di-hash pada sesi audit ini. Tautan berita bukan pengganti dokumen keputusan.

## DESAIN

1. Import CP dilakukan per sumber dokumen dan per cakupan yang dapat dibuktikan.
2. Fase A, B, C mengikuti struktur mapel aktual dari bootstrap seeder; mapel pilihan, transisi, seni pilihan, dan muatan lokal tidak boleh diperlakukan sebagai mapel wajib seragam.
3. CP umum hanya diimpor dari bagian/elemen yang cocok dengan mapel MI pada BSKAP 046/2025 dan ketentuan perubahan 2026 yang relevan.
4. CP PAI/Bahasa Arab diimpor dari lampiran resmi Kepdirjen Pendis yang sesuai.
5. Setiap record CP harus memiliki source_id, source_locator spesifik, source_checksum yang sama dengan checksum artefak sumber, status verifikasi yang sesuai, serta metadata fase/mapel/elemen/urutan.
6. TP/ATP tidak dibuat otomatis sebagai CP; tetap menjadi rancangan Guru berdasarkan CP resmi.
7. Importer harus idempoten, fail-closed pada konflik identitas, transaksional, dan tidak menghapus histori.
8. Jangan menandai seluruh domain CP sebagai terverifikasi jika baru sebagian mapel/fase diimpor. Cakupan verifikasi harus eksplisit.

## IMPLEMENTASI

- Tidak ada source aplikasi yang diubah dalam audit ini.
- Tidak ada file asli/backup yang ditimpa.
- Tidak ada migration yang dijalankan.
- Tidak ada database produksi atau VPS yang diakses.
- Tidak ada importer CP lengkap yang dibuat atau dipasang.

## PENGUJIAN

Belum dijalankan:
- Import ke MySQL 8 disposable.
- Pengujian idempotensi dan konflik identitas.
- Pemeriksaan jumlah/kelengkapan CP per mapel dan fase.
- Uji relasi source checksum pada semua outcome.
- Uji dropdown CP pada penugasan Guru lintas kelas/fase.
- Uji isolasi data pada instalasi madrasah.

Audit source ini bukan bukti bahwa CP sudah tersedia di website produksi.

## Pekerjaan berikutnya
1. Arsipkan artefak PDF resmi BSKAP 046/2025 dan hitung SHA-256 dari berkas yang benar.
2. Dapatkan dan arsipkan artefak keputusan Kepdirjen Pendis 9941/2025 beserta lampiran CP MI; verifikasi keaslian dan checksum.
3. Pastikan artefak keputusan BKPDM 020/2026 serta cakupan penerapannya pada MI/madrasah.
4. Ekstrak CP secara menyeluruh per mapel dan fase, lakukan pemeriksaan silang dengan halaman/elemen dokumen.
5. Baru setelah itu buat importer lengkap dan jalankan tes pada database disposable MySQL 8.
6. Pertahankan PR #38 sebagai Draft sampai pengujian dan review selesai. Jangan merge atau deploy tanpa persetujuan.

## Referensi GitHub
- PR #38: https://github.com/zane0184/cbt_november/pull/38
- PR #34: https://github.com/zane0184/cbt_november/pull/34
- Branch audit: `integration/zip-vps-php83-20261126`
- Commit audit: `5a658aece0e3c9184ef0ad78cdcea78126b58a92`
