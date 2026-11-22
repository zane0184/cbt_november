# DESAIN UI GURU — IMPORT SOAL XLSX / DOCX (WG-01)

Tanggal: 22 November 2026  
Repository: `zane0184/cbt_november`  
Branch pemilik: `work/web-guru-foundation-20261122`  
Status: **DESIGN ONLY — menunggu kontrak backend Agent 2**

## 1. Tujuan dan batas desain

Menyediakan satu pengalaman Guru untuk mengimpor soal dari dua format:
- XLSX: mengikuti template `CBT-QUESTION-1`.
- DOCX: mengikuti document profile yang disetujui atau auto-detect terbatas yang hasilnya tetap wajib ditinjau.

Kedua format memiliki adapter ekstraksi berbeda, tetapi wajib menuju satu normalized draft DTO, satu batch, satu staging lifecycle, satu idempotency contract, satu audit lifecycle, satu penyimpanan Question, dan satu alur preview sampai report.

Dokumen ini hanya rancangan UI Agent 3. Ini bukan kontrak API final, bukan implementasi, dan tidak memberi izin membuat endpoint, parser, batch service, schema, dependency, atau persistence. Semua nama data/aksi backend di bawah bersifat kebutuhan UI konseptual sampai Agent 2 menetapkannya.

## 2. Prinsip pengalaman pengguna

1. Guru hanya dapat mengakses fitur jika server memastikan tepat satu role GURU, tenant aktif, permission, TeacherProfile, penugasan subject/kelas/tahun ajaran, dan lingkup akademik yang sah.
2. Browser tidak menjadi sumber otorisasi. Semua pemeriksaan diulang server pada upload, pembacaan draft, koreksi, revalidation, confirm, cancel, dan report.
3. Tidak ada auto-publish. Konfirmasi Guru hanya meminta penyimpanan sebagai DRAFT.
4. Kurikulum Merdeka dan KBC/Panca Cinta tampil sebagai satu konteks akademik terintegrasi; bukan dua pilihan kurikulum yang saling menggantikan.
5. UI tidak mengarang CP, TP, ATP, KBC, kelas, subject, tahun ajaran, atau sumber.
6. Jenis soal tetap MULTIPLE_CHOICE, MULTIPLE_RESPONSE, TRUE_FALSE, MATCHING, SHORT_ANSWER, ESSAY. Opsi aktif A-D; tidak ada E.
7. SHORT_ANSWER tetap BLOCKED sampai accepted answers terbukti tersimpan, masuk ExamSnapshot, dan digunakan Scoring.
8. ESSAY memerlukan penilaian manual.
9. DOCX answer key hanya candidate dari marker eksplisit dengan nomor dan pemetaan opsi yang pasti. Selain itu NEEDS_REVIEW. Tidak ada tebakan AI atau heuristik.
10. Hasil ekstraksi yang gagal, terpotong, atau tidak pasti harus terlihat sebagai warning; tidak boleh hilang diam-diam.

## 3. Informasi halaman dan navigasi

Rancangan halaman tunggal di bawah modul Bank Soal setelah gate akses Bank Soal disetujui. Sampai saat itu, tautan unduh template XLSX yang ada tetap berdiri sendiri dan modul Bank Soal tetap disabled.

Halaman konseptual:
- Judul: **Import Soal**
- Keterangan: “Impor dokumen menjadi draf soal. Semua soal harus ditinjau dan dikonfirmasi Guru sebelum disimpan sebagai DRAFT.”
- Dua jalur file yang jelas: **Template XLSX** dan **Dokumen Word DOCX**.
- Penjelasan singkat: XLSX cocok untuk data terstruktur; DOCX untuk dokumen dengan pola yang dikenali dan tetap memerlukan preview.
- Tidak menyediakan PDF, DOC lama, CSV, atau format lain pada tahap ini.

## 4. Alur wizard bersama

### Tahap 1 — Pilih format dan file

- Pilih XLSX atau DOCX melalui dua pilihan terpisah.
- File picker menerima hanya ekstensi yang sesuai; batas ukuran/jenis final mengikuti kontrak backend.
- Tampilkan nama file, ukuran, dan tombol hapus/ganti sebelum upload.
- Jangan menganggap ekstensi/MIME browser sebagai bukti keamanan file.
- Berikan peringatan agar Guru tidak mengunggah dokumen berisi data pribadi siswa yang tidak diperlukan.

