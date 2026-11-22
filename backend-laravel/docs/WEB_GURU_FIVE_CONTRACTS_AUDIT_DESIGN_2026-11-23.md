# Web Guru — Audit & Kontrak Integrasi Kurikulum, Import, Media, dan SHORT_ANSWER

Tanggal audit: 23 November 2026  
Repository: `zane0184/cbt_november`  
Branch asal yang diperiksa: `work/web-guru-phase1-audit-implementation-20261122`  
HEAD asal: `5962646bdcf5a2702ef162c43373815483906e5c`  
Branch kerja dokumen: `work/web-guru-five-contracts-20261123`

> Dokumen ini adalah audit dan rancangan integrasi Web Guru. Kontrak backend final untuk referensi kurikulum, periode akademik, mapping kurikulum, snapshot, media staging, dan accepted answer dimiliki Agent 2 sesuai keputusan owner pada Issue #8. Karena itu, field/schema di bawah yang berstatus usulan tidak boleh dianggap sebagai schema final atau diimplementasikan ke DB sebelum disetujui pemilik kontrak.

## 1. AUDIT — sumber resmi Pembelajaran Mendalam, 8 Dimensi Profil Lulusan, dan Panca Cinta

### 1.1 Rujukan pemerintah yang dapat dijadikan sumber data

Pemisahan jenis sumber penting: peraturan/keputusan adalah dasar normatif; surat edaran menyampaikan arahan implementasi; panduan memberi uraian operasional. Jangan menganggap surat edaran sebagai pengganti Permen/KMA.

| Urutan | Dokumen pemerintah | Tanggal / versi | Fungsi dan status yang dapat dibuktikan |
|---|---|---|---|
| 1 | Permendikdasmen No. 10 Tahun 2025 — Standar Kompetensi Lulusan | Ditetapkan 10 Juni 2025; berlaku 13 Juni 2025 | Dasar normatif 8 dimensi profil lulusan; status JDIH BPK: Berlaku. |
| 2 | Permendikdasmen No. 13 Tahun 2025 — perubahan atas Permendikbudristek No. 12 Tahun 2024 tentang Kurikulum | Ditetapkan 11 Juli 2025; berlaku 15 Juli 2025 | Menguatkan arah kurikulum dan pendekatan Pembelajaran Mendalam; bukan kurikulum baru. Status JDIH BPK: Berlaku. |
| 3 | Kepmendikdasmen No. 126/P/2025 — Pedoman Implementasi Pembelajaran Mendalam | Ditetapkan 4 September 2025; dipakai mulai TA 2025/2026 | Pedoman operasional PM untuk PAUD, pendidikan dasar, dan menengah. |
| 4 | KMA No. 1503 Tahun 2025 — perubahan atas KMA No. 450 Tahun 2024 tentang Pedoman Implementasi Kurikulum pada RA/Madrasah | Ditetapkan 22 September 2025 | Rujukan khusus madrasah; mengintegrasikan Pembelajaran Mendalam dan Kurikulum Berbasis Cinta/Panca Cinta. KMA 450/2024 tetap berlaku sepanjang tidak bertentangan dengan perubahan KMA 1503/2025. |
| 5 | Kepdirjen Pendis No. 6077 Tahun 2025 — Panduan Kurikulum Berbasis Cinta | Ditetapkan 22 Juli 2025 | Panduan KBC bagi madrasah pemerintah dan masyarakat; menjadi acuan pelaksanaan pembelajaran. |
| 6 | Surat Dirjen Pendis No. B-279/Dt.I.I/PP.00/07/2025 | 22 Juli 2025 | Surat pengantar penyampaian Panduan KBC/Kepdirjen 6077/2025 kepada Kanwil Kemenag seluruh Indonesia. |
| 7 | Surat Edaran Dirjen Pendis No. 10 Tahun 2025 — Implementasi Kurikulum Berbasis Cinta | 24 Juli 2025 | Edaran implementasi/sosialisasi KBC yang menindaklanjuti Kepdirjen Pendis 6077/2025. Salinan yang ditemukan perlu dicocokkan dengan naskah TTE/arsip primer Ditjen Pendis sebelum dijadikan sumber master otomatis. |
| 8 | Surat Dirjen Pendis No. B-572/Dt.I.I/PP.00/11/2025 | 19 November 2025 | Surat pengantar panduan implementasi kurikulum madrasah yang sesuai KMA 1503/2025; meminta diketahui, dipedomani, dan disosialisasikan. |
| 9 | Permendikdasmen No. 1 Tahun 2026 — Standar Proses | Ditetapkan 2 Januari 2026; berlaku 5 Januari 2026 | Regulasi standar proses yang lebih baru dan berlaku; relevan untuk alur perencanaan, pelaksanaan, dan penilaian proses pembelajaran. Bukan sumber definisi 8 dimensi atau pengganti KMA madrasah. |

