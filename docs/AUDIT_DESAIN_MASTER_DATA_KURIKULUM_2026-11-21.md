# AUDIT & SPESIFIKASI MASTER DATA KURIKULUM
Tanggal: 2026-11-21
Repository: zane0184/cbt_november
Branch audit: work/wa01-package0-implementation-20261121
HEAD saat audit: b9ae0537f842cf2e54a62d684c8d9ccafb4312cf

## 1. Status dan batas audit

Audit ini merupakan audit source statis terhadap branch GitHub di atas. Bukan audit database runtime. Tidak ada koneksi ke database produksi/staging pada sesi ini, sehingga isi tabel aktual, jumlah CP/TP/ATP, asal setiap baris data, dan hasil query relasi belum dapat diverifikasi.

Pemeriksaan tree repository (835 entri) menemukan:
- Tidak ada file dump database .sql.
- Tidak ada dataset kurikulum .json/.csv/.xlsx/.xls/xml yang dapat diidentifikasi sebagai katalog CP/TP/ATP.
- Seeder yang teridentifikasi hanya DatabaseSeeder.php dan SystemTenantSeeder.php; tidak ditemukan seeder katalog kurikulum.
- DatabaseSeeder membuat role/permission dan memanggil RolePermissionBootstrapper; tidak mengisi katalog CP/TP/ATP/elemen/fase/mapel.
- Karena itu, kelengkapan data master di database TIDAK BOLEH dinyatakan tersedia atau lengkap.

## 2. Temuan source aktual

### 2.1 Halaman Admin Kurikulum
File: backend-laravel/app/Livewire/Admin/CurriculumManagement.php
View: backend-laravel/resources/views/livewire/admin/curriculum-management.blade.php

Temuan:
- Halaman membaca LearningOutcome beserta subject, phase, currentVersion, objectives.
- Fungsi createTp() memungkinkan Admin membuat TP secara manual.
- Form TP meminta Admin mengisi kode, judul, deskripsi, dan memilih CP induk.
- View menampilkan CP dan daftar TP; tidak ditemukan alur pengelolaan katalog resmi yang lengkap untuk fase, elemen, CP, ATP, KKTP, mata pelajaran, versi referensi, impor, validasi, atau sinkronisasi.
- Fungsi Admin ini memeriksa role ADMIN_MADRASAH/ADMIN dan permission curriculum.manage. Ini belum merupakan pembuktian keseluruhan authorization route/API.

### 2.2 API kurikulum
File:
- backend-laravel/app/Http/Controllers/Api/V1/CurriculumController.php
- backend-laravel/app/Http/Controllers/Api/V1/LearningOutcomeController.php
- backend-laravel/app/Http/Controllers/Api/V1/LearningObjectiveController.php
- backend-laravel/app/Http/Controllers/Api/V1/LearningPathController.php

Temuan statis:
- CurriculumController memakai policy untuk CRUD Curriculum.
- LearningOutcomeController index/store/show/update/destroy yang diperiksa tidak menunjukkan authorize policy eksplisit di setiap operasi.
- LearningObjectiveController index/store/show/update/destroy yang diperiksa tidak menunjukkan authorize policy eksplisit di setiap operasi; store memvalidasi tenant untuk parent CP, tetapi validasi tenant parent tidak menggantikan otorisasi role/permission.
- LearningPathController memvalidasi sebagian foreign keys terhadap tenant pada store, tetapi otorisasi policy/role tidak tampak di operasi yang diperiksa.
- Query index() pada sejumlah controller menggunakan model langsung tanpa pembatas tenant eksplisit yang terlihat pada baris tersebut. Harus diaudit bersama global scope/model boot/route middleware sebelum kesimpulan final; jangan menganggap aman atau rentan hanya dari satu lapisan.
- Temuan ini adalah kandidat gap keamanan yang wajib diuji dan diperbaiki sebelum fitur dipakai.

### 2.3 Alur Guru / Bank Soal
File: backend-laravel/app/Livewire/Admin/QuestionBank.php (namespace component Admin, namun guard hanya mengizinkan GURU)
View: backend-laravel/resources/views/livewire/admin/question-bank.blade.php

