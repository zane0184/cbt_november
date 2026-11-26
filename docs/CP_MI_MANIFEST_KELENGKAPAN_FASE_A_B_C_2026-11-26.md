# Manifest Audit Kelengkapan CP MI Fase A, B, C — 26 November 2026

Repository: `zane0184/cbt_november`
Branch dasar: `integration/zip-vps-php83-20261126`
HEAD dasar: `7dc3c25ae883ee2383d820873d4b4422f2f5797e`

## AUDIT

### 1. Ruang lingkup yang wajib dipenuhi

- Fase A: kelas I–II MI.
- Fase B: kelas III–IV MI.
- Fase C: kelas V–VI MI.
- CP harus diinventarisasi per mata pelajaran, fase, elemen/subelemen, dan locator dokumen.
- TP hanya disusun setelah CP induk yang relevan tersedia dan provenance-nya lolos verifikasi.
- ATP disusun dari TP dan harus mempertahankan urutan, versi, dan histori.

### 2. Struktur mata pelajaran pada bootstrap seeder

Sumber: `backend-laravel/database/seeders/CurriculumRegistryBootstrapSeeder.php`.

| Fase | Kelas | Mata pelajaran/node yang dibuat bootstrap |
|---|---|---|
| A | 1–2 | Al-Qur’an Hadis; Akidah Akhlak; Fikih; Bahasa Arab; Pendidikan Pancasila; Bahasa Indonesia; Matematika; PJOK; Seni dan Budaya; Muatan Lokal |
| B | 3–4 | Al-Qur’an Hadis; Akidah Akhlak; Fikih; SKI; Bahasa Arab; Pendidikan Pancasila; Bahasa Indonesia; Matematika; IPAS; PJOK; Seni dan Budaya; Bahasa Inggris (transisi); Muatan Lokal |
| C | 5–6 | Al-Qur’an Hadis; Akidah Akhlak; Fikih; SKI; Bahasa Arab; Pendidikan Pancasila; Bahasa Indonesia; Matematika; IPAS; PJOK; Seni dan Budaya; Bahasa Inggris (transisi); Koding dan AI (pilihan); Muatan Lokal |

Catatan:
- Bootstrap merupakan registry struktur, bukan bukti semua node mempunyai teks CP.
- Seni dan Budaya memiliki pilihan Seni Musik, Seni Rupa, Seni Teater, dan Seni Tari.
- Muatan Lokal merupakan konfigurasi satuan/daerah; jangan mengisi CP nasional generik.
- Koding dan AI ditandai pilihan dalam bootstrap.
- Bahasa Inggris ditandai transisi pada bootstrap; status penerapan perlu mengikuti ketentuan kurikulum yang berlaku.
- Keberadaan node pada registry tidak otomatis berarti CP resmi tersedia untuk fase tersebut.

### 3. Sumber regulasi yang telah dibuka

| Dokumen | Cakupan yang dapat dibuktikan | Status audit |
|---|---|---|
| BSKAP 046/H/KR/2025 | CP umum SD/MI; Lampiran II; Fase A/B/C | Dokumen resmi ditemukan pada domain Kemendikdasmen; byte PDF lokal dan SHA-256 belum berhasil diarsipkan |
| BKPDM 020 Tahun 2026 | Mengubah CP Pendidikan Agama dan Budi Pekerti pada Lampiran II/V BSKAP 046/2025; ditetapkan 11 Juni 2026 | Naskah PDF terbaca dari salinan pihak ketiga; siaran pers resmi Kemendikdasmen menegaskan perubahan terbatas pada mapel agama dan budi pekerti |
| Kepdirjen Pendis 9941 Tahun 2025 | CP PAI dan Bahasa Arab pada RA/MI/MTs/MA/MAK; bagian MI meliputi Al-Qur’an Hadis, Akidah Akhlak, Fikih, SKI, Bahasa Arab | Naskah PDF terbaca dari host situs madrasah, memuat pengantar Kemenag dan keputusan; byte asli/arsip resmi serta SHA-256 lokal belum berhasil diverifikasi |
| KMA 1503 Tahun 2025 | Pedoman implementasi kurikulum madrasah dan struktur fase | Dipakai untuk struktur implementasi, bukan sumber teks CP umum per mapel |