Sumber primer/pemerintah untuk verifikasi:
- Permendikdasmen 10/2025 (JDIH BPK): https://peraturan.bpk.go.id/Details/321419/permendikdasmen-no-10-tahun-2025
- Permendikdasmen 13/2025 (JDIH BPK): https://peraturan.bpk.go.id/Details/322506/permendikdasmen-no-13-tahun-2025
- Permendikdasmen 1/2026 (JDIH BPK): https://peraturan.bpk.go.id/Details/344196/permendikdasmen-no-1-tahun-2026
- Kepmendikdasmen 126/P/2025 (salinan dokumen pemerintah/BPMP): https://bpmpbabel.kemendikdasmen.go.id/PPID/wp-content/uploads/2025/09/Salinan-Kepmendikdasmen-Nomor-126_P_2025-CAP.pdf
- KMA 1503/2025 (salinan pada domain Kanwil Kemenag): https://sulteng.kemenag.go.id/files/show/20251020162801surat-penyampaian-kma-kurikulum-2025%2Blampiran_compressed-tzR.pdf
- Kepdirjen Pendis 6077/2025 (salinan pada domain Kanwil Kemenag): https://sulteng.kemenag.go.id/files/show/panduan-kurikulum-berbasis-cinta-baT.pdf
- Panduan Kokurikuler dan surat pengantar B-572/2025 (salinan dokumen Kemenag): https://mdrsh.id/v3/media/ac70e61b11ba9ef12a76630c61f8ad6b539.pdf
- Penegasan Kemendikdasmen bahwa Permendikdasmen 13/2025 bukan kurikulum baru: https://gtk.kemendikdasmen.go.id/news/siaran-pers/c034d26f-83b6-4b4b-926c-b572e665e477

### 1.2 Delapan dimensi yang harus menjadi referensi terkontrol

Sesuai Pasal 4 Permendikdasmen 10/2025:
1. Keimanan dan ketakwaan terhadap Tuhan Yang Maha Esa
2. Kewargaan
3. Penalaran kritis
4. Kreativitas
5. Kolaborasi
6. Kemandirian
7. Kesehatan
8. Komunikasi

Pembelajaran Mendalam adalah pendekatan pembelajaran, bukan nama kurikulum baru. Kepmendikdasmen 126/P/2025 menjelaskan prinsip berkesadaran (*mindful*), bermakna (*meaningful*), dan menggembirakan (*joyful*) serta olah pikir, olah hati, olah rasa, dan olah raga secara holistik-terpadu. Delapan dimensi adalah profil lulusan yang dituju; jangan diperlakukan sebagai delapan jenis soal atau sebagai pengganti CP/TP.

### 1.3 Panca Cinta untuk konteks madrasah

KMA 1503/2025 dan Panduan KBC memuat nilai:
1. Cinta Allah dan Rasul-Nya
2. Cinta Ilmu
3. Cinta Lingkungan
4. Cinta Diri dan Sesama Manusia
5. Cinta Tanah Air

Panca Cinta adalah ruh/nilai yang mengintegrasikan proses pendidikan, bukan mata pelajaran baru dan bukan label pengganti CP/TP.

### 1.4 Audit source saat ini

Pada `TeacherCurriculumContextService::forTenant()`:
- `OUTCOME_TYPES` hanya memuat CP, TP, ATP, KBC_CINTA.
- `panca_cinta` dikembalikan sebagai array literal; belum dibuktikan sebagai referensi versi resmi dengan sumber/provenance.
- `official_cp_verified`, `deep_learning_available`, dan `graduate_dimensions_available` semuanya masih `false`.
- Struktur menghitung `DIMENSI_CINTA` dan `PETA_KASIH`, bukan delapan Dimensi Profil Lulusan.
- Data outcome dibatasi 100 item dan hanya mengembalikan code/description/version_title; tidak menyertakan identitas sumber, versi, tanggal efektif, atau riwayat.

