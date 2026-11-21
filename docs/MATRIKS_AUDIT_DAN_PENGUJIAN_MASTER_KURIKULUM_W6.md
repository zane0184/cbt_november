# Matriks Audit dan Pengujian — Master Kurikulum Merdeka & KBC (W6)

**Tanggal:** 21 November 2026  
**Source audit pin:** `7fc45bf71ac1ebd375eba44fd57c983bf18d66e0`  
**Jenis:** Matriks target dan bukti statis awal. Bukan hasil uji runtime.  
**Status implementasi:** BELUM DIMULAI.

## A. Matriks audit source/database

| ID | Komponen | Artefak source yang diperiksa | Temuan read-only | Gap / risiko | Tindak lanjut sebelum/ketika W6 |
|---|---|---|---|---|---|
| AUD-CURR-01 | Master mapel | `2024_01_01_000002_create_academic_tables.php`, `Subject.php` | `subjects` berisi tenant_id, code, name, short_name, status, timestamps, softDeletes; unique tenant+code | Belum ada bukti klasifikasi mapel umum/PAI/Mulok dan provenance kode RDM | Audit semua pemakai Subject, import/export RDM, seeder, UI; desain metadata dan status verifikasi tanpa duplikasi tabel |
| AUD-CURR-02 | Kurikulum | migration academic core, `Curriculum.php` | `curricula` memiliki tenant, code, name, description, status, is_active, softDeletes | Belum membuktikan satu kerangka Kurmer dengan lapisan KBC terintegrasi | Tetapkan satu struktur akademik Kurmer; inventarisasi data multi_curriculums lama |
| AUD-CURR-03 | Fase/mapel | `phases`, `curriculum_subjects`, `elements` migrations/models | Relasi kurikulum, fase, mapel, elemen tersedia | Integritas konsistensi fase/mapel/tenant perlu diuji di semua jalur tulis | Tambahkan validasi domain dan composite FK yang benar; MySQL 8 test |
| AUD-CURR-04 | CP | `learning_outcomes`, `learning_outcome_versions`, `LearningOutcome.php` | CP memiliki curriculum_id, phase_id, subject_id, element_id, code; versi menyimpan title, description, status, is_current | Provenance sumber resmi, effective date/year, dan audit histori penuh belum tersedia pada skema yang diperiksa | Audit semua migration; desain source registry dan version metadata |
| AUD-CURR-05 | TP | `learning_objectives`, `LearningObjective.php` | TP terhubung ke CP, punya code/title/description/order/status, softDeletes | Tidak ditemukan version table TP pada tree yang diperiksa; endpoint destroy ada | Audit relasi yang telah dipakai; tambah versioning/deactivation tanpa hard delete |
| AUD-CURR-06 | ATP | `learning_paths`, `learning_path_versions`, `learning_path_objectives` | ATP memiliki curriculum/subject/phase, versi dan urutan TP | Validasi kesesuaian tiap TP dengan mapel/fase/kurikulum ATP perlu dibuktikan; provenance belum memadai | Audit service/API dan enforce consistency |
| AUD-CURR-07 | UI Admin | `CurriculumManagement.php`, `curriculum-management.blade.php` | Tampilan CP/TP; aksi tambah TP; filter mapel | Belum terlihat CRUD lengkap tambah/ubah CP, ubah TP, master mapel/Mulok, Panca Cinta, DPL | Jangan buat ulang sebelum audit komponen/route lain; susun UI master terpusat |
| AUD-CURR-08 | Multi-curriculum | migrations 190004–190008, 200001; models MultiCurriculum/Structure/Outcome | Struktur versi, node, outcome generik, registration, mapping soal tersedia | `MERDEKA` dan `KBC` tersimpan sebagai kode kurikulum sejajar; berpotensi membuat pilihan alternatif | Audit data aktual dan provenance; desain migrasi kompatibel, bukan migration langsung |
| AUD-CURR-09 | Panca Cinta/DPL | `multi_curriculum_structures`, `multi_learning_outcomes` | Node generik mencakup `DIMENSI_CINTA`; outcome type mencakup `KBC_CINTA` | Belum terbukti ada katalog lima Panca Cinta dan delapan DPL resmi, terpisah, terversi, dan bersumber | Audit semua seeders/data; tetapkan master referensi resmi |
| AUD-CURR-10 | Bank Soal | `Question.php`, `question_multi_curriculum_mappings` | Question punya curriculum/subject/phase/grade; mapping mengarah ke multi_learning_outcomes | Tidak terlihat relasi langsung Question → legacy CP/TP; perlu rekonsiliasi mapping tanpa orphan | Rancang integrasi CP/TP dan label KBC/DPL serta immutable snapshot |
| AUD-CURR-11 | API CP/TP/ATP | LearningOutcomeController, LearningObjectiveController, LearningPathController | Endpoint list/store/update/destroy ditemukan | Destroy tersedia; validasi role/tenant/source provenance harus diperiksa lintas middleware dan route | Tutup delete, tegakkan authorization dan tenant constraints |
| AUD-CURR-12 | Survei karakter | Recursive tree path scan untuk nama survey/character/Panca/DPL | Tidak ditemukan path yang secara eksplisit bernama survey/character/Panca/DPL | Fitur survei belum dapat dinyatakan tersedia atau tidak ada tanpa audit route/table/semua nama domain | Audit route, migration, model, service, view dan laporan sebelum desain integrasi |
| AUD-CURR-13 | Sumber resmi RDM | Portal RDM publik | Portal saat pemeriksaan menampilkan pemeliharaan | Kode mapel belum dapat diverifikasi | Tahan semua kode sampai referensi resmi dapat diakses |
| AUD-CURR-14 | Penghapusan histori | Migrations CP/TP/ATP dan multi mapping | Terdapat cascade dan soft delete; beberapa API destroy aktif | Cascade/soft delete berpotensi menghapus histori relasi; aturan belum konsisten | Buat matriks ON DELETE dan lifecycle; RESTRICT/deactivation untuk data terpakai |