Temuan:
- boot()/mount() mensyaratkan tenant, role GURU, bukan ADMIN_MADRASAH, dan permission question.view.
- Guru mengisi relasi learning_outcome_id dan learning_objective_id pada versi soal.
- render() memuat CP berdasarkan subject_id dan TP berdasarkan CP yang dipilih.
- Ini merupakan bukti alur pilihan CP/TP pada Bank Soal, tetapi bukan bukti pilihan kelas/rombel -> fase -> mapel -> elemen -> CP -> TP -> ATP.
- Tidak ditemukan alur ATP pada komponen Bank Soal yang diperiksa.
- Relasi CP/TP saat ini menggunakan LearningOutcome/LearningObjective lama, bukan terbukti menggunakan katalog multi_curriculum sebagai sumber tunggal.
- Perlu audit lebih lanjut pada semua alur Pembelajaran, Perencanaan Pembelajaran, Asesmen, Evaluasi, dan Laporan.

### 2.4 Skema multi-kurikulum
Migration:
- 2026_11_05_190004_create_multi_curriculums_table.php
- 2026_11_05_190005_create_multi_curriculum_versions_table.php
- 2026_11_05_190006_create_multi_curriculum_structures_table.php
- 2026_11_05_190007_create_multi_learning_outcomes_table.php
- 2026_11_05_190008_create_question_multi_curriculum_mappings_table.php
- 2026_11_05_200001_fase1c_multi_curriculum_integrity.php

Temuan:
- Struktur tabel mendukung versioning, struktur hierarkis, node_type seperti FASE/GRADE_LEVEL/ELEMENT/DIMENSI_CINTA/PETA_KASIH, dan outcome_type seperti CP/TP/ATP/KKTP/KBC_CINTA/K13_KD.
- Skema tidak membuktikan isi tabel, asal resmi, kelengkapan, keterhubungan CP-TP-ATP, maupun pemakaian di seluruh modul.
- Harus ada pemetaan/strategi migrasi yang jelas dari LearningOutcome/LearningObjective/LearningPath lama ke katalog canonical; jangan membuat dua sumber kebenaran yang tidak sinkron.

## 3. Referensi regulasi/data resmi yang sudah ditemukan

Dokumen berikut merupakan dasar verifikasi sumber; sebelum impor data, periksa naskah lengkap, lampiran, nomor keputusan, tanggal, cakupan jenjang/mapel, perubahan/pencabutan, dan checksum file yang diarsipkan:
1. KMA Nomor 1503 Tahun 2025, perubahan atas KMA 450 Tahun 2024 tentang Pedoman Implementasi Kurikulum pada RA dan Madrasah. Dokumen PDF resmi yang ditemukan di domain Kemenag: https://sulteng.kemenag.go.id/files/show/20251020162801surat-penyampaian-kma-kurikulum-2025%2Blampiran_compressed-tzR.pdf
2. Keputusan Kepala BSKAP Nomor 046/H/KR/2025 tentang Capaian Pembelajaran PAUD, pendidikan dasar, dan menengah. Salinan PDF ditemukan di domain resmi kurikulum Kemendikdasmen: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
3. Hasil penelusuran menunjukkan Keputusan Nomor 020 Tahun 2026 mengubah sebagian CP Pendidikan Agama dan Budi Pekerti dalam Keputusan 046/H/KR/2025. Dokumen primer perubahan 020/2026 harus diambil dan diverifikasi sebelum mengimpor CP PAI umum.
4. Untuk CP PAI dan Bahasa Arab madrasah, rujukan yang disebut dalam publikasi/buku Kemenag adalah Keputusan Direktur Jenderal Pendidikan Islam Nomor 9941 Tahun 2025. Contoh publikasi/buku resmi Kemenag: https://cms2025.kemenag.go.id/storage/files/Alquran%20Hadis%20Kelas%203.pdf. Dokumen primer SK Dirjen 9941/2025 dan lampiran lengkap harus diarsipkan serta diverifikasi sebelum impor.
5. Keputusan Dirjen Pendis Nomor 6077 Tahun 2025 tentang Panduan Kurikulum Berbasis Cinta disebut dalam publikasi buku Kemenag; dokumen primer harus diverifikasi sebelum mengisi katalog Panca Cinta/KBC.

Catatan: sumber kebijakan/CP bukan otomatis sumber TP dan ATP baku. TP dan ATP harus diimpor hanya jika dokumen resmi yang secara eksplisit memuatnya telah ditemukan dan diverifikasi. Jika tidak, sistem harus menyimpan TP/ATP sebagai rancangan yang disusun pendidik sesuai proses yang diizinkan oleh kebijakan, bukan mengklaimnya sebagai master resmi atau menghasilkannya dengan AI. Keputusan bisnis final untuk alur penyusunan TP/ATP perlu diselaraskan dengan ketentuan kurikulum dan kebutuhan pengguna; ketentuan pengguna pada proyek ini adalah Guru hanya memilih referensi yang telah disediakan Admin.

