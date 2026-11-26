# Checkpoint CP/TP — 26 November 2026

Repository: `zane0184/cbt_november`
Branch pencatatan: `audit/coordinator-continuation-20261126`
Commit laporan audit: `4dc0ea552c8875446a211748623052000efaac1f`
Laporan lengkap: [REPORT_CP_TP_AUDIT_2026-11-26.md](REPORT_CP_TP_AUDIT_2026-11-26.md)

## Mandat aktif
Hanya memperbaiki CP/TP/ATP pada Web Admin, Guru, Siswa dan integrasi backend. Android dan Web Super Admin ditunda sampai Juni 2027. Tidak mengulang patch TP/ATP provenance atau QuestionVersion snapshot yang sudah ada.

## Source yang diaudit
- Branch source terbaru: `work/rombel-docx-completion-20261126`
- Exact HEAD: `841a1dd90dbe0277026fade1511a39b5dc9216b6`
- Tree: 2.053 entri.
- Audit hanya dari GitHub; ZIP baseline, Library, Project Files, VPS dan DB produksi tidak dibuka.

## Posisi pekerjaan yang benar-benar terbukti

### Selesai pada source (tidak boleh dibuat ulang tanpa bukti regresi)
- Admin registry versi kurikulum, sync katalog mapel dan mapping mapel lokal.
- Struktur fase/kelas/mapel, Panca Cinta, struktur PM dan delapan dimensi pada bootstrap seeder.
- Guru TP/ATP authoring berbasis assignment, CP parent, provenance, KBC/PM mapping.
- Guard provenance CP pada create TP, create ATP dan publish.
- Mapping kurikulum pada QuestionVersion.
- Salinan konteks CP/TP/ATP/framework ke immutable ExamQuestionSnapshot.

### Belum selesai / blocker
1. Halaman `/admin/curriculum` masih memakai model/tabel legacy `LearningOutcome/LearningObjective`; tidak membaca canonical registry. Halaman itu secara eksplisit read-only legacy.
2. Belum ada row CP resmi pada registry `MultiLearningOutcome` yang berasal dari artefak resmi terverifikasi.
3. PDF asli BSKAP 046/H/KR/2025, Kepdirjen Pendis 9941/2025 beserta annex CP MI, dan naskah/annex BKPDM 020/2026 belum tersedia di source GitHub untuk verifikasi bytes/checksum. 9941 tetap PENDING.
4. Tanpa CP official checksum verified, Guru tidak dapat memilih CP dan membuat TP/ATP resmi; question mapping dan exam snapshot yang mewajibkan CP juga tertahan.
5. Issue #14/#15 masih OPEN; deskripsi awal #14 sebagian stale terhadap source terbaru, perlu rekonsiliasi status berdasar test exact SHA.
6. Belum ada acceptance end-to-end Admin registry → Guru TP/ATP → QuestionVersion → Exam snapshot → payload Siswa pada exact HEAD 841a.

## Pekerjaan berikutnya
1. Sediakan artefak sumber CP asli melalui GitHub (bukan ringkasan/salinan teks); verifikasi penerbit, nomor, versi, halaman/annex, ukuran dan SHA-256.
2. Implementasikan dataset CP per fase/mapel/elemen dari dokumen resmi; jangan gunakan synthetic test fixture sebagai seed produksi.
3. Ganti tampilan legacy Admin agar membaca canonical CP registry dan TP/ATP Guru secara read-only; pertahankan larangan Admin membuat TP/ATP.
4. Jalankan acceptance MySQL 8/InnoDB pada exact commit: no-data, provenance fail, CP valid, TP, ATP, question mapping, immutable snapshot, Siswa payload, dan histori.
5. Rekonsiliasi Issue #14/#15 dan update checkpoint dengan bukti CI exact SHA.

