# CHECKPOINT HANDOVER AGENT BARU — CBT MADRASAH / CP–TP–ATP MI
Tanggal checkpoint: 26 November 2026
Status: CP IMPORT BLOCKED — LANJUTKAN AUDIT/EKSTRAKSI, JANGAN MULAI DARI NOL

## 0. TUJUAN DAN RUANG LINGKUP AKTIF
Fokus pekerjaan saat handover ini hanya:
1. Capaian Pembelajaran (CP) MI untuk seluruh mata pelajaran yang memang berlaku, fase A/B/C sesuai sumber dan registry.
2. Tujuan Pembelajaran (TP) yang disusun Guru berdasarkan CP terverifikasi.
3. Alur Tujuan Pembelajaran (ATP) yang disusun dari TP dengan versi dan histori.

Jangan melebar ke UI/UX, Proktor, Web Siswa, modul admin lain, atau backlog non-CP. Jangan membuat CP sintetis, ringkasan yang diperlakukan sebagai CP resmi, atau memaksa mapel/fase yang tidak ada pada sumber.

## 1. IDENTITAS SOURCE GITHUB
- Repository: https://github.com/zane0184/cbt_november
- Branch kerja CP: `integration/zip-vps-php83-20261126`
- HEAD terakhir yang diverifikasi pada sesi ini: `caf875ab4eed1442c6b9be28ccec67bd9e4923ba`
- Commit: https://github.com/zane0184/cbt_november/commit/caf875ab4eed1442c6b9be28ccec67bd9e4923ba
- Branch alternatif terkait pekerjaan admin/import: `work/admin-import-curriculum-fixes-20261126`. Jangan berpindah atau menggabungkan branch tanpa audit diff/PR. Panduan 26 Nov mencatat PR #34 masih OPEN/DRAFT dan belum merged.
- Branch CP utama tetap branch integration di atas sampai pemeriksaan HEAD berikutnya membuktikan ada perubahan baru.

Dokumen GitHub terakhir:
1. Manifest kelengkapan: https://github.com/zane0184/cbt_november/blob/integration/zip-vps-php83-20261126/docs/CP_MI_MANIFEST_KELENGKAPAN_FASE_A_B_C_2026-11-26.md
2. Ekstraksi PABP BKPDM 020/2026: https://github.com/zane0184/cbt_november/blob/integration/zip-vps-php83-20261126/docs/CP_PABP_SD_MI_EKSTRAKSI_BKPDM_020_2026_2026-11-26.md
3. Ekstraksi Pendis 9941/2025: https://github.com/zane0184/cbt_november/blob/integration/zip-vps-php83-20261126/docs/CP_MI_EKSTRAKSI_LANJUTAN_PENDIS_9941_2026-11-26.md

## 2. AUDIT — KONDISI SOURCE APLIKASI
Paket kandidat Web yang diperiksa:
- `/mnt/data/CBT_MADRASAH_WEB_DEPLOYMENT_82bc43bc7953.zip`
- SHA-256: `2f0c355acad6c3bf79583d383d34cf6e50f9900a06edad2bcbc2cd6aaf4660e0`
- Paket kedua: `/mnt/data/CBT_MADRASAH_WEB_DEPLOYMENT_e7b3c329c502.zip`
- SHA-256: `5ed2382767c9fb40d77259a1a1dde7900e2c60b40d1aadcc31b828ced38542b5`
- Keduanya berisi 9.996 entri ZIP. Jangan menganggap paket ini bukti kondisi VPS/database produksi.