## 4. Spesifikasi desain yang ditetapkan

### 4.1 Sumber dan provenance
Setiap paket referensi harus menyimpan sekurang-kurangnya:
- source_authority, source_document_number, source_title, source_url, publication/effective date;
- jurisdiction/education_level, curriculum_code, subject scope, phase/grade scope;
- source_file checksum (SHA-256), imported_at, importer, package_version;
- status verifikasi, catatan perubahan, dan relasi ke versi sebelumnya.
Tidak boleh ada baris CP/TP/ATP yang diberi label resmi jika sumber primer dan bagian dokumen pendukungnya tidak tercatat.

### 4.2 Katalog canonical dan tenant overlay
- Katalog referensi resmi global dikelola sebagai dataset berversi dan tidak dapat diubah langsung oleh Admin Madrasah.
- Admin Madrasah dapat mengaktifkan/menggunakan referensi global, menambahkan muatan lokal, dan membuat penyesuaian lokal hanya sebagai overlay/ekstensi tenant yang jelas ditandai, tidak mengubah data global.
- Untuk kebutuhan koreksi metadata global, Admin mengajukan perubahan/sinkronisasi sesuai kewenangan; perubahan paket global dilakukan oleh pengelola platform setelah verifikasi sumber.
- Referensi yang sudah digunakan tidak boleh hard-delete; gunakan status aktif/nonaktif/arsip dan versi immutable.
- Menonaktifkan referensi tidak boleh menghilangkan pilihan dari histori record lama.

### 4.3 Hak akses
- ADMIN_MADRASAH: read seluruh master yang relevan; tambah/ubah/nonaktifkan ekstensi tenant termasuk muatan lokal; kelola aktivasi; jalankan sinkronisasi yang diizinkan; tidak boleh hard-delete data yang sudah direferensikan.
- GURU: read/select saja; tidak ada endpoint atau Livewire action create/update/delete master. Hak ini harus ditegakkan di controller/policy/service dan database scope, bukan hanya menyembunyikan tombol.
- SUPER_ADMIN/platform: mengelola paket global setelah sumber diverifikasi dan proses rilis disetujui.
- Role gabungan/stale permission harus diuji; role Admin tidak boleh mendapat akses Guru hanya karena memiliki permission lama.

### 4.4 Alur dropdown Guru
1. pilih kelas/rombel -> server menentukan grade level dan fase berdasarkan konfigurasi jenjang/kurikulum aktif;
2. pilih mata pelajaran dari penugasan Guru pada rombel/kelas tersebut;
3. pilih elemen yang tersedia untuk mapel dan fase;
4. pilih CP yang terhubung ke elemen/mapel/fase;
5. pilih TP yang merupakan anak/relasi CP yang dipilih;
6. pilih ATP yang terkait dengan TP/urutan yang benar.
Setiap perubahan parent mengosongkan child selection. Server memvalidasi ulang seluruh relasi, tenant, versi, status aktif, penugasan Guru, dan rombel pada saat simpan. Tidak menerima ID child yang tidak berada di parent chain yang dipilih.

### 4.5 Integrasi lintas modul
Canonical curriculum reference ID + version ID harus dipakai secara konsisten oleh:
- pembelajaran;
- perencanaan pembelajaran;
- bank soal;
- asesmen;
- ujian/snapshot;
- evaluasi;
- laporan hasil belajar.
Snapshot/riwayat harus menyimpan referensi versi yang digunakan, bukan mengambil teks terkini secara dinamis setelah master berubah.

### 4.6 Sinkronisasi idempoten
- Import/sync berdasarkan source authority + document number + document version + stable source code.
- Upsert idempoten; unique constraints mencegah duplikasi.
- Preview/diff sebelum apply, validasi parent-child, orphan, kode duplikat, mapel/fase tak dikenal, checksum, dan jumlah record.
- Sinkronisasi tidak menghapus record lokal atau histori; perubahan sumber menghasilkan versi baru atau status deprecated.
- Simpan audit log, hasil sync, rejected rows, dan rollback plan.
- Jangan jalankan seeder/import pada database produksi dalam tahap audit.

## 5. Acceptance criteria sebelum dinyatakan selesai