## Catatan keselamatan
- Tidak ada source aplikasi, migration, seeder, database, VPS atau deployment yang diubah pada tahap audit ini.
- Tidak ada backup source dibuat karena tidak ada source file yang diedit.
- Test baru tidak dijalankan pada audit ini. Run #182 pada SHA 185f6d... adalah bukti historis untuk gate provenance/non-CP, bukan bukti CP official positive path atau CI pada HEAD 841a...
- Jangan mengubah status CP PENDING atau mengisi CP sintetis untuk membuat UI tampak penuh.

**Status checkpoint: AUDIT SELESAI — IMPLEMENTASI CP OFFICIAL BLOCKED OLEH ARTEFAK SUMBER — ADMIN LEGACY VIEW GAP TERIDENTIFIKASI — BELUM END-TO-END.**


## Addendum — penelusuran sumber publik (26 November 2026)
Atas arahan koordinator, penelusuran tidak berhenti pada file yang tidak ada di repo. Manifest sumber publik dibuat pada `docs/CP_TP_SOURCE_RECOVERY_MANIFEST_2026-11-26.md` (commit `8c64f2b784210d1de23331c2ce574fe3fcf9b1e5`).

Temuan tambahan:
- PDF salinan BSKAP 046/H/KR/2025 Lampiran II SD/MI ditemukan pada host publik; masih perlu diunduh, hash, dan cross-check.
- Siaran pers resmi Kemendikdasmen 23 Juni 2026 mengonfirmasi BKPDM 020/2026 hanya mengubah CP Agama dan Budi Pekerti. Naskah keputusan dan lampirannya tetap harus diperoleh dan diverifikasi.
- Salinan isi Kepdirjen Pendis 9941/2025 dan folder unduh PDF/DOCX lampiran ditemukan melalui sumber pihak ketiga. Status tetap `UNVERIFIED_COPY` sampai tanda tangan, nomor, lampiran MI, bytes, dan hash dicocokkan dengan sumber resmi.
- Belum ada dataset CP yang diimport, tidak ada file aplikasi yang diubah, tidak ada migration/DB/production action, dan belum ada test baru.
- Langkah berikutnya adalah akuisisi artefak PDF, verifikasi hash/provenance, ekstraksi teks persis beserta nomor halaman, lalu importer idempoten + acceptance test disposable. Jangan mengisi registry dengan data sintetis.


## Addendum 2 — hasil verifikasi sumber primer/sekunder (26 November 2026)
Manifest diperbarui pada commit `b85eea25d0d478488f28ffa29a182f17df4bc21c`.

- BSKAP 046/H/KR/2025: PDF berhasil dibuka pada domain resmi Kemendikdasmen. Identitas keputusan, tanggal 16 Juli 2025, Lampiran II SD/MI, dan fase A/B/C terkonfirmasi. Ekstraksi web tersedia, tetapi byte PDF belum berhasil diunduh ke workspace/repository; SHA-256 file asli belum ada.
- BKPDM 020/2026: pengumuman resmi Kemendikdasmen dan salinan PDF yang memuat naskah keputusan ditemukan. Salinan menunjukkan tanggal 11 Juni 2026 dan perubahan pada lampiran CP Agama/Budi Pekerti; host PDF bukan domain penerbit, sehingga status hanya dikorroborasi, belum validasi TTE/bytes primer.
- SK Dirjen Pendis 9941/2025: halaman Kemenag subdomain mengindeks CP PAI/Bahasa Arab; salinan PDF/teks dari pihak ketiga juga ditemukan. Folder Drive yang ditautkan Ayo Madrasah ditemukan, tetapi PDF asli dan validasi TTE belum berhasil diambil. Status tetap `UNVERIFIED_COPY_PENDING_OFFICIAL_BYTES`.
- Tidak ada CP buatan atau sintetis yang dimasukkan. Belum ada import, source aplikasi, migration, database, atau deployment yang diubah. Belum ada test baru.
- Pekerjaan berikutnya: memperoleh bytes PDF 9941 dan 020 dari sumber penerbit/arsip, memvalidasi tanda tangan/hash, mengekstrak CP MI per halaman, baru menyiapkan dataset dan importer.

