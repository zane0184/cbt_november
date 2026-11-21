# Addendum Spesifikasi Induk Web Admin — Master Kurikulum Merdeka & KBC

**Tanggal:** 21 November 2026 (WIB)  
**Basis audit source:** `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`  
**Status:** Persyaratan arsitektur wajib; dokumen spesifikasi; belum menjadi persetujuan implementasi W1/W6.  
**Jenis perubahan:** Dokumentasi tambahan saja. Tidak ada source aplikasi, migration, database, atau konfigurasi runtime yang diubah.

## 1. Kedudukan kurikulum — keputusan wajib

1. Kurikulum Merdeka adalah kerangka struktur akademik dan pembelajaran madrasah: mata pelajaran, fase, CP, TP, ATP, perencanaan, pembelajaran, asesmen, dan hasil belajar.
2. Kurikulum Berbasis Cinta (KBC) adalah ruh, landasan nilai, dan penguatan karakter yang diintegrasikan ke dalam pelaksanaan Kurikulum Merdeka.
3. Kurmer dan KBC adalah satu kesatuan terintegrasi. Tidak boleh ada pilihan konfigurasi “Kurmer atau KBC”.
4. KBC bukan kurikulum alternatif yang menggantikan Kurmer. Dilarang membuat struktur mapel, fase, CP, TP, ATP, atau kode RDM tandingan khusus KBC.
5. Panca Cinta dapat diterapkan lintas mata pelajaran, termasuk mata pelajaran umum, bukan hanya rumpun agama.
6. Pembelajaran Mendalam dan 8 Dimensi Profil Lulusan (DPL) terhubung dengan perencanaan dan evaluasi pembelajaran, bukan katalog label yang berdiri sendiri.

Rujukan kebijakan utama:
- KMA Nomor 1503 Tahun 2025 tentang perubahan atas KMA Nomor 450 Tahun 2024. Lampiran I memuat Pembelajaran Mendalam, 8 dimensi profil lulusan, dan nilai Panca Cinta. Dokumen: https://sulteng.kemenag.go.id/files/show/20251020162801surat-penyampaian-kma-kurikulum-2025%2Blampiran_compressed-tzR.pdf
- Kepdirjen Pendidikan Islam Nomor 6077 Tahun 2025 tentang Panduan Kurikulum Berbasis Cinta. Dokumen panduan yang disampaikan melalui surat Ditjen Pendis B-279/Dt.I.I/PP.00/07/2025: https://assets.zyrosite.com/YNqBxzZ8EVI1XRal/panduan-kurikulum-berbasis-cinta-2025-dan-pengantar-YKblkllNGvhpRq7W.pdf

Nomenklatur resmi harus disalin dari dokumen resmi, bukan ditulis ulang berdasarkan asumsi. Jika ada perbedaan redaksi antardokumen, simpan sumber, versi, dan catatan rekonsiliasi; jangan diam-diam menormalisasi istilah.

## 2. Master Mata Pelajaran MI

Portal Admin Madrasah menyediakan pengelolaan master mata pelajaran untuk:
- Mata pelajaran umum MI.
- Rumpun PAI: Al-Qur'an Hadis, Akidah Akhlak, Fikih, SKI, dan Bahasa Arab.
- Muatan Lokal (Mulok).

Atribut minimum yang perlu ditetapkan setelah audit skema:
- identitas stabil (UUID);
- kode mapel dan nama resmi;
- nama singkat bila tersedia;
- kelompok/rumpun dan jenis mapel (umum, PAI, atau Mulok);
- status aktif/tidak aktif/tidak berlaku;
- sumber referensi, identitas dokumen/versi, tanggal berlaku, dan catatan verifikasi;
- kode RDM beserta status verifikasinya.