Kesimpulan: UI tidak boleh menampilkan referensi ini sebagai “resmi/terverifikasi”. Harus menampilkan status sumber belum tervalidasi sampai Agent 2 mengisi registry resmi dan provenance.

## 2. DESAIN — kontrak akademik tahun ajaran, materi/topik, dan relasi soal

### 2.1 Fakta source baseline

- `AcademicYear` adalah model tenant-local dan memiliki relasi ke semester/rombel. `TeacherPortalDataService::academicContext()` mengasumsikan tepat satu tahun ajaran aktif dan tepat satu semester aktif.
- `QuestionVersion` memiliki `element_id`, `learning_outcome_id`, dan `learning_objective_id` legacy, tetapi tidak memiliki relasi eksplisit ke tahun ajaran atau materi/topik.
- `QuestionMultiCurriculumMapping` saat ini terhubung ke `question_id`, bukan `question_version_id`. Akibatnya mapping tidak secara langsung mengunci konteks kurikulum pada revisi soal yang dipakai ujian.
- `MultiCurriculumVersion`, `MultiCurriculumStructure`, `MultiLearningOutcome`, dan `TenantCurriculumRegistration` sudah ada. Registrasi kurikulum mengikat tenant ke versi kurikulum dan owner (system atau tenant).
- XLSX parser saat ini menolak nilai pada kolom `Tahun Ajaran Ref`, `Materi/Topik`, `KBC Ref`, dan `Elemen KBC Ref`; parser menyatakan metadata tersebut belum memiliki kontrak tervalidasi.

### 2.2 Prinsip kontrak yang harus diputuskan Agent 2

1. Tahun ajaran adalah identitas akademik tenant, bukan teks bebas. Referensi harus berupa ID yang tervalidasi terhadap tenant dan semester/rombel yang relevan.
2. Bank soal dapat digunakan ulang lintas tahun ajaran. Karena itu, jangan otomatis mengunci seluruh identitas soal ke satu tahun ajaran tanpa konsep “konteks penggunaan/penugasan” yang eksplisit.
3. Materi/topik harus merupakan referensi terstruktur dan berversi yang berada dalam konteks kurikulum/struktur/subject/fase yang benar; jangan menerima string bebas sebagai referensi resmi.
4. CP/TP/ATP, KBC/Panca Cinta, dan DPL harus dapat dipetakan pada **QuestionVersion** yang immutable, bukan hanya pada Question induk.
5. Snapshot ujian harus menyalin identitas, kode, nama/label, versi kurikulum, sumber, dan konteks akademik yang dipakai saat ujian dibuat. Perubahan master data setelah ujian diterbitkan tidak boleh mengubah sejarah.
6. Jika satu soal mempunyai beberapa pemetaan kurikulum, snapshot harus menyimpan seluruh mapping yang telah disahkan, bukan mengambil mapping aktif terbaru secara dinamis.
7. Mapping wajib divalidasi terhadap tenant instalasi, curriculum registration, curriculum owner, subject, fase/kelas, CP/TP/ATP dan status aktif. Penolakan harus *fail closed*.

### 2.3 Usulan bentuk kontrak data (PENDING APPROVAL, bukan schema final)

Satu agregat versi soal perlu mempunyai:
- identitas QuestionVersion;
- subject, grade/phase;
- konteks tahun ajaran (bila memang diperlukan untuk konteks pengajaran/penggunaan, bukan sekadar tanggal pembuatan soal);
- referensi curriculum registration + MultiCurriculumVersion + curriculum owner;
- relasi CP/TP/ATP yang sesuai struktur;
- relasi materi/topik ke node struktur yang tervalidasi;
- relasi Panca Cinta/KBC dan DPL yang bersumber dari registry resmi;
- provenance sumber (issuer, document number, title, document version, URL/URI, effective date, retrieval/verification date, checksum dokumen);
- status validasi dan riwayat mapping.

Nama tabel/kolom dan kardinalitas harus diputuskan Agent 2 setelah menilai schema/migration baseline dan kebutuhan backward compatibility. Jangan menambah migration paralel dari Web Guru.

## 3. DESAIN — versi kurikulum dan pemetaan sampai snapshot ujian

