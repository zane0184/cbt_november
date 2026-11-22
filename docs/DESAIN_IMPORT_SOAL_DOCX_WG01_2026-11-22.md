# DESAIN IMPORT SOAL MICROSOFT WORD (DOCX) — WG-01
Tanggal: 22 November 2026
Status: DESAIN AWAL — menunggu kontrak Agent 2
Repository: zane0184/cbt_november
Branch koordinasi Agent 3: work/web-guru-foundation-20261122

## 1. Tujuan dan batas

Guru dapat memasukkan soal melalui dua sumber:
1. Excel XLSX terstruktur menggunakan template CBT-QUESTION-1.
2. Microsoft Word DOCX yang disusun Guru dengan format dokumen yang dapat bervariasi.

DOCX dan XLSX adalah dua adapter ekstraksi, bukan dua sistem penyimpanan. Setelah ekstraksi, keduanya wajib menjadi draft dengan kontrak netral-format yang sama dan menjalani lifecycle backend yang sama.

Format .doc lama tidak masuk tahap awal. DOCX dan DOC bukan format internal yang setara: DOCX merupakan paket OOXML/ZIP, sedangkan DOC lama memakai format binary compound document. Dukungan DOC lama memerlukan audit parser dan risiko tersendiri.

## 2. Arsitektur yang diusulkan

### Jalur ingest

- Upload Adapter XLSX → preflight file → parser XLSX → normalized Import Draft.
- Upload Adapter DOCX → preflight file → pemilihan document profile → parser DOCX → normalized Import Draft.
- Kedua jalur meneruskan draft ke satu Import Batch / staging / validation / audit service milik Agent 2.
- Guru melakukan preview dan koreksi pada draft.
- Server memvalidasi ulang seluruh data dan otorisasi saat konfirmasi.
- Data tersimpan melalui kontrak Question/QuestionVersion/QuestionOption/QuestionMultiCurriculumMapping/MediaAsset yang ditetapkan Agent 2, dengan status DRAFT.
- Tidak ada auto-publish.

### Kontrak hasil ekstraksi netral-format

Usulan bidang konseptual (bukan schema final):
- import_batch_id dan source_file metadata/checksum.
- parser_type dan parser_profile/version.
- source_map untuk setiap blok (paragraph index, numbering metadata, table/row/cell, image relationship, equation block).
- question candidate: external id, displayed number, stem content blocks, question type candidate, points, explanation/rubric.
- option candidates: key, content blocks, order, correctness candidate.
- answer_key_candidate dengan explicit_marker, mapping status dan review status.
- media/table/equation blocks dengan extraction status.
- warnings/errors per block.
- review_status: READY_FOR_REVIEW / NEEDS_REVIEW / INVALID.
- confidence hanya untuk kualitas ekstraksi struktur; tidak boleh digunakan untuk menebak kunci jawaban.

Nama DTO, endpoint, status final, persistence, batch model, idempotency dan audit event harus diputuskan Agent 2. Agent 3 tidak membuat kontrak tandingan.

## 3. Document profile / format dokumen

Karena dokumen Guru tidak seragam, UI harus menyediakan:
- pilihan profil dokumen yang disetujui backend; atau
- auto-detect terbatas yang hanya menyarankan profil, bukan memaksakan hasil.

Contoh kelompok profil yang perlu diuji, bukan asumsi format universal:
- nomor soal berupa teks manual dan pilihan A-D dalam paragraf;
- nomor soal menggunakan Word automatic numbering;
- pertanyaan dan opsi berada dalam tabel;
- kunci jawaban/pembahasan berada di bagian terpisah dengan referensi nomor;
- dokumen tanpa kunci jawaban;
- dokumen campuran teks, tabel, gambar, dan persamaan.

Profil harus versioned. Guru dapat memilih profil sebelum ekstraksi atau menggantinya saat preview bila hasil tidak cocok. Format tidak dikenal harus menghasilkan NEEDS_REVIEW, bukan auto-mapping paksa.

## 4. Ekstraksi struktur OOXML

DOCX adalah paket ZIP yang berisi XML, relationship parts dan media. Parser perlu membaca struktur dokumen secara berurutan, tidak hanya mengekstrak teks polos.

### Paragraf dan penomoran
- Ekstrak teks run/paragraf dan urutan blok.
- Baca metadata numbering Word (numPr/numbering definitions) agar automatic numbering tidak hilang.
- Kenali nomor manual hanya melalui pola yang dinyatakan oleh profil.
- Pertahankan nomor asli, termasuk nomor tidak berurutan. Jangan mengurutkan ulang atau mengarang nomor yang hilang.
- Paragraf panjang, style, bold/italic/underline dan line breaks perlu dipertahankan sejauh model konten mendukungnya.