**Gerbang kode RDM:** jangan membuat, menebak, atau mengisi kode berdasarkan pola buatan. Kode hanya boleh ditandai VERIFIED setelah dibandingkan dengan referensi RDM resmi yang relevan untuk jenjang MI dan versi yang berlaku. Jika referensi resmi belum tersedia/terakses, simpan status PENDING_VERIFICATION dan jangan tampilkan kode sebagai kode RDM sah. Portal RDM saat audit ini menampilkan pemberitahuan pemeliharaan; verifikasi kode karenanya masih terbuka.

Mulok harus dapat ditambahkan/diubah sesuai konteks madrasah, tetapi tidak boleh diberi kode RDM resmi tanpa sumber yang membuktikannya.

## 3. Bank CP, TP, dan ATP

Admin dapat menambah dan mengubah referensi CP dan TP yang bersumber dari dokumen resmi. Pengelompokan minimal:
- kurikulum/kerangka akademik;
- fase;
- mata pelajaran;
- CP induk;
- TP yang terkait dengan CP;
- ATP yang menyusun TP secara berurutan.

Setiap referensi resmi harus memiliki provenance:
- nama/nomor dokumen sumber dan penerbit;
- URL/identitas arsip sumber bila tersedia;
- versi/nomor keputusan;
- tahun berlaku dan tanggal mulai berlaku;
- tanggal impor/pencatatan;
- status aktif, tidak aktif, atau tidak berlaku;
- histori perubahan dan aktor perubahan.

Validasi wajib:
1. CP harus menunjuk ke kurikulum, fase, dan mapel yang sama/valid.
2. TP harus memiliki CP induk yang valid; TP tidak boleh berdiri sebagai referensi lepas.
3. ATP hanya boleh menyusun TP yang sesuai dengan mapel/fase/kurikulum ATP tersebut.
4. Relasi antarentitas harus berada pada tenant/instalasi yang sama, kecuali referensi global resmi yang provenance dan registrasinya terbukti.
5. Guru memilih CP dan TP dari dropdown bertingkat (mapel → fase → CP → TP); Guru tidak mengetik ulang atau mengubah master.
6. Jika CP/TP berubah atau tidak berlaku, referensi lama tetap dapat dibaca pada histori soal, asesmen, dan laporan yang sudah menggunakannya.

CP/TP/ATP tidak boleh diisi dengan contoh generik atau teks buatan sebagai data resmi. Seed awal hanya dilakukan dari sumber resmi yang sudah diverifikasi dan dicatat.

## 4. Master Panca Cinta dan 8 DPL

### 4.1 Panca Cinta

Gunakan lima nomenklatur yang dinyatakan dalam KMA 1503/2025:
1. Cinta Allah dan Rasul-Nya.
2. Cinta Ilmu.
3. Cinta Lingkungan.
4. Cinta Diri dan Sesama Manusia.
5. Cinta Tanah Air.

Panduan KBC Kepdirjen 6077/2025 menyebut Panca Cinta sebagai lima topik Kurikulum Cinta dan memberi contoh integrasi intrakurikuler lintas mapel. Sistem harus mendukung pelabelan lebih dari satu nilai pada satu aktivitas/soal jika sesuai desain pembelajaran, tanpa memaksa KBC menjadi mapel atau kurikulum terpisah.

### 4.2 Delapan Dimensi Profil Lulusan

Gunakan nomenklatur KMA 1503/2025:
1. Keimanan dan ketakwaan kepada Tuhan YME.
2. Kewargaan.
3. Penalaran kritis.
4. Kreativitas.
5. Kolaborasi.
6. Kemandirian.
7. Kesehatan.
8. Komunikasi.

Sumber resmi, versi, tahun berlaku, deskripsi, dan status referensi harus tersimpan. Jangan mengarang definisi atau mengganti istilah resmi.

Panca Cinta dan DPL digunakan untuk:
- pelabelan soal dan aktivitas pembelajaran;
- perencanaan pembelajaran mendalam;
- asesmen dan evaluasi;
- survei karakter sesuai desain akademik;
- laporan perkembangan/evaluasi pembelajaran.