Alur wajib:
1. Admin mendaftarkan versi kurikulum yang sah untuk instalasi madrasah.
2. Referensi global/tenant memiliki owner, nomor dokumen, versi, tanggal berlaku, sumber, checksum, status, dan riwayat.
3. Guru memilih konteks akademik yang memang tersedia pada penugasan subject/kelas/tahun ajaran.
4. Guru memetakan versi soal ke CP/TP/ATP, materi/topik, Panca Cinta, dan DPL yang valid.
5. Saat soal disimpan sebagai versi baru, mapping menjadi bagian dari versi tersebut; versi lama tidak ditimpa.
6. Saat snapshot ujian dibuat, snapshot menyalin versi kurikulum dan seluruh mapping berikut label/kode serta provenance. Snapshot tidak boleh mengandalkan join dinamis ke master referensi yang bisa berubah.
7. Hasil ujian/analitik dapat menunjukkan mapping historis yang sama dengan saat ujian berlangsung.

Pemisahan makna:
- Kurikulum Merdeka adalah versi kerangka kurikulum yang terdaftar.
- Panca Cinta adalah kerangka nilai KBC.
- Pembelajaran Mendalam adalah pendekatan pembelajaran.
- Delapan dimensi adalah profil lulusan.
Keempatnya tidak boleh disimpan sebagai satu enum atau satu kolom generik yang kehilangan tipe dan sumber.

## 4. DESAIN — kontrak media XLSX: ekstraksi, penyimpanan, pemasangan

### 4.1 Fakta source baseline

- `MediaAsset` dan `question_version_media` sudah tersedia.
- Relasi media melekat ke QuestionVersion dengan role STEM/OPTION/EXPLANATION dan sequence.
- `ExamSnapshotService` sudah menyalin daftar media ke `snapshot_media`.
- Parser XLSX saat ini menolak `Media Ref` di Data Soal maupun Detail Opsi. Belum ada ekstraksi drawing/image dari XLSX ke staging.
- `ImportBatch` dan `ImportBatchRow` sudah tersedia dengan tenant scope, checksum file, status/counter, row summary, dan encrypted payload. Jangan membuat batch table kedua.

### 4.2 Kontrak media yang perlu disetujui

- XLSX menggunakan referensi media lokal yang stabil (mis. Media Ref), bukan path filesystem atau URL bebas.
- Template harus mendefinisikan bagaimana media embedded di workbook dipasangkan dengan baris/soal/opsi dan role-nya. Jika format workbook tidak dapat mengikat gambar ke referensi secara deterministik, desain sheet manifest media harus diputuskan sebelum implementasi.
- Adapter ekstraksi mengambil binary media dari drawing/relationship XLSX, bukan mengunduh URL eksternal.
- Validasi keamanan harus memeriksa magic bytes/MIME aktual, ekstensi, ukuran, jumlah file, dimensi gambar, checksum, workbook ZIP entry count/expanded size, formula, external link, macro/OLE, dan path traversal. SVG dan format aktif harus ditolak kecuali ada sanitizer teruji.
- Staging harus private dan terisolasi per instalasi/tenant serta per batch; nama file fisik random/UUID, tidak memakai nama unggahan sebagai path.
- Simpan metadata original name hanya sebagai metadata audit yang disanitasi.
- Preview hanya melalui endpoint berotorisasi dan URL sementara; jangan membuat file staging publik.
- Saat confirm, pemasangan ke QuestionVersion harus satu transaksi dengan persistensi soal. Jika gagal, rollback relasi dan lakukan cleanup orphan staging.
- Media hanya boleh dipasang pada QuestionVersion DRAFT. Versi PUBLISHED/LOCKED read-only.
- MediaRef harus dipetakan ke MediaAsset ID yang sudah tervalidasi; role dan sequence harus deterministik.
- Snapshot menyimpan referensi immutable ke media asset dan metadata minimum. Akses media oleh peserta ujian wajib memeriksa assignment attempt/exam/tenant; storage_path tidak boleh bocor di payload siswa.
- Audit event mencatat upload, extract, reject, preview, attach, detach, confirm, cleanup, dan alasan kegagalan.

### 4.3 Syarat uji media