Tautan rujukan yang diperiksa:
- BSKAP 046/2025: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
- BKPDM 020/2026 (naskah terbaca): https://cdn.schoolpro.id/documents/1782087907907-48baee9c9a6c43c280955078b534442c.pdf
- Siaran pers resmi 23 Juni 2026: https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti
- Penegasan bersama BKPDM–Kemenag 29 Juni 2026: https://bkpdm.kemendikdasmen.go.id/publikasi/bkpdm-dan-kementerian-agama-tegaskan-perubahan-capaian-pembelajaran-hanya-berlaku-untuk-mata-pelajaran-pendidikan-agama-dan-budi-pekerti
- Kepdirjen Pendis 9941/2025 (naskah terbaca): https://min2sbb.sch.id/wp-content/uploads/2025/12/SK-Dirjen-Pendis-9941-2025-CP-PAI-dan-Bahasa-Arab.pdf

### 4. Temuan cakupan PAI madrasah

Kepdirjen Pendis 9941/2025 memuat CP MI untuk lima mata pelajaran terpisah:
- Al-Qur’an Hadis: Fase A, B, C.
- Akidah Akhlak: Fase A, B, C.
- Fikih: Fase A, B, C.
- SKI: bagian MI yang diperiksa memulai CP dari Fase B dan memuat Fase C; jangan membuat SKI Fase A tanpa sumber yang menyatakannya.
- Bahasa Arab: Fase A, B, C.

BKPDM 020/2026 mengubah CP Pendidikan Agama dan Budi Pekerti pada SD/MI dan jenjang lain. Naskahnya menyebut koordinasi dengan Kementerian Agama; siaran pers bersama 29 Juni 2026 juga mengonfirmasi kerja sama BKPDM–Kemenag. Namun bukti yang tersedia belum menetapkan secara eksplisit hubungan operasional/precedence antara CP PAI terpadu BKPDM 020/2026 dan lima mata pelajaran PAI madrasah pada Kepdirjen Pendis 9941/2025.

**Keputusan fail-closed:** jangan mengimpor atau menghapus salah satu struktur PAI tersebut sampai hubungan regulasi untuk MI ditetapkan dari dokumen/penjelasan resmi yang dapat ditelusuri. Simpan provenance masing-masing sumber secara terpisah.

### 5. Matriks status data CP per fase

Status saat checkpoint ini:

| Kelompok | Fase A | Fase B | Fase C |
|---|---|---|---|
| PAI khas MI (9941/2025) | Teks lengkap belum diekstrak; sumber belum checksum lokal | Teks lengkap belum diekstrak; sumber belum checksum lokal | Teks lengkap belum diekstrak; sumber belum checksum lokal |
| Bahasa Arab MI (9941/2025) | Teks lengkap belum diekstrak; sumber belum checksum lokal | Teks lengkap belum diekstrak; sumber belum checksum lokal | Teks lengkap belum diekstrak; sumber belum checksum lokal |
| Pendidikan Pancasila (046/2025) | Belum diekstrak | Belum diekstrak | Belum diekstrak |
| Bahasa Indonesia (046/2025) | Belum diimpor/di-hash | Belum diimpor/di-hash | Importer parsial 4 elemen pernah dibuat; **tidak boleh dianggap dataset lengkap atau dijalankan sebagai solusi akhir** |
| Matematika (046/2025) | Belum diekstrak | Belum diekstrak | Belum diekstrak |
| IPAS (046/2025) | Tidak tercantum sebagai node Fase A di bootstrap | Belum diekstrak | Belum diekstrak |
| PJOK (046/2025) | Belum diekstrak | Belum diekstrak | Belum diekstrak |
| Seni dan Budaya (046/2025) | Belum diekstrak; pilihan seni perlu dipetakan sesuai dokumen | Belum diekstrak; pilihan seni perlu dipetakan sesuai dokumen | Belum diekstrak; pilihan seni perlu dipetakan sesuai dokumen |
| Bahasa Inggris (046/2025) | Tidak tercantum sebagai node Fase A di bootstrap | Belum diekstrak; status transisi harus dihormati | Belum diekstrak; status transisi harus dihormati |
| Koding dan AI | Tidak tercantum sebagai node Fase A di bootstrap | Tidak tercantum sebagai node Fase B di bootstrap | Node pilihan; sumber CP spesifik dan kelayakan fase harus diverifikasi sebelum impor |
| Muatan Lokal | Sumber lokal belum tersedia | Sumber lokal belum tersedia | Sumber lokal belum tersedia |