Temuan source terkonfirmasi dari laporan audit dan inspeksi paket:
- `CurriculumRegistryBootstrapSeeder.php` membentuk registry kurikulum `KMA-1503-2025`, fase A/B/C, kelas MI 1–6, serta node mata pelajaran; seeder registry tidak mengisi baris CP.
- Fase A: Al-Qur’an Hadis, Akidah Akhlak, Fikih, Bahasa Arab, Pendidikan Pancasila, Bahasa Indonesia, Matematika, PJOK, Seni dan Budaya, Muatan Lokal.
- Fase B: daftar fase A ditambah SKI dan IPAS; Bahasa Inggris berstatus transisi; Seni dan Budaya memiliki opsi; Muatan Lokal configurable.
- Fase C: daftar fase B; Koding dan Kecerdasan Artifisial pilihan; Bahasa Inggris transisi; Seni dan Budaya memiliki opsi; Muatan Lokal configurable.
- Pertahankan variasi fase/status ini. Jangan meratakan mapel untuk semua fase.
- `multi_learning_outcomes` memakai UUID dan relasi struktur/versi/tenant serta provenance sumber.
- `TeacherCurriculumContextService::outcomeArtifactVerified()` hanya menerima CP resmi jika source berstatus `ARTIFACT_CHECKSUM_VERIFIED`, checksum source terisi, checksum CP terisi, dan keduanya sama.
- `CurriculumReferenceSourceSeeder.php` saat audit mendaftarkan Pendis 9941 sebagai PENDING; URL yang dipakai bukan artefak keputusan PDF. Catatan seeder meminta artefak keputusan/lampiran MI untuk diverifikasi.
- Tanpa baris CP terverifikasi, pilihan CP Guru tetap pending/tidak selectable. Ini perilaku fail-closed yang benar; jangan longgarkan gate.
- TP dibuat Guru dari CP. ATP dirangkai dari TP dan harus menjaga versi/histori.

## 3. AUDIT — SUMBER REGULASI DAN PROVENANCE
### BSKAP 046/H/KR/2025
- PDF resmi: https://kurikulum.kemendikdasmen.go.id/file/1753929861_manage_file.pdf
- Keputusan 16 Juli 2025; Lampiran II memuat CP SD/MI.
- Fase A kelas I–II, B kelas III–IV, C kelas V–VI.
- PDF berhasil dibaca melalui web, tetapi raw bytes belum berhasil diarsipkan di workspace; SHA-256 independen belum tersedia.

### BKPDM 020 Tahun 2026
- Salinan PDF yang dibaca: https://cdn.schoolpro.id/documents/1782087907907-48baee9c9a6c43c280955078b534442c.pdf
- Salinan pihak ketiga; belum ada verifikasi tanda tangan/bytes resmi.
- Lampiran I berisi CP PAIBP terpadu (elemen Al-Qur’an Hadis, Akidah, Akhlak, Fikih, Sejarah Peradaban Islam), fase A/B/C.
- Siaran pers resmi Kemendikdasmen menyatakan hanya CP Pendidikan Agama dan Budi Pekerti yang berubah; mapel lain tetap 046/2025:
  https://www.kemendikdasmen.go.id/siaran-pers/15636-capaian-pembelajaran-baru-telah-terbit-yang-berubah-hanya-mata-pelajaran-agama-dan-budi-pekerti
- Siaran pers bukan pengganti artefak keputusan.

### Kepdirjen Pendis 9941 Tahun 2025
- Library source: `Dirjen-pendis-2025-9941 Capaian Pembelajaran PAI dan Bahasa Arab pada Madrasah.pdf`
- File ID: `file_0000000011308211b521c0481040fc8e`
- Metadata: 107 halaman, 1.161.548 byte. Raw-byte materialization ditolak; SHA-256 independen dan validasi tanda tangan digital belum dilakukan.
- Salinan yang dapat dibaca: https://min2sbb.sch.id/wp-content/uploads/2025/12/SK-Dirjen-Pendis-9941-2025-CP-PAI-dan-Bahasa-Arab.pdf
- Mapel MI: Al-Qur’an Hadis, Akidah Akhlak, Fikih, SKI, Bahasa Arab.
- Bagian CP yang ditemukan: Al-Qur’an Hadis A/B/C; Akidah Akhlak A/B/C; Fikih A/B/C; SKI B/C (Fase A tidak ditemukan di bagian MI yang diperiksa); Bahasa Arab A/B/C.
- Locator tercetak: Al-Qur’an Hadis hlm. 11–13; Akidah Akhlak 42–46; Fikih 68–70 (C berlanjut); SKI 88–89 dan seterusnya; Bahasa Arab 97–99.
- Hasil yang ada baru ekstraksi substansi parsial, bukan transkripsi lengkap dan bukan verifikasi karakter per karakter. Bahasa Arab memerlukan pemeriksaan citra halaman untuk teks Arab, harakat, dan RTL.