- valid XLSX tanpa gambar; satu gambar; banyak gambar; gambar pada stem; gambar pada opsi; gambar pada penjelasan.
- ekstensi palsu/MIME palsu, file terlalu besar, gambar korup, zip bomb/entry berlebihan, path traversal, external relationship, macro/OLE, formula.
- duplikasi checksum dan retry idempotent.
- kegagalan transaksi saat attach: tidak ada media orphan/relasi setengah jadi.
- media milik instalasi lain tidak dapat dipasang/diakses.
- versi PUBLISHED tidak dapat diubah.
- snapshot tetap dapat mengambil media yang sama setelah versi soal baru dibuat atau master media dihapus secara logis.
- peserta ujian tidak dapat melihat accepted answer, is_correct, explanation privat, atau storage path.

## 5. DESAIN — accepted answer dari authoring hingga snapshot dan scoring SHORT_ANSWER

### 5.1 Fakta source baseline

- `QuestionVersion` fillable dan migration versi soal belum memuat kolom `metadata` atau struktur accepted-answer khusus.
- XLSX parser menolak `Accepted Answer` pada Data Soal dan Detail Opsi.
- `SHORT_ANSWER` tidak termasuk `ALLOWED_TYPES` parser.
- `ExamSnapshotService` pada branch Web Guru hanya menyalin difficulty, cognitive_level, dan question_type ke metadata snapshot; tidak menyalin accepted answers.
- Branch Agent 2 yang diperiksa menolak SHORT_ANSWER pada snapshot creation dan ScoringService secara eksplisit. Ini adalah guard yang benar untuk mencegah soal tipe ini dipakai sebelum kontrak lengkap.
- ScoringService baseline memiliki fungsi evaluasi accepted answer yang membaca `snapshot.metadata['accepted_answers']`, tetapi tidak ada bukti alur authoring → QuestionVersion → snapshot yang mengisi struktur tersebut. Fungsi lama tersebut tidak cukup untuk menyatakan SHORT_ANSWER siap.

### 5.2 Kontrak accepted answer yang harus diputuskan

1. Accepted answer melekat pada QuestionVersion, bukan Question induk, agar setiap revisi soal memiliki kunci yang sesuai.
2. Dukung lebih dari satu alternatif jawaban yang diterima.
3. Setiap alternatif perlu punya urutan, status, bahasa/locale bila diperlukan, dan profil normalisasi yang berversi.
4. Normalisasi harus eksplisit dan konsisten antara input siswa dan kunci. Minimal keputusan: trim, whitespace normalization, case sensitivity, Unicode normalization. Jangan otomatis menghapus harakat/tanda baca/diakritik Arab tanpa aturan yang disetujui; itu dapat mengubah makna.
5. Simpan nilai accepted answer sebagai data privat. API/Livewire payload siswa tidak boleh menerima teks kunci, hash kunci yang dapat dipakai menebak, atau indikator benar.
6. Snapshot harus menyalin accepted answer dan aturan normalisasi sebagai konfigurasi scoring privat yang immutable.
7. Scoring hanya membaca snapshot, tidak melakukan join ke QuestionVersion aktif/master bank soal.
8. Jika accepted answer kosong/tidak valid, snapshot dan scoring harus ditolak (*fail closed*).
9. Perubahan accepted answer setelah ujian diterbitkan tidak boleh mengubah skor atau kunci ujian lama.
10. Regrading harus membuat scoring version baru dengan audit log; scoring version lama tetap ada.

### 5.3 Jalur UI Web Guru yang harus disiapkan setelah kontrak disetujui

- Form QuestionBank: pilih tipe SHORT_ANSWER; tambah/hapus alternatif accepted answer; tampilkan instruksi normalisasi yang dipilih; validasi minimal satu alternatif.
- XLSX: satu baris per alternatif atau bentuk detail yang disepakati; parser memetakan ke satu DTO yang sama dengan authoring UI.
- Preview import: tampilkan accepted answer hanya pada layar Guru yang berotorisasi; kesalahan per baris dapat dikoreksi sebelum confirm.
- Confirm: revalidasi dan persist sebagai DRAFT; tidak auto-publish.
- Edit versi: perubahan accepted answer membuat versi baru; versi published tetap immutable.
- Exam Builder: menolak memasukkan SHORT_ANSWER sampai backend snapshot/scoring dan uji integrasi dinyatakan lulus.
- Result Detail: hasil SHORT_ANSWER harus jelas membedakan AUTO_SCORED vs MANUAL_REVIEW; untuk model accepted answer yang deterministik, status scoring tetap dapat diaudit dengan versi aturan.