## B. Matriks pengujian fungsional dan keamanan W6

| ID test | Area | Skenario | Hasil yang diwajibkan | Prioritas |
|---|---|---|---|---|
| W6-T01 | Mapel | Admin tambah mapel umum MI dengan sumber resmi | Data tersimpan pada tenant benar dan audit log terbentuk | P0 |
| W6-T02 | PAI | Admin mengelola Al-Qur'an Hadis, Akidah Akhlak, Fikih, SKI, Bahasa Arab | Nama/rumpun sesuai referensi resmi; tidak ada kode asumsi | P0 |
| W6-T03 | RDM | Input kode yang belum diverifikasi | Sistem menolak status VERIFIED; menandai PENDING_VERIFICATION | P0 |
| W6-T04 | RDM | Verifikasi kode terhadap sumber resmi dan versi berlaku | Kode, sumber, tanggal, versi, dan aktor verifikasi tercatat | P0 |
| W6-T05 | Mulok | Admin tambah/ubah mapel Mulok | Mulok terhubung ke tenant dan tahun/struktur akademik yang tepat | P0 |
| W6-T06 | CP | Admin tambah/ubah CP dengan sumber resmi | CP terikat ke satu kurikulum, fase, mapel yang valid dan provenance lengkap | P0 |
| W6-T07 | TP | Admin tambah/ubah TP | TP terikat ke CP induk yang valid; versi lama tetap tersedia | P0 |
| W6-T08 | ATP | Admin menyusun/mengubah ATP | Semua TP konsisten mapel/fase/kurikulum; urutan tersimpan | P0 |
| W6-T09 | Dropdown Guru | Guru pilih mapel, fase, CP, TP | Dropdown berantai hanya menampilkan referensi aktif dan konsisten | P0 |
| W6-T10 | Panca Cinta | Sistem menampilkan lima nilai resmi | Ejaan/nomenklatur sesuai KMA 1503/2025 dan panduan KBC | P0 |
| W6-T11 | DPL | Sistem menampilkan delapan dimensi resmi | Delapan nama resmi tepat, tanpa perubahan istilah | P0 |
| W6-T12 | Lintas mapel | Guru memberi label Panca Cinta pada soal mapel umum | KBC dapat dipakai lintas mapel, tidak dibatasi ke mapel agama | P0 |
| W6-T13 | Bank Soal | Guru menyimpan soal dengan CP/TP/Panca Cinta/DPL | Semua referensi valid; ID dan snapshot versi/label tersimpan | P0 |
| W6-T14 | Histori soal | Master CP/TP berubah setelah soal dibuat | Soal lama tetap menampilkan referensi dan versi saat dibuat | P0 |
| W6-T15 | Penonaktifan | Admin menonaktifkan CP/TP/mapel yang tidak berlaku | Tidak muncul untuk pilihan baru; histori lama tetap utuh | P0 |
| W6-T16 | No hard delete | Admin mencoba menghapus master yang telah digunakan | Ditolak; tersedia deactivation/obsolete dengan alasan | P0 |
| W6-T17 | Otorisasi Guru | Guru mencoba create/update/deactivate master | HTTP/UI menolak 403; data tidak berubah | P0 |
| W6-T18 | Tenant isolation | User tenant A menebak UUID master tenant B | Tidak ada kebocoran data atau mutasi lintas tenant | P0 |
| W6-T19 | Referential integrity | CP memakai phase/mapel beda kurikulum; TP memakai CP beda tenant | Validasi menolak dan DB constraint mencegah persist | P0 |
| W6-T20 | Audit trail | Admin mengubah sumber/status/isi referensi | Actor, tenant, waktu, before/after, alasan tercatat | P0 |
| W6-T21 | Survei karakter | Survei membaca Panca Cinta dan DPL | Pilihan berasal dari master aktif; jawaban/rekaman survei simpan snapshot | P1 |
| W6-T22 | Laporan | Laporan akademik merangkum label Panca Cinta/DPL | Hasil sesuai versi referensi dan filter akademik | P1 |
| W6-T23 | Regression | Bank Soal/asesmen lama tanpa mapping baru dibuka | Data lama tetap terbaca; tidak ada mapping palsu yang dibuat | P0 |
| W6-T24 | MySQL | Jalankan seluruh migration/test pada MySQL 8+ InnoDB disposable | FK/composite FK, restrict, transaksi, dan concurrency lulus | P0 |
| W6-T25 | Idempotensi | Request tambah/import yang sama dikirim ulang | Tidak menggandakan master, mapping, atau histori | P1 |
| W6-T26 | Versi | Referensi resmi versi baru diimpor | Versi lama tidak ditimpa; effective date dan relasi historis terjaga | P0 |