1. Dataset resmi terarsip dengan URL primer, nomor dokumen, tanggal, checksum dan cakupan MI.
2. Manifest import menyatakan jumlah record per kurikulum/mapel/fase/elemen/CP/TP/ATP; semua jumlah dapat direkonsiliasi dengan sumber.
3. Tidak ada CP/TP/ATP fiktif/AI-generated yang ditandai sebagai data resmi.
4. Database staging menunjukkan data benar-benar terisi; query memeriksa duplikasi, orphan, relasi salah, tenant leakage, dan status/version.
5. Dropdown Guru diuji browser/runtime dari rombel sampai ATP, termasuk reset cascade dan data kosong.
6. Admin bisa mengelola ekstensi/muatan lokal; tidak bisa menghapus permanen referensi yang telah digunakan.
7. Guru tidak dapat membuat/mengubah master lewat UI, Livewire calls, API langsung, atau stale permissions.
8. Uji multi-tenant memastikan madrasah tidak dapat mengubah/melihat overlay tenant lain.
9. Uji histori memastikan update/nonaktif/sinkronisasi master tidak mengubah pembelajaran, asesmen, snapshot ujian, atau laporan terdahulu.
10. Uji sync berulang membuktikan tidak ada duplikasi dan hasilnya idempoten.
11. Semua integrasi lintas modul diverifikasi; tidak cukup hanya tabel atau halaman CRUD.
12. Runtime tests (PHP 8.3, dependency lock, MySQL 8/InnoDB disposable staging) harus dijalankan dan hasilnya dicatat. Tidak ada migrasi eksperimental pada produksi.

## 6. Tahapan kerja berikutnya

A. Selesaikan audit data aktual: dapatkan akses database staging/isolated dump yang sah; inventarisasi isi tabel lama dan multi_curriculum; jangan sentuh produksi.
B. Akuisisi sumber primer: KMA 1503/2025, SK BSKAP 046/2025 dan perubahannya 020/2026, SK Dirjen Pendis 9941/2025 dan 6077/2025; verifikasi lampiran dan ruang lingkup MI.
C. Rancang model canonical dan strategi migrasi/backfill dari LearningOutcome/LearningObjective/LearningPath tanpa memutus referensi lama.
D. Implementasi importer/catalog sync di staging dengan backup, preview, validasi, idempotency dan audit log.
E. Implementasi hak Admin/Guru dan cascading dropdown end-to-end.
F. Integrasi modul dan uji histori, keamanan, multi-tenant, sinkronisasi.
G. Update checkpoint dan laporan terpisah AUDIT/DESAIN/IMPLEMENTASI/PENGUJIAN.

## 7. Status tahap saat ini

- AUDIT: source statis dilakukan; katalog data resmi tidak ditemukan di repo; runtime DB tidak tersedia untuk diperiksa.
- DESAIN: spesifikasi pada dokumen ini ditetapkan sebagai arah implementasi.
- IMPLEMENTASI: belum ada perubahan source aplikasi untuk Master Kurikulum pada audit ini.
- PENGUJIAN: belum ada test runtime/database dijalankan.
- Status: BELUM SELESAI / BELUM SIAP PRODUKSI.


## 8. Koreksi kepatuhan regulasi sebelum Paket 5 (ditambahkan 2026-11-21)

Daftar regulasi yang ditetapkan pengguna sebagai baseline wajib:
1. UU Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional.
2. PP Nomor 57 Tahun 2021 tentang Standar Nasional Pendidikan sebagaimana diubah dengan PP Nomor 4 Tahun 2022.
3. Permendikbudristek Nomor 12 Tahun 2024 tentang Kurikulum pada PAUD, Pendidikan Dasar dan Menengah sebagaimana diubah dengan Permendikdasmen Nomor 13 Tahun 2025.
4. KMA Nomor 450 Tahun 2024 tentang Pedoman Implementasi Kurikulum pada RA/Madrasah sebagaimana diubah dengan KMA Nomor 1503 Tahun 2025.
5. Keputusan Direktur Jenderal Pendidikan Islam Nomor 6077 Tahun 2025 tentang Panduan Kurikulum Berbasis Cinta.