### 5.4 Syarat uji integrasi SHORT_ANSWER

- accepted answer satu dan banyak alternatif.
- trim, spasi ganda, case sensitivity, Unicode NFC/NFKC sesuai profil.
- teks Arab berharakat dan tanpa harakat tidak boleh dianggap setara kecuali profil normalisasi resmi mengizinkan.
- jawaban kosong, accepted answer kosong, tipe tidak didukung, malformed payload.
- accepted answer tidak pernah muncul di API siswa, Livewire payload siswa, export hasil siswa, log yang tidak berwenang, atau media endpoint.
- snapshot lama tidak berubah setelah authoring versi baru.
- scoring mengambil accepted answer dari snapshot yang benar, bukan versi soal terbaru.
- regrade membuat scoring version baru; history dan current pointer konsisten.
- dua attempt paralel tidak dapat mengubah accepted answer/snapshot.
- tenant/instalasi lain tidak dapat membaca atau memasang data accepted answer.

## 6. IMPLEMENTASI — batas ownership dan urutan kerja

### Agent 2 (pemilik kontrak backend bersama)
Harus memberikan keputusan tertulis APPROVED/REJECTED/NEEDS DESIGN untuk:
1. Registry resmi PM, 8 DPL, Kurmer, KBC/Panca Cinta: sumber, versi, effective date, provenance, history, status berlaku/dicabut.
2. Kontrak academic year + subject/grade/phase + material/topic + relation to QuestionVersion.
3. Mapping kurikulum versioned dan snapshot metadata.
4. XLSX/DOCX common DTO, ImportBatch lifecycle, media extraction/staging/attach/retention.
5. Accepted answer schema, snapshot-private payload, normalization profile, scoring rule, and SHORT_ANSWER enablement gate.

### Agent Web Guru (scope implementasi UI)
Setelah kontrak disetujui:
- update TeacherCurriculumContextService view model untuk status verifikasi dan data versi resmi;
- update QuestionBank authoring form, validation, preview, and version editing;
- update XLSX template UI, parser adapter, preview/correction/confirm UI to consume shared DTO/batch API;
- update media preview/attach/detach controls and tests;
- update Exam Builder validation and Result Detail presentation;
- add UI/feature regression tests for tenant, role, assignment, immutable versions, media, and key confidentiality.

### Urutan dependensi
A. Official source/provenance registry + curriculum structure contract  
B. Academic year/material/topic contract and QuestionVersion mapping  
C. Snapshot copy contract for all curriculum metadata  
D. Media staging/extraction/attach contract  
E. Accepted answer authoring + snapshot-private storage + scoring  
F. Web Guru UI integration and end-to-end disposable MySQL CI  
G. UAT Guru/Admin/Siswa before production readiness

Tidak boleh mengaktifkan SHORT_ANSWER sebelum E dan F lulus.

## 7. PENGUJIAN — bukti yang belum ada

Pada saat audit dokumen ini:
- Belum ada bukti CI yang menguji lima kontrak ini secara end-to-end.
- Belum ada bukti registry resmi dengan checksum/provenance/effective date/history.
- Belum ada bukti mapping QuestionVersion → snapshot yang mencakup tahun ajaran, materi/topik, Kurmer, KBC/Panca Cinta, PM, dan 8 DPL.
- Belum ada bukti ekstraksi gambar XLSX dan transactional attach media.
- Belum ada bukti accepted answer sampai snapshot dan scoring; guard SHORT_ANSWER tetap harus aktif.
- Tidak ada migration produksi, merge ke main, deployment, atau perubahan DB produksi.

## 8. Checkpoint

- Dokumen ini dibuat pada branch kerja terpisah dari HEAD Web Guru yang diperiksa.
- Tidak ada file source existing yang diubah pada checkpoint ini; karena itu tidak ada backup source yang diperlukan untuk penambahan dokumen baru.
- Tidak ada perubahan pada branch Agent 2.
- Langkah berikut: kirim keputusan/kontrak ini ke Agent 2 melalui Issue #8; setelah jawaban owner tersedia, implementasikan UI Web Guru pada branch kerja ini dengan backup byte-exact untuk setiap file existing yang akan diubah.