### Rekonsiliasi PAI MI — BLOCKER
Jangan menyatukan lima mapel Pendis 9941/2025 dengan lima elemen PAIBP BKPDM 020/2026 secara otomatis. BKPDM 020 mengubah CP PAIBP SD/MI; Pendis 9941 mengatur mapel PAI khas madrasah. Perlu klarifikasi normatif resmi Kemenag tentang penerapan keduanya di MI. Sampai itu jelas, jangan tetapkan sumber aktif PAI pada importer.

### KMA 1503/2025
KMA adalah pedoman implementasi kurikulum madrasah, termasuk Pembelajaran Mendalam dan Kurikulum Berbasis Cinta/Panca Cinta. Bukan pengganti CP per mata pelajaran. Panca Cinta dan Pembelajaran Mendalam menjadi ekosistem pembelajaran/pendekatan, bukan menggantikan teks CP.

## 4. IMPLEMENTASI — APA YANG SUDAH / BELUM DIUBAH
Sudah:
- Tiga dokumen audit/ekstraksi CP tercatat di branch integration (tautan di bagian 1).
- Dokumen ekstraksi PABP BKPDM memuat 15 pasangan elemen-fase (5 elemen × A/B/C) dari salinan PDF yang diunggah; hash file unggahan tercatat di dokumen terkait. Itu membuktikan identitas byte unggahan, bukan autentisitas penerbit atau tanda tangan.
- Audit Pendis merekam temuan parsial dan gap ekstraksi.

Belum:
- Tidak ada importer final, seeder CP lengkap, SQL final, atau migration CP yang dibuat.
- Tidak ada source aplikasi yang diubah dalam rangka CP/TP/ATP pada checkpoint ini.
- Patch lama `CBT_PATCH_CP_BAHASA_INDONESIA_FASE_C.zip` tidak boleh dipasang sebagai solusi lengkap; hanya parsial dan tidak memenuhi seluruh mapel/fase/provenance.
- Tidak ada database produksi/VPS yang disentuh.

## 5. PENGUJIAN — BUKTI YANG ADA DAN TIDAK ADA
Dilakukan:
- Inspeksi statis source/seeder/migration/model/service pada paket kandidat.
- Pembacaan teks PDF/sumber melalui web dan Project Library extraction.
- Verifikasi GitHub commit HEAD `caf875ab...` dan fetch dokumen Pendis di branch.
- SHA-256 paket ZIP dihitung di sesi ini (nilai pada bagian 2).

Belum dilakukan:
- Raw-byte SHA-256 resmi untuk BSKAP 046, BKPDM 020, Pendis 9941, KMA 1503.
- Verifikasi sertifikat/tanda tangan BSrE/QR dokumen.
- Transkripsi lengkap CP umum semua mapel dan fase A/B/C.
- Transkripsi penuh keterampilan proses semua mapel Pendis dan pemeriksaan visual teks Arab.
- Klarifikasi resmi PAI MI antara BKPDM 020 dan Pendis 9941.
- Importer, MySQL 8/InnoDB disposable, idempotensi, tenant isolation, query/dropdown Guru.
- UAT browser maupun pengujian pada VPS.
Tidak ada klaim siap produksi.