### 8.1 Hasil verifikasi awal sumber publik
- UU 20/2003: metadata JDIH BPK menyatakan berlaku; halaman memuat catatan putusan MK yang memberi tafsir bersyarat pada Pasal 34 ayat (2). Implementasi yang merujuk pasal tersebut wajib mempertimbangkan putusan MK terkait. Sumber: https://peraturan.bpk.go.id/Home/Details/43920%20%20/uuno-20-tahun-2003
- PP 57/2021: JDIH BPK mencatat diubah dengan PP 4/2022. Sumber: https://peraturan.bpk.go.id/Details/165024/pp-o-57-tahun-2021
- PP 4/2022: JDIH BPK mencatat sebagai perubahan atas PP 57/2021. Sumber: https://peraturan.bpk.go.id/Details/196151/pp-no-4-tahun-2022
- Permendikbudristek 12/2024: JDIH BPK menyatakan berlaku dan mencatat perubahan melalui Permendikdasmen 13/2025. Sumber: https://peraturan.bpk.go.id/Details/281847/permendikbudriset-no-12-tahun-2024
- Permendikdasmen 13/2025: JDIH BPK menyatakan berlaku, tanggal berlaku 15 Juli 2025, dan merupakan perubahan atas Permendikbudristek 12/2024. Sumber: https://peraturan.bpk.go.id/Details/322506/permendikdasmen-no-13-tahun-2025
- KMA 450/2024 jo KMA 1503/2025: publikasi resmi Kanwil Kemenag menyebut KMA 1503/2025 sebagai perubahan atas KMA 450/2024 dan menyatakan cakupan RA, MI, MTs, MA, MAK. Sumber: https://dki.kemenag.go.id/berita/kemenag-sosialisasikan-kma-1503-tahun-2025-perkuat-implementasi-kurikulum-madrasah-TQt3E . Salinan PDF KMA 1503 yang ditemukan pada penelusuran awal bukan host JDIH pusat; tetap wajib memperoleh/mengarsipkan salinan primer resmi dan memeriksa metadata penetapan serta seluruh lampirannya sebelum menjadikan rincian teknis sebagai aturan mesin.
- Kepdirjen Pendis 6077/2025: PDF yang dipublikasikan pada domain Kanwil Kemenag memuat judul keputusan dan nomor 6077/2025. Sumber: https://sulteng.kemenag.go.id/files/show/panduan-kurikulum-berbasis-cinta-baT.pdf . Salinan ini harus diarsipkan, diberi checksum, dan dibandingkan dengan salinan resmi Ditjen Pendis bila tersedia.

### 8.2 Prinsip penerjemahan regulasi ke desain sistem
- KBC/Panca Cinta diperlakukan sebagai pendekatan/panduan yang mengintegrasikan nilai ke pembelajaran dan budaya madrasah; jangan memodelkannya otomatis sebagai mata pelajaran baru atau mengganti Kurikulum Merdeka.
- Pembelajaran Mendalam dan KBC perlu tercermin pada perencanaan, pelaksanaan, asesmen, kokurikuler, dan laporan sesuai ketentuan dan panduan yang relevan.
- Permendikdasmen 13/2025 tidak diperlakukan sebagai kurikulum baru; sistem menyimpan hubungan perubahan regulasi dan versi, bukan membuat identitas kurikulum baru tanpa dasar.
- KMA 1503/2025 menjadi rujukan terbaru untuk implementasi kurikulum madrasah pada cakupan RA/MI/MTs/MA/MAK, dengan KMA 450/2024 tetap tercatat sebagai regulasi asal yang diubah.
- Setiap ketentuan teknis yang akan diwujudkan sebagai validasi wajib ditautkan ke pasal/lampiran/halaman sumber, bukan hanya nama regulasi.

### 8.3 Gate kepatuhan Paket 5
Paket 5 tidak boleh mulai mengubah source aplikasi sampai:
1. daftar regulasi dan status perubahannya diverifikasi terhadap JDIH/portal resmi;
2. dokumen primer dan lampiran diarsipkan, checksum SHA-256 dicatat;
3. matriks pasal/lampiran -> requirement sistem disetujui;
4. cakupan MI, mapel umum, PAI/Bahasa Arab, struktur fase/kelas, intrakurikuler/kokurikuler, pembelajaran dan asesmen dipetakan;
5. data katalog yang akan diimpor memiliki provenance yang dapat ditelusuri;
6. tidak ada inferensi atau konten CP/TP/ATP buatan AI yang diberi label sebagai referensi resmi.

Catatan kelengkapan pesan pengguna: daftar yang diterima dalam percakapan terpotong setelah butir 5 pada frasa "tentang Panduan Kurik...". Butir 1-5 di atas dicatat sesuai bagian yang terbaca; jika pengguna mengirim kelanjutan daftar, lampiran ini harus diperbarui sebelum Paket 5.

Status: gate kepatuhan regulasi ditetapkan; verifikasi awal sumber publik dilakukan; pengarsipan dokumen primer lengkap, matriks pasal-requirement, dan implementasi belum dilakukan.


## 9. Koreksi desain mengikat: satu kesatuan sistem kurikulum dan kewenangan profesional Guru (2026-11-21)