### Tahap 2 — Konteks akademik

- Pilih subject, kelas/fase, tahun ajaran, dan assignment dari referensi server tenant yang diizinkan.
- Tampilkan konteks CP/TP dan KBC/Panca Cinta sebagai hubungan akademik yang terintegrasi, bukan dropdown kurikulum alternatif.
- Referensi harus berasal dari API tenant-scoped yang disetujui Agent 2.
- Bila referensi tidak tersedia atau tidak cocok dengan assignment, hentikan proses dan tampilkan alasan; jangan membuat nilai default atau ID rekaan.
- Simpan konteks yang dipilih hanya jika kontrak backend menyatakan field tersebut bagian dari draft.

### Tahap 3 — Pemeriksaan awal dan upload

- Tampilkan status pemeriksaan awal: format, ukuran, versi template/profile, serta status pemeriksaan keamanan yang dikembalikan backend.
- XLSX: validasi awal versi `CBT-QUESTION-1`, struktur sheet, header, dan kemungkinan workbook tidak sesuai.
- DOCX: wajib memilih document profile yang tersedia atau menerima rekomendasi auto-detect terbatas; Guru tetap dapat memilih ulang sebelum ekstraksi.
- Tampilkan bahwa upload membuat draft/batch, bukan soal terbit.
- UI tidak menghitung checksum final, menetapkan idempotency key, atau menentukan status keamanan sendiri; gunakan kontrak backend.

### Tahap 4 — Preview ekstraksi

Preview menggunakan struktur netral-format yang sama untuk kedua parser. Per soal, tampilkan:
- nomor/source row atau source paragraph/table range;
- external row ID bila tersedia;
- jenis soal dan status validasinya;
- pertanyaan;
- pilihan A-D atau pasangan matching;
- kandidat kunci eksplisit;
- pembahasan/rubrik;
- bobot dan kesulitan;
- referensi media;
- konteks akademik;
- warning, error, dan status review.

Untuk DOCX tambahkan:
- document profile/version;
- sumber paragraf/numbering/tabel;
- segmen gambar, tabel, rumus, atau konten yang belum didukung;
- metadata Arabic/harakat dan bidi/RTL jika parser mengembalikannya;
- confidence/status ekstraksi yang disediakan parser, tanpa UI membuat skor confidence sendiri.

Status per item yang diusulkan untuk disepakati backend:
- READY_FOR_REVIEW
- NEEDS_REVIEW
- INVALID
- BLOCKED

Status tersebut adalah usulan label UI, bukan enum backend yang sudah disetujui.

### Tahap 5 — Koreksi Guru

- Guru dapat mengedit field yang diizinkan dalam draft.
- Kunci jawaban yang tidak eksplisit atau mapping-nya ambigu harus kosong/NEEDS_REVIEW; UI tidak menyarankan jawaban.
- Guru dapat menandai item untuk dihapus dari batch jika backend menyediakan aksi tersebut; penghapusan tidak menghapus Question existing.
- Konten tabel/gambar/rumus yang tidak dapat dipertahankan harus ditampilkan sebagai warning yang dapat ditinjau.
- SHORT_ANSWER tidak dapat dikonfirmasi selama status BLOCKED.
- ESSAY menampilkan label “Penilaian manual”.
- Aksi simpan koreksi harus mempertahankan source mapping dan audit actor/time.

### Tahap 6 — Validasi ulang

- Tombol “Validasi ulang” memanggil backend.
- Tampilkan error per field dan per soal, bukan hanya notifikasi global.
- Tampilkan perubahan status dari READY/NEEDS_REVIEW/INVALID/BLOCKED sesuai response backend.
- Bila assignment, permission, referensi akademik, atau tenant berubah/kadaluarsa, backend harus menolak dan UI meminta Guru memuat ulang konteks.
- Confirm tetap disabled selama ada error/blocker yang dilarang kontrak.

### Tahap 7 — Konfirmasi

