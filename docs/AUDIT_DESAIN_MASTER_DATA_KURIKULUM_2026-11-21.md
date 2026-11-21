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