**Jangan menyebut seluruh dokumen sudah didapat atau data CP siap diimpor.**


## Addendum 3 — dua berkas sumber diterima dan diperiksa (26 November 2026)

Pengguna melampirkan dua PDF. Keduanya telah diperiksa dari byte file yang termount di workspace; SHA-256 dan peta halaman dicatat pada `docs/CP_TP_SOURCE_RECOVERY_MANIFEST_2026-11-26.md`, commit `3a684830b3f09429cfb717fa1ad99ae423c6300b`.

- **SK Dirjen Pendis 9941/2025**: 1,161,548 byte, 107 halaman, SHA-256 `63ecce28af6c02b9fefde85f9594e7432b2575a69a263a46882ace6e87ffb757`. Surat pengantar Ditjen Pendis B-606 tertanggal 1 Desember 2025, keputusan 9941 tertanggal 28 November 2025, diktum dan lampiran cocok secara isi/identitas. Peta CP MI: Al-Qur'an Hadis PDF 10–19; Akidah Akhlak 39–54; Fikih 68–85; SKI 88–95; Bahasa Arab 96–103. PDF tidak memiliki signature object menurut `pdfsig`, meskipun surat pengantar menyebut TTE BSrE. Status belum tervalidasi kriptografis.
- **Salinan Kepka BKPDM 020/2026**: 8,510,107 byte, 79 halaman, SHA-256 `598278746b0f62333fdcab520d5dd4c1a9650c94bd3906acff4254100976552b`. Halaman 1–3 memuat nomor, judul, tanggal 11 Juni 2026, pejabat dan cap/tanda tangan hasil scan. Lampiran I PDF 4–45; perubahan CP PAIBP umum SD/MI ada pada PDF 4–9. Lampiran II PDF 46–79 adalah CP Pendidikan Khusus. Teks PDF hasil scan tidak terbaca oleh pdftotext; pemeriksaan dilakukan dengan gambar halaman. Tidak ada signature object digital.
- Pembedaan penting: 9941 adalah acuan khusus PAI dan Bahasa Arab pada madrasah (lima mapel MI), sedangkan Kepka 020 mengubah CP PAIBP dalam struktur BSKAP 046/2025. Jangan menggabungkan atau mengganti CP PAI MI madrasah dengan struktur PAIBP umum SD/MI tanpa dasar harmonisasi resmi.
- Belum ada import data, perubahan source, migration, akses DB, deployment, atau pengujian aplikasi. Belum ada transkripsi CP penuh. Tidak mengklaim validasi tanda tangan atau keaslian digital final.

**Tahap saat ini:** AUDIT SUMBER DOKUMEN selesai untuk penerimaan, identitas, hash dan indeks halaman awal. Berikutnya: transkripsi CP MI per fase/elemen dari 9941 dengan verifikasi visual, menyimpan provenance; setelah itu desain importer dry-run dan uji terisolasi. Jangan mulai seed produksi.


- Indeks halaman sumber CP MI dibuat di `docs/CP_9941_MI_SOURCE_INDEX.md` (commit `664e12cf1ab7ae196f2cc87c61c4371f2543a5ef`). Indeks mencatat halaman PDF per mapel, identitas dokumen, hash sumber, serta aturan provenance dan ekstraksi.


## Addendum 4 — transkripsi staging CP MI (26 November 2026)