## 6. LANGKAH AGENT BARU — URUTAN WAJIB
1. Baca checkpoint ini dan tiga dokumen GitHub di bagian 1 terlebih dahulu. Jangan mulai ulang atau mengulang patch.
2. Periksa ulang branch HEAD aktual. Pastikan tidak ada commit baru sejak `caf875ab...`; jika berubah, audit diff dan perbarui checkpoint.
3. Periksa seluruh sumber yang tersedia sebelum meminta unggahan baru: Project Files/Library, seluruh ZIP deployment yang tersedia, dokumen audit, backup, dan file patch lama. Workspace `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tidak ter-mount pada sesi checkpoint ini; jangan menyatakan workspace tidak ada secara global. Periksa ulang di sesi/agent berikutnya.
4. Lanjutkan ekstraksi penuh BSKAP 046 Lampiran II untuk semua mapel umum yang relevan bagi MI dan fase A/B/C. Pertahankan teks CP persis, elemen, pemahaman konsep, keterampilan proses, halaman tercetak dan nomor halaman PDF. Jangan gunakan ringkasan sekunder sebagai isi CP.
5. Lanjutkan ekstraksi Pendis 9941: semua keterampilan proses tiap mapel/fase, kelengkapan Fikih C dan SKI C, serta visual/RTL/harakat Bahasa Arab hlm. 97–99. Jangan mengisi SKI A.
6. Dapatkan raw bytes sumber resmi dan hitung SHA-256; validasi tanda tangan/QR bila tersedia. Jika belum bisa, status tetap PENDING dan CP tidak selectable.
7. Dapatkan klarifikasi normatif Kemenag untuk relasi BKPDM 020/2026 vs Pendis 9941/2025 sebelum memutuskan struktur PAI MI.
8. Audit registry mata pelajaran terhadap KMA 1503/2025 dan sumber mapel; jangan menambah CP mapel pilihan/transisi tanpa dasar.
9. Setelah sumber lengkap dan provenance sah: rancang importer idempoten, transaksional, tenant-aware, fail-closed. Backup source sebelum mengubah file existing.
10. Uji hanya di MySQL 8/InnoDB disposable: import idempoten, checksum mismatch, source pending, tenant mismatch, struktur fase/mapel tidak cocok, rollback transaksi, serta query dropdown Guru untuk kelas 1–6.
11. Baru siapkan patch targeted dan instruksi rollback. Tidak ada perubahan DB/VPS produksi tanpa rencana, backup tervalidasi, preflight, dan persetujuan eksplisit.

## 7. LARANGAN KERAS / KEPUTUSAN TETAP
- Jangan menurunkan gate `ARTIFACT_CHECKSUM_VERIFIED`.
- Jangan membuat CP sintetis atau mengubah ringkasan menjadi teks CP resmi.
- Jangan menyamaratakan daftar mapel antarfase.
- Jangan mengarang SKI Fase A.
- Jangan menyatukan PAI 9941 dan PAIBP BKPDM 020 tanpa klarifikasi.
- Jangan membuat TP/ATP seolah-olah CP sudah terverifikasi.
- Jangan menjalankan migration pada database produksi/VPS.
- Jangan menimpa source asli atau ZIP baseline.
- Jangan menjalankan Gradle lewat terminal; build Android tetap via Android Studio.
- Jangan mengklaim uji yang belum dijalankan.

## 8. STATUS SAAT HANDOVER
`CP DATASET: PARTIAL / NOT READY FOR IMPORT`
`PROVENANCE: BLOCKED`
`PAI REGULATORY PRECEDENCE: BLOCKED`
`IMPORTER: NOT IMPLEMENTED`
`MYSQL 8 DISPOSABLE TEST: NOT RUN`
`PRODUCTION/VPS: UNTOUCHED`

Agent baru harus melanjutkan dari langkah 4–7 bagian 6, bukan mengulang audit dasar yang sudah tercatat.