Dialog konfirmasi harus menjelaskan:
- jumlah soal yang siap disimpan;
- jumlah yang ditolak/dikecualikan;
- jenis soal BLOCKED;
- bahwa seluruh soal disimpan sebagai DRAFT;
- tidak ada publikasi otomatis dan tidak ada perubahan pada ujian yang telah diterbitkan.

Konfirmasi harus idempotent menurut kontrak backend. UI mencegah klik ganda untuk pengalaman pengguna, tetapi keamanan idempotensi wajib di server.

### Tahap 8 — Laporan batch

Tampilkan:
- batch ID/status;
- jumlah diterima, ditolak, perlu review, dan gagal;
- daftar soal yang tersimpan sebagai DRAFT;
- alasan kegagalan per baris;
- waktu, actor, dan status audit yang diizinkan untuk ditampilkan;
- aksi unduh laporan bila endpoint/format report disediakan backend;
- aksi kembali ke Bank Soal hanya jika modul dan policy sudah disetujui.

Tidak boleh menampilkan detail storage path, token internal, exception mentah, atau informasi tenant lain.

## 5. Tata letak responsif dan aksesibilitas

### Desktop
- Stepper horizontal.
- Preview tabel dengan kolom ringkas dan panel detail per soal.
- Filter status: semua, perlu review, invalid, blocked, siap ditinjau.
- Panel warning tetap terlihat tanpa menutupi konten.

### Mobile
- Stepper vertikal/ringkas.
- Setiap soal sebagai kartu, bukan tabel lebar yang memaksa scroll horizontal.
- Tombol utama full-width dan urutan aksi konsisten.
- Preview source dan warning dapat dibuka per soal.
- Link template XLSX tetap tersedia melalui navigasi mobile yang ada; perlu dibuktikan lewat browser QA sebelum dinyatakan BROWSER VERIFIED.

### Keyboard dan aksesibilitas
- Semua kontrol memiliki label programatik.
- Urutan Tab mengikuti alur wizard.
- Focus indicator terlihat.
- Error dihubungkan dengan field terkait dan diumumkan melalui live region yang sesuai.
- Warna bukan satu-satunya penanda status.
- Dialog konfirmasi mengelola focus dan mengembalikannya ke pemicu.
- Bahasa UI jelas dan konsisten; teks Arab/Indonesia menggunakan arah teks yang sesuai tanpa membalik struktur halaman keseluruhan.
- Target uji: keyboard-only, zoom 200%, kontras, screen reader smoke test, dan viewport ponsel.

## 6. Model status UX — usulan, bukan kontrak backend

| Tahap | Status tampilan | Makna |
|---|---|---|
| File dipilih | LOCAL_SELECTED | Belum diunggah |
| Upload berjalan | UPLOADING | Menunggu server |
| Pemeriksaan | PREFLIGHT | Validasi format/keamanan |
| Draft dibuat | DRAFT_READY | Batch tersedia untuk preview |
| Perlu koreksi | NEEDS_REVIEW | Guru harus memeriksa |
| Gagal validasi | INVALID | Tidak boleh dikonfirmasi |
| Dibatasi | BLOCKED | Fitur/jenis belum didukung |
| Valid | REVALIDATED | Backend menyatakan valid pada konteks saat itu |
| Konfirmasi | CONFIRMING | Permintaan confirm berjalan |
| Selesai | SAVED_AS_DRAFT | Soal tersimpan sebagai DRAFT |
| Dibatalkan | CANCELLED | Batch dibatalkan sesuai aturan backend |

Backend Agent 2 menentukan enum, transisi, retensi, retry/resume, dan apakah status tertentu digabung/dipisah.

## 7. Kontrak backend yang wajib tersedia sebelum UI diimplementasikan

| Kebutuhan UI | Kontrak Agent 2 yang diperlukan | Status saat desain |
|---|---|---|
| Upload XLSX dan DOCX | Route, request, batas file, response batch | BLOCKED |
| Parser profile DOCX | daftar profile, versi, auto-detect response | BLOCKED |
| Draft DTO bersama | field soal/opsi/key/media/warning/source | BLOCKED |
| Preview | pagination, item detail, status, source map | BLOCKED |
| Correction | field editable, optimistic concurrency/version | BLOCKED |
| Revalidation | aturan ulang, error map, stale context | BLOCKED |
| Confirm | idempotency, transaksi, partial failure, DRAFT | BLOCKED |
| Cancel | aturan pembatalan dan cleanup | BLOCKED |
| Report | ringkasan, detail row, ekspor | BLOCKED |
| Referensi akademik | tenant-scoped subject/grade/year/CP/TP/KBC | BLOCKED |
| Media | MediaAsset attach/ref dan private URL policy | BLOCKED |
| Authorization | exact-role, permission, assignment, tenant | BLOCKED |
| Audit/retention | event, actor, reason, retention/cleanup | BLOCKED |