KBC/Panca Cinta dan DPL dapat dipakai lintas mapel. Keduanya tidak mengubah struktur mapel, fase, CP, TP, ATP, maupun kode RDM.

## 5. Hak akses dan lifecycle master

- Hanya role `ADMIN_MADRASAH` yang dapat menambah dan mengubah master kurikulum.
- Admin Madrasah dan Operator tetap satu identitas/role/portal sesuai keputusan proyek; tidak dibuat profil hak akses kedua.
- Guru hanya membaca/memilih referensi master dalam pembelajaran, bank soal, dan asesmen.
- Guru tidak boleh create/update/delete master kurikulum.
- Tidak tersedia penghapusan permanen untuk master yang sudah tersimpan atau pernah direferensikan.
- Referensi yang tidak berlaku harus dinonaktifkan/ditandai obsolete dengan tanggal berlaku akhir, alasan, dan audit trail.
- Jika suatu referensi sudah digunakan, perubahan substantif harus menghasilkan versi baru atau histori perubahan yang tidak mengubah snapshot lama.
- Foreign key dan kebijakan penghapusan harus mencegah cascade yang menghilangkan histori CP/TP/ATP, mapping soal, asesmen, atau laporan. Gunakan RESTRICT/soft deactivation sesuai relasi yang dibuktikan.
- Semua perubahan dicatat: actor, tenant, waktu server, nilai sebelum/sesudah, alasan, dan correlation/request ID bila tersedia.

## 6. Integrasi satu ekosistem

Integrasi harus konsisten di:
1. identitas dan pengaturan kurikulum madrasah;
2. master mapel, fase, CP, TP, ATP;
3. perencanaan pembelajaran dan Pembelajaran Mendalam;
4. bank soal dan metadata soal;
5. asesmen, ujian, dan hasil belajar;
6. Panca Cinta dan DPL;
7. survei karakter sesuai rancangan akademik;
8. laporan akademik dan evaluasi pembelajaran.

Pada Bank Soal Guru:
- pilihan mapel/fase membatasi daftar CP;
- pilihan CP membatasi daftar TP;
- Panca Cinta dan DPL tersedia sebagai label terpisah yang dapat dipilih;
- backend memvalidasi seluruh referensi, tenant, status, versi, dan relasi;
- soal menyimpan ID referensi dan snapshot label/versi agar histori tetap terbaca ketika master berubah;
- tidak boleh ada ID CP/TP, mapel, Panca Cinta, atau DPL lintas tenant tanpa otorisasi referensi global yang sah.

Spesifikasi integrasi Portal Guru dan Siswa pada tahap berikutnya wajib mengadopsi kontrak referensi ini. Siswa menerima konten/aktivitas/asesmen yang telah dipetakan; Siswa tidak mengelola master atau mengubah label akademik.

## 7. Implikasi audit source saat ini