Bagian ini merupakan koreksi dan pengganti terhadap ketentuan yang bertentangan pada bagian 4.2, 4.3, 4.4, 5, dan catatan keputusan bisnis sebelumnya. Jika ada perbedaan, bagian 9 ini yang berlaku.

### 9.1 Model konseptual tunggal

CBT Madrasah tidak boleh menawarkan KBC, Pembelajaran Mendalam, atau 8 Dimensi Profil Lulusan sebagai pilihan kurikulum alternatif. Model konseptualnya:

- **Kurikulum Merdeka**: kerangka dan struktur kurikulum.
- **Kurikulum Berbasis Cinta (KBC)**: landasan nilai dan karakter pendidikan madrasah; Panca Cinta menjadi nilai yang diintegrasikan dalam pembelajaran.
- **Pembelajaran Mendalam**: pendekatan pembelajaran berkesadaran, bermakna, dan menggembirakan.
- **8 Dimensi Profil Lulusan**: arah capaian kompetensi dan karakter.
- **Alur akademik**: CP -> TP -> ATP -> Materi -> Bank Soal -> Asesmen -> Ujian -> Hasil Belajar.

Keempat unsur tersebut harus terhubung pada data pembelajaran, perencanaan, asesmen, evaluasi dan pelaporan. Implementasi KBC dan Pembelajaran Mendalam tidak boleh mengubah CP resmi secara diam-diam atau menciptakan mata pelajaran baru tanpa dasar regulasi.

### 9.2 Koreksi daftar regulasi dan sumber CP

Tambahan daftar wajib:
6. Keputusan Kepala BSKAP Nomor 046/H/KR/2025 tentang Capaian Pembelajaran pada PAUD, Jenjang Pendidikan Dasar dan Menengah sebagaimana diubah oleh Keputusan Kepala BKPDM Nomor 020 Tahun 2026.

Hasil verifikasi publik awal:
- Siaran pers resmi Kemendikdasmen tanggal 23 Juni 2026 menyatakan Kepka BKPDM 020/2026 mengubah CP Pendidikan Agama dan Budi Pekerti; perubahan ini terbatas pada mapel tersebut, sedangkan CP mapel lain tetap merujuk Kepka BSKAP 046/H/KR/2025. Sumber primer publik: https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti dan dokumen siaran pers: https://www.kemendikdasmen.go.id/download/file/1933
- Portal resmi kurikulum Kemendikdasmen memuat dokumen CP BSKAP 046/H/KR/2025: https://kurikulum.kemendikdasmen.go.id/file/1758643025_manage_file.pdf
- Buku ajar resmi Kemenag yang diterbitkan pada cms2025.kemenag.go.id merujuk Kepdirjen Pendis 9941 Tahun 2025 untuk CP PAI dan Bahasa Arab madrasah, misalnya: https://cms2025.kemenag.go.id/storage/files/Alquran%20Hadis%20Kelas%201.pdf dan menampilkan CP Al-Qur'an Hadis MI yang secara eksplisit menyebut Kepdirjen 9941/2025: https://cms2025.kemenag.go.id/storage/files/Alquran%20Hadis%20Kelas%203.pdf
- Oleh sebab itu, pemetaan CP MI wajib membedakan sumber CP mapel umum dari sumber CP mapel PAI dan Bahasa Arab madrasah. Kepka BKPDM 020/2026 tidak boleh diterapkan secara pukul rata ke semua mapel, dan CP madrasah tidak boleh diimpor hanya dari dokumen nasional yang tidak mencakup kekhususan mapel madrasah.
- 8 Dimensi Profil Lulusan tercantum dalam Standar Kompetensi Lulusan yang dirujuk Permendikdasmen 10/2025; portal Pusat Standar dan Kebijakan Pendidikan menjelaskan delapan dimensi tersebut: https://pskp.kemendikdasmen.go.id/standar-nasional-pendidikan-a/standar-penilaian-pendidikan
- Permendikdasmen 13/2025 menguatkan arah kebijakan melalui 8 dimensi profil lulusan dan Pembelajaran Mendalam; siaran pers resmi: https://www.kemendikdasmen.go.id/siaran-pers/13278-permendikdasmen-nomor-13-tahun-2025-menguatkan-arah-kebijakan-melalui-pembelajaran-mendalam