## C. Matriks penerimaan W6

| Gate | Kriteria | Bukti minimum |
|---|---|---|
| W6-G01 | Sumber resmi terverifikasi | Dokumen sumber, nomor/versi, tanggal, hash arsip bila tersedia |
| W6-G02 | Master mapel lengkap | Screenshot UI + API response + DB rows + audit log |
| W6-G03 | CP/TP/ATP valid | Feature tests untuk relasi valid dan invalid |
| W6-G04 | Panca Cinta dan DPL resmi | Seed/import manifest dari sumber resmi dan exact-string assertions |
| W6-G05 | Integrasi Guru/Bank Soal | E2E dropdown berantai, save question, reload snapshot |
| W6-G06 | Non-destructive lifecycle | Uji deactivate/obsolete, hard-delete denial, dan referential history |
| W6-G07 | Tenant security | Negative tests lintas tenant dan authorization |
| W6-G08 | MySQL integrity | CI integration run pada MySQL 8+ InnoDB disposable |
| W6-G09 | Survei dan laporan | Feature/integration tests terhadap kontrak akademik |
| W6-G10 | Tidak ada asumsi | Review data import, manifest sumber, dan audit sampel semua kode/referensi |

## D. Ketentuan hasil tes

- Status PASS hanya diberikan untuk test yang benar-benar dijalankan dan output/log tersimpan.
- Test unit atau SQLite tidak menggantikan integration test MySQL 8+ InnoDB.
- Jangan menguji migration pada database produksi.
- Jangan menambahkan CP/TP/kode RDM placeholder ke seed production.
- Tidak ada merge/deployment tanpa persetujuan terpisah.
- Matriks ini menjadi acceptance criteria W6 dan kontrak untuk integrasi Portal Guru/Siswa berikutnya.