### Pilihan jawaban
- Kenali A-D hanya sesuai document profile dan struktur yang dapat dipastikan.
- Opsi yang tidak lengkap, key duplikat, urutan ambigu, atau label yang tidak dikenali harus NEEDS_REVIEW.
- Opsi E tidak boleh diterima pada kontrak source saat ini tanpa keputusan perubahan engine tersendiri.

### Tabel
- Ekstrak tabel sebagai struktur baris/kolom, bukan meratakan semua cell menjadi satu string.
- Simpan source_map untuk table index, row, cell dan urutan blok.
- Nested table atau merged cell yang tidak dapat dipetakan harus menghasilkan warning yang terlihat.

### Gambar
- Resolve image relationship ke media part internal yang valid.
- Simpan sebagai media candidate pada private staging; jangan langsung membuat MediaAsset final tanpa kontrak Agent 2.
- Inline dan floating/anchored drawing diuji terpisah.
- Bila gambar tidak dapat diambil, unsupported, atau relationship rusak, tampilkan placeholder/warning di preview. Dilarang menghilangkan gambar diam-diam.
- Embedded OLE/object bukan gambar biasa dan tidak boleh dieksekusi.

### Rumus / persamaan
- Persamaan Word dapat tersimpan sebagai Office Math Markup Language (OMML), bukan teks biasa.
- Pertahankan struktur persamaan sebagai block candidate atau tandai unsupported/NEEDS_REVIEW jika konversi tidak terbukti.
- Jangan mengubah persamaan menjadi teks yang tampak mirip tetapi mengubah makna.
- Rendering lintas Web dan Android tetap mengikuti kontrak equation renderer terpisah.

### Arab, harakat, campuran bahasa dan RTL
- Pertahankan Unicode code points dan urutan teks.
- Baca properti paragraph/run untuk bidi/RTL bila tersedia.
- Jangan membalik string Arab secara manual.
- Pertahankan metadata arah paragraf/run pada source block bila model konten mendukungnya.
- Uji Arabic/harakat dan mixed Arabic-Indonesian; jika layout/bidi tidak dapat dipertahankan, tampilkan warning dan minta Guru memeriksa preview.
- Jangan mengklaim RTL sempurna sebelum uji dokumen nyata.

## 5. Kunci jawaban — aturan non-negotiable

Kunci jawaban hanya menjadi candidate jika:
- marker eksplisit sesuai grammar document profile;
- nomor soal terhubung tepat ke satu soal;
- key cocok dengan opsi yang benar-benar tersedia;
- tidak ada konflik atau marker ganda.

Jika marker hilang, ambigu, tidak cocok, opsi tidak lengkap, atau ada lebih dari satu kemungkinan:
- answer_key_candidate = null;
- status NEEDS_REVIEW;
- Guru wajib memperbaiki sebelum konfirmasi.

Dilarang menebak jawaban dengan AI, pola posisi, frekuensi, pengetahuan materi, atau inferensi teks.

SHORT_ANSWER tetap BLOCKED sampai accepted answers terbukti tersimpan, masuk ExamSnapshot dan dipakai Scoring. ESSAY tetap memerlukan penilaian manual.

## 6. Keamanan file dan resource

Sebelum parsing:
- validasi extension, MIME dan signature/package structure;
- validasi ZIP central directory dan keberadaan part OOXML wajib;
- batasi ukuran upload, jumlah entry, ukuran per-entry, total expanded bytes dan rasio kompresi;
- tolak path traversal, absolute path, duplicate entry/path collision, nested archive dan malformed ZIP;
- tolak atau isolasi XML DTD/ENTITY dan external entity;
- blokir external relationships, linked templates dan target eksternal;
- jangan mengeksekusi macro, script, OLE, embedded object atau external resource;
- audit embedded package dan content type yang tidak diizinkan;
- batasi jumlah paragraf, tabel, baris/kolom, gambar, relasi dan kedalaman struktur;
- parser berjalan dengan batas CPU, memory dan timeout;
- simpan upload dan media sementara pada private storage dengan random key, bukan public path;
- bersihkan file sesuai retention policy Agent 2;
- log actor, tenant, file checksum, hasil preflight, parser profile, status dan alasan penolakan.

DOCX macro-free tidak berarti bebas embedded object atau external relationship. File yang gagal preflight tidak boleh diteruskan ke parser.

## 7. Preview dan koreksi Guru