Catatan verifikasi: hasil di atas merupakan verifikasi awal metadata dan pengumuman/dokumen publik. Sebelum paket data diimpor, tim tetap harus mengarsipkan naskah keputusan primer lengkap beserta seluruh lampiran, memastikan status berlaku/perubahan, mencatat checksum SHA-256, dan menguji cakupan MI per mapel/fase. Siaran pers atau buku ajar dapat menjadi bukti penunjuk sumber, tetapi tidak menggantikan naskah keputusan primer beserta lampirannya.

### 9.3 Hak Admin dan kewenangan Guru yang dikoreksi

- **Admin Madrasah** mengelola referensi yang tersedia bagi madrasah, aktivasi/nonaktif referensi sesuai kewenangan, template akademik, muatan lokal/ekstensi tenant, serta sinkronisasi resmi yang diizinkan. Admin tidak boleh mengubah isi CP global resmi atau menghapus referensi yang sudah digunakan.
- **Guru** dapat memilih CP dan referensi resmi yang sesuai, serta **membuat, mengembangkan, mengurutkan, dan menyesuaikan TP/ATP** berdasarkan CP, konteks pembelajaran, karakteristik dan kebutuhan peserta didik, sesuai ketentuan yang berlaku.
- TP/ATP hasil pengembangan Guru harus disimpan sebagai karya/perencanaan milik tenant dan Guru, ditautkan ke CP sumber beserta versi, fase, mapel, rombel, tahun ajaran, pembuat, waktu perubahan, dan status. Sistem harus membedakan secara tegas antara referensi resmi, template/contoh, dan rancangan TP/ATP yang dikembangkan Guru.
- Template atau contoh TP/ATP yang disediakan Admin tidak boleh diperlakukan sebagai satu-satunya pilihan, tidak mengunci Guru untuk hanya memilih, dan tidak menghapus kewenangan Guru mengembangkan rancangan sendiri.
- Guru tidak boleh mengubah teks CP resmi, fase, elemen, atau identitas mapel referensi. Bila Guru mengembangkan TP/ATP, itu bukan perubahan terhadap CP sumber.
- Riwayat rancangan TP/ATP harus berversi; perubahan tidak boleh mengubah rencana, asesmen, ujian, atau laporan historis yang sudah mereferensikan versi sebelumnya.
- Hak pengembangan TP/ATP tetap dibatasi tenant, penugasan, kelas/rombel, mapel, dan permission Guru; bukan izin membuat atau mengubah katalog CP resmi global.

### 9.4 Perubahan alur akademik Guru

Alur minimum:
1. Guru memilih kelas/rombel yang menjadi penugasannya.
2. Sistem menentukan fase dari kelas/jenjang dan konfigurasi kurikulum yang berlaku.
3. Guru memilih mapel dari penugasan yang sah.
4. Guru memilih elemen dan CP resmi yang cocok dengan mapel/fase/versi.
5. Guru memilih TP yang tersedia sebagai template/rancangan terdahulu **atau membuat dan mengembangkan TP baru** yang ditautkan ke CP.
6. Guru menyusun ATP dari TP terpilih/dikembangkan, mengurutkan tujuan, serta menyesuaikan alur sesuai kebutuhan pembelajaran.
7. Guru menghubungkan materi, aktivitas, asesmen, bank soal dan evaluasi dengan TP/ATP yang relevan.
8. Sistem memvalidasi seluruh hubungan, hak akses, tenant, penugasan, versi dan status saat simpan/publikasi.

Dropdown bertingkat tetap membantu menemukan referensi, tetapi bukan mekanisme yang membatasi Guru hanya pada daftar TP/ATP baku. Form pengembangan harus mendukung rancangan baru, duplikasi sebagai draft, revisi, pengurutan, dan pengarsipan versi tanpa mengubah sumber CP.

### 9.5 Data model dan audit trail minimum

Model data harus membedakan setidaknya:
- reference source/document/version dan checksum;
- curriculum framework/version, fase, kelas, mapel, elemen;
- CP resmi (immutable/versioned);
- TP/ATP template atau contoh (provenance dan label status);
- TP/ATP rancangan Guru/tenant (author, tenant, CP source/version, revision/version, status);
- relasi urutan ATP ke TP dan pemetaan ke materi, pembelajaran, asesmen, soal, ujian, evaluasi, hasil belajar;
- snapshot historis untuk semua proses yang sudah berlangsung.

Jangan mengasumsikan skema yang ada sudah memenuhi ini hanya karena tabel multi-kurikulum memiliki outcome_type CP/TP/ATP. Audit migration/model/constraint/query dan data aktual harus membuktikannya. Perubahan skema harus dirancang kompatibel dengan LearningOutcome/LearningObjective/LearningPath lama, disertai backup dan strategi backfill di staging.