- File staging dibuat: `docs/CP_MI_9941_STAGING_DATASET.md`, commit `16ed23375320c0b791243dbb32933a41f51e1e40`; koreksi page provenance terakhir commit `52f5d7a6e19d9b88714d1aea0d793651bc84dcd5`.
- Lima mapel sudah diindeks dan sebagian teks CP Pemahaman Konsep ditranskripsikan ke tabel staging: Al-Qur'an Hadis (fase A/B/C), Akidah Akhlak (A/B/C), Fikih (A/B/C), SKI (B/C; fase A tidak ditemukan), Bahasa Arab (A/B/C).
- Elemen Keterampilan Proses/Keterampilan Bahasa tidak dihapus; tabel staging saat ini masih menandai ekstraksi rinci elemen proses sebagai pekerjaan QA, jadi dataset belum lengkap.
- Render halaman sumber telah dibuat dan diperiksa secara visual untuk sampel, termasuk PDF halaman 13 (Al-Qur'an Hadis fase A) dan PDF halaman 99 (Bahasa Arab fase A). Halaman tersebut cocok dengan transkripsi inti. Pemeriksaan visual semua halaman belum selesai.
- Koreksi halaman PDF final: Al-Qur'an Hadis 13–15; Akidah Akhlak 44–47; Fikih 70–73; SKI 90–92; Bahasa Arab 99–101. Rentang indeks awal yang terlalu luas/bergeser telah diperbaiki di indeks dan tabel staging.
- File transkripsi kerja lengkap ekstraksi yang belum dinormalisasi tersedia di workspace sesi saat ini sebagai `/mnt/data/cp_work/CP_MI_9941_TRANSKRIPSI_KERJA.md`; belum diarsipkan sebagai file biner GitHub. Data staging GitHub adalah dokumen utama yang dapat ditinjau.
- Tidak ada source aplikasi, schema/migration, database, atau deployment yang diubah. Tidak ada test aplikasi dijalankan.
- **Status:** transkripsi CP inti sebagian selesai; belum boleh dipakai untuk import produksi. Lanjutkan dengan QA visual semua halaman, ekstraksi penuh elemen proses, koreksi istilah Arab dari render, lalu dataset terstruktur dan importer dry-run.


- Laporan audit diperbarui dengan hasil QA visual sampel, batas status signature, temuan SKI fase A yang tidak tercantum, dan larangan import produksi sebelum QA penuh; commit `ed27b9696666d2b3eff9962557d82442750bdf82`.


## Addendum 5 — transkripsi elemen proses per fase (26 November 2026)

- Dokumen `docs/CP_MI_9941_KETERAMPILAN_PROSES_TRANSKRIPSI.md` dibuat, commit `855fa5eea3d640b4ebd12a021d3a58db749a20a7`.
- Isi mencakup Al-Qur'an Hadis fase A/B/C, Akidah Akhlak A/B/C, Fikih A/B/C, SKI B/C, dan Bahasa Arab A/B/C; memisahkan tiap proses sesuai struktur keputusan.
- Untuk Fikih fase B, pengulangan frasa pada dokumen sumber dicatat sebagai karakteristik transkripsi dan tidak diperbaiki tanpa catatan.
- Untuk Bahasa Arab, teks CP umum diterjemahkan/transkripsi dalam bahasa Indonesia, tetapi contoh pola kalimat Arab masih memerlukan proofread manual dari render halaman PDF. Belum ditandai final.
- Dataset staging diberi addendum yang menautkan dokumen transkripsi proses, commit `b28b79869e3dc31155f0f267256adaf32fd91fa5`.
- Source aplikasi, schema, migration, database, deployment tidak diubah. Tidak ada acceptance test dijalankan.
- Pekerjaan berikutnya: proofread seluruh baris Pemahaman Konsep terhadap render halaman, transkripsi manual pola kalimat Arab, lalu bentuk JSON/CSV dengan provenance per record dan buat importer dry-run.


## Addendum 6 — kontrak staging JSON (26 November 2026)
- JSON Schema untuk record CP MI dibuat di `docs/schemas/cp-mi-staging.schema.json`, commit `0837666512d40c2e5b52c95d4e577dd21d475847`.
- Schema mewajibkan source hash, halaman PDF, mapel, fase, rentang kelas, elemen, deskripsi sumber, status keaslian, dan status QA.
- Validasi schema terhadap seluruh record belum dijalankan karena dataset masih berupa tabel Markdown dan transkripsi Arab/pemeriksaan visual belum final. Belum ada importer.
- Kontrak import desain tersedia di `docs/CP_MI_IMPORT_CONTRACT_DESIGN.md`.