Preview wajib menampilkan:
- nomor soal dan asal nomor (manual/automatic numbering);
- teks pertanyaan dalam blok yang dapat diedit;
- pilihan A-D dan urutannya;
- kunci jawaban candidate, marker sumber dan status verifikasi;
- pembahasan/rubrik dan bobot;
- tabel, gambar, persamaan, dan status ekstraksinya;
- sumber dokumen (paragraph/table/image block);
- warning/error dan bagian yang belum dikenali;
- jenis soal candidate dan alasan bila tidak dapat ditentukan.

Guru dapat:
- mengganti document profile;
- mengedit nomor, pertanyaan, opsi, pembahasan, bobot dan jenis soal;
- memperbaiki/menghapus candidate key atau memasukkan kunci secara manual;
- mengonfirmasi media/table/equation yang perlu penanganan manual;
- menghapus item yang bukan soal.

Setiap koreksi harus disimpan sebagai draft batch dan divalidasi ulang server-side. UI tidak boleh mengubah status menjadi READY hanya berdasarkan state browser.

## 8. Satu lifecycle untuk XLSX dan DOCX

- Satu batch service dan storage yang disetujui Agent 2.
- Satu checksum file per upload dan idempotency contract.
- Satu normalized draft contract.
- Satu preview/correction/revalidation/confirm/report flow.
- Satu duplicate detection policy dan transaksi per row.
- Satu audit log dan retention policy.
- Satu penyimpanan question domain.
- Adapter parser hanya bertanggung jawab mengubah file sumber menjadi draft candidate.

Tidak boleh ada import batch kedua khusus DOCX.

## 9. Matriks pengujian

1. Pilihan ganda sederhana.
2. Nomor manual dan Word automatic numbering.
3. Nomor tidak berurutan dan nomor soal hilang.
4. Paragraf panjang, style campuran dan format tidak konsisten.
5. Opsi A-D normal, tidak lengkap, key duplikat dan urutan berbeda.
6. Tabel sederhana, merged cells dan nested table.
7. Gambar inline dan floating/anchored.
8. Rumus teks, OMML equation dan object yang tidak didukung.
9. Arab dengan harakat, RTL, dan campuran Arab-Indonesia.
10. Kunci eksplisit, tanpa kunci, marker ambigu, marker ganda, nomor tidak cocok.
11. Dokumen rusak, ZIP bomb fixture, external relationship, embedded OLE/object.
12. File oversize, jumlah entry berlebihan dan resource exhaustion.
13. Upload ulang/idempotensi dan duplicate rows.
14. Tenant, exact-role, assignment, subject dan permission negative access.
15. Preview correction, revalidation, confirm berulang, partial failure dan audit log.

## 10. Dependency decision gate

Audit Composer branch saat ini menunjukkan Laravel Excel/PhpSpreadsheet digunakan untuk XLSX; PHPWord tidak ditemukan pada composer.json/composer.lock yang diperiksa. Belum ada parser DOCX yang disetujui.

Sebelum dependency baru:
- tentukan apakah memakai parser library terpelihara atau OOXML reader terbatas;
- periksa lisensi dan distribusi komersial;
- verifikasi PHP 8.3/Laravel 13 compatibility;
- periksa CVE/advisory, ZIP/XML hardening dan maintenance;
- ukur memory/CPU/timeout pada dokumen uji;
- uji Arabic/RTL, tables, images dan OMML;
- kunci versi pada Composer lock dan jalankan CI security tests.

Tidak ada Composer dependency yang ditambahkan dalam tahap desain ini.

## 11. Pembagian Agent

### Agent 2 — backend bersama
API upload/preview/correction/confirm/report, parser service, normalized DTO, shared batch, staging, file security, idempotency, duplicate detection, validation, tenant/role/assignment, audit, MediaAsset, persistence, ExamSnapshot dan Scoring.

### Agent 3 — Web Guru
Pilihan jalur DOCX/XLSX, pemilihan document profile, upload form, preview blocks, warning/review state, correction UI, revalidation, confirm dan laporan. Implementasi aktif hanya setelah kontrak API Agent 2 tersedia.

### Batas
Agent 3 tidak mengubah controller/policy/schema/migration/importer/shared batch/Composer/MediaAsset/ExamSnapshot/Scoring tanpa persetujuan Agent 2.

## 12. Keputusan tahap

- DOCX: DESAIN AWAL.
- DOCX parser/library: PENDING.
- DOCX upload/API: BLOCKED BY AGENT 2 CONTRACT.
- DOCX preview UI: BLOCKED BY CONTRACT.
- DOC lama: DEFERRED / NEEDS SEPARATE AUDIT.
- PDF/OCR: tetap ditunda; bukan bagian dari parser DOCX.