### 9.6 Audit ulang terhadap source yang sudah diperiksa

Berdasarkan pemeriksaan statis terdahulu:
- Halaman Admin Kurikulum saat ini dapat membuat TP secara manual; belum terbukti memiliki katalog resmi lengkap, provenance, versioning, import/sync dan pengelolaan ATP yang sesuai desain.
- Bank Soal Guru saat ini menunjukkan pemilihan CP/TP berbasis LearningOutcome/LearningObjective; belum terbukti memiliki cascading penuh dari rombel/fase/mapel/elemen sampai ATP, dan belum terbukti terhubung dengan katalog canonical multi-kurikulum.
- Seeder yang diperiksa tidak mengisi dataset CP/TP/ATP. Tidak ada dump DB atau dataset katalog resmi yang ditemukan pada tree repository saat audit sebelumnya.
- Tidak ada akses DB runtime saat audit; isi tabel, data CP/TP/ATP, jumlah, sumber, relasi, duplikasi, orphan, dan konsistensi tenant belum dapat dibuktikan.
- Belum ada bukti integrasi end-to-end dengan perencanaan pembelajaran, materi, bank soal, asesmen, ujian, evaluasi dan hasil belajar.
- Karena itu, implementasi Master Data Kurikulum **BELUM LULUS AUDIT** dan Paket 5 tetap ditahan.

### 9.7 Acceptance gate yang diperbarui sebelum Paket 5 dinyatakan selesai

1. Naskah primer lengkap dan lampiran Kepka BSKAP 046/2025, Kepka BKPDM 020/2026, Kepdirjen Pendis 9941/2025, KMA 450/2024 jo 1503/2025, Kepdirjen Pendis 6077/2025, serta regulasi terkait telah diarsipkan, checksum dan cakupan MI diverifikasi.
2. Matriks mapel MI membedakan CP mapel umum, CP PAI, dan CP Bahasa Arab dengan sumber hukum/dokumen yang benar; pembaruan CP 2026 diterapkan hanya pada lingkup yang diubah.
3. Tidak ada rumusan atau kode CP/TP/ATP resmi yang dibuat oleh AI atau disalin dari sumber tidak terverifikasi.
4. Admin dapat mengelola master referensi/aktivasi/template akademik sesuai kewenangan; tidak dapat merusak referensi global atau histori.
5. Guru dapat memilih CP resmi serta mengembangkan TP/ATP sendiri; seluruh rancangan tertaut ke CP dan versi yang benar serta memiliki audit trail.
6. Dropdown bertingkat dan form pengembangan TP/ATP diuji runtime, termasuk reset cascade, validasi relasi, draft, revisi, pengurutan dan penugasan.
7. Integrasi end-to-end CP -> TP -> ATP -> Materi -> Bank Soal -> Asesmen -> Ujian -> Hasil Belajar dibuktikan melalui data dan skenario uji.
8. Versi lama tetap dapat dibaca dan tidak berubah setelah revisi CP/TP/ATP atau sinkronisasi.
9. Sync referensi resmi idempoten, tidak menggandakan data, tidak menghapus histori, dan menghasilkan audit log.
10. Uji RBAC, multi-tenant, tenant mismatch, stale permission, dan akses API/Livewire langsung lulus.
11. Semua pengujian dijalankan pada staging/DB disposable, bukan database produksi, dan hasilnya dicatat.

### 9.8 Status pelaksanaan setelah koreksi pengguna

- AUDIT: temuan statis terdahulu dipertahankan; verifikasi publik awal CP 046/2025, perubahan BKPDM 020/2026, rujukan CP madrasah Kepdirjen 9941/2025, dan 8 Dimensi telah ditambahkan. Audit database/runtime dan audit lintas modul masih tertunda.
- DESAIN: model satu kesatuan Kurikulum Merdeka + KBC/Panca Cinta + Pembelajaran Mendalam + 8 Dimensi ditetapkan. Hak Guru mengembangkan TP/ATP mengoreksi spesifikasi sebelumnya.
- IMPLEMENTASI: tidak ada source aplikasi yang diubah dalam pembaruan dokumen ini.
- PENGUJIAN: tidak ada test aplikasi/database dijalankan dalam pembaruan ini.
- KEPUTUSAN: Paket 5 belum boleh dinyatakan selesai atau siap implementasi penuh sebelum dokumen primer, matriks regulasi, data aktual, desain model dan acceptance gate di atas diverifikasi.