UI tidak boleh memanggil route yang belum disepakati atau menebak nama endpoint. Setelah Agent 2 memberi keputusan, kontrak harus dicocokkan dengan model, controller, policy, service, migration, dan test aktual.

## 8. Keamanan dan integritas akademik

- Semua file diperiksa server-side berdasarkan extension, MIME, magic/package signature, ukuran, struktur, ZIP expansion, XML DTD/ENTITY, external relationships, formula, macro/embedded objects, dan resource limits.
- Private staging storage; tidak ada file import yang dapat diakses publik.
- Tenant, exact-role, permission, assignment, subject, kelas/fase, tahun ajaran, CP/TP/KBC diperiksa server pada setiap mutasi.
- Upload, preview, correction, revalidation, confirm, cancel, retry, dan report diaudit.
- Duplicate detection, row fingerprint, checksum, confirm berulang, dan concurrency diselesaikan server-side.
- Tidak boleh ada cross-tenant reference.
- Tidak ada auto-publish.
- Tidak boleh ada data akademik yang diisi dari tebakan.
- DOCX embedded media/OMML/table yang gagal diparse harus ditandai.
- Parser tidak boleh mengikuti external relationship atau menjalankan macro/OLE/script.
- Retention dan cleanup mengikuti keputusan backend; UI tidak menghapus staging secara langsung.

## 9. Matriks uji UI setelah kontrak tersedia

1. Exact-role GURU, ADMIN_MADRASAH, SISWA, multi-role, nonaktif, tanpa TeacherProfile.
2. Tenant berbeda dan assignment tidak cocok/kadaluarsa.
3. XLSX template valid, versi tidak dikenal, header berubah, sheet hilang, opsi E, formula/external link, file rusak.
4. DOCX profile valid/tidak cocok, numbering manual/otomatis, nomor tidak berurutan, tabel, gambar, OMML, Arabic/harakat, mixed RTL.
5. Answer key explicit, ambiguous, missing, nomor mismatch, opsi tidak lengkap.
6. Duplicate file, duplicate row, double confirm, retry, session expired, stale draft.
7. Partial failure, cancellation, retention cleanup.
8. Viewport desktop/tablet/mobile, keyboard, zoom, contrast, screen reader smoke.
9. Semua hasil import tetap DRAFT; tidak ada auto-publish.

## 10. Kepemilikan dan keputusan

**Agent 2:** API, parser, shared DTO, batch, staging, idempotency, duplicate detection, persistence, authorization, security, audit, media, snapshot, scoring contract.

**Agent 3:** halaman Guru, pemilihan format/profile, upload interaction setelah API tersedia, preview, correction, warning, revalidation, confirm, report, responsive/accessibility QA.

**Belum disetujui:** seluruh endpoint, enum status, DTO field final, batch reuse, parser profile, dependency, retention, report format, dan persistence mapping.

## 11. Keputusan yang ditahan

- Tidak ada implementasi UI import pada tahap ini.
- Tidak ada parser XLSX/DOCX.
- Tidak ada upload/preview/correction/confirm API.
- Tidak ada batch service/schema/dependency baru.
- Tidak mengubah QuestionController/QuestionPolicy/ImportBatch/ImportBatchRow.
- Tidak mengaktifkan Bank Soal operasional.
- PDF/OCR dan DOC lama tetap deferred.
- SHORT_ANSWER tetap BLOCKED; ESSAY manual review.
- Tidak ada merge, PR, deploy, atau migration produksi.

**Kesimpulan:** rancangan UI siap digunakan sebagai bahan review kontrak bersama Agent 2. Implementasi UI baru boleh dimulai setelah kontrak backend disetujui dan terbukti pada source.