Matriks ini adalah status audit, bukan klaim CP sudah lengkap.

## DESAIN — kontrak kelengkapan

1. Satu manifest CP harus memuat seluruh fase A/B/C dan semua mapel yang berlaku menurut struktur serta dokumen resmi.
2. Setiap record minimal menyimpan: jenjang MI, fase, kelas, subject node, elemen, subelemen bila ada, teks CP persis, kode stabil, urutan, dokumen/versi, halaman, locator, tanggal berlaku, source ID, dan checksum.
3. Teks CP tidak boleh diparafrase, disintesis, atau disalin dari ringkasan sekunder.
4. Bahasa Arab wajib mempertahankan huruf, harakat, urutan RTL, dan struktur kalimat.
5. Jangan menganggap semua mapel hadir di semua fase. Cakupan fase harus mengikuti dokumen primer.
6. CP pilihan/transisi/lokal harus memiliki status dan aturan pemakaian eksplisit; tidak boleh disamakan dengan mapel wajib.
7. TP dibuat oleh Guru dengan relasi ke CP yang lolos gate; ATP dirangkai dari TP, berversi, dan mempertahankan histori.
8. Jangan melonggarkan syarat `ARTIFACT_CHECKSUM_VERIFIED` atau pencocokan SHA-256 pada service Guru.

## IMPLEMENTASI

- Tidak ada perubahan source aplikasi, migration, seeder, SQL, atau database pada checkpoint ini.
- Tidak ada akses atau perubahan VPS/database produksi.
- Unduhan langsung kedua PDF dari lingkungan kerja gagal; belum ada byte PDF lokal untuk hash.
- Importer Bahasa Indonesia Fase C yang ada tetap parsial dan tidak layak dijadikan paket lengkap.
- Dokumen ini merupakan manifest audit/checkpoint baru. Tidak menimpa file lama.

## PENGUJIAN

Belum dijalankan:
- ekstraksi seluruh teks CP per mapel/fase;
- pemeriksaan silang teks dan halaman;
- hitung SHA-256 dari artefak lokal;
- dry-run importer di MySQL 8 disposable;
- idempotensi, unique key, FK, tenant isolation, checksum match/mismatch;
- uji dropdown CP untuk semua fase/subject assignment.

## LANGKAH LANJUT

1. Dapatkan artefak PDF resmi/identik yang dapat diarsipkan untuk BSKAP 046/2025, BKPDM 020/2026, dan Kepdirjen Pendis 9941/2025.
2. Verifikasi bytes, ukuran, metadata, keutuhan PDF, dan SHA-256; simpan arsip read-only.
3. Ekstrak semua CP MI dari Fase A, B, dan C; buat hitungan per subject/phase dan review halaman demi halaman.
4. Selesaikan keputusan regulasi PAI 020/2026 versus 9941/2025 sebelum import PAI.
5. Buat importer lengkap, granular, idempoten dan fail-closed hanya setelah manifest konten disetujui.
6. Uji hanya pada MySQL 8 disposable; tidak ada eksekusi pada database produksi tanpa persetujuan eksplisit.