Audit statis read-only pada source pin menemukan:
- `subjects`, `curricula`, `phases`, `curriculum_subjects`, `elements`, `learning_outcomes`, `learning_outcome_versions`, `learning_objectives`, `learning_paths`, dan version/association ATP sudah tersedia.
- `CurriculumManagement` saat ini menampilkan CP/TP dan hanya menyediakan alur tambah TP; belum ditemukan alur Admin yang lengkap untuk tambah/ubah CP dan ubah TP, mapel/Mulok, Panca Cinta, atau DPL.
- `learning_outcome_versions` sudah memiliki versi CP, tetapi belum memiliki field provenance dokumen resmi/tanggal berlaku yang memadai.
- `learning_objectives` memiliki soft delete tetapi belum ditemukan version table khusus TP.
- `learning_paths` memiliki versi dan susunan TP, tetapi provenance sumber/tahun berlaku perlu diperiksa dan dilengkapi.
- `multi_curriculums` menyimpan kode `MERDEKA`, `KBC`, `K13`, dan `CUSTOM` sebagai jenis kurikulum sejajar. Pola ini bertentangan dengan keputusan Kurmer+KBC sebagai satu kesatuan; perlu desain kompatibilitas dan rekonsiliasi data sebelum diubah.
- `multi_curriculum_structures` mengenal node generik `DIMENSI_CINTA`, tetapi belum membuktikan katalog Panca Cinta dan DPL resmi yang terpisah, terversi, dan memiliki provenance.
- `multi_learning_outcomes` menyimpan tipe generik CP/TP/ATP/KBC_CINTA, namun tidak otomatis membuktikan hubungan integritas dengan tabel legacy `learning_outcomes`/`learning_objectives`.
- `Question` belum menunjukkan relasi Eloquent langsung ke CP/TP legacy atau ke `QuestionMultiCurriculumMapping`. Tabel mapping multi-kurikulum yang ada menghubungkan soal ke `multi_learning_outcomes`; konsolidasi relasi masih diperlukan.
- Pencarian tree source tidak menemukan nama file/migration yang secara eksplisit menyebut survey, character, Panca Cinta, atau profile dimension. Ini bukan bukti bahwa fitur survei tidak ada di seluruh source; audit lebih lanjut pada semua route, tabel, dan alur akademik tetap diperlukan.

Temuan di atas bukan persetujuan untuk membuat ulang semua tabel. Implementasi harus dimulai dengan audit mendalam terhadap migration, data legacy, model, service, policy, API, Livewire, view, seeder, dan test.

## 8. Acceptance criteria W6

W6 hanya dapat dinyatakan diterima jika seluruh syarat berikut terbukti:

1. Admin Madrasah dapat menambah dan mengubah mapel umum, rumpun PAI, Mulok, kode RDM yang telah diverifikasi, CP, TP, ATP, Panca Cinta, dan DPL.
2. Referensi mapel dan kode RDM memiliki sumber/version/effective-year; kode yang belum diverifikasi tidak ditampilkan sebagai kode resmi.
3. CP terhubung valid ke kurikulum, fase, dan mapel; TP terhubung ke CP; ATP hanya menyusun TP yang konsisten.
4. Lima Panca Cinta dan delapan DPL tersimpan dengan nomenklatur resmi, sumber, versi, tahun berlaku, dan histori.
5. Tidak ada konfigurasi Kurmer ATAU KBC; tidak ada KBC sebagai struktur mapel/fase/CP/TP/ATP tandingan.
6. Guru dapat memilih CP/TP melalui dropdown berantai dan memilih Panca Cinta/DPL tanpa hak mengubah master.
7. Integrasi Bank Soal menyimpan referensi yang tervalidasi dan snapshot histori; tidak ada referensi orphan/cross-tenant.
8. Master yang digunakan tidak dapat dihapus permanen; deactivation/obsolete mempertahankan relasi dan histori.
9. Perubahan master menghasilkan audit trail lengkap.
10. Survei karakter dan laporan akademik dapat membaca referensi Panca Cinta/DPL sesuai rancangan; tidak mengubah referensi master.
11. Uji negatif membuktikan Guru tidak dapat memutasi master, tenant lain tidak dapat mengakses data, dan Admin tidak dapat menghapus permanen master.
12. Integration test dijalankan pada MySQL 8+ InnoDB disposable; SQLite saja tidak cukup untuk membuktikan composite FK dan perilaku delete.
13. Regression test memastikan Bank Soal, CP/TP, ATP, asesmen, dan laporan lama tetap terbaca.
14. Tidak ada CP/TP, kode RDM, atau nomenklatur resmi yang dibuat dari asumsi.

## 9. Gerbang implementasi

Dokumen ini hanya menambah dan memperjelas persyaratan. W0 tetap menunggu penutupan dan persetujuan pengguna. W1 belum disetujui/dimulai. Tidak ada perubahan source, migration, seeder, database, merge PR, atau deployment dalam pekerjaan dokumentasi ini.
